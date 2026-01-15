<?php
// Super Admin - System Logs Page
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Logs - Super Admin</title>
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
                        <i class="fas fa-list-alt text-red-600 mr-3"></i>System Logs
                    </h1>
                    <p class="text-slate-600 mt-2 text-sm">View system activity and logs</p>
                </div>
                <a href="../dashboard/superadmin" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-6 py-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-6xl mx-auto">
            <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                <i class="fas fa-list-alt text-red-600 mr-3"></i>Recent System Logs
            </h2>
            
            <!-- Log Filter Controls -->
            <div class="mb-4 flex flex-wrap gap-2">
                <select id="logCategoryFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    <option value="SMS">SMS</option>
                    <option value="PAYMENT">Payment</option>
                    <option value="AUTH">Authentication</option>
                    <option value="SYSTEM">System</option>
                </select>
                <select id="logDateFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <button onclick="refreshLogs()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition-colors flex items-center">
                    <i class="fas fa-refresh mr-1"></i>Refresh
                </button>
            </div>
            
            <div id="logs" class="text-sm text-gray-700 space-y-2 max-h-96 overflow-y-auto">
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT created_at, level, category, message FROM system_logs ORDER BY created_at DESC LIMIT 50");
                    $stmt->execute();
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $date = new DateTime($row['created_at']);
                        $formatted_date = $date->format('M j, Y g:i A');
                        echo '<div class="border border-gray-200 rounded-lg p-3 log-entry hover:bg-gray-50" data-category="'.htmlspecialchars($row['category']).'" data-date="'.htmlspecialchars($row['created_at']).'">
                                <div class="text-xs text-gray-500 mb-1">'.htmlspecialchars($formatted_date).' · '.htmlspecialchars($row['level']).' · '.htmlspecialchars($row['category'])."</div>
                                <div class='text-gray-700'>".htmlspecialchars($row['message'])."</div>
                              </div>";
                    }
                } catch (Exception $e) {
                    echo '<div class="text-red-600 p-3 bg-red-50 rounded-lg">Unable to load logs.</div>';
                }
                ?>
            </div>
        </div>
    </main>
</div>

<script>
function refreshLogs() {
    location.reload();
}

document.getElementById('logCategoryFilter').addEventListener('change', filterLogs);
document.getElementById('logDateFilter').addEventListener('change', filterLogs);

function filterLogs() {
    const category = document.getElementById('logCategoryFilter').value;
    const dateFilter = document.getElementById('logDateFilter').value;
    const entries = document.querySelectorAll('.log-entry');
    
    entries.forEach(entry => {
        const entryCategory = entry.getAttribute('data-category');
        const entryDate = new Date(entry.getAttribute('data-date'));
        const now = new Date();
        
        let show = true;
        
        if (category && entryCategory !== category) {
            show = false;
        }
        
        if (dateFilter) {
            if (dateFilter === 'today') {
                if (entryDate.toDateString() !== now.toDateString()) {
                    show = false;
                }
            } else if (dateFilter === 'week') {
                const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                if (entryDate < weekAgo) {
                    show = false;
                }
            } else if (dateFilter === 'month') {
                const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                if (entryDate < monthAgo) {
                    show = false;
                }
            }
        }
        
        entry.style.display = show ? 'block' : 'none';
    });
}
</script>
</body>
</html>

