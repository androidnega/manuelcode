<?php
/**
 * Notification Helper Class
 * Handles product updates, user notifications, and delivery tracking
 */
class NotificationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Record a product update
     */
    public function recordProductUpdate($product_id, $update_type, $update_message, $previous_data = null, $new_data = null, $admin_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO product_updates (product_id, update_type, update_message, previous_data, new_data, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $product_id,
                $update_type,
                $update_message,
                $previous_data ? json_encode($previous_data) : null,
                $new_data ? json_encode($new_data) : null,
                $admin_id
            ]);
            
            $update_id = $this->pdo->lastInsertId();
            
            // Notify all users who purchased this product
            $this->notifyProductPurchasers($product_id, $update_type, $update_message);
            
            return $update_id;
        } catch (Exception $e) {
            error_log("Error recording product update: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify all users who purchased a specific product
     */
    public function notifyProductPurchasers($product_id, $notification_type, $message) {
        try {
            // Get all users who purchased this product
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.user_id, p.id as purchase_id, pr.title as product_title
                FROM purchases p
                JOIN products pr ON p.product_id = pr.id
                WHERE p.product_id = ? AND p.status = 'paid'
            ");
            $stmt->execute([$product_id]);
            $purchasers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($purchasers as $purchaser) {
                if ($purchaser['user_id']) {
                    $this->createUserNotification(
                        $purchaser['user_id'],
                        $product_id,
                        $notification_type,
                        $this->getNotificationTitle($notification_type, $purchaser['product_title']),
                        $message
                    );
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error notifying product purchasers: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a user notification
     */
    public function createUserNotification($user_id, $product_id, $notification_type, $title, $message) {
        try {
            // Check user notification preferences
            if (!$this->shouldSendNotification($user_id, $notification_type)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notifications (user_id, product_id, notification_type, title, message)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$user_id, $product_id, $notification_type, $title, $message]);
            $notification_id = $this->pdo->lastInsertId();
            
            // Log delivery attempt
            $this->logDeliveryAttempt($notification_id, 'dashboard', 'pending');
            
            return $notification_id;
        } catch (Exception $e) {
            error_log("Error creating user notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if notification should be sent based on user preferences
     */
    private function shouldSendNotification($user_id, $notification_type) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_notification_preferences WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$preferences) {
                // Create default preferences
                $this->createDefaultPreferences($user_id);
                return true;
            }
            
            switch ($notification_type) {
                case 'download_ready':
                    return $preferences['download_ready'] && $preferences['dashboard_notifications'];
                case 'product_updated':
                case 'product_improved':
                    return $preferences['product_updates'] && $preferences['dashboard_notifications'];
                default:
                    return $preferences['dashboard_notifications'];
            }
        } catch (Exception $e) {
            error_log("Error checking notification preferences: " . $e->getMessage());
            return true; // Default to sending if there's an error
        }
    }
    
    /**
     * Create default notification preferences for a user
     */
    public function createDefaultPreferences($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notification_preferences (user_id)
                VALUES (?)
            ");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Error creating default preferences: " . $e->getMessage());
        }
    }
    
    /**
     * Get notification title based on type
     */
    private function getNotificationTitle($notification_type, $product_title) {
        switch ($notification_type) {
            case 'download_ready':
                return "Download Ready: {$product_title}";
            case 'product_updated':
                return "Product Updated: {$product_title}";
            case 'product_improved':
                return "Product Improved: {$product_title}";
            default:
                return "Product Update: {$product_title}";
        }
    }
    
    /**
     * Log delivery attempt
     */
    public function logDeliveryAttempt($notification_id, $delivery_method, $status, $error_message = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_delivery_logs (notification_id, delivery_method, delivery_status, error_message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$notification_id, $delivery_method, $status, $error_message]);
        } catch (Exception $e) {
            error_log("Error logging delivery attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_notifications 
                SET is_read = TRUE, read_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 10, $offset = 0, $unread_only = false) {
        try {
            $where_clause = "WHERE un.user_id = ?";
            if ($unread_only) {
                $where_clause .= " AND un.is_read = FALSE";
            }
            
            $stmt = $this->pdo->prepare("
                SELECT un.*, pr.title as product_title, pr.preview_image
                FROM user_notifications un
                JOIN products pr ON un.product_id = pr.id
                {$where_clause}
                ORDER BY un.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$user_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM user_notifications
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if product has download link
     */
    public function hasDownloadLink($product_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT drive_link FROM products WHERE id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return !empty($product['drive_link']);
        } catch (Exception $e) {
            error_log("Error checking download link: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get product update history
     */
    public function getProductUpdateHistory($product_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pu.*, a.name as admin_name
                FROM product_updates pu
                LEFT JOIN admins a ON pu.created_by = a.id
                WHERE pu.product_id = ?
                ORDER BY pu.created_at DESC
            ");
            $stmt->execute([$product_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting product update history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOldNotifications($days_old = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND is_read = TRUE
            ");
            return $stmt->execute([$days_old]);
        } catch (Exception $e) {
            error_log("Error cleaning up old notifications: " . $e->getMessage());
            return false;
        }
    }
}
