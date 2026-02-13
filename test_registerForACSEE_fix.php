<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;
use App\Models\ExamYear;

echo "\n=== REGISTER FOR ACSEE - FIX VERIFICATION ===\n\n";

// Get a test candidate
$candidate = Candidate::where('exam_type', 'ACSEE')
    ->whereDoesntHave('examRegistrations')
    ->first();

if (!$candidate) {
    echo "No unregistered ACSEE candidates found. Creating test scenario...\n";
    $candidate = Candidate::where('exam_type', 'ACSEE')->first();
    if ($candidate) {
        // Delete existing registration for this test
        $candidate->examRegistrations()->delete();
        echo "Cleaned up registrations for testing.\n\n";
    } else {
        echo "No ACSEE candidates found at all!\n";
        exit(1);
    }
}

echo "Test Candidate: {$candidate->candidate_id} - {$candidate->full_name}\n";
echo "Exam Type: {$candidate->exam_type}\n";
echo "Combination: {$candidate->combination}\n\n";

// Test the fixed registerForACSEE logic
echo "Testing registerForACSEE with string year '2026'...\n\n";

try {
    // Get the controller
    $controller = app(\App\Http\Controllers\CandidateController::class);
    
    // Use reflection to call the private method
    $reflection = new ReflectionMethod($controller, 'registerForACSEE');
    $reflection->setAccessible(true);
    
    // Call with string year
    $reflection->invoke($controller, $candidate, $candidate->combination, '2026');
    
    echo "✅ registerForACSEE succeeded!\n\n";
    
    // Verify the registration was created
    $registration = $candidate->examRegistrations()->first();
    if ($registration) {
        echo "Registration created:\n";
        echo "  - ID: {$registration->id}\n";
        echo "  - Exam Type ID: {$registration->exam_type_id}\n";
        echo "  - Exam Year ID: {$registration->exam_year_id}\n";
        echo "  - Year Field: {$registration->year}\n";
        
        if ($registration->examYear) {
            echo "  - Related ExamYear: {$registration->examYear->year_label}\n";
            echo "\n✅ EXAM YEAR_ID IS PROPERLY SET!\n";
        } else {
            echo "  - Related ExamYear: NOT FOUND (exam_year_id={$registration->exam_year_id})\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "\nStacktrace:\n{$e->getTraceAsString()}\n";
}

echo "\n=== TEST COMPLETE ===\n\n";
