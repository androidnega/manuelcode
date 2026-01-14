<?php
// Setup script for submission toggle functionality
include 'includes/db.php';

echo "<h2>Submission Toggle Setup</h2>";

try {
    // Check if the setting already exists
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'submissions_enabled'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>✅ Submission toggle setting already exists: <strong>" . htmlspecialchars($result['value']) . "</strong></p>";
    } else {
        // Create the setting with default value 'enabled'
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, value, created_at, updated_at) 
            VALUES ('submissions_enabled', 'enabled', NOW(), NOW())
        ");
        
        if ($stmt->execute()) {
            echo "<p>✅ Submission toggle setting created successfully with default value: <strong>enabled</strong></p>";
        } else {
            echo "<p>❌ Failed to create submission toggle setting</p>";
        }
    }
    
    echo "<h3>Current Status:</h3>";
    echo "<p><a href='submission.php' target='_blank'>🔗 View Submission Page</a></p>";
    echo "<p><a href='analyst/dashboard.php' target='_blank'>🔗 View Analyst Dashboard</a></p>";
    
    echo "<h3>How to Use:</h3>";
    echo "<ol>";
    echo "<li>Login to the analyst dashboard</li>";
    echo "<li>Look for the 'Submission System Control' section</li>";
    echo "<li>Click 'Enable Submissions' or 'Disable Submissions' as needed</li>";
    echo "<li>The submission form will be hidden/shown to students immediately</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><small>You can delete this file after setup is complete.</small></p>";
?>
