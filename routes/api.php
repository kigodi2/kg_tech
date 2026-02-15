<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Region;
use App\Models\School;
use App\Models\Candidate;
use App\Models\ExamType;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamTypeController;
use App\Http\Controllers\Api\CombinationController;
use App\Http\Controllers\DistrictCandidateImportController;
use App\Http\Controllers\CandidateImportController;
use App\Http\Controllers\Admin\CandidateExtremityController;
use App\Http\Controllers\DailyMarksEntryReportController;

// ==================== CANDIDATE EXTREMITY ANALYSIS API ====================
Route::prefix('extremity')->middleware(['auth', 'admin'])->group(function () {
    Route::post('analyze', [CandidateExtremityController::class, 'analyze']);
    Route::get('dashboard', [CandidateExtremityController::class, 'dashboard']);
    Route::get('report/{report}', [CandidateExtremityController::class, 'show']);
    Route::post('report/{report}/mark-reviewed', [CandidateExtremityController::class, 'markReviewed']);
    Route::get('export', [CandidateExtremityController::class, 'export']);
});

Route::get('/regions', function () {
    return Region::all();
});

Route::get('/subjects', function () {
    try {
        $subjects = \App\Models\Subject::orderBy('name')->get(['id', 'code', 'name']);
        return response()->json(['data' => $subjects]);
    } catch (\Exception $e) {
        \Log::error('Subjects API error:', ['error' => $e->getMessage()]);
        return response()->json(['data' => []], 200);
    }
});



Route::get('/schools', function (Request $request) {
    $pageSize = $request->input('page_size', 15);
    $districtId = $request->input('district_id', '');
    
    $query = School::query();
    
    if ($districtId) {
        $query->where('district_id', $districtId);
    }
    
    $total = $query->count();
    $totalPages = ceil($total / $pageSize);
    
    $schools = $query->paginate($pageSize);
    
    return response()->json([
        'data' => $schools->items(),
        'pagination' => [
            'total_count' => $total,
            'total_pages' => $totalPages
        ]
    ]);
});

Route::get('/candidates', function (Request $request) {
    $pageSize = $request->input('page_size', 10);
    $page = $request->input('page', 1);
    $search = $request->input('search', '');
    $schoolId = $request->input('school_id', '');
    $districtId = $request->input('district_id', '');
    $regionId = $request->input('region_id', '');
    
    $query = Candidate::with('school', 'school.district', 'school.district.region', 'examRegistrations.examYear');
    
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%");
        });
    }
    
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
    
    $total = $query->count();
    $totalPages = ceil($total / $pageSize);
    
    $candidates = $query->paginate($pageSize, ['*'], 'page', $page);
    
    // Ensure exam_year is included in the response
    // by making sure the appended attribute is computed
    $items = array_map(function($candidate) {
        return is_array($candidate) ? $candidate : $candidate->toArray();
    }, $candidates->items());
    
    return response()->json([
        'data' => $items,
        'pagination' => [
            'total_count' => $total,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $pageSize
        ]
    ]);
});

Route::get('/exam-types', function () {
    return ExamType::all();
});

Route::get('/exam-years', function () {
    return \App\Models\ExamYear::orderBy('id', 'desc')->get();
});

Route::get('/subjects', function () {
    return \App\Models\Subject::where('is_active', true)->orderBy('name')->get();
});

Route::get('/regions/{region}', function (Region $region) {
    return $region->load('schools');
});

Route::get('/schools/{school}', function (School $school) {
    return $school->load('candidates');
});

