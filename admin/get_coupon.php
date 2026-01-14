<?php
include 'auth/check_auth.php';
include '../includes/db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Coupon ID required']);
    exit;
}

$coupon_id = (int)$_GET['id'];

try {
    // Get coupon data
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$coupon_id]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        http_response_code(404);
        echo json_encode(['error' => 'Coupon not found']);
        exit;
    }
    
    // Get selected products if applies_to is specific_products
    $selected_products = [];
    if ($coupon['applies_to'] === 'specific_products') {
        $stmt = $pdo->prepare("SELECT product_id FROM coupon_products WHERE coupon_id = ?");
        $stmt->execute([$coupon_id]);
        $selected_products = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    $coupon['selected_products'] = $selected_products;
    
    // Convert timestamps to datetime-local format
    $coupon['valid_from'] = date('Y-m-d\TH:i', strtotime($coupon['valid_from']));
    if ($coupon['valid_until']) {
        $coupon['valid_until'] = date('Y-m-d\TH:i', strtotime($coupon['valid_until']));
    }
    
    header('Content-Type: application/json');
    echo json_encode($coupon);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
