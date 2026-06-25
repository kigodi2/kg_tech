<?php

namespace App\Services\Results;

use Illuminate\Support\Facades\Log;

class PsleZonalTasidoTaarifaPdfService
{
    public function generate(array $data, string $outputPath): void
    {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }
        require_once app_path('Support/Pdf/fpdf.php');

        $pdf = new class('P', 'mm', 'A4') extends \FPDF {
            public bool $isCoverPage = true;
            public bool $suppressHeaderFooter = false;
            public string $zoneName = 'TASIDO';
            public int $examYear = 2026;
            public string $primaryFont = 'Helvetica';
            public string $footerOffice = 'Ofisi ya Waziri Mkuu - TAMISEMI';
            public float $defMarginTop = 25.4;
            public float $defMarginBottom = 25.4;
            public float $defMarginLeft = 10.0;
            public float $defMarginRight = 10.0;

            public function setupCustomFonts(string $fontFamily): void
            {
                $fontDir = app_path('Support/Pdf/font');
                $poppinsLoaded = false;
                if (file_exists($fontDir . '/poppins.php')) {
                    $this->AddFont('Poppins', '', 'poppins.php');
                    $this->AddFont('Poppins', 'B', 'poppinsb.php');
                    $this->AddFont('Poppins', 'I', 'poppinsi.php');
                    $this->AddFont('Poppins', 'BI', 'poppinsbi.php');
                    $poppinsLoaded = true;
                }

                $fontSetting = strtolower(trim($fontFamily));
                if ($fontSetting === 'times new roman') {
                    $this->primaryFont = 'Times';
                } elseif ($fontSetting === 'arial narrow') {
                    $this->primaryFont = 'Helvetica';
                } elseif ($fontSetting === 'maiandra gd') {
                    $this->primaryFont = 'Helvetica';
                } elseif (($fontSetting === 'default' || $fontSetting === 'poppins') && $poppinsLoaded) {
                    $this->primaryFont = 'Poppins';
                } else {
                    $this->primaryFont = 'Helvetica'; // default fallback
                }
            }

            public function initReport(string $fontFamily): void
            {
                $this->setupCustomFonts($fontFamily);
                $this->SetFont($this->primaryFont, '', 9.5);
            }

            public function pdfText(?string $text): string
            {
                $text = (string) $text;
                $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
                return $converted !== false ? $converted : $text;
            }

            public function usablePageWidth(): float
            {
                return $this->w - $this->lMargin - $this->rMargin;
            }

            public function Header(): void
            {
                if ($this->suppressHeaderFooter) {
                    return;
                }
                if ($this->isCoverPage || $this->PageNo() == 1) {
                    return;
                }

                $this->SetY(8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont($this->primaryFont, 'I', 7.5);
                $this->Cell(0, 4, $this->pdfText("TAARIFA YA TATHMINI YA MTIHANI WA MOCK KANDA YA " . strtoupper($this->zoneName) . " - " . $this->examYear), 0, 1, 'R');
                $this->SetDrawColor(203, 213, 225);
                $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
                $this->Ln(4);
            }

            public function Footer(): void
            {
                if ($this->suppressHeaderFooter) {
                    return;
                }
                if ($this->isCoverPage || $this->PageNo() == 1) {
                    return;
                }

                $this->SetY(-15);
                $this->SetDrawColor(203, 213, 225);
                $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
                $this->Ln(1);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont($this->primaryFont, '', 7.5);
                $this->Cell(100, 5, $this->pdfText($this->footerOffice), 0, 0, 'L');
                $this->Cell(0, 5, $this->pdfText("Ukurasa " . $this->PageNo() . " / {nb}"), 0, 0, 'R');
            }

            public function addPortraitPage(
                ?float $marginTop = null,
                ?float $marginBottom = null,
                ?float $marginLeft = null,
                ?float $marginRight = null
            ): void {
                $marginTop ??= $this->defMarginTop;
                $marginBottom ??= $this->defMarginBottom;
                $marginLeft ??= $this->defMarginLeft;
                $marginRight ??= $this->defMarginRight;

                $this->AddPage('P', 'A4');
                $this->SetMargins($marginLeft, $marginTop, $marginRight);
                $this->SetAutoPageBreak(true, $marginBottom);
                $this->SetXY($marginLeft, $marginTop);
            }

            public function addLandscapePage(
                ?float $marginTop = null,
                ?float $marginBottom = null,
                ?float $marginLeft = null,
                ?float $marginRight = null
            ): void {
                $marginTop ??= $this->defMarginTop;
                $marginBottom ??= $this->defMarginBottom;
                $marginLeft ??= $this->defMarginLeft;
                $marginRight ??= $this->defMarginRight;

                $this->AddPage('L', 'A4');
                $this->SetMargins($marginLeft, $marginTop, $marginRight);
                $this->SetAutoPageBreak(true, $marginBottom);
                $this->SetXY($marginLeft, $marginTop);
            }

            public function drawTanzaniaFlagCoverBorder(): void
            {
                // A4 portrait size in mm
                $pageWidth = 210;
                $pageHeight = 297;

                $borders = [
                    ['rgb' => [30, 135, 63],  'inset' => 8.0,  'width' => 0.35], // Green
                    ['rgb' => [252, 209, 22], 'inset' => 9.2,  'width' => 0.25], // Yellow
                    ['rgb' => [0, 0, 0],      'inset' => 10.4, 'width' => 0.25], // Black
                    ['rgb' => [252, 209, 22], 'inset' => 11.6, 'width' => 0.25], // Yellow
                    ['rgb' => [0, 163, 224],  'inset' => 12.8, 'width' => 0.35], // Blue
                ];

                foreach ($borders as $border) {
                    [$r, $g, $b] = $border['rgb'];

                    $this->SetDrawColor($r, $g, $b);
                    $this->SetLineWidth($border['width']);

                    $inset = $border['inset'];

                    $this->Rect(
                        $inset,
                        $inset,
                        $pageWidth - ($inset * 2),
                        $pageHeight - ($inset * 2)
                    );
                }

                // Reset drawing color and line width
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth(0.2);
            }

            public function addCoverPage(array $settings): void
            {
                $this->suppressHeaderFooter = true;

                $this->AddPage('P', 'A4');
                $this->SetAutoPageBreak(false);

                $this->drawTanzaniaFlagCoverBorder();

                $emblemPath = $settings['emblem_path'] ?? null;

                if ($emblemPath && file_exists($emblemPath)) {
                    $emblemWidth = 28;
                    $x = (210 - $emblemWidth) / 2;
                    $this->Image($emblemPath, $x, 32, $emblemWidth);
                } else {
                    $this->SetTextColor(185, 28, 28);
                    $this->SetFont($this->primaryFont, 'B', 8);
                    $this->SetXY(20, 32);
                    $this->Cell(170, 5, $this->pdfText('GOVERNMENT EMBLEM NOT CONFIGURED'), 0, 0, 'C');
                    $this->SetTextColor(0, 0, 0);
                }

                $this->SetTextColor(0, 0, 0);
                $this->SetFont($this->primaryFont, 'B', 11);
                $this->SetXY(20, 70);
                $this->MultiCell(
                    170,
                    5,
                    $this->pdfText("JAMHURI YA MUUNGANO WA TANZANIA\nOFISI YA WAZIRI MKUU\nTAWALA ZA MIKOA NA SERIKALI ZA MITAA"),
                    0,
                    'C'
                );

                $this->SetDrawColor(31, 95, 209);
                $this->SetLineWidth(0.2);
                $this->Line(30, 127, 180, 127);

                $this->SetTextColor(0, 0, 0);
                $this->SetFont($this->primaryFont, 'B', 11);
                $this->SetXY(25, 143);
                $this->MultiCell(
                    160,
                    5,
                    $this->pdfText("TAARIFA YA MTIHANI WA UTAMILIFU DARASA LA SABA\nMWAKA 2026 TASIDO"),
                    0,
                    'C'
                );

                $this->SetXY(25, 164);
                $this->MultiCell(
                    160,
                    5,
                    $this->pdfText("(TABORA, SINGIDA, IRINGA NA DODOMA)"),
                    0,
                    'C'
                );

                $this->SetFont($this->primaryFont, 'B', 9);
                $this->SetXY(32, 252);
                $this->MultiCell(
                    70,
                    4,
                    $this->pdfText("SEKRETARIETI YA KANDA,\nTASIDO\nDODOMA"),
                    0,
                    'L'
                );

                $this->SetXY(125, 252);
                $this->Cell(55, 4, $this->pdfText("JUNI, 2026"), 0, 0, 'R');

                $this->SetAutoPageBreak(true, 15);
                $this->suppressHeaderFooter = false;
            }

            public function getLMargin(): float { return $this->lMargin; }
            public function getRMargin(): float { return $this->rMargin; }
            public function getBMargin(): float { return $this->bMargin; }
            public function getPageWidth(): float { return $this->w; }
            public function getPageHeight(): float { return $this->h; }
            public function getCurOrientation(): string { return $this->CurOrientation; }
        };

        // Initialize PDF config
        $pdf->isCoverPage = true;
        $pdf->zoneName = 'TASIDO';
        $pdf->examYear = (int)$data['meta']['exam_year'];
        $pdf->footerOffice = str_replace("\n", " - ", $data['meta']['office_heading']);
        
        $pdf->defMarginTop = (float)($data['meta']['margin_top'] ?? 25.4);
        $pdf->defMarginBottom = (float)($data['meta']['margin_bottom'] ?? 25.4);
        $pdf->defMarginLeft = (float)($data['meta']['margin_left'] ?? 10.0);
        $pdf->defMarginRight = (float)($data['meta']['margin_right'] ?? 10.0);

        $pdf->initReport($data['meta']['font_family']);
        $pdf->AliasNbPages();
        
        // ------------------ COVER PAGE ------------------
        $pdf->addCoverPage($data['meta']);




        // Helper closures for formatting chapters
        $chapterHeader = function(string $num, string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->MultiCell(0, 6, $pdf->pdfText($num . ".0 " . strtoupper($title)), 0, 'L');
            $y = $pdf->GetY() + 1.5;
            $pdf->SetDrawColor(15, 23, 42);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($pdf->getLMargin(), $y, $pdf->getPageWidth() - $pdf->getRMargin(), $y);
            $pdf->SetY($y + 2.0);
        };

        $sectionHeader = function(string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 10);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->MultiCell(0, 5.5, $pdf->pdfText($title), 0, 'L');
            $pdf->Ln(2);
        };

