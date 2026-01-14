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
    <title>Account Security and Privacy - ManuelCode</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 4rem 3rem;
            margin-bottom: 4rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
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
                    <h1 class="text-xl font-semibold text-gray-900">Account Security</h1>
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
                <i class="fas fa-shield-alt text-4xl mb-4"></i>
                <h1 class="text-3xl font-bold mb-2">Account Security and Privacy</h1>
                <p class="text-lg opacity-90">Protect your account and understand how we handle your data</p>
            </div>

            <!-- Security Overview -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">How We Protect Your Account</h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <i class="fas fa-lock text-blue-600 text-2xl mb-2"></i>
                        <h3 class="font-semibold text-gray-900">Encryption</h3>
                        <p class="text-sm text-gray-600">All data is encrypted in transit and at rest</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <i class="fas fa-mobile-alt text-green-600 text-2xl mb-2"></i>
                        <h3 class="font-semibold text-gray-900">OTP Verification</h3>
                        <p class="text-sm text-gray-600">Secure phone-based authentication</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <i class="fas fa-eye-slash text-purple-600 text-2xl mb-2"></i>
                        <h3 class="font-semibold text-gray-900">Privacy First</h3>
                        <p class="text-sm text-gray-600">We never share your personal data</p>
                    </div>
                </div>
            </div>

            <!-- Account Security Tips -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Account Security Best Practices</h2>
                
                <div class="space-y-6">
                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-key text-blue-600 mr-2"></i>
                            Strong Authentication
                        </h3>
                        <p class="text-gray-700 mb-3">Keep your account secure with these authentication tips.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Use a unique phone number for OTP verification</li>
                            <li>Never share your OTP codes with anyone</li>
                            <li>Log out from shared or public computers</li>
                            <li>Keep your phone number updated in your account</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-user-shield text-green-600 mr-2"></i>
                            Password Security
                        </h3>
                        <p class="text-gray-700 mb-3">Even though we use OTP, follow these general security practices.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Use strong, unique passwords for other accounts</li>
                            <li>Enable two-factor authentication where available</li>
                            <li>Never reuse passwords across different services</li>
                            <li>Use a password manager for better security</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-desktop text-purple-600 mr-2"></i>
                            Device Security
                        </h3>
                        <p class="text-gray-700 mb-3">Protect your devices to keep your account safe.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Keep your devices updated with latest security patches</li>
                            <li>Use antivirus software on your computers</li>
                            <li>Lock your phone with a strong PIN or biometric</li>
                            <li>Be careful when using public Wi-Fi networks</li>
                        </ul>
                    </div>

                    <div class="feature-box">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <i class="fas fa-exclamation-triangle text-orange-600 mr-2"></i>
                            Phishing Awareness
                        </h3>
                        <p class="text-gray-700 mb-3">Stay vigilant against phishing attempts.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Never click on suspicious links in emails or messages</li>
                            <li>Verify the sender's email address before responding</li>
                            <li>ManuelCode will never ask for your OTP via email</li>
                            <li>Report suspicious activity to our support team</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Privacy Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Privacy Rights</h2>
                
                <div class="space-y-4">
                    <div class="border-l-4 border-blue-400 bg-blue-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-eye text-blue-600 mr-2"></i>
                            Data Access
                        </h3>
                        <p class="text-gray-700 mb-2">You have the right to access your personal data.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>View all data we have about you</li>
                            <li>Download your data in a portable format</li>
                            <li>Request information about how we use your data</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-400 bg-green-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-edit text-green-600 mr-2"></i>
                            Data Correction
                        </h3>
                        <p class="text-gray-700 mb-2">You can update or correct your information.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Update your contact information</li>
                            <li>Correct any inaccurate data</li>
                            <li>Change your account preferences</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-red-400 bg-red-50 p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-trash text-red-600 mr-2"></i>
                            Data Deletion
                        </h3>
                        <p class="text-gray-700 mb-2">You can request deletion of your data.</p>
                        <ul class="list-disc list-inside text-gray-600 ml-4">
                            <li>Request complete account deletion</li>
                            <li>Remove specific data categories</li>
                            <li>Note: Some data may be retained for legal purposes</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Data We Collect -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Data We Collect and Use</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-user text-blue-600 mr-2"></i>
                            Personal Information
                        </h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Name and contact details</li>
                            <li>• Phone number for OTP verification</li>
                            <li>• Email address for communications</li>
                            <li>• Payment information (processed securely)</li>
                        </ul>
                    </div>
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-chart-line text-green-600 mr-2"></i>
                            Usage Data
                        </h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Purchase history and preferences</li>
                            <li>• Website usage patterns</li>
                            <li>• Device and browser information</li>
                            <li>• Support interactions</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Security Measures -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Security Measures</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="fas fa-lock text-green-600 mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900">SSL Encryption</h4>
                                <p class="text-sm text-gray-600">All data transmission is encrypted using industry-standard SSL/TLS protocols.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-database text-blue-600 mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900">Secure Storage</h4>
                                <p class="text-sm text-gray-600">Your data is stored in secure, encrypted databases with access controls.</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="fas fa-shield-alt text-purple-600 mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900">Access Controls</h4>
                                <p class="text-sm text-gray-600">Strict access controls ensure only authorized personnel can access your data.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-sync text-orange-600 mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900">Regular Audits</h4>
                                <p class="text-sm text-gray-600">We regularly audit our security measures and update them as needed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg p-6 text-white text-center">
                <i class="fas fa-headset text-3xl mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">Security Concerns?</h2>
                <p class="mb-4">If you notice any suspicious activity or have security concerns, contact us immediately.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="../dashboard/support.php" class="bg-white text-purple-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        <i class="fas fa-ticket-alt mr-2"></i>Report Security Issue
                    </a>
                    <a href="mailto:security@manuelcode.info" class="bg-white text-purple-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        <i class="fas fa-envelope mr-2"></i>Email Security Team
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
