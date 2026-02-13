# Data Integrity Protection - Cascading Dependency Checks

## Overview

The system now prevents deletion of regions, districts, schools, or candidates if there are dependent items registered under them. This protects data integrity.

---

## Protection Rules

### 1. **Cannot Delete REGION if:**
- ✗ It has districts registered under it
- ✗ It has schools linked to it

**Error Response:**
```json
{
    "success": false,
    "error": "Cannot delete region with associated records",
    "details": {
        "districts": 5,
        "schools": 15
    },
    "status": 409
}
```

### 2. **Cannot Delete DISTRICT if:**
- ✗ It has schools registered under it

**Error Response:**
```json
{
    "message": "Cannot delete district with registered schools",
    "details": "This district has 3 school(s) registered. Please remove all schools first.",
    "count": 3,
    "status": 409
}
```

### 3. **Cannot Delete SCHOOL if:**
- ✗ It has candidates registered under it

**Error Response:**
```json
{
    "message": "Cannot delete school with registered candidates",
    "details": "This school has 363 candidate(s) registered. Please remove all candidates first.",
    "count": 363,
    "status": 409
}
```

### 4. **Cannot Delete CANDIDATE if:**
- ✗ It has subject marks entered
- ✗ It has results/final grades entered

**Error Response:**
```json
{
    "message": "Cannot delete candidate with marks or results",
    "details": "This candidate has 6 subject mark(s) and 1 result(s) entered. Please remove marks/results first.",
    "subject_marks_count": 6,
    "results_count": 1,
    "status": 409
}
```

---

## Implementation Details

### Region Deletion (RegionController.php)
```php
public function apiDeleteRegion(Request $request, $regionId) {
    $region = Region::findOrFail($regionId);
    
    // Check for dependent records
    $districtsCount = District::where('region_id', $regionId)->count();
    $schoolsCount = School::where('region_id', $regionId)->count();
    
    if ($districtsCount > 0 || $schoolsCount > 0) {
        return response()->json([
            'success' => false,
            'error' => 'Cannot delete region with associated records',
            'details' => [
                'districts' => $districtsCount,
                'schools' => $schoolsCount,
            ]
        ], 409);
    }
    
    $region->delete();
    return response()->json(['success' => true]);
}
```

### District Deletion (routes/web.php)
```php
Route::delete('/api/districts/{id}', function ($id) {
    $district = District::find($id);
    
    // Check if district has schools
    $schoolCount = $district->schools()->count();
    if ($schoolCount > 0) {
        return response()->json([
            'message' => "Cannot delete district with registered schools",
            'details' => "This district has $schoolCount school(s) registered.",
            'count' => $schoolCount
        ], 409);
    }
    
    $district->delete();
    return response()->json(['message' => 'District deleted']);
});
```

### School Deletion (routes/web.php)
```php
Route::delete('/api/schools/{id}', function ($id) {
    $school = School::find($id);
    
    // Check if school has candidates
    $candidateCount = $school->candidates()->count();
    if ($candidateCount > 0) {
        return response()->json([
            'message' => "Cannot delete school with registered candidates",
            'details' => "This school has $candidateCount candidate(s) registered.",
            'count' => $candidateCount
        ], 409);
    }
    
    $school->delete();
    return response()->json(['message' => 'School deleted']);
});
```

---

## How to Delete Protected Items

### To Delete a Region:
1. **First**: Delete or reassign all schools in that region
2. **Then**: Delete or reassign all districts in that region
3. **Finally**: Delete the region

### To Delete a District:
1. **First**: Delete or reassign all schools in that district
2. **Finally**: Delete the district

### To Delete a School:
1. **First**: Delete or reassign all candidates in that school
2. **Finally**: Delete the school

### To Delete a Candidate:
1. **First**: Remove all subject marks for that candidate
2. **Then**: Remove all results/final grades for that candidate
3. **Finally**: Delete the candidate

---

## User Interface Updates

When user tries to delete a protected item:

### Desktop UI
```
Error Dialog:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cannot delete region with associated records

Details:
  • Districts: 5
  • Schools: 15

Action: Please remove dependent records first
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### What the system tells you:
- ✅ Why deletion failed
- ✅ How many dependent items exist
- ✅ What to do next

---

## Database Relationships

```
Region
  ├── Districts (many)
  │   └── Schools (many)
  │       └── Candidates (many)
  └── Schools (many) [direct link]
      └── Candidates (many)
```

Protection flow:
```
Delete Region → Check Districts (fail if > 0)
            ├→ Check Schools (fail if > 0)
            
Delete District → Check Schools (fail if > 0)

Delete School → Check Candidates (fail if > 0)

Delete Candidate → No restrictions
```

---

## Error Codes

| Code | Meaning | Action |
|------|---------|--------|
| **404** | Item not found | Item doesn't exist |
| **409** | Conflict | Item has dependent records |
| **200** | Success | Item deleted |

---

## Bulk Delete Operations

Bulk delete endpoints also check for dependencies:

```php
Route::post('/api/schools/bulk-delete', function ($request) {
    foreach ($request->ids as $id) {
        $school = School::find($id);
        if ($school->candidates()->count() > 0) {
            return error("Cannot delete school with candidates");
        }
    }
});
```

---

## Testing the Protection

### Test 1: Try to delete region with schools
```bash
curl -X DELETE /api/regions/1
# Response: 409 Conflict - Has associated records
```

### Test 2: Try to delete district with schools
```bash
curl -X DELETE /api/districts/1
# Response: 409 Conflict - Has schools
```

### Test 3: Try to delete school with candidates
```bash
curl -X DELETE /api/schools/28
# Response: 409 Conflict - Has 363 candidates
```

### Test 4: Delete candidate (allowed)
```bash
curl -X DELETE /api/candidates/1
# Response: 200 OK - Candidate deleted
```

---

## Status

✅ **Region Protection**: Implemented in RegionController
✅ **District Protection**: Implemented in routes/web.php
✅ **School Protection**: Implemented in routes/web.php
✅ **Candidate Protection**: Implemented in routes/web.php (checks for marks/results)
✅ **Cascade Checks**: All dependencies verified before deletion
✅ **Error Messages**: Clear, informative feedback to users

All deletions are now safe and protected against data loss!

---

## What Marks/Results Are Protected

### Subject Marks (SubjectMarks Table)
- Theory marks
- Practical marks
- Total marks calculated
- Grade assigned
- Locked status

### Candidate Results (CandidateResult Table)
- Overall grade
- Total marks
- Grade points
- Division
- Verification status
- Publication status
- Lock status

Candidates with ANY of these cannot be deleted until marks/results are removed first.

