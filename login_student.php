<?php
// login_student.php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    if ($student_id === '') {
        $error = "Please enter your Student ID.";
    } else {
        try {
            $stmt = $mysqli->prepare("SELECT id FROM students WHERE id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $stmt->bind_result($id);
            if ($stmt->fetch()) {
                session_regenerate_id(true);
                $_SESSION['student_id'] = $id;
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid Student ID. Please try again.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "A system error occurred. Please try again later or contact support.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | College Scheduling</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body.login-page {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            position: relative;
        }
        body.login-page::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('picture/hi.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(5px); /* Adjust the blur radius as needed */
            z-index: -1;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            z-index: 1;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .logo {
            display: block;
            margin: 0 auto 1rem;
            max-width: 150px;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <img src="picture/hi.png" alt="College Sagay City USAT Inc. Logo" class="logo">
        <form method="POST">
            <h2><i class="fas fa-user-graduate"></i> Student Login</h2>
            <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter your Student ID" required autofocus>
            </div>
            <button type="submit" class="btn btn-success" style="width: 100%;"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
    </div>
</body>
</html>