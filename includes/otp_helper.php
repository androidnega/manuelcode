<?php
// Include safe cleanup override
if (file_exists(__DIR__ . '/safe_cleanup_override.php')) {
    include_once __DIR__ . '/safe_cleanup_override.php';
}


/**
 * OTP Authentication Helper Functions
 */

// Ensure SMS utilities are available when this helper is included standalone
if (!function_exists('send_sms')) {
    $utilPath = __DIR__ . '/util.php';
    if (file_exists($utilPath)) {
        include_once $utilPath;
    }
}

// Include SMS configuration
$smsConfigPath = __DIR__ . '/../config/sms_config.php';
if (file_exists($smsConfigPath)) {
    include_once $smsConfigPath;
}

/**
 * Generate a 6-digit OTP code
 */
function generate_otp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Normalize phone number to Ghana format
 */
function normalize_phone_number($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Handle different formats
    if (strlen($phone) === 9) {
        // If it's 9 digits (e.g., 248069639), add 233 prefix
        return '233' . $phone;
    } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        // If it's 10 digits starting with 0 (e.g., 0241234567), replace 0 with 233
        return '233' . substr($phone, 1);
    } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '233') {
        // If it's already in 233 format, return as is
        return $phone;
    } elseif (strlen($phone) === 11 && substr($phone, 0, 3) === '233') {
        // If it's 11 digits starting with 233, it's already correct format
        return $phone;
    }
    
    // If none of the above, return original (will be validated later)
    return $phone;
}

/**
 * Format phone number for display with +233 prefix
 */
function format_phone_for_display($phone) {
    // If already starts with +233, return as is
    if (strpos($phone, '+233') === 0) {
        return $phone;
    }
    
    $normalized = normalize_phone_number($phone);
    return '+233' . substr($normalized, 3); // Remove 233 and add +
}

/**
 * Format phone number for database storage (233XXXXXXXXX)
 */
function format_phone_for_storage($phone) {
    return normalize_phone_number($phone);
}

/**
 * Validate phone number format
 */
function validate_phone_number($phone) {
    $normalized = normalize_phone_number($phone);
    // Validate exact format: 233XXXXXXXXX (9 digits after 233)
    return preg_match('/^233[0-9]{9}$/', $normalized);
}

/**
 * Send OTP via SMS
 */
