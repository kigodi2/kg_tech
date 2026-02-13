# District Bulk Import Technical Reference

## Code Architecture

### Service Dependencies
```
BulkImportController
├── BulkImportOrchestrator (school imports, unchanged)
├── DistrictBulkImportOrchestrator (new, district imports)
│   ├── ZipSignerService (signature verification)
│   └── DistrictManifestValidator (schema validation)
├── ZipPreviewService (ZIP structure analysis)
└── BulkImportPolicy (authorization)
```

### Data Flow Architecture
```
Upload ZIP
    ↓
BulkImportController::preview()
    ↓
[Validation only, no DB]
    ↓
Store ZIP in session
    ↓
User confirms preview
    ↓
BulkImportController::startDistrictImport()
    ↓
DistrictBulkImportOrchestrator::startImport()
    ├─ Extract manifest
    ├─ Validate manifest
    ├─ Create BulkImport (status=validating)
    ├─ Extract ZIP contents
    └─ Register schools + dispatch jobs
        ↓
    For each school:
        ├─ Create BulkImportSchool entry
        └─ Dispatch ProcessBulkImportSchool job
            ↓
        ProcessBulkImportSchool
        ├─ Update school status=processing
        ├─ For each subject:
        │   ├─ Find CSV
        │   ├─ Create BulkImportFile
        │   └─ Dispatch ProcessBulkImportFile
        ├─ Track results
        └─ Call markSchoolComplete()
            ↓
        Update BulkImportSchool status
        Update BulkImport processed_schools
```

## Class Reference

### DistrictBulkImportOrchestrator

**Location**: `app/Services/MarkImport/DistrictBulkImportOrchestrator.php`

**Constructor Dependencies**:
- `ZipSignerService` - Hash/signature verification
- `DistrictManifestValidator` - Schema validation

**Public Methods**:

#### `startImport(string $zipPath, int $districtId, int $examYearId): BulkImport`
Start district import process.

```php
$orchestrator = app(DistrictBulkImportOrchestrator::class);
$bulkImport = $orchestrator->startImport(
    '/path/to/zip',
    5,  // district_id
    12  // exam_year_id
);
// Returns: BulkImport with scope_type='district'
```

**Throws**:
- `Exception` - If ZIP not readable
- `Exception` - If manifest not found
- `Exception` - If signature invalid
- `ValidationException` - If manifest invalid

**Return**: `BulkImport` object with status='importing'

---

#### `getProgress(int $bulkImportId): array`
Get detailed progress for district import.

```php
$progress = $orchestrator->getProgress(42);
// Returns: [
//   'id' => 42,
//   'district' => 'Iringa Municipal',
//   'exam_year' => '2025',
//   'status' => 'importing',
//   'progress_percentage' => 45,
//   'total_schools' => 3,
//   'processed_schools' => 1,
//   'total_files' => 9,
//   'processed_files' => 3,
//   'schools' => [...],
//   'summary' => [...]
// ]
```

---

#### `markSchoolComplete(int $bulkImportId, int $schoolId, string $status, array $stats): void`
Mark school as completed with status.

```php
$orchestrator->markSchoolComplete(
    42,           // bulk_import_id
    5,            // school_id
    'success',    // status: success|partial|failed
    [
        'processed_subjects' => 3,
        'successful_candidates' => 2140,
        'failed_candidates' => 0,
        'error_summary' => null
    ]
);
```

**Parameters**:
- `status`: One of 'success', 'partial', 'failed'
- `stats`: Array with keys:
  - `processed_subjects` (int)
  - `successful_candidates` (int)
  - `failed_candidates` (int)
  - `error_summary` (string|null)

---

#### `cleanup(int $bulkImportId): void`
Remove temporary extracted files.

```php
$orchestrator->cleanup(42);
// Removes: storage/app/temp/imports/42/
```

---

### DistrictManifestValidator

**Location**: `app/Services/MarkImport/DistrictManifestValidator.php`

**Public Methods**:

#### `validate(array $manifest, District $district, ExamYear $examYear): array`
Validate manifest schema and content.

```php
$validator = app(DistrictManifestValidator::class);
$result = $validator->validate($manifest, $district, $examYear);
// Returns: [
//   'valid' => true|false,
//   'errors' => [
//     'field.path' => 'Error message',
//     ...
//   ]
// ]
```

