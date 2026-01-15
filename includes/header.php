<?php 
// Include auth helper for session management
if (file_exists(__DIR__ . '/auth_helper.php')) {
    include 'auth_helper.php';
} elseif (file_exists(__DIR__ . '/../includes/auth_helper.php')) {
    include '../includes/auth_helper.php';
}

// Include maintenance mode check for all pages
if (file_exists(__DIR__ . '/maintenance_mode.php')) {
    include 'maintenance_mode.php';
} elseif (file_exists(__DIR__ . '/../includes/maintenance_mode.php')) {
    include '../includes/maintenance_mode.php';
}

// Include meta helper for dynamic meta tags
if (file_exists(__DIR__ . '/meta_helper.php')) {
    include 'meta_helper.php';
} elseif (file_exists(__DIR__ . '/../includes/meta_helper.php')) {
    include '../includes/meta_helper.php';
}

// Check if maintenance mode is active (only for non-admin pages)
if (function_exists('should_show_maintenance_page') && should_show_maintenance_page()) {
    display_maintenance_page();
}

// Prevent function redeclaration
if (!function_exists('isCurrentPage')) {
    // Function to determine if current page is active
    function isCurrentPage($pageName) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage === $pageName;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
      
  <!-- Lazy Loading Screen Styles -->
  <style>
    .lazy-loading-screen {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: white;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 1;
      transition: opacity 0.8s ease-in-out;
    }
    
    .lazy-loading-screen.hidden {
      opacity: 0;
      pointer-events: none;
    }
    

    
    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 4px solid rgba(0, 0, 0, 0.1);
      border-top: 4px solid #536895;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 20px;
    }
    
    .loading-text {
      color: #2D3E50;
      font-size: 18px;
      font-weight: 500;
      text-align: center;
      margin-bottom: 10px;
      font-family: 'Arial', sans-serif;
    }
    
    .loading-subtext {
      color: #536895;
      font-size: 14px;
      text-align: center;
      font-family: 'Arial', sans-serif;
    }
    
    .loading-counter {
      color: #F5A623;
      font-size: 24px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 15px;
      font-family: 'Arial', sans-serif;
    }
    
    .loading-progress {
      width: 200px;
      height: 4px;
      background: rgba(0, 0, 0, 0.1);
      border-radius: 2px;
      margin-top: 20px;
      overflow: hidden;
    }
    
    .loading-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #536895, #F5A623);
      border-radius: 2px;
      width: 0%;
      transition: width 0.3s ease;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.05); opacity: 0.8; }
    }
    
    .loading-dots {
      display: inline-block;
      animation: dots 1.5s infinite;
    }
    
    @keyframes dots {
      0%, 20% { content: ""; }
      40% { content: "."; }
      60% { content: ".."; }
      80%, 100% { content: "..."; }
    }
    
    /* Hide body content while loading */
    body.loading {
      overflow: hidden;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .loading-spinner {
        width: 40px;
        height: 40px;
        margin-bottom: 15px;
      }
      
      .loading-text {
        font-size: 16px;
      }
      
      .loading-subtext {
        font-size: 12px;
      }
      
      .loading-progress {
        width: 150px;
      }
    }
  </style>
  <?php
  // Load SEO settings from database for additional verification codes
  $seo_settings = [];
  if (file_exists(__DIR__ . '/db.php')) {
      include_once 'db.php';
      try {
          $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM seo_settings");
          $stmt->execute();
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $seo_settings[$row['setting_key']] = $row['setting_value'];
          }
      } catch (Exception $e) {
          // Use default values if database is not available
      }
  }
  
  // Set default values for verification codes
  $google_analytics_id = $seo_settings['google_analytics_id'] ?? '';
  $google_search_console = $seo_settings['google_search_console'] ?? '';
  $bing_webmaster = $seo_settings['bing_webmaster'] ?? '';
  $yandex_webmaster = $seo_settings['yandex_webmaster'] ?? '';
  $favicon_url = $seo_settings['favicon_url'] ?? 'assets/favi/favicon.png';
  
  // Render dynamic meta tags
  renderMetaTags();
  
  // Force title override for live server (cache busting)
  if (basename($_SERVER['PHP_SELF']) === 'index.php') {
    echo '<title>ManuelCode | Building Digital Excellence</title>';
  }
  ?>
  
  <!-- Search Engine Verification -->
  <?php if (!empty($google_search_console)): ?>
  <meta name="google-site-verification" content="<?php echo htmlspecialchars($google_search_console); ?>">
  <?php endif; ?>
  <?php if (!empty($bing_webmaster)): ?>
  <meta name="msvalidate.01" content="<?php echo htmlspecialchars($bing_webmaster); ?>">
  <?php endif; ?>
  <?php if (!empty($yandex_webmaster)): ?>
  <meta name="yandex-verification" content="<?php echo htmlspecialchars($yandex_webmaster); ?>">
  <?php endif; ?>
  
  <!-- Google Analytics -->
  <?php if (!empty($google_analytics_id)): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($google_analytics_id); ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo htmlspecialchars($google_analytics_id); ?>');
  </script>
  <?php endif; ?>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($favicon_url); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars($favicon_url); ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($seo_settings['apple_touch_icon'] ?? $favicon_url); ?>">
  <link rel="manifest" href="site.webmanifest">
  <meta name="msapplication-TileColor" content="#2D3E50">
  <meta name="theme-color" content="#2D3E50">
  <!-- Preload Critical Resources -->
  <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
  <link rel="preload" href="assets/css/style.css" as="style">
  <link rel="preload" href="assets/favi/favicon.png" as="image">
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&display=swap" as="style">
  
  <!-- DNS Prefetch for External Resources -->
  <link rel="dns-prefetch" href="//fonts.googleapis.com">
  <link rel="dns-prefetch" href="//fonts.gstatic.com">
  <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
  <link rel="dns-prefetch" href="//www.googletagmanager.com">
  
  <!-- Preconnect to Critical Third-Party Origins -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  
  <!-- Critical CSS Inline -->
  <style>
    /* Critical above-the-fold styles */
    body { 
      font-family: 'Poppins', 'Inter', sans-serif; 
      margin: 0; 
      padding: 0; 
      background-color: #fafafa; 
      color: #536895; 
    }
    .nav-container { 
      background: rgba(255, 255, 255, 0.98); 
      backdrop-filter: blur(20px); 
      position: sticky; 
      top: 0; 
      z-index: 99999; 
    }
  </style>
  
  <!-- Load Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Defer Non-Critical Scripts -->
  <script src="assets/js/session-timeout.js" defer></script>
  <script src="assets/js/analytics.js" defer></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  
  <?php
  // Track page visit for analytics
  if (file_exists(__DIR__ . '/analytics_tracker.php')) {
      include_once 'analytics_tracker.php';
      // trackPageVisit() is automatically called in analytics_tracker.php
      // No need to call it manually here
  }
  ?>
  
  <style>
          body {
            background-color: #fafafa;
            color: #536895;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            scroll-behavior: smooth;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
            font-family: 'Poppins', 'Inter', sans-serif;
          }
          
          /* Prevent horizontal scrolling */
          html {
            overflow-x: hidden;
            width: 100%;
          }
          
          /* Ensure all containers respect viewport width */
          .max-w-6xl {
            max-width: 72rem;
            width: 100%;
            box-sizing: border-box;
          }
          
          /* Mobile-specific width constraints */
          @media (max-width: 768px) {
            body {
              width: 100vw;
              overflow-x: hidden;
            }
          }
          
          /* Modern Navigation Styles */
          .nav-link-modern {
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
          }
          
          .nav-link-modern:hover {
            background: rgba(83, 104, 149, 0.1);
            transform: translateY(-1px);
          }
          
          /* Active Navigation Styles */
          .nav-link-modern.active {
            background: rgba(83, 104, 149, 0.15);
            color: #536895;
            font-weight: 600;
          }
          
          .nav-link-modern.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background: #536895;
            border-radius: 2px;
          }
          
          /* Hero Section Styles - Clean and Simple */
          .hero-section {
            position: relative;
            width: 100vw;
            height: 100vh;
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: calc(-50vw + 50%);
          }
          
          /* Desktop: Hide mobile hero content */
          .mobile-hero-content {
            display: none;
          }
          
          /* Mobile: Hide desktop hero content and reduce height */
          @media (max-width: 768px) {
            .hero-section {
              height: 25vh !important;
              min-height: 25vh !important;
            }
            .hero-video-container {
              height: 25vh !important;
            }
            .hero-video-iframe {
              height: 25vh !important;
            }
            .desktop-hero-content {
              display: none !important;
            }
            .mobile-hero-content {
              display: block !important;
            }
          }
          
          .hero-video-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
          }
          
          .hero-video-iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            pointer-events: none;
            z-index: 0;
          }
          
          /* Mobile: Simplified hero section */
          @media (max-width: 768px) {
            .hero-section {
              height: 25vh;
              min-height: 25vh;
            }
            .hero-video-container {
              height: 25vh;
            }
            .hero-video-iframe {
              height: 25vh;
              min-height: 25vh;
              object-fit: contain;
              object-position: center;
            }
            /* Black overlay for mobile to reveal background video */
            .hero-video-container::after {
              content: '';
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background: rgba(0, 0, 0, 0.4);
              z-index: 1;
              pointer-events: none;
            }
            
            /* Mobile-only hero content */
            .mobile-hero-content {
              display: block !important;
              text-align: center;
              z-index: 10;
              position: relative;
              padding-top: 0.5rem;
            }
            
            .mobile-hero-title {
              font-size: 2.5rem;
              font-weight: 800;
              color: #F5A623;
              margin-bottom: 0.5rem;
              font-family: 'Inter', 'Poppins', sans-serif;
              letter-spacing: -0.02em;
              line-height: 1.1;
              text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            }
            
            .mobile-hero-subtitle {
              font-size: 1.1rem;
              color: #FFFFFF;
              font-weight: 600;
              font-family: 'Inter', 'Poppins', sans-serif;
              overflow: hidden;
              border-right: 2px solid #F5A623;
              white-space: nowrap;
              margin: 0 auto;
              display: inline-block;
              width: 0;
              animation: typing 2.5s steps(30, end) forwards, blink-caret 0.75s step-end infinite 2.5s;
              text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
            }
            
            @keyframes typing {
              from { 
                width: 0; 
              }
              to { 
                width: 100%; 
              }
            }
            
            @keyframes blink-caret {
              from, to { border-color: transparent; }
              50% { border-color: #F5A623; }
            }
            
            /* Mobile hero height for other pages (not homepage) */
            .page-hero-section {
              height: 40vh !important;
              min-height: 40vh !important;
            }
            
            .page-hero-container {
              height: 40vh !important;
            }
          }
          
          /* Tiny screens: Simplified hero section */
          @media (max-width: 480px) {
            .hero-section {
              height: 20vh;
              min-height: 20vh;
            }
            .hero-video-container {
              height: 20vh;
            }
            .hero-video-iframe {
              height: 20vh;
              min-height: 20vh;
              object-fit: contain;
              object-position: center;
            }
            /* Black overlay for tiny screens to reveal background video */
            .hero-video-container::after {
              content: '';
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background: rgba(0, 0, 0, 0.4);
              z-index: 1;
              pointer-events: none;
            }
            
            /* Mobile-only hero content for tiny screens */
            .mobile-hero-title {
              font-size: 2rem;
              margin-bottom: 0.4rem;
            }
            
            .mobile-hero-subtitle {
              font-size: 0.95rem;
              animation: typing 2s steps(25, end) forwards, blink-caret 0.75s step-end infinite 2s;
            }
          }
          
          .hero-video-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            display: flex;
            align-items: center;
            justify-content: center;
          }
          
          .hero-video-iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 90vh;
            object-fit: cover;
            transform: scale(1.2);
            z-index: 0;
            pointer-events: none;
            border: none;
            outline: none;
          }
          
          .hero-video-fallback {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
          }
          
          .hero-fallback-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #2D3E50 0%, #34495E 50%, #1a252f 100%);
            z-index: 0;
          }
          
          .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(45, 62, 80, 0.9) 0%, rgba(83, 104, 149, 0.85) 50%, rgba(245, 166, 35, 0.8) 100%);
            z-index: 1;
          }
          
          .hero-content {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            text-align: center;
          }
          
          .hero-content-inner {
            max-width: 800px;
            margin: 0 auto;
          }
          
          /* Hero Text Styles */
          .hero-title {
            margin-bottom: 1.5rem;
            line-height: 1.1;
          }
          
          .hero-title-main {
            display: block;
            font-size: 4rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            font-family: 'Montserrat', 'Poppins', sans-serif;
            letter-spacing: -0.02em;
          }
          
          .hero-title-accent {
            display: block;
            font-size: 2.5rem;
            font-weight: 600;
            color: #F5A623;
            font-family: 'Poppins', 'Inter', sans-serif;
            letter-spacing: -0.01em;
          }
          
          .hero-description {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
            margin-bottom: 2rem;
            font-family: 'Inter', 'Poppins', sans-serif;
            font-weight: 400;
            letter-spacing: 0.01em;
          }
          
          .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
          }
          
          .btn-primary, .btn-secondary {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
          }
          
          .btn-primary {
            background: #F5A623;
            color: white;
          }
          
          .btn-primary:hover {
            background: #d88c1b;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 166, 35, 0.4);
          }
          
          .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
          }
          
          .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
          }
          
          /* Store Hero Specific Styles */
          .store-hero .hero-content-inner {
            text-align: center;
          }
          
          .store-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 2rem;
          }
          
          .store-hero-title {
            margin-bottom: 1.5rem;
            line-height: 1.1;
          }
          
          .store-title-main {
            display: block;
            font-size: 3.5rem;
            font-weight: 700;
            color: white;
          }
          
          .store-title-accent {
            display: block;
            font-size: 2.5rem;
            font-weight: 600;
            color: #F5A623;
          }
          
          .store-hero-description {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
          }
          
          /* Floating Elements */
          .floating-element {
            position: absolute;
            border-radius: 50%;
            z-index: 2;
          }
          
          .floating-1 {
            top: 10%;
            left: 10%;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            animation: float-pulse 3s ease-in-out infinite;
          }
          
          .floating-2 {
            bottom: 20%;
            right: 20%;
            width: 64px;
            height: 64px;
            background: rgba(245, 166, 35, 0.2);
            animation: float-bounce 2s ease-in-out infinite;
          }
          
          .floating-3 {
            top: 50%;
            left: 25%;
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.05);
            animation: float-ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
          }
          
          @keyframes float-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
          }
          
          @keyframes float-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
          }
          
          @keyframes float-ping {
            75%, 100% { transform: scale(2); opacity: 0; }
          }
          
          /* Mobile Responsive */
          @media (max-width: 768px) {
            .hero-section {
              min-height: 70vh;
            }
            
            .hero-video-iframe {
              width: 100vw;
              height: 177.78vw; /* 9:16 for better mobile fit */
              min-height: 100vh;
              min-width: 56.25vh;
            }
            
            .hero-title-main {
              font-size: 2.5rem;
            }
            
            .hero-title-accent {
              font-size: 1.75rem;
            }
            
            .store-title-main {
              font-size: 2.75rem;
            }
            
            .store-title-accent {
              font-size: 2rem;
            }
            
            .hero-description, .store-hero-description {
              font-size: 1.125rem;
              padding: 0 1rem;
            }
            
            .hero-buttons {
              flex-direction: column;
              align-items: center;
            }
            
            .btn-primary, .btn-secondary {
              width: 100%;
              max-width: 280px;
              justify-content: center;
            }
          }
          
          @media (max-width: 480px) {
            .hero-section {
              min-height: 60vh;
            }
            
            .hero-title-main {
              font-size: 2rem;
            }
            
            .hero-title-accent {
              font-size: 1.5rem;
            }
            
            .store-title-main {
              font-size: 2.25rem;
            }
            
            .store-title-accent {
              font-size: 1.75rem;
            }
            
            .hero-description, .store-hero-description {
              font-size: 1rem;
            }
          }
          
          .max-w-6xl {
            width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
            box-sizing: border-box;
          }
          .main-content {
            flex: 1;
          }
          
          /* Video hero section enhancements */
          .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
          }
          
          .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
          }
          
          /* Gradient text effect */
          .gradient-text {
            background: linear-gradient(135deg, #dbdabe 0%, #536895 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
          }
          
          /* Professional modern font for hero text */
          .hero-title {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: #536895;
          }
          
          /* Mobile menu enhancements - Optimized */
          #mobile-menu {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.98);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            min-height: 100vh;
            padding-top: 80px;
          }
          
          #mobile-menu a {
            position: relative;
            transition: background-color 0.2s ease;
          }
          
          #mobile-menu a:hover {
            background-color: rgba(83, 104, 149, 0.05);
          }
          
          /* Mobile menu button animation - Optimized */
          #mobile-menu-btn span {
            transform-origin: center;
            transition: transform 0.2s ease, opacity 0.2s ease;
          }
          
          /* Smooth animations */
          .hover-scale {
            transition: transform 0.3s ease-in-out;
          }
          
          .hover-scale:hover {
            transform: scale(1.05);
          }
          
          /* Responsive hero enhancements */
          @media (max-width: 640px) {
            .hero-content h1 {
              font-size: 2rem;
              line-height: 1.2;
            }
            .hero-content p {
              font-size: 1rem;
              line-height: 1.5;
            }
          }
          
          @media (max-width: 480px) {
            .hero-content h1 {
              font-size: 1.75rem;
            }
            .hero-content p {
              font-size: 0.9rem;
            }
          }
          

          
          /* Back to top button */
          .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #536895;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(83, 104, 149, 0.3);
          }
          
          .back-to-top.show {
            opacity: 1;
            visibility: visible;
          }
          
          .back-to-top:hover {
            background: #dbdabe;
            color: #536895;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(83, 104, 149, 0.4);
          }
          
          /* Modern Desktop Navigation Styles */
          .nav-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(83, 104, 149, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            will-change: transform;
            position: sticky;
            top: 0;
            z-index: 99999;
          }
          
          /* Desktop Navigation Links - Modern Design */
          .nav-link {
            position: relative;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.025em;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            color: #374151;
            text-decoration: none;
            overflow: hidden;
            background: transparent;
          }
          
          .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(83, 104, 149, 0.1) 0%, rgba(83, 104, 149, 0.05) 100%);
            transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
          }
          
          .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, #536895 0%, #F5A623 100%);
            border-radius: 2px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
          }
          
          .nav-link:hover {
            color: #536895;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(83, 104, 149, 0.15);
          }
          
          .nav-link:hover::before {
            left: 0;
          }
          
          .nav-link:hover::after {
            width: 80%;
          }
          
          .nav-link.active {
            color: #536895;
            background: linear-gradient(135deg, rgba(83, 104, 149, 0.15) 0%, rgba(83, 104, 149, 0.1) 100%);
            box-shadow: 0 6px 20px rgba(83, 104, 149, 0.15);
            transform: translateY(-1px);
            font-weight: 700;
          }
          
          .nav-link.active::before {
            left: 0;
          }
          
          .nav-link.active::after {
            width: 100%;
            background: linear-gradient(90deg, #536895 0%, #F5A623 50%, #536895 100%);
          }

          /* Enhanced active state for modern nav links */
          .nav-link-modern.active {
            color: white !important;
            background: #536895 !important;
            box-shadow: 0 4px 15px rgba(83, 104, 149, 0.3) !important;
            font-weight: 700 !important;
            border-radius: 8px;
            padding: 0.75rem 1.25rem;
          }

          .nav-link-modern.active::before {
            display: none;
          }

          /* Dropdown styles */
          .nav-dropdown {
            position: relative;
          }

          .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 99999;
            border: 1px solid rgba(83, 104, 149, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
          }

          .nav-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
          }

          .dropdown-item {
            display: block;
            padding: 0.75rem 1.25rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0.25rem;
          }

          .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(83, 104, 149, 0.1) 0%, rgba(83, 104, 149, 0.05) 100%);
            color: #536895;
            transform: translateX(5px);
          }

          .dropdown-item.active {
            background: #536895;
            color: white;
            font-weight: 700;
          }

          /* Mobile dropdown styles */
          .mobile-dropdown {
            display: none;
            background: rgba(83, 104, 149, 0.05);
            border-radius: 8px;
            margin: 0.5rem 0;
            padding: 0.5rem;
          }

          .mobile-dropdown.active {
            display: block;
          }

          .mobile-dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            border-radius: 6px;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
          }

          .mobile-dropdown-item:hover {
            background: rgba(83, 104, 149, 0.1);
            color: #536895;
          }

          .mobile-dropdown-item.active {
            background: #536895;
            color: white;
            font-weight: 700;
          }
          
          /* Modern Login Button */
          .nav-container .login-button {
            background: linear-gradient(135deg, #536895 0%, #4a5a7a 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.025em;
            box-shadow: 0 4px 15px rgba(83, 104, 149, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
          }
          
          .nav-container .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
            transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
          }
          
          .nav-container .login-button:hover {
            background: linear-gradient(135deg, #4a5a7a 0%, #536895 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(83, 104, 149, 0.4);
          }
          
          .nav-container .login-button:hover::before {
            left: 0;
          }
          
          /* Modern Dashboard Links */
          .nav-container a[href*="dashboard"] {
            background: linear-gradient(135deg, rgba(83, 104, 149, 0.08) 0%, rgba(83, 104, 149, 0.05) 100%);
            padding: 0.625rem 1.25rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(83, 104, 149, 0.1);
          }
          
          .nav-container a[href*="dashboard"]:hover {
            background: linear-gradient(135deg, rgba(83, 104, 149, 0.15) 0%, rgba(83, 104, 149, 0.1) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(83, 104, 149, 0.15);
            border-color: rgba(83, 104, 149, 0.2);
          }
          
          /* Modern Logout Link */
          .nav-container a[href*="logout"] {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.05) 100%);
            padding: 0.625rem 1.25rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(239, 68, 68, 0.1);
            color: #ef4444;
          }
          
          .nav-container a[href*="logout"]:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.1) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.2);
            color: #dc2626;
          }
          
          /* Line clamp utility for text truncation */
          .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
          }
        </style>
        
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            const hamburgerSpans = mobileMenuBtn.querySelectorAll('span');
            
            mobileMenuBtn.addEventListener('click', function() {
              const isOpen = mobileMenu.classList.contains('mobile-menu-open');
              
              if (isOpen) {
                // Close menu
                mobileMenu.classList.remove('mobile-menu-open');
                mobileMenu.style.transform = 'translateY(-100%)';
                mobileMenu.style.opacity = '0';
                mobileMenu.style.pointerEvents = 'none';
                
                // Restore body scroll
                document.body.style.overflow = '';
                
                // Reset hamburger
                hamburgerSpans[0].style.transform = 'rotate(0deg) translateY(0)';
                hamburgerSpans[1].style.opacity = '1';
                hamburgerSpans[2].style.transform = 'rotate(0deg) translateY(0)';
              } else {
                // Open menu
                mobileMenu.classList.add('mobile-menu-open');
                mobileMenu.style.transform = 'translateY(0)';
                mobileMenu.style.opacity = '1';
                mobileMenu.style.pointerEvents = 'auto';
                mobileMenu.style.top = '0';
                
                // Prevent body scroll
                document.body.style.overflow = 'hidden';
                
                // Animate hamburger to X
                hamburgerSpans[0].style.transform = 'rotate(45deg) translateY(6px)';
                hamburgerSpans[1].style.opacity = '0';
                hamburgerSpans[2].style.transform = 'rotate(-45deg) translateY(-6px)';
              }
            });
            
            // Close menu when clicking close button
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', function() {
                    mobileMenu.classList.remove('mobile-menu-open');
                    mobileMenu.style.transform = 'translateY(-100%)';
                    mobileMenu.style.opacity = '0';
                    mobileMenu.style.pointerEvents = 'none';
                    
                    // Restore body scroll
                    document.body.style.overflow = '';
                    
                    // Reset hamburger
                    hamburgerSpans[0].style.transform = 'rotate(0deg) translateY(0)';
                    hamburgerSpans[1].style.opacity = '1';
                    hamburgerSpans[2].style.transform = 'rotate(0deg) translateY(0)';
                });
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
              if (!mobileMenuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.remove('mobile-menu-open');
                mobileMenu.style.transform = 'translateY(-100%)';
                mobileMenu.style.opacity = '0';
                mobileMenu.style.pointerEvents = 'none';
                
                // Reset hamburger
                hamburgerSpans[0].style.transform = 'rotate(0deg) translateY(0)';
                hamburgerSpans[1].style.opacity = '1';
                hamburgerSpans[2].style.transform = 'rotate(0deg) translateY(0)';
              }
            });
            
            // Close menu on window resize
            window.addEventListener('resize', function() {
              if (window.innerWidth >= 768) {
                mobileMenu.classList.remove('mobile-menu-open');
                mobileMenu.style.transform = 'translateY(-100%)';
                mobileMenu.style.opacity = '0';
                mobileMenu.style.pointerEvents = 'none';
                
                // Reset hamburger
                hamburgerSpans[0].style.transform = 'rotate(0deg) translateY(0)';
                hamburgerSpans[1].style.opacity = '1';
                hamburgerSpans[2].style.transform = 'rotate(0deg) translateY(0)';
              }
            });
            
            // Back to top functionality
            const backToTopBtn = document.createElement('div');
            backToTopBtn.className = 'back-to-top';
            backToTopBtn.innerHTML = `
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
              </svg>
            `;
            document.body.appendChild(backToTopBtn);
            
            // Show/hide back to top button
            window.addEventListener('scroll', function() {
              if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
              } else {
                backToTopBtn.classList.remove('show');
              }
            });
            
            // Smooth scroll to top
            backToTopBtn.addEventListener('click', function() {
              window.scrollTo({
                top: 0,
                behavior: 'smooth'
              });
            });
          });
        </script>
