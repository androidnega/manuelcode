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
            // Detect cPanel/WHM servers
            $is_cpanel = (
                strpos($php_ini_path, '/opt/alt/') !== false || 
                strpos($php_ini_path, 'cpanel') !== false ||
                strpos($php_ini_path, '/usr/local/lib/php.ini') !== false ||
                file_exists('/usr/local/cpanel') ||
                isset($_SERVER['CPANEL']) ||
                function_exists('cpanel_version')
            );
            ?>
            
            <?php if ($is_cpanel || strpos($php_ini_path, '/opt/alt/') !== false): ?>
            <strong>🌐 You're on a Live Server (cPanel) - Here's how to fix it:</strong>
            <br><br>
            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <strong>📍 Your PHP Config:</strong> <code><?php echo htmlspecialchars($php_ini_path); ?></code>
                <br><small style="color: #666;">This file is managed by cPanel - you cannot edit it directly.</small>
            </div>
            
            <ol style="line-height: 2;">
                <li><strong>Method 1 - .htaccess (Easiest - Try this first):</strong>
                    <ul style="margin-top: 10px;">
                        <li>Go to <strong>cPanel → File Manager</strong> (or use FTP)</li>
                        <li>Navigate to your <strong>website root</strong> (usually <code>public_html</code> or <code>htdocs</code>)</li>
                        <li>Open or create <code>.htaccess</code> file</li>
                        <li>Add these lines at the <strong>top</strong> of the file:</li>
                        <li style="margin: 10px 0;">
                            <code style="display: block; background: #f4f4f4; padding: 10px; border-radius: 3px; margin-top: 5px;">
php_value upload_max_filesize 10M<br>
php_value post_max_size 10M<br>
php_value max_execution_time 300<br>
php_value memory_limit 256M
                            </code>
                        </li>
                        <li><strong>Save</strong> the file</li>
                        <li><strong>Refresh this page</strong> to verify changes (may take 1-2 minutes)</li>
                    </ul>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 3px; margin-top: 10px;">
                        <strong>⚠️ Note:</strong> If you get a 500 error after adding these lines, your host doesn't allow PHP overrides. Try Method 2 instead.
                    </div>
                </li>
                <li style="margin-top: 20px;"><strong>Method 2 - cPanel MultiPHP INI Editor:</strong>
                    <ul style="margin-top: 10px;">
                        <li>Login to <strong>cPanel</strong></li>
                        <li>Find <strong>"MultiPHP INI Editor"</strong> (usually under "Software" or "PHP" section)</li>
                        <li>Select your <strong>domain</strong> from the dropdown</li>
                        <li>Click <strong>"Editor Mode"</strong> tab</li>
                        <li>Find and change these values:</li>
                        <li style="margin: 10px 0;">
                            <code style="display: block; background: #f4f4f4; padding: 10px; border-radius: 3px; margin-top: 5px;">
upload_max_filesize = 10M<br>
post_max_size = 10M<br>
max_execution_time = 300<br>
memory_limit = 256M
                            </code>
                        </li>
                        <li>Click <strong>"Save"</strong></li>
                        <li>Wait <strong>2-5 minutes</strong> for changes to take effect</li>
                        <li>Refresh this page to verify</li>
                    </ul>
                </li>
                <li style="margin-top: 20px;"><strong>Method 3 - Contact Hosting Support:</strong>
                    <ul style="margin-top: 10px;">
                        <li>If Methods 1 and 2 don't work, contact your hosting provider</li>
                        <li>Ask them to increase PHP limits for your account:</li>
                        <li style="margin: 10px 0;">
                            <code style="display: block; background: #f4f4f4; padding: 10px; border-radius: 3px; margin-top: 5px;">
upload_max_filesize = 10M<br>
post_max_size = 10M<br>
max_execution_time = 300
                            </code>
                        </li>
                    </ul>
                </li>
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

