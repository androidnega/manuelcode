<?php
// Super Admin - System Settings Page
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';

// Get current settings
try {
    $stmt = $pdo->query("SELECT setting_key, value FROM settings");
    $current_settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['setting_key']] = $row['value'];
    }
} catch (Exception $e) {
    $current_settings = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Settings - Super Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-blue-800 bg-clip-text text-transparent">
                        <i class="fas fa-cog text-gray-600 mr-3"></i>System Settings
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Configure API keys, URLs, and system settings</p>
                </div>
                <a href="../dashboard/superadmin" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-6 py-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                <i class="fas fa-cog text-gray-600 mr-3"></i>System Settings
            </h2>
            <div id="settings_result" class="text-sm text-gray-600 mb-4 p-3 bg-blue-50 rounded-lg">Load and update global API keys, URLs, and secrets.</div>
            
            <!-- Paystack Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-[#2D3E50] mb-3 flex items-center">
                    <i class="fas fa-credit-card text-green-600 mr-2"></i>Paystack Configuration
                </h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Test Public Key</label>
                            <input id="paystack_public_key" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="pk_test_..." value="<?php echo htmlspecialchars($current_settings['paystack_public_key'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Test Secret Key</label>
                            <input id="paystack_secret_key" type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="sk_test_..." value="<?php echo htmlspecialchars($current_settings['paystack_secret_key'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Live Public Key</label>
                            <input id="paystack_live_public_key" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="pk_live_..." value="<?php echo htmlspecialchars($current_settings['paystack_live_public_key'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Live Secret Key</label>
                            <input id="paystack_live_secret_key" type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="sk_live_..." value="<?php echo htmlspecialchars($current_settings['paystack_live_secret_key'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMS Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-[#2D3E50] mb-3 flex items-center">
                    <i class="fas fa-sms text-purple-600 mr-2"></i>SMS Configuration
                </h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Arkesel API Key</label>
                            <input id="arkassel_api_key" type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ark_..." value="<?php echo htmlspecialchars($current_settings['arkassel_api_key'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SMS Sender Name</label>
                            <input id="sms_sender_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ManuelCode" value="<?php echo htmlspecialchars($current_settings['sms_sender_name'] ?? 'ManuelCode'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cloudinary Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-[#2D3E50] mb-3 flex items-center">
                    <i class="fas fa-cloud text-blue-500 mr-2"></i>Cloudinary API Configuration
                </h3>
                <div class="space-y-3">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Configure Cloudinary for image and media management. Get your credentials from <a href="https://cloudinary.com" target="_blank" class="underline font-semibold">cloudinary.com</a>
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cloud Name</label>
                            <input id="cloudinary_cloud_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="your-cloud-name" value="<?php echo htmlspecialchars($current_settings['cloudinary_cloud_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                            <input id="cloudinary_api_key" type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="123456789012345" value="<?php echo htmlspecialchars($current_settings['cloudinary_api_key'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Secret</label>
                            <input id="cloudinary_api_secret" type="password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="••••••••••••••••" value="<?php echo htmlspecialchars($current_settings['cloudinary_api_secret'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Preset (Optional)</label>
                            <input id="cloudinary_upload_preset" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="unsigned_preset_name" value="<?php echo htmlspecialchars($current_settings['cloudinary_upload_preset'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="flex items-center">
                            <input type="checkbox" id="cloudinary_enabled" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo (isset($current_settings['cloudinary_enabled']) && $current_settings['cloudinary_enabled'] === '1') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700">Enable Cloudinary for image uploads</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Site Configuration -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-[#2D3E50] mb-3 flex items-center">
                    <i class="fas fa-globe text-blue-600 mr-2"></i>Site Configuration
                </h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site URL</label>
                            <input id="site_url" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://example.com" value="<?php echo htmlspecialchars($current_settings['site_url'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Download Token Secret</label>
                            <input id="download_token_secret" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="32-character secret key" value="<?php echo htmlspecialchars($current_settings['download_token_secret'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" onclick="saveSettings()" class="bg-[#2D3E50] hover:bg-[#536895] text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                <i class="fas fa-save mr-2"></i>Save All Settings
            </button>
        </div>
    </main>
</div>

<script>
async function saveSettings(){
  const resultDiv = document.getElementById('settings_result');
  resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Saving settings...</div>';
  
  const body = {
    cloudinary_cloud_name: document.getElementById('cloudinary_cloud_name').value,
    cloudinary_api_key: document.getElementById('cloudinary_api_key').value,
    cloudinary_api_secret: document.getElementById('cloudinary_api_secret').value,
    cloudinary_upload_preset: document.getElementById('cloudinary_upload_preset').value,
    cloudinary_enabled: document.getElementById('cloudinary_enabled').checked ? '1' : '0',
    site_url: document.getElementById('site_url').value,
    paystack_public_key: document.getElementById('paystack_public_key').value,
    paystack_secret_key: document.getElementById('paystack_secret_key').value,
    paystack_live_public_key: document.getElementById('paystack_live_public_key').value,
    paystack_live_secret_key: document.getElementById('paystack_live_secret_key').value,
    arkassel_api_key: document.getElementById('arkassel_api_key').value,
    sms_sender_name: document.getElementById('sms_sender_name').value,
    download_token_secret: document.getElementById('download_token_secret').value
  };
  
  try {
    const res = await fetch('superadmin_settings.php', {
      method: 'POST', 
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }, 
      body: JSON.stringify(body)
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const data = await res.json();
    
    if (data.success) {
      resultDiv.innerHTML = '<div class="text-green-600"><i class="fas fa-check mr-2"></i>Settings saved successfully!</div>';
      setTimeout(() => {
        resultDiv.innerHTML = '<div class="text-sm text-gray-600">Load and update global API keys, URLs, and secrets.</div>';
      }, 3000);
    } else {
      resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to save: ' + (data.error || 'Unknown error') + '</div>';
    }
  } catch (error) {
    resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Network error: ' + error.message + '</div>';
  }
}
</script>
</body>
</html>

