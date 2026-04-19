<?php

namespace App\Services\Results;

use Illuminate\Support\Collection;

class AcseeResultsFpdfService
{
    public function generate(
        Collection $schoolSections,
        int $year,
        ?string $schoolName,
        \Carbon\CarbonInterface $exportedAt,
        string $exportedBy,
        string $outputPath
    ): void {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $pdf = new class($this, $year, $schoolName, $exportedAt, $exportedBy) extends \FPDF {
            public function __construct(
                private AcseeResultsFpdfService $service,
                private int $year,
                private ?string $schoolName,
                private \Carbon\CarbonInterface $exportedAt,
                private string $exportedBy
            ) {
                parent::__construct('L', 'mm', 'A4');
                $this->SetMargins(8, 10, 8);
                $this->SetAutoPageBreak(true, 10);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                $this->SetFillColor(248, 250, 252);
                $this->Rect(0, 0, 297, 210, 'F');
                $this->SetFillColor(15, 23, 42);
                $this->Rect(0, 0, 297, 4, 'F');

                $emblem = public_path('images/emblem.png');
                if (is_file($emblem)) {
                    $this->Image($emblem, 10, 8, 13, 13);
                    $this->Image($emblem, 274, 8, 13, 13);
                }

                $this->SetY(7);
                $this->SetTextColor(15, 23, 42);
                $this->SetFont('Helvetica', 'B', 10);
                $this->Cell(0, 5, $this->service->text('ACSEE RESULTS EXPORT'), 0, 1, 'C');
                $this->SetTextColor(37, 99, 235);
                $this->SetFont('Helvetica', 'B', 8);
                $this->Cell(0, 4, $this->service->text('Advanced Certificate of Secondary Education - ' . $this->year), 0, 1, 'C');
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 6.5);
                $subtitle = $this->schoolName ? 'Filtered School Export: ' . $this->schoolName : 'Consolidated Premium Results Export';
                $this->Cell(0, 4, $this->service->text($subtitle, 90), 0, 1, 'C');
                $this->Ln(2);
            }

            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetTextColor(100, 116, 139);
                $this->SetFont('Helvetica', '', 6.5);
                $this->Cell(95, 4, $this->service->text('Generated: ' . $this->exportedAt->format('d/m/Y H:i')), 0, 0, 'L');
                $this->Cell(95, 4, $this->service->text('By: ' . $this->exportedBy, 32), 0, 0, 'C');
                $this->Cell(95, 4, $this->service->text('Page ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
            }
        };

        foreach ($schoolSections->values() as $sectionIndex => $section) {
            $pdf->AddPage();
            $this->renderSchoolHeader($pdf, $section, $sectionIndex + 1, $schoolSections->count());
            $this->renderCandidateTable($pdf, $section);
            $this->renderSummaryBlocks($pdf, $section);
            $this->renderSubjectPerformance($pdf, $section);
        }

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

    private function renderSchoolHeader(\FPDF $pdf, array $section, int $position, int $total): void
    {
        $school = $section['school'];
        $overall = $section['overall_performance'];
        $division = $section['division_performance'];

        $y = $pdf->GetY();
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Rect(8, $y, 281, 24, 'DF');

        $pdf->SetXY(12, $y + 3);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(120, 4, $this->text('EXAMINATION CENTRE SNAPSHOT'), 0, 0, 'L');

        $pdf->SetXY(12, $y + 8);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(160, 5, $this->text(($school?->code ? $school->code . ' - ' : '') . ($school?->name ?? 'UNKNOWN SCHOOL'), 72), 0, 0, 'L');

        $pdf->SetXY(12, $y + 14);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->Cell(160, 4, $this->text('Region: ' . ($overall['region'] ?? '-') . '   District: ' . ($overall['district'] ?? '-') . '   Sheet ' . $position . ' of ' . $total, 95), 0, 0, 'L');

        $cards = [
            ['Registered', $overall['registered'] ?? 0],
            ['Passed', $overall['passed'] ?? 0],
            ['Failed', $overall['failed'] ?? 0],
            ['GPA', $overall['gpa_info']['text'] ?? 'N/A'],
        ];

        $cardX = 172;
        foreach ($cards as $i => [$label, $value]) {
            $x = $cardX + ($i * 29);
            $pdf->SetFillColor($label === 'GPA' ? 219 : 239, $label === 'GPA' ? 234 : 246, $label === 'GPA' ? 254 : 255);
            $pdf->Rect($x, $y + 4, 25, 16, 'F');
            $pdf->SetXY($x + 1.5, $y + 6);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('Helvetica', 'B', 5.5);
            $pdf->Cell(22, 3, $this->text(strtoupper($label)), 0, 1, 'C');
            $pdf->SetXY($x + 1.5, $y + 10);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->Cell(22, 5, $this->text((string) $value, 16), 0, 1, 'C');
        }

        $pdf->SetY($y + 28);
    }

    private function renderCandidateTable(\FPDF $pdf, array $section): void
    {
        $subjects = collect($section['subjects']);
        $candidateRows = collect($section['candidate_rows']);
        $subjectCount = max($subjects->count(), 1);
        $subjectWidth = max(8, min(12, (170 - 28) / $subjectCount));

        $headers = [
            ['INDEX #', 22],
            ['CANDIDATE NAME', 52],
            ['SEX', 10],
        ];

        foreach ($subjects as $subject) {
            $headers[] = [$subject['code'], $subjectWidth];
        }

        $headers[] = ['PTS', 12];
        $headers[] = ['DIV', 12];

        $pdf->SetFillColor(15, 23, 42);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 6);
        foreach ($headers as [$label, $width]) {
            $pdf->Cell($width, 8, $this->text($label, 14), 1, 0, 'C', true);
        }
        $pdf->Ln();

        foreach ($candidateRows as $index => $row) {
            if ($pdf->GetY() > 120) {
                $pdf->AddPage();
                $this->renderSchoolHeader($pdf, $section, 1, 1);
                $pdf->SetFillColor(15, 23, 42);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Helvetica', 'B', 6);
                foreach ($headers as [$label, $width]) {
                    $pdf->Cell($width, 8, $this->text($label, 14), 1, 0, 'C', true);
                }
                $pdf->Ln();
            }

            $fill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $pdf->SetFillColor(...$fill);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 6);

            $cells = [
                $row['result']->candidate->candidate_id,
                $this->text($row['result']->candidate->full_name, 32),
                $row['result']->candidate->gender === 'M' ? 'M' : 'F',
            ];

            foreach ($row['subject_grades'] as $grade) {
                $cells[] = $grade;
            }

            $cells[] = $row['status'] === 'COMPLETE' ? ($row['result']->grade_points ?? '-') : $row['status'];
            $cells[] = $row['division'];

            foreach ($headers as $cellIndex => [, $width]) {
                $align = $cellIndex === 1 ? 'L' : 'C';
                if ($cellIndex === count($headers) - 1) {
                    $pdf->SetTextColor(37, 99, 235);
                    $pdf->SetFont('Helvetica', 'B', 6);
                } else {
                    $pdf->SetTextColor(30, 41, 59);
                    $pdf->SetFont('Helvetica', '', 6);
                }
                $pdf->Cell($width, 6.5, $this->text((string) ($cells[$cellIndex] ?? ''), 18), 1, 0, $align, true);
            }
            $pdf->Ln();
        }

