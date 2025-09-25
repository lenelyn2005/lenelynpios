<?php
// teacher_room_schedule.php - Detailed Room Schedule View
session_start();
require 'config.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login_teacher.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$room_id = $_GET['room_id'] ?? null;
$success = '';
$error = '';

// Fetch selected room
$selectedRoom = null;
if ($room_id) {
    $selectedRoom = $mysqli->query("SELECT * FROM rooms WHERE id = $room_id")->fetch_assoc();
    if (!$selectedRoom) {
        $error = "Room not found.";
    }
} else {
    $error = "No room selected.";
}

// Fetch students grouped by section
$studentsBySection = [];
$studentsResult = $mysqli->query("
    SELECT st.first_name, st.last_name, sec.id as section_id
    FROM students st
    LEFT JOIN sections sec ON st.section_id = sec.id
");
if ($studentsResult) {
    while ($student = $studentsResult->fetch_assoc()) {
        $studentsBySection[$student['section_id']][] = $student['first_name'] . ' ' . $student['last_name'];
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$timeSlots = [
    ['08:00:00', '09:00:00'],
    ['09:00:00', '10:00:00'],
    ['10:00:00', '11:00:00'],
    ['11:00:00', '12:00:00'],
    ['13:00:00', '14:00:00'],
    ['14:00:00', '15:00:00'],
    ['15:00:00', '16:00:00'],
    ['16:00:00', '17:00:00']
];

// Precompute detailed schedules for the selected room
$roomSchedules = [];
if ($selectedRoom) {
    foreach ($days as $day) {
        $roomSchedules[$day] = [];
        
        // Fetch all schedules for this room and day
        $schedulesResult = $mysqli->query("
            SELECT s.start_time, s.end_time, sub.name as subject_name, 
                   sec.name as section_name, sec.id as section_id, t.first_name, t.last_name
            FROM schedules s
            LEFT JOIN subjects sub ON s.subject_id = sub.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            LEFT JOIN teachers t ON s.teacher_id = t.id
            WHERE s.room_id = $room_id
            AND s.day_of_week = '$day'
            ORDER BY s.start_time
        ");
        
        $schedules = [];
        if ($schedulesResult) {
            while ($schedule = $schedulesResult->fetch_assoc()) {
                $schedule['students'] = $studentsBySection[$schedule['section_id']] ?? [];
                $schedules[] = $schedule;
            }
        }
        
        // Build availability per slot
        $roomSchedules[$day]['slots'] = [];
        foreach ($timeSlots as $slot) {
            $slotStart = $slot[0];
            $slotEnd = $slot[1];
            $isAvailable = true;
            $occupying = null;
            
            foreach ($schedules as $schedule) {
                if (
                    ($schedule['start_time'] <= $slotStart && $schedule['end_time'] > $slotStart) ||
                    ($schedule['start_time'] < $slotEnd && $schedule['end_time'] >= $slotEnd) ||
                    ($schedule['start_time'] >= $slotStart && $schedule['end_time'] <= $slotEnd)
                ) {
                    $isAvailable = false;
                    $occupying = $schedule;
                    break;
                }
            }
            
            $roomSchedules[$day]['slots'][] = [
                'time' => substr($slotStart, 0, 5) . ' - ' . substr($slotEnd, 0, 5),
                'available' => $isAvailable,
                'occupying' => $occupying
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $selectedRoom ? htmlspecialchars($selectedRoom['name']) . ' - Schedule' : 'Room Schedule'; ?> - Teacher Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="includes/sidebar.css">
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
    .dashboard-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; color: #333; }
    .schedule-container { overflow-x: auto; }
    .day-section { margin-bottom: 30px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #fafafa; }
    .day-section h4 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    .schedule-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .schedule-table th, .schedule-table td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
    .schedule-table th { background: #007bff; color: white; }
    .schedule-table tr.available { background: #d4edda; }
    .schedule-table tr.occupied { background: #f8d7da; }
    .schedule-table tr.available:hover { background: #c3e6cb; }
    .schedule-table tr.occupied:hover { background: #f5c6cb; }
    .badge { padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; color: #fff; }
    .badge-success { background: #28a745; }
    .badge-danger { background: #dc3545; }
    .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    @media (max-width: 768px) { .schedule-table th, .schedule-table td { font-size: 0.9rem; } }
  </style>
</head>
<body>
  <?php renderSidebar('teacher', 'rooms'); ?>

  <div class="main-content">
    <div class="top-navbar">
      <div class="navbar-left">
        <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
        <h1 class="page-title">Room Schedule</h1>
      </div>
      <div class="navbar-right">
        <div class="breadcrumb">
          <a href="teacher_dashboard.php">Dashboard</a>
          <span class="breadcrumb-separator">/</span>
          <a href="teacher_rooms.php">Rooms</a>
          <span class="breadcrumb-separator">/</span>
          <span>Schedule</span>
        </div>
      </div>
    </div>

    <div class="content-area">
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <a href="teacher_rooms.php" class="btn btn-primary">Back to Rooms</a>
      <?php elseif ($selectedRoom): ?>
        <div class="dashboard-card">
          <div class="card-header">
            <i class="fas fa-calendar-alt"></i>
            <h3><?php echo htmlspecialchars($selectedRoom['name']); ?> - Weekly Schedule</h3>
            <a href="teacher_rooms.php" style="color: #666; font-size: 0.9em;">Back to Rooms</a>
          </div>
          <div class="schedule-container">
            <?php foreach ($days as $day): ?>
              <div class="day-section">
                <h4><?php echo $day; ?></h4>
                <table class="schedule-table">
                  <thead>
                    <tr>
                      <th>Time Slot</th>
                      <th>Status</th>
                      <th>Details</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      $slots = $roomSchedules[$day]['slots'] ?? [];
                      foreach ($slots as $slot): 
                    ?>
                      <tr class="<?php echo $slot['available'] ? 'available' : 'occupied'; ?>">
                        <td><?php echo $slot['time']; ?></td>
                        <td>
                          <span class="badge badge-<?php echo $slot['available'] ? 'success' : 'danger'; ?>">
                            <?php echo $slot['available'] ? 'Available' : 'Occupied'; ?>
                          </span>
                        </td>
                        <td>
                          <?php if (!$slot['available'] && $slot['occupying']): ?>
                            <strong><?php echo htmlspecialchars($slot['occupying']['subject_name']); ?></strong><br>
                            Section: <?php echo htmlspecialchars($slot['occupying']['section_name']); ?><br>
                            Teacher: <?php echo htmlspecialchars($slot['occupying']['first_name'] . ' ' . $slot['occupying']['last_name']); ?><br>
                            <?php echo substr($slot['occupying']['start_time'], 0, 5) . ' - ' . substr($slot['occupying']['end_time'], 0, 5); ?><br>
                            <?php if (!empty($slot['occupying']['students'])): ?>
                              Students (<?php echo count($slot['occupying']['students']); ?>): 
                              <?php echo implode(', ', array_slice(array_map('htmlspecialchars', $slot['occupying']['students']), 0, 5)); ?>
                              <?php if (count($slot['occupying']['students']) > 5): ?> ... and <?php echo count($slot['occupying']['students']) - 5; ?> more<?php endif; ?>
                            <?php else: ?>
                              No students enrolled
                            <?php endif; ?>
                          <?php else: ?>
                            Free
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="includes/sidebar.js"></script>
</body>
</html>
