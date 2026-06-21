<?php

namespace App\Services\Results;

use Illuminate\Support\Facades\Log;

class PsleZonalResultBookPdfService
{
    public function generate(array $data, string $outputPath): void
    {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }
        require_once app_path('Support/Pdf/fpdf.php');

        $narrative = new ZonalResultBookNarrativeService();

        $pdf = new class('P', 'mm', 'A4') extends \FPDF {
            public bool $isCoverPage = true;
            public string $zoneName = 'TASIDO';
            public int $examYear = 2026;
            public string $primaryFont = 'Helvetica';

            protected function setupFonts(): void
            {
                $fontDir = app_path('Support/Pdf/font');

                $regular = $fontDir . '/poppins.php';
                $bold = $fontDir . '/poppinsb.php';
                $italic = $fontDir . '/poppinsi.php';
                $boldItalic = $fontDir . '/poppinsbi.php';

                if (
                    file_exists($regular) &&
                    file_exists($bold) &&
                    file_exists($italic) &&
                    file_exists($boldItalic)
                ) {
                    $this->AddFont('Poppins', '', 'poppins.php');
                    $this->AddFont('Poppins', 'B', 'poppinsb.php');
                    $this->AddFont('Poppins', 'I', 'poppinsi.php');
                    $this->AddFont('Poppins', 'BI', 'poppinsbi.php');

                    $this->primaryFont = 'Poppins';
                    return;
                }

                $this->primaryFont = 'Helvetica';
            }

            public function initReport(): void
            {
                $this->setupFonts();
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
                $this->Cell(0, 4, $this->pdfText("KITABU CHA MATOKEO KANDA YA " . strtoupper($this->zoneName) . " - MWAKA " . $this->examYear), 0, 1, 'R');
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
                $this->Cell(100, 5, $this->pdfText("Mfumo wa IRMS - Ofisi ya Waziri Mkuu - TAMISEMI"), 0, 0, 'L');
                $this->Cell(0, 5, $this->pdfText("Ukurasa " . $this->PageNo() . " / {nb}"), 0, 0, 'R');
            }

            public function addPortraitPage(): void
            {
                $this->AddPage('P', 'A4');
                $this->SetMargins(15, 15, 15);
                $this->SetAutoPageBreak(true, 18);
            }

            public function addLandscapePage(): void
            {
                $this->AddPage('L', 'A4');
                $this->SetMargins(10, 12, 10);
                $this->SetAutoPageBreak(true, 15);
            }

            public function getLMargin(): float
            {
                return $this->lMargin;
            }

            public function getRMargin(): float
            {
                return $this->rMargin;
            }

            public function getBMargin(): float
            {
                return $this->bMargin;
            }

            public function getPageWidth(): float
            {
                return $this->w;
            }

            public function getPageHeight(): float
            {
                return $this->h;
            }

            public function getCurOrientation(): string
            {
                return $this->CurOrientation;
            }
        };

        $pdf->isCoverPage = true;
        $pdf->zoneName = 'TASIDO';
        $pdf->examYear = (int)$data['meta']['exam_year'];
        $pdf->initReport();
        $pdf->AliasNbPages();
        $pdf->addPortraitPage();

        // COVER PAGE
        // 1. Draw top band
        $pdf->SetFillColor(8, 39, 109);
        $pdf->Rect(0, 0, 210, 15, 'F');

        $pdf->SetY(25);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont($pdf->primaryFont, 'B', 12);
        $pdf->Cell(0, 6, $pdf->pdfText("OFISI YA WAZIRI MKUU"), 0, 1, 'C');
        $pdf->Cell(0, 6, $pdf->pdfText("REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT"), 0, 1, 'C');
        $pdf->Cell(0, 6, $pdf->pdfText("ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)"), 0, 1, 'C');
        $pdf->Ln(10);

        // Emblem
        $emblem = public_path('images/emblem.png');
        if (is_file($emblem)) {
            $pdf->Image($emblem, (210 - 32) / 2, $pdf->GetY(), 32, 32);
            $pdf->Ln(38);
        } else {
            $pdf->Ln(20);
        }

