<?php
/**
 * Enhanced Database and File System Sanitization Analysis Script
 * 
 * This enhanced script performs additional analysis including:
 * - Source code file reference checking
 * - More comprehensive file type scanning
 * - Detailed usage analysis
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
    'file_system_scan' => [],
    'source_code_references' => [],
    'files_in_source_code' => []
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ENHANCED DATABASE AND FILE SYSTEM SANITIZATION ANALYSIS ===\n";
    echo "Analysis started at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Phase 1: Enhanced File System Analysis
    echo "PHASE 1: ENHANCED FILE SYSTEM ANALYSIS\n";
    echo "======================================\n";
    
    $root_dir = __DIR__;
    $file_extensions = [
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp',
        'pdf', 'doc', 'docx', 'txt', 'rtf',
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm',
        'mp3', 'wav', 'ogg', 'aac',
        'zip', 'rar', '7z', 'tar', 'gz'
    ];
    
    echo "Scanning file system for media and document files...\n";
    
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
                'accessed' => date('Y-m-d H:i:s', fileatime($file)),
                'referenced_in_db' => false,
                'referenced_in_code' => false
            ];
            
            $analysis_results['file_system_scan'][] = $file_info;
            echo "Found: $relative_path (" . number_format($file_info['size']) . " bytes)\n";
        }
    }
    
    echo "\nTotal files found: " . count($analysis_results['file_system_scan']) . "\n\n";
    
    // Phase 2: Source Code Analysis
    echo "PHASE 2: SOURCE CODE ANALYSIS\n";
    echo "=============================\n";
    
    echo "Scanning source code for file references...\n";
    
    $source_files = glob($root_dir . "/**/*.{php,html,js,css}", GLOB_BRACE);
    $files_in_source = [];
    
    foreach ($source_files as $source_file) {
        if (is_file($source_file)) {
            $content = file_get_contents($source_file);
            $relative_source_path = str_replace($root_dir . '/', '', $source_file);
            
            // Check for file references in source code
            foreach ($analysis_results['file_system_scan'] as &$file_info) {
                $filename = $file_info['filename'];
                $relative_path = $file_info['relative_path'];
                
                // Check for various reference patterns
                $patterns = [
                    $filename,
                    $relative_path,
                    basename($relative_path),
                    str_replace('\\', '/', $relative_path)
                ];
                
                foreach ($patterns as $pattern) {
                    if (strpos($content, $pattern) !== false) {
                        $file_info['referenced_in_code'] = true;
                        $files_in_source[] = [
                            'file' => $relative_path,
                            'referenced_in' => $relative_source_path,
                            'pattern' => $pattern
                        ];
                        break 2; // Found reference, no need to check other patterns
                    }
                }
            }
        }
    }
    
    $analysis_results['files_in_source_code'] = $files_in_source;
    
    // Count files referenced in source code
    $referenced_in_code = 0;
    foreach ($analysis_results['file_system_scan'] as $file_info) {
        if ($file_info['referenced_in_code']) {
            $referenced_in_code++;
        }
    }
    
    echo "Files referenced in source code: $referenced_in_code\n\n";
    
    // Phase 3: Database Analysis
    echo "PHASE 3: DATABASE ANALYSIS\n";
    echo "==========================\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables in database:\n";
    foreach ($tables as $table) {
        $analysis_results['database_tables'][] = $table;
    }
    
    // Analyze tables with potential file references
    $file_reference_columns = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            $column_name = $column['Field'];
            $column_type = $column['Type'];
            
            // Check for file-related column names
            if (preg_match('/(file|image|attachment|media|path|url|doc|preview|gallery)/i', $column_name)) {
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
    
    // Phase 4: Cross-Reference Analysis
    echo "PHASE 4: CROSS-REFERENCE ANALYSIS\n";
    echo "==================================\n";
    
    // Check for orphaned files (files without database or code references)
    echo "Checking for orphaned files...\n";
    foreach ($analysis_results['file_system_scan'] as $file_info) {
        $is_referenced = false;
        $relative_path = $file_info['relative_path'];
        $filename = $file_info['filename'];
        
        // Check database references
        foreach ($file_reference_columns as $ref) {
            $table = $ref['table'];
            $column = $ref['column'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` LIKE ? OR `$column` LIKE ?");
            $stmt->execute(["%$filename%", "%$relative_path%"]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $is_referenced = true;
                $file_info['referenced_in_db'] = true;
                break;
            }
        }
        
        // If not referenced in database, check if referenced in code
        if (!$is_referenced && $file_info['referenced_in_code']) {
            $is_referenced = true;
        }
        
        if (!$is_referenced) {
            $analysis_results['orphaned_files'][] = $file_info;
            echo "ORPHANED: $relative_path\n";
        }
    }
    
    echo "\nOrphaned files found: " . count($analysis_results['orphaned_files']) . "\n\n";
    
    // Check for ghost records
    echo "Checking for ghost records...\n";
    foreach ($file_reference_columns as $ref) {
        $table = $ref['table'];
        $column = $ref['column'];
        
        $stmt = $pdo->prepare("SELECT `$column` FROM `$table` WHERE `$column` IS NOT NULL AND `$column` != ''");
        $stmt->execute();
        $file_paths = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($file_paths as $file_path) {
            $clean_path = $file_path;
            if (strpos($clean_path, '/') === 0) {
                $clean_path = substr($clean_path, 1);
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
    
    // Phase 5: Generate Enhanced Reports
    echo "PHASE 5: GENERATING ENHANCED REPORTS\n";
    echo "====================================\n";
    
    // Enhanced orphaned files report
    $enhanced_orphaned_report = "ENHANCED ORPHANED FILES REPORT\n";
    $enhanced_orphaned_report .= "==============================\n";
    $enhanced_orphaned_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (empty($analysis_results['orphaned_files'])) {
        $enhanced_orphaned_report .= "No orphaned files found.\n";
    } else {
        $enhanced_orphaned_report .= "Files that are not referenced in database OR source code:\n\n";
        foreach ($analysis_results['orphaned_files'] as $file) {
            $enhanced_orphaned_report .= "File: " . $file['relative_path'] . "\n";
            $enhanced_orphaned_report .= "Size: " . number_format($file['size']) . " bytes\n";
            $enhanced_orphaned_report .= "Modified: " . $file['modified'] . "\n";
            $enhanced_orphaned_report .= "Referenced in DB: " . ($file['referenced_in_db'] ? 'Yes' : 'No') . "\n";
            $enhanced_orphaned_report .= "Referenced in Code: " . ($file['referenced_in_code'] ? 'Yes' : 'No') . "\n";
            $enhanced_orphaned_report .= "---\n";
        }
    }
    
    // Source code references report
    $source_refs_report = "SOURCE CODE REFERENCES REPORT\n";
    $source_refs_report .= "=============================\n";
    $source_refs_report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (empty($analysis_results['files_in_source_code'])) {
        $source_refs_report .= "No file references found in source code.\n";
    } else {
        $source_refs_report .= "Files referenced in source code:\n\n";
        foreach ($analysis_results['files_in_source_code'] as $ref) {
            $source_refs_report .= "File: " . $ref['file'] . "\n";
            $source_refs_report .= "Referenced in: " . $ref['referenced_in'] . "\n";
            $source_refs_report .= "Pattern: " . $ref['pattern'] . "\n";
            $source_refs_report .= "---\n";
        }
    }
    
    // Save enhanced reports
    file_put_contents('enhanced_orphaned_files_report.txt', $enhanced_orphaned_report);
    file_put_contents('source_code_references_report.txt', $source_refs_report);
    
    echo "Enhanced reports generated:\n";
    echo "- enhanced_orphaned_files_report.txt\n";
    echo "- source_code_references_report.txt\n\n";
    
    // Summary
    echo "ENHANCED ANALYSIS SUMMARY\n";
    echo "=========================\n";
    echo "Database tables analyzed: " . count($analysis_results['database_tables']) . "\n";
    echo "File reference columns found: " . count($analysis_results['file_references']) . "\n";
    echo "Files scanned: " . count($analysis_results['file_system_scan']) . "\n";
    echo "Files referenced in source code: $referenced_in_code\n";
    echo "Orphaned files (no DB or code refs): " . count($analysis_results['orphaned_files']) . "\n";
    echo "Ghost records: " . count($analysis_results['ghost_records']) . "\n";
    echo "\nEnhanced analysis completed at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "Error during enhanced analysis: " . $e->getMessage() . "\n";
    exit(1);
}
?>
