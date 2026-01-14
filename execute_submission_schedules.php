<?php
// Automatic execution of scheduled submission actions
// This script should be run via cron job every minute or called manually

include 'includes/db.php';

echo "<h2>Submission Schedule Executor</h2>";
echo "<p>Checking for scheduled actions...</p>";

try {
    // Get all pending schedules that are due
    $current_datetime = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT * FROM submission_schedules 
        WHERE status = 'pending' 
        AND CONCAT(scheduled_date, ' ', scheduled_time) <= ?
        ORDER BY scheduled_date ASC, scheduled_time ASC
    ");
    $stmt->execute([$current_datetime]);
    $due_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($due_schedules)) {
        echo "<p>✅ No scheduled actions are due at this time.</p>";
        echo "<p>Current time: " . $current_datetime . "</p>";
    } else {
        echo "<p>Found " . count($due_schedules) . " scheduled action(s) to execute:</p>";
        
        foreach ($due_schedules as $schedule) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h3>Executing Schedule #" . $schedule['id'] . "</h3>";
            echo "<p><strong>Action:</strong> " . ucfirst($schedule['action']) . " Submissions</p>";
            echo "<p><strong>Scheduled for:</strong> " . $schedule['scheduled_date'] . " " . $schedule['scheduled_time'] . "</p>";
            if (!empty($schedule['notes'])) {
                echo "<p><strong>Notes:</strong> " . htmlspecialchars($schedule['notes']) . "</p>";
            }
            
            try {
                // Update the submission status
                $new_status = $schedule['action'] === 'enable' ? 'enabled' : 'disabled';
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, value, created_at, updated_at) 
                    VALUES ('submissions_enabled', ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()
                ");
                $stmt->execute([$new_status, $new_status]);
                
                // Mark schedule as executed
                $stmt = $pdo->prepare("
                    UPDATE submission_schedules 
                    SET status = 'executed', executed_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$schedule['id']]);
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO submission_analyst_logs (analyst_id, action, details, ip_address, user_agent, created_at)
                    VALUES (?, 'scheduled_submission_toggle', ?, 'system', 'scheduled_executor', NOW())
                ");
                $stmt->execute([
                    $schedule['created_by'], 
                    "Submissions " . $new_status . " by scheduled action (Schedule #" . $schedule['id'] . ")"
                ]);
                
                echo "<p style='color: green;'>✅ Successfully " . $schedule['action'] . "d submissions!</p>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error executing schedule: " . htmlspecialchars($e->getMessage()) . "</p>";
                
                // Mark as failed (you might want to create a 'failed' status)
                $stmt = $pdo->prepare("
                    UPDATE submission_schedules 
                    SET status = 'cancelled' 
                    WHERE id = ?
                ");
                $stmt->execute([$schedule['id']]);
            }
            
            echo "</div>";
        }
    }
    
    // Show next pending schedules
    $stmt = $pdo->prepare("
        SELECT * FROM submission_schedules 
        WHERE status = 'pending'
        ORDER BY scheduled_date ASC, scheduled_time ASC
        LIMIT 5
    ");
    $stmt->execute();
    $next_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($next_schedules)) {
        echo "<h3>Next Scheduled Actions:</h3>";
        echo "<ul>";
        foreach ($next_schedules as $schedule) {
            $scheduled_datetime = $schedule['scheduled_date'] . ' ' . $schedule['scheduled_time'];
            echo "<li>" . ucfirst($schedule['action']) . " submissions on " . date('M j, Y g:i A', strtotime($scheduled_datetime));
            if (!empty($schedule['notes'])) {
                echo " (" . htmlspecialchars($schedule['notes']) . ")";
            }
            echo "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><small>This script can be run manually or set up as a cron job to run every minute:</small></p>";
echo "<p><code>*/1 * * * * /usr/bin/php " . __FILE__ . " > /dev/null 2>&1</code></p>";
echo "<p><small>Or for Windows Task Scheduler, run this file every minute.</small></p>";
?>
