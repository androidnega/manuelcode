<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logout is due to timeout
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == '1';

// Destroy session
session_destroy();

// Redirect with timeout message if applicable
if ($timeout) {
    header("Location: login.php?message=timeout");
} else {
    header("Location: login.php");
}
exit;
?>
