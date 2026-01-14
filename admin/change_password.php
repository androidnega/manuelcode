<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

$admin_username = $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? '';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'New password must be at least 8 characters long.';
    } else {
        try {
            // Get current admin password
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($current_password, $admin['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                
                $success_message = 'Password updated successfully!';
            } else {
                $error_message = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Change Password - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Prevent horizontal overflow */
    body {
      overflow-x: hidden;
      max-width: 100vw;
    }
    
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
    
    /* Enhanced Mobile Responsiveness */
    @media (max-width: 1024px) {
      .main-content {
        padding: 1rem;
        width: 100%;
        max-width: 100%;
      }
      .mobile-header {
        padding: 1rem;
        position: sticky;
        top: 0;
        z-index: 30;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
      }
      .mobile-title {
        font-size: 1.25rem;
        font-weight: 600;
      }
      .form-container {
        width: 100%;
        max-width: 100%;
      }
      .form-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
    }
    
    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
        width: 100%;
        max-width: 100%;
      }
      .mobile-header {
        padding: 1rem;
        position: sticky;
        top: 0;
        z-index: 30;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
      }
      .mobile-title {
        font-size: 1.25rem;
        font-weight: 600;
      }
      .mobile-button {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        border-radius: 8px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .mobile-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        width: 100%;
      }
      .form-container {
        width: 100%;
        max-width: 100%;
        padding: 1rem;
      }
      .form-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
      .security-tips {
        padding: 1rem;
        margin-top: 1rem;
      }
    }
    
    @media (max-width: 640px) {
      .main-content {
        padding: 0.75rem;
        width: 100%;
        max-width: 100%;
      }
      .mobile-header {
        padding: 0.75rem;
      }
      .mobile-title {
        font-size: 1.125rem;
      }
      .mobile-button {
        padding: 0.875rem 1.25rem;
        font-size: 0.9rem;
      }
      .form-container {
        padding: 0.75rem;
      }
      .form-grid {
        gap: 1.25rem;
      }
      .security-tips {
        padding: 0.75rem;
        margin-top: 0.75rem;
      }
      .button-group {
        flex-direction: column;
        gap: 0.75rem;
      }
      .button-group > * {
        width: 100%;
        text-align: center;
      }
    }
    
    @media (max-width: 480px) {
      .main-content {
        padding: 0.5rem;
        width: 100%;
        max-width: 100%;
      }
      .mobile-header {
        padding: 0.5rem;
      }
      .mobile-title {
        font-size: 1rem;
      }
      .mobile-button {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
      }
      .form-container {
        padding: 0.5rem;
      }
      .form-grid {
        gap: 1rem;
      }
      .security-tips {
        padding: 0.5rem;
        margin-top: 0.5rem;
      }
      .button-group {
        gap: 0.5rem;
      }
    }
    
    /* Touch-friendly improvements */
    @media (hover: none) and (pointer: coarse) {
      .mobile-button,
      .action-button {
        min-height: 44px;
        touch-action: manipulation;
      }
    }
    
    /* Enhanced sidebar for mobile */
    @media (max-width: 1024px) {
      .main-content {
        max-width: 100%;
        overflow-x: hidden;
      }
      #sidebar {
        width: 280px;
        max-width: 85vw;
      }
      
      #sidebar a {
        padding: 1rem 1.25rem;
        font-size: 1rem;
        min-height: 48px;
      }
      
      #sidebar i {
        font-size: 1.125rem;
        width: 1.5rem;
      }
    }
  </style>
</head>
<body class="bg-[#F4F4F9]">
  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

  <!-- Layout Container -->
  <div class="flex">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 bg-[#2D3E50] text-white transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
      <div class="flex items-center justify-between p-6 border-b border-[#243646] lg:border-none">
        <div class="font-bold text-xl">Admin Panel</div>
        <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-gray-300">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto scrollbar-hide">
        <nav class="mt-4 px-2 pb-4">
          <a href="dashboard.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Dashboard</span>
          </a>
          <a href="products.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-box mr-3 w-5 text-center"></i>
            <span class="flex-1">Products</span>
          </a>
          <a href="projects.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
            <span class="flex-1">Projects</span>
          </a>
          <a href="orders.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
            <span class="flex-1">Orders</span>
          </a>
          <a href="users.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span class="flex-1">Users</span>
          </a>
          <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
            <span class="flex-1">Reports</span>
          </a>
          <a href="change_password.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-key mr-3 w-5 text-center"></i>
            <span class="flex-1">Change Password</span>
          </a>
          <?php if (($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
          <a href="superadmin.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
            <span class="flex-1">Super Admin</span>
          </a>
          <?php endif; ?>
        </nav>
      </div>
      
      <div class="p-4 border-t border-[#243646]">
        <a href="auth/logout.php" class="flex items-center py-3 px-4 text-red-300 hover:bg-[#243646] rounded-lg transition-colors">
          <i class="fas fa-sign-out-alt mr-3"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-0 min-h-screen">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-[#2D3E50]">Change Password</h1>
            <p class="text-gray-600 mt-1">Update your admin account password.</p>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
            <a href="auth/logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
              <i class="fas fa-sign-out-alt mr-2"></i>
              <span>Logout</span>
            </a>
          </div>
        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 mobile-header">
        <div class="flex items-center justify-between">
          <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="mobile-title font-semibold text-gray-800">Change Password</h1>
          <div class="w-8"></div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-4 lg:p-6 w-full">
        <!-- Messages -->
        <?php if ($success_message): ?>
          <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded">
            <div class="flex">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <div class="flex">
              <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
              <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Change Password Form -->
        <div class="form-container bg-white rounded-lg shadow-sm border border-gray-100 mobile-card">
          <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">Update Password</h2>
            <p class="text-gray-600 text-sm mt-1">Change your admin account password for enhanced security.</p>
          </div>
          
          <div class="p-4 lg:p-6">
            <form method="POST" class="form-grid space-y-6">
              <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                  Current Password
                </label>
                <input type="password" 
                       id="current_password" 
                       name="current_password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent"
                       required>
              </div>

              <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                  New Password
                </label>
                <input type="password" 
                       id="new_password" 
                       name="new_password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent"
                       required>
                <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long.</p>
              </div>

              <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                  Confirm New Password
                </label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent"
                       required>
              </div>

              <div class="button-group flex justify-end space-x-3">
                <a href="dashboard.php" 
                   class="mobile-button px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium transition-colors hover:bg-gray-50">
                  Cancel
                </a>
                <button type="submit" 
                        class="mobile-button bg-[#536895] hover:bg-[#4a5a7a] text-white px-6 py-2 rounded-lg font-medium transition-colors">
                  <i class="fas fa-save mr-2"></i>
                  Update Password
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Security Tips -->
        <div class="security-tips mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 mobile-card">
          <h3 class="text-lg font-medium text-blue-900 mb-2">
            <i class="fas fa-shield-alt mr-2"></i>
            Password Security Tips
          </h3>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>• Use at least 8 characters</li>
            <li>• Include uppercase and lowercase letters</li>
            <li>• Add numbers and special characters</li>
            <li>• Avoid common words or patterns</li>
            <li>• Don't reuse passwords from other accounts</li>
          </ul>
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
