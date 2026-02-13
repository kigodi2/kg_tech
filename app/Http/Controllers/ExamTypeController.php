<?php

namespace App\Http\Controllers;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Combination;
use App\Services\ExamTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExamTypeController extends Controller
{
    public function index()
    {
        if (request()->expectsJson()) {
            return response()->json(['data' => ExamType::all()]);
        }
        $examTypes = ExamType::paginate(15);
        return view('exam-types.index', compact('examTypes'));
    }

    public function show(ExamType $examType)
    {
        if (request()->expectsJson()) {
            return response()->json(['data' => $examType->load('subjects')]);
        }
        $examType->load('subjects');
        return view('exam-types.show', compact('examType'));
    }

    public function create()
    {
        return view('exam-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:exam_types',
            'name' => 'required',
            'description' => 'nullable',
            'education_level' => 'required|in:PRIMARY,SECONDARY,BOTH',
            'level' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $examType = ExamType::create($validated);
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Exam type created', 'data' => $examType], 201);
        }
        
        return redirect('/exam-types')->with('success', 'Exam Type created');
    }

    public function edit(ExamType $examType)
    {
        return view('exam-types.edit', compact('examType'));
    }

    public function update(Request $request, ExamType $examType)
    {
        $validated = $request->validate([
            'code' => 'required|unique:exam_types,code,' . $examType->id,
            'name' => 'required',
            'description' => 'nullable',
            'education_level' => 'nullable|in:PRIMARY,SECONDARY,BOTH',
            'level' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $examType->update($validated);
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Exam type updated', 'data' => $examType]);
        }
        
        return redirect('/exam-types')->with('success', 'Exam Type updated');
    }

    public function destroy(ExamType $examType)
    {
        $examType->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Exam type deleted']);
        }
        
        return redirect('/exam-types')->with('success', 'Exam Type deleted');
    }

    // Subjects CRUD
    public function getSubjects($examTypeCode)
    {
        $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
        $subjects = $examType->subjects()->get();
        return response()->json(['data' => $subjects]);
    }

    public function createSubject(Request $request, $examTypeCode)
    {
        Log::info('ExamTypeController: createSubject called', [
            'examTypeCode' => $examTypeCode,
            'request_data' => $request->all(),
        ]);

        try {
            $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
            
            $validated = $request->validate([
                'code' => 'required|unique:subjects',
                'name' => 'required|string',
                'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
                'writtenPapers' => 'required|integer|in:1,2,3',
                'hasPractical' => 'boolean',
                'hasProject' => 'boolean',
                'description' => 'nullable|string',
                'max_marks' => 'nullable|numeric',
                'is_active' => 'boolean',
            ]);

            Log::info('ExamTypeController: Validation passed', ['validated' => $validated]);

            $service = new ExamTypeService();
            $subject = $service->createSubject($examType, $validated);

            Log::info('ExamTypeController: Subject created successfully', [
                'subject_id' => $subject->id,
                'code' => $subject->code,
            ]);
            
            return response()->json(['message' => 'Subject created', 'data' => $subject], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ExamTypeController: Validation failed', ['errors' => $e->errors()]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('ExamTypeController: Failed to create subject', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error creating subject', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSubject(Request $request, $examTypeCode, $subjectId)
    {
        Log::info('ExamTypeController: updateSubject called', [
            'examTypeCode' => $examTypeCode,
            'subjectId' => $subjectId,
            'request_data' => $request->all(),
        ]);

        try {
            $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
            $subject = $examType->subjects()->findOrFail($subjectId);
            
            $validated = $request->validate([
                'code' => 'required|unique:subjects,code,' . $subject->id,
                'name' => 'required|string',
                'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
                'writtenPapers' => 'required|integer|in:1,2,3',
                'hasPractical' => 'boolean',
                'hasProject' => 'boolean',
                'description' => 'nullable|string',
                'max_marks' => 'nullable|numeric',
                'is_active' => 'boolean',
            ]);

            Log::info('ExamTypeController: Validation passed for update', ['validated' => $validated]);

            $service = new ExamTypeService();
            $subject = $service->updateSubject($subject, $validated);

            Log::info('ExamTypeController: Subject updated successfully', ['subject_id' => $subject->id]);

            return response()->json(['message' => 'Subject updated', 'data' => $subject]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('ExamTypeController: Validation failed on update', ['errors' => $e->errors()]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('ExamTypeController: Failed to update subject', [
                'subject_id' => $subjectId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Error updating subject', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteSubject($examTypeCode, $subjectId)
    {
        Log::info('ExamTypeController: deleteSubject called', [
            'examTypeCode' => $examTypeCode,
            'subjectId' => $subjectId,
        ]);

        try {
            $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
            $subject = $examType->subjects()->findOrFail($subjectId);
            
            $service = new ExamTypeService();
            $service->deleteSubject($subject);

            Log::info('ExamTypeController: Subject deleted successfully', ['subject_id' => $subjectId]);
            
            return response()->json(['message' => 'Subject deleted']);
        } catch (\Exception $e) {
            Log::error('ExamTypeController: Failed to delete subject', [
                'subject_id' => $subjectId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Error deleting subject', 'error' => $e->getMessage()], 500);
        }
    }

    // Combinations CRUD
    public function getCombinations($examTypeCode)
    {
        $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
        $page = request()->get('page', 1);
        $pageSize = request()->get('page_size', 25);
        $search = request()->get('search', '');

        $query = $examType->combinations();
        
        if ($search) {
            $query->where('code', 'like', "%{$search}%")
                  ->orWhere('subjects', 'like', "%{$search}%");
        }

        $total = $query->count();
        $combinations = $query->orderBy('created_at', 'desc')
                              ->skip(($page - 1) * $pageSize)
                              ->take($pageSize)
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $combinations,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $pageSize,
                'total' => $total,
                'total_pages' => ceil($total / $pageSize),
            ]
        ]);
    }

    public function createCombination(Request $request, $examTypeCode)
    {
        $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
        
        $validated = $request->validate([
            'code' => 'required|unique:combinations',
            'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
            'description' => 'nullable|string',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        try {
            // Get subject codes to store as subjects string
            $subjects = Subject::whereIn('id', $validated['subject_ids'])->pluck('code');
            
            $combination = Combination::create([
                'code' => $validated['code'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
                'subjects' => $subjects->implode(', '),
                'exam_type_id' => $examType->id,
            ]);

            // Attach subjects to combination (if using pivot table)
            $combination->subjects()->attach($validated['subject_ids']);
            
            return response()->json([
                'success' => true,
                'message' => 'Combination created successfully',
                'data' => $combination->load('subjects')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating combination: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCombination(Request $request, $examTypeCode, $combinationId)
    {
        $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
        $combination = $examType->combinations()->findOrFail($combinationId);
        
        $validated = $request->validate([
            'code' => 'required|unique:combinations,code,' . $combination->id,
            'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
            'description' => 'nullable|string',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        try {
            // Get subject codes to store as subjects string
            $subjects = Subject::whereIn('id', $validated['subject_ids'])->pluck('code');
            
            $combination->update([
                'code' => $validated['code'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
                'subjects' => $subjects->implode(', '),
            ]);

            // Update pivot table
            $combination->subjects()->sync($validated['subject_ids']);
            
            return response()->json([
                'success' => true,
                'message' => 'Combination updated successfully',
                'data' => $combination->load('subjects')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating combination: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteCombination($examTypeCode, $combinationId)
    {
        $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
        $combination = $examType->combinations()->findOrFail($combinationId);
        $combination->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Combination deleted successfully'
        ]);
    }

    /**
     * Get ACSEE candidates (read-only from registration/candidates)
     * Retrieved from registration module and enriched with allocated subjects
     */
    public function getAcseeCandicates(Request $request, $code)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('page_size', 15);
            $search = $request->get('search', '');
            $schoolId = $request->get('school_id', '');
            $districtId = $request->get('district_id', '');
            $regionId = $request->get('region_id', '');

            // Get exam type from code
            $examType = ExamType::where('code', strtoupper($code))->firstOrFail();

            $query = \App\Models\Candidate::where('exam_type', $examType->code)
                ->with('school', 'school.district', 'school.district.region')
                ->orderBy('candidate_id');

            // Filter by school
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }

            // Filter by district (through school relationship)
            if ($districtId) {
                $query->whereHas('school', function($q) use ($districtId) {
                    $q->where('district_id', $districtId)
                      ->whereNotNull('district_id');
                });
            }

            // Filter by region (through school -> district relationship)
            if ($regionId) {
                $query->whereHas('school.district', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)
                      ->whereNotNull('region_id');
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('candidate_id', 'like', "%{$search}%")
                      ->orWhere('full_name', 'like', "%{$search}%");
                });
            }

            $candidates = $query->paginate($pageSize);

            $data = $candidates->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'candidate_id' => $candidate->candidate_id,
                    'full_name' => $candidate->full_name,
                    'gender' => $candidate->gender,
                    'combination' => $candidate->combination,
                    'school_id' => $candidate->school_id,
                    'school_name' => $candidate->school?->name ?? '-',
                    'allocated_subjects' => $this->getCombinationSubjectsForExam($candidate->combination),
                    'exam_type' => $candidate->exam_type,
                    'status' => $candidate->status ?? 'registered',
                ];
            })->toArray();

            return response()->json([
                'candidates' => $data,
                'pagination' => [
                    'page' => $candidates->currentPage(),
                    'page_size' => $pageSize,
                    'total_count' => $candidates->total(),
                    'total_pages' => $candidates->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading candidates: ' . $e->getMessage(), [
                'code' => $code,
                'exception' => $e
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Get subjects for a combination
     */
    private function getCombinationSubjectsForExam($combinationCode)
    {
        if (!$combinationCode) {
            return [];
        }

        $combination = Combination::where('code', $combinationCode)->first();

        if (!$combination) {
            return [];
        }

        // Get subjects from relationship (use subjects() method to avoid column conflict)
        $subjects = $combination->subjects()->get();
        
        if (!$subjects || $subjects->isEmpty()) {
            return [];
        }

        return $subjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
            ];
        })->toArray();
    }
}
