<?php
$host = "localhost";
$dbname = "manuelc8_db";
$username = "manuelc8_user";
$password = "Atomic2@2020^";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Check if this is an API request (JSON content type expected)
    if (isset($_SERVER[
        'HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    } else {
        die("DB Connection failed: " . $e->getMessage());
    }
}

// Include auto configuration and logging
include_once __DIR__ . '/auto_config.php';
include_once __DIR__ . '/logger.php';

// Log database connection (only if not in API context)
if (!defined('API_CONTEXT')) {
    log_system("Database connection established", ['host' => $host, 'database' => $dbname]);
}
?>
