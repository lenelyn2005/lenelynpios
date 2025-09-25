<?php
// admin_rooms.php - Admin CRUD for Rooms
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
    if (isset($_POST['add_room'])) {
        $name = trim($_POST['name']);
        $capacity = (int)$_POST['capacity'];
        $room_type = $_POST['room_type'];
        $location = trim($_POST['location']);
        $description = trim($_POST['description']);
        
        if (!empty($name) && $capacity > 0) {
            $stmt = $mysqli->prepare("INSERT INTO rooms (name, capacity, room_type, location, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $name, $capacity, $room_type, $location, $description);
            if ($stmt->execute()) {
                $success = "Room added successfully!";
            } else {
                $error = "Error adding room: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Room name and capacity are required.";
        }
    }
    
    elseif (isset($_POST['edit_room'])) {
        $id = (int)$_POST['room_id'];
        $name = trim($_POST['name']);
        $capacity = (int)$_POST['capacity'];
        $room_type = $_POST['room_type'];
        $location = trim($_POST['location']);
        $description = trim($_POST['description']);
        
        if (!empty($name) && $capacity > 0 && $id > 0) {
            $stmt = $mysqli->prepare("UPDATE rooms SET name = ?, capacity = ?, room_type = ?, location = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sisssi", $name, $capacity, $room_type, $location, $description, $id);
            if ($stmt->execute()) {
                $success = "Room updated successfully!";
            } else {
                $error = "Error updating room: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Room name and capacity are required.";
        }
    }
    
    elseif (isset($_POST['delete_room'])) {
        $id = (int)$_POST['room_id'];
        if ($id > 0) {
            // Check if room has schedules
            $check_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM schedules WHERE room_id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $check_stmt->close();
            
            if ($count > 0) {
                $error = "Cannot delete room. It has $count schedule(s) associated with it.";
            } else {
                $stmt = $mysqli->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Room deleted successfully!";
                } else {
                    $error = "Error deleting room: " . $mysqli->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid room ID.";
        }
    }
}

// Fetch rooms with usage statistics
$rooms = $mysqli->query("
    SELECT r.*, 
           COUNT(DISTINCT s.id) as schedule_count,
           COUNT(DISTINCT CASE WHEN s.day_of_week IS NOT NULL THEN s.id END) as active_schedules
    FROM rooms r 
    LEFT JOIN schedules s ON r.id = s.room_id
    GROUP BY r.id
    ORDER BY r.name
")->fetch_all(MYSQLI_ASSOC);

// Room types for dropdown
$room_types = ['classroom', 'laboratory', 'computer_lab', 'library', 'auditorium', 'conference_room', 'office', 'other'];

// Helper function to get room type color class
function getRoomTypeColor($type) {
    $colors = [
        'classroom' => 'primary',
        'laboratory' => 'success',
        'computer_lab' => 'info',
        'library' => 'warning',
        'auditorium' => 'danger',
        'conference_room' => 'secondary',
        'office' => 'muted',
        'other' => 'light'
    ];
    return $colors[$type] ?? 'secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Dashboard</title>
    <!-- For offline use: Download Font Awesome CSS and icons from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- For now, keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> <!-- Assume this is local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume this is local -->
</head>
<body>
    <?php renderSidebar('admin', 'rooms'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn" aria-label="Toggle mobile menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Rooms</h1>
            </div>
            <div class="navbar-right">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator" aria-hidden="true">/</span>
                    <span>Rooms</span>
                </nav>
            </div>
        </div>
        
        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success fade-in" role="alert">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error fade-in" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Add Room Form -->
                <article class="dashboard-card form-card">
                    <header class="card-header">
                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                        <h2>Add New Room</h2>
                    </header>
                    <div class="card-content">
                        <form method="POST" class="form-grid" novalidate aria-label="Add room form">
                            <div class="form-group">
                                <label for="name">Room Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       required 
                                       aria-required="true"
                                       aria-describedby="name-help"
                                       placeholder="e.g., Room 101, Lab A">
                                <small id="name-help" class="sr-only">Enter a unique name for the room.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="capacity">Capacity <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <input type="number" 
                                       id="capacity" 
                                       name="capacity" 
                                       min="1" 
                                       max="500" 
                                       required 
                                       aria-required="true"
                                       aria-describedby="capacity-help"
                                       placeholder="e.g., 30">
                                <small id="capacity-help" class="sr-only">Enter the maximum number of seats (1-500).</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="room_type">Room Type <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <select id="room_type" 
                                        name="room_type" 
                                        required 
                                        aria-required="true"
                                        aria-describedby="type-help">
                                    <option value="">Select Room Type</option>
                                    <?php foreach ($room_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo ucwords(str_replace('_', ' ', $type)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="type-help" class="sr-only">Choose the type of room for scheduling purposes.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" 
                                       id="location" 
                                       name="location" 
                                       aria-describedby="location-help"
                                       placeholder="e.g., Building A, 2nd Floor">
                                <small id="location-help" class="sr-only">Optional: Specify the building and floor.</small>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" 
                                          name="description" 
                                          rows="4" 
                                          aria-describedby="desc-help"
                                          placeholder="Additional details about the room (optional)"></textarea>
                                <small id="desc-help" class="sr-only">Optional: Provide any special notes or equipment available.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="add_room" class="btn btn-primary full-width">
                                    <i class="fas fa-plus" aria-hidden="true"></i> Add Room
                                </button>
                            </div>
                        </form>
                    </div>
                </article>

                <!-- Rooms List -->
                <article class="dashboard-card list-card">
                    <header class="card-header">
                        <i class="fas fa-list-ul" aria-hidden="true"></i>
                        <h2>All Rooms (<?php echo count($rooms); ?>)</h2>
                    </header>
                    <div class="card-content">
                        <?php if (empty($rooms)): ?>
                            <div class="empty-state">
                                <i class="fas fa-door-open" aria-hidden="true"></i>
                                <h3>No Rooms Found</h3>
                                <p>Add your first room using the form above to get started.</p>
                                <button class="btn btn-primary" onclick="document.querySelector('.form-card').scrollIntoView({behavior: 'smooth'})">Add Room</button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table" role="table" aria-label="Rooms list">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Capacity</th>
                                            <th scope="col">Location</th>
                                            <th scope="col">Usage</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rooms as $room): ?>
                                            <tr>
                                                <td><?php echo $room['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($room['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo getRoomTypeColor($room['room_type']); ?>" aria-label="Room type: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $room['room_type']))); ?>">
                                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $room['room_type']))); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info" aria-label="<?php echo $room['capacity']; ?> seats capacity">
                                                        <?php echo $room['capacity']; ?> seats
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($room['location'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-success" aria-label="<?php echo $room['active_schedules']; ?> active schedules">
                                                        <?php echo $room['active_schedules']; ?> active
                                                    </span>
                                                    <br>
                                                    <small class="text-muted"><?php echo $room['schedule_count']; ?> total</small>
                                                </td>
                                                <td>
                                                    <?php $desc = $room['description'] ?? ''; ?>
                                                    <?php echo htmlspecialchars(substr($desc, 0, 50)); ?>
                                                    <?php if (strlen($desc) > 50): ?>...<?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-warning" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)" aria-label="Edit <?php echo htmlspecialchars($room['name']); ?>">
                                                            <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-info" onclick="viewRoomSchedule(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')" aria-label="View schedule for <?php echo htmlspecialchars($room['name']); ?>">
                                                            <i class="fas fa-calendar" aria-hidden="true"></i> Schedule
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')" aria-label="Delete <?php echo htmlspecialchars($room['name']); ?>">
                                                            <i class="fas fa-trash" aria-hidden="true"></i> Delete
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
                </article>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <dialog id="editModal" class="modal" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-content">
            <header class="modal-header">
                <h3 id="editModalTitle">Edit Room</h3>
                <button class="close" aria-label="Close modal" role="button">&times;</button>
            </header>
            <form method="POST" id="editForm" novalidate aria-label="Edit room form">
                <div class="modal-body">
                    <input type="hidden" id="edit_room_id" name="room_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Room Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_name" name="name" required aria-required="true" aria-describedby="edit-name-help">
                        <small id="edit-name-help" class="sr-only">Enter a unique name for the room.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_capacity">Capacity <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="number" id="edit_capacity" name="capacity" min="1" max="500" required aria-required="true" aria-describedby="edit-capacity-help">
                        <small id="edit-capacity-help" class="sr-only">Enter the maximum number of seats (1-500).</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_room_type">Room Type <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_room_type" name="room_type" required aria-required="true" aria-describedby="edit-type-help">
                            <option value="">Select Room Type</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo ucwords(str_replace('_', ' ', $type)); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="edit-type-help" class="sr-only">Choose the type of room for scheduling purposes.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_location">Location</label>
                        <input type="text" id="edit_location" name="location" aria-describedby="edit-location-help">
                        <small id="edit-location-help" class="sr-only">Optional: Specify the building and floor.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="4" aria-describedby="edit-desc-help"></textarea>
                        <small id="edit-desc-help" class="sr-only">Optional: Provide any special notes or equipment available.</small>
                    </div>
                </div>
                <footer class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="edit_room" class="btn btn-primary">Update Room</button>
                </footer>
            </form>
        </div>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="deleteModal" class="modal" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal-content">
            <header class="modal-header">
                <h3 id="deleteModalTitle">Confirm Delete</h3>
                <button class="close" aria-label="Close modal" role="button">&times;</button>
            </header>
            <div class="modal-body">
                <p>Are you sure you want to delete the room "<span id="delete_room_name"></span>"?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><strong>Warning:</strong> This action cannot be undone and may affect related schedules if any exist.</p>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_room_id" name="room_id">
                    <button type="submit" name="delete_room" class="btn btn-danger">Delete Room</button>
                </form>
            </footer>
        </div>
    </dialog>

    <!-- View Schedule Modal (Placeholder) -->
    <dialog id="scheduleModal" class="modal" aria-modal="true" aria-labelledby="scheduleModalTitle">
        <div class="modal-content">
            <header class="modal-header">
                <h3 id="scheduleModalTitle">Room Schedule</h3>
                <button class="close" aria-label="Close modal" role="button">&times;</button>
            </header>
            <div class="modal-body">
                <p id="schedule_room_name"></p>
                <p>Schedule view feature is coming soon. This room has associated schedules that can be viewed here.</p>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeScheduleModal()">Close</button>
            </footer>
        </div>
    </dialog>

    <script src="includes/sidebar.js"></script>
    <script>
        function editRoom(room) {
            document.getElementById('edit_room_id').value = room.id;
            document.getElementById('edit_name').value = room.name || '';
            document.getElementById('edit_capacity').value = room.capacity || '';
            document.getElementById('edit_room_type').value = room.room_type || '';
            document.getElementById('edit_location').value = room.location || '';
            document.getElementById('edit_description').value = room.description || '';
            const modal = document.getElementById('editModal');
            modal.showModal();
            modal.classList.add('fade-in');
            document.getElementById('edit_name').focus();
        }

        function viewRoomSchedule(id, name) {
            document.getElementById('schedule_room_name').textContent = name + ' (ID: ' + id + ')';
            const modal = document.getElementById('scheduleModal');
            modal.showModal();
            modal.classList.add('fade-in');
        }

        function deleteRoom(id, name) {
            document.getElementById('delete_room_id').value = id;
            document.getElementById('delete_room_name').textContent = name;
            const modal = document.getElementById('deleteModal');
            modal.showModal();
            modal.classList.add('fade-in');
        }

        function closeModal() {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const scheduleModal = document.getElementById('scheduleModal');
            if (editModal.open) {
                editModal.classList.remove('fade-in');
                editModal.close();
            }
            if (deleteModal.open) {
                deleteModal.classList.remove('fade-in');
                deleteModal.close();
            }
            if (scheduleModal.open) {
                scheduleModal.classList.remove('fade-in');
                scheduleModal.close();
            }
        }

        function closeScheduleModal() {
            const modal = document.getElementById('scheduleModal');
            if (modal.open) {
                modal.classList.remove('fade-in');
                modal.close();
            }
        }

        // Close on overlay click
        document.addEventListener('click', function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const scheduleModal = document.getElementById('scheduleModal');
            if (event.target === editModal || event.target === deleteModal || event.target === scheduleModal) {
                closeModal();
            }
        });

        // Close buttons
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', closeModal);
        });

        // Esc key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
    
    <style>
        /* CSS Variables for Theming - Matching previous admin pages */
        :root {
            --primary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --secondary-color: #95a5a6;
            --background-color: #f8f9fa;
            --text-color: #2c3e50;
            --muted-text: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0,0,0,0.08);
            --border-color: #e1e8ed;
            --hover-shadow: 0 4px 20px rgba(0,0,0,0.12);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --body-bg: #f8f9fc;
            --border-radius: 12px;
            --font-size-base: 14px;
            --hover-lift: translateY(-2px);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--body-bg);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            line-height: 1.5;
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
            background: var(--white);
            padding: 16px 24px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            border-radius: var(--border-radius);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--muted-text);
            padding: 8px;
            border-radius: 6px;
            transition: var(--transition);
        }

        .mobile-menu-btn:hover,
        .mobile-menu-btn:focus {
            background: var(--background-color);
            color: var(--primary-color);
            outline: none;
        }

        .page-title {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .navbar-right {
            display: flex;
            align-items: center;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            font-size: var(--font-size-base);
            color: var(--muted-text);
            gap: 8px;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover,
        .breadcrumb a:focus {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: var(--border-color);
        }

        .content-area {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .dashboard-card:hover {
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            background: var(--background-color);
            border-bottom: 1px solid var(--border-color);
        }

        .card-header i {
            color: var(--primary-color);
            font-size: 20px;
            flex-shrink: 0;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-content {
            padding: 24px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--text-color);
            font-size: var(--font-size-base);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        input, select, textarea {
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: var(--font-size-base);
            font-family: inherit;
            transition: var(--transition);
            background: var(--white);
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-start;
            margin-top: 20px;
        }

        /* Button Styles */
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: var(--font-size-base);
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover,
        .btn:focus {
            transform: var(--hover-lift);
            box-shadow: var(--shadow);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-info {
            background: var(--info-color);
            color: var(--white);
        }

        .btn-info:hover {
            background: #1e90ff;
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--white);
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .full-width {
            width: 100%;
            justify-content: center;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .data-table th,
        .data-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--background-color);
            font-weight: 600;
            font-size: var(--font-size-base);
            color: var(--text-color);
        }

        .data-table tbody tr {
            transition: var(--transition);
        }

        .data-table tbody tr:hover {
            background: var(--background-color);
        }

        .data-table td {
            font-size: var(--font-size-base);
        }

        .text-muted {
            color: var(--muted-text);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: rgba(52, 152, 219, 0.2);
            color: var(--primary-color);
        }

        .badge-success {
            background: rgba(39, 174, 96, 0.2);
            color: var(--success-color);
        }

        .badge-info {
            background: rgba(52, 152, 219, 0.2);
            color: var(--info-color);
        }

        .badge-warning {
            background: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }

        .badge-danger {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }

        .badge-secondary {
            background: rgba(149, 165, 166, 0.2);
            color: var(--secondary-color);
        }

        .badge-light {
            background: rgba(248, 249, 250, 0.5);
            color: var(--text-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--muted-text);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 12px;
            color: var(--text-color);
        }

        .empty-state p {
            font-size: var(--font-size-base);
            margin-bottom: 24px;
        }

        /* Modal Styles */
        .modal {
            border: none;
            border-radius: var(--border-radius);
            max-width: 500px;
            width: 90%;
            backdrop-filter: blur(4px);
        }

        .modal::backdrop {
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--hover-shadow);
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            background: var(--background-color);
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--muted-text);
            padding: 4px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .close:hover,
        .close:focus {
            background: var(--border-color);
            color: var(--text-color);
            outline: none;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body .form-group {
            margin-bottom: 20px;
        }

        .text-danger {
            color: var(--danger-color);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            font-size: var(--font-size-base);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 20px 24px;
            border-top: 1px solid var(--border-color);
            background: var(--background-color);
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: var(--font-size-base);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            color: var(--success-color);
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: var(--danger-color);
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal.fade-in {
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }

            .top-navbar {
                padding: 12px 16px;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .navbar-left {
                justify-content: space-between;
                width: 100%;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .form-card {
                order: 2;
            }

            .list-card {
                order: 1;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card-content {
                padding: 16px;
            }

            .data-table th,
            .data-table td {
                padding: 12px 8px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }

            .btn-sm {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                margin: 20px;
                width: calc(100% - 40px);
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.25rem;
            }

            .breadcrumb {
                font-size: 13px;
            }
        }

        /* High contrast and reduced motion */
        @media (prefers-contrast: high) {
            :root {
                --shadow: 0 2px 10px rgba(0,0,0,0.2);
                --border-color: #000;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
        }

        /* Print styles */
        @media print {
            .top-navbar, .modal {
                display: none;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
