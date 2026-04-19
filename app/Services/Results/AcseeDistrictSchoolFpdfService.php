<?php

namespace App\Services\Results;

use Illuminate\Support\Collection;

class AcseeDistrictSchoolFpdfService
{
    public function __construct(
        protected NectaGradingService $gradingService
    ) {
    }

    public function generateSchoolPdf(
        Collection $schoolRows,
        string $outputPath,
        string $yearLabel,
        ?object $region,
        ?object $district,
        string $exportedBy
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/') );
        }
        require_once app_path('Support/Pdf/fpdf.php');

        $metrics = $this->buildSchoolMetrics($schoolRows, $yearLabel);
        $school = $metrics['school'];

        $pdf = new class($this, $metrics, $yearLabel, $region, $district, $exportedBy) extends \FPDF {
            public function __construct(
                private AcseeDistrictSchoolFpdfService $service,
                private array $metrics,
                private string $yearLabel,
                private ?object $region,
                private ?object $district,
                private string $exportedBy
            ) {
                parent::__construct('P', 'mm', 'A3');
                $this->SetMargins(8, 8, 8);
                $this->SetAutoPageBreak(true, 10);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                $school = $this->metrics['school'];
                $this->SetFillColor(176, 224, 230);
                $this->Rect(0, 0, 297, 420, 'F');

                if ($this->PageNo() > 1) {
                    $this->SetY(8);
                    $this->SetFont('Helvetica', 'B', 10);
                    $this->SetTextColor(30, 58, 138);
                    $this->Cell(0, 5, $this->service->text('OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION - ' . $this->yearLabel), 0, 1, 'C');
                    $this->Ln(3);
                    return;
                }

                $emblem = public_path('images/emblem.png');
                if (is_file($emblem)) {
                    $this->Image($emblem, 12, 10, 18, 18);
                    $this->Image($emblem, 267, 10, 18, 18);
                }

                $this->SetY(10);
                $this->SetFont('Helvetica', 'B', 11);
                $this->SetTextColor(30, 58, 138);
                $this->Cell(0, 5, $this->service->text("PRIME MINISTER'S OFFICE"), 0, 1, 'C');
                $this->Cell(0, 5, $this->service->text('REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT'), 0, 1, 'C');
                $this->Cell(0, 5, $this->service->text('TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, LINDI, MTWARA NA TABORA'), 0, 1, 'C');
                $this->Cell(0, 5, $this->service->text('OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION - ' . $this->yearLabel), 0, 1, 'C');
                $schoolLine = trim(($school?->code ?: 'N/A') . ' - ' . strtoupper((string) ($school?->name ?: 'UNKNOWN SCHOOL')));
                $this->Cell(0, 5, $this->service->text($schoolLine), 0, 1, 'C');
                $this->Ln(2);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(90, 90, 90);
                $this->SetFont('Helvetica', '', 7);
                $this->Cell(0, 4, $this->service->text('ACSEE JOINT ZONAL RESULTS, 2026'), 0, 0, 'L');
                $this->Cell(0, 4, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }

            public function contentBottomLimit(float $reserve = 0): float
            {
                return 420 - 10 - 8 - $reserve;
            }
        };

        $pdf->AddPage();
        $this->renderDivisionPerformanceSummary($pdf, $metrics);
        $this->renderCandidateResults($pdf, $metrics);
        $this->renderOverallPerformance($pdf, $metrics);
        $this->renderResultsSummary($pdf, $metrics);
        $this->renderSubjectPerformance($pdf, $metrics);

        $pdf->Output('F', $outputPath);
    }

