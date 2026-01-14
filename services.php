<?php 
include 'includes/user_activity_tracker.php';
include 'includes/meta_helper.php';

// Set page-specific meta data
setQuickMeta(
    'Our Services | Web Development, Mobile Apps & API Development - ManuelCode',
    'Comprehensive software development services including web development, mobile applications, API development, database design, and custom software solutions. Expert full-stack development for modern businesses.',
    'assets/favi/favicon.png',
    'web development, mobile apps, API development, software development, custom software, full-stack development, React, Node.js, PHP, database design'
);

include 'includes/header.php';
?>


<!-- Hero Section -->
<section class="relative py-16 md:py-24 bg-gradient-to-br from-[#536895] to-[#2D3E50] overflow-hidden page-hero-section">
  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <h1 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight" style="font-family: 'Inter', sans-serif;">
      Our Services
    </h1>
    <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto leading-relaxed">
      Comprehensive software solutions for your business
    </p>
  </div>
</section>

<!-- Services Overview -->
<section class="py-20 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        What We <span class="text-[#536895]">Offer</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        Comprehensive software development services tailored to your business needs
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Web Development -->
      <div class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
        <div class="h-24 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
          <i class="fas fa-code text-white text-2xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Web Development</h3>
          <p class="text-gray-600 mb-3 text-sm">Modern, responsive websites and web applications built with cutting-edge technologies.</p>
          <ul class="space-y-1 text-xs text-gray-600 mb-4">
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>React & Vue.js</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Node.js & PHP</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Responsive Design</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>SEO Optimization</li>
          </ul>
                     <div class="flex justify-center items-center">
             <a href="quote_request.php" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
               Get Quote
             </a>
           </div>
        </div>
      </div>

      <!-- Mobile Development -->
      <div class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
        <div class="h-24 bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
          <i class="fas fa-mobile-alt text-white text-2xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Mobile Apps</h3>
          <p class="text-gray-600 mb-3 text-sm">Native and cross-platform mobile applications for iOS and Android platforms.</p>
          <ul class="space-y-1 text-xs text-gray-600 mb-4">
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>React Native</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Flutter</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Native iOS/Android</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>App Store Publishing</li>
          </ul>
                     <div class="flex justify-center items-center">
             <a href="quote_request.php" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
               Get Quote
             </a>
           </div>
        </div>
      </div>

      <!-- API Development -->
      <div class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
        <div class="h-24 bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
          <i class="fas fa-cogs text-white text-2xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-gray-900 mb-2">API Development</h3>
          <p class="text-gray-600 mb-3 text-sm">Robust RESTful APIs and microservices for seamless integration.</p>
          <ul class="space-y-1 text-xs text-gray-600 mb-4">
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>RESTful APIs</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>GraphQL</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Microservices</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>API Documentation</li>
          </ul>
                     <div class="flex justify-center items-center">
             <a href="quote_request.php" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
               Get Quote
             </a>
           </div>
        </div>
      </div>

      <!-- Database Design -->
      <div class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
        <div class="h-24 bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
          <i class="fas fa-database text-white text-2xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Database Design</h3>
          <p class="text-gray-600 mb-3 text-sm">Optimized database architecture and data management solutions.</p>
          <ul class="space-y-1 text-xs text-gray-600 mb-4">
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>MySQL & PostgreSQL</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>MongoDB</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Redis</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Data Migration</li>
          </ul>
                     <div class="flex justify-center items-center">
             <a href="quote_request.php" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
               Get Quote
             </a>
           </div>
        </div>
      </div>

      <!-- Cloud Solutions -->
      <div class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
        <div class="h-24 bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center">
          <i class="fas fa-cloud text-white text-2xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Cloud Solutions</h3>
          <p class="text-gray-600 mb-3 text-sm">Scalable cloud infrastructure and deployment solutions.</p>
          <ul class="space-y-1 text-xs text-gray-600 mb-4">
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>AWS & Azure</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Docker & Kubernetes</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>CI/CD Pipelines</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Server Management</li>
          </ul>
                     <div class="flex justify-center items-center">
             <a href="quote_request.php" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
               Get Quote
             </a>
           </div>
        </div>
      </div>

      <!-- Digital Products -->
      <div class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
        <div class="h-24 bg-gradient-to-br from-pink-500 to-pink-600 flex items-center justify-center">
          <i class="fas fa-shopping-cart text-white text-2xl group-hover:scale-110 transition-transform duration-300"></i>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Digital Products</h3>
          <p class="text-gray-600 mb-3 text-sm">Ready-to-use software products and digital solutions.</p>
          <ul class="space-y-1 text-xs text-gray-600 mb-4">
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>E-commerce Platforms</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>CMS Solutions</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Custom Software</li>
            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Product Support</li>
          </ul>
                     <div class="flex justify-center items-center">
             <a href="quote_request.php" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
               Get Quote
             </a>
           </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Process Section -->
<section class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        Our <span class="text-[#536895]">Process</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        A proven methodology that ensures successful project delivery
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Discovery -->
      <div class="text-center">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-white font-bold text-xl">1</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Discovery</h3>
        <p class="text-gray-600">We analyze your requirements and create a detailed project plan.</p>
      </div>
      
      <!-- Design -->
      <div class="text-center">
        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-white font-bold text-xl">2</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Design</h3>
        <p class="text-gray-600">Creating wireframes and prototypes to visualize the solution.</p>
      </div>
      
      <!-- Development -->
      <div class="text-center">
        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-white font-bold text-xl">3</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Development</h3>
        <p class="text-gray-600">Building your solution with clean, maintainable code.</p>
      </div>
      
      <!-- Delivery -->
      <div class="text-center">
        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-white font-bold text-xl">4</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Delivery</h3>
        <p class="text-gray-600">Testing, deployment, and ongoing support for your project.</p>
      </div>
    </div>
  </div>
</section>



<?php include 'includes/footer.php'; ?>
