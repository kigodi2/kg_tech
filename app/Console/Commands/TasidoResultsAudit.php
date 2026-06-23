<?php

namespace App\Console\Commands;

use App\Services\Results\RegionalSchoolResultDiagnosticService;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TasidoResultsAudit extends Command
{
    protected $signature = 'tasido:results-audit {exam_year} {exam_type}';

    protected $description = 'Perform result processing audit for TASIDO registered schools';

    public function handle(RegionalSchoolResultDiagnosticService $diagnosticService): int
    {
        $yearLabel = (int) $this->argument('exam_year');
        $typeCode = strtoupper($this->argument('exam_type'));

        $examYear = ExamYear::where('year_label', $yearLabel)->first();
        if (!$examYear) {
            $this->error("Exam year {$yearLabel} not found.");
            return 1;
        }

        $examType = ExamType::where('code', $typeCode)->first();
        if (!$examType) {
            $this->error("Exam type {$typeCode} not found.");
            return 1;
        }

        $this->info("Running results audit for {$typeCode} {$yearLabel}...");

        $snapshot = DB::table('result_snapshots')
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type', $typeCode)
            ->where('is_active', true)
            ->first();

        $snapshotId = $snapshot ? $snapshot->id : null;

        $diagnostics = $diagnosticService->runDiagnostics($yearLabel, $examType->id, $snapshotId);

        $this->line("");
        $this->info("=== SCHOOL METRICS ===");
        $this->line("TASIDO Registered Primary Schools: " . $diagnostics['total_registered']);
        if ($diagnostics['is_count_valid']) {
            $this->info("Expected count validation: SUCCESS (matches database registered count: " . $diagnostics['total_registered'] . ")");
        } else {
            $this->error("Expected count validation: FAILED (Expected 3077 or 3087, got " . $diagnostics['total_registered'] . ")");
        }
        $this->line("Schools with registered candidates: " . $diagnostics['schools_with_candidates_count']);
        $this->line("Schools with at least one mark: " . $diagnostics['schools_with_marks_count']);
        $this->line("Processed schools: " . $diagnostics['processed_schools_count']);

        // In active snapshot, displayed schools are all primary schools in the region because the portal lists all of them.
        // Schools with results or without results.
        $this->line("Displayed regionalwise schools: " . $diagnostics['total_registered']);

        $this->line("");
        $this->info("=== CANDIDATE METRICS ===");
        $this->line("COMPLETE Candidates: " . $diagnostics['complete_candidates_count']);
        $this->line("INC Candidates: " . $diagnostics['inc_candidates_count']);
        $this->line("ABS Candidates: " . $diagnostics['abs_candidates_count']);

        $this->line("");
        if (count($diagnostics['missing_schools']) > 0) {
            $this->warn("=== MISSING/UNPROCESSED SCHOOLS (" . count($diagnostics['missing_schools']) . ") ===");
            $headers = ['Region', 'Council', 'School Code', 'School Name', 'Reason'];
            $rows = [];
            foreach ($diagnostics['missing_schools'] as $school) {
                $rows[] = [
                    $school['region_name'],
                    $school['council_name'],
                    $school['school_code'],
                    $school['school_name'],
                    $school['reason']
                ];
            }
            // Sort by region, council, code
            usort($rows, function ($a, $b) {
                $regComp = strcmp($a[0], $b[0]);
                if ($regComp !== 0) return $regComp;
                $councComp = strcmp($a[1], $b[1]);
                if ($councComp !== 0) return $councComp;
                return strcmp($a[2], $b[2]);
            });
            $this->table($headers, $rows);
        } else {
            $this->info("No missing/unprocessed schools found.");
        }

        return 0;
    }
}
