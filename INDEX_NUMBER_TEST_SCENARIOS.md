# Index Number Validation - Test Scenarios

**Date**: 2026-02-15  
**Purpose**: Manual testing guide for validation engine  

---

## Setup (Before Testing)

1. **Create test school**:
   ```bash
   php artisan tinker
   > $school = App\Models\School::create(['code' => 'TEST01', 'registration_number' => 'S0445', 'name' => 'Test School', 'region_id' => 1])
   > exit
   ```

2. **Create test exam year**:
   ```bash
   > $year = App\Models\ExamYear::where('year_label', '2026')->first() ?? App\Models\ExamYear::create(['year_label' => '2026', 'is_active' => true])
   > exit
   ```

3. **Verify ACSEE exam type exists**:
   ```bash
   > App\Models\ExamType::where('code', 'ACSEE')->first()
   ```

---

## Test Scenarios

### Scenario 1: Valid SCHOOL Candidate (Happy Path)

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S0445-0001",
  "full_name": "John Doe",
  "gender": "M",
  "exam_type": "ACSEE",
  "combination": "PHYSICS-CHEMISTRY-MATHEMATICS"
}
```

**Expected Output**:
```json
{
  "success": true,
  "message": "Candidate registered successfully",
  "data": {
    "id": 1,
    "school_id": 1,
    "candidate_id": "S0445-0001",
    "full_name": "John Doe",
    "gender": "M",
    "candidate_type": "SCHOOL",
    "created_at": "2026-02-15T10:00:00Z"
  }
}
```

**Verifications**:
- ✅ Candidate created with `candidate_type = 'SCHOOL'`
- ✅ Exam registration created for ACSEE
- ✅ No errors in response

---

### Scenario 2: Empty Index Number

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "",
  "full_name": "Jane Doe",
  "gender": "F",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Index number cannot be empty",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "INDEX_EMPTY",
      "message": "Index number cannot be empty",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ Error returned before DB operation

---

### Scenario 3: Invalid Format - Missing Hyphen

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S04450001",
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Invalid format. Use: CCCC-SSSS (e.g., S0445-0001)",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "INDEX_FORMAT_INVALID",
      "message": "Invalid format. Use: CCCC-SSSS (e.g., S0445-0001)",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ Hyphen delimiter required

---

### Scenario 4: Invalid Prefix

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "X0445-0001",
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Unknown centre prefix. Must be S (School) or P (Private)",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "CENTRE_PREFIX_UNKNOWN",
      "message": "Unknown centre prefix. Must be S (School) or P (Private)",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ Only S and P accepted

---

### Scenario 5: Centre Not Found

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S9999-0001",
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Centre not found in system",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "CENTRE_NOT_FOUND",
      "message": "Centre not found in system",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ School with registration_number='S9999' must not exist

---

### Scenario 6: Duplicate Index Number (Same Exam Year + Type)

**Setup**:
1. Create first candidate with S0445-0001
2. Try to create second candidate with same index

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S0445-0001",  // Same as first candidate
  "full_name": "Another User",
  "gender": "F",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "This index number is already registered for this exam",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "DUPLICATE_INDEX_NUMBER",
      "message": "This index number is already registered for this exam",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**:
- ✅ First candidate exists in DB
- ✅ Second attempt blocked with DUPLICATE error
- ✅ DB unchanged (transaction rolled back)

---

### Scenario 7: Same Index, Different Exam Years (Allowed)

**Setup**:
1. Create exam year 2025
2. Create candidate in 2025 with S0445-0001
3. Try to create same index in 2026

**Input** (for 2026):
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S0445-0001",  // Same index
  "full_name": "Same Student",
  "gender": "M",
  "exam_type": "ACSEE",
  "exam_year": "2026"
}
```

**Expected Output**:
```json
{
  "success": true,
  "message": "Candidate registered successfully",
  "data": {...}
}
```

