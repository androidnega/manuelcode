<?php
/**
 * Route Handler - Routes all dashboard requests to router.php
 * This is a fallback if .htaccess isn't working
 * Access via: /dashboard/route_handler.php?route=my-purchases
 */

// Get the route from query string or REQUEST_URI
$route = $_GET['route'] ?? '';

// If route is empty, try to parse from REQUEST_URI
if (empty($route) && isset($_SERVER['REQUEST_URI'])) {
    $request_uri = $_SERVER['REQUEST_URI'];
    // Remove query string
    $request_uri = strtok($request_uri, '?');
    // Extract route from /dashboard/route
    if (preg_match('#^/dashboard/(.+)$#', $request_uri, $matches)) {
        $route = $matches[1];
        // Remove .php extension if present
        $route = preg_replace('/\.php$/', '', $route);
    }
}

// Include router
$_GET['route'] = $route;
include __DIR__ . '/router.php';
?>

