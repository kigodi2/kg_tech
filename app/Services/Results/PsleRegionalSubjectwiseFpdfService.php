<?php

namespace App\Services\Results;

class PsleRegionalSubjectwiseFpdfService
{
    public const PAGE_WIDTH = 297.0;
    public const PAGE_HEIGHT = 210.0;
    public const LEFT_MARGIN = 8.0;
    public const RIGHT_MARGIN = 8.0;
    public const CONTENT_WIDTH = self::PAGE_WIDTH - self::LEFT_MARGIN - self::RIGHT_MARGIN;

    public function generate(object $region, int $examYearValue, array $rows, array $summary, string $outputPath, string $reportLabel): void
    {
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
                private PsleRegionalSubjectwiseFpdfService $service,
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
                $this->Rect(0, 0, PsleRegionalSubjectwiseFpdfService::PAGE_WIDTH, PsleRegionalSubjectwiseFpdfService::PAGE_HEIGHT, 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, PsleRegionalSubjectwiseFpdfService::PAGE_WIDTH, 4, 'F');
                $emblem = public_path('images/emblem.png');
                if (is_file($emblem)) {
                    $this->Image($emblem, 8, 9, 13, 13);
                    $this->Image($emblem, 276, 9, 13, 13);
                }
                $this->SetXY(28, 8.8);
                $this->SetTextColor(8, 39, 109);
                $this->SetFont('Helvetica', 'B', 9.8);
                $this->Cell(241, 4.6, "PRIME MINISTER'S OFFICE", 0, 1, 'C');
                $this->SetX(28);
                $this->Cell(241, 4.6, 'REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT', 0, 1, 'C');
                $this->SetX(28);
                $this->Cell(241, 4.6, 'ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)', 0, 1, 'C');
                $this->SetX(28);
                $this->Cell(241, 4.8, 'STANDARD SEVEN ZONAL JOINT MOCK EVALUATION RESULTS - MAY, ' . $this->examYearValue, 0, 1, 'C');
                $this->SetX(28);
                $this->Cell(241, 4.8, strtoupper((string) $this->region->name) . ' - ' . strtoupper($this->reportLabel), 0, 1, 'C');
                $this->Ln(0.9);
                $this->SetX(8);
                $this->SetDrawColor(191, 219, 254);
                $this->SetFillColor(219, 234, 254);
                $this->SetTextColor(30, 64, 175);
                $this->SetFont('Helvetica', 'B', 6.7);
                $banner = 'PROFESSIONAL PSLE SUBJECTWISE PERFORMANCE REPORT';
                $this->Cell(PsleRegionalSubjectwiseFpdfService::CONTENT_WIDTH, 4.6, $banner, 0, 1, 'C', true);
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
                $this->Cell(PsleRegionalSubjectwiseFpdfService::CONTENT_WIDTH, 3.2, 'GENERATED: ' . $this->generatedAt . ' | IRMS NODE: ' . $this->node, 0, 1, 'R');
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
                $this->Cell(0, 3.6, 'STANDARD SEVEN ZONAL MOCK - SUBJECTWISE REPORT, ' . $this->examYearValue, 0, 0, 'L');
                $this->Cell(0, 3.6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderSummary($pdf, $region, $summary);
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

    private function text(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $ascii !== false ? $ascii : $value;
    }

    private function renderSummary(\FPDF $pdf, object $region, array $summary): void
    {
        $gradeSummary = $summary['grade_summary'] ?? [];
        $overall = $summary['overall'] ?? [];
        $startY = max($pdf->GetY() + 0.8, 35.8);
        $pdf->SetXY(8, $startY);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(8, $startY, self::CONTENT_WIDTH, 17.4, 'DF');
        $pdf->SetXY(8, $startY + 3.2);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 8.0);
        $pdf->Cell(self::CONTENT_WIDTH, 4.1, 'REGION: ' . strtoupper((string) $region->name), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 4.1, 'TOTAL SUBJECTS: ' . ($summary['subjects'] ?? 0) . ' | TOTAL PASSED CANDIDATES (A - C): ' . ($overall['passed'] ?? 0), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 4.1, 'EXAMINATION CENTRE GPA: ' . (!empty($overall['gpa']) ? number_format((float) $overall['gpa'], 4) : '-'), 0, 1, 'L');
        $pdf->Cell(self::CONTENT_WIDTH, 4.1, 'GRADE SUMMARY (T): REGIST ' . data_get($gradeSummary, 'T.REGIST', 0) . ' | SAT ' . data_get($gradeSummary, 'T.SAT', 0) . ' | A ' . data_get($gradeSummary, 'T.A', 0) . ' | B ' . data_get($gradeSummary, 'T.B', 0) . ' | C ' . data_get($gradeSummary, 'T.C', 0) . ' | D ' . data_get($gradeSummary, 'T.D', 0) . ' | E ' . data_get($gradeSummary, 'T.E', 0), 0, 1, 'L');
        $pdf->Ln(1.2);
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
        $widths = ['code'=>12,'name'=>78,'reg'=>14,'sat'=>14,'abs'=>14,'m'=>12,'ac'=>15,'ad'=>15,'avg'=>16,'grd'=>12,'comp'=>54];
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetX(8);
        $pdf->Cell(self::CONTENT_WIDTH, 5.0, strtoupper($this->text($reportLabel)), 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', 'B', 7.0);
        $pdf->SetX(8);
        foreach ([
            ['CODE',$widths['code'],'C'],['SUBJECT NAME',$widths['name'],'L'],['REGIST',$widths['reg'],'C'],
            ['SAT',$widths['sat'],'C'],['ABS',$widths['abs'],'C'],['A',$widths['m'],'C'],['B',$widths['m'],'C'],
            ['C',$widths['m'],'C'],['A - C',$widths['ac'],'C'],['D',$widths['m'],'C'],['A - D',$widths['ad'],'C'],
            ['E',$widths['m'],'C'],['AVG',$widths['avg'],'C'],['GRD',$widths['grd'],'C'],['COMPETENCY LEVEL',$widths['comp'],'L']
        ] as [$label,$w,$align]) {
            $pdf->Cell($w,4.6,$label,1,0,$align,true);
        }
        $pdf->Ln();

        $pdf->SetFillColor(255,253,231);
        $pdf->SetTextColor(0,0,128);
        foreach ($rows as $row) {
            $pdf->SetX(8);
            $this->fitCell($pdf,$widths['code'],4.7,(string)($row['code']??'-'),'C');
            $this->fitCell($pdf,$widths['name'],4.7,(string)($row['name']??'-'),'L');
            $this->fitCell($pdf,$widths['reg'],4.7,(string)($row['registered']??0),'C');
            $this->fitCell($pdf,$widths['sat'],4.7,(string)($row['sat']??0),'C');
            $this->fitCell($pdf,$widths['abs'],4.7,(string)($row['abs']??0),'C');
            $this->fitCell($pdf,$widths['m'],4.7,(string)($row['grade_a']??0),'C');
            $this->fitCell($pdf,$widths['m'],4.7,(string)($row['grade_b']??0),'C');
            $this->fitCell($pdf,$widths['m'],4.7,(string)($row['grade_c']??0),'C');
            $this->fitCell($pdf,$widths['ac'],4.7,(string)($row['a_to_c']??0),'C');
            $this->fitCell($pdf,$widths['m'],4.7,(string)($row['grade_d']??0),'C');
            $this->fitCell($pdf,$widths['ad'],4.7,(string)($row['a_to_d']??0),'C');
            $this->fitCell($pdf,$widths['m'],4.7,(string)($row['grade_e']??0),'C');
            $this->fitCell($pdf,$widths['avg'],4.7,number_format((float)($row['avg_marks']??0),0),'C');
            $this->fitCell($pdf,$widths['grd'],4.7,(string)($row['grade']??'-'),'C');
            $this->fitCell($pdf,$widths['comp'],4.7,(string)data_get($row,'competence.label','-'),'L');
            $pdf->Ln();
        }
    }
}
