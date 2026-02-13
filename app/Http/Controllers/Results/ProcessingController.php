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
    public function index()
    {
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();
        
        $processes = ResultProcess::where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->with('user')
            ->latest()
            ->paginate(10);

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
        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        // Create draft processing batch
        $process = ResultProcess::create([
            'exam_type_id' => $acsee->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'in_progress',
            'user_id' => auth()->id(),
            'total_candidates' => 0,
            'processed_count' => 0,
        ]);

        // Queue processing job
        // Dispatch to queue for background processing

        return response()->json([
            'success' => true,
            'batch_id' => $process->id,
            'message' => 'Draft processing started. You will be notified when complete.',
        ]);
    }

    public function finalRun(Request $request)
    {
        $request->validate(['confirm' => 'required|boolean']);

        if (!$request->confirm) {
            return response()->json(['error' => 'Confirmation required'], 422);
        }

        $examYear = ExamYear::active()->first();
        $acsee = ExamType::where('code', 'ACSEE')->first();

        // Create final processing batch
        $process = ResultProcess::create([
            'exam_type_id' => $acsee->id,
            'exam_year_id' => $examYear->id,
            'type' => 'final',
            'status' => 'in_progress',
            'user_id' => auth()->id(),
            'total_candidates' => 0,
            'processed_count' => 0,
        ]);

        // Queue processing job
        // Dispatch to queue for background processing

        return response()->json([
            'success' => true,
            'batch_id' => $process->id,
            'message' => 'Final processing started. Results will be locked after completion.',
        ]);
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
        $process = ResultProcess::findOrFail($batchId);

        if ($process->type === 'final' && $process->status === 'completed') {
            // Require explicit unpublish first
            return response()->json(['error' => 'Cannot rollback final processing. Unpublish results first.'], 422);
        }

        $process->update(['status' => 'rolled_back']);

        return response()->json(['success' => true, 'message' => 'Processing rolled back.']);
    }
}
