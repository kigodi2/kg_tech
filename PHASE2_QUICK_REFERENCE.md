# PHASE 2: QUICK REFERENCE GUIDE
## Key Findings & Integration Points

---

## CSV FORMATS (FINAL)

### SCHOOL Candidate Allocation CSV
```csv
exam_year,index_number,combination_code,replace_allocations
2026,S0445-0001,PCB,NO
2026,S0445-0002,HGL,NO
```
- **Match key**: index_number + exam_year
- **Subject source**: combination_subject pivot (automatic)
- **Validation**: GS mandatory + ≥3 principals (via AcseeAllocationValidator)

### PRIVATE Candidate Allocation CSV
```csv
exam_year,index_number,subject_codes,replace_allocations
2026,P0652-0501,111|112|123|145,NO
2026,P0652-0502,111|001|002|003,NO
```
- **Match key**: index_number + exam_year
- **Subject source**: Pipe-separated codes from CSV
- **Validation**: GS mandatory + ≥3 principals (via AcseeAllocationValidator)

---

## KEY MODELS & RELATIONSHIPS

| Model | Key Column(s) | Notes |
|-------|---------------|-------|
| `Candidate` | `id`, `candidate_id`, `candidate_type`, `combination` | candidate_type = SCHOOL or PRIVATE |
| `Subject` | `id`, `code` | code = 111 (GS) or numeric (001, 002, etc.) |
| `Combination` | `id`, `code` | Has many subjects via pivot |
| `CandidateSubjectSelection` | `id` | Unique: (candidate_id, exam_type_id, exam_year_id, subject_id) |
| `ExamYear` | `id`, `year`, `year_label` | Denormalized in allocations |

**Pivot**: `combination_subject` (combination_id ↔ subject_id)

---

## VALIDATION RULES (Reusable)

**File**: `app/Services/AcseeAllocationValidator.php`

```php
$validator = new AcseeAllocationValidator();
$result = $validator->validate(
    $candidate,                    // Candidate model
    $examTypeId,                   // From candidate.examRegistrations()
    $examYearId,                   // From UI/CSV
    [$subjectId1, $subjectId2, ...] // Array of subject IDs
);

// Returns:
// {
//   ok: bool,
//   errors: [],
//   warnings: [],
//   principal_subject_ids: [],
//   all_subject_ids: []
// }
```

**Rules**:
1. General Studies (code 111) mandatory
2. Minimum 3 principal subjects (excluding GS)
3. No duplicates (auto-removed with warning)

---

## EXISTING ENDPOINTS (Reusable)

### Single-Candidate Allocation (Already Works)
```
POST /api/exam-types/acsee/allocate-subjects
```
- Payload: `{candidate_id, exam_year_id, subject_ids[], is_principal_map, replace_allocations, source}`
- Uses `updateOrCreate()` for idempotency
- Returns allocated subjects list

### Candidate Listing (Query for CSV Matching)
```
GET /api/exam-types/{code}/candidates?page=1&page_size=15&search=...
```
- Response includes `candidate_id`, `allocated_subjects`
- Can filter by school_id, district_id, region_id

### Combination Subjects (Template Lookup)
```
GET /api/combinations/{id}/subjects
```
- Returns pivot-related subjects for a combination

---

## DATABASE UNIQUE CONSTRAINT

**Table**: `candidate_subject_selections`

```sql
UNIQUE (candidate_id, exam_type_id, exam_year_id, subject_id)
```

**Implication**: `updateOrCreate()` is idempotent. Re-running same import doesn't duplicate allocations.

---

## IMPORT WORKFLOW (Two-Phase)

