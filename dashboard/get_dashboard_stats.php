<?php
session_start();
include '../includes/db.php';
include '../includes/auth_only.php';
include '../includes/dashboard_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get real-time user dashboard statistics
    $user_stats = getUserDashboardStats($pdo, $user_id);
    
    // Get unread notification count
    include '../includes/notification_helper.php';
    $notificationHelper = new NotificationHelper($pdo);
    $unread_notifications = $notificationHelper->getUnreadCount($user_id);
    
    // Add notifications count to stats
    $user_stats['unread_notifications'] = $unread_notifications;
    
    echo json_encode([
        'success' => true,
        'stats' => $user_stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to fetch statistics',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
