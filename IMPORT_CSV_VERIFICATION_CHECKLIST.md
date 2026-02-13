# Import CSV Workflow - Verification Checklist

## Pre-Import Verification

- [ ] Candidates Management page loads without errors
- [ ] Tools button visible and clickable in top-right
- [ ] Tools dropdown menu shows 3 options:
  - [ ] CSV Template
  - [ ] Import CSV
  - [ ] Export Excel

## Import CSV Flow Testing

### Step 1: Open Import Modal
- [ ] Click Tools menu → appears
- [ ] Click "Import CSV" option → closes menu
- [ ] "Import Candidates" modal opens
- [ ] Modal title shows "Import Candidates"
- [ ] Modal close (X) button visible in top-right
- [ ] No JavaScript errors in console

### Step 2: Modal Content Verification
- [ ] "Exam Year *" label visible
- [ ] Exam Year dropdown shows:
  - [ ] "Select Exam Year" as default option
  - [ ] List of exam years from database (e.g., 2024, 2025, 2026)
- [ ] "Exam Type (optional)" label visible
- [ ] Exam Type dropdown shows:
  - [ ] "Auto-detect from CSV" as default
  - [ ] PSLE, CSEE, ACSEE as options
- [ ] Help text visible: "Tip: CSV should contain columns..."
- [ ] Cancel button visible and enabled
- [ ] "Select File" button visible but DISABLED

### Step 3: Button State Management
- [ ] Click "Select File" while no exam year selected
  - [ ] Button is disabled (grayed out)
  - [ ] File picker does NOT open
- [ ] Select an exam year from dropdown
  - [ ] "Select File" button becomes ENABLED (blue)
  - [ ] Modal stays open
- [ ] (Optional) Select exam type
  - [ ] Exam type selection works
  - [ ] Modal stays open
- [ ] Click "Select File" button
  - [ ] File picker opens
  - [ ] Can navigate to CSV file

### Step 4: File Selection
- [ ] Download "CSV Template" to see correct format
- [ ] Prepare a test CSV with candidates:
  ```csv
  candidate_id,full_name,sex,combination,school_code,exam_type
  S1378-TEST-1,TEST CANDIDATE 1,M,PCM,1378,ACSEE
  ```
- [ ] Select CSV file in file picker
  - [ ] "Import Candidates" modal closes
  - [ ] System starts checking for conflicts
  - [ ] No JavaScript errors in console

### Step 5: Conflict Check (No Conflicts Path)
- [ ] If file has NO existing candidates:
  - [ ] Import processes automatically
  - [ ] Green success message appears:
    - [ ] Shows "Candidates imported successfully"
    - [ ] Shows count of imported records
    - [ ] Shows count of skipped/replaced if any
  - [ ] Success message disappears after 4 seconds
  - [ ] Candidates table refreshes
  - [ ] New candidates appear in table

### Step 6: Conflict Check (With Conflicts Path)
- [ ] Import file with existing candidates:
  ```csv
  candidate_id,full_name,sex,combination,school_code,exam_type
  S1378-0501,EXISTING CANDIDATE,F,CBE,1378,ACSEE
  ```
  (Use a candidate_id that already exists in system)
  
  - [ ] "Import Conflicts Detected" modal opens
  - [ ] Modal shows conflict count:
    - [ ] "X candidate(s) already exist in the system"
    - [ ] Help text explains conflict resolution
  - [ ] Conflicts list visible:
    - [ ] Shows first 10 conflicting candidate IDs
    - [ ] Shows count of additional conflicts if > 10
  - [ ] No JavaScript errors in console

### Step 7: Import Mode Options

**Option 1: Skip Existing Records (Default)**
- [ ] Radio button "Skip Existing Records" is selected by default
- [ ] Description: "Only import new records, leave existing ones unchanged"
- [ ] Select this option
  - [ ] Radio button is marked
  - [ ] Background highlights selection
- [ ] Click "Import" button
  - [ ] Import proceeds with skip mode
  - [ ] Success message shows correct counts
  - [ ] Existing candidates unchanged
  - [ ] New candidates added

**Option 2: Replace Existing Records**
- [ ] Click radio button "Replace Existing Records"
  - [ ] Radio button is marked
  - [ ] Background highlights selection
- [ ] Description: "Update existing records with new data"
- [ ] Click "Import" button
  - [ ] Import proceeds with replace mode
  - [ ] Success message shows updated counts
  - [ ] Existing candidates updated with new values
  - [ ] New candidates added
  - [ ] Table shows updated information

**Option 3: Replace All**
- [ ] Click radio button "Replace All"
  - [ ] Radio button is marked
  - [ ] Background highlights selection
- [ ] Description: "Delete all existing records and import fresh"
- [ ] Click "Import" button
  - [ ] CAUTION: This is destructive
  - [ ] All existing candidates deleted
  - [ ] Fresh import from CSV file
  - [ ] Success message shows total new count
  - [ ] Table shows only imported candidates

