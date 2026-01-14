<?php
/**
 * Purchase Update Helper
 * Ensures successful purchases are properly reflected across all system components
 */

/**
 * Update all system components when a purchase is successful
 * This function should be called after any successful payment
 */
function updatePurchaseSystem($purchase_id, $user_id = null, $guest_order_id = null) {
    global $pdo;
    
    try {
        if ($user_id) {
            // Handle user purchase
            updateUserPurchaseSystem($purchase_id, $user_id);
        } elseif ($guest_order_id) {
            // Handle guest order
            updateGuestOrderSystem($guest_order_id);
        }
        
        // Update global statistics
        updateGlobalStatistics();
        
        return true;
    } catch (Exception $e) {
        error_log("Purchase update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user purchase system components
 */
function updateUserPurchaseSystem($purchase_id, $user_id) {
    global $pdo;
    
    // 1. Update purchase status to paid
    $stmt = $pdo->prepare("UPDATE purchases SET status = 'paid' WHERE id = ?");
    $stmt->execute([$purchase_id]);
    
    // 2. Generate download access
    generateDownloadAccess($purchase_id, 'user');
    
    // 3. Create user notification
    createUserNotification($user_id, $purchase_id, 'purchase_successful');
    
    // 4. Update user activity
    updateUserActivity($user_id, 'purchase_completed', $purchase_id);
    
    // 5. Send email notification (if configured)
    sendPurchaseEmail($user_id, $purchase_id);
    
    // 6. Update admin notifications
    createAdminNotification($purchase_id, 'new_purchase');
}

/**
 * Update guest order system components
 */
function updateGuestOrderSystem($guest_order_id) {
    global $pdo;
    
    // 1. Update guest order status to paid
    $stmt = $pdo->prepare("UPDATE guest_orders SET status = 'paid' WHERE id = ?");
    $stmt->execute([$guest_order_id]);
    
    // 2. Generate download access
    generateDownloadAccess($guest_order_id, 'guest');
    
    // 3. Send email notification
    sendGuestPurchaseEmail($guest_order_id);
    
    // 4. Update admin notifications
    createAdminNotification($guest_order_id, 'new_guest_purchase');
}

/**
 * Generate download access for purchased items
 */
function generateDownloadAccess($order_id, $order_type) {
    global $pdo;
    
    if ($order_type === 'user') {
        // Get purchase details
        $stmt = $pdo->prepare("
            SELECT p.*, pr.title, pr.drive_link, pr.doc_file, u.email
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$order_id]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($purchase) {
            // Create download record
            $stmt = $pdo->prepare("
                INSERT INTO download_access (user_id, product_id, purchase_id, access_type, created_at)
                VALUES (?, ?, ?, 'user', NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $stmt->execute([$purchase['user_id'], $purchase['product_id'], $order_id]);
        }
    } else {
        // Get guest order details
        $stmt = $pdo->prepare("
            SELECT go.*, pr.id as product_id, pr.title, pr.drive_link, pr.doc_file
            FROM guest_orders go
            JOIN products pr ON go.product_name = pr.title
            WHERE go.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Create download record for guest
            $stmt = $pdo->prepare("
                INSERT INTO download_access (guest_email, product_id, guest_order_id, access_type, created_at)
                VALUES (?, ?, ?, 'guest', NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $stmt->execute([$order['email'], $order['product_id'], $order_id]);
        }
    }
}

/**
 * Create user notification
 */
function createUserNotification($user_id, $purchase_id, $type) {
    global $pdo;
    
    $messages = [
        'purchase_successful' => 'Your purchase was successful! You can now download your files.',
        'download_ready' => 'Your download is ready!',
        'product_updated' => 'A product you purchased has been updated.'
    ];
    
    $message = $messages[$type] ?? 'Purchase notification';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications (user_id, notification_type, title, message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $type, 'Purchase Update', $message]);
    } catch (Exception $e) {
        // Log error but don't fail the entire process
        error_log("Failed to create user notification: " . $e->getMessage());
    }
}

/**
 * Create admin notification
 */
function createAdminNotification($order_id, $type) {
    global $pdo;
    
    $messages = [
        'new_purchase' => 'New user purchase completed',
        'new_guest_purchase' => 'New guest purchase completed'
    ];
    
    $message = $messages[$type] ?? 'New order notification';
    
    try {
        // Get admin IDs
        $stmt = $pdo->query("SELECT id FROM admins WHERE status = 'active'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            $stmt = $pdo->prepare("
                INSERT INTO admin_notifications (admin_id, notification_type, title, message, order_id, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$admin['id'], $type, 'New Order', $message, $order_id]);
        }
    } catch (Exception $e) {
        // Log error but don't fail the entire process
        error_log("Failed to create admin notification: " . $e->getMessage());
    }
}

/**
 * Update user activity
 */
function updateUserActivity($user_id, $activity_type, $reference_id = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO user_activity (user_id, activity_type, reference_id, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $activity_type,
        $reference_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Send purchase email notification
 */
function sendPurchaseEmail($user_id, $purchase_id) {
    global $pdo;
    
    // Get user and purchase details
    $stmt = $pdo->prepare("
        SELECT u.name, u.email, pr.title, pr.price
        FROM purchases p
        JOIN users u ON p.user_id = u.id
        JOIN products pr ON p.product_id = pr.id
        WHERE p.id = ? AND u.id = ?
    ");
    $stmt->execute([$purchase_id, $user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Email content
        $subject = "Purchase Successful - {$data['title']}";
        $message = "
        Dear {$data['name']},
        
        Your purchase of '{$data['title']}' for GHS " . number_format($data['price'], 2) . " was successful!
        
        You can now access your download in your dashboard.
        
        Thank you for your purchase!
        
        Best regards,
        ManuelCode Team
        ";
        
        // Send email (implement your email sending logic here)
        // mail($data['email'], $subject, $message);
        
        // Log email
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (user_id, email, subject, message, status, created_at)
            VALUES (?, ?, ?, ?, 'sent', NOW())
        ");
        $stmt->execute([$user_id, $data['email'], $subject, $message]);
    }
}

/**
 * Send guest purchase email notification
 */
function sendGuestPurchaseEmail($guest_order_id) {
    global $pdo;
    
    // Get guest order details
    $stmt = $pdo->prepare("
        SELECT email, product_name, total_amount, unique_id
        FROM guest_orders
        WHERE id = ?
    ");
    $stmt->execute([$guest_order_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Email content
        $subject = "Purchase Successful - {$data['product_name']}";
        $message = "
        Dear Customer,
        
        Your purchase of '{$data['product_name']}' for GHS " . number_format($data['total_amount'], 2) . " was successful!
        
        Your unique order ID: {$data['unique_id']}
        
        You can download your files using your email and order ID at our download page.
        
        Thank you for your purchase!
        
        Best regards,
        ManuelCode Team
        ";
        
        // Send email (implement your email sending logic here)
        // mail($data['email'], $subject, $message);
        
        // Log email
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (email, subject, message, status, created_at)
            VALUES (?, ?, ?, 'sent', NOW())
        ");
        $stmt->execute([$data['email'], $subject, $message]);
    }
}

/**
 * Update global statistics
 */
function updateGlobalStatistics() {
    global $pdo;
    
    // Update total revenue
    $stmt = $pdo->query("
        UPDATE site_statistics SET 
        total_revenue = (
            SELECT COALESCE(SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END), 0) +
                       COALESCE(SUM(CASE WHEN go.status = 'paid' THEN go.total_amount ELSE 0 END), 0)
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            CROSS JOIN guest_orders go
        ),
        total_orders = (
            SELECT COUNT(*) FROM purchases WHERE status = 'paid'
        ) + (
            SELECT COUNT(*) FROM guest_orders WHERE status = 'paid'
        ),
        updated_at = NOW()
        WHERE id = 1
    ");
    
    // Insert if not exists
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->query("
            INSERT INTO site_statistics (total_revenue, total_orders, created_at, updated_at)
            SELECT 
                COALESCE(SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.amount, pr.price) ELSE 0 END), 0) +
                COALESCE(SUM(CASE WHEN go.status = 'paid' THEN go.total_amount ELSE 0 END), 0),
                (SELECT COUNT(*) FROM purchases WHERE status = 'paid') +
                (SELECT COUNT(*) FROM guest_orders WHERE status = 'paid'),
                NOW(), NOW()
            FROM purchases p
            JOIN products pr ON p.product_id = pr.id
            CROSS JOIN guest_orders go
        ");
    }
}

/**
 * Auto-update purchase status on successful payment
 * This should be called from payment callback handlers
 */
function autoUpdatePurchaseStatus($payment_reference, $status = 'paid') {
    global $pdo;
    
    // Check user purchases
    $stmt = $pdo->prepare("SELECT id, user_id FROM purchases WHERE payment_ref = ?");
    $stmt->execute([$payment_reference]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($purchase) {
        updatePurchaseSystem($purchase['id'], $purchase['user_id']);
        return true;
    }
    
    // Check guest orders
    $stmt = $pdo->prepare("SELECT id FROM guest_orders WHERE payment_reference = ?");
    $stmt->execute([$payment_reference]);
    $guest_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($guest_order) {
        updatePurchaseSystem(null, null, $guest_order['id']);
        return true;
    }
    
    return false;
}
?>