function send_otp_sms($phone, $otp_code, $purpose = 'login', $force_production = false) {
    // Always send real SMS (removed development mode check)
    
    // Normalize phone number
    $normalized_phone = normalize_phone_number($phone);
    
    // Validate phone number
    if (!validate_phone_number($phone)) {
        error_log("OTP SMS Error: Invalid phone number format - $phone");
        return ['success' => false, 'error' => 'Invalid phone number format'];
    }
    
    // Get API key from database
    $api_key = get_config('arkassel_api_key');
    if (empty($api_key) || $api_key === 'ark_xxx') {
        error_log("OTP SMS Error: API key not configured");
        return ['success' => false, 'error' => 'SMS API key not configured. Please configure it in Super Admin settings.'];
    }
    
    // Get sender name from database settings
    $sender_name = get_config('sms_sender_name', 'ManuelCode');
    $sender_name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sender_name));
    $sender_name = substr($sender_name, 0, 11);
    
    // Convert to +233 format for SMS API
    $sms_phone = '+233' . substr($normalized_phone, 3);
    
    // Prepare message based on purpose
    if ($purpose === 'registration') {
        $message = "Welcome to ManuelCode! Your verification code is: {$otp_code}. Valid for 2 minutes 30 seconds.";
    } elseif ($purpose === 'admin_login') {
        $message = "Admin login code: {$otp_code}. Valid for 2 minutes 30 seconds.";
    } else {
        $message = "Your ManuelCode verification code is: {$otp_code}. Valid for 2 minutes 30 seconds.";
    }
    
    // Log SMS attempt
    error_log("OTP SMS Attempt - Phone: $sms_phone, Purpose: $purpose, OTP: $otp_code");
    
    // Prepare SMS data for Arkesel API v2
    $sms_data = [
        'sender' => $sender_name,
        'message' => $message,
        'recipients' => [$sms_phone]
    ];
    
    // Send SMS via Arkesel API v2
    $ch = curl_init('https://sms.arkesel.com/api/v2/sms/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ManuelCode-SMS-API/1.0');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the response for debugging
    error_log("OTP SMS Response - HTTP: $http_code, Response: $response, Error: $curl_error");
    
    // Handle CURL errors
    if ($curl_error) {
        error_log("OTP SMS CURL Error: " . $curl_error);
        return ['success' => false, 'error' => 'Network error: ' . $curl_error];
    }
    
    // Handle HTTP errors
    if ($http_code !== 200) {
        error_log("OTP SMS HTTP Error: " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'HTTP error: ' . $http_code];
    }
    
    // Parse response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("OTP SMS JSON Error: " . json_last_error_msg() . " - " . $response);
        return ['success' => false, 'error' => 'Invalid response format'];
    }
    
    // Check success status
    $is_success = false;
    if (isset($result['status'])) {
        $is_success = (strtolower($result['status']) === 'success');
    } elseif (isset($result['success'])) {
        $is_success = ($result['success'] === true);
    }
    
    if ($is_success) {
        error_log("OTP SMS Success - Message ID: " . ($result['message_id'] ?? 'N/A'));
        
        // Log successful SMS
        log_sms_attempt($sms_phone, $message, 'sent', $result);
        
        return [
            'success' => true,
            'message_id' => $result['message_id'] ?? null,
            'response' => $result
        ];
    } else {
        $error_msg = $result['message'] ?? $result['error'] ?? 'Unknown error';
        error_log("OTP SMS API Error: " . $error_msg);
        
        // Log failed SMS
        log_sms_attempt($sms_phone, $message, 'failed', $result);
        
        return ['success' => false, 'error' => $error_msg];
    }
}

/**
 * Send SMS notification for successful purchase
 */
function send_purchase_sms($phone, $customer_name, $product_title, $order_id, $amount) {
    // Normalize phone number
    $normalized_phone = normalize_phone_number($phone);
    
    // Validate phone number
    if (!validate_phone_number($phone)) {
        error_log("Purchase SMS Error: Invalid phone number format - $phone");
        return ['success' => false, 'error' => 'Invalid phone number format'];
    }
    
    // Get API key from database
    $api_key = get_config('arkassel_api_key');
    if (empty($api_key) || $api_key === 'ark_xxx') {
        error_log("Purchase SMS Error: API key not configured");
        return ['success' => false, 'error' => 'SMS API key not configured'];
    }
    
    // Get sender name from database settings
    $sender_name = get_config('sms_sender_name', 'ManuelCode');
    $sender_name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sender_name));
    $sender_name = substr($sender_name, 0, 11);
    
    // Convert to +233 format for SMS API
    $sms_phone = '+233' . substr($normalized_phone, 3);
    
    // Prepare purchase success message
    $message = "Hi {$customer_name}, your payment of GHS " . number_format($amount, 2) . " for '{$product_title}' has been received. Order ID: {$order_id}. Thank you for choosing ManuelCode!";
    
    // Log SMS attempt
    error_log("Purchase SMS Attempt - Phone: $sms_phone, Order: $order_id, Product: $product_title");
    
    // Prepare SMS data for Arkesel API v2
    $sms_data = [
        'sender' => $sender_name,
        'message' => $message,
        'recipients' => [$sms_phone]
    ];
    
    // Send SMS via Arkesel API v2
    $ch = curl_init('https://sms.arkesel.com/api/v2/sms/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ManuelCode-SMS-API/1.0');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the response for debugging
    error_log("Purchase SMS Response - HTTP: $http_code, Response: $response, Error: $curl_error");
    
    // Handle CURL errors
    if ($curl_error) {
        error_log("Purchase SMS CURL Error: " . $curl_error);
        log_sms_attempt($sms_phone, $message, 'failed', ['error' => $curl_error]);
        return ['success' => false, 'error' => 'Network error: ' . $curl_error];
    }
    
    // Handle HTTP errors
    if ($http_code !== 200) {
        error_log("Purchase SMS HTTP Error: " . $http_code . " - " . $response);
        log_sms_attempt($sms_phone, $message, 'failed', ['http_code' => $http_code, 'response' => $response]);
        return ['success' => false, 'error' => 'HTTP error: ' . $http_code];
    }
    
    // Parse response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Purchase SMS JSON Error: " . json_last_error_msg() . " - " . $response);
        log_sms_attempt($sms_phone, $message, 'failed', ['json_error' => json_last_error_msg(), 'response' => $response]);
        return ['success' => false, 'error' => 'Invalid response format'];
    }
    
    // Check if SMS was sent successfully
    if (isset($result['status']) && $result['status'] === 'success') {
        log_sms_attempt($sms_phone, $message, 'sent', $result);
        return [
            'success' => true,
            'message' => 'Purchase SMS sent successfully',
            'data' => $result
        ];
    } else {
        $error_msg = isset($result['message']) ? $result['message'] : 'Unknown error';
        log_sms_attempt($sms_phone, $message, 'failed', $result);
        return ['success' => false, 'error' => $error_msg];
    }
}

