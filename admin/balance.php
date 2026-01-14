<?php
/**
 * SMS Balance Checker
 * Fetches SMS balance from Arkesel API
 */

// Include database connection
include '../includes/db.php';
include '../includes/util.php';

// Get API key from database settings
$api_key = get_config('arkassel_api_key');

if (empty($api_key) || $api_key === 'ark_xxx') {
    $sms_balance = "API key not configured";
} else {
    // Arkesel API endpoint
    $url = "https://sms.arkesel.com/api/v2/balance";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ManuelCode-SMS-API/1.0');
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Handle errors
    if ($curl_error) {
        $sms_balance = "Network error: " . $curl_error;
    } elseif ($http_code !== 200) {
        $sms_balance = "HTTP error: " . $http_code;
    } else {
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($data['balance'])) {
            $sms_balance = intval($data['balance']);
        } else {
            $sms_balance = "Balance not available";
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => is_numeric($sms_balance),
    'balance' => $sms_balance,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
