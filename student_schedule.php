<?php
// student_schedule.php - Student Schedule Viewer
session_start();
require 'config.php';
require_once 'includes/sidebar_new.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login_student.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$success = '';
$error = '';

// Get student information (prepared to prevent injection)
$stmt = $mysqli->prepare("
    SELECT s.*, sec.name as section_name, yl.name as year_level_name, c.name as course_name
    FROM students s
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN year_levels yl ON s.year_level_id = yl.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
");
if (!$stmt) {
    $error = "Database prepare error: " . $mysqli->error;
    $student = [];
} else {
    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        $error = "Database execute error: " . $stmt->error;
        $student = [];
    } else {
        $result = $stmt->get_result();
        $student = $result->fetch_assoc() ?: [];
    }
    $stmt->close();
}

if (!$student) {
    header("Location: login_student.php");
    exit;
}

// Get student's schedule (prepared)
$stmt = $mysqli->prepare("
    SELECT s.*, sub.name as subject_name, sub.code as subject_code,
           t.first_name, t.last_name, r.name as room_name, r.location
    FROM schedules s
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE s.section_id = ?
    ORDER BY
        CASE s.day_of_week
            WHEN 'Monday' THEN 1
            WHEN 'Tuesday' THEN 2
            WHEN 'Wednesday' THEN 3
            WHEN 'Thursday' THEN 4
            WHEN 'Friday' THEN 5
            WHEN 'Saturday' THEN 6
            WHEN 'Sunday' THEN 7
        END,
        s.start_time
");
if (!$stmt) {
    $error = "Database prepare error for schedule: " . $mysqli->error;
    $schedule = [];
} else {
    $section_id = $student['section_id'] ?? 0;
    $stmt->bind_param("i", $section_id);
    if (!$stmt->execute()) {
        $error = "Database execute error for schedule: " . $stmt->error;
        $schedule = [];
    } else {
        $result = $stmt->get_result();
        $schedule = $result->fetch_all(MYSQLI_ASSOC) ?: [];
    }
    $stmt->close();
}

// Get schedule statistics (prepared)
$stats_result = $mysqli->prepare("
    SELECT
        COUNT(*) as total_classes,
        COUNT(DISTINCT s.day_of_week) as active_days,
        SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time)) as total_minutes
    FROM schedules s
    WHERE s.section_id = ?
");
if (!$stats_result) {
    $error .= " Database prepare error for stats: " . $mysqli->error;
    $scheduleStats = ['total_classes' => 0, 'active_days' => 0, 'total_hours' => 0];
} else {
    $section_id = $student['section_id'] ?? 0;
    $stats_result->bind_param("i", $section_id);
    if (!$stats_result->execute()) {
        $error .= " Database execute error for stats: " . $stats_result->error;
        $scheduleStats = ['total_classes' => 0, 'active_days' => 0, 'total_hours' => 0];
    } else {
        $result = $stats_result->get_result();
        $stats_data = $result->fetch_assoc() ?: [];
        $scheduleStats = [
            'total_classes' => $stats_data['total_classes'] ?? 0,
            'active_days' => $stats_data['active_days'] ?? 0,
            'total_hours' => ($stats_data['total_minutes'] ?? 0) / 60
        ];
    }
    $stats_result->close();
}

// Organize schedule by day
$scheduleByDay = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days as $day) {
    $scheduleByDay[$day] = [];
}

foreach ($schedule as $class) {
    $scheduleByDay[$class['day_of_week']][] = $class;
}

// Get current week dates
$today = date('Y-m-d');
$startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($today)));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Student Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php renderSidebar('student', 'schedule'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">My Schedule</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="student_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>My Schedule</span>
                </div>
            </div>
        </div>

        <div class="content-area">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        
        <!-- Weekly Schedule -->
        <div class="dashboard-card">
            <div class="card-header">
                <i class="fas fa-calendar-week"></i>
                <h3>Weekly Schedule</h3>
            </div>
            <div class="schedule-container">
                <div class="schedule-grid">
                    <?php foreach ($days as $day): ?>
                        <div class="day-column">
                            <div class="day-header">
                                <h4><?php echo $day; ?></h4>
                                <span class="class-count"><?php echo count($scheduleByDay[$day]); ?> classes</span>
                            </div>
                            <div class="day-classes">
                                <?php if (empty($scheduleByDay[$day])): ?>
                                    <div class="no-classes">
                                        <i class="fas fa-coffee"></i>
                                        <span>No classes</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($scheduleByDay[$day] as $class): ?>
                                        <div class="class-item">
                                            <div class="class-time">
                                                <?php echo date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time'])); ?>
                                            </div>
                                            <div class="class-details">
                                                <div class="subject-code"><?php echo htmlspecialchars($class['subject_code']); ?></div>
                                                <div class="subject-name"><?php echo htmlspecialchars($class['subject_name']); ?></div>
                                                <div class="class-info">
                                                    <span><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?></span>
                                                    <span><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room_name']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Today's Schedule -->
        <?php
        $todaySchedule = $scheduleByDay[date('l')] ?? [];
        ?>
        <div class="dashboard-card">
            <div class="card-header">
                <i class="fas fa-calendar-day"></i>
                <h3>Today's Schedule (<?php echo date('l, M d'); ?>)</h3>
            </div>
            <div class="form-container">
                <?php if (empty($todaySchedule)): ?>
                    <div class="empty-state">
                        <i class="fas fa-sun"></i>
                        <h4>No Classes Today</h4>
                        <p>Enjoy your day off!</p>
                    </div>
                <?php else: ?>
                    <div class="today-classes">
                        <?php foreach ($todaySchedule as $class): ?>
                            <div class="today-class-item">
                                <div class="today-class-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time'])); ?>
                                </div>
                                <div class="today-class-details">
                                    <h4><?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']); ?></h4>
                                    <p><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?></p>
                                    <p><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room_name'] . ' (' . ($class['location'] ?? '') . ')'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Schedule Table View -->
        <div class="dashboard-card">
            <div class="card-header">
                <i class="fas fa-table"></i>
                <h3>Schedule Table</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['day_of_week']); ?></td>
                                <td><?php echo date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($class['subject_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($class['subject_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['room_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <style>
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-item label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }

        .info-item span {
            color: #333;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        .stat-item i {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 10px;
        }

        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .day-column {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }

        .day-header {
            background: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .day-header h4 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }

        .class-count {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .day-classes {
            min-height: 200px;
        }

        .no-classes {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: #666;
            text-align: center;
        }

        .no-classes i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #ccc;
        }

        .class-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background: white;
        }

        .class-item:last-child {
            border-bottom: none;
        }

        .class-time {
            font-weight: 600;
            color: #007bff;
            margin-bottom: 8px;
        }

        .subject-code {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .subject-name {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .class-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 0.8rem;
            color: #888;
        }

        .class-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .today-classes {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .today-class-item {
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .today-class-time {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 10px;
        }

        .today-class-details h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .today-class-details p {
            margin: 5px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }

        .empty-state h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .table tr:hover {
            background: #f5f5f5;
        }

        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .student-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
