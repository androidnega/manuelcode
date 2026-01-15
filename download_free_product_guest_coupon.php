<?php
session_start();
include 'includes/db.php';
include 'includes/coupon_helper.php';
include 'includes/download_tracker.php';

$product_id = intval($_GET['id']);
$coupon_code = $_GET['coupon'] ?? '';

if (empty($coupon_code)) {
    header('Location: product.php?id=' . $product_id . '&error=No coupon code provided');
    exit();
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php?error=Product not found');
    exit();
}

// Validate the coupon (no user_id for guests)
$couponManager = new CouponManager($pdo);
$couponValidation = $couponManager->validateCoupon($coupon_code, null, $product_id, $product['price']);

if (!$couponValidation['valid']) {
    header('Location: product.php?id=' . $product_id . '&error=Invalid coupon: ' . urlencode($couponValidation['message']));
    exit();
}

$coupon = $couponValidation['coupon'];

// Calculate discount
$discount_amount = $couponManager->calculateDiscount($coupon, $product['price']);
$final_amount = $product['price'] - $discount_amount;

// Verify the product is actually free with this coupon (100% or exact amount)
if ($final_amount > 0) {
    header('Location: product.php?id=' . $product_id . '&error=This coupon does not make the product free');
    exit();
}

// For guests, we'll create a temporary session to track the coupon usage
if (!isset($_SESSION['guest_coupon_usage'])) {
    $_SESSION['guest_coupon_usage'] = [];
}

$coupon_key = $product_id . '_' . $coupon_code;

// Check if guest has already used this coupon for this product
if (in_array($coupon_key, $_SESSION['guest_coupon_usage'])) {
    // Guest has already used this coupon, proceed to download
} else {
    // Record guest coupon usage in session
    $_SESSION['guest_coupon_usage'][] = $coupon_key;
    
    // Record the guest coupon usage in database if possible
    try {
        // Create a guest order record
        $stmt = $pdo->prepare("
            INSERT INTO guest_orders (
                product_id, amount, status, created_at, 
                coupon_code, discount_amount, original_amount, order_number
            ) 
            VALUES (?, ?, 'paid', NOW(), ?, ?, ?, ?)
        ");
        
        $order_number = 'GST' . date('Ymd') . str_pad($product_id, 5, '0', STR_PAD_LEFT);
        
        $stmt->execute([
            $product_id, 
            $final_amount,
            $coupon_code,
            $discount_amount,
            $product['price'],
            $order_number
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Apply the coupon usage (with null user_id for guests)
        $couponManager->applyCoupon($coupon['id'], null, $order_id, $product['price'], $discount_amount);
        
        // Track the download using DownloadTracker
        $downloadTracker = new DownloadTracker($pdo);
        $downloadTracker->trackDownload($order_id, null, 'guest', $product_id);
        
    } catch (Exception $e) {
        error_log("Error recording guest coupon usage: " . $e->getMessage());
        // Continue with download even if recording fails
    }
}

// FIXED: Direct download from Google Drive instead of showing pages
if (!empty($product['drive_link'])) {
    // Google Drive product - direct download
    $drive_link = $product['drive_link'];
    
    // Convert Google Drive link to direct download format
    if (strpos($drive_link, 'drive.google.com') !== false) {
        // Extract file ID from various Google Drive link formats
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9_-]+)/',  // /file/d/{file_id}/view
            '/\/d\/([a-zA-Z0-9_-]+)/',        // /d/{file_id}
            '/[?&]id=([a-zA-Z0-9_-]+)/',      // ?id={file_id} or &id={file_id}
        ];
        
        $file_id = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $drive_link, $matches)) {
                $file_id = $matches[1];
                break;
            }
        }
        
        if ($file_id) {
            $direct_download_link = "https://drive.google.com/uc?export=download&id=" . $file_id;
            
            // Redirect to direct download
            header('Location: ' . $direct_download_link);
            exit;
        }
    }
    
    // If we can't parse the link, redirect to the original
    header('Location: ' . $drive_link);
    exit;
    
} elseif (!empty($product['doc_file']) && file_exists("assets/docs/" . $product['doc_file'])) {
    // Documentation file - direct download
    $file_path = "assets/docs/" . $product['doc_file'];
    $file_name = basename($product['doc_file']);
    
    // Set proper headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear output buffer to ensure clean download
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file
    readfile($file_path);
    exit;
    
} else {
    // No download available - show error page
    header('Location: download_error.php?error=no_download_available&product=' . urlencode($product['title']));
    exit;
}
?>
