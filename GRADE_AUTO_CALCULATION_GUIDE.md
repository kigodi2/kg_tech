# Grade Auto-Calculation System

## Overview

Grades, GPA, division, and points are now **automatically calculated and stored** as soon as marks are imported into the system. This ensures data consistency and eliminates the need for manual grade entry or separate calculation runs.

## How It Works

### 1. Bulk Mark Import Flow

When marks are imported via bulk CSV import:

```
CSV Upload → ProcessBulkImportFile Job → Insert Marks → Calculate Grades → Update Exam Registration
```

**Steps:**
1. Bulk import CSV file is processed in chunks
2. Marks are inserted into `subject_marks` table
3. `GradeCalculationService::calculateForCandidate()` is called for each affected candidate
4. Grades are calculated using NECTA standards
5. Total marks, total points, GPA, and division are calculated
6. `candidate_exam_registrations` table is updated with calculated values

### 2. Automatic Grade Calculation

For each candidate, the system:

1. **Calculates individual subject grades** using NECTA boundaries:
   - Marks are analyzed and grade assigned (A, B, C, D, E, S, F)
   - Grade is stored in `subject_marks.grade`

2. **Calculates total marks**:
   - Sum of all marks across all subjects (including excluded subjects)

3. **Calculates total points**:
   - Only counts points from non-excluded subjects (not GENERAL STUDIES or BASIC APPLIED MATHEMATICS)
   - Each grade has corresponding points: A=1, B=2, C=3, D=4, E=5, S=6, F=7

4. **Calculates GPA**:
   - GPA = Total Points / Number of valid subjects
   - Formula: `GPA = Sum(Points) / Count(Non-Excluded Subjects)`
   - Example: If student has 4 valid subjects with points [1, 2, 3, 2], GPA = 8/4 = 2.0

5. **Determines division**:
   - Based on total points using NECTA standards:
     - Division I: 3-9 points (Excellent)
     - Division II: 10-12 points (Very Good)
     - Division III: 13-17 points (Good)
     - Division IV: 18-19 points (Average)
     - Division O: 20+ points (Fail/Other)

6. **Updates exam registration**:
   - `candidate_exam_registrations.grade` → Overall grade
   - `candidate_exam_registrations.total_marks` → Total marks
   - `candidate_exam_registrations.total_points` → Total points
   - `candidate_exam_registrations.gpa` → GPA
   - `candidate_exam_registrations.division` → Division

## Integration Points

### ProcessBulkImportFile Job
**File:** `app/Jobs/ProcessBulkImportFile.php`

After marks are inserted, the job automatically:
- Identifies all candidates affected by the import
- Calls `GradeCalculationService::calculateForCandidate()` for each candidate
- Logs progress and completion

```php
private function calculateGradesForImportedMarks(GradeCalculationService $gradeCalculationService): void
{
    // Gets all unique candidates from imported marks
    $candidates = DB::table('subject_marks')
        ->where('subject_id', $this->importFile->subject_id)
        ->where('exam_year_id', $this->importFile->bulkImport->exam_year_id)
        ->distinct()
        ->pluck('candidate_id');

    // Calculate grades for each
    foreach ($candidates as $candidateId) {
        $gradeCalculationService->calculateForCandidate(...);
    }
}
```

### GradeCalculationService
**File:** `app/Services/Results/GradeCalculationService.php`

Main service that handles all grade calculations.

**Public Methods:**

```php
// Calculate grades for a single candidate
calculateForCandidate(int $candidateId, int $examYearId, int $examTypeId): bool

// Calculate grades for all candidates in a school
calculateForSchool(int $schoolId, int $examYearId, int $examTypeId): array

// Calculate grades for all candidates in an exam year
calculateForExamYear(int $examYearId, int $examTypeId): array
```

## Usage Examples

