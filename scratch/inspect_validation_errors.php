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

// Run validation logic without limits
$minScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('minimum_subject_score', 0);
$maxScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('maximum_subject_score', 50);
$absentCode = \App\Helpers\SystemSettingsHelper::getSetting('absent_code', 'ABS');
$incompleteCode = \App\Helpers\SystemSettingsHelper::getSetting('incomplete_code', 'INC');

$subjects = DB::table('subjects as s')
    ->join('exam_types as et', 'et.id', '=', 's.exam_type_id')
    ->where('et.code', 'PSLE')
    ->select('s.*')
    ->get();
$subjectIds = $subjects->pluck('id')->toArray();
$subjectMap = $subjects->keyBy('id');

$criticalErrors = [];

DB::table('candidate_exam_registrations as cer')
    ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
    ->join('schools as s', 's.id', '=', 'c.school_id')
    ->whereIn('s.region_id', $tasidoRegionIds)
    ->where('cer.exam_type_id', $psleType->id)
    ->where('cer.year', $activeYear->year_label)
    ->select(['c.id as candidate_pk', 'c.candidate_id as index_number', 'c.prem_no', 'c.full_name', 'c.school_id', 's.region_id'])
    ->orderBy('c.id')
    ->chunk(5000, function ($candidatesChunk) use ($schoolIds, $activeYear, $subjectIds, $subjectMap, $minScore, $maxScore, $absentCode, $incompleteCode, &$criticalErrors) {
        $candidateIds = $candidatesChunk->pluck('candidate_pk')->toArray();

        $rawMarks = DB::table('raw_marks')
            ->whereIn('school_id', $schoolIds)
            ->where('exam_year_id', $activeYear->id)
            ->whereIn('candidate_id', $candidateIds)
            ->get();

        $rawMarkGroups = [];
        $rawMarkRecords = [];
        foreach ($rawMarks as $rm) {
            $rawMarkGroups[$rm->candidate_id][$rm->subject_id] = ($rawMarkGroups[$rm->candidate_id][$rm->subject_id] ?? 0) + 1;
            $rawMarkRecords[$rm->candidate_id][$rm->subject_id] = $rm;
        }

        foreach ($candidatesChunk as $candidate) {
            foreach ($subjectIds as $subId) {
                $count = $rawMarkGroups[$candidate->candidate_pk][$subId] ?? 0;
                $subject = $subjectMap->get($subId);

                $err = null;

                if ($count === 0) {
                    // warning, ignore
                } elseif ($count > 1) {
                    $err = [
                        'school_id' => $candidate->school_id,
                        'candidate_no' => $candidate->index_number,
                        'error_type' => 'duplicate_marks',
                        'error_message' => "Candidate {$candidate->index_number} has duplicate mark entries for subject " . ($subject->name ?? 'Unknown') . ".",
                        'severity' => 'critical',
                    ];
                } else {
                    $record = $rawMarkRecords[$candidate->candidate_pk][$subId];
                    if (!is_null($record->paper_1_marks)) {
                        $markVal = (float) $record->paper_1_marks;
                        if ($markVal < $minScore || $markVal > $maxScore) {
                            $err = [
                                'school_id' => $candidate->school_id,
                                'candidate_no' => $candidate->index_number,
                                'error_type' => 'invalid_score_range',
                                'error_message' => "Candidate {$candidate->index_number} has out-of-range mark {$markVal} for subject " . ($subject->name ?? 'Unknown') . " (expected {$minScore}-{$maxScore}).",
                                'severity' => 'critical',
                            ];
                        }
                    } else {
                        $statusVal = strtoupper(trim((string) $record->subject_status));
                        if ($statusVal !== strtoupper($absentCode) && $statusVal !== strtoupper($incompleteCode)) {
                            $err = [
                                'school_id' => $candidate->school_id,
                                'candidate_no' => $candidate->index_number,
                                'error_type' => 'invalid_status_code',
                                'error_message' => "Candidate {$candidate->index_number} has null mark but invalid status code '{$record->subject_status}' for subject " . ($subject->name ?? 'Unknown') . " (expected {$absentCode} or {$incompleteCode}).",
                                'severity' => 'critical',
                            ];
                        }
                    }
                }

                if ($err && $err['severity'] === 'critical') {
                    $criticalErrors[] = $err;
                }
            }
        }
    });

// Check orphans
$orphanMarks = DB::table('raw_marks as rm')
    ->leftJoin('candidate_exam_registrations as cer', function ($join) use ($psleType, $activeYear) {
        $join->on('cer.candidate_id', '=', 'rm.candidate_id')
            ->where('cer.exam_type_id', '=', $psleType->id)
            ->where('cer.year', '=', $activeYear->year_label);
    })
    ->leftJoin('candidates as c', 'c.id', '=', 'rm.candidate_id')
    ->whereIn('rm.school_id', $schoolIds)
    ->where('rm.exam_year_id', $activeYear->id)
    ->whereNull('cer.candidate_id')
    ->select(['rm.school_id', 'rm.candidate_id', 'rm.subject_id', 'rm.candidate_index_number', 'c.id as reg_candidate_id'])
    ->get();

foreach ($orphanMarks as $rm) {
    $candidateNo = $rm->candidate_index_number ?? 'Unknown';
    $sub = $subjectMap->get($rm->subject_id);
    $criticalErrors[] = [
        'school_id' => $rm->school_id,
        'candidate_no' => $candidateNo,
        'error_type' => 'orphan_marks',
        'error_message' => "Raw mark record found for candidate ID {$rm->candidate_id} ({$candidateNo}) who is not registered in TASIDO region for this year.",
        'severity' => 'critical',
    ];
}

echo "Total Critical Errors: " . count($criticalErrors) . "\n";
$byType = [];
foreach ($criticalErrors as $err) {
    $byType[$err['error_type']][] = $err;
}

foreach ($byType as $type => $list) {
    echo "\nCritical Error Type: {$type} (Count: " . count($list) . ")\n";
    $sample = array_slice($list, 0, 20);
    foreach ($sample as $item) {
        echo "  - School: {$item['school_id']}, Candidate: {$item['candidate_no']}, Message: {$item['error_message']}\n";
    }
}
