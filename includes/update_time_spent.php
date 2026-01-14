<?php
// Update time spent for page visits
// This endpoint receives time spent data from the JavaScript analytics tracker

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['session_id']) || !isset($data['page_url']) || !isset($data['time_spent'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$session_id = $data['session_id'];
$page_url = $data['page_url'];
$time_spent = (int)$data['time_spent'];

// Validate time spent (should be reasonable)
if ($time_spent < 0 || $time_spent > 3600) { // Max 1 hour
    http_response_code(400);
    echo json_encode(['error' => 'Invalid time spent']);
    exit;
}

try {
    include 'db.php';
    
    // Update the most recent page visit for this session and page URL
    $stmt = $pdo->prepare("
        UPDATE page_visits 
        SET time_spent = ? 
        WHERE session_id = ? AND page_url = ? 
        ORDER BY visit_time DESC 
        LIMIT 1
    ");
    
    $result = $stmt->execute([$time_spent, $session_id, $page_url]);
    
    if ($result) {
        // Also update the popular_pages table with average time spent
        $stmt = $pdo->prepare("
            UPDATE popular_pages 
            SET avg_time_spent = (
                SELECT AVG(time_spent) 
                FROM page_visits 
                WHERE page_url = ? AND time_spent > 0
            )
            WHERE page_url = ?
        ");
        $stmt->execute([$page_url, $page_url]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update time spent']);
    }
    
} catch (Exception $e) {
    error_log("Error updating time spent: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