## Edge Cases & Error Handling

### Invalid Exam Year
- [ ] Try clicking "Select File" without choosing exam year
  - [ ] Button is disabled
  - [ ] File picker does NOT open

### File Format Issues
- [ ] Try importing file with missing columns
  - [ ] Should show error message
  - [ ] Error message indicates missing columns
  - [ ] No import occurs

### Empty File
- [ ] Try importing empty CSV file
  - [ ] Should show error or warning
  - [ ] No import occurs

### Cancel Operations
- [ ] Click Cancel in Import Candidates modal
  - [ ] Modal closes
  - [ ] No import occurs
  - [ ] Returns to normal page
  
- [ ] Click Cancel in Import Conflicts modal
  - [ ] Modal closes
  - [ ] No import occurs
  - [ ] Returns to normal page

- [ ] Click backdrop (black area) in either modal
  - [ ] Modal closes
  - [ ] No import occurs

- [ ] Click X button in either modal
  - [ ] Modal closes
  - [ ] No import occurs

## Data Integrity Checks

### After Skip Import
- [ ] Existing candidates:
  - [ ] Names unchanged
  - [ ] School unchanged
  - [ ] All fields unchanged
- [ ] New candidates:
  - [ ] Appear in table
  - [ ] All fields populated correctly
  - [ ] Status shows "registered"

### After Replace Import
- [ ] Updated candidates:
  - [ ] Reflect new CSV values
  - [ ] Schools updated if different
  - [ ] Names updated if different
  - [ ] Status remains consistent
- [ ] New candidates:
  - [ ] Added to system
  - [ ] All fields populated

### After Replace All
- [ ] Old candidates:
  - [ ] Completely removed from system
  - [ ] Cannot be accessed/searched
- [ ] New candidates:
  - [ ] Only imported candidates exist
  - [ ] All data matches CSV
  - [ ] Total count = CSV rows

## UI/UX Verification

### Modal Appearance
- [ ] Import modal centered on screen
- [ ] Modal backdrop (black area) covers full page
- [ ] Modal content readable and well-formatted
- [ ] Form fields properly aligned
- [ ] Buttons properly styled

### Conflict Modal Appearance
- [ ] Conflict modal properly positioned
- [ ] List of conflicts scrollable if > 10
- [ ] Radio buttons properly styled
- [ ] Options descriptions clear
- [ ] Import/Cancel buttons properly positioned

### Messages
- [ ] Success messages:
  - [ ] Green background
  - [ ] Clear text
  - [ ] Disappear after 4 seconds
- [ ] Error messages:
  - [ ] Red background
  - [ ] Clear error description
  - [ ] Disappear after 4 seconds

### Responsiveness
- [ ] Test on different screen sizes:
  - [ ] Desktop (1920x1080) - all elements visible
  - [ ] Tablet (768x1024) - modals centered
  - [ ] Mobile (375x667) - modals fit screen

## Console Checks

- [ ] No JavaScript errors
- [ ] No Alpine.js warnings
- [ ] Network requests successful:
  - [ ] POST /api/candidates/import/check → 200 OK
  - [ ] POST /api/candidates/import → 200 OK
- [ ] State variables updated correctly:
  - [ ] `showImportModal` changes as expected
  - [ ] `showImportConflictModal` changes as expected
  - [ ] `importExamYear` stores selected value
  - [ ] `importMode` stores selected mode

## Performance Checks

- [ ] Import doesn't freeze UI
- [ ] Progress visible during large imports
- [ ] Table refreshes smoothly
- [ ] No memory leaks after repeated imports
- [ ] Modals open/close smoothly

## Database Checks

### After Successful Import
- [ ] New candidates in database:
  ```sql
  SELECT COUNT(*) FROM candidates WHERE exam_year = '2026';
  ```
- [ ] Correct school assignments
- [ ] Correct exam types
- [ ] All required fields populated

### After Replace Import
- [ ] Updated candidates have new values
- [ ] Old data overwritten correctly
- [ ] No duplicate records

### After Replace All
- [ ] Old records gone
- [ ] New records only from CSV
- [ ] Correct total count

## Final Sign-Off

### Overall Workflow
- [ ] Complete workflow functions end-to-end
- [ ] All three import modes work
- [ ] No data corruption
- [ ] No JavaScript errors
- [ ] User experience smooth

### Deployment Readiness
- [ ] All tests passing
- [ ] No console errors
- [ ] Data integrity verified
- [ ] Performance acceptable
- [ ] Ready for production

---

**Test Date**: _____________  
**Tester**: _____________  
**Browser**: _____________  
**OS**: _____________  

**Notes**:
```
_________________________________________________
_________________________________________________
_________________________________________________
```

**Sign-Off**: ☐ PASS / ☐ FAIL

---

**If any item fails**:
1. Document the failure
2. Check console for errors
3. Review error message
4. Verify CSV format
5. Check database for issues
6. Consult IMPORT_CSV_WORKFLOW_FIX.md for troubleshooting
