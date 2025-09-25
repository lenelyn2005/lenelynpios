<?php
// admin_sections.php - Admin CRUD for Year Levels & Sections
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
    if (isset($_POST['add_section'])) {
        $name = trim($_POST['name']);
        $year_level_id = $_POST['year_level_id'];
        $course_id = $_POST['course_id'];
        $max_students = $_POST['max_students'];
        
        if (!empty($name) && !empty($year_level_id) && !empty($course_id)) {
            $stmt = $mysqli->prepare("INSERT INTO sections (name, year_level_id, course_id, max_students) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siii", $name, $year_level_id, $course_id, $max_students);
            if ($stmt->execute()) {
                $success = "Section added successfully!";
            } else {
                $error = "Error adding section: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['edit_section'])) {
        $id = $_POST['section_id'];
        $name = trim($_POST['name']);
        $year_level_id = $_POST['year_level_id'];
        $course_id = $_POST['course_id'];
        $max_students = $_POST['max_students'];
        
        if (!empty($name) && !empty($year_level_id) && !empty($course_id)) {
            $stmt = $mysqli->prepare("UPDATE sections SET name = ?, year_level_id = ?, course_id = ?, max_students = ? WHERE id = ?");
            $stmt->bind_param("siiii", $name, $year_level_id, $course_id, $max_students, $id);
            if ($stmt->execute()) {
                $success = "Section updated successfully!";
            } else {
                $error = "Error updating section: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
    
    elseif (isset($_POST['delete_section'])) {
        $id = $_POST['section_id'];
        $stmt = $mysqli->prepare("DELETE FROM sections WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Section deleted successfully!";
        } else {
            $error = "Error deleting section: " . $mysqli->error;
        }
        $stmt->close();
    }
}

// Filter logic
$where = "WHERE 1=1";
$params = [];
$types = "";
if (isset($_GET['course_id']) && !empty($_GET['course_id'])) {
    $where .= " AND s.course_id = ?";
    $params[] = intval($_GET['course_id']);
    $types .= "i";
}
if (isset($_GET['section_id']) && !empty($_GET['section_id'])) {
    $where .= " AND s.id = ?";
    $params[] = intval($_GET['section_id']);
    $types .= "i";
}

// Fetch sections with related information
$query = "
    SELECT s.*, yl.name as year_level_name, c.name as course_name, d.name as department_name,
           COUNT(st.id) as student_count
    FROM sections s 
    LEFT JOIN year_levels yl ON s.year_level_id = yl.id 
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN students st ON s.id = st.section_id
    $where
    GROUP BY s.id
    ORDER BY yl.name, c.name, s.name
";
if (!empty($params)) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $sections = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sections = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Fetch year levels and courses for dropdowns
$year_levels = $mysqli->query("SELECT * FROM year_levels ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$courses = $mysqli->query("SELECT c.*, d.name as department_name FROM courses c LEFT JOIN departments d ON c.department_id = d.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);

$all_sections = $mysqli->query("SELECT id, name FROM sections ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - Admin Dashboard (Offline Version)</title>
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
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #d6eaf8; color: #3498db; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .badge-success { background: #d4edda; color: #155724; }
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
            max-width: 500px;
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
    <?php renderSidebar('admin', 'sections'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Sections</h1>
            </div>
            <div class="navbar-right">
                <div class="breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Sections</span>
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

            <!-- Add Section Form -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New Section</h3>
                </div>
                <div class="card-content">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="name">Section Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <input type="text" id="name" name="name" placeholder="e.g., CS-1A, IT-2B" required aria-required="true">
                        </div>
                        
                        <div class="form-group">
                            <label for="year_level_id">Year Level <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <select id="year_level_id" name="year_level_id" required aria-required="true">
                                <option value="">Select Year Level</option>
                                <?php foreach ($year_levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_id">Course <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                            <select id="course_id" name="course_id" required aria-required="true">
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?> (<?php echo htmlspecialchars($course['department_name']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_students">Max Students</label>
                            <input type="number" id="max_students" name="max_students" value="50" min="1" max="100">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_section" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Section
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            
            <!-- Sections List -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-list-ul"></i>
                    <h3>All Sections (<?php echo count($sections); ?>)</h3>
                </div>
                <div class="card-content">
                    <!-- Filter Sections -->
                    <div class="filter-section">
                        <form method="GET">
                            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
                                <div class="form-group">
                                    <label for="course_id">Filter by Course</label>
                                    <select id="course_id" name="course_id">
                                        <option value="">All Courses</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($course['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="section_id">Filter by Section</label>
                                    <select id="section_id" name="section_id">
                                        <option value="">All Sections</option>
                                        <?php foreach ($all_sections as $sec): ?>
                                            <option value="<?php echo $sec['id']; ?>" <?php echo (isset($_GET['section_id']) && $_GET['section_id'] == $sec['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sec['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php if (empty($sections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Sections Found</h3>
                            <p>Add your first section above to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Section Name</th>
                                        <th>Year Level</th>
                                        <th>Course</th>
                                        <th>Department</th>
                                        <th>Students</th>
                                        <th>Max Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sections as $section): ?>
                                        <tr>
                                            <td><?php echo $section['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($section['name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($section['year_level_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo htmlspecialchars($section['course_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo htmlspecialchars($section['department_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo $section['student_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <?php echo $section['max_students']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editSection(<?php echo htmlspecialchars(json_encode($section)); ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteSection(<?php echo $section['id']; ?>, '<?php echo htmlspecialchars($section['name']); ?>')">
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

    <!-- Edit Section Modal -->
    <div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editModalTitle">Edit Section</h3>
                <span class="close" aria-label="Close">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_section_id" name="section_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Section Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <input type="text" id="edit_name" name="name" required aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_year_level_id">Year Level <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_year_level_id" name="year_level_id" required aria-required="true">
                            <option value="">Select Year Level</option>
                            <?php foreach ($year_levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_course_id">Course <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <select id="edit_course_id" name="course_id" required aria-required="true">
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?> (<?php echo htmlspecialchars($course['department_name']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_max_students">Max Students</label>
                        <input type="number" id="edit_max_students" name="max_students" min="1" max="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="edit_section" class="btn btn-primary">Update Section</button>
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
                <p>Are you sure you want to delete the section "<span id="delete_section_name"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and may affect related students and schedules.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_section_id" name="section_id">
                    <button type="submit" name="delete_section" class="btn btn-danger">Delete Section</button>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script> <!-- Assume local -->
    <script>
        // Enhanced JavaScript for better usability: Smooth modals, keyboard accessibility
        function editSection(section) {
            document.getElementById('edit_section_id').value = section.id;
            document.getElementById('edit_name').value = section.name;
            document.getElementById('edit_year_level_id').value = section.year_level_id;
            document.getElementById('edit_course_id').value = section.course_id;
            document.getElementById('edit_max_students').value = section.max_students;
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_name').focus(); // Focus on first field for accessibility
        }

        // Delete Section Function
        function deleteSection(id, name) {
            document.getElementById('delete_section_id').value = id;
            document.getElementById('delete_section_name').textContent = name;
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
