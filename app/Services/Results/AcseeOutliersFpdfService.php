<?php

namespace App\Services\Results;

use Carbon\CarbonInterface;

class AcseeOutliersFpdfService
{
    public function generate(
        array $rows,
        array $filters,
        CarbonInterface $generatedAt,
        string $generatedBy,
        string $outputPath
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $pdf = new class($this, $filters, $generatedAt, $generatedBy) extends \FPDF {
            public function __construct(
                private AcseeOutliersFpdfService $service,
                private array $filters,
                private CarbonInterface $generatedAt,
                private string $generatedBy
            ) {
                parent::__construct('L', 'mm', 'A4');
                $this->SetMargins(8, 10, 8);
                $this->SetAutoPageBreak(true, 10);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                $this->SetFillColor(248, 250, 252);
                $this->Rect(0, 0, 297, 210, 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, 297, 4, 'F');

                $this->SetY(8);
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 11);
                $this->Cell(0, 5, $this->service->text('ACSEE FINAL OUTLIERS & EXTREMES REPORT'), 0, 1, 'C');
                $this->SetTextColor(37, 99, 235);
                $this->SetFont('Helvetica', 'B', 8);
                $this->Cell(0, 4, $this->service->text('Read-only anomaly export from final results tables'), 0, 1, 'C');

                $this->Ln(2);
                $this->SetFillColor(255, 255, 255);
                $this->SetDrawColor(226, 232, 240);
                $this->Rect(8, 24, 281, 18, 'DF');
                $this->SetXY(12, 27);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', 'B', 6.5);
                $this->Cell(38, 4, $this->service->text('GENERATED'), 0, 0, 'L');
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', '', 6.5);
                $this->Cell(55, 4, $this->service->text($this->generatedAt->format('d M Y H:i')), 0, 0, 'L');

                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', 'B', 6.5);
                $this->Cell(18, 4, $this->service->text('BY'), 0, 0, 'L');
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', '', 6.5);
                $this->Cell(40, 4, $this->service->text($this->generatedBy, 22), 0, 0, 'L');

                $filterText = $this->service->buildFilterSummary($this->filters);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', 'B', 6.5);
                $this->Cell(20, 4, $this->service->text('FILTERS'), 0, 0, 'L');
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', '', 6.5);
                $this->Cell(100, 4, $this->service->text($filterText, 60), 0, 1, 'L');

                $this->Ln(4);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 6.5);
                $this->Cell(95, 4, $this->service->text('System Stamp Signature: IRMS'), 0, 0, 'L');
                $this->Cell(95, 4, $this->service->text('Premium FPDF Export'), 0, 0, 'C');
                $this->Cell(95, 4, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderSummary($pdf, $rows);
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

    public function buildFilterSummary(array $filters): string
    {
        $parts = [];
        if (!empty($filters['exam_year_id'])) {
            $parts[] = 'Exam Year ID: ' . $filters['exam_year_id'];
        }
        if (!empty($filters['region_id'])) {
            $parts[] = 'Region ID: ' . $filters['region_id'];
        }
        if (!empty($filters['district_id'])) {
            $parts[] = 'District ID: ' . $filters['district_id'];
        }
        if (!empty($filters['school_id'])) {
            $parts[] = 'School ID: ' . $filters['school_id'];
        }
        if (!empty($filters['q'])) {
            $parts[] = 'Search: ' . $filters['q'];
        }

        return empty($parts) ? 'No active filters' : implode(' | ', $parts);
    }

    private function renderSummary(\FPDF $pdf, array $rows): void
    {
        $highFlags = count(array_filter($rows, fn ($row) => strtoupper((string) ($row['flag'] ?? '')) === 'HIGH'));
        $moderateFlags = count(array_filter($rows, fn ($row) => strtoupper((string) ($row['flag'] ?? '')) === 'MODERATE'));
        $distinctSchools = count(array_unique(array_map(fn ($row) => (string) ($row['school_name'] ?? ''), $rows)));
        $distinctSubjects = count(array_unique(array_map(fn ($row) => (string) ($row['subject_name'] ?? ''), $rows)));

        $cards = [
            ['Rows Exported', (string) count($rows)],
            ['High Flags', (string) $highFlags],
            ['Schools', (string) $distinctSchools],
            ['Subjects', (string) $distinctSubjects],
            ['Moderate Flags', (string) $moderateFlags],
        ];

        $x = 12;
        $y = $pdf->GetY();
        foreach ($cards as $index => [$label, $value]) {
            $cardX = $x + ($index * 55);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($cardX, $y, 50, 16, 'DF');
            $pdf->SetFillColor(219, 234, 254);
            $pdf->Rect($cardX, $y, 50, 3, 'F');
            $pdf->SetXY($cardX + 2.5, $y + 5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->Cell(45, 3, $this->text(strtoupper($label), 16), 0, 1, 'L');
            $pdf->SetX($cardX + 2.5);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(45, 5, $this->text($value, 12), 0, 1, 'L');
        }

        $pdf->SetY($y + 22);
    }

    private function renderTable(\FPDF $pdf, array $rows): void
    {
        $headers = [
            ['INDEX NUMBER', 27],
            ['CANDIDATE NAME', 46],
            ['SCHOOL', 48],
            ['SUBJECT', 40],
            ['MARK', 15],
            ['Z-SCORE', 16],
            ['FLAG', 18],
            ['DIVISION', 16],
            ['GPA', 14],
        ];

        $printHeader = function () use ($pdf, $headers) {
            $pdf->SetFillColor(15, 23, 42);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 6);
            foreach ($headers as [$label, $width]) {
                $pdf->Cell($width, 8, $this->text($label, 18), 1, 0, 'C', true);
            }
            $pdf->Ln();
        };

        $printHeader();

        foreach ($rows as $index => $row) {
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $this->renderSummary($pdf, $rows);
                $printHeader();
            }

            $fill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $pdf->SetFillColor(...$fill);
            $pdf->SetFont('Helvetica', '', 6);

            $cells = [
                $row['index_number'] ?? '',
                $row['candidate_name'] ?? '',
                $row['school_name'] ?? '',
                $row['subject_name'] ?? '',
                $row['mark'] ?? '',
                $row['z_score'] ?? '',
                $row['flag'] ?? '',
                $row['division'] ?? '',
                $row['gpa'] ?? '',
            ];

            foreach ($headers as $cellIndex => [, $width]) {
                $align = $cellIndex <= 3 ? 'L' : 'C';
                $flagValue = strtoupper((string) ($row['flag'] ?? ''));
                if ($cellIndex === 6 && $flagValue === 'HIGH') {
                    $pdf->SetTextColor(185, 28, 28);
                    $pdf->SetFont('Helvetica', 'B', 6);
                } elseif ($cellIndex === 6 && $flagValue === 'MODERATE') {
                    $pdf->SetTextColor(180, 83, 9);
                    $pdf->SetFont('Helvetica', 'B', 6);
                } else {
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont('Helvetica', '', 6);
                }

                $pdf->Cell($width, 6.5, $this->text((string) ($cells[$cellIndex] ?? ''), $cellIndex <= 3 ? 30 : 14), 1, 0, $align, true);
            }
            $pdf->Ln();
        }
    }
}
