<?php
session_start();
include '../includes/db.php';
include '../includes/auth_only.php';
include '../includes/product_functions.php';
include '../includes/util.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';

// Get user's purchases with product information (including guest purchases by email)
$user_email = $_SESSION['user_email'] ?? null;
$purchases = getAllPurchasedProducts($user_id, $user_email);

// FIXED: Filter to ensure only paid purchases are shown
$purchases = array_filter($purchases, function($purchase) {
    return $purchase['status'] === 'paid';
});

// FIXED: Remove duplicates by product_id (keep only the most recent)
$unique_purchases = [];
$seen_products = [];

foreach ($purchases as $purchase) {
    if (!in_array($purchase['product_id'], $seen_products)) {
        $unique_purchases[] = $purchase;
        $seen_products[] = $purchase['product_id'];
    }
}

$purchases = $unique_purchases;
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Purchases - ManuelCode</title>
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
     
     /* Text overflow utilities */
     .truncate {
       overflow: hidden;
       text-overflow: ellipsis;
       white-space: nowrap;
     }
     
     .line-clamp-2 {
       display: -webkit-box;
       -webkit-line-clamp: 2;
       -webkit-box-orient: vertical;
       overflow: hidden;
     }
    
         /* Purchase Grid and Card Styles */
     .purchase-grid {
       display: grid;
       grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
       gap: 1rem;
       padding: 0;
     }
     
     .purchase-card {
       padding: 0.75rem;
       max-width: 100%;
       overflow: hidden;
     }
     
     .purchase-image {
       height: 100px;
       width: 100%;
       object-fit: cover;
     }
     
     .purchase-actions {
       gap: 0.5rem;
     }
     
     .purchase-actions .btn-primary,
     .purchase-actions .btn-secondary {
       padding: 0.375rem 0.75rem;
       font-size: 0.875rem;
     }
     
     /* Mobile Responsive Styles */
     @media (max-width: 768px) {
       .main-content {
         padding: 0.75rem;
       }
       .mobile-header {
         padding: 0.75rem;
         position: sticky;
         top: 0;
         z-index: 30;
         background: white;
         border-bottom: 1px solid #e5e7eb;
       }
       .mobile-title {
         font-size: 1.125rem;
         font-weight: 600;
       }
       .purchase-grid {
         grid-template-columns: 1fr;
         gap: 0.75rem;
       }
       .purchase-card {
         padding: 0.5rem;
       }
       .purchase-image {
         height: 80px;
       }
       .purchase-actions {
         flex-direction: column;
         gap: 0.375rem;
       }
       .purchase-actions .btn-primary,
       .purchase-actions .btn-secondary {
         width: 100%;
         text-align: center;
         padding: 0.25rem 0.5rem;
         font-size: 0.75rem;
       }
     }
     
     @media (min-width: 769px) and (max-width: 1024px) {
       .purchase-grid {
         grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
         gap: 1rem;
       }
       .purchase-card {
         padding: 0.625rem;
       }
       .purchase-image {
         height: 90px;
       }
     }
     
     @media (min-width: 1025px) {
       .purchase-grid {
         grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
         gap: 1.25rem;
       }
       .purchase-card {
         padding: 0.75rem;
       }
       .purchase-image {
         height: 100px;
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
          <a href="my-purchases" class="flex items-center py-3 px-4 bg-blue-50 text-blue-700 rounded-lg mb-2 transition-colors w-full">
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
          <a href="settings" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
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
    <div class="flex-1 lg:ml-0 min-h-screen">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">My Purchases</h1>
            <p class="text-gray-600 mt-1">View and manage your purchased products</p>
          </div>
          <div class="flex items-center space-x-4">
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
      <header class="lg:hidden bg-white border-b border-gray-200 mobile-header">
        <div class="flex items-center justify-between">
          <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="mobile-title font-semibold text-gray-800">My Purchases</h1>
          <div class="w-8"></div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-6">
        <!-- Purchases Section -->
        <div class="dashboard-card">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Your Purchased Products</h2>
          </div>
          <div class="p-6">
            <?php if (empty($purchases)): ?>
              <div class="text-center py-8">
                <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 mb-2">No purchases yet</p>
                <p class="text-sm text-gray-500 mb-4">You haven't purchased any products yet.</p>
                <a href="../store.php" class="btn-primary inline-flex items-center">
                  <i class="fas fa-store mr-2"></i>
                  Browse Store
                </a>
              </div>
            <?php else: ?>
              <div class="purchase-grid grid">
                <?php foreach ($purchases as $purchase): ?>
                  <div class="dashboard-card purchase-card">
                    <!-- Product Image -->
                    <div class="mb-4">
                      <?php if ($purchase['preview_image']): ?>
                        <img src="../assets/images/products/<?php echo htmlspecialchars($purchase['preview_image']); ?>" 
                             alt="<?php echo htmlspecialchars($purchase['product_title']); ?>" 
                             class="w-full purchase-image object-cover rounded-lg">
                      <?php else: ?>
                        <div class="w-full purchase-image bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                          <i class="fas fa-box text-white text-3xl"></i>
                        </div>
                      <?php endif; ?>
                    </div>
                    
                                         <!-- Product Info -->
                     <div class="mb-3">
                       <h3 class="text-base font-semibold text-gray-900 mb-1 truncate"><?php echo htmlspecialchars($purchase['product_title']); ?></h3>
                       <?php if ($purchase['short_desc']): ?>
                         <p class="text-xs text-gray-600 mb-2 line-clamp-2"><?php echo htmlspecialchars($purchase['short_desc']); ?></p>
                       <?php endif; ?>
                       
                       <div class="flex items-center justify-between mb-2">
                         <div class="flex items-center space-x-1">
                           <span class="text-xs text-gray-500">Price:</span>
                           <?php 
                           $amount = $purchase['amount'] ?? $purchase['price'];
                           $discount_amount = $purchase['discount_amount'] ?? 0;
                           $original_amount = $purchase['original_amount'] ?? $purchase['price'];
                           $final_amount = $amount - $discount_amount;
                           
                           // Determine purchase type
                           $purchase_type = '';
                           if ($amount == 0 && $discount_amount > 0) {
                               $purchase_type = 'FREE with coupon';
                           } elseif ($amount == 0 && $discount_amount == 0) {
                               $purchase_type = 'FREE';
                           } else {
                               $purchase_type = 'Paid';
                           }
                           ?>
                           <div class="flex flex-col">
                             <?php if ($purchase_type === 'FREE with coupon'): ?>
                                 <span class="text-xs font-medium text-green-600">FREE</span>
                                 <span class="text-xs text-gray-500 line-through">₵<?php echo number_format($original_amount, 2); ?></span>
                                 <span class="text-xs text-blue-600">Coupon applied</span>
                             <?php elseif ($purchase_type === 'FREE'): ?>
                                 <span class="text-xs font-medium text-green-600">FREE</span>
                             <?php else: ?>
                                 <span class="text-xs font-medium text-green-600">GHS <?php echo number_format($final_amount, 2); ?></span>
                                 <?php if ($discount_amount > 0): ?>
                                     <span class="text-xs text-gray-500 line-through">₵<?php echo number_format($original_amount, 2); ?></span>
                                     <span class="text-xs text-green-500">-₵<?php echo number_format($discount_amount, 2); ?> discount</span>
                                 <?php endif; ?>
                             <?php endif; ?>
                           </div>
                         </div>
                         <div class="flex items-center space-x-2">
                           <?php if (isset($purchase['purchase_type']) && $purchase['purchase_type'] === 'guest'): ?>
                             <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                               <i class="fas fa-user mr-1"></i>Guest
                             </span>
                           <?php endif; ?>
                           <div class="flex items-center space-x-1">
                             <span class="text-xs text-gray-500">Status:</span>
                             <?php 
                             $status_class = '';
                             $status_text = '';
                             switch($purchase['status']) {
                                 case 'paid':
                                     $status_class = 'bg-green-100 text-green-800';
                                     $status_text = 'Completed';
                                     break;
                                 case 'pending':
                                     $status_class = 'bg-yellow-100 text-yellow-800';
                                     $status_text = 'Pending';
                                     break;
                                 case 'failed':
                                     $status_class = 'bg-red-100 text-red-800';
                                     $status_text = 'Failed';
                                     break;
                                 case 'refunded':
                                     $status_class = 'bg-gray-100 text-gray-800';
                                     $status_text = 'Refunded';
                                     break;
                                 default:
                                     $status_class = 'bg-gray-100 text-gray-800';
                                     $status_text = ucfirst($purchase['status']);
                             }
                             ?>
                             <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                               <?php echo $status_text; ?>
                             </span>
                           </div>
                         </div>
                       </div>
                       
                       <div class="text-xs text-gray-500">
                         <span>Purchased: <?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></span>
                       </div>
                     </div>
                    
                                         <!-- Action Buttons -->
                     <div class="purchase-actions flex space-x-2">
                       <?php 
                       // FIXED: Always check the actual Google Drive link from the database
                       $download_link = null;
                       $download_type = null;
                       
                       // Get the actual product details including Google Drive link
                       $stmt = $pdo->prepare("
                           SELECT pr.drive_link, pr.doc_file, pr.title
                           FROM products pr 
                           WHERE pr.id = ?
                       ");
                       $stmt->execute([$purchase['product_id']]);
                       $product_details = $stmt->fetch(PDO::FETCH_ASSOC);
                       
                       if ($product_details && $product_details['drive_link']) {
                           // If Google Drive link exists, convert to direct download format
                           $download_link = convert_google_drive_to_download($product_details['drive_link']);
                           $download_type = 'drive';
                       } elseif ($product_details && $product_details['doc_file']) {
                           // If local file exists, use the secure download system
                           if (isset($purchase['purchase_type']) && $purchase['purchase_type'] === 'guest') {
                               $download_link = getGuestDownloadLink($_SESSION['user_email'], $purchase['product_id']);
                           } else {
                               $download_link = getProductDownloadLink($_SESSION['user_id'], $purchase['product_id']);
                           }
                           $download_type = 'local';
                       }
                       
                       if ($download_link && $purchase['status'] === 'paid'): ?>
                         <!-- Unified Download Button - Direct download for all file types -->
                         <a href="<?php echo htmlspecialchars($download_link); ?>" 
                            class="flex-1 btn-primary text-center">
                           <i class="fas fa-download mr-1"></i>
                           Download
                         </a>
                       <?php elseif ($purchase['status'] === 'pending'): ?>
                         <span class="flex-1 btn-secondary text-center cursor-not-allowed opacity-50">
                           <i class="fas fa-clock mr-1"></i>
                           Payment Pending
                         </span>
                       <?php elseif ($purchase['status'] === 'failed'): ?>
                         <span class="flex-1 btn-secondary text-center cursor-not-allowed opacity-50">
                           <i class="fas fa-exclamation-triangle mr-1"></i>
                           Payment Failed
                         </span>
                       <?php endif; ?>
                       
                       <a href="../product.php?id=<?php echo $purchase['product_id']; ?>" 
                          class="flex-1 btn-secondary text-center">
                         <i class="fas fa-info-circle mr-1"></i>
                         View Details
                       </a>
                     </div>
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
