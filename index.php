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

<!-- Hero Section with Team Image -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden hero-section">
  <!-- Team Image Background -->
  <div class="absolute inset-0 w-full h-full">
    <img 
      src="<?php echo htmlspecialchars($team_image_url); ?>" 
      alt="ManuelCode Team"
      class="w-full h-full object-cover"
      loading="eager"
      style="object-fit: cover; width: 100%; height: 100%;">
    <!-- Dark overlay for better text readability -->
    <div class="absolute inset-0 bg-black/50 pointer-events-none"></div>
  </div>
  
  <!-- Hero Content -->
  <div class="relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 text-center">
    <div class="max-w-4xl mx-auto">
      <!-- Desktop Hero Content -->
      <h1 class="mb-4 sm:mb-6 desktop-hero-content">
        <span class="hero-title-main block text-3xl sm:text-5xl lg:text-7xl font-bold text-white mb-2 sm:mb-4" style="font-family: 'Inter', sans-serif; text-shadow: none;">ManuelCode</span>
        <span class="hero-title-accent block text-2xl sm:text-3xl lg:text-5xl font-semibold text-[#F5A623]" style="font-family: 'Inter', sans-serif; text-shadow: none;">Building Digital Excellence</span>
      </h1>
      <p class="hero-description text-lg sm:text-xl lg:text-2xl text-gray-200 mb-6 sm:mb-8 max-w-3xl mx-auto leading-relaxed desktop-hero-content">
        Transforming ideas into elegant code. Full-stack development, custom applications, and innovative software solutions.
      </p>
      <div class="hero-buttons flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 lg:gap-6 desktop-hero-content">
        <a href="services.php" 
           class="w-full sm:w-auto px-6 sm:px-8 py-2 sm:py-3 text-base sm:text-lg font-semibold text-white bg-[#F5A623] hover:bg-[#d88c1b] rounded-lg transition-all duration-300 flex items-center justify-center transform hover:scale-105">
          <i class="fas fa-cogs mr-2"></i>Our Services
        </a>
        <a href="projects.php" 
           class="w-full sm:w-auto px-6 sm:px-8 py-2 sm:py-3 text-base sm:text-lg font-semibold text-white bg-transparent border-2 border-white hover:bg-white hover:text-[#2D3E50] rounded-lg transition-all duration-300 flex items-center justify-center transform hover:scale-105">
          <i class="fas fa-code mr-2"></i>View Projects
        </a>
      </div>
      
      <!-- Mobile Hero Content -->
      <div class="mobile-hero-content">
        <h1 class="mobile-hero-title">ManuelCode!</h1>
        <p class="mobile-hero-subtitle" id="typewriter-text">Building Digital Excellence</p>
      </div>
    </div>
  </div>
</section>

<script>
// Typewriter effect for mobile hero with changing messages
document.addEventListener('DOMContentLoaded', function() {
  const typewriterElement = document.getElementById('typewriter-text');
  if (typewriterElement && window.innerWidth <= 768) {
    const messages = [
      "Building Digital Excellence",
      "ManuelCode",
      "Innovative Web Solutions",
      "Custom Applications",
      "Professional Development"
    ];
    
    let currentMessageIndex = 0;
    let currentCharIndex = 0;
    let isDeleting = false;
    let isRunning = true;
    
    function typeWriter() {
      if (!isRunning) return;
      
      const currentMessage = messages[currentMessageIndex];
      
      if (isDeleting) {
        // Deleting effect
        if (currentCharIndex > 0) {
          typewriterElement.textContent = currentMessage.substring(0, currentCharIndex - 1);
          currentCharIndex--;
        } else {
          // Finished deleting, move to next message
          isDeleting = false;
          currentMessageIndex = (currentMessageIndex + 1) % messages.length;
          currentCharIndex = 0;
          setTimeout(typeWriter, 500); // Pause before next message
          return;
        }
      } else {
        // Typing effect
        if (currentCharIndex < currentMessage.length) {
          typewriterElement.textContent = currentMessage.substring(0, currentCharIndex + 1);
          currentCharIndex++;
        } else {
          // Finished typing, start deleting
          isDeleting = true;
          setTimeout(typeWriter, 2000); // Pause at end before deleting
          return;
        }
      }
      
      // Calculate width for smooth animation (only during typing)
      if (!isDeleting) {
        const progress = currentCharIndex / currentMessage.length;
        typewriterElement.style.width = (progress * 100) + '%';
      }
      
      // Set typing speed
      let speed = isDeleting ? 50 : 100;
      
      // Continue the animation
      setTimeout(typeWriter, speed);
    }
    
    // Start typewriter effect after a short delay
    setTimeout(typeWriter, 1000);
    
    // Pause typewriter when page is not visible (optional performance improvement)
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        isRunning = false;
      } else {
        isRunning = true;
        // Restart if it was paused
        if (!isDeleting && currentCharIndex === 0) {
          setTimeout(typeWriter, 100);
        }
      }
    });
  }
});
</script>

<!-- About Section -->
<section class="py-16 bg-white">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div class="space-y-6">
        <div class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-medium">
          <i class="fas fa-star mr-2"></i>
          Professional Excellence
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
          Transforming Ideas Into 
          <span class="text-[#536895]">Digital Reality</span>
        </h2>
        <p class="text-lg text-gray-600 leading-relaxed">
          We specialize in creating innovative software solutions that drive business growth. 
          From concept to deployment, we deliver cutting-edge applications that exceed expectations.
        </p>
        <div class="grid grid-cols-2 gap-6 pt-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
              <i class="fas fa-layer-group text-white text-sm"></i>
            </div>
            <span class="text-gray-700 font-medium">Full-Stack Development</span>
          </div>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
              <i class="fas fa-mobile-alt text-white text-sm"></i>
            </div>
            <span class="text-gray-700 font-medium">Mobile Solutions</span>
          </div>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
              <i class="fas fa-cloud text-white text-sm"></i>
            </div>
            <span class="text-gray-700 font-medium">Cloud Architecture</span>
          </div>
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
              <i class="fas fa-headset text-white text-sm"></i>
            </div>
            <span class="text-gray-700 font-medium">24/7 Support</span>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 pt-6">
          <a href="about.php" class="inline-flex items-center justify-center px-6 py-3 bg-[#536895] text-white font-semibold rounded-lg hover:bg-[#4a5a7a] transition-all duration-300 transform hover:scale-105">
            Learn More
            <i class="fas fa-arrow-right ml-2"></i>
          </a>
          <a href="contact.php" class="inline-flex items-center justify-center px-6 py-3 border-2 border-[#536895] text-[#536895] font-semibold rounded-lg hover:bg-[#536895] hover:text-white transition-all duration-300">
            Get In Touch
          </a>
        </div>
      </div>
      <div class="relative">
        <div class="bg-gradient-to-br from-[#536895] to-[#4a5a7a] rounded-2xl p-8 text-white">
          <div class="space-y-6">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-code text-xl"></i>
              </div>
              <div>
                <h3 class="text-xl font-bold">Clean Code</h3>
                <p class="text-blue-100">Maintainable & scalable solutions</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-rocket text-xl"></i>
              </div>
              <div>
                <h3 class="text-xl font-bold">Fast Delivery</h3>
                <p class="text-blue-100">Quick turnaround times</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="fas fa-shield-alt text-xl"></i>
              </div>
              <div>
                <h3 class="text-xl font-bold">Secure & Reliable</h3>
                <p class="text-blue-100">Enterprise-grade security</p>
              </div>
            </div>
          </div>
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
