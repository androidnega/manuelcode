<?php
require 'auth_only.php';

if (!in_array(($_SESSION['user_role'] ?? 'user'), ['admin','superadmin'], true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}
?>
