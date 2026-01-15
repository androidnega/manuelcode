<?php
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/notification_helper.php';
include '../includes/product_notification_helper.php';

$notificationHelper = new NotificationHelper($pdo);
$productNotificationHelper = new ProductNotificationHelper($pdo);

$admin_username = $_SESSION['admin_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $title = trim($_POST['title']);
    $short_desc = trim($_POST['short_desc']);
    $full_desc = trim($_POST['full_desc']);
    $product_type = $_POST['product_type'] ?? 'paid';
    $price = ($product_type === 'free') ? 0.00 : (float)$_POST['price'];
    $drive_link = trim($_POST['drive_link']);
    $tags = trim($_POST['tags'] ?? '');
    $demo_url = trim($_POST['demo_url'] ?? '');
    
    if (empty($title) || empty($drive_link)) {
        $error_message = 'Title and Google Drive link are required fields.';
    } else {
        try {
            // Handle file uploads
            $imgName = null;
            $docName = null;
            
            // Handle preview image upload
            if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
                $cloudinaryHelper = new CloudinaryHelper($pdo);
                $imgName = null;
                
                // Try Cloudinary upload first
                if ($cloudinaryHelper->isEnabled()) {
                    $uploadResult = $cloudinaryHelper->uploadImage($_FILES['preview_image']['tmp_name'], 'products');
                    if ($uploadResult && isset($uploadResult['url'])) {
                        $imgName = $uploadResult['url'];
                    }
                }
                
                // Fallback to local storage
                if (!$imgName) {
                    $imgName = time() . '_' . sanitize_filename($_FILES['preview_image']['name']);
                    $imgPath = '../assets/images/products/' . $imgName;
                    
                    // Ensure the upload directory exists
                    $uploadDir = '../assets/images/products/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Move uploaded file with error handling
                    if (!move_uploaded_file($_FILES['preview_image']['tmp_name'], $imgPath)) {
                        throw new Exception('Failed to upload preview image. Please check directory permissions.');
                    }
                }
            }
            
            // Handle documentation file upload
            if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                $docName = time() . '_' . sanitize_filename($_FILES['doc_file']['name']);
                $docPath = '../assets/docs/' . $docName;
                
                // Ensure the upload directory exists
                $uploadDir = '../assets/docs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Move uploaded file with error handling
                if (!move_uploaded_file($_FILES['doc_file']['tmp_name'], $docPath)) {
                    throw new Exception('Failed to upload documentation file. Please check directory permissions.');
                }
            }
            
            // Handle gallery images upload
            $gallery_images = [];
            if (isset($_FILES['gallery_images'])) {
                $uploadDir = '../assets/images/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $galleryName = time() . '_' . sanitize_filename($_FILES['gallery_images']['name'][$key]);
                        $galleryPath = $uploadDir . $galleryName;
                        
                        if (move_uploaded_file($tmp_name, $galleryPath)) {
                            $gallery_images[] = $galleryName;
                        }
                    }
                }
            }
            
            if ($product_id > 0) {
                // Get current product data for comparison
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $current_product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update existing product
                $updateFields = "title = ?, short_desc = ?, full_desc = ?, price = ?, drive_link = ?, tags = ?, demo_url = ?, updated_at = NOW()";
                $params = [$title, $short_desc, $full_desc, $price, $drive_link, $tags, $demo_url];
                
                if ($imgName) {
                    $updateFields .= ", preview_image = ?";
                    $params[] = $imgName;
                }
                
                if ($docName) {
                    $updateFields .= ", doc_file = ?";
                    $params[] = $docName;
                }
                
                if (!empty($gallery_images)) {
                    $updateFields .= ", gallery_images = ?";
                    $params[] = json_encode($gallery_images);
                }
                
                $params[] = $product_id;
                
                $stmt = $pdo->prepare("UPDATE products SET $updateFields WHERE id = ?");
                $stmt->execute($params);
                
                // Determine update type and send notifications to users
                $update_type = 'product_updated';
                
                // Check if download link was added
                if (empty($current_product['drive_link']) && !empty($drive_link)) {
                    $update_type = 'link_update';
                    $productNotificationHelper->notifyLinkUpdate($product_id, 'download', $drive_link);
                }
                
                // Check if documentation file was added
                if (empty($current_product['doc_file']) && !empty($docName)) {
                    $productNotificationHelper->notifyFileUpdate($product_id, 'documentation', $docName);
                }
                
                // Check if significant changes were made
                if ($current_product['title'] !== $title || $current_product['full_desc'] !== $full_desc) {
                    $productNotificationHelper->notifyProductUpdate($product_id, 'product_improved', [
                        'type' => 'product_improved',
                        'old_title' => $current_product['title'],
                        'new_title' => $title,
                        'message' => "Product '{$title}' has been improved with new features and updates."
                    ]);
                }
                
                // General product update notification
                $productNotificationHelper->notifyProductUpdate($product_id, 'product_updated', [
                    'type' => 'product_updated',
                    'message' => "Product '{$title}' has been updated with new features and improvements."
                ]);
                
                $success_message = 'Product updated successfully! Users will be notified of the changes.';
            } else {
                // Create new product
                $stmt = $pdo->prepare("
                    INSERT INTO products (title, short_desc, full_desc, price, drive_link, preview_image, doc_file, tags, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $short_desc, $full_desc, $price, $drive_link, $imgName, $docName, $tags]);
                $product_id = $pdo->lastInsertId();
                $success_message = 'Product created successfully!';
            }
        } catch (Exception $e) {
            $error_message = 'Error saving product: ' . $e->getMessage();
        }
    }
}

