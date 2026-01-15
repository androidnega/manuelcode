<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/auth_helper.php';
requireLogin();
include '../includes/db.php';
include '../includes/otp_helper.php';

$user = getCurrentUser();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (empty($name) || empty($email) || empty($phone)) {
            $error_message = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Format phone number
            $phone = format_phone_for_storage($phone);
            
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error_message = 'This email address is already registered by another user.';
            } else {
                // Check if phone already exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
                $stmt->execute([$phone, $user['id']]);
                if ($stmt->fetch()) {
                    $error_message = 'This phone number is already registered by another user.';
                } else {
                    // Update user profile
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$name, $email, $phone, $user['id']])) {
                        $success_message = 'Profile updated successfully!';
                        // Update session data
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $user['name'] = $name;
                        $user['email'] = $email;
                        $user['phone'] = $phone;
                    } else {
                        $error_message = 'Failed to update profile. Please try again.';
                    }
                }
            }
        }
    } elseif (isset($_POST['delete_account'])) {
        // Handle account deletion
        $confirm_email = trim($_POST['confirm_email']);
        
        if ($confirm_email !== $user['email']) {
            $error_message = 'Email confirmation does not match. Please enter your email correctly.';
        } else {
            // Delete user account and related data
            try {
                $pdo->beginTransaction();
                
                // Delete user's purchases
                $stmt = $pdo->prepare("DELETE FROM purchases WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Delete user's notifications
                $stmt = $pdo->prepare("DELETE FROM product_notifications WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Delete user's support tickets
                $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Delete user's refund requests
                $stmt = $pdo->prepare("DELETE FROM refunds WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Delete user's sessions
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                // Finally delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                $pdo->commit();
                
                // Redirect to logout
                header('Location: /auth/logout.php?deleted=1');
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Failed to delete account. Please try again.';
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
    <title>Settings - ManuelCode</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .mobile-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #d1d5db;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        /* Desktop-first responsive styles */
        @media (min-width: 1024px) {
            .settings-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }
            .settings-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
            }
            .settings-full-width {
                grid-column: 1 / -1;
            }
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            .mobile-header {
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 30;
                background: white;
                border-bottom: 1px solid #e5e7eb;
            }
            .mobile-title {
                font-size: 1.25rem;
                font-weight: 600;
            }
            .settings-grid {
                display: block;
            }
            .settings-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Layout Container -->
    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="font-bold text-xl text-gray-800">Dashboard</div>
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto scrollbar-hide">
                <nav class="mt-4 px-4 pb-4">
                    <a href="" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                        <span class="flex-1">Overview</span>
                    </a>
                    <a href="my-purchases" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
                        <span class="flex-1">My Purchases</span>
                    </a>
                    <a href="downloads" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-download mr-3 w-5 text-center"></i>
                        <span class="flex-1">Downloads</span>
                    </a>
                    <a href="refunds" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-undo mr-3 w-5 text-center"></i>
                        <span class="flex-1">Refunds</span>
                    </a>
                    <a href="support" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-headset mr-3 w-5 text-center"></i>
                        <span class="flex-1">Support</span>
                    </a>
                    <a href="settings" class="flex items-center py-3 px-4 bg-blue-50 text-blue-700 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-cog mr-3 w-5 text-center"></i>
                        <span class="flex-1">Settings</span>
                    </a>
                </nav>
            </div>
            
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($user['user_id'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <a href="/auth/logout.php" class="flex items-center py-2 px-4 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0 min-h-screen">
            <!-- Desktop Header -->
            <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
                        <p class="text-gray-600 mt-1">Manage your account preferences and profile</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-home mr-2"></i>Home
                        </a>
                        <a href="/store" class="text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-store mr-2"></i>Store
                        </a>
                    </div>
                </div>
            </header>

            <!-- Mobile Header -->
            <header class="lg:hidden bg-white border-b border-gray-200 mobile-header">
                <div class="flex items-center justify-between">
                    <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="mobile-title font-semibold text-gray-800">Settings</h1>
                    <div class="w-8"></div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="settings-container">
                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <!-- Profile Section -->
                    <div class="dashboard-card p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-lg"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
                                <p class="text-sm text-gray-500">Update your personal details</p>
                            </div>
                        </div>

                        <form method="POST" class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>"
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       required>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       required>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone"
                                       value="<?php echo format_phone_for_display($user['phone']); ?>"
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="Enter your phone number"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Format: 0241234567 or +233241234567</p>
                            </div>

                            <button type="submit" 
                                    name="update_profile" 
                                    class="w-full btn-primary py-3 px-4">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div class="dashboard-card p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Account Information</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm text-gray-600">Member Since</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm text-gray-600">Account Status</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>Active
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-sm text-gray-600">User ID</span>
                                <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($user['user_id'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card p-6 settings-full-width">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="/store" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-store text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Browse Store</p>
                                    <p class="text-sm text-gray-500">Find new products</p>
                                </div>
                            </a>

                            <a href="/contact" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-headset text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Get Support</p>
                                    <p class="text-sm text-gray-500">Contact our team</p>
                                </div>
                            </a>

                            <a href="../about.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-info-circle text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">About ManuelCode</p>
                                    <p class="text-sm text-gray-500">Learn more about us</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="dashboard-card p-6 settings-full-width">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Account Actions</h2>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button onclick="confirmLogout()" class="flex-1 flex items-center justify-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors text-red-600">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </button>
                            <button onclick="showDeleteAccountModal()" class="flex-1 flex items-center justify-center p-4 bg-red-600 rounded-lg hover:bg-red-700 transition-colors text-white">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Account
                            </button>
                        </div>
                    </div>

                    <!-- Delete Account Modal -->
                    <div id="deleteAccountModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                        <div class="flex items-center justify-center min-h-screen p-4">
                            <div class="bg-white rounded-lg max-w-md w-full p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900">Delete Account</h3>
                                </div>
                                <p class="text-gray-600 mb-4">
                                    This action cannot be undone. All your data including purchases, support tickets, and account information will be permanently deleted.
                                </p>
                                <form method="POST" class="space-y-4">
                                    <div>
                                        <label for="confirm_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Confirm your email address
                                        </label>
                                        <input type="email" 
                                               id="confirm_email" 
                                               name="confirm_email" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                               placeholder="Enter your email address"
                                               required>
                                    </div>
                                    <div class="flex space-x-3">
                                        <button type="button" 
                                                onclick="hideDeleteAccountModal()" 
                                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit" 
                                                name="delete_account" 
                                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                            Delete Account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Confirm logout
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '/auth/logout.php';
            }
        }

        // Show delete account modal
        function showDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        // Hide delete account modal
        function hideDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteAccountModal');
            if (event.target === modal) {
                hideDeleteAccountModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('#sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('mobile-overlay');
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>
