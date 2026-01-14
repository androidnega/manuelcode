<?php
// Include support agent authentication check
include 'auth/check_support_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';
include '../includes/user_activity_tracker.php';

$support_agent_name = $_SESSION['support_agent_name'] ?? $_SESSION['admin_name'] ?? 'Support Agent';

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'reply_ticket':
                $ticket_id = $_POST['ticket_id'] ?? '';
                $reply_message = $_POST['reply_message'] ?? '';
                $status = $_POST['status'] ?? 'replied';
                
                if (!empty($ticket_id) && !empty($reply_message)) {
                    try {
                        // Insert reply
                        $stmt = $pdo->prepare("
                            INSERT INTO ticket_replies (ticket_id, replied_by, reply_message, support_agent_id, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$ticket_id, $support_agent_name, $reply_message, $_SESSION['support_agent_id'] ?? null]);
                        
                        // Update ticket status
                        $stmt = $pdo->prepare("
                            UPDATE support_tickets 
                            SET status = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$status, $ticket_id]);
                        
                        $success_message = "Reply sent successfully!";
                    } catch (Exception $e) {
                        $error_message = "Error sending reply: " . $e->getMessage();
                    }
                }
                break;
                
            case 'update_status':
                $ticket_id = $_POST['ticket_id'] ?? '';
                $status = $_POST['status'] ?? '';
                
                if (!empty($ticket_id) && !empty($status)) {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE support_tickets 
                            SET status = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$status, $ticket_id]);
                        
                        $success_message = "Ticket status updated successfully!";
                    } catch (Exception $e) {
                        $error_message = "Error updating status: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get ticket statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_tickets FROM support_tickets");
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total_tickets'];

$stmt = $pdo->query("SELECT COUNT(*) as open_tickets FROM support_tickets WHERE status = 'open'");
$open_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['open_tickets'];

$stmt = $pdo->query("SELECT COUNT(*) as replied_tickets FROM support_tickets WHERE status = 'replied'");
$replied_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['replied_tickets'];

$stmt = $pdo->query("SELECT COUNT(*) as closed_tickets FROM support_tickets WHERE status = 'closed'");
$closed_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['closed_tickets'];

