<?php
/**
 * Restore Data from Localhost - ManuelCode.info
 * This script helps you restore your user data, admin accounts, and purchases
 */

echo "<h1>🔄 Restore Data from Localhost - ManuelCode.info</h1>";
echo "<p>This will help you restore your users, admins, and purchase data...</p>";

// Include database connection
try {
    include 'includes/db.php';
    
    // Enable buffered queries to prevent PDO errors
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    echo "✅ Connected to live database: " . $dbname . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// =====================================================
// 1. CHECK CURRENT DATA ON LIVE SERVER
// =====================================================
echo "<h2>📋 1. Current Data on Live Server</h2>";

$tables_to_check = ['users', 'admins', 'purchases', 'products', 'settings'];
foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            echo "✅ $table: $count records<br>";
        } else {
            echo "⚠️ $table: $count records (EMPTY)<br>";
        }
    } catch (Exception $e) {
        echo "❌ $table: Error - " . $e->getMessage() . "<br>";
    }
}

// =====================================================
// 2. BACKUP CURRENT LIVE DATA (JUST IN CASE)
// =====================================================
echo "<h2>📋 2. Creating Backup of Current Live Data</h2>";

$backup_timestamp = date('Y-m-d_H-i-s');
$backup_content = "-- Backup of live data before restore: $backup_timestamp\n\n";

foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $backup_content .= "-- Data for table: $table\n";
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                // Escape values
                $escaped_values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $values);
                
                $backup_content .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escaped_values) . ");\n";
            }
            $backup_content .= "\n";
        }
    } catch (Exception $e) {
        $backup_content .= "-- Error backing up $table: " . $e->getMessage() . "\n";
    }
}

$backup_file = "backup_live_data_$backup_timestamp.sql";
if (file_put_contents($backup_file, $backup_content)) {
    echo "✅ Created backup: $backup_file<br>";
} else {
    echo "❌ Failed to create backup<br>";
}

// =====================================================
// 3. PROVIDE LOCALHOST EXPORT INSTRUCTIONS
// =====================================================
echo "<h2>📋 3. Export Data from Your Localhost</h2>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 Steps to Export from Localhost:</h3>";
echo "<ol>";
echo "<li><strong>Go to your localhost phpMyAdmin</strong> (usually http://localhost/phpmyadmin)</li>";
echo "<li><strong>Select your 'manuelcode_db' database</strong></li>";
echo "<li><strong>Click 'Export' tab</strong></li>";
echo "<li><strong>Select these tables ONLY:</strong>";
echo "<ul>";
echo "<li>✅ users</li>";
echo "<li>✅ admins</li>";
echo "<li>✅ purchases</li>";
echo "<li>✅ products</li>";
echo "<li>✅ guest_orders (if exists)</li>";
echo "<li>✅ support_tickets (if exists)</li>";
echo "<li>✅ user_activity (if exists)</li>";
echo "</ul></li>";
echo "<li><strong>Format:</strong> SQL</li>";
echo "<li><strong>Check 'Add DROP TABLE / VIEW / PROCEDURE'</strong></li>";
echo "<li><strong>Click 'Go' to download</strong></li>";
echo "</ol>";
echo "</div>";

// =====================================================
// 4. CREATE DATA IMPORT FORM
// =====================================================
echo "<h2>📋 4. Import Your Localhost Data</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $upload_file = $_FILES['sql_file'];
    
    if ($upload_file['error'] === UPLOAD_ERR_OK && $upload_file['size'] > 0) {
        echo "<h3>🔄 Processing Import...</h3>";
        echo "<p>File size: " . number_format($upload_file['size'] / 1024, 2) . " KB</p>";
        
        $sql_content = file_get_contents($upload_file['tmp_name']);
        
        if ($sql_content) {
            try {
                // Disable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Begin transaction
                $pdo->beginTransaction();
                
                // Split SQL into individual statements
                $statements = array_filter(array_map('trim', explode(';', $sql_content)));
                
                $success_count = 0;
                $error_count = 0;
                $delete_count = 0;
                $insert_count = 0;
                
                echo "<div style='max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin: 10px 0;'>";
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^SET/', $statement)) {
                        try {
                            $pdo->exec($statement);
                            $success_count++;
                            
                            if (stripos($statement, 'DELETE') === 0) {
                                $delete_count++;
                                echo "🗑️ Cleared table data<br>";
                            } elseif (stripos($statement, 'INSERT') === 0) {
                                $insert_count++;
                                if ($insert_count % 50 == 0) {
                                    echo "📥 Imported $insert_count records...<br>";
                                    flush();
                                }
                            }
                        } catch (Exception $e) {
                            $error_count++;
                            // Only show first 10 errors to avoid spam
                            if ($error_count <= 10) {
                                echo "⚠️ Error: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "<br>";
                            }
                        }
                    }
                }
                
                echo "</div>";
                
                if ($error_count <= 10) { // Allow some minor errors
                    $pdo->commit();
                    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
                    echo "✅ <strong>Import Successful!</strong><br>";
                    echo "📊 Success: $success_count statements<br>";
                    echo "📥 Inserted: $insert_count records<br>";
                    echo "🗑️ Cleared: $delete_count tables<br>";
                    if ($error_count > 0) {
                        echo "⚠️ Minor errors: $error_count (ignored)<br>";
                    }
                    echo "</div>";
                } else {
                    $pdo->rollback();
                    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
                    echo "❌ <strong>Import Failed!</strong><br>";
                    echo "Too many errors: $error_count<br>";
                    echo "Transaction rolled back for safety.<br>";
                    echo "</div>";
                }
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
                echo "❌ <strong>Import Error:</strong> " . $e->getMessage();
                echo "</div>";
            }
        } else {
            echo "❌ Failed to read uploaded file<br>";
        }
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $error_msg = $error_messages[$upload_file['error']] ?? 'Unknown upload error';
        echo "❌ File upload error: $error_msg<br>";
    }
}

