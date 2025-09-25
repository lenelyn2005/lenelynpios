<?php
// student_rooms.php - Student Room Lookup System
session_start();
require 'config.php';
require_once 'includes/sidebar_new.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login_student.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$success = '';
$error = '';

// ------------------
// Get student info
// ------------------
$student = [];
$result = $mysqli->query("
    SELECT s.*, sec.name as section_name, yl.name as year_level_name, c.name as course_name
    FROM students s
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN year_levels yl ON s.year_level_id = yl.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE s.id = '$student_id'
");
if ($result) {
    $student = $result->fetch_assoc();
} else {
    echo "Student query failed: " . $mysqli->error;
}

// ------------------
// Handle room search
// ------------------
$searchResults = [];
if (isset($_POST['search_room'])) {
    $searchTerm = trim($_POST['search_term']);
    $roomType   = $_POST['room_type'];
    $capacity   = $_POST['capacity'];

    $query = "
        SELECT r.*
        FROM rooms r
        WHERE 1=1
    ";

    $params = [];
    $types  = '';

    if (!empty($searchTerm)) {
        $query .= " AND (r.name LIKE ? OR r.location LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $types .= 'ss';
    }

    if (!empty($roomType)) {
        $query .= " AND r.room_type = ?";
        $params[] = $roomType;
        $types .= 's';
    }

    if (!empty($capacity)) {
        $query .= " AND r.capacity >= ?";
        $params[] = $capacity;
        $types .= 'i';
    }

    $query .= " ORDER BY r.name";

    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res) {
                $searchResults = $res->fetch_all(MYSQLI_ASSOC);
            }
        } else {
            echo "Search query failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $mysqli->error;
    }
}

// -----------------------------
// Handle room availability check
// -----------------------------
$availabilityResult = null;
if (isset($_POST['check_availability'])) {
    $room_id    = $_POST['room_id'];
    $day        = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];

    $query = "
        SELECT s.*, sub.name as subject_name, sec.name as section_name,
               t.first_name, t.last_name
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
    ";
    $conflict = $mysqli->query($query);
    if ($conflict && $conflict->num_rows > 0) {
        $conflictRow = $conflict->fetch_assoc();
        $error = "Room is occupied by " . $conflictRow['subject_name'] . 
                 " (" . $conflictRow['section_name'] . ") - " . 
                 $conflictRow['first_name'] . " " . $conflictRow['last_name'];
    } elseif ($conflict) {
        $success = "Room is available at the requested time!";
    } else {
        echo "Availability query failed: " . $mysqli->error;
    }
}

