<?php
/**
 * Cloudinary Test Upload API Endpoint
 * Handles test image uploads to Cloudinary
 */

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Super Admin only.']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['test_image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded. Please select an image file.']);
    exit;
}

if ($_FILES['test_image']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'PHP extension stopped the upload',
    ];
    $error_msg = $upload_errors[$_FILES['test_image']['error']] ?? 'Upload error code: ' . $_FILES['test_image']['error'];
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . $error_msg]);
    exit;
}

// Include required files with proper path resolution
$base_dir = dirname(__DIR__);
$db_file = $base_dir . '/includes/db.php';
$cloudinary_file = $base_dir . '/includes/cloudinary_helper.php';

if (!file_exists($db_file)) {
    http_response_code(500);
    error_log("test_cloudinary_upload.php: db.php not found at $db_file");
    echo json_encode(['success' => false, 'error' => 'Database configuration not found.']);
    exit;
}

if (!file_exists($cloudinary_file)) {
    http_response_code(500);
    error_log("test_cloudinary_upload.php: cloudinary_helper.php not found at $cloudinary_file");
    echo json_encode(['success' => false, 'error' => 'Cloudinary helper not found.']);
    exit;
}

include $db_file;
include $cloudinary_file;

try {
    $cloudinaryHelper = new CloudinaryHelper($pdo);
    
    if (!$cloudinaryHelper->isEnabled()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cloudinary is not enabled. Please enable it in System Settings.']);
        exit;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $file_type = $_FILES['test_image']['type'];
    $file_extension = strtolower(pathinfo($_FILES['test_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Please upload an image file (JPEG, PNG, WebP, or GIF).']);
        exit;
    }
    
    // Validate file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($_FILES['test_image']['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File size too large. Maximum size is 10MB.']);
        exit;
    }
    
    // Upload to Cloudinary
    $uploadResult = $cloudinaryHelper->uploadImage(
        $_FILES['test_image']['tmp_name'],
        'test',
        [
            'public_id' => 'test_' . time(),
            'overwrite' => true
        ]
    );
    
    if ($uploadResult && isset($uploadResult['url'])) {
        echo json_encode([
            'success' => true,
            'url' => $uploadResult['url'],
            'public_id' => $uploadResult['public_id'],
            'width' => $uploadResult['width'] ?? 0,
            'height' => $uploadResult['height'] ?? 0,
            'bytes' => $uploadResult['bytes'] ?? 0,
            'format' => $uploadResult['format'] ?? ''
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to upload image to Cloudinary. Please check your Cloudinary configuration and error logs.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $error_message = $e->getMessage();
    $error_trace = $e->getTraceAsString();
    error_log("Cloudinary test upload error: " . $error_message);
    error_log("Stack trace: " . $error_trace);
    
    // Don't expose full error details to client in production
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred during upload. Please check error logs for details.'
    ]);
} catch (Error $e) {
    http_response_code(500);
    $error_message = $e->getMessage();
    $error_trace = $e->getTraceAsString();
    error_log("Cloudinary test upload fatal error: " . $error_message);
    error_log("Stack trace: " . $error_trace);
    
    echo json_encode([
        'success' => false,
        'error' => 'A fatal error occurred. Please check error logs.'
    ]);
}
?>

