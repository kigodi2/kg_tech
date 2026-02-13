<?php

// Run from command line: php test_exam_year_display.php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;

echo "\n=== EXAM YEAR DISPLAY DIAGNOSTIC ===\n\n";

// Check candidates with ACSEE exam type
$acseeCount = Candidate::where('exam_type', 'ACSEE')->count();
echo "Total ACSEE Candidates: $acseeCount\n\n";

// Check sample candidates
$candidates = Candidate::where('exam_type', 'ACSEE')
    ->with('examRegistrations.examYear')
    ->limit(5)
    ->get();

echo "Sample Candidates with Details:\n";
echo str_repeat("-", 100) . "\n";

foreach ($candidates as $candidate) {
    echo "\nCandidate: {$candidate->candidate_id} - {$candidate->full_name}\n";
    echo "  Exam Type: {$candidate->exam_type}\n";
    echo "  Combination: {$candidate->combination}\n";
    
    // Check registrations
    $registrations = $candidate->examRegistrations()->with('examYear')->get();
    echo "  Registrations Count: " . $registrations->count() . "\n";
    
    foreach ($registrations as $reg) {
        echo "    - Registration ID: {$reg->id}\n";
        echo "      Exam Type ID: {$reg->exam_type_id}\n";
        echo "      Exam Year ID: {$reg->exam_year_id}\n";
        echo "      Year Field: {$reg->year}\n";
        
        if ($reg->examYear) {
            echo "      Related ExamYear: ID={$reg->examYear->id}, Label={$reg->examYear->year_label}\n";
        } else {
            echo "      Related ExamYear: NOT FOUND (exam_year_id={$reg->exam_year_id})\n";
        }
    }
    
    // Test the accessor
    $examYear = $candidate->exam_year;
    echo "  getExamYearAttribute() Result: " . ($examYear ?? "null") . "\n";
}

echo "\n" . str_repeat("-", 100) . "\n";

// Check ExamYear records
echo "\nExamYear Records in Database:\n";
$years = \App\Models\ExamYear::all();
foreach ($years as $year) {
    echo "  - ID={$year->id}, Label={$year->year_label}, Active={$year->is_active}\n";
}

// Check registration records without exam_year_id
echo "\nRegistrations with NULL exam_year_id:\n";
$nullYearRegs = CandidateExamRegistration::whereNull('exam_year_id')->count();
echo "  Count: $nullYearRegs\n";

if ($nullYearRegs > 0) {
    echo "  Sample:\n";
    $samples = CandidateExamRegistration::whereNull('exam_year_id')
        ->with('candidate', 'examType')
        ->limit(3)
        ->get();
    
    foreach ($samples as $sample) {
        echo "    - ID={$sample->id}, Candidate={$sample->candidate->candidate_id}, Year={$sample->year}\n";
    }
}

echo "\n=== END DIAGNOSTIC ===\n\n";
