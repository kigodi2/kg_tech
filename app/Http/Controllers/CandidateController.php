<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\ExamYear;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Services\ExamYear\ExamYearValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CandidateController extends Controller
{
    public function index()
    {
        $candidates = Candidate::with('school')->paginate(15);
        return view('candidates.index', compact('candidates'));
    }

    public function show(Candidate $candidate)
    {
        if (request()->expectsJson()) {
            return response()->json($candidate);
        }
        $candidate->load('school');
        return view('candidates.show', compact('candidate'));
    }

    public function create()
    {
        $schools = School::all();
        return view('candidates.create', compact('schools'));
    }

    /**
     * Store a new candidate and register for exam if ACSEE
     */
    public function store(Request $request)
    {
        // Validation: support both old API and new API
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'required|unique:candidates',
            'full_name' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'gender' => 'required|in:M,F',
            'date_of_birth' => 'nullable|date',
            'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
            'combination' => 'nullable|string',
            'exam_year' => 'nullable|string',  // Accept exam year (year_label or id)
        ]);

        // Authorization: Check if user can register candidates at this school
        $school = School::findOrFail($validated['school_id']);
        try {
            $this->authorize('registerForSchool', [\App\Models\Candidate::class, $school->id]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::warning('User attempted unauthorized candidate registration', [
                'user_id' => auth()->id(),
                'school_id' => $validated['school_id'],
            ]);

            // Log failed authorization
            \App\Models\GovernanceAuditLog::log(
                \App\Models\GovernanceAuditLog::ACTION_IMPORT_FAILED,
                userId: auth()->id(),
                adminId: null,
                data: [
                    'reason' => 'unauthorized_registration',
                    'school_id' => $validated['school_id'],
                    'user_scope' => auth()->user()?->getScopeId(),
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to register candidates at this school.',
                ], 403);
            }

            return redirect('/candidates')->with('error', 'You do not have permission to register candidates at this school.');
        }

        try {
            // Start transaction for data consistency
            DB::beginTransaction();

            // Prepare candidate data
            $candidateData = [
                'school_id' => $validated['school_id'],
                'candidate_id' => $validated['candidate_id'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
            ];

            // Support both full_name and first_name/last_name
            if (!empty($validated['full_name'])) {
                $candidateData['full_name'] = $validated['full_name'];
            } elseif (!empty($validated['first_name']) && !empty($validated['last_name'])) {
                $candidateData['full_name'] = $validated['first_name'] . ' ' . $validated['last_name'];
            }

            // Create candidate
             $candidate = Candidate::create($candidateData);

             // Register for ACSEE if specified
             if ($validated['exam_type'] === 'ACSEE') {
                 $this->registerForACSEE($candidate, $validated['combination'] ?? null, $validated['exam_year'] ?? null);
             }

            DB::commit();

            // Log successful candidate registration
            \App\Models\GovernanceAuditLog::log(
                \App\Models\GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                userId: auth()->id(),
                adminId: null,
                data: [
                    'event' => 'candidate_registration',
                    'candidate_id' => $candidate->id,
                    'school_id' => $validated['school_id'],
                    'exam_type' => $validated['exam_type'] ?? null,
                ]
            );

            // Return based on request type
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate registered successfully',
                    'data' => $candidate,
                ], 201);
            }

            return redirect('/candidates')->with('success', 'Candidate created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed candidate registration
            \App\Models\GovernanceAuditLog::log(
                \App\Models\GovernanceAuditLog::ACTION_IMPORT_FAILED,
                userId: auth()->id(),
                adminId: null,
                data: [
                    'event' => 'candidate_registration_failed',
                    'error' => $e->getMessage(),
                    'school_id' => $validated['school_id'] ?? null,
                ]
            );

            \Log::error('Error creating candidate:', [
                'candidate_id' => $validated['candidate_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error registering candidate: ' . $e->getMessage(),
                ], 400);
            }

            return redirect('/candidates')->with('error', 'Error creating candidate: ' . $e->getMessage());
        }
    }

    public function edit(Candidate $candidate)
    {
        $schools = School::all();
        return view('candidates.edit', compact('candidate', 'schools'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'required|unique:candidates,candidate_id,' . $candidate->id,
            'full_name' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'gender' => 'required|in:M,F',
            'date_of_birth' => 'nullable|date',
            'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
            'combination' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Prepare update data
            $updateData = [
                'school_id' => $validated['school_id'],
                'candidate_id' => $validated['candidate_id'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
            ];

            // Support both full_name and first_name/last_name
            if (!empty($validated['full_name'])) {
                $updateData['full_name'] = $validated['full_name'];
            } elseif (!empty($validated['first_name']) && !empty($validated['last_name'])) {
                $updateData['full_name'] = $validated['first_name'] . ' ' . $validated['last_name'];
            }

            $candidate->update($updateData);

            // If updating to ACSEE, register them
            if ($validated['exam_type'] === 'ACSEE' && !empty($validated['combination'])) {
                // Check if already registered
                $existingReg = CandidateExamRegistration::where('candidate_id', $candidate->id)
                    ->where('exam_type_id', ExamType::where('code', 'ACSEE')->first()?->id)
                    ->first();

                // Only register if not already registered
                if (!$existingReg) {
                    $this->registerForACSEE($candidate, $validated['combination']);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate updated successfully',
                    'data' => $candidate,
                ], 200);
            }

            return redirect('/candidates')->with('success', 'Candidate updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error updating candidate:', [
                'candidate_id' => $candidate->candidate_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating candidate: ' . $e->getMessage(),
                ], 400);
            }

            return redirect('/candidates')->with('error', 'Error updating candidate: ' . $e->getMessage());
        }
    }

    public function destroy(Candidate $candidate)
    {
        try {
            DB::beginTransaction();

            // Delete related exam registrations and subject selections
            CandidateExamRegistration::where('candidate_id', $candidate->id)->delete();
            CandidateSubjectSelection::where('candidate_id', $candidate->id)->delete();

            // Delete candidate
            $candidate->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate deleted successfully',
                ], 200);
            }

            return redirect('/candidates')->with('success', 'Candidate deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error deleting candidate:', [
                'candidate_id' => $candidate->candidate_id,
                'error' => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting candidate: ' . $e->getMessage(),
                ], 400);
            }

            return redirect('/candidates')->with('error', 'Error deleting candidate: ' . $e->getMessage());
        }
    }

    /**
     * Register a candidate for ACSEE exam
     *
     * Creates:
     * 1. candidate_exam_registrations record
     * 2. candidate_subject_selections records (one per subject in combination)
     *
     * IMPORTANT: exam_year_id is now MANDATORY (enforces year isolation)
     *
     * @param Candidate $candidate
     * @param string|null $combination
     * @param ExamYear|int|null $examYear - If null, uses current active year
     * @throws \Exception
     */
    private function registerForACSEE(Candidate $candidate, ?string $combination, $examYear = null): void
    {
        // Validate combination provided
        if (empty($combination)) {
            throw new \Exception('Combination is required for ACSEE candidates');
        }

        // Get ACSEE exam type
        $examType = ExamType::where('code', 'ACSEE')->first();

        if (!$examType) {
            throw new \Exception('ACSEE exam type not found in database. Please create it first.');
        }

        // Resolve exam year
        if ($examYear === null) {
            // Use current active year (fallback)
            $examYear = ExamYear::active()->first();
            if (!$examYear) {
                throw new \Exception('No active exam year found. Please set an active exam year.');
            }
        } elseif (is_int($examYear)) {
            // Look up by ID
            $examYear = ExamYear::find($examYear);
            if (!$examYear) {
                throw new \Exception('Invalid exam year ID provided');
            }
        } elseif (is_string($examYear)) {
            // Look up by year_label (e.g., "2026")
            $examYear = ExamYear::where('year_label', $examYear)->first();
            if (!$examYear) {
                throw new \Exception('Exam year not found: ' . $examYear);
            }
        } else {
            throw new \Exception('Invalid exam year type provided');
        }

        // Validate candidate can register for this year
        $yearValidation = app(ExamYearValidationService::class)->validateCandidateRegistration($candidate, $examYear);
        if (!$yearValidation['valid']) {
            throw new \Exception($yearValidation['message']);
        }

        // Check if already registered for this year
        $existingReg = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->first();

        if ($existingReg) {
            \Log::info("Candidate already registered for ACSEE: {$candidate->candidate_id} in year {$examYear->year_label}");
            return;
        }

        // Create exam registration with exam_year_id FK
        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => (int)$examYear->year_label, // Keep for backward compatibility
            'registration_number' => 'REG-' . uniqid(),
            'is_active' => true,
            'is_verified' => false,
        ]);

        // Parse combination and register subjects
        $subjects = $this->parseAndFindSubjects($combination, $examType->id);

        foreach ($subjects as $subject) {
            // Check if already selected
            $existingSelection = CandidateSubjectSelection::where('candidate_id', $candidate->id)
                ->where('subject_id', $subject->id)
                ->where('exam_type_id', $examType->id)
                ->where('exam_year_id', $examYear->id)
                ->first();

            // Only create if not already exists
            if (!$existingSelection) {
                CandidateSubjectSelection::create([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subject->id,
                    'year' => (int)$examYear->year_label, // Keep for backward compatibility
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Parse combination string and find matching subjects
     *
     * Supports:
     * - "PCM" (short codes)
     * - "Physics,Chemistry,Math" (full names)
     * - "PHY,CHE,MAT" (codes)
     * - "Physics,CHE,Math" (mixed)
     *
     * @param string $combination
     * @param int $examTypeId
     * @return Collection
     * @throws \Exception
     */
    private function parseAndFindSubjects(string $combination, int $examTypeId): Collection
    {
        // Remove spaces and split by comma
        $parts = array_map('trim', explode(',', $combination));

        // Remove empty parts
        $parts = array_filter($parts, fn($p) => !empty($p));

        if (empty($parts)) {
            throw new \Exception('Invalid combination format');
        }

        // Search for subjects by code or name
        $subjects = Subject::where('exam_type_id', $examTypeId)
            ->where(function ($query) use ($parts) {
                foreach ($parts as $part) {
                    // Match by code (case-insensitive) or name (contains)
                    $query->orWhere(DB::raw('UPPER(code)'), '=', strtoupper($part))
                          ->orWhere(DB::raw('UPPER(name)'), 'LIKE', '%' . strtoupper($part) . '%');
                }
            })
            ->get();

        if ($subjects->isEmpty()) {
            $availableSubjects = Subject::where('exam_type_id', $examTypeId)
                ->pluck('code')
                ->implode(', ');
            throw new \Exception("No subjects found for combination: '$combination'. Available subjects: $availableSubjects");
        }

        return $subjects;
    }
}
