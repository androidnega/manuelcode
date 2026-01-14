<?php
// Core Configuration
// These will be overridden by database settings from admin panel

// File Upload Limits
define('MAX_PREVIEW_SIZE', 200 * 1024); // 200KB
define('MAX_DOC_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed File Types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);

// Security Settings
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('DOWNLOAD_TOKEN_EXPIRY', 24 * 3600); // 24 hours

// Default SMS Templates (will be overridden by database settings)
define('SMS_PAYMENT_SUCCESS', 'Payment received successfully! Your download link: {download_url}');
define('SMS_ORDER_CONFIRMED', 'Order #{order_id} confirmed! Download your file: {download_url}');
?>
