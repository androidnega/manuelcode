<?php
// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/util.php';
include '../includes/quote_helper.php';

$message = '';
$error = '';

// Handle quote actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $quote_id = (int)$_POST['quote_id'];
                $status = $_POST['status'];
                $quote_message = $_POST['admin_notes'] ?? '';
                $quoted_amount = $_POST['quoted_amount'] ?? null;
                $priority = $_POST['priority'] ?? 'medium';
                
                try {
                    $result = updateQuoteStatus($pdo, $quote_id, [
                        'status' => $status,
                        'quote_message' => $quote_message,
                        'quote_amount' => $quoted_amount
                    ], $_SESSION['admin_id']);
                    
                    if ($result['success']) {
                        // Send SMS notification
                        $quote = getQuoteById($pdo, $quote_id);
                        if ($quote) {
                            sendQuoteNotification($pdo, $quote, $status, $quoted_amount);
                        }
                        
                        $message = "Quote status updated successfully! SMS notification sent to customer.";
                    } else {
                        $error = "Error updating quote: " . $result['error'];
                    }
                } catch (Exception $e) {
                    $error = "Error updating quote: " . $e->getMessage();
                }
                break;
                
            case 'delete_quote':
                $quote_id = (int)$_POST['quote_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM quote_requests WHERE id = ?");
                    $stmt->execute([$quote_id]);
                    
                    $message = "Quote deleted successfully!";
                } catch (Exception $e) {
                    $error = "Error deleting quote: " . $e->getMessage();
                }
                break;
                
            case 'add_note':
                $quote_id = (int)$_POST['quote_id'];
                $note = $_POST['note'] ?? '';
                
                if (!empty($note)) {
                    addQuoteCommunication($pdo, $quote_id, 'admin_note', $note, $_SESSION['admin_id']);
                    $message = "Note added successfully!";
                }
                break;
                
            case 'bulk_action':
                $selected_quotes = $_POST['selected_quotes'] ?? [];
                $bulk_action = $_POST['bulk_action'] ?? '';
                
                if (!empty($selected_quotes) && !empty($bulk_action)) {
                    $success_count = 0;
                    foreach ($selected_quotes as $quote_id) {
                        try {
                            switch ($bulk_action) {
                                case 'mark_quoted':
                                    updateQuoteStatus($pdo, $quote_id, ['status' => 'quoted'], $_SESSION['admin_id']);
                                    break;
                                case 'mark_accepted':
                                    updateQuoteStatus($pdo, $quote_id, ['status' => 'accepted'], $_SESSION['admin_id']);
                                    break;
                                case 'mark_declined':
                                    updateQuoteStatus($pdo, $quote_id, ['status' => 'declined'], $_SESSION['admin_id']);
                                    break;
                                case 'delete':
                                    $stmt = $pdo->prepare("DELETE FROM quote_requests WHERE id = ?");
                                    $stmt->execute([$quote_id]);
                                    break;
                            }
                            $success_count++;
                        } catch (Exception $e) {
                            // Continue with other quotes
                        }
                    }
                    $message = "Bulk action completed successfully on {$success_count} quotes!";
                }
                break;
        }
    }
}

// Get filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'service_type' => $_GET['service_type'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Get quotes with filters
$quotes = searchQuotes($pdo, $filters);

// Get quote statistics
$stats = getQuoteStatistics($pdo, 'all');
$today_stats = getQuoteStatistics($pdo, 'today');
$week_stats = getQuoteStatistics($pdo, 'week');

// Get quote templates
$templates = getQuoteTemplates($pdo);

