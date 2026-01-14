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
            case 'reopen_ticket':
                $ticket_id = $_POST['ticket_id'] ?? '';
                
                if (!empty($ticket_id)) {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE support_tickets 
                            SET status = 'open', updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$ticket_id]);
                        
                        $success_message = "Ticket reopened successfully!";
                    } catch (Exception $e) {
                        $error_message = "Error reopening ticket: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count of closed tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'closed'");
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_tickets / $limit);

// Get closed tickets with pagination
$stmt = $pdo->prepare("
    SELECT st.*, u.name as user_name, u.email as user_email, u.user_id
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    WHERE st.status = 'closed'
    ORDER BY st.updated_at DESC 
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset
);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Closed Support Tickets - ManuelCode</title>
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
                    <a href="support_dashboard.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
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
                    <a href="support_closed_tickets.php" class="flex items-center py-3 px-4 bg-[#243646] rounded-lg mb-2 transition-colors w-full">
                        <i class="fas fa-check-circle mr-3 w-5 text-center"></i>
                        <span class="flex-1">Closed Tickets</span>
                    </a>
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
            <header class="hidden lg:block bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-[#2D3E50]">Closed Support Tickets</h1>
                        <p class="text-gray-600 mt-1">Review resolved support tickets</p>
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
            <header class="lg:hidden bg-white shadow-sm border-b border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="font-semibold text-gray-800">Closed Support Tickets</h1>
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

                <!-- Tickets Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">Closed Tickets (<?php echo $total_tickets; ?>)</h2>
                            <div class="flex space-x-2">
                                <a href="support_tickets.php" class="inline-flex items-center px-3 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-ticket-alt mr-2"></i>All Tickets
                                </a>
                                <a href="support_open_tickets.php" class="inline-flex items-center px-3 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                                    <i class="fas fa-exclamation-circle mr-2"></i>Open Tickets
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 lg:p-6">
                        <?php if ($tickets): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket ID</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closed Date</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolution Time</th>
                                            <th class="px-3 lg:px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">#<?php echo $ticket['id']; ?></td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">
                                                    <div>
                                                        <div><?php echo htmlspecialchars($ticket['user_name'] ?? 'Guest'); ?></div>
                                                        <?php if ($ticket['user_id']): ?>
                                                            <div class="text-xs text-gray-500 font-mono">ID: <?php echo htmlspecialchars($ticket['user_id']); ?></div>
                                                        <?php endif; ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($ticket['user_email'] ?? $ticket['email']); ?></div>
                                                    </div>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars(substr($ticket['subject'], 0, 50)) . (strlen($ticket['subject']) > 50 ? '...' : ''); ?>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-600">
                                                    <?php echo date('M j, Y', strtotime($ticket['updated_at'])); ?>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm text-gray-600">
                                                    <?php 
                                                    $created = new DateTime($ticket['created_at']);
                                                    $closed = new DateTime($ticket['updated_at']);
                                                    $diff = $created->diff($closed);
                                                    echo $diff->days . ' days, ' . $diff->h . ' hours';
                                                    ?>
                                                </td>
                                                <td class="px-3 lg:px-4 py-3 text-sm">
                                                    <div class="flex space-x-2">
                                                        <a href="../dashboard/view-ticket?id=<?php echo $ticket['id']; ?>" 
                                                           class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                                            View
                                                        </a>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reopen this ticket?')">
                                                            <input type="hidden" name="action" value="reopen_ticket">
                                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                            <button type="submit" class="text-orange-600 hover:text-orange-800 font-medium text-sm">
                                                                Reopen
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="mt-6 flex items-center justify-between">
                                    <div class="text-sm text-gray-700">
                                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_tickets); ?> of <?php echo $total_tickets; ?> results
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                                Previous
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <a href="?page=<?php echo $i; ?>" 
                                               class="px-3 py-2 rounded-lg text-sm <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                                Next
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-archive text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No closed tickets found.</p>
                                <p class="text-sm text-gray-500 mt-1">Closed tickets will appear here once they are resolved.</p>
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
