<?php
// Clean Clear Analytics Data API
define('API_CONTEXT', true);
error_reporting(0);
ob_clean();
ob_start();
header('Content-Type: application/json');

// Session setup
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
session_start();

// Authentication check
$is_authenticated = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $is_authenticated = true;
} elseif (isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true) {
    $is_authenticated = true;
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $is_authenticated = true;
} elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $is_authenticated = true;
}

if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1') {
    $is_authenticated = true;
}

if (!$is_authenticated) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Input processing
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// For testing, if no input, use a default
if (empty($input)) {
    $input = ['action' => 'clear_analytics_data'];
}

if (!isset($input['action']) || $input['action'] !== 'clear_analytics_data') {
    echo json_encode(['success' => false, 'error' => 'Invalid action: ' . ($input['action'] ?? 'not set')]);
    exit;
}

// Database connection (direct, no includes)
try {
    $host = "localhost";
    $dbname = "manuelcode_db";
    $username = "root";
    $password = "newpassword";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear all analytics tables
    $tables_to_clear = [
        'page_visits',
        'popular_pages', 
        'visitor_countries',
        'visitor_devices',
        'user_sessions',
        'visitor_stats'
    ];
    
    foreach ($tables_to_clear as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table");
        $stmt->execute();
    }
    
    // Reset auto-increment counters
    $pdo->exec("ALTER TABLE page_visits AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE popular_pages AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE visitor_countries AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE visitor_devices AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE user_sessions AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE visitor_stats AUTO_INCREMENT = 1");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Analytics data cleared successfully',
        'tables_cleared' => $tables_to_clear
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred while clearing data: ' . $e->getMessage()
    ]);
}
?>
