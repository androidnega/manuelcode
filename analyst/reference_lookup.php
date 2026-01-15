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

$submission = null;
$error_message = '';
$success_message = '';

// Handle reference lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_reference'])) {
    $reference = trim($_POST['reference']);
    
    if (!empty($reference)) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM submissions 
                WHERE reference = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$reference]);
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($submission) {
                $success_message = "Submission found successfully!";
                
                // Log the lookup action
                $stmt = $pdo->prepare("
                    INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                    VALUES (?, 'reference_lookup', ?, ?, ?, NOW())
                ");
                $details = json_encode(['reference' => $reference, 'submission_id' => $submission['id']]);
                $stmt->execute([$analyst_id, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                
            } else {
                $error_message = "No submission found with reference: " . htmlspecialchars($reference);
            }
        } catch (Exception $e) {
            $error_message = "Error looking up submission: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter a reference number.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
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
    <title>Reference Lookup - Analyst Dashboard</title>
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
                        <a href="/dashboard" class="text-xl font-semibold text-gray-900 mr-6">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <h1 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-search mr-2 text-blue-600"></i>Reference Lookup
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-700">
                            Welcome, <span class="font-medium"><?php echo htmlspecialchars($analyst_name); ?></span>
                        </div>
                        <a href="?logout=1" 
                           class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors text-sm">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reference Lookup Form -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-search mr-2 text-blue-600"></i>Track Student Payment
                    </h2>
                    <p class="text-gray-600">Enter a reference number to lookup submission and payment details.</p>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 mb-2">
                            Reference Number *
                        </label>
                        <div class="flex space-x-2">
                            <input type="text" 
                                   id="reference" 
                                   name="reference" 
                                   placeholder="e.g., SUB_68adc5591868f_1756218713"
                                   value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" name="lookup_reference"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-search mr-2"></i>Lookup
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Enter the reference number from student payment or SMS</p>
                    </div>
                </form>
            </div>

            <!-- Submission Results -->
            <?php if ($submission): ?>
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-green-50 border-b border-green-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-green-900">
                            <i class="fas fa-check-circle mr-2"></i>Submission Found
                        </h3>
                        <p class="text-sm text-green-700">Reference: <?php echo htmlspecialchars($submission['reference']); ?></p>
                    </div>

                    <div class="p-6">
                        <!-- Submission Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-3">
                                    <i class="fas fa-user mr-2 text-blue-600"></i>Student Information
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div><span class="font-medium">Name:</span> <?php echo htmlspecialchars($submission['name']); ?></div>
                                    <div><span class="font-medium">Index Number:</span> <?php echo htmlspecialchars($submission['index_number']); ?></div>
                                    <div><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($submission['phone_number']); ?></div>
                                    <div><span class="font-medium">File:</span> <?php echo htmlspecialchars($submission['file_name']); ?></div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900 mb-3">
                                    <i class="fas fa-credit-card mr-2 text-green-600"></i>Payment Information
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="font-medium">Amount:</span> 
                                        <span class="font-bold text-green-600">GHS <?php echo number_format($submission['amount'], 2); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Status:</span> 
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i><?php echo ucfirst($submission['status']); ?>
                                        </span>
                                    </div>
                                    <div><span class="font-medium">Date:</span> <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?></div>
                                    <div><span class="font-medium">Reference:</span> <span class="font-mono text-xs"><?php echo htmlspecialchars($submission['reference']); ?></span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3">
                            <a href="view_submission.php?id=<?php echo $submission['id']; ?>" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-eye mr-2"></i>View Full Details
                            </a>
                            
                            <?php if (!empty($submission['file_path'])): ?>
                                <a href="download_submission.php?id=<?php echo $submission['id']; ?>" 
                                   class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors text-sm">
                                    <i class="fas fa-download mr-2"></i>Download Document
                                </a>
                            <?php endif; ?>
                            
                            <a href="export_submissions.php?type=single&id=<?php echo $submission['id']; ?>" 
                               class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors text-sm">
                                <i class="fas fa-file-export mr-2"></i>Export Info
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Usage Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                <h4 class="font-medium text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>How to Use Reference Lookup
                </h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• Students receive reference numbers in SMS confirmations</li>
                    <li>• Reference numbers are also shown on the submission success page</li>
                    <li>• Use this tool to quickly verify payments and find submissions</li>
                    <li>• All lookups are logged for audit purposes</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
