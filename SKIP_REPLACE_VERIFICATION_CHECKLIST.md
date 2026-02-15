# Candidate Import Skip/Replace Feature - VERIFICATION CHECKLIST

**Date**: February 15, 2026  
**Status**: Implementation Complete - Testing Required  
**Goal**: Verify all 15+ test cases from TEST PLAN pass

---

## PRE-TEST DATABASE SETUP

Before running tests, execute:

```bash
# Clear existing test candidates
php artisan tinker
>>> \App\Models\Candidate::where('candidate_id', 'like', 'S0754%')->delete();
>>> \App\Models\Candidate::where('candidate_id', 'like', 'P0652%')->delete();
>>> \App\Models\Candidate::where('candidate_id', 'like', 'S0%0010')->delete();

# Create school S0754
>>> $school = \App\Models\School::firstOrCreate(
    ['code' => 'S0754'],
    ['name' => 'Test School S0754', 'district_id' => 1]
);

# Create existing candidates
>>> $c1 = \App\Models\Candidate::create([
    'school_id' => $school->id,
    'candidate_id' => 'S0754-0001',
    'full_name' => 'JOHN DOE',
    'gender' => 'M',
    'exam_type' => 'ACSEE',
    'combination' => 'PCM',
    'candidate_type' => 'SCHOOL',
    'status' => 'registered',
    'is_active' => true,
]);

>>> $c2 = \App\Models\Candidate::create([
    'school_id' => $school->id,
    'candidate_id' => 'S0754-0002',
    'full_name' => 'JANE SMITH',
    'gender' => 'F',
    'exam_type' => 'ACSEE',
    'combination' => 'HGE',
    'candidate_type' => 'SCHOOL',
    'status' => 'registered',
    'is_active' => true,
]);

# Register S0754-0002 for exam (to test mark preservation)
>>> $exam_year = \App\Models\ExamYear::where('year_label', '2026')->first();
>>> $exam_type = \App\Models\ExamType::where('code', 'ACSEE')->first();
>>> $reg = \App\Models\CandidateExamRegistration::create([
    'candidate_id' => $c2->id,
    'exam_type_id' => $exam_type->id,
    'exam_year_id' => $exam_year->id,
    'year' => 2026,
    'registration_number' => 'TEST-REG-' . $c2->id,
    'is_active' => true,
]);

exit
```

---

## TEST CSV FILES

Create these test files in `/tmp/`:

### File A: New Candidates Only
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0010,NEW STUDENT 1,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0011,NEW STUDENT 2,F,S0754,HGE,ACSEE,2026,SCHOOL
```

### File B: Mixed (New + Existing)
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0001,JOHN PETER DOE,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0003,NEW STUDENT 3,M,S0754,CBE,ACSEE,2026,SCHOOL
S0754-0002,JANE MARIE SMITH,F,S0754,HGE,ACSEE,2026,SCHOOL
```

### File C: Errors
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0004,,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0005,NAME,X,BADSCHOOL,XYZ,ACSEE,2026,SCHOOL
```

### File D: Duplicates in File
```csv
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0020,DUP STUDENT,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0020,DUP STUDENT,M,S0754,PCM,ACSEE,2026,SCHOOL
```

---

## TEST CASE VERIFICATION

### TEST 1: Skip Mode - New Candidates Only

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_a.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

**Expected Response:**
```json
{
  "success": true,
  "total_rows": 2,
  "create_count": 2,
  "update_count": 0,
  "skip_count": 0,
  "error_count": 0,
  "can_import": true,
  "rows": [
    {"row_number": 1, "candidate_id": "S0754-0010", "status": "NEW"},
    {"row_number": 2, "candidate_id": "S0754-0011", "status": "NEW"}
  ]
}
```

**Verify:**
- [ ] create_count = 2
- [ ] skip_count = 0
- [ ] update_count = 0
- [ ] error_count = 0
- [ ] can_import = true
- [ ] All rows status = "NEW"

---

### TEST 2: Skip Mode - Mixed File (New + Existing)

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

**Expected Response:**
```json
{
  "success": true,
  "total_rows": 3,
  "create_count": 1,
  "update_count": 0,
  "skip_count": 2,
  "error_count": 0,
  "can_import": true,
  "rows": [
    {"row_number": 1, "candidate_id": "S0754-0001", "status": "SKIP"},
    {"row_number": 2, "candidate_id": "S0754-0003", "status": "NEW"},
    {"row_number": 3, "candidate_id": "S0754-0002", "status": "SKIP"}
  ]
}
```

**Verify:**
- [ ] create_count = 1 (S0754-0003)
- [ ] skip_count = 2 (S0754-0001, S0754-0002)
- [ ] update_count = 0
- [ ] error_count = 0
- [ ] can_import = true
- [ ] Row 1 status = "SKIP"
- [ ] Row 2 status = "NEW"
- [ ] Row 3 status = "SKIP"

---

### TEST 3: Replace Mode - Mixed File

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=replace"
```

