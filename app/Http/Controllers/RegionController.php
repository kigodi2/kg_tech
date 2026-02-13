<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\DistrictCouncil;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RegionController extends Controller
{
    // PAGE VIEWS
    public function index()
    {
        return view('regions.dashboard');
    }

    public function show(Region $region)
    {
        if (request()->expectsJson()) {
            return response()->json($region);
        }
        $region->load('schools', 'councils');
        return view('regions.show', compact('region'));
    }

    public function create()
    {
        return view('regions.create');
    }

    public function edit(Region $region)
    {
        return view('regions.edit', compact('region'));
    }

    // API ENDPOINTS - REGIONS
    public function apiGetRegions(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = min($request->get('page_size', 25), 100);
            $search = $request->get('search', '');

            $query = Region::query();

            if ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            $totalCount = $query->count();
            $regions = $query->orderBy('code')
                ->paginate($pageSize, ['*'], 'page', $page);

            $data = $regions->map(function ($region) {
                $districtsCount = \App\Models\District::where('region_id', $region->id)->count();
                $schoolsCount = School::where('region_id', $region->id)->count();
                $candidatesCount = \App\Models\Candidate::whereIn('school_id', 
                    School::where('region_id', $region->id)->pluck('id')
                )->count();

                return [
                    'id' => $region->id,
                    'code' => $region->code,
                    'name' => $region->name,
                    'status' => $region->is_active ? 'active' : 'inactive',
                    'districts_count' => $districtsCount,
                    'schools_count' => $schoolsCount,
                    'candidates_count' => $candidatesCount,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $regions->currentPage(),
                    'page_size' => $pageSize,
                    'total_count' => $totalCount,
                    'total_pages' => $regions->lastPage(),
                    'has_next' => $regions->hasMorePages(),
                    'has_previous' => $regions->currentPage() > 1,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function apiAddRegion(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:regions,code',
                'name' => 'required|string|max:255',
            ]);

            $region = Region::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Region added successfully',
                'data' => $region,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function apiUpdateRegion(Request $request, $regionId)
    {
        try {
            $region = Region::findOrFail($regionId);

            $validated = $request->validate([
                'code' => 'required|unique:regions,code,' . $regionId,
                'name' => 'required|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $region->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Region updated successfully',
                'data' => $region,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function apiDeleteRegion(Request $request, $regionId)
    {
        try {
            $region = Region::findOrFail($regionId);

            // Check for dependent records
            $districtsCount = \App\Models\District::where('region_id', $regionId)->count();
            $schoolsCount = School::where('region_id', $regionId)->count();

            if ($districtsCount > 0 || $schoolsCount > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete region with associated records',
                    'details' => [
                        'districts' => $districtsCount,
                        'schools' => $schoolsCount,
                    ],
                ], 400);
            }

            $region->delete();

            return response()->json([
                'success' => true,
                'message' => 'Region deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    // EXPORT FUNCTIONS
    
    // Professional PDF Export using DomPDF
    public function apiExportRegionsPdf()
    {
        try {
            $regions = Region::orderBy('code')->get()->map(function ($region) {
                $candidatesCount = \App\Models\Candidate::whereIn('school_id', 
                    School::where('region_id', $region->id)->pluck('id')
                )->count();
                
                return [
                    'code' => $region->code,
                    'name' => $region->name,
                    'districts_count' => \App\Models\District::where('region_id', $region->id)->count(),
                    'schools_count' => School::where('region_id', $region->id)->count(),
                    'candidates_count' => $candidatesCount,
                    'status' => $region->is_active ? 'Active' : 'Inactive',
                ];
            });

            $pdf = Pdf::loadView('exports.regions-pdf', [
                'regions' => $regions,
                'generatedAt' => now(),
                'totalRecords' => $regions->count(),
            ]);

            return $pdf->download('regions_' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
    
    public function apiExportRegionsExcel()
    {
        try {
            $regions = Region::orderBy('code')->get();
            
            // Create CSV content that Excel can open
            $csv = "Code,Region Name,Districts,Schools,Candidates,Status,Created Date\n";
            
            foreach ($regions as $region) {
                $districtsCount = \App\Models\District::where('region_id', $region->id)->count();
                $schoolsCount = School::where('region_id', $region->id)->count();
                $candidatesCount = \App\Models\Candidate::whereIn('school_id', 
                    School::where('region_id', $region->id)->pluck('id')
                )->count();
                $status = $region->is_active ? 'Active' : 'Inactive';
                $createdDate = $region->created_at ? $region->created_at->format('Y-m-d') : '-';
                
                // Escape CSV values
                $code = $this->escapeCsvValue($region->code);
                $name = $this->escapeCsvValue($region->name);
                
                $csv .= "{$code},{$name},{$districtsCount},{$schoolsCount},{$candidatesCount},{$status},{$createdDate}\n";
            }
            
            // Return as Excel-compatible file
            return response(
                "\xEF\xBB\xBF" . $csv,  // UTF-8 BOM for proper encoding
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="regions_' . date('Y-m-d') . '.xlsx"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]
            );
            
        } catch (\Exception $e) {
            \Log::error('Excel export error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'error' => 'Error exporting to Excel'
            ], 400);
        }
    }
    
    /**
     * Escape CSV values properly
     */
    private function escapeCsvValue($value)
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    // IMPORT FUNCTIONS
    public function apiImportRegions(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:csv']);
            
            $file = $request->file('file');
            $imported = 0;
            $failed = 0;
            $errors = [];

            if ($file->getClientOriginalExtension() === 'csv') {
                $content = file_get_contents($file->getRealPath());
                $lines = explode("\n", $content);
                
                // Skip header row
                foreach ($lines as $index => $line) {
                    if ($index === 0) continue; // Skip header
                    
                    $line = trim($line);
                    if (empty($line)) continue; // Skip empty lines
                    
                    try {
                        $row = str_getcsv($line);
                        
                        if (count($row) < 1) continue;
                        
                        $name = trim($row[0] ?? '');
                        
                        if (empty($name)) continue;
                        
                        // Generate region code: First 2-3 letters + 2-digit sequential number
                        // Try 2 letters first, if conflict use 3 letters
                        $twoLetterPrefix = strtoupper(substr($name, 0, 2));
                        $threeLetterPrefix = strtoupper(substr($name, 0, 3));
                        
                        // Check if 2-letter prefix already exists (to avoid conflicts)
                        $twoLetterExists = \App\Models\Region::where('code', 'like', $twoLetterPrefix . '%')->exists();
                        $useThreeLetters = $twoLetterExists; // Use 3 letters if 2-letter prefix already exists
                        
                        $prefix = $useThreeLetters ? $threeLetterPrefix : $twoLetterPrefix;
                        
                        // The number is based on total regions in system
                        $totalRegions = \App\Models\Region::count();
                        $nextNumber = $totalRegions + 1;
                        
                        $code = $prefix . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
                        
                        // Check if region already exists by name
                        $existing = Region::where('name', $name)->first();
                        
                        if ($existing) {
                            // Update existing region
                            $existing->update([
                                'code' => $code,
                                'is_active' => true,
                            ]);
                        } else {
                            // Create new region
                            Region::create([
                                'code' => $code,
                                'name' => $name,
                                'is_active' => true,
                            ]);
                        }
                        
                        $imported++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Row error: " . $e->getMessage();
                    }
                }
            }

            $message = "Imported $imported region(s)";
            if ($failed > 0) {
                $message .= ", $failed failed";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    // STORE & UPDATE for form submissions
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:regions,code',
                'name' => 'required|string|max:255',
            ]);

            Region::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'is_active' => true,
            ]);

            return redirect('/regions')->with('success', 'Region created successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, Region $region)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|unique:regions,code,' . $region->id,
                'name' => 'required|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $region->update($validated);

            return redirect('/regions')->with('success', 'Region updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Region $region)
    {
        try {
            $region->delete();
            return redirect('/regions')->with('success', 'Region deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