echo "<form method='POST' enctype='multipart/form-data' style='background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📁 Upload Your Localhost SQL Export</h3>";
echo "<p>Select the SQL file you exported from localhost:</p>";
echo "<input type='file' name='sql_file' accept='.sql' required style='padding: 10px; margin: 10px 0; display: block;'>";
echo "<button type='submit' style='padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 3px; cursor: pointer;'>Import Data</button>";
echo "</form>";

// =====================================================
// 5. MANUAL DATA ENTRY OPTION
// =====================================================
echo "<h2>📋 5. Manual Admin Account Creation</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $admin_name = trim($_POST['admin_name']);
    $admin_email = trim($_POST['admin_email']);
    $admin_phone = trim($_POST['admin_phone']);
    $admin_password = trim($_POST['admin_password']);
    $admin_role = $_POST['admin_role'];
    
    if ($admin_name && $admin_email && $admin_phone && $admin_password) {
        try {
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO admins (name, email, phone, password, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            
            if ($stmt->execute([$admin_name, $admin_email, $admin_phone, $hashed_password, $admin_role])) {
                echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
                echo "✅ <strong>Admin Created Successfully!</strong><br>";
                echo "Name: $admin_name<br>";
                echo "Email: $admin_email<br>";
                echo "Role: $admin_role<br>";
                echo "</div>";
            } else {
                echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
                echo "❌ Failed to create admin account";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
            echo "❌ Error creating admin: " . $e->getMessage();
            echo "</div>";
        }
    }
}

echo "<form method='POST' style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>👤 Create New Admin Account</h3>";
echo "<table>";
echo "<tr><td>Name:</td><td><input type='text' name='admin_name' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Email:</td><td><input type='email' name='admin_email' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Phone:</td><td><input type='text' name='admin_phone' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Password:</td><td><input type='password' name='admin_password' required style='width: 200px; padding: 5px;'></td></tr>";
echo "<tr><td>Role:</td><td>";
echo "<select name='admin_role' style='width: 200px; padding: 5px;'>";
echo "<option value='admin'>Admin</option>";
echo "<option value='superadmin'>Super Admin</option>";
echo "<option value='support'>Support</option>";
echo "</select>";
echo "</td></tr>";
echo "<tr><td colspan='2'><br><button type='submit' name='create_admin' style='padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 3px;'>Create Admin</button></td></tr>";
echo "</table>";
echo "</form>";

// =====================================================
// 6. VERIFY RESTORED DATA
// =====================================================
echo "<h2>📋 6. Verify Current Data</h2>";

foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            echo "✅ $table: $count records<br>";
            
            // Show sample data for verification
            if ($table === 'users' || $table === 'admins') {
                $stmt = $pdo->query("SELECT name, email FROM `$table` LIMIT 3");
                $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($samples)) {
                    echo "&nbsp;&nbsp;&nbsp;Sample: ";
                    foreach ($samples as $sample) {
                        echo htmlspecialchars($sample['name'] . ' (' . $sample['email'] . ')') . "; ";
                    }
                    echo "<br>";
                }
            }
        } else {
            echo "⚠️ $table: $count records (EMPTY)<br>";
        }
    } catch (Exception $e) {
        echo "❌ $table: Error - " . $e->getMessage() . "<br>";
    }
}

// =====================================================
// 7. NEXT STEPS
// =====================================================
echo "<h2>📋 7. Next Steps</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🔧 After Restoring Data:</h3>";
echo "<ol>";
echo "<li><strong>Test Login:</strong> Try logging into admin panel</li>";
echo "<li><strong>Verify Users:</strong> Check if user accounts are visible</li>";
echo "<li><strong>Check Purchases:</strong> Verify purchase history is restored</li>";
echo "<li><strong>Test Products:</strong> Ensure products are showing</li>";
echo "<li><strong>Remove Files:</strong> Delete this restore script and diagnostic files</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🧪 Test Your System:</h3>";
echo "<ul>";
echo "<li><a href='admin/auth/login.php' target='_blank'>Admin Login</a></li>";
echo "<li><a href='admin/dashboard.php' target='_blank'>Admin Dashboard</a></li>";
echo "<li><a href='admin/users.php' target='_blank'>User Management</a></li>";
echo "<li><a href='admin/products.php' target='_blank'>Product Management</a></li>";
echo "<li><a href='store.php' target='_blank'>Store (Frontend)</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Generated on:</strong> " . date('Y-m-d H:i:s') . " | ";
echo "<strong>Database:</strong> $dbname</p>";

echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "⚠️ <strong>Security Note:</strong> Remove this file after successfully restoring your data.";
echo "</div>";
?>

