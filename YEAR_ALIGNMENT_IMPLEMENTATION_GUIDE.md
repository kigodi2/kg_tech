# Year-Based Data Alignment Implementation Guide

## ✅ Implementation Complete

This guide documents the year-based data alignment fixes for ACSEE mark entry. All changes enforce explicit exam-year registrations and prevent silent empty states.

---

## 🎯 What Was Fixed

### Problem Statement
- ACSEE candidates and subject registrations used loose `year` (integer) columns instead of `exam_year_id` FK
- Subject filtering could silently fallback to previous years or show stale data
- No clear distinction between "no registrations" vs "no matching data" in UI
- Years could not be properly locked/published
- Zero audit trail for year-based operations

### Solution
- Added `exam_year_id` FK to `candidate_exam_registrations` and `candidate_subject_selections` tables
- Enforced mandatory exam year selection in registration flow
- Updated all subject filtering queries to use `exam_year_id` FK (NO loose year column)
- Added validation guardrails that return 422 errors for invalid year combos
- Implemented year-aware UI with status indicators
- Created audit logging table for year operations
- Optional legacy data alignment command

---

## 📋 Database Schema Changes

### Migration File
**File**: `database/migrations/2026_02_01_enforce_exam_year_relationships.php`

### Changes Applied

```
candidate_exam_registrations:
  + exam_year_id (FK to exam_years, NOT NULL)
  + Index: (exam_year_id, candidate_id)
  + Index: (exam_year_id, exam_type_id)
  
candidate_subject_selections:
  + exam_year_id (FK to exam_years, NOT NULL)
  + Index: (exam_year_id, candidate_id)
  + Index: (exam_year_id, subject_id)

exam_year_audit_logs (NEW TABLE):
  - id (PK)
  - exam_year_id (FK)
  - user_id (FK to users)
  - action (VARCHAR 100: REGISTER, LOCK, PUBLISH, BACKFILL, etc.)
  - affected_records (INT)
  - details (JSON: structured data about operation)
  - executed_at (TIMESTAMP)
```

### Running the Migration

```bash
php artisan migrate
```

**What it does:**
1. Adds `exam_year_id` columns to both registration tables (nullable initially)
2. Backlills existing data by matching `year` integers to `exam_year` records
3. Makes columns NOT NULL after backfill
4. Creates compound indexes for query optimization
5. Creates `exam_year_audit_logs` table for operational audits

---

## 🔧 Model Updates

### CandidateExamRegistration
**File**: `app/Models/CandidateExamRegistration.php`

```php
// New relationship
public function examYear()
{
    return $this->belongsTo(ExamYear::class);
}

// Added to $fillable
'exam_year_id',
```

### CandidateSubjectSelection
**File**: `app/Models/CandidateSubjectSelection.php`

```php
// New relationship
public function examYear()
{
    return $this->belongsTo(ExamYear::class);
}

// Added to $fillable
'exam_year_id',
```

---

## 🛡️ Validation Service (NEW)

### ExamYearValidationService
**File**: `app/Services/ExamYear/ExamYearValidationService.php`

Provides business-logic validation for year-based operations.

#### Methods

```php
// Validate candidate can register for year
validateCandidateRegistration(Candidate $candidate, $examYear): array
  → ['valid' => bool, 'message' => string, 'code' => string]
  → Codes: ELIGIBLE, YEAR_LOCKED, YEAR_PUBLISHED, ALREADY_REGISTERED

// Validate mark entry allowed for school + year
validateMarkEntry(int $schoolId, $examYear): array
  → ['valid' => bool, 'message' => string, 'code' => string, 'candidate_count' => int]
  → Codes: ALLOWED, YEAR_LOCKED, NO_CANDIDATES, INVALID_YEAR

// Validate subject available for year
validateSubjectForYear(Subject $subject, $examYear, int $schoolId): array
  → ['valid' => bool, 'message' => string, 'code' => string, 'candidate_count' => int]
  → Codes: AVAILABLE, SUBJECT_INACTIVE, NO_SUBJECT_REGISTRATIONS, INVALID_YEAR

// Validate year can be locked
validateCanLockYear(ExamYear $year): array
  → ['valid' => bool, 'message' => string, 'code' => string]
  → Codes: READY_TO_LOCK, ALREADY_LOCKED, INCOMPLETE_MARKS

// Helper methods
getCurrentYear(): ?ExamYear
getNextYear(ExamYear $current): ?ExamYear
ensureUnlocked($examYear): ExamYear  // Throws if locked
```

