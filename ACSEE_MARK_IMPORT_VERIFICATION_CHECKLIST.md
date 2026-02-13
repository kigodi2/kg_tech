# ACSEE Mark Import Refactoring - Verification Checklist

**Status:** Ready for Testing  
**Date:** 2026-01-31  

---

## PRE-DEPLOYMENT VERIFICATION

### Code Quality

- [x] All controllers lint-free
- [x] All services lint-free
- [x] All models lint-free
- [x] Migration syntax correct
- [x] No unused imports
- [x] PSR-12 compliance checked
- [x] Type hints present where applicable

### Architecture Compliance

- [x] Combination is not in UI state
- [x] Combination is not in request payload
- [x] Combination derivation is in validation service (correct layer)
- [x] Template generation doesn't use combination
- [x] Batch creation doesn't require combination_id
- [x] Legacy protection prevents combination_id acceptance

### Database Schema

- [x] Migration created: make_combination_id_nullable_in_batches.php
- [x] Backward compatibility maintained
- [x] Foreign key relationship preserved
- [x] Indexes remain intact

### API Surface

- [x] POST /mark-entry/acsee/upload no longer expects combination_id
- [x] GET /mark-entry/acsee/download-template no longer expects combination_id
- [x] Route for getCombinations removed
- [x] All other routes intact

### UI/UX

- [x] Combination dropdown removed from template
- [x] Subject field expanded
- [x] Grid layout adjusted
- [x] Alpine.js state cleaned
- [x] Form validation messages updated
- [x] Download button enabled by subject only
- [x] Upload validation checks subject only

---

## TESTING MATRIX

### Unit Tests

**MarkValidationService::validateRawMark()**

- [ ] Test: Candidate exists
  - Expected: Passes validation
  - Actual: ___

- [ ] Test: Candidate does NOT exist
  - Expected: Error "Candidate with index number 'X' not found"
  - Actual: ___

- [ ] Test: Candidate not registered for ACSEE
  - Expected: Error "Candidate is not registered for ACSEE in year X"
  - Actual: ___

- [ ] Test: Candidate registered for ACSEE
  - Expected: Passes this check
  - Actual: ___

**MarkValidationService::getCandidateCombination()**

- [ ] Test: Candidate with valid combination
  - Expected: Returns Combination object
  - Actual: ___

- [ ] Test: Candidate with multiple valid combos (edge case)
  - Expected: Returns first matching combination
  - Actual: ___

- [ ] Test: Candidate with no matching combo
  - Expected: Returns null
  - Actual: ___

**Subject-Combination Validation**

- [ ] Test: Subject IS in candidate's combination
  - Expected: Passes validation
  - Actual: ___

- [ ] Test: Subject NOT in candidate's combination
  - Expected: Error "Subject 'X' is not registered under candidate's ACSEE combination"
  - Actual: ___

**MarkImportService::createBatch()**

- [ ] Test: Batch created without combination_id
  - Expected: Batch created, combination_id is null
  - Actual: ___

**MarkTemplateService::generateCsv()**

- [ ] Test: Template generated with subject only
  - Expected: CSV headers match subject structure
  - Actual: ___

- [ ] Test: Sample rows use generic index
  - Expected: Rows like "S000001", "S000002"
  - Actual: ___

### Integration Tests

**CSV Upload Flow**

- [ ] Test: Upload without combination_id
  - Steps:
    1. Select year, school, subject
    2. Upload CSV
    3. No combination_id in form data
  - Expected: Success, batch created
  - Actual: ___

- [ ] Test: Upload WITH combination_id (legacy)
  - Steps:
    1. Manually add combination_id to form
    2. Submit
  - Expected: Error 422 "Combination selection is not allowed"
  - Actual: ___

**Validation with Single-Combination School**

- [ ] Test: All students have Science combination
  - Setup:
    - Create 5 candidates, all registered ACSEE Science combo
    - Upload Biology marks
  - Expected: All rows validate successfully
  - Actual: ___

**Validation with Multi-Combination School**

- [ ] Test: Students in different combinations
  - Setup:
    - Student 1: Science combo (Physics, Chemistry, Biology)
    - Student 2: Arts combo (History, Geography, Kiswahili)
    - Student 3: Science combo (Physics, Chemistry, Biology)
    - Upload Biology marks
  - Expected:
    - Student 1: ✅ Valid
    - Student 2: ❌ Invalid (Biology not in Arts)
    - Student 3: ✅ Valid
  - Actual: ___

**Validation with Subject Shared Across Combinations**

