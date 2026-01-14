<?php
/**
 * User Login Page
 * Route: manuelcode.info/login
 */
session_start();
include 'includes/db.php';
include 'includes/otp_helper.php';
include 'includes/logger.php';
include 'includes/user_id_generator.php';

// Clean up expired data
cleanup_expired_data();

$error_message = '';
$success_message = '';
$show_otp_form = false;
$show_registration_form = false;
$phone = '';
$csrf_token = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/');
    exit;
}

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
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Security validation failed. Please try again.';
    } else {
        if (isset($_POST['send_otp'])) {
            $phone = trim($_POST['phone']);
            
            // Validate phone number
            if (empty($phone)) {
                $error_message = 'Please enter your phone number.';
            } elseif (!validate_phone_number($phone)) {
                $error_message = 'Please enter a valid Ghana phone number (10 digits including leading 0, e.g., 0241234567).';
            } else {
                // Normalize phone number
                $normalized_phone = normalize_phone_number($phone);
                
                // Check if user exists
                try {
                    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE phone = ?");
                    $stmt->execute([$normalized_phone]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        // Send OTP
                        $otp_code = generate_otp();
                        if (store_otp($normalized_phone, $user['email'] ?? '', $otp_code, 'user_login')) {
                            $sms_result = send_otp_sms($normalized_phone, $otp_code, 'user_login');
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
                        // User doesn't exist, show registration option
                        $show_registration_form = true;
                        $error_message = 'No account found with this phone number. Would you like to register?';
                    }
                } catch (Exception $e) {
                    $error_message = 'Database error: ' . $e->getMessage();
                    error_log("User login error: " . $e->getMessage());
                }
            }
        } elseif (isset($_POST['verify_otp'])) {
            $phone = trim($_POST['phone']);
            $otp_code = trim($_POST['otp_code']);
            
            // Normalize phone number
            $normalized_phone = normalize_phone_number($phone);
            
            // Get user email for OTP verification (use same email that was used when storing OTP)
            try {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE phone = ?");
                $stmt->execute([$normalized_phone]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                // Use email from database, or empty string if not set (must match what was stored)
                $email = $user['email'] ?? '';
            } catch (Exception $e) {
                $email = '';
            }
            
            // Debug logging
            error_log("User Login OTP Verify - Phone: $normalized_phone, Email: '$email', OTP: $otp_code, Purpose: user_login");
            
            // Verify OTP - for user_login, email is not required for matching
            $verified = verify_otp($normalized_phone, $email, $otp_code, 'user_login');
            
            if ($verified) {
                // Get user details
                try {
                    $stmt = $pdo->prepare("SELECT id, name, email, phone, role FROM users WHERE phone = ?");
                    $stmt->execute([$normalized_phone]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        // Create user session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_phone'] = $user['phone'];
                        $_SESSION['user_role'] = $user['role'] ?? 'user';
                        
                        // Redirect to dashboard
                        header('Location: dashboard/');
                        exit;
                    }
                } catch (Exception $e) {
                    $error_message = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error_message = 'The verification code you entered is incorrect or has expired. Please request a new code and try again.';
                $show_otp_form = true;
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
    <title>User Login - ManuelCode | Sign In to Your Account</title>
    <link rel="icon" type="image/svg+xml" href="assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6">
        <div class="text-center mb-6">
            <div class="mb-3">
                <i class="fas fa-user-circle text-3xl text-blue-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">User Login</h1>
            <p class="text-sm text-gray-600">Sign in to your account</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$show_otp_form && !$show_registration_form): ?>
            <!-- Phone Number Form -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required
                           placeholder="0241234567"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-2">Enter your Ghana phone number</p>
                </div>

                <button type="submit" name="send_otp"
                        class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                    Send OTP
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="auth/register.php" class="text-purple-600 hover:text-purple-700">Don't have an account? Register</a>
            </div>
        <?php elseif ($show_otp_form): ?>
            <!-- OTP Verification Form -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Enter OTP Code</label>
                    <input type="text" name="otp_code" required maxlength="6" pattern="[0-9]{6}"
                           placeholder="000000"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-center text-2xl tracking-widest">
                    <p class="text-sm text-gray-500 mt-2">Enter the 6-digit code sent to <?php echo htmlspecialchars($phone); ?></p>
                </div>

                <button type="submit" name="verify_otp"
                        class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                    Verify OTP
                </button>

                <a href="login" class="block text-center text-purple-600 hover:text-purple-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to login
                </a>
            </form>
        <?php endif; ?>

    </div>
</body>
</html>