---

## 🔄 Subject Filtering Service (Updated)

### SubjectFilterService
**File**: `app/Services/MarkImport/SubjectFilterService.php`

#### Key Changes
- All methods now use `exam_year_id` FK instead of loose `year` column
- Added `resolveExamYearId()` helper to convert year integers to IDs
- Strict year isolation: NO fallback to previous years

#### Updated Methods

```php
getSubjectsBySchoolAndYear(int $schoolId, int $examYear): Collection
  // Now uses exam_year_id FK in joins
  // Returns empty collection if year not found (no silent fallback)

schoolHasACSEECandidates(int $schoolId, int $examYear): bool
  // Uses exam_year_id FK for strict isolation

getACSEECandidateCount(int $schoolId, int $examYear): int
  // Uses exam_year_id FK, returns 0 if not found

getSubjectEnrollmentStats(int $schoolId, int $examYear): array
  // Uses exam_year_id FK for audit/debugging
```

#### Year Resolution Logic

```php
private function resolveExamYearId(int $examYear): ?int
{
  if ($examYear < 100) {
    // Treat as exam_year_id (1-100 range)
    return ExamYear::find($examYear)?->id;
  }
  // Treat as year label (2024, 2025, etc.)
  return ExamYear::where('year_label', (string)$examYear)->first()?->id;
}
```

---

## 🚨 MarkEntryController (Updated)

### File
`app/Http/Controllers/MarkEntryController.php`

### Changes

#### 1. Added Validation Service Injection
```php
public function __construct(
    // ... other services
    ExamYearValidationService $yearValidationService
)
```

#### 2. Updated `getSubjectsBySchoolAndYear()` Endpoint

**Behavior:**
- Validates year exists and is not locked (GUARDRAIL 1)
- Returns 422 Unprocessable Entity if validation fails
- Returns detailed error codes for frontend handling

**Response Codes:**
```
200 OK: Year valid, subjects loaded successfully
422 Unprocessable Entity: 
  - code: 'YEAR_LOCKED' → Year is locked
  - code: 'NO_CANDIDATES' → No registrations exist
  - code: 'INVALID_YEAR' → Year not found
```

**Response Example (422):**
```json
{
  "success": false,
  "data": [],
  "has_candidates": false,
  "candidate_count": 0,
  "message": "No ACSEE candidates registered for 2025. Please register candidates first.",
  "code": "NO_CANDIDATES"
}
```

---

## 👥 CandidateController (Updated)

### File
`app/Http/Controllers/CandidateController.php`

### Changes

#### 1. Updated `registerForACSEE()` Method

**Key Changes:**
- Now accepts optional `$examYear` parameter (defaults to current active year)
- Uses `ExamYearValidationService` to validate registration eligibility
- Creates registrations with `exam_year_id` FK
- Stores year label in backward-compatible `year` column

**Method Signature:**
```php
private function registerForACSEE(
    Candidate $candidate,
    ?string $combination,
    $examYear = null  // NEW: Can be ExamYear, int ID, or int label
): void
```

**Validation Flow:**
1. Validate combination provided
2. Resolve exam year (null → active year)
3. Validate candidate eligible (not already registered, year not locked)
4. Create registration with `exam_year_id` FK
5. Create subject selections with `exam_year_id` FK

**Example Usage:**
```php
// Use current active year (default)
$this->registerForACSEE($candidate, 'PCM');

// Use specific year
$this->registerForACSEE($candidate, 'PCM', 2025);

// Use ExamYear model
$this->registerForACSEE($candidate, 'PCM', $examYear);
```

---

## 🎨 Frontend UI Updates

### File
`resources/views/mark-entry/index.blade.php`

### Changes

#### 1. Alpine.js State
```javascript
examYear: 2024,           // Year integer
yearIsLocked: false,      // UI state for locked indicator
yearStatus: null,         // 'active', 'locked', 'draft'
```

