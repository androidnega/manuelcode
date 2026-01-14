<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$refund_id = $input['refund_id'] ?? null;
$status = $input['status'] ?? null;

if (!$refund_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing refund_id or status']);
    exit;
}

// Validate status
if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get refund details
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name, u.email as user_email, u.user_id, p.title as product_title, p.price as product_price
        FROM refunds r
        JOIN users u ON r.user_id = u.id
        JOIN purchases pur ON r.purchase_id = pur.id
        JOIN products p ON pur.product_id = p.id
        WHERE r.id = ?
    ");
    $stmt->execute([$refund_id]);
    $refund = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$refund) {
        throw new Exception('Refund not found');
    }
    
    if ($refund['status'] !== 'pending') {
        throw new Exception('Refund has already been processed');
    }
    
    // Update refund status
    $stmt = $pdo->prepare("
        UPDATE refunds 
        SET status = ?, processed_at = NOW(), processed_by = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $_SESSION['admin_id'] ?? 'admin', $refund_id]);
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO refund_logs (refund_id, action, status, admin_id, notes, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $refund_id,
        'status_update',
        $status,
        $_SESSION['admin_id'] ?? 'admin',
        "Refund {$status} by admin"
    ]);
    
    // If approved, you might want to update the purchase status or send notification
    if ($status === 'approved') {
        // Update purchase status to refunded
        $stmt = $pdo->prepare("
            UPDATE purchases 
            SET status = 'refunded', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$refund['purchase_id']]);
        
        // Here you would typically integrate with your payment gateway to process the actual refund
        // For now, we'll just log it
        $stmt = $pdo->prepare("
            INSERT INTO refund_logs (refund_id, action, status, admin_id, notes, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $refund_id,
            'payment_refund',
            'pending',
            $_SESSION['admin_id'] ?? 'admin',
            "Payment refund initiated for GHS " . number_format($refund['product_price'], 2)
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Refund {$status} successfully",
        'refund_id' => $refund_id,
        'status' => $status
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Refund processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
