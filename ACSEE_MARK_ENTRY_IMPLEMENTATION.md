# ACSEE Mark Entry Module - Implementation Documentation

## Overview

A professional CSV-based mark entry system for ACSEE (Advanced Certificate of Secondary Education) examinations. Designed for national-level educational assessments with emphasis on data integrity, auditability, and scalability.

## Architecture

### Core Principles
- **CSV-Only Import**: No manual/inline mark entry UI
- **Raw Data Staging**: Marks stored exactly as uploaded before processing
- **Batch-Based Processing**: Organized by school, subject, combination, and year
- **Comprehensive Validation**: Per-row error reporting with downloadable error logs
- **Audit Trail**: Full tracking of import, validation, lock, and processing stages
- **Transaction Safety**: Database transactions prevent partial/incomplete imports

## Database Schema

### `mark_import_batches` Table
Tracks each mark import batch with full lifecycle management.

```sql
- id (PK)
- batch_code (UNIQUE) - Auto-generated code (BATCH-{schoolId}-{subjectId}-{year}-{timestamp})
- exam_year - Academic year for marks
- region_id (FK) - Region where school is located
- district_id (FK) - District where school is located
- school_id (FK) - School importing marks
- subject_id (FK) - Subject being marked
- combination_id (FK) - Combination code (required for ACSEE)
- exam_type_id (FK) - Always ACSEE for this module
- status - enum: draft|validated|locked|processed
- total_records - Count of imported rows
- valid_records - Count of error-free rows
- error_records - Count of rows with validation errors
- imported_by (user_id)
- imported_at
- validated_by (user_id) - Who approved the batch
- validated_at
- locked_by (user_id) - Who locked for processing
- locked_at
- processed_by (user_id) - Who processed the grades
- processed_at
- notes - Optional admin comments
- timestamps
```

### `raw_marks` Table
Stores marks exactly as uploaded, before grade computation.

```sql
- id (PK)
- mark_import_batch_id (FK)
- candidate_id (FK) - Linked candidate
- row_number - CSV row for error reporting
- candidate_index_number - Index number as uploaded
- full_name - Name as uploaded
- paper_1_marks - Paper 1 raw mark (0-100)
- paper_2_marks - Paper 2 raw mark (0-100)
- paper_3_marks - Paper 3 raw mark (0-100, if applicable)
- practical_marks - Practical component (0-100, if applicable)
- project_marks - Project component (0-100, if applicable)
- has_errors - Boolean: validation failed?
- error_messages - JSON array of error strings
- raw_data - JSON: complete original CSV row
- processed_at - When grade was computed
- timestamps
```

## Models

### `MarkImportBatch`
**Location**: `app/Models/MarkImportBatch.php`

**Relationships**:
- `region()` - belongsTo Region
- `district()` - belongsTo District
- `school()` - belongsTo School
- `subject()` - belongsTo Subject
- `combination()` - belongsTo Combination
- `examType()` - belongsTo ExamType
- `rawMarks()` - hasMany RawMark

**Key Methods**:
- `isDraft()` / `isValidated()` / `isLocked()` / `isProcessed()` - Status checks
- `validate($validatedBy)` - Change status to VALIDATED
- `lock($lockedBy)` - Change status to LOCKED
- `process($processedBy)` - Change status to PROCESSED
- `getStatusBadgeClass()` - For UI styling
- `getErrorsCount()` - Count error records

**Status Flow**:
```
DRAFT → VALIDATED → LOCKED → PROCESSED
```

### `RawMark`
**Location**: `app/Models/RawMark.php`

**Relationships**:
- `batch()` - belongsTo MarkImportBatch
- `candidate()` - belongsTo Candidate

**Key Methods**:
- `addError(string $message)` - Append validation error
- `clearErrors()` - Remove all errors
- `getErrorsHtml()` - HTML representation of errors

## Service Layer

### `MarkImportService`
**Location**: `app/Services/MarkImport/MarkImportService.php`

