# ACSEE Enhanced Marks Import System

## Overview

This document describes three major enhancements to the ACSEE CSV marks import system:
1. **Professional CSV Template Generation** - Minimal data exposure, school/subject/year-specific
2. **CSV Checksum Integrity Verification** - Detect modifications, mismatches, and tampering
3. **Row-Level Locking** - Prevent accidental or malicious modification of processed marks

---

## PART 1: PROFESSIONAL CSV TEMPLATE GENERATION

### Service: `AcseeMarkTemplateService`

**Location:** `app/Services/MarkImport/AcseeMarkTemplateService.php`

### Design Principles

- **Minimal Data Exposure**: Templates expose ONLY `index_number` and `sex` (read-only reference)
- **No Full Names**: Candidate identification relies ONLY on index_number
- **Scope-Specific**: Templates are school-, subject-, and exam-year specific
- **Professional Format**: Filenames follow NECTA conventions

### Template Structure

**Columns:**
```
index_number,sex,paper_p1,paper_p2,paper_p3,[practical],[project]
S000001,M,,,,
S000002,F,,,,
...
```

**Rules:**
- `index_number`: Candidate ID (e.g., S000001)
- `sex`: M or F (read-only reference only)
- `paper_p1`, `paper_p2`, etc.: Dynamically generated based on subject paper structure
- `practical`: Optional, if subject has practical component
- `project`: Optional, if subject has project component
- All mark cells are empty (to be filled in)

### Eligible Candidates

Templates include ONLY candidates who:
1. Are registered for ACSEE
2. Belong to the selected school
3. Have a subject selection that includes the selected subject
4. Are marked as active for the specified exam year

### File Naming Convention

Format: `<SCHOOL_NAME>_<SUBJECT_CODE>.csv`

**Rules:**
- School name: Uppercase, spaces replaced with underscores, special characters removed
- Subject code: Uppercase

**Examples:**
```
LUGALO_SECONDARY_SCHOOL_PHY.csv
ST_ANDREWS_SECONDARY_CHEMI.csv
MOSHI_CO_SECONDARY_BIO.csv
```

### Usage

#### Download Template

```php
// In MarkEntryController
$request->validate([
    'exam_year' => 'required|integer',
    'school_id' => 'required|exists:schools,id',
    'subject_id' => 'required|exists:subjects,id',
]);

// Service automatically:
// 1. Generates template with eligible candidates
// 2. Generates professional filename
// 3. Creates batch for tracking
// 4. Generates and stores checksum for integrity verification
```

**API Endpoint:**
```
POST /api/mark-entry/download-template
{
    "exam_year": 2024,
    "school_id": 5,
    "subject_id": 12
}
```

---

## PART 2: CSV CHECKSUM & INTEGRITY VERIFICATION

### Service: `CsvIntegrityService`

**Location:** `app/Services/MarkImport/CsvIntegrityService.php`

### Model: `MarkImportChecksum`

**Location:** `app/Models/MarkImportChecksum.php`

**Table:** `mark_import_checksums`

**Fields:**
- `mark_import_batch_id` (FK)
- `checksum` (SHA-256 hash)
- `candidate_count` (integer)
- `candidate_index_numbers` (JSON array)
- `generated_at` (timestamp)

### Checksum Computation

Checksums are computed from:
1. Exam year
2. School ID
3. Subject ID
4. Paper structure (number of papers, practical, project flags)
5. **Ordered list of candidate index numbers** (critical for integrity)
6. Header structure (for uploaded file validation)

The algorithm uses SHA-256 hashing of a JSON-serialized checksum data structure.

### Process Flow

#### 1. Template Generation Phase

```
User requests template
  ↓
AcseeMarkTemplateService::generateTemplate()
  ↓
Service retrieves eligible candidates
  ↓
Service generates CSV
  ↓
MarkImportService::createBatch()
  ↓
CsvIntegrityService::generateAndStoreChecksum()
  ↓
Template + Batch ID returned to user
```

**Result:** Checksum stored securely in DB, linked to batch

#### 2. CSV Upload Phase

```
User uploads CSV
  ↓
MarkImportService::processCSVUpload()
  ↓
CsvIntegrityService::verifyUploadedCSV()
  ↓
Checksum computation from uploaded file
  ↓
Hash comparison (constant-time comparison)
  ↓
Header structure verification
  ↓
Candidate count verification
  ↓
Proceed or reject
```

### Integrity Checks

The service detects:

