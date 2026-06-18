<?php

namespace App\Services\Results;

class PsleRegionalSchoolwiseFpdfService
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
        array $total,
        string $outputPath,
        ?string $reportLabel = null,
        array $options = []
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

        $pdf = new class($this, $region, $examYearValue, $reportLabel, $generatedAt, $host, $barcodePayload) extends \FPDF {
            public function __construct(
                private PsleRegionalSchoolwiseFpdfService $service,
                private object $region,
                private int $examYearValue,
                private ?string $reportLabel,
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
                $this->Rect(0, 0, PsleRegionalSchoolwiseFpdfService::PAGE_WIDTH, PsleRegionalSchoolwiseFpdfService::PAGE_HEIGHT, 'F');

                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, PsleRegionalSchoolwiseFpdfService::PAGE_WIDTH, 4, 'F');

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
                    $this->service->text(strtoupper((string) $this->region->name) . ' - ' . strtoupper(trim((string) ($this->reportLabel ?: 'SCHOOLWISE EVALUATION')))),
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
                $banner = $this->reportLabel
                    ? 'PROFESSIONAL ' . strtoupper(trim((string) $this->reportLabel)) . ' PERFORMANCE REPORT'
                    : 'PROFESSIONAL SCHOOLWISE PERFORMANCE REPORT';
                $this->Cell(PsleRegionalSchoolwiseFpdfService::CONTENT_WIDTH, 4.6, $this->service->text($banner), 0, 1, 'C', true);
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
                $this->Cell(PsleRegionalSchoolwiseFpdfService::CONTENT_WIDTH, 3.2, $this->service->text('GENERATED: ' . $this->generatedAt . ' | IRMS NODE: ' . $this->host), 0, 1, 'R');

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
                $this->Cell(0, 3.6, $this->service->text($this->service->footerLabel($this->reportLabel, 'PSLE regional schoolwise evaluation export')), 0, 0, 'L');
                $this->Cell(0, 3.6, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderSummary($pdf, $region, $options['summary'] ?? []);
        $this->renderTable($pdf, $rows, $total, $reportLabel, $options);
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

    private function renderSummary(\FPDF $pdf, object $region, array $summary): void
    {
        $pdf->SetXY(8, 35.8);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 7.2);

        $registered = $summary['registered'] ?? ['m' => 0, 'f' => 0, 't' => 0];
        $sat = $summary['sat'] ?? ['m' => 0, 'f' => 0, 't' => 0];
        $absent = $summary['absent'] ?? ['m' => 0, 'f' => 0, 't' => 0];
        $inc = $summary['inc'] ?? ['m' => 0, 'f' => 0, 't' => 0];

        $pdf->Cell(self::CONTENT_WIDTH, 3.9, $this->text('REGION: ' . strtoupper((string) ($summary['region_name'] ?? $region->name))), 0, 1, 'L');

        if (!empty($summary['zonal_rank'])) {
            $rank = (array) $summary['zonal_rank'];
            $pdf->Cell(self::CONTENT_WIDTH, 3.9, $this->text('ZONAL RANK: ' . ($rank['rank'] ?? '-') . ' OUT OF ' . ($rank['total'] ?? '-')), 0, 1, 'L');
        }

        $groupLine = (string) ($summary['group_count_label'] ?? 'TOTAL GROUPS') . ': ' . (int) ($summary['group_count'] ?? 0);
        if (($summary['is_schoolwise'] ?? false) === true) {
            $groupLine .= ' (GOVERNMENT: ' . (int) ($summary['government_school_count'] ?? 0) . ', NON-GOVERNMENT: ' . (int) ($summary['non_government_school_count'] ?? 0) . ')';
        }
        $pdf->Cell(self::CONTENT_WIDTH, 3.9, $this->text($groupLine), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 3.9, $this->text('TOTAL REGISTERED CANDIDATES: ' . ($registered['t'] ?? 0) . ' (F: ' . ($registered['f'] ?? 0) . ', M: ' . ($registered['m'] ?? 0) . ')'), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 3.9, $this->text('TOTAL SAT CANDIDATES: ' . ($sat['t'] ?? 0) . ' (F: ' . ($sat['f'] ?? 0) . ', M: ' . ($sat['m'] ?? 0) . ') | TOTAL ABSENT CANDIDATES: ' . ($absent['t'] ?? 0) . ' (F: ' . ($absent['f'] ?? 0) . ', M: ' . ($absent['m'] ?? 0) . ') | TOTAL CANDIDATES WITH INCOMPLETES: ' . ($inc['t'] ?? 0) . ' (F: ' . ($inc['f'] ?? 0) . ', M: ' . ($inc['m'] ?? 0) . ')'), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 3.9, $this->text('PASS RATE (A-C): ' . number_format((float) ($summary['pass_ac_pct'] ?? 0), 2) . '% | PASS RATE (A-D): ' . number_format((float) ($summary['pass_ad_pct'] ?? 0), 2) . '%'), 0, 1, 'L');

        $this->renderAverageLine($pdf, (string) ($summary['average_label'] ?? 'AVERAGE'), $summary['regional_average'] ?? null, $summary['regional_average_badge'] ?? null);
        $this->renderBestLeastLine($pdf, (string) ($summary['best_label'] ?? 'BEST'), (string) ($summary['best_name'] ?? '-'), $summary['best_average'] ?? null, $summary['best_average_badge'] ?? null, $summary['best_pos'] ?? null);
        $this->renderBestLeastLine($pdf, (string) ($summary['least_label'] ?? 'LEAST'), (string) ($summary['least_name'] ?? '-'), $summary['least_average'] ?? null, $summary['least_average_badge'] ?? null, $summary['least_pos'] ?? null);
        $pdf->Ln(1.0);
    }

    private function renderAverageLine(\FPDF $pdf, string $label, mixed $average, mixed $badge): void
    {
        $value = is_null($average) ? '-' : number_format((float) $average, 2);
        $text = $label . ': ' . $value;
        $pdf->Cell($pdf->GetStringWidth($text), 3.9, $this->text($text), 0, 0, 'L');
        $pdf->Cell(1.5, 3.9, '', 0, 0);
        $this->renderBadge($pdf, is_array($badge) ? $badge : null);
        $pdf->Ln(3.9);
    }

    private function renderBestLeastLine(\FPDF $pdf, string $label, string $name, mixed $average, mixed $badge, mixed $pos): void
    {
        $value = is_null($average) ? '-' : number_format((float) $average, 2);
        $prefix = $label . ': ' . $name . ' (AVERAGE: ' . $value;
        $pdf->Cell($pdf->GetStringWidth($prefix), 3.9, $this->text($prefix), 0, 0, 'L');
        $pdf->Cell(1.5, 3.9, '', 0, 0);
        $this->renderBadge($pdf, is_array($badge) ? $badge : null);
        $suffix = ', POS: ' . ($pos ?? '-') . ')';
        $pdf->Cell($pdf->GetStringWidth($suffix), 3.9, $this->text($suffix), 0, 0, 'L');
        $pdf->Ln(3.9);
    }

    private function renderBadge(\FPDF $pdf, ?array $badge): void
    {
        if (!$badge) {
            return;
        }

        $text = 'Grade ' . ($badge['grade'] ?? '-') . ' (' . ($badge['competence'] ?? '-') . ')';
        [$r, $g, $b] = $this->hexToRgb((string) ($badge['color'] ?? '#FFFFFF'));
        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->Cell($pdf->GetStringWidth($text) + 1.6, 3.5, $this->text($text), 0, 0, 'C', true);
    }

    private function drawFittedCell(\FPDF $pdf, float $width, float $height, string $text, string $align = 'L', bool $fill = true, float $baseFontSize = 5.7): void
    {
        $safeText = $this->text($text, 0);
        $fontSize = $baseFontSize;
        $minFontSize = 3.6;

        $pdf->SetFont('Helvetica', '', $fontSize);
        while ($fontSize > $minFontSize && $pdf->GetStringWidth($safeText) > ($width - 1.2)) {
            $fontSize -= 0.2;
            $pdf->SetFont('Helvetica', '', $fontSize);
        }

        $pdf->Cell($width, $height, $safeText, 1, 0, $align, $fill);
        $pdf->SetFont('Helvetica', '', $baseFontSize);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [255, 255, 255];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function renderTable(\FPDF $pdf, array $rows, array $total, ?string $reportLabel = null, array $options = []): void
    {
        $tableStartX = 8.0;
        $tableWidth = self::CONTENT_WIDTH;
        $hideSecondColumn = (bool) ($options['hide_second_column'] ?? false);
        $firstColumnLabel = strtoupper((string) ($options['first_column_label'] ?? 'COUNCIL'));
        $secondColumnLabel = strtoupper((string) ($options['second_column_label'] ?? 'SCHOOL'));
        $firstColumnKey = (string) ($options['first_column_key'] ?? 'council');
        $secondColumnKey = (string) ($options['second_column_key'] ?? 'school');
        $secondColumnAlign = strtoupper((string) ($options['second_column_align'] ?? 'L'));
        $columnWidths = [
            'sn' => 8.0,
            'first' => (float) ($options['first_column_width'] ?? 22.0),
            'second' => $hideSecondColumn ? 0.0 : (float) ($options['second_column_width'] ?? 58.0),
            'metric' => (float) ($options['metric_width'] ?? 4.5),
            'average' => (float) ($options['average_width'] ?? 11.0),
            'grd' => (float) ($options['grd_width'] ?? 6.0),
            'pos' => (float) ($options['pos_width'] ?? 6.0),
        ];

        $baseTableWidth =
            $columnWidths['sn']
            + $columnWidths['first']
            + $columnWidths['second']
            + ($columnWidths['metric'] * 28)
            + $columnWidths['average']
            + $columnWidths['grd']
            + $columnWidths['pos'];

        if ($baseTableWidth > 0) {
            $scale = $tableWidth / $baseTableWidth;
            foreach ($columnWidths as $key => $width) {
                $columnWidths[$key] = round($width * $scale, 3);
            }

            $scaledTableWidth =
                $columnWidths['sn']
                + $columnWidths['first']
                + $columnWidths['second']
                + ($columnWidths['metric'] * 28)
                + $columnWidths['average']
                + $columnWidths['grd']
                + $columnWidths['pos'];

            $remainder = round($tableWidth - $scaledTableWidth, 3);
            if (abs($remainder) > 0.001) {
                if (!$hideSecondColumn) {
                    $columnWidths['second'] += $remainder;
                } else {
                    $columnWidths['first'] += $remainder;
                }
            }
        }

        $flatHeaders = [
            ['S/N', $columnWidths['sn']],
            [$firstColumnLabel, $columnWidths['first']],
            ...($hideSecondColumn ? [] : [[$secondColumnLabel, $columnWidths['second']]]),
            ['REG M', $columnWidths['metric']], ['REG F', $columnWidths['metric']], ['REG T', $columnWidths['metric']],
            ['ABS M', $columnWidths['metric']], ['ABS F', $columnWidths['metric']], ['ABS T', $columnWidths['metric']], ['ABS %', $columnWidths['metric']],
            ['SAT M', $columnWidths['metric']], ['SAT F', $columnWidths['metric']], ['SAT T', $columnWidths['metric']], ['SAT %', $columnWidths['metric']],
            ['INC M', $columnWidths['metric']], ['INC F', $columnWidths['metric']], ['INC T', $columnWidths['metric']], ['INC %', $columnWidths['metric']],
            ['A', $columnWidths['metric']], ['B', $columnWidths['metric']], ['C', $columnWidths['metric']],
            ['A-C M', $columnWidths['metric']], ['A-C F', $columnWidths['metric']], ['A-C T', $columnWidths['metric']], ['A-C %', $columnWidths['metric']],
            ['D', $columnWidths['metric']],
            ['A-D M', $columnWidths['metric']], ['A-D F', $columnWidths['metric']], ['A-D T', $columnWidths['metric']], ['A-D %', $columnWidths['metric']],
            ['E', $columnWidths['metric']],
            ['AVERAGE', $columnWidths['average']],
            ['GRD', $columnWidths['grd']],
            ['POS', $columnWidths['pos']],
        ];

        $outerBorder = [100, 116, 139];
        $currentTableTop = 0.0;

        $drawOuterBorder = function () use ($pdf, $tableStartX, $tableWidth, $outerBorder, &$currentTableTop) {
            if ($currentTableTop <= 0) {
                return;
            }

            $tableBottom = $pdf->GetY();
            if ($tableBottom <= $currentTableTop) {
                return;
            }

            $pdf->SetDrawColor(...$outerBorder);
            $pdf->SetLineWidth(0.24);
            $pdf->Rect($tableStartX, $currentTableTop, $tableWidth, $tableBottom - $currentTableTop, 'D');
            $pdf->SetLineWidth(0.12);
        };

        $printHeader = function () use (
            $pdf,
            $columnWidths,
            $tableStartX,
            $tableWidth,
            &$currentTableTop,
            $reportLabel,
            $firstColumnLabel,
            $secondColumnLabel,
            $secondColumnAlign,
            $hideSecondColumn
        ) {
            $currentTableTop = $pdf->GetY();
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->Rect($tableStartX, $pdf->GetY(), $tableWidth, 7, 'DF');
            $pdf->SetXY($tableStartX + 2.0, $pdf->GetY() + 1.2);
            $pdf->SetTextColor(37, 99, 235);
            $pdf->SetFont('Helvetica', 'B', 6.6);
            $sectionLabel = $reportLabel ? strtoupper(trim((string) $reportLabel)) : 'SCHOOLWISE EVALUATION';
            $pdf->Cell($tableWidth - 4, 3.2, $this->text($sectionLabel), 0, 0, 'L');

            $startX = $tableStartX;
            $y = $pdf->GetY() + 5.8;
            $headerFill = [244, 241, 177];
            $headerText = [8, 39, 109];
            $border = [100, 116, 139];
            $pdf->SetFillColor(...$headerFill);
            $pdf->SetDrawColor(...$border);
            $pdf->SetTextColor(...$headerText);
            $pdf->SetLineWidth(0.24);
            $pdf->SetFont('Helvetica', 'B', 5.4);
            $pdf->Rect($startX, $y, $tableWidth, 9.2, 'F');

            $pdf->SetXY($startX, $y);
            $pdf->Cell($columnWidths['sn'], 9.2, 'S/N', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['first'], 9.2, $firstColumnLabel, 1, 0, 'L', true);
            if (!$hideSecondColumn) {
                $pdf->Cell($columnWidths['second'], 9.2, $secondColumnLabel, 1, 0, $secondColumnAlign, true);
            }

            $pdf->Cell($columnWidths['metric'] * 3, 3.4, 'REG', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'] * 4, 3.4, 'ABS', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'] * 4, 3.4, 'SAT', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'] * 4, 3.4, 'INC', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'], 9.2, 'A', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'], 9.2, 'B', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'], 9.2, 'C', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'] * 4, 3.4, 'A-C', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'], 9.2, 'D', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'] * 4, 3.4, 'A-D', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric'], 9.2, 'E', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['average'], 9.2, 'AVG', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['grd'], 9.2, 'GRD', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['pos'], 9.2, 'POS', 1, 0, 'C', true);

            $secondRowX = $startX
                + $columnWidths['sn']
                + $columnWidths['first']
                + ($hideSecondColumn ? 0 : $columnWidths['second']);

            $pdf->SetXY($secondRowX, $y + 3.4);

            foreach (['M', 'F', 'T'] as $label) {
                $pdf->Cell($columnWidths['metric'], 5.8, $label, 1, 0, 'C', true);
            }

            foreach (range(1, 3) as $unused) {
                foreach (['M', 'F', 'T', '%'] as $label) {
                    $pdf->Cell($columnWidths['metric'], 5.8, $label, 1, 0, 'C', true);
                }
            }

            $pdf->SetX($pdf->GetX() + ($columnWidths['metric'] * 3));
            foreach (['M', 'F', 'T', '%'] as $label) {
                $pdf->Cell($columnWidths['metric'], 5.8, $label, 1, 0, 'C', true);
            }

            $pdf->SetX($pdf->GetX() + $columnWidths['metric']);
            foreach (['M', 'F', 'T', '%'] as $label) {
                $pdf->Cell($columnWidths['metric'], 5.8, $label, 1, 0, 'C', true);
            }

            $pdf->SetY($y + 9.2);
            $pdf->SetLineWidth(0.18);
        };

        $printHeader();

            foreach ($rows as $index => $row) {
            if ($pdf->GetY() > 394) {
                $drawOuterBorder();
                $pdf->AddPage();
                $this->renderSummary($pdf, (object) ['name' => $options['summary']['region_name'] ?? ''], $options['summary'] ?? []);
                $printHeader();
            }

            $pdf->SetX($tableStartX);
            $fill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $pdf->SetFillColor(...$fill);
            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);
            $pdf->SetFont('Helvetica', '', 5.7);
            $pdf->SetTextColor(8, 39, 109);

            $cells = [
                $index + 1,
                (string) ($row[$firstColumnKey] ?? ''),
                ...($hideSecondColumn ? [] : [(string) ($row[$secondColumnKey] ?? '')]),
                $row['registered']['m'] ?? 0, $row['registered']['f'] ?? 0, $row['registered']['t'] ?? 0,
                $row['absent']['m'] ?? 0, $row['absent']['f'] ?? 0, $row['absent']['t'] ?? 0, number_format((float) ($row['absent']['pct'] ?? 0), 0),
                $row['sat']['m'] ?? 0, $row['sat']['f'] ?? 0, $row['sat']['t'] ?? 0, number_format((float) ($row['sat']['pct'] ?? 0), 0),
                $row['inc']['m'] ?? 0, $row['inc']['f'] ?? 0, $row['inc']['t'] ?? 0, number_format((float) ($row['inc']['pct'] ?? 0), 0),
                $row['grades']['a']['t'] ?? 0,
                $row['grades']['b']['t'] ?? 0,
                $row['grades']['c']['t'] ?? 0,
                $row['pass_ac']['m'] ?? 0, $row['pass_ac']['f'] ?? 0, $row['pass_ac']['t'] ?? 0, number_format((float) ($row['pass_ac']['pct'] ?? 0), 0),
                $row['grades']['d']['t'] ?? 0,
                $row['pass_ad']['m'] ?? 0, $row['pass_ad']['f'] ?? 0, $row['pass_ad']['t'] ?? 0, number_format((float) ($row['pass_ad']['pct'] ?? 0), 0),
                $row['grades']['e']['t'] ?? 0,
                is_null($row['avg_marks'] ?? null) ? '-' : number_format((float) $row['avg_marks'], 2),
                $row['avg_grade'] ?? '-',
                $row['pos'] ?? '',
            ];

            foreach ($flatHeaders as $cellIndex => [, $width]) {
                $align = match ($cellIndex) {
                    1 => 'L',
                    2 => $hideSecondColumn ? 'C' : $secondColumnAlign,
                    default => 'C',
                };

                if ($cellIndex === count($flatHeaders) - 1 || $cellIndex === count($flatHeaders) - 2) {
                    $pdf->SetFont('Helvetica', 'B', 5.7);
                } else {
                    $pdf->SetFont('Helvetica', '', 5.7);
                }

                if ($cellIndex === 1 || ($cellIndex === 2 && !$hideSecondColumn)) {
                    $this->drawFittedCell($pdf, $width, 4.8, (string) ($cells[$cellIndex] ?? ''), $align, true, 5.7);
                    continue;
                }

                $limit = 8;
                $pdf->Cell($width, 4.8, $this->text((string) ($cells[$cellIndex] ?? ''), $limit), 1, 0, $align, true);
            }
            $pdf->Ln();
        }

        if ($pdf->GetY() > 394) {
            $drawOuterBorder();
            $pdf->AddPage();
            $this->renderSummary($pdf, (object) ['name' => $options['summary']['region_name'] ?? ''], $options['summary'] ?? []);
            $printHeader();
        }

        $pdf->SetFont('Helvetica', 'B', 5.9);
        $pdf->SetFillColor(255, 250, 205);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->SetLineWidth(0.18);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetX($tableStartX);

        $pdf->Cell(
            $columnWidths['sn'] + $columnWidths['first'] + ($hideSecondColumn ? 0 : $columnWidths['second']),
            5.2,
            'TOTAL',
            1,
            0,
            'C',
            true
        );

        $pdf->SetFont('Helvetica', '', 5.9);

        $totalCells = [
            $total['registered']['m'] ?? 0, $total['registered']['f'] ?? 0, $total['registered']['t'] ?? 0,
            $total['absent']['m'] ?? 0, $total['absent']['f'] ?? 0, $total['absent']['t'] ?? 0, number_format((float) ($total['absent']['pct'] ?? 0), 1),
            $total['sat']['m'] ?? 0, $total['sat']['f'] ?? 0, $total['sat']['t'] ?? 0, number_format((float) ($total['sat']['pct'] ?? 0), 1),
            $total['inc']['m'] ?? 0, $total['inc']['f'] ?? 0, $total['inc']['t'] ?? 0, number_format((float) ($total['inc']['pct'] ?? 0), 1),
            $total['grades']['a']['t'] ?? 0,
            $total['grades']['b']['t'] ?? 0,
            $total['grades']['c']['t'] ?? 0,
            $total['pass_ac']['m'] ?? 0, $total['pass_ac']['f'] ?? 0, $total['pass_ac']['t'] ?? 0, number_format((float) ($total['pass_ac']['pct'] ?? 0), 1),
            $total['grades']['d']['t'] ?? 0,
            $total['pass_ad']['m'] ?? 0, $total['pass_ad']['f'] ?? 0, $total['pass_ad']['t'] ?? 0, number_format((float) ($total['pass_ad']['pct'] ?? 0), 1),
            $total['grades']['e']['t'] ?? 0,
            '',
            '',
            '',
        ];

        $metricStartIndex = $hideSecondColumn ? 2 : 3;
        foreach (array_slice($flatHeaders, $metricStartIndex) as $cellIndex => [, $width]) {
            $value = $totalCells[$cellIndex] ?? '';
            $pdf->Cell($width, 5.2, $this->text((string) $value, 8), 1, 0, 'C', true);
        }

        $pdf->Ln();
        $drawOuterBorder();
    }
}
