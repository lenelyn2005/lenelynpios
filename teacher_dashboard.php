<?php
// teacher_dashboard_fixed.php - Updated Teacher Dashboard with Fixed Sidebar
session_start();
require 'config.php';
require_once 'includes/sidebar_new.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login_teacher.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher information
$teacher = $mysqli->query("
    SELECT t.*, d.name as department_name
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.id
    WHERE t.id = '$teacher_id'
")->fetch_assoc();

if (!$teacher) {
    header("Location: login_teacher.php");
    exit;
}

// Fetch teacher's subjects
$teacherSubjects = $mysqli->query("
    SELECT s.*, d.name as department_name
    FROM subjects s
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY s.name
")->fetch_all(MYSQLI_ASSOC);

// Fetch teacher's schedule
$schedule = $mysqli->query("
    SELECT s.*, sub.name as subject_name, sub.code as subject_code,
           r.name as room_name, r.location as room_location,
           sec.name as section_name
    FROM schedules s
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN rooms r ON s.room_id = r.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.teacher_id = $teacher_id
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
")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'subjects' => count($teacherSubjects),
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

// Handle schedule change requests
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_change'])) {
    $schedule_id = $_POST['schedule_id'];
    $reason = trim($_POST['reason']);

    if (!empty($reason)) {
        $stmt = $mysqli->prepare("INSERT INTO schedule_change_requests (schedule_id, teacher_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iis", $schedule_id, $teacher_id, $reason);
        if ($stmt->execute()) {
            $success = "Schedule change request submitted successfully!";
        } else {
            $error = "Error submitting request.";
        }
        $stmt->close();
    } else {
        $error = "Please provide a reason for the schedule change.";
    }
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - College Scheduling System</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php renderSidebar('teacher', 'schedule'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Teacher Dashboard</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="teacher_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>My Schedule</span>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success fade-in"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error fade-in"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome back, <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>!</h2>
                <p>Department: <?php echo htmlspecialchars($teacher['department_name']); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['subjects']; ?></h3>
                        <p>Subjects Teaching</p>
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

            <!-- My Subjects -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-book"></i>
                    <h3>My Subjects</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($teacherSubjects)): ?>
                        <p>No subjects assigned yet.</p>
                    <?php else: ?>
                        <div class="subjects-grid">
                            <?php foreach ($teacherSubjects as $subject): ?>
                                <div class="subject-item">
                                    <div class="subject-info">
                                        <h4><?php echo htmlspecialchars($subject['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($subject['code']); ?> - <?php echo htmlspecialchars($subject['department_name']); ?></p>
                                        <small><?php echo $subject['units']; ?> units</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                                        <th>Section</th>
                                        <th>Room</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedule as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['day_of_week']); ?></td>
                                            <td><?php echo date('H:i', strtotime($class['start_time'])); ?> - <?php echo date('H:i', strtotime($class['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['section_name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['room_name'] . ' (' . $class['room_location'] . ')'); ?></td>
                                            <td>
                                                <button class="btn btn-small btn-warning" onclick="requestChange(<?php echo $class['id']; ?>)">
                                                    <i class="fas fa-exchange-alt"></i> Request Change
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i>
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="teacher_rooms.php" class="action-btn">
                            <i class="fas fa-door-open"></i>
                            <span>Check Room Availability</span>
                        </a>
                        <a href="teacher_profile.php" class="action-btn">
                            <i class="fas fa-user"></i>
                            <span>Update Profile</span>
                        </a>
                        <a href="teacher_notifications.php" class="action-btn">
                            <i class="fas fa-bell"></i>
                            <span>View Notifications</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Change Request Modal -->
    <div id="changeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Schedule Change</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="schedule_id" id="schedule_id">
                <div class="form-group">
                    <label for="reason">Reason for Change:</label>
                    <textarea name="reason" id="reason" rows="4" placeholder="Please explain why you need to change this schedule..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="request_change" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <script>
        function requestChange(scheduleId) {
            document.getElementById('schedule_id').value = scheduleId;
            document.getElementById('changeModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('changeModal').style.display = 'none';
            document.getElementById('reason').value = '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('changeModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

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

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .subject-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .subject-info h4 {
            margin: 0 0 8px 0;
            color: #333;
        }

        .subject-info p {
            margin: 0 0 5px 0;
            color: #666;
        }

        .subject-info small {
            color: #999;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            padding: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .modal-actions {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
            text-align: right;
        }

        .modal-actions button {
            margin-left: 10px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
    </style>
</body>
</html>