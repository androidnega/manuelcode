<?php
/**
 * Content SEO Optimizer
 * Optimizes content for search engines and user experience
 */

// Prevent function redeclaration
if (!function_exists('optimizeContentForSEO')) {

/**
 * Optimize content for SEO
 * @param string $content Raw content
 * @param array $keywords Target keywords
 * @param string $page_type Type of page
 * @return string Optimized content
 */
function optimizeContentForSEO($content, $keywords = [], $page_type = 'page') {
    // Add semantic HTML structure
    $content = addSemanticHTMLStructure($content);
    
    // Optimize headings hierarchy
    $content = optimizeHeadingsHierarchy($content);
    
    // Add keyword optimization
    if (!empty($keywords)) {
        $content = optimizeKeywordsInContent($content, $keywords);
    }
    
    // Add internal linking opportunities
    $content = addInternalLinkingOpportunities($content, $page_type);
    
    // Optimize images with alt text
    $content = optimizeImagesInContent($content);
    
    // Add schema markup opportunities
    $content = addSchemaMarkupOpportunities($content, $page_type);
    
    return $content;
}

/**
 * Add semantic HTML structure to content
 * @param string $content Raw content
 * @return string Content with semantic HTML
 */
function addSemanticHTMLStructure($content) {
    // Wrap content in semantic sections
    $content = '<main class="main-content" role="main">' . $content . '</main>';
    
    // Add article structure for blog-like content
    if (strpos($content, '<h1>') !== false || strpos($content, '<h2>') !== false) {
        $content = str_replace('<main class="main-content" role="main">', '<main class="main-content" role="main"><article class="content-article">', $content);
        $content = str_replace('</main>', '</article></main>', $content);
    }
    
    return $content;
}

/**
 * Optimize headings hierarchy for SEO
 * @param string $content Content with headings
 * @return string Content with optimized headings
 */
function optimizeHeadingsHierarchy($content) {
    // Ensure proper heading hierarchy (H1 -> H2 -> H3, etc.)
    $heading_levels = [];
    $content = preg_replace_callback('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', function($matches) use (&$heading_levels) {
        $level = intval($matches[1]);
        $text = $matches[2];
        
        // Track heading levels
        $heading_levels[] = $level;
        
        // Ensure proper hierarchy
        if (count($heading_levels) > 1) {
            $prev_level = $heading_levels[count($heading_levels) - 2];
            if ($level > $prev_level + 1) {
                $level = $prev_level + 1;
            }
        }
        
        // Add SEO-friendly attributes
        $id = sanitizeHeadingId($text);
        return '<h' . $level . ' id="' . $id . '" class="seo-heading">' . $text . '</h' . $level . '>';
    }, $content);
    
    return $content;
}

/**
 * Sanitize heading text for ID generation
 * @param string $text Heading text
 * @return string Sanitized ID
 */
function sanitizeHeadingId($text) {
    $id = strip_tags($text);
    $id = strtolower($id);
    $id = preg_replace('/[^a-z0-9\s-]/', '', $id);
    $id = preg_replace('/\s+/', '-', $id);
    $id = trim($id, '-');
    return $id;
}

/**
 * Optimize keywords in content
 * @param string $content Content to optimize
 * @param array $keywords Target keywords
 * @return string Content with optimized keywords
 */
function optimizeKeywordsInContent($content, $keywords) {
    $keyword_density = [];
    
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if (strlen($keyword) < 3) continue;
        
        // Count current keyword density
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $word_count = str_word_count(strip_tags($content));
        $density = ($keyword_count / $word_count) * 100;
        
        $keyword_density[$keyword] = $density;
        
        // Add keyword variations if density is low
        if ($density < 1.0 && $keyword_count < 3) {
            $content = addKeywordVariations($content, $keyword);
        }
    }
    
    return $content;
}

/**
 * Add keyword variations to content
 * @param string $content Content
 * @param string $keyword Target keyword
 * @return string Content with keyword variations
 */
function addKeywordVariations($content, $keyword) {
    $variations = generateKeywordVariations($keyword);
    
    foreach ($variations as $variation) {
        if (stripos($content, $variation) === false) {
            // Add variation in a natural way
            $content = addNaturalKeywordVariation($content, $variation);
        }
    }
    
    return $content;
}

