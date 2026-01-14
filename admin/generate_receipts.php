<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth/login.php');
    exit();
}

$message = '';
$error = '';

// Handle receipt generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'generate_single':
                    $purchase_id = (int)$_POST['purchase_id'];
                    $type = $_POST['type']; // 'user' or 'guest'
                    
                    if ($type === 'user') {
                        $stmt = $pdo->prepare("
                            UPDATE purchases 
                            SET receipt_number = CONCAT('REC', LPAD(id, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
                            WHERE id = ? AND (receipt_number IS NULL OR receipt_number = '')
                        ");
                        $stmt->execute([$purchase_id]);
                        $affected = $stmt->rowCount();
                        
                        if ($affected > 0) {
                            $message = "Receipt generated successfully for user purchase #{$purchase_id}";
                        } else {
                            $error = "Purchase already has a receipt number or not found";
                        }
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE guest_orders 
                            SET receipt_number = CONCAT('GST', LPAD(id, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
                            WHERE id = ? AND (receipt_number IS NULL OR receipt_number = '')
                        ");
                        $stmt->execute([$purchase_id]);
                        $affected = $stmt->rowCount();
                        
                        if ($affected > 0) {
                            $message = "Receipt generated successfully for guest order #{$purchase_id}";
                        } else {
                            $error = "Guest order already has a receipt number or not found";
                        }
                    }
                    break;
                    
                case 'generate_bulk':
                    $selected_purchases_json = $_POST['selected_purchases'] ?? '[]';
                    $selected_guests_json = $_POST['selected_guests'] ?? '[]';
                    
                    $selected_purchases = json_decode($selected_purchases_json, true) ?: [];
                    $selected_guests = json_decode($selected_guests_json, true) ?: [];
                    
                    $user_affected = 0;
                    $guest_affected = 0;
                    
                    if (!empty($selected_purchases)) {
                        $placeholders = str_repeat('?,', count($selected_purchases) - 1) . '?';
                        $stmt = $pdo->prepare("
                            UPDATE purchases 
                            SET receipt_number = CONCAT('REC', LPAD(id, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
                            WHERE id IN ($placeholders) AND (receipt_number IS NULL OR receipt_number = '')
                        ");
                        $stmt->execute($selected_purchases);
                        $user_affected = $stmt->rowCount();
                    }
                    
                    if (!empty($selected_guests)) {
                        $placeholders = str_repeat('?,', count($selected_guests) - 1) . '?';
                        $stmt = $pdo->prepare("
                            UPDATE guest_orders 
                            SET receipt_number = CONCAT('GST', LPAD(id, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
                            WHERE id IN ($placeholders) AND (receipt_number IS NULL OR receipt_number = '')
                        ");
                        $stmt->execute($selected_guests);
                        $guest_affected = $stmt->rowCount();
                    }
                    
                    $message = "Successfully generated {$user_affected} user receipts and {$guest_affected} guest receipts.";
                    break;
                    
                case 'generate_all':
                    // Generate all missing receipts
                    $stmt = $pdo->prepare("
                        UPDATE purchases 
                        SET receipt_number = CONCAT('REC', LPAD(id, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
                        WHERE receipt_number IS NULL OR receipt_number = ''
                    ");
                    $stmt->execute();
                    $user_affected = $stmt->rowCount();
                    
                    $stmt = $pdo->prepare("
                        UPDATE guest_orders 
                        SET receipt_number = CONCAT('GST', LPAD(id, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
                        WHERE receipt_number IS NULL OR receipt_number = ''
                    ");
                    $stmt->execute();
                    $guest_affected = $stmt->rowCount();
                    
                    $message = "Successfully generated {$user_affected} user receipts and {$guest_affected} guest receipts.";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? 'all'; // 'all', 'user', 'guest'
$status = $_GET['status'] ?? 'all'; // 'all', 'with_receipt', 'without_receipt'

// Build queries
$user_where = "1=1";
$guest_where = "1=1";
$params = [];

if ($search) {
    $user_where .= " AND (u.name LIKE ? OR u.email LIKE ? OR pr.title LIKE ?)";
    $guest_where .= " AND (go.name LIKE ? OR go.email LIKE ? OR pr.title LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($status === 'with_receipt') {
    $user_where .= " AND p.receipt_number IS NOT NULL AND p.receipt_number != ''";
    $guest_where .= " AND go.receipt_number IS NOT NULL AND go.receipt_number != ''";
} elseif ($status === 'without_receipt') {
    $user_where .= " AND (p.receipt_number IS NULL OR p.receipt_number = '')";
    $guest_where .= " AND (go.receipt_number IS NULL OR go.receipt_number = '')";
}

// Get user purchases
$user_purchases = [];
if ($type === 'all' || $type === 'user') {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name, u.email as user_email, pr.title as product_title,
               CASE WHEN p.receipt_number IS NULL OR p.receipt_number = '' THEN 0 ELSE 1 END as has_receipt
        FROM purchases p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN products pr ON p.product_id = pr.id
        WHERE $user_where
        ORDER BY p.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $user_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get guest orders
$guest_orders = [];
if ($type === 'all' || $type === 'guest') {
    $stmt = $pdo->prepare("
        SELECT go.*, pr.title as product_title,
               CASE WHEN go.receipt_number IS NULL OR go.receipt_number = '' THEN 0 ELSE 1 END as has_receipt
        FROM guest_orders go
        LEFT JOIN products pr ON go.product_id = pr.id
        WHERE $guest_where
        ORDER BY go.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $guest_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM purchases");
$total_user_purchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM guest_orders");
$total_guest_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM purchases WHERE receipt_number IS NULL OR receipt_number = ''");
$missing_user_receipts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM guest_orders WHERE receipt_number IS NULL OR receipt_number = ''");
$missing_guest_receipts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Receipts - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Responsive Table Styles */
        @media (max-width: 768px) {
            .responsive-table {
                display: block;
            }
            
            .responsive-table thead {
                display: none;
            }
            
            .responsive-table tbody {
                display: block;
            }
            
            .responsive-table tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1rem;
                background: white;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .responsive-table td {
                display: block;
                text-align: left;
                border: none;
                padding: 0.5rem 0;
                border-bottom: 1px solid #f3f4f6;
            }
            
            .responsive-table td:last-child {
                border-bottom: none;
            }
            
            .responsive-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #374151;
                display: block;
                margin-bottom: 0.25rem;
                font-size: 0.875rem;
            }
            
            /* Hide checkbox column on mobile */
            .responsive-table td:first-child {
                display: none;
            }
            
            /* Adjust action buttons for mobile */
            .responsive-table .flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .responsive-table .flex a,
            .responsive-table .flex button {
                width: 100%;
                text-align: center;
                padding: 0.5rem;
                border-radius: 6px;
                font-size: 0.875rem;
            }
        }
        
        /* Enhanced mobile styles for better readability */
        @media (max-width: 640px) {
            .responsive-table tr {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }
            
            .responsive-table td {
                padding: 0.375rem 0;
                font-size: 0.875rem;
            }
            
            .responsive-table td::before {
                font-size: 0.75rem;
                margin-bottom: 0.125rem;
            }
            
            /* Mobile bulk actions */
            .mobile-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .mobile-actions form,
            .mobile-actions button {
                width: 100%;
                text-align: center;
                padding: 0.75rem;
                font-size: 0.875rem;
            }
        }
        
        /* Mobile header adjustments */
        @media (max-width: 768px) {
            .mobile-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .mobile-actions form,
            .mobile-actions button {
                width: 100%;
                text-align: center;
                padding: 0.75rem;
            }
            
            .mobile-header-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .mobile-header-actions a {
                padding: 0.5rem;
                font-size: 0.875rem;
                text-align: center;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4">
                                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-800">Generate Receipts</h1>
                        <div class="flex space-x-2 mobile-header-actions">
                            <a href="digital_signature.php" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                                <i class="fas fa-signature mr-2"></i>Digital Signature
                            </a>
                            <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total User Purchases</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_user_purchases); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-user text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Guest Orders</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_guest_orders); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Missing User Receipts</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($missing_user_receipts); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Missing Guest Receipts</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($missing_guest_receipts); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-check-circle mt-1 mr-3"></i>
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name, email, or product..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="user" <?php echo $type === 'user' ? 'selected' : ''; ?>>User Purchases</option>
                                <option value="guest" <?php echo $type === 'guest' ? 'selected' : ''; ?>>Guest Orders</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Receipt Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="with_receipt" <?php echo $status === 'with_receipt' ? 'selected' : ''; ?>>With Receipt</option>
                                <option value="without_receipt" <?php echo $status === 'without_receipt' ? 'selected' : ''; ?>>Without Receipt</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                            <a href="generate_receipts.php" class="ml-2 bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                                <i class="fas fa-times mr-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Bulk Actions</h3>
                    
                    <div class="flex flex-wrap gap-4 mobile-actions">
                        <form method="POST" onsubmit="return confirm('Generate receipts for all missing records?');">
                            <input type="hidden" name="action" value="generate_all">
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-magic mr-2"></i>Generate All Missing Receipts
                            </button>
                        </form>
                        
                                                 <button onclick="generateSelected()" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                             <i class="fas fa-check-square mr-2"></i>Generate Selected Receipts
                         </button>
                         
                         <button onclick="downloadSelectedReceipts()" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition-colors">
                             <i class="fas fa-download mr-2"></i>Download Selected Receipts
                         </button>
                        
                        <button onclick="selectAll()" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                            <i class="fas fa-check-double mr-2"></i>Select All
                        </button>
                        
                        <button onclick="deselectAll()" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                            <i class="fas fa-square mr-2"></i>Deselect All
                        </button>
                    </div>
                </div>
            </div>

            <!-- User Purchases -->
            <?php if (!empty($user_purchases)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">User Purchases</h2>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full responsive-table">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">
                                            <input type="checkbox" id="select-all-users" onchange="toggleUserSelection(this)">
                                        </th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">User</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Product</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Amount</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Date</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Receipt</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_purchases as $purchase): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4" data-label="Select">
                                                <input type="checkbox" name="selected_purchases[]" value="<?php echo $purchase['id']; ?>" 
                                                       class="user-checkbox" <?php echo $purchase['has_receipt'] ? 'disabled' : ''; ?>>
                                            </td>
                                            <td class="py-3 px-4" data-label="User">
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($purchase['user_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($purchase['user_email']); ?></div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-gray-900" data-label="Product"><?php echo htmlspecialchars($purchase['product_title']); ?></td>
                                            <td class="py-3 px-4 text-green-600 font-semibold" data-label="Amount">₵<?php echo number_format($purchase['amount'] ?? 0, 2); ?></td>
                                            <td class="py-3 px-4 text-sm text-gray-500" data-label="Date"><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></td>
                                            <td class="py-3 px-4" data-label="Receipt">
                                                <?php if ($purchase['has_receipt']): ?>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <?php echo htmlspecialchars($purchase['receipt_number']); ?>
                                                        </span>
                                                        <a href="view_receipt.php?id=<?php echo $purchase['id']; ?>&type=user" 
                                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Missing
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4" data-label="Actions">
                                                <?php if (!$purchase['has_receipt']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="generate_single">
                                                        <input type="hidden" name="purchase_id" value="<?php echo $purchase['id']; ?>">
                                                        <input type="hidden" name="type" value="user">
                                                        <button type="submit" class="text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-plus-circle"></i> Generate
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Already has receipt</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Guest Orders -->
            <?php if (!empty($guest_orders)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Guest Orders</h2>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full responsive-table">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">
                                            <input type="checkbox" id="select-all-guests" onchange="toggleGuestSelection(this)">
                                        </th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Guest</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Product</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Amount</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Date</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Receipt</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($guest_orders as $order): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4" data-label="Select">
                                                <input type="checkbox" name="selected_guests[]" value="<?php echo $order['id']; ?>" 
                                                       class="guest-checkbox" <?php echo $order['has_receipt'] ? 'disabled' : ''; ?>>
                                            </td>
                                            <td class="py-3 px-4" data-label="Guest">
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($order['name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['email']); ?></div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-gray-900" data-label="Product"><?php echo htmlspecialchars($order['product_title']); ?></td>
                                            <td class="py-3 px-4 text-green-600 font-semibold" data-label="Amount">₵<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td class="py-3 px-4 text-sm text-gray-500" data-label="Date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td class="py-3 px-4" data-label="Receipt">
                                                <?php if ($order['has_receipt']): ?>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <?php echo htmlspecialchars($order['receipt_number']); ?>
                                                        </span>
                                                        <a href="view_receipt.php?id=<?php echo $order['id']; ?>&type=guest" 
                                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Missing
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4" data-label="Actions">
                                                <?php if (!$order['has_receipt']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="generate_single">
                                                        <input type="hidden" name="purchase_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="type" value="guest">
                                                        <button type="submit" class="text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-plus-circle"></i> Generate
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Already has receipt</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- No Results -->
            <?php if (empty($user_purchases) && empty($guest_orders)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No results found</h3>
                    <p class="text-gray-500">Try adjusting your search criteria or filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bulk Action Form -->
    <form id="bulk-form" method="POST" style="display: none;">
        <input type="hidden" name="action" value="generate_bulk">
        <input type="hidden" name="selected_purchases" id="selected-purchases">
        <input type="hidden" name="selected_guests" id="selected-guests">
    </form>

    <!-- Download Form -->
    <form id="download-form" method="POST" action="download_receipts.php" style="display: none;">
        <input type="hidden" name="selected_purchases" id="download-purchases">
        <input type="hidden" name="selected_guests" id="download-guests">
    </form>

    <script>
        function toggleUserSelection(checkbox) {
            const userCheckboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            userCheckboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function toggleGuestSelection(checkbox) {
            const guestCheckboxes = document.querySelectorAll('.guest-checkbox:not(:disabled)');
            guestCheckboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function selectAll() {
            document.querySelectorAll('input[type="checkbox"]:not(:disabled)').forEach(cb => cb.checked = true);
        }

        function deselectAll() {
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }

        function generateSelected() {
            const selectedPurchases = Array.from(document.querySelectorAll('input[name="selected_purchases[]"]:checked')).map(cb => cb.value);
            const selectedGuests = Array.from(document.querySelectorAll('input[name="selected_guests[]"]:checked')).map(cb => cb.value);
            
            if (selectedPurchases.length === 0 && selectedGuests.length === 0) {
                alert('Please select at least one item to generate receipts for.');
                return;
            }
            
            if (confirm(`Generate receipts for ${selectedPurchases.length} user purchases and ${selectedGuests.length} guest orders?`)) {
                document.getElementById('selected-purchases').value = JSON.stringify(selectedPurchases);
                document.getElementById('selected-guests').value = JSON.stringify(selectedGuests);
                document.getElementById('bulk-form').submit();
            }
        }

        function downloadSelectedReceipts() {
            const selectedPurchases = Array.from(document.querySelectorAll('input[name="selected_purchases[]"]:checked')).map(cb => cb.value);
            const selectedGuests = Array.from(document.querySelectorAll('input[name="selected_guests[]"]:checked')).map(cb => cb.value);
            
            if (selectedPurchases.length === 0 && selectedGuests.length === 0) {
                alert('Please select at least one item to download receipts for.');
                return;
            }
            
            if (confirm(`Download receipts for ${selectedPurchases.length} user purchases and ${selectedGuests.length} guest orders?`)) {
                document.getElementById('download-purchases').value = JSON.stringify(selectedPurchases);
                document.getElementById('download-guests').value = JSON.stringify(selectedGuests);
                document.getElementById('download-form').submit();
            }
        }
    </script>
</body>
</html>