/**
 * Store OTP in database with rate limiting
 */
function store_otp($phone, $email, $otp_code, $purpose) {
    global $pdo;
    
    try {
        // Normalize phone number for storage
        $normalized_phone = normalize_phone_number($phone);
        
        // Check rate limiting - max 3 attempts per hour per phone/email
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM otp_codes 
            WHERE phone = ? AND email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$normalized_phone, $email]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= 3) {
            error_log("OTP Rate Limit Exceeded for phone: $normalized_phone");
            return false;
        }
        
        // Check for recent OTP requests (within last 30 seconds)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM otp_codes 
            WHERE phone = ? AND email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
        ");
        $stmt->execute([$normalized_phone, $email]);
        $recent = $stmt->fetchColumn();
        
        if ($recent > 0) {
            error_log("OTP Cooldown Period - Phone: $normalized_phone");
            return false;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Clear any existing OTPs for this phone/email
        $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE phone = ? OR email = ?");
        $stmt->execute([$normalized_phone, $email]);
        
        // Insert new OTP with 2.5 minutes expiration
        $stmt = $pdo->prepare("
            INSERT INTO otp_codes (phone, email, otp_code, purpose, created_at, expires_at) 
            VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 150 SECOND))
        ");
        $result = $stmt->execute([$normalized_phone, $email, $otp_code, $purpose]);
        
        if ($result) {
            $pdo->commit();
            error_log("OTP stored successfully for phone: $normalized_phone, purpose: $purpose");
        } else {
            $pdo->rollBack();
            error_log("Failed to store OTP for phone: $normalized_phone");
        }
        
        return $result;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error storing OTP: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP code
 */
