<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white shadow-lg rounded-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Privacy Policy</h1>
            
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-600 mb-6">Last updated: <?php echo date('F j, Y'); ?></p>
                
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Information We Collect</h2>
                    <p class="text-gray-700 mb-4">
                        We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support.
                    </p>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Personal Information:</h3>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Name and contact information (email, phone number)</li>
                        <li>Billing and payment information</li>
                        <li>Account credentials</li>
                        <li>Communication preferences</li>
                        <li>Support requests and feedback</li>
                    </ul>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Automatically Collected Information:</h3>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Device information (IP address, browser type, operating system)</li>
                        <li>Usage data (pages visited, time spent, links clicked)</li>
                        <li>Cookies and similar tracking technologies</li>
                        <li>Location data (if permitted by your device)</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. How We Use Your Information</h2>
                    <p class="text-gray-700 mb-4">We use the information we collect to:</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Provide and maintain our services</li>
                        <li>Process transactions and send related information</li>
                        <li>Send technical notices, updates, and support messages</li>
                        <li>Respond to your comments, questions, and requests</li>
                        <li>Improve our services and develop new features</li>
                        <li>Protect against fraud and ensure security</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. Information Sharing</h2>
                    <p class="text-gray-700 mb-4">
                        We do not sell, trade, or otherwise transfer your personal information to third parties except in the following circumstances:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our website and providing services</li>
                        <li><strong>Payment Processors:</strong> Payment information is shared with secure payment processors to complete transactions</li>
                        <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our rights and safety</li>
                        <li><strong>Business Transfers:</strong> In the event of a merger or acquisition, your information may be transferred</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Data Security</h2>
                    <p class="text-gray-700 mb-4">
                        We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Encryption of sensitive data</li>
                        <li>Regular security assessments</li>
                        <li>Access controls and authentication</li>
                        <li>Secure data transmission (HTTPS)</li>
                        <li>Regular backups and disaster recovery</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Cookies and Tracking</h2>
                    <p class="text-gray-700 mb-4">
                        We use cookies and similar technologies to enhance your experience on our website. These technologies help us:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Remember your preferences and settings</li>
                        <li>Analyze website traffic and usage patterns</li>
                        <li>Provide personalized content and advertisements</li>
                        <li>Improve website functionality and performance</li>
                    </ul>
                    <p class="text-gray-700 mb-4">
                        You can control cookie settings through your browser preferences. However, disabling cookies may affect website functionality.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Your Rights and Choices</h2>
                    <p class="text-gray-700 mb-4">You have the right to:</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li><strong>Access:</strong> Request a copy of your personal information</li>
                        <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                        <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                        <li><strong>Portability:</strong> Receive your data in a portable format</li>
                        <li><strong>Opt-out:</strong> Unsubscribe from marketing communications</li>
                        <li><strong>Restriction:</strong> Limit how we process your information</li>
                    </ul>
                    <p class="text-gray-700 mb-4">
                        To exercise these rights, please contact us using the information provided below.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Data Retention</h2>
                    <p class="text-gray-700 mb-4">
                        We retain your personal information for as long as necessary to provide our services and fulfill the purposes outlined in this policy. We may retain certain information for longer periods to comply with legal obligations, resolve disputes, and enforce our agreements.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. International Data Transfers</h2>
                    <p class="text-gray-700 mb-4">
                        Your information may be transferred to and processed in countries other than your own. We ensure that such transfers comply with applicable data protection laws and implement appropriate safeguards to protect your information.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Children's Privacy</h2>
                    <p class="text-gray-700 mb-4">
                        Our services are not intended for children under the age of 13. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Changes to This Policy</h2>
                    <p class="text-gray-700 mb-4">
                        We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the "Last updated" date. Your continued use of our services after such changes constitutes acceptance of the updated policy.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Contact Us</h2>
                    <p class="text-gray-700 mb-4">
                        If you have any questions about this Privacy Policy or our data practices, please contact us:
                    </p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-700"><strong>Email:</strong> privacy@manuelcode.info</p>
                        <p class="text-gray-700"><strong>Phone:</strong> +233 24 806 9639</p>
                        <p class="text-gray-700"><strong>Address:</strong> Ghana</p>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> ManuelCode. All rights reserved.</p>
                <div class="mt-4 space-x-4">
                    <a href="terms.php" class="text-gray-300 hover:text-white">Terms of Service</a>
                    <a href="privacy.php" class="text-gray-300 hover:text-white">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
