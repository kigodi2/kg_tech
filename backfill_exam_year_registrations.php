<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║   BACKFILL EXAM YEAR REGISTRATIONS FOR ACSEE CANDIDATES    ║\n";
echo "║                  February 4, 2026                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get the exam year to use
$targetYear = ExamYear::where('is_active', true)->first();
if (!$targetYear) {
    $targetYear = ExamYear::where('year_label', '2026')->first();
}

if (!$targetYear) {
    echo "❌ ERROR: No active exam year found. Please create an exam year first.\n";
    exit(1);
}

echo "Using exam year: {$targetYear->year_label} (ID={$targetYear->id})\n\n";

// Get ACSEE exam type
$acseeType = ExamType::where('code', 'ACSEE')->first();
if (!$acseeType) {
    echo "❌ ERROR: ACSEE exam type not found.\n";
    exit(1);
}

// Count candidates to process
$totalAcsee = Candidate::where('exam_type', 'ACSEE')->count();
$withRegistration = CandidateExamRegistration::whereHas('candidate', function($q) {
    $q->where('exam_type', 'ACSEE');
})->where('exam_year_id', '!=', null)->count();

$needsBackfill = $totalAcsee - $withRegistration;

echo "Candidate Status:\n";
echo "  - Total ACSEE candidates: $totalAcsee\n";
echo "  - With exam_year_id: $withRegistration\n";
echo "  - Need backfill: $needsBackfill\n\n";

if ($needsBackfill === 0) {
    echo "✅ All candidates already have exam_year_id. No backfill needed.\n\n";
    exit(0);
}

echo "Starting backfill process...\n\n";

// Get all ACSEE candidates without exam year registration
$candidates = Candidate::where('exam_type', 'ACSEE')
    ->whereDoesntHave('examRegistrations', function($q) use ($targetYear) {
        $q->where('exam_year_id', $targetYear->id);
    })
    ->get();

$processed = 0;
$created = 0;
$skipped = 0;
$errors = [];

foreach ($candidates as $candidate) {
    $processed++;
    
    try {
        // Check if already registered (shouldn't happen but safety check)
        $existing = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $acseeType->id)
            ->where('exam_year_id', $targetYear->id)
            ->first();
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Create the registration
        $registration = CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $acseeType->id,
            'exam_year_id' => $targetYear->id,
            'year' => (int)$targetYear->year_label,
            'registration_number' => 'REG-' . uniqid(),
            'status' => 'APPROVED',
            'is_active' => true,
            'is_verified' => false,
        ]);
        
        // Register subjects if combination is provided
        if ($candidate->combination) {
            try {
                $subjects = $this->parseAndFindSubjects($candidate->combination, $acseeType->id);
                
                foreach ($subjects as $subject) {
                    $existingSelection = CandidateSubjectSelection::where('candidate_id', $candidate->id)
                        ->where('subject_id', $subject->id)
                        ->where('exam_type_id', $acseeType->id)
                        ->where('exam_year_id', $targetYear->id)
                        ->first();
                    
                    if (!$existingSelection) {
                        CandidateSubjectSelection::create([
                            'candidate_id' => $candidate->id,
                            'exam_type_id' => $acseeType->id,
                            'exam_year_id' => $targetYear->id,
                            'subject_id' => $subject->id,
                            'year' => (int)$targetYear->year_label,
                            'is_active' => true,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Log but don't fail on subject registration
                \Log::warning("Subject registration failed for candidate {$candidate->candidate_id}: {$e->getMessage()}");
            }
        }
        
        $created++;
        
        if ($processed % 500 === 0) {
            echo "  Processed: $processed/$needsBackfill (Created: $created, Skipped: $skipped)\n";
        }
        
    } catch (\Exception $e) {
        $skipped++;
        $errors[] = [
            'candidate_id' => $candidate->candidate_id,
            'error' => $e->getMessage()
        ];
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "BACKFILL COMPLETE\n";
echo str_repeat("=", 60) . "\n";
echo "  Processed: $processed candidates\n";
echo "  Created: $created registrations\n";
echo "  Skipped: $skipped already registered\n";
echo "  Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach (array_slice($errors, 0, 10) as $error) {
        echo "  - {$error['candidate_id']}: {$error['error']}\n";
    }
    if (count($errors) > 10) {
        echo "  ... and " . (count($errors) - 10) . " more\n";
    }
}

echo "\n✅ Backfill process complete!\n";
echo "   Candidates now have exam_year_id and can be seen in Mark Entry\n\n";

// Verify
$afterCount = CandidateExamRegistration::whereHas('candidate', function($q) {
    $q->where('exam_type', 'ACSEE');
})->where('exam_year_id', $targetYear->id)->count();

echo "Verification:\n";
echo "  ACSEE candidates with exam_year_id: $afterCount\n";
echo "  Total ACSEE candidates: $totalAcsee\n";

if ($afterCount === $totalAcsee) {
    echo "\n✅ SUCCESS: All ACSEE candidates now have exam year registration!\n";
} else {
    echo "\n⚠️  WARNING: Some candidates still missing exam year registration\n";
    echo "   Difference: " . ($totalAcsee - $afterCount) . "\n";
}

echo "\n";
