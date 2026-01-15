<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$analyst_id = $_SESSION['analyst_id'];
$analyst_name = $_SESSION['analyst_name'];
$analyst_email = $_SESSION['analyst_email'];

// Get current settings
try {
    // Get submission system status
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'submissions_enabled'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $submissions_enabled = ($result && $result['value'] === 'enabled') ? true : false;
    
    // Get current submission price
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'submission_price'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_price = $result ? floatval($result['value']) : 5.00;
    
} catch (Exception $e) {
    $submissions_enabled = true;
    $current_price = 5.00;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle submission toggle
    if (isset($_POST['toggle_submissions'])) {
        try {
            $new_status = $submissions_enabled ? 'disabled' : 'enabled';
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, value, created_at, updated_at) 
                VALUES ('submissions_enabled', ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()
            ");
            $stmt->execute([$new_status, $new_status]);
            
            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, 'submission_toggle', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $analyst_id, 
                "Submissions " . $new_status . " by analyst", 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $submissions_enabled = !$submissions_enabled;
            $success_message = "Submissions have been " . ($submissions_enabled ? "enabled" : "disabled") . " successfully!";
            
        } catch (Exception $e) {
            $error_message = "Error updating submission status: " . $e->getMessage();
        }
    }
    
    // Handle price update
    if (isset($_POST['update_price'])) {
        try {
            $new_price = floatval($_POST['submission_price']);
            
            if ($new_price < 0.01) {
                throw new Exception("Price must be at least GHS 0.01");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, value, created_at, updated_at) 
                VALUES ('submission_price', ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()
            ");
            $stmt->execute([$new_price, $new_price]);
            
            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, 'price_update', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $analyst_id, 
                "Submission price updated to GHS " . number_format($new_price, 2), 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $current_price = $new_price;
            $success_message = "Submission price updated to GHS " . number_format($new_price, 2) . " successfully!";
            
        } catch (Exception $e) {
            $error_message = "Error updating price: " . $e->getMessage();
        }
    }
    
    // Handle scheduling
    if (isset($_POST['schedule_submission'])) {
        try {
            $action = $_POST['schedule_action'];
            $scheduled_date = $_POST['scheduled_date'];
            $scheduled_time = $_POST['scheduled_time'];
            $notes = trim($_POST['notes'] ?? '');
            
            // Validate date and time
            $scheduled_datetime = $scheduled_date . ' ' . $scheduled_time;
            if (strtotime($scheduled_datetime) <= time()) {
                throw new Exception("Scheduled date and time must be in the future");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO submission_schedules (action, scheduled_date, scheduled_time, created_by, notes, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$action, $scheduled_date, $scheduled_time, $analyst_id, $notes]);
            
            $success_message = "Schedule created successfully! Submissions will be " . $action . "d on " . date('M j, Y g:i A', strtotime($scheduled_datetime));
            
        } catch (Exception $e) {
            $error_message = "Error creating schedule: " . $e->getMessage();
        }
    }
    
    // Handle schedule cancellation
    if (isset($_POST['cancel_schedule'])) {
        try {
            $schedule_id = (int)$_POST['schedule_id'];
            $stmt = $pdo->prepare("
                UPDATE submission_schedules 
                SET status = 'cancelled' 
                WHERE id = ? AND created_by = ? AND status = 'pending'
            ");
            $stmt->execute([$schedule_id, $analyst_id]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = "Schedule cancelled successfully!";
            } else {
                $error_message = "Schedule not found or already processed";
            }
            
        } catch (Exception $e) {
            $error_message = "Error cancelling schedule: " . $e->getMessage();
        }
    }
}

// Get pending schedules
try {
    $stmt = $pdo->prepare("
        SELECT * FROM submission_schedules 
        WHERE created_by = ? AND status = 'pending'
        ORDER BY scheduled_date ASC, scheduled_time ASC
    ");
    $stmt->execute([$analyst_id]);
    $pending_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pending_schedules = [];
}

// Handle logout
if (isset($_GET['logout'])) {
    // Log analyst activity
    try {
        $stmt = $pdo->prepare("
            INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, 'logout', 'Analyst logged out', ?, ?, NOW())
        ");
        $stmt->execute([$analyst_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    } catch (Exception $e) {
        error_log("Error logging analyst logout: " . $e->getMessage());
    }
    
    // Destroy session
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyst Settings - ManuelCode</title>
    
    <!-- Analyst Settings Meta Tags -->
    <meta name="description" content="Analyst settings for managing submission system, pricing, and scheduling.">
    <meta name="keywords" content="analyst settings, submission control, pricing, scheduling, management">
    <meta name="author" content="ManuelCode">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Analyst Settings - ManuelCode">
    <meta property="og:description" content="Analyst settings for managing submission system, pricing, and scheduling.">
    <meta property="og:image" content="https://manuelcode.info/assets/favi/favicon.png">
    <meta property="og:url" content="https://manuelcode.info/analyst/settings.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ManuelCode">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Analyst Settings - ManuelCode">
    <meta name="twitter:description" content="Analyst settings for managing submission system, pricing, and scheduling.">
    <meta name="twitter:image" content="https://manuelcode.info/assets/favi/favicon.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-md border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row justify-between items-center py-4 sm:h-16 space-y-4 sm:space-y-0">
                    <div class="flex items-center">
                        <h1 class="text-lg sm:text-xl font-semibold text-gray-800">
                            <i class="fas fa-cog mr-2 text-blue-600"></i>Analyst Settings
                        </h1>
                    </div>
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <a href="/dashboard" 
                           class="bg-blue-500 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-md hover:bg-blue-600 transition-colors text-sm shadow-sm">
                            <i class="fas fa-tachometer-alt mr-1 sm:mr-2"></i><span class="hidden sm:inline">Dashboard</span><span class="sm:hidden">Home</span>
                        </a>
                        <a href="?logout=1" 
                           class="bg-rose-500 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-md hover:bg-rose-600 transition-colors text-sm shadow-sm">
                            <i class="fas fa-sign-out-alt mr-1 sm:mr-2"></i><span class="hidden sm:inline">Logout</span><span class="sm:hidden">Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Welcome Message -->
            <div class="mb-6 text-center sm:text-left">
                <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-2">
                    Settings & Controls
                </h2>
                <p class="text-sm sm:text-base text-gray-600">
                    Manage submission system, pricing, and scheduling settings.
                </p>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-6 shadow-sm">
                    <div class="flex items-center justify-center sm:justify-start">
                        <i class="fas fa-check-circle mr-2 text-green-600"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-6 shadow-sm">
                    <div class="flex items-center justify-center sm:justify-start">
                        <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Settings Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                
                <!-- Submission System Control -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 space-y-3 sm:space-y-0">
                            <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-700">
                                <i class="fas fa-toggle-on mr-2 text-blue-600"></i>Submission System Control
                            </h3>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $submissions_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <i class="fas <?php echo $submissions_enabled ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                                    <?php echo $submissions_enabled ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">
                            Control whether students can submit project reports. When disabled, the submission form will be hidden from students.
                        </p>
                        
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-3 sm:space-y-0">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas <?php echo $submissions_enabled ? 'fa-toggle-on text-green-600' : 'fa-toggle-off text-red-600'; ?> text-2xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                Current Status: <span class="<?php echo $submissions_enabled ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $submissions_enabled ? 'Submissions Enabled' : 'Submissions Disabled'; ?>
                                                </span>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $submissions_enabled ? 'Students can submit project reports' : 'Students cannot access the submission form'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <form method="POST" class="inline">
                                        <button type="submit" name="toggle_submissions" 
                                                class="bg-<?php echo $submissions_enabled ? 'red' : 'green'; ?>-500 text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-<?php echo $submissions_enabled ? 'red' : 'green'; ?>-600 transition-all duration-200 text-sm shadow-sm">
                                            <i class="fas <?php echo $submissions_enabled ? 'fa-ban' : 'fa-check'; ?> mr-2"></i>
                                            <?php echo $submissions_enabled ? 'Disable Submissions' : 'Enable Submissions'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Price Control -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 space-y-3 sm:space-y-0">
                            <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-700">
                                <i class="fas fa-dollar-sign mr-2 text-green-600"></i>Price Control
                            </h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                Active
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">
                            Set the price that students pay for project submissions. Changes take effect immediately for new submissions.
                        </p>
                        
                        <form method="POST" class="space-y-4">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-3 sm:space-y-0">
                                    <div class="flex-1">
                                        <label for="submission_price" class="block text-sm font-medium text-gray-700 mb-2">Current Price</label>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">GHS</span>
                                            <input type="number" 
                                                   name="submission_price" 
                                                   id="submission_price"
                                                   value="<?php echo number_format($current_price, 2); ?>"
                                                   step="0.01" 
                                                   min="0.01"
                                                   class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Minimum: GHS 0.01</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button type="submit" name="update_price" 
                                                class="bg-green-500 text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-green-600 transition-all duration-200 text-sm shadow-sm">
                                            <i class="fas fa-save mr-2"></i>Update Price
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Scheduling Section -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 space-y-3 sm:space-y-0">
                        <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-700">
                            <i class="fas fa-clock mr-2 text-purple-600"></i>Schedule Submissions
                        </h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Auto Schedule
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Schedule automatic enable/disable of submissions at specific dates and times. Perfect for exam periods or maintenance windows.
                    </p>
                    
                    <!-- Schedule Form -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm mb-4">
                        <form method="POST" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label for="schedule_action" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                                    <select name="schedule_action" id="schedule_action" required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                                        <option value="disable">Disable Submissions</option>
                                        <option value="enable">Enable Submissions</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                    <input type="date" name="scheduled_date" id="scheduled_date" required
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                                </div>
                                <div>
                                    <label for="scheduled_time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                                    <input type="time" name="scheduled_time" id="scheduled_time" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" name="schedule_submission" 
                                            class="w-full bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600 transition-all duration-200 text-sm shadow-sm">
                                        <i class="fas fa-plus mr-2"></i>Schedule
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                <input type="text" name="notes" id="notes" placeholder="e.g., Exam period, Maintenance window..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                            </div>
                        </form>
                    </div>
                    
                    <!-- Pending Schedules -->
                    <?php if (!empty($pending_schedules)): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 shadow-sm">
                            <h4 class="text-sm font-medium text-blue-900 mb-3">
                                <i class="fas fa-list mr-2"></i>Pending Schedules
                            </h4>
                            <div class="space-y-2">
                                <?php foreach ($pending_schedules as $schedule): ?>
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-white border border-blue-200 rounded-lg p-3">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas <?php echo $schedule['action'] === 'enable' ? 'fa-toggle-on text-green-600' : 'fa-toggle-off text-red-600'; ?>"></i>
                                                <span class="text-sm font-medium text-gray-900">
                                                    <?php echo ucfirst($schedule['action']); ?> Submissions
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($schedule['scheduled_date'] . ' ' . $schedule['scheduled_time'])); ?>
                                                <?php if (!empty($schedule['notes'])): ?>
                                                    • <?php echo htmlspecialchars($schedule['notes']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 mt-2 sm:mt-0">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                <button type="submit" name="cancel_schedule" 
                                                        class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 transition-colors text-xs"
                                                        onclick="return confirm('Are you sure you want to cancel this schedule?')">
                                                    <i class="fas fa-times mr-1"></i>Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                            <i class="fas fa-calendar-plus text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500">No pending schedules. Create one above to automate submission control.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set default time to next hour
        function setDefaultTime() {
            const now = new Date();
            const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
            const timeString = nextHour.toTimeString().slice(0, 5);
            document.getElementById('scheduled_time').value = timeString;
        }
        
        // Set default date to today
        function setDefaultDate() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('scheduled_date').value = today;
        }
        
        // Initialize form defaults
        setDefaultDate();
        setDefaultTime();
        
        // Validate schedule form
        function validateSchedule() {
            const scheduledDate = document.getElementById('scheduled_date').value;
            const scheduledTime = document.getElementById('scheduled_time').value;
            
            if (!scheduledDate || !scheduledTime) {
                alert('Please select both date and time for the schedule.');
                return false;
            }
            
            const scheduledDateTime = new Date(scheduledDate + ' ' + scheduledTime);
            const now = new Date();
            
            if (scheduledDateTime <= now) {
                alert('Scheduled date and time must be in the future.');
                return false;
            }
            
            return true;
        }
        
        // Add form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.name === 'schedule_submission') {
                if (!validateSchedule()) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
