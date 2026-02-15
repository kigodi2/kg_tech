# Manual UI Testing Guide - Candidate Import System
**Date**: 2026-02-16  
**Component**: Candidate Import with ACSEE Auto-Allocation  
**Duration**: ~15-20 minutes  
**Tester**: [Your Name]

---

## Prerequisites

- [ ] Application running on localhost:8000
- [ ] Logged in with admin/moderator credentials
- [ ] Browser developer tools available (F12)
- [ ] Test CSV file ready (provided below)
- [ ] Database backup taken (recommended)

---

## Test CSV Files

### Test File 1: Basic Import (Without exam_year Column)
Create file: `test_import_basic.csv`
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S0101TEST,Alice Johnson,F,S0713,SCHOOL,PCM,
S0102TEST,Bob Williams,M,S0713,SCHOOL,PCB,
P0101TEST,Charlie Brown,M,S0744,PRIVATE,,111|121|131
P0102TEST,Diana Prince,F,S0744,PRIVATE,,111|122|132
```

### Test File 2: With exam_year Column (Optional)
Create file: `test_import_with_year.csv`
```csv
candidate_id,full_name,gender,school_code,candidate_type,exam_year,combination,subjects
S0201TEST,Eve Adams,F,S0713,SCHOOL,2026,PCM,
S0202TEST,Frank Miller,M,S0713,SCHOOL,2026,PCB,
P0201TEST,Grace Hopper,F,S0744,PRIVATE,2026,,111|131|141
```

---

## Test 1: Import Modal Opens Correctly

### Steps:
1. Navigate to **Dashboard** (if not already there)
2. Click **Registration** in sidebar
3. Click **Candidates** submenu
4. Look for **"Import Candidates"** button (usually in top-right or under table)
5. Click the button

### Expected Results:
- [ ] Modal window appears (not full page reload)
- [ ] Modal title: "Import Candidates" or "Bulk Import"
- [ ] Modal has input fields for:
  - [ ] Exam Year (dropdown, default: 2026)
  - [ ] Import Mode (Skip/Replace radio buttons)
  - [ ] File Upload (drag-drop area or file button)
- [ ] Modal has action buttons: **Upload**, **Cancel**
- [ ] No JavaScript errors in console (F12 → Console tab)

### Screenshot Checklist:
- [ ] Take screenshot showing modal with Exam Year dropdown visible
- [ ] Verify dropdown shows "2026" as default

---

## Test 2: Exam Year Dropdown Works

### Steps:
1. In the import modal, click the **Exam Year** dropdown
2. Verify available options

### Expected Results:
- [ ] Dropdown opens smoothly
- [ ] Options visible: **2024**, **2025**, **2026**
- [ ] Default selected: **2026** (highlighted)
- [ ] Can select different year (e.g., 2025)

### Verification:
- [ ] Select 2025
- [ ] Click elsewhere to close dropdown
- [ ] Verify 2025 remains selected

---

## Test 3: Import Mode Selection

### Steps:
1. In the import modal, look for **Import Mode** section
2. Verify both options visible

### Expected Results:
- [ ] Two radio button options:
  - [ ] **Skip** (default selected) - prevents duplicates
  - [ ] **Replace** - updates existing candidates
- [ ] Can click each option
- [ ] Selection changes when clicked
- [ ] Description or help text explains difference

### Skip Mode Test:
- [ ] Select "Skip" option
- [ ] Verify it's selected (radio button filled)

### Replace Mode Test:
- [ ] Select "Replace" option
- [ ] Verify it's selected (radio button filled)

---

## Test 4: File Upload - Basic CSV Without exam_year

### Steps:
1. Ensure Exam Year is set to **2026**
2. Ensure Import Mode is **Skip**
3. In the **File Upload** section, click to select file
4. Choose `test_import_basic.csv` (4 rows: 2 SCHOOL, 2 PRIVATE)
5. Click **"Upload"** or **"Validate"** button

### Expected Results - Phase 1 (Validation):
- [ ] Modal shows upload progress (loading indicator)
- [ ] After ~2-3 seconds, preview table appears showing:
  - [ ] Column headers: Row, Candidate ID, Name, Status, Messages
  - [ ] 4 data rows visible with status
  - [ ] Status column shows: "NEW" for all rows (first import)
  - [ ] No error messages
- [ ] Summary shows: "Create: 4, Error: 0"
- [ ] **"Proceed to Import"** button appears (or similar)

### Preview Table Details:
```
Row | Candidate ID | Name | Status | Messages
1   | S0101TEST    | Alice Johnson | NEW |
2   | S0102TEST    | Bob Williams | NEW |
3   | P0101TEST    | Charlie Brown | NEW |
4   | P0102TEST    | Diana Prince | NEW |
```

### If Errors Appear:
- [ ] Take screenshot of error
- [ ] Note error message exactly
- [ ] Check browser console (F12) for JavaScript errors
- [ ] Proceed to troubleshooting section at end

---

## Test 5: File Upload - CSV WITH exam_year Column

### Steps:
1. Click **"Choose File"** again (or clear and re-upload)
2. Select `test_import_with_year.csv`
3. Exam Year can be 2026 (UI value) or 2025 (will use CSV value)
4. Click **"Validate"** button

### Expected Results:
- [ ] Validation succeeds
- [ ] 3 rows shown in preview (S0201TEST, S0202TEST, P0201TEST)
- [ ] All rows status: "NEW"
- [ ] No error messages
- [ ] Summary: "Create: 3, Error: 0"

### Key Verification:
- [ ] System accepts CSV with exam_year column
- [ ] System also accepts CSV without exam_year column (Test 4)
- [ ] Both scenarios work correctly

---

## Test 6: Import Commit - Execute Actual Import

### Steps:
1. From the preview screen (after validation), click **"Proceed to Import"** or **"Confirm Import"**
2. Wait for import to complete (may show progress bar)

### Expected Results:
- [ ] Import completes successfully
- [ ] Success message appears: "Successfully imported X candidates"
- [ ] Modal closes automatically (or shows close button)
- [ ] Page refreshes or returns to Candidates list
- [ ] No error messages or exceptions

### Success Indicators:
- [ ] Modal disappears
- [ ] Candidates page loads normally
- [ ] Toast/alert message shows success
- [ ] Console shows no errors (F12 → Console)

---

## Test 7: Verify Candidates Created

### Steps:
1. Stay on **Candidates** page (should auto-refresh)
2. Scroll through the candidates table
3. Search for test candidates using ID search (if available)

### Expected Results:
- [ ] New candidates appear in table:
  - [ ] S0101TEST - Alice Johnson
  - [ ] S0102TEST - Bob Williams
  - [ ] P0101TEST - Charlie Brown
  - [ ] P0102TEST - Diana Prince
- [ ] Candidate Type column shows:
  - [ ] "SCHOOL" for S* candidates
  - [ ] "PRIVATE" for P* candidates
- [ ] Status shows "registered"

### Table View:
- [ ] Can see candidate ID, name, gender, type
- [ ] Can click on candidate to view details
- [ ] No broken data or display issues

---

## Test 8: Verify ACSEE Allocations on ACSEE Page

### Steps:
1. Navigate to **Exams** in sidebar
2. Click **ACSEE Management** (or similar)
3. Look for filter/search controls
4. Filter by **Year**: 2026 (or select from dropdown)
5. Look for test candidates in table

### Expected Results - SCHOOL Candidates:
- [ ] S0101TEST visible with subjects from PCM combination:
  - [ ] PHYSICS (131)
  - [ ] CHEMISTRY (132)
  - [ ] BASIC APPLIED MATHEMATICS (141)
- [ ] S0102TEST visible with subjects from PCB combination:
  - [ ] PHYSICS (131)
  - [ ] CHEMISTRY (132)
  - [ ] BIOLOGY (133)

### Expected Results - PRIVATE Candidates:
- [ ] P0101TEST visible with allocated subjects:
  - [ ] 111 - GENERAL STUDIES
  - [ ] 121 - KISWAHILI
  - [ ] 131 - PHYSICS
- [ ] P0102TEST visible with allocated subjects:
  - [ ] 111 - GENERAL STUDIES
  - [ ] 122 - ENGLISH LANGUAGE
  - [ ] 132 - CHEMISTRY

### Allocated Subjects Column:
- [ ] Column header: "Allocated Subjects" or "Subjects"
- [ ] Shows subject codes and names
- [ ] PRIVATE candidates show exactly the subjects from CSV
- [ ] SCHOOL candidates show subjects from combination

---

## Test 9: Verify Subject Allocation Details

### Steps:
1. On ACSEE page, click on one PRIVATE candidate (e.g., P0101TEST)
2. View candidate details

### Expected Results:
- [ ] Candidate details page opens
- [ ] Shows allocated subjects section
- [ ] P0101TEST shows:
  - [ ] 111 - GENERAL STUDIES
  - [ ] 121 - KISWAHILI
  - [ ] 131 - PHYSICS
- [ ] All subjects marked as "Principal" or similar indicator
- [ ] Subjects match exactly what was in CSV

### Subject Properties:
- [ ] Each subject shows code and name
- [ ] Can see "Principal" status (yes/no)
- [ ] Edit/delete buttons available (if needed)

---

## Test 10: Skip Mode Works (No Duplicates)

### Steps:
1. Go back to **Candidates** → **Import Candidates**
2. Select **Skip** mode (should be default)
3. Upload `test_import_basic.csv` again
4. Click **Validate**

### Expected Results - Validation Phase:
- [ ] Preview shows 4 rows
- [ ] Status column shows:
  - [ ] S0101TEST: "SKIP" (already exists)
  - [ ] S0102TEST: "SKIP" (already exists)
  - [ ] P0101TEST: "SKIP" (already exists)
  - [ ] P0102TEST: "SKIP" (already exists)
- [ ] Summary shows: "Skip: 4, Create: 0, Error: 0"
- [ ] **"Cannot Import"** message or button disabled (no new records to create)

### Expected Behavior:
- [ ] System recognizes duplicates
- [ ] Doesn't create new records
- [ ] Prevents data duplication

---

## Test 11: Replace Mode Works (Updates Data)

### Steps:
1. Go back to **Candidates** → **Import Candidates**
2. Select **Replace** mode
3. Upload `test_import_basic.csv` again
4. Click **Validate**

### Expected Results - Validation Phase:
- [ ] Preview shows 4 rows
- [ ] Status column shows:
  - [ ] S0101TEST: "REPLACE" (will update existing)
  - [ ] S0102TEST: "REPLACE" (will update existing)
  - [ ] P0101TEST: "REPLACE" (will update existing)
  - [ ] P0102TEST: "REPLACE" (will update existing)
- [ ] Summary shows: "Replace: 4, Create: 0, Error: 0"
- [ ] **"Proceed to Import"** button is enabled

### After Proceeding to Import:
- [ ] Import completes
- [ ] Success message shows "Updated X candidates"
- [ ] Candidates page still shows same 4 candidates (no duplicates)
- [ ] Data is refreshed (new import overrides old values)

---

## Test 12: Error Handling - Invalid School Code

### Steps:
1. Create test CSV with invalid school code:
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
INVALID01,Invalid Test,M,ZZZZ,SCHOOL,PCM,
```
2. Upload the file
3. Click **Validate**

