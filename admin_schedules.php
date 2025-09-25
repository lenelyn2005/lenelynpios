<?php
// admin_schedules.php - Admin Schedule Management
session_start();
require 'config.php';
require_once 'includes/sidebar.php';
require_once 'ga_scheduler.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['generate_schedule'])) {
        $scheduler = new GAScheduler($mysqli);
        $result = $scheduler->generateSchedule();
        if ($result['success']) {
            $success = "Schedule generated successfully! " . $result['message'];
        } else {
            $error = "Error generating schedule: " . $result['message'];
        }
    }
    
    elseif (isset($_POST['clear_schedules'])) {
        $stmt = $mysqli->prepare("DELETE FROM schedules");
        if ($stmt->execute()) {
            $success = "All schedules cleared successfully!";
        } else {
            $error = "Error clearing schedules: " . $mysqli->error;
        }
        $stmt->close();
    }
    
    elseif (isset($_POST['delete_schedule'])) {
        $id = $_POST['schedule_id'];
        $stmt = $mysqli->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Schedule deleted successfully!";
        } else {
            $error = "Error deleting schedule: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// Get schedule statistics
$schedule_stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_schedules,
        COUNT(DISTINCT teacher_id) as teachers_scheduled,
        COUNT(DISTINCT section_id) as sections_scheduled,
        COUNT(DISTINCT room_id) as rooms_used,
        COUNT(DISTINCT subject_id) as subjects_scheduled
    FROM schedules
")->fetch_assoc();

// Get all schedules with related information
$schedules_result = $mysqli->query("
    SELECT s.*, 
           t.first_name as teacher_first_name, t.last_name as teacher_last_name,
           sub.name as subject_name, sub.code as subject_code,
           sec.name as section_name,
           r.name as room_name, r.capacity as room_capacity,
           -- yl.name as year_level_name, -- Removed because year_levels join was removed
           c.name as course_name,
           d.name as department_name
    FROM schedules s
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN rooms r ON s.room_id = r.id
    -- Removed join on year_levels as year_level_id column does not exist in schedules table
    -- LEFT JOIN year_levels yl ON s.year_level_id = yl.id
    LEFT JOIN courses c ON sec.course_id = c.id
    LEFT JOIN departments d ON t.department_id = d.id
    ORDER BY s.day_of_week, s.start_time, s.room_id
");
if ($schedules_result) {
    $schedules = $schedules_result->fetch_all(MYSQLI_ASSOC);
} else {
    $schedules = [];
    $error = "Failed to fetch schedules: " . $mysqli->error;
}

// Group schedules by day for better display
$schedules_by_day = [];
foreach ($schedules as $schedule) {
    $day = $schedule['day_of_week'];
    if (!isset($schedules_by_day[$day])) {
        $schedules_by_day[$day] = [];
    }
    $schedules_by_day[$day][] = $schedule;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - Admin Dashboard (Offline Version)</title>
    <!-- For offline use: Download Font Awesome CSS from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- Keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> <!-- Assume local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume local -->
    <style>
        /* Inline CSS enhancements for user-friendly design: Modern, accessible, responsive */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .page-title {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .breadcrumb {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        .breadcrumb-separator {
            margin: 0 5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .stat-card:nth-child(4) .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card:nth-child(5) .stat-icon { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .stat-content h3 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }
        .stat-content p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .dashboard-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
        }
        .card-header i {
            margin-right: 10px;
            color: #3498db;
        }
        .card-content {
            padding: 20px;
        }
        .action-buttons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .schedule-day {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .schedule-day h4 {
            margin: 0 0 15px 0;
            color: #333;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .schedule-slots {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .schedule-item {
            background: white;
            border-radius: 6px;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 5px;
            transition: box-shadow 0.2s;
        }
        .schedule-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .schedule-time {
            font-weight: bold;
            color: #3498db;
            font-size: 0.9rem;
        }
        .schedule-details {
            font-size: 0.85rem;
        }
        .schedule-actions {
            display: flex;
            justify-content: flex-end;
        }
        .no-schedule {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 0.9rem;
        }
        .no-schedule i {
            font-size: 1.5rem;
            margin-bottom: 10px;
            display: block;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .badge-primary { background: #d6eaf8; color: #3498db; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #bdc3c7;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
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
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e9ecef;
        }
        .close {
            cursor: pointer;
            font-size: 1.5rem;
            color: #95a5a6;
        }
        .close:hover {
            color: #34495e;
        }
        .text-danger {
            color: #e74c3c;
        }
        .text-muted {
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .stats-grid, .action-buttons-grid, .schedule-grid {
                grid-template-columns: 1fr;
            }
            .top-navbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .navbar-right {
                margin-top: 10px;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php renderSidebar('admin', 'schedules'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Schedule Management</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Schedules</span>
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

            <!-- Schedule Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $schedule_stats['total_schedules']; ?></h3>
                        <p>Total Schedules</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $schedule_stats['teachers_scheduled']; ?></h3>
                        <p>Teachers Scheduled</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $schedule_stats['sections_scheduled']; ?></h3>
                        <p>Sections Scheduled</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $schedule_stats['rooms_used']; ?></h3>
                        <p>Rooms Used</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $schedule_stats['subjects_scheduled']; ?></h3>
                        <p>Subjects Scheduled</p>
                    </div>
                </div>
            </div>

            <!-- Schedule Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-cog"></i>
                    <h3>Schedule Actions</h3>
                </div>
                <div class="card-content">
                    <div class="action-buttons-grid">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="generate_schedule" class="btn btn-success" onclick="return confirm('This will generate a new schedule using the Genetic Algorithm. Continue?')">
                                <i class="fas fa-dna"></i> Generate New Schedule
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="clear_schedules" class="btn btn-warning" onclick="return confirm('This will delete ALL schedules. Are you sure?')">
                                <i class="fas fa-trash-alt"></i> Clear All Schedules
                            </button>
                        </form>
                        
                        <button class="btn btn-info" onclick="exportSchedules()">
                            <i class="fas fa-download"></i> Export Schedules
                        </button>
                        
                        <button class="btn btn-primary" onclick="viewConflicts()">
                            <i class="fas fa-exclamation-triangle"></i> Check Conflicts
                        </button>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule View -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-calendar-week"></i>
                    <h3>Weekly Schedule Overview</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($schedules)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Schedules Found</h3>
                            <p>Generate a schedule to view the weekly overview.</p>
                        </div>
                    <?php else: ?>
                        <div class="schedule-grid">
                            <?php foreach ($days as $day): ?>
                                <div class="schedule-day">
                                    <h4><?php echo $day; ?></h4>
                                    <div class="schedule-slots">
                                        <?php if (isset($schedules_by_day[$day])): ?>
                                            <?php foreach ($schedules_by_day[$day] as $schedule): ?>
                                                <div class="schedule-item">
                                                    <div class="schedule-time">
                                                        <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - 
                                                        <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                                    </div>
                                                    <div class="schedule-details">
                                                        <strong><?php echo htmlspecialchars($schedule['subject_name']); ?></strong>
                                                        <br>
                                                        <small>
                                                            <?php echo htmlspecialchars($schedule['teacher_first_name'] . ' ' . $schedule['teacher_last_name']); ?> | 
                                                            <?php echo htmlspecialchars($schedule['section_name']); ?> | 
                                                            <?php echo htmlspecialchars($schedule['room_name']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="schedule-actions">
                                                        <button class="btn btn-sm btn-danger" onclick="deleteSchedule(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['subject_name']); ?>')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-schedule">
                                                <i class="fas fa-calendar-times"></i>
                                                <span>No classes</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detailed Schedule Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-table"></i>
                    <h3>Detailed Schedule List</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($schedules)): ?>
                        <div class="empty-state">
                            <i class="fas fa-table"></i>
                            <h3>No Schedules Found</h3>
                            <p>Generate a schedule to view the detailed list.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Section</th>
                                        <th>Room</th>
                                        <th>Capacity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo $schedule['day_of_week']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - 
                                                <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($schedule['subject_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($schedule['subject_code']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($schedule['teacher_first_name'] . ' ' . $schedule['teacher_last_name']); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($schedule['section_name']); ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($schedule['course_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo htmlspecialchars($schedule['room_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <?php echo $schedule['room_capacity']; ?> seats
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editSchedule(<?php echo $schedule['id']; ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteSchedule(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['subject_name']); ?>')">
                                                        <i class="fas fa-trash-alt"></i> Delete
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
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="deleteModalTitle">Confirm Delete</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the schedule for "<span id="delete_schedule_name"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_schedule_id" name="schedule_id">
                    <button type="submit" name="delete_schedule" class="btn btn-danger">Delete Schedule</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Enhanced JavaScript for better usability: Smooth modals, keyboard accessibility
        function deleteSchedule(id, name) {
            document.getElementById('delete_schedule_id').value = id;
            document.getElementById('delete_schedule_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // Edit Schedule Function (placeholder)
        function editSchedule(id) {
            alert('Edit schedule feature coming soon! Schedule ID: ' + id);
        }

        // Export Schedules Function (placeholder)
        function exportSchedules() {
            alert('Export schedules feature coming soon!');
        }

        // View Conflicts Function (placeholder)
        function viewConflicts() {
            alert('Conflict checking feature coming soon!');
        }

        // Close Modal Function
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        }

        // Close modal when clicking X or Escape key
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                closeModal();
            }
        });

        // Keyboard accessibility: Close on Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>