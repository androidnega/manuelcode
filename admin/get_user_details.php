<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Get user details with purchase summary - only count paid purchases
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(CASE WHEN p.status = 'paid' THEN p.id END) as total_purchases,
               SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END) as total_spent,
               MAX(CASE WHEN p.status = 'paid' THEN p.created_at END) as last_purchase
        FROM users u 
        LEFT JOIN purchases p ON u.id = p.user_id 
        LEFT JOIN products pr ON p.product_id = pr.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Get user's purchase history - only show paid purchases
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pr.title as product_title, 
                   COALESCE(p.amount, pr.price) as amount,
                   COALESCE(p.reference, p.payment_ref) as payment_reference,
                   p.status
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            WHERE p.user_id = ? AND p.status = 'paid'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If there's an error, try a simpler query
        $stmt = $pdo->prepare("
            SELECT p.*, pr.title as product_title, pr.price as amount
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add default status and payment reference for each purchase
        foreach ($purchases as &$purchase) {
            $purchase['status'] = 'completed';
            $purchase['payment_reference'] = $purchase['payment_ref'] ?? 'N/A';
        }
    }
    
    // Format phone number for display
    if (!empty($user['phone'])) {
        $user['phone'] = format_phone_for_display($user['phone']);
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'purchases' => $purchases
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
