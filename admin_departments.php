<?php
// admin_departments.php - Admin CRUD for Departments
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
    if (isset($_POST['add_department'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $mysqli->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $success = "Department added successfully!";
            } else {
                $error = "Error adding department: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Department name is required.";
        }
    }
    
    elseif (isset($_POST['edit_department'])) {
        $id = $_POST['department_id'];
        $name = trim($_POST['name']);
        if (!empty($name) && !empty($id)) {
            $stmt = $mysqli->prepare("UPDATE departments SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $success = "Department updated successfully!";
            } else {
                $error = "Error updating department: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Department name is required.";
        }
    }
    
    elseif (isset($_POST['delete_department'])) {
        $id = $_POST['department_id'];
        if (!empty($id)) {
            // Check if department has courses
            $check_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM courses WHERE department_id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $check_stmt->close();
            
            if ($count > 0) {
                $error = "Cannot delete department. It has $count course(s) associated with it.";
            } else {
                $stmt = $mysqli->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Department deleted successfully!";
                } else {
                    $error = "Error deleting department: " . $mysqli->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid department ID.";
        }
    }
}

// Fetch all departments with course count
$departments = $mysqli->query("
    SELECT d.*, COUNT(c.id) as course_count 
    FROM departments d 
    LEFT JOIN courses c ON d.id = c.department_id 
    GROUP BY d.id 
    ORDER BY d.name
")->fetch_all(MYSQLI_ASSOC);

// Get department for editing
$edit_department = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_department = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - Admin Dashboard</title>
    <!-- For offline use: Download Font Awesome CSS and icons from https://fontawesome.com/download and place in a local 'fontawesome' folder -->
    <!-- Example local link: <link rel="stylesheet" href="fontawesome/css/all.min.css"> -->
    <!-- For now, keeping CDN for demonstration; replace with local for true offline -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxvA5OBs9Ozw+Bw5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> <!-- Assume this is local -->
    <link rel="stylesheet" href="includes/sidebar.css"> <!-- Assume this is local -->
</head>
<body>
    <?php renderSidebar('admin', 'departments'); ?>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-btn" aria-label="Toggle mobile menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Manage Departments</h1>
            </div>
            <div class="navbar-right">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="admin_dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator" aria-hidden="true">/</span>
                    <span>Departments</span>
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
                <!-- Add/Edit Department Form -->
                <article class="dashboard-card form-card">
                    <header class="card-header">
                        <i class="fas fa-<?php echo $edit_department ? 'edit' : 'plus-circle'; ?>" aria-hidden="true"></i>
                        <h2><?php echo $edit_department ? 'Edit Department' : 'Add New Department'; ?></h2>
                    </header>
                    <div class="card-content">
                        <form method="POST" class="form-grid" novalidate aria-label="<?php echo $edit_department ? 'Edit department form' : 'Add department form'; ?>">
                            <?php if ($edit_department): ?>
                                <input type="hidden" name="department_id" value="<?php echo $edit_department['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group full-width">
                                <label for="name">Department Name <span aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       required 
                                       aria-required="true"
                                       aria-describedby="name-help"
                                       value="<?php echo $edit_department ? htmlspecialchars($edit_department['name']) : ''; ?>"
                                       placeholder="Enter department name">
                                <small id="name-help" class="sr-only">Enter the full name of the department.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" 
                                        name="<?php echo $edit_department ? 'edit_department' : 'add_department'; ?>" 
                                        class="btn btn-primary full-width">
                                    <i class="fas fa-<?php echo $edit_department ? 'save' : 'plus'; ?>" aria-hidden="true"></i>
                                    <?php echo $edit_department ? 'Update Department' : 'Add Department'; ?>
                                </button>
                                
                                <?php if ($edit_department): ?>
                                    <a href="admin_departments.php" class="btn btn-secondary full-width">
                                        <i class="fas fa-times" aria-hidden="true"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </article>

                <!-- Departments List -->
                <article class="dashboard-card list-card">
                    <header class="card-header">
                        <i class="fas fa-list-ul" aria-hidden="true"></i>
                        <h2>All Departments (<?php echo count($departments); ?>)</h2>
                    </header>
                    <div class="card-content">
                        <?php if (empty($departments)): ?>
                            <div class="empty-state">
                                <i class="fas fa-building" aria-hidden="true"></i>
                                <h3>No Departments Found</h3>
                                <p>Add your first department using the form above to get started.</p>
                                <button class="btn btn-primary" onclick="document.querySelector('.form-card').scrollIntoView({behavior: 'smooth'})">Add Department</button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table" role="table" aria-label="Departments list">
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Courses</th>
                                            <th scope="col">Created</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departments as $dept): ?>
                                            <tr>
                                                <td><?php echo $dept['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info" aria-label="<?php echo $dept['course_count']; ?> courses in this department">
                                                        <?php echo $dept['course_count']; ?> courses
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($dept['created_at'] ?? 'now')); ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?edit=<?php echo $dept['id']; ?>" 
                                                           class="btn btn-sm btn-warning" 
                                                           aria-label="Edit <?php echo htmlspecialchars($dept['name']); ?>">
                                                            <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                                        </a>
                                                        
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger" 
                                                                onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name']); ?>')"
                                                                aria-label="Delete <?php echo htmlspecialchars($dept['name']); ?>">
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

    <!-- Delete Confirmation Modal -->
    <dialog id="deleteModal" class="modal" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal-content">
            <header class="modal-header">
                <h3 id="deleteModalTitle">Confirm Delete</h3>
                <button class="close" aria-label="Close modal" role="button">&times;</button>
            </header>
            <div class="modal-body">
                <p>Are you sure you want to delete the department "<span id="delete_department_name"></span>"?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><strong>Warning:</strong> This action cannot be undone and may affect related courses and teachers if any exist.</p>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" id="delete_department_id" name="department_id">
                    <button type="submit" name="delete_department" class="btn btn-danger">Delete Department</button>
                </form>
            </footer>
        </div>
    </dialog>

    <script src="includes/sidebar.js"></script>
    <script>
        function deleteDepartment(id, name) {
            document.getElementById('delete_department_id').value = id;
            document.getElementById('delete_department_name').textContent = name;
            const modal = document.getElementById('deleteModal');
            modal.showModal();
            modal.classList.add('fade-in');
        }

        function closeModal() {
            const modal = document.getElementById('deleteModal');
            if (modal.open) {
                modal.classList.remove('fade-in');
                modal.close();
            }
        }

        // Close on overlay click
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Close button
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
            display: flex;
            flex-direction: column;
            gap: 12px;
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
            justify-content: center;
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

        .btn-warning {
            background: var(--warning-color);
            color: var(--text-color);
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

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-info {
            background: rgba(52, 152, 219, 0.2);
            color: var(--info-color);
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

            .form-actions {
                flex-direction: column;
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
            .top-navbar, .modal, .action-buttons .btn-danger {
                display: none;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
