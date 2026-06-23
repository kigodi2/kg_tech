<?php

namespace App\Console\Commands;

use App\Services\Results\TasidoResultProcessingService;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TasidoResultsReprocess extends Command
{
    protected $signature = 'tasido:results-reprocess {exam_year} {exam_type} {--force}';

    protected $description = 'Reprocess results for TASIDO registered schools';

    public function handle(TasidoResultProcessingService $processingService): int
    {
        $yearLabel = (int) $this->argument('exam_year');
        $typeCode = strtoupper($this->argument('exam_type'));
        $force = (bool) $this->option('force');

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

        if (!$force && !$this->confirm("Are you sure you want to reprocess all results for {$typeCode} {$yearLabel}?")) {
            $this->info("Reprocessing cancelled.");
            return 0;
        }

        $this->info("Reprocessing results for {$typeCode} {$yearLabel}...");

        $processed = DB::transaction(function() use ($examYear, $examType, $processingService) {
            // Deactivate active snapshots
            DB::table('result_snapshots')
                ->where('exam_year_id', $examYear->id)
                ->where('exam_type', $examType->code)
                ->update(['is_active' => false]);

            $latestVersion = DB::table('result_snapshots')
                ->where('exam_year_id', $examYear->id)
                ->where('exam_type', $examType->code)
                ->max('version') ?? 0;
            $newVersion = $latestVersion + 1;

            // Create process log
            $processId = DB::table('result_processes')->insertGetId([
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'user_id' => 1, // system user / console
                'type' => 'final',
                'status' => 'in_progress',
                'total_candidates' => 0,
                'processed_count' => 0,
                'error_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Snapshot record
            $snapshotId = DB::table('result_snapshots')->insertGetId([
                'exam_type' => $examType->code,
                'exam_year_id' => $examYear->id,
                'process_id' => $processId,
                'version' => $newVersion,
                'is_active' => true,
                'is_rolled_back' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Process
            $count = $processingService->processResults($examYear, $examType, $snapshotId, $processId);

            DB::table('result_processes')->where('id', $processId)->update([
                'status' => 'completed',
                'total_candidates' => $count,
                'processed_count' => $count,
                'processed_at' => now(),
                'completed_at' => now(),
            ]);

            return $count;
        });

        $this->info("Reprocessing completed! Processed {$processed} candidates.");

        // Dispatch background evaluations cache precalculation job
        $this->info("Dispatching precalculation job for evaluations cache...");
        \App\Jobs\PrecalculatePsleEvaluationsJob::dispatch($yearLabel, 'all', null, null, true);

        return 0;
    }
}