// Candidate CRUD endpoints
Route::post('/candidates', function (Request $request) {
    $validated = $request->validate([
        'school_id' => 'required|exists:schools,id',
        'candidate_id' => 'nullable|unique:candidates,candidate_id',
        'full_name' => 'required|string|max:255',
        'gender' => 'required|in:M,F',
        'combination' => 'nullable|string|max:255',
        'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
        'status' => 'nullable|string|max:255',
    ]);
    
    // Auto-generate candidate_id if not provided
    if (empty($validated['candidate_id'])) {
        $count = Candidate::count() + 1;
        $validated['candidate_id'] = 'CAND-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
    
    $candidate = Candidate::create($validated);
    
    return response()->json(['message' => 'Candidate registered successfully', 'data' => $candidate->load('school')], 201);
});

Route::put('/candidates/{id}', function (Request $request, $id) {
    $candidate = Candidate::findOrFail($id);
    
    $validated = $request->validate([
        'school_id' => 'required|exists:schools,id',
        'candidate_id' => 'nullable|unique:candidates,candidate_id,' . $candidate->id,
        'full_name' => 'required|string|max:255',
        'gender' => 'required|in:M,F',
        'combination' => 'nullable|string|max:255',
        'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
        'status' => 'nullable|string|max:255',
    ]);
    
    $candidate->update($validated);
    
    return response()->json(['message' => 'Candidate updated successfully', 'data' => $candidate->load('school')]);
});

Route::delete('/candidates/{id}', function ($id) {
    $candidate = Candidate::findOrFail($id);
    $candidate->delete();
    return response()->json(['message' => 'Candidate deleted successfully']);
});

Route::post('/candidates/bulk-delete', function (Request $request) {
    $ids = $request->input('ids', []);
    
    if (empty($ids)) {
        return response()->json(['message' => 'No IDs provided'], 400);
    }
    
    $deleted = Candidate::whereIn('id', $ids)->delete();
    
    return response()->json([
        'message' => 'Candidates deleted successfully',
        'deleted' => $deleted
    ]);
});

// ==================== CANDIDATE IMPORT ROUTES (Two-Phase: Validate + Commit) ====================
// Uses CandidateImportController with on_exists_mode support (skip|replace)
// Test routes (without CSRF for automated testing)
Route::prefix('candidates/import')->middleware(['web'])->group(function () {
    Route::post('/validate', [CandidateImportController::class, 'validateImport'])->withoutMiddleware('web');
    Route::post('/commit', [CandidateImportController::class, 'commitImport'])->withoutMiddleware('web');
    Route::post('/template', [CandidateImportController::class, 'downloadTemplate'])->withoutMiddleware('web');
    Route::post('/download-errors', [CandidateImportController::class, 'downloadErrorReport'])->withoutMiddleware('web');
    Route::post('/async', [CandidateImportController::class, 'asyncBulkImport'])->withoutMiddleware('web');
});

// Legacy endpoints - deprecated but kept for backward compatibility
// Check for conflicts in candidate import
Route::post('/candidates/import/check', function (Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt'
    ]);
    
    $file = $request->file('file');
    $handle = fopen($file->getRealPath(), 'r');
    
    $conflicts = [];
    $header = fgetcsv($handle);
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 5) continue;
        
        try {
            $candidateId = trim($row[0]) ?: null;
            if (!empty($candidateId)) {
                // Check if candidate exists
                if (Candidate::where('candidate_id', $candidateId)->exists()) {
                    $conflicts[] = $candidateId;
                }
            }
        } catch (\Exception $e) {
            continue;
        }
    }
    
    fclose($handle);
    
    return response()->json([
        'conflicts' => $conflicts,
        'conflict_count' => count($conflicts)
    ]);
});

