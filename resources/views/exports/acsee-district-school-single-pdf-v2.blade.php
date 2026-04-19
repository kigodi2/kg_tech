<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>School Results - {{ $yearLabel }}</title>
    <style>
        @page { size: A3 portrait; margin: 8mm; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-weight: 400;
            white-space: nowrap;
        }
    </style>
</head>
<body>
@php
    $school = $schoolRows->first()?->candidate?->school;
    $emblemPath = public_path('images/emblem.png');
    $emblemSrc = is_file($emblemPath) ? 'file://' . str_replace('\\', '/', $emblemPath) : null;
    $subjectAliases = (array) config('necta_subject_aliases.acsee', []);
    $gradeService = app(\App\Services\Results\NectaGradingService::class);
    $subjectLabel = function ($subject) use ($subjectAliases) {
        $code = trim((string) ($subject?->code ?? ''));
        if ($code !== '' && isset($subjectAliases[$code])) {
            return (string) $subjectAliases[$code];
        }
        return strtoupper((string) ($subject?->name ?? 'SUBJECT'));
    };
    $requiredPaperCodesForSubject = function ($subject): array {
        if (!$subject) {
            return ['paper_1'];
        }
        $codes = [];
        $written = max(1, min(2, (int) ($subject->written_papers ?? 1)));
        for ($i = 1; $i <= $written; $i++) {
            $codes[] = "paper_{$i}";
        }
        if (!empty($subject->has_practical)) {
            $codes[] = 'paper_3';
        }
        return array_values(array_unique($codes));
    };

    $divisionMap = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '0' => '0'];
    $displayCombination = function (?string $combination): string {
        $value = trim((string) $combination);
        if ($value === '') {
            return '-';
        }
        return strtoupper($value) === 'PMCS' ? 'PMCs' : $value;
    };

    $candidatesWithMetrics = $schoolRows->map(function ($row) use ($divisionMap, $subjectLabel, $requiredPaperCodesForSubject, $gradeService, $yearLabel) {
        $latestMarks = collect($row->subjectMarks ?? [])
            ->groupBy('subject_id')
            ->map(function ($subjectRows) use ($requiredPaperCodesForSubject) {
                $rows = collect($subjectRows)->sortByDesc('id')->values();
                $subject = $rows->first()?->subject;
                $required = $requiredPaperCodesForSubject($subject);
                $hasPositiveByPaper = [];
                foreach ($required as $paperCode) {
                    $hasPositiveByPaper[$paperCode] = $rows->contains(function ($m) use ($paperCode) {
                        $v = $m->{$paperCode} ?? null;
                        return $v !== null && (float) $v > 0;
                    });
                }

                $preferred = $rows->first(function ($mark) use ($required, $hasPositiveByPaper) {
                    $status = strtoupper((string) ($mark->subject_status ?? ''));
                    if ($status === 'INC') {
                        return false;
                    }

                    foreach ($required as $paperCode) {
                        $value = $mark->{$paperCode} ?? null;
                        if ($value === null) {
                            return false;
                        }
                        if (($hasPositiveByPaper[$paperCode] ?? false) && (float) $value <= 0) {
                            return false;
                        }
                    }
                    return true;
                });
                return $preferred ?: $rows->first();
            })
            ->filter()
            ->sortBy(function ($mark) use ($subjectLabel) {
                $code = strtoupper(trim((string) ($mark?->subject?->code ?? '')));
                $name = strtoupper(trim((string) ($mark?->subject?->name ?? '')));
                $label = strtoupper($subjectLabel($mark?->subject));
                $isGeneralStudies = $code === '111'
                    || $name === 'GENERAL STUDIES'
                    || $label === 'G/STUDIES';

                return sprintf(
                    '%d_%s_%s',
                    $isGeneralStudies ? 0 : 1,
                    $code !== '' ? $code : 'ZZZ',
                    $name
                );
            })
            ->values();

        $coreSubjectIds = collect($row->candidate?->subjectSelections ?? [])
            ->filter(function ($sel) use ($yearLabel) {
                if (empty($sel->is_active) || empty($sel->is_principal)) {
                    return false;
                }
                $yearMatch = (string) ($sel->year ?? '') === (string) $yearLabel;
                $noYearOnSelection = empty($sel->year);
                return $yearMatch || $noYearOnSelection;
            })
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $subjectResults = [];
        $hasAnyEnteredMark = false;
        $hasIncompleteSubject = false;
        $allAbsentOrX = $latestMarks->isNotEmpty();
        $aggtSubjects = [];

        foreach ($latestMarks as $mark) {
            $subjectName = $subjectLabel($mark->subject);
            $status = strtoupper((string) ($mark->subject_status ?? ''));
            if (!in_array($status, ['ABS', 'X'], true)) {
                $allAbsentOrX = false;
            }

            if (
                $mark->marks_obtained !== null
                || $mark->paper_1 !== null
                || $mark->paper_2 !== null
                || $mark->paper_3 !== null
                || $status === 'INC'
            ) {
                $hasAnyEnteredMark = true;
            }

            if (in_array($status, ['ABS', 'X'], true)) {
                $subjectResults[] = $subjectName . '=' . $status . " '" . $status . "'";
                continue;
            }

            if ($status === 'INC' || $mark->marks_obtained === null) {
                $hasIncompleteSubject = true;
                $subjectResults[] = $subjectName . "=INC 'INC'";
                continue;
            }

            $score = rtrim(rtrim(number_format((float) $mark->marks_obtained, 2, '.', ''), '0'), '.');
            $grade = $mark->marks_obtained !== null
                ? $gradeService->calculateGrade((float) $mark->marks_obtained)
                : strtoupper((string) ($mark->grade ?? ''));
            $subjectResults[] = $subjectName . '=' . $score . ($grade !== '' ? " '" . $grade . "'" : '');
            if (
                $grade !== ''
                && in_array($grade, ['A', 'B', 'C', 'D', 'E', 'S', 'F'], true)
                && !$gradeService->isExcludedSubject((string) ($mark->subject?->name ?? ''))
            ) {
                $aggtSubjects[] = [
                    'subject_id' => (int) ($mark->subject_id ?? 0),
                    'subject_name' => (string) ($mark->subject?->name ?? ''),
                    'grade' => $grade,
                    'points' => $gradeService->getGradePoints($grade),
                ];
            }
        }

        $candidateStatus = 'COMPLETE';
        if (!$hasAnyEnteredMark && $latestMarks->isEmpty()) {
            $candidateStatus = 'ABS';
        } elseif ($hasIncompleteSubject) {
            $candidateStatus = 'INC';
        } elseif ($allAbsentOrX) {
            $candidateStatus = 'ABS';
        }

        // Fallback for legacy/missing principal mappings:
        // use available non-excluded graded subjects so AGGT/DIV don't collapse to 0.
        $effectiveCoreSubjectIds = !empty($coreSubjectIds)
            ? $coreSubjectIds
            : collect($aggtSubjects)
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

        $computedAggtPoints = $gradeService->calculateAggtFromSubjectGrades($aggtSubjects, $effectiveCoreSubjectIds);
        $principalPassCount = $gradeService->countPrincipalPassesFromSubjectGrades($aggtSubjects, $effectiveCoreSubjectIds);
        $divisionInfo = $gradeService->calculateDivisionWithEligibility((float) ($computedAggtPoints ?? 0), $principalPassCount);
        $divisionRaw = (string) ($divisionInfo['division'] ?? '0');
        $division = $divisionMap[$divisionRaw] ?? (trim($divisionRaw) !== '' ? strtoupper($divisionRaw) : '0');
        $gpaRows = collect($aggtSubjects)
            ->filter(fn (array $row) => in_array((int) ($row['subject_id'] ?? 0), $effectiveCoreSubjectIds, true))
            ->sortBy('points')
            ->take(3)
            ->values();
        $computedGpa = ($gpaRows->isNotEmpty() && $candidateStatus === 'COMPLETE')
            ? round((float) $gpaRows->avg('points'), 4)
            : 0;
        $computedGpaPointsSum = (float) $gpaRows->sum('points');
        $computedGpaSubjectCount = (int) $gpaRows->count();

        return [
            'candidate' => $row->candidate,
            'candidateStatus' => $candidateStatus,
            'division' => $division,
            'totalMarks' => (float) ($row->total_marks ?? 0),
            'average' => (float) ($row->total_percentage ?? 0),
            'gpa' => $computedGpa,
            'totalPoints' => $computedAggtPoints ?? 0,
            'subjectResultsStr' => $allAbsentOrX ? 'ABS' : (!empty($subjectResults) ? implode(', ', $subjectResults) : '-'),
            'gpaPointsSum' => $computedGpaPointsSum,
            'gpaSubjectCount' => $computedGpaSubjectCount,
            'latestMarks' => $latestMarks,
        ];
    })->values();

    $statusOrder = ['COMPLETE' => 0, 'INC' => 1, 'ABS' => 2];

    $candidatesWithMetrics = $candidatesWithMetrics->sort(function ($a, $b) use ($statusOrder) {
        $aStatus = $statusOrder[$a['candidateStatus']] ?? 9;
        $bStatus = $statusOrder[$b['candidateStatus']] ?? 9;
        if ($aStatus !== $bStatus) return $aStatus <=> $bStatus;

        if ($a['candidateStatus'] !== 'COMPLETE') {
            return strcmp((string) ($a['candidate']?->candidate_id ?? ''), (string) ($b['candidate']?->candidate_id ?? ''));
        }

        $gpaCmp = ((float) ($a['gpa'] ?? 99)) <=> ((float) ($b['gpa'] ?? 99));
        if ($gpaCmp !== 0) return $gpaCmp;

        $aggtCmp = ((float) ($a['totalPoints'] ?? 999)) <=> ((float) ($b['totalPoints'] ?? 999));
        if ($aggtCmp !== 0) return $aggtCmp;

        $avgCmp = ((float) ($b['average'] ?? 0)) <=> ((float) ($a['average'] ?? 0));
        if ($avgCmp !== 0) return $avgCmp;

        return strcmp((string) ($a['candidate']?->candidate_id ?? ''), (string) ($b['candidate']?->candidate_id ?? ''));
    })->values();

    $divisionStatsBySex = [
        'F' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
        'M' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
    ];
    $absIncStatsBySex = [
        'F' => ['ABS' => 0, 'INC' => 0],
        'M' => ['ABS' => 0, 'INC' => 0],
    ];
    $totalDivisions = ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0];
    $genderCounts = ['F' => 0, 'M' => 0];

    foreach ($candidatesWithMetrics as $data) {
        $candidate = $data['candidate'];
        $gender = strtoupper((string) ($candidate->gender ?? ''));
        if (!isset($genderCounts[$gender])) {
            continue;
        }

        if ($data['candidateStatus'] === 'ABS') {
            $absIncStatsBySex[$gender]['ABS']++;
        } elseif ($data['candidateStatus'] === 'INC') {
            $absIncStatsBySex[$gender]['INC']++;
        } else {
            $div = $data['division'];
            if (!isset($totalDivisions[$div])) $div = '0';
            $totalDivisions[$div]++;
            $divisionStatsBySex[$gender][$div]++;
        }

        $genderCounts[$gender]++;
    }

    $totalCandidates = $candidatesWithMetrics->count();
    $totalPassed = $totalDivisions['I'] + $totalDivisions['II'] + $totalDivisions['III'] + $totalDivisions['IV'];
    $totalFailed = $totalDivisions['0'];
    $totalInc = $absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC'];
    $totalAbsent = $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'];

    $overallGpaPoints = 0;
    $overallGpaSubjects = 0;
    foreach ($candidatesWithMetrics as $data) {
        if (($data['candidateStatus'] ?? 'COMPLETE') === 'COMPLETE') {
            $overallGpaPoints += (float) ($data['gpaPointsSum'] ?? 0);
            $overallGpaSubjects += (int) ($data['gpaSubjectCount'] ?? 0);
        }
    }
    $overallGpa = $overallGpaSubjects > 0 ? $overallGpaPoints / $overallGpaSubjects : 0;
    $overallGpaInfo = app(\App\Services\Results\NectaGradingService::class)->getGpaCompetence((float) $overallGpa);

    $subjectPerformance = [];
    foreach ($candidatesWithMetrics as $data) {
        $latestMarks = collect($data['latestMarks'] ?? [])->sortByDesc('id')->unique('subject_id');
        foreach ($latestMarks as $mark) {
            $subjectId = (int) ($mark->subject_id ?? 0);
            if ($subjectId <= 0) continue;
            $status = strtoupper((string) ($mark->subject_status ?? ''));
            $grade = strtoupper((string) ($mark->grade ?? ''));

            if (!isset($subjectPerformance[$subjectId])) {
                $subjectPerformance[$subjectId] = [
                    'code' => $mark->subject?->code ?? '-',
                    'name' => $mark->subject?->name ?? 'Unknown',
                    'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'S' => 0, 'F' => 0, 'ABS' => 0,
                ];
            }

            if (in_array($status, ['ABS', 'X'], true) || $mark->marks_obtained === null) {
                $subjectPerformance[$subjectId]['ABS']++;
            } elseif ($grade && in_array($grade, ['A', 'B', 'C', 'D', 'E', 'S', 'F'], true)) {
                $subjectPerformance[$subjectId][$grade]++;
            }
        }
    }
