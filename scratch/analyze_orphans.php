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

$unresolvedGrouped = [];
foreach ($orphanMarks as $rm) {
    $idx = $rm->candidate_index_number;
    
    // Skip those that can be resolved easily by exact candidate_id set or typo fixes
    if (is_null($rm->candidate_id)) {
        $candidate = DB::table('candidates')->where('candidate_id', $idx)->first();
        if ($candidate) {
            continue;
        }
    }
    if (strpos($idx, 'PS94') === 0) {
        $correctedIdx = 'PS04' . substr($idx, 4);
        $candidate = DB::table('candidates')->where('candidate_id', $correctedIdx)->first();
        if ($candidate) {
            continue;
        }
    }
    if (!is_null($idx)) {
        $parts = explode('-', $idx);
        $suffix = end($parts);
        if ($suffix && strlen($suffix) === 4 && is_numeric($suffix)) {
            $candidatesInSchool = DB::table('candidates')
                ->where('school_id', $rm->school_id)
                ->where('candidate_id', 'like', '%' . $suffix)
                ->get();
            if ($candidatesInSchool->count() === 1) {
                continue;
            }
        }
    }
    
    $unresolvedGrouped[$idx]['school_id'] = $rm->school_id;
    $unresolvedGrouped[$idx]['subjects'][] = $rm->subject_id;
}

echo "Found " . count($unresolvedGrouped) . " unique unresolved index numbers:\n\n";

foreach ($unresolvedGrouped as $idx => $info) {
    $schoolId = $info['school_id'];
    $school = DB::table('schools')->find($schoolId);
    $schoolName = $school ? $school->name : 'Unknown';
    echo "Index: '{$idx}' | School ID: {$schoolId} ({$schoolName})\n";
    
    // List candidates in this school
    $candidates = DB::table('candidates')
        ->where('school_id', $schoolId)
        ->get();
        
    echo "  Candidates registered in this school (" . $candidates->count() . " total):\n";
    foreach ($candidates as $cand) {
        // Let's see if this candidate already has raw marks
        $marksCount = DB::table('raw_marks')
            ->where('candidate_id', $cand->id)
            ->where('exam_year_id', $activeYear->id)
            ->count();
        echo "    - ID: {$cand->id}, Index: {$cand->candidate_id}, Name: {$cand->full_name} (Existing Marks: {$marksCount})\n";
    }
    echo "\n";
}
