<?php

namespace App\Console\Commands;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\ResultProcess;
use App\Models\ResultSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VerifyAcseeSchoolwiseAggregation extends Command
{
    protected $signature = 'results:verify-acsee-schoolwise
        {region : Region ID or region name}
        {--exam-year=2026 : Exam year label}
        {--school-id= : Restrict output to one school ID}
        {--limit=10 : Maximum schools to display in the summary}';

    protected $description = 'Verify ACSEE regional schoolwise aggregation against source registrations and final grades';

    public function handle(): int
    {
        $examYearValue = (string) $this->option('exam-year');
        $regionInput = (string) $this->argument('region');
        $schoolId = $this->option('school-id');
        $limit = max((int) $this->option('limit'), 1);

        $examType = ExamType::query()->where('code', 'ACSEE')->first();
        if (!$examType) {
            $this->error('ACSEE exam type not found.');
            return self::FAILURE;
        }

        $activeYear = ExamYear::query()
            ->where('year_label', $examYearValue)
            ->orWhere('year', $examYearValue)
            ->first();

        $region = $this->resolveRegion($regionInput);
        if (!$region) {
            $this->error("Region not found: {$regionInput}");
            return self::FAILURE;
        }

        $context = $this->loadContext($region, $examType->id, $examYearValue, $activeYear?->id);
        $schoolRows = $this->aggregateSchoolRows($context['enrichedRows'], $context['finalByCandidate']);

        if ($schoolId) {
            $schoolRows = $schoolRows->where('school_id', (int) $schoolId)->values();
            if ($schoolRows->isEmpty()) {
                $this->error("No schoolwise row found for school_id={$schoolId} in region {$region->name}.");
                return self::FAILURE;
            }
        }

        $reportRows = $schoolRows
            ->map(fn (array $row) => $this->buildVerificationRow($row))
            ->sortByDesc(fn (array $row) => [$row['has_issue'] ? 1 : 0, $row['unknown_gender'], $row['school']])
            ->values();

        $issues = $reportRows->where('has_issue', true)->count();
        $unknownGender = $reportRows->sum('unknown_gender');

        $this->info('ACSEE schoolwise aggregation verification');
        $this->line('Region: ' . strtoupper((string) $region->name));
        $this->line('Exam year: ' . $examYearValue);
        $this->line('Schools checked: ' . $reportRows->count());
        $this->line('Schools with invariant issues: ' . $issues);
        $this->line('Candidates with unknown/invalid gender: ' . $unknownGender);
        $this->newLine();

        $displayRows = $reportRows->take($schoolId ? 1 : $limit)->map(function (array $row) {
            return [
                'School ID' => $row['school_id'],
                'Council' => $row['council'],
                'School' => $row['school'],
                'Reg' => $row['registered_t'],
                'Abs' => $row['absent_t'],
                'Sat' => $row['sat_t'],
                'Inc' => $row['inc_t'],
                'Div Total' => $row['division_total'],
                'Unknown Gender' => $row['unknown_gender'],
                'Status' => $row['has_issue'] ? 'CHECK' : 'OK',
            ];
        })->all();

        $this->table(
            ['School ID', 'Council', 'School', 'Reg', 'Abs', 'Sat', 'Inc', 'Div Total', 'Unknown Gender', 'Status'],
            $displayRows
        );

        $issueRows = $reportRows->where('has_issue', true)->values();
        if ($issueRows->isNotEmpty()) {
            $this->warn('Invariant issues found:');
            foreach ($issueRows->take($schoolId ? 1 : $limit) as $row) {
                $this->line("- {$row['school']} ({$row['school_id']}): " . implode('; ', $row['issues']));
            }
            $this->newLine();
        }

        if ($unknownGender > 0) {
            $this->warn('Professional advice: unknown gender values are now excluded from M/F counts and kept only in totals.');
            $this->line('Review the source candidate gender data before using M/F breakdowns operationally.');
            $this->newLine();
        }

        $this->line('Verification rules applied:');
        $this->line('- REGISTERED = ABSENT + SAT');
        $this->line('- SAT >= INC');
        $this->line('- DIVISION total = SAT - INC');
        $this->line('- I-III = I + II + III');
        $this->line('- I-IV = I-III + IV');

        return ($issues > 0 || $unknownGender > 0) ? self::FAILURE : self::SUCCESS;
    }

    private function resolveRegion(string $regionInput): ?Region
    {
        if (ctype_digit($regionInput)) {
            return Region::query()->find((int) $regionInput);
        }

        return Region::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($regionInput)])
            ->first();
    }

    private function loadContext(Region $region, int $examTypeId, string $examYearValue, ?int $activeYearId): array
    {
        $applyYearFilter = function ($query) use ($activeYearId, $examYearValue) {
            $query->where(function ($q) use ($activeYearId, $examYearValue) {
                $q->where('cer.year', $examYearValue);
                if ($activeYearId) {
                    $q->orWhere('cer.exam_year_id', $activeYearId);
                }
            });
        };

        $baseRegistrations = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->leftJoin('districts as d', 'd.id', '=', 's.district_id')
            ->where('s.region_id', $region->id)
            ->where('cer.exam_type_id', $examTypeId);
        $applyYearFilter($baseRegistrations);

        $registrationRows = (clone $baseRegistrations)
            ->selectRaw('cer.candidate_id as candidate_id')
            ->selectRaw('c.gender as gender')
            ->selectRaw('s.id as school_id, s.name as school_name')
            ->selectRaw('COALESCE(dc.name, d.name, "-") as council_name')
            ->get();

        $regionCandidateIds = $registrationRows->pluck('candidate_id')->unique()->values();
        $hasStoredResultStatus = Schema::hasColumn('candidate_results', 'result_status');
        $activeSnapshot = ResultSnapshot::query()
            ->where('exam_year_id', $activeYearId)
            ->where('is_active', true)
            ->first();
        $latestProcessId = ResultProcess::query()
            ->where('exam_type_id', $examTypeId)
            ->where('exam_year_id', $activeYearId)
            ->where('status', 'completed')
            ->latest('id')
            ->value('id');

        $useSnapshotForFinalGrades = false;
        if ($activeSnapshot && Schema::hasColumn('final_grades', 'snapshot_id')) {
            $useSnapshotForFinalGrades = DB::table('final_grades')
                ->where('exam_type_id', $examTypeId)
                ->where('year', $examYearValue)
                ->where('snapshot_id', $activeSnapshot->id)
                ->exists();
        }

        $resultsBase = DB::table('final_grades as fg')
            ->join('candidates as c', 'c.id', '=', 'fg.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->where('fg.exam_type_id', $examTypeId)
            ->where('fg.year', $examYearValue)
            ->where('s.region_id', $region->id);

        if ($useSnapshotForFinalGrades) {
            $resultsBase->where('fg.snapshot_id', $activeSnapshot->id);
        } elseif ($latestProcessId) {
            $resultsBase->where(function ($q) use ($latestProcessId) {
                $q->where('fg.process_id', $latestProcessId)
                    ->whereNull('fg.snapshot_id');
            });
        }

        $resultStatusByCandidate = collect();
        if ($hasStoredResultStatus) {
            $statusBase = DB::table('candidate_results as cr')
                ->where('cr.exam_type_id', $examTypeId)
                ->where('cr.year', $examYearValue)
                ->whereIn('cr.candidate_id', $regionCandidateIds);

            if ($useSnapshotForFinalGrades && $activeSnapshot && Schema::hasColumn('candidate_results', 'snapshot_id')) {
                $statusBase->where('cr.snapshot_id', $activeSnapshot->id);
            } elseif ($latestProcessId) {
                $statusBase->where(function ($q) use ($latestProcessId) {
                    $q->where('cr.process_id', $latestProcessId)
                        ->whereNull('cr.snapshot_id');
                });
            }

            $resultStatusByCandidate = $statusBase
                ->get(['cr.candidate_id', 'cr.result_status'])
                ->keyBy('candidate_id');

            $missingStatusCandidateIds = $regionCandidateIds
                ->diff($resultStatusByCandidate->keys())
                ->values();

            if ($missingStatusCandidateIds->isNotEmpty()) {
                $fallbackStatusRows = DB::table('candidate_results as cr')
                    ->where('cr.exam_type_id', $examTypeId)
                    ->where('cr.year', $examYearValue)
                    ->whereIn('cr.candidate_id', $missingStatusCandidateIds)
                    ->orderByDesc('cr.id')
                    ->get(['cr.id', 'cr.candidate_id', 'cr.result_status'])
                    ->unique('candidate_id')
                    ->keyBy('candidate_id');

                $resultStatusByCandidate = $resultStatusByCandidate->union($fallbackStatusRows);
            }
        }

        $scopedFinalRows = $resultsBase
            ->selectRaw('fg.candidate_id as candidate_id')
            ->selectRaw('fg.gpa as resolved_gpa_source')
            ->selectRaw('fg.division as resolved_division_source')
            ->selectRaw('fg.grading_breakdown as resolved_breakdown_source')
            ->get();

        $finalByCandidate = $scopedFinalRows->keyBy('candidate_id');
        $missingFinalCandidateIds = $regionCandidateIds
            ->diff($finalByCandidate->keys())
            ->values();

        if ($missingFinalCandidateIds->isNotEmpty()) {
            $fallbackFinalRows = DB::table('final_grades as fg')
                ->where('fg.exam_type_id', $examTypeId)
                ->where('fg.year', $examYearValue)
                ->whereIn('fg.candidate_id', $missingFinalCandidateIds)
                ->orderByDesc('fg.id')
                ->get([
                    'fg.id',
                    'fg.candidate_id',
                    'fg.gpa as resolved_gpa_source',
                    'fg.division as resolved_division_source',
                    'fg.grading_breakdown as resolved_breakdown_source',
                ])
                ->unique('candidate_id')
                ->keyBy('candidate_id');

            $finalByCandidate = $finalByCandidate->union($fallbackFinalRows);
        }

        $resolveResultStatus = function ($row) use ($finalByCandidate, $resultStatusByCandidate) {
            $stored = strtoupper(trim((string) (($resultStatusByCandidate->get($row->candidate_id)->result_status ?? null) ?? '')));
            if (in_array($stored, ['COMPLETE', 'INC', 'ABS'], true)) {
                return $stored;
            }

            $final = $finalByCandidate->get($row->candidate_id);
            $decoded = is_array($final?->resolved_breakdown_source ?? null)
                ? $final->resolved_breakdown_source
                : json_decode((string) ($final?->resolved_breakdown_source ?? ''), true);
            $irregular = strtoupper(trim((string) data_get($decoded, 'irregular_overall_status', '')));

            if (in_array($irregular, ['ABS', 'X'], true)) {
                return 'ABS';
            }
            if ($irregular !== '') {
                return 'INC';
            }

            $aggtPoints = data_get($decoded, 'aggt_points');
            $principalPasses = (int) data_get($decoded, 'principal_passes', 0);
            $gpaSubjectsCount = (int) data_get($decoded, 'gpa_subjects_count', 0);

            if ($aggtPoints === null && $principalPasses === 0 && $gpaSubjectsCount === 0) {
                return 'INC';
            }

            return 'COMPLETE';
        };

        $enrichedRows = $registrationRows->map(function ($row) use ($finalByCandidate, $resolveResultStatus) {
            $final = $finalByCandidate->get($row->candidate_id);
            $row->resolved_result_status = $resolveResultStatus($row);
            $row->resolved_division = (int) ($final?->resolved_division_source ?? 0);
            $row->resolved_gpa = !is_null($final?->resolved_gpa_source) && $final->resolved_gpa_source !== ''
                ? (float) $final->resolved_gpa_source
                : null;
            return $row;
        })->values();

        return [
            'enrichedRows' => $enrichedRows,
            'finalByCandidate' => $finalByCandidate,
        ];
    }

    private function aggregateSchoolRows(Collection $enrichedRows, Collection $finalByCandidate): Collection
    {
        $schoolBuckets = [];

        foreach ($enrichedRows as $row) {
            if (empty($row->school_id)) {
                continue;
            }

            $schoolKey = (string) ((int) $row->school_id);
            if (!isset($schoolBuckets[$schoolKey])) {
                $schoolBuckets[$schoolKey] = [
                    'school_id' => (int) $row->school_id,
                    'council' => strtoupper((string) ($row->council_name ?? '-')),
                    'school' => strtoupper((string) ($row->school_name ?? '-')),
                    'registered' => ['m' => 0, 'f' => 0, 't' => 0],
                    'absent' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                    'sat' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                    'inc' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                    'division' => [
                        'i' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'ii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'i_iii' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'i_iv' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                        'zero' => ['m' => 0, 'f' => 0, 't' => 0, 'pct' => 0.0],
                    ],
                    'unknown_gender' => [
                        'registered' => 0,
                        'absent' => 0,
                        'sat' => 0,
                        'inc' => 0,
                        'division' => 0,
                    ],
                    'gpa_sum' => 0.0,
                    'gpa_count' => 0,
                    'gpa' => null,
                ];
            }

            $genderValue = strtoupper(trim((string) $row->gender));
            $gender = match ($genderValue) {
                'F' => 'f',
                'M' => 'm',
                default => null,
            };

            $bucket = &$schoolBuckets[$schoolKey];
            $bucket['registered']['t']++;
            if ($gender !== null) {
                $bucket['registered'][$gender]++;
            } else {
                $bucket['unknown_gender']['registered']++;
            }

            $resultStatus = strtoupper(trim((string) ($row->resolved_result_status ?? 'COMPLETE')));
            if ($resultStatus === 'ABS') {
                $bucket['absent']['t']++;
                if ($gender !== null) {
                    $bucket['absent'][$gender]++;
                } else {
                    $bucket['unknown_gender']['absent']++;
                }
                unset($bucket);
                continue;
            }

            $bucket['sat']['t']++;
            if ($gender !== null) {
                $bucket['sat'][$gender]++;
            } else {
                $bucket['unknown_gender']['sat']++;
            }

            if ($resultStatus === 'INC') {
                $bucket['inc']['t']++;
                if ($gender !== null) {
                    $bucket['inc'][$gender]++;
                } else {
                    $bucket['unknown_gender']['inc']++;
                }
                unset($bucket);
                continue;
            }

            $divisionValue = (int) ($finalByCandidate->get($row->candidate_id)?->resolved_division_source ?? 0);
            $group = match ($divisionValue) {
                1 => 'i',
                2 => 'ii',
                3 => 'iii',
                4 => 'iv',
                default => 'zero',
            };

            $bucket['division'][$group]['t']++;
            if ($gender !== null) {
                $bucket['division'][$group][$gender]++;
            } else {
                $bucket['unknown_gender']['division']++;
            }

            $gpaValue = $finalByCandidate->get($row->candidate_id)?->resolved_gpa_source;
            if (!is_null($gpaValue) && $gpaValue !== '') {
                $bucket['gpa_sum'] += (float) $gpaValue;
                $bucket['gpa_count']++;
            }

            unset($bucket);
        }

        return collect($schoolBuckets)->map(function (array $bucket) {
            $regT = max((int) $bucket['registered']['t'], 0);
            $absT = (int) $bucket['absent']['t'];
            $incT = (int) $bucket['inc']['t'];

            $bucket['sat']['m'] = max((int) $bucket['registered']['m'] - (int) $bucket['absent']['m'], 0);
            $bucket['sat']['f'] = max((int) $bucket['registered']['f'] - (int) $bucket['absent']['f'], 0);
            $bucket['sat']['t'] = max($regT - $absT, 0);
            $satT = (int) $bucket['sat']['t'];

            $bucket['absent']['pct'] = $regT > 0 ? ($absT / $regT) * 100 : 0.0;
            $bucket['sat']['pct'] = $regT > 0 ? ($satT / $regT) * 100 : 0.0;
            $bucket['inc']['pct'] = $regT > 0 ? ($incT / $regT) * 100 : 0.0;

            $bucket['division']['i_iii']['m'] = $bucket['division']['i']['m'] + $bucket['division']['ii']['m'] + $bucket['division']['iii']['m'];
            $bucket['division']['i_iii']['f'] = $bucket['division']['i']['f'] + $bucket['division']['ii']['f'] + $bucket['division']['iii']['f'];
            $bucket['division']['i_iii']['t'] = $bucket['division']['i_iii']['m'] + $bucket['division']['i_iii']['f'];

            $bucket['division']['i_iv']['m'] = $bucket['division']['i_iii']['m'] + $bucket['division']['iv']['m'];
            $bucket['division']['i_iv']['f'] = $bucket['division']['i_iii']['f'] + $bucket['division']['iv']['f'];
            $bucket['division']['i_iv']['t'] = $bucket['division']['i_iv']['m'] + $bucket['division']['i_iv']['f'];

            foreach (['i', 'ii', 'iii', 'i_iii', 'iv', 'i_iv', 'zero'] as $group) {
                $bucket['division'][$group]['pct'] = $satT > 0
                    ? ((int) $bucket['division'][$group]['t'] / $satT) * 100
                    : 0.0;
            }

            $bucket['gpa'] = $bucket['gpa_count'] > 0
                ? round($bucket['gpa_sum'] / $bucket['gpa_count'], 4)
                : null;

            unset($bucket['gpa_sum'], $bucket['gpa_count']);
            return $bucket;
        })->values();
    }

    private function buildVerificationRow(array $row): array
    {
        $divisionTotal = (int) $row['division']['i']['t']
            + (int) $row['division']['ii']['t']
            + (int) $row['division']['iii']['t']
            + (int) $row['division']['iv']['t']
            + (int) $row['division']['zero']['t'];

        $issues = [];

        if ((int) $row['registered']['t'] !== ((int) $row['absent']['t'] + (int) $row['sat']['t'])) {
            $issues[] = 'registered != absent + sat';
        }

        if ((int) $row['sat']['t'] < (int) $row['inc']['t']) {
            $issues[] = 'sat < inc';
        }

        if ($divisionTotal !== ((int) $row['sat']['t'] - (int) $row['inc']['t'])) {
            $issues[] = 'division total != sat - inc';
        }

        if ((int) $row['division']['i_iii']['t'] !== ((int) $row['division']['i']['t'] + (int) $row['division']['ii']['t'] + (int) $row['division']['iii']['t'])) {
            $issues[] = 'i-iii mismatch';
        }

        if ((int) $row['division']['i_iv']['t'] !== ((int) $row['division']['i_iii']['t'] + (int) $row['division']['iv']['t'])) {
            $issues[] = 'i-iv mismatch';
        }

        return [
            'school_id' => $row['school_id'],
            'council' => $row['council'],
            'school' => $row['school'],
            'registered_t' => (int) $row['registered']['t'],
            'absent_t' => (int) $row['absent']['t'],
            'sat_t' => (int) $row['sat']['t'],
            'inc_t' => (int) $row['inc']['t'],
            'division_total' => $divisionTotal,
            'unknown_gender' => array_sum($row['unknown_gender']),
            'issues' => $issues,
            'has_issue' => !empty($issues),
        ];
    }
}
