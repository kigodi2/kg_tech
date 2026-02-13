<?php
/**
 * TEMPORARY: Populate marks for testing/demonstration
 * This script adds marks_obtained and grades for all registered candidates
 * Run only if marks CSV was not uploaded
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\SubjectMarks;
use App\Models\GradingProfile;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the ACSEE exam type
$acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
if (!$acseeType) {
    echo "ACSEE exam type not found\n";
    exit(1);
}

echo "Populating marks for ACSEE (ID: {$acseeType->id})\n";

// Get all empty marks
$emptyMarks = SubjectMarks::where('exam_type_id', $acseeType->id)
    ->whereNull('marks_obtained')
    ->get();

echo "Found {$emptyMarks->count()} empty marks records\n";

$gradesMap = [
    1 => 'A',
    2 => 'B',
    3 => 'C',
    4 => 'D',
    5 => 'E',
];

$updated = 0;
foreach ($emptyMarks as $mark) {
    // Generate random marks (45-95)
    $marksObtained = rand(45, 95);
    $percentage = ($marksObtained / 100) * 100;
    
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
    
    $mark->update([
        'marks_obtained' => $marksObtained,
        'percentage' => $percentage,
        'grade' => $grade,
    ]);
    
    $updated++;
    if ($updated % 50 == 0) {
        echo "Updated $updated marks...\n";
    }
}

echo "\nCompleted: Updated $updated marks\n";
echo "Marks are now visible in hierarchy/school/29/results\n";
