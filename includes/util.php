<?php
/**
 * Utility Helper Functions
 */

/**
 * Start CSRF protection and return token
 */
function csrf_token_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf'];
}

/**
 * Check CSRF token validity
 */
function csrf_check($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Generate signed token with expiration
 */
function signed_token($data, $secret, $ttl = 3600) {
    $payload = $data;
    $payload['exp'] = time() + $ttl;
    $json = json_encode($payload);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, $secret);
    
    return $b64 . '.' . $sig;
}

/**
 * Verify signed token and return data
 */
function verify_signed_token($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return false;
    }
    
    list($b64, $sig) = $parts;
    $calc = hash_hmac('sha256', $b64, $secret);
    
    if (!hash_equals($calc, $sig)) {
        return false;
    }
    
    $json = base64_decode(strtr($b64, '-_', '+/'));
    $data = json_decode($json, true);
    
    if (!$data || time() > $data['exp']) {
        return false;
    }
    
    return $data;
}

/**
 * Get configuration from database or fallback to constants
 */
function get_config($key, $default = null) {
    // Use auto configuration if available
    if (function_exists('get_auto_config')) {
        return get_auto_config($key, $default);
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['value'];
        }
    } catch (Exception $e) {
        // Fallback to constants if database table doesn't exist
    }
    
    // Fallback to constants for SMS templates
    $constant_map = [
        'sms_payment_success' => 'SMS_PAYMENT_SUCCESS',
        'sms_order_confirmed' => 'SMS_ORDER_CONFIRMED'
    ];
    
    if (isset($constant_map[$key]) && defined($constant_map[$key])) {
        return constant($constant_map[$key]);
    }
    
    return $default;
}

/**
 * Validate file upload
 */
function validate_file_upload($file, $allowed_types, $max_size) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    return ['valid' => true, 'mime_type' => $mime_type];
}

/**
 * Sanitize filename
 */
function sanitize_filename($filename) {
    return time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $filename);
}

