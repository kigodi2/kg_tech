# NECTA Grading System Quick Start Guide

## Installation

1. **Copy the Service File**
   - The service is already located at: `app/Services/Results/NectaGradingService.php`

2. **Run Tests** (Optional)
   ```bash
   php artisan test tests/Unit/Services/Results/NectaGradingServiceTest.php
   ```

## Basic Usage

### 1. Calculate Grade for Marks
```php
use App\Services\Results\NectaGradingService;

$service = new NectaGradingService();

// Get grade for marks
$grade = $service->calculateGrade(75); // Returns 'B'

// Get competence level
$competence = $service->getCompetenceLevel($grade); // Returns 'Very Good'

// Get points value
$points = $service->getGradePoints($grade); // Returns 2
```

### 2. Check if Subject is Excluded
```php
$service = new NectaGradingService();

if ($service->isExcludedSubject('GENERAL STUDIES')) {
    echo "This subject is excluded from GPA calculation";
}
```

### 3. Get All Grade Data
```php
// Get grade boundaries
$boundaries = $service->getGradeBoundaries();
// Output:
// [
//   ['min' => 80, 'max' => 100, 'grade' => 'A', 'competence' => 'Excellent'],
//   ['min' => 70, 'max' => 79, 'grade' => 'B', 'competence' => 'Very Good'],
//   ...
// ]

// Get all excluded subjects
$excluded = $service->getExcludedSubjects();
// Output: ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS']
```

## Working with Candidates

### Generate Full Grading Report
```php
use App\Services\Results\NectaGradingService;
use App\Models\Candidate;

$service = new NectaGradingService();
$candidate = Candidate::find(1);

$report = $service->generateGradingReport(
    candidate: $candidate,
    examTypeId: 1,  // ACSEE = 1
    year: 2024
);

// Access report data
echo "Student: " . $report['candidate_name'];
echo "Total Marks: " . $report['total_marks'];
echo "GPA: " . $report['gpa'];
echo "Division: " . $report['division']['division'];
echo "Grade: " . $report['overall_grade'];

// Access individual subject grades
foreach ($report['subject_grades'] as $subject) {
    echo "{$subject['subject_name']}: {$subject['grade']} ({$subject['marks_obtained']} marks)";
}

// Access excluded subjects separately
echo "Excluded Subjects:";
foreach ($report['excluded_subject_grades'] as $subject) {
    echo "{$subject['subject_name']}: {$subject['grade']} (not counted in GPA)";
}
```

### Calculate Individual Metrics
```php
use App\Services\Results\NectaGradingService;
use App\Models\Candidate;

$service = new NectaGradingService();
$candidate = Candidate::find(1);

// Total marks (including excluded subjects)
$totalMarks = $service->calculateTotalMarks($candidate, 1, 2024);
echo "Total Marks: $totalMarks";

// Total points (excluding general studies and basic applied math)
$totalPoints = $service->calculateTotalPoints($candidate, 1, 2024);
echo "Total Points: $totalPoints";

// GPA (average of points for included subjects)
$gpa = $service->calculateGPA($candidate, 1, 2024);
echo "GPA: $gpa";

// Division based on total points
$division = $service->calculateDivision($totalPoints);
echo "Division: {$division['division']} ({$division['competence']})";

// Best overall grade
$grade = $service->calculateOverallGrade($candidate, 1, 2024);
echo "Overall Grade: $grade";
```

## Batch Processing

### Process All Candidates for Exam Year
```php
use App\Services\Results\NectaGradingService;

$service = new NectaGradingService();

// Process all candidates for ACSEE 2024
$results = $service->processBatchGrading(
    examTypeId: 1,
    year: 2024
);

foreach ($results as $report) {
    echo "{$report['candidate_name']}: GPA {$report['gpa']}, Division {$report['division']['division']}";
}
```

### Process By School
```php
// Process only candidates from specific school
$results = $service->processBatchGrading(
    examTypeId: 1,
    year: 2024,
    schoolId: 5  // School ID
);
```

## Integration with Controllers

