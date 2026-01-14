<?php
session_start();
include '../includes/auth_only.php';
include '../includes/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How to Download Purchased Products - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .help-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 4rem;
        }
        .step-box {
            background: linear-gradient(135deg, #e8f4fd 0%, #f0f9ff 100%);
            color: #1e40af;
            border: 2px solid #bfdbfe;
            border-radius: 20px;
            padding: 4rem 3rem;
            margin-bottom: 4rem;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }
        .feature-box {
            background: #fefefe;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .feature-box:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }
        .soft-blue-bg {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        .soft-green-bg {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        .soft-purple-bg {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }
        .soft-orange-bg {
            background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
        }
        .soft-pink-bg {
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        }
        .soft-yellow-bg {
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
        }
        .soft-red-bg {
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
        }
        .soft-cyan-bg {
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .help-content {
                max-width: 100%;
                padding: 0 2rem;
            }
            .step-box {
                padding: 3rem 2rem;
                margin-bottom: 3rem;
            }
            .feature-box {
                padding: 2rem;
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .help-content {
                padding: 0 1rem;
            }
            .step-box {
                padding: 2rem 1.5rem;
                margin-bottom: 2rem;
                border-radius: 16px;
            }
            .feature-box {
                padding: 1.5rem;
                margin-bottom: 1rem;
                border-radius: 12px;
            }
        }
        
        @media (max-width: 640px) {
            .help-content {
                padding: 0 0.75rem;
            }
            .step-box {
                padding: 1.5rem 1rem;
                margin-bottom: 1.5rem;
            }
            .feature-box {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="../dashboard/index.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900">Download Guide</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../auth/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full py-12">
        <div class="help-content">
            <!-- Hero Section -->
            <div class="step-box text-center">
                <i class="fas fa-download text-4xl mb-4"></i>
                <h1 class="text-3xl font-bold mb-2">How to Download Your Purchased Products</h1>
                <p class="text-lg opacity-90">Complete guide to accessing and downloading your digital products</p>
            </div>

            <!-- Quick Steps -->
            <div class="soft-blue-bg rounded-3xl shadow-xl p-10 mb-10 border border-blue-200">
                <h2 class="text-4xl font-bold text-blue-900 mb-8 text-center">Quick Steps</h2>
                <div class="grid lg:grid-cols-3 md:grid-cols-2 gap-8">
                    <div class="text-center p-8 bg-white rounded-2xl shadow-lg border border-blue-100 hover:shadow-xl transition-all duration-300">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-2xl mx-auto mb-6">1</div>
                        <h3 class="font-semibold text-blue-900 text-xl mb-3">Go to Downloads</h3>
                        <p class="text-blue-700 text-lg">Navigate to your dashboard downloads section</p>
                    </div>
                    <div class="text-center p-8 bg-white rounded-2xl shadow-lg border border-green-100 hover:shadow-xl transition-all duration-300">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-2xl mx-auto mb-6">2</div>
                        <h3 class="font-semibold text-green-900 text-xl mb-3">Find Your Product</h3>
                        <p class="text-green-700 text-lg">Locate the product you want to download</p>
                    </div>
                    <div class="text-center p-8 bg-white rounded-2xl shadow-lg border border-purple-100 hover:shadow-xl transition-all duration-300 lg:col-span-1 md:col-span-2">
                        <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-2xl mx-auto mb-6">3</div>
                        <h3 class="font-semibold text-purple-900 text-xl mb-3">Click Download</h3>
                        <p class="text-purple-700 text-lg">Click the download button to get your file</p>
                    </div>
                </div>
            </div>

            <!-- Detailed Instructions -->
            <div class="soft-green-bg rounded-3xl shadow-xl p-10 mb-10 border border-green-200">
                <h2 class="text-4xl font-bold text-green-900 mb-8 text-center">Detailed Instructions</h2>
                
                <div class="space-y-6">
                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>
                            Step 1: Access Your Dashboard
                        </h3>
                        <p class="text-gray-700 mb-3">Log into your ManuelCode account and navigate to your dashboard.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Click on "Dashboard" in the main navigation</li>
                            <li>You'll see an overview of your account and recent purchases</li>
                            <li>Look for the "Downloads" section in the sidebar</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-download text-green-600 mr-2"></i>
                            Step 2: Navigate to Downloads
                        </h3>
                        <p class="text-gray-700 mb-3">Click on "Downloads" in your dashboard sidebar to view all your purchased products.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>All your purchased products will be listed here</li>
                            <li>Products are organized by purchase date (newest first)</li>
                            <li>You can see the product name, purchase date, and download status</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-search text-purple-600 mr-2"></i>
                            Step 3: Find Your Product
                        </h3>
                        <p class="text-gray-700 mb-3">Locate the specific product you want to download from your list.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Use the search function if you have many products</li>
                            <li>Check the product status - it should show "Ready for Download"</li>
                            <li>If it shows "Processing", wait a few minutes and refresh the page</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-download text-red-600 mr-2"></i>
                            Step 4: Download Your Product
                        </h3>
                        <p class="text-gray-700 mb-3">Click the download button to get your digital product.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Click the blue "Download" button next to your product</li>
                            <li>Your browser will start downloading the file</li>
                            <li>Check your browser's download folder for the file</li>
                            <li>Some products may be delivered via Google Drive links</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Common Issues -->
            <div class="soft-orange-bg rounded-3xl shadow-xl p-10 mb-10 border border-orange-200">
                <h2 class="text-4xl font-bold text-orange-900 mb-8 text-center">Common Issues & Solutions</h2>
                
                <div class="grid lg:grid-cols-3 md:grid-cols-2 gap-8">
                    <div class="bg-white rounded-2xl p-8 shadow-lg border border-yellow-200 hover:shadow-xl transition-all duration-300">
                        <h3 class="font-semibold text-yellow-900 mb-4 text-xl">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 text-2xl"></i>
                            Download Button Not Working
                        </h3>
                        <p class="text-yellow-800 mb-4 text-lg">If the download button doesn't respond:</p>
                        <ul class="list-disc list-inside text-yellow-700 space-y-2 text-lg">
                            <li>Check if you're logged into your account</li>
                            <li>Try refreshing the page</li>
                            <li>Clear your browser cache and cookies</li>
                            <li>Try a different browser</li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl p-8 shadow-lg border border-red-200 hover:shadow-xl transition-all duration-300">
                        <h3 class="font-semibold text-red-900 mb-4 text-xl">
                            <i class="fas fa-times-circle text-red-600 mr-3 text-2xl"></i>
                            "Download Not Ready" Message
                        </h3>
                        <p class="text-red-800 mb-4 text-lg">If you see this message:</p>
                        <ul class="list-disc list-inside text-red-700 space-y-2 text-lg">
                            <li>Wait 5-10 minutes and refresh the page</li>
                            <li>Contact support if the issue persists</li>
                            <li>Check your email for download instructions</li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl p-8 shadow-lg border border-blue-200 hover:shadow-xl transition-all duration-300 lg:col-span-1 md:col-span-2">
                        <h3 class="font-semibold text-blue-900 mb-4 text-xl">
                            <i class="fas fa-info-circle text-blue-600 mr-3 text-2xl"></i>
                            Google Drive Links
                        </h3>
                        <p class="text-blue-800 mb-4 text-lg">For products delivered via Google Drive:</p>
                        <ul class="list-disc list-inside text-blue-700 space-y-2 text-lg">
                            <li>Click the Google Drive link provided</li>
                            <li>You may need to request access if it's a private folder</li>
                            <li>Download files individually or use "Download All"</li>
                            <li>Contact support if you can't access the Drive folder</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="soft-cyan-bg rounded-3xl shadow-xl p-10 text-center border border-cyan-200">
                <i class="fas fa-headset text-6xl mb-8 text-cyan-600"></i>
                <h2 class="text-4xl font-bold mb-6 text-cyan-900">Need More Help?</h2>
                <p class="mb-8 text-cyan-800 text-xl">If you're still having trouble downloading your products, our support team is here to help.</p>
                <div class="flex flex-col lg:flex-row gap-6 justify-center">
                    <a href="mailto:support@manuelcode.info" class="bg-white text-cyan-600 px-10 py-4 rounded-2xl font-semibold hover:bg-cyan-50 transition-all duration-300 shadow-lg border border-cyan-200 text-lg hover:shadow-xl">
                        <i class="fas fa-envelope mr-3"></i>Email Support
                    </a>
                    <a href="tel:+233257940791" class="bg-white text-cyan-600 px-10 py-4 rounded-2xl font-semibold hover:bg-cyan-50 transition-all duration-300 shadow-lg border border-cyan-200 text-lg hover:shadow-xl">
                        <i class="fas fa-phone mr-3"></i>Call Support
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; <?php echo date('Y'); ?> ManuelCode. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
