<?php
/**
 * Internal Linking Optimizer
 * Enhances internal linking structure for better SEO
 */

// Prevent function redeclaration
if (!function_exists('generateInternalLinkingStructure')) {

/**
 * Generate optimized internal linking structure
 * @param string $current_page Current page identifier
 * @return array Internal linking suggestions
 */
function generateInternalLinkingStructure($current_page) {
    $internal_links = [
        'homepage' => [
            'primary_links' => [
                ['url' => 'services.php', 'text' => 'Software Development Services', 'title' => 'Professional software development services'],
                ['url' => 'projects.php', 'text' => 'Our Projects', 'title' => 'View our portfolio of completed projects'],
                ['url' => 'store.php', 'text' => 'Digital Products', 'title' => 'Browse our digital products and templates'],
                ['url' => 'about.php', 'text' => 'About ManuelCode', 'title' => 'Learn more about our expertise and experience']
            ],
            'secondary_links' => [
                ['url' => 'quote_request.php', 'text' => 'Get Free Quote', 'title' => 'Request a free project quote'],
                ['url' => 'contact.php', 'text' => 'Contact Us', 'title' => 'Get in touch for your project needs']
            ],
            'contextual_links' => [
                'web development' => ['url' => 'services.php#web-development', 'text' => 'Web Development Services'],
                'mobile apps' => ['url' => 'services.php#mobile-development', 'text' => 'Mobile App Development'],
                'database design' => ['url' => 'services.php#database-design', 'text' => 'Database Design Services'],
                'PHP development' => ['url' => 'services.php#php-development', 'text' => 'PHP Development Services'],
                'JavaScript development' => ['url' => 'services.php#javascript-development', 'text' => 'JavaScript Development Services']
            ]
        ],
        'services' => [
            'primary_links' => [
                ['url' => 'index.php', 'text' => 'Home', 'title' => 'Return to homepage'],
                ['url' => 'projects.php', 'text' => 'Our Work', 'title' => 'See examples of our development work'],
                ['url' => 'quote_request.php', 'text' => 'Get Quote', 'title' => 'Request a free project quote'],
                ['url' => 'contact.php', 'text' => 'Contact Us', 'title' => 'Discuss your project requirements']
            ],
            'secondary_links' => [
                ['url' => 'store.php', 'text' => 'Digital Products', 'title' => 'Browse our ready-made solutions'],
                ['url' => 'about.php', 'text' => 'About Us', 'title' => 'Learn about our expertise and experience']
            ],
            'contextual_links' => [
                'portfolio' => ['url' => 'projects.php', 'text' => 'View Our Portfolio'],
                'pricing' => ['url' => 'quote_request.php', 'text' => 'Get Free Quote'],
                'contact' => ['url' => 'contact.php', 'text' => 'Contact Us Today'],
                'web development' => ['url' => 'services.php#web-development', 'text' => 'Web Development Services'],
                'mobile development' => ['url' => 'services.php#mobile-development', 'text' => 'Mobile App Development']
            ]
        ],
        'projects' => [
            'primary_links' => [
                ['url' => 'index.php', 'text' => 'Home', 'title' => 'Return to homepage'],
                ['url' => 'services.php', 'text' => 'Our Services', 'title' => 'Explore our development services'],
                ['url' => 'submission.php', 'text' => 'Submit Project', 'title' => 'Submit your project for development'],
                ['url' => 'contact.php', 'text' => 'Contact Us', 'title' => 'Discuss your project needs']
            ],
            'secondary_links' => [
                ['url' => 'store.php', 'text' => 'Digital Products', 'title' => 'Browse our digital products'],
                ['url' => 'about.php', 'text' => 'About Us', 'title' => 'Learn about our development expertise']
            ],
            'contextual_links' => [
                'similar projects' => ['url' => 'projects.php', 'text' => 'More Projects'],
                'development services' => ['url' => 'services.php', 'text' => 'Our Services'],
                'get quote' => ['url' => 'quote_request.php', 'text' => 'Get Free Quote'],
                'contact' => ['url' => 'contact.php', 'text' => 'Contact Us']
            ]
        ],
        'store' => [
            'primary_links' => [
                ['url' => 'index.php', 'text' => 'Home', 'title' => 'Return to homepage'],
                ['url' => 'services.php', 'text' => 'Custom Development', 'title' => 'Need custom development services?'],
                ['url' => 'projects.php', 'text' => 'Our Work', 'title' => 'See examples of our development work'],
                ['url' => 'contact.php', 'text' => 'Contact Us', 'title' => 'Get in touch for custom solutions']
            ],
            'secondary_links' => [
                ['url' => 'guest_download.php', 'text' => 'Downloads', 'title' => 'Access your downloads'],
                ['url' => 'about.php', 'text' => 'About Us', 'title' => 'Learn about our expertise']
            ],
            'contextual_links' => [
                'custom development' => ['url' => 'services.php', 'text' => 'Custom Development Services'],
                'portfolio' => ['url' => 'projects.php', 'text' => 'View Our Portfolio'],
                'contact' => ['url' => 'contact.php', 'text' => 'Contact Us'],
                'downloads' => ['url' => 'guest_download.php', 'text' => 'Your Downloads']
            ]
        ],
        'about' => [
            'primary_links' => [
                ['url' => 'index.php', 'text' => 'Home', 'title' => 'Return to homepage'],
                ['url' => 'services.php', 'text' => 'Our Services', 'title' => 'Explore our development services'],
                ['url' => 'projects.php', 'text' => 'Our Work', 'title' => 'See examples of our development work'],
                ['url' => 'contact.php', 'text' => 'Contact Us', 'title' => 'Get in touch with us']
            ],
            'secondary_links' => [
                ['url' => 'quote_request.php', 'text' => 'Get Quote', 'title' => 'Request a free project quote'],
                ['url' => 'store.php', 'text' => 'Digital Products', 'title' => 'Browse our digital products']
            ],
            'contextual_links' => [
                'services' => ['url' => 'services.php', 'text' => 'Our Services'],
                'portfolio' => ['url' => 'projects.php', 'text' => 'Our Portfolio'],
                'contact' => ['url' => 'contact.php', 'text' => 'Contact Us'],
                'get quote' => ['url' => 'quote_request.php', 'text' => 'Get Free Quote']
            ]
        ],
        'contact' => [
            'primary_links' => [
                ['url' => 'index.php', 'text' => 'Home', 'title' => 'Return to homepage'],
                ['url' => 'services.php', 'text' => 'Our Services', 'title' => 'Explore our development services'],
                ['url' => 'quote_request.php', 'text' => 'Get Quote', 'title' => 'Request a free project quote'],
                ['url' => 'projects.php', 'text' => 'Our Work', 'title' => 'See examples of our development work']
            ],
            'secondary_links' => [
                ['url' => 'about.php', 'text' => 'About Us', 'title' => 'Learn about our expertise'],
                ['url' => 'store.php', 'text' => 'Digital Products', 'title' => 'Browse our digital products']
            ],
            'contextual_links' => [
                'services' => ['url' => 'services.php', 'text' => 'Our Services'],
                'portfolio' => ['url' => 'projects.php', 'text' => 'Our Portfolio'],
                'get quote' => ['url' => 'quote_request.php', 'text' => 'Get Free Quote'],
                'about' => ['url' => 'about.php', 'text' => 'About Us']
            ]
        ]
    ];
    
    return $internal_links[$current_page] ?? $internal_links['homepage'];
}

/**
 * Generate contextual internal links based on content
 * @param string $content Page content
 * @param string $current_page Current page identifier
 * @return string Enhanced content with internal links
 */
function enhanceContentWithInternalLinks($content, $current_page) {
    $linking_structure = generateInternalLinkingStructure($current_page);
    $contextual_links = $linking_structure['contextual_links'] ?? [];
    
    foreach ($contextual_links as $keyword => $link_data) {
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
        $replacement = '<a href="' . htmlspecialchars($link_data['url']) . '" title="' . htmlspecialchars($link_data['text']) . '" class="internal-link">' . $keyword . '</a>';
        
        // Replace only the first occurrence to avoid over-linking
        $content = preg_replace($pattern, $replacement, $content, 1);
    }
    
    return $content;
}

/**
 * Generate related pages section
 * @param string $current_page Current page identifier
 * @return string Related pages HTML
 */
function generateRelatedPages($current_page) {
    $linking_structure = generateInternalLinkingStructure($current_page);
    $primary_links = $linking_structure['primary_links'] ?? [];
    
    if (empty($primary_links)) return '';
    
    $html = '<section class="related-pages" aria-label="Related Pages">';
    $html .= '<h3>Related Pages</h3>';
    $html .= '<ul class="related-pages-list">';
    
    foreach ($primary_links as $link) {
        $html .= '<li>';
        $html .= '<a href="' . htmlspecialchars($link['url']) . '" title="' . htmlspecialchars($link['title']) . '" class="related-page-link">';
        $html .= htmlspecialchars($link['text']);
        $html .= '</a>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</section>';
    
    return $html;
}

/**
 * Generate breadcrumb navigation
 * @param array $breadcrumbs Breadcrumb items
 * @return string Breadcrumb HTML with structured data
 */
function generateBreadcrumbNavigation($breadcrumbs) {
    if (empty($breadcrumbs)) return '';
    
    $html = '<nav aria-label="Breadcrumb" class="breadcrumb-navigation">';
    $html .= '<ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $position = $index + 1;
        $html .= '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if ($index === count($breadcrumbs) - 1) {
            // Current page (no link)
            $html .= '<span itemprop="name" class="breadcrumb-current">' . htmlspecialchars($crumb['name']) . '</span>';
        } else {
            // Link to other pages
            $html .= '<a href="' . htmlspecialchars($crumb['url']) . '" itemprop="item" class="breadcrumb-link">';
            $html .= '<span itemprop="name">' . htmlspecialchars($crumb['name']) . '</span>';
            $html .= '</a>';
        }
        
        $html .= '<meta itemprop="position" content="' . $position . '">';
        $html .= '</li>';
        
        // Add separator (except for last item)
        if ($index < count($breadcrumbs) - 1) {
            $html .= '<li class="breadcrumb-separator" aria-hidden="true">›</li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Generate footer internal links
 * @return string Footer internal links HTML
 */
function generateFooterInternalLinks() {
    $footer_links = [
        'services' => [
            ['url' => 'services.php', 'text' => 'Web Development'],
            ['url' => 'services.php#mobile-development', 'text' => 'Mobile Apps'],
            ['url' => 'services.php#database-design', 'text' => 'Database Design'],
            ['url' => 'services.php#custom-software', 'text' => 'Custom Software']
        ],
        'company' => [
            ['url' => 'about.php', 'text' => 'About Us'],
            ['url' => 'projects.php', 'text' => 'Our Work'],
            ['url' => 'contact.php', 'text' => 'Contact'],
            ['url' => 'quote_request.php', 'text' => 'Get Quote']
        ],
        'resources' => [
            ['url' => 'store.php', 'text' => 'Digital Products'],
            ['url' => 'guest_download.php', 'text' => 'Downloads'],
            ['url' => 'privacy.php', 'text' => 'Privacy Policy'],
            ['url' => 'terms.php', 'text' => 'Terms of Service']
        ]
    ];
    
    $html = '<div class="footer-internal-links">';
    
    foreach ($footer_links as $section => $links) {
        $html .= '<div class="footer-link-section">';
        $html .= '<h4>' . ucfirst($section) . '</h4>';
        $html .= '<ul>';
        
        foreach ($links as $link) {
            $html .= '<li>';
            $html .= '<a href="' . htmlspecialchars($link['url']) . '" class="footer-link">';
            $html .= htmlspecialchars($link['text']);
            $html .= '</a>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate internal linking CSS
 * @return string Internal linking CSS
 */
function generateInternalLinkingCSS() {
    return '
    <style>
    /* Internal Linking Styles */
    .internal-link {
        color: #536895;
        text-decoration: underline;
        text-decoration-color: rgba(83, 104, 149, 0.3);
        text-underline-offset: 2px;
        transition: all 0.3s ease;
    }
    
    .internal-link:hover {
        color: #F5A623;
        text-decoration-color: #F5A623;
        text-decoration-thickness: 2px;
    }
    
    /* Breadcrumb Navigation */
    .breadcrumb-navigation {
        margin: 1rem 0;
        padding: 0.5rem 0;
    }
    
    .breadcrumb-list {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        list-style: none;
        margin: 0;
        padding: 0;
        font-size: 0.9rem;
    }
    
    .breadcrumb-item {
        display: flex;
        align-items: center;
    }
    
    .breadcrumb-link {
        color: #536895;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-link:hover {
        color: #F5A623;
    }
    
    .breadcrumb-current {
        color: #F5A623;
        font-weight: 600;
    }
    
    .breadcrumb-separator {
        margin: 0 0.5rem;
        color: #999;
    }
    
    /* Related Pages */
    .related-pages {
        margin: 2rem 0;
        padding: 1.5rem;
        background: rgba(83, 104, 149, 0.05);
        border-radius: 8px;
    }
    
    .related-pages h3 {
        margin: 0 0 1rem 0;
        color: #536895;
        font-size: 1.2rem;
    }
    
    .related-pages-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.5rem;
    }
    
    .related-page-link {
        display: block;
        padding: 0.75rem 1rem;
        background: white;
        color: #536895;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s ease;
        border: 1px solid rgba(83, 104, 149, 0.1);
    }
    
    .related-page-link:hover {
        background: #536895;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(83, 104, 149, 0.2);
    }
    
    /* Footer Internal Links */
    .footer-internal-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin: 2rem 0;
    }
    
    .footer-link-section h4 {
        margin: 0 0 1rem 0;
        color: #536895;
        font-size: 1.1rem;
    }
    
    .footer-link-section ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .footer-link-section li {
        margin: 0.5rem 0;
    }
    
    .footer-link {
        color: #666;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .footer-link:hover {
        color: #F5A623;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .breadcrumb-list {
            font-size: 0.8rem;
        }
        
        .related-pages-list {
            grid-template-columns: 1fr;
        }
        
        .footer-internal-links {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }
    </style>';
}

} // End function_exists check
?>
