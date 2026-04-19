<?php

namespace App\Services\Results;

class AcseeRegionalSchoolwiseFpdfService
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
        array $total,
        string $outputPath,
        ?string $reportLabel = null,
        array $options = []
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $pdf = new class($this, $region, $examYearValue, $reportLabel) extends \FPDF {
            public function __construct(
                private AcseeRegionalSchoolwiseFpdfService $service,
                private object $region,
                private int $examYearValue,
                private ?string $reportLabel
            ) {
                parent::__construct('L', 'mm', 'A3');
                $this->SetMargins(8, 10, 8);
                $this->SetAutoPageBreak(true, 10);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                $this->SetFillColor(248, 250, 252);
                $this->Rect(0, 0, 420, 297, 'F');

                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, 420, 4, 'F');

                $emblem = public_path('images/emblem.png');
                if (is_file($emblem)) {
                    $this->Image($emblem, 8, 9, 13, 13);
                    $this->Image($emblem, 399, 9, 13, 13);
                }

                $titleBlockX = 28;
                $titleBlockWidth = 364;

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
                $this->Cell($titleBlockWidth, 3.2, $this->service->text('TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA'), 0, 1, 'C');
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 10.5);
                $this->SetX($titleBlockX);
                $titleSuffix = $this->reportLabel ? ' - ' . strtoupper(trim((string) $this->reportLabel)) : '';
                $this->Cell(
                    $titleBlockWidth,
                    4.6,
                    $this->service->text(
                        'FORM SIX ZONAL JOINT MOCK EVALUATION RESULTS - FEBRUARY, '
                        . $this->examYearValue
                        . ' - '
                        . strtoupper((string) $this->region->name)
                        . $titleSuffix
                    ),
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
                $this->Cell(404, 4.6, $this->service->text($banner), 0, 1, 'C', true);
                $this->Ln(0.5);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->Cell(0, 4, $this->service->text($this->service->footerLabel($this->reportLabel, 'ACSEE regional schoolwise evaluation export')), 0, 0, 'L');
                $this->Cell(0, 4, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
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

    private function renderTable(\FPDF $pdf, array $rows, array $total, ?string $reportLabel = null, array $options = []): void
    {
        $tableStartX = 8.0;
        $tableWidth = 404.0;
        $hideSecondColumn = (bool) ($options['hide_second_column'] ?? false);
        $firstColumnLabel = strtoupper((string) ($options['first_column_label'] ?? 'COUNCIL'));
        $secondColumnLabel = strtoupper((string) ($options['second_column_label'] ?? 'SCHOOL'));
        $firstColumnKey = (string) ($options['first_column_key'] ?? 'council');
        $secondColumnKey = (string) ($options['second_column_key'] ?? 'school');
        $secondColumnAlign = strtoupper((string) ($options['second_column_align'] ?? 'L'));
        $columnWidths = [
            'sn' => 10,
            'council' => (float) ($options['first_column_width'] ?? 40),
            'school' => $hideSecondColumn ? 0.0 : (float) ($options['second_column_width'] ?? 94),
            'metric3' => (float) ($options['metric3_width'] ?? 8.5),
            'metric4' => (float) ($options['metric4_width'] ?? 8.25),
            'gpa' => (float) ($options['gpa_width'] ?? 18),
            'pos' => (float) ($options['pos_width'] ?? 10),
        ];

        $flatHeaders = [
            ['S/N', $columnWidths['sn']],
            [$firstColumnLabel, $columnWidths['council']],
            ...($hideSecondColumn ? [] : [[$secondColumnLabel, $columnWidths['school']]]),
            ['REG M', $columnWidths['metric3']], ['REG F', $columnWidths['metric3']], ['REG T', $columnWidths['metric3']],
            ['ABS M', $columnWidths['metric4']], ['ABS F', $columnWidths['metric4']], ['ABS T', $columnWidths['metric4']], ['ABS %', $columnWidths['metric4']],
            ['SAT M', $columnWidths['metric4']], ['SAT F', $columnWidths['metric4']], ['SAT T', $columnWidths['metric4']], ['SAT %', $columnWidths['metric4']],
            ['INC M', $columnWidths['metric4']], ['INC F', $columnWidths['metric4']], ['INC T', $columnWidths['metric4']], ['INC %', $columnWidths['metric4']],
            ['I', $columnWidths['metric4']], ['II', $columnWidths['metric4']], ['III', $columnWidths['metric4']],
            ['I-III M', $columnWidths['metric4']], ['I-III F', $columnWidths['metric4']], ['I-III T', $columnWidths['metric4']], ['I-III %', $columnWidths['metric4']],
            ['IV', $columnWidths['metric4']],
            ['I-IV M', $columnWidths['metric4']], ['I-IV F', $columnWidths['metric4']], ['I-IV T', $columnWidths['metric4']], ['I-IV %', $columnWidths['metric4']],
            ['0', $columnWidths['metric4']],
            ['GPA', $columnWidths['gpa']],
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
            $pdf->Rect($tableStartX, $pdf->GetY(), $tableWidth, 9, 'DF');
            $pdf->SetXY($tableStartX + 4, $pdf->GetY() + 1.5);
            $pdf->SetTextColor(37, 99, 235);
            $pdf->SetFont('Helvetica', 'B', 8);
            $sectionLabel = $reportLabel
                ? 'DETAILED ' . strtoupper(trim((string) $reportLabel)) . ' TABLE'
                : 'DETAILED SCHOOLWISE TABLE';
            $pdf->Cell(220, 4, $this->text($sectionLabel), 0, 0, 'L');

            $startX = $tableStartX;
            $y = $pdf->GetY() + 7.5;
            $headerFill = [244, 241, 177];
            $headerText = [8, 39, 109];
            $border = [100, 116, 139];
            $pdf->SetFillColor(...$headerFill);
            $pdf->SetDrawColor(...$border);
            $pdf->SetTextColor(...$headerText);
            $pdf->SetLineWidth(0.24);

            $pdf->SetXY($startX, $y);
            $pdf->SetFont('Helvetica', 'B', 9);

            $pdf->Cell($columnWidths['sn'], 12, 'S/N', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['council'], 12, $firstColumnLabel, 1, 0, 'L', true);
            if (!$hideSecondColumn) {
                $pdf->Cell($columnWidths['school'], 12, $secondColumnLabel, 1, 0, $secondColumnAlign, true);
            }
            $pdf->Cell($columnWidths['metric3'] * 3, 4, 'REGISTERED', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'] * 4, 4, 'ABSENT', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'] * 4, 4, 'SAT', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'] * 4, 4, 'INC', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'] * 13, 4, 'DIVISION', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['gpa'], 12, 'GPA', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['pos'], 12, 'POS', 1, 0, 'C', true);

            $pdf->SetXY($startX + $columnWidths['sn'] + $columnWidths['council'] + $columnWidths['school'], $y + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell($columnWidths['metric3'], 8, 'M', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric3'], 8, 'F', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric3'], 8, 'T', 1, 0, 'C', true);

            foreach (['ABSENT', 'SAT', 'INC'] as $unused) {
                $pdf->Cell($columnWidths['metric4'], 8, 'M', 1, 0, 'C', true);
                $pdf->Cell($columnWidths['metric4'], 8, 'F', 1, 0, 'C', true);
                $pdf->Cell($columnWidths['metric4'], 8, 'T', 1, 0, 'C', true);
                $pdf->Cell($columnWidths['metric4'], 8, '%', 1, 0, 'C', true);
            }

            foreach (['I', 'II', 'III'] as $label) {
                $pdf->Cell($columnWidths['metric4'], 8, $label, 1, 0, 'C', true);
            }
            $pdf->Cell($columnWidths['metric4'] * 4, 4, 'I - III', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'], 8, 'IV', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'] * 4, 4, 'I - IV', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'], 8, '0', 1, 0, 'C', true);

            $divisionSecondRowX = $startX
                + $columnWidths['sn']
                + $columnWidths['council']
                + $columnWidths['school']
                + ($columnWidths['metric3'] * 3)
                + ($columnWidths['metric4'] * 12)
                + ($columnWidths['metric4'] * 3);

            $pdf->SetXY($divisionSecondRowX, $y + 8);
            foreach (range(1, 4) as $unused) {
                $pdf->Cell($columnWidths['metric4'], 4, ['M', 'F', 'T', '%'][$unused - 1], 1, 0, 'C', true);
            }
            $pdf->SetX($pdf->GetX() + $columnWidths['metric4']);
            $pdf->Cell($columnWidths['metric4'], 4, 'M', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'], 4, 'F', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'], 4, 'T', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['metric4'], 4, '%', 1, 0, 'C', true);
            $pdf->SetY($y + 12);
            $pdf->SetLineWidth(0.18);
        };

        $printHeader();

        foreach ($rows as $index => $row) {
            if ($pdf->GetY() > 275) {
                $drawOuterBorder();
                $pdf->AddPage();
                $printHeader();
            }

            $pdf->SetX($tableStartX);
            $fill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $pdf->SetFillColor(...$fill);
            $pdf->SetDrawColor(100, 116, 139);
            $pdf->SetLineWidth(0.18);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(30, 41, 59);

            $cells = [
                $index + 1,
                $this->text((string) ($row[$firstColumnKey] ?? ''), 40),
                ...($hideSecondColumn ? [] : [$this->text((string) ($row[$secondColumnKey] ?? ''))]),
                $row['registered']['m'] ?? 0, $row['registered']['f'] ?? 0, $row['registered']['t'] ?? 0,
                $row['absent']['m'] ?? 0, $row['absent']['f'] ?? 0, $row['absent']['t'] ?? 0, number_format((float) ($row['absent']['pct'] ?? 0), 0),
                $row['sat']['m'] ?? 0, $row['sat']['f'] ?? 0, $row['sat']['t'] ?? 0, number_format((float) ($row['sat']['pct'] ?? 0), 0),
                $row['inc']['m'] ?? 0, $row['inc']['f'] ?? 0, $row['inc']['t'] ?? 0, number_format((float) ($row['inc']['pct'] ?? 0), 0),
                $row['division']['i']['t'] ?? 0,
                $row['division']['ii']['t'] ?? 0,
                $row['division']['iii']['t'] ?? 0,
                $row['division']['i_iii']['m'] ?? 0, $row['division']['i_iii']['f'] ?? 0, $row['division']['i_iii']['t'] ?? 0, number_format((float) ($row['division']['i_iii']['pct'] ?? 0), 0),
                $row['division']['iv']['t'] ?? 0,
                $row['division']['i_iv']['m'] ?? 0, $row['division']['i_iv']['f'] ?? 0, $row['division']['i_iv']['t'] ?? 0, number_format((float) ($row['division']['i_iv']['pct'] ?? 0), 0),
                $row['division']['zero']['t'] ?? 0,
                is_null($row['gpa'] ?? null) ? '-' : number_format((float) $row['gpa'], 4),
                $row['pos'] ?? '',
            ];

            foreach ($flatHeaders as $cellIndex => [, $width]) {
                $align = match ($cellIndex) {
                    1 => 'L',
                    2 => $hideSecondColumn ? 'C' : $secondColumnAlign,
                    default => 'C',
                };
                if ($cellIndex === count($flatHeaders) - 1) {
                    $pdf->SetTextColor(37, 99, 235);
                    $pdf->SetFont('Helvetica', 'B', 9);
                } elseif ($cellIndex === count($flatHeaders) - 2) {
                    $pdf->SetTextColor(15, 23, 42);
                    $pdf->SetFont('Helvetica', 'B', 9);
                } elseif ($cellIndex === 2 && !$hideSecondColumn) {
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont('Helvetica', '', $secondColumnAlign === 'C' ? 9 : 8);
                } elseif ($cellIndex === 1) {
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont('Helvetica', '', 9);
                } elseif (in_array($cellIndex, [1, 2], true)) {
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont('Helvetica', '', $cellIndex === 2 ? 8 : 9);
                } else {
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont('Helvetica', '', 9);
                }

                $limit = $cellIndex === 1 ? 40 : ($cellIndex === 2 && !$hideSecondColumn ? ($secondColumnAlign === 'C' ? 8 : 0) : 12);
                $pdf->Cell($width, 6.2, $this->text((string) ($cells[$cellIndex] ?? ''), $limit), 1, 0, $align, true);
            }
            $pdf->Ln();
        }

        if ($pdf->GetY() > 275) {
            $drawOuterBorder();
            $pdf->AddPage();
            $printHeader();
        }

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(244, 241, 177);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->SetLineWidth(0.18);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetX($tableStartX);

        $pdf->Cell(
            $columnWidths['sn'] + $columnWidths['council'] + $columnWidths['school'],
            6.8,
            'TOTAL',
            1,
            0,
            'C',
            true
        );

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(15, 23, 42);

        $totalCells = [
            $total['registered']['m'] ?? 0, $total['registered']['f'] ?? 0, $total['registered']['t'] ?? 0,
            $total['absent']['m'] ?? 0, $total['absent']['f'] ?? 0, $total['absent']['t'] ?? 0, number_format((float) ($total['absent']['pct'] ?? 0), 1),
            $total['sat']['m'] ?? 0, $total['sat']['f'] ?? 0, $total['sat']['t'] ?? 0, number_format((float) ($total['sat']['pct'] ?? 0), 1),
            $total['inc']['m'] ?? 0, $total['inc']['f'] ?? 0, $total['inc']['t'] ?? 0, number_format((float) ($total['inc']['pct'] ?? 0), 1),
            $total['division']['i']['t'] ?? 0,
            $total['division']['ii']['t'] ?? 0,
            $total['division']['iii']['t'] ?? 0,
            $total['division']['i_iii']['m'] ?? 0, $total['division']['i_iii']['f'] ?? 0, $total['division']['i_iii']['t'] ?? 0, number_format((float) ($total['division']['i_iii']['pct'] ?? 0), 2),
            $total['division']['iv']['t'] ?? 0,
            $total['division']['i_iv']['m'] ?? 0, $total['division']['i_iv']['f'] ?? 0, $total['division']['i_iv']['t'] ?? 0, number_format((float) ($total['division']['i_iv']['pct'] ?? 0), 2),
            $total['division']['zero']['t'] ?? 0,
            '', '',
        ];

        $metricStartIndex = $hideSecondColumn ? 2 : 3;
        foreach (array_slice($flatHeaders, $metricStartIndex) as $cellIndex => [, $width]) {
            $value = $totalCells[$cellIndex] ?? '';
            $pdf->Cell($width, 6.8, $this->text((string) $value, 12), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $drawOuterBorder();
    }
}
