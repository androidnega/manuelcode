<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set error handler to catch fatal errors and show them
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal error in callback.php: " . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        // Show error page instead of blank page
        echo "<!DOCTYPE html><html><head><title>Payment Processing Error</title></head><body>";
        echo "<h1>Payment Processing Error</h1>";
        echo "<p>An error occurred while processing your payment. Please contact support with reference: " . ($_GET['reference'] ?? 'N/A') . "</p>";
        echo "<p><a href='../dashboard/my-purchases'>Go to Purchases</a></p>";
        echo "</body></html>";
        exit;
    }
});

// Include required files with error handling
$base_dir = dirname(__DIR__);

// Include database
$db_file = $base_dir . '/includes/db.php';
if (!file_exists($db_file)) {
    error_log("callback.php: db.php not found at: $db_file");
    die("Configuration error. Please contact support.");
}
include_once $db_file;

// Include other files - use include_once to prevent redeclaration
$includes = [
    'otp_helper.php',
    'product_functions.php',
    'receipt_helper.php',
    'purchase_update_helper.php'
];

foreach ($includes as $include_file) {
    $file_path = $base_dir . '/includes/' . $include_file;
    if (file_exists($file_path)) {
        include_once $file_path;
    } else {
        error_log("callback.php: $include_file not found at: $file_path (continuing anyway)");
    }
}

// Include payment config - CRITICAL: This file contains verifyPaystackPayment function
$payment_config_loaded = false;
$payment_config_paths = [
    $base_dir . '/config/payment_config.php',
    dirname(__DIR__) . '/config/payment_config.php',
    __DIR__ . '/../config/payment_config.php'
];

foreach ($payment_config_paths as $payment_config) {
    if (file_exists($payment_config)) {
        include_once $payment_config;
        $payment_config_loaded = true;
        error_log("callback.php: payment_config.php loaded from: $payment_config");
        break;
    }
}

// If payment_config.php not found, load settings from database and define functions
if (!$payment_config_loaded) {
    error_log("callback.php: payment_config.php not found in any location, loading from database");
    
    // Function to get setting from database
    if (!function_exists('getSetting')) {
        function getSetting($key, $default = '') {
            global $pdo;
            try {
                $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result ? $result['value'] : $default;
            } catch (Exception $e) {
                error_log("Error getting setting $key: " . $e->getMessage());
                return $default;
            }
        }
    }
    
    // Load Paystack credentials from database
    $paystack_secret_key = getSetting('paystack_secret_key', '');
    if (empty($paystack_secret_key)) {
        $paystack_secret_key = getSetting('paystack_live_secret_key', '');
    }
    
    if (!defined('PAYSTACK_SECRET_KEY')) {
        define('PAYSTACK_SECRET_KEY', $paystack_secret_key);
    }
    if (!defined('PAYSTACK_CURRENCY')) {
        define('PAYSTACK_CURRENCY', 'GHS');
    }
    
    error_log("Loaded Paystack secret key from database: " . (empty($paystack_secret_key) ? 'EMPTY' : 'OK'));
}

// Define redirect URLs if not already defined
if (!defined('PAYMENT_SUCCESS_REDIRECT')) define('PAYMENT_SUCCESS_REDIRECT', '../dashboard/my-purchases');
if (!defined('PAYMENT_FAILURE_REDIRECT')) define('PAYMENT_FAILURE_REDIRECT', '../store.php?error=payment_failed');

