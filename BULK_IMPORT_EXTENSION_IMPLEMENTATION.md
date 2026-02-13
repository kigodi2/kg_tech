# Bulk Import Extension - Complete Implementation

## Overview

This document covers the complete extension of the bulk CSV system with:

1. **Bulk Import Orchestration** - ZIP upload, validation, job queuing, progress tracking
2. **Cryptographic Signing** - SHA-256 HMAC signatures for integrity verification
3. **ZIP Preview** - Pre-import validation and candidate counting
4. **Stress Testing** - Command to test with up to 5,000 candidates

## Architecture

### Database Schema

#### bulk_imports
```sql
id                  | bigint | PK
school_id           | bigint | FK → schools
exam_year_id        | bigint | FK → exam_years
status              | enum   | pending, processing, completed, failed
total_files         | int    | Number of CSVs in ZIP
processed_files     | int    | CSVs completed (success or failed)
created_by          | bigint | FK → users
started_at          | timestamp
completed_at        | timestamp
error_summary       | text   | Summary of errors across all files
zip_hash            | string | SHA-256 of original ZIP
manifest_hash       | string | SHA-256 of manifest.json
signature           | text   | Digital signature (base64)
```

#### bulk_import_files
```sql
id                  | bigint | PK
bulk_import_id      | bigint | FK → bulk_imports
subject_id          | bigint | FK → subjects
subject_code        | string | e.g., "PHY"
filename            | string | e.g., "PHY_2026_S0325.csv"
status              | enum   | pending, processing, success, failed
rows_total          | int    | Total rows in CSV
rows_success        | int    | Successfully imported
rows_failed         | int    | Failed rows
error_log           | longtext | JSON-formatted errors
file_hash           | string | SHA-256 of CSV file
started_at          | timestamp
completed_at        | timestamp
```

### Service Layer

#### BulkImportOrchestrator
**Purpose:** Coordinates ZIP processing workflow

**Key Methods:**
- `startImport(zipPath, schoolId, examYearId)` - Initiates import
- `extractAndValidateManifest(zipPath)` - Parses and verifies manifest
- `registerFilesAndDispatchJobs(...)` - Creates jobs for each CSV
- `getProgress(bulkImportId)` - Real-time progress data
- `markFileComplete(importFileId)` - Updates parent import status
- `cleanup(bulkImportId)` - Removes temporary files

**Data Flow:**
```
ZIP Upload
    ↓
Validate ZIP Structure & Manifest
    ↓
Create BulkImport record
    ↓
Extract ZIP to temp storage
    ↓
Register BulkImportFile records
    ↓
Dispatch ProcessBulkImportFile Jobs (one per subject)
    ↓
Track progress via bulk_import.processed_files
    ↓
Cleanup temp files on completion
```

#### ZipSignerService
**Purpose:** Cryptographic signing and verification

**Algorithms:**
- HMAC-SHA256 using Laravel APP_KEY
- Constant-time comparison for security

**Key Methods:**
- `signManifest(manifest)` - Generate HMAC-SHA256 signature
- `verifyManifest(manifest, signature)` - Validate signature
- `hashFile(filePath)` - Compute SHA-256 of file
- `addSignatureToManifest(manifest)` - Embed signature in manifest
- `verifyManifestSignature(manifest)` - Verify embedded signature

**Signature Structure:**
```json
"signature": {
  "algorithm": "HMAC-SHA256",
  "value": "base64_encoded_signature",
  "signed_at": "2026-01-15T10:20:00Z",
  "signed_by": 12
}
```

#### ZipPreviewService
**Purpose:** Analyze ZIP before import

**Key Methods:**
- `preview(zipPath)` - Get detailed preview with issues
- `validate(zipPath)` - Validate ZIP structure
- `countCsvRows(zip, filename)` - Count rows efficiently

**Preview Response:**
```json
{
  "school": "IRINGA GIRLS",
  "exam_year": 2026,
  "subjects": [
    {
      "filename": "PHY_2026_S0325.csv",
      "subject_code": "PHY",
      "subject_name": "Physics",
      "candidates": 312
    }
  ],
  "total_files": 9,
  "total_candidates": 2843,
  "is_signed": true,
  "issues": [],
  "is_valid": true
}
```

### Jobs

