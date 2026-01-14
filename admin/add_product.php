<?php
// Include admin authentication check
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/config.php';
include '../includes/util.php';
include '../includes/product_notification_helper.php';

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $short_desc = trim($_POST['short_desc']);
    $full_desc = trim($_POST['full_desc']);
    $product_type = $_POST['product_type'] ?? 'paid';
    $price = ($product_type === 'free') ? 0.00 : (float) $_POST['price'];
    $drive_link = trim($_POST['drive_link']);

    // Preview image validation
    if (!isset($_FILES['preview_image']) || $_FILES['preview_image']['error'] !== UPLOAD_ERR_OK) {
        die('Preview image required');
    }
    
    $preview_validation = validate_file_upload($_FILES['preview_image'], ALLOWED_IMAGE_TYPES, MAX_PREVIEW_SIZE);
    if (!$preview_validation['valid']) {
        die('Preview image: ' . $preview_validation['error']);
    }
    
    $imgName = sanitize_filename($_FILES['preview_image']['name']);
    $imgPath = "../assets/images/products/" . $imgName;
    
    // Ensure the upload directory exists
    $uploadDir = "../assets/images/products/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    move_uploaded_file($_FILES['preview_image']['tmp_name'], $imgPath);

    // Optional documentation file
    $docName = null;
    if (!empty($_FILES['doc_file']['name'])) {
        if ($_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $doc_validation = validate_file_upload($_FILES['doc_file'], ALLOWED_DOC_TYPES, MAX_DOC_SIZE);
            if (!$doc_validation['valid']) {
                die('Documentation file: ' . $doc_validation['error']);
            }
            
            $docName = sanitize_filename($_FILES['doc_file']['name']);
            
            // Ensure the upload directory exists
            $uploadDir = "../assets/docs/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            move_uploaded_file($_FILES['doc_file']['tmp_name'], "../assets/docs/" . $docName);
        }
    }

    $tags = trim($_POST['tags'] ?? '');
    $demo_url = trim($_POST['demo_url'] ?? '');
    
    // Handle gallery images
    $gallery_images = [];
    if (isset($_FILES['gallery_images'])) {
        $uploadDir = "../assets/images/products/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_validation = validate_file_upload([
                    'name' => $_FILES['gallery_images']['name'][$key],
                    'type' => $_FILES['gallery_images']['type'][$key],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$key],
                    'error' => $_FILES['gallery_images']['error'][$key],
                    'size' => $_FILES['gallery_images']['size'][$key]
                ], ALLOWED_IMAGE_TYPES, MAX_PREVIEW_SIZE);
                
                if ($file_validation['valid']) {
                    $galleryName = sanitize_filename($_FILES['gallery_images']['name'][$key]);
                    $galleryPath = $uploadDir . $galleryName;
                    
                    if (move_uploaded_file($tmp_name, $galleryPath)) {
                        $gallery_images[] = $galleryName;
                    }
                }
            }
        }
    }
    
    $gallery_json = json_encode($gallery_images);
    
    $stmt = $pdo->prepare("INSERT INTO products (title, short_desc, full_desc, price, preview_image, gallery_images, demo_url, drive_link, doc_file, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$title, $short_desc, $full_desc, $price, $imgName, $gallery_json, $demo_url, $drive_link, $docName, $tags]);
    
    $product_id = $pdo->lastInsertId();
    
    // Send notifications to users if this is a free product (they can download immediately)
    if ($price == 0) {
        $productNotificationHelper = new ProductNotificationHelper($pdo);
        $productNotificationHelper->notifyProductUpdate($product_id, 'new_free_product', [
            'type' => 'new_free_product',
            'message' => "A new free product '{$title}' is now available for download!"
        ]);
    }

    header("Location: ../dashboard/");
    exit;
}
?>

