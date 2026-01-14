<?php
include 'includes/db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key = ?");
    $stmt->execute(['submission_price']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Submission Price Setting:\n";
    if ($result) {
        echo "Value: " . $result['value'] . "\n";
        echo "Type: " . gettype($result['value']) . "\n";
        echo "Float value: " . floatval($result['value']) . "\n";
        echo "Int value (kobo): " . intval(floatval($result['value']) * 100) . "\n";
    } else {
        echo "NOT SET\n";
    }
    
    // Check all settings
    echo "\nAll Settings:\n";
    $stmt = $pdo->query("SELECT setting_key, value FROM settings ORDER BY setting_key");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['setting_key'] . ": " . $row['value'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
