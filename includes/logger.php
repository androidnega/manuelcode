<?php
/**
 * Comprehensive Logging System
 */

/**
 * Log levels
 */
if (!defined('LOG_LEVEL_ERROR')) define('LOG_LEVEL_ERROR', 'ERROR');
if (!defined('LOG_LEVEL_WARNING')) define('LOG_LEVEL_WARNING', 'WARNING');
if (!defined('LOG_LEVEL_INFO')) define('LOG_LEVEL_INFO', 'INFO');
if (!defined('LOG_LEVEL_DEBUG')) define('LOG_LEVEL_DEBUG', 'DEBUG');

/**
 * Log categories
 */
if (!defined('LOG_CATEGORY_AUTH')) define('LOG_CATEGORY_AUTH', 'AUTH');
if (!defined('LOG_CATEGORY_PAYMENT')) define('LOG_CATEGORY_PAYMENT', 'PAYMENT');
if (!defined('LOG_CATEGORY_SMS')) define('LOG_CATEGORY_SMS', 'SMS');
if (!defined('LOG_CATEGORY_DOWNLOAD')) define('LOG_CATEGORY_DOWNLOAD', 'DOWNLOAD');
if (!defined('LOG_CATEGORY_SYSTEM')) define('LOG_CATEGORY_SYSTEM', 'SYSTEM');
if (!defined('LOG_CATEGORY_ADMIN')) define('LOG_CATEGORY_ADMIN', 'ADMIN');
if (!defined('LOG_CATEGORY_RECEIPT')) define('LOG_CATEGORY_RECEIPT', 'RECEIPT');
if (!defined('LOG_CATEGORY_EMAIL')) define('LOG_CATEGORY_EMAIL', 'EMAIL');
if (!defined('LOG_CATEGORY_USER_ACTIVITY')) define('LOG_CATEGORY_USER_ACTIVITY', 'USER_ACTIVITY');
if (!defined('LOG_CATEGORY_DATABASE')) define('LOG_CATEGORY_DATABASE', 'DATABASE');

/**
 * Write log entry
 */
if (!function_exists('write_log')) {
function write_log($level, $category, $message, $data = null, $user_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (level, category, message, data, user_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $level,
            $category,
            $message,
            $data ? json_encode($data) : null,
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        error_log("Logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log authentication events
 */
function log_auth($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_AUTH, $message, $data, $user_id);
}

/**
 * Log payment events
 */
function log_payment($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_PAYMENT, $message, $data, $user_id);
}

/**
 * Log SMS events
 */
function log_sms($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_SMS, $message, $data, $user_id);
}

/**
 * Log download events
 */
function log_download($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_DOWNLOAD, $message, $data, $user_id);
}

/**
 * Log system events
 */
function log_system($message, $data = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_SYSTEM, $message, $data);
}

/**
 * Log admin events
 */
function log_admin($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_ADMIN, $message, $data, $user_id);
}

/**
 * Log receipt events
 */
function log_receipt($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_RECEIPT, $message, $data, $user_id);
}

/**
 * Log email events
 */
function log_email($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_EMAIL, $message, $data, $user_id);
}

/**
 * Log user activity events
 */
function log_user_activity($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_USER_ACTIVITY, $message, $data, $user_id);
}

/**
 * Log database events
 */
function log_database($message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_INFO, LOG_CATEGORY_DATABASE, $message, $data, $user_id);
}

/**
 * Log errors
 */
function log_error($category, $message, $data = null, $user_id = null) {
    return write_log(LOG_LEVEL_ERROR, $category, $message, $data, $user_id);
}

/**
 * Get logs with filters
 */
function get_logs($category = null, $level = null, $limit = 100, $offset = 0) {
    global $pdo;
    
    $where_conditions = [];
    $params = [];
    
    if ($category) {
        $where_conditions[] = "category = ?";
        $params[] = $category;
    }
    
    if ($level) {
        $where_conditions[] = "level = ?";
        $params[] = $level;
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $stmt = $pdo->prepare("
        SELECT * FROM system_logs 
        {$where_clause}
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Clean old logs (keep last 30 days)
 */
function clean_old_logs() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Failed to clean old logs: " . $e->getMessage());
        return 0;
    }
}
} // Close the if (!function_exists('write_log')) block
?>
