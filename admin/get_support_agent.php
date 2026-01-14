<?php
include 'auth/check_auth.php';
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Agent ID required']);
    exit;
}

$agent_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM support_agents WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        http_response_code(404);
        echo json_encode(['error' => 'Support agent not found']);
        exit;
    }

    // Remove password from response
    unset($agent['password']);

    header('Content-Type: application/json');
    echo json_encode($agent);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
