# Candidate Import Skip/Replace - Quick Start Guide

## What's New?

The Candidate Import system now supports **two modes**:

- **Skip Mode** (default): Don't change existing candidates
- **Replace Mode**: Update existing candidates with new data

Both modes use a **two-phase process**: Validate → Commit

---

## 30-Second Overview

### Skip Mode Example
```bash
# Upload CSV with 5 new candidates
curl -X POST http://localhost:8000/api/candidates/import/validate \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=skip"

# Returns: create_count: 5, skip_count: 0
# → All 5 will be imported

# Commit the import
curl -X POST http://localhost:8000/api/candidates/import/commit \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=skip"

# Returns: imported_count: 5
```

### Replace Mode Example
```bash
# Upload CSV with 3 new + 2 existing candidates
curl -X POST http://localhost:8000/api/candidates/import/validate \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=replace"

# Returns: create_count: 3, update_count: 2
# → 3 new will be created, 2 existing will be updated

# Commit the import
curl -X POST http://localhost:8000/api/candidates/import/commit \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=replace"

# Returns: imported_count: 3, updated_count: 2
```

---

## Files You Need to Know

| File | Purpose |
|------|---------|
| `docs/candidate_import_skip_replace.md` | Complete documentation (64 KB) |
| `docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md` | curl examples & templates (12 KB) |
| `CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md` | Deployment status & checklist (15 KB) |
| `scripts/test_candidate_import.sh` | Automated test suite (executable) |

---

## CSV Format (2 Minutes)

```csv
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
```

**Required columns:**
- `candidate_id` - Unique identifier
- `full_name` - Candidate name
- `gender` - M or F
- `school_code` - School identifier

**Optional columns:**
- `combination` - Subjects for ACSEE (comma-separated)
- `exam_type` - PSLE|CSEE|ACSEE (default from modal)
- `exam_year` - 4-digit year (default from modal)

---

## API Endpoints

### 1. Validate (Phase 1: Dry-run)
```
POST /api/candidates/import/validate
Parameters:
  - file (required): CSV file
  - on_exists_mode (optional): skip|replace (default: skip)
  - exam_year (optional): 2026
  - exam_type (optional): ACSEE

Response:
{
  "success": true,
  "create_count": 5,
  "update_count": 0,
  "skip_count": 0,
  "error_count": 0,
  "can_import": true
}
```

### 2. Commit (Phase 2: Actual write)
```
POST /api/candidates/import/commit
Parameters: (same as validate)

Response:
{
  "success": true,
  "imported_count": 5,
  "updated_count": 0,
  "skipped_count": 0,
  "errors": []
}
```

### 3. Other Endpoints
- `GET /api/candidates/import/template` - Download CSV template
- `POST /api/candidates/import/download-errors` - Get error report
- `POST /api/candidates/import/async` - Background import (large files)

---

## Mode Behavior

### Skip Mode (Default)
**Use when**: You want to preserve existing data, only add new records

```
Database: candidate_id="0001" exists (Jane Wilson)
CSV Row: candidate_id="0001", full_name="Jane Smith"

Result: Database unchanged, Jane Wilson stays (no update)
Status: SKIP
```

### Replace Mode
**Use when**: You want to update existing candidate data

```
Database: candidate_id="0001" exists (Jane Wilson)
CSV Row: candidate_id="0001", full_name="Jane Smith"

Result: Database updated, now Jane Smith
Status: REPLACE (updates only full_name, gender, combination, school_id)
```

**Immutable fields** (never updated):
- `candidate_id` - The unique key
- `exam_year` - Once registered, preserved
- `exam_registrations` - Append-only

---

## Testing

### Run All Tests
```bash
bash scripts/test_candidate_import.sh skip all
bash scripts/test_candidate_import.sh replace all
```

### Run Specific Test
```bash
bash scripts/test_candidate_import.sh skip basic     # 5 new candidates
bash scripts/test_candidate_import.sh replace mixed  # 3 new + 2 existing
bash scripts/test_candidate_import.sh skip errors    # Error detection
bash scripts/test_candidate_import.sh skip acsee     # ACSEE import
```

### Manual Test with curl
```bash
# Create test file
cat > test.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
EOF

# Test validation
curl -X POST http://localhost:8000/api/candidates/import/validate \
  -H "X-CSRF-TOKEN: your-token" \
  -F "file=@test.csv" \
  -F "on_exists_mode=skip" | jq '.'

# Test commit (if validation passed)
curl -X POST http://localhost:8000/api/candidates/import/commit \
  -H "X-CSRF-TOKEN: your-token" \
  -F "file=@test.csv" \
  -F "on_exists_mode=skip" | jq '.'
```

---

## Common Scenarios

### Scenario 1: New School Batch
**Use**: Skip mode (all new candidates)

