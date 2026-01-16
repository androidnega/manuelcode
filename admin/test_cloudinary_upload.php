<?php
/**
 * Cloudinary Test Upload API Endpoint
 * Handles test image uploads to Cloudinary
 */

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Super Admin only.']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['test_image']) || $_FILES['test_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred.']);
    exit;
}

// Include required files
include '../includes/db.php';
include '../includes/cloudinary_helper.php';

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
    error_log("Cloudinary test upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>

