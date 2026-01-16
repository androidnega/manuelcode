<?php
session_start();
include '../includes/db.php';
include '../config/payment_config.php';
include '../includes/product_functions.php';

// Require user to be logged in - guest purchases are disabled
if (!isset($_SESSION['user_id'])) {
    $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($product_id) {
        header('Location: ../login?redirect=' . urlencode('product.php?id=' . $product_id . '&error=Please login to purchase products'));
    } else {
        header('Location: ../login?redirect=' . urlencode('store.php') . '&error=Please login to purchase products');
    }
    exit();
}

$is_guest = false;
$guest_data = null;

// Get product ID from URL or session
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no product ID in URL, try to get from guest data
if (!$product_id && $guest_data && isset($guest_data['product_id'])) {
    $product_id = (int)$guest_data['product_id'];
}

if (!$product_id) {
    header('Location: ../store.php');
    exit();
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: ../store.php');
    exit();
}

// Check if user has already purchased this product
$has_purchased = false;
$download_link = null;
if (!$is_guest && isset($_SESSION['user_id'])) {
    // FIXED: Use the new duplicate prevention function
    $user_email = $_SESSION['user_email'] ?? null;
    if (!canUserPurchaseProduct($_SESSION['user_id'], $product_id, $user_email)) {
        $has_purchased = true;
        
        // Get existing download link
        if (hasUserPurchasedProduct($_SESSION['user_id'], $product_id)) {
            $download_link = getProductDownloadLink($_SESSION['user_id'], $product_id);
            if ($download_link) {
                $download_link = "../" . $download_link;
            }
        } else {
            // Check guest purchases by email
            if ($user_email) {
                $stmt = $pdo->prepare("SELECT * FROM guest_orders WHERE email = ? AND product_id = ? AND status = 'paid'");
                $stmt->execute([$user_email, $product_id]);
                $guest_purchase = $stmt->fetch();
                if ($guest_purchase) {
                    $download_link = getGuestDownloadLink($user_email, $product_id);
                    if ($download_link) {
                        $download_link = "../" . $download_link;
                    }
                }
            }
        }
    }
    
    // If already purchased, redirect to download
    if ($has_purchased && $download_link) {
        header('Location: ' . $download_link);
        exit();
    }
}

// Get user details if logged in
$user = null;
if (!$is_guest) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// FIXED: Check for applied coupon in session and calculate discounted amount
$original_price = $product['price'];
$final_amount = $original_price;
$discount_amount = 0;
$applied_coupon = null;

