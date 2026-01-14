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
    <title>Payment Issues and Solutions - ManuelCode</title>
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 20px;
            padding: 4rem 3rem;
            margin-bottom: 4rem;
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.15);
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
                    <h1 class="text-xl font-semibold text-gray-900">Payment Issues</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="/auth/logout.php" class="text-red-600 hover:text-red-800">
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
                <i class="fas fa-credit-card text-4xl mb-4"></i>
                <h1 class="text-3xl font-bold mb-2">Payment Issues and Solutions</h1>
                <p class="text-lg opacity-90">Common payment problems and how to resolve them</p>
            </div>

            <!-- Common Payment Issues -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Common Payment Issues</h2>
                
                <div class="space-y-4">
                    <div class="border-l-4 border-red-400 bg-red-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                            Payment Declined
                        </h3>
                        <p class="text-gray-700 mb-2">Your payment was declined by your bank or payment provider.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Check if your card has sufficient funds</li>
                            <li>Verify your card details are correct</li>
                            <li>Contact your bank to authorize the transaction</li>
                            <li>Try using a different payment method</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-clock text-yellow-600 mr-2"></i>
                            Payment Pending
                        </h3>
                        <p class="text-gray-700 mb-2">Your payment is being processed and may take some time.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Wait 5-10 minutes for processing</li>
                            <li>Check your email for payment confirmation</li>
                            <li>Refresh the page to check status</li>
                            <li>Contact support if pending for more than 30 minutes</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-blue-400 bg-blue-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>
                            Mobile Money Issues
                        </h3>
                        <p class="text-gray-700 mb-2">Problems with mobile money payments (MTN, Vodafone, AirtelTigo).</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Ensure you have sufficient mobile money balance</li>
                            <li>Check if your mobile money account is active</li>
                            <li>Verify the phone number is correct</li>
                            <li>Try again after a few minutes</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-400 bg-green-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                            Security Verification
                        </h3>
                        <p class="text-gray-700 mb-2">Additional security checks required for your payment.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Complete any 3D Secure verification</li>
                            <li>Enter SMS verification codes if prompted</li>
                            <li>Check your email for verification links</li>
                            <li>Contact your bank if verification fails</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Troubleshooting Steps -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Troubleshooting Steps</h2>
                
                <div class="space-y-6">
                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-refresh text-blue-600 mr-2"></i>
                            Step 1: Refresh and Retry
                        </h3>
                        <p class="text-gray-700 mb-3">Simple solutions that often resolve payment issues.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Refresh your browser page</li>
                            <li>Clear browser cache and cookies</li>
                            <li>Try using a different browser</li>
                            <li>Wait 5 minutes and try again</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-credit-card text-green-600 mr-2"></i>
                            Step 2: Check Payment Method
                        </h3>
                        <p class="text-gray-700 mb-3">Verify your payment method is working correctly.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Ensure your card is not expired</li>
                            <li>Check if your card is blocked or frozen</li>
                            <li>Verify your billing address matches</li>
                            <li>Try a different card or payment method</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-phone text-purple-600 mr-2"></i>
                            Step 3: Contact Your Bank
                        </h3>
                        <p class="text-gray-700 mb-3">If the issue persists, contact your financial institution.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Call your bank's customer service</li>
                            <li>Ask about any transaction blocks</li>
                            <li>Request authorization for online payments</li>
                            <li>Verify your card supports online transactions</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-headset text-orange-600 mr-2"></i>
                            Step 4: Contact Our Support
                        </h3>
                        <p class="text-gray-700 mb-3">If all else fails, our support team is here to help.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Provide your transaction reference number</li>
                            <li>Include any error messages you received</li>
                            <li>Tell us what payment method you used</li>
                            <li>Share screenshots if possible</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Supported Payment Methods</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-credit-card text-blue-600 mr-2"></i>
                            Credit/Debit Cards
                        </h3>
                        <p class="text-sm text-gray-600 mb-2">Visa, Mastercard, and other major cards</p>
                        <ul class="text-xs text-gray-500">
                            <li>• Secure 3D Secure verification</li>
                            <li>• Instant processing</li>
                            <li>• International cards accepted</li>
                        </ul>
                    </div>
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-mobile-alt text-green-600 mr-2"></i>
                            Mobile Money
                        </h3>
                        <p class="text-sm text-gray-600 mb-2">MTN, Vodafone, AirtelTigo</p>
                        <ul class="text-xs text-gray-500">
                            <li>• Quick and convenient</li>
                            <li>• No card required</li>
                            <li>• Instant confirmation</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Security Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Payment Security</h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <i class="fas fa-lock text-blue-600 text-2xl mb-2"></i>
                        <h3 class="font-semibold text-gray-900">SSL Encryption</h3>
                        <p class="text-sm text-gray-600">All payments are encrypted</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <i class="fas fa-shield-alt text-green-600 text-2xl mb-2"></i>
                        <h3 class="font-semibold text-gray-900">PCI Compliant</h3>
                        <p class="text-sm text-gray-600">Secure payment processing</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <i class="fas fa-user-shield text-purple-600 text-2xl mb-2"></i>
                        <h3 class="font-semibold text-gray-900">Fraud Protection</h3>
                        <p class="text-sm text-gray-600">Advanced security measures</p>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg p-6 text-white text-center">
                <i class="fas fa-headset text-3xl mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">Still Having Payment Issues?</h2>
                <p class="mb-4">Our support team is here to help resolve any payment problems.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="../dashboard/support.php" class="bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        <i class="fas fa-ticket-alt mr-2"></i>Create Support Ticket
                    </a>
                    <a href="tel:+233257940791" class="bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        <i class="fas fa-phone mr-2"></i>Call Support
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
