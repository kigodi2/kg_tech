# Hierarchy Results System - Quick Start Guide

## What is This?

A hierarchical navigation system for viewing ACSEE examination results organized by:
1. **Region** → 2. **District** → 3. **School** → 4. **Detailed Results**

Mirrors the official NECTA results portal structure.

---

## How to Access

### In the Application
1. Login to the IRMS system
2. Navigate to Dashboard
3. Look for "Results Hierarchy" or "View Results by School"
4. Click on a region to start

### Direct URLs
```
/hierarchy/regions                          # Start here
/hierarchy/districts/1                      # Districts in region 1
/hierarchy/schools/1                        # Schools in district 1
/hierarchy/school/1/results                 # Results for school 1
```

---

## What You'll See

### Region View
- Grid layout of all regions
- Number of districts per region
- Click a region to see its districts

### District View
- Grid layout of all districts in selected region
- Number of schools per district
- Breadcrumb showing: Region Name > Back
- Click a district to see its schools

### School View
- Grid layout of all schools in selected district
- Number of candidates per school
- Breadcrumb showing: Region Name > District Name > Back
- Click a school to see its results

### Results View (Main Report)

#### Section 1: Division Performance Summary
Shows candidate distribution by division and gender:
```
SEX  |  I  | II  | III  | IV  |  0  | INC | ABS
-----+-----+-----+------+-----+-----+-----+-----
F    |  0  |  1  |  5   | 10  |  2  |  0  |  0
M    |  1  |  5  | 30   | 60  | 15  |  0  |  0
T    |  1  |  6  | 35   | 70  | 17  |  0  |  0
```

#### Section 2: Detailed Results Table
All candidates sorted by Division and GPA:
- Candidate Number (CNO)
- Sex (M/F)
- Subject Combination (COMB)
- Detailed subjects with marks and grades
- Total marks, average, grade, points
- Division and GPA
- Position (rank)

**Example row:**
```
CNO: S001234
SUBJECTS: CHEMISTRY=82 'B', PHYSICS=75 'B', MATHEMATICS=88 'A'
AVG: 81.67 | GRD: B | DIV: II | GPA: 3.33 | POS: 5
```

#### Section 3: Examination Centre Performance
Three sub-tables:

1. **Overall Performance**
   - Region, District names
   - Total registered & passed candidates
   - School GPA average

2. **Division Performance**
   - REGIST: Total registered
   - ABSENT: Not sat examination
   - SAT: Sat examination
   - WITHHELD: Withheld results
   - NO-CA: No continuous assessment
   - CLEAN: No issues
   - DIV I-0: Breakdown by division

3. **Subjects Performance**
   - Subject code and name
   - Grade distribution (A, B, C, D, E, S, F, ABS)
   - Total candidates
   - Average GPA
   - Competency level (color-coded)

---

## Key Features

✅ **Dynamic Gender Rows** - Only shows F/M rows if candidates exist  
✅ **Proper Sorting** - Candidates sorted Division I → 0, then by GPA descending  
✅ **Missing Marks** - Shows 'X' if marks not entered for a subject  
✅ **NECTA Styling** - Official layout with government emblem and typography  
✅ **Responsive Design** - Works on desktop and mobile  
✅ **Color Coding** - Competency levels use color indicators  

---

## Current Data

- **8 Regions**: Tanga, Iringa, Singida, Morogoro, Dodoma, Tabora, Lindi, Mtwara
- **52 Districts**: Distributed across regions
- **42 Schools**: Distributed across districts
- **4,889 Candidates**: All with exam registrations
- **4,871 with Results**: GPA, Division, Grade calculated

---

## Common Tasks

### Find a Specific School's Results
1. Click on Region name
2. Click on District
3. Find school in grid
4. Click school to see results

### Check a Student's Division
1. Navigate to school results
2. Find student in "Detailed Results Table"
3. Look at DIV column (I, II, III, IV, or 0)

### View Subject Performance
1. Navigate to school results
2. Scroll to "Examination Centre Subjects Performance"
3. See grade distribution and competency levels

---

## Troubleshooting

### "No candidates found for this school"
- School may not have any registered candidates
- Try a different school in the same district

### All candidates show Division 0
- Not all marks have been entered yet
- GPA/Division calculated when marks are complete

### 'X' appearing in results
- Candidate registered but no marks entered
- Contact mark entry administrator

---

## Support

For issues or questions:
1. Check the IRMS Dashboard
2. Contact the Results Administrator
3. Report errors with: Region, District, School, and Candidate details
