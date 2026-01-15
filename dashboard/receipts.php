<?php
// If accessed directly (not via router), redirect to router
if (!defined('ROUTER_INCLUDED') && !isset($_SERVER['HTTP_X_ROUTER'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
    header('Location: ' . $protocol . '://' . $host . '/dashboard/receipts');
    exit;
}

session_start();
include '../includes/auth_only.php';
include '../includes/db.php';
include '../includes/receipt_helper.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Get user's unique ID
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_unique_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'] ?? 'N/A';

// Get user's receipts
$receipts = get_user_receipts($user_id);

// Handle receipt view
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $receipt_id = (int)$_GET['id'];
    
    $receipt = get_receipt_by_id($receipt_id);
    
    if ($receipt && $receipt['user_id'] == $user_id) {
        // Generate receipt HTML for display
        $receipt_html = generate_receipt_html(
            ['id' => $receipt['purchase_id'], 'created_at' => $receipt['purchase_date'], 'amount' => $receipt['amount']],
            ['name' => $user_name, 'email' => $_SESSION['user_email'] ?? ''],
            ['title' => $receipt['product_title'], 'description' => '']
        );
        
        // Display receipt HTML in a clean format
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Receipt - <?php echo htmlspecialchars($receipt['product_title']); ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <style>
                @media print {
                    .no-print { display: none; }
                    body { margin: 0; padding: 20px; }
                }
            </style>
        </head>
        <body class="bg-gray-50">
            <div class="no-print bg-white shadow-sm border-b p-4">
                <div class="max-w-4xl mx-auto flex justify-between items-center">
                    <h1 class="text-xl font-semibold text-gray-800">Receipt</h1>
                    <div class="flex space-x-2">
                        <button onclick="generatePDF()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            <i class="fas fa-file-pdf mr-2"></i>Download PDF
                        </button>
                        <button onclick="window.print()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                        <button onclick="window.close()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            <i class="fas fa-times mr-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
            <div id="receipt-content" class="max-w-4xl mx-auto p-8">
                <?php echo $receipt_html; ?>
            </div>
            
            <script>
                function generatePDF() {
                    const element = document.getElementById('receipt-content');
                    const opt = {
                        margin: 1,
                        filename: 'receipt-<?php echo $receipt['receipt_number']; ?>.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2 },
                        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                    };
                    
                    html2pdf().set(opt).from(element).save();
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle PDF download
if (isset($_GET['action']) && $_GET['action'] === 'pdf' && isset($_GET['id'])) {
    $receipt_id = (int)$_GET['id'];
    
    $receipt = get_receipt_by_id($receipt_id);
    
    if ($receipt && $receipt['user_id'] == $user_id) {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="receipt-' . $receipt['receipt_number'] . '.pdf"');
        
        // For now, redirect to the HTML version which can be converted to PDF
        // In a production environment, you'd use a proper PDF library like TCPDF or mPDF
        echo '<script>window.location.href = "?action=view&id=' . $receipt_id . '";</script>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Receipts - Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .mobile-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .receipt-card {
            transition: all 0.3s ease;
        }
        .receipt-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 1024px) {
            .main-content {
                padding: 1.5rem;
            }
            .sidebar {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            .mobile-header {
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 30;
                background: white;
                border-bottom: 1px solid #e5e7eb;
            }
            .mobile-title {
                font-size: 1.25rem;
                font-weight: 600;
            }
        }
        
        @media (max-width: 640px) {
            .main-content {
                padding: 0.75rem;
            }
            .mobile-header {
                padding: 0.75rem;
            }
            .mobile-title {
                font-size: 1.125rem;
            }
        }
        
        @media (max-width: 1024px) {
            #sidebar {
                width: 280px;
                max-width: 85vw;
            }
            
            #sidebar a {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }
            
            #sidebar i {
                font-size: 1rem;
                width: 1.25rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Layout Container -->
    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="font-bold text-xl text-gray-800">Dashboard</div>
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto scrollbar-hide">
                <nav class="mt-4 px-4 pb-4">
                    <a href="/dashboard" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                        <span class="flex-1">Overview</span>
                    </a>
                    <a href="my-purchases" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-shopping-bag mr-3 w-5 text-center"></i>
                        <span class="flex-1">My Purchases</span>
                    </a>
                    <a href="downloads" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-download mr-3 w-5 text-center"></i>
                        <span class="flex-1">Downloads</span>
                    </a>
                    <a href="receipts" class="flex items-center py-3 px-4 bg-blue-50 text-blue-700 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-receipt mr-3 w-5 text-center"></i>
                        <span class="flex-1">Receipts</span>
                    </a>
                    <a href="settings" class="flex items-center py-3 px-4 text-gray-700 hover:bg-gray-50 rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-cog mr-3 w-5 text-center"></i>
                        <span class="flex-1">Settings</span>
                    </a>
                </nav>
            </div>
            
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                              <div class="ml-3">
            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_name); ?></p>
            <p class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($user_unique_id); ?></p>
          </div>
                </div>
                <a href="/auth/logout.php" class="flex items-center py-2 px-4 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0 min-h-screen">
            <!-- Desktop Header -->
            <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">My Receipts</h1>
                        <p class="text-gray-600 mt-1">View and download your purchase receipts</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-home mr-2"></i>Home
                        </a>
                        <a href="/store" class="text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-store mr-2"></i>Store
                        </a>
                    </div>
                </div>
            </header>

            <!-- Mobile Header -->
            <header class="lg:hidden bg-white border-b border-gray-200 mobile-header">
                <div class="flex items-center justify-between">
                    <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="mobile-title font-semibold text-gray-800">My Receipts</h1>
                    <div class="w-8"></div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content p-6">
                <!-- Receipts Grid -->
                <?php if (empty($receipts)): ?>
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-receipt text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Receipts Yet</h3>
                        <p class="text-gray-600 mb-6">You haven't made any purchases yet. Start shopping to get your first receipt!</p>
                        <a href="/store" class="inline-flex items-center px-4 py-2 bg-[#F5A623] text-white rounded-lg hover:bg-[#d88c1b] transition-colors">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Browse Products
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($receipts as $receipt): ?>
                            <div class="receipt-card dashboard-card p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center min-w-0 flex-1 mr-3">
                                        <div class="w-10 h-10 bg-[#F5A623] rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                            <i class="fas fa-receipt text-white"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($receipt['product_title']); ?></h3>
                                            <p class="text-sm text-gray-600 truncate">Receipt #<?php echo $receipt['receipt_number']; ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="text-lg font-bold text-[#F5A623]">₵<?php echo number_format($receipt['amount'], 2); ?></div>
                                        <div class="text-xs text-gray-500 whitespace-nowrap">
                                            <?php echo date('M j, Y', strtotime($receipt['purchase_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4">
                                    <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
                                        <span>Receipt Status:</span>
                                        <span class="flex items-center">
                                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                            <span class="text-green-600">Generated</span>
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="?action=view&id=<?php echo $receipt['id']; ?>" 
                                           target="_blank"
                                           class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <a href="?action=pdf&id=<?php echo $receipt['id']; ?>" 
                                           class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                            <i class="fas fa-file-pdf mr-1"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
