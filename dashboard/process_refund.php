<?php
session_start();
include '../includes/db.php';
include '../includes/auth_only.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$purchase_id = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (!$purchase_id) {
    echo json_encode(['success' => false, 'message' => 'Purchase ID is required']);
    exit;
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Refund reason is required']);
    exit;
}

try {
    // Check if purchase exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT p.*, pr.title as product_title, pr.price
        FROM purchases p 
        JOIN products pr ON p.product_id = pr.id 
        WHERE p.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$purchase_id, $user_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        echo json_encode(['success' => false, 'message' => 'Purchase not found or not eligible for refund']);
        exit;
    }
    
    // Check if refund already exists
    $stmt = $pdo->prepare("SELECT id FROM refunds WHERE purchase_id = ? AND status IN ('pending', 'approved')");
    $stmt->execute([$purchase_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Refund request already exists for this purchase']);
        exit;
    }
    
    // Check if purchase is within 7 days
    $purchase_date = new DateTime($purchase['created_at']);
    $now = new DateTime();
    $days_diff = $now->diff($purchase_date)->days;
    
    if ($days_diff > 7) {
        echo json_encode(['success' => false, 'message' => 'Refund period has expired (7 days from purchase)']);
        exit;
    }
    
    // Create refund request
    $stmt = $pdo->prepare("
        INSERT INTO refunds (purchase_id, user_id, product_id, amount, reason, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $purchase_id,
        $user_id,
        $purchase['product_id'],
        $purchase['price'],
        $reason
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Refund request submitted successfully! We will review your request within 24-48 hours.'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing refund: ' . $e->getMessage()]);
}
?>