// ------------------
// Get all rooms
// ------------------
$rooms = [];
$result = $mysqli->query("
    SELECT r.*
    FROM rooms r
    ORDER BY r.name
");
if ($result) {
    $rooms = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Rooms query failed: " . $mysqli->error;
}

// ------------------
// Get popular rooms
// ------------------
$popularRooms = [];
$result = $mysqli->query("
    SELECT r.name, r.room_type, r.location, COUNT(s.id) as usage_count
    FROM rooms r
    LEFT JOIN schedules s ON r.id = s.room_id
    GROUP BY r.id
    ORDER BY usage_count DESC
    LIMIT 5
");
if ($result) {
    $popularRooms = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Popular rooms query failed: " . $mysqli->error;
}

// ------------------
// Room types
// ------------------
$roomTypes = ['classroom', 'laboratory', 'lecture_hall', 'computer_lab'];
?>

<!-- (HTML stays the same as your version, no changes needed) -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Lookup - Student Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="includes/sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php renderSidebar('student', 'rooms'); ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Room Lookup</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="student_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Room Lookup</span>
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

        

            <!-- Room Search -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-search"></i>
                    <h3>Search Rooms</h3>
                </div>
                <div class="form-container">
                    <form method="POST" class="search-form">
                        <div class="form-group">
                            <label for="search_term">Search by Room Name or Location</label>
                            <input type="text" id="search_term" name="search_term" placeholder="e.g., Room 101 or Building A">
                        </div>

                        <div class="form-group">
                            <label for="room_type">Room Type</label>
                            <select id="room_type" name="room_type">
                                <option value="">All Types</option>
                                <?php foreach ($roomTypes as $type): ?>
                                    <option value="<?php echo $type; ?>"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="capacity">Minimum Capacity</label>
                            <input type="number" id="capacity" name="capacity" min="1" placeholder="e.g., 30">
                        </div>

                        <button type="submit" name="search_room" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search Rooms
                        </button>
                    </form>
                </div>
            </div>

            <!-- Popular Rooms -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-star"></i>
                    <h3>Popular Rooms</h3>
                </div>
                <div class="form-container">
                    <div class="popular-rooms-grid">
                        <?php foreach ($popularRooms as $room): ?>
                            <div class="popular-room-item">
                                <div class="room-icon">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="room-details">
                                    <h4><?php echo htmlspecialchars($room['name']); ?></h4>
                                    <p><?php echo ucfirst(str_replace('_', ' ', $room['room_type'])); ?></p>
                                    <p><?php echo htmlspecialchars($room['location']); ?></p>
                                    <span class="usage-count">
                                        <i class="fas fa-users"></i> Used <?php echo $room['usage_count']; ?> times
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Search Results -->
            <?php if (isset($_POST['search_room'])): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-list"></i>
                        <h3>Search Results (<?php echo count($searchResults); ?> rooms found)</h3>
                    </div>
                    <div class="table-container">
                        <?php if (empty($searchResults)): ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h4>No Rooms Found</h4>
                                <p>Try adjusting your search criteria.</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Type</th>
                                        <th>Capacity</th>
                                        <th>Location</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($searchResults as $room): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($room['name']); ?></strong></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $room['room_type'])); ?></td>
                                            <td><?php echo $room['capacity']; ?> seats</td>
                                            <td><?php echo htmlspecialchars($room['location']); ?></td>
                                            <td><?php echo htmlspecialchars($room['department_name'] ?? 'General'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="checkAvailability(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')">
                                                    <i class="fas fa-calendar-check"></i> Check Availability
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Rooms Directory -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-building"></i>
                    <h3>All Rooms Directory</h3>
                </div>
                <div class="table-container">
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
                                    <td><?php echo htmlspecialchars($room['department_name'] ?? 'General'); ?></td>
                                    <td>
                                        <span class="badge badge-success">Available</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Availability Check Modal -->
    <div id="availabilityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Check Room Availability</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" id="availabilityForm">
                <div class="modal-body">
                    <input type="hidden" id="modal_room_id" name="room_id">

                    <div class="form-group">
                        <label for="modal_room_name">Room</label>
                        <input type="text" id="modal_room_name" readonly>
                    </div>

                    <div class="form-group">
                        <label for="modal_day">Day</label>
                        <select id="modal_day" name="day" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modal_start_time">Start Time</label>
                        <input type="time" id="modal_start_time" name="start_time" required>
                    </div>

                    <div class="form-group">
                        <label for="modal_end_time">End Time</label>
                        <input type="time" id="modal_end_time" name="end_time" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="check_availability" class="btn btn-primary">Check Availability</button>
                </div>
            </form>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <script>
        // Check availability function
        function checkAvailability(roomId, roomName) {
            document.getElementById('modal_room_id').value = roomId;
            document.getElementById('modal_room_name').value = roomName;
            document.getElementById('availabilityModal').style.display = 'block';
        }

        // Close modal function
        function closeModal() {
            document.getElementById('availabilityModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('availabilityModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal when clicking X
        document.querySelector('.close').onclick = function() {
            closeModal();
        }
    </script>

    <style>
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .info-item label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 4px;
            font-size: 0.8rem;
        }

        .info-item span {
            color: #333;
            font-size: 0.9rem;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .popular-rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .popular-room-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .room-icon {
            width: 50px;
            height: 50px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .room-details h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .room-details p {
            margin: 2px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .usage-count {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            color: #007bff;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }

        .empty-state h4 {
            margin-bottom: 10px;
            color: #333;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px;
            background: #007bff;
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ccc;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            text-align: right;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .popular-rooms-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                margin: 20% auto;
                width: 95%;
            }
        }
    </style>
</body>
</html>
