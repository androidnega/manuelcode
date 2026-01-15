<?php
    session_start();
include '../includes/db.php';
include '../includes/auth_only.php';
include '../includes/user_activity_tracker.php';
include '../includes/notification_helper.php';
include '../includes/dashboard_helper.php';

$notificationHelper = new NotificationHelper($pdo);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';

// Get real-time user dashboard statistics
$user_stats = getUserDashboardStats($pdo, $user_id);

// Get unread notification count
$unread_notifications = $notificationHelper->getUnreadCount($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Manuela</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Remove scrollbars */
        ::-webkit-scrollbar {
            display: none;
        }
        * {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        body {
            overflow-x: hidden;
        }
        .overflow-y-auto {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .overflow-y-auto::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50">
  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden transition-opacity duration-300" onclick="toggleSidebar()"></div>

      <!-- Layout Container -->
    <div class="flex min-h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-50 w-64 lg:w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen transition-transform duration-300 ease-in-out shadow-xl lg:shadow-none">
      <div class="flex items-center justify-between p-4 lg:p-6 border-b border-gray-200">
        <div class="font-bold text-lg lg:text-xl text-gray-800">Dashboard</div>
        <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900 p-2">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
              <div class="flex-1 overflow-y-auto">
        <nav class="mt-4 px-2 lg:px-4 pb-4 space-y-1">
          <a href="" class="flex items-center py-3 px-3 lg:px-4 bg-blue-50 text-blue-700 rounded-lg transition-colors w-full">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Overview</span>
          </a>
          <a href="my-purchases" class="flex items-center py-3 px-3 lg:px-4 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full">
            <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
            <span class="flex-1">My Purchases</span>
          </a>
          <a href="downloads" class="flex items-center py-3 px-3 lg:px-4 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full">
            <i class="fas fa-download mr-3 w-5 text-center"></i>
            <span class="flex-1">Downloads</span>
          </a>
          <a href="receipts" class="flex items-center py-3 px-3 lg:px-4 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full">
            <i class="fas fa-receipt mr-3 w-5 text-center"></i>
            <span class="flex-1">Receipts</span>
          </a>
          <a href="refunds" class="flex items-center py-3 px-3 lg:px-4 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full">
            <i class="fas fa-undo mr-3 w-5 text-center"></i>
            <span class="flex-1">Refunds</span>
          </a>
          <a href="support" class="flex items-center py-3 px-3 lg:px-4 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full">
            <i class="fas fa-headset mr-3 w-5 text-center"></i>
            <span class="flex-1">Support</span>
          </a>
          <a href="settings" class="flex items-center py-3 px-3 lg:px-4 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors w-full">
            <i class="fas fa-cog mr-3 w-5 text-center"></i>
            <span class="flex-1">Settings</span>
          </a>
        </nav>
      </div>
      
      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center mb-3">
          <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
            <p class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($user_unique_id); ?></p>
          </div>
        </div>
        <a href="/auth/logout.php" class="flex items-center py-2 px-4 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
          <i class="fas fa-sign-out-alt mr-3"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

          <!-- Main Content -->
      <div class="flex-1 lg:ml-0 min-h-screen overflow-hidden">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</p>
          </div>
          <div class="flex items-center space-x-4">
            <a href="notifications" class="text-gray-600 hover:text-blue-600 transition-colors relative">
              <i class="fas fa-bell mr-2"></i>Notifications
              <?php if ($unread_notifications > 0): ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                  <?php echo $unread_notifications; ?>
                </span>
              <?php endif; ?>
            </a>
            <a href="../index.php" class="text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-home mr-2"></i>Home
            </a>
            <a href="../store.php" class="text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-store mr-2"></i>Store
            </a>
          </div>
        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden bg-white border-gray-200 border-b sticky top-0 z-30">
        <div class="flex items-center justify-between p-4">
          <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
          <div class="w-8"></div>
        </div>
      </header>

              <!-- Main Content Area -->
        <main class="p-4 lg:p-6 overflow-hidden">
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
          <div class="bg-gradient-to-br from-slate-100 to-slate-200 text-slate-700 rounded-xl p-3 lg:p-6 border border-slate-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-slate-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-shopping-bag text-base lg:text-xl text-slate-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h2 class="text-xs font-medium text-slate-600 truncate">Total Purchases</h2>
                <p class="text-lg lg:text-2xl font-bold text-slate-800 truncate"><span id="total-purchases"><?php echo $user_stats['total_purchases']; ?></span></p>
              </div>
            </div>
          </div>
          
          <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-700 rounded-xl p-3 lg:p-6 border border-emerald-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-emerald-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-dollar-sign text-base lg:text-xl text-emerald-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h2 class="text-xs font-medium text-emerald-600 truncate">Total Spent</h2>
                <p class="text-lg lg:text-2xl font-bold text-emerald-800 truncate"><span id="total-spent">GHS <?php echo number_format($user_stats['total_spent'], 2); ?></span></p>
              </div>
            </div>
          </div>
          
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 text-blue-700 rounded-xl p-3 lg:p-6 border border-blue-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-blue-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-download text-base lg:text-xl text-blue-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h2 class="text-xs font-medium text-blue-600 truncate">Downloads</h2>
                <p class="text-lg lg:text-2xl font-bold text-blue-800 truncate"><span id="total-downloads"><?php echo $user_stats['total_downloads']; ?></span></p>
              </div>
            </div>
          </div>
          
          <div class="bg-gradient-to-br from-amber-50 to-amber-100 text-amber-700 rounded-xl p-3 lg:p-6 border border-amber-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-amber-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-bell text-base lg:text-xl text-amber-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h2 class="text-xs font-medium text-amber-600 truncate">Notifications</h2>
                <p class="text-lg lg:text-2xl font-bold text-amber-800 truncate">
                  <span id="total-notifications"><?php echo $unread_notifications; ?></span>
                  <?php if ($unread_notifications > 0): ?>
                    <span class="text-xs opacity-75">unread</span>
                  <?php endif; ?>
                </p>
              </div>
              <?php if ($unread_notifications > 0): ?>
                <a href="notifications" class="text-amber-600 hover:text-amber-700 transition-colors flex-shrink-0 ml-1">
                  <i class="fas fa-arrow-right"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Quick Actions Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
          <a href="my-purchases" class="bg-gradient-to-br from-slate-50 to-slate-100 text-slate-700 rounded-xl p-3 lg:p-6 border border-slate-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-slate-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-shopping-bag text-base lg:text-xl text-slate-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h3 class="text-xs lg:text-base font-semibold text-slate-800 truncate">My Purchases</h3>
                <p class="text-xs text-slate-600 truncate">View all purchases</p>
              </div>
            </div>
          </a>
          
          <a href="downloads" class="bg-gradient-to-br from-blue-50 to-blue-100 text-blue-700 rounded-xl p-3 lg:p-6 border border-blue-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-blue-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-download text-base lg:text-xl text-blue-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h3 class="text-xs lg:text-base font-semibold text-blue-800 truncate">Downloads</h3>
                <p class="text-xs text-blue-600 truncate">Access your files</p>
              </div>
            </div>
          </a>
          
          <a href="receipts" class="bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-700 rounded-xl p-3 lg:p-6 border border-emerald-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-emerald-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-receipt text-base lg:text-xl text-emerald-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h3 class="text-xs lg:text-base font-semibold text-emerald-800 truncate">Receipts</h3>
                <p class="text-xs text-emerald-600 truncate">View receipts</p>
              </div>
            </div>
          </a>
          
          <a href="settings" class="bg-gradient-to-br from-amber-50 to-amber-100 text-amber-700 rounded-xl p-3 lg:p-6 border border-amber-200">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 bg-amber-300 bg-opacity-50 rounded-lg flex-shrink-0">
                <i class="fas fa-cog text-base lg:text-xl text-amber-600"></i>
              </div>
              <div class="ml-2 lg:ml-4 min-w-0 flex-1">
                <h3 class="text-xs lg:text-base font-semibold text-amber-800 truncate">Settings</h3>
                <p class="text-xs text-amber-600 truncate">Manage account</p>
              </div>
            </div>
          </a>
        </div>

        <!-- Recent Purchases -->
        <?php if (!empty($user_stats['recent_purchases'])): ?>
        <div class="bg-white rounded-xl border border-gray-100 mb-6 lg:mb-8">
          <div class="px-3 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
            <h2 class="text-base lg:text-xl font-semibold text-gray-800">Recent Purchases</h2>
          </div>
          <div class="p-2 lg:p-6">
            <div class="overflow-x-auto -mx-2 lg:mx-0">
              <table class="w-full min-w-full">
                <thead class="hidden sm:table-header-group">
                  <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 lg:px-4 font-medium text-gray-700 text-xs lg:text-sm">Product</th>
                    <th class="text-left py-2 px-2 lg:px-4 font-medium text-gray-700 text-xs lg:text-sm hidden md:table-cell">Price</th>
                    <th class="text-left py-2 px-2 lg:px-4 font-medium text-gray-700 text-xs lg:text-sm hidden lg:table-cell">Downloads</th>
                    <th class="text-left py-2 px-2 lg:px-4 font-medium text-gray-700 text-xs lg:text-sm hidden sm:table-cell">Date</th>
                    <th class="text-left py-2 px-2 lg:px-4 font-medium text-gray-700 text-xs lg:text-sm hidden lg:table-cell">Status</th>
                    <th class="text-left py-2 px-2 lg:px-4 font-medium text-gray-700 text-xs lg:text-sm">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($user_stats['recent_purchases'] as $purchase): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                      <td class="py-2 px-2 lg:px-4">
                        <div class="flex items-center min-w-0">
                          <?php if (isset($purchase['preview_image']) && $purchase['preview_image']): ?>
                            <img src="../assets/images/products/<?php echo htmlspecialchars($purchase['preview_image']); ?>" alt="<?php echo htmlspecialchars($purchase['product_title']); ?>" class="w-8 h-8 lg:w-10 lg:h-10 rounded object-cover mr-2 lg:mr-3 flex-shrink-0">
                          <?php else: ?>
                            <div class="w-8 h-8 lg:w-10 lg:h-10 bg-gray-200 rounded flex items-center justify-center mr-2 lg:mr-3 flex-shrink-0">
                              <i class="fas fa-file text-gray-400 text-xs lg:text-sm"></i>
                            </div>
                          <?php endif; ?>
                          <span class="font-medium text-gray-900 text-xs lg:text-sm truncate"><?php echo htmlspecialchars($purchase['product_title']); ?></span>
                        </div>
                        <!-- Mobile: Show price and date inline -->
                        <div class="sm:hidden mt-1 text-xs text-gray-600">
                          <span class="font-semibold text-green-600">
                            <?php 
                            $original_price = $purchase['original_price'] ?? $purchase['price'];
                            $final_amount = $purchase['amount'] ?? 0;
                            echo 'GHS ' . number_format($final_amount > 0 ? $final_amount : $original_price, 2);
                            ?>
                          </span>
                          <span class="mx-2">•</span>
                          <span><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></span>
                        </div>
                      </td>
                      <td class="py-2 px-2 lg:px-4 font-semibold text-green-600 text-xs lg:text-sm hidden md:table-cell">
                        <?php 
                        // Show appropriate price based on purchase type
                        $original_price = $purchase['original_price'] ?? $purchase['price'];
                        $discount_amount = $purchase['discount_amount'] ?? 0;
                        $final_amount = $purchase['amount'] ?? 0;
                        $purchase_type = $purchase['purchase_type'] ?? 'Paid';
                        
                        if ($purchase_type === 'FREE with coupon') {
                            // Product was free with coupon
                            echo '<span class="text-green-600 font-bold">FREE</span>';
                            echo '<br><span class="text-xs text-gray-500 line-through">₵' . number_format($original_price, 2) . '</span>';
                            echo '<br><span class="text-xs text-blue-600">Coupon applied</span>';
                        } elseif ($purchase_type === 'FREE') {
                            // Product was originally free
                            echo '<span class="text-green-600 font-bold">FREE</span>';
                        } else {
                            // Regular paid purchase
                            if ($discount_amount > 0) {
                                echo '<span class="text-green-600">₵' . number_format($final_amount, 2) . '</span>';
                                echo '<br><span class="text-xs text-gray-500 line-through">₵' . number_format($original_price, 2) . '</span>';
                                echo '<br><span class="text-xs text-blue-600">-₵' . number_format($discount_amount, 2) . '</span>';
                            } else {
                                echo 'GHS ' . number_format($original_price, 2);
                            }
                        }
                        ?>
                      </td>
                      <td class="py-2 px-2 lg:px-4 text-gray-600 text-xs lg:text-sm hidden lg:table-cell">
                        <div class="text-center">
                          <div class="font-medium text-gray-900">
                            <?php echo $purchase['download_count'] ?? 0; ?>
                          </div>
                          <div class="text-xs text-gray-500">downloads</div>
                        </div>
                      </td>
                      <td class="py-2 px-2 lg:px-4 text-gray-600 text-xs lg:text-sm hidden sm:table-cell"><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></td>
                      <td class="py-2 px-2 lg:px-4 hidden lg:table-cell">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                          Completed
                        </span>
                      </td>
                      <td class="py-2 px-2 lg:px-4">
                        <div class="flex flex-wrap gap-1.5 lg:gap-2 w-full sm:w-auto">
                          <?php if (isset($purchase['doc_file']) && $purchase['doc_file'] || isset($purchase['drive_link']) && $purchase['drive_link']): ?>
                            <a href="downloads" class="inline-flex items-center justify-center px-2 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                              <i class="fas fa-download mr-1 text-xs"></i><span>Download</span>
                            </a>
                          <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1.5 text-gray-400 text-xs whitespace-nowrap">File not available</span>
                          <?php endif; ?>
                          <a href="my-purchases" class="inline-flex items-center justify-center px-2 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors whitespace-nowrap">
                            <i class="fas fa-eye mr-1 text-xs"></i><span>View</span>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if (count($user_stats['recent_purchases']) > 5): ?>
              <div class="mt-4 text-center">
                <a href="my-purchases" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                  View All Purchases
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </main>
    </div>

    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        
        // Check if sidebar is visible (not translated off-screen)
        const isOpen = !sidebar.classList.contains('-translate-x-full') || sidebar.classList.contains('translate-x-0');
        
        if (isOpen) {
          // Close sidebar
          sidebar.classList.remove('translate-x-0');
          sidebar.classList.add('-translate-x-full');
          if (overlay) overlay.classList.add('hidden');
          document.body.style.overflow = '';
        } else {
          // Open sidebar
          sidebar.classList.remove('-translate-x-full');
          sidebar.classList.add('translate-x-0');
          if (overlay) overlay.classList.remove('hidden');
          document.body.style.overflow = 'hidden';
        }
      }
      
      // Close sidebar when clicking overlay
      const overlay = document.getElementById('mobile-overlay');
      if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
      }
      
      // Real-time dashboard statistics refresh
      function refreshDashboardStats() {
        // Try multiple paths for the stats endpoint
        const paths = [
          'get_dashboard_stats.php',
          './get_dashboard_stats.php',
          '../dashboard/get_dashboard_stats.php'
        ];
        
        let fetchPath = paths[0];
        
        fetch(fetchPath)
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              return response.text().then(text => {
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
              });
            }
            return response.json();
          })
          .then(data => {
            if (data.success) {
              // Update statistics only if elements exist
              const totalPurchases = document.getElementById('total-purchases');
              const totalSpent = document.getElementById('total-spent');
              const totalDownloads = document.getElementById('total-downloads');
              const totalReceipts = document.getElementById('total-receipts');
              const totalNotifications = document.getElementById('total-notifications');
              
              if (totalPurchases) totalPurchases.textContent = data.stats.total_purchases || 0;
              if (totalSpent) totalSpent.textContent = 'GHS ' + parseFloat(data.stats.total_spent || 0).toFixed(2);
              if (totalDownloads) totalDownloads.textContent = data.stats.total_downloads || 0;
              if (totalReceipts) totalReceipts.textContent = data.stats.total_receipts || 0;
              if (totalNotifications) totalNotifications.textContent = data.stats.unread_notifications || 0;
            }
          })
          .catch(error => {
            // Silently fail - don't spam console
            console.debug('Dashboard stats refresh:', error.message);
          });
      }
      
      // Refresh stats every 30 seconds
      setInterval(refreshDashboardStats, 30000);
      
      // Also refresh when page becomes visible
      document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
          refreshDashboardStats();
        }
      });
      
      // Initial refresh after page load
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(refreshDashboardStats, 1000);
      });
    </script>
</body>
</html>
