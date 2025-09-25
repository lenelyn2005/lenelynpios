<?php
// logout.php - Enhanced logout functionality for all user types
session_start();

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Determine redirect based on user type (if session data still exists)
$redirectUrl = 'index.php'; // Default redirect

if (isset($_SESSION['admin_id'])) {
    $redirectUrl = 'index.php';
} elseif (isset($_SESSION['teacher_id'])) {
    $redirectUrl = 'index.php';
} elseif (isset($_SESSION['student_id'])) {
    $redirectUrl = 'index.php';
}

// Add a logout parameter to show success message
$redirectUrl .= '?logout=success';

// Redirect to main page
header("Location: $redirectUrl");
exit;
?>
