<?php
session_start();
include '../includes/db.php';
include '../includes/notification_helper.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $notificationHelper = new NotificationHelper($pdo);
    $unread_count = $notificationHelper->getUnreadCount($user_id);
    
    echo json_encode([
        'unread_count' => $unread_count,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error getting notification count: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