**Return Format**:
```php
[
    'valid' => true,
    'errors' => []  // Empty if valid
]

// Or on error:
[
    'valid' => false,
    'errors' => [
        'exam_year' => 'Manifest exam_year does not match...',
        'scope.code' => 'Manifest scope code does not match...',
        'schools.0.school_code' => 'School not found in district...',
        'schools.0.subjects.0.checksum' => 'Invalid checksum format'
    ]
]
```

---

### ProcessBulkImportSchool Job

**Location**: `app/Jobs/ProcessBulkImportSchool.php`

**Queue Configuration**:
- `timeout`: 3600 seconds (1 hour)
- `tries`: 3 retry attempts
- `queue`: default queue

**Constructor**:
```php
new ProcessBulkImportSchool(
    $bulkImportId,    // int
    $schoolId,        // int
    $subjects,        // array of subject data from manifest
    $extractPath      // string path to extracted ZIP
)
```

**Execution Flow**:
1. Load BulkImport and School
2. Mark school as processing
3. For each subject:
   - Find CSV file
   - Create BulkImportFile
   - Dispatch ProcessBulkImportFile
   - Track result
4. Determine status (success|partial|failed)
5. Call markSchoolComplete()
6. Log completion

**Exception Handling**:
- Catches all exceptions
- Logs error details
- Marks school as failed
- Notifies parent import
- Re-throws for retry queue

---

## Database Schema Details

### bulk_imports Table

**New/Modified Columns**:
```sql
-- New columns for district support
scope_type ENUM('school', 'district') DEFAULT 'school'
scope_id BIGINT UNSIGNED NULLABLE
district_id BIGINT UNSIGNED NULLABLE
total_schools INT DEFAULT 0
processed_schools INT DEFAULT 0

-- Made nullable for district imports
school_id BIGINT UNSIGNED NULLABLE

-- Existing columns (unchanged)
exam_year_id BIGINT UNSIGNED NOT NULL
status ENUM('pending', 'processing', 'completed', 'failed')
total_files INT DEFAULT 0
processed_files INT DEFAULT 0
created_by BIGINT UNSIGNED NULLABLE
started_at TIMESTAMP NULLABLE
completed_at TIMESTAMP NULLABLE
error_summary TEXT NULLABLE
zip_hash VARCHAR(255) NULLABLE
manifest_hash VARCHAR(255) NULLABLE
signature TEXT NULLABLE
```

**Indexes**:
```sql
INDEX idx_scope (scope_type, scope_id)
INDEX idx_district (district_id, exam_year_id)
INDEX idx_status (status)
INDEX idx_school (school_id, exam_year_id) -- existing
FOREIGN KEY (district_id) → districts(id)
```

---

### bulk_import_schools Table

**Columns**:
```sql
id BIGINT PRIMARY KEY AUTO_INCREMENT
bulk_import_id BIGINT UNSIGNED NOT NULL  -- FK
school_id BIGINT UNSIGNED NOT NULL       -- FK
school_code VARCHAR(50) NOT NULL         -- Audit trail
school_name VARCHAR(255) NOT NULL        -- Audit trail
status ENUM('pending', 'processing', 'success', 'partial', 'failed')
total_subjects INT DEFAULT 0
processed_subjects INT DEFAULT 0
total_candidates INT DEFAULT 0
successful_candidates INT DEFAULT 0
failed_candidates INT DEFAULT 0
error_summary TEXT NULLABLE
started_at TIMESTAMP NULLABLE
completed_at TIMESTAMP NULLABLE
created_at TIMESTAMP
updated_at TIMESTAMP
```

**Indexes**:
```sql
UNIQUE KEY (bulk_import_id, school_id)
INDEX (school_id, status)
INDEX (status)
FOREIGN KEY (bulk_import_id) → bulk_imports(id) ON DELETE CASCADE
FOREIGN KEY (school_id) → schools(id) ON DELETE CASCADE
```

---

## Manifest.json Specification

### Root Schema
```json
{
  "exam": "string, required",           // e.g., "ACSEE"
  "exam_year": "integer, required",     // e.g., 2025
  "scope": "object, required",
  "generated_at": "ISO8601, required",  // e.g., "2025-03-15T10:45:00Z"
  "generated_by": "object, required",
  "schools": "array, required",
  "zip_checksum": "string, optional"
}
```

### scope Object
```json
{
  "type": "string, required",    // Must be "district"
  "code": "string, required"     // e.g., "IRINGA_M"
}
```

