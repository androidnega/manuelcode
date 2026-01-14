<?php
/**
 * Update Database Configuration - ManuelCode.info
 * This script helps you update the database connection to point to your live server
 */

echo "<h1>🔧 Update Database Configuration - ManuelCode.info</h1>";
echo "<p>This will help you connect to your live server database with 80 submissions...</p>";

// Check current configuration
echo "<h2>📋 Current Database Configuration</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Current Settings (from includes/db.php):</h3>";

if (file_exists('includes/db.php')) {
    $db_content = file_get_contents('includes/db.php');
    
    // Extract current values
    preg_match('/\$host = "([^"]+)"/', $db_content, $host_match);
    preg_match('/\$dbname = "([^"]+)"/', $db_content, $dbname_match);
    preg_match('/\$username = "([^"]+)"/', $db_content, $username_match);
    preg_match('/\$password = "([^"]+)"/', $db_content, $password_match);
    
    $current_host = $host_match[1] ?? 'Not found';
    $current_dbname = $dbname_match[1] ?? 'Not found';
    $current_username = $username_match[1] ?? 'Not found';
    $current_password = $password_match[1] ?? 'Not found';
    
    echo "<p><strong>Host:</strong> $current_host</p>";
    echo "<p><strong>Database:</strong> $current_dbname</p>";
    echo "<p><strong>Username:</strong> $current_username</p>";
    echo "<p><strong>Password:</strong> " . str_repeat('*', strlen($current_password)) . "</p>";
    
    if ($current_host === 'localhost') {
        echo "<div style='background: #ffebee; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "⚠️ <strong>Issue Found:</strong> Currently connected to localhost. You need to connect to your live server!";
        echo "</div>";
    }
} else {
    echo "<p>❌ Database configuration file not found!</p>";
}

echo "</div>";

