<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_purchases_json = $_POST['selected_purchases'] ?? '[]';
    $selected_guests_json = $_POST['selected_guests'] ?? '[]';
    
    $selected_purchases = json_decode($selected_purchases_json, true) ?: [];
    $selected_guests = json_decode($selected_guests_json, true) ?: [];
    
    if (empty($selected_purchases) && empty($selected_guests)) {
        header('Location: generate_receipts.php?error=No items selected');
        exit();
    }
    
    // Create ZIP file
    $zip = new ZipArchive();
    $zip_filename = 'receipts_' . date('Y-m-d_H-i-s') . '.zip';
    $zip_path = sys_get_temp_dir() . '/' . $zip_filename;
    
    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
        header('Location: generate_receipts.php?error=Could not create ZIP file');
        exit();
    }
    
    // Add user purchase receipts
    if (!empty($selected_purchases)) {
        $placeholders = str_repeat('?,', count($selected_purchases) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as user_name, u.email as user_email, pr.title as product_title, pr.price as product_price
            FROM purchases p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN products pr ON p.product_id = pr.id
            WHERE p.id IN ($placeholders)
        ");
        $stmt->execute($selected_purchases);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($purchases as $purchase) {
            $receipt_content = generateReceiptContent($purchase, 'user');
            $filename = 'User_Receipt_' . $purchase['id'] . '_' . date('Y-m-d', strtotime($purchase['created_at'])) . '.html';
            $zip->addFromString($filename, $receipt_content);
        }
    }
    
    // Add guest order receipts
    if (!empty($selected_guests)) {
        $placeholders = str_repeat('?,', count($selected_guests) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT go.*, pr.title as product_title, pr.price as product_price
            FROM guest_orders go
            LEFT JOIN products pr ON go.product_id = pr.id
            WHERE go.id IN ($placeholders)
        ");
        $stmt->execute($selected_guests);
        $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($guests as $guest) {
            $receipt_content = generateReceiptContent($guest, 'guest');
            $filename = 'Guest_Receipt_' . $guest['id'] . '_' . date('Y-m-d', strtotime($guest['created_at'])) . '.html';
            $zip->addFromString($filename, $receipt_content);
        }
    }
    
    $zip->close();
    
    // Download the ZIP file
    if (file_exists($zip_path)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($zip_path);
        unlink($zip_path); // Delete temporary file
        exit();
    } else {
        header('Location: generate_receipts.php?error=Could not create ZIP file');
        exit();
    }
}

function generateReceiptContent($data, $type) {
    if ($type === 'user') {
        $customer_name = $data['user_name'];
        $customer_email = $data['user_email'];
        $amount = $data['amount'] ?? $data['product_price'];
    } else {
        $customer_name = $data['name'];
        $customer_email = $data['email'];
        $amount = $data['total_amount'];
    }
    
    $receipt_number = $data['receipt_number'] ?? 'N/A';
    
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - ' . htmlspecialchars($receipt_number) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .receipt { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .receipt-number { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-section h3 { margin: 0 0 15px 0; color: #333; }
        .info-item { margin-bottom: 8px; }
        .label { font-weight: bold; color: #666; }
        .value { color: #333; }
        .product-section { border-top: 1px solid #eee; padding-top: 20px; margin-bottom: 20px; }
        .product-item { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .total-section { border-top: 2px solid #eee; padding-top: 20px; text-align: right; }
        .total-amount { font-size: 24px; font-weight: bold; color: #28a745; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>ManuelCode</h1>
            <p>Digital Product Receipt</p>
            <p style="font-size: 14px; color: #666;">' . date('F j, Y \a\t g:i A', strtotime($data['created_at'])) . '</p>
        </div>
        
        <div class="receipt-number">
            <strong>Receipt Number:</strong> ' . htmlspecialchars($receipt_number) . '
        </div>
        
        <div class="info-grid">
            <div class="info-section">
                <h3>Customer Information</h3>
                <div class="info-item">
                    <span class="label">Name:</span>
                    <span class="value">' . htmlspecialchars($customer_name) . '</span>
                </div>
                <div class="info-item">
                    <span class="label">Email:</span>
                    <span class="value">' . htmlspecialchars($customer_email) . '</span>
                </div>
                <div class="info-item">
                    <span class="label">Customer Type:</span>
                    <span class="value">' . ucfirst($type) . '</span>
                </div>
            </div>
            
            <div class="info-section">
                <h3>Order Information</h3>
                <div class="info-item">
                    <span class="label">Order ID:</span>
                    <span class="value">#' . $data['id'] . '</span>
                </div>
                <div class="info-item">
                    <span class="label">Order Date:</span>
                    <span class="value">' . date('F j, Y', strtotime($data['created_at'])) . '</span>
                </div>
                <div class="info-item">
                    <span class="label">Order Time:</span>
                    <span class="value">' . date('g:i A', strtotime($data['created_at'])) . '</span>
                </div>
            </div>
        </div>
        
        <div class="product-section">
            <h3>Product Details</h3>
            <div class="product-item">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0 0 5px 0;">' . htmlspecialchars($data['product_title']) . '</h4>
                        <p style="margin: 0; color: #666;">Digital Product</p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 18px; font-weight: bold;">₵' . number_format($amount, 2) . '</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="total-section">
            <div class="total-amount">₵' . number_format($amount, 2) . '</div>
            <p style="margin: 5px 0 0 0; color: #666;">Total Amount</p>
        </div>
        
        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>This is a digital product receipt. Please keep this for your records.</p>
            <p>For support, contact: support@manuelcode.info</p>
        </div>
    </div>
</body>
</html>';
}

header('Location: generate_receipts.php?error=Invalid request');
exit();
?>
