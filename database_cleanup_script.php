<?php
/**
 * Database Cleanup Script - ManuelCode.info
 * 
 * This script identifies and removes:
 * 1. Duplicate records across all tables
 * 2. Ghost records (database entries pointing to missing files)
 * 3. Irrelevant data not related to the site
 * 
 * IMPORTANT: This script generates SQL files for review before execution
 * DO NOT run DELETE operations automatically - review all generated SQL first
 */

// Database connection
require_once 'includes/db.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output file for cleanup SQL
$cleanup_sql_file = 'database_cleanup_operations.sql';
$report_file = 'database_cleanup_report.txt';

// Initialize report
$report = "DATABASE CLEANUP REPORT - " . date('Y-m-d H:i:s') . "\n";
$report .= "=" . str_repeat("=", 50) . "\n\n";

$cleanup_sql = "-- Database Cleanup Operations - ManuelCode.info\n";
$cleanup_sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
$cleanup_sql .= "-- IMPORTANT: Review all operations before executing!\n\n";

$total_duplicates = 0;
$total_ghost_records = 0;
$total_irrelevant = 0;

echo "🔍 Starting Database Cleanup Analysis...\n\n";

// =====================================================
// 1. DUPLICATE RECORDS CLEANUP
// =====================================================

echo "📋 1. Analyzing Duplicate Records...\n";
$cleanup_sql .= "-- =====================================================\n";
$cleanup_sql .= "-- DUPLICATE RECORDS CLEANUP\n";
$cleanup_sql .= "-- =====================================================\n\n";

// Users table duplicates
echo "   - Checking users table...\n";
$stmt = $pdo->query("
    SELECT email, COUNT(*) as count 
    FROM users 
    GROUP BY email 
    HAVING COUNT(*) > 1
");
$duplicate_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicate_users)) {
    $cleanup_sql .= "-- Duplicate users (keeping the oldest record)\n";
    foreach ($duplicate_users as $duplicate) {
        $cleanup_sql .= "-- Email: {$duplicate['email']} - {$duplicate['count']} duplicates\n";
        $cleanup_sql .= "DELETE u1 FROM users u1\n";
        $cleanup_sql .= "INNER JOIN users u2 ON u1.email = u2.email\n";
        $cleanup_sql .= "WHERE u1.id > u2.id AND u1.email = '{$duplicate['email']}';\n\n";
        $total_duplicates += $duplicate['count'] - 1;
    }
    $report .= "Users table: " . count($duplicate_users) . " duplicate emails found\n";
}

// Page visits duplicates (same URL, IP, and time within 1 minute)
echo "   - Checking page_visits table...\n";
$stmt = $pdo->query("
    SELECT page_url, ip_address, DATE_FORMAT(visit_time, '%Y-%m-%d %H:%i') as minute, COUNT(*) as count
    FROM page_visits 
    GROUP BY page_url, ip_address, DATE_FORMAT(visit_time, '%Y-%m-%d %H:%i')
    HAVING COUNT(*) > 1
");
$duplicate_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicate_visits)) {
    $cleanup_sql .= "-- Duplicate page visits (same URL, IP, and minute)\n";
    foreach ($duplicate_visits as $duplicate) {
        $cleanup_sql .= "-- URL: {$duplicate['page_url']} - {$duplicate['count']} duplicates\n";
        $cleanup_sql .= "DELETE p1 FROM page_visits p1\n";
        $cleanup_sql .= "INNER JOIN page_visits p2 ON p1.page_url = p2.page_url\n";
        $cleanup_sql .= "AND p1.ip_address = p2.ip_address\n";
        $cleanup_sql .= "AND DATE_FORMAT(p1.visit_time, '%Y-%m-%d %H:%i') = DATE_FORMAT(p2.visit_time, '%Y-%m-%d %H:%i')\n";
        $cleanup_sql .= "WHERE p1.id > p2.id;\n\n";
        $total_duplicates += $duplicate['count'] - 1;
    }
    $report .= "Page visits: " . count($duplicate_visits) . " duplicate visit groups found\n";
}

