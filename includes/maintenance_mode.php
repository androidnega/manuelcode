<?php
// Maintenance Mode Handler
// This file should be included at the top of all public pages

// Prevent function redeclaration
if (!function_exists('get_maintenance_mode')) {
function get_maintenance_mode() {
    global $pdo;
    
    // Ensure database connection is available
    if (!isset($pdo) || !$pdo) {
        return ['site_mode' => 'standard', 'maintenance_message' => ''];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, value FROM settings WHERE setting_key IN ('site_mode', 'maintenance_message', 'maintenance_start', 'maintenance_end', 'maintenance_logo', 'maintenance_icon')");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['value'];
        }
        return $settings;
    } catch (Exception $e) {
        // Log error but don't break the page
        error_log("Maintenance mode check failed: " . $e->getMessage());
        return ['site_mode' => 'standard', 'maintenance_message' => ''];
    }
}

function should_show_maintenance_page() {
    // Don't show maintenance page for admin areas
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        return false;
    }
    
    // Don't show maintenance page for API endpoints
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        return false;
    }
    
    $settings = get_maintenance_mode();
    $mode = $settings['site_mode'] ?? 'standard';
    
    return $mode !== 'standard';
}

function display_maintenance_page() {
    $settings = get_maintenance_mode();
    $mode = $settings['site_mode'] ?? 'standard';
    $message = $settings['maintenance_message'] ?? '';
    $logo = $settings['maintenance_logo'] ?? '';
    $icon = $settings['maintenance_icon'] ?? '';
    $start = $settings['maintenance_start'] ?? '';
    $end = $settings['maintenance_end'] ?? '';
    
    $mode_config = [
        'maintenance' => [
            'title' => 'Site Under Maintenance',
            'icon' => 'fas fa-wrench',
            'color' => 'text-red-600',
            'bg_color' => 'bg-red-50',
            'default_message' => 'We are currently performing maintenance to improve your experience. Please check back soon.'
        ],
        'coming_soon' => [
            'title' => 'Coming Soon',
            'icon' => 'fas fa-clock',
            'color' => 'text-yellow-600',
            'bg_color' => 'bg-yellow-50',
            'default_message' => 'We are working hard to bring you something amazing. Stay tuned!'
        ],
        'update' => [
            'title' => 'Site Update in Progress',
            'icon' => 'fas fa-sync-alt',
            'color' => 'text-blue-600',
            'bg_color' => 'bg-blue-50',
            'default_message' => 'We are updating our site with new features. Please wait a moment.'
        ]
    ];
    
    $config = $mode_config[$mode] ?? $mode_config['maintenance'];
    $display_message = $message ?: $config['default_message'];
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($config['title']); ?> - ManuelCode</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .gradient-bg {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .glass-effect {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(0, 0, 0, 0.1);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body class="bg-white min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-xl">
            <!-- Logo/Brand Section -->
            <div class="text-center mb-8">
                <?php if ($logo): ?>
                  <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" class="h-14 mx-auto mb-4" />
                <?php endif; ?>
            </div>

            <!-- Maintenance Card -->
            <div class="border rounded-xl shadow p-6 md:p-8 text-center">
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 <?php echo $config['bg_color']; ?> rounded-full mb-4">
                        <i class="<?php echo $icon ?: $config['icon']; ?> text-2xl <?php echo $config['color']; ?>"></i>
                    </div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-3"><?php echo htmlspecialchars($config['title']); ?></h2>
                    <p class="text-gray-600 text-base leading-relaxed mb-4"><?php echo htmlspecialchars($display_message); ?></p>
                </div>
                <?php if ($start || $end): ?>
                <div class="mb-6 text-sm text-gray-500">
                    <?php if ($start): ?><div class="mb-1">From: <?php echo htmlspecialchars($start); ?></div><?php endif; ?>
                    <?php if ($end): ?><div>Until: <?php echo htmlspecialchars($end); ?></div><?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Contact Information -->
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-gray-600 mb-3 text-sm">Need immediate assistance?</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <a href="https://wa.me/233541069241" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                            <i class="fab fa-whatsapp mr-2"></i>
                            WhatsApp
                        </a>
                        <a href="sms:+233257940791" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            <i class="fas fa-sms mr-2"></i>
                            SMS
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="tel:+233257940791" class="inline-flex items-center justify-center px-4 py-2 bg-[#536895] text-white rounded-lg hover:bg-[#4a5a7a] transition-colors text-sm font-medium">
                            <i class="fas fa-phone mr-2"></i>
                            Call Us
                        </a>
                        <a href="mailto:admin@manuelcode.info" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium">
                            <i class="fas fa-envelope mr-2"></i>
                            Email Us
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-gray-400 text-xs">&copy; <?php echo date('Y'); ?> ManuelCode. All rights reserved.</p>
            </div>
        </div>

        <script>
            // Auto-refresh every 30 seconds to check if maintenance is over
            setTimeout(function() {
                window.location.reload();
            }, 30000);
        </script>
    </body>
    </html>
    <?php
    exit();
}
} // End of function_exists check
?>
