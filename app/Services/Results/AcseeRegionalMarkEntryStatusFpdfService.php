<?php

namespace App\Services\Results;

class AcseeRegionalMarkEntryStatusFpdfService
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
                private AcseeRegionalMarkEntryStatusFpdfService $service,
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
                    : 'PROFESSIONAL MARK ENTRY STATUS REPORT';
                $this->Cell(277, 5, $this->service->text($banner), 0, 1, 'C', true);
                $this->Ln(1);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->Cell(0, 4, $this->service->text($this->service->footerLabel($this->reportLabel, 'ACSEE regional mark entry status export')), 0, 0, 'L');
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
        $label = $reportLabel ? 'DETAILED ' . strtoupper(trim((string) $reportLabel)) . ' TABLE' : 'DETAILED MARK ENTRY STATUS TABLE';
        $pdf->Cell(200, 4, $this->text($label), 0, 1, 'L');
        $pdf->Ln(4);
    }

    private function renderTable(\FPDF $pdf, array $rows): void
    {
        $startX = 10;
        $widths = [12, 112, 30, 30, 24, 28, 41];
        $headers = ['S/N', 'SUBJECT', 'EXPECTED', 'MARKED', 'PENDING', 'COMP %', 'STATUS'];

        $pdf->SetX($startX);
        $pdf->SetFillColor(244, 241, 177);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->SetLineWidth(0.24);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 8.2);
        foreach ($headers as $index => $header) {
            $pdf->Cell($widths[$index], 9, $header, 1, 0, $index === 1 || $index === 6 ? 'L' : 'C', true);
        }
        $pdf->Ln();

        foreach ($rows as $index => $row) {
            $pdf->SetX($startX);
            $baseFill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $statusFill = match ($row['status'] ?? '') {
                'Complete' => [31, 238, 11],
                'Near Complete' => [222, 240, 67],
                'In Progress' => [255, 119, 47],
                default => [255, 39, 47],
            };

            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 8.2);

            $cells = [
                $index + 1,
                $row['subject'] ?? '-',
                $row['expected_entries'] ?? 0,
                $row['marked_entries'] ?? 0,
                $row['pending_entries'] ?? 0,
                number_format((float) ($row['completion'] ?? 0), 1) . '%',
                $row['status'] ?? '-',
            ];

            foreach ($widths as $cellIndex => $width) {
                $fill = $cellIndex === 6 ? $statusFill : $baseFill;
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $align = $cellIndex === 1 || $cellIndex === 6 ? 'L' : 'C';
                $pdf->Cell($width, 7, $this->text((string) $cells[$cellIndex], in_array($cellIndex, [1, 6], true) ? 0 : 12), 1, 0, $align, true);
            }
            $pdf->Ln();
        }
    }
}