// SMS logs duplicates
echo "   - Checking sms_logs table...\n";
$stmt = $pdo->query("
    SELECT phone, message, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as minute, COUNT(*) as count
    FROM sms_logs 
    GROUP BY phone, message, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
    HAVING COUNT(*) > 1
");
$duplicate_sms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicate_sms)) {
    $cleanup_sql .= "-- Duplicate SMS logs (same phone, message, and minute)\n";
    foreach ($duplicate_sms as $duplicate) {
        $cleanup_sql .= "-- Phone: {$duplicate['phone']} - {$duplicate['count']} duplicates\n";
        $cleanup_sql .= "DELETE s1 FROM sms_logs s1\n";
        $cleanup_sql .= "INNER JOIN sms_logs s2 ON s1.phone = s2.phone\n";
        $cleanup_sql .= "AND s1.message = s2.message\n";
        $cleanup_sql .= "AND DATE_FORMAT(s1.created_at, '%Y-%m-%d %H:%i') = DATE_FORMAT(s2.created_at, '%Y-%m-%d %H:%i')\n";
        $cleanup_sql .= "WHERE s1.id > s2.id;\n\n";
        $total_duplicates += $duplicate['count'] - 1;
    }
    $report .= "SMS logs: " . count($duplicate_sms) . " duplicate SMS groups found\n";
}

// =====================================================
// 2. GHOST RECORDS CLEANUP
// =====================================================

echo "\n📋 2. Analyzing Ghost Records...\n";
$cleanup_sql .= "-- =====================================================\n";
$cleanup_sql .= "-- GHOST RECORDS CLEANUP (Missing Files)\n";
$cleanup_sql .= "-- =====================================================\n\n";

// Products with missing preview images
echo "   - Checking products with missing images...\n";
$stmt = $pdo->query("
    SELECT id, title, preview_image 
    FROM products 
    WHERE preview_image IS NOT NULL 
    AND preview_image != ''
");
$products_with_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ghost_product_images = [];
foreach ($products_with_images as $product) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $product['preview_image'];
    if (!file_exists($file_path)) {
        $ghost_product_images[] = $product;
    }
}

if (!empty($ghost_product_images)) {
    $cleanup_sql .= "-- Products with missing preview images\n";
    foreach ($ghost_product_images as $product) {
        $cleanup_sql .= "-- Product: {$product['title']} - Missing: {$product['preview_image']}\n";
        $cleanup_sql .= "UPDATE products SET preview_image = NULL WHERE id = {$product['id']};\n";
        $total_ghost_records++;
    }
    $cleanup_sql .= "\n";
    $report .= "Products: " . count($ghost_product_images) . " missing preview images\n";
}

// Products with missing document files
echo "   - Checking products with missing documents...\n";
$stmt = $pdo->query("
    SELECT id, title, doc_file 
    FROM products 
    WHERE doc_file IS NOT NULL 
    AND doc_file != ''
");
$products_with_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ghost_product_docs = [];
foreach ($products_with_docs as $product) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $product['doc_file'];
    if (!file_exists($file_path)) {
        $ghost_product_docs[] = $product;
    }
}

if (!empty($ghost_product_docs)) {
    $cleanup_sql .= "-- Products with missing document files\n";
    foreach ($ghost_product_docs as $product) {
        $cleanup_sql .= "-- Product: {$product['title']} - Missing: {$product['doc_file']}\n";
        $cleanup_sql .= "UPDATE products SET doc_file = NULL WHERE id = {$product['id']};\n";
        $total_ghost_records++;
    }
    $cleanup_sql .= "\n";
    $report .= "Products: " . count($ghost_product_docs) . " missing document files\n";
}

// Projects with missing images
echo "   - Checking projects with missing images...\n";
$stmt = $pdo->query("
    SELECT id, title, image_url 
    FROM projects 
    WHERE image_url IS NOT NULL 
    AND image_url != ''
    AND image_url NOT LIKE 'http%'
");
$projects_with_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ghost_project_images = [];
foreach ($projects_with_images as $project) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $project['image_url'];
    if (!file_exists($file_path)) {
        $ghost_project_images[] = $project;
    }
}

