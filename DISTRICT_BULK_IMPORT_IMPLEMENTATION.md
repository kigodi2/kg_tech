# District Bulk CSV Import - Complete Implementation Guide

## Overview

This document details the complete district-level bulk CSV import system with scope isolation, failure recovery, and comprehensive audit trails.

## Architecture

### 1. Scope Architecture

The system supports two import scopes:

```
scope_type: 'school' | 'district'
scope_id: schools.id | districts.id
```

- **School Import**: Single school, single ZIP, multiple subjects
- **District Import**: One district, one ZIP, multiple schools, multiple subjects per school

### 2. Database Schema

#### bulk_imports table
```sql
- id (PK)
- school_id (FK, nullable for district imports)
- district_id (FK, nullable for school imports)
- exam_year_id (FK)
- scope_type (enum: school|district)
- scope_id (references either school_id or district_id)
- status (enum: validating|importing|partial|completed|failed)
- total_files (count of CSVs to import)
- processed_files (count of CSVs processed)
- total_schools (district imports only)
- processed_schools (district imports only)
- zip_hash (SHA-256 of ZIP file for audit)
- manifest_hash (SHA-256 of manifest.json)
- signature (HMAC-SHA256 signature)
- error_summary (text)
- created_by (FK to users)
- started_at, completed_at
```

#### bulk_import_schools table (pivot for district imports)
```sql
- id (PK)
- bulk_import_id (FK)
- school_id (FK)
- school_code, school_name (audit trail)
- status (enum: pending|processing|success|partial|failed)
- total_subjects, processed_subjects
- total_candidates, successful_candidates, failed_candidates
- error_summary
- started_at, completed_at
```

### 3. ZIP File Structure (STRICT)

```
DISTRICT_<DISTRICTCODE>_<YEAR>.zip
│
├── manifest.json
├── manifest.sig
│
├── <SCHOOL_CODE>_<SCHOOL_NAME>/
│   ├── PHY.csv
│   ├── ENG.csv
│   └── ...
│
├── <SCHOOL_CODE>_<SCHOOL_NAME>/
│   ├── PHY.csv
│   └── ...
│
└── logs/
    └── precheck_report.json (optional)
```

### 4. Manifest Schema (District)

```json
{
  "exam": "ACSEE",
  "exam_year": 2025,
  "scope": {
    "type": "district",
    "code": "IRINGA_M"
  },
  "generated_at": "2025-03-15T10:45:00Z",
  "generated_by": {
    "user_id": 14,
    "role": "district_officer"
  },
  "schools": [
    {
      "school_code": "S0203",
      "school_name": "IRINGA GIRLS",
      "candidates": 2140,
      "subjects": [
        {
          "code": "PHY",
          "papers": ["P1", "P2"],
          "candidates": 2140,
          "checksum": "sha256:abcd1234..."
        }
      ]
    }
  ],
  "zip_checksum": "sha256:globalhash...",
  "signature": {
    "algorithm": "HMAC-SHA256",
    "value": "base64encodedvalue",
    "signed_at": "2025-03-15T10:45:00Z",
    "signed_by": 14
  }
}
```

## Import Flow

### 1. Preflight (NO DB WRITES)

**Input**: ZIP file, district_id, exam_year_id

**Validation Steps**:
1. ZIP file is readable
2. manifest.json exists and is valid JSON
3. manifest structure matches schema
4. Exam year matches selected year
5. Scope is 'district'
6. Scope code matches district code
7. All schools belong to district
8. All subjects are valid
9. All checksums present and valid
10. Signature verification passes

**Output**: Validated manifest, no changes to DB

### 2. Execution

**Step 1: Register Import**
```php
BulkImport::create([
    'district_id' => $districtId,
    'exam_year_id' => $examYearId,
    'scope_type' => 'district',
    'scope_id' => $districtId,
    'status' => 'validating',
    'total_schools' => count($schools),
    'total_files' => sum of subject count,
    'zip_hash' => hash_file('sha256', $zipPath),
    'manifest_hash' => hash($manifest),
    'created_by' => auth()->id(),
]);
```

**Step 2: Register Schools**
```php
foreach ($manifest['schools'] as $school) {
    $bulkImport->schools()->attach($school->id, [
        'school_code' => $school['school_code'],
        'school_name' => $school['school_name'],
        'status' => 'pending',
        'total_subjects' => count($school['subjects']),
    ]);
}
```

**Step 3: Extract ZIP to Temp Directory**
```
/storage/app/temp/imports/{bulk_import_id}/
├── manifest.json
├── manifest.sig
├── S0203_IRINGA GIRLS/
│   ├── PHY.csv
│   └── ENG.csv
└── S0204_MBEYA HIGH/
    └── BIO.csv
```

**Step 4: Dispatch Per-School Jobs**
```php
foreach ($schools as $school) {
    ProcessBulkImportSchool::dispatch(
        $bulkImportId,
        $schoolId,
        $subjects,
        $extractPath
    );
}
```

