# ACSEE Results Module - Operator Guide

**Version:** 1.0  
**Date:** February 4, 2026  
**Status:** Production Ready

---

## Table of Contents

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Operator Workflows](#operator-workflows)
4. [Step-by-Step Procedures](#step-by-step-procedures)
5. [Data Verification](#data-verification)
6. [Troubleshooting](#troubleshooting)
7. [Quick Reference](#quick-reference)

---

## Overview

The ACSEE Results Module provides a hierarchical navigation system (Region → District → School → Results) that mirrors the official NECTA results portal. Results are organized by school and displayed in three sections:

- **Section 1**: Division Summary (by sex)
- **Section 2**: Detailed Results (candidate-level)
- **Section 3**: Subject Performance (grade distribution)

### Key Features

- Grid-based navigation with color-coded cards
- Official NECTA-style header with government emblems
- Compact, print-friendly layout
- Automatic grade calculation (GPA, division, points)
- PDF export capability
- Role-based access control

---

## System Architecture

### Data Flow

```
CSV Import → Mark Entry → Grading Engine → Results Publishing → PDF Export
```

### Key Tables

| Table | Purpose | Status Field |
|-------|---------|--------------|
| `candidate_exam_registrations` | Candidate enrollment | `result_status` (draft/published) |
| `subject_marks` | Individual subject marks | Input from Mark Entry |
| `candidate_results` | Calculated results | Generated after grading |

### Current System State

- **Total Candidates**: 4,889
- **Current Status**: All in `draft` mode
- **Marks Loaded**: 0
- **Results Calculated**: 0

---

## Operator Workflows

### Workflow 1: Import Marks

**Timeline**: ~2-4 hours per batch  
**User Role**: Mark Entry Officer  
**System State**: Draft registrations ready

1. Prepare CSV file with subject marks
2. Login to Mark Entry module
3. Upload CSV and validate
4. Review import summary
5. Confirm and process

**Outcome**: Subject marks stored in `subject_marks` table

### Workflow 2: Calculate Results

**Timeline**: ~15-30 minutes  
**User Role**: Results Officer  
**Prerequisite**: Marks imported for all subjects

1. Navigate to Results module
2. Select region, district, school
3. View results dashboard
4. Trigger grading calculation (if manual)
5. Verify calculations

**Outcome**: Grades, GPA, divisions calculated and stored in `candidate_results`

### Workflow 3: Publish Results

**Timeline**: ~5-10 minutes  
**User Role**: Admin/Results Officer  
**Prerequisite**: Results verified and approved

1. Navigate to Results module
2. Confirm all schools processed
3. Update `result_status` to `published`
4. Set `published_at` timestamp
5. Generate PDF exports

**Outcome**: Results visible to candidates/parents; `published_at` field populated

### Workflow 4: Export & Archive

**Timeline**: ~30 minutes per school  
**User Role**: Admin  
**Prerequisite**: Results published

1. Select school from hierarchy
2. Generate PDF scoresheet
3. Download or email
4. Archive in secure location
5. Update audit log

**Outcome**: PDF scoresheets available for distribution

---

## Step-by-Step Procedures

### Step 1: Prepare & Import Marks

#### Prerequisites
- CSV file with correct format (see **CSV Format** below)
- Valid exam year and school selection
- All candidates registered in ACSEE module

#### CSV Format

```csv
candidate_index,subject_code,marks,practical_marks
ABC001,MAT,75,
ABC001,ENG,68,
ABC001,KIS,72,
ABC002,MAT,45,
ABC002,ENG,52,
...
```

**Fields:**
- `candidate_index`: Unique candidate identifier (e.g., ABC001)
- `subject_code`: 3-letter subject code (MAT, ENG, BIO, etc.)
- `marks`: Written/theory marks (0-100)
- `practical_marks`: Practical marks if applicable (optional)

#### Procedure

1. **Access Mark Entry Module**
   - Login → Admin Panel → Mark Entry → Import

2. **Select Upload Type**
   - Choose "ACSEE Marks Import"
   - Confirm exam year (should be current year)

3. **Upload CSV File**
   - Click "Choose File"
   - Select prepared CSV
   - Click "Upload"

4. **Review Validation Report**
   - System displays: Total rows, valid rows, errors
   - Check error log (if any)
   - Example: "4,889 rows processed | 4,885 valid | 4 errors"

5. **Confirm Import**
   - Review summary statistics
   - Click "Proceed with Import"
   - System inserts marks into `subject_marks` table

6. **Verify Completion**
   - Check timestamp in audit log
   - Verify marks visible in Mark Entry view

---

### Step 2: Navigate Results Hierarchy

#### Procedure

1. **Access Results Module**
   - Login → Dashboard → Results (or directly: `/hierarchy/regions`)

2. **Select Region**
   - View 8-card grid (colored: Red, Green, Black, Gray)
   - Click region card to view districts

3. **Select District**
   - View districts within selected region
   - Click district card to view schools

4. **Select School**
   - View schools within selected district
   - Click school card to view results

5. **View Results**
   - Three sections auto-display with loaded data:
     - **Section 1**: Division summary by sex (Female/Male rows)
     - **Section 2**: Detailed results (11 columns)
     - **Section 3**: Subject performance (grade distribution)

---

### Step 3: Verify Result Calculations

#### What Gets Calculated

When marks are imported and the grading engine runs:

1. **Points Calculation**
   - Grade A = 5 points
   - Grade B = 4 points
   - Grade C = 3 points
   - Grade D = 2 points
   - Grade E = 1 point
   - INC/ABS = 0 points

2. **GPA Calculation**
   - Total Points ÷ Total Subjects (excluding INC/ABS)
   - Rounded to 2 decimal places
   - Range: 1.0–5.0

3. **Division Assignment**
   - Division 1: GPA 4.0–5.0
   - Division 2A: GPA 3.0–3.99
   - Division 2B: GPA 2.0–2.99
   - Division 3: GPA 1.0–1.99

#### Verification Checklist

- [ ] All candidates show 6 subjects (expected)
- [ ] No candidate has GPA > 5.0
- [ ] Division matches GPA range
- [ ] Section 1 shows Female/Male rows (if both present)
- [ ] Section 3 shows all unique subjects registered
- [ ] Total column in Section 3 matches expected count
- [ ] No blank cells in required columns

#### Manual Verification Steps

1. **Select a school** with significant candidate count (e.g., 50+ candidates)
2. **Check Section 2** (Detailed Results):
   - Pick 3-5 random candidates
   - Manually verify: (Sum of Points ÷ 6 subjects) = GPA
   - Example: (5+5+4+3+2+1) ÷ 6 = 3.33 → Division 2A ✓

3. **Check Section 3** (Subject Performance):
   - Verify header shows all 6 subjects
   - Verify grade columns (A, B, C, D, E, S, F, ABS, TOTAL)
   - Verify TOTAL column = number of candidates in school

---

### Step 4: Publish Results

#### Prerequisites
- All marks imported
- All calculations verified
- Admin approval obtained

#### Procedure

1. **Update Result Status**
   - Database access or UI option (if available)
   - Update `candidate_exam_registrations`:
     ```sql
     UPDATE candidate_exam_registrations
     SET result_status = 'published', published_at = NOW()
     WHERE exam_year_id = (current year)
     ```

2. **Verify Published Status**
   - Refresh Results page
   - Confirm sections still display correctly
   - Verify `published_at` timestamp is set

3. **Notify Stakeholders**
   - Send email to school heads
   - Publish notification in admin panel
   - Update candidate portal access

---

### Step 5: Export Results as PDF

#### Procedure

1. **Navigate to School Results**
   - Follow Steps 1-4 in **Step 2: Navigate Results Hierarchy**

2. **Click Export/Print Button**
   - Look for "Download PDF" or "Print" button
   - Button appears below Section 3

3. **Configure PDF Settings** (if prompted)
   - Page orientation: Portrait
   - Margins: 0.5 inch
   - Include header: Yes (government emblems)
   - Include footer: Yes (school name, date)

4. **Save/Download**
   - PDF downloads to default location
   - Filename format: `RESULTS_[SchoolCode]_[ExamYear].pdf`
   - Typical file size: 50-200 KB

5. **Archive**
   - Store in secure folder
   - Backup to external drive
   - Update audit log with export timestamp

---

## Data Verification

### Daily Verification Checklist

Run this checklist daily during the results processing phase:

```
Date: _______________  Operator: _______________

[ ] Access Results module → No errors displayed
[ ] Navigate Region → District → School (random sample)
[ ] Verify Section 1 displays (division summary)
[ ] Verify Section 2 displays (candidate detail)
[ ] Verify Section 3 displays (subject performance)
[ ] Check export PDF function
[ ] Verify timestamps in audit log
[ ] Confirm no candidates with GPA > 5.0
[ ] Confirm all divisions (1, 2A, 2B, 3) present
[ ] Verify subject codes match expected list
```

### Database Verification Queries

#### Count Imported Marks
```sql
SELECT COUNT(*) FROM subject_marks WHERE exam_year_id = (current year);
```
**Expected**: ~29,334 (4,889 candidates × 6 subjects)

#### Count Calculated Results
```sql
SELECT COUNT(*) FROM candidate_results WHERE exam_year_id = (current year);
```
**Expected**: 4,889 (one per candidate)

#### Check Result Status
```sql
SELECT result_status, COUNT(*) FROM candidate_exam_registrations 
WHERE exam_year_id = (current year)
GROUP BY result_status;
```
**Expected**: All rows show `published` (if publishing complete)

#### Find GPA Outliers
```sql
SELECT * FROM candidate_results 
WHERE exam_year_id = (current year) AND gpa > 5.0;
```
**Expected**: No results (0 rows)

---

## Troubleshooting

### Issue 1: Results Page Shows Empty (No Data)

**Symptoms**: Navigate to school → all three sections blank

**Causes & Solutions**:

1. **Marks not imported**
   - Check: Run "Count Imported Marks" query above
   - Fix: Upload CSV via Mark Entry module

2. **School has no registered candidates**
   - Check: `SELECT COUNT(*) FROM candidate_exam_registrations WHERE school_id = X`
   - Fix: Register candidates via ACSEE module first

3. **Results not calculated**
   - Check: Run "Count Calculated Results" query
   - Fix: Trigger grading engine (contact admin if no manual trigger available)

**Resolution Steps**:
1. Verify marks exist: `SELECT COUNT(*) FROM subject_marks WHERE school_id = X`
2. Verify registrations exist: `SELECT COUNT(*) FROM candidate_exam_registrations WHERE school_id = X`
3. Verify results calculated: `SELECT COUNT(*) FROM candidate_results WHERE school_id = X`
4. If any count is 0, follow corresponding import/calculation procedure

---

### Issue 2: GPA Calculation Incorrect

**Symptoms**: GPA doesn't match manual calculation; division misaligned

**Causes & Solutions**:

1. **Marks include incomplete subjects (INC)**
   - Check Section 2 for INC entries
   - Verify: INC should contribute 0 points, not be excluded from count
   - Fix: Confirm grading profile includes INC handling

2. **Subject count mismatch**
   - Check: Expected 6 subjects, but candidate shows 5
   - Fix: Import missing subject mark for that candidate

3. **Rounding error**
   - Check: GPA rounded to 2 decimals?
   - Example: 3.334 → 3.33 (correct), NOT 3.34
   - Fix: Verify grading engine uses ROUND(value, 2)

**Resolution Steps**:
1. Select affected candidate in Section 2
2. Manually calculate: sum of points ÷ subject count
3. Compare with displayed GPA
4. If mismatch: Check for missing marks or INC entries
5. Contact admin if calculation formula is suspected

---

### Issue 3: PDF Export Button Not Working

**Symptoms**: Click export → nothing happens or 404 error

**Causes & Solutions**:

1. **No export route defined**
   - Check: `routes/web.php` for `/hierarchy/school/*/export-pdf`
   - Fix: Ensure route exists and points to PDF controller

2. **PDF library not installed**
   - Check: `composer.json` for `dompdf` or similar
   - Fix: Run `composer require dompdf/dompdf`

3. **Permission denied**
   - Check: `storage/app/` directory writable?
   - Fix: `chmod 755 storage/app/`

**Resolution Steps**:
1. Try export from different school
2. Check browser console for JavaScript errors
3. Check server error log: `tail -50 storage/logs/laravel.log`
4. Contact admin with error details

---

### Issue 4: Section 3 (Subject Performance) Missing Subjects

**Symptoms**: Only 4-5 subjects shown, expected 6

**Causes & Solutions**:

1. **Not all subjects have marks**
   - Check: Some candidates didn't register for that subject
   - Expected: Each subject shown if ANY candidate took it
   - Fix: Verify all candidates have complete subject registrations

2. **Subject filter applied**
   - Check: Is there a filter in UI?
   - Fix: Clear filters to show all subjects

3. **Query bug**
   - Check: `SELECT DISTINCT subject_id FROM subject_marks WHERE school_id = X`
   - Fix: Should return 6 rows; if less, marks missing for some subjects

**Resolution Steps**:
1. Verify marks exist for all subjects: Run subject marks query
2. Confirm candidate registrations complete: Check ACSEE registrations
3. Clear any active filters
4. Refresh page (Ctrl+Shift+R hard refresh)

---

### Issue 5: Duplicate Entries in Section 2

**Symptoms**: Same candidate appears twice; totals incorrect

**Causes & Solutions**:

1. **Duplicate marks imported**
   - Check: `SELECT candidate_id, subject_id, COUNT(*) FROM subject_marks GROUP BY candidate_id, subject_id HAVING COUNT(*) > 1`
   - Fix: Delete duplicates or re-import with deduplication

2. **Query joins incorrectly**
   - Check: `HierarchyController@schoolResults()` method
   - Fix: Ensure `DISTINCT` or `GROUP BY` applied to prevent row duplication

**Resolution Steps**:
1. Run duplicate detection query
2. If duplicates found: Contact admin for cleanup
3. If query issue: Check controller logic with dev team
4. Reimport clean data if necessary

---

## Quick Reference

### Key Paths & URLs

| Function | URL | Access |
|----------|-----|--------|
| Results Home | `/hierarchy/regions` | Auth required |
| Select District | `/hierarchy/districts/{regionId}` | Auth required |
| Select School | `/hierarchy/schools/{districtId}` | Auth required |
| View Results | `/hierarchy/school/{schoolId}/results` | Auth required |
| Mark Entry | `/mark-entry/import` | Auth + Role required |
| Admin Panel | `/admin` | Admin only |

### Database Quick Commands

**Candidate count by school**:
```sql
SELECT school_id, COUNT(*) FROM candidate_exam_registrations 
WHERE exam_year_id = (current year) GROUP BY school_id;
```

**Marks count by subject**:
```sql
SELECT subject_id, COUNT(*) FROM subject_marks 
WHERE exam_year_id = (current year) GROUP BY subject_id;
```

**Results summary**:
```sql
SELECT 
  result_status,
  COUNT(*) as count,
  AVG(gpa) as avg_gpa,
  MIN(gpa) as min_gpa,
  MAX(gpa) as max_gpa
FROM candidate_results
GROUP BY result_status;
```

### Common CSV Formats

**Subject Codes Reference**:
- MAT = Mathematics
- ENG = English
- KIS = Kiswahili
- BIO = Biology
- CHM = Chemistry
- PHY = Physics
- GEO = Geography
- CRE = Christian Religious Education
- ISL = Islamic Religious Education
- HIS = History
- COM = Commerce

### Escalation Contacts

| Issue | Contact | Timing |
|-------|---------|--------|
| Data calculation error | Results Officer | Within 2 hours |
| System error/404 | Tech Support | Immediate |
| Permission denied | Admin | Within 1 hour |
| PDF export not working | Tech Support | Within 2 hours |
| Candidate data mismatch | Registration Officer | Within 4 hours |

---

## Support & Documentation

- **Quick Start**: `HIERARCHY_QUICK_START.md`
- **Technical Reference**: `HIERARCHY_DEPLOYMENT_INDEX.md`
- **Database Schema**: `DATABASE_SCHEMA_FINAL.md`
- **Project Status**: `ACSEE_RESULTS_FINAL_STATUS.md`

---

**Last Updated**: February 4, 2026  
**For Questions**: Contact IT Support or Project Manager
