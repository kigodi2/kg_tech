# Bulk CSV Export Feature Implementation

## Overview

The **Bulk CSV Export** feature allows educators to download all subject-wise CSV files for a school + exam year as a single ZIP archive. This is a convenience layer for mark entry that maintains data integrity through checksums, role-based access control, and comprehensive audit logging.

## Architecture

### Core Components

1. **BulkCsvExportService** (`app/Services/MarkImport/BulkCsvExportService.php`)
   - Orchestrates bulk export generation
   - Implements chunked queries for memory efficiency
   - Generates per-subject CSVs with streaming
   - Creates ZIP with manifest and checksums

2. **BulkCsvExportPolicy** (`app/Policies/BulkCsvExportPolicy.php`)
   - Role-based authorization
   - Access matrix: School User → own school, Regional Officer → region schools, Admin → any school

3. **MarkEntryController** endpoint
   - `GET /mark-entry/acsee/bulk-csv-download`
   - Validates inputs, enforces authorization, triggers export

4. **UI Integration** (Mark Entry Page)
   - "Download All Subjects (ZIP)" button
   - Disabled if no subjects or locked year
   - Loading indicator with async download

## ZIP File Structure

```
IRMS_ACSEE_2026_S0325.zip
│
├── PHY_2026_S0325.csv        (Physics)
├── CHE_2026_S0325.csv        (Chemistry)
├── MAT_2026_S0325.csv        (Mathematics)
├── HIS_2026_S0325.csv        (History)
└── manifest.json             (Integrity & audit metadata)
```

### CSV Format (Per Subject)

**Columns (strict order):**
```
index_number,sex,papers,paper_1,paper_2,paper_3
```

**Rules:**
- ✅ Only candidates registered for that subject
- ✅ Pre-filled papers count (from subject.written_papers)
- ✅ Empty mark cells (left blank for user input)
- ❌ NO full names (privacy/security)
- ❌ NO hidden metadata rows (compatibility)

**Example:**
```csv
index_number,sex,papers,paper_1,paper_2,paper_3
2024001,M,2,,
2024002,F,2,,
2024003,M,2,,
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
  "files": [
    {
      "filename": "PHY_2026_S0325.csv",
      "subject_code": "PHY",
      "subject_name": "Physics",
      "checksum": "sha256:abcd1234...",
      "candidate_count": 156
    },
    {
      "filename": "CHE_2026_S0325.csv",
      "subject_code": "CHE",
      "subject_name": "Chemistry",
      "checksum": "sha256:efgh5678...",
      "candidate_count": 160
    }
  ]
}
```

## Authorization Rules (STRICT)

### Access Matrix

| Role              | Can Download |
|-------------------|--------------|
| Admin             | Any school   |
| Regional Officer  | Schools in their region |
| School User       | Own school only |

### Policy Implementation

```php
// BulkCsvExportPolicy::downloadBulkCsv()

if ($user->isAdmin()) return true;

if ($user->isRegionalOfficer() && $user->region_id) {
    return $school->district?->region_id == $user->region_id;
}

if ($user->isSchoolUser() && $user->school_id) {
    return $user->school_id == $schoolId;
}

return false;
```

**Enforcement:**
- ✅ Policy-based authorization via Laravel Gates/Policies
- ✅ Server-side validation (NOT frontend only)
- ✅ HTTP 403 Forbidden on violations
- ✅ Audit logging of all attempts

## Performance Optimization

### Design for Scale (1000+ candidates, 10–15 subjects)

#### 1. Chunked Database Queries
```php
$query->chunk(500, function ($registrations) {
    // Process 500 records at a time
});
```

#### 2. Streaming CSV Generation
- No full dataset loaded into memory
- Rows streamed via `fputcsv()` to temporary file
- Temporary files cleaned up after ZIP creation

#### 3. Per-Subject Processing
- Each subject CSV generated independently
- Allows for incremental ZIP writing
- Prevents memory bloat with large numbers of subjects

#### 4. Eager Loading
```php
->with('candidate:id,candidate_id,gender')
```
Selects only required columns to minimize memory footprint.