        $pdf->Ln(3);
    }

    private function renderSummaryBlocks(\FPDF $pdf, array $section): void
    {
        $overall = $section['overall_performance'];
        $division = $section['division_performance'];

        $y = $pdf->GetY();
        if ($y > 150) {
            $pdf->AddPage();
            $this->renderSchoolHeader($pdf, $section, 1, 1);
            $y = $pdf->GetY();
        }

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Rect(8, $y, 136, 42, 'DF');
        $pdf->Rect(153, $y, 136, 42, 'DF');

        $pdf->SetXY(12, $y + 4);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(120, 4, $this->text('OVERALL PERFORMANCE'), 0, 1, 'L');

        $items = [
            'Region' => $overall['region'] ?? '-',
            'District' => $overall['district'] ?? '-',
            'Registered' => $overall['registered'] ?? 0,
            'Passed' => $overall['passed'] ?? 0,
            'Failed' => $overall['failed'] ?? 0,
            'Centre GPA' => $overall['gpa_info']['text'] ?? 'N/A',
        ];

        $rowY = $y + 10;
        foreach ($items as $label => $value) {
            $pdf->SetXY(12, $rowY);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->Cell(34, 4, $this->text(strtoupper($label)), 0, 0, 'L');
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('Helvetica', '', 6.5);
            $pdf->Cell(90, 4, $this->text((string) $value, 40), 0, 1, 'L');
            $rowY += 5;
        }

        $pdf->SetXY(157, $y + 4);
        $pdf->SetTextColor(16, 185, 129);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(120, 4, $this->text('DIVISION PERFORMANCE'), 0, 1, 'L');

        $divItems = [
            'Registered' => $division['registered'] ?? 0,
            'Absent' => $division['absent'] ?? 0,
            'Sat' => $division['sat'] ?? 0,
            'INC' => $division['inc'] ?? 0,
            'Clean' => $division['clean'] ?? 0,
            'Div I / II / III / IV / 0' => implode(' / ', [
                $division['divisions']['I'] ?? 0,
                $division['divisions']['II'] ?? 0,
                $division['divisions']['III'] ?? 0,
                $division['divisions']['IV'] ?? 0,
                $division['divisions']['0'] ?? 0,
            ]),
        ];

        $rowY = $y + 10;
        foreach ($divItems as $label => $value) {
            $pdf->SetXY(157, $rowY);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->Cell(42, 4, $this->text(strtoupper($label), 24), 0, 0, 'L');
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('Helvetica', '', 6.5);
            $pdf->Cell(82, 4, $this->text((string) $value, 44), 0, 1, 'L');
            $rowY += 5;
        }

        $pdf->SetY($y + 48);
    }

    private function renderSubjectPerformance(\FPDF $pdf, array $section): void
    {
        $subjects = collect($section['subject_performance']);
        if ($subjects->isEmpty()) {
            return;
        }

        if ($pdf->GetY() > 175) {
            $pdf->AddPage();
            $this->renderSchoolHeader($pdf, $section, 1, 1);
        }

        $headers = [
            ['CODE', 14], ['SUBJECT NAME', 54], ['A', 9], ['B', 9], ['C', 9], ['D', 9], ['E', 9], ['S', 9], ['F', 9], ['ABS', 10], ['TOTAL', 11], ['GPA', 12], ['COMPETENCY', 38],
        ];

        $pdf->SetFillColor(15, 23, 42);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 6);
        foreach ($headers as [$label, $width]) {
            $pdf->Cell($width, 8, $this->text($label, 18), 1, 0, 'C', true);
        }
        $pdf->Ln();

        foreach ($subjects as $index => $subject) {
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $this->renderSchoolHeader($pdf, $section, 1, 1);
                $pdf->SetFillColor(15, 23, 42);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Helvetica', 'B', 6);
                foreach ($headers as [$label, $width]) {
                    $pdf->Cell($width, 8, $this->text($label, 18), 1, 0, 'C', true);
                }
                $pdf->Ln();
            }

            $fill = $index % 2 === 0 ? [255, 255, 255] : [248, 250, 252];
            $pdf->SetFillColor(...$fill);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 6);

            $cells = [
                $subject['code'],
                $this->text($subject['name'], 34),
                $subject['gradeA'],
                $subject['gradeB'],
                $subject['gradeC'],
                $subject['gradeD'],
                $subject['gradeE'],
                $subject['gradeS'],
                $subject['gradeF'],
                $subject['absent'],
                $subject['total'],
                number_format((float) $subject['gpa'], 4),
                $this->text($subject['competency'], 22),
            ];

            foreach ($headers as $cellIndex => [, $width]) {
                $align = $cellIndex === 1 || $cellIndex === 12 ? 'L' : 'C';
                $pdf->Cell($width, 6.5, $this->text((string) $cells[$cellIndex], 30), 1, 0, $align, true);
            }
            $pdf->Ln();
        }
    }
}
