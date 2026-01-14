<?php
session_start();
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['code']) || !isset($data['discount_type']) || !isset($data['discount_value'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid coupon data']);
    exit;
}

// Store coupon in session
$_SESSION['store_applied_coupon'] = [
    'code' => $data['code'],
    'discount_type' => $data['discount_type'],
    'discount_value' => $data['discount_value'],
    'applied_at' => time()
];

// Also store for backward compatibility with existing code
$_SESSION['applied_coupon'] = $data['code'];
$_SESSION['coupon_discount_info'] = [
    'discount_type' => $data['discount_type'],
    'discount_value' => $data['discount_value']
];

echo json_encode([
    'success' => true, 
    'message' => 'Coupon stored in session successfully'
]);
?>
