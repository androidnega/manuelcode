<?php
include '../includes/google_config.php';

$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?scope=email%20profile&response_type=code&redirect_uri=" . urlencode(GOOGLE_REDIRECT_URI) . "&client_id=" . GOOGLE_CLIENT_ID;
header("Location: $auth_url");
exit;
?>
