<?php
// Get current page from route or fallback to PHP_SELF
$route = $_SESSION['current_route'] ?? $_GLOBALS['current_route'] ?? '';
if (!empty($route)) {
    // Map route to page name for highlighting
    $route_to_page = [
        'admin-dashboard' => 'dashboard',
        'purchase-management' => 'purchase_management',
        'refunds-admin' => 'refunds',
        'change-password' => 'change_password',
        'support-management' => 'support_management',
        'generate-receipts' => 'generate_receipts',
    ];
    $current_page = $route_to_page[$route] ?? str_replace('-', '_', $route);
} else {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}
$admin_username = $_SESSION['admin_name'] ?? 'Admin';
$dark_mode = $_SESSION['dark_mode'] ?? false;
?>

<!-- Mobile Menu Overlay -->
<div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- Layout Container -->
<div class="flex">
  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 <?php echo $dark_mode ? 'bg-gray-900 border-gray-700' : 'bg-[#2d3e50] border-gray-600'; ?> border-r transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
    <div class="flex items-center justify-between p-6 border-b <?php echo $dark_mode ? 'border-gray-700' : 'border-gray-600'; ?>">
      <div class="font-bold text-xl <?php echo $dark_mode ? 'text-white' : 'text-white'; ?>">Admin Panel</div>
              <button onclick="toggleSidebar()" class="lg:hidden <?php echo $dark_mode ? 'text-gray-300 hover:text-white' : 'text-gray-300 hover:text-white'; ?>">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>
    
    <div class="flex-1 overflow-y-auto scrollbar-hide">
      <nav class="mt-4 px-4 pb-4">
        <a href="../dashboard/" class="flex items-center py-3 px-4 <?php echo $current_page === 'dashboard' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
          <span class="flex-1">Dashboard</span>
        </a>
        <a href="../dashboard/products" class="flex items-center py-3 px-4 <?php echo $current_page === 'products' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-box mr-3 w-5 text-center"></i>
          <span class="flex-1">Products</span>
        </a>
        <a href="../dashboard/projects" class="flex items-center py-3 px-4 <?php echo $current_page === 'projects' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
          <span class="flex-1">Projects</span>
        </a>
        <a href="../dashboard/orders" class="flex items-center py-3 px-4 <?php echo $current_page === 'orders' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
          <span class="flex-1">Orders</span>
        </a>
        <a href="../dashboard/purchase-management" class="flex items-center py-3 px-4 <?php echo $current_page === 'purchase_management' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
          <span class="flex-1">Purchase Management</span>
        </a>
        <a href="../dashboard/users" class="flex items-center py-3 px-4 <?php echo $current_page === 'users' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-users mr-3 w-5 text-center"></i>
          <span class="flex-1">Users</span>
        </a>
        <a href="../dashboard/reports" class="flex items-center py-3 px-4 <?php echo $current_page === 'reports' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
          <span class="flex-1">Reports</span>
        </a>
        <a href="../dashboard/refunds-admin" class="flex items-center py-3 px-4 <?php echo $current_page === 'refunds' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-undo mr-3 w-5 text-center"></i>
          <span class="flex-1">Refunds</span>
        </a>
        <a href="../dashboard/change-password" class="flex items-center py-3 px-4 <?php echo $current_page === 'change_password' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-key mr-3 w-5 text-center"></i>
          <span class="flex-1">Change Password</span>
        </a>
        <a href="../dashboard/support-management" class="flex items-center py-3 px-4 <?php echo $current_page === 'support_management' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-headset mr-3 w-5 text-center"></i>
          <span class="flex-1">Support Management</span>
        </a>
        <a href="../dashboard/generate-receipts" class="flex items-center py-3 px-4 <?php echo $current_page === 'generate_receipts' ? ($dark_mode ? 'bg-blue-900 text-blue-300' : 'bg-blue-600 text-white') : ($dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-200 hover:bg-gray-600'); ?> rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-receipt mr-3 w-5 text-center"></i>
          <span class="flex-1">Generate Receipts</span>
        </a>
      </nav>
    </div>
    
    <div class="p-4 border-t <?php echo $dark_mode ? 'border-gray-700' : 'border-gray-600'; ?>">
      <div class="flex items-center mb-3">
        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
          <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium <?php echo $dark_mode ? 'text-white' : 'text-white'; ?>"><?php echo htmlspecialchars($admin_username); ?></p>
          <p class="text-xs <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-300'; ?>">Admin</p>
        </div>
      </div>
      <a href="auth/logout.php" class="flex items-center py-2 px-4 <?php echo $dark_mode ? 'text-red-400 hover:bg-red-900' : 'text-red-300 hover:bg-red-800'; ?> rounded-lg transition-colors">
        <i class="fas fa-sign-out-alt mr-3"></i>
        <span>Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 lg:ml-0 min-h-screen">
    <!-- Desktop Header -->
    <header class="hidden lg:block <?php echo $dark_mode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'; ?> border-b px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>"><?php echo ucfirst(str_replace('_', ' ', $current_page)); ?> Management</h1>
          <p class="<?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?> mt-1">Manage your <?php echo str_replace('_', ' ', $current_page); ?> and settings</p>
        </div>
        <div class="flex items-center space-x-4">
          <!-- Dark Mode Toggle -->
          <button onclick="toggleDarkMode()" class="<?php echo $dark_mode ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-600 hover:text-gray-800'; ?> transition-colors p-2 rounded-lg <?php echo $dark_mode ? 'hover:bg-gray-700' : 'hover:bg-gray-100'; ?>">
            <i class="fas <?php echo $dark_mode ? 'fa-sun' : 'fa-moon'; ?> text-lg"></i>
          </button>
          <a href="../index.php" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'; ?> transition-colors">
            <i class="fas fa-home mr-2"></i>Home
          </a>
          <a href="../dashboard/" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'; ?> transition-colors">
            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
          </a>
        </div>
      </div>
    </header>

    <!-- Mobile Header -->
    <header class="lg:hidden <?php echo $dark_mode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'; ?> border-b mobile-header">
      <div class="flex items-center justify-between">
        <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="mobile-title font-semibold text-gray-800"><?php echo ucfirst(str_replace('_', ' ', $current_page)); ?> Management</h1>
        <div class="w-8"></div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="p-6">