<!DOCTYPE html>
<html>
  <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>manuelcode | Admin - Add Product</title>
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
  
  <?php include 'includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <div class="flex-1 lg:ml-0 min-h-screen">

    <!-- Mobile Header -->
    <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 mobile-header">
      <div class="flex items-center justify-between">
        <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <h1 class="mobile-title font-semibold text-gray-800">Add Product</h1>
        <div class="w-8"></div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="main-content p-4 lg:p-6">
      <div class="form-container bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-200 w-full">
          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Product Title</label>
              <input name="title" placeholder="Enter product title" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent" required>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
              <textarea name="short_desc" placeholder="Brief product description" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent h-20" required></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Full Description</label>
              <textarea name="full_desc" placeholder="Detailed product description" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent h-32" required></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Product Pricing</label>
              <div class="space-y-3">
                <div class="flex items-center space-x-3">
                  <input type="radio" id="paid_product" name="product_type" value="paid" class="w-4 h-4 text-[#2D3E50] border-gray-300 focus:ring-[#2D3E50]" checked>
                  <label for="paid_product" class="text-sm font-medium text-gray-700">Paid Product</label>
                </div>
                <div class="flex items-center space-x-3">
                  <input type="radio" id="free_product" name="product_type" value="free" class="w-4 h-4 text-[#2D3E50] border-gray-300 focus:ring-[#2D3E50]">
                  <label for="free_product" class="text-sm font-medium text-gray-700">Free Product</label>
                </div>
              </div>
            </div>
            
            <div id="price_field">
              <label class="block text-sm font-medium text-gray-700 mb-2">Price (GHS)</label>
              <input name="price" type="number" step="0.01" placeholder="0.00" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent" required>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Google Drive Link</label>
              <input name="drive_link" placeholder="https://drive.google.com/..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent" required>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tech Stack Tags</label>
              <input name="tags" placeholder="e.g., HTML, CSS, JavaScript, PHP, MySQL" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent">
              <p class="text-xs text-gray-500 mt-1">Enter tech stack tags separated by commas (e.g., HTML, CSS, JavaScript)</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Preview Image (max 200KB)</label>
              <input type="file" name="preview_image" accept="image/png,image/jpeg,image/webp" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent" required>
              <p class="text-xs text-gray-500 mt-1">Accepted formats: PNG, JPEG, WebP. Maximum size: 200KB</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Gallery Images (optional, max 5 images)</label>
              <input type="file" name="gallery_images[]" accept="image/png,image/jpeg,image/webp" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent" multiple>
              <p class="text-xs text-gray-500 mt-1">Upload 3-5 screenshots for product gallery. Accepted formats: PNG, JPEG, WebP. Maximum size: 200KB per image</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Demo URL (optional)</label>
              <input name="demo_url" type="url" placeholder="https://demo.example.com" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent">
              <p class="text-xs text-gray-500 mt-1">Live demo link for the product (optional)</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Documentation File (optional)</label>
              <input type="file" name="doc_file" accept="application/pdf" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D3E50] focus:border-transparent">
              <p class="text-xs text-gray-500 mt-1">PDF format only. Maximum size: 5MB</p>
            </div>
            
            <button class="mobile-button w-full bg-[#2D3E50] text-white py-3 rounded-lg hover:bg-[#243646] transition-colors">
              <i class="fas fa-save mr-2"></i>Save Product
            </button>
          </form>
        </div>
      </main>
    </div>
    
    <?php include 'includes/footer.php'; ?>

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

      // Handle product pricing radio buttons
      document.addEventListener('DOMContentLoaded', function() {
        const paidRadio = document.getElementById('paid_product');
        const freeRadio = document.getElementById('free_product');
        const priceField = document.getElementById('price_field');
        const priceInput = priceField.querySelector('input[name="price"]');

        function togglePriceField() {
          if (freeRadio.checked) {
            priceField.style.display = 'none';
            priceInput.value = '0.00';
            priceInput.required = false;
          } else {
            priceField.style.display = 'block';
            priceInput.required = true;
          }
        }

        // Initial state
        togglePriceField();

        // Add event listeners
        paidRadio.addEventListener('change', togglePriceField);
        freeRadio.addEventListener('change', togglePriceField);
      });
    </script>
  </body>
</html>