</head>
<body class="font-sans <?php 
// Add appropriate CSS class based on user type
if (function_exists('isLoggedIn') && isLoggedIn()) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        echo 'admin-logged-in';
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin') {
        echo 'superadmin-logged-in';
    } elseif (isset($_SESSION['is_support']) && $_SESSION['is_support']) {
        echo 'support-logged-in';
    } else {
        echo 'logged-in';
    }
}
?>">

<?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
<!-- Lazy Loading Screen - Only on Homepage -->
<div id="lazy-loading-screen" class="lazy-loading-screen">
  <div class="loading-spinner"></div>
  <div class="loading-counter" id="loading-counter">0%</div>
  <div class="loading-text">Welcome to ManuelCode</div>
  <div class="loading-subtext">Loading amazing experiences<span class="loading-dots"></span></div>
  <div class="loading-progress">
    <div class="loading-progress-bar" id="loading-progress-bar"></div>
  </div>
</div>

<script>
// Lazy Loading Screen Logic - Only on Homepage
document.addEventListener('DOMContentLoaded', function() {
  const loadingScreen = document.getElementById('lazy-loading-screen');
  const progressBar = document.getElementById('loading-progress-bar');
  const loadingCounter = document.getElementById('loading-counter');
  const body = document.body;
  
  // Check if this is a new tab (not a refresh)
  const isNewTab = !sessionStorage.getItem('pageVisited');
  
  if (isNewTab) {
    // Mark as visited
    sessionStorage.setItem('pageVisited', 'true');
    
    // Add loading class to body
    body.classList.add('loading');
    
    // Simulate loading progress
    let progress = 0;
    const progressInterval = setInterval(() => {
      progress += Math.random() * 15 + 5; // Random progress between 5-20%
      if (progress > 100) progress = 100;
      
      progressBar.style.width = progress + '%';
      loadingCounter.textContent = Math.round(progress) + '%';
      
      if (progress >= 100) {
        clearInterval(progressInterval);
        
        // Hide loading screen after a short delay
        setTimeout(() => {
          loadingScreen.classList.add('hidden');
          body.classList.remove('loading');
          
          // Remove from DOM after animation
          setTimeout(() => {
            if (loadingScreen.parentNode) {
              loadingScreen.parentNode.removeChild(loadingScreen);
            }
          }, 800);
        }, 500);
      }
    }, 100);
    
    // Fallback: hide loading screen after 3 seconds max
    setTimeout(() => {
      if (loadingScreen && !loadingScreen.classList.contains('hidden')) {
        loadingScreen.classList.add('hidden');
        body.classList.remove('loading');
        setTimeout(() => {
          if (loadingScreen.parentNode) {
            loadingScreen.parentNode.removeChild(loadingScreen);
          }
        }, 800);
      }
    }, 3000);
    
  } else {
    // Not a new tab, hide loading screen immediately
    if (loadingScreen) {
      loadingScreen.style.display = 'none';
    }
  }
});

