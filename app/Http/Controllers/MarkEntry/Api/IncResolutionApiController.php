<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Services\MarkEntry\Moderation\IncResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
