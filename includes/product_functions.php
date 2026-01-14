<?php
/**
 * Product Management Functions
 * Handles product updates, purchase verification, and notifications
 */

/**
 * Check if user has purchased a specific product
 */
function hasUserPurchasedProduct($user_id, $product_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM purchases 
            WHERE user_id = ? AND product_id = ? AND status = 'paid'
        ");
        $stmt->execute([$user_id, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking user purchase: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a guest has purchased a product by email
 */
function hasGuestPurchasedProduct($email, $product_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM guest_orders 
            WHERE email = ? AND product_id = ? AND status = 'paid'
        ");
        $stmt->execute([$email, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking guest purchase: " . $e->getMessage());
        return false;
    }
}

/**
 * Get guest purchase details by email and product
 */
function getGuestPurchaseDetails($email, $product_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT go.*, p.title as product_title, p.price, p.preview_image
            FROM guest_orders go
            JOIN products p ON go.product_id = p.id
            WHERE go.email = ? AND go.product_id = ? AND go.status = 'paid'
            ORDER BY go.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$email, $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting guest purchase details: " . $e->getMessage());
        return null;
    }
}

/**
 * Get download link for guest purchase
 */
function getGuestDownloadLink($email, $product_id) {
    $purchase = getGuestPurchaseDetails($email, $product_id);
    if ($purchase) {
        return "download.php?type=guest&email=" . urlencode($email) . "&product_id=" . $product_id . "&ref=" . $purchase['reference'];
    }
    return null;
}

/**
 * Get user's purchased products
 */
function getUserPurchasedProducts($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pr.title, pr.short_desc, pr.price, pr.preview_image, pr.version, pr.last_updated
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            WHERE p.user_id = ? AND p.status = 'paid'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user purchases: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all purchased products for a user (including guest purchases by email)
 * FIXED: Prevents duplicates and ensures only paid purchases are shown
 */
function getAllPurchasedProducts($user_id, $user_email = null) {
    global $pdo;
    
    $all_purchases = [];
    $seen_products = []; // Track seen products to prevent duplicates
    
    // Get user purchases - only PAID purchases
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id as purchase_id,
                p.product_id,
                p.reference as payment_ref,
                p.amount as price,
                p.created_at,
                p.status,
                COALESCE(p.discount_amount, 0) as discount_amount,
                pr.title as product_title,
                pr.short_desc,
                pr.preview_image,
                pr.version,
                pr.last_updated,
                'user' as purchase_type
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            WHERE p.user_id = ? AND p.status = 'paid'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $user_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($user_purchases as $purchase) {
            $all_purchases[] = $purchase;
            $seen_products[] = $purchase['product_id'];
        }
    } catch (Exception $e) {
        error_log("Error getting user purchases: " . $e->getMessage());
    }
    
    // If user has email, also check for guest purchases (but avoid duplicates)
    if ($user_email) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    go.id as purchase_id,
                    go.product_id,
                    go.reference as payment_ref,
                    go.total_amount as price,
                    go.created_at,
                    go.status,
                    0 as discount_amount,
                    pr.title as product_title,
                    pr.short_desc,
                    pr.preview_image,
                    pr.version,
                    pr.last_updated,
                    'guest' as purchase_type
                FROM guest_orders go
                JOIN products pr ON go.product_id = pr.id
                WHERE go.email = ? AND go.status = 'paid'
                ORDER BY go.created_at DESC
            ");
            $stmt->execute([$user_email]);
            $guest_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add guest purchases only if not already seen as user purchase
            foreach ($guest_purchases as $guest_purchase) {
                if (!in_array($guest_purchase['product_id'], $seen_products)) {
                    $all_purchases[] = $guest_purchase;
                    $seen_products[] = $guest_purchase['product_id'];
                }
            }
        } catch (Exception $e) {
            error_log("Error getting guest purchases: " . $e->getMessage());
        }
    }
    
    // Sort by creation date (newest first)
    usort($all_purchases, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $all_purchases;
}

/**
 * Check if product is purchased (user or guest)
 */
function isProductPurchased($user_id, $product_id, $user_email = null) {
    // Check user purchases
    if (hasUserPurchasedProduct($user_id, $product_id)) {
        return true;
    }
    
    // Check guest purchases if email is provided
    if ($user_email && hasGuestPurchasedProduct($user_email, $product_id)) {
        return true;
    }
    
    return false;
}

/**
 * NEW FUNCTION: Check if user can purchase a product (prevents duplicates)
 */
function canUserPurchaseProduct($user_id, $product_id, $user_email = null) {
    // If user has already purchased, they cannot purchase again
    if (isProductPurchased($user_id, $product_id, $user_email)) {
        return false;
    }
    
    // Check for pending purchases (prevent multiple pending orders)
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM purchases 
            WHERE user_id = ? AND product_id = ? AND status IN ('pending', 'processing')
        ");
        $stmt->execute([$user_id, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return false;
        }
        
        // Check guest orders if email provided
        if ($user_email) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM guest_orders 
                WHERE email = ? AND product_id = ? AND status IN ('pending', 'processing')
            ");
            $stmt->execute([$user_email, $product_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return false;
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error checking purchase eligibility: " . $e->getMessage());
        return false;
    }
}

/**
 * Get product updates for a specific product
 */
function getProductUpdates($product_id, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM product_updates 
            WHERE product_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$product_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting product updates: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread updates for a user
 */
function getUserUnreadUpdates($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT pu.*, p.title as product_title, pn.status as notification_status
            FROM product_updates pu
            JOIN products p ON pu.product_id = p.id
            JOIN purchases pur ON p.id = pur.product_id
            LEFT JOIN product_notifications pn ON pu.id = pn.update_id AND pn.user_id = ?
            WHERE pur.user_id = ? AND pur.status = 'paid'
            AND (pn.status IS NULL OR pn.status = 'pending')
            ORDER BY pu.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user unread updates: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a product update
 */
function createProductUpdate($product_id, $update_type, $title, $description, $version = null, $file_path = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO product_updates (product_id, update_type, title, description, version, file_path, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$product_id, $update_type, $title, $description, $version, $file_path]);
        
        $update_id = $pdo->lastInsertId();
        
        // Update product version and last_updated
        if ($version) {
            $stmt = $pdo->prepare("UPDATE products SET version = ?, last_updated = NOW() WHERE id = ?");
            $stmt->execute([$version, $product_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET last_updated = NOW() WHERE id = ?");
            $stmt->execute([$product_id]);
        }
        
        // Notify all users who purchased this product
        notifyProductUpdate($update_id, $product_id);
        
        return $update_id;
    } catch (Exception $e) {
        error_log("Error creating product update: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify users about product updates
 */
function notifyProductUpdate($update_id, $product_id) {
    global $pdo;
    
    try {
        // Get all users who purchased this product
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.name, u.email, u.phone
            FROM users u
            JOIN purchases p ON u.id = p.user_id
            WHERE p.product_id = ? AND p.status = 'paid'
        ");
        $stmt->execute([$product_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            // Create notification record
            $stmt = $pdo->prepare("
                INSERT INTO product_notifications (user_id, product_id, update_id, notification_type, status, created_at) 
                VALUES (?, ?, ?, 'sms', 'pending', NOW())
            ");
            $stmt->execute([$user['id'], $product_id, $update_id]);
            
            // Send SMS notification if phone number exists
            if (!empty($user['phone'])) {
                sendProductUpdateSMS($user['phone'], $user['name'], $product_id, $update_id);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error notifying product update: " . $e->getMessage());
        return false;
    }
}

/**
 * Send SMS notification for product updates
 */
function sendProductUpdateSMS($phone, $user_name, $product_id, $update_id) {
    global $pdo;
    
    try {
        // Get product and update details
        $stmt = $pdo->prepare("
            SELECT p.title as product_title, pu.title as update_title, pu.update_type
            FROM products p
            JOIN product_updates pu ON p.id = pu.product_id
            WHERE p.id = ? AND pu.id = ?
        ");
        $stmt->execute([$product_id, $update_id]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($details) {
            $message = "Hi {$user_name}, your purchased product '{$details['product_title']}' has been updated: {$details['update_title']}. Visit your dashboard to download the latest version.";
            
            // Send SMS using existing SMS function
            $sms_result = sendSMS($phone, $message);
            
            // Update notification status
            $status = $sms_result['success'] ? 'sent' : 'failed';
            $stmt = $pdo->prepare("
                UPDATE product_notifications 
                SET status = ?, sent_at = NOW() 
                WHERE user_id = (SELECT id FROM users WHERE phone = ?) AND update_id = ?
            ");
            $stmt->execute([$status, $phone, $update_id]);
            
            return $sms_result;
        }
        
        return ['success' => false, 'message' => 'Product or update not found'];
    } catch (Exception $e) {
        error_log("Error sending product update SMS: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Log purchase transaction
 */
function logPurchaseTransaction($user_id, $product_id, $purchase_id, $amount, $payment_method, $transaction_reference) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO purchase_logs (user_id, product_id, purchase_id, amount, payment_method, transaction_reference, purchase_date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $product_id, $purchase_id, $amount, $payment_method, $transaction_reference]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error logging purchase transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Send purchase confirmation SMS
 */
function sendPurchaseConfirmationSMS($phone, $user_name, $product_title, $amount) {
    $message = "Hi {$user_name}, thank you for your purchase! You've successfully bought '{$product_title}' for GHS " . number_format($amount, 2) . ". You can now download it from your dashboard.";
    
    return sendSMS($phone, $message);
}

/**
 * Generic SMS sending function
 */
function sendSMS($phone, $message) {
    // Use existing SMS configuration
    include_once __DIR__ . '/../config/sms_config.php';
    
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
 * Get download link for purchased product
 */
function getProductDownloadLink($user_id, $product_id) {
    global $pdo;
    
    try {
        // Verify user has purchased the product
        if (!hasUserPurchasedProduct($user_id, $product_id)) {
            return false;
        }
        
        // Get purchase details
        $stmt = $pdo->prepare("
            SELECT p.*, pr.drive_link, pr.doc_file 
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            WHERE p.user_id = ? AND p.product_id = ? AND p.status = 'paid'
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id, $product_id]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($purchase) {
            // Generate secure download token
            $token = base64_encode($purchase['id'] . '|' . $product_id . '|' . $user_id . '|' . time());
            return 'download.php?t=' . urlencode($token);
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error generating download link: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($user_id, $update_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE product_notifications 
            SET status = 'sent' 
            WHERE user_id = ? AND update_id = ?
        ");
        $stmt->execute([$user_id, $update_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}
?>

