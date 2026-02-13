<?php
/**
 * Import correct marks from CSV files in storage
 * Process individual paper marks and calculate totals
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

use App\Models\SubjectMarks;
use App\Models\Candidate;
use App\Models\Subject;
use App\Models\ExamType;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== IMPORTING CORRECT MARKS FROM CSV FILES ===\n\n";

// Get ACSEE exam type
$acseeType = ExamType::where('code', 'ACSEE')->first();
if (!$acseeType) {
    echo "Error: ACSEE exam type not found\n";
    exit(1);
}

echo "ACSEE Exam Type ID: {$acseeType->id}\n";

// Find the latest import directory
$importPath = '/home/prosmart-technologies/SOL/irms/storage/app/temp/imports';
$dirs = array_filter(glob("$importPath/*"), 'is_dir');
rsort($dirs);
$latestDir = reset($dirs);

if (!$latestDir) {
    echo "Error: No import directories found\n";
    exit(1);
}

echo "Using import directory: $latestDir\n\n";

// Get all CSV files
$csvFiles = glob("$latestDir/*.csv");
echo "Found " . count($csvFiles) . " CSV files\n\n";

$totalImported = 0;

// Process each CSV file
foreach ($csvFiles as $csvFile) {
    $filename = basename($csvFile);
    echo "Processing: $filename\n";
    
    // Extract subject code and name
    preg_match('/(\d+)_(.+)\.csv/', $filename, $matches);
    $subjectCode = $matches[1];
    $subjectName = $matches[2];
    
    // Find subject
    $subject = Subject::where('code', $subjectCode)
        ->where('exam_type_id', $acseeType->id)
        ->first();
    
    if (!$subject) {
        echo "  ⚠ Subject not found: $subjectCode ($subjectName)\n";
        continue;
    }
    
    echo "  ✓ Subject: {$subject->name} (ID: {$subject->id})\n";
    
    // Read CSV
    $handle = fopen($csvFile, 'r');
    $headers = fgetcsv($handle);
    
    echo "  Columns: " . implode(', ', $headers) . "\n";
    
    $rowCount = 0;
    $importedCount = 0;
    
    while ($row = fgetcsv($handle)) {
        $rowCount++;
        
        // Map columns
        $indexNumber = trim($row[0]);
        
        if (empty($indexNumber)) {
            continue;
        }
        
        // Find candidate by index number
        $candidate = Candidate::whereHas('examRegistrations', function($q) use ($acseeType) {
            $q->where('exam_type_id', $acseeType->id);
        })
        ->where('candidate_id', $indexNumber)
        ->first();
        
        if (!$candidate) {
            continue;
        }
        
        // Collect paper marks
        $paperMarks = [];
        for ($i = 2; $i < count($headers); $i++) {
            $value = isset($row[$i]) ? trim($row[$i]) : null;
            if ($value !== '' && $value !== null) {
                $paperMarks[] = (float)$value;
            }
        }
        
        if (empty($paperMarks)) {
            continue;
        }
        
        // Calculate total marks
        $totalMarks = array_sum($paperMarks);
        $marksCount = count($paperMarks);
        
        // Calculate percentage
        $percentage = ($totalMarks / 100) * 100;
        
        // Assign grade based on percentage
        if ($percentage >= 80) {
            $grade = 'A';
        } elseif ($percentage >= 70) {
            $grade = 'B';
        } elseif ($percentage >= 60) {
            $grade = 'C';
        } elseif ($percentage >= 50) {
            $grade = 'D';
        } elseif ($percentage >= 40) {
            $grade = 'E';
        } else {
            $grade = 'F';
        }
        
        // Update or create subject marks
        SubjectMarks::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $acseeType->id,
            ],
            [
                'year' => 2026,
                'marks_obtained' => $totalMarks,
                'max_marks' => 100,
                'percentage' => $percentage,
                'grade' => $grade,
            ]
        );
        
        $importedCount++;
    }
    
    fclose($handle);
    
    echo "  ✓ Imported $importedCount candidates\n\n";
    $totalImported += $importedCount;
}

echo "=== IMPORT COMPLETE ===\n";
echo "Total records imported: $totalImported\n";
echo "\nVerifying imported data...\n";

// Verify
$marksWithValues = SubjectMarks::where('exam_type_id', $acseeType->id)
    ->whereNotNull('marks_obtained')
    ->count();
    
echo "Marks with values: $marksWithValues\n";

// Show sample
$sample = SubjectMarks::where('exam_type_id', $acseeType->id)
    ->with('candidate', 'subject')
    ->limit(5)
    ->get();

echo "\nSample imported marks:\n";
foreach ($sample as $mark) {
    echo "  {$mark->candidate->candidate_id} - {$mark->subject->name}: {$mark->marks_obtained} ({$mark->grade})\n";
}

echo "\nDone!\n";
