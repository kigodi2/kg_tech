# Grade Calculation System - Corrected Implementation

## Critical Issues Fixed

### Issue #1: Wrong Database Column Reference ❌➜✅
**Severity:** CRITICAL - Caused zero grades to be calculated

**Root Cause:**
- `SubjectMarks` table uses `year` column (e.g., 2025, 2026)
- Not `exam_year_id` column
- Queries were returning empty results

**Files Fixed:**
1. `app/Services/Results/GradeCalculationService.php` - Line 42-46
2. `app/Jobs/ProcessBulkImportFile.php` - Line 195

**Before (WRONG):**
```php
$marks = SubjectMarks::where('candidate_id', $candidateId)
    ->where('exam_year_id', $examYearId)  // ❌ WRONG COLUMN
    ->where('exam_type_id', $examTypeId)
    ->get();
```

**After (CORRECT):**
```php
$examYear = ExamYear::find($examYearId);
$marks = SubjectMarks::where('candidate_id', $candidateId)
    ->where('year', $examYear->year)  // ✅ CORRECT COLUMN
    ->where('exam_type_id', $examTypeId)
    ->get();
```

### Issue #2: GPA Decimal Precision Inconsistency ❌➜✅
**Severity:** MEDIUM - Display inconsistency with grading service

**Root Cause:**
- GradeCalculationService rounded to 4 decimals
- NectaGradingService rounds to 2 decimals
- Inconsistent GPA values

**File Fixed:**
`app/Services/Results/GradeCalculationService.php` - Line 78

**Before (INCONSISTENT):**
```php
$gpa = round($totalPoints / $validSubjectCount, 4);  // ❌ 4 decimals
```

**After (CONSISTENT):**
```php
$gpa = round($totalPoints / $validSubjectCount, 2);  // ✅ 2 decimals (matches NectaGradingService)
```

## How Grade Calculation Works (CORRECTED)

### Flow Diagram
```
1. Marks Imported
    ↓
2. ProcessBulkImportFile Job runs
    ↓
3. Identifies all candidates with new marks
    ↓
4. For each candidate:
    a. Get all marks for candidate in exam year
    b. Calculate grade for each mark (using NECTA boundaries)
    c. Sum total marks (all subjects)
    d. Sum total points (excluding 2 subjects)
    e. Calculate GPA = Points ÷ Valid Subjects
    f. Determine Division from total points
    g. Update candidate_exam_registrations
```

### Step-by-Step Example

**Candidate: 123**
Marks for exam year 2025, ACSEE:

| Subject | Marks | Grade | Points | Excluded? |
|---------|-------|-------|--------|-----------|
| GENERAL STUDIES | 65 | C | 3 | YES |
| PHYSICS | 75 | B | 2 | NO |
| CHEMISTRY | 80 | A | 1 | NO |
| BIOLOGY | 70 | B | 2 | NO |
| **BASIC APPLIED MATH** | 60 | C | 3 | YES |

**Calculations:**
- Total Marks = 65+75+80+70+60 = **350** ✓
- Valid Subjects = 3 (Physics, Chemistry, Biology) ✓
- Total Points = 2+1+2 = **5** ✓
- GPA = 5 ÷ 3 = **1.67** ✓
- Division = III (13-17 points) → **N/A** (only 5 points) → **I** ✓

**Database Update:**
```sql
UPDATE candidate_exam_registrations
SET 
    total_marks = 350,
    total_points = 5,
    gpa = 1.67,
    division = 'I',
    grade = 'A'  -- Best overall grade
WHERE candidate_id = 123
AND exam_year_id = 1
AND exam_type_id = 1;
```

## Verification Steps

### 1. Check Database
```sql
-- Verify marks have grades
SELECT candidate_id, subject_id, marks_obtained, grade, year
FROM subject_marks
WHERE year = 2025
LIMIT 20;

-- Verify registration has GPA/Division
SELECT candidate_id, total_marks, total_points, gpa, division
FROM candidate_exam_registrations
WHERE exam_year_id = 1
LIMIT 20;
```

### 2. Check Logs
```bash
tail -50 storage/logs/laravel.log | grep -i "grade"
```

Expected logs:
```
[2026-02-08] Grades calculated for candidate 123: GPA=1.67, Division=I
[2026-02-08] Grade calculation completed for 150 candidates
```

### 3. Manual Recalculation

**Using Artisan Command:**
```bash
php artisan grades:recalculate --exam-year=1 --exam-type=ACSEE
```

**Using Tinker:**
```bash
php artisan tinker
> $service = app(\App\Services\Results\GradeCalculationService::class)
> $results = $service->calculateForExamYear(1, 1)
> dd($results)
```

## Files Changed

| File | Changes | Line |
|------|---------|------|
| `app/Services/Results/GradeCalculationService.php` | Fixed year column reference | 42-49 |
| `app/Services/Results/GradeCalculationService.php` | Fixed GPA decimal precision | 78 |
| `app/Jobs/ProcessBulkImportFile.php` | Fixed year column in query | 180-201 |
| `app/Console/Commands/RecalculateGrades.php` | NEW: Command to recalculate all grades | N/A |

## New Artisan Command

**Command:** `php artisan grades:recalculate`

**Options:**
- `--exam-year=ID` - Exam year ID (required)
- `--exam-type=CODE` - Exam type code, default: ACSEE

**Usage Examples:**
```bash
# Interactive mode
php artisan grades:recalculate

# Direct parameters
php artisan grades:recalculate --exam-year=1 --exam-type=ACSEE

# For 2026 exam year
php artisan grades:recalculate --exam-year=2 --exam-type=ACSEE
```

## NECTA Grading Standards (Reference)

### Grade Boundaries
| Grade | Min Marks | Max Marks | Competence |
|-------|-----------|-----------|------------|
| A | 79.5 | 100 | Excellent |
| B | 69.5 | 79.49 | Very Good |
| C | 59.5 | 69.49 | Good |
| D | 49.5 | 59.49 | Average |
| E | 39.5 | 49.49 | Satisfactory |
| S | 34.5 | 39.49 | Unsatisfactory |
| F | 0 | 34.49 | Fail |

### Division Boundaries
| Division | Points Range | Competence |
|----------|--------------|-----------|
| I | 3-9 | Excellent |
| II | 10-12 | Very Good |
| III | 13-17 | Good |
| IV | 18-19 | Average |
| O | 20+ | Fail |

### Grade Points
| Grade | Points |
|-------|--------|
| A | 1 |
| B | 2 |
| C | 3 |
| D | 4 |
| E | 5 |
| S | 6 |
| F | 7 |

## Testing Checklist

- [ ] Import marks for a test batch of 10-20 students
- [ ] Verify `subject_marks.grade` column has correct grades
- [ ] Verify `candidate_exam_registrations.gpa` is calculated correctly
- [ ] Verify `candidate_exam_registrations.division` is correct
- [ ] Verify school results page displays correct grades and GPA
- [ ] Check logs for any error messages
- [ ] Run `php artisan grades:recalculate` to test command
- [ ] Verify display format in hierarchy/school-results view (2 decimal GPA)

## Rollback Plan

If issues occur:

1. **Check logs** for error messages
2. **Verify database** columns exist and are correct
3. **Recalculate** using command:
   ```bash
   php artisan grades:recalculate --exam-year=1 --exam-type=ACSEE
   ```
4. **Monitor** logs during recalculation
5. **Contact support** if errors persist

## Support

For issues with grade calculation:
1. Check the GRADE_CALCULATION_FIX.md document
2. Run verification SQL queries
3. Check application logs
4. Use artisan command to recalculate
