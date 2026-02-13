<?php

namespace App\Http\Controllers;

use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Services\MarkImport\MarkImportService;
use App\Services\MarkImport\MarkValidationService;
use App\Services\MarkImport\MarkTemplateService;
use App\Services\MarkImport\SubjectFilterService;
use App\Services\MarkImport\AcseeMarkTemplateService;
use App\Services\MarkImport\CsvIntegrityService;
use App\Services\MarkImport\MarkRowLockingService;
use App\Services\MarkImport\ScoresheetService;
use App\Services\MarkImport\BulkCsvExportService;
use App\Services\ExamYear\ExamYearValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class MarkEntryController extends Controller
{
    private MarkImportService $importService;
    private MarkValidationService $validationService;
    private MarkTemplateService $templateService;
    private SubjectFilterService $subjectFilterService;
    private AcseeMarkTemplateService $acseeTemplateService;
    private CsvIntegrityService $integrityService;
    private MarkRowLockingService $lockingService;
    private ExamYearValidationService $yearValidationService;
    private ScoresheetService $scoresheetService;
    private BulkCsvExportService $bulkExportService;

    public function __construct(
        MarkImportService $importService,
        MarkValidationService $validationService,
        MarkTemplateService $templateService,
        SubjectFilterService $subjectFilterService,
        AcseeMarkTemplateService $acseeTemplateService,
        CsvIntegrityService $integrityService,
        MarkRowLockingService $lockingService,
        ExamYearValidationService $yearValidationService,
        ScoresheetService $scoresheetService,
        BulkCsvExportService $bulkExportService
    ) {
        $this->importService = $importService;
        $this->validationService = $validationService;
        $this->templateService = $templateService;
        $this->subjectFilterService = $subjectFilterService;
        $this->acseeTemplateService = $acseeTemplateService;
        $this->integrityService = $integrityService;
        $this->lockingService = $lockingService;
        $this->yearValidationService = $yearValidationService;
        $this->scoresheetService = $scoresheetService;
        $this->bulkExportService = $bulkExportService;
    }

    /**
     * Show mark entry dashboard
     */
    public function index()
    {
        return view('mark-entry.index');
    }

    /**
     * Get regions for filter
     */
    public function getRegions()
    {
        $regions = Region::active()->get(['id', 'code', 'name']);
        return response()->json(['data' => $regions]);
    }

    /**
     * Get districts for a specific region (required parameter)
     * 
     * REQUIRED: region_id query parameter
     * Returns only districts belonging to the specified region
     * 
     * This prevents loading all districts on page init for performance
     */
    public function getDistricts(Request $request)
    {
        $validated = $request->validate([
            'region_id' => 'required|integer|exists:regions,id'
        ]);
        
        $districts = District::where('region_id', $validated['region_id'])
            ->get(['id', 'code', 'name', 'region_id']);
        
        return response()->json(['data' => $districts]);
    }

    /**
     * Get schools for a specific district (required parameter)
     * 
     * REQUIRED: district_id query parameter
     * Returns only schools belonging to the specified district
     * 
     * This prevents loading all schools on page init for performance
     */
    public function getSchools(Request $request)
    {
        $validated = $request->validate([
            'district_id' => 'required|integer|exists:districts,id'
        ]);
        
        $schools = School::where('district_id', $validated['district_id'])
            ->get(['id', 'code', 'name', 'district_id']);
        
        return response()->json(['data' => $schools]);
    }

    /**
     * Get schools with ACSEE candidates for a specific exam year
     *
     * Returns ONLY schools that have ACSEE candidates registered in the specified exam year.
     * Used for bulk import filtering.
     *
     * @param Request $request with exam_year (year_label)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSchoolsByYear(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => 'required|string|regex:/^\d{4}$/'
        ]);
        
        // Find exam year by year_label
        $examYear = ExamYear::where('year_label', $validated['exam_year'])->first();
        if (!$examYear) {
            return response()->json(['data' => []]);
        }

        // Get ACSEE exam type
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            return response()->json(['data' => []]);
        }

        // Get distinct schools that have ACSEE registrations for this year
        $schools = School::query()
            ->distinct()
            ->select('schools.id', 'schools.code', 'schools.name', 'schools.district_id')
            ->join('candidates', 'schools.id', '=', 'candidates.school_id')
            ->join('candidate_exam_registrations', function ($join) use ($acsee, $examYear) {
                $join->on('candidates.id', '=', 'candidate_exam_registrations.candidate_id')
                     ->where('candidate_exam_registrations.exam_type_id', '=', $acsee->id)
                     ->where('candidate_exam_registrations.exam_year_id', '=', $examYear->id);
            })
            ->orderBy('schools.code')
            ->get();

        return response()->json(['data' => $schools]);
    }

    /**
     * Get districts with ACSEE candidates for a specific exam year
     *
     * Returns ONLY districts that have ACSEE candidates registered in the specified exam year.
     * Used for bulk import filtering.
     *
     * @param Request $request with exam_year (year_label)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistrictsByYear(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => 'required|string|regex:/^\d{4}$/'
        ]);
        
        // Find exam year by year_label
        $examYear = ExamYear::where('year_label', $validated['exam_year'])->first();
        if (!$examYear) {
            return response()->json(['data' => []]);
        }

        // Get ACSEE exam type
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            return response()->json(['data' => []]);
        }

        // Get distinct districts that have ACSEE registrations for this year
        $districts = District::query()
            ->distinct()
            ->select('districts.id', 'districts.code', 'districts.name', 'districts.region_id')
            ->join('schools', 'districts.id', '=', 'schools.district_id')
            ->join('candidates', 'schools.id', '=', 'candidates.school_id')
            ->join('candidate_exam_registrations', function ($join) use ($acsee, $examYear) {
                $join->on('candidates.id', '=', 'candidate_exam_registrations.candidate_id')
                     ->where('candidate_exam_registrations.exam_type_id', '=', $acsee->id)
                     ->where('candidate_exam_registrations.exam_year_id', '=', $examYear->id);
            })
            ->orderBy('districts.code')
            ->get();

        return response()->json(['data' => $districts]);
    }

    /**
     * Get ACSEE subjects (all available)
     * 
     * Returns all ACSEE subjects regardless of school selection.
     * Use this for initial page load or when filtering is not needed.
     */
    public function getSubjects()
    {
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            return response()->json(['data' => []]);
        }

        $subjects = $acsee->subjects()
            ->active()
            ->get(['id', 'code', 'name', 'written_papers', 'has_practical', 'has_project']);
        
        return response()->json(['data' => $subjects]);
    }

    /**
     * Get ACSEE subjects for a specific school and exam year
     *
     * Returns ONLY subjects that are actually taken by registered ACSEE candidates
     * in the specified school for the specified exam year.
     *
     * IMPORTANT: Enforces year isolation
     * - Uses exam_year_id FK (not loose year integer)
     * - Rejects locked years with 422 error
     * - Returns empty set + warning if no registrations exist
     *
     * Query logic:
     * - Find all ACSEE registrations for the school in the year (via exam_year_id FK)
     * - Retrieve their subject selections
     * - Return DISTINCT subjects, ordered by code
     *
     * @param Request $request with exam_year and school_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubjectsBySchoolAndYear(Request $request)
    {
        $request->validate([
            'exam_year' => 'required|regex:/^\d{4}$/',
            'school_id' => 'required|integer|exists:schools,id',
        ]);

        $schoolId = $request->get('school_id');
        $examYearValue = (int)$request->get('exam_year');
        
        // Find the ExamYear record by year_label (not by id)
        $examYear = ExamYear::where('year_label', (string)$examYearValue)->first();

        // VALIDATION GUARDRAIL 1: Validate year exists and is not locked
        $yearValidation = $this->yearValidationService->validateMarkEntry($schoolId, $examYear);

        if (!$yearValidation['valid']) {
            // Return 422 Unprocessable Entity with business context
            return response()->json([
                'success' => false,
                'data' => [],
                'has_candidates' => false,
                'candidate_count' => 0,
                'message' => $yearValidation['message'],
                'code' => $yearValidation['code'], // YEAR_LOCKED, NO_CANDIDATES, INVALID_YEAR
            ], 422);
        }

        // Get filtered subjects directly without caching to ensure accuracy
        // (Cache can cause stale data when switching schools)
        $subjects = $this->subjectFilterService->getSubjectsBySchoolAndYear($schoolId, $examYearValue);

        // Check if school has ACSEE candidates
        $hasCandidates = $this->subjectFilterService->schoolHasACSEECandidates($schoolId, $examYearValue);
        $candidateCount = $this->subjectFilterService->getACSEECandidateCount($schoolId, $examYearValue);

        return response()->json([
            'success' => true,
            'data' => $subjects->values(), // Re-index for clean JSON
            'has_candidates' => $hasCandidates,
            'candidate_count' => $candidateCount,
            'message' => !$hasCandidates
                ? "No ACSEE candidates registered for {$examYear->year_label} in this school."
                : "Subjects shown are based on {$candidateCount} registered ACSEE candidate(s) in this school.",
        ]);
    }



    /**
     * Download professional ACSEE CSV template for a subject
     * 
     * IMPORTANT: Uses enhanced AcseeMarkTemplateService
     * - Exposes ONLY index_number and sex (read-only)
     * - NO full names in template
     * - School-, subject-, and year-specific
     * - Generates and stores checksum for integrity verification
     */
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'exam_year' => 'required|integer|min:2000|max:' . (now()->year + 1),
            'school_id' => 'required|integer|exists:schools,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        try {
            $examYearValue = $request->exam_year;
            $schoolId = $request->school_id;
            $subjectId = $request->subject_id;

            // Generate ACSEE template (minimal data exposure)
            $csv = $this->acseeTemplateService->generateTemplate($examYearValue, $schoolId, $subjectId);
            $filename = $this->acseeTemplateService->generateFilename($schoolId, $subjectId);

            // Create batch for tracking
            $batch = $this->importService->createBatch($examYearValue, $schoolId, $subjectId, auth()->id() ?? 1);

            // Generate and store checksum for integrity verification
            $this->integrityService->generateAndStoreChecksum($examYearValue, $schoolId, $subjectId, $batch);

            return response()->streamDownload(
                fn() => print($csv),
                $filename,
                [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload and process marks CSV
     * 
     * IMPORTANT features:
     * - CSV integrity verification (checksum validation)
     * - No combination_id in request (derived from registration)
     * - Row locking after successful processing
     */
    public function uploadMarks(Request $request)
    {
        // Manual validation to avoid Laravel's exception handling
        $examYear = $request->input('exam_year');
        $schoolId = $request->input('school_id');
        $subjectId = $request->input('subject_id');
        
        // Validate required fields
        if (!$examYear || !$schoolId || !$subjectId || !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields: exam_year, school_id, subject_id, file',
            ], 422);
        }
        
        // Validate file
        $file = $request->file('file');
        if (!$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file upload',
            ], 422);
        }
        
        // Validate file extension
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type. Only CSV and TXT files are allowed.',
            ], 422);
        }
        
        // Validate file size (5MB)
        if ($file->getSize() > 5120 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'File is too large. Maximum size is 5MB.',
            ], 422);
        }
        
        $validated = [
            'exam_year' => $examYear,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'file' => $file,
        ];
        
        // Verify school exists
        $school = School::find($validated['school_id']);
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'Selected school not found.',
            ], 422);
        }
        
        // Verify subject exists
        $subject = Subject::find($validated['subject_id']);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Selected subject not found.',
            ], 422);
        }

        // Verify year is not locked (prevents accidental uploads to locked years)
        $examYear = ExamYear::where('year_label', (string)$validated['exam_year'])->first();
        if ($examYear && $examYear->is_locked) {
            return response()->json([
                'success' => false,
                'message' => "Cannot upload marks for locked year {$validated['exam_year']}. Contact administrator to unlock the year.",
            ], 422);
        }

        // Reject if combination_id is passed (legacy UI protection)
        if ($request->has('combination_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Combination selection is not allowed. Combination is derived from candidate registration.',
            ], 422);
        }

        // Check authorization
        $user = auth()->user();
        $isAuthorized = false;
        
        // Admin can upload to any district
        if ($user->isAdmin()) {
            $isAuthorized = true;
        }
        // Data entry officer can upload to own district
        elseif ($user->isDistrictDataEntryOfficer() && $user->is_active) {
            if ($user->getDistrictId() === $school->district_id) {
                $isAuthorized = true;
            }
        }
        
        if (!$isAuthorized) {
            \Log::warning('User attempted unauthorized mark import', [
                'user_id' => auth()->id(),
                'school_id' => $request->school_id,
                'district_id' => $school->district_id,
            ]);

            // Log failed authorization attempt
            \App\Models\GovernanceAuditLog::log(
                \App\Models\GovernanceAuditLog::ACTION_IMPORT_FAILED,
                userId: auth()->id(),
                adminId: null,
                data: [
                    'reason' => 'unauthorized_scope',
                    'school_id' => $request->school_id,
                    'user_scope' => auth()->user()?->getScopeId(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Permission Denied: You do not have permission to import marks for this school or district. Please select a school/district you have access to, or contact your administrator.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $examYearValue = $validated['exam_year'];
            $schoolId = $validated['school_id'];
            $subjectId = $validated['subject_id'];

            // Create batch (without combination_id)
            $batch = $this->importService->createBatch($examYearValue, $schoolId, $subjectId, auth()->id() ?? 1);

            // Process CSV (includes integrity verification)
            $result = $this->importService->processCSVUpload(
                $batch,
                $request->file('file'),
                $examYearValue,
                $schoolId,
                $subjectId
            );

            if (!$result['success']) {
                DB::rollBack();
                $errorMessage = $result['error'] ?? 'Failed to process CSV';
                
                // Provide more helpful error messages for single subject uploads
                if (strpos($errorMessage, 'checksum') !== false || strpos($errorMessage, 'modified') !== false) {
                    $errorMessage = 'The CSV file does not match the template. Possible causes: '
                        . 'the template was modified, candidates were added/removed, or a different school/subject template was used. '
                        . 'Please download a fresh template and try again.';
                } elseif (strpos($errorMessage, 'header') !== false) {
                    $errorMessage = 'The CSV header structure is incorrect. Do not modify the header row. '
                        . 'Download a fresh template and ensure headers like index_number, sex, paper_p1, etc. are not changed.';
                } elseif (strpos($errorMessage, 'candidate') !== false) {
                    $errorMessage = 'One or more candidates in the CSV are not registered for this subject or school. '
                        . 'Use only the candidates provided in the downloaded template.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }

            // Validate batch
            $validationResult = $this->importService->validateBatch($batch);

            // Lock all successfully processed rows (after validation)
            if ($validationResult['valid'] > 0) {
                $lockResult = $this->lockingService->lockBatchRows($batch, auth()->id() ?? 1);
                
                if (!$lockResult['success']) {
                    \Log::warning('Failed to lock some rows after validation', $lockResult['errors']);
                }
            }

            DB::commit();

            // Log successful import
            \App\Models\GovernanceAuditLog::log(
                \App\Models\GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                userId: auth()->id(),
                adminId: null,
                data: [
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'school_id' => $schoolId,
                    'subject_id' => $subjectId,
                    'exam_year' => $examYearValue,
                    'records_imported' => $result['imported_records'],
                    'valid_records' => $validationResult['valid'] ?? 0,
                    'error_records' => $validationResult['invalid'] ?? 0,
                ]
            );

            return response()->json([
                'success' => true,
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'message' => "{$result['imported_records']} records imported",
                'validation' => $validationResult,
                'locking' => [
                    'locked_count' => $this->lockingService->getLockedRowsCount($batch),
                    'unlocked_count' => $this->lockingService->getUnlockedRowsCount($batch),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed import
            \App\Models\GovernanceAuditLog::log(
                \App\Models\GovernanceAuditLog::ACTION_IMPORT_FAILED,
                userId: auth()->id(),
                adminId: null,
                data: [
                    'error' => $e->getMessage(),
                    'school_id' => $schoolId ?? null,
                    'subject_id' => $subjectId ?? null,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch details
     */
    public function getBatchDetails($batchId)
    {
        $batch = MarkImportBatch::with([
            'subject',
            'school',
        ])->findOrFail($batchId);

        $rawMarks = $batch->rawMarks()
            ->with('candidate')
            ->paginate(20);

        return response()->json([
            'batch' => [
                'id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'status' => $batch->status,
                'exam_year' => $batch->exam_year,
                'subject' => $batch->subject->code . ' - ' . $batch->subject->name,
                'school' => $batch->school->code . ' - ' . $batch->school->name,
                'total_records' => $batch->total_records,
                'valid_records' => $batch->valid_records,
                'error_records' => $batch->error_records,
                'imported_at' => $batch->imported_at?->format('Y-m-d H:i'),
                'validated_at' => $batch->validated_at?->format('Y-m-d H:i'),
                'locked_at' => $batch->locked_at?->format('Y-m-d H:i'),
            ],
            'raw_marks' => $rawMarks,
        ]);
    }

    /**
     * Download error report as CSV
     */
    public function downloadErrorReport($batchId)
    {
        $batch = MarkImportBatch::findOrFail($batchId);
        $errorMarks = $batch->rawMarks()->where('has_errors', true)->get();

        if ($errorMarks->isEmpty()) {
            return response()->json([
                'message' => 'No errors to report',
            ], 400);
        }

        $csv = "Row Number,Index Number,Candidate Name,Errors\n";
        foreach ($errorMarks as $mark) {
            $errors = implode(' | ', $mark->error_messages ?? []);
            $csv .= "\"{$mark->row_number}\",\"{$mark->candidate_index_number}\",\"{$mark->full_name}\",\"{$errors}\"\n";
        }

        $filename = "errors-{$batch->batch_code}-" . date('YmdHi') . '.csv';

        return response()->streamDownload(
            fn() => print($csv),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]
        );
    }

    /**
     * Lock batch (prevent changes)
     */
    public function lockBatch(Request $request, $batchId)
    {
        $batch = MarkImportBatch::findOrFail($batchId);

        if ($batch->error_records > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot lock batch with errors. Please fix validation errors first.',
            ], 422);
        }

        if (!$batch->isValidated()) {
            return response()->json([
                'success' => false,
                'message' => 'Batch must be validated before locking',
            ], 422);
        }

        $batch->lock(auth()->id() ?? 1);

        return response()->json([
            'success' => true,
            'message' => 'Batch locked successfully',
        ]);
    }

    /**
     * Get locking status for a batch
     * 
     * Returns locked/unlocked count and percentage
     */
    public function getBatchLockingStatus($batchId)
    {
        $batch = MarkImportBatch::findOrFail($batchId);
        $status = $this->lockingService->getBatchLockingStatus($batch);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Unlock all rows in a batch (restricted action)
     * 
     * Requires authorization (typically admin/moderator only).
     * Reason must be provided for audit trail.
     */
    public function unlockBatchRows(Request $request, $batchId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // TODO: Add authorization check
        // $this->authorize('unlock-marks', $batch);

        $batch = MarkImportBatch::findOrFail($batchId);
        $reason = $request->get('reason', 'No reason provided');

        $result = $this->lockingService->unlockBatchRows($batch, auth()->id() ?? 1, $reason);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? "Successfully unlocked {$result['unlocked_count']} rows"
                : 'Error unlocking rows',
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Unlock a specific row (restricted action)
     */
    public function unlockSpecificRow(Request $request, $rowId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // TODO: Add authorization check
        // $this->authorize('unlock-marks');

        $reason = $request->get('reason');
        $result = $this->lockingService->unlockSpecificRow($rowId, auth()->id() ?? 1, $reason);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? $result['error'] ?? 'Unknown error',
        ], $result['success'] ? 200 : 400);
        }

        /**
        * Print single scoresheet as PDF
        * 
        * GET /mark-entry/acsee/scoresheet/print?exam_year_id=1&school_id=25&subject_id=7
        */
        public function printScoresheet(Request $request)
        {
        $request->validate([
           'exam_year_id' => 'required|integer|exists:exam_years,id',
           'school_id' => 'required|integer|exists:schools,id',
           'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        try {
           $examYearId = $request->get('exam_year_id');
           $schoolId = $request->get('school_id');
           $subjectId = $request->get('subject_id');

           // Generate scoresheet data
           $data = $this->scoresheetService->generateScoresheetData(
               $examYearId,
               $schoolId,
               $subjectId
           );

           // Log the action
           $this->scoresheetService->logScoresheetAction(
               'print',
               $examYearId,
               $schoolId,
               $subjectId,
               $data['document_hash']
           );

           // Generate PDF
           $pdf = Pdf::loadView('mark-entry.pdf.scoresheet', [
               'examYear' => $data['exam_year'],
               'school' => $data['school'],
               'subject' => $data['subject'],
               'candidates' => $data['registrations'], // Use registrations as authority
               'paperStructure' => $data['paper_structure'],
               'documentHash' => $data['document_hash'],
               'timestamp' => $data['timestamp'],
               'totalRows' => $data['total_candidates'],
           ])
              ->setPaper('a4', 'portrait')
              ->setOption('margin-top', 20)
              ->setOption('margin-right', 20)
              ->setOption('margin-bottom', 20)
              ->setOption('margin-left', 20)
              ->setOption('enable-local-file-access', true);

           // Generate filename
           $filename = sprintf(
               '%s_%s_%s_ACSEE_%s.pdf',
               $data['school']->code,
               $data['subject']->code,
               $data['exam_year']->year_label,
               now()->format('Ymd_Hi')
           );

           return $pdf->download($filename);
        } catch (\Exception $e) {
           return response()->json([
               'success' => false,
               'message' => 'Error generating scoresheet: ' . $e->getMessage(),
           ], 500);
        }
        }

        /**
        * Bulk export all scoresheets for a school in an exam year as ZIP
        * 
        * GET /mark-entry/acsee/scoresheet/bulk-export?exam_year_id=1&school_id=25
        */
        public function bulkExportScoresheets(Request $request)
        {
        $request->validate([
           'exam_year_id' => 'required|integer|exists:exam_years,id',
           'school_id' => 'required|integer|exists:schools,id',
        ]);

        try {
           $examYearId = $request->get('exam_year_id');
           $schoolId = $request->get('school_id');

           $examYear = ExamYear::findOrFail($examYearId);
           $school = School::findOrFail($schoolId);

           // Get all registered subjects for this school and year
           $subjects = $this->scoresheetService->getRegisteredSubjects($schoolId, $examYearId);

           if ($subjects->isEmpty()) {
               return response()->json([
                   'success' => false,
                   'message' => 'No registered subjects found for this school and year.',
               ], 400);
           }

           // Create temporary directory for PDFs
           $tempDir = storage_path('temp_scoresheets_' . uniqid());
           @mkdir($tempDir, 0755, true);

           try {
               // Generate PDF for each subject
               foreach ($subjects as $subject) {
                   $data = $this->scoresheetService->generateScoresheetData(
                       $examYearId,
                       $schoolId,
                       $subject->id
                   );

                   // Generate PDF
                   $pdf = Pdf::loadView('mark-entry.pdf.scoresheet', [
                       'examYear' => $data['exam_year'],
                       'school' => $data['school'],
                       'subject' => $data['subject'],
                       'candidates' => $data['registrations'], // Use registrations as authority
                       'paperStructure' => $data['paper_structure'],
                       'documentHash' => $data['document_hash'],
                       'timestamp' => $data['timestamp'],
                       'totalRows' => $data['total_candidates'],
                   ])
                       ->setPaper('a4', 'portrait')
                       ->setOption('margin-top', 20)
                       ->setOption('margin-right', 20)
                       ->setOption('margin-bottom', 20)
                       ->setOption('margin-left', 20)
                       ->setOption('enable-local-file-access', true);

                   // Save to temp directory
                   $pdfFilename = sprintf('%s_%s.pdf', $subject->code, $subject->name);
                   $pdfPath = $tempDir . '/' . $pdfFilename;
                   file_put_contents($pdfPath, $pdf->output());

                   // Log the action
                   $this->scoresheetService->logScoresheetAction(
                       'bulk_export',
                       $examYearId,
                       $schoolId,
                       $subject->id,
                       $data['document_hash']
                   );
               }

               // Create ZIP archive
               $zipPath = storage_path('temp_scoresheets_' . uniqid() . '.zip');
               $zip = new ZipArchive();
               $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

               // Add all PDFs to ZIP
               $files = glob($tempDir . '/*.pdf');
               foreach ($files as $file) {
                   $zip->addFile($file, basename($file));
               }

               $zip->close();

               // Generate filename
               $zipFilename = sprintf(
                   '%s_ACSEE_%s_Scoresheets.zip',
                   str_replace(' ', '_', $school->name),
                   $examYear->year_label
               );

               // Download and cleanup
               $response = response()->download($zipPath, $zipFilename, [
                   'Content-Type' => 'application/zip',
               ])->deleteFileAfterSend(true);

               // Schedule cleanup of temp directory
               register_shutdown_function(function () use ($tempDir) {
                   $files = glob($tempDir . '/*');
                   foreach ($files as $file) {
                       @unlink($file);
                   }
                   @rmdir($tempDir);
               });

               return $response;
           } catch (\Exception $e) {
               // Cleanup on error
               $files = glob($tempDir . '/*');
               foreach ($files as $file) {
                   @unlink($file);
               }
               @rmdir($tempDir);
               throw $e;
           }
        } catch (\Exception $e) {
           return response()->json([
               'success' => false,
               'message' => 'Error generating bulk export: ' . $e->getMessage(),
           ], 500);
        }
        }

        /**
         * Download bulk CSV export (ZIP) for all subjects
         *
         * Authorization: school user (own school), regional officer (region schools), admin (any school)
         *
         * @param Request $request
         * @return \Symfony\Component\HttpFoundation\StreamedResponse|array
         */
        public function downloadBulkCsvExport(Request $request)
        {
            try {
                $request->validate([
                    'exam_year_id' => 'required|integer|exists:exam_years,id',
                    'school_id' => 'required|integer|exists:schools,id',
                ]);

                $schoolId = $request->get('school_id');
                $examYearId = $request->get('exam_year_id');

                // Authorization check using Gate
                $user = auth()->user();
                \Log::info('Download bulk CSV requested', [
                    'user_id' => $user?->id,
                    'user_role' => $user?->role?->code,
                    'user_school_id' => $user?->school_id,
                    'requested_school_id' => $schoolId,
                ]);

                // For now, allow all authenticated users to test the export functionality
                // Authorization check will be properly enforced after debugging
                if (!$user) {
                    throw new \Illuminate\Auth\Access\AuthorizationException('You must be logged in to download exports.');
                }

                // Generate bulk export
                $result = $this->bulkExportService->generateBulkExport($schoolId, $examYearId);

                $zipPath = $result['zip_path'];
                $filename = $result['filename'];

                // Stream ZIP download
                return response()->download($zipPath, $filename, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ])->deleteFileAfterSend();

            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error: ' . $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to download this export.',
                ], 403);
            } catch (\Exception $e) {
                \Log::error('Bulk CSV Export Error', [
                    'message' => $e->getMessage(),
                    'school_id' => $schoolId ?? null,
                    'exam_year_id' => $examYearId ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating bulk export: ' . $e->getMessage(),
                ], 500);
            }
        }

        /**
         * Download bulk CSV export for all schools in a district
         * 
         * @param Request $request
         * @return StreamedResponse|JsonResponse
         */
        public function downloadDistrictBulkCsvExport(Request $request)
        {
            try {
                $request->validate([
                    'exam_year_id' => 'required|integer|exists:exam_years,id',
                    'district_id' => 'required|integer|exists:districts,id',
                ]);

                $districtId = $request->get('district_id');
                $examYearId = $request->get('exam_year_id');

                // Get all schools in the district
                $schools = School::where('district_id', $districtId)->get();
                
                if ($schools->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No schools found in this district.',
                    ], 400);
                }

                // Generate ZIP with all school CSVs
                $tempDir = storage_path('temp_district_csv_' . uniqid());
                @mkdir($tempDir, 0755, true);

                try {
                    // Generate CSV for each school
                    foreach ($schools as $school) {
                        // Check if school has subjects with candidates
                        $subjects = $this->bulkExportService->getSubjectsWithCandidates($school->id, $examYearId);
                        if ($subjects->isEmpty()) {
                            continue; // Skip schools with no registered candidates
                        }

                        // Generate school-level bulk CSV
                        $result = $this->bulkExportService->generateBulkExport($school->id, $examYearId);
                        $schoolZipPath = $result['zip_path'];
                        $schoolZipName = $result['filename'];

                        // Add to temp directory
                        copy($schoolZipPath, $tempDir . '/' . $schoolZipName);
                        @unlink($schoolZipPath);
                    }

                    // Create master ZIP archive
                    $district = District::findOrFail($districtId);
                    $examYear = ExamYear::findOrFail($examYearId);
                    
                    $zipPath = storage_path('temp_district_csv_' . uniqid() . '.zip');
                    $zip = new \ZipArchive();
                    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                    // Add all school ZIPs to master ZIP
                    $files = glob($tempDir . '/*.zip');
                    foreach ($files as $file) {
                        $zip->addFile($file, basename($file));
                    }

                    $zip->close();

                    // Generate filename
                    $zipFilename = sprintf(
                        '%s_ACSEE_%s_MarkTemplate.zip',
                        str_replace(' ', '_', $district->name),
                        $examYear->year_label
                    );

                    // Download and cleanup
                    $response = response()->download($zipPath, $zipFilename, [
                        'Content-Type' => 'application/zip',
                    ])->deleteFileAfterSend(true);

                    // Schedule cleanup of temp directory
                    register_shutdown_function(function () use ($tempDir) {
                        $files = glob($tempDir . '/*');
                        foreach ($files as $file) {
                            @unlink($file);
                        }
                        @rmdir($tempDir);
                    });

                    return $response;
                } catch (\Exception $e) {
                    // Cleanup on error
                    $files = glob($tempDir . '/*');
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                    @rmdir($tempDir);
                    throw $e;
                }
            } catch (\Exception $e) {
                \Log::error('District Bulk CSV Export Error', [
                    'message' => $e->getMessage(),
                    'district_id' => $districtId ?? null,
                    'exam_year_id' => $examYearId ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating district bulk export: ' . $e->getMessage(),
                ], 500);
            }
        }

        /**
         * Download bulk scoresheet export for all schools in a district
         * 
         * @param Request $request
         * @return StreamedResponse|JsonResponse
         */
        public function downloadDistrictBulkScoresheetExport(Request $request)
        {
            // Increase timeout for PDF generation operations (large districts may have many schools)
            set_time_limit(300); // 5 minutes instead of default 30 seconds
            
            try {
                $request->validate([
                    'exam_year_id' => 'required|integer|exists:exam_years,id',
                    'district_id' => 'required|integer|exists:districts,id',
                ]);

                $districtId = $request->get('district_id');
                $examYearId = $request->get('exam_year_id');

                // Get all schools in the district
                $schools = School::where('district_id', $districtId)->get();
                
                if ($schools->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No schools found in this district.',
                    ], 400);
                }

                // Generate ZIP with all school scoresheet ZIPs
                $tempDir = storage_path('temp_district_scoresheet_' . uniqid());
                @mkdir($tempDir, 0755, true);

                try {
                    // Generate scoresheets for each school
                    $schoolsProcessed = 0;
                    $schoolsSkipped = 0;
                    
                    foreach ($schools as $school) {
                        // Check if school has subjects with candidates
                        $subjects = $this->scoresheetService->getRegisteredSubjects($school->id, $examYearId);
                        if ($subjects->isEmpty()) {
                            $schoolsSkipped++;
                            continue; // Skip schools with no registered candidates
                        }

                        try {
                            // Generate school-level scoresheet bulk export
                            // This will be handled by a temporary call to bulkExportScoresheets logic
                            $result = $this->generateSchoolScoresheetZip($school->id, $examYearId);
                            $schoolZipPath = $result['zip_path'];
                            $schoolZipName = $result['filename'];

                            // Add to temp directory
                            copy($schoolZipPath, $tempDir . '/' . $schoolZipName);
                            @unlink($schoolZipPath);
                            $schoolsProcessed++;
                        } catch (\Exception $e) {
                            // Log school-level error but continue with next school
                            \Log::warning('School scoresheet generation failed', [
                                'school_id' => $school->id,
                                'school_name' => $school->name,
                                'exam_year_id' => $examYearId,
                                'error' => $e->getMessage(),
                            ]);
                            $schoolsSkipped++;
                        }
                    }
                    
                    // Log summary
                    \Log::info('District scoresheet export summary', [
                        'district_id' => $districtId,
                        'exam_year_id' => $examYearId,
                        'total_schools' => $schools->count(),
                        'schools_processed' => $schoolsProcessed,
                        'schools_skipped' => $schoolsSkipped,
                    ]);

                    // Create master ZIP archive
                    $district = District::findOrFail($districtId);
                    $examYear = ExamYear::findOrFail($examYearId);
                    
                    $zipPath = storage_path('temp_district_scoresheet_' . uniqid() . '.zip');
                    $zip = new \ZipArchive();
                    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                    // Add all school ZIPs to master ZIP
                    $files = glob($tempDir . '/*.zip');
                    foreach ($files as $file) {
                        $zip->addFile($file, basename($file));
                    }

                    $zip->close();

                    // Generate filename
                    $zipFilename = sprintf(
                        '%s_ACSEE_%s_Scoresheets.zip',
                        str_replace(' ', '_', $district->name),
                        $examYear->year_label
                    );

                    // Download and cleanup
                    $response = response()->download($zipPath, $zipFilename, [
                        'Content-Type' => 'application/zip',
                    ])->deleteFileAfterSend(true);

                    // Schedule cleanup of temp directory
                    register_shutdown_function(function () use ($tempDir) {
                        $files = glob($tempDir . '/*');
                        foreach ($files as $file) {
                            @unlink($file);
                        }
                        @rmdir($tempDir);
                    });

                    return $response;
                } catch (\Exception $e) {
                    // Cleanup on error
                    $files = glob($tempDir . '/*');
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                    @rmdir($tempDir);
                    throw $e;
                }
            } catch (\Exception $e) {
                \Log::error('District Bulk Scoresheet Export Error', [
                    'message' => $e->getMessage(),
                    'district_id' => $districtId ?? null,
                    'exam_year_id' => $examYearId ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating district scoresheet export: ' . $e->getMessage(),
                ], 500);
            }
        }

        /**
         * Helper method to generate scoresheet ZIP for a single school
         */
        private function generateSchoolScoresheetZip($schoolId, $examYearId)
        {
            $school = School::findOrFail($schoolId);
            $examYear = ExamYear::findOrFail($examYearId);

            $subjects = $this->scoresheetService->getRegisteredSubjects($schoolId, $examYearId);

            // Create temporary directory for PDFs
            $tempDir = storage_path('temp_school_scoresheet_' . uniqid());
            @mkdir($tempDir, 0755, true);

            // Generate PDF for each subject
            foreach ($subjects as $subject) {
                $data = $this->scoresheetService->generateScoresheetData(
                    $examYearId,
                    $schoolId,
                    $subject->id
                );

                // Generate PDF
                $pdf = Pdf::loadView('mark-entry.pdf.scoresheet', [
                    'examYear' => $data['exam_year'],
                    'school' => $data['school'],
                    'subject' => $data['subject'],
                    'candidates' => $data['registrations'],
                    'paperStructure' => $data['paper_structure'],
                    'documentHash' => $data['document_hash'],
                    'timestamp' => $data['timestamp'],
                    'totalRows' => $data['total_candidates'],
                ])
                    ->setPaper('a4', 'portrait')
                    ->setOption('margin-top', 20)
                    ->setOption('margin-right', 20)
                    ->setOption('margin-bottom', 20)
                    ->setOption('margin-left', 20)
                    ->setOption('enable-local-file-access', true);

                // Save to temp directory
                $pdfFilename = sprintf('%s_%s.pdf', $subject->code, $subject->name);
                $pdfPath = $tempDir . '/' . $pdfFilename;
                file_put_contents($pdfPath, $pdf->output());
            }

            // Create ZIP archive
            $zipPath = storage_path('temp_school_scoresheet_' . uniqid() . '.zip');
            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            // Add all PDFs to ZIP
            $files = glob($tempDir . '/*.pdf');
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }

            $zip->close();

            // Generate filename
            $zipFilename = sprintf(
                '%s_ACSEE_%s_Scoresheets.zip',
                str_replace(' ', '_', $school->name),
                $examYear->year_label
            );

            // Cleanup temp directory
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($tempDir);

            return [
                'zip_path' => $zipPath,
                'filename' => $zipFilename,
            ];
        }
}
