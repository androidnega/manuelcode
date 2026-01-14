<?php
/**
 * User Management Functions
 * Handles user ID generation, purchase management, and user-related operations
 */

/**
 * Generate unique user ID with 5 digits and alphanumeric characters
 */
function generateUniqueUserId() {
    global $pdo;
    
    do {
        // Generate a 5-character alphanumeric ID
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $userId = '';
        
        for ($i = 0; $i < 5; $i++) {
            $userId .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if this ID already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    return $userId;
}

/**
 * Generate unique payment reference for transactions
 */
function generatePaymentReference($userId) {
    global $pdo;
    
    do {
        // Generate reference with user ID prefix
        $timestamp = time();
        $random = rand(1000, 9999);
        $reference = $userId . '_' . $timestamp . '_' . $random;
        
        // Check if this reference already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE payment_ref = ?");
        $stmt->execute([$reference]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    return $reference;
}

/**
 * Get user's purchase history with download tracking
 */
function getUserPurchaseHistory($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pr.title as product_title, pr.price, pr.preview_image, pr.version,
                   pl.download_count, pl.last_downloaded, pl.purchase_date,
                   pu.title as latest_update_title, pu.created_at as latest_update_date
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            LEFT JOIN purchase_logs pl ON p.id = pl.purchase_id
            LEFT JOIN (
                SELECT product_id, title, created_at,
                       ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY created_at DESC) as rn
                FROM product_updates
            ) pu ON pr.id = pu.product_id AND pu.rn = 1
            WHERE p.user_id = ? AND p.status = 'paid'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user purchase history: " . $e->getMessage());
        return [];
    }
}

/**
 * Track download for a purchase
 */
function trackDownload($purchase_id, $user_id) {
    global $pdo;
    
    try {
        // Update or insert download tracking
        $stmt = $pdo->prepare("
            INSERT INTO purchase_logs (user_id, purchase_id, download_count, last_downloaded, purchase_date) 
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            download_count = download_count + 1,
            last_downloaded = NOW()
        ");
        $stmt->execute([$user_id, $purchase_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error tracking download: " . $e->getMessage());
        return false;
    }
}

/**
 * Get download count for a purchase
 */
function getDownloadCount($purchase_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT download_count, last_downloaded 
            FROM purchase_logs 
            WHERE purchase_id = ?
        ");
        $stmt->execute([$purchase_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'count' => $result['download_count'] ?? 0,
            'last_downloaded' => $result['last_downloaded'] ?? null
        ];
    } catch (Exception $e) {
        error_log("Error getting download count: " . $e->getMessage());
        return ['count' => 0, 'last_downloaded' => null];
    }
}

/**
 * Check if user can request refund (within 7 days of purchase)
 */
function canRequestRefund($purchase_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT created_at, DATEDIFF(NOW(), created_at) as days_since_purchase
            FROM purchases 
            WHERE id = ? AND status = 'paid'
        ");
        $stmt->execute([$purchase_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
        // Allow refund within 7 days
        return $result['days_since_purchase'] <= 7;
    } catch (Exception $e) {
        error_log("Error checking refund eligibility: " . $e->getMessage());
        return false;
    }
}

/**
 * Create refund request
 */
function createRefundRequest($purchase_id, $user_id, $reason) {
    global $pdo;
    
    try {
        // Check if refund request already exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM refund_requests 
            WHERE purchase_id = ? AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$purchase_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'Refund request already exists for this purchase'];
        }
        
        // Create refund request
        $stmt = $pdo->prepare("
            INSERT INTO refund_requests (purchase_id, user_id, reason, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$purchase_id, $user_id, $reason]);
        
        return ['success' => true, 'message' => 'Refund request submitted successfully'];
    } catch (Exception $e) {
        error_log("Error creating refund request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit refund request'];
    }
}

/**
 * Create support ticket for purchase issue
 */
function createSupportTicket($purchase_id, $user_id, $subject, $message, $priority = 'medium') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (purchase_id, user_id, subject, message, priority, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'open', NOW())
        ");
        $stmt->execute([$purchase_id, $user_id, $subject, $message, $priority]);
        
        return ['success' => true, 'ticket_id' => $pdo->lastInsertId()];
    } catch (Exception $e) {
        error_log("Error creating support ticket: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create support ticket'];
    }
}

/**
 * Delete purchase record (soft delete)
 */
function deletePurchase($purchase_id, $user_id) {
    global $pdo;
    
    try {
        // Verify user owns this purchase
        $stmt = $pdo->prepare("
            SELECT id FROM purchases 
            WHERE id = ? AND user_id = ? AND status = 'paid'
        ");
        $stmt->execute([$purchase_id, $user_id]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Purchase not found or access denied'];
        }
        
        // Soft delete by updating status
        $stmt = $pdo->prepare("
            UPDATE purchases 
            SET status = 'deleted', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$purchase_id]);
        
        return ['success' => true, 'message' => 'Purchase deleted successfully'];
    } catch (Exception $e) {
        error_log("Error deleting purchase: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete purchase'];
    }
}

/**
 * Get user's refund requests
 */
function getUserRefundRequests($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT rr.*, p.payment_ref, pr.title as product_title, pr.price
            FROM refund_requests rr
            JOIN purchases p ON rr.purchase_id = p.id
            JOIN products pr ON p.product_id = pr.id
            WHERE rr.user_id = ?
            ORDER BY rr.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting refund requests: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's support tickets
 */
function getUserSupportTickets($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT st.*, p.payment_ref, pr.title as product_title
            FROM support_tickets st
            JOIN purchases p ON st.purchase_id = p.id
            JOIN products pr ON p.product_id = pr.id
            WHERE st.user_id = ?
            ORDER BY st.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting support tickets: " . $e->getMessage());
        return [];
    }
}
?>
