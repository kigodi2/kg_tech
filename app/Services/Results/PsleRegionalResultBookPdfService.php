<?php

namespace App\Services\Results;

use App\Models\Region;
use Illuminate\Support\Facades\Log;

class PsleRegionalResultBookPdfService
{
    public function generate(Region $region, array $data, string $outputPath): void
    {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }
        require_once app_path('Support/Pdf/fpdf.php');

        $narrative = new RegionalResultBookNarrativeService();

        $pdf = new class('P', 'mm', 'A4') extends \FPDF {
            public bool $isCoverPage = true;
            public string $regionName = '';
            public int $examYear = 0;

            public function Header(): void
            {
                if ($this->isCoverPage) {
                    return;
                }

                $this->SetY(8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', 'I', 7.5);
                $this->Cell(0, 4, "KITABU CHA MATOKEO MKOA WA " . strtoupper($this->regionName) . " - MWAKA " . $this->examYear, 0, 1, 'R');
                $this->SetDrawColor(226, 232, 240);
                $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
                $this->Ln(4);
            }

            public function Footer(): void
            {
                if ($this->isCoverPage) {
                    return;
                }

                $this->SetY(-15);
                $this->SetDrawColor(226, 232, 240);
                $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
                $this->Ln(1);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 7.5);
                $this->Cell(100, 5, "Mfumo wa IRMS - Ofisi ya Rais - TAMISEMI", 0, 0, 'L');
                $this->Cell(0, 5, "Ukurasa " . $this->PageNo() . " / {nb}", 0, 0, 'R');
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
        $pdf->regionName = $region->name;
        $pdf->examYear = (int)$data['meta']['exam_year'];
        $pdf->AliasNbPages();
        $pdf->addPortraitPage();

        // COVER PAGE
        // 1. Draw top band
        $pdf->SetFillColor(8, 39, 109);
        $pdf->Rect(0, 0, 210, 15, 'F');

        $pdf->SetY(25);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 6, "OFISI YA RAIS", 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 6, "TAWALA ZA MIKOA NA SERIKALI ZA MITAA", 0, 1, 'C');
        $pdf->Cell(0, 6, "OFISI YA MKUU WA MKOA WA " . strtoupper($pdf->regionName), 0, 1, 'C');
        $pdf->Ln(10);

        // Emblem
        $emblem = public_path('images/emblem.png');
        if (is_file($emblem)) {
            $pdf->Image($emblem, (210 - 32) / 2, $pdf->GetY(), 32, 32);
            $pdf->Ln(38);
        } else {
            $pdf->Ln(20);
        }

        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 10, "KITABU CHA MATOKEO", 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, "(RESULT BOOK REPORT)", 0, 1, 'C');
        $pdf->Ln(8);

        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->MultiCell(0, 6, "TATHMINI YA MTIHANI WA UTAMILIFU WA DARASA LA SABA\n(PSLE MOCK)", 0, 'C');
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 10, "MWAKA " . $pdf->examYear, 0, 1, 'C');

        // Draw operational info block at the bottom
        $pdf->SetY(195);
        $pdf->SetDrawColor(8, 39, 109);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(4);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, "Mkuu wa Mkoa (RC):", 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, "Mkuu wa Mkoa wa " . $pdf->regionName, 0, 1);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, "Afisa Elimu wa Mkoa (REO):", 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $data['operational']['reo_name'], 0, 1);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, "Afisa Taaluma wa Mkoa (RTO):", 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $data['operational']['rto_name'], 0, 1);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, "Kituo cha Usahihishaji:", 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $data['operational']['marking_center'], 0, 1);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, "Mkoa wa Moderation:", 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $data['operational']['moderation_region'], 0, 1);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(60, 5, "Tarehe ya Kuzalishwa:", 0, 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $data['meta']['generated_at'], 0, 1);

        $pdf->Line(20, $pdf->GetY() + 4, 190, $pdf->GetY() + 4);

        // NARRATIVES SECTION
        $pdf->isCoverPage = false;
        $pdf->addPortraitPage();

        // Helper closures
        $chapterHeader = function(string $num, string $title) use ($pdf) {
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(8, 39, 109);
            $pdf->Cell(0, 8, $num . ". " . strtoupper($title), 0, 1, 'L');
            $pdf->SetDrawColor(8, 39, 109);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($pdf->getLMargin(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->getRMargin(), $pdf->GetY());
            $pdf->Ln(3);
        };

        $chapterSubHeader = function(string $char, string $title) use ($pdf) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(0, 7, $char . ". " . $title, 0, 1, 'L');
            $pdf->Ln(1);
        };

        $renderParagraph = function(string $text) use ($pdf) {
            $clean = str_replace('**', '', $text);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(0, 5.2, $clean, 0, 'J');
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
                    $pdf->SetFont('Helvetica', 'B', 9.5);
                    $pdf->Cell(4, 5.2, chr(149), 0, 0);
                    $pdf->SetFont('Helvetica', '', 9.5);
                    $pdf->MultiCell(0, 5.2, str_replace('**', '', $clean), 0, 'J');
                    $pdf->Ln(1.5);
                } elseif (preg_match('/^\d+\.\s+(.*)/', $line, $matches)) {
                    $clean = trim($matches[1]);
                    $pdf->SetX(20);
                    $pdf->SetFont('Helvetica', 'B', 9.5);
                    $pdf->Cell(6, 5.2, $line[0] . '.', 0, 0);
                    $pdf->SetFont('Helvetica', '', 9.5);
                    $pdf->MultiCell(0, 5.2, str_replace('**', '', $clean), 0, 'J');
                    $pdf->Ln(1.5);
                } else {
                    $pdf->SetFont('Helvetica', '', 9.5);
                    $pdf->MultiCell(0, 5.2, str_replace('**', '', $line), 0, 'J');
                    $pdf->Ln(3.5);
                }
            }
            $pdf->Ln(2);
        };

        // 1. UTANGULIZI
        $chapterHeader("1", "Utangulizi");
        $renderParagraph($narrative->getIntroduction($data));
        $pdf->Ln(4);

        // 2. MAANDALIZI
        $chapterHeader("2", "Maandalizi ya Mtihani");
        $renderParagraph($narrative->getPreparations($data));
        $pdf->Ln(4);

        // 3. UTUNGAJI NA MODERATION
        $chapterHeader("3", "Utungaji na Moderation");
        $renderParagraph($narrative->getModeration($data));
        $pdf->Ln(4);

        // 4. UZALISHAJI NA USAMBAZAJI
        $chapterHeader("4", "Uzalishaji na Usambazaji");
        $renderBullets($narrative->getProduction($data));
        $pdf->Ln(4);

        // 5. UFANYIKAJI NA RATIBA
        $chapterHeader("5", "Ufanyikaji na Uratibu wa Mtihani");
        $renderParagraph($narrative->getExecution($data));
        $pdf->Ln(4);

        // 6. USAHIHISHAJI NA UINGIZAJI ALAMA
        $chapterHeader("6", "Usahihishaji na Uingizaji Alama");
        $renderBullets($narrative->getMarking($data));
        $pdf->Ln(4);

        // 7. TAKWIMU ZA USAJILI NA MAHUDHURIO
        $pdf->addPortraitPage();
        $chapterHeader("7", "Takwimu za Usajili na Mahudhurio");
        $renderParagraph("Jedwali lifuatalo linaonesha mchanganuo wa watahiniwa waliosajiliwa, waliofanya mtihani, na wasiofanya mtihani kwa kila Halmashauri katika Mkoa wetu:");

        // Table 1 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 6, "Jedwali 1: Usajili na Mahudhurio ya Watahiniwa Ki-Halmashauri", 0, 1, 'L');
        $pdf->Ln(2);

        $t1Headers = ['S/N', 'Halmashauri', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Sat ME', 'Sat KE', 'Sat JUMLA', 'Abs ME', 'Abs KE', 'Abs JUMLA', 'Asilimia (%)'];
        $t1Widths = [12, 65, 18, 18, 22, 18, 18, 22, 18, 18, 22, 26];
        $t1Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
        
        $t1Rows = [];
        foreach ($data['attendance']['council_rows'] as $index => $crow) {
            $t1Rows[] = [
                $index + 1,
                $crow['name'],
                number_format($crow['registered_m']),
                number_format($crow['registered_f']),
                number_format($crow['registered_t']),
                number_format($crow['sat_m']),
                number_format($crow['sat_f']),
                number_format($crow['sat_t']),
                number_format($crow['absent_m']),
                number_format($crow['absent_f']),
                number_format($crow['absent_t']),
                number_format($crow['attendance_rate'], 2) . '%'
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
        
        $this->renderTable($pdf, $t1Headers, $t1Widths, $t1Aligns, $t1Rows, $t1Total);
        $pdf->Ln(6);

        // 8. TATHMINI YA UTENDAJI NA MATOKEO
        $pdf->addPortraitPage();
        $chapterHeader("8", "Tathmini ya Utenda Kazi na Matokeo (Statistical Performance Overview)");
        $renderParagraph("Sehemu hii inatoa muhtasari wa matokeo yaliyochakatwa na mfumo wa IRMS, ikionesha mgawanyo wa madaraja ya ufaulu, GPA, na kulinganisha ufaulu ki-halmashauri, ki-shule, na ki-masomo.");

        // Table 2 & 3 (Landscape)
        $pdf->addLandscapePage();
        $chapterSubHeader("A", "Mgawanyo wa Madaraja ya Ufaulu Kimkoa (Grade Distribution)");
        
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
            $data['performance']['regional']['gender'],
            number_format($data['performance']['regional']['a']),
            number_format($data['performance']['regional']['b']),
            number_format($data['performance']['regional']['c']),
            number_format($data['performance']['regional']['d']),
            number_format($data['performance']['regional']['e']),
            number_format($data['performance']['regional']['sat']),
            number_format($data['performance']['regional']['pass']),
            number_format($data['performance']['regional']['pct'], 2) . '%'
        ];
        
        $this->renderTable($pdf, $t2Headers, $t2Widths, $t2Aligns, $t2Rows, $t2Total);
        $pdf->Ln(8);

        // B. Councilwise Evaluation (Landscape page break for clean formatting)
        $pdf->addLandscapePage();
        $chapterSubHeader("B", "Tathmini ya Matokeo Ki-Halmashauri (Councilwise Performance)");
        
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, "Mlinganisho wa ufaulu na GPA kati ya Halmashauri zote zinazounda Mkoa, zilizopangwa kwa kufuata wastani wa GPA (Halmashauri zenye ufaulu bora zaidi zinaanza):", 0, 'J');
        $pdf->Ln(2);

        $t3Headers = ['Nafasi', 'Halmashauri', 'Waliofanya', 'Waliofaulu (A-C)', 'Waliofaulu (D)', 'Waliofeli (E)', 'Wastani GPA', 'Daraja'];
        $t3Widths = [20, 75, 32, 35, 32, 30, 33, 20];
        $t3Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'C'];
        
        $t3Rows = [];
        foreach ($data['performance']['councils'] as $cRow) {
            $t3Rows[] = [
                $cRow['position'],
                $cRow['name'],
                number_format($cRow['sat']),
                number_format($cRow['pass_ac']),
                number_format($cRow['pass_d']),
                number_format($cRow['fail']),
                number_format($cRow['gpa'], 4),
                $cRow['grade']
            ];
        }
        
        $this->renderTable($pdf, $t3Headers, $t3Widths, $t3Aligns, $t3Rows);
        $pdf->Ln(6);

        // C. Schoolwise Evaluation (Top 10) (Landscape)
        $pdf->addLandscapePage();
        $chapterSubHeader("C", "Tathmini ya Matokeo Ki-Shule (Schoolwise Performance)");
        
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, "Mchanganuo huu unaonesha shule kumi (10) bora zilizoongoza kitaaluma na shule kumi (10) za mwisho katika Mkoa wetu:", 0, 'J');
        $pdf->Ln(2);

        $t4Headers = ['Nafasi', 'Jina la Shule', 'Halmashauri', 'Umiliki', 'Watahiniwa', 'GPA Wastani', 'Daraja'];
        $t4Widths = [20, 85, 50, 40, 22, 35, 25];
        $t4Aligns = ['C', 'L', 'L', 'L', 'R', 'R', 'C'];

        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(0, 6, "1) Shule Bora Kumi (Top 10 Schools) Kimkoa", 0, 1, 'L');
        $pdf->Ln(1);
        
        $t4Rows = [];
        foreach ($data['performance']['top_schools'] as $sRow) {
            $t4Rows[] = [
                $sRow['position'],
                $sRow['name'],
                $sRow['council'],
                $sRow['ownership'],
                number_format($sRow['sat']),
                number_format($sRow['gpa'], 4),
                $sRow['grade']
            ];
        }
        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t4Rows);
        
        // Bottom 10 Schools (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(0, 6, "2) Shule za Mwisho Kumi (Bottom 10 Schools) Kimkoa", 0, 1, 'L');
        $pdf->Ln(1);

        $t5Rows = [];
        foreach ($data['performance']['bottom_schools'] as $sRow) {
            $t5Rows[] = [
                $sRow['position'],
                $sRow['name'],
                $sRow['council'],
                $sRow['ownership'],
                number_format($sRow['sat']),
                number_format($sRow['gpa'], 4),
                $sRow['grade']
            ];
        }
        $this->renderTable($pdf, $t4Headers, $t4Widths, $t4Aligns, $t5Rows);
        $pdf->Ln(6);

        // D. Subjectwise Performance (Landscape)
        $pdf->addLandscapePage();
        $chapterSubHeader("D", "Tathmini ya Matokeo Ki-Masomo (Subjectwise Performance)");
        
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, "Mchanganuo wa ufaulu kwa kila somo kwa kuzingatia idadi ya waliotahiniwa, kiwango cha ufaulu, na nafasi ya somo kitaaluma kimkoa:", 0, 'J');
        $pdf->Ln(2);

        $t6Headers = ['Nafasi', 'Somo', 'Waliotahiniwa', 'Waliofaulu', 'Waliofeli', 'Asilimia (%)', 'Wastani GPA', 'Daraja'];
        $t6Widths = [20, 75, 32, 32, 30, 33, 35, 20];
        $t6Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'C'];
        
        $t6Rows = [];
        foreach ($data['performance']['subjects'] as $subRow) {
            $t6Rows[] = [
                $subRow['position'],
                $subRow['name'],
                number_format($subRow['sat']),
                number_format($subRow['pass']),
                number_format($subRow['fail']),
                number_format($subRow['pass_rate'], 2) . '%',
                number_format($subRow['gpa'], 2),
                $subRow['grade']
            ];
        }
        $this->renderTable($pdf, $t6Headers, $t6Widths, $t6Aligns, $t6Rows);
        $pdf->Ln(6);

        // E. Ownership Result Evaluation (Landscape)
        $pdf->addLandscapePage();
        $chapterSubHeader("E", "Tathmini ya Ufaulu kwa Umiliki (Ownership Performance)");
        
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->MultiCell(0, 5.2, "Mchanganuo unaolinganisha utendaji na ufaulu kati ya shule za Serikali (Government) na shule za Binafsi/Zisizo za Serikali (Non-Government):", 0, 'J');
        $pdf->Ln(2);

        $t7Headers = ['Umiliki', 'Idadi ya Shule', 'Waliosajiliwa', 'Waliofanya', 'Waliofaulu', 'Waliofeli', 'Ufaulu %', 'GPA'];
        $t7Widths = [75, 32, 32, 32, 32, 30, 24, 20];
        $t7Aligns = ['L', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];
        
        $t7Rows = [];
        foreach ($data['performance']['ownership'] as $ownRow) {
            $t7Rows[] = [
                $ownRow['ownership'],
                number_format($ownRow['schools_count']),
                number_format($ownRow['registered']),
                number_format($ownRow['sat']),
                number_format($ownRow['pass']),
                number_format($ownRow['fail']),
                number_format($ownRow['pass_rate'], 2) . '%',
                number_format($ownRow['gpa'], 4)
            ];
        }
        $this->renderTable($pdf, $t7Headers, $t7Widths, $t7Aligns, $t7Rows);
        $pdf->Ln(6);

        // 9. CHANGAMOTO NA MAPENDEKEZO (Return to Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("9", "Changamoto na Mapendekezo");
        
        $chapterSubHeader("A", "Changamoto Zilizobainika (Identified Challenges)");
        foreach ($narrative->getChallenges() as $idx => $chal) {
            $pdf->SetX(20);
            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Cell(6, 5.2, ($idx + 1) . ".", 0, 0);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->MultiCell(0, 5.2, str_replace('**', '', $chal), 0, 'J');
            $pdf->Ln(1.5);
        }
        $pdf->Ln(4);

        $chapterSubHeader("B", "Mapendekezo na Suluhisho za Kisitemu (Recommendations)");
        foreach ($narrative->getRecommendations() as $idx => $rec) {
            $pdf->SetX(20);
            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Cell(6, 5.2, ($idx + 1) . ".", 0, 0);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->MultiCell(0, 5.2, str_replace('**', '', $rec), 0, 'J');
            $pdf->Ln(1.5);
        }
        $pdf->Ln(6);

        // 10. UHAKIKI WA DATA NA UBORA (Portrait)
        $pdf->addPortraitPage();
        $chapterHeader("10", "Uhakiki na Usahihi wa Data (Data Quality Audit)");
        $renderParagraph($data['data_quality']['summary']);
        
        if (!empty($data['data_quality']['issues'])) {
            $pdf->SetTextColor(185, 28, 28); // warning red
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(0, 6, "Mambo yaliyobainika wakati wa uhakiki (Observations):", 0, 1);
            $pdf->Ln(2);
            
            foreach ($data['data_quality']['issues'] as $idx => $issue) {
                $pdf->SetX(20);
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->Cell(6, 5, ($idx + 1) . ".", 0, 0);
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->MultiCell(0, 5, $issue, 0, 'J');
                $pdf->Ln(1.5);
            }
            $pdf->SetTextColor(30, 41, 59);
        } else {
            $pdf->SetFillColor(240, 253, 244); // light green
            $pdf->SetDrawColor(187, 247, 208);
            $pdf->SetTextColor(21, 128, 61);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->MultiCell(0, 6, "Hakuna hitilafu yoyote iliyobainika wakati wa uhakiki wa data ya matokeo ya Mkoa.", 1, 'L', true);
            $pdf->SetTextColor(30, 41, 59);
        }
        $pdf->Ln(10);

        // 11. SIGN-OFF (Portrait)
        if ($pdf->GetY() > 210) {
            $pdf->addPortraitPage();
        }

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(90, 6, "Imeandaliwa na:", 0, 0, 'L');
        $pdf->Cell(90, 6, "Imehakikiwa na Kuidhinishwa na:", 0, 1, 'L');

        $pdf->Ln(15); // space for actual signature

        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell(90, 5, "_______________________________________", 0, 0, 'L');
        $pdf->Cell(90, 5, "_______________________________________", 0, 1, 'L');

        $pdf->Cell(90, 5, $data['operational']['rto_name'], 0, 0, 'L');
        $pdf->Cell(90, 5, $data['operational']['reo_name'], 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(90, 4, $data['operational']['prepared_by_title'], 0, 0, 'L');
        $pdf->Cell(90, 4, $data['operational']['approved_by_title'], 0, 1, 'L');

        $pdf->Cell(90, 4, "Mkoa wa " . $pdf->regionName, 0, 0, 'L');
        $pdf->Cell(90, 4, "Mkoa wa " . $pdf->regionName, 0, 1, 'L');

        $pdf->Cell(90, 4, "Tarehe: ___________________", 0, 0, 'L');
        $pdf->Cell(90, 4, "Tarehe: ___________________", 0, 1, 'L');

        // Output to file
        $pdf->Output('F', $outputPath);
    }

    private function renderTable(\FPDF $pdf, array $headers, array $widths, array $aligns, array $rows, ?array $totalRow = null): void
    {
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.2);

        $pdf->SetFont('Helvetica', 'B', 8.5);

        $hHeight = 6.5;
        foreach ($headers as $colIdx => $header) {
            $pdf->Cell($widths[$colIdx], $hHeight, $header, 1, 0, 'C', true);
        }
        $pdf->Ln($hHeight);

        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(51, 65, 85);

        $fill = false;
        foreach ($rows as $row) {
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
            if ($pdf->GetY() > ($limit - 8)) {
                if ($pdf->getCurOrientation() === 'L') {
                    $pdf->addLandscapePage();
                } else {
                    $pdf->addPortraitPage();
                }
                
                $pdf->SetFont('Helvetica', 'B', 8.5);
                $pdf->SetFillColor(241, 245, 249);
                $pdf->SetTextColor(15, 23, 42);
                foreach ($headers as $colIdx => $header) {
                    $pdf->Cell($widths[$colIdx], $hHeight, $header, 1, 0, 'C', true);
                }
                $pdf->Ln($hHeight);
                $pdf->SetFont('Helvetica', '', 8.5);
                $pdf->SetTextColor(51, 65, 85);
            }

            if ($fill) {
                $pdf->SetFillColor(248, 250, 252);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            foreach ($widths as $colIdx => $w) {
                $val = $row[$colIdx] ?? '';
                $align = $aligns[$colIdx] ?? 'C';
                $pdf->Cell($w, 6.0, $val, 1, 0, $align, true);
            }
            $pdf->Ln(6.0);
            $fill = !$fill;
        }

        if ($totalRow) {
            $limit = $pdf->getPageHeight() - $pdf->getBMargin();
            if ($pdf->GetY() > ($limit - 8)) {
                if ($pdf->getCurOrientation() === 'L') {
                    $pdf->addLandscapePage();
                } else {
                    $pdf->addPortraitPage();
                }
            }
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->SetFillColor(226, 232, 240);
            $pdf->SetTextColor(15, 23, 42);
            foreach ($widths as $colIdx => $w) {
                $val = $totalRow[$colIdx] ?? '';
                $align = $aligns[$colIdx] ?? 'C';
                $pdf->Cell($w, 6.5, $val, 1, 0, $align, true);
            }
            $pdf->Ln(6.5);
        }
        $pdf->Ln(4);
    }
}