// Helper function to sanitize filename
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}

// Get product for editing
$product = null;
$edit_mode = false;
if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $edit_mode = true;
}

// Get all products for listing
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Management - Admin</title>
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
  <?php include 'includes/sidebar.php'; ?>
        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
          <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex">
              <i class="fas fa-check-circle mt-1 mr-3"></i>
              <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex">
              <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
              <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Product Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
              <?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?>
            </h2>
          </div>
          <div class="p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
              <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? 0; ?>">
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Product Title *</label>
                  <input type="text" id="title" name="title" required
                         value="<?php echo htmlspecialchars($product['title'] ?? ''); ?>"
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Product Pricing</label>
                  <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                      <input type="radio" id="paid_product" name="product_type" value="paid" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" <?php echo (($product['price'] ?? 0) > 0) ? 'checked' : ''; ?>>
                      <label for="paid_product" class="text-sm font-medium text-gray-700">Paid Product</label>
                    </div>
                    <div class="flex items-center space-x-3">
                      <input type="radio" id="free_product" name="product_type" value="free" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" <?php echo (($product['price'] ?? 0) == 0) ? 'checked' : ''; ?>>
                      <label for="free_product" class="text-sm font-medium text-gray-700">Free Product</label>
                    </div>
                  </div>
                </div>
              </div>
              
              <div id="price_field">
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (GHS) *</label>
                <input type="number" id="price" name="price" step="0.01" 
                       value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" <?php echo (($product['price'] ?? 0) > 0) ? 'required' : ''; ?>>
              </div>
              
              <div>
                <label for="short_desc" class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                <textarea id="short_desc" name="short_desc" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Brief description of the product..."><?php echo htmlspecialchars($product['short_desc'] ?? ''); ?></textarea>
              </div>
              
              <div>
                <label for="full_desc" class="block text-sm font-medium text-gray-700 mb-2">Full Description</label>
                <textarea id="full_desc" name="full_desc" rows="6"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Detailed description of the product..."><?php echo htmlspecialchars($product['full_desc'] ?? ''); ?></textarea>
              </div>
              
                             <div>
                 <label for="drive_link" class="block text-sm font-medium text-gray-700 mb-2">Google Drive Link *</label>
                 <input type="url" id="drive_link" name="drive_link" required
                        value="<?php echo htmlspecialchars($product['drive_link'] ?? ''); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="https://drive.google.com/...">
                 <p class="text-xs text-gray-500 mt-1">Link to download the product file</p>
               </div>
               
               <div>
                 <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tech Stack Tags</label>
                 <input type="text" id="tags" name="tags" 
                        value="<?php echo htmlspecialchars($product['tags'] ?? ''); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., HTML, CSS, JavaScript, PHP, MySQL">
                 <p class="text-xs text-gray-500 mt-1">Enter tech stack tags separated by commas (e.g., HTML, CSS, JavaScript)</p>
               </div>
              
              <div>
                <label for="preview_image" class="block text-sm font-medium text-gray-700 mb-2">Preview Image</label>
                <?php if (!empty($product['preview_image'])): ?>
                  <div class="mb-2">
                    <img src="../assets/images/products/<?php echo htmlspecialchars($product['preview_image']); ?>" 
                         alt="Current preview" class="w-32 h-32 object-cover rounded border">
                    <p class="text-xs text-gray-500 mt-1">Current image: <?php echo htmlspecialchars($product['preview_image']); ?></p>
                  </div>
                <?php endif; ?>
                <input type="file" id="preview_image" name="preview_image" accept="image/png,image/jpeg,image/webp"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Accepted formats: PNG, JPEG, WebP. Maximum size: 200KB</p>
              </div>
              
              <div>
                <label for="gallery_images" class="block text-sm font-medium text-gray-700 mb-2">Gallery Images (optional, max 5 images)</label>
                <?php 
                $current_gallery = [];
                if (!empty($product['gallery_images'])) {
                    $current_gallery = json_decode($product['gallery_images'], true) ?: [];
                }
                if (!empty($current_gallery)): ?>
                  <div class="mb-2">
                    <p class="text-xs text-gray-500 mb-2">Current gallery images:</p>
                    <div class="flex flex-wrap gap-2">
                      <?php foreach ($current_gallery as $gallery_img): ?>
                        <img src="../assets/images/products/<?php echo htmlspecialchars($gallery_img); ?>" 
                             alt="Gallery image" class="w-16 h-16 object-cover rounded border">
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
                <input type="file" id="gallery_images" name="gallery_images[]" accept="image/png,image/jpeg,image/webp" multiple
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Upload 3-5 screenshots for product gallery. Accepted formats: PNG, JPEG, WebP. Maximum size: 200KB per image</p>
              </div>
              
              <div>
                <label for="demo_url" class="block text-sm font-medium text-gray-700 mb-2">Demo URL (optional)</label>
                <input type="url" id="demo_url" name="demo_url" 
                       value="<?php echo htmlspecialchars($product['demo_url'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="https://demo.example.com">
                <p class="text-xs text-gray-500 mt-1">Live demo link for the product (optional)</p>
              </div>
              
              <div>
                <label for="doc_file" class="block text-sm font-medium text-gray-700 mb-2">Documentation File (optional)</label>
                <?php if (!empty($product['doc_file'])): ?>
                  <div class="mb-2">
                    <p class="text-xs text-gray-500">Current file: <?php echo htmlspecialchars($product['doc_file']); ?></p>
                  </div>
                <?php endif; ?>
                <input type="file" id="doc_file" name="doc_file" accept="application/pdf"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">PDF format only. Maximum size: 5MB</p>
              </div>
              
              <div class="flex space-x-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                  <i class="fas fa-save mr-2"></i>
                  <?php echo $edit_mode ? 'Update Product' : 'Create Product'; ?>
                </button>
                
                <?php if ($edit_mode): ?>
                  <a href="../dashboard/products" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Product
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- Products List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">All Products</h2>
          </div>
          <div class="p-6">
            <?php if (empty($products)): ?>
              <div class="text-center py-8">
                <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">No products found</p>
                <p class="text-sm text-gray-500">Create your first product to get started.</p>
              </div>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-gray-200">
                      <th class="text-left py-3 px-4 font-medium text-gray-700">Product</th>
                      <th class="text-left py-3 px-4 font-medium text-gray-700">Price</th>
                      <th class="text-left py-3 px-4 font-medium text-gray-700">Created</th>
                      <th class="text-left py-3 px-4 font-medium text-gray-700">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($products as $prod): ?>
                      <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-4">
                          <div>
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($prod['title']); ?></div>
                            <?php if ($prod['short_desc']): ?>
                              <div class="text-sm text-gray-500"><?php echo htmlspecialchars($prod['short_desc']); ?></div>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td class="py-4 px-4">
                          <?php if ($prod['price'] > 0): ?>
                            <span class="text-green-600 font-semibold">GHS <?php echo number_format($prod['price'], 2); ?></span>
                          <?php else: ?>
                            <span class="text-blue-600 font-semibold">FREE</span>
                          <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-500">
                          <?php echo date('M j, Y', strtotime($prod['created_at'])); ?>
                        </td>
                        <td class="py-4 px-4">
                          <div class="flex space-x-2">
                            <a href="../dashboard/edit-product?id=<?php echo $prod['id']; ?>" 
                               class="text-blue-600 hover:text-blue-800">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a href="../product.php?id=<?php echo $prod['id']; ?>" 
                               target="_blank"
                               class="text-green-600 hover:text-green-800">
                              <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="deleteProduct(<?php echo $prod['id']; ?>)" 
                                    class="text-red-600 hover:text-red-800">
                              <i class="fas fa-trash"></i>
                            </button>
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
      
      <script>
        function deleteProduct(productId) {
          if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            // Here you would typically send an AJAX request to delete the product
            alert('Delete functionality would be implemented here.');
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
      
      <?php include 'includes/footer.php'; ?>