**Responsibilities**:
1. Create new import batches
2. Parse CSV uploads
3. Extract marks based on paper structure
4. Create raw mark records
5. Coordinate validation process

**Key Methods**:
```php
createBatch(int $examYear, int $schoolId, int $subjectId, int $combinationId, string $importedBy): MarkImportBatch
processCSVUpload(MarkImportBatch $batch, UploadedFile $file): array
validateBatch(MarkImportBatch $batch): array
```

### `MarkValidationService`
**Location**: `app/Services/MarkImport/MarkValidationService.php`

**Validation Rules**:
1. Candidate must exist in database
2. Candidate must be registered for ACSEE
3. Subject must belong to candidate's combination
4. All required paper marks must be present
5. All marks must be numeric
6. All marks must be in valid range (0-100)

**Key Methods**:
```php
validateRawMark(RawMark $rawMark, MarkImportBatch $batch): array
// Returns array of error messages (empty if valid)
```

### `MarkTemplateService`
**Location**: `app/Services/MarkImport/MarkTemplateService.php`

**Responsibilities**:
1. Generate CSV headers based on paper structure
2. Create sample rows for data entry
3. Generate downloadable CSV files
4. Provide user instructions

**Key Methods**:
```php
generateTemplateHeaders(Subject $subject): array
generateSampleRows(Subject $subject, Combination $combination, int $sampleCount = 3): array
generateCsv(Subject $subject, Combination $combination): string
getInstructions(Subject $subject): string
```

## Controller

### `MarkEntryController`
**Location**: `app/Http/Controllers/MarkEntryController.php`

**Routes**:
```
GET  /mark-entry                                    - Dashboard view
GET  /mark-entry/download-template                 - Download CSV template
POST /mark-entry/upload                            - Upload and process marks
GET  /mark-entry/batch/{batchId}                   - Get batch details
GET  /mark-entry/batch/{batchId}/error-report      - Download error report
POST /mark-entry/batch/{batchId}/lock              - Lock batch

API Routes:
GET /api/mark-entry/regions                        - Get regions
GET /api/mark-entry/districts?region_id=X         - Get districts
GET /api/mark-entry/schools?district_id=X         - Get schools
GET /api/mark-entry/subjects                       - Get ACSEE subjects
GET /api/mark-entry/combinations                   - Get ACSEE combinations
```

## CSV Format

### Template Structure
```csv
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100),Practical (out of 100),...
S1378-0501,ADVENTINA GIDIONI ELIA,75,82,88
S1378-0502,AGRIPINA MAKOBE LUSATO,68,71,79
```

### Column Mapping
1. **Index Number**: Candidate's unique identifier (e.g., S1378-0501)
2. **Full Name**: Candidate's name (for verification)
3. **Paper 1-N**: Written paper marks (dynamic count per subject)
4. **Practical** (if applicable): Practical component
5. **Project** (if applicable): Project component

### Validation Rules
- All marks must be numeric (0-100)
- Missing required paper marks = row error
- Extra unknown columns = ignored
- Empty rows = skipped
- Duplicate index numbers = allowed (last one wins via updateOrCreate)

## User Workflow

### Step 1: Context Selection
User selects:
- Exam year
- Region → District → School (cascading dropdowns)
- Subject (ACSEE only)
- Combination code

