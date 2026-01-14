<?php
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';

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

$error_message = '';
$success_message = '';
$admin = null;

// Get account ID and type from URL
$account_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$account_type = isset($_GET['type']) ? $_GET['type'] : 'admin'; // 'admin' or 'analyst'

if (!$account_id) {
    header('Location: manage_admins.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = trim($_POST['role'] ?? 'admin');
    
    if (empty($name) || empty($email) || empty($phone)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Normalize phone number for storage
            $normalized_phone = format_phone_for_storage($phone);
            
            if ($account_type === 'analyst') {
                // Update analyst account
                // Check if email/phone already exists with another analyst
                $stmt = $pdo->prepare("SELECT id FROM analysts WHERE (email = ? OR phone = ?) AND id != ?");
                $stmt->execute([$email, $normalized_phone, $account_id]);
                if ($stmt->fetch()) {
                    $error_message = 'An analyst with this email or phone number already exists.';
                } else {
                    // Also check admins table
                    $stmt = $pdo->prepare("SELECT id FROM admins WHERE (email = ? OR phone = ?)");
                    $stmt->execute([$email, $normalized_phone]);
                    if ($stmt->fetch()) {
                        $error_message = 'An admin with this email or phone number already exists.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE analysts SET name = ?, email = ?, phone = ? WHERE id = ?");
                        if ($stmt->execute([$name, $email, $normalized_phone, $account_id])) {
                            $success_message = 'Analyst account updated successfully!';
                            // Refresh analyst data
                            $stmt = $pdo->prepare("SELECT * FROM analysts WHERE id = ?");
                            $stmt->execute([$account_id]);
                            $account = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $error_message = 'Failed to update analyst account.';
                        }
                    }
                }
            } else {
                // Update admin account
                // Check if email/phone already exists with another admin
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE (email = ? OR phone = ?) AND id != ?");
                $stmt->execute([$email, $normalized_phone, $account_id]);
                if ($stmt->fetch()) {
                    $error_message = 'An admin with this email or phone number already exists.';
                } else {
                    // Also check analysts table
                    $stmt = $pdo->prepare("SELECT id FROM analysts WHERE (email = ? OR phone = ?)");
                    $stmt->execute([$email, $normalized_phone]);
                    if ($stmt->fetch()) {
                        $error_message = 'An analyst with this email or phone number already exists.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                        if ($stmt->execute([$name, $email, $normalized_phone, $role, $account_id])) {
                            $success_message = 'Account updated successfully!';
                            // Refresh admin data
                            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
                            $stmt->execute([$account_id]);
                            $account = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $error_message = 'Failed to update account.';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch account data
if (!isset($account)) {
    try {
        if ($account_type === 'analyst') {
            $stmt = $pdo->prepare("SELECT * FROM analysts WHERE id = ?");
            $stmt->execute([$account_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$account) {
                header('Location: manage_admins.php');
                exit;
            }
            
            // Set default role for analyst
            $account['role'] = 'analyst';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$account_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$account) {
                header('Location: manage_admins.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = 'Failed to load account data.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin - Super Admin</title>
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
                            <i class="fas fa-edit mr-2"></i>Edit Account
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="manage_admins.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Manage Admins
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
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

            <!-- Edit Form -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-user-edit mr-2"></i>Edit <?php echo ucfirst($account_type); ?>: <?php echo htmlspecialchars($account['name']); ?>
                </h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="account_type" value="<?php echo $account_type; ?>">
                    <input type="hidden" name="account_id" value="<?php echo $account_id; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($account['name']); ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter full name">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($account['email']); ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="<?php echo $account_type === 'analyst' ? 'analyst@example.com' : 'admin@example.com'; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" id="admin_phone_input" value="<?php echo htmlspecialchars(format_phone_for_display($account['phone'])); ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter phone number">
                            <p class="text-xs text-gray-500 mt-1">Enter phone number with or without country code</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                            <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                                <span class="text-sm text-gray-900 font-medium">
                                    <?php 
                                    if ($account_type === 'analyst') {
                                        echo '<i class="fas fa-chart-line text-orange-500 mr-2"></i>Analyst (OTP-only login)';
                                    } elseif ($account['role'] === 'superadmin') {
                                        echo '<i class="fas fa-crown text-red-500 mr-2"></i>Super Admin';
                                    } else {
                                        echo '<i class="fas fa-user-shield text-blue-500 mr-2"></i>Admin';
                                    }
                                    ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php if ($account_type === 'analyst'): ?>
                                    Analyst accounts use OTP-only login and cannot be changed to admin roles
                                <?php elseif ($account['role'] === 'superadmin' && $account['id'] == 1): ?>
                                    Default super admin account - role cannot be changed
                                <?php else: ?>
                                    Select account role for admin accounts
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if ($account_type !== 'analyst'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" <?php echo ($account['role'] === 'superadmin' && $account['id'] == 1) ? 'disabled' : ''; ?>>
                                <option value="admin" <?php echo $account['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="superadmin" <?php echo $account['role'] === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select account role</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <a href="manage_admins.php" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" name="update_admin"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Phone number formatting for admin editing - more flexible
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
        });
    </script>
</body>
</html>
