<?php
// includes/sidebar.php - Responsive Sidebar Component

function renderSidebar($userType, $activePage = '') {
    $menuItems = getMenuItems($userType);
    ?>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i>
                <span class="logo-text">College Scheduler</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <?php foreach ($menuItems as $item): ?>
                    <li class="nav-item <?php echo ($activePage === $item['page']) ? 'active' : ''; ?>">
                        <a href="<?php echo $item['url']; ?>" class="nav-link">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span class="nav-text"><?php echo $item['text']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo getUserName($userType); ?></span>
                    <span class="user-role"><?php echo ucfirst($userType); ?></span>
                </div>
            </div>
            <a href="log_out.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php
}

function getMenuItems($userType) {
    switch ($userType) {
        case 'admin':
            return [
                ['page' => 'dashboard', 'text' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => 'admin_dashboard.php'],
                ['page' => 'departments', 'text' => 'Departments', 'icon' => 'fas fa-building', 'url' => 'admin_departments.php'],
                ['page' => 'courses', 'text' => 'Courses', 'icon' => 'fas fa-graduation-cap', 'url' => 'admin_courses.php'],
                ['page' => 'sections', 'text' => 'Year Levels & Sections', 'icon' => 'fas fa-users', 'url' => 'admin_sections.php'],
                ['page' => 'subjects', 'text' => 'Subjects', 'icon' => 'fas fa-book', 'url' => 'admin_subjects.php'],
                ['page' => 'teachers', 'text' => 'Teachers', 'icon' => 'fas fa-chalkboard-teacher', 'url' => 'admin_teachers.php'],
                ['page' => 'assignments', 'text' => 'Teacher Assignments', 'icon' => 'fas fa-user-plus', 'url' => 'admin_teacher_assignments.php'],
                ['page' => 'students', 'text' => 'Students', 'icon' => 'fas fa-user-graduate', 'url' => 'admin_students.php'],
                ['page' => 'rooms', 'text' => 'Rooms', 'icon' => 'fas fa-door-open', 'url' => 'admin_rooms.php'],
                ['page' => 'schedules', 'text' => 'Schedules', 'icon' => 'fas fa-calendar-alt', 'url' => 'admin_schedules.php'],
                ['page' => 'reports', 'text' => 'Reports & Analytics', 'icon' => 'fas fa-chart-bar', 'url' => 'admin_reports.php'],
                ['page' => 'settings', 'text' => 'Settings', 'icon' => 'fas fa-cog', 'url' => 'admin_settings.php']
            ];
            
        case 'teacher':
            return [
                ['page' => 'schedule', 'text' => 'My Schedule', 'icon' => 'fas fa-calendar-week', 'url' => 'teacher_dashboard.php'],
                ['page' => 'rooms', 'text' => 'Room Availability', 'icon' => 'fas fa-door-open', 'url' => 'teacher_rooms.php'],
                ['page' => 'profile', 'text' => 'My Profile', 'icon' => 'fas fa-user', 'url' => 'teacher_profile.php'],
                ['page' => 'notifications', 'text' => 'Notifications', 'icon' => 'fas fa-bell', 'url' => 'teacher_notifications.php']
            ];
            
        case 'student':
            return [
                ['page' => 'info', 'text' => 'My Info', 'icon' => 'fas fa-user', 'url' => 'student_dashboard.php'],
                ['page' => 'schedule', 'text' => 'My Schedule', 'icon' => 'fas fa-calendar-week', 'url' => 'student_schedule.php'],
                ['page' => 'rooms', 'text' => 'Room Lookup', 'icon' => 'fas fa-search', 'url' => 'student_rooms.php']
            ];
            
        default:
            return [];
    }
}

function getUserName($userType) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    switch ($userType) {
        case 'admin':
            return 'Administrator';
        case 'teacher':
            if (isset($_SESSION['teacher_id'])) {
                global $mysqli;
                $stmt = $mysqli->prepare("SELECT first_name, last_name FROM teachers WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['teacher_id']);
                $stmt->execute();
                $stmt->bind_result($first_name, $last_name);
                if ($stmt->fetch()) {
                    return $first_name . ' ' . $last_name;
                }
            }
            return 'Teacher';
        case 'student':
            if (isset($_SESSION['student_id'])) {
                global $mysqli;
                $stmt = $mysqli->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
                $stmt->bind_param("s", $_SESSION['student_id']);
                $stmt->execute();
                $stmt->bind_result($first_name, $last_name);
                if ($stmt->fetch()) {
                    return $first_name . ' ' . $last_name;
                }
            }
            return 'Student';
        default:
            return 'User';
    }
}
?>
