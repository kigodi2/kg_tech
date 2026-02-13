# Grade Calculation Fix Report

## Issues Found and Fixed

### 1. **Wrong Year Column Reference** ❌ FIXED
**Problem:** The GradeCalculationService was querying `subject_marks` using `exam_year_id` column, but the table uses `year` column (the actual academic year like 2025, 2026).

**Fix:** 
- Updated to fetch the ExamYear record first
- Extract the `year` value and use it in SubjectMarks query
- Applied to both `GradeCalculationService` and `ProcessBulkImportFile`

```php
// BEFORE (Wrong)
$marks = SubjectMarks::where('exam_year_id', $examYearId)

// AFTER (Correct)
$examYear = ExamYear::find($examYearId);
$marks = SubjectMarks::where('year', $examYear->year)
```

### 2. **GPA Decimal Precision** ❌ FIXED
**Problem:** GradeCalculationService was rounding GPA to 4 decimal places, but NectaGradingService uses 2 decimal places. This inconsistency could cause display issues.

**Fix:**
- Changed GPA rounding from 4 to 2 decimal places
- Matches the NectaGradingService standard: `round($totalPoints / $validSubjectCount, 2)`

## How to Verify Calculations

### Manual Verification Script

```php
// In controller or artisan command
use App\Services\Results\GradeCalculationService;
use App\Models\ExamYear;

$gradeCalc = app(GradeCalculationService::class);

// Single candidate verification
$result = $gradeCalc->calculateForCandidate(
    candidateId: 123,
    examYearId: 1,  // ID of exam year
    examTypeId: 1   // ID of exam type
);

// Check logs
tail storage/logs/laravel.log
```

### Database Query to Verify

```sql
-- Check candidate marks and grades
SELECT 
    sm.candidate_id,
    sm.subject_id,
    s.name as subject_name,
    sm.marks_obtained,
    sm.grade,
    sm.year
FROM subject_marks sm
JOIN subjects s ON sm.subject_id = s.id
WHERE sm.candidate_id = 123
AND sm.year = 2025
ORDER BY sm.subject_id;

-- Check exam registration with calculated GPA/Division
SELECT 
    cer.candidate_id,
    cer.total_marks,
    cer.total_points,
    cer.gpa,
    cer.division,
    cer.grade
FROM candidate_exam_registrations cer
WHERE cer.candidate_id = 123
AND cer.exam_year_id = 1;
```

## Recalculate All Grades

If grades need to be recalculated for an exam year:

### Option 1: Via Artisan Command (if you have one)
```bash
php artisan grades:recalculate --exam-year=2026 --exam-type=ACSEE
```

### Option 2: Via Controller (create a temporary route)
```php
// routes/web.php
Route::get('/admin/recalculate-grades/{examYearId}/{examTypeId}', function($examYearId, $examTypeId) {
    $service = app(\App\Services\Results\GradeCalculationService::class);
    $results = $service->calculateForExamYear($examYearId, $examTypeId);
    
    return [
        'message' => 'Grade calculation completed',
        'results' => $results
    ];
});
```

Then visit: `http://localhost/admin/recalculate-grades/1/1`

### Option 3: Via Tinker
```bash
php artisan tinker
> $service = app(\App\Services\Results\GradeCalculationService::class)
> $service->calculateForExamYear(1, 1)
```

## Expected Behavior After Fix

1. **When marks are imported:**
   - ✓ System automatically calculates grades for each subject
   - ✓ Grades are stored in `subject_marks.grade` column
   - ✓ Total marks, total points, GPA, and division are calculated
   - ✓ `candidate_exam_registrations` table is updated with results

2. **Grade calculation follows NECTA rules:**
   - ✓ Grade boundaries: A(79.5+), B(69.5-79.49), C(59.5-69.49), D(49.5-59.49), E(39.5-49.49), S(34.5-39.49), F(0-34.49)
   - ✓ GPA excludes GENERAL STUDIES and BASIC APPLIED MATHEMATICS
   - ✓ Division calculated from total points (I: 3-9, II: 10-12, III: 13-17, IV: 18-19, O: 20+)

3. **Display in school results:**
   - ✓ Grades calculated dynamically from marks using NectaGradingService
   - ✓ GPA displayed with 2 decimal places
   - ✓ Division shows correct classification

## Logging

Check logs to verify calculations:
```bash
tail -f storage/logs/laravel.log | grep "Grades calculated"
```

Expected log message:
```
Grades calculated for candidate 123: GPA=2.50, Division=III
Grade calculation completed for 150 candidates
```

## Files Modified

1. `app/Services/Results/GradeCalculationService.php`
   - Fixed year column reference
   - Corrected GPA decimal precision

2. `app/Jobs/ProcessBulkImportFile.php`
   - Fixed year column reference in grade calculation query

## Testing

After fix is deployed, test with:
1. Import a small batch of marks (10-20 candidates)
2. Verify `subject_marks` table has correct grades
3. Verify `candidate_exam_registrations` has correct GPA and division
4. Check logs for any errors
5. View school results page to confirm grades display correctly
