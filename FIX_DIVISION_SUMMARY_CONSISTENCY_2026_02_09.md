# Division Summary Consistency Fix - Single Calculation Point - 2026-02-09

## Issue
The DIVISION PERFORMANCE SUMMARY (Section 1) was showing different counts than what appeared in the detailed results (Section 2) under the DIV column.

### Root Cause
There were TWO separate calculations:
1. Early calculation (lines 36-121) - for Section 1 Division Performance Summary
2. Later calculation (lines 239+) - for Section 2 detailed table

These two calculations were using slightly different logic, resulting in mismatched counts.

## Solution
Moved the `$candidatesWithMetrics` calculation to BEFORE Section 1, then reuse the EXACT SAME calculation for both sections. This ensures perfect consistency.

## Changes Made

### File: resources/views/hierarchy/school-results.blade.php

#### Step 1: Calculate Metrics Early (Lines 36-145)
Moved `$candidatesWithMetrics` calculation to the very top, before Section 1 table:

```blade
@php
    // Build candidatesWithMetrics ONCE - use for both summary and detailed sections
    $candidatesWithMetrics = $candidates->map(function($cand) use ($acseeType) {
        // Calculate: marks, totalPoints, status, division, avg
        // Return array with candidate, status, division, avg, totalPoints
    })->sortBy(...)->values();
    
    // Count divisions by sex FROM the same metrics
    foreach($candidatesWithMetrics as $data) {
        // Count by gender and division
    }
@endphp
```

#### Step 2: Remove Duplicate Calculation (Lines 238-370)
Removed the second calculation in Section 2 that was recalculating the same values:

**Removed:**
```blade
// OLD: Recalculated metrics again
$candidatesWithMetrics = $candidates->map(function($cand) { ... })->sortBy(...)->values();

// Then recalculated divisions again
$divisionStatsBySex = [ ... ];
$absIncStatsBySex = [ ... ];
```

**Replaced with:**
```blade
// NEW: Just use the pre-calculated metrics
// candidatesWithMetrics already calculated earlier (before Section 1)
// No need to recalculate - just use it
```

## Single Calculation Point

Now the flow is:
```
1. EARLY CALCULATION (lines 36-145)
   ↓
   Calculate $candidatesWithMetrics (sorted, with metrics)
   Count divisions by sex for Section 1 table
   ↓
2. SECTION 1: DIVISION PERFORMANCE SUMMARY
   Displays: $divisionStatsBySex, $totalDivisions (from early calc)
   ↓
3. SECTION 2: DETAILED RESULTS TABLE
   Loops through: $candidatesWithMetrics (from early calc)
   Displays: DIV column values (same data as Section 1 counts)
   ↓
4. LATER SECTIONS
   Use: $totalDivisions (from early calc)
```

## Perfect Consistency

Since both Section 1 and Section 2 use the EXACT SAME `$candidatesWithMetrics`:
- Count of DIV I in Section 1 = Count of DIV I candidates in Section 2
- Count of DIV II in Section 1 = Count of DIV II candidates in Section 2
- etc.

## Calculation Details

For each candidate (in `$candidatesWithMetrics`):
1. Load marks with `grade_from_average`
2. Calculate total points (exclude GS and BAM)
3. Determine status (ABS, INC, COMPLETE)
4. Calculate average mark
5. Determine division from points
6. Return single data structure

Then sorted by: Status → Division → AVG (descending)

## Example Verification

If Section 2 shows:
```
S1378-0523 F DIV 0
S1378-0550 M DIV II
S1378-0567 M DIV II
... (14 more M DIV II records)
S1378-0521 F DIV II
```

Then Section 1 should show:
```
F: DIV I=0, DIV II=1, DIV III=?, DIV IV=?, DIV 0=1, ...
M: DIV I=0, DIV II=14, DIV III=?, DIV IV=?, DIV 0=?, ...
T: DIV I=0, DIV II=15, DIV III=?, DIV IV=?, DIV 0=?, ...
```

(Counts match the DIV column in Section 2)

## Verification Checklist

- [x] Single calculation point for `$candidatesWithMetrics`
- [x] Duplicate calculation removed
- [x] Section 1 uses pre-calculated metrics
- [x] Section 2 uses same pre-calculated metrics
- [x] Division counts in Section 1 match DIV column in Section 2
- [x] All sections use consistent division values

## Performance Benefit

- Single pass through candidates (not two)
- Fewer database queries (metrics calculated once)
- Consistent memory usage
- Faster rendering

## Deployment

✓ File updated: `resources/views/hierarchy/school-results.blade.php`
✓ No database changes needed
✓ No controller changes needed
✓ No cache clearing required
✓ Backward compatible with existing data

---

**Status:** FIXED - Division summary now matches detailed results exactly
**Completed:** 2026-02-09
