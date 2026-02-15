# Candidate Import: Skip vs Replace Mode

## Overview

The Candidate Import system now supports two operational modes for handling existing candidates:
- **Skip Mode** (default): Candidates that already exist in the database are skipped
- **Replace Mode**: Existing candidates are updated with new values from the import file

This document details the implementation, API contracts, and usage patterns.

---

## System Architecture

### Two-Phase Import Process

All candidate imports follow a two-phase validation + commit pattern:

```
Phase 1: VALIDATE
├─ Parse CSV file
├─ Check for validation errors
├─ Report conflicts (existing candidates)
├─ Count: create, update, skip operations
└─ Return preview WITHOUT modifying database

Phase 2: COMMIT
├─ Re-validate the file
├─ Create new candidates
├─ Update/Skip existing candidates (based on mode)
├─ Register for ACSEE if applicable
└─ Return final counts and errors
```

### API Endpoints

#### 1. Validate Import (Phase 1: Dry-run)
```
POST /api/candidates/import/validate
Content-Type: multipart/form-data

Parameters:
- file (required): CSV file
- exam_year (optional): 4-digit year (e.g., "2026")
- exam_type (optional): PSLE|CSEE|ACSEE (default from modal)
- on_exists_mode (optional): skip|replace (default: skip)

Response:
{
  "success": true,
  "message": "All rows valid",
  "total_rows": 10,
  "create_count": 7,
  "update_count": 2,
  "skip_count": 1,
  "error_count": 0,
  "errors": [],
  "rows": [
    {
      "row_number": 2,
      "candidate_id": "0001",
      "full_name": "John Doe",
      "status": "NEW",
      "messages": []
    },
    {
      "row_number": 3,
      "candidate_id": "0002",
      "full_name": "Jane Smith",
      "status": "SKIP",
      "messages": ["Candidate already exists"]
    }
  ],
  "summary": {},
  "can_import": true
}
```

#### 2. Commit Import (Phase 2: Actual write)
```
POST /api/candidates/import/commit
Content-Type: multipart/form-data

Parameters:
- file (required): Same CSV file from validation
- exam_year (optional): 4-digit year
- exam_type (optional): PSLE|CSEE|ACSEE
- on_exists_mode (optional): skip|replace (must match validation)

Response:
{
  "success": true,
  "message": "Import completed successfully",
  "imported_count": 7,
  "skipped_count": 1,
  "updated_count": 2,
  "errors": []
}
```

#### 3. Download Template
```
GET /api/candidates/import/template

Returns CSV template with headers:
- candidate_id
- full_name
- gender (M|F)
- combination (comma-separated for ACSEE)
- school_code
- exam_type (optional: PSLE|CSEE|ACSEE)
- exam_year (optional: 4-digit year)
```

#### 4. Download Error Report
```
POST /api/candidates/import/download-errors
Content-Type: application/json

Body:
{
  "errors": [
    {
      "row_number": 5,
      "candidate_id": "BAD001",
      "full_name": "Invalid Name",
      "gender": "X",
      "school_code": "SCH001",
      "combination": "Physics,Chemistry",
      "exam_type": "ACSEE",
      "error_messages": ["Gender must be M or F"]
    }
  ]
}

Returns: CSV file with error details
```

#### 5. Async Bulk Import (Background Processing)
```
POST /api/candidates/import/async
Content-Type: multipart/form-data

Parameters:
- file (required): CSV file (up to 50MB)
- exam_year (optional): 4-digit year
- exam_type (optional): PSLE|CSEE|ACSEE
- on_exists_mode (optional): skip|replace

Response: 202 Accepted
{
  "success": true,
  "message": "Import job dispatched. Processing in background...",
  "file_path": "imports/path/to/file.csv",
  "import_id": "import_abc123def456"
}
```

---

## CSV Format

### Required Columns
1. **candidate_id** (text): Unique identifier
   - Format: Free text or numeric
   - Examples: "0001", "CAN-001", "12345"
   - Must be unique within the file and database

2. **full_name** (text): Candidate's full name
   - Examples: "John Doe", "Jane Smith"
   - Non-empty required

3. **gender** (text): Single letter M or F
   - Case-insensitive (M, m, F, f accepted)
   - Examples: "M", "F"

4. **school_code** (text): School identifier
   - Format: School code, registration_number, or numeric ID
   - Examples: "SCH001", "123", "456"
   - Auto-creates school if not found (for imports with auto-school flag)

