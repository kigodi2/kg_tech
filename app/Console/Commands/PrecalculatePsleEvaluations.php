<?php

namespace App\Console\Commands;

use App\Services\Results\PslePrecalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrecalculatePsleEvaluations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psle:precalculate-evaluations
                            {year? : The exam year (e.g. 2026)}
                            {--force : Force rebuilding of ready caches}
                            {--scope= : The scope: zonal or regional}
                            {--region= : The region ID (if scope is regional)}
                            {--evaluation= : The evaluation key (e.g. schoolwise)}
                            {--sync : Run synchronously instead of queueing jobs}
                            {--chunk=500 : Chunk size for loading candidate rows}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Precalculate and cache PSLE evaluations payload for instant loading.';

    /**
     * Execute the console command.
     */
    public function handle(PslePrecalculationService $service): int
    {
        $year = $this->argument('year');
        if (!$year) {
            $activeYear = \App\Models\ExamYear::query()->where('is_active', true)->first();
            $year = $activeYear ? (int) $activeYear->year_label : (int) date('Y');
        } else {
            $year = (int) $year;
        }

        $snapshotId = $service->getActiveSnapshotId($year);
        if (!$snapshotId) {
            $this->error("Cannot precalculate PSLE evaluations for year {$year}: No active published snapshot found.");
            return 1;
        }

        $force = (bool) $this->option('force');
        $scopeOpt = $this->option('scope') ?: 'all';
        $regionOpt = $this->option('region') ? (int) $this->option('region') : null;
        $evaluationOpt = $this->option('evaluation');
        $sync = (bool) $this->option('sync');
        $chunk = $this->option('chunk') ? (int) $this->option('chunk') : 500;

        if ($scopeOpt && !in_array($scopeOpt, ['all', 'zonal', 'regional'])) {
            $this->error("Invalid scope option '{$scopeOpt}'. Must be: zonal or regional.");
            return 1;
        }

        // Restrict sync to targeted debugging only
        if ($sync && $scopeOpt === 'all' && !$regionOpt && !$evaluationOpt) {
            $this->error("For safety, --sync is restricted to targeted debugging. Please specify --scope, --region, or --evaluation to run synchronously.");
            return 1;
        }

        // Resolve evaluation lists
        $controller = $this->laravel->make(\App\Http\Controllers\PsleEvaluationsController::class);
        $zonalKeys = $controller->zonalEvaluationEntries()->pluck('key')->all();
        $regionalKeys = $controller->regionalEvaluationEntries()->pluck('key')->all();

        $units = [];

        // Zonal units
        if ($scopeOpt === 'all' || $scopeOpt === 'zonal') {
            $keys = $evaluationOpt ? [$evaluationOpt] : $zonalKeys;
            $keys = array_intersect($keys, $zonalKeys);
            foreach ($keys as $key) {
                $units[] = [
                    'scope_type' => 'zonal',
                    'scope_id' => null,
                    'evaluation_key' => $key,
                ];
            }
        }

        // Regional units
        if ($scopeOpt === 'all' || $scopeOpt === 'regional') {
            $regionsQuery = \App\Models\Region::query()->where('name', 'NOT LIKE', '%UNASSIGNED%');
            if ($regionOpt) {
                $regionsQuery->where('id', $regionOpt);
            } else {
                $regionsQuery->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA']);
            }

            $regions = $regionsQuery->get();
            $keys = $evaluationOpt ? [$evaluationOpt] : $regionalKeys;
            $keys = array_intersect($keys, $regionalKeys);

            foreach ($regions as $reg) {
                foreach ($keys as $key) {
                    $units[] = [
                        'scope_type' => 'regional',
                        'scope_id' => $reg->id,
                        'evaluation_key' => $key,
                    ];
                }
            }
        }

        $this->info("Found " . count($units) . " report units to process.");

        $dispatchedCount = 0;

        foreach ($units as $unit) {
            $existing = \App\Models\PslePrecalculatedEvaluation::where('exam_year', $year)
                ->where('exam_type', 'PSLE')
                ->where('scope_type', $unit['scope_type'])
                ->where('scope_id', $unit['scope_id'])
                ->where('evaluation_key', $unit['evaluation_key'])
                ->where('snapshot_id', $snapshotId)
                ->first();

            if (!$force && $existing && $existing->status === \App\Models\PslePrecalculatedEvaluation::STATUS_READY) {
                // Already calculated, skip
                continue;
            }

            // Mark as pending
            \App\Models\PslePrecalculatedEvaluation::updateOrCreate([
                'exam_year' => $year,
                'exam_type' => 'PSLE',
                'scope_type' => $unit['scope_type'],
                'scope_id' => $unit['scope_id'],
                'evaluation_key' => $unit['evaluation_key'],
                'snapshot_id' => $snapshotId,
            ], [
                'status' => \App\Models\PslePrecalculatedEvaluation::STATUS_PENDING,
                'error_message' => null,
            ]);

            if ($sync) {
                $this->info("Calculating synchronously: [{$unit['scope_type']}/" . ($unit['scope_id'] ?? 'null') . "/{$unit['evaluation_key']}]...");
                $service->precalculateSingle($year, $unit['scope_type'], $unit['scope_id'], $unit['evaluation_key'], $force, $chunk);
            } else {
                \App\Jobs\PrecalculateSinglePsleEvaluationJob::dispatch(
                    $year,
                    $unit['scope_type'],
                    $unit['scope_id'],
                    $unit['evaluation_key'],
                    $chunk,
                    $force
                );
                $dispatchedCount++;
            }
        }

        if ($sync) {
            $this->info("Synchronous precalculation completed.");
        } else {
            $this->info("Dispatched {$dispatchedCount} PrecalculateSinglePsleEvaluationJob jobs to the queue.");
        }

        return 0;
    }
}
