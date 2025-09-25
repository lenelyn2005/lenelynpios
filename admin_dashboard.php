<?php
// admin_dashboard.php - Admin Dashboard Overview
session_start();
require 'config.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_department'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $success = "Department added successfully!";
            } else {
                $error = "Error adding department.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['add_course'])) {
        $name = trim($_POST['name']);
        $department_id = $_POST['department_id'];
        if (!empty($name) && !empty($department_id)) {
            $stmt = $mysqli->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $department_id);
            if ($stmt->execute()) {
                $success = "Course added successfully!";
            } else {
                $error = "Error adding course.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['add_section'])) {
        $name = trim($_POST['name']);
        $year_level_id = $_POST['year_level_id'];
        $course_id = $_POST['course_id'];
        $max_students = $_POST['max_students'];
        if (!empty($name) && !empty($year_level_id) && !empty($course_id)) {
            $stmt = $mysqli->prepare("INSERT INTO sections (name, year_level_id, course_id, max_students) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siii", $name, $year_level_id, $course_id, $max_students);
            if ($stmt->execute()) {
                $success = "Section added successfully!";
            } else {
                $error = "Error adding section.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['add_subject'])) {
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $department_id = $_POST['department_id'];
        $course_id = $_POST['course_id'];
        $year_level_id = $_POST['year_level_id'];
        $units = $_POST['units'];
        $hours_per_week = $_POST['hours_per_week'];
        if (!empty($name) && !empty($code) && !empty($department_id) && !empty($course_id) && !empty($year_level_id)) {
            $stmt = $mysqli->prepare("INSERT INTO subjects (name, code, department_id, course_id, year_level_id, units, hours_per_week) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiii", $name, $code, $department_id, $course_id, $year_level_id, $units, $hours_per_week);
            if ($stmt->execute()) {
                $success = "Subject added successfully!";
            } else {
                $error = "Error adding subject.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['add_teacher'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $department_id = $_POST['department_id'];
        $employment_type = $_POST['employment_type'];
        $monthly_hours = $_POST['monthly_hours'];
        $daily_hours = $_POST['daily_hours'];

        if (!empty($username) && !empty($password) && !empty($first_name) && !empty($last_name) && !empty($department_id)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO teachers (username, password, first_name, last_name, email, department_id, employment_type, monthly_hours, daily_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisii", $username, $hashed_password, $first_name, $last_name, $email, $department_id, $employment_type, $monthly_hours, $daily_hours);
            if ($stmt->execute()) {
                $success = "Teacher added successfully!";
            } else {
                $error = "Error adding teacher.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['add_room'])) {
        $name = trim($_POST['name']);
        $capacity = $_POST['capacity'];
        $room_type = $_POST['room_type'];
        $location = trim($_POST['location']);
        if (!empty($name) && !empty($capacity)) {
            $stmt = $mysqli->prepare("INSERT INTO rooms (name, capacity, room_type, location) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $name, $capacity, $room_type, $location);
            if ($stmt->execute()) {
                $success = "Room added successfully!";
            } else {
                $error = "Error adding room.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['add_student'])) {
        $id = trim($_POST['id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $section_id = $_POST['section_id'];
        $year_level_id = $_POST['year_level_id'];
        $course_id = $_POST['course_id'];
        if (!empty($id) && !empty($first_name) && !empty($last_name) && !empty($section_id)) {
            $stmt = $mysqli->prepare("INSERT INTO students (id, first_name, last_name, email, section_id, year_level_id, course_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $id, $first_name, $last_name, $email, $section_id, $year_level_id, $course_id);
            if ($stmt->execute()) {
                $success = "Student added successfully!";
            } else {
                $error = "Error adding student.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['assign_teacher_subject'])) {
        $teacher_id = $_POST['teacher_id'];
        $subject_id = $_POST['subject_id'];
        if (!empty($teacher_id) && !empty($subject_id)) {
            $stmt = $mysqli->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $teacher_id, $subject_id);
            if ($stmt->execute()) {
                $success = "Teacher assigned to subject successfully!";
            } else {
                $error = "Error assigning teacher to subject.";
            }
            $stmt->close();
        }
    }

    elseif (isset($_POST['generate_schedule'])) {
        // Include the GA scheduler
        require_once 'ga_scheduler.php';
        $scheduler = new GAScheduler($mysqli);
        $result = $scheduler->generateSchedule();
        if ($result['success']) {
            $success = "Schedule generated successfully! " . $result['message'];
        } else {
            $error = "Error generating schedule: " . $result['message'];
        }
    }

    // Teacher Assignment Logic from admin_teacher_assignments.php
    if (isset($_POST['assign_teacher'])) {
        $teacher_id = $_POST['teacher_id'];
        $subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : [];

        if (!empty($teacher_id) && !empty($subject_ids)) {
            $success_count = 0;
            $error_count = 0;

            foreach ($subject_ids as $subject_id) {
                $check_stmt = $mysqli->prepare("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
                $check_stmt->bind_param("ii", $teacher_id, $subject_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows == 0) {
                    $stmt = $mysqli->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $teacher_id, $subject_id);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    $stmt->close();
                } else {
                    $error_count++;
                }
                $check_stmt->close();
            }

            if ($success_count > 0) {
                $success = "Successfully assigned $success_count subject(s) to teacher!";
            }
            if ($error_count > 0) {
                $error = "$error_count assignment(s) failed (already exist or other error).";
            }
        } else {
            $error = "Please select a teacher and at least one subject.";
        }
    }

    elseif (isset($_POST['unassign_teacher'])) {
        $teacher_id = $_POST['teacher_id'];
        $subject_id = $_POST['subject_id'];

        if (!empty($teacher_id) && !empty($subject_id)) {
            $stmt = $mysqli->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
            $stmt->bind_param("ii", $teacher_id, $subject_id);
            if ($stmt->execute()) {
                $success = "Successfully unassigned teacher from subject!";
            } else {
                $error = "Error unassigning teacher: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Invalid teacher or subject ID.";
        }
    }

    elseif (isset($_POST['bulk_assign'])) {
        $teacher_id = $_POST['teacher_id'];
        $department_id = $_POST['department_id'];
        $course_id = $_POST['course_id'];

        if (!empty($teacher_id) && (!empty($department_id) || !empty($course_id))) {
            $query = "SELECT id FROM subjects WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($department_id)) {
                $query .= " AND department_id = ?";
                $params[] = $department_id;
                $types .= "i";
            }

            if (!empty($course_id)) {
                $query .= " AND course_id = ?";
                $params[] = $course_id;
                $types .= "i";
            }

            $stmt = $mysqli->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $success_count = 0;
            $error_count = 0;

            while ($subject = $result->fetch_assoc()) {
                $check_stmt = $mysqli->prepare("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
                $check_stmt->bind_param("ii", $teacher_id, $subject['id']);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows == 0) {
                    $assign_stmt = $mysqli->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                    $assign_stmt->bind_param("ii", $teacher_id, $subject['id']);
                    if ($assign_stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    $assign_stmt->close();
                } else {
                    $error_count++;
                }
                $check_stmt->close();
            }
            $stmt->close();

            if ($success_count > 0) {
                $success = "Successfully assigned $success_count subject(s) to teacher!";
            }
            if ($error_count > 0) {
                $error = "$error_count assignment(s) failed (already exist or other error).";
            }
        } else {
            $error = "Please select a teacher and at least one filter (department or course).";
        }
    }
}

// Fetch data for dropdowns
$departments = $mysqli->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$courses = $mysqli->query("SELECT c.*, d.name as department_name FROM courses c LEFT JOIN departments d ON c.department_id = d.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$year_levels = $mysqli->query("SELECT * FROM year_levels ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$sections = $mysqli->query("SELECT s.*, yl.name as year_level_name, c.name as course_name FROM sections s LEFT JOIN year_levels yl ON s.year_level_id = yl.id LEFT JOIN courses c ON s.course_id = c.id ORDER BY s.name")->fetch_all(MYSQLI_ASSOC);
$subjects = $mysqli->query("SELECT s.*, d.name as department_name, c.name as course_name, yl.name as year_level_name FROM subjects s LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN courses c ON s.course_id = c.id LEFT JOIN year_levels yl ON s.year_level_id = yl.id ORDER BY s.name")->fetch_all(MYSQLI_ASSOC);
$teachers = $mysqli->query("SELECT t.*, d.name as department_name FROM teachers t LEFT JOIN departments d ON t.department_id = d.id ORDER BY t.last_name, t.first_name")->fetch_all(MYSQLI_ASSOC);
$rooms = $mysqli->query("SELECT * FROM rooms ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$students = $mysqli->query("SELECT s.*, sec.name as section_name, yl.name as year_level_name, c.name as course_name FROM students s LEFT JOIN sections sec ON s.section_id = sec.id LEFT JOIN year_levels yl ON s.year_level_id = yl.id LEFT JOIN courses c ON s.course_id = c.id ORDER BY s.id")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'departments' => count($departments),
    'courses' => count($courses),
    'sections' => count($sections),
    'subjects' => count($subjects),
    'teachers' => count($teachers),
    'rooms' => count($rooms),
    'students' => count($students)
];

// Additional queries for assignments
$teacher_subjects = $mysqli->query("SELECT ts.*, t.first_name, t.last_name, t.username, s.name as subject_name, s.code as subject_code, d.name as department_name, c.name as course_name FROM teacher_subjects ts LEFT JOIN teachers t ON ts.teacher_id = t.id LEFT JOIN subjects s ON ts.subject_id = s.id LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN courses c ON s.course_id = c.id ORDER BY t.last_name, t.first_name, s.name")->fetch_all(MYSQLI_ASSOC);

$teachers_with_assignments = $mysqli->query("SELECT DISTINCT teacher_id FROM teacher_subjects")->num_rows;
$subjects_assigned = $mysqli->query("SELECT DISTINCT subject_id FROM teacher_subjects")->num_rows;
$total_assignments = count($teacher_subjects);

$stats['total_assignments'] = $total_assignments;
$stats['teachers_with_assignments'] = $teachers_with_assignments;
$stats['subjects_assigned'] = $subjects_assigned;
$stats['total_teachers'] = count($teachers);
$stats['total_subjects'] = count($subjects);

// Add subject_count to teachers
foreach ($teachers as &$teacher) {
    $count_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM teacher_subjects WHERE teacher_id = ?");
    $count_stmt->bind_param("i", $teacher['id']);
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    $count = $result->fetch_assoc();
    $teacher['subject_count'] = $count['count'];
    $count_stmt->close();
}

// Current assignments
$current_assignments = $teacher_subjects;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Scheduling System</title>
    <!-- For offline use: Download Font Awesome CSS and icons from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- For now, keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> <!-- Assume this is local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume this is local -->
</head>
<body>
    <?php renderSidebar('admin', 'dashboard'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Admin Dashboard</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Overview</span>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success fade-in" role="alert">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error fade-in" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <section class="stats-grid" aria-label="Dashboard statistics">
                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['departments']; ?></h3>
                        <p>Departments</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['courses']; ?></h3>
                        <p>Courses</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['sections']; ?></h3>
                        <p>Sections</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['subjects']; ?></h3>
                        <p>Subjects</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['teachers']; ?></h3>
                        <p>Teachers</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-door-open" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['rooms']; ?></h3>
                        <p>Rooms</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['students']; ?></h3>
                        <p>Students</p>
                    </div>
                </article>
            </section>

            <div class="dashboard-grid">
                <!-- Quick Actions Card -->
                <article class="dashboard-card quick-actions-card">
                    <header class="card-header">
                        <i class="fas fa-bolt" aria-hidden="true"></i>
                        <h2>Quick Actions</h2>
                    </header>
                    <div class="card-content">
                        <nav class="quick-actions" aria-label="Quick navigation">
                            <a href="admin_departments.php" class="action-btn" aria-label="Manage Departments">
                                <i class="fas fa-building" aria-hidden="true"></i>
                                <span>Departments</span>
                            </a>
                            <a href="admin_courses.php" class="action-btn" aria-label="Manage Courses">
                                <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                                <span>Courses</span>
                            </a>
                            <a href="admin_teachers.php" class="action-btn" aria-label="Manage Teachers">
                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                <span>Teachers</span>
                            </a>
                            <a href="admin_students.php" class="action-btn" aria-label="Manage Students">
                                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                                <span>Students</span>
                            </a>
                            <a href="admin_rooms.php" class="action-btn" aria-label="Manage Rooms">
                                <i class="fas fa-door-open" aria-hidden="true"></i>
                                <span>Rooms</span>
                            </a>
                            <a href="admin_schedules.php" class="action-btn" aria-label="View Schedules">
                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                <span>Schedules</span>
                            </a>
                        </nav>
                    </div>
                </article>

                <!-- Schedule Generation Card -->
                <article class="dashboard-card schedule-generation-card">
                    <header class="card-header">
                        <i class="fas fa-magic" aria-hidden="true"></i>
                        <h2>Generate Schedule</h2>
                    </header>
                    <div class="card-content">
                        <p>Automatically generate conflict-free schedules using the Genetic Algorithm for optimal teacher, room, and section assignments.</p>
                        <form method="POST" class="form-actions" aria-label="Generate schedule form">
                            <button type="submit" name="generate_schedule" class="btn btn-primary full-width">
                                <i class="fas fa-magic" aria-hidden="true"></i> Generate Schedule
                            </button>
                        </form>
                    </div>
                </article>
            </div>

            <!-- Assignment Statistics -->
            <section class="stats-grid assignment-stats" aria-label="Assignment statistics">
                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-link" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_assignments']; ?></h3>
                        <p>Total Assignments</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['teachers_with_assignments']; ?>/<?php echo $stats['total_teachers']; ?></h3>
                        <p>Teachers Assigned</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['subjects_assigned']; ?>/<?php echo $stats['total_subjects']; ?></h3>
                        <p>Subjects Assigned</p>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line" aria-hidden="true"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_teachers'] > 0 ? round(($stats['teachers_with_assignments'] / $stats['total_teachers']) * 100, 1) : 0; ?>%</h3>
                        <p>Utilization Rate</p>
                    </div>
                </article>
            </section>

            <div class="dashboard-grid assignment-grid">
                <!-- Individual Assignment Form -->
                <article class="dashboard-card assignment-form-card">
                    <header class="card-header">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        <h2>Assign Teacher to Subjects</h2>
                    </header>
                    <div class="card-content">
                        <form method="POST" class="form-grid" novalidate aria-label="Assign teacher form">
                            <div class="form-group">
                                <label for="teacher_id">Select Teacher <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <select id="teacher_id" name="teacher_id" required aria-required="true" aria-describedby="teacher-help">
                                    <option value="">Choose a teacher...</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                            (<?php echo htmlspecialchars($teacher['department_name'] ?? 'N/A'); ?>)
                                            - <?php echo $teacher['subject_count']; ?> subjects
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="teacher-help" class="sr-only">Select the teacher to assign subjects to.</small>
                            </div>

                            <div class="form-group full-width">
                                <label for="subjects-list">Select Subjects <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <div class="subjects-container" id="subjects-list" role="listbox" aria-label="Available subjects">
                                    <?php foreach ($subjects as $subject): ?>
                                        <div class="subject-item" role="option">
                                            <input type="checkbox" name="subject_ids[]" value="<?php echo $subject['id']; ?>" class="assignment-checkbox" id="subject_<?php echo $subject['id']; ?>">
                                            <label for="subject_<?php echo $subject['id']; ?>">
                                                <div class="subject-info">
                                                    <strong><?php echo htmlspecialchars($subject['name']); ?></strong> (<?php echo htmlspecialchars($subject['code']); ?>)
                                                    <br><small><?php echo htmlspecialchars($subject['course_name'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($subject['department_name'] ?? 'N/A'); ?></small>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="sr-only">Check the subjects to assign to the selected teacher.</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="assign_teacher" class="btn btn-primary full-width">
                                    <i class="fas fa-plus" aria-hidden="true"></i> Assign Selected Subjects
                                </button>
                            </div>
                        </form>
                    </div>
                </article>

                <!-- Bulk Assignment Form -->
                <article class="dashboard-card bulk-assignment-card">
                    <header class="card-header">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        <h2>Bulk Assignment</h2>
                    </header>
                    <div class="card-content">
                        <form method="POST" class="form-grid" novalidate aria-label="Bulk assign form">
                            <div class="form-group">
                                <label for="bulk_teacher_id">Select Teacher <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <select id="bulk_teacher_id" name="teacher_id" required aria-required="true" aria-describedby="bulk-teacher-help">
                                    <option value="">Choose a teacher...</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                            (<?php echo htmlspecialchars($teacher['department_name'] ?? 'N/A'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="bulk-teacher-help" class="sr-only">Select the teacher for bulk assignment.</small>
                            </div>

                            <div class="form-group">
                                <label for="bulk_department_id">Filter by Department</label>
                                <select id="bulk_department_id" name="department_id" aria-describedby="bulk-dept-help">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="bulk-dept-help" class="sr-only">Optional filter by department.</small>
                            </div>

                            <div class="form-group">
                                <label for="bulk_course_id">Filter by Course</label>
                                <select id="bulk_course_id" name="course_id" aria-describedby="bulk-course-help">
                                    <option value="">All Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="bulk-course-help" class="sr-only">Optional filter by course.</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="bulk_assign" class="btn btn-primary full-width">
                                    <i class="fas fa-layer-group" aria-hidden="true"></i> Assign Matching Subjects
                                </button>
                            </div>
                        </form>
                    </div>
                </article>
            </div>

            <!-- Current Assignments List -->
            <article class="dashboard-card assignments-list-card">
                <header class="card-header">
                    <i class="fas fa-list-ul" aria-hidden="true"></i>
                    <h2>Current Assignments (<?php echo count($current_assignments); ?>)</h2>
                </header>
                <div class="card-content">
                    <?php if (empty($current_assignments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-link" aria-hidden="true"></i>
                            <h3>No Assignments</h3>
                            <p>Create your first teacher-subject assignment using the forms above.</p>
                            <button class="btn btn-primary" onclick="document.querySelector('.assignment-form-card').scrollIntoView({behavior: 'smooth'})">Assign Now</button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table" role="table" aria-label="Current teacher assignments">
                                <thead>
                                    <tr>
                                        <th scope="col">Teacher</th>
                                        <th scope="col">Subject</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Course</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_assignments as $assignment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></strong>
                                                <br><small class="text-muted">@<?php echo htmlspecialchars($assignment['username']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary" aria-label="Subject code: <?php echo htmlspecialchars($assignment['subject_code']); ?>">
                                                    <?php echo htmlspecialchars($assignment['subject_code']); ?>
                                                </span>
                                                <strong><?php echo htmlspecialchars($assignment['subject_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info" aria-label="Department: <?php echo htmlspecialchars($assignment['department_name']); ?>">
                                                    <?php echo htmlspecialchars($assignment['department_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary" aria-label="Course: <?php echo htmlspecialchars($assignment['course_name']); ?>">
                                                    <?php echo htmlspecialchars($assignment['course_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-danger" onclick="deleteAssignment(<?php echo $assignment['teacher_id']; ?>, <?php echo $assignment['subject_id']; ?>, '<?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>', '<?php echo htmlspecialchars($assignment['subject_name']); ?>')" aria-label="Unassign <?php echo htmlspecialchars($assignment['subject_name']); ?> from <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>">
                                                        <i class="fas fa-unlink" aria-hidden="true"></i> Unassign
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <!-- Unassign Confirmation Modal -->
            <dialog id="deleteModal" class="modal" aria-modal="true" aria-labelledby="deleteModalTitle">
                <div class="modal-content">
                    <header class="modal-header">
                        <h3 id="deleteModalTitle">Confirm Unassign</h3>
                        <button class="close" aria-label="Close modal" role="button">&times;</button>
                    </header>
                    <div class="modal-body">
                        <p>Are you sure you want to unassign <strong id="delete_teacher_name"></strong> from <strong id="delete_subject_name"></strong>?</p>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><strong>Warning:</strong> This action cannot be undone and may affect schedules.</p>
                    </div>
                    <footer class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" id="delete_teacher_id" name="teacher_id">
                            <input type="hidden" id="delete_subject_id" name="subject_id">
                            <button type="submit" name="unassign_teacher" class="btn btn-danger">Unassign</button>
                        </form>
                    </footer>
                </div>
            </dialog>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <script>
        function deleteAssignment(teacherId, subjectId, teacherName, subjectName) {
            document.getElementById('delete_teacher_id').value = teacherId;
            document.getElementById('delete_subject_id').value = subjectId;
            document.getElementById('delete_teacher_name').textContent = teacherName;
            document.getElementById('delete_subject_name').textContent = subjectName;
            const modal = document.getElementById('deleteModal');
            modal.showModal();
            modal.classList.add('fade-in');
        }

        function closeModal() {
            const modal = document.getElementById('deleteModal');
            if (modal.open) {
                modal.classList.remove('fade-in');
                modal.close();
            }
        }

        // Close on overlay click
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Close button
        document.querySelector('.close').addEventListener('click', closeModal);

        // Esc key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const assignForm = document.querySelector('form[method="POST"]');
            if (assignForm) {
                assignForm.addEventListener('submit', function(e) {
                    const teacherSelect = document.getElementById('teacher_id');
                    const checkboxes = document.querySelectorAll('input[name="subject_ids[]"]:checked');
                    
                    if (!teacherSelect.value) {
                        e.preventDefault();
                        alert('Please select a teacher.');
                        teacherSelect.focus();
                        return;
                    }
                    
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one subject.');
                        return;
                    }
                });
            }

            const bulkForm = document.querySelector('form[method="POST"]');
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    const teacherSelect = document.getElementById('bulk_teacher_id');
                    const departmentSelect = document.getElementById('bulk_department_id');
                    const courseSelect = document.getElementById('bulk_course_id');
                    
                    if (!teacherSelect.value) {
                        e.preventDefault();
                        alert('Please select a teacher.');
                        teacherSelect.focus();
                        return;
                    }
                    
                    if (!departmentSelect.value && !courseSelect.value) {
                        e.preventDefault();
                        alert('Please select at least one filter (department or course).');
                        return;
                    }
                });
            }

            // Subject checkboxes styling
            const subjectCheckboxes = document.querySelectorAll('.assignment-checkbox');
            subjectCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const subjectItem = this.closest('.subject-item');
                    if (this.checked) {
                        subjectItem.classList.add('selected');
                    } else {
                        subjectItem.classList.remove('selected');
                    }
                });
            });
        });
    </script>
    
</body>
</html>
