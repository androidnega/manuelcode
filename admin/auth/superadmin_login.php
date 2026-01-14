<?php
session_start();
include '../../includes/db.php';

$error_message = '';
$success_message = '';

// Handle timeout message
if (isset($_GET['message']) && $_GET['message'] === 'timeout') {
    $error_message = 'Your session has expired due to inactivity. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Check if super admin exists with this username
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, phone FROM admins WHERE email = ? AND name LIKE '%Super Admin%'");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // For now, we'll use a simple password check
                // In production, you should use password_hash() and password_verify()
                if ($password === 'superadmin123') { // Default password - change this
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Create super admin session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_phone'] = $admin['phone'];
                    $_SESSION['user_role'] = 'superadmin'; // Super admin role
                    $_SESSION['admin_login_time'] = time();
                    
                    header('Location: ../superadmin.php');
                    exit;
                } else {
                    $error_message = 'Invalid password.';
                }
            } else {
                $error_message = 'Super admin account not found.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-50 to-red-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mb-4">
                <i class="fas fa-crown text-red-600 text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Super Admin Login</h1>
            <p class="text-gray-600 text-sm">Access super admin controls</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-3 mb-4 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-400 mr-2 text-sm"></i>
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-3 mb-4 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-400 mr-2 text-sm"></i>
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Username/Password Form -->
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username/Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                        <i class="fas fa-user text-sm"></i>
                    </span>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                                                           placeholder="Enter your email" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                        <i class="fas fa-lock text-sm"></i>
                    </span>
                    <input type="password" name="password" 
                           class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                           placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors font-medium text-sm">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500 mb-2">
                <i class="fas fa-crown mr-1"></i>
                Super Admin Access Only
            </p>
            <a href="login.php" class="text-xs text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i>Regular Admin Login
            </a>
        </div>
    </div>
</body>
</html>
