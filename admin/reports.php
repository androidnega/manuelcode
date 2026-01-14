<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/analytics_helper.php';

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Use standardized analytics functions for consistent calculations
try {
    $total_revenue = getTotalRevenue($pdo);
    $total_orders = getTotalOrders($pdo);
    $total_products = getTotalProducts($pdo);
} catch (Exception $e) {
    $total_revenue = 0;
    $total_orders = 0;
    $total_products = 0;
}

// Get monthly sales data for the last 6 months
try {
    $stmt = $pdo->query("
        SELECT 
            month,
            SUM(orders) as orders,
            SUM(revenue) as revenue
        FROM (
            SELECT 
                DATE_FORMAT(p.created_at, '%Y-%m') as month,
                COUNT(*) as orders,
                SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END) as revenue
            FROM purchases p 
            JOIN products pr ON p.product_id = pr.id
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND p.status = 'paid'
            GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
            UNION ALL
            SELECT 
                DATE_FORMAT(go.created_at, '%Y-%m') as month,
                COUNT(*) as orders,
                SUM(go.total_amount) as revenue
            FROM guest_orders go
            WHERE go.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND go.status = 'paid'
            GROUP BY DATE_FORMAT(go.created_at, '%Y-%m')
        ) combined_sales
        GROUP BY month
        ORDER BY month DESC
    ");
    $monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $monthly_sales = [];
}

// Get top selling products
try {
    $stmt = $pdo->query("
        SELECT 
            pr.title,
            pr.price,
            SUM(sales_count) as sales_count,
            SUM(total_revenue) as total_revenue
        FROM (
            SELECT 
                pr.id,
                pr.title,
                pr.price,
                COUNT(p.id) as sales_count,
                SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END) as total_revenue
            FROM products pr 
            LEFT JOIN purchases p ON pr.id = p.product_id AND p.status = 'paid'
            GROUP BY pr.id
            UNION ALL
            SELECT 
                pr.id,
                pr.title,
                pr.price,
                COUNT(go.id) as sales_count,
                SUM(go.total_amount) as total_revenue
            FROM products pr 
            LEFT JOIN guest_orders go ON pr.id = go.product_id AND go.status = 'paid'
            GROUP BY pr.id
        ) combined_sales
        GROUP BY id, title, price
        ORDER BY sales_count DESC 
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_products = [];
}

