<?php
/**
 * Quote Management Helper Functions
 * Comprehensive quote management system with templates, communications, and analytics
 */

// Include utility functions
require_once __DIR__ . '/util.php';

/**
 * Generate a unique quote tracking code
 */
function generateUniqueQuoteCode($pdo) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $check_stmt = $pdo->prepare("SELECT id FROM quote_requests WHERE unique_code = ?");
        $check_stmt->execute([$code]);
    } while ($check_stmt->fetch());
    
    return $code;
}

/**
 * Create a new quote request
 */
function createQuoteRequest($pdo, $data) {
    try {
        $unique_code = generateUniqueQuoteCode($pdo);
        
        $stmt = $pdo->prepare("
            INSERT INTO quote_requests (
                unique_code, service_type, project_title, description, budget_range, timeline,
                contact_name, contact_email, contact_phone, additional_requirements,
                user_id, status, priority, source, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
        ");
        
        $stmt->execute([
            $unique_code,
            $data['service_type'],
            $data['project_title'],
            $data['description'],
            $data['budget_range'] ?? null,
            $data['timeline'] ?? null,
            $data['contact_name'],
            $data['contact_email'],
            $data['contact_phone'] ?? null,
            $data['additional_requirements'] ?? null,
            $data['user_id'] ?? null,
            $data['priority'] ?? 'medium',
            $data['source'] ?? 'website'
        ]);
        
        $quote_id = $pdo->lastInsertId();
        
        // Log the quote creation
        logQuoteActivity($pdo, $quote_id, 'quote_created', [
            'unique_code' => $unique_code,
            'service_type' => $data['service_type'],
            'contact_email' => $data['contact_email']
        ]);
        
        return [
            'success' => true,
            'quote_id' => $quote_id,
            'unique_code' => $unique_code
        ];
    } catch (Exception $e) {
        error_log("Error creating quote request: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Update quote status and provide response
 */
function updateQuoteStatus($pdo, $quote_id, $data, $admin_id = null) {
    try {
        $pdo->beginTransaction();
        
        // Get current quote details
        $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = ?");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quote) {
            throw new Exception("Quote not found");
        }
        
        // Update quote
        $stmt = $pdo->prepare("
            UPDATE quote_requests 
            SET status = ?, quote_message = ?, quote_amount = ?, 
                quoted_by = ?, quoted_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $data['status'],
            $data['quote_message'] ?? null,
            $data['quote_amount'] ?? null,
            $admin_id,
            $quote_id
        ]);
        
        // Add communication record
        addQuoteCommunication($pdo, $quote_id, 'quote_provided', $data['quote_message'] ?? '', $admin_id);
        
        // Log the activity
        logQuoteActivity($pdo, $quote_id, 'quote_updated', [
            'status' => $data['status'],
            'amount' => $data['quote_amount'] ?? null,
            'admin_id' => $admin_id
        ]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'quote' => $quote
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating quote status: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Add communication to quote
 */
function addQuoteCommunication($pdo, $quote_id, $type, $message, $admin_id = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO quote_communications (quote_id, communication_type, message, admin_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$quote_id, $type, $message, $admin_id]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error adding quote communication: " . $e->getMessage());
        return false;
    }
}

/**
 * Get quote communications
 */
function getQuoteCommunications($pdo, $quote_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT qc.*, a.name as admin_name
            FROM quote_communications qc
            LEFT JOIN admins a ON qc.admin_id = a.id
            WHERE qc.quote_id = ?
            ORDER BY qc.created_at ASC
        ");
        $stmt->execute([$quote_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting quote communications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get quote templates
 */
function getQuoteTemplates($pdo, $service_type = null) {
    try {
        $sql = "SELECT * FROM quote_templates WHERE is_active = 1";
        $params = [];
        
        if ($service_type) {
            $sql .= " AND (service_types IS NULL OR JSON_CONTAINS(service_types, ?))";
            $params[] = json_encode($service_type);
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting quote templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Create quote template
 */
function createQuoteTemplate($pdo, $data, $admin_id) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO quote_templates (name, subject, message, service_types, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['subject'],
            $data['message'],
            json_encode($data['service_types'] ?? []),
            $admin_id
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error creating quote template: " . $e->getMessage());
        return false;
    }
}

/**
 * Get quote statistics
 */
function getQuoteStatistics($pdo, $period = 'all') {
    try {
        $date_filter = '';
        $params = [];
        
        switch ($period) {
            case 'today':
                $date_filter = 'WHERE DATE(created_at) = CURDATE()';
                break;
            case 'week':
                $date_filter = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $date_filter = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'year':
                $date_filter = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
                break;
        }
        
        // Total quotes
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quote_requests $date_filter");
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Status breakdown
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count 
            FROM quote_requests 
            $date_filter 
            GROUP BY status
        ");
        $stmt->execute($params);
        $status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Service type breakdown
        $stmt = $pdo->prepare("
            SELECT service_type, COUNT(*) as count 
            FROM quote_requests 
            $date_filter 
            GROUP BY service_type
        ");
        $stmt->execute($params);
        $service_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Conversion rate (quoted to accepted)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'quoted' THEN 1 END) as quoted_count,
                COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_count
            FROM quote_requests 
            $date_filter
        ");
        $stmt->execute($params);
        $conversion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $conversion_rate = $conversion['quoted_count'] > 0 
            ? round(($conversion['accepted_count'] / $conversion['quoted_count']) * 100, 2)
            : 0;
        
        return [
            'total' => $total,
            'status_breakdown' => $status_breakdown,
            'service_breakdown' => $service_breakdown,
            'conversion_rate' => $conversion_rate,
            'quoted_count' => $conversion['quoted_count'],
            'accepted_count' => $conversion['accepted_count']
        ];
    } catch (Exception $e) {
        error_log("Error getting quote statistics: " . $e->getMessage());
        return [
            'total' => 0,
            'status_breakdown' => [],
            'service_breakdown' => [],
            'conversion_rate' => 0,
            'quoted_count' => 0,
            'accepted_count' => 0
        ];
    }
}

/**
 * Log quote activity for analytics
 */
function logQuoteActivity($pdo, $quote_id, $action, $details = []) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO quote_analytics (quote_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$quote_id, $action, json_encode($details)]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging quote activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get quote by unique code
 */
function getQuoteByCode($pdo, $unique_code) {
    try {
        $stmt = $pdo->prepare("
            SELECT qr.*, u.name as user_name, u.email as user_email
            FROM quote_requests qr
            LEFT JOIN users u ON qr.user_id = u.id
            WHERE qr.unique_code = ?
        ");
        $stmt->execute([$unique_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting quote by code: " . $e->getMessage());
        return false;
    }
}

/**
 * Get quote by ID with full details
 */
function getQuoteById($pdo, $quote_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT qr.*, u.name as user_name, u.email as user_email,
                   a.name as quoted_by_name
            FROM quote_requests qr
            LEFT JOIN users u ON qr.user_id = u.id
            LEFT JOIN admins a ON qr.quoted_by = a.id
            WHERE qr.id = ?
        ");
        $stmt->execute([$quote_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting quote by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Search quotes with filters
 */
function searchQuotes($pdo, $filters = []) {
    try {
        $sql = "
            SELECT qr.*, u.name as user_name, u.email as user_email
            FROM quote_requests qr
            LEFT JOIN users u ON qr.user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND qr.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['service_type'])) {
            $sql .= " AND qr.service_type = ?";
            $params[] = $filters['service_type'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND qr.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (qr.project_title LIKE ? OR qr.contact_name LIKE ? OR qr.contact_email LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(qr.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(qr.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY qr.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error searching quotes: " . $e->getMessage());
        return [];
    }
}

/**
 * Send quote notification SMS
 */
function sendQuoteNotification($pdo, $quote, $status, $amount = null) {
    try {
        if (empty($quote['contact_phone'])) {
            return false;
        }
        
        $site_url = "https://manuelcode.info";
        $tracking_url = $site_url . "/track_quote.php?code=" . $quote['unique_code'];
        
        $status_text = ucfirst($status);
        $amount_text = $amount ? " Amount: GHS " . number_format($amount, 2) : "";
        
        // Get SMS template from database
        $sms_template = get_config('sms_quote_update', 'Your quote request ({unique_code}) has been {status}.{amount} Track your quote at: {tracking_url}');
        
        $sms_message = str_replace(
            ['{unique_code}', '{status}', '{amount}', '{tracking_url}'],
            [$quote['unique_code'], $status_text, $amount_text, $tracking_url],
            $sms_template
        );
        
        // Send SMS
        $sms_result = send_sms($quote['contact_phone'], $sms_message);
        
        // Log SMS attempt
        if (function_exists('log_sms_activity')) {
            log_sms_activity($quote['contact_phone'], $sms_message, $sms_result);
        }
        
        return $sms_result;
    } catch (Exception $e) {
        error_log("Error sending quote notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get quote dashboard data
 */
function getQuoteDashboardData($pdo) {
    try {
        // Recent quotes
        $stmt = $pdo->prepare("
            SELECT qr.*, u.name as user_name
            FROM quote_requests qr
            LEFT JOIN users u ON qr.user_id = u.id
            ORDER BY qr.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recent_quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pending quotes count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quote_requests WHERE status = 'pending'");
        $stmt->execute();
        $pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Today's quotes
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quote_requests WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $today_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // This week's quotes
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quote_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        $week_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Service type distribution
        $stmt = $pdo->prepare("
            SELECT service_type, COUNT(*) as count 
            FROM quote_requests 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY service_type 
            ORDER BY count DESC
        ");
        $stmt->execute();
        $service_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'recent_quotes' => $recent_quotes,
            'pending_count' => $pending_count,
            'today_count' => $today_count,
            'week_count' => $week_count,
            'service_distribution' => $service_distribution
        ];
    } catch (Exception $e) {
        error_log("Error getting quote dashboard data: " . $e->getMessage());
        return [
            'recent_quotes' => [],
            'pending_count' => 0,
            'today_count' => 0,
            'week_count' => 0,
            'service_distribution' => []
        ];
    }
}
?>