#### ProcessBulkImportFile (Laravel Job)
**Purpose:** Import a single CSV file asynchronously

**Features:**
- Chunked row processing (500 rows/chunk)
- Memory-efficient streaming
- Row-level error logging
- Automatic retry (max 3 attempts)
- Timeout: 5 minutes

**Processing Pipeline:**
```
1. Mark file as 'processing'
2. Read CSV in chunks
3. For each row:
   - Validate format
   - Find candidate by index_number
   - Verify registration for exam year
   - Store marks
   - Log errors
4. Update row counts
5. Mark file as 'success' or 'failed'
6. Update parent bulk_import status
7. Cleanup temp file
```

### Controllers

#### BulkImportController
**Endpoints:**

**POST /api/bulk-import/preview**
- Upload ZIP and get preview
- Validates structure before import
- Response includes subject list, candidate counts, issues

**POST /api/bulk-import/start**
- Begin import process
- Creates bulk_import record
- Dispatches ProcessBulkImportFile jobs
- Returns bulk_import_id for tracking

**GET /api/bulk-import/{id}/progress**
- Real-time progress tracking
- Updated as jobs complete
- Includes file-level status

**GET /api/bulk-import/{id}**
- Detailed import results
- File-level statistics
- Error logs (first 10 per file)

### Artisan Commands

#### php artisan irms:stress-test-import
**Purpose:** Test system with large datasets

**Options:**
```bash
php artisan irms:stress-test-import 5000 --school-id=1 --exam-year-id=1
```

**Steps:**
1. Generate N fake candidates with random combinations
2. Create bulk CSV export
3. Import CSV
4. Measure execution time, memory, throughput

**Output:**
```
📊 Performance Report
====================
Total Candidates: 5000
Execution Time: 28.45 seconds
Peak Memory: 145.23 MB
Throughput: 175 candidates/sec
```

## ZIP Format

### Export Format
```
IRMS_ACSEE_2026_S0325.zip
├── PHY_2026_S0325.csv
├── CHE_2026_S0325.csv
├── MAT_2026_S0325.csv
└── manifest.json
```

### Manifest.json Structure
```json
{
  "system": "IRMS",
  "exam_type": "ACSEE",
  "exam_year": "2026",
  "school_code": "S0325",
  "school_name": "EXAMPLE SECONDARY SCHOOL",
  "generated_at": "2026-01-15T10:42:00Z",
  "generated_by": 12,
  "files": {
    "PHY_2026_S0325.csv": {
      "subject_code": "PHY",
      "subject_name": "Physics",
      "checksum": "sha256:abcd1234..."
    }
  },
  "signature": {
    "algorithm": "HMAC-SHA256",
    "value": "base64_encoded_signature",
    "signed_at": "2026-01-15T10:42:00Z",
    "signed_by": 12
  }
}
```

## CSV Format (Import)
```csv
index_number,sex,papers,paper_1,paper_2,paper_3
2024001,M,2,75,82,
2024002,F,2,68,71,
2024003,M,2,85,90,
```

**Validation Rules:**
- index_number: Must match existing candidate
- sex: Not validated on import
- papers: Not validated on import
- paper_1, paper_2, paper_3: Numeric 0-100 or empty

**Error Handling:**
- Candidate not found → Skip row, log error
- Not registered for year → Skip row, log error
- Invalid mark value → Store as NULL, count as success
- Missing column → Skip row, log error

## Security Features

### Signature Verification
```php
// On import, manifest signature is verified before processing
$isValid = $signerService->verifyManifestSignature($manifest);

if (!$isValid) {
    throw new Exception("ZIP signature verification failed");
}
```

**Prevents:**
- File tampering (hash mismatch)
- Manifest modification
- Replay attacks (timestamp + user ID)

### Audit Logging
```
Bulk Import Started:
- bulk_import_id
- school_id, exam_year_id
- total_files
- zip_hash
- user_id, IP address

ZIP Signature Event:
- action: sign | verify
- result: success | failed
- user_id, timestamp, IP
```

## Performance Optimizations

### Memory Management
- CSV streaming (fputcsv/fgetcsv)
- Chunked processing (500 rows)
- Garbage collection per chunk
- No full dataset in memory

### Database
- Batch inserts (transactions per chunk)
- Indexed queries (school_id, exam_year_id)
- Eager loading of relations
- N+1 prevention via scoping

