# Average Marks Calculation - FINAL FIX

**Date**: February 9, 2026  
**Status**: ✓ FIXED - Always divide by number of papers

## Clarification Received

You stated: **"Subjects with 2 papers and 3 papers calculate the averages... What is considered is the number of papers given if their sum is 20 find their average and present as the final marks"**

## Implementation

**Simple Rule**: 
```
IF subject has multiple papers:
    average = marks_obtained ÷ number_of_papers
ELSE:
    average = marks_obtained
```

**No checking value > 100. Always divide based on paper count.**

## Files Updated

### `/resources/views/hierarchy/school-results.blade.php`

**Section 1: Building Subject Results (lines 144-172)**
```php
// Determine number of papers
$totalPapers = ($subject->written_papers ?? 1) + 
              ($subject->has_practical ? 1 : 0) + 
              ($subject->has_project ? 1 : 0);

// Calculate average: always divide if multiple papers
$average = $totalPapers > 1 
    ? round($mark->marks_obtained / $totalPapers, 2)
    : $mark->marks_obtained;

// Display as: SUBJECT=AVERAGE 'GRADE'
return $name . '=' . $average . " '" . $grade . "'";
```

**Section 2: Calculating Total (lines 195-219)**
```php
// Same logic for each subject
$totalPapers = ($selection->subject->written_papers ?? 1) + 
              ($selection->subject->has_practical ? 1 : 0) + 
              ($selection->subject->has_project ? 1 : 0);

$average = $totalPapers > 1 
    ? round($mark->marks_obtained / $totalPapers, 2)
    : $mark->marks_obtained;

$calculatedTotalMarks += $average;
```

## Examples

### 3-Paper Subject (Chemistry)

```
Papers: 3
Sum of marks: 115
Average: 115 ÷ 3 = 38.33
Display: CHEMISTRY=38.33 'D'
```

```
Papers: 3
Sum of marks: 20 (e.g., 5+8+7)
Average: 20 ÷ 3 = 6.67
Display: CHEMISTRY=6.67 'F'
```

### 2-Paper Subject (English)

```
Papers: 2
Sum of marks: 130
Average: 130 ÷ 2 = 65.00
Display: ENGLISH LANGUAGE=65.00 'B'
```

```
Papers: 2
Sum of marks: 50
Average: 50 ÷ 2 = 25.00
Display: ENGLISH LANGUAGE=25.00 'F'
```

### 1-Paper Subject (Kiswahili)

```
Papers: 1
Marks: 75
Average: 75 ÷ 1 = 75.00
Display: KISWAHILI=75 'B'
```

## Verification Table

| Subject | Papers | marks_obtained | Calculation | Average | Display |
|---------|--------|----------------|-------------|---------|---------|
| Chemistry | 3 | 115 | 115÷3 | 38.33 | CHEMISTRY=38.33 'D' |
| Chemistry | 3 | 20 | 20÷3 | 6.67 | CHEMISTRY=6.67 'F' |
| English | 2 | 130 | 130÷2 | 65.00 | ENGLISH=65.00 'B' |
| English | 2 | 50 | 50÷2 | 25.00 | ENGLISH=25.00 'F' |
| Kiswahili | 1 | 75 | 75÷1 | 75 | KISWAHILI=75 'B' |

## What Gets Displayed

### DETAILED SUBJECTS RESULT
```
GENERAL STUDIES=56.00 'D'
CHEMISTRY=38.33 'D'      ← Average of 3 papers
BIOLOGY=46.50 'C'        ← Average of 2 papers (or 3 papers)
BASIC APPLIED MATH=25.00 'F'  ← Average of 2 papers
EDUCATION=62.00 'C'      ← Single paper (no division)
```

### TOTAL
```
Sum of all averages:
56.00 + 38.33 + 46.50 + 25.00 + 62.00 = 227.83
```

### AVG
```
Average per subject:
227.83 ÷ 5 = 45.57
```

## Key Points

✓ **Number of papers determines division count** (not the sum value)

✓ **Always divide if papers > 1** (regardless if sum is 20, 50, 115, etc.)

✓ **Always use the true mathematical average** (sum ÷ count)

✓ **Single-paper subjects are never divided**

✓ **Result is the final mark for that subject**

## Logic Summary

```
For each subject:
1. Count total papers: written_papers + practical + project
2. If count > 1: divide marks by count
3. If count = 1: use marks as-is
4. Display as: SUBJECT=AVERAGE 'GRADE'
5. Sum all averages for TOTAL
6. Divide TOTAL by subject count for AVG
```

## Status

✓ Logic corrected: Always divide by paper count
✓ Handles any sum value (20, 50, 100, 115, 130, etc.)
✓ Calculates true mathematical average
✓ Applies to all subjects automatically
✓ Tested and verified
✓ Ready for production

The system now **always calculates and displays the true average** of all papers for each subject.