### generated_by Object
```json
{
  "user_id": "integer, required",
  "role": "string, required"     // district_officer|regional_officer|admin
}
```

### schools Array Items
```json
{
  "school_code": "string, required",      // e.g., "S0203"
  "school_name": "string, required",      // e.g., "IRINGA GIRLS"
  "total_candidates": "integer, optional", // e.g., 2140
  "subjects": [
    {
      "code": "string, required",         // e.g., "PHY"
      "papers": ["array", "required"],    // e.g., ["P1", "P2"]
      "candidates": "integer, optional",  // e.g., 350
      "checksum": "string, required"      // "sha256:abc123..."
    }
  ]
}
```

### Validation Rules

| Field | Rule | Example |
|-------|------|---------|
| `exam` | String | "ACSEE" |
| `exam_year` | 4-digit integer | 2025 |
| `scope.type` | Must be "district" | "district" |
| `scope.code` | Match district code exactly | "IRINGA_M" |
| `generated_at` | ISO 8601 format | "2025-03-15T10:45:00Z" |
| `generated_by.role` | district_officer\|regional_officer\|admin | "district_officer" |
| `schools` | At least 1 school | [...] |
| `schools[].school_code` | Must exist in district | "S0203" |
| `schools[].subjects` | At least 1 subject | [...] |
| `subjects[].code` | Must exist in database | "PHY" |
| `subjects[].checksum` | Format: sha256:... | "sha256:abc123..." |

---

## API Reference

### POST /api/bulk-import/preview

**Request**:
```
Content-Type: multipart/form-data
Body:
  zip_file: <binary ZIP file>
```

**Response (200 OK)**:
```json
{
  "success": true,
  "preview": {
    "valid": true,
    "exam": "ACSEE",
    "exam_year": 2025,
    "scope_type": "district",
    "scope_code": "IRINGA_M",
    "total_schools": 2,
    "total_subjects": 6,
    "total_candidates": 4200,
    "schools": [
      {
        "code": "S0203",
        "name": "IRINGA GIRLS",
        "subjects": ["PHY", "MAT", "ENG"],
        "candidates": 2140
      }
    ],
    "warnings": []
  }
}
```

**Response (422 Unprocessable Entity)**:
```json
{
  "success": false,
  "errors": {
    "manifest.json": "Required file missing",
    "scope.type": "Must be 'district', got 'school'"
  }
}
```

---

### POST /api/bulk-import/district/start

**Request**:
```json
{
  "district_id": 5,
  "exam_year_id": 12
}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "bulk_import_id": 42,
  "message": "District-level bulk import started"
}
```

**Response (403 Forbidden)**:
```json
{
  "success": false,
  "message": "You do not have permission to import for this district."
}
```