@endphp

<div style="background-color: #B0E0E6; min-height: 100vh; padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'DejaVu Sans', Arial, sans-serif; font-weight: 400; white-space: nowrap;">
    <div style="padding-left: 1rem; padding-right: 1rem;">

        <div style="background-color: #B0E0E6; padding-top: 1.5rem; padding-bottom: 1.5rem; padding-left: 1rem; padding-right: 1rem; margin-bottom: 1.5rem;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="width: 10%; text-align: center; vertical-align: middle;">
                        @if($emblemSrc)<img src="{{ $emblemSrc }}" alt="Coat of Arms" style="height: 64px; width: 64px; object-fit: contain;">@endif
                    </td>
                    <td style="width: 80%; text-align: center; padding-left: 1rem; padding-right: 1rem; vertical-align: middle;">
                        <p style="margin: 0; font-size: 1.00rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; line-height: 1.15;">PRIME MINISTER'S OFFICE</p>
                        <p style="margin: 0; font-size: 1.00rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; line-height: 1.15;">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</p>
                        <p style="margin: 0.15rem 0 0 0; font-size: 1.00rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; line-height: 1.15;">TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA</p>
                        <p style="margin: 0.15rem 0 0 0; font-size: 1.00rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; line-height: 1.15;">OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION - {{ $yearLabel }}</p>
                        <p style="margin: 0.15rem 0 0 0; font-size: 1.00rem; font-weight: bold; color: #1e3a8a; white-space: nowrap; line-height: 1.15;">{{ $school->code ?? 'N/A' }} - {{ strtoupper($school->name ?? 'UNKNOWN SCHOOL') }}</p>
                    </td>
                    <td style="width: 10%; text-align: center; vertical-align: middle;">
                        @if($emblemSrc)<img src="{{ $emblemSrc }}" alt="Coat of Arms" style="height: 64px; width: 64px; object-fit: contain;">@endif
                    </td>
                </tr>
            </table>
        </div>

        <div style="background-color: #B0E0E6; padding: 0 1rem; margin-bottom: 0;">
            <table style="width: 100%; table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #4b5563;">
                <thead>
                    <tr style="background-color: #003366;"><th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="8">DIVISION PERFORMANCE SUMMARY</th></tr>
                    <tr style="background-color: LIGHTYELLOW;">
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">SEX</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">I</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">II</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">III</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">IV</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">0</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">INC</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">ABS</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @if($genderCounts['F'] > 0)
                    <tr style="color: #000080;"><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">F</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['F']['I'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['F']['II'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['F']['III'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['F']['IV'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['F']['0'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $absIncStatsBySex['F']['INC'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $absIncStatsBySex['F']['ABS'] }}</td></tr>
                    @endif
                    @if($genderCounts['M'] > 0)
                    <tr style="color: #000080;"><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">M</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['M']['I'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['M']['II'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['M']['III'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['M']['IV'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $divisionStatsBySex['M']['0'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $absIncStatsBySex['M']['INC'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $absIncStatsBySex['M']['ABS'] }}</td></tr>
                    @endif
                    <tr style="background-color: LIGHTYELLOW; color: #000080;"><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">T</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['I'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['II'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['III'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['IV'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['0'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC'] }}</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'] }}</td></tr>
                </tbody>
            </table>
        </div>

        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0.25rem;">
            <table style="width: 100%; table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #4b5563;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 9%;">CNO</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">SEX</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">COMB</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 55%;">DETAILED SUBJECTS RESULT</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">TOTAL</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">AVG</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">GRD</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">AGGT</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">DIV</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">GPA</th>
                        <th style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.72rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">POS</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @php
                        $completeCandidates = $candidatesWithMetrics->filter(fn($d) => ($d['candidateStatus'] ?? 'COMPLETE') === 'COMPLETE');
                        $incCandidates = $candidatesWithMetrics->filter(fn($d) => ($d['candidateStatus'] ?? '') === 'INC');
                        $absCandidates = $candidatesWithMetrics->filter(fn($d) => ($d['candidateStatus'] ?? '') === 'ABS');
                        $positionCounter = 1;
                    @endphp

                    @forelse($completeCandidates as $data)
                        @php
                            $candidate = $data['candidate'];
                            $totalMarks = $data['totalMarks'];
                            $averageMarks = $data['average'];
                            $gpa = $data['gpa'];
                            $gpaDisplay = abs($gpa - round($gpa)) < 0.00005
                                ? number_format($gpa, 0)
                                : number_format($gpa, 4);
                            $division = $data['division'];
                            $totalPoints = $data['totalPoints'];
                            $subjectResultsStr = $data['subjectResultsStr'] ?: '-';
                            if ($averageMarks >= 80) $grd = 'A';
                            elseif ($averageMarks >= 70) $grd = 'B';
                            elseif ($averageMarks >= 60) $grd = 'C';
                            elseif ($averageMarks >= 50) $grd = 'D';
                            elseif ($averageMarks >= 45) $grd = 'E';
                            elseif ($averageMarks >= 35) $grd = 'S';
                            else $grd = 'F';
                        @endphp
                        <tr style="color: #000080; border: 1px solid #4b5563;">
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $displayCombination($candidate->combination) }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.70rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $subjectResultsStr }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ number_format($totalMarks, 0) }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ number_format($averageMarks, 2) }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $grd }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $totalPoints }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $division }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $gpaDisplay }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $positionCounter++ }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" style="border: 1px solid #4b5563; padding: 1rem; text-align: center; color: #999;">No results</td></tr>
                    @endforelse

                    @foreach($incCandidates as $data)
                        @php
                            $candidate = $data['candidate'];
                            $subjectResultsStr = $data['subjectResultsStr'] ?: 'INC';
                            $totalMarks = (float) ($data['totalMarks'] ?? 0);
                            $averageMarks = (float) ($data['average'] ?? 0);
                        @endphp
                        <tr style="color: #000080; border: 1px solid #4b5563;">
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $displayCombination($candidate->combination) }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.70rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $subjectResultsStr }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $totalMarks > 0 ? number_format($totalMarks, 0) : 'INC' }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $averageMarks > 0 ? number_format($averageMarks, 2) : 'INC' }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">-</td>
                        </tr>
                    @endforeach

                    @foreach($absCandidates as $data)
                        @php $candidate = $data['candidate']; @endphp
                        <tr style="color: #000080; border: 1px solid #4b5563;">
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">{{ $displayCombination($candidate->combination) }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.70rem;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #4b5563; padding: 0.18rem; font-size: 0.74rem; text-align: center;">ABS</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="background-color: #B0E0E6; padding: 0 1rem; margin-bottom: 0;">
            <table style="width: 100%; table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #4b5563;">
                <thead><tr style="background-color: #003366;"><th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="3">EXAMINATION CENTRE OVERALL PERFORMANCE</th></tr></thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    <tr><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem;" colspan="1">EXAMINATION CENTRE SCHOOL</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: left;" colspan="2">{{ $school->name }}</td></tr>
                    <tr><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem;" colspan="1">TOTAL REGISTERED CANDIDATES</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: left;" colspan="2">{{ $totalCandidates }}</td></tr>
                    <tr><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem;" colspan="1">TOTAL PASSED CANDIDATES</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: left;" colspan="2">{{ $totalPassed }}</td></tr>
                    <tr><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem;" colspan="1">TOTAL FAILED CANDIDATES</td><td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: left;" colspan="2">{{ $totalFailed }}</td></tr>
                    @php
                        $overallGpaBg = ($overallGpa > 0 && !empty($overallGpaInfo['color']))
                            ? (string) $overallGpaInfo['color']
                            : '#CCCCCC';
                        $overallGpaTextColor = in_array(strtoupper($overallGpaBg), ['#DEF043', '#1FEE0B'], true) ? '#000000' : '#FFFFFF';
                    @endphp
                    <tr>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem;" colspan="1">EXAMINATION CENTRE GPA</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: left; background-color: {{ $overallGpaBg }}; color: {{ $overallGpaTextColor }};">{{ abs($overallGpa - round($overallGpa)) < 0.00005 ? number_format($overallGpa, 0) : number_format($overallGpa, 4) }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: left; background-color: {{ $overallGpaBg }}; color: {{ $overallGpaTextColor }};">@if($overallGpa > 0)Grade {{ $overallGpaInfo['grade'] ?? '-' }} - {{ $overallGpaInfo['competence'] ?? '-' }}@else-@endif</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="background-color: #B0E0E6; padding: 0 1rem; margin-bottom: 0;">
            <table style="width: 100%; table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #4b5563;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="11">EXAMINATION CENTRE DIVISION PERFORMANCE</th>
                    </tr>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">REGIST</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">ABSENT</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">SAT</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">WITHHELD</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">INC</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">CLEAN</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV I</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV II</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV III</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV IV</th>
                        <th style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV 0</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    <tr>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalCandidates }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalAbsent }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalCandidates - $totalAbsent }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">0</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalInc }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ ($totalCandidates - $totalAbsent) - $totalInc }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['I'] }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['II'] }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['III'] }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['IV'] }}</td>
                        <td style="border: 1px solid #4b5563; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalDivisions['0'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="background-color: #B0E0E6; padding: 0 1rem; margin-bottom: 0;">
            <div style="border: 1px solid #4b5563; background-color: #003366; color: #FFFFFF; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left;">
                EXAMINATION CENTRE SUBJECTS PERFORMANCE
            </div>
            <table style="width: 100%; table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #4b5563;">
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 24%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 4.5%;">
                    <col style="width: 7%;">
                    <col style="width: 23%;">
                </colgroup>
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">CODE</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 24%;">SUBJECT NAME</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">A</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">B</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">C</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">D</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">E</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">S</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">F</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">ABS</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4.5%;">TOTAL</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 7%;">GPA</th>
                        <th style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.78rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 23%;">COMPETENCY LEVEL</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @forelse($subjectPerformance as $subjectId => $data)
                        @php
                            $gradeService = app(\App\Services\Results\NectaGradingService::class);
                            $total = $data['A'] + $data['B'] + $data['C'] + $data['D'] + $data['E'] + $data['S'] + $data['F'] + $data['ABS'];
                            $graded = $data['A'] + $data['B'] + $data['C'] + $data['D'] + $data['E'] + $data['S'] + $data['F'];
                            $subjectGpa = $graded > 0 ? round(($data['A']*1 + $data['B']*2 + $data['C']*3 + $data['D']*4 + $data['E']*5 + $data['S']*6 + $data['F']*7) / $graded, 4) : 0;
                            $competencyColor = '#f0f0f0';
                            $competencyText = '-';
                            if ($subjectGpa > 0) {
                                $gpaInfo = $gradeService->getGpaCompetence($subjectGpa);
                                $competencyColor = $gpaInfo['color'] ?? '#f0f0f0';
                                $competencyText = "Grade {$gpaInfo['grade']} ({$gpaInfo['competence']})";
                            }
                        @endphp
                        <tr>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['code'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: left; white-space: nowrap; overflow: hidden;">{{ $data['name'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['A'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['B'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['C'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['D'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['E'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['S'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['F'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $data['ABS'] }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ $total }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: center;">{{ abs($subjectGpa - round($subjectGpa)) < 0.00005 ? number_format($subjectGpa, 0) : number_format($subjectGpa, 4) }}</td>
                            <td style="border: 1px solid #4b5563; padding: 0.20rem; font-size: 0.76rem; text-align: left; white-space: nowrap; overflow: hidden; background-color: {{ $competencyColor }}; color: #000080;">{{ $competencyText }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="13" style="border: 1px solid #4b5563; padding: 1rem; text-align: center; color: #999;">No subject data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
    $size = 9;
    $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
    $pageWidth = $pdf->get_width();
    $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
    $x = ($pageWidth - $textWidth) / 2;
    $y = $pdf->get_height() - 40;
    $pdf->page_text($x, $y, $text, $font, $size, [0, 0, 0]);
}
</script>
</body>
</html>