/**
 * Generate keyword variations
 * @param string $keyword Base keyword
 * @return array Keyword variations
 */
function generateKeywordVariations($keyword) {
    $variations = [];
    
    // Add common variations
    $variations[] = $keyword . ' services';
    $variations[] = 'professional ' . $keyword;
    $variations[] = $keyword . ' solutions';
    $variations[] = 'expert ' . $keyword;
    
    // Add location-based variations
    $variations[] = $keyword . ' in Ghana';
    $variations[] = 'Ghana ' . $keyword;
    
    return $variations;
}

/**
 * Add natural keyword variation to content
 * @param string $content Content
 * @param string $variation Keyword variation
 * @return string Content with added variation
 */
function addNaturalKeywordVariation($content, $variation) {
    // Find a good place to add the variation
    $sentences = preg_split('/(?<=[.!?])\s+/', $content);
    
    foreach ($sentences as $index => $sentence) {
        if (strlen($sentence) > 50 && strlen($sentence) < 150) {
            // Add variation to this sentence
            $sentences[$index] = $sentence . ' We provide ' . $variation . ' to help you achieve your goals.';
            break;
        }
    }
    
    return implode(' ', $sentences);
}

/**
 * Add internal linking opportunities
 * @param string $content Content
 * @param string $page_type Page type
 * @return string Content with internal links
 */
function addInternalLinkingOpportunities($content, $page_type) {
    $linking_keywords = [
        'web development' => 'services.php#web-development',
        'mobile app development' => 'services.php#mobile-development',
        'database design' => 'services.php#database-design',
        'custom software' => 'services.php#custom-software',
        'PHP development' => 'services.php#php-development',
        'JavaScript development' => 'services.php#javascript-development',
        'React development' => 'services.php#react-development',
        'Node.js development' => 'services.php#nodejs-development',
        'portfolio' => 'projects.php',
        'our work' => 'projects.php',
        'contact us' => 'contact.php',
        'get quote' => 'quote_request.php',
        'about us' => 'about.php',
        'digital products' => 'store.php'
    ];
    
    foreach ($linking_keywords as $keyword => $url) {
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
        $replacement = '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars(ucfirst($keyword)) . '" class="internal-link">' . $keyword . '</a>';
        
        // Replace only the first occurrence
        $content = preg_replace($pattern, $replacement, $content, 1);
    }
    
    return $content;
}

/**
 * Optimize images in content
 * @param string $content Content with images
 * @return string Content with optimized images
 */
function optimizeImagesInContent($content) {
    $content = preg_replace_callback('/<img([^>]*)>/i', function($matches) {
        $img_tag = $matches[0];
        $attributes = $matches[1];
        
        // Add missing alt attribute
        if (strpos($attributes, 'alt=') === false) {
            $attributes .= ' alt="ManuelCode - Professional Software Development"';
        }
        
        // Add loading="lazy" if not present
        if (strpos($attributes, 'loading=') === false) {
            $attributes .= ' loading="lazy"';
        }
        
        // Add decoding="async" if not present
        if (strpos($attributes, 'decoding=') === false) {
            $attributes .= ' decoding="async"';
        }
        
        // Add SEO-friendly class
        if (strpos($attributes, 'class=') === false) {
            $attributes .= ' class="seo-optimized-image"';
        } else {
            $attributes = preg_replace('/class="([^"]*)"/', 'class="$1 seo-optimized-image"', $attributes);
        }
        
        return '<img' . $attributes . '>';
    }, $content);
    
    return $content;
}

/**
 * Add schema markup opportunities
 * @param string $content Content
 * @param string $page_type Page type
 * @return string Content with schema markup
 */
function addSchemaMarkupOpportunities($content, $page_type) {
    $schema_markup = '';
    
    switch ($page_type) {
        case 'service':
            $schema_markup = generateServiceSchemaMarkup();
            break;
        case 'product':
            $schema_markup = generateProductSchemaMarkup();
            break;
        case 'project':
            $schema_markup = generateProjectSchemaMarkup();
            break;
        case 'about':
            $schema_markup = generateAboutSchemaMarkup();
            break;
    }
    
    return $content . $schema_markup;
}

