<?php

namespace App\Services\Results;

use App\Models\CandidateResult;
use Illuminate\Database\Eloquent\Collection;

/**
 * Results Export Service
 * 
 * Generates PDF and CSV exports of ACSEE results
 * respecting role-based scoping and exam authority compliance
 */
class ResultsExportService
{
    public function __construct(
        private readonly AcseeResultsFpdfService $fpdfService
    ) {
    }

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

        $schoolSections = $this->buildSchoolSections($results);

        $filename = sprintf(
            'ACSEE-Results-%s%s.pdf',
            $year,
            $schoolId ? "-School-{$schoolId}" : '-Complete'
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'acsee_results_');
        if ($tempPath === false) {
            abort(500, 'Unable to prepare PDF export file.');
        }
        $pdfPath = $tempPath . '.pdf';
        @rename($tempPath, $pdfPath);

        $this->fpdfService->generate(
            $schoolSections,
            $year,
            $schoolName,
            now(),
            auth()->user()->name,
            $pdfPath
        );

        return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
    }

    public function buildSchoolSections(Collection $results): Collection
    {
        return $results->groupBy(function ($result) {
            return $result->candidate->school_id;
        })->map(function (Collection $schoolResults) {
            return $this->buildSchoolSection($schoolResults);
        })->values();
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

    private function buildSchoolSection(Collection $schoolResults): array
    {
        $school = $schoolResults->first()?->candidate?->school;
        $subjects = $schoolResults
            ->flatMap(fn (CandidateResult $result) => $result->subjectMarks ?? collect())
            ->filter(fn ($mark) => $mark->subject)
            ->groupBy('subject_id')
            ->map(function (Collection $marks) {
                $subject = $marks->first()->subject;

                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name' => $subject->name,
                ];
            })
            ->sortBy('code')
            ->values();

        $candidateRows = $schoolResults
            ->map(function (CandidateResult $result) use ($subjects) {
                $status = $this->normalizeResultStatus($result);
                $marksBySubject = $result->subjectMarks->keyBy('subject_id');
                $division = $status === 'COMPLETE'
                    ? $this->normalizeDivisionLabel($result->division)
                    : $status;

                $subjectGrades = $subjects->map(function (array $subject) use ($marksBySubject, $status) {
                    $mark = $marksBySubject->get($subject['id']);

                    if ($status === 'ABS') {
                        return 'ABS';
                    }

                    if ($status === 'INC' && !$mark?->grade_from_average && !$mark?->grade) {
                        return 'INC';
                    }

                    return $mark?->grade_from_average
                        ?? $mark?->grade
                        ?? '-';
                });

                $gpa = $this->calculateCandidateGpa($result);

                return [
                    'result' => $result,
                    'status' => $status,
                    'division' => $division,
                    'subject_grades' => $subjectGrades,
                    'gpa' => $gpa,
                ];
            })
            ->values();

        $divisionCounts = ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0];
        $absent = 0;
        $incomplete = 0;
        $clean = 0;
        $centreGpaTotal = 0.0;
        $centreGpaCount = 0;

        foreach ($candidateRows as $row) {
            if ($row['status'] === 'ABS') {
                $absent++;
                continue;
            }

            if ($row['status'] === 'INC') {
                $incomplete++;
                continue;
            }

            $clean++;
            if (isset($divisionCounts[$row['division']])) {
                $divisionCounts[$row['division']]++;
            }

            if ($row['gpa'] !== null) {
                $centreGpaTotal += $row['gpa'];
                $centreGpaCount++;
            }
        }

        $registered = $candidateRows->count();
        $sat = $registered - $absent;
        $passed = $divisionCounts['I'] + $divisionCounts['II'] + $divisionCounts['III'] + $divisionCounts['IV'];
        $failed = $divisionCounts['0'];
        $centreGpa = $centreGpaCount > 0 ? round($centreGpaTotal / $centreGpaCount, 4) : null;
        $centreGpaInfo = $centreGpa !== null ? get_gpa_info($centreGpa) : ['text' => 'N/A', 'color' => '#CCCCCC'];

        $subjectPerformance = $subjects->map(function (array $subject) use ($schoolResults) {
            $allMarks = $schoolResults
                ->flatMap(function (CandidateResult $result) use ($subject) {
                    return ($result->subjectMarks ?? collect())
                        ->filter(fn ($mark) => (int) $mark->subject_id === (int) $subject['id']);
                })
                ->values();

            $gradeCounts = [
                'A' => 0,
                'B' => 0,
                'C' => 0,
                'D' => 0,
                'E' => 0,
                'S' => 0,
                'F' => 0,
            ];
            $absCount = 0;
            $gradePointSum = 0;
            $gradedCount = 0;

            foreach ($allMarks as $mark) {
                $grade = strtoupper((string) ($mark->grade_from_average ?? $mark->grade ?? ''));
                $hasScore = $mark->marks_obtained !== null || $grade !== '';

                if (!$hasScore) {
                    $absCount++;
                    continue;
                }

                if (isset($gradeCounts[$grade])) {
                    $gradeCounts[$grade]++;
                    $gradePointSum += $this->gradeToPoints($grade);
                    $gradedCount++;
                }
            }

            $gpa = $gradedCount > 0 ? round($gradePointSum / $gradedCount, 4) : 0.0;

            return [
                'code' => $subject['code'],
                'name' => $subject['name'],
                'gradeA' => $gradeCounts['A'],
                'gradeB' => $gradeCounts['B'],
                'gradeC' => $gradeCounts['C'],
                'gradeD' => $gradeCounts['D'],
                'gradeE' => $gradeCounts['E'],
                'gradeS' => $gradeCounts['S'],
                'gradeF' => $gradeCounts['F'],
                'absent' => $absCount,
                'total' => $allMarks->count(),
                'gpa' => $gpa,
                'competency' => $this->getCompetencyLevel($gpa),
                'gpa_info' => get_gpa_info($gpa),
            ];
        })->values();

        return [
            'school' => $school,
            'subjects' => $subjects,
            'candidate_rows' => $candidateRows,
            'overall_performance' => [
                'region' => $school?->region?->name ?? '-',
                'district' => $school?->district?->name ?? '-',
                'registered' => $registered,
                'passed' => $passed,
                'failed' => $failed,
                'gpa' => $centreGpa,
                'gpa_info' => $centreGpaInfo,
            ],
            'division_performance' => [
                'registered' => $registered,
                'absent' => $absent,
                'sat' => $sat,
                'withheld' => 0,
                'inc' => $incomplete,
                'clean' => $clean,
                'divisions' => $divisionCounts,
            ],
            'subject_performance' => $subjectPerformance,
        ];
    }

    private function normalizeResultStatus(CandidateResult $result): string
    {
        $status = strtoupper(trim((string) $result->result_status));

        if (in_array($status, ['ABS', 'INC', 'COMPLETE'], true)) {
            return $status;
        }

        $marks = $result->subjectMarks ?? collect();
        $recordedCount = $marks->filter(function ($mark) {
            return $mark->marks_obtained !== null
                || filled($mark->grade_from_average)
                || filled($mark->grade);
        })->count();

        if ($recordedCount === 0) {
            return 'ABS';
        }

        if ($recordedCount < max(1, $marks->count())) {
            return 'INC';
        }

        return 'COMPLETE';
    }

    private function normalizeDivisionLabel(mixed $division): string
    {
        return match (strtoupper(trim((string) $division))) {
            '1', 'I' => 'I',
            '2', 'II' => 'II',
            '3', 'III' => 'III',
            '4', 'IV' => 'IV',
            default => '0',
        };
    }

    private function calculateCandidateGpa(CandidateResult $result): ?float
    {
        $gradePoints = 0;
        $validCount = 0;

        foreach (($result->subjectMarks ?? collect()) as $mark) {
            $subjectName = strtoupper((string) ($mark->subject?->name ?? ''));
            if ($subjectName !== '' && is_excluded_subject($subjectName)) {
                continue;
            }

            $grade = strtoupper((string) ($mark->grade_from_average ?? $mark->grade ?? ''));
            $hasScore = $mark->marks_obtained !== null || $grade !== '';

            if (!$hasScore || $grade === '') {
                continue;
            }

            $gradePoints += $this->gradeToPoints($grade);
            $validCount++;
        }

        if ($validCount === 0) {
            return null;
        }

        return round($gradePoints / $validCount, 4);
    }

    private function gradeToPoints(string $grade): int
    {
        return match (strtoupper($grade)) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'S' => 6,
            default => 7,
        };
    }

    private function getCompetencyLevel(float $avgPoints): string
    {
        $rounded = round($avgPoints);

        if ($rounded <= 1) {
            return 'Grade A (Excellent)';
        }
        if ($rounded <= 2) {
            return 'Grade B (Very Good)';
        }
        if ($rounded <= 3) {
            return 'Grade C (Good)';
        }
        if ($rounded <= 4) {
            return 'Grade D (Average)';
        }
        if ($rounded <= 5) {
            return 'Grade E (Satisfactory)';
        }
        if ($rounded <= 6) {
            return 'Grade S (Unsatisfactory)';
        }

        return 'Grade F (Fail)';
    }
}
