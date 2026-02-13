<?php

namespace App\Services\Results;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Results Export Service
 * 
 * Generates PDF and CSV exports of ACSEE results
 * respecting role-based scoping and exam authority compliance
 */
class ResultsExportService
{
    /**
     * Generate PDF export of results
     * 
     * PDF Format (NECTA-compliant):
     * - Header with exam info and school details
     * - Table with candidate results
     * - Each candidate: index number, name, sex, subject grades, division
     * - Footer with export metadata
     */
    public function generatePdf(Collection $results, int $year, ?int $schoolId = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $schoolName = null;
        if ($schoolId) {
            $schoolName = $results->first()?->candidate?->school?->name;
        }

        // Group by school for sectioning
        $grouped = $results->groupBy(function ($result) {
            return $result->candidate->school_id;
        });

        $pdf = \PDF::loadView('exports.acsee-results-pdf', [
            'results' => $results,
            'grouped' => $grouped,
            'year' => $year,
            'schoolName' => $schoolName,
            'exportedAt' => now(),
            'exportedBy' => auth()->user()->name,
        ]);

        $filename = sprintf(
            'ACSEE-Results-%s%s.pdf',
            $year,
            $schoolId ? "-School-{$schoolId}" : '-Complete'
        );

        return $pdf->download($filename);
    }

    /**
     * Generate CSV export of results
     * 
     * CSV Format (analysis-ready):
     * - Headers: Index Number, Name, Sex, Subject Grades, Total Points, Division, School, District, Region
     * - One row per candidate
     * - Compatible with Excel, Google Sheets, R, Python
     */
    public function generateCsv(Collection $results, int $year): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Get unique subjects across all results
        $allSubjects = collect();
        $results->each(function ($result) use ($allSubjects) {
            $result->subjectMarks->each(function ($mark) use ($allSubjects) {
                $allSubjects->push($mark->subject);
            });
        });
        $subjects = $allSubjects->unique('id')->sortBy('code')->values();

        // Build CSV
        $csv = $this->buildCsvContent($results, $subjects);

        $filename = sprintf(
            'ACSEE-Results-%s-%s.csv',
            $year,
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Build CSV content
     */
    protected function buildCsvContent(Collection $results, Collection $subjects): string
    {
        $output = fopen('php://memory', 'w');

        // UTF-8 BOM for Excel compatibility
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        $headers = [
            'Index Number',
            'Candidate Name',
            'Sex',
        ];

        // Add subject columns
        $subjects->each(function ($subject) use (&$headers) {
            $headers[] = "Grade-{$subject['code']}";
        });

        $headers = array_merge($headers, [
            'Total Points',
            'Division',
            'School',
            'District',
            'Region',
            'Exam Year',
        ]);

        fputcsv($output, $headers);

        // Data rows
        $results->each(function ($result) use ($output, $subjects) {
            $row = [
                $result->candidate->candidate_id,
                $result->candidate->full_name,
                $result->candidate->gender === 'M' ? 'Male' : 'Female',
            ];

            // Add subject grades
            $subjects->each(function ($subject) use ($result, &$row) {
                $mark = $result->subjectMarks
                    ->firstWhere('subject_id', $subject['id']);
                $row[] = $mark?->grade ?? '-';
            });

            // Add aggregate data
            $row = array_merge($row, [
                $result->grade_points ?? '-',
                $result->division ?? '-',
                $result->candidate->school?->name ?? '-',
                $result->candidate->school?->district?->name ?? '-',
                $result->candidate->school?->region?->name ?? '-',
                $result->year,
            ]);

            fputcsv($output, $row);
        });

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Validate export request
     */
    public function validateExportRequest(array $filters): bool
    {
        return isset($filters['year']) && !empty($filters['year']);
    }
}
