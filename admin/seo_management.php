<?php
// SEO and Social Media Management
session_start();
include 'auth/check_superadmin_auth.php';
include '../includes/db.php';
include '../includes/util.php';

// Get current SEO settings
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value, description FROM seo_settings ORDER BY setting_key");
    $stmt->execute();
    $seo_settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $seo_settings[$row['setting_key']] = $row;
    }
} catch (Exception $e) {
    $seo_settings = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['seo_settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE seo_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        // Generate robots.txt
        $robots_content = $_POST['seo_settings']['robots_txt'];
        file_put_contents('../robots.txt', $robots_content);
        
        // Generate sitemap if enabled
        if ($_POST['seo_settings']['sitemap_enabled'] == '1') {
            generateSitemap();
        }
        
        $success_message = "SEO settings updated successfully!";
        
        // Refresh settings
        $stmt = $pdo->prepare("SELECT setting_key, setting_value, description FROM seo_settings ORDER BY setting_key");
        $stmt->execute();
        $seo_settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $seo_settings[$row['setting_key']] = $row;
        }
    } catch (Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

function generateSitemap() {
    global $pdo;
    
    try {
        $base_url = "https://manuelcode.info";
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add static pages
        $static_pages = [
            '' => '1.0',
            'about.php' => '0.8',
            'services.php' => '0.9',
            'contact.php' => '0.8',
            'store.php' => '0.9',
            'projects.php' => '0.9'
        ];
        
        foreach ($static_pages as $page => $priority) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$base_url}/{$page}</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }
        
        // Add products
        $stmt = $pdo->prepare("SELECT id, title, updated_at FROM products WHERE status = 'active'");
        $stmt->execute();
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$base_url}/product.php?id={$product['id']}</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', strtotime($product['updated_at'])) . "</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.7</priority>\n";
            $xml .= "  </url>\n";
        }
        
        // Add projects
        $stmt = $pdo->prepare("SELECT id, title, updated_at FROM projects WHERE status = 'active'");
        $stmt->execute();
        while ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$base_url}/project-detail.php?id={$project['id']}</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', strtotime($project['updated_at'])) . "</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.7</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        file_put_contents('../sitemap.xml', $xml);
        return true;
    } catch (Exception $e) {
        error_log("Error generating sitemap: " . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SEO Management - Super Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#F4F4F9]">
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[#2D3E50]">
                    <i class="fas fa-search mr-2"></i>SEO & Social Media Management
                </h1>
                <p class="text-gray-600">Configure search engine optimization and social media sharing</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="generateSitemap()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-sitemap mr-2"></i>Generate Sitemap
                </button>
                <a href="superadmin.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <!-- Basic SEO Settings -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-cog text-blue-600 mr-3"></i>Basic SEO Settings
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site Title</label>
                        <input type="text" name="seo_settings[site_title]" 
                               value="<?php echo htmlspecialchars($seo_settings['site_title']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Your Site Title">
                        <p class="text-xs text-gray-500 mt-1">Main title for search engines</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
                        <textarea name="seo_settings[site_description]" rows="3"
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Brief description of your website"><?php echo htmlspecialchars($seo_settings['site_description']['setting_value'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Meta description for search results</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                        <input type="text" name="seo_settings[site_keywords]" 
                               value="<?php echo htmlspecialchars($seo_settings['site_keywords']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="keyword1, keyword2, keyword3">
                        <p class="text-xs text-gray-500 mt-1">Comma-separated keywords</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Favicon URL</label>
                        <input type="text" name="seo_settings[favicon_url]" 
                               value="<?php echo htmlspecialchars($seo_settings['favicon_url']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="/assets/favi/favicon.png">
                        <p class="text-xs text-gray-500 mt-1">Path to your favicon file</p>
                    </div>
                </div>
            </div>

            <!-- Open Graph (Facebook/WhatsApp) -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fab fa-facebook text-blue-600 mr-3"></i>Open Graph (Facebook/WhatsApp)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OG Title</label>
                        <input type="text" name="seo_settings[og_title]" 
                               value="<?php echo htmlspecialchars($seo_settings['og_title']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Title for social media sharing">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OG Description</label>
                        <textarea name="seo_settings[og_description]" rows="3"
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Description for social media sharing"><?php echo htmlspecialchars($seo_settings['og_description']['setting_value'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OG Image</label>
                        <input type="text" name="seo_settings[og_image]" 
                               value="<?php echo htmlspecialchars($seo_settings['og_image']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="/assets/images/og-image.jpg">
                        <p class="text-xs text-gray-500 mt-1">Image displayed when shared on social media (1200x630px recommended)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OG Type</label>
                        <select name="seo_settings[og_type]" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="website" <?php echo ($seo_settings['og_type']['setting_value'] ?? '') === 'website' ? 'selected' : ''; ?>>Website</option>
                            <option value="article" <?php echo ($seo_settings['og_type']['setting_value'] ?? '') === 'article' ? 'selected' : ''; ?>>Article</option>
                            <option value="product" <?php echo ($seo_settings['og_type']['setting_value'] ?? '') === 'product' ? 'selected' : ''; ?>>Product</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Twitter Cards -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fab fa-twitter text-blue-400 mr-3"></i>Twitter Cards
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Card Type</label>
                        <select name="seo_settings[twitter_card]" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="summary" <?php echo ($seo_settings['twitter_card']['setting_value'] ?? '') === 'summary' ? 'selected' : ''; ?>>Summary</option>
                            <option value="summary_large_image" <?php echo ($seo_settings['twitter_card']['setting_value'] ?? '') === 'summary_large_image' ? 'selected' : ''; ?>>Summary Large Image</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Title</label>
                        <input type="text" name="seo_settings[twitter_title]" 
                               value="<?php echo htmlspecialchars($seo_settings['twitter_title']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Title for Twitter sharing">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Description</label>
                        <textarea name="seo_settings[twitter_description]" rows="3"
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Description for Twitter sharing"><?php echo htmlspecialchars($seo_settings['twitter_description']['setting_value'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Image</label>
                        <input type="text" name="seo_settings[twitter_image]" 
                               value="<?php echo htmlspecialchars($seo_settings['twitter_image']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="/assets/images/twitter-image.jpg">
                        <p class="text-xs text-gray-500 mt-1">Image for Twitter cards (1200x600px recommended)</p>
                    </div>
                </div>
            </div>

            <!-- Search Engine Verification -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-search text-green-600 mr-3"></i>Search Engine Verification
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Google Analytics ID</label>
                        <input type="text" name="seo_settings[google_analytics_id]" 
                               value="<?php echo htmlspecialchars($seo_settings['google_analytics_id']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="G-XXXXXXXXXX">
                        <p class="text-xs text-gray-500 mt-1">Google Analytics 4 measurement ID</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Google Search Console</label>
                        <input type="text" name="seo_settings[google_search_console]" 
                               value="<?php echo htmlspecialchars($seo_settings['google_search_console']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Verification code">
                        <p class="text-xs text-gray-500 mt-1">HTML tag verification code</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bing Webmaster Tools</label>
                        <input type="text" name="seo_settings[bing_webmaster]" 
                               value="<?php echo htmlspecialchars($seo_settings['bing_webmaster']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Verification code">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Yandex Webmaster</label>
                        <input type="text" name="seo_settings[yandex_webmaster]" 
                               value="<?php echo htmlspecialchars($seo_settings['yandex_webmaster']['setting_value'] ?? ''); ?>"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Verification code">
                    </div>
                </div>
            </div>

            <!-- Robots.txt and Sitemap -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-[#2D3E50] mb-4 flex items-center">
                    <i class="fas fa-robot text-purple-600 mr-3"></i>Robots.txt & Sitemap
                </h2>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Robots.txt Content</label>
                        <textarea name="seo_settings[robots_txt]" rows="8"
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                                  placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/&#10;Sitemap: https://yourdomain.com/sitemap.xml"><?php echo htmlspecialchars($seo_settings['robots_txt']['setting_value'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Instructions for search engine crawlers</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="seo_settings[sitemap_enabled]" value="1" 
                               <?php echo ($seo_settings['sitemap_enabled']['setting_value'] ?? '') === '1' ? 'checked' : ''; ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-900">Enable automatic sitemap generation</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg transition-colors flex items-center">
                    <i class="fas fa-save mr-2"></i>Save All Settings
                </button>
            </div>
        </form>
    </div>

    <script>
        function generateSitemap() {
            if (confirm('Generate a new sitemap.xml file?')) {
                fetch('seo_management.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=generate_sitemap'
                })
                .then(response => response.text())
                .then(data => {
                    alert('Sitemap generated successfully!');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error generating sitemap');
                });
            }
        }
    </script>
</body>
</html>
