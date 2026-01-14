<?php
session_start();
include '../../includes/db.php';
include '../../includes/otp_helper.php';

// Clean up expired data
cleanup_expired_data();

$error_message = '';
$success_message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please enter both email and password.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, phone, password FROM support_agents WHERE email = ? AND status = 'active'");
                $stmt->execute([$email]);
                $agent = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($agent && password_verify($password, $agent['password'])) {
                                         // Login successful
                     $_SESSION['support_agent_id'] = $agent['id'];
                     $_SESSION['support_agent_name'] = $agent['name'];
                     $_SESSION['support_agent_email'] = $agent['email'];
                     $_SESSION['support_agent_phone'] = $agent['phone'];
                     $_SESSION['support_agent_logged_in'] = true;
                     $_SESSION['user_role'] = 'support_agent';
                     $_SESSION['support_agent_login_time'] = time();
                     
                     // Update last login
                     $stmt = $pdo->prepare("UPDATE support_agents SET last_login = NOW() WHERE id = ?");
                     $stmt->execute([$agent['id']]);
                     
                     // Redirect to support dashboard
                     header('Location: ../../dashboard/support-dashboard');
                     exit;
                } else {
                    $error_message = 'Invalid email or password.';
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
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
    <title>Support Agent Login - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="../../assets/favi/favicon.png" alt="ManuelCode" class="h-12 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Support Agent Login</h1>
            <p class="text-gray-600 mt-2">Access the support dashboard</p>
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

                         <!-- Single Login Form -->
             <form method="POST" class="space-y-6">
                 <div>
                     <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                     <input type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email); ?>"
                            class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter your email address"
                            required>
                 </div>
 
                 <div>
                     <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                     <input type="password" 
                            id="password" 
                            name="password" 
                            class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter your password"
                            required>
                 </div>
 
                 <button type="submit" 
                         name="login_password" 
                         class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                     Login
                 </button>
             </form>

            

            
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-gray-600 text-sm">
                <a href="login.php" class="text-blue-600 hover:underline">← Back to Admin Login</a>
            </p>
        </div>
    </div>


</body>
</html>
