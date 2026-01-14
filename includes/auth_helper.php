<?php
// Prevent function redeclaration
if (!function_exists('isLoggedIn')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/db.php';

    function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        return $_SESSION['user'];
    }
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, name, email, phone, role, user_id, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user'] = $user;
            return $user;
        }
    } catch (Exception $e) {
        // ignore
    }
    return null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'superadmin')) {
        header("Location: ../index.php");
        exit;
    }
}

    function isAdmin() {
        $user = getCurrentUser();
        return $user && ($user['role'] === 'admin' || $user['role'] === 'superadmin');
    }
}
?>
