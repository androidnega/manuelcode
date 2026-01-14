<?php
session_start();
include '../includes/db.php';
include '../includes/otp_helper.php';
include '../includes/product_functions.php';
include '../includes/receipt_helper.php';
include '../includes/purchase_update_helper.php';
include '../config/payment_config.php';

// Handle Paystack callback for registered users
$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header('Location: ' . PAYMENT_FAILURE_REDIRECT);
    exit;
}

try {
    // FIXED: Enhanced payment verification with Paystack
    $verification_result = verifyPaystackPayment($reference);
    
    if (!$verification_result['success']) {
        // Payment verification failed - mark as failed
        $stmt = $pdo->prepare("UPDATE purchases SET status = 'failed', updated_at = NOW() WHERE payment_ref = ?");
        $stmt->execute([$reference]);
        
        error_log("Payment verification failed for reference: $reference");
        header('Location: ' . PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    $payment_data = $verification_result['data'];
    $metadata = $payment_data['metadata'] ?? [];
    
    // FIXED: Verify payment status from Paystack response
    if ($payment_data['status'] !== 'success') {
        // Payment was not successful - mark as failed
        $stmt = $pdo->prepare("UPDATE purchases SET status = 'failed', updated_at = NOW() WHERE payment_ref = ?");
        $stmt->execute([$reference]);
        
        error_log("Payment status not successful for reference: $reference. Status: " . $payment_data['status']);
        header('Location: ' . PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE payment_ref = ?");
    $stmt->execute([$reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        error_log("Order not found for reference: $reference");
        header('Location: ' . PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    // Check if order is already processed
    if ($order['status'] === 'paid') {
        header('Location: ' . PAYMENT_SUCCESS_REDIRECT);
        exit;
    }
    
    // FIXED: Check if user has already purchased this product (prevent duplicates)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as existing_purchases 
        FROM purchases 
        WHERE user_id = ? AND product_id = ? AND status = 'paid' AND id != ?
    ");
    $stmt->execute([$order['user_id'], $order['product_id'], $order['id']]);
    $existing_purchases = $stmt->fetch(PDO::FETCH_ASSOC)['existing_purchases'];
    
    if ($existing_purchases > 0) {
        // User already has this product - mark current order as duplicate and refund
        $stmt = $pdo->prepare("UPDATE purchases SET status = 'duplicate', updated_at = NOW() WHERE payment_ref = ?");
        $stmt->execute([$reference]);
        
        // Process automatic refund
        $transaction_id = $payment_data['id'];
        $refund_result = processAutomaticRefund($transaction_id, $order['amount'], 'Duplicate purchase detected - automatic refund');
        
        if ($refund_result['success']) {
            // Log the refund
            logRefundTransaction(
                $order['user_id'], 
                $order['id'], 
                $transaction_id, 
                $refund_result['refund_id'], 
                $order['amount'], 
                'Duplicate purchase detected - automatic refund'
            );
            
            // Redirect to refund notification page
            header('Location: ../refund_notification.php?refund_id=' . $refund_result['refund_reference'] . '&amount=' . $order['amount']);
            exit;
        } else {
            // Refund failed, log error
            error_log("Automatic refund failed for duplicate purchase: " . json_encode($refund_result));
            header('Location: ' . PAYMENT_FAILURE_REDIRECT);
            exit;
        }
    }
    
    // Get user and product details
    $stmt = $pdo->prepare("SELECT u.*, pr.title as product_title, pr.price as product_price FROM users u JOIN purchases p ON u.id = p.user_id JOIN products pr ON p.product_id = pr.id WHERE p.payment_ref = ?");
    $stmt->execute([$reference]);
    $user_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_order) {
        header('Location: ' . PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    // Begin transaction for normal payment processing
    $pdo->beginTransaction();
    
    // FIXED: Update purchase status to 'paid' only after successful verification
    $stmt = $pdo->prepare("UPDATE purchases SET status = 'paid', updated_at = NOW() WHERE payment_ref = ?");
    $stmt->execute([$reference]);
    
    // FIXED: Log payment verification properly
    try {
        $stmt = $pdo->prepare("CALL log_payment_verification(?, ?, 'verified', ?)");
        $stmt->execute([$reference, $payment_data['id'], json_encode($payment_data)]);
    } catch (Exception $e) {
        // If stored procedure doesn't exist, use direct insert
        $stmt = $pdo->prepare("
            INSERT INTO payment_verifications (payment_ref, transaction_id, status, payment_data, created_at) 
            VALUES (?, ?, 'verified', ?, NOW())
            ON DUPLICATE KEY UPDATE status = VALUES(status), payment_data = VALUES(payment_data), updated_at = NOW()
        ");
        $stmt->execute([$reference, $payment_data['id'], json_encode($payment_data)]);
    }
    
    // FIXED: Use comprehensive purchase update system to ensure admin dashboard reflection
    $update_result = updatePurchaseSystem($order['id'], $order['user_id']);
    
    if (!$update_result) {
        throw new Exception("Failed to update purchase system");
    }
    
    // FIXED: Log purchase transaction for admin reports
    try {
        logPurchaseTransaction(
            $order['user_id'], 
            $order['product_id'], 
            $order['id'], 
            $user_order['product_price'], 
            'paystack', 
            $reference
        );
    } catch (Exception $e) {
        // If function doesn't exist, create basic log
        $stmt = $pdo->prepare("
            INSERT INTO purchase_logs (user_id, product_id, purchase_id, amount, payment_method, reference, created_at) 
            VALUES (?, ?, ?, ?, 'paystack', ?, NOW())
        ");
        $stmt->execute([$order['user_id'], $order['product_id'], $order['id'], $user_order['product_price'], $reference]);
    }
    
    // FIXED: Generate receipt automatically
    try {
        $receipt_created = create_receipt($order['id'], $order['user_id']);
        if ($receipt_created) {
            error_log("Receipt created successfully for purchase: " . $order['id']);
        } else {
            error_log("Receipt creation failed for purchase: " . $order['id']);
        }
    } catch (Exception $e) {
        error_log("Receipt creation error: " . $e->getMessage());
    }
    
    // FIXED: Log payment success for admin orders page
    $stmt = $pdo->prepare("
        INSERT INTO payment_logs (user_id, order_id, reference, amount, status, payment_data, created_at) 
        VALUES (?, ?, ?, ?, 'success', ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), payment_data = VALUES(payment_data), updated_at = NOW()
    ");
    $stmt->execute([$order['user_id'], $order['id'], $reference, $user_order['product_price'], json_encode($payment_data)]);
    
    // FIXED: Update site statistics for admin dashboard
    try {
        updateGlobalStatistics();
    } catch (Exception $e) {
        error_log("Statistics update failed: " . $e->getMessage());
    }
    
    // FIXED: Create admin notification for new order
    try {
        createAdminNotification($order['id'], 'new_purchase');
    } catch (Exception $e) {
        error_log("Admin notification failed: " . $e->getMessage());
    }
    
    $pdo->commit();
    
    // Redirect to success page
    header('Location: ' . PAYMENT_SUCCESS_REDIRECT);
    exit;
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log error
    error_log("Payment callback error: " . $e->getMessage());
    
    // Mark purchase as failed if there was an error
    if (isset($order) && $order) {
        $stmt = $pdo->prepare("UPDATE purchases SET status = 'failed', updated_at = NOW() WHERE payment_ref = ?");
        $stmt->execute([$reference]);
    }
    
    header('Location: ' . PAYMENT_FAILURE_REDIRECT);
    exit;
}

function generateDownloadLink($order_id, $product_id, $user_id) {
    $token = base64_encode($order_id . '|' . $product_id . '|' . $user_id . '|' . time());
    return 'http://' . $_SERVER['HTTP_HOST'] . '/download.php?t=' . urlencode($token);
}

function sendSMSNotification($phone, $name, $order_id, $download_link, $product_title) {
    // Implementation depends on your SMS provider
    // This is a placeholder - implement based on your SMS service
    return ['success' => true, 'message' => 'SMS sent successfully'];
}
?>
