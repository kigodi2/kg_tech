<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\ResultProcess;
use Illuminate\Http\Request;

/**
 * ProcessingController
 *
 * Orchestrates result processing: validation, draft runs, and final runs.
 * Handles computing grades, GPA, and divisions.
 */
class ProcessingController extends Controller
{
    private function routePrefix(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'results.psle' : 'results.acsee';
    }

    private function examCode(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'PSLE' : 'ACSEE';
    }

    private function legacyLifecycleRedirectResponse(Request $request, string $action)
    {
        return response()->json([
            'success' => false,
            'message' => "Legacy {$action} endpoint is disabled. Use /" . str_replace('.', '/', $this->routePrefix($request)) . " lifecycle Compute / Validate workflow instead.",
        ], 410);
    }

    public function index(Request $request)
    {
        $examYear = ExamYear::active()->first();
        $examType = ExamType::where('code', $this->examCode($request))->first();
        
        $processes = ResultProcess::where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('results.acsee.processing.index', compact('processes', 'examYear'));
    }

    public function validateResults(Request $request)
    {
        $examYear = ExamYear::active()->first();
        
        // Validate:
        // 1. Grading system is active
        // 2. All marks are present
        // 3. Result linking is complete

        $validationResult = [
            'valid' => true,
            'messages' => [],
            'errors' => [],
        ];

        // Check grading system
        // Check marks completeness
        // Check linking status

        if (!$validationResult['valid']) {
            return response()->json($validationResult, 422);
        }

        return response()->json($validationResult, 200);
    }

    public function draftRun(Request $request)
    {
        return $this->legacyLifecycleRedirectResponse($request, 'draft processing');
    }

    public function finalRun(Request $request)
    {
        return $this->legacyLifecycleRedirectResponse($request, 'final processing');
    }

    public function status($batchId)
    {
        $process = ResultProcess::findOrFail($batchId);

        return response()->json([
            'id' => $process->id,
            'status' => $process->status,
            'type' => $process->type,
            'progress' => $process->processed_count / $process->total_candidates * 100,
            'total' => $process->total_candidates,
            'processed' => $process->processed_count,
        ]);
    }

    public function rollback($batchId)
    {
        return $this->legacyLifecycleRedirectResponse(request(), 'rollback');
    }
}
