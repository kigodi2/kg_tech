<?php

namespace App\Services\Results;

class AcseeRegionalStudentRankingFpdfService
{
    public function resolvedReportLabel(?string $reportLabel, string $fallback): string
    {
        $label = strtoupper(trim((string) $reportLabel));

        return $label !== '' ? $label : $fallback;
    }

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
                private AcseeRegionalStudentRankingFpdfService $service,
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
                $banner = 'PROFESSIONAL ' . $this->service->resolvedReportLabel($this->reportLabel, 'STUDENT RANKING') . ' PERFORMANCE REPORT';
                $this->Cell(277, 5, $this->service->text($banner), 0, 1, 'C', true);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->Cell(0, 4, $this->service->text($this->service->footerLabel($this->reportLabel, 'ACSEE regional student ranking export')), 0, 0, 'L');
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
        $pdf->SetX(10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(10, $pdf->GetY(), 277, 10, 'DF');
        $pdf->SetXY(14, $pdf->GetY() + 2);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 8);
        $label = 'DETAILED ' . $this->resolvedReportLabel($reportLabel, 'STUDENT') . ' TABLE';
        $pdf->Cell(200, 4, $this->text($label), 0, 1, 'L');
        $pdf->Ln(2);
    }

    private function renderTable(\FPDF $pdf, array $rows): void
    {
        $widths = [18, 48, 10, 14, 109, 13, 13, 10, 11, 9, 13, 9];
        $this->renderSummaryHeaderRow($pdf);

        foreach ($rows as $index => $row) {
            $cells = [
                $row['index_number'] ?? '-',
                $row['school'] ?? '-',
                $row['sex'] ?? '-',
                $row['combination'] ?? '-',
                $row['subject_results_text'] ?? '-',
                is_null($row['total_marks'] ?? null) ? '-' : number_format((float) $row['total_marks'], 0),
                is_null($row['avg_marks'] ?? null) ? '-' : number_format((float) $row['avg_marks'], 2),
                $row['overall_grade'] ?? '-',
                is_null($row['aggt'] ?? null) ? '-' : (string) $row['aggt'],
                $row['division'] ?? '-',
                is_null($row['gpa'] ?? null) ? '-' : number_format((float) $row['gpa'], 4),
                $row['position'] ?? '-',
            ];

            $rowHeight = 7.0;
            $this->checkPageBreak($pdf, $rowHeight + 1);

            $fill = $index % 2 === 0 ? [255, 253, 231] : [255, 249, 196];
            $x = 10.0;
            $y = $pdf->GetY();
            foreach ($widths as $cellIndex => $width) {
                $align = in_array($cellIndex, [1, 4], true) ? 'L' : 'C';
                $text = $this->text((string) $cells[$cellIndex], match ($cellIndex) {
                    0 => 10,
                    1 => 52,
                    4 => 0,
                    default => 18,
                });
                $textY = $y + max(0, ($rowHeight - 4.2) / 2);
                $pdf->SetXY($x, $y);
                $pdf->SetFillColor(...$fill);
                $pdf->SetDrawColor(100, 116, 139);
                $pdf->SetLineWidth(0.18);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetFont('Helvetica', in_array($cellIndex, [3, 7, 9, 11], true) ? 'B' : '', 6.3);
                $pdf->Rect($x, $y, $width, $rowHeight, 'DF');
                $pdf->SetXY($x, $textY);
                $pdf->Cell($width, 4.2, $text, 0, 0, $align, false);
                $x += $width;
            }
            $pdf->SetY($y + $rowHeight);
        }
    }

    private function checkPageBreak(\FPDF $pdf, float $requiredHeight): void
    {
        $pageHeight = method_exists($pdf, 'GetPageHeight') ? $pdf->GetPageHeight() : 210;
        $bottomMargin = 12;
        if (($pdf->GetY() + $requiredHeight) > ($pageHeight - $bottomMargin)) {
            $pdf->AddPage();
            $this->renderSummaryHeaderRow($pdf);
        }
    }

    private function renderSummaryHeaderRow(\FPDF $pdf): void
    {
        $widths = [18, 48, 10, 14, 109, 13, 13, 10, 11, 9, 13, 9];
        $headers = ['CNO', 'SCHOOL', 'SEX', 'COMB', 'DETAILED SUBJECTS RESULT', 'TOTAL', 'AVG', 'GRD', 'AGGT', 'DIV', 'GPA', 'POS'];
        $pdf->SetX(10);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->SetLineWidth(0.24);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 7.2);
        $x = 10.0;
        $y = $pdf->GetY();
        $rowHeight = 9.0;
        foreach ($headers as $index => $header) {
            $align = in_array($index, [1, 4], true) ? 'L' : 'C';
            $width = $widths[$index];
            $textY = $y + max(0, ($rowHeight - 4.2) / 2);
            $pdf->SetXY($x, $y);
            $pdf->Rect($x, $y, $width, $rowHeight, 'DF');
            $pdf->SetXY($x, $textY);
            $pdf->Cell($width, 4.2, $header, 0, 0, $align, false);
            $x += $width;
        }
        $pdf->SetY($y + $rowHeight);
    }
}
