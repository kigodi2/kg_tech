<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\SubjectPaperWeight;
use App\Models\SubjectMarks;
use App\Jobs\ProcessBulkImportFile;
use App\Services\Results\GradeCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RecalculateAllMarksAndGrades extends Command
{
    protected $signature = 'marks:recalculate-all {exam_year_id? : The exam year ID} {exam_type_code? : Exam type code (ACSEE, NECTA)}';
    protected $description = 'Recalculate marks_obtained for all subjects and grades for all candidates. This ensures multi-paper subjects are properly averaged.';

    private GradeCalculationService $gradeCalcService;
    private array $paperWeightCache = [];

    public function __construct(GradeCalculationService $gradeCalcService)
    {
        parent::__construct();
        $this->gradeCalcService = $gradeCalcService;
    }

    public function handle()
    {
        $examYearId = $this->argument('exam_year_id');
        $examTypeCode = $this->argument('exam_type_code') ?? 'ACSEE';

        $examType = ExamType::where('code', $examTypeCode)->first();
        if (!$examType) {
            $this->error("Exam type '{$examTypeCode}' not found");
            return 1;
        }

        $this->info("Starting recalculation of marks and grades...");
        $this->info("Exam Type: {$examTypeCode} (ID: {$examType->id})");

        $query = ExamYear::query();
        if ($examYearId) {
            $query->where('id', $examYearId);
            $this->info("Exam Year ID: {$examYearId}");
        }

        $examYears = $query->get();

        if ($examYears->isEmpty()) {
            $this->error("No exam years found");
            return 1;
        }

        $totalCandidates = 0;
        $totalMarksRecalculated = 0;
        $totalGradesRecalculated = 0;

        foreach ($examYears as $examYear) {
            $this->info("\n--- Processing Exam Year: {$examYear->year_label} (ID: {$examYear->id}) ---");

            // Get all candidates with marks in this exam year
            $candidates = Candidate::whereHas('marks', function ($q) use ($examType, $examYear) {
                $q->where('exam_type_id', $examType->id)
                  ->where('year', $examYear->year_label ?? $examYear->year);
            })->get();

            $this->info("Found {$candidates->count()} candidates with marks");
            $totalCandidates += $candidates->count();

            foreach ($candidates as $candidate) {
                // Get all marks for this candidate in this exam year
                $marks = SubjectMarks::where('candidate_id', $candidate->id)
                    ->where('exam_type_id', $examType->id)
                    ->where('year', $examYear->year_label ?? $examYear->year)
                    ->with('subject')
                    ->get();

                foreach ($marks as $mark) {
                    // Recalculate marks_obtained using SubjectMark100 weighted normalization
                    $subject = $mark->subject;
                    
                    // Get paper marks
                    $paper1 = $mark->paper_1;
                    $paper2 = $mark->paper_2;
                    $paper3 = $mark->paper_3;

                    $finalMarks = $this->normalizeSubjectMarkTo100(
                        [
                            'paper_1' => $paper1,
                            'paper_2' => $paper2,
                            'paper_3' => $paper3,
                        ],
                        $subject
                    );

                    if ($finalMarks === null) {
                        continue;
                    }

                    // Only update if changed
                    if ($finalMarks !== null && $mark->marks_obtained != $finalMarks) {
                        $mark->update(['marks_obtained' => $finalMarks]);
                        $totalMarksRecalculated++;
                    }
                }

                // Recalculate grades for this candidate
                if ($this->gradeCalcService->calculateForCandidate(
                    $candidate->id,
                    $examYear->id,
                    $examType->id
                )) {
                    $totalGradesRecalculated++;
                }
            }
        }

        $this->info("\n=== RECALCULATION COMPLETE ===");
        $this->info("Total Candidates Processed: {$totalCandidates}");
        $this->info("Total Marks Recalculated: {$totalMarksRecalculated}");
        $this->info("Total Grades Recalculated: {$totalGradesRecalculated}");

        return 0;
    }

    private function normalizeSubjectMarkTo100(array $paperValuesRaw, ?Subject $subject): ?float
    {
        $paperValues = [];
        foreach (['paper_1', 'paper_2', 'paper_3'] as $code) {
            $v = $paperValuesRaw[$code] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            $paperValues[$code] = (float) $v;
        }

        if (empty($paperValues)) {
            return null;
        }

        if ($subject) {
            $weights = $this->paperWeightsForSubject((int) $subject->id);
            if (!empty($weights)) {
                $weightedSum = 0.0;
                $weightedMax = 0.0;
                foreach ($weights as $row) {
                    $paperCode = (string) ($row['paper_code'] ?? '');
                    if ($paperCode === '' || !array_key_exists($paperCode, $paperValues)) {
                        continue;
                    }
                    $weight = (float) ($row['weight'] ?? 1.0);
                    $maxMark = (float) ($row['max_mark'] ?? 100.0);
                    $mark = (float) $paperValues[$paperCode];
                    $weightedSum += ($mark * $weight);
                    $weightedMax += ($maxMark * $weight);
                }
                if ($weightedMax > 0) {
                    return round(($weightedSum / $weightedMax) * 100.0, 0);
                }
            }
        }

        $weightedSum = 0.0;
        $weightedMax = 0.0;
        foreach ($paperValues as $paperCode => $mark) {
            $weightedSum += (float) $mark;
            $weightedMax += $this->paperMaxMark((string) $paperCode);
        }

        if ($weightedMax <= 0) {
            return null;
        }

        return round(($weightedSum / $weightedMax) * 100.0, 0);
    }

    private function paperWeightsForSubject(int $subjectId): array
    {
        if (array_key_exists($subjectId, $this->paperWeightCache)) {
            return $this->paperWeightCache[$subjectId];
        }

        if (!Schema::hasTable('subject_paper_weights')) {
            $this->paperWeightCache[$subjectId] = [];
            return [];
        }

        $rows = SubjectPaperWeight::query()
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereIn('paper_code', ['paper_1', 'paper_2', 'paper_3'])
            ->orderBy('paper_code')
            ->get(['paper_code', 'weight', 'max_mark'])
            ->map(fn ($r) => [
                'paper_code' => (string) $r->paper_code,
                'weight' => (float) ($r->weight ?? 1.0),
                'max_mark' => $this->paperMaxMark((string) $r->paper_code, $r->max_mark),
            ])
            ->values()
            ->all();

        $this->paperWeightCache[$subjectId] = $rows;
        return $rows;
    }

    private function paperMaxMark(string $paperCode, mixed $configuredMax = null): float
    {
        $canonical = $paperCode === 'paper_3' ? 50.0 : 100.0;
        if ($configuredMax === null || $configuredMax === '') {
            return $canonical;
        }

        $value = (float) $configuredMax;
        return $value > 0 ? $value : $canonical;
    }
}
