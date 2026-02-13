<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\GradingProfile;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Http\Request;

/**
 * GradingController
 *
 * Manages ACSEE grading profiles with grade boundaries,
 * GPA mapping, and competence levels.
 */
class GradingController extends Controller
{
    public function index()
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        $profiles = GradingProfile::where('exam_type_id', $acsee->id)
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
        $profile = GradingProfile::findOrFail($id);
        return view('results.acsee.grading.show', compact('profile'));
    }

    public function edit($id)
    {
        $profile = GradingProfile::findOrFail($id);
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
            'exam_type_id' => ExamType::where('code', 'ACSEE')->first()->id,
            ...$validated,
        ]);

        return redirect()->route('results.acsee.grading.show', $grading)
            ->with('success', 'Grading profile created successfully.');
    }

    public function update(Request $request, $id)
    {
        $profile = GradingProfile::findOrFail($id);
        
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
        $profile = GradingProfile::findOrFail($id);
        $profile->update(['is_locked' => true]);

        return back()->with('success', 'Grading profile locked. No further changes allowed.');
    }

    public function destroy($id)
    {
        $profile = GradingProfile::findOrFail($id);
        
        if ($profile->is_locked) {
            return back()->with('error', 'Cannot delete locked grading profile.');
        }

        $profile->delete();

        return redirect()->route('results.acsee.grading.index')
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
