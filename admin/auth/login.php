<?php
session_start();
include '../../includes/db.php';
include '../../includes/otp_helper.php';

// Clean up expired data
cleanup_expired_data();

$error_message = '';
$success_message = '';
$show_otp_form = false;
$show_password_form = false;
$email = '';
$phone = '';
$login_method = '';

// Handle timeout message
if (isset($_GET['message']) && $_GET['message'] === 'timeout') {
    $error_message = 'Your session has expired due to inactivity. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['choose_method'])) {
        $email = trim($_POST['email']);
        $login_method = $_POST['login_method'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Check if admin exists with this email (exclude super admin)
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, phone FROM admins WHERE email = ? AND name NOT LIKE '%Super Admin%'");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    if ($login_method === 'otp') {
                        $phone = $admin['phone'];
                        
                        // Normalize phone number
                        $normalized_phone = normalize_phone_number($phone);
                        
                        // Validate phone number format
                        if (!validate_phone_number($phone)) {
                            $error_message = 'Admin phone number is not in valid format. Please contact administrator.';
                        } else {
                            // Send OTP for admin login
                            $otp_code = generate_otp();
                            if (store_otp($normalized_phone, $email, $otp_code, 'admin_login')) {
                                $sms_result = send_otp_sms($normalized_phone, $otp_code, 'admin_login');
                                if ($sms_result['success']) {
                                    $success_message = 'OTP sent to your phone number!';
                                    $show_otp_form = true;
                                } else {
                                    $error_message = 'Failed to send OTP: ' . $sms_result['error'];
                                }
                            } else {
                                $error_message = 'Failed to generate OTP. Please try again.';
                            }
                        }
                    } else {
                        $show_password_form = true;
                    }
                } else {
                    $error_message = 'No admin account found with this email address.';
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
                error_log("Admin login error: " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['send_otp'])) {
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Validate inputs
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (empty($phone)) {
            $error_message = 'Please enter your phone number.';
        } else {
            // Normalize phone number for comparison
            $normalized_phone = normalize_phone_number($phone);
            
            // Validate phone number format (must be 233XXXXXXXXX)
            if (!validate_phone_number($phone)) {
                $error_message = 'Please enter a valid Ghana phone number (e.g., 0241234567 or 241234567).';
            } else {
                // Check if admin exists with this email and phone (exclude super admin)
                try {
                    $stmt = $pdo->prepare("SELECT id, name, phone FROM admins WHERE email = ? AND phone = ? AND name NOT LIKE '%Super Admin%'");
                    $stmt->execute([$email, $normalized_phone]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($admin) {
                        // Send OTP for admin login
                        $otp_code = generate_otp();
                        if (store_otp($normalized_phone, $email, $otp_code, 'admin_login')) {
                            $sms_result = send_otp_sms($normalized_phone, $otp_code, 'admin_login');
                            if ($sms_result['success']) {
                                $success_message = 'OTP sent to your phone number!';
                                $show_otp_form = true;
                            } else {
                                $error_message = 'Failed to send OTP: ' . $sms_result['error'];
                            }
                        } else {
                            $error_message = 'Failed to generate OTP. Please try again.';
                        }
                    } else {
                        $error_message = 'The email address or phone number you entered is incorrect. Please check your details and try again.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Database error: ' . $e->getMessage();
                    error_log("Admin login error: " . $e->getMessage());
                }
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $otp_code = trim($_POST['otp_code']);

        // Normalize phone number
        $normalized_phone = normalize_phone_number($phone);
        
        // Debug: Log the verification attempt
        error_log("OTP Verification Attempt - Phone: $normalized_phone, Email: $email, OTP: $otp_code, Purpose: admin_login");
        
        // Verify OTP for admin login
        $verified = verify_otp($normalized_phone, $email, $otp_code, 'admin_login');
        
        // Debug: Log the result
        error_log("OTP Verification Result: " . ($verified ? "SUCCESS" : "FAILED"));
        
        if ($verified) {
            // Get admin details (exclude super admin)
            try {
                $stmt = $pdo->prepare("SELECT id, name, phone FROM admins WHERE email = ? AND phone = ? AND name NOT LIKE '%Super Admin%'");
                $stmt->execute([$email, $normalized_phone]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    // Update last login and login method
                    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW(), login_method = 'otp' WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Create admin session (regular admin, not super admin)
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_phone'] = $normalized_phone;
                    $_SESSION['user_role'] = 'admin'; // Regular admin role
                    $_SESSION['admin_login_time'] = time();
                    
                    header('Location: ../../dashboard/');
                    exit;
                } else {
                    $error_message = 'Admin account not found. Please check your credentials.';
                    $show_otp_form = true;
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
                error_log("Admin login error: " . $e->getMessage());
                $show_otp_form = true;
            }
        } else {
            $error_message = 'The verification code you entered is incorrect or has expired. Please request a new code and try again.';
            $show_otp_form = true;
        }
    } elseif (isset($_POST['login_password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please enter both email and password.';
            $show_password_form = true;
        } else {
            try {
                // Get admin details (include all admins)
                $stmt = $pdo->prepare("SELECT id, name, email, password FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug logging
                error_log("Admin login attempt - Email: $email, Admin found: " . ($admin ? 'YES' : 'NO'));
                if ($admin) {
                    error_log("Password verification - Input: $password, Stored hash: " . substr($admin['password'], 0, 20) . "...");
                    error_log("Password verify result: " . (password_verify($password, $admin['password']) ? 'TRUE' : 'FALSE'));
                }
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Update last login and login method
                    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW(), login_method = 'password' WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Create admin session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['admin_login_time'] = time();
                    
                    header('Location: ../../dashboard/');
                    exit;
                } else {
                    $error_message = 'Invalid email or password. Please try again.';
                    $show_password_form = true;
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
                $show_password_form = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Login - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favi/login-favicon.svg">
    <link rel="alternate icon" href="../../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Prevent horizontal overflow */
        body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        /* Enhanced Desktop Styling */
        @media (min-width: 1024px) {
            .login-container {
                padding: 2rem;
                margin: 1rem;
            }
            .login-card {
                padding: 2.5rem;
                max-width: 450px;
                min-height: auto;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }
            .login-header {
                margin-bottom: 2rem;
            }
            .login-form {
                gap: 1.5rem;
            }
            .form-group {
                margin-bottom: 1.5rem;
            }
            .input-field {
                padding: 1rem 1rem 1rem 3rem;
                font-size: 1rem;
                border-radius: 12px;
            }
            .input-icon {
                left: 1rem;
            }
            .btn-primary {
                padding: 1rem 1.5rem;
                font-size: 1rem;
                border-radius: 12px;
            }
            .radio-group {
                gap: 1rem;
            }
            .radio-option {
                padding: 0.75rem;
                font-size: 1rem;
                border-radius: 8px;
            }
        }
        
        /* Enhanced Mobile Responsiveness */
        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
                margin: 0.5rem;
            }
            .login-card {
                padding: 1.5rem;
                max-width: 100%;
                min-height: auto;
            }
            .login-header {
                margin-bottom: 1.5rem;
            }
            .login-form {
                gap: 1rem;
            }
            .form-group {
                margin-bottom: 1rem;
            }
            .input-field {
                padding: 0.75rem 0.75rem 0.75rem 2.5rem;
                font-size: 0.875rem;
            }
            .input-icon {
                left: 0.75rem;
            }
            .btn-primary {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
            .radio-group {
                gap: 0.75rem;
            }
            .radio-option {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 640px) {
            .login-container {
                padding: 0.75rem;
                margin: 0.25rem;
            }
            .login-card {
                padding: 1.25rem;
            }
            .login-header {
                margin-bottom: 1.25rem;
            }
            .login-form {
                gap: 0.875rem;
            }
            .form-group {
                margin-bottom: 0.875rem;
            }
            .input-field {
                padding: 0.625rem 0.625rem 0.625rem 2.25rem;
                font-size: 0.875rem;
            }
            .input-icon {
                left: 0.625rem;
            }
            .btn-primary {
                padding: 0.625rem 0.875rem;
                font-size: 0.875rem;
            }
            .radio-group {
                gap: 0.625rem;
            }
            .radio-option {
                padding: 0.375rem;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 0.5rem;
                margin: 0.125rem;
            }
            .login-card {
                padding: 1rem;
            }
            .login-header {
                margin-bottom: 1rem;
            }
            .login-form {
                gap: 0.75rem;
            }
            .form-group {
                margin-bottom: 0.75rem;
            }
            .input-field {
                padding: 0.5rem 0.5rem 0.5rem 2rem;
                font-size: 0.875rem;
            }
            .input-icon {
                left: 0.5rem;
            }
            .btn-primary {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }
            .radio-group {
                gap: 0.5rem;
            }
            .radio-option {
                padding: 0.25rem;
                font-size: 0.875rem;
            }
        }
        
        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .btn-primary,
            .btn-secondary {
                min-height: 44px;
                touch-action: manipulation;
            }
            .input-field {
                min-height: 44px;
                touch-action: manipulation;
            }
        }
        
        /* Enhanced form styling */
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }
        
        .radio-option:hover {
            background-color: #f8fafc;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* OTP input specific styles */
        .otp-input {
            text-align: center;
            font-size: 1.5rem; /* Adjust as needed */
            font-family: monospace; /* Ensure consistent font */
            width: 100%; /* Take full width of its container */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }

        .otp-input:focus {
            outline: none; /* Remove default focus outline */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5), 0 0 0 6px rgba(59, 130, 246, 0.2); /* More visible focus */
            border-color: #3b82f6;
        }

        .otp-input::-webkit-inner-spin-button,
        .otp-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .otp-input:not(:placeholder-shown) {
            text-transform: uppercase; /* Optional: for uppercase OTP */
        }

        .otp-input:invalid {
            border-color: #ef4444; /* Red border for invalid input */
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .otp-input:valid {
            border-color: #22c55e; /* Green border for valid input */
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
        }

        .otp-input:focus:invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.5), 0 0 0 6px rgba(239, 68, 68, 0.2);
        }

        .otp-input:focus:valid {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.5), 0 0 0 6px rgba(34, 197, 94, 0.2);
        }

        /* Navigation between OTP inputs */
        .otp-input[data-prev] {
            margin-left: -1px; /* Overlap with the next input */
        }

        .otp-input[data-next] {
            margin-right: -1px; /* Overlap with the previous input */
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100 min-h-screen flex items-center justify-center login-container">
    <div class="bg-white rounded-2xl shadow-xl login-card w-full">
        <div class="text-center login-header">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full mb-4 shadow-lg">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Admin Login</h1>
            <p class="text-gray-600">Choose your preferred login method</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-2 mb-3 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-400 mr-2 text-xs"></i>
                    <p class="text-xs text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-2 mb-3 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-400 mr-2 text-xs"></i>
                    <p class="text-xs text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$show_otp_form && !$show_password_form): ?>
            <!-- Initial Login Form -->
            <form method="POST" class="login-form space-y-3">
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 input-icon">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                               class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm input-field"
                               placeholder="admin@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Login Method</label>
                    <div class="radio-group space-y-2">
                        <label class="flex items-center radio-option">
                            <input type="radio" name="login_method" value="password" class="mr-2" checked>
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-key mr-1"></i>Email & Password
                            </span>
                        </label>
                        <label class="flex items-center radio-option">
                            <input type="radio" name="login_method" value="otp" class="mr-2">
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-mobile-alt mr-1"></i>SMS OTP
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="choose_method" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm btn-primary">
                    <i class="fas fa-arrow-right mr-2"></i>Continue
                </button>
            </form>
        <?php elseif ($show_password_form): ?>
            <!-- Password Login Form -->
            <form method="POST" class="login-form space-y-3">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="text-center">
                    <p class="text-gray-600 text-xs mb-1">Login with password for</p>
                    <p class="font-medium text-gray-800 text-xs"><?php echo htmlspecialchars($email); ?></p>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 input-icon">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" 
                               class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm input-field"
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" name="login_password" 
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium text-sm btn-primary">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>

                <div class="text-center">
                    <button type="button" onclick="resetToLogin()" 
                            class="text-blue-600 hover:text-blue-800 text-xs btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Login
                    </button>
                </div>
            </form>
        <?php elseif ($show_otp_form): ?>
            <!-- OTP Verification Form -->
            <form method="POST" class="login-form space-y-3">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                
                <div class="text-center">
                    <p class="text-gray-600 text-xs mb-1">Enter the 6-digit code sent to</p>
                    <p class="font-medium text-gray-800 text-xs"><?php echo htmlspecialchars(format_phone_for_display($phone)); ?></p>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">OTP Code</label>
                    <div class="flex justify-center space-x-2">
                        <input type="text" name="otp_1" maxlength="1" class="w-12 h-12 text-center text-lg font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent otp-input" data-next="otp_2" required>
                        <input type="text" name="otp_2" maxlength="1" class="w-12 h-12 text-center text-lg font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent otp-input" data-next="otp_3" data-prev="otp_1" required>
                        <input type="text" name="otp_3" maxlength="1" class="w-12 h-12 text-center text-lg font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent otp-input" data-next="otp_4" data-prev="otp_2" required>
                        <input type="text" name="otp_4" maxlength="1" class="w-12 h-12 text-center text-lg font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent otp-input" data-next="otp_5" data-prev="otp_3" required>
                        <input type="text" name="otp_5" maxlength="1" class="w-12 h-12 text-center text-lg font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent otp-input" data-next="otp_6" data-prev="otp_4" required>
                        <input type="text" name="otp_6" maxlength="1" class="w-12 h-12 text-center text-lg font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent otp-input" data-prev="otp_5" required>
                    </div>
                    <input type="hidden" name="otp_code" id="otp_code_combined">
                    <p class="text-xs text-gray-500 mt-2 text-center">Enter the 6-digit code sent to your phone</p>
                </div>

                <button type="submit" name="verify_otp" 
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium text-sm btn-primary">
                    <i class="fas fa-check mr-2"></i>Verify & Login
                </button>

                <div class="text-center">
                    <button type="button" onclick="resetToLogin()" 
                            class="text-blue-600 hover:text-blue-800 text-xs btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Login
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="mt-4 pt-2 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500 mb-1">
                <i class="fas fa-shield-alt mr-1"></i>
                Secure Admin Access
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // OTP input formatting and navigation
            const otpInputs = document.querySelectorAll('.otp-input');
            const combinedOtpInput = document.getElementById('otp_code_combined');
            
            otpInputs.forEach((input, index) => {
                // Auto-focus first input
                if (index === 0) {
                    input.focus();
                }
                
                input.addEventListener('input', function() {
                    // Only allow digits
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
                    
                    // Combine all OTP digits
                    updateCombinedOtp();
                    
                    // Auto-focus next input if current is filled
                    if (this.value && this.dataset.next) {
                        const nextInput = document.querySelector(`[name="${this.dataset.next}"]`);
                        if (nextInput) {
                            nextInput.focus();
                        }
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '') {
                        // Move to previous input on backspace if current is empty
                        if (this.dataset.prev) {
                            const prevInput = document.querySelector(`[name="${this.dataset.prev}"]`);
                            if (prevInput) {
                                prevInput.focus();
                            }
                        }
                    } else if (e.key === 'ArrowRight') {
                        // Move to next input
                        if (this.dataset.next) {
                            const nextInput = document.querySelector(`[name="${this.dataset.next}"]`);
                            if (nextInput) {
                                nextInput.focus();
                            }
                        }
                    } else if (e.key === 'ArrowLeft') {
                        // Move to previous input
                        if (this.dataset.prev) {
                            const prevInput = document.querySelector(`[name="${this.dataset.prev}"]`);
                            if (prevInput) {
                                prevInput.focus();
                            }
                        }
                    }
                });
                
                // Handle paste event
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedData.replace(/[^0-9]/g, '').slice(0, 6);
                    
                    // Fill all inputs with pasted digits
                    otpInputs.forEach((inputField, i) => {
                        if (i < digits.length) {
                            inputField.value = digits[i];
                        } else {
                            inputField.value = '';
                        }
                    });
                    
                    updateCombinedOtp();
                    
                    // Focus the next empty input or the last one
                    const nextEmptyIndex = digits.length < 6 ? digits.length : 5;
                    if (otpInputs[nextEmptyIndex]) {
                        otpInputs[nextEmptyIndex].focus();
                    }
                });
            });
            
            function updateCombinedOtp() {
                const otpDigits = Array.from(otpInputs).map(input => input.value).join('');
                if (combinedOtpInput) {
                    combinedOtpInput.value = otpDigits;
                }
            }
            
            // Password input focus
            const passwordInput = document.querySelector('input[name="password"]');
            if (passwordInput) {
                passwordInput.focus();
            }
        });
        
        // Function to reset form and go back to login
        function resetToLogin() {
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>