- [ ] Test: Subject in multiple combinations
  - Setup:
    - Combination A: Math, Physics, Chemistry
    - Combination B: Math, Biology, Geography
    - Upload Math marks (both students have Math)
  - Expected: Both validate successfully
  - Actual: ___

**Error Scenarios**

- [ ] Test: Invalid candidate index
  - Expected: Error message in report
  - Actual: ___

- [ ] Test: Missing marks column
  - Expected: Error "Paper 1 marks are missing or empty"
  - Actual: ___

- [ ] Test: Marks out of range (>100)
  - Expected: Error "marks must be between 0 and 100"
  - Actual: ___

- [ ] Test: Non-numeric marks
  - Expected: Error "marks must be numeric"
  - Actual: ___

**Batch Operations**

- [ ] Test: Lock batch with no errors
  - Expected: Status → Locked, locked_at set
  - Actual: ___

- [ ] Test: Lock batch with errors (should fail)
  - Expected: Error "Cannot lock batch with errors"
  - Actual: ___

- [ ] Test: Download error report
  - Expected: CSV with Row, Index, Name, Errors
  - Actual: ___

### Manual/E2E Tests

**UI Flow**

- [ ] Test: Load /mark-entry/acsee page
  - Expected: Page loads, no JS errors in console
  - Actual: ___

- [ ] Test: Combination dropdown NOT visible
  - Expected: Only Year, Region, District, School, Subject dropdowns
  - Actual: ___

- [ ] Test: Download template disabled without subject
  - Expected: Button shows "disabled" appearance
  - Actual: ___

- [ ] Test: Download template enabled with subject
  - Expected: Button active, clicking downloads CSV
  - Actual: ___

**Template Download**

- [ ] Test: Biology template
  - Expected:
    - Columns: Index Number, Full Name, Paper 1, Paper 2, Practical
    - 3 sample rows
    - Filename includes "BIO"
  - Actual: ___

- [ ] Test: Physics template (only papers, no practical)
  - Expected:
    - Columns: Index Number, Full Name, Paper 1, Paper 2
    - No Practical column
  - Actual: ___

- [ ] Test: Chemistry template (papers + practical + project)
  - Expected:
    - Columns: Index Number, Full Name, Paper 1, Paper 2, Practical, Project
  - Actual: ___

**File Upload**

- [ ] Test: Select file (valid CSV)
  - Expected: File appears in form
  - Actual: ___

- [ ] Test: Upload without selecting file
  - Expected: Error "Please select a file"
  - Actual: ___

- [ ] Test: Upload with required fields missing
  - Expected: Error "Please select all required fields (year, school, subject)"
  - Actual: ___

- [ ] Test: Upload with valid CSV
  - Expected: Success, batch created, validation summary shown
  - Actual: ___

**Results Display**

- [ ] Test: View batch with 100% success
  - Expected:
    - Total: X, Valid: X, Errors: 0, Status: Ready
    - "Lock Batch" button visible
    - No error download button
  - Actual: ___

- [ ] Test: View batch with errors
  - Expected:
    - Total: X, Valid: Y, Errors: Z, Status: Review Errors
    - "Download Error Report" button visible
    - "Lock Batch" button hidden
  - Actual: ___

**Error Report**

- [ ] Test: Download error report with errors
  - Expected:
    - CSV file downloads
    - Columns: Row Number, Index Number, Candidate Name, Errors
    - Each error row listed with reasons
  - Actual: ___

**Batch Locking**

- [ ] Test: Lock batch with valid data
  - Expected:
    - Status changes to "Locked"
    - locked_at timestamp set
    - locked_by set to current user
  - Actual: ___

- [ ] Test: Lock batch with errors (should fail)
  - Expected: Error "Cannot lock batch with errors"
  - Actual: ___

**Data Persistence**

- [ ] Test: Refresh page after successful import
  - Expected: Batch still visible with same data
  - Actual: ___

- [ ] Test: Close and reopen browser
  - Expected: Batch history preserved
  - Actual: ___

---

## SCENARIO-BASED TESTING

### Scenario 1: Normal School (Single Combination)

**Setup:**
- School: Mkuranga Secondary
- All students: Science combination
- Subject: Biology

**Test Steps:**
1. Open /mark-entry/acsee
2. Select: Year 2025, School "Mkuranga Secondary", Subject "Biology"
3. Download template
4. Fill 20 student marks (valid data)
5. Upload CSV
6. Verify: All 20 validate successfully
7. Lock batch
8. Confirm batch is locked

**Expected Result:** ✅ All steps succeed, batch locked

**Actual Result:** ___

