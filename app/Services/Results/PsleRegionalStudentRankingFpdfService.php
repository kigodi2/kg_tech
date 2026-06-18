<?php

namespace App\Services\Results;

class PsleRegionalStudentRankingFpdfService
{
    public const PAGE_WIDTH = 297.0;
    public const PAGE_HEIGHT = 210.0;
    public const LEFT_MARGIN = 8.0;
    public const RIGHT_MARGIN = 8.0;
    public const CONTENT_WIDTH = self::PAGE_WIDTH - self::LEFT_MARGIN - self::RIGHT_MARGIN;

    public function generate(
        object $region,
        int $examYearValue,
        array $rows,
        string $outputPath,
        string $reportLabel
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $generatedAt = date('d-m-Y H:i:s');
        $node = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', (string) (gethostname() ?: 'NODE')));
        $node = $node !== '' ? substr($node, 0, 8) : 'NODE';
        $regionCode = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', (string) $region->name));
        $regionCode = $regionCode !== '' ? substr($regionCode, 0, 3) : 'REG';
        $barcodePayload = sprintf('PSLE-%s-%s-%s', $regionCode, date('Ymd-His'), $node);

        $pdf = new class($this, $region, $examYearValue, $reportLabel, $generatedAt, $node, $barcodePayload) extends \FPDF {
            public function __construct(
                private PsleRegionalStudentRankingFpdfService $service,
                private object $region,
                private int $examYearValue,
                private string $reportLabel,
                private string $generatedAt,
                private string $node,
                private string $barcodePayload
            ) {
                parent::__construct('L', 'mm', 'A4');
                $this->SetMargins(8, 10, 8);
                $this->SetAutoPageBreak(true, 10);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                $this->SetFillColor(248, 250, 252);
                $this->Rect(0, 0, PsleRegionalStudentRankingFpdfService::PAGE_WIDTH, PsleRegionalStudentRankingFpdfService::PAGE_HEIGHT, 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, PsleRegionalStudentRankingFpdfService::PAGE_WIDTH, 4, 'F');

                $emblem = public_path('images/emblem.png');
                if (is_file($emblem)) {
                    $this->Image($emblem, 8, 9, 13, 13);
                    $this->Image($emblem, 276, 9, 13, 13);
                }

                $titleBlockX = 28;
                $titleBlockWidth = 241;

                $this->SetXY($titleBlockX, 9.5);
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 9.5);
                $this->Cell($titleBlockWidth, 4.2, $this->service->text("PRIME MINISTER'S OFFICE"), 0, 1, 'C');
                $this->SetTextColor(30, 64, 175);
                $this->SetX($titleBlockX);
                $this->Cell($titleBlockWidth, 4.2, $this->service->text('REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT'), 0, 1, 'C');
                $this->SetTextColor(71, 85, 105);
                $this->SetFont('Helvetica', '', 7.5);
                $this->SetX($titleBlockX);
                $this->Cell($titleBlockWidth, 3.2, $this->service->text('ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)'), 0, 1, 'C');
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 10.5);
                $this->SetX($titleBlockX);
                $this->Cell(
                    $titleBlockWidth,
                    4.6,
                    $this->service->text('STANDARD SEVEN ZONAL JOINT MOCK EVALUATION RESULTS - MAY, ' . $this->examYearValue),
                    0,
                    1,
                    'C'
                );
                $this->SetFont('Helvetica', 'B', 10.1);
                $this->SetX($titleBlockX);
                $this->Cell(
                    $titleBlockWidth,
                    4.4,
                    $this->service->text(strtoupper((string) $this->region->name) . ' - ' . strtoupper(trim((string) $this->reportLabel))),
                    0,
                    1,
                    'C'
                );

                $this->Ln(0.5);
                $this->SetX(8);
                $this->SetDrawColor(191, 219, 254);
                $this->SetFillColor(219, 234, 254);
                $this->SetTextColor(30, 64, 175);
                $this->SetFont('Helvetica', 'B', 6.7);
                $banner = 'PROFESSIONAL PSLE STUDENT RANKING REPORT';
                $this->Cell(PsleRegionalStudentRankingFpdfService::CONTENT_WIDTH, 4.6, $banner, 0, 1, 'C', true);
                $this->Ln(1.0);
                $this->SetFillColor(0, 166, 81);
                $this->Rect(8, 34.2, 84.78, 0.5, 'F');
                $this->SetFillColor(245, 208, 0);
                $this->Rect(92.78, 34.2, 70.89, 0.5, 'F');
                $this->SetFillColor(0, 0, 0);
                $this->Rect(163.67, 34.2, 52.86, 0.5, 'F');
                $this->SetFillColor(11, 47, 91);
                $this->Rect(216.53, 34.2, 72.47, 0.5, 'F');
            }

