<?php
/**
 * Check and Create Missing Database Tables
 * Creates tables that are needed for the comprehensive system fix
 */

include 'includes/db.php';

echo "=== CHECKING AND CREATING MISSING TABLES ===\n\n";

try {
    // 1. Check which tables exist
    echo "1. CHECKING EXISTING TABLES:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($existing_tables) . " existing tables:\n";
    foreach ($existing_tables as $table) {
        echo "  - {$table}\n";
    }
    
    echo "\n";
    
    // 2. Define required tables and their structures
    $required_tables = [
        'receipts' => "
            CREATE TABLE IF NOT EXISTS `receipts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `purchase_id` int(11) NOT NULL,
                `user_id` varchar(50) NOT NULL,
                `receipt_number` varchar(100) NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `product_title` varchar(255) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `receipt_number` (`receipt_number`),
                KEY `purchase_id` (`purchase_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'download_access' => "
            CREATE TABLE IF NOT EXISTS `download_access` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `purchase_id` int(11) NOT NULL,
                `user_id` varchar(50) NOT NULL,
                `product_id` int(11) NOT NULL,
                `access_granted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `expires_at` timestamp NULL DEFAULT NULL,
                `download_count` int(11) DEFAULT 0,
                `last_downloaded` timestamp NULL DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `purchase_id` (`purchase_id`),
                KEY `user_id` (`user_id`),
                KEY `product_id` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'admin_notifications' => "
            CREATE TABLE IF NOT EXISTS `admin_notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) DEFAULT NULL,
                `user_id` varchar(50) DEFAULT NULL,
                `type` varchar(50) NOT NULL,
                `message` text NOT NULL,
                `is_read` tinyint(1) DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                KEY `user_id` (`user_id`),
                KEY `type` (`type`),
                KEY `is_read` (`is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'payment_logs' => "
            CREATE TABLE IF NOT EXISTS `payment_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `reference` varchar(255) NOT NULL,
                `status` varchar(50) NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `currency` varchar(10) DEFAULT 'GHS',
                `gateway` varchar(50) DEFAULT 'paystack',
                `response_data` text,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `reference` (`reference`),
                KEY `order_id` (`order_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'purchase_logs' => "
            CREATE TABLE IF NOT EXISTS `purchase_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` varchar(50) DEFAULT NULL,
                `purchase_id` int(11) NOT NULL,
                `download_count` int(11) DEFAULT 1,
                `last_downloaded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `purchase_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `purchase_id` (`purchase_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];
    
    // 3. Create missing tables
    echo "2. CREATING MISSING TABLES:\n";
    echo "===========================\n";
    
    $tables_created = 0;
    
    foreach ($required_tables as $table_name => $create_sql) {
        if (!in_array($table_name, $existing_tables)) {
            try {
                $pdo->exec($create_sql);
                echo "✅ Created table: {$table_name}\n";
                $tables_created++;
            } catch (Exception $e) {
                echo "❌ Failed to create table {$table_name}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ Table already exists: {$table_name}\n";
        }
    }
    
    echo "\n";
    
    // 4. Verify table creation
    echo "3. VERIFYING TABLE CREATION:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $final_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Total tables after creation: " . count($final_tables) . "\n";
    
    $missing_tables = [];
    foreach (array_keys($required_tables) as $required_table) {
        if (!in_array($required_table, $final_tables)) {
            $missing_tables[] = $required_table;
        }
    }
    
    if (empty($missing_tables)) {
        echo "✅ All required tables are now available!\n";
    } else {
        echo "❌ Still missing tables: " . implode(', ', $missing_tables) . "\n";
    }
    
    echo "\n";
    
    // 5. Check table structures
    echo "4. CHECKING TABLE STRUCTURES:\n";
    echo "=============================\n";
    
    foreach (array_keys($required_tables) as $table_name) {
        if (in_array($table_name, $final_tables)) {
            try {
                $stmt = $pdo->query("DESCRIBE `{$table_name}`");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "✅ {$table_name}: " . count($columns) . " columns\n";
            } catch (Exception $e) {
                echo "❌ {$table_name}: Error checking structure - " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n";
    
    // 6. Summary
    echo "5. SUMMARY:\n";
    echo "===========\n";
    
    if ($tables_created > 0) {
        echo "✅ Successfully created {$tables_created} missing table(s)\n";
        echo "🎉 Your database is now ready for the comprehensive system fix!\n";
        echo "\n=== NEXT STEPS ===\n";
        echo "1. Run: php comprehensive_system_fix.php\n";
        echo "2. Run: php test_comprehensive_fixes.php\n";
        echo "3. Test user dashboard functionality\n";
    } else {
        echo "✅ All required tables were already present\n";
        echo "🎉 Your database is ready for the comprehensive system fix!\n";
        echo "\n=== NEXT STEPS ===\n";
        echo "1. Run: php comprehensive_system_fix.php\n";
        echo "2. Run: php test_comprehensive_fixes.php\n";
        echo "3. Test user dashboard functionality\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during table creation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
