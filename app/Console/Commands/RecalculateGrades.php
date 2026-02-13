<?php

namespace App\Console\Commands;

use App\Models\ExamYear;
use App\Models\ExamType;
use App\Services\Results\GradeCalculationService;
use Illuminate\Console\Command;

class RecalculateGrades extends Command
{
    protected $signature = 'grades:recalculate {--exam-year= : Exam Year ID} {--exam-type=ACSEE : Exam Type code}';
    protected $description = 'Recalculate grades for candidates in an exam year';

    public function handle(GradeCalculationService $gradeCalculationService)
    {
        $examYearId = $this->option('exam-year');
        $examTypeCode = $this->option('exam-type');

        if (!$examYearId) {
            // Show available exam years
            $examYears = ExamYear::all();
            $this->info('Available Exam Years:');
            foreach ($examYears as $year) {
                $yearLabel = $year->year_label ?? $year->year ?? 'N/A';
                $this->line("  ID: {$year->id}, Year: {$yearLabel}");
            }
            
            $examYearId = $this->ask('Enter Exam Year ID');
        }

        $examYear = ExamYear::find($examYearId);
        if (!$examYear) {
            $this->error("Exam year not found: {$examYearId}");
            return 1;
        }

        $examType = ExamType::where('code', $examTypeCode)->first();
        if (!$examType) {
            $this->error("Exam type not found: {$examTypeCode}");
            return 1;
        }

        $yearLabel = $examYear->year_label ?? $examYear->year ?? 'N/A';
        $this->info("Starting grade recalculation...");
        $this->info("Exam Year: {$yearLabel} (ID: {$examYear->id})");
        $this->info("Exam Type: {$examType->name} (Code: {$examType->code})");

        $bar = $this->output->createProgressBar(100);
        $bar->start();

        $results = $gradeCalculationService->calculateForExamYear($examYear->id, $examType->id);

        $bar->finish();
        $this->newLine();

        $this->info('Grade recalculation completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Candidates', $results['total']],
                ['Successful', $results['success']],
                ['Failed', $results['failed']],
            ]
        );

        return 0;
    }
}
