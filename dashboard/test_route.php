<?php
// Test file to verify routing
echo "Router test - Route: " . ($_GET['route'] ?? 'none');
echo "<br>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'none');
echo "<br>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'none');
phpinfo();
?>

