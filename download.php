<?php
session_start();
include 'includes/db.php';
include 'includes/product_functions.php';
include 'includes/notification_helper.php';
include 'includes/download_tracker.php';

$notificationHelper = new NotificationHelper($pdo);
$downloadTracker = new DownloadTracker($pdo);

// Check if user is logged in or has valid guest download
if (!isset($_SESSION['user_id']) && (!isset($_GET['type']) || $_GET['type'] !== 'guest') && !isset($_GET['t'])) {
    header('Location: auth/login.php');
    exit;
}

$download_type = $_GET['type'] ?? 'user';
$product_id = (int)($_GET['product_id'] ?? 0);
$token = $_GET['t'] ?? '';

// Handle token-based downloads
if ($token) {
    try {
        $decoded_token = base64_decode($token);
        $token_parts = explode('|', $decoded_token);
        
        if (count($token_parts) === 4) {
            $purchase_id = $token_parts[0];
            $product_id = $token_parts[1];
            $user_id = $token_parts[2];
            $token_time = $token_parts[3];
            
            // Check if token is not expired (24 hours)
            if (time() - $token_time > 86400) {
                header('Location: store.php?error=download_link_expired');
                exit;
            }
            
            // Verify user purchase
            $stmt = $pdo->prepare("
                SELECT p.*, pr.title, pr.doc_file, pr.drive_link
                FROM purchases p
                JOIN products pr ON p.product_id = pr.id
                WHERE p.id = ? AND p.user_id = ? AND p.product_id = ? AND p.status = 'paid'
            ");
            $stmt->execute([$purchase_id, $user_id, $product_id]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($purchase) {
                $product_title = $purchase['title'];
                $doc_file = $purchase['doc_file'];
                $drive_link = $purchase['drive_link'];
                $download_type = 'user';
                
                // Debug: Log the variables being passed
                error_log("Download.php - Token download: product_title={$product_title}, drive_link={$drive_link}");
            } else {
                header('Location: store.php?error=invalid_purchase');
                exit;
            }
        } else {
            header('Location: store.php?error=invalid_token');
            exit;
        }
    } catch (Exception $e) {
        header('Location: store.php?error=invalid_token');
        exit;
    }
} else {
    // Handle regular downloads
    if (!$product_id) {
        header('Location: store.php?error=invalid_product');
        exit;
    }

    try {
        if ($download_type === 'guest') {
            // Handle guest download
            $email = $_GET['email'] ?? '';
            $reference = $_GET['ref'] ?? '';
            
            if (empty($email) || empty($reference)) {
                header('Location: store.php?error=invalid_guest_download');
                exit;
            }
            
            // Verify guest purchase
            $stmt = $pdo->prepare("
                SELECT go.*, p.title, p.doc_file, p.drive_link
                FROM guest_orders go
                JOIN products p ON go.product_id = p.id
                WHERE go.email = ? AND go.reference = ? AND go.product_id = ? AND go.status = 'paid'
            ");
            $stmt->execute([$email, $reference, $product_id]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$purchase) {
                header('Location: store.php?error=invalid_guest_purchase');
                exit;
            }
            
            $product_title = $purchase['title'];
            $doc_file = $purchase['doc_file'];
            $drive_link = $purchase['drive_link'];
            
        } else {
            // Handle user download
            $user_id = $_SESSION['user_id'];
            
            // Verify user purchase
            $stmt = $pdo->prepare("
                SELECT p.*, pr.title, pr.doc_file, pr.drive_link
                FROM purchases p
                JOIN products pr ON p.product_id = pr.id
                WHERE p.user_id = ? AND p.product_id = ? AND p.status = 'paid'
            ");
            $stmt->execute([$user_id, $product_id]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$purchase) {
                header('Location: store.php?error=invalid_purchase');
                exit;
            }
            
            $product_title = $purchase['title'];
            $doc_file = $purchase['doc_file'];
            $drive_link = $purchase['drive_link'];
        }
    } catch (Exception $e) {
        error_log("Download verification error: " . $e->getMessage());
        header('Location: store.php?error=download_failed');
        exit;
    }
}

// Track download using the new DownloadTracker
$download_id = $purchase['id'] ?? $purchase['purchase_id'];
$user_id_for_log = $download_type === 'guest' ? null : ($_SESSION['user_id'] ?? $user_id);

// Track the download
$downloadTracker->trackDownload($download_id, $user_id_for_log, $download_type, $product_id);

// FIXED: Direct download from Google Drive instead of showing pages
if ($drive_link) {
    // Google Drive product - direct download
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
    
} elseif ($doc_file && file_exists("assets/docs/$doc_file")) {
    // Documentation file - direct download
    $file_path = "assets/docs/$doc_file";
    $file_name = basename($doc_file);
    
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
    // No download available - show proper error page
    $error_type = 'download_not_ready';
    $product_title = $purchase['title'];
    
    // Include error page
    include 'download_error.php';
    exit;
}
?>
