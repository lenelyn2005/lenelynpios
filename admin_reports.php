<?php
// admin_reports.php - Admin Reports and Analytics
session_start();
require 'config.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

// Get comprehensive statistics
$stats = [];

// Basic counts
$stats['departments'] = $mysqli->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$stats['courses'] = $mysqli->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$stats['sections'] = $mysqli->query("SELECT COUNT(*) as count FROM sections")->fetch_assoc()['count'];
$stats['subjects'] = $mysqli->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];
$stats['teachers'] = $mysqli->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$stats['students'] = $mysqli->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$stats['rooms'] = $mysqli->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$stats['schedules'] = $mysqli->query("SELECT COUNT(*) as count FROM schedules")->fetch_assoc()['count'];

// Teacher statistics
$teacher_stats = $mysqli->query("
    SELECT 
        employment_type,
        COUNT(*) as count
    FROM teachers 
    GROUP BY employment_type
")->fetch_all(MYSQLI_ASSOC);

// Department statistics
$department_stats = $mysqli->query("
    SELECT 
        d.name as department_name,
        COUNT(DISTINCT c.id) as course_count,
        COUNT(DISTINCT t.id) as teacher_count,
        COUNT(DISTINCT s.id) as student_count
    FROM departments d
    LEFT JOIN courses c ON d.id = c.department_id
    LEFT JOIN teachers t ON d.id = t.department_id
    LEFT JOIN students s ON d.id = (SELECT department_id FROM courses WHERE id = s.course_id)
    GROUP BY d.id, d.name
    ORDER BY d.name
")->fetch_all(MYSQLI_ASSOC);

// Room utilization
$room_utilization = $mysqli->query("
    SELECT 
        r.name as room_name,
        r.capacity,
        COUNT(s.id) as schedule_count,
        ROUND((COUNT(s.id) * 100.0 / 35), 2) as utilization_percentage
    FROM rooms r
    LEFT JOIN schedules s ON r.id = s.room_id
    GROUP BY r.id, r.name, r.capacity
    ORDER BY utilization_percentage DESC
")->fetch_all(MYSQLI_ASSOC);

// Schedule distribution by day
$schedule_by_day = $mysqli->query("
    SELECT 
        day_of_week,
        COUNT(*) as schedule_count
    FROM schedules
    GROUP BY day_of_week
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
")->fetch_all(MYSQLI_ASSOC);

// Top subjects by schedule count
$top_subjects = $mysqli->query("
    SELECT 
        sub.name as subject_name,
        sub.code as subject_code,
        COUNT(s.id) as schedule_count
    FROM subjects sub
    LEFT JOIN schedules s ON sub.id = s.subject_id
    GROUP BY sub.id, sub.name, sub.code
    ORDER BY schedule_count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Teacher workload analysis
$teacher_workload = $mysqli->query("
    SELECT 
        t.first_name,
        t.last_name,
        t.employment_type,
        t.monthly_hours,
        t.daily_hours,
        COUNT(s.id) as schedule_count,
        SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time))) / 3600 as total_hours
    FROM teachers t
    LEFT JOIN schedules s ON t.id = s.teacher_id
    GROUP BY t.id, t.first_name, t.last_name, t.employment_type, t.monthly_hours, t.daily_hours
    ORDER BY total_hours DESC
")->fetch_all(MYSQLI_ASSOC);

// Section capacity analysis
$section_capacity = $mysqli->query("
    SELECT 
        sec.name as section_name,
        sec.max_students,
        COUNT(st.id) as current_students,
        ROUND((COUNT(st.id) * 100.0 / sec.max_students), 2) as capacity_percentage
    FROM sections sec
    LEFT JOIN students st ON sec.id = st.section_id
    GROUP BY sec.id, sec.name, sec.max_students
    ORDER BY capacity_percentage DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Dashboard (Offline Version)</title>
    <!-- For offline use: Download Font Awesome CSS from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- Keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- For offline use: Download Chart.js from https://www.chartjs.org/docs/latest/getting-started/ and place in a local 'chartjs' folder -->
    <!-- Example local script: <script src="chartjs/chart.umd.min.js"></script> -->
    <!-- Keeping CDN for demonstration; replace with local for true offline -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="styles.css"> <!-- Assume local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume local -->
    <style>
        /* CSS Variables for Theming - Matching admin dashboard */
        :root {
            --primary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --secondary-color: #95a5a6;
            --background-color: #f8f9fa;
            --text-color: #2c3e50;
            --muted-text: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0,0,0,0.08);
            --border-color: #e1e8ed;
            --hover-shadow: 0 4px 20px rgba(0,0,0,0.12);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --body-bg: #f8f9fc;
            --border-radius: 12px;
            --font-size-base: 14px;
            --hover-lift: translateY(-2px);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--body-bg);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            line-height: 1.5;
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
            background: var(--white);
            padding: 16px 24px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            border-radius: var(--border-radius);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--muted-text);
            padding: 8px;
            border-radius: 6px;
            transition: var(--transition);
        }

        .mobile-menu-btn:hover,
        .mobile-menu-btn:focus {
            background: var(--background-color);
            color: var(--primary-color);
            outline: none;
        }

        .page-title {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .navbar-right {
            display: flex;
            align-items: center;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            font-size: var(--font-size-base);
            color: var(--muted-text);
            gap: 8px;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover,
        .breadcrumb a:focus {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--border-color);
        }

        .content-area {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: var(--hover-lift);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--white);
            flex-shrink: 0;
        }

        .stat-card:nth-child(1) .stat-icon { background: var(--primary-color); }
        .stat-card:nth-child(2) .stat-icon { background: var(--danger-color); }
        .stat-card:nth-child(3) .stat-icon { background: var(--success-color); }
        .stat-card:nth-child(4) .stat-icon { background: var(--warning-color); }
        .stat-card:nth-child(5) .stat-icon { background: #9b59b6; }
        .stat-card:nth-child(6) .stat-icon { background: #1abc9c; }
        .stat-card:nth-child(7) .stat-icon { background: var(--secondary-color); }
        .stat-card:nth-child(8) .stat-icon { background: var(--warning-color); }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 4px 0;
            color: var(--text-color);
        }

        .stat-content p {
            margin: 0;
            color: var(--muted-text);
            font-size: var(--font-size-base);
            font-weight: 500;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .dashboard-card:hover {
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            background: var(--background-color);
            border-bottom: 1px solid var(--border-color);
        }

        .card-header i {
            color: var(--primary-color);
            font-size: 20px;
            flex-shrink: 0;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-content {
            padding: 24px;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-top: 16px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .data-table th,
        .data-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--background-color);
            font-weight: 600;
            font-size: var(--font-size-base);
            color: var(--text-color);
        }

        .data-table tbody tr {
            transition: var(--transition);
        }

        .data-table tbody tr:hover {
            background: var(--background-color);
        }

        .data-table td {
            font-size: var(--font-size-base);
        }

        .text-muted {
            color: var(--muted-text) !important;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: rgba(52, 152, 219, 0.2);
            color: var(--primary-color);
        }

        .badge-info {
            background: rgba(52, 152, 219, 0.2);
            color: var(--info-color);
        }

        .badge-success {
            background: rgba(39, 174, 96, 0.2);
            color: var(--success-color);
        }

        .badge-warning {
            background: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }

        .badge-danger {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }

        .badge-secondary {
            background: rgba(149, 165, 166, 0.2);
            color: var(--secondary-color);
        }

        .badge-light {
            background: rgba(248, 249, 250, 0.5);
            color: var(--text-color);
        }

        /* Progress Bar for Reports */
        .progress-bar {
            position: relative;
            height: 24px;
            background: var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            transition: width 0.3s ease;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.85rem;
            font-weight: bold;
            color: var(--white);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }

            .top-navbar {
                padding: 12px 16px;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .navbar-left {
                justify-content: space-between;
                width: 100%;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
            }

            .data-table th,
            .data-table td {
                padding: 12px 8px;
            }

            .card-content {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.25rem;
            }

            .breadcrumb {
                font-size: 13px;
                flex-wrap: wrap;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* High contrast and reduced motion support */
        @media (prefers-contrast: high) {
            :root {
                --shadow: 0 2px 10px rgba(0,0,0,0.2);
                --border-color: #000;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
        }

        /* Print styles */
        @media print {
            .top-navbar {
                display: none;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php renderSidebar('admin', 'reports'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Reports & Analytics</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Reports</span>
                </div>
            </div>
        </div>
        
        <div class="content-area">
            <!-- Overview Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['departments']; ?></h3>
                        <p>Departments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['courses']; ?></h3>
                        <p>Courses</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['sections']; ?></h3>
                        <p>Sections</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['subjects']; ?></h3>
                        <p>Subjects</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['teachers']; ?></h3>
                        <p>Teachers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['students']; ?></h3>
                        <p>Students</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['rooms']; ?></h3>
                        <p>Rooms</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['schedules']; ?></h3>
                        <p>Schedules</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="dashboard-grid">
                <!-- Teacher Employment Distribution -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i>
                        <h3>Teacher Employment Distribution</h3>
                    </div>
                    <div class="card-content">
                        <canvas id="teacherChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Schedule Distribution by Day -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Schedule Distribution by Day</h3>
                    </div>
                    <div class="card-content">
                        <canvas id="scheduleChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Department Analysis -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>Department Analysis</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Courses</th>
                                    <th>Teachers</th>
                                    <th>Students</th>
                                    <th>Total Resources</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($department_stats as $dept): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                                        <td><span class="badge badge-primary"><?php echo $dept['course_count']; ?></span></td>
                                        <td><span class="badge badge-info"><?php echo $dept['teacher_count']; ?></span></td>
                                        <td><span class="badge badge-success"><?php echo $dept['student_count']; ?></span></td>
                                        <td><span class="badge badge-warning"><?php echo $dept['course_count'] + $dept['teacher_count'] + $dept['student_count']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Room Utilization -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-area"></i>
                    <h3>Room Utilization Analysis</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Capacity</th>
                                    <th>Schedules</th>
                                    <th>Utilization</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($room_utilization as $room): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($room['room_name']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo $room['capacity']; ?> seats</span></td>
                                        <td><span class="badge badge-primary"><?php echo $room['schedule_count']; ?></span></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $room['utilization_percentage']; ?>%"></div>
                                                <span class="progress-text"><?php echo $room['utilization_percentage']; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($room['utilization_percentage'] > 80): ?>
                                                <span class="badge badge-danger">High Usage</span>
                                            <?php elseif ($room['utilization_percentage'] > 50): ?>
                                                <span class="badge badge-warning">Moderate Usage</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Low Usage</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Subjects -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-trophy"></i>
                    <h3>Top Subjects by Schedule Count</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Subject</th>
                                    <th>Code</th>
                                    <th>Schedule Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_subjects as $index => $subject): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <span class="badge badge-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'success'); ?>">
                                                    <?php echo $index + 1; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-light"><?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($subject['subject_code']); ?></span></td>
                                        <td><span class="badge badge-info"><?php echo $subject['schedule_count']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Teacher Workload Analysis -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user-clock"></i>
                    <h3>Teacher Workload Analysis</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Employment Type</th>
                                    <th>Allocated Hours</th>
                                    <th>Scheduled Hours</th>
                                    <th>Utilization</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacher_workload as $teacher): ?>
                                    <?php 
                                    $allocated_hours = $teacher['employment_type'] == 'full_time' ? $teacher['monthly_hours'] : ($teacher['daily_hours'] * 5 * 4); // Assuming 5 days per week, 4 weeks per month
                                    $utilization = $allocated_hours > 0 ? round(($teacher['total_hours'] / $allocated_hours) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $teacher['employment_type'] == 'full_time' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $teacher['employment_type'])); ?>
                                            </span>
                                        </td>
                                        <td><span class="badge badge-info"><?php echo $allocated_hours; ?> hrs</span></td>
                                        <td><span class="badge badge-primary"><?php echo round($teacher['total_hours'], 1); ?> hrs</span></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min($utilization, 100); ?>%"></div>
                                                <span class="progress-text"><?php echo $utilization; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($utilization > 100): ?>
                                                <span class="badge badge-danger">Overloaded</span>
                                            <?php elseif ($utilization > 80): ?>
                                                <span class="badge badge-warning">High Load</span>
                                            <?php elseif ($utilization > 50): ?>
                                                <span class="badge badge-success">Optimal</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Underutilized</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Section Capacity Analysis -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-users-cog"></i>
                    <h3>Section Capacity Analysis</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Max Students</th>
                                    <th>Current Students</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section_capacity as $section): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($section['section_name']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo $section['max_students']; ?></span></td>
                                        <td><span class="badge badge-primary"><?php echo $section['current_students']; ?></span></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $section['capacity_percentage']; ?>%"></div>
                                                <span class="progress-text"><?php echo $section['capacity_percentage']; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($section['capacity_percentage'] > 90): ?>
                                                <span class="badge badge-danger">Overcrowded</span>
                                            <?php elseif ($section['capacity_percentage'] > 70): ?>
                                                <span class="badge badge-warning">Near Full</span>
                                            <?php elseif ($section['capacity_percentage'] > 30): ?>
                                                <span class="badge badge-success">Good</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Underutilized</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Teacher Employment Chart
        const teacherCtx = document.getElementById('teacherChart').getContext('2d');
        const teacherData = <?php echo json_encode($teacher_stats); ?>;
        
        new Chart(teacherCtx, {
            type: 'doughnut',
            data: {
                labels: teacherData.map(item => item.employment_type.replace('_', ' ').toUpperCase()),
                datasets: [{
                    data: teacherData.map(item => item.count),
                    backgroundColor: [
                        '#3498db',
                        '#e74c3c',
                        '#2ecc71',
                        '#f39c12',
                        '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Schedule Distribution Chart
        const scheduleCtx = document.getElementById('scheduleChart').getContext('2d');
        const scheduleData = <?php echo json_encode($schedule_by_day); ?>;
        
        new Chart(scheduleCtx, {
            type: 'bar',
            data: {
                labels: scheduleData.map(item => item.day_of_week),
                datasets: [{
                    label: 'Number of Schedules',
                    data: scheduleData.map(item => item.schedule_count),
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
