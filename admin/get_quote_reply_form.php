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
    <!-- Quote Information Summary -->
    <div class="bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Quote Request Summary</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-blue-700">Customer:</span>
                <span class="text-sm text-blue-900 ml-2"><?php echo htmlspecialchars($quote['contact_name']); ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-blue-700">Project:</span>
                <span class="text-sm text-blue-900 ml-2"><?php echo htmlspecialchars($quote['project_title']); ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-blue-700">Service:</span>
                <span class="text-sm text-blue-900 ml-2"><?php echo ucfirst(str_replace('-', ' ', $quote['service_type'])); ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-blue-700">Status:</span>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 ml-2">
                    <?php echo ucfirst($quote['status']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Quote Response Form -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="font-medium text-gray-900 mb-4">Provide Quote Response</h4>
        
        <!-- Admin Hint -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                </div>
                <div class="ml-3">
                    <h5 class="text-sm font-medium text-blue-800">Admin Hint</h5>
                    <p class="text-sm text-blue-700 mt-1">
                        When you submit this quote response, the customer will receive an SMS notification with the tracking link. 
                        They will be able to view the quote amount and your response message on the tracking page.
                        <strong>Note:</strong> If you provide a quote amount, the status will automatically be set to "Quoted".
                    </p>
                </div>
            </div>
        </div>
        <form id="quoteResponseForm" class="space-y-4">
            <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
            <input type="hidden" name="action" value="update_status">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quote Amount (GHS)</label>
                <input type="number" name="quoted_amount" step="0.01" min="0" 
                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter quote amount" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quote Message</label>
                <textarea name="admin_notes" rows="6" 
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Provide detailed quote, terms, timeline, and deliverables..." required></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    <option value="">Select status...</option>
                    <option value="quoted">✅ Quoted - Provide quote amount and details</option>
                    <option value="declined">❌ Declined - Project not suitable</option>
                    <option value="pending">⏳ Keep Pending - Need more information</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Submit Quote Response
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quoteResponseForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validate form data
            const quoteId = formData.get('quote_id');
            let status = formData.get('status');
            const adminNotes = formData.get('admin_notes');
            const quotedAmount = formData.get('quoted_amount');
            
            // Auto-set status to "quoted" if quote amount is provided and status is not explicitly set
            if (quotedAmount && quotedAmount > 0 && (!status || status === '')) {
                status = 'quoted';
                formData.set('status', 'quoted');
            }
            
            if (!quoteId || !status || !adminNotes || !quotedAmount) {
                alert('Please fill in all required fields: Quote ID, Status, Quote Message, and Quote Amount.');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;
            
            console.log('Submitting form data:', Object.fromEntries(formData));
            
            fetch('quotes_enhanced.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                console.log('Response received:', html.substring(0, 500));
                
                // Check if the response contains success message
                if (html.includes('Quote status updated successfully') || html.includes('successfully')) {
                    // Show success message with better UI
                    const successDiv = document.createElement('div');
                    successDiv.className = 'fixed top-4 right-4 bg-green-50 border border-green-200 rounded-lg p-4 z-50';
                    successDiv.innerHTML = `
                        <div class="flex items-center text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Quote response submitted successfully! SMS notification sent to customer.</span>
                        </div>
                    `;
                    document.body.appendChild(successDiv);
                    
                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        if (successDiv.parentNode) {
                            successDiv.parentNode.removeChild(successDiv);
                        }
                    }, 3000);
                    
                                         // Close modal and reload the page to show updated data
                     setTimeout(() => {
                         closeModal();
                         location.reload(true); // Force reload from server
                     }, 1000);
                } else {
                    // Show error if response doesn't contain success message
                    throw new Error('Response does not indicate success. Please check the server logs.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error message with better UI
                const errorDiv = document.createElement('div');
                errorDiv.className = 'fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 z-50';
                errorDiv.innerHTML = `
                    <div class="flex items-center text-red-800">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>Error submitting quote response: ${error.message}</span>
                    </div>
                `;
                document.body.appendChild(errorDiv);
                
                // Remove error message after 5 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.parentNode.removeChild(errorDiv);
                    }
                }, 5000);
                
                // Reset button
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>
