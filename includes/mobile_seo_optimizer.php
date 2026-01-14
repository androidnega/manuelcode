<?php
/**
 * Mobile SEO Optimizer
 * Mobile-first indexing optimization for ManuelCode
 */

// Prevent function redeclaration
if (!function_exists('generateMobileOptimizedMeta')) {

/**
 * Generate mobile-optimized meta tags
 * @param array $meta_data Page meta data
 * @return string Mobile-optimized meta tags
 */
function generateMobileOptimizedMeta($meta_data) {
    $html = '<!-- Mobile-First SEO Optimization -->';
    
    // Mobile viewport optimization
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">';
    
    // Mobile app meta
    $html .= '<meta name="mobile-web-app-capable" content="yes">';
    $html .= '<meta name="apple-mobile-web-app-capable" content="yes">';
    $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
    $html .= '<meta name="apple-mobile-web-app-title" content="ManuelCode">';
    $html .= '<meta name="application-name" content="ManuelCode">';
    
    // Touch icons for mobile
    $html .= '<link rel="apple-touch-icon" sizes="180x180" href="assets/favi/apple-touch-icon.png">';
    $html .= '<link rel="icon" type="image/png" sizes="32x32" href="assets/favi/favicon-32x32.png">';
    $html .= '<link rel="icon" type="image/png" sizes="16x16" href="assets/favi/favicon-16x16.png">';
    $html .= '<link rel="manifest" href="site.webmanifest">';
    
    // Theme colors
    $html .= '<meta name="theme-color" content="#536895">';
    $html .= '<meta name="msapplication-TileColor" content="#536895">';
    $html .= '<meta name="msapplication-config" content="browserconfig.xml">';
    
    // Mobile performance hints
    $html .= '<meta name="format-detection" content="telephone=no">';
    $html .= '<meta name="format-detection" content="date=no">';
    $html .= '<meta name="format-detection" content="address=no">';
    $html .= '<meta name="format-detection" content="email=no">';
    
    return $html;
}

/**
 * Generate mobile-optimized structured data
 * @param array $business_data Business information
 * @return string Mobile-optimized structured data
 */
function generateMobileStructuredData($business_data) {
    $structured_data = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => 'ManuelCode',
        'description' => 'Professional Software Engineer specializing in full-stack development',
        'url' => 'https://manuelcode.info',
        'telephone' => $business_data['phone'] ?? '',
        'email' => $business_data['email'] ?? 'contact@manuelcode.info',
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'GH',
            'addressRegion' => 'Ghana'
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $business_data['latitude'] ?? '',
            'longitude' => $business_data['longitude'] ?? ''
        ],
        'openingHours' => 'Mo-Fr 09:00-18:00',
        'priceRange' => '$$',
        'paymentAccepted' => 'Cash, Credit Card, Mobile Money',
        'currenciesAccepted' => 'GHS, USD',
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'Ghana'
        ],
        'serviceArea' => [
            '@type' => 'GeoCircle',
            'geoMidpoint' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $business_data['latitude'] ?? '',
                'longitude' => $business_data['longitude'] ?? ''
            ],
            'geoRadius' => '50000'
        ],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Software Development Services',
            'itemListElement' => [
                [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => 'Web Development',
                        'description' => 'Custom web applications and websites'
                    ]
                ],
                [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => 'Mobile App Development',
                        'description' => 'iOS and Android mobile applications'
                    ]
                ],
                [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => 'Database Design',
                        'description' => 'Custom database solutions and optimization'
                    ]
                ]
            ]
        ],
        'sameAs' => [
            'https://github.com/manuelcode',
            'https://linkedin.com/in/manuelcode',
            'https://twitter.com/manuelcode'
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Generate mobile-optimized CSS
 * @return string Mobile-optimized CSS
 */
function generateMobileOptimizedCSS() {
    return '
    <style>
    /* Mobile-First Responsive Design */
    @media (max-width: 768px) {
        /* Optimize touch targets */
        .nav-link, .btn, .mobile-menu-item {
            min-height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Improve readability */
        body {
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Optimize images for mobile */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Improve form usability */
        input, textarea, select {
            font-size: 16px;
            padding: 12px;
            border-radius: 8px;
        }
        
        /* Optimize navigation */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            z-index: 99999;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.open {
            transform: translateY(0);
        }
        
        /* Improve button spacing */
        .btn {
            margin: 8px 0;
            padding: 12px 24px;
        }
        
        /* Optimize content spacing */
        .container, .max-w-6xl {
            padding: 0 16px;
        }
        
        /* Improve hero section for mobile */
        .hero-section {
            min-height: 50vh;
            padding: 2rem 1rem;
        }
        
        .hero-title {
            font-size: 2rem;
            line-height: 1.2;
        }
        
        .hero-description {
            font-size: 1.1rem;
            line-height: 1.5;
        }
    }
    
    /* Touch device optimizations */
    @media (hover: none) and (pointer: coarse) {
        .hover\\:scale-105:hover {
            transform: none;
        }
        
        .hover\\:bg-gray-100:hover {
            background-color: transparent;
        }
        
        /* Add touch feedback */
        .btn:active, .nav-link:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
    }
    
    /* High DPI display optimizations */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .logo, .hero-image {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        :root {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .nav-container {
            background: rgba(26, 26, 26, 0.98);
            border-bottom-color: var(--border-color);
        }
    }
    
    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
    </style>';
}

/**
 * Generate mobile performance optimization script
 * @return string Mobile performance optimization JavaScript
 */
function generateMobilePerformanceScript() {
    return '
    <script>
    // Mobile Performance Optimization
    (function() {
        "use strict";
        
        // Detect mobile device
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isTouchDevice = "ontouchstart" in window || navigator.maxTouchPoints > 0;
        
        if (isMobile || isTouchDevice) {
            document.documentElement.classList.add("mobile-device");
        }
        
        // Optimize images for mobile
        function optimizeImagesForMobile() {
            const images = document.querySelectorAll("img");
            images.forEach(img => {
                // Add loading="lazy" if not present
                if (!img.hasAttribute("loading")) {
                    img.setAttribute("loading", "lazy");
                }
                
                // Add decoding="async" if not present
                if (!img.hasAttribute("decoding")) {
                    img.setAttribute("decoding", "async");
                }
                
                // Optimize for high DPI displays
                if (window.devicePixelRatio > 1) {
                    const src = img.src;
                    if (src && !src.includes("?")) {
                        img.src = src + "?w=" + (img.offsetWidth * window.devicePixelRatio);
                    }
                }
            });
        }
        
        // Optimize forms for mobile
        function optimizeFormsForMobile() {
            const inputs = document.querySelectorAll("input, textarea, select");
            inputs.forEach(input => {
                // Prevent zoom on focus (iOS)
                if (input.type === "text" || input.type === "email" || input.type === "tel" || input.type === "url") {
                    input.style.fontSize = "16px";
                }
                
                // Add touch-friendly styling
                input.style.minHeight = "44px";
                input.style.padding = "12px";
            });
        }
        
        // Optimize navigation for mobile
        function optimizeNavigationForMobile() {
            const menuToggle = document.getElementById("mobile-menu-btn");
            const mobileMenu = document.getElementById("mobile-menu");
            
            if (menuToggle && mobileMenu) {
                menuToggle.addEventListener("click", function() {
                    mobileMenu.classList.toggle("open");
                    document.body.style.overflow = mobileMenu.classList.contains("open") ? "hidden" : "";
                });
                
                // Close menu when clicking outside
                document.addEventListener("click", function(e) {
                    if (!menuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                        mobileMenu.classList.remove("open");
                        document.body.style.overflow = "";
                    }
                });
            }
        }
        
        // Optimize scrolling for mobile
        function optimizeScrollingForMobile() {
            // Smooth scrolling for anchor links
            document.querySelectorAll("a[href^=\'#\']").forEach(anchor => {
                anchor.addEventListener("click", function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute("href"));
                    if (target) {
                        target.scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });
                    }
                });
            });
            
            // Add momentum scrolling for iOS
            document.body.style.webkitOverflowScrolling = "touch";
        }
        
        // Initialize mobile optimizations
        document.addEventListener("DOMContentLoaded", function() {
            optimizeImagesForMobile();
            optimizeFormsForMobile();
            optimizeNavigationForMobile();
            optimizeScrollingForMobile();
        });
        
        // Handle orientation change
        window.addEventListener("orientationchange", function() {
            setTimeout(function() {
                // Recalculate layouts after orientation change
                window.dispatchEvent(new Event("resize"));
            }, 100);
        });
        
    })();
    </script>';
}

