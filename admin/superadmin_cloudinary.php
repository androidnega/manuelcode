<?php
// Super Admin - Cloudinary Management Page
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include '../includes/cloudinary_helper.php';

// Get Cloudinary settings
try {
    $stmt = $pdo->query("SELECT setting_key, value FROM settings WHERE setting_key LIKE 'cloudinary_%'");
    $cloudinary_settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cloudinary_settings[$row['setting_key']] = $row['value'];
    }
} catch (Exception $e) {
    $cloudinary_settings = [];
}

$cloudinaryHelper = new CloudinaryHelper($pdo);
$cloudinary_enabled = $cloudinaryHelper->isEnabled();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloudinary Management - Super Admin</title>
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
                        <i class="fas fa-cloud text-blue-500 mr-3"></i>Cloudinary Media Management
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Manage images and media uploads</p>
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
            <!-- Status Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-3"></i>Cloudinary Status
                </h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700">Status:</span>
                        <span class="<?php echo $cloudinary_enabled ? 'text-green-600' : 'text-red-600'; ?> font-semibold">
                            <?php echo $cloudinary_enabled ? '✓ Enabled' : '✗ Disabled'; ?>
                        </span>
                    </div>
                    <?php if ($cloudinary_enabled): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-700">Cloud Name:</span>
                            <span class="text-gray-600"><?php echo htmlspecialchars($cloudinary_settings['cloudinary_cloud_name'] ?? 'N/A'); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Cloudinary is not configured. Please configure it in <a href="../dashboard/system-settings" class="underline font-semibold">System Settings</a>.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Configuration Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-cog text-gray-600 mr-3"></i>Configuration
                </h2>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        All image uploads (products, projects, etc.) are automatically uploaded to Cloudinary when enabled. 
                        Images are stored in organized folders and optimized automatically.
                    </p>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Folder Structure</label>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div>• Products: <code class="bg-gray-100 px-2 py-1 rounded">products/</code></div>
                                <div>• Gallery: <code class="bg-gray-100 px-2 py-1 rounded">products/gallery/</code></div>
                                <div>• Projects: <code class="bg-gray-100 px-2 py-1 rounded">projects/</code></div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Features</label>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div>✓ Automatic image optimization</div>
                                <div>✓ CDN delivery for fast loading</div>
                                <div>✓ Responsive image transformations</div>
                                <div>✓ Secure cloud storage</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="../dashboard/system-settings" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center">
                        <i class="fas fa-cog mr-2"></i>Configure Cloudinary Settings
                    </a>
                </div>
            </div>

            <!-- Upload Test Card -->
            <?php if ($cloudinary_enabled): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-upload text-green-600 mr-3"></i>Test Upload
                </h2>
                <form id="testUploadForm" class="space-y-4" enctype="multipart/form-data">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Test Image</label>
                        <input type="file" id="test_image" name="test_image" accept="image/*" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Select an image to test Cloudinary upload</p>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center">
                        <i class="fas fa-upload mr-2"></i>Test Upload
                    </button>
                </form>
                <div id="upload_result" class="mt-4"></div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
<?php if ($cloudinary_enabled): ?>
document.getElementById('testUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const resultDiv = document.getElementById('upload_result');
    const fileInput = document.getElementById('test_image');
    
    if (!fileInput.files[0]) {
        resultDiv.innerHTML = '<div class="text-red-600 p-3 bg-red-50 rounded-lg">Please select an image file.</div>';
        return;
    }
    
    resultDiv.innerHTML = '<div class="text-blue-600 p-3 bg-blue-50 rounded-lg"><i class="fas fa-spinner fa-spin mr-2"></i>Uploading...</div>';
    
    const formData = new FormData();
    formData.append('test_image', fileInput.files[0]);
    formData.append('action', 'test_cloudinary_upload');
    
    try {
        // Try simplified version first, fallback to original
        const uploadUrl = window.location.pathname.includes('/dashboard/') 
            ? '../admin/test_cloudinary_upload_simple.php' 
            : 'admin/test_cloudinary_upload_simple.php';
        
        const response = await fetch(uploadUrl, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(`Unexpected response format. Expected JSON but got: ${contentType}. Response: ${text.substring(0, 200)}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-green-800 font-semibold mb-2">✓ Upload Successful!</div>
                    <div class="text-sm text-gray-700 space-y-2">
                        <div><strong>URL:</strong> <a href="${data.url}" target="_blank" class="text-blue-600 underline">${data.url}</a></div>
                        <div><strong>Public ID:</strong> <code class="bg-gray-100 px-2 py-1 rounded">${data.public_id}</code></div>
                        ${data.width ? `<div><strong>Dimensions:</strong> ${data.width} x ${data.height}px</div>` : ''}
                        ${data.bytes ? `<div><strong>Size:</strong> ${(data.bytes / 1024).toFixed(2)} KB</div>` : ''}
                    </div>
                    ${data.url ? `<div class="mt-3"><img src="${data.url}" alt="Uploaded" class="max-w-full h-auto rounded-lg border border-gray-200"></div>` : ''}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `<div class="text-red-600 p-3 bg-red-50 rounded-lg">Error: ${data.error || 'Upload failed'}</div>`;
        }
    } catch (error) {
        resultDiv.innerHTML = `<div class="text-red-600 p-3 bg-red-50 rounded-lg">Network error: ${error.message}</div>`;
    }
});
<?php endif; ?>
</script>
</body>
</html>