### Expected Results:
- [ ] Validation fails
- [ ] Error message appears: "School not found" or similar
- [ ] Row shows "ERROR" status
- [ ] Summary shows: "Error: 1, Create: 0"
- [ ] **"Cannot Import"** button disabled
- [ ] Detailed error message visible in preview

---

## Test 13: Error Handling - Invalid Subjects

### Steps:
1. Create test CSV with invalid subject codes:
```csv
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
P0301TEST,Invalid Subjects,F,S0744,PRIVATE,,999|888|777
```
2. Upload the file
3. Click **Validate**

### Expected Results:
- [ ] Validation fails
- [ ] Error message appears: "Subject not found: 999" (or similar)
- [ ] Row shows "ERROR" status
- [ ] Summary shows: "Error: 1, Create: 0"
- [ ] Detailed error explains which subjects are invalid

---

## Test 14: Browser Console Check

### Steps:
1. Open browser Developer Tools: Press **F12**
2. Go to **Console** tab
3. Run any of the above tests
4. Check console output

### Expected Results:
- [ ] No red error messages
- [ ] No warning messages about missing CSRF tokens
- [ ] No undefined variable errors
- [ ] No JavaScript exceptions
- [ ] Console clean or only info-level logs

### If Errors Found:
- [ ] Take screenshot
- [ ] Note the exact error message
- [ ] Check network tab (F12 → Network) for failed requests
- [ ] Document for troubleshooting

