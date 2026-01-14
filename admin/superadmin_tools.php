<?php
// Superadmin tools API
error_reporting(0); // Suppress errors for API responses
ob_start();
header('Content-Type: application/json');

// Ensure session path is correct
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
session_start();

// Debug session variables (temporary)
error_log("Superadmin tools session variables: " . print_r($_SESSION, true));
include '../includes/db.php';
include '../includes/util.php';
include_once '../includes/auto_config.php';

// Check if user is logged in as admin (multiple possible session variables)
$is_authenticated = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  $is_authenticated = true;
} elseif (isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true) {
  $is_authenticated = true;
} elseif (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
  $is_authenticated = true;
} elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
  $is_authenticated = true;
}

// For debugging, let's also check if we're in a development environment
if (!$is_authenticated && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')) {
  // In development, allow access if we have any session data
  if (!empty($_SESSION)) {
    $is_authenticated = true;
  }
}

if (!$is_authenticated) {
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Not authenticated']);
  exit;
}

// Check if user is superadmin or has superadmin access
$is_superadmin = ($_SESSION['user_role'] ?? 'user') === 'superadmin';
$has_superadmin_access = isset($_SESSION['superadmin_access']) && $_SESSION['superadmin_access'] === true;

if (!$is_superadmin && !$has_superadmin_access) {
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Forbidden - Superadmin access required']);
  exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
  switch ($action) {
    case 'clean_logs':
      if (function_exists('clean_old_logs')) {
        $cleaned = clean_old_logs();
      } else {
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $cleaned = $stmt->rowCount();
      }
      echo json_encode(['success'=>true,'cleaned'=>$cleaned]);
      break;
      
    case 'clear_sms_data':
      // Clear all SMS logs
      $stmt = $pdo->prepare("DELETE FROM sms_logs");
      $stmt->execute();
      $sms_cleared = $stmt->rowCount();
      
      // Clear all OTP codes
      $stmt = $pdo->prepare("DELETE FROM otp_codes");
      $stmt->execute();
      $otp_cleared = $stmt->rowCount();
      
      // Log the action
      $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
      $stmt->execute(['INFO', 'SYSTEM', "SMS data cleared: $sms_cleared SMS logs, $otp_cleared OTP codes deleted"]);
      
      echo json_encode(['success'=>true,'sms_cleared'=>$sms_cleared,'otp_cleared'=>$otp_cleared]);
      break;
      
    case 'clear_all_logs':
      // Clear all system logs
      $stmt = $pdo->prepare("DELETE FROM system_logs");
      $stmt->execute();
      $logs_cleared = $stmt->rowCount();
      
      // Clear all SMS logs
      $stmt = $pdo->prepare("DELETE FROM sms_logs");
      $stmt->execute();
      $sms_cleared = $stmt->rowCount();
      
      // Clear all OTP codes
      $stmt = $pdo->prepare("DELETE FROM otp_codes");
      $stmt->execute();
      $otp_cleared = $stmt->rowCount();
      
      echo json_encode(['success'=>true,'logs_cleared'=>$logs_cleared,'sms_cleared'=>$sms_cleared,'otp_cleared'=>$otp_cleared]);
      break;
      
        case 'system_cleanup':
      // Comprehensive system cleanup - clear all data except super admin account
      try {
        // Get super admin ID to preserve
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE role = 'superadmin' ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $super_admin_id = $stmt->fetchColumn();
        
        if (!$super_admin_id) {
          throw new Exception('No super admin account found to preserve');
        }
        
        $cleaned_data = [];
        
        // Helper function to safely delete from table
        function safeDeleteFromTable($pdo, $table_name, $where_clause = null, $params = []) {
          try {
            if ($where_clause) {
              $stmt = $pdo->prepare("DELETE FROM $table_name WHERE $where_clause");
              $stmt->execute($params);
            } else {
              $stmt = $pdo->prepare("DELETE FROM $table_name");
              $stmt->execute();
            }
            return $stmt->rowCount();
          } catch (Exception $e) {
            // Table doesn't exist or other error, return 0
            return 0;
          }
        }
        
        // Helper function to safely reset auto-increment
        function safeResetAutoIncrement($pdo, $table_name) {
          try {
            $stmt = $pdo->prepare("ALTER TABLE $table_name AUTO_INCREMENT = 1");
            $stmt->execute();
            return true;
          } catch (Exception $e) {
            // Table doesn't exist or other error, ignore
            return false;
          }
        }
        
        // 1. Clear all user data (except super admin)
        $cleaned_data['users'] = safeDeleteFromTable($pdo, 'users', 'id != ?', [$super_admin_id]);
        
        // 2. Clear all purchases/orders
        $cleaned_data['purchases'] = safeDeleteFromTable($pdo, 'purchases');
        
        // 3. Clear all guest orders
        $cleaned_data['guest_orders'] = safeDeleteFromTable($pdo, 'guest_orders');
        
        // 4. Clear all support tickets and replies
        $cleaned_data['support_replies'] = safeDeleteFromTable($pdo, 'support_replies');
        $cleaned_data['support_tickets'] = safeDeleteFromTable($pdo, 'support_tickets');
        
        // 5. Clear all refunds
        $cleaned_data['refunds'] = safeDeleteFromTable($pdo, 'refunds');
        $cleaned_data['refund_logs'] = safeDeleteFromTable($pdo, 'refund_logs');
        
        // 6. Clear all download data
        $cleaned_data['download_logs'] = safeDeleteFromTable($pdo, 'download_logs');
        $cleaned_data['download_tokens'] = safeDeleteFromTable($pdo, 'download_tokens');
        
        // 7. Clear all SMS and OTP data
        $cleaned_data['sms_logs'] = safeDeleteFromTable($pdo, 'sms_logs');
        $cleaned_data['otp_codes'] = safeDeleteFromTable($pdo, 'otp_codes');
        
        // 8. Clear all payment logs
        $cleaned_data['payment_logs'] = safeDeleteFromTable($pdo, 'payment_logs');
        
        // 9. Clear all purchase logs
        $cleaned_data['purchase_logs'] = safeDeleteFromTable($pdo, 'purchase_logs');
        
        // 10. Clear all user notifications
        $cleaned_data['user_notifications'] = safeDeleteFromTable($pdo, 'user_notifications');
        $cleaned_data['user_notification_preferences'] = safeDeleteFromTable($pdo, 'user_notification_preferences');
        $cleaned_data['product_notifications'] = safeDeleteFromTable($pdo, 'product_notifications');
        
        // 11. Clear all user activity
        $cleaned_data['user_activity'] = safeDeleteFromTable($pdo, 'user_activity');
        
        // 12. Clear all user sessions
        $cleaned_data['user_sessions'] = safeDeleteFromTable($pdo, 'user_sessions');
        
        // 13. Clear all coupon usage
        $cleaned_data['coupon_usage'] = safeDeleteFromTable($pdo, 'coupon_usage');
        
        // 14. Clear all system logs
        $cleaned_data['system_logs'] = safeDeleteFromTable($pdo, 'system_logs');
        
        // 15. Clear all IP management data
        $cleaned_data['ip_management'] = safeDeleteFromTable($pdo, 'ip_management');
        
        // 16. Clear all product updates
        $cleaned_data['product_updates'] = safeDeleteFromTable($pdo, 'product_updates');
        
        // 17. Clear all admin accounts except super admin
        $cleaned_data['admins'] = safeDeleteFromTable($pdo, 'admins', 'id != ? AND role != ?', [$super_admin_id, 'superadmin']);
        
        // 18. Reset auto-increment counters for clean IDs
        $tables_to_reset = [
          'users', 'purchases', 'guest_orders', 'support_tickets', 'support_replies',
          'refunds', 'refund_logs', 'download_logs', 'download_tokens', 'sms_logs',
          'otp_codes', 'payment_logs', 'purchase_logs', 'user_notifications',
          'user_notification_preferences', 'product_notifications', 'user_activity',
          'user_sessions', 'coupon_usage', 'system_logs', 'ip_management', 'product_updates'
        ];
        
        foreach ($tables_to_reset as $table) {
          safeResetAutoIncrement($pdo, $table);
        }
        
        // Log the cleanup action (only if system_logs table exists)
        try {
          $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
          $stmt->execute(['WARNING', 'SYSTEM', 'Complete system cleanup performed - all data cleared except super admin account']);
        } catch (Exception $e) {
          // system_logs table might not exist, ignore
        }
        
        echo json_encode([
          'success' => true,
          'message' => 'System cleanup completed successfully',
          'cleaned_data' => $cleaned_data,
          'super_admin_preserved' => $super_admin_id
        ]);
        
      } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'System cleanup failed: ' . $e->getMessage()]);
      }
      break;
      $otp_cleared = $stmt->rowCount();
      
      // Clear user activity logs
      $stmt = $pdo->prepare("DELETE FROM user_activity_logs");
      $stmt->execute();
      $activity_cleared = $stmt->rowCount();
      
      echo json_encode(['success'=>true,'logs_cleared'=>$logs_cleared,'sms_cleared'=>$sms_cleared,'otp_cleared'=>$otp_cleared,'activity_cleared'=>$activity_cleared]);
      break;
      
    case 'check_sms_balance':
      // Get API key from database
      $api_key = get_config('arkassel_api_key');
      if (empty($api_key) || $api_key === 'ark_xxx') {
          echo json_encode(['success' => false, 'error' => 'SMS API key not configured']);
          break;
      }
      
      // Check SMS balance via Arkesel API
      $ch = curl_init('https://sms.arkesel.com/api/v2/balance');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Accept: application/json',
          'api-key: ' . $api_key
      ]);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_USERAGENT, 'ManuelCode-SMS-API/1.0');
      
      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curl_error = curl_error($ch);
      curl_close($ch);
      
      // Handle CURL errors
      if ($curl_error) {
          error_log("SMS Balance Check CURL Error: " . $curl_error);
          echo json_encode(['success' => false, 'error' => 'Network error: ' . $curl_error]);
          break;
      }
      
      // Handle HTTP errors
      if ($http_code !== 200) {
          error_log("SMS Balance Check HTTP Error: " . $http_code . " - " . $response);
          echo json_encode(['success' => false, 'error' => 'HTTP error: ' . $http_code]);
          break;
      }
      
      // Parse response
      $result = json_decode($response, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
          error_log("SMS Balance Check JSON Error: " . json_last_error_msg() . " - " . $response);
          echo json_encode(['success' => false, 'error' => 'Invalid response format']);
          break;
      }
      
      // Check if balance is available in response
      if (isset($result['balance'])) {
          $balance = intval($result['balance']);
          echo json_encode(['success' => true, 'balance' => $balance]);
      } else {
          error_log("SMS Balance Check - No balance in response: " . $response);
          echo json_encode(['success' => false, 'error' => 'Balance not found in response']);
      }
      break;
      
    case 'validate':
      // Comprehensive API validation
      $results = [];
      
      // Validate SMS API (Arkesel)
      $ark = get_config('arkassel_api_key');
      $sms_test = ['reachable'=>false, 'configured'=>false, 'message'=>'Not configured'];
      if ($ark && $ark !== 'ark_xxx') {
        $sms_test['configured'] = true;
        try {
          // Test SMS API connectivity
          $ch = curl_init('https://sms.arkesel.com/api/v2/balance');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
              'Accept: application/json',
              'api-key: ' . $ark
          ]);
          curl_setopt($ch, CURLOPT_TIMEOUT, 10);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          
          $response = curl_exec($ch);
          $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
          
          if ($http_code === 200) {
            $sms_test['reachable'] = true;
            $sms_test['message'] = 'SMS API is working';
          } else {
            $sms_test['message'] = 'SMS API returned HTTP ' . $http_code;
          }
        } catch (Exception $e) {
          $sms_test['message'] = 'SMS API error: ' . $e->getMessage();
        }
      }
      $results['sms'] = $sms_test;
      
      // Validate Payment API (Paystack)
      $paystack_key = get_config('paystack_secret_key');
      $payment_test = ['reachable'=>false, 'configured'=>false, 'message'=>'Not configured'];
      if ($paystack_key && $paystack_key !== 'sk_test_xxx') {
        $payment_test['configured'] = true;
        try {
          // Test Paystack API connectivity
          $ch = curl_init('https://api.paystack.co/transaction/verify/1234567890');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
              'Authorization: Bearer ' . $paystack_key,
              'Cache-Control: no-cache'
          ]);
          curl_setopt($ch, CURLOPT_TIMEOUT, 10);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          
          $response = curl_exec($ch);
          $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
          
          if ($http_code === 200 || $http_code === 404) {
            $payment_test['reachable'] = true;
            $payment_test['message'] = 'Payment API is working';
          } else {
            $payment_test['message'] = 'Payment API returned HTTP ' . $http_code;
          }
        } catch (Exception $e) {
          $payment_test['message'] = 'Payment API error: ' . $e->getMessage();
        }
      }
      $results['payment'] = $payment_test;
      
      // Validate Database Connection
      $db_test = ['reachable'=>false, 'message'=>'Database connection failed'];
      try {
        $pdo->query("SELECT 1");
        $db_test['reachable'] = true;
        $db_test['message'] = 'Database connection is working';
      } catch (Exception $e) {
        $db_test['message'] = 'Database error: ' . $e->getMessage();
      }
      $results['database'] = $db_test;
      
      // Log the validation attempt
      $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
      $stmt->execute(['INFO', 'SYSTEM', 'API validation completed']);
      
      echo json_encode(['success'=>true, 'results'=>$results]);
      break;
      
    case 'set_maintenance_mode':
      $mode = $_POST['mode'] ?? '';
      $message = $_POST['message'] ?? '';
      $start = $_POST['start_datetime'] ?? '';
      $end = $_POST['end_datetime'] ?? '';
      $logo = $_POST['logo_url'] ?? '';
      $icon = $_POST['icon'] ?? '';
      $allowed_modes = ['maintenance', 'coming_soon', 'update', 'standard'];
      
      if (!in_array($mode, $allowed_modes)) {
        echo json_encode(['success'=>false,'error'=>'Invalid mode']);
        break;
      }
      
      // Update or insert maintenance mode setting
      $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('site_mode', ?) ON DUPLICATE KEY UPDATE value = ?");
      $stmt->execute([$mode, $mode]);
      
      // Update maintenance message if provided
      if ($message) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('maintenance_message', ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$message, $message]);
      }
      
      // Optional scheduling fields
      if ($start) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('maintenance_start', ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$start, $start]);
      }
      if ($end) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('maintenance_end', ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$end, $end]);
      }
      
      // Optional branding
      if ($logo) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('maintenance_logo', ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$logo, $logo]);
      }
      if ($icon) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('maintenance_icon', ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$icon, $icon]);
      }
      
      // Log the action
      $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
      $stmt->execute(['INFO', 'SYSTEM', "Site mode changed to: $mode"]);
      
      echo json_encode(['success'=>true,'mode'=>$mode]);
      break;
      
    case 'get_maintenance_status':
      $stmt = $pdo->prepare("SELECT setting_key, value FROM settings WHERE setting_key IN ('site_mode', 'maintenance_message', 'maintenance_start', 'maintenance_end', 'maintenance_logo', 'maintenance_icon')");
      $stmt->execute();
      $settings = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['value'];
      }
      echo json_encode(['success'=>true,'settings'=>$settings]);
      break;
      
    case 'search_purchases':
      $search_type = $_POST['search_type'] ?? '';
      $search_value = $_POST['search_value'] ?? '';
      
      // Debug logging
      error_log("Search request - Type: $search_type, Value: $search_value");
      
      if (empty($search_type) || empty($search_value)) {
        echo json_encode(['success'=>false,'error'=>'Search type and value are required']);
        break;
      }
      
      // Log the search attempt
      $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
      $stmt->execute(['INFO', 'ADMIN', "Purchase search attempted - Type: $search_type, Value: $search_value"]);
      
      try {
        $where_clause = '';
        $params = [];
        
        switch ($search_type) {
          case 'order_id':
            $where_clause = 'p.id = ?';
            $params = [intval($search_value)];
            break;
            
          case 'user_id':
            $where_clause = 'p.user_id = ?';
            $params = [intval($search_value)];
            break;
            
          case 'email':
            $where_clause = 'u.email LIKE ?';
            $params = ['%' . $search_value . '%'];
            break;
            
          default:
            echo json_encode(['success'=>false,'error'=>'Invalid search type']);
            break 2;
        }
        
        $query = "
          SELECT p.*, pr.title as product_title, pr.price, u.name as user_name, u.email as user_email, u.user_id
          FROM purchases p 
          JOIN products pr ON p.product_id = pr.id 
          LEFT JOIN users u ON p.user_id = u.id
          WHERE $where_clause
          ORDER BY p.created_at DESC
          LIMIT 50
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug logging
        error_log("Search query executed. Found " . count($purchases) . " results");
        
        // Log the search results
        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
        $stmt->execute(['INFO', 'ADMIN', "Purchase search completed - Found " . count($purchases) . " results for $search_type: $search_value"]);
        
        echo json_encode(['success'=>true,'purchases'=>$purchases]);
        
      } catch (Exception $e) {
        // Log the error
        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
        $stmt->execute(['ERROR', 'ADMIN', "Purchase search failed: " . $e->getMessage()]);
        
        echo json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]);
      }
      break;
      
    case 'manage_user':
      $user_id = $_POST['user_id'] ?? '';
      $action = $_POST['user_action'] ?? '';
      
      if (empty($user_id) || empty($action)) {
        echo json_encode(['success'=>false,'error'=>'User ID and action are required']);
        break;
      }
      
      try {
        switch ($action) {
          case 'suspend':
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "User suspended successfully";
            break;
            
          case 'activate':
            $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "User activated successfully";
            break;
            
          case 'delete':
            // Delete user and all related data
            try {
              $pdo->beginTransaction();
              
              // Delete all related records first (in proper order to avoid foreign key constraints)
              
              // 1. Delete support ticket responses
              $stmt = $pdo->prepare("DELETE FROM support_replies WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 2. Delete support tickets
              $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 3. Delete refund requests
              $stmt = $pdo->prepare("DELETE FROM refund_requests WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 4. Delete refunds
              $stmt = $pdo->prepare("DELETE FROM refunds WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 5. Delete refund logs
              $stmt = $pdo->prepare("DELETE FROM refund_logs WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 6. Delete download logs
              $stmt = $pdo->prepare("DELETE FROM download_logs WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 7. Delete download tokens
              $stmt = $pdo->prepare("DELETE FROM download_tokens WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 8. Delete SMS logs
              $stmt = $pdo->prepare("DELETE FROM sms_logs WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 9. Delete payment logs
              $stmt = $pdo->prepare("DELETE FROM payment_logs WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 10. Delete purchase logs
              $stmt = $pdo->prepare("DELETE FROM purchase_logs WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 11. Delete user notifications
              $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 12. Delete notification preferences
              $stmt = $pdo->prepare("DELETE FROM user_notification_preferences WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 13. Delete user activity
              $stmt = $pdo->prepare("DELETE FROM user_activity WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
              // 14. Delete user sessions
              $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
              $stmt->execute([$user_id]);
              
                          // 15. Delete OTP codes
            $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE email = (SELECT email FROM users WHERE id = ?)");
            $stmt->execute([$user_id]);
            
            // 16. Delete coupon usage
            $stmt = $pdo->prepare("DELETE FROM coupon_usage WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
                        // 17. Delete purchases (orders)
            $stmt = $pdo->prepare("DELETE FROM purchases WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // 18. Delete guest orders by email and phone (if user had any guest purchases)
            $stmt = $pdo->prepare("DELETE FROM guest_orders WHERE email = (SELECT email FROM users WHERE id = ?) OR phone = (SELECT phone FROM users WHERE id = ?)");
            $stmt->execute([$user_id, $user_id]);
            
            // 19. Finally delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
              if ($stmt->execute([$user_id])) {
                $pdo->commit();
                $message = "User and all related data deleted successfully";
              } else {
                $pdo->rollback();
                echo json_encode(['success'=>false,'error'=>'Failed to delete user']);
                break;
              }
            } catch (Exception $e) {
              $pdo->rollback();
              echo json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]);
              break;
            }
            break;
            
          case 'reset_password':
            // Generate a random password
            $new_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $message = "Password reset successfully. New password: $new_password";
            break;
            
          case 'get_details':
            $stmt = $pdo->prepare("
              SELECT u.*, 
                     COUNT(p.id) as total_purchases,
                     SUM(p.amount) as total_spent,
                     MAX(p.created_at) as last_purchase
              FROM users u 
              LEFT JOIN purchases p ON u.id = p.user_id
              WHERE u.id = ?
              GROUP BY u.id
            ");
            $stmt->execute([$user_id]);
            $user_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user_details) {
              echo json_encode(['success'=>false,'error'=>'User not found']);
              break;
            }
            
            // Get user's purchase history
            $stmt = $pdo->prepare("
              SELECT p.*, pr.title as product_title, pr.price
              FROM purchases p 
              JOIN products pr ON p.product_id = pr.id
              WHERE p.user_id = ?
              ORDER BY p.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $user_details['purchases'] = $purchases;
            
            echo json_encode(['success'=>true,'user_details'=>$user_details]);
            break;
            
          default:
            echo json_encode(['success'=>false,'error'=>'Invalid user action']);
            break;
        }
        
        if (isset($message)) {
          // Log the action
          $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
          $stmt->execute(['INFO', 'SUPERADMIN', "User management action: $action on user ID $user_id - $message"]);
          
          echo json_encode(['success'=>true,'message'=>$message]);
        }
        
      } catch (Exception $e) {
        // Log the error
        $stmt = $pdo->prepare("INSERT INTO system_logs (level, category, message) VALUES (?, ?, ?)");
        $stmt->execute(['ERROR', 'SUPERADMIN', "User management failed: " . $e->getMessage()]);
        
        echo json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]);
      }
      break;
      
    default:
      echo json_encode(['success'=>false,'error'=>'Unknown action']);
  }
} catch (Exception $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>


