# NECTA Phase 2 CSV Import Template Guide
**Date**: 2026-02-15  
**Feature**: SCHOOL and PRIVATE Candidate CSV Import  
**Status**: Production Ready

---

## Overview

The updated CSV import templates support both SCHOOL and PRIVATE candidate registration with subject allocation.

---

## CSV Template Formats

### Template 1: SCHOOL Candidates (Using Combination)

**Filename**: `candidates_school_import.csv`

```csv
candidate_id,full_name,gender,candidate_type,combination,school_code,exam_type,exam_year
S1378-0001,John Doe,M,SCHOOL,SCIENCE,S1378,ACSEE,2026
S1378-0002,Jane Smith,F,SCHOOL,COMMERCE,S1378,ACSEE,2026
S1378-0003,Peter Brown,M,SCHOOL,GENERAL,S1378,ACSEE,2026
```

**Fields:**
- `candidate_id` (required): Unique identifier
- `full_name` (required): Candidate full name
- `gender` (required): M or F
- `candidate_type` (required): **SCHOOL** (uses combination template)
- `combination` (required for SCHOOL): Subject combination code (SCIENCE, COMMERCE, GENERAL, etc.)
- `school_code` (required): School identifier
- `exam_type` (required): ACSEE, NECTA, etc.
- `exam_year` (required): Year (2026, 2027, etc.)

**Behavior:**
- System loads subjects from the selected combination
- Subjects automatically allocated
- No manual subject selection needed

---

### Template 2: PRIVATE Candidates (Manual Subject Selection)

**Filename**: `candidates_private_import.csv`

```csv
candidate_id,full_name,gender,candidate_type,subjects,exam_type,exam_year,district
P2026-0001,Alice Johnson,F,PRIVATE,"111|102|103|104",ACSEE,2026,Dar es Salaam
P2026-0002,Bob Wilson,M,PRIVATE,"111|102|103|105",ACSEE,2026,Dar es Salaam
P2026-0003,Carol Davis,F,PRIVATE,"111|102|103|106",ACSEE,2026,Dodoma
```

**Fields:**
- `candidate_id` (required): Unique identifier
- `full_name` (required): Candidate full name
- `gender` (required): M or F
- `candidate_type` (required): **PRIVATE** (manual subject selection)
- `subjects` (required for PRIVATE): Subject IDs separated by pipe (|)
  - Format: `111|102|103|104` (General Studies|Subject2|Subject3|Subject4)
  - Minimum: 4 subjects (GS + 3 principals)
  - First subject should be 111 (General Studies)
- `exam_type` (required): ACSEE, NECTA, etc.
- `exam_year` (required): Year (2026, 2027, etc.)
- `district` (required): District name

**Behavior:**
- System validates subjects exist
- Checks General Studies (111) is included
- Checks minimum 3 principal subjects
- Allocates specified subjects directly
- No combination used

---

### Template 3: Mixed SCHOOL + PRIVATE Import

**Filename**: `candidates_mixed_import.csv`

```csv
candidate_id,full_name,gender,candidate_type,combination,subjects,school_code,district,exam_type,exam_year
S1378-0001,John Doe,M,SCHOOL,SCIENCE,,S1378,,ACSEE,2026
S1378-0002,Jane Smith,F,SCHOOL,COMMERCE,,S1378,,ACSEE,2026
P2026-0001,Alice Johnson,F,PRIVATE,,"111|102|103|104",,Dar es Salaam,ACSEE,2026
P2026-0002,Bob Wilson,M,PRIVATE,,"111|102|103|105",,Dodoma,ACSEE,2026
```

**Usage:**
- Use for mixed candidate batches
- SCHOOL rows: Fill `combination` + `school_code`, leave `subjects` + `district` blank
- PRIVATE rows: Fill `subjects` + `district`, leave `combination` + `school_code` blank

---

## Subject ID Reference

### Common Subject Codes

