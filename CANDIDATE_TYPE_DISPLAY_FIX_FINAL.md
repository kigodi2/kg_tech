# Candidate Type Display Fix - Final

## Problem
Even after updating the database with correct `candidate_type` values, the UI still showed all P-prefix candidates as SCHOOL instead of PRIVATE.

## Root Cause
The API endpoint `/api/candidates` (line 589-668 in routes/web.php) was not including `candidate_type` in its response payload. The frontend component expected it but received `null`, which defaulted to 'SCHOOL' in the display logic.

## Solution
Added `candidate_type` to the API response mapping.

### File Modified: `routes/web.php`

**Location:** Line 644-657 (in the GET /api/candidates endpoint)

**Change:**
```php
// BEFORE
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
        'status' => $c->status ?? 'registered'
    ];
});

// AFTER
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
        'candidate_type' => $c->candidate_type ?? 'SCHOOL',  // ← ADDED
        'status' => $c->status ?? 'registered'
    ];
});
```

## To Apply Fix

1. **Hard refresh browser** (Ctrl+F5 or Cmd+Shift+R on Mac)
2. Navigate to `/registration/candidates`
3. Candidates with P-prefix index numbers should now show as **PRIVATE** type

## Verification

After the fix, candidates with P-prefix index numbers like `P0652-0502` will display:
- **TYPE column:** PRIVATE (with purple badge)
- Instead of: SCHOOL (with blue badge)

## Complete Fix Timeline

1. ✅ Fixed code to auto-detect candidate_type from index prefix
   - DistrictCandidateImportController
   - CandidateImportService batch method

2. ✅ Fixed existing database data (16 P-prefix candidates)
   - Created `candidates:fix-type` Artisan command
   - Updated candidates with wrong type

3. ✅ Fixed API response to include candidate_type
   - Added `candidate_type` field to `/api/candidates` endpoint
   - Frontend component now receives the correct data

## Result
Candidates are now properly classified:
- **S-prefix** → SCHOOL (blue badge)
- **P-prefix** → PRIVATE (purple badge)
