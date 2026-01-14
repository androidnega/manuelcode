<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and session manager
include '../includes/db.php';
include '../includes/session_manager.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not logged in, redirect to login page
    // Use absolute path to avoid redirect loops
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
    header('Location: ' . $protocol . '://' . $host . '/admin');
    exit();
}

// Include admin configuration
include 'config.php';

// Check user role - ensure this is a superadmin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    // This is not a superadmin, redirect to appropriate login
    $sessionManager->destroySession();
    // Use absolute path to avoid redirect loops
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
    header('Location: ' . $protocol . '://' . $host . '/admin?error=invalid_role');
    exit();
}

// Superadmin is authenticated and has correct role, continue with the page
?>