### Step 2: Download Template
- Click "Download CSV Template"
- Template pre-configured for selected subject
- Includes sample rows (delete before upload)
- Includes header row (don't modify)

### Step 3: Data Entry
- Fill in candidate index numbers
- Enter marks for all required papers
- Practical/project marks if applicable
- Delete sample rows
- Save as CSV

### Step 4: Upload
- Click file upload area or drag-drop CSV
- System processes and validates
- View summary: Total, Valid, Errors
- If errors: Download error report, fix, re-upload

### Step 5: Lock & Process
- After validation passes (0 errors)
- Click "Lock Batch" to prevent further changes
- Admin can later process grades

## Error Handling

### Validation Errors
```json
{
  "has_errors": true,
  "error_messages": [
    "Candidate with index number 'S1378-XXXX' not found",
    "Candidate is not registered for ACSEE",
    "Subject MAT is not in candidate's combination (CBE)",
    "Paper 1 marks are missing or empty",
    "Paper 1 marks must be between 0 and 100 (got: 150)"
  ]
}
```

### Error Report Download
- Downloadable CSV with all validation errors
- Columns: Row Number, Index Number, Name, Errors
- User fixes errors and re-uploads

## Performance Considerations

### Batch Processing
- Single CSV upload = one batch
- Batch isolation prevents cross-contamination
- Up to 5MB CSV files supported
- Typical: 500+ candidates per import

### Database Transactions
```php
DB::beginTransaction();
  // Create batch
  // Process CSV
  // Validate all rows
DB::commit();
// Or rollback on error
```

### Indexing
```
- mark_import_batches.batch_code (UNIQUE)
- mark_import_batches.school_id, exam_year
- mark_import_batches.status
- raw_marks.mark_import_batch_id
- raw_marks.candidate_id
- raw_marks.has_errors
```

## Security & Audit

### Access Control
- Requires `auth` middleware
- Can be extended with role-based access (regional, district, school users)

### Audit Trail
- Every action tracked with user_id and timestamp
- Import user recorded
- Validation user recorded
- Lock user recorded
- Process user recorded

### Data Integrity
- Raw marks never deleted (history preserved)
- Transactions prevent partial imports
- Batch codes globally unique
- Lock mechanism prevents accidental overwrites

## Grade Computation (Future)

When marks are processed:
1. Read raw marks from `raw_marks` table
2. Compute total (paper_1 + paper_2 + practical + project)
3. Assign grade based on grading profile
4. Store in `subject_marks` table
5. Compute subject grade
6. Compute overall result

## API Response Examples

### Upload Success
```json
{
  "success": true,
  "batch_id": 123,
  "batch_code": "BATCH-65-4-2026-202601311545",
  "message": "450 records imported",
  "validation": {
    "valid": 450,
    "invalid": 0,
    "total": 450,
    "errors": []
  }
}
```

### Upload with Errors
```json
{
  "success": true,
  "batch_id": 123,
  "batch_code": "BATCH-65-4-2026-202601311545",
  "message": "445 records imported",
  "validation": {
    "valid": 445,
    "invalid": 5,
    "total": 450,
    "errors": [
      "Row 12: Candidate with index number 'INVALID' not found",
      "Row 28: Paper 1 marks must be between 0 and 100 (got: 250)",
      ...
    ]
  }
}
```

## Testing Checklist

- [ ] Create batch for valid context
- [ ] Upload valid CSV (all correct)
- [ ] Upload CSV with missing candidate
- [ ] Upload CSV with invalid marks (>100)
- [ ] Upload CSV with missing columns
- [ ] Upload CSV with extra columns
- [ ] Download error report
- [ ] Re-upload after fixing errors
- [ ] Lock batch
- [ ] Prevent modification after lock
- [ ] Validate transaction rollback on error

## Future Enhancements

1. **Bulk Processing**: Queue job for large imports
2. **Mark Re-entry**: Allow locked-batch mark updates with justification
3. **Grade Computation**: Automatic grade calculation
4. **Conditional Access**: Role-based access per region/district/school
5. **Analytics**: Import trends, error rates, performance metrics
6. **Notifications**: Email alerts for import completion/errors
7. **Scheduled Imports**: Automated import from partner systems
8. **Data Quality Dashboard**: Visual representation of mark distributions

## Code Quality Standards

- PSR-12 compliance
- Type hints on all methods
- Comprehensive validation
- Transaction safety
- Meaningful error messages
- Audit logging
- No direct database queries in controllers
- Service layer handles business logic
- Clean, readable code with documentation

---

**Version**: 1.0  
**Last Updated**: January 31, 2026  
**Status**: Production Ready
