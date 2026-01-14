<?php
/**
 * Check .htaccess and File Permissions - ManuelCode.info
 * 
 * This script checks .htaccess file permissions and other critical files
 * that might be causing HTTP 500 errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Check .htaccess and File Permissions - ManuelCode.info</h1>";
echo "<p>Checking file permissions that might be causing HTTP 500 errors...</p>";

// =====================================================
// 1. CHECK .HTACCESS FILES
// =====================================================

echo "<h2>📋 1. .htaccess Files Check</h2>";

$htaccess_files = [
    '.htaccess',
    'admin/.htaccess',
    'auth/.htaccess',
    'dashboard/.htaccess'
];

foreach ($htaccess_files as $htaccess) {
    if (file_exists($htaccess)) {
        $perms = fileperms($htaccess);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        $size = filesize($htaccess);
        $modified = date('Y-m-d H:i:s', filemtime($htaccess));
        
        echo "✅ $htaccess exists<br>";
        echo "   - Permissions: $perms_octal<br>";
        echo "   - Size: {$size} bytes<br>";
        echo "   - Modified: $modified<br>";
        
        // Check if readable
        if (is_readable($htaccess)) {
            echo "   - ✅ Readable<br>";
        } else {
            echo "   - ❌ Not readable<br>";
        }
        
        // Show first few lines
        $content = file_get_contents($htaccess);
        $lines = explode("\n", $content);
        $first_lines = array_slice($lines, 0, 5);
        
        echo "   - <strong>First 5 lines:</strong><br>";
        echo "   <div style='background: #f5f5f5; padding: 5px; margin: 5px 0; font-family: monospace; font-size: 11px;'>";
        foreach ($first_lines as $line) {
            echo "   " . htmlspecialchars($line) . "<br>";
        }
        echo "   </div>";
        
    } else {
        echo "❌ $htaccess missing<br>";
    }
    echo "<br>";
}

// =====================================================
// 2. CHECK CRITICAL FILE PERMISSIONS
// =====================================================

echo "<h2>📋 2. Critical File Permissions Check</h2>";

$critical_files = [
    'includes/db.php',
    'auth/otp_login.php',
    'admin/auth/login.php',
    'index.php',
    'store.php',
    'projects.php'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        $size = filesize($file);
        
        echo "✅ $file<br>";
        echo "   - Permissions: $perms_octal<br>";
        echo "   - Size: {$size} bytes<br>";
        
        // Check if readable
        if (is_readable($file)) {
            echo "   - ✅ Readable<br>";
        } else {
            echo "   - ❌ Not readable<br>";
        }
        
        // Check if executable (for PHP files)
        if (is_executable($file)) {
            echo "   - ✅ Executable<br>";
        } else {
            echo "   - ⚠️ Not executable<br>";
        }
        
    } else {
        echo "❌ $file missing<br>";
    }
    echo "<br>";
}

// =====================================================
// 3. CHECK DIRECTORY PERMISSIONS
// =====================================================

echo "<h2>📋 3. Directory Permissions Check</h2>";

$critical_dirs = [
    'includes',
    'auth',
    'admin',
    'admin/auth',
    'assets',
    'uploads'
];

foreach ($critical_dirs as $dir) {
    if (is_dir($dir)) {
        $perms = fileperms($dir);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        
        echo "✅ $dir/ (directory)<br>";
        echo "   - Permissions: $perms_octal<br>";
        
        // Check if readable
        if (is_readable($dir)) {
            echo "   - ✅ Readable<br>";
        } else {
            echo "   - ❌ Not readable<br>";
        }
        
        // Check if executable (needed to access directory)
        if (is_executable($dir)) {
            echo "   - ✅ Executable (accessible)<br>";
        } else {
            echo "   - ❌ Not executable (not accessible)<br>";
        }
        
        // Check if writable (for uploads)
        if (is_writable($dir)) {
            echo "   - ✅ Writable<br>";
        } else {
            echo "   - ⚠️ Not writable<br>";
        }
        
    } else {
        echo "❌ $dir/ missing<br>";
    }
    echo "<br>";
}

// =====================================================
// 4. COMMON PERMISSION ISSUES
// =====================================================

echo "<h2>📋 4. Common Permission Issues</h2>";

echo "<h3>🔧 .htaccess Issues:</h3>";
echo "<ul>";
echo "<li><strong>Wrong permissions:</strong> .htaccess should be 644 (rw-r--r--)</li>";
echo "<li><strong>Not readable:</strong> Server can't read .htaccess rules</li>";
echo "<li><strong>Syntax errors:</strong> Invalid Apache directives</li>";
echo "<li><strong>Missing file:</strong> No .htaccess file uploaded</li>";
echo "</ul>";

echo "<h3>🔧 PHP File Issues:</h3>";
echo "<ul>";
echo "<li><strong>Wrong permissions:</strong> PHP files should be 644 (rw-r--r--)</li>";
echo "<li><strong>Not readable:</strong> Server can't read PHP files</li>";
echo "<li><strong>Not executable:</strong> Server can't execute PHP files</li>";
echo "</ul>";

echo "<h3>🔧 Directory Issues:</h3>";
echo "<ul>";
echo "<li><strong>Wrong permissions:</strong> Directories should be 755 (rwxr-xr-x)</li>";
echo "<li><strong>Not executable:</strong> Can't access directory contents</li>";
echo "<li><strong>Not writable:</strong> Can't upload files or create logs</li>";
echo "</ul>";

// =====================================================
// 5. FIX PERMISSIONS COMMANDS
// =====================================================

echo "<h2>📋 5. Fix Permissions Commands</h2>";

echo "<h3>🔧 For .htaccess files:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "chmod 644 .htaccess\n";
echo "chmod 644 admin/.htaccess\n";
echo "chmod 644 auth/.htaccess\n";
echo "chmod 644 dashboard/.htaccess\n";
echo "</pre>";

echo "<h3>🔧 For PHP files:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "chmod 644 *.php\n";
echo "chmod 644 includes/*.php\n";
echo "chmod 644 auth/*.php\n";
echo "chmod 644 admin/*.php\n";
echo "chmod 644 admin/auth/*.php\n";
echo "</pre>";

echo "<h3>🔧 For directories:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "chmod 755 includes/\n";
echo "chmod 755 auth/\n";
echo "chmod 755 admin/\n";
echo "chmod 755 admin/auth/\n";
echo "chmod 755 assets/\n";
echo "chmod 755 uploads/\n";
echo "</pre>";

echo "<h3>🔧 For upload directories (writable):</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "chmod 777 uploads/\n";
echo "chmod 777 assets/uploads/\n";
echo "</pre>";

// =====================================================
// 6. TEST .HTACCESS CONTENT
// =====================================================

echo "<h2>📋 6. Test .htaccess Content</h2>";

if (file_exists('.htaccess')) {
    $htaccess_content = file_get_contents('.htaccess');
    
    echo "<h3>📄 Root .htaccess content:</h3>";
    echo "<div style='background: #f5f5f5; padding: 10px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars($htaccess_content);
    echo "</div>";
    
    // Check for common issues
    echo "<h3>🔍 Common .htaccess Issues:</h3>";
    
    if (strpos($htaccess_content, 'RewriteEngine On') !== false) {
        echo "✅ RewriteEngine On found<br>";
    } else {
        echo "❌ RewriteEngine On missing<br>";
    }
    
    if (strpos($htaccess_content, 'RewriteCond') !== false) {
        echo "✅ RewriteCond rules found<br>";
    } else {
        echo "⚠️ No RewriteCond rules found<br>";
    }
    
    if (strpos($htaccess_content, 'RewriteRule') !== false) {
        echo "✅ RewriteRule found<br>";
    } else {
        echo "⚠️ No RewriteRule found<br>";
    }
    
} else {
    echo "❌ Root .htaccess file missing<br>";
}

// =====================================================
// 7. SUMMARY AND RECOMMENDATIONS
// =====================================================

echo "<h2>📋 7. Summary and Recommendations</h2>";

echo "<h3>🎯 Most Common HTTP 500 Causes:</h3>";
echo "<ol>";
echo "<li><strong>.htaccess permissions:</strong> Should be 644</li>";
echo "<li><strong>PHP file permissions:</strong> Should be 644</li>";
echo "<li><strong>Directory permissions:</strong> Should be 755</li>";
echo "<li><strong>Database connection:</strong> Wrong credentials</li>";
echo "<li><strong>Missing files:</strong> Critical files not uploaded</li>";
echo "</ol>";

echo "<h3>🔧 Quick Fix Steps:</h3>";
echo "<ol>";
echo "<li>Set .htaccess permissions to 644</li>";
echo "<li>Set PHP file permissions to 644</li>";
echo "<li>Set directory permissions to 755</li>";
echo "<li>Check database credentials</li>";
echo "<li>Test the website again</li>";
echo "</ol>";

echo "<h3>🔗 Test Links:</h3>";
echo "<ul>";
echo "<li><a href='auth/otp_login.php' target='_blank'>Test OTP Login</a></li>";
echo "<li><a href='admin/auth/login.php' target='_blank'>Test Admin Login</a></li>";
echo "<li><a href='index.php' target='_blank'>Test Homepage</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
?>
