<?php
include 'includes/db.php';

$filename = 'complete_database_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = __DIR__ . '/' . $filename;

echo "Generating complete database export...\n";
echo "File will be saved as: {$filename}\n\n";

$sql_content = "-- Complete Database Export for ManuelCode\n";
$sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
$sql_content .= "-- This file contains all tables, data, and structure\n\n";

$sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql_content .= "SET AUTOCOMMIT = 0;\n";
$sql_content .= "START TRANSACTION;\n";
$sql_content .= "SET time_zone = \"+00:00\";\n\n";

try {
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables to export\n\n";
    
    foreach ($tables as $table) {
        echo "Processing table: {$table}\n";
        
        $sql_content .= "-- Table structure for table `{$table}`\n";
        
        // Get table structure
        $create_table = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $create_sql = $create_table['Create Table'];
        
        // Remove DEFINER clauses that cause access denied errors
        $create_sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $create_sql);
        $create_sql = preg_replace('/SQL SECURITY DEFINER/', '', $create_sql);
        
        $sql_content .= $create_sql . ";\n\n";
        
        // Get table data count
        $count_result = $pdo->query("SELECT COUNT(*) as total FROM `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $total_rows = $count_result['total'];
        
        if ($total_rows > 0) {
            echo "  - Exporting {$total_rows} rows of data\n";
            
            $sql_content .= "-- Dumping data for table `{$table}`\n";
            
            // Get column names
            $columns_result = $pdo->query("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($columns_result, 'Field');
            $column_list = '`' . implode('`, `', $columns) . '`';
            
            // Export data in batches to avoid memory issues
            $batch_size = 100;
            $offset = 0;
            
            while ($offset < $total_rows) {
                $data = $pdo->query("SELECT * FROM `{$table}` LIMIT {$batch_size} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($data)) {
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
                
                $offset += $batch_size;
            }
        } else {
            echo "  - No data to export\n";
            $sql_content .= "-- No data to dump for table `{$table}`\n\n";
        }
    }
    
    $sql_content .= "COMMIT;\n";
    $sql_content .= "-- End of database export\n";
    
    // Write to file
    if (file_put_contents($filepath, $sql_content)) {
        echo "\n✅ Database export completed successfully!\n";
        echo "📁 File saved as: {$filename}\n";
        echo "📊 Total size: " . number_format(filesize($filepath)) . " bytes\n";
        echo "\nYou can now:\n";
        echo "1. Download this file to your computer\n";
        echo "2. Upload it to your new hosting environment\n";
        echo "3. Import it using phpMyAdmin or MySQL command line\n";
        echo "\nThe file contains:\n";
        echo "- All table structures (without DEFINER clauses)\n";
        echo "- All data from every table\n";
        echo "- Proper SQL formatting for easy import\n";
    } else {
        echo "\n❌ Error: Could not write to file\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error during export: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
}

echo "\nScript completed.\n";
?>
