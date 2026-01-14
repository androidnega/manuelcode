<?php
// Super Admin User Management System
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include_once '../includes/auto_config.php';

// Ensure super admin access is maintained
if (!isset($_SESSION['superadmin_access']) || $_SESSION['superadmin_access'] !== true) {
    // Generate temporary access for user management
    $_SESSION['superadmin_access'] = true;
    $_SESSION['superadmin_access_time'] = time();
    $_SESSION['superadmin_access_code'] = 'USER_MANAGEMENT_' . time();
}

$admin_username = $_SESSION['admin_name'] ?? 'Super Admin';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'suspend':
                $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                $success_message = "User suspended successfully";
                break;
                
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                $success_message = "User activated successfully";
                break;
                
            case 'delete':
                // Delete user and all related data
                try {
                    $pdo->beginTransaction();
                    
                    // Delete all related records first (in proper order to avoid foreign key constraints)
                    
                    // 1. Delete support ticket responses
                    $stmt = $pdo->prepare("DELETE FROM support_replies WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 2. Delete support tickets
                    $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 3. Delete refund requests
                    $stmt = $pdo->prepare("DELETE FROM refund_requests WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 4. Delete refunds
                    $stmt = $pdo->prepare("DELETE FROM refunds WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 5. Delete refund logs
                    $stmt = $pdo->prepare("DELETE FROM refund_logs WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 6. Delete download logs
                    $stmt = $pdo->prepare("DELETE FROM download_logs WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 7. Delete download tokens
                    $stmt = $pdo->prepare("DELETE FROM download_tokens WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 8. Delete SMS logs
                    $stmt = $pdo->prepare("DELETE FROM sms_logs WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 9. Delete payment logs
                    $stmt = $pdo->prepare("DELETE FROM payment_logs WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 10. Delete purchase logs
                    $stmt = $pdo->prepare("DELETE FROM purchase_logs WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 11. Delete user notifications
                    $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 12. Delete notification preferences
                    $stmt = $pdo->prepare("DELETE FROM user_notification_preferences WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 13. Delete user activity
                    $stmt = $pdo->prepare("DELETE FROM user_activity WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // 14. Delete user sessions
                    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                                // 15. Delete OTP codes
            $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE email = (SELECT email FROM users WHERE id = ?)");
            $stmt->execute([$user_id]);
            
            // 16. Delete coupon usage
            $stmt = $pdo->prepare("DELETE FROM coupon_usage WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
                        // 17. Delete purchases (orders)
            $stmt = $pdo->prepare("DELETE FROM purchases WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // 18. Delete guest orders by email and phone (if user had any guest purchases)
            $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE email = (SELECT email FROM users WHERE id = ?) OR phone = (SELECT phone FROM users WHERE id = ?)");
            $stmt->execute([$user_id, $user_id]);
            
            // 19. Finally delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        $pdo->commit();
                        $success_message = "User and all related data deleted successfully";
                    } else {
                        $pdo->rollback();
                        $error_message = "Failed to delete user";
                    }
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error_message = "Database error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Handle IP management
if (isset($_POST['ip_action'])) {
    $ip_address = $_POST['ip_address'];
    $ip_action = $_POST['ip_action'];
    
    if ($ip_action === 'whitelist') {
        $stmt = $pdo->prepare("INSERT INTO ip_management (ip_address, status, action_by, created_at) VALUES (?, 'whitelisted', ?, NOW()) ON DUPLICATE KEY UPDATE status = 'whitelisted', updated_at = NOW()");
        $stmt->execute([$ip_address, $admin_username]);
        $success_message = "IP address whitelisted successfully";
    } elseif ($ip_action === 'blacklist') {
        $stmt = $pdo->prepare("INSERT INTO ip_management (ip_address, status, action_by, created_at) VALUES (?, 'blacklisted', ?, NOW()) ON DUPLICATE KEY UPDATE status = 'blacklisted', updated_at = NOW()");
        $stmt->execute([$ip_address, $admin_username]);
        $success_message = "IP address blacklisted successfully";
    } elseif ($ip_action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM ip_management WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        $success_message = "IP address removed from management";
    }
}

// Get users with their activity data
$stmt = $pdo->query("
    SELECT 
        u.*,
        COUNT(DISTINCT p.id) as total_purchases,
        SUM(pr.price) as total_spent,
        COUNT(DISTINCT ua.id) as total_visits,
        MAX(ua.visited_at) as last_visit,
        ua.ip_address as last_ip
    FROM users u
    LEFT JOIN purchases p ON u.id = p.user_id
    LEFT JOIN products pr ON p.product_id = pr.id
    LEFT JOIN user_activity ua ON u.id = ua.user_id
    WHERE u.status != 'deleted'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get IP management data
$stmt = $pdo->query("SELECT * FROM ip_management ORDER BY created_at DESC");
$ip_management = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent user activity
$stmt = $pdo->query("
    SELECT ua.*, u.name as user_name, u.email, u.user_id
    FROM user_activity ua 
    JOIN users u ON ua.user_id = u.id 
    ORDER BY ua.visited_at DESC 
    LIMIT 50
");
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin - User Management</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .mobile-overlay { transition: opacity 0.3s ease-in-out; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        
        @media (max-width: 768px) {
            .mobile-header { padding: 1rem; position: sticky; top: 0; z-index: 30; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
            .mobile-title { font-size: 1.25rem; font-weight: 600; }
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; margin: 0 -0.75rem; }
            .table-responsive { min-width: 600px; padding: 0 0.75rem; }
            .mobile-button { padding: 0.75rem 1rem; font-size: 0.875rem; border-radius: 8px; min-height: 44px; display: flex; align-items: center; justify-content: center; }
            .mobile-card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        }
    </style>
</head>
<body class="bg-[#F4F4F9]">
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-[#2D3E50]">
                <i class="fas fa-users mr-2"></i>User Management System
            </h1>
            <div class="flex space-x-2">
                <a href="superadmin.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Super Admin
                </a>
                <a href="auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Total Users</div>
                        <div class="text-3xl font-bold text-gray-900"><?php echo count($users); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-check text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Active Users</div>
                        <div class="text-3xl font-bold text-gray-900">
                            <?php echo count(array_filter($users, function($u) { return $u['status'] === 'active'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-clock text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Today's Visits</div>
                        <div class="text-3xl font-bold text-gray-900">
                            <?php 
                            $today_visits = count(array_filter($recent_activity, function($a) { 
                                return date('Y-m-d', strtotime($a['visited_at'])) === date('Y-m-d'); 
                            }));
                            echo $today_visits;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-network-wired text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">IP Addresses</div>
                        <div class="text-3xl font-bold text-gray-900"><?php echo count($ip_management); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="showTab('users')" class="tab-button active border-b-2 border-blue-500 text-blue-600 py-4 px-1 text-sm font-medium">
                        <i class="fas fa-users mr-2"></i>Users
                    </button>
                    <button onclick="showTab('activity')" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-4 px-1 text-sm font-medium">
                        <i class="fas fa-chart-line mr-2"></i>Activity
                    </button>
                    <button onclick="showTab('ip')" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-4 px-1 text-sm font-medium">
                        <i class="fas fa-network-wired mr-2"></i>IP Management
                    </button>
                </nav>
            </div>

            <!-- Users Tab -->
            <div id="users-tab" class="tab-content p-6">
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchases</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                  ($user['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $user['total_purchases'] ?? 0; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₵<?php echo number_format($user['total_spent'] ?? 0, 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $user['last_visit'] ? date('M j, Y H:i', strtotime($user['last_visit'])) : 'Never'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="view_user.php?id=<?php echo $user['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="suspend">
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" 
                                                            onclick="return confirm('Are you sure you want to suspend this user?')">
                                                        <i class="fas fa-pause"></i> Suspend
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="activate">
                                                    <button type="submit" class="text-green-600 hover:text-green-900">
                                                        <i class="fas fa-play"></i> Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="text-red-600 hover:text-red-900" 
                                                        onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Activity Tab -->
            <div id="activity-tab" class="tab-content p-6 hidden">
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['user_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($activity['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($activity['page_visited']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="font-mono"><?php echo htmlspecialchars($activity['ip_address']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($activity['user_agent']); ?>">
                                            <?php echo htmlspecialchars(substr($activity['user_agent'], 0, 50)) . (strlen($activity['user_agent']) > 50 ? '...' : ''); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y H:i:s', strtotime($activity['visited_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- IP Management Tab -->
            <div id="ip-tab" class="tab-content p-6 hidden">
                <!-- Add IP Form -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Manage IP Address</h3>
                    <form method="POST" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-64">
                            <input type="text" name="ip_address" placeholder="Enter IP address (e.g., 192.168.1.1)" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" name="ip_action" value="whitelist" 
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                <i class="fas fa-check mr-2"></i>Whitelist
                            </button>
                            <button type="submit" name="ip_action" value="blacklist" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                <i class="fas fa-ban mr-2"></i>Blacklist
                            </button>
                        </div>
                    </form>
                </div>

                <!-- IP List -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($ip_management as $ip): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-mono text-sm text-gray-900"><?php echo htmlspecialchars($ip['ip_address']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $ip['status'] === 'whitelisted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($ip['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($ip['action_by']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y H:i', strtotime($ip['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="ip_address" value="<?php echo $ip['ip_address']; ?>">
                                            <input type="hidden" name="ip_action" value="remove">
                                            <button type="submit" class="text-red-600 hover:text-red-900" 
                                                    onclick="return confirm('Are you sure you want to remove this IP from management?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.add('hidden'));
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // Add active class to selected tab button
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('border-blue-500', 'text-blue-600');
        }
    </script>
</body>
</html>
