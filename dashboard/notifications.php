<?php
session_start();
include '../includes/auth_only.php';
include '../includes/db.php';
include '../includes/notification_helper.php';



$notificationHelper = new NotificationHelper($pdo);
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';

// Handle mark as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $notificationHelper->markNotificationAsRead($notification_id, $user_id);
    header('Location: notifications.php?success=marked_read');
    exit;
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("
        UPDATE user_notifications 
        SET is_read = TRUE, read_at = NOW()
        WHERE user_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$user_id]);
    header('Location: notifications.php?success=all_marked_read');
    exit;
}

// Get notifications
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$notifications = $notificationHelper->getUserNotifications($user_id, $limit, $offset);
$unread_count = $notificationHelper->getUnreadCount($user_id);

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_notifications / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ManuelCode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
</head>
<body class="bg-gray-50">
  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

  <!-- Layout Container -->
  <div class="flex">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 bg-white border-gray-200 border-r transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
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
          <a href="my-purchases" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
            <span class="flex-1">My Purchases</span>
          </a>
          <a href="downloads" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-download mr-3 w-5 text-center"></i>
            <span class="flex-1">Downloads</span>
          </a>
          <a href="receipts" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-receipt mr-3 w-5 text-center"></i>
            <span class="flex-1">Receipts</span>
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
      <header class="hidden lg:block bg-white border-gray-200 border-b px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
            <p class="text-gray-600 mt-1">Stay updated with your purchase notifications</p>
          </div>
          <div class="flex items-center space-x-4">
            <a href="" class="text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">
              <i class="fas fa-home mr-2"></i>Home
            </a>
          </div>
        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden bg-white border-gray-200 border-b mobile-header">
        <div class="flex items-center justify-between">
          <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="mobile-title font-semibold text-gray-800">Notifications</h1>
          <div class="w-8"></div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                    <p class="text-gray-600 mt-2">
                        <?php if ($unread_count > 0): ?>
                            You have <span class="font-semibold text-blue-600"><?php echo $unread_count; ?></span> unread notification<?php echo $unread_count !== 1 ? 's' : ''; ?>
                        <?php else: ?>
                            All caught up! No unread notifications.
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($unread_count > 0): ?>
                    <form method="POST" class="inline">
                        <button type="submit" name="mark_all_read" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-check-double mr-2"></i>
                            Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-check-circle mt-1 mr-3"></i>
                        <p>
                            <?php if ($_GET['success'] === 'marked_read'): ?>
                                Notification marked as read.
                            <?php elseif ($_GET['success'] === 'all_marked_read'): ?>
                                All notifications marked as read.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notifications List -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-bell text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications yet</h3>
                        <p class="text-gray-500">You'll see notifications here when there are updates to your purchases.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="p-6 <?php echo !$notification['is_read'] ? 'bg-blue-50' : ''; ?> hover:bg-gray-50 transition-colors">
                                <div class="flex items-start space-x-4">
                                    <!-- Notification Icon -->
                                    <div class="flex-shrink-0">
                                        <?php if (!$notification['is_read']): ?>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                        <?php endif; ?>
                                        
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center mt-2
                                                    <?php 
                                                    switch ($notification['notification_type']) {
                                                        case 'download_ready':
                                                            echo 'bg-green-100';
                                                            break;
                                                        case 'product_updated':
                                                            echo 'bg-blue-100';
                                                            break;
                                                        case 'product_improved':
                                                            echo 'bg-purple-100';
                                                            break;
                                                        default:
                                                            echo 'bg-gray-100';
                                                    }
                                                    ?>">
                                            <i class="fas 
                                                <?php 
                                                switch ($notification['notification_type']) {
                                                    case 'download_ready':
                                                        echo 'fa-download text-green-600';
                                                        break;
                                                    case 'product_updated':
                                                        echo 'fa-sync text-blue-600';
                                                        break;
                                                    case 'product_improved':
                                                        echo 'fa-star text-purple-600';
                                                        break;
                                                    default:
                                                        echo 'fa-bell text-gray-600';
                                                }
                                                ?>"></i>
                                        </div>
                                    </div>

                                    <!-- Notification Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h3>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500">
                                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                </span>
                                                
                                                <?php if (!$notification['is_read']): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                        <button type="submit" name="mark_read" 
                                                                class="text-xs text-blue-600 hover:text-blue-800">
                                                            Mark as read
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        
                                        <div class="mt-3 flex items-center space-x-4">
                                            <?php if ($notification['preview_image']): ?>
                                                <img src="../assets/images/products/<?php echo htmlspecialchars($notification['preview_image']); ?>" 
                                                     alt="Product preview" 
                                                     class="w-12 h-12 object-cover rounded border">
                                            <?php endif; ?>
                                            
                                            <div class="flex space-x-2">
                                                <a href="/product?id=<?php echo $notification['product_id']; ?>" 
                                                   class="text-sm text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-eye mr-1"></i>View Product
                                                </a>
                                                
                                                <?php if ($notification['notification_type'] === 'download_ready'): ?>
                                                    <a href="/download?product_id=<?php echo $notification['product_id']; ?>" 
                                                       class="text-sm text-green-600 hover:text-green-800">
                                                        <i class="fas fa-download mr-1"></i>Download Now
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_notifications); ?> 
                                    of <?php echo $total_notifications; ?> notifications
                                </div>
                                
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" 
                                           class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>" 
                                           class="px-3 py-2 text-sm rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" 
                                           class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
      </div>
    </div>

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
      

    </style>

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

    <?php include '../includes/footer.php'; ?>

    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            fetch('get_notifications_count.php')
                .then(response => response.json())
                .then(data => {
                    const currentCount = <?php echo $unread_count; ?>;
                    if (data.unread_count > currentCount) {
                        // Show notification badge update
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.unread_count;
                            badge.classList.remove('hidden');
                        }
                    }
                })
                .catch(error => console.log('Notification check failed:', error));
        }, 30000);
    </script>
</body>
</html>
