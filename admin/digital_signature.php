<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth/login.php');
    exit();
}

$admin_username = $_SESSION['admin_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle signature upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_signature') {
            $signature_data = $_POST['signature_data'] ?? '';
            
            if (!empty($signature_data)) {
                try {
                    // Check if signature already exists
                    $stmt = $pdo->prepare("SELECT id FROM admin_signatures WHERE admin_id = ?");
                    $stmt->execute([$_SESSION['admin_id']]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // Update existing signature
                        $stmt = $pdo->prepare("UPDATE admin_signatures SET signature_data = ?, updated_at = NOW() WHERE admin_id = ?");
                        $stmt->execute([$signature_data, $_SESSION['admin_id']]);
                    } else {
                        // Insert new signature
                        $stmt = $pdo->prepare("INSERT INTO admin_signatures (admin_id, signature_data, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                        $stmt->execute([$_SESSION['admin_id'], $signature_data]);
                    }
                    
                    $success_message = 'Digital signature saved successfully!';
                } catch (Exception $e) {
                    $error_message = 'Error saving signature: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Please draw your signature before saving.';
            }
        } elseif ($_POST['action'] === 'clear_signature') {
            try {
                $stmt = $pdo->prepare("DELETE FROM admin_signatures WHERE admin_id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                $success_message = 'Digital signature cleared successfully!';
            } catch (Exception $e) {
                $error_message = 'Error clearing signature: ' . $e->getMessage();
            }
        }
    }
}

// Get current signature
$current_signature = null;
try {
    $stmt = $pdo->prepare("SELECT signature_data FROM admin_signatures WHERE admin_id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $current_signature = $stmt->fetchColumn();
} catch (Exception $e) {
    // Table might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signature Management - Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/session-timeout.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Layout Container -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:sticky top-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-700 to-slate-800 text-white transform -translate-x-full lg:translate-x-0 lg:flex lg:flex-col h-screen transition-transform duration-300 ease-in-out shadow-xl">
            <div class="flex items-center justify-between p-4 lg:p-6 border-b border-slate-600">
                <div class="font-bold text-lg lg:text-xl text-white">Admin Panel</div>
                <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-slate-300 transition-colors p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto">
                <nav class="mt-4 px-2 pb-4 space-y-1">
                    <a href="dashboard.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                        <span class="flex-1">Dashboard</span>
                    </a>
                    <a href="products.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-box mr-3 w-5 text-center"></i>
                        <span class="flex-1">Products</span>
                    </a>
                    <a href="projects.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-project-diagram mr-3 w-5 text-center"></i>
                        <span class="flex-1">Projects</span>
                    </a>
                    <a href="orders.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-shopping-cart mr-3 w-5 text-center"></i>
                        <span class="flex-1">Orders</span>
                    </a>
                    <a href="purchase_management.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-credit-card mr-3 w-5 text-center"></i>
                        <span class="flex-1">Purchase Management</span>
                    </a>
                    <a href="users.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-users mr-3 w-5 text-center"></i>
                        <span class="flex-1">Users</span>
                    </a>
                    <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
                        <span class="flex-1">Reports</span>
                    </a>
                    <a href="refunds.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-undo mr-3 w-5 text-center"></i>
                        <span class="flex-1">Refunds</span>
                    </a>
                    <a href="support_management.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-headset mr-3 w-5 text-center"></i>
                        <span class="flex-1">Support Management</span>
                    </a>
                    <a href="generate_receipts.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-receipt mr-3 w-5 text-center"></i>
                        <span class="flex-1">Generate Receipts</span>
                    </a>
                    <a href="digital_signature.php" class="flex items-center py-3 px-4 bg-slate-600 rounded-lg transition-colors w-full text-white">
                        <i class="fas fa-signature mr-3 w-5 text-center"></i>
                        <span class="flex-1">Digital Signature</span>
                    </a>
                    <a href="change_password.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-key mr-3 w-5 text-center"></i>
                        <span class="flex-1">Change Password</span>
                    </a>
                    <?php if (($_SESSION['user_role'] ?? 'user') === 'superadmin'): ?>
                    <a href="superadmin.php" class="flex items-center py-3 px-4 hover:bg-slate-600 rounded-lg transition-colors w-full text-slate-200 hover:text-white">
                        <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
                        <span class="flex-1">Super Admin</span>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="p-4 border-t border-slate-600">
                <a href="auth/logout.php" class="flex items-center py-3 px-4 text-red-300 hover:bg-slate-600 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0 min-h-screen">
            <!-- Mobile Header for toggling sidebar -->
            <header class="lg:hidden bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 sticky top-0 z-30">
                <div class="flex items-center justify-between p-4">
                    <button onclick="toggleSidebar()" class="text-slate-600 hover:text-slate-900 p-2 transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800">Digital Signature</h1>
                    <div class="w-8"></div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 lg:p-6 overflow-x-hidden">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent">Digital Signature Management</h1>
                    <p class="text-slate-600 mt-2">Create and manage your digital signature for receipts and documents</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-check-circle mt-1 mr-3"></i>
                            <p class="text-sm"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                            <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Signature Canvas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Draw Your Signature</h2>
                        
                        <div class="mb-4">
                            <canvas id="signatureCanvas" width="400" height="200" class="border-2 border-gray-300 rounded-lg cursor-crosshair bg-white"></canvas>
                        </div>
                        
                        <div class="flex flex-wrap gap-2 mb-4">
                            <button onclick="clearCanvas()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors text-sm">
                                <i class="fas fa-eraser mr-2"></i>Clear
                            </button>
                            <button onclick="undoLastStroke()" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition-colors text-sm">
                                <i class="fas fa-undo mr-2"></i>Undo
                            </button>
                            <button onclick="changeColor('black')" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm">
                                <i class="fas fa-pen mr-2"></i>Black
                            </button>
                            <button onclick="changeColor('#1e40af')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-pen mr-2"></i>Blue
                            </button>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <button onclick="saveSignature()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Save Signature
                            </button>
                            <button onclick="clearSignature()" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash mr-2"></i>Clear Signature
                            </button>
                        </div>
                    </div>

                    <!-- Current Signature Preview -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Current Signature</h2>
                        
                        <?php if ($current_signature): ?>
                            <div class="mb-4">
                                <h3 class="text-sm font-medium text-gray-600 mb-2">Signature Preview:</h3>
                                <div class="border-2 border-gray-200 rounded-lg p-4 bg-gray-50">
                                    <img src="<?php echo htmlspecialchars($current_signature); ?>" alt="Current Signature" class="max-w-full h-auto">
                                </div>
                            </div>
                            
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span class="text-green-700 text-sm">Signature is active and will be used on receipts</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-signature text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No signature saved yet</p>
                                <p class="text-sm text-gray-400 mt-1">Draw your signature on the left to get started</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        How to Use Your Digital Signature
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            <h4 class="font-medium text-gray-800 mb-2">Drawing Your Signature:</h4>
                            <ul class="space-y-1">
                                <li>• Use your mouse or touch screen to draw</li>
                                <li>• Choose from black or blue ink colors</li>
                                <li>• Use the Undo button to remove last stroke</li>
                                <li>• Use Clear to start over</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-2">Signature Usage:</h4>
                            <ul class="space-y-1">
                                <li>• Your signature will appear on all receipts</li>
                                <li>• It will be used as a watermark</li>
                                <li>• Only you can modify your signature</li>
                                <li>• Signature is stored securely</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Signature Canvas functionality
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let strokes = [];
        let currentStroke = [];
        let currentColor = 'black';
        let lineWidth = 2;

        // Set initial canvas style
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        // Touch events for mobile
        canvas.addEventListener('touchstart', handleTouch);
        canvas.addEventListener('touchmove', handleTouch);
        canvas.addEventListener('touchend', stopDrawing);

        function startDrawing(e) {
            isDrawing = true;
            currentStroke = [];
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            currentStroke.push({x, y, color: currentColor});
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            currentStroke.push({x, y, color: currentColor});
            ctx.lineTo(x, y);
            ctx.stroke();
        }

        function stopDrawing() {
            if (isDrawing) {
                isDrawing = false;
                strokes.push([...currentStroke]);
            }
        }

        function handleTouch(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                            e.type === 'touchmove' ? 'mousemove' : 'mouseup', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }

        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            strokes = [];
        }

        function undoLastStroke() {
            if (strokes.length > 0) {
                strokes.pop();
                redrawCanvas();
            }
        }

        function redrawCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            strokes.forEach(stroke => {
                if (stroke.length > 0) {
                    ctx.strokeStyle = stroke[0].color;
                    ctx.beginPath();
                    ctx.moveTo(stroke[0].x, stroke[0].y);
                    stroke.forEach(point => {
                        ctx.lineTo(point.x, point.y);
                    });
                    ctx.stroke();
                }
            });
        }

        function changeColor(color) {
            currentColor = color;
            ctx.strokeStyle = color;
        }

        function saveSignature() {
            if (strokes.length === 0) {
                alert('Please draw your signature before saving.');
                return;
            }

            const signatureData = canvas.toDataURL('image/png');
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="save_signature">
                <input type="hidden" name="signature_data" value="${signatureData}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function clearSignature() {
            if (confirm('Are you sure you want to clear your signature? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="clear_signature">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Auto-resize canvas for better mobile experience
        function resizeCanvas() {
            const container = canvas.parentElement;
            const containerWidth = container.clientWidth;
            const aspectRatio = 2; // width:height ratio
            
            canvas.width = Math.min(400, containerWidth - 32); // 32px for padding
            canvas.height = canvas.width / aspectRatio;
            
            // Redraw if there are strokes
            if (strokes.length > 0) {
                redrawCanvas();
            }
        }

        // Resize on load and window resize
        window.addEventListener('load', resizeCanvas);
        window.addEventListener('resize', resizeCanvas);
    </script>
</body>
</html>
