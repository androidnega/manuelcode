<?php
session_start();
include '../includes/db.php';
include '../includes/product_functions.php';
include '../config/payment_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get product ID from request
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
    exit;
}

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }
    
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    // FIXED: Check if user can purchase this product (prevents duplicates)
    $user_email = $user['email'] ?? null;
    if (!canUserPurchaseProduct($user_id, $product_id, $user_email)) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'You have already purchased this product or have a pending order']);
        exit;
    }
    
    // Generate unique reference
    $reference = 'PAY_' . time() . '_' . $user_id . '_' . $product_id;
    
    // Check if reference already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE payment_ref = ?");
    $stmt->execute([$reference]);
    if ($stmt->fetchColumn() > 0) {
        // Generate new reference if duplicate
        $reference = 'PAY_' . time() . '_' . $user_id . '_' . $product_id . '_' . rand(1000, 9999);
    }
    
    // Check for existing pending order
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE user_id = ? AND product_id = ? AND status IN ('pending', 'processing')");
    $stmt->execute([$user_id, $product_id]);
    $existing_order = $stmt->fetch();
    
    if ($existing_order) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Order already processed']);
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Create order in database with 'pending' status (not 'paid' yet)
    $stmt = $pdo->prepare("
        INSERT INTO purchases (user_id, product_id, payment_ref, reference, amount, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$user_id, $product_id, $reference, $reference, $product['price']]);
    $order_id = $pdo->lastInsertId();
    
    // Generate download link (will be available after payment verification)
    $download_link = generateDownloadLink($order_id, $product_id, $user_id);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response with pending status
    echo json_encode([
        'status' => 'success',
        'message' => 'Order created successfully. Please complete payment.',
        'order_id' => $order_id,
        'reference' => $reference,
        'download_link' => $download_link,
        'product_title' => $product['title'],
        'amount' => $product['price'],
        'payment_status' => 'pending'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Payment processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}

/**
 * Verify payment with Paystack API
 */
function verifyPaystackPayment($reference) {
    $url = "https://api.paystack.co/transaction/verify/" . urlencode($reference);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Cache-Control: no-cache'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return ['success' => false, 'message' => 'Payment verification failed'];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['status'])) {
        return ['success' => false, 'message' => 'Invalid response from Paystack'];
    }
    
    if ($result['status'] === true && $result['data']['status'] === 'success') {
        return ['success' => true, 'data' => $result['data']];
    }
    
    return ['success' => false, 'message' => 'Payment not successful'];
}

/**
 * Send SMS notification using Arkassel API
 */
function sendSMSNotification($phone, $user_name, $order_id, $download_link, $product_title) {
    $message = str_replace(
        ['{user_name}', '{order_id}', '{download_link}', '{product_title}'],
        [$user_name, $order_id, $download_link, $product_title],
        SMS_PAYMENT_SUCCESS
    );
    
    $data = [
        'api_key' => ARKASSEL_API_KEY,
        'to' => $phone,
        'message' => $message,
        'sender_id' => ARKASSEL_SENDER_ID
    ];
    
    $ch = curl_init(ARKASSEL_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $http_code === 200 && isset($result['status']) && $result['status'] === 'success',
        'message' => $message,
        'response' => $result
    ];
}

/**
 * Generate secure download link
 */
function generateDownloadLink($order_id, $product_id, $user_id) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    global $pdo;
    
    // Store download token
    $stmt = $pdo->prepare("
        INSERT INTO download_tokens (order_id, product_id, user_id, token, expires_at, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$order_id, $product_id, $user_id, $token, $expiry]);
    
    return "http://localhost/ManuelCode.info/manuelcode/download.php?token=" . $token;
}
?>
