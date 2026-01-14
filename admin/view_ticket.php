<?php
// Include support agent authentication check
include 'auth/check_support_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';
include '../includes/user_activity_tracker.php';

$support_agent_name = $_SESSION['support_agent_name'] ?? $_SESSION['admin_name'] ?? 'Support Agent';

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    header('Location: support_tickets.php');
    exit;
}

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'reply_ticket':
                $reply_message = $_POST['reply_message'] ?? '';
                $status = $_POST['status'] ?? 'replied';
                
                if (!empty($reply_message)) {
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
                $status = $_POST['status'] ?? '';
                
                if (!empty($status)) {
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

// Get ticket details
$stmt = $pdo->prepare("
    SELECT st.*, u.name as user_name, u.email as user_email, u.user_id
    FROM support_tickets st 
    LEFT JOIN users u ON st.user_id = u.id 
    WHERE st.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: support_tickets.php');
    exit;
}

// Get ticket replies
$stmt = $pdo->prepare("
    SELECT * FROM ticket_replies 
    WHERE ticket_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$ticket_id]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?php echo $ticket_id; ?> - ManuelCode</title>
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
                    <a href="support_closed_tickets.php" class="flex items-center py-3 px-4 hover:bg-[#243646] rounded-lg mb-2 transition-colors w-full">
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
                        <h1 class="text-2xl font-bold text-[#2D3E50]">Ticket #<?php echo $ticket_id; ?></h1>
                        <p class="text-gray-600 mt-1">View and respond to support ticket</p>
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
                    <h1 class="font-semibold text-gray-800">Ticket #<?php echo $ticket_id; ?></h1>
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

                <!-- Ticket Details -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                            <div class="flex items-center space-x-2">
                                <?php
                                $status_colors = [
                                    'open' => 'bg-red-100 text-red-800',
                                    'replied' => 'bg-yellow-100 text-yellow-800',
                                    'closed' => 'bg-green-100 text-green-800'
                                ];
                                $status_color = $status_colors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $status_color; ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">User Information</h3>
                                <div class="space-y-2">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($ticket['user_name'] ?? 'Guest'); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['user_email'] ?? $ticket['email']); ?></p>
                                    <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></p>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Ticket Information</h3>
                                <div class="space-y-2">
                                    <p><strong>Ticket ID:</strong> #<?php echo $ticket['id']; ?></p>
                                    <p><strong>Status:</strong> <?php echo ucfirst($ticket['status']); ?></p>
                                    <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($ticket['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Message</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($ticket['message']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Replies -->
                <?php if ($replies): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
                        <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">Replies (<?php echo count($replies); ?>)</h2>
                        </div>
                        <div class="p-4 lg:p-6">
                            <div class="space-y-4">
                                <?php foreach ($replies as $reply): ?>
                                    <div class="border-l-4 border-blue-500 pl-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($reply['replied_by']); ?></span>
                                                <span class="text-sm text-gray-500">(Support Agent)</span>
                                            </div>
                                            <span class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?></span>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($reply['reply_message']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reply Form -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg lg:text-xl font-semibold text-[#2D3E50]">Reply to Ticket</h2>
                    </div>
                    <div class="p-4 lg:p-6">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="reply_ticket">
                            
                            <div>
                                <label for="reply_message" class="block text-sm font-medium text-gray-700 mb-2">Your Reply</label>
                                <textarea 
                                    id="reply_message" 
                                    name="reply_message" 
                                    rows="6" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter your reply to the user..."
                                    required></textarea>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <label for="status" class="text-sm font-medium text-gray-700">Update Status:</label>
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="replied" <?php echo $ticket['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                    </select>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="support_tickets.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                        Back to Tickets
                                    </a>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        Send Reply
                                    </button>
                                </div>
                            </div>
                        </form>
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
