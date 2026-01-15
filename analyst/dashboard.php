<?php
session_start();
include '../includes/db.php';

// Check if analyst is logged in
if (!isset($_SESSION['analyst_logged_in']) || $_SESSION['analyst_logged_in'] !== true) {
    header('Location: /analyst/login.php');
    exit;
}

$analyst_id = $_SESSION['analyst_id'];
$analyst_name = $_SESSION['analyst_name'];
$analyst_email = $_SESSION['analyst_email'];

// Get submission statistics
try {
    // Get overall statistics - all submissions
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_submissions,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as successful_submissions,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_submissions,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_submissions,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_revenue
        FROM submissions
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent successful submissions (paid only)
    $stmt = $pdo->prepare("
        SELECT * FROM submissions 
        WHERE status = 'paid'
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute();
    $recent_submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching submission data: " . $e->getMessage());
    $stats = [
        'total_submissions' => 0,
        'successful_submissions' => 0,
        'failed_submissions' => 0,
        'total_revenue' => 0
    ];
    $recent_submissions = [];
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
    header('Location: /analyst/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyst Dashboard - ManuelCode</title>
    
    <!-- Analyst Dashboard Meta Tags -->
    <meta name="description" content="Analyst dashboard for managing student submissions, payments, and project reports.">
    <meta name="keywords" content="analyst dashboard, student submissions, payments, project reports, management">
    <meta name="author" content="ManuelCode">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Analyst Dashboard - ManuelCode">
    <meta property="og:description" content="Analyst dashboard for managing student submissions, payments, and project reports.">
    <meta property="og:image" content="https://manuelcode.info/assets/favi/favicon.png">
    <meta property="og:url" content="https://manuelcode.info/analyst/dashboard.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ManuelCode">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Analyst Dashboard - ManuelCode">
    <meta name="twitter:description" content="Analyst dashboard for managing student submissions, payments, and project reports.">
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
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>Analyst Dashboard
                        </h1>
                    </div>
                                         <div class="flex items-center space-x-3 sm:space-x-4">
                         <a href="/dashboard/analyst-settings" 
                            class="bg-blue-500 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-md hover:bg-blue-600 transition-colors text-sm shadow-sm">
                             <i class="fas fa-cog mr-1 sm:mr-2"></i><span class="hidden sm:inline">Settings</span><span class="sm:hidden">⚙️</span>
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
                    Welcome back, <span class="text-blue-600"><?php echo htmlspecialchars($analyst_name); ?></span>!
                </h2>
                <p class="text-sm sm:text-base text-gray-600">
                    Manage student submissions and monitor your project reports dashboard.
                </p>
            </div>
                         <!-- Success/Error Messages -->
             <?php if (isset($_GET['message']) && $_GET['message'] === 'no_submissions'): ?>
                 <div class="bg-amber-100 border border-amber-300 text-amber-800 px-4 py-3 rounded-lg mb-6 shadow-sm">
                     <div class="flex items-center justify-center sm:justify-start">
                         <i class="fas fa-info-circle mr-2 text-amber-600"></i>
                         <span class="text-sm">No successful submissions found to export. Submissions will appear here after students complete payment.</span>
                     </div>
                 </div>
             <?php endif; ?>
             

             

                          <!-- Statistics Cards -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-8">
                  <div class="bg-blue-50 overflow-hidden shadow-sm rounded-lg border border-blue-200">
                      <div class="p-4 sm:p-5">
                          <div class="flex items-center">
                              <div class="flex-shrink-0">
                                  <i class="fas fa-file-upload text-2xl sm:text-3xl text-blue-600"></i>
                              </div>
                              <div class="ml-4 sm:ml-5 w-0 flex-1">
                                  <dl>
                                                                             <dt class="text-xs sm:text-sm font-medium text-gray-600 truncate">Submissions</dt>
                                       <dd class="text-base sm:text-lg font-medium text-gray-800 flex items-center">
                                           <span class="bg-blue-100 text-blue-800 rounded-full px-2 py-1 text-sm font-semibold mr-2">
                                               <?php echo number_format($stats['successful_submissions']); ?>
                                           </span>
                                       </dd>
                                  </dl>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="bg-green-50 overflow-hidden shadow-sm rounded-lg border border-green-200">
                      <div class="p-4 sm:p-5">
                          <div class="flex items-center">
                              <div class="flex-shrink-0">
                                  <i class="fas fa-money-bill-wave text-2xl sm:text-3xl text-green-600"></i>
                              </div>
                              <div class="ml-4 sm:ml-5 w-0 flex-1">
                                  <dl>
                                      <dt class="text-xs sm:text-sm font-medium text-gray-600 truncate">Total Revenue</dt>
                                      <dd class="text-base sm:text-lg font-medium text-gray-800">GHS <?php echo number_format($stats['total_revenue'], 2); ?></dd>
                                  </dl>
                              </div>
                          </div>
                      </div>
                  </div>
                           </div>
 
                                                       <!-- Settings Quick Access -->
               <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-8">
                   <div class="px-4 py-5 sm:p-6">
                       <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 space-y-3 sm:space-y-0">
                           <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-700">
                               <i class="fas fa-cog mr-2 text-blue-600"></i>System Settings
                           </h3>
                           <a href="/dashboard/analyst-settings" 
                              class="bg-blue-500 text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-200 text-sm shadow-sm">
                               <i class="fas fa-external-link-alt mr-2"></i>Open Settings
                           </a>
                       </div>
                       <p class="text-sm text-gray-600 mb-4">
                           Manage submission system controls, pricing, and scheduling settings.
                       </p>
                       
                       <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                           <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                               <div class="flex items-center space-x-3">
                                   <i class="fas fa-toggle-on text-2xl text-blue-600"></i>
                                   <div>
                                       <h4 class="text-sm font-medium text-gray-900">Submission Control</h4>
                                       <p class="text-xs text-gray-500">Enable/disable submissions</p>
                                   </div>
                               </div>
                           </div>
                           
                           <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                               <div class="flex items-center space-x-3">
                                   <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
                                   <div>
                                       <h4 class="text-sm font-medium text-gray-900">Price Control</h4>
                                       <p class="text-xs text-gray-500">Set submission pricing</p>
                                   </div>
                               </div>
                           </div>
                           
                           <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                               <div class="flex items-center space-x-3">
                                   <i class="fas fa-clock text-2xl text-purple-600"></i>
                                   <div>
                                       <h4 class="text-sm font-medium text-gray-900">Scheduling</h4>
                                       <p class="text-xs text-gray-500">Auto enable/disable</p>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
 
                                                       <!-- Quick Actions Section -->
               <div class="bg-gray-50 shadow-sm rounded-lg border border-gray-200 mb-8">
                   <div class="px-4 py-5 sm:p-6">
                       <div class="flex justify-between items-center mb-4">
                           <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-700">
                               <i class="fas fa-tools mr-2 text-blue-600"></i>Quick Actions
                           </h3>
                       </div>
                       <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                           <a href="/dashboard/analyst-reference-lookup" 
                              class="bg-blue-500 text-white px-4 sm:px-6 py-3 sm:py-4 rounded-lg hover:bg-blue-600 transition-all duration-200 text-center shadow-sm hover:shadow-md">
                               <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-3">
                                   <i class="fas fa-search text-xl sm:text-2xl"></i>
                                   <div>
                                       <div class="font-semibold text-sm sm:text-lg">Track Payment</div>
                                       <div class="text-xs sm:text-sm opacity-90">Lookup submissions by reference</div>
                                   </div>
                               </div>
                           </a>
                           <a href="/dashboard/analyst-price-control" 
                              class="bg-green-500 text-white px-4 sm:px-6 py-3 sm:py-4 rounded-lg hover:bg-green-600 transition-all duration-200 text-center shadow-sm hover:shadow-md">
                               <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-3">
                                   <i class="fas fa-dollar-sign text-xl sm:text-2xl"></i>
                                   <div>
                                       <div class="font-semibold text-sm sm:text-lg">Price Control</div>
                                       <div class="text-xs sm:text-sm opacity-90">Manage submission pricing</div>
                                   </div>
                               </div>
                           </a>
                           <a href="/dashboard/analyst-download-all" 
                              class="bg-purple-500 text-white px-4 sm:px-6 py-3 sm:py-4 rounded-lg hover:bg-purple-600 transition-all duration-200 text-center shadow-sm hover:shadow-md sm:col-span-2 lg:col-span-1">
                               <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-3">
                                   <i class="fas fa-file-archive text-xl sm:text-2xl"></i>
                                   <div>
                                       <div class="font-semibold text-sm sm:text-lg">Export All ZIP</div>
                                       <div class="text-xs sm:text-sm opacity-90">Download all documents</div>
                                   </div>
                               </div>
                           </a>
                       </div>
                   </div>
               </div>
 

 
                           <!-- Successful Submissions -->
             <div class="bg-white shadow rounded-lg">
                 <div class="px-4 py-5 sm:p-6">
                                          <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                                                     <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900">
                               <i class="fas fa-check-circle mr-2 text-green-600"></i>Submissions 
                               <span class="bg-green-100 text-green-800 rounded-full px-2 py-1 text-sm font-semibold ml-2">
                                   <span id="submission-count"><?php echo count($recent_submissions); ?></span>
                               </span>
                           </h3>
                          
                          <div class="flex flex-wrap gap-2">
                              <!-- Bulk Delete Button -->
                              <button id="bulk-delete-btn" 
                                      class="bg-red-500 text-white px-3 sm:px-4 py-2 rounded-md hover:bg-red-600 transition-colors text-xs sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                      style="display: none;">
                                  <i class="fas fa-trash-alt mr-1 sm:mr-2"></i><span class="hidden sm:inline">Delete Selected (<span id="selected-count">0</span>)</span><span class="sm:hidden">Delete</span>
                              </button>
                              
                              <!-- ZIP Export Button (Prominent) -->
                              <?php if (class_exists('ZipArchive')): ?>
                                  <a href="/dashboard/analyst-download-all" 
                                     class="bg-purple-500 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:bg-purple-600 transition-colors font-semibold text-sm sm:text-base">
                                      <i class="fas fa-file-archive mr-1 sm:mr-2"></i><span class="hidden sm:inline">📦 Export All ZIP</span><span class="sm:hidden">ZIP</span>
                                  </a>
                              <?php endif; ?>
                              
                              <!-- Download All Documents -->
                              <a href="/dashboard/analyst-download-all" 
                                 class="bg-green-500 text-white px-3 sm:px-4 py-2 rounded-md hover:bg-green-600 transition-colors text-xs sm:text-sm">
                                  <i class="fas fa-download mr-1 sm:mr-2"></i><span class="hidden sm:inline">Download All</span><span class="sm:hidden">All</span>
                              </a>
                              
                              <!-- Export Info -->
                              <a href="/dashboard/analyst-export-submissions?type=all" 
                                 class="bg-blue-500 text-white px-3 sm:px-4 py-2 rounded-md hover:bg-blue-600 transition-colors text-xs sm:text-sm">
                                  <i class="fas fa-file-text mr-1 sm:mr-2"></i><span class="hidden sm:inline">Export Info</span><span class="sm:hidden">Info</span>
                              </a>
                              
                              <!-- ZIP Status Indicator -->
                              <?php if (class_exists('ZipArchive')): ?>
                                  <span class="inline-flex items-center px-2 sm:px-3 py-2 rounded-md text-xs sm:text-sm font-medium bg-green-100 text-green-800">
                                      <i class="fas fa-check-circle mr-1 sm:mr-2"></i><span class="hidden sm:inline">ZIP Ready</span><span class="sm:hidden">Ready</span>
                                  </span>
                              <?php else: ?>
                                  <a href="../enable_zip_auto.php" target="_blank"
                                     class="bg-orange-500 text-white px-3 sm:px-4 py-2 rounded-md hover:bg-orange-600 transition-colors text-xs sm:text-sm">
                                      <i class="fas fa-cog mr-1 sm:mr-2"></i><span class="hidden sm:inline">Enable ZIP</span><span class="sm:hidden">Enable</span>
                                  </a>
                              <?php endif; ?>
                          </div>
                      </div>
                     
                                           <!-- Search and Filter Section -->
                      <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-6">
                          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                              <!-- Search Box -->
                              <div>
                                  <label for="search-input" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Search</label>
                                  <input type="text" 
                                         id="search-input" 
                                         placeholder="Search by name, index number (ITS/ITN/ITD), phone, file..."
                                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                  <p class="text-xs text-gray-500 mt-1">Quick search: Type ITS, ITN, or ITD to find all students in that specialization</p>
                              </div>
                              
                              <!-- Date Filter -->
                              <div>
                                  <label for="date-filter" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Date Range</label>
                                  <select id="date-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                      <option value="">All Time</option>
                                      <option value="today">Today</option>
                                      <option value="week">This Week</option>
                                      <option value="month">This Month</option>
                                      <option value="year">This Year</option>
                                  </select>
                              </div>
                              
                              <!-- Clear Filters -->
                              <div class="flex items-end">
                                  <button id="clear-filters" 
                                          class="w-full bg-gray-500 text-white px-3 sm:px-4 py-2 rounded-md hover:bg-gray-600 transition-colors text-xs sm:text-sm">
                                      <i class="fas fa-times mr-1 sm:mr-2"></i>Clear Filters
                                  </button>
                              </div>
                          </div>
                      </div>
                    
                                         <?php if (empty($recent_submissions)): ?>
                         <div class="text-center py-8">
                             <i class="fas fa-check-circle text-3xl sm:text-4xl text-gray-400 mb-4"></i>
                             <p class="text-sm sm:text-base text-gray-500">No successful submissions found.</p>
                         </div>
                     <?php else: ?>
                         <!-- No results message (hidden by default) -->
                         <div id="no-results-message" class="text-center py-8" style="display: none;">
                             <i class="fas fa-search text-3xl sm:text-4xl text-gray-400 mb-4"></i>
                             <p class="text-sm sm:text-base text-gray-500">No submissions found matching your search criteria.</p>
                             <p class="text-xs text-gray-400 mt-2">Try adjusting your search terms or clearing the filters.</p>
                         </div>
                                                  <div class="overflow-x-auto">
                              <!-- Mobile Cards View -->
                              <div class="grid grid-cols-1 gap-3 sm:gap-4 lg:hidden">
                                                                   <?php foreach ($recent_submissions as $submission): ?>
                                      <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 submission-card" 
                                           data-search="<?php echo strtolower(htmlspecialchars($submission['name'] . ' ' . $submission['index_number'] . ' ' . $submission['phone_number'] . ' ' . $submission['file_name'])); ?>"
                                           data-date="<?php echo date('Y-m-d', strtotime($submission['created_at'])); ?>"
                                           data-submission-id="<?php echo $submission['id']; ?>">
                                          <!-- Checkbox and Student Info -->
                                          <div class="mb-3 sm:mb-4">
                                              <div class="flex justify-between items-start">
                                                  <div class="flex items-start flex-1 min-w-0">
                                                      <input type="checkbox" 
                                                             class="submission-checkbox mt-1 mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                             value="<?php echo $submission['id']; ?>"
                                                             data-submission-name="<?php echo htmlspecialchars($submission['name']); ?>">
                                                      <div class="flex-1 min-w-0">
                                                          <h4 class="font-semibold text-gray-900 text-base sm:text-lg truncate"><?php echo htmlspecialchars($submission['name']); ?></h4>
                                                          <p class="text-xs sm:text-sm text-gray-600 font-medium"><?php echo htmlspecialchars($submission['index_number']); ?></p>
                                                          <p class="text-xs sm:text-sm text-gray-500">📞 <?php echo htmlspecialchars($submission['phone_number']); ?></p>
                                                      </div>
                                                  </div>
                                                  <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded ml-2 flex-shrink-0"><?php echo date('M j, Y', strtotime($submission['created_at'])); ?></span>
                                              </div>
                                          </div>
                                          
                                          <!-- File Info -->
                                          <div class="mb-3 sm:mb-4 p-2 sm:p-3 bg-gray-50 rounded-lg">
                                              <div class="flex items-center mb-2">
                                                  <i class="fas fa-file-alt text-blue-600 mr-2 text-sm"></i>
                                                  <span class="text-xs sm:text-sm font-medium text-gray-900">Project Document</span>
                                              </div>
                                              <p class="text-xs sm:text-sm text-gray-900 mb-1 truncate">📄 <?php echo htmlspecialchars($submission['file_name']); ?></p>
                                              <p class="text-xs text-gray-500"><?php echo strtoupper($submission['file_type']); ?> • <?php echo round($submission['file_size'] / 1024 / 1024, 2); ?> MB</p>
                                          </div>
                                          
                                          <!-- Reference Info -->
                                          <div class="mb-3 sm:mb-4">
                                              <p class="text-xs text-gray-500 mb-1">Payment Reference:</p>
                                              <p class="text-xs font-mono bg-blue-50 text-blue-800 px-2 py-1 rounded border break-all"><?php echo htmlspecialchars($submission['reference']); ?></p>
                                          </div>
                                          <!-- Action Buttons -->
                                          <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-1 sm:gap-2 pt-3 border-t border-gray-200">
                                              <a href="/dashboard/analyst-view-submission?id=<?php echo $submission['id']; ?>" 
                                                 class="bg-blue-500 text-white px-2 sm:px-3 py-2 rounded-md text-xs sm:text-sm font-medium hover:bg-blue-600 transition-colors flex items-center justify-center">
                                                  <i class="fas fa-eye mr-1 sm:mr-2 text-xs sm:text-sm"></i><span class="hidden xs:inline">View</span><span class="xs:hidden">V</span>
                                              </a>
                                              <?php if (!empty($submission['file_path'])): ?>
                                                  <a href="download_submission.php?id=<?php echo $submission['id']; ?>" 
                                                     class="bg-green-500 text-white px-2 sm:px-3 py-2 rounded-md text-xs sm:text-sm font-medium hover:bg-green-600 transition-colors flex items-center justify-center">
                                                      <i class="fas fa-download mr-1 sm:mr-2 text-xs sm:text-sm"></i><span class="hidden xs:inline">Download</span><span class="xs:hidden">D</span>
                                                  </a>
                                                  <a href="export_submissions.php?type=single&id=<?php echo $submission['id']; ?>" 
                                                     class="bg-purple-500 text-white px-2 sm:px-3 py-2 rounded-md text-xs sm:text-sm font-medium hover:bg-purple-600 transition-colors flex items-center justify-center">
                                                      <i class="fas fa-export mr-1 sm:mr-2 text-xs sm:text-sm"></i><span class="hidden xs:inline">Export</span><span class="xs:hidden">E</span>
                                                  </a>
                                                  <?php if (class_exists('ZipArchive')): ?>
                                                      <a href="export_submissions.php?type=single&id=<?php echo $submission['id']; ?>&format=zip" 
                                                         class="bg-orange-500 text-white px-2 sm:px-3 py-2 rounded-md text-xs sm:text-sm font-medium hover:bg-orange-600 transition-colors flex items-center justify-center" title="Export as ZIP">
                                                          <i class="fas fa-file-archive mr-1 sm:mr-2 text-xs sm:text-sm"></i>ZIP
                                                      </a>
                                                  <?php endif; ?>
                                              <?php endif; ?>
                                              <button onclick="deleteSubmission(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['name']); ?>')" 
                                                      class="bg-red-500 text-white px-2 sm:px-3 py-2 rounded-md text-xs sm:text-sm font-medium hover:bg-red-600 transition-colors flex items-center justify-center col-span-2 sm:col-span-1">
                                                  <i class="fas fa-trash mr-1 sm:mr-2 text-xs sm:text-sm"></i><span class="hidden xs:inline">Delete</span><span class="xs:hidden">X</span>
                                              </button>
                                          </div>
                                      </div>
                                  <?php endforeach; ?>
                             </div>
                             
                                                           <!-- Desktop Table View -->
                              <table class="min-w-full divide-y divide-gray-200 hidden lg:table submission-table">
                                  <thead class="bg-gray-50">
                                      <tr>
                                          <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              <input type="checkbox" 
                                                     id="select-all-checkbox"
                                                     class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                          </th>
                                          <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                          <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                          <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Reference</th>
                                          <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                          <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                      </tr>
                                  </thead>
                                  <tbody class="bg-white divide-y divide-gray-200">
                                      <?php foreach ($recent_submissions as $submission): ?>
                                          <tr class="hover:bg-gray-50 submission-row" 
                                              data-search="<?php echo strtolower(htmlspecialchars($submission['name'] . ' ' . $submission['index_number'] . ' ' . $submission['phone_number'] . ' ' . $submission['file_name'])); ?>"
                                              data-date="<?php echo date('Y-m-d', strtotime($submission['created_at'])); ?>"
                                              data-submission-id="<?php echo $submission['id']; ?>">
                                              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                  <input type="checkbox" 
                                                         class="submission-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                         value="<?php echo $submission['id']; ?>"
                                                         data-submission-name="<?php echo htmlspecialchars($submission['name']); ?>">
                                              </td>
                                              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                  <div>
                                                      <div class="text-sm font-medium text-gray-900">
                                                          <?php echo htmlspecialchars($submission['name']); ?>
                                                      </div>
                                                      <div class="text-sm text-gray-500">
                                                          <?php echo htmlspecialchars($submission['index_number']); ?>
                                                      </div>
                                                      <div class="text-sm text-gray-500">
                                                          <?php echo htmlspecialchars($submission['phone_number']); ?>
                                                      </div>
                                                  </div>
                                              </td>
                                              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                  <div>
                                                      <div class="text-sm text-gray-900">
                                                          <?php echo htmlspecialchars($submission['file_name']); ?>
                                                      </div>
                                                      <div class="text-sm text-gray-500">
                                                          <?php echo strtoupper($submission['file_type']); ?> • 
                                                          <?php echo round($submission['file_size'] / 1024 / 1024, 2); ?> MB
                                                      </div>
                                                  </div>
                                              </td>
                                              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                  <div class="text-sm text-gray-500 font-mono">
                                                      <?php echo htmlspecialchars($submission['reference']); ?>
                                                  </div>
                                              </td>
                                              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                  <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?>
                                              </td>
                                              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                  <div class="flex flex-wrap space-x-1 sm:space-x-2">
                                                      <a href="/dashboard/analyst-view-submission?id=<?php echo $submission['id']; ?>" 
                                                         class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50">
                                                          <i class="fas fa-eye mr-1"></i>View
                                                      </a>
                                                      <?php if (!empty($submission['file_path'])): ?>
                                                          <a href="download_submission.php?id=<?php echo $submission['id']; ?>" 
                                                             class="text-green-600 hover:text-green-900 px-2 py-1 rounded hover:bg-green-50" title="Download the actual document file">
                                                              <i class="fas fa-file-download mr-1"></i>Download
                                                          </a>
                                                          <a href="export_submissions.php?type=single&id=<?php echo $submission['id']; ?>" 
                                                             class="text-purple-600 hover:text-purple-900 px-2 py-1 rounded hover:bg-purple-50" title="Export submission information">
                                                              <i class="fas fa-export mr-1"></i>Export
                                                          </a>
                                                          <?php if (class_exists('ZipArchive')): ?>
                                                              <a href="export_submissions.php?type=single&id=<?php echo $submission['id']; ?>&format=zip" 
                                                                 class="text-orange-600 hover:text-orange-900 px-2 py-1 rounded hover:bg-orange-50" title="Export as ZIP">
                                                                  <i class="fas fa-file-archive mr-1"></i>ZIP
                                                              </a>
                                                          <?php endif; ?>
                                                      <?php endif; ?>
                                                      <button onclick="deleteSubmission(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['name']); ?>')" 
                                                              class="text-red-600 hover:text-red-900 px-2 py-1 rounded hover:bg-red-50">
                                                          <i class="fas fa-trash mr-1"></i>Delete
                                                      </button>
                                                  </div>
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
     </div>
 
     <script>
         // Search and Filter Functionality
         const searchInput = document.getElementById('search-input');
         const dateFilter = document.getElementById('date-filter');
         const clearFiltersBtn = document.getElementById('clear-filters');
         const submissionCards = document.querySelectorAll('.submission-card');
         const submissionRows = document.querySelectorAll('.submission-row');
         const submissionCount = document.getElementById('submission-count');
         

 
                  function filterSubmissions() {
             const searchTerm = searchInput.value.toLowerCase();
             const dateRange = dateFilter.value;
             let visibleCount = 0;

             // Get current date for date filtering
             const currentDate = new Date();
             const today = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate());

             // Debug logging
             console.log('Filtering submissions:', { searchTerm, dateRange, totalCards: submissionCards.length });

                          // Filter mobile cards
             submissionCards.forEach(card => {
                 const searchData = card.dataset.search;
                 const cardDate = new Date(card.dataset.date);
                 let showCard = true;

                 // Search filter with specialization support
                 if (searchTerm) {
                     // Check for specialization search (ITS, ITN, ITD)
                     const specializationTerms = ['its', 'itn', 'itd'];
                     const isSpecializationSearch = specializationTerms.includes(searchTerm.toLowerCase());
                     
                     if (isSpecializationSearch) {
                         // Search specifically in index numbers for specialization
                         const indexNumber = searchData.match(/bc\/\w+\/\d+\/\d+/i);
                         if (indexNumber) {
                             const specialization = indexNumber[0].split('/')[1].toLowerCase();
                             showCard = searchTerm.toLowerCase() === specialization;
                         } else {
                             showCard = false;
                         }
                     } else {
                         // Regular search - make it more flexible
                         showCard = searchData.includes(searchTerm);
                     }
                     
                     // Debug logging for search
                     console.log('Search check:', { 
                         searchTerm, 
                         searchData, 
                         showCard, 
                         isSpecializationSearch,
                         studentName: card.querySelector('h4')?.textContent || 'Unknown'
                     });
                 }

                 // Date filter - only apply if search passed
                 if (dateRange && showCard) {
                     const cardDateOnly = new Date(cardDate.getFullYear(), cardDate.getMonth(), cardDate.getDate());
                     
                     switch (dateRange) {
                         case 'today':
                             showCard = cardDateOnly.getTime() === today.getTime();
                             break;
                         case 'week':
                             const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                             showCard = cardDateOnly >= weekAgo;
                             break;
                         case 'month':
                             const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
                             showCard = cardDateOnly >= monthAgo;
                             break;
                         case 'year':
                             const yearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
                             showCard = cardDateOnly >= yearAgo;
                             break;
                     }
                     
                     // Debug logging for date filter
                     console.log('Date filter check:', { 
                         dateRange, 
                         cardDate: cardDateOnly.toISOString().split('T')[0], 
                         today: today.toISOString().split('T')[0],
                         showCard 
                     });
                 }

                 card.style.display = showCard ? 'block' : 'none';
                 if (showCard) visibleCount++;
             });
 
                                                    // Filter desktop table rows
             submissionRows.forEach(row => {
                 const searchData = row.dataset.search;
                 const rowDate = new Date(row.dataset.date);
                 let showRow = true;

                 // Search filter with specialization support
                 if (searchTerm) {
                     // Check for specialization search (ITS, ITN, ITD)
                     const specializationTerms = ['its', 'itn', 'itd'];
                     const isSpecializationSearch = specializationTerms.includes(searchTerm.toLowerCase());
                     
                     if (isSpecializationSearch) {
                         // Search specifically in index numbers for specialization
                         const indexNumber = searchData.match(/bc\/\w+\/\d+\/\d+/i);
                         if (indexNumber) {
                             const specialization = indexNumber[0].split('/')[1].toLowerCase();
                             showRow = searchTerm.toLowerCase() === specialization;
                         } else {
                             showRow = false;
                         }
                     } else {
                         // Regular search - make it more flexible
                         showRow = searchData.includes(searchTerm);
                     }
                 }

                 // Date filter - only apply if search passed
                 if (dateRange && showRow) {
                     const rowDateOnly = new Date(rowDate.getFullYear(), rowDate.getMonth(), rowDate.getDate());
                     
                     switch (dateRange) {
                         case 'today':
                             showRow = rowDateOnly.getTime() === today.getTime();
                             break;
                         case 'week':
                             const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                             showRow = rowDateOnly >= weekAgo;
                             break;
                         case 'month':
                             const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
                             showRow = rowDateOnly >= monthAgo;
                             break;
                         case 'year':
                             const yearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
                             showRow = rowDateOnly >= yearAgo;
                             break;
                     }
                 }

                 row.style.display = showRow ? 'table-row' : 'none';
                 if (showRow) visibleCount++;
             });
 
                      // Update count
         submissionCount.textContent = visibleCount;
         
         // Show/hide no results message
         const noResultsMessage = document.getElementById('no-results-message');
         if (noResultsMessage) {
             noResultsMessage.style.display = visibleCount === 0 && (searchTerm || dateRange) ? 'block' : 'none';
         }
         }
 
         // Event listeners
         searchInput.addEventListener('input', filterSubmissions);
         dateFilter.addEventListener('change', filterSubmissions);
 
         clearFiltersBtn.addEventListener('click', () => {
             searchInput.value = '';
             dateFilter.value = '';
             filterSubmissions();
         });
 
         // Bulk delete functionality
         const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
         const selectAllCheckbox = document.getElementById('select-all-checkbox');
         const submissionCheckboxes = document.querySelectorAll('.submission-checkbox');
         const selectedCountSpan = document.getElementById('selected-count');
         
         // Update bulk delete button visibility and count
         function updateBulkDeleteButton() {
             const checkedBoxes = document.querySelectorAll('.submission-checkbox:checked');
             const count = checkedBoxes.length;
             
             if (count > 0) {
                 bulkDeleteBtn.style.display = 'inline-block';
                 selectedCountSpan.textContent = count;
                 bulkDeleteBtn.disabled = false;
             } else {
                 bulkDeleteBtn.style.display = 'none';
                 bulkDeleteBtn.disabled = true;
             }
             
             // Update select all checkbox state
             if (selectAllCheckbox) {
                 selectAllCheckbox.checked = count === submissionCheckboxes.length && count > 0;
                 selectAllCheckbox.indeterminate = count > 0 && count < submissionCheckboxes.length;
             }
         }
         
         // Select all checkbox handler
         if (selectAllCheckbox) {
             selectAllCheckbox.addEventListener('change', function() {
                 submissionCheckboxes.forEach(checkbox => {
                     checkbox.checked = this.checked;
                 });
                 updateBulkDeleteButton();
             });
         }
         
         // Individual checkbox handlers
         submissionCheckboxes.forEach(checkbox => {
             checkbox.addEventListener('change', updateBulkDeleteButton);
         });
         
         // Bulk delete button handler
         bulkDeleteBtn.addEventListener('click', function() {
             const checkedBoxes = document.querySelectorAll('.submission-checkbox:checked');
             const selectedIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
             const selectedNames = Array.from(checkedBoxes).map(cb => cb.dataset.submissionName);
             
             if (selectedIds.length === 0) {
                 alert('Please select at least one submission to delete.');
                 return;
             }
             
             const confirmMessage = `Are you sure you want to delete ${selectedIds.length} submission(s)?\n\nThis action cannot be undone and will permanently remove the submission records and associated files.\n\nSelected: ${selectedNames.join(', ')}`;
             
             if (confirm(confirmMessage)) {
                 // Show loading state
                 const originalText = this.innerHTML;
                 this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...';
                 this.disabled = true;
                 
                 // Send bulk delete request
                 fetch('delete_submission.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify({
                         submission_ids: selectedIds
                     })
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         // Remove all selected rows/cards from DOM
                         checkedBoxes.forEach(checkbox => {
                             const row = checkbox.closest('.submission-row') || checkbox.closest('.submission-card');
                             if (row) {
                                 row.remove();
                             }
                         });
                         
                         // Update count
                         const currentCount = parseInt(submissionCount.textContent);
                         submissionCount.textContent = currentCount - selectedIds.length;
                         
                         // Hide bulk delete button
                         updateBulkDeleteButton();
                         
                         // Show success message
                         alert(`Successfully deleted ${data.deleted_count || selectedIds.length} submission(s)!`);
                         
                         // Reload page if no submissions left
                         if (currentCount - selectedIds.length === 0) {
                             location.reload();
                         }
                     } else {
                         throw new Error(data.message || 'Failed to delete submissions');
                     }
                 })
                 .catch(error => {
                     alert('Error deleting submissions: ' + error.message);
                     // Restore button
                     this.innerHTML = originalText;
                     this.disabled = false;
                 });
             }
         });
         
         // Delete submission function (single delete)
         function deleteSubmission(submissionId, studentName) {
             if (confirm(`Are you sure you want to delete the submission for "${studentName}"?\n\nThis action cannot be undone and will permanently remove the submission record and associated file.`)) {
                 // Show loading state
                 const deleteBtn = event.target.closest('button');
                 const originalText = deleteBtn.innerHTML;
                 deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...';
                 deleteBtn.disabled = true;

                 // Send delete request
                 fetch('delete_submission.php', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify({
                         submission_id: submissionId
                     })
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         // Remove the row/card from DOM
                         const row = deleteBtn.closest('.submission-row') || deleteBtn.closest('.submission-card');
                         if (row) {
                             row.remove();
                             
                             // Update count
                             const currentCount = parseInt(submissionCount.textContent);
                             submissionCount.textContent = currentCount - 1;
                             
                             // Update bulk delete button
                             updateBulkDeleteButton();
                             
                             // Show success message
                             alert('Submission deleted successfully!');
                         }
                     } else {
                         throw new Error(data.message || 'Failed to delete submission');
                     }
                 })
                 .catch(error => {
                     alert('Error deleting submission: ' + error.message);
                     // Restore button
                     deleteBtn.innerHTML = originalText;
                     deleteBtn.disabled = false;
                 });
             }
         }
     </script>
 </body>
 </html>
