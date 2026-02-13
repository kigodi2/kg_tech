# District Bulk Import - Complete Testing Guide

**Document Date**: February 1, 2026  
**Target**: Full end-to-end testing of district bulk CSV import system

---

## 1. Pre-Test Setup

### 1.1 Database & Migrations
```bash
# Apply all migrations
php artisan migrate

# Verify tables exist
php artisan tinker
>>> DB::table('bulk_imports')->count()
>>> DB::table('bulk_import_schools')->count()
```

### 1.2 Test Data
Create test district, schools, and exam year:

```php
// Create district
$district = District::create([
    'code' => 'TEST_DIST',
    'name' => 'Test District',
    'region_id' => 1
]);

// Create schools under district
$school1 = School::create([
    'code' => 'S0001',
    'name' => 'Test School 1',
    'district_id' => $district->id
]);

$school2 = School::create([
    'code' => 'S0002',
    'name' => 'Test School 2',
    'district_id' => $district->id
]);

// Create exam year
$examYear = ExamYear::create([
    'year' => 2025,
    'year_label' => 'ACSEE 2025',
    'exam_type_id' => 3  // ACSEE
]);

// Create candidates for school 1
Candidate::create([
    'candidate_id' => 'CAND001',
    'full_name' => 'John Doe',
    'gender' => 'M',
    'school_id' => $school1->id,
    'exam_type' => 'ACSEE'
]);

// Register candidates for exam year
CandidateExamRegistration::create([
    'candidate_id' => 1,
    'exam_year_id' => $examYear->id,
    'exam_type_id' => 3,
    'subject_combination_id' => 1
]);
```

### 1.3 Test User
Create district officer user:

```php
$user = User::create([
    'name' => 'District Officer',
    'email' => 'district.officer@test.local',
    'password' => bcrypt('password'),
    'role' => 'district_officer',
    'district_id' => $district->id
]);
```

### 1.4 Queue Configuration
```bash
# For testing, use sync driver
# In .env: QUEUE_CONNECTION=sync

# Or run queue worker in separate terminal
php artisan queue:work --timeout=3600
```

---

## 2. Test Case 1: Valid District ZIP Import

### 2.1 Create Valid ZIP Structure

**Directory structure**:
```
TEST_DIST_2025.zip
├── manifest.json
├── S0001_TEST_SCHOOL_1/
│   ├── PHY.csv
│   └── CHE.csv
└── S0002_TEST_SCHOOL_2/
    └── BIO.csv
```

**manifest.json content**:
```json
{
  "exam": "ACSEE",
  "exam_year": 2025,
  "scope": {
    "type": "district",
    "code": "TEST_DIST"
  },
  "generated_at": "2025-01-15T10:00:00Z",
  "generated_by": {
    "user_id": 5,
    "role": "district_officer"
  },
  "schools": [
    {
      "school_code": "S0001",
      "school_name": "Test School 1",
      "total_candidates": 100,
      "subjects": [
        {
          "code": "PHY",
          "papers": ["P1", "P2"],
          "candidates": 50,
          "checksum": "sha256:0000000000000000000000000000000000000000000000000000000000000000"
        },
        {
          "code": "CHE",
          "papers": ["P1", "P2"],
          "candidates": 50,
          "checksum": "sha256:1111111111111111111111111111111111111111111111111111111111111111"
        }
      ]
    },
    {
      "school_code": "S0002",
      "school_name": "Test School 2",
      "total_candidates": 75,
      "subjects": [
        {
          "code": "BIO",
          "papers": ["P1", "P2"],
          "candidates": 75,
          "checksum": "sha256:2222222222222222222222222222222222222222222222222222222222222222"
        }
      ]
    }
  ],
  "zip_checksum": "sha256:3333333333333333333333333333333333333333333333333333333333333333"
}
```

**PHY.csv content** (S0001/PHY.csv):
```
candidate_id,sex,papers,paper_1,paper_2,paper_3
CAND001,M,2,75,80,
CAND002,F,2,85,90,
```

**CHE.csv, BIO.csv**: Similar format

### 2.2 Test Steps

1. **Navigate to Mark Entry**
   - Go to `/mark-entry`
   - Verify "District Bulk ZIP" tab visible

2. **Access Upload Form**
   - Click "District Bulk ZIP" tab
   - Verify form displays

3. **Fill Form**
   - Select Exam Year: "2025 (ACSEE 2025)"
   - Select District: "TEST_DIST - Test District"
   - Verify "Preview" button enabled

