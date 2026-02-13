# Dashboard ACSEE Candidates - Quick Start Implementation

## 5-Step Implementation Plan

### Step 1: Create Routes (5 minutes)

**File**: `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/exam/ACSEE', [DashboardController::class, 'acseeExam'])
        ->name('dashboard.exam.acsee');
});
```

**File**: `routes/api.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/candidates/acsee', [DashboardController::class, 'getAcseeCandicates']);
    Route::get('/dashboard/candidates/filter-data', [DashboardController::class, 'getAcseeFilterData']);
});
```

---

### Step 2: Create Controller Methods (15 minutes)

**File**: `app/Http/Controllers/DashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Combination;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show ACSEE exam dashboard
     */
    public function acseeExam()
    {
        return view('dashboard.exam-acsee');
    }

    /**
     * Get ACSEE candidates with filters
     */
    public function getAcseeCandicates(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 15);
        $search = $request->get('search', '');
        $schoolId = $request->get('school_id');
        $districtId = $request->get('district_id');
        $regionId = $request->get('region_id');

        $query = Candidate::where('exam_type', 'ACSEE')
            ->with('school.district.region')
            ->orderBy('candidate_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_id', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($districtId) {
            $query->whereHas('school', function ($q) use ($districtId) {
                $q->where('district_id', $districtId);
            });
        }

        if ($regionId) {
            $query->whereHas('school.district', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        $candidates = $query->paginate($pageSize);

        $data = $candidates->map(function ($candidate) {
            return [
                'id' => $candidate->id,
                'candidate_id' => $candidate->candidate_id,
                'full_name' => $candidate->full_name,
                'gender' => $candidate->gender,
                'combination' => $candidate->combination,
                'school_name' => $candidate->school?->name ?? '-',
                'school_id' => $candidate->school_id,
                'district_id' => $candidate->school?->district_id,
                'district_name' => $candidate->school?->district?->name ?? '-',
                'region_id' => $candidate->school?->district?->region_id,
                'region_name' => $candidate->school?->district?->region?->name ?? '-',
                'allocated_subjects' => $this->getCombinationSubjects($candidate->combination),
                'exam_type' => $candidate->exam_type,
            ];
        });

        return response()->json([
            'candidates' => $data,
            'pagination' => [
                'page' => $candidates->currentPage(),
                'page_size' => $pageSize,
                'total_count' => $candidates->total(),
                'total_pages' => $candidates->lastPage(),
                'has_previous' => $candidates->currentPage() > 1,
                'has_next' => $candidates->hasMorePages(),
            ]
        ]);
    }

    /**
     * Get filter options for ACSEE candidates
     */
    public function getAcseeFilterData()
    {
        $regions = Region::whereHas('districts.schools.candidates', function ($q) {
            $q->where('exam_type', 'ACSEE');
        })->orderBy('name')->get(['id', 'name']);

        $districts = District::whereHas('schools.candidates', function ($q) {
            $q->where('exam_type', 'ACSEE');
        })->orderBy('name')->get(['id', 'name', 'region_id']);

        $schools = School::whereHas('candidates', function ($q) {
            $q->where('exam_type', 'ACSEE');
        })->with('district')
         ->orderBy('name')
         ->get(['id', 'name', 'district_id']);

        return response()->json([
            'regions' => $regions,
            'districts' => $districts,
            'schools' => $schools,
        ]);
    }

    /**
     * Get subjects for a combination
     */
    private function getCombinationSubjects($combinationCode)
    {
        if (!$combinationCode) {
            return [];
        }

        $combination = Combination::where('code', $combinationCode)
            ->with('subjects')
            ->first();

        if (!$combination) {
            return [];
        }

        return $combination->subjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
            ];
        })->toArray();
    }
}
```

---

### Step 3: Create Blade View (20 minutes)

**File**: `resources/views/dashboard/exam-acsee.blade.php`

