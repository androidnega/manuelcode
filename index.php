<?php 
// Include required files
include_once 'includes/db.php';  // Database connection first
include_once 'includes/maintenance_mode.php';  // Then maintenance mode
include_once 'includes/auth_helper.php';
include_once 'includes/util.php';
include_once 'includes/user_activity_tracker.php';
include_once 'includes/analytics_tracker.php';
include_once 'includes/meta_helper.php';

// Check if maintenance mode is active
if (should_show_maintenance_page()) {
    display_maintenance_page();
}

// Set page-specific meta data
setQuickMeta(
    'ManuelCode | Building Digital Excellence',
    'Professional Software Engineer specializing in full-stack development, custom web applications, mobile solutions, and innovative software architecture. Turning complex problems into elegant code.',
    'assets/favi/favicon.png',
    'software engineer, full-stack developer, web development, mobile apps, custom software, PHP, JavaScript, React, Node.js, database design, professional development'
);

include_once 'includes/header.php';
include_once 'includes/cloudinary_helper.php';

// Get team image URL from Cloudinary
$team_image_url = '';
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'homepage_team_image_url'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['value'])) {
        $team_image_url = $result['value'];
    } else {
        // Fallback: Try to get from Cloudinary using public_id
        $cloudinaryHelper = new CloudinaryHelper($pdo);
        if ($cloudinaryHelper->isEnabled()) {
            $team_image_url = $cloudinaryHelper->getOptimizedUrl('homepage/team', [
                'width' => 1920,
                'height' => 1080,
                'crop' => 'fill',
                'quality' => 'auto',
                'format' => 'auto'
            ]);
        }
    }
} catch (Exception $e) {
    error_log("Error getting team image URL: " . $e->getMessage());
}

// Fallback to local image if Cloudinary URL not available
if (empty($team_image_url)) {
    $team_image_url = 'assets/images/team.png';
}
?>

<!-- Section 1: Image Section -->
<section class="w-full">
  <div class="relative w-full" style="aspect-ratio: 16/9; min-height: 60vh;">
    <img 
      src="<?php echo htmlspecialchars($team_image_url); ?>" 
      alt="ManuelCode"
      class="w-full h-full object-cover object-center"
      loading="eager">
  </div>
</section>

<!-- Section 2: About ManuelCode -->
<section class="py-16 md:py-24 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto text-center space-y-8">
      <div>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-4">
          ManuelCode
        </h1>
        <p class="text-xl md:text-2xl text-[#536895] font-semibold mb-6">
          Building Digital Excellence
        </p>
      </div>
      
      <div class="prose prose-lg max-w-none text-left space-y-6">
        <p class="text-lg md:text-xl text-gray-700 leading-relaxed">
          ManuelCode is a professional software development company specializing in transforming ideas into elegant, 
          high-performance digital solutions. We combine cutting-edge technology with innovative thinking to deliver 
          software that drives business growth and exceeds expectations.
        </p>
        
        <p class="text-lg md:text-xl text-gray-700 leading-relaxed">
          Our expertise spans full-stack web development, mobile applications, cloud architecture, and custom software 
          solutions. We work closely with clients to understand their unique needs and deliver tailored solutions that 
          are scalable, secure, and maintainable.
        </p>
        
        <p class="text-lg md:text-xl text-gray-700 leading-relaxed">
          At ManuelCode, we believe in clean code, fast delivery, and exceptional support. Every project is an opportunity 
          to create something remarkable that makes a real difference for our clients and their users.
        </p>
      </div>
      
      <div class="pt-8">
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a href="about.php" class="inline-flex items-center justify-center px-8 py-3 bg-[#536895] text-white font-semibold rounded-lg hover:bg-[#4a5a7a] transition-all duration-300">
            Learn More About Us
            <i class="fas fa-arrow-right ml-2"></i>
          </a>
          <a href="contact.php" class="inline-flex items-center justify-center px-8 py-3 border-2 border-[#536895] text-[#536895] font-semibold rounded-lg hover:bg-[#536895] hover:text-white transition-all duration-300">
            Get In Touch
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Services Section -->
<section class="py-16 bg-gray-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
        Our <span class="text-[#536895]">Services</span>
      </h2>
      <p class="text-lg text-gray-600 max-w-2xl mx-auto">
        Comprehensive software solutions tailored to your business needs
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Web Development -->
      <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 p-6">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-code text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Web Development</h3>
        <p class="text-gray-600 mb-4">Modern, responsive websites and web applications built with cutting-edge technologies.</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>React & Vue.js</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Node.js & PHP</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Responsive Design</li>
        </ul>
      </div>

      <!-- Mobile Development -->
      <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 p-6">
        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-mobile-alt text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Mobile Apps</h3>
        <p class="text-gray-600 mb-4">Native and cross-platform mobile applications for iOS and Android platforms.</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>React Native</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Flutter</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Native iOS/Android</li>
        </ul>
      </div>

      <!-- API Development -->
      <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 p-6">
        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-cogs text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">API Development</h3>
        <p class="text-gray-600 mb-4">Robust RESTful APIs and microservices for seamless integration.</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>RESTful APIs</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>GraphQL</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Microservices</li>
        </ul>
      </div>

      <!-- Database Design -->
      <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 p-6">
        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-database text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Database Design</h3>
        <p class="text-gray-600 mb-4">Optimized database architecture and data management solutions.</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>MySQL & PostgreSQL</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>MongoDB</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Redis</li>
        </ul>
      </div>

      <!-- Cloud Solutions -->
      <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 p-6">
        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-cloud text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Cloud Solutions</h3>
        <p class="text-gray-600 mb-4">Scalable cloud infrastructure and deployment solutions.</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>AWS & Azure</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Docker & Kubernetes</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>CI/CD Pipelines</li>
        </ul>
      </div>

      <!-- Digital Products -->
      <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 p-6">
        <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-shopping-cart text-white text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Digital Products</h3>
        <p class="text-gray-600 mb-4">Ready-to-use software products and digital solutions.</p>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>E-commerce Platforms</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>CMS Solutions</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Custom Software</li>
        </ul>
      </div>
    </div>
    
    <div class="text-center mt-12">
      <a href="services.php" class="inline-flex items-center px-8 py-4 bg-[#536895] text-white font-semibold rounded-lg hover:bg-[#4a5a7a] transition-all duration-300 transform hover:scale-105">
        View All Services
        <i class="fas fa-arrow-right ml-2"></i>
      </a>
    </div>
  </div>
</section>





<?php include 'includes/footer.php'; ?>
