<?php
session_start();
include 'includes/db.php';
include 'includes/util.php';

// Get site URL for absolute image paths
$site_url = get_config('site_url', 'https://manuelcode.info');
$base_url = rtrim($site_url, '/');

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: store.php');
    exit();
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: store.php');
    exit();
}

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
    
    // Store the discounted amount in session for payment processing
    $_SESSION['guest_discounted_amount'] = $final_amount;
    $_SESSION['guest_discount_amount'] = $discount_amount;
    
    error_log("Guest purchase with store coupon - Original: {$original_price}, Discount: {$discount_amount}, Final: {$final_amount}");
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
    
    // Store the discounted amount in session for payment processing
    $_SESSION['guest_discounted_amount'] = $final_amount;
    $_SESSION['guest_discount_amount'] = $discount_amount;
    
    error_log("Guest purchase with legacy coupon - Original: {$original_price}, Discount: {$discount_amount}, Final: {$final_amount}");
}

// Check if product is free (original price 0) or becomes free after discount
$is_free_after_discount = ($final_amount <= 0 && $discount_amount > 0);

// If product is free after discount, redirect to direct download
if ($is_free_after_discount) {
    // Store guest data in session for download
    $_SESSION['guest_free_download'] = [
        'product_id' => $product_id,
        'coupon_code' => $applied_coupon,
        'original_price' => $original_price,
        'discount_amount' => $discount_amount
    ];
    
    // Redirect to free download page
    header('Location: download_free_product_guest_coupon.php?id=' . $product_id . '&coupon=' . urlencode($applied_coupon));
    exit();
}

