<?php

namespace App\Jobs;

use App\Services\Results\PslePrecalculationService;
use App\Http\Controllers\PsleEvaluationsController;
use App\Models\PslePrecalculatedEvaluation;
use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PrecalculatePsleEvaluationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $examYear;
    public string $scopeType;
    public ?int $scopeId;
    public ?string $evaluationKey;
    public bool $force;
    public int $chunk;

    /**
     * Create a new job instance.
     */
    public function __construct(int $examYear, string $scopeType = 'all', ?int $scopeId = null, ?string $evaluationKey = null, bool $force = false, int $chunk = 500)
    {
        $this->examYear = $examYear;
        $this->scopeType = $scopeType;
        $this->scopeId = $scopeId;
        $this->evaluationKey = $evaluationKey;
        $this->force = $force;
        $this->chunk = $chunk;
    }

    /**
     * Execute the job.
     */
    public function handle(PslePrecalculationService $service, PsleEvaluationsController $controller): void
    {
        $snapshotId = $service->getActiveSnapshotId($this->examYear);
        if (!$snapshotId) {
            return;
        }

        $zonalKeys = $controller->zonalEvaluationEntries()->pluck('key')->all();
        $regionalKeys = $controller->regionalEvaluationEntries()->pluck('key')->all();

        $units = [];

        // Zonal units
        if ($this->scopeType === 'all' || $this->scopeType === 'zonal') {
            $keys = $this->evaluationKey ? [$this->evaluationKey] : $zonalKeys;
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
        if ($this->scopeType === 'all' || $this->scopeType === 'regional') {
            $regionsQuery = Region::query()->where('name', 'NOT LIKE', '%UNASSIGNED%');
            if ($this->scopeId) {
                $regionsQuery->where('id', $this->scopeId);
            } else {
                $regionsQuery->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA']);
            }

            $regions = $regionsQuery->get();
            $keys = $this->evaluationKey ? [$this->evaluationKey] : $regionalKeys;
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

        foreach ($units as $unit) {
            $existing = PslePrecalculatedEvaluation::where('exam_year', $this->examYear)
                ->where('exam_type', 'PSLE')
                ->where('scope_type', $unit['scope_type'])
                ->where('scope_id', $unit['scope_id'])
                ->where('evaluation_key', $unit['evaluation_key'])
                ->where('snapshot_id', $snapshotId)
                ->first();

            if (!$this->force && $existing && $existing->status === PslePrecalculatedEvaluation::STATUS_READY) {
                continue;
            }

            // Mark as pending
            PslePrecalculatedEvaluation::updateOrCreate([
                'exam_year' => $this->examYear,
                'exam_type' => 'PSLE',
                'scope_type' => $unit['scope_type'],
                'scope_id' => $unit['scope_id'],
                'evaluation_key' => $unit['evaluation_key'],
                'snapshot_id' => $snapshotId,
            ], [
                'status' => PslePrecalculatedEvaluation::STATUS_PENDING,
                'error_message' => null,
            ]);

            // Dispatch single precalculation job
            PrecalculateSinglePsleEvaluationJob::dispatch(
                $this->examYear,
                $unit['scope_type'],
                $unit['scope_id'],
                $unit['evaluation_key'],
                $this->chunk,
                $this->force
            );
        }
    }
}
