<?php
session_start();
include '../includes/db.php';
include '../includes/auth_only.php';

// Initialize dark mode from session
if (!isset($_SESSION['user_dark_mode'])) {
    $_SESSION['user_dark_mode'] = false;
}
$dark_mode = $_SESSION['user_dark_mode'];

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';

// Get user's purchases that might be eligible for refunds
$stmt = $pdo->prepare("
    SELECT p.*, pr.title as product_title, pr.price, pr.preview_image
    FROM purchases p 
    JOIN products pr ON p.product_id = pr.id 
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's refund requests
$stmt = $pdo->prepare("
    SELECT r.*, pr.title as product_title, pr.price
    FROM refunds r
    JOIN products pr ON r.product_id = pr.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Refunds - ManuelCode</title>
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.2s ease;
      border: none;
      cursor: pointer;
    }
    
    .btn-primary:hover {
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
    
    .btn-danger {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.2s ease;
      border: none;
      cursor: pointer;
    }
    
    .btn-danger:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
      .table-container {
        overflow-x: auto;
        border-radius: 8px;
      }
      .table-responsive {
        min-width: 600px;
      }
      .refund-table th,
      .refund-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
      }
      .refund-actions {
        flex-direction: column;
        gap: 0.5rem;
      }
      .refund-actions .btn-danger,
      .refund-actions .btn-secondary {
        width: 100%;
        text-align: center;
      }
         }
     
     /* Dark mode styles */
     .dark .dashboard-card {
       background: #1f2937;
       border: 1px solid #374151;
       color: white;
     }
     
     .dark .dashboard-card:hover {
       box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
     }
     
     .dark .table-container {
       border: 1px solid #374151;
     }
     
     .dark .table-responsive th {
       background-color: #374151;
       color: #f9fafb;
       border-bottom: 1px solid #4b5563;
     }
     
     .dark .table-responsive td {
       border-bottom: 1px solid #4b5563;
     }
     
     .dark .table-responsive tr:hover {
       background-color: #374151;
     }
     
     .dark .btn-secondary {
       background: #374151;
       color: #f9fafb;
       border: 1px solid #4b5563;
     }
     
     .dark .btn-secondary:hover {
       background: #4b5563;
     }
   </style>
