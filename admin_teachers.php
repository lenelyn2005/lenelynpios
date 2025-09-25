<?php
// admin_teachers.php - Admin CRUD for Teachers
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
    if (isset($_POST['add_teacher'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $department_id = $_POST['department_id'];
        $employment_type = $_POST['employment_type'];
        $monthly_hours = $_POST['monthly_hours'] ?? 0;
        $daily_hours = $_POST['daily_hours'] ?? 0;
        
        if (!empty($username) && !empty($password) && !empty($first_name) && !empty($last_name) && !empty($department_id)) {
            // Check if username already exists
            $check_stmt = $mysqli->prepare("SELECT id FROM teachers WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO teachers (username, password, first_name, last_name, email, department_id, employment_type, monthly_hours, daily_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssisii", $username, $hashed_password, $first_name, $last_name, $email, $department_id, $employment_type, $monthly_hours, $daily_hours);
                if ($stmt->execute()) {
                    $success = "Teacher added successfully!";
                } else {
                    $error = "Error adding teacher: " . $mysqli->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['edit_teacher'])) {
        $id = $_POST['teacher_id'];
        $username = trim($_POST['username']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $department_id = $_POST['department_id'];
        $employment_type = $_POST['employment_type'];
        $monthly_hours = $_POST['monthly_hours'] ?? 0;
        $daily_hours = $_POST['daily_hours'] ?? 0;
        
        if (!empty($username) && !empty($first_name) && !empty($last_name) && !empty($department_id)) {
            // Check if username already exists (excluding current teacher)
            $check_stmt = $mysqli->prepare("SELECT id FROM teachers WHERE username = ? AND id != ?");
            $check_stmt->bind_param("si", $username, $id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                $stmt = $mysqli->prepare("UPDATE teachers SET username = ?, first_name = ?, last_name = ?, email = ?, department_id = ?, employment_type = ?, monthly_hours = ?, daily_hours = ? WHERE id = ?");
                $stmt->bind_param("ssssisiii", $username, $first_name, $last_name, $email, $department_id, $employment_type, $monthly_hours, $daily_hours, $id);
                if ($stmt->execute()) {
                    $success = "Teacher updated successfully!";
                } else {
                    $error = "Error updating teacher: " . $mysqli->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['change_password'])) {
        $id = $_POST['teacher_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!empty($new_password) && $new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE teachers SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $id);
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error changing password: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Passwords do not match or are empty.";
        }
    }
    
    elseif (isset($_POST['delete_teacher'])) {
        $id = $_POST['teacher_id'];
        $stmt = $mysqli->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Teacher deleted successfully!";
        } else {
            $error = "Error deleting teacher: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// Fetch teachers with department information
$teachers = $mysqli->query("
    SELECT t.*, d.name as department_name,
           COUNT(ts.id) as subject_count
    FROM teachers t 
    LEFT JOIN departments d ON t.department_id = d.id 
    LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
    GROUP BY t.id
    ORDER BY t.last_name, t.first_name
")->fetch_all(MYSQLI_ASSOC);

// Fetch departments for dropdown
$departments = $mysqli->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin Dashboard (Offline Version)</title>
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
        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
        }
        input, select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus, select:focus {
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
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background: #e67e22;
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
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #d6eaf8; color: #3498db; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
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
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php renderSidebar('admin', 'teachers'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Teachers</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Teachers</span>
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

            <!-- Add Teacher Form -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New Teacher</h3>
                </div>
                <div class="card-content">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="username">Username <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="username" name="username" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="password" id="password" name="password" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">First Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="first_name" name="first_name" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="last_name" name="last_name" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="department_id">Department <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <select id="department_id" name="department_id" required aria-required="true">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="employment_type">Employment Type <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <select id="employment_type" name="employment_type" required onchange="toggleWorkloadFields()" aria-required="true">
                                <option value="">Select Type</option>
                                <option value="full_time">Full-time</option>
                                <option value="part_time">Part-time</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="monthly_hours_group" style="display: none;">
                            <label for="monthly_hours">Monthly Hours</label>
                            <input type="number" id="monthly_hours" name="monthly_hours" min="1" max="200">
                        </div>
                        
                        <div class="form-group" id="daily_hours_group" style="display: none;">
                            <label for="daily_hours">Daily Hours</label>
                            <input type="number" id="daily_hours" name="daily_hours" min="1" max="12">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_teacher" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Teachers List -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-list-ul"></i>
                    <h3>All Teachers (<?php echo count($teachers); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($teachers)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h3>No Teachers Found</h3>
                            <p>Add your first teacher above to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Employment</th>
                                        <th>Workload</th>
                                        <th>Subjects</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo $teacher['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($teacher['username']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($teacher['email']); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($teacher['department_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $teacher['employment_type'] == 'full_time' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $teacher['employment_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($teacher['employment_type'] == 'full_time'): ?>
                                                    <span class="badge badge-primary"><?php echo $teacher['monthly_hours']; ?> hrs/month</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo $teacher['daily_hours']; ?> hrs/day</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo $teacher['subject_count']; ?> subject(s)
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="changePassword(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['username']); ?>')">
                                                        <i class="fas fa-key"></i> Password
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>')">
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

    <!-- Edit Teacher Modal -->
    <div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editModalTitle">Edit Teacher</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_teacher_id" name="teacher_id">
                    
                    <div class="form-group">
                        <label for="edit_username">Username <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_username" name="username" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_first_name">First Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_first_name" name="first_name" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">Last Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_last_name" name="last_name" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_department_id">Department <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_department_id" name="department_id" required aria-required="true">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_employment_type">Employment Type <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_employment_type" name="employment_type" required onchange="toggleEditWorkloadFields()" aria-required="true">
                            <option value="">Select Type</option>
                            <option value="full_time">Full-time</option>
                            <option value="part_time">Part-time</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="edit_monthly_hours_group" style="display: none;">
                        <label for="edit_monthly_hours">Monthly Hours</label>
                        <input type="number" id="edit_monthly_hours" name="monthly_hours" min="1" max="200">
                    </div>
                    
                    <div class="form-group" id="edit_daily_hours_group" style="display: none;">
                        <label for="edit_daily_hours">Daily Hours</label>
                        <input type="number" id="edit_daily_hours" name="daily_hours" min="1" max="12">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="edit_teacher" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="passwordModalTitle">Change Password</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <form method="POST" id="passwordForm">
                <div class="modal-body">
                    <input type="hidden" id="password_teacher_id" name="teacher_id">
                    
                    <div class="form-group">
                        <label for="new_password">New Password <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="password" id="new_password" name="new_password" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required aria-required="true">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                </div>
            </form>
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
                <p>Are you sure you want to delete the teacher "<span id="delete_teacher_name"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and may affect related schedules and subject assignments.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_teacher_id" name="teacher_id">
                    <button type="submit" name="delete_teacher" class="btn btn-danger">Delete Teacher</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Toggle workload fields based on employment type
        function toggleWorkloadFields() {
            const employmentType = document.getElementById('employment_type').value;
            const monthlyGroup = document.getElementById('monthly_hours_group');
            const dailyGroup = document.getElementById('daily_hours_group');
            
            if (employmentType === 'full_time') {
                monthlyGroup.style.display = 'block';
                dailyGroup.style.display = 'none';
            } else if (employmentType === 'part_time') {
                monthlyGroup.style.display = 'none';
                dailyGroup.style.display = 'block';
            } else {
                monthlyGroup.style.display = 'none';
                dailyGroup.style.display = 'none';
            }
        }

        // Toggle workload fields in edit modal
        function toggleEditWorkloadFields() {
            const employmentType = document.getElementById('edit_employment_type').value;
            const monthlyGroup = document.getElementById('edit_monthly_hours_group');
            const dailyGroup = document.getElementById('edit_daily_hours_group');
            
            if (employmentType === 'full_time') {
                monthlyGroup.style.display = 'block';
                dailyGroup.style.display = 'none';
            } else if (employmentType === 'part_time') {
                monthlyGroup.style.display = 'none';
                dailyGroup.style.display = 'block';
            } else {
                monthlyGroup.style.display = 'none';
                dailyGroup.style.display = 'none';
            }
        }

        // Edit Teacher Function
        function editTeacher(teacher) {
            document.getElementById('edit_teacher_id').value = teacher.id;
            document.getElementById('edit_username').value = teacher.username;
            document.getElementById('edit_first_name').value = teacher.first_name;
            document.getElementById('edit_last_name').value = teacher.last_name;
            document.getElementById('edit_email').value = teacher.email || '';
            document.getElementById('edit_department_id').value = teacher.department_id;
            document.getElementById('edit_employment_type').value = teacher.employment_type;
            document.getElementById('edit_monthly_hours').value = teacher.monthly_hours || '';
            document.getElementById('edit_daily_hours').value = teacher.daily_hours || '';
            
            // Toggle workload fields
            toggleEditWorkloadFields();
            
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_username').focus(); // Focus on first field for accessibility
        }

        // Change Password Function
        function changePassword(id, username) {
            document.getElementById('password_teacher_id').value = id;
            document.getElementById('passwordModal').style.display = 'flex';
        }

        // Delete Teacher Function
        function deleteTeacher(id, name) {
            document.getElementById('delete_teacher_id').value = id;
            document.getElementById('delete_teacher_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // Close Modal Function
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const passwordModal = document.getElementById('passwordModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === passwordModal) {
                passwordModal.style.display = 'none';
            }
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