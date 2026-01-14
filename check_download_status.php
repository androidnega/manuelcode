<?php
session_start();
include 'includes/db.php';
include 'includes/notification_helper.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_GET['product_id'] ?? 0);

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

try {
    $notificationHelper = new NotificationHelper($pdo);
    
    // Check if user has purchased this product
    $stmt = $pdo->prepare("
        SELECT p.*, pr.title, pr.drive_link
        FROM purchases p
        JOIN products pr ON p.product_id = pr.id
        WHERE p.user_id = ? AND p.product_id = ? AND p.status = 'paid'
    ");
    $stmt->execute([$user_id, $product_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        http_response_code(404);
        echo json_encode(['error' => 'Purchase not found']);
        exit;
    }
    
    // Check if download link is available
    $has_download_link = $notificationHelper->hasDownloadLink($product_id);
    
    // Check for recent updates
    $updates = $notificationHelper->getProductUpdateHistory($product_id);
    $recent_updates = array_filter($updates, function($update) {
        return strtotime($update['created_at']) > strtotime('-24 hours');
    });
    
    echo json_encode([
        'available' => $has_download_link,
        'product_title' => $purchase['title'],
        'has_recent_updates' => !empty($recent_updates),
        'recent_updates' => array_slice($recent_updates, 0, 3), // Last 3 updates
        'last_checked' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Download status check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
