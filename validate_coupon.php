<?php
session_start();
include 'includes/db.php';
include 'includes/coupon_helper.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['coupon_code'])) {
    echo json_encode(['success' => false, 'message' => 'Coupon code is required']);
    exit;
}

$coupon_code = trim($input['coupon_code']);

if (empty($coupon_code)) {
    echo json_encode(['success' => false, 'message' => 'Coupon code cannot be empty']);
    exit;
}

try {
    // Get coupon details directly from database
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = TRUE
    ");
    $stmt->execute([strtoupper(trim($coupon_code))]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
        exit;
    }
    
    // Check if coupon is expired
    if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Coupon has expired']);
        exit;
    }
    
    // Check if coupon is not yet valid
    if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > time()) {
        echo json_encode(['success' => false, 'message' => 'Coupon is not yet valid']);
        exit;
    }
    
    // Check usage limit
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Coupon usage limit reached']);
        exit;
    }
    
    // Calculate discount info
    $discount_info = '';
    if ($coupon['discount_type'] === 'percentage') {
        $discount_info = "{$coupon['discount_value']}% off your purchase";
    } else {
        $discount_info = "₵{$coupon['discount_value']} off your purchase";
    }
    
    // Store coupon in session for payment processing
    $_SESSION['applied_coupon'] = [
        'code' => $coupon_code,
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'coupon_id' => $coupon['id']
    ];
    
    // FIXED: Calculate and store the discounted total in session
    $discount_amount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount_amount = ($coupon['discount_value'] * 100) / 100; // This will be calculated per product
    } else {
        $discount_amount = $coupon['discount_value'];
    }
    
    // Store discount info for payment processing
    $_SESSION['coupon_discount_info'] = [
        'discount_amount' => $discount_amount,
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value']
    ];
    
    echo json_encode([
        'success' => true,
        'message' => "Coupon '{$coupon_code}' applied successfully!",
        'discount_info' => $discount_info,
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value']
    ]);
    
} catch (Exception $e) {
    error_log("Coupon validation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error validating coupon. Please try again.'
    ]);
}
?>
