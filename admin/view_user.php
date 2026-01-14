<?php
// Super Admin User Detail View
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';

// Ensure super admin access is maintained
if (!isset($_SESSION['superadmin_access']) || $_SESSION['superadmin_access'] !== true) {
    $_SESSION['superadmin_access'] = true;
    $_SESSION['superadmin_access_time'] = time();
    $_SESSION['superadmin_access_code'] = 'USER_VIEW_' . time();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$admin_username = $_SESSION['admin_name'] ?? 'Super Admin';

// Validate user ID
if ($user_id <= 0) {
    header('Location: user_management.php?error=Invalid user ID');
    exit();
}

// Get user details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: user_management.php?error=User not found');
        exit();
    }
} catch (Exception $e) {
    header('Location: user_management.php?error=Database error occurred');
    exit();
}

// Get user's purchases
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pr.title as product_title, pr.price, pr.preview_image, pr.doc_file, pr.drive_link
        FROM purchases p 
        JOIN products pr ON p.product_id = pr.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
    ");
    $stmt->execute([$user_id]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $purchases = [];
}

// Get user's activity
try {
    $stmt = $pdo->prepare("
        SELECT * FROM user_activity 
        WHERE user_id = ? 
        ORDER BY visited_at DESC 
        LIMIT 100
    ");
    $stmt->execute([$user_id]);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activity = [];
}

// Get user stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_purchases FROM purchases WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_purchases = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchases'];

    $stmt = $pdo->prepare("SELECT SUM(pr.price) as total_spent FROM purchases p JOIN products pr ON p.product_id = pr.id WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $total_spent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_visits FROM user_activity WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_visits = $stmt->fetch(PDO::FETCH_ASSOC)['total_visits'];
} catch (Exception $e) {
    $total_purchases = 0;
    $total_spent = 0;
    $total_visits = 0;
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'suspend':
                    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user['status'] = 'suspended';
                    $success_message = "User suspended successfully";
                    break;
                    
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user['status'] = 'active';
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
                            header('Location: user_management.php?success=User and all related data deleted successfully');
                            exit();
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
        } catch (Exception $e) {
            $error_message = "An error occurred while processing the action";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin - User Details</title>
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
                <i class="fas fa-user mr-2"></i>User Details
            </h1>
            <div class="flex space-x-2">
                <a href="user_management.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Users
                </a>
                <a href="superadmin.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>Super Admin
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
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- User Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center mr-4">
                        <i class="fas fa-user text-2xl text-gray-600"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                  ($user['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <?php if ($user['status'] === 'active'): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="suspend">
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center" 
                                    onclick="return confirm('Are you sure you want to suspend this user?')">
                                <i class="fas fa-pause mr-2"></i>Suspend
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                                <i class="fas fa-play mr-2"></i>Activate
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center" 
                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $total_purchases; ?></div>
                    <div class="text-sm text-gray-600">Total Purchases</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">₵<?php echo number_format($total_spent, 2); ?></div>
                    <div class="text-sm text-gray-600">Total Spent</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600"><?php echo $total_visits; ?></div>
                    <div class="text-sm text-gray-600">Total Visits</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                    <div class="text-sm text-gray-600">Joined</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="showTab('purchases')" class="tab-button active border-b-2 border-blue-500 text-blue-600 py-4 px-1 text-sm font-medium">
                        <i class="fas fa-shopping-cart mr-2"></i>Purchases
                    </button>
                    <button onclick="showTab('activity')" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-4 px-1 text-sm font-medium">
                        <i class="fas fa-chart-line mr-2"></i>Activity
                    </button>
                </nav>
            </div>

            <!-- Purchases Tab -->
            <div id="purchases-tab" class="tab-content p-6">
                <?php if (empty($purchases)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No purchases found for this user.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if ($purchase['preview_image']): ?>
                                                    <img class="h-10 w-10 rounded object-cover" src="../assets/images/products/<?php echo htmlspecialchars($purchase['preview_image']); ?>" alt="">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 rounded bg-gray-300 flex items-center justify-center">
                                                        <i class="fas fa-file text-gray-600"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($purchase['product_title']); ?></div>
                                                    <div class="text-sm text-gray-500">Order #<?php echo $purchase['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₵<?php echo number_format($purchase['price'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y H:i', strtotime($purchase['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="view_receipt.php?purchase_id=<?php echo $purchase['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-receipt mr-1"></i>View Receipt
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity Tab -->
            <div id="activity-tab" class="tab-content p-6 hidden">
                <?php if (empty($activity)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No activity found for this user.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($activity as $act): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($act['page_visited']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="font-mono"><?php echo htmlspecialchars($act['ip_address']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($act['user_agent']); ?>">
                                                <?php echo htmlspecialchars(substr($act['user_agent'], 0, 50)) . (strlen($act['user_agent']) > 50 ? '...' : ''); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y H:i:s', strtotime($act['visited_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
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