// Import candidates with conflict handling
Route::post('/candidates/import', function (Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt',
        'mode' => 'required|in:skip,replace,replace-all',
        'exam_year' => 'nullable|string',
        'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE'
    ]);
    
    $file = $request->file('file');
    $mode = $request->input('mode');
    $examYearValue = $request->input('exam_year');
    $examTypeOverride = $request->input('exam_type');
    
    \Log::info('Import request received', [
        'exam_year_raw' => $examYearValue,
        'exam_type' => $examTypeOverride,
        'mode' => $mode
    ]);
    
    // Convert exam_year string to integer for validation
    if ($examYearValue) {
        $examYearValue = intval($examYearValue);
        if ($examYearValue < 2000 || $examYearValue > (now()->year + 1)) {
            return response()->json(['message' => 'Invalid exam year'], 422);
        }
        \Log::info('Exam year converted', ['exam_year_int' => $examYearValue]);
    }
    
    $handle = fopen($file->getRealPath(), 'r');
    
    $count = 0;
    $skipped = 0;
    $replaced = 0;
    $header = fgetcsv($handle);
    
    // Handle replace-all mode
    if ($mode === 'replace-all') {
        Candidate::truncate();
    }
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 5) continue;
        
        try {
            $candidateId = trim($row[0]) ?: null;
            $fullName = trim($row[1] ?? '');
            $gender = trim($row[2] ?? '');
            $combination = trim($row[3] ?? '') ?: null;
            $schoolCodeOrId = trim($row[4] ?? '');
            $examType = trim($row[5] ?? '') ?: $examTypeOverride;
            
            if (empty($candidateId)) {
                $candidateCount = Candidate::count() + 1;
                $candidateId = 'CAND-' . str_pad($candidateCount, 6, '0', STR_PAD_LEFT);
            }
            
            // Check if exists
            $exists = Candidate::where('candidate_id', $candidateId)->exists();
            
            if ($exists && $mode === 'skip') {
                $skipped++;
                continue;
            }
            
            // Resolve school ID from code or numeric ID
            $schoolId = intval($schoolCodeOrId);
            $school = null;
            
            if (!$schoolId || !School::find($schoolId)) {
                // Try to find by code or registration_number
                $school = School::where('registration_number', $schoolCodeOrId)
                    ->orWhere('code', $schoolCodeOrId)
                    ->first();
                
                // If not found and schoolCodeOrId looks like a code, auto-create it
                if (!$school && !empty($schoolCodeOrId) && !is_numeric($schoolCodeOrId)) {
                    // Auto-register missing school
                    $defaultRegion = \App\Models\Region::first();
                    $defaultDistrict = \App\Models\District::first();
                    
                    $school = School::create([
                        'code' => $schoolCodeOrId,
                        'name' => "Imported School - {$schoolCodeOrId}",
                        'district_id' => $defaultDistrict ? $defaultDistrict->id : null,
                        'region_id' => $defaultRegion ? $defaultRegion->id : null,
                        'ownership' => 'GOVERNMENT',
                        'is_active' => true,
                    ]);
                    
                    \Log::info('Auto-registered missing school', [
                        'school_code' => $schoolCodeOrId,
                        'school_id' => $school->id
                    ]);
                }
                
                $schoolId = $school ? $school->id : (intval($schoolCodeOrId) ?? 1);
            }
            
            $candidate = Candidate::updateOrCreate(
                ['candidate_id' => $candidateId],
                [
                    'full_name' => $fullName,
                    'gender' => strtoupper($gender),
                    'combination' => $combination,
                    'school_id' => $schoolId,
                    'exam_type' => strtoupper($examType),
                ]
            );
            
            // Register for ACSEE if applicable and exam year provided
            if (strtoupper($examType) === 'ACSEE' && $examYearValue && !empty($combination)) {
                \Log::info('Registering ACSEE candidate', [
                    'candidate_id' => $candidateId,
                    'exam_year_value' => $examYearValue,
                    'combination' => $combination,
                    'exam_type' => $examType
                ]);
                try {
                    $controller = app(\App\Http\Controllers\CandidateController::class);
                    $reflection = new \ReflectionMethod($controller, 'registerForACSEE');
                    $reflection->setAccessible(true);
                    $reflection->invoke($controller, $candidate, $combination, $examYearValue);
                    \Log::info('ACSEE registration succeeded', ['candidate_id' => $candidateId]);
                } catch (\Exception $e) {
                    \Log::warning('ACSEE registration failed during import', [
                        'candidate_id' => $candidateId,
                        'exam_year' => $examYearValue,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                \Log::info('Skipped ACSEE registration', [
                    'candidate_id' => $candidateId,
                    'exam_type' => $examType,
                    'exam_year_value' => $examYearValue,
                    'combination' => $combination
                ]);
            }
            
            if ($exists) {
                $replaced++;
            } else {
                $count++;
            }
        } catch (\Exception $e) {
            \Log::error('Row import error', ['error' => $e->getMessage()]);
            continue;
        }
    }
    
    fclose($handle);
    
    return response()->json([
        'message' => 'Import completed successfully',
        'count' => $count,
        'skipped' => $skipped,
        'replaced' => $replaced
    ]);
});