**Expected Response:**
```json
{
  "success": true,
  "total_rows": 3,
  "create_count": 1,
  "update_count": 2,
  "skip_count": 0,
  "error_count": 0,
  "can_import": true,
  "rows": [
    {"row_number": 1, "candidate_id": "S0754-0001", "status": "REPLACE"},
    {"row_number": 2, "candidate_id": "S0754-0003", "status": "NEW"},
    {"row_number": 3, "candidate_id": "S0754-0002", "status": "REPLACE"}
  ]
}
```

**Verify:**
- [ ] create_count = 1
- [ ] update_count = 2 (S0754-0001, S0754-0002)
- [ ] skip_count = 0
- [ ] error_count = 0
- [ ] can_import = true
- [ ] Row 1 status = "REPLACE"
- [ ] Row 2 status = "NEW"
- [ ] Row 3 status = "REPLACE"

---

### TEST 4: Commit Skip Mode - Verify DB Unchanged for Existing

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/commit \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

**Expected Response:**
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

**Database Verification (after commit):**
```bash
php artisan tinker
>>> \App\Models\Candidate::where('candidate_id', 'S0754-0001')->first()->full_name
# Should output: "JOHN DOE" (NOT "JOHN PETER DOE")

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0002')->first()->full_name
# Should output: "JANE SMITH" (NOT "JANE MARIE SMITH")

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0003')->exists()
# Should output: true (newly created)

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0002')->first()->examRegistrations()->count()
# Should output: 1 (exam registration preserved)

exit
```

**Verify:**
- [ ] created_count = 1
- [ ] updated_count = 0
- [ ] skipped_count = 2
- [ ] S0754-0001 name unchanged ("JOHN DOE")
- [ ] S0754-0002 name unchanged ("JANE SMITH")
- [ ] S0754-0003 created
- [ ] S0754-0002 exam registration preserved (count = 1)

---

### TEST 5: Commit Replace Mode - Verify DB Updated for Existing

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/commit \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=replace"
```

**Expected Response:**
```json
{
  "success": true,
  "created_count": 1,
  "updated_count": 2,
  "skipped_count": 0,
  "failed_count": 0,
  "errors": []
}
```

**Database Verification (after commit):**
```bash
php artisan tinker
>>> \App\Models\Candidate::where('candidate_id', 'S0754-0001')->first()->full_name
# Should output: "JOHN PETER DOE" (UPDATED)

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0002')->first()->full_name
# Should output: "JANE MARIE SMITH" (UPDATED)

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0003')->exists()
# Should output: true (newly created)

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0002')->first()->examRegistrations()->count()
# Should output: 1 (exam registration PRESERVED, not deleted!)

