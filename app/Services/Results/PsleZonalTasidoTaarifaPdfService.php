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
            public string $zoneName = 'TASIDO';
            public int $examYear = 2026;
            public string $primaryFont = 'Helvetica';
            public string $footerOffice = 'Ofisi ya Waziri Mkuu - TAMISEMI';

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
                } elseif ($fontSetting === 'poppins' && $poppinsLoaded) {
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
                if ($this->isCoverPage) {
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
                if ($this->isCoverPage) {
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

            public function addPortraitPage(int $marginTop = 15, int $marginBottom = 18, int $marginLeft = 10, int $marginRight = 10): void
            {
                $this->AddPage('P', 'A4');
                $this->SetMargins($marginLeft, $marginTop, $marginRight);
                $this->SetAutoPageBreak(true, $marginBottom);
            }

            public function addLandscapePage(int $marginTop = 12, int $marginBottom = 15, int $marginLeft = 10, int $marginRight = 10): void
            {
                $this->AddPage('L', 'A4');
                $this->SetMargins($marginLeft, $marginTop, $marginRight);
                $this->SetAutoPageBreak(true, $marginBottom);
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
        $pdf->initReport($data['meta']['font_family']);
        $pdf->AliasNbPages();
        
        // Custom margins from settings
        $pdf->addPortraitPage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            $data['meta']['margin_left'],
            $data['meta']['margin_right']
        );

        // ------------------ COVER PAGE ------------------
        // Draw top aesthetic colored band
        $pdf->SetFillColor(8, 39, 109);
        $pdf->Rect(0, 0, 210, 15, 'F');

        $pdf->SetY(25);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont($pdf->primaryFont, 'B', 12);
        
        // Office Heading
        $pdf->MultiCell(0, 6, $pdf->pdfText($data['meta']['office_heading']), 0, 'C');
        $pdf->Ln(2);
        $pdf->SetFont($pdf->primaryFont, 'B', 11);
        $pdf->Cell(0, 6, $pdf->pdfText("ACADEMIC ZONE: " . strtoupper($data['meta']['subtitle'])), 0, 1, 'C');
        $pdf->Ln(8);

        // Government Emblem
        if ($data['meta']['show_logo']) {
            $emblem = public_path('images/emblem.png');
            if (is_file($emblem)) {
                $pdf->Image($emblem, (210 - 32) / 2, $pdf->GetY(), 32, 32);
                $pdf->Ln(38);
            } else {
                $pdf->Ln(20);
            }
        } else {
            $pdf->Ln(20);
        }

        // Titles
        $pdf->SetFont($pdf->primaryFont, 'B', 15);
        $pdf->MultiCell(0, 7, $pdf->pdfText($data['meta']['cover_title']), 0, 'C');
        $pdf->Ln(10);

        // Draw operational info block at the bottom
        $pdf->SetY(190);
        $pdf->SetDrawColor(8, 39, 109);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(4);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Kanda ya Taaluma:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($pdf->zoneName), 0, 1);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Mratibu wa Kanda (REO):"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['operational']['reo_name']), 0, 1);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Mratibu Taaluma Kanda (RTO):"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['operational']['rto_name']), 0, 1);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Kituo cha Usahihishaji:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['operational']['marking_center']), 0, 1);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Tarehe za Mtihani:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['meta']['exam_dates']), 0, 1);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Mahali / Mahudhurio:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText(str_replace("\n", " ", $data['meta']['secretariat'])), 0, 1);

        $pdf->Line(20, $pdf->GetY() + 4, 190, $pdf->GetY() + 4);

        // ------------------ TABLE OF CONTENTS ------------------
        $pdf->isCoverPage = false;
        $pdf->addPortraitPage(
            $data['meta']['margin_top'],
            $data['meta']['margin_bottom'],
            10,
            10
        );

        $pdf->SetFont($pdf->primaryFont, 'B', 14);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 10, $pdf->pdfText("YALIYOMO (TABLE OF CONTENTS)"), 0, 1, 'C');
        $pdf->Ln(5);

        $tocItems = [
            "1. UTANGULIZI NA TAARIFA ZA WATAHINIWA (Jedwali Na. 1 na 2)",
            "2. UCHAMBUZI WA MATOKEO NA TAKWIMU ZA WATAHINIWA (Jedwali Na. 3a, 3b, 4 na 5)",
            "3. HALI YA UFAULU WA HALMASHAURI KWA MADARAJA (Jedwali Na. 6)",
            "4. HALI YA UFAULU WA HALMASHAURI KWA MASOMO NA MADARAJA (SHULE ZA SERIKALI) (Jedwali Na. 7)",
            "5. HALI YA UFAULU KWA SHULE KUMI BORA KIKANDA (Jedwali Na. 8)",
            "6. HALI YA UFAULU KWA SHULE KUMI DUNI (Jedwali Na. 9)",
            "7. HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI) (Jedwali Na. 10)",
            "8. HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA SERIKALI NA BINAFSI) (Jedwali Na. 11)",
            "9. HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA BINAFSI) (Jedwali Na. 12)",
            "10. MAFANIKIO",
            "11. CHANGAMOTO ZILIZOJITOKEZA KATIKA UENDESHAJI WA MTIHANI",
            "12. UTATUZI WA CHANGAMOTO",
            "13. MAONI NA MAPENDEKEZO",
            "14. HITIMISHO",
            "15. KARATASI YA UIDHINISHAJI (APPROVAL PAGE)"
        ];

        $pdf->SetFont($pdf->primaryFont, 'B', 9.5);
        foreach ($tocItems as $item) {
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(140, 7.0, $pdf->pdfText($item), 0, 0, 'L');
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont($pdf->primaryFont, 'I', 8.5);
            $pdf->Cell(0, 7.0, $pdf->pdfText("Ukurasa wa Ripoti"), 0, 1, 'R');
            $pdf->SetFont($pdf->primaryFont, 'B', 9.5);

            $currY = $pdf->GetY();
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetLineWidth(0.2);
            $pdf->Line(10, $currY, 200, $currY);
        }
        $pdf->Ln(10);

        // Helper closures for formatting chapters
        $chapterHeader = function(string $num, string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 8, $pdf->pdfText($num . ". " . strtoupper($title)), 0, 1, 'L');
            $pdf->SetDrawColor(15, 23, 42);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(3);
        };

        $sectionHeader = function(string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 10);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 7, $pdf->pdfText($title), 0, 1, 'L');
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
                $pdf->SetX(15);
                $pdf->Cell(6, 5.2, $pdf->pdfText(($index + 1) . "."), 0, 0);
                $pdf->MultiCell(0, 5.2, $pdf->pdfText($item), 0, 'J');
                $pdf->Ln(2);
            }
            $pdf->Ln(2);
        };

        // ------------------ SECTION 1 ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("1", "UTANGULIZI NA TAARIFA ZA WATAHINIWA");
        $renderParagraph($data['narratives']['introduction']);
        $renderParagraph($data['narratives']['taarifa_za_watahiniwa']);
        
        $sectionHeader("Jedwali Na. 1: Watahiniwa waliosajiliwa na waliofanya Mtihani");
        
        $t1Headers = ['S/N', 'Mkoa', 'Idadi ya Shule', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Sat ME', 'Sat KE', 'Sat JUMLA', 'Mahudhurio %'];
        $t1Widths = [10, 32, 20, 17, 17, 20, 17, 17, 20, 20];
        $t1Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
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
        $this->renderTable($pdf, $t1Headers, $t1Widths, $t1Aligns, $t1Rows, $t1Total, true, 'attendance');

        // ------------------ SECTION 2 (Table 2 & Section 2 text) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $sectionHeader("Jedwali na. 2: Watahiniwa wasiofanya mtihani");
        
        $t2Headers = ['S/N', 'Mkoa', 'Idadi ya Shule', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Abs ME', 'Abs KE', 'Abs JUMLA', 'Asilimia (%)'];
        $t2Widths = [10, 32, 20, 17, 17, 20, 17, 17, 20, 20];
        $t2Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
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
        $this->renderTable($pdf, $t2Headers, $t2Widths, $t2Aligns, $t2Rows, $t2Total, true, 'absenteeism');
        $pdf->Ln(5);

        $chapterHeader("2", "UCHAMBUZI WA MATOKEO NA TAKWIMU ZA WATAHINIWA");
        $sectionHeader("Hali ya ufaulu ngazi ya Kanda");
        $renderParagraph($data['narratives']['hali_ya_ufaulu_kanda']);

        // ------------------ SECTION 3 (Tables 3a, 3b, 4, 5) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $sectionHeader("Jedwali Na. 3a: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Wastani wa Ufaulu");
        
        $t3aHeaders = ['NA', 'Mkoa', 'Daraja A', 'Daraja B', 'Daraja C', 'Ufaulu A-C', 'Ufaulu %', 'D-E', 'Feli %', 'Wastani /300', 'Nafasi'];
        $t3aWidths = [8, 35, 16, 16, 16, 18, 18, 16, 18, 21, 8];
        $t3aAligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'C'];
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
        $this->renderTable($pdf, $t3aHeaders, $t3aWidths, $t3aAligns, $t3aRows);
        $pdf->Ln(5);

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
        $this->renderTable($pdf, $t3aHeaders, $t3aWidths, $t3aAligns, $t3bRows);

        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $sectionHeader("Jedwali Na. 4: Ufaulu Kikanda kwa Madaraja - Shule za Serikali");
        
        $t4Headers = ['S/N', 'Mkoa', 'Idadi Shule', 'A', 'B', 'C', 'A-C JML', 'A-C %', 'D', 'E', 'D-E JML', 'D-E %', 'Wastani', 'Umahiri'];
        $t4Widths = [8, 26, 16, 11, 11, 11, 14, 14, 11, 11, 14, 14, 15, 14];
        $t4Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'C'];
        $t4Rows = [];
        foreach ($data['table4'] as $row) {
            $t4Rows[] = [
                $row['sn'],
                $row['region'],
                number_format($row['schools_count']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $row['competence']
            ];
        }
        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t4Rows);
        $pdf->Ln(5);

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
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['fail_de']),
                number_format($row['fail_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                $row['competence']
            ];
        }
        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t5Rows);

        // ------------------ SECTION 4 (Table 6 & Section 3 text) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("3", "HALI YA UFAULU WA HALMASHAURI KWA MADARAJA");
        $renderParagraph($data['narratives']['hali_ya_ufaulu_halmashauri']);

        $sectionHeader("Jedwali Na: 6: Hali ya ufaulu wa Halmashauri kwa madaraja");
        
        $t6Headers = ['S/N', 'Mkoa', 'Halmashauri', 'A', 'B', 'C', 'A-C JML', 'A-C %', 'D-E JML', 'D-E %', 'Wastani', 'Nafasi'];
        $t6Widths = [8, 22, 32, 11, 11, 11, 15, 15, 15, 15, 17, 8];
        $t6Aligns = ['C', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'C'];
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
        $this->renderTable($pdf, $t6Headers, $t6Widths, $t6Aligns, $t6Rows);

        // ------------------ SECTION 5 (Table 7 & Section 4 text) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("4", "HALI YA UFAULU WA HALMASHAURI KWA MASOMO NA MADARAJA (SHULE ZA SERIKALI)");
        $renderParagraph($data['narratives']['ufaulu_halmashauri_masomo_madaraja_gov']);

        $sectionHeader("Jedwali Na. 7: Msambao wa Ufaulu wa shule Kumi Bora za Serikali kwa Madaraja");
        
        $t7Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'Reg', 'Fanya', 'A', 'B', 'C', 'A-C', 'A-C %', 'Wastani', 'Umahiri', 'Nafasi'];
        $t7Widths = [8, 20, 25, 35, 10, 10, 9, 9, 9, 11, 11, 13, 12, 8];
        $t7Aligns = ['C', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'C', 'C'];
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
                $row['competence'],
                $row['sn']
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t7Rows);

        // ------------------ SECTION 6 (Table 8 & Section 5 text) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
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
                $row['competence'],
                $row['sn']
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t8Rows);

        // ------------------ SECTION 7 (Table 9 & Section 6 text) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("6", "HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI NA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_shule_10_duni']);

        $sectionHeader("Jedwali Na. 9: Msambao wa Ufaulu wa Shule Kumi Duni kwa Masomo na Madaraja Kikanda");
        
        $t9Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'Umiliki', 'ME', 'KE', 'JML', 'A', 'B', 'C', 'A-C', 'A-C %', 'D-E', 'D-E %', 'Wastani'];
        $t9Widths = [8, 20, 20, 32, 16, 9, 9, 11, 8, 8, 8, 10, 10, 10, 10, 11];
        $t9Aligns = ['C', 'L', 'L', 'L', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
        $t9Rows = [];
        foreach ($data['table9'] as $row) {
            $t9Rows[] = [
                $row['sn'],
                $row['region'],
                $row['council'],
                $row['school'],
                $row['ownership'],
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
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("7", "HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI)");
        $renderParagraph($data['narratives']['ufaulu_shule_10_duni_gov']);

        $sectionHeader("Jedwali Na. 10: Msambao wa Ufaulu wa Shule Kumi Duni za Serikali kwa Masomo na Madaraja Kikanda");
        
        $t10Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'ME', 'KE', 'JML', 'ME', 'KE', 'JML', '%', 'ME', 'KE', 'JML', '%', 'Wastani'];
        $t10Widths = [8, 18, 18, 30, 9, 9, 10, 9, 9, 10, 10, 9, 9, 10, 10, 12];
        $t10Aligns = ['C', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
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
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("8", "HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA SERIKALI NA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_masomo']);

        $sectionHeader("Jedwali na. 11: Msambao wa Ufaulu wa Masomo kwa Madaraja Kikanda");
        
        $t11Headers = ['Somo', 'Shule', 'Jinsi', 'Reg', 'Abs', 'Abs %', 'Fanya', 'A', 'B', 'C', 'D', 'E', 'Pass', 'Pass %', 'Wastani'];
        $t11Widths = [28, 12, 10, 11, 10, 11, 11, 9, 9, 9, 9, 9, 12, 12, 18];
        $t11Aligns = ['L', 'R', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
        $t11Rows = [];
        foreach ($data['table11'] as $row) {
            $t11Rows[] = [
                $row['subject'],
                number_format($row['schools_count']),
                $row['gender'],
                number_format($row['registered']),
                number_format($row['absent']),
                number_format($row['absent_pct'], 2) . '%',
                number_format($row['sat']),
                number_format($row['a']),
                number_format($row['b']),
                number_format($row['c']),
                number_format($row['d']),
                number_format($row['e']),
                number_format($row['pass']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['average_marks'], 2)
            ];
        }
        $this->renderTable($pdf, $t11Headers, $t11Widths, $t11Aligns, $t11Rows);

        // ------------------ SECTION 10 (Table 12 & Section 9 text) ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("9", "HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA BINAFSI)");
        $renderParagraph($data['narratives']['ufaulu_masomo_binafsi']);

        $sectionHeader("Jedwali Na. 12: Ufaulu Kikanda kwa Masomo (shule za binafsi)");
        
        $t12Headers = ['S/N', 'Somo', 'Shule', 'A', 'B', 'C', 'D', 'E', 'Pass', 'Pass %', 'Fail', 'Fail %', 'Wastani', 'Umahiri'];
        $t12Widths = [8, 32, 12, 11, 11, 11, 11, 11, 14, 14, 14, 14, 15, 12];
        $t12Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'C'];
        $t12Rows = [];
        foreach ($data['table12'] as $row) {
            $t12Rows[] = [
                $row['sn'],
                $row['subject'],
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
                $row['competence']
            ];
        }
        $this->renderTable($pdf, $t12Headers, $t12Widths, $t12Aligns, $t12Rows);

        // ------------------ BULLETS & CONCLUSION ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        
        $chapterHeader("10", "MAFANIKIO");
        $renderBullets($data['narratives']['mafanikio']);
        $pdf->Ln(5);

        $chapterHeader("11", "CHANGAMOTO ZILIZOJITOKEZA KATIKA UENDESHAJI WA MTIHANI");
        $renderBullets($data['narratives']['changamoto']);
        $pdf->Ln(5);

        $chapterHeader("12", "UTATUZI WA CHANGAMOTO");
        $renderBullets($data['narratives']['utatuzi']);

        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], 10, 10);
        $chapterHeader("13", "MAONI NA MAPENDEKEZO");
        $renderBullets($data['narratives']['maoni_mapendekezo']);
        $pdf->Ln(5);

        $chapterHeader("14", "HITIMISHO");
        $renderParagraph($data['narratives']['hitimisho']);
        $pdf->Ln(10);

        // ------------------ APPROVAL SHEET ------------------
        $chapterHeader("15", "KARATASI YA UIDHINISHAJI (APPROVAL PAGE)");
        $pdf->Ln(5);

        $renderParagraph("Taarifa hii ya Tathmini ya Mtihani wa Mock Darasa la VII kwa mwaka 2026 katika Kanda ya Academic Zone ya TASIDO imejadiliwa, kuhakikiwa na kupitishwa rasmi na Kamati ya Mitihani ya Kanda.");
        $pdf->Ln(10);

        $pdf->SetFont($pdf->primaryFont, 'B', 10);
        $pdf->Cell(95, 6, $pdf->pdfText("Imeandaliwa na (Prepared By):"), 0, 0, 'L');
        $pdf->Cell(95, 6, $pdf->pdfText("Imehakikiwa na Kuidhinishwa na (Approved By):"), 0, 1, 'L');

        $pdf->Ln(15);

        $pdf->SetFont($pdf->primaryFont, 'B', 9.5);
        $pdf->Cell(95, 5, $pdf->pdfText("_______________________________________"), 0, 0, 'L');
        $pdf->Cell(95, 5, $pdf->pdfText("_______________________________________"), 0, 1, 'L');

        $pdf->Cell(95, 5, $pdf->pdfText($data['operational']['rto_name']), 0, 0, 'L');
        $pdf->Cell(95, 5, $pdf->pdfText($data['operational']['reo_name']), 0, 1, 'L');

        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(95, 4, $pdf->pdfText($data['operational']['prepared_by_title']), 0, 0, 'L');
        $pdf->Cell(95, 4, $pdf->pdfText($data['operational']['approved_by_title']), 0, 1, 'L');

        $pdf->Cell(95, 4, $pdf->pdfText("Kanda ya Taaluma: " . $pdf->zoneName), 0, 0, 'L');
        $pdf->Cell(95, 4, $pdf->pdfText("Kanda ya Taaluma: " . $pdf->zoneName), 0, 1, 'L');

        $pdf->Cell(95, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 0, 'L');
        $pdf->Cell(95, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 1, 'L');

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
        string $doubleHeaderType = ''
    ): void {
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.2);

        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        $headerHeight = $isDoubleHeader ? 13.0 : 6.5;
        $rowHeight = 6.0;

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
                $pdf->Cell($widths[$colIdx], 6.5, $pdf->pdfText($header), 1, 0, 'C', true);
            }
            $pdf->Ln(6.5);
        }

        $pdf->SetFont($pdf->primaryFont, '', 7.5);
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
                        $pdf->Cell($widths[$colIdx], 6.5, $pdf->pdfText($header), 1, 0, 'C', true);
                    }
                    $pdf->Ln(6.5);
                }

                $pdf->SetFont($pdf->primaryFont, '', 7.5);
                $pdf->SetTextColor(30, 41, 59);
            }

            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);

            foreach ($widths as $colIdx => $w) {
                $val = $row[$colIdx] ?? '';
                $align = $aligns[$colIdx] ?? 'C';
                $pdf->Cell($w, 6.0, $pdf->pdfText((string)$val), 1, 0, $align, true);
            }
            $pdf->Ln(6.0);
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
            foreach ($widths as $colIdx => $w) {
                $val = $totalRow[$colIdx] ?? '';
                $align = $aligns[$colIdx] ?? 'C';
                $pdf->Cell($w, 6.5, $pdf->pdfText((string)$val), 1, 0, $align, true);
            }
            $pdf->Ln(6.5);
        }
        $pdf->Ln(4);
    }

    private function renderCustomDoubleHeader(\FPDF $pdf, array $widths, string $type): void
    {
        $pdf->SetFont($pdf->primaryFont, 'B', 7.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(203, 213, 225);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $h1 = 7;
        $h2 = 6;
        $fullHeight = $h1 + $h2;

        if ($type === 'attendance') {
            $pdf->Cell($widths[0], $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('Mkoa'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('Shule'), 1, 0, 'C', true);
            $pdf->Cell($widths[3] + $widths[4] + $widths[5], $h1, $pdf->pdfText('WALIOSAJILIWA'), 1, 0, 'C', true);
            $pdf->Cell($widths[6] + $widths[7] + $widths[8], $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $fullHeight, $pdf->pdfText('Mahudhurio %'), 1, 1, 'C', true);

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
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('Mkoa'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('Shule'), 1, 0, 'C', true);
            $pdf->Cell($widths[3] + $widths[4] + $widths[5], $h1, $pdf->pdfText('WALIOSAJILIWA'), 1, 0, 'C', true);
            $pdf->Cell($widths[6] + $widths[7] + $widths[8], $h1, $pdf->pdfText('WASIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $fullHeight, $pdf->pdfText('Asilimia %'), 1, 1, 'C', true);

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
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('Mkoa'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('Halmashauri'), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $fullHeight, $pdf->pdfText('Jina la Shule'), 1, 0, 'C', true);
            $pdf->Cell($widths[4], $fullHeight, $pdf->pdfText('Umiliki'), 1, 0, 'C', true);
            $pdf->Cell($widths[5] + $widths[6] + $widths[7], $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[8], $fullHeight, $pdf->pdfText('A'), 1, 0, 'C', true);
            $pdf->Cell($widths[9], $fullHeight, $pdf->pdfText('B'), 1, 0, 'C', true);
            $pdf->Cell($widths[10], $fullHeight, $pdf->pdfText('C'), 1, 0, 'C', true);
            $pdf->Cell($widths[11] + $widths[12], $h1, $pdf->pdfText('A-C'), 1, 0, 'C', true);
            $pdf->Cell($widths[13] + $widths[14], $h1, $pdf->pdfText('D-E'), 1, 0, 'C', true);
            $pdf->Cell($widths[15], $fullHeight, $pdf->pdfText('Wastani'), 1, 1, 'C', true);

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
            $pdf->Cell($widths[1], $fullHeight, $pdf->pdfText('Mkoa'), 1, 0, 'C', true);
            $pdf->Cell($widths[2], $fullHeight, $pdf->pdfText('Halmashauri'), 1, 0, 'C', true);
            $pdf->Cell($widths[3], $fullHeight, $pdf->pdfText('Jina la Shule'), 1, 0, 'C', true);
            $pdf->Cell($widths[4] + $widths[5] + $widths[6], $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
            $pdf->Cell($widths[7] + $widths[8] + $widths[9] + $widths[10], $h1, $pdf->pdfText('WALIOFAULU A-C'), 1, 0, 'C', true);
            $pdf->Cell($widths[11] + $widths[12] + $widths[13] + $widths[14], $h1, $pdf->pdfText('WASIOFAULU D-E'), 1, 0, 'C', true);
            $pdf->Cell($widths[15], $fullHeight, $pdf->pdfText('Wastani'), 1, 1, 'C', true);

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
        }
    }
}