if (!empty($ghost_project_images)) {
    $cleanup_sql .= "-- Projects with missing images\n";
    foreach ($ghost_project_images as $project) {
        $cleanup_sql .= "-- Project: {$project['title']} - Missing: {$project['image_url']}\n";
        $cleanup_sql .= "UPDATE projects SET image_url = NULL WHERE id = {$project['id']};\n";
        $total_ghost_records++;
    }
    $cleanup_sql .= "\n";
    $report .= "Projects: " . count($ghost_project_images) . " missing images\n";
}

// Quote attachments with missing files
echo "   - Checking quote attachments...\n";
$stmt = $pdo->query("
    SELECT id, quote_id, file_name, file_path 
    FROM quote_attachments
");
$quote_attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ghost_quote_attachments = [];
foreach ($quote_attachments as $attachment) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $attachment['file_path'];
    if (!file_exists($file_path)) {
        $ghost_quote_attachments[] = $attachment;
    }
}

if (!empty($ghost_quote_attachments)) {
    $cleanup_sql .= "-- Quote attachments with missing files\n";
    foreach ($ghost_quote_attachments as $attachment) {
        $cleanup_sql .= "-- Attachment: {$attachment['file_name']} - Missing: {$attachment['file_path']}\n";
        $cleanup_sql .= "DELETE FROM quote_attachments WHERE id = {$attachment['id']};\n";
        $total_ghost_records++;
    }
    $cleanup_sql .= "\n";
    $report .= "Quote attachments: " . count($ghost_quote_attachments) . " missing files\n";
}

// =====================================================
// 3. IRRELEVANT DATA CLEANUP
// =====================================================

echo "\n📋 3. Analyzing Irrelevant Data...\n";
$cleanup_sql .= "-- =====================================================\n";
$cleanup_sql .= "-- IRRELEVANT DATA CLEANUP\n";
$cleanup_sql .= "-- =====================================================\n\n";

// Page visits with irrelevant URLs
echo "   - Checking irrelevant page visits...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM page_visits 
    WHERE page_url LIKE '%/ManuelCode.info/%' 
    OR page_url LIKE '%localhost%'
    OR page_url LIKE '%127.0.0.1%'
    OR page_url LIKE '%xampp%'
");
$irrelevant_visits = $stmt->fetch(PDO::FETCH_ASSOC);

if ($irrelevant_visits['count'] > 0) {
    $cleanup_sql .= "-- Remove irrelevant page visits (localhost, xampp, etc.)\n";
    $cleanup_sql .= "DELETE FROM page_visits WHERE page_url LIKE '%/ManuelCode.info/%';\n";
    $cleanup_sql .= "DELETE FROM page_visits WHERE page_url LIKE '%localhost%';\n";
    $cleanup_sql .= "DELETE FROM page_visits WHERE page_url LIKE '%127.0.0.1%';\n";
    $cleanup_sql .= "DELETE FROM page_visits WHERE page_url LIKE '%xampp%';\n\n";
    $total_irrelevant += $irrelevant_visits['count'];
    $report .= "Page visits: {$irrelevant_visits['count']} irrelevant URLs found\n";
}

// Popular pages with irrelevant URLs
echo "   - Checking irrelevant popular pages...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM popular_pages 
    WHERE page_url LIKE '%/ManuelCode.info/%' 
    OR page_url LIKE '%localhost%'
    OR page_url LIKE '%127.0.0.1%'
");
$irrelevant_popular = $stmt->fetch(PDO::FETCH_ASSOC);

if ($irrelevant_popular['count'] > 0) {
    $cleanup_sql .= "-- Remove irrelevant popular pages\n";
    $cleanup_sql .= "DELETE FROM popular_pages WHERE page_url LIKE '%/ManuelCode.info/%';\n";
    $cleanup_sql .= "DELETE FROM popular_pages WHERE page_url LIKE '%localhost%';\n";
    $cleanup_sql .= "DELETE FROM popular_pages WHERE page_url LIKE '%127.0.0.1%';\n\n";
    $total_irrelevant += $irrelevant_popular['count'];
    $report .= "Popular pages: {$irrelevant_popular['count']} irrelevant URLs found\n";
}

// Old OTP codes (older than 24 hours)
echo "   - Checking expired OTP codes...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM otp_codes 
    WHERE expires_at < NOW() - INTERVAL 24 HOUR
