<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ExamTypeController;
use App\Http\Controllers\MarkEntryController;
use App\Http\Controllers\ExamYearController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\BackupManagementController;
use App\Http\Controllers\BackupRestoreController;
use App\Http\Controllers\Results\AcseeResultsController;
use App\Http\Controllers\HierarchyController;
use App\Http\Controllers\PublicResultsController;
use App\Http\Controllers\DistrictCandidateImportController;
use App\Http\Controllers\CandidateImportController;
use App\Http\Controllers\AcseeAllocationController;

Route::get('/', function () {
    return view('welcome');
});

// Public Results Portal (no authentication required)
Route::get('/results/{examYear}/{examType}', function($examYear, $examType) {
    return view('public.results.index', compact('examYear', 'examType'));
})->name('public.results');

Route::post('/api/public-results', [PublicResultsController::class, 'search'])->name('public.results.search');
Route::get('/results/{examYear}/{examType}/candidate/{candidateId}', [PublicResultsController::class, 'candidate'])->name('public.results.candidate');
Route::get('/results/{examYear}/{examType}/school/{schoolId}', [PublicResultsController::class, 'school'])->name('public.results.school');

// Auth routes (no middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);

// Forced password change on first login
Route::get('/password/change-required', [PasswordChangeController::class, 'showChangeRequired'])->name('password.change-required')->middleware('auth');
Route::post('/password/update-required', [PasswordChangeController::class, 'updateRequired'])->name('password.update-required')->middleware('auth');

// Backup Management Routes (Admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/backups', [BackupManagementController::class, 'index'])->name('backups.index');
    Route::post('/admin/backups/create', [BackupManagementController::class, 'create'])->name('backups.create');
    Route::delete('/admin/backups/{id}', [BackupManagementController::class, 'delete'])->name('backups.delete');
    
    // Backup Restore Routes
    Route::get('/backups/{id}/restore', [BackupRestoreController::class, 'showRestoreForm'])->name('backup.restore-form');
    Route::post('/backups/{id}/restore', [BackupRestoreController::class, 'executeRestore'])->name('backup.execute-restore');
});

