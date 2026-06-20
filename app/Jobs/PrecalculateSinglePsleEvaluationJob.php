<?php

namespace App\Jobs;

use App\Services\Results\PslePrecalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrecalculateSinglePsleEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $examYear;
    public string $scopeType;
    public ?int $scopeId;
    public string $evaluationKey;
    public int $chunk;
    public bool $force;

    public $timeout = 3600;
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(int $examYear, string $scopeType, ?int $scopeId, string $evaluationKey, int $chunk = 500, bool $force = false)
    {
        $this->examYear = $examYear;
        $this->scopeType = $scopeType;
        $this->scopeId = $scopeId;
        $this->evaluationKey = $evaluationKey;
        $this->chunk = $chunk;
        $this->force = $force;
    }

    /**
     * Execute the job.
     */
    public function handle(PslePrecalculationService $service): void
    {
        $service->precalculateSingle($this->examYear, $this->scopeType, $this->scopeId, $this->evaluationKey, $this->force, $this->chunk);
    }
}
