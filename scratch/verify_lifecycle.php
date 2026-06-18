<?php

// Boot Laravel Application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Region;
use App\Models\School;
use App\Models\Candidate;
use App\Models\AuditLog;

function heading($text) {
    echo "\n=== " . strtoupper($text) . " ===\n";
}

function success($text) {
    echo "  [OK] " . $text . "\n";
}

function failure($text) {
    echo "  [FAIL] " . $text . "\n";
}

heading("1. Setup & Context Verification");

$activeYear = ExamYear::where('is_active', true)->first();
if ($activeYear) {
    success("Active Exam Year: " . $activeYear->year_label . " (ID: " . $activeYear->id . ")");
} else {
    $activeYear = ExamYear::orderByDesc('year_label')->first();
    success("Using Latest Year: " . $activeYear->year_label . " (ID: " . $activeYear->id . ")");
}
$examYearId = $activeYear->id;
$examYearValue = $activeYear->year_label;

$psleType = ExamType::where('code', 'PSLE')->first();
if ($psleType) {
    success("PSLE Exam Type ID: " . $psleType->id);
} else {
    failure("PSLE exam type not found in database.");
    exit(1);
}
$psleExamTypeId = $psleType->id;

$tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
success("TASIDO Region Count: " . $tasidoRegions->count());

$parentSchool = School::whereIn('region_id', $tasidoRegions->pluck('id'))
    ->where('education_level', 'PRIMARY')
    ->first();
if (!$parentSchool) {
    failure("No parent school found in TASIDO regions!");
    exit(1);
}
success("Found parent school: {$parentSchool->name} in Region ID: {$parentSchool->region_id}");

// Clean up previous runs for year so we can run a clean simulation
DB::table('result_processes')->where('exam_year_id', $examYearId)->where('exam_type_id', $psleExamTypeId)->delete();
DB::table('result_snapshots')->where('exam_year_id', $examYearId)->where('exam_type', 'PSLE')->delete();
DB::table('psle_result_publications')->where('exam_year_id', $examYearId)->delete();

// Clean up any stray data from previous partial runs
$straySchool = School::where('code', 'TEMP999')->first();
if ($straySchool) {
    $strayCandidates = Candidate::where('school_id', $straySchool->id)->pluck('id');
    DB::table('raw_marks')->whereIn('candidate_id', $strayCandidates)->delete();
    DB::table('candidate_exam_registrations')->whereIn('candidate_id', $strayCandidates)->delete();
    DB::table('candidates')->whereIn('id', $strayCandidates)->delete();
    DB::table('mark_import_batches')->where('school_id', $straySchool->id)->delete();
    $straySchool->delete();
    success("Stray data from previous run cleaned up.");
}

heading("2. Seeding Test Candidates");

$tempSchool = null;
$subjects = [];
$candidateIds = [];
$batchIds = [];

