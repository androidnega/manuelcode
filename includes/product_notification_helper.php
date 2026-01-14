<?php
/**
 * Product Notification Helper
 * Sends notifications to users about changes in their purchased products
 */

class ProductNotificationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send notification when product is updated
     */
    public function notifyProductUpdate($product_id, $update_type, $update_details = []) {
        try {
            // Get all users who have purchased this product
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.user_id, u.name, u.email
                FROM purchases p 
                JOIN users u ON p.user_id = u.id
                WHERE p.product_id = ? AND p.status = 'paid'
            ");
            $stmt->execute([$product_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $this->createProductUpdateNotification(
                    $user['user_id'], 
                    $product_id, 
                    $update_type, 
                    $update_details
                );
            }
            
            return count($users);
            
        } catch (Exception $e) {
            error_log("Error notifying product update: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send notification when product file is updated
     */
    public function notifyFileUpdate($product_id, $file_type, $file_name) {
        $update_details = [
            'type' => 'file_update',
            'file_type' => $file_type,
            'file_name' => $file_name,
            'message' => "A new {$file_type} file has been added to your purchased product."
        ];
        
        return $this->notifyProductUpdate($product_id, 'file_update', $update_details);
    }
    
    /**
     * Send notification when product documentation is updated
     */
    public function notifyDocumentationUpdate($product_id, $doc_title, $doc_description) {
        $update_details = [
            'type' => 'documentation_update',
            'doc_title' => $doc_title,
            'doc_description' => $doc_description,
            'message' => "New documentation has been added to your purchased product."
        ];
        
        return $this->notifyProductUpdate($product_id, 'documentation_update', $update_details);
    }
    
    /**
     * Send notification when product version is updated
     */
    public function notifyVersionUpdate($product_id, $old_version, $new_version, $changelog = '') {
        $update_details = [
            'type' => 'version_update',
            'old_version' => $old_version,
            'new_version' => $new_version,
            'changelog' => $changelog,
            'message' => "Your purchased product has been updated to version {$new_version}."
        ];
        
        return $this->notifyProductUpdate($product_id, 'version_update', $update_details);
    }
    
    /**
     * Send notification when product link is updated
     */
    public function notifyLinkUpdate($product_id, $link_type, $link_url) {
        $update_details = [
            'type' => 'link_update',
            'link_type' => $link_type,
            'link_url' => $link_url,
            'message' => "A new {$link_type} link has been added to your purchased product."
        ];
        
        return $this->notifyProductUpdate($product_id, 'link_update', $update_details);
    }
    
    /**
     * Send notification when product is discontinued or removed
     */
    public function notifyProductDiscontinuation($product_id, $reason = '') {
        $update_details = [
            'type' => 'product_discontinuation',
            'reason' => $reason,
            'message' => "Your purchased product has been discontinued."
        ];
        
        return $this->notifyProductUpdate($product_id, 'product_discontinuation', $update_details);
    }
    
    /**
     * Create the actual notification in the database
     */
    private function createProductUpdateNotification($user_id, $product_id, $update_type, $update_details) {
        try {
            // Get product title
            $stmt = $this->pdo->prepare("SELECT title FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return false;
            }
            
            $product_title = $product['title'];
            $notification_title = "Product Update: {$product_title}";
            $notification_message = $update_details['message'] ?? "Your purchased product has been updated.";
            
            // Create notification record
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (
                    user_id, 
                    title, 
                    message, 
                    type, 
                    related_id, 
                    related_type,
                    data,
                    is_read,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $notification_title,
                $notification_message,
                'product_update',
                $product_id,
                'product',
                json_encode($update_details)
            ]);
            
            // Log the notification
            error_log("Product update notification sent to user {$user_id} for product {$product_id} ({$update_type})");
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error creating product update notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send bulk notifications for multiple product updates
     */
    public function notifyBulkProductUpdates($updates) {
        $total_notifications = 0;
        
        foreach ($updates as $update) {
            $count = $this->notifyProductUpdate(
                $update['product_id'],
                $update['type'],
                $update['details'] ?? []
            );
            $total_notifications += $count;
        }
        
        return $total_notifications;
    }
    
    /**
     * Get unread product update notifications for a user
     */
    public function getUnreadProductNotifications($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND type = 'product_update' AND is_read = 0
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting unread product notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark product notification as read
     */
    public function markNotificationAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all product notifications as read for a user
     */
    public function markAllNotificationsAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, updated_at = NOW()
                WHERE user_id = ? AND type = 'product_update'
            ");
            $stmt->execute([$user_id]);
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return 0;
        }
    }
}
?>
