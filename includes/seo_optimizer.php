<?php
/**
 * SEO Optimizer
 * Comprehensive SEO optimization functions for ManuelCode
 */

// Prevent function redeclaration
if (!function_exists('optimizeImagesForSEO')) {

/**
 * Generate optimized image markup for SEO
 * @param string $src Image source
 * @param string $alt Alt text
 * @param string $title Image title
 * @param array $sizes Responsive sizes
 * @return string Optimized image HTML
 */
function optimizeImagesForSEO($src, $alt, $title = '', $sizes = []) {
    $base_url = 'https://manuelcode.info';
    $full_src = $base_url . '/' . ltrim($src, '/');
    
    // Generate srcset for responsive images
    $srcset = '';
    if (!empty($sizes)) {
        $srcset_parts = [];
        foreach ($sizes as $size) {
            $srcset_parts[] = $full_src . '?w=' . $size . ' ' . $size . 'w';
        }
        $srcset = ' srcset="' . implode(', ', $srcset_parts) . '"';
    }
    
    $title_attr = $title ? ' title="' . htmlspecialchars($title) . '"' : '';
    
    return '<img src="' . htmlspecialchars($full_src) . '"' . 
           ' alt="' . htmlspecialchars($alt) . '"' . 
           $title_attr . 
           $srcset . 
           ' loading="lazy"' . 
           ' decoding="async"' . 
           ' width="auto"' . 
           ' height="auto"' . 
           ' class="seo-optimized-image">';
}

/**
 * Generate breadcrumb navigation for SEO
 * @param array $breadcrumbs Array of breadcrumb items
 * @return string Breadcrumb HTML with structured data
 */
function generateBreadcrumbs($breadcrumbs) {
    if (empty($breadcrumbs)) return '';
    
    $html = '<nav aria-label="Breadcrumb" class="breadcrumb-nav">';
    $html .= '<ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $position = $index + 1;
        $html .= '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if ($index === count($breadcrumbs) - 1) {
            // Last item (current page)
            $html .= '<span itemprop="name">' . htmlspecialchars($crumb['name']) . '</span>';
        } else {
            // Link item
            $html .= '<a href="' . htmlspecialchars($crumb['url']) . '" itemprop="item">';
            $html .= '<span itemprop="name">' . htmlspecialchars($crumb['name']) . '</span>';
            $html .= '</a>';
        }
        
        $html .= '<meta itemprop="position" content="' . $position . '">';
        $html .= '</li>';
        
        if ($index < count($breadcrumbs) - 1) {
            $html .= '<li class="breadcrumb-separator" aria-hidden="true">›</li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Generate FAQ structured data
 * @param array $faqs Array of FAQ items
 * @return string JSON-LD structured data
 */
function generateFAQStructuredData($faqs) {
    if (empty($faqs)) return '';
    
    $structured_data = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];
    
    foreach ($faqs as $faq) {
        $structured_data['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Generate service structured data
 * @param array $services Array of service items
 * @return string JSON-LD structured data
 */
function generateServiceStructuredData($services) {
    if (empty($services)) return '';
    
    $structured_data = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'provider' => [
            '@type' => 'Person',
            'name' => 'ManuelCode',
            'url' => 'https://manuelcode.info'
        ],
        'serviceType' => 'Software Development',
        'areaServed' => 'Ghana',
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Software Development Services',
            'itemListElement' => []
        ]
    ];
    
    foreach ($services as $index => $service) {
        $structured_data['hasOfferCatalog']['itemListElement'][] = [
            '@type' => 'Offer',
            'itemOffered' => [
                '@type' => 'Service',
                'name' => $service['name'],
                'description' => $service['description']
            ],
            'position' => $index + 1
        ];
    }
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Generate internal linking suggestions
 * @param string $content Page content
 * @param array $target_pages Available pages to link to
 * @return string Enhanced content with internal links
 */
function enhanceInternalLinking($content, $target_pages) {
    foreach ($target_pages as $page) {
        $keywords = explode(',', $page['keywords']);
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (strlen($keyword) > 3 && stripos($content, $keyword) !== false) {
                $link = '<a href="' . htmlspecialchars($page['url']) . '" title="' . htmlspecialchars($page['title']) . '" class="internal-link">' . $keyword . '</a>';
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link, $content, 1);
            }
        }
    }
    return $content;
}

/**
 * Generate meta robots based on page type
 * @param string $page_type Type of page
 * @return string Meta robots content
 */
function getMetaRobots($page_type = 'page') {
    $robots_rules = [
        'homepage' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'service' => 'index, follow, max-image-preview:large, max-snippet:-1',
        'product' => 'index, follow, max-image-preview:large, max-snippet:-1',
        'project' => 'index, follow, max-image-preview:large, max-snippet:-1',
        'contact' => 'index, follow, noarchive',
        'privacy' => 'index, follow, noarchive',
        'terms' => 'index, follow, noarchive',
        'admin' => 'noindex, nofollow, noarchive, nosnippet',
        'default' => 'index, follow, max-image-preview:large, max-snippet:-1'
    ];
    
    return $robots_rules[$page_type] ?? $robots_rules['default'];
}

/**
 * Generate social sharing meta tags
 * @param array $meta_data Page meta data
 * @return string Social sharing meta tags
 */
function generateSocialSharingMeta($meta_data) {
    $base_url = 'https://manuelcode.info';
    $og_image = $base_url . '/' . ltrim($meta_data['og_image'] ?? 'assets/images/manuelcode-og-image.jpg', '/');
    
    $html = '<!-- Enhanced Social Sharing Meta -->';
    $html .= '<meta property="og:title" content="' . htmlspecialchars($meta_data['title']) . '">';
    $html .= '<meta property="og:description" content="' . htmlspecialchars($meta_data['description']) . '">';
    $html .= '<meta property="og:image" content="' . htmlspecialchars($og_image) . '">';
    $html .= '<meta property="og:image:width" content="1200">';
    $html .= '<meta property="og:image:height" content="630">';
    $html .= '<meta property="og:url" content="' . htmlspecialchars($meta_data['canonical']) . '">';
    $html .= '<meta property="og:type" content="' . htmlspecialchars($meta_data['og_type'] ?? 'website') . '">';
    $html .= '<meta property="og:site_name" content="ManuelCode">';
    $html .= '<meta property="og:locale" content="en_US">';
    
    // Twitter Card meta
    $html .= '<meta name="twitter:card" content="summary_large_image">';
    $html .= '<meta name="twitter:site" content="@manuelcode">';
    $html .= '<meta name="twitter:creator" content="@manuelcode">';
    $html .= '<meta name="twitter:title" content="' . htmlspecialchars($meta_data['title']) . '">';
    $html .= '<meta name="twitter:description" content="' . htmlspecialchars($meta_data['description']) . '">';
    $html .= '<meta name="twitter:image" content="' . htmlspecialchars($og_image) . '">';
    
    return $html;
}

/**
 * Generate performance optimization hints
 * @return string Performance optimization meta tags
 */
function generatePerformanceHints() {
    $html = '<!-- Performance Optimization Hints -->';
    $html .= '<meta http-equiv="X-DNS-Prefetch-Control" content="on">';
    $html .= '<meta name="format-detection" content="telephone=no">';
    $html .= '<meta name="mobile-web-app-capable" content="yes">';
    $html .= '<meta name="apple-mobile-web-app-capable" content="yes">';
    $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
    $html .= '<meta name="apple-mobile-web-app-title" content="ManuelCode">';
    
    return $html;
}

/**
 * Generate Core Web Vitals optimization
 * @return string Core Web Vitals optimization code
 */
function generateCoreWebVitalsOptimization() {
    return '
    <script>
    // Core Web Vitals optimization
    if ("connection" in navigator) {
        // Adjust loading strategy based on connection
        if (navigator.connection.effectiveType === "slow-2g" || navigator.connection.effectiveType === "2g") {
            // Reduce image quality for slow connections
            document.documentElement.classList.add("slow-connection");
        }
    }
    
    // Lazy load images
    if ("IntersectionObserver" in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove("lazy");
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll("img[data-src]").forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Preload critical resources
    const criticalResources = [
        "assets/css/style.css",
        "assets/favi/favicon.png"
    ];
    
    criticalResources.forEach(resource => {
        const link = document.createElement("link");
        link.rel = "preload";
        link.href = resource;
        link.as = resource.endsWith(".css") ? "style" : "image";
        document.head.appendChild(link);
    });
    </script>';
}

} // End function_exists check
?>
