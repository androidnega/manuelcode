<?php
// Super Admin - Homepage Image Management
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include '../includes/cloudinary_helper.php';

$error_message = '';
$success_message = '';

// Get current homepage image URL
$current_image_url = '';
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'homepage_team_image_url'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['value'])) {
        $current_image_url = $result['value'];
    }
} catch (Exception $e) {
    error_log("Error getting homepage image: " . $e->getMessage());
}

$cloudinaryHelper = new CloudinaryHelper($pdo);
$cloudinary_enabled = $cloudinaryHelper->isEnabled();

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['homepage_image'])) {
    // Get specific upload error messages
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];
    
    if ($_FILES['homepage_image']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['homepage_image']['error'];
        $error_message = isset($upload_errors[$error_code]) 
            ? 'Upload error: ' . $upload_errors[$error_code] 
            : 'Error uploading file. Error code: ' . $error_code;
        
        // Log the error for debugging
        error_log("Homepage image upload error: Code $error_code - " . ($upload_errors[$error_code] ?? 'Unknown error'));
    } else {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = $_FILES['homepage_image']['type'];
        
        // Also check file extension as backup
        $file_extension = strtolower(pathinfo($_FILES['homepage_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
            $error_message = 'Invalid file type. Please upload a JPEG, PNG, or WebP image.';
        } else {
            // Validate file size (max 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB
            if ($_FILES['homepage_image']['size'] > $max_size) {
                $error_message = 'File size too large. Maximum size is 10MB.';
            } else {
                // Check if file actually exists
                if (!file_exists($_FILES['homepage_image']['tmp_name'])) {
                    $error_message = 'Temporary file not found. Please try again.';
                } else {
                    // Upload to Cloudinary if enabled
                    if ($cloudinary_enabled) {
                        $uploadResult = $cloudinaryHelper->uploadImage(
                            $_FILES['homepage_image']['tmp_name'], 
                            'homepage', 
                            [
                                'public_id' => 'team',
                                'overwrite' => true
                            ]
                        );
                        
                        if ($uploadResult && isset($uploadResult['url'])) {
                            // Trim URL to remove any whitespace
                            $image_url = trim($uploadResult['url']);
                            // Save URL to database
                            $stmt = $pdo->prepare("
                                INSERT INTO settings (setting_key, value) 
                                VALUES ('homepage_team_image_url', ?)
                                ON DUPLICATE KEY UPDATE value = ?
                            ");
                            $stmt->execute([$image_url, $image_url]);
                            
                            $success_message = 'Homepage image uploaded successfully!';
                            $current_image_url = $image_url;
                        } else {
                            $error_message = 'Failed to upload image to Cloudinary. Please check Cloudinary configuration and error logs.';
                            error_log("Cloudinary upload failed. Response: " . json_encode($uploadResult));
                        }
                    } else {
                        $error_message = 'Cloudinary is not enabled. Please enable Cloudinary in System Settings to upload images.';
                    }
                }
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error_message = 'No file was selected. Please choose an image file.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Homepage Image Management - Super Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-blue-800 bg-clip-text text-transparent">
                        <i class="fas fa-image text-blue-500 mr-3"></i>Homepage Image Management
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Manage the main hero image on the homepage</p>
                </div>
                <a href="../dashboard/superadmin" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-6 py-8">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Messages -->
            <?php if ($error_message): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3"></i>
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Current Image Preview -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-eye text-blue-600 mr-3"></i>Current Homepage Image
                </h2>
                
                <?php if ($current_image_url): ?>
                    <div class="space-y-4">
                        <div class="relative bg-gray-100 rounded-lg overflow-hidden" style="aspect-ratio: 16/9; max-height: 500px;">
                            <img 
                                src="<?php echo htmlspecialchars($current_image_url); ?>" 
                                alt="Current Homepage Image"
                                class="w-full h-full object-cover"
                                id="current-image-preview">
                        </div>
                        <div class="text-sm text-gray-600">
                            <p><strong>Image URL:</strong> <span class="font-mono text-xs break-all"><?php echo htmlspecialchars($current_image_url); ?></span></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No homepage image is currently set. Upload an image below.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload New Image -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-upload text-green-600 mr-3"></i>Upload New Homepage Image
                </h2>
                
                <?php if (!$cloudinary_enabled): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-red-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Cloudinary is not enabled. Please enable Cloudinary in <a href="../dashboard/system-settings" class="underline font-semibold">System Settings</a> to upload images.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Recommended Image Specifications:</strong>
                    </p>
                    <ul class="text-sm text-blue-700 mt-2 ml-6 list-disc space-y-1">
                        <li>Format: JPEG, PNG, or WebP</li>
                        <li>Recommended size: 1920x1080 pixels (16:9 aspect ratio)</li>
                        <li>Maximum file size: 10MB</li>
                        <li>Image will be automatically optimized and cropped for best display on mobile and desktop</li>
                    </ul>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Image File
                        </label>
                        <input 
                            type="file" 
                            name="homepage_image" 
                            id="homepage_image"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: JPEG, PNG, WebP (Max 10MB)</p>
                    </div>

                    <!-- Image Preview -->
                    <div id="image-preview-container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                        <div class="relative bg-gray-100 rounded-lg overflow-hidden" style="aspect-ratio: 16/9; max-height: 400px;">
                            <img 
                                id="image-preview" 
                                src="" 
                                alt="Image Preview"
                                class="w-full h-full object-cover">
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center"
                        <?php echo !$cloudinary_enabled ? 'disabled' : ''; ?>>
                        <i class="fas fa-upload mr-2"></i>Upload Image
                    </button>
                </form>
            </div>

            <!-- Mobile & Desktop Preview -->
            <?php if ($current_image_url): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-mobile-alt text-purple-600 mr-3"></i>Preview on Different Devices
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Desktop Preview -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Desktop View</h3>
                        <div class="border-4 border-gray-300 rounded-lg overflow-hidden bg-gray-100" style="aspect-ratio: 16/9;">
                            <div class="relative w-full h-full">
                                <img 
                                    src="<?php echo htmlspecialchars($current_image_url); ?>" 
                                    alt="Desktop Preview"
                                    class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center text-white px-4">
                                        <h1 class="text-2xl font-bold mb-2">ManuelCode</h1>
                                        <p class="text-lg">Building Digital Excellence</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Preview -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Mobile View</h3>
                        <div class="border-4 border-gray-300 rounded-lg overflow-hidden bg-gray-100 mx-auto" style="width: 375px; aspect-ratio: 9/16;">
                            <div class="relative w-full h-full">
                                <img 
                                    src="<?php echo htmlspecialchars($current_image_url); ?>" 
                                    alt="Mobile Preview"
                                    class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center text-white px-4">
                                        <h1 class="text-xl font-bold mb-2">ManuelCode</h1>
                                        <p class="text-sm">Building Digital Excellence</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Image preview before upload
document.getElementById('homepage_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            const container = document.getElementById('image-preview-container');
            preview.src = e.target.result;
            container.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('image-preview-container').classList.add('hidden');
    }
});
</script>
</body>
</html>

