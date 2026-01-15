<?php
session_start();
include 'includes/db.php';

// Get the product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1003;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<!DOCTYPE html><html><head><title>Not Logged In</title></head><body>";
    echo "<h1>Not Logged In</h1>";
    echo "<p>Please <a href='login'>log in</a> to check purchase status.</p>";
    echo "</body></html>";
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? 'N/A';

echo "<!DOCTYPE html><html><head><title>Purchase Status Check</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;} .success{color:green;} .error{color:red;}</style>";
echo "</head><body>";

echo "<h1>Purchase Status Debug</h1>";
echo "<p><strong>User ID:</strong> {$user_id}</p>";
echo "<p><strong>User Email:</strong> {$user_email}</p>";
echo "<p><strong>Product ID:</strong> {$product_id}</p>";

// Check product exists
$stmt = $pdo->prepare("SELECT id, title, price FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    echo "<p class='success'><strong>Product Found:</strong> {$product['title']} (GHS {$product['price']})</p>";
} else {
    echo "<p class='error'><strong>Product NOT Found!</strong></p>";
    exit;
}

echo "<hr>";

// Check user purchases
echo "<h2>User Purchases (purchases table)</h2>";
$stmt = $pdo->prepare("SELECT * FROM purchases WHERE user_id = ? AND product_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id, $product_id]);
$user_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($user_purchases) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Payment Ref</th><th>Status</th><th>Amount</th><th>Created</th><th>Updated</th></tr>";
    foreach ($user_purchases as $purchase) {
        $status_class = $purchase['status'] === 'paid' ? 'success' : 'error';
        echo "<tr>";
        echo "<td>{$purchase['id']}</td>";
        echo "<td>{$purchase['payment_ref']}</td>";
        echo "<td class='{$status_class}'><strong>{$purchase['status']}</strong></td>";
        echo "<td>GHS {$purchase['amount']}</td>";
        echo "<td>{$purchase['created_at']}</td>";
        echo "<td>" . ($purchase['updated_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count paid purchases
    $paid_count = 0;
    foreach ($user_purchases as $purchase) {
        if ($purchase['status'] === 'paid') {
            $paid_count++;
        }
    }
    
    if ($paid_count > 0) {
        echo "<p class='success'><strong>✓ Found {$paid_count} PAID purchase(s) - User SHOULD see download button!</strong></p>";
    } else {
        echo "<p class='error'><strong>✗ No PAID purchases found - User will see buy button</strong></p>";
    }
} else {
    echo "<p>No purchases found in purchases table.</p>";
}

echo "<hr>";

// Check guest purchases by email
echo "<h2>Guest Purchases by Email (guest_orders table)</h2>";
$stmt = $pdo->prepare("SELECT * FROM guest_orders WHERE email = ? AND product_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_email, $product_id]);
$guest_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($guest_purchases) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Reference</th><th>Status</th><th>Amount</th><th>Email</th><th>Created</th></tr>";
    foreach ($guest_purchases as $purchase) {
        $status_class = $purchase['status'] === 'paid' ? 'success' : 'error';
        echo "<tr>";
        echo "<td>{$purchase['id']}</td>";
        echo "<td>{$purchase['reference']}</td>";
        echo "<td class='{$status_class}'><strong>{$purchase['status']}</strong></td>";
        $amount = $purchase['total_amount'] ?? $purchase['amount'] ?? 'N/A';
        echo "<td>GHS {$amount}</td>";
        echo "<td>{$purchase['email']}</td>";
        echo "<td>{$purchase['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No guest purchases found for this email.</p>";
}

echo "<hr>";

// Check all purchases for this user (any product)
echo "<h2>All User Purchases (Any Product)</h2>";
$stmt = $pdo->prepare("SELECT p.*, pr.title as product_title FROM purchases p LEFT JOIN products pr ON p.product_id = pr.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$all_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($all_purchases) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Payment Ref</th><th>Status</th><th>Amount</th><th>Created</th></tr>";
    foreach ($all_purchases as $purchase) {
        $status_class = $purchase['status'] === 'paid' ? 'success' : 'error';
        echo "<tr>";
        echo "<td>{$purchase['id']}</td>";
        echo "<td>{$purchase['product_title']}</td>";
        echo "<td>{$purchase['payment_ref']}</td>";
        echo "<td class='{$status_class}'><strong>{$purchase['status']}</strong></td>";
        echo "<td>GHS {$purchase['amount']}</td>";
        echo "<td>{$purchase['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No purchases found for this user.</p>";
}

echo "<hr>";
echo "<p><a href='product.php?id={$product_id}'>← Back to Product</a> | <a href='dashboard/my-purchases'>My Purchases</a></p>";

echo "</body></html>";
?>
