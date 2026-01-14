<?php
/**
 * Dashboard Helper Functions
 * Provides real-time statistics and data for user and admin dashboards
 */

/**
 * Get real-time user dashboard statistics
 */
function getUserDashboardStats($pdo, $user_id) {
    try {
        // Get total purchases (only paid)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM purchases WHERE user_id = ? AND status = 'paid'");
        $stmt->execute([$user_id]);
        $total_purchases = $stmt->fetchColumn();
        
        // Get total spent (only paid purchases)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.amount), 0) as total 
            FROM purchases p 
            WHERE p.user_id = ? AND p.status = 'paid'
        ");
        $stmt->execute([$user_id]);
        $total_spent = $stmt->fetchColumn();
        
        // Get total downloads (from purchase logs)
        try {
            // First check if purchase_logs table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_logs'");
            $table_exists = $stmt->fetch();
            
            if ($table_exists) {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(pl.download_count), 0) as total 
                    FROM purchases p 
                    LEFT JOIN purchase_logs pl ON p.id = pl.purchase_id 
                    WHERE p.user_id = ? AND p.status = 'paid'
                ");
                $stmt->execute([$user_id]);
                $total_downloads = $stmt->fetchColumn();
            } else {
                // If table doesn't exist, count purchases as downloads
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM purchases p 
                    WHERE p.user_id = ? AND p.status = 'paid'
                ");
                $stmt->execute([$user_id]);
                $total_downloads = $stmt->fetchColumn();
            }
        } catch (Exception $e) {
            error_log("Error getting download count: " . $e->getMessage());
            $total_downloads = 0;
        }
        
        // Get total receipts
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM receipts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_receipts = $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting receipts count: " . $e->getMessage());
            $total_receipts = 0;
        }
        
        // Get recent purchases with download information
        try {
            // Check if purchase_logs table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_logs'");
            $table_exists = $stmt->fetch();
            
            if ($table_exists) {
                $stmt = $pdo->prepare("
                    SELECT 
                        p.*, 
                        pr.title as product_title, 
                        pr.preview_image, 
                        pr.doc_file, 
                        pr.drive_link,
                        pr.price as original_price,
                        COALESCE(p.discount_amount, 0) as discount_amount,
                        COALESCE(p.original_amount, pr.price) as display_price,
                        COALESCE(pl.download_count, 0) as download_count,
                        pl.last_downloaded,
                        CASE 
                            WHEN p.amount = 0 AND p.discount_amount > 0 THEN 'FREE with coupon'
                            WHEN p.amount = 0 AND p.discount_amount = 0 THEN 'FREE'
                            ELSE 'Paid'
                        END as purchase_type
                    FROM purchases p 
                    JOIN products pr ON p.product_id = pr.id 
                    LEFT JOIN purchase_logs pl ON p.id = pl.purchase_id
                    WHERE p.user_id = ? AND p.status = 'paid'
                    ORDER BY p.created_at DESC 
                    LIMIT 5
                ");
            } else {
                // If table doesn't exist, get purchases without download info
                $stmt = $pdo->prepare("
                    SELECT 
                        p.*, 
                        pr.title as product_title, 
                        pr.preview_image, 
                        pr.doc_file, 
                        pr.drive_link,
                        pr.price as original_price,
                        COALESCE(p.discount_amount, 0) as discount_amount,
                        COALESCE(p.original_amount, pr.price) as display_price,
                        0 as download_count,
                        NULL as last_downloaded,
                        CASE 
                            WHEN p.amount = 0 AND p.discount_amount > 0 THEN 'FREE with coupon'
                            WHEN p.amount = 0 AND p.discount_amount = 0 THEN 'FREE'
                            ELSE 'Paid'
                        END as purchase_type
                    FROM purchases p 
                    JOIN products pr ON p.product_id = pr.id 
                    WHERE p.user_id = ? AND p.status = 'paid'
                    ORDER BY p.created_at DESC 
                    LIMIT 5
                ");
            }
            
            $stmt->execute([$user_id]);
            $recent_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting recent purchases: " . $e->getMessage());
            $recent_purchases = [];
        }
        
        return [
            'total_purchases' => $total_purchases,
            'total_spent' => $total_spent,
            'total_downloads' => $total_downloads,
            'total_receipts' => $total_receipts,
            'recent_purchases' => $recent_purchases
        ];
        
    } catch (Exception $e) {
        error_log("Error getting user dashboard stats: " . $e->getMessage());
        return [
            'total_purchases' => 0,
            'total_spent' => 0,
            'total_downloads' => 0,
            'total_receipts' => 0,
            'recent_purchases' => []
        ];
    }
}

