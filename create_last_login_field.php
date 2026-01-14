<?php
include 'includes/db.php';

echo "<h2>Adding Last Login Field to Users Table</h2>";

try {
    // Check if last_login field exists
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    if (!in_array('last_login', $column_names)) {
        echo "<p>Adding last_login field to users table...</p>";
        
        // Add last_login field
        $sql = "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER updated_at";
        $pdo->exec($sql);
        
        echo "<p style='color: green;'>✅ last_login field added successfully!</p>";
        
        // Add index for better performance
        $sql = "ALTER TABLE users ADD INDEX idx_last_login (last_login)";
        $pdo->exec($sql);
        
        echo "<p style='color: green;'>✅ Index added for last_login field!</p>";
        
    } else {
        echo "<p style='color: green;'>✅ last_login field already exists!</p>";
    }
    
    // Check current table structure
    echo "<h3>Current Users Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
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
    
    // Check if there are any existing users with last_login data
    echo "<h3>Existing Users Last Login Data:</h3>";
    $stmt = $pdo->query("
        SELECT id, name, email, created_at, last_login 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th><th>Last Login</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "<td>" . ($user['last_login'] ? $user['last_login'] : 'Never') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found.</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. The last_login field is now ready</p>";
    echo "<p>2. Update the OTP login system to record login timestamps</p>";
    echo "<p>3. Test user login to verify last_login is updated</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
