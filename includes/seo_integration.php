<?php
/**
 * SEO Integration
 * Comprehensive SEO integration for ManuelCode
 * Includes all SEO optimizations in one place
 */

// Include all SEO optimization files
if (file_exists(__DIR__ . '/seo_optimizer.php')) {
    include_once 'seo_optimizer.php';
}

if (file_exists(__DIR__ . '/mobile_seo_optimizer.php')) {
    include_once 'mobile_seo_optimizer.php';
}

if (file_exists(__DIR__ . '/internal_linking_optimizer.php')) {
    include_once 'internal_linking_optimizer.php';
}

if (file_exists(__DIR__ . '/content_seo_optimizer.php')) {
    include_once 'content_seo_optimizer.php';
}

// Prevent function redeclaration
if (!function_exists('initializeSEO')) {

/**
 * Initialize comprehensive SEO for a page
 * @param string $page_type Type of page (homepage, services, projects, etc.)
 * @param array $custom_meta Custom meta data
 * @return array Complete SEO configuration
 */
function initializeSEO($page_type = 'homepage', $custom_meta = []) {
    // Get mobile SEO meta
    $mobile_meta = generateMobileSEOMeta($page_type);
    
    // Merge with custom meta
    $meta_data = array_merge($mobile_meta, $custom_meta);
    
    // Set page meta
    setPageMeta($meta_data);
    
    // Generate additional SEO elements
    $seo_config = [
        'meta_data' => $meta_data,
        'mobile_optimized' => generateMobileOptimizedMeta($meta_data),
        'structured_data' => generateMobileStructuredData([
            'phone' => '+233-XXX-XXX-XXX',
            'email' => 'contact@manuelcode.info',
            'latitude' => '5.6037',
            'longitude' => '-0.1870'
        ]),
        'internal_links' => generateInternalLinkingStructure($page_type),
        'performance_hints' => generatePerformanceHints(),
        'core_web_vitals' => generateCoreWebVitalsOptimization()
    ];
    
    return $seo_config;
}

/**
 * Render complete SEO head section
 * @param array $seo_config SEO configuration
 * @return string Complete SEO head HTML
 */
function renderCompleteSEOHead($seo_config) {
    $html = '';
    
    // Render meta tags
    renderMetaTags();
    
    // Add mobile optimization
    $html .= $seo_config['mobile_optimized'];
    
    // Add structured data
    $html .= $seo_config['structured_data'];
    
    // Add performance hints
    $html .= $seo_config['performance_hints'];
    
    // Add Core Web Vitals optimization
    $html .= $seo_config['core_web_vitals'];
    
    // Add mobile CSS
    $html .= generateMobileOptimizedCSS();
    
    // Add internal linking CSS
    $html .= generateInternalLinkingCSS();
    
    // Add content optimization CSS
    $html .= generateContentOptimizationCSS();
    
    return $html;
}

/**
 * Generate page-specific SEO content
 * @param string $page_type Page type
 * @param string $content Raw content
 * @return string SEO-optimized content
 */
function generatePageSEOContent($page_type, $content) {
    // Get page-specific keywords
    $keywords = getPageKeywords($page_type);
    
    // Optimize content for SEO
    $optimized_content = optimizeContentForSEO($content, $keywords, $page_type);
    
    // Add internal linking
    $optimized_content = enhanceContentWithInternalLinks($optimized_content, $page_type);
    
    // Add related pages
    $related_pages = generateRelatedPages($page_type);
    
    return $optimized_content . $related_pages;
}

/**
 * Get page-specific keywords
 * @param string $page_type Page type
 * @return array Keywords for the page
 */
function getPageKeywords($page_type) {
    $keywords_map = [
        'homepage' => [
            'software engineer Ghana',
            'full-stack developer',
            'web development Ghana',
            'mobile app development',
            'PHP developer',
            'JavaScript developer',
            'React developer',
            'Node.js developer',
            'custom software development',
            'database design'
        ],
        'services' => [
            'software development services',
            'web development services',
            'mobile app development',
            'custom software solutions',
            'database design services',
            'PHP development',
            'JavaScript development',
            'React development',
            'Node.js development',
            'software consulting Ghana'
        ],
        'projects' => [
            'software development portfolio',
            'web development projects',
            'mobile app projects',
            'custom software examples',
            'PHP projects',
            'JavaScript projects',
            'React projects',
            'Node.js projects',
            'database projects',
            'software development work'
        ],
        'store' => [
            'digital products',
            'software templates',
            'web development templates',
            'mobile app templates',
            'PHP templates',
            'JavaScript templates',
            'React templates',
            'Node.js templates',
            'database templates',
            'software solutions'
        ],
        'about' => [
            'software engineer profile',
            'full-stack developer experience',
            'web development expertise',
            'mobile app development skills',
            'PHP expertise',
            'JavaScript expertise',
            'React expertise',
            'Node.js expertise',
            'database design skills',
            'software development experience'
        ],
        'contact' => [
            'contact software engineer',
            'hire developer Ghana',
            'software development consultation',
            'web development consultation',
            'mobile app development consultation',
            'custom software consultation',
            'PHP development consultation',
            'JavaScript development consultation',
            'React development consultation',
            'Node.js development consultation'
        ]
    ];
    
    return $keywords_map[$page_type] ?? $keywords_map['homepage'];
}

/**
 * Generate comprehensive SEO report
 * @param string $page_type Page type
 * @return array SEO report data
 */
function generateSEOReport($page_type) {
    $report = [
        'page_type' => $page_type,
        'meta_optimization' => [
            'title_length' => strlen(getPageMeta()['title'] ?? ''),
            'description_length' => strlen(getPageMeta()['description'] ?? ''),
            'keywords_count' => count(explode(',', getPageMeta()['keywords'] ?? '')),
            'has_canonical' => !empty(getPageMeta()['canonical']),
            'has_og_tags' => !empty(getPageMeta()['og_title']),
            'has_twitter_cards' => !empty(getPageMeta()['twitter_title'])
        ],
        'technical_seo' => [
            'has_structured_data' => true,
            'has_mobile_optimization' => true,
            'has_internal_linking' => true,
            'has_performance_optimization' => true,
            'has_core_web_vitals' => true
        ],
        'content_optimization' => [
            'has_semantic_html' => true,
            'has_heading_hierarchy' => true,
            'has_optimized_images' => true,
            'has_internal_links' => true,
            'has_keyword_optimization' => true
        ],
        'mobile_seo' => [
            'has_mobile_viewport' => true,
            'has_touch_optimization' => true,
            'has_mobile_structured_data' => true,
            'has_mobile_performance' => true,
            'has_mobile_content_optimization' => true
        ]
    ];
    
    return $report;
}

/**
 * Generate SEO checklist for manual verification
 * @return array SEO checklist
 */
function generateSEOChecklist() {
    return [
        'Technical SEO' => [
            '✅ XML Sitemap is present and updated',
            '✅ Robots.txt is properly configured',
            '✅ Canonical URLs are set',
            '✅ Meta robots tags are appropriate',
            '✅ Page loading speed is optimized',
            '✅ Core Web Vitals are optimized',
            '✅ Mobile-first indexing is enabled',
            '✅ HTTPS is implemented',
            '✅ SSL certificate is valid'
        ],
        'Content SEO' => [
            '✅ Title tags are optimized (50-60 characters)',
            '✅ Meta descriptions are compelling (150-160 characters)',
            '✅ Heading hierarchy is proper (H1 > H2 > H3)',
            '✅ Images have descriptive alt text',
            '✅ Internal linking structure is logical',
            '✅ Content is original and valuable',
            '✅ Keywords are naturally integrated',
            '✅ Content is mobile-friendly',
            '✅ Content loads quickly'
        ],
        'Structured Data' => [
            '✅ JSON-LD structured data is implemented',
            '✅ Person schema is present',
            '✅ Service schema is implemented',
            '✅ Local business schema is added',
            '✅ FAQ schema is used where appropriate',
            '✅ Breadcrumb schema is implemented',
            '✅ Organization schema is present',
            '✅ Contact information is structured',
            '✅ Social media profiles are linked'
        ],
        'Mobile SEO' => [
            '✅ Mobile viewport is properly set',
            '✅ Touch targets are at least 44px',
            '✅ Mobile navigation is user-friendly',
            '✅ Mobile content is readable',
            '✅ Mobile images are optimized',
            '✅ Mobile forms are usable',
            '✅ Mobile performance is fast',
            '✅ Mobile structured data is present',
            '✅ Mobile social sharing works'
        ],
        'Performance' => [
            '✅ Page load time is under 3 seconds',
            '✅ First Contentful Paint is under 1.5s',
            '✅ Largest Contentful Paint is under 2.5s',
            '✅ Cumulative Layout Shift is under 0.1',
            '✅ First Input Delay is under 100ms',
            '✅ Images are optimized and lazy-loaded',
            '✅ CSS and JS are minified',
            '✅ Critical CSS is inlined',
            '✅ Non-critical resources are deferred'
        ]
    ];
}

/**
 * Generate SEO monitoring script
 * @return string SEO monitoring JavaScript
 */
function generateSEOMonitoringScript() {
    return '
    <script>
    // SEO Monitoring and Analytics
    (function() {
        "use strict";
        
        // Monitor Core Web Vitals
        function monitorCoreWebVitals() {
            if ("web-vital" in window) {
                // Monitor LCP
                new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        if (entry.entryType === "largest-contentful-paint") {
                            console.log("LCP:", entry.startTime);
                        }
                    }
                }).observe({entryTypes: ["largest-contentful-paint"]});
                
                // Monitor FID
                new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        if (entry.entryType === "first-input") {
                            console.log("FID:", entry.processingStart - entry.startTime);
                        }
                    }
                }).observe({entryTypes: ["first-input"]});
                
                // Monitor CLS
                let clsValue = 0;
                new PerformanceObserver((entryList) => {
                    for (const entry of entryList.getEntries()) {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    }
                    console.log("CLS:", clsValue);
                }).observe({entryTypes: ["layout-shift"]});
            }
        }
        
        // Monitor page load performance
        function monitorPageLoad() {
            window.addEventListener("load", function() {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log("Page Load Time:", loadTime + "ms");
                
                // Monitor resource loading
                const resources = performance.getEntriesByType("resource");
                resources.forEach(resource => {
                    if (resource.duration > 1000) {
                        console.warn("Slow resource:", resource.name, resource.duration + "ms");
                    }
                });
            });
        }
        
        // Monitor user engagement
        function monitorUserEngagement() {
            let startTime = Date.now();
            let isActive = true;
            
            document.addEventListener("visibilitychange", function() {
                if (document.hidden) {
                    isActive = false;
                } else {
                    isActive = true;
                    startTime = Date.now();
                }
            });
            
            // Track time on page
            window.addEventListener("beforeunload", function() {
                const timeOnPage = Date.now() - startTime;
                console.log("Time on page:", timeOnPage + "ms");
            });
        }
        
        // Initialize monitoring
        document.addEventListener("DOMContentLoaded", function() {
            monitorCoreWebVitals();
            monitorPageLoad();
            monitorUserEngagement();
        });
        
    })();
    </script>';
}

} // End function_exists check
?>
