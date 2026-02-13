<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\SubjectMarks;
use App\Jobs\ProcessBulkImportFile;
use App\Services\Results\GradeCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateAllMarksAndGrades extends Command
{
    protected $signature = 'marks:recalculate-all {exam_year_id? : The exam year ID} {exam_type_code? : Exam type code (ACSEE, NECTA)}';
    protected $description = 'Recalculate marks_obtained for all subjects and grades for all candidates. This ensures multi-paper subjects are properly averaged.';

    private GradeCalculationService $gradeCalcService;

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
                    // Recalculate marks_obtained based on paper scores and subject config
                    $subject = $mark->subject;
                    
                    // Get paper marks
                    $paper1 = $mark->paper_1;
                    $paper2 = $mark->paper_2;
                    $paper3 = $mark->paper_3;
                    
                    // Calculate final mark (averaged if multi-paper)
                    $paperMarks = [];
                    if (!empty($paper1)) $paperMarks[] = (float)$paper1;
                    if (!empty($paper2)) $paperMarks[] = (float)$paper2;
                    if (!empty($paper3)) $paperMarks[] = (float)$paper3;
                    
                    if (empty($paperMarks)) {
                        continue;
                    }
                    
                    $totalPapers = ($subject->written_papers ?? 1) + 
                                  ($subject->has_practical ? 1 : 0) + 
                                  ($subject->has_project ? 1 : 0);
                    
                    // Calculate final marks
                    if ($totalPapers > 1) {
                        // Multi-paper: average
                        $finalMarks = round(array_sum($paperMarks) / count($paperMarks), 2);
                    } else {
                        // Single paper: use as-is
                        $finalMarks = $paperMarks[0] ?? null;
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
}
