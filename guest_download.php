<?php
include 'includes/db.php';
include 'includes/otp_helper.php';

$error_message = '';
$success_message = '';
$order_details = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $guest_id = trim($_POST['guest_id'] ?? '');
    $order_id = trim($_POST['order_id'] ?? '');
    
    if (empty($email) && empty($guest_id) && empty($order_id)) {
        $error_message = 'Please provide either your email, guest ID, or order ID to download your purchase.';
    } else {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($email)) {
                $where_conditions[] = "go.email = ?";
                $params[] = $email;
            }
            
            if (!empty($guest_id)) {
                // Search by unique_id field
                $where_conditions[] = "go.unique_id = ?";
                $params[] = $guest_id;
            }
            
            if (!empty($order_id)) {
                $where_conditions[] = "go.id = ?";
                $params[] = $order_id;
            }
            
            $where_clause = 'WHERE ' . implode(' OR ', $where_conditions);
            
            $query = "
                SELECT go.*, pr.title as product_title, pr.description, pr.doc_file, pr.drive_link, pr.price
                FROM guest_orders go 
                JOIN products pr ON go.product_id = pr.id 
                $where_clause AND go.status = 'paid'
                ORDER BY go.created_at DESC
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)) {
                $error_message = 'No paid orders found with the provided information. Please check your details and try again.';
            } else {
                $order_details = $orders;
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred while searching for your orders. Please try again.';
            error_log("Guest download error: " . $e->getMessage());
        }
    }
}

// Handle download request
if (isset($_GET['download']) && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT go.*, pr.title as product_title, pr.doc_file, pr.drive_link, pr.price
            FROM guest_orders go 
            JOIN products pr ON go.product_id = pr.id 
            WHERE go.id = ? AND go.status = 'paid'
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Redirect to the main download system
            $download_url = "download.php?type=guest&email=" . urlencode($order['email']) . "&product_id=" . $order['product_id'] . "&ref=" . $order['reference'];
            header('Location: ' . $download_url);
            exit;
        } else {
            $error_message = 'File not found or order not valid.';
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred while downloading the file.';
        error_log("Guest download file error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Download - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2 group">
                        <img src="assets/favi/favicon.png" alt="ManuelCode Logo" class="h-8 w-auto transition-transform duration-300 group-hover:scale-105">
                        <span class="text-lg font-bold text-gray-800 group-hover:text-[#536895] transition-colors duration-300" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">ManuelCode</span>
                    </a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900 transition-colors">Home</a>
                    <a href="store.php" class="text-gray-600 hover:text-gray-900 transition-colors">Store</a>
                    <a href="projects.php" class="text-gray-600 hover:text-gray-900 transition-colors">Projects</a>
                    <a href="contact.php" class="text-gray-600 hover:text-gray-900 transition-colors">Contact</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Download Your Purchase</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Access your purchased digital products using your email, guest ID, or order ID. 
                This system is designed for guests who made purchases without creating an account.
            </p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Find Your Order</h2>
            
            <?php if ($error_message): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                        <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="guest_id" class="block text-sm font-medium text-gray-700 mb-2">Guest ID (Optional)</label>
                        <input type="text" id="guest_id" name="guest_id" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., GUEST000123">
                    </div>
                    <div>
                        <label for="order_id" class="block text-sm font-medium text-gray-700 mb-2">Order ID (Optional)</label>
                        <input type="text" id="order_id" name="order_id" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., 123">
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium">
                        <i class="fas fa-search mr-2"></i>
                        Find My Orders
                    </button>
                </div>
            </form>
        </div>

        <!-- Order Results -->
        <?php if ($order_details): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Orders</h2>
                
                <div class="space-y-4">
                    <?php foreach ($order_details as $order): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($order['product_title']); ?></h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">Order ID:</span> 
                                            <span class="font-mono">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Guest ID:</span> 
                                            <span class="font-mono"><?php echo htmlspecialchars($order['unique_id'] ?? 'GUEST' . str_pad($order['id'], 6, '0', STR_PAD_LEFT)); ?></span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Date:</span> 
                                            <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="font-medium text-gray-600">Email:</span> 
                                        <span class="text-gray-900"><?php echo htmlspecialchars($order['email']); ?></span>
                                    </div>
                                    <?php if ($order['name']): ?>
                                        <div class="mt-1">
                                            <span class="font-medium text-gray-600">Name:</span> 
                                            <span class="text-gray-900"><?php echo htmlspecialchars($order['name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <span class="text-lg font-bold text-green-600">GHS <?php echo number_format($order['price'], 2); ?></span>
                                        <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">✅ Paid</span>
                                    </div>
                                </div>
                                
                                <div class="ml-4">
                                    <a href="download.php?type=guest&email=<?php echo urlencode($order['email']); ?>&product_id=<?php echo $order['product_id']; ?>&ref=<?php echo $order['reference']; ?>" 
                                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-200 font-medium">
                                        <i class="fas fa-download mr-2"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-question-circle mr-2 text-blue-500"></i>
                Need Help?
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">How to find your order:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Use the email address you provided during checkout</li>
                        <li>• Check your order confirmation email for the Order ID</li>
                        <li>• Your Guest ID format is: GUEST + 6-digit number</li>
                        <li>• Only paid orders are available for download</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Contact Support:</h4>
                    <p class="text-sm text-gray-600 mb-2">If you're having trouble accessing your download:</p>
                    <a href="contact.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-envelope mr-1"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-600">
                <p>&copy; <?php echo date('Y'); ?> ManuelCode. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