#### 2. Enhanced Error Messages
- No candidates: "No ACSEE candidates registered for 2024. Please register candidates first."
- Year locked: "Year 2024 is locked. Mark entry is disabled." (RED banner)
- Network error: "Error loading subjects." (with retry capability)

#### 3. Year-Aware Subject Loading
```javascript
async loadFilteredSubjects() {
  // On 422 response:
  // - Check if code === 'YEAR_LOCKED' → Set yearIsLocked = true
  // - Show appropriate message
  // - Disable subject dropdown
}
```

#### 4. UI Visibility Logic
```html
<!-- No candidates message (only if year NOT locked) -->
<div x-show="selectedSchool && !subjectLoading && filteredSubjects.length === 0 && !yearIsLocked">
  <!-- Show helpful message -->
</div>

<!-- Year locked message (red) -->
<div x-show="yearIsLocked">
  <i class="fas fa-lock"></i>
  Year {{ examYear }} is locked. Mark entry is disabled.
</div>
```

---

## 🔨 Artisan Command (Optional Legacy Alignment)

### File
`app/Console/Commands/AlignLegacyACSEEYear.php`

### Purpose
One-time command to assign legacy ACSEE candidates (with NULL `exam_year_id`) to an explicit exam year.

### Safety Constraints
- ❌ NO auto-assignment
- ❌ NO default year assumption
- ✅ Requires explicit year selection
- ✅ Shows preview of affected records
- ✅ Requires confirmation before execution
- ✅ Creates audit log entry

### Usage

```bash
# Interactive mode
php artisan acsee:align-legacy-year
```

**Prompt Sequence:**
1. Lists available exam years
2. Asks for target year ID
3. Validates year (not locked)
4. Shows preview: # registrations, # selections
5. Requires confirmation: "Assign all X records to year YYYY?"
6. Executes and reports:
   - Registrations updated: X
   - Selections updated: Y
   - Total affected: X+Y

**Audit Trail:**
- Creates entry in `exam_year_audit_logs` table
- Logs: action='LEGACY_ALIGNMENT', affected_records count, JSON details

---

## 📊 Audit Logging

### Audit Table
**Table**: `exam_year_audit_logs`

**Schema:**
```sql
CREATE TABLE exam_year_audit_logs (
  id BIGINT PRIMARY KEY,
  exam_year_id BIGINT (FK),
  user_id BIGINT NULL (FK to users),
  action VARCHAR(100),         -- REGISTER, LOCK, PUBLISH, BACKFILL, LEGACY_ALIGNMENT
  affected_records INT,
  details JSON,               -- { candidates: [...], subjects: [...] }
  executed_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**Queries:**
```php
// Get all operations for a year
ExamYearAuditLog::where('exam_year_id', $yearId)->get();

// Get legacy alignment operations
ExamYearAuditLog::where('action', 'LEGACY_ALIGNMENT')->get();

