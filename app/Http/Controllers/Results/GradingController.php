<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\GradingProfile;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GradingController
 *
 * Manages ACSEE grading profiles with grade boundaries,
 * GPA mapping, and competence levels.
 */
class GradingController extends Controller
{
    private function routePrefix(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'results.psle' : 'results.acsee';
    }

    private function examCode(Request $request): string
    {
        return $request->routeIs('results.psle.*') ? 'PSLE' : 'ACSEE';
    }

    private function examType(Request $request): ExamType
    {
        return ExamType::query()->where('code', $this->examCode($request))->firstOrFail();
    }

    private function scopedProfile(Request $request, $id): GradingProfile
    {
        $profile = GradingProfile::query()
            ->where('exam_type_id', $this->examType($request)->id)
            ->find($id);

        if (!$profile) {
            throw new NotFoundHttpException('Grading profile not found for this exam type.');
        }

        return $profile;
    }

    private function primaryProfile(Request $request): ?GradingProfile
    {
        return GradingProfile::query()
            ->where('exam_type_id', $this->examType($request)->id)
            ->orderByDesc('is_active')
            ->orderByDesc('exam_year_id')
            ->orderBy('id')
            ->first();
    }

    private function fallbackProfileRedirect(Request $request): ?RedirectResponse
    {
        if (!$request->routeIs('results.psle.*')) {
            return null;
        }

        $profile = $this->primaryProfile($request);
        if (!$profile) {
            return null;
        }

        return redirect()->route($this->routePrefix($request) . '.grading.show', $profile->id);
    }

    public function index()
    {
        $examType = $this->examType(request());
        $profiles = GradingProfile::where('exam_type_id', $examType->id)
            ->with('examYear')
            ->latest()
            ->paginate(10);

        $examYears = ExamYear::where('is_active', true)->get();

        return view('results.acsee.grading.index', compact('profiles', 'examYears'));
    }

    public function create()
    {
        $examYears = ExamYear::where('is_active', true)->get();
        return view('results.acsee.grading.create', compact('examYears'));
    }

    public function show($id)
    {
        try {
            $profile = $this->scopedProfile(request(), $id);
        } catch (NotFoundHttpException $e) {
            $fallback = $this->fallbackProfileRedirect(request());
            if ($fallback) {
                return $fallback;
            }

            throw $e;
        }

        return view('results.acsee.grading.show', compact('profile'));
    }

    public function edit($id)
    {
        try {
            $profile = $this->scopedProfile(request(), $id);
        } catch (NotFoundHttpException $e) {
            $fallback = $this->fallbackProfileRedirect(request());
            if ($fallback) {
                return $fallback;
            }

            throw $e;
        }

        $examYears = ExamYear::where('is_active', true)->get();
        return view('results.acsee.grading.edit', compact('profile', 'examYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'exam_year_id' => 'required|exists:exam_years,id',
            'grade_boundaries' => 'required|array',
            'gpa_mapping' => 'required|array',
            'competence_levels' => 'required|array',
        ]);

        $grading = GradingProfile::create([
            'exam_type_id' => $this->examType($request)->id,
            ...$validated,
        ]);

        return redirect()->route($this->routePrefix($request) . '.grading.show', $grading)
            ->with('success', 'Grading profile created successfully.');
    }

    public function update(Request $request, $id)
    {
        $profile = $this->scopedProfile($request, $id);
        
        if ($profile->is_locked) {
            return back()->with('error', 'Cannot edit locked grading profile.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_boundaries' => 'required|array',
            'gpa_mapping' => 'required|array',
            'competence_levels' => 'required|array',
        ]);

        $profile->update($validated);

        return back()->with('success', 'Grading profile updated successfully.');
    }

    public function lock($id)
    {
        $profile = $this->scopedProfile(request(), $id);
        $profile->update(['is_locked' => true]);

        return back()->with('success', 'Grading profile locked. No further changes allowed.');
    }

    public function destroy($id)
    {
        $profile = $this->scopedProfile(request(), $id);
        
        if ($profile->is_locked) {
            return back()->with('error', 'Cannot delete locked grading profile.');
        }

        $profile->delete();

        return redirect()->route($this->routePrefix(request()) . '.grading.index')
            ->with('success', 'Grading profile deleted.');
    }

    public function previewGrade(Request $request)
    {
        // API endpoint for grade calculation preview
        $marks = $request->validate(['marks' => 'required|numeric|min:0|max:100']);
        $profileId = $request->validate(['profile_id' => 'required|exists:grading_profiles,id'])['profile_id'];

        $profile = GradingProfile::find($profileId);
        $grade = $this->calculateGrade($marks['marks'], $profile);

        return response()->json(['grade' => $grade]);
    }

    private function calculateGrade($marks, $profile)
    {
        // Implementation will use profile's grade boundaries
        foreach ($profile->grade_boundaries as $boundary) {
            if ($marks >= $boundary['min'] && $marks <= $boundary['max']) {
                return $boundary['grade'];
            }
        }
        return 'F';
    }
}
