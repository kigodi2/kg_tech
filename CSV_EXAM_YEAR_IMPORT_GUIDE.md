# CSV Import with Exam Year - User Guide

**Issue**: Imported candidates don't show exam year in the Candidates table.

**Root Cause**: Exam year must be provided in one of two ways:

## Option 1: Using Modal Exam Year (Simple)

1. Download CSV template (has 7 columns including exam_year)
2. Edit CSV - **ignore column 7 (exam_year)** or leave it blank
3. Go to Registration → Candidates → Import CSV
4. **Select Exam Year from dropdown** (e.g., "2026")
5. Select CSV file
6. Import
7. ✓ Exam registrations created automatically

## Option 2: Using CSV Exam Year Column (Flexible)

1. Download CSV template (has 7 columns including exam_year)
2. Edit CSV - **FILL COLUMN 7 (exam_year)** with year values (e.g., 2026)
3. Go to Registration → Candidates → Import CSV
4. Exam Year dropdown can be left empty OR selected (CSV takes priority)
5. Select CSV file
6. Import
7. ✓ Exam registrations created automatically using CSV year

## Option 3: Hybrid (CSV + Modal)

Different candidates can have different exam years:
1. Some rows in CSV have exam_year filled (column 7)
2. Some rows in CSV have exam_year blank
3. Select default Exam Year in modal
4. Import
5. ✓ Candidates with CSV year → use CSV year
   ✓ Candidates without CSV year → use modal year

## **IMPORTANT: Must Provide Exam Year Somewhere**

If you import ACSEE candidates WITHOUT providing exam year in:
- Modal dropdown AND
- CSV column 7

Then candidates are created BUT **exam registrations NOT created**, and exam_year column shows "-"

## How to Fix Missing Exam Years

If you already imported candidates without exam years:

```bash
php register_candidate_subjects.php
php fix_missing_exam_registrations.php 2026
```

This creates exam registrations for all ACSEE candidates retroactively.

## CSV Column Reference

| # | Header | Required? | Example | Notes |
|---|--------|-----------|---------|-------|
| 1 | candidate_id | No | S1378-0501 | Auto-generated if blank |
| 2 | full_name | Yes | JOHN SMITH | |
| 3 | gender | Yes | M or F | |
| 4 | combination | For ACSEE | CBE, HGE, PCB | Required if EXAM_TYPE=ACSEE |
| 5 | school_code | Yes | S1378 | Must exist in system |
| 6 | exam_type | Yes | ACSEE | ACSEE, CSEE, PSLE |
| 7 | exam_year | Optional | 2026 | Use Modal if blank |

## Troubleshooting

**Q: I uploaded CSV with exam_year but it still shows "-"**
A: Check that:
   - Column 7 actually has data (not blank)
   - Year format is correct (e.g., "2026" not "20226")
   - EXAM_TYPE is "ACSEE" (other types don't need registration)
   - Check logs: `tail -f storage/logs/laravel.log` for errors

**Q: Can I import the same CSV twice to add exam year?**
A: Yes, use "Replace" mode in conflict dialog. Import again with exam_year filled in column 7.

**Q: Why does the system require exam year?**
A: ACSEE requires subject registrations per exam year. Without knowing the year, the system can't:
   - Register subjects
   - Filter marks by year
   - Generate year-specific reports

## Status

✅ **CSV now includes exam_year column (column 7)**
✅ **Either CSV or Modal exam_year will work**
✅ **Exam registrations created automatically during import**
✅ **No backfill scripts needed (for new imports)**
