# District Bulk Import - Database Schema

## Tables Overview

```
bulk_imports
├── id (PK)
├── school_id (FK, nullable)
├── district_id (FK, nullable)
├── exam_year_id (FK)
├── scope_type (enum)
├── scope_id
├── status (enum)
├── ... counters and audit fields
└── indices: (scope_type, scope_id), (district_id, exam_year_id)

bulk_import_schools (PIVOT)
├── id (PK)
├── bulk_import_id (FK)
├── school_id (FK)
├── school_code (audit)
├── school_name (audit)
├── status (enum)
├── ... counters and error tracking
└── indices: (bulk_import_id, school_id), (school_id, status)

bulk_import_files (EXISTING)
├── id (PK)
├── bulk_import_id (FK)
├── subject_id (FK)
├── filename
├── status (enum)
├── rows_total, rows_success, rows_failed
└── indices: bulk_import_id
```

## Detailed Schema

### bulk_imports Table

```sql
CREATE TABLE bulk_imports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Scope identification
    school_id BIGINT UNSIGNED NULLABLE, -- FK to schools (school-level import)
    district_id BIGINT UNSIGNED NULLABLE, -- FK to districts (district-level import)
    exam_year_id BIGINT UNSIGNED NOT NULL, -- FK to exam_years
    
    scope_type ENUM('school', 'district') NOT NULL DEFAULT 'school',
    scope_id BIGINT UNSIGNED NOT NULL, -- Points to either school_id or district_id
    
    -- Status tracking
    status ENUM('validating', 'importing', 'partial', 'completed', 'failed') 
           DEFAULT 'validating',
    
    -- File/school counters
    total_files INT NOT NULL DEFAULT 0, -- For school imports: number of subjects
    processed_files INT NOT NULL DEFAULT 0, -- School imports
    total_schools INT NOT NULL DEFAULT 0, -- For district imports
    processed_schools INT NOT NULL DEFAULT 0, -- District imports
    
    -- Audit & integrity
    zip_hash VARCHAR(64) NOT NULL, -- SHA-256 hash of ZIP file
    manifest_hash VARCHAR(64) NULLABLE, -- SHA-256 hash of manifest
    signature VARCHAR(255) NULLABLE, -- HMAC-SHA256 signature (base64)
    
    -- Error tracking
    error_summary LONGTEXT NULLABLE, -- JSON or delimited list of errors
    
    -- Timestamps
    created_by BIGINT UNSIGNED NOT NULL, -- FK to users
    started_at TIMESTAMP NULLABLE,
    completed_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_year_id) REFERENCES exam_years(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    -- Indices
    INDEX idx_scope (scope_type, scope_id),
    INDEX idx_district (district_id, exam_year_id),
    INDEX idx_school (school_id, exam_year_id),
    INDEX idx_status (status),
    INDEX idx_exam_year (exam_year_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### bulk_import_schools Table (Pivot)

```sql
CREATE TABLE bulk_import_schools (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Relationships
    bulk_import_id BIGINT UNSIGNED NOT NULL, -- FK to bulk_imports
    school_id BIGINT UNSIGNED NOT NULL, -- FK to schools
    
    -- Audit trail (immutable records of school at time of import)
    school_code VARCHAR(10) NOT NULL, -- e.g., "S0203"
    school_name VARCHAR(255) NOT NULL, -- e.g., "IRINGA GIRLS"
    
    -- Status tracking (per school in the import)
    status ENUM('pending', 'processing', 'success', 'partial', 'failed') 
           DEFAULT 'pending',
    
    -- Subject counters (within this school)
    total_subjects INT NOT NULL DEFAULT 0,
    processed_subjects INT NOT NULL DEFAULT 0,
    
    -- Candidate counters (sum of candidates in all subjects for this school)
    total_candidates INT NOT NULL DEFAULT 0,
    successful_candidates INT NOT NULL DEFAULT 0,
    failed_candidates INT NOT NULL DEFAULT 0,
    
    -- Error tracking
    error_summary LONGTEXT NULLABLE, -- JSON array of errors for this school
    
    -- Timestamps
    started_at TIMESTAMP NULLABLE, -- When processing started
    completed_at TIMESTAMP NULLABLE, -- When processing completed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (bulk_import_id) REFERENCES bulk_imports(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    
    -- Unique constraint: each school appears once per import
    UNIQUE KEY uq_import_school (bulk_import_id, school_id),
    
    -- Indices
    INDEX idx_school_status (school_id, status),
    INDEX idx_import_status (bulk_import_id, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### bulk_import_files Table (Existing - Compatible)

```sql
CREATE TABLE bulk_import_files (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Relationships
    bulk_import_id BIGINT UNSIGNED NOT NULL, -- FK to bulk_imports
    subject_id BIGINT UNSIGNED NULLABLE, -- FK to subjects
    
    -- File information
    subject_code VARCHAR(10) NOT NULL, -- e.g., "PHY"
    filename VARCHAR(255) NOT NULL, -- Original CSV filename
    file_hash VARCHAR(64) NULLABLE, -- SHA-256 hash of CSV
    
    -- Status tracking
    status ENUM('pending', 'processing', 'success', 'failed') 
           DEFAULT 'pending',
    
    -- Row counters
    rows_total INT NOT NULL DEFAULT 0,
    rows_success INT NOT NULL DEFAULT 0,
    rows_failed INT NOT NULL DEFAULT 0,
    
    -- Error tracking
    error_log LONGTEXT NULLABLE, -- JSON array of row errors
    
    -- Timestamps
    started_at TIMESTAMP NULLABLE,
    completed_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (bulk_import_id) REFERENCES bulk_imports(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    
    -- Indices
    INDEX idx_bulk_import (bulk_import_id),
    INDEX idx_subject (subject_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Data Relationships

```
User (created_by)
    ↓
BulkImport (scope_type='district', scope_id=district_id)
    ├─ District (via district_id)
    ├─ ExamYear (via exam_year_id)
    ├─ BelongsToMany: School (via bulk_import_schools pivot)
    │   ├─ school_code (audit)
    │   ├─ school_name (audit)
    │   ├─ status (pending|processing|success|partial|failed)
    │   ├─ total_subjects, processed_subjects
    │   ├─ total_candidates, successful_candidates, failed_candidates
    │   └─ error_summary
    │
    └─ HasMany: BulkImportFile (per subject CSV)
        ├─ subject_id (FK to subjects)
        ├─ filename
        ├─ status (pending|processing|success|failed)
        ├─ rows_total, rows_success, rows_failed
        └─ error_log (JSON array of row errors)
```

## Example: Multi-District Scenario

```sql
-- District 1: IRINGA_M with 2 schools
INSERT INTO districts VALUES (..., 'IRINGA_M', ...);
INSERT INTO schools VALUES 
    (..., 1, 'S0203', 'IRINGA GIRLS', ...),
    (..., 1, 'S0204', 'MBEYA HIGH', ...);

-- Import record
INSERT INTO bulk_imports VALUES 
    (id=100, 
     school_id=NULL, 
     district_id=1, 
     scope_type='district', 
     scope_id=1, 
     status='importing', 
     total_schools=2, 
     ...);

-- School registrations (pivot)
INSERT INTO bulk_import_schools VALUES
    (id=1, bulk_import_id=100, school_id=1, 
     school_code='S0203', school_name='IRINGA GIRLS',
     status='processing', total_subjects=3, ...),
    (id=2, bulk_import_id=100, school_id=2, 
     school_code='S0204', school_name='MBEYA HIGH',
     status='pending', total_subjects=2, ...);

-- CSV files (for first school)
INSERT INTO bulk_import_files VALUES
    (id=1, bulk_import_id=100, subject_id=1, 
     subject_code='PHY', filename='PHY.csv', status='success',
     rows_total=2140, rows_success=2140, ...),
    (id=2, bulk_import_id=100, subject_id=2, 
     subject_code='ENG', filename='ENG.csv', status='processing', ...),
    (id=3, bulk_import_id=100, subject_id=3, 
     subject_code='BIO', filename='BIO.csv', status='pending', ...);
```

## Querying Patterns

### Get all district imports for a district
```sql
SELECT * FROM bulk_imports 
WHERE scope_type='district' AND scope_id=1;
```

### Get all schools in an import with their status
```sql
SELECT bis.*, s.id, s.name FROM bulk_import_schools bis
JOIN schools s ON bis.school_id = s.id
WHERE bis.bulk_import_id = 100
ORDER BY bis.school_code;
```

### Get failed schools in an import
```sql
SELECT bis.* FROM bulk_import_schools bis
WHERE bis.bulk_import_id = 100 
AND bis.status IN ('failed', 'partial')
ORDER BY bis.school_code;
```

### Get all files for a school in an import
```sql
SELECT bif.* FROM bulk_import_files bif
JOIN bulk_import_schools bis ON bif.bulk_import_id = bis.bulk_import_id
WHERE bis.bulk_import_id = 100 AND bis.school_id = 1
ORDER BY bif.subject_code;
```

### Get import progress summary
```sql
SELECT 
    bi.status,
    COUNT(DISTINCT bis.school_id) as total_schools,
    COUNT(DISTINCT IF(bis.status != 'pending', bis.school_id, NULL)) as processed_schools,
    COUNT(DISTINCT IF(bis.status = 'success', bis.school_id, NULL)) as successful_schools,
    COUNT(DISTINCT IF(bis.status = 'partial', bis.school_id, NULL)) as partial_schools,
    COUNT(DISTINCT IF(bis.status = 'failed', bis.school_id, NULL)) as failed_schools,
    SUM(bis.total_candidates) as total_candidates,
    SUM(bis.successful_candidates) as successful_candidates,
    SUM(bis.failed_candidates) as failed_candidates
FROM bulk_imports bi
LEFT JOIN bulk_import_schools bis ON bi.id = bis.bulk_import_id
WHERE bi.id = 100
GROUP BY bi.id;
```

## Indexes Strategy

### Primary Indexes
- `(scope_type, scope_id)` - Fast lookup of imports by district/school
- `(district_id, exam_year_id)` - Find all imports for a district in a year
- `(school_id, exam_year_id)` - Find school imports by exam year

### Secondary Indexes
- `status` - Quick filtering by import status
- `(school_id, status)` - Fast lookup of schools by status
- `(bulk_import_id, status)` - Fast lookup of schools in import by status
- `bulk_import_id` - Fast lookup of files in import

## Migration Order

1. First migration: Add scope columns to bulk_imports
   - Makes school_id nullable
   - Adds district_id with FK
   - Adds scope_type, scope_id, total_schools, processed_schools

2. Second migration: Create bulk_import_schools pivot table
   - New table for district import school tracking
   - Includes audit fields (school_code, school_name)
   - Includes status and error tracking

## Data Integrity Constraints

### Foreign Keys
- `bulk_imports.school_id` → `schools.id` (CASCADE)
- `bulk_imports.district_id` → `districts.id` (CASCADE)
- `bulk_imports.exam_year_id` → `exam_years.id` (RESTRICT)
- `bulk_imports.created_by` → `users.id` (RESTRICT)
- `bulk_import_schools.bulk_import_id` → `bulk_imports.id` (CASCADE)
- `bulk_import_schools.school_id` → `schools.id` (CASCADE)
- `bulk_import_files.bulk_import_id` → `bulk_imports.id` (CASCADE)
- `bulk_import_files.subject_id` → `subjects.id` (SET NULL)

### Unique Constraints
- `bulk_import_schools(bulk_import_id, school_id)` - Each school once per import

### Enum Values
- `bulk_imports.scope_type`: 'school' | 'district'
- `bulk_imports.status`: 'validating' | 'importing' | 'partial' | 'completed' | 'failed'
- `bulk_import_schools.status`: 'pending' | 'processing' | 'success' | 'partial' | 'failed'
- `bulk_import_files.status`: 'pending' | 'processing' | 'success' | 'failed'

## Column Size Justifications

- `zip_hash` (64 chars) - SHA-256 hex representation
- `manifest_hash` (64 chars) - SHA-256 hex representation
- `signature` (255 chars) - Base64-encoded HMAC (plus algorithm prefix)
- `subject_code` (10 chars) - e.g., "MATHEMATICS" (reasonable max)
- `school_code` (10 chars) - e.g., "S123456" (5-6 digits typical)
- `school_name` (255 chars) - School names can be long
- `filename` (255 chars) - Standard filename length
- `error_summary` (LONGTEXT) - Can contain many errors
- `error_log` (LONGTEXT) - Can contain row-level errors (thousands)

## Performance Tuning Notes

### Indexing
- Queries are indexed for fast lookups
- Composite indexes support WHERE + ORDER BY
- Status indexes enable quick filtering

### Partitioning (Future)
- Could partition `bulk_import_files` by `exam_year_id` if table grows large
- Could archive completed imports to separate table after 1 year

### Optimization Tips
- Use pagination when listing imports (LIMIT 50)
- Cache recovery status for 10 seconds
- Batch update pivot statuses where possible
