<?php
/**
 * Purchase Status Diagnostic Script
 * This script checks the current status of purchases to help diagnose display issues
 */

include 'includes/db.php';

// Get current user ID from session or parameter
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo "Please provide a user_id parameter: ?user_id=YOUR_USER_ID\n";
    exit;
}

echo "=== PURCHASE STATUS DIAGNOSTIC ===\n";
echo "User ID: $user_id\n\n";

try {
    // 1. Check all purchases for this user
    echo "1. ALL PURCHASES FOR USER:\n";
    $stmt = $pdo->prepare("
        SELECT p.*, pr.title as product_title, pr.price, pr.status as product_status
        FROM purchases p
        JOIN products pr ON p.product_id = pr.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $all_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_purchases)) {
        echo "   No purchases found for this user.\n";
    } else {
        foreach ($all_purchases as $purchase) {
            echo "   - ID: {$purchase['id']}, Product: {$purchase['product_title']}, Status: {$purchase['status']}, Amount: {$purchase['amount']}, Created: {$purchase['created_at']}\n";
        }
    }
    
    echo "\n";
    
    // 2. Check purchase status counts
    echo "2. PURCHASE STATUS COUNTS:\n";
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count, SUM(amount) as total_amount
        FROM purchases 
        WHERE user_id = ?
        GROUP BY status
        ORDER BY count DESC
    ");
    $stmt->execute([$user_id]);
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($status_counts as $status) {
        echo "   - {$status['status']}: {$status['count']} purchases, Total: GHS {$status['total_amount']}\n";
    }
    
    echo "\n";
    
    // 3. Check products table
    echo "3. PRODUCTS TABLE STATUS:\n";
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.status, p.price
        FROM products p
        JOIN purchases pur ON p.id = pur.product_id
        WHERE pur.user_id = ?
        GROUP BY p.id
    ");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "   - ID: {$product['id']}, Title: {$product['title']}, Status: {$product['status']}, Price: GHS {$product['price']}\n";
    }
    
    echo "\n";
    
    // 4. Check payment logs
    echo "4. PAYMENT LOGS:\n";
    $stmt = $pdo->prepare("
        SELECT pl.*, p.payment_ref, p.status as purchase_status
        FROM payment_logs pl
        JOIN purchases p ON pl.order_id = p.id
        WHERE p.user_id = ?
        ORDER BY pl.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $payment_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payment_logs)) {
        echo "   No payment logs found.\n";
    } else {
        foreach ($payment_logs as $log) {
            echo "   - Order ID: {$log['order_id']}, Status: {$log['status']}, Amount: {$log['amount']}, Reference: {$log['reference']}, Purchase Status: {$log['purchase_status']}\n";
        }
    }
    
    echo "\n";
    
    // 5. Check guest orders (if user has email)
    echo "5. GUEST ORDERS CHECK:\n";
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_email = $stmt->fetch(PDO::FETCH_ASSOC)['email'] ?? null;
    
    if ($user_email) {
        $stmt = $pdo->prepare("
            SELECT go.*, p.title as product_title
            FROM guest_orders go
            JOIN products p ON go.product_id = p.id
            WHERE go.email = ?
            ORDER BY go.created_at DESC
        ");
        $stmt->execute([$user_email]);
        $guest_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($guest_orders)) {
            echo "   No guest orders found for email: $user_email\n";
        } else {
            foreach ($guest_orders as $order) {
                echo "   - ID: {$order['id']}, Product: {$order['product_title']}, Status: {$order['status']}, Amount: {$order['total_amount']}, Created: {$order['created_at']}\n";
            }
        }
    } else {
        echo "   User has no email address.\n";
    }
    
    echo "\n";
    
    // 6. Test the getAllPurchasedProducts function
    echo "6. TESTING getAllPurchasedProducts FUNCTION:\n";
    include 'includes/product_functions.php';
    
    $purchased_products = getAllPurchasedProducts($user_id, $user_email);
    
    if (empty($purchased_products)) {
        echo "   Function returned no products.\n";
    } else {
        echo "   Function returned " . count($purchased_products) . " products:\n";
        foreach ($purchased_products as $product) {
            echo "   - Product: {$product['product_title']}, Status: {$product['status']}, Type: {$product['purchase_type']}\n";
        }
    }
    
    echo "\n";
    
    // 7. Recommendations
    echo "7. RECOMMENDATIONS:\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM purchases WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as failed_count FROM purchases WHERE user_id = ? AND status = 'failed'");
    $stmt->execute([$user_id]);
    $failed_count = $stmt->fetch(PDO::FETCH_ASSOC)['failed_count'];
    
    if ($pending_count > 0) {
        echo "   - You have $pending_count pending purchases. These need payment verification.\n";
    }
    
    if ($failed_count > 0) {
        echo "   - You have $failed_count failed purchases. These need to be retried or refunded.\n";
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as paid_count FROM purchases WHERE user_id = ? AND status = 'paid'");
    $stmt->execute([$user_id]);
    $paid_count = $stmt->fetch(PDO::FETCH_ASSOC)['paid_count'];
    
    if ($paid_count == 0) {
        echo "   - No paid purchases found. This explains why nothing shows in the dashboard.\n";
        echo "   - Check if payments were completed successfully.\n";
        echo "   - Verify Paystack callback processing.\n";
    }
    
    echo "\n=== DIAGNOSTIC COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error during diagnostic: " . $e->getMessage() . "\n";
}
?>