    public function text(?string $value, int $limit = 0): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $ascii = $ascii !== false ? $ascii : $value;
        if ($limit > 0 && strlen($ascii) > $limit) {
            return rtrim(substr($ascii, 0, max(0, $limit - 3))) . '...';
        }
        return $ascii;
    }

    public function centerDescriptor(?string $schoolCode): string
    {
        $normalized = strtoupper(trim((string) $schoolCode));
        if (str_starts_with($normalized, 'P')) {
            return 'Private Candidates';
        }

        return 'School Candidates';
    }

    protected function buildSchoolMetrics(Collection $schoolRows, string $yearLabel): array
    {
        $school = $schoolRows->first()?->candidate?->school;
        $divisionMap = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '0' => '0'];
        $subjectAliases = (array) config('necta_subject_aliases.acsee', []);

        $subjectLabel = function ($subject) use ($subjectAliases): string {
            $code = trim((string) ($subject?->code ?? ''));
            if ($code !== '' && isset($subjectAliases[$code])) {
                return (string) $subjectAliases[$code];
            }
            return strtoupper((string) ($subject?->name ?? 'SUBJECT'));
        };

        $subjectFullName = function ($subject): string {
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

        $displayCombination = fn (?string $combination): string => ($c = trim((string) $combination)) === '' ? '-' : (strtoupper($c) === 'PMCS' ? 'PMCs' : $c);

        $subjectPerformance = [];

        $candidates = $schoolRows->map(function ($row) use ($divisionMap, $yearLabel, $subjectLabel, $subjectFullName, $requiredPaperCodesForSubject, $displayCombination, &$subjectPerformance) {
            $latestMarks = collect($row->subjectMarks ?? [])
                ->groupBy('subject_id')
                ->map(function ($subjectRows) use ($requiredPaperCodesForSubject) {
                    $rows = collect($subjectRows)->sortByDesc('id')->values();
                    $subject = $rows->first()?->subject;
                    $required = $requiredPaperCodesForSubject($subject);
                    $positiveByPaper = [];
                    foreach ($required as $paperCode) {
                        $positiveByPaper[$paperCode] = $rows->contains(function ($m) use ($paperCode) {
                            $v = $m->{$paperCode} ?? null;
                            return $v !== null && (float) $v > 0;
                        });
                    }

                    $preferred = $rows->first(function ($mark) use ($required, $positiveByPaper) {
                        $status = strtoupper((string) ($mark->subject_status ?? ''));
                        if ($status === 'INC') {
                            return false;
                        }
                        foreach ($required as $paperCode) {
                            $value = $mark->{$paperCode} ?? null;
                            if ($value === null) {
                                return false;
                            }
                            if (($positiveByPaper[$paperCode] ?? false) && (float) $value <= 0) {
                                return false;
                            }
                        }
                        return true;
                    });

                    return $preferred ?: $rows->first();
                })
                ->filter()
                ->values();

            $coreSubjectIds = collect($row->candidate?->subjectSelections ?? [])
                ->filter(function ($sel) use ($yearLabel) {
                    if (empty($sel->is_active) || empty($sel->is_principal)) {
                        return false;
                    }
                    return (string) ($sel->year ?? '') === (string) $yearLabel || empty($sel->year);
                })
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $principalLabelById = collect($row->candidate?->subjectSelections ?? [])
                ->filter(function ($sel) use ($yearLabel) {
                    if (empty($sel->is_active) || empty($sel->is_principal)) {
                        return false;
                    }
                    return (string) ($sel->year ?? '') === (string) $yearLabel || empty($sel->year);
                })
                ->mapWithKeys(function ($sel) use ($subjectLabel) {
                    return [(int) $sel->subject_id => $subjectLabel($sel->subject)];
                })
                ->all();

            $subjectParts = [];
            $aggtSubjects = [];
            $hasAnyMark = false;
            $hasIncomplete = false;
            $allAbsent = $latestMarks->isNotEmpty();

            foreach ($latestMarks as $mark) {
                $status = strtoupper((string) ($mark->subject_status ?? ''));
                if (!in_array($status, ['ABS', 'X'], true)) {
                    $allAbsent = false;
                }

                if ($mark->marks_obtained !== null || $mark->paper_1 !== null || $mark->paper_2 !== null || $mark->paper_3 !== null || $status === 'INC') {
                    $hasAnyMark = true;
                }

                $label = $subjectLabel($mark->subject);
                $subjectId = (int) ($mark->subject_id ?? 0);
                if ($subjectId > 0 && !isset($subjectPerformance[$subjectId])) {
                    $subjectPerformance[$subjectId] = [
                        'subject_id' => $subjectId,
                        'code' => (string) ($mark->subject?->code ?? ''),
                        'name' => $subjectFullName($mark->subject),
                        'A' => 0,
                        'B' => 0,
                        'C' => 0,
                        'D' => 0,
                        'E' => 0,
                        'S' => 0,
                        'F' => 0,
                        'ABS' => 0,
                        'grade_points_sum' => 0.0,
                        'graded_count' => 0,
                    ];
                }
                if (in_array($status, ['ABS', 'X'], true)) {
                    $subjectParts[] = $label . '=ABS';
                    if ($subjectId > 0) {
                        $subjectPerformance[$subjectId]['ABS']++;
                    }
                    continue;
                }

                if ($status === 'INC' || $mark->marks_obtained === null) {
                    $hasIncomplete = true;
                    $subjectParts[] = $label . '=INC';
                    continue;
                }

                $score = rtrim(rtrim(number_format((float) $mark->marks_obtained, 2, '.', ''), '0'), '.');
                $grade = $this->gradingService->calculateGrade((float) $mark->marks_obtained);
                $subjectParts[] = $label . '=' . $score . " '" . $grade . "'";

                if (
                    in_array($grade, ['A', 'B', 'C', 'D', 'E', 'S', 'F'], true)
                    && !$this->gradingService->isExcludedSubject((string) ($mark->subject?->name ?? ''))
                ) {
                    $aggtSubjects[] = [
                        'subject_id' => (int) ($mark->subject_id ?? 0),
                        'subject_name' => (string) ($mark->subject?->name ?? ''),
                        'grade' => $grade,
                        'points' => $this->gradingService->getGradePoints($grade),
                    ];
                }

                if ($subjectId > 0 && isset($subjectPerformance[$subjectId][$grade])) {
                    $subjectPerformance[$subjectId][$grade]++;
                    $subjectPerformance[$subjectId]['grade_points_sum'] += (float) $this->gradingService->getGradePoints($grade);
                    $subjectPerformance[$subjectId]['graded_count']++;
                }
            }

            $presentSubjectIds = $latestMarks
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $missingPrincipalIds = !empty($coreSubjectIds)
                ? array_values(array_diff($coreSubjectIds, $presentSubjectIds))
                : [];
            foreach ($missingPrincipalIds as $subjectId) {
                $hasIncomplete = true;
                $hasAnyMark = true;
                $subjectParts[] = ($principalLabelById[$subjectId] ?? ('SUBJECT ' . $subjectId)) . '=INC';
            }

            $status = 'COMPLETE';
            if (!$hasAnyMark && $latestMarks->isEmpty()) {
                $status = 'ABS';
            } elseif ($hasIncomplete) {
                $status = 'INC';
            } elseif ($allAbsent) {
                $status = 'ABS';
            }

            $effectiveCoreSubjectIds = !empty($coreSubjectIds)
                ? $coreSubjectIds
                : collect($aggtSubjects)->pluck('subject_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

            $aggt = $this->gradingService->calculateAggtFromSubjectGrades($aggtSubjects, $effectiveCoreSubjectIds);
            $passes = $this->gradingService->countPrincipalPassesFromSubjectGrades($aggtSubjects, $effectiveCoreSubjectIds);
            $divisionInfo = $this->gradingService->calculateDivisionWithEligibility((float) ($aggt ?? 0), $passes);
            $division = $divisionMap[(string) ($divisionInfo['division'] ?? '0')] ?? '0';

            $gpaRows = collect($aggtSubjects)
                ->filter(fn (array $subject) => in_array((int) ($subject['subject_id'] ?? 0), $effectiveCoreSubjectIds, true))
                ->sortBy('points')
                ->take(3)
                ->values();

            $gpa = ($gpaRows->isNotEmpty() && $status === 'COMPLETE')
                ? round((float) $gpaRows->avg('points'), 4)
                : 0.0;

            $avg = (float) ($row->total_percentage ?? 0);
            if ($avg >= 80) $overallGrade = 'A';
            elseif ($avg >= 70) $overallGrade = 'B';
            elseif ($avg >= 60) $overallGrade = 'C';
            elseif ($avg >= 50) $overallGrade = 'D';
            elseif ($avg >= 45) $overallGrade = 'E';
            elseif ($avg >= 35) $overallGrade = 'S';
            else $overallGrade = 'F';

            return [
                'candidate' => $row->candidate,
                'candidate_status' => $status,
                'sex' => strtoupper((string) ($row->candidate?->gender ?? '')),
                'combination' => $displayCombination((string) ($row->candidate?->combination ?? '')),
                'subject_results' => $allAbsent ? 'ABS' : (!empty($subjectParts) ? implode(', ', $subjectParts) : '-'),
                'total_marks' => (float) ($row->total_marks ?? 0),
                'average' => $avg,
                'overall_grade' => $status === 'COMPLETE' ? $overallGrade : $status,
                'aggt' => $status === 'COMPLETE' ? (float) ($aggt ?? 0) : null,
                'division' => $status === 'COMPLETE' ? $division : $status,
                'gpa' => $status === 'COMPLETE' ? $gpa : null,
                'gpa_points_sum' => (float) $gpaRows->sum('points'),
                'gpa_subject_count' => (int) $gpaRows->count(),
            ];
        })->values();

        $statusOrder = ['COMPLETE' => 0, 'INC' => 1, 'ABS' => 2];
        $candidates = $candidates->sort(function ($a, $b) use ($statusOrder) {
            $aStatus = $statusOrder[$a['candidate_status']] ?? 9;
            $bStatus = $statusOrder[$b['candidate_status']] ?? 9;
            if ($aStatus !== $bStatus) {
                return $aStatus <=> $bStatus;
            }
            if ($a['candidate_status'] !== 'COMPLETE') {
                return strcmp((string) ($a['candidate']?->candidate_id ?? ''), (string) ($b['candidate']?->candidate_id ?? ''));
            }
            $gpaCmp = ((float) ($a['gpa'] ?? 99)) <=> ((float) ($b['gpa'] ?? 99));
            if ($gpaCmp !== 0) {
                return $gpaCmp;
            }
            return strcmp((string) ($a['candidate']?->candidate_id ?? ''), (string) ($b['candidate']?->candidate_id ?? ''));
        })->values();

        $position = 1;
        $candidates = $candidates->map(function (array $row) use (&$position) {
            $row['position'] = $row['candidate_status'] === 'COMPLETE' ? $position++ : '-';
            return $row;
        });

        $genderCounts = ['F' => 0, 'M' => 0];
        $divisionStatsBySex = ['F' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0], 'M' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0]];
        $absIncStatsBySex = ['F' => ['ABS' => 0, 'INC' => 0], 'M' => ['ABS' => 0, 'INC' => 0]];
        $totalDivisions = ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0];

        foreach ($candidates as $candidate) {
            $sex = $candidate['sex'];
            if (!isset($genderCounts[$sex])) {
                continue;
            }
            $genderCounts[$sex]++;
            if ($candidate['candidate_status'] === 'ABS') {
                $absIncStatsBySex[$sex]['ABS']++;
            } elseif ($candidate['candidate_status'] === 'INC') {
                $absIncStatsBySex[$sex]['INC']++;
            } else {
                $div = $candidate['division'];
                if (!isset($totalDivisions[$div])) {
                    $div = '0';
                }
                $totalDivisions[$div]++;
                $divisionStatsBySex[$sex][$div]++;
            }
        }

        $overallGpaPoints = (float) $candidates->where('candidate_status', 'COMPLETE')->sum('gpa_points_sum');
        $overallGpaSubjects = (int) $candidates->where('candidate_status', 'COMPLETE')->sum('gpa_subject_count');
        $overallGpa = $overallGpaSubjects > 0 ? $overallGpaPoints / $overallGpaSubjects : 0.0;

        $subjectPerformance = collect($subjectPerformance)
            ->map(function (array $row) {
                $total = (int) ($row['A'] + $row['B'] + $row['C'] + $row['D'] + $row['E'] + $row['S'] + $row['F']);
                $gpa = ($row['graded_count'] ?? 0) > 0
                    ? round(((float) ($row['grade_points_sum'] ?? 0.0)) / (int) $row['graded_count'], 4)
                    : null;
                $competence = $gpa !== null ? $this->gradingService->getGpaCompetence((float) $gpa) : null;

                return [
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'A' => $row['A'],
                    'B' => $row['B'],
                    'C' => $row['C'],
                    'D' => $row['D'],
                    'E' => $row['E'],
                    'S' => $row['S'],
                    'F' => $row['F'],
                    'TOTAL' => $total,
                    'GPA' => $gpa,
                    'COMPETENCE' => $competence,
                ];
            })
            ->sortBy('code')
            ->values();

        $completeCandidates = $candidates->where('candidate_status', 'COMPLETE')->count();
        $operationalSummary = [
            'REGIST' => (int) $candidates->count(),
            'ABSENT' => (int) ($absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS']),
            'SAT' => (int) ($candidates->count() - ($absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'])),
            'WITHHELD' => 0,
            'INC' => (int) ($absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC']),
            'CLEAN' => (int) $completeCandidates,
            'DIV I' => (int) $totalDivisions['I'],
            'DIV II' => (int) $totalDivisions['II'],
            'DIV III' => (int) $totalDivisions['III'],
            'DIV IV' => (int) $totalDivisions['IV'],
            'DIV 0' => (int) $totalDivisions['0'],
        ];

        return [
            'school' => $school,
            'candidates' => $candidates,
            'gender_counts' => $genderCounts,
            'division_stats_by_sex' => $divisionStatsBySex,
            'abs_inc_stats_by_sex' => $absIncStatsBySex,
            'total_divisions' => $totalDivisions,
            'total_candidates' => $candidates->count(),
            'total_passed' => $totalDivisions['I'] + $totalDivisions['II'] + $totalDivisions['III'] + $totalDivisions['IV'],
            'total_failed' => $totalDivisions['0'],
            'total_inc' => $absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC'],
            'total_absent' => $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'],
            'overall_gpa' => $overallGpa,
            'overall_gpa_info' => $overallGpa > 0 ? $this->gradingService->getGpaCompetence((float) $overallGpa) : null,
            'operational_summary' => $operationalSummary,
            'subject_performance' => $subjectPerformance,
        ];
    }

    protected function renderResultsSummary(\FPDF $pdf, array $metrics): void
    {
        $summary = $metrics['operational_summary'] ?? [];
        if (empty($summary)) {
            return;
        }

        if ($pdf->GetY() + 22 > $pdf->contentBottomLimit()) {
            $pdf->AddPage();
        }

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(281, 8, 'EXAMINATION CENTRE DIVISION PERFORMANCE', 1, 1, 'L', true);

        $headers = array_keys($summary);
        $width = 281 / max(count($headers), 1);

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($headers as $header) {
            $pdf->Cell($width, 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($summary as $value) {
            $pdf->Cell($width, 7, (string) $value, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->Ln(2);
    }

    protected function renderSubjectPerformance(\FPDF $pdf, array $metrics): void
    {
        $rows = collect($metrics['subject_performance'] ?? []);
        if ($rows->isEmpty()) {
            return;
        }

        if ($pdf->GetY() + 70 > $pdf->contentBottomLimit()) {
            $pdf->AddPage();
        }

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(281, 8, 'EXAMINATION CENTRE SUBJECTS PERFORMANCE', 1, 1, 'L', true);

        $columns = [
            ['label' => 'CODE', 'w' => 14, 'align' => 'C'],
            ['label' => 'SUBJECT NAME', 'w' => 74, 'align' => 'L'],
            ['label' => 'A', 'w' => 12, 'align' => 'C'],
            ['label' => 'B', 'w' => 12, 'align' => 'C'],
            ['label' => 'C', 'w' => 12, 'align' => 'C'],
            ['label' => 'D', 'w' => 12, 'align' => 'C'],
            ['label' => 'E', 'w' => 12, 'align' => 'C'],
            ['label' => 'S', 'w' => 12, 'align' => 'C'],
            ['label' => 'F', 'w' => 12, 'align' => 'C'],
            ['label' => 'TOTAL', 'w' => 18, 'align' => 'C'],
            ['label' => 'GPA', 'w' => 22, 'align' => 'C'],
            ['label' => 'COMPETENCY LEVEL', 'w' => 69, 'align' => 'L'],
        ];

        foreach ($columns as $column) {
            $pdf->Cell($column['w'], 7, $column['label'], 1, 0, $column['align'], true);
        }
        $pdf->Ln();

        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(0, 0, 0);
        $baseFill = [255, 255, 224];

        foreach ($rows as $row) {
            if ($pdf->GetY() + 7 > $pdf->contentBottomLimit()) {
                $pdf->AddPage();
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetFillColor(0, 51, 102);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($columns as $column) {
                    $pdf->Cell($column['w'], 7, $column['label'], 1, 0, $column['align'], true);
                }
                $pdf->Ln();
                $pdf->SetFont('Helvetica', '', 7.5);
                $pdf->SetTextColor(0, 0, 0);
            }

            $competence = $row['COMPETENCE'] ?? null;
            $competenceText = $competence
                ? sprintf('Grade %s (%s)', $competence['grade'] ?? '-', $competence['competence'] ?? 'Unknown')
                : '-';
            $gpaText = $row['GPA'] !== null
                ? number_format((float) $row['GPA'], 4)
                : '-';

            $data = [
                [(string) $row['code'], $baseFill],
                [$this->text((string) $row['name'], 40), $baseFill],
                [(string) $row['A'], $baseFill],
                [(string) $row['B'], $baseFill],
                [(string) $row['C'], $baseFill],
                [(string) $row['D'], $baseFill],
                [(string) $row['E'], $baseFill],
                [(string) $row['S'], $baseFill],
                [(string) $row['F'], $baseFill],
                [(string) $row['TOTAL'], $baseFill],
                [$gpaText, $baseFill],
                [$this->text($competenceText, 36), $this->hexToRgb($competence['color'] ?? $competence['color_code'] ?? null, [255, 255, 224])],
            ];

            foreach ($columns as $idx => $column) {
                [$text, $fill] = $data[$idx];
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $pdf->Cell($column['w'], 7, $text, 1, 0, $column['align'], true);
            }
            $pdf->Ln();
        }

        $pdf->Ln(2);
    }

    protected function renderDivisionPerformanceSummary(\FPDF $pdf, array $metrics): void
    {
        if ($pdf->GetY() + 36 > $pdf->contentBottomLimit()) {
            $pdf->AddPage();
        }

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(281, 8, 'DIVISION PERFORMANCE SUMMARY', 1, 1, 'L', true);

        $headers = ['SEX', 'I', 'II', 'III', 'IV', '0', 'INC', 'ABS'];
        $equalWidth = 281 / count($headers);
        $widths = array_fill(0, count($headers), $equalWidth);

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($headers as $idx => $header) {
            $pdf->Cell($widths[$idx], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Helvetica', '', 8);
        foreach (['F', 'M'] as $sex) {
            if (($metrics['gender_counts'][$sex] ?? 0) <= 0) {
                continue;
            }

            $row = [
                $sex,
                $metrics['division_stats_by_sex'][$sex]['I'],
                $metrics['division_stats_by_sex'][$sex]['II'],
                $metrics['division_stats_by_sex'][$sex]['III'],
                $metrics['division_stats_by_sex'][$sex]['IV'],
                $metrics['division_stats_by_sex'][$sex]['0'],
                $metrics['abs_inc_stats_by_sex'][$sex]['INC'],
                $metrics['abs_inc_stats_by_sex'][$sex]['ABS'],
            ];

            foreach ($row as $idx => $value) {
                $pdf->Cell($widths[$idx], 7, (string) $value, 1, 0, 'C', true);
            }
            $pdf->Ln();
        }

        $totalRow = [
            'T',
            $metrics['total_divisions']['I'],
            $metrics['total_divisions']['II'],
            $metrics['total_divisions']['III'],
            $metrics['total_divisions']['IV'],
            $metrics['total_divisions']['0'],
            $metrics['total_inc'],
            $metrics['total_absent'],
        ];

        foreach ($totalRow as $idx => $value) {
            $pdf->Cell($widths[$idx], 7, (string) $value, 1, 0, 'C', true);
        }

        $pdf->Ln();
        $pdf->Ln(2);
    }

    protected function renderCandidateResults(\FPDF $pdf, array $metrics): void
    {
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);

        $columns = [
            ['label' => 'CNO', 'w' => 24, 'align' => 'C'],
            ['label' => 'SEX', 'w' => 12, 'align' => 'C'],
            ['label' => 'COMB', 'w' => 18, 'align' => 'C'],
            ['label' => 'DETAILED SUBJECTS RESULT', 'w' => 126, 'align' => 'L'],
            ['label' => 'TOTAL', 'w' => 16, 'align' => 'C'],
            ['label' => 'AVG', 'w' => 16, 'align' => 'C'],
            ['label' => 'GRD', 'w' => 12, 'align' => 'C'],
            ['label' => 'AGGT', 'w' => 14, 'align' => 'C'],
            ['label' => 'DIV', 'w' => 12, 'align' => 'C'],
            ['label' => 'GPA', 'w' => 16, 'align' => 'C'],
            ['label' => 'POS', 'w' => 15, 'align' => 'C'],
        ];

        foreach ($columns as $column) {
            $pdf->Cell($column['w'], 7, $column['label'], 1, 0, $column['align'], true);
        }
        $pdf->Ln();

        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 128);
        $pdf->SetFont('Helvetica', '', 7);
        $rowHeight = 6;

        foreach ($metrics['candidates'] as $candidate) {
            if ($pdf->GetY() + $rowHeight > $pdf->contentBottomLimit()) {
                $pdf->AddPage();
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetFillColor(0, 51, 102);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($columns as $column) {
                    $pdf->Cell($column['w'], 7, $column['label'], 1, 0, $column['align'], true);
                }
                $pdf->Ln();
                $pdf->SetFillColor(255, 255, 224);
                $pdf->SetTextColor(0, 0, 128);
                $pdf->SetFont('Helvetica', '', 7);
            }

            $avg = $candidate['candidate_status'] === 'COMPLETE'
                ? number_format((float) $candidate['average'], 2)
                : $candidate['candidate_status'];
            $gpa = $candidate['candidate_status'] === 'COMPLETE'
                ? (abs(((float) $candidate['gpa']) - round((float) $candidate['gpa'])) < 0.00005 ? number_format((float) $candidate['gpa'], 0) : number_format((float) $candidate['gpa'], 4))
                : $candidate['candidate_status'];

            $row = [
                $this->text((string) ($candidate['candidate']?->candidate_id ?? ''), 18),
                $candidate['sex'],
                $this->text((string) $candidate['combination'], 8),
                $this->text((string) $candidate['subject_results'], 110),
                $candidate['candidate_status'] === 'COMPLETE' ? number_format((float) $candidate['total_marks'], 0) : $candidate['candidate_status'],
                $avg,
                $candidate['overall_grade'],
                $candidate['candidate_status'] === 'COMPLETE' ? (string) ((int) round((float) ($candidate['aggt'] ?? 0))) : $candidate['candidate_status'],
                (string) $candidate['division'],
                $gpa,
                (string) $candidate['position'],
            ];

            foreach ($columns as $idx => $column) {
                $pdf->Cell($column['w'], $rowHeight, $row[$idx], 1, 0, $column['align'], true);
            }
            $pdf->Ln();
        }

        $pdf->Ln(2);
    }

    protected function renderOverallPerformance(\FPDF $pdf, array $metrics): void
    {
        $school = $metrics['school'];
        $gpaInfo = $metrics['overall_gpa_info'];

        $sectionHeight = $gpaInfo ? 36 : 29;
        if ($pdf->GetY() + $sectionHeight > $pdf->contentBottomLimit()) {
            $pdf->AddPage();
        }

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(281, 8, 'EXAMINATION CENTRE OVERALL PERFORMANCE', 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 0);
        $columnWidth = 281 / 2;
        $rows = [
            ['EXAMINATION CENTRE REGION', (string) ($school?->region?->name ?? $this->region?->name ?? '-')],
            ['TOTAL PASSED CANDIDATES', (string) $metrics['total_passed']],
            [
                'EXAMINATION CENTRE GPA',
                $metrics['overall_gpa'] > 0
                    ? (abs($metrics['overall_gpa'] - round($metrics['overall_gpa'])) < 0.00005
                        ? number_format($metrics['overall_gpa'], 0)
                        : number_format($metrics['overall_gpa'], 4))
                    : '-',
            ],
        ];

        foreach ($rows as $row) {
            $pdf->Cell($columnWidth, 7, $this->text($row[0]), 1, 0, 'L', true);
            $pdf->Cell($columnWidth, 7, $this->text($row[1]), 1, 1, 'L', true);
        }

        if ($gpaInfo) {
            $pdf->Cell($columnWidth, 7, 'GPA COMPETENCE', 1, 0, 'L', true);
            $fill = $this->hexToRgb($gpaInfo['color'] ?? $gpaInfo['color_code'] ?? null, [255, 255, 224]);
            $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
            $pdf->Cell($columnWidth, 7, $this->text('Grade ' . ($gpaInfo['grade'] ?? '-') . ' (' . ($gpaInfo['competence'] ?? '-') . ')'), 1, 1, 'L', true);
        }

        $pdf->Ln(2);
    }

    protected function hexToRgb(?string $hex, array $fallback = [255, 255, 224]): array
    {
        $value = strtoupper(trim((string) $hex));
        $value = ltrim($value, '#');
        if ($value === '' || !in_array(strlen($value), [3, 6], true)) {
            return $fallback;
        }

        if (strlen($value) === 3) {
            $value = preg_replace('/(.)/', '$1$1', $value);
        }

        if (!preg_match('/^[A-F0-9]{6}$/', $value)) {
            return $fallback;
        }

        return [
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2)),
        ];
    }
}