// Get dashboard data
$dashboard_data = getQuoteDashboardData($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Management - Admin Dashboard</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Quote Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="quote_templates.php" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-file-alt mr-1"></i> Templates
                        </a>
                        <a href="quote_analytics.php" class="text-purple-600 hover:text-purple-800">
                            <i class="fas fa-chart-bar mr-1"></i> Analytics
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center text-green-800">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center text-red-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Statistics Cards -->
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
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $dashboard_data['pending_count']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Today</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $today_stats['total']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-percentage text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Conversion Rate</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['conversion_rate']; ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filters & Search</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full p-2 border border-gray-300 rounded-lg">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="reviewed" <?php echo $filters['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="quoted" <?php echo $filters['status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                            <option value="accepted" <?php echo $filters['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="declined" <?php echo $filters['status'] === 'declined' ? 'selected' : ''; ?>>Declined</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                        <select name="service_type" class="w-full p-2 border border-gray-300 rounded-lg">
                            <option value="">All Services</option>
                            <option value="web-development" <?php echo $filters['service_type'] === 'web-development' ? 'selected' : ''; ?>>Web Development</option>
                            <option value="mobile-apps" <?php echo $filters['service_type'] === 'mobile-apps' ? 'selected' : ''; ?>>Mobile Apps</option>
                            <option value="api-development" <?php echo $filters['service_type'] === 'api-development' ? 'selected' : ''; ?>>API Development</option>
                            <option value="custom-software" <?php echo $filters['service_type'] === 'custom-software' ? 'selected' : ''; ?>>Custom Software</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select name="priority" class="w-full p-2 border border-gray-300 rounded-lg">
                            <option value="">All Priorities</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                               placeholder="Project title, customer name..." 
                               class="w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?php echo $filters['date_from']; ?>" 
                               class="w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?php echo $filters['date_to']; ?>" 
                               class="w-full p-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div class="lg:col-span-2 flex items-end space-x-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search mr-1"></i>Filter
                        </button>
                        <a href="quotes_enhanced.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Bulk Actions</h2>
                <form method="POST" id="bulkActionForm">
                    <input type="hidden" name="action" value="bulk_action">
                    <div class="flex items-center space-x-4">
                        <select name="bulk_action" class="p-2 border border-gray-300 rounded-lg">
                            <option value="">Select Action</option>
                            <option value="mark_quoted">Mark as Quoted</option>
                            <option value="mark_accepted">Mark as Accepted</option>
                            <option value="mark_declined">Mark as Declined</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700" 
                                onclick="return confirm('Are you sure you want to perform this action on selected quotes?')">
                            <i class="fas fa-play mr-1"></i>Execute
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quotes List -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Quote Requests (<?php echo count($quotes); ?> found)</h2>
                </div>
                
                <?php if (empty($quotes)): ?>
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-invoice-dollar text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Quotes Found</h3>
                        <p class="text-gray-600">No quote requests match your current filters.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($quotes as $quote): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="selected_quotes[]" value="<?php echo $quote['id']; ?>" 
                                                   class="quote-checkbox rounded border-gray-300">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quote['contact_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($quote['contact_email']); ?></div>
                                                <?php if ($quote['contact_phone']): ?>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($quote['contact_phone']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($quote['unique_code']): ?>
                                                    <div class="text-xs text-gray-400 font-mono"><?php echo htmlspecialchars($quote['unique_code']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quote['project_title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($quote['description'], 0, 50)) . (strlen($quote['description']) > 50 ? '...' : ''); ?></div>
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $priority_colors = [
                                                'low' => 'bg-gray-100 text-gray-800',
                                                'medium' => 'bg-blue-100 text-blue-800',
                                                'high' => 'bg-orange-100 text-orange-800',
                                                'urgent' => 'bg-red-100 text-red-800'
                                            ];
                                            $priority_color = $priority_colors[$quote['priority'] ?? 'medium'] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $priority_color; ?>">
                                                <?php echo ucfirst($quote['priority'] ?? 'medium'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($quote['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewQuote(<?php echo $quote['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </button>
                                            <?php if ($quote['status'] === 'pending'): ?>
                                                <button onclick="editQuote(<?php echo $quote['id']; ?>)" 
                                                        class="text-green-600 hover:text-green-900 mr-3">
                                                    <i class="fas fa-edit mr-1"></i>Quote
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="addNote(<?php echo $quote['id']; ?>)" 
                                                    class="text-purple-600 hover:text-purple-900 mr-3">
                                                <i class="fas fa-sticky-note mr-1"></i>Note
                                            </button>
                                            <button onclick="deleteQuote(<?php echo $quote['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quote Detail Modal -->
    <div id="quoteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Quote Details</h2>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="quoteModalContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.quote-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        function viewQuote(quoteId) {
            document.getElementById('quoteModalContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i><p class="mt-2 text-gray-600">Loading quote details...</p></div>';
            document.getElementById('quoteModal').classList.remove('hidden');
            
            fetch(`get_quote_details.php?id=${quoteId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('quoteModalContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading quote details:', error);
                document.getElementById('quoteModalContent').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                        <p class="mt-2 text-red-600">Error loading quote details: ${error.message}</p>
                        <button onclick="closeModal()" class="mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Close
                        </button>
                    </div>
                `;
            });
        }

        function editQuote(quoteId) {
            document.getElementById('quoteModalContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i><p class="mt-2 text-gray-600">Loading quote form...</p></div>';
            document.getElementById('quoteModal').classList.remove('hidden');
            
            fetch(`get_quote_reply_form.php?id=${quoteId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('quoteModalContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading quote reply form:', error);
                document.getElementById('quoteModalContent').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                        <p class="mt-2 text-red-600">Error loading quote reply form: ${error.message}</p>
                        <button onclick="closeModal()" class="mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Close
                        </button>
                    </div>
                `;
            });
        }

        function addNote(quoteId) {
            const note = prompt('Enter your note:');
            if (note) {
                const formData = new FormData();
                formData.append('action', 'add_note');
                formData.append('quote_id', quoteId);
                formData.append('note', note);
                
                fetch('quotes_enhanced.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding note. Please try again.');
                });
            }
        }

        function closeModal() {
            document.getElementById('quoteModal').classList.add('hidden');
        }

        function deleteQuote(quoteId) {
            if (confirm('Are you sure you want to delete this quote? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete_quote');
                formData.append('quote_id', quoteId);
                
                fetch('quotes_enhanced.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting quote. Please try again.');
                });
            }
        }

        // Close modal when clicking outside
        document.getElementById('quoteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