// Clear session storage when user closes the tab/window
window.addEventListener('beforeunload', function() {
  sessionStorage.removeItem('pageVisited');
});
</script>
<?php endif; ?>

<!-- Desktop Navigation Only -->
<div class="hidden lg:block relative z-[99999]">
  <nav class="bg-[#f8f9fa] backdrop-blur-sm border-b border-gray-200 shadow-sm relative z-[99999]">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
      <div class="flex justify-between items-center py-3 lg:py-4 xl:py-6">
        <!-- Logo -->
        <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../index.php' : 'index.php'; ?>" class="flex items-center group flex-shrink-0">
          <img src="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../assets/favi/favicon.png' : 'assets/favi/favicon.png'; ?>" alt="ManuelCode Logo" class="h-8 sm:h-10 lg:h-12 w-auto transition-transform duration-300 group-hover:scale-105">
          <span class="ml-2 lg:ml-3 text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 group-hover:text-[#536895] transition-colors duration-300 whitespace-nowrap" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">ManuelCode</span>
        </a>
        
        <!-- Desktop Navigation Links -->
        <div class="hidden lg:flex items-center space-x-4 xl:space-x-6 flex-1 justify-center mx-4 xl:mx-8">
          <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../about.php' : 'about.php'; ?>" class="nav-link-modern text-gray-700 hover:text-[#536895] font-medium transition-all duration-300 relative text-sm xl:text-base <?php echo isCurrentPage('about.php') ? 'active' : ''; ?>">
            About
          </a>
          
          <!-- Services Dropdown -->
          <div class="nav-dropdown">
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../services.php' : 'services.php'; ?>" class="nav-link-modern text-gray-700 hover:text-[#536895] font-medium transition-all duration-300 relative flex items-center text-sm xl:text-base <?php echo (isCurrentPage('services.php') || isCurrentPage('quote_request.php')) ? 'active' : ''; ?>">
              Services
              <svg class="w-3 h-3 xl:w-4 xl:h-4 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </a>
            <div class="dropdown-menu">
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../services.php' : 'services.php'; ?>" class="dropdown-item <?php echo isCurrentPage('services.php') ? 'active' : ''; ?>">
                <i class="fas fa-cogs mr-2"></i>All Services
              </a>
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../quote_request.php' : 'quote_request.php'; ?>" class="dropdown-item <?php echo isCurrentPage('quote_request.php') ? 'active' : ''; ?>">
                <i class="fas fa-quote-left mr-2"></i>Get Quote
              </a>
            </div>
          </div>
          
          <!-- Projects Dropdown -->
          <div class="nav-dropdown">
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../projects.php' : 'projects.php'; ?>" class="nav-link-modern text-gray-700 hover:text-[#536895] font-medium transition-all duration-300 relative flex items-center text-sm xl:text-base <?php echo (isCurrentPage('projects.php') || isCurrentPage('project-detail.php')) ? 'active' : ''; ?>">
              Projects
              <svg class="w-3 h-3 xl:w-4 xl:h-4 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </a>
            <div class="dropdown-menu">
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../projects.php' : 'projects.php'; ?>" class="dropdown-item <?php echo isCurrentPage('projects.php') ? 'active' : ''; ?>">
                <i class="fas fa-folder mr-2"></i>All Projects
              </a>
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../submission.php' : 'submission.php'; ?>" class="dropdown-item <?php echo isCurrentPage('submission.php') ? 'active' : ''; ?>">
                <i class="fas fa-upload mr-2"></i>Submit Project
              </a>
            </div>
          </div>
          
          <!-- Store Dropdown -->
          <div class="nav-dropdown">
            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../store.php' : 'store.php'; ?>" class="nav-link-modern text-gray-700 hover:text-[#536895] font-medium transition-all duration-300 relative flex items-center text-sm xl:text-base <?php echo (isCurrentPage('store.php') || isCurrentPage('product.php') || isCurrentPage('guest_download.php')) ? 'active' : ''; ?>">
              Store
              <svg class="w-3 h-3 xl:w-4 xl:h-4 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </a>
            <div class="dropdown-menu">
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../store.php' : 'store.php'; ?>" class="dropdown-item <?php echo isCurrentPage('store.php') ? 'active' : ''; ?>">
                <i class="fas fa-store mr-2"></i>All Products
              </a>
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../guest_download.php' : 'guest_download.php'; ?>" class="dropdown-item <?php echo isCurrentPage('guest_download.php') ? 'active' : ''; ?>">
                <i class="fas fa-download mr-2"></i>Downloads
              </a>
            </div>
          </div>

          <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../contact.php' : 'contact.php'; ?>" class="nav-link-modern text-gray-700 hover:text-[#536895] font-medium transition-all duration-300 relative text-sm xl:text-base <?php echo isCurrentPage('contact.php') ? 'active' : ''; ?>">
            Contact
          </a>
        </div>
        
        <!-- User Actions -->
        <div class="hidden lg:flex items-center space-x-2 xl:space-x-4 flex-shrink-0">
          <?php if (isLoggedIn()): ?>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
              <a href="dashboard/" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-3 xl:px-4 py-2 rounded-lg transition-all duration-300 flex items-center shadow-sm text-xs xl:text-sm whitespace-nowrap">
                <i class="fas fa-tachometer-alt mr-1 xl:mr-2"></i>
                <span class="hidden xl:inline">Admin Dashboard</span>
                <span class="xl:hidden">Admin</span>
              </a>
            <?php else: ?>
              <a href="dashboard/" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-3 xl:px-4 py-2 rounded-lg transition-all duration-300 flex items-center shadow-sm text-xs xl:text-sm whitespace-nowrap">
                <i class="fas fa-user-circle mr-1 xl:mr-2"></i>
                Dashboard
              </a>
            <?php endif; ?>
            <a href="auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 xl:px-4 py-2 rounded-lg transition-all duration-300 flex items-center shadow-sm text-xs xl:text-sm whitespace-nowrap">
              <i class="fas fa-sign-out-alt mr-1 xl:mr-2"></i>
              <span class="hidden xl:inline">Logout</span>
              <span class="xl:hidden">Out</span>
            </a>
          <?php else: ?>
            <a href="login" class="bg-[#536895] hover:bg-[#4a5a7a] text-white px-4 xl:px-6 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center font-medium text-xs xl:text-sm whitespace-nowrap">
              <i class="fas fa-sign-in-alt mr-1 xl:mr-2"></i>
              Login
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</div>

