# NECTA Grading System Implementation Guide

## Overview

This document describes the implementation of the NECTA grading system in the IRMS. The system implements official NECTA grading standards where certain subjects (GENERAL STUDIES and BASIC APPLIED MATHEMATICS) are excluded from GPA and total points calculations but included in total marks.

## Grading Scales

### 1. Marks to Grade Conversion

| MARKS | GRADE | COMPETENCE LEVEL |
|-------|-------|------------------|
| 80-100 | A | Excellent |
| 70-79 | B | Very Good |
| 60-69 | C | Good |
| 50-59 | D | Average |
| 40-49 | E | Satisfactory |
| 35-39 | S | Unsatisfactory |
| 0-34 | F | Fail |

### 2. Grade to Points Conversion

| GRADE | POINT |
|-------|-------|
| A | 1 |
| B | 2 |
| C | 3 |
| D | 4 |
| E | 5 |
| S | 6 |
| F | 7 |

### 3. Division Classification (Based on Total Points)

| DIVISION | POINTS | COMPETENCE LEVEL |
|----------|--------|------------------|
| I | 3-9 | Excellent |
| II | 10-12 | Very Good |
| III | 13-17 | Good |
| IV | 18-19 | Average |
| O | 20-21 | Fail |

## Key Business Rules

### Excluded Subjects
The following subjects are **EXCLUDED** from GPA and total points calculations:
- GENERAL STUDIES
- BASIC APPLIED MATHEMATICS

### Included Subjects
All other subjects are included in both:
- Total Points calculation
- GPA calculation
- Division determination

### Calculations

#### Total Marks (TOTAL)
**Formula:** Sum of all subject marks (including excluded subjects)

```php
Total Marks = Sum of all subject marks
```

Example:
- English: 75
- Mathematics: 85
- Physics: 70
- Chemistry: 65
- Biology: 80
- General Studies: 55
- Basic Applied Math: 60

Total Marks = 75 + 85 + 70 + 65 + 80 + 55 + 60 = **490**

#### Total Points
**Formula:** Sum of grade points for all subjects EXCEPT excluded ones

```php
Total Points = Sum of points for (included subjects only)
```

Example (using subjects above):
- English (75): B = 2 points
- Mathematics (85): A = 1 point
- Physics (70): B = 2 points
- Chemistry (65): C = 3 points
- Biology (80): A = 1 point
- General Studies (55): D = 4 points [EXCLUDED]
- Basic Applied Math (60): C = 3 points [EXCLUDED]

Total Points = 2 + 1 + 2 + 3 + 1 = **9 points** (excluded subjects not counted)

#### GPA (Grade Point Average)
**Formula:** Total Points ÷ Number of included subjects

```php
GPA = Total Points / Count of included subjects
```

Using example above:
- Total Points: 9
- Included subjects: 5 (English, Math, Physics, Chemistry, Biology)

GPA = 9 / 5 = **1.80**

#### Division
**Determination:** Based on Total Points (excluding subjects)

Using example above:
- Total Points: 9
- Points in range 3-9
- **Division: I** (Excellent)

## Service Integration

### NectaGradingService

Located at: `app/Services/Results/NectaGradingService.php`

#### Available Methods

##### Grade Calculation
```php
public function calculateGrade(float $marks): string
```
Returns the grade letter (A, B, C, D, E, S, F) for given marks.

##### Competence Level
```php
public function getCompetenceLevel(string $grade): string
```
Returns the competence description for a grade.

##### Grade Points
```php
public function getGradePoints(string $grade): int
```
Returns the point value (1-7) for a grade.

##### Subject Exclusion Check
```php
public function isExcludedSubject(string $subjectName): bool
```
Checks if a subject should be excluded from GPA/points calculation.

##### Total Marks Calculation
```php
public function calculateTotalMarks(Candidate $candidate, int $examTypeId, int $year): ?float
```
Calculates total marks including all subjects.

##### Total Points Calculation
```php
public function calculateTotalPoints(Candidate $candidate, int $examTypeId, int $year): ?float
```
Calculates total points excluding specified subjects.

##### GPA Calculation
```php
public function calculateGPA(Candidate $candidate, int $examTypeId, int $year): ?float
```
Calculates GPA as average of points for included subjects.