1. **Added Candidates**: CSV contains new index numbers not in template
2. **Removed Candidates**: CSV is missing candidates from template
3. **Altered Headers**: Column structure changed
4. **Wrong CSV Reused**: Different subject/school/year CSV uploaded
5. **Modified Content**: Any change to candidate list or structure

### Error Messages

Clear, actionable errors are returned to user:

```
"Uploaded CSV does not match the generated template or has been modified. 
Please ensure you are using the correct template and have not added or removed candidates."

"CSV header structure is incorrect. Expected 6 columns but found 5."

"Number of candidates in CSV does not match template. Expected 45 but found 42."
```

### Security Properties

- **Hash-based**: Uses cryptographically secure SHA-256
- **Constant-time comparison**: Prevents timing attacks via `hash_equals()`
- **Candidate-ordered**: Detects reordering or manipulation
- **Immutable**: Stored checksum cannot be modified retroactively

---

## PART 3: ROW-LEVEL LOCKING

### Service: `MarkRowLockingService`

**Location:** `app/Services/MarkImport/MarkRowLockingService.php`

### Model Updates: `RawMark`

**New Fields:**
- `is_locked` (boolean, default: false)
- `locked_at` (timestamp, nullable)
- `locked_by` (foreign key to users, nullable)

**New Scopes:**
- `locked()` - Retrieve locked rows
- `unlocked()` - Retrieve unlocked rows

**New Methods:**
```php
$rawMark->lock(int $userId): self
$rawMark->unlock(int $userId): self
$rawMark->preventLocked(string $operation = 'update'): self
```

### Locking Lifecycle

```
CSV Imported → Validation Passes → Rows Locked → Cannot be modified
                                        ↑
                                        └─ Automatic on success
```

#### Stage 1: Draft (Unlocked)
- Rows can be created, updated, deleted
- Validation is running
- Errors can be fixed

#### Stage 2: After Validation Success (Locked)
- Rows are automatically locked
- Prevents accidental modification
- UI displays marks as read-only

#### Stage 3: Unlock (Restricted)
- Only authorized roles can unlock
- Reason must be provided
- All actions logged for audit

### Locking Operations

#### Lock Batch Rows

```php
$this->lockingService->lockBatchRows(
    $batch,                    // MarkImportBatch
    auth()->id()              // User ID (who locked)
): array
```

**Returns:**
```php
[
    'success' => true,
    'locked_count' => 45,
    'failed_count' => 0,
    'errors' => []
]
```

**Logged as:**
```
INFO: Batch BATCH-5-12-2024-202402011530 rows locked
- locked_count: 45
- locked_by: 3
```

#### Lock Specific Rows

```php
$this->lockingService->lockSpecificRows(
    [1, 2, 3, 4, 5],          // Row IDs
    auth()->id()
): array
```

#### Unlock Batch Rows (Restricted)

```php
$this->lockingService->unlockBatchRows(
    $batch,
    auth()->id(),
    'Correcting candidate data - approved by Principal'  // Reason
): array
```

**Logged as:**
```
WARNING: Batch BATCH-5-12-2024-202402011530 rows unlocked
- unlocked_count: 45
- unlocked_by: 3
- reason: "Correcting candidate data - approved by Principal"
```

#### Unlock Specific Row

```php
$this->lockingService->unlockSpecificRow(
    $rowId,
    auth()->id(),
    'Data entry error - fixed'
): array
```

### Prevention Mechanisms

#### Method 1: Model-Level Prevention

```php
$rawMark->preventLocked('update');  // Throws Exception if locked
```

#### Method 2: Service-Level Prevention

```php
$this->lockingService->preventLockedRowUpdate($rawMark);
$this->lockingService->preventLockedRowDelete($rawMark);
```

#### Method 3: Query-Level

```php
// Work only on unlocked rows
$unlockedRows = $batch->rawMarks()->unlocked()->get();
```

### Audit Trail

All lock/unlock operations are logged via Laravel's logging system.

**Log Location:** `storage/logs/laravel.log`

**Format:**
```
[2024-02-01 15:30:45] local.INFO: Batch BATCH-5-12-2024-202402011530 rows locked {"batch_id":42,"locked_count":45,"failed_count":0,"locked_by":3}

[2024-02-01 16:15:22] local.WARNING: Batch BATCH-5-12-2024-202402011530 rows unlocked {"batch_id":42,"unlocked_count":45,"failed_count":0,"unlocked_by":3,"reason":"Correcting data"}

[2024-02-01 16:16:10] local.INFO: RawMark row 245 unlocked by user 3 {"batch_id":42,"row_number":3,"index_number":"S000003"}
```