4. **Upload ZIP**
   - Click upload area
   - Select `TEST_DIST_2025.zip`
   - Verify file displayed as "Selected: TEST_DIST_2025.zip"

5. **Preview**
   - Click "Preview" button
   - **Expect**:
     - Green checkmark: "ZIP is valid and ready to import"
     - Schools: 2
     - Subjects: 3
     - Candidates: 175
     - Signed: ✅ Yes
     - School list shows both schools with subjects

6. **Start Import**
   - Click "Start Import" button
   - **Expect**:
     - Progress section appears
     - Overall progress bar at 0%
     - Schools show "pending" status

7. **Monitor Progress**
   - Watch progress update every 2 seconds
   - **Expect**:
     - Schools transition: pending → processing → success
     - Progress percentage increases
     - Candidate counts update

8. **Verify Completion**
   - Wait for import to complete
   - **Expect**:
     - Completion section appears
     - Status badge: Green "COMPLETED"
     - Successful Schools: 2
     - Total Imported: 175 candidates
     - No failed/partial schools shown

9. **Verify Database**
   ```php
   // Check bulk_imports
   $import = BulkImport::latest()->first();
   assert($import->scope_type === 'district');
   assert($import->status === 'completed');
   assert($import->total_schools === 2);
   assert($import->processed_schools === 2);
   
   // Check bulk_import_schools
   $schools = $import->schools()->get();
   assert($schools->count() === 2);
   assert($schools->every(fn($s) => $s->pivot->status === 'success'));
   
   // Check marks imported
   $marks = SubjectMarks::where('exam_year_id', $examYear->id)->count();
   assert($marks > 0);
   ```

### 2.3 Expected Result
✅ **PASS**: All data imported, no errors, status "completed"

---

## 3. Test Case 2: ZIP with Validation Issues

### 3.1 Create Invalid ZIPs

**Missing School**:
```json
{
  "schools": [
    {
      "school_code": "S9999",  // Non-existent school
      "school_name": "Invalid School",
      "subjects": [...]
    }
  ]
}
```

**Invalid Subject**:
```json
{
  "subjects": [
    {
      "code": "INVALID",  // Non-existent subject
      "papers": ["P1"],
      "candidates": 50
    }
  ]
}
```

**Missing Manifest**:
```
ZIP without manifest.json
```

### 3.2 Test Steps

1. **Upload Invalid ZIP** (missing school)
2. **Click Preview**
3. **Expect**:
   - Red alert: "⚠️ Validation Issues Found"
   - Error message: "School S9999 not found in district TEST_DIST"
   - "Start Import" button remains disabled

4. **Verify No DB Changes**
   - Query `bulk_imports`: count unchanged

### 3.3 Expected Result
✅ **PASS**: Validation error caught, ZIP rejected, no DB writes

---

## 4. Test Case 3: Partial Failure (One School Fails)

### 4.1 Create ZIP with Problem School

Manifest:
```json
{
  "schools": [
    {
      "school_code": "S0001",
      "subjects": [
        {
          "code": "PHY",
          "papers": ["P1", "P2"],
          "candidates": 50
        }
      ]
    },
    {
      "school_code": "S0002",
      "subjects": [
        {
          "code": "INVALID",  // This will cause failure
          "papers": ["P1"],
          "candidates": 30
        }
      ]
    }
  ]
}
```

### 4.2 Test Steps

1. **Upload ZIP**
2. **Start Import**
3. **Monitor Progress**
   - School 1 (S0001): processing → success
   - School 2 (S0002): processing → failed

4. **Verify Completion**
   - Status badge: Yellow "PARTIAL"
   - Successful Schools: 1
   - Failed Schools: 1
   - Failed schools list shows:
     - S0002 - Test School 2
     - Error: "Subject INVALID not found"
     - "Retry This School" button

5. **Verify Database**
   ```php
   $import = BulkImport::latest()->first();
   assert($import->status === 'partial');
   
   $schools = $import->schools()->get();
   $successful = $schools->where('pivot.status', 'success');
   $failed = $schools->where('pivot.status', 'failed');
   
   assert($successful->count() === 1);
   assert($failed->count() === 1);
   ```

### 4.3 Expected Result
✅ **PASS**: Failure isolated, other school imported, status "partial"

---

## 5. Test Case 4: School-Level Failure Recovery

### 5.1 Prerequisite
Run Test Case 3 (creates partial import)

### 5.2 Test Steps

