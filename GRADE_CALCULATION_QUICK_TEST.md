# Grade Calculation Quick Test Guide

## Quick Verification (5 minutes)

### 1. Check One Candidate's Marks
```bash
mysql irms -e "SELECT candidate_id, subject_id, marks_obtained, grade, year FROM subject_marks WHERE candidate_id = 1 LIMIT 5;"
```

Expected output: Shows marks with grade column populated (A, B, C, D, E, S, F)

### 2. Check Candidate's GPA/Division
```bash
mysql irms -e "SELECT candidate_id, total_marks, total_points, gpa, division FROM candidate_exam_registrations WHERE candidate_id = 1;"
```

Expected output: Shows calculated GPA (e.g., 2.50) and Division (I, II, III, IV, or O)

### 3. Verify Grades in View
Visit: `http://yourapp.test/hierarchy/school/1/results`

Look for:
- ✓ GPA column shows values like "2.50" (2 decimal places)
- ✓ DIV column shows I, II, III, IV, or O
- ✓ Grades calculated dynamically from marks

### 4. Check Recent Logs
```bash
tail -100 storage/logs/laravel.log | grep -i "grade"
```

Expected: See messages like "Grades calculated for candidate 123: GPA=2.50, Division=III"

## Manual Recalculation Test

### Option 1: Single Candidate (Fastest)
```php
// In tinker or controller
$service = app(\App\Services\Results\GradeCalculationService::class);
$result = $service->calculateForCandidate(
    candidateId: 1,
    examYearId: 1,
    examTypeId: 1
);
echo $result ? "SUCCESS" : "FAILED";
```

### Option 2: All Candidates in Exam Year
```bash
php artisan grades:recalculate --exam-year=1 --exam-type=ACSEE
```

### Option 3: School Batch
```php
$service = app(\App\Services\Results\GradeCalculationService::class);
$results = $service->calculateForSchool(1, 1, 1);
echo json_encode($results);
// Output: {"total": 150, "success": 145, "failed": 5}
```

## Expected Values Example

**For a candidate with these marks:**
- GENERAL STUDIES: 65 marks
- PHYSICS: 75 marks  
- CHEMISTRY: 80 marks
- BIOLOGY: 70 marks
- BASIC APPLIED MATH: 60 marks

**Expected calculations:**
| Metric | Value |
|--------|-------|
| Total Marks | 350 |
| Total Points | 5 (2+1+2, excluding 2 subjects) |
| GPA | 1.67 (5÷3) |
| Division | I (3-9 points range) |

## Troubleshooting

### Issue: Grades not calculated
**Check:**
```sql
SELECT COUNT(*) as total, COUNT(grade) as with_grade FROM subject_marks WHERE year = 2025;
```
If `with_grade` is less than `total`, grades haven't been calculated.

**Fix:**
```bash
php artisan grades:recalculate --exam-year=1
```

### Issue: Wrong GPA values
**Check:**
```sql
SELECT 
    candidate_id,
    SUM(marks_obtained) as total_marks,
    COUNT(*) as subject_count,
    gpa
FROM subject_marks sm
JOIN candidate_exam_registrations cer ON sm.candidate_id = cer.candidate_id
WHERE sm.candidate_id = 1
GROUP BY candidate_id;
```

**Verify:** GPA should be calculated excluding GENERAL STUDIES and BASIC APPLIED MATHEMATICS

### Issue: Wrong Division
**Check:**
```sql
SELECT total_points, division FROM candidate_exam_registrations WHERE candidate_id = 1;
```

**Reference table:**
- 3-9 points → Division I
- 10-12 points → Division II
- 13-17 points → Division III
- 18-19 points → Division IV
- 20+ points → Division O

## Performance Test

### Import Sample Data (10 students)
```bash
# Via bulk import UI or API
# Then check time taken in logs
tail storage/logs/laravel.log | grep "Grade calculation completed"
```

Expected time: ~5-10 seconds for 10 students

### Recalculate All (150+ students)
```bash
time php artisan grades:recalculate --exam-year=1 --exam-type=ACSEE
```

Expected time: ~30-60 seconds for 150 students

## Database Queries for Verification

### Count grades by type
```sql
SELECT grade, COUNT(*) as count FROM subject_marks WHERE year = 2025 GROUP BY grade ORDER BY grade;
```

### Find candidates with missing grades
```sql
SELECT DISTINCT candidate_id FROM subject_marks WHERE grade IS NULL AND year = 2025;
```

### Verify GPA distribution
```sql
SELECT 
    CASE 
        WHEN gpa >= 3.5 THEN 'A (Excellent)'
        WHEN gpa >= 3.0 THEN 'B (Good)'
        WHEN gpa >= 2.5 THEN 'C (Satisfactory)'
        WHEN gpa >= 1.5 THEN 'D (Average)'
        ELSE 'E (Fail)'
    END as grade_range,
    COUNT(*) as count
FROM candidate_exam_registrations
WHERE exam_year_id = 1
GROUP BY grade_range;
```

### Check excluded subjects
```sql
SELECT 
    sm.subject_id,
    s.name,
    COUNT(*) as count
FROM subject_marks sm
JOIN subjects s ON sm.subject_id = s.id
WHERE s.name IN ('GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS')
AND sm.year = 2025
GROUP BY sm.subject_id, s.name;
```

## Success Indicators

✓ All marks have a grade (A-F or X for absent)
✓ GPA values are between 1.00 and 7.00
✓ Division values are I, II, III, IV, or O
✓ Total Points reflects non-excluded subjects only
✓ School results page displays grades correctly
✓ No error messages in logs for grade calculations
✓ Recalculation command completes without errors
