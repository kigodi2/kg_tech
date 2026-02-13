# NECTA Grading System - Visual Reference Guide

## Grading Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                   NECTA GRADING SYSTEM FLOW                      │
└─────────────────────────────────────────────────────────────────┘

                        CANDIDATE MARKS
                              │
                              ▼
                ┌─────────────────────────┐
                │  MARKS RECEIVED (0-100) │
                └────────────┬────────────┘
                             │
                ┌────────────┴────────────┐
                │                         │
                ▼                         ▼
        ┌──────────────┐         ┌──────────────┐
        │ FOR EACH     │         │ APPLY TO ALL │
        │ SUBJECT:     │         │ SUBJECTS:    │
        │              │         │              │
        │ 1. Get Grade │         │ 1. Sum ALL   │
        │ 2. Get Points│         │    marks     │
        │ 3. Check if  │         │              │
        │    Excluded  │         │ RESULT:      │
        │              │         │ Total Marks  │
        │ RESULT:      │         └──────────────┘
        │ Grade Sheet  │
        └──────┬───────┘
               │
        ┌──────┴──────┐
        │             │
        ▼             ▼
    INCLUDED    EXCLUDED
    SUBJECTS    SUBJECTS
    │           │
    │           • General Studies
    │           • Basic Applied Math
    │
    ▼
    Sum Points for
    Included Subjects
    │
    ▼
    ┌──────────────────┐
    │ TOTAL POINTS     │
    │ (for division)   │
    └────────┬─────────┘
             │
             ├─────────────┐
             │             │
             ▼             ▼
        ┌─────────┐   ┌──────────┐
        │ GPA     │   │ DIVISION │
        │ Points/ │   │ by Points│
        │ Count   │   │          │
        └────┬────┘   └────┬─────┘
             │             │
             ▼             ▼
        FINAL GPA    FINAL DIVISION
        (1.0-7.0)    (I, II, III, IV, O)
```

## Calculation Process

```
STEP 1: MARKS TO GRADE CONVERSION
═════════════════════════════════

Marks         Grade    Points    Competence
0-34    →      F    →    7    →   Fail
35-39   →      S    →    6    →   Unsatisfactory
40-49   →      E    →    5    →   Satisfactory
50-59   →      D    →    4    →   Average
60-69   →      C    →    3    →   Good
70-79   →      B    →    2    →   Very Good
80-100  →      A    →    1    →   Excellent


STEP 2: CALCULATE TOTAL MARKS (ALL SUBJECTS)
═════════════════════════════════════════════

Total Marks = English(75) + Math(85) + Physics(70) 
            + Chemistry(65) + Biology(80) 
            + GeneralStudies(55) + BasicAppliedMath(60)
            
Total Marks = 490


STEP 3: CALCULATE TOTAL POINTS (INCLUDED SUBJECTS ONLY)
═══════════════════════════════════════════════════════

Included Subjects (6):     Points:
English (75) → B        =    2
Math (85) → A           =    1
Physics (70) → B        =    2
Chemistry (65) → C      =    3
Biology (80) → A        =    1
────────────────────────────────
SUBTOTAL (Included)     =    9

Excluded Subjects (2):     Points:
General Studies (55) → D =    4  ❌ NOT COUNTED
Basic Applied Math (60) → C = 3  ❌ NOT COUNTED

Total Points = 9


STEP 4: CALCULATE GPA
═════════════════════

GPA = Total Points / Number of Included Subjects
GPA = 9 / 6 subjects
GPA = 1.5


STEP 5: DETERMINE DIVISION (FROM TOTAL POINTS)
═══════════════════════════════════════════════

Total Points = 9

Points Range          Division    Competence
3-9    →        I      →   Excellent    ✓ (9 points fall here)
10-12   →        II     →   Very Good
13-17   →        III    →   Good
18-19   →        IV     →   Average
20-21    →        O      →   Fail

