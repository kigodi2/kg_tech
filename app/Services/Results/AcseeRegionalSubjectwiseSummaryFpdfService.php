<?php

namespace App\Services\Results;

class AcseeRegionalSubjectwiseSummaryFpdfService
{
    public function footerLabel(?string $reportLabel, string $fallback): string
    {
        $label = strtolower(trim((string) $reportLabel));
        if ($label === '') {
            return $fallback;
        }

        $label = preg_replace('/\s+/', ' ', $label) ?? $label;
        $label = preg_replace('/\bevaluation\b/', '', $label) ?? $label;
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;
        $label = trim($label, " -\t\n\r\0\x0B");

        return 'ACSEE regional ' . ($label !== '' ? $label . ' evaluation export' : 'evaluation export');
    }

    public function generate(
        object $region,
        int $examYearValue,
        array $rows,
        array $summary,
        string $outputPath,
        ?string $reportLabel = null
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $pdf = new class($this, $region, $examYearValue, $reportLabel) extends \FPDF {
            public function __construct(
                private AcseeRegionalSubjectwiseSummaryFpdfService $service,
                private object $region,
                private int $examYearValue,
                private ?string $reportLabel
            ) {
                parent::__construct('L', 'mm', 'A4');
                $this->SetMargins(10, 10, 10);
                $this->SetAutoPageBreak(true, 12);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                $this->SetFillColor(248, 250, 252);
                $this->Rect(0, 0, 297, 210, 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, 297, 4, 'F');

                $emblem = public_path('images/emblem.png');
                if (is_file($emblem)) {
                    $this->Image($emblem, 10, 9, 12, 12);
                    $this->Image($emblem, 275, 9, 12, 12);
                }

                $titleBlockX = 28;
                $titleBlockWidth = 241;
                $this->SetXY($titleBlockX, 9);
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 9);
                $this->Cell($titleBlockWidth, 4, $this->service->text("PRIME MINISTER'S OFFICE"), 0, 1, 'C');
                $this->SetTextColor(30, 64, 175);
                $this->SetX($titleBlockX);
                $this->Cell($titleBlockWidth, 4, $this->service->text('REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT'), 0, 1, 'C');
                $this->SetTextColor(71, 85, 105);
                $this->SetFont('Helvetica', '', 6.8);
                $this->SetX($titleBlockX);
                $this->Cell($titleBlockWidth, 3.2, $this->service->text('TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA'), 0, 1, 'C');
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 9.6);
                $this->SetX($titleBlockX);
                $suffix = $this->reportLabel ? ' - ' . strtoupper(trim((string) $this->reportLabel)) : '';
                $this->Cell($titleBlockWidth, 4.5, $this->service->text('FORM SIX ZONAL JOINT MOCK EVALUATION RESULTS - FEBRUARY, ' . $this->examYearValue . ' - ' . strtoupper((string) $this->region->name) . $suffix), 0, 1, 'C');

                $this->Ln(1);
                $this->SetX(10);
                $this->SetFillColor(219, 234, 254);
                $this->SetTextColor(30, 64, 175);
                $this->SetFont('Helvetica', 'B', 6.8);
                $banner = $this->reportLabel
                    ? 'PROFESSIONAL ' . strtoupper(trim((string) $this->reportLabel)) . ' PERFORMANCE REPORT'
                    : 'PROFESSIONAL SUBJECTWISE PERFORMANCE REPORT';
                $this->Cell(277, 5, $this->service->text($banner), 0, 1, 'C', true);
                $this->Ln(1);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->Cell(0, 4, $this->service->text($this->service->footerLabel($this->reportLabel, 'ACSEE regional subjectwise evaluation export')), 0, 0, 'L');
                $this->Cell(0, 4, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderSummary($pdf, $summary, $reportLabel);
        $this->renderTable($pdf, $rows);
        $pdf->Output('F', $outputPath);
    }

    public function text(?string $value, int $limit = 0): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $ascii = $ascii !== false ? $ascii : $value;

        if ($limit > 0 && strlen($ascii) > $limit) {
            return rtrim(substr($ascii, 0, max(0, $limit - 3))) . '...';
        }

        return $ascii;
    }

