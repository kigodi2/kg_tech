# Candidate Import Skip/Replace: Test Plan

**Date**: February 15, 2026  
**Tester**: Engineering Team  
**Feature**: Skip Existing + Replace Existing candidate modes

---

## Pre-Test Setup

### 1. Database State

Create test data:
```php
// Using tinker or test data factory

// School: S0754
$school1 = School::firstOrCreate(['code' => 'S0754'], ['name' => 'Test School 1', 'district_id' => 1]);

// Existing candidate S0754-0001
Candidate::create([
    'school_id' => $school1->id,
    'candidate_id' => 'S0754-0001',
    'full_name' => 'JOHN DOE',
    'gender' => 'M',
    'exam_type' => 'ACSEE',
    'combination' => 'PCM',
    'candidate_type' => 'SCHOOL',
    'status' => 'registered',
    'is_active' => true,
]);

// Existing candidate S0754-0002 with marks (to test protection)
$cand2 = Candidate::create([
    'school_id' => $school1->id,
    'candidate_id' => 'S0754-0002',
    'full_name' => 'JANE SMITH',
    'gender' => 'F',
    'exam_type' => 'ACSEE',
    'combination' => 'HGE',
    'candidate_type' => 'SCHOOL',
    'status' => 'registered',
    'is_active' => true,
]);

// Register for exam with subjects and marks (to verify not deleted)
$examYear = ExamYear::where('year_label', '2026')->first();
$examType = ExamType::where('code', 'ACSEE')->first();
$registration = CandidateExamRegistration::create([
    'candidate_id' => $cand2->id,
    'exam_type_id' => $examType->id,
    'exam_year_id' => $examYear->id,
    'year' => 2026,
    'registration_number' => 'REG-TEST-001',
    'is_active' => true,
]);

// Add some marks to verify they're preserved
$subject = Subject::first();
SubjectMarks::create([
    'candidate_id' => $cand2->id,
    'subject_id' => $subject->id,
    'exam_year_id' => $examYear->id,
    'marks' => 85,
]);
```

### 2. Test Files

**File A: New Candidates**
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0010,NEW STUDENT 1,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0011,NEW STUDENT 2,F,S0754,HGE,ACSEE,2026,SCHOOL
```

**File B: Mixed (New + Existing)**
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0001,JOHN PETER DOE,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0003,NEW STUDENT 3,M,S0754,CBE,ACSEE,2026,SCHOOL
S0754-0002,JANE MARIE SMITH,F,S0754,HGE,ACSEE,2026,SCHOOL
```

**File C: Errors**
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0004,,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0005,NAME,X,BADSCHOOL,XYZ,ACSEE,2026,SCHOOL
```

**File D: Replace Scenario**
```csv
candidate_id,full_name,gender,school_code
S0754-0002,JANE UPDATED NAME,M,S0754
```

---

## Test Cases

### Test 1: Skip Mode - New Candidates Only

**File**: File A  
**Mode**: Skip (default)  
**Expected Result**:
- create_count = 2
- skip_count = 0
- update_count = 0
- error_count = 0
- can_import = true
- Status badges: both "✓ NEW"

**Verify**:
1. Validate file
2. Import Plan shows 2 NEW rows
3. Click Import
4. Database: S0754-0010 and S0754-0011 created
5. Check candidates list displays both

---

### Test 2: Skip Mode - Mixed File (New + Existing)

**File**: File B  
**Mode**: Skip  
**Expected Result**:
- create_count = 1 (S0754-0003)
- skip_count = 2 (S0754-0001, S0754-0002)
- update_count = 0
- error_count = 0
- can_import = true

**Verify**:
1. Validate file
2. Summary cards show:
   - New = 1
   - Will Skip = 2
3. Import Plan shows:
   - Row 1: ⊘ SKIP (S0754-0001)
   - Row 2: ✓ NEW (S0754-0003)
   - Row 3: ⊘ SKIP (S0754-0002)
4. Click Import
5. Database check:
   - S0754-0003 created ✓
   - S0754-0001 name still "JOHN DOE" (not updated) ✓
   - S0754-0002 name still "JANE SMITH" (not updated) ✓

---

### Test 3: Replace Mode - Mixed File

**File**: File B  
**Mode**: Replace  
**Expected Result**:
- create_count = 1 (S0754-0003)
- skip_count = 0
- update_count = 2 (S0754-0001, S0754-0002)
- error_count = 0
- can_import = true

**Verify**:
1. In Step 1, select "Replace existing" radio button
2. Validate file
3. Summary cards show:
   - New = 1
   - Will Update = 2
   - No "Will Skip" card shown
4. Orange warning appears:
   - "Replace Mode Active - 2 existing candidate(s) will be updated..."
5. Import Plan shows:
   - Row 1: ↻ UPDATE (S0754-0001)
   - Row 2: ✓ NEW (S0754-0003)
   - Row 3: ↻ UPDATE (S0754-0002)
6. Button shows "Import 3 Records"
7. Click Import
8. Database check:
   - S0754-0001 name = "JOHN PETER DOE" ✓ (updated)
   - S0754-0002 name = "JANE MARIE SMITH" ✓ (updated)
   - S0754-0003 created ✓
   - Exam registrations preserved (not deleted) ✓
   - Marks preserved (not deleted) ✓

---

### Test 4: Replace Mode - Update Only Name (Careful!)

**File**: File D  
**Mode**: Replace  
**Expected Result**:
- create_count = 0
- update_count = 1 (S0754-0002)
- error_count = 0
- can_import = true

**Note**: File D has ONLY candidate_id, full_name, gender, school_code columns (no exam data)

**Verify**:
1. Validate file
2. Import Plan shows ↻ UPDATE for S0754-0002
3. Click Import
4. Database check:
   - S0754-0002 full_name = "JANE UPDATED NAME" ✓
   - S0754-0002 gender = "M" ✓
   - Exam registrations UNCHANGED ✓
   - Subjects UNCHANGED ✓
   - Marks UNCHANGED ✓

---

### Test 5: Validation Errors

**File**: File C  
**Mode**: Skip or Replace (doesn't matter)  
**Expected Result**:
- error_count = 2
- can_import = false
- Error table shows both rows with messages
- Import button DISABLED

**Verify**:
1. Validate file
2. Summary card "Can Import" shows "No ✗"
3. "Errors Found" table displays:
   - Row 1: "full_name is required..."
   - Row 2: "Invalid gender or school_code not found..."
4. Import button is disabled (can't click)
5. Fix errors in CSV, re-upload

---

### Test 6: Boundary - Empty Existing Table

**Setup**: Delete all candidates from school S0754  
**File**: File A  
**Mode**: Skip  
**Expected Result**:
- create_count = 2
- skip_count = 0
- Behaves same as normal create

**Verify**: Both candidates created

---

### Test 7: Boundary - All Rows Are Existing

**File**:
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0001,JOHN NEW,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0002,JANE NEW,F,S0754,HGE,ACSEE,2026,SCHOOL
```

