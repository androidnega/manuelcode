<?php
/**
 * Direct Cloudinary Test Upload - Standalone Diagnostic Tool
 * Access: https://manuelcode.info/test_cloudinary_direct.php
 * This file tests Cloudinary upload directly without routing
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

// Check if user is superadmin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    die('Access denied. Please login as Super Admin first: <a href="/admin">Login</a>');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloudinary Direct Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 15px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Cloudinary Direct Test & Diagnostic Tool</h1>
        
        <?php
        // Step 1: Check includes
        echo "<h2>Step 1: Checking Required Files</h2>";
        $base_dir = __DIR__;
        $db_file = $base_dir . '/includes/db.php';
        $cloudinary_file = $base_dir . '/includes/cloudinary_helper.php';
        
        $errors = [];
        
        if (!file_exists($db_file)) {
            echo "<div class='error'>❌ db.php not found at: <code>$db_file</code></div>";
            $errors[] = "db.php missing";
        } else {
            echo "<div class='success'>✅ db.php found at: <code>$db_file</code></div>";
        }
        
        if (!file_exists($cloudinary_file)) {
            echo "<div class='error'>❌ cloudinary_helper.php not found at: <code>$cloudinary_file</code></div>";
            $errors[] = "cloudinary_helper.php missing";
        } else {
            echo "<div class='success'>✅ cloudinary_helper.php found at: <code>$cloudinary_file</code></div>";
        }
        
        if (!empty($errors)) {
            echo "<div class='error'><strong>Cannot proceed - missing required files</strong></div>";
            exit;
        }
        
        // Step 2: Include files
        echo "<h2>Step 2: Loading Files</h2>";
        try {
            require_once $db_file;
            echo "<div class='success'>✅ db.php loaded successfully</div>";
            
            if (!isset($pdo)) {
                throw new Exception("Database connection (\$pdo) not established");
            }
            echo "<div class='success'>✅ Database connection established</div>";
            
            require_once $cloudinary_file;
            echo "<div class='success'>✅ cloudinary_helper.php loaded successfully</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error loading files: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        } catch (Error $e) {
            echo "<div class='error'>❌ Fatal error loading files: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        }
        
        // Step 3: Check Cloudinary Configuration
        echo "<h2>Step 3: Checking Cloudinary Configuration</h2>";
        try {
            $cloudinaryHelper = new CloudinaryHelper($pdo);
            
            // Get settings directly from database
            $stmt = $pdo->prepare("SELECT setting_key, value FROM settings WHERE setting_key LIKE 'cloudinary_%'");
            $stmt->execute();
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['value'];
            }
            
            echo "<div class='info'>";
            echo "<strong>Cloudinary Settings from Database:</strong><br>";
            echo "cloudinary_enabled: <code>" . ($settings['cloudinary_enabled'] ?? 'not set') . "</code><br>";
            echo "cloudinary_cloud_name: <code>" . ($settings['cloudinary_cloud_name'] ?? 'not set') . "</code><br>";
            echo "cloudinary_api_key: <code>" . (!empty($settings['cloudinary_api_key']) ? '***' . substr($settings['cloudinary_api_key'], -4) : 'not set') . "</code><br>";
            echo "cloudinary_api_secret: <code>" . (!empty($settings['cloudinary_api_secret']) ? '***' . substr($settings['cloudinary_api_secret'], -4) : 'not set') . "</code><br>";
            echo "cloudinary_upload_preset: <code>" . ($settings['cloudinary_upload_preset'] ?? 'not set') . "</code><br>";
            echo "</div>";
            
            $isEnabled = $cloudinaryHelper->isEnabled();
            if ($isEnabled) {
                echo "<div class='success'>✅ Cloudinary is enabled</div>";
            } else {
                echo "<div class='error'>❌ Cloudinary is NOT enabled or not properly configured</div>";
            }
            
            // Check if we have upload method
            $hasPreset = !empty($settings['cloudinary_upload_preset']);
            $hasCredentials = !empty($settings['cloudinary_api_key']) && !empty($settings['cloudinary_api_secret']);
            
            if ($hasPreset) {
                echo "<div class='success'>✅ Upload method: Using Upload Preset (unsigned upload)</div>";
            } elseif ($hasCredentials) {
                echo "<div class='success'>✅ Upload method: Using API Key + Secret (signed upload)</div>";
            } else {
                echo "<div class='error'>❌ No upload method configured! You need either:</div>";
                echo "<ul>";
                echo "<li>An Upload Preset (recommended), OR</li>";
                echo "<li>Both API Key and API Secret</li>";
                echo "</ul>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error checking Cloudinary: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        
        // Step 4: Handle file upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
            echo "<h2>Step 4: Testing Upload</h2>";
            
            try {
                if ($_FILES['test_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Upload error code: " . $_FILES['test_file']['error']);
                }
                
                if (!file_exists($_FILES['test_file']['tmp_name'])) {
                    throw new Exception("Temporary file not found");
                }
                
                echo "<div class='info'>File info:<br>";
                echo "Name: <code>" . htmlspecialchars($_FILES['test_file']['name']) . "</code><br>";
                echo "Size: <code>" . number_format($_FILES['test_file']['size'] / 1024, 2) . " KB</code><br>";
                echo "Type: <code>" . htmlspecialchars($_FILES['test_file']['type']) . "</code><br>";
                echo "Temp path: <code>" . htmlspecialchars($_FILES['test_file']['tmp_name']) . "</code><br>";
                echo "</div>";
                
                $uploadResult = $cloudinaryHelper->uploadImage(
                    $_FILES['test_file']['tmp_name'],
                    'test',
                    [
                        'public_id' => 'test_' . time(),
                        'overwrite' => true
                    ]
                );
                
                if ($uploadResult && isset($uploadResult['url'])) {
                    echo "<div class='success'>";
                    echo "<strong>✅ Upload Successful!</strong><br>";
                    echo "URL: <a href='" . htmlspecialchars($uploadResult['url']) . "' target='_blank'>" . htmlspecialchars($uploadResult['url']) . "</a><br>";
                    echo "Public ID: <code>" . htmlspecialchars($uploadResult['public_id']) . "</code><br>";
                    if (isset($uploadResult['width'])) {
                        echo "Dimensions: <code>" . $uploadResult['width'] . " x " . $uploadResult['height'] . "px</code><br>";
                    }
                    echo "<br><img src='" . htmlspecialchars($uploadResult['url']) . "' style='max-width: 500px; border: 1px solid #ddd; border-radius: 5px;'>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "<strong>❌ Upload Failed</strong><br>";
                    echo "Result: <pre>" . print_r($uploadResult, true) . "</pre>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<strong>❌ Upload Error:</strong><br>";
                echo htmlspecialchars($e->getMessage()) . "<br>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</div>";
            }
        }
        ?>
        
        <h2>Test Upload Form</h2>
        <form method="POST" enctype="multipart/form-data" class="card">
            <div style="margin-bottom: 15px;">
                <label><strong>Select Test Image:</strong></label><br>
                <input type="file" name="test_file" accept="image/*" required style="margin-top: 10px; padding: 10px; width: 100%; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            <button type="submit">Upload Test Image</button>
        </form>
        
        <div class="info">
            <strong>📝 Notes:</strong>
            <ul>
                <li>This test bypasses the router and tests Cloudinary directly</li>
                <li>Check the diagnostic output above to identify any issues</li>
                <li>If upload fails, check PHP error logs for detailed error messages</li>
                <li>Make sure you have either Upload Preset OR API Key + Secret configured</li>
            </ul>
        </div>
    </div>
</body>
</html>

