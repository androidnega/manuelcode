<?php
session_start();
include 'includes/db.php';

$reference = $_GET['ref'] ?? '';
$submission = null;
$error = false;

if (!empty($reference)) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM submissions 
            WHERE reference = ? AND status = 'paid'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$reference]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            $error = true;
        }
    } catch (Exception $e) {
        $error = true;
        error_log("Error fetching submission: " . $e->getMessage());
    }
} else {
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Successful - ManuelCode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="index.php" class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Home
                        </a>
                    </div>
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-check-circle mr-2 text-green-600"></i>Submission Status
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <?php if ($error || !$submission): ?>
                <!-- Error State -->
                <div class="bg-white shadow rounded-lg p-8 text-center">
                    <div class="mb-6">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Submission Not Found</h2>
                        <p class="text-gray-600 mb-6">We couldn't find your submission. Please check your reference number or contact support.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <a href="submission.php" 
                           class="inline-block bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-upload mr-2"></i>Submit New Project
                        </a>
                        <br>
                        <a href="index.php" 
                           class="inline-block bg-gray-500 text-white px-6 py-3 rounded-md hover:bg-gray-600 transition-colors">
                            <i class="fas fa-home mr-2"></i>Go to Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Success State -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-8 text-white text-center">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-6xl mb-4"></i>
                            <h1 class="text-3xl font-bold mb-2">Submission Successful!</h1>
                            <p class="text-green-100 text-lg">Your project report document has been submitted and payment confirmed.</p>
                        </div>
                    </div>

                    <!-- Essential Information Only -->
                    <div class="p-6">
                        <!-- Key Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">
                                    <i class="fas fa-user mr-2 text-blue-600"></i>Student Details
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div><span class="font-medium">Name:</span> <?php echo htmlspecialchars($submission['name']); ?></div>
                                    <div><span class="font-medium">Index Number:</span> <?php echo htmlspecialchars($submission['index_number']); ?></div>
                                    <div><span class="font-medium">File:</span> <?php echo htmlspecialchars($submission['file_name']); ?></div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">
                                    <i class="fas fa-credit-card mr-2 text-green-600"></i>Payment Details
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div><span class="font-medium">Amount:</span> <span class="font-bold text-green-600">GHS <?php echo number_format($submission['amount'], 2); ?></span></div>
                                    <div><span class="font-medium">Status:</span> <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i>Paid</span></div>
                                    <div><span class="font-medium">Reference:</span> <span class="font-mono text-xs"><?php echo htmlspecialchars($submission['reference']); ?></span></div>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Confirmation -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <i class="fas fa-sms text-green-600 mr-3 mt-1"></i>
                                <div>
                                    <h4 class="font-medium text-green-900 mb-1">SMS Confirmation Sent</h4>
                                    <p class="text-sm text-green-700">
                                        A confirmation SMS has been sent to your phone number for your project report document submission.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Reference Number -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-center">
                            <p class="text-sm text-blue-700 mb-2">Keep this reference number for your records:</p>
                            <div class="bg-white inline-block px-4 py-2 rounded-md border border-blue-300">
                                <code class="text-lg font-mono text-blue-900"><?php echo htmlspecialchars($submission['reference']); ?></code>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="submission.php" 
                               class="bg-blue-600 text-white px-8 py-3 rounded-md hover:bg-blue-700 transition-colors text-center">
                                <i class="fas fa-plus mr-2"></i>Submit Another Project
                            </a>
                            <a href="index.php" 
                               class="bg-gray-500 text-white px-8 py-3 rounded-md hover:bg-gray-600 transition-colors text-center">
                                <i class="fas fa-home mr-2"></i>Return to Home
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