### Concurrency
- Jobs queued (can be executed asynchronously)
- Multiple CSV files processed in parallel
- Progress tracked in database (no memory state)
- Resumable after failure

## Testing

### Unit Tests
```bash
php artisan test --filter BulkImportOrchestratorTest
php artisan test --filter ZipSignerServiceTest
php artisan test --filter ZipPreviewServiceTest
```

### Stress Test
```bash
# Generate 1,000 candidates and test import
php artisan irms:stress-test-import 1000 --school-id=1

# Generate 5,000 candidates (full stress)
php artisan irms:stress-test-import 5000 --school-id=1
```

### Manual Testing Checklist
```
□ Upload valid ZIP
□ Preview shows correct subjects
□ Preview shows correct candidate counts
□ Preview shows no issues
□ Import starts successfully
□ Progress updates in real-time
□ All files process successfully
□ Error log captures failed rows
□ Signature verification works
□ Tampered ZIP is rejected
□ Authorization enforced (403 on invalid user)
□ Cleanup removes temp files
□ Large import (1000+) completes in < 30s
□ Memory usage stays under 256 MB
```

## Error Handling

### Validation Errors
```json
{"success": false, "errors": ["manifest.json missing required fields"]}
```

### Authorization Errors
```json
{"success": false, "message": "You do not have permission to import for this school."}
```

### Import Errors
```json
{
  "success": true,
  "bulk_import": {
    "status": "completed",
    "files": [{
      "subject_code": "PHY",
      "status": "failed",
      "rows_total": 312,
      "rows_success": 310,
      "rows_failed": 2,
      "errors": [
        {"row": 45, "index_number": "2024045", "reason": "Candidate not found"}
      ]
    }]
  }
}
```

## Configuration

### Required Settings
```php
// config/app.php
'key' => env('APP_KEY'), // Used for HMAC-SHA256 signing

// config/queue.php
'default' => env('QUEUE_CONNECTION', 'sync'), // For testing (sync) or redis (production)
```

### Recommended Database Indexes
```sql
CREATE INDEX idx_bulk_import_school_exam 
  ON bulk_imports(school_id, exam_year_id);

CREATE INDEX idx_bulk_import_status 
  ON bulk_imports(status);

CREATE INDEX idx_bulk_import_file_status 
  ON bulk_import_files(bulk_import_id, status);
```

## Deployment Steps

1. **Run migrations:**
   ```bash
   php artisan migrate
   ```

2. **Publish queue configuration:**
   ```bash
   php artisan queue:publish
   ```

3. **Create temp directory:**
   ```bash
   mkdir -p storage/app/temp/imports
   chmod 755 storage/app/temp/imports
   ```

4. **Configure queue driver** (config/queue.php):
   - Development: `sync` (synchronous)
   - Production: `redis` (asynchronous)

5. **Run queue worker** (production only):
   ```bash
   php artisan queue:work
   ```

6. **Verify migrations:**
   ```bash
   php artisan migrate:status
   ```

## Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| "ZIP file not readable" | Permission issue | Check file permissions on uploaded ZIP |
| "manifest.json not found" | ZIP corrupted or invalid | Download fresh ZIP from system |
| "Signature verification failed" | ZIP tampered | Reject and request new export |
| Import hangs | Queue not processing | Run `php artisan queue:work` |
| Memory exceeded on 1000+ candidates | Chunk size too large | Reduce CHUNK_SIZE in ProcessBulkImportFile |
| Duplicate subject errors | ZIP malformed | Check ZIP was generated by system |

## Future Enhancements

1. **Resumable Imports**
   - Store progress checkpoints
   - Resume from failure point

2. **Compression**
   - GZIP CSV files within ZIP
   - Reduce bandwidth

3. **Webhook Notifications**
   - POST to external service on completion
   - Email summaries

4. **Revalidation**
   - Auto-revalidate after X days
   - Audit compliance

5. **Bulk Update**
   - Modify existing marks
   - Append-only mode

## References

- Laravel Queues: https://laravel.com/docs/queues
- Laravel Jobs: https://laravel.com/docs/jobs
- PHP HMAC-SHA256: https://www.php.net/manual/en/function.hash-hmac.php
- ZipArchive: https://www.php.net/manual/en/class.ziparchive.php
