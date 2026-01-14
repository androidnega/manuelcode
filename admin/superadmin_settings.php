<?php
// Superadmin settings API
ob_start();
header('Content-Type: application/json');
session_start();
include '../includes/db.php';

if (($_SESSION['user_role'] ?? 'user') !== 'superadmin') {
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Forbidden']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  echo json_encode(['success'=>false,'error'=>'Invalid JSON']);
  exit;
}

$allowed = [
  'site_url',
  'paystack_public_key',
  'paystack_secret_key', 
  'paystack_live_public_key',
  'paystack_live_secret_key',
  'arkassel_api_key',
  'sms_sender_name',
  'download_token_secret'
];

try {
  $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
  foreach ($allowed as $key) {
    if (array_key_exists($key, $input)) {
      $stmt->execute([$key, trim((string)$input[$key])]);
    }
  }
  echo json_encode(['success'=>true]);
} catch (Exception $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>


