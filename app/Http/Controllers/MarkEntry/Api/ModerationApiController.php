<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Models\MarkImportRun;
use App\Models\MarkImportRunError;
use App\Models\MarkRejection;
use App\Services\MarkEntry\Moderation\ModerationDashboardService;
use App\Services\MarkEntry\Moderation\ModerationActionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModerationApiController extends Controller
{
    private ModerationDashboardService $dashboardService;
    private ModerationActionService $actionService;

    public function __construct(
        ModerationDashboardService $dashboardService,
        ModerationActionService $actionService
    ) {
        $this->dashboardService = $dashboardService;
        $this->actionService = $actionService;
    }

    /**
     * GET /api/mark-entry/acsee/moderation/dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $examYear = $request->input('exam_year');
            $stats = $this->dashboardService->getDashboardStats($examYear ? (int) $examYear : null);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            $correlationId = (string) Str::uuid();
            \Log::error("Moderation dashboard error [{$correlationId}]", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/moderation/pending
     */
    public function pending(Request $request)
    {
        try {
            $filters = $request->only(['exam_year', 'region_id', 'district_id', 'school_id', 'subject_id', 'status']);
            $perPage = (int) $request->input('per_page', 20);
            $batches = $this->dashboardService->getPendingQueue($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $batches->items(),
                'pagination' => [
                    'total' => $batches->total(),
                    'per_page' => $batches->perPage(),
                    'current_page' => $batches->currentPage(),
                    'last_page' => $batches->lastPage(),
                    'has_more' => $batches->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            $correlationId = (string) Str::uuid();
            \Log::error("Moderation pending error [{$correlationId}]", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending queue.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/moderation/errors?run_id=...&batch_id=...
     */
    public function errors(Request $request)
    {
        try {
            $runId = $request->input('run_id');
            $batchId = $request->input('batch_id');
            $perPage = (int) $request->input('per_page', 50);
            $filters = $request->only(['severity', 'error_code']);

            if ($runId) {
                $errors = $this->dashboardService->getRunErrors((int) $runId, $filters, $perPage);
            } elseif ($batchId) {
                if ($this->dashboardService->batchHasLinkedRuns((int) $batchId)) {
                    $errors = $this->dashboardService->getBatchErrors((int) $batchId, $perPage);
                } else {
                    $errors = $this->dashboardService->getBatchRawMarkErrors((int) $batchId, $perPage);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either run_id or batch_id is required.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $errors->items(),
                'pagination' => [
                    'total' => $errors->total(),
                    'per_page' => $errors->perPage(),
                    'current_page' => $errors->currentPage(),
                    'last_page' => $errors->lastPage(),
                    'has_more' => $errors->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            $correlationId = (string) Str::uuid();
            \Log::error("Moderation errors fetch error [{$correlationId}]", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load errors.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/mark-entry/acsee/moderation/errors/csv?run_id=...&batch_id=...
     */
    public function errorsCsv(Request $request)
    {
        $runId = $request->input('run_id');
        $batchId = $request->input('batch_id');

        if (!$runId && !$batchId) {
            return response()->json(['success' => false, 'message' => 'run_id or batch_id is required.'], 422);
        }

        try {
            if ($batchId && !$runId) {
                // Find the latest run for this batch
                $run = MarkImportRun::where('mark_import_batch_id', $batchId)
                    ->latest('id')->first();
                $runId = $run?->id;
            }

            // If no linked run, fall back to raw_marks errors
            if (!$runId && $batchId) {
                $rows = $this->dashboardService->exportBatchRawMarkErrorsCsv((int) $batchId);
                $filename = "errors_batch_{$batchId}_" . now()->format('Ymd_His') . '.csv';
            } elseif ($runId) {
                $rows = $this->dashboardService->exportRunErrorsCsv((int) $runId);
                $filename = 'errors_' . ($batchId ? "batch_{$batchId}" : "run_{$runId}") . '_' . now()->format('Ymd_His') . '.csv';
            } else {
                return response()->json(['success' => false, 'message' => 'No errors data available.'], 404);
            }

            $callback = function () use ($rows) {
                $handle = fopen('php://output', 'w');
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\Exception $e) {
            $correlationId = (string) Str::uuid();
            \Log::error("Error CSV export failed [{$correlationId}]", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to export errors.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/mark-entry/acsee/moderation/approve
     *
     * Body: { scope: "single_subject"|"school"|"district",
     *         batch_id?, exam_year_id?, school_id?, subject_id?, district_id?,
     *         feedback? }
     */
    public function approve(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string|in:single_subject,school,district',
            'batch_id' => 'nullable|integer|exists:mark_import_batches,id',
            'exam_year_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'feedback' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->actionService->approve(
                $validated['scope'],
                $validated,
                auth()->user(),
                $validated['feedback'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "Approved {$result['batch_count']} batch(es), {$result['approved_count']} marks.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error("Moderation approve error", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/mark-entry/acsee/moderation/reject
     *
     * Body: { scope: "candidate"|"subject_batch"|"run"|"batch",
     *         batch_id?, run_id?, candidate_id?, row_number?,
     *         reason_code, note? }
     */
    public function reject(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string|in:candidate,subject_batch,run,batch',
            'batch_id' => 'nullable|integer|exists:mark_import_batches,id',
            'run_id' => 'nullable|integer',
            'candidate_id' => 'nullable|integer',
            'row_number' => 'nullable|integer',
            'exam_year_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'reason_code' => 'required|string|in:DATA_QUALITY,MISSING_MARKS,WRONG_SUBJECT,DUPLICATE_SUBMISSION,TEMPLATE_ERROR,OTHER',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->actionService->reject(
                $validated['scope'],
                $validated,
                auth()->user(),
                $validated['reason_code'],
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "Rejected {$result['batch_count']} batch(es), {$result['rejected_count']} marks.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error("Moderation reject error", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/mark-entry/acsee/moderation/rejections?batch_id=...
     */
    public function rejections(Request $request)
    {
        try {
            $batchId = $request->input('batch_id');
            $perPage = (int) $request->input('per_page', 20);

            $query = MarkRejection::with(['rejectedByUser:id,name', 'batch:id,batch_code'])
                ->orderBy('created_at', 'desc');

            if ($batchId) {
                $query->where('mark_import_batch_id', $batchId);
            }

            $rejections = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $rejections->items(),
                'pagination' => [
                    'total' => $rejections->total(),
                    'per_page' => $rejections->perPage(),
                    'current_page' => $rejections->currentPage(),
                    'last_page' => $rejections->lastPage(),
                    'has_more' => $rejections->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load rejections.',
            ], 500);
        }
    }
}
