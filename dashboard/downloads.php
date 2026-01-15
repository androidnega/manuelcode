<?php
// If accessed directly (not via router), redirect to router
if (!defined('ROUTER_INCLUDED') && !isset($_SERVER['HTTP_X_ROUTER'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
    header('Location: ' . $protocol . '://' . $host . '/dashboard/downloads');
    exit;
}

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

// Get user's downloadable purchases (including guest purchases)
$user_email = $_SESSION['user_email'] ?? null;
$downloads = getAllPurchasedProducts($user_id, $user_email);

// FIXED: Filter to ensure only paid purchases are shown
$downloads = array_filter($downloads, function($download) {
    return $download['status'] === 'paid';
});

// FIXED: Remove duplicates by product_id (keep only the most recent)
$unique_downloads = [];
$seen_products = [];

foreach ($downloads as $download) {
    if (!in_array($download['product_id'], $seen_products)) {
        $unique_downloads[] = $download;
        $seen_products[] = $download['product_id'];
    }
}

$downloads = $unique_downloads;
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Downloads - ManuelCode</title>
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
        overflow-x: hidden;
        width: 100%;
        max-width: 100%;
        border-radius: 8px;
      }
      .table-responsive {
        width: 100%;
        table-layout: auto;
      }
      
      body, html {
        overflow-x: hidden;
        max-width: 100vw;
      }
      
      .main-content, main {
        overflow-x: hidden;
        max-width: 100%;
      }
      
      @media (max-width: 640px) {
        .table-responsive th,
        .table-responsive td {
          padding: 0.5rem 0.25rem;
          font-size: 0.75rem;
        }
        .whitespace-nowrap {
          white-space: normal;
        }
        .main-content {
          padding: 0.75rem;
        }
        .dashboard-card {
          margin: 0;
          border-radius: 0;
        }
        .dashboard-card > div:first-child {
          padding: 0.75rem;
        }
      }
      .download-table th,
      .download-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
      }
      .download-actions {
        flex-direction: column;
        gap: 0.5rem;
      }
      .download-actions .btn-primary,
      .download-actions .btn-secondary {
        width: 100%;
        text-align: center;
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
          <a href="/dashboard" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Overview</span>
          </a>
          <a href="my-purchases" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
            <span class="flex-1">My Purchases</span>
          </a>
          <a href="downloads" class="flex items-center py-3 px-4 bg-blue-50 text-blue-700 rounded-lg mb-2 transition-colors w-full">
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
    <div class="flex-1 lg:ml-0 min-h-screen w-0 min-w-0">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Downloads</h1>
            <p class="text-gray-600 mt-1">Access your purchased digital products</p>
          </div>
          <div class="flex items-center space-x-4">
            <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-home mr-2"></i>Home
            </a>
            <a href="/store" class="text-gray-600 hover:text-blue-600 transition-colors">
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
          <h1 class="mobile-title font-semibold text-gray-800">Downloads</h1>
          <div class="w-8"></div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-3 sm:p-4 lg:p-6 w-full max-w-full overflow-x-hidden">
        <!-- Downloads Section -->
        <div class="dashboard-card">
          <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h2 class="text-base sm:text-lg font-semibold text-gray-800">Your Downloads</h2>
          </div>
          <div class="p-3 sm:p-4 lg:p-6">
            <?php if (empty($downloads)): ?>
              <div class="text-center py-6 sm:py-8">
                <i class="fas fa-download text-3xl sm:text-4xl text-gray-300 mb-3 sm:mb-4"></i>
                <p class="text-sm sm:text-base text-gray-600 mb-2">No downloads available</p>
                <p class="text-xs sm:text-sm text-gray-500 mb-3 sm:mb-4">Purchase products to access downloads.</p>
                <a href="/store" class="btn-primary inline-flex items-center text-xs sm:text-sm px-3 sm:px-4 py-2">
                  <i class="fas fa-store mr-2"></i>
                  Browse Store
                </a>
              </div>
            <?php else: ?>
              <!-- Mobile Card View -->
              <div class="block sm:hidden space-y-3 w-full max-w-full">
                <?php foreach ($downloads as $download): ?>
                  <?php 
                  $download_link = null;
                  $stmt = $pdo->prepare("SELECT pr.drive_link, pr.doc_file, pr.title FROM products pr WHERE pr.id = ?");
                  $stmt->execute([$download['product_id']]);
                  $product_details = $stmt->fetch(PDO::FETCH_ASSOC);
                  
                  if ($product_details && $product_details['drive_link']) {
                      $download_link = convert_google_drive_to_download($product_details['drive_link']);
                  } elseif ($product_details && $product_details['doc_file']) {
                      if (isset($download['purchase_type']) && $download['purchase_type'] === 'guest') {
                          $download_link = getGuestDownloadLink($_SESSION['user_email'], $download['product_id']);
                      } else {
                          $download_link = getProductDownloadLink($_SESSION['user_id'], $download['product_id']);
                      }
                  }
                  ?>
                  <div class="bg-white border border-gray-200 rounded-lg p-3 w-full max-w-full overflow-hidden">
                    <div class="flex items-start gap-3 mb-3 w-full">
                      <?php if ($download['preview_image']): ?>
                        <img src="../assets/images/products/<?php echo htmlspecialchars($download['preview_image']); ?>" alt="<?php echo htmlspecialchars($download['product_title']); ?>" class="w-12 h-12 rounded object-cover flex-shrink-0">
                      <?php else: ?>
                        <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-file text-gray-400"></i>
                        </div>
                      <?php endif; ?>
                      <div class="flex-1 min-w-0 overflow-hidden">
                        <h3 class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($download['product_title']); ?></h3>
                        <p class="text-xs text-gray-500 truncate">Digital Product</p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($download['created_at'])); ?></p>
                        <p class="text-sm font-semibold text-green-600 mt-1">GHS <?php echo number_format($download['price'], 2); ?></p>
                      </div>
                    </div>
                    <div class="flex flex-col space-y-2 w-full">
                      <?php if ($download_link): ?>
                        <a href="<?php echo htmlspecialchars($download_link); ?>" class="btn-primary text-xs py-2 text-center w-full">
                          <i class="fas fa-download mr-1"></i>Download
                        </a>
                      <?php else: ?>
                        <span class="text-xs text-gray-400 text-center py-2">File not available</span>
                      <?php endif; ?>
                      <a href="/product?id=<?php echo $download['product_id']; ?>" class="btn-secondary text-xs py-2 text-center w-full">
                        <i class="fas fa-eye mr-1"></i>View Details
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              
              <!-- Desktop Table View -->
              <div class="hidden sm:block table-container w-full">
                <table class="table-responsive w-full download-table min-w-0">
                  <thead>
                    <tr>
                      <th class="px-3 sm:px-4 lg:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                      <th class="px-3 sm:px-4 lg:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                      <th class="px-3 sm:px-4 lg:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Purchase Date</th>
                      <th class="px-3 sm:px-4 lg:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <?php foreach ($downloads as $download): ?>
                      <tr class="hover:bg-gray-50">
                        <td class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4">
                          <div class="flex items-center">
                            <?php if ($download['preview_image']): ?>
                              <img src="../assets/images/products/<?php echo htmlspecialchars($download['preview_image']); ?>" alt="<?php echo htmlspecialchars($download['product_title']); ?>" class="w-8 h-8 sm:w-10 sm:h-10 rounded object-cover mr-2 sm:mr-3 flex-shrink-0">
                            <?php else: ?>
                              <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gray-200 rounded flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                <i class="fas fa-file text-gray-400 text-xs"></i>
                              </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                              <div class="text-xs sm:text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($download['product_title']); ?></div>
                              <div class="text-xs text-gray-500 hidden sm:block">Digital Product</div>
                            </div>
                          </div>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 text-xs sm:text-sm font-semibold text-green-600 whitespace-nowrap">
                          GHS <?php echo number_format($download['price'], 2); ?>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-500 whitespace-nowrap hidden md:table-cell">
                          <?php echo date('M j, Y', strtotime($download['created_at'])); ?>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium">
                          <div class="download-actions flex flex-col sm:flex-row space-y-1 sm:space-y-0 sm:space-x-2">
                            <?php 
                            $download_link = null;
                            $stmt = $pdo->prepare("SELECT pr.drive_link, pr.doc_file, pr.title FROM products pr WHERE pr.id = ?");
                            $stmt->execute([$download['product_id']]);
                            $product_details = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($product_details && $product_details['drive_link']) {
                                $download_link = convert_google_drive_to_download($product_details['drive_link']);
                            } elseif ($product_details && $product_details['doc_file']) {
                                if (isset($download['purchase_type']) && $download['purchase_type'] === 'guest') {
                                    $download_link = getGuestDownloadLink($_SESSION['user_email'], $download['product_id']);
                                } else {
                                    $download_link = getProductDownloadLink($_SESSION['user_id'], $download['product_id']);
                                }
                            }
                            
                            if ($download_link): ?>
                              <a href="<?php echo htmlspecialchars($download_link); ?>" 
                                 class="btn-primary text-xs sm:text-sm px-2 sm:px-3 py-1 sm:py-2 whitespace-nowrap">
                                <i class="fas fa-download mr-1"></i>Download
                              </a>
                            <?php else: ?>
                              <span class="text-gray-400 text-xs sm:text-sm">File not available</span>
                            <?php endif; ?>
                            <a href="/product?id=<?php echo $download['product_id']; ?>" class="btn-secondary text-xs sm:text-sm px-2 sm:px-3 py-1 sm:py-2 whitespace-nowrap">
                              <i class="fas fa-eye mr-1"></i>View
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
