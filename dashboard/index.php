<?php
/**
 * Dashboard Index - Routes all requests to router.php
 * This is a fallback if .htaccess isn't working
 */

// Get the route from the URL
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$route = '';

// Extract route from /dashboard/route
if (preg_match('#^/dashboard/(.+)$#', $request_uri, $matches)) {
    $route = $matches[1];
    // Remove .php extension if present
    $route = preg_replace('/\.php$/', '', $route);
    // Remove query string
    $route = strtok($route, '?');
}

// If no route, check if we're at /dashboard
if (empty($route) && (strpos($request_uri, '/dashboard') !== false && $request_uri === '/dashboard' || $request_uri === '/dashboard/')) {
    $route = '';
}

// Redirect to router with route parameter
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
$query_string = $_SERVER['QUERY_STRING'] ?? '';
$redirect_url = $protocol . '://' . $host . '/dashboard/router.php?route=' . urlencode($route);
if ($query_string) {
    $redirect_url .= '&' . $query_string;
}

header('Location: ' . $redirect_url);
exit;
?>
