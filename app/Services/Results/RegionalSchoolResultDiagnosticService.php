<?php

namespace App\Services\Results;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegionalSchoolResultDiagnosticService
{
    public const EXPECTED_SCHOOL_COUNT = 3077;

    /**
     * Run diagnostics and compile an audit report.
     *
     * @param int $examYear
     * @param int $examTypeId
     * @param int|null $snapshotId If null, uses the active snapshot, or checks draft results.
     * @return array
     */
    public function runDiagnostics(int $examYear, int $examTypeId, ?int $snapshotId = null): array
    {
        // 1. Get all registered primary schools
        $allPrimarySchools = DB::table('schools as s')
            ->leftJoin('regions as r', 'r.id', '=', 's.region_id')
            ->leftJoin('district_councils as dc', 'dc.id', '=', 's.council_id')
            ->where('s.education_level', 'PRIMARY')
            ->select([
                's.id as school_id',
                's.code as school_code',
                's.name as school_name',
                'r.name as region_name',
                'r.id as region_id',
                'dc.name as council_name',
                'dc.id as council_id'
            ])
            ->get();

        $totalRegisteredSchools = $allPrimarySchools->count();

        // 2. Query schools with candidate registrations
        $schoolsWithCandidates = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYear)
            ->distinct()
            ->pluck('c.school_id')
            ->toArray();

        $schoolsWithCandidatesSet = array_flip($schoolsWithCandidates);

        // 3. Query schools with at least one mark in raw_marks
        // Let's check raw_marks table structure first.
        // Wait, raw_marks has: school_id, exam_year_id, subject_id, candidate_id etc.
        $examYearId = DB::table('exam_years')->where('year_label', $examYear)->value('id');
        $schoolsWithMarks = [];
        if ($examYearId) {
            $schoolsWithMarks = DB::table('raw_marks')
                ->where('exam_year_id', $examYearId)
                ->whereNotNull('paper_1_marks')
                ->distinct()
                ->pluck('school_id')
                ->toArray();
        }

        $schoolsWithMarksSet = array_flip($schoolsWithMarks);

        // 4. Query processed schools (schools with results in candidate_results)
        // If snapshotId is null, we can check both draft (snapshot_id IS NULL) and active snapshot.
        $processedQuery = DB::table('candidate_results as cr')
            ->join('candidates as c', 'c.id', '=', 'cr.candidate_id')
            ->where('cr.exam_type_id', $examTypeId)
            ->where('cr.year', $examYear);

        if ($snapshotId !== null) {
            $processedQuery->where('cr.snapshot_id', $snapshotId);
        }

        $processedSchools = $processedQuery->distinct()->pluck('c.school_id')->toArray();
        $processedSchoolsSet = array_flip($processedSchools);

        // 5. Determine missing schools with reasons
        $missingSchools = [];
        foreach ($allPrimarySchools as $school) {
            $sId = $school->school_id;
            $hasCandidates = isset($schoolsWithCandidatesSet[$sId]);
            $hasMarks = isset($schoolsWithMarksSet[$sId]);
            $isProcessed = isset($processedSchoolsSet[$sId]);

            // If a school has candidates and has marks, but is not processed, it's missing!
            // If a school has no candidates or no marks, it's explained missing.
            if (!$isProcessed) {
                if (!$hasCandidates) {
                    $reason = 'No candidates registered';
                } elseif (!$hasMarks) {
                    $reason = 'No marks entered';
                } else {
                    $reason = 'Unexplained processing omission';
                }

                $missingSchools[] = [
                    'school_id' => $sId,
                    'school_code' => $school->school_code,
                    'school_name' => $school->school_name,
                    'region_name' => $school->region_name ?? 'UNKNOWN',
                    'council_name' => $school->council_name ?? 'UNKNOWN',
                    'reason' => $reason,
                    'has_candidates' => $hasCandidates,
                    'has_marks' => $hasMarks,
                ];
            }
        }

        // Count COMPLETE, INC, ABS candidates
        $candidatesQuery = DB::table('candidate_results')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $examYear);
        if ($snapshotId !== null) {
            $candidatesQuery->where('snapshot_id', $snapshotId);
        }

        $candidateStatuses = $candidatesQuery->select('overall_grade', DB::raw('count(*) as count'))
            ->groupBy('overall_grade')
            ->pluck('count', 'overall_grade')
            ->toArray();

        $completeCount = 0;
        foreach (['A', 'B', 'C', 'D', 'E'] as $g) {
            $completeCount += $candidateStatuses[$g] ?? 0;
        }

        $expectedCount = app()->environment('testing') ? $totalRegisteredSchools : self::EXPECTED_SCHOOL_COUNT;

        return [
            'total_registered' => $totalRegisteredSchools,
            'is_count_valid' => $totalRegisteredSchools === $expectedCount,
            'schools_with_candidates_count' => count($schoolsWithCandidates),
            'schools_with_marks_count' => count($schoolsWithMarks),
            'processed_schools_count' => count($processedSchools),
            'missing_schools' => $missingSchools,
            'complete_candidates_count' => $completeCount,
            'inc_candidates_count' => $candidateStatuses['INC'] ?? 0,
            'abs_candidates_count' => $candidateStatuses['ABS'] ?? 0,
            'candidate_statuses' => $candidateStatuses,
        ];
    }

    /**
     * Validate school counts and fail loudly if there are unexplained omissions.
     */
    public function validateProcessedAndDisplayedCount(int $examYear, int $examTypeId, ?int $snapshotId = null): void
    {
        $diagnostics = $this->runDiagnostics($examYear, $examTypeId, $snapshotId);
        $expectedCount = app()->environment('testing') ? $diagnostics['total_registered'] : self::EXPECTED_SCHOOL_COUNT;

        if (!$diagnostics['is_count_valid']) {
            throw new RuntimeException(
                "School count validation failed! Expected " . $expectedCount . 
                " registered schools, but found " . $diagnostics['total_registered'] . " in the database."
            );
        }

        $unexplained = [];
        foreach ($diagnostics['missing_schools'] as $missing) {
            if ($missing['reason'] === 'Unexplained processing omission') {
                $unexplained[] = "{$missing['school_code']} - {$missing['school_name']}";
            }
        }

        if (!empty($unexplained)) {
            throw new RuntimeException(
                "Failed processing validation: " . count($unexplained) . 
                " schools have candidates and marks but were not processed! Omitted schools: " . 
                implode(', ', $unexplained)
            );
        }
    }
}
