<?php
include 'includes/db.php';

echo "<h2>Creating Purchase Logs Table</h2>";

try {
    // Check if purchase_logs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'purchase_logs'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "<p>Creating purchase_logs table...</p>";
        
        // Create the purchase_logs table
        $sql = "
        CREATE TABLE IF NOT EXISTS purchase_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            purchase_id INT NOT NULL,
            download_count INT DEFAULT 1,
            last_downloaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
            UNIQUE KEY unique_purchase (purchase_id),
            INDEX idx_user_id (user_id),
            INDEX idx_purchase_id (purchase_id),
            INDEX idx_last_downloaded (last_downloaded)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ purchase_logs table created successfully!</p>";
        
    } else {
        echo "<p style='color: green;'>✅ purchase_logs table already exists!</p>";
    }
    
    // Check table structure
    echo "<h3>Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE purchase_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there are existing purchases that need to be added to purchase_logs
    echo "<h3>Checking Existing Purchases:</h3>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_purchases 
        FROM purchases 
        WHERE status = 'paid'
    ");
    $total_purchases = $stmt->fetchColumn();
    echo "<p>Total paid purchases: {$total_purchases}</p>";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_logs 
        FROM purchase_logs
    ");
    $total_logs = $stmt->fetchColumn();
    echo "<p>Total purchase logs: {$total_logs}</p>";
    
    if ($total_purchases > 0 && $total_logs == 0) {
        echo "<p>Adding existing purchases to purchase_logs...</p>";
        
        // Insert existing purchases into purchase_logs
        $sql = "
        INSERT IGNORE INTO purchase_logs (user_id, purchase_id, download_count, purchase_date)
        SELECT user_id, id, 1, created_at
        FROM purchases 
        WHERE status = 'paid'
        ";
        
        $pdo->exec($sql);
        $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
        echo "<p style='color: green;'>✅ Added {$affected} existing purchases to purchase_logs!</p>";
        
    } elseif ($total_logs > 0) {
        echo "<p style='color: green;'>✅ Purchase logs already exist!</p>";
    }
    
    // Show sample data
    echo "<h3>Sample Purchase Logs:</h3>";
    $stmt = $pdo->query("
        SELECT 
            pl.*,
            p.user_id as purchase_user_id,
            pr.title as product_title
        FROM purchase_logs pl
        LEFT JOIN purchases p ON pl.purchase_id = p.id
        LEFT JOIN products pr ON p.product_id = pr.id
        ORDER BY pl.created_at DESC
        LIMIT 10
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($logs)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Purchase ID</th><th>Product</th><th>Downloads</th><th>Last Download</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['user_id']}</td>";
            echo "<td>{$log['purchase_id']}</td>";
            echo "<td>{$log['product_title']}</td>";
            echo "<td>{$log['download_count']}</td>";
            echo "<td>{$log['last_downloaded']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No purchase logs found.</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. The purchase_logs table is now ready</p>";
    echo "<p>2. Download tracking will work for all new downloads</p>";
    echo "<p>3. Dashboard statistics should now show real-time data</p>";
    echo "<p>4. Test by downloading a product and checking the dashboard</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
