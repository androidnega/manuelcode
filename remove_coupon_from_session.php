<?php
session_start();
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Remove coupon from session
unset($_SESSION['store_applied_coupon']);
unset($_SESSION['applied_coupon']);
unset($_SESSION['coupon_discount_info']);
unset($_SESSION['guest_discounted_amount']);
unset($_SESSION['guest_discount_amount']);

echo json_encode([
    'success' => true, 
    'message' => 'Coupon removed from session successfully'
]);
?>
