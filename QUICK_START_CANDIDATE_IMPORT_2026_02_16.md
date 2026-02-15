# Quick Start: Candidate Import with Auto-Subject Allocation

## TL;DR
- Upload CSV to import candidates
- System automatically allocates subjects for PRIVATE candidates
- Exam year comes from UI dropdown (no need in CSV)
- Works with or without exam_year column

## CSV Format

### SCHOOL Candidates
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S0001,John School,M,P0652,SCHOOL,PCM,
S0002,Jane School,F,P1770,SCHOOL,HGL,
```

### PRIVATE Candidates
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
P0001,John Private,M,P0652,PRIVATE,,111|102|103|121
P0002,Jane Private,F,P1770,PRIVATE,,111|121|122|
```

### Mixed Import
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S0001,John School,M,P0652,SCHOOL,PCM,
P0001,Jane Private,F,P0652,PRIVATE,,111|102|103|121
```

## Column Definitions

| Column | Required | Notes |
|--------|----------|-------|
| `candidate_id` | YES | Unique identifier (e.g., S0001, P0001) |
| `full_name` | YES | Candidate's full name |
| `gender` | YES | M or F |
| `school_code` | YES | School code (e.g., P0652) |
| `candidate_type` | YES | SCHOOL or PRIVATE |
| `combination` | SCHOOL | Combination code (e.g., PCM, HGL). Leave blank for PRIVATE. |
| `subjects` | PRIVATE | Pipe-separated subject codes (e.g., 111\|102\|103). Leave blank for SCHOOL. |
| `exam_type` | NO | PSLE, CSEE, ACSEE (default: ACSEE from UI) |
| `exam_year` | NO | 4-digit year (2024, 2025, 2026). Default from UI dropdown. |

## Step-by-Step Import

### 1. Navigate to Candidates Page
- Go to Dashboard → Registration → Candidates

### 2. Click "Import Candidates"
- Opens import modal

### 3. Select Exam Year
- Dropdown shows available years (2024, 2025, 2026)
- **Default: 2026**
- This is applied to ALL candidates in the import

### 4. Select Import Mode
- **Skip**: Don't import if candidate already exists
- **Replace**: Update existing candidates (safe - only updates name/gender/school)

### 5. Upload CSV File
- Drag and drop or click to select
- CSV format as shown above

### 6. Review Validation Results
- See how many will be created/updated/skipped
- Check for any errors
- If errors exist, download error report and fix CSV

### 7. Confirm and Import
- Click "Import" button
- System processes in batches of 100
- Provides progress feedback

### 8. Verify on ACSEE Page
- Go to Dashboard → Exams → ACSEE Management
- For PRIVATE candidates:
  - Filter by Year = 2026
  - Filter by Candidate Type = PRIVATE
  - Verify "Allocated Subjects" column shows the subjects from your CSV

## Automatic Subject Allocation for PRIVATE Candidates

When you import PRIVATE candidates with subjects specified:

```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
P0001,Jane Private,F,P0652,PRIVATE,,111|102|103|121
```

The system automatically:
1. ✓ Creates the candidate record
2. ✓ Registers them for ACSEE 2026
3. ✓ Allocates subjects 111, 102, 103, 121
4. ✓ Validates subject combination (NECTA rules)
5. ✓ Marks subjects as principal (PRIVATE candidates don't follow NECTA principal subject rules)

You can then view and manage these allocations on the ACSEE page.

## Subject Codes Reference

Common ACSEE subject codes:
- **111**: General Studies (GS) - Optional for PRIVATE
- **102**: Physics
- **103**: Chemistry
- **104**: Biology
- **121**: Mathematics (Additional)
- **122**: Geography
- **123**: History
- **124**: Civics
- And many more...

## Skip vs Replace Mode

### Skip Mode (Default)
- Existing candidates are NOT imported again
- Useful for incremental imports
- Safe - no data is changed

Example:
```
Import 1: 10 candidates → All 10 created
Import 2: Same 10 + 5 new → Only 5 new created, 10 skipped
```

### Replace Mode
- Existing candidates are UPDATED (only name, gender, school)
- Useful for correcting data
- Safe - candidate_id, exam_type, combination NOT changed

Example:
```
Import 1: S0001 John School, PCM
Import 2: S0001 John Smith (updated name), PCM → Name updated only
```

## Error Handling

### Common Errors

**"school_code not found: P0999"**
- School doesn't exist in database
- Fix: Use valid school code (e.g., P0652, P1770)

**"combination code not found: XYZ"**
- Combination doesn't exist for ACSEE
- Fix: Use valid combination (PCM, HGL, PME, etc.)

**"candidate_id is duplicated within this file"**
- Same candidate_id appears twice in CSV
- Fix: Each candidate_id must be unique

**"full_name is required"**
- Missing candidate name
- Fix: Provide full_name for each row

### Download Error Report
- If validation fails, click "Download Error Report"
- Shows exact row numbers and errors
- Use to fix CSV and retry

## Testing

### Test Import (SCHOOL)
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S9999,Test School,M,P0652,SCHOOL,PCM,
```

Expected: 1 candidate created, registered for ACSEE with PCM subjects

### Test Import (PRIVATE)
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
P9999,Test Private,F,P0652,PRIVATE,,111|102|103|121
```

Expected: 1 candidate created, registered for ACSEE with subjects allocated

### Verify on ACSEE Page
1. Go to `/exam-types/acsee`
2. Filter: Year = 2026, Type = PRIVATE
3. Check "Allocated Subjects" column for P9999
4. Should show: 111, 102, 103, 121

## FAQ

**Q: Can I include exam_year in the CSV?**
A: Yes, it's optional. If included, it's validated against the selected year.

**Q: What if a subject code is invalid?**
A: Import fails with validation error. Download report to see which subjects are invalid.

**Q: Can I import without selecting exam year?**
A: No, exam year is required. Default is 2026 when modal opens.

**Q: What happens if I import same candidate twice?**
A: First import: created. Second import (Skip mode): skipped. Second import (Replace mode): name/gender/school updated only.

**Q: Do PRIVATE subjects need General Studies (111)?**
A: No, 111 is optional. Minimum 1 subject required.

**Q: Can a PRIVATE candidate have multiple subjects?**
A: Yes, use pipe separator: `111|102|103|121`

## Troubleshooting

### Import Validation Fails
1. Download error report
2. Check row numbers and errors
3. Fix CSV
4. Re-upload

### Subjects Not Appearing on ACSEE Page
1. Check candidate type is "PRIVATE"
2. Check exam year is "2026" (matching your import)
3. Refresh page (Ctrl+F5)
4. Check browser console for errors

### Duplicate Candidates Error
1. Each candidate_id must be unique in the file
2. Check for typos (S0001 vs S00O1)
3. Remove duplicates from CSV

## Support
See `/api/candidates/import/template` for example template download
