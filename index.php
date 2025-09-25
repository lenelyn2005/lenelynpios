<?php
session_start();

// Check for logout success message
$showLogoutMessage = isset($_GET['logout']) && $_GET['logout'] === 'success';

// Redirect users who are already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
} elseif (isset($_SESSION['teacher_id'])) {
    header("Location: teacher_dashboard.php");
    exit;
} elseif (isset($_SESSION['student_id'])) {
    header("Location: student_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USAT College Scheduling System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Reset & Base */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body {
            background-image: url('picture/hi.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 62, 80, 0.6); /* Semi-transparent overlay for better text readability */
            z-index: -1;
        }
        a { text-decoration: none; }

        .container { width: 100%; max-width: 1200px; padding: 20px; position: relative; z-index: 1; }

        /* Header */
        .main-header { text-align: center; margin-bottom: 40px; }
        .main-header .logo { display: flex; flex-direction: column; align-items: center; }
        .main-header i { font-size: 60px; color: #f1c40f; margin-bottom: 10px; }
        .main-header h1 { font-size: 2.5rem; }

        /* Logout Success Message */
        .logout-message {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: fadeInOut 3s ease-in-out;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-20px); }
            20%, 80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-20px); }
        }

        .logout-message i { margin-right: 10px; }

        /* Login Options */
        .login-options { display: flex; justify-content: center; }
        .login-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; width: 100%; }

        .login-card {
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent for background blend */
            color: #333;
            padding: 25px; border-radius: 15px;
            text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        .login-card .card-icon i {
            font-size: 50px; margin-bottom: 15px; color: #2c3e50;
        }

        .login-card h3 { margin-bottom: 10px; color: #2c3e50; }
        .login-card p { font-size: 14px; color: #555; margin-bottom: 20px; }

        /* Buttons */
        .btn {
            display: inline-block; padding: 10px 20px;
            border-radius: 30px; font-weight: bold; transition: 0.3s ease;
        }
        .btn i { margin-right: 8px; }
        .btn-primary { background: #2980b9; color: #fff; }
        .btn-primary:hover { background: #1f6391; }
        .btn-secondary { background: #27ae60; color: #fff; }
        .btn-secondary:hover { background: #1e874b; }
        .btn-success { background: #8e44ad; color: #fff; }
        .btn-success:hover { background: #732d91; }

        /* Features */
        .features-section { margin-top: 50px; text-align: center; }
        .features-section h2 { margin-bottom: 25px; font-size: 2rem; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }

        .feature-item {
            background: rgba(255,255,255,0.15); /* Adjusted for better visibility on image bg */
            padding: 20px; border-radius: 12px;
            transition: 0.3s; backdrop-filter: blur(8px); /* Enhanced blur for modern look */
        }
        .feature-item:hover { background: rgba(255,255,255,0.25); }
        .feature-item i { font-size: 40px; margin-bottom: 10px; color: #f39c12; }
        .feature-item h4 { margin-bottom: 10px; font-size: 18px; }

        /* Footer */
        .main-footer { text-align: center; margin-top: 40px; font-size: 14px; color: #eee; } /* Slightly brighter for readability */
    </style>
</head>
<body>
    <div class="container">
        <!-- Logout Success Message -->
        <?php if ($showLogoutMessage): ?>
            <div class="logout-message">
                <i class="fas fa-check-circle"></i>
                You have been successfully logged out. Thank you for using the system!
            </div>
        <?php endif; ?>

        <!-- Header -->
        <header class="main-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>USAT College Scheduling System</h1>
            </div>
        </header>

        <!-- Main content -->
        <main>
            <!-- Login options -->
            <section class="login-options">
                <div class="login-cards">
                    <!-- Admin Card -->
                    <div class="login-card admin-card">
                        <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                        <h3>Administrator</h3>
                        <p>Manage departments, teachers, subjects, and generate schedules</p>
                        <a href="login_admin.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Admin Login</a>
                    </div>

                    <!-- Teacher Card -->
                    <div class="login-card teacher-card">
                        <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h3>Teacher</h3>
                        <p>View schedules, check room availability, and manage classes</p>
                        <a href="login_teacher.php" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Teacher Login</a>
                    </div>

                    <!-- Student Card -->
                    <div class="login-card student-card">
                        <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>Student</h3>
                        <p>View personal information and class schedules</p>
                        <a href="login_student.php" class="btn btn-success"><i class="fas fa-sign-in-alt"></i> Student Login</a>
                    </div>
                </div>
            </section>

            <!-- Features section -->
            <section class="features-section">
                <h2>System Features</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-dna"></i>
                        <h4>Genetic Algorithm</h4>
                        <p>Advanced scheduling optimization with conflict resolution</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <h4>Multi-User Support</h4>
                        <p>Separate dashboards for admins, teachers, and students</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-calendar-alt"></i>
                        <h4>Smart Scheduling</h4>
                        <p>Automatic conflict detection and resolution</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-building"></i>
                        <h4>Room Management</h4>
                        <p>Track room availability and capacity</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="main-footer">
            <p>&copy; <?= date("Y"); ?> USAT College Scheduling System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
