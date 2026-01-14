<?php
/**
 * Admin Page Error Handler
 * Include this at the top of admin pages to prevent 500 errors
 */

// Set error reporting for debugging (remove in production)
ini_set("display_errors", 1);
error_reporting(E_ALL);

// Safe session handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Safe database inclusion
try {
    if (file_exists("../includes/db.php")) {
        include "../includes/db.php";
    } elseif (file_exists("includes/db.php")) {
        include "includes/db.php";
    } else {
        throw new Exception("Database connection file not found");
    }
} catch (Exception $e) {
    error_log("Admin page DB error: " . $e->getMessage());
    die("Database connection failed. Please check configuration.");
}

// Include safe admin queries
try {
    if (file_exists("../includes/safe_admin_queries.php")) {
        include "../includes/safe_admin_queries.php";
    } elseif (file_exists("includes/safe_admin_queries.php")) {
        include "includes/safe_admin_queries.php";
    }
} catch (Exception $e) {
    error_log("Safe queries error: " . $e->getMessage());
}

// Safe authentication check
function check_admin_access() {
    if (!isset($_SESSION["admin_id"]) && !isset($_SESSION["superadmin_access"])) {
        header("Location: auth/login.php");
        exit;
    }
    return true;
}

// Error handler function
function handle_admin_error($message) {
    error_log("Admin error: " . $message);
    echo "<div style=\"padding: 20px; background: #ffebee; border: 1px solid #f44336; border-radius: 5px; margin: 20px;\">
            <h3>⚠️ Admin Error</h3>
            <p>An error occurred while loading this page. The error has been logged.</p>
            <p><strong>Error:</strong> " . htmlspecialchars($message) . "</p>
            <p><a href=\"dashboard.php\">← Return to Dashboard</a></p>
          </div>";
}
?>