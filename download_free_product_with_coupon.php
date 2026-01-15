<?php
session_start();
include 'includes/db.php';
include 'includes/product_functions.php';
include 'includes/coupon_helper.php';
include 'includes/download_tracker.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

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

// Validate the coupon
$couponManager = new CouponManager($pdo);
$couponValidation = $couponManager->validateCoupon($coupon_code, $_SESSION['user_id'], $product_id, $product['price']);

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

// Check if user has already downloaded this product with this coupon
$stmt = $pdo->prepare("
    SELECT * FROM purchases 
    WHERE user_id = ? AND product_id = ? AND coupon_code = ?
");
$stmt->execute([$_SESSION['user_id'], $product_id, $coupon_code]);
$existing_purchase = $stmt->fetch();

if (!$existing_purchase) {
    // Record the coupon-based free purchase
    try {
        $stmt = $pdo->prepare("
            INSERT INTO purchases (
                user_id, product_id, amount, status, created_at, 
                coupon_code, discount_amount, original_amount, receipt_number
            ) 
            VALUES (?, ?, ?, 'paid', NOW(), ?, ?, ?, ?)
        ");
        
        $receipt_number = 'RCP' . date('Ymd') . str_pad($product_id, 5, '0', STR_PAD_LEFT);
        
        $stmt->execute([
            $_SESSION['user_id'], 
            $product_id, 
            $final_amount,
            $coupon_code,
            $discount_amount,
            $product['price'],
            $receipt_number
        ]);
        
        $purchase_id = $pdo->lastInsertId();
        
        // Apply the coupon usage
        $couponManager->applyCoupon($coupon['id'], $_SESSION['user_id'], $purchase_id, $product['price'], $discount_amount);
        
        // Create receipt
        include 'includes/receipt_helper.php';
        create_receipt($purchase_id, $_SESSION['user_id']);
        
        // Track the download using DownloadTracker
        $downloadTracker = new DownloadTracker($pdo);
        $downloadTracker->trackDownload($purchase_id, $_SESSION['user_id'], 'user');
        
        // Log the download
        include 'includes/logger.php';
        log_download("Coupon-based free product download", [
            'user_id' => $_SESSION['user_id'],
            'product_id' => $product_id,
            'product_title' => $product['title'],
            'coupon_code' => $coupon_code,
            'discount_amount' => $discount_amount
        ], $_SESSION['user_id']);
        
    } catch (Exception $e) {
        error_log("Error recording coupon-based free purchase: " . $e->getMessage());
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
