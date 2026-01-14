<?php
/**
 * Database and File System Sanitization Analysis Script
 * 
 * This script analyzes the website's database and file system to identify:
 * - Orphaned files (files without database references)
 * - Ghost records (database entries without corresponding files)
 * - Duplicate files
 * - Unused files
 * 
 * IMPORTANT: This script only performs analysis and reporting.
 * NO DELETE or DROP operations are executed.
 */

// Database configuration
$host = "localhost";
$dbname = "manuelcode_db";
$username = "root";
$password = "newpassword";

// Initialize analysis results
$analysis_results = [
    'database_tables' => [],
    'file_references' => [],
    'orphaned_files' => [],
    'ghost_records' => [],
    'duplicate_files' => [],
    'unused_files' => [],
    'file_system_scan' => []
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DATABASE AND FILE SYSTEM SANITIZATION ANALYSIS ===\n";
    echo "Analysis started at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Phase 1: Database Structure Analysis
    echo "PHASE 1: DATABASE STRUCTURE ANALYSIS\n";
    echo "====================================\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        $analysis_results['database_tables'][] = $table;
    }
    echo "\n";
    
    // Analyze tables with potential file references
    $file_reference_tables = [];
    $file_reference_columns = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            $column_name = $column['Field'];
            $column_type = $column['Type'];
            
            // Check for file-related column names
            if (preg_match('/(file|image|attachment|media|path|url|doc|preview)/i', $column_name)) {
                $file_reference_tables[$table][] = $column_name;
                $file_reference_columns[] = [
                    'table' => $table,
                    'column' => $column_name,
                    'type' => $column_type
                ];
                
                echo "Found file reference: $table.$column_name ($column_type)\n";
            }
        }
    }
    
    $analysis_results['file_references'] = $file_reference_columns;
    echo "\nTotal file reference columns found: " . count($file_reference_columns) . "\n\n";
    
    // Phase 2: File System Analysis
    echo "PHASE 2: FILE SYSTEM ANALYSIS\n";
    echo "=============================\n";
    
    $root_dir = __DIR__;
    $file_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf', 'doc', 'docx', 'mp4', 'avi', 'mov'];
    
    echo "Scanning file system for media files...\n";
    
    foreach ($file_extensions as $ext) {
        $files = glob($root_dir . "/**/*.$ext", GLOB_BRACE);
        foreach ($files as $file) {
            $relative_path = str_replace($root_dir . '/', '', $file);
            $file_info = [
                'full_path' => $file,
                'relative_path' => $relative_path,
                'filename' => basename($file),
                'extension' => $ext,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
                'accessed' => date('Y-m-d H:i:s', fileatime($file))
            ];
            
            $analysis_results['file_system_scan'][] = $file_info;
            echo "Found: $relative_path (" . number_format($file_info['size']) . " bytes)\n";
        }
    }
    
    echo "\nTotal media files found: " . count($analysis_results['file_system_scan']) . "\n\n";
    
    // Phase 3: Cross-Reference Analysis
    echo "PHASE 3: CROSS-REFERENCE ANALYSIS\n";
    echo "=================================\n";
    
    // Check for orphaned files (files without database references)
    echo "Checking for orphaned files...\n";
    foreach ($analysis_results['file_system_scan'] as $file_info) {
        $is_referenced = false;
        $relative_path = $file_info['relative_path'];
        $filename = $file_info['filename'];
        
        // Check each file reference column
        foreach ($file_reference_columns as $ref) {
            $table = $ref['table'];
            $column = $ref['column'];
            
            // Search for file references in database
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` LIKE ? OR `$column` LIKE ?");
            $stmt->execute(["%$filename%", "%$relative_path%"]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $is_referenced = true;
                break;
            }
        }
        
        if (!$is_referenced) {
            $analysis_results['orphaned_files'][] = $file_info;
            echo "ORPHANED: $relative_path\n";
        }
    }
    
    echo "\nOrphaned files found: " . count($analysis_results['orphaned_files']) . "\n\n";
    
    // Check for ghost records (database entries without files)
    echo "Checking for ghost records...\n";
    foreach ($file_reference_columns as $ref) {
        $table = $ref['table'];
        $column = $ref['column'];
        
        $stmt = $pdo->prepare("SELECT `$column` FROM `$table` WHERE `$column` IS NOT NULL AND `$column` != ''");
        $stmt->execute();
        $file_paths = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($file_paths as $file_path) {
            // Clean up the file path
            $clean_path = $file_path;
            if (strpos($clean_path, '/') === 0) {
                $clean_path = substr($clean_path, 1); // Remove leading slash
            }
            
            $full_path = $root_dir . '/' . $clean_path;
            
            if (!file_exists($full_path)) {
                $ghost_record = [
                    'table' => $table,
                    'column' => $column,
                    'file_path' => $file_path,
                    'full_path' => $full_path
                ];
                $analysis_results['ghost_records'][] = $ghost_record;
                echo "GHOST: $table.$column = '$file_path' (file not found)\n";
            }
        }
    }
    
    echo "\nGhost records found: " . count($analysis_results['ghost_records']) . "\n\n";
    
    // Phase 4: Duplicate File Analysis
    echo "PHASE 4: DUPLICATE FILE ANALYSIS\n";
    echo "================================\n";
    
    // Group files by size first (quick check)
    $files_by_size = [];
    foreach ($analysis_results['file_system_scan'] as $file_info) {
        $size = $file_info['size'];
        if (!isset($files_by_size[$size])) {
            $files_by_size[$size] = [];
        }
        $files_by_size[$size][] = $file_info;
    }
    
    // Check for potential duplicates (same size)
    foreach ($files_by_size as $size => $files) {
        if (count($files) > 1) {
            echo "Potential duplicates (size: " . number_format($size) . " bytes):\n";
            foreach ($files as $file) {
                echo "  - " . $file['relative_path'] . "\n";
            }
            $analysis_results['duplicate_files'] = array_merge($analysis_results['duplicate_files'], $files);
            echo "\n";
        }
    }
    
    echo "Potential duplicate files found: " . count($analysis_results['duplicate_files']) . "\n\n";
    
    // Phase 5: Generate Reports
    echo "PHASE 5: GENERATING REPORTS\n";
    echo "===========================\n";
    
    // Generate orphaned files report
    $orphaned_report = "ORPHANED FILES REPORT\n";
    $orphaned_report .= "=====================\n";
    $orphaned_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (empty($analysis_results['orphaned_files'])) {
        $orphaned_report .= "No orphaned files found.\n";
    } else {
        foreach ($analysis_results['orphaned_files'] as $file) {
            $orphaned_report .= "File: " . $file['relative_path'] . "\n";
            $orphaned_report .= "Size: " . number_format($file['size']) . " bytes\n";
            $orphaned_report .= "Modified: " . $file['modified'] . "\n";
            $orphaned_report .= "Accessed: " . $file['accessed'] . "\n";
            $orphaned_report .= "---\n";
        }
    }
    
    // Generate ghost records report
    $ghost_report = "GHOST RECORDS REPORT\n";
    $ghost_report .= "===================\n";
    $ghost_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (empty($analysis_results['ghost_records'])) {
        $ghost_report .= "No ghost records found.\n";
    } else {
        foreach ($analysis_results['ghost_records'] as $record) {
            $ghost_report .= "Table: " . $record['table'] . "\n";
            $ghost_report .= "Column: " . $record['column'] . "\n";
            $ghost_report .= "File Path: " . $record['file_path'] . "\n";
            $ghost_report .= "Full Path: " . $record['full_path'] . "\n";
            $ghost_report .= "---\n";
        }
    }
    
    // Generate duplicate files report
    $duplicate_report = "DUPLICATE FILES REPORT\n";
    $duplicate_report .= "======================\n";
    $duplicate_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (empty($analysis_results['duplicate_files'])) {
        $duplicate_report .= "No duplicate files found.\n";
    } else {
        foreach ($analysis_results['duplicate_files'] as $file) {
            $duplicate_report .= "File: " . $file['relative_path'] . "\n";
            $duplicate_report .= "Size: " . number_format($file['size']) . " bytes\n";
            $duplicate_report .= "---\n";
        }
    }
    
    // Save reports to files
    file_put_contents('orphaned_files_report.txt', $orphaned_report);
    file_put_contents('ghost_records_report.txt', $ghost_report);
    file_put_contents('duplicate_files_report.txt', $duplicate_report);
    
    echo "Reports generated:\n";
    echo "- orphaned_files_report.txt\n";
    echo "- ghost_records_report.txt\n";
    echo "- duplicate_files_report.txt\n\n";
    
    // Generate SQL scripts for review
    echo "Generating SQL scripts for review...\n";
    
    // SQL script to mark ghost records as deleted
    $ghost_sql = "-- GHOST RECORDS CLEANUP SCRIPT (FOR REVIEW ONLY)\n";
    $ghost_sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $ghost_sql .= "-- WARNING: Review each statement before execution\n\n";
    
    foreach ($analysis_results['ghost_records'] as $record) {
        $ghost_sql .= "-- Ghost record in " . $record['table'] . "." . $record['column'] . "\n";
        $ghost_sql .= "-- File path: " . $record['file_path'] . "\n";
        $ghost_sql .= "-- UPDATE `" . $record['table'] . "` SET `" . $record['column'] . "` = NULL WHERE `" . $record['column'] . "` = '" . addslashes($record['file_path']) . "';\n\n";
    }
    
    // SQL script to delete ghost records (commented out)
    $delete_sql = "-- GHOST RECORDS DELETE SCRIPT (FOR REVIEW ONLY)\n";
    $delete_sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $delete_sql .= "-- WARNING: This will permanently delete records. Review carefully!\n\n";
    
    foreach ($analysis_results['ghost_records'] as $record) {
        $delete_sql .= "-- DELETE FROM `" . $record['table'] . "` WHERE `" . $record['column'] . "` = '" . addslashes($record['file_path']) . "';\n";
    }
    
    file_put_contents('ghost_records_cleanup.sql', $ghost_sql);
    file_put_contents('ghost_records_delete.sql', $delete_sql);
    
    echo "SQL scripts generated:\n";
    echo "- ghost_records_cleanup.sql (UPDATE statements)\n";
    echo "- ghost_records_delete.sql (DELETE statements - commented)\n\n";
    
    // Summary
    echo "ANALYSIS SUMMARY\n";
    echo "===============\n";
    echo "Database tables analyzed: " . count($analysis_results['database_tables']) . "\n";
    echo "File reference columns found: " . count($analysis_results['file_references']) . "\n";
    echo "Files scanned: " . count($analysis_results['file_system_scan']) . "\n";
    echo "Orphaned files: " . count($analysis_results['orphaned_files']) . "\n";
    echo "Ghost records: " . count($analysis_results['ghost_records']) . "\n";
    echo "Potential duplicates: " . count($analysis_results['duplicate_files']) . "\n";
    echo "\nAnalysis completed at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "Error during analysis: " . $e->getMessage() . "\n";
    exit(1);
}
?>
