<?php
/**
 * Quick Status Check
 * Shows current system status
 */

include 'includes/db.php';

echo "=== QUICK SYSTEM STATUS CHECK ===\n\n";

try {
    // Check counts
    $stmt = $pdo->query("SELECT COUNT(*) FROM purchases WHERE status = 'paid'");
    $total_paid = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM receipts");
    $total_receipts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM download_access");
    $total_download_access = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_notifications");
    $total_admin_notifications = $stmt->fetchColumn();
    
    echo "Current System Status:\n";
    echo "======================\n";
    echo "Paid purchases: {$total_paid}\n";
    echo "Receipts: {$total_receipts}\n";
    echo "Download access: {$total_download_access}\n";
    echo "Admin notifications: {$total_admin_notifications}\n\n";
    
    // Check if all are synchronized
    if ($total_paid == $total_receipts && $total_paid == $total_download_access && $total_paid == $total_admin_notifications) {
        echo "✅ ALL SYSTEMS ARE SYNCHRONIZED!\n";
        echo "Every user with purchased items will now see them in:\n";
        echo "- my_purchases.php\n";
        echo "- downloads.php\n";
        echo "- receipts page\n";
        echo "- Admin dashboard\n";
    } else {
        echo "❌ Systems still need synchronization\n";
        echo "Missing:\n";
        if ($total_paid != $total_receipts) echo "- Receipts: " . ($total_paid - $total_receipts) . "\n";
        if ($total_paid != $total_download_access) echo "- Download access: " . ($total_paid - $total_download_access) . "\n";
        if ($total_paid != $total_admin_notifications) echo "- Admin notifications: " . ($total_paid - $total_admin_notifications) . "\n";
    }
    
    echo "\n=== STATUS CHECK COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