");
$expired_otp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($expired_otp['count'] > 0) {
    $cleanup_sql .= "-- Remove expired OTP codes (older than 24 hours)\n";
    $cleanup_sql .= "DELETE FROM otp_codes WHERE expires_at < NOW() - INTERVAL 24 HOUR;\n\n";
    $total_irrelevant += $expired_otp['count'];
    $report .= "OTP codes: {$expired_otp['count']} expired codes found\n";
}

// Old user sessions (inactive for more than 30 days)
echo "   - Checking old user sessions...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM user_sessions 
    WHERE login_time < NOW() - INTERVAL 30 DAY
    AND is_active = 0
");
$old_sessions = $stmt->fetch(PDO::FETCH_ASSOC);

if ($old_sessions['count'] > 0) {
    $cleanup_sql .= "-- Remove old inactive user sessions (older than 30 days)\n";
    $cleanup_sql .= "DELETE FROM user_sessions WHERE login_time < NOW() - INTERVAL 30 DAY AND is_active = 0;\n\n";
    $total_irrelevant += $old_sessions['count'];
    $report .= "User sessions: {$old_sessions['count']} old inactive sessions found\n";
}

// Old system logs (older than 90 days)
echo "   - Checking old system logs...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM system_logs 
    WHERE created_at < NOW() - INTERVAL 90 DAY
");
$old_logs = $stmt->fetch(PDO::FETCH_ASSOC);

if ($old_logs['count'] > 0) {
    $cleanup_sql .= "-- Remove old system logs (older than 90 days)\n";
    $cleanup_sql .= "DELETE FROM system_logs WHERE created_at < NOW() - INTERVAL 90 DAY;\n\n";
    $total_irrelevant += $old_logs['count'];
    $report .= "System logs: {$old_logs['count']} old logs found\n";
}

// Old page visits (older than 1 year)
echo "   - Checking old page visits...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM page_visits 
    WHERE visit_time < NOW() - INTERVAL 1 YEAR
");
$old_visits = $stmt->fetch(PDO::FETCH_ASSOC);

if ($old_visits['count'] > 0) {
    $cleanup_sql .= "-- Remove old page visits (older than 1 year)\n";
    $cleanup_sql .= "DELETE FROM page_visits WHERE visit_time < NOW() - INTERVAL 1 YEAR;\n\n";
    $total_irrelevant += $old_visits['count'];
    $report .= "Page visits: {$old_visits['count']} old visits found\n";
}

// =====================================================
// 4. DATA INTEGRITY CLEANUP
// =====================================================

echo "\n📋 4. Analyzing Data Integrity Issues...\n";
$cleanup_sql .= "-- =====================================================\n";
$cleanup_sql .= "-- DATA INTEGRITY CLEANUP\n";
$cleanup_sql .= "-- =====================================================\n\n";

// Orphaned purchases (user or product doesn't exist)
echo "   - Checking orphaned purchases...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM purchases p 
    LEFT JOIN users u ON p.user_id = u.id 
    LEFT JOIN products pr ON p.product_id = pr.id 
    WHERE (p.user_id IS NOT NULL AND u.id IS NULL) 
    OR (p.product_id IS NOT NULL AND pr.id IS NULL)
");
$orphaned_purchases = $stmt->fetch(PDO::FETCH_ASSOC);

if ($orphaned_purchases['count'] > 0) {
    $cleanup_sql .= "-- Remove orphaned purchases (user or product doesn't exist)\n";
    $cleanup_sql .= "DELETE p FROM purchases p\n";
    $cleanup_sql .= "LEFT JOIN users u ON p.user_id = u.id\n";
    $cleanup_sql .= "LEFT JOIN products pr ON p.product_id = pr.id\n";
    $cleanup_sql .= "WHERE (p.user_id IS NOT NULL AND u.id IS NULL)\n";
    $cleanup_sql .= "OR (p.product_id IS NOT NULL AND pr.id IS NULL);\n\n";
    $total_irrelevant += $orphaned_purchases['count'];
    $report .= "Purchases: {$orphaned_purchases['count']} orphaned records found\n";
}

// Orphaned quote responses (quote doesn't exist)
echo "   - Checking orphaned quote responses...\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM quote_responses qr 
    LEFT JOIN quotes q ON qr.quote_id = q.id 
    WHERE q.id IS NULL
