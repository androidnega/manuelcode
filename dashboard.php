<?php
/**
 * Dashboard Redirect
 * Redirects /dashboard to /dashboard/index.php
 */

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    header('Location: auth/login.php');
    exit;
}

// Redirect to the actual dashboard
header('Location: dashboard/index.php');
exit;
?>