function verify_otp($phone, $email, $otp_code, $purpose = 'login') {
    global $pdo;
    
    try {
        // Normalize phone number for verification
        $normalized_phone = normalize_phone_number($phone);
        
        // Debug: Log verification parameters
        error_log("OTP Verify - Phone: $normalized_phone, Email: $email, OTP: $otp_code, Purpose: $purpose");
        
        // First, let's check what OTPs exist for this phone/email
        $stmt = $pdo->prepare("
            SELECT id, otp_code, used, expires_at, created_at 
            FROM otp_codes 
            WHERE phone = ? AND email = ? AND purpose = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$normalized_phone, $email, $purpose]);
        $all_otps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("OTP Debug - Found " . count($all_otps) . " OTP records for this phone/email");
        foreach ($all_otps as $otp_record) {
            error_log("OTP Record - ID: {$otp_record['id']}, Code: {$otp_record['otp_code']}, Used: {$otp_record['used']}, Expires: {$otp_record['expires_at']}");
        }
        
        // Now check for the specific OTP
        $stmt = $pdo->prepare("
            SELECT id FROM otp_codes 
            WHERE phone = ? AND email = ? AND otp_code = ? AND purpose = ? 
            AND used = FALSE AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        
        $stmt->execute([$normalized_phone, $email, $otp_code, $purpose]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            error_log("OTP Found - ID: {$result['id']}");
            // Mark OTP as used
            $stmt = $pdo->prepare("UPDATE otp_codes SET used = TRUE WHERE id = ?");
            $stmt->execute([$result['id']]);
            error_log("OTP Marked as Used");
            return true;
        }
        
        // If not found, let's check why
        $stmt = $pdo->prepare("
            SELECT id, used, expires_at 
            FROM otp_codes 
            WHERE phone = ? AND email = ? AND otp_code = ? AND purpose = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$normalized_phone, $email, $otp_code, $purpose]);
        $check_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check_result) {
            if ($check_result['used']) {
                error_log("OTP Already Used - ID: {$check_result['id']}");
            } else {
                error_log("OTP Expired - ID: {$check_result['id']}, Expires: {$check_result['expires_at']}");
            }
        } else {
            error_log("OTP Not Found - No matching OTP record");
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error verifying OTP: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if phone number is duplicate across all user types
 */
function check_phone_duplicate($phone) {
    global $pdo;
    
    // Normalize phone number for lookup
    $normalized_phone = normalize_phone_number($phone);
    
    // Check in users table
    $stmt = $pdo->prepare("SELECT id, name, 'user' as user_type FROM users WHERE phone = ?");
    $stmt->execute([$normalized_phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        return [
            'is_duplicate' => true,
            'user_type' => 'user',
            'user_data' => $user
        ];
    }
    
    // Check in admins table
    $stmt = $pdo->prepare("SELECT id, name, role, 'admin' as user_type FROM admins WHERE phone = ?");
    $stmt->execute([$normalized_phone]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        return [
            'is_duplicate' => true,
            'user_type' => $admin['role'] === 'superadmin' ? 'super admin' : 'admin',
            'user_data' => $admin
        ];
    }
    
    // No duplicate found
    return [
        'is_duplicate' => false,
        'user_type' => null,
        'user_data' => null
    ];
}

/**
 * Log user activity to database
 * Note: This is a legacy function. Use log_user_activity() from logger.php for new code.
 */
function log_user_activity_legacy($user_id, $activity_type, $description = '') {
    global $pdo;
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$user_id, $activity_type, $description, $ip_address, $user_agent]);
        return true;
    } catch (Exception $e) {
        error_log("Error logging user activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user exists by phone number
 */
function user_exists_by_phone($phone) {
    global $pdo;
    
    // Normalize phone number for lookup
    $normalized_phone = normalize_phone_number($phone);
    
    $stmt = $pdo->prepare("SELECT id, name, email, registration_completed FROM users WHERE phone = ?");
    $stmt->execute([$normalized_phone]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create or update user session
 */
function create_user_session($user_id) {
    global $pdo;
    
    // Delete existing sessions for this user
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Create new session
    $session_token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, session_id, is_active, login_time, last_activity) 
        VALUES (?, ?, 1, NOW(), NOW())
    ");
    
    if ($stmt->execute([$user_id, $session_token])) {
        return $session_token;
    }
    
    return false;
}

/**
 * Validate session token
 */
function validate_session_token($session_token) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT user_id FROM user_sessions 
        WHERE session_id = ? AND is_active = 1 AND last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    $stmt->execute([$session_token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['user_id'] : false;
}

/**
 * Log SMS attempt to database
 */
function log_sms_attempt($phone, $message, $status, $response = null, $user_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sms_logs (user_id, phone, message, status, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $response_data = $response ? json_encode($response) : null;
        $stmt->execute([$user_id, $phone, $message, $status]);
        
        error_log("SMS Logged - Phone: $phone, Status: $status");
        return true;
    } catch (Exception $e) {
        error_log("Error logging SMS: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired OTPs and sessions
 */
function cleanup_expired_data() {
    global $pdo;
    
    // Delete expired OTPs
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE expires_at < NOW()");
    $stmt->execute();
    
    // Delete old inactive sessions (older than 30 days)
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
}
?>
