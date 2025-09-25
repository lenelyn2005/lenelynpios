<?php
// admin_teacher_assignments.php - Admin interface for managing teacher-subject assignments
session_start();
require 'config.php';
require_once 'includes/sidebar_new.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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


}

// Fetch dropdown data
$teachers = $mysqli->query("
    SELECT t.*, d.name as department_name,
           COUNT(ts.id) as subject_count
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
    GROUP BY t.id
    ORDER BY t.last_name, t.first_name
")->fetch_all(MYSQLI_ASSOC);

$departments = $mysqli->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$courses = $mysqli->query("SELECT id, name FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$subjects = $mysqli->query("
    SELECT s.id, s.name, s.code, c.name AS course_name, d.name AS dept_name
    FROM subjects s
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY c.name, s.name
")->fetch_all(MYSQLI_ASSOC);

// Group subjects by course
$grouped_subjects = [];
foreach ($subjects as $subject) {
    $course_name = $subject['course_name'] ?: 'No Course';
    $grouped_subjects[$course_name][] = $subject;
}

// Group teachers by department
$grouped_teachers = [];
foreach ($teachers as $teacher) {
    $department_name = $teacher['department_name'] ?: 'No Department';
    $grouped_teachers[$department_name][] = $teacher;
}

// Get statistics for teacher assignments
$stats = [
    'total_assignments' => $mysqli->query("SELECT COUNT(*) as count FROM teacher_subjects")->fetch_assoc()['count'],
    'teachers_with_assignments' => $mysqli->query("SELECT COUNT(DISTINCT teacher_id) as count FROM teacher_subjects")->fetch_assoc()['count'],
    'subjects_assigned' => $mysqli->query("SELECT COUNT(DISTINCT subject_id) as count FROM teacher_subjects")->fetch_assoc()['count'],
    'total_teachers' => $mysqli->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'],
    'total_subjects' => $mysqli->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count']
];

// Fetch current assignments with details
$current_assignments = $mysqli->query("
    SELECT ts.id, t.first_name, t.last_name, t.username,
           s.name as subject_name, s.code as subject_code,
           d.name as department_name, c.name as course_name
    FROM teacher_subjects ts
    JOIN teachers t ON ts.teacher_id = t.id
    JOIN subjects s ON ts.subject_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN courses c ON s.course_id = c.id
    ORDER BY t.last_name, t.first_name, s.name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Assignments - Admin Dashboard (Offline Version)</title>
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
        }
        input, select, textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #3498db;
            outline: none;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
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
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
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
        .stat-card:nth-child(6) .stat-icon { background: linear-gradient(135deg, #1abc9c, #16a085); }
        .stat-card:nth-child(7) .stat-icon { background: linear-gradient(135deg, #34495e, #2c3e50); }
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
            max-width: 600px;
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
            display: flex;
            flex-direction: column;
            gap: 15px;
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
        .assignment-checkbox {
            margin-right: 8px;
        }
        .subject-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .subject-item:last-child {
            border-bottom: none;
        }
        .subject-info {
            flex-grow: 1;
        }
        .subject-info small {
            color: #666;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .top-navbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .navbar-right {
                margin-top: 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php renderSidebar('admin', 'assignments'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Teacher Assignments</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Teacher Assignments</span>
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

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_assignments']; ?></h3>
                        <p>Total Assignments</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['teachers_with_assignments']; ?>/<?php echo $stats['total_teachers']; ?></h3>
                        <p>Teachers with Assignments</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['subjects_assigned']; ?>/<?php echo $stats['total_subjects']; ?></h3>
                        <p>Subjects Assigned</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_teachers'] > 0 ? round(($stats['teachers_with_assignments'] / $stats['total_teachers']) * 100, 1) : 0; ?>%</h3>
                        <p>Teachers Utilization</p>
                    </div>
                </div>
            </div>

            <!-- Individual Assignment Form -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Assign Teacher to Subjects</h3>
                </div>
                <div class="card-content">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="teacher_id">Select Teacher <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <select id="teacher_id" name="teacher_id" required aria-required="true">
                                <option value="">Choose a teacher...</option>
                                <?php foreach ($grouped_teachers as $department_name => $department_teachers): ?>
                                    <optgroup label="<?php echo htmlspecialchars($department_name); ?>">
                                        <?php foreach ($department_teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['id']; ?>">
                                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                                - <?php echo $teacher['subject_count']; ?> subjects assigned
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="course_filter">Filter by Course</label>
                            <select id="course_filter" name="course_filter">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="subject_search">Search Subjects</label>
                            <input type="text" id="subject_search" placeholder="Search by subject name, code, course, or department..." style="margin-bottom: 15px;">
                            
                            <label>Select Subjects <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <div id="subjects_container" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px;">
                                <?php if (empty($grouped_subjects)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-book"></i>
                                        <h3>No Subjects Found</h3>
                                        <p>Add subjects in the Subjects management section.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($grouped_subjects as $course_name => $course_subjects): ?>
                                        <?php
                                        // Get the course ID for the first subject in this group
                                        $course_id = '';
                                        if (!empty($course_subjects)) {
                                            $first_subject = $course_subjects[0];
                                            $course_id = isset($first_subject['course_id']) ? $first_subject['course_id'] : '';
                                        }
                                        ?>
                                        <div class="course-group" data-course-id="<?php echo $course_id; ?>" data-course="<?php echo htmlspecialchars(strtolower($course_name)); ?>">
                                            <div class="course-header" style="background: #f8f9fa; padding: 10px; margin: 10px 0 5px 0; border-radius: 6px; font-weight: bold; color: #2c3e50; border-left: 4px solid #3498db;">
                                                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($course_name); ?>
                                            </div>
                                            <div class="course-subjects">
                                                <?php foreach ($course_subjects as $subject): ?>
                                                    <div class="subject-item" data-subject="<?php echo htmlspecialchars(strtolower($subject['name'] . ' ' . $subject['code'] . ' ' . $subject['course_name'] . ' ' . $subject['dept_name'])); ?>">
                                                        <input type="checkbox" name="subject_ids[]" value="<?php echo $subject['id']; ?>" class="assignment-checkbox" id="subject_<?php echo $subject['id']; ?>">
                                                        <label for="subject_<?php echo $subject['id']; ?>" style="cursor: pointer; margin: 0;">
                                                            <div class="subject-info">
                                                                <strong><?php echo htmlspecialchars($subject['name']); ?></strong> (<?php echo htmlspecialchars($subject['code']); ?>)
                                                                <br><small><?php echo htmlspecialchars($subject['dept_name']); ?></small>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="assign_teacher" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Assign Selected Subjects
                            </button>
                        </div>
                    </form>
                </div>
            </div>



            <!-- Current Assignments List -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-list-ul"></i>
                    <h3>Current Assignments (<?php echo count($current_assignments); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($current_assignments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-plus"></i>
                            <h3>No Assignments Found</h3>
                            <p>Create your first teacher assignment above to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Subject</th>
                                        <th>Department</th>
                                        <th>Course</th>
                                        <th>Actions</th>
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
                                                <span class="badge badge-primary">
                                                    <?php echo htmlspecialchars($assignment['subject_code']); ?>
                                                </span>
                                                <strong><?php echo htmlspecialchars($assignment['subject_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($assignment['department_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo htmlspecialchars($assignment['course_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-danger" onclick="unassignTeacher(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>', '<?php echo htmlspecialchars($assignment['subject_name']); ?>')">
                                                        <i class="fas fa-trash-alt"></i> Unassign
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

    <!-- Unassign Confirmation Modal -->
    <div id="unassignModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="unassignModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="unassignModalTitle">Confirm Unassignment</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to unassign <span id="unassign_teacher_name"></span> from <span id="unassign_subject_name"></span>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and may affect related schedules.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="unassign_teacher_id" name="teacher_id">
                    <input type="hidden" id="unassign_subject_id" name="subject_id">
                    <button type="submit" name="unassign_teacher" class="btn btn-danger">Unassign Teacher</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Unassign Teacher Function
        function unassignTeacher(assignmentId, teacherName, subjectName) {
            // Find the assignment details to get teacher_id and subject_id
            const assignment = <?php echo json_encode($current_assignments); ?>.find(a => a.id == assignmentId);
            if (assignment) {
                document.getElementById('unassign_teacher_id').value = assignment.teacher_id;
                document.getElementById('unassign_subject_id').value = assignment.subject_id;
                document.getElementById('unassign_teacher_name').textContent = teacherName;
                document.getElementById('unassign_subject_name').textContent = subjectName;
                document.getElementById('unassignModal').style.display = 'flex';
            }
        }

        // Close Modal Function
        function closeModal() {
            document.getElementById('unassignModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('unassignModal');
            if (event.target === modal) {
                modal.style.display = 'none';
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

        // Course filtering functionality
        document.getElementById('course_filter').addEventListener('change', function(e) {
            const selectedCourseId = e.target.value;
            const courseGroups = document.querySelectorAll('.course-group');

            console.log('Selected course ID:', selectedCourseId);

            courseGroups.forEach(group => {
                const courseId = group.getAttribute('data-course-id');
                console.log('Course group ID:', courseId, 'Course name:', group.querySelector('.course-header').textContent);

                // Show all groups if "All Courses" is selected (empty string)
                if (!selectedCourseId) {
                    group.style.display = 'block';
                    console.log('Showing group (all courses):', group.querySelector('.course-header').textContent);
                }
                // Show only groups that match the selected course ID
                else if (String(courseId) === String(selectedCourseId)) {
                    group.style.display = 'block';
                    console.log('Showing group (matching course):', group.querySelector('.course-header').textContent);
                }
                else {
                    group.style.display = 'none';
                    console.log('Hiding group (no match):', group.querySelector('.course-header').textContent);
                }
            });

            // Update search results if there's a search term
            const searchInput = document.getElementById('subject_search');
            if (searchInput.value.trim()) {
                searchInput.dispatchEvent(new Event('input'));
            }
        });

        // Search functionality for subjects
        document.getElementById('subject_search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const courseGroups = document.querySelectorAll('.course-group');

            courseGroups.forEach(group => {
                const courseHeader = group.querySelector('.course-header');
                const subjectItems = group.querySelectorAll('.subject-item');
                let visibleSubjects = 0;

                subjectItems.forEach(item => {
                    const subjectText = item.getAttribute('data-subject');
                    if (subjectText.includes(searchTerm)) {
                        item.style.display = 'flex';
                        visibleSubjects++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Show/hide course group based on whether it has visible subjects
                if (visibleSubjects > 0) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });

            // Show message if no results found
            const visibleGroups = Array.from(courseGroups).some(group => group.style.display !== 'none');
            let noResultsMsg = document.getElementById('no-results-message');
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'no-results-message';
                noResultsMsg.style.cssText = 'text-align: center; padding: 20px; color: #7f8c8d; display: none;';
                noResultsMsg.innerHTML = '<i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; color: #bdc3c7;"></i><h3>No subjects found</h3><p>Try adjusting your search terms.</p>';
                document.getElementById('subjects_container').appendChild(noResultsMsg);
            }

            if (!visibleGroups && searchTerm) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        });

        // Select all/none functionality for subject checkboxes
        function toggleAllSubjects() {
            const checkboxes = document.querySelectorAll('input[name="subject_ids[]"]');
            const selectAllBtn = document.getElementById('selectAllBtn');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(cb => cb.checked = !allChecked);
            selectAllBtn.textContent = allChecked ? 'Select All' : 'Deselect All';
        }
    </script>
</body>
</html>
