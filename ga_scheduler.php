<?php
// ga_scheduler.php - Advanced Genetic Algorithm Implementation for College Scheduling

class GAScheduler {
    private $mysqli;
    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private $timeSlots = [
        ['08:00:00', '09:00:00'],
        ['09:00:00', '10:00:00'],
        ['10:00:00', '11:00:00'],
        ['11:00:00', '12:00:00'],
        ['13:00:00', '14:00:00'],
        ['14:00:00', '15:00:00'],
        ['15:00:00', '16:00:00'],
        ['16:00:00', '17:00:00']
    ];
    
    // GA Parameters
    private $populationSize = 200;
    private $generations = 1000;
    private $mutationRate = 0.05;
    private $crossoverRate = 0.8;
    private $eliteSize = 20;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function generateSchedule() {
        try {
            // Clear existing schedules
            $this->mysqli->query("DELETE FROM schedules");
            $this->mysqli->query("DELETE FROM schedule_conflicts");
            
            // Fetch all required data
            $sections = $this->getSections();
            $subjects = $this->getSubjects();
            $teachers = $this->getTeachers();
            $rooms = $this->getRooms();
            $teacherSubjects = $this->getTeacherSubjects();
            
            if (empty($sections) || empty($subjects) || empty($teachers) || empty($rooms)) {
                return ['success' => false, 'message' => 'Insufficient data to generate schedule'];
            }
            
            // Generate required assignments
            $requiredAssignments = $this->generateRequiredAssignments($sections, $subjects, $teacherSubjects);
            
            if (empty($requiredAssignments)) {
                return ['success' => false, 'message' => 'No valid assignments found'];
            }
            
            // Pre-build section map for capacity checks
            $sectionMap = [];
            foreach ($sections as $sec) {
                $sectionMap[$sec['id']] = $sec['max_students'];
            }
            
            // Initialize population
            $population = $this->initializePopulation($requiredAssignments, $teachers, $rooms, $sectionMap);
            
            // Run genetic algorithm with sections for capacity
            $bestSchedule = $this->runGeneticAlgorithm($population, $teachers, $sectionMap);
            
            if (empty($bestSchedule)) {
                return ['success' => false, 'message' => 'Failed to generate valid schedule after GA iterations'];
            }
            
            // Save best schedule to database
            $this->saveSchedule($bestSchedule);
            
            // Check for remaining conflicts
            $conflicts = $this->checkConflicts($bestSchedule);
            
            $message = "Schedule generated successfully! ";
            if ($conflicts > 0) {
                $message .= "Warning: $conflicts conflicts detected and logged.";
            } else {
                $message .= "No conflicts detected.";
            }
            
            return ['success' => true, 'message' => $message, 'conflicts' => $conflicts];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function getSections() {
        $result = $this->mysqli->query("
            SELECT s.*, yl.name as year_level_name, c.name as course_name 
            FROM sections s 
            LEFT JOIN year_levels yl ON s.year_level_id = yl.id 
            LEFT JOIN courses c ON s.course_id = c.id 
            ORDER BY s.name
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    private function getSubjects() {
        $result = $this->mysqli->query("
            SELECT s.*, d.name as department_name, c.name as course_name, yl.name as year_level_name
            FROM subjects s 
            LEFT JOIN departments d ON s.department_id = d.id 
            LEFT JOIN courses c ON s.course_id = c.id 
            LEFT JOIN year_levels yl ON s.year_level_id = yl.id 
            ORDER BY s.name
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    private function getTeachers() {
        $result = $this->mysqli->query("
            SELECT t.*, d.name as department_name 
            FROM teachers t 
            LEFT JOIN departments d ON t.department_id = d.id 
            ORDER BY t.last_name, t.first_name
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    private function getRooms() {
        // Cache rooms to avoid repeated DB hits during GA/mutations
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $result = $this->mysqli->query("SELECT * FROM rooms ORDER BY name");
        $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        return $cache;
    }
    
    private function getTeacherSubjects() {
        $result = $this->mysqli->query("
            SELECT ts.*, t.first_name, t.last_name, s.name as subject_name, s.code as subject_code
            FROM teacher_subjects ts
            LEFT JOIN teachers t ON ts.teacher_id = t.id
            LEFT JOIN subjects s ON ts.subject_id = s.id
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    private function generateRequiredAssignments($sections, $subjects, $teacherSubjects) {
        $assignments = [];

        // Pre-index subjects by (year_level_id|course_id)
        $subjectsByKey = [];
        foreach ($subjects as $subject) {
            $key = $subject['year_level_id'] . '|' . $subject['course_id'];
            if (!isset($subjectsByKey[$key])) {
                $subjectsByKey[$key] = [];
            }
            $subjectsByKey[$key][] = $subject;
        }

        // Pre-index teacher_subjects by subject_id => [teacher_id, ...]
        $teachersBySubjectId = [];
        foreach ($teacherSubjects as $ts) {
            $sid = $ts['subject_id'];
            if (!isset($teachersBySubjectId[$sid])) {
                $teachersBySubjectId[$sid] = [];
            }
            $teachersBySubjectId[$sid][] = $ts['teacher_id'];
        }
        
        foreach ($sections as $section) {
            $key = $section['year_level_id'] . '|' . $section['course_id'];
            $sectionSubjects = $subjectsByKey[$key] ?? [];

            foreach ($sectionSubjects as $subject) {
                $teacherIds = $teachersBySubjectId[$subject['id']] ?? [];
                if (!empty($teacherIds)) {
                    // Create required number of class sessions per week
                    $sessionsPerWeek = (int) $subject['hours_per_week'];
                    for ($i = 0; $i < $sessionsPerWeek; $i++) {
                        $assignments[] = [
                            'section_id' => $section['id'],
                            'subject_id' => $subject['id'],
                            'section_name' => $section['name'],
                            'subject_name' => $subject['name'],
                            'subject_code' => $subject['code'],
                            'available_teachers' => $teacherIds,
                            'section_max_students' => $section['max_students']
                        ];
                    }
                }
            }
        }
        
        return $assignments;
    }
    
    private function initializePopulation($requiredAssignments, $teachers, $rooms, $sectionMap) {
        $population = [];
        $daysCount = count($this->days);
        $timeSlotsCount = count($this->timeSlots);
        $roomsCount = count($rooms);
        
        // Pre-filter rooms by capacity for each assignment to avoid invalid initial populations
        $roomMapByCapacity = [];
        foreach ($rooms as $room) {
            $cap = $room['capacity'];
            if (!isset($roomMapByCapacity[$cap])) {
                $roomMapByCapacity[$cap] = [];
            }
            $roomMapByCapacity[$cap][] = $room;
        }
        
        for ($i = 0; $i < $this->populationSize; $i++) {
            $chromosome = [];
            
            foreach ($requiredAssignments as $assignment) {
                $availTeachersCount = count($assignment['available_teachers']);
                $sectionMax = $assignment['section_max_students'];
                // Randomly select teacher
                $teacherId = $assignment['available_teachers'][mt_rand(0, $availTeachersCount - 1)];
                // Select room with sufficient capacity
                $validRooms = $roomMapByCapacity[$sectionMax] ?? [];
                if (empty($validRooms)) {
                    // Fallback to any room if no exact match
                    $validRooms = $rooms;
                }
                $validRoomCount = count($validRooms);
                $room = $validRooms[mt_rand(0, $validRoomCount - 1)];
                $roomId = $room['id'];
                $day = $this->days[mt_rand(0, $daysCount - 1)];
                $timeSlot = $this->timeSlots[mt_rand(0, $timeSlotsCount - 1)];
                
                $chromosome[] = [
                    'section_id' => $assignment['section_id'],
                    'subject_id' => $assignment['subject_id'],
                    'teacher_id' => $teacherId,
                    'room_id' => $roomId,
                    'day' => $day,
                    'start_time' => $timeSlot[0],
                    'end_time' => $timeSlot[1],
                    'section_max_students' => $sectionMax
                ];
            }
            
            $population[] = $chromosome;
        }
        
        return $population;
    }
    
    private function runGeneticAlgorithm($population, $teachers, $sectionMap) {
        $bestFitness = -999999;
        $bestChromosome = null;
        $noImprovementCount = 0;
        $maxNoImprovement = 100; // Stop if no improvement for 100 generations
        
        for ($generation = 0; $generation < $this->generations; $generation++) {
            // Evaluate fitness
            $fitnessScores = [];
            foreach ($population as $index => $chromosome) {
                $fitness = $this->calculateFitness($chromosome, $teachers, $sectionMap);
                $fitnessScores[$index] = $fitness;
                
                if ($fitness > $bestFitness) {
                    $bestFitness = $fitness;
                    $bestChromosome = $chromosome;
                    $noImprovementCount = 0;
                } else {
                    $noImprovementCount++;
                }
            }
            
            // Early termination if perfect solution found (no conflicts, good capacity, balanced)
            if ($bestFitness >= -50) {  // Allow small bonuses to terminate
                break;
            }
            
            // Stop if no improvement for too long
            if ($noImprovementCount > $maxNoImprovement) {
                break;
            }
            
            // Selection and reproduction
            $newPopulation = [];
            
            // Elitism: Keep best chromosomes
            arsort($fitnessScores);
            $eliteIndices = array_slice(array_keys($fitnessScores), 0, $this->eliteSize);
            foreach ($eliteIndices as $index) {
                $newPopulation[] = $population[$index];
            }
            
            // Generate offspring
            while (count($newPopulation) < $this->populationSize) {
                $parent1 = $this->tournamentSelection($population, $fitnessScores);
                $parent2 = $this->tournamentSelection($population, $fitnessScores);
                
                if (mt_rand(0, 9999) < (int) round($this->crossoverRate * 10000)) {
                    $offspring = $this->crossover($parent1, $parent2);
                } else {
                    $offspring = [$parent1, $parent2];
                }
                
                foreach ($offspring as $child) {
                    $this->mutate($child, $teachers, $sectionMap);
                    $newPopulation[] = $child;
                }
            }
            
            $population = array_slice($newPopulation, 0, $this->populationSize);
        }
        
        return $bestChromosome ?: $population[0];
    }
    
    private function calculateFitness($chromosome, $teachers, $sectionMap) {
        $fitness = 0;
        $conflicts = 0;
        
        // Check for conflicts
        $teacherSchedule = [];
        $roomSchedule = [];
        $sectionSchedule = [];
        $teacherWorkload = [];
        $roomCapacities = []; // Cache room capacities
        
        foreach ($this->getRooms() as $room) {
            $roomCapacities[$room['id']] = $room['capacity'];
        }
        
        foreach ($chromosome as $assignment) {
            $key = $assignment['teacher_id'] . '_' . $assignment['day'] . '_' . $assignment['start_time'];
            
            // Teacher conflict
            if (isset($teacherSchedule[$key])) {
                $conflicts++;
            } else {
                $teacherSchedule[$key] = true;
            }
            
            // Room conflict
            $roomKey = $assignment['room_id'] . '_' . $assignment['day'] . '_' . $assignment['start_time'];
            if (isset($roomSchedule[$roomKey])) {
                $conflicts++;
            } else {
                $roomSchedule[$roomKey] = true;
            }
            
            // Section conflict
            $sectionKey = $assignment['section_id'] . '_' . $assignment['day'] . '_' . $assignment['start_time'];
            if (isset($sectionSchedule[$sectionKey])) {
                $conflicts++;
            } else {
                $sectionSchedule[$sectionKey] = true;
            }
            
            // Room capacity violation
            $sectionMax = $sectionMap[$assignment['section_id']] ?? 30;
            $roomCap = $roomCapacities[$assignment['room_id']] ?? 30;
            if ($roomCap < $sectionMax) {
                $conflicts += ($sectionMax - $roomCap) * 2; // Heavy penalty for capacity issues
            }
            
            // Track teacher workload
            $teacherId = $assignment['teacher_id'];
            if (!isset($teacherWorkload[$teacherId])) {
                $teacherWorkload[$teacherId] = ['total_hours' => 0, 'daily_hours' => []];
            }
            $teacherWorkload[$teacherId]['total_hours']++;
            $teacherWorkload[$teacherId]['daily_hours'][$assignment['day']] = 
                ($teacherWorkload[$teacherId]['daily_hours'][$assignment['day']] ?? 0) + 1;
        }
        
        // Build teacher map once for O(1) lookups
        static $teacherMap = null;
        if ($teacherMap === null) {
            $teacherMap = [];
            foreach ($teachers as $t) {
                $teacherMap[$t['id']] = $t;
            }
        }

        // Check workload constraints only for teachers present in this chromosome
        foreach ($teacherWorkload as $tId => $workload) {
            if (!isset($teacherMap[$tId])) {
                continue;
            }
            $teacher = $teacherMap[$tId];

            if ($teacher['employment_type'] == 'full_time') {
                // Monthly hours constraint (roughly 4 weeks)
                $maxWeeklyHours = $teacher['monthly_hours'] / 4;
                if ($workload['total_hours'] > $maxWeeklyHours) {
                    $conflicts += ($workload['total_hours'] - $maxWeeklyHours);
                }
            } else {
                // Daily hours constraint
                foreach ($workload['daily_hours'] as $day => $hours) {
                    if ($hours > $teacher['daily_hours']) {
                        $conflicts += ($hours - $teacher['daily_hours']);
                    }
                }
            }
        }
        
        // Calculate fitness (higher is better)
        $fitness = -$conflicts * 100; // Heavy penalty for conflicts
        
        // Bonus for good distribution
        $fitness += $this->calculateDistributionBonus($chromosome);
        
        return $fitness;
    }
    
    private function calculateDistributionBonus($chromosome) {
        $bonus = 0;
        
        // Check day distribution
        $dayCount = [];
        foreach ($chromosome as $assignment) {
            $dayCount[$assignment['day']] = ($dayCount[$assignment['day']] ?? 0) + 1;
        }
        
        // Prefer balanced distribution
        $avgClasses = count($chromosome) / count($this->days);
        foreach ($dayCount as $count) {
            $bonus += max(0, 10 - abs($count - $avgClasses));
        }
        
        return $bonus;
    }
    
    private function tournamentSelection($population, $fitnessScores, $tournamentSize = 3) {
        $tournament = [];
        $popSize = count($population);
        for ($i = 0; $i < $tournamentSize; $i++) {
            $randomIndex = mt_rand(0, $popSize - 1);
            $tournament[] = ['index' => $randomIndex, 'fitness' => $fitnessScores[$randomIndex]];
        }
        
        usort($tournament, function($a, $b) {
            return $b['fitness'] - $a['fitness'];
        });
        
        return $population[$tournament[0]['index']];
    }
    
    private function crossover($parent1, $parent2) {
        $size = count($parent1);
        $crossoverPoint = mt_rand(0, $size - 1);
        
        $child1 = array_merge(
            array_slice($parent1, 0, $crossoverPoint),
            array_slice($parent2, $crossoverPoint)
        );
        
        $child2 = array_merge(
            array_slice($parent2, 0, $crossoverPoint),
            array_slice($parent1, $crossoverPoint)
        );
        
        return [$child1, $child2];
    }
    
    private function mutate(&$chromosome, $teachers, $sectionMap) {
        // Use cached rooms and mt_rand for faster RNG
        $rooms = $this->getRooms();
        $roomsCount = count($rooms);
        $daysCount = count($this->days);
        $timeSlotsCount = count($this->timeSlots);
        $mutationThreshold = (int) round($this->mutationRate * 10000);

        // Pre-filter valid rooms by capacity
        $validRoomsBySection = [];
        foreach ($chromosome as $assignment) {
            $secId = $assignment['section_id'];
            $secMax = $sectionMap[$secId] ?? 30;
            $valid = [];
            foreach ($rooms as $room) {
                if ($room['capacity'] >= $secMax) {
                    $valid[] = $room;
                }
            }
            $validRoomsBySection[$secId] = $valid;
        }

        foreach ($chromosome as &$assignment) {
            if (mt_rand(0, 9999) < $mutationThreshold) {
                // Mutate day (low probability to preserve structure)
                if (mt_rand(0, 99) < 20) {
                    $assignment['day'] = $this->days[mt_rand(0, $daysCount - 1)];
                }

                // Mutate time slot
                if (mt_rand(0, 99) < 30) {
                    $ts = $this->timeSlots[mt_rand(0, $timeSlotsCount - 1)];
                    $assignment['start_time'] = $ts[0];
                    $assignment['end_time'] = $ts[1];
                }

                // Mutate room to valid capacity
                if (mt_rand(0, 99) < 25) {
                    $secId = $assignment['section_id'];
                    $valid = $validRoomsBySection[$secId];
                    if (!empty($valid)) {
                        $assignment['room_id'] = $valid[mt_rand(0, count($valid) - 1)]['id'];
                    }
                }

                // Occasionally mutate teacher (10% chance, to explore better fits)
                if (mt_rand(0, 99) < 10) {
                    // Re-fetch available teachers for this subject
                    $teacherSubjects = $this->getTeacherSubjects();
                    $availTeachers = array_filter($teacherSubjects, function($ts) use ($assignment) {
                        return $ts['subject_id'] == $assignment['subject_id'];
                    });
                    if (!empty($availTeachers)) {
                        $assignment['teacher_id'] = $availTeachers[array_rand($availTeachers)]['teacher_id'];
                    }
                }
            }
        }
    }
    
    private function saveSchedule($schedule) {
        $this->mysqli->begin_transaction();

        $stmt = $this->mysqli->prepare("
            INSERT INTO schedules (teacher_id, subject_id, section_id, room_id, day_of_week, start_time, end_time, semester, academic_year) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $semester = '1st Semester';
        $academicYear = date('Y') . '-' . (date('Y') + 1); // Dynamic year
        
        foreach ($schedule as $assignment) {
            $dayOfWeek = $assignment['day']; // Map 'day' to 'day_of_week'
            $stmt->bind_param("iiiisssss", 
                $assignment['teacher_id'],
                $assignment['subject_id'],
                $assignment['section_id'],
                $assignment['room_id'],
                $dayOfWeek,
                $assignment['start_time'],
                $assignment['end_time'],
                $semester,
                $academicYear
            );
            if (!$stmt->execute()) {
                $this->mysqli->rollback();
                throw new Exception("Failed to save assignment: " . $stmt->error);
            }
        }
        
        $stmt->close();

        $this->mysqli->commit();
    }
    
    private function checkConflicts($schedule) {
        $conflicts = 0;
        
        // Check for remaining conflicts and log them
        $teacherSchedule = [];
        $roomSchedule = [];
        $sectionSchedule = [];
        $roomCapacities = [];
        
        foreach ($this->getRooms() as $room) {
            $roomCapacities[$room['id']] = $room['capacity'];
        }

        // Prepare once for logging to minimize prepare overhead
        $stmt = $this->mysqli->prepare("
            INSERT INTO schedule_conflicts (conflict_type, entity_id, day_of_week, start_time, end_time, description) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($schedule as $assignment) {
            $teacherKey = $assignment['teacher_id'] . '_' . $assignment['day'] . '_' . $assignment['start_time'];
            $roomKey = $assignment['room_id'] . '_' . $assignment['day'] . '_' . $assignment['start_time'];
            $sectionKey = $assignment['section_id'] . '_' . $assignment['day'] . '_' . $assignment['start_time'];
            
            if (isset($teacherSchedule[$teacherKey])) {
                $type = 'teacher';
                $entityId = $assignment['teacher_id'];
                $day = $assignment['day'];
                $start = $assignment['start_time'];
                $end = $assignment['end_time'];
                $desc = 'Teacher double booking';
                $stmt->bind_param("sissss", $type, $entityId, $day, $start, $end, $desc);
                $stmt->execute();
                $conflicts++;
            } else {
                $teacherSchedule[$teacherKey] = true;
            }
            
            if (isset($roomSchedule[$roomKey])) {
                $type = 'room';
                $entityId = $assignment['room_id'];
                $day = $assignment['day'];
                $start = $assignment['start_time'];
                $end = $assignment['end_time'];
                $desc = 'Room double booking';
                $stmt->bind_param("sissss", $type, $entityId, $day, $start, $end, $desc);
                $stmt->execute();
                $conflicts++;
            } else {
                $roomSchedule[$roomKey] = true;
            }
            
            if (isset($sectionSchedule[$sectionKey])) {
                $type = 'section';
                $entityId = $assignment['section_id'];
                $day = $assignment['day'];
                $start = $assignment['start_time'];
                $end = $assignment['end_time'];
                $desc = 'Section double booking';
                $stmt->bind_param("sissss", $type, $entityId, $day, $start, $end, $desc);
                $stmt->execute();
                $conflicts++;
            } else {
                $sectionSchedule[$sectionKey] = true;
            }

            // Log capacity conflicts
            $sectionMax = $sectionMap[$assignment['section_id']] ?? 30;
            $roomCap = $roomCapacities[$assignment['room_id']] ?? 30;
            if ($roomCap < $sectionMax) {
                $type = 'capacity';
                $entityId = $assignment['room_id'];
                $day = $assignment['day'];
                $start = $assignment['start_time'];
                $end = $assignment['end_time'];
                $desc = "Room capacity insufficient for section (room: $roomCap, section: $sectionMax)";
                $stmt->bind_param("sissss", $type, $entityId, $day, $start, $end, $desc);
                $stmt->execute();
                $conflicts++;
            }
        }

        $stmt->close();
        
        return $conflicts;
    }
    
    private function logConflict($type, $entityId, $day, $startTime, $endTime, $description) {
        $stmt = $this->mysqli->prepare("
            INSERT INTO schedule_conflicts (conflict_type, entity_id, day_of_week, start_time, end_time, description) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sissss", $type, $entityId, $day, $startTime, $endTime, $description);
        $stmt->execute();
        $stmt->close();
    }
    
    public function regenerateTeacherSchedule($teacherId) {
        try {
            $teacherId = (int) $teacherId;

            // Get current schedule for other teachers (prepared for safety)
            $stmt = $this->mysqli->prepare("SELECT * FROM schedules WHERE teacher_id != ?");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $otherSchedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Index other schedules by day and start_time for fast lookups
            $otherIndex = [];
            foreach ($otherSchedules as $other) {
                $day = $other['day_of_week'];
                $time = $other['start_time'];
                if (!isset($otherIndex[$day][$time])) {
                    $otherIndex[$day][$time] = [];
                }
                $otherIndex[$day][$time][] = $other;
            }
            
            // Get teacher's current assignments (prepared)
            $stmt = $this->mysqli->prepare("
                SELECT s.*, sub.name as subject_name, sec.name as section_name
                FROM schedules s
                LEFT JOIN subjects sub ON s.subject_id = sub.id
                LEFT JOIN sections sec ON s.section_id = sec.id
                WHERE s.teacher_id = ?
            ");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $teacherAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            if (empty($teacherAssignments)) {
                return ['success' => false, 'message' => 'No assignments found for this teacher'];
            }
            
            // Remove teacher's current schedule
            $stmt = $this->mysqli->prepare("DELETE FROM schedules WHERE teacher_id = ?");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $stmt->close();
            
            // Generate new schedule for this teacher only
            $rooms = $this->getRooms();
            $sectionMap = [];
            foreach ($this->getSections() as $sec) {
                $sectionMap[$sec['id']] = $sec['max_students'];
            }
            
            $newSchedule = [];
            foreach ($teacherAssignments as $assignment) {
                // Try to find a conflict-free slot
                $bestSlot = $this->findBestTimeSlot($assignment, $otherIndex, $sectionMap);
                if ($bestSlot) {
                    $newSchedule[] = $bestSlot;
                } else {
                    // If no conflict-free slot, use original with conflict logged
                    $original = $assignment;
                    $original['day'] = $original['day_of_week'];
                    unset($original['day_of_week']);
                    $newSchedule[] = $original;
                    $this->logConflict('teacher', $teacherId, $assignment['day_of_week'], $assignment['start_time'], $assignment['end_time'], 'No conflict-free slot available');
                }
            }
            
            // Save new schedule
            $this->saveSchedule($newSchedule);
            
            return ['success' => true, 'message' => 'Teacher schedule regenerated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function findBestTimeSlot($assignment, $otherIndex, $sectionMap) {
        $bestSlot = null;
        $minConflicts = 999999;
        $sectionMax = $sectionMap[$assignment['section_id']] ?? 30;
        $validRooms = [];
        foreach ($this->getRooms() as $room) {
            if ($room['capacity'] >= $sectionMax) {
                $validRooms[] = $room;
            }
        }
        
        foreach ($this->days as $day) {
            foreach ($this->timeSlots as $timeSlot) {
                $slotOthers = $otherIndex[$day][$timeSlot[0]] ?? [];
                $conflicts = 0;
                
                // Check conflicts only with assignments in this exact slot
                foreach ($slotOthers as $other) {
                    if ($other['room_id'] == $assignment['room_id'] || 
                        $other['section_id'] == $assignment['section_id']) {
                        $conflicts++;
                    }
                }
                
                if ($conflicts < $minConflicts) {
                    $minConflicts = $conflicts;
                    $bestSlot = [
                        'teacher_id' => $assignment['teacher_id'],
                        'subject_id' => $assignment['subject_id'],
                        'section_id' => $assignment['section_id'],
                        'room_id' => $assignment['room_id'],
                        'day' => $day,
                        'start_time' => $timeSlot[0],
                        'end_time' => $timeSlot[1],
                        'section_max_students' => $sectionMax
                    ];
                }
            }
        }
        
        return $bestSlot;
    }
}
?>
