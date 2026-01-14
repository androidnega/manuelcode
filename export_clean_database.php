<?php
/**
 * Clean Database Export Script
 * Exports database without DEFINER clauses and ensures proper formatting
 */

// Database configuration
$host = "localhost";
$dbname = "manuela";
$username = "root";
$password = "newpassword";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database: $dbname\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Output file
$outputFile = 'manuela_clean_export.sql';

echo "Starting clean database export...\n";

// Start SQL file
$sql = "-- ManuelCode Database Export\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Database: $dbname\n";
$sql .= "-- This export is clean and ready for import on any server\n\n";

// Set SQL mode for compatibility
$sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql .= "SET AUTOCOMMIT = 0;\n";
$sql .= "START TRANSACTION;\n";
$sql .= "SET time_zone = \"+00:00\";\n\n";

// Get all tables
$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

echo "Found " . count($tables) . " tables\n";

// Export each table
foreach ($tables as $table) {
    echo "Exporting table: $table\n";
    
    try {
        // Get table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $createTable = $row[1];
        
        // Remove DEFINER clauses from CREATE TABLE
        $createTable = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/', '', $createTable);
        $createTable = preg_replace('/SQL SECURITY DEFINER/', '', $createTable);
        
        $sql .= "-- Table structure for table `$table`\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createTable . ";\n\n";
        
        // Get table data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $sql .= "-- Data for table `$table`\n";
            
            // Get column names
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            // Insert data in batches
            $batchSize = 100;
            for ($i = 0; $i < count($rows); $i += $batchSize) {
                $batch = array_slice($rows, $i, $batchSize);
                
                $sql .= "INSERT INTO `$table` ($columnList) VALUES\n";
                
                $values = [];
                foreach ($batch as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $rowValues[] = $pdo->quote($value);
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
    } catch (Exception $e) {
        echo "Warning: Error exporting table $table: " . $e->getMessage() . "\n";
        continue;
    }
}

// Export views (without DEFINER)
echo "Exporting views...\n";
try {
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $viewName = $row[0];
        echo "Exporting view: $viewName\n";
        
        $stmt2 = $pdo->query("SHOW CREATE VIEW `$viewName`");
        $row2 = $stmt2->fetch(PDO::FETCH_NUM);
        $createView = $row2[1];
        
        // Remove DEFINER clauses
        $createView = preg_replace('/CREATE\s+ALGORITHM=UNDEFINED\s+DEFINER=`[^`]+`@`[^`]+`\s+SQL\s+SECURITY\s+DEFINER\s+VIEW/', 'CREATE VIEW', $createView);
        
        $sql .= "-- View structure for view `$viewName`\n";
        $sql .= "DROP VIEW IF EXISTS `$viewName`;\n";
        $sql .= $createView . ";\n\n";
    }
} catch (Exception $e) {
    echo "Warning: Error exporting views: " . $e->getMessage() . "\n";
}

// Export stored procedures (without DEFINER)
echo "Exporting stored procedures...\n";
try {
    $stmt = $pdo->query("SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = '$dbname' AND ROUTINE_TYPE = 'PROCEDURE'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $procName = $row[0];
        echo "Exporting procedure: $procName\n";
        
        $stmt2 = $pdo->query("SHOW CREATE PROCEDURE `$procName`");
        $row2 = $stmt2->fetch(PDO::FETCH_NUM);
        $createProc = $row2[1];
        
        // Remove DEFINER clauses
        $createProc = preg_replace('/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+PROCEDURE/', 'CREATE PROCEDURE', $createProc);
        
        $sql .= "-- Procedure structure for procedure `$procName`\n";
        $sql .= "DROP PROCEDURE IF EXISTS `$procName`;\n";
        $sql .= $createProc . ";\n\n";
    }
} catch (Exception $e) {
    echo "Warning: Error exporting procedures: " . $e->getMessage() . "\n";
}

// Export functions (without DEFINER)
echo "Exporting functions...\n";
try {
    $stmt = $pdo->query("SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = '$dbname' AND ROUTINE_TYPE = 'FUNCTION'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $funcName = $row[0];
        echo "Exporting function: $funcName\n";
        
        $stmt2 = $pdo->query("SHOW CREATE FUNCTION `$funcName`");
        $row2 = $stmt2->fetch(PDO::FETCH_NUM);
        $createFunc = $row2[1];
        
        // Remove DEFINER clauses
        $createFunc = preg_replace('/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+FUNCTION/', 'CREATE FUNCTION', $createFunc);
        
        $sql .= "-- Function structure for function `$funcName`\n";
        $sql .= "DROP FUNCTION IF EXISTS `$funcName`;\n";
        $sql .= $createFunc . ";\n\n";
    }
} catch (Exception $e) {
    echo "Warning: Error exporting functions: " . $e->getMessage() . "\n";
}

// Export triggers (without DEFINER)
echo "Exporting triggers...\n";
try {
    $stmt = $pdo->query("SELECT TRIGGER_NAME FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA = '$dbname'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $triggerName = $row[0];
        echo "Exporting trigger: $triggerName\n";
        
        $stmt2 = $pdo->query("SHOW CREATE TRIGGER `$triggerName`");
        $row2 = $stmt2->fetch(PDO::FETCH_NUM);
        $createTrigger = $row2[1];
        
        // Remove DEFINER clauses
        $createTrigger = preg_replace('/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+TRIGGER/', 'CREATE TRIGGER', $createTrigger);
        
        $sql .= "-- Trigger structure for trigger `$triggerName`\n";
        $sql .= "DROP TRIGGER IF EXISTS `$triggerName`;\n";
        $sql .= $createTrigger . ";\n\n";
    }
} catch (Exception $e) {
    echo "Warning: Error exporting triggers: " . $e->getMessage() . "\n";
}

// End SQL file
$sql .= "COMMIT;\n";

// Write to file
if (file_put_contents($outputFile, $sql) !== false) {
    echo "\n✅ Database export completed successfully!\n";
    echo "📁 Output file: $outputFile\n";
    echo "📊 File size: " . number_format(strlen($sql)) . " bytes\n";
    echo "\n🚀 This file is ready to import on any server without DEFINER issues!\n";
    echo "💡 The submission form should work correctly after import.\n";
} else {
    echo "\n❌ Failed to write output file\n";
}

echo "\nExport process completed.\n";
?>
