<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ResultProcess;
use App\Models\ExamYear;
use Illuminate\Http\Request;

/**
 * AuditController
 *
 * Manages governance and audit logging for result processing:
 * who processed results, when grading changed, processing history, etc.
 */
class AuditController extends Controller
{
    public function index()
    {
        $examYear = ExamYear::active()->first();
        
        return view('results.acsee.audit.index', compact('examYear'));
    }

    public function logs(Request $request)
    {
        $examYear = ExamYear::active()->first();
        
        $query = AuditLog::where('module', 'results')
            ->where('exam_year_id', $examYear->id)
            ->with('user');

        // Filters
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->latest()->paginate(20);

        return view('results.acsee.audit.logs', compact('logs', 'examYear'));
    }

    public function processingHistory(Request $request)
    {
        $examYear = ExamYear::active()->first();

        $history = ResultProcess::where('exam_year_id', $examYear->id)
            ->with('user')
            ->latest('processed_at')
            ->paginate(20);

        return view('results.acsee.audit.processing-history', compact('history', 'examYear'));
    }

    public function publicationHistory(Request $request)
    {
        $examYear = ExamYear::active()->first();

        // Get all publish/unpublish events
        $history = AuditLog::where('module', 'results')
            ->where('exam_year_id', $examYear->id)
            ->whereIn('action', ['publish_result', 'unpublish_result'])
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('results.acsee.audit.publication-history', compact('history', 'examYear'));
    }

    public function exportLogs(Request $request)
    {
        $format = $request->validate(['format' => 'required|in:pdf,excel,csv'])['format'];
        $examYear = ExamYear::active()->first();

        $logs = AuditLog::where('module', 'results')
            ->where('exam_year_id', $examYear->id)
            ->with('user')
            ->latest()
            ->get();

        // Generate export in specified format
        // For now, return placeholder response

        return response()->json(['success' => true, 'message' => 'Audit logs export queued.']);
    }
}
