# Candidate Import: Skip/Replace Feature

**Status**: ✅ **IMPLEMENTATION COMPLETE**  
**Date**: February 15, 2026  
**Ready**: Testing & Verification

---

## START HERE 👇

### For Quick Testing (15 minutes)
📖 **Read**: `HOW_TO_VERIFY_SKIP_REPLACE.md`

This guide will walk you through:
1. Setting up test data
2. Testing SKIP mode (validation + commit)
3. Testing REPLACE mode (validation + commit)
4. Verifying database changes

**Time**: 15 minutes  
**Outcome**: Confirms core functionality works

---

### For Complete Testing (1-2 hours)
📋 **Read**: `SKIP_REPLACE_VERIFICATION_CHECKLIST.md`

This document covers:
- All 10+ test cases from the test plan
- API curl commands for each scenario
- Database verification queries
- UI verification steps
- Safety rule validation
- Performance testing
- Sign-off checklist

**Time**: 1-2 hours  
**Outcome**: Comprehensive verification of all features

---

### For Technical Overview
📄 **Read**: `IMPLEMENTATION_COMPLETE_SUMMARY.md`

Summary of:
- What was implemented
- How to verify (overview)
- File locations
- Quality assurance
- Next steps

**Time**: 10 minutes  
**Outcome**: Understand what was built and why

---

### For Quick Reference
📋 **Read**: `SKIP_REPLACE_QUICK_REFERENCE.txt`

One-page cheat sheet:
- Mode comparison
- API response examples
- Status badges
- Data safety rules
- FAQ

**Time**: 5 minutes  
**Outcome**: Quick lookup for common questions

---

## What Was Implemented

### Two Modes for Handling Existing Candidates

**SKIP Mode** (Default, Safe)
- Ignore existing candidates
- Create only new ones
- No modifications to data

**REPLACE Mode** (Update)
- Update existing candidate details
- Updates: name, gender, school
- Protects: ID, exam type, marks, registrations

### Key Features

✅ Two-phase workflow (validate, then commit)  
✅ Clear preview before importing  
✅ Per-row status tracking (NEW, SKIP, REPLACE, ERROR)  
✅ Accurate counts (create, update, skip, errors)  
✅ Data safety: transactional, no deletes  
✅ Backward compatible (no breaking changes)  

---

## Files Modified (3)

```
app/Http/Controllers/CandidateImportController.php
app/Services/Candidates/CandidateImportService.php
resources/views/registration/candidates.blade.php
```

---

## Quick Test Commands

```bash
# Setup test data
php artisan tinker
>>> # Create S0754-0001 and S0754-0002

# Create test CSV
cat > /tmp/test.csv << 'CSV'
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0001,JOHN PETER DOE,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0003,NEW STUDENT,M,S0754,CBE,ACSEE,2026,SCHOOL
S0754-0002,JANE MARIE SMITH,F,S0754,HGE,ACSEE,2026,SCHOOL
CSV

# Test SKIP mode validation
curl -s -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test.csv" \
  -F "on_exists_mode=skip" | jq .

# Expected: create_count=1, skip_count=2, update_count=0

# Test REPLACE mode validation
curl -s -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test.csv" \
  -F "on_exists_mode=replace" | jq .

# Expected: create_count=1, skip_count=0, update_count=2
```

---

## Testing Path

1. **Quick Test** (15 min)
   - Follow `HOW_TO_VERIFY_SKIP_REPLACE.md`
   - Verify skip and replace modes work

2. **Full Test** (1-2 hours)
   - Use `SKIP_REPLACE_VERIFICATION_CHECKLIST.md`
   - Test all 10+ scenarios
   - Verify database integrity
   - Check UI changes

3. **Sign Off**
   - Mark tests complete
   - Confirm no blockers
   - Ready for deployment

---

## API Contract

### Validate Endpoint

**Request:**
```
POST /api/candidates/import/validate
Parameters: file, exam_year, exam_type, on_exists_mode (optional)
```

**Response:**
```json
{
  "total_rows": 3,
  "create_count": 1,
  "update_count": 0,
  "skip_count": 2,
  "error_count": 0,
  "can_import": true,
  "rows": [
    {"row_number": 1, "status": "SKIP"},
    {"row_number": 2, "status": "NEW"},
    {"row_number": 3, "status": "SKIP"}
  ]
}
```

### Commit Endpoint

**Request:**
```
POST /api/candidates/import/commit
Parameters: file, exam_year, exam_type, on_exists_mode
```

**Response:**
```json
{
  "success": true,
  "created_count": 1,
  "updated_count": 0,
  "skipped_count": 2,
  "failed_count": 0,
  "errors": []
}
```

---

## Data Safety

### Guaranteed Protections

✅ No candidate deletions  
✅ No exam registration deletions  
✅ No marks deletions  
✅ No results deletions  
✅ Transactional commits (all-or-nothing)  
✅ Duplicate detection  

### Replace Mode Safe Fields

**Updates Only:**
- full_name
- gender
- school_id

**Never Changes:**
- candidate_id
- exam_type
- combination

---

## Document Index

| Document | Purpose | Time | Audience |
|----------|---------|------|----------|
| HOW_TO_VERIFY_SKIP_REPLACE.md | Quick test walkthrough | 15 min | QA, Dev |
| SKIP_REPLACE_VERIFICATION_CHECKLIST.md | Complete test plan | 1-2 hr | QA, Dev |
| IMPLEMENTATION_COMPLETE_SUMMARY.md | Overview of implementation | 10 min | All |
| SKIP_REPLACE_QUICK_REFERENCE.txt | One-page cheat sheet | 5 min | All |
| README_SKIP_REPLACE.md | This file | - | All |

---

## Quick FAQ

**Q: Will existing candidates be modified?**  
A: Only in REPLACE mode. SKIP mode leaves them untouched.

**Q: Will marks be deleted?**  
A: No. Never. REPLACE mode only updates name, gender, school.

**Q: Do I need to specify on_exists_mode?**  
A: Optional. Defaults to "skip" if missing.

**Q: How long is testing?**  
A: 15 min for quick test, 1-2 hours for full plan.

**Q: Can I rollback if issues?**  
A: Yes. No database migrations, so just revert 3 files.

---

## Next Steps

### Immediate (Now)
1. Read `HOW_TO_VERIFY_SKIP_REPLACE.md` (15 min)
2. Run the quick tests
3. Confirm SKIP and REPLACE modes work

### Short Term (This Session)
1. Read `SKIP_REPLACE_VERIFICATION_CHECKLIST.md`
2. Run all 10+ test cases
3. Verify database integrity
4. Test UI changes

### Before Deployment
1. Complete all tests
2. Sign off on verification checklist
3. Monitor logs post-deployment

---

## Support

### For Developers
- Implementation details: `IMPLEMENTATION_COMPLETE_SUMMARY.md`
- Code files: 3 modified files (controller, service, blade)

### For QA
- Quick test: `HOW_TO_VERIFY_SKIP_REPLACE.md`
- Full test plan: `SKIP_REPLACE_VERIFICATION_CHECKLIST.md`

### For Everyone
- Quick reference: `SKIP_REPLACE_QUICK_REFERENCE.txt`
- This README: `README_SKIP_REPLACE.md`

---

**Status**: ✅ Implementation Complete  
**Next Action**: Start with `HOW_TO_VERIFY_SKIP_REPLACE.md` (15 min)  
**Questions?**: Check `SKIP_REPLACE_QUICK_REFERENCE.txt` FAQ section