DIVISION = I (Excellent)
```

## Data Structure

```
┌──────────────────────────────────────────────────────┐
│           CANDIDATE GRADING REPORT                   │
├──────────────────────────────────────────────────────┤
│                                                       │
│  Candidate Information                               │
│  ├─ candidate_id: 1                                  │
│  ├─ candidate_name: "John Doe"                       │
│  ├─ exam_type_id: 1                                  │
│  └─ year: 2024                                       │
│                                                       │
│  Overall Results                                     │
│  ├─ total_marks: 490          [ALL SUBJECTS]         │
│  ├─ total_points: 9           [INCLUDED SUBJECTS]    │
│  ├─ gpa: 1.5                  [INCLUDED SUBJECTS]    │
│  ├─ overall_grade: "A"        [BEST GRADE]           │
│  └─ competence_level: "Excellent"                    │
│                                                       │
│  Division                                            │
│  ├─ division: "I"                                    │
│  ├─ points: 9                                        │
│  └─ competence: "Excellent"                          │
│                                                       │
│  Subject Details                                     │
│  ├─ Subject 1: ENGLISH (75 marks)                    │
│  │  ├─ Grade: B                                      │
│  │  ├─ Points: 2                                     │
│  │  ├─ Competence: "Very Good"                       │
│  │  └─ is_excluded: false                            │
│  │                                                    │
│  ├─ Subject 2: MATHEMATICS (85 marks)                │
│  │  ├─ Grade: A                                      │
│  │  ├─ Points: 1                                     │
│  │  ├─ Competence: "Excellent"                       │
│  │  └─ is_excluded: false                            │
│  │                                                    │
│  └─ Subject 7: GENERAL STUDIES (55 marks) [EXCLUDED] │
│     ├─ Grade: D                                      │
│     ├─ Points: 4                                     │
│     ├─ Competence: "Average"                         │
│     └─ is_excluded: true  ⚠️                         │
│                                                       │
│  Categorized Results                                 │
│  ├─ included_subject_grades: [6 subjects]            │
│  └─ excluded_subject_grades: [2 subjects]            │
│                                                       │
└──────────────────────────────────────────────────────┘
```

## Grade Mapping Tables

### Table 1: Marks to Grade

```
╔════════════╦═══════╦════════╦════════════════╗
║   MARKS    ║ GRADE ║ POINTS ║  COMPETENCE    ║
╠════════════╬═══════╬════════╬════════════════╣
║ 80 - 100   ║   A   ║   1    ║   Excellent    ║
║ 70 -  79   ║   B   ║   2    ║  Very Good     ║
║ 60 -  69   ║   C   ║   3    ║     Good       ║
║ 50 -  59   ║   D   ║   4    ║    Average     ║
║ 40 -  49   ║   E   ║   5    ║   Satisfactory    ║
║ 35 -  39   ║   S   ║   6    ║  Unsatisfactory   ║
║  0 -  34   ║   F   ║   7    ║      Fail      ║
╚════════════╩═══════╩════════╩════════════════╝
```

### Table 2: Division Classification

```
╔═══════════════╦═════════╦════════════════╗
║ TOTAL POINTS  ║ DIVISION║  COMPETENCE    ║
╠═══════════════╬═════════╬════════════════╣
║     3 - 9     ║    I    ║   Excellent    ║
║    10 - 12    ║   II    ║  Very Good     ║
║    13 - 17    ║  III    ║     Good       ║
║    18 - 19    ║   IV    ║    Average     ║
║    20 - 21    ║    O    ║      Fail      ║
╚═══════════════╩═════════╩════════════════╝
```

### Table 3: Subject Status

```
╔════════════════════════════════╦═════════════════════════════╗
║        SUBJECT NAME            ║ CALCULATION INCLUSION        ║
╠════════════════════════════════╬═════════════════════════════╣
║  GENERAL STUDIES               ║  Total Marks: ✓             ║
║                                ║  Total Points: ✗ EXCLUDED  ║
║                                ║  GPA: ✗ EXCLUDED           ║
║                                ║  Division: ✗ EXCLUDED      ║
╠════════════════════════════════╬═════════════════════════════╣
║  BASIC APPLIED MATHEMATICS     ║  Total Marks: ✓             ║
║                                ║  Total Points: ✗ EXCLUDED  ║
║                                ║  GPA: ✗ EXCLUDED           ║
║                                ║  Division: ✗ EXCLUDED      ║
╠════════════════════════════════╬═════════════════════════════╣
║  ALL OTHER SUBJECTS            ║  Total Marks: ✓             ║
║  (English, Math, Physics, etc) ║  Total Points: ✓ INCLUDED  ║
║                                ║  GPA: ✓ INCLUDED           ║
║                                ║  Division: ✓ INCLUDED      ║
╚════════════════════════════════╩═════════════════════════════╝
```

## Code Architecture

```
app/Services/Results/
├── NectaGradingService.php
│   ├── Grade Calculation
│   │   ├─ calculateGrade(marks) → grade
│   │   ├─ getCompetenceLevel(grade) → competence
│   │   └─ getGradePoints(grade) → points
│   │
│   ├─ Subject Management
│   │   ├─ isExcludedSubject(name) → boolean
│   │   └─ getExcludedSubjects() → array
│   │
│   ├─ Candidate Calculations
│   │   ├─ calculateTotalMarks(candidate, examTypeId, year) → float
│   │   ├─ calculateTotalPoints(candidate, examTypeId, year) → float
│   │   ├─ calculateGPA(candidate, examTypeId, year) → float
│   │   ├─ calculateDivision(totalPoints) → array
│   │   └─ calculateOverallGrade(candidate, examTypeId, year) → string
│   │
│   ├─ Reporting
│   │   ├─ generateGradingReport(candidate, examTypeId, year) → array
│   │   └─ processBatchGrading(examTypeId, year, schoolId) → array
│   │
│   └─ Reference Data
│       ├─ getGradeBoundaries() → array
│       ├─ getGradePointsMapping() → array
│       └─ getDivisionBoundaries() → array
```

## Algorithm Pseudocode

```
FUNCTION generateGradingReport(candidate, examTypeId, year)
  
  // 1. Get all marks for candidate
  marks = candidate.marks(examTypeId, year)
  
  IF marks is empty:
    RETURN null
  
  // 2. Initialize accumulators
  totalMarks = 0
  totalPoints = 0
  includedCount = 0
  bestGrade = 'F'
  bestPoints = 7
  
  // 3. Process each subject
  FOR EACH mark IN marks:
    grade = calculateGrade(mark.marks_obtained)
    points = getGradePoints(grade)
    isExcluded = isExcludedSubject(mark.subject.name)
    
    // 3a. All marks count toward total
    totalMarks += mark.marks_obtained
    
    // 3b. Only included subjects count toward points
    IF NOT isExcluded:
      totalPoints += points
      includedCount += 1
    
    // 3c. All grades can be best grade
    IF points < bestPoints:
      bestPoints = points
      bestGrade = grade
  
  // 4. Calculate GPA
  gpa = totalPoints / includedCount
  
  // 5. Calculate division
  division = calculateDivision(totalPoints)
  
  // 6. Return complete report
  RETURN {
    candidate_id: candidate.id,
    total_marks: totalMarks,
    total_points: totalPoints,
    gpa: gpa,
    division: division,
    overall_grade: bestGrade,
    // ... plus details
  }