#### 5. ZIP Incremental Write
- `ZipArchive::CREATE` flag allows streaming
- Files added incrementally via `addFile()`
- No in-memory ZIP construction

### Query Optimization

**N+1 Prevention:**
```php
CandidateExamRegistration::query()
    ->with('candidate:id,candidate_id,gender')  // Eager load
    ->whereHas('candidate.combination.subjects')  // Filter via relation
    ->chunk(500, ...)  // Process in batches
```

**Index Recommendations:**
```sql
-- Ensure these indexes exist:
CREATE INDEX idx_candidate_exam_reg_year_type 
    ON candidate_exam_registrations(exam_year_id, exam_type_id);

CREATE INDEX idx_candidate_school 
    ON candidates(school_id);

CREATE INDEX idx_combination_subject 
    ON combination_subject(combination_id, subject_id);
```

## Checksum Strategy (Integrity)

### Generation
```php
$checksum = hash_file('sha256', $csvPath);
// Output: "sha256:abcd1234..."
```

### Verification
When importing a ZIP from bulk export:
1. Extract manifest.json
2. For each CSV file:
   - Compute SHA-256
   - Compare against manifest checksum
   - Reject if mismatch (tampered/corrupted)
3. Log verification result for audit

### Use Cases
- Detect file corruption during transfer
- Identify tampering
- Audit trail: who downloaded when

## Audit & Logging

### Log Entry Format
```
Action: bulk_csv_export
User ID: 12
Role: school_user | regional_officer | admin
School ID: 34
Exam Year ID: 1
Number of Subjects: 12
Number of Candidates: 523
ZIP Filename: IRMS_ACSEE_2026_S0325.zip
Timestamp: 2026-01-15T10:42:00Z
IP Address: 192.168.1.100
```

### Log Channel
```php
Log::channel('audit')->info('Bulk CSV Export', [...]);
```

Logged to: `storage/logs/audit.log`

## Implementation Files

### Service Layer
- **BulkCsvExportService.php** - Core export logic
  - `generateBulkExport()` - Main orchestration
  - `generateSubjectCsv()` - Per-subject CSV
  - `getSubjectsWithCandidates()` - Subject discovery
  - `computeChecksum()` - SHA-256 generation
  - `logExport()` - Audit trail

### Authorization
- **BulkCsvExportPolicy.php** - Policy rules
  - `downloadBulkCsv()` - Access control

### Controller
- **MarkEntryController.php** - Endpoint
  - `downloadBulkCsvExport()` - HTTP handler
    - Input validation
    - Authorization enforcement
    - Service invocation
    - ZIP download response

### Routes
- **routes/web.php**
  - `GET /mark-entry/acsee/bulk-csv-download` - Download endpoint

### UI
- **resources/views/mark-entry/index.blade.php**
  - Button: "Download All Subjects (ZIP)"
  - Alpine.js: `downloadBulkCsv()` method
  - Loading state: `bulkCsvLoading`
  - Disabled state logic

## Usage Workflow

### 1. User Opens Mark Entry Page
```
User navigates to: /mark-entry/acsee
```

### 2. Selects Context
```
Year: 2026
Region: IRINGA
District: IRINGA MC
School: S0203 - IRINGA GIRLS' SECONDARY SCHOOL
```

### 3. System Shows Available Subjects
```
Based on candidates' combinations:
- GENERAL STUDIES (111) - 295 candidates
- HISTORY (112) - 280 candidates
- GEOGRAPHY (113) - 275 candidates
- ... (12 total subjects)
```

### 4. User Clicks "Download All Subjects (ZIP)"
```
Button is enabled if:
✅ School selected
✅ Year selected
✅ At least 1 subject found
✅ Year NOT locked
```

### 5. System Generates Export
```
Process:
1. Validate school access (policy check)
2. Verify year not locked
3. Get subjects with candidates
4. For each subject:
   a. Generate CSV with registrations
   b. Compute SHA-256 checksum
   c. Add to ZIP
5. Create manifest.json with checksums
6. Add manifest to ZIP
7. Stream ZIP to browser
8. Log action to audit trail
```