**HTTP Status**: 201  
**Verification**:
- ✅ First candidate exists in 2025
- ✅ Second candidate created in 2026 (different year)
- ✅ Both have same index_number but different exam_year_id
- ✅ No duplicate error

---

### Scenario 8: Update Candidate - Keep Same Index

**Setup**:
1. Create candidate with S0445-0001
2. Update same candidate without changing index

**Input**:
```
PUT /api/candidates/1
{
  "school_id": 1,
  "candidate_id": "S0445-0001",  // Same index
  "full_name": "Updated Name",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": true,
  "message": "Candidate updated successfully",
  "data": {...}
}
```

**HTTP Status**: 200  
**Verification**:
- ✅ Candidate updated (full_name changed)
- ✅ No duplicate error (self ignored)

---

### Scenario 9: Update Candidate - Change Index

**Setup**:
1. Create candidate with S0445-0001
2. Update same candidate to S0445-0002

**Input**:
```
PUT /api/candidates/1
{
  "school_id": 1,
  "candidate_id": "S0445-0002",  // Changed index
  "full_name": "Updated Name",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": true,
  "message": "Candidate updated successfully",
  "data": {
    "id": 1,
    "candidate_id": "S0445-0002",
    ...
  }
}
```

**HTTP Status**: 200  
**Verification**:
- ✅ Candidate updated with new index
- ✅ No duplicate error (checked against other candidates, not self)
- ✅ Old index (S0445-0001) no longer used by this candidate

---

### Scenario 10: Update to Duplicate Index (Should Fail)

**Setup**:
1. Create candidate 1 with S0445-0001
2. Create candidate 2 with S0445-0002
3. Try to update candidate 2 to S0445-0001 (duplicate)

**Input**:
```
PUT /api/candidates/2
{
  "school_id": 1,
  "candidate_id": "S0445-0001",  // Same as candidate 1
  "full_name": "Updated",
  "gender": "F",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "This index number is already registered for this exam",
  "field": "index_number",
  "validation_errors": [...]
}
```

**HTTP Status**: 422  
**Verification**:
- ✅ Candidate 1 still has S0445-0001
- ✅ Candidate 2 still has S0445-0002 (update rejected)
- ✅ Duplicate error returned

---

### Scenario 11: Invalid Serial Length

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S0445-001",  // Only 3 digits, needs 4
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Serial number must be 4 digits",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "SERIAL_INVALID",
      "message": "Serial number must be 4 digits",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ Serial must be exactly 4 digits

---

### Scenario 12: Invalid Centre Code Length

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S044-0001",  // Only 3 digits after S, needs 4
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Centre code must be 4 digits after prefix",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "CENTRE_CODE_INVALID",
      "message": "Centre code must be 4 digits after prefix",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ Centre code must be exactly 4 digits (after prefix)

---

### Scenario 13: Non-Numeric Serial

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "S0445-ABCD",  // Letters instead of digits
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": false,
  "message": "Serial number must be 4 digits",
  "field": "index_number",
  "validation_errors": [
    {
      "code": "SERIAL_INVALID",
      "message": "Serial number must be 4 digits",
      "field": "index_number"
    }
  ]
}
```

**HTTP Status**: 422  
**Verification**: ✅ Serial must be numeric only

---

### Scenario 14: Whitespace Normalization

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "  S0445-0001  ",  // With spaces
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": true,
  "message": "Candidate registered successfully",
  "data": {
    "id": 1,
    "candidate_id": "S0445-0001",  // Spaces trimmed
    ...
  }
}
```

**HTTP Status**: 201  
**Verification**:
- ✅ Whitespace trimmed automatically
- ✅ Stored as "S0445-0001" (normalized)

---

### Scenario 15: Lowercase Normalization

**Input**:
```
POST /api/candidates
{
  "school_id": 1,
  "candidate_id": "s0445-0001",  // Lowercase
  "full_name": "Test User",
  "gender": "M",
  "exam_type": "ACSEE"
}
```

**Expected Output**:
```json
{
  "success": true,
  "message": "Candidate registered successfully",
  "data": {
    "id": 1,
    "candidate_id": "S0445-0001",  // Uppercase
    ...
  }
}
```