/**
 * Get real-time admin dashboard statistics
 */
function getAdminDashboardStats($pdo) {
    try {
        // Total revenue (users + guests) - actual amount paid
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(
                CASE WHEN p.status = 'paid' 
                THEN GREATEST(0, COALESCE(p.amount, pr.price))
                ELSE 0 
                END
            ), 0) as user_revenue
            FROM purchases p 
            JOIN products pr ON p.product_id = pr.id
        ");
        $user_revenue = $stmt->fetchColumn();
        
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(
                CASE WHEN go.status = 'paid' 
                THEN GREATEST(0, go.total_amount)
                ELSE 0 
                END
            ), 0) as guest_revenue
            FROM guest_orders go
        ");
        $guest_revenue = $stmt->fetchColumn();
        $total_revenue = $user_revenue + $guest_revenue;
        
        // Total orders
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM purchases WHERE status = 'paid') +
                (SELECT COUNT(*) FROM guest_orders WHERE status = 'paid') as total_orders
        ");
        $total_orders = $stmt->fetchColumn();
        
        // User orders
        $stmt = $pdo->query("SELECT COUNT(*) FROM purchases WHERE status = 'paid'");
        $user_orders = $stmt->fetchColumn();
        
        // Guest orders
        $stmt = $pdo->query("SELECT COUNT(*) FROM guest_orders WHERE status = 'paid'");
        $guest_orders = $stmt->fetchColumn();
        
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $total_products = $stmt->fetchColumn();
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $total_users = $stmt->fetchColumn();
        
        // Total receipts
        $stmt = $pdo->query("SELECT COUNT(*) FROM receipts");
        $total_receipts = $stmt->fetchColumn();
        
        // Total discounts
        $stmt = $pdo->query("
            SELECT 
                COALESCE(SUM(p.discount_amount), 0) as user_discounts,
                COALESCE(SUM(go.discount_amount), 0) as guest_discounts
            FROM purchases p 
            CROSS JOIN guest_orders go
            WHERE p.status = 'paid' AND go.status = 'paid'
        ");
        $discount_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_discounts = ($discount_data['user_discounts'] ?? 0) + ($discount_data['guest_discounts'] ?? 0);
        
        // Orders with discounts
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM purchases WHERE status = 'paid' AND discount_amount > 0) +
                (SELECT COUNT(*) FROM guest_orders WHERE status = 'paid' AND discount_amount > 0) as orders_with_discounts
        ");
        $orders_with_discounts = $stmt->fetchColumn();
        
        // Recent orders
        $stmt = $pdo->query("
            SELECT p.*, pr.title as product_title, u.name as user_name
            FROM purchases p 
            JOIN products pr ON p.product_id = pr.id
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'paid'
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent guest orders
        $stmt = $pdo->query("
            SELECT go.*, p.title as product_title
            FROM guest_orders go
            JOIN products p ON go.product_id = p.id
            WHERE go.status = 'paid'
            ORDER BY go.created_at DESC 
            LIMIT 10
        ");
        $recent_guest_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Unread admin notifications
        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
        $unread_notifications = $stmt->fetchColumn();
        
        // Total submissions
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'paid'");
            $total_submissions = $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting submissions count: " . $e->getMessage());
            $total_submissions = 0;
        }
        
        // Total SMS sent
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM submission_analyst_logs WHERE action LIKE '%sms%' OR action LIKE '%otp%'");
            $total_sms = $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting SMS count: " . $e->getMessage());
            $total_sms = 0;
        }
        
        // Total downloads (from purchase logs and submission downloads)
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_logs'");
            $purchase_logs_exists = $stmt->fetch();
            
            if ($purchase_logs_exists) {
                $stmt = $pdo->query("SELECT COALESCE(SUM(download_count), 0) FROM purchase_logs");
                $purchase_downloads = $stmt->fetchColumn();
            } else {
                $purchase_downloads = 0;
            }
            
            // Count submission downloads from logs
            $stmt = $pdo->query("SELECT COUNT(*) FROM submission_analyst_logs WHERE action LIKE '%download%' OR action LIKE '%export%'");
            $submission_downloads = $stmt->fetchColumn();
            
            $total_downloads = $purchase_downloads + $submission_downloads;
        } catch (Exception $e) {
            error_log("Error getting downloads count: " . $e->getMessage());
            $total_downloads = 0;
        }
        
        // Monthly revenue breakdown
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(p.created_at, '%Y-%m') as month,
                SUM(GREATEST(0, COALESCE(p.amount, pr.price))) as revenue
            FROM purchases p 
            JOIN products pr ON p.product_id = pr.id
            WHERE p.status = 'paid' 
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $monthly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_revenue' => $total_revenue,
            'total_orders' => $total_orders,
            'user_orders' => $user_orders,
            'guest_orders' => $guest_orders,
            'total_products' => $total_products,
            'total_users' => $total_users,
            'total_receipts' => $total_receipts,
            'total_discounts' => $total_discounts,
            'orders_with_discounts' => $orders_with_discounts,
            'total_submissions' => $total_submissions,
            'total_sms' => $total_sms,
            'total_downloads' => $total_downloads,
            'recent_orders' => $recent_orders,
            'recent_guest_orders' => $recent_guest_orders,
            'unread_notifications' => $unread_notifications,
            'monthly_revenue' => $monthly_revenue
        ];
        
    } catch (Exception $e) {
        error_log("Error getting admin dashboard stats: " . $e->getMessage());
        return [
            'total_revenue' => 0,
            'total_orders' => 0,
            'user_orders' => 0,
            'guest_orders' => 0,
            'total_products' => 0,
            'total_users' => 0,
            'total_receipts' => 0,
            'recent_orders' => [],
            'recent_guest_orders' => [],
            'unread_notifications' => 0,
            'monthly_revenue' => []
        ];
    }
}