exit
```

**Verify:**
- [ ] created_count = 1
- [ ] updated_count = 2
- [ ] skipped_count = 0
- [ ] S0754-0001 name updated ("JOHN PETER DOE")
- [ ] S0754-0002 name updated ("JANE MARIE SMITH")
- [ ] S0754-0003 created
- [ ] S0754-0002 exam registration preserved (count = 1)
- [ ] **CRITICAL**: Marks/results still exist for S0754-0002

---

### TEST 6: Validation Errors

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_c.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

**Expected Response:**
```json
{
  "success": false,
  "total_rows": 2,
  "error_count": 2,
  "can_import": false,
  "errors": [
    {
      "row_number": 1,
      "candidate_id": "S0754-0004",
      "error_messages": ["full_name is required"]
    },
    {
      "row_number": 2,
      "candidate_id": "S0754-0005",
      "error_messages": ["Invalid gender or school not found"]
    }
  ]
}
```

**Verify:**
- [ ] success = false
- [ ] error_count = 2
- [ ] can_import = false
- [ ] Row 1 has error message about full_name
- [ ] Row 2 has error message about gender or school

---

### TEST 7: Duplicate in File Detection

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_d.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

**Expected Response:**
```json
{
  "success": false,
  "total_rows": 2,
  "create_count": 1,
  "error_count": 1,
  "can_import": false,
  "rows": [
    {"row_number": 1, "candidate_id": "S0754-0020", "status": "NEW"},
    {"row_number": 2, "candidate_id": "S0754-0020", "status": "ERROR", "messages": ["Duplicate candidate_id in file"]}
  ]
}
```

**Verify:**
- [ ] Row 1 status = "NEW"
- [ ] Row 2 status = "ERROR"
- [ ] Row 2 messages include "Duplicate candidate_id in file"
- [ ] can_import = false (due to duplicate)

---

### TEST 8: Missing Mode Parameter (Backward Compat)

**API Call (no on_exists_mode parameter):**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE"
```

**Expected Response:**
```json
{
  "success": true,
  "create_count": 1,
  "skip_count": 2,
  "update_count": 0,
  ...
}
```

**Verify:**
- [ ] Mode defaults to "skip"
- [ ] Behaves exactly like TEST 2
- [ ] No error from missing parameter

---

### TEST 9: Invalid Mode Parameter

**API Call:**
```bash
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "on_exists_mode=INVALID"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "The selected on_exists_mode is invalid",
  ...
}
```

**Verify:**
- [ ] Returns validation error
- [ ] HTTP 422 status
- [ ] Message mentions invalid on_exists_mode

---

### TEST 10: Replace Mode Case Sensitivity

**API Calls:**
```bash
# Should FAIL
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "on_exists_mode=REPLACE"

# Should FAIL
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "on_exists_mode=Replace"

# Should SUCCEED
curl -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "on_exists_mode=replace"
```

**Verify:**
- [ ] "REPLACE" rejected (HTTP 422)
- [ ] "Replace" rejected (HTTP 422)
- [ ] "replace" accepted (HTTP 200)

---

## UI VERIFICATION

Open http://127.0.0.1:8000/registration/candidates

### Step 1: Mode Selection
- [ ] Radio button "Skip existing" visible (default selected)
- [ ] Radio button "Replace existing" visible
- [ ] Help text under each option clear
- [ ] Can switch between modes

### Step 2: Summary Cards
- [ ] 6 cards displayed (Total, New, Update/Skip, Errors, Can Import)
- [ ] Counts match API response
- [ ] "Will Update" card shows in replace mode
- [ ] "Will Skip" card shows in skip mode

### Step 2: Import Plan Table
- [ ] First 20 rows displayed
- [ ] Columns: Row #, ID, Name, Status badge
- [ ] Status badges correct colors:
  - [ ] NEW = blue
  - [ ] SKIP = amber
  - [ ] REPLACE = purple
  - [ ] ERROR = red
- [ ] Pagination hint for > 20 rows

### Step 2: Replace Warning
- [ ] Orange warning appears when mode=replace AND update_count > 0
- [ ] Message shows update count
- [ ] Explains fields being updated

### Import Button
- [ ] Button text shows "(create_count + update_count) Records"
- [ ] Disabled when can_import = false
- [ ] Enabled when can_import = true

---

## BACKWARD COMPATIBILITY TESTS

### API Compatibility
- [ ] validateCSV() without mode param defaults to 'skip'
- [ ] commitImport() without mode param defaults to 'skip'
- [ ] Response includes new fields; old code ignores them
- [ ] No database schema changes required

### Frontend Compatibility
- [ ] Old import modal code still works
- [ ] Skip mode behavior unchanged (default)
- [ ] No console errors in browser