**HTTP Status**: 201  
**Verification**:
- ✅ Lowercase converted to uppercase automatically
- ✅ Stored as "S0445-0001" (normalized)

---

### Scenario 16: Admin Scan for Duplicates

**Setup**:
1. Create 2 candidates with same index in same exam context (via direct DB or by temporarily disabling validation)
2. Run scan command

**Command**:
```bash
php artisan necta:scan-duplicate-index
```

**Expected Output**:
```
Scanning for duplicate index numbers...

+-------+-----------+--------+------+-----------+-----------+----------+
| ID    | Index     | School | Name | Exam Year | Exam Type | Dups     |
+-------+-----------+--------+------+-----------+-----------+----------+
| 1     | S0445-001 | School | John | 2026      | ACSEE     | 2        |
| 2     | S0445-001 | School | Jane | 2026      | ACSEE     | 2        |
+-------+-----------+--------+------+-----------+-----------+----------+

⚠ Found 2 duplicate entries (across 1 group)
Review and resolve these manually before applying unique constraints.
```

**Verification**:
- ✅ Scan detects all duplicates
- ✅ Grouped by exam context
- ✅ Shows candidate details
- ✅ Advises manual resolution

---

## Admin Command Testing

### Test 1: Export to JSON
```bash
php artisan necta:scan-duplicate-index --output=json --export=/tmp/dupes.json
cat /tmp/dupes.json
```

**Expected**: JSON file with duplicate details

### Test 2: Export to CSV
```bash
php artisan necta:scan-duplicate-index --output=csv --export=/tmp/dupes.csv
cat /tmp/dupes.csv
```

**Expected**: CSV file with duplicate details

### Test 3: Filter by Exam Year
```bash
php artisan necta:scan-duplicate-index --exam-year=2026
```

**Expected**: Only duplicates from 2026 shown

### Test 4: Filter by Exam Type
```bash
php artisan necta:scan-duplicate-index --exam-type=ACSEE
```

**Expected**: Only ACSEE duplicates shown

### Test 5: Filter by Both
```bash
php artisan necta:scan-duplicate-index --exam-year=2026 --exam-type=ACSEE
```

**Expected**: Only 2026 ACSEE duplicates shown

---

## Database Testing

### Verify Unique Constraint

After migration runs successfully:

```bash
php artisan tinker

# Create 2 candidates with same index in same exam
> $c1 = App\Models\Candidate::create(['school_id' => 1, 'candidate_id' => 'TEST001', 'gender' => 'M'])
> $c2 = App\Models\Candidate::create(['school_id' => 1, 'candidate_id' => 'TEST002', 'gender' => 'F'])
> $year = App\Models\ExamYear::first()
> $type = App\Models\ExamType::where('code', 'ACSEE')->first()

# Try to create 2 registrations with same index
> App\Models\CandidateExamRegistration::create(['candidate_id' => $c1->id, 'exam_year_id' => $year->id, 'exam_type_id' => $type->id, 'year' => 2026])
> App\Models\CandidateExamRegistration::create(['candidate_id' => $c2->id, 'exam_year_id' => $year->id, 'exam_type_id' => $type->id, 'year' => 2026])

# Expected: Second insert should fail with UNIQUE constraint error
```

---

## Summary

**Total Test Scenarios**: 16  
**Coverage**:
- ✅ Happy path (valid input)
- ✅ Empty/missing input
- ✅ Format validation (all cases)
- ✅ Centre resolution (found/not found)
- ✅ Duplicate detection (various scenarios)
- ✅ Update scenarios (same index, new index, duplicate)
- ✅ Normalization (spaces, case)
- ✅ Admin commands (scan, export, filter)
- ✅ Database constraints

**Pass Criteria**:
- All 16 scenarios produce expected output
- No data loss or corruption
- Error messages are user-friendly
- Admin tools work correctly
- Unique constraint prevents duplicates

---

