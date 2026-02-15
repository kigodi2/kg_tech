# Production UI Test Execution - Step by Step
**Date**: 2026-02-16  
**Status**: IN PROGRESS  
**Reference**: @T-019c633e-3cde-7159-8a60-bec226565fd2

---

## STEP 1: Click "Validate CSV" Button

### Current State
- URL: `http://127.0.0.1:8001/exam-types/acsee`
- Modal: "Bulk Import Allocations" is open
- CSV Selected: `private_allocation.csv (1.35 KB)`
- Exam Year: `2026`
- Mode: `Private (Subject Codes)` ← Selected
- Import Type: `Private Only`

### What to Expect
The system will:
1. Read the CSV file
2. Validate each row for:
   - Valid candidate IDs
   - Valid subject codes
   - Proper formatting
3. Show results in a preview table
4. Display any errors

### Expected Success Result
```
Preview Table Should Show:
- Row count
- Candidate IDs
- Subject codes
- Status (NEW, ERROR, etc.)
- No error messages

Summary Should Show:
- Create count: X candidates
- Error count: 0
- "All rows valid" message
```

### Expected Error Handling
If errors occur:
- Error message displays clearly
- Row numbers shown
- Specific issue identified
- "Validate CSV" button stays active for retry

---

## STEP 2: Review Validation Results

### Check For:
- [ ] Preview table appears
- [ ] Row data displayed correctly
- [ ] Error count shows 0
- [ ] "All rows valid" message visible
- [ ] "Proceed to Import" or "Import" button enabled

### If Errors Found:
- [ ] Note error message exactly
- [ ] Check row numbers
- [ ] Review CSV format
- [ ] Correct CSV and retry

---

## STEP 3: Click "Proceed to Import" Button

### What Happens
1. System processes the import
2. Database records created/updated
3. Success message displayed
4. Modal closes automatically (or manually)

### Expected Result
```
Success Message:
"Successfully imported X candidates"
or
"X allocations created"

Action: Modal closes, returns to ACSEE page
```

---

## STEP 4: Verify Results on ACSEE Page

### Check:
- [ ] Page reloaded/refreshed
- [ ] Candidates still visible
- [ ] Imported candidates show in table
- [ ] No error messages in console (F12)

### Browser Console (F12)
- [ ] Check Console tab
- [ ] No red error messages
- [ ] No JavaScript exceptions
- [ ] Network requests successful

---

## STEP 5: Search for Imported Candidates

### On ACSEE Page:
- [ ] Use search box to find imported candidates
- [ ] Verify candidates appear in results
- [ ] Check "Allocated Subjects" column

### Expected Data:
- Candidate IDs from CSV visible
- Subject codes displayed
- Year: 2026 showing
- Status: Active

---

## STEP 6: Click on One Candidate (Optional)

### View Details:
- [ ] Modal or detail page opens
- [ ] Shows candidate info
- [ ] Shows allocated subjects
- [ ] Shows year (2026)
- [ ] Shows all subject codes from CSV

---

## Success Criteria - All Met = ✅ PASS

- [x] Modal opens without errors (CONFIRMED)
- [ ] CSV validates successfully
- [ ] Preview table displays correctly
- [ ] No validation errors
- [ ] Import completes successfully
- [ ] Success message appears
- [ ] Candidates visible on ACSEE page
- [ ] Allocated subjects displayed
- [ ] No errors in browser console
- [ ] No errors in server logs

---

## Troubleshooting If Issues Occur

### If CSV Validation Fails:
1. Check error message content
2. Verify CSV format
3. Check subject codes exist in database
4. Review file for blank rows
5. Try uploading again

### If Import Fails:
1. Check browser console (F12)
2. Check server logs: `tail -f storage/logs/laravel.log`
3. Verify database connection
4. Try validating CSV again
5. Check for database errors

### If Candidates Don't Appear:
1. Refresh page (F5)
2. Clear browser cache (Ctrl+Shift+Delete)
3. Check ACSEE Management filters
4. Search with exact candidate ID
5. Check database directly

---

## Current Screenshot Status

✅ Modal: Open and functional
✅ CSV: Loaded (1.35 KB)
✅ Exam Year: 2026 selected
✅ Mode: Private selected
✅ Form: Ready for validation

**Ready for Step 1: Click "Validate CSV"**

---

## Command Line Verification (Parallel)

While testing UI, you can also run:

```bash
# Check logs in real-time
tail -f storage/logs/laravel.log

# Run API tests
php scripts/test-candidate-import-api.php

# Check database
php artisan tinker
>>> DB::table('candidates')->where('candidate_type', 'PRIVATE')->count();
>>> DB::table('candidate_subject_selections')->count();
```

---

## Documentation Reference

For detailed information:
- **Manual Testing Guide**: MANUAL_UI_TESTING_GUIDE_2026_02_16.md
- **API Tests**: scripts/test-candidate-import-api.php
- **Deployment Checklist**: DEPLOYMENT_CHECKLIST_CANDIDATE_IMPORT_2026_02_16.md

---

**Next Action**: Click "Validate CSV" button and report results.

Expected: CSV validates successfully with 0 errors and shows preview table.