### Example: Results Display Controller
```php
<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Services\Results\NectaGradingService;

class CandidateResultsController extends Controller
{
    private NectaGradingService $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function show(Candidate $candidate)
    {
        $report = $this->gradingService->generateGradingReport(
            candidate: $candidate,
            examTypeId: 1,
            year: now()->year
        );

        return view('results.show', [
            'candidate' => $candidate,
            'gradeReport' => $report,
        ]);
    }

    public function export(int $examTypeId, int $year)
    {
        $results = $this->gradingService->processBatchGrading(
            examTypeId: $examTypeId,
            year: $year
        );

        // Export to PDF, Excel, etc.
        return response()->download($this->generateFile($results));
    }
}
```

### Example: API Endpoint
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Services\Results\NectaGradingService;
use Illuminate\Http\Request;

class GradingApiController extends Controller
{
    private NectaGradingService $service;

    public function __construct(NectaGradingService $service)
    {
        $this->service = $service;
    }

    public function candidateReport(Candidate $candidate, Request $request)
    {
        $report = $this->service->generateGradingReport(
            candidate: $candidate,
            examTypeId: $request->exam_type_id,
            year: $request->year
        );

        return response()->json($report);
    }

    public function calculateGrade(Request $request)
    {
        $grade = $this->service->calculateGrade($request->marks);

        return response()->json([
            'marks' => $request->marks,
            'grade' => $grade,
            'competence' => $this->service->getCompetenceLevel($grade),
            'points' => $this->service->getGradePoints($grade),
        ]);
    }
}
```

## Integration with Models

### Enhance FinalGrade Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Results\NectaGradingService;

class FinalGrade extends Model
{
    // ... existing code ...

    protected $appends = ['gpa', 'division'];

    public function getGpaAttribute()
    {
        $service = new NectaGradingService();
        return $service->calculateGPA(
            $this->candidate,
            $this->exam_type_id,
            $this->year
        );
    }

    public function getDivisionAttribute()
    {
        $service = new NectaGradingService();
        $totalPoints = $service->calculateTotalPoints(
            $this->candidate,
            $this->exam_type_id,
            $this->year
        );
        return $service->calculateDivision($totalPoints);
    }
}
```

## Display in Views

### Blade Template Example
```blade
<div class="grading-report">
    <h2>{{ $gradeReport['candidate_name'] }}</h2>
    
    <div class="summary">
        <p><strong>Total Marks:</strong> {{ $gradeReport['total_marks'] }}</p>
        <p><strong>Total Points:</strong> {{ $gradeReport['total_points'] }}</p>
        <p><strong>GPA:</strong> {{ number_format($gradeReport['gpa'], 2) }}</p>
        <p><strong>Division:</strong> {{ $gradeReport['division']['division'] }}</p>
        <p><strong>Overall Grade:</strong> {{ $gradeReport['overall_grade'] }}</p>
    </div>

    <table class="subjects-table">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Points</th>
                <th>Competence</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($gradeReport['subject_grades'] as $subject)
                <tr class="@if($subject['is_excluded']) excluded @endif">
                    <td>{{ $subject['subject_name'] }}</td>
                    <td>{{ $subject['marks_obtained'] }}</td>
                    <td>{{ $subject['grade'] }}</td>
                    <td>{{ $subject['points'] }}</td>
                    <td>{{ $subject['competence'] }}</td>
                    <td>
                        @if($subject['is_excluded'])
                            <span class="badge">Excluded from GPA</span>
                        @else
                            <span class="badge">Included</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($gradeReport['excluded_subject_grades']) > 0)
        <div class="excluded-subjects-note">
            <p><em>Note: The following subjects are not included in GPA calculation:</em></p>
            <ul>
                @foreach ($gradeReport['excluded_subject_grades'] as $subject)
                    <li>{{ $subject['subject_name'] }}: {{ $subject['grade'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<style>
    .grading-report {
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .summary {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .subjects-table {
        width: 100%;
        border-collapse: collapse;
    }

    .subjects-table th,
    .subjects-table td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }

    .subjects-table tr.excluded {
        background: #fffacd;
    }

    .excluded-subjects-note {
        background: #fff3cd;
        border: 1px solid #ffc107;
        padding: 10px;
        border-radius: 5px;
        margin-top: 20px;
    }

    .badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .badge.excluded {
        background: #ffc107;
        color: #333;
    }
</style>
```

