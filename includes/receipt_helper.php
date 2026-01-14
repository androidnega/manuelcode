<?php
// Receipt Helper Functions
// Handles receipt generation and management for purchases

/**
 * Generate receipt HTML for a purchase
 */
function generate_receipt_html($purchase_data, $user_data, $product_data) {
    $receipt_number = 'RCP' . date('Ymd') . str_pad($purchase_data['id'], 5, '0', STR_PAD_LEFT);
    $purchase_date = date('F j, Y \a\t g:i A', strtotime($purchase_data['created_at']));
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receipt - ManuelCode</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
            .receipt-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 2px solid #F5A623; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 24px; font-weight: bold; color: #2D3E50; margin-bottom: 10px; }
            .tagline { color: #666; font-size: 14px; }
            .receipt-title { font-size: 28px; color: #2D3E50; margin: 0; }
            .receipt-number { font-size: 16px; color: #666; margin: 5px 0; }
            .date { font-size: 14px; color: #666; }
            .section { margin-bottom: 30px; }
            .section-title { font-size: 18px; font-weight: bold; color: #2D3E50; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
            .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .info-item { margin-bottom: 10px; }
            .label { font-weight: bold; color: #666; font-size: 14px; }
            .value { color: #2D3E50; font-size: 14px; }
            .product-details { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .product-title { font-size: 18px; font-weight: bold; color: #2D3E50; margin-bottom: 10px; }
            .product-desc { color: #666; font-size: 14px; margin-bottom: 15px; }
            .price { font-size: 24px; font-weight: bold; color: #F5A623; text-align: right; }
            .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
            .contact-info { background: #f0f8ff; padding: 15px; border-radius: 8px; margin-top: 20px; }
            .contact-title { font-weight: bold; color: #2D3E50; margin-bottom: 10px; }
            .contact-item { margin-bottom: 5px; font-size: 14px; }
            @media (max-width: 600px) {
                .info-grid { grid-template-columns: 1fr; }
                .receipt-container { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="header">
                <div class="logo">ManuelCode</div>
                <div class="tagline">Professional Software Engineering</div>
                <h1 class="receipt-title">Receipt</h1>
                <div class="receipt-number">Receipt #: ' . $receipt_number . '</div>
                <div class="date">Date: ' . $purchase_date . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Customer Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Name:</div>
                        <div class="value">' . htmlspecialchars($user_data['name']) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Email:</div>
                        <div class="value">' . htmlspecialchars($user_data['email']) . '</div>
                    </div>';
    
    if (!empty($user_data['phone'])) {
        $html .= '
                    <div class="info-item">
                        <div class="label">Phone:</div>
                        <div class="value">' . htmlspecialchars($user_data['phone']) . '</div>
                    </div>';
    }
    
    $html .= '
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Product Details</div>
                <div class="product-details">
                    <div class="product-title">' . htmlspecialchars($product_data['title']) . '</div>
                    <div class="product-desc">' . htmlspecialchars($product_data['description'] ?? $product_data['short_desc'] ?? '') . '</div>
                    <div class="price">₵' . number_format($purchase_data['amount'] ?? $purchase_data['price'], 2) . '</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Payment Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Payment Method:</div>
                        <div class="value">Paystack</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Transaction ID:</div>
                        <div class="value">' . htmlspecialchars($purchase_data['payment_ref'] ?? 'N/A') . '</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Order ID:</div>
                        <div class="value">' . $purchase_data['id'] . '</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Status:</div>
                        <div class="value">Paid</div>
                    </div>
                </div>
            </div>
            
            <div class="contact-info">
                <div class="contact-title">Need Help?</div>
                <div class="contact-item">📧 Email: admin@manuelcode.info</div>
                <div class="contact-item">📱 WhatsApp: +233 54 106 9241</div>
                <div class="contact-item">📞 Phone: +233 25 794 0791</div>
                <div class="contact-item">🌐 Website: manuelcode.info</div>
            </div>
            
            <div class="footer">
                <p>Thank you for your purchase!</p>
                <p>&copy; ' . date('Y') . ' ManuelCode. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Create receipt for a purchase (works with current database structure)
 */
function create_receipt($purchase_id, $user_id = null, $guest_email = null) {
    global $pdo;
    
    try {
        // Check if receipt already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM receipts WHERE purchase_id = ?");
        $stmt->execute([$purchase_id]);
        if ($stmt->fetchColumn() > 0) {
            return true; // Receipt already exists
        }
        
        // Get purchase details
        $stmt = $pdo->prepare("
            SELECT p.*, pr.title as product_title, pr.description, pr.short_desc
            FROM purchases p 
            JOIN products pr ON p.product_id = pr.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$purchase_id]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase) {
            error_log("Receipt creation failed - Purchase not found: " . $purchase_id);
            return false;
        }
        
        // Get user details
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Guest user
            $user = [
                'name' => 'Guest User',
                'email' => $guest_email,
                'phone' => null
            ];
        }
        
        // Generate receipt HTML
        $receipt_html = generate_receipt_html($purchase, $user, $purchase);
        
        // Generate receipt number
        $receipt_number = 'RCP' . date('Ymd') . str_pad($purchase_id, 5, '0', STR_PAD_LEFT);
        
        // Insert into receipts table
        $stmt = $pdo->prepare("
            INSERT INTO receipts (purchase_id, user_id, receipt_number, amount, product_title, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $purchase_id, 
            $user_id, 
            $receipt_number, 
            $purchase['amount'] ?? $purchase['price'], 
            $purchase['product_title']
        ]);
        
        error_log("Receipt created successfully for purchase: " . $purchase_id);
        return true;
        
    } catch (Exception $e) {
        error_log("Receipt creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user receipts (works with current database structure)
 */
function get_user_receipts($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, p.created_at as purchase_date, p.amount, pr.title as product_title
            FROM receipts r
            JOIN purchases p ON r.purchase_id = p.id
            JOIN products pr ON p.product_id = pr.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user receipts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get receipt by ID
 */
function get_receipt_by_id($receipt_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, p.created_at as purchase_date, p.amount, pr.title as product_title
            FROM receipts r
            JOIN purchases p ON r.purchase_id = p.id
            JOIN products pr ON p.product_id = pr.id
            WHERE r.id = ?
        ");
        $stmt->execute([$receipt_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting receipt: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate missing receipts for all users
 */
function generate_missing_receipts() {
    global $pdo;
    
    try {
        // Find purchases without receipts
        $stmt = $pdo->query("
            SELECT p.id, p.user_id, p.product_id
            FROM purchases p
            LEFT JOIN receipts r ON p.id = r.purchase_id
            WHERE p.status = 'paid' AND r.id IS NULL
        ");
        $missing_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $created_count = 0;
        foreach ($missing_receipts as $purchase) {
            if (create_receipt($purchase['id'], $purchase['user_id'])) {
                $created_count++;
            }
        }
        
        return $created_count;
    } catch (Exception $e) {
        error_log("Error generating missing receipts: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get receipt statistics
 */
function get_receipt_stats($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_receipts FROM receipts");
        $total_receipts = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_purchases FROM purchases WHERE status = 'paid'");
        $total_purchases = $stmt->fetchColumn();
        
        return [
            'total_receipts' => $total_receipts,
            'total_purchases' => $total_purchases,
            'missing_receipts' => $total_purchases - $total_receipts
        ];
    } catch (Exception $e) {
        return ['total_receipts' => 0, 'total_purchases' => 0, 'missing_receipts' => 0];
    }
}
?>
