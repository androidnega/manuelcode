<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if ZipArchive is available and provide fallback
if (!class_exists('ZipArchive')) {
    // Create a simple file listing export as fallback
    $export_type = $_GET['type'] ?? 'all';
    $submission_id = $_GET['id'] ?? '';
    
    if ($export_type === 'single' && !empty($submission_id)) {
        $query = "SELECT * FROM submissions WHERE id = ? AND status = 'paid'";
        $params = [$submission_id];
        $filename = "submission_{$submission_id}_files.txt";
    } else {
        $query = "SELECT * FROM submissions WHERE status = 'paid' ORDER BY created_at DESC";
        $params = [];
        $filename = "all_submissions_list_" . date('Y-m-d_H-i-s') . ".txt";
    }
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            if (empty($submissions)) {
        // Redirect back to dashboard with message
        header('Location: /dashboard?message=no_submissions');
        exit;
    }
        
        // Create text file with file paths
        $content = "SUBMISSION FILES LIST\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Total Files: " . count($submissions) . "\n\n";
        
        foreach ($submissions as $submission) {
            $content .= "Submission ID: {$submission['id']}\n";
            $content .= "Student: {$submission['name']}\n";
            $content .= "Index Number: {$submission['index_number']}\n";
            $content .= "File: {$submission['file_name']}\n";
            $content .= "File Path: {$submission['file_path']}\n";
            $content .= "Payment Reference: {$submission['reference']}\n";
            $content .= "Date: {$submission['created_at']}\n";
            $content .= "-" . str_repeat("-", 50) . "\n\n";
        }
        
        // Download the text file
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        echo $content;
        exit;
        
    } catch (Exception $e) {
        error_log("Export error: " . $e->getMessage());
        die('Export failed: ' . $e->getMessage());
    }
}

// Get export parameters
$export_type = $_GET['type'] ?? 'all'; // all, date_range, single
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$submission_id = $_GET['id'] ?? '';

// Build query based on export type
if ($export_type === 'single' && !empty($submission_id)) {
    $query = "SELECT * FROM submissions WHERE id = ? AND status = 'paid'";
    $params = [$submission_id];
    $zip_name = "submission_{$submission_id}.zip";
} elseif ($export_type === 'date_range' && !empty($date_from) && !empty($date_to)) {
    $query = "SELECT * FROM submissions WHERE status = 'paid' AND created_at BETWEEN ? AND ? ORDER BY created_at DESC";
    $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    $zip_name = "submissions_{$date_from}_to_{$date_to}.zip";
} else {
    // Export all successful submissions
    $query = "SELECT * FROM submissions WHERE status = 'paid' ORDER BY created_at DESC";
    $params = [];
    $zip_name = "all_submissions_" . date('Y-m-d_H-i-s') . ".zip";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($submissions)) {
        // Redirect back to dashboard with message
        header('Location: /dashboard?message=no_submissions');
        exit;
    }
    
    // Create ZIP file
    $zip = new ZipArchive();
    $temp_file = tempnam(sys_get_temp_dir(), 'submissions_');
    
    if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
        die('Could not create ZIP file.');
    }
    
    $added_files = 0;
    $errors = [];
    
    foreach ($submissions as $submission) {
        $file_path = $submission['file_path'];
        
        // Check if file exists
        if (!file_exists($file_path)) {
            $errors[] = "File not found: {$submission['file_name']} (ID: {$submission['id']})";
            continue;
        }
        
        // Create organized filename
        $file_extension = pathinfo($submission['file_name'], PATHINFO_EXTENSION);
        $clean_index = preg_replace('/[^A-Za-z0-9]/', '_', $submission['index_number']);
        $clean_name = preg_replace('/[^A-Za-z0-9]/', '_', $submission['name']);
        $zip_filename = "{$clean_index}_{$clean_name}.{$file_extension}";
        
        // Add file to ZIP
        if ($zip->addFile($file_path, $zip_filename)) {
            $added_files++;
        } else {
            $errors[] = "Failed to add file: {$submission['file_name']}";
        }
    }
    
    $zip->close();
    
    // Log the export action
    try {
        $stmt = $pdo->prepare("
            INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, 'export_submissions', ?, ?, ?, NOW())
        ");
        $export_details = json_encode([
            'export_type' => $export_type,
            'files_count' => $added_files,
            'errors' => $errors,
            'zip_name' => $zip_name,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'submission_id' => $submission_id
        ]);
        $stmt->execute([
            $_SESSION['analyst_id'],
            $export_details,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (Exception $e) {
        error_log("Error logging export: " . $e->getMessage());
    }
    
    // Download the ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_name . '"');
    header('Content-Length: ' . filesize($temp_file));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    readfile($temp_file);
    unlink($temp_file); // Clean up temp file
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    die('Export failed: ' . $e->getMessage());
}
?>
