<?php 
include '../includes/db.php';
include '../includes/meta_helper.php';

setQuickMeta(
    'How to Request a Refund - ManuelCode Help Guide',
    'Learn how to request a refund for your purchase. Step-by-step guide on the refund process and policies.',
    'assets/favi/favicon.png',
    'refund guide, request refund, refund policy, return policy, ManuelCode help'
);

include '../includes/header.php';
?>

<div class="flex flex-col min-h-screen">
  <section class="relative py-12 sm:py-16 md:py-20 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 mb-4 sm:mb-6">
        <i class="fas fa-undo-alt text-white text-2xl sm:text-3xl"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 sm:mb-4 leading-tight">
        How to Request a Refund
      </h1>
      <p class="text-base sm:text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed px-2">
        Complete guide on requesting refunds for your purchases
      </p>
    </div>
  </section>

  <section class="py-12 sm:py-16 bg-white flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="bg-pink-50 rounded-xl border border-pink-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-pink-900 mb-6 sm:mb-8 text-center">Refund Process</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
          <div class="text-center p-6 sm:p-8 bg-white rounded-xl border border-pink-100">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold text-xl sm:text-2xl mx-auto mb-4 sm:mb-6">1</div>
            <h3 class="font-semibold text-pink-900 text-lg sm:text-xl mb-2 sm:mb-3">Go to Refunds</h3>
            <p class="text-pink-700 text-sm sm:text-base">Navigate to your dashboard refunds section</p>
          </div>
          <div class="text-center p-6 sm:p-8 bg-white rounded-xl border border-green-100">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-xl sm:text-2xl mx-auto mb-4 sm:mb-6">2</div>
            <h3 class="font-semibold text-green-900 text-lg sm:text-xl mb-2 sm:mb-3">Select Product</h3>
            <p class="text-green-700 text-sm sm:text-base">Choose the product you want to refund</p>
          </div>
          <div class="text-center p-6 sm:p-8 bg-white rounded-xl border border-blue-100 sm:col-span-2 lg:col-span-1">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl sm:text-2xl mx-auto mb-4 sm:mb-6">3</div>
            <h3 class="font-semibold text-blue-900 text-lg sm:text-xl mb-2 sm:mb-3">Submit Request</h3>
            <p class="text-blue-700 text-sm sm:text-base">Fill out the refund form and submit</p>
          </div>
        </div>
      </div>

      <div class="bg-green-50 rounded-xl border border-green-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-green-900 mb-6 sm:mb-8 text-center">Refund Policy</h2>
        <div class="space-y-4 sm:space-y-6">
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-clock text-green-600 mr-2"></i>
              Refund Timeframe
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Refunds are typically processed within 5-7 business days after approval.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Refund requests must be submitted within 30 days of purchase</li>
              <li>Processing time: 3-5 business days for review</li>
              <li>Refund time: 5-7 business days after approval</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-check-circle text-blue-600 mr-2"></i>
              Eligible for Refund
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Products that qualify for refunds:</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Duplicate purchases</li>
              <li>Technical issues preventing product access</li>
              <li>Product not as described</li>
              <li>Unauthorized transactions</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-times-circle text-red-600 mr-2"></i>
              Not Eligible for Refund
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Products that do not qualify:</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Products downloaded more than 3 times</li>
              <li>Refund requests after 30 days</li>
              <li>Change of mind after successful download</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="bg-cyan-50 rounded-xl border border-cyan-200 p-6 sm:p-8 lg:p-10 text-center">
        <i class="fas fa-headset text-4xl sm:text-5xl lg:text-6xl mb-6 sm:mb-8 text-cyan-600"></i>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6 text-cyan-900">Need Help?</h2>
        <p class="mb-6 sm:mb-8 text-cyan-800 text-base sm:text-lg lg:text-xl">Contact our support team for assistance with refund requests.</p>
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center">
          <a href="/contact" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-envelope mr-2"></i>Contact Support
          </a>
          <a href="/dashboard/refunds" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-undo-alt mr-2"></i>Request Refund
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