<!-- Mobile Navigation (Keep existing for mobile) -->
<div class="lg:hidden">
  <nav class="bg-white shadow-md relative nav-container z-[9999]">
    <div class="max-w-6xl mx-auto px-4">
      <div class="flex justify-between items-center py-4">
        <!-- Logo -->
        <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../index.php' : 'index.php'; ?>" class="flex items-center group">
          <img src="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../assets/favi/favicon.png' : 'assets/favi/favicon.png'; ?>" alt="ManuelCode Logo" class="h-10 sm:h-12 md:h-14 w-auto transition-transform duration-300 group-hover:scale-105">
          <span class="ml-2 sm:ml-3 text-lg sm:text-xl font-bold text-gray-800 group-hover:text-[#536895] transition-colors duration-300" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">ManuelCode</span>
        </a>
        
        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn" class="lg:hidden flex flex-col space-y-1 p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <span class="w-6 h-0.5 bg-gray-700 transition-all duration-300"></span>
          <span class="w-6 h-0.5 bg-gray-700 transition-all duration-300"></span>
          <span class="w-6 h-0.5 bg-gray-700 transition-all duration-300"></span>
        </button>
      </div>
    </div>
    
    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-lg transform -translate-y-full opacity-0 pointer-events-none transition-all duration-300 ease-in-out z-[99999] max-h-screen overflow-y-auto">
      <div class="px-4 py-6 space-y-4">
        <!-- Close Button -->
        <div class="flex justify-end mb-4">
          <button id="mobile-menu-close" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <!-- Mobile Navigation Links -->
        <div class="space-y-2">
          <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../index.php' : 'index.php'; ?>" class="block text-gray-700 hover:text-[#536895] transition-all duration-300 py-3 px-4 rounded-lg hover:bg-gray-50 font-medium <?php echo isCurrentPage('index.php') ? 'bg-[#536895] text-white' : ''; ?>">
            Home
          </a>
          <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../about.php' : 'about.php'; ?>" class="block text-gray-700 hover:text-[#536895] transition-all duration-300 py-3 px-4 rounded-lg hover:bg-gray-50 font-medium <?php echo isCurrentPage('about.php') ? 'bg-[#536895] text-white' : ''; ?>">
            About
          </a>
          
          <!-- Mobile Services Dropdown -->
          <div>
            <button onclick="toggleMobileDropdown('services')" class="w-full flex items-center justify-between text-gray-700 hover:text-[#536895] transition-all duration-300 py-3 px-4 rounded-lg hover:bg-gray-50 font-medium <?php echo (isCurrentPage('services.php') || isCurrentPage('quote_request.php')) ? 'bg-[#536895] text-white' : ''; ?>">
              <span>Services</span>
              <svg class="w-4 h-4 transition-transform duration-200" id="services-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div id="services-dropdown" class="mobile-dropdown">
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../services.php' : 'services.php'; ?>" class="mobile-dropdown-item <?php echo isCurrentPage('services.php') ? 'active' : ''; ?>">
                All Services
              </a>
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../quote_request.php' : 'quote_request.php'; ?>" class="mobile-dropdown-item <?php echo isCurrentPage('quote_request.php') ? 'active' : ''; ?>">
                Get Quote
              </a>
            </div>
          </div>
          
          <!-- Mobile Projects Dropdown -->
          <div>
            <button onclick="toggleMobileDropdown('projects')" class="w-full flex items-center justify-between text-gray-700 hover:text-[#536895] transition-all duration-300 py-3 px-4 rounded-lg hover:bg-gray-50 font-medium <?php echo (isCurrentPage('projects.php') || isCurrentPage('project-detail.php') || isCurrentPage('submission.php')) ? 'bg-[#536895] text-white' : ''; ?>">
              <span>Projects</span>
              <svg class="w-4 h-4 transition-transform duration-200" id="projects-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div id="projects-dropdown" class="mobile-dropdown">
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../projects.php' : 'projects.php'; ?>" class="mobile-dropdown-item <?php echo isCurrentPage('projects.php') ? 'active' : ''; ?>">
                All Projects
              </a>
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../submission.php' : 'submission.php'; ?>" class="mobile-dropdown-item <?php echo isCurrentPage('submission.php') ? 'active' : ''; ?>">
                Submit Project
              </a>
            </div>
          </div>
          
          <!-- Mobile Store Dropdown -->
          <div>
            <button onclick="toggleMobileDropdown('store')" class="w-full flex items-center justify-between text-gray-700 hover:text-[#536895] transition-all duration-300 py-3 px-4 rounded-lg hover:bg-gray-50 font-medium <?php echo (isCurrentPage('store.php') || isCurrentPage('product.php') || isCurrentPage('guest_download.php')) ? 'bg-[#536895] text-white' : ''; ?>">
              <span>Store</span>
              <svg class="w-4 h-4 transition-transform duration-200" id="store-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div id="store-dropdown" class="mobile-dropdown">
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../store.php' : 'store.php'; ?>" class="mobile-dropdown-item <?php echo isCurrentPage('store.php') ? 'active' : ''; ?>">
                All Products
              </a>
              <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../guest_download.php' : 'guest_download.php'; ?>" class="mobile-dropdown-item <?php echo isCurrentPage('guest_download.php') ? 'active' : ''; ?>">
                Downloads
              </a>
            </div>
          </div>

          <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/help/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../contact.php' : 'contact.php'; ?>" class="block text-gray-700 hover:text-[#536895] transition-all duration-300 py-3 px-4 rounded-lg hover:bg-gray-50 font-medium <?php echo isCurrentPage('contact.php') ? 'bg-[#536895] text-white' : ''; ?>">
            Contact
          </a>
        </div>
        
        <!-- Mobile User Actions -->
        <div class="pt-4 border-t border-gray-200 space-y-2">
          <?php if (isLoggedIn()): ?>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
              <a href="dashboard/" class="block text-gray-700 hover:text-[#536895] transition-colors py-2 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Admin Dashboard
              </a>
            <?php else: ?>
              <a href="dashboard/" class="block text-gray-700 hover:text-[#536895] transition-colors py-2 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Dashboard
              </a>
            <?php endif; ?>
            <a href="auth/logout.php" class="block text-gray-700 hover:text-[#536895] transition-colors py-2 flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              Logout
            </a>
          <?php else: ?>
            <a href="auth/login.php" class="block text-gray-700 hover:text-[#536895] transition-colors py-2 flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
              </svg>
              Login
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</div>

<script>
// Mobile Navigation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    
    if (mobileMenuBtn && mobileMenu && mobileMenuClose) {
        // Open mobile menu
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('-translate-y-full', 'opacity-0', 'pointer-events-none');
            mobileMenu.classList.add('translate-y-0', 'opacity-100');
            document.body.style.overflow = 'hidden';
        });
        
        // Close mobile menu
        mobileMenuClose.addEventListener('click', function() {
            mobileMenu.classList.add('-translate-y-full', 'opacity-0', 'pointer-events-none');
            mobileMenu.classList.remove('translate-y-0', 'opacity-100');
            document.body.style.overflow = '';
        });
        
        // Close mobile menu when clicking outside
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu) {
                mobileMenuClose.click();
            }
        });
        
        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !mobileMenu.classList.contains('pointer-events-none')) {
                mobileMenuClose.click();
            }
        });
    }
});

// Mobile dropdown toggle function
function toggleMobileDropdown(dropdownName) {
    const dropdown = document.getElementById(dropdownName + '-dropdown');
    const arrow = document.getElementById(dropdownName + '-arrow');
    
    if (dropdown && arrow) {
        const isActive = dropdown.classList.contains('active');
        
        // Close all other dropdowns
        document.querySelectorAll('.mobile-dropdown').forEach(dd => {
            dd.classList.remove('active');
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(arr => {
            arr.style.transform = 'rotate(0deg)';
        });
        
        // Toggle current dropdown
        if (!isActive) {
            dropdown.classList.add('active');
            arrow.style.transform = 'rotate(180deg)';
        }
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.mobile-dropdown') && !e.target.closest('button[onclick*="toggleMobileDropdown"]')) {
        document.querySelectorAll('.mobile-dropdown').forEach(dd => {
            dd.classList.remove('active');
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(arr => {
            arr.style.transform = 'rotate(0deg)';
        });
    }
});
</script>
