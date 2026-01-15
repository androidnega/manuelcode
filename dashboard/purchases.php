<?php
/**
 * Redirect /dashboard/purchases.php to /dashboard/my-purchases
 * This ensures backward compatibility with old URLs
 */

// Get the base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
$base_url = $protocol . '://' . $host;

// Redirect to the correct URL
header('Location: ' . $base_url . '/dashboard/my-purchases');
exit;
?>