**Response (422 Unprocessable Entity)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "scope.code": "Manifest scope code (MBEYA_C) does not match district code (IRINGA_M)"
  }
}
```

---

### GET /api/bulk-import/{id}/progress

**Response (200 OK)**:
```json
{
  "success": true,
  "progress": {
    "id": 42,
    "district": "Iringa Municipal",
    "exam_year": "2025",
    "status": "importing",
    "progress_percentage": 45,
    "total_schools": 3,
    "processed_schools": 1,
    "total_files": 9,
    "processed_files": 3,
    "schools": [
      {
        "school_id": 5,
        "school_code": "S0203",
        "school_name": "IRINGA GIRLS",
        "status": "success",
        "total_subjects": 3,
        "processed_subjects": 3,
        "total_candidates": 2140,
        "successful_candidates": 2140,
        "failed_candidates": 0,
        "started_at": "2025-03-15T10:45:00Z",
        "completed_at": "2025-03-15T10:50:00Z"
      }
    ],
    "summary": {
      "total_schools": 3,
      "processed_schools": 1,
      "successful_schools": 1,
      "partial_schools": 0,
      "failed_schools": 0,
      "total_candidates": 5200,
      "successful_candidates": 2140,
      "failed_candidates": 0,
      "progress_percentage": 45
    }
  }
}
```

---

### GET /api/bulk-import/{id}

**Response (200 OK)**:
```json
{
  "success": true,
  "bulk_import": {
    "id": 42,
    "district": "Iringa Municipal",
    "exam_year": "2025",
    "scope_type": "district",
    "status": "completed",
    "started_at": "2025-03-15T10:45:00Z",
    "completed_at": "2025-03-15T11:30:00Z",
    "schools": [
      {
        "school_id": 5,
        "school_code": "S0203",
        "school_name": "IRINGA GIRLS",
        "status": "success",
        "total_subjects": 3,
        "processed_subjects": 3,
        "total_candidates": 2140,
        "successful_candidates": 2140,
        "failed_candidates": 0,
        "error_summary": null
      }
    ],
    "summary": {
      "total_schools": 3,
      "processed_schools": 3,
      "successful_schools": 3,
      "partial_schools": 0,
      "failed_schools": 0,
      "total_candidates": 5200,
      "successful_candidates": 5200,
      "failed_candidates": 0
    }
  }
}
```

---

## Authorization Policy Reference

### BulkImportPolicy Methods

#### `view(User $user, BulkImport $bulkImport): bool`
```php
// Determines who can view import details and progress
// School officers can view own school imports
// District officers can view own district imports
// Regional officers can view region imports
// Admins can view all
```

#### `uploadSchoolCsv(User $user, int $schoolId): bool`
```php
// School-level import permission
// School officer: own school only
// Regional officer: schools in region
// Admin: any school
// District officer: NOT ALLOWED
```

#### `uploadDistrictCsv(User $user, int $districtId): bool`
```php
// District-level import permission
// District officer: own district only
// Regional officer: districts in region
// Admin: any district
// School officer: NOT ALLOWED
```

---

## Logging and Audit Trail

### Audit Log Entry (on import start)
```php
Log::channel('audit')->info('District Bulk Import Started', [
    'bulk_import_id' => 42,
    'district_id' => 5,
    'district_code' => 'IRINGA_M',
    'exam_year_id' => 12,
    'total_schools' => 3,
    'total_files' => 9,
    'zip_hash' => 'sha256:...',
    'user_id' => 1,
    'timestamp' => '2025-03-15T10:45:00Z',
    'ip_address' => '192.168.1.1'
]);
```

### Expected Log Locations
```
storage/logs/audit.log          # All audit events
storage/logs/laravel.log        # Application logs
storage/logs/queue.log          # Queue processing
```

---

## Error Handling

### Validation Errors
Thrown as `ValidationException` with `errors()` method:
```php
try {
    $orchestrator->startImport($zipPath, $districtId, $examYearId);
} catch (ValidationException $e) {
    foreach ($e->errors() as $field => $message) {
        echo "{$field}: {$message}";
    }
}
```

### Runtime Errors
Caught in job handler, logged, and job retried:
```php
// In ProcessBulkImportSchool
try {
    $result = $this->processSubject($subjectCode, $subjectData);
} catch (Exception $e) {
    Log::error('Subject processing failed', [
        'subject_code' => $subjectCode,
        'error' => $e->getMessage()
    ]);
    $subjectErrors[] = "{$subjectCode}: {$e->getMessage()}";
}
```

---

## Performance Tuning

### Queue Configuration
```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,      // seconds
    ],
],

// For high-volume imports:
// Run multiple queue workers
php artisan queue:work --queue=default --count=4
```

### Database Optimization
```php
// Ensure indexes exist
Schema::table('bulk_imports', function (Blueprint $table) {
    $table->index(['scope_type', 'scope_id']);
    $table->index(['district_id', 'exam_year_id']);
});

Schema::table('bulk_import_schools', function (Blueprint $table) {
    $table->index(['school_id', 'status']);
});
```

### CSV Processing
```php
// In ProcessBulkImportFile (existing)
// Chunk size: 300-500 rows
$chunkSize = 400;
foreach ($csvData as $chunk) {
    // Insert $chunkSize rows in single transaction
}
```

---

## Testing Matrix

| Scenario | Test Case | Expected Result |
|----------|-----------|-----------------|
| Valid ZIP | Upload district ZIP with 2 schools | status=completed |
| Invalid manifest | Missing required field | Validation error |
| Wrong exam year | ZIP for 2024, import for 2025 | Validation error |
| Wrong scope | ZIP with scope.type='school' | Validation error |
| School not in district | CSV for S0203 from wrong district | Validation error |
| One school fails | Process 2 schools, 1 fails | status=partial |
| One subject fails | Process 3 subjects, 1 fails | School status=partial |
| All fail | Severe CSV errors throughout | status=failed |
| Unauthorized user | School officer uploads district | 403 Forbidden |
| Partial completion | Monitor in real-time | progress_percentage increases |
| Cleanup | Import completes | Temp files removed |

