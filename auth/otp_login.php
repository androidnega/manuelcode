<?php
session_start();
include '../includes/db.php';
include '../includes/otp_helper.php';
include '../includes/logger.php';
include '../includes/user_id_generator.php';

// Clean up expired data
cleanup_expired_data();

$error_message = '';
$success_message = '';
$show_otp_form = false;
$show_registration_form = false;
$phone = '';
$csrf_token = '';

// Handle timeout message
if (isset($_GET['message']) && $_GET['message'] === 'timeout') {
    $error_message = 'Your session has expired due to inactivity. Please log in again.';
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("OTP Login: POST request received");
    error_log("OTP Login: POST data: " . json_encode($_POST));
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("OTP Login: CSRF token mismatch");
        $error_message = 'Security validation failed. Please try again.';
    } else {
        if (isset($_POST['send_otp'])) {
            error_log("OTP Login: Send OTP request");
            $phone = trim($_POST['phone']);
            
            // Validate phone number
            if (empty($phone)) {
                $error_message = 'Please enter your phone number.';
            } elseif (!validate_phone_number($phone)) {
                $error_message = 'Please enter a valid Ghana phone number (10 digits including leading 0, e.g., 0241234567).';
            } else {
                // Normalize phone number
                $normalized_phone = normalize_phone_number($phone);
                error_log("OTP Login: Normalized phone: $normalized_phone");
                
                // Check for duplicate phone numbers (only deny admin/super admin numbers)
                $duplicate_check = check_phone_duplicate($normalized_phone);
                if ($duplicate_check['is_duplicate'] && in_array($duplicate_check['user_type'], ['admin', 'super admin'])) {
                    $error_message = "This phone number is already registered as a " . $duplicate_check['user_type'] . ". Please use a different number.";
                } else {
                    // Check if user exists and their registration status
                    $user_exists = user_exists_by_phone($normalized_phone);
                    
                    if ($user_exists) {
                        // User exists - check if registration is complete
                        if ($user_exists['registration_completed']) {
                            // Registration complete - direct login
                            $purpose = 'login';
                            error_log("OTP Login: Returning user with complete registration - direct login");
                        } else {
                            // Registration incomplete - show registration form
                            $purpose = 'registration';
                            error_log("OTP Login: Returning user with incomplete registration - show registration form");
                        }
                    } else {
                        // New user - show registration form
                        $purpose = 'registration';
                        error_log("OTP Login: New user - show registration form");
                    }
                    
                    // Generate and store OTP
                    $otp = generate_otp();
                    $email = $normalized_phone . '@manuelcode.local';
                    
                    if (store_otp($normalized_phone, $email, $otp, $purpose)) {
                        error_log("OTP Login: OTP stored successfully");
                        
                        // Send SMS
                        $message = "Your ManuelCode verification code is: $otp. Valid for 10 minutes.";
                        $sms_result = send_sms($normalized_phone, $message);
                        error_log("OTP Login: SMS result: " . json_encode($sms_result));
                        
                        if ($sms_result['success']) {
                            $_SESSION['otp_phone'] = $normalized_phone;
                            $_SESSION['otp_purpose'] = $purpose;
                            $show_otp_form = true;
                            $success_message = 'OTP sent successfully to your phone number.';
                            error_log("OTP Login: OTP sent successfully, showing OTP form");
                        } else {
                            $error_message = 'Failed to send OTP. Please try again.';
                            error_log("OTP Login: SMS sending failed: " . $sms_result['error']);
                        }
                    } else {
                        $error_message = 'Failed to generate OTP. Please try again.';
                        error_log("OTP Login: OTP storage failed");
                    }
                }
            }
        } elseif (isset($_POST['verify_otp'])) {
            error_log("OTP Login: Verify OTP request");
            $otp = trim($_POST['otp']);
            $phone = $_SESSION['otp_phone'] ?? '';
            $purpose = $_SESSION['otp_purpose'] ?? '';
            
            if (empty($otp) || empty($phone)) {
                $error_message = 'Invalid request. Please try again.';
            } else {
                $email = $phone . '@manuelcode.local';
                if (verify_otp($phone, $email, $otp, $purpose)) {
                    error_log("OTP Login: OTP verified successfully");
                    
                                         if ($purpose === 'login') {
                         // Existing user with complete registration - log them in directly
                         $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND registration_completed = TRUE");
                         $stmt->execute([$phone]);
                         $user = $stmt->fetch(PDO::FETCH_ASSOC);
                         
                         if ($user) {
                             $_SESSION['user_id'] = $user['id'];
                             $_SESSION['user_name'] = $user['name'];
                             $_SESSION['user_email'] = $user['email'];
                             $_SESSION['user_phone'] = $user['phone'];
                             $_SESSION['user_logged_in'] = true;
                             
                             // Update last_login timestamp
                             $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                             $stmt->execute([$user['id']]);
                             
                             // Log user activity
                             log_user_activity('OTP login successful - direct dashboard access', ['user_id' => $user['id'], 'activity_type' => 'login'], $user['id']);
                             
                             // Clear OTP session data
                             unset($_SESSION['otp_phone'], $_SESSION['otp_purpose']);
                             
                             error_log("OTP Login: User logged in successfully, redirecting to dashboard");
                             header('Location: ../../dashboard/');
                             exit;
                         } else {
                             $error_message = 'User account not found or incomplete. Please contact support.';
                             error_log("OTP Login: User not found or registration incomplete for phone: $phone");
                         }
                     } else {
                         // New user or incomplete registration - show registration form
                         $show_registration_form = true;
                         error_log("OTP Login: New user or incomplete registration, showing registration form");
                     }
                } else {
                    $error_message = 'Invalid OTP code. Please try again.';
                    error_log("OTP Login: OTP verification failed");
                }
            }
        } elseif (isset($_POST['register_user'])) {
            error_log("OTP Login: Register user request");
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = $_SESSION['otp_phone'] ?? '';
            
            if (empty($name) || empty($email) || empty($phone)) {
                $error_message = 'Please fill in all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = 'This email address is already registered.';
                } else {
                    // Check if user already exists with this phone number (incomplete registration)
                    $existing_user = user_exists_by_phone($phone);
                    
                                         if ($existing_user && !$existing_user['registration_completed']) {
                        // Generate unique user ID if not already set
                        $generator = new UserIDGenerator($pdo);
                        $unique_user_id = $existing_user['user_id'];
                        if (empty($unique_user_id) || strlen($unique_user_id) != 6) {
                            $unique_user_id = $generator->generateUserID($name);
                        }
                        
                        // Update existing user with complete registration and user ID
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, user_id = ?, registration_completed = TRUE WHERE phone = ?");
                        if ($stmt->execute([$name, $email, $unique_user_id, $phone])) {
                             $user_id = $existing_user['id'];
                             
                             // Log user in
                             $_SESSION['user_id'] = $user_id;
                             $_SESSION['user_name'] = $name;
                             $_SESSION['user_email'] = $email;
                             $_SESSION['user_phone'] = $phone;
                             $_SESSION['user_logged_in'] = true;
                             
                             // Update last_login timestamp for completed registrations
                             $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                             $stmt->execute([$user_id]);
                             
                             // Log user activity
                             log_user_activity('Completed registration via OTP - now ready for direct login', ['user_id' => $user_id, 'activity_type' => 'registration'], $user_id);
                             
                             // Clear OTP session data
                             unset($_SESSION['otp_phone'], $_SESSION['otp_purpose']);
                             
                             error_log("OTP Login: User registration completed, redirecting to dashboard");
                             header('Location: ../../dashboard/');
                             exit;
                         } else {
                             $error_message = 'Failed to update account. Please try again.';
                             error_log("OTP Login: Failed to update user registration for phone: $phone");
                         }
                    } else {
                        // Generate unique user ID
                        $generator = new UserIDGenerator($pdo);
                        $unique_user_id = $generator->generateUserID($name);
                        
                        // Create new user with registration completed flag and user ID
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, user_id, registration_completed, created_at) VALUES (?, ?, ?, ?, TRUE, NOW())");
                        if ($stmt->execute([$name, $email, $phone, $unique_user_id])) {
                             $user_id = $pdo->lastInsertId();
                             
                             // Log user in
                             $_SESSION['user_id'] = $user_id;
                             $_SESSION['user_name'] = $name;
                             $_SESSION['user_email'] = $email;
                             $_SESSION['user_phone'] = $phone;
                             $_SESSION['user_logged_in'] = true;
                             
                             // Update last_login timestamp for new users
                             $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                             $stmt->execute([$user_id]);
                             
                             // Log user activity
                             log_user_activity('New user registration via OTP - now ready for direct login', ['user_id' => $user_id, 'activity_type' => 'registration'], $user_id);
                             
                             // Clear OTP session data
                             unset($_SESSION['otp_phone'], $_SESSION['otp_purpose']);
                             
                             error_log("OTP Login: New user created successfully, redirecting to dashboard");
                             header('Location: ../../dashboard/');
                             exit;
                         } else {
                             $error_message = 'Failed to create account. Please try again.';
                             error_log("OTP Login: Failed to create new user for phone: $phone");
                         }
                    }
                }
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
    <meta name="format-detection" content="telephone=no">
    <title>Login - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="../assets/favi/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="../assets/favi/favicon.png" alt="ManuelCode" class="h-12 mx-auto mb-4">
            <p class="text-gray-600 mt-2">Enter your phone number to continue</p>
        </div>

        <!-- Main Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error_message): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$show_otp_form && !$show_registration_form): ?>
                <!-- Phone Number Form -->
                <form method="POST" id="phoneForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="mb-6">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">+233</span>
                            </div>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($phone); ?>"
                                   class="block w-full pl-12 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0241234567"
                                   pattern="[0-9]{10}"
                                   maxlength="10"
                                   required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Enter your full phone number including leading 0 (e.g., 0241234567)</p>
                    </div>

                    <button type="submit" 
                            name="send_otp" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Continue
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($show_otp_form): ?>
                <!-- OTP Verification Form -->
                <div class="text-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Enter Verification Code</h2>
                    <p class="text-gray-600">We've sent a 6-digit code to <strong><?php echo format_phone_for_display($_SESSION['otp_phone']); ?></strong></p>
                </div>

                <form method="POST" id="otpForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                                    <div class="mb-6">
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                    <input type="text" 
                           id="otp" 
                           name="otp" 
                           class="block w-full px-3 py-3 text-center text-xl font-bold border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter 6-digit code"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required>
                    <p class="text-xs text-gray-500 mt-1 text-center">Enter the 6-digit code sent to your phone</p>
                </div>

                    <button type="submit" 
                            name="verify_otp" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors mb-4">
                        Verify & Continue
                    </button>

                    <button type="button" 
                            onclick="resendOTP()" 
                            class="w-full text-blue-600 py-2 px-4 rounded-lg font-medium hover:bg-blue-50 transition-colors">
                        Resend Code
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($show_registration_form): ?>
                <!-- Registration Form -->
                <div class="text-center mb-6">
                    <?php 
                    $existing_user = user_exists_by_phone($_SESSION['otp_phone'] ?? '');
                    $is_completing_registration = $existing_user && !$existing_user['registration_completed'];
                    ?>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        <?php echo $is_completing_registration ? 'Complete Your Registration' : 'Create Your Account'; ?>
                    </h2>
                    <p class="text-gray-600">
                        <?php echo $is_completing_registration 
                            ? 'Please provide your details to complete your account setup' 
                            : 'Please provide your details to create your account'; ?>
                    </p>
                </div>

                <form method="POST" id="registrationForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo $is_completing_registration ? htmlspecialchars($existing_user['name'] ?? '') : ''; ?>"
                               class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter your full name"
                               required>
                    </div>

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo $is_completing_registration ? htmlspecialchars($existing_user['email'] ?? '') : ''; ?>"
                               class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter your email address"
                               required>
                    </div>

                    <button type="submit" 
                            name="register_user" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <?php echo $is_completing_registration ? 'Complete Registration' : 'Create Account'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-gray-600 text-sm">
                By continuing, you agree to our 
                <a href="../terms.php" class="text-blue-600 hover:underline">Terms of Service</a> 
                and 
                <a href="../privacy.php" class="text-blue-600 hover:underline">Privacy Policy</a>
            </p>
        </div>
    </div>

    <script>
        // Phone number formatting
        document.getElementById('phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });

        // Simple OTP input handling
        const otpInput = document.getElementById('otp');
        
        if (otpInput) {
            // Only allow numeric input
            otpInput.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
            });
            
            // Allow paste functionality for convenience
            otpInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
                if (pastedData.length <= 6) {
                    e.target.value = pastedData;
                }
            });
        }

        // Form submission logging and handling
        document.getElementById('phoneForm')?.addEventListener('submit', function(e) {
            console.log('Phone form submitted');
        });

        document.getElementById('otpForm')?.addEventListener('submit', function(e) {
            console.log('OTP form submitted');
            const otpValue = document.getElementById('otp').value;
            if (otpValue.length !== 6) {
                e.preventDefault();
                alert('Please enter the complete 6-digit code');
                return false;
            }
        });

        document.getElementById('registrationForm')?.addEventListener('submit', function(e) {
            console.log('Registration form submitted');
        });

        // Resend OTP function
        function resendOTP() {
            const phone = '<?php echo $_SESSION['otp_phone'] ?? ''; ?>';
            if (phone) {
                // Submit the phone form again to resend OTP
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="phone" value="${phone}">
                    <input type="hidden" name="send_otp" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