/**
 * Generate mobile SEO meta tags
 * @param string $page_type Type of page
 * @return string Mobile SEO meta tags
 */
function generateMobileSEOMeta($page_type = 'page') {
    $meta_tags = [
        'homepage' => [
            'title' => 'ManuelCode | Professional Software Engineer & Full-Stack Developer',
            'description' => 'Professional Software Engineer specializing in full-stack development, custom web applications, and mobile solutions. Expert in PHP, JavaScript, React, Node.js. Based in Ghana.',
            'keywords' => 'software engineer Ghana, full-stack developer, web development, mobile apps, PHP developer, JavaScript developer, React developer, Node.js developer, Ghana software company'
        ],
        'services' => [
            'title' => 'Software Development Services | ManuelCode',
            'description' => 'Professional software development services including web applications, mobile apps, database design, and custom software solutions. Expert development team in Ghana.',
            'keywords' => 'software development services, web development Ghana, mobile app development, custom software, database design, software consulting'
        ],
        'contact' => [
            'title' => 'Contact ManuelCode | Software Engineer',
            'description' => 'Get in touch with ManuelCode for professional software development services. Expert in web development, mobile apps, and custom software solutions in Ghana.',
            'keywords' => 'contact software engineer, hire developer Ghana, software development consultation, web development services'
        ]
    ];
    
    $page_meta = $meta_tags[$page_type] ?? $meta_tags['homepage'];
    
    $html = '<!-- Mobile SEO Meta Tags -->' . "\n";
    $html .= '<title>' . htmlspecialchars($page_meta['title']) . '</title>' . "\n";
    $html .= '<meta name="description" content="' . htmlspecialchars($page_meta['description']) . '">' . "\n";
    $html .= '<meta name="keywords" content="' . htmlspecialchars($page_meta['keywords']) . '">';
    
    return $html;
}

} // End function_exists check
?>
