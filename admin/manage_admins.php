<?php
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';

// Test database connection
if (!$pdo) {
    die('Database connection failed');
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin');
    exit;
}

// Check if user is superadmin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: auth/superadmin_login.php?error=invalid_role');
    exit;
}

// Super admin authentication is now properly checked above

$error_message = '';
$success_message = '';

// Handle admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'admin');
    
    // Security: Only allow admin, superadmin, and analyst roles
    if (!in_array($role, ['admin', 'superadmin', 'analyst'])) {
        $error_message = 'Invalid role selected.';
    } elseif (empty($name) || empty($email) || empty($phone)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Normalize phone number for storage
            $normalized_phone = format_phone_for_storage($phone);
            
            // Check if account already exists (check both admins and analysts tables)
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? OR phone = ?");
            $stmt->execute([$email, $normalized_phone]);
            if ($stmt->fetch()) {
                $error_message = 'An admin with this email or phone number already exists.';
            } else {
                // Also check analysts table
                $stmt = $pdo->prepare("SELECT id FROM analysts WHERE email = ? OR phone = ?");
                $stmt->execute([$email, $normalized_phone]);
                if ($stmt->fetch()) {
                    $error_message = 'An analyst with this email or phone number already exists.';
                } else {
                    // Handle different account types
                if ($role === 'analyst') {
                    // Create analyst account (OTP-only, no password)
                                         $stmt = $pdo->prepare("INSERT INTO analysts (name, email, phone, status, created_by, created_at) VALUES (?, ?, ?, 'active', ?, NOW())");
                     $created_by = $_SESSION['admin_id'] ?? null;
                     if ($stmt->execute([$name, $email, $normalized_phone, $created_by])) {
                        $success_message = 'Analyst account created successfully! Analyst can login using OTP only.';
                        
                        // Log the action
                        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
                        $stmt->execute(['INFO', 'ADMIN', "Super admin created new analyst account: {$name} ({$email})"]);
                    } else {
                        $error_message = 'Failed to create analyst account.';
                    }
                } else {
                    // Create admin/superadmin account (with password option)
                    $hashed_password = null;
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO admins (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$name, $email, $normalized_phone, $hashed_password, $role])) {
                        $success_message = ucfirst($role) . ' account created successfully!';
                        if (!empty($password)) {
                            $success_message .= ' Password has been set.';
                        } else {
                            $success_message .= ' Admin can login using OTP only.';
                        }
                        
                        // Log the action
                        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
                        $stmt->execute(['INFO', 'ADMIN', "Super admin created new {$role} account: {$name} ({$email})"]);
                    } else {
                        $error_message = 'Failed to create ' . $role . ' account.';
                    }
                }
            }
        }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle admin/analyst deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $account_id = (int)$_POST['admin_id'];
    $account_type = $_POST['account_type'] ?? 'admin'; // 'admin' or 'analyst'
    
    try {
        // Check if this is the default super admin (ID 1)
        if ($account_type === 'admin' && $account_id == 1) {
            $error_message = 'Cannot delete the default super admin account.';
        } else {
            if ($account_type === 'analyst') {
                // Delete analyst account
                $stmt = $pdo->prepare("SELECT name, email FROM analysts WHERE id = ?");
                $stmt->execute([$account_id]);
                $account_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($account_to_delete) {
                    $stmt = $pdo->prepare("DELETE FROM analysts WHERE id = ?");
                    if ($stmt->execute([$account_id])) {
                        $success_message = 'Analyst account deleted successfully!';
                        
                        // Log the action
                        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
                        $stmt->execute(['WARNING', 'ADMIN', "Super admin deleted analyst account: {$account_to_delete['name']} ({$account_to_delete['email']})"]);
                    } else {
                        $error_message = 'Failed to delete analyst account.';
                    }
                } else {
                    $error_message = 'Analyst account not found.';
                }
            } else {
                // Delete admin account
                $stmt = $pdo->prepare("SELECT name, email, role FROM admins WHERE id = ?");
                $stmt->execute([$account_id]);
                $admin_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin_to_delete) {
                    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                    if ($stmt->execute([$account_id])) {
                        $success_message = 'Account deleted successfully!';
                        
                        // Log the action
                        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
                        $stmt->execute(['WARNING', 'ADMIN', "Super admin deleted {$admin_to_delete['role']} account: {$admin_to_delete['name']} ({$admin_to_delete['email']})"]);
                    } else {
                        $error_message = 'Failed to delete account.';
                    }
                } else {
                    $error_message = 'Account not found.';
                }
            }
        }
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

