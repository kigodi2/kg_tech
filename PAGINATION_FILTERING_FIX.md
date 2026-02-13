# Pagination Filtering Bug Fix

## Problem

Candidates were not showing when selecting certain districts (KILOLO DC, MUFINDI DC, etc.) even though the data existed in the database.

### Root Cause

**Frontend was filtering locally against only 10 paginated records instead of all matching records.**

Example:
- KILOLO DC has 394 candidates total
- API loads only 10 candidates per page (first page)
- If those 10 candidates weren't from KILOLO DC, filter returned 0 results
- KILOLO DC candidates were on pages 2-40, never checked

## Solution

**Moved filtering from frontend to backend (API).**

### How It Works Now

1. **User selects district filter** (e.g., KILOLO DC)
2. **Frontend sends filter parameters to API:**
   ```
   /api/candidates?page=1&page_size=10&district_id=17
   ```
3. **Backend filters before pagination:**
   - Queries candidates WHERE school.district_id = 17
   - Counts total matching records (394)
   - Calculates total pages (40 pages × 10 items)
   - Returns page 1 of those 394 candidates
4. **Frontend displays correct results**

## Changes Made

### Frontend (candidates.blade.php)

**Before:**
```javascript
// Loaded 10 candidates, then filtered locally
let url = `/api/candidates?page=1&page_size=10`;
this.candidates = data.data; // Only 10 items

// Filtered against only those 10
this.filteredCandidates = this.candidates.filter(candidate => {
    if (districtId && candidate.school.district_id != districtId) {
        return false; // Might filter out all 10!
    }
});
```

**After:**
```javascript
// Send filter to API
let url = `/api/candidates?page=1&page_size=10&district_id=17`;
const response = await fetch(url);

// API returns only matching candidates
this.candidates = data.data; // All 10 from KILOLO DC
this.filteredCandidates = this.candidates; // No local filtering needed
```

### Backend (routes/api.php)

**Added filter support:**
```php
// New parameters
$districtId = $request->input('district_id', '');
$regionId = $request->input('region_id', '');

// Filter by district
if ($districtId) {
    $query->whereHas('school', function($q) use ($districtId) {
        $q->where('district_id', $districtId);
    });
}

// Filter by region
if ($regionId) {
    $query->whereHas('school.district', function($q) use ($regionId) {
        $q->where('region_id', $regionId);
    });
}
```

## Impact

### Before
- KILOLO DC: 394 candidates, but 0 displayed
- MUFINDI DC: Similar issue
- MAFINGA TC: Similar issue

### After
- KILOLO DC: 394 candidates, all pages (1-40) working correctly
- All districts now return correct paginated results
- Filtering works across all pagination pages

## API Endpoint

### GET /api/candidates

**Parameters:**
- `page` - Page number (default: 1)
- `page_size` - Items per page (default: 10)
- `search` - Search full name
- `school_id` - Filter by school (optional, new: backend filtering)
- `district_id` - Filter by district (NEW - backend filtering)
- `region_id` - Filter by region (NEW - backend filtering)

**Query Examples:**
```
/api/candidates
/api/candidates?page=1&page_size=25
/api/candidates?school_id=44
/api/candidates?district_id=17
/api/candidates?region_id=5
/api/candidates?district_id=17&page_size=50&page=2
```

## Testing

**Verify fix by selecting:**
1. Region: IRINGA
2. District: KILOLO DC
3. Should display: 394 candidates across 40 pages
4. Pages 1-40 should all have candidates
5. Pagination controls work correctly

**For other districts:**
- IRINGA DC: Should show candidates
- MUFINDI DC: Should show candidates
- MAFINGA TC: Should show candidates

## Performance

**Improvement:**
- Filtering now happens at database level (indexed query)
- Frontend no longer needs to filter
- Reduces unnecessary data processing
- Exact pagination works correctly

## Technical Details

### Relationship Chain
```
Candidate → School → District → Region
```

### Eloquent Queries Used
```php
// District filtering
$query->whereHas('school', function($q) use ($districtId) {
    $q->where('district_id', $districtId);
});

// Region filtering  
$query->whereHas('school.district', function($q) use ($regionId) {
    $q->where('region_id', $regionId);
});
```

These queries efficiently filter candidates through their school relationships.

## Files Modified

1. **resources/views/registration/candidates.blade.php**
   - Modified `loadCandidates()` method
   - Removed local filtering logic
   - Added filter parameters to API URL

2. **routes/api.php**
   - Updated `/api/candidates` endpoint
   - Added `district_id` and `region_id` parameters
   - Added `whereHas` filtering logic

---

**Status**: ✅ Complete  
**Date**: January 31, 2026  
**Impact**: Fixes candidate display for all districts
