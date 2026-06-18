<?php

namespace App\Services\Results;

class PsleRegionalMarkEntryStatusFpdfService
{
    public const PAGE_WIDTH = 297.0;
    public const PAGE_HEIGHT = 210.0;
    public const LEFT_MARGIN = 8.0;
    public const RIGHT_MARGIN = 8.0;
    public const CONTENT_WIDTH = self::PAGE_WIDTH - self::LEFT_MARGIN - self::RIGHT_MARGIN;

    public function footerLabel(?string $reportLabel, string $fallback): string
    {
        return $fallback;
    }

    public function generate(
        object $region,
        int $examYearValue,
        array $rows,
        array $summary,
        string $outputPath,
        ?string $reportLabel = null,
        array $filters = []
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $generatedAt = date('d-m-Y H:i:s');
        $host = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', (string) (gethostname() ?: 'NODE')));
        $host = $host !== '' ? substr($host, 0, 8) : 'NODE';
        $regionCode = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', (string) $region->name));
        $regionCode = $regionCode !== '' ? substr($regionCode, 0, 3) : 'REG';
        $barcodePayload = sprintf('PSLE-%s-%s-%s', $regionCode, date('Ymd-His'), $host);

        $pdf = new class($this, $region, $examYearValue, $reportLabel, $summary, $filters, $generatedAt, $host, $barcodePayload) extends \FPDF {
            public function __construct(
                private PsleRegionalMarkEntryStatusFpdfService $service,
                private object $region,
                private int $examYearValue,
                private ?string $reportLabel,
                private array $summary,
                private array $filters,
                private string $generatedAt,
                private string $host,
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
                $this->Rect(0, 0, PsleRegionalMarkEntryStatusFpdfService::PAGE_WIDTH, PsleRegionalMarkEntryStatusFpdfService::PAGE_HEIGHT, 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, PsleRegionalMarkEntryStatusFpdfService::PAGE_WIDTH, 4, 'F');

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
                $this->SetFont('Helvetica', 'B', 10.0);
                $this->SetX($titleBlockX);
                $this->Cell(
                    $titleBlockWidth,
                    4.4,
                    $this->service->text(
                        strtoupper((string) $this->region->name)
                        . ' - '
                        . strtoupper(trim((string) ($this->reportLabel ?: 'MARK ENTRY STATUS REPORT')))
                    ),
                    0,
                    1,
                    'C'
                );

                $this->Ln(0.9);
                $this->SetX(8);
                $this->SetDrawColor(191, 219, 254);
                $this->SetFillColor(219, 234, 254);
                $this->SetTextColor(30, 64, 175);
                $this->SetFont('Helvetica', 'B', 6.7);
                $banner = 'PROFESSIONAL PSLE MARK ENTRY STATUS REPORT';
                $this->Cell(PsleRegionalMarkEntryStatusFpdfService::CONTENT_WIDTH, 4.6, $this->service->text($banner), 0, 1, 'C', true);
                $stripY = $this->GetY() + 0.3;
                $this->SetFillColor(0, 166, 81);
                $this->Rect(8, $stripY, 84.78, 0.5, 'F');
                $this->SetFillColor(245, 208, 0);
                $this->Rect(92.78, $stripY, 70.89, 0.5, 'F');
                $this->SetFillColor(0, 0, 0);
                $this->Rect(163.67, $stripY, 52.86, 0.5, 'F');
                $this->SetFillColor(11, 47, 91);
                $this->Rect(216.53, $stripY, 72.47, 0.5, 'F');
                $this->SetY($stripY + 1.2);
            }

            public function Footer(): void
            {
                $this->SetTextColor(71, 85, 105);
                $this->SetFont('Helvetica', '', 6.2);
                $this->SetXY(8, 191.0);
                $this->Cell(PsleRegionalMarkEntryStatusFpdfService::CONTENT_WIDTH, 3.2, $this->service->text('GENERATED: ' . $this->generatedAt . ' | IRMS NODE: ' . $this->host), 0, 1, 'R');

                $this->SetY(203.5);
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
                $this->Cell(0, 3.6, $this->service->text($this->service->footerLabel($this->reportLabel, 'STANDARD SEVEN ZONAL MOCK - MARK ENTRY STATUS REPORT, ' . $this->examYearValue)), 0, 0, 'L');
                $this->Cell(0, 3.6, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderSummary($pdf, $region, $summary, $filters);
        $this->renderLegend($pdf);
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

    private function drawFittedCell(
        \FPDF $pdf,
        float $width,
        float $height,
        string $text,
        int|string $border = 0,
        int $ln = 0,
        string $align = 'C',
        bool $fill = false,
        float $maxFont = 8.0,
        float $minFont = 5.0,
        string $fontFamily = 'Helvetica',
        string $fontStyle = 'B'
    ): void {
        $text = $this->text($text);

        for ($size = $maxFont; $size >= $minFont; $size -= 0.2) {
            $pdf->SetFont($fontFamily, $fontStyle, $size);
            if ($pdf->GetStringWidth($text) <= max(1.0, $width - 1.0)) {
                $pdf->Cell($width, $height, $text, $border, $ln, $align, $fill);
                return;
            }
        }

        $pdf->SetFont($fontFamily, $fontStyle, $minFont);
        $pdf->Cell($width, $height, $text, $border, $ln, $align, $fill);
    }

    private function formatDate(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        return date('d-m-Y', $timestamp);
    }

    private function renderSummary(\FPDF $pdf, object $region, array $summary, array $filters): void
    {
        $startY = max($pdf->GetY() + 0.8, 35.8);
        $pdf->SetXY(8, $startY);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $summaryHeight = 26.5;
        if (!empty($filters['activity_date']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['status'])) {
            $summaryHeight += 4.6;
        }
        $pdf->Rect(8, $startY, self::CONTENT_WIDTH, $summaryHeight, 'DF');
        $pdf->SetXY(8, $startY + 3.4);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetFont('Helvetica', 'B', 8.4);

        $lines = [
            'REGION: ' . strtoupper((string) $region->name),
            'TOTAL SCHOOLS: ' . ($summary['schools'] ?? '0') . ' | TOTAL REGISTERED CANDIDATES: ' . ($summary['registered_candidates'] ?? '0'),
            'TOTAL EXPECTED SCRIPTS: ' . ($summary['total_scripts'] ?? '0') . ' | TOTAL MARKED SCRIPTS: ' . ($summary['marked_scripts'] ?? '0') . ' | TOTAL PENDING SCRIPTS: ' . ($summary['pending_scripts'] ?? '0'),
            'OVERALL COMPLETION RATE: ' . ($summary['completion'] ?? '0.0') . '%',
            'SCHOOL STATUS SUMMARY: COMPLETE ' . ($summary['complete_schools'] ?? '0') . ' | IN PROGRESS ' . ($summary['in_progress_schools'] ?? '0') . ' | NOT STARTED ' . ($summary['not_started_schools'] ?? '0'),
        ];

        $filterBits = [];
        if (!empty($filters['activity_date'])) {
            $filterBits[] = 'ACTIVITY DATE: ' . $this->formatDate((string) $filters['activity_date']);
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $filterBits[] = 'DATE RANGE: ' . $this->formatDate((string) ($filters['date_from'] ?: '')) . ' TO ' . $this->formatDate((string) ($filters['date_to'] ?: ''));
        }
        if (!empty($filters['status'])) {
            $filterBits[] = 'STATUS: ' . strtoupper((string) $filters['status']);
        }
        if ($filterBits !== []) {
            $lines[] = implode(' | ', $filterBits);
        }

        foreach ($lines as $line) {
            $pdf->Cell(self::CONTENT_WIDTH, 4.45, $this->text($line), 0, 1, 'L');
        }
        $pdf->Ln(3.2);
    }

    private function renderLegend(\FPDF $pdf): void
    {
        $pdf->SetXY(8, $pdf->GetY());
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetFont('Helvetica', 'B', 8.2);
        $labelWidth = $pdf->GetStringWidth('STATUS LEGEND:');
        $pdf->Cell($labelWidth, 4.2, 'STATUS LEGEND:', 0, 0, 'L');
        $pdf->Cell(2.0, 4.2, '', 0, 0);

        $items = [
            ['COMPLETE', [31, 238, 11]],
            ['NEAR COMPLETE', [222, 240, 67]],
            ['IN PROGRESS', [255, 119, 47]],
            ['NOT STARTED', [255, 39, 47]],
        ];

        foreach ($items as [$label, $rgb]) {
            $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
            $w = $pdf->GetStringWidth($label) + 5.0;
            $pdf->Cell($w, 4.2, $label, 0, 0, 'C', true);
            $pdf->Cell(1.2, 4.2, '', 0, 0);
        }
        $pdf->Ln(6.2);
    }

    private function renderTable(\FPDF $pdf, array $rows): void
    {
        $x = 8;
        $tableWidth = self::CONTENT_WIDTH;
        $widths = [10, 106, 16, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 18, 12, 12, 12, 12, 24, 38];
        $baseWidth = array_sum($widths);
        if ($baseWidth > 0) {
            $scale = $tableWidth / $baseWidth;
            foreach ($widths as $index => $width) {
                $widths[$index] = round($width * $scale, 3);
            }
            $diff = round($tableWidth - array_sum($widths), 3);
            if (abs($diff) > 0.001) {
                $widths[1] += $diff;
            }
        }
        $currentTableTop = 0.0;

        $drawOuterBorder = function () use ($pdf, $x, $tableWidth, &$currentTableTop) {
            if ($currentTableTop <= 0) {
                return;
            }

            $tableBottom = $pdf->GetY();
            if ($tableBottom <= $currentTableTop) {
                return;
            }

            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.24);
            $pdf->Rect($x, $currentTableTop, $tableWidth, $tableBottom - $currentTableTop, 'D');
            $pdf->SetLineWidth(0.12);
        };

        $drawHeaders = function () use ($pdf, $x, $widths, &$currentTableTop, $tableWidth) {
            $currentTableTop = $pdf->GetY();
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->Rect($x, $pdf->GetY(), $tableWidth, 9, 'DF');
            $pdf->SetXY($x + 4, $pdf->GetY() + 1.5);
            $pdf->SetTextColor(37, 99, 235);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(220, 4, $this->text('DETAILED MARK ENTRY STATUS TABLE'), 0, 0, 'L');

            $startY = $pdf->GetY() + 7.5;
            $pdf->SetFillColor(244, 241, 177);
            $pdf->SetTextColor(8, 39, 109);
            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);
            $pdf->SetFont('Helvetica', 'B', 7.4);
            $pdf->SetXY($x, $startY);

            $row1 = 4.0;
            $row2 = 4.0;
            $row3 = 4.0;

            // Row 1
            $this->drawFittedCell($pdf, $widths[0], $row1 + $row2 + $row3, 'S/N', 1, 0, 'C', true, 7.4, 6.0, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[1], $row1 + $row2 + $row3, 'SCHOOL', 1, 0, 'L', true, 7.4, 6.0, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[2], $row1 + $row2 + $row3, 'REGIST', 1, 0, 'C', true, 7.2, 5.8, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, array_sum(array_slice($widths, 3, 12)), $row1, 'SUBJECTS', 1, 0, 'C', true, 7.4, 6.0, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[15], $row1 + $row2 + $row3, 'TOTAL', 1, 0, 'C', true, 7.4, 6.0, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[16] + $widths[17], $row1 + $row2, 'MARKED', 1, 0, 'C', true, 7.2, 5.8, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[18] + $widths[19], $row1 + $row2, 'PENDING', 1, 0, 'C', true, 7.2, 5.8, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[20], $row1 + $row2 + $row3, 'COMPLETION %', 1, 0, 'C', true, 6.9, 5.4, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[21], $row1 + $row2 + $row3, 'STATUS', 1, 0, 'L', true, 7.2, 5.8, 'Helvetica', 'B');

            // Row 2
            $pdf->SetXY($x + array_sum(array_slice($widths, 0, 3)), $startY + $row1);
            $subjectLabels = ['KISWAHILI', 'ENGLISH', 'SOCIAL', 'MATHEMATICS', 'SCIENCE', 'CIVIC'];
            $pdf->SetFont('Helvetica', 'B', 6.4);
            foreach ($subjectLabels as $labelIndex => $label) {
                $offset = 3 + ($labelIndex * 2);
                $this->drawFittedCell($pdf, $widths[$offset] + $widths[$offset + 1], $row2, $label, 1, 0, 'C', true, 6.4, 4.8, 'Helvetica', 'B');
            }

            // Row 3
            $pdf->SetXY($x + array_sum(array_slice($widths, 0, 3)), $startY + $row1 + $row2);
            $pdf->SetFont('Helvetica', 'B', 6.2);
            foreach (range(0, 5) as $subjectIndex) {
                $offset = 3 + ($subjectIndex * 2);
                $this->drawFittedCell($pdf, $widths[$offset], $row3, 'SCRIPTS', 1, 0, 'C', true, 6.2, 4.6, 'Helvetica', 'B');
                $this->drawFittedCell($pdf, $widths[$offset + 1], $row3, '%', 1, 0, 'C', true, 6.2, 4.6, 'Helvetica', 'B');
            }
            $pdf->SetXY($x + array_sum(array_slice($widths, 0, 16)), $startY + $row1 + $row2);
            $this->drawFittedCell($pdf, $widths[16], $row3, 'SCRIPTS', 1, 0, 'C', true, 6.2, 4.6, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[17], $row3, '%', 1, 0, 'C', true, 6.2, 4.6, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[18], $row3, 'SCRIPTS', 1, 0, 'C', true, 6.2, 4.6, 'Helvetica', 'B');
            $this->drawFittedCell($pdf, $widths[19], $row3, '%', 1, 0, 'C', true, 6.2, 4.6, 'Helvetica', 'B');

            $pdf->SetY($startY + $row1 + $row2 + $row3);
        };

        $drawHeaders();

        foreach ($rows as $index => $row) {
            if ($pdf->GetY() > 184) {
                $drawOuterBorder();
                $pdf->AddPage();
                $pdf->SetY(35.8);
                $drawHeaders();
            }

            $statusFill = match ($row['status'] ?? '') {
                'Complete' => [31, 238, 11],
                'Near Complete' => [222, 240, 67],
                'In Progress' => [255, 119, 47],
                default => [255, 39, 47],
            };

            $pdf->SetX($x);
            $fill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 8.1);
            $pdf->SetFillColor(...$fill);
            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);

            $cells = [
                $index + 1,
                $this->text((string) ($row['school'] ?? '-'), 52),
                $row['registered'] ?? 0,
                $row['kisw'] ?? 0,
                number_format((float) ($row['kisw_pct'] ?? 0), 1),
                $row['eng'] ?? 0,
                number_format((float) ($row['eng_pct'] ?? 0), 1),
                $row['sst'] ?? 0,
                number_format((float) ($row['sst_pct'] ?? 0), 1),
                $row['math'] ?? 0,
                number_format((float) ($row['math_pct'] ?? 0), 1),
                $row['sci'] ?? 0,
                number_format((float) ($row['sci_pct'] ?? 0), 1),
                $row['cme'] ?? 0,
                number_format((float) ($row['cme_pct'] ?? 0), 1),
                $row['total'] ?? 0,
                $row['marked_scripts'] ?? 0,
                number_format((float) ($row['marked_pct'] ?? 0), 1),
                $row['pending_scripts'] ?? 0,
                number_format((float) ($row['pending_pct'] ?? 0), 1),
                number_format((float) ($row['completion'] ?? 0), 1) . '%',
                $this->text((string) ($row['status'] ?? '-'), 16),
            ];

            foreach ($widths as $i => $w) {
                if ($i === 21) {
                    $pdf->SetFillColor($statusFill[0], $statusFill[1], $statusFill[2]);
                    $align = 'L';
                } else {
                    $pdf->SetFillColor(...$fill);
                    $align = $i === 1 ? 'L' : 'C';
                }
                $limit = match ($i) {
                    1 => 52,
                    21 => 16,
                    default => 12,
                };
                $pdf->Cell($w, 6.2, $this->text((string) $cells[$i], $limit), 1, 0, $align, true);
            }
            $pdf->Ln();
        }

        if ($rows !== []) {
            $pdf->SetX($x);
            $pdf->SetTextColor(8, 39, 109);
            $pdf->SetFont('Helvetica', 'B', 8.2);
            $pdf->SetFillColor(244, 241, 177);
            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);

            $totalRegistered = array_sum(array_column($rows, 'registered'));
            $totalKisw = array_sum(array_column($rows, 'kisw'));
            $totalEng = array_sum(array_column($rows, 'eng'));
            $totalSst = array_sum(array_column($rows, 'sst'));
            $totalMath = array_sum(array_column($rows, 'math'));
            $totalSci = array_sum(array_column($rows, 'sci'));
            $totalCme = array_sum(array_column($rows, 'cme'));
            $totalExpected = array_sum(array_column($rows, 'total'));
            $totalMarked = array_sum(array_column($rows, 'marked_scripts'));
            $totalPending = array_sum(array_column($rows, 'pending_scripts'));
            $markedPct = $totalExpected > 0 ? ($totalMarked / $totalExpected) * 100 : 0;
            $pendingPct = $totalExpected > 0 ? ($totalPending / $totalExpected) * 100 : 0;

            $rowH = 6.8;
            $pdf->Cell($widths[0] + $widths[1], $rowH, 'TOTAL', 1, 0, 'C', true);
            $pdf->Cell($widths[2], $rowH, (string) $totalRegistered, 1, 0, 'C', true);
            $pdf->Cell($widths[3], $rowH, (string) $totalKisw, 1, 0, 'C', true);
            $pdf->Cell($widths[4], $rowH, number_format($totalRegistered > 0 ? ($totalKisw / $totalRegistered) * 100 : 0, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $rowH, (string) $totalEng, 1, 0, 'C', true);
            $pdf->Cell($widths[6], $rowH, number_format($totalRegistered > 0 ? ($totalEng / $totalRegistered) * 100 : 0, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $rowH, (string) $totalSst, 1, 0, 'C', true);
            $pdf->Cell($widths[8], $rowH, number_format($totalRegistered > 0 ? ($totalSst / $totalRegistered) * 100 : 0, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $rowH, (string) $totalMath, 1, 0, 'C', true);
            $pdf->Cell($widths[10], $rowH, number_format($totalRegistered > 0 ? ($totalMath / $totalRegistered) * 100 : 0, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $rowH, (string) $totalSci, 1, 0, 'C', true);
            $pdf->Cell($widths[12], $rowH, number_format($totalRegistered > 0 ? ($totalSci / $totalRegistered) * 100 : 0, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $rowH, (string) $totalCme, 1, 0, 'C', true);
            $pdf->Cell($widths[14], $rowH, number_format($totalRegistered > 0 ? ($totalCme / $totalRegistered) * 100 : 0, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[15], $rowH, (string) $totalExpected, 1, 0, 'C', true);
            $pdf->Cell($widths[16], $rowH, (string) $totalMarked, 1, 0, 'C', true);
            $pdf->Cell($widths[17], $rowH, number_format($markedPct, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[18], $rowH, (string) $totalPending, 1, 0, 'C', true);
            $pdf->Cell($widths[19], $rowH, number_format($pendingPct, 1), 1, 0, 'C', true);
            $pdf->Cell($widths[20], $rowH, number_format($markedPct, 1) . '%', 1, 0, 'C', true);
            $pdf->Cell($widths[21], $rowH, 'SUMMARY', 1, 0, 'L', true);
            $pdf->Ln();
        }

        $drawOuterBorder();
    }
}
