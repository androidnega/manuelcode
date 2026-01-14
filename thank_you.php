<?php
require 'includes/db.php';
require 'includes/config.php';
require 'includes/util.php';
session_start();

$order_id = (int) ($_GET['order_id'] ?? $_GET['order'] ?? 0);
$is_guest_order = isset($_GET['order_id']) || isset($_SESSION['guest_order_id']);

if (!$order_id) {
    header('Location: index.php');
    exit;
}

if ($is_guest_order) {
    // Handle guest order
    $stmt = $pdo->prepare("SELECT go.*, p.title as product_title FROM guest_orders go JOIN products p ON go.product_id = p.id WHERE go.id = ? AND go.status = 'paid'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo "Guest order not found or not paid";
        exit;
    }
    
    // Use session download link if available, otherwise generate one
    $download_url = $_SESSION['guest_download_link'] ?? "guest_download.php?order_id=" . $order['id'];
    
} else {
    // Handle user order
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $stmt->execute([$order_id, (int) $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo "Order not found";
        exit;
    }
    
    $token = signed_token([
        'user_id' => $order['user_id'],
        'product_id' => $order['product_id'],
        'order_id' => $order['id']
    ], get_config('download_token_secret'), 24 * 3600);
    
    $download_url = get_config('site_url') . "/download.php?t=" . urlencode($token);
}
?>

<?php include 'includes/header.php'; ?>

<section class="max-w-xl mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-lg shadow-sm p-8">
        <div class="mb-6">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-[#2D3E50] mb-2">Thank You!</h1>
            <p class="text-gray-700">Payment confirmed successfully</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-[#2D3E50] mb-2">Order Details</h2>
            <p class="text-sm text-gray-600">Order #<?php echo $order['id']; ?></p>
            <p class="text-sm text-gray-600">Product: <?php echo htmlspecialchars($order['product_title'] ?? 'Product'); ?></p>
            <p class="text-sm text-gray-600">Amount: GHS <?php echo number_format($is_guest_order ? $order['total_amount'] : $order['amount'], 2); ?></p>
            <p class="text-sm text-gray-600">Date: <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
            <?php if ($is_guest_order): ?>
                <p class="text-sm text-gray-600">Guest ID: <?php echo htmlspecialchars($order['unique_id'] ?? 'GUEST' . str_pad($order['id'], 6, '0', STR_PAD_LEFT)); ?></p>
            <?php endif; ?>
        </div>
        
        <a href="<?php echo htmlspecialchars($download_url); ?>" 
           class="inline-block bg-[#2D3E50] text-white px-8 py-3 rounded-lg hover:bg-[#243646] transition-colors">
            <i class="fas fa-download mr-2"></i>Download Your Product
        </a>
        
        <?php if ($is_guest_order): ?>
            <p class="text-xs text-gray-500 mt-4">
                Keep your Guest ID safe for future downloads
            </p>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Guest Download:</strong> You can access your download anytime using your email or Guest ID at our 
                    <a href="guest_download.php" class="underline font-medium">Download Page</a>
                </p>
            </div>
        <?php else: ?>
            <p class="text-xs text-gray-500 mt-4">
                Download link expires in 24 hours
            </p>
        <?php endif; ?>
        
        <div class="mt-8 pt-6 border-t border-gray-200">
            <?php if ($is_guest_order): ?>
                <a href="guest_download.php" class="text-[#2D3E50] hover:underline">
                    <i class="fas fa-download mr-1"></i>Access Downloads
                </a>
            <?php else: ?>
                <a href="dashboard/my-purchases" class="text-[#2D3E50] hover:underline">
                    <i class="fas fa-list mr-1"></i>View All My Purchases
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