// Get recent tickets
$stmt = $pdo->query("
    SELECT st.*, u.name as user_name, u.email as user_email, u.user_id
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    ORDER BY st.created_at DESC 
    LIMIT 10
");
$recent_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Dashboard - ManuelCode</title>
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
        
        @media (max-width: 768px) {
            .main-content {
                padding: 0.75rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .stats-card {
                padding: 1rem;
                border-radius: 12px;
            }
            .mobile-header {
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 30;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
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
        <aside id="sidebar" class="sidebar-transition fixed lg:sticky top-0 left-0 z-50 w-64 bg-[#2D3E50] text-white transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen">
            <div class="flex items-center justify-between p-6 border-b border-[#243646] lg:border-none">
                <div class="font-bold text-xl">Support Panel</div>
                <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto scrollbar-hide">
                <nav class="mt-4 px-2 pb-4">
                    <a href="support_dashboard.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                        <span class="flex-1">Dashboard</span>
                    </a>
                    <a href="support_tickets.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-ticket-alt mr-3 w-5 text-center"></i>
                        <span class="flex-1">All Tickets</span>
                    </a>
                    <a href="support_open_tickets.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-exclamation-circle mr-3 w-5 text-center"></i>
                        <span class="flex-1">Open Tickets</span>
                    </a>
                    <a href="support_closed_tickets.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-check-circle mr-3 w-5 text-center"></i>
                        <span class="flex-1">Closed Tickets</span>
                    </a>
                    <?php if (($_SESSION['user_role'] ?? 'user') === 'admin' || ($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
                    <a href="dashboard.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-cog mr-3 w-5 text-center"></i>
                        <span class="flex-1">Admin Panel</span>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="p-4 border-t border-[#243646]">
                <a href="auth/logout.php" class="flex items-center py-3 px-4 text-red-300 hover:bg-[#243646] rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0 min-h-screen">
            <!-- Desktop Header -->
            <header class="hidden lg:block bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-[#2D3E50]">Support Dashboard</h1>
                            <p class="text-gray-600 mt-1">Manage user support tickets and provide assistance.</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($support_agent_name); ?></span>
                            <a href="auth/logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Mobile Header -->
            <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 mobile-header">
                <div class="flex items-center justify-between">
                    <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="font-semibold text-gray-800">Support Dashboard</h1>
                    <div class="w-8"></div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="px-6 py-8">
                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-check mr-2"></i><?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-ticket-alt text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 flex-1">
                                <h2 class="text-gray-600 text-sm">Total Tickets</h2>
                                <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]"><?php echo $total_tickets; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-orange-100 text-orange-600">
                                <i class="fas fa-exclamation-circle text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 flex-1">
                                <h2 class="text-gray-600 text-sm">Open Tickets</h2>
                                <p class="text-lg lg:text-2xl font-bold text-[#FF9800]"><?php echo $open_tickets; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-reply text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 flex-1">
                                <h2 class="text-gray-600 text-sm">Replied</h2>
                                <p class="text-lg lg:text-2xl font-bold text-[#FFC107]"><?php echo $replied_tickets; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 lg:p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-check-circle text-lg lg:text-xl"></i>
                            </div>
                            <div class="ml-3 lg:ml-4 flex-1">
                                <h2 class="text-gray-600 text-sm">Closed</h2>
                                <p class="text-lg lg:text-2xl font-bold text-[#4CAF50]"><?php echo $closed_tickets; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center mb-3">
                            <div class="p-2 rounded-full bg-orange-100 text-orange-600">
                                <i class="fas fa-exclamation-circle text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-[#2D3E50] ml-3">Open Tickets</h3>
                        </div>
                        <p class="mb-4 text-gray-600 text-sm">Review and respond to new support requests.</p>
                        <a href="support_open_tickets.php" class="inline-flex items-center justify-center w-full bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors text-sm">
                            <i class="fas fa-eye mr-2"></i>
                            View Open Tickets
                        </a>
                    </div>

                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center mb-3">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-ticket-alt text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-[#2D3E50] ml-3">All Tickets</h3>
                        </div>
                        <p class="mb-4 text-gray-600 text-sm">View and manage all support tickets.</p>
                        <a href="support_tickets.php" class="inline-flex items-center justify-center w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors text-sm">
                            <i class="fas fa-list mr-2"></i>
                            View All Tickets
                        </a>
                    </div>

                    <div class="bg-white p-4 lg:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex items-center mb-3">
                            <div class="p-2 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-[#2D3E50] ml-3">Closed Tickets</h3>
                        </div>
                        <p class="mb-4 text-gray-600 text-sm">Review resolved support tickets.</p>
                        <a href="support_closed_tickets.php" class="inline-flex items-center justify-center w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors text-sm">
                            <i class="fas fa-archive mr-2"></i>
                            View Closed Tickets
                        </a>
                    </div>
                </div>

                <!-- Recent Tickets -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">Recent Tickets</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <?php if ($recent_tickets): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket ID</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($recent_tickets as $ticket): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">#<?php echo $ticket['id']; ?></td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">
                                                    <div>
                                                        <div><?php echo htmlspecialchars($ticket['user_name'] ?? 'Guest'); ?></div>
                                                        <?php if ($ticket['user_id']): ?>
                                                            <div class="text-gray-500 font-mono text-xs">ID: <?php echo htmlspecialchars($ticket['user_id']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars(substr($ticket['subject'], 0, 50)) . (strlen($ticket['subject']) > 50 ? '...' : ''); ?>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm">
                                                    <?php
                                                    $status_colors = [
                                                        'open' => 'bg-red-100 text-red-800',
                                                        'replied' => 'bg-yellow-100 text-yellow-800',
                                                        'closed' => 'bg-green-100 text-green-800'
                                                    ];
                                                    $status_color = $status_colors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                                        <?php echo ucfirst($ticket['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-600">
                                                    <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm">
                                                    <a href="../dashboard/view-ticket?id=<?php echo $ticket['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No tickets found.</p>
                                <p class="text-sm text-gray-500 mt-1">Support tickets will appear here once users submit them.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Global function for sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                // Open sidebar
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                // Close sidebar
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Close sidebar when clicking on a link (mobile)
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('#sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) { // lg breakpoint
                        toggleSidebar();
                    }
                });
            });

            // Close sidebar on window resize if screen becomes large
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('mobile-overlay');
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>
