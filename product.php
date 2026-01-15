<?php 
session_start();
include 'includes/db.php';
include 'includes/config.php';
include 'includes/util.php';
include 'includes/product_functions.php';
include 'includes/meta_helper.php';

// Get site URL for absolute image paths
$site_url = get_config('site_url', 'https://manuelcode.info');
$base_url = rtrim($site_url, '/');

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for applied coupon in session and calculate discounted amount
$original_price = $product['price'];
$final_amount = $original_price;
$discount_amount = 0;
$applied_coupon = null;
$coupon_info = null;

// Check for store-applied coupon (new system)
if (isset($_SESSION['store_applied_coupon'])) {
    $applied_coupon = $_SESSION['store_applied_coupon']['code'];
    $coupon_info = [
        'discount_type' => $_SESSION['store_applied_coupon']['discount_type'],
        'discount_value' => $_SESSION['store_applied_coupon']['discount_value']
    ];
    
    // Calculate the final amount with discount
    if ($coupon_info['discount_type'] === 'percentage') {
        $discount_amount = ($original_price * $coupon_info['discount_value']) / 100;
    } else {
        $discount_amount = $coupon_info['discount_value'];
    }
    
    $final_amount = max(0, $original_price - $discount_amount);
    
    error_log("Product page with store coupon - Original: {$original_price}, Discount: {$discount_amount}, Final: {$final_amount}");
}
// Check for backward compatibility with old system
elseif (isset($_SESSION['applied_coupon']) && isset($_SESSION['coupon_discount_info'])) {
    $applied_coupon = $_SESSION['applied_coupon'];
    $coupon_info = $_SESSION['coupon_discount_info'];
    
    // Calculate the final amount with discount
    if ($coupon_info['discount_type'] === 'percentage') {
        $discount_amount = ($original_price * $coupon_info['discount_value']) / 100;
    } else {
        $discount_amount = $coupon_info['discount_value'];
    }
    
    $final_amount = max(0, $original_price - $discount_amount);
    
    error_log("Product page with legacy coupon - Original: {$original_price}, Discount: {$discount_amount}, Final: {$final_amount}");
}

if (!$product) {
    echo "<section class='max-w-6xl mx-auto px-4 py-12'><p class='text-red-500'>Product not found.</p></section>";
    include 'includes/footer.php';
    exit;
}

// Set page-specific meta data based on product
$product_title = htmlspecialchars($product['title']);
$product_description = htmlspecialchars(substr($product['description'], 0, 160)) . '...';
$product_image = $product['preview_image'] ?: 'assets/favi/favicon.png';

setQuickMeta(
    $product_title . ' | ManuelCode',
    $product_description,
    $product_image,
    'software, development, ' . strtolower($product_title) . ', digital product, code, solution'
);

// Check if user has already purchased this product
$has_purchased = false;
$download_link = null;
$purchase_type = '';

if (isset($_SESSION['user_id'])) {
    // Check regular user purchases
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE user_id = ? AND product_id = ? AND status = 'paid'");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $user_purchase = $stmt->fetch();
    
    if ($user_purchase) {
        $has_purchased = true;
        $purchase_type = 'user';
        $download_link = getProductDownloadLink($_SESSION['user_id'], $id);
    } else {
        // Check guest purchases by email
        $user_email = $_SESSION['user_email'] ?? null;
        if ($user_email) {
            $stmt = $pdo->prepare("SELECT * FROM guest_orders WHERE email = ? AND product_id = ? AND status = 'paid'");
            $stmt->execute([$user_email, $id]);
            $guest_purchase = $stmt->fetch();
            if ($guest_purchase) {
                $has_purchased = true;
                $purchase_type = 'guest';
                $download_link = getGuestDownloadLink($user_email, $id);
            }
        }
    }
    
    // Debug information (remove in production)
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";
        echo "<strong>Debug Info:</strong><br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "Product ID: " . $id . "<br>";
        echo "Has Purchased: " . ($has_purchased ? 'Yes' : 'No') . "<br>";
        echo "Purchase Type: " . $purchase_type . "<br>";
        echo "Download Link: " . ($download_link ?? 'None') . "<br>";
        echo "</div>";
    }
}