---

## Test 15: Network Requests Verification

### Steps:
1. Open browser Developer Tools: **F12**
2. Go to **Network** tab
3. Perform an import (validate + commit)
4. Check network requests

### Expected Results:
- [ ] Request to `/api/candidates/import/validate` 
  - Status: **200 OK**
  - Response: JSON with validation results
- [ ] Request to `/api/candidates/import/commit`
  - Status: **200 OK**
  - Response: JSON with success message
- [ ] No **4xx** or **5xx** errors
- [ ] Response times < 3 seconds for validate
- [ ] Response times < 5 seconds for commit

### Network Troubleshooting:
- [ ] If 422 error: Check CSRF token
- [ ] If 500 error: Check server logs
- [ ] If timeout: Check database performance

---

## Test Results Summary

### Overall Status
- [ ] Test 1: Modal Opens - **PASS / FAIL**
- [ ] Test 2: Dropdown Works - **PASS / FAIL**
- [ ] Test 3: Import Modes - **PASS / FAIL**
- [ ] Test 4: CSV Without exam_year - **PASS / FAIL**
- [ ] Test 5: CSV With exam_year - **PASS / FAIL**
- [ ] Test 6: Import Commit - **PASS / FAIL**
- [ ] Test 7: Candidates Created - **PASS / FAIL**
- [ ] Test 8: ACSEE Allocations - **PASS / FAIL**
- [ ] Test 9: Allocation Details - **PASS / FAIL**
- [ ] Test 10: Skip Mode - **PASS / FAIL**
- [ ] Test 11: Replace Mode - **PASS / FAIL**
- [ ] Test 12: Error Handling (School) - **PASS / FAIL**
- [ ] Test 13: Error Handling (Subjects) - **PASS / FAIL**
- [ ] Test 14: Console Check - **PASS / FAIL**
- [ ] Test 15: Network Check - **PASS / FAIL**

