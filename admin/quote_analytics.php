<?php
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include '../includes/quote_helper.php';

// Get analytics data
$period = $_GET['period'] ?? 'month';
$stats = getQuoteStatistics($pdo, $period);
$dashboard_data = getQuoteDashboardData($pdo);

// Get detailed analytics
try {
    // Monthly trends for the last 12 months
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_quotes,
            COUNT(CASE WHEN status = 'quoted' THEN 1 END) as quoted_count,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_count,
            AVG(CASE WHEN quote_amount IS NOT NULL THEN quote_amount END) as avg_quote_amount
        FROM quote_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Service type performance
    $stmt = $pdo->prepare("
        SELECT 
            service_type,
            COUNT(*) as total_quotes,
            COUNT(CASE WHEN status = 'quoted' THEN 1 END) as quoted_count,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_count,
            AVG(CASE WHEN quote_amount IS NOT NULL THEN quote_amount END) as avg_quote_amount
        FROM quote_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY service_type
        ORDER BY total_quotes DESC
    ");
    $stmt->execute();
    $service_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily quotes for the last 30 days
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as quotes_count
        FROM quote_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $daily_quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Conversion funnel
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_submitted,
            COUNT(CASE WHEN status = 'quoted' THEN 1 END) as total_quoted,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as total_accepted
        FROM quote_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $conversion_funnel = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $monthly_trends = [];
    $service_performance = [];
    $daily_quotes = [];
    $conversion_funnel = ['total_submitted' => 0, 'total_quoted' => 0, 'total_accepted' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Analytics - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 mr-4">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Quote Analytics</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="quotes_enhanced.php" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-list mr-1"></i> Quote Management
                        </a>
                        <select id="periodSelect" class="border border-gray-300 rounded-lg px-3 py-2">
                            <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-file-invoice-dollar text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Quotes</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Quoted</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['quoted_count']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-thumbs-up text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Accepted</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['accepted_count']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-percentage text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Conversion Rate</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['conversion_rate']; ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Monthly Trends Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Quote Trends</h3>
                    <canvas id="monthlyTrendsChart" width="400" height="200"></canvas>
                </div>

                <!-- Service Performance Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Service Type Performance</h3>
                    <canvas id="servicePerformanceChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Daily Quotes Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Quote Activity (Last 30 Days)</h3>
                    <canvas id="dailyQuotesChart" width="400" height="200"></canvas>
                </div>

                <!-- Conversion Funnel Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversion Funnel (Last 30 Days)</h3>
                    <canvas id="conversionFunnelChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Detailed Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Status Breakdown -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($stats['status_breakdown'] as $status): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php 
                                                $colors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'reviewed' => 'bg-blue-100 text-blue-800',
                                                    'quoted' => 'bg-green-100 text-green-800',
                                                    'accepted' => 'bg-purple-100 text-purple-800',
                                                    'declined' => 'bg-red-100 text-red-800'
                                                ];
                                                echo $colors[$status['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>">
                                                <?php echo ucfirst($status['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $status['count']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $stats['total'] > 0 ? round(($status['count'] / $stats['total']) * 100, 1) : 0; ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Service Type Breakdown -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Service Type Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($stats['service_breakdown'] as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo ucfirst(str_replace('-', ' ', $service['service_type'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $service['count']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $stats['total'] > 0 ? round(($service['count'] / $stats['total']) * 100, 1) : 0; ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Quote Activity</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dashboard_data['recent_quotes'] as $quote): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quote['contact_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($quote['contact_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($quote['project_title']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo ucfirst(str_replace('-', ' ', $quote['service_type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'reviewed' => 'bg-blue-100 text-blue-800',
                                            'quoted' => 'bg-green-100 text-green-800',
                                            'accepted' => 'bg-purple-100 text-purple-800',
                                            'declined' => 'bg-red-100 text-red-800'
                                        ];
                                        $color = $status_colors[$quote['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $color; ?>">
                                            <?php echo ucfirst($quote['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $quote['quote_amount'] ? 'GHS ' . number_format($quote['quote_amount'], 2) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($quote['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Period selector
        document.getElementById('periodSelect').addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('period', this.value);
            window.location.href = url.toString();
        });

        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_trends, 'month')); ?>,
                datasets: [{
                    label: 'Total Quotes',
                    data: <?php echo json_encode(array_column($monthly_trends, 'total_quotes')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Quoted',
                    data: <?php echo json_encode(array_column($monthly_trends, 'quoted_count')); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Accepted',
                    data: <?php echo json_encode(array_column($monthly_trends, 'accepted_count')); ?>,
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Service Performance Chart
        const servicePerformanceCtx = document.getElementById('servicePerformanceChart').getContext('2d');
        new Chart(servicePerformanceCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($service) { return ucfirst(str_replace('-', ' ', $service['service_type'])); }, $service_performance)); ?>,
                datasets: [{
                    label: 'Total Quotes',
                    data: <?php echo json_encode(array_column($service_performance, 'total_quotes')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }, {
                    label: 'Quoted',
                    data: <?php echo json_encode(array_column($service_performance, 'quoted_count')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)'
                }, {
                    label: 'Accepted',
                    data: <?php echo json_encode(array_column($service_performance, 'accepted_count')); ?>,
                    backgroundColor: 'rgba(168, 85, 247, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Daily Quotes Chart
        const dailyQuotesCtx = document.getElementById('dailyQuotesChart').getContext('2d');
        new Chart(dailyQuotesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_quotes, 'date')); ?>,
                datasets: [{
                    label: 'Daily Quotes',
                    data: <?php echo json_encode(array_column($daily_quotes, 'quotes_count')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Conversion Funnel Chart
        const conversionFunnelCtx = document.getElementById('conversionFunnelChart').getContext('2d');
        new Chart(conversionFunnelCtx, {
            type: 'doughnut',
            data: {
                labels: ['Submitted', 'Quoted', 'Accepted'],
                datasets: [{
                    data: [
                        <?php echo $conversion_funnel['total_submitted']; ?>,
                        <?php echo $conversion_funnel['total_quoted']; ?>,
                        <?php echo $conversion_funnel['total_accepted']; ?>
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