Route::post('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ==================== DATA AUDIT ENDPOINTS ====================

// Audit candidates data integrity
Route::get('/audit/candidates', function () {
    $totalSchools = \App\Models\School::count();
    $totalCandidates = \App\Models\Candidate::count();
    $schoolsWithoutDistrict = \App\Models\School::whereNull('district_id')->count();
    
    // Find schools with candidates not in same district
    $mismatches = [];
    $schools = \App\Models\School::with('district', 'candidates')->get();
    
    foreach ($schools as $school) {
        if ($school->candidates->count() > 0 && !$school->district) {
            $mismatches[] = "{$school->code} ({$school->name}) - No district assigned";
            continue;
        }
    }
    
    return response()->json([
        'total_schools' => $totalSchools,
        'total_candidates' => $totalCandidates,
        'schools_without_district' => $schoolsWithoutDistrict,
        'mismatches' => array_unique($mismatches)
    ]);
});

// Fix candidates data integrity issues
Route::post('/audit/candidates/fix', function (Request $request) {
    $mismatches = $request->input('mismatches', []);
    $fixed = 0;
    
    // Fix schools without district assignment
    $schoolsWithoutDistrict = \App\Models\School::whereNull('district_id')->get();
    foreach ($schoolsWithoutDistrict as $school) {
        // Try to infer district from candidates
        $candidateSchool = $school->candidates()->first();
        if ($candidateSchool) {
            // Get district from any candidate's school
            $referenceSchool = \App\Models\School::whereHas('candidates', function ($q) use ($school) {
                $q->where('school_id', $school->id);
            })->whereNotNull('district_id')->first();
            
            if ($referenceSchool && $referenceSchool->district_id) {
                $school->update(['district_id' => $referenceSchool->district_id]);
                $fixed++;
            }
        }
    }
    
    return response()->json([
        'message' => 'Fixed data integrity issues',
        'fixed' => $fixed
    ]);
});

// ==================== BULK IMPORT ENDPOINTS ====================
Route::prefix('bulk-import')->group(function () {
    // Preview ZIP before import
    Route::post('/preview', [\App\Http\Controllers\BulkImportController::class, 'preview']);

    // School-level import
    Route::post('/start', [\App\Http\Controllers\BulkImportController::class, 'startImport']);

    // District-level import
    Route::post('/district/start', [\App\Http\Controllers\BulkImportController::class, 'startDistrictImport']);

    // Get import progress
    Route::get('{id}/progress', [\App\Http\Controllers\BulkImportController::class, 'getProgress']);

    // Get import details
    Route::get('{id}', [\App\Http\Controllers\BulkImportController::class, 'getDetails']);

    // District import recovery endpoints
    Route::get('{id}/recovery-status', [\App\Http\Controllers\BulkImportController::class, 'getRecoveryStatus']);
    Route::post('{id}/retry-school', [\App\Http\Controllers\BulkImportController::class, 'retrySchool']);
    Route::post('{id}/retry-all', [\App\Http\Controllers\BulkImportController::class, 'retryAll']);
});

// ==================== COMBINATION ENDPOINTS ====================
// All endpoints follow RESTful conventions with exam-type-specific routes

// Dashboard ACSEE Candidates Endpoints
Route::get('/dashboard/candidates/acsee', [DashboardController::class, 'getAcseeCandicates']);
Route::get('/dashboard/candidates/filter-data', [DashboardController::class, 'getAcseeFilterData']);

Route::prefix('exam-types/{code}')->group(function () {
    // ACSEE Candidates (read-only from registration)
    Route::get('candidates', [ExamTypeController::class, 'getAcseeCandicates']);
    
    Route::prefix('combinations')->group(function () {
        // List combinations with pagination, search, and filtering
        Route::get('/', [CombinationController::class, 'index']);
        
        // Create a new combination
        Route::post('/', [CombinationController::class, 'store']);
        
        // Update a combination
        Route::put('{id}', [CombinationController::class, 'update']);
        
        // Delete a combination
        Route::delete('{id}', [CombinationController::class, 'destroy']);
        
        // Import combinations from CSV
        Route::post('import', [CombinationController::class, 'import']);
        
        // Export combinations to CSV
        Route::get('export', [CombinationController::class, 'export']);
    });
});

// ==================== SCHOOL IMPORT API ====================
Route::prefix('schools/import')->group(function () {
    Route::post('validate', [\App\Http\Controllers\SchoolImportController::class, 'validateImport']);
    Route::post('commit', [\App\Http\Controllers\SchoolImportController::class, 'commit']);
    Route::post('download-errors', [\App\Http\Controllers\SchoolImportController::class, 'downloadErrors']);
    Route::get('template', [\App\Http\Controllers\SchoolImportController::class, 'downloadTemplate']);
});

// ==================== DISTRICT IMPORT API ====================
Route::prefix('districts/import')->group(function () {
    Route::post('validate', [\App\Http\Controllers\DistrictImportController::class, 'validateImportDistrict']);
    Route::post('commit', [\App\Http\Controllers\DistrictImportController::class, 'commitImportDistrict']);
    Route::post('download-errors', [\App\Http\Controllers\DistrictImportController::class, 'downloadErrors']);
    Route::get('template', [\App\Http\Controllers\DistrictImportController::class, 'downloadTemplate']);
});

// ==================== DAILY MARKS ENTRY REPORT API ====================
Route::get('/daily-marks-entry-report', [DailyMarksEntryReportController::class, 'getReport'])->middleware(['auth', 'admin']);

// ==================== DISTRICT CANDIDATE IMPORT API ====================
Route::post('/registration/import-by-district', [DistrictCandidateImportController::class, 'importByDistrict']);
Route::get('/districts', [DistrictCandidateImportController::class, 'getDistricts']);
Route::get('/districts/{districtId}/schools', [DistrictCandidateImportController::class, 'getDistrictSchools']);

// ==================== BACKUP & RESTORE API ====================
require_once 'backup.php';

// ==================== NECTA GRADING API ====================
require base_path('routes/api-grading.php');
