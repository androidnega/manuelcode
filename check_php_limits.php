<?php
/**
 * Quick PHP Limits Checker
 * This script shows your current PHP upload limits
 * Access: https://manuelcode.info/check_php_limits.php
 */

// Helper function to convert PHP ini size values to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Upload Limits Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196f3;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 15px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 PHP Upload Limits Checker</h1>
        
        <?php
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $memory_limit = ini_get('memory_limit');
        $max_execution_time = ini_get('max_execution_time');
        
        $upload_max_bytes = return_bytes($upload_max);
        $post_max_bytes = return_bytes($post_max);
        $max_allowed = min($upload_max_bytes, $post_max_bytes);
        $max_allowed_mb = round($max_allowed / (1024 * 1024), 2);
        ?>
        
        <table>
            <tr>
                <th>Setting</th>
                <th>Current Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td><strong>upload_max_filesize</strong></td>
                <td><code><?php echo $upload_max; ?></code></td>
                <td><?php echo ($upload_max_bytes >= 10 * 1024 * 1024) ? '✅ OK' : '⚠️ Too Low'; ?></td>
            </tr>
            <tr>
                <td><strong>post_max_size</strong></td>
                <td><code><?php echo $post_max; ?></code></td>
                <td><?php echo ($post_max_bytes >= 10 * 1024 * 1024) ? '✅ OK' : '⚠️ Too Low'; ?></td>
            </tr>
            <tr>
                <td><strong>memory_limit</strong></td>
                <td><code><?php echo $memory_limit; ?></code></td>
                <td><?php echo (return_bytes($memory_limit) >= 128 * 1024 * 1024) ? '✅ OK' : '⚠️ Consider increasing'; ?></td>
            </tr>
            <tr>
                <td><strong>max_execution_time</strong></td>
                <td><code><?php echo $max_execution_time; ?> seconds</code></td>
                <td><?php echo ($max_execution_time >= 300) ? '✅ OK' : '⚠️ Consider increasing'; ?></td>
            </tr>
        </table>
        
        <div class="info">
            <strong>📊 Maximum File Size You Can Upload:</strong> <code><?php echo $max_allowed_mb; ?>MB</code>
        </div>
        
        <?php if ($max_allowed_mb < 10): ?>
        <div class="warning">
            <strong>⚠️ Warning:</strong> Your PHP upload limit is too low for uploading 10MB files.
            <br><br>
            <?php
            $php_ini_path = php_ini_loaded_file();
            $is_cpanel = strpos($php_ini_path, '/opt/alt/') !== false || strpos($php_ini_path, 'cpanel') !== false;
            ?>
            
            <?php if ($is_cpanel): ?>
            <strong>To fix this (cPanel/Live Server):</strong>
            <ol>
                <li><strong>Method 1 - .htaccess (Recommended):</strong>
                    <ul>
                        <li>Edit <code>.htaccess</code> in your website root</li>
                        <li>Add: <code>php_value upload_max_filesize 10M</code></li>
                        <li>Add: <code>php_value post_max_size 10M</code></li>
                        <li>Save and refresh this page</li>
                    </ul>
                </li>
                <li><strong>Method 2 - cPanel MultiPHP INI Editor:</strong>
                    <ul>
                        <li>Login to cPanel → MultiPHP INI Editor</li>
                        <li>Select your domain → Editor Mode</li>
                        <li>Change <code>upload_max_filesize</code> to <code>10M</code></li>
                        <li>Change <code>post_max_size</code> to <code>10M</code></li>
                        <li>Save and wait a few minutes</li>
                    </ul>
                </li>
                <li><strong>Method 3:</strong> Contact your hosting provider to increase PHP limits</li>
            </ol>
            <?php else: ?>
            <strong>To fix this (XAMPP/Local):</strong>
            <ol>
                <li>Open <code><?php echo $php_ini_path; ?></code> in Notepad (Run as Administrator)</li>
                <li>Press <kbd>Ctrl+F</kbd> and search for <code>upload_max_filesize</code></li>
                <li>Change <code>upload_max_filesize = <?php echo $upload_max; ?></code> to <code>upload_max_filesize = 10M</code></li>
                <li>Search for <code>post_max_size</code> and change it to <code>post_max_size = 10M</code></li>
                <li>Save the file (Ctrl+S)</li>
                <li>Restart your web server (Apache/Nginx)</li>
                <li>Refresh this page to verify the changes</li>
            </ol>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="success">
            <strong>✅ Good!</strong> Your PHP upload limits are sufficient for uploading files up to 10MB.
        </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 20px;">
            <strong>📝 PHP Configuration File Location:</strong><br>
            <code><?php echo php_ini_loaded_file(); ?></code>
            <?php if (php_ini_scanned_files()): ?>
                <br><br><strong>Additional .ini files:</strong><br>
                <code><?php echo php_ini_scanned_files(); ?></code>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