```bash
curl -X POST /api/candidates/import/validate \
  -F "file=@new_school.csv" \
  -F "on_exists_mode=skip"
# Expected: create_count=50, skip_count=0

curl -X POST /api/candidates/import/commit \
  -F "file=@new_school.csv" \
  -F "on_exists_mode=skip"
# Expected: imported_count=50
```

### Scenario 2: Name Corrections
**Use**: Replace mode (update existing names)

```bash
curl -X POST /api/candidates/import/validate \
  -F "file=@corrections.csv" \
  -F "on_exists_mode=replace"
# Expected: create_count=0, update_count=20

curl -X POST /api/candidates/import/commit \
  -F "file=@corrections.csv" \
  -F "on_exists_mode=replace"
# Expected: updated_count=20
```

### Scenario 3: Mixed Import
**Use**: Skip mode for safe import, Replace mode for full sync

```bash
# Skip mode: Safe, never loses data
curl -X POST /api/candidates/import/validate \
  -F "file=@all_candidates.csv" \
  -F "on_exists_mode=skip"
# Expected: create_count=100, skip_count=50

# Replace mode: Updates everything
curl -X POST /api/candidates/import/validate \
  -F "file=@all_candidates.csv" \
  -F "on_exists_mode=replace"
# Expected: create_count=100, update_count=50
```

---

## Error Handling

### Validation Errors
```json
{
  "success": false,
  "error_count": 2,
  "errors": [
    {
      "row_number": 3,
      "error_messages": ["Gender must be M or F"]
    },
    {
      "row_number": 5,
      "error_messages": ["School not found: INVALID"]
    }
  ],
  "can_import": false
}
```

**Action**: Fix errors and re-submit

### Download Error Report
```bash
curl -X POST /api/candidates/import/download-errors \
  -H "Content-Type: application/json" \
  -d '{"errors": [...]}' \
  -o errors.csv
```

---

## Performance Tips

| File Size | Method | Time |
|-----------|--------|------|
| < 1MB | POST /validate + /commit | < 1 sec |
| 1-10MB | POST /validate + /commit | 5-10 sec |
| 10-50MB | POST /async | 1-5 min (background) |
| > 50MB | Not supported | — |

**Large File Import**:
```bash
curl -X POST /api/candidates/import/async \
  -F "file=@large_file.csv" \
  -F "on_exists_mode=skip"
# Returns: import_id (processing in background)
```

---

## Validation Rules

| Field | Rule | Error |
|-------|------|-------|
| candidate_id | Required, unique | "Candidate ID is required" |
| full_name | Required, max 255 chars | "Full name is required" |
| gender | M or F only | "Gender must be M or F" |
| school_code | Must exist | "School not found: XXX" |
| combination | Valid for ACSEE | "Combination not found" |
| exam_year | Valid year format | "Invalid exam year" |

---

## Implementation Files

### Backend
- **Controller**: `app/Http/Controllers/CandidateImportController.php` (31-118 lines)
- **Service**: `app/Services/Candidates/CandidateImportService.php` (40-717 lines)
- **Routes**: `routes/api.php` (209-215 lines)

### Frontend
- **Modal**: `resources/views/registration/candidates.blade.php`

### Documentation
- **Main**: `docs/candidate_import_skip_replace.md`
- **Examples**: `docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md`
- **Status**: `CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md`
- **Tests**: `scripts/test_candidate_import.sh`

---

## Troubleshooting

### "CSV file is empty"
→ Check file has data rows (not just header)

### "Duplicate candidate_id in file"
→ Remove duplicate rows from CSV

### "School not found: XXX"
→ Verify school_code exists in database

### "Gender must be M or F"
→ Use only M or F (case-insensitive)

### "can_import: false"
→ Check errors array for specific issues

### Request timeout
→ Use `/api/candidates/import/async` for files > 10MB

---

## Next Steps

1. **Review full documentation** → `docs/candidate_import_skip_replace.md`
2. **Download CSV template** → `GET /api/candidates/import/template`
3. **Test with sample data** → `bash scripts/test_candidate_import.sh skip basic`
4. **Check API examples** → `docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md`
5. **Deploy to production** → Follow deployment checklist

---

## Support

**Documentation**:
- Complete reference: `docs/candidate_import_skip_replace.md`
- curl examples: `docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md`

**Testing**:
- Automated tests: `bash scripts/test_candidate_import.sh`
- curl templates: See examples doc

**Status**:
- Deployment complete: `CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md`
- Ready for production ✅

---

## Summary

**What You Get**:
✅ Two import modes (skip & replace)
✅ Two-phase validation & commit
✅ Complete error reporting
✅ ACSEE exam registration
✅ Async processing for large files
✅ Full API documentation
✅ curl examples and test scripts

**Time to Deploy**: < 5 minutes  
**Risk Level**: Low (backward compatible)  
**Status**: Production Ready ✅

---

**Questions?** Refer to the main documentation or check the test script for working examples.
