<?php
/**
 * Super Admin Access Handler
 * Allows super admins to access admin pages with request code verification
 */

session_start();
include '../includes/db.php';
include 'auth/check_superadmin_auth.php';

// Generate a unique request code for super admin access
function generateRequestCode() {
    return 'SUPER_' . strtoupper(substr(md5(uniqid()), 0, 8));
}

// Verify super admin access
function verifySuperAdminAccess($request_code) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM superadmin_access_codes WHERE code = ? AND expires_at > NOW() AND used = 0");
        $stmt->execute([$request_code]);
        $code = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($code) {
            // Mark code as used
            $stmt = $pdo->prepare("UPDATE superadmin_access_codes SET used = 1, used_at = NOW() WHERE id = ?");
            $stmt->execute([$code['id']]);
            
            // Set session variables for admin access
            $_SESSION['superadmin_access'] = true;
            $_SESSION['superadmin_access_code'] = $request_code;
            $_SESSION['superadmin_access_time'] = time();
            
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Super admin access verification error: " . $e->getMessage());
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_code':
            try {
                $code = generateRequestCode();
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                $stmt = $pdo->prepare("INSERT INTO superadmin_access_codes (code, created_by, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$code, $_SESSION['superadmin_username'], $expires_at]);
                
                echo json_encode(['success' => true, 'code' => $code, 'expires_at' => $expires_at]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'verify_code':
            $code = $_POST['code'] ?? '';
            if (verifySuperAdminAccess($code)) {
                echo json_encode(['success' => true, 'message' => 'Access granted']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid or expired code']);
            }
            exit;
            
        case 'revoke_access':
            // Clear session variables
            unset($_SESSION['superadmin_access']);
            unset($_SESSION['superadmin_access_code']);
            unset($_SESSION['superadmin_access_time']);
            echo json_encode(['success' => true, 'message' => 'Access revoked']);
            exit;
    }
}

// Check if super admin has valid access
function hasSuperAdminAccess() {
    if (!isset($_SESSION['superadmin_access']) || !$_SESSION['superadmin_access']) {
        return false;
    }
    
    // Check if access is still valid (30 minutes)
    if (time() - $_SESSION['superadmin_access_time'] > 1800) {
        unset($_SESSION['superadmin_access']);
        unset($_SESSION['superadmin_access_code']);
        unset($_SESSION['superadmin_access_time']);
        return false;
    }
    
    return true;
}

// If this is included in admin pages, check access
if (basename($_SERVER['PHP_SELF']) !== 'superadmin_access.php') {
    if (!hasSuperAdminAccess()) {
        // Redirect to super admin dashboard with access request
        header('Location: superadmin.php?access_required=1');
        exit;
    }
}
?>
