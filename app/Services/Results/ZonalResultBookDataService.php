<?php

namespace App\Services\Results;

use App\Models\Region;
use App\Models\PslePrecalculatedEvaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZonalResultBookDataService
{
    public function __construct(
        protected PslePrecalculationService $precalcService
    ) {}

    public function getReportData(int $examYear, array $overrides = []): array
    {
        $snapshotId = $this->precalcService->getActiveSnapshotId($examYear);
        if (!$snapshotId) {
            throw new \Exception("Kitabu cha Matokeo ya Kanda hakiwezi kuzalishwa: Hakuna snapshot ya matokeo iliyo hai au iliyochapishwa kwa mwaka {$examYear}.");
        }

        // Centralized list of Academic Zone regions
        $regions = DB::table('regions')
            ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
            ->get(['id', 'name']);
        $regionIds = $regions->pluck('id')->toArray();
        $regionNames = $regions->pluck('name')->map(fn($n) => strtoupper($n))->toArray();

        // Fetch precalculated zonal payloads (scope type 'zonal', scope id null)
        $general = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'general');
        $regionalwise = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'regionalwise');
        $councilwise = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'councilwise');
        $schoolwise = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'schoolwise');
        $subjectwise = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'subjectwise-result-evaluation');
        $ownership = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'ownership-result-evaluation');

        $bestTenSchools = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'best-ten-schools');
        $leastTenSchools = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'least-ten-schools');
        $bestTenCouncils = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'best-ten-councils');
        $leastTenCouncils = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'least-ten-councils');
        $overallBestStudents = $this->precalcService->getReadyPayloadOrNull($examYear, 'zonal', null, 'overall-best-ten-students');

        // Fetch zone profile counts from database
        $schoolCounts = DB::table('schools')
            ->whereIn('region_id', $regionIds)
            ->selectRaw('
                count(*) as total,
                sum(case when upper(trim(ownership)) = "GOVERNMENT" then 1 else 0 end) as gov,
                sum(case when upper(trim(ownership)) = "NON-GOVERNMENT" then 1 else 0 end) as priv
            ')
            ->first();

        $councilsCount = DB::table('district_councils')
            ->whereIn('region_id', $regionIds)
            ->count();

        // 1. Meta parameters
        $meta = [
            'region_name' => 'TASIDO',
            'zone_name' => 'TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)',
            'exam_name' => 'MTIHANI WA UTAMILIFU WA DARASA LA SABA KIKANDA (PSLE ZONAL MOCK)',
            'exam_type' => 'PSLE',
            'exam_level' => 'Darasa la Saba',
            'exam_year' => $examYear,
            'start_date' => $overrides['exam_start_date'] ?? date('d-m-Y', strtotime('monday this week')),
            'end_date' => $overrides['exam_end_date'] ?? date('d-m-Y', strtotime('friday this week')),
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'snapshot_id' => $snapshotId,
        ];

        // 2. Operational parameters
        $operational = [
            'reo_name' => $overrides['reo_name'] ?? 'Ndg. Raymond Mapunda',
            'rto_name' => $overrides['rto_name'] ?? 'Ndg. Catherine J. Minde',
            'rso_name' => $overrides['rso_name'] ?? 'Ndg. Joseph S. Kishe',
            'exam_coordinator_name' => $overrides['exam_coordinator_name'] ?? 'Ndg. Catherine J. Minde',
            'marking_center' => $overrides['marking_center'] ?? 'Ifunda Girls\' Secondary School',
            'moderation_region' => $overrides['moderation_region'] ?? 'Dodoma',
            'production_days' => $overrides['production_days'] ?? '5',
            'marking_days' => $overrides['marking_days'] ?? '7',
            'markers_count' => $overrides['markers_count'] ?? '124',
            'students_assistants_count' => $overrides['students_assistants_count'] ?? '12',
            'budget_amount' => $overrides['budget_amount'] ?? '12500000',
            'risso_machine_count' => $overrides['risso_machine_count'] ?? '2',
            'risso_machine_value' => $overrides['risso_machine_value'] ?? '8500000',
            'exam_start_date' => $overrides['exam_start_date'] ?? '18-05-2026',
            'exam_end_date' => $overrides['exam_end_date'] ?? '22-05-2026',
            'collaborating_regions' => $overrides['collaborating_regions'] ?? 'Tabora, Singida, Iringa na Dodoma (TASIDO)',
            'prepared_by_title' => $overrides['prepared_by_title'] ?? 'Katibu wa Kamati ya Taaluma ya Kanda',
            'approved_by_title' => $overrides['approved_by_title'] ?? 'Mwenyekiti wa Kamati ya Mitihani ya Kanda',
        ];

        // 3. Attendance & Absence parsing (clamped to 0 and warning if sat > registered)
        $attendanceRegionRows = [];
        $registeredMale = 0;
        $registeredFemale = 0;
        $registeredTotal = 0;
        $satMale = 0;
        $satFemale = 0;
        $satTotal = 0;

        $dqSatGreaterRegistered = false;

        if ($regionalwise && isset($regionalwise['rows'])) {
            foreach ($regionalwise['rows'] as $row) {
                $regM = (int) ($row['registered']['m'] ?? 0);
                $regF = (int) ($row['registered']['f'] ?? 0);
                $regT = (int) ($row['registered']['t'] ?? 0);

                $satM = (int) ($row['sat']['m'] ?? 0);
                $satF = (int) ($row['sat']['f'] ?? 0);
                $satT = (int) ($row['sat']['t'] ?? 0);

                if ($satM > $regM || $satF > $regF || $satT > $regT) {
                    $dqSatGreaterRegistered = true;
                }

                $absM = max(0, $regM - $satM);
                $absF = max(0, $regF - $satF);
                $absT = max(0, $regT - $satT);

                $attRate = $regT > 0 ? round(($satT / $regT) * 100, 2) : 0.0;

                $attendanceRegionRows[] = [
                    'name' => $row['region'] ?? 'N/A',
                    'registered_m' => $regM,
                    'registered_f' => $regF,
                    'registered_t' => $regT,
                    'sat_m' => $satM,
                    'sat_f' => $satF,
                    'sat_t' => $satT,
                    'absent_m' => $absM,
                    'absent_f' => $absF,
                    'absent_t' => $absT,
                    'attendance_rate' => $attRate,
                ];

                $registeredMale += $regM;
                $registeredFemale += $regF;
                $registeredTotal += $regT;
                $satMale += $satM;
                $satFemale += $satF;
                $satTotal += $satT;
            }
        }

        $absentMale = max(0, $registeredMale - $satMale);
        $absentFemale = max(0, $registeredFemale - $satFemale);
        $absentTotal = max(0, $registeredTotal - $satTotal);
        $attendanceRate = $registeredTotal > 0 ? round(($satTotal / $registeredTotal) * 100, 2) : 0.0;

        // 4. Performance Grade Distribution (Table 2)
        $gradeDistribution = [];
        $totalA = 0; $totalB = 0; $totalC = 0; $totalD = 0; $totalE = 0;
        $totalSat = 0; $totalPass = 0;

        $hasZonalTotal = isset($general['total']['grades']);
        $femaleData = null;
        $maleData = null;

        if ($hasZonalTotal) {
            $femaleData = [
                'a' => (int) ($general['total']['grades']['a']['f'] ?? 0),
                'b' => (int) ($general['total']['grades']['b']['f'] ?? 0),
                'c' => (int) ($general['total']['grades']['c']['f'] ?? 0),
                'd' => (int) ($general['total']['grades']['d']['f'] ?? 0),
                'e' => (int) ($general['total']['grades']['e']['f'] ?? 0),
                'sat' => (int) ($general['total']['sat']['f'] ?? 0),
                'pass' => (int) ($general['total']['pass_ad']['f'] ?? 0),
            ];
            $maleData = [
                'a' => (int) ($general['total']['grades']['a']['m'] ?? 0),
                'b' => (int) ($general['total']['grades']['b']['m'] ?? 0),
                'c' => (int) ($general['total']['grades']['c']['m'] ?? 0),
                'd' => (int) ($general['total']['grades']['d']['m'] ?? 0),
                'e' => (int) ($general['total']['grades']['e']['m'] ?? 0),
                'sat' => (int) ($general['total']['sat']['m'] ?? 0),
                'pass' => (int) ($general['total']['pass_ad']['m'] ?? 0),
            ];
        } else {
            // Check if zonal general rows have 'FEMALE' / 'MALE'
            $fRow = collect($general['rows'] ?? [])->first(fn($r) => strtoupper($r['gender'] ?? $r['council'] ?? '') === 'FEMALE');
            $mRow = collect($general['rows'] ?? [])->first(fn($r) => strtoupper($r['gender'] ?? $r['council'] ?? '') === 'MALE');
            if ($fRow && $mRow) {
                $femaleData = [
                    'a' => (int) ($fRow['grades']['a']['t'] ?? 0),
                    'b' => (int) ($fRow['grades']['b']['t'] ?? 0),
                    'c' => (int) ($fRow['grades']['c']['t'] ?? 0),
                    'd' => (int) ($fRow['grades']['d']['t'] ?? 0),
                    'e' => (int) ($fRow['grades']['e']['t'] ?? 0),
                    'sat' => (int) ($fRow['sat']['t'] ?? 0),
                    'pass' => (int) ($fRow['pass_ad']['t'] ?? 0),
                ];
                $maleData = [
                    'a' => (int) ($mRow['grades']['a']['t'] ?? 0),
                    'b' => (int) ($mRow['grades']['b']['t'] ?? 0),
                    'c' => (int) ($mRow['grades']['c']['t'] ?? 0),
                    'd' => (int) ($mRow['grades']['d']['t'] ?? 0),
                    'e' => (int) ($mRow['grades']['e']['t'] ?? 0),
                    'sat' => (int) ($mRow['sat']['t'] ?? 0),
                    'pass' => (int) ($mRow['pass_ad']['t'] ?? 0),
                ];
            } else {
                // Aggregate from regional general payloads
                $regionalGenerals = DB::table('psle_precalculated_evaluations')
                    ->where('exam_year', $examYear)
                    ->where('scope_type', 'regional')
                    ->whereIn('scope_id', $regionIds)
                    ->where('evaluation_key', 'general')
                    ->where('snapshot_id', $snapshotId)
                    ->pluck('data');

                if ($regionalGenerals->isNotEmpty()) {
                    $femaleData = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'sat' => 0, 'pass' => 0];
                    $maleData = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'sat' => 0, 'pass' => 0];

                    foreach ($regionalGenerals as $regGenRaw) {
                        $regGen = json_decode($regGenRaw, true);
                        if (!$regGen) continue;

                        $regF = collect($regGen['rows'] ?? [])->first(fn($r) => strtoupper($r['council'] ?? '') === 'FEMALE');
                        $regM = collect($regGen['rows'] ?? [])->first(fn($r) => strtoupper($r['council'] ?? '') === 'MALE');

                        if ($regF) {
                            $femaleData['a'] += (int) ($regF['grades']['a']['t'] ?? 0);
                            $femaleData['b'] += (int) ($regF['grades']['b']['t'] ?? 0);
                            $femaleData['c'] += (int) ($regF['grades']['c']['t'] ?? 0);
                            $femaleData['d'] += (int) ($regF['grades']['d']['t'] ?? 0);
                            $femaleData['e'] += (int) ($regF['grades']['e']['t'] ?? 0);
                            $femaleData['sat'] += (int) ($regF['sat']['t'] ?? 0);
                            $femaleData['pass'] += (int) ($regF['pass_ad']['t'] ?? 0);
                        }
                        if ($regM) {
                            $maleData['a'] += (int) ($regM['grades']['a']['t'] ?? 0);
                            $maleData['b'] += (int) ($regM['grades']['b']['t'] ?? 0);
                            $maleData['c'] += (int) ($regM['grades']['c']['t'] ?? 0);
                            $maleData['d'] += (int) ($regM['grades']['d']['t'] ?? 0);
                            $maleData['e'] += (int) ($regM['grades']['e']['t'] ?? 0);
                            $maleData['sat'] += (int) ($regM['sat']['t'] ?? 0);
                            $maleData['pass'] += (int) ($regM['pass_ad']['t'] ?? 0);
                        }
                    }
                }
            }
        }

        // Add logging in dev/local mode
        if (app()->environment('local', 'testing', 'dev')) {
            Log::debug('Zonal Result Book Grade Distribution Mapping', [
                'has_zonal_total' => $hasZonalTotal,
                'female_data' => $femaleData,
                'male_data' => $maleData,
            ]);
        }

        foreach (['FEMALE', 'MALE'] as $gender) {
            $gData = $gender === 'FEMALE' ? $femaleData : $maleData;
            if ($gData) {
                $a = $gData['a'];
                $b = $gData['b'];
                $c = $gData['c'];
                $d = $gData['d'];
                $e = $gData['e'];
                $sat = $gData['sat'];
                $pass = $gData['pass'];
                $pct = $sat > 0 ? round(($pass / $sat) * 100, 2) : 0.0;

                $genderLabel = $gender === 'FEMALE' ? 'FEMALE' : 'MALE';
                $gradeDistribution[$genderLabel] = [
                    'gender' => $gender === 'FEMALE' ? 'Wasichana (KE)' : 'Wavulana (ME)',
                    'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e,
                    'sat' => $sat, 'pass' => $pass, 'pct' => $pct
                ];

                $totalA += $a;
                $totalB += $b;
                $totalC += $c;
                $totalD += $d;
                $totalE += $e;
                $totalSat += $sat;
                $totalPass += $pass;
            } else {
                $genderLabel = $gender === 'FEMALE' ? 'FEMALE' : 'MALE';
                $gradeDistribution[$genderLabel] = [
                    'gender' => $gender === 'FEMALE' ? 'Wasichana (KE)' : 'Wavulana (ME)',
                    'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'sat' => 0, 'pass' => 0, 'pct' => 0.0
                ];
            }
        }

        $totalPct = $totalSat > 0 ? round(($totalPass / $totalSat) * 100, 2) : 0.0;
        $gradeDistribution['TOTAL'] = [
            'gender' => 'JUMLA KUU',
            'a' => $totalA, 'b' => $totalB, 'c' => $totalC, 'd' => $totalD, 'e' => $totalE,
            'sat' => $totalSat, 'pass' => $totalPass, 'pct' => $totalPct
        ];

        // 5. Region-wise Performance Comparison (Table 3)
        $performanceRegions = [];
        if ($regionalwise && isset($regionalwise['rows'])) {
            foreach ($regionalwise['rows'] as $index => $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passAC = (int) ($row['pass_ac']['t'] ?? 0);
                $passD = (int) ($row['grades']['d']['t'] ?? 0);
                $failE = (int) ($row['grades']['e']['t'] ?? 0);
                $passRate = $satVal > 0 ? round((($passAC + $passD) / $satVal) * 100, 2) : 0.0;

                $avgMarksVal = $row['average_marks'] ?? $row['avg_marks'] ?? null;
                if (is_null($avgMarksVal)) {
                    $totalMarksSum = (float) ($row['total_marks_sum'] ?? $row['total_marks'] ?? 0.0);
                    $avgMarksVal = $this->calculateAverageMarks($totalMarksSum, $satVal);
                }
                $avgMarksVal = round((float) $avgMarksVal, 2);

                $performanceRegions[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['region'] ?? 'N/A',
                    'sat' => $satVal,
                    'pass_ac' => $passAC,
                    'pass_d' => $passD,
                    'fail' => $failE,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['avg_grade'] ?? 'E',
                ];
            }
        }

        // Sort regions by average_marks descending
        usort($performanceRegions, function($a, $b) {
            return $b['average_marks'] <=> $a['average_marks'];
        });
        foreach ($performanceRegions as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        // 6. Councilwise performance parsing (Table 4)
        $performanceCouncils = [];
        if ($councilwise && isset($councilwise['rows'])) {
            foreach ($councilwise['rows'] as $index => $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passAC = (int) ($row['pass_ac']['t'] ?? 0);
                $passD = (int) ($row['grades']['d']['t'] ?? 0);
                $failE = (int) ($row['grades']['e']['t'] ?? 0);
                $passRate = $satVal > 0 ? round((($passAC + $passD) / $satVal) * 100, 2) : 0.0;

                $avgMarksVal = $row['average_marks'] ?? $row['avg_marks'] ?? null;
                if (is_null($avgMarksVal)) {
                    $totalMarksSum = (float) ($row['total_marks_sum'] ?? $row['total_marks'] ?? 0.0);
                    $avgMarksVal = $this->calculateAverageMarks($totalMarksSum, $satVal);
                }
                $avgMarksVal = round((float) $avgMarksVal, 2);

                $performanceCouncils[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['council'] ?? 'N/A',
                    'region' => $row['region'] ?? 'N/A',
                    'sat' => $satVal,
                    'pass_ac' => $passAC,
                    'pass_d' => $passD,
                    'fail' => $failE,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['avg_grade'] ?? 'E',
                ];
            }
        }

        // Sort by average_marks descending (higher is better)
        usort($performanceCouncils, function($a, $b) {
            return $b['average_marks'] <=> $a['average_marks'];
        });
        foreach ($performanceCouncils as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        // 7. Top 10 and Bottom 10 Schools (Tables 5 & 6)
        $topSchools = [];
        if ($bestTenSchools && isset($bestTenSchools['rows'])) {
            foreach ($bestTenSchools['rows'] as $index => $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passVal = (int) (($row['pass_ad']['t'] ?? ($row['pass_ac']['t'] ?? 0)));
                $passRate = $satVal > 0 ? round(($passVal / $satVal) * 100, 2) : 0.0;

                $avgMarksVal = $row['average_marks'] ?? $row['avg_marks'] ?? null;
                if (is_null($avgMarksVal)) {
                    $totalMarksSum = (float) ($row['total_marks_sum'] ?? $row['total_marks'] ?? 0.0);
                    $avgMarksVal = $this->calculateAverageMarks($totalMarksSum, $satVal);
                }
                $avgMarksVal = round((float) $avgMarksVal, 2);

                $topSchools[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['school'] ?? 'N/A',
                    'council' => $row['council'] ?? 'N/A',
                    'region' => $row['region'] ?? 'N/A',
                    'ownership' => $row['ownership'] ?? 'GOVERNMENT',
                    'sat' => $satVal,
                    'pass' => $passVal,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['avg_grade'] ?? 'E',
                ];
            }
        }

        // Sort Top 10 by average_marks descending
        usort($topSchools, function($a, $b) {
            return $b['average_marks'] <=> $a['average_marks'];
        });
        foreach ($topSchools as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        $bottomSchools = [];
        if ($leastTenSchools && isset($leastTenSchools['rows'])) {
            foreach ($leastTenSchools['rows'] as $index => $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passVal = (int) (($row['pass_ad']['t'] ?? ($row['pass_ac']['t'] ?? 0)));
                $passRate = $satVal > 0 ? round(($passVal / $satVal) * 100, 2) : 0.0;

                $avgMarksVal = $row['average_marks'] ?? $row['avg_marks'] ?? null;
                if (is_null($avgMarksVal)) {
                    $totalMarksSum = (float) ($row['total_marks_sum'] ?? $row['total_marks'] ?? 0.0);
                    $avgMarksVal = $this->calculateAverageMarks($totalMarksSum, $satVal);
                }
                $avgMarksVal = round((float) $avgMarksVal, 2);

                $bottomSchools[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['school'] ?? 'N/A',
                    'council' => $row['council'] ?? 'N/A',
                    'region' => $row['region'] ?? 'N/A',
                    'ownership' => $row['ownership'] ?? 'GOVERNMENT',
                    'sat' => $satVal,
                    'pass' => $passVal,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['avg_grade'] ?? 'E',
                ];
            }
        }

        // Sort Bottom 10 by average_marks ascending (lower/weaker is at the top of bottom list)
        usort($bottomSchools, function($a, $b) {
            return $a['average_marks'] <=> $b['average_marks'];
        });
        foreach ($bottomSchools as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        // 8. Subjectwise Performance (Table 7)
        $subjectsPerformance = [];
        if ($subjectwise && isset($subjectwise['rows'])) {
            foreach ($subjectwise['rows'] as $index => $row) {
                $satVal = (int) ($row['sat'] ?? 0);
                $passVal = (int) ($row['a_to_d'] ?? (($row['grade_a'] ?? 0) + ($row['grade_b'] ?? 0) + ($row['grade_c'] ?? 0) + ($row['grade_d'] ?? 0)));
                $failVal = (int) ($row['grade_e'] ?? 0);
                $passRate = $satVal > 0 ? round(($passVal / $satVal) * 100, 2) : 0.0;

                $avgMarksVal = $row['average_marks'] ?? $row['avg_marks'] ?? 0.0;
                $avgMarksVal = round((float) $avgMarksVal, 2);

                $subjectsPerformance[] = [
                    'position' => $index + 1,
                    'name' => $row['name'] ?? 'N/A',
                    'sat' => $satVal,
                    'pass' => $passVal,
                    'fail' => $failVal,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['grade'] ?? 'E',
                ];
            }
        }

        // Sort subjects by average_marks descending
        usort($subjectsPerformance, function($a, $b) {
            return $b['average_marks'] <=> $a['average_marks'];
        });
        foreach ($subjectsPerformance as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        // 9. Ownership Performance (Table 8)
        $ownershipPerformance = [];
        if ($ownership && isset($ownership['rows'])) {
            foreach ($ownership['rows'] as $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passVal = (int) (($row['pass_ad']['t'] ?? ($row['pass_ac']['t'] ?? 0)));
                $failVal = (int) ($row['grades']['e']['t'] ?? 0);
                $passRate = $satVal > 0 ? round(($passVal / $satVal) * 100, 2) : 0.0;

                $ownLabel = strtoupper(trim((string) ($row['ownership'] ?? '')));
                $labelSwahili = $ownLabel === 'GOVERNMENT' ? 'Shule za Serikali' : 'Shule za Binafsi';

                $avgMarksVal = $row['average_marks'] ?? $row['avg_marks'] ?? null;
                if (is_null($avgMarksVal)) {
                    $totalMarksSum = (float) ($row['total_marks_sum'] ?? $row['total_marks'] ?? 0.0);
                    $avgMarksVal = $this->calculateAverageMarks($totalMarksSum, $satVal);
                }
                $avgMarksVal = round((float) $avgMarksVal, 2);

                $ownershipPerformance[] = [
                    'ownership' => $labelSwahili,
                    'schools_count' => $row['schools_count'] ?? 0,
                    'registered' => $row['registered']['t'] ?? 0,
                    'sat' => $satVal,
                    'pass' => $passVal,
                    'fail' => $failVal,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                ];
            }
        }

        // Sort ownership by average_marks descending
        usort($ownershipPerformance, function($a, $b) {
            return $b['average_marks'] <=> $a['average_marks'];
        });

        // 10. Top Candidates
        $topCandidates = [];
        if ($overallBestStudents && isset($overallBestStudents['rows'])) {
            foreach ($overallBestStudents['rows'] as $index => $row) {
                $topCandidates[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['name'] ?? 'N/A',
                    'gender' => strtoupper(trim((string) ($row['gender'] ?? ''))),
                    'school' => $row['school'] ?? 'N/A',
                    'council' => $row['council'] ?? 'N/A',
                    'region' => $row['region'] ?? 'N/A',
                    'average_marks' => round((float) ($row['average_marks'] ?? $row['avg_marks'] ?? ($row['total_marks'] / 7) ?? 0.0), 2),
                    'marks' => $row['total_marks'] ?? 0,
                ];
            }
        }

        // 11. Best & Least Councils
        $topCouncilsList = [];
        if ($bestTenCouncils && isset($bestTenCouncils['rows'])) {
            foreach ($bestTenCouncils['rows'] as $index => $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passAC = (int) ($row['pass_ac']['t'] ?? 0);
                $passD = (int) ($row['grades']['d']['t'] ?? 0);
                $passRate = $satVal > 0 ? round((($passAC + $passD) / $satVal) * 100, 2) : 0.0;
                $avgMarksVal = round((float) ($row['average_marks'] ?? $row['avg_marks'] ?? 0.0), 2);

                $topCouncilsList[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['council'] ?? 'N/A',
                    'region' => $row['region'] ?? 'N/A',
                    'sat' => $satVal,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['avg_grade'] ?? 'E',
                ];
            }
        }
        usort($topCouncilsList, function($a, $b) {
            return $b['average_marks'] <=> $a['average_marks'];
        });
        foreach ($topCouncilsList as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        $bottomCouncilsList = [];
        if ($leastTenCouncils && isset($leastTenCouncils['rows'])) {
            foreach ($leastTenCouncils['rows'] as $index => $row) {
                $satVal = (int) ($row['sat']['t'] ?? 0);
                $passAC = (int) ($row['pass_ac']['t'] ?? 0);
                $passD = (int) ($row['grades']['d']['t'] ?? 0);
                $passRate = $satVal > 0 ? round((($passAC + $passD) / $satVal) * 100, 2) : 0.0;
                $avgMarksVal = round((float) ($row['average_marks'] ?? $row['avg_marks'] ?? 0.0), 2);

                $bottomCouncilsList[] = [
                    'position' => $row['position'] ?? ($index + 1),
                    'name' => $row['council'] ?? 'N/A',
                    'region' => $row['region'] ?? 'N/A',
                    'sat' => $satVal,
                    'pass_rate' => $passRate,
                    'average_marks' => $avgMarksVal,
                    'grade' => $row['avg_grade'] ?? 'E',
                ];
            }
        }
        usort($bottomCouncilsList, function($a, $b) {
            return $a['average_marks'] <=> $b['average_marks'];
        });
        foreach ($bottomCouncilsList as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);

        // 12. Data Quality Checks
        $examYearId = DB::table('exam_years')->where('year_label', $examYear)->value('id');
        $dqIssues = [];

        // Check A: Schools with registered candidates but no marks
        $schoolsWithNoMarks = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->leftJoin('candidate_results as cr', function($join) use ($examYear, $snapshotId) {
                $join->on('cr.candidate_id', '=', 'c.id')
                     ->where('cr.year', '=', $examYear)
                     ->where('cr.snapshot_id', '=', $snapshotId);
            })
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_year_id', $examYearId)
            ->whereNull('cr.id')
            ->select('s.name', DB::raw('count(*) as count'))
            ->groupBy('s.name')
            ->get();

        foreach ($schoolsWithNoMarks as $row) {
            $dqIssues[] = "Shule ya [{$row->name}] ina watahiniwa {$row->count} waliosajiliwa lakini hawana alama zozote kwenye mfumo.";
        }

        // Check B: Candidates with missing gender
        $missingGenderCount = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_year_id', $examYearId)
            ->where(function($query) {
                $query->whereNull('c.gender')
                      ->orWhereRaw("trim(c.gender) = ''")
                      ->orWhereNotIn(DB::raw('upper(c.gender)'), ['M', 'F']);
            })
            ->count();

        if ($missingGenderCount > 0) {
            $dqIssues[] = "Kuna watahiniwa {$missingGenderCount} ambao hawana taarifa sahihi za jinsia (ME/KE) kwenye mfumo.";
        }

        // Check C: Duplicate index numbers
        $duplicateIndexCount = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $regionIds)
            ->where('cer.exam_year_id', $examYearId)
            ->select('c.candidate_id')
            ->groupBy('c.candidate_id')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();

        if ($duplicateIndexCount > 0) {
            $dqIssues[] = "Kuna namba za usajili (Index Numbers) {$duplicateIndexCount} zinazojirudia kwa zaidi ya mtahiniwa mmoja.";
        }

        // Check D: Sat candidates greater than registered candidates
        if ($dqSatGreaterRegistered) {
            $dqIssues[] = "Hitilafu: Idadi ya watahiniwa waliofanya mtihani katika baadhi ya vituo ni kubwa kuliko idadi ya waliosajiliwa rasmi.";
        }

        // Check E: Schools without ownership category
        $schoolsWithoutOwnership = DB::table('schools')
            ->whereIn('region_id', $regionIds)
            ->where(function($query) {
                $query->whereNull('ownership')
                      ->orWhereRaw("trim(ownership) = ''")
                      ->orWhereNotIn(DB::raw('upper(ownership)'), ['GOVERNMENT', 'NON-GOVERNMENT']);
            })
            ->pluck('name');

        if ($schoolsWithoutOwnership->isNotEmpty()) {
            $dqIssues[] = "Shule zifuatazo hazina jamii ya umiliki (Government/Non-Government) kwenye mfumo: " . $schoolsWithoutOwnership->implode(', ');
        }

        // Check F: Councils without complete school mapping
        $councilsWithoutSchools = DB::table('district_councils as co')
            ->whereIn('co.region_id', $regionIds)
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('schools as s')
                      ->whereColumn('s.council_id', 'co.id');
            })
            ->pluck('co.name');

        if ($councilsWithoutSchools->isNotEmpty()) {
            $dqIssues[] = "Halmashauri zifuatazo hazina shule zozote zilizounganishwa nazo kwenye mfumo: " . $councilsWithoutSchools->implode(', ');
        }

        // Check G: Schools without coordinates
        if (\Illuminate\Support\Facades\Schema::hasColumn('schools', 'latitude')) {
            $schoolsWithoutCoords = DB::table('schools')
                ->whereIn('region_id', $regionIds)
                ->whereNull('latitude')
                ->count();

            if ($schoolsWithoutCoords > 0) {
                $dqIssues[] = "Kuna shule {$schoolsWithoutCoords} zisizokuwa na viwianisho vya kijiografia (Coordinates) katika Kanda yetu.";
            }
        }

        // Check H: Empty or missing snapshot payload sections
        if (empty($general) || empty($councilwise) || empty($schoolwise) || empty($subjectwise) || empty($ownership)) {
            $dqIssues[] = "Tahadhari: Baadhi ya taarifa zilizochakatwa awali (Precalculated Payloads) hazikupatikana, na mfumo umelazimika kutumia taarifa mbadala au tupu.";
        }

        $dqSummary = empty($dqIssues)
            ? "Uhakiki wa awali wa data unaonesha kuwa taarifa zilizotumika kuzalisha kitabu hiki zimekamilika kwa kiwango kinachokubalika, bila kubainika kwa hitilafu kubwa zinazoweza kuathiri tafsiri ya matokeo."
            : "Uhakiki wa data umebaini hitilafu kadhaa ambazo zinapaswa kushughulikiwa na timu ya TEHAMA kwa ajili ya usahihi kamili wa takwimu.";

        return [
            'meta' => $meta,
            'operational' => $operational,
            'zone_profile' => [
                'total_schools' => $schoolCounts->total ?? 0,
                'government_schools' => $schoolCounts->gov ?? 0,
                'private_schools' => $schoolCounts->priv ?? 0,
                'councils_count' => $councilsCount,
                'regions_count' => count($regionIds),
            ],
            'attendance' => [
                'registered_male' => $registeredMale,
                'registered_female' => $registeredFemale,
                'registered_total' => $registeredTotal,
                'sat_male' => $satMale,
                'sat_female' => $satFemale,
                'sat_total' => $satTotal,
                'absent_male' => $absentMale,
                'absent_female' => $absentFemale,
                'absent_total' => $absentTotal,
                'attendance_rate' => $attendanceRate,
                'region_rows' => $attendanceRegionRows,
            ],
            'performance' => [
                'regional' => $gradeDistribution['TOTAL'] ?? [],
                'grade_distribution' => array_values(array_filter($gradeDistribution, fn($k) => $k !== 'TOTAL', ARRAY_FILTER_USE_KEY)),
                'gender' => [
                    'female' => $gradeDistribution['FEMALE'] ?? [],
                    'male' => $gradeDistribution['MALE'] ?? [],
                ],
                'regions' => $performanceRegions,
                'councils' => $performanceCouncils,
                'subjects' => $subjectsPerformance,
                'top_schools' => $topSchools,
                'bottom_schools' => $bottomSchools,
                'ownership' => $ownershipPerformance,
                'top_candidates' => $topCandidates,
                'top_councils' => $topCouncilsList,
                'bottom_councils' => $bottomCouncilsList,
            ],
            'data_quality' => [
                'issues' => $dqIssues,
                'summary' => $dqSummary,
            ],
        ];
    }

    private function calculateAverageMarks(float|int $totalMarks, int $satCandidates): float
    {
        return \App\Services\Results\PsleSchoolAverageService::calculate((float) $totalMarks, $satCandidates, 0)['average'];
    }
}
