<?php

namespace App\Console\Commands;

use App\Models\ExamType;
use App\Models\ExamYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillCandidateResultStatus extends Command
{
    protected $signature = 'results:backfill-result-status
        {--exam-year= : Optional numeric year label (defaults to active year)}
        {--process-id= : Limit to a specific result process}
        {--snapshot-id= : Limit to a specific snapshot}
        {--chunk=1000 : Chunk size for processing}
        {--force : Overwrite existing result_status values}
        {--dry-run : Preview updates without writing}';

    protected $description = 'Backfill candidate_results.result_status from final_grades without touching source mark-entry data.';

    public function handle(): int
    {
        if (!Schema::hasColumn('candidate_results', 'result_status')) {
            $this->error('candidate_results.result_status column does not exist.');
            return self::FAILURE;
        }

        $acsee = ExamType::query()->where('code', 'ACSEE')->first();
        if (!$acsee) {
            $this->error('ACSEE exam type not found.');
            return self::FAILURE;
        }

        $year = $this->resolveYear();
        if ($year === null) {
            return self::FAILURE;
        }

        $processId = $this->option('process-id');
        $snapshotId = $this->option('snapshot-id');
        $chunkSize = max(100, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $baseQuery = DB::table('candidate_results as cr')
            ->where('cr.exam_type_id', $acsee->id)
            ->where('cr.year', $year)
            ->when($processId !== null && $processId !== '', fn ($q) => $q->where('cr.process_id', (int) $processId))
            ->when($snapshotId !== null && $snapshotId !== '', fn ($q) => $q->where('cr.snapshot_id', (int) $snapshotId))
            ->when(!$force, fn ($q) => $q->where(function ($inner) {
                $inner->whereNull('cr.result_status')->orWhere('cr.result_status', '');
            }))
            ->orderBy('cr.id');

        $totalRows = (clone $baseQuery)->count();
        if ($totalRows === 0) {
            $this->warn('No candidate_results rows matched the selected scope.');
            return self::SUCCESS;
        }

        $this->info("Backfilling {$totalRows} candidate_results rows for ACSEE {$year} (chunk={$chunkSize})...");

        $processed = 0;
        $resolved = 0;
        $updated = 0;
        $skipped = 0;
        $statusCounts = [
            'COMPLETE' => 0,
            'INC' => 0,
            'ABS' => 0,
        ];

        $baseQuery->chunkById($chunkSize, function ($rows) use (
            &$processed,
            &$resolved,
            &$updated,
            &$skipped,
            &$statusCounts,
            $dryRun
        ) {
            foreach ($rows as $row) {
                $processed++;

                $final = $this->findBestFinalGradeForResult($row);
                if (!$final) {
                    $skipped++;
                    continue;
                }

                $status = $this->resolveResultStatus($final);
                if (!isset($statusCounts[$status])) {
                    $statusCounts[$status] = 0;
                }
                $statusCounts[$status]++;
                $resolved++;

                if ($dryRun) {
                    $updated++;
                    continue;
                }

                $affected = DB::table('candidate_results')
                    ->where('id', (int) $row->id)
                    ->update([
                        'result_status' => $status,
                        'updated_at' => now(),
                    ]);

                if ($affected > 0) {
                    $updated += $affected;
                }
            }
        }, 'cr.id', 'id');

        $this->newLine();
        $this->info('Backfill complete.');
        $this->line("Processed rows: {$processed}");
        $this->line("Resolved from final_grades: {$resolved}");
        $this->line("Skipped (no matching final_grades): {$skipped}");
        $this->line(($dryRun ? 'Would update: ' : 'Updated: ') . $updated);
        $this->line('Resolved statuses: ' . json_encode($statusCounts));

        return self::SUCCESS;
    }

    private function resolveYear(): ?int
    {
        $yearOption = $this->option('exam-year');
        if ($yearOption !== null && $yearOption !== '') {
            return (int) $yearOption;
        }

        $activeYear = ExamYear::query()->active()->first();
        if ($activeYear) {
            return (int) $activeYear->year_label;
        }

        $this->error('No active exam year found. Pass --exam-year explicitly.');
        return null;
    }

    private function findBestFinalGradeForResult(object $row): ?object
    {
        $base = DB::table('final_grades as fg')
            ->where('fg.candidate_id', (int) $row->candidate_id)
            ->where('fg.exam_type_id', (int) $row->exam_type_id)
            ->where('fg.year', (int) $row->year);

        $attempts = [
            ['snapshot_id' => $row->snapshot_id, 'process_id' => $row->process_id],
            ['snapshot_id' => $row->snapshot_id],
            ['process_id' => $row->process_id],
            [],
        ];

        foreach ($attempts as $filters) {
            $query = clone $base;

            foreach ($filters as $column => $value) {
                if ($value === null) {
                    $query->whereNull("fg.{$column}");
                } else {
                    $query->where("fg.{$column}", $value);
                }
            }

            $match = $query->orderByDesc('fg.id')->first();
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function resolveResultStatus(object $final): string
    {
        $breakdown = $this->decodeBreakdown($final->grading_breakdown ?? null);
        $irregular = strtoupper((string) ($breakdown['irregular_overall_status'] ?? ''));

        if (in_array($irregular, ['ABS', 'X'], true)) {
            return 'ABS';
        }

        if ($irregular !== '') {
            return 'INC';
        }

        $aggtPoints = $breakdown['aggt_points'] ?? null;
        $principalPasses = (int) ($breakdown['principal_passes'] ?? 0);
        $gpaSubjectsCount = (int) ($breakdown['gpa_subjects_count'] ?? 0);

        if ($aggtPoints === null && $principalPasses === 0 && $gpaSubjectsCount === 0) {
            return 'INC';
        }

        return 'COMPLETE';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBreakdown(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
