<?php
// Session Management System
// Handles session timeout, security, and admin privileges

// Session configuration - only define if not already defined
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 600); // 10 minutes in seconds
}
if (!defined('ADMIN_SESSION_TIMEOUT')) {
    define('ADMIN_SESSION_TIMEOUT', 600); // 10 minutes for admin sessions
}
if (!defined('SUPERADMIN_SESSION_TIMEOUT')) {
    define('SUPERADMIN_SESSION_TIMEOUT', 600); // 10 minutes for superadmin
}
if (!defined('SUPPORT_SESSION_TIMEOUT')) {
    define('SUPPORT_SESSION_TIMEOUT', 600); // 10 minutes for support
}

class SessionManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Initialize session with security settings
     */
    public function initSession() {
        // Set secure session parameters BEFORE starting session
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
        
        // Set session timeout based on user type
        $this->setSessionTimeout();
        
        // Check if session has expired
        if ($this->isSessionExpired()) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $this->updateLastActivity();
        
        return true;
    }
    
    /**
     * Set session timeout based on user type
     */
    private function setSessionTimeout() {
        $timeout = SESSION_TIMEOUT; // Default timeout
        
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            $timeout = ADMIN_SESSION_TIMEOUT;
        }
        
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin') {
            $timeout = SUPERADMIN_SESSION_TIMEOUT;
        }
        
        if (isset($_SESSION['is_support']) && $_SESSION['is_support']) {
            $timeout = SUPPORT_SESSION_TIMEOUT;
        }
        
        // Set session timeout
        $_SESSION['timeout'] = $timeout;
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if session has expired
     */
    public function isSessionExpired() {
        if (!isset($_SESSION['last_activity']) || !isset($_SESSION['timeout'])) {
            return true;
        }
        
        $timeout = $_SESSION['timeout'];
        $last_activity = $_SESSION['last_activity'];
        
        return (time() - $last_activity) > $timeout;
    }
    
    /**
     * Update last activity timestamp
     */
    public function updateLastActivity() {
        $_SESSION['last_activity'] = time();
        
        // Update in database for logged-in users
        if (isset($_SESSION['user_id'])) {
            $this->updateUserActivity($_SESSION['user_id']);
        }
        
        if (isset($_SESSION['admin_id'])) {
            $this->updateAdminActivity($_SESSION['admin_id']);
        }
        
        if (isset($_SESSION['support_id'])) {
            $this->updateSupportActivity($_SESSION['support_id']);
        }
    }
    
    /**
     * Update user activity in database
     */
    private function updateUserActivity($user_id) {
        try {
            // Check if this is the first activity update (indicating first dashboard access)
            $stmt = $this->pdo->prepare("SELECT last_activity FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $is_first_access = !$user || !$user['last_activity'];
            
            if ($is_first_access) {
                // First time accessing dashboard - update last_login
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET last_activity = NOW(), 
                        last_login = NOW(),
                        last_ip = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
            } else {
                // Regular activity update
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET last_activity = NOW(), 
                        last_ip = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$_SERVER['REMOTE_ADDR'], $user_id]);
        } catch (Exception $e) {
            error_log("Error updating user activity: " . $e->getMessage());
        }
    }
    
    /**
     * Update admin activity in database
     */
    private function updateAdminActivity($admin_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE admins 
                SET last_activity = NOW(), 
                    last_ip = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'], $admin_id]);
        } catch (Exception $e) {
            error_log("Error updating admin activity: " . $e->getMessage());
        }
    }
    
    /**
     * Update support activity in database
     */
    private function updateSupportActivity($support_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE support_agents 
                SET last_activity = NOW(), 
                    last_ip = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'], $support_id]);
        } catch (Exception $e) {
            error_log("Error updating support activity: " . $e->getMessage());
        }
    }
    
    /**
     * Destroy session and redirect to login
     */
    public function destroySession() {
        // Log the session expiry
        $this->logSessionExpiry();
        
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Log session expiry for analytics
     */
    private function logSessionExpiry() {
        try {
            $user_type = 'guest';
            $user_id = null;
            
            if (isset($_SESSION['user_id'])) {
                $user_type = 'user';
                $user_id = $_SESSION['user_id'];
            } elseif (isset($_SESSION['admin_id'])) {
                $user_type = 'admin';
                $user_id = $_SESSION['admin_id'];
            } elseif (isset($_SESSION['support_id'])) {
                $user_type = 'support';
                $user_id = $_SESSION['support_id'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO session_logs (user_type, user_id, ip_address, session_duration, expiry_reason, created_at) 
                VALUES (?, ?, ?, ?, 'timeout', NOW())
            ");
            
            $session_duration = isset($_SESSION['last_activity']) ? 
                (time() - $_SESSION['last_activity']) : 0;
            
            $stmt->execute([
                $user_type, 
                $user_id, 
                $_SERVER['REMOTE_ADDR'], 
                $session_duration
            ]);
        } catch (Exception $e) {
            error_log("Error logging session expiry: " . $e->getMessage());
        }
    }
    
    /**
     * Get remaining session time in seconds
     */
    public function getRemainingTime() {
        if (!isset($_SESSION['last_activity']) || !isset($_SESSION['timeout'])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = $_SESSION['timeout'] - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Get session timeout in minutes for display
     */
    public function getTimeoutMinutes() {
        $timeout = SESSION_TIMEOUT;
        
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            $timeout = ADMIN_SESSION_TIMEOUT;
        }
        
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin') {
            $timeout = SUPERADMIN_SESSION_TIMEOUT;
        }
        
        if (isset($_SESSION['is_support']) && $_SESSION['is_support']) {
            $timeout = SUPPORT_SESSION_TIMEOUT;
        }
        
        return round($timeout / 60);
    }
    
    /**
     * Check if user has admin privileges
     */
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    /**
     * Check if user has superadmin privileges
     */
    public function isSuperAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin';
    }
    
    /**
     * Check if user has support privileges
     */
    public function isSupport() {
        return isset($_SESSION['is_support']) && $_SESSION['is_support'] === true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user type
     */
    public function getUserType() {
        if ($this->isSuperAdmin()) {
            return 'superadmin';
        } elseif ($this->isAdmin()) {
            return 'admin';
        } elseif ($this->isSupport()) {
            return 'support';
        } elseif ($this->isLoggedIn()) {
            return 'user';
        } else {
            return 'guest';
        }
    }
}

// Global session manager instance
$sessionManager = new SessionManager($pdo);

// Initialize session
if (!$sessionManager->initSession()) {
    // Session expired, redirect to appropriate login page
    $current_page = basename($_SERVER['PHP_SELF']);
    
    if (strpos($current_page, 'admin') !== false) {
        header('Location: auth/login.php?expired=1');
        exit;
    } elseif (strpos($current_page, 'support') !== false) {
        header('Location: auth/support_login.php?expired=1');
        exit;
    } elseif (strpos($current_page, 'dashboard') !== false) {
        header('Location: ../auth/login.php?expired=1');
        exit;
    } else {
        // For regular pages, just continue without session
    }
}
?>