</head>
<body class="<?php echo $dark_mode ? 'bg-gray-900 dark' : 'bg-gray-50'; ?>">
  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

  <!-- Layout Container -->
  <div class="flex">
         <!-- Sidebar -->
     <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 <?php echo $dark_mode ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'; ?> border-r transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
       <div class="flex items-center justify-between p-6 border-b <?php echo $dark_mode ? 'border-gray-700' : 'border-gray-200'; ?>">
         <div class="font-bold text-xl <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Dashboard</div>
         <button onclick="toggleSidebar()" class="lg:hidden <?php echo $dark_mode ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
           <i class="fas fa-times text-xl"></i>
         </button>
       </div>
      
      <div class="flex-1 overflow-y-auto scrollbar-hide">
                 <nav class="mt-4 px-4 pb-4">
           <a href="index.php" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
             <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
             <span class="flex-1">Overview</span>
           </a>
           <a href="my_purchases.php" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
             <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
             <span class="flex-1">My Purchases</span>
           </a>
           <a href="downloads.php" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
             <i class="fas fa-download mr-3 w-5 text-center"></i>
             <span class="flex-1">Downloads</span>
           </a>
           <a href="refunds.php" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'bg-red-900 text-red-300' : 'bg-red-50 text-red-700'; ?> rounded-lg mb-2 transition-colors w-full">
             <i class="fas fa-undo mr-3 w-5 text-center"></i>
             <span class="flex-1">Refunds</span>
           </a>
           <a href="settings.php" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
             <i class="fas fa-cog mr-3 w-5 text-center"></i>
             <span class="flex-1">Settings</span>
           </a>
         </nav>
      </div>
      
             <div class="p-4 border-t <?php echo $dark_mode ? 'border-gray-700' : 'border-gray-200'; ?>">
         <div class="flex items-center mb-3">
           <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
             <?php echo strtoupper(substr($user_name, 0, 1)); ?>
           </div>
           <div class="ml-3">
             <p class="text-sm font-medium <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($user_name); ?></p>
             <p class="text-xs <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?>">ID: <?php echo htmlspecialchars($user_unique_id); ?></p>
           </div>
         </div>
         <a href="../auth/logout.php" class="flex items-center py-2 px-4 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
             <h1 class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Refunds</h1>
             <p class="<?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?> mt-1">Request refunds for your purchases</p>
           </div>
           <div class="flex items-center space-x-4">
             <a href="../index.php" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'; ?> transition-colors">
               <i class="fas fa-home mr-2"></i>Home
             </a>
             <a href="../store.php" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'; ?> transition-colors">
               <i class="fas fa-store mr-2"></i>Store
             </a>
           </div>
         </div>
       </header>

       <!-- Mobile Header -->
       <header class="lg:hidden <?php echo $dark_mode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'; ?> border-b mobile-header">
         <div class="flex items-center justify-between">
           <button onclick="toggleSidebar()" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900'; ?> p-2">
             <i class="fas fa-bars text-xl"></i>
           </button>
           <h1 class="mobile-title font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Refunds</h1>
           <div class="w-8"></div>
         </div>
       </header>

      <!-- Main Content Area -->
      <main class="main-content p-6">
        <!-- Success/Error Messages -->
        <div id="message-container"></div>

                 <!-- Refund Policy -->
         <div class="dashboard-card mb-6">
           <div class="px-6 py-4 border-b <?php echo $dark_mode ? 'border-gray-600' : 'border-gray-200'; ?>">
             <h2 class="text-lg font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Refund Policy</h2>
           </div>
          <div class="p-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <h3 class="text-sm font-semibold text-blue-800 mb-2">Our Refund Policy</h3>
              <ul class="text-sm text-blue-700 space-y-1">
                <li>• Refunds are available within 7 days of purchase</li>
                <li>• Digital products are non-refundable after download</li>
                <li>• Technical issues will be resolved before refund consideration</li>
                <li>• Refund requests are reviewed within 24-48 hours</li>
              </ul>
            </div>
                         <p class="text-sm <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>">If you're experiencing issues with a product, please contact our support team first. We'll do our best to resolve any problems before processing a refund.</p>
          </div>
        </div>

                 <!-- My Refund Requests -->
         <?php if (!empty($refunds)): ?>
         <div class="dashboard-card mb-6">
           <div class="px-6 py-4 border-b <?php echo $dark_mode ? 'border-gray-600' : 'border-gray-200'; ?>">
             <h2 class="text-lg font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">My Refund Requests</h2>
           </div>
          <div class="p-6">
            <div class="table-container">
              <table class="table-responsive w-full refund-table">
                <thead>
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <?php foreach ($refunds as $refund): ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($refund['product_title']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                        GHS <?php echo number_format($refund['amount'], 2); ?>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="max-w-xs truncate"><?php echo htmlspecialchars($refund['reason']); ?></div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'completed' => 'bg-blue-100 text-blue-800'
                        ];
                        $status_color = $status_colors[$refund['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                          <?php echo ucfirst($refund['status']); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($refund['created_at'])); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

                 <!-- Request New Refund -->
         <div class="dashboard-card">
           <div class="px-6 py-4 border-b <?php echo $dark_mode ? 'border-gray-600' : 'border-gray-200'; ?>">
             <h2 class="text-lg font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Request New Refund</h2>
           </div>
          <div class="p-6">
            <?php if (empty($purchases)): ?>
                             <div class="text-center py-8">
                 <i class="fas fa-undo text-4xl <?php echo $dark_mode ? 'text-gray-500' : 'text-gray-300'; ?> mb-4"></i>
                 <p class="<?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?> mb-2">No purchases available for refund</p>
                 <p class="text-sm <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?> mb-4">You need to make purchases before requesting refunds.</p>
                <a href="../store.php" class="btn-primary inline-flex items-center">
                  <i class="fas fa-store mr-2"></i>
                  Browse Store
                </a>
              </div>
            <?php else: ?>
              <div class="table-container">
                <table class="table-responsive w-full refund-table">
                  <thead>
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <?php foreach ($purchases as $purchase): ?>
                      <?php 
                      $purchase_date = new DateTime($purchase['created_at']);
                      $now = new DateTime();
                      $days_diff = $now->diff($purchase_date)->days;
                      $is_eligible = $days_diff <= 7;
                      
                      // Check if refund already exists
                      $has_refund = false;
                      foreach ($refunds as $refund) {
                          if ($refund['purchase_id'] == $purchase['id'] && in_array($refund['status'], ['pending', 'approved'])) {
                              $has_refund = true;
                              break;
                          }
                      }
                      ?>
                                             <tr class="<?php echo $dark_mode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'; ?>">
                         <td class="px-6 py-4 whitespace-nowrap">
                           <div class="flex items-center">
                             <?php if ($purchase['preview_image']): ?>
                               <img src="../assets/images/products/<?php echo htmlspecialchars($purchase['preview_image']); ?>" alt="<?php echo htmlspecialchars($purchase['product_title']); ?>" class="w-10 h-10 rounded object-cover mr-3">
                             <?php else: ?>
                               <div class="w-10 h-10 <?php echo $dark_mode ? 'bg-gray-600' : 'bg-gray-200'; ?> rounded flex items-center justify-center mr-3">
                                 <i class="fas fa-file <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-400'; ?>"></i>
                               </div>
                             <?php endif; ?>
                             <div>
                               <div class="text-sm font-medium <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($purchase['product_title']); ?></div>
                               <div class="text-sm <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?>">Digital Product</div>
                             </div>
                           </div>
                         </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                          GHS <?php echo number_format($purchase['price'], 2); ?>
                        </td>
                                                 <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?>">
                           <?php echo date('M j, Y', strtotime($purchase['created_at'])); ?>
                         </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <?php if ($has_refund): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                              Refund Requested
                            </span>
                          <?php elseif ($is_eligible): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                              Eligible for Refund
                            </span>
                          <?php else: ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                              Not Eligible
                            </span>
                          <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <div class="refund-actions flex space-x-2">
                            <?php if ($is_eligible && !$has_refund): ?>
                              <button onclick="showRefundModal(<?php echo $purchase['id']; ?>, '<?php echo htmlspecialchars($purchase['product_title']); ?>')" class="btn-danger text-sm">
                                <i class="fas fa-undo mr-1"></i>Request Refund
                              </button>
                            <?php elseif ($has_refund): ?>
                              <span class="text-gray-400 text-sm">Refund requested</span>
                                                         <?php else: ?>
                               <span class="<?php echo $dark_mode ? 'text-gray-500' : 'text-gray-400'; ?> text-sm">Refund period expired</span>
                             <?php endif; ?>
                            <a href="my_purchases.php" class="btn-secondary text-sm">
                              <i class="fas fa-eye mr-1"></i>View Details
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>

     <!-- Refund Modal -->
   <div id="refundModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
     <div class="flex items-center justify-center min-h-screen p-4">
       <div class="<?php echo $dark_mode ? 'bg-gray-800' : 'bg-white'; ?> rounded-lg max-w-md w-full p-6">
         <div class="flex items-center justify-between mb-4">
           <h3 class="text-lg font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>">Request Refund</h3>
           <button onclick="closeRefundModal()" class="<?php echo $dark_mode ? 'text-gray-400 hover:text-gray-300' : 'text-gray-400 hover:text-gray-600'; ?>">
             <i class="fas fa-times"></i>
           </button>
         </div>
        <form id="refundForm">
          <input type="hidden" id="purchaseId" name="purchase_id">
                     <div class="mb-4">
             <label class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Product</label>
             <p id="productTitle" class="text-sm <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>"></p>
           </div>
           <div class="mb-4">
             <label for="refundReason" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Reason for Refund *</label>
             <textarea id="refundReason" name="reason" rows="4" class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Please explain why you're requesting a refund..."></textarea>
           </div>
          <div class="flex space-x-3">
            <button type="button" onclick="closeRefundModal()" class="flex-1 btn-secondary">
              Cancel
            </button>
            <button type="submit" class="flex-1 btn-danger">
              Submit Request
            </button>
          </div>
        </form>
      </div>
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

    function showRefundModal(purchaseId, productTitle) {
      document.getElementById('purchaseId').value = purchaseId;
      document.getElementById('productTitle').textContent = productTitle;
      document.getElementById('refundModal').classList.remove('hidden');
    }

    function closeRefundModal() {
      document.getElementById('refundModal').classList.add('hidden');
      document.getElementById('refundForm').reset();
    }

    document.getElementById('refundForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('process_refund.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showMessage(data.message, 'success');
          closeRefundModal();
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          showMessage(data.message, 'error');
        }
      })
      .catch(error => {
        showMessage('An error occurred. Please try again.', 'error');
      });
    });

    function showMessage(message, type) {
      const container = document.getElementById('message-container');
      const alertClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
      const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
      
      container.innerHTML = `
        <div class="border rounded-lg p-4 mb-6 ${alertClass}">
          <div class="flex">
            <i class="${iconClass} mt-1 mr-3"></i>
            <p>${message}</p>
          </div>
        </div>
      `;
      
      setTimeout(() => {
        container.innerHTML = '';
      }, 5000);
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