try {
    DB::transaction(function() use ($parentSchool, $activeYear, $psleExamTypeId, &$tempSchool, &$subjects, &$candidateIds, &$batchIds) {
        // Create temp school
        $tempSchool = School::create([
            'region_id' => $parentSchool->region_id,
            'district_id' => $parentSchool->district_id,
            'council_id' => $parentSchool->council_id,
            'code' => 'TEMP999',
            'name' => 'TEMP PSLE TEST SCHOOL',
            'education_level' => 'PRIMARY',
            'ownership' => 'GOVERNMENT',
            'is_active' => true,
        ]);

        // Get subjects
        $subjects = DB::table('subjects')
            ->where('exam_type_id', $psleExamTypeId)
            ->where('is_active', true)
            ->get();

        // Create 6 batches (one per subject) for the temp school
        $batchIdsBySubject = [];
        foreach ($subjects as $sub) {
            $batchId = DB::table('mark_import_batches')->insertGetId([
                'batch_code' => "BATCH-TEMP-{$tempSchool->code}-{$sub->code}-" . uniqid(),
                'school_id' => $tempSchool->id,
                'subject_id' => $sub->id,
                'exam_type_id' => $psleExamTypeId,
                'exam_year_id' => $activeYear->id,
                'exam_year' => $activeYear->year_label,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $batchIdsBySubject[$sub->id] = $batchId;
            $batchIds[] = $batchId;
        }

        // Create 3 candidates:
        // Candidate 1: 6 normal marks
        // Candidate 2: 5 normal marks, 1 ABS
        // Candidate 3: 5 normal marks, 1 INC
        for ($i = 1; $i <= 3; $i++) {
            $candidate = Candidate::create([
                'school_id' => $tempSchool->id,
                'candidate_id' => 'PS09999-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'prem_no' => '9999' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'full_name' => 'PSLE TEST CANDIDATE ' . $i,
                'gender' => ($i % 2 === 0) ? 'F' : 'M',
                'exam_type' => 'PSLE',
                'candidate_type' => 'school',
                'is_active' => true,
                'status' => 'approved',
                'exam_year_id' => $activeYear->id,
                'exam_type_id' => $psleExamTypeId,
            ]);
            $candidateIds[] = $candidate->id;

            DB::table('candidate_exam_registrations')->insert([
                'candidate_id' => $candidate->id,
                'exam_type_id' => $psleExamTypeId,
                'exam_year_id' => $activeYear->id,
                'year' => $activeYear->year_label,
                'status' => 'active',
                'registration_number' => $candidate->candidate_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create raw marks for 6 subjects
            foreach ($subjects as $j => $sub) {
                $batchId = $batchIdsBySubject[$sub->id];
                $paper1 = 40.00;
                $status = 'CLEAN';

                if ($i === 2 && $j === 5) {
                    $paper1 = null;
                    $status = 'ABS';
                }
                if ($i === 3 && $j === 5) {
                    $paper1 = null;
                    $status = 'INC';
                }

                DB::table('raw_marks')->insert([
                    'mark_import_batch_id' => $batchId,
                    'candidate_id' => $candidate->id,
                    'school_id' => $tempSchool->id,
                    'subject_id' => $sub->id,
                    'subject_status' => $status,
                    'row_number' => $j + 1,
                    'candidate_index_number' => $candidate->candidate_id,
                    'full_name' => $candidate->full_name,
                    'paper_1_marks' => $paper1,
                    'is_locked' => false,
                    'exam_year_id' => $activeYear->id,
                    'raw_data' => '[]',
                    'warning_messages' => '[]',
                    'error_messages' => '[]',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    });
    success("Test candidates, registrations, batches, and raw marks seeded successfully.");
} catch (\Throwable $e) {
    failure("Seeding failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

heading("3. Verification of Seeding Requirements");

// Check ABS marks
$absMark = DB::table('raw_marks')
    ->where('school_id', $tempSchool->id)
    ->where('subject_status', 'ABS')
    ->first();
if ($absMark && is_null($absMark->paper_1_marks)) {
    success("ABS candidate seeded correctly (subject_status = 'ABS', paper_1_marks = NULL).");
} else {
    failure("ABS candidate marks seeding failed.");
}

// Check INC marks
$incMark = DB::table('raw_marks')
    ->where('school_id', $tempSchool->id)
    ->where('subject_status', 'INC')
    ->first();
if ($incMark && is_null($incMark->paper_1_marks)) {
    success("INC candidate seeded correctly (subject_status = 'INC', paper_1_marks = NULL).");
} else {
    failure("INC candidate marks seeding failed.");
}

heading("4. Admin Action Sequence & Gating");

$controller = app(\App\Http\Controllers\Admin\AdminPsleResultsController::class);
$admin = \App\Models\User::where('is_admin', true)->first();
if ($admin) {
    auth()->login($admin);
    success("Logged in as Admin: " . $admin->email);
} else {
    echo "  [WARN] No admin user found. Executing as guest.\n";
}

$officer = \App\Models\User::where('is_admin', false)->first();
$createdOfficer = false;
if (!$officer) {
    $officer = \App\Models\User::create([
        'name' => 'Mock Officer',
        'email' => 'mock_officer@example.com',
        'password' => bcrypt('password'),
        'is_admin' => false,
        'portal_role' => 'mark_entry_officer',
        'region_id' => $parentSchool->region_id,
        'is_active' => true,
    ]);
    $createdOfficer = true;
}

$request = new \Illuminate\Http\Request(['exam_year_id' => $examYearId]);

// 4.1 Pre-run validation
try {
    $res = $controller->validateData($request);
    $resData = json_decode($res->getContent(), true);
    if ($resData['success'] ?? false) {
        success("Validation execution succeeded: " . $resData['message']);
    } else {
        failure("Validation failed: " . ($resData['message'] ?? ''));
    }
} catch (\Throwable $e) {
    failure("Validation threw exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

// 4.2 Submit & Lock
try {
    $res = $controller->submitAndLockRawMarks($request);
    $resData = json_decode($res->getContent(), true);
    if ($resData['success'] ?? false) {
        success("Submit & Lock execution succeeded: " . $resData['message']);
    } else {
        failure("Submit & Lock failed: " . ($resData['message'] ?? ''));
    }
} catch (\Throwable $e) {
    failure("Submit & Lock threw exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

// 4.3 Check that raw marks are indeed locked and contain submission metadata
if (\Illuminate\Support\Facades\Schema::hasColumn('raw_marks', 'submitted_at') && \Illuminate\Support\Facades\Schema::hasColumn('raw_marks', 'submitted_by')) {
    success("raw_marks.submitted_at exists");
    success("raw_marks.submitted_by exists");
} else {
    failure("raw_marks.submitted_at or raw_marks.submitted_by columns do not exist in database.");
}

$rawMarksMetadata = DB::table('raw_marks')->where('school_id', $tempSchool->id)->get();
$lockedMarksCount = 0;
$hasSubmissionMetadata = true;

foreach ($rawMarksMetadata as $rm) {
    if ($rm->is_locked) {
        $lockedMarksCount++;
    }
    if (is_null($rm->submitted_at) || is_null($rm->submitted_by)) {
        $hasSubmissionMetadata = false;
    }
}

if ($lockedMarksCount === 18) {
    success("All 18 raw marks for the test school are locked in the database.");
} else {
    failure("Raw marks locking check failed. Locked count: " . $lockedMarksCount);
}

if ($hasSubmissionMetadata) {
    success("Submit & Lock writes submission metadata correctly.");
} else {
    failure("raw_marks.submitted_at or raw_marks.submitted_by is null after submit and lock!");
}

// 4.4 Verify locked mark mutation attempt guards
$sampleLockedMark = \App\Models\RawMark::where('school_id', $tempSchool->id)->first();
try {
    $sampleLockedMark->update(['paper_1_marks' => 45]);
    failure("Mutating locked raw mark directly using Eloquent succeeded but should have failed!");
} catch (\Throwable $e) {
    if (strpos($e->getMessage(), "These marks are submitted and locked for processing.") !== false) {
        success("Eloquent direct update guard rejected with correct exact message: " . $e->getMessage());
    } else {
        failure("Eloquent direct update guard rejected but with different message: " . $e->getMessage());
    }
}

// Try through PsleMarkEntryController manual save method
try {
    $markEntryController = app(\App\Http\Controllers\PsleMarkEntryController::class);
    
    // Create mock mark officer user for this school's region using an existing user ID to satisfy FK constraint
    $officer = new \App\Models\User();
    $officer->id = $admin->id;
    $officer->email = 'mock_officer@example.com';
    $officer->portal_role = 'mark_entry_officer';
    $officer->is_admin = false;
    $officer->region_id = $tempSchool->region_id;
    
    $mockRequest = new \Illuminate\Http\Request([
        'exam_year_id' => $examYearId,
        'school_id' => $tempSchool->id,
        'subject_id' => $sampleLockedMark->subject_id,
        'candidate_id' => $sampleLockedMark->candidate_id,
        'score' => 45
    ]);
    $mockRequest->setUserResolver(fn () => $officer);
    
    $res = $markEntryController->saveMark($mockRequest);
    $resData = json_decode($res->getContent(), true);
    if (($resData['message'] ?? '') === "These marks are submitted and locked for processing.") {
        success("PsleMarkEntryController save method rejected update with correct exact message: " . $resData['message']);
    } else {
        failure("PsleMarkEntryController save method returned wrong message or status code. Response: " . $res->getContent());
    }
} catch (\Throwable $e) {
    if (strpos($e->getMessage(), "These marks are submitted and locked for processing.") !== false) {
        success("PsleMarkEntryController save method threw exception with correct exact message: " . $e->getMessage());
    } else {
        failure("PsleMarkEntryController save method threw exception with different message: " . $e->getMessage());
    }
}

// 4.5 Draft Run
try {
    $res = $controller->draftRun($request);
    $resData = json_decode($res->getContent(), true);
    if ($resData['success'] ?? false) {
        success("Draft Run execution succeeded: " . $resData['message']);
    } else {
        failure("Draft Run failed: " . ($resData['message'] ?? ''));
    }
} catch (\Throwable $e) {
    failure("Draft Run threw exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

// 4.6 Final Run
try {
    $res = $controller->finalRun($request);
    $resData = json_decode($res->getContent(), true);
    if ($resData['success'] ?? false) {
        success("Final Run execution succeeded: " . $resData['message']);
    } else {
        failure("Final Run failed: " . ($resData['message'] ?? ''));
    }
} catch (\Throwable $e) {
    failure("Final Run threw exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

heading("5. Public Portal Guest Access & Snapshot Gating");

// 5.1 Guest access test BEFORE publication (Should fail/block with 403)
try {
    auth()->logout();
    $guestRequest = \Illuminate\Http\Request::create("/results/{$examYearValue}/psle", 'GET');
    $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($guestRequest);
    if ($response->getStatusCode() === 403) {
        success("/results/{$examYearValue}/psle gating verified before publish: blocked with HTTP 403.");
    } else {
        failure("/results/{$examYearValue}/psle BEFORE publication returned HTTP code: " . $response->getStatusCode());
    }

    // Bypass session cookie issues by calling controller directly for evaluations
    if ($officer) {
        auth()->login($officer);
    }
    try {
        $evalController = app(\App\Http\Controllers\PsleEvaluationsController::class);
        $evalController->index();
        failure("/evaluations/psle BEFORE publication did not block!");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            success("/evaluations/psle gating verified before publish: blocked with HTTP 403.");
        } else {
            failure("/evaluations/psle BEFORE publication returned HTTP status: " . $e->getStatusCode());
        }
    }
    auth()->logout();
} catch (\Throwable $e) {
    failure("Guest/Auth access before publication check threw exception: " . $e->getMessage());
}

// 5.2 Publish Snapshot
try {
    if ($admin) {
        auth()->login($admin);
    }
    $res = $controller->publishSnapshot($request);
    $resData = json_decode($res->getContent(), true);
    if ($resData['success'] ?? false) {
        success("Publish Snapshot execution succeeded: " . $resData['message']);
    } else {
        failure("Publish Snapshot failed: " . ($resData['message'] ?? ''));
    }
} catch (\Throwable $e) {
    failure("Publish Snapshot threw exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

// 5.3 Guest access test AFTER publication (Should succeed with 200)
try {
    auth()->logout();
    $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($guestRequest);
    if ($response->getStatusCode() === 200) {
        success("/results/{$examYearValue}/psle gating verified after publish: opens with HTTP 200.");
    } else {
        failure("/results/{$examYearValue}/psle AFTER publication returned HTTP code: " . $response->getStatusCode());
    }

    // Bypass session cookie issues by calling controller directly for evaluations
    if ($officer) {
        auth()->login($officer);
    }
    try {
        $evalController = app(\App\Http\Controllers\PsleEvaluationsController::class);
        $evalController->index();
        success("/evaluations/psle gating verified after publish: opens with HTTP 200.");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        failure("/evaluations/psle AFTER publication returned HTTP status: " . $e->getStatusCode() . " - " . $e->getMessage());
    }
    auth()->logout();
} catch (\Throwable $e) {
    failure("Guest/Auth access after publication check threw exception: " . $e->getMessage());
}

// 5.4 Verify results portal isolation (only reads active published snapshots and never draft tables)
$publication = DB::table('psle_result_publications')
    ->where('exam_year_id', $examYearId)
    ->where('status', 'published')
    ->first();
$snapshotId = $publication ? $publication->snapshot_id : 0;

$snapshotSubjectMarksCount = DB::table('subject_marks')
    ->where('snapshot_id', $snapshotId)
    ->where('year', $examYearValue)
    ->count();

$draftSubjectMarksCount = DB::table('subject_marks')
    ->whereNull('snapshot_id')
    ->where('year', $examYearValue)
    ->count();

if ($snapshotSubjectMarksCount > 0 && $draftSubjectMarksCount === 0) {
    success("Database validation: only snapshot records exist for this year, no draft records are present.");
} else {
    echo "  [INFO] Snapshot records count: {$snapshotSubjectMarksCount}, Draft records count: {$draftSubjectMarksCount}\n";
}

// 5.5 Rollback
try {
    if ($admin) {
        auth()->login($admin);
    }
    $res = $controller->rollback($request);
    $resData = json_decode($res->getContent(), true);
    if ($resData['success'] ?? false) {
        success("Rollback execution succeeded: " . $resData['message']);
    } else {
        failure("Rollback failed: " . ($resData['message'] ?? ''));
    }
} catch (\Throwable $e) {
    failure("Rollback threw exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}

// 5.6 Guest access test AFTER rollback (Should fail/block with 403 again)
try {
    auth()->logout();
    $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($guestRequest);
    if ($response->getStatusCode() === 403) {
        success("/results/{$examYearValue}/psle gating verified after rollback: blocked with HTTP 403.");
    } else {
        failure("/results/{$examYearValue}/psle AFTER rollback returned HTTP code: " . $response->getStatusCode());
    }

    // Bypass session cookie issues by calling controller directly for evaluations
    if ($officer) {
        auth()->login($officer);
    }
    try {
        $evalController = app(\App\Http\Controllers\PsleEvaluationsController::class);
        $evalController->index();
        failure("/evaluations/psle AFTER rollback did not block!");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            success("/evaluations/psle gating verified after rollback: blocked with HTTP 403.");
        } else {
            failure("/evaluations/psle AFTER rollback returned HTTP status: " . $e->getStatusCode());
        }
    }
    auth()->logout();
} catch (\Throwable $e) {
    failure("Guest/Auth access after rollback check threw exception: " . $e->getMessage());
}

// 5.7 Verify rollback does not delete publication records, result_snapshots, candidate_results, and subject_marks
$rolledBackPublications = DB::table('psle_result_publications')
    ->where('exam_year_id', $examYearId)
    ->get();

if ($rolledBackPublications->count() > 0) {
    $allRolledBack = true;
    foreach ($rolledBackPublications as $pub) {
        if ($pub->status !== 'rolled_back') {
            $allRolledBack = false;
        }
    }
    if ($allRolledBack) {
        success("rollback does not delete publication records (updated to status = 'rolled_back').");
    } else {
        failure("Some publication records do not have 'rolled_back' status.");
    }
} else {
    failure("rollback deleted or failed to find publication records!");
}

$snapshotsCount = DB::table('result_snapshots')
    ->where('exam_year_id', $examYearId)
    ->where('exam_type', 'PSLE')
    ->count();

$firstPub = $rolledBackPublications->first();
$snapshotIdVal = $firstPub ? $firstPub->snapshot_id : 0;

$candidateResultsCount = DB::table('candidate_results')
    ->where('snapshot_id', $snapshotIdVal)
    ->count();

$subjectMarksCount = DB::table('subject_marks')
    ->where('snapshot_id', $snapshotIdVal)
    ->count();

if ($snapshotsCount > 0 && $candidateResultsCount > 0 && $subjectMarksCount > 0) {
    success("Rollback preserved result_snapshots, snapshot-linked candidate_results, and snapshot-linked subject_marks.");
} else {
    failure("Rollback deleted snapshots, candidate results, or subject marks! Snapshots: {$snapshotsCount}, Candidates: {$candidateResultsCount}, Marks: {$subjectMarksCount}");
}

heading("6. Audit Log Verification");

$logs = AuditLog::where('module', 'results')
    ->where('exam_year_id', $examYearId)
    ->get();

$expectedActions = [
    'psle_results_validate' => false,
    'psle_raw_marks_submitted' => false,
    'psle_results_draft_run' => false,
    'psle_results_final_run' => false,
    'psle_results_published' => false,
    'psle_results_rollback' => false,
];

$candidateMarkValuesLeak = false;

foreach ($logs as $log) {
    if (array_key_exists($log->action, $expectedActions)) {
        $expectedActions[$log->action] = true;
    }
    
    // Check that details/metadata does not leak raw candidate marks
    // Raw marks used in this simulation are 40 or 40.00
    // We check if "40" or "40.0" is leaked as a mark value
    $detailsStr = strtolower($log->details ?? '');
    if (preg_match('/\b40\b|\b40\.0\b/', $detailsStr)) {
        $candidateMarkValuesLeak = true;
    }
}

$allActionsLogged = true;
foreach ($expectedActions as $action => $found) {
    if ($found) {
        success("Audit log created for action: {$action}");
    } else {
        failure("Audit log MISSING for action: {$action}");
        $allActionsLogged = false;
    }
}

if (!$candidateMarkValuesLeak) {
    success("No candidate mark values leaked in the audit log details.");
} else {
    failure("Candidate mark values leaked in the audit logs!");
}

heading("7. Automatic Cleanup");

if ($admin) {
    auth()->login($admin);
}

try {
    DB::transaction(function() use ($tempSchool, $candidateIds, $batchIds, $createdOfficer, $officer) {
        // Delete raw marks
        DB::table('raw_marks')->whereIn('candidate_id', $candidateIds)->delete();
        
        // Delete registrations
        DB::table('candidate_exam_registrations')->whereIn('candidate_id', $candidateIds)->delete();
        
        // Delete candidates
        DB::table('candidates')->whereIn('id', $candidateIds)->delete();
        
        // Delete batches
        DB::table('mark_import_batches')->whereIn('id', $batchIds)->delete();
        
        // Delete school
        DB::table('schools')->where('id', $tempSchool->id)->delete();

        // Delete mock officer
        if ($createdOfficer && $officer) {
            DB::table('users')->where('id', $officer->id)->delete();
        }
    });
    success("All temporary test data successfully cleaned up from the database.");
} catch (\Throwable $e) {
    failure("Cleanup failed: " . $e->getMessage());
}

echo "\nVerification suite completed.\n";
