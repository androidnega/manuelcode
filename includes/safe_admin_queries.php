<?php
/**
 * Safe Admin Queries - Replacement for problematic queries in admin pages
 */

function get_users_safely($pdo) {
    try {
        // Check if tables exist before querying
        $tables_exist = true;
        $required_tables = ["users", "purchases", "products"];
        
        foreach ($required_tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '".$table."'");
            if ($stmt->rowCount() == 0) {
                $tables_exist = false;
                break;
            }
        }
        
        if (!$tables_exist) {
            // Return basic user data if complex tables don't exist
            $stmt = $pdo->query("SELECT * FROM users WHERE status != 'deleted' ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Try complex query with error handling
        $stmt = $pdo->query("
            SELECT 
                u.*,
                COALESCE(COUNT(DISTINCT p.id), 0) as total_purchases,
                COALESCE(SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END), 0) as total_spent,
                0 as total_visits,
                NULL as last_visit,
                NULL as last_ip
            FROM users u
            LEFT JOIN purchases p ON u.id = p.user_id AND p.status = 'paid'
            LEFT JOIN products pr ON p.product_id = pr.id
            WHERE u.status != 'deleted'
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Safe user query error: " . $e->getMessage());
        // Fallback to basic query
        $stmt = $pdo->query("SELECT * FROM users WHERE status != 'deleted' ORDER BY created_at DESC LIMIT 100");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

function get_ip_management_safely($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'ip_management'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT * FROM ip_management ORDER BY created_at DESC LIMIT 100");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    } catch (Exception $e) {
        error_log("Safe IP management query error: " . $e->getMessage());
        return [];
    }
}

function get_recent_activity_safely($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_activity'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("
                SELECT ua.*, u.name as user_name, u.email 
                FROM user_activity ua 
                LEFT JOIN users u ON ua.user_id = u.id 
                ORDER BY ua.created_at DESC 
                LIMIT 50
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    } catch (Exception $e) {
        error_log("Safe activity query error: " . $e->getMessage());
        return [];
    }
}
?>