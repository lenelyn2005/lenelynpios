<?php
// logout_fixed.php - Enhanced logout functionality for all user types
session_start();

// Check user type before destroying session
$userType = '';
if (isset($_SESSION['admin_id'])) {
    $userType = 'admin';
} elseif (isset($_SESSION['teacher_id'])) {
    $userType = 'teacher';
} elseif (isset($_SESSION['student_id'])) {
    $userType = 'student';
}

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

// Redirect to main page with success message
header("Location: index.php?logout=success");
exit;
?>