// Function to check if product should be free (either original price is 0 or 100% discount applied)
function isProductFree($product_price, $applied_coupon = null) {
    if ($product_price == 0) {
        return true;
    }
    
    if ($applied_coupon) {
        if ($applied_coupon['discount_type'] === 'percentage' && $applied_coupon['discount_value'] >= 100) {
            return true;
        } elseif ($applied_coupon['discount_type'] === 'fixed' && $applied_coupon['discount_value'] >= $product_price) {
            return true;
        }
    }
    
    return false;
}

// Function to calculate final price with coupon
function calculateFinalPrice($product_price, $applied_coupon = null) {
    if (!$applied_coupon) {
        return $product_price;
    }
    
    $discount = 0;
    if ($applied_coupon['discount_type'] === 'percentage') {
        $discount = ($product_price * $applied_coupon['discount_value']) / 100;
    } else {
        $discount = $applied_coupon['discount_value'];
    }
    
    $final_price = $product_price - $discount;
    return max(0, $final_price); // Ensure price doesn't go negative
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - ManuelCode</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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

    <section class="max-w-6xl mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Product Image -->
                <div class="p-6">
                    <?php 
                    // Get gallery images for fallback
                    $gallery_images = [];
                    if (!empty($product['gallery_images'])) {
                        $gallery_images = json_decode($product['gallery_images'], true) ?: [];
                    }
                    
                    // Get preview image URL or fallback to first gallery image
                    $preview_image_url = get_product_image_url($product['preview_image'], $base_url);
                    if (empty($preview_image_url) && !empty($gallery_images)) {
                        $preview_image_url = get_fallback_gallery_image($gallery_images, $base_url);
                    }
                    ?>
                    <?php if ($preview_image_url): ?>
                        <img src="<?php echo htmlspecialchars($preview_image_url); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>" 
                             class="w-full h-96 object-cover rounded-lg cursor-pointer hover:scale-105 transition-transform duration-300"
                             onclick="openImageModal('<?php echo htmlspecialchars($preview_image_url); ?>')"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-96 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center" style="display: none;">
                            <i class="fas fa-box text-white text-6xl"></i>
                        </div>
                    <?php else: ?>
                        <div class="w-full h-96 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-box text-white text-6xl"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div class="p-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="text-gray-500 ml-2">(5.0)</span>
                    </div>
                    
      <!-- Purchase Status Display -->
      <?php if (isset($_SESSION['user_id']) && $has_purchased): ?>
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-600 mr-2"></i>
              <?php if ($product['price'] > 0): ?>
                <span class="text-green-800 font-medium">Purchased</span>
              <?php else: ?>
                <span class="text-green-800 font-medium">Downloaded</span>
              <?php endif; ?>
            </div>
                            <?php if ($product['price'] > 0): ?>
                  <span class="text-green-600 text-sm">GHS <?php echo number_format($product['price'], 2); ?></span>
                <?php else: ?>
                  <span class="text-blue-600 text-sm font-semibold">FREE</span>
                <?php endif; ?>
          </div>
        </div>
      <?php elseif ($product['price'] == 0): ?>
        <!-- Free Product Display -->
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <i class="fas fa-gift text-blue-600 mr-2"></i>
              <span class="text-blue-800 font-medium">Free Product</span>
            </div>
            <span class="text-blue-600 text-sm font-semibold">FREE</span>
          </div>
        </div>
      <?php else: ?>
        <div class="mb-4">
          <?php if ($applied_coupon && $discount_amount > 0): ?>
            <!-- Show discounted price -->
            <div class="flex items-center space-x-3 mb-2">
              <p class="text-lg text-gray-500 line-through">GHS <?php echo number_format($product['price'], 2); ?></p>
              <span class="text-green-600 text-sm font-medium bg-green-100 px-2 py-1 rounded">
                -₵<?php echo number_format($discount_amount, 2); ?>
              </span>
            </div>
            <p class="text-2xl font-bold text-[#F5A623] mb-2">
              <?php if ($final_amount > 0): ?>
                GHS <?php echo number_format($final_amount, 2); ?>
              <?php else: ?>
                <span class="text-green-600">FREE</span>
              <?php endif; ?>
            </p>
            <p class="text-sm text-gray-600 mb-4">
              <i class="fas fa-tag mr-1"></i>
              Coupon applied: <?php echo htmlspecialchars($applied_coupon); ?>
            </p>
          <?php else: ?>
            <!-- Show original price -->
            <p class="text-2xl font-bold text-[#F5A623] mb-4">GHS <?php echo number_format($product['price'], 2); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
             <?php if (isset($_SESSION['user_id'])): ?>
         <?php if ($has_purchased): ?>
           <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
             <i class="fas fa-check-circle mr-2"></i>
             <?php if ($product['price'] > 0): ?>
               You have already purchased this product!
             <?php else: ?>
               You have already downloaded this free product!
             <?php endif; ?>
           </div>
                        <div class="mt-4 space-y-3">
             <a href="<?php echo $download_link; ?>" class="inline-block bg-[#4CAF50] text-white px-6 py-3 rounded hover:bg-[#45a049] transition-colors">
               <i class="fas fa-download mr-2"></i>Download Now
             </a>
             <a href="dashboard/my-purchases" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition-colors ml-3">
               <i class="fas fa-list mr-2"></i><?php echo ($product['price'] > 0) ? 'View All Purchases' : 'View My Downloads'; ?>
             </a>
           </div>
         <?php elseif ($product['price'] == 0): ?>
           <!-- Free Product Download -->
           <div class="mt-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
             <i class="fas fa-gift mr-2"></i>
             This is a free product! You can download it directly.
           </div>
           <div class="mt-4 space-y-3">
             <a href="download_free_product.php?id=<?php echo $product['id']; ?>" class="inline-block bg-[#4CAF50] text-white px-6 py-3 rounded hover:bg-[#45a049] transition-colors">
               <i class="fas fa-download mr-2"></i>Download Free Product
             </a>
             <a href="dashboard/my-purchases" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition-colors ml-3">
               <i class="fas fa-list mr-2"></i>View My Downloads
             </a>
           </div>
         <?php else: ?>
           <!-- Coupon Section -->
           <div class="mt-4 p-4 bg-gray-50 rounded-lg">
             <h3 class="text-lg font-semibold text-gray-800 mb-3">Have a Coupon?</h3>
             <div class="flex space-x-2">
               <input type="text" id="coupon-code" placeholder="Enter coupon code" 
                      class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
               <button id="apply-coupon" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                 Apply
               </button>
             </div>
             
             <!-- Coupon Result Messages -->
             <div id="coupon-result" class="hidden mt-3">
               <div id="coupon-success" class="hidden p-2 bg-green-100 border border-green-400 text-green-700 rounded text-sm">
                 <i class="fas fa-check-circle mr-1"></i>
                 <span id="success-message"></span>
               </div>
               <div id="coupon-error" class="hidden p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                 <i class="fas fa-exclamation-circle mr-1"></i>
                 <span id="error-message"></span>
               </div>
             </div>
             
             <!-- Price Display with Coupon -->
             <div class="bg-white p-3 rounded border">
               <div class="flex justify-between items-center">
                 <span class="text-gray-600">Original Price:</span>
                 <?php if ($product['price'] > 0): ?>
                  <span class="font-semibold">GHS <?php echo number_format($product['price'], 2); ?></span>
                <?php else: ?>
                  <span class="font-semibold text-blue-600">FREE</span>
                <?php endif; ?>
               </div>
               
               <div id="coupon-discount-display" class="hidden">
                 <div class="flex justify-between items-center text-green-600 text-sm">
                   <span>Coupon Discount:</span>
                   <span id="discount-amount">-₵0.00</span>
                 </div>
               </div>
               
               <div class="border-t border-gray-200 mt-2 pt-2">
                 <div class="flex justify-between items-center font-bold">
                   <span>Total:</span>
                   <?php if ($product['price'] > 0): ?>
                  <span class="text-[#F5A623]" id="total-amount">GHS <?php echo number_format($product['price'], 2); ?></span>
                <?php else: ?>
                  <span class="text-blue-600 font-bold" id="total-amount">FREE</span>
                <?php endif; ?>
                 </div>
               </div>
             </div>
           </div>
           
           <!-- Payment Button - Will be updated by JavaScript based on final price -->
           <button id="paystackButton" 
                   class="mt-4 bg-[#2D3E50] text-white px-6 py-3 rounded hover:bg-[#243646] transition-colors w-full"
                   data-product-id="<?php echo $product['id']; ?>"
                   data-is-guest="false"
                   data-original-price="<?php echo $product['price']; ?>"
                   data-final-price="<?php echo $final_amount; ?>"
                   data-coupon-code="<?php echo $applied_coupon ? htmlspecialchars($applied_coupon) : ''; ?>">
             <?php if ($final_amount > 0): ?>
               <i class="fas fa-credit-card mr-2"></i>Buy Now
             <?php else: ?>
               <i class="fas fa-download mr-2"></i>Download Free Product
             <?php endif; ?>
           </button>
         <?php endif; ?>
       <?php else: ?>
         <?php if ($product['price'] == 0): ?>
           <!-- Free Product for Guests - Require Login -->
           <div class="mt-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded">
             <i class="fas fa-info-circle mr-2"></i>
             This is a free product! Please <a href="login" class="underline font-semibold">login</a> to download it.
           </div>
           <div class="mt-4 space-y-3">
             <a href="login" class="block w-full bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition-colors text-center">
               <i class="fas fa-sign-in-alt mr-2"></i>Login to Download
             </a>
             <div class="text-center">
               <span class="text-gray-500 text-sm">or</span>
             </div>
             <a href="auth/register.php" class="block w-full bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 transition-colors text-center">
               <i class="fas fa-user-plus mr-2"></i>Create Account
             </a>
           </div>
         <?php else: ?>
           <!-- Paid Product for Guests -->
           <div class="mt-4 space-y-3">
             <div class="p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
               <i class="fas fa-info-circle mr-2"></i>
               Please <a href="login" class="underline">login</a> to purchase this product.
             </div>
             <div class="text-center">
               <span class="text-gray-500 text-sm">or</span>
             </div>
             <a href="guest_purchase.php?id=<?php echo $product['id']; ?>" 
                class="block w-full bg-[#F5A623] text-white px-6 py-3 rounded hover:bg-[#d88c1b] transition-colors text-center">
               <i class="fas fa-shopping-cart mr-2"></i>Buy as Guest
             </a>
             
             <!-- Coupon Information for Guests -->
             <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
               <div class="flex items-center">
                 <i class="fas fa-ticket-alt text-green-600 mr-2"></i>
                 <div>
                   <h4 class="font-semibold text-green-800">Have a Coupon?</h4>
                   <p class="text-sm text-green-700 mt-1">
                     Use the "Buy as Guest" button above to access coupon functionality and get discounts!
                   </p>
                 </div>
               </div>
             </div>
           </div>
         <?php endif; ?>
       <?php endif; ?>
    </div>
  </div>
</section>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center">
  <div class="relative max-w-4xl max-h-full p-4">
    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
      <i class="fas fa-times"></i>
    </button>
    <img id="modalImage" src="" alt="Product image" class="max-w-full max-h-full object-contain">
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/payment.js"></script>

<script>
// Image Modal functionality
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    modalImage.src = imageSrc;
    modal.classList.remove('hidden');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
}

