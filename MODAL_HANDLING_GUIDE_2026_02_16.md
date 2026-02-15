# Modal Handling Guide - Bulk Import Allocations
**Date**: 2026-02-16

---

## Current Modal State

**Screenshot shows**: Bulk Import Allocations modal is open with:
- CSV file selected: `private_allocation.csv`
- Exam Year: 2026
- Mode: Private (Subject Codes)
- File size: 1.35 KB
- Ready for validation

---

## Option 1: Close the Modal (If Needed)

### Click "Close" Button
Located at bottom left of modal

**Result**: Modal closes, returns to ACSEE Management page

---

## Option 2: Proceed with Import Validation

### Click "Validate CSV" Button
Located at bottom right of modal (blue button)

**Expected Flow**:
1. File uploads to server
2. CSV is parsed and validated
3. Preview table appears showing:
   - Row count
   - Candidate data
   - Subject codes
   - Status (NEW, ERROR, etc.)
4. Success message: "Validation complete: X rows scanned, X valid"
5. "Proceed to Import" button becomes enabled

---

## Troubleshooting Modal Issues

### If Modal Won't Close
**Solution**:
1. Click the X button (top right of modal)
2. Press Escape key
3. Click outside the modal (if allowed)

### If Validation Button Doesn't Respond
**Check**:
1. File is actually selected (see filename)
2. Exam year is selected (2026)
3. No JavaScript errors (F12 → Console)
4. Try clicking again

### If No Preview Appears After Validation
**Check**:
1. Browser console (F12) for errors
2. Network tab (F12) to see API request
3. Look for response status code
4. Check server logs: `tail -f storage/logs/laravel.log`

### If Error Message Appears
**Note**:
- Read the error message carefully
- Common errors:
  - "Invalid CSV format"
  - "Subject code not found"
  - "Candidate not found"
- Fix the CSV file and retry

---

## Browser Inspector (F12) - Debugging

### Open Developer Tools
- **Windows/Linux**: F12 or Ctrl+Shift+I
- **Mac**: Cmd+Option+I

### Check Console Tab
- Look for red error messages
- Note any JavaScript errors
- Check for network requests

### Check Network Tab
- Look for POST request to `/api/exam-types/acsee/allocate-from-csv/validate`
- Check response status (should be 200 for success)
- View response body for details

### Check Elements Tab
- Find the modal element: `<div role="dialog">`
- Look for error message elements
- Check form inputs

---

## Server-Side Debugging

### Monitor Logs in Real-Time
```bash
tail -f storage/logs/laravel.log
```

### Check Recent Errors
```bash
tail -20 storage/logs/laravel.log | grep -i error
```

### Test Endpoint Directly
```bash
# Check if route exists
php artisan route:list | grep "allocate-from-csv"

# Test via tinker
php artisan tinker
```

---

## Expected Behavior Flow

```
1. Modal Opens ✓ (Visible in screenshot)
   ↓
2. Click "Validate CSV"
   ↓
3. Loading indicator appears (spinner/progress)
   ↓
4. One of:
   A) Preview table + Success message
      ↓
      5. Click "Proceed to Import"
      ↓
      6. Import completes
      ↓
      7. Modal closes, data appears on page
   
   OR
   
   B) Error message appears
      ↓
      5. Fix CSV or settings
      ↓
      6. Try "Validate CSV" again
```

---

## Quick Decision Matrix

| Situation | Action |
|-----------|--------|
| Want to test import | Click "Validate CSV" |
| Want to cancel | Click "Close" |
| File not showing | Click "Select CSV File" again |
| Wrong exam year | Click dropdown and select 2026 |
| Wrong candidate type | Change "Private Only" setting |
| Got error message | Read carefully, fix CSV, retry |
| Preview didn't appear | Check F12 console, try again |

---

## Common Issues & Fixes

### Issue: "No file selected" error
**Fix**: Click "Select CSV File" and choose file again

### Issue: "Invalid exam year" error
**Fix**: Select exam year from dropdown (2026)

### Issue: Modal won't respond
**Fix**: Refresh page (F5), open modal again

### Issue: Validation takes too long
**Fix**: Wait 5-10 seconds, check console for errors

### Issue: Preview shows errors
**Fix**: Download template, check CSV format, fix issues

---

## Next Steps

**Choose one**:

1. **Test the Import**: Click "Validate CSV" and follow the flow
2. **Adjust Settings**: Change exam year or candidate type filter
3. **Close Modal**: Click "Close" to return to ACSEE page
4. **Upload Different File**: Click "Select CSV File" to choose different file

---

## Success Indicators

✅ CSV validates successfully
✅ Preview table appears with data
✅ 0 error count shown
✅ "Proceed to Import" button enabled
✅ Import completes
✅ Success message displayed
✅ Modal closes automatically
✅ New data visible on ACSEE page

---

## Support

For detailed information:
- **Manual Testing**: MANUAL_UI_TESTING_GUIDE_2026_02_16.md
- **Deployment**: DEPLOYMENT_CHECKLIST_CANDIDATE_IMPORT_2026_02_16.md
- **API**: scripts/test-candidate-import-api.php

---

**Status**: Ready to handle modal interactions
**Next Action**: Click "Validate CSV" or "Close" as desired