// Get operations by user
ExamYearAuditLog::where('user_id', $userId)->get();
```

---

## 🧪 Testing Checklist

### Database
- [ ] Migration runs successfully without errors
- [ ] Legacy data is backfilled correctly
- [ ] `exam_year_id` FK constraints work
- [ ] Compound indexes created

### Models
- [ ] `CandidateExamRegistration::examYear()` relationship works
- [ ] `CandidateSubjectSelection::examYear()` relationship works
- [ ] Models accept `exam_year_id` in fillable array

### Subject Filtering
- [ ] `getSubjectsBySchoolAndYear()` uses exam_year_id FK
- [ ] No fallback to previous years
- [ ] Returns empty collection if year not found
- [ ] Query performance is acceptable

### Validation Service
- [ ] `validateCandidateRegistration()` rejects locked years
- [ ] `validateMarkEntry()` returns NO_CANDIDATES correctly
- [ ] `validateSubjectForYear()` enforces year isolation
- [ ] Error messages are clear and actionable

### MarkEntryController
- [ ] GET `/api/mark-entry/acsee/subjects-by-school` returns 422 for locked year
- [ ] Response includes `code` field for frontend
- [ ] Cache is invalidated properly

### CandidateController
- [ ] `registerForACSEE()` accepts exam_year parameter
- [ ] Registrations created with `exam_year_id` FK
- [ ] Subject selections created with `exam_year_id` FK
- [ ] Validation prevents duplicate registrations

### Frontend
- [ ] Year filtering works correctly
- [ ] No candidates message shows when appropriate
- [ ] Locked year message shows in red
- [ ] Subject dropdown disabled when no candidates
- [ ] 422 errors handled gracefully

### Artisan Command
- [ ] Lists available years
- [ ] Validates locked year check
- [ ] Shows preview of affected records
- [ ] Requires explicit confirmation
- [ ] Audit log created

---

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] Backup database
- [ ] Review all migration changes
- [ ] Test in staging environment
- [ ] Update API documentation

### Deployment Steps
1. **Pull latest code**
   ```bash
   git pull origin main
   ```

2. **Run migration**
   ```bash
   php artisan migrate
   ```

3. **Clear caches**
   ```bash
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   ```

4. **Verify models**
   ```bash
   php artisan tinker
   > ExamYear::active()->first()
   > CandidateExamRegistration::first()->examYear
   ```

5. **Test mark entry endpoint**
   ```bash
   curl "http://localhost/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1"
   ```

### Post-Deployment
- [ ] Monitor error logs
- [ ] Test mark entry UI in production
- [ ] Verify no silent fallbacks occur
- [ ] Check audit logs for anomalies

---

## 🎓 Developer Reference

### Core Concepts

**Exam Year as Domain Boundary**
- Every ACSEE operation (registration, mark entry, subject selection) is scoped to an explicit exam year
- Years can be active (current operations), locked (read-only), or draft (inactive)
- Data MUST NOT flow between years without explicit action

**Strict Year Isolation**
- No fallback to previous years
- No auto-assignment of years
- All year references use `exam_year_id` FK (NOT loose year integers)
- Invalid year combos return 422 errors (not 500 or silent failure)

**Validation Guardrails**
- Every operation validates year state before proceeding
- Meaningful error messages for every failure mode
- Audit trail for all year-related operations
- Clear distinction between "data not found" vs "year not configured"

### Common Tasks

#### Register candidate for specific year
```php
$candidate = Candidate::find(1);
$examYear = ExamYear::where('year_label', '2024')->first();
$this->registerForACSEE($candidate, 'PCM', $examYear);
```

#### Get subjects for mark entry
```php
$service = app(SubjectFilterService::class);
$subjects = $service->getSubjectsBySchoolAndYear($schoolId, $examYear);
```

#### Validate year before operation
```php
$validation = app(ExamYearValidationService::class);
$result = $validation->validateMarkEntry($schoolId, $examYear);

if (!$result['valid']) {
    return response()->json(['error' => $result['message']], 422);
}
```

#### Query registrations for year
```php
CandidateExamRegistration::where('exam_year_id', $examYear->id)
    ->with('candidate', 'examType')
    ->get();
```

---

## 🔗 Related Files

### Core Implementation
- Migration: `database/migrations/2026_02_01_enforce_exam_year_relationships.php`
- Models: `app/Models/CandidateExamRegistration.php`, `CandidateSubjectSelection.php`
- Services: `app/Services/ExamYear/ExamYearValidationService.php`, `app/Services/MarkImport/SubjectFilterService.php`
- Controllers: `app/Http/Controllers/MarkEntryController.php`, `CandidateController.php`
- Views: `resources/views/mark-entry/index.blade.php`

### Documentation
- This file: `YEAR_ALIGNMENT_IMPLEMENTATION_GUIDE.md`
- Plan: `YEAR_ALIGNMENT_IMPLEMENTATION_PLAN.md`
- Database audit logs: Check `exam_year_audit_logs` table for operational history

---

## 📞 Support & Questions

For questions about the year alignment implementation:
1. Check this guide's "Common Tasks" section
2. Review inline code comments (marked with IMPORTANT)
3. Check `ExamYearValidationService` for available validations
4. Review `exam_year_audit_logs` table for what operations occurred

---

**Last Updated**: Feb 01, 2026  
**Status**: ✅ Implementation Complete  
**Next Steps**: Deploy to staging, run tests, deploy to production  
