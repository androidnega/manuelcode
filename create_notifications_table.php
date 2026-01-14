<?php
include 'includes/db.php';

echo "<h2>Creating Notifications Table</h2>";

try {
    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "<p>Creating notifications table...</p>";
        
        // Create the notifications table
        $sql = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'general',
            related_id INT NULL,
            related_type VARCHAR(50) NULL,
            data JSON NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at),
            INDEX idx_related (related_id, related_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ notifications table created successfully!</p>";
        
    } else {
        echo "<p style='color: green;'>✅ notifications table already exists!</p>";
    }
    
    // Check table structure
    echo "<h3>Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE notifications");
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
    
    // Check if there are existing notifications
    echo "<h3>Existing Notifications:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $total_notifications = $stmt->fetchColumn();
    echo "<p>Total notifications: {$total_notifications}</p>";
    
    if ($total_notifications > 0) {
        // Show sample notifications
        $stmt = $pdo->query("
            SELECT * FROM notifications 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Type</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['id']}</td>";
            echo "<td>{$notification['user_id']}</td>";
            echo "<td>{$notification['title']}</td>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$notification['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the notification system
    echo "<h3>Testing Notification System:</h3>";
    
    // Check if we have any users and products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetchColumn();
    echo "<p>Total users: {$total_users}</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $total_products = $stmt->fetchColumn();
    echo "<p>Total products: {$total_products}</p>";
    
    if ($total_users > 0 && $total_products > 0) {
        // Get a sample user and product
        $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
        $sample_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT id, title FROM products LIMIT 1");
        $sample_product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample_user && $sample_product) {
            echo "<p>Sample user ID: {$sample_user['id']}</p>";
            echo "<p>Sample product: {$sample_product['title']} (ID: {$sample_product['id']})</p>";
            
            // Test creating a notification
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (
                        user_id, title, message, type, related_id, related_type, data
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $test_data = [
                    'type' => 'test_notification',
                    'message' => 'This is a test notification'
                ];
                
                $stmt->execute([
                    $sample_user['id'],
                    'Test Notification',
                    'This is a test notification to verify the system works.',
                    'test',
                    $sample_product['id'],
                    'product',
                    json_encode($test_data)
                ]);
                
                echo "<p style='color: green;'>✅ Test notification created successfully!</p>";
                
                // Clean up test notification
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE type = 'test'");
                $stmt->execute();
                echo "<p style='color: blue;'>ℹ️ Test notification cleaned up.</p>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error creating test notification: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. The notifications table is now ready</p>";
    echo "<p>2. Product update notifications will be sent automatically</p>";
    echo "<p>3. Users will receive notifications about changes to their purchased products</p>";
    echo "<p>4. Test by updating a product in the admin panel</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
