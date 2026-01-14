<?php
/**
 * Store Functions
 * Global functions for store purchase detection and download management
 */

/**
 * Check if product is purchased by user with proper status verification
 */
function isProductPurchasedByUser($user_id, $product_id, $user_email = null) {
    global $pdo;
    
    // Check user purchases with 'paid' status only
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE user_id = ? AND product_id = ? AND status = 'paid'");
    $stmt->execute([$user_id, $product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        return true;
    }
    
    // Check guest purchases by email with 'paid' status only
    if ($user_email) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM guest_orders WHERE email = ? AND product_id = ? AND status = 'paid'");
        $stmt->execute([$user_email, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get download link for purchased product
 */
function getProductDownloadLinkForUser($user_id, $product_id, $user_email = null) {
    global $pdo;
    
    // Check user purchases first
    $stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND product_id = ? AND status = 'paid'");
    $stmt->execute([$user_id, $product_id]);
    $purchase = $stmt->fetch();
    
    if ($purchase) {
        // Use the existing function from product_functions.php
        $download_link = getProductDownloadLink($user_id, $product_id);
        if (!$download_link) {
            $download_link = "download.php?product_id=" . $product_id;
        }
        return $download_link;
    }
    
    // Check guest purchases by email
    if ($user_email) {
        $stmt = $pdo->prepare("SELECT id FROM guest_orders WHERE email = ? AND product_id = ? AND status = 'paid'");
        $stmt->execute([$user_email, $product_id]);
        $guest_purchase = $stmt->fetch();
        
        if ($guest_purchase) {
            // Use the existing function from product_functions.php
            return getGuestDownloadLink($user_email, $product_id);
        }
    }
    
    return null;
}

/**
 * Get purchase type (user vs guest)
 */
function getPurchaseType($user_id, $product_id, $user_email = null) {
    global $pdo;
    
    // Check user purchases first
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE user_id = ? AND product_id = ? AND status = 'paid'");
    $stmt->execute([$user_id, $product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        return 'user';
    }
    
    // Check guest purchases by email
    if ($user_email) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM guest_orders WHERE email = ? AND product_id = ? AND status = 'paid'");
        $stmt->execute([$user_email, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return 'guest';
        }
    }
    
    return null;
}
?>
