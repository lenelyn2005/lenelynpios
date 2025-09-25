<?php
// login_admin.php
session_start();
require 'config.php'; // contains $mysqli

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        try {
            $stmt = $mysqli->prepare("SELECT id, password FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($admin_id, $stored_password);
            $found = $stmt->fetch();
            $stmt->close();

            if ($found) {
                $isValid = false;
                $stored_password = (string) $stored_password;
                $password_info = password_get_info($stored_password);

                if ($password_info['algo'] !== 0) {
                    $isValid = password_verify($password, $stored_password);
                } else {
                    $isValid = hash_equals($stored_password, $password);
                }

                if ($isValid) {
                    // Upgrade old passwords to modern hashing if necessary
                    if ($password_info['algo'] === 0) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $update = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
                        $update->bind_param("si", $newHash, $admin_id);
                        $update->execute();
                        $update->close();
                    }

                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin_id;
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Incorrect username or password. Please try again.";
                }
            } else {
                $error = "Incorrect username or password. Please try again.";
            }
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
    <title>Admin Login | College Scheduling</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body.login-page {
            background-image: url('picture/hi.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
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
        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary:hover {
            background-color: #0056b3;
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
        <!-- Assuming the uploaded image is the logo; adjust src if it's saved as something else -->
        <img src="picture/hi.png" alt="College Sagay City USAT Inc. Logo" class="logo">
        <form method="POST">
            <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
            <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
    </div>
</body>
</html>