```
┌─────────────────┐
│  CSV Upload     │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│ Phase 1: Validate (Dry-run) │  ← Non-destructive, returns errors
│  - Parse CSV                │
│  - Match candidates         │
│  - Validate rules           │
└────────┬────────────────────┘
         │
    ┌────▼─────┐
    │ Show      │
    │ Report   │
    └────┬─────┘
         │
    [User Review]
         │
    ┌────▼──────────┐
    │ Proceed?      │
    └────┬──────────┘
         │ YES
         ▼
┌─────────────────────────────┐
│ Phase 2: Commit (Actual DB)  │
│  - Re-validate              │
│  - Delete if replace=YES    │
│  - Insert allocations       │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────┐
│ Return Report   │
│ (Summary +      │
│  Errors)        │
└─────────────────┘
```

---

## CANDIDATE MATCHING

**Current** (single-step allocation):
- Match by: Candidate ID (from UI selection)
- Location: Modal already has `candidate` object

**New** (CSV import):
- Match by: `index_number` (from CSV) + `exam_year_id` (from UI)
- Query:
  ```php
  $candidate = Candidate::where('candidate_id', $indexNumber)
      ->whereHas('examRegistrations', fn($q) => $q->where('exam_year_id', $examYearId))
      ->first();
  ```

---

## IDEMPOTENCY & SAFETY

**Unique Key** (prevents duplicates):
```php
CandidateSubjectSelection::updateOrCreate(
    [
        'candidate_id' => $candidateId,
        'exam_type_id' => $examTypeId,
        'exam_year_id' => $examYearId,
        'subject_id' => $subjectId,
    ],
    [
        'is_principal' => $isPrincipal,
        'source' => 'csv_import',
        'created_by' => auth()->id(),
    ]
);
```

**Result**: Running same CSV import twice = idempotent (no duplicates created)

---

## REPLACE MODE

**Default Behavior** (ADD ONLY):
- Skip if allocation already exists
- Idempotent via `updateOrCreate()`

**Replace Behavior** (DESTRUCTIVE):
- Delete existing allocations for this candidate + exam_year
- Insert new allocations
- Requires explicit CSV row flag: `replace_allocations=YES`
- Confirmation dialog before commit

---

## ERROR HANDLING

**Phase 1 Errors** (Validation):
- Returned as JSON array
- Display in modal, highlight row numbers
- Allow download as CSV

**Phase 2 Errors** (Commit):
- Partial success possible (some rows imported, some failed)
- Already-committed rows are preserved (transaction scope per candidate-subject combo)
- Return same error list + affected count

---

## MODAL STRUCTURE (Current)

**File**: `resources/views/exam-types/acsee.blade.php`

**Alpine Component**: `acseeManager()`

**Current Sections**:
- Subjects tab (CRUD)
- Combinations tab (CRUD)
- Candidates tab (read-only list)
  - Allocation modal (single-candidate, two modes: template + manual)

**To Add**:
- Candidate type filter (above candidates table)
- Bulk allocation tab or section
  - Template download buttons
  - CSV file upload
  - Exam year selector
  - Replace checkbox
  - Import button
  - Report display

---

## ENDPOINTS TO CREATE

### 1. Template Downloads
```
GET /api/exam-types/acsee/templates/school-allocation.csv
GET /api/exam-types/acsee/templates/private-allocation.csv
```
Returns: CSV file with headers + example row + instructions

### 2. CSV Allocation Import
```
POST /api/exam-types/acsee/allocate-from-csv
```
Payload:
```json
{
    "file": <File>,
    "exam_year_id": 45,
    "candidate_type_filter": "ALL|SCHOOL|PRIVATE",
    "replace_allocations_default": false
}
```
Returns: Import report (success/fail counts, error details)

---

## SERVICES TO CREATE

### 1. AcseeAllocationTemplateService
```php
public function generateSchoolTemplate(): string {}
public function generatePrivateTemplate(): string {}
```

