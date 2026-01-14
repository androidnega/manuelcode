<?php
include 'includes/db.php';

echo "<h2>Updating Existing Purchases</h2>";

try {
    // Check if original_amount column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM purchases LIKE 'original_amount'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        echo "<p>❌ The 'original_amount' column doesn't exist in the purchases table.</p>";
        echo "<p>Please run this SQL command first:</p>";
        echo "<code>ALTER TABLE purchases ADD COLUMN original_amount DECIMAL(10,2) NULL AFTER amount;</code>";
        exit;
    }
    
    // Update purchases that don't have original_amount set
    $stmt = $pdo->prepare("
        UPDATE purchases p 
        JOIN products pr ON p.product_id = pr.id 
        SET p.original_amount = pr.price 
        WHERE p.original_amount IS NULL AND p.status = 'paid'
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    
    echo "<p>✅ Updated {$updated} purchases with original amounts</p>";
    
    // Show current status
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_purchases,
            COUNT(CASE WHEN original_amount IS NOT NULL THEN 1 END) as with_original_amount,
            COUNT(CASE WHEN original_amount IS NULL THEN 1 END) as without_original_amount
        FROM purchases 
        WHERE status = 'paid'
    ");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Status:</h3>";
    echo "<p>Total paid purchases: {$status['total_purchases']}</p>";
    echo "<p>With original amount: {$status['with_original_amount']}</p>";
    echo "<p>Without original amount: {$status['without_original_amount']}</p>";
    
    if ($status['without_original_amount'] == 0) {
        echo "<p>✅ All purchases now have original amounts set!</p>";
    } else {
        echo "<p>⚠️ Some purchases still don't have original amounts. You may need to run this script again.</p>";
    }
    
    // Show sample of updated purchases
    echo "<h3>Sample Updated Purchases:</h3>";
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.user_id,
            pr.title as product_title,
            p.amount,
            p.original_amount,
            p.discount_amount,
            p.created_at
        FROM purchases p 
        JOIN products pr ON p.product_id = pr.id 
        WHERE p.status = 'paid' AND p.original_amount IS NOT NULL
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($purchases)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Product</th><th>Amount Paid</th><th>Original Price</th><th>Discount</th><th>Date</th></tr>";
        
        foreach ($purchases as $purchase) {
            $discount = $purchase['discount_amount'] ?? 0;
            $original = $purchase['original_amount'];
            $paid = $purchase['amount'];
            
            echo "<tr>";
            echo "<td>{$purchase['id']}</td>";
            echo "<td>{$purchase['product_title']}</td>";
            echo "<td>₵{$paid}</td>";
            echo "<td>₵{$original}</td>";
            echo "<td>₵{$discount}</td>";
            echo "<td>" . date('M j, Y', strtotime($purchase['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. Go to your dashboard</p>";
    echo "<p>2. Check the Recent Purchases section</p>";
    echo "<p>3. Products should now show the correct pricing (original price, not 0.00)</p>";
    echo "<p>4. Free products with coupons will show 'FREE' with the original price crossed out</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
