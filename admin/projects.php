<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';
// Utilities (image preview helpers, etc.)
include_once '../includes/util.php';

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle project deletion
if (isset($_POST['delete_project'])) {
    $project_id = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    header("Location: projects.php?success=Project deleted successfully");
    exit;
}

// Handle project status toggle
if (isset($_POST['toggle_status'])) {
    $project_id = (int)$_POST['project_id'];
    $new_status = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $project_id]);
    header("Location: projects.php?success=Project status updated");
    exit;
}

// Get all projects with error handling and debugging
try {
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the number of projects found
    error_log("Projects found: " . count($projects));
    
    // Additional debugging
    if (empty($projects)) {
        error_log("No projects found in database");
        $error_message = "No projects found in database. Please check if projects exist.";
    }
} catch (PDOException $e) {
    error_log("Database error in projects.php: " . $e->getMessage());
    $projects = [];
    $error_message = "Database connection error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>manuelcode | Admin - Projects</title>
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
    
    /* Prevent scrollbars on body and html */
    html, body {
      overflow-x: hidden;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }
    
    html::-webkit-scrollbar, body::-webkit-scrollbar {
      display: none;
    }
    
    /* Ensure table doesn't cause horizontal overflow */
    .table-container {
      overflow-x: auto;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }
    
    .table-container::-webkit-scrollbar {
      display: none;
    }
    
    /* Ensure main content area doesn't overflow */
    .main-content {
      overflow-x: hidden;
      width: 100%;
    }
    
    /* Ensure actions are always visible and properly spaced */
    .actions-container {
      min-width: 120px;
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
    
    @media (max-width: 768px) {
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
      .search-filters {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
    }
    
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
      .mobile-button {
        padding: 0.875rem 1.25rem;
        font-size: 0.9rem;
      }
      .search-filters {
        grid-template-columns: 1fr;
        gap: 0.75rem;
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
    
    .actions-container {
      max-width: 150px;
    }
    
    /* Responsive table adjustments */
    @media (max-width: 1024px) {
      .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 12px;
        margin: 0 -1rem;
      }
      .table-responsive {
        min-width: 900px;
        padding: 0 1rem;
      }
      .actions-container {
        min-width: auto;
        max-width: none;
      }
    }
    
    @media (max-width: 768px) {
      .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 12px;
        margin: 0 -0.75rem;
      }
      .table-responsive {
        min-width: 700px;
        padding: 0 0.75rem;
      }
    }
    
    @media (max-width: 640px) {
      .table-responsive {
        min-width: 600px;
      }
    }
    
    @media (max-width: 480px) {
      .table-responsive {
        min-width: 550px;
      }
    }
    
    /* Ensure table columns don't overflow */
    table {
      table-layout: fixed;
      width: 100%;
    }
    
    /* Column widths for better space management */
    th:nth-child(1), td:nth-child(1) { width: 25%; } /* Project */
    th:nth-child(2), td:nth-child(2) { width: 15%; } /* Category */
    th:nth-child(3), td:nth-child(3) { width: 10%; } /* Status */
    th:nth-child(4), td:nth-child(4) { width: 10%; } /* Featured */
    th:nth-child(5), td:nth-child(5) { width: 15%; } /* Created */
    th:nth-child(6), td:nth-child(6) { width: 25%; } /* Actions */
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
        <a href="projects.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
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
    <div class="flex-1 lg:ml-0 h-screen flex flex-col main-content">
      <!-- Page Header -->
      <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Projects Management</h1>
            <p class="text-gray-600 mt-1">Manage your portfolio projects and showcase your work.</p>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content flex-1 overflow-y-auto p-4 lg:p-6">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6">
          <div class="mb-4 lg:mb-0">
          </div>
          <a href="add_project.php" class="mobile-button bg-[#4CAF50] text-white px-4 py-2 rounded-lg hover:bg-[#45a049] transition-colors inline-flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Add New Project
          </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
          <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-400 mr-3"></i>
              <div>
                <p class="text-sm text-green-700"><?php echo htmlspecialchars($_GET['success']); ?></p>
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

        <!-- Search and Filter Controls -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6 mobile-card">
          <div class="search-filters grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
              <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Projects</label>
              <input type="text" id="search" placeholder="Search by title, description..." 
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
            </div>
            
            <!-- Category Filter -->
            <div>
              <label for="categoryFilter" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
              <select id="categoryFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
                <option value="">All Categories</option>
                <option value="Web Development">Web Development</option>
                <option value="Mobile Development">Mobile Development</option>
                <option value="Software Solution">Software Solution</option>
                <option value="Integration">Integration</option>
              </select>
            </div>
            
            <!-- Status Filter -->
            <div>
              <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            
            <!-- Date Filter -->
            <div>
              <label for="dateFilter" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
              <select id="dateFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#536895] focus:border-transparent">
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Projects Grid (Mobile) / Table (Desktop) -->
        <div class="lg:hidden main-content">
          <!-- Mobile Cards -->
          <?php if (empty($projects)): ?>
            <div class="text-center py-12">
              <i class="fas fa-project-diagram text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-500 mb-4">No projects found.</p>
              <a href="add_project.php" class="bg-[#F5A623] text-white px-6 py-2 rounded-lg hover:bg-[#d88c1b] transition-colors">
                <i class="fas fa-plus mr-2"></i>Add First Project
              </a>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($projects as $project): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 project-card" data-project="<?php echo $project['id']; ?>">
                  <div class="flex items-start space-x-4">
                    <!-- Project Image -->
                    <div class="flex-shrink-0">
                      <?php 
                      $preview_url = get_image_preview_url($project['image_url']);
                      if ($preview_url): ?>
                        <img class="h-16 w-16 rounded-lg object-cover" src="<?php echo htmlspecialchars($preview_url); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center" style="display: none;">
                          <i class="fas fa-image text-gray-400"></i>
                        </div>
                      <?php else: ?>
                        <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center">
                          <i class="fas fa-image text-gray-400"></i>
                        </div>
                      <?php endif; ?>
                    </div>
                    
                    <!-- Project Info -->
                    <div class="flex-1 min-w-0">
                      <h3 class="text-lg font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($project['title']); ?></h3>
                      <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars(substr($project['description'], 0, 80)) . '...'; ?></p>
                      
                                             <!-- Tags -->
                       <div class="flex flex-wrap gap-2 mb-3">
                         <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full category-badge
                           <?php 
                           switch($project['category']) {
                             case 'Web Development': echo 'bg-blue-100 text-blue-800'; break;
                             case 'Mobile Development': echo 'bg-purple-100 text-purple-800'; break;
                             case 'Software Solution': echo 'bg-green-100 text-green-800'; break;
                             case 'Integration': echo 'bg-orange-100 text-orange-800'; break;
                             default: echo 'bg-gray-100 text-gray-800';
                           }
                           ?>">
                           <?php echo htmlspecialchars($project['category']); ?>
                         </span>
                        
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                          <?php echo $project['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                          <?php echo ucfirst($project['status']); ?>
                        </span>
                        
                        <?php if ($project['featured']): ?>
                          <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-star mr-1"></i>Featured
                          </span>
                        <?php endif; ?>
                      </div>
                      
                      <!-- Actions -->
                      <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                        <div class="flex space-x-2">
                          <a href="edit_project.php?id=<?php echo $project['id']; ?>" 
                             class="text-[#F5A623] hover:text-[#d88c1b] text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>Edit
                          </a>
                          
                          <form method="POST" class="inline">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $project['status'] === 'active' ? 'inactive' : 'active'; ?>">
                            <button type="submit" name="toggle_status" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                              <i class="fas fa-toggle-on mr-1"></i><?php echo $project['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                          </form>
                          
                          <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this project?')">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <button type="submit" name="delete_project" class="text-red-600 hover:text-red-900 text-sm font-medium">
                              <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Desktop Table -->
        <div class="hidden lg:block">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto table-container">
              <table class="table-responsive w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php foreach ($projects as $project): ?>
                    <tr class="hover:bg-gray-50" data-project="<?php echo $project['id']; ?>">
                      <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10">
                            <?php 
                            $preview_url = get_image_preview_url($project['image_url']);
                            if ($preview_url): ?>
                              <img class="h-10 w-10 rounded-lg object-cover" src="<?php echo htmlspecialchars($preview_url); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                              <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center" style="display: none;">
                                <i class="fas fa-image text-gray-400"></i>
                              </div>
                            <?php else: ?>
                              <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['title']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                          <?php 
                          switch($project['category']) {
                            case 'Web Development': echo 'bg-blue-100 text-blue-800'; break;
                            case 'Mobile Development': echo 'bg-purple-100 text-purple-800'; break;
                            case 'Software Solution': echo 'bg-green-100 text-green-800'; break;
                            case 'Integration': echo 'bg-orange-100 text-orange-800'; break;
                            default: echo 'bg-gray-100 text-gray-800';
                          }
                          ?>">
                          <?php echo htmlspecialchars($project['category']); ?>
                        </span>
                      </td>
                      <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                          <?php echo $project['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                          <?php echo ucfirst($project['status']); ?>
                        </span>
                      </td>
                      <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                          <?php echo $project['featured'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'; ?>">
                          <?php echo $project['featured'] ? 'Featured' : 'Regular'; ?>
                        </span>
                      </td>
                      <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                      </td>
                      <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm font-medium actions-container">
                        <div class="flex flex-col space-y-1">
                          <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="text-[#F5A623] hover:text-[#d88c1b] inline-flex items-center">
                            <i class="fas fa-edit mr-1"></i>Edit
                          </a>
                          
                          <!-- Toggle Status -->
                          <form method="POST" class="inline">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $project['status'] === 'active' ? 'inactive' : 'active'; ?>">
                            <button type="submit" name="toggle_status" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                              <i class="fas fa-toggle-on mr-1"></i><?php echo $project['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                          </form>
                          
                          <!-- Delete -->
                          <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this project?')">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <button type="submit" name="delete_project" class="text-red-600 hover:text-red-900 inline-flex items-center">
                              <i class="fas fa-trash mr-1"></i>Delete
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

          <?php if (empty($projects)): ?>
            <div class="text-center py-12">
              <i class="fas fa-project-diagram text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-500 mb-4">No projects found.</p>
              <a href="add_project.php" class="bg-[#F5A623] text-white px-6 py-2 rounded-lg hover:bg-[#d88c1b] transition-colors">
                <i class="fas fa-plus mr-2"></i>Add First Project
              </a>
            </div>
          <?php endif; ?>
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

      // Search and Filter Functionality
      const searchInput = document.getElementById('search');
      const categoryFilter = document.getElementById('categoryFilter');
      const statusFilter = document.getElementById('statusFilter');
      const dateFilter = document.getElementById('dateFilter');
      const projectRows = document.querySelectorAll('tr[data-project]');
      const projectCards = document.querySelectorAll('.project-card');

      function filterProjects() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryValue = categoryFilter.value;
        const statusValue = statusFilter.value;
        const dateValue = dateFilter.value;

        // Filter desktop table rows
        projectRows.forEach(row => {
          const title = row.querySelector('td:first-child .text-sm.font-medium')?.textContent.toLowerCase() || '';
          const description = row.querySelector('td:first-child .text-sm.text-gray-500')?.textContent.toLowerCase() || '';
          const category = row.querySelector('td:nth-child(2) span')?.textContent || '';
          const status = row.querySelector('td:nth-child(3) span')?.textContent.toLowerCase() || '';

          const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
          const matchesCategory = !categoryValue || category === categoryValue;
          const matchesStatus = !statusValue || status === statusValue;
          
          // Date filtering
          let matchesDate = true;
          if (dateValue) {
            const createdDate = new Date(row.querySelector('td:nth-child(5)')?.textContent || '');
            const now = new Date();
            
            switch(dateValue) {
              case 'today':
                matchesDate = createdDate.toDateString() === now.toDateString();
                break;
              case 'week':
                const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                matchesDate = createdDate >= weekAgo;
                break;
              case 'month':
                const monthAgo = new Date(now.getFullYear(), now.getMonth(), 1);
                matchesDate = createdDate >= monthAgo;
                break;
              case 'quarter':
                const quarterStart = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
                matchesDate = createdDate >= quarterStart;
                break;
              case 'year':
                const yearStart = new Date(now.getFullYear(), 0, 1);
                matchesDate = createdDate >= yearStart;
                break;
            }
          }

          if (matchesSearch && matchesCategory && matchesStatus && matchesDate) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });

        // Filter mobile cards
        projectCards.forEach(card => {
          const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
          const description = card.querySelector('p')?.textContent.toLowerCase() || '';
          const category = card.querySelector('.category-badge')?.textContent || '';
          const status = card.querySelector('.status-badge')?.textContent.toLowerCase() || '';

          const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
          const matchesCategory = !categoryValue || category === categoryValue;
          const matchesStatus = !statusValue || status === statusValue;
          
          // Date filtering for mobile cards
          let matchesDate = true;
          if (dateValue) {
            const createdDateText = card.querySelector('.text-xs.text-gray-500')?.textContent || '';
            const createdDate = new Date(createdDateText);
            const now = new Date();
            
            switch(dateValue) {
              case 'today':
                matchesDate = createdDate.toDateString() === now.toDateString();
                break;
              case 'week':
                const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                matchesDate = createdDate >= weekAgo;
                break;
              case 'month':
                const monthAgo = new Date(now.getFullYear(), now.getMonth(), 1);
                matchesDate = createdDate >= monthAgo;
                break;
              case 'quarter':
                const quarterStart = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
                matchesDate = createdDate >= quarterStart;
                break;
              case 'year':
                const yearStart = new Date(now.getFullYear(), 0, 1);
                matchesDate = createdDate >= yearStart;
                break;
            }
          }

          if (matchesSearch && matchesCategory && matchesStatus && matchesDate) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
      }

      // Add event listeners
      searchInput.addEventListener('input', filterProjects);
      categoryFilter.addEventListener('change', filterProjects);
      statusFilter.addEventListener('change', filterProjects);
      dateFilter.addEventListener('change', filterProjects);
    });
  </script>
</body>
</html>
