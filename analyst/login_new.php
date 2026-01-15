<?php
session_start();
include '../includes/db.php';
include '../includes/otp_helper.php';

// Initialize variables
$error_message = '';
$success_message = '';
$auth_step = 'email'; // Default step: email or otp
$is_loading = false;

// Check for success message flag after redirect
if (isset($_SESSION['otp_success_message']) && $_SESSION['otp_success_message'] === true) {
    $success_message = "OTP sent successfully to your phone number! Check your SMS for the 6-digit code.";
    $auth_step = 'otp';
    // Do not unset here. Unset only after OTP is verified or reset.
}

// Ensure only one of success or error message is shown
if ($success_message !== '') {
    $error_message = '';
}

// Check if already logged in
if (isset($_SESSION['analyst_logged_in']) && $_SESSION['analyst_logged_in'] === true) {
    header('Location: /dashboard');
    exit;
}

// Handle reset parameter (for back button)
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    unset($_SESSION['pending_analyst']);
    unset($_SESSION['otp_request_time']);
    unset($_SESSION['otp_success_message']);
    $auth_step = 'email';
    $success_message = '';
    $error_message = '';
    header('Location: login_new.php');
    exit;
}

// Only clear OTP session if explicitly reset or expired
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST) && !isset($_GET['reset'])) {
    if (isset($_SESSION['otp_request_time'])) {
        $time_diff = time() - $_SESSION['otp_request_time'];
        // Expire after 10 minutes
        if ($time_diff > 600) {
            unset($_SESSION['otp_request_time'], $_SESSION['pending_analyst'], $_SESSION['otp_success_message']);
            $auth_step = 'email';
        } else {
            $auth_step = 'otp';
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_otp'])) {
        $is_loading = true;
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
            unset($_SESSION['otp_success_message']);
        } else {
            try {
                // Check if analyst exists and is active
                $stmt = $pdo->prepare("SELECT id, name, email, phone, status FROM analysts WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $analyst = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$analyst) {
                    $error_message = 'No active analyst account found with this email address.';
                    unset($_SESSION['otp_success_message']);
                } elseif (empty($analyst['phone'])) {
                    $error_message = 'Phone number not found for this analyst account. Please contact administrator.';
                    unset($_SESSION['otp_success_message']);
                } else {
                    // Generate OTP
                    $otp = generate_otp();
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Debug: Log OTP generation
                    error_log("OTP Generated: '$otp' for analyst ID: " . $analyst['id']);
                    
                    // Save OTP to database
                    $stmt = $pdo->prepare("UPDATE analysts SET otp = ?, otp_expires_at = ? WHERE id = ?");
                    $stmt->execute([$otp, $otp_expires, $analyst['id']]);
                    
                    // Verify OTP was saved
                    $verify_stmt = $pdo->prepare("SELECT otp FROM analysts WHERE id = ?");
                    $verify_stmt->execute([$analyst['id']]);
                    $saved_otp = $verify_stmt->fetchColumn();
                    error_log("OTP Saved to DB: '$saved_otp' for analyst ID: " . $analyst['id']);
                    
                    // Send OTP via SMS
                    try {
                        include '../config/sms_config.php';
                        $normalized_phone = normalize_phone_number($analyst['phone']);
                        $message = "Your Analyst Login OTP is: {$otp}. Valid for 5 minutes. - ManuelCode";
                        
                        $sms_result = send_sms_improved($normalized_phone, $message);
                        
                        // Store analyst info in session for OTP verification
                        $_SESSION['pending_analyst'] = $analyst;
                        $_SESSION['otp_request_time'] = time();
                        $_SESSION['otp_success_message'] = true;
                        
                        // Log SMS sending
                        $stmt = $pdo->prepare("
                            INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                            VALUES (?, 'otp_sent', 'OTP sent via SMS to ' . ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$analyst['id'], $normalized_phone, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                        
                        // Redirect to prevent form resubmission
                        session_write_close();
                        header('Location: login_new.php');
                        exit;
                    } // End of inner try block
                } // End of else block
            } catch (Exception $e) {
                $error_message = 'An error occurred. Please try again.';
                unset($_SESSION['otp_success_message']);
                error_log("Analyst login error: " . $e->getMessage());
            }
        }
        $is_loading = false;
    } elseif (isset($_POST['verify_otp'])) {
        $is_loading = true;
        $otp = trim($_POST['otp'] ?? '');
        
        // Debug: Log that OTP verification was attempted
        error_log("OTP Verification Attempt - OTP: '$otp', Session: " . json_encode($_SESSION['pending_analyst'] ?? 'NULL'));
        
        if (empty($otp)) {
            $error_message = 'Please enter the OTP.';
            unset($_SESSION['otp_success_message']);
        } elseif (!isset($_SESSION['pending_analyst'])) {
            $error_message = 'Please request an OTP first.';
            unset($_SESSION['otp_success_message']);
        } else {
            try {
                $analyst_id = $_SESSION['pending_analyst']['id'];
                
                // Verify OTP
                $stmt = $pdo->prepare("SELECT id, name, email, otp, otp_expires_at FROM analysts WHERE id = ? AND status = 'active'");
                $stmt->execute([$analyst_id]);
                $analyst = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug: Log OTP comparison
                error_log("OTP Debug - Input: '$otp', Stored: '" . ($analyst['otp'] ?? 'NULL') . "', Match: " . (($analyst['otp'] ?? '') === $otp ? 'YES' : 'NO'));
                
                // Store debug info in session for display
                $_SESSION['debug_otp_input'] = $otp;
                $_SESSION['debug_otp_stored'] = $analyst['otp'] ?? 'NULL';
                $_SESSION['debug_otp_match'] = (($analyst['otp'] ?? '') === $otp ? 'YES' : 'NO');
                
                if (!$analyst) {
                    $error_message = 'Analyst account not found.';
                    unset($_SESSION['otp_success_message']);
                } elseif (empty($analyst['otp'])) {
                    $error_message = 'OTP not found. Please request a new one.';
                    unset($_SESSION['otp_success_message']);
                } elseif (trim($analyst['otp']) !== trim($otp)) {
                    $error_message = 'Invalid OTP. Please try again.';
                    unset($_SESSION['otp_success_message']);
                } elseif (strtotime($analyst['otp_expires_at']) < time()) {
                    $error_message = 'OTP has expired. Please request a new one.';
                    unset($_SESSION['otp_success_message']);
                } else {
                    // OTP is valid - log in the analyst
                    $_SESSION['analyst_logged_in'] = true;
                    $_SESSION['analyst_id'] = $analyst['id'];
                    $_SESSION['analyst_name'] = $analyst['name'];
                    $_SESSION['analyst_email'] = $analyst['email'];
                    $_SESSION['user_role'] = 'analyst';
                    
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE analysts SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$analyst['id']]);
                    
                    // Log analyst activity
                    $stmt = $pdo->prepare("
                        INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                        VALUES (?, 'login', 'Analyst logged in successfully', ?, ?, NOW())
                    ");
                    $stmt->execute([$analyst['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                    
                    // Clear session data
                    unset($_SESSION['pending_analyst'], $_SESSION['otp_success_message'], $_SESSION['otp_request_time']);
                    
                    // Redirect to dashboard
                    header('Location: /dashboard');
                    exit;
                }
            } catch (Exception $e) {
                $error_message = 'An error occurred. Please try again.';
                unset($_SESSION['otp_success_message']);
                error_log("Analyst OTP verification error: " . $e->getMessage());
            }
        }
        $is_loading = false;
    }
}

// Determine current auth step
if (isset($_SESSION['pending_analyst']) && isset($_SESSION['otp_request_time'])) {
    $time_diff = time() - $_SESSION['otp_request_time'];
    if ($time_diff <= 600) { // 10 minutes
        $auth_step = 'otp';
    } else {
        // Session expired
        unset($_SESSION['pending_analyst'], $_SESSION['otp_request_time'], $_SESSION['otp_success_message']);
        $auth_step = 'email';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyst Login - ManuelCode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fade-in { animation: fadeIn 0.5s ease-in; }
        .slide-up { animation: slideUp 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .loading-spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center fade-in">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-800">
                    Analyst Login
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600">
                    Access project submissions and analytics
                </p>
            </div>

            <!-- Main Form Container -->
            <div class="bg-white/80 backdrop-blur-sm py-8 px-6 shadow-xl rounded-2xl border border-white/20 slide-up">
                
                <!-- Messages -->
                <?php if ($error_message): ?>
                    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl shadow-sm fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-rose-500 mr-3"></i>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl shadow-sm fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Debug Information (remove this after testing) -->
                <?php if (isset($_SESSION['pending_analyst']) && isset($_SESSION['pending_analyst']['id'])): ?>
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
                        <strong>Debug Info:</strong> Analyst ID: <?php echo $_SESSION['pending_analyst']['id']; ?> | 
                        Email: <?php echo $_SESSION['pending_analyst']['email']; ?>
                        <?php 
                        // Get current OTP from database for debugging
                        try {
                            $debug_stmt = $pdo->prepare("SELECT otp, otp_expires_at FROM analysts WHERE id = ?");
                            $debug_stmt->execute([$_SESSION['pending_analyst']['id']]);
                            $debug_otp = $debug_stmt->fetch(PDO::FETCH_ASSOC);
                            if ($debug_otp) {
                                echo " | Current OTP: " . ($debug_otp['otp'] ?? 'NULL') . " | Expires: " . ($debug_otp['otp_expires_at'] ?? 'NULL');
                            }
                        } catch (Exception $e) {
                            echo " | Error getting OTP: " . $e->getMessage();
                        }
                        ?>
                        
                        <?php if (isset($_SESSION['debug_otp_input'])): ?>
                            <br><strong>Last OTP Attempt:</strong> Input: <?php echo $_SESSION['debug_otp_input']; ?> | 
                            Stored: <?php echo $_SESSION['debug_otp_stored']; ?> | 
                            Match: <?php echo $_SESSION['debug_otp_match']; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Email Form -->
                <?php if ($auth_step === 'email'): ?>
                    <form method="POST" class="space-y-6 fade-in">
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-indigo-500"></i>
                                Email Address
                            </label>
                            <div class="relative">
                                <input id="email" name="email" type="email" required
                                       class="appearance-none relative block w-full px-4 py-4 border border-slate-300 placeholder-slate-400 text-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm shadow-sm transition-all duration-200"
                                       placeholder="Enter your email address"
                                       <?php echo $is_loading ? 'disabled' : ''; ?>>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-envelope text-slate-400"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" name="request_otp"
                                    class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?php echo $is_loading ? 'disabled' : ''; ?>>
                                <?php if ($is_loading): ?>
                                    <i class="fas fa-spinner loading-spinner mr-2"></i>
                                    Sending OTP...
                                <?php else: ?>
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Request OTP
                                <?php endif; ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- OTP Verification Form -->
                <?php if ($auth_step === 'otp'): ?>
                    <form method="POST" class="space-y-6 fade-in">
                        <div class="text-center mb-4">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 mb-3">
                                <i class="fas fa-mobile-alt text-emerald-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800">Verify OTP</h3>
                            <p class="text-sm text-slate-600 mt-1">
                                Enter the 6-digit code sent to your phone
                            </p>
                        </div>

                        <div>
                            <label for="otp" class="block text-sm font-semibold text-slate-700 mb-2">
                                <i class="fas fa-key mr-2 text-emerald-500"></i>
                                OTP Code
                            </label>
                            <div class="relative">
                                <input id="otp" name="otp" type="text" maxlength="6" required
                                       class="appearance-none relative block w-full px-4 py-4 border border-slate-300 placeholder-slate-400 text-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:z-10 sm:text-lg text-center tracking-widest font-mono shadow-sm transition-all duration-200"
                                       placeholder="000000"
                                       <?php echo $is_loading ? 'disabled' : ''; ?>>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-key text-slate-400"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 text-center">
                                <i class="fas fa-clock mr-1"></i>
                                OTP expires in 5 minutes
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" name="verify_otp"
                                    class="flex-1 group relative flex justify-center py-4 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?php echo $is_loading ? 'disabled' : ''; ?>>
                                <?php if ($is_loading): ?>
                                    <i class="fas fa-spinner loading-spinner mr-2"></i>
                                    Verifying...
                                <?php else: ?>
                                    <i class="fas fa-check mr-2"></i>
                                    Verify OTP
                                <?php endif; ?>
                            </button>
                            <a href="login_new.php?reset=1" 
                               class="flex-1 group relative flex justify-center py-4 px-4 border border-slate-300 text-sm font-semibold rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm hover:shadow-md transition-all duration-200">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back
                            </a>
                        </div>

                        <!-- Resend OTP Option -->
                        <div class="text-center">
                            <button type="button" onclick="resendOTP()" 
                                    class="text-sm text-indigo-600 hover:text-indigo-500 transition-colors font-medium">
                                <i class="fas fa-redo mr-1"></i>
                                Resend OTP
                            </button>
                        </div>

                        <!-- Test OTP Button (remove after testing) -->
                        <div class="text-center mt-2">
                            <button type="button" onclick="testOTP()" 
                                    class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
                                <i class="fas fa-bug mr-1"></i>
                                Test OTP Verification
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <a href="../index.php" class="text-sm text-slate-600 hover:text-slate-800 transition-colors font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus and input validation
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            const emailInput = document.getElementById('email');
            
            if (otpInput) {
                otpInput.focus();
                
                // Only allow numbers
                otpInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
                
                // Auto-submit when 6 digits are entered
                otpInput.addEventListener('input', function() {
                    if (this.value.length === 6) {
                        this.form.submit();
                    }
                });
            }
            
            if (emailInput) {
                emailInput.focus();
            }
        });

        // Resend OTP function
        function resendOTP() {
            if (confirm('Resend OTP to your phone number?')) {
                // Redirect to email step to request new OTP
                window.location.href = 'login_new.php?reset=1';
            }
        }

        // Test OTP function (remove after testing)
        function testOTP() {
            const otpInput = document.getElementById('otp');
            if (otpInput && otpInput.value) {
                console.log('Testing OTP:', otpInput.value);
                console.log('OTP length:', otpInput.value.length);
                console.log('OTP type:', typeof otpInput.value);
                console.log('OTP trimmed:', otpInput.value.trim());
            } else {
                console.log('No OTP input found or empty');
            }
        }

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
