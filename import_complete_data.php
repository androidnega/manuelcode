<?php
/**
 * Import Complete Data - ManuelCode.info
 * This script imports ALL your localhost data to the live server
 * Upload this to your LIVE SERVER
 */

echo "<h1>📥 Import Complete Localhost Data</h1>";
echo "<p>This will import ALL your localhost data and replace any existing data...</p>";

// Include database connection
try {
    include 'includes/db.php';
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    echo "✅ Connected to live database: " . $dbname . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<p>Make sure your database credentials are correct in includes/db.php</p>";
    exit;
}

echo "<hr>";

// =====================================================
// 1. CURRENT DATA BACKUP
// =====================================================
echo "<h2>📋 1. Creating Backup of Current Live Data</h2>";

// Get all tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($all_tables) . " tables on live server<br>";
} catch (Exception $e) {
    echo "❌ Error getting tables: " . $e->getMessage() . "<br>";
    exit;
}

// Create backup
$backup_timestamp = date('Y-m-d_H-i-s');
$backup_content = "-- Backup of live data before import: $backup_timestamp\n\n";

$backup_records = 0;
foreach ($all_tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $backup_content .= "-- Data for table: $table ($count records)\n";
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                $escaped_values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $values);
                
                $backup_content .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escaped_values) . ");\n";
                $backup_records++;
            }
            $backup_content .= "\n";
        }
    } catch (Exception $e) {
        $backup_content .= "-- Error backing up $table: " . $e->getMessage() . "\n";
    }
}

$backup_file = "live_data_backup_$backup_timestamp.sql";
if (file_put_contents($backup_file, $backup_content)) {
    echo "✅ Created backup: $backup_file ($backup_records records)<br>";
} else {
    echo "⚠️ Could not create backup file<br>";
}

// =====================================================
// 2. FILE UPLOAD AND IMPORT
// =====================================================
echo "<h2>📋 2. Import Your Complete Localhost Data</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['complete_sql_file'])) {
    $upload_file = $_FILES['complete_sql_file'];
    
    if ($upload_file['error'] === UPLOAD_ERR_OK && $upload_file['size'] > 0) {
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>🔄 Processing Complete Data Import...</h3>";
        echo "<p><strong>File:</strong> " . $upload_file['name'] . "</p>";
        echo "<p><strong>Size:</strong> " . number_format($upload_file['size'] / 1024, 2) . " KB</p>";
        echo "</div>";
        
        $sql_content = file_get_contents($upload_file['tmp_name']);
        
        if ($sql_content) {
            try {
                // Set MySQL settings for large imports
                $pdo->exec("SET SESSION max_execution_time = 300");
                $pdo->exec("SET SESSION wait_timeout = 300");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $pdo->exec("SET AUTOCOMMIT = 0");
                
                echo "<div style='background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin: 10px 0; max-height: 400px; overflow-y: auto;'>";
                echo "<h4>Import Progress:</h4>";
                
                // Begin transaction
                $pdo->beginTransaction();
                
                // Split SQL into statements
                $statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql_content)));
                
                $success_count = 0;
                $error_count = 0;
                $delete_count = 0;
                $insert_count = 0;
                $current_table = '';
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^--/', $statement)) {
                        try {
                            // Skip SET statements (already handled)
                            if (stripos($statement, 'SET ') === 0 || 
                                stripos($statement, 'START TRANSACTION') === 0 ||
                                stripos($statement, 'COMMIT') === 0) {
                                continue;
                            }
                            
                            $pdo->exec($statement);
                            $success_count++;
                            
                            // Track progress
                            if (stripos($statement, 'DELETE FROM') === 0) {
                                $delete_count++;
                                preg_match('/DELETE FROM `?(\w+)`?/', $statement, $matches);
                                $current_table = $matches[1] ?? 'unknown';
                                echo "🗑️ Clearing table: <strong>$current_table</strong><br>";
                                flush();
                            } elseif (stripos($statement, 'INSERT INTO') === 0) {
                                $insert_count++;
                                
                                // Show progress every 100 inserts
                                if ($insert_count % 100 == 0) {
                                    echo "📥 Imported $insert_count records...<br>";
                                    flush();
                                }
                                
                                // Show table progress every 50 inserts
                                if ($insert_count % 50 == 0) {
                                    preg_match('/INSERT INTO `?(\w+)`?/', $statement, $matches);
                                    if (isset($matches[1]) && $matches[1] !== $current_table) {
                                        $current_table = $matches[1];
                                        echo "📊 Importing to: <strong>$current_table</strong><br>";
                                        flush();
                                    }
                                }
                            }
                            
                        } catch (Exception $e) {
                            $error_count++;
                            
                            // Only show first 20 errors to avoid spam
                            if ($error_count <= 20) {
                                $short_statement = substr($statement, 0, 60) . "...";
                                echo "⚠️ Error: " . htmlspecialchars($short_statement) . " - " . htmlspecialchars($e->getMessage()) . "<br>";
                                flush();
                            }
                        }
                    }
                }
                
                echo "</div>";
                
                // Check if import was successful
                if ($error_count <= 50) { // Allow some minor errors
                    $pdo->commit();
                    
                    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
                    echo "<h3>✅ Import Completed Successfully!</h3>";
                    echo "<ul>";
                    echo "<li><strong>Total Statements:</strong> " . count($statements) . "</li>";
                    echo "<li><strong>Successful:</strong> $success_count</li>";
                    echo "<li><strong>Tables Cleared:</strong> $delete_count</li>";
                    echo "<li><strong>Records Imported:</strong> $insert_count</li>";
                    if ($error_count > 0) {
                        echo "<li><strong>Minor Errors:</strong> $error_count (ignored)</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    
                } else {
                    $pdo->rollback();
                    
                    echo "<div style='background: #ffebee; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
                    echo "<h3>❌ Import Failed!</h3>";
                    echo "<p>Too many errors encountered: $error_count</p>";
                    echo "<p>Transaction rolled back for safety.</p>";
                    echo "<p>Check your SQL file for issues.</p>";
                    echo "</div>";
                }
                
                // Reset MySQL settings
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $pdo->exec("SET AUTOCOMMIT = 1");
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
                echo "<h3>❌ Import Error</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
            echo "❌ Failed to read uploaded file";
            echo "</div>";
        }
        
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk'
        ];
        
        $error_msg = $error_messages[$upload_file['error']] ?? 'Unknown upload error';
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "❌ Upload Error: $error_msg";
        echo "</div>";
    }
}

