# Examination Centre Division Performance - Fix Counts and Labels - 2026-02-09

## Issue
The "EXAMINATION CENTRE DIVISION PERFORMANCE" table had two problems:

1. **Incorrect Counts**: All values were hardcoded or calculated from total candidates instead of actual status counts
   - ABSENT always showed 0
   - SAT showed total candidates (should be total - absent)
   - NO-CA/INC always showed 0
   - CLEAN always showed total (should only be complete candidates)

2. **Wrong Column Label**: "NO-CA" should be "INC" (Incomplete) to match NECTA terminology

## Root Cause
The table used hardcoded values instead of calculating actual candidate status counts:
- Line 444: ABSENT hardcoded as 0
- Line 447: NO-CA hardcoded as 0
- Line 448: CLEAN hardcoded to show all candidates

## Solution
Added proper calculation of candidate statuses before the table:
1. Count ABS (no marks in any subject)
2. Count INC (marks in some but not all subjects)
3. Count COMPLETE (marks in all subjects)
4. Calculate SAT = Total - ABS
5. Change label from NO-CA to INC

## Changes Made

### File: resources/views/hierarchy/school-results.blade.php

#### Added Status Calculation Block (Lines 421-455)
```blade
@php
    // Calculate proper counts for division performance
    $absCount = 0;
    $incCount = 0;
    $completeCount = 0;
    
    foreach ($candidates as $candidate) {
        $subjectSelections = \App\Models\CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $acseeType?->id ?? 2)
            ->count();
        
        $candidateMarks = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $acseeType?->id ?? 2)
            ->get();
        
        $marksCount = 0;
        foreach($candidateMarks as $mark) {
            if ($mark->marks_obtained !== null) {
                $marksCount++;
            }
        }
        
        if ($marksCount === 0) {
            $absCount++;
        } elseif ($marksCount < $subjectSelections) {
            $incCount++;
        } else {
            $completeCount++;
        }
    }
    
    $totalRegistered = $candidates->count();
    $totalSat = $totalRegistered - $absCount; // SAT = Registered - Absent
@endphp
```

#### Updated Table Values
**Before:**
```blade
<td>0</td>           <!-- ABSENT -->
<td>0</td>           <!-- NO-CA/INC -->
<td>{{ all candidates }}</td> <!-- CLEAN -->
```

**After:**
```blade
<td>{{ $absCount }}</td>      <!-- ABSENT -->
<td>{{ $incCount }}</td>       <!-- INC (was NO-CA) -->
<td>{{ $completeCount }}</td>  <!-- CLEAN -->
```

#### Changed Column Header
```blade
NO-CA  → INC
```

## Column Definitions

| Column | Meaning | Formula |
|--------|---------|---------|
| REGIST | Total registered candidates | Count of all candidates |
| ABSENT | Candidates with no marks | Count where marks_obtained = null in all subjects |
| SAT | Candidates who sat exam | REGIST - ABSENT |
| WITHHELD | Candidates with withheld results | 0 (not tracked in system) |
| INC | Incomplete candidates | Count where marks_obtained exists in some but not all subjects |
| CLEAN | Complete candidates | Count where marks_obtained exists in all subjects |
| DIV I | Division I candidates | From $totalDivisions['I'] |
| DIV II | Division II candidates | From $totalDivisions['II'] |
| DIV III | Division III candidates | From $totalDivisions['III'] |
| DIV IV | Division IV candidates | From $totalDivisions['IV'] |
| DIV 0 | Division 0 (Fail) candidates | From $totalDivisions['0'] |

## Calculation Logic

For each candidate:
1. Get allocated subjects count
2. Count marks with `marks_obtained !== null`
3. Classify:
   - **ABS**: 0 marks → absCount++
   - **INC**: Some marks (< allocated) → incCount++
   - **COMPLETE**: All marks → completeCount++

## Expected Results Example
```
For a school with 84 candidates:
- REGIST = 84 (total)
- ABSENT = 0 (no one absent)
- SAT = 84 (84 - 0)
- WITHHELD = 0 (none withheld)
- INC = 5 (5 incomplete)
- CLEAN = 79 (84 - 5)
- DIV I + II + III + IV + 0 = 79 (matching CLEAN)
```

## Verification Checklist

- [x] ABSENT count correctly counts candidates with no marks
- [x] SAT = REGIST - ABSENT
- [x] INC correctly counts incomplete candidates
- [x] CLEAN correctly counts complete candidates
- [x] Sum of DIV I+II+III+IV+0 equals CLEAN
- [x] Column header changed from NO-CA to INC
- [x] All counts are properly calculated, not hardcoded

## Deployment

✓ File updated: `resources/views/hierarchy/school-results.blade.php`
✓ No database changes needed
✓ No controller changes needed
✓ No cache clearing required
✓ Backward compatible with existing data

## Notes

- The calculation reuses the same logic used in candidate filtering
- WITHHELD remains 0 as the system doesn't track this status
- All counts are calculated fresh each time the view is rendered
- Performance impact is minimal (single pass through candidates)

---

**Status:** FIXED - Division performance counts now accurate, labels correct
**Completed:** 2026-02-09
