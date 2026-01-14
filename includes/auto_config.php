<?php
/**
 * Automatic Configuration Management System
 */

/**
 * Initialize automatic configuration
 */
function init_auto_config() {
    global $pdo;
    
    try {
        // Check if settings table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        if (!$stmt->fetch()) {
            create_settings_table();
        }
        
        // Set default configurations if not exists
        set_default_configs();
        
        // Generate download token secret if not set
        ensure_download_token_secret();
        
        // Set site URL if not configured
        ensure_site_url();
        
        return true;
    } catch (Exception $e) {
        error_log("Auto config failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Create settings table if not exists
 */
function create_settings_table() {
    global $pdo;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
}

/**
 * Set default configurations
 */
function set_default_configs() {
    global $pdo;
    
    $defaults = [
        'site_url' => 'https://www.manuelcode.info',
        'sms_payment_success' => 'Payment received successfully! Your download link: {download_url}',
        'sms_order_confirmed' => 'Order #{order_id} confirmed! Download your file: {download_url}',
        'paystack_public_key' => '',
        'paystack_secret_key' => '',
        'paystack_live_public_key' => '',
        'paystack_live_secret_key' => '',
        'arkassel_api_key' => '',
        'download_token_secret' => ''
    ];
    
    foreach ($defaults as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO settings (setting_key, value) 
            VALUES (?, ?)
        ");
        $stmt->execute([$key, $value]);
    }
}

/**
 * Ensure download token secret is set
 */
function ensure_download_token_secret() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'download_token_secret'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || empty($result['value']) || $result['value'] === 'change-this-32-characters-secret-key') {
        $secret = generate_secure_token_secret();
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, value) 
            VALUES ('download_token_secret', ?) 
            ON DUPLICATE KEY UPDATE value = ?
        ");
        $stmt->execute([$secret, $secret]);
    }
}

/**
 * Ensure site URL is set
 */
function ensure_site_url() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'site_url'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || empty($result['value'])) {
        $site_url = 'https://www.manuelcode.info';
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, value) 
            VALUES ('site_url', ?) 
            ON DUPLICATE KEY UPDATE value = ?
        ");
        $stmt->execute([$site_url, $site_url]);
    }
}

/**
 * Generate secure 32-character token secret
 */
function generate_secure_token_secret() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $secret = '';
    
    for ($i = 0; $i < 32; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $secret;
}

/**
 * Get configuration with automatic fallback
 */
function get_auto_config($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['value'])) {
            return $result['value'];
        }
    } catch (Exception $e) {
        // Fallback to constants
    }
    
    // Fallback to constants
    $constant_map = [
        'site_url' => 'SITE_URL',
        'download_token_secret' => 'DOWNLOAD_TOKEN_SECRET',
        'sms_payment_success' => 'SMS_PAYMENT_SUCCESS',
        'sms_order_confirmed' => 'SMS_ORDER_CONFIRMED'
    ];
    
    if (isset($constant_map[$key]) && defined($constant_map[$key])) {
        return constant($constant_map[$key]);
    }
    
    return $default;
}

/**
 * Set configuration value
 */
function set_auto_config($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE value = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        error_log("Failed to set config {$key}: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all configurations
 */
function get_all_configs() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_key, value FROM settings ORDER BY setting_key");
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[$row['setting_key']] = $row['value'];
        }
        return $configs;
    } catch (Exception $e) {
        return [];
    }
}
?>
