<?php
session_start();
include '../includes/db.php';
include '../includes/util.php';
include '../config/payment_config.php';
include '../config/sms_config.php';

// Enhanced logging for debugging
error_log("=== SUBMISSION CALLBACK START ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("GET data: " . json_encode($_GET));
error_log("POST data: " . json_encode($_POST));
error_log("SERVER: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Also write to file for easier debugging
file_put_contents('submission_callback.log', 
    "=== SUBMISSION CALLBACK ===\n" .
    "Time: " . date('Y-m-d H:i:s') . "\n" .
    "GET: " . json_encode($_GET) . "\n" .
    "POST: " . json_encode($_POST) . "\n" .
    "SERVER: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n" .
    "================================\n", 
    FILE_APPEND
);

// Get the reference from Paystack callback
$reference = $_GET['reference'] ?? $_POST['reference'] ?? '';

if (empty($reference)) {
    error_log("No reference provided in submission callback");
    header('Location: ../submission.php?error=no_reference');
    exit;
}

try {
    // Verify payment with Paystack
    $verification_result = verifyPaystackPayment($reference);
    
    if ($verification_result['success']) {
        $transaction_data = $verification_result['data'];
        $amount = $transaction_data['amount'] / 100; // Convert from kobo to pesewas
        
        // Get expected submission price from settings
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
            $stmt->execute(['submission_price']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $expected_amount = $result ? floatval($result['value']) : 0.01;
        } catch (Exception $e) {
            $expected_amount = 0.01; // Default fallback
        }
        
        // Check if this is a submission payment
        if (abs($amount - $expected_amount) > 0.01) {
            error_log("Invalid amount for submission payment: Expected " . $expected_amount . ", Got " . $amount);
            header('Location: ../submission.php?error=invalid_amount');
            exit;
        }
        
        // Check if submission already exists
        $stmt = $pdo->prepare("SELECT id FROM submissions WHERE reference = ?");
        $stmt->execute([$reference]);
        
        if ($stmt->fetch()) {
            error_log("Submission with reference {$reference} already exists");
            header('Location: ../submission_success.php?ref=' . urlencode($reference));
            exit;
        }
        
        // Get metadata from transaction
        $metadata = $transaction_data['metadata'] ?? [];
        
        // Extract submission data from metadata
        $name = $metadata['name'] ?? '';
        $index_number = $metadata['index_number'] ?? '';
        $phone = $metadata['phone'] ?? '';
        $file_path = $metadata['file_path'] ?? '';
        $file_name = $metadata['file_name'] ?? '';
        $file_size = $metadata['file_size'] ?? 0;
        $file_type = $metadata['file_type'] ?? '';
        
        // Validate required data
        if (empty($name) || empty($index_number) || empty($phone) || empty($file_path)) {
            error_log("Missing required submission data in metadata");
            header('Location: ../submission.php?error=missing_data');
            exit;
        }
        
        // Check if file exists
        if (!file_exists('../' . $file_path)) {
            error_log("Submitted file not found: " . $file_path);
            header('Location: ../submission.php?error=file_not_found');
            exit;
        }
        
        // Save submission to database
        $stmt = $pdo->prepare("
            INSERT INTO submissions (
                name, index_number, phone_number, file_path, file_name, 
                file_size, file_type, amount, status, reference, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, NOW())
        ");
        
        if ($stmt->execute([
            $name, $index_number, $phone, $file_path, $file_name,
            $file_size, $file_type, $amount, $reference
        ])) {
            $submission_id = $pdo->lastInsertId();
            
                         // Send SMS confirmation
             $message = "Hello {$name}, your project report document submission has been received successfully. Reference: {$reference}";
            $sms_result = send_sms_improved($phone, $message);
            
            // Log SMS notification
            $stmt = $pdo->prepare("
                INSERT INTO submission_notifications (
                    submission_id, phone_number, message, status, sms_response, sent_at, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $sms_status = $sms_result['success'] ? 'sent' : 'failed';
            $stmt->execute([
                $submission_id, $phone, $message, $sms_status, json_encode($sms_result)
            ]);
            
            // Log the successful submission
            error_log("Submission completed successfully: ID {$submission_id}, Reference {$reference}");
            
            // Clear session data
            unset($_SESSION['submission_data']);
            
            // Redirect to success page
            header('Location: ../submission_success.php?ref=' . urlencode($reference));
            exit;
            
        } else {
            error_log("Failed to save submission to database");
            header('Location: ../submission.php?error=save_failed');
            exit;
        }
        
    } else {
        // Payment verification failed
        error_log("Payment verification failed for reference {$reference}: " . $verification_result['message']);
        
        // Update submission status to failed if it exists
        $stmt = $pdo->prepare("UPDATE submissions SET status = 'failed' WHERE reference = ?");
        $stmt->execute([$reference]);
        
        header('Location: ../submission.php?error=payment_failed');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error processing submission callback: " . $e->getMessage());
    header('Location: ../submission.php?error=processing_error');
    exit;
}
?>