### Authorization Considerations

**TODO:** Implement authorization policies for unlock operations

Recommended authorization:
- **Lock**: Can be performed by any user with mark entry permission
- **Unlock**: Restricted to:
  - Examination Officer (Regional)
  - Examination Officer (District)
  - System Administrator
  - NOT regular data entry clerks

Example implementation:
```php
// In MarkEntryController
$this->authorize('unlock-marks', $batch);  // Will be added

// In Policy
public function unlockMarks(User $user, MarkImportBatch $batch)
{
    return $user->hasAnyRole(['examination_officer', 'admin']);
}
```

---

## Integration with Existing Workflow

### Changes to MarkImportService

```php
public function processCSVUpload(
    MarkImportBatch $batch,
    UploadedFile $file,
    int $examYear,        // NEW: For integrity check
    int $schoolId,        // NEW: For integrity check
    int $subjectId        // NEW: For integrity check
): array {
    // 1. Verify CSV integrity against stored checksum
    $integrityResult = $this->integrityService->verifyUploadedCSV(...);
    
    if (!$integrityResult['valid']) {
        return ['success' => false, 'error' => $integrityResult['error']];
    }
    
    // 2. Parse and process CSV (existing logic)
    // ...
    
    // 3. Rows will be locked automatically after validation passes
}
```

### Changes to MarkEntryController

#### Template Download
```php
public function downloadTemplate(Request $request)
{
    // NEW: Validate exam_year and school_id
    
    // Generate ACSEE template (replaces old logic)
    $csv = $this->acseeTemplateService->generateTemplate($examYear, $schoolId, $subjectId);
    
    // Create batch and generate checksum
    $batch = $this->importService->createBatch(...);
    $this->integrityService->generateAndStoreChecksum(..., $batch);
    
    // Return CSV
}
```

#### Upload Marks
```php
public function uploadMarks(Request $request)
{
    // Process CSV (includes integrity verification)
    $result = $this->importService->processCSVUpload(
        $batch,
        $request->file('file'),
        $examYear,
        $schoolId,
        $subjectId
    );
    
    // After validation, rows are automatically locked
    if ($validationResult['valid'] > 0) {
        $lockResult = $this->lockingService->lockBatchRows($batch, auth()->id());
    }
}
```

#### New Endpoints
```php
// Get locking status
GET /api/mark-entry/{batchId}/locking-status

// Unlock batch rows (restricted)
POST /api/mark-entry/{batchId}/unlock-rows
{
    "reason": "Correcting data per Principal approval"
}

// Unlock specific row (restricted)
POST /api/mark-entry/rows/{rowId}/unlock
{
    "reason": "Data entry error"
}
```

---

## Database Migrations

### Migration File
**File:** `database/migrations/2026_02_01_add_locking_and_checksum_to_raw_marks.php`

**Changes:**
1. Add fields to `raw_marks` table:
   - `is_locked` (boolean)
   - `locked_at` (timestamp)
   - `locked_by` (foreign key)

2. Create `mark_import_checksums` table:
   - `mark_import_batch_id` (FK)
   - `checksum` (string)
   - `candidate_count` (integer)
   - `candidate_index_numbers` (JSON)
   - `generated_at` (timestamp)

**Run Migration:**
```bash
php artisan migrate
```

---

## Testing Checklist

### CSV Template Generation
- [ ] Template contains only index_number and sex columns
- [ ] No full names in template
- [ ] Only eligible candidates included
- [ ] Paper columns match subject configuration
- [ ] Filename follows convention: SCHOOL_CODE_SUBJECT_CODE.csv
- [ ] Checksum is generated and stored

### CSV Integrity Verification
- [ ] Modified CSV (added candidate) is rejected
- [ ] Modified CSV (removed candidate) is rejected
- [ ] Modified CSV (altered headers) is rejected
- [ ] Modified CSV (reordered candidates) is rejected
- [ ] Wrong subject CSV is rejected
- [ ] Wrong school CSV is rejected
- [ ] Wrong year CSV is rejected
- [ ] Valid CSV passes verification
- [ ] Error messages are clear and actionable

### Row Locking
- [ ] Rows are locked after successful validation
- [ ] Locked rows cannot be updated
- [ ] Locked rows cannot be deleted
- [ ] Unlock operations log reason and user
- [ ] Locking status displays correctly
- [ ] Only authorized users can unlock
- [ ] Unlocked rows can be modified again