### Optional Columns
5. **combination** (text): Subject combination for ACSEE
   - Format: Comma-separated subject names or codes
   - Examples: "Physics,Chemistry,Biology", "PHY,CHM,BIO"
   - Only required for SCHOOL candidates in ACSEE
   - PRIVATE candidates use subject IDs (pipe-separated)

6. **exam_type** (text): Overrides modal exam_type
   - Values: PSLE, CSEE, ACSEE
   - Default: From modal selection

7. **exam_year** (text): Overrides modal exam_year
   - Format: 4-digit year
   - Examples: "2026", "2025"
   - Default: From modal selection

### Example CSV
```csv
candidate_id,full_name,gender,combination,school_code,exam_type,exam_year
0001,John Doe,M,Physics;Chemistry;Biology,SCH001,ACSEE,2026
0002,Jane Smith,F,Mathematics;Chemistry;Geography,SCH002,ACSEE,2026
0003,Tom Wilson,M,,SCH001,PSLE,2026
0004,Sarah Brown,F,English;Swahili;Civics,SCH003,CSEE,2026
```

---

## Mode Behavior

### Skip Mode (Default)
**Use case**: Avoid overwriting existing data; focus on new imports

**Behavior**:
```
For each candidate in CSV:
  IF exists in database:
    - Mark as SKIP
    - Do not modify database
    - Count toward skip_count
  ELSE:
    - Create new candidate
    - Count toward create_count
```

**Validation Phase Output**:
- `create_count`: Number of new candidates
- `skip_count`: Number of existing candidates (unchanged)
- `update_count`: 0
- `can_import`: true if create_count > 0

**Commit Phase Output**:
- `imported_count`: Created
- `skipped_count`: Skipped existing
- `updated_count`: 0

**Use cases**:
- First-time candidate imports
- Incremental batch imports
- Safe imports without data loss

---

### Replace Mode
**Use case**: Update candidate records with fresh data; handle corrections

**Behavior**:
```
For each candidate in CSV:
  IF exists in database:
    - Update existing candidate fields
    - Mark as REPLACE
    - Count toward update_count
  ELSE:
    - Create new candidate
    - Count toward create_count
```

**Fields Updated**:
- `full_name`
- `gender`
- `combination` (if provided and valid)
- `exam_type` (if different)
- `school_id` (resolved from school_code)

**Fields NOT Updated (Immutable)**:
- `candidate_id` (primary key)
- `exam_year` (once registered, preserved)
- `exam_registrations` (append-only)

**Validation Phase Output**:
- `create_count`: Number of new candidates
- `update_count`: Number of candidates to be updated
- `skip_count`: 0
- `can_import`: true if (create_count + update_count) > 0

**Commit Phase Output**:
- `imported_count`: Created
- `updated_count`: Updated
- `skipped_count`: 0

**Use cases**:
- Bulk name corrections
- Gender corrections
- School reassignment
- Combination updates before ACSEE registration

---

## Validation Rules

All validation occurs in Phase 1 and is re-validated in Phase 2.

### Candidate ID
- Required
- Must be unique within the CSV file
- Must be unique within the database (for new candidates)
- **Duplicate detection**: "Duplicate candidate_id in file"

### Full Name
- Required
- Non-empty string
- Max 255 characters
- **Error**: "Full name is required"

### Gender
- Required
- Must be M or F (case-insensitive)
- **Error**: "Gender must be M or F"

### School Code
- Required
- Must resolve to valid school
- Can be: school code, registration_number, or numeric ID
- **Error**: "School not found: {code}"

### Combination (ACSEE School Candidates)
- Required for SCHOOL candidates with exam_type=ACSEE
- Format: Comma-separated subject names or codes
- Must resolve to valid ACSEE subjects
- **Error**: "Combination not found or invalid"

### Exam Year (Optional)
- Format: 4-digit year
- Must be valid year in database (if provided)
- **Error**: "Invalid exam year format or year not found"

### Exam Type (Optional)
- Must be: PSLE, CSEE, ACSEE (if provided)
- **Error**: "Invalid exam type"

---

## Conflict Handling

### Duplicate in CSV File
```
Row 5: candidate_id = "0001"
Row 8: candidate_id = "0001"

Validation Result:
- Row 5: ERROR - "Duplicate candidate_id in file"
- Row 8: ERROR - "Duplicate candidate_id in file"
- can_import: false
```

### Duplicate in Database (Skip Mode)
```
Database: candidate_id = "0001" exists

CSV Row: candidate_id = "0001"

Validation Result:
- Status: SKIP
- No error
- Still counts toward can_import
```

### Duplicate in Database (Replace Mode)
```
Database: candidate_id = "0001" exists

CSV Row: candidate_id = "0001"

Validation Result:
- Status: REPLACE
- No error
- Updates existing record on commit
```

