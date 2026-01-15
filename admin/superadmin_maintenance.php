<?php
// Super Admin - Maintenance Mode Page
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Mode - Super Admin</title>
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
                        <i class="fas fa-tools text-yellow-600 mr-3"></i>Site Maintenance Mode
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">Manage site maintenance and access modes</p>
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
                <i class="fas fa-tools text-yellow-600 mr-3"></i>Site Maintenance Mode
            </h2>
            
            <!-- Current Status -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <h3 class="font-medium text-gray-700 mb-2">Current Site Status</h3>
                <div id="current_status" class="text-sm text-gray-600">Loading...</div>
            </div>
            
            <!-- Mode Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <button onclick="setSiteMode('standard')" class="mode-btn bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 p-3 rounded-lg text-center transition-colors">
                    <i class="fas fa-check-circle text-xl mb-2 text-emerald-600"></i>
                    <div class="font-medium">Standard Mode</div>
                    <div class="text-xs opacity-80">Site fully operational</div>
                </button>
                
                <button onclick="setSiteMode('maintenance')" class="mode-btn bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 p-3 rounded-lg text-center transition-colors">
                    <i class="fas fa-wrench text-xl mb-2 text-rose-600"></i>
                    <div class="font-medium">Maintenance Mode</div>
                    <div class="text-xs opacity-80">Site under maintenance</div>
                </button>
                
                <button onclick="setSiteMode('coming_soon')" class="mode-btn bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 p-3 rounded-lg text-center transition-colors">
                    <i class="fas fa-clock text-xl mb-2 text-amber-600"></i>
                    <div class="font-medium">Coming Soon</div>
                    <div class="text-xs opacity-80">Site launching soon</div>
                </button>
                
                <button onclick="setSiteMode('update')" class="mode-btn bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 p-3 rounded-lg text-center transition-colors">
                    <i class="fas fa-sync-alt text-xl mb-2 text-sky-600"></i>
                    <div class="font-medium">Update Mode</div>
                    <div class="text-xs opacity-80">Site being updated</div>
                </button>
            </div>
            
            <!-- Custom Message -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Custom Message (Optional)</label>
                    <textarea id="maintenance_message" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Enter a custom message to display to visitors..."></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start (Optional)</label>
                        <input type="datetime-local" id="maintenance_start" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End (Optional)</label>
                        <input type="datetime-local" id="maintenance_end" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo URL (Optional)</label>
                            <input type="url" id="maintenance_logo" placeholder="https://..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Icon (Font Awesome class)</label>
                            <input type="text" id="maintenance_icon" placeholder="fas fa-wrench" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button onclick="applyMaintenanceMode()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-1"></i>Apply Mode
                </button>
                <button onclick="refreshMaintenanceStatus()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-refresh mr-1"></i>Refresh Status
                </button>
            </div>
            
            <div id="maintenance_result" class="text-sm mt-3"></div>
        </div>
    </main>
</div>

<script>
async function refreshMaintenanceStatus() {
    const statusDiv = document.getElementById('current_status');
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
    
    try {
        const response = await fetch('superadmin_tools.php?action=get_maintenance_status');
        const data = await response.json();
        
        if (data.success) {
            statusDiv.innerHTML = `<span class="font-semibold">${data.mode || 'standard'}</span> - ${data.message || 'No custom message'}`;
        } else {
            statusDiv.innerHTML = '<span class="text-red-600">Error loading status</span>';
        }
    } catch (error) {
        statusDiv.innerHTML = '<span class="text-red-600">Error: ' + error.message + '</span>';
    }
}

function setSiteMode(mode) {
    document.getElementById('maintenance_message').value = '';
    document.getElementById('maintenance_start').value = '';
    document.getElementById('maintenance_end').value = '';
    document.getElementById('maintenance_logo').value = '';
    document.getElementById('maintenance_icon').value = '';
}

async function applyMaintenanceMode() {
    const resultDiv = document.getElementById('maintenance_result');
    resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Applying mode...</div>';
    
    const mode = document.querySelector('.mode-btn.bg-emerald-100, .mode-btn.bg-rose-100, .mode-btn.bg-amber-100, .mode-btn.bg-sky-100');
    const selectedMode = mode ? (mode.textContent.includes('Standard') ? 'standard' : 
                                 mode.textContent.includes('Maintenance') ? 'maintenance' :
                                 mode.textContent.includes('Coming') ? 'coming_soon' : 'update') : 'standard';
    
    const body = {
        mode: selectedMode,
        message: document.getElementById('maintenance_message').value,
        start: document.getElementById('maintenance_start').value,
        end: document.getElementById('maintenance_end').value,
        logo: document.getElementById('maintenance_logo').value,
        icon: document.getElementById('maintenance_icon').value
    };
    
    try {
        const response = await fetch('superadmin_tools.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=set_maintenance_mode&${new URLSearchParams(body).toString()}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = '<div class="text-green-600"><i class="fas fa-check mr-2"></i>Maintenance mode updated successfully!</div>';
            refreshMaintenanceStatus();
        } else {
            resultDiv.innerHTML = '<div class="text-red-600">Error: ' + (data.error || 'Unknown error') + '</div>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<div class="text-red-600">Network error: ' + error.message + '</div>';
    }
}

// Load status on page load
refreshMaintenanceStatus();
</script>
</body>
</html>

