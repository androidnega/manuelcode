<?php
// Real-time User Activity Data Endpoint
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/analytics_helper.php';

header('Content-Type: application/json');

try {
    // Get active users
    $active_users = getActiveUsers();
    
    // Get recent sessions
    $stmt = $pdo->prepare("
        SELECT us.*, u.name, u.email
        FROM user_sessions us
        LEFT JOIN users u ON us.user_id = u.id
        ORDER BY us.login_time DESC
        LIMIT 50
    ");
    $stmt->execute();
    $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get session statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_sessions FROM user_sessions");
    $stmt->execute();
    $total_sessions = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_sessions FROM user_sessions WHERE is_active = 1");
    $stmt->execute();
    $active_sessions = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as unique_users FROM user_sessions WHERE user_id IS NOT NULL");
    $stmt->execute();
    $unique_users = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_sessions FROM user_sessions WHERE DATE(login_time) = CURDATE()");
    $stmt->execute();
    $today_sessions = $stmt->fetchColumn();
    
    $response = [
        'success' => true,
        'data' => [
            'active_users' => $active_users,
            'recent_sessions' => $recent_sessions,
            'stats' => [
                'active_sessions' => $active_sessions,
                'today_sessions' => $today_sessions,
                'unique_users' => $unique_users,
                'total_sessions' => $total_sessions
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