// Check if product is originally free - redirect to login for free products
if ($product['price'] == 0) {
    header('Location: product.php?id=' . $product_id . '&error=Free products require login. Please login to download.');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    if (empty($errors)) {
        // Store guest data in session for payment processing
        $_SESSION['guest_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'product_id' => $product_id
        ];
        
        // Redirect to payment processing with product ID
        header("Location: payment/process_payment.php?id=" . $product_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Purchase - <?php echo htmlspecialchars($product['title']); ?> - ManuelCode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #F5A623;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .back-to-top:hover {
            background: #d88c1b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center group">
                    <img src="assets/favi/favicon.png" alt="ManuelCode Logo" class="h-8 w-auto transition-transform duration-300 group-hover:scale-105">
                    <span class="ml-2 text-lg font-bold text-gray-800 group-hover:text-[#536895] transition-colors duration-300" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">ManuelCode</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="store.php" class="text-gray-600 hover:text-[#536895]">← Back to Store</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Product Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Product Details</h2>
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
                         class="w-full h-48 object-cover rounded-lg mb-4"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg mb-4 flex items-center justify-center" style="display: none;">
                        <i class="fas fa-box text-white text-6xl"></i>
                    </div>
                <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg mb-4 flex items-center justify-center">
                        <i class="fas fa-box text-white text-6xl"></i>
                    </div>
                <?php endif; ?>
                <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($product['title']); ?></h3>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($product['short_desc']); ?></p>
                
                <!-- Gallery Images -->
                <?php if (!empty($gallery_images)): ?>
                  <div class="mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Product Gallery</h4>
                    <div class="grid grid-cols-3 gap-2">
                      <?php foreach ($gallery_images as $gallery_img): 
                          $gallery_url = get_product_image_url($gallery_img, $base_url);
                      ?>
                        <?php if ($gallery_url): ?>
                          <img src="<?php echo htmlspecialchars($gallery_url); ?>" 
                               alt="Product screenshot" 
                               class="w-full h-20 object-cover rounded border cursor-pointer hover:opacity-80 transition-opacity"
                               onclick="openImageModal('<?php echo htmlspecialchars($gallery_url); ?>')"
                               onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                          <div class="w-full h-20 bg-gray-200 rounded border flex items-center justify-center" style="display: none;">
                            <i class="fas fa-image text-gray-400"></i>
                          </div>
                        <?php else: ?>
                          <div class="w-full h-20 bg-gray-200 rounded border flex items-center justify-center">
                            <i class="fas fa-image text-gray-400"></i>
                          </div>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
                
                <!-- Demo URL -->
                <?php if (!empty($product['demo_url'])): ?>
                  <div class="mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Live Demo</h4>
                    <a href="<?php echo htmlspecialchars($product['demo_url']); ?>" 
                       target="_blank" 
                       class="inline-flex items-center bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors">
                      <i class="fas fa-external-link-alt mr-2"></i>
                      View Live Demo
                    </a>
                  </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between">
                    <?php if ($product['price'] > 0): ?>
                      <span class="text-2xl font-bold text-[#F5A623]">GHS <?php echo number_format($product['price'], 2); ?></span>
                    <?php else: ?>
                      <span class="text-2xl font-bold text-blue-600">FREE</span>
                    <?php endif; ?>
                    <span class="text-sm text-gray-500">One-time purchase</span>
                </div>
            </div>

            <!-- Guest Purchase Form -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Guest Purchase</h2>
                <p class="text-gray-600 mb-6">Fill in your details to purchase this product without creating an account.</p>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               placeholder="Enter your phone number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent">
                    </div>

                    <!-- Coupon Section -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-3">
                            <i class="fas fa-tag text-[#F5A623] mr-2"></i>
                            Have a Coupon?
                        </h3>
                        <div class="flex gap-2">
                            <input type="text" 
                                   id="coupon-code" 
                                   name="coupon_code" 
                                   placeholder="Enter coupon code (e.g., WELCOME10)" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F5A623] focus:border-transparent text-sm">
                            <button type="button" 
                                    id="apply-coupon"
                                    class="bg-[#F5A623] hover:bg-[#d88c1b] text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">
                                Apply
                            </button>
                        </div>
                        
                        <div id="coupon-result" class="mt-3 hidden">
                            <div id="coupon-success" class="hidden bg-green-50 border border-green-200 rounded-md p-3">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                    <div>
                                        <p class="text-green-800 font-semibold text-sm" id="success-message"></p>
                                        <p class="text-green-600 text-xs" id="discount-info"></p>
                                    </div>
                                </div>
                            </div>
                            <div id="coupon-error" class="hidden bg-red-50 border border-red-200 rounded-md p-3">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                                    <p class="text-red-800 text-sm" id="error-message"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">Order Summary</h3>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600"><?php echo htmlspecialchars($product['title']); ?></span>
                            <?php if ($product['price'] > 0): ?>
                              <span class="font-semibold">GHS <?php echo number_format($product['price'], 2); ?></span>
                            <?php else: ?>
                              <span class="font-semibold text-blue-600">FREE</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Show coupon discount if applied -->
                        <?php if ($applied_coupon && $discount_amount > 0): ?>
                        <div id="coupon-discount-display" class="block">
                            <div class="flex justify-between items-center text-green-600 text-sm">
                                <span>Coupon Discount (<?php echo htmlspecialchars($applied_coupon); ?>):</span>
                                <span id="discount-amount">-₵<?php echo number_format($discount_amount, 2); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="border-t border-gray-200 mt-2 pt-2">
                            <div class="flex justify-between items-center font-bold">
                                <span>Total</span>
                                <?php if ($final_amount > 0): ?>
                                  <span class="text-[#F5A623]" id="total-amount">GHS <?php echo number_format($final_amount, 2); ?></span>
                                <?php else: ?>
                                  <span class="text-blue-600 font-bold" id="total-amount">FREE</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full bg-[#F5A623] text-white py-3 px-4 rounded-md hover:bg-[#d88c1b] transition-colors font-semibold"
                            id="payment-button">
                        <?php if ($final_amount > 0): ?>
                            Proceed to Payment
                        <?php else: ?>
                            Download Free Product
                        <?php endif; ?>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">Already have an account?</p>
                    <a href="login" class="text-[#F5A623] hover:underline font-medium" id="sign-in-link">Sign in to purchase</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <div class="back-to-top" style="display: none;">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center">
      <div class="relative max-w-4xl max-h-full p-4">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
          <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" alt="Product image" class="max-w-full max-h-full object-contain">
      </div>
    </div>

         <script>
         // Image Modal functionality
         function openImageModal(imageSrc) {
             const modal = document.getElementById('imageModal');
             const modalImage = document.getElementById('modalImage');
             modalImage.src = imageSrc;
             modal.classList.remove('hidden');
             document.body.style.overflow = 'hidden';
         }

         function closeImageModal() {
             const modal = document.getElementById('imageModal');
             modal.classList.add('hidden');
             document.body.style.overflow = '';
         }

         // Close modal when clicking outside the image
         document.getElementById('imageModal').addEventListener('click', function(e) {
             if (e.target === this) {
                 closeImageModal();
             }
         });

         // Back to top functionality
         const backToTopBtn = document.querySelector('.back-to-top');
         
         window.addEventListener('scroll', function() {
             if (window.pageYOffset > 300) {
                 backToTopBtn.style.display = 'flex';
             } else {
                 backToTopBtn.style.display = 'none';
             }
         });
         
         backToTopBtn.addEventListener('click', function() {
             window.scrollTo({
                 top: 0,
                 behavior: 'smooth'
             });
         });

                  // Coupon functionality
         document.addEventListener('DOMContentLoaded', function() {
             // Initialize variables
             const originalPrice = <?php echo $product['price']; ?>;
             let currentPrice = originalPrice;
             let appliedCoupon = null;
             
             // Check if we have a pre-applied coupon from store page
             <?php if ($applied_coupon && $coupon_info): ?>
             // Pre-applied coupon detected
             appliedCoupon = {
                 code: '<?php echo htmlspecialchars($applied_coupon); ?>',
                 discount_type: '<?php echo htmlspecialchars($coupon_info['discount_type']); ?>',
                 discount_value: <?php echo $coupon_info['discount_value']; ?>
             };
             
             // Show pre-applied coupon message
             showPreAppliedCoupon(appliedCoupon);
             console.log('Pre-applied coupon detected:', appliedCoupon);
             <?php endif; ?>
             
             const applyCouponBtn = document.getElementById('apply-coupon');
             const couponCode = document.getElementById('coupon-code');
             const couponResult = document.getElementById('coupon-result');
             const couponSuccess = document.getElementById('coupon-success');
             const couponError = document.getElementById('coupon-error');
             const successMessage = document.getElementById('success-message');
             const discountInfo = document.getElementById('discount-info');
             const errorMessage = document.getElementById('error-message');
             const couponDiscountDisplay = document.getElementById('coupon-discount-display');
             const discountAmount = document.getElementById('discount-amount');
             const totalAmount = document.getElementById('total-amount');
            
            if (applyCouponBtn) {
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
                            console.log('Coupon stored in session storage:', appliedCoupon);
                        } else {
                            showCouponError(data.message);
                            sessionStorage.removeItem('applied_coupon');
                            console.log('Coupon error, removed from session storage');
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
            }
            
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
                
                if (coupon.discount_type === 'percentage') {
                    discount = (originalPrice * discountValue) / 100;
                } else {
                    discount = discountValue;
                }
                
                currentPrice = originalPrice - discount;
                discountAmount.textContent = `-₵${discount.toFixed(2)}`;
                
                // Check if product becomes free after discount (100% or exact amount)
                if (currentPrice <= 0) {
                    totalAmount.textContent = 'FREE';
                    totalAmount.className = 'text-blue-600 font-bold';
                    
                    // Update the payment button to a download button
                    const paymentButton = document.querySelector('button[type="submit"]');
                    if (paymentButton) {
                        paymentButton.innerHTML = '<i class="fas fa-download mr-2"></i>Download Free Product';
                        paymentButton.className = 'w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 transition-colors font-semibold';
                        paymentButton.type = 'button'; // Change from submit to button
                        paymentButton.onclick = function() {
                            downloadFreeProductGuestCoupon();
                        };
                    }
                    
                    // Update sign-in link text
                    const signInLink = document.getElementById('sign-in-link');
                    if (signInLink) {
                        signInLink.textContent = 'Sign in to download';
                    }
                    
                    // Show free product message
                    const freeProductMessage = document.createElement('div');
                    freeProductMessage.id = 'free-product-message';
                    freeProductMessage.className = 'mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded text-center';
                    freeProductMessage.innerHTML = `
                        <i class="fas fa-gift mr-2"></i>
                        <strong>Congratulations!</strong> This product is now FREE with your coupon!
                        <div class="mt-2 text-sm text-green-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            No account required - download immediately!
                        </div>
                    `;
                    
                    // Remove existing message if any
                    const existingMessage = document.getElementById('free-product-message');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                    
                    // Insert the message after the form
                    const form = document.querySelector('form');
                    if (form) {
                        form.parentNode.insertBefore(freeProductMessage, form.nextSibling);
                    }
                    
                } else {
                    totalAmount.textContent = `GHS ${currentPrice.toFixed(2)}`;
                    totalAmount.className = 'text-[#F5A623]';
                    
                    // Restore original payment button
                    const paymentButton = document.querySelector('button[type="button"]');
                    if (paymentButton && paymentButton.innerHTML.includes('Download')) {
                        paymentButton.innerHTML = 'Proceed to Payment';
                        paymentButton.className = 'w-full bg-[#F5A623] text-white py-3 px-4 rounded-md hover:bg-[#d88c1b] transition-colors font-semibold';
                        paymentButton.type = 'submit'; // Change back to submit
                        paymentButton.onclick = null; // Remove custom onclick
                    }
                    
                    // Update sign-in link text back to original
                    const signInLink = document.getElementById('sign-in-link');
                    if (signInLink) {
                        signInLink.textContent = 'Sign in to purchase';
                    }
                    
                    // Remove free product message if it exists
                    const existingMessage = document.getElementById('free-product-message');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                }
                
                couponDiscountDisplay.classList.remove('hidden');
            }
            
                      // Function to show pre-applied coupon message
          function showPreAppliedCoupon(coupon) {
              successMessage.textContent = `Coupon '${coupon.code}' applied successfully!`;
              discountInfo.textContent = coupon.discount_type === 'percentage' 
                  ? `${coupon.discount_value}% off` 
                  : `₵${coupon.discount_value} off`;
              
              couponSuccess.classList.remove('hidden');
              couponError.classList.add('hidden');
              couponResult.classList.remove('hidden');
              couponDiscountDisplay.classList.remove('hidden');
              
              // Pre-fill the coupon input
              document.getElementById('coupon-code').value = coupon.code;
          }
          
          // Function to download free product with coupon (for guests)
          window.downloadFreeProductGuestCoupon = function() {
                // Try both session storage keys
                let couponData = sessionStorage.getItem('applied_coupon');
                if (!couponData) {
                    couponData = sessionStorage.getItem('store_applied_coupon');
                }
                
                if (couponData) {
                    const coupon = JSON.parse(couponData);
                    console.log('Downloading with coupon:', coupon);
                    // Redirect to guest download with coupon information
                    window.location.href = `download_free_product_guest_coupon.php?id=<?php echo $product['id']; ?>&coupon=${encodeURIComponent(coupon.code)}`;
                } else {
                    console.log('No coupon data found in session storage');
                    // Fallback to guest purchase page
                    window.location.href = 'guest_purchase.php?id=<?php echo $product['id']; ?>';
                }
            };
                 });
     </script>
</body>
</html>
