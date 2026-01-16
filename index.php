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
        // Trim any whitespace and ensure proper URL format
        $team_image_url = trim($result['value']);
        // Remove any trailing spaces or invalid characters
        $team_image_url = rtrim($team_image_url, " \t\n\r\0\x0B/");
        // Remove any existing cache-busting parameters
        $team_image_url = preg_replace('/[?&]v=\d+/', '', $team_image_url);
        $team_image_url = preg_replace('/[?&]t=\d+/', '', $team_image_url);
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

// Ensure URL is properly formatted (trim again just in case)
$team_image_url = trim($team_image_url);

// Set flag to indicate this is the homepage (for header/footer conditional display)
$is_homepage = true;
?>

<!-- Combined Section: Image and About (Side by side on desktop, stacked on mobile) -->
<section class="w-full bg-white min-h-screen">
  <div class="w-full">
    <!-- Mobile: Stacked Layout -->
    <div class="block lg:hidden">
      <!-- Image Section (Mobile) -->
      <div class="w-full overflow-hidden bg-white flex items-center justify-center py-8">
        <img 
          src="<?php echo htmlspecialchars($team_image_url); ?>?v=<?php echo time(); ?>" 
          alt="ManuelCode"
          class="max-w-full max-h-[60vh] object-contain object-center block mx-auto"
          loading="eager">
      </div>
      
      <!-- About Section (Mobile) -->
      <div class="px-4 sm:px-6 py-8">
        <div class="max-w-2xl mx-auto text-center space-y-5 border-2 border-gray-300 rounded-lg p-6 bg-white">
          <div>
            <p class="text-3xl sm:text-4xl text-[#536895] font-semibold mb-4">
              Welcome
            </p>
          </div>
          
          <div class="prose prose-lg max-w-none">
            <p class="text-base sm:text-lg text-gray-700 leading-relaxed">
              Transforming ideas into elegant, high-performance digital solutions.
            </p>
          </div>
          
          <div class="pt-4">
            <div class="flex flex-col gap-3">
              <a href="store.php" class="inline-flex items-center justify-center px-6 py-3 bg-[#536895] text-white font-semibold rounded-lg hover:bg-[#4a5a7a] transition-all duration-300">
                Store
                <i class="fas fa-store ml-2"></i>
              </a>
              <a href="about.php" class="inline-flex items-center justify-center px-6 py-3 border-2 border-[#536895] text-[#536895] font-semibold rounded-lg hover:bg-[#536895] hover:text-white transition-all duration-300">
                About Us
                <i class="fas fa-info-circle ml-2"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Desktop: Side by Side Layout -->
    <div class="hidden lg:flex lg:items-center lg:justify-center min-h-screen" id="desktop-hero-section">
      <div class="w-full max-w-7xl mx-auto flex items-center justify-center">
        <!-- Image Section (Desktop - Left Side) -->
        <div class="w-1/2 flex-shrink-0 bg-white flex items-center justify-center px-8 xl:px-12" id="image-container">
          <img 
            src="<?php echo htmlspecialchars($team_image_url); ?>?v=<?php echo time(); ?>" 
            alt="ManuelCode"
            class="max-w-full max-h-screen object-contain object-center"
            id="hero-image"
            loading="eager">
        </div>
        
        <!-- About Section (Desktop - Right Side) -->
        <div class="w-1/2 flex items-center justify-center px-8 xl:px-12" id="text-container">
          <div class="w-full max-w-2xl space-y-5 text-center border-2 border-gray-300 rounded-lg p-8 xl:p-10 bg-white flex flex-col justify-center" id="text-card">
          <div>
            <p class="text-4xl xl:text-5xl text-[#536895] font-semibold mb-4">
              Welcome
            </p>
          </div>
          
          <div class="prose prose-lg max-w-none">
            <p class="text-lg xl:text-xl text-gray-700 leading-relaxed">
              Transforming ideas into elegant, high-performance digital solutions.
            </p>
          </div>
          
          <div class="pt-2">
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <a href="store.php" class="inline-flex items-center justify-center px-8 py-3 bg-[#536895] text-white font-semibold rounded-lg hover:bg-[#4a5a7a] transition-all duration-300">
                Store
                <i class="fas fa-store ml-2"></i>
              </a>
              <a href="about.php" class="inline-flex items-center justify-center px-8 py-3 border-2 border-[#536895] text-[#536895] font-semibold rounded-lg hover:bg-[#536895] hover:text-white transition-all duration-300">
                About Us
                <i class="fas fa-info-circle ml-2"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
</section>


<script>
// Match text container height to image container height on desktop - exact match
document.addEventListener('DOMContentLoaded', function() {
    const imageContainer = document.getElementById('image-container');
    const textContainer = document.getElementById('text-container');
    const textCard = document.getElementById('text-card');
    const heroImage = document.getElementById('hero-image');
    
    if (imageContainer && textContainer && textCard && heroImage && window.innerWidth >= 1024) {
        function matchHeights() {
            // Wait for image to load
            if (heroImage.complete) {
                setHeights();
            } else {
                heroImage.addEventListener('load', setHeights);
            }
        }
        
        function setHeights() {
            // Get the exact height of the image container
            const imageHeight = imageContainer.offsetHeight;
            
            // Set text container to match image container height exactly (no difference)
            textContainer.style.height = imageHeight + 'px';
            textContainer.style.minHeight = imageHeight + 'px';
            textContainer.style.maxHeight = imageHeight + 'px';
            
            // Also ensure the card inside matches
            textCard.style.height = '100%';
            textCard.style.display = 'flex';
            textCard.style.flexDirection = 'column';
            textCard.style.justifyContent = 'center';
        }
        
        matchHeights();
        
        // Recalculate on window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 1024) {
                    setHeights();
                } else {
                    textContainer.style.height = 'auto';
                    textContainer.style.minHeight = 'auto';
                    textContainer.style.maxHeight = 'none';
                    if (textCard) {
                        textCard.style.height = 'auto';
                        textCard.style.display = 'block';
                    }
                }
            }, 250);
        });
    }
});
</script>

<?php 
// Only include footer if not on homepage
if (!isset($is_homepage) || !$is_homepage) {
    include 'includes/footer.php';
}
?>