/**
 * Send SMS via Arkassel API
 */
 function send_sms($phone, $message) {
     $api_key = get_config('arkassel_api_key');
    
    if (!$api_key || $api_key === 'ark_xxx' || empty($api_key)) {
        return ['success' => false, 'error' => 'SMS API not configured'];
    }
    
     // Normalize phone and validate
     $normalized_phone = $phone;
     
     // Remove + prefix if present
     if (strpos($normalized_phone, '+') === 0) {
         $normalized_phone = substr($normalized_phone, 1);
     }
     
     if (function_exists('normalize_phone_number')) {
         $normalized_phone = normalize_phone_number($normalized_phone);
     }
 
     if (function_exists('validate_phone_number')) {
         if (!validate_phone_number($phone)) {
             return ['success' => false, 'error' => 'Invalid phone number format. Use Ghana format (e.g., 233XXXXXXXXX, +233XXXXXXXXX, 0XXXXXXXXX)'];
         }
     } else {
         // Fallback validation on normalized phone
         if (!preg_match('/^233[0-9]{9}$/', $normalized_phone)) {
             return ['success' => false, 'error' => 'Invalid phone number format. Use 233XXXXXXXXX'];
         }
     }
     
     // Convert to +233 format for SMS API
     $sms_phone = '+233' . substr($normalized_phone, 3);
    
    // Get sender name from settings
     $sender_name = get_config('sms_sender_name', 'ManuelCode');
     // Sanitize sender: alphanumeric, uppercase, max 11 chars (common SMS gateway rules)
     $sender_name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sender_name));
     $sender_name = substr($sender_name, 0, 11);
    
    // Primary: Arkesel v2 JSON API
    $primary_endpoint = "https://sms.arkesel.com/api/v2/sms/send";
    $ch = curl_init($primary_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'sender' => $sender_name,
        'message' => $message,
        'recipients' => [$sms_phone]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ManuelCode-SMS-API/1.0');
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        // Log the specific error for debugging
        error_log("SMS Primary Endpoint CURL Error: " . $curl_error . " - Endpoint: " . $primary_endpoint);
        
        // If it's a timeout, try fallback immediately
        if (strpos($curl_error, 'timeout') !== false || strpos($curl_error, 'timed out') !== false) {
            error_log("SMS: Timeout detected, trying fallback endpoint immediately");
            // Continue to fallback instead of returning error
        } else {
            return ['success' => false, 'error' => 'Network Error: ' . $curl_error, 'endpoint' => $primary_endpoint];
        }
    }
    
    // Check if response is valid JSON
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If not valid JSON, check if it's an HTML error page
        if (strpos($response, '<!DOCTYPE html>') !== false || strpos($response, '<html>') !== false) {
            return [
                'success' => false, 
                'error' => 'SMS API returned HTML error page (HTTP ' . $http_code . ')',
                'response' => $response,
                'http_code' => $http_code
            ];
        }
        
        return [
            'success' => false, 
            'error' => 'Invalid JSON response from SMS API: ' . json_last_error_msg(),
            'response' => $response,
            'http_code' => $http_code
        ];
    }
    
    // Enhanced success check for Arkesel API responses
    $is_success = false;
    
    // Log the actual response for debugging
    error_log("SMS API Response: " . json_encode($result));
    error_log("SMS HTTP Code: " . $http_code);
    
    // Check various success indicators
    if (isset($result['status'])) {
        $status = strtolower($result['status']);
        $is_success = in_array($status, ['success', 'ok', 'sent', 'delivered']);
        error_log("SMS Status check: {$status} -> " . ($is_success ? 'SUCCESS' : 'FAILED'));
    } elseif (isset($result['success'])) {
        $is_success = ($result['success'] === true || $result['success'] === 'true');
        error_log("SMS Success check: " . ($is_success ? 'SUCCESS' : 'FAILED'));
    } elseif (isset($result['message']) && strpos(strtolower($result['message']), 'success') !== false) {
        $is_success = true;
        error_log("SMS Message check: SUCCESS (contains 'success')");
    } elseif ($http_code === 200) {
        $is_success = true;
        error_log("SMS HTTP 200 check: SUCCESS");
    }
    
    // Additional check: if we got a response and no error message, consider it success
    if (!$is_success && $http_code === 200 && !isset($result['error']) && !isset($result['message'])) {
        $is_success = true;
        error_log("SMS Fallback success check: SUCCESS (200 OK with no error)");
    }

    if ($is_success) {
        return [
            'success' => true,
            'response' => $result,
            'http_code' => $http_code,
            'raw_response' => $response,
            'endpoint_used' => $primary_endpoint
        ];
    }

    // Fallback: Legacy Arkesel endpoint (query/form)
    $fallback_endpoint = 'https://sms.arkesel.com/sms/api';
    $ch = curl_init($fallback_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'send-sms',
        'api_key' => $api_key,
        'to' => $normalized_phone,
        'from' => $sender_name,
        'sms' => $message
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ManuelCode-SMS-API/1.0');
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);

    $response2 = curl_exec($ch);
    $http_code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error2 = curl_error($ch);
    curl_close($ch);

    if ($curl_error2) {
        error_log("SMS Fallback Endpoint CURL Error: " . $curl_error2 . " - Endpoint: " . $fallback_endpoint);
        return [
            'success' => false, 
            'error' => 'Network timeout: Both SMS endpoints are unreachable. Please check your internet connection or try again later.', 
            'primary_error' => $curl_error ?? 'Unknown',
            'fallback_error' => $curl_error2,
            'endpoints_tried' => [$primary_endpoint, $fallback_endpoint]
        ];
    }

    $result2 = json_decode($response2, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response from SMS API (fallback): ' . json_last_error_msg(),
            'response' => $response2,
            'http_code' => $http_code2,
            'endpoint_used' => $fallback_endpoint
        ];
    }

    $is_success2 = false;
    
    // Log fallback response for debugging
    error_log("SMS Fallback API Response: " . json_encode($result2));
    error_log("SMS Fallback HTTP Code: " . $http_code2);
    
    if (isset($result2['status'])) {
        $status2 = strtolower($result2['status']);
        $is_success2 = in_array($status2, ['success', 'ok', 'sent', 'delivered']);
        error_log("SMS Fallback Status check: {$status2} -> " . ($is_success2 ? 'SUCCESS' : 'FAILED'));
    } elseif (isset($result2['success'])) {
        $is_success2 = ($result2['success'] === true || $result2['success'] === 'true');
        error_log("SMS Fallback Success check: " . ($is_success2 ? 'SUCCESS' : 'FAILED'));
    } elseif (isset($result2['message']) && strpos(strtolower($result2['message']), 'success') !== false) {
        $is_success2 = true;
        error_log("SMS Fallback Message check: SUCCESS (contains 'success')");
    } elseif ($http_code2 === 200) {
        $is_success2 = true;
        error_log("SMS Fallback HTTP 200 check: SUCCESS");
    }
    
    // Additional fallback check
    if (!$is_success2 && $http_code2 === 200 && !isset($result2['error']) && !isset($result2['message'])) {
        $is_success2 = true;
        error_log("SMS Fallback final check: SUCCESS (200 OK with no error)");
    }

    return [
        'success' => $is_success2,
        'response' => $result2,
        'http_code' => $http_code2,
        'raw_response' => $response2,
        'endpoint_used' => $fallback_endpoint
    ];
}

/**
 * Convert Google Drive URL to direct image URL for preview
 * @param string $url The Google Drive URL
 * @return string The direct image URL
 */
function convert_google_drive_url($url) {
    // Check if it's a Google Drive URL
    if (strpos($url, 'drive.google.com') !== false) {
        // Extract file ID from various Google Drive URL formats
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9_-]+)/',  // /file/d/{file_id}
            '/id=([a-zA-Z0-9_-]+)/',          // ?id={file_id}
            '/\/d\/([a-zA-Z0-9_-]+)/'         // /d/{file_id}
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $file_id = $matches[1];
                // Convert to direct image URL
                return "https://drive.google.com/uc?export=view&id=" . $file_id;
            }
        }
    }
    
    return $url;
}

/**
 * Check if URL is an image and return appropriate preview URL
 * @param string $url The URL to check
 * @return string The preview URL
 */
function get_image_preview_url($url) {
    if (empty($url)) {
        return '';
    }
    
    // Convert Google Drive URLs
    $converted_url = convert_google_drive_url($url);
    
    // Check if it's an image file
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $url_lower = strtolower($converted_url);
    
    foreach ($image_extensions as $ext) {
        if (strpos($url_lower, '.' . $ext) !== false) {
            return $converted_url;
        }
    }
    
    // If it's Google Drive and we can't determine extension, assume it's an image
    if (strpos($converted_url, 'drive.google.com/uc?export=view') !== false) {
        return $converted_url;
    }
    
    return $url;
}
?>
