<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';
include '../includes/user_activity_tracker.php';
include '../includes/analytics_helper.php';
include '../includes/dashboard_helper.php';

$admin_username = $_SESSION['admin_name'] ?? 'Admin';

// Get real-time admin dashboard statistics
$admin_stats = getAdminDashboardStats($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Admin Dashboard Meta Tags -->
  <title>Admin Dashboard | ManuelCode</title>
  <meta name="description" content="Admin dashboard for managing products, orders, users, and system settings.">
  <meta name="keywords" content="admin dashboard, management, products, orders, users">
  <meta name="author" content="ManuelCode">
  <meta name="robots" content="noindex, nofollow">
  
  <!-- Open Graph Tags -->
  <meta property="og:title" content="Admin Dashboard | ManuelCode">
  <meta property="og:description" content="Admin dashboard for managing products, orders, users, and system settings.">
  <meta property="og:image" content="https://manuelcode.info/assets/favi/favicon.png">
  <meta property="og:url" content="https://manuelcode.info/admin/dashboard.php">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="ManuelCode">
  
  <!-- Twitter Card Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Admin Dashboard | ManuelCode">
  <meta name="twitter:description" content="Admin dashboard for managing products, orders, users, and system settings.">
  <meta name="twitter:image" content="https://manuelcode.info/assets/favi/favicon.png">
  
  <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="../assets/js/session-timeout.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

  <!-- Layout Container -->
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-700 to-slate-800 text-white transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen transition-transform duration-300 ease-in-out shadow-xl">
      <div class="flex items-center justify-between p-4 lg:p-6 border-b border-slate-600">
        <div class="font-bold text-lg lg:text-xl text-white">Admin Panel</div>
        <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-slate-300 transition-colors p-2">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto">
        <nav class="mt-4 px-2 pb-4 space-y-1">
          <a href="dashboard.php" class="flex items-center py-3 px-4 bg-slate-600 rounded-lg transition-colors w-full text-white">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Dashboard</span>
          </a>
          <a href="products.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-box mr-3 w-5 text-center"></i>
            <span class="flex-1">Products</span>
          </a>
          <a href="projects.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
            <span class="flex-1">Projects</span>
          </a>
          <a href="orders.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
            <span class="flex-1">Orders</span>
          </a>
          <a href="purchase_management.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
            <span class="flex-1">Purchase Management</span>
          </a>
          <a href="users.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span class="flex-1">Users</span>
          </a>
          <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
            <span class="flex-1">Reports</span>
          </a>
          <a href="refunds.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-undo mr-3 w-5 text-center"></i>
            <span class="flex-1">Refunds</span>
          </a>
          <a href="change_password.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-key mr-3 w-5 text-center"></i>
            <span class="flex-1">Change Password</span>
          </a>
          <a href="support_management.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-headset mr-3 w-5 text-center"></i>
            <span class="flex-1">Support Management</span>
          </a>
          <a href="generate_receipts.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-receipt mr-3 w-5 text-center"></i>
            <span class="flex-1">Generate Receipts</span>
          </a>
          <?php if (($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
          <a href="superadmin.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
            <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
            <span class="flex-1">Super Admin</span>
          </a>
          <?php endif; ?>
        </nav>
      </div>
      
      <div class="p-4 border-t border-slate-600">
        <a href="auth/logout.php" class="flex items-center py-3 px-4 text-red-300 hover:bg-slate-600 rounded-lg transition-colors">
          <i class="fas fa-sign-out-alt mr-3"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent">Dashboard Overview</h1>
            <p class="text-slate-600 mt-1">Welcome back! Here's what's happening with your store.</p>
          </div>

        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="flex items-center justify-between p-4">
          <button onclick="toggleSidebar()" class="text-slate-600 hover:text-slate-900 p-2 transition-colors">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="text-lg font-semibold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent">Dashboard</h1>
          <div class="w-8"></div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="flex-1 p-4 lg:p-6 overflow-x-hidden">
                 <!-- Stats Grid -->
         <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 mb-6">
           <!-- Total Revenue Card -->
           <div class="bg-gradient-to-br from-green-50 to-emerald-100 border border-green-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
             <div class="flex items-center">
               <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-sm">
                 <i class="fas fa-dollar-sign text-base lg:text-lg"></i>
               </div>
               <div class="ml-3 flex-1 min-w-0">
                 <h2 class="text-green-700 text-xs lg:text-sm font-medium truncate">Total Revenue</h2>
                 <p class="text-base lg:text-lg xl:text-xl font-bold text-green-800 truncate">GHS <?php echo number_format($admin_stats['total_revenue'], 2); ?></p>
               </div>
             </div>
           </div>
          
          <!-- Total Products Card -->
          <div class="bg-gradient-to-br from-violet-50 to-purple-100 border border-violet-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-violet-400 to-purple-500 text-white shadow-sm">
                <i class="fas fa-box text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-violet-700 text-xs lg:text-sm font-medium truncate">Total Products</h2>
                <p class="text-base lg:text-lg xl:text-xl font-bold text-violet-800"><?php echo $admin_stats['total_products']; ?></p>
              </div>
            </div>
          </div>
          

          
          <!-- Total Orders Card -->
          <div class="bg-gradient-to-br from-amber-50 to-orange-100 border border-amber-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm">
                <i class="fas fa-shopping-cart text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-amber-700 text-xs lg:text-sm font-medium truncate">Total Orders</h2>
                                 <p class="text-base lg:text-lg xl:text-xl font-bold text-amber-800"><?php echo $admin_stats['total_orders']; ?></p>
              </div>
            </div>
          </div>
          
          <!-- Total Users Card -->
          <div class="bg-gradient-to-br from-indigo-50 to-purple-100 border border-indigo-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 text-white shadow-sm">
                <i class="fas fa-users text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-indigo-700 text-xs lg:text-sm font-medium truncate">Total Users</h2>
                <p class="text-base lg:text-lg xl:text-xl font-bold text-indigo-800"><?php echo $admin_stats['total_users']; ?></p>
              </div>
            </div>
          </div>
          
          <!-- Total Discounts Card -->
          <div class="bg-gradient-to-br from-red-50 to-pink-100 border border-red-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-red-400 to-pink-500 text-white shadow-sm">
                <i class="fas fa-tag text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-red-700 text-xs lg:text-sm font-medium truncate">Total Discounts</h2>
                <p class="text-base lg:text-lg xl:text-xl font-bold text-red-800">₵<?php echo number_format($admin_stats['total_discounts'], 2); ?></p>
                <p class="text-xs text-red-600"><?php echo $admin_stats['orders_with_discounts']; ?> orders</p>
              </div>
            </div>
          </div>
          
          <!-- Total Submissions Card -->
          <div class="bg-gradient-to-br from-teal-50 to-cyan-100 border border-teal-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 text-white shadow-sm">
                <i class="fas fa-file-upload text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-teal-700 text-xs lg:text-sm font-medium truncate">Total Submissions</h2>
                <p class="text-base lg:text-lg xl:text-xl font-bold text-teal-800"><?php echo number_format($admin_stats['total_submissions'] ?? 0); ?></p>
                <p class="text-xs text-teal-600">Project reports submitted</p>
              </div>
            </div>
          </div>

          <!-- SMS Count Card -->
          <div class="bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 text-white shadow-sm">
                <i class="fas fa-sms text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-blue-700 text-xs lg:text-sm font-medium truncate">SMS Sent</h2>
                <p class="text-base lg:text-lg xl:text-xl font-bold text-blue-800"><?php echo number_format($admin_stats['total_sms'] ?? 0); ?></p>
                <p class="text-xs text-blue-600">OTP & notifications</p>
              </div>
            </div>
          </div>

          <!-- Downloads Count Card -->
          <div class="bg-gradient-to-br from-purple-50 to-pink-100 border border-purple-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-gradient-to-br from-purple-400 to-pink-500 text-white shadow-sm">
                <i class="fas fa-download text-base lg:text-lg"></i>
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <h2 class="text-purple-700 text-xs lg:text-sm font-medium truncate">Total Downloads</h2>
                <p class="text-base lg:text-lg xl:text-xl font-bold text-purple-800"><?php echo number_format($admin_stats['total_downloads'] ?? 0); ?></p>
                <p class="text-xs text-purple-600">Products & documents</p>
              </div>
            </div>
          </div>

        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7 gap-4 mb-6">
          <!-- Products Card -->
          <div class="bg-gradient-to-br from-emerald-50 to-green-100 border border-emerald-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-emerald-400 to-green-500 text-white shadow-sm">
                <i class="fas fa-box text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-emerald-800 ml-3">Products</h3>
            </div>
            <p class="mb-4 text-emerald-700 text-xs lg:text-sm">Manage your digital products and software solutions.</p>
            <a href="add_product.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-emerald-500 to-green-600 text-white px-3 py-2 rounded-lg hover:from-emerald-600 hover:to-green-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-plus mr-2"></i>
              Add Product
            </a>
          </div>

          <!-- Orders Card -->
          <div class="bg-gradient-to-br from-sky-50 to-blue-100 border border-sky-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-sky-400 to-blue-500 text-white shadow-sm">
                <i class="fas fa-shopping-cart text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-sky-800 ml-3">Orders</h3>
            </div>
            <p class="mb-4 text-sky-700 text-xs lg:text-sm">View and process customer orders and payments.</p>
            <a href="orders.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-sky-500 to-blue-600 text-white px-3 py-2 rounded-lg hover:from-sky-600 hover:to-blue-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-eye mr-2"></i>
              View Orders
            </a>
          </div>

          <!-- Projects Card -->
          <div class="bg-gradient-to-br from-amber-50 to-orange-100 border border-amber-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm">
                <i class="fas fa-project-diagram text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-amber-800 ml-3">Projects</h3>
            </div>
            <p class="mb-4 text-amber-700 text-xs lg:text-sm">Manage your development projects and custom solutions.</p>
            <a href="add_project.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white px-3 py-2 rounded-lg hover:from-amber-600 hover:to-orange-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-plus mr-2"></i>
              Add Project
            </a>
          </div>

          <!-- Users Card -->
          <div class="bg-gradient-to-br from-violet-50 to-purple-100 border border-violet-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-violet-400 to-purple-500 text-white shadow-sm">
                <i class="fas fa-users text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-violet-800 ml-3">Users</h3>
            </div>
            <p class="mb-4 text-violet-700 text-xs lg:text-sm">Manage user accounts and view user activity.</p>
            <a href="users.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-violet-500 to-purple-600 text-white px-3 py-2 rounded-lg hover:from-violet-600 hover:to-purple-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-eye mr-2"></i>
              View Users
            </a>
          </div>

          <!-- Receipts Card -->
          <div class="bg-gradient-to-br from-yellow-50 to-orange-100 border border-yellow-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 text-white shadow-sm">
                <i class="fas fa-receipt text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-yellow-800 ml-3">Receipts</h3>
            </div>
            <p class="mb-4 text-yellow-700 text-xs lg:text-sm">Generate missing receipts for existing purchases.</p>
            <a href="../generate_missing_receipts.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-yellow-500 to-orange-600 text-white px-3 py-2 rounded-lg hover:from-yellow-600 hover:to-orange-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-magic mr-2"></i>
              Generate Receipts
            </a>
          </div>

          <!-- Coupons Card -->
          <div class="bg-gradient-to-br from-purple-50 to-indigo-100 border border-purple-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-purple-400 to-indigo-500 text-white shadow-sm">
                <i class="fas fa-tag text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-purple-800 ml-3">Coupons</h3>
            </div>
            <p class="mb-4 text-purple-700 text-xs lg:text-sm">Manage discount coupons and promotional codes.</p>
            <a href="coupons.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-3 py-2 rounded-lg hover:from-purple-600 hover:to-indigo-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-cog mr-2"></i>
              Manage Coupons
            </a>
          </div>

          <!-- Quote Management Card -->
          <div class="bg-gradient-to-br from-teal-50 to-cyan-100 border border-teal-200 p-4 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
            <div class="flex items-center mb-3">
              <div class="p-2 rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 text-white shadow-sm">
                <i class="fas fa-file-invoice-dollar text-base"></i>
              </div>
              <h3 class="text-base font-semibold text-teal-800 ml-3">Quote Management</h3>
            </div>
            <p class="mb-4 text-teal-700 text-xs lg:text-sm">Manage customer quote requests and responses.</p>
            <a href="quotes_enhanced.php" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-teal-500 to-cyan-600 text-white px-3 py-2 rounded-lg hover:from-teal-600 hover:to-cyan-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px]">
              <i class="fas fa-cogs mr-2"></i>
              Quote Management
            </a>
          </div>
        </div>

        <!-- Admin Messaging Section -->
        <div class="bg-gradient-to-br from-rose-50 to-pink-100 rounded-xl shadow-sm border border-rose-200 p-4 mb-6">
          <div class="flex items-center mb-4">
            <div class="p-2 rounded-full bg-gradient-to-br from-rose-400 to-pink-500 text-white shadow-sm">
              <i class="fas fa-sms text-base"></i>
            </div>
            <h3 class="text-base font-semibold text-rose-800 ml-3">Send Messages to Users</h3>
          </div>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Send to All Users -->
            <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 border border-rose-100">
              <h4 class="font-medium text-rose-800 mb-3 text-sm">Send to All Users</h4>
              <form id="sendToAllForm" class="space-y-3">
                <div>
                  <label class="block text-xs lg:text-sm font-medium text-rose-700 mb-1">Message</label>
                  <textarea id="messageToAll" rows="3" class="w-full p-3 border border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent bg-white/80 text-sm" 
                            placeholder="Enter your message to send to all users..." required></textarea>
                </div>
                <button type="submit" class="bg-gradient-to-r from-rose-500 to-pink-600 text-white px-3 py-2 rounded-lg hover:from-rose-600 hover:to-pink-700 transition-all duration-200 text-xs lg:text-sm font-medium shadow-sm min-h-[40px] w-full">
                  <i class="fas fa-paper-plane mr-2"></i>Send to All Users
                </button>
              </form>
              <div id="sendToAllResult" class="mt-2 text-xs lg:text-sm"></div>
            </div>

            <!-- Send to Specific User -->
            <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 border border-rose-100">
              <h4 class="font-medium text-rose-800 mb-3 text-sm">Send to Specific User</h4>
              <?php
              try {
                $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE phone IS NOT NULL AND phone != ''");
                $users_with_phone = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
                echo '<p class="text-xs lg:text-sm text-gray-600 mb-3">Available users with phone numbers: <span class="font-semibold text-blue-600">' . $users_with_phone . '</span></p>';
              } catch (Exception $e) {
                echo '<p class="text-xs lg:text-sm text-gray-600 mb-3">Available users: <span class="font-semibold text-blue-600">Loading...</span></p>';
              }
              ?>
              <form id="sendToUserForm" class="space-y-3">
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs lg:text-sm font-medium text-gray-700">Select User</label>
                    <button type="button" onclick="refreshUserList()" class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm">
                      <i class="fas fa-sync-alt mr-1"></i>Refresh
                    </button>
                  </div>
                  <select id="selectedUser" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                    <option value="">Choose a user...</option>
                    <?php
                    try {
                      $stmt = $pdo->query("SELECT id, name, phone, email, created_at, user_id FROM users ORDER BY name");
                      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                      
                      if (empty($users)) {
                        echo '<option value="">No users found</option>';
                      } else {
                        $users_with_phone = 0;
                        foreach ($users as $user) {
                          if (!empty($user['phone']) && $user['phone'] !== '') {
                            $phone_display = format_phone_for_display($user['phone']);
                            $user_id_display = $user['user_id'] ?? 'USER' . str_pad($user['id'], 6, '0', STR_PAD_LEFT);
                            $display_name = $user['name'] . ' (ID: ' . $user_id_display . ', ' . $phone_display . ')';
                            echo '<option value="' . $user['id'] . '" data-phone="' . htmlspecialchars($user['phone']) . '">' . htmlspecialchars($display_name) . '</option>';
                            $users_with_phone++;
                          }
                        }
                        
                        if ($users_with_phone === 0) {
                          echo '<option value="">No users with phone numbers found</option>';
                        }
                      }
                    } catch (Exception $e) {
                      echo '<option value="">Error loading users</option>';
                      error_log("Error loading users for SMS dropdown: " . $e->getMessage());
                    }
                    ?>
                  </select>
                </div>
                <div>
                  <label class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">Message</label>
                  <textarea id="messageToUser" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" 
                            placeholder="Enter your message..." required></textarea>
                </div>
                <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition-colors text-xs lg:text-sm min-h-[40px] w-full">
                  <i class="fas fa-paper-plane mr-2"></i>Send to User
                </button>
              </form>
              <div id="sendToUserResult" class="mt-2 text-xs lg:text-sm"></div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100">
          <div class="px-4 py-4 border-b border-gray-200">
            <h2 class="text-base lg:text-lg font-semibold text-slate-800">Recent Orders</h2>
          </div>
          <div class="p-4">
            <?php
            $stmt = $pdo->query("
              SELECT 
                p.*, 
                pr.title as product_title, 
                pr.price as product_price,
                COALESCE(p.original_amount, pr.price) as original_amount,
                COALESCE(p.discount_amount, 0) as discount_amount,
                COALESCE(pl.download_count, 0) as download_count,
                pl.last_downloaded,
                CASE 
                  WHEN p.amount = 0 AND p.discount_amount > 0 THEN 'FREE with coupon'
                  WHEN p.amount = 0 AND p.discount_amount = 0 THEN 'FREE'
                  ELSE 'Paid'
                END as purchase_type
              FROM purchases p 
              JOIN products pr ON p.product_id = pr.id 
              LEFT JOIN purchase_logs pl ON p.id = pl.purchase_id
              ORDER BY p.created_at DESC 
              LIMIT 5
            ");
            $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if ($recent_orders): ?>
              <div class="overflow-x-auto">
                <table class="w-full min-w-[400px] lg:min-w-[600px]">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recent_orders as $order): ?>
                      <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-3 text-xs lg:text-sm text-gray-900 truncate max-w-[150px] lg:max-w-none">
                          <?php echo htmlspecialchars($order['product_title']); ?>
                          <div class="text-xs text-gray-500 mt-1">
                            Downloads: <?php echo $order['download_count'] ?? 0; ?>
                            <?php if ($order['last_downloaded']): ?>
                              <br>Last: <?php echo date('M j', strtotime($order['last_downloaded'])); ?>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td class="px-3 py-3 text-xs lg:text-sm font-bold text-emerald-600">
                          <?php 
                          $purchase_type = $order['purchase_type'] ?? 'Paid';
                          $original_amount = $order['original_amount'] ?? $order['product_price'];
                          $discount_amount = $order['discount_amount'] ?? 0;
                          $paid_amount = $order['amount'] ?? 0;
                          
                          if ($purchase_type === 'FREE with coupon') {
                              echo '<span class="text-green-600">FREE</span><br>';
                              echo '<span class="text-xs text-gray-500 line-through">₵' . number_format($original_amount, 2) . '</span><br>';
                              echo '<span class="text-xs text-blue-600">Coupon applied</span>';
                          } elseif ($purchase_type === 'FREE') {
                              echo '<span class="text-green-600">FREE</span>';
                          } else {
                              if ($discount_amount > 0) {
                                  echo '<span class="text-green-600">₵' . number_format($paid_amount, 2) . '</span><br>';
                                  echo '<span class="text-xs text-gray-500 line-through">₵' . number_format($original_amount, 2) . '</span><br>';
                                  echo '<span class="text-xs text-blue-600">-₵' . number_format($discount_amount, 2) . '</span>';
                              } else {
                                  echo 'GHS ' . number_format($original_amount, 2);
                              }
                          }
                          ?>
                        </td>
                        <td class="px-3 py-3 text-xs lg:text-sm text-gray-600">
                          <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-8">
                <i class="fas fa-shopping-cart text-3xl lg:text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 text-sm lg:text-base">No orders yet.</p>
                <p class="text-xs lg:text-sm text-gray-500 mt-1">Orders will appear here once customers make purchases.</p>
              </div>
            <?php endif; ?>
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

      // Admin Messaging Functionality
      const sendToAllForm = document.getElementById('sendToAllForm');
      const sendToUserForm = document.getElementById('sendToUserForm');

      if (sendToAllForm) {
        sendToAllForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const message = document.getElementById('messageToAll').value.trim();
          const resultDiv = document.getElementById('sendToAllResult');
          
          if (!message) {
            resultDiv.innerHTML = '<div class="text-red-600">Please enter a message.</div>';
            return;
          }

          resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Sending message to all users...</div>';
          
          fetch('admin_messaging.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              action: 'send_to_all',
              message: message
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              resultDiv.innerHTML = `<div class="text-green-600"><i class="fas fa-check mr-2"></i>Message sent successfully to ${data.sent_count} users!</div>`;
              document.getElementById('messageToAll').value = '';
            } else {
              resultDiv.innerHTML = `<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ${data.error}</div>`;
            }
          })
          .catch(error => {
            resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Network error. Please try again.</div>';
          });
        });
      }

      if (sendToUserForm) {
        sendToUserForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const userId = document.getElementById('selectedUser').value;
          const message = document.getElementById('messageToUser').value.trim();
          const resultDiv = document.getElementById('sendToUserResult');
          
          if (!userId || !message) {
            resultDiv.innerHTML = '<div class="text-red-600">Please select a user and enter a message.</div>';
            return;
          }

          resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Sending message...</div>';
          
          fetch('admin_messaging.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              action: 'send_to_user',
              user_id: userId,
              message: message
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              resultDiv.innerHTML = `<div class="text-green-600"><i class="fas fa-check mr-2"></i>Message sent successfully to ${data.user_name}!</div>`;
              document.getElementById('messageToUser').value = '';
              document.getElementById('selectedUser').value = '';
            } else {
              resultDiv.innerHTML = `<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ${data.error}</div>`;
            }
          })
          .catch(error => {
            resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Network error. Please try again.</div>';
          });
        });
      }

      function refreshUserList() {
        const refreshBtn = event.target;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Loading...';
        refreshBtn.disabled = true;
        
        setTimeout(() => {
          location.reload();
        }, 500);
      }
    });
  </script>
</body>
</html>
