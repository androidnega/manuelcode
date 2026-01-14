<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logout is due to timeout
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == '1';

// Destroy session
session_destroy();

// Redirect with timeout message if applicable
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';

if ($timeout) {
    header("Location: " . $protocol . "://" . $host . "/login?message=timeout");
} else {
    header("Location: " . $protocol . "://" . $host . "/login");
}
exit;
?>
