<?php
/**
 * Cloudinary Configuration Test Script
 * This script tests your Cloudinary configuration
 * Access: https://manuelcode.info/test_cloudinary.php
 */

session_start();
include 'includes/db.php';
include 'includes/cloudinary_helper.php';

// Check if user is superadmin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    die('Access denied. Super Admin only.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloudinary Configuration Test</title>
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
        h1 {
            color: #333;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 15px 0;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            padding: 15px;
            border-left: 4px solid #dc3545;
            margin: 15px 0;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
            color: #856404;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196f3;
            margin: 15px 0;
            color: #0c5460;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
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
        <h1>🔍 Cloudinary Configuration Test</h1>
        
        <?php
        // Get Cloudinary settings from database
        $stmt = $pdo->prepare("SELECT setting_key, value FROM settings WHERE setting_key LIKE 'cloudinary_%'");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['value'];
        }
        
        $cloudinaryHelper = new CloudinaryHelper($pdo);
        $isEnabled = $cloudinaryHelper->isEnabled();
        ?>
        
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td><strong>cloudinary_enabled</strong></td>
                <td><code><?php echo htmlspecialchars($settings['cloudinary_enabled'] ?? 'Not set'); ?></code></td>
                <td><?php echo (isset($settings['cloudinary_enabled']) && $settings['cloudinary_enabled'] === '1') ? '✅ Enabled' : '❌ Disabled'; ?></td>
            </tr>
            <tr>
                <td><strong>cloudinary_cloud_name</strong></td>
                <td><code><?php echo htmlspecialchars($settings['cloudinary_cloud_name'] ?? 'Not set'); ?></code></td>
                <td><?php echo !empty($settings['cloudinary_cloud_name']) ? '✅ Set' : '❌ Missing'; ?></td>
            </tr>
            <tr>
                <td><strong>cloudinary_api_key</strong></td>
                <td><code><?php echo !empty($settings['cloudinary_api_key']) ? '••••••••' . substr($settings['cloudinary_api_key'], -4) : 'Not set'; ?></code></td>
                <td><?php echo !empty($settings['cloudinary_api_key']) ? '✅ Set' : '❌ Missing'; ?></td>
            </tr>
            <tr>
                <td><strong>cloudinary_api_secret</strong></td>
                <td><code><?php echo !empty($settings['cloudinary_api_secret']) ? '••••••••' . substr($settings['cloudinary_api_secret'], -4) : 'Not set'; ?></code></td>
                <td><?php echo !empty($settings['cloudinary_api_secret']) ? '✅ Set' : '❌ Missing'; ?></td>
            </tr>
            <tr>
                <td><strong>cloudinary_upload_preset</strong></td>
                <td><code><?php echo htmlspecialchars($settings['cloudinary_upload_preset'] ?? 'Not set'); ?></code></td>
                <td><?php echo !empty($settings['cloudinary_upload_preset']) ? '✅ Set' : '⚠️ Optional'; ?></td>
            </tr>
        </table>
        
        <?php if ($isEnabled): ?>
        <div class="success">
            <strong>✅ Cloudinary is Enabled</strong>
            <p>Cloudinary helper reports that Cloudinary is enabled and configured.</p>
        </div>
        
        <?php
        // Test upload configuration
        $hasUploadPreset = !empty($settings['cloudinary_upload_preset']);
        $hasApiCredentials = !empty($settings['cloudinary_api_key']) && !empty($settings['cloudinary_api_secret']);
        
        if ($hasUploadPreset) {
            echo '<div class="info">';
            echo '<strong>📤 Upload Method:</strong> Using Upload Preset (Unsigned Upload)';
            echo '<p>Your uploads will use the preset: <code>' . htmlspecialchars($settings['cloudinary_upload_preset']) . '</code></p>';
            echo '</div>';
        } elseif ($hasApiCredentials) {
            echo '<div class="info">';
            echo '<strong>📤 Upload Method:</strong> Using API Key + Secret (Signed Upload)';
            echo '<p>Your uploads will be signed with your API credentials.</p>';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<strong>❌ Upload Configuration Error:</strong>';
            echo '<p>You need either:</p>';
            echo '<ul>';
            echo '<li>An Upload Preset (recommended for unsigned uploads), OR</li>';
            echo '<li>Both API Key and API Secret (for signed uploads)</li>';
            echo '</ul>';
            echo '<p>Please configure one of these options in <a href="dashboard/system-settings">System Settings</a>.</p>';
            echo '</div>';
        }
        ?>
        
        <?php else: ?>
        <div class="error">
            <strong>❌ Cloudinary is Not Enabled or Not Configured</strong>
            <p>Please enable Cloudinary and configure your credentials in <a href="dashboard/system-settings">System Settings</a>.</p>
        </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 20px;">
            <strong>📝 Configuration Notes:</strong>
            <ul>
                <li><strong>Upload Preset Method:</strong> Easier to set up, allows unsigned uploads. Create a preset in your Cloudinary dashboard.</li>
                <li><strong>API Key + Secret Method:</strong> More secure, requires signing each upload. Use this if you don't have an upload preset.</li>
                <li>You only need ONE of these methods, not both.</li>
                <li>Make sure your Cloud Name is correct - this is found in your Cloudinary dashboard.</li>
            </ul>
        </div>
    </div>
</body>
</html>

