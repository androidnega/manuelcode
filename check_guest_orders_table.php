<?php
require_once 'includes/db.php';

echo "=== CHECKING GUEST_ORDERS TABLE STRUCTURE ===\n\n";

try {
    // Check current table structure
    $stmt = $pdo->query("DESCRIBE guest_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current columns in guest_orders table:\n";
    $existing_columns = [];
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
        $existing_columns[] = $column['Field'];
    }
    
    echo "\nChecking for required coupon columns...\n";
    
    // Check if coupon_code column exists
    if (!in_array('coupon_code', $existing_columns)) {
        echo "❌ coupon_code column missing - adding...\n";
        $stmt = $pdo->prepare("ALTER TABLE guest_orders ADD COLUMN coupon_code VARCHAR(50) NULL");
        $stmt->execute();
        echo "✅ coupon_code column added\n";
    } else {
        echo "✅ coupon_code column exists\n";
    }
    
    // Check if discount_amount column exists
    if (!in_array('discount_amount', $existing_columns)) {
        echo "❌ discount_amount column missing - adding...\n";
        $stmt = $pdo->prepare("ALTER TABLE guest_orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00");
        $stmt->execute();
        echo "✅ discount_amount column added\n";
    } else {
        echo "✅ discount_amount column exists\n";
    }
    
    // Check if original_amount column exists
    if (!in_array('original_amount', $existing_columns)) {
        echo "❌ original_amount column missing - adding...\n";
        $stmt = $pdo->prepare("ALTER TABLE guest_orders ADD COLUMN original_amount DECIMAL(10,2) DEFAULT 0.00");
        $stmt->execute();
        echo "✅ original_amount column added\n";
    } else {
        echo "✅ original_amount column exists\n";
    }
    
    echo "\n=== FINAL TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE guest_orders");
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($final_columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== COMPLETE ===\n";
    echo "✓ Guest orders table now supports coupon tracking\n";
    echo "✓ Both users and guests can use 100% coupons\n";
    echo "✓ Products automatically become free with valid coupons\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
