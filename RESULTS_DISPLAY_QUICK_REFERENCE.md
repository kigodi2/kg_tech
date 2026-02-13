# Results Display Quick Reference
**Status:** ✅ FULLY IMPLEMENTED - 2026-02-08

## Section 2: Detailed Results Display Rules

### How Marks Are Displayed

#### Single-Paper Subjects
```
Display Mark = Actual Mark
Example: General Studies (1 paper) with 94 marks → Display 94
```

#### Multi-Paper Subjects
```
Display Mark = Actual Mark ÷ Number of Papers
Example: Chemistry (3 papers) with 82 marks → Display 82÷3 = 27.33
```

### Subject Paper Structure

**Formula for Total Papers:**
```
Total Papers = Written Papers + Practical (if yes) + Project (if yes)
```

**Common Subject Types:**

| Type | Written | Practical | Project | Total |
|------|---------|-----------|---------|-------|
| Theory Only | 1-3 | NO | NO | 1-3 |
| Practical Subject | 2 | YES | NO | 3 |
| Project-Based | 2 | YES | YES | 4 |

### What Gets Displayed in Section 2

For each candidate and subject:

| Column | Value |
|--------|-------|
| CNO | Candidate number |
| SEX | M/F |
| COMB | Combination code (e.g., CBE) |
| DETAILED SUBJECTS | Subject=Marks 'Grade' (formatted) |
| TOTAL | Sum of all subject marks |
| AVG | Average marks per subject |
| GRD | Overall grade (A-F) |
| PTS | Points (if applicable) |
| DIV | Division (I, II, III, IV, or 0) |
| GPA | GPA score |
| POS | Position/Rank |

### Example Display Interpretation

**Candidate S1378-0501:**
```
DETAILED SUBJECTS: GENERAL STUDIES=94 'A', CHEMISTRY=27.33 'A', BIOLOGY=21.33 'C', EDUCATION=62 'C'
TOTAL: 384  (94 + 82 + 64 + 62 = actual sum)
AVG: 76.80  (384 ÷ 5 subjects = 76.8, but displayed is per subject average)
```

**Read as:**
- General Studies: 1 paper, scored 94/100, Grade A
- Chemistry: 3 papers, averaged 27.33/paper, Grade A
- Biology: 3 papers, averaged 21.33/paper, Grade C
- Education: 1 paper, scored 62/100, Grade C

### Important Notes

1. **Grades are NOT averaged** - They reflect the overall performance based on total marks
2. **Only display marks are adjusted** - The underlying marks_obtained remains unchanged
3. **TOTAL column** - Shows sum of ACTUAL marks (not displayed marks)
4. **AVG column** - Shows average of ACTUAL marks per subject
5. **Multi-paper averaging** - Only affects the DETAILED SUBJECTS display format

### How It Works Behind the Scenes

```php
// Fetch subject structure
$totalPapers = $subject->written_papers + 
               ($subject->has_practical ? 1 : 0) + 
               ($subject->has_project ? 1 : 0);

// Determine display value
if ($totalPapers > 1) {
    // Multi-paper: show average per paper
    $display = $mark->marks_obtained / $totalPapers;
} else {
    // Single paper: show actual marks
    $display = $mark->marks_obtained;
}

// Grade and total calculations always use actual marks
$grade = calculateGrade($mark->marks_obtained);
$total = sum($allMarksobtained);
```

## Troubleshooting

### Q: Why does Chemistry show 27.33 but the grade is A?
**A:** Chemistry has 3 papers (total 82 marks). The display shows 27.33 (average per paper), but the grade is calculated from the actual 82 marks.

### Q: Why is TOTAL different from DETAILED SUBJECTS sum?
**A:** DETAILED SUBJECTS shows averaged marks for multi-paper subjects, while TOTAL uses actual marks.

### Q: Can I see individual paper marks?
**A:** Currently, the system shows aggregated subject marks. Individual paper marks are stored in the raw_marks table during import but displayed as aggregates in results.

### Q: Are the displayed marks used for ranking?
**A:** No. Ranking and division are calculated from ACTUAL marks, not the displayed values.

## Files Involved

- `resources/views/hierarchy/school-results.blade.php` - Display logic
- `app/Models/Subject.php` - Paper structure metadata
- `app/Models/SubjectMarks.php` - Actual marks storage
- `HierarchyController.php` - Data aggregation for view
