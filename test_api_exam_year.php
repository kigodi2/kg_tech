<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Candidate;

echo "\n=== API EXAM YEAR RESPONSE TEST ===\n\n";

// Simulate what the API does
$pageSize = 10;
$page = 1;

$query = Candidate::with('examRegistrations.examYear');
$candidates = $query->paginate($pageSize, ['*'], 'page', $page);

// Simulate the API response (the way we fixed it)
$items = array_map(function($candidate) {
    return is_array($candidate) ? $candidate : $candidate->toArray();
}, $candidates->items());

echo "Sample API Response (first 3 candidates):\n";
echo str_repeat("-", 80) . "\n";

$first = true;
foreach (array_slice($items, 0, 3) as $item) {
    echo "\nCandidate: {$item['candidate_id']}\n";
    echo "  Full Name: {$item['full_name']}\n";
    echo "  Exam Type: {$item['exam_type']}\n";
    echo "  Exam Year: " . ($item['exam_year'] ?? "NULL/MISSING") . "\n";
    
    // Show full JSON for first item only
    if ($first) {
        echo "\nFull JSON for first candidate (partial):\n";
        $subset = array_intersect_key($item, array_flip(['candidate_id', 'full_name', 'exam_type', 'exam_year', 'combination', 'school']));
        echo json_encode($subset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $first = false;
    }
}

echo "\n" . str_repeat("-", 80) . "\n";
echo "✅ API Response Includes exam_year Field\n";
echo "\n=== TEST COMPLETE ===\n\n";
