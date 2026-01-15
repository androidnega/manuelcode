<?php 
include '../includes/db.php';
include '../includes/meta_helper.php';

setQuickMeta(
    'Payment Issues and Solutions - ManuelCode Help Guide',
    'Troubleshooting guide for payment problems. Common payment issues and their solutions.',
    'assets/favi/favicon.png',
    'payment issues, payment problems, payment help, troubleshooting payment, ManuelCode help'
);

include '../includes/header.php';
?>

<div class="flex flex-col min-h-screen">
  <section class="relative py-12 sm:py-16 md:py-20 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 mb-4 sm:mb-6">
        <i class="fas fa-credit-card text-white text-2xl sm:text-3xl"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 sm:mb-4 leading-tight">
        Payment Issues and Solutions
      </h1>
      <p class="text-base sm:text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed px-2">
        Troubleshooting guide for common payment problems
      </p>
    </div>
  </section>

  <section class="py-12 sm:py-16 bg-white flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="bg-blue-50 rounded-xl border border-blue-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-900 mb-6 sm:mb-8 text-center">Common Payment Issues</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
          <div class="bg-white rounded-xl p-6 sm:p-8 border border-red-200">
            <h3 class="font-semibold text-red-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-exclamation-triangle text-red-600 mr-2 text-xl sm:text-2xl"></i>
              Payment Declined
            </h3>
            <p class="text-red-800 mb-3 sm:mb-4 text-sm sm:text-base">If your payment is declined:</p>
            <ul class="list-disc list-inside text-red-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Check your card balance</li>
              <li>Verify card details are correct</li>
              <li>Contact your bank</li>
              <li>Try a different payment method</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-6 sm:p-8 border border-yellow-200">
            <h3 class="font-semibold text-yellow-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-clock text-yellow-600 mr-2 text-xl sm:text-2xl"></i>
              Payment Pending
            </h3>
            <p class="text-yellow-800 mb-3 sm:mb-4 text-sm sm:text-base">If payment shows as pending:</p>
            <ul class="list-disc list-inside text-yellow-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Wait 5-10 minutes</li>
              <li>Check your email for confirmation</li>
              <li>Refresh the page</li>
              <li>Contact support if it persists</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-6 sm:p-8 border border-blue-200 sm:col-span-2 lg:col-span-1">
            <h3 class="font-semibold text-blue-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-network-wired text-blue-600 mr-2 text-xl sm:text-2xl"></i>
              Network Error
            </h3>
            <p class="text-blue-800 mb-3 sm:mb-4 text-sm sm:text-base">If you see a network error:</p>
            <ul class="list-disc list-inside text-blue-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Check your internet connection</li>
              <li>Try refreshing the page</li>
              <li>Clear browser cache</li>
              <li>Try a different browser</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="bg-green-50 rounded-xl border border-green-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-green-900 mb-6 sm:mb-8 text-center">Payment Methods</h2>
        <div class="space-y-4 sm:space-y-6">
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-mobile-alt text-green-600 mr-2"></i>
              Mobile Money (MoMo)
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">We accept Mobile Money payments through Paystack.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Select "Pay with MoMo" at checkout</li>
              <li>Enter your mobile money number</li>
              <li>Complete payment via USSD or mobile app</li>
              <li>You'll receive a confirmation email</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-credit-card text-blue-600 mr-2"></i>
              Credit/Debit Cards
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">We accept all major credit and debit cards.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Visa, Mastercard, and Verve cards accepted</li>
              <li>Secure payment processing via Paystack</li>
              <li>3D Secure authentication may be required</li>
              <li>International cards are accepted</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="bg-cyan-50 rounded-xl border border-cyan-200 p-6 sm:p-8 lg:p-10 text-center">
        <i class="fas fa-headset text-4xl sm:text-5xl lg:text-6xl mb-6 sm:mb-8 text-cyan-600"></i>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6 text-cyan-900">Still Having Issues?</h2>
        <p class="mb-6 sm:mb-8 text-cyan-800 text-base sm:text-lg lg:text-xl">Our support team is ready to help you resolve any payment problems.</p>
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center">
          <a href="/contact" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-envelope mr-2"></i>Contact Support
          </a>
          <a href="tel:+233257940791" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-phone mr-2"></i>Call Support
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
