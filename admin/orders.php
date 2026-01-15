<?php
// Include admin authentication check or super admin access
if (isset($_SESSION['superadmin_access']) && $_SESSION['superadmin_access']) {
    // Super admin has access
    include_once '../includes/db.php';
} else {
    // Regular admin authentication
    include 'auth/check_auth.php';
    include_once '../includes/db.php';
}
include_once '../includes/otp_helper.php';
include_once '../includes/analytics_helper.php';

$admin_username = $_SESSION['admin_name'] ?? 'Admin';

// Handle order deletion
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_order'])) {
        $order_id = (int)$_POST['order_id'];
        $order_type = $_POST['order_type'];
        
        try {
            if ($order_type === 'user') {
                // Delete user order from purchases table
                $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    $success_message = 'User order deleted successfully!';
                } else {
                    $error_message = 'Failed to delete user order.';
                }
            } else {
                // Delete guest order from guest_orders table
                $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    $success_message = 'Guest order deleted successfully!';
                } else {
                    $error_message = 'Failed to delete guest order.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Note: Order status is automatically managed by the payment system
// Orders are set to 'paid' only when payment is successful
// No manual status updates are allowed to maintain data integrity

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';
$order_type = $_GET['order_type'] ?? 'all'; // all, user, guest

// Build the query for regular purchases with filters
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_term)) {
    $where_conditions[] = "(pr.title LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(p.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $where_conditions[] = "p.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get regular orders with filters
$user_orders_query = "
    SELECT 
        p.*, 
        pr.title as product_title, 
        pr.price as product_price, 
        p.amount, 
        p.original_amount,
        p.discount_amount,
        p.coupon_code,
        u.name as user_name, 
        u.email as user_email, 
        u.phone as user_phone, 
        u.user_id, 
        'user' as order_type,
        COALESCE(pl.download_count, 0) as download_count,
        pl.last_downloaded,
        CASE 
            WHEN p.amount = 0 AND p.discount_amount > 0 THEN 'FREE with coupon'
            WHEN p.amount = 0 AND p.discount_amount = 0 THEN 'FREE'
            ELSE 'Paid'
        END as purchase_type
    FROM purchases p 
    JOIN products pr ON p.product_id = pr.id 
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN purchase_logs pl ON p.id = pl.purchase_id
    $where_clause
";

// Build the query for guest orders with filters
$guest_where_conditions = [];
$guest_params = [];

if (!empty($status_filter)) {
    $guest_where_conditions[] = "go.status = ?";
    $guest_params[] = $status_filter;
}

if (!empty($search_term)) {
    $guest_where_conditions[] = "(pr.title LIKE ? OR go.name LIKE ? OR go.email LIKE ?)";
    $search_param = "%$search_term%";
    $guest_params[] = $search_param;
    $guest_params[] = $search_param;
    $guest_params[] = $search_param;
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $guest_where_conditions[] = "DATE(go.created_at) = CURDATE()";
            break;
        case 'week':
            $guest_where_conditions[] = "go.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $guest_where_conditions[] = "go.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $guest_where_conditions[] = "go.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

$guest_where_clause = !empty($guest_where_conditions) ? 'WHERE ' . implode(' AND ', $guest_where_conditions) : '';

// Get guest orders with filters
$guest_orders_query = "
    SELECT 
        go.*, 
        pr.title as product_title, 
        pr.price as product_price,
        go.amount,
        go.original_amount,
        go.discount_amount,
        go.coupon_code,
        go.name as user_name, 
        go.email as user_email, 
        go.phone as user_phone, 
        go.unique_id as user_id, 
        'guest' as order_type,
        COALESCE(pl.download_count, 0) as download_count,
        pl.last_downloaded,
        CASE 
            WHEN go.amount = 0 AND go.discount_amount > 0 THEN 'FREE with coupon'
            WHEN go.amount = 0 AND go.discount_amount = 0 THEN 'FREE'
            ELSE 'Paid'
        END as purchase_type
    FROM guest_orders go 
    JOIN products pr ON go.product_id = pr.id 
    LEFT JOIN purchase_logs pl ON go.id = pl.purchase_id
    $guest_where_clause
";

// Combine orders based on filter
$all_orders = [];

if ($order_type === 'all' || $order_type === 'user') {
    $stmt = $pdo->prepare($user_orders_query);
    $stmt->execute($params);
    $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $all_orders = array_merge($all_orders, $user_orders);
}

if ($order_type === 'all' || $order_type === 'guest') {
    $stmt = $pdo->prepare($guest_orders_query);
    $stmt->execute($guest_params);
    $guest_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $all_orders = array_merge($all_orders, $guest_orders);
}

// Sort all orders by date
usort($all_orders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Group orders by date for history view
$grouped_orders = [];
foreach ($all_orders as $order) {
    $date = date('Y-m-d', strtotime($order['created_at']));
    if (!isset($grouped_orders[$date])) {
        $grouped_orders[$date] = [];
    }
    $grouped_orders[$date][] = $order;
}

// Get statistics - only count paid orders since that's the only valid status
$total_orders = count($all_orders);

// Calculate filtered revenue (based on current filters)
$filtered_revenue = 0;
foreach ($all_orders as $order) {
    if ($order['order_type'] === 'user') {
        // For user orders, use amount if available, otherwise use price
        $filtered_revenue += $order['amount'] ?? $order['price'];
    } else {
        // For guest orders, use total_amount
        $filtered_revenue += $order['total_amount'] ?? $order['price'];
    }
}

// Use standardized revenue calculation for consistency with other pages
$total_revenue = getTotalRevenue($pdo);

$paid_orders = count(array_filter($all_orders, function($order) { return $order['status'] === 'paid'; }));
$completed_orders = $paid_orders; // All orders shown are completed/paid

// Count by type
$user_orders_count = count(array_filter($all_orders, function($order) { return $order['order_type'] === 'user'; }));
$guest_orders_count = count(array_filter($all_orders, function($order) { return $order['order_type'] === 'guest'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders Management - Admin</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="../assets/js/session-timeout.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

  <!-- Layout Container -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-700 to-slate-800 text-white transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen transition-transform duration-300 ease-in-out shadow-xl">
      <div class="flex items-center justify-between p-4 lg:p-6 border-b border-slate-600">
        <div class="font-bold text-lg lg:text-xl text-white">Admin Panel</div>
        <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-slate-300 transition-colors p-2">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto">
        <nav class="mt-4 px-2 pb-4 space-y-1">
          <a href="../dashboard/" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Dashboard</span>
          </a>
          <a href="../dashboard/products" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-box mr-3 w-5 text-center"></i>
            <span class="flex-1">Products</span>
          </a>
          <a href="../dashboard/projects" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
            <span class="flex-1">Projects</span>
          </a>
          <a href="../dashboard/orders" class="flex items-center py-3 px-4 bg-slate-600 rounded-lg transition-colors w-full text-white">
            <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
            <span class="flex-1">Orders</span>
          </a>
          <a href="../dashboard/purchase-management" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
            <span class="flex-1">Purchase Management</span>
          </a>
          <a href="../dashboard/users" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span class="flex-1">Users</span>
          </a>
          <a href="../dashboard/reports" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
            <span class="flex-1">Reports</span>
          </a>
          <a href="../dashboard/refunds-admin" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-undo mr-3 w-5 text-center"></i>
            <span class="flex-1">Refunds</span>
          </a>
          <a href="../dashboard/support-management" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-headset mr-3 w-5 text-center"></i>
            <span class="flex-1">Support Management</span>
          </a>
          <a href="../dashboard/change-password" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-key mr-3 w-5 text-center"></i>
            <span class="flex-1">Change Password</span>
          </a>
          <a href="../dashboard/generate-receipts" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-receipt mr-3 w-5 text-center"></i>
            <span class="flex-1">Generate Receipts</span>
          </a>
          <?php if (($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
          <a href="../dashboard/superadmin" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
            <span class="flex-1">Super Admin</span>
          </a>
          <?php endif; ?>
        </nav>
      </div>
      
      <div class="p-4 border-t border-slate-600">
        <a href="auth/logout.php" class="flex items-center py-3 px-4 text-red-300 hover:bg-slate-600 rounded-lg transition-colors">
          <i class="fas fa-sign-out-alt mr-3"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent">Orders Management</h1>
            <p class="text-slate-600 mt-1">View all successful customer orders and payments. Orders are automatically marked as paid when payment is successful.</p>
          </div>

        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="flex items-center justify-between p-4">
          <button onclick="toggleSidebar()" class="text-slate-600 hover:text-slate-900 p-2 transition-colors">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="text-lg font-semibold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent">Orders</h1>
          <div class="w-8"></div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="flex-1 p-4 lg:p-6 overflow-x-hidden">
        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
          <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex">
              <i class="fas fa-check-circle mt-1 mr-3"></i>
              <p class="text-sm">Order information updated successfully!</p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Order Deletion Messages -->
        <?php if ($success_message): ?>
          <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex">
              <i class="fas fa-check-circle mt-1 mr-3"></i>
              <p class="text-sm"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex">
              <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
              <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-blue-100 rounded-lg">
                <i class="fas fa-shopping-cart text-blue-600 text-base lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4">
                <p class="text-xs lg:text-sm font-medium text-gray-600">Total Orders</p>
                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo $total_orders; ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-green-100 rounded-lg">
                <i class="fas fa-money-bill-wave text-green-600 text-base lg:text-xl"></i>
              </div>
                             <div class="ml-3 lg:ml-4">
                 <p class="text-xs lg:text-sm font-medium text-gray-600">Total Revenue</p>
                 <p class="text-lg lg:text-2xl font-bold text-gray-900">GHS <?php echo number_format($total_revenue, 2); ?></p>
                 <?php if ($filtered_revenue != $total_revenue): ?>
                   <p class="text-xs text-gray-500">Filtered: GHS <?php echo number_format($filtered_revenue, 2); ?></p>
                 <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-blue-100 rounded-lg">
                <i class="fas fa-users text-blue-600 text-base lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4">
                <p class="text-xs lg:text-sm font-medium text-gray-600">User Orders</p>
                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo $user_orders_count; ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-purple-100 rounded-lg">
                <i class="fas fa-user-clock text-purple-600 text-base lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4">
                <p class="text-xs lg:text-sm font-medium text-gray-600">Guest Orders</p>
                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo $guest_orders_count; ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Live Search & Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
          <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base lg:text-lg font-semibold text-gray-800">Live Search & Filters</h2>
            <p class="text-xs text-gray-600 mt-1">Search and filter orders in real-time</p>
          </div>
          <div class="p-4 lg:p-6">
            <div class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                  <label for="search" class="block text-xs lg:text-sm font-medium text-gray-700 mb-2">Search</label>
                  <input type="text" id="search" 
                         placeholder="Search by product, customer name, or email..."
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                
                <div>
                  <label for="status" class="block text-xs lg:text-sm font-medium text-gray-700 mb-2">Status</label>
                  <select id="status" name="status" class="filter-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">All Orders</option>
                    <option value="paid">Paid Orders</option>
                  </select>
                </div>
                
                <div>
                  <label for="date" class="block text-xs lg:text-sm font-medium text-gray-700 mb-2">Date Range</label>
                  <select id="date" name="date" class="filter-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">Last 7 Days</option>
                    <option value="month">Last 30 Days</option>
                    <option value="year">Last Year</option>
                  </select>
                </div>

                <div>
                  <label for="order_type" class="block text-xs lg:text-sm font-medium text-gray-700 mb-2">Order Type</label>
                  <select id="order_type" name="order_type" class="filter-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">All Orders</option>
                    <option value="user">User Orders</option>
                    <option value="guest">Guest Orders</option>
                  </select>
                </div>
              </div>
              
              <div class="flex flex-col sm:flex-row gap-2">
                <button type="button" class="clear-filters bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors text-sm min-h-[40px] text-center flex items-center justify-center">
                  <i class="fas fa-times mr-2"></i>Clear All Filters
                </button>
                <div class="results-count text-sm text-gray-600 flex items-center">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Ready to search</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
            <h2 class="text-base lg:text-lg font-semibold text-gray-800">Orders History</h2>
            <p class="text-xs lg:text-sm text-gray-600 mt-1">Showing <?php echo $total_orders; ?> successful orders (all paid)</p>
          </div>
          <div class="p-4 lg:p-6">
            <!-- No Results Message (Hidden by default) -->
            <div class="no-results text-center py-8" style="display: none;">
              <i class="fas fa-search text-3xl lg:text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-600 text-sm lg:text-base">No orders found</p>
              <p class="text-xs lg:text-sm text-gray-500">Try adjusting your filters or search terms.</p>
            </div>
            
            <?php if (empty($all_orders)): ?>
              <div class="text-center py-8">
                <i class="fas fa-shopping-cart text-3xl lg:text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 text-sm lg:text-base">No orders found</p>
                <p class="text-xs lg:text-sm text-gray-500">Try adjusting your filters or search terms.</p>
              </div>
            <?php else: ?>
              <!-- Grouped by Date -->
              <?php foreach ($grouped_orders as $date => $date_orders): ?>
                <div class="mb-6 lg:mb-8">
                  <h3 class="text-base lg:text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                    <?php echo date('l, F j, Y', strtotime($date)); ?>
                    <span class="text-xs lg:text-sm font-normal text-gray-500">(<?php echo count($date_orders); ?> orders)</span>
                  </h3>
                  
                  <!-- Desktop Table View -->
                  <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full min-w-[1000px]">
                      <thead>
                        <tr class="border-b border-gray-200">
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Order ID</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Product</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Customer</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Amount</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Coupon</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Downloads</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Status</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Date</th>
                          <th class="text-left py-3 px-4 font-medium text-gray-700 text-sm">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($date_orders as $order): ?>
                          <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                              data-status="paid"
                              data-order-type="<?php echo $order['order_type']; ?>"
                              data-date="<?php echo date('Y-m-d', strtotime($order['created_at'])); ?>">
                            <td class="py-4 px-4">
                              <div>
                                <span class="font-mono text-sm text-gray-600">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                <?php if ($order['order_type'] === 'guest'): ?>
                                  <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Guest</span>
                                <?php else: ?>
                                  <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">User</span>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td class="py-4 px-4">
                              <div>
                                <div class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($order['product_title']); ?></div>
                                <div class="text-xs text-gray-500">ID: <?php echo $order['product_id']; ?></div>
                              </div>
                            </td>
                            <td class="py-4 px-4">
                              <div>
                                <div class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></div>
                                <?php if ($order['user_id']): ?>
                                  <div class="text-xs text-gray-500 font-mono">ID: <?php echo htmlspecialchars($order['user_id']); ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['user_email'] ?? 'No email'); ?></div>
                                <?php if ($order['user_phone']): ?>
                                  <div class="text-xs text-gray-500"><?php echo format_phone_for_display($order['user_phone']); ?></div>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td class="py-4 px-4 text-green-600 font-semibold text-sm">
                              <?php 
                              $purchase_type = $order['purchase_type'] ?? 'Paid';
                              $original_amount = $order['original_amount'] ?? $order['product_price'];
                              $discount_amount = $order['discount_amount'] ?? 0;
                              $paid_amount = $order['amount'] ?? 0;
                              
                              if ($purchase_type === 'FREE with coupon') {
                                  echo '<span class="text-green-600 font-bold">FREE</span><br>';
                                  echo '<span class="text-xs text-gray-500 line-through">₵' . number_format($original_amount, 2) . '</span><br>';
                                  echo '<span class="text-xs text-blue-600">Coupon applied</span>';
                              } elseif ($purchase_type === 'FREE') {
                                  echo '<span class="text-green-600 font-bold">FREE</span>';
                              } else {
                                  if ($discount_amount > 0) {
                                      echo '<span class="text-green-600">₵' . number_format($paid_amount, 2) . '</span><br>';
                                      echo '<span class="text-xs text-gray-500 line-through">₵' . number_format($original_amount, 2) . '</span><br>';
                                      echo '<span class="text-xs text-blue-600">-₵' . number_format($discount_amount, 2) . '</span>';
                                  } else {
                                      echo 'GHS ' . number_format($original_amount, 2);
                                  }
                              }
                              ?>
                            </td>
                            <td class="py-4 px-4">
                              <?php if ($order['coupon_code']): ?>
                                <div class="text-xs">
                                  <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">
                                    <?php echo htmlspecialchars($order['coupon_code']); ?>
                                  </span>
                                  <?php if ($discount_amount > 0): ?>
                                    <div class="text-xs text-green-600 mt-1">
                                      -₵<?php echo number_format($discount_amount, 2); ?>
                                    </div>
                                  <?php endif; ?>
                                </div>
                              <?php else: ?>
                                <span class="text-xs text-gray-500">No coupon</span>
                              <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                              <div class="text-xs">
                                <div class="font-medium text-gray-900">
                                  <?php echo $order['download_count'] ?? 0; ?> downloads
                                </div>
                                <?php if ($order['last_downloaded']): ?>
                                  <div class="text-gray-500">
                                    Last: <?php echo date('M j, Y', strtotime($order['last_downloaded'])); ?>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td class="py-4 px-4">
                              <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full font-medium">
                                ✅ Paid
                              </span>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-500">
                              <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="py-4 px-4">
                              <div class="flex space-x-2">
                                 <button onclick="showUserOrderModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                         class="text-blue-600 hover:text-blue-800" title="View Order Details">
                                   <i class="fas fa-<?php echo $order['user_id'] ? 'user' : 'user-clock'; ?> text-sm"></i>
                                 </button>
                                <a href="/product?id=<?php echo $order['product_id']; ?>" 
                                   target="_blank"
                                   class="text-green-600 hover:text-green-800" title="View Product">
                                   <i class="fas fa-eye text-sm"></i>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                                  <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                  <input type="hidden" name="order_type" value="<?php echo $order['order_type']; ?>">
                                  <button type="submit" name="delete_order" 
                                          class="text-red-600 hover:text-red-800" title="Delete Order">
                                    <i class="fas fa-trash text-sm"></i>
                                  </button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- Mobile Card View -->
                  <div class="lg:hidden space-y-4">
                    <?php foreach ($date_orders as $order): ?>
                      <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-lg border border-slate-200 p-4 shadow-sm">
                        <!-- Order Header -->
                        <div class="flex items-center justify-between mb-3">
                          <div class="flex items-center space-x-2">
                            <span class="font-mono text-sm font-semibold text-slate-700">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <?php if ($order['order_type'] === 'guest'): ?>
                              <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full font-medium">Guest</span>
                            <?php else: ?>
                              <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full font-medium">User</span>
                            <?php endif; ?>
                          </div>
                          <div class="text-right">
                            <?php 
                            $purchase_type = $order['purchase_type'] ?? 'Paid';
                            $original_amount = $order['original_amount'] ?? $order['product_price'];
                            $discount_amount = $order['discount_amount'] ?? 0;
                            $paid_amount = $order['amount'] ?? 0;
                            
                            if ($purchase_type === 'FREE with coupon') {
                                echo '<div class="text-lg font-bold text-green-600">FREE</div>';
                                echo '<div class="text-xs text-gray-500 line-through">₵' . number_format($original_amount, 2) . '</div>';
                                echo '<div class="text-xs text-blue-600">Coupon applied</div>';
                            } elseif ($purchase_type === 'FREE') {
                                echo '<div class="text-lg font-bold text-green-600">FREE</div>';
                            } else {
                                if ($discount_amount > 0) {
                                    echo '<div class="text-lg font-bold text-emerald-600">₵' . number_format($paid_amount, 2) . '</div>';
                                    echo '<div class="text-xs text-gray-500 line-through">₵' . number_format($original_amount, 2) . '</div>';
                                    echo '<div class="text-xs text-blue-600">-₵' . number_format($discount_amount, 2) . '</div>';
                                } else {
                                    echo '<div class="text-lg font-bold text-emerald-600">GHS ' . number_format($original_amount, 2) . '</div>';
                                }
                            }
                            ?>
                            <div class="text-xs text-slate-500"><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></div>
                          </div>
                        </div>

                        <!-- Product Information -->
                        <div class="bg-white rounded-lg p-3 mb-3 border border-slate-100">
                          <div class="flex items-start justify-between">
                            <div class="flex-1">
                              <h4 class="font-semibold text-slate-800 text-sm mb-1"><?php echo htmlspecialchars($order['product_title']); ?></h4>
                              <p class="text-xs text-slate-600">Product ID: <?php echo $order['product_id']; ?></p>
                            </div>
                            <a href="/product?id=<?php echo $order['product_id']; ?>" 
                               target="_blank"
                               class="ml-2 p-2 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition-colors">
                              <i class="fas fa-eye text-sm"></i>
                            </a>
                          </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="bg-white rounded-lg p-3 mb-3 border border-slate-100">
                          <h5 class="font-medium text-slate-700 text-sm mb-2 flex items-center">
                            <i class="fas fa-user mr-2 text-slate-500"></i>
                            Customer Details
                          </h5>
                          <div class="space-y-1">
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">Name:</span>
                              <span class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></span>
                            </div>
                            <?php if ($order['user_id']): ?>
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">User ID:</span>
                              <span class="text-xs font-mono text-slate-700"><?php echo htmlspecialchars($order['user_id']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">Email:</span>
                              <span class="text-xs text-slate-700 truncate max-w-[150px]"><?php echo htmlspecialchars($order['user_email'] ?? 'No email'); ?></span>
                            </div>
                            <?php if ($order['user_phone']): ?>
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">Phone:</span>
                              <span class="text-xs text-slate-700"><?php echo format_phone_for_display($order['user_phone']); ?></span>
                            </div>
                            <?php endif; ?>
                                                         <div class="mt-2 pt-2 border-t border-slate-100">
                               <div class="flex space-x-2">
                                 <button onclick="showUserOrderModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                         class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs hover:bg-blue-200 transition-colors">
                                   <i class="fas fa-<?php echo $order['user_id'] ? 'user' : 'user-clock'; ?> mr-1"></i>
                                   View Order Details
                                 </button>
                                 <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                                   <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                   <input type="hidden" name="order_type" value="<?php echo $order['order_type']; ?>">
                                   <button type="submit" name="delete_order" 
                                           class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs hover:bg-red-200 transition-colors">
                                     <i class="fas fa-trash mr-1"></i>
                                     Delete
                                   </button>
                                 </form>
                               </div>
                             </div>
                          </div>
                        </div>

                        <!-- Coupon & Download Information -->
                        <div class="bg-white rounded-lg p-3 mb-3 border border-slate-100">
                          <h5 class="font-medium text-slate-700 text-sm mb-2 flex items-center">
                            <i class="fas fa-tag mr-2 text-blue-500"></i>
                            Coupon & Downloads
                          </h5>
                          <div class="space-y-2">
                            <!-- Coupon Information -->
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">Coupon:</span>
                              <?php if ($order['coupon_code']): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                  <?php echo htmlspecialchars($order['coupon_code']); ?>
                                </span>
                              <?php else: ?>
                                <span class="text-xs text-slate-500">No coupon</span>
                              <?php endif; ?>
                            </div>
                            
                            <!-- Download Information -->
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">Downloads:</span>
                              <span class="text-sm font-medium text-slate-800">
                                <?php echo $order['download_count'] ?? 0; ?> times
                              </span>
                            </div>
                            
                            <?php if ($order['last_downloaded']): ?>
                            <div class="flex items-center justify-between">
                              <span class="text-xs text-slate-600">Last Download:</span>
                              <span class="text-xs text-slate-700">
                                <?php echo date('M j, Y', strtotime($order['last_downloaded'])); ?>
                              </span>
                            </div>
                            <?php endif; ?>
                          </div>
                        </div>

                        <!-- Order Status -->
                         <div class="bg-white rounded-lg p-3 border border-slate-100">
                           <h5 class="font-medium text-slate-700 text-sm mb-2 flex items-center">
                             <i class="fas fa-check-circle mr-2 text-green-500"></i>
                             Payment Status
                           </h5>
                           <div class="flex items-center justify-center">
                             <span class="px-3 py-2 text-sm bg-green-100 text-green-800 rounded-full font-medium">
                               ✅ Payment Successful - Order Paid
                             </span>
                           </div>
                         </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- User Order Details Modal -->
  <div id="userOrderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Order Details</h3>
        <button onclick="closeUserOrderModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <!-- Modal Content -->
      <div class="p-6">
        <div id="modalContent">
          <!-- Content will be populated by JavaScript -->
        </div>
      </div>
      
      <!-- Modal Footer -->
      <div class="flex items-center justify-end p-6 border-t border-gray-200">
        <button onclick="closeUserOrderModal()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
          Close
        </button>
      </div>
    </div>
  </div>

  <script src="assets/js/live-search.js"></script>
  <script>
    // Helper function to safely format prices
    function formatPrice(price) {
      if (price === null || price === undefined || price === '') {
        return '0.00';
      }
      const numPrice = parseFloat(price);
      if (isNaN(numPrice)) {
        return '0.00';
      }
      return numPrice.toFixed(2);
    }
    
    // Helper function to safely format dates
    function formatDate(dateString) {
      if (!dateString) {
        return 'N/A';
      }
      try {
        return new Date(dateString).toLocaleDateString('en-US', { 
          weekday: 'long', 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch (e) {
        return 'Invalid Date';
      }
    }
    
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('mobile-overlay');
      
      if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
      }
    }

    function showUserOrderModal(orderData) {
      const modal = document.getElementById('userOrderModal');
      const modalContent = document.getElementById('modalContent');
      
      // Debug logging
      console.log('Order data received:', orderData);
      
      // Validate order data
      if (!orderData || typeof orderData !== 'object') {
        console.error('Invalid order data received:', orderData);
        modalContent.innerHTML = '<div class="text-red-600 p-4">Error: Invalid order data received</div>';
        modal.classList.remove('hidden');
        return;
      }
      
      // Determine if this is a guest or user order
      const isGuestOrder = orderData.order_type === 'guest';
      const orderTypeLabel = isGuestOrder ? 'Guest Order' : 'User Order';
      const orderTypeClass = isGuestOrder ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
      
      // Format the order data for display
      const content = `
        <div class="space-y-6">
          <!-- Order Header -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-lg font-semibold text-gray-900">Order #${(orderData.id || 0).toString().padStart(4, '0')}</h4>
                <p class="text-sm text-gray-600">${formatDate(orderData.created_at)}</p>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-green-600">GHS ${formatPrice(orderData.amount)}</div>
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">✅ Paid</span>
              </div>
            </div>
          </div>



          <!-- Product Information -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
              <i class="fas fa-box mr-2 text-green-500"></i>
              Product Information
            </h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Product Title</label>
                <p class="text-sm text-gray-900 font-medium">${orderData.product_title || 'N/A'}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Product ID</label>
                <p class="text-sm text-gray-900 font-mono">${orderData.product_id || 'N/A'}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Final Amount</label>
                <p class="text-sm text-gray-900 font-semibold">GHS ${formatPrice(orderData.amount)}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Order Type</label>
                <span class="px-2 py-1 text-xs ${orderTypeClass} rounded-full">
                  ${orderData.purchase_type || orderTypeLabel}
                </span>
              </div>
            </div>
            
            <!-- Additional Price Information -->
            ${orderData.original_amount || orderData.discount_amount ? `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200">
              ${orderData.original_amount ? `
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Original Price</label>
                <p class="text-sm text-gray-900 line-through">GHS ${formatPrice(orderData.original_amount)}</p>
              </div>
              ` : ''}
              ${orderData.discount_amount ? `
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Discount Applied</label>
                <p class="text-sm text-green-600">-GHS ${formatPrice(orderData.discount_amount)}</p>
              </div>
              ` : ''}
              ${orderData.coupon_code ? `
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Coupon Code</label>
                <p class="text-sm text-blue-600 font-mono">${orderData.coupon_code}</p>
              </div>
              ` : ''}
            </div>
            ` : ''}
            </div>
          </div>

          <!-- Payment Information -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
              <i class="fas fa-credit-card mr-2 text-purple-500"></i>
              Payment Information
            </h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Payment Status</label>
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full font-medium">✅ Payment Successful</span>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Payment Method</label>
                <p class="text-sm text-gray-900">Paystack</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Order Date</label>
                <p class="text-sm text-gray-900">${new Date(orderData.created_at).toLocaleDateString() || 'N/A'}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Order Time</label>
                <p class="text-sm text-gray-900">${new Date(orderData.created_at).toLocaleTimeString() || 'N/A'}</p>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex space-x-3">
            ${orderData.product_id ? `
            <a href="/product?id=${orderData.product_id}" target="_blank" 
               class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-center">
              <i class="fas fa-eye mr-2"></i>
              View Product
            </a>
            ` : `
            <button disabled class="flex-1 bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed text-center">
              <i class="fas fa-eye mr-2"></i>
              Product Not Available
            </button>
            `}
            ${orderData.user_id && orderData.user_id !== 'N/A' ? `
            <a href="../dashboard/view-user?view=${orderData.user_id}" 
               class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center">
              <i class="fas fa-user mr-2"></i>
              View Customer Profile
            </a>
            ` : `
            <button disabled class="flex-1 bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed text-center">
              <i class="fas fa-user mr-2"></i>
              Guest Order
            </button>
            `}
          </div>
        </div>
      `;
      
      modalContent.innerHTML = content;
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeUserOrderModal() {
      const modal = document.getElementById('userOrderModal');
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }

    // Close modal when clicking outside
    document.getElementById('userOrderModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeUserOrderModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeUserOrderModal();
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      const sidebarLinks = document.querySelectorAll('#sidebar a');
      sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth < 1024) {
            toggleSidebar();
          }
        });
      });

      window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
          const sidebar = document.getElementById('sidebar');
          const overlay = document.getElementById('mobile-overlay');
          sidebar.classList.remove('-translate-x-full');
          overlay.classList.add('hidden');
          document.body.style.overflow = '';
        }
      });
    });
  </script>
</body>
</html>
