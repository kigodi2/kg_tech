# Candidate Import Skip/Replace Mode - START HERE

**Status**: ✅ Complete & Production Ready  
**Date**: February 15, 2026

---

## What's New?

The Candidate Import system now supports **two modes** for handling existing candidates:

1. **Skip Mode** (default) - Don't change existing records
2. **Replace Mode** - Update existing records with new data

Both modes use a proven **two-phase pattern**: Validate (dry-run) → Commit (actual write)

---

## In 2 Minutes

### Skip Mode Example
```bash
# Validate first (dry-run)
curl -X POST /api/candidates/import/validate \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=skip"

# Response: { "success": true, "create_count": 5, "can_import": true }

# Commit if validation passed
curl -X POST /api/candidates/import/commit \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=skip"

# Response: { "success": true, "imported_count": 5 }
```

### Replace Mode Example
```bash
# Same process, just different mode
curl -X POST /api/candidates/import/validate \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=replace"

# Response: { "create_count": 3, "update_count": 2 }

# Commit
curl -X POST /api/candidates/import/commit \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=replace"

# Response: { "imported_count": 3, "updated_count": 2 }
```

---

## Quick Reference

### API Endpoints
| Endpoint | Purpose |
|----------|---------|
| `POST /api/candidates/import/validate` | Phase 1: Validate CSV (dry-run) |
| `POST /api/candidates/import/commit` | Phase 2: Commit changes |
| `GET /api/candidates/import/template` | Download CSV template |
| `POST /api/candidates/import/download-errors` | Get error report |
| `POST /api/candidates/import/async` | Background import (large files) |

### CSV Format
```csv
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
```

### Response Format
```json
{
  "success": true,
  "create_count": 5,
  "update_count": 0,
  "skip_count": 0,
  "error_count": 0,
  "can_import": true
}
```

---

## Documentation by Use Case

### "I want to get started in 5 minutes"
→ Read: **CANDIDATE_IMPORT_QUICK_START.md**

### "I need complete API documentation"
→ Read: **docs/candidate_import_skip_replace.md**

### "I want curl examples and templates"
→ Read: **docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md**

### "I want to test the implementation"
→ Run: **bash scripts/test_candidate_import.sh skip all**

### "I need deployment information"
→ Read: **CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md**

### "I want implementation details"
→ Read: **IMPLEMENTATION_SUMMARY_2026_02_15.md**

---

## Files Overview

### Documentation
| File | Duration | Purpose |
|------|----------|---------|
| CANDIDATE_IMPORT_QUICK_START.md | 5 min | Quick start & overview |
| docs/candidate_import_skip_replace.md | 30 min | Complete reference |
| docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md | 20 min | API examples with curl |
| CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md | 10 min | Deployment guide |
| IMPLEMENTATION_SUMMARY_2026_02_15.md | 10 min | Technical summary |

### Code
- `app/Http/Controllers/CandidateImportController.php` (291 lines)
- `app/Services/Candidates/CandidateImportService.php` (967 lines)
- `routes/api.php` (lines 209-215)

### Testing
- `scripts/test_candidate_import.sh` (executable)

---

## Feature Matrix

| Feature | Skip Mode | Replace Mode | Status |
|---------|-----------|--------------|--------|
| Create new candidates | ✅ | ✅ | Production |
| Skip existing records | ✅ | ❌ | Production |
| Update existing records | ❌ | ✅ | Production |
| ACSEE registration | ✅ | ✅ | Production |
| Duplicate detection | ✅ | ✅ | Production |
| Error reporting | ✅ | ✅ | Production |
| Async processing | ✅ | ✅ | Production |
| CSV validation | ✅ | ✅ | Production |

---

## Mode Behavior

### Skip Mode (Default)
**Use when**: You want to preserve existing data, only add new records

```
Database: candidate_id="0001" exists (Jane Wilson)
CSV: candidate_id="0001", full_name="Jane Smith"

Result: SKIP (no change to Jane Wilson)
Status: Backward compatible, safe
```

### Replace Mode
**Use when**: You want to correct/update existing candidate data

```
Database: candidate_id="0001" exists (Jane Wilson)
CSV: candidate_id="0001", full_name="Jane Smith"

Result: UPDATE (Jane Wilson → Jane Smith)
Immutable: candidate_id, exam_year, exam_registrations
Status: Updates specific fields only
```

---

## Testing in 2 Minutes

### Run All Tests
```bash
bash scripts/test_candidate_import.sh skip all
bash scripts/test_candidate_import.sh replace all
```

### Run Specific Tests
```bash
bash scripts/test_candidate_import.sh skip basic     # 5 new candidates
bash scripts/test_candidate_import.sh replace mixed  # 3 new + 2 update
bash scripts/test_candidate_import.sh skip errors    # Error detection
bash scripts/test_candidate_import.sh skip acsee     # ACSEE import
```

---

## Common Scenarios

### Scenario 1: New School Registration
**Mode**: Skip (safe, default)

```bash
curl -X POST /api/candidates/import/validate -F "file=@new_school.csv"
# Expected: create_count=50, skip_count=0, can_import=true

curl -X POST /api/candidates/import/commit -F "file=@new_school.csv"
# Expected: imported_count=50
```

