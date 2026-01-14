<?php
/**
 * SMS Helper Functions
 * Handles SMS notifications for quote updates and other system notifications
 */

// Get SMS settings from database
function get_sms_settings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM sms_settings");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        // Return default settings if database is not available
        return [
            'sms_provider' => 'arkassel',
            'api_key' => '',
            'sender_id' => 'ManuelCode',
            'is_active' => '0'
        ];
    }
}

/**
 * Send SMS using configured provider
 * @param string $phone_number - Phone number to send SMS to
 * @param string $message - Message content
 * @return array - Response with success status and message
 */
function send_sms($phone_number, $message) {
    // Get SMS settings
    $settings = get_sms_settings();
    
    // Check if SMS is enabled
    if ($settings['is_active'] !== '1') {
        return [
            'success' => false,
            'message' => 'SMS notifications are disabled'
        ];
    }
    
    // Validate phone number
    $phone_number = clean_phone_number($phone_number);
    if (empty($phone_number)) {
        return [
            'success' => false,
            'message' => 'Invalid phone number'
        ];
    }
    
    // Send SMS based on provider
    switch ($settings['sms_provider']) {
        case 'arkassel':
            return send_sms_arkassel($phone_number, $message, $settings);
        case 'twilio':
            return send_sms_twilio($phone_number, $message, $settings);
        default:
            return [
                'success' => false,
                'message' => 'SMS provider not configured'
            ];
    }
}

/**
 * Send SMS using Arkassel API
 */
function send_sms_arkassel($phone_number, $message, $settings) {
    $api_key = $settings['api_key'] ?? '';
    $sender_id = $settings['sender_id'] ?? 'ManuelCode';
    
    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'Arkassel API key not configured'
        ];
    }
    
    $url = 'https://sms.arkassel.com/api/v2/send';
    $data = [
        'api_key' => $api_key,
        'sender_id' => $sender_id,
        'phone' => $phone_number,
        'message' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'success') {
            return [
                'success' => true,
                'message' => 'SMS sent successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send SMS'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'HTTP error: ' . $http_code
        ];
    }
}

/**
 * Send SMS using Twilio API (alternative provider)
 */
function send_sms_twilio($phone_number, $message, $settings) {
    $account_sid = $settings['twilio_account_sid'] ?? '';
    $auth_token = $settings['twilio_auth_token'] ?? '';
    $from_number = $settings['twilio_from_number'] ?? '';
    
    if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
        return [
            'success' => false,
            'message' => 'Twilio credentials not configured'
        ];
    }
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
    $data = [
        'From' => $from_number,
        'To' => $phone_number,
        'Body' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$account_sid}:{$auth_token}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 201) {
        return [
            'success' => true,
            'message' => 'SMS sent successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send SMS via Twilio'
        ];
    }
}

/**
 * Clean and format phone number
 */
function clean_phone_number($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Handle Ghana phone numbers
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        // Convert 0241234567 to +233241234567
        $phone = '+233' . substr($phone, 1);
    } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '233') {
        // Convert 233241234567 to +233241234567
        $phone = '+' . $phone;
    } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '+') {
        // Already in international format
        $phone = $phone;
    } else {
        // Assume it's a local number and add Ghana country code
        $phone = '+233' . $phone;
    }
    
    return $phone;
}

/**
 * Log SMS activity
 */
function log_sms_activity($phone_number, $message, $response) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sms_logs (phone, message, status, response_data, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $status = $response['success'] ? 'sent' : 'failed';
        $response_data = json_encode($response);
        
        $stmt->execute([
            $phone_number,
            $message,
            $status,
            $response_data
        ]);
    } catch (Exception $e) {
        // Log error silently
        error_log("Failed to log SMS activity: " . $e->getMessage());
    }
}

/**
 * Test SMS functionality
 */
function test_sms($phone_number) {
    $message = "This is a test SMS from ManuelCode. If you receive this, SMS functionality is working correctly.";
    return send_sms($phone_number, $message);
}
?>
