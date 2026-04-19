<?php

namespace App\Http\Controllers;

use App\Models\ExamSubmission;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamFormatValidation\ExamFormatValidator;
use App\Services\ExamFormatValidation\ExamTypeFinalReportBuilder;
use App\Services\ExamFormatValidation\NectaFormatRulebook;
use App\Mail\ExamSubmissionApproved;
use App\Mail\ExamSubmissionRejected;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ExamSubmissionController extends Controller
{
    protected ExamFormatValidator $formatValidator;
    protected ExamTypeFinalReportBuilder $finalReportBuilder;
    protected NectaFormatRulebook $rulebook;

    public function __construct(
        ExamFormatValidator $formatValidator,
        ExamTypeFinalReportBuilder $finalReportBuilder,
        NectaFormatRulebook $rulebook
    )
    {
        $this->formatValidator = $formatValidator;
        $this->finalReportBuilder = $finalReportBuilder;
        $this->rulebook = $rulebook;
    }

    /**
     * Display the exam submission form
     */
    public function create()
    {
        $examTypes = collect([
            [
                'code' => 'ACSEE',
                'name' => 'Advanced Certificate of Secondary Education Examination',
                'description' => 'NECTA A-Level examination format validation',
                'education_level' => 'SECONDARY',
            ],
            [
                'code' => 'CSEE',
                'name' => 'Certificate of Secondary Education Examination',
                'description' => 'NECTA O-Level examination format validation',
                'education_level' => 'SECONDARY',
            ],
            [
                'code' => 'FTNA',
                'name' => 'Form Two National Assessment',
                'description' => 'NECTA FTNA examination format validation',
                'education_level' => 'SECONDARY',
            ],
        ])->map(function (array $definition) {
            return ExamType::firstOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'education_level' => $definition['education_level'],
                    'is_active' => true,
                ]
            );
        })->sortBy('name')->values();

        if (ExamYear::count() === 0) {
            ExamYear::create([
                'year_label' => (string) now()->year,
                'is_active' => true,
                'is_locked' => false,
            ]);
        }

        $examYears = ExamYear::orderByDesc('year_label')->get();
        $maxUploadMegabytes = min(10, $this->getPhpUploadLimitInMegabytes());

        return view('exam-submissions.create', compact('examTypes', 'examYears', 'maxUploadMegabytes'));
    }

    /**
     * Store a new exam submission
     */
    public function store(Request $request): JsonResponse
    {
        if ($uploadError = $this->getUploadErrorMessage($request, 'exam_paper')) {
            return response()->json([
                'success' => false,
                'message' => $uploadError,
            ], 422);
        }

        $request->validate([
            'exam_type_id' => 'required|exists:exam_types,id',
            'exam_year_id' => 'required|exists:exam_years,id',
            'subject_id' => 'required|exists:subjects,id',
            'school_id' => 'nullable|exists:schools,id',
            'exam_paper' => 'required|file|mimes:pdf,docx|max:10240', // 10MB max
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('exam_paper');
            $examType = ExamType::findOrFail($request->exam_type_id);
            $subject = Subject::findOrFail($request->subject_id);

            // Validate the uploaded document format
            $validationResults = $this->formatValidator->validateExamFormat($file, $examType->code, $subject->code);

            // Store the file
            $path = $file->store('exam-submissions', 'public');

            // Create the submission record - all submissions require admin review
            $submission = ExamSubmission::create([
                'user_id' => auth()->id(),
                'exam_type_id' => $request->exam_type_id,
                'exam_year_id' => $request->exam_year_id,
                'subject_id' => $request->subject_id,
                'school_id' => $request->school_id,
                'exam_paper_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'status' => 'pending',
                'validation_results' => $validationResults,
                'rejection_reason' => null,
                'submitted_at' => now(),
                'validated_at' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'submission' => $submission,
                'validation_results' => $validationResults,
                'message' => 'Exam submitted successfully and is pending admin review. Validation results are available on the submission details page.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit exam: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subjects for a specific exam type
     */
    public function getSubjects($exam_type_id): JsonResponse
    {
        $examType = ExamType::findOrFail($exam_type_id);
        $this->syncOfficialSubjectsForExamType($examType);

        $subjects = Subject::where('exam_type_id', $exam_type_id)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json($subjects);
    }

    protected function syncOfficialSubjectsForExamType(ExamType $examType): void
    {
        $officialSubjects = $this->rulebook->getOfficialSubjects($examType->code);

        foreach ($officialSubjects as $subjectDefinition) {
            $code = strtoupper((string) ($subjectDefinition['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            Subject::updateOrCreate(
                [
                    'exam_type_id' => $examType->id,
                    'code' => $code,
                ],
                [
                    'name' => (string) ($subjectDefinition['name'] ?? $code),
                    'category' => (string) ($subjectDefinition['category'] ?? 'SCIENCE'),
                    'subject_group_label' => $subjectDefinition['subject_group_label'] ?? 'Official Format Subjects',
                    'written_papers' => (int) ($subjectDefinition['written_papers'] ?? 1),
                    'paper_pattern_label' => $subjectDefinition['paper_pattern_label'] ?? 'NECTA Official Format',
                    'has_practical' => (bool) ($subjectDefinition['has_practical'] ?? false),
                    'has_project' => (bool) ($subjectDefinition['has_project'] ?? false),
                    'max_marks' => (int) ($subjectDefinition['max_marks'] ?? 100),
                    'description' => 'Sourced from official NECTA format catalog for the exam submission validation module.',
                    'is_active' => true,
                ]
            );
        }
    }

    protected function getPhpUploadLimitInMegabytes(): int
    {
        $uploadMax = $this->convertPhpSizeToBytes((string) ini_get('upload_max_filesize'));
        $postMax = $this->convertPhpSizeToBytes((string) ini_get('post_max_size'));
        $effective = min($uploadMax, $postMax);

        if ($effective <= 0) {
            return 10;
        }

        return max(1, (int) floor($effective / 1024 / 1024));
    }

    protected function convertPhpSizeToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    protected function getUploadErrorMessage(Request $request, string $field): ?string
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            if ($file && $file->isValid()) {
                return null;
            }

            $errorCode = $file?->getError();
        } else {
            $errorCode = $_FILES[$field]['error'] ?? null;
        }

        return match ($errorCode) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE =>
                'Upload failed: the document exceeds the current server upload limit of ' . $this->getPhpUploadLimitInMegabytes() . ' MB. Increase PHP upload_max_filesize/post_max_size or upload a smaller file.',
            \UPLOAD_ERR_PARTIAL =>
                'Upload failed: the document was only partially uploaded. Please try again.',
            \UPLOAD_ERR_NO_FILE, null =>
                null,
            default =>
                'Upload failed: the exam paper could not be received by the server.',
        };
    }

    /**
     * Show submission status
     */
    public function show(ExamSubmission $examSubmission)
    {
        $this->authorize('view', $examSubmission);
        $submission = $examSubmission;

        return view('exam-submissions.show', compact('submission'));
    }

    /**
     * List user's submissions
     */
    public function index(Request $request)
    {
        $scopeQuery = ExamSubmission::query();
        $isAdmin = auth()->user()->isAdmin();

        if (! $isAdmin) {
            $scopeQuery->where('user_id', auth()->id());
        }

        $query = (clone $scopeQuery)
            ->with(['examType', 'examYear', 'subject', 'school', 'user'])
            ->orderBy('submitted_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('exam_type_id')) {
            $query->where('exam_type_id', $request->integer('exam_type_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        if ($request->filled('exam_year_id')) {
            $query->where('exam_year_id', $request->integer('exam_year_id'));
        }

        $submissions = $query->paginate(20);

        $availableExamTypeIds = (clone $scopeQuery)->distinct()->pluck('exam_type_id')->filter()->all();
        $availableSubjectIds = (clone $scopeQuery)->distinct()->pluck('subject_id')->filter()->all();
        $availableExamYearIds = (clone $scopeQuery)->distinct()->pluck('exam_year_id')->filter()->all();

        $filterExamTypes = ExamType::query()
            ->whereIn('id', $availableExamTypeIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $filterSubjects = Subject::query()
            ->whereIn('id', $availableSubjectIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $filterExamYears = ExamYear::query()
            ->whereIn('id', $availableExamYearIds)
            ->orderByDesc('year_label')
            ->get(['id', 'year_label']);

        $reportExamTypes = ExamType::whereIn('code', ['ACSEE', 'CSEE', 'FTNA'])->orderBy('name')->get();
        $reportExamYears = ExamYear::orderByDesc('year_label')->get();
        $reportUsers = $isAdmin
            ? User::query()
                ->whereIn('id', ExamSubmission::query()->select('user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        return view('exam-submissions.index', compact(
            'submissions',
            'filterExamTypes',
            'filterSubjects',
            'filterExamYears',
            'reportExamTypes',
            'reportExamYears',
            'reportUsers'
        ));
    }

    /**
     * Generate a consolidated final report for one examination activity.
     */
    public function finalReport(Request $request)
    {
        $validated = $request->validate([
            'exam_type_id' => 'required|exists:exam_types,id',
            'exam_year_id' => 'required|exists:exam_years,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $examType = ExamType::findOrFail($validated['exam_type_id']);
        $examYear = ExamYear::findOrFail($validated['exam_year_id']);

        if (auth()->user()->isAdmin()) {
            if (empty($validated['user_id'])) {
                return redirect()
                    ->route('exam-submissions.index')
                    ->with('error', 'Select the submitting account before generating the final report.');
            }

            $submitter = User::findOrFail($validated['user_id']);
        } else {
            $submitter = auth()->user();
        }

        $report = $this->finalReportBuilder->build($examType, $examYear, $submitter);

        return view('exam-submissions.final-report', compact('report'));
    }

    /**
     * Approve submission (admin only)
     */
    public function approve(ExamSubmission $examSubmission)
    {
        $this->authorize('review', $examSubmission);
        $submission = $examSubmission;

        $submission->update([
            'status' => 'approved',
            'admin_id' => auth()->id(),
            'rejection_reason' => null,
            'validated_at' => now(),
        ]);

        // Send approval email if user is available
        if ($submission->user && filter_var($submission->user->email, FILTER_VALIDATE_EMAIL)) {
            Mail::to($submission->user->email)->send(new ExamSubmissionApproved($submission));
        }

        return redirect()->back()->with('success', 'Exam submission approved successfully.');
    }

    /**
     * Reject submission (admin only)
     */
    public function reject(Request $request, ExamSubmission $examSubmission)
    {
        $this->authorize('review', $examSubmission);
        $submission = $examSubmission;

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $submission->update([
            'status' => 'rejected',
            'admin_id' => auth()->id(),
            'rejection_reason' => $request->input('rejection_reason'),
            'validated_at' => now(),
        ]);

        // Send rejection email if user is available
        if ($submission->user && filter_var($submission->user->email, FILTER_VALIDATE_EMAIL)) {
            Mail::to($submission->user->email)->send(new ExamSubmissionRejected($submission));
        }

        return redirect()->back()->with('success', 'Exam submission rejected successfully.');
    }

    /**
     * Download submitted exam paper
     */
    public function download(ExamSubmission $examSubmission)
    {
        $this->authorize('view', $examSubmission);
        $submission = $examSubmission;

        if (!Storage::disk('public')->exists($submission->exam_paper_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $submission->exam_paper_path,
            $submission->original_filename
        );
    }

    /**
     * Validate an exam format without storing
     */
    public function validateFormat(Request $request): JsonResponse
    {
        if ($uploadError = $this->getUploadErrorMessage($request, 'exam_paper')) {
            return response()->json([
                'success' => false,
                'message' => $uploadError,
            ], 422);
        }

        $request->validate([
            'exam_paper' => 'required|file|mimes:pdf,docx|max:10240',
            'exam_type' => 'required|string|in:ACSEE,CSEE,FTNA',
            'subject_id' => 'nullable|exists:subjects,id',
            'subject_code' => 'nullable|string|max:10',
        ]);

        try {
            $file = $request->file('exam_paper');
            $subjectCode = null;

            if ($request->filled('subject_id')) {
                $subjectCode = Subject::find($request->integer('subject_id'))?->code;
            } elseif ($request->filled('subject_code')) {
                $subjectCode = strtoupper(trim((string) $request->input('subject_code')));
            }

            $validationResults = $this->formatValidator->validateExamFormat($file, $request->exam_type, $subjectCode);

            return response()->json([
                'success' => true,
                'validation_results' => $validationResults
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
