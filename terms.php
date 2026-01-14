<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white shadow-lg rounded-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Terms of Service</h1>
            
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-600 mb-6">Last updated: <?php echo date('F j, Y'); ?></p>
                
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Acceptance of Terms</h2>
                    <p class="text-gray-700 mb-4">
                        By accessing and using ManuelCode.info ("the Website"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Description of Service</h2>
                    <p class="text-gray-700 mb-4">
                        ManuelCode provides web development services, digital products, and technical solutions. Our services include but are not limited to:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Web development and design services</li>
                        <li>Digital product sales</li>
                        <li>Technical consulting</li>
                        <li>Project management services</li>
                        <li>Support and maintenance services</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. User Accounts</h2>
                    <p class="text-gray-700 mb-4">
                        To access certain features of our service, you may be required to create an account. You are responsible for:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Maintaining the confidentiality of your account information</li>
                        <li>All activities that occur under your account</li>
                        <li>Providing accurate and complete information</li>
                        <li>Notifying us immediately of any unauthorized use</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Payment Terms</h2>
                    <p class="text-gray-700 mb-4">
                        All payments are processed securely through our payment partners. By making a purchase, you agree to:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Pay the full amount specified for the service or product</li>
                        <li>Provide accurate billing information</li>
                        <li>Authorize us to charge your payment method</li>
                        <li>Understand that all sales are final unless otherwise specified</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Intellectual Property</h2>
                    <p class="text-gray-700 mb-4">
                        All content on this website, including but not limited to text, graphics, logos, images, and software, is the property of ManuelCode and is protected by copyright laws.
                    </p>
                    <p class="text-gray-700 mb-4">
                        Digital products purchased from our store are licensed for your personal or commercial use as specified in the product description. You may not redistribute, resell, or modify our products without explicit permission.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Refund Policy</h2>
                    <p class="text-gray-700 mb-4">
                        We offer refunds under the following conditions:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 ml-4">
                        <li>Technical issues preventing product delivery</li>
                        <li>Product not as described</li>
                        <li>Duplicate purchases</li>
                        <li>Service cancellation within 24 hours of booking</li>
                    </ul>
                    <p class="text-gray-700 mb-4">
                        Refund requests must be submitted within 7 days of purchase. Contact our support team for assistance.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Limitation of Liability</h2>
                    <p class="text-gray-700 mb-4">
                        ManuelCode shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or use, incurred by you or any third party.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Privacy</h2>
                    <p class="text-gray-700 mb-4">
                        Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the Website, to understand our practices.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Modifications</h2>
                    <p class="text-gray-700 mb-4">
                        We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of the Website constitutes acceptance of the modified terms.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Contact Information</h2>
                    <p class="text-gray-700 mb-4">
                        If you have any questions about these Terms of Service, please contact us at:
                    </p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-700"><strong>Email:</strong> support@manuelcode.info</p>
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
