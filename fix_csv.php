<?php
/**
 * Quick CSV Fixer Script
 * 
 * Usage: php fix_csv.php input.csv output.csv
 * 
 * This script:
 * 1. Takes your CSV with school code S0108
 * 2. Replaces it with DSM001
 * 3. Saves to a new CSV file
 * 4. CSV is ready to import
 */

if ($argc < 3) {
    echo "❌ Usage: php fix_csv.php <input.csv> <output.csv>\n";
    echo "   Example: php fix_csv.php candidates.csv candidates_fixed.csv\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

// Check if input file exists
if (!file_exists($inputFile)) {
    echo "❌ Input file not found: $inputFile\n";
    exit(1);
}

// Mapping of school codes
// Add more mappings as needed
$schoolMappings = [
    'S0108' => 'DSM001',
    'S0109' => 'DSM001',
    'S0110' => 'DSM001',
    // Add more mappings here if needed
    // 'S0111' => 'DSM002',
];

echo "📂 Processing: $inputFile\n";
echo "💾 Output: $outputFile\n\n";

$input = fopen($inputFile, 'r');
$output = fopen($outputFile, 'w');

if (!$input || !$output) {
    echo "❌ Could not open files\n";
    exit(1);
}

$rowNumber = 0;
$replacementCount = 0;

while (($row = fgetcsv($input)) !== false) {
    $rowNumber++;
    
    // Column 5 (index 4) is the School ID column
    if (isset($row[4])) {
        $originalCode = $row[4];
        
        // Check if this code needs replacement
        if (isset($schoolMappings[$originalCode])) {
            $row[4] = $schoolMappings[$originalCode];
            $replacementCount++;
            
            if ($rowNumber === 1) {
                echo "ℹ️  Header row (not modified)\n";
            } else {
                echo "✏️  Row $rowNumber: Replaced '$originalCode' → '{$row[4]}'\n";
            }
        }
    }
    
    fputcsv($output, $row);
}

fclose($input);
fclose($output);

echo "\n✅ Done!\n";
echo "   Replacements made: $replacementCount\n";
echo "   Output file: $outputFile\n";
echo "   Ready to import!\n";
?>
