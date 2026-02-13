<?php
/**
 * Sync Combination Allocated Subjects
 * Ensure ALL candidates are registered for ALL subjects allocated to their combination
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

use App\Models\CandidateSubjectSelection;
use App\Models\Candidate;
use App\Models\Combination;
use App\Models\ExamType;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SYNCING COMBINATION ALLOCATED SUBJECTS ===\n\n";

$acseeType = ExamType::where('code', 'ACSEE')->first();
if (!$acseeType) {
    echo "Error: ACSEE exam type not found\n";
    exit(1);
}

echo "ACSEE Exam Type ID: {$acseeType->id}\n\n";

// Get all candidates
$candidates = Candidate::all();
echo "Processing " . $candidates->count() . " candidates...\n\n";

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($candidates as $candidate) {
    if (empty($candidate->combination)) {
        continue;
    }
    
    // Get combination
    $combination = Combination::where('code', $candidate->combination)
        ->where('exam_type_id', $acseeType->id)
        ->first();
    
    if (!$combination) {
        continue;
    }
    
    // Get allocated subjects from the subjects string
    $subjectCodes = array_map('trim', explode(',', $combination->subjects));
    $allocatedSubjects = \App\Models\Subject::whereIn('code', $subjectCodes)
        ->where('exam_type_id', $acseeType->id)
        ->pluck('id')
        ->toArray();
    
    // For each allocated subject, ensure registration exists
    foreach ($allocatedSubjects as $subjectId) {
        $exists = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $acseeType->id)
            ->where('subject_id', $subjectId)
            ->exists();
        
        if (!$exists) {
            try {
                CandidateSubjectSelection::create([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $acseeType->id,
                    'exam_year_id' => 1,
                    'subject_id' => $subjectId,
                    'year' => 2026,
                    'is_active' => true,
                ]);
                
                $created++;
            } catch (\Exception $e) {
                echo "Error for {$candidate->candidate_id}: {$e->getMessage()}\n";
                $errors++;
            }
        } else {
            $skipped++;
        }
    }
}

echo "\n=== SYNC COMPLETE ===\n";
echo "Created: $created registrations\n";
echo "Skipped: $skipped (already exist)\n";
echo "Errors: $errors\n";

// Verify
echo "\nVerifying by combination...\n";
$combinations = Combination::where('exam_type_id', $acseeType->id)->get();

foreach ($combinations as $combination) {
    $candidates = Candidate::where('combination', $combination->code)->count();
    
    $subjectCodes = array_map('trim', explode(',', $combination->subjects));
    $allocatedSubjects = \App\Models\Subject::whereIn('code', $subjectCodes)
        ->where('exam_type_id', $acseeType->id)
        ->pluck('id')
        ->toArray();
    
    $expectedRegistrations = $candidates * count($allocatedSubjects);
    
    $actualRegistrations = CandidateSubjectSelection::whereHas('candidate', function($q) use ($combination) {
        $q->where('combination', $combination->code);
    })
    ->where('exam_type_id', $acseeType->id)
    ->count();
    
    $status = $expectedRegistrations === $actualRegistrations ? "✅" : "❌";
    echo "$status Combination {$combination->code}: Expected $expectedRegistrations, Got $actualRegistrations\n";
}

// Show sample verification
echo "\nSample verification (S1378-0508 - was missing Math):\n";
$candidate = Candidate::where('candidate_id', 'S1378-0508')->first();
$selections = CandidateSubjectSelection::where('candidate_id', $candidate->id)
    ->where('exam_type_id', $acseeType->id)
    ->with('subject')
    ->orderBy('subject_id')
    ->get();

echo "Registered subjects: " . $selections->count() . "\n";
foreach ($selections as $sel) {
    echo "  ✓ {$sel->subject->name}\n";
}
