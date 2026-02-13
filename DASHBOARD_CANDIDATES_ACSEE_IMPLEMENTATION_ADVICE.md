# Dashboard ACSEE Candidates Implementation Guide

## Overview
This guide provides advice on implementing the ACSEE Candidates page in the dashboard, following the backup IRMS architecture but adapted for your current Laravel system. The key difference from the backup is that you **retrieve candidates data** from the `registration/candidates` page rather than creating/editing them directly in the dashboard.

---

## Architecture Analysis

### Backup IRMS (Django) Pattern
**Location**: `/dashboard/exam/ACSEE/`

**Data Flow**:
```
Dashboard → Exam Summary View → Candidates API → Candidate Model
                ↓
          Retrieve from registration/candidates
          (Index Number, Full Name, Sex, Combination)
                ↓
          Enrich with Allocated Subjects
          (from exam-types/acsee combinations)
                ↓
          Display in read-only table
```

**Key Features**:
- Hierarchical filtering: Region → District → School
- Search by candidate number or name
- Display allocated subjects from combination
- Export (PDF, Excel) capabilities
- Pagination support
- Read-only view (no editing in dashboard)

### Current Laravel IRMS Structure
**Locations**:
- `/registration/candidates` - Where candidates are registered/managed
- `/exam-types/acsee` - Where combinations and subjects are managed
- `/dashboard` - Where overview pages are displayed

**Current Implementation**:
- Alpine.js for frontend interactivity
- API routes for data retrieval
- Blade templates for views
- CandidateController for CRUD operations

---

## Implementation Strategy

### 1. **Create Dashboard ACSEE Candidates Page**

**Route** (add to `routes/web.php`):
```php
Route::get('/dashboard/exam/ACSEE', [DashboardController::class, 'acseeExam'])->name('dashboard.exam.acsee');
```

**Create Controller Method** (`app/Http/Controllers/DashboardController.php`):
```php
public function acseeExam()
{
    return view('dashboard.exam-acsee');
}
```

**Create View** (`resources/views/dashboard/exam-acsee.blade.php`):
- Similar structure to `/exam-types/acsee.blade.php` but focused on candidates
- Use tabbed interface: Subjects | Combinations | Candidates
- Read-only display with filtering capabilities

---

### 2. **Data Retrieval Strategy**

#### A. **Retrieve Candidates from Registration**
**API Endpoint** (add to `routes/api.php`):
```php
Route::get('/dashboard/candidates/acsee', [DashboardController::class, 'getAcseeCandicates']);
```

**Controller Logic**:
```php
public function getAcseeCandicates(Request $request)
{
    $page = $request->get('page', 1);
    $pageSize = $request->get('page_size', 15);
    $search = $request->get('search', '');
    $schoolId = $request->get('school_id');
    $districtId = $request->get('district_id');
    $regionId = $request->get('region_id');

    // Base query: Get candidates where exam_type = 'ACSEE'
    $query = Candidate::where('exam_type', 'ACSEE')
        ->with('school.district.region')
        ->orderBy('candidate_id');

    // Apply filters
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

    // Paginate
    $candidates = $query->paginate($pageSize);

    // Transform data to include combination subjects
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
 * Get subjects allocated to a combination
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
```

#### B. **Get Filter Data (Regions, Districts, Schools)**
```php
public function getAcseeFilterData()
{
    // Get all regions where there are ACSEE candidates
    $regions = Region::whereHas('districts.schools.candidates', function ($q) {
        $q->where('exam_type', 'ACSEE');
    })->get(['id', 'name']);

    // Get all districts with ACSEE candidates
    $districts = District::whereHas('schools.candidates', function ($q) {
        $q->where('exam_type', 'ACSEE');
    })->get(['id', 'name', 'region_id']);

    // Get all schools with ACSEE candidates
    $schools = School::whereHas('candidates', function ($q) {
        $q->where('exam_type', 'ACSEE');
    })->with('district')->get(['id', 'name', 'district_id']);

    return response()->json([
        'regions' => $regions,
        'districts' => $districts,
        'schools' => $schools,
    ]);
}
```

---

### 3. **Frontend Implementation (Alpine.js)**

**Key JavaScript Functions** (`resources/views/dashboard/exam-acsee.blade.php`):

