<?php

namespace App\Http\Middleware;

use App\Models\ExamYear;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * SetExamYearContext Middleware
 *
 * Responsibilities:
 * 1. Resolve the selected exam year from session or request
 * 2. Validate that the exam year exists
 * 3. Reject access if year is locked and request is write-based
 * 4. Bind resolved year to app container for global access
 *
 * Usage in kernel:
 * protected $middlewareGroups = [
 *     'web' => [
 *         ...
 *         \App\Http\Middleware\SetExamYearContext::class,
 *     ],
 * ];
 */
class SetExamYearContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get exam year from multiple sources (in order of precedence)
            $examYearId = $this->resolveExamYearId($request);

            // If no exam year found, use the active one
            if (!$examYearId) {
                $activeYear = ExamYear::active()->first();

                if (!$activeYear) {
                    // No active year exists - this is a setup issue
                    return response()->view('errors.no-exam-year', [], 500);
                }

                $examYear = $activeYear;
            } else {
                // Validate that exam year exists
                $examYear = ExamYear::findOrFail($examYearId);
            }

            // Store in session for persistence across requests
            Session::put('exam_year_id', $examYear->id);

            // Bind to app container for global access
            app()->instance('examYear', $examYear);
            app()->instance('examYearId', $examYear->id);

            // Check if this is a write operation on a locked year
            if ($this->isWriteOperation($request) && $examYear->isLocked()) {
                return response()->json([
                    'error' => 'Locked Year',
                    'message' => "Exam year {$examYear->year_label} is locked and read-only",
                ], 423); // 423 Locked
            }

            // Add exam year to request for use in controllers
            $request->attributes->add(['examYear' => $examYear]);
            $request->attributes->add(['examYearId' => $examYear->id]);

            // Set URL defaults for year and examYear dynamically
            \Illuminate\Support\Facades\URL::defaults([
                'year' => (int) ($examYear->year_label ?? 2026),
                'examYear' => (int) ($examYear->year_label ?? 2026),
            ]);
        } catch (\Exception $e) {
            // During testing or setup, if exam_years table doesn't exist yet, just continue
            // This allows migrations to run without failing
            if (app()->environment(['testing', 'local'])) {
                // In testing/local, allow request to pass without exam year context
                return $next($request);
            }
            throw $e;
        }

        return $next($request);
    }

    /**
     * Resolve exam year ID from request.
     *
     * Checks in this order:
     * 1. Session (if already selected)
     * 2. Request parameter (exam_year_id)
     * 3. Request header (X-Exam-Year-ID)
     * 4. Route parameter (if route has {exam_year})
     */
    protected function resolveExamYearId(Request $request): ?int
    {
        // Check session first
        if (Session::has('exam_year_id')) {
            return Session::get('exam_year_id');
        }

        // Check query parameter
        if ($request->has('exam_year_id')) {
            return (int) $request->exam_year_id;
        }

        // Check request header
        if ($request->hasHeader('X-Exam-Year-ID')) {
            return (int) $request->header('X-Exam-Year-ID');
        }

        // Check route parameter
        if ($request->route() && $request->route()->parameter('exam_year')) {
            return (int) $request->route()->parameter('exam_year');
        }

        return null;
    }

    /**
     * Determine if the request is a write operation.
     *
     * Write operations: POST, PUT, PATCH, DELETE
     * Read operations: GET, HEAD, OPTIONS
     */
    protected function isWriteOperation(Request $request): bool
    {
        if ($request->is('login', 'logout', 'api/login', 'api/logout', 'mock-portal/*')) {
            return false;
        }

        return in_array($request->method(), [
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
        ]);
    }
}
