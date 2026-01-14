<?php
// Debug version - log everything to see if callback is being called
error_log("=== SUBMISSION CALLBACK DEBUG START ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("GET data: " . json_encode($_GET));
error_log("POST data: " . json_encode($_POST));
error_log("SERVER data: " . json_encode($_SERVER));

// Write to a file for easier debugging
file_put_contents('submission_callback_debug.log', 
    "=== SUBMISSION CALLBACK DEBUG ===\n" .
    "Time: " . date('Y-m-d H:i:s') . "\n" .
    "GET: " . json_encode($_GET) . "\n" .
    "POST: " . json_encode($_POST) . "\n" .
    "SERVER: " . json_encode($_SERVER) . "\n" .
    "================================\n", 
    FILE_APPEND
);

// Now call the actual callback
include 'submission_callback.php';
?>