**Step 5: Update Status**
```php
$bulkImport->update(['status' => 'importing']);
```

### 3. Per-School Processing

**Job**: `ProcessBulkImportSchool`

**Responsibilities**:
1. Mark school as 'processing'
2. For each subject:
   - Find CSV file in extracted directory
   - Create BulkImportFile record
   - Dispatch ProcessBulkImportFile job (synchronous)
   - Track success/failure
3. Determine school status:
   - success: all subjects imported
   - partial: some subjects imported
   - failed: no subjects imported
4. Call markSchoolComplete()

**Failure Isolation**: School failure does not affect other schools

### 4. Per-Subject CSV Processing

**Job**: `ProcessBulkImportFile`

**CSV Format**:
```
index_number,sex,papers,paper_1,paper_2,paper_3
S123ABC,M,P1;P2,45,52,
S124ABC,F,P1;P2;P3,48,61,38
```

**Processing**:
1. Read CSV in chunks (500-row chunks)
2. Per row:
   - Validate format
   - Find candidate by index_number
   - Find exam registration for this exam_year
   - Insert SubjectMarks record
   - Track row success/failure
3. Update BulkImportFile:
   - rows_total, rows_success, rows_failed
   - status: success|failed
   - error_log: JSON array of errors

**Atomicity**: Each subject is a separate transaction

### 5. Status Tracking

**Import Statuses**:
- `validating`: Preflight validation in progress
- `importing`: Schools are being processed
- `partial`: Some schools completed, others failed
- `completed`: All schools succeeded
- `failed`: All schools failed

**School Statuses**:
- `pending`: Awaiting processing
- `processing`: Currently processing
- `success`: All subjects imported
- `partial`: Some subjects imported
- `failed`: All subjects failed

**File Statuses**:
- `pending`: Registered, awaiting processing
- `processing`: Currently processing
- `success`: All rows imported
- `failed`: Processing failed

## Failure Recovery

### Recovery Rules

| Scenario | Action | Result |
|----------|--------|--------|
| One school fails | Other schools continue | Import status = partial |
| One subject fails | School marks as partial | Other subjects in school continue |
| One row fails | Log error, continue | Subject continues, status reported |

### Retry Mechanisms

#### 1. Retry Single School
```
POST /api/bulk-import/{id}/retry-school
{
  "school_id": 123
}
```

**Process**:
1. Reset school status to 'pending'
2. Reset counters (processed_subjects=0)
3. Re-dispatch ProcessBulkImportSchool job
4. Extraction directory is reused

#### 2. Retry All Failed Schools
```
POST /api/bulk-import/{id}/retry-all
```

**Process**:
1. Find all schools with status in [failed, partial]
2. Reset each to pending
3. Re-dispatch jobs for each
4. Update import status to 'importing'

#### 3. Recovery Status
```
GET /api/bulk-import/{id}/recovery-status
```

**Response**:
```json
{
  "import_id": 123,
  "import_status": "failed",
  "total_schools": 5,
  "schools_summary": {
    "pending": 0,
    "successful": 3,
    "partial": 1,
    "failed": 1
  },
  "failed_schools": [
    {
      "school_id": 456,
      "school_code": "S0203",
      "school_name": "IRINGA GIRLS",
      "status": "failed",
      "error_summary": "CSV file not found for subject PHY",
      "can_retry": true
    }
  ],
  "partial_schools": [...],
  "can_retry_all": true
}
```

## Authorization

### Policy: BulkImportPolicy

#### View Import
- Admin: ✓ any import
- School Officer: ✓ own school imports only
- District Officer: ✓ own district imports only
- Regional Officer: ✓ districts in own region

#### Upload School CSV
- School Officer: ✓ own school only
- Regional Officer: ✓ schools in own region
- Admin: ✓ any school

#### Upload District CSV
- School Officer: ✗ not allowed
- District Officer: ✓ own district only
- Regional Officer: ✓ districts in own region
- Admin: ✓ any district

#### Retry Import
- Same as View

## Audit Trail

### Logged Events

1. **Import Started**
```
- Event: District Bulk Import Started
- Logged: bulk_import_id, district_id, exam_year_id, total_schools, total_files
- Channel: audit
- User: authenticated user
- Timestamp: ISO 8601
- IP: request IP
```

2. **School Processing**
```
- Event: School import started/completed
- Logged: school_id, school_code, status, processed_subjects
```

3. **Signature Events**
```
- Event: ZIP Signature Event
- Action: sign|verify
- Result: success|failed
- Logged: zip_hash, user_id, timestamp
```

### Data Integrity

- ZIP hash stored in bulk_imports.zip_hash
- Manifest hash stored in bulk_imports.manifest_hash
- Signature stored in bulk_imports.signature
- File hashes stored in bulk_import_files.file_hash
- Row-level errors stored in error log

## API Endpoints

