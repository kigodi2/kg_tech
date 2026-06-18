<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Region;
use App\Models\School;

$activeYear = ExamYear::where('is_active', true)->first();
if (!$activeYear) {
    echo "No active exam year found.\n";
    exit(1);
}

$psleType = ExamType::where('code', 'PSLE')->first();
if (!$psleType) {
    echo "PSLE Exam Type not found.\n";
    exit(1);
}

$tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
$tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
$schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

echo "Active Year: {$activeYear->year_label} (ID: {$activeYear->id})\n";

$controller = app(\App\Http\Controllers\Admin\AdminPsleResultsController::class);
// Use reflection to call private method runValidationChecks
$reflector = new \ReflectionClass(get_class($controller));
$method = $reflector->getMethod('runValidationChecks');
$method->setAccessible(true);

$result = $method->invoke($controller, $activeYear, $tasidoRegionIds, $schoolIds, $psleType->id);

echo "Total Errors Found: " . $result['errors_count'] . "\n";
echo "Critical Errors Found: " . $result['critical_count'] . "\n";

$errorsByType = [];
foreach ($result['errors'] as $err) {
    $errorsByType[$err['error_type']][] = $err;
}

foreach ($errorsByType as $type => $list) {
    echo "\nError Type: {$type} (Count: " . count($list) . " shown in sample)\n";
    // Show first 10
    $sample = array_slice($list, 0, 10);
    foreach ($sample as $item) {
        echo "  - School ID: {$item['school_id']}, Candidate: {$item['candidate_no']}, Msg: {$item['error_message']}\n";
    }
}
