<?php
/**
 * Universal Dashboard Router
 * Routes clean URLs to appropriate admin or user dashboard pages
 */

session_start();

// Get the route from query string
$route = $_GET['route'] ?? '';

// Remove trailing slash
$route = rtrim($route, '/');

// Set global variable for current page detection in included files
$_SESSION['current_route'] = $route;
$_GLOBALS['current_route'] = $route;

// Route mapping: slug => [file_path, auth_type, required_role]
$routes = [
    // User Dashboard Routes
    '' => ['dashboard/index.php', 'user', null],
    'index' => ['dashboard/index.php', 'user', null],
    'my-purchases' => ['dashboard/my_purchases.php', 'user', null],
    'downloads' => ['dashboard/downloads.php', 'user', null],
    'receipts' => ['dashboard/receipts.php', 'user', null],
    'refunds' => ['dashboard/refunds.php', 'user', null],
    'settings' => ['dashboard/settings.php', 'user', null],
    'support' => ['dashboard/support.php', 'user', null],
    'notifications' => ['dashboard/notifications.php', 'user', null],
    
    // Admin Dashboard Routes
    'admin' => ['admin/dashboard.php', 'admin', 'admin'],
    'admin-dashboard' => ['admin/dashboard.php', 'admin', 'admin'],
    'purchase-management' => ['admin/purchase_management.php', 'admin', 'admin'],
    'products' => ['admin/products.php', 'admin', 'admin'],
    'add-product' => ['admin/add_product.php', 'admin', 'admin'],
    'edit-product' => ['admin/edit_product.php', 'admin', 'admin'],
    'projects' => ['admin/projects.php', 'admin', 'admin'],
    'add-project' => ['admin/add_project.php', 'admin', 'admin'],
    'edit-project' => ['admin/edit_project.php', 'admin', 'admin'],
    'orders' => ['admin/orders.php', 'admin', 'admin'],
    'users' => ['admin/users.php', 'admin', 'admin'],
    'user-management' => ['admin/user_management.php', 'admin', 'admin'],
    'view-user' => ['admin/view_user.php', 'admin', 'admin'],
    'reports' => ['admin/reports.php', 'admin', 'admin'],
    'refunds-admin' => ['admin/refunds.php', 'admin', 'admin'],
    'change-password' => ['admin/change_password.php', 'admin', 'admin'],
    'support-management' => ['admin/support_management.php', 'admin', 'admin'],
    'view-ticket' => ['admin/view_ticket.php', 'admin', 'admin'],
    'generate-receipts' => ['admin/generate_receipts.php', 'admin', 'admin'],
    'view-receipt' => ['admin/view_receipt.php', 'admin', 'admin'],
    'download-receipts' => ['admin/download_receipts.php', 'admin', 'admin'],
    'coupons' => ['admin/coupons.php', 'admin', 'admin'],
    'site-analytics' => ['admin/site_analytics.php', 'admin', 'admin'],
    'user-activity' => ['admin/user_activity.php', 'admin', 'admin'],
    'quotes-enhanced' => ['admin/quotes_enhanced.php', 'admin', 'admin'],
    'quote-analytics' => ['admin/quote_analytics.php', 'admin', 'admin'],
    'product-updates' => ['admin/product_updates.php', 'admin', 'admin'],
    'balance' => ['admin/balance.php', 'admin', 'admin'],
    'digital-signature' => ['admin/digital_signature.php', 'admin', 'admin'],
    'seo-management' => ['admin/seo_management.php', 'admin', 'admin'],
    'admin-messaging' => ['admin/admin_messaging.php', 'admin', 'admin'],
    
    // Superadmin Routes
    'superadmin' => ['admin/superadmin.php', 'admin', 'superadmin'],
    'superadmin-dashboard' => ['admin/superadmin.php', 'admin', 'superadmin'],
    'superadmin-settings' => ['admin/superadmin_settings.php', 'admin', 'superadmin'],
    'superadmin-tools' => ['admin/superadmin_tools.php', 'admin', 'superadmin'],
    'manage-admins' => ['admin/manage_admins.php', 'admin', 'superadmin'],
    'edit-admin' => ['admin/edit_admin.php', 'admin', 'superadmin'],
    
    // Support Routes
    'support-dashboard' => ['admin/support_dashboard.php', 'support', 'support'],
    'support-tickets' => ['admin/support_tickets.php', 'support', 'support'],
    'support-open-tickets' => ['admin/support_open_tickets.php', 'support', 'support'],
    'support-closed-tickets' => ['admin/support_closed_tickets.php', 'support', 'support'],
    'support-dashboard-old' => ['admin/support_dashboard.php', 'support', 'support'], // Legacy route
];

// Handle empty route (dashboard root)
if (empty($route)) {
    // Check if user is admin or regular user and redirect accordingly
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $route = 'admin-dashboard';
    } else {
        $route = 'index';
    }
}

// Check if route exists
if (!isset($routes[$route])) {
    http_response_code(404);
    die('Page not found');
}

// Get route configuration
[$file_path, $auth_type, $required_role] = $routes[$route];

// Check authentication based on route type
if ($auth_type === 'admin') {
    // Check admin authentication
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ../admin');
        exit;
    }
    
    // Check role if required
    if ($required_role !== null) {
        $user_role = $_SESSION['user_role'] ?? '';
        if ($required_role === 'superadmin' && $user_role !== 'superadmin') {
            header('Location: ../admin?error=access_denied');
            exit;
        } elseif ($required_role === 'admin' && $user_role !== 'admin' && $user_role !== 'superadmin') {
            header('Location: ../admin?error=access_denied');
            exit;
        } elseif ($required_role === 'support' && $user_role !== 'support') {
            header('Location: ../admin?error=access_denied&type=support');
            exit;
        }
    }
} elseif ($auth_type === 'user') {
    // Check user authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login');
        exit;
    }
}

// Build full file path
$full_path = __DIR__ . '/../' . $file_path;

// Check if file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    die('Page file not found: ' . $file_path);
}

// Include the requested file
include $full_path;