/**
 * Generate service schema markup
 * @return string Service schema markup
 */
function generateServiceSchemaMarkup() {
    return '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Software Development Services",
        "description": "Professional software development services including web applications, mobile apps, and custom software solutions",
        "provider": {
            "@type": "Person",
            "name": "ManuelCode",
            "url": "https://manuelcode.info"
        },
        "areaServed": {
            "@type": "Country",
            "name": "Ghana"
        },
        "serviceType": "Software Development",
        "offers": {
            "@type": "Offer",
            "description": "Professional software development services",
            "priceCurrency": "GHS",
            "availability": "https://schema.org/InStock"
        }
    }
    </script>';
}

/**
 * Generate product schema markup
 * @return string Product schema markup
 */
function generateProductSchemaMarkup() {
    return '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "Digital Products",
        "description": "Professional digital products and templates for software development",
        "brand": {
            "@type": "Brand",
            "name": "ManuelCode"
        },
        "offers": {
            "@type": "Offer",
            "priceCurrency": "GHS",
            "availability": "https://schema.org/InStock"
        }
    }
    </script>';
}

/**
 * Generate project schema markup
 * @return string Project schema markup
 */
function generateProjectSchemaMarkup() {
    return '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CreativeWork",
        "name": "Software Development Projects",
        "description": "Portfolio of completed software development projects",
        "creator": {
            "@type": "Person",
            "name": "ManuelCode"
        },
        "dateCreated": "2024-01-01",
        "genre": "Software Development"
    }
    </script>';
}

/**
 * Generate about schema markup
 * @return string About schema markup
 */
function generateAboutSchemaMarkup() {
    return '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Person",
        "name": "ManuelCode",
        "jobTitle": "Professional Software Engineer",
        "description": "Full-stack developer specializing in web applications and software architecture",
        "url": "https://manuelcode.info",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "GH"
        },
        "knowsAbout": [
            "Software Engineering",
            "Web Development",
            "Mobile App Development",
            "Database Design"
        ]
    }
    </script>';
}

/**
 * Generate content optimization CSS
 * @return string Content optimization CSS
 */
function generateContentOptimizationCSS() {
    return '
    <style>
    /* Content SEO Optimization Styles */
    .main-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1rem;
        line-height: 1.7;
    }
    
    .content-article {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .seo-heading {
        color: #536895;
        margin: 2rem 0 1rem 0;
        font-weight: 600;
        line-height: 1.3;
    }
    
    .seo-heading:first-child {
        margin-top: 0;
    }
    
    .seo-heading:hover {
        color: #F5A623;
    }
    
    .seo-optimized-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin: 1rem 0;
    }
    
    .internal-link {
        color: #536895;
        text-decoration: underline;
        text-decoration-color: rgba(83, 104, 149, 0.3);
        text-underline-offset: 2px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .internal-link:hover {
        color: #F5A623;
        text-decoration-color: #F5A623;
        text-decoration-thickness: 2px;
    }
    
    /* Content typography */
    .main-content p {
        margin: 1rem 0;
        color: #374151;
    }
    
    .main-content ul, .main-content ol {
        margin: 1rem 0;
        padding-left: 2rem;
    }
    
    .main-content li {
        margin: 0.5rem 0;
        color: #374151;
    }
    
    .main-content blockquote {
        border-left: 4px solid #F5A623;
        padding-left: 1rem;
        margin: 2rem 0;
        font-style: italic;
        color: #6B7280;
    }
    
    .main-content code {
        background: #F3F4F6;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-family: "Courier New", monospace;
        color: #E11D48;
    }
    
    .main-content pre {
        background: #1F2937;
        color: #F9FAFB;
        padding: 1rem;
        border-radius: 8px;
        overflow-x: auto;
        margin: 1rem 0;
    }
    
    .main-content pre code {
        background: none;
        color: inherit;
        padding: 0;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .main-content {
            padding: 1rem 0.5rem;
        }
        
        .content-article {
            padding: 1.5rem;
        }
        
        .seo-heading {
            font-size: 1.5rem;
        }
    }
    </style>';
}

} // End function_exists check
?>
