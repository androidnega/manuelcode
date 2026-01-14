<?php
session_start();
include 'includes/db.php';
include 'includes/product_functions.php';
include 'includes/download_tracker.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$product_id = intval($_GET['id']);

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php?error=Product not found');
    exit();
}

// Check if product is actually free
if ($product['price'] > 0) {
    header('Location: product.php?id=' . $product_id . '&error=This product is not free');
    exit();
}

// Check if user has already downloaded this free product
$stmt = $pdo->prepare("SELECT * FROM purchases WHERE user_id = ? AND product_id = ?");
$stmt->execute([$_SESSION['user_id'], $product_id]);
$existing_purchase = $stmt->fetch();

if (!$existing_purchase) {
    // Record the free purchase
    try {
        $stmt = $pdo->prepare("
            INSERT INTO purchases (
                user_id, product_id, amount, status, created_at, 
                original_amount, receipt_number
            ) 
            VALUES (?, ?, 0.00, 'paid', NOW(), ?, CONCAT('REC', LPAD(LAST_INSERT_ID(), 8, '0'), DATE_FORMAT(NOW(), '%Y%m%d')))
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id, $product['price']]);
        
        // Get the purchase ID for receipt number
        $purchase_id = $pdo->lastInsertId();
        
        // Update receipt number with correct ID
        $stmt = $pdo->prepare("
            UPDATE purchases 
            SET receipt_number = CONCAT('REC', LPAD(?, 8, '0'), DATE_FORMAT(created_at, '%Y%m%d'))
            WHERE id = ?
        ");
        $stmt->execute([$purchase_id, $purchase_id]);
        
        // Track the download using DownloadTracker
        $downloadTracker = new DownloadTracker($pdo);
        $downloadTracker->trackDownload($purchase_id, $_SESSION['user_id'], 'user');
        
        // Log the download
        include 'includes/logger.php';
        log_download("Free product download", [
            'user_id' => $_SESSION['user_id'],
            'product_id' => $product_id,
            'product_title' => $product['title']
        ], $_SESSION['user_id']);
        
    } catch (Exception $e) {
        error_log("Error recording free purchase: " . $e->getMessage());
        // Continue with download even if recording fails
    }
}

// FIXED: Direct download from Google Drive instead of showing pages
if (!empty($product['drive_link'])) {
    // Google Drive product - direct download
    $drive_link = $product['drive_link'];
    
    // Convert Google Drive link to direct download format
    if (strpos($drive_link, 'drive.google.com/file/d/') !== false) {
        // Extract file ID from Google Drive link
        preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $drive_link, $matches);
        if (isset($matches[1])) {
            $file_id = $matches[1];
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
