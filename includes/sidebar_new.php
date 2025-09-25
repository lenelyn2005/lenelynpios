<?php
// includes/sidebar.php - Enhanced Responsive Sidebar Component with improved logout

function renderSidebar($userType, $activePage = '') {
    $menuItems = getMenuItems($userType);
    ?>
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu" aria-expanded="false">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon" style="background-image: url('picture/hi.png'); background-size: cover; background-position: center; width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; display: inline-block;"></div>
                <span class="logo-text">College Scheduler</span>
            </div>
            <button class="close-btn" id="closeSidebar" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Sidebar navigation">
            <ul class="nav-list" role="menu">
                <?php foreach ($menuItems as $item): ?>
                    <li class="nav-item <?php echo ($activePage === $item['page']) ? 'active' : ''; ?>" role="none">
                        <a href="<?php echo $item['url']; ?>" class="nav-link" role="menuitem"
                           <?php echo ($activePage === $item['page']) ? 'aria-current="page"' : ''; ?>>
                            <i class="<?php echo $item['icon']; ?>" aria-hidden="true"></i>
                            <span class="nav-text"><?php echo $item['text']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar" aria-hidden="true">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo getUserName($userType); ?></span>
                    <span class="user-role"><?php echo ucfirst($userType); ?></span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn" role="menuitem" onclick="return confirmLogout()">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <script>
    function confirmLogout() {
        return confirm('Are you sure you want to logout?');
    }
    </script>
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
                ['page' => 'info', 'text' => 'My Info', 'icon' => 'fas fa-user', 'url' => 'student_info.php'],
                ['page' => 'schedule', 'text' => 'My Schedule', 'icon' => 'fas fa-calendar-week', 'url' => 'student_schedule.php'],
                ['page' => 'rooms', 'text' => 'Room Lookup', 'icon' => 'fas fa-search', 'url' => 'student_rooms.php']
            ];

        default:
            return [];
    }
}

function getUserName($userType) {
    // Note: session_start() should be called at the top of the file using this function
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
                    $stmt->close();
                    return $first_name . ' ' . $last_name;
                }
                $stmt->close();
            }
            return 'Teacher';
        case 'student':
            if (isset($_SESSION['student_id'])) {
                global $mysqli;
                $stmt = $mysqli->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['student_id']);
                $stmt->execute();
                $stmt->bind_result($first_name, $last_name);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return $first_name . ' ' . $last_name;
                }
                $stmt->close();
            }
            return 'Student';
        default:
            return 'User';
    }
}
?>
