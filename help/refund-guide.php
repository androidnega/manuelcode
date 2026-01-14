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
    <title>How to Request a Refund - ManuelCode</title>
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
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
            color: #be185d;
            border: 2px solid #f9a8d4;
            border-radius: 20px;
            padding: 4rem 3rem;
            margin-bottom: 4rem;
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.15);
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
                    <h1 class="text-xl font-semibold text-gray-900">Refund Guide</h1>
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
                <i class="fas fa-undo-alt text-4xl mb-4"></i>
                <h1 class="text-3xl font-bold mb-2">How to Request a Refund</h1>
                <p class="text-lg opacity-90">Complete guide to requesting refunds for your purchases</p>
            </div>

            <!-- Refund Policy Overview -->
            <div class="soft-green-bg rounded-3xl shadow-xl p-10 mb-10 border border-green-200">
                <h2 class="text-4xl font-bold text-green-900 mb-8 text-center">Our Refund Policy</h2>
                <div class="grid lg:grid-cols-2 gap-10">
                    <div class="bg-white p-8 rounded-2xl shadow-lg border border-green-200 hover:shadow-xl transition-all duration-300">
                        <h3 class="font-semibold text-green-800 mb-6 text-xl">
                            <i class="fas fa-check-circle text-green-600 mr-3 text-2xl"></i>
                            Eligible for Refund
                        </h3>
                        <ul class="text-green-700 space-y-3 text-lg">
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3 text-xl"></i>Technical issues preventing download</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3 text-xl"></i>Product not as described</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3 text-xl"></i>Duplicate purchases</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3 text-xl"></i>Service cancellation within 24 hours</li>
                        </ul>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-lg border border-red-200 hover:shadow-xl transition-all duration-300">
                        <h3 class="font-semibold text-red-800 mb-6 text-xl">
                            <i class="fas fa-times-circle text-red-600 mr-3 text-2xl"></i>
                            Not Eligible for Refund
                        </h3>
                        <ul class="text-red-700 space-y-3 text-lg">
                            <li class="flex items-center"><i class="fas fa-times text-red-500 mr-3 text-xl"></i>Change of mind after download</li>
                            <li class="flex items-center"><i class="fas fa-times text-red-500 mr-3 text-xl"></i>Product working as described</li>
                            <li class="flex items-center"><i class="fas fa-times text-red-500 mr-3 text-xl"></i>Requests after 7 days</li>
                            <li class="flex items-center"><i class="fas fa-times text-red-500 mr-3 text-xl"></i>Custom development work</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Step-by-Step Process -->
            <div class="soft-blue-bg rounded-3xl shadow-xl p-10 mb-10 border border-blue-200">
                <h2 class="text-4xl font-bold text-blue-900 mb-8 text-center">How to Request a Refund</h2>
                
                <div class="space-y-6">
                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-receipt text-blue-600 mr-2"></i>
                            Step 1: Locate Your Purchase
                        </h3>
                        <p class="text-gray-700 mb-3">Find the purchase you want to request a refund for.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Go to your dashboard and click "My Purchases"</li>
                            <li>Find the specific product in your purchase history</li>
                            <li>Make sure the purchase is within the 7-day refund window</li>
                            <li>Note down the purchase ID and date</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-headset text-green-600 mr-2"></i>
                            Step 2: Contact Support
                        </h3>
                        <p class="text-gray-700 mb-3">Reach out to our support team with your refund request.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Go to the "Support" section in your dashboard</li>
                            <li>Click "Create Ticket" to submit a new support request</li>
                            <li>Select "Refund Request" as the category</li>
                            <li>Provide detailed information about your request</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-file-alt text-purple-600 mr-2"></i>
                            Step 3: Provide Required Information
                        </h3>
                        <p class="text-gray-700 mb-3">Include all necessary details in your refund request.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Purchase ID or transaction reference</li>
                            <li>Date of purchase</li>
                            <li>Product name and description</li>
                            <li>Reason for refund request</li>
                            <li>Any supporting evidence (screenshots, error messages)</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-clock text-orange-600 mr-2"></i>
                            Step 4: Wait for Review
                        </h3>
                        <p class="text-gray-700 mb-3">Our team will review your request and respond within 24-48 hours.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>We'll investigate your claim thoroughly</li>
                            <li>You may be asked for additional information</li>
                            <li>We'll check if the refund criteria are met</li>
                            <li>You'll receive an email with our decision</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Refund Request Template -->
            <div class="soft-purple-bg rounded-3xl shadow-xl p-10 mb-10 border border-purple-200">
                <h2 class="text-4xl font-bold text-purple-900 mb-8 text-center">Refund Request Template</h2>
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-purple-200">
                    <p class="text-purple-700 mb-6 text-xl">Use this template when submitting your refund request:</p>
                    <div class="bg-purple-50 p-8 rounded-2xl border-2 border-purple-200">
                        <div class="space-y-4 text-lg">
                            <p class="text-purple-900"><strong>Subject:</strong> Refund Request - [Product Name]</p>
                            <p class="text-purple-900"><strong>Purchase ID:</strong> [Your Purchase ID]</p>
                            <p class="text-purple-900"><strong>Purchase Date:</strong> [Date of Purchase]</p>
                            <p class="text-purple-900"><strong>Product:</strong> [Product Name]</p>
                            <p class="text-purple-900"><strong>Amount Paid:</strong> [Amount]</p>
                            <p class="text-purple-900"><strong>Reason for Refund:</strong> [Detailed explanation]</p>
                            <p class="text-purple-900"><strong>Supporting Evidence:</strong> [Any screenshots or error messages]</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Processing Time -->
            <div class="soft-yellow-bg rounded-3xl shadow-xl p-10 mb-10 border border-yellow-200">
                <h2 class="text-4xl font-bold text-yellow-900 mb-8 text-center">Refund Processing Time</h2>
                <div class="grid lg:grid-cols-3 md:grid-cols-2 gap-8">
                    <div class="text-center p-8 bg-white rounded-2xl shadow-lg border border-blue-200 hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-search text-blue-600 text-4xl mb-6"></i>
                        <h3 class="font-semibold text-blue-900 text-xl mb-3">Review Period</h3>
                        <p class="text-blue-700 font-medium text-lg">24-48 hours</p>
                    </div>
                    <div class="text-center p-8 bg-white rounded-2xl shadow-lg border border-green-200 hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-check text-green-600 text-4xl mb-6"></i>
                        <h3 class="font-semibold text-green-900 text-xl mb-3">Approval</h3>
                        <p class="text-green-700 font-medium text-lg">Immediate if approved</p>
                    </div>
                    <div class="text-center p-8 bg-white rounded-2xl shadow-lg border border-purple-200 hover:shadow-xl transition-all duration-300 lg:col-span-1 md:col-span-2">
                        <i class="fas fa-credit-card text-purple-600 text-4xl mb-6"></i>
                        <h3 class="font-semibold text-purple-900 text-xl mb-3">Payment Processing</h3>
                        <p class="text-purple-700 font-medium text-lg">3-5 business days</p>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="bg-gradient-to-r from-red-500 to-pink-600 rounded-3xl p-10 text-white text-center shadow-xl">
                <i class="fas fa-headset text-5xl mb-6"></i>
                <h2 class="text-4xl font-bold mb-4">Ready to Request a Refund?</h2>
                <p class="mb-8 text-xl">Contact our support team to start your refund process.</p>
                <div class="flex flex-col lg:flex-row gap-6 justify-center">
                    <a href="../dashboard/support.php" class="bg-white text-red-600 px-10 py-4 rounded-2xl font-semibold hover:bg-gray-100 transition-all duration-300 shadow-lg text-lg">
                        <i class="fas fa-ticket-alt mr-3"></i>Create Support Ticket
                    </a>
                    <a href="mailto:support@manuelcode.info" class="bg-white text-red-600 px-10 py-4 rounded-2xl font-semibold hover:bg-gray-100 transition-all duration-300 shadow-lg text-lg">
                        <i class="fas fa-envelope mr-3"></i>Email Support
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
