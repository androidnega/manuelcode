<?php
session_start();
include '../includes/db.php';
include '../includes/otp_helper.php';
include '../includes/receipt_helper.php';
include '../includes/purchase_update_helper.php';
include '../config/payment_config.php';

// Handle Paystack callback for guest users
$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header('Location: ' . GUEST_PAYMENT_FAILURE_REDIRECT);
    exit;
}

try {
    // Verify payment with Paystack
    $verification_result = verifyPaystackPayment($reference);
    
    if (!$verification_result['success']) {
        // Payment verification failed - mark as failed
        $stmt = $pdo->prepare("UPDATE guest_orders SET status = 'failed', updated_at = NOW() WHERE reference = ?");
        $stmt->execute([$reference]);
        
        header('Location: ' . GUEST_PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    $payment_data = $verification_result['data'];
    $metadata = $payment_data['metadata'] ?? [];
    
    // Get guest order details
    $stmt = $pdo->prepare("SELECT * FROM guest_orders WHERE reference = ?");
    $stmt->execute([$reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: ' . GUEST_PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    // Check if order is already processed
    if ($order['status'] === 'paid') {
        header('Location: ' . GUEST_PAYMENT_SUCCESS_REDIRECT . '?order_id=' . $order['id']);
        exit;
    }
    
    // Check if payment was actually successful
    if ($payment_data['status'] !== 'success') {
        // Payment was not successful - mark as failed
        $stmt = $pdo->prepare("UPDATE guest_orders SET status = 'failed', updated_at = NOW() WHERE reference = ?");
        $stmt->execute([$reference]);
        
        header('Location: ' . GUEST_PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    // Get product details
    $stmt = $pdo->prepare("SELECT p.title as product_title, p.price as product_price FROM products p WHERE p.id = ?");
    $stmt->execute([$order['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: ' . GUEST_PAYMENT_FAILURE_REDIRECT);
        exit;
    }
    
    // Check for duplicate payments for guest (by email and product)
    $duplicate_check = detectGuestDuplicatePayments($order['email'], $order['product_id'], $order['total_amount']);
    
    if ($duplicate_check['is_duplicate'] && AUTO_REFUND_ENABLED) {
        // Process automatic refund for duplicate payment
        $transaction_id = $payment_data['id'];
        $refund_result = processAutomaticRefund($transaction_id, $order['total_amount'], 'Duplicate guest payment detected - automatic refund');
        
        if ($refund_result['success']) {
            // Log the refund
            logRefundTransaction(
                null, 
                $order['id'], 
                $transaction_id, 
                $refund_result['refund_id'], 
                $order['total_amount'], 
                'Duplicate guest payment detected - automatic refund',
                $order['email']
            );
            
            // Send refund notification SMS
            if (!empty($order['phone'])) {
                $sms_result = sendRefundSMS(
                    $order['phone'],
                    $order['name'],
                    $order['total_amount'],
                    $refund_result['refund_reference']
                );
                
                // Log SMS result
                $stmt = $pdo->prepare("
                    INSERT INTO sms_logs (guest_order_id, phone, message, status, response_data, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $order['id'], 
                    $order['phone'], 
                    $sms_result['message'], 
                    $sms_result['success'] ? 'sent' : 'failed',
                    json_encode($sms_result)
                ]);
            }
            
            // Update order status to refunded
            $stmt = $pdo->prepare("UPDATE guest_orders SET status = 'refunded', updated_at = NOW() WHERE reference = ?");
            $stmt->execute([$reference]);
            
            // Log the duplicate payment detection
            error_log("Duplicate guest payment detected and refunded: Email {$order['email']}, Product ID {$order['product_id']}, Amount {$order['total_amount']}, Refund ID {$refund_result['refund_id']}");
            
            // Redirect to refund notification page
            header('Location: ../refund_notification.php?refund_id=' . $refund_result['refund_reference'] . '&amount=' . $order['total_amount']);
            exit;
        } else {
            // Refund failed, log error but still process the payment
            error_log("Automatic guest refund failed: " . json_encode($refund_result));
        }
    }
    
    // Begin transaction for normal payment processing
    $pdo->beginTransaction();
    
    // Log payment verification
    $stmt = $pdo->prepare("CALL log_payment_verification(?, ?, 'verified', ?)");
    $stmt->execute([$reference, $payment_data['id'], json_encode($payment_data)]);
    
    // Use comprehensive purchase update system for guest order
    $update_result = updatePurchaseSystem(null, null, $order['id']);
    
    if (!$update_result) {
        throw new Exception("Failed to update guest purchase system");
    }
    
    // Generate download link for guest
    $download_link = generateGuestDownloadLink($order['id'], $order['product_id'], $order['email']);
    
    // Log payment success
    $stmt = $pdo->prepare("
        INSERT INTO payment_logs (guest_order_id, reference, amount, status, payment_data, created_at) 
        VALUES (?, ?, ?, 'success', ?, NOW())
    ");
    $stmt->execute([$order['id'], $reference, $order['total_amount'], json_encode($payment_data)]);
    
    // Generate and send receipt for guest
    $receipt_created = create_user_receipt(null, null, $order['email']);
    
    // Store download link in session for thank you page
    $_SESSION['guest_download_link'] = $download_link;
    $_SESSION['guest_order_id'] = $order['id'];
    $_SESSION['guest_product_title'] = $product['product_title'];
    
    $pdo->commit();
    
    // Redirect to success page
    header('Location: ' . GUEST_PAYMENT_SUCCESS_REDIRECT . '?order_id=' . $order['id']);
    exit;
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log error
    error_log("Guest payment callback error: " . $e->getMessage());
    
    // Mark guest order as failed if there was an error
    if (isset($order) && $order) {
        $stmt = $pdo->prepare("UPDATE guest_orders SET status = 'failed', updated_at = NOW() WHERE reference = ?");
        $stmt->execute([$reference]);
    }
    
    header('Location: ' . GUEST_PAYMENT_FAILURE_REDIRECT);
    exit;
}

/**
 * Detect duplicate payments for guest users within a time window
 */
function detectGuestDuplicatePayments($email, $product_id, $amount, $time_window = DUPLICATE_PAYMENT_WINDOW) {
    global $pdo;
    
    try {
        // Check for recent successful payments for the same email and product
        $stmt = $pdo->prepare("
            SELECT go.*, p.price as product_price 
            FROM guest_orders go 
            JOIN products p ON go.product_id = p.id 
            WHERE go.email = ? 
            AND go.product_id = ? 
            AND go.status = 'paid' 
            AND go.created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY go.created_at DESC
        ");
        $stmt->execute([$email, $product_id, $time_window]);
        $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent_payments) > 1) {
            // Found duplicate payments
            return [
                'is_duplicate' => true,
                'duplicate_count' => count($recent_payments),
                'recent_payments' => $recent_payments,
                'message' => 'Duplicate guest payment detected: ' . count($recent_payments) . ' payments within ' . $time_window . ' seconds'
            ];
        }
        
        return ['is_duplicate' => false];
        
    } catch (Exception $e) {
        error_log("Error detecting duplicate guest payments: " . $e->getMessage());
        return ['is_duplicate' => false, 'error' => $e->getMessage()];
    }
}

function generateGuestDownloadLink($order_id, $product_id, $email) {
    $token = base64_encode($order_id . '|' . $product_id . '|' . $email . '|' . time());
    return 'http://' . $_SERVER['HTTP_HOST'] . '/download.php?t=' . urlencode($token);
}

function sendSMSNotification($phone, $name, $order_id, $download_link, $product_title) {
    // Implementation depends on your SMS provider
    // This is a placeholder - implement based on your SMS service
    return ['success' => true, 'message' => 'SMS sent successfully'];
}
?>