// Define verifyPaystackPayment function if it doesn't exist
if (!function_exists('verifyPaystackPayment')) {
    error_log("callback.php: Defining verifyPaystackPayment function");
    
    function verifyPaystackPayment($reference) {
        $url = 'https://api.paystack.co/transaction/verify/' . $reference;
        
        $headers = [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            error_log("Paystack verification CURL error: " . $error);
            return ['success' => false, 'message' => 'CURL Error: ' . $error];
        }
        
        if ($http_code !== 200) {
            error_log("Paystack verification HTTP error: " . $http_code);
            return ['success' => false, 'message' => 'Payment verification failed - HTTP ' . $http_code];
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['status'])) {
            error_log("Paystack verification invalid response: " . $response);
            return ['success' => false, 'message' => 'Invalid response from Paystack'];
        }
        
        // Check both API status and transaction status
        if ($result['status'] === true && isset($result['data']) && $result['data']['status'] === 'success') {
            $transaction_data = $result['data'];
            
            if (!isset($transaction_data['amount']) || $transaction_data['amount'] <= 0) {
                error_log("Paystack verification failed: Invalid amount");
                return ['success' => false, 'message' => 'Payment verification failed - Invalid amount'];
            }
            
            if (isset($transaction_data['currency']) && $transaction_data['currency'] !== PAYSTACK_CURRENCY) {
                error_log("Paystack verification failed: Currency mismatch");
                return ['success' => false, 'message' => 'Payment verification failed - Currency mismatch'];
            }
            
            error_log("Paystack payment verified successfully for reference: " . $reference);
            return ['success' => true, 'data' => $transaction_data];
        } else {
            $status = $result['data']['status'] ?? 'unknown';
            error_log("Paystack verification failed: Transaction status is " . $status);
            return ['success' => false, 'message' => 'Payment verification failed - Status: ' . $status];
        }
    }
}

// Handle Paystack callback for registered users
$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    header('Location: ' . PAYMENT_FAILURE_REDIRECT);
    exit;
}

