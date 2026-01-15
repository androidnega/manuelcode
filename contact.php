<?php 
include 'includes/db.php';
include 'includes/user_activity_tracker.php';
include 'includes/meta_helper.php';

// Set page-specific meta data
setQuickMeta(
    'Contact ManuelCode | Get in Touch for Software Development Services',
    'Contact ManuelCode for professional software development services, project inquiries, or technical consultations. Get a quote for web development, mobile apps, or custom software solutions.',
    'assets/favi/favicon.png',
    'contact ManuelCode, software development services, project inquiry, technical consultation, get quote, web development contact'
);

include 'includes/header.php';
?>

<div class="flex flex-col min-h-screen">
  <!-- Hero Section -->
  <section class="relative py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden page-hero-section">
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 sm:mb-4 leading-tight" style="font-family: 'Inter', sans-serif;">
        Contact Us
      </h1>
      <p class="text-base sm:text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed px-2">
        Get in touch and let's discuss your project
      </p>
    </div>
  </section>

  <!-- Contact Information -->
  <section class="py-12 sm:py-16 lg:py-20 bg-white flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-8 sm:mb-12">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">
          Get In <span class="text-[#536895]">Touch</span>
        </h2>
        <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto px-2">
          Choose your preferred way to contact us. We're here to help with your project needs.
        </p>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 max-w-4xl mx-auto">
        <!-- WhatsApp Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8 text-center">
          <div class="w-12 h-12 sm:w-16 sm:h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
            <i class="fab fa-whatsapp text-white text-xl sm:text-2xl"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">WhatsApp</h3>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Quick chat for instant support and project discussions</p>
          <a href="https://wa.me/233541069241" target="_blank" 
             class="inline-flex items-center justify-center w-full bg-green-500 hover:bg-green-600 text-white py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg font-semibold text-sm sm:text-base transition-colors">
            <i class="fab fa-whatsapp mr-2"></i>
            <span class="hidden sm:inline">Chat on </span>WhatsApp
          </a>
        </div>
        
        <!-- SMS Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8 text-center">
          <div class="w-12 h-12 sm:w-16 sm:h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
            <i class="fas fa-sms text-white text-xl sm:text-2xl"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">SMS</h3>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Send us a text message for quick inquiries</p>
          <a href="sms:+233257940791" 
             class="inline-flex items-center justify-center w-full bg-blue-500 hover:bg-blue-600 text-white py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg font-semibold text-sm sm:text-base transition-colors">
            <i class="fas fa-sms mr-2"></i>
            Send SMS
          </a>
        </div>
        
        <!-- Phone Call Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 sm:p-8 text-center sm:col-span-2 lg:col-span-1">
          <div class="w-12 h-12 sm:w-16 sm:h-16 bg-[#536895] rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
            <i class="fas fa-phone text-white text-xl sm:text-2xl"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-3 sm:mb-4">Phone Call</h3>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Call us directly for detailed project discussions</p>
          <a href="tel:+233257940791" 
             class="inline-flex items-center justify-center w-full bg-[#536895] hover:bg-[#4a5a7a] text-white py-2.5 sm:py-3 px-4 sm:px-6 rounded-lg font-semibold text-sm sm:text-base transition-colors">
            <i class="fas fa-phone mr-2"></i>
            Call Now
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'includes/footer.php'; ?>
