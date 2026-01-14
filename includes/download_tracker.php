<?php
/**
 * Download Tracker
 * Comprehensive system for tracking all downloads and updating statistics
 */

class DownloadTracker {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Track a download for any type of purchase
     */
    public function trackDownload($purchase_id, $user_id = null, $download_type = 'user', $product_id = null) {
        try {
            // Determine the correct purchase_id for different types
            $actual_purchase_id = $this->getActualPurchaseId($purchase_id, $user_id, $download_type, $product_id);
            
            if (!$actual_purchase_id) {
                error_log("DownloadTracker: Could not determine purchase_id for tracking");
                return false;
            }
            
            // Insert or update download tracking
            $stmt = $this->pdo->prepare("
                INSERT INTO purchase_logs (user_id, purchase_id, download_count, last_downloaded, purchase_date, ip_address, user_agent) 
                VALUES (?, ?, 1, NOW(), NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE 
                download_count = download_count + 1,
                last_downloaded = NOW(),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
            ");
            
            $stmt->execute([
                $user_id, 
                $actual_purchase_id, 
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Log successful tracking
            error_log("DownloadTracker: Successfully tracked download for purchase_id={$actual_purchase_id}, user_id={$user_id}, type={$download_type}");
            
            return true;
            
        } catch (Exception $e) {
            error_log("DownloadTracker Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the actual purchase_id for different types of purchases
     */
    private function getActualPurchaseId($purchase_id, $user_id, $download_type, $product_id) {
        if ($download_type === 'guest' && $product_id) {
            // For guest orders, find the guest_order_id
            $stmt = $this->pdo->prepare("
                SELECT id FROM guest_orders 
                WHERE product_id = ? AND status = 'paid'
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$product_id]);
            $guest_order = $stmt->fetch(PDO::FETCH_ASSOC);
            return $guest_order['id'] ?? null;
        }
        
        return $purchase_id;
    }
    
    /**
     * Get download statistics for a user
     */
    public function getUserDownloadStats($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(SUM(pl.download_count), 0) as total_downloads,
                    COUNT(DISTINCT pl.purchase_id) as unique_products_downloaded,
                    MAX(pl.last_downloaded) as last_download_date
                FROM purchases p 
                LEFT JOIN purchase_logs pl ON p.id = pl.purchase_id 
                WHERE p.user_id = ? AND p.status = 'paid'
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("DownloadTracker: Error getting user download stats: " . $e->getMessage());
            return [
                'total_downloads' => 0,
                'unique_products_downloaded' => 0,
                'last_download_date' => null
            ];
        }
    }
    
    /**
     * Get download statistics for all users (admin)
     */
    public function getAllUsersDownloadStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(SUM(pl.download_count), 0) as total_downloads,
                    COUNT(DISTINCT pl.purchase_id) as total_download_events,
                    COUNT(DISTINCT pl.user_id) as unique_users_downloaded
                FROM purchase_logs pl
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("DownloadTracker: Error getting all users download stats: " . $e->getMessage());
            return [
                'total_downloads' => 0,
                'total_download_events' => 0,
                'unique_users_downloaded' => 0
            ];
        }
    }
    
    /**
     * Get download count for a specific purchase
     */
    public function getPurchaseDownloadCount($purchase_id) {
        try {
            $stmt = $this->pdo->prepare("
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
            error_log("DownloadTracker: Error getting purchase download count: " . $e->getMessage());
            return ['count' => 0, 'last_downloaded' => null];
        }
    }
    
    /**
     * Update download statistics in real-time
     */
    public function updateDownloadStatistics() {
        try {
            // This method can be called to refresh download statistics
            // For now, it just returns the current stats
            return [
                'all_users' => $this->getAllUsersDownloadStats(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("DownloadTracker: Error updating download statistics: " . $e->getMessage());
            return false;
        }
    }
}
?>
