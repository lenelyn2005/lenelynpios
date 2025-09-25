<?php
// teacher_rooms.php - Teacher Room Availability Management
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

// Handle room availability check
if (isset($_POST['check_availability'])) {
    $room_id = $_POST['room_id'];
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $conflict = $mysqli->query("
        SELECT s.*, sub.name as subject_name, sec.name as section_name, t.first_name, t.last_name
        FROM schedules s
        LEFT JOIN subjects sub ON s.subject_id = sub.id
        LEFT JOIN sections sec ON s.section_id = sec.id
        LEFT JOIN teachers t ON s.teacher_id = t.id
        WHERE s.room_id = $room_id
        AND s.day_of_week = '$day'
        AND (
            (s.start_time <= '$start_time' AND s.end_time > '$start_time') OR
            (s.start_time < '$end_time' AND s.end_time >= '$end_time') OR
            (s.start_time >= '$start_time' AND s.end_time <= '$end_time')
        )
    ")->fetch_assoc();

    if ($conflict) {
        $error = "Room is occupied by " . $conflict['subject_name'] . " (" . $conflict['section_name'] . ") - " . $conflict['first_name'] . " " . $conflict['last_name'] . " from " . substr($conflict['start_time'], 0, 5) . " to " . substr($conflict['end_time'], 0, 5);
    } else {
        $success = "Room is available at the requested time!";
    }
}

// Get all rooms with detailed information - FIXED: Removed department join since rooms table doesn't have department_id
$rooms = $mysqli->query("
    SELECT r.*
    FROM rooms r
    ORDER BY r.name
")->fetch_all(MYSQLI_ASSOC);

// Fetch students grouped by section
$studentsBySection = [];
$studentsResult = $mysqli->query("
    SELECT st.id, st.first_name, st.last_name, sec.id as section_id
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

// Precompute detailed schedules for all rooms
$roomSchedules = [];
foreach ($rooms as $room) {
    $roomSchedules[$room['id']] = [];
    foreach ($days as $day) {
        $roomSchedules[$room['id']][$day] = [];
        
        // Fetch all schedules for this room and day
        $schedulesResult = $mysqli->query("
            SELECT s.start_time, s.end_time, sub.name as subject_name, 
                   sec.name as section_name, sec.id as section_id, t.first_name, t.last_name
            FROM schedules s
            LEFT JOIN subjects sub ON s.subject_id = sub.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            LEFT JOIN teachers t ON s.teacher_id = t.id
            WHERE s.room_id = {$room['id']}
            AND s.day_of_week = '$day'
            ORDER BY s.start_time
        ");
        
        if ($schedulesResult) {
            while ($schedule = $schedulesResult->fetch_assoc()) {
                $schedule['students'] = $studentsBySection[$schedule['section_id']] ?? [];
                $roomSchedules[$room['id']][$day][] = $schedule;
            }
        }
        
        $schedules = $roomSchedules[$room['id']][$day];
        
        // Build availability per slot
        $roomSchedules[$room['id']][$day]['slots'] = [];
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
                    break; // Take first overlapping
                }
            }
            
            $roomSchedules[$room['id']][$day]['slots'][] = [
                'time' => substr($slotStart, 0, 5) . ' - ' . substr($slotEnd, 0, 5),
                'available' => $isAvailable,
                'occupying' => $occupying
            ];
        }
    }
}
$roomAvailability = $roomSchedules; // Reuse variable for compatibility
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Room Availability - Teacher Dashboard</title>

  <!-- Local CSS -->
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="includes/sidebar.css">

  <!-- Local FontAwesome (offline) -->
  <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
</head>
<body>
  <?php renderSidebar('teacher', 'rooms'); ?>

  <div class="main-content">
    <!-- Navbar -->
    <div class="top-navbar">
      <div class="navbar-left">
        <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
        <h1 class="page-title">Room Availability</h1>
      </div>
      <div class="navbar-right">
        <div class="breadcrumb">
          <a href="teacher_dashboard.php">Dashboard</a>
          <span class="breadcrumb-separator">/</span>
          <span>Room Availability</span>
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

      <!-- Check Room Availability -->
      <div class="dashboard-card">
        <div class="card-header">
          <i class="fas fa-search"></i>
          <h3>Check Room Availability</h3>
        </div>
        <form method="POST" class="form-container">
          <div class="form-group">
            <label for="room_id">Room</label>
            <select id="room_id" name="room_id" class="form-control" required>
              <option value="">Select Room</option>
              <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room['id']; ?>">
                  <?php echo htmlspecialchars($room['name'] . ' (' . $room['room_type'] . ') - ' . $room['location']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="day">Day</label>
            <select id="day" name="day" class="form-control" required>
              <option value="">Select Day</option>
              <option>Monday</option>
              <option>Tuesday</option>
              <option>Wednesday</option>
              <option>Thursday</option>
              <option>Friday</option>
            </select>
          </div>
          <div class="form-group">
            <label for="start_time">Start Time</label>
            <input type="time" id="start_time" name="start_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="end_time">End Time</label>
            <input type="time" id="end_time" name="end_time" class="form-control" required>
          </div>
          <button type="submit" name="check_availability" class="btn btn-primary">
            <i class="fas fa-search"></i> Check Availability
          </button>
        </form>
      </div>

      <!-- Room Availability Overview -->
      <div class="dashboard-grid">
        <?php foreach ($rooms as $room): ?>
          <div class="dashboard-card">
            <div class="card-header">
              <i class="fas fa-door-open"></i>
              <h4><?php echo htmlspecialchars($room['name']); ?></h4>
            </div>
            <div class="room-info">
              <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $room['room_type'])); ?></p>
              <p><strong>Capacity:</strong> <?php echo $room['capacity']; ?> seats</p>
              <p><strong>Location:</strong> <?php echo htmlspecialchars($room['location']); ?></p>

              <h5>Weekly Availability Summary:</h5>
              <div class="availability-grid">
                <?php 
                  $totalAvailable = 0;
                  $totalSlots = 0;
                  foreach ($days as $day) {
                    $daySlots = $roomAvailability[$room['id']][$day]['slots'] ?? [];
                    $availableInDay = 0;
                    foreach ($daySlots as $slot) {
                      if ($slot['available']) $availableInDay++;
                    }
                    $totalAvailable += $availableInDay;
                    $totalSlots += count($daySlots);
                  }
                  $availabilityPercent = ($totalSlots > 0) ? round(($totalAvailable / $totalSlots) * 100, 1) : 0;
                ?>
                <div class="day-availability overall">
                  <strong>Overall Availability</strong><br>
                  <span class="badge badge-<?php echo $availabilityPercent > 50 ? 'success' : ($availabilityPercent > 20 ? 'warning' : 'danger'); ?>">
                    <?php echo $availabilityPercent; ?>% free (<?php echo $totalAvailable; ?>/<?php echo $totalSlots; ?> slots)
                  </span>
                </div>
              </div>

              <a href="teacher_room_schedule.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">
                <i class="fas fa-calendar"></i> View Detailed Schedule
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Room Details -->
      <div class="table-container">
        <h3><i class="fas fa-list"></i> Room Details</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Room Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Location</th>
                <th>Department</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rooms as $room): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($room['name']); ?></strong></td>
                  <td><?php echo ucfirst(str_replace('_', ' ', $room['room_type'])); ?></td>
                  <td><?php echo $room['capacity']; ?> seats</td>
                  <td><?php echo htmlspecialchars($room['location']); ?></td>
                  <td>General</td>
                  <td><span class="badge badge-success">Available</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <script src="includes/sidebar.js"></script>

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      margin: 0;
    }

    .dashboard-card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
      color: #333;
    }

    .btn {
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.95rem;
    }
    .btn-primary { background: #007bff; color: #fff; }
    .btn-primary:hover { background: #0056b3; }

    .badge {
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 0.8rem;
      color: #fff;
    }
    .badge-success { background: #28a745; }
    .badge-warning { background: #ffc107; color: #333; }
    .badge-danger { background: #dc3545; }

    .availability-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }

    .day-availability {
      text-align: center;
      padding: 10px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      background: #f9fbfd;
    }

    .day-availability.overall {
      grid-column: 1 / -1;
      text-align: center;
      padding: 15px;
      background: #e3f2fd;
      border-color: #2196f3;
    }

    .room-info {
      padding: 10px 0;
    }

    .table-container {
      margin-top: 25px;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .table-responsive { overflow-x: auto; }

    .table {
      width: 100%;
      border-collapse: collapse;
    }

    .table th, .table td {
      padding: 12px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
    }

    .table th {
      background: #f1f3f5;
      font-weight: bold;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    @media (max-width: 768px) {
      .form-group { width: 100%; }
      .dashboard-card { padding: 15px; }
      .table th, .table td { font-size: 0.9rem; }
      .dashboard-grid { grid-template-columns: 1fr; }
    }
  </style>
</body>
</html>
