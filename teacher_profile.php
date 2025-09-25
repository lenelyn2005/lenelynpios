<?php
// teacher_profile.php - Teacher Profile Management
session_start();
require 'config.php';
require_once 'includes/sidebar_new.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login_teacher.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$success = '';
$error = '';

// Get teacher information
$teacher = $mysqli->query("
    SELECT t.*, d.name as department_name
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.id
    WHERE t.id = $teacher_id
")->fetch_assoc();

if (!$teacher) {
    header("Location: login_teacher.php");
    exit;
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    if (!empty($first_name) && !empty($last_name) && !empty($email)) {
        $stmt = $mysqli->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $email, $teacher_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Refresh teacher data
            $teacher = $mysqli->query("
                SELECT t.*, d.name as department_name
                FROM teachers t
                LEFT JOIN departments d ON t.department_id = d.id
                WHERE t.id = $teacher_id
            ")->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            // Verify current password
            if (password_verify($current_password, $teacher['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $teacher_id);
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . $mysqli->error;
                }
                $stmt->close();
            } else {
                $error = "Current password is incorrect.";
            }
        }
    } else {
        $error = "Please fill in all password fields.";
    }
}

// Get teacher's assigned subjects
$assignedSubjects = $mysqli->query("
    SELECT s.*, ts.created_at as assigned_date
    FROM subjects s
    INNER JOIN teacher_subjects ts ON s.id = ts.subject_id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY s.name
")->fetch_all(MYSQLI_ASSOC);

// Get teacher's schedule statistics
$scheduleStats = $mysqli->query("
    SELECT
        COUNT(*) as total_classes,
        COUNT(DISTINCT day_of_week) as active_days,
        SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as total_hours
    FROM schedules
    WHERE teacher_id = $teacher_id
")->fetch_assoc();

// Get departments for dropdown
$departments = $mysqli->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Teacher Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php renderSidebar('teacher', 'profile'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">My Profile</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="teacher_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>My Profile</span>
                </div>
            </div>
        </div>

        <div class="content-area">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Profile Information -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <h3>Profile Information</h3>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($teacher['username']); ?>" readonly>
                            <small class="form-text">Username cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" value="<?php echo htmlspecialchars($teacher['department_name']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="employment_type">Employment Type</label>
                            <input type="text" id="employment_type" value="<?php echo ucfirst(str_replace('_', ' ', $teacher['employment_type'])); ?>" readonly>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-key"></i>
                    <h3>Change Password</h3>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Teaching Statistics -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Teaching Statistics</h3>
                </div>
                <div class="form-container">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <i class="fas fa-book"></i>
                            <span class="stat-number"><?php echo count($assignedSubjects); ?></span>
                            <span class="stat-label">Subjects</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-calendar"></i>
                            <span class="stat-number"><?php echo $scheduleStats['active_days'] ?? 0; ?></span>
                            <span class="stat-label">Active Days</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock"></i>
                            <span class="stat-number"><?php echo $scheduleStats['total_classes'] ?? 0; ?></span>
                            <span class="stat-label">Total Classes</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-hourglass"></i>
                            <span class="stat-number"><?php echo $scheduleStats['total_hours'] ?? 0; ?></span>
                            <span class="stat-label">Teaching Hours</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Subjects -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-book"></i>
                    <h3>Assigned Subjects</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Units</th>
                                <th>Hours/Week</th>
                                <th>Assigned Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedSubjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                    <td><?php echo $subject['units']; ?></td>
                                    <td><?php echo $subject['hours_per_week']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($subject['assigned_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <style>
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

        .form-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</body>
</html>