            public function Footer(): void
            {
                $this->SetTextColor(71, 85, 105);
                $this->SetFont('Helvetica', '', 6.2);
                $this->SetXY(8, 191.0);
                $this->Cell(PsleRegionalStudentRankingFpdfService::CONTENT_WIDTH, 3.2, 'GENERATED: ' . $this->generatedAt . ' | IRMS NODE: ' . $this->node, 0, 1, 'R');

                $this->SetFillColor(0, 166, 81);
                $this->Rect(8, 203.5, 84.78, 0.5, 'F');
                $this->SetFillColor(245, 208, 0);
                $this->Rect(92.78, 203.5, 70.89, 0.5, 'F');
                $this->SetFillColor(0, 0, 0);
                $this->Rect(163.67, 203.5, 52.86, 0.5, 'F');
                $this->SetFillColor(11, 47, 91);
                $this->Rect(216.53, 203.5, 72.47, 0.5, 'F');
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->SetY(204.4);
                $this->Cell(0, 3.6, 'STANDARD SEVEN ZONAL MOCK - STUDENT RANKING REPORT, ' . $this->examYearValue, 0, 0, 'L');
                $this->Cell(0, 3.6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderSummary($pdf, $region, $rows);
        $this->renderTable($pdf, $rows, $reportLabel);
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
        $width = 0.0;
        foreach (str_split('*' . strtoupper($value) . '*') as $char) {
            $width += ($narrow * 6) + ($wide * 3) + $narrow;
        }
        return $width;
    }

    public function text(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $ascii !== false ? $ascii : $value;
    }

    private function renderSummary(\FPDF $pdf, object $region, array $rows): void
    {
        $candidateCount = count($rows);
        $femaleCount = count(array_filter($rows, fn ($row) => strtoupper((string) ($row['gender'] ?? '')) === 'F'));
        $maleCount = count(array_filter($rows, fn ($row) => strtoupper((string) ($row['gender'] ?? '')) === 'M'));
        $totals = array_values(array_filter(array_map(fn ($row) => $row['total_marks'] ?? null, $rows), fn ($v) => !is_null($v)));
        $gpas = array_values(array_filter(array_map(fn ($row) => $row['gpa'] ?? null, $rows), fn ($v) => !is_null($v)));
        $averageTotal = !empty($totals) ? (array_sum($totals) / count($totals)) : null;
        $averageGpa = !empty($gpas) ? (array_sum($gpas) / count($gpas)) : null;
        $averageTotalBadge = $this->totalAverageBadge($averageTotal);
        $averageGpaBadge = $this->gpaAverageBadge($averageGpa);
        $best = $rows[0] ?? null;
        $least = !empty($rows) ? $rows[array_key_last($rows)] : null;

        $startY = 35.8;
        $pdf->SetXY(8, $startY);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 7.2);
        $pdf->SetXY(8, $startY + 3.2);
        $pdf->Cell(self::CONTENT_WIDTH, 4.1, 'REGION: ' . strtoupper((string) $region->name), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 4.1, 'TOTAL CANDIDATES LISTED: ' . $candidateCount . ' (F: ' . $femaleCount . ', M: ' . $maleCount . ')', 0, 1, 'L');
        $pdf->SetX(8);
        $averageTotalText = 'AVERAGE TOTAL: ' . (is_null($averageTotal) ? '-' : number_format((float) $averageTotal, 2));
        $pdf->Cell($pdf->GetStringWidth($averageTotalText), 3.9, $averageTotalText, 0, 0, 'L');
        $pdf->Cell(1.5, 3.9, '', 0, 0);
        if ($averageTotalBadge) {
            $this->drawInlineBadge($pdf, $averageTotalBadge);
        }
        $averageGpaText = ' | AVERAGE GPA: ' . (is_null($averageGpa) ? '-' : number_format((float) $averageGpa, 4));
        $pdf->Cell($pdf->GetStringWidth($averageGpaText), 3.9, $averageGpaText, 0, 0, 'L');
        $pdf->Cell(1.5, 3.9, '', 0, 0);
        if ($averageGpaBadge) {
            $this->drawInlineBadge($pdf, $averageGpaBadge);
        }
        $pdf->Ln(3.9);
        if ($best) {
            $pdf->Cell(self::CONTENT_WIDTH, 3.9, 'BEST CANDIDATE: ' . $this->text((string) ($best['index_number'] ?? '-')) . ' (TOTAL: ' . (is_null($best['total_marks'] ?? null) ? '-' : number_format((float) $best['total_marks'], 0)) . ', GRD: ' . $this->text((string) ($best['overall_grade'] ?? '-')) . ', POS: ' . ($best['position'] ?? '-') . ')', 0, 1, 'L');
        }
        if ($least) {
            $pdf->Cell(self::CONTENT_WIDTH, 3.9, 'LEAST CANDIDATE: ' . $this->text((string) ($least['index_number'] ?? '-')) . ' (TOTAL: ' . (is_null($least['total_marks'] ?? null) ? '-' : number_format((float) $least['total_marks'], 0)) . ', GRD: ' . $this->text((string) ($least['overall_grade'] ?? '-')) . ', POS: ' . ($least['position'] ?? '-') . ')', 0, 1, 'L');
        }
        $pdf->Ln(1.2);
    }

