<?php
/**
 * Unified Admin Login Page
 * Handles both admin and superadmin login
 * Route: manuelcode.info/admin
 */
session_start();
include 'includes/db.php';
include 'includes/otp_helper.php';

// Clean up expired data
cleanup_expired_data();

$error_message = '';
$success_message = '';
$show_otp_form = false;
$show_password_form = false;
$email = '';
$phone = '';
$login_method = '';

// If already logged in, redirect to unified dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard/');
    exit;
}

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
            // Check if admin exists with this email (both regular and super admin)
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, phone, password FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    // Determine if superadmin or regular admin
                    $is_superadmin = (stripos($admin['name'], 'Super Admin') !== false || stripos($admin['name'], 'superadmin') !== false);
                    
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
                                    $_SESSION['pending_admin_email'] = $email;
                                    $_SESSION['pending_admin_phone'] = $normalized_phone;
                                    $_SESSION['pending_is_superadmin'] = $is_superadmin;
                                } else {
                                    $error_message = 'Failed to send OTP: ' . $sms_result['error'];
                                }
                            } else {
                                $error_message = 'Failed to generate OTP. Please try again.';
                            }
                        }
                    } else {
                        $show_password_form = true;
                        $_SESSION['pending_admin_email'] = $email;
                        $_SESSION['pending_is_superadmin'] = $is_superadmin;
                    }
                } else {
                    $error_message = 'No admin account found with this email address.';
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
                error_log("Admin login error: " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $email = $_SESSION['pending_admin_email'] ?? trim($_POST['email']);
        $phone = $_SESSION['pending_admin_phone'] ?? trim($_POST['phone']);
        $otp_code = trim($_POST['otp_code']);
        $is_superadmin = $_SESSION['pending_is_superadmin'] ?? false;

        // Normalize phone number
        $normalized_phone = normalize_phone_number($phone);
        
        // Verify OTP for admin login
        $verified = verify_otp($normalized_phone, $email, $otp_code, 'admin_login');
        
        if ($verified) {
            // Get admin details
            try {
                $stmt = $pdo->prepare("SELECT id, name, phone FROM admins WHERE email = ? AND phone = ?");
                $stmt->execute([$email, $normalized_phone]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    // Update last login and login method
                    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW(), login_method = 'otp' WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Create admin session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_phone'] = $normalized_phone;
                    $_SESSION['user_role'] = $is_superadmin ? 'superadmin' : 'admin';
                    $_SESSION['admin_login_time'] = time();
                    
                    // Clear pending session data
                    unset($_SESSION['pending_admin_email']);
                    unset($_SESSION['pending_admin_phone']);
                    unset($_SESSION['pending_is_superadmin']);
                    
                    // Redirect to unified dashboard
                    header('Location: dashboard/');
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
        $email = $_SESSION['pending_admin_email'] ?? trim($_POST['email']);
        $password = $_POST['password'];
        $is_superadmin = $_SESSION['pending_is_superadmin'] ?? false;
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please enter both email and password.';
            $show_password_form = true;
        } else {
            try {
                // Get admin details
                $stmt = $pdo->prepare("SELECT id, name, email, password FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Determine role
                    $is_superadmin = (stripos($admin['name'], 'Super Admin') !== false || stripos($admin['name'], 'superadmin') !== false);
                    
                    // Update last login and login method
                    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW(), login_method = 'password' WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Create admin session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['user_role'] = $is_superadmin ? 'superadmin' : 'admin';
                    $_SESSION['admin_login_time'] = time();
                    
                    // Clear pending session data
                    unset($_SESSION['pending_admin_email']);
                    unset($_SESSION['pending_is_superadmin']);
                    
                    // Redirect to unified dashboard
                    header('Location: dashboard/');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #4a5568;
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="mb-4">
                <i class="fas fa-shield-alt text-4xl text-purple-600 mb-2"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Login</h1>
            <p class="text-gray-600">Administrator & Super Admin Access</p>
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

        <?php if (!$show_otp_form && !$show_password_form): ?>
            <!-- Choose Login Method -->
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Login Method</label>
                    <select name="login_method" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="password">Password</option>
                        <option value="otp">OTP (SMS)</option>
                    </select>
                </div>

                <button type="submit" name="choose_method"
                        class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                    Continue
                </button>
            </form>
        <?php elseif ($show_otp_form): ?>
            <!-- OTP Verification Form -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Enter OTP Code</label>
                    <input type="text" name="otp_code" required maxlength="6" pattern="[0-9]{6}"
                           placeholder="000000"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-center text-2xl tracking-widest">
                    <p class="text-sm text-gray-500 mt-2">Enter the 6-digit code sent to your phone</p>
                </div>

                <button type="submit" name="verify_otp"
                        class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                    Verify OTP
                </button>

                <a href="admin" class="block text-center text-purple-600 hover:text-purple-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to login
                </a>
            </form>
        <?php elseif ($show_password_form): ?>
            <!-- Password Login Form -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <button type="submit" name="login_password"
                        class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                    Login
                </button>

                <a href="admin" class="block text-center text-purple-600 hover:text-purple-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to login
                </a>
            </form>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="login" class="text-gray-600 hover:text-gray-800">User Login</a>
        </div>
    </div>
</body>
</html>

