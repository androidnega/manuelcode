<?php
// Comprehensive Analytics Tracker
// Include this file at the top of pages to track all visitor activity

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('trackPageVisit')) {
function trackPageVisit($page_name = null) {
    global $pdo;
    
    // Get current page name if not provided
    if (!$page_name) {
        $page_name = $_SERVER['REQUEST_URI'];
        // Remove query parameters
        $page_name = strtok($page_name, '?');
        // Get just the filename
        $page_name = basename($page_name);
        if (empty($page_name) || $page_name === '/') {
            $page_name = 'index.php';
        }
    }
    
    // Get visitor information
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Get device and browser info
    $device_info = getBrowserInfo($user_agent);
    $device_type = getDeviceType($user_agent);
    
    // Get location info (simplified for now)
    $location = getLocationInfo($ip_address);
    
    try {
        // Track page visit
        $stmt = $pdo->prepare("
            INSERT INTO page_visits (session_id, user_id, page_url, page_title, referrer, ip_address, device_type, browser, os, country, city, visit_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $session_id,
            $user_id,
            $page_name,
            $page_name, // Using page name as title for now
            $referrer,
            $ip_address,
            $device_type,
            $device_info['browser'],
            $device_info['os'],
            $location['country'],
            $location['city']
        ]);
        
        // Update popular pages
        $stmt = $pdo->prepare("
            INSERT INTO popular_pages (page_url, page_title, total_visits, unique_visitors, last_visit)
            VALUES (?, ?, 1, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            total_visits = total_visits + 1,
            last_visit = NOW()
        ");
        $stmt->execute([$page_name, $page_name]);
        
        // Update visitor countries
        $stmt = $pdo->prepare("
            INSERT INTO visitor_countries (country, total_visits, unique_visitors, last_visit)
            VALUES (?, 1, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            total_visits = total_visits + 1,
            last_visit = NOW()
        ");
        $stmt->execute([$location['country']]);
        
        // Update visitor devices
        $stmt = $pdo->prepare("
            INSERT INTO visitor_devices (device_type, total_visits, unique_visitors, last_visit)
            VALUES (?, 1, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            total_visits = total_visits + 1,
            last_visit = NOW()
        ");
        $stmt->execute([$device_type]);
        
        // Track user session if logged in
        if ($user_id) {
            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_type, browser, os, country, city)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                is_active = 1,
                updated_at = NOW()
            ");
            $stmt->execute([
                $user_id,
                $session_id,
                $ip_address,
                $user_agent,
                $device_type,
                $device_info['browser'],
                $device_info['os'],
                $location['country'],
                $location['city']
            ]);
        }
        
    } catch (Exception $e) {
        // Silently fail - don't break the user experience
        error_log("Analytics tracking error: " . $e->getMessage());
    }
}
} // End of trackPageVisit function_exists check

if (!function_exists('getBrowserInfo')) {
function getBrowserInfo($user_agent) {
    $browser = 'Unknown';
    $os = 'Unknown';
    
    // Browser detection
    if (preg_match('/MSIE|Trident/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Opera|OPR/i', $user_agent)) {
        $browser = 'Opera';
    } elseif (preg_match('/Edge/i', $user_agent)) {
        $browser = 'Edge';
    }
    
    // OS detection
    if (preg_match('/Windows/i', $user_agent)) {
        $os = 'Windows';
    } elseif (preg_match('/Mac/i', $user_agent)) {
        $os = 'Mac';
    } elseif (preg_match('/Linux/i', $user_agent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android/i', $user_agent)) {
        $os = 'Android';
    } elseif (preg_match('/iOS/i', $user_agent)) {
        $os = 'iOS';
    }
    
    return array('browser' => $browser, 'os' => $os);
}
} // End of getBrowserInfo function_exists check

if (!function_exists('getDeviceType')) {
function getDeviceType($user_agent) {
    $user_agent = strtolower($user_agent);
    
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        return 'tablet';
    }
    
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        return 'mobile';
    }
    
    return 'desktop';
}
} // End of getDeviceType function_exists check

if (!function_exists('getLocationInfo')) {
function getLocationInfo($ip) {
    // Skip localhost and private IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'unknown' || 
        preg_match('/^192\.168\./', $ip) || 
        preg_match('/^10\./', $ip) || 
        preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)) {
        return array(
            'country' => 'Ghana', // Default to Ghana for local development
            'city' => 'Accra'
        );
    }
    
    // Try to get location from IP-API (free service)
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,city,region,regionName,timezone,isp,org,as,mobile,proxy,hosting,query";
        $response = file_get_contents($url);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                return array(
                    'country' => $data['country'] ?? 'Ghana',
                    'city' => $data['city'] ?? 'Unknown'
                );
            }
        }
    } catch (Exception $e) {
        // Silently fail and return default values
        error_log("IP geolocation error: " . $e->getMessage());
    }
    
    // Fallback: Try to determine country from common patterns
    if (preg_match('/^233\./', $ip)) {
        return array('country' => 'Ghana', 'city' => 'Accra');
    } elseif (preg_match('/^1\./', $ip)) {
        return array('country' => 'United States', 'city' => 'Unknown');
    } elseif (preg_match('/^44\./', $ip)) {
        return array('country' => 'United Kingdom', 'city' => 'Unknown');
    } elseif (preg_match('/^91\./', $ip)) {
        return array('country' => 'India', 'city' => 'Unknown');
    }
    
    // Default to Ghana for unknown IPs
    return array(
        'country' => 'Ghana',
        'city' => 'Unknown'
    );
}
} // End of getLocationInfo function_exists check

// Auto-track current page
if (isset($_SERVER['REQUEST_URI'])) {
    // Clean the page URL to remove domain and get just the path
    $page_url = $_SERVER['REQUEST_URI'];
    // Remove query parameters
    $page_url = strtok($page_url, '?');
    // Get just the filename or path
    $page_url = basename($page_url);
    if (empty($page_url) || $page_url === '/') {
        $page_url = 'index.php';
    }
    
    trackPageVisit($page_url);
}
?>
