<?php
// admin_settings.php - Admin Settings and Configuration
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
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if (!empty($username)) {
            // Check if username already exists (excluding current admin)
            $check_stmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $check_stmt->bind_param("si", $username, $_SESSION['admin_id']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                $stmt = $mysqli->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $email, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Error updating profile: " . $mysqli->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $error = "Username cannot be empty.";
        }
    }
    
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!empty($current_password) && !empty($new_password) && $new_password === $confirm_password) {
            // Verify current password
            $stmt = $mysqli->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            $stmt->close();
            
            if (password_verify($current_password, $hashed_password)) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_hashed_password, $_SESSION['admin_id']);
                if ($update_stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . $mysqli->error;
                }
                $update_stmt->close();
            } else {
                $error = "Current password is incorrect.";
            }
        } else {
            $error = "Please fill in all fields and ensure passwords match.";
        }
    }
    
    elseif (isset($_POST['add_year_level'])) {
        $name = trim($_POST['year_level_name']);
        $description = trim($_POST['year_level_description']);
        
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO year_levels (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $success = "Year level added successfully!";
            } else {
                $error = "Error adding year level: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Year level name cannot be empty.";
        }
    }
    
    elseif (isset($_POST['delete_year_level'])) {
        $id = $_POST['year_level_id'];
        $stmt = $mysqli->prepare("DELETE FROM year_levels WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Year level deleted successfully!";
        } else {
            $error = "Error deleting year level: " . $mysqli->error;
        }
        $stmt->close();
    }
    
    elseif (isset($_POST['backup_database'])) {
        // Simple database backup (in production, use proper backup tools)
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = "mysqldump --user=" . DB_USERNAME . " --password=" . DB_PASSWORD . " --host=" . DB_SERVER . " " . DB_NAME . " > " . $backup_file;
        
        if (system($command) !== false) {
            $success = "Database backup created successfully: " . $backup_file;
        } else {
            $error = "Error creating database backup.";
        }
    }
}

// Get current admin information
$admin_info = $mysqli->query("SELECT * FROM admins WHERE id = " . $_SESSION['admin_id'])->fetch_assoc();

// Get year levels
$year_levels = $mysqli->query("SELECT * FROM year_levels ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $mysqli->server_info,
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Get database statistics
$db_stats = $mysqli->query("
    SELECT 
        (SELECT COUNT(*) FROM departments) as departments,
        (SELECT COUNT(*) FROM courses) as courses,
        (SELECT COUNT(*) FROM year_levels) as year_levels,
        (SELECT COUNT(*) FROM sections) as sections,
        (SELECT COUNT(*) FROM subjects) as subjects,
        (SELECT COUNT(*) FROM teachers) as teachers,
        (SELECT COUNT(*) FROM students) as students,
        (SELECT COUNT(*) FROM rooms) as rooms,
        (SELECT COUNT(*) FROM schedules) as schedules
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard (Offline Version)</title>
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
        input, textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus {
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
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .info-item label {
            font-weight: bold;
            color: #333;
        }
        .info-item span {
            color: #666;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
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
        @media (max-width: 768px) {
            .form-grid, .info-grid, .stats-grid {
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
    <?php renderSidebar('admin', 'settings'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Settings</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Settings</span>
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

            <!-- Profile Settings -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user-cog"></i>
                    <h3>Profile Settings</h3>
                </div>
                <div class="card-content">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="username">Username <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin_info['username']); ?>" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_info['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-key"></i>
                    <h3>Change Password</h3>
                </div>
                <div class="card-content">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="current_password">Current Password <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="password" id="current_password" name="current_password" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="password" id="new_password" name="new_password" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required aria-required="true">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Year Levels Management -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-layer-group"></i>
                    <h3>Year Levels Management</h3>
                </div>
                <div class="card-content">
                    <!-- Add Year Level Form -->
                    <form method="POST" class="form-grid" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="year_level_name">Year Level Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="year_level_name" name="year_level_name" placeholder="e.g., 1st Year, 2nd Year" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="year_level_description">Description</label>
                            <input type="text" id="year_level_description" name="year_level_description" placeholder="Optional description">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_year_level" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Year Level
                            </button>
                        </div>
                    </form>
                    
                    <!-- Year Levels List -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($year_levels as $level): ?>
                                    <tr>
                                        <td><?php echo $level['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($level['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($level['description'] ?? ''); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-danger" onclick="deleteYearLevel(<?php echo $level['id']; ?>, '<?php echo htmlspecialchars($level['name']); ?>')">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>System Information</h3>
                </div>
                <div class="card-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>PHP Version:</label>
                            <span><?php echo $system_info['php_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>MySQL Version:</label>
                            <span><?php echo $system_info['mysql_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Server Time:</label>
                            <span><?php echo $system_info['server_time']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Timezone:</label>
                            <span><?php echo $system_info['timezone']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Memory Limit:</label>
                            <span><?php echo $system_info['memory_limit']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Max Execution Time:</label>
                            <span><?php echo $system_info['max_execution_time']; ?> seconds</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Statistics -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-database"></i>
                    <h3>Database Statistics</h3>
                </div>
                <div class="card-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['departments']; ?></span>
                            <span class="stat-label">Departments</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['courses']; ?></span>
                            <span class="stat-label">Courses</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['year_levels']; ?></span>
                            <span class="stat-label">Year Levels</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['sections']; ?></span>
                            <span class="stat-label">Sections</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['subjects']; ?></span>
                            <span class="stat-label">Subjects</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['teachers']; ?></span>
                            <span class="stat-label">Teachers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['students']; ?></span>
                            <span class="stat-label">Students</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['rooms']; ?></span>
                            <span class="stat-label">Rooms</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $db_stats['schedules']; ?></span>
                            <span class="stat-label">Schedules</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Backup -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-download"></i>
                    <h3>Database Backup</h3>
                </div>
                <div class="card-content">
                    <p>Create a backup of the entire database. This will generate a SQL dump file.</p>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="backup_database" class="btn btn-info" onclick="return confirm('This will create a database backup. Continue?')">
                            <i class="fas fa-download"></i> Create Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Year Level Confirmation Modal -->
    <div id="deleteModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="deleteModalTitle">Confirm Delete</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the year level "<span id="delete_year_level_name"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and may affect related sections and students.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_year_level_id" name="year_level_id">
                    <button type="submit" name="delete_year_level" class="btn btn-danger">Delete Year Level</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Enhanced JavaScript for better usability: Smooth modals, keyboard accessibility
        function deleteYearLevel(id, name) {
            document.getElementById('delete_year_level_id').value = id;
            document.getElementById('delete_year_level_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
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
