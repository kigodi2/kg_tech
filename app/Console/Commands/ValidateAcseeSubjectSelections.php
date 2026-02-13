<?php

namespace App\Console\Commands;

use App\Services\Validation\AcseeSubjectSelectionValidator;
use Illuminate\Console\Command;

class ValidateAcseeSubjectSelections extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'acsee:validate-selections {--exam-year=2026} {--report}';

    /**
     * The console command description.
     */
    protected $description = 'Validate that all ACSEE candidates have complete subject selections and generate reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $examYearLabel = $this->option('exam-year');
        $showReport = $this->option('report');

        $examYear = \App\Models\ExamYear::where('year_label', $examYearLabel)->first();
        if (!$examYear) {
            $this->error("Exam year {$examYearLabel} not found");
            return 1;
        }

        $validator = new AcseeSubjectSelectionValidator();

        if ($showReport) {
            $this->showDetailedReport($validator, $examYear->id);
        } else {
            $this->showQuickStatus($validator, $examYear->id);
        }

        return 0;
    }

    /**
     * Show quick status of all schools
     */
    private function showQuickStatus(AcseeSubjectSelectionValidator $validator, int $examYearId): void
    {
        $this->line("\n<info>ACSEE Subject Selection Validation for 2026</info>\n");

        $validation = $validator->validateExamYear($examYearId);

        if ($validation['valid']) {
            $this->info("✅ All {$validation['total_schools_checked']} schools have complete subject selections!");
        } else {
            $this->error("❌ {$validation['schools_with_issues']} schools have missing subject selections:");
            
            foreach ($validation['schools_with_issues'] as $issue) {
                $this->line("  - School ID {$issue['school_id']}: {$issue['registrations']} registrations, {$issue['selections']} selections (missing {$issue['missing_count']})");
            }
        }

        $this->line("\n💡 Run with --report flag for detailed information per school");
    }

    /**
     * Show detailed report
     */
    private function showDetailedReport(AcseeSubjectSelectionValidator $validator, int $examYearId): void
    {
        $this->line("\n<info>Detailed ACSEE Subject Selection Report for 2026</info>\n");

        $report = $validator->getDetailedReport($examYearId);

        // Table headers
        $this->table(
            ['School Code', 'School Name', 'Registrations', 'Selections', 'Status', 'Missing'],
            $report
        );

        // Summary
        $totalSchools = count($report);
        $okSchools = count(array_filter($report, fn($r) => $r['status'] === '✅ OK'));
        $problemSchools = $totalSchools - $okSchools;
        $totalMissing = array_sum(array_column($report, 'missing_count'));

        $this->line("\n<info>Summary:</info>");
        $this->line("  Total Schools: $totalSchools");
        $this->line("  ✅ OK: $okSchools");
        $this->line("  ❌ Problems: $problemSchools");
        $this->line("  📊 Total Missing Selections: $totalMissing");

        if ($problemSchools > 0) {
            $this->line("\n<info>To fix schools with missing selections, run:</info>");
            $this->line("  php artisan acsee:ensure-subject-selections --exam-year=2026");
        }
    }
}