### 6. Browser Downloads ZIP
```
Filename: IRMS_ACSEE_2026_S0203.zip
Content:
  - PHY_2026_S0203.csv (Physics - 156 candidates)
  - CHE_2026_S0203.csv (Chemistry - 160 candidates)
  - ... (more subjects)
  - manifest.json (integrity metadata)
```

### 7. User Can Verify Integrity
```
Open manifest.json:
- Check SHA-256 checksums match
- Verify school code, exam year
- Review generation timestamp
- Confirm number of candidates per subject
```

## Error Handling

### Validation Errors
```
Input validation fails → HTTP 422 Unprocessable Entity
{
  "success": false,
  "message": "Validation error..."
}
```

### Authorization Errors
```
User lacks permission → HTTP 403 Forbidden
{
  "success": false,
  "message": "You do not have permission to download this export."
}
```

### Business Logic Errors
```
No subjects found, year locked, etc. → HTTP 500 Server Error
{
  "success": false,
  "message": "No subjects with registered candidates found..."
}
```

## Testing

### Unit Tests
```bash
php artisan test --filter BulkCsvExportServiceTest
```

### Policy Tests
```bash
php artisan test --filter BulkCsvExportPolicyTest
```

### Integration Tests
```bash
php artisan test --filter BulkCsvExportControllerTest
```

### Manual Testing Checklist
```
□ School User downloads own school only
□ Regional Officer downloads region schools only
□ Admin downloads any school
□ Non-privileged user gets 403 error
□ No subjects found → appropriate error
□ Year locked → export prevented
□ Checksum in manifest is valid
□ All subjects appear in ZIP
□ CSV columns are correct order
□ Candidate counts match database
□ Audit log entry created
□ Large school (1000+ candidates) completes in <30s
```

## Constraints & Guarantees

### ✅ Enforced
- Exam year isolation (exam_year_id FK)
- Role-based access control (server-side)
- No full names in CSV (privacy)
- SHA-256 checksums (integrity)
- Per-subject filtering (only registered candidates)
- Audit logging (all operations)
- No memory bloat (chunked processing)

### ❌ Prevented
- Merging subjects into one CSV (maintains import compatibility)
- Breaking existing subject-wise import (reuses SubjectFilterService)
- Bypassing exam year locks (validated before generation)
- Frontend-only authorization (enforced server-side)

## Performance Benchmarks (Expected)

| Dataset | Time | Memory |
|---------|------|--------|
| 100 candidates, 3 subjects | ~200ms | <10MB |
| 500 candidates, 10 subjects | ~800ms | <20MB |
| 1000+ candidates, 15 subjects | ~2s | <30MB |

## Future Enhancements

1. **Scheduled Downloads**
   - Queue bulk exports for large datasets
   - Email ZIP link to user

2. **Incremental Exports**
   - Export only modified records since last download
   - Reduces file size for repeated exports

3. **Format Options**
   - Excel (.xlsx) option
   - PDF summary report in ZIP

4. **Bulk Import Companion**
   - Import ZIP directly to update marks
   - Validate checksums during import
   - Atomic transaction per subject

5. **Compression Options**
   - GZIP compression for large ZIPs
   - Bandwidth optimization

## Troubleshooting

### ZIP Creation Fails
**Error:** `Failed to create ZIP file: {path}`

**Solutions:**
- Check `storage/app/temp/` directory exists and is writable
- Ensure disk space available
- Verify `/tmp` is writable (fallback for temporary files)

### Out of Memory on Large Schools
**Error:** `Allowed memory size of X bytes exhausted`

**Solutions:**
- Chunk size is 500; reduce to 250 if needed
- Ensure MySQL `max_allowed_packet` is large (>16MB)
- Run during off-peak hours for system resources

### No Subjects Found
**Error:** `No subjects with registered candidates found...`

**Solutions:**
- Verify candidates have combinations assigned
- Check exam_type matches ACSEE
- Ensure school has registered candidates for the year

## References

- Laravel Policies: https://laravel.com/docs/policies
- ZipArchive: https://www.php.net/manual/en/class.ziparchive.php
- Chunking: https://laravel.com/docs/queries#chunking-results
- Audit Logging: Standard Laravel logging with custom channel
