<?php
// Super Admin Dashboard
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';

// Load basic stats
try { 
    $total_orders = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM purchases WHERE status = 'paid'
            UNION ALL
            SELECT id FROM guest_orders WHERE status = 'paid'
        ) as combined_orders
    ")->fetchColumn(); 
} catch (Exception $e) { 
    $total_orders = 0; 
}
try { $total_downloads = (int)$pdo->query("SELECT COUNT(*) FROM downloads")->fetchColumn(); } catch (Exception $e) { $total_downloads = 0; }
try { 
    $total_sms = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM sms_logs
            UNION ALL
            SELECT id FROM otp_codes
        ) as combined_sms
    ")->fetchColumn(); 
} catch (Exception $e) { 
    $total_sms = 0; 
}
try { $total_logs = (int)$pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn(); } catch (Exception $e) { $total_logs = 0; }
try { $total_super_admins = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn(); } catch (Exception $e) { $total_super_admins = 0; }
try { $active_users = (int)$pdo->query("SELECT COUNT(*) FROM user_sessions WHERE is_active = 1")->fetchColumn(); } catch (Exception $e) { $active_users = 0; }

// Load SMS statistics from sms_logs table (including OTPs)
try { 
    $today_sms = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT created_at FROM sms_logs WHERE DATE(created_at) = CURDATE()
            UNION ALL
            SELECT created_at FROM otp_codes WHERE DATE(created_at) = CURDATE()
        ) as combined_sms
    ")->fetchColumn(); 
} catch (Exception $e) { 
    $today_sms = 0; 
}
try { 
    $week_sms = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT created_at FROM sms_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT created_at FROM otp_codes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) as combined_sms
    ")->fetchColumn(); 
} catch (Exception $e) { 
    $week_sms = 0; 
}
try { 
    $month_sms = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT created_at FROM sms_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT created_at FROM otp_codes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as combined_sms
    ")->fetchColumn(); 
} catch (Exception $e) { 
    $month_sms = 0; 
}



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
    <title>Super Admin - System Control</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-blue-800 bg-clip-text text-transparent">
                        <i class="fas fa-toolbox mr-3 text-blue-600"></i>Super Admin Control Center
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">System overview and management dashboard</p>
                </div>
                <a href="auth/logout.php" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-xl transition-all duration-200 flex items-center shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
        <div class="px-6 py-8">
         <?php if (isset($_GET['access_required'])): ?>
     <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
         <div class="flex items-center">
             <i class="fas fa-exclamation-triangle mr-2"></i>
             <div>
                 <strong>Access Required:</strong> You need to generate or verify an access code to view admin pages (orders, users, etc.).
                 <div class="text-sm mt-1">Use the "Super Admin Access Control" section below to get access.</div>
             </div>
         </div>
     </div>
     <?php endif; ?>

    <!-- Stats Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
        <!-- Total Orders Card -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl shadow-sm border border-blue-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-shopping-cart text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-blue-600 opacity-80">Total Orders</div>
                    <div class="text-2xl font-bold text-blue-800"><?php echo number_format($total_orders); ?></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-blue-200">
                <div class="text-xs text-blue-600 opacity-70">All time purchases</div>
            </div>
        </div>
        
        <!-- Downloads Card -->
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 rounded-2xl shadow-sm border border-emerald-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-download text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-emerald-600 opacity-80">Downloads</div>
                    <div class="text-2xl font-bold text-emerald-800"><?php echo number_format($total_downloads); ?></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-emerald-200">
                <div class="text-xs text-emerald-600 opacity-70">Files downloaded</div>
            </div>
        </div>
        
        <!-- SMS Sent Card -->
        <div class="bg-gradient-to-br from-violet-50 to-violet-100 p-6 rounded-2xl shadow-sm border border-violet-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-violet-400 to-violet-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-sms text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-violet-600 opacity-80">SMS Sent</div>
                    <div class="text-2xl font-bold text-violet-800"><?php echo number_format($total_sms); ?></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-violet-200">
                <div class="text-xs text-violet-600 opacity-70">
                    Today: <?php echo $today_sms; ?> | Week: <?php echo $week_sms; ?>
                </div>
            </div>
        </div>
        
        <!-- System Logs Card -->
        <div class="bg-gradient-to-br from-rose-50 to-rose-100 p-6 rounded-2xl shadow-sm border border-rose-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-list-alt text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-rose-600 opacity-80">System Logs</div>
                    <div class="text-2xl font-bold text-rose-800"><?php echo number_format($total_logs); ?></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-rose-200">
                <div class="text-xs text-rose-600 opacity-70">Activity records</div>
            </div>
        </div>
        
        <!-- Super Admins Card -->
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-6 rounded-2xl shadow-sm border border-indigo-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-user-shield text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-indigo-600 opacity-80">Super Admins</div>
                    <div class="text-2xl font-bold text-indigo-800"><?php echo $total_super_admins; ?></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-indigo-200">
                <div class="text-xs text-indigo-600 opacity-70">System administrators</div>
            </div>
        </div>
        
        <!-- Active Users Card -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-2xl shadow-sm border border-green-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-users text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-green-600 opacity-80">Active Users</div>
                    <div class="text-2xl font-bold text-green-800"><?php echo number_format($active_users); ?></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-green-200">
                <div class="text-xs text-green-600 opacity-70">Currently online</div>
            </div>
        </div>
        
        <!-- User Management Card -->
        <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 p-6 rounded-2xl shadow-sm border border-cyan-200 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-users text-xl text-white"></i>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-cyan-600 opacity-80">User Management</div>
                    <div class="text-2xl font-bold text-cyan-800">
                        <a href="user_management.php" class="hover:text-cyan-900 transition-colors duration-200">Manage</a>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-cyan-200">
                <div class="text-xs text-cyan-600 opacity-70">User accounts</div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <!-- Purchase Tracking Card -->
        <a href="../dashboard/purchase-tracking" class="bg-white border border-gray-200 rounded-lg p-6 block hover:bg-gray-50">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-search text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Purchase Tracking</h3>
                    <p class="text-sm text-gray-500">Track orders</p>
                </div>
            </div>
        </a>

        <!-- System Settings Card -->
        <a href="../dashboard/system-settings" class="bg-white border border-gray-200 rounded-lg p-6 block hover:bg-gray-50">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-cog text-gray-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">System Settings</h3>
                    <p class="text-sm text-gray-500">Configure APIs</p>
                </div>
            </div>
        </a>

        <!-- System Logs Card -->
        <a href="../dashboard/system-logs" class="bg-white border border-gray-200 rounded-lg p-6 block hover:bg-gray-50">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-list-alt text-red-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">System Logs</h3>
                    <p class="text-sm text-gray-500">View activity</p>
                </div>
            </div>
        </a>

        <!-- Maintenance Mode Card -->
        <a href="../dashboard/maintenance-mode" class="bg-white border border-gray-200 rounded-lg p-6 block hover:bg-gray-50">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-tools text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Maintenance</h3>
                    <p class="text-sm text-gray-500">Site mode</p>
                </div>
            </div>
        </a>

        <!-- System Cleanup Card -->
        <a href="../dashboard/system-cleanup" class="bg-white border border-gray-200 rounded-lg p-6 block hover:bg-gray-50">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-broom text-orange-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">System Cleanup</h3>
                    <p class="text-sm text-gray-500">Reset system</p>
                </div>
            </div>
        </a>

        <!-- Cloudinary Card -->
        <a href="../dashboard/cloudinary" class="bg-white border border-gray-200 rounded-lg p-6 block hover:bg-gray-50">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-cloud text-blue-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Cloudinary</h3>
                    <p class="text-sm text-gray-500">Media management</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Right Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-3 space-y-6">
            <!-- Analytics & Activity Section -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-chart-line text-blue-600 mr-3"></i>Analytics & Activity
                </h2>
                <div class="space-y-3">
                    <a href="user_activity.php" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <i class="fas fa-users text-blue-600 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">User Activity</div>
                            <div class="text-sm text-gray-600">Live users and sessions</div>
                        </div>
                    </a>
                    <a href="site_analytics.php" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                        <i class="fas fa-chart-bar text-green-600 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Site Analytics</div>
                            <div class="text-sm text-gray-600">Visitor statistics & trends</div>
                        </div>
                    </a>
                    <a href="seo_management.php" class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                        <i class="fas fa-search text-purple-600 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">SEO Management</div>
                            <div class="text-sm text-gray-600">Search & social media</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- API Testing Section -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-flask text-green-600 mr-3"></i>API Testing
                </h2>
                
                <!-- Payment Test -->
                <div class="mb-4">
                    <h3 class="text-md font-semibold text-[#2D3E50] mb-2 flex items-center">
                        <i class="fas fa-credit-card text-blue-600 mr-2"></i>Test Payment Verification
                    </h3>
                    <div class="space-y-2">
                        <input id="test_reference" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Paystack reference">
                        <div class="flex gap-2">
                            <input id="test_product_id" type="number" class="w-24 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="1" value="1">
                            <button onclick="testPayment()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg transition-colors text-sm">Test Payment</button>
                        </div>
                        <button onclick="validateKeys()" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg transition-colors text-sm">Validate Keys</button>
                    </div>
                    <div id="payment_result" class="text-sm mt-2"></div>
                </div>

                <!-- SMS Test -->
                <div class="mb-4">
                    <h3 class="text-md font-semibold text-[#2D3E50] mb-2 flex items-center">
                        <i class="fas fa-sms text-purple-600 mr-2"></i>Test SMS
                    </h3>
                    <div class="space-y-2">
                        <input id="test_sms_phone" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Phone number">
                        <button onclick="testSMS()" class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg transition-colors text-sm">Send SMS</button>
                    </div>
                    <div id="sms_result" class="text-sm mt-2"></div>
                </div>

                <!-- Token Test -->
                <div class="mb-4">
                    <h3 class="text-md font-semibold text-[#2D3E50] mb-2 flex items-center">
                        <i class="fas fa-key text-indigo-600 mr-2"></i>Test Token Generation
                    </h3>
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <input id="test_user_id" type="number" class="w-20 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="User ID">
                            <input id="test_token_product_id" type="number" class="w-24 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Product ID">
                            <input id="test_order_id" type="number" class="w-24 p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Order ID">
                        </div>
                        <button onclick="testToken()" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg transition-colors text-sm">Generate Token</button>
                    </div>
                    <div id="token_result" class="text-sm mt-2"></div>
                </div>
            </div>

            <!-- Data Management Section -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-database text-orange-600 mr-3"></i>Data Management
                </h2>
                
                <!-- Clear SMS Data -->
                <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
                    <h3 class="font-medium text-red-900 mb-2 flex items-center">
                        <i class="fas fa-trash text-red-600 mr-2"></i>Clear SMS Data
                    </h3>
                    <p class="text-sm text-red-700 mb-3">This will permanently delete all SMS sent records and reset SMS statistics.</p>
                    <button onclick="clearSmsData()" class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                        <i class="fas fa-trash mr-2"></i>Clear All SMS Data
                    </button>
                </div>
                
                <!-- Clear All Logs -->
                <div class="mb-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
                    <h3 class="font-medium text-orange-900 mb-2 flex items-center">
                        <i class="fas fa-broom text-orange-600 mr-2"></i>Clear System Logs
                    </h3>
                    <p class="text-sm text-orange-700 mb-3">This will permanently delete all system logs and reset log statistics.</p>
                    <button onclick="clearAllLogs()" class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                        <i class="fas fa-trash mr-2"></i>Clear All Logs
                    </button>
                </div>

                <!-- System Maintenance -->
                <div class="space-y-2">
                    <button onclick="cleanLogs()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm flex items-center justify-center">
                        <i class="fas fa-broom mr-2"></i>Clean Old Logs
                    </button>
                    <button onclick="validateApis()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors text-sm flex items-center justify-center">
                        <i class="fas fa-stethoscope mr-2"></i>Validate APIs
                    </button>
                </div>
                <div id="maint_result" class="text-sm text-gray-600 mt-3"></div>
            </div>

            <!-- Admin Management Section -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-users-cog text-indigo-600 mr-3"></i>Admin Management
                </h2>
                <div class="space-y-3">
                    <a href="manage_admins.php" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center">
                        <i class="fas fa-user-plus mr-2"></i>Manage Admins
                    </a>
                    <div class="text-sm text-gray-600 p-3 bg-gray-50 rounded-lg">
                        Create and manage admin accounts for the system.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

