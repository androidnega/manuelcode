<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = (int)$_GET['id'];

try {
    // Get ticket data
    $stmt = $pdo->prepare("
        SELECT * FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }

    // Get replies
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END as is_agent,
               COALESCE(sa.name, u.name) as author_name
        FROM support_replies sr
        LEFT JOIN support_agents sa ON sr.support_agent_id = sa.id
        LEFT JOIN users u ON sr.user_id = u.id
        WHERE sr.ticket_id = ?
        ORDER BY sr.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ticket['replies'] = $replies;

    echo json_encode($ticket);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