try {
    // FIXED: Enhanced payment verification with Paystack
    error_log("Callback started for reference: $reference");
    
    $verification_result = verifyPaystackPayment($reference);
    error_log("Verification result: " . json_encode($verification_result));
    
    if (!$verification_result['success']) {
        // Payment verification failed - mark as failed
        $stmt = $pdo->prepare("UPDATE purchases SET status = 'failed', updated_at = NOW() WHERE payment_ref = ?");
        $stmt->execute([$reference]);
        
        error_log("Payment verification failed for reference: $reference - " . ($verification_result['message'] ?? 'Unknown error'));
        
        // Show detailed error instead of just redirecting
        echo "<!DOCTYPE html><html><head><title>Payment Verification Failed</title></head><body>";
        echo "<h1>Payment Verification Failed</h1>";
        echo "<p>Reference: $reference</p>";
        echo "<p>Error: " . ($verification_result['message'] ?? 'Unknown error') . "</p>";
        echo "<p><a href='" . PAYMENT_FAILURE_REDIRECT . "'>Go to Store</a></p>";
        echo "</body></html>";
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
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE payment_ref = ? OR reference = ?");
    $stmt->execute([$reference, $reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        error_log("Order not found for reference: $reference");
        
        // Show detailed error with database info
        echo "<!DOCTYPE html><html><head><title>Order Not Found</title></head><body>";
        echo "<h1>Order Not Found</h1>";
        echo "<p>Reference: $reference</p>";
        
        // Check if any orders exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM purchases");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total orders in database: $count</p>";
        
        // Check for similar references
        $stmt = $pdo->prepare("SELECT payment_ref, reference, status, created_at FROM purchases WHERE payment_ref LIKE ? OR reference LIKE ? ORDER BY created_at DESC LIMIT 5");
        $like_ref = '%' . substr($reference, 0, 10) . '%';
        $stmt->execute([$like_ref, $like_ref]);
        $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($similar) {
            echo "<h3>Similar orders found:</h3><ul>";
            foreach ($similar as $s) {
                echo "<li>{$s['payment_ref']} / {$s['reference']} - Status: {$s['status']} - Created: {$s['created_at']}</li>";
            }
            echo "</ul>";
        }
        
        echo "<p><a href='" . PAYMENT_FAILURE_REDIRECT . "'>Go to Store</a></p>";
        echo "</body></html>";
        exit;
    }
    
    error_log("Order found: " . json_encode($order));
    
    // Check if order is already processed
    if ($order['status'] === 'paid') {
        error_log("Order already marked as paid, redirecting to success page");
        
        // Show success message with download option
        echo "<!DOCTYPE html><html><head><title>Payment Already Processed</title>";
        echo "<meta http-equiv='refresh' content='3;url=" . PAYMENT_SUCCESS_REDIRECT . "'>";
        echo "</head><body style='font-family:Arial;text-align:center;padding:50px;'>";
        echo "<h1 style='color:#4CAF50;'>✓ Payment Already Processed</h1>";
        echo "<p>Your purchase has already been recorded.</p>";
        echo "<p>Redirecting you to your purchases page...</p>";
        echo "<p><a href='" . PAYMENT_SUCCESS_REDIRECT . "'>Click here if not redirected</a></p>";
        echo "</body></html>";
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
    
    // FIXED: Update purchase status to 'paid' immediately after successful verification
    // This ensures orders are marked as paid, not pending, in admin dashboard
    $stmt = $pdo->prepare("UPDATE purchases SET status = 'paid', updated_at = NOW() WHERE payment_ref = ? OR reference = ?");
    $stmt->execute([$reference, $reference]);
    
    // Also update any pending orders with the same reference to ensure consistency
    $stmt = $pdo->prepare("UPDATE purchases SET status = 'paid', updated_at = NOW() WHERE (payment_ref = ? OR reference = ?) AND status = 'pending'");
    $stmt->execute([$reference, $reference]);
    
    // FIXED: Log payment verification properly
    try {
        // First try stored procedure
        $stmt = $pdo->prepare("CALL log_payment_verification(?, ?, 'verified', ?)");
        $stmt->execute([$reference, $payment_data['id'], json_encode($payment_data)]);
        error_log("Payment verification logged via stored procedure");
    } catch (Exception $e) {
        error_log("Stored procedure failed, using direct insert: " . $e->getMessage());
        // If stored procedure doesn't exist, try direct insert
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_verifications (payment_ref, transaction_id, status, payment_data, created_at) 
                VALUES (?, ?, 'verified', ?, NOW())
                ON DUPLICATE KEY UPDATE status = VALUES(status), payment_data = VALUES(payment_data), updated_at = NOW()
            ");
            $stmt->execute([$reference, $payment_data['id'], json_encode($payment_data)]);
            error_log("Payment verification logged via direct insert");
        } catch (Exception $e2) {
            // Table might not exist, just log and continue
            error_log("Payment verification table not found: " . $e2->getMessage());
        }
    }
    
    // FIXED: Use comprehensive purchase update system to ensure admin dashboard reflection
    if (function_exists('updatePurchaseSystem')) {
        $update_result = updatePurchaseSystem($order['id'], $order['user_id']);
        
        if (!$update_result) {
            error_log("Failed to update purchase system for order: " . $order['id']);
        }
    }
    
    // FIXED: Log purchase transaction for admin reports
    if (function_exists('logPurchaseTransaction')) {
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
            error_log("Error logging purchase transaction: " . $e->getMessage());
        }
    } else {
        // If function doesn't exist, create basic log
        try {
            $stmt = $pdo->prepare("
                INSERT INTO purchase_logs (user_id, product_id, purchase_id, amount, payment_method, reference, created_at) 
                VALUES (?, ?, ?, ?, 'paystack', ?, NOW())
            ");
            $stmt->execute([$order['user_id'], $order['product_id'], $order['id'], $user_order['product_price'], $reference]);
        } catch (Exception $e) {
            error_log("Error creating purchase log: " . $e->getMessage());
        }
    }
    
    // FIXED: Generate receipt automatically
    if (function_exists('create_receipt')) {
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
    }
    
    // FIXED: Log payment success for admin orders page
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_logs (user_id, order_id, reference, amount, status, payment_data, created_at) 
            VALUES (?, ?, ?, ?, 'success', ?, NOW())
            ON DUPLICATE KEY UPDATE status = VALUES(status), payment_data = VALUES(payment_data), updated_at = NOW()
        ");
        $stmt->execute([$order['user_id'], $order['id'], $reference, $user_order['product_price'], json_encode($payment_data)]);
        error_log("Payment log created successfully");
    } catch (Exception $e) {
        // Table might not exist, just log and continue
        error_log("Payment logs table not found: " . $e->getMessage());
    }
    
    // FIXED: Update site statistics for admin dashboard
    if (function_exists('updateGlobalStatistics')) {
        try {
            updateGlobalStatistics();
        } catch (Exception $e) {
            error_log("Statistics update failed: " . $e->getMessage());
        }
    }
    
    // FIXED: Create admin notification for new order
    if (function_exists('createAdminNotification')) {
        try {
            createAdminNotification($order['id'], 'new_purchase');
        } catch (Exception $e) {
            error_log("Admin notification failed: " . $e->getMessage());
        }
    }
    
    $pdo->commit();
    
    error_log("Payment processed successfully for reference: $reference, User ID: {$order['user_id']}, Product ID: {$order['product_id']}");
    
    // Show success page with auto-redirect
    echo "<!DOCTYPE html><html><head><title>Payment Successful</title>";
    echo "<meta http-equiv='refresh' content='3;url=" . PAYMENT_SUCCESS_REDIRECT . "'>";
    echo "<style>body{font-family:Arial;text-align:center;padding:50px;background:#f5f5f5;} .box{max-width:500px;margin:0 auto;background:white;padding:40px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} h1{color:#4CAF50;} .btn{display:inline-block;margin:20px 10px;padding:12px 24px;background:#4CAF50;color:white;text-decoration:none;border-radius:4px;font-weight:bold;} .btn:hover{background:#45a049;}</style>";
    echo "</head><body><div class='box'>";
    echo "<h1>✓ Payment Successful!</h1>";
    echo "<p>Your purchase has been processed successfully.</p>";
    echo "<p><strong>Reference:</strong> $reference</p>";
    echo "<p>Redirecting you to your downloads...</p>";
    echo "<a href='" . PAYMENT_SUCCESS_REDIRECT . "' class='btn'>View My Purchases</a>";
    echo "</div></body></html>";
    exit;
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log detailed error
    error_log("Payment callback error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Show detailed error page
    echo "<!DOCTYPE html><html><head><title>Payment Processing Error</title></head><body>";
    echo "<h1>Payment Processing Error</h1>";
    echo "<p><strong>Reference:</strong> " . htmlspecialchars($reference) . "</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")</p>";
    
    // Check if order exists
    if (isset($reference)) {
        try {
            $stmt = $pdo->prepare("SELECT id, user_id, product_id, status, amount FROM purchases WHERE payment_ref = ? OR reference = ?");
            $stmt->execute([$reference, $reference]);
            $check_order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($check_order) {
                echo "<h3>Order Found in Database:</h3>";
                echo "<ul>";
                echo "<li>Order ID: {$check_order['id']}</li>";
                echo "<li>User ID: {$check_order['user_id']}</li>";
                echo "<li>Product ID: {$check_order['product_id']}</li>";
                echo "<li>Status: <strong>{$check_order['status']}</strong></li>";
                echo "<li>Amount: GHS {$check_order['amount']}</li>";
                echo "</ul>";
                
                // If status is pending, update it to paid
                if ($check_order['status'] === 'pending') {
                    echo "<p style='color:orange;'>⚠️ Order is marked as PENDING. Let me try to update it...</p>";
                    
                    try {
                        $stmt = $pdo->prepare("UPDATE purchases SET status = 'paid', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$check_order['id']]);
                        echo "<p style='color:green;'>✓ Order status updated to PAID!</p>";
                        echo "<p><a href='" . PAYMENT_SUCCESS_REDIRECT . "'>Go to My Purchases</a></p>";
                    } catch (Exception $e2) {
                        echo "<p style='color:red;'>✗ Failed to update order: " . htmlspecialchars($e2->getMessage()) . "</p>";
                    }
                }
            } else {
                echo "<p style='color:red;'>✗ Order not found in database</p>";
            }
        } catch (Exception $e3) {
            echo "<p style='color:red;'>Error checking order: " . htmlspecialchars($e3->getMessage()) . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='" . PAYMENT_FAILURE_REDIRECT . "'>Go to Store</a> | <a href='" . PAYMENT_SUCCESS_REDIRECT . "'>My Purchases</a></p>";
    echo "</body></html>";
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