// Protected routes
// Test Seeding API (for E2E tests only - disabled in production)
if (config('app.env') === 'testing' || config('app.debug')) {
    Route::post('/api/test-seed/user', function (\Illuminate\Http\Request $request) {
        $email = $request->input('email', 'admin@test.com');
        $password = $request->input('password', 'password');
        
        \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'status' => 'active',
            ]
        );
        
        return response()->json(['message' => 'User seeded']);
    });
}

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/exam/ACSEE', [DashboardController::class, 'acseeExam'])->name('dashboard.exam.acsee');
    
    // Registration Management Routes
    Route::get('/registration', function () { return view('registration.dashboard'); });
    Route::get('/registration/dashboard', function () { return view('registration.dashboard'); });
    Route::get('/registration/regions', function () { return view('registration.regions'); });
    Route::get('/registration/districts', function () { 
        return view('registration.districts', ['regions' => \App\Models\Region::all()]); 
    });
    Route::get('/registration/schools', function () { 
        return view('registration.schools', ['regions' => \App\Models\Region::all()]); 
    });
    Route::get('/registration/candidates', function () { 
        return view('registration.candidates'); 
    });
    Route::get('/registration/candidates-by-district', function () { 
        return view('registration.candidates-by-district'); 
    });
    Route::get('/exam-types', function () { 
        return view('exam-types.index'); 
    });
    // Dedicated ACSEE route
    Route::get('/exam-types/acsee', function () { 
        return view('exam-types.acsee'); 
    });
    Route::get('/exam-types/{code}', function ($code) { 
        return view('exam-types.show', ['code' => $code]); 
    });
    
    // Extremity Analysis API Routes
    Route::prefix('api/extremity')->middleware(['auth', 'admin'])->group(function () {
        Route::post('analyze', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'analyze']);
        Route::get('dashboard', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'dashboard']);
        Route::get('report/{report}', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'show']);
        Route::post('report/{report}/mark-reviewed', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'markReviewed']);
        Route::get('export', [\App\Http\Controllers\Admin\CandidateExtremityController::class, 'export']);
    });
    
    // Evaluations Routes
    Route::get('/evaluations', function () { 
        return view('evaluations.index'); 
    });
    Route::get('/evaluations/acsee', function () { 
        return view('evaluations.acsee'); 
    });
    Route::get('/evaluations/acsee/daily-marks-entry-report', function () { 
        return view('evaluations.daily-marks-entry-report'); 
    })->name('evaluations.daily-marks-entry-report');
    Route::get('/evaluations/extremity-analysis', function () { 
        return view('evaluations.extremity-analysis'); 
    })->name('evaluations.extremity-analysis');
    
    // Candidate Extremity Analysis Routes
    Route::get('/admin/candidate-extremity', function () {
        return redirect('/evaluations/extremity-analysis');
    });
    
    Route::get('/admin/candidate-extremity/{report}', function ($report) {
        return view('admin.candidate-extremity-detail', ['reportId' => $report]);
    })->name('admin.candidate-extremity.show');
    
    Route::resource('regions', RegionController::class);
    Route::resource('schools', SchoolController::class);
    Route::resource('candidates', CandidateController::class);
    Route::resource('exam-types', ExamTypeController::class);
    Route::resource('exam-years', ExamYearController::class);
    Route::post('exam-years/{examYear}/activate', [ExamYearController::class, 'activate'])->name('exam-years.activate');
    Route::post('exam-years/{examYear}/publish', [ExamYearController::class, 'publish'])->name('exam-years.publish');
    
    // Regions API Endpoints
    Route::get('/api/regions', [RegionController::class, 'apiGetRegions']);
    Route::post('/api/regions', [RegionController::class, 'apiAddRegion']);
    Route::put('/api/regions/{id}', [RegionController::class, 'apiUpdateRegion']);
    Route::delete('/api/regions/{id}', [RegionController::class, 'apiDeleteRegion']);
    Route::get('/api/regions/export-pdf', [RegionController::class, 'apiExportRegionsPdf']);
    Route::get('/api/regions/export-excel', [RegionController::class, 'apiExportRegionsExcel']);
    Route::post('/api/regions/import', [RegionController::class, 'apiImportRegions']);
    Route::post('/api/regions/bulk-delete', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:regions,id'
        ]);
        
        $deleted = \App\Models\Region::whereIn('id', $validated['ids'])->delete();
        return response()->json(['deleted' => $deleted, 'message' => 'Regions deleted successfully']);
    });

    // Districts API Endpoints
    Route::get('/api/districts', function () {
        $page = request('page', 1);
        $pageSize = request('page_size', 10);
        $search = request('search', '');
        $regionId = request('region_id', '');
        
        $query = \App\Models\District::with('region');
        
        // Filter by region if specified
        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        
        // Search by code or name
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%");
            });
        }
        
        $total = $query->count();
        $districts = $query->skip(($page - 1) * $pageSize)
                           ->take($pageSize)
                           ->get();
        
        $data = $districts->map(function($d) {
            $schoolIds = $d->schools()->pluck('id');
            $candidatesCount = \App\Models\Candidate::whereIn('school_id', $schoolIds)->count();
            
            return [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'region_id' => $d->region_id,
                'region_name' => $d->region->name ?? null,
                'schools_count' => $d->schools()->count(),
                'candidates_count' => $candidatesCount,
                'status' => 'active'
            ];
        });
        
        return response()->json([
            'data' => $data,
            'pagination' => [
                'total_count' => $total,
                'total_pages' => ceil($total / $pageSize),
                'current_page' => $page,
                'page_size' => $pageSize
            ]
        ]);
    });
    Route::post('/api/districts', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'code' => 'required|unique:districts',
            'name' => 'required',
            'region_id' => 'required|exists:regions,id'
        ]);
        $district = \App\Models\District::create($validated);
        return response()->json(['message' => 'District added', 'data' => $district], 201);
    });
    Route::put('/api/districts/{id}', function (\Illuminate\Http\Request $request, $id) {
        $district = \App\Models\District::find($id);
        if (!$district) return response()->json(['message' => 'Not found'], 404);
        
        $validated = $request->validate([
            'code' => 'required|unique:districts,code,'.$id,
            'name' => 'required',
            'region_id' => 'required|exists:regions,id'
        ]);
        $district->update($validated);
        return response()->json(['message' => 'District updated', 'data' => $district]);
    });
    Route::delete('/api/districts/{id}', function ($id) {
        $district = \App\Models\District::find($id);
        if (!$district) return response()->json(['message' => 'Not found'], 404);
        
        // Check if district has schools registered
        $schoolCount = $district->schools()->count();
        if ($schoolCount > 0) {
            return response()->json([
                'message' => "Cannot delete district with registered schools",
                'details' => "This district has $schoolCount school(s) registered. Please remove all schools first.",
                'count' => $schoolCount
            ], 409);
        }
        
        $district->delete();
        return response()->json(['message' => 'District deleted']);
    });
    Route::post('/api/districts/import', function (\Illuminate\Http\Request $request) {
        $file = $request->file('file');
        if (!$file) {
            return response()->json(['message' => 'No file provided'], 400);
        }

        try {
            // Read file content directly without storing
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);
            
            $imported = 0;
            $failed = 0;
            $errors = [];
            
            // Skip header row
            foreach ($lines as $index => $line) {
                if ($index === 0) continue; // Skip header
                
                $line = trim($line);
                if (empty($line)) continue; // Skip empty lines
                
                try {
                    // Parse CSV line
                    $row = str_getcsv($line);
                    
                    if (count($row) < 2) continue;
                    
                    $name = trim($row[0] ?? '');
                    $region_id = trim($row[1] ?? '');
                    
                    if (empty($name) || empty($region_id)) continue;
                    
                    // Find region by ID (code like MT08, IR07, etc)
                    $region = \App\Models\Region::where('code', $region_id)->first();
                    
                    if (!$region) {
                        $failed++;
                        $errors[] = "District '$name': Region ID '$region_id' not found";
                        continue;
                    }
                    
                    // Generate district code: Region Code + 2-digit sequential number
                    // Find highest number for this region
                    $lastDistrict = \App\Models\District::where('region_id', $region->id)
                        ->orderByRaw("CAST(SUBSTR(code, -2) AS UNSIGNED) DESC")
                        ->first();
                    
                    $nextNumber = 1;
                    if ($lastDistrict) {
                        $lastNumber = (int) substr($lastDistrict->code, -2);
                        $nextNumber = $lastNumber + 1;
                    }
                    
                    $code = $region_id . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
                    
                    // Check if district already exists by name in this region
                    $existing = \App\Models\District::where('region_id', $region->id)
                        ->where('name', $name)
                        ->first();
                    
                    if ($existing) {
                        // Update existing district
                        $existing->update([
                            'code' => $code,
                            'region_id' => $region->id,
                        ]);
                    } else {
                        // Create new district
                        \App\Models\District::create([
                            'code' => $code,
                            'name' => $name,
                            'region_id' => $region->id,
                            'status' => 'active',
                        ]);
                    }
                    
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row error: " . $e->getMessage();
                }
            }
            
            $message = "Imported $imported district(s)";
            if ($failed > 0) {
                $message .= ", $failed failed";
            }
            
            return response()->json([
                'message' => $message,
                'count' => $imported,
                'failed' => $failed,
                'errors' => $errors
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import error: ' . $e->getMessage()], 500);
        }
    });
    Route::post('/api/districts/bulk-delete', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:districts,id'
        ]);
        
        $deleted = \App\Models\District::whereIn('id', $validated['ids'])->delete();
        return response()->json(['deleted' => $deleted, 'message' => 'Districts deleted successfully']);
    });

    // Schools API Endpoints
    Route::get('/api/schools', function () {
        $page = request('page', 1);
        $pageSize = request('page_size', 10);
        $search = request('search', '');
        $regionId = request('region_id', '');
        $districtId = request('district_id', '');
        
        $query = \App\Models\School::with('district', 'district.region');
        
        // Filter by region if specified
        if ($regionId) {
            $query->whereHas('district', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        
        // Filter by district if specified (takes precedence over region)
        if ($districtId) {
            $query->where('district_id', $districtId);
        }
        
        // Search by code or name
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%");
            });
        }
        
        $total = $query->count();
        $schools = $query->skip(($page - 1) * $pageSize)
                         ->take($pageSize)
                         ->get();
        
        $data = $schools->map(function($s) {
            return [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'ownership' => $s->ownership,
                'district_id' => $s->district_id,
                'region_id' => $s->district->region_id ?? null,
                'district_name' => $s->district->name ?? null,
                'region_name' => $s->district->region->name ?? null,
                'candidates_count' => $s->candidates()->count(),
                'status' => 'active'
            ];
        });
        
        return response()->json([
            'data' => $data,
            'pagination' => [
                'total_count' => $total,
                'total_pages' => ceil($total / $pageSize),
                'current_page' => $page,
                'page_size' => $pageSize
            ]
        ]);
    });
    Route::post('/api/schools', function (\Illuminate\Http\Request $request) {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:schools',
                'name' => 'required',
                'ownership' => 'required|in:GOVERNMENT,NON-GOVERNMENT',
                'region_id' => 'required|exists:regions,id',
                'district_id' => 'required|exists:districts,id'
            ]);
            $school = \App\Models\School::create($validated);
            return response()->json(['message' => 'School added', 'data' => $school], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating school: ' . $e->getMessage()], 500);
        }
    });
    Route::put('/api/schools/{id}', function (\Illuminate\Http\Request $request, $id) {
        try {
            $school = \App\Models\School::find($id);
            if (!$school) return response()->json(['message' => 'School not found'], 404);
            
            $validated = $request->validate([
                'code' => 'required|unique:schools,code,'.$id,
                'name' => 'required',
                'ownership' => 'required|in:GOVERNMENT,NON-GOVERNMENT',
                'region_id' => 'required|exists:regions,id',
                'district_id' => 'required|exists:districts,id'
            ]);
            $school->update($validated);
            return response()->json(['message' => 'School updated', 'data' => $school]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating school: ' . $e->getMessage()], 500);
        }
    });
    Route::delete('/api/schools/{id}', function ($id) {
        $school = \App\Models\School::find($id);
        if (!$school) return response()->json(['message' => 'Not found'], 404);
        
        // Check if school has candidates registered
        $candidateCount = $school->candidates()->count();
        if ($candidateCount > 0) {
            return response()->json([
                'message' => "Cannot delete school with registered candidates",
                'details' => "This school has $candidateCount candidate(s) registered. Please remove all candidates first.",
                'count' => $candidateCount
            ], 409);
        }
        
        $school->delete();
        return response()->json(['message' => 'School deleted']);
    });
    Route::post('/api/schools/import', function (\Illuminate\Http\Request $request) {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        
        $file = $request->file('file');
        $path = $file->getRealPath();
        $csv = array_map('str_getcsv', file($path));
        
        if (empty($csv) || count($csv) < 2) {
            return response()->json(['message' => 'CSV file is empty'], 400);
        }
        
        // Get headers and normalize them
        $rawHeaders = $csv[0];
        $headers = [];
        foreach ($rawHeaders as $header) {
            $normalized = strtolower(trim($header));
            $normalized = str_replace(' ', '_', $normalized);
            $headers[] = $normalized;
        }
        
        $imported = 0;
        $errors = [];
        
        // Process each row
        for ($i = 1; $i < count($csv); $i++) {
            $rowData = $csv[$i];
            
            // Skip empty rows
            if (empty(array_filter($rowData))) {
                continue;
            }
            
            // Combine headers with data
            $row = [];
            foreach ($headers as $idx => $header) {
                $row[$header] = $rowData[$idx] ?? '';
            }
            
            try {
                // Validate required fields
                if (empty($row['code'])) {
                    $errors[] = "Row " . ($i + 1) . ": Code is required";
                    continue;
                }
                if (empty($row['name'])) {
                    $errors[] = "Row " . ($i + 1) . ": Name is required";
                    continue;
                }
                
                // Get district_id - try numeric ID first, then code, then name
                 $districtId = null;
                 if (!empty($row['district_id'])) {
                     $districtVal = trim($row['district_id']);
                     
                     // Try as numeric ID first
                     if (is_numeric($districtVal)) {
                         $districtExists = \App\Models\District::find((int)$districtVal);
                         $districtId = $districtExists ? (int)$districtVal : null;
                     }
                     
                     // If not found, try as code
                     if (!$districtId && $districtVal) {
                         $district = \App\Models\District::where('code', $districtVal)->first();
                         $districtId = $district ? $district->id : null;
                     }
                 } elseif (!empty($row['district'])) {
                     // Try to find by name
                     $district = \App\Models\District::where('name', trim($row['district']))->first();
                     $districtId = $district ? $district->id : null;
                 }
                 
                 // Get region_id - try numeric ID first, then code, then name
                 $regionId = null;
                 if (!empty($row['region_id'])) {
                     $regionVal = trim($row['region_id']);
                     
                     // Try as numeric ID first
                     if (is_numeric($regionVal)) {
                         $regionExists = \App\Models\Region::find((int)$regionVal);
                         $regionId = $regionExists ? (int)$regionVal : null;
                     }
                     
                     // If not found, try as code
                     if (!$regionId && $regionVal) {
                         $region = \App\Models\Region::where('code', $regionVal)->first();
                         $regionId = $region ? $region->id : null;
                     }
                 } elseif (!empty($row['region'])) {
                     // Try to find by name
                     $region = \App\Models\Region::where('name', trim($row['region']))->first();
                     $regionId = $region ? $region->id : null;
                 }
                
                // Check if school already exists by code
                $exists = \App\Models\School::where('code', trim($row['code']))->first();
                if ($exists) {
                    continue; // Skip duplicates silently
                }
                
                // Create school
                \App\Models\School::create([
                    'code' => trim($row['code']),
                    'name' => trim($row['name']),
                    'ownership' => trim($row['ownership']) ?? 'PUBLIC',
                    'district_id' => $districtId,
                    'region_id' => $regionId,
                    'is_active' => true,
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }
        
        return response()->json([
            'message' => "Imported $imported school(s)",
            'count' => $imported,
            'errors' => $errors
        ]);
    });
    Route::post('/api/schools/bulk-delete', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:schools,id'
        ]);
        
        $deleted = \App\Models\School::whereIn('id', $validated['ids'])->delete();
        return response()->json(['deleted' => $deleted, 'message' => 'Schools deleted successfully']);
    });

    // Candidates API Endpoints
    Route::get('/api/candidates', function () {
         $page = request('page', 1);
         $pageSize = request('page_size', 10);
         $search = request('search', '');
         $schoolId = request('school_id', '');
         $districtId = request('district_id', '');
         $regionId = request('region_id', '');
         $examType = request('exam_type', '');
         
         $query = \App\Models\Candidate::with('school', 'school.district', 'school.district.region', 'examRegistrations.examYear');
        
        // Filter by school if specified
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

        // Filter by exam_type if specified
        if ($examType) {
            $query->where('exam_type', strtoupper($examType));
        }
        
        // Search by candidate_id, full_name, combination, school name, exam_type
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('candidate_id', 'like', "%$search%")
                  ->orWhere('full_name', 'like', "%$search%")
                  ->orWhere('combination', 'like', "%$search%")
                  ->orWhere('exam_type', 'like', "%$search%")
                  ->orWhereHas('school', function($q) use ($search) {
                       $q->where('name', 'like', "%$search%");
                   });
            });
        }
        
        $total = $query->count();
        $candidates = $query->skip(($page - 1) * $pageSize)
                             ->take($pageSize)
                             ->get();
        
        $data = $candidates->map(function($c) {
            return [
                'id' => $c->id,
                'candidate_id' => $c->candidate_id,
                'full_name' => $c->full_name,
                'gender' => $c->gender,
                'combination' => $c->combination ?? null,
                'school_id' => $c->school_id,
                'school_name' => $c->school->name ?? null,
                'exam_type' => $c->exam_type,
                'exam_year' => $c->exam_year,
                'candidate_type' => $c->candidate_type ?? 'SCHOOL',
                'status' => $c->status ?? 'registered'
            ];
        });
        
        return response()->json([
            'data' => $data,
            'pagination' => [
                'total_count' => $total,
                'total_pages' => ceil($total / $pageSize),
                'current_page' => $page,
                'page_size' => $pageSize
            ]
        ]);
    });

    Route::post('/api/candidates', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'nullable|unique:candidates,candidate_id',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'combination' => 'nullable|string|max:255',
            'combination_id' => 'nullable|exists:combinations,id',
            'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
            'candidate_type' => 'nullable|in:SCHOOL,PRIVATE',
            'status' => 'nullable|string|max:255',
        ]);
        
        // Auto-generate candidate_id if not provided
        if (empty($validated['candidate_id'])) {
            $count = \App\Models\Candidate::count() + 1;
            $validated['candidate_id'] = 'CAND-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        }
        
        $candidate = \App\Models\Candidate::create($validated);
        
        return response()->json(['message' => 'Candidate registered successfully', 'data' => $candidate->load('school')], 201);
    });

    Route::put('/api/candidates/{id}', function (\Illuminate\Http\Request $request, $id) {
        $candidate = \App\Models\Candidate::findOrFail($id);
        
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'nullable|unique:candidates,candidate_id,' . $candidate->id,
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'combination' => 'nullable|string|max:255',
            'combination_id' => 'nullable|exists:combinations,id',
            'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
            'candidate_type' => 'nullable|in:SCHOOL,PRIVATE',
            'status' => 'nullable|string|max:255',
        ]);
        
        $candidate->update($validated);
        
        return response()->json(['message' => 'Candidate updated successfully', 'data' => $candidate->load('school')]);
    });

    Route::delete('/api/candidates/{id}', function ($id) {
        $candidate = \App\Models\Candidate::findOrFail($id);
        
        // Check if candidate has marks/results entered
        $subjectMarksCount = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)->count();
        $resultsCount = \App\Models\CandidateResult::where('candidate_id', $candidate->id)->count();
        
        if ($subjectMarksCount > 0 || $resultsCount > 0) {
            return response()->json([
                'message' => "Cannot delete candidate with marks or results",
                'details' => "This candidate has " . $subjectMarksCount . " subject mark(s) and " . $resultsCount . " result(s) entered. Please remove marks/results first.",
                'subject_marks_count' => $subjectMarksCount,
                'results_count' => $resultsCount
            ], 409);
        }
        
        $candidate->delete();
        return response()->json(['message' => 'Candidate deleted successfully']);
    });

    Route::post('/api/candidates/import/check', function (\Illuminate\Http\Request $request) {
         // Pre-import validation endpoint
         $request->validate([
             'file' => 'required|file|mimes:csv,txt',
             'exam_year' => 'nullable|string|regex:/^\d{4}$/',
             'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE'
         ]);
         
         $file = $request->file('file');
         $handle = fopen($file->getRealPath(), 'r');
         
         $conflicts = [];
         $rowNumber = 0;
         $header = fgetcsv($handle);
         
         while (($row = fgetcsv($handle)) !== false) {
             $rowNumber++;
             
             if (empty(array_filter($row))) {
                 continue;
             }
             
             if (count($row) < 5) {
                 continue;
             }
             
             // CSV format: candidate_id, full_name, gender, combination, school_code, exam_type, exam_year
             $candidateId = trim($row[0] ?? '') ?: null;
             if (!empty($candidateId)) {
                 $existing = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                 if ($existing) {
                     $conflicts[] = [
                         'row' => $rowNumber,
                         'candidate_id' => $candidateId,
                         'full_name' => trim($row[1] ?? ''),
                         'action' => 'update'
                     ];
                 }
             }
         }
         
         fclose($handle);
         
         return response()->json([
             'has_conflicts' => count($conflicts) > 0,
             'conflicts' => $conflicts,
             'conflict_count' => count($conflicts)
         ]);
     });

    Route::post('/api/candidates/import', function (\Illuminate\Http\Request $request) {
         $request->validate([
             'file' => 'required|file|mimes:csv,txt',
             'exam_year' => 'nullable|string|regex:/^\d{4}$/',
             'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
             'mode' => 'nullable|in:skip,replace,replace-all'
         ]);
         
         $file = $request->file('file');
         $handle = fopen($file->getRealPath(), 'r');
         
         $count = 0;
         $skipped = 0;
         $replaced = 0;
         $errors = [];
         $rowNumber = 0;
         $header = fgetcsv($handle);
         
         // Get exam year and type from request
         $examYearValue = $request->input('exam_year');
         $examTypeOverride = $request->input('exam_type');
         $importMode = $request->input('mode', 'skip');
         
         \Log::info('Candidate import started', ['header' => $header, 'exam_year' => $examYearValue]);
         
         while (($row = fgetcsv($handle)) !== false) {
         $rowNumber++;
         
         // Skip empty rows
         if (empty(array_filter($row))) {
         continue;
         }
         
         if (count($row) < 5) {
         $errors[] = "Row $rowNumber: Insufficient columns (expected minimum 5, got " . count($row) . ")";
         continue;
         }
         
         try {
         // CSV format: candidate_id, full_name, gender, combination, school_code, exam_type, exam_year
         $candidateId = trim($row[0] ?? '') ?: null;
         $fullName = trim($row[1] ?? '');
         $gender = trim($row[2] ?? '');
         $combination = trim($row[3] ?? '') ?: null;
         $schoolCode = trim($row[4] ?? '');
         $examType = trim($row[5] ?? '') ?: $examTypeOverride;
         $csvExamYear = trim($row[6] ?? '') ?: null;
                 
                 \Log::debug('Processing row', [
                     'rowNumber' => $rowNumber,
                     'candidateId' => $candidateId,
                     'fullName' => $fullName,
                     'schoolCode' => $schoolCode,
                     'examType' => $examType,
                     'csvExamYear' => $csvExamYear,
                     'columnsCount' => count($row)
                 ]);
                 
                 // Validate required fields
                 if (empty($fullName)) {
                     $errors[] = "Row $rowNumber: Missing Full Name";
                     continue;
                 }
                 if (empty($gender)) {
                     $errors[] = "Row $rowNumber: Missing Sex";
                     continue;
                 }
                 if (empty($schoolCode)) {
                     $errors[] = "Row $rowNumber: Missing School Code";
                     continue;
                 }
                 if (empty($examType)) {
                     $errors[] = "Row $rowNumber: Missing Exam Type";
                     continue;
                 }
                 
                 // Look up school by registration_number or code
                 $school = \App\Models\School::where('registration_number', $schoolCode)
                     ->orWhere('code', $schoolCode)
                     ->first();
                 if (!$school) {
                     $errors[] = "Row $rowNumber: School code '$schoolCode' does not exist";
                     continue;
                 }
                 $schoolId = $school->id;
                 
                 // Auto-generate candidate_id if not provided
                 if (empty($candidateId)) {
                     $candidateCount = \App\Models\Candidate::count() + $count + 1;
                     $candidateId = 'CAND-' . str_pad($candidateCount, 6, '0', STR_PAD_LEFT);
                 }
                 
                 // Check if candidate already exists
                 $existingCandidate = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                 
                 // Handle import mode
                 if ($existingCandidate) {
                     if ($importMode === 'skip') {
                         $skipped++;
                         continue;
                     } elseif ($importMode === 'replace') {
                         // Replace mode - ask which ones to replace (handled at frontend)
                         $replaced++;
                     } elseif ($importMode === 'replace-all') {
                         // Replace all - update without asking
                         $replaced++;
                     }
                 }
                 
                 $candidate = \App\Models\Candidate::updateOrCreate(
                     ['candidate_id' => $candidateId],
                     [
                         'full_name' => $fullName,
                         'gender' => strtoupper($gender),
                         'combination' => $combination,
                         'school_id' => $schoolId,
                         'exam_type' => strtoupper($examType),
                         'status' => 'registered'
                     ]
                 );
                 
                 // Register for ACSEE if applicable and exam year provided (from CSV or modal)
                 // IMPORTANT: Prefer CSV exam year if provided, otherwise use modal exam year
                 // For proper ACSEE registration, exam year MUST come from one of these sources
                 $yearForRegistration = !empty($csvExamYear) ? $csvExamYear : $examYearValue;
                 
                 \Log::debug('ACSEE registration check', [
                     'examType' => strtoupper($examType),
                     'csvExamYear' => $csvExamYear,
                     'examYearValue' => $examYearValue,
                     'yearForRegistration' => $yearForRegistration,
                     'combination' => $combination,
                     'isACSEE' => (strtoupper($examType) === 'ACSEE'),
                     'willRegister' => (strtoupper($examType) === 'ACSEE' && !empty($yearForRegistration) && !empty($combination))
                 ]);
                 
                 if (strtoupper($examType) === 'ACSEE' && !empty($yearForRegistration) && !empty($combination)) {
                     try {
                         // Use the CandidateController's registerForACSEE method
                         $controller = app(\App\Http\Controllers\CandidateController::class);
                         $reflection = new ReflectionMethod($controller, 'registerForACSEE');
                         $reflection->setAccessible(true);
                         $reflection->invoke($controller, $candidate, $combination, $yearForRegistration);
                     } catch (\Exception $e) {
                         \Log::warning('ACSEE registration failed during import', [
                             'candidate_id' => $candidateId,
                             'exam_year' => $yearForRegistration,
                             'error' => $e->getMessage()
                         ]);
                     }
                 }
                 
                 $count++;
             } catch (\Exception $e) {
                 $errors[] = "Row $rowNumber error: " . $e->getMessage();
                 \Log::error('Row import error', ['rowNumber' => $rowNumber, 'error' => $e->getMessage()]);
                 continue;
             }
         }
         
         fclose($handle);
         
         \Log::info('Candidate import completed', [
             'count' => $count,
             'skipped' => $skipped,
             'replaced' => $replaced,
             'errors_count' => count($errors),
             'errors' => $errors
         ]);
         
         return response()->json([
             'message' => $count . ' candidate(s) imported successfully' . (count($errors) > 0 ? ' with ' . count($errors) . ' error(s)' : ''),
             'count' => $count,
             'skipped' => $skipped,
             'replaced' => $replaced,
             'errors' => $errors
         ]);
     });

     // School CSV Import Endpoint
     Route::post('/api/schools/import', function (\Illuminate\Http\Request $request) {
         $request->validate([
             'file' => 'required|file|mimes:csv,txt'
         ]);
         
         $file = $request->file('file');
         $handle = fopen($file->getRealPath(), 'r');
         
         $count = 0;
         $errors = [];
         $rowNumber = 0;
         $header = fgetcsv($handle);
         
         \Log::info('School import started', ['header' => $header]);
         
         while (($row = fgetcsv($handle)) !== false) {
             $rowNumber++;
             
             // Skip empty rows
             if (empty(array_filter($row))) {
                 continue;
             }
             
             if (count($row) < 5) {
                 $errors[] = "Row $rowNumber: Insufficient columns (expected 5, got " . count($row) . ")";
                 continue;
             }
             
             try {
                 $registrationCode = trim($row[0] ?? '');
                 $name = trim($row[1] ?? '');
                 $ownership = trim($row[2] ?? '');
                 $regionCode = trim($row[3] ?? '');
                 $districtCode = trim($row[4] ?? '');
                 
                 // Validate required fields
                 if (empty($registrationCode)) {
                     $errors[] = "Row $rowNumber: Missing School Code";
                     continue;
                 }
                 
                 if (empty($name)) {
                     $errors[] = "Row $rowNumber: Missing School Name";
                     continue;
                 }
                 
                 if (empty($regionCode)) {
                     $errors[] = "Row $rowNumber: Missing Region Code";
                     continue;
                 }
                 
                 if (empty($districtCode)) {
                     $errors[] = "Row $rowNumber: Missing District Code";
                     continue;
                 }
                 
                 // Look up region by code
                 $region = \App\Models\Region::where('code', $regionCode)->first();
                 if (!$region) {
                     $errors[] = "Row $rowNumber: Region code '$regionCode' does not exist";
                     continue;
                 }
                 
                 // Look up district by code
                 $district = \App\Models\District::where('code', $districtCode)->first();
                 if (!$district) {
                     $errors[] = "Row $rowNumber: District code '$districtCode' does not exist";
                     continue;
                 }
                 
                 // Verify district belongs to region
                 if ($district->region_id != $region->id) {
                     $errors[] = "Row $rowNumber: District code '$districtCode' does not belong to region '$regionCode'";
                     continue;
                 }
                 
                 // Create or update school
                 \App\Models\School::updateOrCreate(
                     ['registration_number' => $registrationCode],
                     [
                         'code' => $registrationCode,
                         'name' => $name,
                         'registration_number' => $registrationCode,
                         'ownership' => $ownership,
                         'region_id' => $region->id,
                         'district_id' => $district->id,
                         'school_type' => 'SECONDARY',
                         'is_active' => true
                     ]
                 );
                 
                 $count++;
                 
             } catch (\Exception $e) {
                 $errors[] = "Row $rowNumber error: " . $e->getMessage();
                 \Log::error('Row import error', ['rowNumber' => $rowNumber, 'error' => $e->getMessage()]);
                 continue;
             }
         }
         
         fclose($handle);
         
         \Log::info('School import completed', [
             'count' => $count,
             'errors_count' => count($errors),
             'errors' => $errors
         ]);
         
         return response()->json([
             'message' => $count . ' school(s) imported successfully' . (count($errors) > 0 ? ' with ' . count($errors) . ' error(s)' : ''),
             'count' => $count,
             'errors' => $errors
         ]);
     });

    // New Structured Candidate Import API (two-phase)
    Route::post('/api/candidates/import/validate', [CandidateImportController::class, 'validateImport']);
    Route::post('/api/candidates/import/commit', [CandidateImportController::class, 'commitImport']);
    Route::post('/api/candidates/import/async', [CandidateImportController::class, 'asyncBulkImport']);
    Route::get('/api/candidates/import/template', [CandidateImportController::class, 'downloadTemplate']);
    Route::post('/api/candidates/import/download-errors', [CandidateImportController::class, 'downloadErrorReport']);

    // Bulk delete
    Route::post('/api/candidates/bulk-delete', function (\Illuminate\Http\Request $request) {
         $validated = $request->validate([
             'ids' => 'required|array',
             'ids.*' => 'integer|exists:candidates,id'
         ]);
         
         $deleted = \App\Models\Candidate::whereIn('id', $validated['ids'])->delete();
         return response()->json(['deleted' => $deleted, 'message' => 'Candidates deleted successfully']);
     });

    // Data Audit Endpoints
    Route::get('/api/audit/candidates', function () {
         $totalSchools = \App\Models\School::count();
         $totalCandidates = \App\Models\Candidate::count();
         $schoolsWithoutDistrict = \App\Models\School::whereNull('district_id')->count();
         
         // Find school-district mismatches
         $mismatches = \App\Models\Candidate::whereHas('school', function($q) {
             $q->whereNull('district_id');
         })->with('school')->get()->map(function($candidate) {
             return [
                 'candidate_id' => $candidate->candidate_id,
                 'candidate_name' => $candidate->full_name,
                 'school_id' => $candidate->school_id,
                 'school_code' => $candidate->school?->code,
                 'school_name' => $candidate->school?->name,
                 'expected_district_id' => null,
                 'actual_district_id' => $candidate->school?->district_id
             ];
         })->toArray();
         
         return response()->json([
             'total_schools' => $totalSchools,
             'total_candidates' => $totalCandidates,
             'schools_without_district' => $schoolsWithoutDistrict,
             'mismatches' => $mismatches
         ]);
     });

    Route::post('/api/audit/candidates/fix', function (\Illuminate\Http\Request $request) {
         $validated = $request->validate([
             'mismatches' => 'required|array'
         ]);
         
         $fixed = 0;
         foreach ($validated['mismatches'] as $mismatch) {
             $candidate = \App\Models\Candidate::find($mismatch['candidate_id'] ?? null);
             if ($candidate && $candidate->school && $candidate->school->district_id) {
                 $candidate->update(['school_id' => $candidate->school_id]);
                 $fixed++;
             }
         }
         
         return response()->json([
             'fixed' => $fixed,
             'message' => "$fixed candidate(s) fixed"
         ]);
     });

     // Exam Types API Endpoints
     Route::get('/api/exam-types', function () {
         $examTypes = \App\Models\ExamType::withCount('candidates')->get();
         
         $data = $examTypes->map(function($e) {
             return [
                 'id' => $e->id,
                 'name' => $e->name,
                 'code' => $e->code,
                 'level' => $e->level,
                 'description' => $e->description,
                 'candidates_count' => $e->candidates_count ?? 0
             ];
         });
         
         return response()->json(['data' => $data]);
     });

     Route::get('/api/exam-types/{code}', function ($code) {
         $examType = \App\Models\ExamType::where('code', strtoupper($code))
             ->withCount('candidates')
             ->firstOrFail();
         
         return response()->json([
             'data' => [
                 'id' => $examType->id,
                 'name' => $examType->name,
                 'code' => $examType->code,
                 'level' => $examType->level,
                 'description' => $examType->description,
                 'candidates_count' => $examType->candidates_count ?? 0
             ]
         ]);
     });

     Route::post('/api/exam-types', function (\Illuminate\Http\Request $request) {
         $validated = $request->validate([
             'name' => 'required|unique:exam_types,name',
             'code' => 'required|unique:exam_types,code',
             'level' => 'nullable|string|max:255',
             'description' => 'nullable|string'
         ]);
         
         $examType = \App\Models\ExamType::create($validated);
         return response()->json(['message' => 'Exam type created successfully', 'data' => $examType], 201);
     });

     Route::put('/api/exam-types/{id}', function (\Illuminate\Http\Request $request, $id) {
         $examType = \App\Models\ExamType::findOrFail($id);
         
         $validated = $request->validate([
             'name' => 'required|unique:exam_types,name,' . $examType->id,
             'code' => 'required|unique:exam_types,code,' . $examType->id,
             'level' => 'nullable|string|max:255',
             'description' => 'nullable|string'
         ]);
         
         $examType->update($validated);
         return response()->json(['message' => 'Exam type updated successfully', 'data' => $examType]);
     });

     Route::delete('/api/exam-types/{id}', function ($id) {
         $examType = \App\Models\ExamType::findOrFail($id);
         $examType->delete();
         return response()->json(['message' => 'Exam type deleted successfully']);
     });

     // Subjects API Endpoints for ACSEE
     Route::get('/api/exam-types/{code}/subjects', [ExamTypeController::class, 'getSubjects']);
     Route::post('/api/exam-types/{code}/subjects', [ExamTypeController::class, 'createSubject']);
     Route::put('/api/exam-types/{code}/subjects/{id}', [ExamTypeController::class, 'updateSubject']);
     Route::delete('/api/exam-types/{code}/subjects/{id}', [ExamTypeController::class, 'deleteSubject']);

     // Global Subjects API
     Route::get('/api/subjects', function () {
         try {
             $subjects = \App\Models\Subject::orderBy('name')->get(['id', 'code', 'name']);
             return response()->json(['data' => $subjects]);
         } catch (\Exception $e) {
             \Log::error('Subjects API error:', ['error' => $e->getMessage()]);
             return response()->json(['data' => []], 200);
         }
     });

     // Daily Marks Entry Report API
     Route::get('/api/daily-marks-entry-report', function (Request $request) {
         try {
             $examYearId = $request->get('exam_year_id', '');
             $regionId = $request->get('region_id', '');
             $subjectId = $request->get('subject_id', '');
             $entryDate = $request->get('entry_date', '');

             // Get all subjects first
             $subjects = \App\Models\Subject::orderBy('name')->get(['id', 'name', 'code']);

             // If specific subject is selected, filter to that subject only
             if ($subjectId) {
                 $subjects = $subjects->where('id', $subjectId);
             }

             // Build report data by aggregating marks entries
             $reportData = [];
             $sn = 1;

             foreach ($subjects as $subject) {
                 // Query marks for this subject with all relationships
                 $marksQuery = \App\Models\SubjectMarks::where('subject_id', $subject->id);

                 // Filter by exam year if provided
                 if ($examYearId) {
                     $examYear = \App\Models\ExamYear::find($examYearId);
                     if ($examYear) {
                         // Extract year from year_label (e.g., "2026" from "2026")
                         $yearValue = preg_replace('/[^0-9]/', '', $examYear->year_label);
                         if ($yearValue) {
                             $marksQuery->where('year', $yearValue);
                         }
                     }
                 }

                 // Filter by region if provided
                 if ($regionId) {
                     $marksQuery->whereHas('candidate', function($q) use ($regionId) {
                         $q->whereHas('school', function($sq) use ($regionId) {
                             $sq->whereHas('district', function($dq) use ($regionId) {
                                 $dq->where('region_id', $regionId);
                             });
                         });
                     });
                 }

                 $marks = $marksQuery->with('candidate.school.district.region')->get();

                 // Count total expected scripts (candidates)
                 $totalCandidates = $marks->count();
                 if ($totalCandidates === 0) {
                     continue;
                 }

                 // Get entry dates from created_at and group by day
                 $dayGroups = [
                     'day1' => 0,
                     'day2' => 0,
                     'day3' => 0,
                     'day4' => 0,
                     'day5' => 0,
                     'remainder' => 0,
                 ];

                 // Simple logic: count entries by creation date
                 $now = now();
                 foreach ($marks as $mark) {
                     $daysOld = $mark->created_at->diffInDays($now);
                     
                     if ($daysOld <= 1) {
                         $dayGroups['day1']++;
                     } elseif ($daysOld <= 2) {
                         $dayGroups['day2']++;
                     } elseif ($daysOld <= 3) {
                         $dayGroups['day3']++;
                     } elseif ($daysOld <= 4) {
                         $dayGroups['day4']++;
                     } elseif ($daysOld <= 5) {
                         $dayGroups['day5']++;
                     } else {
                         $dayGroups['remainder']++;
                     }
                 }

                 // Calculate percentages
                 $day1Pct = $totalCandidates > 0 ? ($dayGroups['day1'] / $totalCandidates) * 100 : 0;
                 $day2Pct = $totalCandidates > 0 ? ($dayGroups['day2'] / $totalCandidates) * 100 : 0;
                 $day3Pct = $totalCandidates > 0 ? ($dayGroups['day3'] / $totalCandidates) * 100 : 0;
                 $day4Pct = $totalCandidates > 0 ? ($dayGroups['day4'] / $totalCandidates) * 100 : 0;
                 $day5Pct = $totalCandidates > 0 ? ($dayGroups['day5'] / $totalCandidates) * 100 : 0;
                 $remainderPct = $totalCandidates > 0 ? ($dayGroups['remainder'] / $totalCandidates) * 100 : 0;

                 $reportData[] = [
                     's_n' => $sn++,
                     'subject_id' => $subject->id,
                     'subject_code' => $subject->code,
                     'subject_name' => $subject->name,
                     'expected_scripts' => $totalCandidates,
                     'day1_count' => $dayGroups['day1'],
                     'day1_percentage' => $day1Pct,
                     'day2_count' => $dayGroups['day2'],
                     'day2_percentage' => $day2Pct,
                     'day3_count' => $dayGroups['day3'],
                     'day3_percentage' => $day3Pct,
                     'day4_count' => $dayGroups['day4'],
                     'day4_percentage' => $day4Pct,
                     'day5_count' => $dayGroups['day5'],
                     'day5_percentage' => $day5Pct,
                     'remainder_count' => $dayGroups['remainder'],
                     'remainder_percentage' => $remainderPct,
                     'remarks' => ''
                 ];
             }

             return response()->json($reportData);
         } catch (\Exception $e) {
             \Log::error('Daily Marks Entry Report API error:', [
                 'error' => $e->getMessage(),
                 'trace' => $e->getTraceAsString()
             ]);
             return response()->json([], 200);
         }
     });

     // Candidates API Endpoints for ACSEE
     Route::get('/api/exam-types/{examTypeCode}/candidates', [ExamTypeController::class, 'getAcseeCandicates']);

     // Combinations API Endpoints for ACSEE
      Route::get('/api/exam-types/{code}/combinations', [ExamTypeController::class, 'getCombinations']);
      Route::post('/api/exam-types/{code}/combinations', [ExamTypeController::class, 'createCombination']);
      Route::put('/api/exam-types/{code}/combinations/{id}', [ExamTypeController::class, 'updateCombination']);
      Route::delete('/api/exam-types/{code}/combinations/{id}', [ExamTypeController::class, 'deleteCombination']);

      // Subject Allocation for ACSEE
      Route::post('/api/exam-types/acsee/allocate-subjects', function (\Illuminate\Http\Request $request) {
          // Validate input
          $validated = $request->validate([
              'candidate_id' => 'required|exists:candidates,id',
              'exam_year_id' => 'required|exists:exam_years,id',
              'subject_ids' => 'required|array|min:1',
              'subject_ids.*' => 'integer|exists:subjects,id',
              'is_principal_map' => 'required|array',
              'replace_allocations' => 'boolean|default:false',
              'source' => 'required|in:manual,template',
          ]);

          try {
              // Load candidate with exam registration context
              $candidate = \App\Models\Candidate::findOrFail($validated['candidate_id']);
              
              // Get exam_type_id from candidate
              $examRegistration = $candidate->examRegistrations()->first();
              if (!$examRegistration) {
                  return response()->json([
                      'ok' => false,
                      'errors' => ['Candidate does not have an exam registration'],
                      'warnings' => [],
                  ], 422);
              }

              // Run validator
              $validator = new \App\Services\AcseeAllocationValidator();
              $validation = $validator->validate(
                  $candidate,
                  $examRegistration->exam_type_id,
                  $validated['exam_year_id'],
                  $validated['subject_ids']
              );

              if (!$validation['ok']) {
                  return response()->json([
                      'ok' => false,
                      'errors' => $validation['errors'],
                      'warnings' => $validation['warnings'],
                      'allocated_subjects' => [],
                  ], 422);
              }

              // Transactional allocation commit
              \Illuminate\Support\Facades\DB::transaction(function () use ($candidate, $validated, $validation, $examRegistration) {
                  if ($validated['replace_allocations'] ?? false) {
                      // Delete existing allocations for this exam_year
                      $candidate->subjectSelections()
                          ->where('exam_year_id', $validated['exam_year_id'])
                          ->delete();
                  }

                  // Create new allocations
                  foreach ($validation['all_subject_ids'] as $subjectId) {
                      $isPrincipal = in_array($subjectId, $validation['principal_subject_ids']);
                      
                      \App\Models\CandidateSubjectSelection::updateOrCreate(
                          [
                              'candidate_id' => $candidate->id,
                              'exam_type_id' => $examRegistration->exam_type_id,
                              'exam_year_id' => $validated['exam_year_id'],
                              'subject_id' => $subjectId,
                              'year' => \App\Models\ExamYear::find($validated['exam_year_id'])->year ?? date('Y'),
                          ],
                          [
                              'is_principal' => $isPrincipal,
                              'source' => $validated['source'],
                              'created_by' => auth()->id(),
                              'is_active' => true,
                          ]
                      );
                  }
              });

              // Return allocated subjects
              $allocated = $candidate->subjectSelections()
                  ->with('subject')
                  ->where('exam_year_id', $validated['exam_year_id'])
                  ->get();

              return response()->json([
                  'ok' => true,
                  'message' => 'Subjects allocated successfully',
                  'allocated_subjects' => $allocated->map(fn($s) => [
                      'id' => $s->subject_id,
                      'code' => $s->subject->code,
                      'name' => $s->subject->name,
                      'is_principal' => $s->is_principal,
                  ]),
                  'created_count' => count($validation['all_subject_ids']),
                  'skipped_count' => 0,
              ]);
          } catch (\Exception $e) {
              \Log::error('Allocation error: ' . $e->getMessage(), ['exception' => $e]);
              
              // Sanitize error message for production
              $errorMessage = env('APP_ENV') === 'production'
                  ? 'An error occurred while allocating subjects. Please try again.'
                  : 'Database error: ' . $e->getMessage();
              
              return response()->json([
                  'ok' => false,
                  'errors' => [$errorMessage],
                  'warnings' => [],
                  'allocated_subjects' => [],
              ], 500);
          }
      });

      // Get combination subjects for preview
      Route::get('/api/combinations/{id}/subjects', function ($id) {
          try {
              $combination = \App\Models\Combination::findOrFail($id);
              $subjects = $combination->subjects()->select('subjects.id', 'subjects.code', 'subjects.name')->get();
              
              return response()->json([
                  'ok' => true,
                  'data' => $subjects,
              ]);
          } catch (\Exception $e) {
              return response()->json([
                  'ok' => false,
                  'errors' => ['Combination not found'],
              ], 404);
          }
      });

      // ACSEE Allocation CSV Import Routes
      Route::get('/api/exam-types/acsee/templates/school-allocation.csv', [AcseeAllocationController::class, 'getSchoolTemplate']);
      Route::get('/api/exam-types/acsee/templates/private-allocation.csv', [AcseeAllocationController::class, 'getPrivateTemplate']);
      Route::post('/api/exam-types/acsee/allocate-from-csv/validate', [AcseeAllocationController::class, 'validateAllocationImport']);
      Route::post('/api/exam-types/acsee/allocate-from-csv/commit', [AcseeAllocationController::class, 'commitAllocationImport']);
      Route::post('/api/exam-types/acsee/allocate-from-csv', [AcseeAllocationController::class, 'importAllocations']);
      Route::post('/api/exam-types/acsee/allocate-from-csv/download-errors', [AcseeAllocationController::class, 'downloadErrorReport']);

      // Mark Entry Module Routes (ACSEE)
      Route::get('/mark-entry/acsee', [MarkEntryController::class, 'index']);
      Route::get('/mark-entry/acsee/download-template', [MarkEntryController::class, 'downloadTemplate']);
      // DEPRECATED: legacy upload endpoint (use validate/commit)
      Route::post('/mark-entry/acsee/upload', [MarkEntryController::class, 'uploadMarks']);
      
      // New Two-Phase Single Subject CSV Endpoints
      Route::post('/mark-entry/acsee/single/validate', [MarkEntryController::class, 'singleValidate']);
      Route::post('/mark-entry/acsee/single/commit', [MarkEntryController::class, 'singleCommit']);

      // New Bulk ZIP Validate/Commit + Progress
      Route::post('/mark-entry/acsee/bulk/school/validate-zip', [MarkEntryController::class, 'schoolValidateZip']);
      Route::post('/mark-entry/acsee/bulk/district/validate-zip', [MarkEntryController::class, 'districtValidateZip']);
      Route::post('/mark-entry/acsee/bulk/school/commit-zip', [MarkEntryController::class, 'schoolCommitZip']);
      Route::post('/mark-entry/acsee/bulk/district/commit-zip', [MarkEntryController::class, 'districtCommitZip']);
      Route::get('/mark-entry/acsee/bulk/{id}/progress', [MarkEntryController::class, 'bulkProgress']);

      Route::get('/test-upload', function() { return response()->json(['status' => 'ok']); });
      Route::get('/mark-entry/acsee/batch/{batchId}', [MarkEntryController::class, 'getBatchDetails']);
      Route::get('/mark-entry/acsee/batch/{batchId}/error-report', [MarkEntryController::class, 'downloadErrorReport']);
      Route::post('/mark-entry/acsee/batch/{batchId}/lock', [MarkEntryController::class, 'lockBatch']);
      
      // Scoresheet PDF Routes
      Route::get('/mark-entry/acsee/scoresheet/print', [MarkEntryController::class, 'printScoresheet']);
      Route::get('/mark-entry/acsee/scoresheet/bulk-export', [MarkEntryController::class, 'bulkExportScoresheets']);

      // Bulk CSV Export Route (School)
      Route::get('/mark-entry/acsee/bulk-csv-download', [MarkEntryController::class, 'downloadBulkCsvExport']);

      // District Bulk CSV Export Route
      Route::get('/mark-entry/acsee/district-bulk-csv-download', [MarkEntryController::class, 'downloadDistrictBulkCsvExport']);

      // District Bulk Scoresheet Export Route
      Route::get('/mark-entry/acsee/district-bulk-scoresheet-download', [MarkEntryController::class, 'downloadDistrictBulkScoresheetExport']);

      // Bulk Import Routes (must be in web.php for session/CSRF support)
      Route::post('/api/bulk-import/preview', [\App\Http\Controllers\BulkImportController::class, 'preview']);
      Route::post('/api/bulk-import/start', [\App\Http\Controllers\BulkImportController::class, 'startImport']);
      Route::post('/api/bulk-import/district/start', [\App\Http\Controllers\BulkImportController::class, 'startDistrictImport']);
      Route::get('/api/bulk-import/{id}/progress', [\App\Http\Controllers\BulkImportController::class, 'getProgress']);
      Route::get('/api/bulk-import/{id}/recovery-status', [\App\Http\Controllers\BulkImportController::class, 'getRecoveryStatus']);
      Route::post('/api/bulk-import/{id}/retry-school', [\App\Http\Controllers\BulkImportController::class, 'retrySchool']);
      Route::post('/api/bulk-import/{id}/retry-all', [\App\Http\Controllers\BulkImportController::class, 'retryAll']);
      Route::get('/api/bulk-import/{id}', [\App\Http\Controllers\BulkImportController::class, 'getDetails']);

      // Mark Entry API Endpoints
      Route::get('/api/mark-entry/acsee/regions', [MarkEntryController::class, 'getRegions']);
      Route::get('/api/mark-entry/acsee/districts', [MarkEntryController::class, 'getDistricts']);
      Route::get('/api/mark-entry/acsee/schools', [MarkEntryController::class, 'getSchools']);
      Route::get('/api/mark-entry/acsee/schools-by-year', [MarkEntryController::class, 'getSchoolsByYear']);
      Route::get('/api/mark-entry/acsee/districts-by-year', [MarkEntryController::class, 'getDistrictsByYear']);
      Route::get('/api/mark-entry/acsee/subjects', [MarkEntryController::class, 'getSubjects']);
      Route::get('/api/mark-entry/acsee/subjects-by-school', [MarkEntryController::class, 'getSubjectsBySchoolAndYear']);
      
      // Exam Years API Endpoints
      Route::get('/api/exam-years', function () {
          try {
              $years = \App\Models\ExamYear::orderByDesc('year_label')->get()->map(function($year) {
                  return [
                      'id' => $year->id,
                      'year_label' => $year->year_label,
                      'is_active' => $year->is_active,
                      'is_locked' => $year->is_locked,
                  ];
              });

              $activeYear = \App\Models\ExamYear::where('is_active', true)->first();

              return response()->json([
                  'exam_years' => $years,
                  'active_year' => $activeYear ? [
                      'id' => $activeYear->id,
                      'year_label' => $activeYear->year_label,
                  ] : null,
              ]);
          } catch (\Exception $e) {
              \Log::error('Exam years API error:', ['error' => $e->getMessage()]);
              return response()->json([
                  'exam_years' => [],
                  'active_year' => null,
                  'error' => 'Unable to load exam years',
              ], 200);
          }
      });
      
      // Get active exam year (for auto-filling forms throughout system)
      Route::get('/api/exam-years/with-acsee', function () {
          try {
              $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
              
              if (!$acseeType) {
                  return response()->json(['years' => []]);
              }
              
              $years = \App\Models\ExamYear::query()
                  ->whereHas('candidateExamRegistrations', function($q) use ($acseeType) {
                      $q->where('exam_type_id', $acseeType->id);
                  })
                  ->orderBy('year_label', 'desc')
                  ->get();
              
              $data = $years->map(function($year) {
                  return [
                      'id' => $year->id,
                      'year_label' => $year->year_label,
                      'is_locked' => $year->is_locked
                  ];
              });
              
              return response()->json(['years' => $data]);
          } catch (\Exception $e) {
              \Log::error('Exam years with ACSEE error:', ['error' => $e->getMessage()]);
              return response()->json(['years' => [], 'error' => 'Unable to load exam years'], 500);
          }
      });

      Route::get('/api/exam-years/active', function () {
          try {
              $activeYear = \App\Models\ExamYear::active()->first();
              
              if (!$activeYear) {
                  return response()->json([
                      'active_year' => null,
                      'message' => 'No active exam year set'
                  ]);
              }
              
              return response()->json([
                  'active_year' => [
                      'id' => $activeYear->id,
                      'year_label' => $activeYear->year_label,
                      'is_locked' => $activeYear->is_locked
                  ]
              ]);
          } catch (\Exception $e) {
              \Log::error('Active exam year error:', ['error' => $e->getMessage()]);
              return response()->json([
                  'active_year' => null,
                  'error' => 'Unable to load active exam year'
              ], 500);
          }
      });

      // ==================== HIERARCHY GRID ROUTES ====================
          Route::middleware('auth')->group(function () {
          Route::get('/hierarchy/regions', [HierarchyController::class, 'regions'])->name('hierarchy.regions');
          Route::get('/hierarchy/districts/{regionId}', [HierarchyController::class, 'districts'])->name('hierarchy.districts');
          Route::get('/hierarchy/schools/{districtId}', [HierarchyController::class, 'schools'])->name('hierarchy.schools');
          Route::get('/hierarchy/school/{schoolId}/results', [HierarchyController::class, 'schoolResults'])->name('hierarchy.school-results');
      });

      // ==================== ACSEE RESULTS ROUTES ====================
      require base_path('routes/results.php');

      // ==================== MARK ENTRY LIFECYCLE ROUTES ====================
      require base_path('routes/mark-entry.php');
      });

// Temporary debug endpoint
Route::post('/api/test-upload', function (\Illuminate\Http\Request $request) {
    try {
        \Log::info('Test upload called', ['method' => $request->method()]);
        
        // Validate
        $validated = $request->validate([
            'zip_file' => 'required|file',
        ]);
        
        $file = $request->file('zip_file');
        \Log::info('File received', [
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ]);
        
        return response()->json(['success' => true, 'message' => 'File received', 'file' => $file->getClientOriginalName()]);
    } catch (\Exception $e) {
        \Log::error('Test upload failed: ' . $e->getMessage());
        return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
    }
});