// Coupon functionality
document.addEventListener('DOMContentLoaded', function() {
    const applyCouponBtn = document.getElementById('apply-coupon');
    const couponCode = document.getElementById('coupon-code');
    const couponSuccess = document.getElementById('coupon-success');
    const couponError = document.getElementById('coupon-error');
    const couponResult = document.getElementById('coupon-result');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const couponDiscountDisplay = document.getElementById('coupon-discount-display');
    const discountAmount = document.getElementById('discount-amount');
    const totalAmount = document.getElementById('total-amount');
    const paystackButton = document.getElementById('paystackButton');
    
    // Check for pre-applied coupon from store page
    <?php if ($applied_coupon && $coupon_info): ?>
    // Pre-applied coupon detected
    const preAppliedCoupon = {
        code: '<?php echo htmlspecialchars($applied_coupon); ?>',
        discount_type: '<?php echo htmlspecialchars($coupon_info['discount_type']); ?>',
        discount_value: <?php echo $coupon_info['discount_value']; ?>
    };
    
    // Show pre-applied coupon message
    showPreAppliedCoupon(preAppliedCoupon);
    console.log('Pre-applied coupon detected:', preAppliedCoupon);
    <?php endif; ?>
    
    // Function to show pre-applied coupon message
    function showPreAppliedCoupon(coupon) {
        if (successMessage && couponResult) {
            successMessage.textContent = `Coupon '${coupon.code}' applied successfully!`;
            couponSuccess.classList.remove('hidden');
            couponError.classList.add('hidden');
            couponResult.classList.remove('hidden');
        }
        
        if (couponDiscountDisplay && discountAmount) {
            const discountValue = coupon.discount_type === 'percentage' 
                ? `${coupon.discount_value}%` 
                : `₵${coupon.discount_value}`;
            
            discountAmount.textContent = `-${discountValue}`;
            couponDiscountDisplay.classList.remove('hidden');
        }
        
        // Update total amount if element exists
        if (totalAmount) {
            const finalPrice = <?php echo $final_amount; ?>;
            if (finalPrice > 0) {
                totalAmount.textContent = `GHS ${finalPrice.toFixed(2)}`;
            } else {
                totalAmount.textContent = 'FREE';
                totalAmount.className = 'text-green-600 font-bold';
            }
        }
        
        // Update payment button for free products
        if (paystackButton && <?php echo $final_amount; ?> <= 0) {
            paystackButton.innerHTML = '<i class="fas fa-download mr-2"></i>Download Free Product';
            paystackButton.className = 'mt-4 bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 transition-colors w-full';
            paystackButton.onclick = function() {
                downloadFreeProductWithCoupon();
            };
        }
        
        // Pre-fill the coupon input if it exists
        if (couponCode) {
            couponCode.value = coupon.code;
        }
    }
    
    // Function to download free product with coupon
    function downloadFreeProductWithCoupon() {
        const couponCode = '<?php echo $applied_coupon ? htmlspecialchars($applied_coupon) : ''; ?>';
        const productId = <?php echo $product['id']; ?>;
        
        if (couponCode) {
            window.location.href = `download_free_product_with_coupon.php?id=${productId}&coupon=${encodeURIComponent(couponCode)}`;
        } else {
            window.location.href = `download_free_product.php?id=${productId}`;
        }
    }
    
    // Debug: Check if paystack button is found
    console.log('Initial button check:');
    console.log('paystackButton found:', !!paystackButton);
    if (paystackButton) {
        console.log('Button ID:', paystackButton.id);
        console.log('Button text:', paystackButton.innerHTML);
        console.log('Button class:', paystackButton.className);
    } else {
        console.log('❌ Paystack button not found! Check if ID is correct.');
        console.log('This usually means:');
        console.log('1. User is not logged in, OR');
        console.log('2. User has already purchased the product, OR');
        console.log('3. Product is free (price = 0)');
        console.log('Current user status:', <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>);
        console.log('Has purchased:', <?php echo $has_purchased ? 'true' : 'false'; ?>);
        console.log('Product price:', <?php echo $product['price']; ?>);
    }
    
    if (applyCouponBtn && couponCode) {
        const originalPrice = <?php echo $product['price']; ?>;
        let currentPrice = originalPrice;
        let appliedCoupon = null;
        
        // Check if paystack button exists before proceeding
        if (!paystackButton) {
            console.log('⚠️  Paystack button not found. Coupon functionality disabled.');
            return;
        }
        
        applyCouponBtn.addEventListener('click', function() {
            const code = couponCode.value.trim();
            if (!code) {
                showCouponError('Please enter a coupon code');
                return;
            }
            
            // Show loading state
            const originalText = applyCouponBtn.innerHTML;
            applyCouponBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Checking...';
            applyCouponBtn.disabled = true;
            
            // Validate coupon via AJAX
            fetch('validate_coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ coupon_code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCouponSuccess(data.message, data.discount_info);
                    appliedCoupon = {
                        code: code,
                        discount_type: data.discount_type,
                        discount_value: data.discount_value
                    };
                    applyCouponToOrder(appliedCoupon);
                    // Store coupon in session storage for payment processing
                    sessionStorage.setItem('applied_coupon', JSON.stringify(appliedCoupon));
                    
                    // Update payment button with coupon data
                    if (paystackButton) {
                        paystackButton.setAttribute('data-coupon', JSON.stringify(appliedCoupon));
                    }
                } else {
                    showCouponError(data.message);
                    sessionStorage.removeItem('applied_coupon');
                }
            })
            .catch(error => {
                console.error('Coupon validation error:', error);
                showCouponError('Error validating coupon. Please try again.');
                sessionStorage.removeItem('applied_coupon');
            })
            .finally(() => {
                // Reset button state
                applyCouponBtn.innerHTML = originalText;
                applyCouponBtn.disabled = false;
            });
        });
        
        function showCouponSuccess(message, discountInfo) {
            successMessage.textContent = message;
            discountInfo.textContent = discountInfo;
            couponSuccess.classList.remove('hidden');
            couponError.classList.add('hidden');
            couponResult.classList.remove('hidden');
        }
        
        function showCouponError(message) {
            errorMessage.textContent = message;
            couponError.classList.remove('hidden');
            couponSuccess.classList.add('hidden');
            couponResult.classList.remove('hidden');
        }
        
        function applyCouponToOrder(coupon) {
            let discount = 0;
            // Convert discount_value to number to ensure it's numeric
            const discountValue = parseFloat(coupon.discount_value);
            
            console.log('Applying coupon:', coupon);
            console.log('Original price:', originalPrice);
            console.log('Discount value:', discountValue);
            console.log('Discount type:', coupon.discount_type);
            
            if (coupon.discount_type === 'percentage') {
                discount = (originalPrice * discountValue) / 100;
            } else {
                discount = discountValue;
            }
            
            currentPrice = originalPrice - discount;
            console.log('Calculated discount:', discount);
            console.log('Final price:', currentPrice);
            
            discountAmount.textContent = `-₵${discount.toFixed(2)}`;
            
            // Check if product becomes free after discount (100% or exact amount)
            if (currentPrice <= 0) {
                console.log('Product is now FREE! Updating button...');
                totalAmount.textContent = 'FREE';
                totalAmount.className = 'text-blue-600 font-bold';
                
                // Update the Paystack button to a download button for free products
                if (paystackButton) {
                    console.log('Found paystack button, updating to download button...');
                    
                    // Store the original button state for restoration
                    if (!paystackButton._originalState) {
                        paystackButton._originalState = {
                            innerHTML: paystackButton.innerHTML,
                            className: paystackButton.className,
                            onclick: paystackButton.onclick
                        };
                    }
                    
                    // Update button appearance and functionality
                    paystackButton.innerHTML = '<i class="fas fa-download mr-2"></i>Download Free Product';
                    paystackButton.className = 'mt-4 bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 transition-colors w-full';
                    
                    // Remove all existing event listeners and add our download handler
                    paystackButton.onclick = null;
                    
                    // Check if user is logged in or guest
                    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                    console.log('User logged in:', isLoggedIn);
                    
                    if (isLoggedIn) {
                        // Logged in user - show download button
                        console.log('Setting up download button for logged-in user...');
                        paystackButton.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('Download button clicked for logged-in user');
                            downloadFreeProductWithCoupon();
                        };
                    } else {
                        // Guest user - show download button
                        console.log('Setting up download button for guest user...');
                        paystackButton.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('Download button clicked for guest user');
                            downloadFreeProductGuestCoupon();
                        };
                    }
                    
                    console.log('Button updated successfully!');
                } else {
                    console.log('Paystack button not found!');
                }
                
                // Show free product message
                const freeProductMessage = document.createElement('div');
                freeProductMessage.id = 'free-product-message';
                freeProductMessage.className = 'mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded text-center';
                
                // Check if user is logged in or guest
                const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                
                if (isLoggedIn) {
                    // Logged in user - direct download
                    freeProductMessage.innerHTML = `
                        <i class="fas fa-gift mr-2"></i>
                        <strong>Congratulations!</strong> This product is now FREE with your coupon!
                        <div class="mt-3">
                            <button onclick="downloadFreeProductWithCoupon()" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Download Now
                            </button>
                        </div>
                    `;
                } else {
                    // Guest user - download with coupon
                    freeProductMessage.innerHTML = `
                        <i class="fas fa-gift mr-2"></i>
                        <strong>Congratulations!</strong> This product is now FREE with your coupon!
                        <div class="mt-3">
                            <button onclick="downloadFreeProductGuestCoupon()" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Download Now
                            </button>
                        </div>
                        <div class="mt-2 text-sm text-green-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            No account required - download immediately!
                        </div>
                    `;
                }
                
                // Remove existing message if any
                const existingMessage = document.getElementById('free-product-message');
                if (existingMessage) {
                    existingMessage.remove();
                }
                
                // Insert the message after the coupon result section
                const couponResult = document.getElementById('coupon-result');
                if (couponResult) {
                    couponResult.parentNode.insertBefore(freeProductMessage, couponResult.nextSibling);
                }
                
            } else {
                totalAmount.textContent = `GHS ${currentPrice.toFixed(2)}`;
                totalAmount.className = 'text-[#F5A623]';
                
                // Restore original Paystack button for paid products
                if (paystackButton && paystackButton._originalState) {
                    console.log('Restoring original button state...');
                    paystackButton.innerHTML = paystackButton._originalState.innerHTML;
                    paystackButton.className = paystackButton._originalState.className;
                    paystackButton.onclick = paystackButton._originalState.onclick;
                    console.log('Button restored to original state');
                }
                
                // Remove free product message if it exists
                const existingMessage = document.getElementById('free-product-message');
                if (existingMessage) {
                    existingMessage.remove();
                }
            }
            
            couponDiscountDisplay.classList.remove('hidden');
        }
        
        // Function to download free product with coupon (for logged-in users)
        window.downloadFreeProductWithCoupon = function() {
            const couponData = sessionStorage.getItem('applied_coupon');
            if (couponData) {
                const coupon = JSON.parse(couponData);
                // Redirect to download with coupon information
                window.location.href = `download_free_product_with_coupon.php?id=<?php echo $product['id']; ?>&coupon=${encodeURIComponent(coupon.code)}`;
            } else {
                // Fallback to regular free download
                window.location.href = 'download_free_product.php?id=<?php echo $product['id']; ?>';
            }
        };
        
        // Function to download free product with coupon (for guests)
        window.downloadFreeProductGuestCoupon = function() {
            const couponData = sessionStorage.getItem('applied_coupon');
            if (couponData) {
                const coupon = JSON.parse(couponData);
                // Redirect to guest download with coupon information
                window.location.href = `download_free_product_guest_coupon.php?id=<?php echo $product['id']; ?>&coupon=${encodeURIComponent(coupon.code)}`;
            } else {
                // Fallback to guest purchase page
                window.location.href = 'guest_purchase.php?id=<?php echo $product['id']; ?>';
            }
        };
    }
    
    // Initialize Paystack payment for logged-in users
    if (paystackButton && <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
        const productId = paystackButton.getAttribute('data-product-id');
        const originalPrice = parseFloat(paystackButton.getAttribute('data-original-price') || 0);
        const finalPrice = parseFloat(paystackButton.getAttribute('data-final-price') || originalPrice);
        
        // Only initialize payment if product is not free
        if (finalPrice > 0) {
            paystackButton.addEventListener('click', function(e) {
                e.preventDefault();
                this.disabled = true;
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                
                // Prepare payment data
                const paymentData = {
                    product_id: parseInt(productId),
                    is_guest: false
                };
                
                // Add coupon data if available
                const couponData = this.getAttribute('data-coupon');
                if (couponData) {
                    try {
                        paymentData.coupon_data = JSON.parse(couponData);
                    } catch (e) {
                        console.error('Error parsing coupon data:', e);
                    }
                } else {
                    // Check sessionStorage
                    const sessionCoupon = sessionStorage.getItem('applied_coupon');
                    if (sessionCoupon) {
                        try {
                            paymentData.coupon_data = JSON.parse(sessionCoupon);
                        } catch (e) {
                            console.error('Error parsing sessionStorage coupon:', e);
                        }
                    }
                }
                
                // Initialize payment
                const apiPath = 'payment/process_payment_api.php';
                console.log('Initiating payment for product ID:', productId);
                console.log('Payment Data:', paymentData);
                
                fetch(apiPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(paymentData)
                })
                .then(async response => {
                    const responseText = await response.text();
                    console.log('Response Status:', response.status);
                    console.log('Response Text:', responseText);
                    
                    if (!response.ok) {
                        let errorMessage = 'Network response was not ok';
                        try {
                            const errorData = JSON.parse(responseText);
                            errorMessage = errorData.message || errorData.error || errorMessage;
                        } catch (e) {
                            errorMessage = `Server error (${response.status}): ${response.statusText}`;
                        }
                        throw new Error(errorMessage);
                    }
                    
                    try {
                        return JSON.parse(responseText);
                    } catch (e) {
                        throw new Error('Invalid response from server');
                    }
                })
                .then(data => {
                    console.log('Payment API Response:', data);
                    if (data.success) {
                        // Redirect to Paystack payment gateway
                        window.location.href = data.authorization_url;
                    } else {
                        throw new Error(data.message || 'Payment initialization failed');
                    }
                })
                .catch(error => {
                    console.error('Payment error:', error);
                    alert('Payment Error: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = originalHTML;
                });
            });
        }
    }
});
</script>