// Test current connection
echo "<h2>📋 Test Current Connection</h2>";
try {
    include 'includes/db.php';
    echo "✅ Current connection successful<br>";
    
    // Check submissions count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions WHERE status = 'paid'");
    $count = $stmt->fetch()['total'];
    echo "📊 Current submissions count: <strong>$count</strong><br>";
    
    if ($count == 0) {
        echo "<div style='background: #ffebee; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "❌ <strong>Problem:</strong> No submissions found! This confirms you're connected to the wrong database.";
        echo "</div>";
    } elseif ($count == 80) {
        echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "✅ <strong>Perfect:</strong> Found 80 submissions! You're already connected to the right database.";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "⚠️ <strong>Warning:</strong> Found $count submissions, but you expected 80.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Current connection failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Update configuration form
echo "<h2>📋 Update Database Configuration</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $new_host = trim($_POST['host']);
    $new_dbname = trim($_POST['dbname']);
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    
    if ($new_host && $new_dbname && $new_username) {
        try {
            // Test new connection first
            $test_pdo = new PDO("mysql:host=$new_host;dbname=$new_dbname;charset=utf8", $new_username, $new_password);
            $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check submissions count in new database
            $stmt = $test_pdo->query("SELECT COUNT(*) as total FROM submissions WHERE status = 'paid'");
            $new_count = $stmt->fetch()['total'];
            
            echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>🔍 Testing New Connection...</h3>";
            echo "<p>✅ Connection successful to: $new_host / $new_dbname</p>";
            echo "<p>📊 Submissions found: <strong>$new_count</strong></p>";
            
            if ($new_count == 80) {
                echo "<p style='color: green; font-weight: bold;'>🎉 Perfect! This database has your 80 submissions!</p>";
                
                // Update the configuration file
                $new_config = "<?php
\$host = \"$new_host\";
\$dbname = \"$new_dbname\";
\$username = \"$new_username\";
\$password = \"$new_password\";

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    // Check if this is an API request (JSON content type expected)
    if (isset(\$_SERVER['HTTP_ACCEPT']) && strpos(\$_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . \$e->getMessage()]);
        exit;
    } else {
        die(\"DB Connection failed: \" . \$e->getMessage());
    }
}

// Include auto configuration and logging
include_once __DIR__ . '/auto_config.php';
include_once __DIR__ . '/logger.php';

// Log database connection (only if not in API context)
if (!defined('API_CONTEXT')) {
    log_system(\"Database connection established\", ['host' => \$host, 'database' => \$dbname]);
}
?>";
                
                // Backup current config
                $backup_file = 'includes/db_backup_' . date('Y-m-d_H-i-s') . '.php';
                file_put_contents($backup_file, file_get_contents('includes/db.php'));
                echo "<p>💾 Backed up current config to: $backup_file</p>";
                
                // Write new config
                if (file_put_contents('includes/db.php', $new_config)) {
                    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h3>✅ Configuration Updated Successfully!</h3>";
                    echo "<p>Your database configuration has been updated to connect to your live server.</p>";
                    echo "<p><strong>New Settings:</strong></p>";
                    echo "<ul>";
                    echo "<li>Host: $new_host</li>";
                    echo "<li>Database: $new_dbname</li>";
                    echo "<li>Username: $new_username</li>";
                    echo "</ul>";
                    echo "<p><a href='analyst/dashboard.php' target='_blank' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Test Dashboard Now</a></p>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h3>❌ Failed to Update Configuration</h3>";
                    echo "<p>Could not write to includes/db.php. Please check file permissions.</p>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>⚠️ Wrong Database</h3>";
                echo "<p>This database has $new_count submissions, but you need 80. Please check your database details.</p>";
                echo "</div>";
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>❌ Connection Failed</h3>";
            echo "<p>Could not connect to the new database: " . $e->getMessage() . "</p>";
            echo "<p>Please check your database credentials.</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Missing Information</h3>";
        echo "<p>Please fill in all required fields.</p>";
        echo "</div>";
    }
}

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 Live Server Database Details</h3>";
echo "<p>You need to provide your <strong>live server</strong> database details:</p>";
echo "<ul>";
echo "<li><strong>Host:</strong> Usually your domain name or server IP (e.g., manuelcode.info, or your server IP)</li>";
echo "<li><strong>Database Name:</strong> The name of your live database (might be different from 'manuela')</li>";
echo "<li><strong>Username:</strong> Your live database username</li>";
echo "<li><strong>Password:</strong> Your live database password</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> This information is usually found in your hosting control panel (cPanel, etc.)</p>";
echo "</div>";

echo "<form method='POST' style='background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📝 Enter Live Server Database Details</h3>";
echo "<table>";
echo "<tr><td>Host:</td><td><input type='text' name='host' value='manuelcode.info' required style='width: 300px; padding: 8px;'></td></tr>";
echo "<tr><td>Database Name:</td><td><input type='text' name='dbname' value='manuela' required style='width: 300px; padding: 8px;'></td></tr>";
echo "<tr><td>Username:</td><td><input type='text' name='username' required style='width: 300px; padding: 8px;'></td></tr>";
echo "<tr><td>Password:</td><td><input type='password' name='password' required style='width: 300px; padding: 8px;'></td></tr>";
echo "<tr><td colspan='2'><br><button type='submit' name='update_config' style='padding: 12px 24px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;'>Update Database Configuration</button></td></tr>";
echo "</table>";
echo "</form>";

// Alternative: Manual configuration
echo "<h2>📋 Alternative: Manual Configuration</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 Manual Steps:</h3>";
echo "<ol>";
echo "<li><strong>Find your live server database details</strong> in your hosting control panel</li>";
echo "<li><strong>Edit includes/db.php</strong> and update these lines:</li>";
echo "<ul>";
echo "<li><code>\$host = \"your-live-server-host\";</code></li>";
echo "<li><code>\$dbname = \"your-live-database-name\";</code></li>";
echo "<li><code>\$username = \"your-live-username\";</code></li>";
echo "<li><code>\$password = \"your-live-password\";</code></li>";
echo "</ul>";
echo "<li><strong>Save the file</strong> and test the dashboard</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "</p>";

echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "⚠️ <strong>Security Note:</strong> Remove this file after successfully updating your database configuration.";
echo "</div>";
?>
