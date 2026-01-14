<?php
// User Activity Dashboard
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include '../includes/analytics_helper.php';

// Get active users count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_users_count FROM user_sessions WHERE is_active = 1");
    $stmt->execute();
    $active_users_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $active_users_count = 0;
}

// Get active users data
try {
    $stmt = $pdo->prepare("
        SELECT us.*, u.name, u.email
        FROM user_sessions us
        LEFT JOIN users u ON us.user_id = u.id
        WHERE us.is_active = 1
        ORDER BY us.login_time DESC
        LIMIT 50
    ");
    $stmt->execute();
    $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $active_users = [];
}

// Get recent user sessions
try {
    $stmt = $pdo->prepare("
        SELECT us.*, u.name, u.email
        FROM user_sessions us
        LEFT JOIN users u ON us.user_id = u.id
        ORDER BY us.login_time DESC
        LIMIT 50
    ");
    $stmt->execute();
    $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_sessions = [];
}

// Get session statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_sessions FROM user_sessions");
    $stmt->execute();
    $total_sessions = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_sessions FROM user_sessions WHERE is_active = 1");
    $stmt->execute();
    $active_sessions = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as unique_users FROM user_sessions WHERE user_id IS NOT NULL");
    $stmt->execute();
    $unique_users = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_sessions FROM user_sessions WHERE DATE(login_time) = CURDATE()");
    $stmt->execute();
    $today_sessions = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_sessions = $active_sessions = $unique_users = $today_sessions = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Activity - Super Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#F4F4F9]">
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[#2D3E50]">
                    <i class="fas fa-users mr-2"></i>User Activity Dashboard
                </h1>
                <p class="text-gray-600">Monitor live users and session activity</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="refreshData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-refresh mr-2"></i>Refresh
                </button>
                <a href="superadmin.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Active Users</div>
                        <div class="text-3xl font-bold text-gray-900"><?php echo $active_users_count; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Today's Sessions</div>
                        <div class="text-3xl font-bold text-gray-900"><?php echo $today_sessions; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-friends text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Unique Users</div>
                        <div class="text-3xl font-bold text-gray-900"><?php echo $unique_users; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-orange-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history text-2xl text-orange-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Total Sessions</div>
                        <div class="text-3xl font-bold text-gray-900"><?php echo $total_sessions; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Live Active Users -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-circle text-green-500 mr-3"></i>Live Active Users
                </h2>
                
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php if (empty($active_users)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-user-slash text-3xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600">No active users at the moment</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($active_users as $user): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">
                                                <?php echo $user['name'] ? htmlspecialchars($user['name']) : 'Guest User'; ?>
                                            </h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo $user['email'] ? htmlspecialchars($user['email']) : 'No email'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('H:i', strtotime($user['login_time'])); ?>
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600">
                                    <div>
                                        <span class="font-medium">Device:</span>
                                        <span class="capitalize"><?php echo htmlspecialchars($user['device_type']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Browser:</span>
                                        <span><?php echo htmlspecialchars($user['browser']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Location:</span>
                                        <span><?php echo htmlspecialchars($user['country']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">IP:</span>
                                        <span class="font-mono"><?php echo htmlspecialchars($user['ip_address']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="mt-2 text-xs text-gray-500">
                                    <span class="font-medium">Active for:</span>
                                    <span id="session-duration-<?php echo $user['id']; ?>">
                                        <?php 
                                        $duration = time() - strtotime($user['login_time']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-history text-blue-600 mr-3"></i>Recent Sessions
                </h2>
                
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php if (empty($recent_sessions)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-clock text-3xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600">No recent sessions</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_sessions as $session): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 <?php echo $session['is_active'] ? 'bg-green-500' : 'bg-gray-400'; ?> rounded-full mr-3"></div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">
                                                <?php echo $session['name'] ? htmlspecialchars($session['name']) : 'Guest User'; ?>
                                            </h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo $session['email'] ? htmlspecialchars($session['email']) : 'No email'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-500">
                                            <?php echo date('M j, H:i', strtotime($session['login_time'])); ?>
                                        </span>
                                        <div class="text-xs <?php echo $session['is_active'] ? 'text-green-600' : 'text-gray-500'; ?>">
                                            <?php echo $session['is_active'] ? 'Active' : 'Ended'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600">
                                    <div>
                                        <span class="font-medium">Device:</span>
                                        <span class="capitalize"><?php echo htmlspecialchars($session['device_type']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Browser:</span>
                                        <span><?php echo htmlspecialchars($session['browser']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Location:</span>
                                        <span><?php echo htmlspecialchars($session['country']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">IP:</span>
                                        <span class="font-mono"><?php echo htmlspecialchars($session['ip_address']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($session['logout_time']): ?>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <span class="font-medium">Session duration:</span>
                                        <span>
                                            <?php 
                                            $duration = strtotime($session['logout_time']) - strtotime($session['login_time']);
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);
                                            echo $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function refreshData() {
            location.reload();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            // Only refresh if user is on this page
            if (document.visibilityState === 'visible') {
                refreshData();
            }
        }, 30000);
        
        // Update session durations every minute
        setInterval(function() {
            const durationElements = document.querySelectorAll('[id^="session-duration-"]');
            durationElements.forEach(element => {
                // This is a simplified update - in a real implementation,
                // you'd want to store the login time and calculate the difference
                const currentText = element.textContent;
                if (currentText.includes('m')) {
                    const minutes = parseInt(currentText.match(/(\d+)m/)[1]);
                    element.textContent = `${minutes + 1}m`;
                }
            });
        }, 60000);
    </script>
</body>
</html>
