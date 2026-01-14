<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$analyst_id = $_SESSION['analyst_id'];
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$submission_id) {
    http_response_code(400);
    echo 'Invalid submission ID';
    exit;
}

// Get submission details
try {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ? AND status = 'paid'");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        http_response_code(404);
        echo 'Submission not found or not paid';
        exit;
    }
    
    if (empty($submission['file_path'])) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
    
    $file_path = '../' . $submission['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo 'File not found on server';
        exit;
    }
    
    // Log analyst activity
    $stmt = $pdo->prepare("
        INSERT INTO submission_analyst_logs (analyst_id, submission_id, action, details, ip_address, user_agent, created_at)
        VALUES (?, ?, 'download_file', 'Downloaded submission file', ?, ?, NOW())
    ");
    $stmt->execute([$analyst_id, $submission_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    
    // Get file information
    $file_size = filesize($file_path);
    $file_name = $submission['file_name'];
    $file_type = mime_content_type($file_path);
    
    // Set headers for file download
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Read and output file
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    error_log("Error downloading submission file: " . $e->getMessage());
    http_response_code(500);
    echo 'Server error occurred';
    exit;
}
?>
