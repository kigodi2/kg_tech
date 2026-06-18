<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PsleMarkEntryPerformanceService;
use App\Models\User;
use App\Models\ExamYear;

class PsleRankingCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psle:ranking-check
                            {--exam-year=1 : Exam year ID}
                            {--region= : Region ID}
                            {--district= : District/Council ID}
                            {--school= : School ID}
                            {--subject= : Subject ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check live Mark Entry Officer rankings based on entered marks and applied filters';

    /**
     * Execute the console command.
     */
    public function handle(PsleMarkEntryPerformanceService $performanceService): int
    {
        $examYearId = $this->option('exam-year');
        if (!$examYearId) {
            $activeYear = ExamYear::where('is_active', true)->first();
            $examYearId = $activeYear ? $activeYear->id : null;
        }

        if (!$examYearId) {
            $this->error('No active exam year set and --exam-year option was not provided.');
            return 1;
        }

        $filters = [
            'exam_year_id' => $examYearId,
            'region_id' => $this->option('region'),
            'district_id' => $this->option('district'),
            'school_id' => $this->option('school'),
            'subject_id' => $this->option('subject'),
        ];

        // Create a mock admin user to run the service query without region scopes
        $admin = new User([
            'is_admin' => true,
            'portal_role' => 'admin',
        ]);

        $this->info("Fetching rankings for Exam Year ID: {$examYearId}...");
        $rankings = $performanceService->getRankings($filters, $admin);

        if (empty($rankings)) {
            $this->warn('No performance data found in this scope.');
            return 0;
        }

        $headers = ['Rank', 'Officer', 'Region', 'Marks Entered', '%', 'Type', 'Schools', 'Subjects', 'Last Activity'];
        $rows = [];

        foreach ($rankings as $r) {
            $rows[] = [
                $r['rank'],
                $r['name'],
                $r['region_name'],
                number_format($r['marks_entered']),
                $r['completion_percentage'] . '%',
                $r['is_contribution'] ? 'Contribution' : 'Completion',
                $r['schools_touched'],
                $r['subjects_touched'],
                $r['last_activity_display'],
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }
}
