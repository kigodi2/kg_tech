<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\MarkImportRunError;
use App\Models\MarkModerationAction;
use App\Models\MarkOutlierResolution;
use App\Models\RawMark;
use App\Services\MarkEntry\Outliers\EntryOutliersService;
use App\Services\MarkEntry\Moderation\IncResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IncResolutionApiController extends Controller
{
    private IncResolutionService $incService;

    public function __construct(IncResolutionService $incService)
    {
        $this->incService = $incService;
    }

    /**
     * POST /api/mark-entry/acsee/moderation/issues/{issueId}/accept-inc
     *
     * Accept a MISSING_REQUIRED_PAPER_MARK issue as INC.
     * issueId may be a MarkImportRunError.id or a RawMark.id (when no import run exists).
     */
    public function acceptInc(Request $request, int $issueId): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            // Try MarkImportRunError first; if not found, try RawMark
            $runError = \App\Models\MarkImportRunError::find($issueId);
            if ($runError) {
                $result = $this->incService->acceptAsInc($issueId, $request->user(), $validated['note'] ?? null);
            } else {
                $result = $this->incService->acceptAsIncFromRawMark($issueId, $request->user(), $validated['note'] ?? null);
            }

            return response()->json($result);
        } catch (\LogicException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Accept INC failed for issue {$issueId}: " . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => 'Failed to accept as INC: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/moderation/issues/{issueId}/reject
     *
     * Reject a MISSING_REQUIRED_PAPER_MARK issue.
     * issueId may be a MarkImportRunError.id or a RawMark.id (when no import run exists).
     */
    public function reject(Request $request, int $issueId): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $runError = \App\Models\MarkImportRunError::find($issueId);
            if ($runError) {
                $result = $this->incService->reject($issueId, $request->user(), $validated['note'] ?? null);
            } else {
                $result = $this->incService->rejectFromRawMark($issueId, $request->user(), $validated['note'] ?? null);
            }

            return response()->json($result);
        } catch (\LogicException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Reject INC failed for issue {$issueId}: " . $e->getMessage());
            return response()->json([
                'ok' => false,
                'message' => 'Failed to reject: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/moderation/issues/accept-inc-bulk
     *
     * Approve all actionable MISSING_REQUIRED_PAPER_MARK flags (as INC)
     * for the current Entry Outliers filter scope.
     */
    public function acceptIncBulk(Request $request, EntryOutliersService $outliersService): JsonResponse
    {
        // Bulk QA approval can process many rows across pages; avoid 30s web timeout.
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        $validated = $request->validate([
            'exam_year_id' => 'nullable|integer',
            'status' => 'nullable|string|max:50',
            'school_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'q' => 'nullable|string|max:255',
            'tab' => 'nullable|string|in:integrity,missing,swings',
            'selected_refs' => 'nullable|array',
            'selected_refs.*' => 'string|max:120',
            'note' => 'nullable|string|max:1000',
        ]);

        $filters = [
            'exam_year_id' => $validated['exam_year_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'school_id' => $validated['school_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'tab' => $validated['tab'] ?? null,
        ];

        try {
            $scopeIssueRefs = collect();
            $page = 1;
            $lastPage = 1;

            do {
                $paginator = $outliersService->list($request->user(), array_merge($filters, [
                    'page' => $page,
                    'per_page' => 100,
                ]));

                $scopeIssueRefs = $scopeIssueRefs->merge(
                    collect($paginator->items())
                        ->filter(fn (array $row) => in_array(($row['issue_type'] ?? null), ['MISSING_REQUIRED_PAPER_MARK', 'SUSPICIOUS_SPIKE'], true))
                        ->pluck('id')
                );

                $lastPage = max(1, (int) $paginator->lastPage());
                $page++;
            } while ($page <= $lastPage);

            $scopeIssueRefs = $scopeIssueRefs->filter()->unique()->values();
            $selectedRefs = collect($validated['selected_refs'] ?? [])->filter()->unique()->values();
            $issueRefs = $selectedRefs->isNotEmpty()
                ? $scopeIssueRefs->intersect($selectedRefs)->values()
                : $scopeIssueRefs;
            $initialMatched = $issueRefs->count();

            $runErrorIds = $issueRefs
                ->map(function ($ref) {
                    if (preg_match('/^run-error-(\d+)$/', (string) $ref, $m)) {
                        return (int) $m[1];
                    }
                    return null;
                })
                ->filter()
                ->values();

            $incRawMarkIds = $issueRefs
                ->map(function ($ref) {
                    if (preg_match('/^inc-(\d+)$/', (string) $ref, $m)) {
                        return (int) $m[1];
                    }
                    return null;
                })
                ->filter()
                ->values();

            $spikeRawMarkIds = $issueRefs
                ->map(function ($ref) {
                    if (preg_match('/^z-(\d+)$/', (string) $ref, $m)) {
                        return (int) $m[1];
                    }
                    return null;
                })
                ->filter()
                ->values();

            $actionableRefs = collect();

            if ($runErrorIds->isNotEmpty()) {
                $actionableRefs = $actionableRefs->merge(
                    MarkImportRunError::query()
                        ->whereIn('id', $runErrorIds->all())
                        ->where('error_code', MarkImportRunError::CODE_MISSING_REQUIRED_PAPER_MARK)
                        ->where('is_actionable', true)
                        ->where('is_resolved', false)
                        ->pluck('id')
                        ->map(fn ($id) => 'run-error-' . $id)
                );
            }

            if ($incRawMarkIds->isNotEmpty()) {
                $actionableRefs = $actionableRefs->merge(
                    RawMark::query()
                        ->whereIn('id', $incRawMarkIds->all())
                        ->where('has_errors', true)
                        ->pluck('id')
                        ->map(fn ($id) => 'inc-' . $id)
                );
            }

            if ($spikeRawMarkIds->isNotEmpty()) {
                $resolvedSpikes = MarkOutlierResolution::query()
                    ->where('issue_type', 'SUSPICIOUS_SPIKE')
                    ->whereIn('raw_mark_id', $spikeRawMarkIds->all())
                    ->pluck('raw_mark_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $resolvedSpikeSet = array_flip($resolvedSpikes);

                foreach ($spikeRawMarkIds as $id) {
                    $markId = (int) $id;
                    if (!isset($resolvedSpikeSet[$markId])) {
                        $actionableRefs->push('z-' . $markId);
                    }
                }
            }

            $issueRefs = $actionableRefs->unique()->values();
            $preSkipped = max(0, $initialMatched - $issueRefs->count());

            if ($issueRefs->isEmpty()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'No actionable flags found for the selected filters.',
                    'stats' => [
                        'matched' => $initialMatched,
                        'resolved' => 0,
                        'skipped' => $preSkipped,
                        'failed' => 0,
                    ],
                    'reasons' => $preSkipped > 0
                        ? ["{$preSkipped} row(s) were already resolved or had no actionable missing-paper error."]
                        : [],
                ]);
            }

            $resolved = 0;
            $skipped = $preSkipped;
            $failed = 0;
            $failReasons = [];
            $note = $validated['note'] ?? 'Bulk approved from Entry Outliers QA';
            $actor = $request->user();

            foreach ($issueRefs as $issueRef) {
                try {
                    if (preg_match('/^run-error-(\d+)$/', (string) $issueRef, $m)) {
                        $this->incService->acceptAsInc((int) $m[1], $actor, $note);
                        $resolved++;
                        continue;
                    }

                    if (preg_match('/^inc-(\d+)$/', (string) $issueRef, $m)) {
                        $this->incService->acceptAsIncFromRawMark((int) $m[1], $actor, $note);
                        $resolved++;
                        continue;
                    }

                    if (preg_match('/^z-(\d+)$/', (string) $issueRef, $m)) {
                        $rawMarkId = (int) $m[1];
                        $correlationId = (string) Str::uuid();
                        MarkOutlierResolution::query()->updateOrCreate(
                            [
                                'raw_mark_id' => $rawMarkId,
                                'issue_type' => 'SUSPICIOUS_SPIKE',
                            ],
                            [
                                'resolution_action' => 'APPROVED',
                                'note' => $note ?: 'Approved from Entry Outliers QA bulk action',
                                'resolved_by' => $actor?->id,
                                'resolved_at' => now(),
                                'resolution_correlation_id' => $correlationId,
                            ]
                        );

                        $rawMark = RawMark::query()->find($rawMarkId);
                        MarkModerationAction::query()->create([
                            'action' => 'APPROVE_SPIKE',
                            'scope' => 'candidate',
                            'actor_id' => $actor?->id,
                            'mark_import_batch_id' => $rawMark?->mark_import_batch_id,
                            'exam_year_id' => null,
                            'school_id' => null,
                            'subject_id' => $rawMark?->subject_id,
                            'candidate_id' => $rawMark?->candidate_id,
                            'affected_rows' => 1,
                            'reason' => 'Suspicious spike approved: ' . ($note ?: 'Bulk approval'),
                            'correlation_id' => $correlationId,
                        ]);
                        $resolved++;
                        continue;
                    }

                    $skipped++;
                } catch (\LogicException $e) {
                    $skipped++;
                    if (count($failReasons) < 5) {
                        $failReasons[] = $e->getMessage();
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    \Log::error('Bulk Accept INC failed for issue ref: ' . $issueRef, [
                        'error' => $e->getMessage(),
                        'user_id' => $request->user()?->id,
                    ]);
                    if (count($failReasons) < 5) {
                        $failReasons[] = $e->getMessage();
                    }
                }
            }

            return response()->json([
                'ok' => true,
                'message' => "Bulk approval completed. Resolved {$resolved}, skipped {$skipped}, failed {$failed}.",
                'stats' => [
                    'matched' => $issueRefs->count(),
                    'resolved' => $resolved,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ],
                'reasons' => $failReasons,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Bulk Accept INC failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Failed to approve all flags: ' . $e->getMessage(),
            ], 500);
        }
    }
}
