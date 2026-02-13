<?php

namespace App\Http\Controllers\MarkEntry\Submission;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Http\Request;

class MarkEntrySubmissionController extends Controller {

    private LifecycleStateService $lifecycleService;

    public function __construct(LifecycleStateService $lifecycleService) {
        $this->lifecycleService = $lifecycleService;
    }

    public function dashboard() {
        $batches = MarkImportBatch::where('lifecycle_state', 'approved')->paginate(20);
        return view('mark-entry.submission.dashboard', ['batches' => $batches]);
    }

    public function lockBatch(Request $request, MarkImportBatch $batch) {
        $this->authorize('lock', $batch);
        
        try {
            $this->lifecycleService->transition($batch, 'submitted', auth()->user(), 'Locked for submission');
            return response()->json(['success' => true, 'message' => 'Batch locked']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
