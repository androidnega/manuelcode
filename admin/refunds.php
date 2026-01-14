<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

$admin_username = $_SESSION['admin_name'] ?? 'Admin';

// Get refund statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_refunds FROM refunds");
$total_refunds = $stmt->fetch(PDO::FETCH_ASSOC)['total_refunds'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_refunds FROM refunds WHERE status = 'pending'");
$pending_refunds = $stmt->fetch(PDO::FETCH_ASSOC)['pending_refunds'];

$stmt = $pdo->query("SELECT COUNT(*) as approved_refunds FROM refunds WHERE status = 'approved'");
$approved_refunds = $stmt->fetch(PDO::FETCH_ASSOC)['approved_refunds'];

$stmt = $pdo->query("SELECT COUNT(*) as rejected_refunds FROM refunds WHERE status = 'rejected'");
$rejected_refunds = $stmt->fetch(PDO::FETCH_ASSOC)['rejected_refunds'];
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>manuelcode | Admin - Refunds</title>
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
    
    /* Enhanced Mobile Responsiveness */
    @media (max-width: 1024px) {
      .main-content {
        padding: 1rem;
      }
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }
      .stats-card {
        padding: 1.25rem;
      }
    }
    
    @media (max-width: 768px) {
      .main-content {
        padding: 0.75rem;
      }
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
      }
      .stats-card {
        padding: 1rem;
        border-radius: 12px;
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
        margin: 0 -0.75rem;
      }
      .table-responsive {
        min-width: 600px;
        padding: 0 0.75rem;
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
      }
    }
    
    @media (max-width: 640px) {
      .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
      }
      .mobile-header {
        padding: 0.75rem;
      }
      .mobile-title {
        font-size: 1.125rem;
      }
    }
    
    /* Enhanced sidebar for mobile */
    @media (max-width: 1024px) {
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
          <a href="purchase_management.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
            <span class="flex-1">Purchase Management</span>
          </a>
          <a href="users.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span class="flex-1">Users</span>
          </a>
          <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
            <span class="flex-1">Reports</span>
          </a>
          <a href="refunds.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-undo mr-3 w-5 text-center"></i>
            <span class="flex-1">Refunds</span>
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
            <h1 class="text-2xl font-bold text-[#2D3E50]">Refund Management</h1>
            <p class="text-gray-600 mt-1">Manage and process refund requests from users.</p>
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
          <h1 class="mobile-title font-semibold text-gray-800">Refunds</h1>
          <div class="w-8"></div> <!-- Spacer for centering -->
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="p-4 lg:p-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
          <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100 mobile-card">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-undo text-lg lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4 flex-1">
                <h2 class="text-gray-600 text-sm">Total Refunds</h2>
                <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]"><?php echo $total_refunds; ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100 mobile-card">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-clock text-lg lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4 flex-1">
                <h2 class="text-gray-600 text-sm">Pending</h2>
                <p class="text-lg lg:text-2xl font-bold text-[#F5A623]"><?php echo $pending_refunds; ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100 mobile-card">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check text-lg lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4 flex-1">
                <h2 class="text-gray-600 text-sm">Approved</h2>
                <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]"><?php echo $approved_refunds; ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100 mobile-card">
            <div class="flex items-center">
              <div class="p-2 lg:p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-times text-lg lg:text-xl"></i>
              </div>
              <div class="ml-3 lg:ml-4 flex-1">
                <h2 class="text-gray-600 text-sm">Rejected</h2>
                <p class="text-lg lg:text-2xl font-bold text-[#F44336]"><?php echo $rejected_refunds; ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Refunds Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 mobile-card">
          <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">Refund Requests</h2>
          </div>
          <div class="p-4 lg:p-6">
            <?php
            $stmt = $pdo->query("
              SELECT r.*, u.name as user_name, u.email as user_email, u.user_id, p.title as product_title, p.price as product_price
              FROM refunds r
              JOIN users u ON r.user_id = u.id
              JOIN purchases pur ON r.purchase_id = pur.id
              JOIN products p ON pur.product_id = p.id
              ORDER BY r.created_at DESC
            ");
            $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if ($refunds): ?>
              <div class="table-container">
                <table class="table-responsive w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <?php foreach ($refunds as $refund): ?>
                      <tr class="hover:bg-gray-50">
                        <td class="px-3 lg:px-4 py-3 text-sm">
                          <div>
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($refund['user_name']); ?></div>
                            <?php if ($refund['user_id']): ?>
                              <div class="text-gray-500 font-mono text-xs">ID: <?php echo htmlspecialchars($refund['user_id']); ?></div>
                            <?php endif; ?>
                            <div class="text-gray-500"><?php echo htmlspecialchars($refund['user_email']); ?></div>
                          </div>
                        </td>
                        <td class="px-3 lg:px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($refund['product_title']); ?></td>
                        <td class="px-3 lg:px-4 py-3 text-sm font-bold text-[#4CAF50]">GHS <?php echo number_format($refund['product_price'], 2); ?></td>
                        <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">
                          <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($refund['reason']); ?>">
                            <?php echo htmlspecialchars($refund['reason']); ?>
                          </div>
                        </td>
                        <td class="px-3 lg:px-4 py-3 text-sm">
                          <?php
                          $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800'
                          ];
                          $status_color = $status_colors[$refund['status']] ?? 'bg-gray-100 text-gray-800';
                          ?>
                          <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                            <?php echo ucfirst($refund['status']); ?>
                          </span>
                        </td>
                        <td class="px-3 lg:px-4 py-3 text-sm text-gray-600"><?php echo date('M j, Y', strtotime($refund['created_at'])); ?></td>
                        <td class="px-3 lg:px-4 py-3 text-sm">
                          <?php if ($refund['status'] === 'pending'): ?>
                            <div class="flex space-x-2">
                              <button onclick="processRefund(<?php echo $refund['id']; ?>, 'approved')" 
                                      class="mobile-button bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-1"></i>Approve
                              </button>
                              <button onclick="processRefund(<?php echo $refund['id']; ?>, 'rejected')" 
                                      class="mobile-button bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition-colors">
                                <i class="fas fa-times mr-1"></i>Reject
                              </button>
                            </div>
                          <?php else: ?>
                            <span class="text-gray-400 text-xs">Processed</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-8">
                <i class="fas fa-undo text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">No refund requests found.</p>
                <p class="text-sm text-gray-500 mt-1">Refund requests will appear here when users submit them.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    // Global function for sidebar toggle
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

    // Function to process refund
    function processRefund(refundId, status) {
      if (!confirm(`Are you sure you want to ${status} this refund request?`)) {
        return;
      }

      fetch('process_refund.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          refund_id: refundId,
          status: status
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(`Refund ${status} successfully!`);
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(error => {
        alert('Network error. Please try again.');
      });
    }
  </script>
</body>
</html>
