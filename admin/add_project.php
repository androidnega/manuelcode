<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $technologies = trim($_POST['technologies']);
    $image_url = trim($_POST['image_url']);
    $image_caption = trim($_POST['image_caption']);
    $live_url = trim($_POST['live_url']);
    $github_url = trim($_POST['github_url']);
    $featured = isset($_POST['featured']) ? 1 : 0;

    if (empty($title) || empty($description) || empty($category)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, description, category, technologies, image_url, image_caption, live_url, github_url, featured, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([$title, $description, $category, $technologies, $image_url, $image_caption, $live_url, $github_url, $featured]);
            
            $success = 'Project added successfully!';
            
            // Clear form data
            $title = $description = $category = $technologies = $image_url = $image_caption = $live_url = $github_url = '';
            $featured = 0;
            
        } catch (Exception $e) {
            $error = 'Error adding project: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
  <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Add Project - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
      .form-container {
        width: 100%;
        max-width: 100%;
      }
    }
    
    @media (max-width: 768px) {
      .main-content {
        padding: 0.75rem;
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
      .form-container {
        width: 100%;
        max-width: 100%;
      }
      .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
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
    }
    
    @media (max-width: 640px) {
      .main-content {
        padding: 0.5rem;
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
      .form-container {
        padding: 1rem;
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
      .form-container {
        padding: 0.75rem;
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
        width: 100%;
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
    <div class="flex-1 lg:ml-0 min-h-screen">
      <!-- Desktop Header -->
      <header class="hidden lg:block bg-white shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-[#2D3E50]">Add New Project</h1>
            <p class="text-gray-600 mt-1">Create a new project to showcase your work.</p>
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
          <h1 class="mobile-title font-semibold text-gray-800">Add Project</h1>
          <div class="w-8"></div> <!-- Spacer for centering -->
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="main-content p-4 lg:p-6 w-full">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-[#2D3E50]">Add New Project</h1>
          <a href="projects.php" class="mobile-button bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
            Back to Projects
          </a>
        </div>

        <?php if (isset($error)): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>

        <!-- Add Project Form -->
        <div class="form-container bg-white rounded-lg shadow p-6 w-full">
          <form method="POST" class="space-y-6">
            <div class="form-grid grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Project Title -->
              <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Project Title *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" required>
              </div>

              <!-- Category -->
              <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <select id="category" name="category" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" required>
                  <option value="">Select Category</option>
                  <option value="Web Development" <?php echo ($category ?? '') === 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
                  <option value="Mobile Development" <?php echo ($category ?? '') === 'Mobile Development' ? 'selected' : ''; ?>>Mobile Development</option>
                  <option value="Software Solution" <?php echo ($category ?? '') === 'Software Solution' ? 'selected' : ''; ?>>Software Solution</option>
                  <option value="Integration" <?php echo ($category ?? '') === 'Integration' ? 'selected' : ''; ?>>Integration</option>
                  <option value="UI/UX Design" <?php echo ($category ?? '') === 'UI/UX Design' ? 'selected' : ''; ?>>UI/UX Design</option>
                  <option value="Database Design" <?php echo ($category ?? '') === 'Database Design' ? 'selected' : ''; ?>>Database Design</option>
                </select>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
              <textarea id="description" name="description" rows="4" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                        placeholder="Describe your project, features, and technologies used..." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <!-- Technologies -->
            <div>
              <label for="technologies" class="block text-sm font-medium text-gray-700 mb-2">Technologies Used</label>
              <input type="text" id="technologies" name="technologies" value="<?php echo htmlspecialchars($technologies ?? ''); ?>" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                     placeholder="e.g., React, Node.js, MongoDB, Docker">
              <p class="text-sm text-gray-500 mt-1">Separate technologies with commas</p>
            </div>

            <!-- Image Section -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-700 mb-4">Project Image</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Image URL -->
                <div>
                  <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                  <div class="flex space-x-2">
                    <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($image_url ?? ''); ?>" 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                           placeholder="https://drive.google.com/file/d/... or any image URL">
                    <button type="button" id="preview_btn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                      Preview
                    </button>
                  </div>
                  <div class="mt-2 text-sm text-gray-600">
                    <p>• Google Drive: Use sharing link and replace /view with /preview</p>
                    <p>• Other sites: Direct image URL (jpg, png, gif, webp)</p>
                    <p>• Example: https://drive.google.com/file/d/FILE_ID/preview</p>
                  </div>
                </div>

                <!-- Image Caption -->
                <div>
                  <label for="image_caption" class="block text-sm font-medium text-gray-700 mb-2">Image Caption</label>
                  <input type="text" id="image_caption" name="image_caption" value="<?php echo htmlspecialchars($image_caption ?? ''); ?>" 
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                         placeholder="Brief description of the project image">
                  <p class="text-sm text-gray-500 mt-1">Optional caption to display with the image</p>
                </div>
              </div>

              <!-- Image Preview -->
              <div id="image_preview_container" class="mt-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Image Preview</label>
                <div class="border border-gray-300 rounded-lg p-4 bg-white">
                  <img id="image_preview" src="" alt="Project Preview" class="max-w-full h-auto max-h-64 mx-auto rounded">
                  <p id="image_preview_caption" class="text-center text-sm text-gray-600 mt-2"></p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Live URL -->
              <div>
                <label for="live_url" class="block text-sm font-medium text-gray-700 mb-2">Live URL</label>
                <input type="url" id="live_url" name="live_url" value="<?php echo htmlspecialchars($live_url ?? ''); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                       placeholder="https://project-demo.com">
                <p class="text-sm text-gray-500 mt-1">Link to live demo or website</p>
              </div>

              <!-- GitHub URL -->
              <div>
                <label for="github_url" class="block text-sm font-medium text-gray-700 mb-2">GitHub URL</label>
                <input type="url" id="github_url" name="github_url" value="<?php echo htmlspecialchars($github_url ?? ''); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                       placeholder="https://github.com/username/project">
                <p class="text-sm text-gray-500 mt-1">Link to source code repository</p>
              </div>
            </div>

            <!-- Featured Project -->
            <div class="flex items-center">
              <input type="checkbox" id="featured" name="featured" value="1" <?php echo ($featured ?? 0) ? 'checked' : ''; ?> 
                     class="h-4 w-4 text-[#F5A623] focus:ring-[#F5A623] border-gray-300 rounded">
              <label for="featured" class="ml-2 block text-sm text-gray-700">
                Mark as Featured Project
              </label>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
              <a href="projects.php" class="mobile-button bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition-colors">
                Cancel
              </a>
              <button type="submit" class="mobile-button bg-[#F5A623] text-white px-6 py-2 rounded hover:bg-[#d88c1b] transition-colors">
                Add Project
              </button>
            </div>
          </form>
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

    // Image preview functionality
    $(document).ready(function() {
      $('#preview_btn').click(function() {
        const imageUrl = $('#image_url').val();
        const caption = $('#image_caption').val();
        
        if (imageUrl) {
          // Convert Google Drive sharing link to direct view link
          let directUrl = imageUrl;
          if (imageUrl.includes('drive.google.com')) {
            directUrl = imageUrl.replace('/view', '/preview').replace('/edit', '/preview');
          }
          
          $('#image_preview').attr('src', directUrl);
          $('#image_preview_caption').text(caption || '');
          $('#image_preview_container').removeClass('hidden');
        } else {
          alert('Please enter an image URL first.');
        }
      });

      // Auto-preview when image URL changes
      $('#image_url').on('input', function() {
        const imageUrl = $(this).val();
        if (imageUrl) {
          let directUrl = imageUrl;
          if (imageUrl.includes('drive.google.com')) {
            directUrl = imageUrl.replace('/view', '/preview').replace('/edit', '/preview');
          }
          $('#image_preview').attr('src', directUrl);
          $('#image_preview_container').removeClass('hidden');
        }
      });

      // Update caption preview when caption changes
      $('#image_caption').on('input', function() {
        $('#image_preview_caption').text($(this).val() || '');
      });
    });

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