## Database Storage

### Storing Calculated Grades
```php
use App\Services\Results\NectaGradingService;
use App\Models\FinalGrade;
use App\Models\Candidate;

$service = new NectaGradingService();
$candidate = Candidate::find(1);

$report = $service->generateGradingReport($candidate, 1, 2024);

// Store in database
FinalGrade::create([
    'candidate_id' => $candidate->id,
    'exam_type_id' => 1,
    'year' => 2024,
    'overall_grade' => $report['overall_grade'],
    'total_marks' => $report['total_marks'],
    'grade_points' => $report['total_points'],
    'gpa' => $report['gpa'],
    'division' => $report['division']['division'],
    'grading_breakdown' => json_encode($report), // Store full report as JSON
]);
```

## Common Scenarios

### Scenario 1: Student has only 4 subjects
```
English: 80 (A, 1 point)
Math: 75 (B, 2 points)
Physics: 85 (A, 1 point)
Chemistry: 70 (B, 2 points)

Total Points = 1 + 2 + 1 + 2 = 6
GPA = 6 / 4 = 1.5
Division: I (Excellent)
```

### Scenario 2: Student has subjects including excluded ones
```
English: 75 (B, 2 points)
Math: 85 (A, 1 point)
Physics: 65 (C, 3 points)
General Studies: 55 (D, 4 points) [EXCLUDED]

Total Marks = 75 + 85 + 65 + 55 = 280
Total Points = 2 + 1 + 3 = 6 (General Studies not counted)
GPA = 6 / 3 = 2.0
Division: II (Very Good)
```

### Scenario 3: Student fails some subjects
```
English: 45 (E, 5 points)
Math: 30 (F, 7 points)
Physics: 55 (D, 4 points)

Total Points = 5 + 7 + 4 = 16
GPA = 16 / 3 = 5.33
Division: III (Good)
```

## Troubleshooting

### Issue: GPA appears null
**Check:**
- Are marks imported correctly? `SubjectMarks` must have data
- Is the exam year correct?
- Are subjects loaded with relationships?

```php
// Debug: Check marks
$marks = $candidate->marks()
    ->where('exam_type_id', 1)
    ->where('year', 2024)
    ->with('subject')
    ->get();

dd($marks);
```

### Issue: Excluded subjects not working
**Check:**
- Subject names must match exactly (case-insensitive in code)
- Verify subject names in database

```php
// Debug: Check excluded subjects
$excluded = $service->getExcludedSubjects();
dd($excluded);

// Check specific subject
if ($service->isExcludedSubject('GENERAL STUDIES')) {
    echo "Correctly identified as excluded";
}
```

### Issue: Division not calculated
**Check:**
- Total points must be a valid number
- Verify points are within division boundaries (3.99-30.0)

```php
// Debug: Check boundaries
$boundaries = $service->getDivisionBoundaries();
dd($boundaries);

// Check specific division
$division = $service->calculateDivision(15.5);
dd($division);
```

## Performance Tips

1. **Cache Results**: Cache grading reports for faster retrieval
   ```php
   $cacheKey = "grade_report_{$candidate->id}_{$examTypeId}_{$year}";
   $report = Cache::remember($cacheKey, 3600, function () use ($service, $candidate, $examTypeId, $year) {
       return $service->generateGradingReport($candidate, $examTypeId, $year);
   });
   ```

2. **Batch Process**: Use `processBatchGrading` for multiple candidates instead of individual calls

3. **Load Relationships**: Always eager load relationships
   ```php
   $candidate = Candidate::with(['marks.subject'])->find(1);
   ```

## Support & Documentation

- Full technical details: `NECTA_GRADING_SYSTEM_IMPLEMENTATION.md`
- Test file: `tests/Unit/Services/Results/NectaGradingServiceTest.php`
- Service code: `app/Services/Results/NectaGradingService.php`
