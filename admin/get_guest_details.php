<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['guest_id'])) {
    echo json_encode(['success' => false, 'error' => 'Guest ID required']);
    exit;
}

$guest_id = (int)$_GET['guest_id'];

try {
    // Get guest details
    $stmt = $pdo->prepare("
        SELECT 
            go.*,
            p.title as product_title,
            p.price as product_price
        FROM guest_orders go
        LEFT JOIN products p ON go.product_id = p.id
        WHERE go.id = ? AND go.status = 'paid'
    ");
    $stmt->execute([$guest_id]);
    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guest) {
        echo json_encode(['success' => false, 'error' => 'Guest account not found']);
        exit;
    }

    // Format the response
    $response = [
        'success' => true,
        'user' => [
            'id' => $guest['id'],
            'name' => $guest['name'],
            'email' => $guest['email'],
            'phone' => $guest['phone'],
            'status' => 'active',
            'created_at' => $guest['created_at'],
            'last_login' => $guest['updated_at'],
            'total_spent' => $guest['total_amount'],
            'last_purchase' => $guest['created_at'],
            'account_type' => 'guest'
        ],
        'purchases' => [
            [
                'id' => $guest['id'],
                'product_title' => $guest['product_title'],
                'amount' => $guest['total_amount'],
                'status' => $guest['status'],
                'created_at' => $guest['created_at'],
                'reference' => $guest['reference']
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
