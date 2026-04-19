<?php

namespace App\Services\Results;

use App\Models\ExamType;
use App\Models\GradingProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PsleDistrictSchoolFpdfService
{
    public const PAGE_WIDTH = 297.0;
    public const PAGE_HEIGHT = 420.0;
    public const LEFT_MARGIN = 6.0;
    public const RIGHT_MARGIN = 6.0;
    public const CONTENT_WIDTH = self::PAGE_WIDTH - self::LEFT_MARGIN - self::RIGHT_MARGIN;

    public function generateSchoolPdf(
        Collection $schoolResults,
        string $outputPath,
        string $yearLabel,
        ?object $region,
        ?object $district,
        string $exportedBy
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }
        require_once app_path('Support/Pdf/fpdf.php');

        $metrics = $this->buildSchoolMetrics($schoolResults, (int) $yearLabel);
        $generatedAt = date('d-m-Y H:i:s');
        $node = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', (string) (gethostname() ?: 'NODE')));
        $node = $node !== '' ? substr($node, 0, 8) : 'NODE';
        $schoolCode = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', (string) ($metrics['school']?->code ?? 'SCH')));
        $schoolCode = $schoolCode !== '' ? substr($schoolCode, 0, 8) : 'SCH';
        $barcodePayload = sprintf('PSLE-%s-%s-%s', $schoolCode, date('Ymd-His'), $node);

        $pdf = new class($this, $metrics, $yearLabel, $region, $district, $exportedBy, $generatedAt, $node, $barcodePayload) extends \FPDF {
            private ?int $lastPageNumber = null;

            public function __construct(
                private PsleDistrictSchoolFpdfService $service,
                private array $metrics,
                private string $yearLabel,
                private ?object $region,
                private ?object $district,
                private string $exportedBy,
                private string $generatedAt,
                private string $node,
                private string $barcodePayload
            ) {
                parent::__construct('P', 'mm', 'A3');
                $this->SetMargins(6, 8, 6);
                $this->SetAutoPageBreak(true, 8);
                $this->AliasNbPages();
            }

            public function setLastPageNumber(int $pageNumber): void
            {
                $this->lastPageNumber = $pageNumber;
            }

            public function Header(): void
            {
                $this->SetFillColor(176, 224, 230);
                $this->Rect(0, 0, $this->GetPageWidth(), $this->GetPageHeight(), 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, $this->GetPageWidth(), 4, 'F');

                if ($this->PageNo() > 1) {
                    return;
                }

                $this->service->renderPdfHeader($this, $this->metrics, $this->yearLabel);
            }

            public function Footer(): void
            {
                if ($this->lastPageNumber !== null && $this->PageNo() === $this->lastPageNumber) {
                    $this->SetTextColor(71, 85, 105);
                    $this->SetFont('Helvetica', '', 6.2);
                    $this->SetXY(6, 403.0);
                    $this->Cell(PsleDistrictSchoolFpdfService::CONTENT_WIDTH, 3.2, $this->service->text('GENERATED: ' . $this->generatedAt . ' | IRMS NODE: ' . $this->node), 0, 1, 'R');

                    $barcodeWidth = $this->service->code39Width($this->barcodePayload, 0.22);
                    $barcodeX = 6 + ((PsleDistrictSchoolFpdfService::CONTENT_WIDTH - $barcodeWidth) / 2);
                    $this->service->drawCode39($this, $barcodeX, 406.0, $this->barcodePayload, 0.22, 2.8);

                    $this->SetXY(6, 409.3);
                    $this->SetFont('Helvetica', '', 5.8);
                    $this->Cell(PsleDistrictSchoolFpdfService::CONTENT_WIDTH, 2.8, $this->barcodePayload, 0, 0, 'C');
                }

                $stripLeft = 6.0;
                $stripTop = 413.5;
                $stripTotal = PsleDistrictSchoolFpdfService::CONTENT_WIDTH;
                $segments = [
                    [[0, 166, 81], 0.30],
                    [[245, 208, 0], 0.24],
                    [[0, 0, 0], 0.16],
                    [[11, 47, 91], 0.30],
                ];

                $currentX = $stripLeft;
                foreach ($segments as [$rgb, $ratio]) {
                    $width = $stripTotal * $ratio;
                    $this->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
                    $this->Rect($currentX, $stripTop, $width, 0.5, 'F');
                    $currentX += $width;
                }

                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->SetXY(6, 414.4);
                $this->Cell(0, 3.6, $this->service->text('RESULTS FOR STANDARD SEVEN ZONAL JOINT MOCK EXAMINATION - ' . $this->yearLabel), 0, 0, 'L');
                $this->Cell(0, 3.6, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderOverview($pdf, $metrics);
        $this->renderGradePerformance($pdf, $metrics);
        $this->renderCandidates($pdf, $metrics);
        $this->renderSubjectPerformance($pdf, $metrics);
        $pdf->setLastPageNumber($pdf->PageNo());
        $pdf->Output('F', $outputPath);
    }

    public function drawCode39(\FPDF $pdf, float $x, float $y, string $value, float $narrow = 0.22, float $height = 2.8): void
    {
        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];

        $code = '*' . strtoupper($value) . '*';
        $pdf->SetFillColor(15, 23, 42);

        foreach (str_split($code) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];
            foreach (str_split($pattern) as $index => $bar) {
                $lineWidth = $bar === 'w' ? $narrow * 2.5 : $narrow;
                if ($index % 2 === 0) {
                    $pdf->Rect($x, $y, $lineWidth, $height, 'F');
                }
                $x += $lineWidth;
            }
            $x += $narrow;
        }
    }

    public function code39Width(string $value, float $narrow = 0.22): float
    {
        $wide = $narrow * 2.5;
        $code = '*' . strtoupper($value) . '*';
        $width = 0.0;

        foreach (str_split($code) as $char) {
            $width += ($narrow * 6) + ($wide * 3) + $narrow;
        }

        return $width;
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

    protected function buildSchoolMetrics(Collection $schoolResults, int $examYear): array
    {
        $school = $schoolResults->first()?->candidate?->school;
        $subjectStats = [];

        $candidates = $schoolResults->map(function ($row) use (&$subjectStats) {
            $marks = collect($row->subjectMarks ?? [])
                ->sortBy(fn ($mark) => $this->subjectOrder((string) ($mark->subject?->code ?? '')))
                ->values();

            $subjectRows = $marks->map(function ($mark) use (&$subjectStats) {
                $rawCode = strtoupper((string) ($mark->subject?->code ?? ''));
                $displayCode = preg_replace('/^PSLE-/', '', $rawCode);
                $detailLabel = $this->detailedSubjectLabel($rawCode);
                $fullName = $this->fullSubjectName($rawCode, (string) ($mark->subject?->name ?? $rawCode));
                $marksObtained = (float) ($mark->marks_obtained ?? 0);
                $maxMarks = (float) ($mark->max_marks ?: 100);
                $score50 = $this->scaledScore50($marksObtained, $maxMarks);
                $grade = strtoupper((string) ($mark->grade ?? $this->gradeFromScore50($score50)));

                if (!isset($subjectStats[$rawCode])) {
                    $subjectStats[$rawCode] = [
                        'code' => $displayCode,
                        'raw_code' => $rawCode,
                        'name' => strtoupper($fullName),
                        'registered' => 0,
                        'sat' => 0,
                        'abs' => 0,
                        'A' => 0,
                        'B' => 0,
                        'C' => 0,
                        'D' => 0,
                        'E' => 0,
                        'a_to_c' => 0,
                        'a_to_d' => 0,
                        'avg_total' => 0.0,
                    ];
                }

                $subjectStats[$rawCode]['registered']++;
                $subjectStats[$rawCode]['sat']++;
                $subjectStats[$rawCode][$grade] = ($subjectStats[$rawCode][$grade] ?? 0) + 1;
                $subjectStats[$rawCode]['a_to_c'] += in_array($grade, ['A', 'B', 'C'], true) ? 1 : 0;
                $subjectStats[$rawCode]['a_to_d'] += in_array($grade, ['A', 'B', 'C', 'D'], true) ? 1 : 0;
                $subjectStats[$rawCode]['avg_total'] += $score50;

                return [
                    'detail_label' => $detailLabel,
                    'subject_name' => strtoupper($fullName),
                    'score_50' => $score50,
                    'grade' => $grade,
                ];
            })->values();

            $subjectCount = $subjectRows->count();
            $total = round($subjectRows->sum('score_50'), 4);
            $avgScore = $subjectCount > 0 ? round($total / $subjectCount, 4) : 0.0;
            $averageGrade = $this->gradeFromScore50($avgScore);
            $aggregate = (int) $subjectRows->sum(fn (array $subject) => $this->gradePointFromGrade($subject['grade']));
            $gpa = $subjectCount > 0 ? round($aggregate / $subjectCount, 4) : 0.0;

            return [
                'candidate_no' => (string) ($row->candidate?->candidate_id ?? '-'),
                'prem_no' => (string) ($row->candidate?->prem_no ?? '-'),
                'sex' => strtoupper((string) ($row->candidate?->gender ?? '-')),
                'subject_rows' => $subjectRows->all(),
                'total_score' => $total,
                'average_grade' => $averageGrade,
                'aggregate_points' => $aggregate,
                'gpa' => $gpa,
            ];
        })->sortBy('candidate_no')->values();

        $positions = [];
        $sortedByMerit = $candidates->sortBy([
            ['total_score', 'desc'],
            ['candidate_no', 'asc'],
        ])->values();
        foreach ($sortedByMerit as $index => $candidate) {
            $positions[$candidate['candidate_no']] = $index + 1;
        }

        $candidates = $candidates->map(function (array $candidate) use ($positions) {
            $candidate['position'] = $positions[$candidate['candidate_no']] ?? null;
            $candidate['subject_line'] = collect($candidate['subject_rows'])
                ->map(fn (array $subject) => sprintf(
                    "%s - %s '%s'",
                    $subject['detail_label'],
                    number_format((float) $subject['score_50'], 0),
                    $subject['grade']
                ))
                ->implode(', ');

            return $candidate;
        })->values();

        $sexSummary = [
            'F' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
            'M' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0],
        ];
        foreach ($candidates as $candidate) {
            $sex = $candidate['sex'];
            if (isset($sexSummary[$sex][$candidate['average_grade']])) {
                $sexSummary[$sex][$candidate['average_grade']]++;
            }
        }

        $totals = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INC' => 0, 'ABS' => 0];
        foreach (['F', 'M'] as $sex) {
            foreach (array_keys($totals) as $key) {
                $totals[$key] += $sexSummary[$sex][$key];
            }
        }

        $subjectStats = collect($subjectStats)
            ->map(function (array $subject) {
                $avg = $subject['sat'] > 0 ? round($subject['avg_total'] / $subject['sat'], 4) : 0.0;
                $grd = $this->gradeFromScore50($avg);

                return array_merge($subject, [
                    'average_score' => $avg,
                    'grade' => $grd,
                    'competence_meta' => $this->gradeMeta($grd),
                ]);
            })
            ->sortBy(fn (array $subject) => $this->subjectOrder($subject['raw_code']))
            ->values()
            ->all();

        $candidateCount = $candidates->count();
        $registeredCandidateCount = 0;
        $registeredByGender = ['F' => 0, 'M' => 0];
        if ($school?->id) {
            $registeredCandidateCount = (int) DB::table('candidates')
                ->where('school_id', $school->id)
                ->where('exam_type', 'PSLE')
                ->count();

            $registeredGenderRows = DB::table('candidates')
                ->where('school_id', $school->id)
                ->where('exam_type', 'PSLE')
                ->selectRaw('upper(coalesce(gender, "")) as gender_key, count(*) as total')
                ->groupBy('gender_key')
                ->pluck('total', 'gender_key');

            $registeredByGender = [
                'F' => (int) ($registeredGenderRows['F'] ?? 0),
                'M' => (int) ($registeredGenderRows['M'] ?? 0),
            ];
        }
        $schoolAverage = $candidateCount > 0 ? round($candidates->sum('total_score') / $candidateCount, 4) : 0.0;
        $schoolAverageGrade = $this->gradeFromScore50($schoolAverage / 6);
        $topCandidate = $sortedByMerit->first();
        $passRateAC = $candidateCount > 0 ? round(($candidates->whereIn('average_grade', ['A', 'B', 'C'])->count() / $candidateCount) * 100, 2) : 0.0;
        $passRateAD = $candidateCount > 0 ? round(($candidates->whereIn('average_grade', ['A', 'B', 'C', 'D'])->count() / $candidateCount) * 100, 2) : 0.0;
        [$districtPosition, $districtSchoolsWithResults] = $this->schoolPositionByScope($examYear, 'district', (int) ($school?->district_id ?? 0), (int) ($school?->id ?? 0));
        [$regionalPosition, $regionalSchoolsWithResults] = $this->schoolPositionByScope($examYear, 'region', (int) ($school?->region_id ?? 0), (int) ($school?->id ?? 0));

        return [
            'school' => $school,
            'candidates' => $candidates->all(),
            'sex_summary' => $sexSummary,
            'totals' => $totals,
            'subject_stats' => $subjectStats,
            'candidate_count' => $candidateCount,
            'registered_candidate_count' => $registeredCandidateCount,
            'registered_by_gender' => $registeredByGender,
            'school_average' => $schoolAverage,
            'school_average_meta' => $this->gradeMeta($schoolAverageGrade),
            'pass_rate_ac' => $passRateAC,
            'pass_rate_ad' => $passRateAD,
            'top_candidate' => $topCandidate,
            'district_position' => $districtPosition,
            'district_schools_with_results' => $districtSchoolsWithResults,
            'regional_position' => $regionalPosition,
            'regional_schools_with_results' => $regionalSchoolsWithResults,
        ];
    }

    public function renderPdfHeader(\FPDF $pdf, array $metrics, string $yearLabel): void
    {
        $school = $metrics['school'];
        $emblem = public_path('images/emblem.png');
        if (is_file($emblem)) {
            $pdf->Image($emblem, 8, 8, 16);
            $pdf->Image($emblem, 273, 8, 16);
        }

        $pdf->SetTextColor(0, 0, 128);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $this->text("PRIME MINISTER'S OFFICE"), 0, 1, 'C');
        $pdf->Cell(0, 6, $this->text('REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT'), 0, 1, 'C');
        $pdf->Cell(0, 6, $this->text('TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA'), 0, 1, 'C');
        $pdf->Cell(0, 6, $this->text('OVERALL RESULTS FOR STANDARD SEVEN ZONAL JOINT MOCK EXAMINATION - MAY, ' . $yearLabel), 0, 1, 'C');
        $pdf->Cell(0, 6, $this->text(strtoupper((string) ($school?->code ?? '-')) . ' - ' . strtoupper((string) ($school?->name ?? 'UNKNOWN SCHOOL'))), 0, 1, 'C');

        $left = 6.0;
        $top = 41.5;
        $total = 285.0;
        $height = 0.7;
        $segments = [
            [[0, 166, 81], 0.30],
            [[245, 208, 0], 0.24],
            [[0, 0, 0], 0.16],
            [[11, 47, 91], 0.30],
        ];

        $currentX = $left;
        foreach ($segments as [$rgb, $ratio]) {
            $width = $total * $ratio;
            $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
            $pdf->Rect($currentX, $top, $width, $height, 'F');
            $currentX += $width;
        }

        $pdf->Ln(8);
    }

    protected function renderOverview(\FPDF $pdf, array $metrics): void
    {
        $registered = $metrics['registered_candidate_count'];
        $sat = $metrics['candidate_count'];
        $girlsRegistered = $metrics['registered_by_gender']['F'] ?? 0;
        $boysRegistered = $metrics['registered_by_gender']['M'] ?? 0;
        $girlsSat = array_sum($metrics['sex_summary']['F']);
        $boysSat = array_sum($metrics['sex_summary']['M']);

        $pdf->SetTextColor(0, 0, 128);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 6.5, $this->text('EXAMINATION CENTRE: ' . strtoupper((string) ($metrics['school']?->name ?? 'UNKNOWN SCHOOL')) . ' - ' . strtoupper((string) ($metrics['school']?->code ?? '-'))), 0, 1, 'L');
        $pdf->Cell(0, 6.5, $this->text(sprintf('CANDIDATES SAT : %d OUT OF %d REGISTERED CANDIDATES (F: %d/%d, M: %d/%d)', $sat, $registered, $girlsSat, $girlsRegistered, $boysSat, $boysRegistered)), 0, 1, 'L');

        $schoolAverage = $metrics['school_average'];
        $avgText = abs($schoolAverage - round($schoolAverage)) < 0.00005 ? number_format($schoolAverage, 0) : number_format($schoolAverage, 4);
        $labelWidth = $pdf->GetStringWidth($this->text('SCHOOL AVERAGE : ' . $avgText)) + 1.5;
        $pdf->Cell($labelWidth, 6.5, $this->text('SCHOOL AVERAGE : ' . $avgText), 0, 0, 'L');
        $meta = $metrics['school_average_meta'];
        $rgb = $meta['rgb'] ?? [255, 255, 224];
        $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->Cell(42, 6.5, $this->text($meta['label'] ?? '-'), 0, 1, 'L', true);

        $pdf->SetFillColor(255, 255, 224);
        $pdf->Cell(0, 6.5, $this->text(sprintf('PASS RATE (A-C): %.2f%% | PASS RATE (A-D): %.2f%%', $metrics['pass_rate_ac'], $metrics['pass_rate_ad'])), 0, 1, 'L');
        if (!empty($metrics['district_position']) && !empty($metrics['district_schools_with_results'])) {
            $pdf->Cell(0, 6.5, $this->text(sprintf('DISTRICT POSITION: %d OUT OF %d SCHOOLS WITH RESULTS', $metrics['district_position'], $metrics['district_schools_with_results'])), 0, 1, 'L');
        }
        if (!empty($metrics['regional_position']) && !empty($metrics['regional_schools_with_results'])) {
            $pdf->Cell(0, 6.5, $this->text(sprintf('REGIONAL POSITION: %d OUT OF %d SCHOOLS WITH RESULTS', $metrics['regional_position'], $metrics['regional_schools_with_results'])), 0, 1, 'L');
        }
        if (!empty($metrics['top_candidate'])) {
            $top = $metrics['top_candidate'];
            $pdf->Cell(0, 6.5, $this->text(sprintf('TOP CANDIDATE: %s (TOTAL: %s, GRD: %s)', strtoupper((string) $top['candidate_no']), number_format((float) $top['total_score'], 0), strtoupper((string) $top['average_grade']))), 0, 1, 'L');
        }
    }

    protected function renderGradePerformance(\FPDF $pdf, array $metrics): void
    {
        $this->ensureSpace($pdf, 36);
        $this->tableTitle($pdf, 'EXAMINATION CENTRE GRADE PERFORMANCE', 12);
        $columns = ['SEX', 'REGIST', 'SAT', 'WITHHELD', 'CLEAN', 'A', 'B', 'C', 'D', 'E', 'INC', 'ABS'];
        $width = 23.75;
        $rowHeight = 6.6;
        $widths = array_fill(0, count($columns), $width);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8);
        foreach ($columns as $index => $column) {
            $pdf->Cell($widths[$index], $rowHeight, $this->text($column, 32), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 128);

        $rows = [
            ['F', $metrics['registered_by_gender']['F'] ?? 0, array_sum($metrics['sex_summary']['F']), 0, max(array_sum($metrics['sex_summary']['F']) - ($metrics['sex_summary']['F']['INC'] ?? 0), 0), $metrics['sex_summary']['F']['A'], $metrics['sex_summary']['F']['B'], $metrics['sex_summary']['F']['C'], $metrics['sex_summary']['F']['D'], $metrics['sex_summary']['F']['E'], $metrics['sex_summary']['F']['INC'], $metrics['sex_summary']['F']['ABS']],
            ['M', $metrics['registered_by_gender']['M'] ?? 0, array_sum($metrics['sex_summary']['M']), 0, max(array_sum($metrics['sex_summary']['M']) - ($metrics['sex_summary']['M']['INC'] ?? 0), 0), $metrics['sex_summary']['M']['A'], $metrics['sex_summary']['M']['B'], $metrics['sex_summary']['M']['C'], $metrics['sex_summary']['M']['D'], $metrics['sex_summary']['M']['E'], $metrics['sex_summary']['M']['INC'], $metrics['sex_summary']['M']['ABS']],
            ['T', $metrics['registered_candidate_count'], $metrics['candidate_count'], 0, max($metrics['candidate_count'] - ($metrics['totals']['INC'] ?? 0), 0), $metrics['totals']['A'], $metrics['totals']['B'], $metrics['totals']['C'], $metrics['totals']['D'], $metrics['totals']['E'], $metrics['totals']['INC'], $metrics['totals']['ABS']],
        ];

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($rows as $row) {
            foreach ($row as $index => $cell) {
                $pdf->Cell($widths[$index], $rowHeight, $this->text((string) $cell), 1, 0, 'C', true);
            }
            $pdf->Ln();
        }
    }

    protected function renderCandidates(\FPDF $pdf, array $metrics): void
    {
        $pdf->Ln(4);
        $columns = ['CAND. NO', 'PREM NO', 'SEX', 'DETAILED SUBJECTS RESULT', 'TOTAL', 'GRD', 'AGGT', 'GPA', 'POS'];
        $widths = [24, 28, 12, 162, 12, 11, 11, 15, 10];
        $rowHeight = 6.4;
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8);
        foreach ($columns as $index => $column) {
            $align = $index === 3 ? 'L' : 'C';
            $pdf->Cell($widths[$index], $rowHeight, $this->text($column, 32), 1, 0, $align, true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 128);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(0, 0, 128);
        $pdf->SetFillColor(255, 255, 224);

        foreach ($metrics['candidates'] as $candidate) {
            $this->ensureSpace($pdf, $rowHeight, function () use ($pdf, $columns, $widths, $rowHeight) {
                $pdf->SetFillColor(0, 51, 102);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Helvetica', 'B', 8);
                foreach ($columns as $index => $column) {
                    $align = $index === 3 ? 'L' : 'C';
                    $pdf->Cell($widths[$index], $rowHeight, $this->text($column, 32), 1, 0, $align, true);
                }
                $pdf->Ln();
                $pdf->SetTextColor(0, 0, 128);
                $pdf->SetFillColor(255, 255, 224);
            });

            $pdf->Cell($widths[0], $rowHeight, $this->text($candidate['candidate_no'], 18), 1, 0, 'L', true);
            $pdf->Cell($widths[1], $rowHeight, $this->text($candidate['prem_no'], 20), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $rowHeight, $this->text($candidate['sex'], 2), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $rowHeight, $this->text($candidate['subject_line'], 128), 1, 0, 'L', true);
            $pdf->Cell($widths[4], $rowHeight, number_format((float) $candidate['total_score'], 0), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $rowHeight, strtoupper((string) $candidate['average_grade']), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $rowHeight, (string) $candidate['aggregate_points'], 1, 0, 'C', true);
            $pdf->Cell($widths[7], $rowHeight, number_format((float) $candidate['gpa'], 4), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $rowHeight, (string) $candidate['position'], 1, 1, 'C', true);
        }
    }

    protected function renderSubjectPerformance(\FPDF $pdf, array $metrics): void
    {
        if (empty($metrics['subject_stats'])) {
            return;
        }

        $pdf->Ln(4);
        $this->ensureSpace($pdf, 18);
        $this->tableTitle($pdf, 'EXAMINATION CENTRE SUBJECTS PERFORMANCE', 15);
        $columns = ['CODE', 'SUBJECT NAME', 'REGIST', 'SAT', 'ABS', 'A', 'B', 'C', 'A - C', 'D', 'A - D', 'E', 'AVG', 'GRD', 'COMPETENCE LEVEL'];
        $widths = [13, 76, 12, 12, 12, 11, 11, 11, 12, 11, 12, 11, 11, 11, 59];
        $rowHeight = 6.6;
        $this->tableHeader($pdf, $columns, $widths, $rowHeight);

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($metrics['subject_stats'] as $subject) {
            $this->ensureSpace($pdf, $rowHeight, function () use ($pdf, $columns, $widths, $rowHeight) {
                $this->tableTitle($pdf, 'EXAMINATION CENTRE SUBJECTS PERFORMANCE', 15);
                $this->tableHeader($pdf, $columns, $widths, $rowHeight);
                $pdf->SetFillColor(255, 255, 224);
            });

            $pdf->SetFillColor(255, 255, 224);
            $pdf->Cell($widths[0], $rowHeight, $this->text($subject['code'], 4), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $rowHeight, $this->text($subject['name'], 36), 1, 0, 'L', true);
            $pdf->Cell($widths[2], $rowHeight, (string) $subject['registered'], 1, 0, 'C', true);
            $pdf->Cell($widths[3], $rowHeight, (string) $subject['sat'], 1, 0, 'C', true);
            $pdf->Cell($widths[4], $rowHeight, (string) $subject['abs'], 1, 0, 'C', true);
            $pdf->Cell($widths[5], $rowHeight, (string) $subject['A'], 1, 0, 'C', true);
            $pdf->Cell($widths[6], $rowHeight, (string) $subject['B'], 1, 0, 'C', true);
            $pdf->Cell($widths[7], $rowHeight, (string) $subject['C'], 1, 0, 'C', true);
            $pdf->Cell($widths[8], $rowHeight, (string) $subject['a_to_c'], 1, 0, 'C', true);
            $pdf->Cell($widths[9], $rowHeight, (string) $subject['D'], 1, 0, 'C', true);
            $pdf->Cell($widths[10], $rowHeight, (string) $subject['a_to_d'], 1, 0, 'C', true);
            $pdf->Cell($widths[11], $rowHeight, (string) $subject['E'], 1, 0, 'C', true);
            $pdf->Cell($widths[12], $rowHeight, number_format((float) $subject['average_score'], 0), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $rowHeight, strtoupper((string) $subject['grade']), 1, 0, 'C', true);
            $fill = $subject['competence_meta']['rgb'] ?? [255, 255, 224];
            $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
            $pdf->Cell($widths[14], $rowHeight, $this->text($subject['competence_meta']['label'] ?? '-', 32), 1, 1, 'L', true);
        }
    }

    protected function ensureSpace(\FPDF $pdf, float $neededHeight, ?callable $afterBreak = null): void
    {
        $limitY = $pdf->GetPageHeight() - 14;
        if ($pdf->GetY() + $neededHeight <= $limitY) {
            return;
        }

        $pdf->AddPage();
        if ($afterBreak) {
            $afterBreak();
        }
    }

    protected function tableTitle(\FPDF $pdf, string $title, int $colspan): void
    {
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 8, $this->text($title), 1, 1, 'L', true);
    }

    protected function tableHeader(\FPDF $pdf, array $columns, array $widths, float $rowHeight = 8.0): void
    {
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8);
        foreach ($columns as $index => $column) {
            $align = $index === 1 || str_contains($column, 'RESULT') || str_contains($column, 'LEVEL') ? 'L' : 'C';
            $pdf->Cell($widths[$index], $rowHeight, $this->text($column, 32), 1, 0, $align, true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 128);
    }

    protected function detailedSubjectLabel(string $code): string
    {
        return match (strtoupper(trim($code))) {
            'PSLE-01' => 'KISWAHILI',
            'PSLE-02' => 'ENGLISH',
            'PSLE-03' => 'SOCIAL',
            'PSLE-04' => 'MATHEMATICS',
            'PSLE-05' => 'SCIENCE',
            'PSLE-06' => 'CIVIC',
            default => strtoupper(trim($code)),
        };
    }

    protected function fullSubjectName(string $code, string $name): string
    {
        return match (strtoupper(trim($code))) {
            'PSLE-01' => 'KISWAHILI',
            'PSLE-02' => 'ENGLISH LANGUAGE',
            'PSLE-03' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
            'PSLE-04' => 'MATHEMATICS',
            'PSLE-05' => 'SCIENCE AND TECHNOLOGY',
            'PSLE-06' => 'CIVIC AND MORAL EDUCATION',
            default => strtoupper(trim($name)),
        };
    }

    protected function subjectOrder(string $code): int
    {
        return match (strtoupper(trim($code))) {
            'PSLE-01' => 1,
            'PSLE-02' => 2,
            'PSLE-03' => 3,
            'PSLE-04' => 4,
            'PSLE-05' => 5,
            'PSLE-06' => 6,
            default => 99,
        };
    }

    protected function schoolPositionByScope(int $examYear, string $scope, int $scopeId, int $schoolId): array
    {
        if ($scopeId <= 0 || $schoolId <= 0) {
            return [null, 0];
        }

        $query = DB::table('subject_marks as sm')
            ->join('candidates as c', 'c.id', '=', 'sm.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->where('sm.exam_type_id', $this->psleExamTypeId())
            ->where('sm.year', $examYear)
            ->where('s.education_level', 'PRIMARY');

        if ($scope === 'region') {
            $query->where('s.region_id', $scopeId);
        } else {
            $query->where('s.district_id', $scopeId);
        }

        $rows = $query
            ->select([
                's.id as school_id',
                'c.id as candidate_pk',
                'sm.marks_obtained',
                'sm.max_marks',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [null, 0];
        }

        $rankedSchoolIds = $rows
            ->groupBy('school_id')
            ->map(function (Collection $schoolRows, $currentSchoolId) {
                $candidateTotals = $schoolRows
                    ->groupBy('candidate_pk')
                    ->map(function (Collection $candidateRows) {
                        return round($candidateRows->sum(function ($row) {
                            return $this->scaledScore50((float) $row->marks_obtained, (float) ($row->max_marks ?: 100));
                        }), 4);
                    })
                    ->values();

                $candidateCount = $candidateTotals->count();
                if ($candidateCount === 0) {
                    return null;
                }

                return [
                    'school_id' => (int) $currentSchoolId,
                    'average' => round($candidateTotals->sum() / $candidateCount, 4),
                ];
            })
            ->filter()
            ->sortBy([
                ['average', 'desc'],
                ['school_id', 'asc'],
            ])
            ->values();

        $position = null;
        foreach ($rankedSchoolIds as $index => $schoolRow) {
            if ((int) $schoolRow['school_id'] === $schoolId) {
                $position = $index + 1;
                break;
            }
        }

        return [$position, $rankedSchoolIds->count()];
    }

    protected function psleExamTypeId(): int
    {
        static $id = null;

        if ($id === null) {
            $id = (int) ExamType::query()->where('code', 'PSLE')->value('id');
        }

        return $id;
    }

    protected function scaledScore50(float $marksObtained, float $maxMarks): float
    {
        if ($maxMarks <= 0) {
            return 0.0;
        }

        return round(($marksObtained / $maxMarks) * 50, 4);
    }

    protected function gradeFromScore50(float $score): string
    {
        return match (true) {
            $score >= 41 => 'A',
            $score >= 31 => 'B',
            $score >= 21 => 'C',
            $score >= 11 => 'D',
            default => 'E',
        };
    }

    protected function gradePointFromGrade(string $grade): int
    {
        return match (strtoupper($grade)) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            default => 5,
        };
    }

    protected function gradeMeta(string $grade): array
    {
        $competenceLabels = $this->psleCompetenceLabel();
        $meta = [
            'A' => ['label' => "Grade A ({$competenceLabels['A']})", 'rgb' => [0, 168, 42]],
            'B' => ['label' => "Grade B ({$competenceLabels['B']})", 'rgb' => [31, 238, 11]],
            'C' => ['label' => "Grade C ({$competenceLabels['C']})", 'rgb' => [222, 240, 67]],
            'D' => ['label' => "Grade D ({$competenceLabels['D']})", 'rgb' => [255, 119, 47]],
            'E' => ['label' => "Grade E ({$competenceLabels['E']})", 'rgb' => [255, 39, 47]],
        ];

        return $meta[strtoupper($grade)] ?? $meta['E'];
    }

    protected function psleCompetenceLabel(): array
    {
        static $labels = null;

        if ($labels === null) {
            $labels = [
                'A' => 'Excellent',
                'B' => 'Very Good',
                'C' => 'Good',
                'D' => 'Satisfactory',
                'E' => 'Unsatisfactory',
            ];

            $profile = GradingProfile::query()
                ->where('code', 'like', 'PSLE-%')
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            $rules = collect(data_get($profile?->competence_levels, 'rules', []));
            if ($rules->isNotEmpty()) {
                foreach (['A', 'B', 'C', 'D', 'E'] as $gradeKey) {
                    $gpa = $this->gradePointFromGrade($gradeKey);
                    $match = $rules->first(function (array $rule) use ($gpa) {
                        return (float) ($rule['min_value'] ?? -INF) <= $gpa
                            && (float) ($rule['max_value'] ?? INF) >= $gpa
                            && !($rule['is_disabled'] ?? false);
                    });

                    if (!empty($match['level_label'])) {
                        $labels[$gradeKey] = (string) $match['level_label'];
                    }
                }
            }
        }

        return $labels;
    }
}