### Preview ZIP
```
POST /api/bulk-import/preview
Content-Type: multipart/form-data

zip_file: <binary>

Response:
{
  "success": true,
  "preview": {
    "scope_type": "district",
    "district": "IRINGA_M",
    "exam_year": 2025,
    "schools": [
      {
        "school_code": "S0203",
        "school_name": "IRINGA GIRLS",
        "subjects": [
          {
            "code": "PHY",
            "papers": ["P1", "P2"],
            "candidates": 2140
          }
        ],
        "total_subjects": 1,
        "total_candidates": 2140
      }
    ],
    "total_schools": 1,
    "total_subjects": 1,
    "total_candidates": 2140,
    "is_signed": true,
    "generated_at": "2025-03-15T10:45:00Z",
    "issues": [],
    "is_valid": true
  }
}
```

### Start District Import
```
POST /api/bulk-import/district/start
Content-Type: application/json

{
  "district_id": 1,
  "exam_year_id": 2025
}

Response:
{
  "success": true,
  "bulk_import_id": 123,
  "message": "District-level bulk import started"
}
```

### Get Import Progress
```
GET /api/bulk-import/{id}/progress

Response:
{
  "success": true,
  "progress": {
    "id": 123,
    "district": "Iringa Municipality",
    "exam_year": "2025 ACSEE",
    "status": "importing",
    "progress_percentage": 60,
    "total_schools": 5,
    "processed_schools": 3,
    "total_files": 15,
    "processed_files": 10,
    "schools": [
      {
        "school_id": 456,
        "school_code": "S0203",
        "school_name": "IRINGA GIRLS",
        "status": "success",
        "total_subjects": 3,
        "processed_subjects": 3,
        "total_candidates": 2140,
        "successful_candidates": 2140,
        "failed_candidates": 0
      }
    ],
    "summary": {
      "total_schools": 5,
      "processed_schools": 3,
      "successful_schools": 2,
      "partial_schools": 1,
      "failed_schools": 0,
      "total_candidates": 15000,
      "successful_candidates": 14985,
      "failed_candidates": 15,
      "progress_percentage": 60
    }
  }
}
```

### Get Import Details
```
GET /api/bulk-import/{id}

Response: Same as progress but with more details including file-level info
```

### Get Recovery Status
```
GET /api/bulk-import/{id}/recovery-status

Response:
{
  "success": true,
  "recovery_status": {
    "import_id": 123,
    "import_status": "partial",
    "total_schools": 5,
    "schools_summary": {
      "pending": 0,
      "successful": 3,
      "partial": 1,
      "failed": 1
    },
    "failed_schools": [...],
    "partial_schools": [...],
    "can_retry_all": true
  }
}
```

### Retry School
```
POST /api/bulk-import/{id}/retry-school
{
  "school_id": 456
}

Response:
{
  "success": true,
  "message": "Retry dispatched for school 456"
}
```

### Retry All Failed Schools
```
POST /api/bulk-import/{id}/retry-all

Response:
{
  "success": true,
  "message": "Retry started for 2 schools",
  "schools_retried": 2
}
```

## Implementation Checklist

- [x] Database migrations for scope support
- [x] BulkImport model with relationships
- [x] BulkImportSchool pivot table
- [x] DistrictBulkImportOrchestrator service
- [x] DistrictManifestValidator service
- [x] ProcessBulkImportSchool job
- [x] ProcessBulkImportFile job (existing)
- [x] DistrictImportRecoveryService
- [x] BulkImportPolicy with authorization
- [x] BulkImportController endpoints
- [x] Routes for all endpoints
- [x] ZipPreviewService for district ZIPs
- [x] Audit logging throughout

## Testing Checklist

- [ ] Preflight validation (all scenarios)
- [ ] ZIP extraction and file finding
- [ ] School job dispatch and execution
- [ ] Per-subject CSV processing
- [ ] Failure isolation (school fails, others continue)
- [ ] Recovery (retry single school)
- [ ] Recovery (retry all failed schools)
- [ ] Authorization checks
- [ ] Audit trail completeness
- [ ] Signature verification

## Security Considerations

1. **ZIP Signature**: HMAC-SHA256 with APP_KEY
2. **Checksum Verification**: SHA-256 for ZIP and manifest
3. **School Ownership**: Validated during preflight
4. **Candidate Index**: Validates existence in database
5. **Exam Year Isolation**: Enforced by exam_year_id FK
6. **Role-Based Access**: Via BulkImportPolicy
7. **Immutable Records**: Imports cannot be deleted without admin action

## Performance Tuning

- **Chunk Size**: 500 rows per memory cycle
- **Job Retry**: max 3 attempts
- **Job Timeout**: 1 hour per school
- **Temp Directory**: Cleaned after import completes
- **Indexing**: `(scope_type, scope_id)` for fast lookups

## Compliance

- **NECTA Alignment**: School is atomic authority
- **Auditability**: Full trail of user, timestamp, checksum
- **Reproducibility**: Zip checksums allow verification
- **Appeal Cases**: ZIP signature enables content verification
- **Post-Publication**: Exam year locking enforced
