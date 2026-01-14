<?php
// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // If not AJAX, check authentication
    include 'auth/check_auth.php';
}
include '../includes/db.php';

if (!isset($_GET['id'])) {
    echo '<div class="text-red-600">Quote ID not provided</div>';
    exit;
}

$quote_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT qr.*, u.name as user_name, u.email as user_email
        FROM quote_requests qr
        LEFT JOIN users u ON qr.user_id = u.id
        WHERE qr.id = ?
    ");
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quote) {
        echo '<div class="text-red-600">Quote not found</div>';
        exit;
    }
} catch (Exception $e) {
    echo '<div class="text-red-600">Error loading quote: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="space-y-6">
    <!-- Quote Information -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quote Request Details</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Customer Information -->
            <div>
                <h4 class="font-medium text-gray-900 mb-3">Customer Information</h4>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Name:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo htmlspecialchars($quote['contact_name']); ?></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Email:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo htmlspecialchars($quote['contact_email']); ?></span>
                    </div>
                    <?php if ($quote['contact_phone']): ?>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Phone:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo htmlspecialchars($quote['contact_phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($quote['user_id']): ?>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Registered User:</span>
                        <span class="text-sm text-gray-900 ml-2">Yes (ID: <?php echo $quote['user_id']; ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Project Information -->
            <div>
                <h4 class="font-medium text-gray-900 mb-3">Project Information</h4>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Service Type:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo ucfirst(str_replace('-', ' ', $quote['service_type'])); ?></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Project Title:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo htmlspecialchars($quote['project_title']); ?></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Budget Range:</span>
                        <span class="text-sm text-gray-900 ml-2">
                            <?php 
                            $budget_display = str_replace('-', ' ', $quote['budget_range']);
                            $budget_display = str_replace('under 1000', 'Under GHS 1,000', $budget_display);
                            $budget_display = str_replace('1000 5000', 'GHS 1,000 - GHS 5,000', $budget_display);
                            $budget_display = str_replace('5000 10000', 'GHS 5,000 - GHS 10,000', $budget_display);
                            $budget_display = str_replace('10000 25000', 'GHS 10,000 - GHS 25,000', $budget_display);
                            $budget_display = str_replace('25000 50000', 'GHS 25,000 - GHS 50,000', $budget_display);
                            $budget_display = str_replace('over 50000', 'Over GHS 50,000', $budget_display);
                            echo $budget_display;
                            ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Timeline:</span>
                        <span class="text-sm text-gray-900 ml-2"><?php echo ucfirst(str_replace('-', ' ', $quote['timeline'])); ?></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Status:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 ml-2">
                            <?php echo ucfirst($quote['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Project Description -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="font-medium text-gray-900 mb-3">Project Description</h4>
        <div class="bg-white rounded-lg p-4 border">
            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($quote['description']); ?></p>
        </div>
    </div>
    
    <!-- Additional Requirements -->
    <?php if ($quote['additional_requirements']): ?>
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="font-medium text-gray-900 mb-3">Additional Requirements</h4>
        <div class="bg-white rounded-lg p-4 border">
            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($quote['additional_requirements']); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quote Details (if already quoted) -->
    <?php if ($quote['status'] !== 'pending' && ($quote['quote_amount'] || $quote['quote_message'])): ?>
    <div class="bg-green-50 rounded-lg p-6">
        <h4 class="font-medium text-gray-900 mb-3">Quote Response</h4>
        <div class="space-y-2">
            <?php if ($quote['quote_amount']): ?>
            <div>
                <span class="text-sm font-medium text-gray-600">Quote Amount:</span>
                <span class="text-sm text-gray-900 ml-2">GHS <?php echo number_format($quote['quote_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($quote['quote_message']): ?>
            <div>
                <span class="text-sm font-medium text-gray-600">Quote Message:</span>
                <div class="bg-white rounded-lg p-4 border mt-2">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($quote['quote_message']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Timestamps -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="font-medium text-gray-900 mb-3">Timestamps</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-600">Created:</span>
                <span class="text-sm text-gray-900 ml-2"><?php echo date('M j, Y g:i A', strtotime($quote['created_at'])); ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600">Last Updated:</span>
                <span class="text-sm text-gray-900 ml-2"><?php echo date('M j, Y g:i A', strtotime($quote['updated_at'])); ?></span>
            </div>
        </div>
    </div>
</div>


