<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/analytics_helper.php';

$admin_username = $_SESSION['admin_name'] ?? 'Admin';

// Handle purchase status updates
if (isset($_POST['update_status']) && isset($_POST['purchase_id']) && isset($_POST['status'])) {
    $purchase_id = $_POST['purchase_id'];
    $status = $_POST['status'];
    $purchase_type = $_POST['purchase_type'] ?? 'user';
    
    try {
        if ($purchase_type === 'guest') {
            $stmt = $pdo->prepare("UPDATE guest_orders SET status = ? WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE purchases SET status = ? WHERE id = ?");
        }
        $stmt->execute([$status, $purchase_id]);
        header("Location: ../dashboard/purchase-management?success=updated");
        exit;
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Handle purchase deletion
if (isset($_POST['delete_purchase'])) {
    $purchase_id = $_POST['purchase_id'];
    $purchase_type = $_POST['purchase_type'] ?? 'user';
    
    try {
        if ($purchase_type === 'guest') {
            $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
        }
        $stmt->execute([$purchase_id]);
        header("Location: ../dashboard/purchase-management?success=deleted");
        exit;
    } catch (Exception $e) {
        $error_message = "Error deleting purchase: " . $e->getMessage();
    }
}

// Handle bulk deletion
if (isset($_POST['bulk_delete']) && isset($_POST['selected_purchases'])) {
    $selected_json = $_POST['selected_purchases'];
    $deleted_count = 0;
    $errors = [];
    
    // Decode the JSON string to get array of purchase data
    $selected = json_decode($selected_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = "Invalid purchase data format. JSON Error: " . json_last_error_msg();
        error_log("Bulk delete error - JSON decode failed. Error: " . json_last_error_msg() . " | Data: " . substr($selected_json, 0, 500));
    } elseif (!is_array($selected) || empty($selected)) {
        $error_message = "No purchases selected or invalid data format.";
        error_log("Bulk delete error - Empty or invalid array. Data: " . substr($selected_json, 0, 500));
    } else {
        try {
            $pdo->beginTransaction();
            
            foreach ($selected as $purchase_data) {
                // Data should already be decoded as array from JSON
                if (!is_array($purchase_data) || !isset($purchase_data['id'])) {
                    error_log("Invalid purchase data format: " . print_r($purchase_data, true));
                    continue;
                }
                
                $purchase_id = (int)$purchase_data['id'];
                $purchase_type = $purchase_data['type'] ?? 'user';
                
                if ($purchase_id <= 0) {
                    error_log("Invalid purchase ID: {$purchase_id}");
                    continue;
                }
                
                try {
                    if ($purchase_type === 'guest') {
                        $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE id = ?");
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
                    }
                    $stmt->execute([$purchase_id]);
                    
                    // Check if row was actually deleted
                    if ($stmt->rowCount() > 0) {
                        $deleted_count++;
                    } else {
                        error_log("No rows deleted for purchase ID {$purchase_id} (type: {$purchase_type}) - purchase may not exist");
                    }
                } catch (Exception $e) {
                    $errors[] = "Error deleting purchase ID {$purchase_id}: " . $e->getMessage();
                    error_log("Bulk delete error for purchase {$purchase_id}: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            // Preserve current filters in redirect
            $redirect_params = ['success' => 'bulk_deleted', 'count' => $deleted_count];
            if (!empty($search)) $redirect_params['search'] = $search;
            if (!empty($status_filter)) $redirect_params['status'] = $status_filter;
            if (!empty($date_from)) $redirect_params['date_from'] = $date_from;
            if (!empty($date_to)) $redirect_params['date_to'] = $date_to;
            if ($page > 1) $redirect_params['page'] = $page;
            
            header("Location: ../dashboard/purchase-management?" . http_build_query($redirect_params));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error during bulk deletion: " . $e->getMessage();
            error_log("Bulk delete transaction error: " . $e->getMessage());
        }
    }
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Validate and sanitize sort parameters
$allowed_sort_fields = ['id', 'product_title', 'user_name', 'price', 'status', 'created_at'];
$sort_by = in_array($_GET['sort'] ?? '', $allowed_sort_fields) ? $_GET['sort'] : 'created_at';

$allowed_sort_orders = ['ASC', 'DESC'];
$sort_order = in_array(strtoupper($_GET['order'] ?? ''), $allowed_sort_orders) ? strtoupper($_GET['order']) : 'DESC';

// Build query with filters for user purchases
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(pr.title LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.user_id LIKE ? OR p.id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(p.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(p.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total user purchases count with filters
$count_query = "
    SELECT COUNT(*) 
    FROM purchases p 
    JOIN products pr ON p.product_id = pr.id 
    LEFT JOIN users u ON p.user_id = u.id
    $where_clause
";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_user_purchases = $stmt->fetchColumn();

// Build query with filters for guest purchases
$guest_where_conditions = [];
$guest_params = [];

if (!empty($search)) {
    $guest_where_conditions[] = "(pr.title LIKE ? OR go.name LIKE ? OR go.email LIKE ? OR go.unique_id LIKE ? OR go.id LIKE ?)";
    $search_param = "%$search%";
    $guest_params[] = $search_param;
    $guest_params[] = $search_param;
    $guest_params[] = $search_param;
    $guest_params[] = $search_param;
    $guest_params[] = $search_param;
}

if (!empty($status_filter)) {
    $guest_where_conditions[] = "go.status = ?";
    $guest_params[] = $status_filter;
}

if (!empty($date_from)) {
    $guest_where_conditions[] = "DATE(go.created_at) >= ?";
    $guest_params[] = $date_from;
}

if (!empty($date_to)) {
    $guest_where_conditions[] = "DATE(go.created_at) <= ?";
    $guest_params[] = $date_to;
}

$guest_where_clause = !empty($guest_where_conditions) ? 'WHERE ' . implode(' AND ', $guest_where_conditions) : '';

// Get total guest purchases count with filters
$guest_count_query = "
    SELECT COUNT(*) 
    FROM guest_orders go 
    JOIN products pr ON go.product_id = pr.id 
    $guest_where_clause
";
$stmt = $pdo->prepare($guest_count_query);
$stmt->execute($guest_params);
$total_guest_purchases = $stmt->fetchColumn();

$total_purchases = $total_user_purchases + $total_guest_purchases;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = (int)20;
$offset = (int)(($page - 1) * $limit);
$total_pages = ceil($total_purchases / $limit);

// Get purchases with filters and sorting
$order_clause = '';
switch($sort_by) {
    case 'id':
        $order_clause = 'ORDER BY p.id ' . $sort_order;
        break;
    case 'product_title':
        $order_clause = 'ORDER BY pr.title ' . $sort_order;
        break;
    case 'user_name':
        $order_clause = 'ORDER BY u.name ' . $sort_order;
        break;
    case 'price':
        $order_clause = 'ORDER BY pr.price ' . $sort_order;
        break;
    case 'status':
        $order_clause = 'ORDER BY p.status ' . $sort_order;
        break;
    default:
        $order_clause = 'ORDER BY p.created_at ' . $sort_order;
        break;
}

// Get user purchases
$user_query = "
    SELECT p.*, pr.title as product_title, pr.price, u.name as user_name, u.email as user_email, u.phone as user_phone, u.user_id, 'user' as purchase_type
    FROM purchases p 
    JOIN products pr ON p.product_id = pr.id 
    LEFT JOIN users u ON p.user_id = u.id
    $where_clause
    $order_clause
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
";

$stmt = $pdo->prepare($user_query);
$stmt->execute($params);
$user_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get guest purchases
$guest_order_clause = '';
switch($sort_by) {
    case 'id':
        $guest_order_clause = 'ORDER BY go.id ' . $sort_order;
        break;
    case 'product_title':
        $guest_order_clause = 'ORDER BY pr.title ' . $sort_order;
        break;
    case 'user_name':
        $guest_order_clause = 'ORDER BY go.name ' . $sort_order;
        break;
    case 'price':
        $guest_order_clause = 'ORDER BY pr.price ' . $sort_order;
        break;
    case 'status':
        $guest_order_clause = 'ORDER BY go.status ' . $sort_order;
        break;
    default:
        $guest_order_clause = 'ORDER BY go.created_at ' . $sort_order;
        break;
}

$guest_query = "
    SELECT go.*, pr.title as product_title, pr.price, go.name as user_name, go.email as user_email, go.phone as user_phone, go.unique_id as user_id, 'guest' as purchase_type
    FROM guest_orders go 
    JOIN products pr ON go.product_id = pr.id 
    $guest_where_clause
    $guest_order_clause
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
";

$stmt = $pdo->prepare($guest_query);
$stmt->execute($guest_params);
$guest_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine and sort all purchases
$all_purchases = array_merge($user_purchases, $guest_purchases);

// Sort combined results by created_at
usort($all_purchases, function($a, $b) use ($sort_order) {
    $date_a = strtotime($a['created_at']);
    $date_b = strtotime($b['created_at']);
    
    if ($sort_order === 'ASC') {
        return $date_a - $date_b;
    } else {
        return $date_b - $date_a;
    }
});

// Apply pagination to combined results
$all_purchases = array_slice($all_purchases, $offset, $limit);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>manuelcode | Admin - Purchase Management</title>
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
    }
    
    /* Base table styles - no horizontal scroll */
    .table-container {
      width: 100%;
      overflow-x: visible;
      border-radius: 12px;
    }
    .table-responsive {
      width: 100%;
      overflow-x: visible;
    }
    .table-responsive table {
      width: 100%;
      table-layout: auto;
      border-collapse: collapse;
    }
    .table-responsive th,
    .table-responsive td {
      word-wrap: break-word;
      overflow-wrap: break-word;
      hyphens: auto;
    }
    
    /* Hide Date column on tablets and below */
    @media (max-width: 1024px) {
      .table-responsive th.date-column,
      .table-responsive td.date-column {
        display: none;
      }
      .table-responsive th,
      .table-responsive td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
      }
    }
    
    /* Hide user ID on mobile, simplify customer display */
    @media (max-width: 768px) {
      .customer-user-id {
        display: none;
      }
      .product-title-full {
        display: none !important;
      }
      .product-title-short {
        display: block !important;
      }
      .main-content {
        padding: 0.75rem;
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
        margin: 0;
        width: 100%;
        overflow-x: visible;
        max-width: 100%;
      }
      .table-responsive {
        width: 100%;
        overflow-x: visible;
        max-width: 100%;
      }
      .table-responsive table {
        width: 100%;
        table-layout: auto;
        max-width: 100%;
      }
      .table-responsive th,
      .table-responsive td {
        padding: 0.5rem 0.25rem;
        font-size: 0.75rem;
        white-space: normal;
        word-break: break-word;
      }
      .table-responsive th:first-child,
      .table-responsive td:first-child {
        padding-left: 0.5rem;
      }
      .table-responsive th:last-child,
      .table-responsive td:last-child {
        padding-right: 0.5rem;
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
    
    /* Stack table on small screens - card layout */
    @media (max-width: 768px) {
      .table-responsive table {
        table-layout: auto !important;
      }
      .table-responsive th:nth-child(1),
      .table-responsive td:nth-child(1),
      .table-responsive th:nth-child(2),
      .table-responsive td:nth-child(2),
      .table-responsive th:nth-child(3),
      .table-responsive td:nth-child(3),
      .table-responsive th:nth-child(4),
      .table-responsive td:nth-child(4),
      .table-responsive th:nth-child(5),
      .table-responsive td:nth-child(5),
      .table-responsive th:nth-child(6),
      .table-responsive td:nth-child(6) {
        width: auto !important;
        min-width: auto !important;
      }
    }
    
    /* Stack table on very small screens - card layout */
    @media (max-width: 640px) {
      .main-content {
        padding: 0.5rem;
      }
      .mobile-header {
        padding: 0.75rem;
      }
      .mobile-title {
        font-size: 1.125rem;
      }
      .table-container {
        overflow-x: visible;
        margin: 0;
      }
      .table-responsive table,
      .table-responsive thead,
      .table-responsive tbody,
      .table-responsive th,
      .table-responsive td,
      .table-responsive tr {
        display: block;
      }
      .table-responsive thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }
      .table-responsive tr {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }
      .table-responsive td {
        border: none;
        position: relative;
        padding-left: 40% !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        text-align: left;
        white-space: normal;
        word-break: break-word;
      }
      .table-responsive td:before {
        content: attr(data-label);
        position: absolute;
        left: 0.75rem;
        width: 35%;
        padding-right: 0.5rem;
        white-space: nowrap;
        font-weight: 600;
        color: #374151;
        font-size: 0.75rem;
        text-transform: uppercase;
      }
      /* Show date in card layout on mobile */
      .table-responsive td.date-column {
        display: block !important;
      }
      .mobile-button {
        padding: 0.875rem 1.25rem;
        font-size: 0.9rem;
      }
    }
    
    @media (max-width: 480px) {
      .main-content {
        padding: 0.5rem;
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
      .table-responsive td {
        padding-left: 35% !important;
        font-size: 0.75rem;
      }
      .table-responsive td:before {
        width: 30%;
        font-size: 0.7rem;
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
          <a href="../dashboard/purchase-management" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
            <span class="flex-1">Purchase Management</span>
          </a>
          <a href="../dashboard/users" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span class="flex-1">Users</span>
          </a>
          <a href="../dashboard/reports" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
            <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
            <span class="flex-1">Reports</span>
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
        <a href="../../admin/auth/logout.php" class="flex items-center py-3 px-4 text-red-300 hover:bg-[#243646] rounded-lg transition-colors">
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
            <h1 class="text-2xl font-bold text-gray-800">Purchase Management</h1>
            <p class="text-gray-600 mt-1">Manage and track all user and guest purchases</p>
          </div>
          <div class="flex items-center space-x-4">
          </div>
        </div>
      </header>

      <!-- Mobile Header -->
      <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 mobile-header">
        <div class="flex items-center justify-between">
          <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <h1 class="text-lg font-semibold text-gray-800">Purchase Management</h1>
          <div class="w-8"></div> <!-- Spacer for centering -->
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-4 lg:p-6">


        <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
          <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <div>
                <p class="text-sm text-green-700">Purchase status updated successfully!</p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
          <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <div>
                <p class="text-sm text-green-700">Purchase deleted successfully!</p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'bulk_deleted'): ?>
          <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <div>
                <p class="text-sm text-green-700">
                  <?php 
                  $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
                  echo $count > 0 ? "Successfully deleted {$count} purchase(s)!" : "Purchases deleted successfully!";
                  ?>
                </p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
          <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
              <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
              <div>
                <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Search and Filter Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
          <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Search -->
              <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by product, user, email, user ID, or purchase ID"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
              
              <!-- Status Filter -->
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Statuses</option>
                  <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                  <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                  <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
              </div>
              
              <!-- Date From -->
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
              
              <!-- Date To -->
              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
            </div>
            
            <div class="flex flex-wrap gap-2">
              <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i>Search
              </button>
              <a href="../dashboard/purchase-management" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-times mr-2"></i>Clear Filters
              </a>
            </div>
          </form>
        </div>

        <!-- Results Summary -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex flex-wrap items-center justify-between">
            <div class="flex items-center space-x-4">
              <span class="text-sm font-medium text-blue-800">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_purchases); ?> of <?php echo $total_purchases; ?> purchases
              </span>
              <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                <span class="text-sm text-blue-600">
                  (Filtered results)
                </span>
              <?php endif; ?>
            </div>
            <div class="text-sm text-blue-600">
              Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
          </div>
        </div>

        <?php if (empty($all_purchases)): ?>
          <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 text-center">
            <i class="fas fa-credit-card text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Purchases Found</h3>
            <p class="text-gray-500">
              <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                No purchases match your current filters. Try adjusting your search criteria.
              <?php else: ?>
                Purchases will appear here once customers make transactions.
              <?php endif; ?>
            </p>
          </div>
        <?php else: ?>
          <!-- Bulk Actions Bar -->
          <div id="bulkActionsBar" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 hidden">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <span id="selectedCount" class="text-sm font-medium text-blue-800">0 selected</span>
                <button onclick="clearSelection()" class="text-sm text-blue-600 hover:text-blue-800 underline">
                  Clear selection
                </button>
              </div>
              <form method="POST" id="bulkDeleteForm" action="../dashboard/purchase-management" onsubmit="return confirmBulkDelete()">
                <input type="hidden" name="bulk_delete" value="1">
                <input type="hidden" name="selected_purchases" id="selectedPurchasesInput">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                  <i class="fas fa-trash mr-2"></i>
                  Delete Selected
                </button>
              </form>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mobile-card">
            <div class="table-container">
              <table class="table-responsive w-full" style="min-width: 0;">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 40px;">
                      <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-label="Purchase ID">Purchase ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-label="Customer">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-label="Product">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-label="Amount">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-label="Status">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider date-column" data-label="Date">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-label="Actions">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php foreach ($all_purchases as $purchase): ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4" style="width: 40px;">
                        <input type="checkbox" 
                               class="purchase-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                               data-purchase='<?php echo htmlspecialchars(json_encode(['id' => $purchase['id'], 'type' => $purchase['purchase_type']])); ?>'
                               onchange="updateBulkActions()">
                      </td>
                      <td class="px-6 py-4 text-sm font-medium text-gray-900" data-label="Purchase ID">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 sm:gap-2">
                          <span class="text-xs sm:text-sm"><?php echo $purchase['order_id'] ?? 'PUR' . str_pad($purchase['id'], 6, '0', STR_PAD_LEFT); ?></span>
                          <?php if ($purchase['purchase_type'] === 'guest'): ?>
                            <span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full whitespace-nowrap">Guest</span>
                          <?php else: ?>
                            <span class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 rounded-full whitespace-nowrap">User</span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900" data-label="Customer">
                        <div>
                          <div class="font-medium"><?php echo htmlspecialchars($purchase['user_name'] ?? 'Guest'); ?></div>
                          <?php if ($purchase['user_id']): ?>
                            <div class="text-gray-500 font-mono text-xs customer-user-id">ID: <?php echo htmlspecialchars($purchase['user_id']); ?></div>
                          <?php endif; ?>
                          <div class="text-gray-500 break-words text-xs"><?php echo htmlspecialchars($purchase['user_email'] ?? 'No email'); ?></div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900" data-label="Product">
                        <div class="break-words product-title-full"><?php echo htmlspecialchars($purchase['product_title']); ?></div>
                        <div class="break-words product-title-short" style="display: none;">
                          <?php 
                          $title = htmlspecialchars($purchase['product_title']);
                          echo strlen($title) > 30 ? substr($title, 0, 30) . '...' : $title;
                          ?>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm font-bold text-[#4CAF50]" data-label="Amount">
                        <?php 
                        $amount = $purchase['amount'] ?? $purchase['price'];
                        $discount_amount = $purchase['discount_amount'] ?? 0;
                        $final_amount = $amount - $discount_amount;
                        ?>
                        <div>
                          <div class="text-xs sm:text-sm">GHS <?php echo number_format($final_amount, 2); ?></div>
                          <?php if ($discount_amount > 0): ?>
                            <div class="text-xs text-green-600 hidden sm:block">-₵<?php echo number_format($discount_amount, 2); ?> discount</div>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-6 py-4" data-label="Status">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                          <?php echo ($purchase['status'] ?? 'pending') === 'paid' ? 'bg-green-100 text-green-800' : 
                                    (($purchase['status'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-red-100 text-red-800'); ?>">
                          <?php echo ucfirst($purchase['status'] ?? 'pending'); ?>
                        </span>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-500 date-column" data-label="Date">
                        <div class="whitespace-normal"><?php echo date('M j, Y g:i A', strtotime($purchase['created_at'])); ?></div>
                      </td>
                      <td class="px-6 py-4 text-sm font-medium" data-label="Actions">
                        <div class="flex flex-wrap gap-1 sm:gap-2 items-center">
                          <button onclick="viewPurchaseDetails(<?php echo htmlspecialchars(json_encode($purchase)); ?>)" 
                                  class="text-blue-600 hover:text-blue-900 px-1.5 py-1 rounded transition-colors text-xs sm:text-sm" title="View Details">
                            <i class="fas fa-eye"></i>
                          </button>
                          <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to update the status?')">
                            <input type="hidden" name="purchase_id" value="<?php echo $purchase['id']; ?>">
                            <input type="hidden" name="purchase_type" value="<?php echo $purchase['purchase_type']; ?>">
                            <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-1.5 py-1 focus:ring-2 focus:ring-blue-500 focus:border-transparent max-w-[90px] sm:max-w-none
                              <?php echo ($purchase['status'] ?? 'pending') === 'paid' ? 'bg-green-100 text-green-800' : 
                                        (($purchase['status'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        'bg-red-100 text-red-800'); ?>">
                              <option value="pending" <?php echo ($purchase['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                              <option value="paid" <?php echo ($purchase['status'] ?? 'pending') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                              <option value="failed" <?php echo ($purchase['status'] ?? 'pending') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                              <option value="cancelled" <?php echo ($purchase['status'] ?? 'pending') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                          </form>
                          <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this purchase?')">
                            <input type="hidden" name="purchase_id" value="<?php echo $purchase['id']; ?>">
                            <input type="hidden" name="purchase_type" value="<?php echo $purchase['purchase_type']; ?>">
                            <button type="submit" name="delete_purchase" class="text-red-600 hover:text-red-900 px-1.5 py-1 rounded transition-colors text-xs sm:text-sm" title="Delete">
                              <i class="fas fa-trash"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="bg-white px-6 py-4 border-t border-gray-200">
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                  Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_purchases); ?> of <?php echo $total_purchases; ?> purchases
                </div>
                <div class="flex space-x-2">
                  <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                      Previous
                    </a>
                  <?php endif; ?>
                  
                  <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="px-3 py-2 text-sm rounded-lg transition-colors <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                      <?php echo $i; ?>
                    </a>
                  <?php endfor; ?>
                  
                  <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                      Next
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <!-- Purchase Details Modal -->
  <div id="purchaseModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Purchase Details</h3>
        <button onclick="closePurchaseModal()" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <!-- Modal Content -->
      <div id="purchaseModalContent" class="p-6">
        <!-- Content will be populated by JavaScript -->
      </div>
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

    // Purchase Details Modal Functions
    function viewPurchaseDetails(purchaseData) {
      const modal = document.getElementById('purchaseModal');
      const content = document.getElementById('purchaseModalContent');
      
      const amount = purchaseData.amount || purchaseData.price;
      const discountAmount = purchaseData.discount_amount || 0;
      const finalAmount = amount - discountAmount;
      
      const purchaseType = purchaseData.purchase_type === 'guest' ? 'Guest Purchase' : 'User Purchase';
      const customerType = purchaseData.purchase_type === 'guest' ? 'Guest Customer' : 'Registered User';
      
      content.innerHTML = `
        <div class="space-y-6">
          <!-- Purchase Type Badge -->
          <div class="flex items-center justify-between">
            <span class="px-3 py-1 text-sm font-semibold rounded-full ${
              purchaseData.purchase_type === 'guest' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
            }">
              ${purchaseType}
            </span>
            <span class="px-3 py-1 text-sm font-semibold rounded-full ${
              purchaseData.status === 'paid' ? 'bg-green-100 text-green-800' : 
              purchaseData.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
            }">
              ${purchaseData.status.toUpperCase()}
            </span>
          </div>

          <!-- Order Information -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
              <i class="fas fa-shopping-cart mr-2 text-blue-500"></i>
              Order Information
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Order ID</label>
                <p class="text-sm text-gray-900 font-mono">${purchaseData.order_id || 'PUR' + purchaseData.id.toString().padStart(6, '0')}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Purchase Date</label>
                <p class="text-sm text-gray-900">${new Date(purchaseData.created_at).toLocaleString()}</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Payment Method</label>
                <p class="text-sm text-gray-900">Paystack</p>
              </div>
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Reference</label>
                <p class="text-sm text-gray-900 font-mono">${purchaseData.reference || 'N/A'}</p>
              </div>
            </div>
          </div>



          <!-- Product Information -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
              <i class="fas fa-box mr-2 text-blue-500"></i>
              Product Information
            </h4>
            <div class="space-y-3">
              <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Product Title</label>
                <p class="text-sm text-gray-900 font-medium">${purchaseData.product_title}</p>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Product ID</label>
                  <p class="text-sm text-gray-900 font-mono">${purchaseData.product_id}</p>
                </div>
                <div>
                  <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Original Price</label>
                  <p class="text-sm text-gray-900">GHS ${parseFloat(amount).toFixed(2)}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Information -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
              <i class="fas fa-credit-card mr-2 text-blue-500"></i>
              Payment Information
            </h4>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Original Price:</span>
                <span class="text-sm font-medium">GHS ${parseFloat(amount).toFixed(2)}</span>
              </div>
              ${discountAmount > 0 ? `
              <div class="flex justify-between items-center text-green-600">
                <span class="text-sm">Discount Applied:</span>
                <span class="text-sm font-medium">-₵${parseFloat(discountAmount).toFixed(2)}</span>
              </div>
              <div class="flex justify-between items-center text-green-600">
                <span class="text-sm">Coupon Code:</span>
                <span class="text-sm font-medium font-mono">${purchaseData.coupon_code || 'N/A'}</span>
              </div>
              ` : ''}
              <div class="border-t border-gray-200 pt-3">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-bold text-gray-900">Final Amount:</span>
                  <span class="text-lg font-bold text-green-600">GHS ${parseFloat(finalAmount).toFixed(2)}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button onclick="closePurchaseModal()" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
              Close
            </button>
            <a href="../product.php?id=${purchaseData.product_id}" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              <i class="fas fa-external-link-alt mr-2"></i>View Product
            </a>
          </div>
        </div>
      `;
      
      modal.classList.remove('hidden');
    }

    function closePurchaseModal() {
      const modal = document.getElementById('purchaseModal');
      modal.classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('purchaseModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closePurchaseModal();
      }
    });

    // Bulk Selection Functions
    function toggleSelectAll(checkbox) {
      const checkboxes = document.querySelectorAll('.purchase-checkbox');
      checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
      });
      updateBulkActions();
    }

    function updateBulkActions() {
      const checkboxes = document.querySelectorAll('.purchase-checkbox:checked');
      const bulkActionsBar = document.getElementById('bulkActionsBar');
      const selectedCount = document.getElementById('selectedCount');
      const selectedPurchasesInput = document.getElementById('selectedPurchasesInput');
      const selectAllCheckbox = document.getElementById('selectAll');
      
      const count = checkboxes.length;
      
      if (count > 0) {
        bulkActionsBar.classList.remove('hidden');
        selectedCount.textContent = count + ' selected';
        
        // Collect selected purchase data
        const selectedPurchases = [];
        checkboxes.forEach(cb => {
          const purchaseData = cb.getAttribute('data-purchase');
          if (purchaseData) {
            // Parse the JSON string from data attribute and add to array
            try {
              const parsed = JSON.parse(purchaseData);
              selectedPurchases.push(parsed);
            } catch (e) {
              console.error('Error parsing purchase data:', e, purchaseData);
            }
          }
        });
        const jsonData = JSON.stringify(selectedPurchases);
        console.log('Selected purchases JSON:', jsonData);
        selectedPurchasesInput.value = jsonData;
      } else {
        bulkActionsBar.classList.add('hidden');
      }
      
      // Update select all checkbox state
      const allCheckboxes = document.querySelectorAll('.purchase-checkbox');
      selectAllCheckbox.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
      selectAllCheckbox.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
    }

    function clearSelection() {
      const checkboxes = document.querySelectorAll('.purchase-checkbox');
      const selectAllCheckbox = document.getElementById('selectAll');
      
      checkboxes.forEach(cb => {
        cb.checked = false;
      });
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = false;
      updateBulkActions();
    }

    function confirmBulkDelete() {
      const checkboxes = document.querySelectorAll('.purchase-checkbox:checked');
      const count = checkboxes.length;
      return confirm(`Are you sure you want to delete ${count} selected purchase(s)? This action cannot be undone.`);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      updateBulkActions();
    });
  </script>
</body>
</html>
