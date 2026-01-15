<?php
/**
 * Database Configuration Deployment Script
 * Run this on your live server to create includes/db.php
 * Upload this file to your server root and access via browser
 */

// Security: Only allow if accessed directly
if (php_sapi_name() !== 'cli' && !isset($_GET['deploy'])) {
    die('Access denied. Add ?deploy=yes to the URL to proceed.');
}

if (isset($_GET['deploy']) && $_GET['deploy'] === 'yes') {
    $db_config = '<?php
$host = "localhost";
$dbname = "manuelc8_db";
$username = "manuelc8_user";
$password = "Atomic2@2020^";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Check if this is an API request (JSON content type expected)
    if (isset($_SERVER[\'HTTP_ACCEPT\']) && strpos($_SERVER[\'HTTP_ACCEPT\'], \'application/json\') !== false) {
        header(\'Content-Type: application/json\');
        echo json_encode([\'success\' => false, \'error\' => \'Database connection failed: \' . $e->getMessage()]);
        exit;
    } else {
        die("DB Connection failed: " . $e->getMessage());
    }
}

// Include auto configuration and logging
include_once __DIR__ . \'/auto_config.php\';
include_once __DIR__ . \'/logger.php\';

// Log database connection (only if not in API context)
if (!defined(\'API_CONTEXT\')) {
    log_system("Database connection established", [\'host\' => $host, \'database\' => $dbname]);
}
?>';

    // Create includes directory if it doesn't exist
    if (!is_dir('includes')) {
        mkdir('includes', 0755, true);
        echo "<p>✅ Created 'includes' directory</p>";
    }

    // Write the database configuration file
    $file_path = 'includes/db.php';
    if (file_put_contents($file_path, $db_config)) {
        echo "<h1 style='color: green;'>✅ SUCCESS!</h1>";
        echo "<p><strong>Database configuration file created at: $file_path</strong></p>";
        
        // Test the connection
        echo "<hr><h2>Testing Database Connection...</h2>";
        try {
            include $file_path;
            if (isset($pdo)) {
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                if ($result) {
                    echo "<p style='color: green; font-size: 18px;'>✅✅✅ DATABASE CONNECTION SUCCESSFUL! ✅✅✅</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ File created but connection test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Please verify your database credentials are correct.</p>";
        }
        
        echo "<hr>";
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ol>";
        echo "<li>Test your homepage: <a href='index.php'>https://manuelcode.info</a></li>";
        echo "<li>Delete this file (deploy_db_config.php) for security</li>";
        echo "<li>Delete test files (test_db.php, test_index.php, error_check.php) if uploaded</li>";
        echo "</ol>";
    } else {
        echo "<h1 style='color: red;'>❌ ERROR</h1>";
        echo "<p>Could not write to $file_path</p>";
        echo "<p>Please check file permissions. The 'includes' directory needs to be writable (755 or 775).</p>";
    }
} else {
    echo "<h1>Database Configuration Deployment</h1>";
    echo "<p>This script will create <code>includes/db.php</code> with your database credentials.</p>";
    echo "<p><a href='?deploy=yes' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Deploy Database Configuration</a></p>";
    echo "<hr>";
    echo "<p><strong>Security Note:</strong> Delete this file after deployment!</p>";
}
?>



