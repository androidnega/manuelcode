<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$analyst_id = $_SESSION['analyst_id'];

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    // Fallback: Create a list of files to download individually
    try {
        $stmt = $pdo->prepare("SELECT * FROM submissions WHERE status = 'paid' ORDER BY created_at DESC");
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($submissions)) {
            header('Location: /dashboard?message=no_submissions');
            exit;
        }
        
        // Create HTML download page
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Download All Documents - Manual Download</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        </head>
        <body class="bg-gray-50 p-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-download mr-2 text-blue-600"></i>Download All Documents
                    </h1>
                    
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-info-circle mr-2"></i>
                        ZIP functionality not available. Please download files individually or enable ZIP extension.
                    </div>
                    
                    <div class="mb-4">
                        <button onclick="downloadAll()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>Download All Files (Auto)
                        </button>
                        <a href="/dashboard" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 ml-2">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">';
        
        foreach ($submissions as $submission) {
            $file_exists = file_exists('../' . $submission['file_path']);
            echo '<tr>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($submission['name']) . '</div>
                    <div class="text-sm text-gray-500">' . htmlspecialchars($submission['index_number']) . '</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">' . htmlspecialchars($submission['file_name']) . '</div>
                    <div class="text-sm text-gray-500">' . strtoupper($submission['file_type']) . ' • ' . round($submission['file_size'] / 1024 / 1024, 2) . ' MB</div>
                </td>
                <td class="px-6 py-4">
                    <a href="download_submission.php?id=' . $submission['id'] . '" class="text-blue-600 hover:text-blue-900" target="_blank">
                        <i class="fas fa-download mr-1"></i>Download
                    </a>
                </td>
            </tr>';
        }
        
        echo '</tbody></table></div></div></div>
        
        <script>
        function downloadAll() {
            const links = document.querySelectorAll("a[href*=\'download_submission.php\']");
            links.forEach((link, index) => {
                setTimeout(() => {
                    window.open(link.href, "_blank");
                }, index * 1000); // Download one file every second
            });
        }
        </script>
        </body></html>';
        exit;
    } catch (Exception $e) {
        error_log("Error creating download page: " . $e->getMessage());
        header('Location: /dashboard?message=error');
        exit;
    }
}

// ZIP Archive is available - create ZIP with all documents
try {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE status = 'paid' ORDER BY created_at DESC");
    $stmt->execute();
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($submissions)) {
        header('Location: /dashboard?message=no_submissions');
        exit;
    }
    
    // Create ZIP file
    $zip = new ZipArchive();
    $temp_file = tempnam(sys_get_temp_dir(), 'documents_');
    
    if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
        throw new Exception('Could not create ZIP file.');
    }
    
    $added_files = 0;
    $errors = [];
    
    foreach ($submissions as $submission) {
        $file_path = '../' . $submission['file_path'];
        
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
    
    // Log the download action
    try {
        $stmt = $pdo->prepare("
            INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, 'download_all_documents', ?, ?, ?, NOW())
        ");
        $download_details = json_encode([
            'files_count' => $added_files,
            'errors' => $errors,
            'total_submissions' => count($submissions)
        ]);
        $stmt->execute([
            $analyst_id,
            $download_details,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (Exception $e) {
        error_log("Error logging download: " . $e->getMessage());
    }
    
    // Download the ZIP file
    $zip_name = "all_student_documents_" . date('Y-m-d_H-i-s') . ".zip";
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_name . '"');
    header('Content-Length: ' . filesize($temp_file));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    readfile($temp_file);
    unlink($temp_file); // Clean up temp file
    
} catch (Exception $e) {
    error_log("Error creating ZIP: " . $e->getMessage());
    header('Location: dashboard.php?message=error');
    exit;
}
?>
