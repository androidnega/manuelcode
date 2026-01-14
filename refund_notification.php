<?php
session_start();
include 'includes/header.php';

$refund_id = $_GET['refund_id'] ?? '';
$amount = $_GET['amount'] ?? 0;
?>

<!-- Hero Section -->
<section class="relative py-12 md:py-20 bg-gradient-to-br from-[#536895] via-[#4a5a7a] to-[#2D3E50] overflow-hidden page-hero-section">
  <div class="absolute inset-0 bg-black bg-opacity-20"></div>
  <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <div class="mb-8">
      <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-full text-sm font-medium mb-6">
        <i class="fas fa-undo-alt mr-2"></i>
        Automatic Refund
      </div>
      <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
        Payment
        <span class="block text-[#F5A623]">Refunded</span>
      </h1>
      <p class="text-xl text-white/90 max-w-3xl mx-auto leading-relaxed">
        We detected a duplicate payment and have automatically refunded the amount to your account.
      </p>
    </div>
  </div>
  
  <!-- Floating Elements -->
  <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full animate-pulse"></div>
  <div class="absolute bottom-20 right-20 w-16 h-16 bg-[#F5A623]/20 rounded-full animate-bounce"></div>
  <div class="absolute top-1/2 left-1/4 w-12 h-12 bg-white/5 rounded-full animate-ping"></div>
</section>

<!-- Refund Details Section -->
<section class="py-16 bg-white">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-xl shadow-lg p-8">
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <i class="fas fa-check-circle text-4xl text-green-600"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Refund Processed Successfully</h2>
        <p class="text-lg text-gray-600">Your duplicate payment has been automatically refunded</p>
      </div>
      
      <div class="bg-gray-50 rounded-lg p-6 mb-8">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Refund Details</h3>
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm font-medium text-gray-600">Refund ID</p>
            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($refund_id); ?></p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600">Refund Amount</p>
            <p class="text-lg font-semibold text-green-600">GHS <?php echo number_format($amount, 2); ?></p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600">Refund Reason</p>
            <p class="text-lg text-gray-900">Duplicate Payment Detected</p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600">Processing Time</p>
            <p class="text-lg text-gray-900">3-5 Business Days</p>
          </div>
        </div>
      </div>
      
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-600 text-xl"></i>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-blue-900 mb-2">What Happened?</h3>
            <p class="text-blue-800">
              Our system detected that you made multiple payments for the same product within a short time period. 
              To protect you from accidental double charges, we automatically refunded the duplicate payment.
            </p>
          </div>
        </div>
      </div>
      
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <i class="fas fa-clock text-yellow-600 text-xl"></i>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-yellow-900 mb-2">Refund Timeline</h3>
            <ul class="text-yellow-800 space-y-2">
              <li class="flex items-center">
                <i class="fas fa-check-circle text-yellow-600 mr-2"></i>
                <span>Refund processed immediately</span>
              </li>
              <li class="flex items-center">
                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                <span>Funds will appear in your account within 3-5 business days</span>
              </li>
              <li class="flex items-center">
                <i class="fas fa-envelope text-yellow-600 mr-2"></i>
                <span>You'll receive an SMS confirmation shortly</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="store.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-[#536895] hover:bg-[#4a5a7a] transition-colors">
          <i class="fas fa-shopping-cart mr-2"></i>
          Continue Shopping
        </a>
        <a href="contact.php" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
          <i class="fas fa-headset mr-2"></i>
          Contact Support
        </a>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="py-16 bg-gray-50">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Frequently Asked Questions</h2>
    
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Why was my payment refunded?</h3>
        <p class="text-gray-600">
          Our system detected multiple payments for the same product within a short time period. 
          This usually happens when users accidentally click the payment button multiple times or experience network issues.
        </p>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">How long will the refund take?</h3>
        <p class="text-gray-600">
          The refund is processed immediately, but it may take 3-5 business days for the funds to appear in your account, 
          depending on your bank's processing time.
        </p>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Can I still access the product?</h3>
        <p class="text-gray-600">
          Yes! Your first successful payment is still valid, and you can access your purchased product through your dashboard.
        </p>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">What if I need the refund faster?</h3>
        <p class="text-gray-600">
          If you need urgent assistance with your refund, please contact our support team. 
          We'll be happy to help expedite the process if possible.
        </p>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
