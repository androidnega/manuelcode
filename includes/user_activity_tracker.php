<?php
// User Activity Tracker
// Include this file at the top of pages to track user activity

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function trackUserActivity($page_name = null) {
    global $pdo;
    
    // Only track if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    // Get current page name if not provided
    if (!$page_name) {
        $page_name = $_SERVER['REQUEST_URI'];
        // Remove query parameters
        $page_name = strtok($page_name, '?');
        // Get just the filename
        $page_name = basename($page_name);
        if (empty($page_name) || $page_name === '/') {
            $page_name = 'index.php';
        }
    }
    
    // Get user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        // Check if we already have a recent entry for this user and page (within 5 minutes)
        $stmt = $pdo->prepare("
            SELECT id FROM user_activity 
            WHERE user_id = ? AND page_visited = ? AND visited_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$_SESSION['user_id'], $page_name]);
        
        // Only log if no recent entry exists
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO user_activity (user_id, page_visited, ip_address, user_agent, visited_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $page_name, $ip_address, $user_agent]);
        }
    } catch (Exception $e) {
        // Silently fail - don't break the user experience
        error_log("User activity tracking error: " . $e->getMessage());
    }
}

// Auto-track current page
trackUserActivity();
?>
