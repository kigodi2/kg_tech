<?php

namespace App\Console\Commands;

use App\Models\MarkEntryLifecycleState;
use App\Models\MarkImportBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillModerationLifecycle extends Command
{
    protected $signature = 'moderation:backfill-lifecycle {--dry-run : Count affected batches without modifying data}';

    protected $description = 'Backfill lifecycle_state to awaiting_moderation for validated/submitted batches still in draft';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $query = MarkImportBatch::where(function ($q) {
                $q->whereNull('lifecycle_state')
                  ->orWhere('lifecycle_state', 'draft');
            })
            ->whereIn('status', ['validated', 'submitted']);

        $total = $query->count();

        if ($total === 0) {
            $this->info('No batches require backfill.');
            return 0;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] {$total} batch(es) would be updated to awaiting_moderation.");
            return 0;
        }

        $this->info("Backfilling {$total} batch(es)...");

        $updated = 0;

        $query->chunkById(100, function ($batches) use (&$updated) {
            DB::transaction(function () use ($batches, &$updated) {
                foreach ($batches as $batch) {
                    $previousState = $batch->lifecycle_state ?? 'draft';

                    $batch->update([
                        'lifecycle_state' => 'awaiting_moderation',
                    ]);

                    MarkEntryLifecycleState::create([
                        'mark_import_batch_id' => $batch->id,
                        'current_state'        => 'awaiting_moderation',
                        'previous_state'       => $previousState,
                        'transitioned_by'      => null,
                        'transitioned_at'      => now(),
                        'transition_reason'    => 'Backfill: auto-transition validated batches to awaiting_moderation',
                    ]);

                    $updated++;
                }
            });
        });

        $this->info("Done. {$updated} batch(es) updated to awaiting_moderation.");

        return 0;
    }
}
