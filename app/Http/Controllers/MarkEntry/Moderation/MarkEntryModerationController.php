<?php

namespace App\Http\Controllers\MarkEntry\Moderation;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Services\MarkEntry\Moderation\MarkModerationService;
use Illuminate\Http\Request;

class MarkEntryModerationController extends Controller {

    private MarkModerationService $moderationService;

    public function __construct(MarkModerationService $moderationService) {
        $this->moderationService = $moderationService;
    }

    public function dashboard() {
        $batches = MarkImportBatch::where('lifecycle_state', 'awaiting_moderation')
            ->paginate(20);
        return view('mark-entry.moderation.dashboard', ['batches' => $batches]);
    }

    public function reviewBatch(MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        return view('mark-entry.moderation.review-batch', ['batch' => $batch]);
    }

    public function approveBatch(Request $request, MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        
        try {
            $validated = $request->validate(['feedback' => 'nullable|string|max:1000']);
            $this->moderationService->approveBatch($batch, auth()->user(), $validated['feedback'] ?? null);
            return response()->json(['success' => true, 'message' => 'Batch approved']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function rejectBatch(Request $request, MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        
        try {
            $validated = $request->validate(['reason' => 'required|string|min:10|max:1000']);
            $this->moderationService->rejectBatch($batch, auth()->user(), $validated['reason']);
            return response()->json(['success' => true, 'message' => 'Batch rejected']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