async function testPayment(){
  const reference = document.getElementById('test_reference').value;
  const productId = document.getElementById('test_product_id').value;
  if(!reference || !productId) { alert('Fill all fields'); return; }
  
  document.getElementById('payment_result').innerHTML = '<div class="text-blue-600">Testing...</div>';
  const res = await fetch('test_payment.php', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({reference, product_id: productId})});
  const data = await res.json().catch(()=>({success:false,error:'Invalid JSON'}));
  document.getElementById('payment_result').innerHTML = data.success? 
    '<div class="text-green-600">✓ '+data.message+'</div>' : 
    '<div class="text-red-600">✗ '+data.error+'</div>';
}

async function validateKeys(){
  document.getElementById('payment_result').innerHTML = '<div class="text-blue-600">Validating...</div>';
  const res = await fetch('test_payment.php', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({validate_keys: true})});
  const data = await res.json().catch(()=>({success:false,error:'Invalid JSON'}));
  document.getElementById('payment_result').innerHTML = data.success? 
    '<div class="text-green-600">✓ Keys validated</div>' : 
    '<div class="text-red-600">✗ '+data.error+'</div>';
}

async function testSMS(){
  const phone = document.getElementById('test_sms_phone').value;
  if(!phone) { 
    alert('Enter phone number'); 
    return; 
  }
  
  const resultDiv = document.getElementById('sms_result');
  resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Sending SMS...</div>';
  
  try {
    const res = await fetch('test_sms.php', {
      method: 'POST', 
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }, 
      body: JSON.stringify({phone})
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const data = await res.json();
    
    if (data.success) {
      resultDiv.innerHTML = '<div class="text-green-600"><i class="fas fa-check mr-2"></i>SMS sent successfully!</div>';
      if (data.details) {
        resultDiv.innerHTML += '<div class="text-xs text-gray-600 mt-1">Phone: ' + data.details.phone + ' | Sender: ' + data.details.sender + '</div>';
      }
    } else {
      let errorHtml = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Failed: ' + data.error + '</div>';
      
      // Add detailed error information if available
      if (data.details) {
        errorHtml += '<div class="text-xs text-gray-600 mt-1">';
        if (data.details.http_code) {
          errorHtml += 'HTTP Code: ' + data.details.http_code + ' | ';
        }
        if (data.details.api_key_configured !== undefined) {
          errorHtml += 'API Key: ' + (data.details.api_key_configured ? 'Configured' : 'Not Configured') + ' | ';
        }
        if (data.details.raw_response) {
          errorHtml += '<br>Response: ' + data.details.raw_response;
        }
        errorHtml += '</div>';
      }
      
      resultDiv.innerHTML = errorHtml;
    }
  } catch (error) {
    resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Network error: ' + error.message + '</div>';
  }
}

async function testToken(){
  const userId = document.getElementById('test_user_id').value;
  const productId = document.getElementById('test_token_product_id').value;
  const orderId = document.getElementById('test_order_id').value;
  if(!userId || !productId || !orderId) { alert('Fill all fields'); return; }
  
  document.getElementById('token_result').innerHTML = '<div class="text-blue-600">Generating...</div>';
  const res = await fetch('test_token.php', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({user_id: userId, product_id: productId, order_id: orderId})});
  const data = await res.json().catch(()=>({success:false,error:'Invalid JSON'}));
  document.getElementById('token_result').innerHTML = data.success? 
    '<div class="text-green-600">✓ Token generated</div>' : 
    '<div class="text-red-600">✗ '+data.error+'</div>';
}

