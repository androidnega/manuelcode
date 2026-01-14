<?php
/**
 * Dynamic Meta Tags Helper
 * Provides functionality for setting page-specific Open Graph and SEO meta tags
 */

// Prevent function redeclaration
if (!function_exists('setPageMeta')) {

/**
 * Set page-specific meta data
 * @param array $meta_data Array containing page meta data
 * @return array Processed meta data with defaults
 */
function setPageMeta($meta_data = []) {
    global $page_meta;
    
    // Default values
    $defaults = [
        'title' => 'ManuelCode | Professional Software Engineer & Full-Stack Developer',
        'description' => 'Professional Software Engineer specializing in full-stack development, custom web applications, mobile solutions, and innovative software architecture. Expert in PHP, JavaScript, React, Node.js, and database design. Turning complex problems into elegant code.',
        'keywords' => 'software engineer, full-stack developer, web development, mobile apps, custom software, PHP, JavaScript, React, Node.js, database design, software architecture, web applications, Ghana developer, freelance developer, software solutions',
        'author' => 'ManuelCode',
        'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'canonical' => null, // Will be set automatically
        'og_title' => null, // Will use page title if not set
        'og_description' => null, // Will use page description if not set
        'og_image' => 'https://manuelcode.info/assets/favi/favicon.png', // Enhanced OG image
        'og_image_width' => '512',
        'og_image_height' => '512',
        'og_type' => 'website',
        'og_url' => null, // Will be set automatically
        'og_site_name' => 'ManuelCode',
        'og_locale' => 'en_US',
        'twitter_card' => 'summary_large_image',
        'twitter_site' => '@manuelcode',
        'twitter_creator' => '@manuelcode',
        'twitter_title' => null, // Will use page title if not set
        'twitter_description' => null, // Will use page description if not set
        'twitter_image' => null, // Will use og_image if not set
        'custom_thumbnail' => null, // Page-specific thumbnail
        'schema_type' => 'Person', // Schema.org type
        'schema_name' => 'ManuelCode',
        'schema_job_title' => 'Professional Software Engineer',
        'schema_description' => 'Full-stack developer specializing in web applications and software architecture',
        'schema_url' => 'https://manuelcode.info',
        'schema_image' => 'https://manuelcode.info/assets/images/manuelcode-profile.jpg',
        'schema_same_as' => [
            'https://github.com/manuelcode',
            'https://linkedin.com/in/manuelcode',
            'https://twitter.com/manuelcode'
        ]
    ];
    
    // Merge with defaults
    $page_meta = array_merge($defaults, $meta_data);
    
    // Use page title for OG and Twitter if not specifically set
    if (empty($page_meta['og_title'])) {
        $page_meta['og_title'] = $page_meta['title'];
    }
    if (empty($page_meta['og_description'])) {
        $page_meta['og_description'] = $page_meta['description'];
    }
    if (empty($page_meta['twitter_title'])) {
        $page_meta['twitter_title'] = $page_meta['title'];
    }
    if (empty($page_meta['twitter_description'])) {
        $page_meta['twitter_description'] = $page_meta['description'];
    }
    if (empty($page_meta['twitter_image'])) {
        $page_meta['twitter_image'] = $page_meta['og_image'];
    }
    
    // Set current URL if not provided
    if (empty($page_meta['og_url'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
        $page_meta['og_url'] = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
    }
    
    // Set canonical URL if not provided
    if (empty($page_meta['canonical'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'manuelcode.info';
        $page_meta['canonical'] = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
    }
    
    // Override with custom thumbnail if provided
    if (!empty($page_meta['custom_thumbnail'])) {
        $page_meta['og_image'] = $page_meta['custom_thumbnail'];
        $page_meta['twitter_image'] = $page_meta['custom_thumbnail'];
    }
    
    return $page_meta;
}

/**
 * Get the current page meta data
 * @return array Page meta data
 */
function getPageMeta() {
    global $page_meta;
    return $page_meta ?? [];
}

/**
 * Render Open Graph and meta tags
 * @param array $meta_data Optional meta data to set before rendering
 */
function renderMetaTags($meta_data = []) {
    global $page_meta;
    
    // If no meta data has been set yet, return early to avoid warnings
    if (empty($page_meta) && empty($meta_data)) {
        return;
    }
    
    if (!empty($meta_data)) {
        setPageMeta($meta_data);
    }
    
    $meta = getPageMeta();
    
    // If still no meta data, set defaults
    if (empty($meta)) {
        $meta = [
            'title' => 'ManuelCode | Professional Software Engineer',
            'description' => 'Professional Software Engineer specializing in full-stack development',
            'keywords' => 'software engineer, full-stack developer, web development',
            'author' => 'ManuelCode',
            'robots' => 'index, follow',
            'og_title' => 'ManuelCode | Professional Software Engineer',
            'og_description' => 'Professional Software Engineer specializing in full-stack development',
            'og_image' => 'assets/favi/favicon.png',
            'og_url' => '',
            'og_type' => 'website',
            'og_site_name' => 'ManuelCode',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'ManuelCode | Professional Software Engineer',
            'twitter_description' => 'Professional Software Engineer specializing in full-stack development',
            'twitter_image' => 'assets/favi/favicon.png'
        ];
    } else {
        // Ensure all required keys exist with fallbacks
        $meta = array_merge([
            'title' => 'ManuelCode | Professional Software Engineer',
            'description' => 'Professional Software Engineer specializing in full-stack development',
            'keywords' => 'software engineer, full-stack developer, web development',
            'author' => 'ManuelCode',
            'robots' => 'index, follow',
            'og_title' => $meta['title'] ?? 'ManuelCode | Professional Software Engineer',
            'og_description' => $meta['description'] ?? 'Professional Software Engineer specializing in full-stack development',
            'og_image' => $meta['og_image'] ?? 'assets/favi/favicon.png',
            'og_url' => $meta['og_url'] ?? '',
            'og_type' => $meta['og_type'] ?? 'website',
            'og_site_name' => $meta['og_site_name'] ?? 'ManuelCode',
            'twitter_card' => $meta['twitter_card'] ?? 'summary_large_image',
            'twitter_title' => $meta['twitter_title'] ?? $meta['title'] ?? 'ManuelCode | Professional Software Engineer',
            'twitter_description' => $meta['twitter_description'] ?? $meta['description'] ?? 'Professional Software Engineer specializing in full-stack development',
            'twitter_image' => $meta['twitter_image'] ?? $meta['og_image'] ?? 'assets/favi/favicon.png'
        ], $meta);
    }
    
    // Build full URLs for images
    $base_url = 'https://manuelcode.info';
    $og_image_url = $base_url . '/' . ltrim($meta['og_image'], '/');
    $twitter_image_url = $base_url . '/' . ltrim($meta['twitter_image'], '/');
    
    ?>
    <!-- Enhanced SEO Meta Tags -->
    <title><?php echo htmlspecialchars($meta['title']); ?></title>
    <!-- Cache buster: <?php echo time(); ?> -->
    <meta name="description" content="<?php echo htmlspecialchars($meta['description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta['keywords']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($meta['author']); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($meta['robots']); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($meta['canonical']); ?>">
    
    <!-- Enhanced Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($meta['og_title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta['og_description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <meta property="og:image:width" content="<?php echo htmlspecialchars($meta['og_image_width']); ?>">
    <meta property="og:image:height" content="<?php echo htmlspecialchars($meta['og_image_height']); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($meta['og_url']); ?>">
    <meta property="og:type" content="<?php echo htmlspecialchars($meta['og_type']); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($meta['og_site_name']); ?>">
    <meta property="og:locale" content="<?php echo htmlspecialchars($meta['og_locale']); ?>">
    
    <!-- Enhanced Twitter Card Tags -->
    <meta name="twitter:card" content="<?php echo htmlspecialchars($meta['twitter_card']); ?>">
    <meta name="twitter:site" content="<?php echo htmlspecialchars($meta['twitter_site']); ?>">
    <meta name="twitter:creator" content="<?php echo htmlspecialchars($meta['twitter_creator']); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta['twitter_title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta['twitter_description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($twitter_image_url); ?>">
    
    <!-- WhatsApp & Social Media Specific Tags -->
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="ManuelCode Logo">
    <meta name="format-detection" content="telephone=no">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="theme-color" content="#536895">
    <meta name="msapplication-TileColor" content="#536895">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ManuelCode">
    <meta name="application-name" content="ManuelCode">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    
    <!-- Language and Geographic Meta -->
    <meta name="language" content="en">
    <meta name="geo.region" content="GH">
    <meta name="geo.country" content="Ghana">
    <meta name="geo.placename" content="Ghana">
    
    <!-- Performance and Security Meta -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">
    <meta name="revisit-after" content="7 days">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "<?php echo htmlspecialchars($meta['schema_type']); ?>",
        "name": "<?php echo htmlspecialchars($meta['schema_name']); ?>",
        "jobTitle": "<?php echo htmlspecialchars($meta['schema_job_title']); ?>",
        "description": "<?php echo htmlspecialchars($meta['schema_description']); ?>",
        "url": "<?php echo htmlspecialchars($meta['schema_url']); ?>",
        "image": "<?php echo htmlspecialchars($meta['schema_image']); ?>",
        "sameAs": <?php echo json_encode($meta['schema_same_as']); ?>,
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "GH",
            "addressRegion": "Ghana"
        },
        "knowsAbout": [
            "Software Engineering",
            "Full-Stack Development",
            "Web Development",
            "Mobile App Development",
            "Database Design",
            "PHP",
            "JavaScript",
            "React",
            "Node.js"
        ],
        "offers": {
            "@type": "Service",
            "name": "Software Development Services",
            "description": "Professional software development and web application services"
        }
    }
    </script>
    <?php
}

/**
 * Quick meta tag setter for common pages
 * @param string $title Page title
 * @param string $description Page description
 * @param string $custom_thumbnail Optional custom thumbnail
 * @param string $keywords Optional keywords
 */
function setQuickMeta($title, $description, $custom_thumbnail = null, $keywords = '') {
    $meta_data = [
        'title' => $title,
        'description' => $description
    ];
    
    if ($custom_thumbnail) {
        $meta_data['custom_thumbnail'] = $custom_thumbnail;
    }
    
    if ($keywords) {
        $meta_data['keywords'] = $keywords;
    }
    
    setPageMeta($meta_data);
}

}

?>