### Overall Result
- [ ] **ALL TESTS PASSED** ✅ Ready for production
- [ ] **SOME TESTS FAILED** ❌ See troubleshooting section

---

## Troubleshooting

### Issue: Modal doesn't appear when clicking Import button
**Solution:**
1. Check browser console for JavaScript errors
2. Verify browser compatibility (Chrome, Firefox, Safari)
3. Clear browser cache: Ctrl+Shift+Delete
4. Try different browser
5. Check for browser extensions blocking modals

### Issue: File upload doesn't work
**Solution:**
1. Verify file is CSV format
2. Check file size (should be < 10MB)
3. Check file has valid headers
4. Try uploading from different directory
5. Check server upload limits in php.ini

### Issue: Validation passes but import fails
**Solution:**
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify database connection
3. Check for foreign key constraints
4. Look for duplicate candidate IDs in database
5. Verify subjects exist in database

### Issue: Subjects not allocated to PRIVATE candidates
**Solution:**
1. Check subject codes in CSV match database
2. Verify subjects are pipe-delimited (|), not comma
3. Check database for subject records
4. Verify exam type is ACSEE
5. Check allocation_details table in database

### Issue: ACSEE page doesn't show allocations
**Solution:**
1. Verify candidates in database: 
   ```sql
   SELECT * FROM candidates WHERE candidate_id LIKE 'P0%TEST';
   ```
2. Check allocations table:
   ```sql
   SELECT * FROM candidate_subject_selections WHERE candidate_id IN (SELECT id FROM candidates WHERE candidate_id LIKE 'P0%TEST');
   ```
3. Clear browser cache
4. Try different year filter
5. Check for JavaScript errors on ACSEE page

---

## Sign-Off

**Tester Name**: _______________________  
**Date**: _______________________  
**Time**: _______________________  

**Overall Result**: 
- [ ] ✅ PASS - All tests passed, ready for production
- [ ] ⚠️ PASS WITH NOTES - See notes below
- [ ] ❌ FAIL - Issues found, see troubleshooting

**Notes/Issues Found**:
```
[Add any issues or observations here]
```

**Recommendation**:
- [ ] Deploy to production
- [ ] Deploy with caution (fix noted issues first)
- [ ] Do not deploy (critical issues found)

---

**Reference**: CANDIDATE_IMPORT_DEPLOYMENT_VERIFICATION_2026_02_16.md  
**Status**: Ready for manual testing
