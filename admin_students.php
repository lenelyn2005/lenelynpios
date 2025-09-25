<?php
// admin_students.php - Admin CRUD for Students
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
    if (isset($_POST['add_student'])) {
        $id = trim($_POST['id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $section_id = $_POST['section_id'];
        $year_level_id = $_POST['year_level_id'];
        $course_id = $_POST['course_id'];
        
        if (!empty($id) && !empty($first_name) && !empty($last_name) && !empty($section_id)) {
            // Check if student ID already exists
            $check_stmt = $mysqli->prepare("SELECT id FROM students WHERE id = ?");
            $check_stmt->bind_param("s", $id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $error = "Student ID already exists. Please use a different ID.";
            } else {
                $stmt = $mysqli->prepare("INSERT INTO students (id, first_name, last_name, email, section_id, year_level_id, course_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiii", $id, $first_name, $last_name, $email, $section_id, $year_level_id, $course_id);
                if ($stmt->execute()) {
                    $success = "Student added successfully!";
                } else {
                    $error = "Error adding student: " . $mysqli->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['edit_student'])) {
        $id = $_POST['student_id'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $section_id = $_POST['section_id'];
        $year_level_id = $_POST['year_level_id'];
        $course_id = $_POST['course_id'];
        
        if (!empty($first_name) && !empty($last_name) && !empty($section_id)) {
            $stmt = $mysqli->prepare("UPDATE students SET first_name = ?, last_name = ?, email = ?, section_id = ?, year_level_id = ?, course_id = ? WHERE id = ?");
            $stmt->bind_param("sssiiis", $first_name, $last_name, $email, $section_id, $year_level_id, $course_id, $id);
            if ($stmt->execute()) {
                $success = "Student updated successfully!";
            } else {
                $error = "Error updating student: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['delete_student'])) {
        $id = $_POST['student_id'];
        $stmt = $mysqli->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            $success = "Student deleted successfully!";
        } else {
            $error = "Error deleting student: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// Fetch students with related information
$students = $mysqli->query("
    SELECT s.*, sec.name as section_name, yl.name as year_level_name, c.name as course_name, d.name as department_name
    FROM students s 
    LEFT JOIN sections sec ON s.section_id = sec.id 
    LEFT JOIN year_levels yl ON s.year_level_id = yl.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN departments d ON c.department_id = d.id
    ORDER BY s.id
")->fetch_all(MYSQLI_ASSOC);

// Fetch sections, year levels, and courses for dropdowns
$sections = $mysqli->query("SELECT s.*, yl.name as year_level_name, c.name as course_name FROM sections s LEFT JOIN year_levels yl ON s.year_level_id = yl.id LEFT JOIN courses c ON s.course_id = c.id ORDER BY s.name")->fetch_all(MYSQLI_ASSOC);
$year_levels = $mysqli->query("SELECT * FROM year_levels ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$courses = $mysqli->query("SELECT c.*, d.name as department_name FROM courses c LEFT JOIN departments d ON c.department_id = d.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin Dashboard (Offline Version)</title>
    <!-- For offline use: Download Font Awesome CSS from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- Keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> <!-- Assume local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume local -->
    <style>
        /* Inline CSS enhancements for user-friendly design: Modern, accessible, responsive */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            color: #333;
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
            background: #ffffff;
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
            color: #7f8c8d;
        }
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        .breadcrumb-separator {
            margin: 0 5px;
        }
        .dashboard-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
        }
        .card-header i {
            margin-right: 10px;
            color: #3498db;
        }
        .card-content {
            padding: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
        }
        input, select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus, select:focus {
            border-color: #3498db;
            outline: none;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
        }
        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .badge-primary { background: #d6eaf8; color: #3498db; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #bdc3c7;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        .modal-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal-footer {
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e9ecef;
        }
        .close {
            cursor: pointer;
            font-size: 1.5rem;
            color: #95a5a6;
        }
        .close:hover {
            color: #34495e;
        }
        .text-danger {
            color: #e74c3c;
        }
        .text-muted {
            color: #6c757d;
        }
        @media (max-width: 768px) {
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php renderSidebar('admin', 'students'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Students</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Students</span>
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

            <!-- Add Student Form -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New Student</h3>
                </div>
                <div class="card-content">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="id">Student ID <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="id" name="id" placeholder="e.g., 2024-0001" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">First Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="first_name" name="first_name" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="last_name" name="last_name" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="section_id">Section <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <select id="section_id" name="section_id" required onchange="updateCourseAndYearLevel()" aria-required="true">
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo $section['id']; ?>" data-course="<?php echo $section['course_id']; ?>" data-year="<?php echo $section['year_level_id']; ?>">
                                        <?php echo htmlspecialchars($section['name']); ?> (<?php echo htmlspecialchars($section['course_name']); ?> - <?php echo htmlspecialchars($section['year_level_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="year_level_id">Year Level</label>
                            <select id="year_level_id" name="year_level_id">
                                <option value="">Select Year Level</option>
                                <?php foreach ($year_levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <select id="course_id" name="course_id">
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?> (<?php echo htmlspecialchars($course['department_name']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_student" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students List -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-list-ul"></i>
                    <h3>All Students (<?php echo count($students); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Students Found</h3>
                            <p>Add your first student above to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Section</th>
                                        <th>Year Level</th>
                                        <th>Course</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['id']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($student['email']); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo htmlspecialchars($student['section_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($student['year_level_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo htmlspecialchars($student['course_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <?php echo htmlspecialchars($student['department_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-info" onclick="viewStudentSchedule('<?php echo htmlspecialchars($student['id']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                                        <i class="fas fa-calendar"></i> Schedule
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteStudent('<?php echo htmlspecialchars($student['id']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
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

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editModalTitle">Edit Student</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_student_id" name="student_id">
                    
                    <div class="form-group">
                        <label for="edit_first_name">First Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_first_name" name="first_name" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">Last Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_last_name" name="last_name" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_section_id">Section <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_section_id" name="section_id" required onchange="updateEditCourseAndYearLevel()" aria-required="true">
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo $section['id']; ?>" data-course="<?php echo $section['course_id']; ?>" data-year="<?php echo $section['year_level_id']; ?>">
                                    <?php echo htmlspecialchars($section['name']); ?> (<?php echo htmlspecialchars($section['course_name']); ?> - <?php echo htmlspecialchars($section['year_level_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_year_level_id">Year Level</label>
                        <select id="edit_year_level_id" name="year_level_id">
                            <option value="">Select Year Level</option>
                            <?php foreach ($year_levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_course_id">Course</label>
                        <select id="edit_course_id" name="course_id">
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?> (<?php echo htmlspecialchars($course['department_name']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="edit_student" class="btn btn-primary">Update Student</button>
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
                <p>Are you sure you want to delete the student "<span id="delete_student_name"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and may affect related schedules.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_student_id" name="student_id">
                    <button type="submit" name="delete_student" class="btn btn-danger">Delete Student</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Update course and year level based on selected section
        function updateCourseAndYearLevel() {
            const sectionSelect = document.getElementById('section_id');
            const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
            
            if (selectedOption.value) {
                const courseId = selectedOption.dataset.course;
                const yearLevelId = selectedOption.dataset.year;
                
                document.getElementById('course_id').value = courseId;
                document.getElementById('year_level_id').value = yearLevelId;
            } else {
                document.getElementById('course_id').value = '';
                document.getElementById('year_level_id').value = '';
            }
        }

        // Update course and year level in edit modal
        function updateEditCourseAndYearLevel() {
            const sectionSelect = document.getElementById('edit_section_id');
            const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
            
            if (selectedOption.value) {
                const courseId = selectedOption.dataset.course;
                const yearLevelId = selectedOption.dataset.year;
                
                document.getElementById('edit_course_id').value = courseId;
                document.getElementById('edit_year_level_id').value = yearLevelId;
            } else {
                document.getElementById('edit_course_id').value = '';
                document.getElementById('edit_year_level_id').value = '';
            }
        }

        // Edit Student Function
        function editStudent(student) {
            document.getElementById('edit_student_id').value = student.id;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_email').value = student.email || '';
            document.getElementById('edit_section_id').value = student.section_id;
            document.getElementById('edit_course_id').value = student.course_id;
            document.getElementById('edit_year_level_id').value = student.year_level_id;
            
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_first_name').focus(); // Focus on first field for accessibility
        }

        // View Student Schedule Function
        function viewStudentSchedule(id, name) {
            // This would open a modal or redirect to a schedule view page
            alert('Student schedule view for ' + name + ' (ID: ' + id + ') - Feature coming soon!');
        }

        // Delete Student Function
        function deleteStudent(id, name) {
            document.getElementById('delete_student_id').value = id;
            document.getElementById('delete_student_name').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // Close Modal Function
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
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

        // Close modal when clicking X or Escape key
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                closeModal();
            }
        });

        // Keyboard accessibility: Close on Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>