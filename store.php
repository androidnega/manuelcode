<?php 
session_start();
include 'includes/db.php';
include 'includes/util.php';
include 'includes/product_functions.php';
include 'includes/store_functions.php';
include 'includes/meta_helper.php';

// Get site URL for absolute image paths
$site_url = get_config('site_url', 'https://manuelcode.info');
$base_url = rtrim($site_url, '/');

// Set page-specific meta data
setQuickMeta(
    'Digital Store | Software Products & Development Resources - ManuelCode',
    'Browse our collection of premium digital products, software solutions, development resources, and professional tools. Quality digital products for developers and businesses.',
    'assets/favi/favicon.png',
    'digital store, software products, development resources, digital downloads, premium tools, professional software'
);

include 'includes/header.php';

// Debug mode - add ?debug=1 to URL to see purchase detection info
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';

// Fetch products from database
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
    error_log("Error fetching products: " . $e->getMessage());
}

// Get user's purchased products if logged in (including guest purchases by email)
$user_purchases = [];
$purchased_product_ids = [];
if (isset($_SESSION['user_id'])) {
    $user_email = $_SESSION['user_email'] ?? null;
    $user_purchases = getAllPurchasedProducts($_SESSION['user_id'], $user_email);
    // Create a simple array of purchased product IDs for quick lookup
    $purchased_product_ids = array_column($user_purchases, 'product_id');
    
    // Also check directly in database for debugging
    if ($debug_mode) {
        // Direct check for user purchases
        $stmt = $pdo->prepare("SELECT product_id FROM purchases WHERE user_id = ? AND status = 'paid'");
        $stmt->execute([$_SESSION['user_id']]);
        $direct_purchases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Direct check for guest purchases by email
        if ($user_email) {
            $stmt = $pdo->prepare("SELECT product_id FROM guest_orders WHERE email = ? AND status = 'paid'");
            $stmt->execute([$user_email]);
            $guest_purchases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $direct_purchases = array_merge($direct_purchases, $guest_purchases);
        }
        
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";
        echo "<strong>Debug Info:</strong><br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "User Email: " . ($user_email ?? 'Not set') . "<br>";
        echo "Function Purchases: " . implode(', ', $purchased_product_ids) . "<br>";
        echo "Direct DB Purchases: " . implode(', ', $direct_purchases) . "<br>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store - ManuelCode</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#536895',
                        secondary: '#F5A623'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-8 sm:py-12">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold">Digital Store</h1>
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="max-w-6xl mx-auto px-4 py-12" id="products-grid">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Available Products</h2>
            <p class="text-gray-600 text-lg">Choose from our collection of high-quality digital products</p>
        </div>

        <!-- Coupon Section -->
        <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
            <div class="text-center mb-4">
                <h3 class="text-xl font-semibold text-blue-900 mb-2">
                    <i class="fas fa-tag mr-2"></i>Have a Coupon?
                </h3>
                <p class="text-blue-700 text-sm">Apply your coupon code to get discounts or make products FREE!</p>
            </div>
            
            <div class="max-w-md mx-auto">
                <div class="flex space-x-2">
                    <input type="text" 
                           id="store-coupon-code" 
                           placeholder="Enter coupon code (e.g., SAVE50, FREE100)" 
                           class="flex-1 px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button onclick="applyStoreCoupon()" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-check mr-2"></i>Apply
                    </button>
                </div>
                
                <!-- Coupon Result Messages -->
                <div id="store-coupon-success" class="hidden mt-3 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span id="store-coupon-success-message"></span>
                </div>
                
                <div id="store-coupon-error" class="hidden mt-3 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="store-coupon-error-message"></span>
                </div>
                
                <!-- Applied Coupon Display -->
                <div id="store-applied-coupon" class="hidden mt-3 p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg text-sm">
                    <div class="flex items-center justify-between">
                        <span>
                            <i class="fas fa-tag mr-2"></i>
                            <strong>Applied:</strong> <span id="store-coupon-display"></span>
                        </span>
                        <button onclick="removeStoreCoupon()" 
                                class="text-blue-600 hover:text-blue-800 text-xs underline">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8" id="products-grid">
      <?php if (empty($products)): ?>
        <div class="col-span-full text-center py-12">
          <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
          <p class="text-gray-500 text-lg">No products available at the moment.</p>
          <p class="text-gray-400 text-sm">Please check back later for new products.</p>
        </div>
      <?php else: ?>
        <?php foreach ($products as $product): ?>
          <?php 
          // FIXED: Use enhanced functions to properly check purchase status
          $is_purchased = isset($_SESSION['user_id']) && isProductPurchasedByUser($_SESSION['user_id'], $product['id'], $_SESSION['user_email'] ?? null);
          $download_link = null;
          $purchase_type = null;
          
          if ($is_purchased) {
              $download_link = getProductDownloadLinkForUser($_SESSION['user_id'], $product['id'], $_SESSION['user_email'] ?? null);
              $purchase_type = getPurchaseType($_SESSION['user_id'], $product['id'], $_SESSION['user_email'] ?? null);
          }
          
          // Debug information for each product
          if ($debug_mode) {
              echo "<div style='background: #e0e0e0; padding: 5px; margin: 5px 0; border: 1px solid #999; font-size: 12px;'>";
              echo "<strong>Product {$product['id']} ({$product['title']}):</strong><br>";
              echo "Is Purchased: " . ($is_purchased ? 'Yes' : 'No') . "<br>";
              echo "Purchase Type: " . ($purchase_type ?? 'None') . "<br>";
              echo "Download Link: " . ($download_link ?? 'None') . "<br>";
              echo "</div>";
          }
          ?>
          <div class="product-card <?php echo ($is_purchased && isset($_SESSION['user_id'])) ? 'bg-green-50 border border-green-200' : 'bg-white border border-gray-200'; ?> rounded-lg overflow-hidden" data-product-id="<?php echo $product['id']; ?>">
            <div class="relative overflow-hidden">
              <?php 
              // Get gallery images for fallback
              $product_gallery = [];
              if (!empty($product['gallery_images'])) {
                  $product_gallery = json_decode($product['gallery_images'], true) ?: [];
              }
              
              // Get preview image URL or fallback to first gallery image
              $preview_image_url = get_product_image_url($product['preview_image'], $base_url);
              if (empty($preview_image_url) && !empty($product_gallery)) {
                  $preview_image_url = get_fallback_gallery_image($product_gallery, $base_url);
              }
              ?>
              <?php if ($preview_image_url): ?>
                <img src="<?php echo htmlspecialchars($preview_image_url); ?>" 
                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                     class="w-full h-24 sm:h-28 object-cover"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="h-24 sm:h-28 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center" style="display: none;">
                  <i class="fas fa-box text-white text-2xl"></i>
                </div>
              <?php else: ?>
                <div class="h-24 sm:h-28 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                  <i class="fas fa-box text-white text-2xl"></i>
                </div>
              <?php endif; ?>
              
              <!-- Purchase Status Badge -->
              <div class="absolute top-2 right-2">
                <?php if ($is_purchased && isset($_SESSION['user_id'])): ?>
                  <?php if ($purchase_type === 'guest'): ?>
                    <span class="bg-blue-500 text-white px-2 py-0.5 rounded text-xs font-medium">
                      <i class="fas fa-user mr-1"></i>Guest
                    </span>
                  <?php else: ?>
                    <span class="bg-green-500 text-white px-2 py-0.5 rounded text-xs font-medium">
                      <i class="fas fa-check mr-1"></i>Owned
                    </span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="bg-[#F5A623] text-white px-2 py-0.5 rounded text-xs font-medium">New</span>
                <?php endif; ?>
              </div>
              
              <!-- Version Badge -->
              <?php if (!empty($product['version'])): ?>
                <div class="absolute top-2 left-2">
                  <span class="bg-blue-500 text-white px-2 py-0.5 rounded text-xs font-medium">
                    v<?php echo htmlspecialchars($product['version']); ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="p-3 sm:p-4">
              <div class="flex items-center justify-between mb-1 sm:mb-2">
                <h3 class="text-sm sm:text-base font-semibold text-gray-900 truncate flex-1"><?php echo htmlspecialchars($product['title']); ?></h3>
                <div class="flex items-center ml-2 flex-shrink-0">
                  <div class="flex text-yellow-400 text-xs">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                  </div>
                  <span class="text-xs text-gray-500 ml-1">(5)</span>
                </div>
              </div>
              
              <p class="text-gray-600 mb-2 text-xs line-clamp-1 hidden sm:block"><?php echo htmlspecialchars($product['short_desc']); ?></p>
              
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                  <?php if ($is_purchased && isset($_SESSION['user_id'])): ?>
                    <span class="text-base sm:text-lg font-semibold text-green-600">Purchased</span>
                  <?php elseif ($product['price'] == 0): ?>
                    <span class="text-base sm:text-lg font-semibold text-blue-600">FREE</span>
                  <?php else: ?>
                    <span class="text-base sm:text-lg font-semibold text-[#536895]">₵<?php echo number_format($product['price'], 2); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="flex flex-col sm:flex-row space-y-1 sm:space-y-0 sm:space-x-2">
                <?php if ($is_purchased && $download_link): ?>
                  <a href="<?php echo $download_link; ?>" 
                     class="flex-1 bg-green-600 text-white py-1.5 px-3 rounded text-sm font-medium text-center">
                    <i class="fas fa-download mr-1"></i>Download
                  </a>
                  <a href="product.php?id=<?php echo $product['id']; ?>" 
                     class="bg-blue-600 text-white py-1.5 px-3 rounded text-sm font-medium text-center">
                    <i class="fas fa-info-circle mr-1"></i>Details
                  </a>
                <?php else: ?>
                  <?php if ($product['price'] == 0): ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <a href="download_free_product.php?id=<?php echo $product['id']; ?>" 
                         class="flex-1 bg-green-600 text-white py-1.5 px-3 rounded text-sm font-medium text-center">
                        <i class="fas fa-download mr-1"></i>Download Free
                      </a>
                    <?php else: ?>
                      <a href="auth/login.php" 
                         class="flex-1 bg-blue-600 text-white py-1.5 px-3 rounded text-sm font-medium text-center">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login
                      </a>
                    <?php endif; ?>
                  <?php else: ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <a href="payment/process_payment.php?id=<?php echo $product['id']; ?>" 
                         class="flex-1 bg-[#536895] text-white py-1.5 px-3 rounded text-sm font-medium text-center">
                        <i class="fas fa-credit-card mr-1"></i>Buy Now
                      </a>
                    <?php else: ?>
                      <a href="guest_purchase.php?id=<?php echo $product['id']; ?>" 
                         class="flex-1 bg-[#536895] text-white py-1.5 px-3 rounded text-sm font-medium text-center">
                        <i class="fas fa-credit-card mr-1"></i>Buy Now
                      </a>
                    <?php endif; ?>
                  <?php endif; ?>
                  <a href="product.php?id=<?php echo $product['id']; ?>" 
                     class="bg-gray-100 text-gray-700 p-1.5 rounded text-center">
                    <i class="fas fa-eye text-sm"></i>
                  </a>
                <?php endif; ?>
              </div>
              
              <!-- Update Notification for Purchased Products -->
              <?php if ($is_purchased && isset($_SESSION['user_id'])): ?>
                <?php 
                $updates = getProductUpdates($product['id'], 1);
                $unread_updates = getUserUnreadUpdates($_SESSION['user_id']);
                $has_unread = false;
                foreach ($unread_updates as $update) {
                    if ($update['product_id'] == $product['id']) {
                        $has_unread = true;
                        break;
                    }
                }
                ?>
                <?php if ($has_unread): ?>
                  <div class="mt-2 p-1.5 bg-yellow-50 border border-yellow-200 rounded text-xs">
                    <i class="fas fa-bell text-yellow-600 mr-1"></i>
                    <span class="text-yellow-800">New update</span>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>
  
  <script>
  // Store Coupon Functionality
  let appliedStoreCoupon = null;
  
  function applyStoreCoupon() {
      const couponCode = document.getElementById('store-coupon-code').value.trim();
      if (!couponCode) {
          showStoreCouponError('Please enter a coupon code');
          return;
      }
      
      const applyBtn = document.querySelector('button[onclick="applyStoreCoupon()"]');
      const originalText = applyBtn.innerHTML;
      applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Checking...';
      applyBtn.disabled = true;
      
      // Validate coupon via AJAX
      fetch('validate_coupon.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({ coupon_code: couponCode })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              appliedStoreCoupon = {
                  code: couponCode,
                  discount_type: data.discount_type,
                  discount_value: data.discount_value
              };
              
              // Store coupon in session storage (client-side)
              sessionStorage.setItem('store_applied_coupon', JSON.stringify(appliedStoreCoupon));
              
              // Also store in server session for persistence across pages
              fetch('store_coupon_in_session.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                  },
                  body: JSON.stringify(appliedStoreCoupon)
              })
              .then(response => response.json())
              .then(sessionData => {
                  if (sessionData.success) {
                      console.log('Coupon stored in server session');
                  }
              })
              .catch(error => {
                  console.error('Error storing coupon in session:', error);
              });
              
              showStoreCouponSuccess(data.message, data.discount_info);
              updateStoreProductPrices();
          } else {
              showStoreCouponError(data.message);
              sessionStorage.removeItem('store_applied_coupon');
          }
      })
      .catch(error => {
          console.error('Coupon validation error:', error);
          showStoreCouponError('Error validating coupon. Please try again.');
          sessionStorage.removeItem('store_applied_coupon');
      })
      .finally(() => {
          // Reset button state
          applyBtn.innerHTML = originalText;
          applyBtn.disabled = false;
      });
  }
  
  function removeStoreCoupon() {
      appliedStoreCoupon = null;
      sessionStorage.removeItem('store_applied_coupon');
      
      // Also remove from server session
      fetch('remove_coupon_from_session.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          }
      })
      .then(response => response.json())
      .then(data => {
          console.log('Coupon removed from server session');
      })
      .catch(error => {
          console.error('Error removing coupon from session:', error);
      });
      
      hideStoreCouponMessages();
      resetStoreProductPrices();
  }
  
  function showStoreCouponSuccess(message, discountInfo) {
      document.getElementById('store-coupon-success-message').textContent = message;
      document.getElementById('store-coupon-success').classList.remove('hidden');
      document.getElementById('store-coupon-error').classList.add('hidden');
      
      // Show applied coupon display
      document.getElementById('store-coupon-display').textContent = `${appliedStoreCoupon.code} (${discountInfo})`;
      document.getElementById('store-applied-coupon').classList.remove('hidden');
      
      // Clear input
      document.getElementById('store-coupon-code').value = '';
  }
  
  function showStoreCouponError(message) {
      document.getElementById('store-coupon-error-message').textContent = message;
      document.getElementById('store-coupon-error').classList.remove('hidden');
      document.getElementById('store-coupon-success').classList.add('hidden');
      document.getElementById('store-applied-coupon').classList.add('hidden');
  }
  
  function hideStoreCouponMessages() {
      document.getElementById('store-coupon-success').classList.add('hidden');
      document.getElementById('store-coupon-error').classList.add('hidden');
      document.getElementById('store-applied-coupon').classList.add('hidden');
  }
  
  function updateStoreProductPrices() {
      if (!appliedStoreCoupon) return;
      
      const products = document.querySelectorAll('.product-card');
      products.forEach(product => {
          const priceElement = product.querySelector('.text-\\[\\#536895\\], .text-blue-600');
          if (priceElement && !priceElement.textContent.includes('FREE') && !priceElement.textContent.includes('Purchased')) {
              const originalPrice = parseFloat(priceElement.textContent.replace('₵', '').replace(',', ''));
              if (!isNaN(originalPrice)) {
                  let discount = 0;
                  if (appliedStoreCoupon.discount_type === 'percentage') {
                      discount = (originalPrice * appliedStoreCoupon.discount_value) / 100;
                  } else {
                      discount = appliedStoreCoupon.discount_value;
                  }
                  
                  const finalPrice = originalPrice - discount;
                  
                  if (finalPrice <= 0) {
                      // Product becomes FREE
                      priceElement.textContent = 'FREE';
                      priceElement.className = 'text-xl sm:text-2xl font-bold text-green-600';
                      
                      // Update button to download
                      const buyButton = product.querySelector('a[href*="process_payment"], a[href*="guest_purchase"]');
                      if (buyButton) {
                          buyButton.innerHTML = '<i class="fas fa-download mr-2"></i>Download Free';
                          buyButton.className = 'flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium transition-colors text-center';
                          
                          // Update href based on user status
                          const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                          if (isLoggedIn) {
                              buyButton.href = `download_free_product_with_coupon.php?id=${product.dataset.productId}&coupon=${encodeURIComponent(appliedStoreCoupon.code)}`;
                          } else {
                              buyButton.href = `download_free_product_guest_coupon.php?id=${product.dataset.productId}&coupon=${encodeURIComponent(appliedStoreCoupon.code)}`;
                          }
                      }
                  } else {
                      // Product has discount
                      priceElement.innerHTML = `<span class="line-through text-gray-400 text-lg">₵${originalPrice.toFixed(2)}</span> <span class="text-green-600">₵${finalPrice.toFixed(2)}</span>`;
                  }
              }
          }
      });
  }
  
  function resetStoreProductPrices() {
      // Reload page to reset all prices
      window.location.reload();
  }
  
  // Check for existing coupon on page load
  document.addEventListener('DOMContentLoaded', function() {
      
      const storedCoupon = sessionStorage.getItem('store_applied_coupon');
      if (storedCoupon) {
          appliedStoreCoupon = JSON.parse(storedCoupon);
          // Re-apply coupon to update prices
          updateStoreProductPrices();
          
          // Show applied coupon display
          const discountInfo = appliedStoreCoupon.discount_type === 'percentage' 
              ? `${appliedStoreCoupon.discount_value}% off` 
              : `₵${appliedStoreCoupon.discount_value} off`;
          
          document.getElementById('store-coupon-display').textContent = `${appliedStoreCoupon.code} (${discountInfo})`;
          document.getElementById('store-applied-coupon').classList.remove('hidden');
      }
  });
  </script>
</body>
</html>