async function cleanLogs(){
  const resultDiv = document.getElementById('maint_result');
  resultDiv.innerHTML = '<div class="text-blue-600">Cleaning old logs...</div>';
  
  try {
    const res = await fetch('superadmin_tools.php?action=clean_logs');
    const data = await res.json().catch(()=>({success:false}));
    
    if (data.success) {
      const cleanedCount = data.cleaned || 0;
      if (cleanedCount > 0) {
        resultDiv.innerHTML = `<div class="text-green-600">✅ Successfully cleaned ${cleanedCount} old log entries</div>`;
      } else {
        resultDiv.innerHTML = '<div class="text-blue-600">ℹ️ No old logs to clean (all logs are within 30 days)</div>';
      }
    } else {
      resultDiv.innerHTML = '<div class="text-red-600">❌ Failed to clean logs</div>';
    }
  } catch (error) {
    resultDiv.innerHTML = '<div class="text-red-600">❌ Error cleaning logs: ' + error.message + '</div>';
  }
}

async function validateApis(){
  const resultDiv = document.getElementById('maint_result');
  resultDiv.innerHTML = '<div class="text-blue-600">Validating APIs...</div>';
  
  try {
    const res = await fetch('superadmin_tools.php?action=validate');
    const data = await res.json().catch(()=>({success:false}));
    
    if (data.success && data.results) {
      let resultHtml = '<div class="space-y-2">';
      
      // SMS API Status
      const sms = data.results.sms;
      const smsIcon = sms.reachable ? '✅' : (sms.configured ? '⚠️' : '❌');
      resultHtml += `<div class="text-sm"><strong>SMS API:</strong> ${smsIcon} ${sms.message}</div>`;
      
      // Payment API Status
      const payment = data.results.payment;
      const paymentIcon = payment.reachable ? '✅' : (payment.configured ? '⚠️' : '❌');
      resultHtml += `<div class="text-sm"><strong>Payment API:</strong> ${paymentIcon} ${payment.message}</div>`;
      
      // Database Status
      const db = data.results.database;
      const dbIcon = db.reachable ? '✅' : '❌';
      resultHtml += `<div class="text-sm"><strong>Database:</strong> ${dbIcon} ${db.message}</div>`;
      
      resultHtml += '</div>';
      resultDiv.innerHTML = resultHtml;
    } else {
      resultDiv.innerHTML = '<div class="text-red-600">❌ API validation failed</div>';
    }
  } catch (error) {
    resultDiv.innerHTML = '<div class="text-red-600">❌ API validation error: ' + error.message + '</div>';
  }
}


