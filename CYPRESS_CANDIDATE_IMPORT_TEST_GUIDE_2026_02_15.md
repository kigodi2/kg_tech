# Cypress E2E Test Guide: Candidate Import Skip/Replace
**Date:** 2026-02-15  
**Status:** Tests Created - Cypress Environment Issue (SIGILL crash on Ubuntu 24.04)

---

## Test File Created

**Location:** `cypress/e2e/candidate_import_skip_replace.cy.js`

**Fixture Files Created:**
- `cypress/fixtures/candidate_import_mixed.csv` - Mixed data (2 existing + 1 new)
- `cypress/fixtures/candidate_import_errors.csv` - Invalid data for error testing

---

## Test Scenarios

### 1. SKIP MODE: Should Not Modify Existing Candidates
**File:** `candidate_import_skip_replace.cy.js` → Test: "SKIP MODE: should not modify existing candidates"

**Steps:**
1. Login
2. Navigate to Candidates → Bulk Import
3. Upload `candidate_import_mixed.csv`
4. Select mode: SKIP
5. Select exam year: 2026
6. Click Validate
   - Expected: create_count=1, skip_count=2
7. Click Commit
8. Verify DB state:
   - S0754-0001 = "JOHN DOE" (unchanged)
   - S0754-0002 = "JANE SMITH" (unchanged)
   - S0754-0003 = "NEW STUDENT" (created)

**Assertions:**
```
✓ Validation shows: 1 new, 2 skipped
✓ Commit successful
✓ Existing names preserved in table
✓ New candidate visible
```

---

### 2. REPLACE MODE: Should Update Existing Candidates
**File:** `candidate_import_skip_replace.cy.js` → Test: "REPLACE MODE: should update existing candidates"

**Steps:**
1. Login
2. Navigate to Candidates → Bulk Import
3. Upload `candidate_import_mixed.csv`
4. Select mode: REPLACE
   - Expected: Warning "will be UPDATED" shown
5. Select exam year: 2026
6. Click Validate
   - Expected: create_count=1, update_count=2
7. Click Commit
8. Verify DB state:
   - S0754-0001 = "JOHN PETER DOE" (updated)
   - S0754-0002 = "JANE MARIE SMITH" (updated)
   - S0754-0003 = "NEW STUDENT" (created)

**Assertions:**
```
✓ Replace warning displayed
✓ Validation shows: 1 new, 2 will update
✓ Commit successful
✓ Existing names updated in table
✓ New candidate visible
```

---

### 3. Error Handling
**File:** `candidate_import_skip_replace.cy.js` → Test: "should show validation errors for invalid CSV"

**Steps:**
1. Login
2. Navigate to Candidates → Bulk Import
3. Upload `candidate_import_errors.csv` (missing ID, school, gender)
4. Select exam year: 2026
5. Click Validate
   - Expected: error_count > 0

**Assertions:**
```
✓ Validation errors displayed
✓ can_import = false
✓ Commit button disabled
```

---

### 4. Preservation of Marks During Replace
**File:** `candidate_import_skip_replace.cy.js` → Test: "should preserve registrations and marks during replace"

**Steps:**
1. Add mark to S0754-0001 via API: subject_id=1, mark=85
2. Upload CSV in REPLACE mode
3. Commit
4. Verify mark still exists via API

**Assertions:**
```
✓ Mark preserved after replace
✓ No data loss during update
```

---

## Current Issue: Cypress SIGILL Crash

**Error:** Cypress 14.0.0 crashes with SIGILL signal on Ubuntu 24.04

**Root Cause:** Node.js version mismatch
- Current: Node 18.19.1
- Required: Node 20.19.0 or ≥ 22.12.0

**Solution Options:**

### Option A: Upgrade Node.js (Recommended)
```bash
nvm install 20.19.0
nvm use 20.19.0
npm install
npx cypress run --spec "cypress/e2e/candidate_import_skip_replace.cy.js"
```

### Option B: Downgrade Cypress
```bash
npm install cypress@12.17.4 --save-dev
npx cypress run --spec "cypress/e2e/candidate_import_skip_replace.cy.js"
```

### Option C: Manual Testing (Immediate)
Use the test steps outlined above to manually verify in browser.

---

## Manual Testing Steps (No Cypress Required)

### Setup
```bash
cd /home/prosmart-technologies/SOL/irms
php artisan serve
# Then open http://localhost:8000 in browser
```

### Test Flow
1. **Login** with admin credentials
2. **Go to:** ACSEE → Candidates
3. **Click:** "Bulk Import" button
4. **Mode 1 (SKIP):**
   - Choose file: `cypress/fixtures/candidate_import_mixed.csv`
   - Mode: SKIP
   - Exam Year: 2026
   - Click Validate → Verify: 1 new, 2 skipped
   - Click Commit → Verify success
   - Refresh → Check: JOHN DOE, JANE SMITH, NEW STUDENT visible

5. **Reset Data (Admin Terminal):**
   ```bash
   php artisan tinker
   App\Models\Candidate::whereIn('candidate_id',['S0754-0001','S0754-0002','S0754-0003'])->delete();
   exit;
   ```

6. **Re-seed:**
   ```bash
   php artisan tinker
   $school=App\Models\School::where('code','S0754')->first();
   App\Models\Candidate::create(['school_id'=>$school->id,'candidate_id'=>'S0754-0001','full_name'=>'JOHN DOE','gender'=>'M','exam_type'=>'ACSEE','combination'=>'PCM','candidate_type'=>'SCHOOL','status'=>'registered','is_active'=>true]);
   App\Models\Candidate::create(['school_id'=>$school->id,'candidate_id'=>'S0754-0002','full_name'=>'JANE SMITH','gender'=>'F','exam_type'=>'ACSEE','combination'=>'HGE','candidate_type'=>'SCHOOL','status'=>'registered','is_active'=>true]);
   exit;
   ```

7. **Mode 2 (REPLACE):**
   - Choose file: `cypress/fixtures/candidate_import_mixed.csv`
   - Mode: REPLACE
   - Exam Year: 2026
   - Click Validate → Verify: 1 new, 2 will update
   - Click Commit → Verify success
   - Refresh → Check: JOHN PETER DOE, JANE MARIE SMITH, NEW STUDENT visible

---

## Test Status Summary

| Test | Status | Notes |
|------|--------|-------|
| Skip Mode | ✅ Service-Level Verified | Created, ready for Cypress |
| Replace Mode | ✅ Service-Level Verified | Created, ready for Cypress |
| Error Handling | ✅ Test Created | Ready for Cypress |
| Mark Preservation | ✅ Test Created | Ready for Cypress |
| Cypress Execution | ⚠️ Blocked | Node version incompatibility |

---

## Recommended Next Action

**Immediate:** Run manual tests using the steps above (5-10 minutes)  
**Follow-up:** Upgrade Node.js to 20.19.0+ and run Cypress for automated CI/CD coverage

---

## File Checksums
```
Test File: cypress/e2e/candidate_import_skip_replace.cy.js
Fixture 1: cypress/fixtures/candidate_import_mixed.csv (3 rows: 2 existing + 1 new)
Fixture 2: cypress/fixtures/candidate_import_errors.csv (3 rows: all invalid)
```
