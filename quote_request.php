<?php 
include 'includes/header.php'; 
include 'includes/user_activity_tracker.php';
include 'includes/db.php';
include 'includes/util.php';
include 'includes/quote_helper.php';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = $_POST['service_type'] ?? '';
    $project_title = $_POST['project_title'] ?? '';
    $description = $_POST['description'] ?? '';
    $budget_range = $_POST['budget_range'] ?? '';
    $timeline = $_POST['timeline'] ?? '';
    $contact_name = $_POST['contact_name'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $additional_requirements = $_POST['additional_requirements'] ?? '';
    
    // Validate required fields
    if (empty($service_type) || empty($project_title) || empty($description) || 
        empty($contact_name) || empty($contact_email)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Prepare quote data
            $quote_data = [
                'service_type' => $service_type,
                'project_title' => $project_title,
                'description' => $description,
                'budget_range' => $budget_range,
                'timeline' => $timeline,
                'contact_name' => $contact_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'additional_requirements' => $additional_requirements,
                'user_id' => $_SESSION['user_id'] ?? null,
                'priority' => 'medium',
                'source' => 'website'
            ];
            
            // Create quote request using helper function
            $result = createQuoteRequest($pdo, $quote_data);
            
            if ($result['success']) {
                $unique_code = $result['unique_code'];
                $quote_id = $result['quote_id'];
                
                // Send SMS notification
                if (!empty($contact_phone)) {
                    $site_url = "https://manuelcode.info";
                    $tracking_url = $site_url . "/track_quote.php?code=" . $unique_code;
                    
                    // Get SMS template from database or use default
                    $sms_template = get_config('sms_quote_submitted', 'Your quote request has been submitted successfully! Your unique tracking code is: {unique_code}. Track your quote status at: {tracking_url}');
                    
                    $sms_message = str_replace(
                        ['{unique_code}', '{tracking_url}'],
                        [$unique_code, $tracking_url],
                        $sms_template
                    );
                    
                    // Send SMS
                    $sms_result = send_sms($contact_phone, $sms_message);
                    
                    // Log SMS attempt
                    if (function_exists('log_sms_activity')) {
                        log_sms_activity($contact_phone, $sms_message, $sms_result);
                    }
                }
                
                $success_message = "Your quote request has been submitted successfully! Your unique tracking code is: {$unique_code}. Please save this code to track your quote status. We'll get back to you within 24 hours.";
                
                // Clear form data
                $service_type = $project_title = $description = $budget_range = $timeline = '';
                $contact_name = $contact_email = $contact_phone = $additional_requirements = '';
            } else {
                $error_message = "Error submitting request: " . $result['error'];
            }
            
        } catch (Exception $e) {
            $error_message = "Error submitting request: " . $e->getMessage();
        }
    }
}
?>

<!-- Hero Section -->
<section class="relative py-16 md:py-24 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden page-hero-section">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight" style="font-family: 'Inter', sans-serif;">
            Get a Quote
        </h1>
        <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed">
            Tell us about your project for a custom quote
        </p>
    </div>
</section>