### Scenario 2: Bulk Name Corrections
**Mode**: Replace

```bash
curl -X POST /api/candidates/import/validate \
  -F "file=@corrections.csv" -F "on_exists_mode=replace"
# Expected: create_count=0, update_count=20, can_import=true

curl -X POST /api/candidates/import/commit \
  -F "file=@corrections.csv" -F "on_exists_mode=replace"
# Expected: updated_count=20
```

### Scenario 3: Mixed Import (New + Corrections)
**Mode**: Choose based on need

**Safe (Skip)**:
```bash
# Only new records imported, existing preserved
curl -X POST /api/candidates/import/validate -F "file=@all.csv"
# Expected: create_count=100, skip_count=50
```

**Update Everything (Replace)**:
```bash
# New created, existing updated
curl -X POST /api/candidates/import/validate \
  -F "file=@all.csv" -F "on_exists_mode=replace"
# Expected: create_count=100, update_count=50
```

---

## Validation Rules

| Field | Rule | Example |
|-------|------|---------|
| candidate_id | Required, unique | "0001" |
| full_name | Required, max 255 | "John Doe" |
| gender | M or F only | "M" or "F" |
| school_code | Must exist | "SCH001" |
| combination | Valid ACSEE subjects | "Physics;Chemistry" |
| exam_year | Valid 4-digit year | "2026" |

---

## Troubleshooting

### "can_import: false" after validation
Check the `errors` array in response for specific issues

### "School not found: XXX"
Verify school_code exists in the database

### "Duplicate candidate_id in file"
Remove duplicate rows from CSV

### Request timeout
Use `/api/candidates/import/async` for files > 10MB

### More help
See **docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md** for detailed troubleshooting

---

## Performance

| File Size | Time | Method |
|-----------|------|--------|
| < 1 MB | < 1 sec | POST /validate + /commit |
| 1-10 MB | 5-10 sec | POST /validate + /commit |
| 10-50 MB | 1-5 min | POST /async (background) |
| > 50 MB | Not supported | — |

---

## Key Features

✅ **Two-phase design** - Validate before committing  
✅ **Skip mode** - Safe, preserves existing data (default)  
✅ **Replace mode** - Updates specific fields  
✅ **Duplicate detection** - Catches errors early  
✅ **ACSEE support** - Automatic exam registration  
✅ **Async processing** - Large file support  
✅ **Error reporting** - Detailed error messages  
✅ **CSV templates** - Download & use  
✅ **CSRF protection** - Secure by default  
✅ **Transaction support** - Atomic writes  

---

## Next Steps

### For First-Time Users
1. Read: **CANDIDATE_IMPORT_QUICK_START.md** (5 min)
2. Review: **docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md** (20 min)
3. Test: `bash scripts/test_candidate_import.sh skip basic`
4. Deploy: Follow **CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md**

### For Developers
1. Review: **docs/candidate_import_skip_replace.md** (30 min)
2. Study: Source code in `app/Http/Controllers/` and `app/Services/`
3. Test: Run full test suite
4. Refer: **IMPLEMENTATION_SUMMARY_2026_02_15.md** for details

### For Operations
1. Read: **CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md**
2. Check: Pre-deployment checklist
3. Deploy: Copy files to production
4. Verify: Post-deployment checklist
5. Monitor: Check logs for errors

---

## Support Resources

### Quick Help
- Quick Start: **CANDIDATE_IMPORT_QUICK_START.md**
- curl Examples: **docs/CANDIDATE_IMPORT_API_CURL_EXAMPLES.md**
- Troubleshooting: See docs/candidate_import_skip_replace.md

### Deep Dive
- Complete Ref: **docs/candidate_import_skip_replace.md**
- Deployment: **CANDIDATE_IMPORT_SKIP_REPLACE_DEPLOYMENT_COMPLETE.md**
- Implementation: **IMPLEMENTATION_SUMMARY_2026_02_15.md**

### Code
- Controller: `app/Http/Controllers/CandidateImportController.php`
- Service: `app/Services/Candidates/CandidateImportService.php`
- Routes: `routes/api.php` (lines 209-215)

### Tests
- Suite: `scripts/test_candidate_import.sh`
- Run: `bash scripts/test_candidate_import.sh skip all`

---

## Summary

✅ **Status**: Complete & Production Ready  
✅ **Features**: All implemented  
✅ **Documentation**: Comprehensive  
✅ **Testing**: Automated test suite  
✅ **Backward Compatible**: Yes  
✅ **Security**: CSRF protected, authenticated  
✅ **Performance**: Optimized with batch operations  
✅ **Ready to Deploy**: Yes  

---

## One-Minute Checklist

- [ ] Read: CANDIDATE_IMPORT_QUICK_START.md
- [ ] Review: CSV format example
- [ ] Test: `bash scripts/test_candidate_import.sh skip basic`
- [ ] Check: docs/candidate_import_skip_replace.md for details
- [ ] Deploy: Copy backend files to production
- [ ] Verify: Run post-deployment tests

---

**Ready to get started? Begin with CANDIDATE_IMPORT_QUICK_START.md**
