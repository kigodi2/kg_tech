<?php

namespace App\Console\Commands;

use App\Models\MarkImportBatch;
use App\Services\MarkEntry\MarkPromotionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class BackfillLockedPromotions extends Command
{
    protected $signature = 'mark-entry:backfill-locked-promotions
        {--exam-year= : Limit to exam year label (e.g. 2026)}
        {--batch-id=* : Process only specific batch IDs}
        {--all-locked : Process all locked batches (not only those with missing candidate links)}
        {--limit=1000 : Max number of batches to process}
        {--chunk=50 : Number of batches per chunk}
        {--retries=2 : Retries per batch when promotion throws}
        {--sleep-ms=200 : Sleep between retries (milliseconds)}
        {--dry-run : Show what would be processed without promoting}';

    protected $description = 'Backfill promotion of locked mark-entry batches into subject_marks for results processing readiness';

    public function handle(MarkPromotionService $promotionService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $chunk = max(1, min(500, (int) $this->option('chunk')));
        $retries = max(0, (int) $this->option('retries'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $dryRun = (bool) $this->option('dry-run');

        $query = MarkImportBatch::query()
            ->where('status', MarkImportBatch::STATUS_LOCKED)
            ->orderBy('id');

        $this->applyFilters($query);

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('No matching locked batches found.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} locked batches matching filters.");
        if ($dryRun) {
            $this->line('Dry-run enabled. No promotion will be executed.');
        }

        $processed = 0;
        $attempted = 0;
        $batchFailures = 0;
        $promotedRows = 0;
        $skippedRows = 0;
        $failedRows = 0;

        $query->limit($limit)
            ->chunkById($chunk, function ($batches) use (
                $promotionService,
                $dryRun,
                $retries,
                $sleepMs,
                &$processed,
                &$attempted,
                &$batchFailures,
                &$promotedRows,
                &$skippedRows,
                &$failedRows
            ) {
                foreach ($batches as $batch) {
                    $processed++;
                    $attempted++;

                    if ($dryRun) {
                        $this->line("DRY-RUN batch #{$batch->id} {$batch->batch_code}");
                        continue;
                    }

                    [$ok, $result] = $this->promoteWithRetries($promotionService, $batch, $retries, $sleepMs);
                    if (!$ok) {
                        $batchFailures++;
                        continue;
                    }

                    $promotedRows += (int) ($result['promoted'] ?? 0);
                    $skippedRows += (int) ($result['skipped'] ?? 0);
                    $failedRows += (int) ($result['failed'] ?? 0);

                    $this->line(
                        "batch #{$batch->id} promoted={$result['promoted']} skipped={$result['skipped']} failed={$result['failed']}"
                    );
                }
            });

        $this->newLine();
        $this->info('Backfill completed.');
        $this->line("Batches processed: {$processed}");
        $this->line("Batch failures: {$batchFailures}");
        if (!$dryRun) {
            $this->line("Rows promoted: {$promotedRows}");
            $this->line("Rows skipped: {$skippedRows}");
            $this->line("Rows failed: {$failedRows}");
        }

        return $batchFailures > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function applyFilters(Builder $query): void
    {
        $batchIds = array_filter((array) $this->option('batch-id'), fn ($v) => is_numeric($v));
        if (!empty($batchIds)) {
            $query->whereIn('id', array_map('intval', $batchIds));
        }

        $examYear = trim((string) $this->option('exam-year'));
        if ($examYear !== '') {
            $query->where('exam_year', (int) $examYear);
        }

        if (!$this->option('all-locked')) {
            $query->whereHas('rawMarks', function ($r) {
                $r->where('has_errors', false)
                    ->where(function ($q) {
                        $q->whereNull('candidate_id')
                          ->orWhereNull('processed_at');
                    });
            });
        }
    }

    private function promoteWithRetries(
        MarkPromotionService $promotionService,
        MarkImportBatch $batch,
        int $retries,
        int $sleepMs
    ): array {
        $attempt = 0;
        $maxAttempts = $retries + 1;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $result = $promotionService->promote($batch);
                return [true, $result];
            } catch (Throwable $e) {
                $this->warn("batch #{$batch->id} attempt {$attempt}/{$maxAttempts} failed: {$e->getMessage()}");
                if ($attempt >= $maxAttempts) {
                    return [false, null];
                }
                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        }

        return [false, null];
    }
}

