<?php
// Super Admin - System Cleanup Page
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Cleanup - Super Admin</title>
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
                        <i class="fas fa-broom text-orange-600 mr-3"></i>System Cleanup & Production Reset
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Reset system for production deployment</p>
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
                <i class="fas fa-broom text-red-600 mr-3"></i>System Cleanup & Production Reset
            </h2>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-1 mr-3"></i>
                    <div>
                        <h3 class="font-semibold text-red-800 mb-2">⚠️ Production Reset Warning</h3>
                        <p class="text-red-700 text-sm mb-3">
                            This action will <strong>permanently delete ALL data</strong> from the system except the super admin account. 
                            This is designed for preparing the system for production deployment.
                        </p>
                        <div class="text-red-700 text-sm space-y-1">
                            <div><strong>Will be deleted:</strong></div>
                            <ul class="list-disc list-inside ml-4 space-y-1">
                                <li>All user accounts and data</li>
                                <li>All orders and purchases</li>
                                <li>All guest orders</li>
                                <li>All support tickets and refunds</li>
                                <li>All admin accounts (except super admin)</li>
                                <li>All system logs and activity data</li>
                                <li>All SMS logs and OTP codes</li>
                                <li>All download logs and tokens</li>
                                <li>All notifications and preferences</li>
                            </ul>
                            <div class="mt-2"><strong>Will be preserved:</strong></div>
                            <ul class="list-disc list-inside ml-4">
                                <li>Super admin account</li>
                                <li>Products and categories</li>
                                <li>System settings and configuration</li>
                                <li>Database structure</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmation Code</label>
                    <input type="text" id="cleanup_confirmation" 
                           placeholder="Type 'PRODUCTION-READY' to confirm" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" />
                    <p class="text-xs text-gray-500 mt-1">Type exactly: PRODUCTION-READY</p>
                </div>
                <div class="flex items-end">
                    <button onclick="performSystemCleanup()" 
                            id="cleanup_btn"
                            disabled
                            class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center justify-center">
                        <i class="fas fa-broom mr-2"></i>
                        <span id="cleanup_btn_text">Perform System Cleanup</span>
                    </button>
                </div>
            </div>
            
            <div id="cleanup_result" class="text-sm mt-3"></div>
            
            <!-- Cleanup Progress -->
            <div id="cleanup_progress" class="hidden mt-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                        <span class="font-medium text-blue-800">System Cleanup in Progress...</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="cleanup_progress_bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div id="cleanup_status" class="text-sm text-blue-700 mt-2">Initializing cleanup...</div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmationInput = document.getElementById('cleanup_confirmation');
    const cleanupBtn = document.getElementById('cleanup_btn');
    
    if (confirmationInput && cleanupBtn) {
        confirmationInput.addEventListener('input', function() {
            cleanupBtn.disabled = this.value !== 'PRODUCTION-READY';
        });
    }
});

async function performSystemCleanup() {
    const confirmation = document.getElementById('cleanup_confirmation').value;
    const resultDiv = document.getElementById('cleanup_result');
    const progressDiv = document.getElementById('cleanup_progress');
    const progressBar = document.getElementById('cleanup_progress_bar');
    const statusDiv = document.getElementById('cleanup_status');
    const btn = document.getElementById('cleanup_btn');
    const btnText = document.getElementById('cleanup_btn_text');
    
    if (confirmation !== 'PRODUCTION-READY') {
        resultDiv.innerHTML = '<div class="text-red-600">Please type exactly: PRODUCTION-READY</div>';
        return;
    }
    
    if (!confirm('⚠️ FINAL WARNING: This will permanently delete ALL data except the super admin account. This action cannot be undone. Are you absolutely sure?')) {
        return;
    }
    
    progressDiv.classList.remove('hidden');
    resultDiv.innerHTML = '';
    btn.disabled = true;
    btnText.textContent = 'Cleaning System...';
    
    try {
        progressBar.style.width = '10%';
        statusDiv.textContent = 'Initializing system cleanup...';
        
        const response = await fetch('superadmin_tools.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=system_cleanup'
        });
        
        progressBar.style.width = '50%';
        statusDiv.textContent = 'Processing cleanup request...';
        
        const data = await response.json();
        
        progressBar.style.width = '100%';
        statusDiv.textContent = 'Cleanup completed!';
        
        if (data.success) {
            let resultsHtml = `
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                    <div class="font-bold">✅ System Cleanup Completed Successfully!</div>
                    <div class="text-sm mt-2">
                        <strong>Super Admin Preserved:</strong> ID ${data.super_admin_preserved || 'N/A'}
                    </div>
                </div>
            `;
            resultDiv.innerHTML = resultsHtml;
            document.getElementById('cleanup_confirmation').value = '';
            btn.disabled = true;
            btnText.textContent = 'System Cleaned';
            progressDiv.classList.add('hidden');
        } else {
            resultDiv.innerHTML = `<div class="text-red-600">Error: ${data.error || 'Unknown error'}</div>`;
            btn.disabled = false;
            btnText.textContent = 'Perform System Cleanup';
            progressDiv.classList.add('hidden');
        }
    } catch (error) {
        resultDiv.innerHTML = `<div class="text-red-600">Network error: ${error.message}</div>`;
        btn.disabled = false;
        btnText.textContent = 'Perform System Cleanup';
        progressDiv.classList.add('hidden');
    }
}
</script>
</body>
</html>