// Clear SMS Data
async function clearSmsData() {
  if (!confirm('Are you sure you want to clear all SMS data? This action cannot be undone.')) {
    return;
  }
  
  try {
    const response = await fetch('superadmin_tools.php?action=clear_sms_data');
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error('Response text:', text);
      throw new Error('Invalid JSON response: ' + text.substring(0, 200));
    }
    
    if (data.success) {
      alert('SMS data cleared successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Unknown error'));
    }
  } catch (error) {
    alert('Network error: ' + error.message);
  }
}

                 function clearAllLogs() {
             if (confirm('Are you sure you want to clear ALL logs? This action cannot be undone.')) {
                 const resultDiv = document.getElementById('maint_result');
                 resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Clearing all logs...</div>';
                 
                 fetch('superadmin_tools.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded',
                     },
                     body: 'action=clear_all_logs'
                 })
                 .then(response => {
                     if (!response.ok) {
                         throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                     }
                     return response.text().then(text => {
                         try {
                             return JSON.parse(text);
                         } catch (e) {
                             console.error('Response text:', text);
                             throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                         }
                     });
                 })
                 .then(data => {
                     if (data.success) {
                         resultDiv.innerHTML = `
                             <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                 <div class="font-bold">✓ All logs cleared successfully!</div>
                                 <div class="text-sm mt-1">
                                     System Logs: ${data.logs_cleared}<br>
                                     SMS Logs: ${data.sms_cleared}<br>
                                     OTP Codes: ${data.otp_cleared}
                                 </div>
                             </div>
                         `;
                         setTimeout(() => location.reload(), 2000);
                     } else {
                         resultDiv.innerHTML = `
                             <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                 <div class="font-bold">✗ Error clearing logs</div>
                                 <div class="text-sm mt-1">${data.error}</div>
                             </div>
                         `;
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     resultDiv.innerHTML = `
                         <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                             <div class="font-bold">✗ Network Error</div>
                             <div class="text-sm mt-1">Failed to clear logs: ${error.message}</div>
                         </div>
                     `;
                 });
             }
         }



                        <div id="userManagementContent">
                            <div class="text-center py-8">
                                <i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i>
                                <p class="text-gray-600">Loading user details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Load user details
            loadUserDetails(userId);
        }

        function closeUserManagementModal() {
            const modal = document.getElementById('userManagementModal');
            if (modal) {
                modal.remove();
            }
        }

        function loadUserDetails(userId) {
            const contentDiv = document.getElementById('userManagementContent');
            
            fetch('superadmin_tools.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'manage_user',
                    user_id: userId,
                    user_action: 'get_details'
                }).toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserDetails(data.user_details);
                } else {
                    contentDiv.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-600 mb-2"></i>
                            <p class="text-gray-600">Error: ${data.error}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-times-circle text-2xl text-red-600 mb-2"></i>
                        <p class="text-gray-600">Error loading user details</p>
                    </div>
                `;
            });
        }

        function displayUserDetails(user) {
            const contentDiv = document.getElementById('userManagementContent');
            
            const statusBadge = user.status === 'active' ? 
                '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>' :
                '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Suspended</span>';
            
            let html = `
                <div class="space-y-6">
                    <!-- User Information -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-user text-blue-600 mr-2"></i>User Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">User ID:</span>
                                <span class="font-medium font-mono">${user.user_id || 'N/A'}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Name:</span>
                                <span class="font-medium">${user.name || 'N/A'}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Email:</span>
                                <span class="font-medium">${user.email}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Phone:</span>
                                <span class="font-medium">${user.phone || 'N/A'}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span class="font-medium">${statusBadge}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Joined:</span>
                                <span class="font-medium">${new Date(user.created_at).toLocaleDateString()}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Last Updated:</span>
                                <span class="font-medium">${new Date(user.updated_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Statistics -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-shopping-cart text-blue-600 mr-2"></i>Purchase Statistics
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">${user.total_purchases || 0}</div>
                                <div class="text-gray-500">Total Purchases</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">GHS ${parseFloat(user.total_spent || 0).toFixed(2)}</div>
                                <div class="text-gray-500">Total Spent</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">${user.last_purchase ? new Date(user.last_purchase).toLocaleDateString() : 'Never'}</div>
                                <div class="text-gray-500">Last Purchase</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Actions -->
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-tools text-yellow-600 mr-2"></i>Management Actions
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            ${user.status === 'active' ? 
                                `<button onclick="manageUser(${user.id}, 'suspend')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm transition-colors">
                                    <i class="fas fa-ban mr-1"></i>Suspend
                                </button>` :
                                `<button onclick="manageUser(${user.id}, 'activate')" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm transition-colors">
                                    <i class="fas fa-check mr-1"></i>Activate
                                </button>`
                            }
                            <button onclick="manageUser(${user.id}, 'reset_password')" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm transition-colors">
                                <i class="fas fa-key mr-1"></i>Reset Password
                            </button>
                            ${user.total_purchases == 0 ? 
                                `<button onclick="manageUser(${user.id}, 'delete')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm transition-colors">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>` :
                                `<button disabled class="bg-gray-400 text-white px-3 py-2 rounded text-sm cursor-not-allowed" title="Cannot delete user with purchase history">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>`
                            }
                        </div>
                    </div>
                </div>
            `;

            // Add purchase history if available
            if (user.purchases && user.purchases.length > 0) {
                html += `
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-history text-gray-600 mr-2"></i>Purchase History
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                `;
                
                user.purchases.forEach(purchase => {
                    html += `
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <div>
                                <span class="font-medium">${purchase.product_title}</span>
                                <span class="text-sm text-gray-500">#${purchase.id.toString().padStart(4, '0')}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-green-600">GHS ${parseFloat(purchase.price).toFixed(2)}</div>
                                <div class="text-xs text-gray-500">${new Date(purchase.created_at).toLocaleDateString()}</div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            contentDiv.innerHTML = html;
        }

                 function manageUser(userId, action) {
             if (!confirm(`Are you sure you want to ${action} this user?`)) {
                 return;
             }
             
             const contentDiv = document.getElementById('userManagementContent');
             contentDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i><p class="text-gray-600">Processing...</p></div>';
             
             fetch('superadmin_tools.php', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/x-www-form-urlencoded',
                 },
                 body: new URLSearchParams({
                     action: 'manage_user',
                     user_id: userId,
                     user_action: action
                 }).toString()
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     // Show success message
                     contentDiv.innerHTML = `
                         <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                             <div class="font-bold">✓ Success!</div>
                             <div class="text-sm mt-1">${data.message}</div>
                         </div>
                     `;
                     // Reload user details after a short delay
                     setTimeout(() => loadUserDetails(userId), 1500);
                 } else {
                     contentDiv.innerHTML = `
                         <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                             <div class="font-bold">✗ Error</div>
                             <div class="text-sm mt-1">${data.error}</div>
                         </div>
                     `;
                     // Reload user details to show current status
                     setTimeout(() => loadUserDetails(userId), 2000);
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 contentDiv.innerHTML = `
                     <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                         <div class="font-bold">✗ Network Error</div>
                         <div class="text-sm mt-1">Failed to perform action</div>
                     </div>
                 `;
                 // Reload user details to show current status
                 setTimeout(() => loadUserDetails(userId), 2000);
             });
         }

                         // Check access status on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAccessStatus();
        });

         // Super Admin Access Control Functions
         async function generateAccessCode() {
             const resultDiv = document.getElementById('access-code-result');
             resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Generating access code...</div>';
             
             try {
                 const response = await fetch('superadmin_access.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded',
                     },
                     body: 'action=generate_code'
                 });
                 
                 const data = await response.json();
                 
                 if (data.success) {
                     resultDiv.innerHTML = `
                         <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                             <div class="font-bold">Access Code Generated!</div>
                             <div class="text-sm mt-1">
                                 <strong>Code:</strong> <span class="font-mono bg-white px-2 py-1 rounded">${data.code}</span>
                             </div>
                             <div class="text-sm mt-1">
                                 <strong>Expires:</strong> ${new Date(data.expires_at).toLocaleString()}
                             </div>
                             <div class="text-xs mt-2 text-green-600">
                                 Use this code to access admin pages. The code expires in 30 minutes.
                             </div>
                         </div>
                     `;
                     checkAccessStatus();
                 } else {
                     resultDiv.innerHTML = `<div class="text-red-600">Error: ${data.error}</div>`;
                 }
             } catch (error) {
                 resultDiv.innerHTML = `<div class="text-red-600">Network error: ${error.message}</div>`;
             }
         }

         async function verifyAccessCode() {
             const code = document.getElementById('access-code-input').value.trim();
             const resultDiv = document.getElementById('verify-code-result');
             
             if (!code) {
                 resultDiv.innerHTML = '<div class="text-red-600">Please enter an access code</div>';
                 return;
             }
             
             resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Verifying code...</div>';
             
             try {
                 const response = await fetch('superadmin_access.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded',
                     },
                     body: `action=verify_code&code=${encodeURIComponent(code)}`
                 });
                 
                 const data = await response.json();
                 
                 if (data.success) {
                     resultDiv.innerHTML = `
                         <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                             <div class="font-bold">Access Granted!</div>
                             <div class="text-sm mt-1">You now have access to admin pages for 30 minutes.</div>
                         </div>
                     `;
                     document.getElementById('access-code-input').value = '';
                     checkAccessStatus();
                     
                     // Auto-refresh after 2 seconds
                     setTimeout(() => {
                         location.reload();
                     }, 2000);
                 } else {
                     resultDiv.innerHTML = `<div class="text-red-600">Error: ${data.error}</div>`;
                 }
             } catch (error) {
                 resultDiv.innerHTML = `<div class="text-red-600">Network error: ${error.message}</div>`;
             }
         }

         async function checkAccessStatus() {
             const statusElement = document.getElementById('current-access-status');
             const codeElement = document.getElementById('current-access-code');
             const expiresElement = document.getElementById('current-access-expires');
             
             // Check if elements exist before trying to access them
             if (!statusElement || !codeElement || !expiresElement) {
                 return; // Exit if elements don't exist
             }
             
             // Check if super admin has access
             const hasAccess = <?php echo isset($_SESSION['superadmin_access']) && $_SESSION['superadmin_access'] ? 'true' : 'false'; ?>;
             
             if (hasAccess) {
                 statusElement.textContent = 'Active';
                 statusElement.className = 'text-green-600 font-semibold';
                 codeElement.textContent = '<?php echo $_SESSION['superadmin_access_code'] ?? 'N/A'; ?>';
                 
                 // Calculate remaining time
                 const accessTime = <?php echo $_SESSION['superadmin_access_time'] ?? 0; ?>;
                 const expiresTime = accessTime + 1800; // 30 minutes
                 const remainingTime = Math.max(0, expiresTime - Math.floor(Date.now() / 1000));
                 
                 if (remainingTime > 0) {
                     const minutes = Math.floor(remainingTime / 60);
                     const seconds = remainingTime % 60;
                     expiresElement.textContent = `${minutes}m ${seconds}s remaining`;
                     expiresElement.className = 'text-orange-600';
                 } else {
                     expiresElement.textContent = 'Expired';
                     expiresElement.className = 'text-red-600';
                 }
             } else {
                 statusElement.textContent = 'Inactive';
                 statusElement.className = 'text-red-600 font-semibold';
                 codeElement.textContent = 'None';
                 expiresElement.textContent = 'N/A';
                 expiresElement.className = '';
             }
         }

         function revokeAccess() {
             if (confirm('Are you sure you want to revoke your current access?')) {
                 // Clear session variables
                 fetch('superadmin_access.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded',
                     },
                     body: 'action=revoke_access'
                 }).then(() => {
                     location.reload();
                 });
             }
         }

         // Initialize page
         document.addEventListener('DOMContentLoaded', function() {
             checkAccessStatus();
         });
</script>
        </div>
    </main>
</div>
</body>
</html>



