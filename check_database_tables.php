<?php
include 'includes/db.php';

echo "<h2>Database Tables Check</h2>";

// List of tables that should exist
$required_tables = [
    'users',
    'products', 
    'purchases',
    'submissions',
    'analysts',
    'submission_analyst_logs',
    'refund_requests',
    'refunds',
    'refund_logs',
    'download_logs',
    'download_tokens',
    'sms_logs',
    'payment_logs',
    'purchase_logs',
    'user_notifications',
    'user_notification_preferences',
    'user_activity',
    'user_sessions',
    'otp_codes',
    'coupon_usage',
    'guest_orders',
    'support_tickets',
    'support_replies',
    'ip_management',
    'system_logs'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Table Name</th><th>Exists</th><th>Status</th></tr>";

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>" . ($exists ? "✅ Yes" : "❌ No") . "</td>";
        echo "<td>" . ($exists ? "Ready" : "Missing") . "</td>";
        echo "</tr>";
    } catch (Exception $e) {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>❌ Error</td>";
        echo "<td>Check failed: " . $e->getMessage() . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>Summary:</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Total tables in database: " . count($all_tables) . "</p>";
    echo "<p>Required tables: " . count($required_tables) . "</p>";
    
    $missing_tables = array_diff($required_tables, $all_tables);
    if (!empty($missing_tables)) {
        echo "<p style='color: red;'>Missing tables:</p>";
        echo "<ul>";
        foreach ($missing_tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✅ All required tables exist!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking tables: " . $e->getMessage() . "</p>";
}
?>
