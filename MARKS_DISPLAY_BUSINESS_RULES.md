# Marks Display Business Rules
**Version:** 1.0  
**Effective:** 2026-02-08

## Core Rule

> **"Subjects with multiple papers display average marks per paper; subjects with single paper display actual marks."**

---

## Detailed Rules

### Rule 1: Paper Count Determination
```
Total Papers = Written Papers + Practical (if applicable) + Project (if applicable)

Where:
- Written Papers = 1, 2, or 3 (defined per subject)
- Practical = 1 point if subject has practical component
- Project = 1 point if subject has project component
```

**Examples:**
- Math (2 written papers, no practical) = 2 papers
- Physics (3 written papers, 1 practical) = 4 papers
- Computer Studies (2 written, 1 practical, 1 project) = 4 papers

### Rule 2: Display Calculation
```
IF Total Papers = 1:
    Display Value = Actual Marks (unchanged)
    
ELSE IF Total Papers > 1:
    Display Value = Actual Marks ÷ Total Papers (rounded to 2 decimals)
    
ELSE:
    Display Value = "-" (no marks)
```

### Rule 3: Grade Determination
```
Grade is ALWAYS calculated from Actual Marks, NOT from Display Value
- Grade = Grade Boundary(Actual Marks)
- Display Value is only for presentation, not grading
```

### Rule 4: Ranking & Division
```
Ranking, Division, GPA are calculated using:
- Actual Marks (not display value)
- Subject Grades (A-F)
- Grading Profile boundaries
```

### Rule 5: Section 2 Display Format
```
For each subject in results:
    Display: Subject Name = Display Marks 'Grade'
    
Example:
    GENERAL STUDIES = 94 'A'          (1 paper, actual marks)
    CHEMISTRY = 27.33 'A'             (3 papers, average marks)
    BIOLOGY = 21.33 'C'               (3 papers, average marks)
```

### Rule 6: Total and Average Calculations
```
TOTAL = Sum of ALL Actual Marks (not display values)
AVERAGE = TOTAL ÷ Number of Subjects

Example: 
    Subjects: Math(2 papers, 85), English(1 paper, 92)
    TOTAL = 85 + 92 = 177
    AVERAGE = 177 ÷ 2 = 88.5
    
    Display: Math = 42.5 (85÷2), English = 92 (85÷1)
    But TOTAL shows 177 and AVERAGE shows 88.5
```

---

## Subject Paper Configuration

### Standard ACSEE Subjects

| Subject | Papers | Practical | Project | Display Logic |
|---------|--------|-----------|---------|----------------|
| General Studies | 1 | NO | NO | Show actual |
| English | 1 | NO | NO | Show actual |
| Mathematics | 2 | NO | NO | Show average |
| Physics | 3 | YES | NO | Show average ÷4 |
| Chemistry | 3 | NO | NO | Show average ÷3 |
| Biology | 3 | NO | NO | Show average ÷3 |
| Geography | 2 | NO | NO | Show average ÷2 |
| History | 1 | NO | NO | Show actual |
| Computer Studies | 2 | YES | YES | Show average ÷4 |
| Fine Art | 1 | NO | YES | Show average ÷2 |

---

## Practical Examples

### Example 1: Single-Paper Subject
```
Subject: History
Papers: 1 written
Actual Marks: 78
Total Papers: 1

Calculation: 78 ÷ 1 = 78
Display: 78
Grade: B (based on 78)
```

### Example 2: Multi-Paper Subject
```
Subject: Chemistry
Papers: 3 written
Actual Marks: 82 (82 = Paper1:28 + Paper2:27 + Paper3:27)
Total Papers: 3

Calculation: 82 ÷ 3 = 27.33
Display: 27.33 (per paper average)
Grade: A (based on actual 82)
```

### Example 3: Subject with Practical
```
Subject: Physics
Papers: 3 written + 1 practical
Actual Marks: 76 (maybe 25+25+24+2 for practical)
Total Papers: 4

Calculation: 76 ÷ 4 = 19
Display: 19 (average per component)
Grade: C (based on actual 76)
```

### Example 4: Complete Result Display
```
Candidate: S1378-0501
Subjects:
  - History (1 paper): Actual=85, Display=85, Grade=A
  - Chemistry (3 papers): Actual=82, Display=27.33, Grade=A
  - Physics (4 papers): Actual=76, Display=19, Grade=C

SECTION 2 DISPLAY:
CNO: S1378-0501
SEX: M
COMB: CBE
DETAILED SUBJECTS: HISTORY=85 'A', CHEMISTRY=27.33 'A', PHYSICS=19 'C'
TOTAL: 243 (85+82+76 using actual marks)
AVG: 81 (243÷3)
GRD: A (based on overall performance)
DIV: I (based on overall GPA)

INTERPRETATION:
- History: Full 85/100 marks
- Chemistry: Averaged 27.33 per paper (out of 100 each)
- Physics: Averaged 19 per component (out of 100 each)
- Overall Division: Grade I based on actual total
```

---

## Data Integrity Rules

### Rule A: Marks Storage
```
✓ All marks stored in subject_marks table as ACTUAL values
✓ marks_obtained = sum of all papers (raw value)
✓ percentage = (marks_obtained ÷ max_marks) × 100
✓ grade = assigned based on percentage and grading profile
```

### Rule B: Display Processing
```
✓ Display value calculated ON-THE-FLY from:
  - marks_obtained (from database)
  - subject.written_papers (from subject configuration)
  - subject.has_practical (from subject configuration)
  - subject.has_project (from subject configuration)
✓ Original database values never modified
✓ Display value never used for calculations
```

### Rule C: Consistency
```
✓ Same subject shows same display rule across all candidates
✓ Same candidate shows consistent totals and averages
✓ Grade assignments remain independent of display format
```

---

## System Implementation

### Where Applied
- ✅ Hierarchy School Results (Section 2)
- ✅ Candidate Result Reports
- 🔄 Planned: Dashboard result cards
- 🔄 Planned: PDF result sheets

### Not Applied To
- ❌ Database storage (always actual marks)
- ❌ Grade calculation (uses actual marks)
- ❌ Ranking/Division (uses actual marks)
- ❌ GPA calculation (uses actual marks)

---

## Edge Cases

### When Marks are Missing
```
If marks_obtained = NULL:
    Display = "X"
    Grade = "-"
    Status = "Not yet entered"
```

### When Subject has No Papers Defined
```
If written_papers = NULL:
    Assume written_papers = 1
    Display = Actual Marks
    (Fallback to single-paper logic)
```

### When Partial Marks Entered
```
If marks_obtained > 0 but < max_marks:
    Display = marks_obtained ÷ paper_count (as normal)
    No special handling - average still applies
```

---

## References

- **Database**: subject_marks table, subjects table
- **View**: resources/views/hierarchy/school-results.blade.php
- **Models**: SubjectMarks.php, Subject.php, CandidateSubjectSelection.php
- **Code Function**: Line 135-160 in school-results.blade.php

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-08 | Initial implementation for KLERRUU results |

**Last Updated:** 2026-02-08  
**Status:** ✅ ACTIVE
