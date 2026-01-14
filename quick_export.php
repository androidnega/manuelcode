<?php
// Quick Database Export Script
// Run this in your browser to generate the complete SQL file

include 'includes/db.php';

$filename = 'manuelcode_complete_database.sql';
$filepath = __DIR__ . '/' . $filename;

echo "<h2>ManuelCode Database Export</h2>";
echo "<p>Generating complete database export...</p>";

$sql_content = "-- ManuelCode Complete Database Export\n";
$sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql_content .= "-- Contains: All tables, structure, and data\n\n";

$sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql_content .= "SET AUTOCOMMIT = 0;\n";
$sql_content .= "START TRANSACTION;\n";
$sql_content .= "SET time_zone = \"+00:00\";\n\n";

try {
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Found " . count($tables) . " tables</p>";
    
    foreach ($tables as $table) {
        echo "<p>Processing: <strong>{$table}</strong></p>";
        
        // Table structure
        $create_table = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $create_sql = $create_table['Create Table'];
        
        // Remove DEFINER clauses
        $create_sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $create_sql);
        $create_sql = preg_replace('/SQL SECURITY DEFINER/', '', $create_sql);
        
        $sql_content .= "-- Table: `{$table}`\n";
        $sql_content .= $create_sql . ";\n\n";
        
        // Table data
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        
        if ($count > 0) {
            echo "<p>  - Exporting {$count} rows</p>";
            
            $data = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_keys($data[0]);
            $column_list = '`' . implode('`, `', $columns) . '`';
            
            $sql_content .= "-- Data for table `{$table}`\n";
            $sql_content .= "INSERT INTO `{$table}` ({$column_list}) VALUES\n";
            
            $values = [];
            foreach ($data as $row) {
                $row_values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $row_values[] = 'NULL';
                    } else {
                        $row_values[] = $pdo->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $row_values) . ')';
            }
            
            $sql_content .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    $sql_content .= "COMMIT;\n";
    
    // Save file
    if (file_put_contents($filepath, $sql_content)) {
        echo "<h3>✅ Export Complete!</h3>";
        echo "<p><strong>File:</strong> {$filename}</p>";
        echo "<p><strong>Size:</strong> " . number_format(filesize($filepath)) . " bytes</p>";
        echo "<p><strong>Location:</strong> " . realpath($filepath) . "</p>";
        
        echo "<h4>Next Steps:</h4>";
        echo "<ol>";
        echo "<li>Download the SQL file</li>";
        echo "<li>Upload to your new hosting</li>";
        echo "<li>Import via phpMyAdmin or MySQL</li>";
        echo "</ol>";
        
        echo "<p><a href='{$filename}' download class='btn btn-primary'>Download SQL File</a></p>";
    } else {
        echo "<p>❌ Error saving file</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.btn { display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; }
.btn:hover { background: #005a87; }
</style>
