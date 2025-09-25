<?php
// student_dashboard_fixed.php - Updated Student Dashboard with Fixed Sidebar
session_start();
require 'config.php';
require_once 'includes/sidebar_new.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login_student.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch student information
$student = $mysqli->query("
    SELECT s.*, sec.name as section_name, yl.name as year_level_name, c.name as course_name
    FROM students s
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN year_levels yl ON s.year_level_id = yl.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE s.id = '$student_id'
")->fetch_assoc();

if (!$student) {
    header("Location: login_student.php");
    exit;
}

// Fetch student's schedule
$schedule = $mysqli->query("
    SELECT sch.*, sub.name as subject_name, sub.code as subject_code,
           r.name as room_name, r.location as room_location
    FROM schedules sch
    LEFT JOIN subjects sub ON sch.subject_id = sub.id
    LEFT JOIN rooms r ON sch.room_id = r.id
    WHERE sch.section_id = {$student['section_id']}
    ORDER BY
        CASE sch.day_of_week
            WHEN 'Monday' THEN 1
            WHEN 'Tuesday' THEN 2
            WHEN 'Wednesday' THEN 3
            WHEN 'Thursday' THEN 4
            WHEN 'Friday' THEN 5
            WHEN 'Saturday' THEN 6
            WHEN 'Sunday' THEN 7
        END,
        sch.start_time
")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'subjects' => count($schedule),
    'classes_today' => 0,
    'total_hours' => 0
];

// Count today's classes
$today = date('l');
foreach ($schedule as $class) {
    if ($class['day_of_week'] == $today) {
        $stats['classes_today']++;
    }
    // Calculate total hours (assuming each class is 1 hour)
    $stats['total_hours']++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Dashboard - College Scheduling System</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="includes/sidebar.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
    <?php renderSidebar('student', 'schedule'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Student Dashboard</h1>
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
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome back, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>!</h2>
                <p>Section: <?php echo htmlspecialchars($student['section_name']); ?> | Year Level: <?php echo htmlspecialchars($student['year_level_name']); ?> | Course: <?php echo htmlspecialchars($student['course_name']); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['subjects']; ?></h3>
                        <p>Subjects Enrolled</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['classes_today']; ?></h3>
                        <p>Classes Today</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_hours']; ?></h3>
                        <p>Total Classes/Week</p>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-calendar-week"></i>
                    <h3>Weekly Schedule</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($schedule)): ?>
                        <p>No schedule assigned yet.</p>
                    <?php else: ?>
                        <div class="schedule-table-container">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedule as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['day_of_week']); ?></td>
                                            <td><?php echo date('H:i', strtotime($class['start_time'])); ?> - <?php echo date('H:i', strtotime($class['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['room_name'] . ' (' . $class['room_location'] . ')'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <style>
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .welcome-section p {
            margin: 0;
            opacity: 0.9;
        }

        .schedule-table-container {
            overflow-x: auto;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .schedule-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .schedule-table tr:hover {
            background: #f5f5f5;
        }
    </style>
</body>
</html>
