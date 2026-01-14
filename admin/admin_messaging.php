<?php
session_start();
include 'auth/check_auth.php';
include '../includes/db.php';
include '../includes/otp_helper.php';

// Set JSON header
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'send_to_all':
            $message = trim($input['message'] ?? '');
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Message is required']);
                exit;
            }
            
            // Get all users with phone numbers
            $stmt = $pdo->prepare("SELECT id, name, phone, user_id FROM users WHERE phone IS NOT NULL AND phone != ''");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sent_count = 0;
            $failed_count = 0;
            
            foreach ($users as $user) {
                // Normalize phone number
                $normalized_phone = normalize_phone_number($user['phone']);
                
                if (validate_phone_number($normalized_phone)) {
                    // Send SMS
                    $sms_result = send_admin_message($normalized_phone, $user['name'], $message);
                    
                    // Log SMS attempt
                    $stmt = $pdo->prepare("
                        INSERT INTO sms_logs (user_id, phone, message, status, response_data, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $log_message = "Admin message to " . $user['name'] . ": " . $message;
                    $stmt->execute([
                        $user['id'],
                        $normalized_phone,
                        $log_message,
                        $sms_result['success'] ? 'sent' : 'failed',
                        json_encode($sms_result)
                    ]);
                    
                    if ($sms_result['success']) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    $failed_count++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'sent_count' => $sent_count,
                'failed_count' => $failed_count,
                'total_users' => count($users)
            ]);
            break;
            
        case 'send_to_user':
            $user_id = (int)($input['user_id'] ?? 0);
            $message = trim($input['message'] ?? '');
            
            if (!$user_id || empty($message)) {
                echo json_encode(['success' => false, 'error' => 'User ID and message are required']);
                exit;
            }
            
            // Get user details
            $stmt = $pdo->prepare("SELECT id, name, phone, user_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            if (empty($user['phone'])) {
                echo json_encode(['success' => false, 'error' => 'User does not have a phone number']);
                exit;
            }
            
            // Normalize phone number
            $normalized_phone = normalize_phone_number($user['phone']);
            
            if (!validate_phone_number($normalized_phone)) {
                echo json_encode(['success' => false, 'error' => 'Invalid phone number format']);
                exit;
            }
            
            // Send SMS
            $sms_result = send_admin_message($normalized_phone, $user['name'], $message);
            
            // Log SMS attempt
            $stmt = $pdo->prepare("
                INSERT INTO sms_logs (user_id, phone, message, status, response_data, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $log_message = "Admin message to " . $user['name'] . ": " . $message;
            $stmt->execute([
                $user['id'],
                $normalized_phone,
                $log_message,
                $sms_result['success'] ? 'sent' : 'failed',
                json_encode($sms_result)
            ]);
            
            if ($sms_result['success']) {
                echo json_encode([
                    'success' => true,
                    'user_name' => $user['name'],
                    'message' => 'SMS sent successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to send SMS: ' . ($sms_result['error'] ?? 'Unknown error')
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Admin messaging error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}

/**
 * Send admin message via SMS
 */
function send_admin_message($phone, $user_name, $message) {
    // Get API key from database
    $api_key = get_config('arkassel_api_key');
    if (empty($api_key) || $api_key === 'ark_xxx') {
        error_log("Admin SMS Error: API key not configured");
        return ['success' => false, 'error' => 'SMS API key not configured'];
    }
    
    // Get sender name from database settings
    $sender_name = get_config('sms_sender_name', 'ManuelCode');
    $sender_name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sender_name));
    $sender_name = substr($sender_name, 0, 11);
    
    // Convert to +233 format for SMS API
    $sms_phone = '+233' . substr($phone, 3);
    
    // Prepare admin message
    $full_message = "Hi {$user_name}, {$message} - ManuelCode Admin";
    
    // Log SMS attempt
    error_log("Admin SMS Attempt - Phone: $sms_phone, User: $user_name, Message: $message");
    
    // Prepare SMS data for Arkesel API v2
    $sms_data = [
        'sender' => $sender_name,
        'message' => $full_message,
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
    error_log("Admin SMS Response - HTTP: $http_code, Response: $response, Error: $curl_error");
    
    // Handle CURL errors
    if ($curl_error) {
        error_log("Admin SMS CURL Error: " . $curl_error);
        return ['success' => false, 'error' => 'Network error: ' . $curl_error];
    }
    
    // Handle HTTP errors
    if ($http_code !== 200) {
        error_log("Admin SMS HTTP Error: " . $http_code . " - " . $response);
        return ['success' => false, 'error' => 'HTTP error: ' . $http_code];
    }
    
    // Parse response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Admin SMS JSON Error: " . json_last_error_msg() . " - " . $response);
        return ['success' => false, 'error' => 'Invalid response format'];
    }
    
    // Check if SMS was sent successfully
    if (isset($result['status']) && $result['status'] === 'success') {
        return [
            'success' => true,
            'message' => 'Admin SMS sent successfully',
            'data' => $result
        ];
    } else {
        $error_msg = isset($result['message']) ? $result['message'] : 'Unknown error';
        return ['success' => false, 'error' => $error_msg];
    }
}
?>