// Get recent activity
try {
    $stmt = $pdo->query("
        SELECT 
            'order' as type,
            p.created_at,
            CONCAT(u.name, ' purchased ', pr.title) as description,
            COALESCE(p.amount, pr.price) as amount
        FROM purchases p 
        JOIN users u ON p.user_id = u.id
        JOIN products pr ON p.product_id = pr.id 
        WHERE p.status = 'paid'
        UNION ALL
        SELECT 
            'guest_order' as type,
            go.created_at,
            CONCAT(go.name, ' (Guest) purchased ', pr.title) as description,
            go.total_amount as amount
        FROM guest_orders go
        JOIN products pr ON go.product_id = pr.id 
        WHERE go.status = 'paid'
        UNION ALL
        SELECT 
            'user' as type,
            u.created_at,
            CONCAT(u.name, ' registered') as description,
            0 as amount
        FROM users u
        WHERE u.role = 'user'
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_activity = [];
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>manuelcode | Admin - Reports</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
       .stats-grid {
         grid-template-columns: repeat(2, 1fr);
         gap: 1rem;
       }
       .chart-container {
         height: 300px;
         width: 100%;
       }
     }
    
         @media (max-width: 768px) {
       .main-content {
         padding: 0.75rem;
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
         padding: 0.5rem;
       }
       .table-responsive th:first-child,
       .table-responsive td:first-child {
         min-width: auto;
       }
       .chart-container {
         height: 250px;
         width: 100%;
         overflow: visible;
       }
       .chart-container canvas {
         max-width: 100%;
         height: auto !important;
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
       .stats-grid {
         grid-template-columns: repeat(2, 1fr);
         gap: 0.75rem;
         width: 100%;
       }
       .stats-grid > div {
         width: 100%;
         min-width: 0;
       }
     }
    
         @media (max-width: 640px) {
       .main-content {
         padding: 0.5rem;
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
       .stats-grid {
         grid-template-columns: 1fr;
         gap: 0.75rem;
         width: 100%;
       }
       .stats-grid > div {
         width: 100%;
         min-width: 0;
       }
       .chart-container {
         height: 200px;
         width: 100%;
         overflow: visible;
       }
       .chart-container canvas {
         max-width: 100%;
         height: auto !important;
       }
       .table-responsive th,
       .table-responsive td {
         padding: 0.25rem;
         font-size: 0.875rem;
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
       .chart-container {
         height: 180px;
         width: 100%;
         overflow: visible;
       }
       .chart-container canvas {
         max-width: 100%;
         height: auto !important;
       }
       .stats-grid > div {
         padding: 1rem;
       }
       .table-responsive th,
       .table-responsive td {
         padding: 0.25rem;
         font-size: 0.75rem;
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
         width: 100%;
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
        <a href="reports.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
          <span class="flex-1">Reports</span>
        </a>
        <a href="purchase_management.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
          <span class="flex-1">Purchase Management</span>
        </a>
        <a href="refunds.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-undo mr-3 w-5 text-center"></i>
          <span class="flex-1">Refunds</span>
        </a>
        <a href="support_management.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-headset mr-3 w-5 text-center"></i>
          <span class="flex-1">Support Management</span>
        </a>
        <a href="generate_receipts.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-receipt mr-3 w-5 text-center"></i>
          <span class="flex-1">Generate Receipts</span>
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
    <div class="flex-1 lg:ml-0 min-h-screen">
        <!-- Desktop Header -->
        <header class="hidden lg:block bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[#2D3E50]">Analytics & Reports</h1>
                    <p class="text-gray-600 mt-1">Comprehensive insights into your business performance and user activity.</p>
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
                <h1 class="mobile-title font-semibold text-gray-800">Reports</h1>
                <div class="w-8"></div> <!-- Spacer for centering -->
            </div>
        </header>

                 <!-- Main Content Area -->
         <main class="main-content p-4 lg:p-6 w-full">

             <!-- Stats Overview -->
       <div class="stats-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8 w-full">
        <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center">
            <div class="p-2 lg:p-3 rounded-full bg-green-100 text-green-600">
              <i class="fas fa-chart-line text-lg lg:text-xl"></i>
            </div>
            <div class="ml-3 lg:ml-4">
              <h2 class="text-gray-600 text-sm">Total Revenue</h2>
                             <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]">GHS <?php echo number_format($total_revenue, 2); ?></p>
            </div>
          </div>
        </div>
        
        <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center">
            <div class="p-2 lg:p-3 rounded-full bg-blue-100 text-blue-600">
              <i class="fas fa-shopping-cart text-lg lg:text-xl"></i>
            </div>
            <div class="ml-3 lg:ml-4">
              <h2 class="text-gray-600 text-sm">Total Orders</h2>
              <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]"><?php echo $total_orders; ?></p>
            </div>
          </div>
        </div>
        

        
        <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
          <div class="flex items-center">
            <div class="p-2 lg:p-3 rounded-full bg-orange-100 text-orange-600">
              <i class="fas fa-box text-lg lg:text-xl"></i>
            </div>
            <div class="ml-3 lg:ml-4">
              <h2 class="text-gray-600 text-sm">Total Products</h2>
              <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]"><?php echo $total_products; ?></p>
            </div>
          </div>
        </div>
      </div>

             <!-- Charts Section -->
       <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 lg:mb-8 w-full">
        <!-- Monthly Sales Chart -->
        <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-200 mobile-card">
          <h3 class="text-lg font-semibold text-[#2D3E50] mb-4">Monthly Sales</h3>
                     <div class="chart-container h-64 w-full">
             <canvas id="salesChart" width="400" height="200"></canvas>
           </div>
        </div>

        <!-- Top Products Chart -->
        <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-200 mobile-card">
          <h3 class="text-lg font-semibold text-[#2D3E50] mb-4">Top Selling Products</h3>
                     <div class="chart-container h-64 w-full">
             <canvas id="productsChart" width="400" height="200"></canvas>
           </div>
        </div>
      </div>

      <!-- Top Products Table -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 lg:mb-8 mobile-card">
        <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-[#2D3E50]">Top Selling Products</h3>
        </div>
                 <div class="table-container w-full">
           <table class="table-responsive w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales</th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php foreach ($top_products as $product): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-4 lg:px-6 py-4 text-sm font-medium text-gray-900">
                    <?php echo htmlspecialchars($product['title']); ?>
                  </td>
                  <td class="px-4 lg:px-6 py-4 text-sm text-gray-500">
                    GHS <?php echo number_format($product['price'], 2); ?>
                  </td>
                  <td class="px-4 lg:px-6 py-4 text-sm text-gray-500">
                    <?php echo $product['sales_count']; ?>
                  </td>
                  <td class="px-4 lg:px-6 py-4 text-sm font-bold text-[#4CAF50]">
                    GHS <?php echo number_format($product['total_revenue'], 2); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 mobile-card">
        <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-[#2D3E50]">Recent Activity</h3>
        </div>
        <div class="p-4 lg:p-6">
          <?php if (empty($recent_activity)): ?>
            <div class="text-center py-8">
              <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-500">No recent activity.</p>
              <p class="text-sm text-gray-400 mt-1">Activity will appear here as users interact with your platform.</p>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($recent_activity as $activity): ?>
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <?php if ($activity['type'] === 'order'): ?>
                      <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-green-600 text-sm"></i>
                      </div>
                    <?php elseif ($activity['type'] === 'guest_order'): ?>
                      <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-clock text-orange-600 text-sm"></i>
                      </div>
                    <?php else: ?>
                      <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                      <?php echo htmlspecialchars($activity['description']); ?>
                    </p>
                    <p class="text-sm text-gray-500">
                      <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                    </p>
                  </div>
                  <?php if ($activity['amount'] > 0): ?>
                    <div class="text-sm font-bold text-[#4CAF50]">
                      GHS <?php echo number_format($activity['amount'], 2); ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
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

    // Monthly Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?php echo json_encode($monthly_sales); ?>;
    
    new Chart(salesCtx, {
      type: 'line',
      data: {
        labels: salesData.map(item => {
          const date = new Date(item.month + '-01');
          return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }).reverse(),
        datasets: [{
          label: 'Revenue (GHS)',
          data: salesData.map(item => item.revenue).reverse(),
          borderColor: '#4CAF50',
          backgroundColor: 'rgba(76, 175, 80, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return 'GHS ' + value.toLocaleString();
              }
            }
          }
        }
      }
    });

    // Top Products Chart
    const productsCtx = document.getElementById('productsChart').getContext('2d');
    const productsData = <?php echo json_encode($top_products); ?>;
    
    new Chart(productsCtx, {
      type: 'doughnut',
      data: {
        labels: productsData.map(item => item.title),
        datasets: [{
          data: productsData.map(item => item.sales_count),
          backgroundColor: [
            '#4CAF50',
            '#2196F3',
            '#FF9800',
            '#9C27B0',
            '#F44336'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
</body>
</html>
