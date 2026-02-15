# PHASE 2: ACSEE CANDIDATES ALLOCATION/IMPORT — EXISTING IMPLEMENTATION STUDY

**Date**: 2026-02-15  
**Status**: STUDY COMPLETE — Non-destructive analysis of current system  
**Objective**: Document existing CSV import behavior, allocation workflow, and database schema before implementing PRIVATE/SCHOOL candidate type filtering and template generation.

---

## 1. SYSTEM OVERVIEW

The IRMS uses a **Two-Phase Candidate Import** approach:
- **Phase 1 (Validation/Dry-run)**: CSV upload → validation → preview errors (non-destructive)
- **Phase 2 (Commit)**: User confirms → inserts into database (with optional replace mode)

Additionally, there is a **Single-Step Allocation Modal** for manually allocating subjects to existing candidates per exam year.

---

## 2. CURRENT CSV TEMPLATES

### 2.1 SCHOOL Candidate CSV
**File**: `public/templates/ACSEE_SCHOOL_CANDIDATES_TEMPLATE.csv`

```csv
candidate_id,full_name,gender,exam_type,exam_year,school_code,combination,candidate_type
S0754-0501,AISHA KHALID KASIM,F,ACSEE,2026,S0754,PCM,SCHOOL
S0754-0502,JOHN PETER MWANGA,M,ACSEE,2026,S0754,HGE,SCHOOL
S0754-0503,MARY JANE OCHIENG,F,ACSEE,2026,S0754,PCB,SCHOOL
```

**Headers (Required)**:
- `candidate_id` — Unique ID per candidate (prefix S for school, e.g., S0754-0501)
- `full_name` — Candidate full name
- `gender` — M or F
- `exam_type` — PSLE, CSEE, ACSEE (optional if default is ACSEE)
- `exam_year` — 4-digit year (2026, 2027, etc.)
- `school_code` — School code (e.g., S0754)
- `combination` — Combination code (e.g., PCM, HGE, PCB) — **NOT subject codes**
- `candidate_type` — SCHOOL

**Notes**:
- Combination is a **template reference** (e.g., PCM means Physics, Chemistry, Math)
- Actual subjects are resolved from the `combinations` table via pivot relationship
- No subject allocation validation at import time (happens at allocation time)

---

### 2.2 PRIVATE Candidate CSV
**File**: `public/templates/ACSEE_PRIVATE_CANDIDATES_TEMPLATE.csv`

```csv
candidate_id,full_name,gender,exam_type,exam_year,school_code,subjects,candidate_type
P0652-0501,DORICAS GIBSON MWILONGO,M,ACSEE,2026,P0652,111|001|002|003,PRIVATE
P0652-0502,KELVIN ABINEL CHAVALA,M,ACSEE,2026,P0652,111|005|006|007,PRIVATE
P0652-0503,JANE SMITH MUTUA,F,ACSEE,2026,P0652,111|008|009|010,PRIVATE
```

**Headers (Required)**:
- `candidate_id` — Unique ID per candidate (prefix P for private, e.g., P0652-0501)
- `full_name` — Candidate full name
- `gender` — M or F
- `exam_type` — PSLE, CSEE, ACSEE (optional if default is ACSEE)
- `exam_year` — 4-digit year
- `school_code` — Exam centre / affiliation code (e.g., P0652) — **still required**
- `subjects` — **Pipe-separated subject codes** (e.g., 111|001|002|003)
- `candidate_type` — PRIVATE

**Notes**:
- Subjects are **NOT combination-based** but individually specified
- Codes are numeric, matching the `subjects.code` field
- 111 is mandatory (General Studies)
- At least 3 principals (non-111) required for ACSEE

---

## 3. DATABASE SCHEMA