if (isset($_SESSION['applied_coupon']) && isset($_SESSION['coupon_discount_info'])) {
    $applied_coupon = $_SESSION['applied_coupon'];
    $discount_info = $_SESSION['coupon_discount_info'];
    
    // Calculate the final amount with discount
    if ($discount_info['discount_type'] === 'percentage') {
        $discount_amount = ($original_price * $discount_info['discount_value']) / 100;
    } else {
        $discount_amount = $discount_info['discount_value'];
    }
    
    $final_amount = max(0, $original_price - $discount_amount);
    
    // Store the discounted amount in session for payment processing
    if ($is_guest) {
        $_SESSION['guest_discounted_amount'] = $final_amount;
        $_SESSION['guest_discount_amount'] = $discount_amount;
    } else {
        $_SESSION['user_discounted_amount'] = $final_amount;
        $_SESSION['user_discount_amount'] = $discount_amount;
    }
    
    error_log("Payment page - Original: {$original_price}, Discount: {$discount_amount}, Final: {$final_amount}");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo htmlspecialchars($product['title']); ?> - ManuelCode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="../index.php" class="flex items-center group">
                    <img src="../assets/favi/favicon.png" alt="ManuelCode Logo" class="h-8 w-auto transition-transform duration-300 group-hover:scale-105">
                    <span class="ml-2 text-lg font-bold text-gray-800 group-hover:text-[#536895] transition-colors duration-300" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">ManuelCode</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="../store.php" class="text-gray-600 hover:text-[#536895]">← Back to Store</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Product Details -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Product Details</h2>
                <img src="../assets/images/products/<?php echo htmlspecialchars($product['preview_image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                     class="w-full h-48 object-cover rounded-lg mb-4">
                <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($product['title']); ?></h3>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($product['short_desc']); ?></p>
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-bold text-[#F5A623]">GHS <?php echo number_format($product['price'], 2); ?></span>
                    <span class="text-sm text-gray-500">One-time purchase</span>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Payment Information</h2>
                
                <?php if ($is_guest): ?>
                    <!-- Guest Payment Info -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Guest Purchase</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($guest_data['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($guest_data['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($guest_data['phone']); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Registered User Payment Info -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Account Purchase</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                                <!-- Payment Summary -->
                <div class="border-t pt-4 mb-6">
                  <h3 class="text-lg font-semibold text-gray-700 mb-3">Payment Summary</h3>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span>Product Price:</span>
                      <span>GHS <?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    
                    <!-- FIXED: Show coupon discount if applied from session -->
                    <?php if ($applied_coupon && $discount_amount > 0): ?>
                    <div id="coupon-discount-section" class="block">
                      <div class="flex justify-between text-green-600">
                        <span>Coupon Discount (<?php echo htmlspecialchars($applied_coupon['code']); ?>):</span>
                        <span id="discount-amount">-₵<?php echo number_format($discount_amount, 2); ?></span>
                      </div>
                      <div class="flex justify-between text-sm text-gray-500">
                        <span id="coupon-code-display">Coupon: <?php echo htmlspecialchars($applied_coupon['code']); ?></span>
                        <button type="button" id="remove-coupon" class="text-red-600 hover:text-red-800">
                          <i class="fas fa-times mr-1"></i>Remove
                        </button>
                      </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between font-semibold text-lg border-t pt-2">
                      <span>Total:</span>
                      <span class="text-[#F5A623]" id="total-amount">GHS <?php echo number_format($final_amount, 2); ?></span>
                    </div>
                  </div>
                </div>

                <!-- MoMo Payment Button -->
                <button id="paystack-button" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-6 rounded-lg font-semibold transition-colors"
                        data-product-id="<?php echo $product_id; ?>"
                        data-is-guest="<?php echo $is_guest ? 'true' : 'false'; ?>"
                        <?php if ($is_guest && $guest_data): ?>
                        data-guest-data='<?php echo json_encode($guest_data); ?>'
                        <?php endif; ?>>
                    <i class="fas fa-mobile-alt mr-2"></i>
                    Pay with MoMo
                </button>

                <div id="payment-status" class="mt-4 hidden">
                    <div id="payment-loading" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#536895] mx-auto"></div>
                        <p class="mt-2 text-gray-600">Processing payment...</p>
                    </div>
                    <div id="payment-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-600" id="error-message"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/payment.js"></script>
    <script>
    // FIXED: Coupon functionality for payment page with session data
    document.addEventListener('DOMContentLoaded', function() {
        const originalPrice = <?php echo $product['price']; ?>;
        let currentPrice = <?php echo $final_amount; ?>; // Use the discounted amount from session
        let appliedCoupon = <?php echo $applied_coupon ? json_encode($applied_coupon) : 'null'; ?>;
        
        // Check for applied coupon in session storage
        const storedCoupon = sessionStorage.getItem('applied_coupon');
        if (storedCoupon) {
            try {
                appliedCoupon = JSON.parse(storedCoupon);
                applyCouponToPayment(appliedCoupon);
            } catch (e) {
                console.error('Error parsing stored coupon:', e);
                sessionStorage.removeItem('applied_coupon');
            }
        }
        
        // Handle remove coupon button
        const removeCouponBtn = document.getElementById('remove-coupon');
        if (removeCouponBtn) {
            removeCouponBtn.addEventListener('click', function() {
                removeCoupon();
            });
        }
        
        function applyCouponToPayment(coupon) {
            const discountSection = document.getElementById('coupon-discount-section');
            const discountAmount = document.getElementById('discount-amount');
            const couponCodeDisplay = document.getElementById('coupon-code-display');
            const totalAmount = document.getElementById('total-amount');
            
            // Convert discount_value to number to ensure it's numeric
            const discountValue = parseFloat(coupon.discount_value);
            
            if (coupon.discount_type === 'percentage') {
                const discount = (originalPrice * discountValue) / 100;
                currentPrice = originalPrice - discount;
                discountAmount.textContent = `-₵${discount.toFixed(2)}`;
            } else {
                const discount = discountValue;
                currentPrice = originalPrice - discount;
                discountAmount.textContent = `-₵${discount.toFixed(2)}`;
            }
            
            couponCodeDisplay.textContent = `Coupon: ${coupon.code}`;
            totalAmount.textContent = `GHS ${currentPrice.toFixed(2)}`;
            discountSection.classList.remove('hidden');
        }
        
        function removeCoupon() {
            const discountSection = document.getElementById('coupon-discount-section');
            const totalAmount = document.getElementById('total-amount');
            
            currentPrice = originalPrice;
            totalAmount.textContent = `GHS ${currentPrice.toFixed(2)}`;
            discountSection.classList.add('hidden');
            appliedCoupon = null;
            sessionStorage.removeItem('applied_coupon');
        }
        
        // Update payment button to include coupon data
        const paystackButton = document.getElementById('paystack-button');
        if (paystackButton && appliedCoupon) {
            paystackButton.setAttribute('data-coupon', JSON.stringify(appliedCoupon));
        }
    });
    </script>
</body>
</html>
