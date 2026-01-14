<?php
/**
 * Quick Error Check - Upload this to your live server root
 * Access via: https://manuelcode.info/error_check.php
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Error Check - ManuelCode.info</h1>";
echo "<hr>";

// Check PHP version
echo "<h2>PHP Version: " . phpversion() . "</h2>";

// Check if .htaccess exists
echo "<h2>File Checks:</h2>";
$files = ['.htaccess', 'index.php', 'dashboard/router.php', 'includes/db.php'];
foreach ($files as $file) {
    $exists = file_exists($file) ? '✅' : '❌';
    echo "$exists $file<br>";
}

// Check .htaccess syntax
echo "<h2>.htaccess Check:</h2>";
if (file_exists('.htaccess')) {
    $htaccess = file_get_contents('.htaccess');
    echo "<pre>" . htmlspecialchars($htaccess) . "</pre>";
} else {
    echo "❌ .htaccess not found<br>";
}

// Test database connection
echo "<h2>Database Connection:</h2>";
if (file_exists('includes/db.php')) {
    try {
        include 'includes/db.php';
        if (isset($pdo)) {
            echo "✅ Database connection successful<br>";
            echo "Host: " . (isset($host) ? $host : 'N/A') . "<br>";
            echo "Database: " . (isset($dbname) ? $dbname : 'N/A') . "<br>";
        } else {
            echo "❌ PDO object not created<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ includes/db.php not found<br>";
}

// Test router.php
echo "<h2>Router Test:</h2>";
if (file_exists('dashboard/router.php')) {
    try {
        // Simulate a route request
        $_GET['route'] = 'index';
        ob_start();
        include 'dashboard/router.php';
        $output = ob_get_clean();
        echo "✅ Router executed (output length: " . strlen($output) . " bytes)<br>";
    } catch (Exception $e) {
        echo "❌ Router error: " . $e->getMessage() . "<br>";
    } catch (Error $e) {
        echo "❌ Router PHP error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ dashboard/router.php not found<br>";
}

// Check Apache mod_rewrite
echo "<h2>Server Info:</h2>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";

echo "<hr>";
echo "<p><strong>Note:</strong> Delete this file after checking!</p>";
?>

