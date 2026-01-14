<?php
session_start();
include '../includes/db.php';
include '../includes/signature_helper.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth/login.php');
    exit();
}

$receipt_data = null;
$error = '';

if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = (int)$_GET['id'];
    $type = $_GET['type'];
    
    try {
        if ($type === 'user') {
            $stmt = $pdo->prepare("
                SELECT p.*, u.name as user_name, u.email as user_email, pr.title as product_title, pr.price as product_price
                FROM purchases p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN products pr ON p.product_id = pr.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($receipt_data) {
                $receipt_data['type'] = 'user';
                $receipt_data['customer_name'] = $receipt_data['user_name'];
                $receipt_data['customer_email'] = $receipt_data['user_email'];
                $receipt_data['amount'] = $receipt_data['amount'] ?? $receipt_data['product_price'];
            }
        } else {
            $stmt = $pdo->prepare("
                SELECT go.*, pr.title as product_title, pr.price as product_price
                FROM guest_orders go
                LEFT JOIN products pr ON go.product_id = pr.id
                WHERE go.id = ?
            ");
            $stmt->execute([$id]);
            $receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($receipt_data) {
                $receipt_data['type'] = 'guest';
                $receipt_data['customer_name'] = $receipt_data['name'];
                $receipt_data['customer_email'] = $receipt_data['email'];
                $receipt_data['amount'] = $receipt_data['total_amount'];
            }
        }
        
        if (!$receipt_data) {
            $error = 'Receipt not found';
        } else {
            // Get admin signature for receipt
            $admin_signature = getReceiptSignature($pdo, $_SESSION['admin_id']);
        }
    } catch (Exception $e) {
        $error = 'Error loading receipt: ' . $e->getMessage();
    }
} else {
    $error = 'Invalid receipt parameters';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Receipt - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">View Receipt</h1>
                    <div class="flex space-x-2">
                        <a href="generate_receipts.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Receipts
                        </a>
                        <?php if ($receipt_data): ?>
                            <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-4xl mx-auto px-4 py-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($receipt_data): ?>
                <!-- Receipt -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 print:p-4">

                    <!-- Receipt Header -->
                    <div class="text-center mb-6">
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">ManuelCode</h1>
                        <p class="text-slate-600">Digital Product Receipt</p>
                        <p class="text-sm text-slate-500 mt-1"><?php echo date('F j, Y \a\t g:i A', strtotime($receipt_data['created_at'])); ?></p>
                    </div>

                    <!-- Receipt Number -->
                    <div class="bg-slate-50 p-4 rounded-lg mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-slate-600">Receipt Number:</span>
                            <span class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($receipt_data['receipt_number']); ?></span>
                        </div>
                    </div>

                    <!-- Customer and Order Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Customer Information -->
                        <div class="bg-slate-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3">Customer Information</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm font-medium text-slate-600">Name:</span>
                                    <span class="ml-2 text-slate-800"><?php echo htmlspecialchars($receipt_data['customer_name']); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-slate-600">Email:</span>
                                    <span class="ml-2 text-slate-800"><?php echo htmlspecialchars($receipt_data['customer_email']); ?></span>
                                </div>
                                <?php if ($receipt_data['type'] === 'guest' && $receipt_data['phone']): ?>
                                    <div>
                                        <span class="text-sm font-medium text-slate-600">Phone:</span>
                                        <span class="ml-2 text-slate-800"><?php echo htmlspecialchars($receipt_data['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <span class="text-sm font-medium text-slate-600">Customer Type:</span>
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"><?php echo ucfirst($receipt_data['type']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Information -->
                        <div class="bg-slate-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-slate-800 mb-3">Order Information</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm font-medium text-slate-600">Order ID:</span>
                                    <span class="ml-2 text-slate-800">#<?php echo $receipt_data['id']; ?></span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-slate-600">Order Date:</span>
                                    <span class="ml-2 text-slate-800"><?php echo date('F j, Y', strtotime($receipt_data['created_at'])); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-slate-600">Order Time:</span>
                                    <span class="ml-2 text-slate-800"><?php echo date('g:i A', strtotime($receipt_data['created_at'])); ?></span>
                                </div>
                                <?php if ($receipt_data['payment_ref']): ?>
                                    <div>
                                        <span class="text-sm font-medium text-slate-600">Payment Reference:</span>
                                        <span class="ml-2 text-slate-800"><?php echo htmlspecialchars($receipt_data['payment_ref']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-lg font-semibold text-slate-800 mb-3">Product Details</h3>
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-800"><?php echo htmlspecialchars($receipt_data['product_title']); ?></h4>
                                    <p class="text-sm text-slate-600 mt-1">Digital Product</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-slate-800">₵<?php echo number_format($receipt_data['amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="border-t border-slate-200 pt-6">
                        <div class="flex justify-between items-center bg-green-50 p-4 rounded-lg border border-green-200">
                            <span class="text-lg font-semibold text-slate-800">Total Amount:</span>
                            <span class="text-2xl font-bold text-green-600">₵<?php echo number_format($receipt_data['amount'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Digital Signature -->
                    <?php if ($admin_signature): ?>
                        <div class="border-t border-slate-200 pt-6 mt-6">
                            <div class="flex justify-between items-end mb-4">
                                <div class="flex-1">
                                    <div class="border-t-2 border-slate-300 w-32 mb-2"></div>
                                    <p class="text-sm text-slate-600">Authorized Signature</p>
                                </div>
                                <div class="text-right">
                                    <div class="border-t-2 border-slate-300 w-32 mb-2"></div>
                                    <p class="text-sm text-slate-600">Date: <?php echo date('F j, Y'); ?></p>
                                </div>
                            </div>
                            <div class="flex justify-center">
                                <div class="bg-white border border-slate-200 rounded-lg p-3">
                                    <img src="<?php echo htmlspecialchars($admin_signature); ?>" alt="Digital Signature" class="h-12 w-auto">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Footer -->
                    <div class="border-t border-slate-200 pt-6 mt-6">
                        <div class="text-center">
                            <p class="text-slate-700 font-medium mb-2">Thank you for your purchase!</p>
                            <p class="text-sm text-slate-600 mb-2">This is a digital product receipt. Please keep this for your records.</p>
                            <p class="text-sm text-slate-600">For support, contact: <span class="text-blue-600 font-medium">support@manuelcode.info</span></p>
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <div class="mt-8 p-6 bg-blue-50 rounded-xl border border-blue-200 print:hidden">
                        <h4 class="font-semibold text-blue-800 mb-3 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Admin Information
                        </h4>
                        <div class="text-sm text-blue-700 space-y-1">
                            <p><strong>Receipt Type:</strong> <?php echo ucfirst($receipt_data['type']); ?> Purchase</p>
                            <p><strong>Database ID:</strong> <?php echo $receipt_data['id']; ?></p>
                            <p><strong>Generated:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                            <?php if ($admin_signature): ?>
                                <p><strong>Digital Signature:</strong> Applied</p>
                            <?php else: ?>
                                <p><strong>Digital Signature:</strong> <a href="digital_signature.php" class="text-blue-600 underline">Set up signature</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; }
            .print\\:hidden { display: none !important; }
            .print\\:p-4 { padding: 1rem !important; }
            .print\\:mb-4 { margin-bottom: 1rem !important; }
            .print\\:pt-4 { padding-top: 1rem !important; }
        }
    </style>
</body>
</html>