        $pdf->SetFont($pdf->primaryFont, 'B', 16);
        $pdf->Cell(0, 10, $pdf->pdfText("PSLE ZONAL RESULT BOOK - " . $pdf->examYear), 0, 1, 'C');
        $pdf->SetFont($pdf->primaryFont, 'B', 11);
        $pdf->Cell(0, 6, $pdf->pdfText("(KITABU CHA MATOKEO YA KANDA)"), 0, 1, 'C');
        $pdf->Ln(8);

        $pdf->SetFont($pdf->primaryFont, 'B', 13);
        $pdf->MultiCell(0, 6, $pdf->pdfText("TATHMINI YA MTIHANI WA UTAMILIFU WA DARASA LA SABA\n(PSLE ZONAL MOCK)"), 0, 'C');
        $pdf->SetFont($pdf->primaryFont, 'B', 14);
        $pdf->Cell(0, 10, $pdf->pdfText("MWAKA " . $pdf->examYear), 0, 1, 'C');

        // Draw operational info block at the bottom
        $pdf->SetY(195);
        $pdf->SetDrawColor(8, 39, 109);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(4);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Kanda ya Taaluma:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['meta']['zone_name']), 0, 1);

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
        $pdf->Cell(60, 5, $pdf->pdfText("Mkoa wa Moderation:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['operational']['moderation_region']), 0, 1);

        $pdf->SetFont($pdf->primaryFont, 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, $pdf->pdfText("Tarehe ya Kuzalishwa:"), 0, 0);
        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(0, 5, $pdf->pdfText($data['meta']['generated_at']), 0, 1);

        $pdf->Line(20, $pdf->GetY() + 4, 190, $pdf->GetY() + 4);

        // YALIYOMO (TABLE OF CONTENTS)
        $pdf->isCoverPage = false;
        $pdf->addPortraitPage();

        $pdf->SetFont($pdf->primaryFont, 'B', 14);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 10, $pdf->pdfText("YALIYOMO"), 0, 1, 'C');
        $pdf->Ln(5);

        $tocItems = [
            "1. Muhtasari wa Kitendaji",
            "2. Utangulizi",
            "3. Maandalizi ya Mtihani",
            "4. Utungaji na Moderation",
            "5. Uzalishaji na Usambazaji",
            "6. Ufanyikaji na Usimamizi wa Mtihani",
            "7. Usahihishaji na Uingizaji wa Alama",
            "8. Takwimu za Usajili na Mahudhurio",
            "9. Tathmini ya Jumla ya Kanda",
            "10. Tathmini ya Matokeo Ki-Mkoa",
            "11. Tathmini ya Matokeo Ki-Halmashauri",
            "12. Halmashauri Bora Kumi Kikanda",
            "13. Halmashauri za Mwisho Kumi Kikanda",
            "14. Shule Bora Kumi Kikanda",
            "15. Shule za Mwisho Kumi Kikanda",
            "16. Tathmini ya Ufaulu kwa Masomo",
            "17. Tathmini ya Ufaulu kwa Umiliki wa Shule",
            "18. Uhakiki wa Ubora wa Data",
            "19. Changamoto Zilizobainika",
            "20. Mapendekezo",
            "21. Hitimisho",
            "22. Uidhinishaji"
        ];

        $pdf->SetFont($pdf->primaryFont, 'B', 10);
        foreach ($tocItems as $item) {
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(120, 7.0, $pdf->pdfText($item), 0, 0, 'L');
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont($pdf->primaryFont, 'I', 9);
            $pdf->Cell(0, 7.0, $pdf->pdfText("Sehemu ya Ripoti"), 0, 1, 'R');
            $pdf->SetFont($pdf->primaryFont, 'B', 10);

            $currY = $pdf->GetY();
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetLineWidth(0.2);
            $pdf->Line($pdf->getLMargin(), $currY, $pdf->getPageWidth() - $pdf->getRMargin(), $currY);
        }
        $pdf->Ln(10);

        // NARRATIVES SECTION
        $pdf->addPortraitPage();

        // Helper closures
        $chapterHeader = function(string $num, string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 12);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 8, $pdf->pdfText($num . ". " . strtoupper($title)), 0, 1, 'L');
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($pdf->getLMargin(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->getRMargin(), $pdf->GetY());
            $pdf->Ln(3);
        };

        $chapterSubHeader = function(string $char, string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell(0, 7, $pdf->pdfText($char . ". " . $title), 0, 1, 'L');
            $pdf->Ln(1);
        };

        $renderParagraph = function(string $text) use ($pdf) {
            $clean = str_replace('**', '', $text);
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(0, 5.2, $pdf->pdfText($clean), 0, 'J');
            $pdf->Ln(3.5);
        };

        $renderBullets = function(string $text) use ($pdf) {
            $lines = explode("\n", $text);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (str_starts_with($line, '*')) {
                    $clean = trim(substr($line, 1));
                    $pdf->SetX(20);
                    $pdf->SetFont($pdf->primaryFont, 'B', 9);
                    $pdf->Cell(4, 5.2, chr(149), 0, 0);
                    $pdf->SetFont($pdf->primaryFont, '', 9);
                    $pdf->MultiCell(0, 5.2, $pdf->pdfText(str_replace('**', '', $clean)), 0, 'J');
                    $pdf->Ln(1.5);
                } elseif (preg_match('/^\d+\.\s+(.*)/', $line, $matches)) {
                    $clean = trim($matches[1]);
                    $pdf->SetX(20);
                    $pdf->SetFont($pdf->primaryFont, 'B', 9);
                    $pdf->Cell(6, 5.2, $line[0] . '.', 0, 0);
                    $pdf->SetFont($pdf->primaryFont, '', 9);
                    $pdf->MultiCell(0, 5.2, $pdf->pdfText(str_replace('**', '', $clean)), 0, 'J');
                    $pdf->Ln(1.5);
                } else {
                    $pdf->SetFont($pdf->primaryFont, '', 9);
                    $pdf->MultiCell(0, 5.2, $pdf->pdfText(str_replace('**', '', $line)), 0, 'J');
                    $pdf->Ln(3.5);
                }
            }
            $pdf->Ln(2);
        };

        // 1. MUHTASARI WA KITENDAJI
        $chapterHeader("1", "Muhtasari wa Kitendaji");
        $renderParagraph($narrative->getExecutiveSummary($data));
        $pdf->Ln(4);

        // 2. UTANGULIZI
        $chapterHeader("2", "Utangulizi");
        $renderParagraph($narrative->getIntroduction($data));
        $pdf->Ln(4);

        // 3. MAANDALIZI
        $chapterHeader("3", "Maandalizi ya Mtihani");
        $renderParagraph($narrative->getPreparations($data));
        $pdf->Ln(4);

        // 4. UTUNGAJI NA MODERATION
        $chapterHeader("4", "Utungaji na Moderation");
        $renderParagraph($narrative->getModeration($data));
        $pdf->Ln(4);

        // 5. UZALISHAJI NA USAMBAZAJI
        $chapterHeader("5", "Uzalishaji na Usambazaji");
        $renderBullets($narrative->getProduction($data));
        $pdf->Ln(4);

        // 6. UFANYIKAJI NA USIMAMIZI WA MTIHANI
        $chapterHeader("6", "Ufanyikaji na Usimamizi wa Mtihani");
        $renderParagraph($narrative->getExecution($data));
        $pdf->Ln(4);

        // 7. USAHIHISHAJI NA UINGIZAJI WA ALAMA
        $chapterHeader("7", "Usahihishaji na Uingizaji wa Alama");
        $renderBullets($narrative->getMarking($data));
        $pdf->Ln(4);

        // 8. TAKWIMU ZA USAJILI NA MAHUDHURIO
        $pdf->addPortraitPage();
        $chapterHeader("8", "Takwimu za Usajili na Mahudhurio");
        $renderParagraph("Jedwali lifuatalo linaonesha mchanganuo wa watahiniwa waliosajiliwa, waliofanya mtihani, na wasiofanya mtihani kwa kila Mkoa katika Kanda yetu:");

        // Table 1 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 1: Usajili na Mahudhurio ya Watahiniwa Ki-Mkoa"), 0, 1, 'L');
        $pdf->Ln(2);

        $t1Headers = ['S/N', 'Mkoa', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Sat ME', 'Sat KE', 'Sat JUMLA', 'Abs ME', 'Abs KE', 'Abs JUMLA', 'Asilimia (%)'];
        $t1Widths = [12, 65, 18, 18, 22, 18, 18, 22, 18, 18, 22, 26];
        $t1Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];

        $t1Rows = [];
        foreach ($data['attendance']['region_rows'] as $index => $rrow) {
            $t1Rows[] = [
                $index + 1,
                $rrow['name'],
                number_format($rrow['registered_m']),
                number_format($rrow['registered_f']),
                number_format($rrow['registered_t']),
                number_format($rrow['sat_m']),
                number_format($rrow['sat_f']),
                number_format($rrow['sat_t']),
                number_format($rrow['absent_m']),
                number_format($rrow['absent_f']),
                number_format($rrow['absent_t']),
                number_format($rrow['attendance_rate'], 2) . '%'
            ];
        }
        $t1Total = [
            '-',
            'JUMLA KUU',
            number_format($data['attendance']['registered_male']),
            number_format($data['attendance']['registered_female']),
            number_format($data['attendance']['registered_total']),
            number_format($data['attendance']['sat_male']),
            number_format($data['attendance']['sat_female']),
            number_format($data['attendance']['sat_total']),
            number_format($data['attendance']['absent_male']),
            number_format($data['attendance']['absent_female']),
            number_format($data['attendance']['absent_total']),
            number_format($data['attendance']['attendance_rate'], 2) . '%'
        ];

        $this->renderTable($pdf, $t1Headers, $t1Widths, $t1Aligns, $t1Rows, $t1Total, true);
        $pdf->Ln(6);

        // 9. TATHMINI YA JUMLA YA KANDA
        $pdf->addPortraitPage();
        $chapterHeader("9", "Tathmini ya Jumla ya Kanda");
        $renderParagraph("Sehemu hii inatoa muhtasari wa matokeo yaliyochakatwa na mfumo wa IRMS, ikionesha mgawanyo wa madaraja ya ufaulu na wastani wa alama kikanda.");

        // Table 2 (Landscape)
        $pdf->addLandscapePage();
        $chapterSubHeader("A", "Mgawanyo wa Madaraja ya Ufaulu Kikanda (Grade Distribution)");

        $t2Headers = ['Jinsia', 'Daraja A', 'Daraja B', 'Daraja C', 'Daraja D', 'Daraja E', 'Waliofanya', 'Waliofaulu', 'Ufaulu %'];
        $t2Widths = [50, 26, 26, 26, 26, 26, 33, 33, 31];
        $t2Aligns = ['L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];

        $t2Rows = [];
        foreach ($data['performance']['grade_distribution'] as $gRow) {
            $t2Rows[] = [
                $gRow['gender'],
                number_format($gRow['a']),
                number_format($gRow['b']),
                number_format($gRow['c']),
                number_format($gRow['d']),
                number_format($gRow['e']),
                number_format($gRow['sat']),
                number_format($gRow['pass']),
                number_format($gRow['pct'], 2) . '%'
            ];
        }
        $t2Total = [
            $data['performance']['regional']['gender'] ?? 'JUMLA KUU',
            number_format($data['performance']['regional']['a'] ?? 0),
            number_format($data['performance']['regional']['b'] ?? 0),
            number_format($data['performance']['regional']['c'] ?? 0),
            number_format($data['performance']['regional']['d'] ?? 0),
            number_format($data['performance']['regional']['e'] ?? 0),
            number_format($data['performance']['regional']['sat'] ?? 0),
            number_format($data['performance']['regional']['pass'] ?? 0),
            number_format($data['performance']['regional']['pct'] ?? 0, 2) . '%'
        ];

        $this->renderTable($pdf, $t2Headers, $t2Widths, $t2Aligns, $t2Rows, $t2Total);
        $pdf->Ln(8);

        // 10. TATHMINI YA MATOKEO KI-MKOA
        $pdf->addLandscapePage();
        $chapterHeader("10", "Tathmini ya Matokeo Ki-Mkoa");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mlinganisho wa ufaulu na wastani wa alama kati ya Mikoa yote inayounda Kanda ya TASIDO, zilizopangwa kwa kufuata wastani wa alama (Mikoa yenye ufaulu bora zaidi inaanza):"), 0, 'J');
        $pdf->Ln(2);

        $t3Headers = ['Nafasi', 'Mkoa', 'Waliofanya', 'Waliofaulu (A-C)', 'Waliofaulu (D)', 'Waliofeli (E)', 'Wastani', 'Daraja'];
        $t3Widths = [20, 75, 32, 35, 32, 30, 33, 20];
        $t3Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'C'];

        $t3Rows = [];
        foreach ($data['performance']['regions'] as $rRow) {
            $t3Rows[] = [
                $rRow['position'],
                $rRow['name'],
                number_format($rRow['sat']),
                number_format($rRow['pass_ac']),
                number_format($rRow['pass_d']),
                number_format($rRow['fail']),
                number_format($rRow['average_marks'], 2),
                $rRow['grade']
            ];
        }

        $this->renderTable($pdf, $t3Headers, $t3Widths, $t3Aligns, $t3Rows);
        $pdf->Ln(6);

        // 11. TATHMINI YA MATOKEO KI-HALMASHAURI
        $pdf->addLandscapePage();
        $chapterHeader("11", "Tathmini ya Matokeo Ki-Halmashauri");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mlinganisho wa ufaulu na wastani wa alama kati ya Halmashauri zote katika Kanda yetu, zilizopangwa kwa kufuata wastani wa alama (Halmashauri zenye ufaulu bora zaidi inaanza):"), 0, 'J');
        $pdf->Ln(2);

        $t4Headers = ['Nafasi', 'Halmashauri', 'Mkoa', 'Waliofanya', 'Waliofaulu (A-C)', 'Waliofaulu (D)', 'Waliofeli (E)', 'Wastani', 'Daraja'];
        $t4Widths = [18, 55, 35, 27, 30, 27, 25, 27, 18];
        $t4Aligns = ['C', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'C'];

        $t4Rows = [];
        foreach ($data['performance']['councils'] as $cRow) {
            $t4Rows[] = [
                $cRow['position'],
                $cRow['name'],
                $cRow['region'],
                number_format($cRow['sat']),
                number_format($cRow['pass_ac']),
                number_format($cRow['pass_d']),
                number_format($cRow['fail']),
                number_format($cRow['average_marks'], 2),
                $cRow['grade']
            ];
        }

        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t4Rows);
        $pdf->Ln(6);

        // 12. HALMASHAURI BORA KUMI KIKANDA
        $pdf->addLandscapePage();
        $chapterHeader("12", "Halmashauri Bora Kumi Kikanda");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mchanganuo huu unaonesha halmashauri kumi (10) bora zilizoongoza kitaaluma katika Kanda yetu:"), 0, 'J');
        $pdf->Ln(2);

        $t5Headers = ['Nafasi', 'Halmashauri', 'Mkoa', 'Watahiniwa', 'Wastani', 'Daraja'];
        $t5Widths = [20, 90, 60, 32, 45, 30];
        $t5Aligns = ['C', 'L', 'L', 'R', 'R', 'C'];

        $t5Rows = [];
        foreach ($data['performance']['top_councils'] as $tcRow) {
            $t5Rows[] = [
                $tcRow['position'],
                $tcRow['name'],
                $tcRow['region'],
                number_format($tcRow['sat']),
                number_format($tcRow['average_marks'], 2),
                $tcRow['grade']
            ];
        }
        $this->renderTable($pdf, $t5Headers, $t5Widths, $t5Aligns, $t5Rows);
        $pdf->Ln(6);

        // 13. HALMASHAURI ZA MWISHO KUMI KIKANDA
        $pdf->addLandscapePage();
        $chapterHeader("13", "Halmashauri za Mwisho Kumi Kikanda");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mchanganuo huu unaonesha halmashauri kumi (10) za mwisho kitaaluma katika Kanda yetu:"), 0, 'J');
        $pdf->Ln(2);

        $t6Rows = [];
        foreach ($data['performance']['bottom_councils'] as $bcRow) {
            $t6Rows[] = [
                $bcRow['position'],
                $bcRow['name'],
                $bcRow['region'],
                number_format($bcRow['sat']),
                number_format($bcRow['average_marks'], 2),
                $bcRow['grade']
            ];
        }
        $this->renderTable($pdf, $t5Headers, $t5Widths, $t5Aligns, $t6Rows);
        $pdf->Ln(6);

        // 14. SHULE BORA KUMI KIKANDA
        $pdf->addLandscapePage();
        $chapterHeader("14", "Shule Bora Kumi Kikanda");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mchanganuo huu unaonesha shule kumi (10) bora zilizoongoza kitaaluma katika Kanda yetu:"), 0, 'J');
        $pdf->Ln(2);

        $t7Headers = ['Nafasi', 'Jina la Shule', 'Halmashauri', 'Mkoa', 'Umiliki', 'Watahiniwa', 'Wastani', 'Daraja'];
        $t7Widths = [18, 70, 45, 35, 32, 22, 30, 25];
        $t7Aligns = ['C', 'L', 'L', 'L', 'L', 'R', 'R', 'C'];

        $t7Rows = [];
        foreach ($data['performance']['top_schools'] as $sRow) {
            $t7Rows[] = [
                $sRow['position'],
                $sRow['name'],
                $sRow['council'],
                $sRow['region'],
                $sRow['ownership'],
                number_format($sRow['sat']),
                number_format($sRow['average_marks'], 2),
                $sRow['grade']
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t7Rows);
        $pdf->Ln(6);

        // 15. SHULE ZA MWISHO KUMI KIKANDA
        $pdf->addLandscapePage();
        $chapterHeader("15", "Shule za Mwisho Kumi Kikanda");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mchanganuo huu unaonesha shule kumi (10) za mwisho kitaaluma katika Kanda yetu:"), 0, 'J');
        $pdf->Ln(2);

        $t8Rows = [];
        foreach ($data['performance']['bottom_schools'] as $sRow) {
            $t8Rows[] = [
                $sRow['position'],
                $sRow['name'],
                $sRow['council'],
                $sRow['region'],
                $sRow['ownership'],
                number_format($sRow['sat']),
                number_format($sRow['average_marks'], 2),
                $sRow['grade']
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t8Rows);
        $pdf->Ln(6);

        // 16. TATHMINI YA UFAULU KWA MASOMO
        $pdf->addLandscapePage();
        $chapterHeader("16", "Tathmini ya Ufaulu kwa Masomo");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mchanganuo wa ufaulu kwa kila somo kwa kuzingatia idadi ya waliotahiniwa, kiwango cha ufaulu, na nafasi ya somo kitaaluma kikanda:"), 0, 'J');
        $pdf->Ln(2);

        $t9Headers = ['Nafasi', 'Somo', 'Waliotahiniwa', 'Waliofaulu', 'Waliofeli', 'Asilimia (%)', 'Wastani', 'Daraja'];
        $t9Widths = [20, 75, 32, 32, 30, 33, 35, 20];
        $t9Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'C'];

        $t9Rows = [];
        foreach ($data['performance']['subjects'] as $subRow) {
            $t9Rows[] = [
                $subRow['position'],
                $subRow['name'],
                number_format($subRow['sat']),
                number_format($subRow['pass']),
                number_format($subRow['fail']),
                number_format($subRow['pass_rate'], 2) . '%',
                number_format($subRow['average_marks'], 2),
                $subRow['grade']
            ];
        }
        $this->renderTable($pdf, $t9Headers, $t9Widths, $t9Aligns, $t9Rows);
        $pdf->Ln(6);

        // 17. TATHMINI YA UFAULU KWA UMILIKI WA SHULE
        $pdf->addLandscapePage();
        $chapterHeader("17", "Tathmini ya Ufaulu kwa Umiliki wa Shule");
        $pdf->SetFont($pdf->primaryFont, '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, $pdf->pdfText("Mchanganuo unaolinganisha utendaji na ufaulu kati ya shule za Serikali (Government) na shule za Binafsi/Zisizo za Serikali (Non-Government):"), 0, 'J');
        $pdf->Ln(2);

        $t10Headers = ['Umiliki', 'Idadi ya Shule', 'Waliosajiliwa', 'Waliofanya', 'Waliofaulu', 'Waliofeli', 'Ufaulu %', 'Wastani'];
        $t10Widths = [75, 32, 32, 32, 32, 30, 24, 20];
        $t10Aligns = ['L', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];

        $t10Rows = [];
        foreach ($data['performance']['ownership'] as $ownRow) {
            $t10Rows[] = [
                $ownRow['ownership'],
                number_format($ownRow['schools_count']),
                number_format($ownRow['registered']),
                number_format($ownRow['sat']),
                number_format($ownRow['pass']),
                number_format($ownRow['fail']),
                number_format($ownRow['pass_rate'], 2) . '%',
                number_format($ownRow['average_marks'], 2)
            ];
        }
        $this->renderTable($pdf, $t10Headers, $t10Widths, $t10Aligns, $t10Rows);
        $pdf->Ln(6);

        // 18. UHAKIKI WA UBORA WA DATA (Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("18", "Uhakiki wa Ubora wa Data");
        $renderParagraph($data['data_quality']['summary']);

        if (!empty($data['data_quality']['issues'])) {
            $pdf->SetTextColor(185, 28, 28);
            $pdf->SetFont($pdf->primaryFont, 'B', 9);
            $pdf->Cell(0, 6, $pdf->pdfText("Mambo yaliyobainika wakati wa uhakiki (Observations):"), 0, 1);
            $pdf->Ln(2);

            foreach ($data['data_quality']['issues'] as $idx => $issue) {
                $pdf->SetX(20);
                $pdf->SetFont($pdf->primaryFont, 'B', 9);
                $pdf->Cell(6, 5, ($idx + 1) . ".", 0, 0);
                $pdf->SetFont($pdf->primaryFont, '', 9);
                $pdf->MultiCell(0, 5, $pdf->pdfText($issue), 0, 'J');
                $pdf->Ln(1.5);
            }
            $pdf->SetTextColor(30, 41, 59);
        } else {
            $pdf->SetFillColor(240, 253, 244);
            $pdf->SetDrawColor(187, 247, 208);
            $pdf->SetTextColor(21, 128, 61);
            $pdf->SetFont($pdf->primaryFont, '', 9);
            $pdf->MultiCell(0, 6, $pdf->pdfText("Hakuna hitilafu yoyote iliyobainika wakati wa uhakiki wa data ya matokeo ya Kanda."), 1, 'L', true);
            $pdf->SetTextColor(30, 41, 59);
        }
        $pdf->Ln(6);

        // 19. CHANGAMOTO ZILIZOBAINIKA (Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("19", "Changamoto Zilizobainika");
        foreach ($narrative->getChallenges() as $idx => $chal) {
            $pdf->SetX(20);
            $pdf->SetFont($pdf->primaryFont, 'B', 9.5);
            $pdf->Cell(6, 5.2, ($idx + 1) . ".", 0, 0);
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->MultiCell(0, 5.2, $pdf->pdfText(str_replace('**', '', $chal)), 0, 'J');
            $pdf->Ln(1.5);
        }
        $pdf->Ln(6);

        // 20. MAPENDEKEZO (Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("20", "Mapendekezo");
        foreach ($narrative->getRecommendations() as $idx => $rec) {
            $pdf->SetX(20);
            $pdf->SetFont($pdf->primaryFont, 'B', 9.5);
            $pdf->Cell(6, 5.2, ($idx + 1) . ".", 0, 0);
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->MultiCell(0, 5.2, $pdf->pdfText(str_replace('**', '', $rec)), 0, 'J');
            $pdf->Ln(1.5);
        }
        $pdf->Ln(6);

        // 21. HITIMISHO (Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("21", "Hitimisho");
        $renderParagraph("Kamati ya Mitihani ya Kanda inatoa shukrani za dhati kwa wadau wote wa elimu walioshiriki kufanikisha mtihani huu wa utamilifu wa Kanda (PSLE Zonal Mock). Matokeo haya yaonyeshe juhudi zinazohitajika katika kipindi kilichobaki kabla ya mtihani wa Taifa wa Darasa la Saba ili kuongeza ufaulu na ubora wa elimu katika kanda yetu.");
        $pdf->Ln(6);

        // 22. UIDHINISHAJI (Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("22", "Uidhinishaji");
        $pdf->Ln(4);

        $pdf->SetFont($pdf->primaryFont, 'B', 10);
        $pdf->Cell(90, 6, $pdf->pdfText("Imeandaliwa na:"), 0, 0, 'L');
        $pdf->Cell(90, 6, $pdf->pdfText("Imehakikiwa na Kuidhinishwa na:"), 0, 1, 'L');

        $pdf->Ln(15);

        $pdf->SetFont($pdf->primaryFont, 'B', 9.5);
        $pdf->Cell(90, 5, $pdf->pdfText("_______________________________________"), 0, 0, 'L');
        $pdf->Cell(90, 5, $pdf->pdfText("_______________________________________"), 0, 1, 'L');

        $pdf->Cell(90, 5, $pdf->pdfText($data['operational']['rto_name']), 0, 0, 'L');
        $pdf->Cell(90, 5, $pdf->pdfText($data['operational']['reo_name']), 0, 1, 'L');

        $pdf->SetFont($pdf->primaryFont, '', 9);
        $pdf->Cell(90, 4, $pdf->pdfText($data['operational']['prepared_by_title']), 0, 0, 'L');
        $pdf->Cell(90, 4, $pdf->pdfText($data['operational']['approved_by_title']), 0, 1, 'L');

        $pdf->Cell(90, 4, $pdf->pdfText("Academic Zone: " . $pdf->zoneName), 0, 0, 'L');
        $pdf->Cell(90, 4, $pdf->pdfText("Academic Zone: " . $pdf->zoneName), 0, 1, 'L');

        $pdf->Cell(90, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 0, 'L');
        $pdf->Cell(90, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 1, 'L');

        // Output to file
        $pdf->Output('F', $outputPath);
    }

    private function renderTable(
        \FPDF $pdf,
        array $headers,
        array $widths,
        array $aligns,
        array $rows,
        ?array $totalRow = null,
        bool $isAttendanceTable = false
    ): void {
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.2);

        $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        $headerHeight = $isAttendanceTable ? 13.0 : 6.5;
        $rowHeight = 6.0;

        if ($pdf->GetY() + $headerHeight + $rowHeight > $limit) {
            if ($pdf->getCurOrientation() === 'L') {
                $pdf->addLandscapePage();
            } else {
                $pdf->addPortraitPage();
            }
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
        }

        if ($isAttendanceTable) {
            $this->renderAttendanceGroupedHeader($pdf, $widths);
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

                if ($isAttendanceTable) {
                    $this->renderAttendanceGroupedHeader($pdf, $widths);
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

            if ($fill) {
                $pdf->SetFillColor(248, 250, 252);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

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

    private function renderAttendanceGroupedHeader(\FPDF $pdf, array $widths): void
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

        $wSn = $widths[0];
        $wRegion = $widths[1];

        $wRegistered = $widths[2] + $widths[3] + $widths[4];
        $wSat = $widths[5] + $widths[6] + $widths[7];
        $wAbsent = $widths[8] + $widths[9] + $widths[10];
        $wPercent = $widths[11];

        $pdf->Cell($wSn, $fullHeight, $pdf->pdfText('S/N'), 1, 0, 'C', true);
        $pdf->Cell($wRegion, $fullHeight, $pdf->pdfText('Mkoa'), 1, 0, 'C', true);

        $pdf->Cell($wRegistered, $h1, $pdf->pdfText('WALIOSAJILIWA'), 1, 0, 'C', true);
        $pdf->Cell($wSat, $h1, $pdf->pdfText('WALIOFANYA'), 1, 0, 'C', true);
        $pdf->Cell($wAbsent, $h1, $pdf->pdfText('WASIOFANYA'), 1, 0, 'C', true);

        $pdf->Cell($wPercent, $fullHeight, $pdf->pdfText('Asilimia (%)'), 1, 1, 'C', true);

        $pdf->SetXY($x + $wSn + $wRegion, $y + $h1);

        foreach ([
            [$widths[2], 'ME'],
            [$widths[3], 'KE'],
            [$widths[4], 'JUMLA'],
            [$widths[5], 'ME'],
            [$widths[6], 'KE'],
            [$widths[7], 'JUMLA'],
            [$widths[8], 'ME'],
            [$widths[9], 'KE'],
            [$widths[10], 'JUMLA'],
        ] as [$width, $label]) {
            $pdf->Cell($width, $h2, $pdf->pdfText($label), 1, 0, 'C', true);
        }

        $pdf->SetXY($x, $y + $fullHeight);
    }
}
