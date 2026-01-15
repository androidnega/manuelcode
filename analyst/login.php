<?php
session_start();
include '../includes/db.php';

$error_message = '';
$success_message = '';

// Check if already logged in
if (isset($_SESSION['analyst_logged_in']) && $_SESSION['analyst_logged_in'] === true) {
    header('Location: /dashboard');
    exit;
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone_last_4 = trim($_POST['phone_last_4'] ?? '');
    
    if (empty($email) || empty($phone_last_4)) {
        $error_message = 'Please enter both email and last 4 digits of phone number.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{4}$/', $phone_last_4)) {
        $error_message = 'Please enter exactly 4 digits for phone number.';
    } else {
        try {
            // Check if analyst exists and is active
            $stmt = $pdo->prepare("SELECT id, name, email, phone, status FROM analysts WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $analyst = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$analyst) {
                $error_message = 'No active analyst account found with this email address.';
            } else {
                // Check if analyst has a phone number
                if (empty($analyst['phone'])) {
                    $error_message = 'Phone number not found for this analyst account. Please contact administrator.';
                } else {
                    // Get last 4 digits of stored phone number
                    $stored_phone = $analyst['phone'];
                    $stored_last_4 = substr($stored_phone, -4);
                    
                    // Compare with entered last 4 digits
                    if ($phone_last_4 === $stored_last_4) {
                        // Authentication successful - log in the analyst
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
                        
                        // Redirect to dashboard
                        header('Location: /dashboard');
                        exit;
                    } else {
                        $error_message = 'Invalid phone number digits. Please check the last 4 digits of your registered phone number.';
                        
                        // Log failed login attempt
                        $stmt = $pdo->prepare("
                            INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                            VALUES (?, 'login_failed', 'Failed login attempt - incorrect phone digits', ?, ?, NOW())
                        ");
                        $stmt->execute([$analyst['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
            error_log("Analyst login error: " . $e->getMessage());
        }
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
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-br from-indigo-100 to-purple-100">
                    <i class="fas fa-chart-line text-indigo-600 text-xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-800">
                    Analyst Login
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600">
                    Access project submissions and analytics
                </p>
            </div>

            <div class="bg-gradient-to-br from-slate-50 to-gray-50 py-8 px-6 shadow-lg rounded-xl border border-slate-200">
                <!-- Error Message -->
                <?php if ($error_message): ?>
                    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg shadow-sm">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-rose-500 mr-3"></i>
                            <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="space-y-6" id="loginForm">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">
                            Email Address
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required
                                   class="appearance-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-400 text-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm shadow-sm"
                                   placeholder="Enter your email address"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <label for="phone_last_4" class="block text-sm font-medium text-slate-700">
                            Last 4 Digits of Phone Number
                        </label>
                        <div class="mt-1">
                            <input id="phone_last_4" name="phone_last_4" type="text" maxlength="4" required
                                   class="appearance-none relative block w-full px-3 py-3 border border-slate-300 placeholder-slate-400 text-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm text-center text-xl tracking-widest shadow-sm"
                                   placeholder="0000"
                                   value="<?php echo htmlspecialchars($_POST['phone_last_4'] ?? ''); ?>">
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Enter the last 4 digits of your registered phone number
                        </p>
                    </div>

                    <div>
                        <button type="submit" id="loginBtn"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            <span id="loginBtnText">Login</span>
                            <div id="loginSpinner" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="../index.php" class="text-sm text-indigo-600 hover:text-indigo-500 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginSpinner = document.getElementById('loginSpinner');
            const phoneInput = document.getElementById('phone_last_4');

            // Only allow numbers in phone input
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Form submission with loading state
            form.addEventListener('submit', function() {
                // Show loading state
                loginBtn.disabled = true;
                loginBtnText.textContent = 'Logging in...';
                loginSpinner.classList.remove('hidden');
                
                // Form will submit normally
            });

            // Auto-focus email input on page load
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
