# NECTA Grading System - Integration Guide

## Integration Steps

### Step 1: Register API Routes

Add to `routes/api.php`:

```php
// At the top of the file, add:
require base_path('routes/api-grading.php');

// Or include within your api group:
Route::middleware(['api'])->prefix('api')->group(function () {
    require base_path('routes/api-grading.php');
});
```

### Step 2: Run Migration

Add the GPA and division fields to final_grades table:

```bash
php artisan migrate
```

This adds:
- `gpa` - Student's GPA (float)
- `division` - Student's division (string)
- `grading_breakdown` - Full grading report (JSON)

### Step 3: Update FinalGrade Model

Add the new fields to the model's fillable array:

```php
// app/Models/FinalGrade.php

protected $fillable = [
    'candidate_id',
    'exam_type_id',
    'year',
    'grading_profile_id',
    'overall_grade',
    'total_marks',
    'grade_points',
    'gpa',
    'division',
    'grading_breakdown',
    'is_published',
    'published_at',
    'is_locked',
    'locked_at',
];

protected $casts = [
    'is_published' => 'boolean',
    'is_locked' => 'boolean',
    'published_at' => 'datetime',
    'locked_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'grading_breakdown' => 'array', // Cast JSON to array
];
```

### Step 4: Create Web Routes (Optional)

Add to `routes/web.php`:

```php
Route::middleware(['auth'])->prefix('grading')->group(function () {
    Route::get('/dashboard', [NectaGradingController::class, 'dashboard'])->name('grading.dashboard');
    Route::get('/candidate/{candidate}/results', [NectaGradingController::class, 'candidateResults'])->name('grading.candidate-results');
});
```

## API Endpoints

### Reference Data
```
GET /api/grading/reference
```
Returns all grade boundaries, points mapping, division boundaries, and excluded subjects.

### Calculate Grade
```
POST /api/grading/calculate-grade
Body: { "marks": 85 }
```
Returns grade, competence, points, and color for given marks.

### Get Candidate Grades
```
GET /api/grading/candidate/{candidate_id}/grades?exam_type_id=1&year=2024
```
Returns complete grading report for a candidate.

### Store Candidate Grades
```
POST /api/grading/candidate/{candidate_id}/store-grades
Body: { "exam_type_id": 1, "year": 2024 }
```
Calculates and stores grades in final_grades table.

### School Statistics
```
GET /api/grading/school/statistics?school_id=5&exam_type_id=1&year=2024
```
Returns grading statistics for a school.

### Batch Process
```
POST /api/grading/batch-process
Body: { "exam_type_id": 1, "year": 2024, "school_id": 5 }
```
Processes grades for all candidates.

### Publish Grades
```
POST /api/grading/publish
Body: { "exam_type_id": 1, "year": 2024 }
```
Marks grades as published.

### Lock Grades
```
POST /api/grading/lock
Body: { "exam_type_id": 1, "year": 2024 }
```
Locks grades to prevent modifications.

## Usage Examples

### Using the Service Directly

```php
use App\Services\Results\NectaGradingService;
use App\Models\Candidate;

$service = new NectaGradingService();
$candidate = Candidate::find(1);

// Get complete report
$report = $service->generateGradingReport($candidate, 1, 2024);

// Access individual components
echo $report['total_marks'];           // 490
echo $report['total_points'];          // 9
echo $report['gpa'];                   // 1.5
echo $report['division']['division'];  // 'I'
echo $report['overall_grade'];         // 'A'

// Get color for competence display
$color = $service->getGradeColor('A'); // '#00AA7A'
```

### Using the Controller (HTTP)

```php
// Get candidate grades
GET /api/grading/candidate/1/grades?exam_type_id=1&year=2024

Response:
{
  "candidate_id": 1,
  "candidate_name": "John Doe",
  "total_marks": 490,
  "total_points": 9,
  "gpa": 1.5,
  "division": {
    "division": "I",
    "competence": "Excellent"
  },
  "overall_grade": "A",
  "subject_grades": [
    {
      "subject_name": "ENGLISH",
      "marks_obtained": 85,
      "grade": "A",
      "competence": "Excellent",
      "competence_level": "Grade A (Excellent)",
      "color": "#00AA7A",
      "points": 1,
      "is_excluded": false
    }
  ]
}
```

### In Blade Templates

