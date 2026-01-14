<?php
/**
 * Simple test file to check if PHP is working
 * Upload this to your server and access: https://manuelcode.info/test_index.php
 */
echo "<h1>PHP is Working!</h1>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";

// Test if index.php exists
if (file_exists('index.php')) {
    echo "<p>✅ index.php exists</p>";
} else {
    echo "<p>❌ index.php NOT found</p>";
}

// Test if .htaccess exists
if (file_exists('.htaccess')) {
    echo "<p>✅ .htaccess exists</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents('.htaccess')) . "</pre>";
} else {
    echo "<p>❌ .htaccess NOT found</p>";
}
?>

