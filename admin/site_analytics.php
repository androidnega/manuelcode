<?php
// Site Analytics Dashboard
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include '../includes/analytics_helper.php';

// Get visitor statistics
$daily_stats = getVisitorStats('daily', 30);
$weekly_stats = getVisitorStats('weekly', 12);
$monthly_stats = getVisitorStats('monthly', 12);

// Get top countries
$top_countries = getTopCountries(10);

// Get top pages
$top_pages = getTopPages(10);

// Debug: Check what data we're getting
error_log("Top countries data: " . print_r($top_countries, true));
error_log("Top pages data: " . print_r($top_pages, true));

// Get device statistics
$device_stats = getDeviceStats();

// Get overall statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_visits FROM page_visits");
    $stmt->execute();
    $total_visits = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) as unique_visitors FROM page_visits");
    $stmt->execute();
    $unique_visitors = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT country) as countries FROM visitor_countries WHERE country != 'Unknown'");
    $stmt->execute();
    $countries_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT AVG(time_spent) as avg_time FROM page_visits WHERE time_spent > 0");
    $stmt->execute();
    $avg_time = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_visits FROM page_visits WHERE DATE(visit_time) = CURDATE()");
    $stmt->execute();
    $today_visits = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_visits = $unique_visitors = $countries_count = $avg_time = $today_visits = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Site Analytics - Super Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-violet-600 bg-clip-text text-transparent">
                            <i class="fas fa-chart-line mr-3"></i>Site Analytics Dashboard
                        </h1>
                        <p class="text-slate-600 text-lg">Real-time visitor insights and performance metrics</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="refreshData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-refresh mr-2"></i>Refresh
                        </button>
                        <button onclick="showClearDataModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-trash mr-2"></i>Clear Data
                        </button>
                        <a href="superadmin.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">
            <div class="px-6 py-8">

                 <!-- Statistics Cards -->
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
             <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow-sm border border-blue-200 hover:shadow-md transition-all duration-300">
                 <div class="flex items-center">
                     <div class="flex-shrink-0">
                         <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                             <i class="fas fa-eye text-xl text-blue-600"></i>
                         </div>
                     </div>
                     <div class="ml-4">
                         <div class="text-sm font-medium text-blue-700">Total Visits</div>
                         <div class="text-3xl font-bold text-blue-900"><?php echo number_format($total_visits); ?></div>
                     </div>
                 </div>
             </div>
             
             <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 rounded-xl shadow-sm border border-emerald-200 hover:shadow-md transition-all duration-300">
                 <div class="flex items-center">
                     <div class="flex-shrink-0">
                         <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                             <i class="fas fa-users text-xl text-emerald-600"></i>
                         </div>
                     </div>
                     <div class="ml-4">
                         <div class="text-sm font-medium text-emerald-700">Unique Visitors</div>
                         <div class="text-3xl font-bold text-emerald-900"><?php echo number_format($unique_visitors); ?></div>
                     </div>
                 </div>
             </div>
             
             <div class="bg-gradient-to-br from-violet-50 to-violet-100 p-6 rounded-xl shadow-sm border border-violet-200 hover:shadow-md transition-all duration-300">
                 <div class="flex items-center">
                     <div class="flex-shrink-0">
                         <div class="w-12 h-12 bg-violet-100 rounded-full flex items-center justify-center">
                             <i class="fas fa-globe text-xl text-violet-600"></i>
                         </div>
                     </div>
                     <div class="ml-4">
                         <div class="text-sm font-medium text-violet-700">Countries</div>
                         <div class="text-3xl font-bold text-violet-900"><?php echo $countries_count; ?></div>
                     </div>
                 </div>
             </div>
             
             <div class="bg-gradient-to-br from-amber-50 to-amber-100 p-6 rounded-xl shadow-sm border border-amber-200 hover:shadow-md transition-all duration-300">
                 <div class="flex items-center">
                     <div class="flex-shrink-0">
                         <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                             <i class="fas fa-clock text-xl text-amber-600"></i>
                         </div>
                     </div>
                     <div class="ml-4">
                         <div class="text-sm font-medium text-amber-700">Avg. Time</div>
                         <div class="text-3xl font-bold text-amber-900"><?php echo round($avg_time / 60, 1); ?>m</div>
                     </div>
                 </div>
             </div>
             
             <div class="bg-gradient-to-br from-rose-50 to-rose-100 p-6 rounded-xl shadow-sm border border-rose-200 hover:shadow-md transition-all duration-300">
                 <div class="flex items-center">
                     <div class="flex-shrink-0">
                         <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center">
                             <i class="fas fa-calendar-day text-xl text-rose-600"></i>
                         </div>
                     </div>
                     <div class="ml-4">
                         <div class="text-sm font-medium text-rose-700">Today's Visits</div>
                         <div class="text-3xl font-bold text-rose-900"><?php echo number_format($today_visits); ?></div>
                     </div>
                 </div>
             </div>
         </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
                         <!-- Visitor Trends Chart -->
             <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-white/20 shadow-sm p-6">
                 <h2 class="text-xl font-semibold text-slate-700 mb-4 flex items-center">
                     <i class="fas fa-chart-area text-blue-500 mr-3"></i>Visitor Trends (Last 30 Days)
                 </h2>
                <div style="height: 400px; position: relative;">
                    <canvas id="visitorChart"></canvas>
                </div>
            </div>

                         <!-- Device Distribution -->
             <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-white/20 shadow-sm p-6">
                 <h2 class="text-xl font-semibold text-slate-700 mb-4 flex items-center">
                     <i class="fas fa-mobile-alt text-emerald-500 mr-3"></i>Device Distribution
                 </h2>
                <div style="height: 400px; position: relative;">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                         <!-- Top Countries -->
             <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-white/20 shadow-sm p-6">
                 <h2 class="text-xl font-semibold text-slate-700 mb-4 flex items-center">
                     <i class="fas fa-flag text-violet-500 mr-3"></i>Top Countries
                 </h2>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (empty($top_countries)): ?>
                                                 <div class="text-center py-12">
                             <div class="w-16 h-16 bg-violet-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                 <i class="fas fa-globe text-2xl text-violet-400"></i>
                             </div>
                             <p class="text-slate-500 text-lg">No country data available</p>
                             <p class="text-slate-400 text-sm mt-1">Real visitor data will appear here</p>
                         </div>
                    <?php else: ?>
                        <?php foreach ($top_countries as $country): ?>
                                                         <div class="flex items-center justify-between p-4 bg-gradient-to-r from-violet-50 to-purple-50 rounded-xl border border-violet-100 hover:shadow-sm transition-all duration-200">
                                 <div class="flex items-center">
                                     <div class="w-10 h-10 bg-violet-100 rounded-full flex items-center justify-center mr-4">
                                         <i class="fas fa-flag text-violet-600 text-sm"></i>
                                     </div>
                                     <div>
                                         <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($country['country']); ?></div>
                                         <div class="text-sm text-slate-600"><?php echo number_format($country['unique_visitors']); ?> unique visitors</div>
                                     </div>
                                 </div>
                                 <div class="text-right">
                                     <div class="font-bold text-violet-700 text-lg"><?php echo number_format($country['total_visits']); ?></div>
                                     <div class="text-xs text-slate-500">visits</div>
                                 </div>
                             </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

                         <!-- Top Pages -->
             <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-white/20 shadow-sm p-6">
                 <h2 class="text-xl font-semibold text-slate-700 mb-4 flex items-center">
                     <i class="fas fa-file-alt text-blue-500 mr-3"></i>Popular Pages
                 </h2>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (empty($top_pages)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-file text-3xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600">No page data available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($top_pages as $page): ?>
                                                         <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100 hover:shadow-sm transition-all duration-200">
                                 <div class="flex items-center justify-between mb-3">
                                     <div class="font-semibold text-slate-800 truncate">
                                         <?php echo htmlspecialchars($page['page_title'] ?: basename($page['page_url'])); ?>
                                     </div>
                                     <div class="text-lg font-bold text-blue-600">
                                         <?php echo number_format($page['total_visits']); ?>
                                     </div>
                                 </div>
                                 <div class="text-xs text-slate-600 truncate mb-2">
                                     <?php echo htmlspecialchars($page['page_url']); ?>
                                 </div>
                                 <div class="flex justify-between text-xs text-slate-500">
                                     <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full"><?php echo round($page['avg_time_spent'] / 60, 1); ?>m avg</span>
                                     <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full"><?php echo number_format($page['unique_visitors']); ?> unique</span>
                                 </div>
                             </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

                         <!-- Device Statistics -->
             <div class="bg-white/80 backdrop-blur-sm rounded-xl border border-white/20 shadow-sm p-6">
                 <h2 class="text-xl font-semibold text-slate-700 mb-4 flex items-center">
                     <i class="fas fa-laptop text-emerald-500 mr-3"></i>Device Statistics
                 </h2>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (empty($device_stats)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-mobile text-3xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600">No device data available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($device_stats as $device): ?>
                                                         <div class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl border border-emerald-100 hover:shadow-sm transition-all duration-200">
                                 <div class="flex items-center">
                                     <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center mr-4">
                                         <?php
                                         $icon = 'desktop';
                                         if ($device['device_type'] === 'mobile') $icon = 'mobile-alt';
                                         elseif ($device['device_type'] === 'tablet') $icon = 'tablet-alt';
                                         ?>
                                         <i class="fas fa-<?php echo $icon; ?> text-emerald-600 text-sm"></i>
                                     </div>
                                     <div>
                                         <div class="font-semibold text-slate-800 capitalize"><?php echo htmlspecialchars($device['device_type']); ?></div>
                                         <div class="text-sm text-slate-600"><?php echo number_format($device['unique_visitors']); ?> unique visitors</div>
                                     </div>
                                 </div>
                                 <div class="text-right">
                                     <div class="font-bold text-emerald-700 text-lg"><?php echo number_format($device['total_visits']); ?></div>
                                     <div class="text-xs text-slate-500">visits</div>
                                 </div>
                             </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
                 </div>
     </div>

     <!-- Clear Data Modal -->
     <div id="clearDataModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
         <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
             <div class="flex items-center justify-between mb-4">
                 <h3 class="text-xl font-semibold text-slate-800 flex items-center">
                     <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                     Clear Analytics Data
                 </h3>
                 <button onclick="hideClearDataModal()" class="text-gray-400 hover:text-gray-600">
                     <i class="fas fa-times text-xl"></i>
                 </button>
             </div>
             
             <div class="mb-6">
                 <p class="text-slate-600 mb-4">
                     This action will permanently delete all analytics data including:
                 </p>
                 <ul class="text-sm text-slate-600 space-y-2 mb-4">
                     <li class="flex items-center">
                         <i class="fas fa-circle text-red-400 text-xs mr-2"></i>
                         All page visits and visitor statistics
                     </li>
                     <li class="flex items-center">
                         <i class="fas fa-circle text-red-400 text-xs mr-2"></i>
                         Country and device data
                     </li>
                     <li class="flex items-center">
                         <i class="fas fa-circle text-red-400 text-xs mr-2"></i>
                         Popular pages tracking
                     </li>
                     <li class="flex items-center">
                         <i class="fas fa-circle text-red-400 text-xs mr-2"></i>
                         User session data
                     </li>
                 </ul>
                 <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                     <p class="text-red-700 text-sm font-medium">
                         <i class="fas fa-warning mr-2"></i>
                         This action cannot be undone!
                     </p>
                 </div>
             </div>
             
             <div class="flex space-x-3">
                 <button onclick="hideClearDataModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg transition-colors">
                     Cancel
                 </button>
                 <button onclick="clearAnalyticsData()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                     <i class="fas fa-trash mr-2"></i>
                     Clear All Data
                 </button>
             </div>
         </div>
     </div>

     <script>
        // Visitor Trends Chart
        const visitorCtx = document.getElementById('visitorChart').getContext('2d');
        const visitorChart = new Chart(visitorCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse(array_column($daily_stats, 'date_value'))); ?>,
                datasets: [{
                    label: 'Unique Visitors',
                    data: <?php echo json_encode(array_reverse(array_column($daily_stats, 'unique_visitors'))); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Total Visits',
                    data: <?php echo json_encode(array_reverse(array_column($daily_stats, 'total_visits'))); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 20
                    }
                }
            }
        });

        // Device Distribution Chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        const deviceChart = new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($device_stats, 'device_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($device_stats, 'total_visits')); ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(251, 146, 60, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

                 function refreshData() {
             location.reload();
         }
         
         // Clear Data Modal Functions
         function showClearDataModal() {
             document.getElementById('clearDataModal').classList.remove('hidden');
         }
         
         function hideClearDataModal() {
             document.getElementById('clearDataModal').classList.add('hidden');
         }
         
         function clearAnalyticsData() {
             if (confirm('Are you absolutely sure you want to clear all analytics data? This action cannot be undone!')) {
                 // Show loading state
                 const clearButton = document.querySelector('button[onclick="clearAnalyticsData()"]');
                 const originalText = clearButton.innerHTML;
                 clearButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Clearing...';
                 clearButton.disabled = true;
                 
                 // Make AJAX call to clear data
                 fetch('clear_analytics_data.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify({
                         action: 'clear_analytics_data'
                     })
                 })
                 .then(response => {
                     console.log('Response status:', response.status);
                     return response.json();
                 })
                 .then(data => {
                     console.log('Response data:', data);
                     if (data.success) {
                         alert('Analytics data cleared successfully!');
                         location.reload();
                     } else {
                         alert('Error clearing data: ' + (data.error || 'Unknown error'));
                         clearButton.innerHTML = originalText;
                         clearButton.disabled = false;
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     alert('Error clearing data. Please try again.');
                     clearButton.innerHTML = originalText;
                     clearButton.disabled = false;
                 });
             }
         }
         
         // Auto-refresh every 5 minutes
         setInterval(function() {
             if (document.visibilityState === 'visible') {
                 refreshData();
             }
                  }, 300000);
     </script>
            </div>
        </main>
    </div>
</body>
</html>
