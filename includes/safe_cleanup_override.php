<?php
/**
 * Safe cleanup function replacement
 * This prevents the login page errors
 */

if (!function_exists('safe_cleanup_expired_data')) {
    function safe_cleanup_expired_data() {
        global $pdo;
        
        try {
            // Only delete clearly expired OTPs (more than 1 hour old)
            $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("Safe cleanup error: " . $e->getMessage());
            return false;
        }
    }
}

// Override the problematic cleanup function
if (!function_exists('cleanup_expired_data')) {
    function cleanup_expired_data() {
        return safe_cleanup_expired_data();
    }
}
?>