1. **View Failed School**
   - Failed school displayed in list
   - Error message visible
   - "Retry This School" button available

2. **Click Retry**
   - Confirm dialog: "Retry this school?"
   - Click OK

3. **Expect**:
   - Toast: "Retry started. Check progress below."
   - Progress section re-appears
   - S0002 shows "processing" status
   - Progress polling resumes

4. **Fix the Issue** (in background)
   - Replace CSV with valid data for S0002
   - Or manually update the ZIP in temp directory

5. **Verify Re-Import**
   - School S0002 re-imported
   - Status changes to "success"
   - Completion section updates

### 5.3 Expected Result
✅ **PASS**: Individual school retried successfully

---

## 6. Test Case 5: Retry All Failed Schools

### 6.1 Create ZIP with Multiple Failures

Manifest:
```json
{
  "schools": [
    {
      "school_code": "S0001",
      "subjects": [{"code": "INVALID1"}]  // Will fail
    },
    {
      "school_code": "S0002",
      "subjects": [{"code": "INVALID2"}]  // Will fail
    }
  ]
}
```

### 6.2 Test Steps

1. **Upload & Import**
   - Creates import with 2 failed schools

2. **Completion Section**
   - Status: "FAILED"
   - Both schools listed as failed
   - "Retry All Failed Schools" button visible

3. **Click Retry All**
   - Confirm: "Retry all failed schools?"
   - Toast: "Retry started for 2 schools"

4. **Expect**:
   - Progress section re-appears
   - Both schools show "pending" → "processing"
   - After retry, status updates

### 6.3 Expected Result
✅ **PASS**: Batch retry triggered, progress resumes

---

## 7. Test Case 6: Authorization & Access Control

### 7.1 School Officer Cannot Access
1. Login as school officer
2. Navigate to Mark Entry
3. **Expect**: "District Bulk ZIP" tab not visible or disabled

### 7.2 District Officer Can Access Own District
1. Login as district officer (district_id = TEST_DIST)
2. Navigate to Mark Entry
3. **Expect**: "District Bulk ZIP" tab visible and enabled
4. Can upload & import for own district

### 7.3 District Officer Cannot Access Other District
1. Login as district officer (district_id = OTHER_DISTRICT)
2. Try to upload for TEST_DIST via API
3. **Expect**: API returns 403 Forbidden

### 7.4 Admin Can Access All Districts
1. Login as admin
2. Can upload for any district

### 7.5 Regional Officer Can Access Districts in Region
1. Login as regional officer (region_id = TEST_REGION)
2. TEST_DIST is in TEST_REGION
3. Can upload for TEST_DIST

### 7.6 Expected Result
✅ **PASS**: Authorization rules enforced correctly

---

## 8. Test Case 7: Large Import (Performance)

### 8.1 Create Large ZIP
- 10 schools
- 100 subjects total
- 50,000 candidates total
- ~10MB ZIP file

### 8.2 Test Steps

1. **Upload Large ZIP**
2. **Monitor Progress**
   - Track polling requests (every 2s)
   - Verify no UI freezing
   - Check memory usage

3. **Verify Completion**
   - Import completes without timeout
   - Progress accurately tracked
   - All 50,000 candidates imported

### 8.3 Performance Metrics
- Preview time: < 5 seconds
- Job processing: ~10 min for 50k rows
- Memory usage: < 100MB
- CPU usage: Reasonable (chunked processing)

### 8.4 Expected Result
✅ **PASS**: Large import completes without errors

---

## 9. Test Case 8: Concurrent Imports

### 9.1 Test Steps

1. **Start Import 1** (District A, 1000 candidates)
2. **Immediately Start Import 2** (District B, 1000 candidates)
3. **Monitor Both Progress**
   - Both show independent progress
   - No interference between imports

4. **Verify Completion**
   - Both complete successfully
   - Database has 2 separate import records
   - No data cross-contamination

### 9.2 Expected Result
✅ **PASS**: Concurrent imports handled correctly

---

## 10. Test Case 9: ZIP Signature Verification

### 10.1 Create Signed ZIP

```php
// Sign manifest
$signer = new ZipSignerService();
$manifest['signature'] = [
    'algorithm' => 'HMAC-SHA256',
    'value' => $signer->signManifest($manifest),
    'signed_at' => now()->toIso8601String()
];
// Add to ZIP
```

### 10.2 Test Steps

1. **Upload Signed ZIP**
2. **Preview**
   - Shows "Signed: ✅ Yes"
   - Signature verified successfully

