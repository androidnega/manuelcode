<?php
session_start();
include 'includes/db.php';
include 'includes/util.php';
include 'includes/otp_helper.php';
include 'includes/meta_helper.php';
include 'config/payment_config.php';
include 'config/sms_config.php';

// Set page-specific meta data
setQuickMeta(
    'Submit Project Report | Student Document Submission - ManuelCode',
    'Submit your project report document securely with payment verification. Easy 3-step submission process for students with SMS confirmation and payment tracking.',
    'assets/favi/favicon.png',
    'project report submission, student documents, secure upload, payment verification, document submission'
);

// Initialize variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error_message = '';
$success_message = '';

// Check if submissions are enabled
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'submissions_enabled'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $submissions_enabled = ($result && $result['value'] === 'enabled') ? true : false;
} catch (Exception $e) {
    $submissions_enabled = true; // Default to enabled if setting doesn't exist
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if submissions are enabled before processing any form data
    if (!$submissions_enabled) {
        $error_message = 'Submissions are currently disabled. Please try again later.';
    } elseif (isset($_POST['step1'])) {
        // Step 1: Validate student details
        $name = trim(strtoupper($_POST['name'] ?? '')); // Auto capitalize names
        $index_number = trim($_POST['index_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        
        // Validation checks
        if (empty($name) || empty($index_number) || empty($phone) || empty($email)) {
            $error_message = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (!validate_phone_number($phone)) {
            $error_message = 'Please enter a valid phone number.';
        } elseif (strlen($index_number) < 12 || strlen($index_number) > 13) {
            $error_message = 'Index number must be between 12 and 13 characters long.';
        } else {
            // Check for duplicate submissions before payment
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE index_number = ? AND status IN ('pending', 'paid')");
                $stmt->execute([$index_number]);
                $existing_count = $stmt->fetchColumn();
                
                if ($existing_count > 0) {
                    $error_message = 'A submission with this index number already exists. Please contact support if this is an error.';
                } else {
                    // Store in session and proceed to step 2
                    $_SESSION['submission_data'] = [
                        'name' => $name,
                        'index_number' => $index_number,
                        'phone' => normalize_phone_number($phone),
                        'email' => $email
                    ];
                    header('Location: submission.php?step=2');
                    exit;
                }
            } catch (Exception $e) {
                error_log("Error checking duplicate submissions: " . $e->getMessage());
                $error_message = 'An error occurred. Please try again.';
            }
        }
    } elseif (isset($_POST['step2'])) {
        // Step 2: Handle file upload
        if (!isset($_SESSION['submission_data'])) {
            header('Location: submission.php?step=1');
            exit;
        }
        
        $upload_dir = 'uploads/projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'Please select a file to upload.';
        } else {
            $file = $_FILES['project_file'];
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_types = ['pdf', 'doc', 'docx'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Only PDF and Word documents are allowed.';
            } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                $error_message = 'File size must be less than 10MB.';
            } else {
                // Generate unique filename
                $unique_filename = uniqid() . '_' . time() . '.' . $file_type;
                $file_path = $upload_dir . $unique_filename;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['submission_data']['file_path'] = $file_path;
                    $_SESSION['submission_data']['file_name'] = $file['name'];
                    $_SESSION['submission_data']['file_size'] = $file['size'];
                    $_SESSION['submission_data']['file_type'] = $file_type;
                    
                    // Proceed to step 3 (payment)
                    header('Location: submission.php?step=3');
                    exit;
                } else {
                    $error_message = 'Failed to upload file. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['step3'])) {
        // Step 3: Process payment
        if (!isset($_SESSION['submission_data'])) {
            header('Location: submission.php?step=1');
            exit;
        }
        
        // Get submission price from settings
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
            $stmt->execute(['submission_price']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $amount = $result ? floatval($result['value']) : 0.01;
            
            // Debug logging
            error_log("Submission payment - Amount from settings: " . $amount);
            error_log("Submission payment - Amount type: " . gettype($amount));
            error_log("Submission payment - Amount in kobo: " . intval($amount * 100));
            
        } catch (Exception $e) {
            $amount = 0.01; // Default fallback
            error_log("Error getting submission price: " . $e->getMessage());
        }
        
        // Validate amount
        if ($amount <= 0) {
            error_log("Invalid submission amount: " . $amount);
            $error_message = 'Invalid submission amount. Please contact support.';
        } else {
            // Initialize Paystack payment
            $reference = 'SUB_' . uniqid() . '_' . time();
            // Use the student's provided email address
            $email = $_SESSION['submission_data']['email'];
            
            $metadata = [
                'name' => $_SESSION['submission_data']['name'],
                'index_number' => $_SESSION['submission_data']['index_number'],
                'phone' => $_SESSION['submission_data']['phone'],
                'email' => $_SESSION['submission_data']['email'],
                'file_path' => $_SESSION['submission_data']['file_path'],
                'file_name' => $_SESSION['submission_data']['file_name'],
                'file_size' => $_SESSION['submission_data']['file_size'],
                'file_type' => $_SESSION['submission_data']['file_type']
            ];
            
            // Create absolute URL for callback using the configured base URL
            $callback_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']) . "/payment/submission_callback.php";
            
            // Debug: Log the payment initialization
            error_log("Submitting payment - Email: $email, Amount: $amount, Reference: $reference, Callback: $callback_url");
            error_log("Submitting payment - Server: " . $_SERVER['HTTP_HOST'] . ", URI: " . $_SERVER['REQUEST_URI']);
            
            $payment_data = initializePaystackPayment(
                $email, 
                $amount, 
                $reference, 
                $callback_url,
                $metadata
            );
            
            if ($payment_data['status']) {
                // Store submission data temporarily
                $_SESSION['submission_data']['reference'] = $reference;
                $_SESSION['submission_data']['amount'] = $amount;
                
                // Redirect to Paystack checkout
                header('Location: ' . $payment_data['data']['authorization_url']);
                exit;
            } else {
                error_log("Payment initialization failed: " . json_encode($payment_data));
                $error_message = 'Payment initialization failed: ' . $payment_data['message'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project Report | Student Document Submission - ManuelCode</title>
    <meta name="description" content="Submit your project report document securely with payment verification. Easy 3-step submission process for students with SMS confirmation and payment tracking.">
    <meta name="keywords" content="project report submission, student documents, secure upload, payment verification, document submission">
    <meta name="author" content="ManuelCode">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Submit Project Report | Student Document Submission - ManuelCode">
    <meta property="og:description" content="Submit your project report document securely with payment verification. Easy 3-step submission process for students with SMS confirmation and payment tracking.">
    <meta property="og:image" content="assets/favi/favicon.png">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ManuelCode">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Submit Project Report | Student Document Submission - ManuelCode">
    <meta name="twitter:description" content="Submit your project report document securely with payment verification. Easy 3-step submission process for students with SMS confirmation and payment tracking.">
    <meta name="twitter:image" content="assets/favi/favicon.png">
    
    <link rel="icon" type="image/svg+xml" href="assets/favi/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Simplified Header for Submission Page -->
    <div class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <a href="index.php" class="flex items-center group">
                    <img src="assets/favi/favicon.png" alt="ManuelCode Logo" class="h-8 w-auto transition-transform duration-300 group-hover:scale-105">
                    <span class="ml-2 text-lg font-bold text-gray-800 group-hover:text-[#536895] transition-colors duration-300" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">ManuelCode</span>
                </a>
                
                <!-- Navigation -->
                <div class="flex items-center space-x-2">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                        <i class="fas fa-home mr-1"></i>
                        <span class="hidden sm:inline">Home</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-50">
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Modern Header Section -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 mb-6">
                    <i class="fas fa-file-upload text-2xl text-blue-600"></i>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-slate-800 mb-4">
                    Submit Your Project Report
                </h1>
                <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Complete your academic submission securely with our streamlined 3-step process. 
                    Upload your document, verify payment, and receive instant confirmation.
                </p>
                <div class="flex items-center justify-center mt-6 space-x-4 text-sm text-slate-500">
                    <span class="flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Secure Upload
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-credit-card mr-2"></i>
                        Payment Verified
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-sms mr-2"></i>
                        SMS Confirmation
                    </span>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="mb-8">
                <!-- Desktop Progress Bar -->
                <div class="hidden md:flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-white font-semibold text-sm">
                            1
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-900">Student Details</span>
                    </div>
                    <div class="flex-1 mx-4">
                        <div class="h-2 bg-gray-200 rounded">
                            <div class="h-2 bg-yellow-500 rounded transition-all duration-500" style="width: <?php echo ($step >= 2 ? '100%' : ($step == 1 ? '50%' : '0%')); ?>"></div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full <?php echo ($step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-500'); ?> flex items-center justify-center font-semibold text-sm">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium <?php echo ($step >= 2 ? 'text-gray-900' : 'text-gray-500'); ?>">Upload Project</span>
                    </div>
                    <div class="flex-1 mx-4">
                        <div class="h-2 bg-gray-200 rounded">
                            <div class="h-2 bg-blue-600 rounded transition-all duration-500" style="width: <?php echo ($step >= 3 ? '100%' : ($step == 2 ? '50%' : '0%')); ?>"></div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full <?php echo ($step >= 3 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-500'); ?> flex items-center justify-center font-semibold text-sm">
                            3
                        </div>
                        <span class="ml-2 text-sm font-medium <?php echo ($step >= 3 ? 'text-gray-900' : 'text-gray-500'); ?>">Payment</span>
                    </div>
                </div>
                
                <!-- Mobile Progress Bar -->
                <div class="md:hidden">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-700">Step <?php echo $step; ?> of 3</span>
                        <span class="text-sm text-gray-500"><?php echo round(($step / 3) * 100); ?>% Complete</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full <?php echo ($step >= 1 ? 'bg-yellow-500 text-white' : 'bg-gray-300 text-gray-500'); ?> flex items-center justify-center font-semibold text-sm">
                            1
                        </div>
                        <div class="flex-1 h-2 bg-gray-200 rounded">
                            <div class="h-2 bg-yellow-500 rounded transition-all duration-500" style="width: <?php echo ($step >= 2 ? '100%' : ($step == 1 ? '50%' : '0%')); ?>"></div>
                        </div>
                        <div class="flex-shrink-0 w-10 h-10 rounded-full <?php echo ($step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-500'); ?> flex items-center justify-center font-semibold text-sm">
                            2
                        </div>
                        <div class="flex-1 h-2 bg-gray-200 rounded">
                            <div class="h-2 bg-blue-600 rounded transition-all duration-500" style="width: <?php echo ($step >= 3 ? '100%' : ($step == 2 ? '50%' : '0%')); ?>"></div>
                        </div>
                        <div class="flex-shrink-0 w-10 h-10 rounded-full <?php echo ($step >= 3 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-500'); ?> flex items-center justify-center font-semibold text-sm">
                            3
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                        <span>Student Details</span>
                        <span>Upload Project</span>
                        <span>Payment</span>
                    </div>
                </div>
            </div>

            <!-- Submission Status Check -->
            <?php if (!$submissions_enabled): ?>
                <div class="max-w-2xl mx-auto mb-8">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-red-100 mb-4">
                            <i class="fas fa-ban text-red-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-red-800 mb-2">Submissions Currently Disabled</h3>
                        <p class="text-red-700 mb-4">
                            Project report submissions are temporarily disabled. Please check back later or contact support for more information.
                        </p>
                        <div class="bg-red-100 border border-red-200 rounded-lg p-3">
                            <p class="text-sm text-red-600">
                                <i class="fas fa-info-circle mr-2"></i>
                                This restriction is managed by the academic administration. 
                                Normal submission services will resume soon.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Messages -->
            <?php if ($error_message): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3"></i>
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 1: Student Details -->
            <?php if ($submissions_enabled && $step == 1): ?>
                <div class="bg-white shadow rounded-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">
                        <i class="fas fa-user mr-2 text-blue-600"></i>Step 1: Student Information
                    </h2>
                    
                    <form method="POST" class="space-y-4 sm:space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" required
                                   value="<?php echo htmlspecialchars($_SESSION['submission_data']['name'] ?? ''); ?>"
                                   class="w-full px-3 py-3 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Index Number *</label>
                            <input type="text" name="index_number" required
                                   value="<?php echo htmlspecialchars($_SESSION['submission_data']['index_number'] ?? ''); ?>"
                                   class="w-full px-3 py-3 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base"
                                   placeholder="BC/ITS/24/001">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" name="email" required
                                   value="<?php echo htmlspecialchars($_SESSION['submission_data']['email'] ?? ''); ?>"
                                   class="w-full px-3 py-3 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base"
                                   placeholder="your.email@example.com">
                            <p class="text-xs text-gray-500 mt-1">Required for payment processing and submission confirmation</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" name="phone" required
                                   value="<?php echo htmlspecialchars($_SESSION['submission_data']['phone'] ?? ''); ?>"
                                   class="w-full px-3 py-3 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base"
                                   placeholder="e.g., 233xxxxxxxxx or 0xxxxxxxxx">
                            <p class="text-xs text-gray-500 mt-1">Enter with or without country code</p>
                        </div>
                        
                        <div class="flex justify-end pt-4">
                            <button type="submit" name="step1"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-arrow-right mr-2"></i>Next Step
                            </button>
                        </div>
                    </form>
                </div>

            <!-- Step 2: File Upload -->
            <?php elseif ($submissions_enabled && $step == 2): ?>
                <div class="bg-white shadow rounded-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">
                        <i class="fas fa-file-upload mr-2 text-blue-600"></i>Step 2: Upload Project
                    </h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6">
                        <h3 class="font-medium text-blue-900 mb-2">Submission Summary:</h3>
                        <div class="space-y-2 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-4 text-sm">
                            <div>
                                <span class="font-medium">Name:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['name']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Index Number:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['index_number']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Email:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['email']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Phone:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['phone']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Amount:</span> <span class="font-bold text-green-600 text-base sm:text-lg">GHS <?php 
                                    try {
                                        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
                                        $stmt->execute(['submission_price']);
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $result ? number_format(floatval($result['value']), 2) : '0.01';
                                    } catch (Exception $e) {
                                        echo '0.01';
                                    }
                                ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4 sm:space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Project File *</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 sm:p-6 text-center" id="upload-area">
                                <input type="file" name="project_file" accept=".pdf,.doc,.docx" required
                                       class="hidden" id="file-input">
                                <label for="file-input" class="cursor-pointer block" id="upload-label">
                                    <i class="fas fa-cloud-upload-alt text-3xl sm:text-4xl text-gray-400 mb-2 sm:mb-4"></i>
                                    <p class="text-base sm:text-lg font-medium text-gray-900">Click to upload your project</p>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1">PDF or Word documents only, max 10MB</p>
                                </label>
                                
                                <!-- Upload Progress Bar -->
                                <div id="upload-progress" class="hidden mt-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Uploading...</span>
                                        <span class="text-sm text-gray-500" id="progress-percent">0%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" id="progress-bar" style="width: 0%"></div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-2" id="upload-status">Preparing upload...</p>
                                </div>
                                
                                <!-- File Info -->
                                <div id="file-info" class="mt-4 hidden">
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                            <div>
                                                <p class="text-sm font-medium text-green-900">File selected successfully!</p>
                                                <p class="text-xs text-green-700">Name: <span id="file-name"></span></p>
                                                <p class="text-xs text-green-700">Size: <span id="file-size"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" id="change-file" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                            <i class="fas fa-edit mr-1"></i>Change file
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="submission.php?step=1" 
                               class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Previous
                            </a>
                            <button type="submit" name="step2"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-arrow-right mr-2"></i>Next Step
                            </button>
                        </div>
                    </form>
                </div>

            <!-- Step 3: Payment -->
            <?php elseif ($submissions_enabled && $step == 3): ?>
                <div class="bg-white shadow rounded-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6">
                        <i class="fas fa-credit-card mr-2 text-blue-600"></i>Step 3: Payment
                    </h2>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6">
                        <h3 class="font-medium text-green-900 mb-2">Final Summary:</h3>
                        <div class="space-y-2 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-4 text-sm">
                            <div>
                                <span class="font-medium">Name:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['name']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Index Number:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['index_number']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Email:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['email']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Phone:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['phone']); ?>
                            </div>
                            <div>
                                <span class="font-medium">File:</span> <?php echo htmlspecialchars($_SESSION['submission_data']['file_name']); ?>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="font-medium">Amount:</span> <span class="text-lg sm:text-xl font-bold text-green-600">GHS <?php 
                                    try {
                                        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
                                        $stmt->execute(['submission_price']);
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $result ? number_format(floatval($result['value']), 2) : '0.01';
                                    } catch (Exception $e) {
                                        echo '0.01';
                                    }
                                ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="font-medium text-blue-900 mb-2">Payment Instructions:</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li><i class="fas fa-check mr-2"></i>Click "Proceed to Payment" to continue</li>
                            <li><i class="fas fa-check mr-2"></i>You'll be redirected to secure Paystack payment page</li>
                            <li><i class="fas fa-check mr-2"></i>Complete payment using Mobile Money, Card, or Bank Transfer</li>
                            <li><i class="fas fa-check mr-2"></i>You'll receive SMS confirmation after successful payment</li>
                        </ul>
                    </div>
                    
                    <form method="POST" class="space-y-6">
                        <div class="flex justify-between">
                            <a href="submission.php?step=2" 
                               class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Previous
                            </a>
                            <button type="submit" name="step3"
                                    class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-credit-card mr-2"></i>Proceed to Payment
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // File input handling with upload progress
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file-input');
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const uploadProgress = document.getElementById('upload-progress');
            const uploadLabel = document.getElementById('upload-label');
            const progressBar = document.getElementById('progress-bar');
            const progressPercent = document.getElementById('progress-percent');
            const uploadStatus = document.getElementById('upload-status');
            const changeFileBtn = document.getElementById('change-file');
            
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        
                        // Validate file type
                        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Please select a PDF or Word document file.');
                            this.value = '';
                            return;
                        }
                        
                        // Validate file size (10MB)
                        if (file.size > 10 * 1024 * 1024) {
                            alert('File size must be less than 10MB.');
                            this.value = '';
                            return;
                        }
                        
                        // Show progress bar and simulate upload
                        simulateUpload(file);
                    }
                });
            }
            
            // Change file button
            if (changeFileBtn) {
                changeFileBtn.addEventListener('click', function() {
                    fileInfo.classList.add('hidden');
                    uploadProgress.classList.add('hidden');
                    uploadLabel.classList.remove('hidden');
                    fileInput.value = '';
                });
            }
            
            // Simulate upload process
            function simulateUpload(file) {
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                
                // Hide upload label and show progress
                uploadLabel.classList.add('hidden');
                uploadProgress.classList.remove('hidden');
                uploadStatus.textContent = 'Starting upload...';
                
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    
                    if (progress >= 100) {
                        progress = 100;
                        clearInterval(interval);
                        uploadComplete();
                    }
                    
                    progressBar.style.width = progress + '%';
                    progressPercent.textContent = Math.round(progress) + '%';
                    
                    if (progress < 30) {
                        uploadStatus.textContent = 'Preparing file...';
                    } else if (progress < 60) {
                        uploadStatus.textContent = 'Uploading file...';
                    } else if (progress < 90) {
                        uploadStatus.textContent = 'Processing file...';
                    } else {
                        uploadStatus.textContent = 'Finalizing...';
                    }
                }, 200);
            }
            
            function uploadComplete() {
                uploadStatus.textContent = 'Upload complete!';
                progressBar.classList.remove('bg-blue-600');
                progressBar.classList.add('bg-green-600');
                
                setTimeout(() => {
                    uploadProgress.classList.add('hidden');
                    fileInfo.classList.remove('hidden');
                    uploadStatus.textContent = 'File ready for submission';
                }, 1000);
            }
        });
    </script>
</body>
</html>