| Code | Name | Type | Required |
|------|------|------|----------|
| 111 | General Studies | Mandatory | ✓ Always |
| 102 | Biology | Principal | Optional |
| 103 | Chemistry | Principal | Optional |
| 104 | Physics | Principal | Optional |
| 105 | Mathematics | Principal | Optional |
| 106 | English Language | Principal | Optional |
| 107 | History | Principal | Optional |
| 108 | Geography | Principal | Optional |
| 109 | Civics | Principal | Optional |
| ... | [Other subjects] | Various | See combination |

**To get all subject IDs:**
```bash
mysql -u root -p irms -e "SELECT id, code, name FROM subjects ORDER BY code;"
```

---

## Combination Reference

### Available Combinations

Get all combinations:
```bash
mysql -u root -p irms -e "SELECT id, name, code FROM combinations;"
```

**Common Combinations:**
- SCIENCE: 111 + Biology + Chemistry + Physics + [optional electives]
- COMMERCE: 111 + Geography + Economics + [optional electives]
- GENERAL: 111 + History + Civics + Geography + [optional electives]

---

## Import Steps

### Via Web Interface

1. **Candidates** → **Bulk Import**
2. Select CSV file
3. Choose import type:
   - **SCHOOL Candidates**: Uses `combination` column
   - **PRIVATE Candidates**: Uses `subjects` column
   - **Mixed**: Detects automatically
4. Preview results
5. Confirm and import

### Via Command Line

```bash
php artisan candidates:import-csv candidates_school_import.csv
php artisan candidates:import-csv candidates_private_import.csv
```

---

## Validation Rules

### SCHOOL Candidates
✅ Required fields: candidate_id, full_name, gender, candidate_type, combination, school_code, exam_type, exam_year  
✅ Combination must exist in database  
✅ School must exist in database  
✅ candidate_type must be "SCHOOL"  
❌ Not allowed: Blank combination

### PRIVATE Candidates
✅ Required fields: candidate_id, full_name, gender, candidate_type, subjects, exam_type, exam_year, district  
✅ Subjects must exist in database  
✅ District must exist in database  
✅ General Studies (111) must be included  
✅ Minimum 3 principal subjects (in addition to GS)  
✅ candidate_type must be "PRIVATE"  
❌ Not allowed: Blank subjects, missing GS, < 3 principals

---

## Error Handling

### Common Errors & Fixes

**Error**: "candidate_type must be SCHOOL or PRIVATE"
```
Fix: Ensure column value is exactly "SCHOOL" or "PRIVATE" (case-sensitive)
```

**Error**: "Combination not found: SCIENCE"
```
Fix: Check combination spelling and case
Command: mysql -e "SELECT name FROM combinations WHERE name LIKE '%SCIENCE%';"
```

**Error**: "Subject 102 not found"
```
Fix: Verify subject ID exists in database
Command: mysql -e "SELECT id, name FROM subjects WHERE id = 102;"
```

**Error**: "General Studies (111) is mandatory"
```
Fix: PRIVATE candidates must include 111 in subjects
Example: "111|102|103|104" (not "102|103|104")
```

**Error**: "Minimum 3 principal subjects required"
```
Fix: Include at least 4 subjects total (GS + 3 principals)
Example: "111|102|103|104" (correct)
Wrong: "111|102|103" (only 2 principals)
```

---

## Example CSV Files

### Example 1: SCHOOL Import (Simple)

```csv
candidate_id,full_name,gender,candidate_type,combination,school_code,exam_type,exam_year
SC001,Alice Mkwashi,F,SCHOOL,SCIENCE,S1378,ACSEE,2026
SC002,Bob Mwase,M,SCHOOL,COMMERCE,S1378,ACSEE,2026
SC003,Carol Njuki,F,SCHOOL,GENERAL,S1379,ACSEE,2026
SC004,David Okafor,M,SCHOOL,SCIENCE,S1379,ACSEE,2026
```

