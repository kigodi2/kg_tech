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
$psleType = ExamType::where('code', 'PSLE')->first();
$tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
$tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
$schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

// Fetch orphans
$orphanMarks = DB::table('raw_marks as rm')
    ->leftJoin('candidate_exam_registrations as cer', function ($join) use ($psleType, $activeYear) {
        $join->on('cer.candidate_id', '=', 'rm.candidate_id')
            ->where('cer.exam_type_id', '=', $psleType->id)
            ->where('cer.year', '=', $activeYear->year_label);
    })
    ->whereIn('rm.school_id', $schoolIds)
    ->where('rm.exam_year_id', $activeYear->id)
    ->whereNull('cer.candidate_id')
    ->select('rm.*')
    ->get();

echo "Analyzing " . $orphanMarks->count() . " orphan marks...\n";

$actions = [
    'link_by_index' => [],
    'delete_duplicate' => [],
    'fix_typo_link' => [],
    'unresolved' => [],
];

foreach ($orphanMarks as $rm) {
    $idx = $rm->candidate_index_number;
    
    // Case 1: candidate_id is null, but index number matches a candidate exactly
    if (is_null($rm->candidate_id)) {
        $candidate = DB::table('candidates')->where('candidate_id', $idx)->first();
        if ($candidate) {
            // Check if there is already a mark record for this candidate and subject
            $exists = DB::table('raw_marks')
                ->where('candidate_id', $candidate->id)
                ->where('subject_id', $rm->subject_id)
                ->where('exam_year_id', $activeYear->id)
                ->where('id', '!=', $rm->id)
                ->first();
                
            if ($exists) {
                $actions['delete_duplicate'][] = [
                    'raw_mark' => $rm,
                    'reason' => "Duplicate of raw_mark ID {$exists->id} which already has candidate_id = {$candidate->id}",
                ];
            } else {
                $actions['link_by_index'][] = [
                    'raw_mark' => $rm,
                    'candidate' => $candidate,
                    'reason' => "Link to candidate ID {$candidate->id} by exact index match",
                ];
            }
            continue;
        }
    }
    
    // Case 2: Index has typo, e.g. starting with PS94 instead of PS04
    if (strpos($idx, 'PS94') === 0) {
        $correctedIdx = 'PS04' . substr($idx, 4);
        $candidate = DB::table('candidates')->where('candidate_id', $correctedIdx)->first();
        if ($candidate) {
            $actions['fix_typo_link'][] = [
                'raw_mark' => $rm,
                'corrected_index' => $correctedIdx,
                'candidate' => $candidate,
                'reason' => "Correct PS94 to PS04 and link to candidate ID {$candidate->id}",
            ];
            continue;
        }
    }

    // Case 3: Index has school code digit mismatch, e.g. PS040409 instead of PS0404009
    // Let's see if we can find candidate in same school (rm.school_id) with matching suffix
    if (!is_null($idx)) {
        // e.g. suffix is the last part: 0002
        $parts = explode('-', $idx);
        $suffix = end($parts);
        if ($suffix && strlen($suffix) === 4 && is_numeric($suffix)) {
            // Find candidate in school $rm->school_id with suffix
            $candidatesInSchool = DB::table('candidates')
                ->where('school_id', $rm->school_id)
                ->where('candidate_id', 'like', '%' . $suffix)
                ->get();
            if ($candidatesInSchool->count() === 1) {
                $candidate = $candidatesInSchool->first();
                $actions['fix_typo_link'][] = [
                    'raw_mark' => $rm,
                    'corrected_index' => $candidate->candidate_id,
                    'candidate' => $candidate,
                    'reason' => "Correct index mismatch to {$candidate->candidate_id} and link to candidate ID {$candidate->id}",
                ];
                continue;
            }
        }
    }

    $actions['unresolved'][] = [
        'raw_mark' => $rm,
        'reason' => "No matching candidate found in DB",
    ];
}

echo "\n--- Summary of proposed actions ---\n";
echo "1. Link by exact index match: " . count($actions['link_by_index']) . "\n";
echo "2. Delete duplicate mark: " . count($actions['delete_duplicate']) . "\n";
echo "3. Fix typo & link: " . count($actions['fix_typo_link']) . "\n";
echo "4. Unresolved: " . count($actions['unresolved']) . "\n";

if (count($actions['unresolved']) > 0) {
    echo "\nUnresolved details:\n";
    foreach (array_slice($actions['unresolved'], 0, 10) as $u) {
        echo "  - Raw Mark ID: {$u['raw_mark']->id}, Index: {$u['raw_mark']->candidate_index_number}, School: {$u['raw_mark']->school_id}\n";
    }
}
