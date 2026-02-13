# Results Display - What Each Column Shows

## Overview

The school results page displays marks and grades with proper averaging for multi-paper subjects. This document clarifies exactly what values are shown in each column.

## Results Table Columns

### DETAILED SUBJECTS RESULT Column

This column shows the final subject score for each subject, which is:
- **For multi-paper subjects**: The average of all papers/components
- **For single-paper subjects**: The mark as-is

**Format**: `SUBJECT=MARK 'GRADE'`

**Example**:
```
PHYSICS=52.00 'D', CHEMISTRY=49.00 'D', BIOLOGY=56.33 'C', ENGLISH LANGUAGE=65.50 'B'
```

### How Marks Are Calculated in This Column

#### Multi-Paper Subject Example (Chemistry with 3 papers):
```
Paper 1: 45 marks
Paper 2: 50 marks
Paper 3: 52 marks

Formula: (45 + 50 + 52) / 3 = 49.00 marks

Display: CHEMISTRY=49.00 'D'
         ↑
         This 49.00 is the AVERAGED mark
```

#### Single-Paper Subject Example (English Language):
```
Paper 1: 65.5 marks

Formula: 65.5 / 1 = 65.5 marks (no averaging)

Display: ENGLISH LANGUAGE=65.5 'B'
         ↑
         This 65.5 is the mark as-is
```

### TOTAL Column

**What it shows**: Sum of all DETAILED subject marks

**Formula**:
```
TOTAL = CHEMISTRY + BIOLOGY + PHYSICS + ... (all subjects)
TOTAL = 49.00 + 56.33 + 52.00 + 65.50 + ...
```

**Note**: This TOTAL includes all subjects (including GENERAL STUDIES, BASIC APPLIED MATHEMATICS if taken)

### AVG Column

**What it shows**: Average mark per subject

**Formula**:
```
AVG = TOTAL / Number of Subjects
AVG = (49.00 + 56.33 + 52.00 + 65.50 + ...) / 5
```

### GRD Column

**What it shows**: Overall grade (best grade across all subjects)

**Calculation**: Grade of the highest-scoring subject

### PTS Column

**What it shows**: Total grade points (for ranking and division calculation)

**Formula**:
```
For each subject:
  - Get the grade (A, B, C, D, E, S, F)
  - Convert to points (A=1, B=2, C=3, D=4, E=5, S=6, F=7)
  - Add to total (excluding GENERAL STUDIES and BASIC APPLIED MATHEMATICS)

PTS = Sum of all grade points (excluding excluded subjects)
```

### DIV Column

**What it shows**: Final division (I, II, III, IV, or 0)

**Calculation**: Based on total points

```
Division I:   Points 3-9 (Excellent)
Division II:  Points 10-12 (Very Good)
Division III: Points 13-17 (Good)
Division IV:  Points 18-19 (Average)
Division 0:   Points 20-21 (Fail)
```

### GPA Column

**What it shows**: Grade Point Average

**Formula**:
```
GPA = Total Points / Number of Valid Subjects
(Valid subjects = all subjects except GENERAL STUDIES and BASIC APPLIED MATHEMATICS)
```

**Range**: 0.0 to 7.0 (lower is better in Tanzania NECTA system)
- GPA 3.0-9.0 = Division I (Excellent)
- GPA 10.0-12.0 = Division II (Very Good)
- GPA 13.0-17.0 = Division III (Good)
- GPA 18.0-19.0 = Division IV (Average)
- GPA 20.0-21.0 = Division 0 (Fail)

### POS Column

**What it shows**: Position/Ranking

**Calculation**: 
1. First, candidates are sorted by status (COMPLETE → INCOMPLETE → ABSENT)
2. Within each status, sorted by Division (I → II → III → IV → 0)
3. Within each division, sorted by GPA descending (best first)
4. POS = their position in this sorted list

## Important Notes

### What Is NOT Shown
- Individual paper marks are NOT displayed in the detailed results
- Only the final averaged mark per subject is shown
- This is intentional - we show final subject scores, not intermediate papers

