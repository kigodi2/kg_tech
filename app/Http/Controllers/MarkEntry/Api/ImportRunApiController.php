<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Models\MarkImportRun;
use App\Models\MarkImportRunError;
use App\Services\MarkImport\MarkImportRunService;
use Illuminate\Http\Request;

class ImportRunApiController extends Controller
{
    private MarkImportRunService $runService;

    public function __construct(MarkImportRunService $runService)
    {
        $this->runService = $runService;
    }

    /**
     * GET /api/mark-entry/acsee/import/runs
     * List import runs with optional filters.
     */
    public function index(Request $request)
    {
        $query = MarkImportRun::with(['user:id,name', 'school:id,code,name', 'subject:id,code,name'])
            ->orderByDesc('created_at');

        if ($request->filled('exam_year_id')) {
            $query->where('exam_year_id', $request->exam_year_id);
        }
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('scope_type')) {
            $query->where('scope_type', $request->scope_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Scope to user's district unless admin
        $user = auth()->user();
        if ($user && !$user->isAdmin() && $user->district_id) {
            $query->where('district_id', $user->district_id);
        }

        $runs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $runs,
        ]);
    }

    /**
     * GET /api/mark-entry/acsee/import/runs/{run}
     * Get a single run with summary.
     */
    public function show(MarkImportRun $run)
    {
        $run->load(['user:id,name', 'school:id,code,name', 'subject:id,code,name', 'batch']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $run->id,
                'correlation_id' => $run->correlation_id,
                'scope_type' => $run->scope_type,
                'file_name' => $run->original_file_name ?? $run->file_name,
                'status' => $run->status,
                'user' => $run->user ? ['id' => $run->user->id, 'name' => $run->user->name] : null,
                'school' => $run->school ? ['id' => $run->school->id, 'code' => $run->school->code, 'name' => $run->school->name] : null,
                'subject' => $run->subject ? ['id' => $run->subject->id, 'code' => $run->subject->code, 'name' => $run->subject->name] : null,
                'batch_id' => $run->mark_import_batch_id,
                'totals' => [
                    'total_rows' => $run->total_rows,
                    'success_rows' => $run->success_rows,
                    'error_rows' => $run->error_rows,
                    'warning_rows' => $run->warning_rows,
                ],
                'summary' => $run->summary,
                'started_at' => $run->started_at?->toIso8601String(),
                'completed_at' => $run->completed_at?->toIso8601String(),
                'created_at' => $run->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/mark-entry/acsee/import/runs/{run}/errors
     * Get paginated errors for a run.
     */
    public function errors(MarkImportRun $run, Request $request)
    {
        $query = $run->errors()->orderBy('row_number');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('error_code')) {
            $query->where('error_code', $request->error_code);
        }

        $errors = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $errors,
        ]);
    }

    /**
     * GET /api/mark-entry/acsee/import/runs/{run}/errors.csv
     * Download error report as CSV.
     */
    public function errorsCsv(MarkImportRun $run)
    {
        $csv = $this->runService->generateErrorCsv($run);

        $filename = "import-errors-run-{$run->id}-" . now()->format('YmdHi') . '.csv';

        return response()->streamDownload(
            fn() => print($csv),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    /**
     * GET /api/mark-entry/acsee/import/runs/{run}/preview
     * Get preview rows for a run.
     */
    public function preview(MarkImportRun $run, Request $request)
    {
        $query = $run->rows()->orderBy('row_number');

        if ($request->filled('valid_only')) {
            $query->where('is_valid', true);
        }
        if ($request->filled('invalid_only')) {
            $query->where('is_valid', false);
        }

        $rows = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }
}
