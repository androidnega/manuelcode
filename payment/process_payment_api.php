<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Include required files with error handling
if (!file_exists('../includes/db.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database configuration file not found']);
    exit;
}
include '../includes/db.php';

if (!file_exists('../includes/coupon_helper.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Coupon helper file not found']);
    exit;
}
include '../includes/coupon_helper.php';

if (!file_exists('../config/payment_config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment configuration file not found']);
    exit;
}
include '../config/payment_config.php';

// Check if required constants are defined
if (!defined('PAYSTACK_SECRET_KEY')) {
    error_log("PAYSTACK_SECRET_KEY not defined");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment configuration error']);
    exit;
}

if (!defined('PAYSTACK_GUEST_CALLBACK_URL') || !defined('PAYSTACK_CALLBACK_URL')) {
    error_log("Paystack callback URLs not defined");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment callback URLs not configured']);
    exit;
}

// Check if database connection is available
if (!isset($pdo) || $pdo === null) {
    error_log("Database connection not available");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Check if CouponManager class exists
if (!class_exists('CouponManager')) {
    error_log("CouponManager class not found");
    // Don't exit - coupon functionality will just be unavailable
}

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = (int)$input['product_id'];
// Fix: Accept both boolean and string 'true' for is_guest
$is_guest = isset($input['is_guest']) && ($input['is_guest'] === true || $input['is_guest'] === 'true' || $input['is_guest'] === '1');
$coupon_data = $input['coupon_data'] ?? null;

// Debug logging
error_log("Payment API - Product ID: {$product_id}, Is Guest: " . ($is_guest ? 'true' : 'false') . ", Has Guest Data: " . (isset($_SESSION['guest_data']) ? 'yes' : 'no'));

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Initialize variables
    $final_amount = $product['price'];
    $discount_amount = 0;
    $coupon_id = null;
    $user_id = null;
    
    if ($is_guest) {
        // Handle guest payment
        if (!isset($_SESSION['guest_data'])) {
            error_log("Payment API Error: Guest payment attempted but guest_data not found in session");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Guest data not found. Please fill in your details again.']);
            exit;
        }
        
        $guest_data = $_SESSION['guest_data'];
        $email = $guest_data['email'];
        $name = $guest_data['name'];
        $phone = $guest_data['phone'] ?? '';
        
        // Generate unique payment reference
        $reference = generatePaymentReference('GUEST_' . time());
        
        // Create pending guest order record with final_amount (supports both total_amount and amount columns)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO guest_orders (email, name, phone, product_id, total_amount, reference, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$email, $name, $phone, $product_id, $final_amount, $reference]);
        } catch (PDOException $e) {
            // If total_amount column doesn't exist, try with amount column
            if (strpos($e->getMessage(), 'total_amount') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                error_log("total_amount column not found, trying amount column");
                $stmt = $pdo->prepare("
                    INSERT INTO guest_orders (email, name, phone, product_id, amount, reference, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$email, $name, $phone, $product_id, $final_amount, $reference]);
            } else {
                throw $e;
            }
        }
        
    } else {
        // Handle logged-in user payment
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Get user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        // Generate unique user ID if not exists
        if (empty($user['user_id'])) {
            $unique_user_id = generateUniqueUserId();
            $stmt = $pdo->prepare("UPDATE users SET user_id = ? WHERE id = ?");
            $stmt->execute([$unique_user_id, $user_id]);
            $user['user_id'] = $unique_user_id;
        }
        
        // Check if user already purchased this product
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE user_id = ? AND product_id = ? AND status = 'paid'");
        $stmt->execute([$user_id, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already purchased this product']);
            exit;
        }
        
        $email = $user['email'];
        $name = $user['name'];
        $phone = $user['phone'] ?? '';
        
        // Generate unique payment reference
        $reference = generatePaymentReference($user['user_id']);
        
        // Create pending purchase record (this will be updated to 'paid' only after successful payment)
        $stmt = $pdo->prepare("
            INSERT INTO purchases (user_id, product_id, payment_ref, reference, amount, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $product_id, $reference, $reference, $final_amount]);
    }
    
    // Calculate final amount with coupon discount (after getting user_id for logged-in users)
    // FIXED: Check for discounted amount in session first
    if ($is_guest && isset($_SESSION['guest_discounted_amount'])) {
        $final_amount = $_SESSION['guest_discounted_amount'];
        $discount_amount = $_SESSION['guest_discount_amount'] ?? 0;
        error_log("Using guest discounted amount from session: {$final_amount}");
    } elseif (!$is_guest && isset($_SESSION['user_discounted_amount'])) {
        $final_amount = $_SESSION['user_discounted_amount'];
        $discount_amount = $_SESSION['user_discount_amount'] ?? 0;
        error_log("Using user discounted amount from session: {$final_amount}");
    } elseif (isset($input['coupon_data']) && $input['coupon_data']) {
        $coupon_data = $input['coupon_data'];
        try {
            // Check if CouponManager is available
            if (!class_exists('CouponManager')) {
                throw new Exception('Coupon system not available');
            }
            // Use CouponManager for validation
            $couponManager = new CouponManager($pdo);
            // Fix: Pass null for user_id when guest, use $user_id for logged-in users
            $validation_user_id = $is_guest ? null : $user_id;
            $validation_result = $couponManager->validateCoupon($coupon_data['code'], $validation_user_id, $product_id, $product['price']);
            
            if ($validation_result['valid']) {
                $coupon = $validation_result['coupon'];
                $discount_amount = $couponManager->calculateDiscount($coupon, $product['price']);
                $final_amount = $product['price'] - $discount_amount;
                $coupon_id = $coupon['id'];
                
                // Ensure final amount doesn't go below 0
                $final_amount = max(0, $final_amount);
            } else {
                // Return error message if coupon is invalid
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $validation_result['message']]);
                exit;
            }
        } catch (Exception $e) {
            error_log("Coupon processing error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Error processing coupon']);
            exit;
        }
    }
    
    // Update order with final amount if it changed
    if ($final_amount != $product['price']) {
        if ($is_guest) {
            try {
                $stmt = $pdo->prepare("UPDATE guest_orders SET total_amount = ? WHERE reference = ?");
                $stmt->execute([$final_amount, $reference]);
            } catch (PDOException $e) {
                // Try with amount column if total_amount doesn't exist
                if (strpos($e->getMessage(), 'total_amount') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                    $stmt = $pdo->prepare("UPDATE guest_orders SET amount = ? WHERE reference = ?");
                    $stmt->execute([$final_amount, $reference]);
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE purchases SET amount = ? WHERE reference = ?");
            $stmt->execute([$final_amount, $reference]);
        }
    }
    
    // Check if product becomes free after discount
    if ($final_amount <= 0) {
        // Handle free product download directly
        if ($is_guest) {
            // For guests, redirect to login for free products
            echo json_encode(['success' => false, 'message' => 'Free products require login. Please login to download.']);
            exit;
        } else {
            // For logged-in users, create a free purchase record
            $stmt = $pdo->prepare("
                UPDATE purchases SET status = 'paid', amount = 0, updated_at = NOW() 
                WHERE user_id = ? AND product_id = ? AND status = 'pending'
            ");
            $stmt->execute([$user_id, $product_id]);
            
            echo json_encode([
                'success' => true,
                'redirect_url' => '../download_free_product.php?id=' . $product_id,
                'message' => 'Free product added to your purchases!'
            ]);
            exit;
        }
    }
    
    // FIXED: Initialize Paystack payment with proper integer conversion
    // Convert to kobo (smallest currency unit) and ensure it's an integer
    $amount = 0;
    
    if (is_numeric($final_amount) && $final_amount > 0) {
        $amount = intval(round($final_amount * 100));
        error_log("Amount conversion: Final amount: {$final_amount}, Kobo: {$amount}");
    } else {
        error_log("Invalid final amount: " . var_export($final_amount, true));
        echo json_encode(['success' => false, 'message' => 'Invalid amount for payment']);
        exit;
    }
    
    $data = [
        'amount' => $amount,
        'email' => $email,
        'reference' => $reference,
        'callback_url' => $is_guest ? PAYSTACK_GUEST_CALLBACK_URL : PAYSTACK_CALLBACK_URL,
        'metadata' => [
            'user_id' => $user_id ?? null,
            'product_id' => $product_id,
            'is_guest' => $is_guest,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'coupon_id' => $coupon_id,
            'discount_amount' => $discount_amount,
            'final_amount' => $final_amount
        ]
    ];
    
    $ch = curl_init('https://api.paystack.co/transaction/initialize');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Handle CURL errors
    if ($curl_error) {
        error_log("Paystack CURL Error: " . $curl_error);
        // Delete pending record
        if ($is_guest) {
            $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE reference = ?");
            $stmt->execute([$reference]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM purchases WHERE reference = ?");
            $stmt->execute([$reference]);
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Connection error. Please check your internet connection and try again.'
        ]);
        exit;
    }
    
    $result = json_decode($response, true);
    
    // Log response for debugging
    error_log("Paystack API Response - HTTP Code: {$http_code}, Response: " . substr($response, 0, 500));
    
    if ($http_code === 200 && isset($result['status']) && $result['status'] === true) {
        echo json_encode([
            'success' => true,
            'authorization_url' => $result['data']['authorization_url'],
            'reference' => $reference,
            'amount' => $final_amount
        ]);
    } else {
        // If Paystack initialization failed, delete the pending record
        if ($is_guest) {
            $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE reference = ?");
            $stmt->execute([$reference]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM purchases WHERE reference = ?");
            $stmt->execute([$reference]);
        }
        
        $error_message = 'Failed to initialize payment';
        if (isset($result['message'])) {
            $error_message = $result['message'];
        } elseif ($http_code !== 200) {
            $error_message = "Payment service error (HTTP {$http_code}). Please try again.";
        }
        
        http_response_code($http_code !== 200 ? $http_code : 400);
        echo json_encode([
            'success' => false, 
            'message' => $error_message
        ]);
    }
    
} catch (Exception $e) {
    error_log("Payment initialization error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure we return JSON with proper HTTP status
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your payment. Please try again.'
    ]);
    exit;
}

// Define helper functions at the top to ensure they're available
if (!function_exists('generatePaymentReference')) {
    function generatePaymentReference($prefix) {
        return $prefix . '_' . time() . '_' . rand(1000, 9999);
    }
}

if (!function_exists('generateUniqueUserId')) {
    function generateUniqueUserId() {
        return 'U' . strtoupper(substr(md5(uniqid()), 0, 5));
    }
}
