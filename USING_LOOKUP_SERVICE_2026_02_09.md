# Using Lookup Service for Average Marks & Grades

**Date**: February 9, 2026  
**Status**: ✓ IMPLEMENTED

## Why Use a Lookup Service?

Instead of writing calculation logic directly in the view, we use a dedicated service:

### Benefits
1. **Reusable**: Use the same logic in multiple places (views, commands, APIs)
2. **Testable**: Easy to unit test the logic
3. **Maintainable**: Change logic in one place, applies everywhere
4. **Clean**: View is cleaner and easier to read
5. **Centralized**: All average/grade logic in one service

## The AverageMarksService

**File**: `/app/Services/Results/AverageMarksService.php`

### Main Methods

#### 1. `calculateAverage(float $marksObtained, Subject $subject): float`

Calculates the average mark for a subject.

```php
// Chemistry: 3 papers, marks_obtained = 115
$average = $service->calculateAverage(115, $chemistry);
// Returns: 38.33

// English: 2 papers, marks_obtained = 130
$average = $service->calculateAverage(130, $english);
// Returns: 65.00

// Kiswahili: 1 paper, marks_obtained = 75
$average = $service->calculateAverage(75, $kiswahili);
// Returns: 75.00
```

#### 2. `getGradeForAverage(float $average): string`

Gets the grade for an average mark.

```php
$grade = $service->getGradeForAverage(38.33);
// Returns: 'F'

$grade = $service->getGradeForAverage(67);
// Returns: 'C'
```

#### 3. `getAverageAndGrade(SubjectMarks $mark, Subject $subject): array` ⭐ **MAIN LOOKUP FUNCTION**

Gets both average and grade in one call. **This is the main function to use!**

```php
$result = $service->getAverageAndGrade($mark, $subject);
// Returns:
// [
//     'average' => 38.33,
//     'grade' => 'F'
// ]

// Use it:
$average = $result['average'];  // 38.33
$grade = $result['grade'];      // 'F'
```

#### 4. `calculateTotalFromAverages($subjectMarks, array $subjectConfigs): float`

Calculates total from all subject averages.

```php
$total = $service->calculateTotalFromAverages(
    $candidateMarks,  // Collection of SubjectMarks
    $subjectConfigs   // Array of subjects keyed by subject_id
);
// Returns: 225.50 (sum of all averages)
```

## Usage in View

### Before (Inline Calculation - BAD)
```php
// Lots of code for each calculation
$totalPapers = ($subject->written_papers ?? 1) + 
              ($subject->has_practical ? 1 : 0) + 
              ($subject->has_project ? 1 : 0);

$average = $totalPapers > 1 
    ? round($mark->marks_obtained / $totalPapers, 2)
    : $mark->marks_obtained;

$grade = $gradingService->calculateGrade($average);

return $name . '=' . $average . " '" . $grade . "'";
```

### After (Using Lookup Service - GOOD)
```php
// Single line using lookup service
$result = $averageMarksService->getAverageAndGrade($mark, $subject);
$average = $result['average'];
$grade = $result['grade'];

return $name . '=' . $average . " '" . $grade . "'";
```

## Implementation in View

### 1. Instantiate the Service
```php
@php
use App\Services\Results\AverageMarksService;
use App\Services\Results\NectaGradingService;

$gradingService = new NectaGradingService();
$averageMarksService = new AverageMarksService($gradingService);
@endphp
```

### 2. Use for Subject Results
```php
$subjectResults = $subjectSelections->map(function($selection) use ($candidateMarks, $averageMarksService) {
    $mark = $candidateMarks->get($selection->subject_id);
    $subject = $selection->subject;
    $name = $subject?->name ?? '-';
    
    if (!$mark || $mark->marks_obtained === null) {
        return $name . '= -';
    }
    
    // Use lookup function
    $result = $averageMarksService->getAverageAndGrade($mark, $subject);
    $average = $result['average'];
    $grade = $result['grade'];
    
    return $name . '=' . $average . " '" . $grade . "'";
})->join(', ');
```

### 3. Use for Total Marks
```php
$subjectConfigsForTotal = [];
foreach ($subjectSelections as $selection) {
    $subjectConfigsForTotal[$selection->subject_id] = $selection->subject;
}

$calculatedTotalMarks = $averageMarksService->calculateTotalFromAverages(
    $candidateMarks,
    $subjectConfigsForTotal
);

$totalMarks = $calculatedTotalMarks > 0 ? $calculatedTotalMarks : ($registration?->total_marks ?? 0);
```

## Using in Other Places

Since the logic is now in a service, you can use it anywhere:

### In a Controller
```php
$averageMarksService = new AverageMarksService($gradingService);

foreach ($subjects as $subject) {
    $average = $averageMarksService->calculateAverage($mark->marks_obtained, $subject);
    // Use $average...
}
```

### In a Command
```php
public function handle()
{
    $averageMarksService = new AverageMarksService($gradingService);
    
    foreach ($marks as $mark) {
        $result = $averageMarksService->getAverageAndGrade($mark, $mark->subject);
        // Process...
    }
}
```

### In an API Response
```php
$result = $averageMarksService->getAverageAndGrade($mark, $subject);
return response()->json([
    'subject' => $subject->name,
    'average' => $result['average'],
    'grade' => $result['grade'],
]);
```

## Benefits of This Approach

### Separation of Concerns
- **Service**: Handles calculation logic
- **View**: Only handles display logic
- **Controller**: Handles request/response

### Single Responsibility
- Service has one job: calculate averages and grades
- View has one job: display results
- Easy to understand and maintain

### Testable
```php
// Easy to test
$service = new AverageMarksService($gradingService);
$result = $service->getAverageAndGrade($mark, $subject);
$this->assertEquals(38.33, $result['average']);
$this->assertEquals('F', $result['grade']);
```

### Reusable
Can use the same service in:
- Views
- Controllers
- Commands
- APIs
- Reports
- Exports
- Any other component

## File Structure

```
app/
├── Services/
│   └── Results/
│       ├── AverageMarksService.php      ← NEW: Lookup service
│       ├── NectaGradingService.php      ← Existing: Grade boundaries
│       ├── GradeCalculationService.php  ← Existing: Bulk calculation
│       └── ResultsExportService.php     ← Existing: Export logic
└── ...

resources/
└── views/
    └── hierarchy/
        └── school-results.blade.php     ← Updated: Now uses service
```

## Summary

Instead of writing calculation logic inline in the view:
```php
// Don't do this
$totalPapers = ...
$average = ...
$grade = ...
```

Use a lookup service:
```php
// Do this
$result = $averageMarksService->getAverageAndGrade($mark, $subject);
$average = $result['average'];
$grade = $result['grade'];
```

**Benefits**:
✓ Cleaner code
✓ Reusable logic
✓ Easier to test
✓ Easier to maintain
✓ Can use in multiple places
✓ Follows best practices

## Status

✓ AverageMarksService created
✓ View updated to use lookup functions
✓ Code is cleaner and more maintainable
✓ Ready for use in other components
