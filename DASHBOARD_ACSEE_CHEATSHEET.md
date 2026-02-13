# Dashboard ACSEE Implementation - Cheatsheet

## TL;DR - What to Do

1. **Copy controller code** → `app/Http/Controllers/DashboardController.php`
2. **Copy view code** → `resources/views/dashboard/exam-acsee.blade.php`
3. **Add routes** → `routes/web.php` + `routes/api.php`
4. **Test** → Navigate to `/dashboard/exam/ACSEE`
5. **Done!** ✅

**Time: 60 minutes**

---

## File Checklist

```
✅ routes/web.php
  ADD: Route::get('/dashboard/exam/ACSEE', [DashboardController::class, 'acseeExam'])

✅ routes/api.php
  ADD: Route::get('/dashboard/candidates/acsee', [DashboardController::class, 'getAcseeCandicates'])
  ADD: Route::get('/dashboard/candidates/filter-data', [DashboardController::class, 'getAcseeFilterData'])

✅ app/Http/Controllers/DashboardController.php
  CREATE or UPDATE with 3 methods:
  - acseeExam()
  - getAcseeCandicates()
  - getAcseeFilterData()
  - getCombinationSubjects() [private]

✅ resources/views/dashboard/exam-acsee.blade.php
  CREATE with:
  - Filter section
  - Search section
  - Table section
  - Pagination
  - Alpine.js component
```

---

## Key Code Snippets

### Route (routes/web.php)
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/exam/ACSEE', [DashboardController::class, 'acseeExam'])
        ->name('dashboard.exam.acsee');
});
```

### API Route (routes/api.php)
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/candidates/acsee', [DashboardController::class, 'getAcseeCandicates']);
    Route::get('/dashboard/candidates/filter-data', [DashboardController::class, 'getAcseeFilterData']);
});
```

### Controller Method
```php
public function getAcseeCandicates(Request $request)
{
    $page = $request->get('page', 1);
    $pageSize = $request->get('page_size', 15);
    $search = $request->get('search', '');
    
    $query = Candidate::where('exam_type', 'ACSEE')
        ->with('school.district.region')
        ->orderBy('candidate_id');
    
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('candidate_id', 'like', "%{$search}%")
              ->orWhere('full_name', 'like', "%{$search}%");
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
            'region_name' => $candidate->school?->district?->region?->name ?? '-',
            'allocated_subjects' => $this->getCombinationSubjects($candidate->combination),
        ];
    });
    
    return response()->json([
        'candidates' => $data,
        'pagination' => [
            'page' => $candidates->currentPage(),
            'page_size' => $pageSize,
            'total_count' => $candidates->total(),
            'total_pages' => $candidates->lastPage(),
        ]
    ]);
}
```

### Alpine.js Core
```javascript
function dashboardAcseeManager() {
    return {
        candidates: [],
        regions: [],
        districts: [],
        schools: [],
        
        async init() {
            await this.loadFilterData();
            await this.loadCandidates();
        },
        
        async loadCandidates() {
            const params = new URLSearchParams({
                page: this.currentPage,
                search: this.searchText,
                region_id: this.selectedRegion,
                district_id: this.selectedDistrict,
                school_id: this.selectedSchool,
            });
            
            const response = await fetch(`/api/dashboard/candidates/acsee?${params}`);
            const data = await response.json();
            
            this.candidates = data.candidates;
            this.totalPages = data.pagination.total_pages;
        },
        
        async loadFilterData() {
            const response = await fetch('/api/dashboard/candidates/filter-data');
            const data = await response.json();
            this.regions = data.regions;
            this.districts = data.districts;
            this.schools = data.schools;
        }
    };
}
```

---

## Data Flow

