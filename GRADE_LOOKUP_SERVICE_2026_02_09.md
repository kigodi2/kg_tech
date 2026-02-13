# Grade Lookup Service - Complete Solution

**Date**: February 9, 2026  
**Status**: ✓ IMPLEMENTED

## Overview

Created `GradeLookupService` for centralized lookup of all grade-related information:
- Grade from marks
- Competence level from grade
- Grade points from grade
- Division from points
- GPA calculation
- Color codes
- Division formatting

## The Service

**File**: `/app/Services/Results/GradeLookupService.php`

### Main Lookup Functions

#### 1. `getGradeFromMarks(float $marks): string`
Get letter grade from mark value.

```php
$grade = $gradeLookupService->getGradeFromMarks(85);
// Returns: 'A'

$grade = $gradeLookupService->getGradeFromMarks(45);
// Returns: 'E'
```

#### 2. `getCompetenceLevel(string $grade): string`
Get competence description from grade.

```php
$competence = $gradeLookupService->getCompetenceLevel('A');
// Returns: 'Excellent'

$competence = $gradeLookupService->getCompetenceLevel('F');
// Returns: 'Fail'
```

#### 3. `getGradePoints(string $grade): int`
Get points value from grade (for GPA calculation).

```php
$points = $gradeLookupService->getGradePoints('A');
// Returns: 1

$points = $gradeLookupService->getGradePoints('F');
// Returns: 7
```

#### 4. `getDivisionFromPoints(int $totalPoints): array` ⭐
Get division information from total points.

```php
$division = $gradeLookupService->getDivisionFromPoints(8);
// Returns:
// [
//     'division' => 1,
//     'name' => 'I',
//     'competence' => 'Excellent'
// ]

$division = $gradeLookupService->getDivisionFromPoints(15);
// Returns:
// [
//     'division' => 3,
//     'name' => 'III',
//     'competence' => 'Good'
// ]
```

#### 5. `getGPA(int $totalPoints, int $subjectCount): float`
Calculate GPA from points and subject count.

```php
$gpa = $gradeLookupService->getGPA(10, 5);
// Returns: 2.0

$gpa = $gradeLookupService->getGPA(8, 5);
// Returns: 1.6
```

#### 6. `getCompleteGradeInfo(...): array`
Get all grade information in one call.

```php
$info = $gradeLookupService->getCompleteGradeInfo(
    $marks,        // float (0-100)
    $totalPoints,  // int (sum of all grade points)
    $subjectCount  // int (number of subjects)
);

// Returns:
// [
//     'grade' => 'A',
//     'competence' => 'Excellent',
//     'points' => 1,
//     'color' => '#00AA7A',
//     'gpa' => 1.6,
//     'division' => 1,
//     'divisionName' => 'I',
//     'divisionCompetence' => 'Excellent'
// ]
```

#### 7. `formatDivision($division): string`
Format division number to display string.

```php
$display = $gradeLookupService->formatDivision(1);
// Returns: 'I'

$display = $gradeLookupService->formatDivision(3);
// Returns: 'III'
```

#### 8. `getGradeColor(string $grade): string`
Get hex color code for grade.

```php
$color = $gradeLookupService->getGradeColor('A');
// Returns: '#00AA7A'
```

#### 9. `isExcludedSubject(string $subjectName): bool`
Check if subject is excluded from GPA/points.

```php
$excluded = $gradeLookupService->isExcludedSubject('GENERAL STUDIES');
// Returns: true
```

## Usage in View

### Before (Inline)
```php
// Lots of if/else logic
if (is_numeric($division)) {
    $divisionMap = [
        1 => 'I',
        2 => 'II', 
        3 => 'III',
        4 => 'IV',
        0 => '0'
    ];
    $division = $divisionMap[$division] ?? '-';
}

// More logic for competence...
$competence = match(true) {
    $gpa >= 3.5 => 'GRADE A (EXCELLENT)',
    $gpa >= 3.0 => 'GRADE B (GOOD)',
    // ... more conditions
};
```

### After (Using Lookup Service)
```php
// Single lookup call
$division = $gradeLookupService->formatDivision($division);
$competence = $gradeLookupService->getCompetenceLevel($grade);
$color = $gradeLookupService->getGradeColor($grade);
```

## Integration with Other Services

### With AverageMarksService
```php
// Get average and grade
$result = $averageMarksService->getAverageAndGrade($mark, $subject);
$average = $result['average'];
$grade = $result['grade'];

// Get competence level
$competence = $gradeLookupService->getCompetenceLevel($grade);
$color = $gradeLookupService->getGradeColor($grade);
$points = $gradeLookupService->getGradePoints($grade);
```

### In Controllers
```php
$gradeLookupService = new GradeLookupService($gradingService);

foreach ($marks as $mark) {
    $grade = $gradeLookupService->getGradeFromMarks($mark->marks_obtained);
    $competence = $gradeLookupService->getCompetenceLevel($grade);
    
    // Use in response...
}
```

### In Commands
```php
$division = $gradeLookupService->getDivisionFromPoints($totalPoints);
echo "Division: {$division['name']} ({$division['competence']})";
```

## Service Architecture

```
GradeLookupService
├── getGradeFromMarks()         → Grade letter
├── getCompetenceLevel()        → Competence text
├── getGradePoints()            → Points (1-7)
├── getDivisionFromPoints()     → Division array
├── getGPA()                    → GPA number
├── getGradeColor()             → Hex color
├── formatDivision()            → Display string
├── isExcludedSubject()         → Boolean
└── getCompleteGradeInfo()      → Complete info array
    └── All above combined
```

## Benefits

✓ **Centralized**: All grade lookups in one place
✓ **Reusable**: Use across views, controllers, commands, APIs
✓ **Consistent**: Same logic everywhere
✓ **Maintainable**: Change logic in one place
✓ **Testable**: Easy to unit test
✓ **Clean Code**: No inline logic in views
✓ **Type-Safe**: Clear parameter and return types
✓ **Self-Documenting**: Method names explain purpose

## Related Services

### AverageMarksService
For calculating averages and getting average+grade combo:
- `calculateAverage()`
- `getGradeForAverage()`
- `getAverageAndGrade()` ← Main lookup
- `calculateTotalFromAverages()`

### NectaGradingService (Existing)
For NECTA grading rules and boundaries.

### GradeLookupService (New)
For easy lookups of grade-related information.

## Complete Example

```php
// In view:
$gradeLookupService = new GradeLookupService($gradingService);

// For each candidate mark:
$result = $averageMarksService->getAverageAndGrade($mark, $subject);
$average = $result['average'];
$grade = $result['grade'];

// Lookup additional info:
$competence = $gradeLookupService->getCompetenceLevel($grade);
$points = $gradeLookupService->getGradePoints($grade);
$color = $gradeLookupService->getGradeColor($grade);

// Display:
echo "{$subject} = {$average} '{$grade}' ({$competence})";
```

## Status

✓ Service created with 9 lookup methods
✓ Integrated with view
✓ Follows best practices
✓ Fully documented
✓ Ready for use everywhere in system
