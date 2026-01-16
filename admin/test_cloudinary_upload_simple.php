<?php
/**
 * Simplified Cloudinary Test Upload - Minimal version
 */

// Start output buffering
ob_start();

header('Content-Type: application/json');

session_start();

// Check authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Check file upload
if (!isset($_FILES['test_image']) || $_FILES['test_image']['error'] !== UPLOAD_ERR_OK) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

// Get base directory
$base_dir = dirname(__DIR__);

// Include files
try {
    require_once $base_dir . '/includes/db.php';
    require_once $base_dir . '/includes/cloudinary_helper.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

// Check database connection
if (!isset($pdo)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
    exit;
}

try {
    // Initialize Cloudinary
    $cloudinaryHelper = new CloudinaryHelper($pdo);
    
    if (!$cloudinaryHelper->isEnabled()) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cloudinary is not enabled']);
        exit;
    }
    
    // Validate file
    $file = $_FILES['test_image'];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }
    
    // Upload
    $result = $cloudinaryHelper->uploadImage(
        $file['tmp_name'],
        'test',
        ['public_id' => 'test_' . time(), 'overwrite' => true]
    );
    
    ob_end_clean();
    
    // Check for error in result
    if (is_array($result) && isset($result['error'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result['error']]);
        exit;
    }
    
    if ($result && isset($result['url'])) {
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'public_id' => $result['public_id'] ?? '',
            'width' => $result['width'] ?? 0,
            'height' => $result['height'] ?? 0
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Upload failed. Check error logs.']);
    }
    
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    error_log("Cloudinary upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

