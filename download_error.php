<?php
// This page is included by download.php when there's a download error
$error_type = $error_type ?? 'unknown_error';
$product_title = $product_title ?? 'Product';
$user_id = $_SESSION['user_id'] ?? null;

// Handle error parameter from URL if set
if (isset($_GET['error'])) {
    $error_type = $_GET['error'];
}

if (isset($_GET['product'])) {
    $product_title = urldecode($_GET['product']);
}

// Set error messages based on error type
$error_messages = [
    'download_not_ready' => 'The download link for this product is not ready yet. Our team is working to make it available as soon as possible.',
    'no_download_available' => 'This product does not have a download file available at the moment. Please contact support for assistance.',
    'invalid_download' => 'The download link is invalid or has expired. Please try again or contact support.',
    'unknown_error' => 'An unexpected error occurred while processing your download. Please try again or contact support.'
];

$error_message = $error_messages[$error_type] ?? $error_messages['unknown_error'];
$error_icon = 'fa-clock';
$error_color = 'yellow';

// Set specific styling for different error types
if ($error_type === 'no_download_available') {
    $error_icon = 'fa-exclamation-triangle';
    $error_color = 'red';
} elseif ($error_type === 'invalid_download') {
    $error_icon = 'fa-times-circle';
    $error_color = 'red';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Error - <?php echo htmlspecialchars($product_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/favi/login-favicon.svg">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <a href="index.php" class="inline-block">
                    <img src="assets/favi/favicon.png" alt="ManuelCode" class="h-12 mx-auto mb-4">
                </a>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    <?php if ($error_type === 'download_not_ready'): ?>
                        Download Not Ready
                    <?php else: ?>
                        Download Error
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600">We're sorry, but there was an issue with your download.</p>
            </div>

            <!-- Error Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-<?php echo $error_color; ?>-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas <?php echo $error_icon; ?> text-<?php echo $error_color; ?>-600 text-2xl"></i>
                    </div>
                    
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($product_title); ?>
                    </h2>
                    
                    <p class="text-gray-600 mb-4">
                        <?php echo $error_message; ?>
                    </p>
                    
                    <?php if ($error_type === 'download_not_ready'): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-bell text-blue-600 mt-1 mr-3"></i>
                            <div class="text-left">
                                <p class="text-sm font-medium text-blue-900 mb-1">We'll notify you when it's ready!</p>
                                <p class="text-sm text-blue-700">
                                    <?php if ($user_id): ?>
                                        You'll receive a notification in your dashboard and email when the download becomes available.
                                    <?php else: ?>
                                        Check back later or contact support for updates.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <?php if ($user_id): ?>
                    <a href="dashboard/" 
                       class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Go to Dashboard
                    </a>
                    
                    <a href="dashboard/my-purchases" 
                       class="w-full bg-gray-600 text-white py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-shopping-bag mr-2"></i>
                        View My Purchases
                    </a>
                <?php else: ?>
                    <a href="store.php" 
                       class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-store mr-2"></i>
                        Browse Store
                    </a>
                <?php endif; ?>
                
                <a href="contact.php" 
                   class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-headset mr-2"></i>
                    Contact Support
                </a>
                
                <a href="index.php" 
                   class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center">
                    <i class="fas fa-home mr-2"></i>
                    Back to Home
                </a>
            </div>

            <!-- Additional Info -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 mb-2">Need immediate assistance?</p>
                <div class="flex items-center justify-center space-x-4 text-sm">
                    <a href="mailto:support@manuelcode.info" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-envelope mr-1"></i>Email Support
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="tel:+233000000000" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-phone mr-1"></i>Call Us
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-refresh script for logged-in users -->
    <?php if ($user_id && $error_type === 'download_not_ready'): ?>
    <script>
        // Check for updates every 30 seconds
        setInterval(function() {
            fetch('check_download_status.php?product_id=<?php echo $product_id ?? 0; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        // Show success message and redirect
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                        notification.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span>Download is now ready!</span>
                            </div>
                        `;
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            window.location.href = 'download.php?product_id=<?php echo $product_id ?? 0; ?>';
                        }, 2000);
                    }
                })
                .catch(error => console.log('Status check failed:', error));
        }, 30000);
    </script>
    <?php endif; ?>
</body>
</html>
