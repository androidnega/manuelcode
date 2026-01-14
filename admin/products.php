<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

// Initialize dark mode from session
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}
$dark_mode = $_SESSION['dark_mode'];

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Get product info for image deletion
    $stmt = $pdo->prepare("SELECT preview_image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product && $product['preview_image']) {
        $image_path = "../assets/images/products/" . $product['preview_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    
    header("Location: ../dashboard/products?success=deleted");
    exit;
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>manuelcode | Admin - Products</title>
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
      .search-filters {
        grid-template-columns: 1fr;
        gap: 1rem;
        width: 100%;
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
      .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 12px;
        margin: 0;
        width: 100%;
      }
      .table-responsive {
        min-width: 100%;
        padding: 0;
      }
      .table-responsive th,
      .table-responsive td {
        white-space: normal;
        word-wrap: break-word;
        min-width: auto;
        padding: 0.75rem;
        font-size: 0.875rem;
      }
      .table-responsive th:first-child,
      .table-responsive td:first-child {
        min-width: auto;
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
      .search-filters {
        grid-template-columns: 1fr;
        gap: 1rem;
        width: 100%;
      }
      .search-filters .md\\:col-span-2 {
        grid-column: 1 / -1;
      }
      /* Product table improvements for mobile */
      .table-responsive .flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
      }
      .table-responsive .flex-shrink-0 {
        margin-bottom: 0.5rem;
      }
      .table-responsive .ml-4 {
        margin-left: 0;
      }
      .table-responsive .flex-col {
        flex-direction: row;
        gap: 1rem;
      }
      .table-responsive .flex-col > * {
        padding: 0.5rem;
        border-radius: 4px;
        background: #f8f9fa;
        min-width: auto;
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
      .search-filters {
        grid-template-columns: 1fr;
        gap: 0.75rem;
        width: 100%;
      }
      .search-filters .md\\:col-span-2 {
        grid-column: 1 / -1;
      }
      .table-responsive th,
      .table-responsive td {
        padding: 0.5rem;
        font-size: 0.875rem;
      }
      /* Enhanced mobile table layout */
      .table-responsive {
        display: block;
      }
      .table-responsive thead {
        display: none;
      }
      .table-responsive tbody {
        display: block;
      }
      .table-responsive tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        background: white;
      }
      .table-responsive td {
        display: block;
        text-align: left;
        border: none;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
      }
      .table-responsive td:last-child {
        border-bottom: none;
      }
      .table-responsive td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #374151;
        display: block;
        margin-bottom: 0.25rem;
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
      .table-responsive {
        min-width: 100%;
        padding: 0;
      }
      .search-filters {
        gap: 0.5rem;
        width: 100%;
      }
      .table-responsive th,
      .table-responsive td {
        padding: 0.5rem;
        font-size: 0.875rem;
      }
      /* Small mobile optimizations */
      .table-responsive tr {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
      }
      .table-responsive td {
        padding: 0.375rem 0;
        font-size: 0.875rem;
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
    
    /* Dark mode styles */
    .dark .mobile-header {
      background: rgba(31, 41, 55, 0.8) !important;
      border-bottom-color: #374151 !important;
    }
    
    .dark .table-container {
      background: #1f2937 !important;
      border-color: #374151 !important;
    }
    
    .dark .table-responsive th {
      background-color: #374151 !important;
      color: #f9fafb !important;
      border-bottom-color: #4b5563 !important;
    }
    
    .dark .table-responsive td {
      border-bottom-color: #4b5563 !important;
      color: #f9fafb !important;
    }
    
    .dark .table-responsive tr:hover {
      background-color: #374151 !important;
    }
    
    .dark .mobile-card {
      background: #1f2937 !important;
      border-color: #374151 !important;
      color: white !important;
    }
    
    .dark .mobile-card:hover {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3) !important;
    }
  </style>
</head>
<body class="<?php echo $dark_mode ? 'bg-gray-900 dark' : 'bg-[#F4F4F9]'; ?>">
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
        <a href="products.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
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
        <a href="change_password.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
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
    <div class="flex-1 lg:ml-0 h-screen flex flex-col">
      <!-- Page Header -->
      <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Products Management</h1>
            <p class="text-gray-600 mt-1">Manage your digital products and software solutions.</p>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content flex-1 overflow-y-auto p-4 lg:p-6 w-full">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6">
          <div class="mb-4 lg:mb-0">
          </div>
          <a href="add_product.php" class="mobile-button bg-[#4CAF50] text-white px-4 py-2 rounded-lg hover:bg-[#45a049] transition-colors inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Add New Product
          </a>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
          <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <div>
                <p class="text-sm text-green-700">Product deleted successfully!</p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Live Search & Filter Controls -->
        <div class="<?php echo $dark_mode ? 'bg-gray-800' : 'bg-white'; ?> rounded-lg shadow-sm p-4 mb-6 mobile-card">
          <div class="mb-3">
            <h3 class="text-lg font-semibold <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-800'; ?>">Live Search & Filters</h3>
            <p class="text-sm <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-600'; ?>">Search and filter products in real-time</p>
          </div>
          <div class="search-filters grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
            <!-- Search -->
            <div class="md:col-span-2">
              <label for="search" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-1">Search Products</label>
              <input type="text" id="search" placeholder="Search by title, description..." 
                     class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
            </div>
            
            <!-- Status Filter -->
            <div>
              <label for="statusFilter" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-1">Status</label>
              <select id="statusFilter" name="status" class="filter-input w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            
            <!-- Date Filter -->
            <div>
              <label for="dateFilter" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-1">Date Range</label>
              <select id="dateFilter" name="date" class="filter-input w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>
            </div>
          </div>
          <div class="mt-4 flex flex-col sm:flex-row gap-2">
            <button type="button" class="clear-filters bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors text-sm">
              <i class="fas fa-times mr-2"></i>Clear All Filters
            </button>
            <div class="results-count text-sm <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-600'; ?> flex items-center">
              <i class="fas fa-info-circle mr-2"></i>
              <span>Ready to search</span>
            </div>
          </div>
        </div>

        <!-- No Results Message (Hidden by default) -->
        <div class="no-results <?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> p-8 rounded-lg shadow-sm border text-center" style="display: none;">
          <i class="fas fa-search text-4xl <?php echo $dark_mode ? 'text-gray-500' : 'text-gray-400'; ?> mb-4"></i>
          <h3 class="text-xl font-semibold <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?> mb-2">No Products Found</h3>
          <p class="<?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?> mb-4">Try adjusting your search or filter criteria.</p>
        </div>
        
        <?php if (empty($products)): ?>
          <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> p-8 rounded-lg shadow-sm border text-center">
            <i class="fas fa-box-open text-4xl <?php echo $dark_mode ? 'text-gray-500' : 'text-gray-400'; ?> mb-4"></i>
            <h3 class="text-xl font-semibold <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?> mb-2">No Products Found</h3>
            <p class="<?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?> mb-4">Start by adding your first product to the store.</p>
            <a href="add_product.php" class="bg-[#4CAF50] text-white px-6 py-2 rounded-lg hover:bg-[#45a049] transition-colors">
              <i class="fas fa-plus mr-2"></i>Add Product
            </a>
          </div>
        <?php else: ?>
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 mobile-card">
            <div class="table-container w-full">
              <table class="table-responsive w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php foreach ($products as $product): ?>
                    <tr class="hover:bg-gray-50" 
                        data-product="<?php echo $product['id']; ?>"
                        data-status="active"
                        data-date="<?php echo date('Y-m-d', strtotime($product['created_at'])); ?>">
                      <td class="px-3 lg:px-6 py-4" data-label="Product">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10">
                            <?php if ($product['preview_image']): ?>
                              <img class="h-10 w-10 rounded-lg object-cover" src="../assets/images/products/<?php echo htmlspecialchars($product['preview_image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                              <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($product['short_desc'], 0, 50)) . '...'; ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="px-3 lg:px-6 py-4 text-sm font-bold text-[#4CAF50]" data-label="Price">
                        GHS <?php echo number_format($product['price'], 2); ?>
                      </td>
                      <td class="px-3 lg:px-6 py-4" data-label="Status">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                          Active
                        </span>
                      </td>
                      <td class="px-3 lg:px-6 py-4 text-sm text-gray-500" data-label="Created">
                        <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                      </td>
                      <td class="px-3 lg:px-6 py-4 text-sm font-medium" data-label="Actions">
                        <div class="flex flex-col space-y-1">
                          <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="text-[#F5A623] hover:text-[#d88c1b] inline-flex items-center">
                            <i class="fas fa-edit mr-1"></i>Edit
                          </a>
                          <a href="?delete=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')" class="text-red-600 hover:text-red-900 inline-flex items-center">
                            <i class="fas fa-trash mr-1"></i>Delete
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <script src="assets/js/live-search.js"></script>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('mobile-overlay');
      
      if (sidebar.classList.contains('-translate-x-full')) {
        // Open sidebar
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      } else {
        // Close sidebar
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
      }
    }

    // Close sidebar when clicking on a link (mobile)
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarLinks = document.querySelectorAll('#sidebar a');
      sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth < 1024) { // lg breakpoint
            toggleSidebar();
          }
        });
      });

      // Close sidebar on window resize if screen becomes large
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
