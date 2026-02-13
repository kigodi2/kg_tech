<?php
/**
 * Fix Missing Exam Registrations
 * 
 * This script creates CandidateExamRegistration records for ACSEE candidates
 * who were imported before the exam_year fix was applied.
 * 
 * Usage: php fix_missing_exam_registrations.php <exam_year> [school_id]
 * Example: php fix_missing_exam_registrations.php 2026 29
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Services\ExamYear\ExamYearValidationService;

$examYearValue = $argv[1] ?? '2026';
$schoolId = $argv[2] ?? null;

echo "\n=== Fixing Missing Exam Registrations ===\n";
echo "Exam Year: $examYearValue\n";
if ($schoolId) echo "School ID: $schoolId\n";
echo "\n";

// Find exam year
$examYear = ExamYear::where('year_label', (string)$examYearValue)->first();
if (!$examYear) {
    echo "❌ Exam year $examYearValue not found\n";
    exit(1);
}
echo "✓ Found exam year: {$examYear->year_label} (ID: {$examYear->id})\n";

// Find exam type
$acseeType = ExamType::where('code', 'ACSEE')->first();
if (!$acseeType) {
    echo "❌ ACSEE exam type not found\n";
    exit(1);
}
echo "✓ Found exam type: ACSEE (ID: {$acseeType->id})\n";

// Find ACSEE candidates without registrations
$query = Candidate::where('exam_type', 'ACSEE')
    ->where(function($q) {
        $q->whereDoesntHave('examRegistrations')
          ->orWhereHas('examRegistrations', fn($q) => $q->where('exam_year_id', null));
    });

if ($schoolId) {
    $query->where('school_id', $schoolId);
}

$candidates = $query->get();
echo "\n✓ Found " . $candidates->count() . " ACSEE candidates without exam registrations\n";

if ($candidates->isEmpty()) {
    echo "\n✓ All candidates already have exam registrations!\n";
    exit(0);
}

$created = 0;
$failed = 0;
$skipped = 0;

foreach ($candidates as $candidate) {
    // Skip if already has registration for this year
    $existingReg = CandidateExamRegistration::where('candidate_id', $candidate->id)
        ->where('exam_year_id', $examYear->id)
        ->where('exam_type_id', $acseeType->id)
        ->exists();
    
    if ($existingReg) {
        echo "  ⊘ {$candidate->candidate_id}: Already registered for {$examYear->year_label}\n";
        $skipped++;
        continue;
    }
    
    try {
        // Create exam registration
        $registration = CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $acseeType->id,
            'exam_year_id' => $examYear->id,
            'year' => (int)$examYear->year_label,
            'registration_number' => 'REG-' . uniqid(),
            'status' => 'APPROVED',
        ]);
        
        // Parse combination and register subjects
        if ($candidate->combination) {
            $combCodes = explode('+', strtoupper($candidate->combination));
            
            foreach ($combCodes as $code) {
                $code = trim($code);
                $subject = \App\Models\Subject::where('code', $code)
                    ->where('exam_type_id', $acseeType->id)
                    ->first();
                
                if ($subject) {
                    CandidateSubjectSelection::firstOrCreate(
                        [
                            'candidate_id' => $candidate->id,
                            'exam_type_id' => $acseeType->id,
                            'exam_year_id' => $examYear->id,
                            'subject_id' => $subject->id,
                        ],
                        [
                            'year' => (int)$examYear->year_label,
                        ]
                    );
                }
            }
        }
        
        echo "  ✓ {$candidate->candidate_id}: Created exam registration\n";
        $created++;
    } catch (\Exception $e) {
        echo "  ✗ {$candidate->candidate_id}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n=== Summary ===\n";
echo "✓ Created: $created\n";
echo "⊘ Skipped: $skipped\n";
echo "✗ Failed: $failed\n";
echo "\nDone!\n\n";