---

## SAFETY RULE VERIFICATION

### Replace Mode Protections
```bash
php artisan tinker

# Test 1: Check that candidate_id is NOT changed
>>> $c = \App\Models\Candidate::find(1);
>>> $original_id = $c->candidate_id;
>>> // After replace update
>>> $c->fresh()->candidate_id === $original_id
# Should be: true (ID unchanged)

# Test 2: Check that exam registration is not deleted
>>> $c = \App\Models\Candidate::where('candidate_id', 'S0754-0002')->first();
>>> $reg_count_before = $c->examRegistrations()->count();
>>> // After replace update
>>> $c->fresh()->examRegistrations()->count() === $reg_count_before
# Should be: true (registrations preserved)

# Test 3: Check that marks are not deleted
>>> // (If marks system is implemented)
>>> $mark_count_before = \App\Models\SubjectMarks::where('candidate_id', $c->id)->count();
>>> // After replace update
>>> \App\Models\SubjectMarks::where('candidate_id', $c->id)->count() === $mark_count_before
# Should be: true (marks preserved)

exit
```

**Verify:**
- [ ] candidate_id never changes
- [ ] exam_type never changes
- [ ] combination never changes
- [ ] exam registrations never deleted
- [ ] marks never deleted
- [ ] School lookups validated (bad school codes → ERROR)

---

## DATA INTEGRITY TESTS

### Transactional Integrity
- [ ] On validation error: no records created
- [ ] On commit error: all changes rolled back
- [ ] Partial imports prevented (all-or-nothing)

### Duplicate Handling
- [ ] Duplicate within file marked as ERROR
- [ ] Later occurrence of duplicate rejected
- [ ] Error message clear

---

## PERFORMANCE TESTS

### Load Testing
- [ ] 100 rows: validation < 10 sec
- [ ] 100 rows: commit < 30 sec
- [ ] 1000 rows: validation < 60 sec (timeout-proof)
- [ ] Memory usage reasonable (streaming, not loading all at once)

---

## SUMMARY CHECKLIST

| Category | Status | Details |
|----------|--------|---------|
| Test 1: Skip mode (new only) | ⏳ | validate + commit |
| Test 2: Skip mode (mixed) | ⏳ | validate + commit |
| Test 3: Replace mode | ⏳ | validate + commit |
| Test 4: Commit skip preservation | ⏳ | DB unchanged |
| Test 5: Commit replace update | ⏳ | DB updated |
| Test 6: Validation errors | ⏳ | error_count correct |
| Test 7: Duplicate in file | ⏳ | detected, can_import=false |
| Test 8: Default mode | ⏳ | defaults to skip |
| Test 9: Invalid mode | ⏳ | rejected with error |
| Test 10: Case sensitivity | ⏳ | only lowercase accepted |
| UI Mode Selection | ⏳ | radio buttons work |
| UI Summary Cards | ⏳ | counts accurate |
| UI Import Plan Table | ⏳ | badges correct |
| UI Replace Warning | ⏳ | appears when needed |
| UI Import Button | ⏳ | shows count, enable/disable |
| Backward Compatibility | ⏳ | no breaking changes |
| Safety: candidate_id immutable | ⏳ | verified |
| Safety: exam registration preserved | ⏳ | verified |
| Safety: marks preserved | ⏳ | verified |
| Performance: acceptable | ⏳ | tested |

---

## FINAL SIGN-OFF

**All tests pass?** → Mark as ✅ READY FOR DEPLOYMENT

**Any failures?** → Log issues and fix before deployment

**Date Completed**: [Fill in date]  
**Tester Name**: [Fill in name]  
**Sign-off**: _______________

---

## How to Run This Checklist

1. Set up test database (DB SETUP section)
2. Create test CSV files (TEST CSV FILES section)
3. Run each TEST CASE using curl commands
4. Verify expected responses match
5. Check database state for commit tests
6. Open UI and verify Step 1 & Step 2 changes
7. Run safety tests
8. Fill in checklist as you go
9. Sign off when complete

**Time estimate**: 1-2 hours for thorough testing

