<?php
// Include admin authentication check or super admin access
if (isset($_SESSION['superadmin_access']) && $_SESSION['superadmin_access']) {
    // Super admin has access
    include_once '../includes/db.php';
} else {
    // Regular admin authentication
    include 'auth/check_auth.php';
    include_once '../includes/db.php';
}

// Include the comprehensive user cleanup helper
include_once '../includes/user_cleanup_helper.php';

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle user actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Validation
        if (empty($name) || empty($email) || empty($phone)) {
            $error_message = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = 'Email address already exists.';
                } else {
                    // Check if phone already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->execute([$phone]);
                    if ($stmt->fetch()) {
                        $error_message = 'Phone number already exists.';
                    } else {
                        // Generate unique user ID using the UserIDGenerator class
                        require_once '../includes/user_id_generator.php';
                        $generator = new UserIDGenerator($pdo);
                        $user_id = $generator->generateUserID($name);
                        
                        // Insert new user without password (OTP-based authentication)
                        $stmt = $pdo->prepare("
                            INSERT INTO users (name, email, phone, user_id, status, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
                        ");
                        
                        if ($stmt->execute([$name, $email, $phone, $user_id])) {
                            $success_message = 'User created successfully! User ID: ' . $user_id . '. User can login with OTP on their phone number.';
                        } else {
                            $error_message = 'Failed to create user.';
                        }
                    }
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // FIXED: Use comprehensive user cleanup instead of manual deletion
        try {
            // Get user data summary before deletion
            $user_summary = getUserDataSummary($user_id);
            
            // Perform comprehensive cleanup
            $cleanup_result = completeUserCleanup($user_id);
            
            if ($cleanup_result['success']) {
                $success_message = 'User and all related data completely removed from system!';
                if (isset($cleanup_result['cleanup_summary'])) {
                    $total_cleaned = $cleanup_result['total_records_cleaned'];
                    $success_message .= " Total records cleaned: $total_cleaned";
                }
            } else {
                $error_message = 'User deletion failed: ' . $cleanup_result['message'];
            }
            
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
        
    } elseif (isset($_POST['toggle_status'])) {
        $user_id = (int)$_POST['user_id'];
        $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $user_id])) {
                $success_message = 'User status updated successfully!';
            } else {
                $error_message = 'Failed to update user status.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    } elseif (isset($_POST['reset_password'])) {
        $user_id = (int)$_POST['user_id'];
        $new_password = bin2hex(random_bytes(8)); // Generate random password
        
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success_message = 'Password reset successfully! New password: ' . $new_password;
            } else {
                $error_message = 'Failed to reset password.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Validate and sanitize sort parameters
$allowed_sort_fields = ['id', 'name', 'email', 'phone', 'status', 'created_at', 'last_login'];
$sort_by = in_array($_GET['sort'] ?? '', $allowed_sort_fields) ? $_GET['sort'] : 'created_at';

$allowed_sort_orders = ['ASC', 'DESC'];
$sort_order = in_array(strtoupper($_GET['order'] ?? ''), $allowed_sort_orders) ? strtoupper($_GET['order']) : 'DESC';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total users count with filters
$count_query = "SELECT COUNT(*) FROM users u $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

// Get total guest accounts count
$guest_count_query = "SELECT COUNT(*) FROM guest_orders WHERE status = 'paid'";
$stmt = $pdo->prepare($guest_count_query);
$stmt->execute();
$total_guests = $stmt->fetchColumn();

$total_accounts = $total_users + $total_guests;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)20;
$offset = (int)(($page - 1) * $limit);
$total_pages = ceil($total_users / $limit);

// Get users with filters and sorting
$order_clause = '';
switch($sort_by) {
    case 'id':
        $order_clause = 'ORDER BY u.id ' . $sort_order;
        break;
    case 'name':
        $order_clause = 'ORDER BY u.name ' . $sort_order;
        break;
    case 'email':
        $order_clause = 'ORDER BY u.email ' . $sort_order;
        break;
    case 'phone':
        $order_clause = 'ORDER BY u.phone ' . $sort_order;
        break;
    case 'status':
        $order_clause = 'ORDER BY u.status ' . $sort_order;
        break;
    case 'created_at':
        $order_clause = 'ORDER BY u.created_at ' . $sort_order;
        break;
    case 'last_login':
        $order_clause = 'ORDER BY u.last_login ' . $sort_order;
        break;
    default:
        $order_clause = 'ORDER BY u.created_at DESC';
}

// Get users with filters and sorting
$user_query = "
    SELECT u.*, 
           COUNT(CASE WHEN p.status = 'paid' THEN p.id END) as total_purchases,
           SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END) as total_spent,
           MAX(CASE WHEN p.status = 'paid' THEN p.created_at END) as last_purchase,
           u.last_login,
           'user' as account_type
    FROM users u 
    LEFT JOIN purchases p ON u.id = p.user_id AND p.status = 'paid'
    LEFT JOIN products pr ON p.product_id = pr.id
    $where_clause
    GROUP BY u.id 
    $order_clause
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
";

