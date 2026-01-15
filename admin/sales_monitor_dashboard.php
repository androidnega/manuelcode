<?php
// Sales Monitor Dashboard - View purchases, prices, and user details
session_start();
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';

// Check if user is sales_monitor role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'sales_monitor') {
    header('Location: ../admin?error=access_denied');
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Sales Monitor';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'paid'; // Default to paid only
$search_term = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';
$order_type = $_GET['order_type'] ?? 'all'; // all, user, guest

// Build the query for regular purchases with filters
$where_conditions = ["p.status = 'paid'"]; // Only show paid purchases
$params = [];

if (!empty($search_term)) {
    $where_conditions[] = "(pr.title LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
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

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : 'WHERE p.status = "paid"';

// Get regular orders
$user_orders_query = "
    SELECT 
        p.*, 
        pr.title as product_title, 
        pr.price as product_price, 
        COALESCE(p.amount, pr.price) as purchase_amount,
        p.original_amount,
        p.discount_amount,
        u.id as user_id,
        u.name as user_name, 
        u.email as user_email, 
        u.phone as user_phone, 
        u.user_id as user_unique_id,
        'user' as order_type
    FROM purchases p 
    JOIN products pr ON p.product_id = pr.id 
    LEFT JOIN users u ON p.user_id = u.id
    $where_clause
    ORDER BY p.created_at DESC
    LIMIT 500
";

// Get guest orders
$guest_where_conditions = ["go.status = 'paid'"];
$guest_params = [];

if (!empty($search_term)) {
    $guest_where_conditions[] = "(pr.title LIKE ? OR go.name LIKE ? OR go.email LIKE ? OR go.phone LIKE ?)";
    $search_param = "%$search_term%";
    $guest_params[] = $search_param;
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

$guest_where_clause = !empty($guest_where_conditions) ? 'WHERE ' . implode(' AND ', $guest_where_conditions) : 'WHERE go.status = "paid"';

$guest_orders_query = "
    SELECT 
        go.*, 
        pr.title as product_title, 
        pr.price as product_price, 
        COALESCE(go.total_amount, pr.price) as purchase_amount,
        go.name as user_name,
        go.email as user_email,
        go.phone as user_phone,
        NULL as user_id,
        NULL as user_unique_id,
        'guest' as order_type
    FROM guest_orders go 
    JOIN products pr ON go.product_id = pr.id 
    $guest_where_clause
    ORDER BY go.created_at DESC
    LIMIT 500
";

try {
    // Execute user orders query
    if (!empty($params)) {
        $stmt = $pdo->prepare($user_orders_query);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($user_orders_query);
    }
    $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Execute guest orders query
    if (!empty($guest_params)) {
        $stmt = $pdo->prepare($guest_orders_query);
        $stmt->execute($guest_params);
    } else {
        $stmt = $pdo->query($guest_orders_query);
    }
    $guest_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine orders based on filter
    $all_orders = [];
    if ($order_type === 'all' || $order_type === 'user') {
        $all_orders = array_merge($all_orders, $user_orders);
    }
    if ($order_type === 'all' || $order_type === 'guest') {
        $all_orders = array_merge($all_orders, $guest_orders);
    }
    
    // Sort by date (newest first)
    usort($all_orders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Get statistics
    $total_purchases = count($all_orders);
    $total_revenue = array_sum(array_column($all_orders, 'purchase_amount'));
    $unique_users = count(array_unique(array_filter(array_column($all_orders, 'user_email'))));
    
    // Get today's stats
    $today_query = "
        SELECT 
            COUNT(*) as count,
            SUM(COALESCE(p.amount, pr.price)) as revenue
        FROM purchases p
        JOIN products pr ON p.product_id = pr.id
        WHERE p.status = 'paid' AND DATE(p.created_at) = CURDATE()
    ";
    $today_stats = $pdo->query($today_query)->fetch(PDO::FETCH_ASSOC);
    
    $today_guest_query = "
        SELECT 
            COUNT(*) as count,
            SUM(COALESCE(go.total_amount, pr.price)) as revenue
        FROM guest_orders go
        JOIN products pr ON go.product_id = pr.id
        WHERE go.status = 'paid' AND DATE(go.created_at) = CURDATE()
    ";
    $today_guest_stats = $pdo->query($today_guest_query)->fetch(PDO::FETCH_ASSOC);
    
    $today_purchases = ($today_stats['count'] ?? 0) + ($today_guest_stats['count'] ?? 0);
    $today_revenue = ($today_stats['revenue'] ?? 0) + ($today_guest_stats['revenue'] ?? 0);
    
} catch (Exception $e) {
    $all_orders = [];
    $error_message = 'Error loading purchases: ' . $e->getMessage();
    error_log("Sales Monitor Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Monitor Dashboard - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-blue-800 bg-clip-text text-transparent">
                        <i class="fas fa-chart-line mr-3 text-blue-600"></i>Sales Monitor Dashboard
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Monitor purchases, prices, and customer details</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                    <a href="../auth/logout.php" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-xl transition-all duration-200 flex items-center shadow-sm hover:shadow-md">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-6 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Purchases -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl shadow-sm border border-blue-200">
                <div class="flex items-center justify-between">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-shopping-cart text-xl text-white"></i>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-blue-600 opacity-80">Total Purchases</div>
                        <div class="text-2xl font-bold text-blue-800"><?php echo number_format($total_purchases); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Total Revenue -->
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 rounded-2xl border border-emerald-200">
                <div class="flex items-center justify-between">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-money-bill-wave text-xl text-white"></i>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-emerald-600 opacity-80">Total Revenue</div>
                        <div class="text-2xl font-bold text-emerald-800">GHS <?php echo number_format($total_revenue, 2); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Unique Customers -->
            <div class="bg-gradient-to-br from-violet-50 to-violet-100 p-6 rounded-2xl border border-violet-200">
                <div class="flex items-center justify-between">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-violet-400 to-violet-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-users text-xl text-white"></i>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-violet-600 opacity-80">Unique Customers</div>
                        <div class="text-2xl font-bold text-violet-800"><?php echo number_format($unique_users); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Today's Sales -->
            <div class="bg-gradient-to-br from-rose-50 to-rose-100 p-6 rounded-2xl border border-rose-200">
                <div class="flex items-center justify-between">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-calendar-day text-xl text-white"></i>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-rose-600 opacity-80">Today's Sales</div>
                        <div class="text-2xl font-bold text-rose-800"><?php echo number_format($today_purchases); ?></div>
                        <div class="text-xs text-rose-600 mt-1">GHS <?php echo number_format($today_revenue, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" 
                           placeholder="Product, name, email, phone..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Filter</label>
                    <select name="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Time</option>
                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="year" <?php echo $date_filter === 'year' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                    <select name="order_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $order_type === 'all' ? 'selected' : ''; ?>>All Orders</option>
                        <option value="user" <?php echo $order_type === 'user' ? 'selected' : ''; ?>>Registered Users</option>
                        <option value="guest" <?php echo $order_type === 'guest' ? 'selected' : ''; ?>>Guest Orders</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Purchases Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-list mr-2"></i>Purchase History (<?php echo count($all_orders); ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_orders)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No purchases found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($order['user_name'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?>
                                        </div>
                                        <?php if (!empty($order['user_phone'])): ?>
                                            <div class="text-xs text-gray-400">
                                                <?php echo htmlspecialchars(format_phone_for_display($order['user_phone'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($order['product_title']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Original: GHS <?php echo number_format($order['product_price'], 2); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        GHS <?php echo number_format($order['product_price'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-green-600">
                                            GHS <?php echo number_format($order['purchase_amount'] ?? $order['product_price'], 2); ?>
                                        </div>
                                        <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                            <div class="text-xs text-green-500">
                                                Discount: GHS <?php echo number_format($order['discount_amount'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php echo $order['order_type'] === 'user' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'; ?>">
                                            <?php echo ucfirst($order['order_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($order['order_type'] === 'user' && !empty($order['user_id'])): ?>
                                            <button onclick="viewUserDetails(<?php echo $order['user_id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-user-circle mr-1"></i>View Details
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400">Guest Order</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- User Details Modal -->
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">User Details</h3>
                <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="userModalContent" class="p-6">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i>
                    <p class="text-gray-600">Loading user details...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewUserDetails(userId) {
            const modal = document.getElementById('userModal');
            const content = document.getElementById('userModalContent');
            
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i><p class="text-gray-600">Loading user details...</p></div>';
            
            fetch(`../admin/get_user_details.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        const purchases = data.purchases || [];
                        
                        let html = `
                            <div class="space-y-6">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-3">User Information</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Name:</span>
                                            <span class="font-medium ml-2">${user.name || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Email:</span>
                                            <span class="font-medium ml-2">${user.email || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Phone:</span>
                                            <span class="font-medium ml-2">${user.phone || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">User ID:</span>
                                            <span class="font-medium ml-2">${user.user_id || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Total Purchases:</span>
                                            <span class="font-medium ml-2">${user.total_purchases || 0}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Total Spent:</span>
                                            <span class="font-medium ml-2 text-green-600">GHS ${parseFloat(user.total_spent || 0).toFixed(2)}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-3">Purchase History</h4>
                                    ${purchases.length > 0 ? `
                                        <div class="space-y-2 max-h-60 overflow-y-auto">
                                            ${purchases.map(p => `
                                                <div class="flex justify-between items-center p-2 bg-white rounded">
                                                    <div>
                                                        <span class="font-medium">${p.product_title}</span>
                                                        <span class="text-xs text-gray-500 ml-2">${new Date(p.created_at).toLocaleDateString()}</span>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="font-medium text-green-600">GHS ${parseFloat(p.amount || 0).toFixed(2)}</div>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm">No purchase history</p>'}
                                </div>
                            </div>
                        `;
                        
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `<div class="text-center py-8"><p class="text-red-600">Error: ${data.message}</p></div>`;
                    }
                })
                .catch(error => {
                    content.innerHTML = `<div class="text-center py-8"><p class="text-red-600">Error loading user details</p></div>`;
                });
        }
        
        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
        
        // Close modal on outside click
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });
    </script>
</body>
</html>