```blade
@extends('layout')

@section('content')
<div class="w-full px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">ACSEE Dashboard</h1>
        <p class="text-gray-600">View registered ACSEE candidates</p>
    </div>

    <!-- Main Component -->
    <div x-data="dashboardAcseeManager()" @init="init()" class="space-y-6">
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Region</label>
                    <select x-model="selectedRegion" @change="onRegionChange()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Regions</option>
                        <template x-for="region in regions" :key="region.id">
                            <option :value="region.id" x-text="region.name"></option>
                        </template>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">District</label>
                    <select x-model="selectedDistrict" @change="onDistrictChange()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Districts</option>
                        <template x-for="district in filteredDistricts" :key="district.id">
                            <option :value="district.id" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">School</label>
                    <select x-model="selectedSchool" @change="onSchoolChange()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Schools</option>
                        <template x-for="school in filteredSchools" :key="school.id">
                            <option :value="school.id" x-text="school.name"></option>
                        </template>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button @click="resetFilters()" class="w-full px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium">
                        Reset
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Search & Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex gap-4">
                <input x-model="searchText" @input="onSearch()" type="text" placeholder="Search by Index Number or Name..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                <button @click="exportToExcel()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <div x-show="loading" class="p-6 text-center text-gray-500">
                <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
            </div>
            
            <table x-show="!loading" class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Index</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Full Name</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Sex</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Combination</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Subjects</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">School</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Region</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="candidate in filteredCandidates" :key="candidate.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-mono" x-text="candidate.candidate_id"></td>
                            <td class="px-6 py-4 text-sm" x-text="candidate.full_name"></td>
                            <td class="px-6 py-4 text-sm text-center" x-text="candidate.gender === 'M' ? '♂' : '♀'"></td>
                            <td class="px-6 py-4 text-sm" x-text="candidate.combination || '-'"></td>
                            <td class="px-6 py-4 text-sm" x-text="candidate.allocated_subjects.map(s => s.code).join(', ') || '-'"></td>
                            <td class="px-6 py-4 text-sm" x-text="candidate.school_name"></td>
                            <td class="px-6 py-4 text-sm" x-text="candidate.region_name"></td>
                        </tr>
                    </template>
                    <tr x-show="filteredCandidates.length === 0 && !loading">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No candidates found</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 bg-white rounded-lg shadow">
            <div class="text-sm text-gray-600">
                Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
            </div>
            <div class="flex gap-2">
                <button @click="currentPage > 1 && (currentPage--, loadCandidates())" :disabled="currentPage <= 1" class="px-3 py-1 border rounded disabled:opacity-50">Previous</button>
                <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)" :key="page">
                    <button @click="currentPage = page; loadCandidates()" :class="currentPage === page ? 'bg-blue-600 text-white' : 'border'" class="px-3 py-1 rounded" x-text="page"></button>
                </template>
                <button @click="currentPage < totalPages && (currentPage++, loadCandidates())" :disabled="currentPage >= totalPages" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
            </div>
        </div>
        
    </div>
</div>

<script>
function dashboardAcseeManager() {
    return {
        // State
        candidates: [],
        filteredCandidates: [],
        regions: [],
        districts: [],
        schools: [],
        
        // Filters
        searchText: '',
        selectedRegion: '',
        selectedDistrict: '',
        selectedSchool: '',
        
        // Pagination
        currentPage: 1,
        pageSize: 15,
        totalPages: 1,
        loading: false,
        
        // Computed
        get filteredDistricts() {
            return this.selectedRegion 
                ? this.districts.filter(d => d.region_id == this.selectedRegion)
                : this.districts;
        },
        
        get filteredSchools() {
            return this.selectedDistrict
                ? this.schools.filter(s => s.district_id == this.selectedDistrict)
                : this.schools;
        },
        
        // Initialization
        async init() {
            await this.loadFilterData();
            await this.loadCandidates();
        },
        
        // Load filter options
        async loadFilterData() {
            try {
                const response = await fetch('/api/dashboard/candidates/filter-data');
                const data = await response.json();
                this.regions = data.regions;
                this.districts = data.districts;
                this.schools = data.schools;
            } catch (error) {
                console.error('Error loading filter data:', error);
            }
        },
        
        // Load candidates
        async loadCandidates() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    page_size: this.pageSize,
                    search: this.searchText,
                    region_id: this.selectedRegion,
                    district_id: this.selectedDistrict,
                    school_id: this.selectedSchool,
                });
                
                const response = await fetch(`/api/dashboard/candidates/acsee?${params}`);
                const data = await response.json();
                
                this.filteredCandidates = data.candidates;
                this.totalPages = data.pagination.total_pages;
                this.currentPage = data.pagination.page;
            } catch (error) {
                console.error('Error loading candidates:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Filter handlers
        onRegionChange() {
            this.selectedDistrict = '';
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        onDistrictChange() {
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        onSchoolChange() {
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        onSearch() {
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        resetFilters() {
            this.searchText = '';
            this.selectedRegion = '';
            this.selectedDistrict = '';
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // Export
        exportToExcel() {
            const headers = ['Index', 'Name', 'Sex', 'Combination', 'Subjects', 'School', 'Region'];
            const rows = this.filteredCandidates.map(c => [
                c.candidate_id || '',
                c.full_name || '',
                c.gender || '',
                c.combination || '',
                c.allocated_subjects.map(s => s.code).join(', ') || '',
                c.school_name || '',
                c.region_name || '',
            ]);
            
            const csv = [headers, ...rows].map(row => 
                row.map(v => `"${v}"`).join(',')
            ).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `acsee_candidates_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    };
}
</script>
@endsection
```

---

### Step 4: Test the Implementation (10 minutes)

1. **Navigate to the route**: `http://localhost:8000/dashboard/exam/ACSEE`
2. **Verify data loads**: Check if candidates are displayed
3. **Test filters**: Try Region → District → School filters
4. **Test search**: Search by index number or name
5. **Test export**: Download CSV file

---

### Step 5: Add Navigation Link (5 minutes)

**File**: `resources/views/layout.blade.php` (or your main navigation)

```blade
<li><a href="{{ route('dashboard.exam.acsee') }}">ACSEE Dashboard</a></li>
```

---

## Total Implementation Time: ~55 minutes

## Important Notes

1. **Candidate Data Source**: All data is READ-ONLY from the `registration/candidates` page
2. **Combination Subjects**: Automatically retrieved from the `exam-types/acsee` combinations
3. **No Registration in Dashboard**: Users register/edit candidates in `/registration/candidates`, not in dashboard
4. **Hierarchical Filtering**: Filters work in cascade: Region → Districts → Schools
5. **Performance**: Uses eager loading to avoid N+1 queries

## Troubleshooting

| Issue | Solution |
|-------|----------|
| No candidates showing | Check that candidates exist with `exam_type = 'ACSEE'` |
| Filters not working | Verify database relationships are correct |
| Combination subjects blank | Check `combinations` table has subjects linked |
| API errors | Check routes are properly registered in `routes/api.php` |

---

## See Also

- Full implementation guide: `DASHBOARD_CANDIDATES_ACSEE_IMPLEMENTATION_ADVICE.md`
- ACSEE management: `/exam-types/acsee`
- Candidates registration: `/registration/candidates`
