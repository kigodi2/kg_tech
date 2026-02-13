# Subject GPA Display Fix - Include All Subjects - 2026-02-09

## Issue
The EXAMINATION CENTRE SUBJECTS PERFORMANCE table was showing GPA = 0.0000 for GENERAL STUDIES and BASIC APPLIED MATHEMATICS (excluded subjects), even though these subjects had valid grades and candidates.

### Example Data from Screenshot
- **GENERAL STUDIES**: All 67 grades present (B=10, C=17, D=15, E=18, S=4, F=3), but GPA showed 0.0000
- **BASIC APPLIED MATHEMATICS**: All 67 grades present (all F's), but GPA showed 0.0000

## Root Cause
The previous fix excluded GENERAL STUDIES and BASIC APPLIED MATHEMATICS from the subject performance GPA calculation entirely. This meant:
- When `validMarkCount = 0` for excluded subjects
- The calculation resulted in `avgGpa = 0 / 0 = 0`

However, the subject performance table should display GPA for **all subjects**, regardless of exclusion. The exclusion from GPA calculations should only apply to the **school-level overall GPA**, not individual subject performance display.

## Solution
Separated the GPA calculations:
1. **Subject Performance Table**: Calculate GPA for **all subjects** (no exclusions)
2. **School Overall GPA**: Exclude GENERAL STUDIES and BASIC APPLIED MATHEMATICS

## Changes Made

### File: app/Http/Controllers/HierarchyController.php

#### Updated GPA Calculation in subjectsPerformance (Lines 198-213)

**Before:**
```php
// Calculate GPA - excluding subjects from calculation
foreach ($marks as $mark) {
    if ($mark->marks_obtained !== null) {
        $grade = $mark->grade_from_average;
        $gradePoints = $this->gradeToPoints($grade);
        
        // Check if subject should be excluded from GPA
        if (!$this->isExcludedSubject($subject->name)) {
            $gradePointsSum += $gradePoints;
            $validMarkCount++;
        }
    }
}

$avgGpa = $validMarkCount > 0 ? ($gradePointsSum / $validMarkCount) : 0;
```

**After:**
```php
// Calculate GPA from grade points (NECTA 7-point scale)
// Note: Subject performance table shows GPA for ALL subjects
// Exclusion from school-level GPA happens in school overall GPA calculation
foreach ($marks as $mark) {
    if ($mark->marks_obtained !== null) {
        $grade = $mark->grade_from_average;
        $gradePoints = $this->gradeToPoints($grade);
        $gradePointsSum += $gradePoints;
        $validMarkCount++;
    }
}

$avgGpa = $validMarkCount > 0 ? ($gradePointsSum / $validMarkCount) : 0;
```

Change:
- Removed the exclusion check from subject-level GPA calculation
- All subjects now show their actual GPA based on candidate grades
- Exclusion logic remains for school overall GPA (not shown in subject table)

## GPA Calculation Example

### GENERAL STUDIES (Subject Code 111)
Grades distribution: B=10, C=17, D=15, E=18, S=4, F=3

**Calculation:**
```
Points: (10×2) + (17×3) + (15×4) + (18×5) + (4×6) + (3×7)
      = 20 + 51 + 60 + 90 + 24 + 21
      = 266

GPA = 266 / 67 = 3.97 (rounds to 4)
Competency: Grade D (Average)
```

### BASIC APPLIED MATHEMATICS (Subject Code 141)
Grades: All F (67 candidates)

**Calculation:**
```
Points: 67 × 7 = 469
GPA = 469 / 67 = 7.0
Competency: Grade F (Fail)
```

## Corrected Display Values

| Subject | Before GPA | After GPA | Competency | Notes |
|---------|-----------|-----------|-----------|-------|
| GENERAL STUDIES | 0.0000 | ~3.97 | Grade D | Now shows actual GPA |
| CHEMISTRY | 6.4923 | 6.4923 | Grade S | Unchanged (included) |
| BIOLOGY | 6.4627 | 6.4627 | Grade S | Unchanged (included) |
| BASIC APPLIED MATHEMATICS | 0.0000 | 7.0000 | Grade F | Now shows actual GPA |
| EDUCATION | 2.8209 | 2.8209 | Grade C | Unchanged (included) |

## Clarification on Exclusion

### When Subjects are EXCLUDED:
- From **school-level overall GPA calculation** (in the overview section)
- From **candidate-level GPA calculations** (in individual results)

### When Subjects are INCLUDED:
- In **subject performance GPA display** (this table)
- In **school/examination centre statistics**
- In **subject grade distribution tables**

This distinction allows:
- Full visibility of all subject performance
- Accurate exclusion of non-core subjects from student GPAs
- Complete reporting of examination centre statistics

## Impact

- Subject performance table now shows accurate GPA for all subjects
- Competency levels correctly mapped to grade point averages
- Table provides complete picture of examination centre performance
- GENERAL STUDIES and BASIC APPLIED MATHEMATICS can be easily identified as excluded subjects

## Verification

Expected values in subject performance table:
- GENERAL STUDIES: GPA should be ~3.97 (Grade D)
- BASIC APPLIED MATHEMATICS: GPA should be 7.0000 (Grade F)
- Other subjects: Show actual calculated GPA values

## Deployment

✓ File updated: `app/Http/Controllers/HierarchyController.php`
✓ No database changes needed
✓ No view changes needed
✓ No cache clearing required
✓ Backward compatible

---

**Status:** FIXED - Subject GPA display now includes all subjects
**Completed:** 2026-02-09