3. **Tamper with ZIP**
   - Modify manifest.json in extracted files
   - Try to import

4. **Expect**:
   - Signature verification fails
   - ZIP rejected with error

### 10.3 Expected Result
✅ **PASS**: Signature verification working

---

## 11. Test Case 10: Audit Trail

### 11.1 Verify Logs
After successful import:

```bash
# Check audit log
tail -100 storage/logs/audit.log
# Should contain:
#   - "District Bulk Import Started"
#   - user_id, district_id, zip_hash, timestamp, ip_address
```

### 11.2 Database Audit
```php
// Check bulk_import record
$import = BulkImport::latest()->first();
assert($import->zip_hash); // SHA-256 hash present
assert($import->manifest_hash); // Manifest hash present
assert($import->created_by); // User ID recorded
assert($import->created_at); // Timestamp recorded
```

### 11.3 Expected Result
✅ **PASS**: Complete audit trail recorded

---

## 12. Integration Tests (PHP Unit)

### 12.1 Test Suite Structure

```php
// tests/Feature/BulkImport/DistrictBulkImportTest.php

class DistrictBulkImportTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        // Create test data
    }
    
    public function test_valid_district_import() {
        // Upload valid ZIP, expect success
    }
    
    public function test_validation_errors() {
        // Invalid ZIP, expect rejection
    }
    
    public function test_school_failure_isolation() {
        // One school fails, others succeed
    }
    
    public function test_retry_failed_school() {
        // Retry mechanism works
    }
    
    public function test_authorization() {
        // School officer cannot upload
        // District officer can upload own
    }
}
```

### 12.2 Run Tests

```bash
php artisan test tests/Feature/BulkImport/DistrictBulkImportTest.php
```

---

## 13. Regression Testing

After any code changes, run:

```bash
# All tests
php artisan test

# Only bulk import tests
php artisan test --filter "BulkImport"

# Only district tests
php artisan test --filter "District"
```

---

## 14. UI/UX Testing Checklist

- [ ] Exam Year dropdown loads all exam years
- [ ] District dropdown loads all districts (user can access)
- [ ] Preview button disabled until all required fields
- [ ] File upload accepts .zip files only
- [ ] Drag & drop works on upload area
- [ ] File name displayed after selection
- [ ] Preview button triggers API call
- [ ] Valid ZIP shows green checkmark
- [ ] Invalid ZIP shows red warnings
- [ ] Schools list displays in preview
- [ ] Start Import button disabled until preview loaded
- [ ] Progress section appears after import starts
- [ ] Progress bar animates smoothly
- [ ] School status updates in real-time
- [ ] Progress stops when import completes
- [ ] Status badge color correct (green/yellow/red)
- [ ] Failed schools list shows (if any)
- [ ] Retry buttons functional
- [ ] Toast messages appear for errors
- [ ] Toast messages disappear after 4 seconds
- [ ] "Import Another ZIP" resets form

---

## 15. Performance Benchmarks

| Operation | Target | Notes |
|-----------|--------|-------|
| ZIP Preview | < 2s | Includes validation |
| Import Start | < 1s | Create records, dispatch jobs |
| Progress Fetch | < 100ms | Should be fast, runs every 2s |
| Per-Subject Import | < 5min | 500 rows, chunked |
| Complete 1000 candidates | < 10min | Across 5 schools |
| Memory usage | < 100MB | Chunked processing |

---

## 16. Deployment Checklist

- [ ] Database migrations applied
- [ ] Services registered in AppServiceProvider
- [ ] Routes configured
- [ ] Policies registered in AuthServiceProvider
- [ ] Queue worker running (async jobs)
- [ ] Audit logging configured
- [ ] Storage permissions correct (temp/imports)
- [ ] File upload size limit set appropriately
- [ ] CSRF protection enabled
- [ ] API rate limiting configured

---

## 17. Post-Test Summary

### Success Criteria
- All 10 test cases pass
- Authorization enforced
- Audit trail complete
- Failures isolated
- Recovery working
- UI responsive
- Performance acceptable

### Known Limitations
- No WebSocket (polling every 2s)
- File size limited by server config
- No built-in ZIP generation UI

### Sign-Off
- [ ] All tests passed
- [ ] No critical bugs found
- [ ] Performance acceptable
- [ ] Ready for production

---

**Test Date**: ____________  
**Tested By**: ____________  
**Approved By**: ____________

