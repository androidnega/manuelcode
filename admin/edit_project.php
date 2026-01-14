<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

$error = '';
$success = '';

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header("Location: projects.php");
    exit;
}

// Get project data
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: projects.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $image_url = trim($_POST['image_url']);
    $image_caption = trim($_POST['image_caption']);
    $live_url = trim($_POST['live_url']);
    $github_url = trim($_POST['github_url']);
    $technologies = trim($_POST['technologies']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'];

    // Validation
    if (empty($title)) {
        $error = 'Project title is required';
    } elseif (empty($description)) {
        $error = 'Project description is required';
    } elseif (empty($category)) {
        $error = 'Project category is required';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET title = ?, description = ?, category = ?, image_url = ?, image_caption = ?, live_url = ?, 
                    github_url = ?, technologies = ?, featured = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $category, $image_url, $image_caption, $live_url, $github_url, $technologies, $featured, $status, $project_id]);
            
            $success = 'Project updated successfully!';
            
            // Update project data for display
            $project = array_merge($project, [
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'image_url' => $image_url,
                'image_caption' => $image_caption,
                'live_url' => $live_url,
                'github_url' => $github_url,
                'technologies' => $technologies,
                'featured' => $featured,
                'status' => $status
            ]);
        } catch (PDOException $e) {
            $error = 'Error updating project: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>manuelcode | Admin - Edit Project</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-[#F4F4F9] flex">
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
        <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
          <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
          <span class="flex-1">Reports</span>
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
          <h1 class="text-2xl font-bold text-[#2D3E50]">Edit Project</h1>
          <p class="text-gray-600 mt-1">Update project details and information.</p>
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
    <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 px-4 py-3">
      <div class="flex items-center justify-between">
        <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="text-lg font-semibold text-gray-800">Edit Project</h1>
        <div class="w-8"></div> <!-- Spacer for centering -->
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="p-4 lg:p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-[#2D3E50]">Edit Project</h1>
      <a href="projects.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
        Back to Projects
      </a>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <!-- Edit Project Form -->
    <div class="bg-white rounded-lg shadow p-6">
      <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Project Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Project Title *</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" required>
          </div>

          <!-- Category -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
            <select id="category" name="category" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" required>
              <option value="">Select Category</option>
              <option value="Web Development" <?php echo $project['category'] === 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
              <option value="Mobile Development" <?php echo $project['category'] === 'Mobile Development' ? 'selected' : ''; ?>>Mobile Development</option>
              <option value="Software Solution" <?php echo $project['category'] === 'Software Solution' ? 'selected' : ''; ?>>Software Solution</option>
              <option value="Integration" <?php echo $project['category'] === 'Integration' ? 'selected' : ''; ?>>Integration</option>
              <option value="UI/UX Design" <?php echo $project['category'] === 'UI/UX Design' ? 'selected' : ''; ?>>UI/UX Design</option>
              <option value="Database Design" <?php echo $project['category'] === 'Database Design' ? 'selected' : ''; ?>>Database Design</option>
            </select>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
          <textarea id="description" name="description" rows="4" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                    placeholder="Describe your project, features, and technologies used..." required><?php echo htmlspecialchars($project['description']); ?></textarea>
        </div>

        <!-- Technologies -->
        <div>
          <label for="technologies" class="block text-sm font-medium text-gray-700 mb-2">Technologies Used</label>
          <input type="text" id="technologies" name="technologies" value="<?php echo htmlspecialchars($project['technologies']); ?>" 
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
                <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($project['image_url']); ?>" 
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
              <input type="text" id="image_caption" name="image_caption" value="<?php echo htmlspecialchars($project['image_caption'] ?? ''); ?>" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                     placeholder="Brief description of the project image">
              <p class="text-sm text-gray-500 mt-1">Optional caption to display with the image</p>
            </div>
          </div>

          <!-- Image Preview -->
          <div id="image_preview_container" class="mt-4 <?php echo !empty($project['image_url']) ? '' : 'hidden'; ?>">
            <label class="block text-sm font-medium text-gray-700 mb-2">Image Preview</label>
            <div class="border border-gray-300 rounded-lg p-4 bg-white">
              <img id="image_preview" src="<?php echo htmlspecialchars(get_image_preview_url($project['image_url'])); ?>" alt="Project Preview" class="max-w-full h-auto max-h-64 mx-auto rounded" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
              <div id="image_error" class="text-center text-gray-500 py-8" style="display: none;">
                <i class="fas fa-image text-4xl mb-2"></i>
                <p>Image could not be loaded</p>
              </div>
              <p id="image_preview_caption" class="text-center text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($project['image_caption'] ?? 'Project Image'); ?></p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Live URL -->
          <div>
            <label for="live_url" class="block text-sm font-medium text-gray-700 mb-2">Live Demo URL</label>
            <input type="url" id="live_url" name="live_url" value="<?php echo htmlspecialchars($project['live_url']); ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                   placeholder="https://demo.manuelcode.info">
          </div>

          <!-- GitHub URL -->
          <div>
            <label for="github_url" class="block text-sm font-medium text-gray-700 mb-2">GitHub Repository URL</label>
            <input type="url" id="github_url" name="github_url" value="<?php echo htmlspecialchars($project['github_url']); ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent" 
                   placeholder="https://github.com/manuelcode/project-name">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Featured -->
          <div>
            <label class="flex items-center">
              <input type="checkbox" name="featured" value="1" <?php echo $project['featured'] ? 'checked' : ''; ?> 
                     class="rounded border-gray-300 text-[#F5A623] focus:ring-[#F5A623]">
              <span class="ml-2 text-sm text-gray-700">Featured Project</span>
            </label>
            <p class="text-sm text-gray-500 mt-1">Featured projects appear prominently on the projects page</p>
          </div>

          <!-- Status -->
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select id="status" name="status" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent">
              <option value="active" <?php echo $project['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo $project['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
          <a href="projects.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition-colors">
            Cancel
          </a>
          <button type="submit" class="bg-[#F5A623] text-white px-6 py-2 rounded hover:bg-[#d88c1b] transition-colors">
            Update Project
          </button>
        </div>
      </form>
    </div>
  </main>
  </div>

  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

  <script>
    // Sidebar toggle functionality
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('mobile-overlay');
      
      if (sidebar.classList.contains('translate-x-0')) {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      } else {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('hidden');
      }
    }

    $(document).ready(function() {
      // Image preview functionality
      $('#preview_btn').click(function() {
        const imageUrl = $('#image_url').val();
        const imageCaption = $('#image_caption').val();
        
        if (imageUrl) {
          // Convert Google Drive URL to direct image URL if needed
          let processedUrl = imageUrl;
          
          // Handle Google Drive URLs
          if (imageUrl.includes('drive.google.com')) {
            // Convert sharing URL to direct image URL
            if (imageUrl.includes('/file/d/')) {
              const fileId = imageUrl.match(/\/file\/d\/([^\/]+)/);
              if (fileId) {
                processedUrl = `https://drive.google.com/uc?export=view&id=${fileId[1]}`;
              }
            }
          }
          
          $('#image_preview').attr('src', processedUrl);
          $('#image_preview_caption').text(imageCaption || 'Project Image');
          $('#image_preview_container').removeClass('hidden');
        } else {
          alert('Please enter an image URL first');
        }
      });

      // Auto-preview when image URL changes
      $('#image_url').on('blur', function() {
        if ($(this).val()) {
          $('#preview_btn').click();
        }
      });

      // Auto-preview when caption changes
      $('#image_caption').on('input', function() {
        if ($('#image_preview_container').is(':visible')) {
          $('#image_preview_caption').text($(this).val() || 'Project Image');
        }
      });
    });
  </script>
</body>
</html>
