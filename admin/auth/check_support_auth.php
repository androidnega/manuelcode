<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and session manager
include '../includes/db.php';
include '../includes/session_manager.php';

// Check if support agent is logged in
if (!isset($_SESSION['support_logged_in']) || $_SESSION['support_logged_in'] !== true) {
    // Not logged in, redirect to login page
    header('Location: auth/support_login.php');
    exit();
}

// Include admin configuration
include 'config.php';

// Check user role - ensure this is a support agent
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'support') {
    // This is not a support agent, redirect to appropriate login
    $sessionManager->destroySession();
    header('Location: auth/support_login.php?error=invalid_role');
    exit();
}

// Support agent is authenticated and has correct role, continue with the page
?>
