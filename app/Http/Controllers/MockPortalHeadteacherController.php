<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\School;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;

class MockPortalHeadteacherController extends Controller
{
    /**
     * Show the headteacher's school landing page.
     * Mirrors the look of the public results portal.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403, 'Access denied. This page is for registered Headteachers only.');
        }

        $school = $this->resolveSchoolForUser($user, ['council', 'region', 'district']);

        if (!$school) {
            abort(404, 'School record not found. Please contact your District Academic Officer.');
        }

        $deadlineDate = Carbon::parse('2026-04-20')->addDays(31)->startOfDay();
        $registrationDeadline = $deadlineDate->format('j M Y');
        $deadlineTimestamp = $deadlineDate->timestamp * 1000;
        $daysRemaining = (int) ceil(now()->diffInHours($deadlineDate, false) / 24);

        return view('mock-portal.headteacher.dashboard', compact('school', 'user', 'registrationDeadline', 'deadlineTimestamp', 'daysRemaining'));
    }

    /**
     * Show the candidate management/upload page.
     */
    public function candidate()
    {
        $user = Auth::user();

        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $school = $this->resolveSchoolForUser($user, ['council', 'region', 'district']);

        if (!$school) {
            abort(404);
        }

        $candidates = Candidate::where('school_id', $school->id)
            ->orderBy('candidate_id')
            ->paginate(20);

        $stats = [
            'total' => $candidates->total(),
            'boys'  => Candidate::where('school_id', $school->id)->where('gender', 'M')->count(),
            'girls' => Candidate::where('school_id', $school->id)->where('gender', 'F')->count(),
        ];

        $windowOpen      = true;
        $deadlineDate    = Carbon::parse('2026-04-20')->addDays(31)->startOfDay();
        $registrationDeadline = $deadlineDate->format('j M Y');
        $daysRemaining   = (int) ceil(now()->diffInHours($deadlineDate, false) / 24);
        $deadlineTimestamp = $deadlineDate->timestamp * 1000; // For JS

        if ($daysRemaining < 0) {
            $windowOpen    = false;
            $daysRemaining = 0;
        }

        return view('mock-portal.headteacher.candidate', compact(
            'school', 'stats', 'candidates', 'windowOpen', 'daysRemaining', 'registrationDeadline', 'deadlineTimestamp'
        ));
    }

    /**
     * Download the CSV template for candidate upload.
     */
    public function downloadTemplate()
    {
        $user = Auth::user();
        $school = $this->resolveSchoolForUser($user);

        $schoolCode = $school ? $school->code : 'PS0101001';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="TASIDO_2026_Candidate_Template.csv"',
        ];

        $callback = function () use ($schoolCode) {
            $file = fopen('php://output', 'w');
            // Header row
            fputcsv($file, ['Index Number', 'PReM No.', 'Full Name', 'Sex']);
            // Sample rows
            fputcsv($file, ["{$schoolCode}-0001", '20261234567', 'Amina Juma Hassan', 'F']);
            fputcsv($file, ["{$schoolCode}-0002", '20261234568', 'Emmanuel Mwenda', 'M']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update school ownership
     */
    public function updateOwnership(Request $request)
    {
        if (\App\Http\Controllers\MockPortalAuthController::mockRegistrationExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration period has expired. This action is no longer available.',
            ], 403);
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'ownership' => 'required|in:GOVERNMENT,NON-GOVERNMENT'
        ]);

        $school = $this->resolveSchoolForUser($user);
        if (!$school) {
            return back()->with('error', 'School record not found.');
        }

        $school->ownership = $request->ownership;
        $school->save();