    private function renderSummary(\FPDF $pdf, array $summary, ?string $reportLabel): void
    {
        $overall = $summary['overall'] ?? [];
        $gpaInfo = $overall['gpa_info'] ?? null;

        $pdf->SetX(10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(10, $pdf->GetY(), 277, 10, 'DF');
        $pdf->SetXY(14, $pdf->GetY() + 2);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 8);
        $label = $reportLabel ? 'DETAILED ' . strtoupper(trim((string) $reportLabel)) . ' TABLE' : 'DETAILED SUBJECTWISE TABLE';
        $pdf->Cell(200, 4, $this->text($label), 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(277, 8, 'DIVISION PERFORMANCE SUMMARY', 1, 1, 'L', true);

        $headers = ['SEX', 'I', 'II', 'III', 'IV', '0', 'INC', 'ABS'];
        $width = 277 / count($headers);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 128);
        foreach ($headers as $header) {
            $pdf->Cell($width, 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('Helvetica', '', 8);
        foreach (['F', 'M', 'T'] as $sex) {
            $row = $summary['division_summary'][$sex] ?? ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0, 'INC' => 0, 'ABS' => 0];
            foreach ([$sex, $row['I'], $row['II'], $row['III'], $row['IV'], $row['0'], $row['INC'], $row['ABS']] as $value) {
                $pdf->Cell($width, 7, (string) $value, 1, 0, 'C', true);
            }
            $pdf->Ln();
        }

        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(277, 8, 'EXAMINATION CENTRE OVERALL PERFORMANCE', 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 224);
        $pdf->SetTextColor(0, 0, 0);
        $colWidth = 138.5;
        $pdf->Cell($colWidth, 7, 'EXAMINATION CENTRE REGION', 1, 0, 'L', true);
        $pdf->Cell($colWidth, 7, $this->text((string) ($overall['region'] ?? '-')), 1, 1, 'L', true);
        $pdf->Cell($colWidth, 7, 'TOTAL PASSED CANDIDATES', 1, 0, 'L', true);
        $pdf->Cell($colWidth, 7, (string) ($overall['passed'] ?? 0), 1, 1, 'L', true);
        $pdf->Cell($colWidth, 7, 'EXAMINATION CENTRE GPA', 1, 0, 'L', true);
        $pdf->Cell($colWidth, 7, !empty($overall['gpa']) ? number_format((float) $overall['gpa'], 4) : '-', 1, 1, 'L', true);
        $pdf->Cell($colWidth, 7, 'GPA COMPETENCE', 1, 0, 'L', true);
        $fill = $this->hexToRgb($gpaInfo['color'] ?? $gpaInfo['color_code'] ?? null, [255, 255, 224]);
        $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $pdf->Cell($colWidth, 7, $gpaInfo ? $this->text('Grade ' . ($gpaInfo['grade'] ?? '-') . ' (' . ($gpaInfo['competence'] ?? '-') . ')') : '-', 1, 1, 'L', true);
        $pdf->Ln(2);
    }

    private function renderTable(\FPDF $pdf, array $rows): void
    {
        $startX = 10;
        $widths = [14, 74, 12, 12, 12, 12, 12, 12, 12, 18, 22, 67];
        $headers = ['CODE', 'SUBJECT NAME', 'A', 'B', 'C', 'D', 'E', 'S', 'F', 'TOTAL', 'GPA', 'COMPETENCY LEVEL'];

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(277, 8, 'EXAMINATION CENTRE SUBJECTS PERFORMANCE', 1, 1, 'L', true);

        $pdf->SetX($startX);
        $pdf->SetFillColor(244, 241, 177);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->SetLineWidth(0.24);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 8.2);
        foreach ($headers as $index => $header) {
            $pdf->Cell($widths[$index], 9, $header, 1, 0, $index === 1 ? 'L' : 'C', true);
        }
        $pdf->Ln();

        foreach ($rows as $index => $row) {
            $pdf->SetX($startX);
            $baseFill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $competenceFill = $this->hexToRgb($row['competence']['color'] ?? $row['competence']['color_code'] ?? null, [255, 255, 224]);
            $pdf->SetFillColor(...$baseFill);
            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 8.2);

            $cells = [
                $row['code'] ?? '-',
                $row['name'] ?? '-',
                $row['grade_a'] ?? 0,
                $row['grade_b'] ?? 0,
                $row['grade_c'] ?? 0,
                $row['grade_d'] ?? 0,
                $row['grade_e'] ?? 0,
                $row['grade_s'] ?? 0,
                $row['grade_f'] ?? 0,
                $row['total'] ?? 0,
                is_null($row['gpa'] ?? null) ? '-' : number_format((float) $row['gpa'], 4),
                isset($row['competence']) ? 'Grade ' . (($row['competence']['grade'] ?? '-')) . ' (' . (($row['competence']['competence'] ?? '-')) . ')' : '-',
            ];

            foreach ($widths as $cellIndex => $width) {
                $align = in_array($cellIndex, [1, 11], true) ? 'L' : 'C';
                $fill = $cellIndex === 11 ? $competenceFill : $baseFill;
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $pdf->Cell($width, 7, $this->text((string) $cells[$cellIndex], in_array($cellIndex, [1, 11], true) ? 0 : 12), 1, 0, $align, true);
            }
            $pdf->Ln();
        }
    }

    private function hexToRgb(?string $hex, array $fallback = [255, 255, 224]): array
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
