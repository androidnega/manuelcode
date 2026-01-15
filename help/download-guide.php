<?php 
include '../includes/db.php';
include '../includes/meta_helper.php';

// Set page-specific meta data
setQuickMeta(
    'How to Download Purchased Products - ManuelCode Help Guide',
    'Complete guide on how to download your purchased digital products from ManuelCode. Step-by-step instructions for accessing your downloads.',
    'assets/favi/favicon.png',
    'download guide, how to download, product download, digital product download, ManuelCode help'
);

include '../includes/header.php';
?>

<div class="flex flex-col min-h-screen">
  <!-- Hero Section -->
  <section class="relative py-12 sm:py-16 md:py-20 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 mb-4 sm:mb-6">
        <i class="fas fa-download text-white text-2xl sm:text-3xl"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 sm:mb-4 leading-tight">
        How to Download Your Purchased Products
      </h1>
      <p class="text-base sm:text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed px-2">
        Complete guide to accessing and downloading your digital products
      </p>
    </div>
  </section>

  <!-- Main Content -->
  <section class="py-12 sm:py-16 bg-white flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Quick Steps -->
      <div class="bg-blue-50 rounded-xl border border-blue-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-blue-900 mb-6 sm:mb-8 text-center">Quick Steps</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
          <div class="text-center p-6 sm:p-8 bg-white rounded-xl border border-blue-100">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl sm:text-2xl mx-auto mb-4 sm:mb-6">1</div>
            <h3 class="font-semibold text-blue-900 text-lg sm:text-xl mb-2 sm:mb-3">Go to Downloads</h3>
            <p class="text-blue-700 text-sm sm:text-base">Navigate to your dashboard downloads section</p>
          </div>
          <div class="text-center p-6 sm:p-8 bg-white rounded-xl border border-green-100">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-xl sm:text-2xl mx-auto mb-4 sm:mb-6">2</div>
            <h3 class="font-semibold text-green-900 text-lg sm:text-xl mb-2 sm:mb-3">Find Your Product</h3>
            <p class="text-green-700 text-sm sm:text-base">Locate the product you want to download</p>
          </div>
          <div class="text-center p-6 sm:p-8 bg-white rounded-xl border border-purple-100 sm:col-span-2 lg:col-span-1">
            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-xl sm:text-2xl mx-auto mb-4 sm:mb-6">3</div>
            <h3 class="font-semibold text-purple-900 text-lg sm:text-xl mb-2 sm:mb-3">Click Download</h3>
            <p class="text-purple-700 text-sm sm:text-base">Click the download button to get your file</p>
          </div>
        </div>
      </div>

      <!-- Detailed Instructions -->
      <div class="bg-green-50 rounded-xl border border-green-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-green-900 mb-6 sm:mb-8 text-center">Detailed Instructions</h2>
        
        <div class="space-y-4 sm:space-y-6">
          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>
              Step 1: Access Your Dashboard
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Log into your ManuelCode account and navigate to your dashboard.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Click on "Dashboard" in the main navigation</li>
              <li>You'll see an overview of your account and recent purchases</li>
              <li>Look for the "Downloads" section in the sidebar</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-download text-green-600 mr-2"></i>
              Step 2: Navigate to Downloads
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Click on "Downloads" in your dashboard sidebar to view all your purchased products.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>All your purchased products will be listed here</li>
              <li>Products are organized by purchase date (newest first)</li>
              <li>You can see the product name, purchase date, and download status</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-search text-purple-600 mr-2"></i>
              Step 3: Find Your Product
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Locate the specific product you want to download from your list.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Use the search function if you have many products</li>
              <li>Check the product status - it should show "Ready for Download"</li>
              <li>If it shows "Processing", wait a few minutes and refresh the page</li>
            </ul>
          </div>

          <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">
              <i class="fas fa-download text-red-600 mr-2"></i>
              Step 4: Download Your Product
            </h3>
            <p class="text-sm sm:text-base text-gray-700 mb-2 sm:mb-3">Click the download button to get your digital product.</p>
            <ul class="list-disc list-inside text-sm sm:text-base text-gray-600 ml-2 sm:ml-4 space-y-1">
              <li>Click the blue "Download" button next to your product</li>
              <li>Your browser will start downloading the file</li>
              <li>Check your browser's download folder for the file</li>
              <li>Some products may be delivered via Google Drive links</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Common Issues -->
      <div class="bg-orange-50 rounded-xl border border-orange-200 p-6 sm:p-8 lg:p-10 mb-8 sm:mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-orange-900 mb-6 sm:mb-8 text-center">Common Issues & Solutions</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
          <div class="bg-white rounded-xl p-6 sm:p-8 border border-yellow-200">
            <h3 class="font-semibold text-yellow-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 text-xl sm:text-2xl"></i>
              Download Button Not Working
            </h3>
            <p class="text-yellow-800 mb-3 sm:mb-4 text-sm sm:text-base">If the download button doesn't respond:</p>
            <ul class="list-disc list-inside text-yellow-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Check if you're logged into your account</li>
              <li>Try refreshing the page</li>
              <li>Clear your browser cache and cookies</li>
              <li>Try a different browser</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-6 sm:p-8 border border-red-200">
            <h3 class="font-semibold text-red-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-times-circle text-red-600 mr-2 text-xl sm:text-2xl"></i>
              "Download Not Ready" Message
            </h3>
            <p class="text-red-800 mb-3 sm:mb-4 text-sm sm:text-base">If you see this message:</p>
            <ul class="list-disc list-inside text-red-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Wait 5-10 minutes and refresh the page</li>
              <li>Contact support if the issue persists</li>
              <li>Check your email for download instructions</li>
            </ul>
          </div>

          <div class="bg-white rounded-xl p-6 sm:p-8 border border-blue-200 sm:col-span-2 lg:col-span-1">
            <h3 class="font-semibold text-blue-900 mb-3 sm:mb-4 text-lg sm:text-xl">
              <i class="fas fa-info-circle text-blue-600 mr-2 text-xl sm:text-2xl"></i>
              Google Drive Links
            </h3>
            <p class="text-blue-800 mb-3 sm:mb-4 text-sm sm:text-base">For products delivered via Google Drive:</p>
            <ul class="list-disc list-inside text-blue-700 space-y-1 sm:space-y-2 text-sm sm:text-base">
              <li>Click the Google Drive link provided</li>
              <li>You may need to request access if it's a private folder</li>
              <li>Download files individually or use "Download All"</li>
              <li>Contact support if you can't access the Drive folder</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Contact Support -->
      <div class="bg-cyan-50 rounded-xl border border-cyan-200 p-6 sm:p-8 lg:p-10 text-center">
        <i class="fas fa-headset text-4xl sm:text-5xl lg:text-6xl mb-6 sm:mb-8 text-cyan-600"></i>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 sm:mb-6 text-cyan-900">Need More Help?</h2>
        <p class="mb-6 sm:mb-8 text-cyan-800 text-base sm:text-lg lg:text-xl">If you're still having trouble downloading your products, our support team is here to help.</p>
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center">
          <a href="mailto:support@manuelcode.info" class="bg-white text-cyan-600 px-6 sm:px-8 lg:px-10 py-3 sm:py-4 rounded-xl font-semibold hover:bg-cyan-50 transition-colors border border-cyan-200 text-sm sm:text-base lg:text-lg">
            <i class="fas fa-envelope mr-2"></i>Email Support
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
