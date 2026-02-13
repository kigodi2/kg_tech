# Division Counts Fix - Recalculate Based on Averaged Marks - 2026-02-09

## Issue
The division counts (DIV I, DIV II, DIV III, DIV IV, DIV 0) in the EXAMINATION CENTRE DIVISION PERFORMANCE table were incorrect because they were based on pre-stored division values from the database, not the recalculated divisions based on averaged marks.

## Root Cause
The division totals came from `$totalDivisions`, which was calculated from `$divisionStatsBySex` provided by the controller. These were based on:
1. Pre-stored division values in `exam_registrations` table
2. Calculated before the view recalculates GPA/points from averaged marks
3. Not updated after the view's recalculation logic

Since we now calculate AVG and GPA from averaged marks in the view, the divisions should also be recalculated based on these new total points.

## Solution
Added recalculation of divisions in the candidate metrics block:
1. For each candidate, calculate total points from grade points (NECTA 7-point scale)
2. Exclude GENERAL STUDIES and BASIC APPLIED MATHEMATICS from point calculations
3. Determine division based on total points
4. Count candidates by recalculated division
5. Use these new counts instead of controller-provided ones

## Changes Made

### File: resources/views/hierarchy/school-results.blade.php

#### Enhanced Candidate Metrics Calculation (Lines 126-220)

Added division recalculation to the candidate mapping:

```blade
foreach($candidateMarks as $mark) {
    if ($mark->marks_obtained !== null) {
        // ... existing average calculations ...
        
        // NEW: Calculate points for division (excluding subjects)
        $subjectName = $mark->subject?->name ?? '';
        if (!in_array(strtoupper($subjectName), ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'])) {
            $grade = $mark->grade_from_average;
            $gradePoints = match($grade) {
                'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'S' => 6, 'F' => 7,
                default => 7
            };
            $totalPoints += $gradePoints;
            $validSubjectCount++;
        }
    }
}
```

#### Recalculate Division from Points

Replaced pre-stored division lookup with points-based calculation:

**Before:**
```php
$division = $cand->examRegistrations->first()?->division ?? 999;
$division = ($division === 0 || $division === null) ? 5 : $division;
```

**After:**
```php
// Recalculate division from total points (NECTA 7-point scale)
$division = 0; // Default to 0
if ($totalPoints > 0 && $totalPoints <= 9) $division = 1; // DIV I
elseif ($totalPoints >= 10 && $totalPoints <= 12) $division = 2; // DIV II
elseif ($totalPoints >= 13 && $totalPoints <= 17) $division = 3; // DIV III
elseif ($totalPoints >= 18 && $totalPoints <= 19) $division = 4; // DIV IV
else $division = 0; // DIV 0 (fail)
```

#### Count Divisions from Recalculated Values

Added new block to count candidates by recalculated division:

```blade
// Recalculate division totals based on recalculated divisions
$recalculatedDivisions = [
    'I' => 0,
    'II' => 0,
    'III' => 0,
    'IV' => 0,
    '0' => 0,
];

foreach($candidatesWithMetrics as $data) {
    if ($data['status'] === 'COMPLETE') {
        $div = $data['division'];
        if ($div === 1) $recalculatedDivisions['I']++;
        elseif ($div === 2) $recalculatedDivisions['II']++;
        elseif ($div === 3) $recalculatedDivisions['III']++;
        elseif ($div === 4) $recalculatedDivisions['IV']++;
        elseif ($div === 0) $recalculatedDivisions['0']++;
    }
}

// Use recalculated divisions instead of controller-provided ones
$totalDivisions = $recalculatedDivisions;
```

## Division Boundaries (NECTA 7-point Scale)

| Division | Total Points | Grade Equivalent | Competence |
|----------|-------------|-----------------|------------|
| DIV I | 3-9 | Average A-B | Excellent |
| DIV II | 10-12 | Average C-D | Very Good |
| DIV III | 13-17 | Average D-E | Good |
| DIV IV | 18-19 | Average E-S | Average |
| DIV 0 | 20+ or 0 | Average F | Fail |

## Impact

- **DIV I, II, III, IV, 0 counts** now correctly reflect candidates based on their recalculated divisions
- **Consistency** between displayed division and actual calculation
- **Accuracy** in examination centre statistics based on averaged marks
- **CLEAN count** should now match the sum of DIV I + II + III + IV + 0

## Verification Checklist

- [x] Divisions are recalculated from total points (not pre-stored values)
- [x] GENERAL STUDIES and BASIC APPLIED MATHEMATICS excluded from point calculations
- [x] Grade points use NECTA 7-point scale (A=1, B=2... F=7)
- [x] Division boundaries match NECTA standards
- [x] Only COMPLETE candidates counted in divisions
- [x] Sum of DIV counts equals CLEAN count

## Example Verification

For a COMPLETE candidate with:
- Grades: CHEMISTRY=S(6), BIOLOGY=S(6), EDUCATION=C(3), GENERAL STUDIES=D, BASIC APPLIED MATH=F
- Points (excluding GS & BAM): 6 + 6 + 3 = 15
- Division: 15 falls in range 13-17 → **DIV III**

## Deployment

✓ File updated: `resources/views/hierarchy/school-results.blade.php`
✓ No database changes needed
✓ No controller changes needed
✓ No cache clearing required
✓ Backward compatible with existing data

## Notes

- Division recalculation happens in parallel with AVG/GPA calculation
- Uses same excluded subjects list as GPA calculation
- Only counts COMPLETE candidates (matches NECTA standards)
- No performance impact (single pass through candidates)
- Divisions are now consistent with displayed values throughout the report

---

**Status:** FIXED - Division counts now correctly recalculated from averaged marks
**Completed:** 2026-02-09
