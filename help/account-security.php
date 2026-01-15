<?php 
include '../includes/db.php';
include '../includes/meta_helper.php';

setQuickMeta(
    'Account Security and Privacy - ManuelCode Help Guide',
    'Learn how to keep your ManuelCode account secure. Privacy settings and security best practices.',
    'assets/favi/favicon.png',
    'account security, privacy, account safety, security guide, ManuelCode help'
);

include '../includes/header.php';
?>

<div class="flex flex-col min-h-screen">
  <section class="relative py-12 sm:py-16 md:py-20 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 mb-4 sm:mb-6">
        <i class="fas fa-shield-alt text-white text-2xl sm:text-3xl"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 sm:mb-4 leading-tight">
        Account Security and Privacy
      </h1>
      <p class="text-base sm:text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed px-2">
        Keep your account safe with these security best practices
      </p>
    </div>
  </section>

  <section class="py-12 sm:py-16 bg-white flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="bg-purple-50 rounded-xl border border-purple-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-purple-900 mb-6 sm:mb-8 text-center">Security Best Practices</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
          <div class="bg-white rounded-xl p-6 sm:p-8 border border-green-200">
            <h3 class="font-semibold text-green-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-key text-green-600 mr-2 text-xl sm:text-2xl"></i>
              Strong Password
            </h3>
            <p class="text-green-800 mb-3 sm:mb-4 text-sm sm:text-base">Create a secure password:</p>
            <ul class="list-disc list-inside text-green-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Use at least 8 characters</li>
              <li>Mix letters, numbers, and symbols</li>
              <li>Don't reuse passwords</li>
              <li>Change it regularly</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-6 sm:p-8 border border-blue-200">
            <h3 class="font-semibold text-blue-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-lock text-blue-600 mr-2 text-xl sm:text-2xl"></i>
              Two-Factor Auth
            </h3>
            <p class="text-blue-800 mb-3 sm:mb-4 text-sm sm:text-base">Enable 2FA for extra security:</p>
            <ul class="list-disc list-inside text-blue-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Go to account settings</li>
              <li>Enable two-factor authentication</li>
              <li>Use OTP for login</li>
              <li>Keep backup codes safe</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-6 sm:p-8 border border-orange-200 sm:col-span-2 lg:col-span-1">
            <h3 class="font-semibold text-orange-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-sign-out-alt text-orange-600 mr-2 text-xl sm:text-2xl"></i>
              Logout Properly
            </h3>
            <p class="text-orange-800 mb-3 sm:mb-4 text-sm sm:text-base">Always logout when done:</p>
            <ul class="list-disc list-inside text-orange-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Click logout button</li>
              <li>Clear browser cache on shared devices</li>
              <li>Don't stay logged in on public computers</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="bg-green-50 rounded-xl border border-green-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-green-900 mb-6 sm:mb-8 text-center">Privacy Settings</h2>
        <div class="space-y-4 sm:space-y-6">
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-user-shield text-green-600 mr-2"></i>
              Data Protection
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">We protect your personal information:</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>All data is encrypted in transit</li>
              <li>Payment information is never stored</li>
              <li>We comply with data protection regulations</li>
              <li>Your privacy is our priority</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-eye-slash text-blue-600 mr-2"></i>
              Account Visibility
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Control what others can see:</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Your email is never shared publicly</li>
              <li>Purchase history is private</li>
              <li>Download links are unique to you</li>
              <li>Account settings are confidential</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="bg-red-50 rounded-xl border border-red-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-red-900 mb-6 sm:mb-8 text-center">If Your Account is Compromised</h2>
        <div class="space-y-4 sm:space-y-6">
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
              Immediate Actions
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">If you suspect unauthorized access:</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Change your password immediately</li>
              <li>Logout from all devices</li>
              <li>Contact support right away</li>
              <li>Review your purchase history</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="bg-cyan-50 rounded-xl border border-cyan-200 p-6 sm:p-8 lg:p-10 text-center">
        <i class="fas fa-headset text-4xl sm:text-5xl lg:text-6xl mb-6 sm:mb-8 text-cyan-600"></i>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6 text-cyan-900">Need Help?</h2>
        <p class="mb-6 sm:mb-8 text-cyan-800 text-base sm:text-lg lg:text-xl">Contact our support team for security assistance.</p>
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center">
          <a href="/contact" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-envelope mr-2"></i>Contact Support
          </a>
          <a href="/dashboard/settings" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-cog mr-2"></i>Account Settings
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../includes/footer.php'; ?>
