# Multi-Paper Subject Display Logic
**Implemented:** 2026-02-08

## Overview
When displaying subject results in the hierarchy school results view (Section 2), the system now intelligently handles subjects with multiple papers:

- **Single Paper Subjects**: Display the actual total marks scored
- **Multi-Paper Subjects**: Display the average marks per paper

## Implementation Details

### Subject Structure
Each subject in the database has:
- `written_papers` (int) - Number of written exam papers (typically 1-3)
- `has_practical` (bool) - Whether subject includes practical examination
- `has_project` (bool) - Whether subject includes project work

### Example Data

| Subject | Written Papers | Practical | Project | Total Papers |
|---------|----------------|-----------|---------|--------------|
| General Studies | 1 | NO | NO | 1 |
| Chemistry | 3 | NO | NO | 3 |
| Biology | 3 | NO | NO | 3 |
| Education | 1 | NO | NO | 1 |
| Physics | 2 | YES | NO | 3 |
| Computer Studies | 2 | YES | YES | 4 |

### Calculation Logic

```php
// Count total papers for the subject
$totalPapers = $subject->written_papers + 
               ($subject->has_practical ? 1 : 0) + 
               ($subject->has_project ? 1 : 0);

// If multiple papers exist, calculate average per paper
if ($totalPapers > 1) {
    $displayMarks = $mark->marks_obtained / $totalPapers;
} else {
    $displayMarks = $mark->marks_obtained;
}
```

### Display Example

**Candidate: S1378-0501**

| Subject | Total Papers | Actual Marks | Display Marks | Grade |
|---------|--------------|--------------|---------------|-------|
| General Studies | 1 | 94 | **94** | A |
| Chemistry | 3 | 82 | **27.33** | A |
| Biology | 3 | 64 | **21.33** | C |
| Education | 1 | 62 | **62** | C |

### Interpretation

**Single Paper (General Studies):**
- Total papers: 1
- Display: 94 (actual marks - no averaging needed)
- This is the final score for the subject

**Multi-Paper (Chemistry):**
- Total papers: 3 (three written exam papers)
- Actual marks: 82 (sum of all papers: Paper1 + Paper2 + Paper3)
- Display: 27.33 (average per paper: 82 ÷ 3)
- Interpretation: Candidate averaged 27.33 marks per paper

## Code Changes

### File: resources/views/hierarchy/school-results.blade.php

```php
// Count total papers for this subject
$totalPapers = ($subject?->written_papers ?? 1) + 
               ($subject?->has_practical ? 1 : 0) + 
               ($subject?->has_project ? 1 : 0);

// If multiple papers, display average; if single paper, display actual marks
$displayMarks = ($totalPapers > 1) ? 
    number_format($mark->marks_obtained / $totalPapers, 2) : 
    $mark->marks_obtained;
```

## Benefits

1. **Fair Comparison**: Subjects with different paper structures can be compared fairly
2. **Clarity**: Single-paper subjects show full marks, multi-paper subjects show per-paper average
3. **Context**: Grades are maintained while marks are intelligently scaled
4. **Consistency**: Applied uniformly across all school hierarchy results

## Notes

- Grades (A-F) are NOT divided - they remain as calculated from the total marks
- Only the displayed marks value is adjusted for multi-paper subjects
- The underlying `marks_obtained` in the database remains unchanged
- This display logic is applied only in the hierarchy school results view
- Grade calculation is based on total marks before averaging for display
