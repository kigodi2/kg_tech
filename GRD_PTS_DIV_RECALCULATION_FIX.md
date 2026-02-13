# GRD, PTS, DIV Recalculation Fix

**Date**: February 9, 2026  
**Status**: ✓ FIXED

## Problem

The GRD (Grade), PTS (Points), and DIV (Division) columns were showing incorrect values because they were using pre-calculated database values that were computed **before** the averaging fix was applied.

### Example
```
Row: TOTAL=158.33, AVG=31.67
Old values: GRD=A, PTS=9, DIV=0, GPA=3.0000
Correct values: GRD should be recalculated, PTS should be recalculated, DIV should be recalculated
```

## Root Cause

When marks were first imported:
- marks_obtained was stored as the SUM of papers (e.g., 115 for chemistry with 3 papers)
- GRD, PTS, DIV were calculated from these SUM values

After our averaging fix:
- marks_obtained is now divided by number of papers (e.g., 115 ÷ 3 = 38.33)
- But GRD, PTS, DIV still use the OLD pre-calculated values

## Solution

Recalculate GRD, PTS, DIV on-the-fly from the averaged marks:

```php
// For each candidate, recalculate from averaged marks
$totalPoints = 0;
$validSubjectCount = 0;
$bestGrade = null;
$bestPoints = 8; // F=7, so 8 is worse

foreach ($candidateMarks as $mark) {
    if ($mark->marks_obtained !== null) {
        $subjectName = $mark->subject?->name ?? '';
        $gradePoints = get_grade_points($mark->grade_from_average);
        
        // Total points (all subjects)
        $totalPoints += $gradePoints;
        
        // Valid subject count (exclude GENERAL STUDIES, etc.)
        if (!is_excluded_subject($subjectName)) {
            $validSubjectCount++;
        }
        
        // Best grade (lowest points = best)
        if ($gradePoints < $bestPoints) {
            $bestPoints = $gradePoints;
            $bestGrade = $mark->grade_from_average;
        }
    }
}

// Recalculated values
$grd = $bestGrade;  // Best grade across all subjects
$totalPoints = $totalPoints;  // Sum of all grade points
$gpa = round($totalPoints / $validSubjectCount, 4);  // Points ÷ valid subjects
$division = get_division_info($totalPoints)['name'];  // Division from points
```

## How It Works

### GRD (Grade)
- Finds the **best grade** (lowest point value) across all subjects
- A=1 (best), B=2, C=3, D=4, E=5, S=6, F=7 (worst)
- Recalculated from `$mark->grade_from_average`

### PTS (Points)
- Sums the grade points from **only non-excluded subjects**
- Excludes: GENERAL STUDIES, BASIC APPLIED MATHEMATICS
- Each grade has a point value (A=1, F=7)
- Recalculated by summing `get_grade_points()` for non-excluded subjects only

### DIV (Division)
- Determined by total points
- Points 3-9 = Division I
- Points 10-12 = Division II
- Points 13-17 = Division III
- Points 18-19 = Division IV
- Points 20-21 = Division 0 (Fail)
- Recalculated using `get_division_info()`

### GPA (Grade Point Average)
- Total points ÷ number of valid subjects
- Valid subjects exclude GENERAL STUDIES and BASIC APPLIED MATHEMATICS
- Recalculated in the view

## Implementation

In `/resources/views/hierarchy/school-results.blade.php`:

1. **Before storing display values**, recalculate from averaged marks
2. **Use helper functions**: `get_grade_points()`, `is_excluded_subject()`, `get_division_info()`
3. **Use model accessors**: `$mark->grade_from_average` (already calculated from average)
4. **Display the recalculated values**, not the pre-stored database values

## Example Calculation

### Candidate with marks:
```
GENERAL STUDIES=42 'E'
CHEMISTRY=28.33 'F'
BIOLOGY=15 'F'
BASIC APPLIED MATHEMATICS=6 'F'
EDUCATION=67 'C'
```

### Recalculation:
```
Points:
- GENERAL STUDIES: E=5 (excluded from GPA)
- CHEMISTRY: F=7
- BIOLOGY: F=7
- BASIC APPLIED MATHEMATICS: F=7 (excluded from GPA)
- EDUCATION: C=3

Total Points = 5+7+7+7+3 = 29
Valid Subjects = 3 (CHEMISTRY, BIOLOGY, EDUCATION)
GPA = 29 / 3 = 9.67

Best Grade = C (lowest points = 3)
Division = Based on 29 points → likely Division 0 (fail)
```

## Files Modified

- `resources/views/hierarchy/school-results.blade.php`
  - Lines 197-243: Recalculation logic
  - Lines 276-302: Display updated values

## Status

✓ GRD recalculated from best grade
✓ PTS recalculated from grade points  
✓ DIV recalculated from total points
✓ GPA recalculated excluding specified subjects
✓ Uses helper functions and model accessors
✓ Pre-stored database values no longer used
