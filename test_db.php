<?php
/**
 * Database Connection Test
 * Upload to server and access: https://manuelcode.info/test_db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

if (file_exists('includes/db.php')) {
    echo "<p>✅ includes/db.php found</p>";
    
    try {
        include 'includes/db.php';
        
        if (isset($pdo)) {
            echo "<p>✅ PDO object created</p>";
            
            // Test query
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            if ($result) {
                echo "<p style='color: green; font-size: 20px;'>✅✅✅ DATABASE CONNECTION SUCCESSFUL! ✅✅✅</p>";
                echo "<p>Host: " . (isset($host) ? htmlspecialchars($host) : 'N/A') . "</p>";
                echo "<p>Database: " . (isset($dbname) ? htmlspecialchars($dbname) : 'N/A') . "</p>";
                echo "<p>Username: " . (isset($username) ? htmlspecialchars($username) : 'N/A') . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Query failed</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ PDO object not created</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red; font-size: 18px;'>❌❌❌ DATABASE CONNECTION FAILED! ❌❌❌</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>This is likely the cause of your HTTP 500 error!</strong></p>";
        echo "<hr>";
        echo "<h2>How to Fix:</h2>";
        echo "<ol>";
        echo "<li>Edit <code>includes/db.php</code> on your server</li>";
        echo "<li>Update the database credentials:</li>";
        echo "<ul>";
        echo "<li><code>\$host</code> - Usually 'localhost' on cPanel</li>";
        echo "<li><code>\$dbname</code> - Your database name from cPanel</li>";
        echo "<li><code>\$username</code> - Your database username from cPanel</li>";
        echo "<li><code>\$password</code> - Your database password from cPanel</li>";
        echo "</ul>";
        echo "<li>Save the file and refresh this page</li>";
        echo "</ol>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ includes/db.php NOT FOUND!</p>";
    echo "<p>You need to create this file with your database credentials.</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> Delete this file after testing!</p>";
?>

