<?php
/**
 * Live Server Diagnostic Tool - ManuelCode.info
 * Upload this file to your live server to diagnose HTTP 500 errors
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 ManuelCode.info - Live Server Diagnostic</h1>";
echo "<p>Checking for issues causing HTTP 500 errors...</p>";
echo "<hr>";

// =====================================================
// 1. PHP ENVIRONMENT CHECK
// =====================================================
echo "<h2>📋 1. PHP Environment Check</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring'];
echo "<strong>Required Extensions:</strong><br>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "$status $ext<br>";
}

// =====================================================
// 2. FILE PERMISSIONS & STRUCTURE CHECK
// =====================================================
echo "<h2>📋 2. File Permissions & Structure Check</h2>";

$critical_files = [
    'includes/db.php',
    'includes/auto_config.php',
    'includes/logger.php',
    'includes/otp_helper.php',
    'auth/otp_login.php',
    '.htaccess'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "✅ $file (permissions: $perms)<br>";
    } else {
        echo "❌ $file (MISSING)<br>";
    }
}

// =====================================================
// 3. DATABASE CONNECTION TEST
// =====================================================
echo "<h2>📋 3. Database Connection Test</h2>";

try {
    if (file_exists('includes/db.php')) {
        echo "✅ Database file exists<br>";
        
        // Capture any errors from including the file
        ob_start();
        $error_occurred = false;
        
        try {
            include_once 'includes/db.php';
            echo "✅ Database file included successfully<br>";
        } catch (Exception $e) {
            $error_occurred = true;
            echo "❌ Error including database file: " . $e->getMessage() . "<br>";
        } catch (Error $e) {
            $error_occurred = true;
            echo "❌ PHP Error in database file: " . $e->getMessage() . "<br>";
        }
        
        $output = ob_get_clean();
        echo $output;
        
        if (!$error_occurred && isset($pdo)) {
            echo "✅ PDO object created<br>";
            
            // Test connection
            try {
                $stmt = $pdo->query("SELECT 1");
                echo "✅ Database connection successful<br>";
                
                // Get database name
                $stmt = $pdo->query("SELECT DATABASE() as db_name");
                $db_info = $stmt->fetch();
                echo "✅ Connected to database: " . $db_info['db_name'] . "<br>";
                
                // Check table count
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                echo "✅ Tables found: " . count($tables) . "<br>";
                
                // Check specific tables
                $important_tables = ['users', 'products', 'purchases', 'admins', 'settings', 'otp_codes'];
                foreach ($important_tables as $table) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = $stmt->fetch()['count'];
                        echo "✅ Table '$table': $count records<br>";
                    } catch (Exception $e) {
                        echo "❌ Table '$table': " . $e->getMessage() . "<br>";
                    }
                }
                
            } catch (Exception $e) {
                echo "❌ Database query failed: " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "❌ PDO object not created<br>";
        }
        
    } else {
        echo "❌ Database file 'includes/db.php' not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

// =====================================================
// 4. SPECIFIC FILE CHECKS
// =====================================================
echo "<h2>📋 4. Specific File Checks</h2>";

// Check otp_login.php syntax
echo "<strong>Checking auth/otp_login.php:</strong><br>";
if (file_exists('auth/otp_login.php')) {
    echo "✅ File exists<br>";
    
    // Check for syntax errors without executing
    $file_content = file_get_contents('auth/otp_login.php');
    if ($file_content !== false) {
        echo "✅ File readable<br>";
        
        // Basic syntax check
        if (strpos($file_content, '<?php') !== false) {
            echo "✅ PHP opening tag found<br>";
        } else {
            echo "❌ No PHP opening tag found<br>";
        }
        
        // Check for common issues
        if (strpos($file_content, 'include') !== false || strpos($file_content, 'require') !== false) {
            echo "✅ Include/require statements found<br>";
        }
        
    } else {
        echo "❌ File not readable<br>";
    }
} else {
    echo "❌ auth/otp_login.php not found<br>";
}

// =====================================================
// 5. ERROR LOG CHECK
// =====================================================
echo "<h2>📋 5. Error Log Check</h2>";

$error_logs = [
    'error_log',
    'auth/error_log',
    'includes/error_log',
    'admin/error_log'
];

foreach ($error_logs as $log_file) {
    if (file_exists($log_file)) {
        $size = filesize($log_file);
        $modified = date('Y-m-d H:i:s', filemtime($log_file));
        echo "📄 $log_file (size: $size bytes, modified: $modified)<br>";
        
        if ($size > 0 && $size < 10000) { // Only show if reasonable size
            $content = file_get_contents($log_file);
            $lines = explode("\n", $content);
            $recent_lines = array_slice($lines, -10); // Last 10 lines
            
            echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
            echo htmlspecialchars(implode("\n", $recent_lines));
            echo "</pre>";
        }
    }
}

// =====================================================
// 6. HTACCESS CHECK
// =====================================================
echo "<h2>📋 6. .htaccess Check</h2>";

if (file_exists('.htaccess')) {
    echo "✅ .htaccess file exists<br>";
    $htaccess_content = file_get_contents('.htaccess');
    if (strlen($htaccess_content) > 0) {
        echo "✅ .htaccess has content (" . strlen($htaccess_content) . " characters)<br>";
        
        // Check for common directives
        if (strpos($htaccess_content, 'RewriteEngine') !== false) {
            echo "✅ RewriteEngine directive found<br>";
        }
        if (strpos($htaccess_content, 'DirectoryIndex') !== false) {
            echo "✅ DirectoryIndex directive found<br>";
        }
    } else {
        echo "⚠️ .htaccess file is empty<br>";
    }
} else {
    echo "❌ .htaccess file missing<br>";
}

// =====================================================
// 7. MEMORY AND LIMITS CHECK
// =====================================================
echo "<h2>📋 7. PHP Limits Check</h2>";
echo "<strong>Memory Limit:</strong> " . ini_get('memory_limit') . "<br>";
echo "<strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds<br>";
echo "<strong>Upload Max Filesize:</strong> " . ini_get('upload_max_filesize') . "<br>";
echo "<strong>Post Max Size:</strong> " . ini_get('post_max_size') . "<br>";

// =====================================================
// 8. QUICK FIX SUGGESTIONS
// =====================================================
echo "<h2>📋 8. Quick Fix Suggestions</h2>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 Common Solutions:</h3>";
echo "<ul>";
echo "<li><strong>Database Credentials:</strong> Update includes/db.php with correct cPanel credentials</li>";
echo "<li><strong>Missing Files:</strong> Ensure all files are uploaded from your local environment</li>";
echo "<li><strong>File Permissions:</strong> Set directories to 755 and files to 644</li>";
echo "<li><strong>Missing Tables:</strong> Import the production_database_complete.sql file</li>";
echo "<li><strong>.htaccess Issues:</strong> Check for syntax errors in .htaccess</li>";
echo "</ul>";
echo "</div>";

// =====================================================
// 9. NEXT STEPS
// =====================================================
echo "<h2>📋 9. Next Steps</h2>";
echo "<ol>";
echo "<li>Review the error logs above for specific PHP errors</li>";
echo "<li>If database connection failed, update includes/db.php with correct credentials</li>";
echo "<li>If files are missing, upload them from your local environment</li>";
echo "<li>If tables are missing, import database/production_database_complete.sql</li>";
echo "<li>Test the specific page again: <a href='auth/otp_login.php'>auth/otp_login.php</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Server:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "⚠️ <strong>Security Note:</strong> Remove this diagnostic file after troubleshooting for security.";
echo "</div>";
?>
