<?php
/**
 * Submission System Setup Script
 * 
 * This script creates the necessary database tables for the student submission system.
 * Run this script once to set up the database structure.
 */

include 'includes/db.php';

echo "<h1>Student Submission System Setup</h1>\n";
echo "<p>Setting up database tables...</p>\n";

try {
    // Define SQL statements in the correct order
    $sql_statements = [
        // 1. Create submissions table first
        "CREATE TABLE IF NOT EXISTS submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            index_number VARCHAR(50) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) DEFAULT 5.00,
            status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            reference VARCHAR(255) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // 2. Create analysts table
        "CREATE TABLE IF NOT EXISTS analysts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            otp VARCHAR(6),
            otp_expires_at TIMESTAMP NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )",
        
        // 3. Create submission_analyst_logs table
        "CREATE TABLE IF NOT EXISTS submission_analyst_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            analyst_id INT NOT NULL,
            submission_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // 4. Create submission_notifications table
        "CREATE TABLE IF NOT EXISTS submission_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            submission_id INT NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            sms_response JSON,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // 5. Add indexes to submissions table
        "CREATE INDEX IF NOT EXISTS idx_submissions_index_number ON submissions(index_number)",
        "CREATE INDEX IF NOT EXISTS idx_submissions_phone_number ON submissions(phone_number)",
        "CREATE INDEX IF NOT EXISTS idx_submissions_status ON submissions(status)",
        "CREATE INDEX IF NOT EXISTS idx_submissions_reference ON submissions(reference)",
        "CREATE INDEX IF NOT EXISTS idx_submissions_created_at ON submissions(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_submissions_status_created ON submissions(status, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_submissions_amount ON submissions(amount)",
        
        // 6. Add indexes to analysts table
        "CREATE INDEX IF NOT EXISTS idx_analysts_email ON analysts(email)",
        "CREATE INDEX IF NOT EXISTS idx_analysts_status ON analysts(status)",
        "CREATE INDEX IF NOT EXISTS idx_analysts_created_by ON analysts(created_by)",
        "CREATE INDEX IF NOT EXISTS idx_analysts_email_status ON analysts(email, status)",
        
        // 7. Add foreign key constraints (with error handling - MariaDB compatible)
        "ALTER TABLE analysts ADD CONSTRAINT fk_analysts_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL",
        "ALTER TABLE submission_analyst_logs ADD CONSTRAINT fk_logs_analyst_id FOREIGN KEY (analyst_id) REFERENCES analysts(id) ON DELETE CASCADE",
        "ALTER TABLE submission_analyst_logs ADD CONSTRAINT fk_logs_submission_id FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE SET NULL",
        "ALTER TABLE submission_notifications ADD CONSTRAINT fk_notifications_submission_id FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE",
        
        // 8. Add indexes to logs table
        "CREATE INDEX IF NOT EXISTS idx_logs_analyst_id ON submission_analyst_logs(analyst_id)",
        "CREATE INDEX IF NOT EXISTS idx_logs_submission_id ON submission_analyst_logs(submission_id)",
        "CREATE INDEX IF NOT EXISTS idx_logs_action ON submission_analyst_logs(action)",
        "CREATE INDEX IF NOT EXISTS idx_logs_created_at ON submission_analyst_logs(created_at)",
        
        // 9. Add indexes to notifications table
        "CREATE INDEX IF NOT EXISTS idx_notifications_submission_id ON submission_notifications(submission_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_status ON submission_notifications(status)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON submission_notifications(created_at)",
        
        // 10. Update admins table (with error handling)
        "ALTER TABLE admins MODIFY COLUMN role ENUM('admin', 'superadmin', 'analyst') NOT NULL DEFAULT 'admin'",
        "ALTER TABLE admins ADD COLUMN IF NOT EXISTS can_manage_analysts BOOLEAN DEFAULT FALSE AFTER role",
    ];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($sql_statements as $index => $statement) {
        try {
            $result = $pdo->exec($statement);
            $description = "Statement " . ($index + 1) . ": " . substr($statement, 0, 50) . "...";
            echo "<p style='color: green;'>✓ $description</p>\n";
            $success_count++;
        } catch (Exception $e) {
            $description = "Statement " . ($index + 1) . ": " . substr($statement, 0, 50) . "...";
            echo "<p style='color: red;'>✗ Error in $description</p>\n";
            echo "<p style='color: red; margin-left: 20px;'>" . $e->getMessage() . "</p>\n";
            $error_count++;
        }
    }
    
    // Create views
    $views = [
        "CREATE OR REPLACE VIEW submission_stats AS
        SELECT 
            COUNT(*) as total_submissions,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_submissions,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_submissions,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_submissions,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_revenue
        FROM submissions",
        
        "CREATE OR REPLACE VIEW recent_submissions_view AS
        SELECT 
            s.*,
            DATE_FORMAT(s.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
            ROUND(s.file_size / 1024, 2) as file_size_kb,
            CASE 
                WHEN s.status = 'paid' THEN 'text-green-600 bg-green-100'
                WHEN s.status = 'pending' THEN 'text-yellow-600 bg-yellow-100'
                WHEN s.status = 'failed' THEN 'text-red-600 bg-red-100'
            END as status_class
        FROM submissions s
        ORDER BY s.created_at DESC"
    ];
    
    foreach ($views as $view) {
        try {
            $result = $pdo->exec($view);
            echo "<p style='color: green;'>✓ Created view: " . substr($view, 0, 30) . "...</p>\n";
            $success_count++;
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error creating view: " . $e->getMessage() . "</p>\n";
            $error_count++;
        }
    }
    
    echo "<hr>\n";
    echo "<h2>Setup Summary</h2>\n";
    echo "<p>Successfully executed: $success_count statements</p>\n";
    echo "<p>Errors: $error_count statements</p>\n";
    
    if ($error_count == 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Database setup completed successfully!</p>\n";
        
        // Test the tables
        echo "<h2>Verification</h2>\n";
        
        $tables_to_check = ['submissions', 'analysts', 'submission_analyst_logs', 'submission_notifications'];
        
        foreach ($tables_to_check as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "<p style='color: green;'>✓ Table '$table' exists</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Table '$table' not found</p>\n";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Error checking table '$table': " . $e->getMessage() . "</p>\n";
            }
        }
        
        // Check admin role enum
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM admins LIKE 'role'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && strpos($result['Type'], 'analyst') !== false) {
                echo "<p style='color: green;'>✓ Admin role enum updated to include 'analyst'</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠ Admin role enum may not include 'analyst'</p>\n";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error checking admin role enum: " . $e->getMessage() . "</p>\n";
        }
        
        echo "<hr>\n";
        echo "<h2>Next Steps</h2>\n";
        echo "<ol>\n";
        echo "<li>Create an Analyst account through the Super Admin panel</li>\n";
        echo "<li>Test the submission form at <a href='submission.php'>submission.php</a></li>\n";
        echo "<li>Test the analyst login at <a href='analyst/login.php'>analyst/login.php</a></li>\n";
        echo "<li>Verify Paystack payment integration</li>\n";
        echo "<li>Test SMS notifications</li>\n";
        echo "</ol>\n";
        
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Database setup completed with errors. Please review the errors above.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Setup failed: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<h2>System Information</h2>\n";
echo "<p>PHP Version: " . PHP_VERSION . "</p>\n";
echo "<p>Database: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>\n";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>\n";

// Check if upload directory exists
$upload_dir = 'uploads/projects/';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color: green;'>✓ Created upload directory: $upload_dir</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Failed to create upload directory: $upload_dir</p>\n";
    }
} else {
    echo "<p style='color: green;'>✓ Upload directory exists: $upload_dir</p>\n";
}

// Check file permissions
if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✓ Upload directory is writable</p>\n";
} else {
    echo "<p style='color: red;'>✗ Upload directory is not writable</p>\n";
}
?>
