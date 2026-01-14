<?php
/**
 * Import Submissions Data - ManuelCode.info
 * This script imports your 83 submissions from the exported SQL file
 */

echo "<h1>📥 Import Submissions Data - ManuelCode.info</h1>";
echo "<p>This will import your 83 submissions from your live server export...</p>";

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
// 1. CHECK CURRENT SUBMISSIONS
// =====================================================
echo "<h2>📋 1. Current Submissions Status</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions");
    $current_count = $stmt->fetch()['total'];
    echo "📊 Current submissions in database: <strong>$current_count</strong><br>";
    
    if ($current_count > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as paid FROM submissions WHERE status = 'paid'");
        $paid_count = $stmt->fetch()['paid'];
        echo "💰 Paid submissions: <strong>$paid_count</strong><br>";
        
        $stmt = $pdo->query("SELECT SUM(amount) as total_revenue FROM submissions WHERE status = 'paid'");
        $revenue = $stmt->fetch()['total_revenue'];
        echo "💵 Total revenue: <strong>GHS " . number_format($revenue, 2) . "</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking current submissions: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// =====================================================
// 2. IMPORT SUBMISSIONS DATA
// =====================================================
echo "<h2>📋 2. Import Submissions Data</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_submissions'])) {
    echo "<h3>🔄 Importing Submissions...</h3>";
    
    try {
        // Read the submissions SQL file
        $sql_file = 'C:\\Users\\Mhanuel\\Desktop\\submissions.sql';
        
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            echo "✅ Found submissions file: " . basename($sql_file) . "<br>";
            echo "📁 File size: " . number_format(filesize($sql_file) / 1024, 2) . " KB<br>";
            
            // Extract the INSERT statement
            if (preg_match('/INSERT INTO `submissions`[^;]+;/', $sql_content, $matches)) {
                $insert_statement = $matches[0];
                echo "✅ Found INSERT statement in file<br>";
                
                // Clear existing submissions (optional)
                if (isset($_POST['clear_existing']) && $_POST['clear_existing'] === 'yes') {
                    $pdo->exec("DELETE FROM submissions");
                    echo "🗑️ Cleared existing submissions<br>";
                }
                
                // Disable foreign key checks temporarily
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Execute the INSERT statement
                $pdo->exec($insert_statement);
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo "📥 Successfully imported submissions data<br>";
                
                // Verify import
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions");
                $new_count = $stmt->fetch()['total'];
                echo "✅ Verification: <strong>$new_count</strong> submissions now in database<br>";
                
                // Show breakdown
                $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM submissions GROUP BY status");
                $status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h4>📊 Status Breakdown:</h4>";
                foreach ($status_breakdown as $status) {
                    $color = $status['status'] === 'paid' ? 'green' : ($status['status'] === 'pending' ? 'orange' : 'red');
                    echo "<span style='color: $color; font-weight: bold;'>" . strtoupper($status['status']) . "</span>: {$status['count']} submissions<br>";
                }
                
                // Calculate revenue
                $stmt = $pdo->query("SELECT SUM(amount) as total_revenue FROM submissions WHERE status = 'paid'");
                $total_revenue = $stmt->fetch()['total_revenue'];
                echo "<h4>💰 Total Revenue: <strong>GHS " . number_format($total_revenue, 2) . "</strong></h4>";
                
                echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>🎉 Import Successful!</h3>";
                echo "<p>Your submissions data has been successfully imported.</p>";
                echo "<p><a href='analyst/dashboard.php' target='_blank' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Test Dashboard Now</a></p>";
                echo "</div>";
                
            } else {
                echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>❌ No INSERT Statement Found</h3>";
                echo "<p>Could not find INSERT statement in the SQL file. Please check the file format.</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>❌ File Not Found</h3>";
            echo "<p>Could not find the submissions file at: $sql_file</p>";
            echo "<p>Please make sure the file is in the correct location.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Import Failed</h3>";
        echo "<p>Error importing submissions: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

// =====================================================
// 3. UPLOAD ALTERNATIVE
// =====================================================
echo "<h2>📋 3. Alternative: Upload SQL File</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $upload_file = $_FILES['sql_file'];
    
    if ($upload_file['error'] === UPLOAD_ERR_OK && $upload_file['size'] > 0) {
        echo "<h3>🔄 Processing Uploaded File...</h3>";
        echo "<p>File: " . $upload_file['name'] . "</p>";
        echo "<p>Size: " . number_format($upload_file['size'] / 1024, 2) . " KB</p>";
        
        $sql_content = file_get_contents($upload_file['tmp_name']);
        
        if ($sql_content) {
            try {
                // Extract the INSERT statement
                if (preg_match('/INSERT INTO `submissions`[^;]+;/', $sql_content, $matches)) {
                    $insert_statement = $matches[0];
                    
                    // Clear existing submissions
                    $pdo->exec("DELETE FROM submissions");
                    echo "🗑️ Cleared existing submissions<br>";
                    
                    // Disable foreign key checks temporarily
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    
                    // Execute the INSERT statement
                    $pdo->exec($insert_statement);
                    
                    // Re-enable foreign key checks
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    
                    echo "📥 Successfully imported submissions from uploaded file<br>";
                    
                    // Verify import
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions");
                    $new_count = $stmt->fetch()['total'];
                    echo "✅ Verification: <strong>$new_count</strong> submissions imported<br>";
                    
                    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h3>🎉 Upload Import Successful!</h3>";
                    echo "<p><a href='analyst/dashboard.php' target='_blank' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Test Dashboard Now</a></p>";
                    echo "</div>";
                    
                } else {
                    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h3>❌ Invalid File Format</h3>";
                    echo "<p>Could not find INSERT statement in uploaded file.</p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>❌ Import Failed</h3>";
                echo "<p>Error: " . $e->getMessage() . "</p>";
                echo "</div>";
            }
        }
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Upload Failed</h3>";
        echo "<p>Please select a valid SQL file.</p>";
        echo "</div>";
    }
}

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 Import Options:</h3>";
echo "<ol>";
echo "<li><strong>Direct Import:</strong> Use the button below to import from your desktop file</li>";
echo "<li><strong>Upload File:</strong> Upload the SQL file using the form below</li>";
echo "</ol>";
echo "</div>";

// Check if submissions table has data
echo "<h2>📋 3. Quick Database Check</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM submissions");
    $total_submissions = $stmt->fetch()['total'];
    
    if ($total_submissions > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as paid FROM submissions WHERE status = 'paid'");
        $paid_submissions = $stmt->fetch()['paid'];
        
        $stmt = $pdo->query("SELECT SUM(amount) as revenue FROM submissions WHERE status = 'paid'");
        $total_revenue = $stmt->fetch()['revenue'];
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Database Already Has Data!</h3>";
        echo "<p><strong>Total Submissions:</strong> $total_submissions</p>";
        echo "<p><strong>Paid Submissions:</strong> $paid_submissions</p>";
        echo "<p><strong>Total Revenue:</strong> GHS " . number_format($total_revenue, 2) . "</p>";
        echo "<p><a href='analyst/dashboard.php' target='_blank' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Go to Dashboard</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>⚠️ No Data Found</h3>";
        echo "<p>The submissions table exists but is empty. You may need to import your data.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Could not check submissions table: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Direct import form (only show if no data)
if (!isset($total_submissions) || $total_submissions == 0) {
    echo "<form method='POST' style='background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📥 Direct Import from Desktop</h3>";
    echo "<p>This will import from: <code>C:\\Users\\Mhanuel\\Desktop\\submissions.sql</code></p>";
    echo "<div style='margin: 10px 0;'>";
    echo "<label><input type='checkbox' name='clear_existing' value='yes' checked> Clear existing submissions before import</label>";
    echo "</div>";
    echo "<button type='submit' name='import_submissions' style='padding: 12px 24px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;'>Import Submissions</button>";
    echo "</form>";
}

// Upload form
echo "<form method='POST' enctype='multipart/form-data' style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📁 Upload SQL File</h3>";
echo "<p>Upload your submissions.sql file:</p>";
echo "<input type='file' name='sql_file' accept='.sql' required style='padding: 10px; margin: 10px 0; display: block; width: 100%; max-width: 400px;'>";
echo "<button type='submit' style='padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 3px; cursor: pointer;'>Upload and Import</button>";
echo "</form>";

// =====================================================
// 4. NEXT STEPS
// =====================================================
echo "<h2>📋 4. Next Steps</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 After Importing:</h3>";
echo "<ol>";
echo "<li><strong>Test Dashboard:</strong> Go to analyst/dashboard.php and verify it shows 83 submissions</li>";
echo "<li><strong>Check Revenue:</strong> Make sure the revenue amount is correct</li>";
echo "<li><strong>Test Pagination:</strong> Verify pagination works with 10 items per page</li>";
echo "<li><strong>Remove Files:</strong> Delete this import script after successful import</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🧪 Test Your System:</h3>";
echo "<ul>";
echo "<li><a href='analyst/dashboard.php' target='_blank'>Analyst Dashboard</a></li>";
echo "<li><a href='analyst/login.php' target='_blank'>Analyst Login</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . " | ";
echo "<strong>Database:</strong> $dbname</p>";

echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "⚠️ <strong>Security Note:</strong> Remove this file after successfully importing your data.";
echo "</div>";
?>
