<?php

namespace App\Http\Controllers;

use App\Models\ExamYear;
use App\Services\ExamYears\ExamYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * ExamYearController
 *
 * Handles exam year management endpoints.
 * All write operations check for locked years via middleware.
 */
class ExamYearController extends Controller
{
    public function __construct(
        private ExamYearService $examYearService
    ) {
        // Apply policy authorization
        $this->authorizeResource(ExamYear::class, 'examYear');
    }

    /**
     * Display all exam years.
     *
     * GET /exam-years
     */
    public function index()
    {
        $years = $this->examYearService->all();

        return view('exam-years.index', [
            'years' => $years,
            'activeYear' => $this->examYearService->getActive(),
        ]);
    }

    /**
     * Show create exam year form.
     *
     * GET /exam-years/create
     */
    public function create()
    {
        $this->authorize('create', ExamYear::class);

        return view('exam-years.create');
    }

    /**
     * Store a newly created exam year.
     *
     * POST /exam-years
     */
    public function store(Request $request)
    {
        $this->authorize('create', ExamYear::class);

        $validated = $request->validate([
            'year_label' => 'required|string|size:4|unique:exam_years,year_label',
        ]);

        $examYear = $this->examYearService->create($validated);

        Cache::forget('exam_years');

        return redirect()->route('exam-years.show', $examYear)
            ->with('success', "Exam year {$examYear->year_label} created successfully");
    }

    /**
     * Display exam year details.
     *
     * GET /exam-years/{examYear}
     */
    public function show(ExamYear $examYear)
    {
        $statistics = $this->examYearService->getStatistics($examYear->id);

        return view('exam-years.show', [
            'examYear' => $examYear,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show edit exam year form.
     *
     * GET /exam-years/{examYear}/edit
     */
    public function edit(ExamYear $examYear)
    {
        $this->authorize('update', $examYear);

        if ($examYear->isLocked()) {
            return redirect()->route('exam-years.show', $examYear)
                ->with('error', 'Cannot edit a locked exam year');
        }

        return view('exam-years.edit', ['examYear' => $examYear]);
    }

    /**
     * Update exam year.
     *
     * PUT /exam-years/{examYear}
     */
    public function update(Request $request, ExamYear $examYear)
    {
        $this->authorize('update', $examYear);

        if ($examYear->isLocked()) {
            return response()->json([
                'error' => 'Locked Year',
                'message' => 'Cannot update a locked exam year',
            ], 423);
        }

        // Allow updating only specific fields
        $validated = $request->validate([
            'year_label' => 'required|string|size:4|unique:exam_years,year_label,' . $examYear->id,
        ]);

        $examYear->update($validated);

        Cache::forget('exam_years');

        return redirect()->route('exam-years.show', $examYear)
            ->with('success', "Exam year updated successfully");
    }

    /**
     * Delete exam year.
     *
     * DELETE /exam-years/{examYear}
     */
    public function destroy(ExamYear $examYear)
    {
        $this->authorize('delete', $examYear);

        if ($examYear->isLocked() || $examYear->isPublished()) {
            return response()->json([
                'error' => 'Cannot Delete',
                'message' => 'Cannot delete a published or locked exam year',
            ], 422);
        }

        $examYear->delete();

        Cache::forget('exam_years');

        return redirect()->route('exam-years.index')
            ->with('success', "Exam year deleted successfully");
    }

    /**
     * Activate an exam year.
     *
     * POST /exam-years/{examYear}/activate
     */
    public function activate(ExamYear $examYear)
    {
        $this->authorize('activate', $examYear);

        $this->examYearService->activate($examYear->id);

        Cache::forget('exam_years');
        Cache::forget('active_exam_year');

        return redirect()->route('exam-years.show', $examYear)
            ->with('success', "Exam year {$examYear->year_label} is now active");
    }

    /**
     * Publish results for an exam year (triggers locking).
     *
     * POST /exam-years/{examYear}/publish
     */
    public function publish(ExamYear $examYear)
    {
        $this->authorize('publish', $examYear);

        if ($examYear->isPublished()) {
            return response()->json([
                'error' => 'Already Published',
                'message' => 'Results for this year have already been published',
            ], 422);
        }

        try {
            $this->examYearService->publishResults($examYear->id);

            Cache::forget('exam_years');
            Cache::forget('active_exam_year');

            return redirect()->route('exam-years.show', $examYear)
                ->with('success', "Results published and year locked successfully");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get exam years as JSON (for AJAX calls, selectors, etc.).
     *
     * GET /api/exam-years
     */
    public function indexApi()
    {
        $years = Cache::rememberForever('exam_years', function () {
            return $this->examYearService->all();
        });

        $activeYear = Cache::rememberForever('active_exam_year', function () {
            return $this->examYearService->getActive();
        });

        return response()->json([
            'data' => $years,
            'active_year' => $activeYear,
        ]);
    }
}