**Mode**: Skip  
**Expected Result**:
- create_count = 0
- skip_count = 2
- can_import = false (no records to import!)
- Error message or disabled button

**Verify**: Import is blocked appropriately

---

### Test 8: Boundary - All Rows Are Existing (Replace Mode)

**Same File as Test 7**  
**Mode**: Replace  
**Expected Result**:
- create_count = 0
- update_count = 2
- can_import = true (2 updates is valid!)
- Import succeeds
- Both candidates updated

**Verify**: Replace mode allows all-existing imports

---

### Test 9: School Not Found in Replace Mode

**File**:
```csv
candidate_id,full_name,gender,school_code
S0754-0002,JANE,F,BADSCHOOL
```

**Mode**: Replace  
**Expected Result**:
- error_count = 1
- Message about school not found
- can_import = false

**Verify**: Invalid school blocks import

---

### Test 10: Duplicate Within File (Skip Mode)

**File**:
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0020,DUP STUDENT,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0020,DUP STUDENT,M,S0754,PCM,ACSEE,2026,SCHOOL
```

**Mode**: Skip  
**Expected Result**:
- error_count = 1 (duplicate detected)
- Only 1 row shows as NEW or ERROR
- can_import = true or false depending on validation logic

**Verify**: Duplicates handled appropriately

---

### Test 11: Case Sensitivity in Mode

**Test**: Ensure on_exists_mode parameter is case-sensitive  
**Attempts**:
- `on_exists_mode=skip` ✓
- `on_exists_mode=SKIP` ✗ (rejected)
- `on_exists_mode=Skip` ✗ (rejected)

**Verify**: Only lowercase 'skip' and 'replace' accepted

---

### Test 12: Missing Mode Parameter (Backward Compat)

**Request without on_exists_mode parameter**:
```
POST /api/candidates/import/validate
file=...
```

**Expected**: Defaults to mode='skip'

**Verify**: Works without error

---

### Test 13: UI Responsiveness

**Test**: Import Plan table displays correctly

**Verify**:
- First 20 rows visible
- Status badges render properly
- Scrolling works
- "Showing X of Y rows" displays
- No layout breaking

---

### Test 14: Internationalization (If Applicable)

**Verify**: Status badges and messages display in correct language

---

### Test 15: Large File (100+ rows)

**File**: CSV with 100+ candidates  
**Mode**: Skip  
**Expected Result**:
- Validation completes in reasonable time (< 30 seconds)
- Preview shows first 20 in tables
- Import button activates
- Import completes (may take 1-2 minutes)

**Verify**: Performance is acceptable

---

## Regression Tests

### Should Still Work

- [ ] Regular candidate create (manual form) - unchanged
- [ ] Candidate edit (manual form) - unchanged
- [ ] Candidate delete - unchanged
- [ ] Candidates list/search/filter - unchanged
- [ ] Exam registration workflow - unchanged
- [ ] Mark entry - unchanged
- [ ] Existing imports (old flow) - backward compatible

---

## Acceptance Criteria

✅ **All test cases pass**

✅ **No SQL errors or PHP exceptions**

✅ **UI renders without console errors**

✅ **Skip and Replace modes produce expected row counts**

✅ **Data integrity: marks/results/registrations preserved in Replace mode**

✅ **Error messages are clear and actionable**

✅ **Import button enabled/disabled appropriately**

✅ **Performance acceptable (< 2 min for 100+ rows)**

✅ **Database transactions work (rollback on error)**

---

## Known Limitations & Future Work

### Current Scope (IMPLEMENTED)
- Skip mode (ignore existing)
- Replace mode (update name, gender, school)
- Clear reporting with preview
- Safety: don't modify exam allocations

### Not Included (Future)
- [ ] Confirmation dialog before Replace import
- [ ] Selective row approval (per-row checkbox)
- [ ] Undo/rollback import
- [ ] Update more fields (DOB, candidate_type, etc.)
- [ ] Merge mode combining both records
- [ ] Import batch audit log storage

---

## Sign-Off

| Role | Name | Date | Sign-off |
|------|------|------|----------|
| Developer | [Name] | 2026-02-15 | ✓ Code complete |
| QA | [Name] | | ⏳ Testing |
| Product | [Name] | | ⏳ Approval |

---

## Notes

- Test in a dev/staging environment first
- Always backup database before testing Replace mode on production data
- Check application logs for any warnings during import
- Verify candidates list displays updated records correctly