// Upload form
echo "<form method='POST' enctype='multipart/form-data' style='background: #f5f5f5; padding: 25px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📁 Upload Your Complete Localhost Export</h3>";
echo "<p>Select the complete SQL file exported from your localhost:</p>";
echo "<input type='file' name='complete_sql_file' accept='.sql' required style='padding: 10px; margin: 10px 0; display: block; width: 100%;'>";
echo "<button type='submit' style='padding: 15px 30px; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📥 Import Complete Data</button>";
echo "</form>";

// =====================================================
// 3. VERIFICATION SECTION
// =====================================================
echo "<h2>📋 3. Data Verification</h2>";

// Check current data counts
$current_stats = [];
$verification_tables = [
    'users' => 'User accounts',
    'admins' => 'Admin accounts',
    'products' => 'Products', 
    'purchases' => 'Purchase records',
    'orders' => 'Order records',
    'payment_verifications' => 'Payment verifications',
    'sms_logs' => 'SMS logs',
    'system_logs' => 'System logs',
    'page_visits' => 'Page visits',
    'user_activity' => 'User activity',
    'support_tickets' => 'Support tickets',
    'downloads' => 'Downloads',
    'quotes' => 'Quotes',
    'projects' => 'Projects',
    'settings' => 'Settings'
];

echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Table</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Description</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Records</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
echo "</tr>";

$total_imported_records = 0;
$tables_with_data = 0;

foreach ($verification_tables as $table => $description) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        $total_imported_records += $count;
        
        if ($count > 0) {
            $tables_with_data++;
            $status = "✅ Has Data";
            $row_color = "#e8f5e8";
        } else {
            $status = "⚠️ Empty";
            $row_color = "#fff3cd";
        }
        
        echo "<tr style='background: $row_color;'>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$table</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>$description</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>$count</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
        echo "</tr>";
        
    } catch (Exception $e) {
        echo "<tr style='background: #ffebee;'>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>$table</strong></td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>$description</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>Error</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>❌ Failed</td>";
        echo "</tr>";
    }
}

echo "</table>";

// =====================================================
// 4. SUMMARY AND NEXT STEPS
// =====================================================
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📊 Import Summary</h3>";
echo "<ul>";
echo "<li><strong>Tables with Data:</strong> $tables_with_data / " . count($verification_tables) . "</li>";
echo "<li><strong>Total Records:</strong> " . number_format($total_imported_records) . "</li>";
echo "<li><strong>Backup Created:</strong> $backup_file</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🧪 Test Your System</h3>";
echo "<p>After importing, test these areas:</p>";
echo "<ul>";
echo "<li><a href='admin/dashboard.php' target='_blank'>Admin Dashboard</a> - Check statistics</li>";
echo "<li><a href='admin/users.php' target='_blank'>User Management</a> - View imported users</li>";
echo "<li><a href='admin/orders.php' target='_blank'>Order Management</a> - View imported orders</li>";
echo "<li><a href='admin/products.php' target='_blank'>Product Management</a> - View imported products</li>";
echo "<li><a href='verify_data_transfer.php' target='_blank'>Data Verification</a> - Complete analysis</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . " | <strong>Database:</strong> $dbname</p>";

echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "⚠️ <strong>Security Note:</strong> Remove this file after successful import.";
echo "</div>";
?>
