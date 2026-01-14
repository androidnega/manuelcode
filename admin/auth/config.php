<?php
// Admin Configuration
// In production, these should be stored in environment variables or a secure database

// Admin credentials (change these in production)
if (!defined('ADMIN_USERNAME')) {
    define('ADMIN_USERNAME', 'admin');
}
if (!defined('ADMIN_PASSWORD')) {
    define('ADMIN_PASSWORD', 'admin123'); // In production, use password_hash()
}

// Session timeout (in seconds) - only define if not already defined
if (!defined('ADMIN_SESSION_TIMEOUT')) {
    define('ADMIN_SESSION_TIMEOUT', 7200); // 2 hours
}

// Security settings
if (!defined('ADMIN_MAX_LOGIN_ATTEMPTS')) {
    define('ADMIN_MAX_LOGIN_ATTEMPTS', 5);
}
if (!defined('ADMIN_LOCKOUT_TIME')) {
    define('ADMIN_LOCKOUT_TIME', 900); // 15 minutes
}

// Optional: Database-based admin authentication
// Set to true to use database instead of hardcoded credentials
if (!defined('USE_DATABASE_AUTH')) {
    define('USE_DATABASE_AUTH', false);
}

// Database table for admin users (if USE_DATABASE_AUTH is true)
if (!defined('ADMIN_TABLE')) {
    define('ADMIN_TABLE', 'admin_users');
}
?>
