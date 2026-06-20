<?php

namespace App\Services\Results;

use App\Http\Controllers\PsleEvaluationsController;
use App\Models\PslePrecalculatedEvaluation;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PslePrecalculationService
{
    protected PsleEvaluationsController $controller;

    public function __construct(PsleEvaluationsController $controller)
    {
        $this->controller = $controller;
    }

    public function getActiveSnapshotId(int $examYear): ?int
    {
        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYear) {
                $query->select('id')->from('exam_years')->where('year_label', $examYear)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->select('rs.id')
            ->first();

        if ($publication) {
            return (int) $publication->id;
        }

        if (app()->runningUnitTests()) {
            return 999999;
        }

        return null;
    }

    public function getReadyPayloadOrNull(int $examYear, string $scopeType, ?int $scopeId, string $evaluationKey): ?array
    {
        $snapshotId = $this->getActiveSnapshotId($examYear);
        if (!$snapshotId) {
            return null;
        }

        $cache = PslePrecalculatedEvaluation::where('exam_year', $examYear)
            ->where('exam_type', 'PSLE')
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('evaluation_key', $evaluationKey)
            ->where('snapshot_id', $snapshotId)
            ->where('status', PslePrecalculatedEvaluation::STATUS_READY)
            ->first();

        return $cache ? $cache->data : null;
    }

    public function getEvaluationStatus(int $examYear, string $scopeType, ?int $scopeId, string $evaluationKey): string
    {
        $snapshotId = $this->getActiveSnapshotId($examYear);
        if (!$snapshotId) {
            return PslePrecalculatedEvaluation::STATUS_PENDING;
        }

        $cache = PslePrecalculatedEvaluation::where('exam_year', $examYear)
            ->where('exam_type', 'PSLE')
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('evaluation_key', $evaluationKey)
            ->where('snapshot_id', $snapshotId)
            ->first();

        return $cache ? $cache->status : PslePrecalculatedEvaluation::STATUS_PENDING;
    }

    public function getStatusesMap(int $examYear, string $scopeType, ?int $scopeId): Collection
    {
        $snapshotId = $this->getActiveSnapshotId($examYear);
        if (!$snapshotId) {
            return collect();
        }

        return PslePrecalculatedEvaluation::where('exam_year', $examYear)
            ->where('exam_type', 'PSLE')
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('snapshot_id', $snapshotId)
            ->pluck('status', 'evaluation_key');
    }

    public function precalculate(int $examYear, string $scopeType, ?int $scopeId = null, ?string $evaluationKey = null, bool $force = false): void
    {
        $snapshotId = $this->getActiveSnapshotId($examYear);
        if (!$snapshotId) {
            Log::warning("Cannot precalculate PSLE evaluations for year {$examYear}: No active published snapshot found.");
            return;
        }

        // Determine which keys to process
        $zonalKeys = $this->controller->zonalEvaluationEntries()->pluck('key')->all();
        $regionalKeys = $this->controller->regionalEvaluationEntries()->pluck('key')->all();

        $scopes = [];
        if ($scopeType === 'zonal') {
            $scopes[] = ['type' => 'zonal', 'id' => null, 'keys' => $zonalKeys];
        } elseif ($scopeType === 'regional') {
            $regionsQuery = Region::query()->where('name', 'NOT LIKE', '%UNASSIGNED%');
            if ($scopeId) {
                $regionsQuery->where('id', $scopeId);
            } else {
                $regionsQuery->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA']);
            }
            foreach ($regionsQuery->get() as $reg) {
                $scopes[] = ['type' => 'regional', 'id' => $reg->id, 'keys' => $regionalKeys, 'model' => $reg];
            }
        } else {
            // Both
            $scopes[] = ['type' => 'zonal', 'id' => null, 'keys' => $zonalKeys];
            $regions = Region::query()
                ->whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])
                ->get();
            foreach ($regions as $reg) {
                $scopes[] = ['type' => 'regional', 'id' => $reg->id, 'keys' => $regionalKeys, 'model' => $reg];
            }
        }

        foreach ($scopes as $scope) {
            $keysToProcess = $evaluationKey ? [$evaluationKey] : $scope['keys'];
            $regionModel = $scope['type'] === 'regional' ? ($scope['model'] ?? Region::find($scope['id'])) : null;

            // Pre-load candidate rows if regional to avoid query inside loop, but only if actually needed
            $candidateRows = null;
            if ($scope['type'] === 'regional' && $regionModel) {
                $hasNonOptimizedKeys = false;
                foreach ($keysToProcess as $k) {
                    if (!in_array($k, [
                        'best-ten-girls', 'least-ten-girls', 'best-ten-boys', 'least-ten-boys',
                        'overall-best-ten-students', 'overall-least-ten-students',
                        'subjectwise-result-evaluation', 'subject-summary-evaluation',
                        'mark-entry-status-report'
                    ])) {
                        $hasNonOptimizedKeys = true;
                        break;
                    }
                }
                if ($hasNonOptimizedKeys) {
                    $candidateRows = $this->controller->regionalCandidateRows($regionModel, $examYear);
                }
            }

            foreach ($keysToProcess as $key) {
                // Check if already ready and not forced
                if (!$force) {
                    $existing = PslePrecalculatedEvaluation::where('exam_year', $examYear)
                        ->where('exam_type', 'PSLE')
                        ->where('scope_type', $scope['type'])
                        ->where('scope_id', $scope['id'])
                        ->where('evaluation_key', $key)
                        ->where('snapshot_id', $snapshotId)
                        ->first();
                    if ($existing && $existing->status === PslePrecalculatedEvaluation::STATUS_READY) {
                        continue;
                    }
                }

                // Create or update status to building
                $record = PslePrecalculatedEvaluation::updateOrCreate([
                    'exam_year' => $examYear,
                    'exam_type' => 'PSLE',
                    'scope_type' => $scope['type'],
                    'scope_id' => $scope['id'],
                    'evaluation_key' => $key,
                    'snapshot_id' => $snapshotId,
                ], [
                    'status' => PslePrecalculatedEvaluation::STATUS_BUILDING,
                    'started_at' => now(),
                    'error_message' => null,
                ]);

                try {
                    $payload = $this->calculatePayload($examYear, $scope['type'], $regionModel, $key, $candidateRows);

                    $record->update([
                        'status' => PslePrecalculatedEvaluation::STATUS_READY,
                        'data' => $payload,
                        'completed_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Failed to precalculate PSLE evaluation [{$scope['type']}/" . ($scope['id'] ?? 'null') . "/{$key}]: " . $e->getMessage(), [
                        'exception' => $e
                    ]);

                    $record->update([
                        'status' => PslePrecalculatedEvaluation::STATUS_FAILED,
                        'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                        'completed_at' => now(),
                    ]);
                }
            }
        }
    }

    protected function calculatePayload(int $examYear, string $scopeType, ?Region $region, string $key, ?Collection $candidateRows, int $chunkSize = 500): array
    {
        @ini_set('memory_limit', '2048M');

        if ($scopeType === 'zonal') {
            $evaluationMap = $this->controller->zonalEvaluationEntries()->keyBy('key');
            $label = (string) data_get($evaluationMap->get($key), 'label', 'PSLE ZONAL EVALUATION');

            switch ($key) {
                case 'general':
                case 'regionalwise':
                    [$rows, $total] = $this->controller->buildZonalRegionalwiseGroupedRows($examYear);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'regionalwise',
                        'evaluationLabel' => $label,
                    ];
                case 'councilwise':
                    [$rows, $total] = $this->controller->buildZonalCouncilwiseGroupedRows($examYear);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'zonal-councilwise',
                        'evaluationLabel' => 'ZONAL COUNCILWISE EVALUATION',
                    ];
                case 'schoolwise':
                    [$rows, $total] = $this->controller->buildZonalSchoolwiseGroupedRows($examYear);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'zonal-schoolwise',
                        'evaluationLabel' => 'ZONAL SCHOOLWISE EVALUATION',
                    ];
                case 'best-ten-councils':
                case 'least-ten-councils':
                    [$rows] = $this->controller->buildZonalCouncilwiseGroupedRows($examYear);
                    $rows = match ($key) {
                        'best-ten-councils' => $rows->take(10)->values(),
                        'least-ten-councils' => $rows->sort(function ($left, $right) {
                            $leftAvg = $left['avg_marks'] ?? INF;
                            $rightAvg = $right['avg_marks'] ?? INF;
                            if ($leftAvg !== $rightAvg) {
                                    return $leftAvg <=> $rightAvg;
                            }
                            $leftGpa = $left['gpa'] ?? INF;
                            $rightGpa = $right['gpa'] ?? INF;
                            if ($leftGpa !== $rightGpa) {
                                    return $rightGpa <=> $leftGpa;
                            }
                            $leftCouncil = strtoupper((string) ($left['council'] ?? ''));
                            $rightCouncil = strtoupper((string) ($right['council'] ?? ''));
                            if ($leftCouncil !== $rightCouncil) {
                                    return strcmp($leftCouncil, $rightCouncil);
                            }
                            return strcmp(strtoupper((string) ($left['region'] ?? '')), strtoupper((string) ($right['region'] ?? '')));
                        })->take(10)->values(),
                    };
                    $rows = $this->controller->applyPositions($rows);
                    $total = $this->controller->summariseGroupedRows($rows);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'zonal-councilwise',
                        'evaluationLabel' => $label,
                    ];
                case 'best-ten-schools':
                case 'least-ten-schools':
                    [$rows] = $this->controller->buildZonalSchoolwiseGroupedRows($examYear);
                    $rows = match ($key) {
                        'best-ten-schools' => $rows->take(10)->values(),
                        'least-ten-schools' => $rows->sort(function ($left, $right) {
                            $leftAvg = $left['avg_marks'] ?? INF;
                            $rightAvg = $right['avg_marks'] ?? INF;
                            if ($leftAvg !== $rightAvg) {
                                    return $leftAvg <=> $rightAvg;
                            }
                            $leftGpa = $left['gpa'] ?? INF;
                            $rightGpa = $right['gpa'] ?? INF;
                            if ($leftGpa !== $rightGpa) {
                                    return $rightGpa <=> $leftGpa;
                            }
                            $leftSchool = strtoupper((string) ($left['school'] ?? ''));
                            $rightSchool = strtoupper((string) ($right['school'] ?? ''));
                            if ($leftSchool !== $rightSchool) {
                                    return strcmp($leftSchool, $rightSchool);
                            }
                            $leftCouncil = strtoupper((string) ($left['council'] ?? ''));
                            $rightCouncil = strtoupper((string) ($right['council'] ?? ''));
                            if ($leftCouncil !== $rightCouncil) {
                                    return strcmp($leftCouncil, $rightCouncil);
                            }
                            return strcmp(strtoupper((string) ($left['region'] ?? '')), strtoupper((string) ($right['region'] ?? '')));
                        })->take(10)->values(),
                    };
                    $rows = $this->controller->applyPositions($rows);
                    $total = $this->controller->summariseGroupedRows($rows);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'zonal-schoolwise',
                        'evaluationLabel' => $label,
                    ];
                case 'ownership-result-evaluation':
                    [$rows, $total] = $this->controller->buildZonalOwnershipGroupedRows($examYear);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'ownership',
                        'evaluationLabel' => $label,
                    ];
                case 'subjectwise-result-evaluation':
                    $rows = $this->controller->buildZonalSubjectwiseRows($examYear);
                    $summary = $this->controller->buildZonalSubjectwiseSummary($examYear);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'summary' => $summary,
                        'evaluationLabel' => $label,
                    ];
            }
        } else {
            // Regional
            if (!$region) {
                throw new \InvalidArgumentException("Region is required for regional evaluations");
            }
            // Skip loading candidates for optimized database queries
            $isDbOptimized = in_array($key, [
                'best-ten-girls', 'least-ten-girls', 'best-ten-boys', 'least-ten-boys',
                'overall-best-ten-students', 'overall-least-ten-students',
                'subjectwise-result-evaluation', 'subject-summary-evaluation',
                'mark-entry-status-report'
            ]);
            if (!$candidateRows && !$isDbOptimized) {
                $candidateRows = $this->controller->regionalCandidateRows($region, $examYear, false, '', $chunkSize);
            }

            $evaluationMap = $this->controller->regionalEvaluationEntries()->keyBy('key');
            $label = (string) data_get($evaluationMap->get($key), 'label', 'PSLE EVALUATION');

            switch ($key) {
                case 'general':
                    [$rows, $total] = $this->controller->buildGroupedRows($candidateRows, 'general');
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'general',
                        'evaluationLabel' => $label,
                        'zonalRank' => $this->controller->zonalRankForGroupedMode($examYear, $region, 'general'),
                    ];
                case 'councilwise':
                case 'best-ten-councils':
                case 'least-ten-councils':
                    [$rows] = $this->controller->buildGroupedRows($candidateRows, 'councilwise');
                    $rows = match ($key) {
                        'best-ten-councils' => $rows->take(10)->values(),
                        'least-ten-councils' => $rows->sort(function ($left, $right) {
                            $leftAvg = $left['avg_marks'] ?? INF;
                            $rightAvg = $right['avg_marks'] ?? INF;
                            if ($leftAvg !== $rightAvg) {
                                return $leftAvg <=> $rightAvg;
                            }
                            $leftGpa = $left['gpa'] ?? INF;
                            $rightGpa = $right['gpa'] ?? INF;
                            if ($leftGpa !== $rightGpa) {
                                return $rightGpa <=> $leftGpa;
                            }
                            return strcmp((string) ($left['sort_label'] ?? ''), (string) ($right['sort_label'] ?? ''));
                        })->take(10)->values(),
                        default => $rows,
                    };
                    $rows = $this->controller->applyPositions($rows);
                    $total = $this->controller->summariseGroupedRows($rows);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'councilwise',
                        'evaluationLabel' => $label,
                        'zonalRank' => $this->controller->zonalRankForGroupedMode($examYear, $region, 'councilwise'),
                    ];
                case 'schoolwise':
                case 'best-ten-schools':
                case 'least-ten-schools':
                case 'government-schools':
                case 'non-government-schools':
                    $filteredCandidates = match ($key) {
                        'government-schools' => $candidateRows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->values(),
                        'non-government-schools' => $candidateRows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->values(),
                        default => $candidateRows,
                    };
                    [$rows] = $this->controller->buildGroupedRows($filteredCandidates, 'schoolwise');
                    $rows = match ($key) {
                        'best-ten-schools' => $rows->take(10)->values(),
                        'least-ten-schools' => $rows->sort(function ($left, $right) {
                            $leftAvg = $left['avg_marks'] ?? INF;
                            $rightAvg = $right['avg_marks'] ?? INF;
                            if ($leftAvg !== $rightAvg) {
                                return $leftAvg <=> $rightAvg;
                            }
                            $leftGpa = $left['gpa'] ?? INF;
                            $rightGpa = $right['gpa'] ?? INF;
                            if ($leftGpa !== $rightGpa) {
                                return $rightGpa <=> $leftGpa;
                            }
                            return strcmp((string) ($left['sort_label'] ?? ''), (string) ($right['sort_label'] ?? ''));
                        })->take(10)->values(),
                        default => $rows,
                    };
                    $rows = $this->controller->applyPositions($rows);
                    $total = $this->controller->summariseGroupedRows($rows);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'schoolwise',
                        'evaluationLabel' => $label,
                        'zonalRank' => $this->controller->zonalRankForGroupedMode($examYear, $region, 'schoolwise', function (Collection $rows) use ($key) {
                            return match ($key) {
                                'government-schools' => $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'GOVERNMENT')->values(),
                                'non-government-schools' => $rows->filter(fn ($row) => strtoupper((string) ($row['ownership'] ?? '')) === 'NON-GOVERNMENT')->values(),
                                default => $rows,
                            };
                        }, $key === 'government-schools' ? 'gov' : ($key === 'non-government-schools' ? 'non-gov' : 'none')),
                    ];
                case 'districtwise':
                    [$rows, $total] = $this->controller->buildGroupedRows($candidateRows, 'districtwise');
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'districtwise',
                        'evaluationLabel' => $label,
                        'zonalRank' => $this->controller->zonalRankForGroupedMode($examYear, $region, 'districtwise'),
                    ];
                case 'ownership-result-evaluation':
                    [$rows, $total] = $this->controller->buildGroupedRows($candidateRows, 'ownership');
                    return [
                        'rows' => $rows->values()->toArray(),
                        'total' => $total,
                        'tableMode' => 'ownership',
                        'evaluationLabel' => $label,
                        'zonalRank' => $this->controller->zonalRankForGroupedMode($examYear, $region, 'ownership'),
                    ];
                case 'best-ten-girls':
                case 'least-ten-girls':
                case 'best-ten-boys':
                case 'least-ten-boys':
                case 'overall-best-ten-students':
                case 'overall-least-ten-students':
                    $rows = $this->controller->buildStudentRankingRowsOptimized($region, $examYear, $key);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'evaluationLabel' => $label,
                        'summary' => [
                            'students' => number_format($rows->count()),
                            'avg_gpa' => number_format((float) ($rows->pluck('gpa')->avg() ?? 0), 2),
                            'best_gpa' => number_format((float) ($rows->pluck('gpa')->min() ?? 0), 2),
                            'sex' => match ($key) {
                                'best-ten-girls', 'least-ten-girls' => 'FEMALE',
                                'best-ten-boys', 'least-ten-boys' => 'MALE',
                                default => 'MIXED',
                            },
                        ],
                    ];
                case 'subjectwise-result-evaluation':
                case 'subject-summary-evaluation':
                    $rows = $this->controller->buildSubjectwiseRows(collect(), $region, $examYear);
                    $summary = $this->controller->buildSubjectwiseSummary(collect(), $region, $examYear);
                    return [
                        'rows' => $rows->values()->toArray(),
                        'summary' => $summary,
                        'evaluationLabel' => $label,
                    ];
                case 'mark-entry-status-report':
                    $payload = $this->controller->markEntryStatusPayload(new Request(), $region, $label, $examYear);
                    $payload['region'] = [
                        'id' => $region->id,
                        'name' => $region->name,
                    ];
                    if ($payload['rows'] instanceof Collection) {
                        $payload['rows'] = $payload['rows']->values()->toArray();
                    }
                    return $payload;
            }
        }

        throw new \InvalidArgumentException("Invalid evaluation key: {$key}");
    }

    public function precalculateSingle(int $examYear, string $scopeType, ?int $scopeId, string $evaluationKey, bool $force = false, int $chunkSize = 500): void
    {
        $snapshotId = $this->getActiveSnapshotId($examYear);
        if (!$snapshotId) {
            Log::warning("Cannot precalculate PSLE evaluation for year {$examYear}: No active published snapshot found.");
            return;
        }

        // Check if already ready and not forced
        if (!$force) {
            $existing = PslePrecalculatedEvaluation::where('exam_year', $examYear)
                ->where('exam_type', 'PSLE')
                ->where('scope_type', $scopeType)
                ->where('scope_id', $scopeId)
                ->where('evaluation_key', $evaluationKey)
                ->where('snapshot_id', $snapshotId)
                ->first();
            if ($existing && $existing->status === PslePrecalculatedEvaluation::STATUS_READY) {
                Log::info("PSLE evaluation [{$scopeType}/" . ($scopeId ?? 'null') . "/{$evaluationKey}] is already ready. Skipping.");
                return;
            }
        }

        // Create or update status to building
        $record = PslePrecalculatedEvaluation::updateOrCreate([
            'exam_year' => $examYear,
            'exam_type' => 'PSLE',
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'evaluation_key' => $evaluationKey,
            'snapshot_id' => $snapshotId,
        ], [
            'status' => PslePrecalculatedEvaluation::STATUS_BUILDING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        $startMemory = memory_get_usage(true);
        Log::info("Starting precalculation for PSLE [{$scopeType}/" . ($scopeId ?? 'null') . "/{$evaluationKey}]. Initial memory: " . ($startMemory / 1024 / 1024) . " MB");

        try {
            $regionModel = null;
            if ($scopeType === 'regional' && $scopeId) {
                $regionModel = Region::find($scopeId);
            }

            // Since we process exactly one key, do not preload candidateRows here, calculatePayload will do it on demand (and only if not optimized)
            $payload = $this->calculatePayload($examYear, $scopeType, $regionModel, $evaluationKey, null, $chunkSize);

            $record->update([
                'status' => PslePrecalculatedEvaluation::STATUS_READY,
                'data' => $payload,
                'completed_at' => now(),
            ]);

            $endMemory = memory_get_usage(true);
            Log::info("Successfully precalculated PSLE [{$scopeType}/" . ($scopeId ?? 'null') . "/{$evaluationKey}]. Final memory: " . ($endMemory / 1024 / 1024) . " MB (diff: " . (($endMemory - $startMemory) / 1024 / 1024) . " MB)");

        } catch (\Throwable $e) {
            $errMemory = memory_get_usage(true);
            Log::error("Failed to precalculate PSLE evaluation [{$scopeType}/" . ($scopeId ?? 'null') . "/{$evaluationKey}]. Memory: " . ($errMemory / 1024 / 1024) . " MB. Error: " . $e->getMessage(), [
                'exception' => $e
            ]);

            $record->update([
                'status' => PslePrecalculatedEvaluation::STATUS_FAILED,
                'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