");
$orphaned_responses = $stmt->fetch(PDO::FETCH_ASSOC);

if ($orphaned_responses['count'] > 0) {
    $cleanup_sql .= "-- Remove orphaned quote responses (quote doesn't exist)\n";
    $cleanup_sql .= "DELETE qr FROM quote_responses qr\n";
    $cleanup_sql .= "LEFT JOIN quotes q ON qr.quote_id = q.id\n";
    $cleanup_sql .= "WHERE q.id IS NULL;\n\n";
    $total_irrelevant += $orphaned_responses['count'];
    $report .= "Quote responses: {$orphaned_responses['count']} orphaned records found\n";
}

// =====================================================
// SUMMARY AND FINALIZATION
// =====================================================

$cleanup_sql .= "-- =====================================================\n";
$cleanup_sql .= "-- CLEANUP SUMMARY\n";
$cleanup_sql .= "-- =====================================================\n\n";
$cleanup_sql .= "-- Total duplicates to remove: {$total_duplicates}\n";
$cleanup_sql .= "-- Total ghost records to fix: {$total_ghost_records}\n";
$cleanup_sql .= "-- Total irrelevant data to remove: {$total_irrelevant}\n";
$cleanup_sql .= "-- Total operations: " . ($total_duplicates + $total_ghost_records + $total_irrelevant) . "\n\n";

$cleanup_sql .= "-- Verification queries after cleanup:\n";
$cleanup_sql .= "SELECT 'Users' as table_name, COUNT(*) as count FROM users\n";
$cleanup_sql .= "UNION ALL\n";
$cleanup_sql .= "SELECT 'Products' as table_name, COUNT(*) as count FROM products\n";
$cleanup_sql .= "UNION ALL\n";
$cleanup_sql .= "SELECT 'Purchases' as table_name, COUNT(*) as count FROM purchases\n";
$cleanup_sql .= "UNION ALL\n";
$cleanup_sql .= "SELECT 'Page Visits' as table_name, COUNT(*) as count FROM page_visits\n";
$cleanup_sql .= "UNION ALL\n";
$cleanup_sql .= "SELECT 'SMS Logs' as table_name, COUNT(*) as count FROM sms_logs;\n";

// Write files
file_put_contents($cleanup_sql_file, $cleanup_sql);
file_put_contents($report_file, $report);

// Final report
$report .= "\n" . str_repeat("=", 60) . "\n";
$report .= "CLEANUP SUMMARY\n";
$report .= str_repeat("=", 60) . "\n";
$report .= "Total Duplicates Found: {$total_duplicates}\n";
$report .= "Total Ghost Records Found: {$total_ghost_records}\n";
$report .= "Total Irrelevant Data Found: {$total_irrelevant}\n";
$report .= "Total Operations: " . ($total_duplicates + $total_ghost_records + $total_irrelevant) . "\n\n";

$report .= "Files Generated:\n";
$report .= "- {$cleanup_sql_file} - SQL operations for review\n";
$report .= "- {$report_file} - Detailed cleanup report\n\n";

$report .= "NEXT STEPS:\n";
$report .= "1. Review the generated SQL file carefully\n";
$report .= "2. Test on a backup database first\n";
$report .= "3. Execute operations in small batches\n";
$report .= "4. Verify data integrity after each batch\n";

// Write final report
file_put_contents($report_file, $report);

echo "\n✅ Database Cleanup Analysis Complete!\n\n";
echo "📊 SUMMARY:\n";
echo "   - Duplicates: {$total_duplicates}\n";
echo "   - Ghost Records: {$total_ghost_records}\n";
echo "   - Irrelevant Data: {$total_irrelevant}\n";
echo "   - Total Operations: " . ($total_duplicates + $total_ghost_records + $total_irrelevant) . "\n\n";

echo "📁 Files Generated:\n";
echo "   - {$cleanup_sql_file} - SQL operations for review\n";
echo "   - {$report_file} - Detailed cleanup report\n\n";

echo "⚠️  IMPORTANT:\n";
echo "   1. Review the SQL file before executing\n";
echo "   2. Test on a backup database first\n";
echo "   3. Execute operations in small batches\n";
echo "   4. Verify data integrity after each batch\n\n";

echo "🚀 Ready to proceed with cleanup!\n";
?>
