<?php
// admin_courses.php - Admin CRUD for Courses
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
    if (isset($_POST['add_course'])) {
        $name = trim($_POST['name']);
        $department_id = $_POST['department_id'];
        $description = trim($_POST['description']);
        
        if (!empty($name) && !empty($department_id)) {
            $stmt = $mysqli->prepare("INSERT INTO courses (name, department_id, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $department_id, $description);
            if ($stmt->execute()) {
                $success = "Course added successfully!";
            } else {
                $error = "Error adding course: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['edit_course'])) {
        $id = $_POST['course_id'];
        $name = trim($_POST['name']);
        $department_id = $_POST['department_id'];
        $description = trim($_POST['description']);
        
        if (!empty($name) && !empty($department_id)) {
            $stmt = $mysqli->prepare("UPDATE courses SET name = ?, department_id = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sisi", $name, $department_id, $description, $id);
            if ($stmt->execute()) {
                $success = "Course updated successfully!";
            } else {
                $error = "Error updating course: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['delete_course'])) {
        $id = $_POST['course_id'];
        $stmt = $mysqli->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Course deleted successfully!";
        } else {
            $error = "Error deleting course: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// Fetch courses with department information
$courses = $mysqli->query("
    SELECT c.*, d.name as department_name, 
           COUNT(s.id) as section_count
    FROM courses c 
    LEFT JOIN departments d ON c.department_id = d.id 
    LEFT JOIN sections s ON c.id = s.course_id
    GROUP BY c.id
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);

// Fetch departments for dropdown
$departments = $mysqli->query("SELECT * FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin Dashboard</title>
    <!-- For offline use: Download Font Awesome CSS and icons from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- For now, keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> <!-- Assume this is local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume this is local -->
</head>
<body>
    <?php renderSidebar('admin', 'courses'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Courses</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Courses</span>
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

            <div class="dashboard-grid">
                <!-- Add Course Form -->
                <div class="dashboard-card form-card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Add New Course</h3>
                    </div>
                    <div class="card-content">
                        <form method="POST" class="form-grid">
                            <div class="form-group">
                                <label for="name">Course Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <input type="text" id="name" name="name" required aria-required="true">
                            </div>
                            
                            <div class="form-group">
                                <label for="department_id">Department <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <select id="department_id" name="department_id" required aria-required="true">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4"></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="add_course" class="btn btn-primary full-width">
                                    <i class="fas fa-plus"></i> Add Course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Courses List -->
                <div class="dashboard-card list-card">
                    <div class="card-header">
                        <i class="fas fa-list-ul"></i>
                        <h3>All Courses (<?php echo count($courses); ?>)</h3>
                    </div>
                    <div class="card-content">
                        <?php if (empty($courses)): ?>
                            <div class="empty-state">
                                <i class="fas fa-graduation-cap"></i>
                                <h3>No Courses Found</h3>
                                <p>Add your first course using the form on the left to get started.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Course Name</th>
                                            <th>Department</th>
                                            <th>Sections</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo $course['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($course['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($course['department_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-secondary">
                                                        <?php echo $course['section_count']; ?> sections
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>
                                                    <?php if (strlen($course['description'] ?? '') > 100): ?>...<?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-warning" onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['name']); ?>')">
                                                            <i class="fas fa-trash-alt"></i> Delete
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
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editModalTitle">Edit Course</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_course_id" name="course_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Course Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_name" name="name" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_department_id">Department <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_department_id" name="department_id" required aria-required="true">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="edit_course" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="deleteModalTitle">Confirm Delete</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the course "<span id="delete_course_name"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and may affect related sections and subjects.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_course_id" name="course_id">
                    <button type="submit" name="delete_course" class="btn btn-danger">Delete Course</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume this is local -->
    <script>
        function editCourse(course) {
            document.getElementById('edit_course_id').value = course.id;
            document.getElementById('edit_name').value = course.name;
            document.getElementById('edit_department_id').value = course.department_id;
            document.getElementById('edit_description').value = course.description || '';
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_name').focus();
        }

        function deleteCourse(id, name) {
            document.getElementById('delete_course_id').value = id;
            document.getElementById('delete_course_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        }

        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
    
    <style>
        /* CSS Variables for Theming */
        :root {
            --primary-color: #3498db;
            --success-color: #28a745;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #17a2b8;
            --secondary-color: #95a5a6;
            --background-color: #f8f9fa;
            --text-color: #333;
            --muted-text: #7f8c8d;
            --white: #fff;
            --shadow: 0 2px 10px rgba(0,0,0,0.05);
            --border-color: #ddd;
            --hover-lift: translateY(-3px);
            --transition: all 0.2s ease;
            --body-bg: #f4f7fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--body-bg);
            color: var(--text-color);
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
            background: var(--white);
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
            color: var(--muted-text);
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-separator {
            margin: 0 5px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 16px;
            margin-bottom: 32px;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .dashboard-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: var(--background-color);
            border-bottom: 1px solid var(--border-color);
        }

        .card-header i {
            color: var(--primary-color);
            font-size: 18px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .card-content {
            padding: 16px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
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
            margin-bottom: 4px;
            color: var(--text-color);
            font-size: 14px;
        }

        input, select, textarea {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-start;
            margin-top: 16px;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: var(--text-color);
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .full-width {
            width: 100%;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--background-color);
            font-weight: 600;
            font-size: 14px;
        }

        .data-table td {
            font-size: 14px;
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: var(--background-color);
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-color);
        }

        .badge-info {
            background: #d6eaf8;
            color: var(--primary-color);
        }

        .badge-secondary {
            background: #e5e7eb;
            color: var(--secondary-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 32px;
            color: var(--muted-text);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 16px;
            color: var(--primary-color);
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
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
            background-color: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 480px;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .close {
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--muted-text);
            transition: var(--transition);
        }

        .close:hover {
            color: var(--text-color);
        }

        .modal-body {
            padding: 16px;
            font-size: 14px;
        }

        .text-danger {
            color: var(--danger-color);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }

        /* Alerts */
        .alert {
            padding: 12px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .form-card {
                order: 2;
            }

            .list-card {
                order: 1;
            }

            .form-grid {
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
    </style>
</body>