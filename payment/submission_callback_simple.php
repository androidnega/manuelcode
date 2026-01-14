<?php
// Simple test callback - just redirect to success page
error_log("Simple submission callback called");
error_log("GET: " . json_encode($_GET));
error_log("POST: " . json_encode($_POST));

// Just redirect to success page with a test reference
$test_ref = $_GET['reference'] ?? 'TEST_' . time();
header('Location: ../submission_success.php?ref=' . urlencode($test_ref));
exit;
?>
