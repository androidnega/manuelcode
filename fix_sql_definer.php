<?php
/**
 * SQL File Fixer - Removes DEFINER clauses to fix access denied errors
 * Run this script to clean up the manuela-.sql file
 */

$inputFile = 'c:/Users/Mhanuel/Desktop/manuela-.sql';
$outputFile = 'c:/Users/Mhanuel/Desktop/manuela-fixed.sql';

echo "Starting SQL file fix...\n";

if (!file_exists($inputFile)) {
    die("Input file not found: $inputFile\n");
}

// Read the entire file
$content = file_get_contents($inputFile);
if ($content === false) {
    die("Failed to read input file\n");
}

echo "File size: " . number_format(strlen($content)) . " bytes\n";

// Pattern to match CREATE VIEW statements with DEFINER
$patterns = [
    // Remove DEFINER clause from CREATE VIEW statements
    '/CREATE\s+ALGORITHM=UNDEFINED\s+DEFINER=`[^`]+`@`[^`]+`\s+SQL\s+SECURITY\s+DEFINER\s+VIEW\s+`([^`]+)`\s+AS\s+/i' => 'CREATE VIEW `$1` AS ',
    
    // Remove DEFINER clause from CREATE PROCEDURE statements
    '/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+PROCEDURE\s+`([^`]+)`/i' => 'CREATE PROCEDURE `$1`',
    
    // Remove DEFINER clause from CREATE FUNCTION statements
    '/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+FUNCTION\s+`([^`]+)`/i' => 'CREATE FUNCTION `$1`',
    
    // Remove DEFINER clause from CREATE TRIGGER statements
    '/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+TRIGGER\s+`([^`]+)`/i' => 'CREATE TRIGGER `$1`',
    
    // Remove DEFINER clause from CREATE EVENT statements
    '/CREATE\s+DEFINER=`[^`]+`@`[^`]+`\s+EVENT\s+`([^`]+)`/i' => 'CREATE EVENT `$1`'
];

$originalContent = $content;
$fixedContent = $content;

echo "Applying fixes...\n";

foreach ($patterns as $pattern => $replacement) {
    $count = 0;
    $fixedContent = preg_replace($pattern, $replacement, $fixedContent, -1, $count);
    if ($count > 0) {
        echo "Fixed $count instances of pattern: " . substr($pattern, 0, 50) . "...\n";
    }
}

// Check if any changes were made
if ($fixedContent === $originalContent) {
    echo "No changes were made to the file.\n";
} else {
    // Write the fixed content to output file
    if (file_put_contents($outputFile, $fixedContent) !== false) {
        echo "Fixed SQL file saved to: $outputFile\n";
        echo "Original file preserved as: $inputFile\n";
        
        // Show some statistics
        $originalSize = strlen($originalContent);
        $fixedSize = strlen($fixedContent);
        $difference = $originalSize - $fixedSize;
        
        echo "File size change: " . number_format($difference) . " bytes removed\n";
        echo "Original size: " . number_format($originalSize) . " bytes\n";
        echo "Fixed size: " . number_format($fixedSize) . " bytes\n";
    } else {
        die("Failed to write output file\n");
    }
}

echo "SQL file fix completed!\n";
echo "\nInstructions:\n";
echo "1. Use the fixed file: $outputFile\n";
echo "2. Upload this fixed file to your database\n";
echo "3. The DEFINER clauses have been removed\n";
echo "4. This should resolve the access denied errors\n";
?>