// Handle admin status toggle (enable/disable)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $admin_id = (int)$_POST['admin_id'];
    try {
        // Check if this is the default super admin (ID 1)
        if ($admin_id == 1) {
            $error_message = 'Cannot modify the default super admin account status.';
        } else {
            // Get current status
            $stmt = $pdo->prepare("SELECT name, email, role, status FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                $new_status = $admin['status'] === 'active' ? 'inactive' : 'active';
                $stmt = $pdo->prepare("UPDATE admins SET status = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $admin_id])) {
                    $success_message = ucfirst($admin['role']) . ' account ' . $new_status . ' successfully!';
                    
                    // Log the action
                    $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
                    $stmt->execute(['INFO', 'ADMIN', "Super admin {$new_status} {$admin['role']} account: {$admin['name']} ({$admin['email']})"]);
                } else {
                    $error_message = 'Failed to update account status.';
                }
            } else {
                $error_message = 'Account not found.';
            }
        }
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

// Fetch all admins and analysts
try {
    // Fetch admins
    $stmt = $pdo->query("SELECT id, name, email, phone, password, role, status, created_at, last_login, login_method FROM admins ORDER BY role DESC, created_at DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch analysts and convert to same format
    $stmt = $pdo->query("SELECT id, name, email, phone, NULL as password, 'analyst' as role, status, created_at, last_login, 'otp' as login_method FROM analysts ORDER BY created_at DESC");
    $analysts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine both arrays
    $all_accounts = array_merge($admins, $analysts);
    
    // Debug: Log the number of accounts found
    error_log("Found " . count($admins) . " admin accounts and " . count($analysts) . " analyst accounts");
    
} catch (Exception $e) {
    $all_accounts = [];
    $error_message = 'Failed to load account list: ' . $e->getMessage();
    error_log("Account list error: " . $e->getMessage());
}

// Get statistics
$total_admins = count(array_filter($all_accounts, function($account) { return in_array($account['role'], ['admin', 'superadmin']); }));
$total_analysts = count(array_filter($all_accounts, function($account) { return $account['role'] === 'analyst'; }));
$active_admins = count(array_filter($all_accounts, function($account) { return $account['status'] !== 'inactive' && in_array($account['role'], ['admin', 'superadmin']); }));
$active_analysts = count(array_filter($all_accounts, function($account) { return $account['status'] !== 'inactive' && $account['role'] === 'analyst'; }));
$super_admins = count(array_filter($all_accounts, function($account) { return $account['role'] === 'superadmin'; }));
$regular_admins = count(array_filter($all_accounts, function($account) { return $account['role'] === 'admin'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-users-cog mr-2"></i>Manage Admins
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="superadmin.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Messages -->
            <?php if ($error_message): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3"></i>
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600">Total Admins</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $total_admins; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600">Active Admins</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $active_admins; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-crown text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600">Super Admins</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $super_admins; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-user-shield text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600">Regular Admins</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $regular_admins; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-chart-line text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600">Analysts</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $total_analysts; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Create Admin Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-user-plus mr-2"></i>Create New Account
                        </h2>
                        
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter full name">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="admin@example.com">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" id="admin_phone_input" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter phone number">
                                <p class="text-xs text-gray-500 mt-1">Enter phone number with or without country code</p>
                            </div>
                            
                            <div id="password-field">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password (Optional)</label>
                                <input type="password" name="password" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter password (min 6 characters)">
                                <p class="text-xs text-gray-500 mt-1">Leave empty for OTP-only login. If set, admin can use either OTP or password.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Super Admin</option>
                                    <option value="analyst">Analyst</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Select account role</p>
                            </div>
                            
                            <button type="submit" name="create_admin"
                                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Create Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Admin List -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">
                                <i class="fas fa-list mr-2"></i>All Accounts (<?php echo count($all_accounts); ?>)
                            </h2>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($admins)): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                                No accounts found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_accounts as $admin): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10">
                                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                                            </div>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <?php echo htmlspecialchars($admin['name']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($admin['email']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars(format_phone_for_display($admin['phone'])); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        <?php 
                                                        if ($admin['role'] === 'superadmin') echo 'bg-red-100 text-red-800';
                                                        elseif ($admin['role'] === 'analyst') echo 'bg-orange-100 text-orange-800';
                                                        else echo 'bg-blue-100 text-blue-800';
                                                        ?>">
                                                        <?php echo ucfirst($admin['role']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex flex-col space-y-1">
                                                        <?php if (!empty($admin['password'])): ?>
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                                <i class="fas fa-key mr-1"></i>Password
                                                            </span>
                                                        <?php endif; ?>
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            <i class="fas fa-mobile-alt mr-1"></i>OTP
                                                        </span>
                                                        <?php if ($admin['login_method']): ?>
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                                <i class="fas fa-clock mr-1"></i>Last: <?php echo ucfirst($admin['login_method']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        <?php echo ($admin['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                        <?php echo ucfirst($admin['status'] ?? 'active'); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($admin['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php 
                                                    if ($admin['last_login']) {
                                                        echo date('M j, Y g:i A', strtotime($admin['last_login']));
                                                    } else {
                                                        echo '<span class="text-gray-400">Never</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-2">
                                                        <a href="../dashboard/edit-admin?id=<?php echo $admin['id']; ?>&type=<?php echo $admin['role']; ?>" 
                                                           class="text-blue-600 hover:text-blue-900">
                                                            <i class="fas fa-edit mr-1"></i>Edit
                                                        </a>
                                                        
                                                        <?php if ($admin['role'] === 'analyst'): ?>
                                                            <!-- Analyst - Allow deletion -->
                                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this analyst account? This action cannot be undone.')">
                                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                <input type="hidden" name="account_type" value="analyst">
                                                                <button type="submit" name="delete_admin" 
                                                                        class="text-red-600 hover:text-red-900">
                                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                                </button>
                                                            </form>
                                                        <?php elseif ($admin['id'] != 1): ?>
                                                            <!-- Admin/Superadmin - Allow status toggle and deletion -->
                                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to change the status of this account?')">
                                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                <button type="submit" name="toggle_status" 
                                                                        class="text-yellow-600 hover:text-yellow-900">
                                                                    <i class="fas fa-toggle-on mr-1"></i>
                                                                    <?php echo ($admin['status'] ?? 'active') === 'active' ? 'Disable' : 'Enable'; ?>
                                                                </button>
                                                            </form>
                                                            
                                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone.')">
                                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                <input type="hidden" name="account_type" value="admin">
                                                                <button type="submit" name="delete_admin" 
                                                                        class="text-red-600 hover:text-red-900">
                                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 text-xs">Protected</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Phone number formatting for admin creation - more flexible
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('admin_phone_input');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Allow digits, spaces, dashes, and plus sign
                    let value = this.value.replace(/[^\d\s\-\+]/g, '');
                    
                    // Limit to reasonable length (15 chars max)
                    value = value.substring(0, 15);
                    
                    this.value = value;
                });
            }
            
            // Handle role selection for password field visibility
            const roleSelect = document.querySelector('select[name="role"]');
            const passwordField = document.getElementById('password-field');
            const passwordInput = document.querySelector('input[name="password"]');
            
            if (roleSelect && passwordField) {
                function togglePasswordField() {
                    if (roleSelect.value === 'analyst') {
                        passwordField.style.display = 'none';
                        passwordInput.value = ''; // Clear password for analysts
                    } else {
                        passwordField.style.display = 'block';
                    }
                }
                
                // Set initial state
                togglePasswordField();
                
                // Listen for changes
                roleSelect.addEventListener('change', togglePasswordField);
            }
        });
    </script>
</body>
</html>
