<?php
session_start();
include '../includes/auth_helper.php';
include '../includes/db.php';
include '../includes/product_functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_update':
                $product_id = $_POST['product_id'] ?? '';
                $update_type = $_POST['update_type'] ?? '';
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $version = $_POST['version'] ?? '';
                
                if ($product_id && $update_type && $title && $description) {
                    $update_id = createProductUpdate($product_id, $update_type, $title, $description, $version);
                    if ($update_id) {
                        $message = "Product update created successfully! All users who purchased this product will be notified.";
                    } else {
                        $error = "Failed to create product update.";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
        }
    }
}

// Fetch products
try {
    $stmt = $pdo->query("SELECT id, title, version, last_updated FROM products WHERE status = 'active' ORDER BY title");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
    $error = "Error fetching products: " . $e->getMessage();
}

// Fetch recent updates
try {
    $stmt = $pdo->query("
        SELECT pu.*, p.title as product_title, 
               COUNT(pn.id) as notification_count,
               SUM(CASE WHEN pn.status = 'sent' THEN 1 ELSE 0 END) as sent_count
        FROM product_updates pu
        JOIN products p ON pu.product_id = p.id
        LEFT JOIN product_notifications pn ON pu.id = pn.update_id
        GROUP BY pu.id
        ORDER BY pu.created_at DESC
        LIMIT 20
    ");
    $recent_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_updates = [];
    $error = "Error fetching updates: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Updates - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Product Updates</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                        <p class="text-green-800"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3"></i>
                        <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Create Update Form -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Create Product Update</h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_update">
                        
                        <div>
                            <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                            <select name="product_id" id="product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['title']); ?> 
                                        (v<?php echo htmlspecialchars($product['version'] ?? '1.0'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="update_type" class="block text-sm font-medium text-gray-700 mb-2">Update Type</label>
                            <select name="update_type" id="update_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select update type</option>
                                <option value="documentation">Documentation Update</option>
                                <option value="link">Link Update</option>
                                <option value="file">File Update</option>
                                <option value="general">General Update</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Update Title</label>
                            <input type="text" name="title" id="title" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., New features added, Bug fixes, etc.">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="4" required 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Describe what's new in this update..."></textarea>
                        </div>
                        
                        <div>
                            <label for="version" class="block text-sm font-medium text-gray-700 mb-2">Version (Optional)</label>
                            <input type="text" name="version" id="version" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., 2.1.0">
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Create Update & Notify Users
                        </button>
                    </form>
                </div>

                <!-- Recent Updates -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Updates</h2>
                    
                    <?php if (empty($recent_updates)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-bell text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No updates created yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_updates as $update): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($update['title']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($update['product_title']); ?></p>
                                            <p class="text-sm text-gray-500 mt-2"><?php echo htmlspecialchars($update['description']); ?></p>
                                            <div class="flex items-center mt-3 space-x-4">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo ucfirst($update['update_type']); ?>
                                                </span>
                                                <?php if ($update['version']): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        v<?php echo htmlspecialchars($update['version']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-xs text-gray-500">
                                                    <?php echo date('M j, Y g:i A', strtotime($update['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-600">
                                                <div class="flex items-center">
                                                    <i class="fas fa-users mr-1"></i>
                                                    <?php echo $update['notification_count']; ?> notified
                                                </div>
                                                <div class="flex items-center text-green-600">
                                                    <i class="fas fa-check mr-1"></i>
                                                    <?php echo $update['sent_count']; ?> sent
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistics -->
            <div class="mt-8 bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Update Statistics</h2>
                
                <?php
                try {
                    // Get total updates
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_updates");
                    $total_updates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Get total notifications sent
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_notifications WHERE status = 'sent'");
                    $total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Get products with most updates
                    $stmt = $pdo->query("
                        SELECT p.title, COUNT(pu.id) as update_count
                        FROM products p
                        LEFT JOIN product_updates pu ON p.id = pu.product_id
                        GROUP BY p.id
                        ORDER BY update_count DESC
                        LIMIT 5
                    ");
                    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $total_updates = 0;
                    $total_notifications = 0;
                    $top_products = [];
                }
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-bell text-blue-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-sm text-blue-600">Total Updates</p>
                                <p class="text-2xl font-bold text-blue-900"><?php echo $total_updates; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-sms text-green-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-sm text-green-600">Notifications Sent</p>
                                <p class="text-2xl font-bold text-green-900"><?php echo $total_notifications; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-box text-purple-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-sm text-purple-600">Active Products</p>
                                <p class="text-2xl font-bold text-purple-900"><?php echo count($products); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($top_products)): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Products with Most Updates</h3>
                        <div class="space-y-2">
                            <?php foreach ($top_products as $product): ?>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-700"><?php echo htmlspecialchars($product['title']); ?></span>
                                    <span class="text-sm text-gray-500"><?php echo $product['update_count']; ?> updates</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