---

## Mixed Import Example

### Input CSV
```csv
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
0003,Existing Candidate,M,SCH003,Physics;Mathematics;English
```

### Database State (Before)
- candidate_id = "0003" exists for Jane Wilson

### Skip Mode

**Validation Phase**:
```
create_count: 2   (0001, 0002)
update_count: 0
skip_count: 1     (0003 - exists)
error_count: 0
can_import: true
```

**Commit Phase**:
```
imported_count: 2
updated_count: 0
skipped_count: 1
```

**Database State (After)**:
- 0001: NEW (John Doe)
- 0002: NEW (Jane Smith)
- 0003: UNCHANGED (Jane Wilson)

---

### Replace Mode

**Validation Phase**:
```
create_count: 2   (0001, 0002)
update_count: 1   (0003 - exists)
skip_count: 0
error_count: 0
can_import: true
```

**Commit Phase**:
```
imported_count: 2
updated_count: 1
skipped_count: 0
```

**Database State (After)**:
- 0001: NEW (John Doe)
- 0002: NEW (Jane Smith)
- 0003: UPDATED (Jane Wilson → Existing Candidate, same school/combination updated)

---

## Frontend Integration (Modal)

### Two-Phase Modal UI

**Step 1: Upload**
```
[File Input]
[Exam Type Dropdown: PSLE | CSEE | ACSEE]
[Exam Year Input: 2026]

⚪ Skip existing candidates (default)
⚫ Replace existing candidates

[Validate] [Cancel]
```

**Step 2: Review** (after successful validation)
```
Summary Cards:
┌──────────┬─────────────┬──────────────┬────────┐
│ New      │ Will Update │ Will Skip    │ Errors │
│ 7        │ 2           │ 1            │ 0      │
└──────────┴─────────────┴──────────────┴────────┘

Message: "7 new candidates will be created, 2 will be 
updated, 1 will be skipped"

[Preview Table]
Row | Candidate ID | Full Name  | Status | Actions
----|--------------|-----------|--------|--------
 2  | 0001         | John Doe  | NEW    | [detail]
 3  | 0002         | Jane Sm...| REPLACE| [detail]
 4  | 0003         | Tom Wil...| SKIP   | [detail]
... | ...          | ...       | ...    | ...

[Import] [Cancel]
```

### AJAX Calls

**Phase 1 - Validate**:
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('exam_type', 'ACSEE');
formData.append('exam_year', '2026');
formData.append('on_exists_mode', modeRadio.value); // 'skip' or 'replace'