<!-- Quote Request Form -->
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Project <span class="text-[#536895]">Details</span>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Fill out the form below with your project requirements and we'll get back to you with a custom quote.
            </p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <!-- Service Type -->
            <div>
                <label for="service_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Service Type <span class="text-red-500">*</span>
                </label>
                <select name="service_type" id="service_type" required 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select a service...</option>
                    <option value="web-development" <?php echo ($service_type ?? '') === 'web-development' ? 'selected' : ''; ?>>Web Development</option>
                    <option value="mobile-apps" <?php echo ($service_type ?? '') === 'mobile-apps' ? 'selected' : ''; ?>>Mobile Apps</option>
                    <option value="api-development" <?php echo ($service_type ?? '') === 'api-development' ? 'selected' : ''; ?>>API Development</option>
                    <option value="database-design" <?php echo ($service_type ?? '') === 'database-design' ? 'selected' : ''; ?>>Database Design</option>
                    <option value="cloud-solutions" <?php echo ($service_type ?? '') === 'cloud-solutions' ? 'selected' : ''; ?>>Cloud Solutions</option>
                    <option value="digital-products" <?php echo ($service_type ?? '') === 'digital-products' ? 'selected' : ''; ?>>Digital Products</option>
                    <option value="custom-software" <?php echo ($service_type ?? '') === 'custom-software' ? 'selected' : ''; ?>>Custom Software</option>
                </select>
            </div>

            <!-- Project Title -->
            <div>
                <label for="project_title" class="block text-sm font-medium text-gray-700 mb-2">
                    Project Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="project_title" id="project_title" required
                       value="<?php echo htmlspecialchars($project_title ?? ''); ?>"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter your project title">
            </div>

            <!-- Project Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Project Description <span class="text-red-500">*</span>
                </label>
                <textarea name="description" id="description" rows="6" required
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Describe your project requirements, goals, and any specific features you need..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <!-- Budget and Timeline -->
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="budget_range" class="block text-sm font-medium text-gray-700 mb-2">
                        Budget Range
                    </label>
                    <select name="budget_range" id="budget_range"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select budget range...</option>
                        <option value="under-1000" <?php echo ($budget_range ?? '') === 'under-1000' ? 'selected' : ''; ?>>Under GHS 1,000</option>
                        <option value="1000-5000" <?php echo ($budget_range ?? '') === '1000-5000' ? 'selected' : ''; ?>>GHS 1,000 - GHS 5,000</option>
                        <option value="5000-10000" <?php echo ($budget_range ?? '') === '5000-10000' ? 'selected' : ''; ?>>GHS 5,000 - GHS 10,000</option>
                        <option value="10000-25000" <?php echo ($budget_range ?? '') === '10000-25000' ? 'selected' : ''; ?>>GHS 10,000 - GHS 25,000</option>
                        <option value="25000-50000" <?php echo ($budget_range ?? '') === '25000-50000' ? 'selected' : ''; ?>>GHS 25,000 - GHS 50,000</option>
                        <option value="over-50000" <?php echo ($budget_range ?? '') === 'over-50000' ? 'selected' : ''; ?>>Over GHS 50,000</option>
                    </select>
                </div>

                <div>
                    <label for="timeline" class="block text-sm font-medium text-gray-700 mb-2">
                        Project Timeline
                    </label>
                    <select name="timeline" id="timeline"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select timeline...</option>
                        <option value="1-2-weeks" <?php echo ($timeline ?? '') === '1-2-weeks' ? 'selected' : ''; ?>>1-2 weeks</option>
                        <option value="1-month" <?php echo ($timeline ?? '') === '1-month' ? 'selected' : ''; ?>>1 month</option>
                        <option value="2-3-months" <?php echo ($timeline ?? '') === '2-3-months' ? 'selected' : ''; ?>>2-3 months</option>
                        <option value="3-6-months" <?php echo ($timeline ?? '') === '3-6-months' ? 'selected' : ''; ?>>3-6 months</option>
                        <option value="6-months-plus" <?php echo ($timeline ?? '') === '6-months-plus' ? 'selected' : ''; ?>>6+ months</option>
                        <option value="flexible" <?php echo ($timeline ?? '') === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                    </select>
                </div>
            </div>

            <!-- Additional Requirements -->
            <div>
                <label for="additional_requirements" class="block text-sm font-medium text-gray-700 mb-2">
                    Additional Requirements
                </label>
                <textarea name="additional_requirements" id="additional_requirements" rows="4"
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Any additional requirements, preferences, or questions..."><?php echo htmlspecialchars($additional_requirements ?? ''); ?></textarea>
            </div>

            <!-- Contact Information -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="contact_name" id="contact_name" required
                               value="<?php echo htmlspecialchars($contact_name ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Your full name">
                    </div>

                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="contact_email" id="contact_email" required
                               value="<?php echo htmlspecialchars($contact_email ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="your.email@example.com">
                    </div>
                </div>

                <div class="mt-6">
                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input type="tel" name="contact_phone" id="contact_phone"
                           value="<?php echo htmlspecialchars($contact_phone ?? ''); ?>"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="+1 (555) 123-4567">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" 
                        class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-8 py-4 rounded-lg font-semibold text-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Quote Request
                </button>
            </div>
        </form>

        <!-- Additional Information -->
        <div class="mt-12 bg-blue-50 p-6 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                What happens next?
            </h3>
            <div class="grid md:grid-cols-3 gap-4 text-sm text-blue-800">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                        <span class="text-blue-600 font-bold text-xs">1</span>
                    </div>
                    <div>
                        <strong>Review</strong><br>
                        We'll review your project requirements and create a detailed proposal.
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                        <span class="text-blue-600 font-bold text-xs">2</span>
                    </div>
                    <div>
                        <strong>Quote</strong><br>
                        You'll receive a comprehensive quote with timeline and deliverables.
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                        <span class="text-blue-600 font-bold text-xs">3</span>
                    </div>
                    <div>
                        <strong>Start</strong><br>
                        Once approved, we'll begin development and keep you updated throughout.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
