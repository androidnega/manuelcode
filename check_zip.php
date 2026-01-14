<?php
// Simple ZIP Status Checker for cPanel
echo "<h2>🔍 ZIP Extension Status Check</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 2px solid #ccc; border-radius: 10px;'>";

// Check ZIP extension
if (extension_loaded('zip')) {
    echo "✅ <strong>ZIP extension is LOADED</strong><br>";
} else {
    echo "❌ <strong>ZIP extension is NOT loaded</strong><br>";
}

// Check ZipArchive class
if (class_exists('ZipArchive')) {
    echo "✅ <strong>ZipArchive class is AVAILABLE</strong><br>";
} else {
    echo "❌ <strong>ZipArchive class is NOT available</strong><br>";
}

// Show PHP version
echo "📊 <strong>PHP Version:</strong> " . phpversion() . "<br>";

// Overall status
if (class_exists('ZipArchive') && extension_loaded('zip')) {
    echo "<br>🎉 <strong>ZIP functionality is FULLY AVAILABLE</strong><br>";
    echo "Your submission system will work with ZIP downloads!";
} else {
    echo "<br>⚠️ <strong>ZIP functionality is LIMITED</strong><br>";
    echo "Your submission system will use fallback downloads.<br>";
    echo "This is still fully functional for your event!";
}

echo "</div>";
?>