/**
 * Get real-time statistics for specific admin pages
 */
function getPageSpecificStats($pdo, $page) {
    try {
        switch ($page) {
            case 'orders':
                // Orders page statistics
                $stmt = $pdo->query("
                    SELECT 
                        status,
                        COUNT(*) as count,
                        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as revenue
                    FROM purchases 
                    GROUP BY status
                ");
                $order_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->query("
                    SELECT 
                        status,
                        COUNT(*) as count,
                        SUM(total_amount) as revenue
                    FROM guest_orders 
                    GROUP BY status
                ");
                $guest_order_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return [
                    'order_stats' => $order_stats,
                    'guest_order_stats' => $guest_order_stats
                ];
                
            case 'users':
                // Users page statistics
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total_users,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_month,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_week
                    FROM users
                ");
                $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->query("
                    SELECT 
                        u.id,
                        u.name,
                        u.email,
                        COUNT(p.id) as purchase_count,
                        SUM(p.amount) as total_spent
                    FROM users u
                    LEFT JOIN purchases p ON u.id = p.user_id AND p.status = 'paid'
                    GROUP BY u.id
                    ORDER BY total_spent DESC
                    LIMIT 10
                ");
                $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return [
                    'user_stats' => $user_stats,
                    'top_users' => $top_users
                ];
                
            case 'reports':
                // Reports page statistics
                $stmt = $pdo->query("
                    SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as orders,
                        SUM(amount) as revenue
                    FROM purchases 
                    WHERE status = 'paid'
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month DESC
                    LIMIT 12
                ");
                $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->query("
                    SELECT 
                        pr.title as product,
                        COUNT(p.id) as sales,
                        SUM(p.amount) as revenue
                    FROM purchases p
                    JOIN products pr ON p.product_id = pr.id
                    WHERE p.status = 'paid'
                    GROUP BY p.product_id
                    ORDER BY revenue DESC
                    LIMIT 10
                ");
                $product_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return [
                    'monthly_stats' => $monthly_stats,
                    'product_performance' => $product_performance
                ];
                
            default:
                return [];
        }
        
    } catch (Exception $e) {
        error_log("Error getting page-specific stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Update dashboard statistics in real-time
 */
function updateDashboardStats($pdo) {
    try {
        // This function can be called to refresh statistics
        // For now, it just returns the current stats
        return [
            'user_stats' => getUserDashboardStats($pdo, $_SESSION['user_id'] ?? 0),
            'admin_stats' => getAdminDashboardStats($pdo),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        error_log("Error updating dashboard stats: " . $e->getMessage());
        return false;
    }
}
?>
