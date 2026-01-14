<?php
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/coupon_helper.php';

$message = '';
$error = '';

// Handle coupon actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $coupon_data = [
                    'code' => strtoupper(trim($_POST['code'])),
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'discount_type' => $_POST['discount_type'],
                    'discount_value' => $_POST['discount_value'],
                    'minimum_amount' => $_POST['minimum_amount'] ?: 0,
                    'maximum_discount' => $_POST['maximum_discount'] ?: null,
                    'usage_limit' => $_POST['usage_limit'] ?: null,
                    'user_limit' => $_POST['user_limit'] ?: 1,
                    'valid_from' => $_POST['valid_from'] ? date('Y-m-d H:i:s', strtotime($_POST['valid_from'])) : date('Y-m-d H:i:s'),
                    'valid_until' => $_POST['valid_until'] ? date('Y-m-d H:i:s', strtotime($_POST['valid_until'])) : null,
                    'applies_to' => $_POST['applies_to'] ?: 'all',
                    'created_by' => $_SESSION['admin_id']
                ];
                
                if ($couponManager->createCoupon($coupon_data)) {
                    $message = 'Coupon created successfully!';
                } else {
                    $error = 'Error creating coupon';
                }
                break;
                
            case 'update':
                $coupon_id = $_POST['coupon_id'];
                $coupon_data = [
                    'code' => strtoupper(trim($_POST['code'])),
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'discount_type' => $_POST['discount_type'],
                    'discount_value' => $_POST['discount_value'],
                    'minimum_amount' => $_POST['minimum_amount'] ?: 0,
                    'maximum_discount' => $_POST['maximum_discount'] ?: null,
                    'usage_limit' => $_POST['usage_limit'] ?: null,
                    'user_limit' => $_POST['user_limit'] ?: 1,
                    'valid_from' => $_POST['valid_from'] ? date('Y-m-d H:i:s', strtotime($_POST['valid_from'])) : date('Y-m-d H:i:s'),
                    'valid_until' => $_POST['valid_until'] ? date('Y-m-d H:i:s', strtotime($_POST['valid_until'])) : null,
                    'applies_to' => $_POST['applies_to'] ?: 'all',
                    'is_active' => isset($_POST['is_active'])
                ];
                
                if ($couponManager->updateCoupon($coupon_id, $coupon_data)) {
                    $message = 'Coupon updated successfully!';
                } else {
                    $error = 'Error updating coupon';
                }
                break;
                
            case 'delete':
                $coupon_id = $_POST['coupon_id'];
                if ($couponManager->deleteCoupon($coupon_id)) {
                    $message = 'Coupon deleted successfully!';
                } else {
                    $error = 'Error deleting coupon';
                }
                break;
        }
    }
}

// Get all coupons
$coupons = $couponManager->getAllCoupons();

