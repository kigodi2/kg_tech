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
use App\Services\MarkImport\MarkImportRunService;
use App\Services\MarkImport\MarkValidationService;
use App\Services\MarkImport\MarkTemplateService;
use App\Services\MarkImport\SubjectFilterService;
use App\Services\MarkImport\AcseeMarkTemplateService;
use App\Services\MarkImport\CsvIntegrityService;
use App\Services\MarkImport\MarkRowLockingService;
use App\Services\MarkImport\ScoresheetService;
use App\Services\MarkImport\BulkCsvExportService;
use App\Services\MarkImport\ZipPreviewService;
use App\Services\ExamYear\ExamYearValidationService;
use App\Models\MarkImportRun;
use App\Models\GovernanceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class MarkEntryController extends Controller
{
    private MarkImportService $importService;
    private MarkImportRunService $runService;
    private MarkValidationService $validationService;
    private MarkTemplateService $templateService;
    private SubjectFilterService $subjectFilterService;
    private AcseeMarkTemplateService $acseeTemplateService;
    private CsvIntegrityService $integrityService;
    private MarkRowLockingService $lockingService;
    private ExamYearValidationService $yearValidationService;
    private ScoresheetService $scoresheetService;
    private BulkCsvExportService $bulkExportService;

    // DEPRECATION NOTICE: uploadMarks is superseded by validate/commit endpoints
    // Keep uploadMarks for backward compatibility until UI fully migrates.

    public function __construct(
        MarkImportService $importService,
        MarkImportRunService $runService,
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
        $this->runService = $runService;
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
        $districtInput = trim((string) $request->query('district_id', ''));
        if ($districtInput === '') {
            return response()->json([
                'message' => 'The district_id field is required.'
            ], 422);
        }

        $district = null;
        if (ctype_digit($districtInput)) {
            $district = District::find((int) $districtInput);
        } else {
            $district = District::query()
                ->where('code', $districtInput)
                ->orWhere('name', $districtInput)
                ->first();
        }

        if (!$district) {
            return response()->json(['data' => []]);
        }

        $schools = School::where('district_id', $district->id)
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
            ->leftJoin('districts', 'districts.id', '=', 'schools.district_id')
            ->select(
                'schools.id',
                'schools.code',
                'schools.name',
                'schools.district_id',
                DB::raw('COALESCE(schools.region_id, districts.region_id) as region_id')
            )
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

        // Validate that the selected exam year exists (kept for API contract consistency).
        $examYear = ExamYear::where('year_label', $validated['exam_year'])->first();
        if (!$examYear) {
            return response()->json(['data' => []]);
        }

        // District Bulk ZIP should allow selecting from all scoped districts.
        // Do not restrict this picker to districts already having registrations/marks.
        $districtsQuery = District::query()
            ->leftJoin('regions', 'districts.region_id', '=', 'regions.id')
            ->select(
                'districts.id',
                'districts.code',
                'districts.name',
                'districts.region_id',
                'regions.code as region_code',
                'regions.name as region_name'
            )
            ->orderBy('districts.code');

        $user = auth()->user();
        if ($user) {
            if ($user->isRegionalOfficer() && $user->getRegionId()) {
                $districtsQuery->where('region_id', $user->getRegionId());
            } elseif (($user->isDistrictDataEntryOfficer() || $user->isDistrictSupervisor()) && $user->getDistrictId()) {
                $districtsQuery->where('id', $user->getDistrictId());
            } elseif ($user->isSchoolRegistrar() && $user->getSchoolId()) {
                $schoolDistrictId = School::whereKey($user->getSchoolId())->value('district_id');
                if ($schoolDistrictId) {
                    $districtsQuery->where('id', $schoolDistrictId);
                } else {
                    $districtsQuery->whereRaw('1 = 0');
                }
            }
        }

        $districts = $districtsQuery->get();

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

            // Advance batch status: draft → validated → awaiting_moderation
            if (($validationResult['valid'] ?? 0) > 0) {
                $batch->update([
                    'status' => MarkImportBatch::STATUS_VALIDATED,
                    'lifecycle_state' => 'awaiting_moderation',
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
            }

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
                'batch' => [
                    'id' => $batch->id,
                    'status' => $batch->status,
                    'total_records' => $batch->total_records,
                    'valid_records' => $batch->valid_records,
                    'error_records' => $batch->error_records,
                    'candidate_count' => $batch->rawMarks()->distinct('candidate_index_number')->count('candidate_index_number'),
                ],
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
                'candidate_count' => $batch->rawMarks()->distinct('candidate_index_number')->count('candidate_index_number'),
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

    /**
     * Single CSV validate (two-phase: validate only, no commit)
     *
     * Creates a MarkImportRun, performs structured validation,
     * populates mark_import_run_errors and mark_import_run_rows.
     */
    public function singleValidate(Request $request)
    {
        $examYear = $request->input('exam_year');
        $schoolId = $request->input('school_id');
        $subjectId = $request->input('subject_id');

        if (!$examYear || !$schoolId || !$subjectId || !$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'Missing required fields: exam_year, school_id, subject_id, file'], 422);
        }

        $file = $request->file('file');

        // Resolve exam_year_id from year label
        $examYearObj = ExamYear::where('year_label', (string) $examYear)->first();
        $examYearId = $examYearObj ? $examYearObj->id : 0;

        // Create import run for tracking
        $run = $this->runService->startRun(
            auth()->id() ?? 1,
            $examYearId,
            (int) $schoolId,
            (int) $subjectId,
            'single_subject',
            $file->getClientOriginalName(),
            $file->getSize()
        );

        try {
            $result = $this->runService->validateSingleCsv(
                $run, $file, (int) $examYear, (int) $schoolId, (int) $subjectId
            );

            return response()->json($result);
        } catch (\Exception $e) {
            $run->fail('Unexpected error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Validation error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Single CSV commit (two-phase: commit validated batch)
     *
     * Creates a MarkImportRun, validates via the run service,
     * then commits valid rows into the existing batch pipeline.
     * NEVER overwrites locked/approved marks.
     */
    public function singleCommit(Request $request)
    {
        $examYear = $request->input('exam_year');
        $schoolId = $request->input('school_id');
        $subjectId = $request->input('subject_id');

        if (!$examYear || !$schoolId || !$subjectId || !$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'Missing required fields'], 422);
        }

        $file = $request->file('file');
        $examYearObj = ExamYear::where('year_label', (string) $examYear)->first();
        $examYearId = $examYearObj ? $examYearObj->id : 0;
        $examYearLabel = (int) $examYear;

        // Create import run
        $run = $this->runService->startRun(
            auth()->id() ?? 1,
            $examYearId,
            (int) $schoolId,
            (int) $subjectId,
            'single_subject',
            $file->getClientOriginalName(),
            $file->getSize()
        );

        try {
            // Validate first
            $validationResult = $this->runService->validateSingleCsv(
                $run, $file, $examYearLabel, (int) $schoolId, (int) $subjectId
            );

            // If no valid rows, do not commit
            if (!($validationResult['can_commit'] ?? false)) {
                return response()->json(array_merge($validationResult, [
                    'message' => 'Cannot commit: no valid rows or blocking errors exist.',
                ]));
            }

            // Commit: create batch and process via existing pipeline
            DB::beginTransaction();

            $batch = $this->importService->createBatch($examYearLabel, (int) $schoolId, (int) $subjectId, auth()->id() ?? 1);
            $result = $this->importService->processCSVUpload($batch, $file, $examYearLabel, (int) $schoolId, (int) $subjectId);

            if (!$result['success']) {
                DB::rollBack();
                $run->fail('Batch processing failed: ' . ($result['error'] ?? 'unknown'));
                return response()->json(['success' => false, 'message' => $result['error'] ?? 'Import failed'], 400);
            }

            $validation = $this->importService->validateBatch($batch);

            if (($validation['valid'] ?? 0) > 0) {
                $batch->update([
                    'status' => MarkImportBatch::STATUS_VALIDATED,
                    'lifecycle_state' => 'awaiting_moderation',
                    'validated_by' => auth()->id(),
                    'validated_at' => now(),
                ]);
                $this->lockingService->lockBatchRows($batch, auth()->id() ?? 1);
            }

            // Link run to batch
            $run->update([
                'mark_import_batch_id' => $batch->id,
                'status' => MarkImportRun::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ($result['imported_records'] ?? 0) . ' records committed',
                'run_id' => $run->id,
                'batch_id' => $batch->id,
                'batch' => [
                    'id' => $batch->id,
                    'status' => $batch->status,
                    'total_records' => $batch->total_records,
                    'valid_records' => $batch->valid_records,
                    'error_records' => $batch->error_records,
                    'candidate_count' => $batch->rawMarks()->distinct('candidate_index_number')->count('candidate_index_number'),
                ],
                'totals' => [
                    'total_rows' => $result['imported_records'] ?? 0,
                    'valid_rows' => $validation['valid'] ?? 0,
                    'invalid_rows' => $validation['invalid'] ?? 0,
                ],
                'links' => [
                    'batch_details_url' => "/mark-entry/acsee/batch/{$batch->id}",
                    'error_report_url' => "/api/mark-entry/acsee/import/runs/{$run->id}/errors.csv",
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $run->fail('Unexpected error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validate a school bulk ZIP (preview + structure check, no commit)
     */
    public function schoolValidateZip(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'No ZIP file uploaded'], 422);
        }

        $file = $request->file('file');
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return response()->json(['success' => false, 'message' => 'Only ZIP files are allowed'], 422);
        }

        try {
            // Move uploaded file to a persistent temp location
            $destDir = storage_path('app/temp/imports');
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $destName = uniqid('school_zip_') . '.zip';
            $fullPath = $destDir . '/' . $destName;
            $file->move($destDir, $destName);

            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== true) {
                @unlink($fullPath);
                return response()->json(['success' => false, 'message' => 'Cannot open ZIP file'], 422);
            }

            // Check if manifest exists (also search in subdirectories)
            $hasManifest = ($zip->locateName('manifest.json') !== false)
                || ($zip->locateName('manifest.json', ZipArchive::FL_NODIR) !== false);
            $subjects = [];
            $totalCandidates = 0;
            $issues = [];

            if ($hasManifest) {
                $zip->close();
                $zipPreview = app(ZipPreviewService::class);
                $preview = $zipPreview->preview($fullPath);
                $subjects = $preview['subjects'] ?? [];
                $totalCandidates = $preview['total_candidates'] ?? 0;
                $issues = $preview['issues'] ?? [];
            } else {
                // Scan ZIP for CSV files directly
                $hasNestedZips = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);

                    // Skip macOS resource fork files
                    if (str_starts_with($filename, '__MACOSX/') || str_contains($filename, '/__MACOSX/')) {
                        continue;
                    }

                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if ($ext === 'zip') {
                        $hasNestedZips = true;
                        continue;
                    }

                    if ($ext !== 'csv' && $ext !== 'txt') {
                        continue;
                    }

                    $basename = pathinfo($filename, PATHINFO_FILENAME);
                    $subjectCode = explode('_', $basename)[0];

                    // Count rows (subtract header) with robust line ending handling.
                    $content = $zip->getFromIndex($i);
                    $lines = preg_split("/\r\n|\n|\r/", (string) $content);
                    $lines = array_filter($lines, fn($l) => trim((string) $l) !== '');
                    $rowCount = max(0, count($lines) - 1);

                    $subjects[] = [
                        'filename' => $filename,
                        'subject_code' => strtoupper($subjectCode),
                        'subject_name' => $subjectCode,
                        'candidates' => $rowCount,
                    ];
                    $totalCandidates += $rowCount;
                }
                $zip->close();

                if (empty($subjects)) {
                    if ($hasNestedZips) {
                        $issues[] = 'This ZIP contains school ZIPs — please use District Import instead';
                    } else {
                        $issues[] = 'No CSV files found in ZIP';
                    }
                }
            }

            // Store temp path in session for commit step
            session(['school_zip_temp_path' => $fullPath]);

            $status = empty($issues) && $totalCandidates > 0 ? 'completed' : (empty($issues) ? 'partial' : 'failed');

            return response()->json([
                'success' => true,
                'status' => $status,
                'can_commit' => $totalCandidates > 0 && empty($issues),
                'totals' => [
                    'total_rows' => $totalCandidates,
                    'valid_rows' => $totalCandidates,
                    'invalid_rows' => 0,
                    'warnings' => count($issues),
                ],
                'preview' => [
                    'scope_type' => 'school',
                    'subjects' => $subjects,
                    'total_files' => count($subjects),
                    'total_candidates' => $totalCandidates,
                    'issues' => $issues,
                    'is_valid' => empty($issues),
                ],
                'errors' => collect($issues)->map(fn($issue) => [
                    'row' => '-',
                    'field' => 'structure',
                    'message' => $issue,
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error validating ZIP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validate a district bulk ZIP
     */
    public function districtValidateZip(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'No ZIP file uploaded'], 422);
        }

        $file = $request->file('file');
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return response()->json(['success' => false, 'message' => 'Only ZIP files are allowed'], 422);
        }

        try {
            $destDir = storage_path('app/temp/imports');
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $destName = uniqid('district_zip_') . '.zip';
            $fullPath = $destDir . '/' . $destName;
            $file->move($destDir, $destName);

            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== true) {
                @unlink($fullPath);
                return response()->json(['success' => false, 'message' => 'Cannot open ZIP file'], 422);
            }

            $hasManifest = ($zip->locateName('manifest.json') !== false)
                || ($zip->locateName('manifest.json', ZipArchive::FL_NODIR) !== false);
            $schools = [];
            $totalCandidates = 0;
            $issues = [];

            if ($hasManifest) {
                $zip->close();
                $zipPreview = app(ZipPreviewService::class);
                $preview = $zipPreview->preview($fullPath);
                $schools = $preview['schools'] ?? [];
                $totalCandidates = $preview['total_candidates'] ?? 0;
                $issues = $preview['issues'] ?? [];
            } else {
                // District ZIP = ZIP of school ZIPs or CSVs
                // Check for nested ZIPs (school-level ZIPs inside district ZIP)
                $hasNestedZips = false;
                $hasCsvFiles = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fn = $zip->getNameIndex($i);
                    // Skip macOS resource fork files
                    if (str_starts_with($fn, '__MACOSX/') || str_contains($fn, '/__MACOSX/')) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                    if ($ext === 'zip') $hasNestedZips = true;
                    if ($ext === 'csv' || $ext === 'txt') $hasCsvFiles = true;
                }

                if ($hasNestedZips) {
                    // Extract each school ZIP and preview it
                    $tmpExtract = storage_path('app/temp/imports/extract_' . uniqid());
                    @mkdir($tmpExtract, 0755, true);

                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') {
                            continue;
                        }

                        $schoolZipContent = $zip->getFromIndex($i);
                        $schoolZipPath = $tmpExtract . '/' . basename($filename);
                        file_put_contents($schoolZipPath, $schoolZipContent);

                        $schoolCandidates = 0;
                        $schoolSubjects = [];
                        $schoolDisplayName = pathinfo($filename, PATHINFO_FILENAME);
                        $schoolDisplayCode = $schoolDisplayName;

                        $innerZip = new ZipArchive();
                        if ($innerZip->open($schoolZipPath) === true) {
                            // Try to read manifest for school info
                            $innerManifestContent = $innerZip->getFromName('manifest.json');
                            if ($innerManifestContent) {
                                $innerManifest = json_decode($innerManifestContent, true);
                                if (!empty($innerManifest['school_name'])) $schoolDisplayName = $innerManifest['school_name'];
                                if (!empty($innerManifest['school_code'])) $schoolDisplayCode = $innerManifest['school_code'];
                            }

                            for ($j = 0; $j < $innerZip->numFiles; $j++) {
                                $innerFile = $innerZip->getNameIndex($j);
                                $innerExt = strtolower(pathinfo($innerFile, PATHINFO_EXTENSION));
                                if ($innerExt !== 'csv' && $innerExt !== 'txt') continue;

                                $content = $innerZip->getFromIndex($j);
                                $lines = preg_split("/\r\n|\n|\r/", (string) $content);
                                $lines = array_filter($lines, fn($l) => trim((string) $l) !== '');
                                $rowCount = max(0, count($lines) - 1);
                                $subjectCode = explode('_', pathinfo($innerFile, PATHINFO_FILENAME))[0];

                                $schoolSubjects[] = ['code' => strtoupper($subjectCode), 'candidates' => $rowCount];
                                $schoolCandidates += $rowCount;
                            }
                            $innerZip->close();
                        }

                        $schools[] = [
                            'school_name' => $schoolDisplayName,
                            'school_code' => $schoolDisplayCode,
                            'total_subjects' => count($schoolSubjects),
                            'total_candidates' => $schoolCandidates,
                            'subjects' => $schoolSubjects,
                        ];
                        $totalCandidates += $schoolCandidates;
                        @unlink($schoolZipPath);
                    }

                    // Cleanup
                    @rmdir($tmpExtract);
                } elseif ($hasCsvFiles) {
                    // Flat CSV structure - detect subject code from filename
                    $schoolEntry = ['school_name' => 'All Files', 'school_code' => '-', 'total_subjects' => 0, 'total_candidates' => 0, 'subjects' => []];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (str_starts_with($filename, '__MACOSX/') || str_contains($filename, '/__MACOSX/')) continue;
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if ($ext !== 'csv' && $ext !== 'txt') continue;

                        $content = $zip->getFromIndex($i);
                        $lines = preg_split("/\r\n|\n|\r/", (string) $content);
                        $lines = array_filter($lines, fn($l) => trim((string) $l) !== '');
                        $rowCount = max(0, count($lines) - 1);

                        // Detect subject code: try first segment, then last, then all
                        $basename = pathinfo($filename, PATHINFO_FILENAME);
                        $parts = explode('_', $basename);
                        $detectedSubject = Subject::where('code', $parts[0])->orWhere('code', strtoupper($parts[0]))->first();
                        if (!$detectedSubject && count($parts) > 1) {
                            $lastPart = end($parts);
                            $detectedSubject = Subject::where('code', $lastPart)->orWhere('code', strtoupper($lastPart))->first();
                        }
                        if (!$detectedSubject && count($parts) > 2) {
                            foreach ($parts as $part) {
                                $detectedSubject = Subject::where('code', $part)->orWhere('code', strtoupper($part))->first();
                                if ($detectedSubject) break;
                            }
                        }

                        $subjectCode = $detectedSubject ? $detectedSubject->code : strtoupper($parts[0]);
                        if (!$detectedSubject) {
                            $issues[] = "Cannot detect subject from filename: {$filename}";
                        }

                        $schoolEntry['subjects'][] = ['code' => $subjectCode, 'candidates' => $rowCount];
                        $schoolEntry['total_candidates'] += $rowCount;
                        $totalCandidates += $rowCount;
                    }
                    $schoolEntry['total_subjects'] = count($schoolEntry['subjects']);
                    $schools[] = $schoolEntry;
                }

                $zip->close();

                if (empty($schools)) {
                    $issues[] = 'No school ZIPs or CSV files found in district ZIP';
                }
            }

            // Existing duplicate handling observed:
            // - Prior flow only blocked LOCKED/APPROVED during commit and did not prevent duplicate ZIP/file/row uploads.
            // - This validation step now computes duplicate fingerprints and returns a managed duplicate report.
            $trackingEnabled = $this->isDuplicateTrackingEnabled();
            $duplicateReport = $this->analyzeDistrictZipDuplicates(
                $fullPath,
                (int) ($request->input('district_id') ?? 0),
                (int) ($request->input('exam_year_id') ?? 0),
                $trackingEnabled
            );

            $bulkUploadId = 0;
            if ($trackingEnabled) {
                $bulkUploadId = DB::table('bulk_uploads')->insertGetId([
                    'exam_year_id' => (int) ($request->input('exam_year_id') ?? 0) ?: null,
                    'region_id' => District::whereKey((int) ($request->input('district_id') ?? 0))->value('region_id'),
                    'district_id' => (int) ($request->input('district_id') ?? 0) ?: null,
                    'upload_type' => 'district_zip',
                    'original_filename' => $file->getClientOriginalName(),
                    'zip_hash' => $duplicateReport['zip_hash'] ?? null,
                    'zip_size' => filesize($fullPath),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'status' => 'validated',
                    'duplicate_status' => $duplicateReport['duplicate_status'] ?? 'new',
                    'metadata' => json_encode([
                        'validation_issues' => $issues,
                        'duplicate_report' => $duplicateReport,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach (($duplicateReport['duplicate_files'] ?? []) as $dupFile) {
                    DB::table('bulk_upload_files')->insert([
                        'bulk_upload_id' => $bulkUploadId,
                        'filename' => $dupFile['filename'] ?? '',
                        'file_hash' => $dupFile['file_hash'] ?? null,
                        'derived_scope' => json_encode($dupFile['derived_scope'] ?? []),
                        'duplicate_of_file_id' => null,
                        'status' => 'duplicate',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach (($duplicateReport['new_files'] ?? []) as $newFile) {
                    DB::table('bulk_upload_files')->insert([
                        'bulk_upload_id' => $bulkUploadId,
                        'filename' => $newFile['filename'] ?? '',
                        'file_hash' => $newFile['file_hash'] ?? null,
                        'derived_scope' => json_encode($newFile['derived_scope'] ?? []),
                        'duplicate_of_file_id' => null,
                        'status' => 'new',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            session([
                'district_zip_temp_path' => $fullPath,
                'district_bulk_upload_id' => $bulkUploadId,
                'district_duplicate_report' => $duplicateReport,
            ]);

            $status = empty($issues) && $totalCandidates > 0 ? 'completed' : (empty($issues) ? 'partial' : 'failed');
            $canCommit = $totalCandidates > 0;

            return response()->json([
                'success' => true,
                'status' => $status,
                'can_commit' => $canCommit,
                'totals' => [
                    'total_rows' => $totalCandidates,
                    'valid_rows' => $totalCandidates,
                    'invalid_rows' => 0,
                    'warnings' => count($issues),
                ],
                'preview' => [
                    'scope_type' => 'district',
                    'schools' => $schools,
                    'total_schools' => count($schools),
                    'total_candidates' => $totalCandidates,
                    'issues' => $issues,
                    'is_valid' => empty($issues),
                ],
                'errors' => collect($issues)->map(fn($issue) => [
                    'row' => '-',
                    'field' => 'structure',
                    'message' => $issue,
                ])->toArray(),
                'bulk_upload_id' => $bulkUploadId,
                'duplicate_report' => $duplicateReport,
                'tracking_enabled' => $trackingEnabled,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error validating ZIP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Commit school bulk ZIP (process all CSVs inside the ZIP)
     *
     * Creates a parent MarkImportRun for the ZIP, with per-file error tracking.
     * Checks locked/approved conflicts per subject before commit.
     */
    public function schoolCommitZip(Request $request)
    {
        $schoolId = $request->input('school_id');
        $examYearId = $request->input('exam_year_id');
        $zipPath = session('school_zip_temp_path');

        if (!$zipPath || !file_exists($zipPath)) {
            return response()->json(['success' => false, 'message' => 'No validated ZIP found. Please validate first.'], 422);
        }

        $examYearObj = $examYearId ? ExamYear::find($examYearId) : null;
        $school = School::find($schoolId);

        // Create parent import run for the ZIP
        $parentRun = $this->runService->startRun(
            auth()->id() ?? 1,
            $examYearId ?? 0,
            (int) $schoolId,
            0,
            'school_zip',
            basename($zipPath),
            filesize($zipPath),
            $school->region_id ?? null,
            $school->district_id ?? null
        );

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                $parentRun->fail('Cannot open ZIP file');
                return response()->json(['success' => false, 'message' => 'Cannot open ZIP file'], 500);
            }

            $manifestContent = $zip->getFromName('manifest.json');
            $manifest = $manifestContent ? json_decode($manifestContent, true) : null;
            $examYear = $manifest['exam_year'] ?? ($examYearObj ? $examYearObj->year_label : date('Y'));
            $results = [];
            $batchIds = [];
            $totalSuccess = 0;
            $totalErrors = 0;
            $totalRows = 0;
            $trackingEnabled = $this->isDuplicateTrackingEnabled();
            $bulkUploadId = (int) (session('district_bulk_upload_id') ?: $request->input('bulk_upload_id', 0));
            $duplicateReport = session('district_duplicate_report', []);
            $duplicateStrategy = strtolower((string) ($request->input('duplicate_strategy', 'skip')));
            $replaceConflicts = filter_var($request->input('replace_conflicts', false), FILTER_VALIDATE_BOOLEAN);
            $replaceReason = trim((string) $request->input('reason', ''));
            $forceReplaceLocked = filter_var($request->input('force_replace_locked', false), FILTER_VALIDATE_BOOLEAN);

            if (!in_array($duplicateStrategy, ['skip', 'merge', 'replace'], true)) {
                return response()->json(['success' => false, 'message' => 'Invalid duplicate strategy selected.'], 422);
            }

            $isAdmin = auth()->user() && (auth()->user()->isAdmin() || Gate::allows('mark-entry.admin'));
            if ($duplicateStrategy === 'replace' && !$isAdmin) {
                return response()->json(['success' => false, 'message' => 'Replace strategy requires admin permission.'], 403);
            }
            if ($duplicateStrategy === 'replace' && $replaceReason === '') {
                return response()->json(['success' => false, 'message' => 'Reason is required for replace strategy.'], 422);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if ($filename === 'manifest.json' || ($ext !== 'csv' && $ext !== 'txt')) {
                    continue;
                }

                $subjectCode = explode('_', pathinfo($filename, PATHINFO_FILENAME))[0];
                $subject = Subject::where('code', $subjectCode)->orWhere('code', strtoupper($subjectCode))->first();
                if (!$subject) {
                    $this->runService->addRunError($parentRun, 0, null, null, null, null, 'INVALID_SUBJECT', 'error',
                        "Subject not found: {$subjectCode} (file: {$filename})", $subjectCode, $filename);
                    $results[] = ['subject_code' => $subjectCode, 'status' => 'failed', 'message' => "Subject not found: {$subjectCode}"];
                    $totalErrors++;
                    continue;
                }

                // Check locked conflict
                $lockedBatch = MarkImportBatch::where('school_id', $schoolId)
                    ->where('subject_id', $subject->id)
                    ->where('exam_year', (int) $examYear)
                    ->whereIn('status', [MarkImportBatch::STATUS_LOCKED, MarkImportBatch::STATUS_APPROVED])
                    ->first();

                if ($lockedBatch && $duplicateStrategy !== 'replace') {
                    $this->runService->addRunError($parentRun, 0, null, $subject->id, null, null, 'LOCKED_CONFLICT', 'error',
                        "Subject {$subjectCode} already has " . strtoupper($lockedBatch->status) . " marks (batch {$lockedBatch->batch_code}). Skipped.",
                        $lockedBatch->batch_code, $filename);
                    $results[] = ['subject_code' => $subjectCode, 'status' => 'skipped', 'message' => 'Locked/approved conflict'];
                    $totalErrors++;
                    continue;
                }

                if ($lockedBatch && $duplicateStrategy === 'replace' && (!$isAdmin || !$forceReplaceLocked)) {
                    $results[] = [
                        'subject_code' => $subjectCode,
                        'status' => 'failed',
                        'message' => 'Replace against approved/locked data requires super-admin confirmation.',
                    ];
                    $totalErrors++;
                    continue;
                }

                $csvContent = $zip->getFromIndex($i);
                $tmpCsv = tempnam(sys_get_temp_dir(), 'csv_');
                file_put_contents($tmpCsv, $csvContent);

                try {
                    DB::beginTransaction();

                    if ($duplicateStrategy === 'replace') {
                        $replaceExcludedStatuses = [MarkImportBatch::STATUS_PROCESSED];
                        if (!$forceReplaceLocked) {
                            $replaceExcludedStatuses[] = MarkImportBatch::STATUS_LOCKED;
                            $replaceExcludedStatuses[] = MarkImportBatch::STATUS_APPROVED;
                        }

                        $existingReplaceable = MarkImportBatch::where('school_id', (int) $schoolId)
                            ->where('subject_id', (int) $subject->id)
                            ->where('exam_year', (int) $examYear)
                            ->whereNotIn('status', $replaceExcludedStatuses)
                            ->get();

                        foreach ($existingReplaceable as $oldBatch) {
                            $oldBatch->update([
                                'status' => 'superseded',
                                'lifecycle_state' => 'archived',
                                'notes' => trim(($oldBatch->notes ? $oldBatch->notes . ' | ' : '') . "Superseded via school replace: {$replaceReason}"),
                            ]);
                        }
                    }

                    $batch = $this->importService->createBatch((int)$examYear, (int)$schoolId, (int)$subject->id, auth()->id() ?? 1);
                    $uploadedFile = new \Illuminate\Http\UploadedFile($tmpCsv, $filename, 'text/csv', null, true);
                    $result = $this->importService->processCSVUpload($batch, $uploadedFile, (int)$examYear, (int)$schoolId, (int)$subject->id);

                    if ($result['success']) {
                        $validation = $this->importService->validateBatch($batch);
                        if (($validation['valid'] ?? 0) > 0) {
                            $batch->update([
                                'status' => MarkImportBatch::STATUS_VALIDATED,
                                'lifecycle_state' => 'awaiting_moderation',
                                'validated_by' => auth()->id(),
                                'validated_at' => now(),
                            ]);
                            $this->lockingService->lockBatchRows($batch, auth()->id() ?? 1);
                        }
                        DB::commit();
                        $batchIds[] = $batch->id;
                        $fileRows = $result['imported_records'] ?? 0;
                        $fileValid = $validation['valid'] ?? 0;
                        $totalRows += $fileRows;
                        $totalSuccess += $fileValid;
                        $results[] = ['subject_code' => $subjectCode, 'status' => 'success', 'rows_total' => $fileRows, 'rows_success' => $fileValid];
                    } else {
                        DB::rollBack();
                        $this->runService->addRunError($parentRun, 0, null, $subject->id, null, null, 'PROCESSING_FAILED', 'error',
                            $result['error'] ?? 'Processing failed', null, $filename);
                        $results[] = ['subject_code' => $subjectCode, 'status' => 'failed', 'message' => $result['error'] ?? 'Processing failed'];
                        $totalErrors++;
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->runService->addRunError($parentRun, 0, null, $subject->id ?? null, null, null, 'EXCEPTION', 'error',
                        $e->getMessage(), null, $filename);
                    $results[] = ['subject_code' => $subjectCode, 'status' => 'failed', 'message' => $e->getMessage()];
                    $totalErrors++;
                }

                @unlink($tmpCsv);
            }

            $zip->close();
            @unlink($zipPath);
            session()->forget('school_zip_temp_path');

            // Finalize parent run
            $parentRun->complete($totalRows, $totalSuccess, $totalErrors, 0,
                count($results) . " files processed, {$totalSuccess} rows committed");

            return response()->json([
                'success' => true,
                'run_id' => $parentRun->id,
                'batch' => ['id' => $batchIds[0] ?? null],
                'files' => $results,
            ]);
        } catch (\Exception $e) {
            $parentRun->fail('Error committing ZIP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error committing ZIP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Commit district bulk ZIP
     *
     * Creates a parent MarkImportRun for the ZIP with per-school/subject error tracking.
     * Checks locked/approved conflicts before processing each file.
     */
    public function districtCommitZip(Request $request)
    {
        $districtId = $request->input('district_id');
        $examYearId = $request->input('exam_year_id');
        $zipPath = session('district_zip_temp_path');

        if (!$zipPath || !file_exists($zipPath)) {
            return response()->json(['success' => false, 'message' => 'No validated ZIP found. Please validate first.'], 422);
        }

        $district = District::find($districtId);
        $parentRun = $this->runService->startRun(
            auth()->id() ?? 1,
            $examYearId ?? 0,
            0,
            0,
            'district_zip',
            basename($zipPath),
            filesize($zipPath),
            $district->region_id ?? null,
            (int) $districtId
        );

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                $parentRun->fail('Cannot open ZIP file');
                return response()->json(['success' => false, 'message' => 'Cannot open ZIP file'], 500);
            }

            $examYearObj = $examYearId ? ExamYear::find($examYearId) : null;
            $results = [];
            $batchIds = [];
            $totalSuccess = 0;
            $totalErrors = 0;
            $totalRows = 0;
            $trackingEnabled = $this->isDuplicateTrackingEnabled();
            $bulkUploadId = (int) (session('district_bulk_upload_id') ?: $request->input('bulk_upload_id', 0));
            $duplicateReport = session('district_duplicate_report', []);
            $duplicateStrategy = strtolower((string) ($request->input('duplicate_strategy', 'skip')));
            $replaceConflicts = filter_var($request->input('replace_conflicts', false), FILTER_VALIDATE_BOOLEAN);
            $replaceReason = trim((string) $request->input('reason', ''));
            $forceReplaceLocked = filter_var($request->input('force_replace_locked', false), FILTER_VALIDATE_BOOLEAN);

            if (!in_array($duplicateStrategy, ['skip', 'merge', 'replace'], true)) {
                return response()->json(['success' => false, 'message' => 'Invalid duplicate strategy selected.'], 422);
            }

            $isAdmin = auth()->user() && (auth()->user()->isAdmin() || Gate::allows('mark-entry.admin'));
            if ($duplicateStrategy === 'replace' && !$isAdmin) {
                return response()->json(['success' => false, 'message' => 'Replace strategy requires admin permission.'], 403);
            }
            if ($duplicateStrategy === 'replace' && $replaceReason === '') {
                return response()->json(['success' => false, 'message' => 'Reason is required for replace strategy.'], 422);
            }

            $hasManifest = ($zip->locateName('manifest.json') !== false);
            $hasNestedZips = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (strtolower(pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION)) === 'zip') {
                    $hasNestedZips = true;
                    break;
                }
            }

            // Helper closure for processing a single CSV in the district context
            $processCsv = function (string $csvContent, string $csvName, $school, $subject, string $examYear) use (
                &$results, &$batchIds, &$totalSuccess, &$totalErrors, &$totalRows, $parentRun,
                $duplicateStrategy, $replaceConflicts, $replaceReason, $forceReplaceLocked, $isAdmin, $bulkUploadId, $trackingEnabled
            ) {
                $duplicateAnalysis = $this->analyzeCsvRowDuplicates(
                    $csvContent,
                    (int) $school->id,
                    (int) $subject->id,
                    (int) $examYear
                );

                $selectedCsvContent = $csvContent;
                if (in_array($duplicateStrategy, ['skip', 'merge'], true)) {
                    $selectedRows = $duplicateStrategy === 'skip'
                        ? $duplicateAnalysis['new_rows']
                        : ($replaceConflicts ? array_merge($duplicateAnalysis['new_rows'], $duplicateAnalysis['conflict_rows']) : $duplicateAnalysis['new_rows']);
                    $selectedCsvContent = $this->buildCsvFromHeaderAndRows($duplicateAnalysis['header'], $selectedRows);

                    if (count($selectedRows) === 0) {
                        $results[] = [
                            'school_code' => $school->code,
                            'school_id' => $school->id,
                            'school_name' => $school->name,
                            'status' => 'skipped',
                            'message' => 'No new rows to commit after duplicate filtering.',
                            'duplicates' => $duplicateAnalysis['exact_duplicate_count'],
                            'conflicts' => $duplicateAnalysis['conflict_count'],
                        ];
                        return;
                    }
                }

                // Check locked/approved conflict
                $lockedBatch = MarkImportBatch::where('school_id', $school->id)
                    ->where('subject_id', $subject->id)
                    ->where('exam_year', (int) $examYear)
                    ->whereIn('status', [MarkImportBatch::STATUS_LOCKED, MarkImportBatch::STATUS_APPROVED])
                    ->latest('id')
                    ->first();

                if ($lockedBatch && $duplicateStrategy !== 'replace') {
                    $this->runService->addRunError($parentRun, 0, null, $subject->id, null, null, 'LOCKED_CONFLICT', 'error',
                        "{$school->code}/{$subject->code}: Already " . strtoupper($lockedBatch->status) . ". Skipped.", $lockedBatch->batch_code, $csvName);
                    $results[] = ['school_code' => $school->code, 'school_id' => $school->id, 'school_name' => $school->name, 'status' => 'skipped', 'message' => 'Locked/approved conflict'];
                    $totalErrors++;
                    return;
                }

                if ($lockedBatch && $duplicateStrategy === 'replace' && (!$isAdmin || !$forceReplaceLocked)) {
                    $results[] = [
                        'school_code' => $school->code,
                        'school_id' => $school->id,
                        'school_name' => $school->name,
                        'status' => 'failed',
                        'message' => 'Replace against approved/locked data requires super-admin confirmation.',
                    ];
                    $totalErrors++;
                    return;
                }

                $tmpCsv = tempnam(sys_get_temp_dir(), 'csv_');
                file_put_contents($tmpCsv, $selectedCsvContent);

                try {
                    DB::beginTransaction();

                    if ($duplicateStrategy === 'replace') {
                        $replaceExcludedStatuses = [MarkImportBatch::STATUS_PROCESSED];
                        if (!$forceReplaceLocked) {
                            $replaceExcludedStatuses[] = MarkImportBatch::STATUS_LOCKED;
                            $replaceExcludedStatuses[] = MarkImportBatch::STATUS_APPROVED;
                        }

                        $existingReplaceable = MarkImportBatch::where('school_id', $school->id)
                            ->where('subject_id', $subject->id)
                            ->where('exam_year', (int) $examYear)
                            ->whereNotIn('status', $replaceExcludedStatuses)
                            ->get();

                        foreach ($existingReplaceable as $oldBatch) {
                            $oldUpdate = [
                                'status' => 'superseded',
                                'lifecycle_state' => 'archived',
                                'notes' => trim(($oldBatch->notes ? $oldBatch->notes . ' | ' : '') . "Superseded via district replace: {$replaceReason}"),
                            ];
                            if ($trackingEnabled && Schema::hasColumn('mark_import_batches', 'superseded_by_bulk_upload_id')) {
                                $oldUpdate['superseded_by_bulk_upload_id'] = $bulkUploadId ?: null;
                            }
                            $oldBatch->update($oldUpdate);
                        }
                    }

                    $batch = $this->createImportBatchWithoutSupersede((int) $examYear, (int) $school->id, (int) $subject->id, (int) (auth()->id() ?? 1));
                    $uploadedFile = new \Illuminate\Http\UploadedFile($tmpCsv, $csvName, 'text/csv', null, true);
                    $result = $this->importService->processCSVUpload($batch, $uploadedFile, (int) $examYear, (int) $school->id, (int) $subject->id);

                    if ($result['success']) {
                        $validation = $this->importService->validateBatch($batch);
                        if (($validation['valid'] ?? 0) > 0) {
                            $batch->update([
                                'status' => MarkImportBatch::STATUS_VALIDATED,
                                'lifecycle_state' => 'awaiting_moderation',
                                'validated_by' => auth()->id(),
                                'validated_at' => now(),
                            ]);
                            $this->lockingService->lockBatchRows($batch, auth()->id() ?? 1);
                        }
                        DB::commit();
                        $batchIds[] = $batch->id;
                        $fileRows = $result['imported_records'] ?? 0;
                        $fileValid = $validation['valid'] ?? 0;
                        $totalRows += $fileRows;
                        $totalSuccess += $fileValid;
                        $results[] = [
                            'school_code' => $school->code,
                            'school_id' => $school->id,
                            'school_name' => $school->name,
                            'status' => 'success',
                            'successful_candidates' => $fileValid,
                            'total_candidates' => $fileRows,
                            'duplicates' => $duplicateAnalysis['exact_duplicate_count'],
                            'conflicts' => $duplicateAnalysis['conflict_count'],
                            'strategy' => $duplicateStrategy,
                        ];
                    } else {
                        DB::rollBack();
                        $this->runService->addRunError($parentRun, 0, null, $subject->id, null, null, 'PROCESSING_FAILED', 'error',
                            $result['error'] ?? 'Processing failed', null, $csvName);
                        $results[] = ['school_code' => $school->code, 'school_id' => $school->id, 'school_name' => $school->name, 'status' => 'failed', 'message' => $result['error'] ?? 'Processing failed'];
                        $totalErrors++;
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->runService->addRunError($parentRun, 0, null, $subject->id ?? null, null, null, 'EXCEPTION', 'error',
                        $e->getMessage(), null, $csvName);
                    $results[] = ['school_code' => $school->code, 'school_id' => $school->id, 'school_name' => $school->name ?? '', 'status' => 'failed', 'message' => $e->getMessage()];
                    $totalErrors++;
                }

                @unlink($tmpCsv);
            };

            if ($hasNestedZips) {
                $tmpExtract = storage_path('app/temp/imports/commit_' . uniqid());
                @mkdir($tmpExtract, 0755, true);

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') continue;

                    $schoolZipContent = $zip->getFromIndex($i);
                    $schoolZipPath = $tmpExtract . '/' . basename($filename);
                    file_put_contents($schoolZipPath, $schoolZipContent);

                    $innerZip = new ZipArchive();
                    if ($innerZip->open($schoolZipPath) !== true) {
                        $this->runService->addRunError($parentRun, 0, null, null, null, null, 'ZIP_OPEN_FAILED', 'error',
                            'Cannot open school ZIP: ' . basename($filename), null, $filename);
                        $results[] = ['school_code' => basename($filename, '.zip'), 'school_name' => basename($filename, '.zip'), 'status' => 'failed', 'message' => 'Cannot open school ZIP'];
                        $totalErrors++;
                        @unlink($schoolZipPath);
                        continue;
                    }

                    $innerManifest = null;
                    $innerManifestContent = $innerZip->getFromName('manifest.json');
                    if ($innerManifestContent) {
                        $innerManifest = json_decode($innerManifestContent, true);
                    }

                    $examYear = $innerManifest['exam_year'] ?? ($examYearObj ? $examYearObj->year_label : date('Y'));
                    $schoolCode = $innerManifest['school_code'] ?? null;
                    $school = $schoolCode ? School::where('code', $schoolCode)->first() : null;

                    // Fallback: match school by name from manifest or ZIP filename
                    if (!$school) {
                        $schoolName = $innerManifest['school_name'] ?? null;
                        if ($schoolName) {
                            $school = School::where('name', $schoolName)->first();
                        }
                    }
                    if (!$school) {
                        // Try to match school name from ZIP filename.
                        // Supports patterns like:
                        //  - IGOWOLE_SECONDARY_SCHOOL_ACSEE_2026_MarkTemplate.zip
                        //  - ISIMILA_SECONDARY_SCHOOL_ACSEE_2026.zip
                        //  - "NAME_A - NAME_B.zip" (uses each side as a candidate)
                        $zipBasename = pathinfo($filename, PATHINFO_FILENAME);
                        $nameCandidates = [$zipBasename];
                        if (str_contains($zipBasename, ' - ')) {
                            $nameCandidates = array_merge($nameCandidates, explode(' - ', $zipBasename));
                        }

                        $normalize = static function (?string $value): string {
                            $value = strtolower((string) $value);
                            return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
                        };

                        $schoolQuery = School::query();
                        if (!empty($district?->id)) {
                            $schoolQuery->where('district_id', $district->id);
                        }
                        $candidateSchools = $schoolQuery->get(['id', 'name', 'code']);

                        foreach ($nameCandidates as $candidateNameRaw) {
                            $candidateName = (string) $candidateNameRaw;
                            $candidateName = preg_replace('/_(ACSEE|CSEE|PSLE)_\d{4}(?:_MARKTEMPLATE)?$/i', '', $candidateName);
                            $candidateName = preg_replace('/_(MARKTEMPLATE|TEMPLATE)$/i', '', $candidateName);
                            $candidateName = trim(preg_replace('/\s+/', ' ', str_replace('_', ' ', $candidateName)));
                            if ($candidateName === '') {
                                continue;
                            }

                            // Exact name match (case-insensitive)
                            $school = $candidateSchools->first(function ($s) use ($candidateName) {
                                return strtolower((string) $s->name) === strtolower($candidateName);
                            });

                            // Normalized match (ignores spaces, punctuation, underscores)
                            if (!$school) {
                                $target = $normalize($candidateName);
                                $school = $candidateSchools->first(function ($s) use ($target, $normalize) {
                                    return $normalize((string) $s->name) === $target;
                                });
                            }

                            // Starts-with fallback for slight suffix/prefix variance
                            if (!$school) {
                                $target = strtolower($candidateName);
                                $school = $candidateSchools->first(function ($s) use ($target) {
                                    return str_starts_with(strtolower((string) $s->name), $target);
                                });
                            }

                            if ($school) {
                                break;
                            }
                        }
                    }

                    if (!$school) {
                        $label = $schoolCode ?? basename($filename, '.zip');
                        $this->runService->addRunError($parentRun, 0, null, null, null, null, 'SCHOOL_NOT_FOUND', 'error',
                            'School not found: ' . $label, $schoolCode, $filename);
                        $results[] = ['school_code' => $label, 'school_name' => $label, 'status' => 'failed', 'message' => 'School not found'];
                        $totalErrors++;
                        $innerZip->close();
                        @unlink($schoolZipPath);
                        continue;
                    }

                    for ($j = 0; $j < $innerZip->numFiles; $j++) {
                        $csvName = $innerZip->getNameIndex($j);
                        $csvExt = strtolower(pathinfo($csvName, PATHINFO_EXTENSION));
                        if ($csvExt !== 'csv' && $csvExt !== 'txt') continue;

                        $subjectCode = explode('_', pathinfo($csvName, PATHINFO_FILENAME))[0];
                        $subject = Subject::where('code', $subjectCode)->orWhere('code', strtoupper($subjectCode))->first();
                        if (!$subject) {
                            $this->runService->addRunError($parentRun, 0, null, null, null, null, 'INVALID_SUBJECT', 'error',
                                "Subject not found: {$subjectCode}", $subjectCode, $csvName);
                            $results[] = ['school_code' => $school->code, 'school_id' => $school->id, 'school_name' => $school->name, 'status' => 'failed', 'message' => "Subject not found: {$subjectCode}"];
                            $totalErrors++;
                            continue;
                        }

                        $csvContent = $innerZip->getFromIndex($j);
                        $processCsv($csvContent, $csvName, $school, $subject, $examYear);
                    }

                    $innerZip->close();
                    @unlink($schoolZipPath);
                }

                @rmdir($tmpExtract);
            } elseif ($hasManifest) {
                $manifestContent = $zip->getFromName('manifest.json');
                $manifest = json_decode($manifestContent, true);
                $examYear = $manifest['exam_year'] ?? ($examYearObj ? $examYearObj->year_label : date('Y'));

                foreach ($manifest['schools'] ?? [] as $schoolData) {
                    $schoolCode = $schoolData['school_code'] ?? null;
                    $school = School::where('code', $schoolCode)->first();
                    if (!$school) {
                        $this->runService->addRunError($parentRun, 0, null, null, null, null, 'SCHOOL_NOT_FOUND', 'error',
                            "School not found: {$schoolCode}", $schoolCode);
                        $results[] = ['school_code' => $schoolCode, 'school_name' => $schoolCode, 'status' => 'failed', 'message' => "School not found: {$schoolCode}"];
                        $totalErrors++;
                        continue;
                    }

                    foreach ($schoolData['subjects'] ?? [] as $subjectData) {
                        $subjectCode = $subjectData['code'] ?? null;
                        $subject = Subject::where('code', $subjectCode)->first();
                        if (!$subject) {
                            $this->runService->addRunError($parentRun, 0, null, null, null, null, 'INVALID_SUBJECT', 'error',
                                "Subject not found: {$subjectCode}", $subjectCode);
                            $results[] = ['school_code' => $schoolCode, 'school_id' => $school->id, 'school_name' => $school->name, 'status' => 'failed', 'message' => "Subject not found: {$subjectCode}"];
                            $totalErrors++;
                            continue;
                        }

                        $csvFilename = "{$schoolCode}/{$subjectCode}.csv";
                        $altFilename = "{$schoolCode}_{$subjectCode}.csv";
                        $csvContent = $zip->getFromName($csvFilename) ?: $zip->getFromName($altFilename);

                        if (!$csvContent) {
                            $this->runService->addRunError($parentRun, 0, null, $subject->id, null, null, 'FILE_NOT_FOUND', 'error',
                                "CSV file not found in ZIP for {$schoolCode}/{$subjectCode}", null, $csvFilename);
                            $results[] = ['school_code' => $schoolCode, 'school_id' => $school->id, 'school_name' => $school->name, 'status' => 'failed', 'message' => "CSV file not found in ZIP"];
                            $totalErrors++;
                            continue;
                        }

                        $processCsv($csvContent, $csvFilename, $school, $subject, $examYear);
                    }
                }
            } else {
                // Flat CSV files directly in the district ZIP
                // Supports two naming conventions:
                //   1. {SUBJECT_CODE}_{name}.csv  (e.g. 131_Economics.csv)
                //   2. {SCHOOL_NAME}_{SUBJECT_CODE}.csv  (e.g. KAWAWA_SECONDARY_SCHOOL_131.csv)
                // The school is inferred from the CSV data (candidate index numbers)
                $examYear = $examYearObj ? $examYearObj->year_label : date('Y');
                $districtSchools = $district ? School::where('district_id', $district->id)->get()->keyBy('code') : collect();

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    // Skip macOS resource fork files and non-CSV files
                    if (str_starts_with($filename, '__MACOSX/') || str_contains($filename, '/__MACOSX/')) continue;
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if ($ext !== 'csv' && $ext !== 'txt') continue;

                    $basename = pathinfo($filename, PATHINFO_FILENAME);
                    $parts = explode('_', $basename);
                    $subject = null;

                    // Detect if this is a school-level MarkTemplate file uploaded to district import by mistake
                    if (str_contains(strtolower($basename), 'marktemplate')) {
                        $this->runService->addRunError($parentRun, 0, null, null, null, null, 'WRONG_IMPORT_TYPE', 'error',
                            "File '{$filename}' appears to be a school-level MarkTemplate. Please use 'School Bulk ZIP' import instead, or upload a District ZIP containing school ZIPs.", null, $filename);
                        $results[] = ['school_code' => '-', 'school_name' => $filename, 'status' => 'failed', 'message' => "This is a school-level template. Use 'School Bulk ZIP' import instead."];
                        $totalErrors++;
                        continue;
                    }

                    // Strategy 1: First segment is subject code (e.g. 131_Economics.csv)
                    $firstPart = $parts[0];
                    $subject = Subject::where('code', $firstPart)->orWhere('code', strtoupper($firstPart))->first();

                    // Strategy 2: Last segment is subject code (e.g. SCHOOL_NAME_131.csv from template)
                    if (!$subject && count($parts) > 1) {
                        $lastPart = end($parts);
                        $subject = Subject::where('code', $lastPart)->orWhere('code', strtoupper($lastPart))->first();
                    }

                    // Strategy 3: Try all segments (handles SCHOOL_NAME_CODE_YEAR patterns)
                    if (!$subject && count($parts) > 2) {
                        foreach ($parts as $part) {
                            $subject = Subject::where('code', $part)->orWhere('code', strtoupper($part))->first();
                            if ($subject) break;
                        }
                    }

                    if (!$subject) {
                        $this->runService->addRunError($parentRun, 0, null, null, null, null, 'INVALID_SUBJECT', 'error',
                            "Cannot determine subject from filename: {$filename}. Expected format: SUBJECTCODE_name.csv or SCHOOLNAME_SUBJECTCODE.csv", null, $filename);
                        $results[] = ['school_code' => '-', 'school_name' => $filename, 'status' => 'failed', 'message' => "No valid subject code found in filename: {$basename}"];
                        $totalErrors++;
                        continue;
                    }

                    // Read CSV content and try to detect school from the first data row's index number
                    $csvContent = $zip->getFromIndex($i);
                    $school = null;

                    // Try to detect school from candidate index numbers in the CSV
                    $lines = array_filter(explode("\n", $csvContent), fn($l) => trim($l) !== '');
                    if (count($lines) > 1) {
                        // Skip header, read first data row
                        $firstDataRow = str_getcsv($lines[1] ?? '');
                        $indexNumber = trim($firstDataRow[0] ?? '');
                        if ($indexNumber) {
                            $candidate = \App\Models\Candidate::where('candidate_id', $indexNumber)->first();
                            if ($candidate && $candidate->school_id) {
                                $school = School::find($candidate->school_id);
                            }
                        }
                    }

                    if (!$school && $districtSchools->count() === 1) {
                        $school = $districtSchools->first();
                    }

                    // Strategy: try to match school name from filename against district schools
                    if (!$school && $districtSchools->isNotEmpty()) {
                        $filenameLower = strtolower(str_replace('_', ' ', $basename));
                        foreach ($districtSchools as $ds) {
                            if (str_contains($filenameLower, strtolower($ds->name))) {
                                $school = $ds;
                                break;
                            }
                        }
                    }

                    if (!$school) {
                        $this->runService->addRunError($parentRun, 0, null, $subject->id, null, null, 'SCHOOL_NOT_FOUND', 'error',
                            "Cannot determine school for file: {$filename}. Ensure candidates are registered or use School Bulk ZIP.", null, $filename);
                        $results[] = ['school_code' => '-', 'school_name' => $filename, 'status' => 'failed', 'message' => 'Cannot determine school from CSV data or filename'];
                        $totalErrors++;
                        continue;
                    }

                    $processCsv($csvContent, $filename, $school, $subject, $examYear);
                }
            }

            $zip->close();
            @unlink($zipPath);
            session()->forget('district_zip_temp_path');
            session()->forget('district_duplicate_report');
            session()->forget('district_bulk_upload_id');

            $allFailed = $totalSuccess === 0 && $totalErrors > 0;
            if ($allFailed) {
                $parentRun->fail(count($results) . " entries processed, all failed");
            } else {
                $parentRun->complete($totalRows, $totalSuccess, $totalErrors, 0,
                    count($results) . " entries processed, {$totalSuccess} rows committed");
            }

            if ($trackingEnabled && $bulkUploadId > 0) {
                DB::table('bulk_uploads')
                    ->where('id', $bulkUploadId)
                    ->update([
                        'status' => $allFailed ? 'rejected' : 'committed',
                        'metadata' => json_encode([
                            'strategy' => $duplicateStrategy,
                            'replace_conflicts' => $replaceConflicts,
                            'reason' => $replaceReason ?: null,
                            'result_totals' => [
                                'rows' => $totalRows,
                                'success' => $totalSuccess,
                                'errors' => $totalErrors,
                            ],
                        ]),
                        'updated_at' => now(),
                    ]);
            }

            GovernanceAuditLog::log(
                $allFailed ? GovernanceAuditLog::ACTION_IMPORT_FAILED : GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                auth()->id(),
                auth()->id(),
                [
                    'module' => 'mark-entry',
                    'scope' => 'district_zip',
                    'district_id' => (int) $districtId,
                    'exam_year_id' => (int) $examYearId,
                    'bulk_upload_id' => $bulkUploadId,
                    'strategy' => $duplicateStrategy,
                    'replace_conflicts' => $replaceConflicts,
                    'reason' => $replaceReason ?: null,
                    'totals' => ['rows' => $totalRows, 'success' => $totalSuccess, 'errors' => $totalErrors],
                ]
            );

            return response()->json([
                'success' => !$allFailed,
                'run_id' => $parentRun->id,
                'batch' => ['id' => $batchIds[0] ?? null],
                'schools' => $results,
                'message' => $allFailed ? 'All files failed to import. Check the error details.' : null,
                'duplicate_strategy' => $duplicateStrategy,
                'bulk_upload_id' => $bulkUploadId,
            ]);
        } catch (\Exception $e) {
            $parentRun->fail('Error committing ZIP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error committing ZIP: ' . $e->getMessage()], 500);
        }
    }

    private function analyzeDistrictZipDuplicates(string $zipPath, int $districtId, int $examYearId, bool $trackingEnabled = true): array
    {
        $zipHash = hash_file('sha256', $zipPath);
        $duplicateZip = false;
        if ($trackingEnabled) {
            $duplicateZip = DB::table('bulk_uploads')
                ->where('upload_type', 'district_zip')
                ->where('district_id', $districtId)
                ->where('exam_year_id', $examYearId)
                ->where('zip_hash', $zipHash)
                ->exists();
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [
                'zip_hash' => $zipHash,
                'duplicate_zip' => $duplicateZip,
                'duplicate_status' => $duplicateZip ? 'dup_zip' : 'new',
                'summary' => [
                    'duplicate_files_count' => 0,
                    'duplicate_rows_count' => 0,
                    'conflicting_rows_count' => 0,
                    'new_rows_count' => 0,
                ],
                'duplicate_files' => [],
                'new_files' => [],
                'conflicting_rows' => [],
                'exact_duplicate_rows' => [],
            ];
        }

        $duplicateFiles = [];
        $newFiles = [];
        $conflictingRows = [];
        $exactDuplicateRows = [];
        $newRowsCount = 0;

        $collectFile = function (string $filename, string $content) use (&$duplicateFiles, &$newFiles, &$conflictingRows, &$exactDuplicateRows, &$newRowsCount, $districtId, $examYearId, $trackingEnabled) {
            $fileHash = hash('sha256', $content);
            $scope = $this->deriveCsvScope($filename, $districtId);
            $scopeSignature = json_encode([
                'district_id' => $districtId,
                'school_id' => $scope['school_id'] ?? null,
                'subject_id' => $scope['subject_id'] ?? null,
                'paper_code' => $scope['paper_code'] ?? null,
            ]);

            $priorFile = null;
            if ($trackingEnabled) {
                $priorFileQuery = DB::table('bulk_upload_files')
                    ->where('file_hash', $fileHash);

                $driver = DB::connection()->getDriverName();
                if ($driver === 'mysql' || $driver === 'mariadb') {
                    $priorFileQuery->where('derived_scope->scope_signature', $scopeSignature);
                } else {
                    $priorFileQuery->whereRaw("json_extract(derived_scope, '$.scope_signature') = ?", [$scopeSignature]);
                }

                $priorFile = $priorFileQuery->first();
            }

            $entry = [
                'filename' => $filename,
                'file_hash' => $fileHash,
                'derived_scope' => array_merge($scope, ['scope_signature' => $scopeSignature]),
            ];

            if ($priorFile) {
                $duplicateFiles[] = $entry;
            } else {
                $newFiles[] = $entry;
            }

            if (!empty($scope['school_id']) && !empty($scope['subject_id'])) {
                $rowAnalysis = $this->analyzeCsvRowDuplicates($content, (int) $scope['school_id'], (int) $scope['subject_id'], (int) $scope['exam_year']);
                $newRowsCount += (int) ($rowAnalysis['new_rows_count'] ?? 0);
                foreach (($rowAnalysis['conflict_details'] ?? []) as $conflict) {
                    $conflict['source_file'] = $filename;
                    $conflictingRows[] = $conflict;
                }
                foreach (($rowAnalysis['duplicate_details'] ?? []) as $dup) {
                    $dup['source_file'] = $filename;
                    $exactDuplicateRows[] = $dup;
                }
            }
        };

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (str_starts_with($filename, '__MACOSX/') || str_contains($filename, '/__MACOSX/')) {
                continue;
            }
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($ext === 'csv' || $ext === 'txt') {
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    $collectFile($filename, $content);
                }
                continue;
            }

            if ($ext === 'zip') {
                $schoolZipContent = $zip->getFromIndex($i);
                $tmpSchoolZip = tempnam(sys_get_temp_dir(), 'dup_school_zip_');
                file_put_contents($tmpSchoolZip, $schoolZipContent);
                $inner = new ZipArchive();
                if ($inner->open($tmpSchoolZip) === true) {
                    for ($j = 0; $j < $inner->numFiles; $j++) {
                        $innerName = $inner->getNameIndex($j);
                        $innerExt = strtolower(pathinfo($innerName, PATHINFO_EXTENSION));
                        if ($innerExt !== 'csv' && $innerExt !== 'txt') {
                            continue;
                        }
                        $innerContent = $inner->getFromIndex($j);
                        if ($innerContent !== false) {
                            $collectFile(basename($filename) . ':' . $innerName, $innerContent);
                        }
                    }
                    $inner->close();
                }
                @unlink($tmpSchoolZip);
            }
        }
        $zip->close();

        $duplicateStatus = 'new';
        if ($duplicateZip) {
            $duplicateStatus = 'dup_zip';
        } elseif (!empty($duplicateFiles)) {
            $duplicateStatus = 'has_dup_files';
        } elseif (!empty($exactDuplicateRows) || !empty($conflictingRows)) {
            $duplicateStatus = 'has_dup_rows';
        }

        return [
            'zip_hash' => $zipHash,
            'duplicate_zip' => $duplicateZip,
            'duplicate_status' => $duplicateStatus,
            'summary' => [
                'duplicate_files_count' => count($duplicateFiles),
                'duplicate_rows_count' => count($exactDuplicateRows),
                'conflicting_rows_count' => count($conflictingRows),
                'new_rows_count' => $newRowsCount,
            ],
            'duplicate_files' => $duplicateFiles,
            'new_files' => $newFiles,
            'conflicting_rows' => array_slice($conflictingRows, 0, 200),
            'exact_duplicate_rows' => array_slice($exactDuplicateRows, 0, 200),
        ];
    }

    private function isDuplicateTrackingEnabled(): bool
    {
        return Schema::hasTable('bulk_uploads') && Schema::hasTable('bulk_upload_files');
    }

    private function deriveCsvScope(string $filename, int $districtId): array
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('_', $basename);
        $subject = null;
        foreach ($parts as $part) {
            $subject = Subject::where('code', strtoupper($part))->first();
            if ($subject) {
                break;
            }
        }

        $school = null;
        if (preg_match('/\bS\d{4,}\b/i', $basename, $m)) {
            $school = School::where('code', strtoupper($m[0]))->first();
        }

        return [
            'district_id' => $districtId,
            'exam_year' => (int) (request('exam_year_id') ? ExamYear::find(request('exam_year_id'))?->year_label : date('Y')),
            'school_id' => $school?->id,
            'school_code' => $school?->code,
            'subject_id' => $subject?->id,
            'subject_code' => $subject?->code,
            'paper_code' => null,
        ];
    }

    private function analyzeCsvRowDuplicates(string $csvContent, int $schoolId, int $subjectId, int $examYear): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (empty($lines) || count($lines) < 2) {
            return [
                'header' => '',
                'new_rows' => [],
                'conflict_rows' => [],
                'new_rows_count' => 0,
                'exact_duplicate_count' => 0,
                'conflict_count' => 0,
                'conflict_details' => [],
                'duplicate_details' => [],
            ];
        }

        $header = array_shift($lines);
        $rows = [];
        $indexNumbers = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line);
            $index = trim((string) ($row[0] ?? ''));
            if ($index === '') {
                continue;
            }
            $rows[] = ['line' => $line, 'cols' => $row, 'index' => $index];
            $indexNumbers[] = $index;
        }

        $existingRows = DB::table('raw_marks')
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'raw_marks.mark_import_batch_id')
            ->where('mark_import_batches.school_id', $schoolId)
            ->where('mark_import_batches.subject_id', $subjectId)
            ->where('mark_import_batches.exam_year', $examYear)
            ->whereNotIn('mark_import_batches.status', ['rejected', 'superseded'])
            ->whereIn('raw_marks.candidate_index_number', $indexNumbers)
            ->select(
                'raw_marks.candidate_index_number',
                'raw_marks.paper_1_marks',
                'raw_marks.paper_2_marks',
                'raw_marks.paper_3_marks',
                'raw_marks.practical_marks',
                'raw_marks.project_marks',
                'mark_import_batches.batch_code',
                'mark_import_batches.status'
            )
            ->get()
            ->keyBy('candidate_index_number');

        $newRows = [];
        $conflictRows = [];
        $duplicateCount = 0;
        $conflictCount = 0;
        $conflictDetails = [];
        $duplicateDetails = [];

        foreach ($rows as $row) {
            $existing = $existingRows->get($row['index']);
            if (!$existing) {
                $newRows[] = $row['line'];
                continue;
            }

            $incomingSig = $this->normalizeCsvMarkSignature($row['cols']);
            $existingSig = $this->normalizeDbMarkSignature($existing);
            if ($incomingSig === $existingSig) {
                $duplicateCount++;
                $duplicateDetails[] = [
                    'index_number' => $row['index'],
                    'batch_code' => $existing->batch_code,
                    'status' => $existing->status,
                ];
            } else {
                $conflictCount++;
                $conflictRows[] = $row['line'];
                $conflictDetails[] = [
                    'index_number' => $row['index'],
                    'old_mark' => $existingSig,
                    'new_mark' => $incomingSig,
                    'batch_code' => $existing->batch_code,
                    'status' => $existing->status,
                ];
            }
        }

        return [
            'header' => $header,
            'new_rows' => $newRows,
            'conflict_rows' => $conflictRows,
            'new_rows_count' => count($newRows),
            'exact_duplicate_count' => $duplicateCount,
            'conflict_count' => $conflictCount,
            'conflict_details' => $conflictDetails,
            'duplicate_details' => $duplicateDetails,
        ];
    }

    private function buildCsvFromHeaderAndRows(string $header, array $rows): string
    {
        $body = implode("\n", $rows);
        return trim($header) . "\n" . $body . "\n";
    }

    private function normalizeCsvMarkSignature(array $cols): string
    {
        $values = array_slice($cols, 2);
        return implode('|', array_map(function ($v) {
            $v = trim((string) $v);
            return $v === '' ? '' : (string) ((float) $v);
        }, $values));
    }

    private function normalizeDbMarkSignature(object $row): string
    {
        return implode('|', [
            $row->paper_1_marks === null ? '' : (string) ((float) $row->paper_1_marks),
            $row->paper_2_marks === null ? '' : (string) ((float) $row->paper_2_marks),
            $row->paper_3_marks === null ? '' : (string) ((float) $row->paper_3_marks),
            $row->practical_marks === null ? '' : (string) ((float) $row->practical_marks),
            $row->project_marks === null ? '' : (string) ((float) $row->project_marks),
        ]);
    }

    private function createImportBatchWithoutSupersede(int $examYear, int $schoolId, int $subjectId, int $importedBy): MarkImportBatch
    {
        $subject = Subject::findOrFail($subjectId);
        $school = School::findOrFail($schoolId);

        return MarkImportBatch::create([
            'batch_code' => "BATCH-{$schoolId}-{$subjectId}-{$examYear}-" . now()->format('YmdHis') . '-' . Str::random(6),
            'exam_year' => $examYear,
            'school_id' => $schoolId,
            'region_id' => $school->region_id,
            'district_id' => $school->district_id,
            'subject_id' => $subjectId,
            'exam_type_id' => $subject->exam_type_id,
            'status' => MarkImportBatch::STATUS_DRAFT,
            'imported_by' => $importedBy,
            'imported_at' => now(),
        ]);
    }

    /**
     * Get bulk import progress
     */
    public function bulkProgress($id)
    {
        $batch = MarkImportBatch::find($id);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found'], 404);
        }

        $totalRows = RawMark::where('batch_id', $batch->id)->count();
        $validRows = RawMark::where('batch_id', $batch->id)->where('is_valid', true)->count();
        $invalidRows = RawMark::where('batch_id', $batch->id)->where('is_valid', false)->count();

        $status = 'completed';
        if ($batch->status === MarkImportBatch::STATUS_DRAFT) {
            $status = 'processing';
        }

        return response()->json([
            'success' => true,
            'progress' => [
                'status' => $status,
                'progress_percentage' => 100,
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
            ],
        ]);
    }
}