    private function totalAverageBadge(?float $average): ?array
    {
        if (is_null($average)) {
            return null;
        }

        $grade = match (true) {
            $average >= 246 => 'A',
            $average >= 186 => 'B',
            $average >= 126 => 'C',
            $average >= 66 => 'D',
            default => 'E',
        };

        return $this->gradeBadge($grade);
    }

    private function gpaAverageBadge(?float $averageGpa): ?array
    {
        if (is_null($averageGpa)) {
            return null;
        }

        $grade = match (true) {
            $averageGpa <= 1.5 => 'A',
            $averageGpa <= 2.5 => 'B',
            $averageGpa <= 3.5 => 'C',
            $averageGpa <= 4.5 => 'D',
            default => 'E',
        };

        return $this->gradeBadge($grade);
    }

    private function gradeBadge(string $grade): array
    {
        $competence = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Satisfactory',
            'E' => 'Unsatisfactory',
        ][$grade] ?? 'Unsatisfactory';

        $color = match ($grade) {
            'A' => [0, 168, 42],
            'B' => [31, 238, 11],
            'C' => [222, 240, 67],
            'D' => [255, 119, 47],
            default => [255, 39, 47],
        };

        return [
            'grade' => $grade,
            'competence' => $competence,
            'color' => $color,
        ];
    }

    private function drawInlineBadge(\FPDF $pdf, array $badge): void
    {
        $label = 'Grade ' . $badge['grade'] . ' (' . $badge['competence'] . ')';
        $width = max(18.0, $pdf->GetStringWidth($label) + 1.6);
        [$r, $g, $b] = $badge['color'];
        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetTextColor(0, 0, 128);
        $pdf->Cell($width, 3.5, $label, 0, 0, 'C', true);
        $pdf->SetTextColor(8, 39, 109);
    }

    private function fitCell(\FPDF $pdf, float $w, float $h, string $text, string $align = 'L', bool $fill = true, float $base = 6.4, string $style = ''): void
    {
        $text = $this->text($text);
        $font = $base;
        $pdf->SetFont('Helvetica', $style, $font);
        while ($font > 4.0 && $pdf->GetStringWidth($text) > ($w - 1.0)) {
            $font -= 0.2;
            $pdf->SetFont('Helvetica', $style, $font);
        }
        $pdf->Cell($w, $h, $text, 1, 0, $align, $fill);
        $pdf->SetFont('Helvetica', '', $base);
    }

    private function renderTable(\FPDF $pdf, array $rows, string $reportLabel): void
    {
        $tableStartX = 8.0;
        $tableWidth = self::CONTENT_WIDTH;

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect($tableStartX, $pdf->GetY(), $tableWidth, 7, 'DF');
        $pdf->SetXY($tableStartX + 2.0, $pdf->GetY() + 1.2);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 6.6);
        $pdf->Cell($tableWidth - 4, 3.2, strtoupper($this->text($reportLabel)), 0, 1, 'L');

        $widths = [
            'council' => 20,
            'cno' => 22,
            'school' => 64,
            'sex' => 10,
            'detail' => 119,
            'total' => 12,
            'grd' => 10,
            'aggt' => 12,
            'pos' => 8,
        ];

        $baseTableWidth = array_sum($widths);
        if ($baseTableWidth > 0) {
            $scale = $tableWidth / $baseTableWidth;
            foreach ($widths as $key => $width) {
                $widths[$key] = round($width * $scale, 3);
            }

            $scaledWidth = array_sum($widths);
            $remainder = round($tableWidth - $scaledWidth, 3);
            if (abs($remainder) > 0.001) {
                $widths['detail'] += $remainder;
            }
        }

        $pdf->SetFillColor(244, 241, 177);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 5.8);
        $pdf->SetX($tableStartX);
        $pdf->Cell($widths['council'], 5.2, 'COUNCIL', 1, 0, 'L', true);
        $pdf->Cell($widths['cno'], 5.2, 'CNO', 1, 0, 'C', true);
        $pdf->Cell($widths['school'], 5.2, 'SCHOOL', 1, 0, 'L', true);
        $pdf->Cell($widths['sex'], 5.2, 'SEX', 1, 0, 'C', true);
        $pdf->Cell($widths['detail'], 5.2, 'DETAILED SUBJECTS RESULT', 1, 0, 'L', true);
        $pdf->Cell($widths['total'], 5.2, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell($widths['grd'], 5.2, 'GRD', 1, 0, 'C', true);
        $pdf->Cell($widths['aggt'], 5.2, 'AGGT', 1, 0, 'C', true);
        $pdf->Cell($widths['pos'], 5.2, 'POS', 1, 1, 'C', true);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($rows as $row) {
            $pdf->SetX($tableStartX);
            $this->fitCell($pdf, $widths['council'], 4.8, (string) ($row['council'] ?? '-'), 'L', true, 5.7);
            $this->fitCell($pdf, $widths['cno'], 4.8, (string) ($row['index_number'] ?? '-'), 'C', true, 5.7);
            $this->fitCell($pdf, $widths['school'], 4.8, (string) ($row['school'] ?? '-'), 'L', true, 5.5);
            $this->fitCell($pdf, $widths['sex'], 4.8, (string) ($row['gender'] ?? '-'), 'C', true, 5.7);
            $this->fitCell($pdf, $widths['detail'], 4.8, (string) (($row['subject_results_text'] ?? '') !== '' ? $row['subject_results_text'] : '-'), 'L', true, 5.4);
            $this->fitCell($pdf, $widths['total'], 4.8, is_null($row['total_marks'] ?? null) ? '-' : number_format((float) $row['total_marks'], 0), 'C', true, 5.7);
            $this->fitCell($pdf, $widths['grd'], 4.8, (string) ($row['overall_grade'] ?? '-'), 'C', true, 5.7);
            $this->fitCell($pdf, $widths['aggt'], 4.8, is_null($row['aggt'] ?? null) ? '-' : (string) $row['aggt'], 'C', true, 5.7);
            $this->fitCell($pdf, $widths['pos'], 4.8, (string) ($row['position'] ?? '-'), 'C', true, 5.7);
            $pdf->Ln();
        }
    }
}