##### Division Calculation
```php
public function calculateDivision(float $totalPoints): ?array
```
Determines division based on total points.

##### Overall Grade
```php
public function calculateOverallGrade(Candidate $candidate, int $examTypeId, int $year): ?string
```
Calculates best grade among all subjects.

##### Complete Grading Report
```php
public function generateGradingReport(Candidate $candidate, int $examTypeId, int $year): ?array
```
Generates comprehensive grading report including all calculations.

Returns:
```php
[
    'candidate_id' => int,
    'candidate_name' => string,
    'exam_type_id' => int,
    'year' => int,
    'subject_grades' => [
        [
            'subject_id' => int,
            'subject_name' => string,
            'marks_obtained' => float,
            'grade' => string,
            'points' => int,
            'competence' => string,
            'is_excluded' => bool,
        ],
        // ...
    ],
    'included_subject_grades' => [...],
    'excluded_subject_grades' => [...],
    'total_marks' => float,
    'total_points' => float,
    'gpa' => float,
    'division' => [
        'division' => string,
        'competence' => string,
        'points' => float,
    ],
    'overall_grade' => string,
    'competence_level' => string,
]
```

##### Batch Processing
```php
public function processBatchGrading(int $examTypeId, int $year, ?int $schoolId = null): array
```
Processes grading for all candidates in a given exam type and year.

## Usage Examples

### Example 1: Get Grade for Marks
```php
use App\Services\Results\NectaGradingService;

$service = new NectaGradingService();

$grade = $service->calculateGrade(75); // Returns 'B'
$competence = $service->getCompetenceLevel('B'); // Returns 'Very Good'
```

### Example 2: Calculate GPA for Candidate
```php
use App\Services\Results\NectaGradingService;
use App\Models\Candidate;

$service = new NectaGradingService();
$candidate = Candidate::find(1);

$gpa = $service->calculateGPA($candidate, 1, 2024);
```

### Example 3: Generate Full Grading Report
```php
use App\Services\Results\NectaGradingService;
use App\Models\Candidate;

$service = new NectaGradingService();
$candidate = Candidate::find(1);

$report = $service->generateGradingReport($candidate, 1, 2024);

echo "Candidate: " . $report['candidate_name'];
echo "Total Marks: " . $report['total_marks'];
echo "GPA: " . $report['gpa'];
echo "Division: " . $report['division']['division'];
```

### Example 4: Check if Subject is Excluded
```php
use App\Services\Results\NectaGradingService;

$service = new NectaGradingService();

if ($service->isExcludedSubject('GENERAL STUDIES')) {
    echo "This subject is excluded from GPA calculation";
}
```

### Example 5: Batch Grade Processing
```php
use App\Services\Results\NectaGradingService;

$service = new NectaGradingService();

// Process all candidates for ACSEE 2024 in a school
$results = $service->processBatchGrading(
    examTypeId: 1,
    year: 2024,
    schoolId: 5
);

foreach ($results as $report) {
    // Store or export results
}
```

## Integration Points

### 1. Update Candidate Results Display
Modify the candidate results view to use the new grading service:

```php
public function show(Candidate $candidate)
{
    $service = new NectaGradingService();
    $gradeReport = $service->generateGradingReport($candidate, 1, 2024);
    
    return view('candidate.results', compact('gradeReport'));
}
```

### 2. Update FinalGrade Model
Store calculated values:

```php
$finalGrade = FinalGrade::create([
    'candidate_id' => $candidate->id,
    'exam_type_id' => 1,
    'year' => 2024,
    'overall_grade' => $report['overall_grade'],
    'total_marks' => $report['total_marks'],
    'grade_points' => $report['total_points'],
    'gpa' => $report['gpa'],
    // Additional fields for division
]);
```

### 3. Update Reports/Exports
Use the service to generate PDF reports, Excel exports, etc.:

```php
public function exportResults($examTypeId, $year)
{
    $service = new NectaGradingService();
    $results = $service->processBatchGrading($examTypeId, $year);
    
    // Export to PDF, Excel, etc.
}
```

### 4. Update API Endpoints
Create endpoints for grade calculations:

```php
Route::post('/api/grades/calculate', function (Request $request) {
    $service = new NectaGradingService();
    $candidate = Candidate::find($request->candidate_id);
    
    $report = $service->generateGradingReport(
        $candidate,
        $request->exam_type_id,
        $request->year
    );
    
    return response()->json($report);
});
```

## Migration Considerations

### Database Schema Updates

If you need to add fields to track grading information:

```php
Schema::table('final_grades', function (Blueprint $table) {
    $table->float('gpa')->nullable();
    $table->float('total_points')->nullable();
    $table->string('division')->nullable();
    $table->text('grading_breakdown')->nullable(); // JSON
});
```

### Data Recalculation

To recalculate grades for existing candidates:

```php
use App\Services\Results\NectaGradingService;
use App\Models\FinalGrade;

$service = new NectaGradingService();

$finalGrades = FinalGrade::all();

foreach ($finalGrades as $grade) {
    $report = $service->generateGradingReport(
        $grade->candidate,
        $grade->exam_type_id,
        $grade->year
    );
    
    $grade->update([
        'total_marks' => $report['total_marks'],
        'grade_points' => $report['total_points'],
        'gpa' => $report['gpa'],
        'overall_grade' => $report['overall_grade'],
    ]);
}
```

## Testing

### Unit Test Example
```php
public function test_grade_calculation()
{
    $service = new NectaGradingService();
    
    $this->assertEquals('A', $service->calculateGrade(80));
    $this->assertEquals('B', $service->calculateGrade(75));
    $this->assertEquals('F', $service->calculateGrade(30));
}

public function test_excluded_subjects()
{
    $service = new NectaGradingService();
    
    $this->assertTrue($service->isExcludedSubject('GENERAL STUDIES'));
    $this->assertTrue($service->isExcludedSubject('BASIC APPLIED MATHEMATICS'));
    $this->assertFalse($service->isExcludedSubject('ENGLISH'));
}

public function test_gpa_calculation()
{
    $service = new NectaGradingService();
    $candidate = Candidate::factory()->create();
    
    $gpa = $service->calculateGPA($candidate, 1, 2024);
    
    $this->assertIsFloat($gpa);
    $this->assertGreaterThanOrEqual(1, $gpa);
    $this->assertLessThanOrEqual(7, $gpa);
}
```

## Customization

### Modifying Grade Boundaries
Edit the `GRADE_BOUNDARIES` constant in `NectaGradingService`:

```php
private const GRADE_BOUNDARIES = [
    ['min' => 80, 'max' => 100, 'grade' => 'A', 'competence' => 'Excellent'],
    // ... modify as needed
];
```

### Changing Excluded Subjects
Edit the `EXCLUDED_SUBJECTS` constant:

```php
private const EXCLUDED_SUBJECTS = [
    'GENERAL STUDIES',
    'BASIC APPLIED MATHEMATICS',
    // Add more as needed
];
```

### Adding New Competence Levels
Modify `DIVISION_BOUNDARIES` as needed:

```php
private const DIVISION_BOUNDARIES = [
    ['min' => 3.99, 'max' => 10.12, 'division' => 'I', 'competence' => 'Excellent'],
    // ... customize
];
```

## Troubleshooting

### Issue: Incorrect GPA Calculation
**Solution:** Ensure SubjectMarks model properly loads subject relationships and marks are correctly stored.

### Issue: Excluded Subjects Not Working
**Solution:** Check subject names match exactly (case-sensitive). Subject names must match `EXCLUDED_SUBJECTS` constant.

### Issue: Division Not Calculated
**Solution:** Verify total points are calculated correctly and fall within `DIVISION_BOUNDARIES` ranges.

## Future Enhancements

1. **Dynamic Configuration:** Move grade boundaries to database for runtime customization
2. **Multiple Grading Systems:** Support different grading profiles per exam year
3. **Advanced Reporting:** Generate detailed statistical reports and analysis
4. **Performance Optimization:** Cache calculations for faster batch processing
5. **Validation Rules:** Add business rule validation for grade consistency

## Support

For issues or questions regarding the NECTA grading implementation, refer to:
- Service code: `app/Services/Results/NectaGradingService.php`
- Tests: `tests/Unit/Services/Results/NectaGradingServiceTest.php`
