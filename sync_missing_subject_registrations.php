<?php
/**
 * Sync Missing Subject Registrations
 * Creates candidate_subject_selections for subjects that have marks but no registration
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

use App\Models\CandidateSubjectSelection;
use App\Models\SubjectMarks;
use App\Models\ExamType;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SYNCING MISSING SUBJECT REGISTRATIONS ===\n\n";

// Get ACSEE exam type
$acseeType = ExamType::where('code', 'ACSEE')->first();
if (!$acseeType) {
    echo "Error: ACSEE exam type not found\n";
    exit(1);
}

echo "ACSEE Exam Type ID: {$acseeType->id}\n\n";

// Find candidates with marks but missing registrations
$marksData = SubjectMarks::where('exam_type_id', $acseeType->id)
    ->with('candidate', 'subject')
    ->get();

echo "Processing " . $marksData->count() . " marks records...\n\n";

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($marksData as $mark) {
    // Check if registration exists
    $exists = CandidateSubjectSelection::where('candidate_id', $mark->candidate_id)
        ->where('exam_type_id', $acseeType->id)
        ->where('subject_id', $mark->subject_id)
        ->exists();
    
    if ($exists) {
        $skipped++;
        continue;
    }
    
    // Create registration
    try {
        CandidateSubjectSelection::create([
            'candidate_id' => $mark->candidate_id,
            'exam_type_id' => $acseeType->id,
            'exam_year_id' => 1, // Assuming 2026 is exam_year_id 1
            'subject_id' => $mark->subject_id,
            'year' => 2026,
            'is_active' => true,
        ]);
        
        $created++;
        
        if ($created % 50 == 0) {
            echo "Created $created registrations...\n";
        }
    } catch (\Exception $e) {
        echo "Error creating registration for candidate {$mark->candidate->candidate_id}, subject {$mark->subject->name}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n=== SYNC COMPLETE ===\n";
echo "Created: $created\n";
echo "Skipped (already exist): $skipped\n";
echo "Errors: $errors\n";

// Verify
echo "\nVerifying by candidate...\n";
$candidates = DB::table('subject_marks')
    ->where('exam_type_id', $acseeType->id)
    ->distinct()
    ->pluck('candidate_id');

$totalRegistrations = CandidateSubjectSelection::whereIn('candidate_id', $candidates)
    ->where('exam_type_id', $acseeType->id)
    ->count();

$totalMarks = SubjectMarks::where('exam_type_id', $acseeType->id)->count();

echo "Total registrations now: $totalRegistrations\n";
echo "Total marks: $totalMarks\n";

if ($totalRegistrations === $totalMarks) {
    echo "\n✅ SUCCESS: All marks now have corresponding registrations!\n";
} else {
    echo "\n⚠️  WARNING: Still have mismatches\n";
    echo "   Registrations: $totalRegistrations\n";
    echo "   Marks: $totalMarks\n";
    echo "   Difference: " . abs($totalRegistrations - $totalMarks) . "\n";
}

// Show sample
echo "\nSample verification (Candidate S1378-0501):\n";
$candidate = \App\Models\Candidate::where('candidate_id', 'S1378-0501')->first();
$selections = CandidateSubjectSelection::where('candidate_id', $candidate->id)
    ->where('exam_type_id', $acseeType->id)
    ->with('subject')
    ->orderBy('subject_id')
    ->get();

echo "Registered subjects: " . $selections->count() . "\n";
foreach ($selections as $sel) {
    echo "  ✓ {$sel->subject->name}\n";
}
