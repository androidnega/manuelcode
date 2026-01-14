<?php
/**
 * ZIP Extension Enable Helper for XAMPP
 * This script helps diagnose and enable ZIP functionality
 */

echo "<h2>ZIP Extension Status Check</h2>";

// Check if ZIP extension is loaded
if (extension_loaded('zip')) {
    echo "<p style='color: green;'>✅ ZIP extension is loaded and working!</p>";
} else {
    echo "<p style='color: red;'>❌ ZIP extension is NOT loaded</p>";
    
    // Check if extension file exists
    $php_version = PHP_VERSION;
    $ext_dir = ini_get('extension_dir');
    
    echo "<h3>Diagnostic Information:</h3>";
    echo "<p><strong>PHP Version:</strong> {$php_version}</p>";
    echo "<p><strong>Extension Directory:</strong> {$ext_dir}</p>";
    
    // Check for zip.dll on Windows
    $zip_dll = $ext_dir . '/php_zip.dll';
    if (file_exists($zip_dll)) {
        echo "<p style='color: green;'>✅ php_zip.dll found at: {$zip_dll}</p>";
    } else {
        echo "<p style='color: red;'>❌ php_zip.dll NOT found at: {$zip_dll}</p>";
    }
    
    echo "<h3>To Enable ZIP Extension:</h3>";
    echo "<ol>";
    echo "<li>Open <strong>php.ini</strong> file in your XAMPP installation</li>";
    echo "<li>Find the line: <code>;extension=zip</code> (it's commented out with semicolon)</li>";
    echo "<li>Remove the semicolon to make it: <code>extension=zip</code></li>";
    echo "<li>Save the file</li>";
    echo "<li>Restart Apache server</li>";
    echo "</ol>";
    
    echo "<p><strong>php.ini location:</strong> Usually found at:</p>";
    echo "<ul>";
    echo "<li><code>C:\\xampp\\php\\php.ini</code></li>";
    echo "</ul>";
    
    echo "<h3>Alternative: Manual Installation</h3>";
    echo "<p>If php_zip.dll is missing:</p>";
    echo "<ol>";
    echo "<li>Download php_zip.dll for your PHP version</li>";
    echo "<li>Place it in the extensions directory</li>";
    echo "<li>Add <code>extension=zip</code> to php.ini</li>";
    echo "<li>Restart Apache</li>";
    echo "</ol>";
}

// Show current loaded extensions
echo "<h3>Currently Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<div style='max-height: 200px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;'>";
foreach ($extensions as $ext) {
    echo "{$ext}<br>";
}
echo "</div>";

// Check php.ini location
echo "<h3>PHP Configuration Files:</h3>";
echo "<p><strong>Loaded php.ini:</strong> " . php_ini_loaded_file() . "</p>";
echo "<p><strong>Additional php.ini files:</strong> " . php_ini_scanned_files() . "</p>";

echo "<h3>Test ZIP Functionality:</h3>";
if (class_exists('ZipArchive')) {
    echo "<p style='color: green;'>✅ ZipArchive class is available</p>";
    
    // Test creating a simple ZIP
    try {
        $test_file = tempnam(sys_get_temp_dir(), 'test_zip');
        $zip = new ZipArchive();
        if ($zip->open($test_file, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('test.txt', 'This is a test file');
            $zip->close();
            echo "<p style='color: green;'>✅ ZIP creation test successful</p>";
            unlink($test_file); // Clean up
        } else {
            echo "<p style='color: red;'>❌ ZIP creation test failed</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ZIP test error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ ZipArchive class is NOT available</p>";
}
?>