```
User clicks filter
    ↓
Alpine.js detects change
    ↓
Calls loadCandidates()
    ↓
Sends GET /api/dashboard/candidates/acsee?filters
    ↓
DashboardController.getAcseeCandicates()
    ↓
Query Candidate table (with filters)
    ↓
Get combination subjects
    ↓
Return JSON response
    ↓
Alpine.js updates candidates array
    ↓
Blade template re-renders table
    ↓
User sees filtered results
```

---

## Database Queries

### Get Candidates
```php
Candidate::where('exam_type', 'ACSEE')
    ->with('school.district.region')
    ->where('candidate_id', 'like', "%{$search}%")
    ->orWhere('full_name', 'like', "%{$search}%")
    ->paginate(15);
```

### Get Combination Subjects
```php
Combination::where('code', $combinationCode)
    ->with('subjects')
    ->first()
    ->subjects;
```

### Get Filter Options
```php
Region::whereHas('districts.schools.candidates', 
    fn($q) => $q->where('exam_type', 'ACSEE'))
    ->get();

District::whereHas('schools.candidates', 
    fn($q) => $q->where('exam_type', 'ACSEE'))
    ->get();

School::whereHas('candidates', 
    fn($q) => $q->where('exam_type', 'ACSEE'))
    ->get();
```

---

## HTML Table Structure

```html
<table>
    <thead>
        <tr>
            <th>Index Number</th>
            <th>Full Name</th>
            <th>Sex</th>
            <th>Combination</th>
            <th>Subjects</th>
            <th>School</th>
            <th>Region</th>
        </tr>
    </thead>
    <tbody>
        <tr x-for="candidate in candidates">
            <td x-text="candidate.candidate_id"></td>
            <td x-text="candidate.full_name"></td>
            <td x-text="candidate.gender === 'M' ? '♂' : '♀'"></td>
            <td x-text="candidate.combination"></td>
            <td x-text="candidate.allocated_subjects.map(s => s.code).join(', ')"></td>
            <td x-text="candidate.school_name"></td>
            <td x-text="candidate.region_name"></td>
        </tr>
    </tbody>
</table>
```

---

## Alpine.js Props Needed

```javascript
{
    // State
    candidates: [],           // array of candidate objects
    regions: [],             // array of regions
    districts: [],           // array of districts
    schools: [],            // array of schools
    
    // Filters
    searchText: '',         // search input value
    selectedRegion: '',     // selected region ID
    selectedDistrict: '',   // selected district ID
    selectedSchool: '',     // selected school ID
    
    // Pagination
    currentPage: 1,         // current page number
    pageSize: 15,          // records per page
    totalPages: 1,         // total number of pages
    
    // UI
    loading: false,        // loading indicator
}
```

---

## Common Tasks

### Add a Column
```blade
<!-- In table thead -->
<th>New Column</th>

<!-- In table tbody -->
<td x-text="candidate.new_field"></td>
```

### Change Page Size
```javascript
pageSize: 20,  // change from 15 to 20
```

### Add Export Button
```blade
<button @click="exportToExcel()" class="px-4 py-2 bg-green-600 text-white rounded">
    Export to Excel
</button>
```

```javascript
exportToExcel() {
    const csv = [
        ['Index', 'Name', 'Combination', 'Subjects'],
        ...this.candidates.map(c => [
            c.candidate_id,
            c.full_name,
            c.combination,
            c.allocated_subjects.map(s => s.code).join(',')
        ])
    ].map(row => row.map(v => `"${v}"`).join(',')).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'candidates.csv';
    a.click();
}
```

### Add Detail View
```blade
<button @click="viewCandidate(candidate)" class="text-blue-600 hover:underline">
    View Details
</button>
```

```javascript
viewCandidate(candidate) {
    // Open modal or navigate to detail page
    window.location.href = `/candidates/${candidate.id}`;
}
```

---

## Testing Checklist