### 2. AcseeAllocationCSVImporter
```php
public function validateCSV(file, examYearId, candidateTypeFilter): array {}
public function commitImport(file, examYearId, candidateTypeFilter, replaceDefault): array {}

private function matchCandidate(indexNumber, examYearId): ?Candidate {}
private function allocateSubjectsForSchool(Candidate, combinationCode): array {}
private function allocateSubjectsForPrivate(Candidate, subjectCodes): array {}
```

---

## TESTS TO WRITE

- [ ] SCHOOL allocation with valid combination
- [ ] SCHOOL allocation fails if combination missing
- [ ] PRIVATE allocation with valid subject codes
- [ ] PRIVATE allocation fails without GS (111)
- [ ] PRIVATE allocation fails with <3 principals
- [ ] Candidate type mismatch detection (SCHOOL index in PRIVATE import)
- [ ] Duplicate prevention (re-import same file)
- [ ] Replace mode (delete existing, insert new)
- [ ] Partial failure handling
- [ ] Error report CSV download
- [ ] Idempotency (same allocation twice = no duplicate)

---

## FILE LOCATIONS

| File | Purpose | Location |
|------|---------|----------|
| Study Document | Findings | `docs/PHASE2_IMPORT_EXISTING_BEHAVIOR.md` |
| Implementation Plan | Detailed spec | `PHASE2_IMPLEMENTATION_PLAN.md` |
| Quick Reference | This doc | `PHASE2_QUICK_REFERENCE.md` |
| Modal (to update) | UI | `resources/views/exam-types/acsee.blade.php` |
| Validator (existing) | Allocation rules | `app/Services/AcseeAllocationValidator.php` |
| Template Service (new) | CSV generation | `app/Services/AcseeAllocationTemplateService.php` |
| CSV Importer (new) | CSV parsing + import | `app/Services/AcseeAllocationCSVImporter.php` |
| Controller (new) | Routes | `app/Http/Controllers/AcseeAllocationController.php` |
| Tests (new) | Validation | `tests/Feature/AcseeAllocationCSVImportTest.php` |

---

## BACKWARD COMPATIBILITY CHECKLIST

- [x] No changes to existing candidates table schema
- [x] No changes to subject allocation endpoint
- [x] No changes to single-candidate allocation modal (only extend)
- [x] No deletion of candidates or allocations
- [x] All new functionality is additive
- [x] Existing import flows unchanged
- [x] Validation rules (GS + 3 principals) unchanged

---

## COMMON GOTCHAS

1. **Index Number Matching**:
   - CSV uses `index_number` (e.g., "S0445-0001")
   - Database column is `candidate_id`
   - These are the same thing!

2. **Subject Codes vs IDs**:
   - CSV: subject codes (e.g., "111", "001", "112")
   - DB: subject IDs (e.g., 1, 2, 3)
   - Must resolve codes → IDs before validation

3. **Combination Codes vs Subject Codes**:
   - SCHOOL CSV: combination code (e.g., "PCB")
   - PRIVATE CSV: subject codes (e.g., "111|001|002|003")
   - Don't mix them up!

4. **General Studies (111)**:
   - Mandatory for **all** ACSEE candidates
   - Must be in subject list (PRIVATE) or combination (SCHOOL)
   - Marked as non-principal (is_principal = false)

5. **Exam Year ID vs Year Label**:
   - UI dropdown shows `year_label` (e.g., "2026")
   - API expects `exam_year_id` (database ID)
   - CSV shows `exam_year` (4-digit year, need to resolve to ID)

---

## REUSE & DON'T REINVENT

✅ **Use existing**:
- `AcseeAllocationValidator` → for validation rules
- `CandidateSubjectSelection` → for allocation storage
- `CandidateImportService` → for CSV parsing patterns
- `updateOrCreate()` → for idempotency
- Existing endpoints → for single-candidate flows

❌ **Don't duplicate**:
- Validation logic (use AcseeAllocationValidator)
- CSV parsing (follow CandidateImportService pattern)
- Error reporting format
- Candidate matching logic

---

**End of Quick Reference**