### 3.1 Candidates Table
```sql
CREATE TABLE candidates (
    id BIGINT PRIMARY KEY,
    candidate_id VARCHAR(50) UNIQUE NOT NULL,       -- E.g., "S0754-0501", "P0652-0501"
    full_name VARCHAR(255) NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    school_id BIGINT,                               -- FK to schools table
    exam_type VARCHAR(50),                          -- PSLE, CSEE, ACSEE
    candidate_type VARCHAR(50),                     -- SCHOOL or PRIVATE
    combination VARCHAR(255),                       -- For SCHOOL candidates
    status VARCHAR(50) DEFAULT 'registered',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Key Observations**:
- `candidate_type` column **already exists** (SCHOOL or PRIVATE)
- `combination` column stores the **combination code** (not subject list)
- `exam_type` is per-candidate (can be different exam types)

---

### 3.2 Subjects Table
```sql
CREATE TABLE subjects (
    id BIGINT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,               -- E.g., "111", "001", "PCM"
    name VARCHAR(255) NOT NULL,                     -- E.g., "General Studies", "Physics"
    category ENUM('ARTS', 'SCIENCE', 'BUSINESS'),
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Key Observations**:
- Code is the **primary identifier** for subject matching
- Code 111 is **mandatory for ACSEE** (General Studies)
- Numeric codes (001, 002, 003, etc.) for ACSEE subjects

---

### 3.3 Combinations Table
```sql
CREATE TABLE combinations (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,               -- E.g., "PCB", "HGL", "PCM"
    exam_type_id BIGINT,                            -- FK to exam_types
    subjects VARCHAR(255),                          -- Denormalized: "Physics, Chemistry, Biology"
    category ENUM('ARTS', 'SCIENCE', 'BUSINESS'),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Pivot Table** (combination_subject):
```sql
CREATE TABLE combination_subject (
    id BIGINT PRIMARY KEY,
    combination_id BIGINT NOT NULL,                 -- FK
    subject_id BIGINT NOT NULL,                     -- FK
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Key Observations**:
- Combination has **many subjects** via pivot
- Combinations are **SCHOOL-only** (template-driven)
- Code is the **lookup key**

---

### 3.4 Candidate Subject Selections (Allocations) Table
```sql
CREATE TABLE candidate_subject_selections (
    id BIGINT PRIMARY KEY,
    candidate_id BIGINT NOT NULL,                   -- FK
    exam_type_id BIGINT NOT NULL,                   -- FK
    exam_year_id BIGINT NOT NULL,                   -- FK
    subject_id BIGINT NOT NULL,                     -- FK
    year INT,                                        -- Denormalized exam year
    is_principal BOOLEAN DEFAULT true,              -- true if not General Studies
    source VARCHAR(50),                             -- 'template' or 'manual'
    created_by BIGINT,                              -- User who allocated
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (candidate_id, exam_type_id, exam_year_id, subject_id)
);
```

**Key Observations**:
- One row per **candidate × subject × exam_year** combination
- `is_principal` is a boolean (not computed)
- `source` tracks whether allocated via template or manual
- **Unique constraint** prevents duplicates
- `updateOrCreate` is used to avoid duplicate insertion (idempotent)

---

### 3.5 Exam Years Table
```sql
CREATE TABLE exam_years (
    id BIGINT PRIMARY KEY,
    year INT NOT NULL,                              -- E.g., 2026
    year_label VARCHAR(50),                         -- E.g., "2026"
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Key Observations**:
- Used for filtering allocations in the modal
- `year_label` is displayed in UI dropdowns

---

## 4. CURRENT IMPORT WORKFLOW

### 4.1 Phase 1: CSV Validation (CandidateImportService::validateCSV)

**File**: `app/Services/Candidates/CandidateImportService.php`

**Process**:
1. Read CSV header row
2. Normalize headers (lowercase, trim)
3. For each data row:
   - Map columns to record dict
   - Validate candidate_id (format, uniqueness)
   - Validate full_name (required, non-empty)
   - Validate gender (M or F)
   - **Validate candidate_type** (SCHOOL or PRIVATE)
   - If ACSEE:
     - If SCHOOL: validate combination exists
     - If PRIVATE: school_code still required (no combination needed)
   - Validate school_code (must exist)
   - Validate exam_year (if provided)
   - Check for duplicates within file
   - Check for duplicates in database
4. Return result object:
   ```php
   [
       'success' => bool,
       'message' => string,
       'total_rows' => int,
       'valid_count' => int,
       'invalid_count' => int,
       'errors' => [
           [
               'row_number' => int,
               'candidate_id' => string,
               'full_name' => string,
               'error_messages' => [string],
               'primary_error' => string
           ]
       ],
       'summary' => ['error_type' => count]
   ]
   ```

**Validation Rules**:
- `candidate_id`: Required, unique (in file and DB), matches pattern
- `full_name`: Required, min 2 chars, max 255 chars
- `gender`: Required, M or F only
- `candidate_type`: Required, SCHOOL or PRIVATE
- `exam_year`: Optional, 4-digit year (1950–2100)
- `school_code`: Required, must exist in schools table
- `combination` (SCHOOL only): Must exist in combinations table
- `subjects` (PRIVATE only): Not validated at import (deferred to allocation)

---

### 4.2 Phase 2: Import Commit (CandidateImportService::commitImport)

**Process**:
1. Re-validate CSV (same rules as Phase 1)
2. For each valid row:
   - Insert new Candidate record (or update if mode='replace')
   - If exam_type=ACSEE and combination is provided:
     - **Auto-trigger** `registerForACSEE()` callback
     - This creates CandidateExamRegistration
     - This creates CandidateSubjectSelections (allocations) **from combination**
3. Return result:
   ```php
   [
       'success' => bool,
       'message' => string,
       'inserted_count' => int,
       'skipped_count' => int,
       'errors' => [row...]
   ]
   ```

**Special Hook** (in CandidateController::registerForACSEE):
- When a candidate is imported with `exam_type=ACSEE` and `combination` is set:
  - Combination subjects are automatically allocated
  - Validation rules are applied (GS + 3 principals)
  - Allocations are stored in `candidate_subject_selections`

---

### 4.3 Allocation Modal (Single-Step)

**File**: `resources/views/exam-types/acsee.blade.php` (Alpine.js component `acseeManager()`)

**Trigger**: Click "+" button on candidate row in ACSEE Candidates tab

**Flow**:
1. User selects Exam Year
2. User chooses allocation mode:
   - **Template Mode**: Select combination → subjects are auto-filled from pivot
   - **Manual Mode**: Checkbox list of all subjects
3. User optionally checks "Replace Allocations" (destructive)
4. User clicks "Save"
5. Front-end posts to `POST /api/exam-types/acsee/allocate-subjects`
6. Backend:
   - Validates input (candidate_id, exam_year_id, subject_ids)
   - Runs `AcseeAllocationValidator::validate()`
     - Checks for GS (111)
     - Checks for ≥3 principals
     - Checks for duplicates
   - If validation fails → return errors in response
   - If validation passes:
     - If `replace_allocations=true`: delete existing allocations for this exam_year
     - Create/update allocations via `updateOrCreate()` (idempotent)
   - Return list of allocated subjects

**Endpoint**: `POST /api/exam-types/acsee/allocate-subjects`

**Request Payload**:
```json
{
    "candidate_id": 123,
    "exam_year_id": 45,
    "subject_ids": [1, 2, 3, 4],
    "is_principal_map": {
        "1": false,     // false = GS (111)
        "2": true,      // true = principal subject
        "3": true,
        "4": true
    },
    "replace_allocations": false,
    "source": "template" or "manual"
}
```

**Response** (on success):
```json
{
    "ok": true,
    "message": "Subjects allocated successfully",
    "allocated_subjects": [
        {
            "id": 1,
            "code": "111",
            "name": "General Studies",
            "is_principal": false
        },
        {
            "id": 2,
            "code": "001",
            "name": "Physics",
            "is_principal": true
        }
    ],
    "created_count": 4,
    "skipped_count": 0
}
```

---

## 5. KEY SERVICES & VALIDATORS

### 5.1 AcseeAllocationValidator
**File**: `app/Services/AcseeAllocationValidator.php`

**Public Methods**:
- `validate(Candidate, examTypeId, examYearId, subjectIds[]): array`
  - Validates individual subject allocation
  - Checks: GS mandatory, ≥3 principals, no duplicates
  - Returns: `{ok, errors[], warnings[], principal_subject_ids[], all_subject_ids[]}`

- `validateFromCombination(Candidate, combinationId, examTypeId, examYearId): array`
  - Loads subjects from combination via pivot
  - Calls `validate()` with those subjects
  - Returns same result

**Validation Rules**:
1. **General Studies (111) is mandatory**
   - Must exist in system
   - Must be in subject list
2. **Minimum 3 principal subjects**
   - Principals = all subjects - General Studies
   - Count must be ≥3
3. **No duplicates**
   - Removed with warning
   - List is de-duplicated before validation

---

### 5.2 CandidateImportService
**File**: `app/Services/Candidates/CandidateImportService.php`

**Key Methods**:
- `validateCSV(file, examYear, examType): array` — Phase 1
- `commitImport(file, examYear, examType, mode): array` — Phase 2
- `mapRowToRecord(row, headers): array` — Column mapping
- `validateCandidateType(type, &errors)` — Type validation
- `validateCombination(code, &errors, mode)` — Combo lookup
- And other field validators

---

### 5.3 CandidateImportController
**File**: `app/Http/Controllers/CandidateImportController.php`

**Endpoints**:
- `POST /api/candidates/import/validate` → Phase 1 dry-run
- `POST /api/candidates/import/commit` → Phase 2 commit
- `POST /api/candidates/import/async` → Background job (queue)
- `POST /api/candidates/import/download-errors` → Error report CSV
- `GET /api/candidates/import/template` → Template download

---

## 6. EXISTING ENDPOINT: Allocate Subjects

**Route**: `POST /api/exam-types/acsee/allocate-subjects`  
**File**: `routes/web.php` (lines 1366–1474)

**Behavior**:
- Per-candidate, per-exam-year allocation
- No bulk CSV import for allocations (only manual modal)
- Idempotent: uses `updateOrCreate()` on unique key
- Supports replace mode with explicit confirmation

---

## 7. RELATED ENDPOINTS

### 7.1 Candidate Listing
**Route**: `GET /api/exam-types/{code}/candidates`  
**Handler**: `ExamTypeController::getAcseeCandicates()`  
**Returns**:
```json
{
    "candidates": [
        {
            "id": 123,
            "candidate_id": "S0754-0501",
            "full_name": "AISHA KHALID KASIM",
            "gender": "F",
            "combination": "PCB",
            "school_id": 45,
            "school_name": "School Name",
            "allocated_subjects": [
                {
                    "id": 1,
                    "code": "111",
                    "name": "General Studies"
                }
            ]
        }
    ],
    "pagination": {
        "page": 1,
        "page_size": 15,
        "total_count": 250,
        "total_pages": 17
    }
}
```

**Parameters**: `page`, `page_size`, `search`, `school_id`, `district_id`, `region_id`

**Note**: Allocated subjects are retrieved via `combination_subject` pivot (helper function `getCombinationSubjectsForExam()`)

### 7.2 Combination Subjects
**Route**: `GET /api/combinations/{id}/subjects`  
**Returns**: Array of subjects linked to combination via pivot

### 7.3 Combinations List
**Route**: `GET /api/exam-types/{code}/combinations`  
**Returns**: Paginated list of combinations with subject list (denormalized)

---

## 8. DATABASE RELATIONSHIPS (Laravel Models)

### Candidate Model
```php
// Has many subject selections
public function subjectSelections() { return $this->hasMany(CandidateSubjectSelection::class); }

// Through exam registrations
public function examRegistrations() { return $this->hasMany(CandidateExamRegistration::class); }
```

### Combination Model
```php
// Has many subjects (pivot)
public function subjects() { return $this->belongsToMany(Subject::class, 'combination_subject'); }
```

### Subject Model
```php
// Belongs to many combinations
public function combinations() { return $this->belongsToMany(Combination::class, 'combination_subject'); }
```

---

## 9. ALLOCATION MODAL (Current State)

**Location**: `resources/views/exam-types/acsee.blade.php` (lines 296–450+)

**Current Features**:
- ✅ Exam year dropdown
- ✅ Template mode (combination selection + subject preview)
- ✅ Manual mode (checkbox list)
- ✅ Replace allocations checkbox + confirmation
- ✅ Validation messages (errors/warnings)

**Missing Features** (to be added in Phase 2):
- ❌ Candidate Type Filter (ALL | SCHOOL | PRIVATE)
- ❌ CSV Template Generator (per candidate type)
- ❌ CSV Import Modal (bulk allocation from file)
- ❌ Import Report display

---

## 10. VALIDATION RULE SUMMARY

| Rule | Location | Applies To |
|------|----------|-----------|
| Candidate ID unique | CandidateImportService | Import (Phase 1 & 2) |
| Candidate Type = SCHOOL or PRIVATE | CandidateImportService | Import validation |
| Exam Year 4-digit | CandidateImportService | Import validation |
| School Code exists | CandidateImportService | Import validation |
| Combination exists (SCHOOL only) | CandidateImportService | Import validation |
| General Studies (111) mandatory | AcseeAllocationValidator | Allocation |
| Minimum 3 principal subjects | AcseeAllocationValidator | Allocation |
| No duplicate subjects | AcseeAllocationValidator | Allocation (warning) |
| Candidate type matches mode | TBD in Phase 2 | CSV allocation import |

---

## 11. NON-DESTRUCTIVE DESIGN NOTES

**Current Safeguards**:
1. **Phase 1 is non-destructive** → validation only, no DB changes
2. **Phase 2 requires explicit commit** → user must approve after Phase 1
3. **Replace mode requires confirmation** → explicit checkbox + alert()
4. **updateOrCreate() with unique key** → prevents duplicate allocations
5. **Idempotent operations** → re-running same allocation doesn't break

**Additional safeguards for Phase 2**:
1. CSV allocation import must follow same two-phase pattern
2. Validation must confirm candidate_type matches import mode
3. Default: add missing only (no auto-replace)
4. Replace must require explicit UI checkbox + warning
5. Errors must be reported and rows listed with issues

---

## 12. INTEGRATION POINTS FOR PHASE 2

### Need to Add:
1. **Candidate Type Filter in Modal**
   - Dropdown: ALL | SCHOOL | PRIVATE
   - Filters candidate list table (optional)
   - Defaults import mode and template display

2. **CSV Template Endpoints**
   - `GET /api/exam-types/acsee/templates/private-allocation.csv`
   - `GET /api/exam-types/acsee/templates/school-allocation.csv`
   - Returns headers + 1 example row + comments

3. **CSV Allocation Import Endpoint**
   - `POST /api/exam-types/acsee/allocate-from-csv`
   - Validates CSV rows
   - Matches candidates by index_number + exam_year
   - Applies subjects based on mode (PRIVATE = codes, SCHOOL = combination)
   - Returns import report (successes/failures)

4. **Import Modal Section**
   - File upload input
   - Preview/validation before commit
   - Error report display
   - Download failed rows option

5. **Candidate Matching Logic**
   - Current: ID-based allocation (per-candidate click)
   - New: Index-number-based allocation (bulk CSV import)
   - Match key: `index_number` + `exam_year_id`

---

## 13. FILES MODIFIED/CREATED IN THIS STUDY

- ✅ This document: `docs/PHASE2_IMPORT_EXISTING_BEHAVIOR.md`

---

## 14. NEXT STEPS (Phase 2 Implementation)

1. ✅ Study complete → This document
2. Define correct CSV formats (in spec doc or task description)
3. Create template download endpoints (2 types)
4. Add candidate type filter to modal UI
5. Create CSV allocation import endpoint
6. Add import report display to modal
7. Write tests (PRIVATE/SCHOOL validation, allocation, errors)
8. Deploy and verify

---

**End of Study Document**
