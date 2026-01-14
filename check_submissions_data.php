<?php
/**
 * Check Submissions Data - ManuelCode.info
 * This script helps diagnose and restore submissions data
 */

echo "<h1>🔍 Check Submissions Data - ManuelCode.info</h1>";
echo "<p>This will help you check and restore your submissions data...</p>";

// Include database connection
try {
    include 'includes/db.php';
    
    // Enable buffered queries to prevent PDO errors
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    echo "✅ Connected to database: " . $dbname . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// =====================================================
// 1. CHECK SUBMISSIONS TABLE
// =====================================================
echo "<h2>📋 1. Submissions Table Status</h2>";

try {
    // Check if submissions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'submissions'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Submissions table exists<br>";
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions");
        $total = $stmt->fetch()['total'];
        echo "📊 Total submissions: <strong>$total</strong><br>";
        
        // Get count by status
        $stmt = $pdo->query("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM submissions 
            GROUP BY status
        ");
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📈 Status Breakdown:</h3>";
        foreach ($status_counts as $status) {
            $color = $status['status'] === 'paid' ? 'green' : ($status['status'] === 'pending' ? 'orange' : 'red');
            echo "<span style='color: $color; font-weight: bold;'>" . strtoupper($status['status']) . "</span>: {$status['count']} submissions";
            if ($status['status'] === 'paid') {
                echo " (GHS " . number_format($status['total_amount'], 2) . ")";
            }
            echo "<br>";
        }
        
        // Show recent submissions
        if ($total > 0) {
            echo "<h3>📝 Recent Submissions (Last 10):</h3>";
            $stmt = $pdo->query("
                SELECT id, name, index_number, status, amount, reference, created_at 
                FROM submissions 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>ID</th><th>Name</th><th>Index</th><th>Status</th><th>Amount</th><th>Reference</th><th>Date</th>";
            echo "</tr>";
            
            foreach ($recent as $submission) {
                $status_color = $submission['status'] === 'paid' ? 'green' : ($submission['status'] === 'pending' ? 'orange' : 'red');
                echo "<tr>";
                echo "<td>{$submission['id']}</td>";
                echo "<td>" . htmlspecialchars($submission['name']) . "</td>";
                echo "<td>" . htmlspecialchars($submission['index_number']) . "</td>";
                echo "<td style='color: $status_color; font-weight: bold;'>" . strtoupper($submission['status']) . "</td>";
                echo "<td>GHS " . number_format($submission['amount'], 2) . "</td>";
                echo "<td style='font-family: monospace; font-size: 12px;'>" . htmlspecialchars($submission['reference']) . "</td>";
                echo "<td>" . date('M j, Y g:i A', strtotime($submission['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ Submissions table does not exist!<br>";
        echo "<p>This is the problem - the submissions table is missing.</p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking submissions: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// =====================================================
// 2. CHECK ANALYST TABLE
// =====================================================
echo "<h2>📋 2. Analyst Table Status</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'analysts'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Analysts table exists<br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM analysts");
        $total = $stmt->fetch()['total'];
        echo "📊 Total analysts: <strong>$total</strong><br>";
        
        if ($total > 0) {
            $stmt = $pdo->query("SELECT id, name, email, phone FROM analysts LIMIT 5");
            $analysts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>👥 Analysts:</h3>";
            foreach ($analysts as $analyst) {
                echo "• " . htmlspecialchars($analyst['name']) . " (" . htmlspecialchars($analyst['email']) . ")<br>";
            }
        }
    } else {
        echo "❌ Analysts table does not exist!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking analysts: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// =====================================================
// 3. CHECK SETTINGS TABLE
// =====================================================
echo "<h2>📋 3. Settings Table Status</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Settings table exists<br>";
        
        $stmt = $pdo->query("SELECT setting_key, value FROM settings WHERE setting_key IN ('submissions_enabled', 'submission_price')");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($settings)) {
            echo "<h3>⚙️ Current Settings:</h3>";
            foreach ($settings as $setting) {
                echo "• " . htmlspecialchars($setting['setting_key']) . ": <strong>" . htmlspecialchars($setting['value']) . "</strong><br>";
            }
        } else {
            echo "⚠️ No settings found<br>";
        }
    } else {
        echo "❌ Settings table does not exist!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking settings: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// =====================================================
// 4. RESTORE OPTIONS
// =====================================================
echo "<h2>📋 4. Restore Options</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_submissions'])) {
    echo "<h3>🔄 Restoring Submissions from Backup...</h3>";
    
    try {
        // Read the backup file
        $backup_file = 'manuela_simple_export.sql';
        if (file_exists($backup_file)) {
            $sql_content = file_get_contents($backup_file);
            
            // Extract submissions data
            if (preg_match('/INSERT INTO `submissions`[^;]+;/', $sql_content, $matches)) {
                $insert_statement = $matches[0];
                
                // Clear existing submissions
                $pdo->exec("DELETE FROM submissions");
                echo "🗑️ Cleared existing submissions<br>";
                
                // Insert backup data
                $pdo->exec($insert_statement);
                echo "📥 Restored submissions from backup<br>";
                
                // Verify restoration
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions");
                $total = $stmt->fetch()['total'];
                echo "✅ Verification: $total submissions restored<br>";
                
            } else {
                echo "❌ Could not find submissions data in backup file<br>";
            }
        } else {
            echo "❌ Backup file not found: $backup_file<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error restoring submissions: " . $e->getMessage() . "<br>";
    }
}

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 Restore Options:</h3>";
echo "<ol>";
echo "<li><strong>From Local Backup:</strong> Use the restore button below to restore from manuela_simple_export.sql</li>";
echo "<li><strong>From Live Server:</strong> If you have access to your live server, export the submissions table and import it here</li>";
echo "<li><strong>Manual Entry:</strong> If you have the data elsewhere, you can manually enter it</li>";
echo "</ol>";
echo "</div>";

echo "<form method='POST' style='background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🔄 Restore from Local Backup</h3>";
echo "<p>This will restore submissions from the manuela_simple_export.sql file:</p>";
echo "<button type='submit' name='restore_submissions' style='padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer;'>Restore Submissions</button>";
echo "</form>";

// =====================================================
// 5. MANUAL DATA ENTRY
// =====================================================
echo "<h2>📋 5. Manual Data Entry</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_submission'])) {
    $name = trim($_POST['name']);
    $index_number = trim($_POST['index_number']);
    $phone_number = trim($_POST['phone_number']);
    $file_name = trim($_POST['file_name']);
    $amount = floatval($_POST['amount']);
    $status = $_POST['status'];
    
    if ($name && $index_number && $phone_number && $file_name) {
        try {
            $reference = 'SUB_' . uniqid() . '_' . time();
            
            $stmt = $pdo->prepare("
                INSERT INTO submissions (name, index_number, phone_number, file_name, file_size, file_type, amount, status, reference, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, 'pdf', ?, ?, ?, NOW(), NOW())
            ");
            
            if ($stmt->execute([$name, $index_number, $phone_number, $file_name, $amount, $status, $reference])) {
                echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
                echo "✅ <strong>Submission Added Successfully!</strong><br>";
                echo "Reference: $reference<br>";
                echo "</div>";
            } else {
                echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
                echo "❌ Failed to add submission";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
            echo "❌ Error adding submission: " . $e->getMessage();
            echo "</div>";
        }
    }
}

echo "<form method='POST' style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>➕ Add New Submission</h3>";
echo "<table>";
echo "<tr><td>Name:</td><td><input type='text' name='name' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Index Number:</td><td><input type='text' name='index_number' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Phone Number:</td><td><input type='text' name='phone_number' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>File Name:</td><td><input type='text' name='file_name' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Amount:</td><td><input type='number' name='amount' step='0.01' value='0.01' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Status:</td><td>";
echo "<select name='status' style='width: 200px; padding: 5px;'>";
echo "<option value='paid'>Paid</option>";
echo "<option value='pending'>Pending</option>";
echo "<option value='failed'>Failed</option>";
echo "</select>";
echo "</td></tr>";
echo "<tr><td colspan='2'><br><button type='submit' name='add_submission' style='padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 3px;'>Add Submission</button></td></tr>";
echo "</table>";
echo "</form>";

// =====================================================
// 6. NEXT STEPS
// =====================================================
echo "<h2>📋 6. Next Steps</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 After Restoring Data:</h3>";
echo "<ol>";
echo "<li><strong>Test Dashboard:</strong> Go to analyst/dashboard.php and check if data shows</li>";
echo "<li><strong>Verify Counts:</strong> Make sure the count matches your expectations</li>";
echo "<li><strong>Test Pagination:</strong> Check if pagination works correctly</li>";
echo "<li><strong>Remove Files:</strong> Delete this diagnostic script after fixing</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🧪 Test Your System:</h3>";
echo "<ul>";
echo "<li><a href='analyst/dashboard.php' target='_blank'>Analyst Dashboard</a></li>";
echo "<li><a href='analyst/login.php' target='_blank'>Analyst Login</a></li>";
echo "<li><a href='submission.php' target='_blank'>Submission Form</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . " | ";
echo "<strong>Database:</strong> $dbname</p>";

echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "⚠️ <strong>Security Note:</strong> Remove this file after successfully restoring your data.";
echo "</div>";
?>
