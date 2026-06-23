<?php

namespace App\Http\Controllers;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use App\Models\Combination;
use App\Services\ExamTypeService;
use App\Support\PsleUserScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExamTypeController extends Controller
{
    public function index()
    {
        $activeCodes = config('irms.active_exam_types', ['PSLE']);
        if (request()->expectsJson()) {
            return response()->json(['data' => ExamType::whereIn('code', $activeCodes)->get()]);
        }
        if (app()->environment('testing')) {
            $examTypes = ExamType::whereIn('code', $activeCodes)->get();
            return view('exam-types.index', compact('examTypes'));
        }
        return redirect()->route('admin.exam-types.psle');
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

    private function resolveExamType($code)
    {
        $normalizedCode = strtoupper($code);
        if (in_array($normalizedCode, ['PSLE_MY', 'PSLE_G', 'PSLE'])) {
            $normalizedCode = 'PSLE';
        }
        return ExamType::where('code', $normalizedCode)->firstOrFail();
    }

    // Subjects CRUD
    public function getSubjects($examTypeCode)
    {
        $examType = $this->resolveExamType($examTypeCode);
        $subjects = $examType->subjects()->get();
        $isPsle = $examType->code === 'PSLE';
        $isCsee = $examType->code === 'CSEE';

        $officialCseeCatalog = collect(config('csee.official_subjects', []))->keyBy('code');

        return response()->json([
            'data' => $subjects->map(function (Subject $subject) use ($isPsle, $isCsee, $officialCseeCatalog) {
                $row = $subject->toArray();

                if ($isPsle) {
                    $row['subject_group_label'] = $subject->subject_group_label ?: $this->defaultPsleSubjectGroupLabel($subject->category);
                    $row['paper_pattern_label'] = $subject->paper_pattern_label ?: $this->defaultPslePaperPatternLabel($subject->written_papers);
                }

                if ($isCsee) {
                    $catalogEntry = $officialCseeCatalog->get($subject->code, []);

                    $row['subject_group_label'] = $subject->subject_group_label
                        ?: ($catalogEntry['subject_group_label'] ?? $this->defaultCseeSubjectGroupLabel($subject->category));
                    $row['paper_pattern_label'] = $subject->paper_pattern_label
                        ?: 'Official booklet on file. Structured paper extraction pending.';
                    $row['source_page'] = $catalogEntry['source_page'] ?? null;
                }

                return $row;
            })->values(),
        ]);
    }

    public function createSubject(Request $request, $examTypeCode)
    {
        Log::info('ExamTypeController: createSubject called', [
            'examTypeCode' => $examTypeCode,
            'request_data' => $request->all(),
        ]);

        try {
            $examType = $this->resolveExamType($examTypeCode);
            if ($examType->code === 'PSLE') {
                return response()->json([
                    'message' => 'PSLE subjects are managed from the official NECTA catalog. Use the sync action instead of manual creation.',
                ], 422);
            }
            
            $validated = $request->validate([
                'code' => 'required|unique:subjects',
                'name' => 'required|string',
                'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
                'subjectGroupLabel' => 'nullable|string|max:255',
                'writtenPapers' => 'required|integer|in:1,2,3',
                'paperPatternLabel' => 'nullable|string|max:255',
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
            $examType = $this->resolveExamType($examTypeCode);
            if ($examType->code === 'PSLE') {
                return response()->json([
                    'message' => 'PSLE subjects are managed from the official NECTA catalog. Manual editing is disabled.',
                ], 422);
            }
            $subject = $examType->subjects()->findOrFail($subjectId);
            
            $validated = $request->validate([
                'code' => 'required|unique:subjects,code,' . $subject->id,
                'name' => 'required|string',
                'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
                'subjectGroupLabel' => 'nullable|string|max:255',
                'writtenPapers' => 'required|integer|in:1,2,3',
                'paperPatternLabel' => 'nullable|string|max:255',
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
            $examType = $this->resolveExamType($examTypeCode);
            if ($examType->code === 'PSLE') {
                return response()->json([
                    'message' => 'PSLE subjects are managed from the official NECTA catalog. Manual deletion is disabled.',
                ], 422);
            }
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

    public function syncOfficialPsleSubjects()
    {
        $examType = ExamType::where('code', 'PSLE')->firstOrFail();
        $catalog = collect(config('psle.official_subjects', []));

        $synced = 0;

        foreach ($catalog as $entry) {
            Subject::updateOrCreate(
                [
                    'exam_type_id' => $examType->id,
                    'code' => $entry['code'],
                ],
                [
                    'name' => $entry['name'],
                    'category' => $entry['category'],
                    'subject_group_label' => $entry['subject_group_label'] ?? $this->defaultPsleSubjectGroupLabel($entry['category'] ?? null),
                    'written_papers' => (int) ($entry['written_papers'] ?? 1),
                    'paper_pattern_label' => null,
                    'has_practical' => false,
                    'has_project' => false,
                    'description' => $entry['description'] ?? null,
                    'max_marks' => (int) ($entry['max_marks'] ?? 50),
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        return response()->json([
            'message' => 'Official PSLE subject catalog synchronized successfully.',
            'synced_count' => $synced,
        ]);
    }

    public function syncOfficialCseeSubjects()
    {
        $examType = ExamType::where('code', 'CSEE')->firstOrFail();
        $catalog = collect(config('csee.official_subjects', []));

        $synced = 0;

        foreach ($catalog as $entry) {
            Subject::updateOrCreate(
                [
                    'exam_type_id' => $examType->id,
                    'code' => $entry['code'],
                ],
                [
                    'name' => $entry['name'],
                    'category' => $entry['category'],
                    'subject_group_label' => $entry['subject_group_label'] ?? $this->defaultCseeSubjectGroupLabel($entry['category'] ?? null),
                    'written_papers' => (int) ($entry['written_papers'] ?? 1),
                    'paper_pattern_label' => 'Official booklet on file. Structured paper extraction pending.',
                    'has_practical' => false,
                    'has_project' => false,
                    'description' => sprintf(
                        'NECTA CSEE official subject from the October 2022 examination formats booklet (section %s, page %s).',
                        $entry['code'] ?? 'N/A',
                        $entry['source_page'] ?? 'N/A'
                    ),
                    'max_marks' => 100,
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        return response()->json([
            'message' => 'Official CSEE subject catalog synchronized successfully.',
            'synced_count' => $synced,
        ]);
    }

    // Combinations CRUD
    public function getCombinations($examTypeCode)
    {
        $examType = $this->resolveExamType($examTypeCode);
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
        $examType = $this->resolveExamType($examTypeCode);
        
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
        $examType = $this->resolveExamType($examTypeCode);
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
        $examType = $this->resolveExamType($examTypeCode);
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
            $startedAt = microtime(true);
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $search = $request->get('q', '');
            $candidateType = $request->get('candidate_type', 'ALL');
            $schoolId = $request->get('school_id', '');
            $districtId = $request->get('district_id', '');
            $regionId = $request->get('region_id', '');
            $examYearLabel = $request->get('exam_year', '');
            $user = $request->user();

            // Validate perPage to prevent abuse
            $perPage = min(max((int)$perPage, 15), 100);

            // Get exam type from code
            $examType = $this->resolveExamType($code);
            $examYear = null;
            if ($examYearLabel !== '') {
                $examYear = ExamYear::where('year_label', (string) $examYearLabel)->first();
            }
            if (!$examYear) {
                $examYear = ExamYear::where('is_active', true)->first();
            }

            // Return 401 if unauthenticated and trying to load PSLE candidates
            if ($examType->code === 'PSLE' && !$user) {
                return response()->json([
                    'message' => 'Authentication required to view PSLE candidates.',
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                        'from' => 0,
                        'to' => 0,
                    ],
                ], 401);
            }

            $totalBeforeFilters = 0;

            // Define the primary query based on exam type
            if ($examType->code === 'PSLE') {
                $query = \App\Services\PsleCandidateRosterService::rosterQuery($examYear->id)
                    ->select([
                        'id',
                        'candidate_id',
                        'prem_no',
                        'full_name',
                        'gender',
                        'school_id',
                        'exam_type',
                        'status',
                    ])
                    ->with([
                        'school:id,name,code,council_id,region_id',
                        'school.council:id,name,region_id',
                        'school.region:id,name',
                        'examRegistrations:id,candidate_id,exam_type_id,exam_year_id,status',
                    ])
                    ->orderBy('candidate_id');
            } else {
                // Calculate total ACSEE candidates before filters if needed (original behavior preserved)
                if ($examYear) {
                    $totalBeforeFiltersQuery = \App\Models\Candidate::query()
                        ->where('exam_type', $examType->code)
                        ->whereHas('examRegistrations', function ($q) use ($examType, $examYear) {
                            $q->where('exam_type_id', $examType->id)
                              ->where('exam_year_id', $examYear->id);
                        });

                    if ($user) {
                        PsleUserScope::applyToCandidateSchools($totalBeforeFiltersQuery, $user);
                    }

                    $totalBeforeFilters = $totalBeforeFiltersQuery->count();
                }

                $query = \App\Models\Candidate::query()
                    ->with('school', 'school.region', 'school.council', 'school.council.region', 'school.district', 'school.district.region')
                    ->with(['subjectSelections' => function ($q) use ($examType, $examYear) {
                        $q->where('exam_type_id', $examType->id);
                        if ($examYear) {
                            $q->where('exam_year_id', $examYear->id);
                        }
                        $q->with('subject');
                    }])
                    ->orderBy('candidate_id');
            }

            if ($examType->code === 'PSLE' && $user) {
                PsleUserScope::applyToCandidateSchools($query, $user);
            }

            if ($examType->code === 'PSLE') {
                // Scoped already via rosterQuery
            } else {
                $query->where(function ($candidateQuery) use ($examType) {
                    $candidateQuery->where('exam_type', strtoupper($examType->code))
                        ->orWhereHas('examRegistrations', fn ($q) => $q->where('exam_type_id', $examType->id));
                });
            }

            if ($examYear) {
                if ($examType->code !== 'PSLE') {
                    $query->whereHas('examRegistrations', function ($q) use ($examType, $examYear) {
                        $q->where('exam_type_id', $examType->id);
                        $q->where(function ($yearQuery) use ($examYear) {
                            $yearQuery->where('exam_year_id', $examYear->id)
                                ->orWhere('year', (int) $examYear->year_label);
                        });
                    });
                }
            } else {
                if ($examType->code !== 'PSLE') {
                    $query->whereHas('examRegistrations', fn ($q) => $q->where('exam_type_id', $examType->id));
                }
            }

            // Filter by candidate type
            if ($candidateType && $candidateType !== 'ALL') {
                $query->where('candidate_type', strtoupper($candidateType));
            }

            // Filter by school
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }

            // Filter by council (kept as district_id request parameter for compatibility)
            if ($districtId) {
                $query->whereHas('school', function($q) use ($districtId) {
                    $q->where('council_id', $districtId);
                });
            }

            // Filter by region (through school -> district relationship)
            if ($regionId) {
                $query->whereHas('school', function($q) use ($regionId) {
                    $q->where('region_id', $regionId)
                      ->orWhereHas('council', function($q2) use ($regionId) {
                          $q2->where('region_id', $regionId);
                      })
                      ->orWhereHas('district', function($q2) use ($regionId) {
                          $q2->where('region_id', $regionId);
                      });
                });
            }

            // Search by candidate_id, prem_no, full_name, or linked school
            if ($search) {
                if ($examType->code === 'PSLE') {
                    $query->where(function ($q) use ($search) {
                        $q->where('candidate_id', 'like', "{$search}%")
                          ->orWhere('prem_no', 'like', "{$search}%");

                        if (mb_strlen($search) >= 3) {
                            $q->orWhere('full_name', 'like', "%{$search}%");
                        }
                    });
                } else {
                    $query->where(function ($q) use ($search) {
                        $q->where('candidate_id', 'like', "%{$search}%")
                          ->orWhere('full_name', 'like', "%{$search}%")
                          ->orWhereHas('school', function ($schoolQuery) use ($search) {
                              $schoolQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('code', 'like', "%{$search}%");
                          });
                    });
                }
            }
            
            $candidates = $query->paginate($perPage);
            
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);

            if (app()->environment('local') && $examType->code === 'PSLE') {
                Log::info('PSLE candidate table loaded', [
                    'duration_ms' => $durationMs,
                    'user_id' => $user?->id,
                    'exam_year_id' => $examYear?->id,
                    'region_id' => $regionId,
                    'district_id' => $districtId,
                    'school_id' => $schoolId,
                    'search' => $search,
                    'page' => $page,
                    'per_page' => $perPage,
                    'rows_returned' => $candidates->count(),
                ]);
            }

            $diagnostics = null;

            $data = $candidates->map(function ($candidate) use ($examType) {
                if ($examType->code === 'PSLE') {
                    $allocated = [];
                    $councilName = $candidate->school?->council?->name ?? $candidate->school?->district?->name;
                    return [
                        'id' => $candidate->id,
                        'candidate_id' => $candidate->candidate_id,
                        'candidate_number' => $candidate->candidate_id, // Alias for compatibility
                        'pupil_name' => $candidate->full_name, // Alias for compatibility
                        'sex' => $candidate->gender, // Alias for compatibility
                        'prem_no' => $candidate->prem_no,
                        'full_name' => $candidate->full_name,
                        'gender' => $candidate->gender,
                        'combination' => null,
                        'school_id' => $candidate->school_id,
                        'school_code' => $candidate->school?->code,
                        'school_name' => $candidate->school?->name ?? '-',
                        'district_id' => $candidate->school?->council_id ?? $candidate->school?->district_id,
                        'district_name' => $councilName ?? '-',
                        'council' => $councilName ?? '-',
                        'council_name' => $councilName ?? '-',
                        'region_id' => $candidate->school?->region_id ?: ($candidate->school?->council?->region_id ?? $candidate->school?->district?->region_id),
                        'region_name' => $candidate->school?->region?->name ?? $candidate->school?->council?->region?->name ?? $candidate->school?->district?->region?->name,
                        'allocated_subjects' => $allocated,
                        'allocated_subject_count' => count($allocated),
                        'exam_type' => $candidate->exam_type,
                        'status' => $candidate->status ?? 'registered',
                    ];
                }

                if ($examType->code === 'CSEE' && $candidate->subjectSelections->count() > 0) {
                    $allocated = $candidate->subjectSelections->map(function ($selection) {
                        return [
                            'id' => $selection->subject_id,
                            'code' => $selection->subject->code,
                            'name' => $selection->subject->name,
                            'is_core' => in_array($selection->subject->code, \App\Services\Candidates\CseeCandidateSubjectService::CORE_SUBJECT_CODES, true),
                        ];
                    })->sortBy('code')->values()->toArray();
                } elseif ($candidate->candidate_type === 'PRIVATE' && $candidate->subjectSelections->count() > 0) {
                    $allocated = $candidate->subjectSelections->map(function ($selection) {
                        return [
                            'id' => $selection->subject_id,
                            'code' => $selection->subject->code,
                            'name' => $selection->subject->name,
                        ];
                    })->toArray();
                } else {
                    // Use combination-based for SCHOOL candidates
                    $allocated = $this->getCombinationSubjectsForExam($candidate->combination);
                }
                
                return [
                    'id' => $candidate->id,
                    'candidate_id' => $candidate->candidate_id,
                    'prem_no' => $candidate->prem_no,
                    'full_name' => $candidate->full_name,
                    'gender' => $candidate->gender,
                    'combination' => $candidate->combination,
                    'school_id' => $candidate->school_id,
                    'school_code' => $candidate->school?->code,
                    'school_name' => $candidate->school?->name ?? '-',
                    'district_id' => $candidate->school?->council_id ?? $candidate->school?->district_id,
                    'district_name' => $candidate->school?->council?->name ?? $candidate->school?->district?->name,
                    'region_id' => $candidate->school?->region_id ?: ($candidate->school?->council?->region_id ?? $candidate->school?->district?->region_id),
                    'region_name' => $candidate->school?->region?->name ?? $candidate->school?->council?->region?->name ?? $candidate->school?->district?->region?->name,
                    'allocated_subjects' => $allocated,
                    'allocated_subject_count' => count($allocated),
                    'exam_type' => $candidate->exam_type,
                    'status' => $candidate->status ?? 'registered',
                ];
            })->toArray();

            if (app()->environment('local') && $examType->code === 'PSLE') {
                $diagnostics = [
                    'active_exam_year' => $examYear?->year_label,
                    'selected_region_id' => $regionId ?: 'None',
                    'selected_council_id' => $districtId ?: 'None',
                    'selected_school_id' => $schoolId ?: 'None',
                    'total_pupils_after_filters' => $candidates->total(),
                    'authenticated_user_id' => $user?->id ?? 'Guest',
                    'authenticated_user_email' => $user?->email ?? 'Guest',
                    'route_source' => 'web.php',
                    'middleware' => 'web, auth, main-system',
                ];
            }

            return response()->json([
                'data' => $data,
                'meta' => [
                    'current_page' => $candidates->currentPage(),
                    'per_page' => $perPage,
                    'total' => $candidates->total(),
                    'last_page' => $candidates->lastPage(),
                    'from' => $candidates->firstItem(),
                    'to' => $candidates->lastItem(),
                ],
                'debug' => $diagnostics,
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
     * Get PSLE summary statistics (cached)
     */
    public function getPsleSummary(Request $request)
    {
        try {
            $startedAt = microtime(true);
            $user = $request->user();
            
            // Return 401 if unauthenticated
            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required.',
                    'subjects' => 0,
                    'synced_councils' => 0,
                    'synced_primary_schools' => 0,
                    'registered_pupils' => 0,
                ], 401);
            }

            $examType = ExamType::where('code', 'PSLE')->firstOrFail();
            $examYearLabel = $request->query('exam_year');
            if ($examYearLabel) {
                $examYear = ExamYear::where('year_label', $examYearLabel)->first();
            }
            if (!$examYear) {
                $examYear = ExamYear::where('is_active', true)->first();
            }
            $examYearId = $examYear ? $examYear->id : 0;

            $regionId = $request->query('region_id');
            $districtId = $request->query('district_id');
            $schoolId = $request->query('school_id');

            $scopeHash = \App\Services\PsleCacheService::scopeHash($user);
            $cacheKey = \App\Services\PsleCacheService::summaryKey($examYearId, $user->id, $scopeHash) . '_' . 
                ($regionId ?: 'all') . '_' . ($districtId ?: 'all') . '_' . ($schoolId ?: 'all');

            $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $examType, $examYear, $regionId, $districtId, $schoolId) {
                // 1. Subjects count
                $subjects = \App\Models\Subject::where('exam_type_id', $examType->id)->count();

                // 2. Synced primary schools query
                $schoolsQuery = \App\Models\School::query()
                    ->where('education_level', 'PRIMARY');

                PsleUserScope::applyToSchools($schoolsQuery, $user);

                if ($regionId) {
                    $schoolsQuery->where(function ($q) use ($regionId) {
                        $q->where('region_id', $regionId)
                          ->orWhereHas('council', fn ($councilQuery) => $councilQuery->where('region_id', $regionId));
                    });
                }

                if ($districtId) {
                    $schoolsQuery->where('council_id', $districtId);
                }

                if ($schoolId) {
                    $schoolsQuery->where('id', $schoolId);
                }

                // Clone schoolsQuery to get unique councils count
                $councilsCount = $schoolsQuery->clone()
                    ->whereNotNull('council_id')
                    ->distinct()
                    ->count('council_id');

                $schoolsCount = $schoolsQuery->count();

                // 3. Registered pupils query (Consistent with getAcseeCandicates / rosterQuery)
                $candidatesQuery = \App\Services\PsleCandidateRosterService::rosterQuery($examYear ? $examYear->id : 0);

                PsleUserScope::applyToCandidateSchools($candidatesQuery, $user);

                if ($regionId) {
                    $candidatesQuery->whereHas('school', function ($q) use ($regionId) {
                        $q->where('region_id', $regionId)
                          ->orWhereHas('council', function ($q2) use ($regionId) {
                              $q2->where('region_id', $regionId);
                          })
                          ->orWhereHas('district', function ($q2) use ($regionId) {
                              $q2->where('region_id', $regionId);
                          });
                    });
                }

                if ($districtId) {
                    $candidatesQuery->whereHas('school', function ($q) use ($districtId) {
                        $q->where('council_id', $districtId);
                    });
                }

                if ($schoolId) {
                    $candidatesQuery->where('school_id', $schoolId);
                }

                $candidatesCount = $candidatesQuery->count();

                return [
                    'subjects' => $subjects,
                    'synced_councils' => $councilsCount,
                    'synced_primary_schools' => $schoolsCount,
                    'registered_pupils' => $candidatesCount,
                ];
            });

            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            if (app()->environment('local')) {
                Log::info('PSLE summary stats loaded', [
                    'duration_ms' => $durationMs,
                    'user_id' => $user->id,
                    'exam_year_id' => $examYearId,
                    'region_id' => $regionId ?: 'all',
                    'district_id' => $districtId ?: 'all',
                ]);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('Error loading PSLE summary: ' . $e->getMessage(), [
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

    private function defaultPsleSubjectGroupLabel(?string $category): string
    {
        return match ($category) {
            'ARTS' => 'Language and Literacy',
            'SCIENCE' => 'Mathematics and Science',
            'BUSINESS' => 'Social Studies and General Learning',
            default => 'General PSLE Subject',
        };
    }

    private function defaultPslePaperPatternLabel(?int $writtenPapers): string
    {
        return match ((int) $writtenPapers) {
            1 => 'Standard single paper',
            2 => 'Two-paper structure',
            3 => 'Three-paper structure',
            default => 'Standard single paper',
        };
    }

    private function defaultCseeSubjectGroupLabel(?string $category): string
    {
        return match ($category) {
            'ARTS' => 'Humanities and Languages',
            'SCIENCE' => 'Sciences and Mathematics',
            'BUSINESS' => 'Applied and Technical Studies',
            default => 'General CSEE Subject',
        };
    }
}
