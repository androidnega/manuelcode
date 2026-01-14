<?php
/**
 * Signature Helper Functions
 * Handles digital signature retrieval and management
 */

/**
 * Get admin signature by admin ID
 */
function getAdminSignature($pdo, $admin_id) {
    try {
        $stmt = $pdo->prepare("SELECT signature_data FROM admin_signatures WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting admin signature: " . $e->getMessage());
        return null;
    }
}

/**
 * Get default admin signature (first available)
 */
function getDefaultAdminSignature($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT signature_data FROM admin_signatures LIMIT 1");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting default admin signature: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if admin has a signature
 */
function hasAdminSignature($pdo, $admin_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_signatures WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Error checking admin signature: " . $e->getMessage());
        return false;
    }
}

/**
 * Get signature for receipt (with fallback)
 */
function getReceiptSignature($pdo, $admin_id = null) {
    if ($admin_id) {
        $signature = getAdminSignature($pdo, $admin_id);
        if ($signature) {
            return $signature;
        }
    }
    
    // Fallback to default signature
    return getDefaultAdminSignature($pdo);
}
?>