```javascript
function dashboardAcseeManager() {
    return {
        activeTab: 'candidates',
        
        // Candidates data
        candidates: [],
        filteredCandidates: [],
        loading: false,
        
        // Filter state
        searchText: '',
        selectedRegion: '',
        selectedDistrict: '',
        selectedSchool: '',
        
        // Filter options
        regions: [],
        districts: [],
        schools: [],
        
        // Pagination
        currentPage: 1,
        pageSize: 15,
        totalPages: 1,
        totalCount: 0,
        
        async init() {
            await this.loadFilterData();
            await this.loadCandidates();
        },
        
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
                
                this.candidates = data.candidates;
                this.filteredCandidates = data.candidates;
                this.totalPages = data.pagination.total_pages;
                this.totalCount = data.pagination.total_count;
                this.currentPage = data.pagination.page;
            } catch (error) {
                console.error('Error loading candidates:', error);
                this.showMessage('Error loading candidates', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        // Region selection triggers district filter update
        onRegionChange() {
            this.selectedDistrict = '';
            this.selectedSchool = '';
            this.currentPage = 1;
            this.loadCandidates();
        },
        
        // District selection triggers school filter update
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
        
        // Export functions
        exportToExcel() {
            const headers = ['Index Number', 'Full Name', 'Sex', 'Combination', 'School', 'Region', 'District', 'Allocated Subjects'];
            const rows = this.filteredCandidates.map(c => [
                c.candidate_id || '',
                c.full_name || '',
                c.gender || '',
                c.combination || '',
                c.school_name || '',
                c.region_name || '',
                c.district_name || '',
                (c.allocated_subjects || []).map(s => s.code).join(', ') || '-'
            ]);
            
            // Create CSV
            const csv = [headers, ...rows].map(row => 
                row.map(v => `"${v}"`).join(',')
            ).join('\n');
            
            // Download
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `acsee_candidates_${new Date().getTime()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },
        
        exportToPdf() {
            // Use html2pdf library for PDF export
            const element = document.querySelector('.candidates-table');
            const opt = {
                margin: 10,
                filename: `acsee_candidates_${new Date().getTime()}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
            };
            html2pdf().set(opt).from(element).save();
        },
        
        showMessage(message, type) {
            // Show toast notification
            const div = document.createElement('div');
            div.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            div.textContent = message;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }
    };
}
```

---

### 4. **Template Structure**

**Create** `resources/views/dashboard/exam-acsee.blade.php`:

```blade
@extends('layout')

@section('content')
<div class="w-full px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">ACSEE Dashboard</h1>
        <p class="text-gray-600">View ACSEE registered candidates</p>
    </div>

    <!-- Main Component -->
    <div x-data="dashboardAcseeManager()" @init="init()" class="space-y-6">
        
        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-4 gap-4">
                <!-- Region Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Region</label>
                    <select 
                        x-model="selectedRegion"
                        @change="onRegionChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                    >
                        <option value="">All Regions</option>
                        <template x-for="region in regions" :key="region.id">
                            <option :value="region.id" x-text="region.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- District Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">District</label>
                    <select 
                        x-model="selectedDistrict"
                        @change="onDistrictChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                    >
                        <option value="">All Districts</option>
                        <template x-for="district in districts.filter(d => !selectedRegion || d.region_id == selectedRegion)" :key="district.id">
                            <option :value="district.id" x-text="district.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- School Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">School</label>
                    <select 
                        x-model="selectedSchool"
                        @change="onSchoolChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                    >
                        <option value="">All Schools</option>
                        <template x-for="school in schools.filter(s => !selectedDistrict || s.district_id == selectedDistrict)" :key="school.id">
                            <option :value="school.id" x-text="school.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- Reset Button -->
                <div class="flex items-end">
                    <button 
                        @click="resetFilters()"
                        class="w-full px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Search and Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex gap-4">
                <input 
                    x-model="searchText"
                    @input="onSearch()"
                    type="text" 
                    placeholder="Search by Index Number or Full Name..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg"
                >
                <button 
                    @click="exportToExcel()"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium flex items-center gap-2"
                >
                    <i class="fas fa-download"></i> Export Excel
                </button>
                <button 
                    @click="exportToPdf()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium flex items-center gap-2"
                >
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>
        
        <!-- Candidates Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <div x-show="loading" class="p-6 text-center text-gray-500">
                <i class="fas fa-spinner animate-spin text-2xl"></i> Loading...
            </div>
            
            <table x-show="!loading" class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Index Number</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Full Name</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Sex</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Combination</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Allocated Subjects</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">School</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Region</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="candidate in filteredCandidates" :key="candidate.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-mono text-gray-800" x-text="candidate.candidate_id"></td>
                            <td class="px-6 py-4 text-sm text-gray-800" x-text="candidate.full_name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600 text-center" x-text="candidate.gender === 'M' ? 'Male' : 'Female'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.combination || '-'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span x-text="candidate.allocated_subjects.map(s => s.code).join(', ') || '-'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.school_name"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="candidate.region_name"></td>
                        </tr>
                    </template>
                    <tr x-show="filteredCandidates.length === 0 && !loading">
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No candidates found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-white rounded-lg shadow">
            <div class="text-sm text-gray-600">
                Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>, showing <span x-text="filteredCandidates.length"></span> of <span x-text="totalCount"></span> records
            </div>
            <div class="flex gap-2">
                <button 
                    @click="currentPage > 1 && (currentPage--, loadCandidates())"
                    :disabled="currentPage <= 1"
                    class="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                >
                    Previous
                </button>
                <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)" :key="page">
                    <button 
                        @click="currentPage = page; loadCandidates()"
                        :class="currentPage === page ? 'bg-blue-600 text-white' : 'border border-gray-300'"
                        class="px-3 py-1 rounded"
                        x-text="page"
                    ></button>
                </template>
                <button 
                    @click="currentPage < totalPages && (currentPage++, loadCandidates())"
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border border-gray-300 rounded disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- Include html2pdf for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
// Alpine.js component definition
window.dashboardAcseeManager = dashboardAcseeManager;
</script>
@endsection
```

---

### 5. **Database Query Optimization**

Add these optimizations to `app/Models/Candidate.php`:

```php
public function scopeForAcsee($query)
{
    return $query->where('exam_type', 'ACSEE')
        ->with(['school.district.region']);
}

public function getCombinationSubjectsAttribute()
{
    return Combination::where('code', $this->combination)
        ->with('subjects')
        ->first()
        ?->subjects ?? collect();
}
```

---

## Key Differences from Backup IRMS

| Aspect | Backup (Django) | Current (Laravel) |
|--------|----------------|-------------------|
| **Framework** | Django | Laravel |
| **Frontend** | jQuery/Custom JS | Alpine.js |
| **Template Engine** | Django Templates | Blade |
| **Data Source** | Candidate model (read/write) | Candidate model (read-only in dashboard) |
| **Combination Subjects** | Stored in database | Queried dynamically |
| **Export Format** | PDF/Excel via backend | Client-side CSV + html2pdf |
| **Filtering** | Via query parameters | Via Alpine.js state |

---

## Implementation Checklist

- [ ] Create `DashboardController` method `acseeExam()`
- [ ] Create API endpoints:
  - [ ] `/api/dashboard/candidates/acsee` (GET)
  - [ ] `/api/dashboard/candidates/filter-data` (GET)
- [ ] Create view `resources/views/dashboard/exam-acsee.blade.php`
- [ ] Implement Alpine.js component
- [ ] Add route to `routes/web.php`
- [ ] Test data retrieval from candidates table
- [ ] Test combination subject enrichment
- [ ] Test filtering (Region → District → School)
- [ ] Test search functionality
- [ ] Test pagination
- [ ] Test export functionality
- [ ] Add navigation link to dashboard

---

## Additional Considerations

1. **Performance**: Use eager loading for relationships to avoid N+1 queries
2. **Caching**: Consider caching combination-subject mappings
3. **Security**: Ensure user roles can only view their assigned regions/schools
4. **Pagination**: Balance between page size and performance
5. **Export Limits**: Consider implementing limits on export size to prevent timeouts

---

## References

- Current ACSEE implementation: `resources/views/exam-types/acsee.blade.php`
- Candidates registration: `resources/views/registration/candidates.blade.php`
- CandidateController: `app/Http/Controllers/CandidateController.php`
- Backup exam summary: `/IRMS BACKUP/templates/dashboard/exam_summary.html`
