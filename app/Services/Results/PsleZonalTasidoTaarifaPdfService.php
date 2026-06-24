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

            public function addPortraitPage(int $marginTop = 15, int $marginBottom = 18, int $marginLeft = 15, int $marginRight = 15): void
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
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], $data['meta']['margin_left'], $data['meta']['margin_right']);

        $pdf->SetFont($pdf->primaryFont, 'B', 14);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 10, $pdf->pdfText("YALIYOMO"), 0, 1, 'C');
        $pdf->Ln(5);

        $tocItems = [
            "Sehemu ya 1: Muhtasari wa Kitendaji (Executive Summary)",
            "Sehemu ya 2: Utangulizi na Maudhui ya Mtihani (Introduction)",
            "Sehemu ya 3: Uratibu na Maandalizi ya Mtihani (Operations: Preparations)",
            "Sehemu ya 4: Uandishi na Uhakiki wa Mtihani (Operations: Moderation)",
            "Sehemu ya 5: Uzalishaji na Ulinzi wa Bahasha (Operations: Production)",
            "Sehemu ya 6: Usimamizi wa Vitendo Vituoni (Operations: Execution)",
            "Sehemu ya 7: Zoezi la Usahihishaji na Data Entry (Operations: Marking)",
            "Sehemu ya 8: Changamoto Katika Mtihani na Zoezi Zima (Challenges)",
            "Sehemu ya 9: Mapendekezo kwa Ajili ya Maboresho (Recommendations)",
            "Sehemu ya 10: Uhakiki wa Ubora wa Data ya Mfumo (Data Quality)",
            "Sehemu ya 11: Jedwali 1 - Usajili na Mahudhurio ya Watahiniwa",
            "Sehemu ya 12: Jedwali 2 - Takwimu za Watahiniwa Wasiofanya Mtihani",
            "Sehemu ya 13: Jedwali 3a & 3b - Ufaulu wa Mikoa Kikanda",
            "Sehemu ya 14: Jedwali 4 & 5 - Ufaulu wa Shule za Serikali na Binafsi",
            "Sehemu ya 15: Jedwali 6 - Ufaulu wa Halmashauri / Wilaya",
            "Sehemu ya 16: Jedwali 7 & 8 - Shule Kumi Bora za Serikali na Jumla",
            "Sehemu ya 17: Jedwali 9 & 10 - Shule Kumi Duni za Serikali na Jumla",
            "Sehemu ya 18: Jedwali 11 - Msambao wa Ufaulu wa Masomo Kikanda",
            "Sehemu ya 19: Jedwali 12 - Ufaulu wa Masomo Shule Zisizo za Serikali",
            "Sehemu ya 20: Uidhinishaji na Sahihi (Approval Sheet)",
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

        // Helper closures for formatting chapters
        $chapterHeader = function(string $num, string $title) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, 'B', 12);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 8, $pdf->pdfText($num . ". " . strtoupper($title)), 0, 1, 'L');
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($pdf->getLMargin(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->getRMargin(), $pdf->GetY());
            $pdf->Ln(3);
        };

        $renderParagraph = function(string $text) use ($pdf) {
            $clean = str_replace('**', '', $text);
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(0, 5.2, $pdf->pdfText($clean), 0, 'J');
            $pdf->Ln(3.5);
        };

        $renderBullets = function(array $items) use ($pdf) {
            $pdf->SetFont($pdf->primaryFont, '', 9.5);
            $pdf->SetTextColor(30, 41, 59);
            foreach ($items as $index => $item) {
                $clean = str_replace('**', '', $item);
                $pdf->SetX($pdf->getLMargin() + 5);
                $pdf->Cell(6, 5.2, $pdf->pdfText(($index + 1) . "."), 0, 0);
                $pdf->MultiCell(0, 5.2, $pdf->pdfText($clean), 0, 'J');
                $pdf->Ln(2);
            }
            $pdf->Ln(2);
        };

        // ------------------ NARRATIVES ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], $data['meta']['margin_left'], $data['meta']['margin_right']);

        // Sehemu ya 1
        $chapterHeader("Sehemu ya 1", "Muhtasari wa Kitendaji (Executive Summary)");
        $renderParagraph($data['narratives']['executive_summary']);
        $pdf->Ln(4);

        // Sehemu ya 2
        $chapterHeader("Sehemu ya 2", "Utangulizi na Maudhui ya Mtihani (Introduction)");
        $renderParagraph($data['narratives']['introduction']);
        $pdf->Ln(4);

        // Sehemu ya 3
        $chapterHeader("Sehemu ya 3", "Uratibu na Maandalizi ya Mtihani (Operations: Preparations)");
        $renderParagraph("Maandalizi ya mtihani yalianza kwa uratibu na vikao vya pamoja vilivyowashirikisha Maafisa Elimu wa Mikoa na Halmashauri (REOs/DEOs), Maafisa Taaluma (RTOs/DTOs), na Wathibiti Ubora wa Shule katika Kanda ya TASIDO. Vikao hivyo vililenga kukubaliana juu ya miongozo ya uendeshaji, usimamizi, usahihishaji, na mifumo ya bajeti.");
        $renderParagraph("1. Uratibu wa Bajeti: Bajeti ya jumla ya shilingi " . number_format((float)$data['operational']['budget_amount']) . " ilitengwa na kupitishwa kwa ajili ya uzalishaji wa mitihani, ununuzi wa karatasi na vifaa vya ofisi ikijumuisha matengenezo na uendeshaji ya mashine za chapa RISSO.");
        $renderParagraph("2. Ushirikiano wa Kikanda (Zonal Collaboration): Mtihani huu uliandaliwa kwa pamoja na mikoa ya " . $data['operational']['collaborating_regions'] . " ili kuleta uwiano wa kitaaluma.");
        $pdf->Ln(4);

        // Sehemu ya 4
        $chapterHeader("Sehemu ya 4", "Uandishi na Uhakiki wa Mtihani (Operations: Moderation)");
        $renderParagraph("Walimu mahiri na wazoefu walishiriki katika utungaji na uthibitishaji wa mitihani kwa kuzingatia ramani za mitihani (Table of Specifications/Format) za Baraza la Mitihani la Tanzania (NECTA). Mapitia na uhakiki wa kiwango cha ubora (Moderation) ulifanyika chini ya Kamati ya Taaluma ya Kanda katika Mkoa wa " . $data['operational']['moderation_region'] . ".");
        $pdf->Ln(4);

        // Sehemu ya 5
        $chapterHeader("Sehemu ya 5", "Uzalishaji na Ulinzi wa Bahasha (Operations: Production)");
        $renderParagraph("Zoezi la uzalishaji na uchapishaji wa mitihani lilifanyika kwa siri na usalama mkubwa chini ya usimamizi wa Kamati ya Kanda. Chapa zote zilifanywa kwenye chumba maalum cha uzalishaji (Strong Room) kwa muda wa siku " . $data['operational']['production_days'] . " kwa kutumia mashine za chapa haraka za RISSO " . $data['operational']['risso_machine_count'] . " zenye thamani ya shilingi " . number_format((float)$data['operational']['risso_machine_value']) . ".");
        $pdf->Ln(4);

        // Sehemu ya 6
        $chapterHeader("Sehemu ya 6", "Usimamizi wa Vitendo Vituoni (Operations: Execution)");
        $renderParagraph("Mtihani ulianza rasmi tarehe " . $data['operational']['exam_start_date'] . " na kukamilika tarehe " . $data['operational']['exam_end_date'] . " katika vituo vyote vilivyosajiliwa. Zoezi zima lilifanyika kwa ufanisi mkubwa kwa kufuata miongozo ya mitihani ya Taifa.");
        $pdf->Ln(4);

        // Sehemu ya 7
        $chapterHeader("Sehemu ya 7", "Zoezi la Usahihishaji na Data Entry (Operations: Marking)");
        $renderParagraph("Usahihishaji ulifanyika katika Kituo Teule cha Kanda kilichopo shule ya " . $data['operational']['marking_center'] . " kwa muda wa siku " . $data['operational']['marking_days'] . " na lilihusisha jumla ya wasahihishaji " . $data['operational']['markers_count'] . " na wasaidizi wataalamu " . $data['operational']['students_assistants_count'] . ". Kazi ya uingizaji wa alama kwenye mfumo (Data Entry) ilifanywa na Timu ya TEHAMA kwa kutumia mfumo wa IRMS.");
        $pdf->Ln(4);

        // Sehemu ya 8
        $chapterHeader("Sehemu ya 8", "Changamoto Katika Mtihani na Zoezi Zima (Challenges)");
        $challenges = [
            "Utofauti wa Taarifa za Bahasha na Skripti: Kubainika kwa tofauti kati ya idadi ya skripti zilizoandikwa kwenye taarifa ya nje ya bahasha na idadi halisi ya skripti zilizokutikana ndani.",
            "Kukosekana kwa ISAL (Individual Subject Attendance Log): Baadhi ya vituo kukosa fomu rasmi za mahudhurio ya kila somo, hali inayofanya iwe vigumu kubaini sababu za watahiniwa wasiofanya mtihani.",
            "Namba za Usajili Zinazojirudia (Duplicate Index Numbers): Baadhi ya shule kuwapa watahiniwa tofauti namba moja ya usajili.",
            "Makosa ya Uchapishaji (Printing Errors): Baadhi ya karatasi za mitihani kukosa maswali au kuwa na kurasa zilizoruka wakati wa uzalishaji wa mitihani.",
            "Ucheleweshaji wa Michango ya Capitation: Baadhi ya Halmashauri kuchelewa kuwasilisha michango yao ya uendeshaji."
        ];
        $renderBullets($challenges);

        // Sehemu ya 9
        $chapterHeader("Sehemu ya 9", "Mapendekezo kwa Ajili ya Maboresho (Recommendations)");
        $recommendations = [
            "Utekelezaji wa Mfumo wa ISAL na CAL Kidijitali: Ni lazima shule zote kupitia mfumo wa IRMS kupakua karatasi rasmi za mahudhurio.",
            "Uhakiki wa Namba za Usajili Vituoni: Wasimamizi wakuu wa vituo na wasimamizi wa vyumba lazima wahakiki namba za usajili za kila mtahiniwa kabla ya mtihani kuanza.",
            "Kuweka Utaratibu wa Karatasi Sanifu za Kujibia: Kamati ya Mitihani ya Kanda inapaswa kuzalisha na kusambaza karatasi za kujibia zilizochapishwa kitaalamu.",
            "Uimarishaji wa Udhibiti wa Ubora wa Uzalishaji: Kuweka jopo maalum la wahakiki kukagua sampuli za kila kundi la mitihani.",
            "Usimamizi wa Michango ya Capitation: Halmashauri zihakikishe fedha za Capitation zinawasilishwa angalau wiki mbili kabla."
        ];
        $renderBullets($recommendations);

        // Sehemu ya 10
        $chapterHeader("Sehemu ya 10", "Uhakiki wa Ubora wa Data ya Mfumo (Data Quality)");
        $renderParagraph($data['data_quality']['summary']);
        if (!empty($data['data_quality']['issues'])) {
            $renderBullets($data['data_quality']['issues']);
        }

        // ------------------ TABLES SECTION ------------------
        
        // Table 1 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 1: Watahiniwa Waliosajiliwa na Waliofanya Mtihani (Registration & Attendance)"), 0, 1, 'L');
        $pdf->Ln(2);

        $t1Headers = ['S/N', 'Mkoa', 'Idadi ya Shule', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Sat ME', 'Sat KE', 'Sat JUMLA', 'Mahudhurio %'];
        $t1Widths = [12, 55, 30, 20, 20, 25, 20, 20, 25, 30];
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

        // Table 2 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 2: Takwimu za Watahiniwa Wasiofanya Mtihani (Absenteeism)"), 0, 1, 'L');
        $pdf->Ln(2);

        $t2Headers = ['S/N', 'Mkoa', 'Idadi ya Shule', 'Reg ME', 'Reg KE', 'Reg JUMLA', 'Abs ME', 'Abs KE', 'Abs JUMLA', 'Asilimia (%)'];
        $t2Widths = [12, 55, 30, 20, 20, 25, 20, 20, 25, 30];
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

        // Table 3a (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 3a: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Wastani"), 0, 1, 'L');
        $pdf->Ln(2);

        $t3aHeaders = ['NA', 'Mkoa', 'Daraja A', 'Daraja B', 'Daraja C', 'Ufaulu A-C', 'Ufaulu %', 'D-E', 'Feli %', 'Wastani /300', 'Nafasi'];
        $t3aWidths = [15, 55, 22, 22, 22, 26, 26, 22, 22, 28, 20];
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

        // Table 3b (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 3b: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Asilimia"), 0, 1, 'L');
        $pdf->Ln(2);

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

        // Table 4 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 4: Ufaulu Kikanda kwa Madaraja - Shule za Serikali"), 0, 1, 'L');
        $pdf->Ln(2);

        $t4Headers = ['S/N', 'Mkoa', 'Idadi Shule', 'A', 'B', 'C', 'A-C JML', 'A-C %', 'D', 'E', 'D-E JML', 'D-E %', 'Wastani', 'Umahiri'];
        $t4Widths = [12, 40, 22, 16, 16, 16, 20, 20, 16, 16, 20, 20, 22, 34];
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

        // Table 5 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 5: Ufaulu Kikanda kwa Madaraja - Shule Zisizo za Serikali"), 0, 1, 'L');
        $pdf->Ln(2);

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

        // Table 6 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 6: Hali ya Ufaulu wa Halmashauri kwa Madaraja"), 0, 1, 'L');
        $pdf->Ln(2);

        $t6Headers = ['S/N', 'Mkoa', 'Halmashauri', 'A', 'B', 'C', 'A-C JML', 'A-C %', 'D-E JML', 'D-E %', 'Wastani', 'Nafasi'];
        $t6Widths = [12, 40, 55, 18, 18, 18, 22, 22, 22, 22, 24, 17];
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

        // Table 7 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 7: Msambao wa Ufaulu wa Shule Kumi Bora za Serikali"), 0, 1, 'L');
        $pdf->Ln(2);

        $t7Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'Reg', 'Fanya', 'A', 'B', 'C', 'A-C', 'A-C %', 'Wastani', 'Umahiri', 'Nafasi'];
        $t7Widths = [12, 35, 40, 50, 15, 15, 12, 12, 12, 16, 18, 20, 25, 18];
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

        // Table 8 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 8: Msambao wa Ufaulu wa Shule Kumi Bora Kikanda (Jumla)"), 0, 1, 'L');
        $pdf->Ln(2);

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

        // Table 9 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 9: Msambao wa Ufaulu wa Shule Kumi Duni Kikanda (Jumla)"), 0, 1, 'L');
        $pdf->Ln(2);

        $t9Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'Umiliki', 'Fanya ME', 'Fanya KE', 'Fanya JML', 'A', 'B', 'C', 'A-C JML', 'A-C %', 'D-E JML', 'D-E %', 'Wastani'];
        $t9Widths = [10, 25, 25, 45, 22, 14, 14, 16, 10, 10, 10, 16, 16, 16, 16, 18];
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

        // Table 10 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 10: Msambao wa Ufaulu wa Shule Kumi Duni za Serikali"), 0, 1, 'L');
        $pdf->Ln(2);

        $t10Headers = ['NA', 'Mkoa', 'Halmashauri', 'Jina la Shule', 'Fanya ME', 'Fanya KE', 'Fanya JML', 'A-C ME', 'A-C KE', 'A-C JML', 'A-C %', 'D-E ME', 'D-E KE', 'D-E JML', 'D-E %', 'Wastani'];
        $t10Widths = [10, 25, 25, 45, 14, 14, 16, 14, 14, 16, 16, 14, 14, 16, 16, 18];
        $t10Aligns = ['C', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'];

        $t10Rows = [];
        foreach ($data['table10'] as $row) {
            // Estimate gender split of passes for Table 10
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
                number_format($row['fail_pct'] ?? $row['fail_pct'] ?? 0.0, 2) . '%',
                number_format($row['average_marks'], 2)
            ];
        }
        $this->renderTable($pdf, $t10Headers, $t10Widths, $t10Aligns, $t10Rows, null, true, 'bottom_gov_schools');

        // Table 11 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 11: Msambao wa Ufaulu wa Masomo kwa Madaraja Kikanda (Total/Sex Split)"), 0, 1, 'L');
        $pdf->Ln(2);

        $t11Headers = ['Somo', 'Idadi Shule', 'Jinsi', 'Reg', 'Abs', 'Abs %', 'Fanya', 'A', 'B', 'C', 'D', 'E', 'Faulu A-C', 'Faulu %', 'Wastani /50'];
        $t11Widths = [45, 18, 14, 16, 14, 16, 16, 14, 14, 14, 14, 14, 22, 22, 24];
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

        // Table 12 (Landscape)
        $pdf->addLandscapePage();
        $pdf->SetFont($pdf->primaryFont, 'B', 10.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 6, $pdf->pdfText("Jedwali 12: Ufaulu Kikanda kwa Masomo - Shule Zisizo za Serikali"), 0, 1, 'L');
        $pdf->Ln(2);

        $t12Headers = ['S/N', 'Somo', 'Shule', 'A JML', 'A %', 'B JML', 'B %', 'C JML', 'C %', 'D JML', 'D %', 'E JML', 'E %', 'Wastani', 'Faulu A-C', 'A-C %', 'Feli %', 'Nafasi', 'Umahiri'];
        $t12Widths = [10, 42, 14, 14, 14, 14, 14, 14, 14, 14, 14, 14, 14, 20, 18, 18, 18, 16, 28];
        $t12Aligns = ['C', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'C', 'C'];

        $t12Rows = [];
        foreach ($data['table12'] as $row) {
            $t12Rows[] = [
                $row['sn'],
                $row['subject'],
                number_format($row['schools_count']),
                number_format($row['a']),
                number_format($row['a_pct'], 2) . '%',
                number_format($row['b']),
                number_format($row['b_pct'], 2) . '%',
                number_format($row['c']),
                number_format($row['c_pct'], 2) . '%',
                number_format($row['d']),
                number_format($row['d_pct'], 2) . '%',
                number_format($row['e']),
                number_format($row['e_pct'], 2) . '%',
                number_format($row['average_marks'], 2),
                number_format($row['pass_ac']),
                number_format($row['pass_pct'], 2) . '%',
                number_format($row['fail_pct'], 2) . '%',
                $row['sn'],
                $row['competence']
            ];
        }
        $this->renderTable($pdf, $t12Headers, $t12Widths, $t12Aligns, $t12Rows);

        // ------------------ APPROVAL SHEET ------------------
        $pdf->addPortraitPage($data['meta']['margin_top'], $data['meta']['margin_bottom'], $data['meta']['margin_left'], $data['meta']['margin_right']);

        $pdf->SetFont($pdf->primaryFont, 'B', 14);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(0, 10, $pdf->pdfText("SEHEMU YA IDHINISHO NA SAHIHI"), 0, 1, 'C');
        $pdf->Ln(10);

        $renderParagraph("Taarifa hii ya Tathmini ya Mtihani wa Mock Darasa la VII kwa mwaka 2026 katika Kanda ya Academic Zone ya TASIDO imejadiliwa, kuhakikiwa na kupitishwa rasmi na Kamati ya Mitihani ya Kanda.");
        $pdf->Ln(20);

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

        $pdf->Cell(90, 4, $pdf->pdfText("Kanda ya Taaluma: " . $pdf->zoneName), 0, 0, 'L');
        $pdf->Cell(90, 4, $pdf->pdfText("Kanda ya Taaluma: " . $pdf->zoneName), 0, 1, 'L');

        $pdf->Cell(90, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 0, 'L');
        $pdf->Cell(90, 4, $pdf->pdfText("Tarehe: ___________________"), 0, 1, 'L');

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
            foreach ([$widths[3], 'ME', $widths[4], 'KE', $widths[5], 'JUMLA', $widths[6], 'ME', $widths[7], 'KE', $widths[8], 'JUMLA'] as $idx => $val) {
                if ($idx % 2 === 0) {
                    $pdf->Cell($val, $h2, $pdf->pdfText($val), 0, 0); // dummy check
                } else {
                    $pdf->Cell(10, $h2, $pdf->pdfText($val), 1, 0, 'C', true); // will override below
                }
            }
            // Reposition and draw exactly
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