        return $this->calPdfReport($request);
    }

    /**
     * Generate CAL (SUBJECT COLLECTIVE ATTENDANCE LIST) zip file
     */
    public function calPdfReport(Request $request)
    {
        if (\App\Http\Controllers\MockPortalAuthController::mockRegistrationExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration period has expired. This action is no longer available.',
            ], 403);
        }

        @set_time_limit(120);
        @ini_set('max_execution_time', '120');
        @ini_set('memory_limit', '512M');

        $user = Auth::user();
        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $school = $this->resolveSchoolForUser($user, ['council', 'region', 'district']);
        if (!$school) {
            return back()->with('error', 'School record not found. Please contact your DAO.');
        }

        $candidates = Candidate::where('school_id', $school->id)
            ->orderBy('candidate_id')
            ->get();

        if ($candidates->isEmpty()) {
            return back()->with('error', 'No candidates registered for your school. Please register candidates first.');
        }

        $subjects = \App\Models\Subject::whereHas('examType', function($q) {
            $q->where('code', 'PSLE');
        })->orderBy('code')->get();

        if ($subjects->isEmpty()) {
            return back()->with('error', 'PSLE Subjects not found. Please contact administrator.');
        }

        $zipFileName = 'CAL_' . $school->code . '_' . now()->format('Ymd_His') . '.zip';
        $tempZip = tempnam(sys_get_temp_dir(), 'cal_');
        $emblemData = $this->getEmblemDataUri();
        
        $zip = new \ZipArchive();
        if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($subjects as $subject) {
                $pdfFileName = $school->code . '_' . preg_replace('/[^a-z0-9]+/i', '_', $subject->name) . '_CAL.pdf';
                
                $schoolCodeStr = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $school->code ?? 'SCH'), 0, 8));
                $subjectCodeStr = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $subject->code ?? 'SUB'), 0, 8));
                $barcodePayload = 'CAL-' . $schoolCodeStr . '-' . $subjectCodeStr . '-' . now()->format('Ymd-His');
                $barcodeBars = $this->getCode39Bars($barcodePayload);

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mock-portal.headteacher.cal-report-pdf', compact('school', 'candidates', 'subject', 'barcodePayload', 'barcodeBars', 'emblemData'))
                    ->setPaper('a4', 'portrait')
                    ->setOption('isPhpEnabled', true);

                $zip->addFromString($pdfFileName, $pdf->output());
            }
            $zip->close();
        } else {
            return back()->with('error', 'Failed to generate CAL zip file. Please try again.');
        }

        return response()->download($tempZip, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Handle CSV upload and import candidates.
     */
    public function uploadCandidates(Request $request)
    {
        $user = Auth::user();

        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'replace_existing' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'The upload could not start.')
                ->with('upload_error_reasons', $validator->errors()->all());
        }

        // 31-day registration window check (Launch: 20 April 2026)
        $deadline = Carbon::parse('2026-04-20')->addDays(31)->startOfDay();
        $expired = \App\Http\Controllers\MockPortalAuthController::mockRegistrationExpired();

        Log::info('Mock registration expiry check', [
            'user_id' => auth()->id(),
            'path' => request()->path(),
            'expired' => $expired,
        ]);

        if ($expired) {
            return back()
                ->with('error', 'The 31-day registration window has closed (deadline: ' . $deadline->format('d M Y') . '). Uploads are no longer accepted.')
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'Uploads are no longer accepted.')
                ->with('upload_error_reasons', [
                    'The 31-day registration window has closed.',
                    'Deadline: ' . $deadline->format('d M Y') . '.',
                ]);
        }

        $school = $this->resolveSchoolForUser($user);

        if (!$school) {
            return back()
                ->with('error', 'Your account is not linked to a school. Contact your DAO.')
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'Your upload cannot be processed.')
                ->with('upload_error_reasons', [
                    'Your account is not linked to a school.',
                    'Contact your DAO for account linking support.',
                ]);
        }

        $file = $request->file('csv_file');
        try {
            $rows = $this->readCandidateUploadRows($file);
        } catch (\Throwable $e) {
            Log::error('Headteacher candidate upload file could not be read.', [
                'school_id' => $school->id,
                'exception' => $e,
            ]);

            return back()
                ->with('error', 'The uploaded file could not be read.')
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'The uploaded file could not be read.')
                ->with('upload_error_reasons', [
                    'The file may be damaged, empty, or saved in an unsupported format.',
                    'Use the official template and upload a valid CSV, XLSX, or XLS file.',
                ]);
        }

        if (empty($rows)) {
            return back()
                ->with('error', 'The uploaded file is empty or could not be read.')
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'The uploaded file is empty or unreadable.')
                ->with('upload_error_reasons', [
                    'No usable rows were found in the file.',
                    'Make sure the first row contains the headings and the next rows contain candidate data.',
                ]);
        }

        array_shift($rows); // skip header row

        $imported = 0;
        $replaced = 0;
        $unchanged = 0;
        $errors   = [];
        $rowNum   = 1;
        $replaceExisting = $request->boolean('replace_existing');
        $psleExamType = ExamType::where('code', 'PSLE')->first();
        $activeExamYear = ExamYear::where('is_active', true)->first();
        $pendingUpserts = [];
        $seenIndexNumbers = [];
        $seenPremNumbers = [];

        if (!$psleExamType || !$activeExamYear) {
            return back()
                ->with('error', 'PSLE exam type or active exam year is not configured. Contact the system administrator.')
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'The upload cannot continue.')
                ->with('upload_error_reasons', [
                    'PSLE exam type is not configured, or there is no active exam year.',
                    'Contact the system administrator before trying again.',
                ]);
        }

        foreach ($rows as $row) {
            $rowNum++;

            if (count($row) < 4) {
                $errors[] = "Row {$rowNum}: not enough columns (expected at least 4).";
                continue;
            }

            [$indexNo, $premNo, $fullName, $sex] = array_pad($row, 4, null);

            $indexNo  = trim($indexNo);
            $premNo   = trim($premNo);
            $fullName = trim($fullName);
            $sex      = strtoupper(trim($sex));

            if (!preg_match($this->indexNumberPatternForSchool($school), $indexNo)) {
                $errors[] = "Row {$rowNum}: Invalid Index Number format. Must be {$school->code}-0001.";
                continue;
            }

            // Validate School Centre Number match
            if (strpos($indexNo, $school->code) !== 0) {
                $errors[] = "Row {$rowNum}: Index Number does not match school centre number ({$school->code}).";
                continue;
            }

            // Validate PReM Number Format: Exactly 11 digits
            if (!preg_match('/^[0-9]{11}$/', $premNo)) {
                $errors[] = "Row {$rowNum}: Invalid PReM Number. Must be exactly 11 digits.";
                continue;
            }

            if (empty($fullName)) {
                $errors[] = "Row {$rowNum}: Full Name is required.";
                continue;
            }

            if (!in_array($sex, ['M', 'F'])) {
                $errors[] = "Row {$rowNum}: Sex must be M or F.";
                continue;
            }

            if (isset($seenIndexNumbers[$indexNo])) {
                $errors[] = "Row {$rowNum}: Duplicate Index Number {$indexNo} also appears on row {$seenIndexNumbers[$indexNo]}.";
                continue;
            }

            $seenIndexNumbers[$indexNo] = $rowNum;

            if (isset($seenPremNumbers[$premNo])) {
                $errors[] = "Row {$rowNum}: Duplicate PReM Number {$premNo} also appears on row {$seenPremNumbers[$premNo]}.";
                continue;
            }

            $seenPremNumbers[$premNo] = $rowNum;

            $existingCandidate = Candidate::where('candidate_id', $indexNo)->first();
            if ($existingCandidate) {
                if ((int) $existingCandidate->school_id !== (int) $school->id) {
                    $otherSchool = $existingCandidate->school;
                    $prefix = explode('-', $indexNo)[0];
                    if ($school->code === $prefix && (!$otherSchool || $otherSchool->code !== $prefix)) {
                        // This candidate is mismatched to another school but belongs to this one.
                        // We will "reclaim" it by updating the school_id during the transaction.
                        GovernanceAuditLog::log(
                            GovernanceAuditLog::ACTION_CANDIDATE_RECLAIMED,
                            userId: auth()->id(),
                            data: [
                                'candidate_id' => $indexNo,
                                'old_school_id' => $existingCandidate->school_id,
                                'old_school_name' => $otherSchool->name ?? 'Unknown',
                                'new_school_id' => $school->id,
                                'new_school_name' => $school->name,
                                'method' => 'bulk_upload'
                            ]
                        );
                    } else {
                        $errors[] = "Row {$rowNum}: Index Number {$indexNo} is already assigned to another school centre.";
                        continue;
                    }
                }

                $sameDetails = $existingCandidate->prem_no === $premNo
                    && trim((string) $existingCandidate->full_name) === $fullName
                    && strtoupper(trim((string) $existingCandidate->gender)) === $sex;

                if ($sameDetails) {
                    $unchanged++;
                    continue;
                }

                if (!$replaceExisting) {
                    $errors[] = "Row {$rowNum}: Index Number {$indexNo} already exists with different details. Turn on Replace Existing in the upload modal or use Edit for that candidate.";
                    continue;
                }
            }

            $existingPremCandidate = Candidate::where('prem_no', $premNo)->first();
            if ($existingPremCandidate && (!$existingCandidate || $existingPremCandidate->id !== $existingCandidate->id)) {
                // Potential reclaim by PReM
                if ((int) $existingPremCandidate->school_id !== (int) $school->id) {
                    $otherSchool = $existingPremCandidate->school;
                    GovernanceAuditLog::log(
                        GovernanceAuditLog::ACTION_CANDIDATE_RECLAIMED,
                        userId: auth()->id(),
                        data: [
                            'candidate_id' => $indexNo,
                            'old_candidate_id' => $existingPremCandidate->candidate_id,
                            'old_school_id' => $existingPremCandidate->school_id,
                            'old_school_name' => $otherSchool->name ?? 'Unknown',
                            'new_school_id' => $school->id,
                            'new_school_name' => $school->name,
                            'method' => 'bulk_upload_prem_reclaim'
                        ]
                    );

                    $pendingUpserts[] = [
                        'mode' => 'replace',
                        'existing_candidate_id' => $existingPremCandidate->id,
                        'candidate_id' => $indexNo,
                        'prem_no' => $premNo,
                        'full_name' => $fullName,
                        'gender' => $sex,
                    ];
                    continue;
                }

                $errors[] = "Row {$rowNum}: PReM Number {$premNo} is already assigned to candidate {$existingPremCandidate->candidate_id} in your school.";
                continue;
            }

            $pendingUpserts[] = [
                'mode' => $existingCandidate ? 'replace' : 'create',
                'existing_candidate_id' => $existingCandidate?->id,
                'candidate_id' => $indexNo,
                'prem_no' => $premNo,
                'full_name' => $fullName,
                'gender' => $sex,
            ];
        }

        if (!empty($errors)) {
            return back()
                ->with('error', 'Upload stopped. No candidates were changed.')
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'Upload stopped. No candidates were changed.')
                ->with('upload_error_reasons', array_slice($errors, 0, 10))
                ->with('upload_error_total', count($errors));
        }

        try {
            DB::transaction(function () use (
                $school,
                $psleExamType,
                $activeExamYear,
                $pendingUpserts,
                &$imported,
                &$replaced
            ) {
                foreach ($pendingUpserts as $row) {
                    if ($row['mode'] === 'replace') {
                        $candidate = Candidate::findOrFail($row['existing_candidate_id']);
                        
                        // Handle reclaims: ensure school_id is correct
                        if ((int) $candidate->school_id !== (int) $school->id) {
                            $candidate->school_id = $school->id;
                        }

                        $candidate->candidate_id = $row['candidate_id'];
                        $candidate->full_name = $row['full_name'];
                        $candidate->gender = $row['gender'];
                        $candidate->prem_no = $row['prem_no'];
                        $candidate->candidate_type = 'SCHOOL';
                        $candidate->exam_type = 'PSLE';
                        $candidate->status = 'registered';
                        $candidate->rejection_reason = null;
                        $candidate->save();
                        $replaced++;
                    } else {
                        $candidate = Candidate::create([
                            'school_id' => $school->id,
                            'candidate_id' => $row['candidate_id'],
                            'full_name' => $row['full_name'],
                            'gender' => $row['gender'],
                            'prem_no' => $row['prem_no'],
                            'candidate_type' => 'SCHOOL',
                            'status' => 'registered',
                            'exam_type' => 'PSLE',
                        ]);

                        $imported++;
                    }

                    $this->registerCandidateForPsle($candidate, $psleExamType, $activeExamYear);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Headteacher candidate upload failed.', [
                'school_id' => $school->id,
                'exception' => $e,
            ]);

            return back()
                ->with('error', 'Upload failed. No candidates were changed. ' . $e->getMessage())
                ->with('upload_modal_open', true)
                ->with('upload_error_title', 'Upload failed before any candidates were saved.')
                ->with('upload_error_reasons', [
                    $e->getMessage(),
                    'Review the file and try again. If this continues, contact the system administrator.',
                ]);
        }

        $message = "Successfully imported {$imported} candidate(s) for {$school->name}.";
        if ($replaced > 0) {
            $message .= " Replaced {$replaced} existing candidate(s) from this school's list.";
        }
        if ($unchanged > 0) {
            $message .= " {$unchanged} existing candidate(s) were already up to date and left unchanged.";
        }

        return back()->with('success', $message);
    }

    public function updateCandidate(Request $request, Candidate $candidate)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $userSchool = $this->resolveSchoolForUser($user);
        if (!$userSchool) {
            return back()->with('error', 'Your account is not linked to a school. Contact your DAO.');
        }

        if (!$this->candidateBelongsToSchool($candidate, $userSchool, $user)) {
            abort(403, 'You can only manage candidates registered under your own school.');
        }

        try {
            $request->validate([
                'candidate_id' => ['required', 'string', 'regex:' . $this->indexNumberPatternForSchool($userSchool)],
                'full_name'    => 'required|string|max:255',
                'gender'       => 'required|in:M,F',
                'prem_no'      => 'required|string|size:11|regex:/^[0-9]+$/',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)
                ->withInput()
                ->with('modal_type', 'edit')
                ->with('editing_candidate_id', $candidate->id);
        }

        $candidate->candidate_id = $request->input('candidate_id');
        $candidate->full_name = $request->input('full_name');
        $candidate->gender = $request->input('gender');
        $candidate->prem_no = $request->input('prem_no');
        
        // If it was rejected, mark it as registered again upon update
        if ($candidate->status === 'rejected') {
            $candidate->status = 'registered';
            $candidate->rejection_reason = null; // Clear the reason
        }

        $candidate->save();

        return back()->with('success', "Candidate '{$candidate->full_name}' updated successfully.");
    }

    public function storeCandidate(Request $request)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $school = $this->resolveSchoolForUser($user);
        if (!$school) {
            return back()->with('error', 'Your account is not linked to a school. Contact your DAO.');
        }

        $deadline = Carbon::parse('2026-04-20')->addDays(31)->startOfDay();
        $expired = \App\Http\Controllers\MockPortalAuthController::mockRegistrationExpired();

        Log::info('Mock registration expiry check', [
            'user_id' => auth()->id(),
            'path' => request()->path(),
            'expired' => $expired,
        ]);

        if ($expired) {
            return back()->with('error', 'The 31-day registration window has closed (deadline: ' . $deadline->format('d M Y') . '). Manual registration is no longer accepted.');
        }

        $psleExamType = ExamType::where('code', 'PSLE')->first();
        $activeExamYear = ExamYear::where('is_active', true)->first();

        if (!$psleExamType || !$activeExamYear) {
            return back()->with('error', 'PSLE exam type or active exam year is not configured. Contact the system administrator.');
        }

        try {
            $request->validate([
                'candidate_id' => ['required', 'string', 'regex:' . $this->indexNumberPatternForSchool($school)],
                'full_name'    => 'required|string|max:255',
                'gender'       => 'required|in:M,F',
                'prem_no'      => 'required|string|size:11|regex:/^[0-9]+$/',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)
                ->withInput()
                ->with('modal_type', 'add');
        }

        $existingCandidate = Candidate::where('candidate_id', $request->candidate_id)->first();
        if ($existingCandidate) {
            // Check if this is a mismatch we can resolve automatically
            if ((int) $existingCandidate->school_id !== (int) $school->id) {
                $otherSchool = $existingCandidate->school;
                $prefix = explode('-', $request->candidate_id)[0];
                
                if ($school->code === $prefix && (!$otherSchool || $otherSchool->code !== $prefix)) {
                    GovernanceAuditLog::log(
                        GovernanceAuditLog::ACTION_CANDIDATE_RECLAIMED,
                        userId: auth()->id(),
                        data: [
                            'candidate_id' => $existingCandidate->candidate_id,
                            'old_school_id' => $existingCandidate->school_id,
                            'old_school_name' => $otherSchool->name ?? 'Unknown',
                            'new_school_id' => $school->id,
                            'new_school_name' => $school->name,
                            'method' => 'single_registration'
                        ]
                    );
                    
                    DB::transaction(function() use ($existingCandidate, $school, $request, $psleExamType, $activeExamYear) {
                        $existingCandidate->school_id = $school->id;
                        $existingCandidate->full_name = $request->full_name;
                        $existingCandidate->gender = $request->gender;
                        $existingCandidate->prem_no = $request->prem_no;
                        $existingCandidate->status = 'registered';
                        $existingCandidate->save();
                        
                        $this->registerCandidateForPsle($existingCandidate, $psleExamType, $activeExamYear);
                    });

                    return back()->with('success', "Candidate '{$request->full_name}' was found linked to another centre but has been successfully reclaimed for your school.");
                }
            }

            $message = (int) $existingCandidate->school_id === (int) $school->id
                ? "Index Number {$request->candidate_id} already exists for this school. Use Edit instead of Register Candidate."
                : "Index Number {$request->candidate_id} is already assigned to another school centre. Contact your DAO.";

            return back()
                ->withInput()
                ->with('modal_type', 'add')
                ->with('error', $message);
        }

        $existingPremCandidate = Candidate::where('prem_no', $request->prem_no)->first();
        if (
            $existingPremCandidate
            && $existingPremCandidate->candidate_id !== $request->candidate_id
        ) {
            // Check if this is a reclaimable PReM conflict
            if ((int) $existingPremCandidate->school_id !== (int) $school->id) {
                $otherSchool = $existingPremCandidate->school;
                
                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_CANDIDATE_RECLAIMED,
                    userId: auth()->id(),
                    data: [
                        'candidate_id' => $request->candidate_id,
                        'old_candidate_id' => $existingPremCandidate->candidate_id,
                        'old_school_id' => $existingPremCandidate->school_id,
                        'old_school_name' => $otherSchool->name ?? 'Unknown',
                        'new_school_id' => $school->id,
                        'new_school_name' => $school->name,
                        'method' => 'single_registration_prem_reclaim'
                    ]
                );
                
                DB::transaction(function() use ($existingPremCandidate, $school, $request, $psleExamType, $activeExamYear) {
                    $existingPremCandidate->school_id = $school->id;
                    $existingPremCandidate->candidate_id = $request->candidate_id;
                    $existingPremCandidate->full_name = $request->full_name;
                    $existingPremCandidate->gender = $request->gender;
                    $existingPremCandidate->prem_no = $request->prem_no;
                    $existingPremCandidate->status = 'registered';
                    $existingPremCandidate->save();
                    
                    $this->registerCandidateForPsle($existingPremCandidate, $psleExamType, $activeExamYear);
                });

                return back()->with('success', "Candidate '{$request->full_name}' was found linked to another centre under a different index number but has been successfully reclaimed and updated for your school.");
            }

            return back()
                ->withInput()
                ->with('modal_type', 'add')
                ->with('error', "PReM Number {$request->prem_no} is already assigned to candidate {$existingPremCandidate->candidate_id} in your school.");
        }

        if (!str_starts_with($request->candidate_id, $school->code . '-')) {
            return back()
                ->withInput()
                ->with('modal_type', 'add')
                ->with('error', "Index Number must start with this school's centre number ({$school->code}).");
        }

        try {
            DB::transaction(function () use ($request, $school, $psleExamType, $activeExamYear) {
                $this->removeStalePsleRegistrationForIndexNumber(
                    $request->candidate_id,
                    $psleExamType,
                    $activeExamYear
                );

                $candidate = Candidate::create([
                    'school_id' => $school->id,
                    'candidate_id' => $request->candidate_id,
                    'full_name' => $request->full_name,
                    'gender' => $request->gender,
                    'prem_no' => $request->prem_no,
                    'candidate_type' => 'SCHOOL',
                    'status' => 'registered',
                    'exam_type' => 'PSLE',
                ]);

                $this->registerCandidateForPsle($candidate, $psleExamType, $activeExamYear);
            });
        } catch (QueryException $e) {
            Log::error('Headteacher candidate registration failed.', [
                'school_id' => $school->id,
                'candidate_id' => $request->candidate_id,
                'exception' => $e,
            ]);

            $message = $this->friendlyCandidateRegistrationError($e, $request);

            return back()
                ->withInput()
                ->with('modal_type', 'add')
                ->with('error', $message);
        } catch (\Throwable $e) {
            Log::error('Headteacher candidate registration failed.', [
                'school_id' => $school->id,
                'candidate_id' => $request->candidate_id,
                'exception' => $e,
            ]);

            return back()
                ->withInput()
                ->with('modal_type', 'add')
                ->with('error', 'Candidate could not be saved. ' . $e->getMessage());
        }

        return back()->with('success', 'Candidate registered successfully.');
    }

    public function destroyCandidate(Candidate $candidate)
    {
        $user = Auth::user();
        if ($user->portal_role !== 'mock_headteacher' && !$user->isAdmin()) {
            abort(403);
        }

        $school = $this->resolveSchoolForUser($user);
        if (!$school) {
            return back()->with('error', 'Your account is not linked to a school. Contact your DAO.');
        }

        if (!$this->candidateBelongsToSchool($candidate, $school, $user)) {
            abort(403, 'You can only manage candidates registered under your own school.');
        }

        $candidate->delete();

        return back()->with('success', 'Candidate record deleted successfully.');
    }

    private function resolveSchoolForUser($user, array $with = [])
    {
        $query = School::query();

        if (!empty($with)) {
            $query->with($with);
        }

        if ($user->school_id) {
            return $query->find($user->school_id);
        }

        if (!$user->isAdmin()) {
            return null;
        }

        // Admin preview must use the same school everywhere, including save/upload/template.
        $school = (clone $query)
            ->where('education_level', 'PRIMARY')
            ->where('region_id', '!=', 1)
            ->first();

        return $school ?: $query->where('education_level', 'PRIMARY')->first();
    }

    private function registerCandidateForPsle(Candidate $candidate, ExamType $psleExamType, ExamYear $activeExamYear): void
    {
        $year = (int) $activeExamYear->year_label;

        CandidateExamRegistration::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $psleExamType->id,
                'exam_year_id' => $activeExamYear->id,
            ],
            [
                'year' => $year,
                'exam_year_id' => $activeExamYear->id,
                'registration_number' => 'PSLE-' . $candidate->candidate_id,
                'status' => 'APPROVED',
            ]
        );
    }

    private function removeStalePsleRegistrationForIndexNumber(
        string $candidateIndexNumber,
        ExamType $psleExamType,
        ExamYear $activeExamYear
    ): void {
        $registrationNumber = 'PSLE-' . $candidateIndexNumber;

        $staleRegistration = CandidateExamRegistration::query()
            ->where('registration_number', $registrationNumber)
            ->where('exam_type_id', $psleExamType->id)
            ->where('exam_year_id', $activeExamYear->id)
            ->first();

        if (!$staleRegistration) {
            return;
        }

        $linkedCandidate = Candidate::find($staleRegistration->candidate_id);

        if ($linkedCandidate && $linkedCandidate->candidate_id === $candidateIndexNumber) {
            return;
        }

        Log::warning('Removed stale PSLE registration blocking mock-portal candidate creation.', [
            'candidate_index_number' => $candidateIndexNumber,
            'registration_id' => $staleRegistration->id,
            'registration_number' => $registrationNumber,
            'linked_candidate_id' => $staleRegistration->candidate_id,
            'linked_candidate_index_number' => $linkedCandidate?->candidate_id,
        ]);

        $staleRegistration->delete();
    }

    private function readCandidateUploadRows(UploadedFile $file): array
    {
        $path = $file->getPathname();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($path);
        }

        return $this->readSpreadsheetRows($path);
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readSpreadsheetRows(string $path): array
    {
        $rows = [];
        $reader = ReaderEntityFactory::createReaderFromFile($path);
        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];

                foreach ($row->getCells() as $cell) {
                    $value = $cell->getValue();
                    $cells[] = is_scalar($value) || $value === null ? (string) $value : '';
                }

                $rows[] = $cells;
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    private function friendlyCandidateRegistrationError(QueryException $e, Request $request): string
    {
        $error = $e->getMessage();

        if (str_contains($error, 'candidate_exam_registrations.registration_number')) {
            return "Candidate {$request->candidate_id} already has a PSLE registration record. If the pupil is missing from the list, ask the administrator to clean the stale registration row.";
        }

        if (
            str_contains($error, 'candidate_exam_registrations.candidate_id') ||
            str_contains($error, 'unique_candidate_exam_context')
        ) {
            return "Candidate {$request->candidate_id} is already registered for the active PSLE exam year.";
        }

        if (str_contains($error, 'candidates.candidate_id')) {
            return "Index Number {$request->candidate_id} already exists.";
        }

        if (str_contains($error, 'candidates.prem_no')) {
            return "PReM Number {$request->prem_no} already exists.";
        }

        return 'Candidate could not be saved. ' . $error;
    }

    private function indexNumberPatternForSchool(School $school): string
    {
        return '/^' . preg_quote($school->code, '/') . '-[0-9]{4}$/';
    }

    private function candidateBelongsToSchool(Candidate $candidate, School $school, $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $candidate->school_id === (int) $school->id;
    }

    private function getEmblemDataUri(): string
    {
        $paths = [
            base_path('images/emblem.png'),
            public_path('images/emblem.png'),
            base_path('../public_html/images/emblem.png'),
            base_path('../../public_html/images/emblem.png'),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
            }
        }

        return '';
    }

    private function getCode39Bars(string $value): array
    {
        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];
        $code = '*' . strtoupper($value) . '*';
        $bars = [];
        foreach (str_split($code) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];
            foreach (str_split($pattern) as $index => $bar) {
                $isBlack = ($index % 2 === 0);
                $isWide = ($bar === 'w');
                $width = $isWide ? 1.25 : 0.5;
                $bars[] = ['color' => $isBlack ? '#0b1d3a' : 'transparent', 'width' => $width];
            }
            $bars[] = ['color' => 'transparent', 'width' => 0.5];
        }
        return $bars;
    }
}
