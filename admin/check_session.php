<?php
// Admin Session Status Check Endpoint
// Handles AJAX requests to check if admin session is still valid

header('Content-Type: application/json');

// Include necessary files
require_once '../includes/db.php';
require_once '../includes/session_manager.php';

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

// Check if it's a session check request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'check_session') {
        // Initialize session manager
        $sessionManager = new SessionManager($pdo);
        
        // Check if session is valid
        $isValid = !$sessionManager->isSessionExpired();
        
        // Update last activity if session is valid
        if ($isValid) {
            $sessionManager->updateLastActivity();
        }
        
        echo json_encode([
            'valid' => $isValid,
            'user_type' => $sessionManager->getUserType(),
            'remaining_time' => $sessionManager->getRemainingTime()
        ]);
        exit;
    }
}

// Invalid request
http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>
