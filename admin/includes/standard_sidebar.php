<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$admin_username = $_SESSION['admin_name'] ?? 'Admin';
?>

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
        <a href="dashboard.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'dashboard' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
          <span class="flex-1">Dashboard</span>
        </a>
        <a href="products.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'products' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-box mr-3 w-5 text-center"></i>
          <span class="flex-1">Products</span>
        </a>
        <a href="projects.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'projects' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
          <span class="flex-1">Projects</span>
        </a>
        <a href="orders.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'orders' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
          <span class="flex-1">Orders</span>
        </a>
        <a href="purchase_management.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'purchase_management' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
          <span class="flex-1">Purchase Management</span>
        </a>
        <a href="users.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'users' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-users mr-3 w-5 text-center"></i>
          <span class="flex-1">Users</span>
        </a>
        <a href="reports.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'reports' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
          <span class="flex-1">Reports</span>
        </a>
        <a href="refunds.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'refunds' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-undo mr-3 w-5 text-center"></i>
          <span class="flex-1">Refunds</span>
        </a>
        <a href="change_password.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'change_password' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-key mr-3 w-5 text-center"></i>
          <span class="flex-1">Change Password</span>
        </a>
        <a href="support_management.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'support_management' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-headset mr-3 w-5 text-center"></i>
          <span class="flex-1">Support Management</span>
        </a>
        <a href="generate_receipts.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'generate_receipts' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-receipt mr-3 w-5 text-center"></i>
          <span class="flex-1">Generate Receipts</span>
        </a>
        <?php if (($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
        <a href="superadmin.php" class="flex items-center py-3 px-4 <?php echo $current_page === 'superadmin' ? 'bg-slate-600 text-white' : 'hover:bg-slate-600 text-slate-200 hover:text-white'; ?> rounded-lg transition-colors w-full">
          <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
          <span class="flex-1">Super Admin</span>
        </a>
        <?php endif; ?>
      </nav>
    </div>
    
    <div class="p-4 border-t border-slate-600">
      <div class="flex items-center mb-3">
        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
          <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($admin_username); ?></p>
          <p class="text-xs text-slate-300">Admin</p>
        </div>
      </div>
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
          <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent"><?php echo ucfirst(str_replace('_', ' ', $current_page)); ?> Management</h1>
          <p class="text-slate-600 mt-1">Manage your <?php echo str_replace('_', ' ', $current_page); ?> and settings</p>
        </div>
        <div class="flex items-center space-x-4">
          <a href="../index.php" class="text-slate-600 hover:text-blue-600 transition-colors">
            <i class="fas fa-home mr-2"></i>Home
          </a>
          <a href="dashboard.php" class="text-slate-600 hover:text-blue-600 transition-colors">
            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
          </a>
        </div>
      </div>
    </header>

    <!-- Mobile Header -->
    <header class="lg:hidden bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 sticky top-0 z-30">
      <div class="flex items-center justify-between p-4">
        <button onclick="toggleSidebar()" class="text-slate-600 hover:text-slate-900 p-2">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="font-semibold text-slate-800"><?php echo ucfirst(str_replace('_', ' ', $current_page)); ?> Management</h1>
        <div class="w-8"></div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="p-6">