### After Bulk Import (Automatic)
```php
// This happens automatically in ProcessBulkImportFile::handle()
$gradeCalculationService->calculateForCandidate(
    candidateId: 123,
    examYearId: 1,
    examTypeId: 1
);
```

### Manual Calculation for Single Student
```php
$gradeCalculationService = app(GradeCalculationService::class);
$result = $gradeCalculationService->calculateForCandidate(123, 1, 1);
// Returns: true if successful, false if failed
```

### Batch Calculation for School
```php
$gradeCalculationService = app(GradeCalculationService::class);
$results = $gradeCalculationService->calculateForSchool(
    schoolId: 5,
    examYearId: 2026,
    examTypeId: 1
);
// Returns: ['total' => 150, 'success' => 145, 'failed' => 5]
```

### Batch Calculation for Entire Exam Year
```php
$gradeCalculationService = app(GradeCalculationService::class);
$results = $gradeCalculationService->calculateForExamYear(
    examYearId: 2026,
    examTypeId: 1
);
// Calculates grades for all candidates in the exam year
```

## Data Storage

### subject_marks table
```sql
grade CHAR(1)  -- Calculated grade (A, B, C, D, E, S, F)
```

### candidate_exam_registrations table
```sql
total_marks DECIMAL(8,2)     -- Sum of all subject marks
total_points INT              -- Sum of grade points (excluding certain subjects)
gpa DECIMAL(5,4)             -- Average of points
division VARCHAR(1)          -- I, II, III, IV, O
grade CHAR(1)                -- Overall best grade
```

## Excluded Subjects

The following subjects are **excluded from GPA and points calculation** per NECTA rules:
- GENERAL STUDIES
- BASIC APPLIED MATHEMATICS

These subjects are included in **total marks** but not in **GPA or points calculation**.

## NECTA Grade Boundaries

| Grade | Min Marks | Max Marks | Competence Level |
|-------|-----------|-----------|------------------|
| A     | 79.5      | 100       | Excellent        |
| B     | 69.5      | 79.49     | Very Good        |
| C     | 59.5      | 69.49     | Good             |
| D     | 49.5      | 59.49     | Average          |
| E     | 39.5      | 49.49     | Satisfactory     |
| S     | 34.5      | 39.49     | Unsatisfactory   |
| F     | 0         | 34.49     | Fail             |

## Troubleshooting

### Grades Not Calculated

**Check:**
1. Bulk import job completed successfully (check `bulk_import_files.status = 'success'`)
2. Marks are in `subject_marks` table
3. Candidates are registered in exam year
4. Job queue is processing (if using queue)

**Manually Recalculate:**
```bash
# Via Artisan command (if created)
php artisan grades:calculate --exam-year=2026

# Or manually in controller
$service = app(GradeCalculationService::class);
$service->calculateForExamYear(2026, 1);
```

### Wrong GPA Values

**Verify:**
1. Subject marks are correct
2. Excluded subjects (GENERAL STUDIES, BASIC APPLIED MATHEMATICS) are not counted in points
3. Only subjects with marks are counted in valid subject count

**Debug:**
```php
$marks = SubjectMarks::where('candidate_id', 123)->get();
$marks->each(function($m) {
    $grade = $gradingService->calculateGrade($m->marks_obtained);
    echo "Subject: {$m->subject->name}, Grade: {$grade}\n";
});
```

## Performance Considerations

- Grades are calculated **in the background** via queue job
- Single candidate calculation: ~100ms
- Batch school calculation (150 students): ~15-20 seconds
- Batch exam year calculation: Done asynchronously, no user wait

## Logging

All grade calculations are logged to:
- **Log file:** `storage/logs/laravel.log`
- **Messages:**
  - `"Grades calculated for candidate {id}: GPA={gpa}, Division={division}"`
  - `"Batch grade calculation completed for school {id}"`
  - `"Batch grade calculation completed for exam year {id}"`

Monitor logs to ensure grades are being calculated correctly.
