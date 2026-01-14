<?php
// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'includes/header.php';
include 'includes/db.php';
include 'includes/quote_helper.php';

$quote = null;
$error_message = '';
$success_message = '';

// Handle code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_code'])) {
    $tracking_code = trim($_POST['tracking_code']);
    
    if (empty($tracking_code)) {
        $error_message = "Please enter your tracking code.";
    } else {
        try {
            $quote = getQuoteByCode($pdo, $tracking_code);
            
            if (!$quote) {
                $error_message = "Invalid tracking code. Please check and try again.";
            }
        } catch (Exception $e) {
            $error_message = "Error tracking quote: " . $e->getMessage();
        }
    }
}

// Handle direct URL access with code parameter
if (!$quote && isset($_GET['code'])) {
    $tracking_code = trim($_GET['code']);
    
    try {
        $quote = getQuoteByCode($pdo, $tracking_code);
        
        if (!$quote) {
            $error_message = "Invalid tracking code. Please check and try again.";
        }
    } catch (Exception $e) {
        $error_message = "Error tracking quote: " . $e->getMessage();
    }
}
?>

<!-- Hero Section -->
<section class="relative py-16 md:py-24 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden page-hero-section">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight" style="font-family: 'Inter', sans-serif;">
            Track Your Quote
        </h1>
        <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed">
            Enter your unique tracking code to view your quote status
        </p>
    </div>
</section>

<!-- Quote Tracking Section -->
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (!$quote): ?>
            <!-- Tracking Form -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Quote <span class="text-[#536895]">Tracking</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Enter the unique tracking code you received when you submitted your quote request.
                </p>
            </div>

            <!-- Error Messages -->
            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Tracking Form -->
            <div class="max-w-md mx-auto">
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="tracking_code" class="block text-sm font-medium text-gray-700 mb-2">
                            Tracking Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="tracking_code" id="tracking_code" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-lg font-mono tracking-wider"
                               placeholder="Enter your 8-digit code" maxlength="8">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-[#536895] text-white py-3 px-6 rounded-lg hover:bg-[#2D3E50] transition-colors font-medium">
                        <i class="fas fa-search mr-2"></i>Track Quote
                    </button>
                </form>
                
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have a tracking code? 
                        <a href="quote_request.php" class="text-[#536895] hover:text-[#2D3E50] font-medium">
                            Submit a new quote request
                        </a>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Quote Details -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Quote <span class="text-[#536895]">Details</span>
                </h2>
                <p class="text-lg text-gray-600">
                    Tracking Code: <span class="font-mono font-bold text-[#536895]"><?php echo htmlspecialchars($quote['unique_code']); ?></span>
                </p>
            </div>

            <!-- Quote Information -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Project Information -->
                    <div>
                        <h3 class="font-medium text-gray-900 mb-3">Project Information</h3>
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
                        </div>
                    </div>
                    
                    <!-- Status Information -->
                    <div>
                        <h3 class="font-medium text-gray-900 mb-3">Status Information</h3>
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm font-medium text-gray-600">Status:</span>
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'reviewed' => 'bg-blue-100 text-blue-800',
                                    'quoted' => 'bg-green-100 text-green-800',
                                    'accepted' => 'bg-purple-100 text-purple-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'completed' => 'bg-gray-100 text-gray-800'
                                ];
                                $color = $status_colors[$quote['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $color; ?> ml-2">
                                    <?php echo ucfirst($quote['status']); ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-600">Submitted:</span>
                                <span class="text-sm text-gray-900 ml-2"><?php echo date('M j, Y g:i A', strtotime($quote['created_at'])); ?></span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-600">Last Updated:</span>
                                <span class="text-sm text-gray-900 ml-2"><?php echo date('M j, Y g:i A', strtotime($quote['updated_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Description - HIDDEN FROM GUEST VIEW -->
            <!-- <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="font-medium text-gray-900 mb-3">Project Description</h3>
                <div class="bg-white rounded-lg p-4 border">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($quote['description']); ?></p>
                </div>
            </div> -->

            <!-- Contact Buttons for Pending Quotes -->
            <?php if ($quote['status'] === 'pending'): ?>
            <div class="bg-yellow-50 rounded-lg p-6 mb-8">
                <h3 class="font-medium text-gray-900 mb-3">Follow Up</h3>
                <div class="space-y-4">
                    <p class="text-sm text-gray-700">
                        Your quote request is currently being reviewed. If you'd like to follow up or have any questions, feel free to contact us:
                    </p>
                    
                                         <!-- Contact Buttons -->
                     <div class="flex flex-col sm:flex-row gap-3">
                         <!-- Call Button -->
                         <a href="tel:+233257940791" 
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-medium">
                             <i class="fas fa-phone mr-2"></i>
                             Call Us
                         </a>
                         
                         <!-- WhatsApp Button -->
                         <a href="https://wa.me/233541069241?text=Hi! I submitted a quote request (Code: <?php echo htmlspecialchars($quote['unique_code']); ?>). I'd like to follow up on the status." 
                            target="_blank"
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                             <i class="fab fa-whatsapp mr-2"></i>
                             WhatsApp
                         </a>
                     </div>
                    
                    <!-- Contact Info -->
                    <div class="mt-3 text-xs text-gray-600 text-center">
                        <p>Business Hours: Mon-Fri 9AM-6PM | Sat 10AM-4PM</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quote Response -->
            <?php if ($quote['status'] !== 'pending' && ($quote['quote_amount'] || $quote['quote_message'])): ?>
            <div class="bg-green-50 rounded-lg p-6 mb-8">
                <h3 class="font-medium text-gray-900 mb-3">Quote Response</h3>
                <div class="space-y-4">
                    <?php if ($quote['quote_amount']): ?>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Quote Amount:</span>
                        <span class="text-lg font-bold text-green-600 ml-2">GHS <?php echo number_format($quote['quote_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($quote['quote_message']): ?>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Message:</span>
                        <div class="bg-white rounded-lg p-4 border mt-2">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($quote['quote_message']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                                         <!-- Contact Buttons -->
                     <div class="mt-6 pt-4 border-t border-green-200">
                         <h4 class="text-sm font-medium text-gray-700 mb-3">Ready to proceed? Contact us:</h4>
                         <div class="flex flex-col sm:flex-row gap-3">
                             <!-- Call Button -->
                             <a href="tel:+233257940791" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                                 <i class="fas fa-phone mr-2"></i>
                                 Call Us
                             </a>
                             
                             <!-- WhatsApp Button -->
                             <a href="https://wa.me/233541069241?text=Hi! I received a quote for my project (Code: <?php echo htmlspecialchars($quote['unique_code']); ?>). I'd like to discuss this further." 
                                target="_blank"
                                class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                                 <i class="fab fa-whatsapp mr-2"></i>
                                 WhatsApp
                             </a>
                         </div>
                        
                        <!-- Contact Info -->
                        <div class="mt-3 text-xs text-gray-600 text-center">
                            <p>Business Hours: Mon-Fri 9AM-6PM | Sat 10AM-4PM</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="text-center space-y-4">
                <a href="track_quote.php" 
                   class="inline-flex items-center px-6 py-3 bg-[#536895] text-white rounded-lg hover:bg-[#2D3E50] transition-colors">
                    <i class="fas fa-search mr-2"></i>Track Another Quote
                </a>
                
                <a href="quote_request.php" 
                   class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors ml-4">
                    <i class="fas fa-plus mr-2"></i>New Quote Request
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