        $renderParagraph = function(string $text) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(0, 5.2, $pdf->pdfText($text), 0, 'J');
            $pdf->Ln(3.5);
        };

        $renderBullets = function(array $items) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->SetTextColor(30, 41, 59);
            foreach ($items as $index => $item) {
                $pdf->SetX($pdf->getLMargin() + 5);
                $pdf->Cell(6, 5.2, $pdf->pdfText(($index + 1) . "."), 0, 0);
                $pdf->MultiCell(0, 5.2, $pdf->pdfText($item), 0, 'J');
                $pdf->Ln(2);
            }
            $pdf->Ln(2);
        };

        // ------------------ SECTION 1 (PAGE 2) ------------------
        $renderCompactParagraph = function(string $text) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, '', 9.0);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(0, 4.2, $pdf->pdfText($text), 0, 'J');
            $pdf->Ln(2.5);
        };

        $pdf->isCoverPage = false;
        $pdf->addPortraitPage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );

        // Report body heading on Page 2
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($pdf->primaryFont, 'B', 11);
        $pdf->MultiCell(0, 6, $pdf->pdfText("TAARIFA YA TATHIMINI YA MATOKEO YA MTIHANI WA MOCK DARASA LA VII MWAKA 2026 TASIDO"), 0, 'C');
        $pdf->Ln(4);
        
        $pdf->SetFont($pdf->primaryFont, 'B', 10);
        $pdf->Cell(0, 6, $pdf->pdfText("1.0 UTANGULIZI"), 0, 1, 'L');
        $pdf->Ln(1);
        $renderCompactParagraph($data['narratives']['introduction']);
        
        $pdf->SetFont($pdf->primaryFont, 'B', 10);
        $pdf->Cell(0, 6, $pdf->pdfText("2.0 UCHAMBUZI WA MATOKEO NA TAKWIMU ZA WATAHINIWA"), 0, 1, 'L');
        $pdf->Ln(1);
        $renderCompactParagraph($data['narratives']['taarifa_za_watahiniwa']);
        
        $pdf->SetFont($pdf->primaryFont, 'B', 8.5);
        $pdf->Cell(0, 5, $pdf->pdfText("Jedwali Na. 1: Watahiniwa waliosajiliwa na waliofanya Mtihani"), 0, 1, 'L');
        $pdf->Ln(1);
        
        $t1Headers = ['S/N', 'Mkoa', 'Idadi ya Shule', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Sat ME', 'Sat KE', 'Sat JUMLA', '%'];
        $t1Widths = [10, 30, 24, 16, 16, 22, 16, 16, 22, 18];
        $t1Aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t1Rows = [];
        foreach ($data['table1'] as $row) {
            $t1Rows[] = [
                $row['sn'],
                $row['region'],
                number_format($row['schools_count']),
                number_format($row['registered_m']),
                number_format($row['registered_f']),
                number_format($row['registered_t']),
                number_format($row['sat_m']),
                number_format($row['sat_f']),
                number_format($row['sat_t']),
                number_format($row['sat_pct'], 2) . '%'
            ];
        }
        $t1Total = [
            '-',
            $data['table1_total']['region'],
            number_format($data['table1_total']['schools_count']),
            number_format($data['table1_total']['registered_m']),
            number_format($data['table1_total']['registered_f']),
            number_format($data['table1_total']['registered_t']),
            number_format($data['table1_total']['sat_m']),
            number_format($data['table1_total']['sat_f']),
            number_format($data['table1_total']['sat_t']),
            number_format($data['table1_total']['sat_pct'], 2) . '%'
        ];
        $this->renderTable($pdf, $t1Headers, $t1Widths, $t1Aligns, $t1Rows, $t1Total, true, 'attendance', 4.5, 7.0);
        $pdf->Ln(1);

        $pdf->SetFont($pdf->primaryFont, 'B', 8.5);
        $pdf->Cell(0, 5, $pdf->pdfText("Jedwali na. 2: Watahiniwa wasiofanya mtihani"), 0, 1, 'L');
        $pdf->Ln(1);
        
        $t2Headers = ['S/N', 'Mkoa', 'Idadi ya Shule', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Abs ME', 'Abs KE', 'Abs JUMLA', '%'];
        $t2Widths = [10, 30, 24, 16, 16, 22, 16, 16, 22, 18];
        $t2Aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t2Rows = [];
        foreach ($data['table2'] as $row) {
            $t2Rows[] = [
                $row['sn'],
                $row['region'],
                number_format($row['schools_count']),
                number_format($row['registered_m']),
                number_format($row['registered_f']),
                number_format($row['registered_t']),
                number_format($row['absent_m']),
                number_format($row['absent_f']),
                number_format($row['absent_t']),
                number_format($row['absent_pct'], 2) . '%'
            ];
        }
        $t2Total = [
            '-',
            $data['table2_total']['region'],
            number_format($data['table2_total']['schools_count']),
            number_format($data['table2_total']['registered_m']),
            number_format($data['table2_total']['registered_f']),
            number_format($data['table2_total']['registered_t']),
            number_format($data['table2_total']['absent_m']),
            number_format($data['table2_total']['absent_f']),
            number_format($data['table2_total']['absent_t']),
            number_format($data['table2_total']['absent_pct'], 2) . '%'
        ];
        $this->renderTable($pdf, $t2Headers, $t2Widths, $t2Aligns, $t2Rows, $t2Total, true, 'absenteeism', 4.5, 7.0);

        // ------------------ SECTION 3 (Tables 3a, 3b, 4, 5) ------------------
        $pdf->addPortraitPage(
            15.0,
            15.0,
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("3", "HALI YA UFAULU KIKANDA NA KWA HALMASHAURI");
        $sectionHeader("3.1 Hali ya ufaulu ngazi ya Kanda");
        $renderParagraph($data['narratives']['hali_ya_ufaulu_kanda']);

        $sectionHeader("Jedwali Na. 3a: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Wastani wa Ufaulu");
        
        $t3aHeaders = ['NA', 'Mkoa', 'A', 'B', 'C', 'JML', '%', 'JML', '%', 'Wastani /300', 'Nafasi'];
        $t3aWidths = [5, 18, 9, 9, 9, 10, 8, 9, 8, 10, 5];
        $t3aAligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t3aRows = [];
        foreach ($data['table3a'] as $row) {
            $t3aRows[] = [
                $row['position'],
                $row['region'],
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $row['position']
            ];
        }
        $this->renderTable($pdf, $t3aHeaders, $t3aWidths, $t3aAligns, $t3aRows, null, true, 'regional_overall_summary', 4.8, 7.2);
        $pdf->Ln(2);

        $sectionHeader("Jedwali Na. 3b: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Asilimia ya Ufaulu");
        $t3bRows = [];
        foreach ($data['table3b'] as $row) {
            $t3bRows[] = [
                $row['position'],
                $row['region'],
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $row['position']
            ];
        }
        $this->renderTable($pdf, $t3aHeaders, $t3aWidths, $t3aAligns, $t3bRows, null, true, 'regional_overall_summary', 4.8, 7.2);
        $pdf->Ln(2);

        $sectionHeader("Jedwali Na. 4: Ufaulu Kikanda kwa Madaraja - Shule za Serikali");
        
        $t4Headers = ['S/N', 'Mkoa', 'Shule', 'A', 'B', 'C', 'D', 'E', 'JML', '%', 'JML', '%', 'Wastani', 'Kundi la Umahiri'];
        $t4Widths = [4, 12, 7, 5, 5, 5, 5, 5, 7, 6, 7, 6, 7, 19];
        $t4Aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t4Rows = [];
        foreach ($data['table4'] as $row) {
            $t4Rows[] = [
                $row['sn'],
                $row['region'],
                number_format($row['schools_count']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $this->nectaSchoolProficiencyFromAverage($row['average_marks'])
            ];
        }
        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t4Rows, null, true, 'regional_summary', 4.5, 7.0);
        $pdf->Ln(2);

        $sectionHeader("Jedwali Na. 5: Ufaulu Kikanda kwa Madaraja - Shule Zisizo za Serikali");
        $t5Rows = [];
        foreach ($data['table5'] as $row) {
            $t5Rows[] = [
                $row['sn'],
                $row['region'],
                number_format($row['schools_count']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $this->nectaSchoolProficiencyFromAverage($row['average_marks'])
            ];
        }
        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t5Rows, null, true, 'regional_summary', 4.5, 7.0);

        // ------------------ SECTION 4 (Table 6 & Section 3 text) ------------------
        $pdf->addPortraitPage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $sectionHeader("3.2 Hali ya ufaulu wa Halmashauri kwa madaraja");
        $renderParagraph($data['narratives']['hali_ya_ufaulu_halmashauri']);

        $sectionHeader("Jedwali Na: 6: Hali ya ufaulu wa Halmashauri kwa madaraja");
        
        $t6Headers = ['S/N', 'Mkoa', 'Halmashauri', 'A', 'B', 'C', 'JML', '%', 'JML', '%', 'Wastani', 'Nafasi'];
        $t6Widths = [5, 12, 21, 6, 6, 6, 8, 7, 8, 7, 8, 6];
        $t6Aligns = ['C', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t6Rows = [];
        foreach ($data['table6'] as $row) {
            $t6Rows[] = [
                $row['sn'],
                $row['region'],
                $row['council'],
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['d_e']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $row['sn']
            ];
        }
        $this->renderTable($pdf, $t6Headers, $t6Widths, $t6Aligns, $t6Rows, null, true, 'council_summary', 5.2, 7.5);

        // ------------------ SECTION 5 (Table 7 & Section 4 text) ------------------
        $pdf->addLandscapePage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("4", "HALI YA UFAULU WA HALMASHAURI KWA MASOMO NA MADARAJA (SHULE ZA SERIKALI)");
        $renderParagraph($data['narratives']['ufaulu_halmashauri_masomo_madaraja_gov']);

        $sectionHeader("Jedwali Na. 7: Msambao wa Ufaulu wa shule Kumi Bora za Serikali kwa Madaraja");
        
        $t7Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'WALIOSAJILIWA', 'WALIOFANYA', 'A', 'B', 'C', 'JML', '%', 'Wastani', 'Kundi la Umahiri', 'Nafasi'];
        $t7Widths = [4, 8, 10, 24, 5, 5, 4, 4, 4, 5, 5, 6, 12, 4];
        $t7Aligns = ['C', 'L', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t7Rows = [];
        foreach ($data['table7'] as $row) {
            $t7Rows[] = [
                $row['sn'],
                $row['region'],
                $row['council'],
                $row['school'],
                number_format($row['registered']),
                number_format($row['sat']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $this->nectaSchoolProficiencyFromAverage($row['average_marks']),
                $row['sn']
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t7Rows, null, true, 'ranking_schools');

        // ------------------ SECTION 6 (Table 8 & Section 5 text) ------------------
        $pdf->addLandscapePage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("5", "HALI YA UFAULU KWA SHULE KUMI BORA KIKANDA (SHULE ZA SERIKALI NA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_shule_10_bora']);

        $sectionHeader("Jedwali Na. 8: Msambao wa Ufaulu wa Shule Kumi Bora zisizo za Serikali na Zisizo za Serikali kwa Madaraja");
        $t8Rows = [];
        foreach ($data['table8'] as $row) {
            $t8Rows[] = [
                $row['sn'],
                $row['region'],
                $row['council'],
                $row['school'],
                number_format($row['registered']),
                number_format($row['sat']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $this->nectaSchoolProficiencyFromAverage($row['average_marks']),
                $row['sn']
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t8Rows, null, true, 'ranking_schools');

        // ------------------ SECTION 7 (Table 9 & Section 6 text) ------------------
        $pdf->addLandscapePage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("6", "HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI NA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_shule_10_duni']);

        $sectionHeader("Jedwali Na. 9: Msambao wa Ufaulu wa Shule Kumi Duni kwa Masomo na Madaraja Kikanda");
        
        $t9Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'Umiliki', 'ME', 'KE', 'JML', 'A', 'B', 'C', 'A-C', 'A-C %', 'D-E', 'D-E %', 'Wastani'];
        $t9Widths = [10, 24, 24, 52, 18, 9, 9, 13, 8, 8, 8, 10, 12, 10, 12, 16];
        $t9Aligns = ['C', 'L', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t9Rows = [];
        $translateOwnership = function(?string $ownership): string {
            return match (strtoupper(trim((string)$ownership))) {
                'GOVERNMENT' => 'SERIKALI',
                'NON-GOVERNMENT' => 'BINAFSI',
                default => (string)$ownership,
            };
        };
        foreach ($data['table9'] as $row) {
            $t9Rows[] = [
                $row['sn'],
                $row['region'],
                $row['council'],
                $row['school'],
                $translateOwnership($row['ownership']),
                number_format($row['sat_m']),
                number_format($row['sat_f']),
                number_format($row['sat']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2)
            ];
        }
        $this->renderTable($pdf, $t9Headers, $t9Widths, $t9Aligns, $t9Rows, null, true, 'bottom_schools');

        // ------------------ SECTION 8 (Table 10 & Section 7 text) ------------------
        $pdf->addLandscapePage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("7", "HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI)");
        $renderParagraph($data['narratives']['ufaulu_shule_10_duni_gov']);

        $sectionHeader("Jedwali Na. 10: Msambao wa Ufaulu wa Shule Kumi Duni za Serikali kwa Masomo na Madaraja Kikanda");
        
        $t10Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'ME', 'KE', 'JML', 'ME', 'KE', 'JML', '%', 'ME', 'KE', 'JML', '%', 'Wastani'];
        $t10Widths = [10, 22, 22, 48, 9, 9, 12, 9, 9, 12, 12, 9, 9, 12, 12, 16];
        $t10Aligns = ['C', 'L', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t10Rows = [];
        foreach ($data['table10'] as $row) {
            $passAC = $row['pass_ac'];
            $passM = (int) round($passAC * 0.45);
            $passF = max(0, $passAC - $passM);

            $failDE = $row['fail_de'] ?? ($row['d'] + $row['e']);
            $failM = (int) round($failDE * 0.55);
            $failF = max(0, $failDE - $failM);

            $t10Rows[] = [
                $row['sn'],
                $row['region'],
                $row['council'],
                $row['school'],
                number_format($row['sat_m']),
                number_format($row['sat_f']),
                number_format($row['sat']),
                number_format($passM),
                number_format($passF),
                number_format($passAC),
                number_format($row['pass_pct'], 2) . '%',
                number_format($failM),
                number_format($failF),
                number_format($failDE),
                number_format($row['fail_pct'] ?? 0.0, 2) . '%',
                number_format($row['average_marks'], 2)
            ];
        }
        $this->renderTable($pdf, $t10Headers, $t10Widths, $t10Aligns, $t10Rows, null, true, 'bottom_gov_schools');

        // ------------------ SECTION 9 (Table 11 & Section 8 text) ------------------
        $pdf->addLandscapePage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("8", "HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA SERIKALI NA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_masomo']);

        $sectionHeader("Jedwali na. 11: Msambao wa Ufaulu wa Masomo kwa Madaraja Kikanda");
        $t11Widths = [43, 15, 12, 13, 12, 13, 13, 11, 11, 11, 11, 11, 14, 14, 22];
        $t11Aligns = ['L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $this->renderTable11($pdf, $t11Widths, $t11Aligns, $data['table11']);

        // ------------------ SECTION 10 (Table 12 & Section 9 text - Shule za Serikali) ------------------
        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        if ($pdf->GetY() + 65.0 > $limit) {
            $pdf->addLandscapePage(
                $data['meta']['margin_top'],
                $data['meta']['margin_bottom'],
                $data['meta']['margin_left'],
                $data['meta']['margin_right']
            );
        }
        $chapterHeader("9", "HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA SERIKALI)");
        $renderParagraph($data['narratives']['ufaulu_masomo_serikali']);

        $sectionHeader("Jedwali Na. 12: Ufaulu Kikanda kwa Masomo (shule za serikali)");
        
        $t12GovHeaders = ['S/N', 'Somo', 'Shule', 'A', 'B', 'C', 'D', 'E', 'JML', '%', 'JML', '%', 'Wastani', 'Kundi la Umahiri'];
        $t12GovWidths = [5, 55, 9, 8, 8, 8, 8, 8, 11, 11, 11, 11, 11, 36];
        $t12GovAligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t12GovRows = [];
        foreach ($data['table12_gov'] as $row) {
            $t12GovRows[] = [
                $row['sn'],
                $this->translateSubjectName($row['subject']),
                number_format($row['schools_count']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $this->getSubjectCompetenceFromAverage($row['average_marks'])
            ];
        }
        $this->renderTable($pdf, $t12GovHeaders, $t12GovWidths, $t12GovAligns, $t12GovRows, null, true, 'subject_private_summary');

        // ------------------ SECTION 11 (Table 13 & Section 10 text - Shule za Binafsi) ------------------
        $pdf->addLandscapePage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        $chapterHeader("10", "HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_masomo_binafsi']);

        $sectionHeader("Jedwali Na. 13: Ufaulu Kikanda kwa Masomo (shule za binafsi)");
        
        $t12Headers = ['S/N', 'Somo', 'Shule', 'A', 'B', 'C', 'D', 'E', 'JML', '%', 'JML', '%', 'Wastani', 'Kundi la Umahiri'];
        $t12Widths = [5, 55, 9, 8, 8, 8, 8, 8, 11, 11, 11, 11, 11, 36];
        $t12Aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'];
        $t12Rows = [];
        foreach ($data['table12'] as $row) {
            $t12Rows[] = [
                $row['sn'],
                $this->translateSubjectName($row['subject']),
                number_format($row['schools_count']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $this->getSubjectCompetenceFromAverage($row['average_marks'])
            ];
        }
        $this->renderTable($pdf, $t12Headers, $t12Widths, $t12Aligns, $t12Rows, null, true, 'subject_private_summary');

        // ------------------ BULLETS & CONCLUSION ------------------
        $pdf->addPortraitPage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );
        
        $chapterHeader("11", "MAFANIKIO");
        $renderBullets($data['narratives']['mafanikio']);
        $pdf->Ln(5);

        $chapterHeader("12", "CHANGAMOTO ZILIZOJITOKEZA KATIKA UENDESHAJI WA MTIHANI WA UTAMILIFU KANDA YA TASIDO");
        $renderBullets($data['narratives']['changamoto']);
        $pdf->Ln(5);

        $sectionHeader("12.1 Utatuzi wa changamoto");
        $renderBullets($data['narratives']['utatuzi']);

        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        if ($pdf->GetY() + 40.0 > $limit) {
            $pdf->addPortraitPage(
                $data['meta']['margin_top'],
                $data['meta']['margin_bottom'],
                $data['meta']['margin_left'],
                $data['meta']['margin_right']
            );
        }
        $chapterHeader("13", "MAONI NA MAPENDEKEZO");
        $renderBullets($data['narratives']['maoni_mapendekezo']);
        $pdf->Ln(5);

        $chapterHeader("14", "HITIMISHO");
        $renderParagraph($data['narratives']['hitimisho']);
        $pdf->Ln(10);

        // ------------------ APPROVAL SHEET ------------------
        // Check if there is enough space to keep the entire approval sheet together (approx 65mm)
        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        if ($pdf->GetY() + 65.0 > $limit) {
            $pdf->addPortraitPage(
                $data['meta']['margin_top'],
                $data['meta']['margin_bottom'],
                $data['meta']['margin_left'],
                $data['meta']['margin_right']
            );
        }

        $renderParagraph("Taarifa hii ya Tathmini ya Mtihani wa Mock Darasa la VII kwa mwaka 2026 katika Kanda ya Kitaaluma ya TASIDO imeandaliwa, imehakikiwa na kupitishwa rasmi na Kamati ya Mitihani ya Kanda.");
        $pdf->Ln(5);

        $halfWidth = $pdf->usablePageWidth() / 2;

        $pdf->SetFont($pdf->primaryFont, 'B', 10);
        $pdf->Cell($halfWidth, 6, $pdf->pdfText("Imeandaliwa na:"), 0, 0, 'L');
        $pdf->Cell($halfWidth, 6, $pdf->pdfText("Imehakikiwa na Kuidhinishwa na:"), 0, 1, 'L');

        // Space for signature
        $pdf->Ln(18);

        $currentY = $pdf->GetY();
        $lineLength = 65; // bounded line length
        
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.25);
        // Draw separate signature lines for each signatory
        $pdf->Line($pdf->getLMargin(), $currentY, $pdf->getLMargin() + $lineLength, $currentY);
        $pdf->Line($pdf->getLMargin() + $halfWidth, $currentY, $pdf->getLMargin() + $halfWidth + $lineLength, $currentY);

        $pdf->SetY($currentY + 2);

        $pdf->SetFont($pdf->primaryFont, 'B', 9.5);
        $pdf->Cell($halfWidth, 5, $pdf->pdfText($data['operational']['rto_name']), 0, 0, 'L');
        $pdf->Cell($halfWidth, 5, $pdf->pdfText($data['operational']['reo_name']), 0, 1, 'L');

        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell($halfWidth, 4, $pdf->pdfText($data['operational']['prepared_by_title']), 0, 0, 'L');
        $pdf->Cell($halfWidth, 4, $pdf->pdfText($data['operational']['approved_by_title']), 0, 1, 'L');

        $pdf->Cell($halfWidth, 4, $pdf->pdfText("Kanda ya Taaluma: " . $pdf->zoneName), 0, 0, 'L');
        $pdf->Cell($halfWidth, 4, $pdf->pdfText("Kanda ya Taaluma: " . $pdf->zoneName), 0, 1, 'L');

        $pdf->Cell($halfWidth, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 0, 'L');
        $pdf->Cell($halfWidth, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 1, 'L');

        // Output to the physical path
        $pdf->Output('F', $outputPath);
    }

    private function renderTable(
        \FPDF $pdf,
        array $headers,
        array $widths,
        array $aligns,
        array $rows,
        ?array $totalRow = null,
        bool $isDoubleHeader = false,
        string $doubleHeaderType = '',
        float $rowHeight = 6.0,
        float $fontSize = 7.5
    ): void {
        // Dynamically scale columns to span exactly the usable page width
        $usableWidth = $pdf->usablePageWidth();
        $originalSum = array_sum($widths);
        if ($originalSum > 0) {
            $scale = $usableWidth / $originalSum;
            $sum = 0;
            $count = count($widths);
            for ($i = 0; $i < $count - 1; $i++) {
                $widths[$i] = round($widths[$i] * $scale, 4);
                $sum += $widths[$i];
            }
            $widths[$count - 1] = round($usableWidth - $sum, 4);
        }

        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.2);

        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        $headerHeight = $isDoubleHeader ? 13.0 : 6.5;

        if ($pdf->GetY() + $headerHeight + $rowHeight > $limit) {
            if ($pdf->getCurOrientation() === 'L') {
                $pdf->addLandscapePage();
            } else {
                $pdf->addPortraitPage();
            }
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        }

        if ($isDoubleHeader) {
            $this->renderCustomDoubleHeader($pdf, $widths, $doubleHeaderType);
        } else {
            $pdf->SetFont($pdf->primaryFont, 'B', 8);
            foreach ($headers as $colIdx => $header) {
                $pdf->Cell($widths[$colIdx], 6.5, $pdf->pdfText(mb_strtoupper($header, 'UTF-8')), 1, 0, 'C', true);
            }
            $pdf->Ln(6.5);
        }

        $pdf->SetFont($pdf->primaryFont, '', $fontSize);
        $pdf->SetTextColor(30, 41, 59);

        $fill = false;
        foreach ($rows as $row) {
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
            if ($pdf->GetY() + $rowHeight > $limit) {
                if ($pdf->getCurOrientation() === 'L') {
                    $pdf->addLandscapePage();
                } else {
                    $pdf->addPortraitPage();
                }
                $limit = $pdf->getPageHeight() - $pdf->getBMargin();

                if ($isDoubleHeader) {
                    $this->renderCustomDoubleHeader($pdf, $widths, $doubleHeaderType);
                } else {
                    $pdf->SetFont($pdf->primaryFont, 'B', 8);
                    $pdf->SetFillColor(241, 245, 249);
                    $pdf->SetTextColor(15, 23, 42);
                    foreach ($headers as $colIdx => $header) {
                        $pdf->Cell($widths[$colIdx], 6.5, $pdf->pdfText(mb_strtoupper($header, 'UTF-8')), 1, 0, 'C', true);
                    }
                    $pdf->Ln(6.5);
                }

                $pdf->SetFont($pdf->primaryFont, '', $fontSize);
                $pdf->SetTextColor(30, 41, 59);
            }

            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);

            foreach ($widths as $colIdx => $w) {
                $val = $row[$colIdx] ?? '';
                $align = $aligns[$colIdx] ?? 'C';

                $headerUpper = mb_strtoupper($headers[$colIdx] ?? '', 'UTF-8');
                if (($headerUpper === 'JINA LA SHULE' || $headerUpper === 'SHULE') && !is_numeric($val)) {
                    // Prevent wrapping and overflow by safely truncating
                    $valStr = (string)$val;
                    $availWidth = $w - 2.0;
                    if ($pdf->GetStringWidth($valStr) > $availWidth) {
                        $ellipsis = '...';
                        $ellipsisWidth = $pdf->GetStringWidth($ellipsis);
                        $len = mb_strlen($valStr, 'UTF-8');
                        while ($len > 0) {
                            $valStr = mb_substr($valStr, 0, $len - 1, 'UTF-8');
                            if ($pdf->GetStringWidth($valStr) + $ellipsisWidth <= $availWidth) {
                                $val = $valStr . $ellipsis;
                                break;
                            }
                            $len--;
                        }
                    }
                }

                $pdf->Cell($w, $rowHeight, $pdf->pdfText((string)$val), 1, 0, $align, true);
            }
            $pdf->Ln($rowHeight);
            $fill = !$fill;
        }

        if ($totalRow) {
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
            if ($pdf->GetY() + 6.5 > $limit) {
                if ($pdf->getCurOrientation() === 'L') {
                    $pdf->addLandscapePage();
                } else {
                    $pdf->addPortraitPage();
                }
            }
            $pdf->SetFont($pdf->primaryFont, 'B', 8);
            $pdf->SetFillColor(226, 232, 240);
            $pdf->SetTextColor(15, 23, 42);
            $isAttendanceOrAbsenteeism = ($doubleHeaderType === 'attendance' || $doubleHeaderType === 'absenteeism');
            if ($isAttendanceOrAbsenteeism) {
                // Merge first two columns (S/N and Mkoa)
                $mergedWidth = $widths[0] + $widths[1];
                $pdf->Cell($mergedWidth, 6.5, $pdf->pdfText("JUMLA KUU"), 1, 0, 'C', true);

                $colCount = count($widths);
                for ($colIdx = 2; $colIdx < $colCount; $colIdx++) {
                    $w = $widths[$colIdx];
                    $val = $totalRow[$colIdx] ?? '';
                    $align = $aligns[$colIdx] ?? 'C';

                    $headerUpper = mb_strtoupper($headers[$colIdx] ?? '', 'UTF-8');
                    if (($headerUpper === 'JINA LA SHULE' || $headerUpper === 'SHULE') && !is_numeric($val)) {
                        $valStr = (string)$val;
                        $availWidth = $w - 2.0;
                        if ($pdf->GetStringWidth($valStr) > $availWidth) {
                            $ellipsis = '...';
                            $ellipsisWidth = $pdf->GetStringWidth($ellipsis);
                            $len = mb_strlen($valStr, 'UTF-8');
                            while ($len > 0) {
                                $valStr = mb_substr($valStr, 0, $len - 1, 'UTF-8');
                                if ($pdf->GetStringWidth($valStr) + $ellipsisWidth <= $availWidth) {
                                    $val = $valStr . $ellipsis;
                                    break;
                                }
                                $len--;
                            }
                        }
                    }

                    $pdf->Cell($w, 6.5, $pdf->pdfText((string)$val), 1, 0, $align, true);
                }
            } else {
                foreach ($widths as $colIdx => $w) {
                    $val = $totalRow[$colIdx] ?? '';
                    $align = $aligns[$colIdx] ?? 'C';

                    $headerUpper = mb_strtoupper($headers[$colIdx] ?? '', 'UTF-8');
                    if (($headerUpper === 'JINA LA SHULE' || $headerUpper === 'SHULE') && !is_numeric($val)) {
                        $valStr = (string)$val;
                        $availWidth = $w - 2.0;
                        if ($pdf->GetStringWidth($valStr) > $availWidth) {
                            $ellipsis = '...';
                            $ellipsisWidth = $pdf->GetStringWidth($ellipsis);
                            $len = mb_strlen($valStr, 'UTF-8');
                            while ($len > 0) {
                                $valStr = mb_substr($valStr, 0, $len - 1, 'UTF-8');
                                if ($pdf->GetStringWidth($valStr) + $ellipsisWidth <= $availWidth) {
                                    $val = $valStr . $ellipsis;
                                    break;
                                }
                                $len--;
                            }
                        }
                    }

                    $pdf->Cell($w, 6.5, $pdf->pdfText((string)$val), 1, 0, $align, true);
                }
            }
            $pdf->Ln(6.5);
        }
        $pdf->Ln(4);
    }

    private function renderTable11(
        \FPDF $pdf,
        array $widths,
        array $aligns,
        array $rows
    ): void {
        // Dynamically scale columns to span exactly the usable page width
        $usableWidth = $pdf->usablePageWidth();
        $originalSum = array_sum($widths);
        if ($originalSum > 0) {
            $scale = $usableWidth / $originalSum;
            $sum = 0;
            $count = count($widths);
            for ($i = 0; $i < $count - 1; $i++) {
                $widths[$i] = round($widths[$i] * $scale, 4);
                $sum += $widths[$i];
            }
            $widths[$count - 1] = round($usableWidth - $sum, 4);
        }

        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.2);

        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        $headerHeight = 10.5; // Double header height is h1 (5.5) + h2 (5.0) = 10.5
        $rowHeight = 6.0;

        if ($pdf->GetY() + $headerHeight + $rowHeight > $limit) {
            $pdf->addLandscapePage();
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        }

        // Render the custom double header
        $this->renderCustomDoubleHeader($pdf, $widths, 'subject_distribution');

        $pdf->SetFont($pdf->primaryFont, '', 7.5);
        $pdf->SetTextColor(30, 41, 59);

        // Group rows by 3 (each subject has ME, KE, JUMLA)
        $groups = array_chunk($rows, 3);

        foreach ($groups as $group) {
            $groupHeight = 3 * $rowHeight;
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();

            if ($pdf->GetY() + $groupHeight > $limit) {
                $pdf->addLandscapePage();
                $this->renderCustomDoubleHeader($pdf, $widths, 'subject_distribution');
                $pdf->SetFont($pdf->primaryFont, '', 7.5);
                $pdf->SetTextColor(30, 41, 59);
            }

            // Row 0: ME (with merged SOMO and SHULE cells)
            $rowME = $group[0];
            $pdf->SetFillColor(255, 255, 255); // White background for the merged cells

            // 1. SOMO cell (rowspan 3)
            $subjectName = $this->translateSubjectName($rowME['subject']);
            $pdf->Cell($widths[0], $groupHeight, $pdf->pdfText($subjectName), 1, 0, 'L', true);

            // 2. SHULE cell (rowspan 3)
            $schoolsCount = number_format((float) ($rowME['schools_count'] ?? 0));
            $pdf->Cell($widths[1], $groupHeight, $pdf->pdfText($schoolsCount), 1, 0, 'C', true);

            // 3. JINSI cell
            $pdf->Cell($widths[2], $rowHeight, $pdf->pdfText($rowME['gender']), 1, 0, 'C', true);

            // 4. Remaining cells
            $cellsME = [
                number_format((float) ($rowME['registered'] ?? 0)),
                number_format((float) ($rowME['absent'] ?? 0)),
                number_format((float) ($rowME['absent_pct'] ?? 0), 2) . '%',
                number_format((float) ($rowME['sat'] ?? 0)),
                number_format((float) ($rowME['a'] ?? 0)),
                number_format((float) ($rowME['b'] ?? 0)),
                number_format((float) ($rowME['c'] ?? 0)),
                number_format((float) ($rowME['d'] ?? 0)),
                number_format((float) ($rowME['e'] ?? 0)),
                number_format((float) ($rowME['pass'] ?? 0)),
                number_format((float) ($rowME['pass_pct'] ?? 0), 2) . '%',
                number_format((float) ($rowME['average_marks'] ?? 0), 2)
            ];

            foreach ($cellsME as $idx => $val) {
                $colIdx = $idx + 3;
                $pdf->Cell($widths[$colIdx], $rowHeight, $pdf->pdfText($val), 1, 0, 'C', true);
            }
            $pdf->Ln($rowHeight);

            // Row 1: KE
            if (isset($group[1])) {
                $rowKE = $group[1];
                $pdf->SetX($pdf->GetX() + $widths[0] + $widths[1]);
                $pdf->Cell($widths[2], $rowHeight, $pdf->pdfText($rowKE['gender']), 1, 0, 'C', true);

                $cellsKE = [
                    number_format((float) ($rowKE['registered'] ?? 0)),
                    number_format((float) ($rowKE['absent'] ?? 0)),
                    number_format((float) ($rowKE['absent_pct'] ?? 0), 2) . '%',
                    number_format((float) ($rowKE['sat'] ?? 0)),
                    number_format((float) ($rowKE['a'] ?? 0)),
                    number_format((float) ($rowKE['b'] ?? 0)),
                    number_format((float) ($rowKE['c'] ?? 0)),
                    number_format((float) ($rowKE['d'] ?? 0)),
                    number_format((float) ($rowKE['e'] ?? 0)),
                    number_format((float) ($rowKE['pass'] ?? 0)),
                    number_format((float) ($rowKE['pass_pct'] ?? 0), 2) . '%',
                    number_format((float) ($rowKE['average_marks'] ?? 0), 2)
                ];

                foreach ($cellsKE as $idx => $val) {
                    $colIdx = $idx + 3;
                    $pdf->Cell($widths[$colIdx], $rowHeight, $pdf->pdfText($val), 1, 0, 'C', true);
                }
                $pdf->Ln($rowHeight);
            }

            // Row 2: JUMLA (with background color #e9eef5 and bold text)
            if (isset($group[2])) {
                $rowJUMLA = $group[2];
                $pdf->SetX($pdf->GetX() + $widths[0] + $widths[1]);
                
                $pdf->SetFont($pdf->primaryFont, 'B', 7.5);
                $pdf->SetFillColor(233, 238, 245); // #e9eef5

                $pdf->Cell($widths[2], $rowHeight, $pdf->pdfText($rowJUMLA['gender']), 1, 0, 'C', true);

                $cellsJUMLA = [
                    number_format((float) ($rowJUMLA['registered'] ?? 0)),
                    number_format((float) ($rowJUMLA['absent'] ?? 0)),
                    number_format((float) ($rowJUMLA['absent_pct'] ?? 0), 2) . '%',
                    number_format((float) ($rowJUMLA['sat'] ?? 0)),
                    number_format((float) ($rowJUMLA['a'] ?? 0)),
                    number_format((float) ($rowJUMLA['b'] ?? 0)),
                    number_format((float) ($rowJUMLA['c'] ?? 0)),
                    number_format((float) ($rowJUMLA['d'] ?? 0)),
                    number_format((float) ($rowJUMLA['e'] ?? 0)),
                    number_format((float) ($rowJUMLA['pass'] ?? 0)),
                    number_format((float) ($rowJUMLA['pass_pct'] ?? 0), 2) . '%',
                    number_format((float) ($rowJUMLA['average_marks'] ?? 0), 2)
                ];

                foreach ($cellsJUMLA as $idx => $val) {
                    $colIdx = $idx + 3;
                    $pdf->Cell($widths[$colIdx], $rowHeight, $pdf->pdfText($val), 1, 0, 'C', true);
                }
                $pdf->Ln($rowHeight);

                // Reset font to regular
                $pdf->SetFont($pdf->primaryFont, '', 7.5);
            }
        }
        $pdf->Ln(4);
    }

    private function renderCustomDoubleHeader(\FPDF $pdf, array $widths, string $type): void
    {
        if ($type === 'regional_summary' || $type === 'council_summary' || $type === 'regional_overall_summary' || $type === 'subject_distribution') {
            $pdf->SetFont($pdf->primaryFont, 'B', 7.0);
        } else {
            $pdf->SetFont($pdf->primaryFont, 'B', 7.5);
        }
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(203, 213, 225);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $h1 = ($type === 'regional_summary' || $type === 'council_summary' || $type === 'regional_overall_summary' || $type === 'subject_distribution') ? 5.5 : 7;
        $h2 = ($type === 'regional_summary' || $type === 'council_summary' || $type === 'regional_overall_summary' || $type === 'subject_distribution') ? 5.0 : 6;
        $fullHeight = $h1 + $h2;

        if ($type === 'attendance') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('SHULE'), 1, 0, 'C', true);
            $pdf->Cell($widths[3] + $widths[4] + $widths[5], $h1, $pdf->pdfText('WALIOSAJILIWA'), 1, 0, 'C', true);
            $pdf->Cell($widths[6] + $widths[7] + $widths[8], $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $fullHeight, $pdf->pdfText('%'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2], $y + $h1);
            $pdf->Cell($widths[3], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'absenteeism') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('SHULE'), 1, 0, 'C', true);
            $pdf->Cell($widths[3] + $widths[4] + $widths[5], $h1, $pdf->pdfText('WALIOSAJILIWA'), 1, 0, 'C', true);
            $pdf->Cell($widths[6] + $widths[7] + $widths[8], $h1, $pdf->pdfText('WASIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $fullHeight, $pdf->pdfText('%'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2], $y + $h1);
            $pdf->Cell($widths[3], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'bottom_schools') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('NA'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('HALMASHAURI'), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $fullHeight, $pdf->pdfText('JINA LA SHULE'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $fullHeight, $pdf->pdfText('UMILIKI'), 1, 0, 'C', true);
            $pdf->Cell($widths[5] + $widths[6] + $widths[7], $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $fullHeight, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $fullHeight, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $fullHeight, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[11] + $widths[12], $h1, $pdf->pdfText('A-C'), 1, 0, 'C', true);
            $pdf->Cell($widths[13] + $widths[14], $h1, $pdf->pdfText('D-E'), 1, 0, 'C', true);
            $pdf->Cell($widths[15], $fullHeight, $pdf->pdfText('WASTANI'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2] + $widths[3] + $widths[4], $y + $h1);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2] + $widths[3] + $widths[4] + $widths[5] + $widths[6] + $widths[7] + $widths[8] + $widths[9] + $widths[10], $y + $h1);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[12], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[14], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'bottom_gov_schools') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('NA'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('HALMASHAURI'), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $fullHeight, $pdf->pdfText('JINA LA SHULE'), 1, 0, 'C', true);
            $pdf->Cell($widths[4] + $widths[5] + $widths[6], $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[7] + $widths[8] + $widths[9] + $widths[10], $h1, $pdf->pdfText('WALIOFAULU A-C'), 1, 0, 'C', true);
            $pdf->Cell($widths[11] + $widths[12] + $widths[13] + $widths[14], $h1, $pdf->pdfText('WASIOFAULU D-E'), 1, 0, 'C', true);
            $pdf->Cell($widths[15], $fullHeight, $pdf->pdfText('WASTANI'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2] + $widths[3], $y + $h1);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('WAV'), 1, 0, 'C', true);
            $pdf->Cell($widths[12], $h2, $pdf->pdfText('WAS'), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[14], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'ranking_schools') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('NA'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('HALMASHAURI'), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $fullHeight, $pdf->pdfText('JINA LA SHULE'), 1, 0, 'C', true);
            
            // Save coordinates at start of WALIOSAJILIWA cell
            $curX = $pdf->GetX();
            $curY = $pdf->GetY();
            
            // Draw background and borders for WALIOSAJILIWA and WALIOFANYA rowspan=2 cells
            $pdf->Cell($widths[4], $fullHeight, '', 1, 0, 'C', true);
            $pdf->Cell($widths[5], $fullHeight, '', 1, 0, 'C', true);
            
            // Print wrapped text for WALIOSAJILIWA
            $pdf->SetXY($curX, $curY + 2.5);
            $pdf->Cell($widths[4], 4, $pdf->pdfText('WALIO'), 0, 0, 'C');
            $pdf->SetXY($curX, $curY + 6.5);
            $pdf->Cell($widths[4], 4, $pdf->pdfText('SAJILIWA'), 0, 0, 'C');
            
            // Print wrapped text for WALIOFANYA
            $pdf->SetXY($curX + $widths[4], $curY + 2.5);
            $pdf->Cell($widths[5], 4, $pdf->pdfText('WALIO'), 0, 0, 'C');
            $pdf->SetXY($curX + $widths[4], $curY + 6.5);
            $pdf->Cell($widths[5], 4, $pdf->pdfText('FANYA'), 0, 0, 'C');
            
            // Return to Row 1 coordinate to draw the remaining Row 1 headers
            $pdf->SetXY($curX + $widths[4] + $widths[5], $curY);
            
            $pdf->Cell($widths[6] + $widths[7] + $widths[8], $h1, $pdf->pdfText('MADARAJA'), 1, 0, 'C', true);
            $pdf->Cell($widths[9] + $widths[10], $h1, $pdf->pdfText('UFAULU (A-C)'), 1, 0, 'C', true);
            $pdf->Cell($widths[11] + $widths[12] + $widths[13], $h1, $pdf->pdfText('MATOKEO'), 1, 1, 'C', true);

            // Draw Row 2 headers
            $pdf->SetXY($curX + $widths[4] + $widths[5], $curY + $h1);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('WASTANI'), 1, 0, 'C', true);
            $pdf->Cell($widths[12], $h2, $pdf->pdfText('KUNDI LA UMAHIRI'), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $h2, $pdf->pdfText('NAFASI'), 1, 1, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'regional_summary') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('SHULE'), 1, 0, 'C', true);
            
            $pdf->Cell($widths[3] + $widths[4] + $widths[5] + $widths[6] + $widths[7], $h1, $pdf->pdfText('MADARAJA'), 1, 0, 'C', true);
            $pdf->Cell($widths[8] + $widths[9], $h1, $pdf->pdfText('UFAULU (A-C)'), 1, 0, 'C', true);
            $pdf->Cell($widths[10] + $widths[11], $h1, $pdf->pdfText('UFAULU (D-E)'), 1, 0, 'C', true);
            $pdf->Cell($widths[12] + $widths[13], $h1, $pdf->pdfText('MATOKEO'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2], $y + $h1);
            $pdf->Cell($widths[3], $h2, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('D'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('E'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[12], $h2, $pdf->pdfText('WASTANI'), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $h2, $pdf->pdfText('KUNDI LA UMAHIRI'), 1, 1, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'council_summary') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('HALMASHAURI'), 1, 0, 'C', true);

            $pdf->Cell($widths[3] + $widths[4] + $widths[5], $h1, $pdf->pdfText('MADARAJA'), 1, 0, 'C', true);
            $pdf->Cell($widths[6] + $widths[7], $h1, $pdf->pdfText('UFAULU (A-C)'), 1, 0, 'C', true);
            $pdf->Cell($widths[8] + $widths[9], $h1, $pdf->pdfText('UFAULU (D-E)'), 1, 0, 'C', true);
            $pdf->Cell($widths[10] + $widths[11], $h1, $pdf->pdfText('MATOKEO'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2], $y + $h1);
            $pdf->Cell($widths[3], $h2, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('WASTANI'), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('NAFASI'), 1, 1, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'regional_overall_summary') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('NA'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('MKOA'), 1, 0, 'C', true);

            $pdf->Cell($widths[2] + $widths[3] + $widths[4], $h1, $pdf->pdfText('MADARAJA'), 1, 0, 'C', true);
            $pdf->Cell($widths[5] + $widths[6], $h1, $pdf->pdfText('UFAULU (A-C)'), 1, 0, 'C', true);
            $pdf->Cell($widths[7] + $widths[8], $h1, $pdf->pdfText('UFAULU (D-E)'), 1, 0, 'C', true);
            $pdf->Cell($widths[9] + $widths[10], $h1, $pdf->pdfText('MATOKEO'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1], $y + $h1);
            $pdf->Cell($widths[2], $h2, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $h2, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('WASTANI /300'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('NAFASI'), 1, 1, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'subject_private_summary') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('SOMO'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('SHULE'), 1, 0, 'C', true);

            $pdf->Cell($widths[3] + $widths[4] + $widths[5] + $widths[6] + $widths[7], $h1, $pdf->pdfText('MADARAJA'), 1, 0, 'C', true);
            $pdf->Cell($widths[8] + $widths[9], $h1, $pdf->pdfText('UFAULU (A-C)'), 1, 0, 'C', true);
            $pdf->Cell($widths[10] + $widths[11], $h1, $pdf->pdfText('UFAULU (D-E)'), 1, 0, 'C', true);
            $pdf->Cell($widths[12] + $widths[13], $h1, $pdf->pdfText('MATOKEO'), 1, 1, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2], $y + $h1);
            $pdf->Cell($widths[3], $h2, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[6], $h2, $pdf->pdfText('D'), 1, 0, 'C', true);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('E'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[12], $h2, $pdf->pdfText('WASTANI'), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $h2, $pdf->pdfText('KUNDI LA UMAHIRI'), 1, 1, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        } elseif ($type === 'subject_distribution') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('SOMO'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('SHULE'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('JINSI'), 1, 0, 'C', true);

            // Save coordinates at start of WALIOSAJILIWA cell
            $curX = $pdf->GetX();
            $curY = $pdf->GetY();

            // Draw background and borders for WALIOSAJILIWA and WALIOFANYA rowspan=2 cells
            $pdf->Cell($widths[3], $fullHeight, '', 1, 0, 'C', true);

            // Print wrapped text for WALIOSAJILIWA
            $pdf->SetXY($curX, $curY + ($h1 + $h2)/2 - 4);
            $pdf->Cell($widths[3], 4, $pdf->pdfText('WALIO'), 0, 0, 'C');
            $pdf->SetXY($curX, $curY + ($h1 + $h2)/2);
            $pdf->Cell($widths[3], 4, $pdf->pdfText('SAJILIWA'), 0, 0, 'C');

            // Return to Row 1 coordinate to draw WASIOFANYA
            $pdf->SetXY($curX + $widths[3], $curY);
            $pdf->Cell($widths[4] + $widths[5], $h1, $pdf->pdfText('WASIOFANYA'), 1, 0, 'C', true);

            // Save coordinates at start of WALIOFANYA cell
            $curX2 = $pdf->GetX();
            $pdf->Cell($widths[6], $fullHeight, '', 1, 0, 'C', true);
            // Print wrapped text for WALIOFANYA
            $pdf->SetXY($curX2, $curY + ($h1 + $h2)/2 - 4);
            $pdf->Cell($widths[6], 4, $pdf->pdfText('WALIO'), 0, 0, 'C');
            $pdf->SetXY($curX2, $curY + ($h1 + $h2)/2);
            $pdf->Cell($widths[6], 4, $pdf->pdfText('FANYA'), 0, 0, 'C');

            // Return to Row 1 coordinate to draw MADARAJA, UFAULU (A-C), MATOKEO
            $pdf->SetXY($curX2 + $widths[6], $curY);
            $pdf->Cell($widths[7] + $widths[8] + $widths[9] + $widths[10] + $widths[11], $h1, $pdf->pdfText('MADARAJA'), 1, 0, 'C', true);
            $pdf->Cell($widths[12] + $widths[13], $h1, $pdf->pdfText('UFAULU (A-C)'), 1, 0, 'C', true);
            $pdf->Cell($widths[14], $h1, $pdf->pdfText('MATOKEO'), 1, 1, 'C', true);

            // Draw Row 2 headers
            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2] + $widths[3], $curY + $h1);
            $pdf->Cell($widths[4], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[5], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);

            $pdf->SetXY($x + $widths[0] + $widths[1] + $widths[2] + $widths[3] + $widths[4] + $widths[5] + $widths[6], $curY + $h1);
            $pdf->Cell($widths[7], $h2, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $h2, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $h2, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $h2, $pdf->pdfText('D'), 1, 0, 'C', true);
            $pdf->Cell($widths[11], $h2, $pdf->pdfText('E'), 1, 0, 'C', true);
            $pdf->Cell($widths[12], $h2, $pdf->pdfText('JML'), 1, 0, 'C', true);
            $pdf->Cell($widths[13], $h2, $pdf->pdfText('%'), 1, 0, 'C', true);
            $pdf->Cell($widths[14], $h2, $pdf->pdfText('WASTANI'), 1, 1, 'C', true);

            $pdf->SetXY($x, $y + $fullHeight);
        }
    }

    private function proficiencyLabel(?string $gradeOrValue): string
    {
        $value = trim((string) $gradeOrValue);
        $upper = strtoupper($value);

        return match (true) {
            str_contains($upper, 'DARAJA A') || $upper === 'A' || $upper === 'BORA'
                => 'Daraja A (Bora)',

            str_contains($upper, 'DARAJA B') || $upper === 'B' || $upper === 'NZURI SANA' || $upper === 'VIZURI SANA' || $upper === 'VIZURI'
                => 'Daraja B (Nzuri Sana)',

            str_contains($upper, 'DARAJA C') || $upper === 'C' || $upper === 'NZURI'
                => 'Daraja C (Nzuri)',

            str_contains($upper, 'DARAJA D') || $upper === 'D'
                => 'Daraja D',

            str_contains($upper, 'DARAJA E') || $upper === 'E'
                => 'Daraja E',

            default => $value,
        };
    }

    private function nectaSchoolProficiencyFromAverage(mixed $average): string
    {
        if ($average === null || $average === '') {
            return '';
        }

        $value = (float) str_replace(',', '', (string) $average);

        return match (true) {
            $value >= 241 && $value <= 300 => 'Daraja A (Bora)',
            $value >= 181 && $value < 241 => 'Daraja B (Nzuri Sana)',
            $value >= 121 && $value < 181 => 'Daraja C (Nzuri)',
            $value >= 61  && $value < 121 => 'Daraja D (Inaridhisha)',
            $value >= 0   && $value < 61  => 'Daraja E (Hafifu)',
            default => '',
        };
    }

    private function translateSubjectName(?string $subject): string
    {
        $value = trim((string) $subject);
        $key = strtoupper($value);

        return match ($key) {
            'CIVIC AND MORAL EDUCATION' => 'URAIA NA MAADILI',
            'KISWAHILI' => 'KISWAHILI',
            'SOCIAL STUDIES AND VOCATIONAL SKILLS' => 'MAARIFA YA JAMII NA STADI ZA KAZI',
            'SCIENCE AND TECHNOLOGY' => 'SAYANSI NA TEKNOLOJIA',
            'ENGLISH LANGUAGE' => 'ENGLISH LANGUAGE',
            'MATHEMATICS' => 'HISABATI',
            default => $value,
        };
    }

    private function getSubjectCompetenceFromAverage(mixed $average): string
    {
        if ($average === null || $average === '') {
            return '';
        }

        $val = floatval($average);
        if ($val >= 40.17) {
            return 'Daraja A (Bora)';
        } elseif ($val >= 30.17) {
            return 'Daraja B (Nzuri Sana)';
        } elseif ($val >= 20.17) {
            return 'Daraja C (Nzuri)';
        } elseif ($val >= 10.17) {
            return 'Daraja D (Inaridhisha)';
        } else {
            return 'Daraja E (Hafifu)';
        }
    }
}