```blade
@extends('layouts.app')

@section('content')
<div class="grading-report">
    <h2>{{ $report['candidate_name'] }}</h2>
    
    <div class="summary">
        <p>Total Marks: {{ $report['total_marks'] }}</p>
        <p>GPA: {{ number_format($report['gpa'], 2) }}</p>
        <p>Division: {{ $report['division']['division'] }}</p>
    </div>

    <table class="subjects">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Competence Level</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['subject_grades'] as $subject)
                <tr>
                    <td>{{ $subject['subject_name'] }}</td>
                    <td>{{ $subject['marks_obtained'] }}</td>
                    <td>{{ $subject['grade'] }}</td>
                    <td style="background-color: {{ $subject['color'] }}; padding: 8px; border-radius: 4px;">
                        {{ $subject['competence_level'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
```

### JavaScript/Vue.js

```javascript
// Get candidate grades
async function getCandidateGrades(candidateId) {
  const response = await fetch(
    `/api/grading/candidate/${candidateId}/grades?exam_type_id=1&year=2024`
  );
  const report = await response.json();
  
  console.log(report.gpa);           // 1.5
  console.log(report.division);      // { division: 'I', competence: 'Excellent' }
  console.log(report.overall_grade); // 'A'
  
  return report;
}

// Calculate grade for marks
async function calculateGrade(marks) {
  const response = await fetch('/api/grading/calculate-grade', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ marks })
  });
  const data = await response.json();
  
  return {
    grade: data.grade,
    competence: data.competence_level,
    color: data.color
  };
}

// Store grades
async function storeGrades(candidateId) {
  const response = await fetch(
    `/api/grading/candidate/${candidateId}/store-grades`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ exam_type_id: 1, year: 2024 })
    }
  );
  return await response.json();
}
```

## Workflow Example

### Scenario: Process ACSEE 2024 Grades

```bash
# 1. Batch process all candidates
POST /api/grading/batch-process
{
  "exam_type_id": 1,
  "year": 2024,
  "school_id": 5
}

# 2. Verify results
GET /api/grading/school/statistics?school_id=5&exam_type_id=1&year=2024

# 3. Publish grades
POST /api/grading/publish
{
  "exam_type_id": 1,
  "year": 2024
}

# 4. Lock grades
POST /api/grading/lock
{
  "exam_type_id": 1,
  "year": 2024
}
```

## Database Fields

### final_grades table (new/updated fields)

| Field | Type | Description |
|-------|------|-------------|
| gpa | float | Student's GPA (1.0-7.0) |
| division | string | Division classification (I, II, III, IV, O) |
| grading_breakdown | longtext/json | Complete grading report |

## File Locations

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Grading/NectaGradingController.php` | Controller for grading operations |
| `routes/api-grading.php` | API routes for grading endpoints |
| `resources/views/grading/candidate-results.blade.php` | View for displaying results |
| `database/migrations/2026_02_08_add_gpa_to_final_grades.php` | Migration for new fields |
| `app/Services/Results/NectaGradingService.php` | Core grading service |

## Testing the Integration

### Test 1: Get Grade Reference
```bash
curl http://localhost/api/grading/reference
```

### Test 2: Calculate Grade
```bash
curl -X POST http://localhost/api/grading/calculate-grade \
  -H "Content-Type: application/json" \
  -d '{"marks": 85}'
```

### Test 3: Get Candidate Grades
```bash
curl http://localhost/api/grading/candidate/1/grades?exam_type_id=1&year=2024
```

### Test 4: Store Grades
```bash
curl -X POST http://localhost/api/grading/candidate/1/store-grades \
  -H "Content-Type: application/json" \
  -d '{"exam_type_id": 1, "year": 2024}'
```

## Troubleshooting

### Issue: Controller not found
- Ensure the controller file is in `app/Http/Controllers/Grading/`
- Run: `composer dump-autoload`

### Issue: Routes not accessible
- Ensure routes are registered in `routes/api.php`
- Run: `php artisan route:clear`

### Issue: Migration errors
- Ensure table columns don't already exist
- Check migration file for syntax errors
- Run: `php artisan migrate --force`

### Issue: Service not injecting
- Ensure service is properly bound in container or use `new NectaGradingService()`
- Check service namespace is correct

## Security Notes

1. **Authentication**: All API endpoints use `auth:sanctum` middleware
2. **Authorization**: Consider adding role-based access control
3. **Data Validation**: All inputs are validated in controller
4. **Rate Limiting**: Consider adding rate limiting for batch operations
5. **Auditing**: All grade changes should be logged for compliance

## Performance Tips

1. Use batch processing for multiple candidates
2. Cache grade reference data
3. Index exam_type_id, year, and candidate_id in database
4. Use pagination for large result sets
5. Consider eager loading relationships

## Next Steps

1. ✅ Register API routes
2. ✅ Run migration
3. ✅ Update FinalGrade model
4. ✅ Test endpoints
5. Create Filament admin panel for grading management (optional)
6. Create reports/exports for grades
7. Set up scheduled jobs for batch grading