```
□ Page loads at /dashboard/exam/ACSEE
□ Candidates table displays
□ No JavaScript errors in console
□ Region filter populates
□ District filter updates when region selected
□ School filter updates when district selected
□ Search by index number works
□ Search by full name works
□ Reset button clears all filters
□ Pagination works (next/previous)
□ Allocated subjects display for each candidate
□ Export to CSV works
□ No N+1 database queries (check Laravel Debugbar)
```

---

## Debug Tips

### Check Database Data
```php
// In tinker or route
Candidate::where('exam_type', 'ACSEE')->count();
Candidate::where('exam_type', 'ACSEE')->first();
```

### Check API Response
```
Visit: http://localhost:8000/api/dashboard/candidates/acsee
Should see JSON response with candidates array
```

### Check Alpine.js State
```javascript
// In browser console
$data // or getElement('div-with-x-data').__x.$data
```

### Check Network Requests
```
Open DevTools → Network tab
Trigger filter
Look for /api/dashboard/candidates/acsee request
Check response JSON
```

---

## Performance Tips

### Optimize Queries
```php
// BAD: N+1 problem
foreach ($candidates as $candidate) {
    $subjects = $candidate->combination->subjects; // Query in loop!
}

// GOOD: Eager load
Candidate::with('school.district.region')->get();
```

### Cache Filter Options
```php
$regions = Cache::remember('acsee-regions', 3600, function() {
    return Region::whereHas('districts.schools.candidates', 
        fn($q) => $q->where('exam_type', 'ACSEE'))->get();
});
```

### Limit Export Size
```php
// Export only current page
if ($request->get('export_all')) {
    // All records
} else {
    // Current page only
}
```

---

## Troubleshooting Quick Fixes

| Issue | Quick Fix |
|-------|-----------|
| Route not found | Run `php artisan route:list` and verify route exists |
| API returns 404 | Check routes/api.php middleware group |
| No candidates show | Check exam_type is exactly 'ACSEE' in database |
| Filters broken | Check JavaScript console for errors |
| Subjects blank | Verify combinations have subjects linked |
| Slow loading | Add .with() eager loading to query |
| Export doesn't work | Check browser console for JS errors |

---

## File Sizes

- Controller: ~400 lines
- View: ~300 lines
- Routes: ~10 lines
- JavaScript: ~150 lines

**Total: ~860 lines of code**

---

## Dependencies

- Laravel 9+ (or your version)
- Alpine.js (already installed)
- Tailwind CSS (for styling - already in project)
- No additional packages needed!

---

## Estimated Timeline

```
Review docs:        10 min
Write routes:        5 min
Write controller:    20 min
Write view:         20 min
Test:               15 min
Deploy:              5 min
───────────────────────────
TOTAL:              75 min (~1.5 hours)
```

---

## Success Indicators

After implementation:

✅ Can access `/dashboard/exam/ACSEE`  
✅ See all ACSEE candidates in table  
✅ Filter by region/district/school  
✅ Search works  
✅ Pagination works  
✅ Export works  
✅ Allocated subjects display  
✅ No database errors  
✅ API response time < 500ms  

---

## Next Steps

1. **Open DASHBOARD_ACSEE_QUICK_START.md** - Step-by-step guide
2. **Copy code snippets** - Paste into your files
3. **Test thoroughly** - Use checklist above
4. **Deploy** - Follow your standard process
5. **Get feedback** - Ask users what else they need

---

## Quick Links

- **Main Implementation**: DASHBOARD_ACSEE_QUICK_START.md
- **Full Details**: DASHBOARD_CANDIDATES_ACSEE_IMPLEMENTATION_ADVICE.md
- **Architectural Decision**: IMPLEMENTATION_RECOMMENDATION.md
- **Comparison with Backup**: BACKUP_COMPARISON_KEY_DIFFERENCES.md
- **Full Index**: DASHBOARD_ACSEE_INDEX.md

---

**Ready?** Open DASHBOARD_ACSEE_QUICK_START.md and follow steps 1-4. You'll be done in 60 minutes! 🚀
