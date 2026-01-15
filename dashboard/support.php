<?php
// If accessed directly (not via router), redirect to router
if (!defined('ROUTER_INCLUDED') && !isset($_SERVER['HTTP_X_ROUTER'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
    header('Location: ' . $protocol . '://' . $host . '/dashboard/support');
    exit;
}

session_start();
include '../includes/auth_only.php';
include '../includes/db.php';



$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
                         case 'create_ticket':
                 $subject = trim($_POST['subject']);
                 $message = trim($_POST['message']);
                 $priority = $_POST['priority'];
                 $department = $_POST['department'];
                 
                 if (empty($subject) || empty($message)) {
                     $error_message = 'Subject and message are required.';
                 } else {
                     try {
                         $stmt = $pdo->prepare("
                             INSERT INTO support_tickets (user_id, subject, message, priority, category, status, created_at)
                             VALUES (?, ?, ?, ?, ?, 'open', NOW())
                         ");
                         $stmt->execute([$user_id, $subject, $message, $priority, $department]);
                         $success_message = 'Support ticket created successfully!';
                     } catch (Exception $e) {
                         $error_message = 'Error creating ticket: ' . $e->getMessage();
                     }
                 }
                 break;
                
            case 'close_ticket':
                $ticket_id = (int)$_POST['ticket_id'];
                try {
                    $stmt = $pdo->prepare("
                        UPDATE support_tickets 
                        SET status = 'closed', closed_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$ticket_id, $user_id]);
                    $success_message = 'Ticket closed successfully!';
                } catch (Exception $e) {
                    $error_message = 'Error closing ticket: ' . $e->getMessage();
                }
                break;
                
            case 'add_reply':
                $ticket_id = (int)$_POST['ticket_id'];
                $reply_message = trim($_POST['reply_message']);
                
                if (empty($reply_message)) {
                    $error_message = 'Reply message is required.';
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO support_replies (ticket_id, user_id, message, created_at)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$ticket_id, $user_id, $reply_message]);
                        
                        // Update ticket status to 'replied'
                        $stmt = $pdo->prepare("
                            UPDATE support_tickets 
                            SET status = 'replied', updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$ticket_id]);
                        
                        $success_message = 'Reply sent successfully!';
                    } catch (Exception $e) {
                        $error_message = 'Error sending reply: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get user's tickets
$stmt = $pdo->prepare("
    SELECT st.*, 
           COUNT(sr.id) as reply_count,
           MAX(sr.created_at) as last_reply
    FROM support_tickets st
    LEFT JOIN support_replies sr ON st.id = sr.ticket_id
    WHERE st.user_id = ?
    GROUP BY st.id
    ORDER BY st.created_at DESC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ticket statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM support_tickets WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as open FROM support_tickets WHERE user_id = ? AND status = 'open'");
$stmt->execute([$user_id]);
$open_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['open'];

$stmt = $pdo->prepare("SELECT COUNT(*) as closed FROM support_tickets WHERE user_id = ? AND status = 'closed'");
$stmt->execute([$user_id]);
$closed_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['closed'];

$stmt = $pdo->prepare("SELECT COUNT(*) as replied FROM support_tickets WHERE user_id = ? AND status = 'replied'");
$stmt->execute([$user_id]);
$replied_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['replied'];
?>

<!DOCTYPE html lang="en">
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support - ManuelCode</title>
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
    
    .form-input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 0.875rem;
      transition: border-color 0.2s ease;
    }
    
    .form-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
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
    }
  </style>
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
          <a href="" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Overview</span>
          </a>
          <a href="my-purchases" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
            <span class="flex-1">My Purchases</span>
          </a>
          <a href="downloads" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-download mr-3 w-5 text-center"></i>
            <span class="flex-1">Downloads</span>
          </a>
          <a href="receipts" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-receipt mr-3 w-5 text-center"></i>
            <span class="flex-1">Receipts</span>
          </a>
          <a href="support" class="flex items-center py-3 px-4 bg-blue-50 text-blue-700 rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-headset mr-3 w-5 text-center"></i>
            <span class="flex-1">Support</span>
          </a>
          <a href="settings" class="flex items-center py-3 px-4 <?php echo $dark_mode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-50'; ?> rounded-lg mb-2 transition-colors w-full">
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
        <a href="/auth/logout.php" class="flex items-center py-2 px-4 <?php echo $dark_mode ? 'text-red-400 hover:bg-red-900' : 'text-red-600 hover:bg-red-50'; ?> rounded-lg transition-colors">
          <i class="fas fa-sign-out-alt mr-3"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-0 min-h-screen">
      <!-- Desktop Header -->
      <header class="hidden lg:block <?php echo $dark_mode ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'; ?> border-b px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Support</h1>
            <p class="<?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?> mt-1">Get help with your purchases and account</p>
          </div>
          <div class="flex items-center space-x-4">
            <button onclick="toggleDarkMode()" class="<?php echo $dark_mode ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-600 hover:text-gray-800'; ?> transition-colors p-2 rounded-lg <?php echo $dark_mode ? 'hover:bg-gray-800' : 'hover:bg-gray-100'; ?>">
              <i class="fas <?php echo $dark_mode ? 'fa-sun' : 'fa-moon'; ?> text-lg"></i>
            </button>
            <a href="" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'; ?> transition-colors">
              <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <a href="/" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'; ?> transition-colors">
              <i class="fas fa-home mr-2"></i>Home
            </a>
          </div>
        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden <?php echo $dark_mode ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'; ?> border-b mobile-header">
        <div class="flex items-center justify-between">
          <button onclick="toggleSidebar()" class="<?php echo $dark_mode ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900'; ?> p-2">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="mobile-title font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Support</h1>
          <button onclick="toggleDarkMode()" class="<?php echo $dark_mode ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-600 hover:text-gray-800'; ?> p-2">
            <i class="fas <?php echo $dark_mode ? 'fa-sun' : 'fa-moon'; ?> text-lg"></i>
          </button>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-6 <?php echo $dark_mode ? 'text-white' : ''; ?>">
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
          <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex">
              <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
              <p class="text-green-800"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
          <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
              <i class="fas fa-exclamation-circle text-red-400 mt-1 mr-3"></i>
              <p class="text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Support Options -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <!-- Contact Information -->
          <div class="dashboard-card">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-800">Contact Information</h2>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-envelope text-blue-600"></i>
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900">Email Support</h3>
                    <p class="text-sm text-gray-600">support@manuelcode.info</p>
                  </div>
                </div>
                
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-phone text-green-600"></i>
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900">Phone Support</h3>
                    <p class="text-sm text-gray-600">+233257940791</p>
                  </div>
                </div>
                
                <div class="flex items-center">
                  <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-clock text-purple-600"></i>
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900">Support Hours</h3>
                    <p class="text-sm text-gray-600">Monday - Friday: 8:00 AM - 6:00 PM GMT</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Help -->
          <div class="dashboard-card">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-800">Quick Help</h2>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <a href="/help/download-guide" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <i class="fas fa-download text-blue-600 mr-3"></i>
                  <span class="text-gray-700">How to download purchased products?</span>
                </a>
                
                <a href="/help/refund-guide" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <i class="fas fa-undo-alt text-green-600 mr-3"></i>
                  <span class="text-gray-700">How to request a refund?</span>
                </a>
                
                <a href="/help/payment-issues" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <i class="fas fa-credit-card text-purple-600 mr-3"></i>
                  <span class="text-gray-700">Payment issues and solutions</span>
                </a>
                
                <a href="/help/account-security" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <i class="fas fa-shield-alt text-orange-600 mr-3"></i>
                  <span class="text-gray-700">Account security and privacy</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Support Ticket -->
        <div class="dashboard-card">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Submit Support Ticket</h2>
          </div>
          <div class="p-6">
                         <form method="POST" class="space-y-6">
               <input type="hidden" name="action" value="create_ticket">
              <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                <input type="text" id="subject" name="subject" required class="form-input" placeholder="Brief description of your issue">
              </div>
              
                             <div>
                 <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                 <select id="priority" name="priority" required class="form-input">
                   <option value="">Select priority level</option>
                   <option value="low">Low - General inquiry</option>
                   <option value="medium">Medium - Minor issue</option>
                   <option value="high">High - Urgent issue</option>
                   <option value="critical">Critical - System down</option>
                 </select>
               </div>
               
               <div>
                 <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                 <select id="department" name="department" required class="form-input">
                   <option value="">Select department</option>
                   <option value="General Support">General Support</option>
                   <option value="Technical Support">Technical Support</option>
                   <option value="Billing Support">Billing Support</option>
                   <option value="Sales Support">Sales Support</option>
                   <option value="Others">Others</option>
                 </select>
               </div>
               
               <div>
                 <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                 <textarea id="message" name="message" rows="6" required class="form-input" placeholder="Please provide detailed information about your issue..."></textarea>
               </div>
              
              <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">We typically respond within 24 hours</p>
                                 <button type="submit" class="btn-primary">
                  <i class="fas fa-paper-plane mr-2"></i>
                  Submit Ticket
                </button>
              </div>
            </form>
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

    function toggleDarkMode() {
      fetch('toggle_dark_mode.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        }
      })
      .catch(error => console.error('Error toggling dark mode:', error));
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