END FUNCTION
```

## Example Workflow

```
                START
                  │
                  ▼
         ┌────────────────┐
         │ Create Service │
         └────────┬───────┘
                  │
                  ▼
         ┌────────────────┐
         │ Get Candidate  │
         │   & Load       │
         │    Marks       │
         └────────┬───────┘
                  │
                  ▼
         ┌────────────────┐
         │  For Each Mark:│
         │  • Get Grade   │
         │  • Get Points  │
         │  • Check if    │
         │    Excluded    │
         └────────┬───────┘
                  │
         ┌────────┴────────┐
         │                 │
         ▼                 ▼
      INCLUDED        EXCLUDED
      Subjects        Subjects
         │                 │
         │       (Marked   │
         │        but not  │
         │        counted) │
         │                 │
         ▼                 ▼
      Sum Points      Sum Points
      (Included)      (Not Used)
         │
         ▼
      Calculate GPA
      = Points / Count
         │
         ▼
      Determine Division
      = Lookup in Table
         │
         ▼
      REPORT
      ├─ Total Marks
      ├─ Total Points
      ├─ GPA
      ├─ Division
      └─ Overall Grade
         │
         ▼
        END
```

## Reference Implementation

```php
// REAL SERVICE USAGE
$service = new NectaGradingService();
$candidate = Candidate::find(1);

// Get complete report
$report = $service->generateGradingReport($candidate, 1, 2024);

// Extract data
$totalMarks = $report['total_marks'];         // 490 (all subjects)
$totalPoints = $report['total_points'];       // 9 (6 included subjects)
$gpa = $report['gpa'];                        // 1.5 (9 / 6)
$division = $report['division']['division'];  // "I"
$grade = $report['overall_grade'];            // "A"

// Loop through subjects
foreach ($report['subject_grades'] as $subject) {
    echo $subject['subject_name'];     // "ENGLISH"
    echo $subject['marks_obtained'];   // 75
    echo $subject['grade'];            // "B"
    echo $subject['points'];           // 2
    echo $subject['is_excluded'];      // false/true
}
```

## Decision Tree

```
                    Candidate Has Marks?
                            │
                ┌───────────┴───────────┐
               YES                     NO
                │                       │
                ▼                       ▼
          Process Marks          Return null
                │                       │
                ▼                       └─── END
         For Each Subject
                │
                ├─ Calculate Grade
                │
                ├─ Get Points
                │
                └─ Is Excluded?
                    │
            ┌───────┴───────┐
           YES              NO
            │                │
            │       ┌────────┴────────┐
            │       │                 │
            │       Add to         Count for
            │   Excluded List      GPA Calc
            │       │                 │
            │       Add to         Add to
            │   Total Marks        Total Points
            │       │                 │
            └───────┴─────────────────┘
                    │
                    ▼
          Calculate GPA
          = TotalPoints / Count
                    │
                    ▼
          Determine Division
          Lookup in Table
                    │
                    ▼
              Return Report
```

## Summary Table

| Calculation | Formula | Includes | Excludes |
|------------|---------|----------|----------|
| **Total Marks** | Sum of all marks | All subjects | None |
| **Total Points** | Sum of grade points | 6 subjects | General Studies, Basic Applied Math |
| **GPA** | Total Points ÷ Subject Count | 6 subjects | General Studies, Basic Applied Math |
| **Division** | Lookup in Table | Based on Total Points | - |
| **Overall Grade** | Best grade achieved | All subjects | - |

This visual reference covers the complete NECTA grading system implementation.
