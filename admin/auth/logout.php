<?php
session_start();

// Check if logout is due to timeout
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == '1';

// Check user role before destroying session
$user_role = $_SESSION['user_role'] ?? '';
$is_superadmin = ($user_role === 'superadmin');

// Destroy all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect based on user role with timeout message if applicable
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';

if ($is_superadmin) {
    // Super admin should go to super admin login page
    if ($timeout) {
        header('Location: ' . $protocol . '://' . $host . '/admin/auth/superadmin_login.php?message=timeout');
    } else {
        header('Location: ' . $protocol . '://' . $host . '/admin/auth/superadmin_login.php');
    }
} else {
    // Regular admin should go to admin login page
    if ($timeout) {
        header('Location: ' . $protocol . '://' . $host . '/admin?message=timeout');
    } else {
        header('Location: ' . $protocol . '://' . $host . '/admin');
    }
}
exit();
?>
