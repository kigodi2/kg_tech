<?php
/**
 * Register Candidate Subjects Based on Combinations
 * 
 * Creates CandidateSubjectSelection records for all ACSEE candidates
 * based on their registered combination.
 * 
 * Usage: php register_candidate_subjects.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;
use App\Models\Combination;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;

echo "\n=== Registering Candidate Subjects Based on Combinations ===\n";

// Find all ACSEE candidates with combinations
$candidates = Candidate::where('exam_type', 'ACSEE')
    ->whereNotNull('combination')
    ->get();

echo "Found " . $candidates->count() . " ACSEE candidates with combinations\n\n";

$acsee = ExamType::where('code', 'ACSEE')->first();
if (!$acsee) {
    echo "❌ ACSEE exam type not found\n";
    exit(1);
}

$created = 0;
$skipped = 0;
$failed = 0;

foreach ($candidates as $candidate) {
    // Get combination
    $combination = Combination::where('code', trim($candidate->combination))->first();
    
    if (!$combination) {
        echo "  ⚠ {$candidate->candidate_id}: Combination '{$candidate->combination}' not found\n";
        $failed++;
        continue;
    }
    
    // Get exam registrations for this candidate
    $registrations = $candidate->examRegistrations()
        ->where('exam_type_id', $acsee->id)
        ->get();
    
    if ($registrations->isEmpty()) {
        echo "  ⚠ {$candidate->candidate_id}: No ACSEE registrations found\n";
        $failed++;
        continue;
    }
    
    // Get subjects for this combination
    $subjects = $combination->subjects()->get();
    
    if ($subjects->isEmpty()) {
        echo "  ⚠ {$candidate->candidate_id}: Combination '{$candidate->combination}' has no subjects\n";
        $failed++;
        continue;
    }
    
    // Register each subject for each exam registration
    foreach ($registrations as $registration) {
        foreach ($subjects as $subject) {
            // Check if already registered
            $existing = CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('exam_type_id', $acsee->id)
                ->where('exam_year_id', $registration->exam_year_id)
                ->where('subject_id', $subject->id)
                ->exists();
            
            if ($existing) {
                $skipped++;
                continue;
            }
            
            try {
                CandidateSubjectSelection::create([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $acsee->id,
                    'exam_year_id' => $registration->exam_year_id,
                    'subject_id' => $subject->id,
                    'year' => (int)$registration->examYear->year_label,
                ]);
                $created++;
            } catch (\Exception $e) {
                echo "  ✗ {$candidate->candidate_id} - {$subject->code}: " . $e->getMessage() . "\n";
                $failed++;
            }
        }
    }
    
    echo "  ✓ {$candidate->candidate_id}: Registered {$subjects->count()} subjects\n";
}

echo "\n=== Summary ===\n";
echo "✓ Created: $created\n";
echo "⊘ Skipped: $skipped\n";
echo "✗ Failed: $failed\n";
echo "\nDone!\n\n";
