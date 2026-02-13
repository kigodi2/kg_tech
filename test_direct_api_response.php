<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Models\Candidate;

echo "\n=== DIRECT API RESPONSE TEST ===\n\n";

// Simulate the exact API call
$request = new Request([
    'page_size' => 10,
    'page' => 1,
    'search' => '',
    'school_id' => '',
    'district_id' => '',
    'region_id' => ''
]);

// This is the exact code from the API route
$pageSize = $request->input('page_size', 10);
$page = $request->input('page', 1);
$search = $request->input('search', '');
$schoolId = $request->input('school_id', '');
$districtId = $request->input('district_id', '');
$regionId = $request->input('region_id', '');

$query = Candidate::with('school', 'school.district', 'school.district.region', 'examRegistrations.examYear');

if ($search) {
    $query->where(function($q) use ($search) {
        $q->where('full_name', 'like', "%{$search}%");
    });
}

if ($schoolId) {
    $query->where('school_id', $schoolId);
}

if ($districtId) {
    $query->whereHas('school', function($q) use ($districtId) {
        $q->where('district_id', $districtId)
          ->whereNotNull('district_id');
    });
}

if ($regionId) {
    $query->whereHas('school.district', function($q) use ($regionId) {
        $q->where('region_id', $regionId)
          ->whereNotNull('region_id');
    });
}

$total = $query->count();
$totalPages = ceil($total / $pageSize);

$candidates = $query->paginate($pageSize, ['*'], 'page', $page);

// The exact code from the API
$items = array_map(function($candidate) {
    return is_array($candidate) ? $candidate : $candidate->toArray();
}, $candidates->items());

// The response
$response = [
    'data' => $items,
    'pagination' => [
        'total_count' => $total,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $pageSize
    ]
];

echo "Response structure (first item):\n";
if (!empty($response['data'])) {
    $first = $response['data'][0];
    echo "\nCandidate keys: " . implode(', ', array_keys($first)) . "\n\n";
    
    echo "Relevant fields:\n";
    echo "  - candidate_id: " . ($first['candidate_id'] ?? 'MISSING') . "\n";
    echo "  - full_name: " . ($first['full_name'] ?? 'MISSING') . "\n";
    echo "  - exam_type: " . ($first['exam_type'] ?? 'MISSING') . "\n";
    echo "  - exam_year: " . ($first['exam_year'] ?? 'MISSING') . "\n";
    
    echo "\nFull first item JSON:\n";
    echo json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

echo "\nTotal items in response: " . count($response['data']) . "\n";

echo "\n=== TEST COMPLETE ===\n\n";