---

## Security Considerations

### 1. CSV Integrity
- Uses cryptographically secure SHA-256
- Constant-time hash comparison prevents timing attacks
- Candidate list ordering is significant (detects reordering)

### 2. Data Exposure
- Templates expose ONLY index_number and sex
- Full names NEVER included in templates
- School and subject scope prevents cross-contamination

### 3. Row Locking
- Prevents accidental modification via UI
- Prevents programmatic modification via API
- All lock/unlock actions logged
- Reason required for audit trail

### 4. Audit Trail
- All operations logged with timestamp and user
- Lock/unlock reasons recorded
- Batch-level and row-level tracking

---

## Troubleshooting

### "Uploaded CSV does not match the generated template"

**Causes:**
1. Different template downloaded (e.g., for different school)
2. Candidates added to CSV before upload
3. Candidates removed from CSV before upload
4. CSV modified in Excel or other tool

**Solution:**
- Download fresh template for exact school/subject/year
- Do NOT add or remove rows
- Do NOT modify headers
- Do NOT reorder candidates

### "CSV header structure is incorrect"

**Causes:**
1. Columns added to CSV
2. Columns deleted from CSV
3. Column order changed

**Solution:**
- Use original template structure
- Do NOT add or remove columns
- Do NOT reorder columns

### Cannot update locked row

**Cause:**
- Row was locked after successful processing

**Solution:**
- Unlock row first (if authorized)
- Provide reason for unlock
- Re-lock after correction

---

## Future Enhancements

1. **Email Notifications**
   - Notify when template is ready
   - Notify when import completes
   - Notify when unlock occurs

2. **Batch Re-download**
   - Allow re-download of template if needed
   - Update checksum on re-download

3. **Partial Locking**
   - Lock only rows without errors
   - Lock rows after moderation

4. **Unlock Approval Workflow**
   - Require approval before unlock
   - Track approval chain

5. **Comparative Integrity**
   - Compare CSV with previous import
   - Detect systematic changes

---

## API Reference

### Download Template

```
POST /api/mark-entry/download-template
Content-Type: application/json

{
    "exam_year": 2024,
    "school_id": 5,
    "subject_id": 12
}

Response: CSV file (LUGALO_SECONDARY_SCHOOL_PHY.csv)
```

### Upload Marks

```
POST /api/mark-entry/upload-marks
Content-Type: multipart/form-data

exam_year: 2024
school_id: 5
subject_id: 12
file: [CSV file]

Response:
{
    "success": true,
    "batch_id": 42,
    "batch_code": "BATCH-5-12-2024-202402011530",
    "message": "45 records imported",
    "validation": {
        "valid": 45,
        "invalid": 0,
        "total": 45
    },
    "locking": {
        "locked_count": 45,
        "unlocked_count": 0
    }
}
```

### Get Locking Status

```
GET /api/mark-entry/batches/{batchId}/locking-status

Response:
{
    "success": true,
    "data": {
        "batch_id": 42,
        "batch_code": "BATCH-5-12-2024-202402011530",
        "total_rows": 45,
        "locked_rows": 45,
        "unlocked_rows": 0,
        "lock_percentage": 100,
        "all_locked": true,
        "fully_unlocked": false
    }
}
```

### Unlock Batch Rows

```
POST /api/mark-entry/batches/{batchId}/unlock-rows
Content-Type: application/json

{
    "reason": "Correcting candidate data - approved by Principal"
}

Response:
{
    "success": true,
    "message": "Successfully unlocked 45 rows",
    "data": {
        "unlocked_count": 45,
        "failed_count": 0,
        "errors": []
    }
}
```

### Unlock Specific Row

```
POST /api/mark-entry/rows/{rowId}/unlock
Content-Type: application/json

{
    "reason": "Data entry error"
}

Response:
{
    "success": true,
    "message": "Row unlocked successfully"
}
```

---

## Summary

This enhancement delivers a secure, professional ACSEE mark entry workflow with:

✅ **Minimal Data Exposure** - Only index_number and sex in templates
✅ **Professional Format** - School-, subject-, and year-specific
✅ **Integrity Verification** - SHA-256 checksums detect modifications
✅ **Row Locking** - Prevent accidental or malicious changes
✅ **Audit Trail** - All operations logged with timestamp and reason
✅ **NECTA-Grade Security** - Suitable for national examination system