### Why We Average Papers
- Multi-paper subjects reflect the student's performance across multiple assessments
- Averaging gives a fair final score
- Follows NECTA standards
- Applied immediately during mark import

### Excluded Subjects
The following subjects are excluded from GPA and total points calculations (but included in TOTAL):
- GENERAL STUDIES
- BASIC APPLIED MATHEMATICS

They are included in:
- TOTAL column ✓
- AVG column ✓

But excluded from:
- PTS column ✗
- GPA column ✗
- Division calculation ✗

## Examples

### Example 1: Complete Candidate with Multi-Paper Subjects

```
CNO: S1234-5001
SEX: F
COMBINATION: PCB

DETAILED SUBJECTS RESULT:
  PHYSICS=52.00 'D'
  CHEMISTRY=49.00 'D'
  BIOLOGY=56.33 'C'
  ENGLISH LANGUAGE=65.50 'B'
  KISWAHILI=70.00 'B'

TOTAL: 292.83 (sum of all marks)
AVG: 58.57 (292.83 / 5)
GRD: B (best grade)
PTS: 13 (B=2, D=4, D=4, C=3)
DIV: III (points 13-17)
GPA: 3.25
POS: 45 (ranked 45th in school)
```

### Example 2: Absent Candidate

```
CNO: S1234-5002
SEX: M

DETAILED SUBJECTS RESULT: ABS
TOTAL: ABS
AVG: ABS
GRD: ABS
PTS: ABS
DIV: ABS
GPA: ABS
POS: [At bottom of list]
```

### Example 3: Incomplete Candidate

```
CNO: S1234-5003
SEX: M

DETAILED SUBJECTS RESULT: INC (has some marks, but not all subjects)
TOTAL: INC
AVG: INC
GRD: INC
PTS: INC
DIV: INC
GPA: INC
POS: [In middle of list, between complete and absent]
```

## Database Storage

### What's Stored for Each Subject Mark

```
subject_marks table:
  candidate_id: ID of student
  subject_id: ID of subject
  paper_1: First paper mark (if applicable)
  paper_2: Second paper/practical mark (if applicable)
  paper_3: Third paper/project mark (if applicable)
  marks_obtained: FINAL AVERAGED MARK (what's displayed in results)
  grade: Letter grade (A-F, S)
  percentage: Percentage score (if different from marks)
  exam_type_id: ACSEE, etc.
  year: Academic year
```

### What's Pre-Calculated

```
candidate_exam_registrations table:
  total_marks: Sum of all marks_obtained values
  total_points: Sum of all grade points
  gpa: Calculated GPA
  division: Calculated division
  grade: Overall grade
```

These values are calculated once at import time and stored for fast retrieval.

## Verification

To verify the marks displayed are correct:

1. **Check TOTAL** = Sum of all detailed subject marks
2. **Check AVG** = TOTAL ÷ 5 (assuming 5 subjects)
3. **Check GPA** = Total Points ÷ Valid Subjects
4. **Check DIV** = Based on GPA range

Example:
```
DETAILED: 49.00 + 56.33 + 52.00 + 65.50 + 70.00 = 292.83
TOTAL shown: 292.83 ✓

TOTAL: 292.83 ÷ 5 = 58.566
AVG shown: 58.57 ✓
```

## Summary

| Column | Shows | Based On | Notes |
|--------|-------|----------|-------|
| DETAILED | Final subject mark | marks_obtained (averaged if multi-paper) | Includes all subjects |
| TOTAL | Sum of marks | All detailed marks | Includes all subjects |
| AVG | Average mark | TOTAL ÷ subjects | Includes all subjects |
| GRD | Best grade | Highest grade | Overall grade |
| PTS | Total points | Grade points sum | Excludes 2 subjects |
| DIV | Final division | Points range | Based on points |
| GPA | Average grade points | Points ÷ valid subjects | Excludes 2 subjects |
| POS | Ranking | Status + Division + GPA | COMPLETE first, ABS last |

---

**Status**: ✓ Marks are properly averaged in the DETAILED SUBJECTS RESULT column
