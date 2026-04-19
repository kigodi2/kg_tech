<?php

namespace App\Services\ExamTypes;

class PsleTimetableFpdfService
{
    public function generate(string $outputPath): void
    {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $pdf = new class($this) extends \FPDF {
            public function __construct(
                private PsleTimetableFpdfService $service
            ) {
                parent::__construct('P', 'mm', 'A3');
                $this->SetMargins(10, 10, 10);
                $this->SetAutoPageBreak(true, 12);
                $this->AliasNbPages();
            }

            public function Footer(): void
            {
                $this->SetY(-10);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7);
                $this->Cell(0, 4, $this->service->text('PSLE zonal timetable export'), 0, 0, 'L');
                $this->Cell(0, 4, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        $pdf->AddPage();
        $this->renderHeader($pdf);
        $this->renderTable($pdf);
        $this->renderSourceNote($pdf);

        $pdf->Output('F', $outputPath);
    }

    public function text(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $ascii !== false ? $ascii : $value;
    }

    private function renderHeader(\FPDF $pdf): void
    {
        $pageWidth = 297.0;
        $emblem = public_path('images/emblem.png');

        if (is_file($emblem)) {
            $pdf->Image($emblem, 11, 12, 24, 24);
            $pdf->Image($emblem, $pageWidth - 35, 12, 24, 24);
        }

        $titleX = 38;
        $titleWidth = 221;

        $pdf->SetXY($titleX, 13);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell($titleWidth, 7, $this->text('OFISI YA WAZIRI MKUU'), 0, 1, 'C');
        $pdf->SetX($titleX);
        $pdf->Cell($titleWidth, 7, $this->text('TAWALA ZA MIKOA NA SERIKALI ZA MITAA'), 0, 1, 'C');
        $pdf->SetX($titleX);
        $pdf->Cell($titleWidth, 7, $this->text('KANDA MAALUMU YA KITAALUMA'), 0, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetX(10);
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(277, 7, $this->text('RATIBA YA MTIHANI WA UTAMILIFU DARASA LA SABA KANDA YA KITAALUMA'), 0, 1, 'C');
        $pdf->SetX(10);
        $pdf->Cell(277, 7, $this->text('(TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, LINDI, MTWARA NA TABORA)'), 0, 1, 'C');
        $pdf->Ln(1.5);
        $pdf->SetX(10);
        $pdf->Cell(277, 7, $this->text('MEI, 2026'), 0, 1, 'C');
        $pdf->Ln(8);
    }

    private function renderTable(\FPDF $pdf): void
    {
        $x = 10.0;
        $y = $pdf->GetY();
        $wDate = 48.0;
        $wTime = 42.0;
        $wCode = 47.0;
        $wSubject = 140.0;
        $rowHeight = 15.0;

        $headerFill = [217, 217, 217];
        $breakFill = [217, 217, 217];
        $border = [0, 0, 0];

        $pdf->SetLineWidth(0.45);
        $pdf->SetDrawColor(...$border);
        $pdf->SetFillColor(...$headerFill);
        $pdf->SetFont('Helvetica', 'B', 11);

        $pdf->SetXY($x, $y);
        $pdf->Cell($wDate, $rowHeight, $this->text('TAREHE NA SIKU'), 1, 0, 'C', true);
        $pdf->Cell($wTime, $rowHeight, $this->text('MUDA (SAA)'), 1, 0, 'C', true);
        $pdf->Cell($wCode, $rowHeight, $this->text('NAMBA MFICHO'), 1, 0, 'C', true);
        $pdf->Cell($wSubject, $rowHeight, $this->text('SOMO'), 1, 1, 'C', true);

        $days = [
            [
                'label' => "20.05.2026\nJUMATANO",
                'rows' => [
                    ['time' => '2:00 -- 3:40', 'code' => '01', 'subject' => 'KISWAHILI', 'break' => false],
                    ['time' => '3:40 -- 4:30', 'code' => '', 'subject' => 'MAPUMZIKO', 'break' => true],
                    ['time' => '4:30 -- 6:30', 'code' => '04', 'subject' => 'HISABATI', 'break' => false],
                    ['time' => '4:30 -- 6:30', 'code' => '04E', 'subject' => 'MATHEMATICS', 'break' => false],
                    ['time' => '6:30 -- 8:30', 'code' => '', 'subject' => 'MAPUMZIKO', 'break' => true],
                    ['time' => '8:30 -- 10:00', 'code' => '06', 'subject' => 'URAIA NA MAADILI', 'break' => false],
                    ['time' => '8:30 -- 10:00', 'code' => '06E', 'subject' => 'CIVIC AND MORAL EDUCATION', 'break' => false],
                ],
            ],
            [
                'label' => "21.05.2026\nALHAMISI",
                'rows' => [
                    ['time' => '2:00 -- 3:40', 'code' => '02', 'subject' => 'ENGLISH LANGUAGE', 'break' => false],
                    ['time' => '3:40 -- 4:30', 'code' => '', 'subject' => 'MAPUMZIKO', 'break' => true],
                    ['time' => '4:30 -- 6:00', 'code' => '05', 'subject' => 'SAYANSI NA TEKNOLOJIA', 'break' => false],
                    ['time' => '4:30 -- 6:00', 'code' => '05E', 'subject' => 'SCIENCE AND TECHNOLOGY', 'break' => false],
                    ['time' => '6:00 -- 8:00', 'code' => '', 'subject' => 'MAPUMZIKO', 'break' => true],
                    ['time' => '8:00 -- 9:30', 'code' => '03', 'subject' => 'MAARIFA YA JAMII NA STADI ZA KAZI', 'break' => false],
                    ['time' => '8:00 -- 9:30', 'code' => '03E', 'subject' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS', 'break' => false],
                ],
            ],
        ];

        foreach ($days as $index => $day) {
            $groupStartY = $pdf->GetY();
            $groupHeight = count($day['rows']) * $rowHeight;

            foreach ($day['rows'] as $row) {
                $fill = $row['break'];
                $pdf->SetXY($x + $wDate, $pdf->GetY());
                $pdf->SetFillColor(...($fill ? $breakFill : [255, 255, 255]));
                $pdf->SetFont('Helvetica', $fill ? 'B' : '', 11);
                $pdf->Cell($wTime, $rowHeight, $this->text($row['time']), 1, 0, 'C', $fill);
                $pdf->Cell($wCode, $rowHeight, $this->text($row['code']), 1, 0, 'C', $fill);
                $pdf->Cell($wSubject, $rowHeight, $this->text($row['subject']), 1, 1, $fill ? 'C' : 'L', $fill);
            }

            $pdf->SetXY($x, $groupStartY);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($x, $groupStartY, $wDate, $groupHeight, 'D');
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetXY($x, $groupStartY + (($groupHeight - 16) / 2) - 4);
            $pdf->MultiCell($wDate, 8, $this->text($day['label']), 0, 'C');

            if ($index === 0) {
                $pdf->Line($x, $groupStartY + $groupHeight, $x + $wDate + $wTime + $wCode + $wSubject, $groupStartY + $groupHeight);
            }
        }
    }

    private function renderSourceNote(\FPDF $pdf): void
    {
        $pdf->Ln(8);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->MultiCell(
            277,
            5.5,
            $this->text('Chanzo: Ratiba hii imeundwa kwa muundo wa PDF wa A3 portrait kwa kuzingatia LaTeX ya "RATIBA YA MTIHANI WA UTAMILIFU DARASA LA SABA KANDA YA KITAALUMA, MEI 2026".')
        );
    }
}
