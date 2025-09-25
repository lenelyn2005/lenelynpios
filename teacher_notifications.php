<?php
// teacher_notifications.php - Teacher Notifications Center
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

// Get teacher information (fixed raw query to prepared)
$stmt = $mysqli->prepare("
    SELECT t.*, d.name as department_name
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.id
    WHERE t.id = ?
");
if (!$stmt) {
    $error = "Database error: " . $mysqli->error;
    $teacher = [];
} else {
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc() ?: [];
    $stmt->close();
}

// Handle notification actions
if (isset($_POST['mark_read'])) {
    $notification_id = (int) $_POST['notification_id'];
    $stmt = $mysqli->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND teacher_id = ?");
    if ($stmt && $stmt->execute()) {
        $success = "Notification marked as read!";
    } else {
        $error = "Error marking notification: " . $mysqli->error;
    }
    if (isset($stmt)) $stmt->close();
}

if (isset($_POST['mark_all_read'])) {
    $stmt = $mysqli->prepare("UPDATE notifications SET is_read = 1 WHERE teacher_id = ?");
    if ($stmt && $stmt->execute()) {
        $success = "All notifications marked as read!";
    } else {
        $error = "Error marking all notifications: " . $mysqli->error;
    }
    if (isset($stmt)) $stmt->close();
}

if (isset($_POST['delete_notification'])) {
    $notification_id = (int) $_POST['notification_id'];
    $stmt = $mysqli->prepare("DELETE FROM notifications WHERE id = ? AND teacher_id = ?");
    if ($stmt && $stmt->execute()) {
        $success = "Notification deleted!";
    } else {
        $error = "Error deleting notification: " . $mysqli->error;
    }
    if (isset($stmt)) $stmt->close();
}

// Get notifications (prepared)
$notifications_result = $mysqli->prepare("
    SELECT * FROM notifications
    WHERE teacher_id = ?
    ORDER BY created_at DESC
");
if ($notifications_result) {
    $notifications_result->bind_param("i", $teacher_id);
    $notifications_result->execute();
    $notifications = $notifications_result->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $notifications_result->close();
} else {
    $notifications = [];
    $error = "Database error fetching notifications: " . $mysqli->error;
}

// Get unread count (prepared)
$unread_result = $mysqli->prepare("
    SELECT COUNT(*) as count FROM notifications
    WHERE teacher_id = ? AND is_read = 0
");
if ($unread_result) {
    $unread_result->bind_param("i", $teacher_id);
    $unread_result->execute();
    $unreadCount = $unread_result->get_result()->fetch_assoc()['count'] ?? 0;
    $unread_result->close();
} else {
    $unreadCount = 0;
    $error = "Database error fetching unread count: " . $mysqli->error;
}

// Get schedule conflicts for this teacher (prepared)
$conflicts_result = $mysqli->prepare("
    SELECT sc.*, s.day_of_week, s.start_time, s.end_time,
           sub.name as subject_name, sec.name as section_name
    FROM schedule_conflicts sc
    LEFT JOIN schedules s ON sc.entity_id = s.teacher_id AND s.teacher_id = ?
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE sc.conflict_type = 'teacher' AND sc.entity_id = ?
    ORDER BY sc.created_at DESC
");
if ($conflicts_result) {
    $conflicts_result->bind_param("ii", $teacher_id, $teacher_id);
    $conflicts_result->execute();
    $scheduleConflicts = $conflicts_result->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $conflicts_result->close();
} else {
    $scheduleConflicts = [];
    $error = "Database error fetching conflicts: " . $mysqli->error;
}

// Get recent schedule changes (prepared)
$changes_result = $mysqli->prepare("
    SELECT s.*, sub.name as subject_name, sec.name as section_name,
           r.name as room_name, s.created_at
    FROM schedules s
    LEFT JOIN subjects sub ON s.subject_id = sub.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE s.teacher_id = ?
    ORDER BY s.created_at DESC
    LIMIT 10
");
if ($changes_result) {
    $changes_result->bind_param("i", $teacher_id);
    $changes_result->execute();
    $recentChanges = $changes_result->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $changes_result->close();
} else {
    $recentChanges = [];
    $error = "Database error fetching recent changes: " . $mysqli->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - Teacher Dashboard</title>

  <!-- Local CSS -->
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="includes/sidebar.css">

  <!-- Local FontAwesome (offline) -->
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
</head>
<body>
  <?php renderSidebar('teacher', 'notifications'); ?>

  <div class="main-content">
    <!-- Top Navbar -->
    <div class="top-navbar">
      <div class="navbar-left">
        <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
        <h1 class="page-title">Notifications</h1>
      </div>
      <div class="navbar-right">
        <div class="breadcrumb">
          <a href="teacher_dashboard.php">Dashboard</a>
          <span class="breadcrumb-separator">/</span>
          <span>Notifications</span>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>

      <div class="dashboard-grid">
        <!-- Notification Summary -->
        <div class="dashboard-card">
          <div class="card-header">
            <i class="fas fa-bell"></i>
            <h3>Notification Summary</h3>
          </div>
          <div class="form-container">
            <div class="stats-grid">
              <div class="stat-item">
                <i class="fas fa-envelope"></i>
                <span class="stat-number"><?php echo count($notifications); ?></span>
                <span class="stat-label">Total</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-envelope-open"></i>
                <span class="stat-number"><?php echo $unreadCount; ?></span>
                <span class="stat-label">Unread</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="stat-number"><?php echo count($scheduleConflicts); ?></span>
                <span class="stat-label">Conflicts</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-calendar-alt"></i>
                <span class="stat-number"><?php echo count($recentChanges); ?></span>
                <span class="stat-label">Recent Changes</span>
              </div>
            </div>

            <?php if ($unreadCount > 0): ?>
              <form method="POST" style="margin-top: 20px;">
                <button type="submit" name="mark_all_read" class="btn btn-primary">
                  <i class="fas fa-check-double"></i> Mark All as Read
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="dashboard-card">
          <div class="card-header">
            <i class="fas fa-list"></i>
            <h3>Recent Notifications</h3>
          </div>
          <div class="form-container">
            <?php if (empty($notifications)): ?>
              <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h4>No Notifications</h4>
                <p>You have no notifications at this time.</p>
              </div>
            <?php else: ?>
              <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                  <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-header">
                      <div class="notification-icon">
                        <i class="fas fa-<?php echo $notification['type'] == 'info' ? 'info-circle' : ($notification['type'] == 'warning' ? 'exclamation-triangle' : 'bell'); ?>"></i>
                      </div>
                      <div class="notification-content">
                        <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                      </div>
                      <div class="notification-actions">
                        <?php if (!$notification['is_read']): ?>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" name="mark_read" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></button>
                          </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                          <button type="submit" name="delete_notification" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Conflicts -->
        <div class="dashboard-card">
          <div class="card-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Schedule Conflicts</h3>
          </div>
          <div class="form-container">
            <?php if (empty($scheduleConflicts)): ?>
              <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h4>No Conflicts</h4>
                <p>Your schedule has no conflicts.</p>
              </div>
            <?php else: ?>
              <div class="conflicts-list">
                <?php foreach ($scheduleConflicts as $conflict): ?>
                  <div class="conflict-item">
                    <div class="conflict-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="conflict-content">
                      <h4><?php echo htmlspecialchars($conflict['description']); ?></h4>
                      <p><strong>Subject:</strong> <?php echo htmlspecialchars($conflict['subject_name']); ?></p>
                      <p><strong>Section:</strong> <?php echo htmlspecialchars($conflict['section_name']); ?></p>
                      <p><strong>Time:</strong> <?php echo $conflict['day_of_week'] . ' ' . date('g:i A', strtotime($conflict['start_time'])) . ' - ' . date('g:i A', strtotime($conflict['end_time'])); ?></p>
                      <small><?php echo date('M d, Y H:i', strtotime($conflict['created_at'])); ?></small>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Schedule Changes -->
        <div class="dashboard-card">
          <div class="card-header">
            <i class="fas fa-history"></i>
            <h3>Recent Schedule Changes</h3>
          </div>
          <div class="form-container">
            <?php if (empty($recentChanges)): ?>
              <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h4>No Recent Changes</h4>
                <p>No recent changes to your schedule.</p>
              </div>
            <?php else: ?>
              <div class="changes-list">
                <?php foreach ($recentChanges as $change): ?>
                  <div class="change-item">
                    <div class="change-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="change-content">
                      <h4><?php echo htmlspecialchars($change['subject_name']); ?></h4>
                      <p><strong>Section:</strong> <?php echo htmlspecialchars($change['section_name']); ?></p>
                      <p><strong>Room:</strong> <?php echo htmlspecialchars($change['room_name']); ?></p>
                      <p><strong>Time:</strong> <?php echo $change['day_of_week'] . ' ' . date('g:i A', strtotime($change['start_time'])) . ' - ' . date('g:i A', strtotime($change['end_time'])); ?></p>
                      <small><?php echo date('M d, Y H:i', strtotime($change['created_at'])); ?></small>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Local Sidebar Script -->
  <script src="includes/sidebar.js"></script>

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      margin: 0;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .dashboard-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
      color: #333;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 15px;
    }

    .stat-item {
      text-align: center;
      padding: 15px;
      border-radius: 8px;
      background: #f9fbfd;
      border: 1px solid #e0e0e0;
    }

    .stat-item i { font-size: 1.8rem; margin-bottom: 8px; color: #007bff; }

    .stat-number { font-size: 1.4rem; font-weight: bold; color: #333; }
    .stat-label { font-size: 0.85rem; color: #777; }

    .notifications-list, .conflicts-list, .changes-list {
      max-height: 380px;
      overflow-y: auto;
      padding-right: 5px;
    }

    .notification-item, .conflict-item, .change-item {
      padding: 12px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 10px;
      background: #fff;
    }

    .notification-item.unread { background: #e8f3ff; border-left: 4px solid #2196f3; }

    .notification-header {
      display: flex;
      gap: 15px;
    }

    .notification-icon, .conflict-icon, .change-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f1f3f5;
    }

    .notification-actions button {
      border: none;
      padding: 6px 8px;
      border-radius: 6px;
      cursor: pointer;
    }

    .btn-primary { background: #007bff; color: white; }
    .btn-danger { background: #dc3545; color: white; }

    .btn-sm { font-size: 0.8rem; }

    .empty-state {
      text-align: center;
      padding: 25px;
      color: #777;
    }

    .empty-state i { font-size: 2.5rem; margin-bottom: 10px; color: #bbb; }
  </style>
</body>
</html>
