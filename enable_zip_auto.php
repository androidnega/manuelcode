<?php
/**
 * Automated ZIP Extension Enabler for XAMPP
 * This script will automatically enable the ZIP extension in php.ini
 */

echo "<h2>Automated ZIP Extension Enabler</h2>";

// Check if script is running with admin privileges (for file writing)
if (!is_writable(php_ini_loaded_file())) {
    echo "<p style='color: red;'>❌ Cannot modify php.ini automatically. You need to do this manually.</p>";
    echo "<h3>Manual Steps:</h3>";
    echo "<ol>";
    echo "<li>Open this file in Notepad: <strong>C:\\xampp\\php\\php.ini</strong></li>";
    echo "<li>Press <strong>Ctrl+F</strong> and search for: <code>;extension=zip</code></li>";
    echo "<li>Remove the semicolon (;) at the beginning of the line</li>";
    echo "<li>The line should become: <code>extension=zip</code></li>";
    echo "<li>Save the file (Ctrl+S)</li>";
    echo "<li>Restart Apache from XAMPP Control Panel</li>";
    echo "</ol>";
    
    echo "<h3>Alternative Method:</h3>";
    echo "<p>You can also edit php.ini through XAMPP Control Panel:</p>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Config' button for Apache</li>";
    echo "<li>Select 'PHP (php.ini)'</li>";
    echo "<li>Search for ';extension=zip' and uncomment it</li>";
    echo "<li>Save and restart Apache</li>";
    echo "</ol>";
    
    exit;
}

$php_ini_path = php_ini_loaded_file();
echo "<p><strong>PHP.ini location:</strong> {$php_ini_path}</p>";

// Read the current php.ini file
$php_ini_content = file_get_contents($php_ini_path);

// Check if ZIP extension is already enabled
if (strpos($php_ini_content, 'extension=zip') !== false) {
    echo "<p style='color: green;'>✅ ZIP extension is already enabled in php.ini</p>";
} elseif (strpos($php_ini_content, ';extension=zip') !== false) {
    // Enable the ZIP extension
    $new_content = str_replace(';extension=zip', 'extension=zip', $php_ini_content);
    
    // Write the modified content back to php.ini
    if (file_put_contents($php_ini_path, $new_content)) {
        echo "<p style='color: green;'>✅ ZIP extension has been enabled in php.ini</p>";
        echo "<p><strong>Next step:</strong> Please restart Apache server from XAMPP Control Panel</p>";
        
        echo "<h3>How to restart Apache:</h3>";
        echo "<ol>";
        echo "<li>Go to XAMPP Control Panel</li>";
        echo "<li>Click 'Stop' button for Apache (if running)</li>";
        echo "<li>Click 'Start' button for Apache</li>";
        echo "</ol>";
        
        echo "<p><strong>After restarting Apache, you can test ZIP functionality by visiting:</strong></p>";
        echo "<p><a href='enable_zip.php' style='color: blue;'>http://localhost/manuela/enable_zip.php</a></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to modify php.ini file. Please do it manually.</p>";
        echo "<p>Search for <code>;extension=zip</code> in the file and remove the semicolon.</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ ZIP extension line not found in php.ini</p>";
    echo "<p>You may need to add <code>extension=zip</code> manually to the file.</p>";
}

// Show current status
echo "<h3>Current Status:</h3>";
if (extension_loaded('zip')) {
    echo "<p style='color: green;'>✅ ZIP extension is loaded and working!</p>";
} else {
    echo "<p style='color: red;'>❌ ZIP extension is NOT loaded (Apache restart required)</p>";
}

if (class_exists('ZipArchive')) {
    echo "<p style='color: green;'>✅ ZipArchive class is available</p>";
} else {
    echo "<p style='color: red;'>❌ ZipArchive class is NOT available (Apache restart required)</p>";
}
?>
