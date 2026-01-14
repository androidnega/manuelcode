<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$analyst_id = $_SESSION['analyst_id'];
$submission_id = $_GET['id'] ?? '';

if (empty($submission_id)) {
    header('Location: dashboard.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ? AND status = 'paid'");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Log the view action
    $stmt = $pdo->prepare("
        INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
        VALUES (?, 'view_submission', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $analyst_id,
        json_encode(['submission_id' => $submission_id, 'student_name' => $submission['name']]),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching submission: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submission - Analyst Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-file-alt mr-2 text-blue-600"></i>Submission Details
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Submission Details -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Student Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-user mr-2 text-blue-600"></i>Student Information
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($submission['name']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Index Number</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($submission['index_number']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($submission['phone_number']); ?></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Project Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-file mr-2 text-green-600"></i>Project Information
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Name</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($submission['file_name']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Type</dt>
                                    <dd class="text-sm text-gray-900"><?php echo strtoupper($submission['file_type']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Size</dt>
                                    <dd class="text-sm text-gray-900"><?php echo round($submission['file_size'] / 1024 / 1024, 2); ?> MB</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-credit-card mr-2 text-purple-600"></i>Payment Information
                        </h3>
                        <dl class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Amount Paid</dt>
                                <dd class="text-sm text-gray-900">GHS <?php echo number_format($submission['amount'], 2); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Payment Reference</dt>
                                <dd class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($submission['reference']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Submission Date</dt>
                                <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?></dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex space-x-4">
                            <a href="download_submission.php?id=<?php echo $submission['id']; ?>" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Download File
                            </a>
                            <a href="export_submissions.php?type=single&id=<?php echo $submission['id']; ?>" 
                               class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                                <i class="fas fa-archive mr-2"></i>Export as ZIP
                            </a>
                            <a href="dashboard.php" 
                               class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