### Example 2: PRIVATE Import (Manual Subjects)

```csv
candidate_id,full_name,gender,candidate_type,subjects,exam_type,exam_year,district
PR001,Emma Banda,F,PRIVATE,"111|102|103|104",ACSEE,2026,Dar es Salaam
PR002,Frank Chuma,M,PRIVATE,"111|102|103|105",ACSEE,2026,Dodoma
PR003,Grace Mwangi,F,PRIVATE,"111|102|103|106",ACSEE,2026,Arusha
PR004,Henry Kipchoge,M,PRIVATE,"111|102|103|107",ACSEE,2026,Mbeya
```

### Example 3: Mixed Import

```csv
candidate_id,full_name,gender,candidate_type,combination,subjects,school_code,district,exam_type,exam_year
SC001,School Candidate 1,M,SCHOOL,SCIENCE,,S1378,,ACSEE,2026
SC002,School Candidate 2,F,SCHOOL,COMMERCE,,S1378,,ACSEE,2026
PR001,Private Candidate 1,F,PRIVATE,,"111|102|103|104",,Dar es Salaam,ACSEE,2026
PR002,Private Candidate 2,M,PRIVATE,,"111|102|103|105",,Dodoma,ACSEE,2026
```

---

## Best Practices

### Before Importing

1. **Validate data locally**
   ```bash
   # Check for duplicates
   cut -d, -f1 candidates.csv | sort | uniq -d
   
   # Count rows
   wc -l candidates.csv
   ```

2. **Verify references**
   ```bash
   # Check all combinations exist
   mysql -e "SELECT name FROM combinations;"
   
   # Check all schools exist
   mysql -e "SELECT code FROM schools;"
   
   # Check all districts exist
   mysql -e "SELECT name FROM districts;"
   ```

3. **Create backup**
   ```bash
   mysqldump -u root -p irms > backup-before-import.sql
   ```

### During Import

4. **Use staging first**
   - Import to staging environment
   - Verify results
   - Then import to production

5. **Monitor logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### After Import

6. **Verify results**
   ```bash
   # Count imported candidates
   mysql -e "SELECT COUNT(*) FROM candidates WHERE created_at > NOW() - INTERVAL 1 HOUR;"
   
   # Check subject allocations
   mysql -e "SELECT candidate_id, COUNT(*) as subject_count FROM candidate_subject_selections GROUP BY candidate_id;"
   ```

7. **Test workflows**
   - View candidate details
   - Check subject allocations
   - Test API endpoints

---

## Troubleshooting

### Import Hangs or Times Out

```bash
# Check PHP timeout
php -i | grep max_execution_time

# Increase if needed (php.ini)
max_execution_time = 300

# For very large imports, process in batches
# Split CSV into smaller files and import separately
```

### Memory Issues

```bash
# Increase memory limit
php -d memory_limit=512M artisan candidates:import-csv file.csv

# Or set in php.ini
memory_limit = 512M
```

### Encoding Issues

```
# Ensure CSV is UTF-8 encoded
file -i candidates.csv
# Should show: text/plain; charset=utf-8

# Convert if needed
iconv -f ISO-8859-1 -t UTF-8 input.csv > output.csv
```

---

## API Integration

### Import via API

```bash
curl -X POST http://localhost:8000/api/candidates/import \
  -F "file=@candidates.csv" \
  -F "type=school"
```

**Parameters:**
- `file`: CSV file (multipart form)
- `type`: `school` | `private` | `auto`

**Response:**
```json
{
  "success": true,
  "imported": 100,
  "failed": 2,
  "errors": [
    {"row": 5, "error": "Combination not found: INVALID"},
    {"row": 12, "error": "Missing General Studies"}
  ]
}
```

---

## Support & Contact

For import issues:
- Check error messages in logs
- Review validation rules above
- Verify data format matches template
- Use staging environment to test

---

**Version**: 1.0  
**Created**: 2026-02-15  
**Status**: Production Ready