// Get all products for selection
$stmt = $pdo->query("SELECT id, title, price FROM products WHERE status = 'active' ORDER BY title");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html lang="en">
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/session-timeout.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    <?php include 'includes/standard_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-0 min-h-screen">
        <!-- Mobile Header for toggling sidebar -->
        <header class="lg:hidden bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="flex items-center justify-between p-4">
                <button onclick="toggleSidebar()" class="text-slate-600 hover:text-slate-900 p-2 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-lg font-semibold text-gray-800">Coupon Management</h1>
                <div class="w-8"></div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 lg:p-6 overflow-x-hidden">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-slate-700 to-blue-700 bg-clip-text text-transparent">Coupon Management</h1>
                <p class="text-slate-600 mt-2">Create and manage discount coupons for your store</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($message): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-check-circle mt-1 mr-3"></i>
                        <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
                        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Create New Coupon Button -->
            <div class="mb-6">
                <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>Create New Coupon
                </button>
            </div>

            <!-- Coupons Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        <i class="fas fa-tag text-2xl mb-2"></i>
                                        <p>No coupons found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-mono text-sm font-medium text-gray-900"><?php echo htmlspecialchars($coupon['code']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($coupon['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($coupon['description']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    <?php echo $coupon['discount_value']; ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    ₵<?php echo number_format($coupon['discount_value'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($coupon['usage_limit']): ?>
                                                <?php echo $coupon['used_count']; ?>/<?php echo $coupon['usage_limit']; ?>
                                            <?php else: ?>
                                                <?php echo $coupon['used_count']; ?>/∞
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                                                                         <?php
                                             $now = time();
                                             $from_time = strtotime($coupon['valid_from']);
                                             $until_time = $coupon['valid_until'] ? strtotime($coupon['valid_until']) : null;
                                             
                                             if (!$coupon['is_active']) {
                                                 $status = 'Inactive';
                                                 $status_class = 'bg-gray-100 text-gray-800';
                                             } elseif ($until_time && $until_time < $now) {
                                                 $status = 'Expired';
                                                 $status_class = 'bg-red-100 text-red-800';
                                             } elseif ($from_time > $now) {
                                                 $status = 'Not yet valid';
                                                 $status_class = 'bg-yellow-100 text-yellow-800';
                                             } else {
                                                 $status = 'Active';
                                                 $status_class = 'bg-green-100 text-green-800';
                                             }
                                             ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $coupon['valid_until'] ? date('M j, Y', strtotime($coupon['valid_until'])) : 'No expiry'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteCoupon(<?php echo $coupon['id']; ?>)" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create/Edit Coupon Modal -->
    <div id="couponModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Create New Coupon</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="couponForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="coupon_id" id="couponId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                 <div>
                             <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code *</label>
                             <div class="flex gap-2">
                                 <input type="text" name="code" id="couponCode" required
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                 <button type="button" onclick="generateCouponCode()" 
                                         class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors text-sm">
                                     <i class="fas fa-sync-alt mr-1"></i>Generate
                                 </button>
                             </div>
                             <p class="text-xs text-gray-500 mt-1">Enter a code or click "Generate" for a new code.</p>
                         </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input type="text" name="name" id="couponName" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" id="couponDescription" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type *</label>
                            <select name="discount_type" id="discountType" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value *</label>
                            <input type="number" name="discount_value" id="discountValue" step="0.01" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Amount</label>
                            <input type="number" name="minimum_amount" id="minimumAmount" step="0.01" min="0" value="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Discount</label>
                            <input type="number" name="maximum_discount" id="maximumDiscount" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Usage Limit</label>
                            <input type="number" name="usage_limit" id="usageLimit" min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User Limit</label>
                            <input type="number" name="user_limit" id="userLimit" min="1" value="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                                                 <div>
                             <label class="block text-sm font-medium text-gray-700 mb-1">Valid From</label>
                             <input type="text" name="valid_from" id="validFrom" placeholder="YYYY-MM-DD HH:MM"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                             <p class="text-xs text-gray-500 mt-1">Format: 2025-08-24 19:30</p>
                         </div>
                         
                         <div>
                             <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                             <input type="text" name="valid_until" id="validUntil" placeholder="YYYY-MM-DD HH:MM (optional)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                             <p class="text-xs text-gray-500 mt-1">Format: 2025-09-24 19:30 (leave empty for no expiry)</p>
                         </div>
                        
                                                 <div class="md:col-span-2">
                             <label class="block text-sm font-medium text-gray-700 mb-1">Applies To</label>
                             <select name="applies_to" id="appliesTo" onchange="toggleProductSelection()"
                                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                 <option value="all">All Products</option>
                                 <option value="specific_products">Specific Products</option>
                                 <option value="specific_categories">Specific Categories</option>
                             </select>
                         </div>
                         
                         <div class="md:col-span-2" id="productSelection" style="display: none;">
                             <label class="block text-sm font-medium text-gray-700 mb-2">Select Products</label>
                             <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-md p-3 bg-gray-50">
                                 <?php foreach ($products as $product): ?>
                                     <label class="flex items-center mb-2">
                                         <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                         <span class="ml-2 text-sm text-gray-700">
                                             <?php echo htmlspecialchars($product['title']); ?> - ₵<?php echo number_format($product['price'], 2); ?>
                                         </span>
                                     </label>
                                 <?php endforeach; ?>
                             </div>
                             <p class="text-xs text-gray-500 mt-1">Select the products this coupon applies to</p>
                         </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" id="isActive" checked
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <span id="submitText">Create Coupon</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
                 function openCreateModal() {
             document.getElementById('modalTitle').textContent = 'Create New Coupon';
             document.getElementById('formAction').value = 'create';
             document.getElementById('couponForm').reset();
             document.getElementById('couponId').value = '';
             document.getElementById('submitText').textContent = 'Create Coupon';
             // Auto-generate coupon code
             generateCouponCode();
             // Set default valid from date to now
             const now = new Date();
             const year = now.getFullYear();
             const month = String(now.getMonth() + 1).padStart(2, '0');
             const day = String(now.getDate()).padStart(2, '0');
             const hours = String(now.getHours()).padStart(2, '0');
             const minutes = String(now.getMinutes()).padStart(2, '0');
             document.getElementById('validFrom').value = `${year}-${month}-${day} ${hours}:${minutes}`;
             document.getElementById('couponModal').classList.remove('hidden');
         }
         
         function generateCouponCode() {
             const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
             let code = '';
             for (let i = 0; i < 8; i++) {
                 code += chars.charAt(Math.floor(Math.random() * chars.length));
             }
             document.getElementById('couponCode').value = code;
         }
        
                                   function editCoupon(coupon) {
              document.getElementById('modalTitle').textContent = 'Edit Coupon';
              document.getElementById('formAction').value = 'update';
              document.getElementById('couponId').value = coupon.id;
              document.getElementById('couponCode').value = coupon.code;
              document.getElementById('couponName').value = coupon.name;
              document.getElementById('couponDescription').value = coupon.description;
              document.getElementById('discountType').value = coupon.discount_type;
              document.getElementById('discountValue').value = coupon.discount_value;
              document.getElementById('minimumAmount').value = coupon.minimum_amount;
              document.getElementById('maximumDiscount').value = coupon.maximum_discount || '';
              document.getElementById('usageLimit').value = coupon.usage_limit || '';
              document.getElementById('userLimit').value = coupon.user_limit;
              
              // Format datetime values for text input (YYYY-MM-DD HH:MM format)
              if (coupon.valid_from) {
                   const fromDate = new Date(coupon.valid_from);
                   const year = fromDate.getFullYear();
                   const month = String(fromDate.getMonth() + 1).padStart(2, '0');
                   const day = String(fromDate.getDate()).padStart(2, '0');
                   const hours = String(fromDate.getHours()).padStart(2, '0');
                   const minutes = String(fromDate.getMinutes()).padStart(2, '0');
                   const fromFormatted = `${year}-${month}-${day} ${hours}:${minutes}`;
                   document.getElementById('validFrom').value = fromFormatted;
               } else {
                   document.getElementById('validFrom').value = '';
               }
               
               if (coupon.valid_until) {
                   const untilDate = new Date(coupon.valid_until);
                   const year = untilDate.getFullYear();
                   const month = String(untilDate.getMonth() + 1).padStart(2, '0');
                   const day = String(untilDate.getDate()).padStart(2, '0');
                   const hours = String(untilDate.getHours()).padStart(2, '0');
                   const minutes = String(untilDate.getMinutes()).padStart(2, '0');
                   const untilFormatted = `${year}-${month}-${day} ${hours}:${minutes}`;
                   document.getElementById('validUntil').value = untilFormatted;
               } else {
                   document.getElementById('validUntil').value = '';
               }
              
              document.getElementById('appliesTo').value = coupon.applies_to;
              document.getElementById('isActive').checked = coupon.is_active == 1;
              document.getElementById('submitText').textContent = 'Update Coupon';
              document.getElementById('couponModal').classList.remove('hidden');
          }
        
        function closeModal() {
            document.getElementById('couponModal').classList.add('hidden');
        }
        
        function deleteCoupon(couponId) {
            if (confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="coupon_id" value="${couponId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
                 function toggleProductSelection() {
             const appliesTo = document.getElementById('appliesTo').value;
             const productSelection = document.getElementById('productSelection');
             
             if (appliesTo === 'specific_products') {
                 productSelection.style.display = 'block';
             } else {
                 productSelection.style.display = 'none';
                 // Uncheck all product checkboxes
                 const checkboxes = productSelection.querySelectorAll('input[type="checkbox"]');
                 checkboxes.forEach(checkbox => checkbox.checked = false);
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
    </script>
</body>
</html>