fetch('/api/candidates/import/validate', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(r => r.json())
.then(data => {
    // Update summary cards
    // Show preview table
    // Enable [Import] button if data.can_import
})
```

**Phase 2 - Commit**:
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]); // Same file
formData.append('exam_type', 'ACSEE');
formData.append('exam_year', '2026');
formData.append('on_exists_mode', modeRadio.value); // Must match validation

fetch('/api/candidates/import/commit', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(r => r.json())
.then(data => {
    // Show success message
    // Display imported_count, updated_count, skipped_count
    // If errors, show download error report button
    // Close modal on success
})
```

---

## Error Handling

### Row-Level Errors

Errors are categorized and returned in the validation response:

```json
{
  "success": false,
  "message": "2 row(s) have errors",
  "error_count": 2,
  "errors": [
    {
      "row_number": 5,
      "candidate_id": "BAD001",
      "full_name": "Invalid Name",
      "gender": "X",
      "school_code": "SCH001",
      "combination": "Physics,Chemistry",
      "exam_type": "ACSEE",
      "error_messages": [
        "Gender must be M or F",
        "School not found: SCH001"
      ],
      "primary_error": "Gender must be M or F"
    },
    {
      "row_number": 8,
      "candidate_id": "0001",
      "full_name": "Duplicate Candidate",
      "gender": "M",
      "school_code": "SCH001",
      "combination": "Physics,Chemistry",
      "exam_type": "ACSEE",
      "error_messages": [
        "Duplicate candidate_id in file"
      ],
      "primary_error": "Duplicate candidate_id in file"
    }
  ],
  "can_import": false
}
```

### Error Report Download

If import succeeds but some rows fail in Phase 2:

```javascript
fetch('/api/candidates/import/download-errors', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf_token
    },
    body: JSON.stringify({
        errors: failedRows // From commit response
    })
})
.then(r => r.blob())
.then(blob => {
    // Download as CSV
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'candidate-import-errors.csv';
    a.click();
})
```

---

## Performance Considerations

### Large File Handling
- **Batch Processing**: Records are processed in chunks of 100
- **Query Optimization**: Uses eager loading and batch operations
- **Memory**: Streaming CSV parsing to avoid loading entire file into memory

### Async Import for Very Large Files
```
POST /api/candidates/import/async
- File size up to 50MB
- Dispatched to queue for background processing
- Returns immediately with import_id
- Supports skip/replace modes
```

### Database Transaction Safety
- Phase 1 (validate): Read-only, no locks
- Phase 2 (commit): All operations within transaction
  - Rollback on any error
  - Atomic create/update operations

---

## Testing Checklist

### Test Case 1: Skip Mode (New Candidates Only)
```
✓ Validate returns create_count=5, skip_count=0, update_count=0
✓ Commit returns imported_count=5, skipped_count=0, updated_count=0
✓ New candidates appear in database
```

### Test Case 2: Skip Mode (With Existing)
```
✓ Validate returns create_count=3, skip_count=2, update_count=0
✓ Commit returns imported_count=3, skipped_count=2, updated_count=0
✓ Existing candidates remain unchanged
```

### Test Case 3: Replace Mode (With Existing)
```
✓ Validate returns create_count=3, skip_count=0, update_count=2
✓ Commit returns imported_count=3, updated_count=2, skipped_count=0
✓ Existing candidates have updated full_name, gender, combination
✓ candidate_id and exam_registrations remain unchanged
```

### Test Case 4: Validation Errors
```
✓ Invalid candidate_id format → ERROR
✓ Missing full_name → ERROR
✓ Invalid gender (not M/F) → ERROR
✓ School not found → ERROR
✓ Invalid combination → ERROR
✓ Duplicate candidate_id in file → ERROR
```

### Test Case 5: ACSEE Registration
```
✓ SCHOOL candidate registered for ACSEE with combination
✓ PRIVATE candidate registered for ACSEE with subjects
✓ Both modes support ACSEE registration
```

---

## Troubleshooting

### "CSV file is empty"
- Check file has header row
- Check file is not corrupted
- Re-download template and compare format

### "Duplicate candidate_id in file"
- Check for duplicate entries in CSV
- Remove duplicates and re-submit

### "School not found: {code}"
- Verify school_code matches existing schools
- Check school code format (registration_number, code, or ID)
- Use admin panel to verify school exists

### "Invalid combination"
- Verify subjects exist in ACSEE subjects list
- Check spelling and format (comma-separated)
- Contact admin to add missing subjects

### Import appears to hang
- Check file size (max 50MB for async)
- Use async endpoint for files > 5MB
- Check server logs for database lock issues

---

## API Reference Summary

| Endpoint | Method | Purpose | Mode-aware |
|----------|--------|---------|-----------|
| `/api/candidates/import/validate` | POST | Dry-run validation | Yes |
| `/api/candidates/import/commit` | POST | Commit import | Yes |
| `/api/candidates/import/template` | POST | Download template | No |
| `/api/candidates/import/download-errors` | POST | Download error report | No |
| `/api/candidates/import/async` | POST | Background import | Yes |

---

## Implementation Details

### CandidateImportController
Location: `app/Http/Controllers/CandidateImportController.php`

Methods:
- `validateImport()`: Phase 1 validation
- `commitImport()`: Phase 2 commit
- `downloadTemplate()`: Get CSV template
- `downloadErrorReport()`: Get error CSV
- `asyncBulkImport()`: Background processing

### CandidateImportService
Location: `app/Services/Candidates/CandidateImportService.php`

Methods:
- `validateCSV()`: Parse and validate file
- `commitImport()`: Apply changes to database
- `updateCandidate()`: Update existing candidate
- `registerForACSEE()`: Register for ACSEE exam
- Various validation helpers

### Routes
Location: `routes/api.php` (lines 209-215)

```php
Route::prefix('candidates/import')->middleware(['auth'])->group(function () {
    Route::post('/validate', [CandidateImportController::class, 'validateImport']);
    Route::post('/commit', [CandidateImportController::class, 'commitImport']);
    Route::post('/template', [CandidateImportController::class, 'downloadTemplate']);
    Route::post('/download-errors', [CandidateImportController::class, 'downloadErrorReport']);
    Route::post('/async', [CandidateImportController::class, 'asyncBulkImport']);
});
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-15 | Initial implementation with skip/replace modes |

---

## Support & Questions

For implementation questions or bug reports, refer to:
- Code comments in `CandidateImportService.php`
- Test examples in this document
- API response examples in each endpoint section
