<?php
include 'includes/db.php';
include 'includes/coupon_helper.php';

echo "<h2>Coupon System Diagnostic</h2>";

// Check what coupons exist
echo "<h3>Existing Coupons:</h3>";
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY id");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($coupons)) {
    echo "<p>No coupons found in database.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Discount</th><th>Applies To</th><th>Status</th></tr>";
    
    foreach ($coupons as $coupon) {
        echo "<tr>";
        echo "<td>{$coupon['id']}</td>";
        echo "<td>{$coupon['code']}</td>";
        echo "<td>{$coupon['name']}</td>";
        echo "<td>{$coupon['discount_type']}: {$coupon['discount_value']}</td>";
        echo "<td>{$coupon['applies_to']}</td>";
        echo "<td>" . ($coupon['is_active'] ? 'Active' : 'Inactive') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check coupon_products table
echo "<h3>Coupon-Product Relationships:</h3>";
$stmt = $pdo->query("SELECT cp.*, c.code as coupon_code, p.title as product_title 
                     FROM coupon_products cp 
                     JOIN coupons c ON cp.coupon_id = c.id 
                     JOIN products p ON cp.product_id = p.id 
                     ORDER BY cp.coupon_id, cp.product_id");
$coupon_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($coupon_products)) {
    echo "<p>No coupon-product relationships found.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Coupon ID</th><th>Coupon Code</th><th>Product ID</th><th>Product Title</th></tr>";
    
    foreach ($coupon_products as $cp) {
        echo "<tr>";
        echo "<td>{$cp['coupon_id']}</td>";
        echo "<td>{$cp['coupon_code']}</td>";
        echo "<td>{$cp['product_id']}</td>";
        echo "<td>{$cp['product_title']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check products
echo "<h3>Available Products:</h3>";
$stmt = $pdo->query("SELECT id, title, price, status FROM products ORDER BY id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "<p>No products found in database.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Price</th><th>Status</th></tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['title']}</td>";
        echo "<td>₵{$product['price']}</td>";
        echo "<td>{$product['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test coupon validation for product ID 1
echo "<h3>Testing Coupon Validation for Product ID 1:</h3>";
if (!empty($coupons)) {
    $test_product_id = 1;
    $test_product = $pdo->query("SELECT * FROM products WHERE id = $test_product_id")->fetch(PDO::FETCH_ASSOC);
    
    if ($test_product) {
        echo "<p>Testing product: {$test_product['title']} (₵{$test_product['price']})</p>";
        
        foreach ($coupons as $coupon) {
            if ($coupon['is_active']) {
                $couponManager = new CouponManager($pdo);
                $result = $couponManager->validateCoupon($coupon['code'], null, $test_product_id, $test_product['price']);
                
                echo "<p><strong>Coupon {$coupon['code']}:</strong> ";
                if ($result['valid']) {
                    echo "<span style='color: green;'>✓ Valid</span> - {$result['message']}";
                } else {
                    echo "<span style='color: red;'>✗ Invalid</span> - {$result['message']}";
                }
                echo "</p>";
            }
        }
    } else {
        echo "<p>Product ID 1 not found.</p>";
    }
}

// Provide solutions
echo "<h3>Solutions:</h3>";
echo "<p><strong>Option 1:</strong> Make all coupons apply to all products by setting 'applies_to' to 'all'</p>";
echo "<p><strong>Option 2:</strong> Add specific products to the coupon_products table for each coupon</p>";
echo "<p><strong>Option 3:</strong> Create a new coupon that applies to all products</p>";

echo "<h3>Quick Fix Commands:</h3>";
echo "<p><strong>Make all coupons apply to all products:</strong></p>";
echo "<code>UPDATE coupons SET applies_to = 'all' WHERE applies_to = 'specific_products';</code>";

echo "<p><strong>Add product ID 1 to all specific-product coupons:</strong></p>";
echo "<code>INSERT INTO coupon_products (coupon_id, product_id) SELECT id, 1 FROM coupons WHERE applies_to = 'specific_products' AND id NOT IN (SELECT coupon_id FROM coupon_products WHERE product_id = 1);</code>";
?>