---

### Scenario 2: Selective School (Multiple Combinations)

**Setup:**
- School: Ifakara High School
- 30 students total:
  - 10 in Science (Phys, Chem, Bio)
  - 10 in Arts (Hist, Geog, Kisw)
  - 10 in Commerce (Econ, Acc, Biz)
- Subject: Geography (only in Arts)

**Test Steps:**
1. Open /mark-entry/acsee
2. Select: Year 2025, School "Ifakara High School", Subject "Geography"
3. Download template
4. Fill 30 marks (10 correct, 20 wrong combo)
5. Upload CSV
6. Verify: 10 valid, 20 invalid with proper error message

**Expected Result:**
- ✅ Valid: 10 (Arts students)
- ❌ Invalid: 20 (Science and Commerce students)
- Error message: "Subject 'GEO' is not registered under candidate's ACSEE combination"
- Download error report shows which students failed

**Actual Result:** ___

---

### Scenario 3: Shared Subject School

**Setup:**
- School: Dar Technical College
- 40 students:
  - 20 in Science (Physics, Chemistry, Math)
  - 20 in Arts (English, Math, History)
- Subject: Math (in both combinations)

**Test Steps:**
1. Open /mark-entry/acsee
2. Select: Year 2025, School "Dar Technical College", Subject "Math"
3. Download template
4. Fill 40 marks (all valid)
5. Upload CSV
6. Verify: All 40 validate (system correctly recognizes Math in both combos)

**Expected Result:**
- ✅ All 40 valid
- Status: Ready to lock

**Actual Result:** ___

---

### Scenario 4: Error Recovery

**Setup:**
- School: Any school
- Subject: Biology

**Test Steps:**
1. Open /mark-entry/acsee
2. Select context
3. Download template
4. Fill marks with some errors:
   - Row 2: Invalid mark (>100)
   - Row 5: Missing mark
   - Row 10: Wrong combo subject
5. Upload CSV
6. Verify errors detected
7. Download error report
8. Fix the 3 problem rows
9. Re-upload corrected CSV
10. Verify all now valid

**Expected Result:**
- ✅ First upload: 3 errors detected
- ✅ Error report shows exact issues
- ✅ Second upload: All valid

**Actual Result:** ___

---

### Scenario 5: Browser/Cache Issues

**Setup:**
- Existing batch in system
- Recent combination selector removal

**Test Steps:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Open /mark-entry/acsee
3. Check UI doesn't show combination dropdown
4. Try to manually inject combination_id in form
5. Attempt upload

**Expected Result:**
- ✅ No combination dropdown visible
- ❌ Manual injection rejected with HTTP 422

**Actual Result:** ___

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment

- [ ] All code reviewed by team lead
- [ ] Unit tests passing (recommend)
- [ ] Manual E2E tests completed
- [ ] Database backup created
- [ ] Rollback plan documented
- [ ] Stakeholders notified

### Deployment

- [ ] Run migration: `php artisan migrate`
- [ ] Clear caches: `php artisan cache:clear`
- [ ] Clear views: `php artisan view:clear`
- [ ] Clear config: `php artisan config:clear`
- [ ] Restart queues (if applicable)
- [ ] Verify app loads without errors

### Post-Deployment

- [ ] Test /mark-entry/acsee loads
- [ ] Download template works
- [ ] Upload works
- [ ] Validation catches errors
- [ ] Lock functionality works
- [ ] Monitor error logs (first 24 hours)

### Rollback (If Issues)

- [ ] Database rollback: `php artisan migrate:rollback`
- [ ] Revert code to previous commit
- [ ] Clear caches again
- [ ] Verify app still works

---

## KNOWN ISSUES & WORKAROUNDS

| Issue | Workaround | Status |
|-------|-----------|--------|
| Template downloads slowly | Check network, try again | N/A |
| Large file upload timeout | Split file into smaller parts | N/A |
| Combination still visible | Clear browser cache (Ctrl+Shift+Delete) | N/A |
| Old combination_id error | Update frontend code | N/A |

---

## SIGN-OFF

**Developer:** ___________________  Date: ___________

**QA Lead:** ___________________  Date: ___________

**Project Manager:** ___________________  Date: ___________

---

## NOTES

Use this space for any issues found during testing:

```
Issue 1:
Description:
Steps to Reproduce:
Expected:
Actual:
Severity: [Critical] [High] [Medium] [Low]
Resolution:

Issue 2:
...
```

---

**Checklist Version:** 1.0  
**Last Updated:** 2026-01-31  
**Status:** READY FOR TESTING
