<?php
// Setup script for submission scheduling functionality
include 'includes/db.php';

echo "<h2>Submission Scheduling Setup</h2>";

try {
    // Create the submission_schedules table
    $sql = "
    CREATE TABLE IF NOT EXISTS submission_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action ENUM('enable', 'disable') NOT NULL,
        scheduled_date DATE NOT NULL,
        scheduled_time TIME NOT NULL,
        status ENUM('pending', 'executed', 'cancelled') DEFAULT 'pending',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        executed_at TIMESTAMP NULL,
        notes TEXT,
        FOREIGN KEY (created_by) REFERENCES analysts(id) ON DELETE CASCADE,
        INDEX idx_scheduled_datetime (scheduled_date, scheduled_time),
        INDEX idx_status (status),
        INDEX idx_created_by (created_by)
    )";
    
    $pdo->exec($sql);
    echo "<p>✅ Submission scheduling table created successfully</p>";
    
    // Add scheduling settings
    $settings = [
        ['submission_scheduling_enabled', 'enabled'],
        ['submission_auto_check_interval', '60']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO settings (setting_key, value, created_at, updated_at) 
            VALUES (?, ?, NOW(), NOW())
        ");
        $stmt->execute($setting);
    }
    
    echo "<p>✅ Scheduling settings added successfully</p>";
    
    echo "<h3>Current Status:</h3>";
    echo "<p><a href='analyst/dashboard.php' target='_blank'>🔗 View Analyst Dashboard</a></p>";
    
    echo "<h3>Features Added:</h3>";
    echo "<ul>";
    echo "<li>✅ Schedule automatic enable/disable of submissions</li>";
    echo "<li>✅ Set specific date and time for actions</li>";
    echo "<li>✅ View pending schedules in dashboard</li>";
    echo "<li>✅ Cancel scheduled actions</li>";
    echo "<li>✅ Automatic execution of scheduled actions</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><small>You can delete this file after setup is complete.</small></p>";
?>
