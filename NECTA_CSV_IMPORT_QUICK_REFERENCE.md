# NECTA CSV Import - Quick Reference Card
**Date**: 2026-02-15

---

## CSV Formats at a Glance

### SCHOOL Candidates
```csv
candidate_id,full_name,gender,candidate_type,combination,school_code,exam_type,exam_year
S001,John Doe,M,SCHOOL,SCIENCE,S1378,ACSEE,2026
```

**Key Fields:**
- `candidate_type`: **SCHOOL** (uses template)
- `combination`: Subject combination code (SCIENCE, COMMERCE, GENERAL)
- `school_code`: School identifier

### PRIVATE Candidates
```csv
candidate_id,full_name,gender,candidate_type,subjects,exam_type,exam_year,district
P001,Jane Doe,F,PRIVATE,"111|102|103|104",ACSEE,2026,Dar es Salaam
```

**Key Fields:**
- `candidate_type`: **PRIVATE** (manual selection)
- `subjects`: Subject IDs pipe-separated (e.g., `111|102|103|104`)
- `district`: District name

---

## Subject Format

**Pipe-separated IDs** for PRIVATE candidates:
```
111|102|103|104
  |  |   |   └─ Subject 4 (Principal)
  |  |   └───── Subject 3 (Principal)
  |  └───────── Subject 2 (Principal)
  └──────────── Subject 1 (General Studies - REQUIRED)
```

**Rules:**
- First subject should be **111** (General Studies)
- Minimum **4 subjects** total (GS + 3 principals)
- Separated by **pipe (|)** character
- No spaces

**Example:** `111|102|103|105` (GS + Biology + Chemistry + Math)

---

## Common Subject Codes

| Code | Name |
|------|------|
| 111 | General Studies (REQUIRED) |
| 102 | Biology |
| 103 | Chemistry |
| 104 | Physics |
| 105 | Mathematics |
| 106 | English |
| 107 | History |
| 108 | Geography |
| 109 | Civics |

---

## Import Methods

### Web Interface
1. Candidates → Bulk Import
2. Choose CSV file
3. Select type: SCHOOL / PRIVATE / Auto
4. Preview → Confirm → Import

### Command Line
```bash
php artisan candidates:import --file=file.csv --exam-type=1 --exam-year=1
```

### API
```bash
curl -X POST http://localhost/api/candidates/import \
  -F "file=@candidates.csv" \
  -F "exam_type=ACSEE" \
  -F "exam_year=2026"
```

---

## Validation Checklist

### SCHOOL
- ✅ candidate_id (unique)
- ✅ full_name
- ✅ gender (M/F)
- ✅ candidate_type = "SCHOOL"
- ✅ combination (exists)
- ✅ school_code (exists)
- ✅ exam_type
- ✅ exam_year

### PRIVATE
- ✅ candidate_id (unique)
- ✅ full_name
- ✅ gender (M/F)
- ✅ candidate_type = "PRIVATE"
- ✅ subjects (pipe-separated, GS included, 3+ principals)
- ✅ district (exists)
- ✅ exam_type
- ✅ exam_year

---

## Common Errors & Fixes

| Error | Fix |
|-------|-----|
| "candidate_type must be SCHOOL or PRIVATE" | Check spelling (case-sensitive) |
| "Combination not found: SCIENCE" | Verify combination exists |
| "Subject 102 not found" | Check subject ID exists |
| "General Studies (111) is mandatory" | Add 111 to subjects for PRIVATE |
| "Minimum 3 principal subjects required" | Add at least 4 subjects total (GS + 3) |
| "Subjects required for PRIVATE candidates" | Subjects field empty; use format: "111\|102\|103\|104" |

---

## Example Files

**SCHOOL:**
```
templates/candidates_school_import_example.csv
```

**PRIVATE:**
```
templates/candidates_private_import_example.csv
```

**MIXED:**
```
templates/candidates_mixed_import_example.csv
```

---

## Tips

✓ **Always test on staging first**

✓ **Backup database before import**
```bash
php artisan backup:run --only=database
```

✓ **Use UTF-8 encoding**
```bash
file -i candidates.csv  # Should show: charset=utf-8
```

✓ **Verify data before importing**
```bash
# Count rows
wc -l candidates.csv

# Check for duplicates
cut -d, -f1 candidates.csv | sort | uniq -d
```

✓ **Monitor import progress**
```bash
tail -f storage/logs/laravel.log
```

---

## Subject Allocation Result

### SCHOOL Candidate
Input: `combination=SCIENCE`  
Result: Biology + Chemistry + Physics + General Studies + [optional electives from SCIENCE]

### PRIVATE Candidate
Input: `subjects=111|102|103|104`  
Result: General Studies + Biology + Chemistry + Physics

---

**Created**: 2026-02-15  
**Version**: 1.0