// Get guest accounts
$guest_query = "
    SELECT 
        go.id,
        go.name,
        go.email,
        go.phone,
        'active' as status,
        go.created_at,
        go.updated_at as last_login,
        go.total_amount as total_spent,
        1 as total_purchases,
        go.created_at as last_purchase,
        'guest' as account_type
    FROM guest_orders go
    WHERE go.status = 'paid'
    ORDER BY go.created_at DESC
";

$stmt = $pdo->prepare($user_query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare($guest_query);
$stmt->execute();
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine users and guests
$all_accounts = array_merge($users, $guests);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Users Management - Admin</title>
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
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
      .search-filters {
        grid-template-columns: 1fr;
        gap: 1rem;
        width: 100%;
      }
      .search-filters .md\\:col-span-4 {
        grid-column: 1 / -1;
      }
      .search-filters .flex {
        flex-direction: column;
        gap: 0.5rem;
      }
      .search-filters .flex > * {
        width: 100%;
      }
      .search-filters .flex-col {
        gap: 0.5rem;
      }
      .search-filters .flex-col > * {
        width: 100%;
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
      /* User table improvements for mobile */
      .table-responsive .flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
      }
      /* Ensure user icon stays centered in its container */
      .table-responsive .flex-shrink-0 .rounded-full {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .table-responsive .flex-shrink-0 {
        margin-bottom: 0.5rem;
      }
      .table-responsive .ml-3 {
        margin-left: 0;
      }
      .table-responsive .space-x-2 {
        flex-direction: column;
        gap: 0.5rem;
      }
      .table-responsive .space-x-2 > * {
        width: 100%;
        text-align: center;
        padding: 0.5rem;
        border-radius: 4px;
        background: #f8f9fa;
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
      .search-filters .md\\:col-span-4 {
        grid-column: 1 / -1;
      }
      .search-filters .flex {
        flex-direction: column;
        gap: 0.5rem;
      }
      .search-filters .flex > * {
        width: 100%;
      }
      .search-filters .flex-col {
        gap: 0.5rem;
      }
      .search-filters .flex-col > * {
        width: 100%;
      }
      /* Ensure user icon stays centered in its container */
      .table-responsive .flex-shrink-0 .rounded-full {
        display: flex;
        align-items: center;
        justify-content: center;
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
      .search-filters .flex {
        gap: 0.25rem;
      }
      /* Ensure user icon stays centered in its container */
      .table-responsive .flex-shrink-0 .rounded-full {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .stats-grid > div {
        padding: 1rem;
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
          <a href="../dashboard/" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
            <span class="flex-1">Dashboard</span>
          </a>
          <a href="../dashboard/products" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-box mr-3 w-5 text-center"></i>
            <span class="flex-1">Products</span>
          </a>
          <a href="../dashboard/projects" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
            <span class="flex-1">Projects</span>
          </a>
          <a href="../dashboard/orders" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
            <span class="flex-1">Orders</span>
          </a>
          <a href="../dashboard/users" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span class="flex-1">Users</span>
          </a>
          <a href="../dashboard/reports" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
            <span class="flex-1">Reports</span>
          </a>
          <a href="../dashboard/purchase-management" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
            <span class="flex-1">Purchase Management</span>
          </a>
          <a href="../dashboard/refunds-admin" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-undo mr-3 w-5 text-center"></i>
            <span class="flex-1">Refunds</span>
          </a>
          <a href="../dashboard/support-management" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-headset mr-3 w-5 text-center"></i>
            <span class="flex-1">Support Management</span>
          </a>
          <a href="../dashboard/generate-receipts" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-receipt mr-3 w-5 text-center"></i>
            <span class="flex-1">Generate Receipts</span>
          </a>
          <a href="../dashboard/change-password" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-key mr-3 w-5 text-center"></i>
            <span class="flex-1">Change Password</span>
          </a>
          <?php if (($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
          <a href="../dashboard/superadmin" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
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
            <h1 class="text-2xl font-bold text-[#2D3E50]">Users Management</h1>
            <p class="text-gray-600 mt-1">Manage user accounts and view user activity.</p>
          </div>

        </div>
      </header>

             <!-- Mobile Header -->
       <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 mobile-header">
         <div class="flex items-center justify-between">
           <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
             <i class="fas fa-bars text-xl"></i>
           </button>
           <h1 class="mobile-title font-semibold text-gray-800">Users</h1>
           <div class="w-8"></div>
         </div>
       </header>

             <!-- Main Content Area -->
       <main class="p-4 lg:p-6 w-full">
        <!-- Messages -->
        <?php if ($success_message): ?>
          <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded">
            <div class="flex">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <div class="flex">
              <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
              <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

                 <!-- Stats -->
         <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 w-full">
          <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <div class="flex items-center">
              <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-lg"></i>
              </div>
              <div class="ml-3">
                <h2 class="text-gray-600 text-sm">Total Users</h2>
                <p class="text-xl font-bold text-[#4CAF50]"><?php echo $total_accounts; ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <div class="flex items-center">
              <div class="p-2 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-user-check text-lg"></i>
              </div>
              <div class="ml-3">
                <h2 class="text-gray-600 text-sm">Active Users</h2>
                <p class="text-xl font-bold text-[#4CAF50]">
                  <?php 
                  $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
                  echo $stmt->fetchColumn();
                  ?>
                </p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <div class="flex items-center">
              <div class="p-2 rounded-full bg-orange-100 text-orange-600">
                <i class="fas fa-user-clock text-lg"></i>
              </div>
              <div class="ml-3">
                <h2 class="text-gray-600 text-sm">New This Month</h2>
                <p class="text-xl font-bold text-[#4CAF50]">
                  <?php 
                  $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
                  echo $stmt->fetchColumn();
                  ?>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Create New User -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
          <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50] mb-2">Create New User</h2>
            <p class="text-sm text-gray-600">Add a new user to the system</p>
          </div>
          <div class="p-4 lg:p-6">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="name" name="name" required
                       placeholder="Enter full name" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
              </div>
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required
                       placeholder="Enter email address" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
              </div>
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" id="phone" name="phone" required
                       placeholder="Enter phone number" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
              </div>
              <div class="md:col-span-3">
                <button type="submit" name="create_user" 
                        class="bg-[#4CAF50] hover:bg-[#45a049] text-white font-medium py-2 px-6 rounded-md transition-colors duration-200">
                  <i class="fas fa-user-plus mr-2"></i>Create User
                </button>
              </div>
            </form>
          </div>
        </div>

                 <!-- Live Search & Filters -->
         <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6 mobile-card">
           <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
             <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50] mb-2">Live Search & Filters</h2>
             <p class="text-sm text-gray-600">Search and filter users in real-time</p>
           </div>
           <div class="p-4 lg:p-6">
             <div class="search-filters grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" 
                       placeholder="Name, email, or phone" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="filter-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                <select name="account_type" class="filter-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
                  <option value="">All Types</option>
                  <option value="user">Registered Users</option>
                  <option value="guest">Guest Users</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <select name="date" class="filter-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895]">
                  <option value="">All Time</option>
                  <option value="today">Today</option>
                  <option value="week">Last 7 Days</option>
                  <option value="month">Last 30 Days</option>
                  <option value="year">Last Year</option>
                </select>
              </div>
              <div class="md:col-span-4 flex flex-col sm:flex-row gap-2">
                <button type="button" class="clear-filters mobile-button px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                  <i class="fas fa-times mr-2"></i>Clear All Filters
                </button>
                <div class="results-count text-sm text-gray-600 flex items-center">
                  <i class="fas fa-info-circle mr-2"></i>
                  <span>Ready to search</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 mobile-card">
          <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">User Accounts (<?php echo $total_accounts; ?>)</h2>
          </div>
          
          <div class="p-4 lg:p-6">
            <!-- No Results Message (Hidden by default) -->
            <div class="no-results text-center py-8" style="display: none;">
              <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-600 text-lg mb-2">No users found</p>
              <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
            </div>
            
            <?php if ($users): ?>
                       <div class="table-container w-full">
           <table class="table-responsive w-full">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                      <th class="px-3 lg:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <?php foreach ($all_accounts as $user): ?>
                                             <tr class="hover:bg-gray-50"
                                                 data-status="<?php echo $user['status'] ?? 'active'; ?>"
                                                 data-account-type="<?php echo $user['account_type']; ?>"
                                                 data-date="<?php echo date('Y-m-d', strtotime($user['created_at'])); ?>">
                         <td class="px-3 lg:px-4 py-3" data-label="User">
                           <div class="flex items-center">
                             <div class="flex-shrink-0 h-10 w-10">
                               <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center shadow-sm border border-indigo-200">
                                 <i class="fas fa-user text-indigo-600 text-sm"></i>
                               </div>
                             </div>
                             <div class="ml-3 flex-1 min-w-0">
                               <div class="text-sm font-medium text-gray-900 truncate">
                                 <?php echo htmlspecialchars($user['name']); ?>
                                 <?php if ($user['account_type'] === 'guest'): ?>
                                   <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Guest</span>
                                 <?php else: ?>
                                   <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">User</span>
                                 <?php endif; ?>
                               </div>
                               <div class="text-sm text-gray-500">
                                 ID: <?php echo $user['account_type'] === 'guest' ? 'GUEST' . str_pad($user['id'], 6, '0', STR_PAD_LEFT) : ($user['user_id'] ?? 'USER' . str_pad($user['id'], 6, '0', STR_PAD_LEFT)); ?>
                               </div>
                             </div>
                           </div>
                         </td>
                         <td class="px-3 lg:px-4 py-3" data-label="Contact">
                           <div class="text-sm text-gray-900">
                             <?php echo htmlspecialchars($user['email']); ?>
                           </div>
                           <div class="text-sm text-gray-500">
                             <?php echo htmlspecialchars($user['phone']); ?>
                           </div>
                         </td>
                         <td class="px-3 lg:px-4 py-3" data-label="Activity">
                           <div class="text-sm text-gray-900">
                             <?php echo $user['total_purchases']; ?> purchases
                           </div>
                           <div class="text-sm text-gray-500">
                             GHS <?php echo number_format($user['total_spent'] ?? 0, 2); ?> spent
                           </div>
                           <?php if ($user['last_purchase']): ?>
                           <div class="text-xs text-gray-400">
                             Last: <?php echo date('M j, Y', strtotime($user['last_purchase'])); ?>
                           </div>
                           <?php endif; ?>
                         </td>
                         <td class="px-3 lg:px-4 py-3" data-label="Status">
                           <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                             <?php echo ($user['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                             <?php echo ucfirst($user['status'] ?? 'active'); ?>
                           </span>
                         </td>
                         <td class="px-3 lg:px-4 py-3 text-sm text-gray-500" data-label="Joined">
                           <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                         </td>
                         <td class="px-3 lg:px-4 py-3 text-sm text-gray-500" data-label="Last Login">
                           <?php 
                           if ($user['account_type'] === 'guest') {
                               echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never';
                           } else {
                               echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never';
                           }
                           ?>
                         </td>
                         <td class="px-3 lg:px-4 py-3 text-sm font-medium" data-label="Actions">
                          <div class="flex space-x-2">
                            <!-- View Details -->
                            <button onclick="viewUserDetails(<?php echo $user['id']; ?>, '<?php echo $user['account_type']; ?>')" 
                                    class="text-blue-600 hover:text-blue-900 text-xs">
                              <i class="fas fa-eye mr-1"></i>View
                            </button>
                            
                            <?php if ($user['account_type'] === 'user'): ?>
                            <!-- Toggle Status -->
                            <form method="POST" class="inline">
                              <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                              <input type="hidden" name="new_status" value="<?php echo ($user['status'] ?? 'active') === 'active' ? 'inactive' : 'active'; ?>">
                              <button type="submit" name="toggle_status" 
                                      class="text-orange-600 hover:text-orange-900 text-xs">
                                <i class="fas fa-toggle-on mr-1"></i><?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate'; ?>
                              </button>
                            </form>
                            
                            <!-- Reset Password -->
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reset this user\'s password?')">
                              <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                              <button type="submit" name="reset_password" 
                                      class="text-green-600 hover:text-green-900 text-xs">
                                <i class="fas fa-key mr-1"></i>Reset PW
                              </button>
                            </form>
                            
                            <!-- Delete User -->
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                              <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                              <button type="submit" name="delete_user" 
                                      class="text-red-600 hover:text-red-900 text-xs">
                                <i class="fas fa-trash mr-1"></i>Delete
                              </button>
                            </form>
                            <?php else: ?>
                            <!-- Guest Account Actions -->
                            <span class="text-gray-400 text-xs">
                              <i class="fas fa-info-circle mr-1"></i>Guest Account
                            </span>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex items-center justify-between">
                  <div class="text-sm text-gray-700">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_accounts); ?> of <?php echo $total_accounts; ?> accounts
                  </div>
                  <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                      <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Previous
                      </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                      <a href="?page=<?php echo $i; ?>" 
                         class="px-3 py-2 text-sm rounded-md <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                      </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                      <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Next
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="text-center py-8">
                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">No users found.</p>
                <p class="text-sm text-gray-500 mt-1">Users will appear here once they register.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- User Details Modal -->
  <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">User Details</h3>
          <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        <div id="userModalContent" class="space-y-4">
          <!-- Content will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <script src="/admin/assets/js/live-search.js"></script>
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

    function viewUserDetails(userId, accountType) {
      const modal = document.getElementById('userModal');
      const content = document.getElementById('userModalContent');
      
      // Show loading
      content.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="mt-2 text-gray-600">Loading account details...</p></div>';
      modal.classList.remove('hidden');
      
      // Fetch account details based on type
      const endpoint = accountType === 'guest' ? `/admin/get_guest_details.php?guest_id=${userId}` : `/admin/get_user_details.php?user_id=${userId}`;
      fetch(endpoint)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          // Check if response is JSON
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
              throw new Error('Expected JSON but got: ' + text.substring(0, 100));
            });
          }
          return response.json();
        })
        .then(data => {
          if (data && data.success) {
            const user = data.user;
            const purchases = data.purchases;
            
            content.innerHTML = `
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- User Information -->
                <div>
                  <h4 class="font-semibold text-gray-900 mb-3">User Information</h4>
                  <div class="space-y-2 text-sm">
                    <div><strong>Name:</strong> ${user.name}</div>
                    <div><strong>Email:</strong> ${user.email}</div>
                    <div><strong>Phone:</strong> ${user.phone}</div>
                    <div><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${user.status}</span></div>
                    <div><strong>Joined:</strong> ${new Date(user.created_at).toLocaleDateString()}</div>
                    <div><strong>Last Login:</strong> ${user.last_login ? new Date(user.last_login).toLocaleString('en-US', { 
                      year: 'numeric', 
                      month: 'long', 
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    }) : 'Never'}</div>
                  </div>
                </div>
                
                <!-- Purchase Summary -->
                <div>
                  <h4 class="font-semibold text-gray-900 mb-3">Purchase Summary</h4>
                  <div class="space-y-2 text-sm">
                    <div><strong>Total Purchases:</strong> ${purchases.length}</div>
                    <div><strong>Total Spent:</strong> GHS ${parseFloat(user.total_spent || 0).toFixed(2)}</div>
                    <div><strong>Last Purchase:</strong> ${user.last_purchase ? new Date(user.last_purchase).toLocaleDateString() : 'None'}</div>
                  </div>
                </div>
              </div>
              
              <!-- Purchase History -->
              <div class="mt-6">
                <h4 class="font-semibold text-gray-900 mb-3">Purchase History</h4>
                ${purchases.length > 0 ? `
                  <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-3 py-2 text-left">Product</th>
                          <th class="px-3 py-2 text-left">Amount</th>
                          <th class="px-3 py-2 text-left">Status</th>
                          <th class="px-3 py-2 text-left">Date</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200">
                        ${purchases.map(purchase => `
                          <tr>
                            <td class="px-3 py-2">${purchase.product_title}</td>
                            <td class="px-3 py-2">GHS ${parseFloat(purchase.amount).toFixed(2)}</td>
                            <td class="px-3 py-2"><span class="px-2 py-1 text-xs rounded-full ${purchase.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${purchase.status}</span></td>
                            <td class="px-3 py-2">${new Date(purchase.created_at).toLocaleDateString()}</td>
                          </tr>
                        `).join('')}
                      </tbody>
                    </table>
                  </div>
                ` : '<p class="text-gray-500 text-sm">No purchases found.</p>'}
              </div>
            `;
          } else {
            content.innerHTML = '<div class="text-center py-8 text-red-600">Error loading user details.</div>';
          }
        })
        .catch(error => {
          content.innerHTML = '<div class="text-center py-8 text-red-600">Error loading user details.</div>';
        });
    }

    function closeUserModal() {
      document.getElementById('userModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('userModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeUserModal();
      }
    });

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
