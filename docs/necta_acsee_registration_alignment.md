# NECTA-Aligned ACSEE Registration & Subject Allocation

## Executive Summary
This document outlines the current state of ACSEE candidate registration and subject allocation, gaps relative to NECTA behavior, and a minimal non-destructive path to alignment.

---

## CURRENT STATE

### Data Model
#### Candidates Table
- **Columns**: `id`, `school_id` (FK), `candidate_id` (unique), `first_name`, `last_name`, `gender`, `date_of_birth`, `is_active`, `combination` (string), `exam_type`, `full_name`, `status`, `timestamps`
- **Current behavior**: All candidates are treated as SCHOOL candidates (tied to school_id)
- **Combination field**: Stores combination code as a string (e.g., 'CBE', 'HGE', 'PCB')
- **Relationship**: `belongsTo(School)`, `belongsTo(Combination, 'combination', 'code')`

#### Candidate Subject Selections Table
- **Columns**: `id`, `candidate_id` (FK), `exam_type_id` (FK), `exam_year_id` (FK), `subject_id` (FK), `year`, `is_active`, `timestamps`
- **Unique constraint**: `(candidate_id, exam_type_id, subject_id, year)`
- **Current role**: Stores allocated subjects per candidate
- **Truth table status**: YES, this is the "truth" source for what subjects a candidate is allocated

#### Combinations Table
- **Columns**: `id`, `exam_type_id` (FK), `code` (string), `subjects` (text/JSON), `is_active`, `timestamps`
- **Unique constraint**: `(exam_type_id, code)`
- **Purpose**: Template definitions (e.g., CBE = Chemistry, Biology, English)

#### Combination Subject Table (Pivot)
- **Columns**: `id`, `combination_id` (FK), `subject_id` (FK), `timestamps`
- **Unique constraint**: `(combination_id, subject_id)`
- **Purpose**: Relational mapping of combination ↔ subjects

### Current Flows
#### Registration UI (`/registration/candidates`)
1. User opens "Register Candidate" modal
2. Fields: Index Number, Full Name, Sex, **Combination** (ACSEE only), School, Exam Year, Exam Type
3. Submit: Creates `Candidate` record with `combination` string
4. No automatic subject allocation occurs at registration time

#### ACSEE Management UI (`/exam-types/acsee`)
- **Candidates tab**: Shows read-only list of ACSEE candidates with:
  - Index Number, Full Name, Sex, Combination, Allocated Subjects, School
  - Allocated Subjects pulled from `candidate_subject_selections`
- **No allocation UI**: Users cannot allocate subjects from this page currently

### ACSEE Rules (Current Implementation)
- General Studies (subject code 111) is mandatory ✓ (in validation logic elsewhere)
- Minimum 3 principal subjects ✓ (in validation logic elsewhere)
- No duplicate subject allocations ✓ (unique constraint)

---

## GAPS vs. NECTA BEHAVIOR

| Aspect | Current | NECTA | Gap |
|--------|---------|-------|-----|
| **Candidate Types** | Only SCHOOL | SCHOOL + PRIVATE | Missing PRIVATE support |
| **Combination Role** | Required field for ACSEE | Optional template | Combination should be nullable for PRIVATE |
| **Subject Allocation** | Manual entry or template (implicit) | Subject-driven, tracked per candidate | No explicit allocation UI; no tracking of allocation source |
| **Private Centre** | Not supported | Private candidates identified (P####) | Missing centre tracking |
| **Principal vs Optional** | Not tracked in schema | Differentiated | Missing `is_principal` flag in selections |
| **Allocation Source** | Implicit | Tracked (manual/import/template) | Missing source tracking |
| **Audit Trail** | Minimal | User who allocated | Missing `created_by` in subject selections |

---

## PENDING MIGRATION ANALYSIS

**File**: `2026_02_15_add_necta_alignment_columns.php`

### What It Adds (Already Well-Designed):
1. **candidates.candidate_type**: ENUM('SCHOOL', 'PRIVATE'), default 'SCHOOL'
   - Additive, safe default
   
2. **candidates.combination_id**: Nullable FK to combinations, adds relational lookup
   - Keeps existing string `combination` column for backward compat
   - Allows proper relational queries
   
3. **candidate_subject_selections.is_principal**: Boolean, default false
   - Tracks whether a subject is one of the major subjects
   
4. **candidate_subject_selections.source**: ENUM('manual', 'import', 'template'), default 'template'
   - Tracks allocation source
   
5. **candidate_subject_selections.created_by**: Nullable FK to users
   - Audit trail: who allocated this subject
   
6. **Indexes**: For principal queries and source tracking
   - Performance optimization

### Assessment
✅ **The migration is well-designed and sufficient** for NECTA alignment. No additional columns needed.

---

## PROPOSED MINIMAL CHANGES

### Phase 1: Apply Pending Migration (REQUIRED)
```bash
php artisan migrate --path=database/migrations/2026_02_15_add_necta_alignment_columns.php
```

### Phase 2: Update Models (ADDITIVE)

#### Candidate Model
- Add `candidate_type` to `$fillable`
- Add `combination_id` to `$fillable`
- Add cast for `candidate_type`
- Add relation: `combination()` via `combination_id` (in addition to existing string-based relation)

#### CandidateSubjectSelection Model
- Add `is_principal`, `source`, `created_by` to `$fillable`
- Add casts for `is_principal` (boolean), `source` (string)
- Add relation: `createdBy()` to User model

### Phase 3: Create Validation Service

**File**: `app/Services/AcseeAllocationValidator.php`

**Responsibilities**:
- Validate a candidate's subject allocation for ACSEE:
  - General Studies (code 111 or name "GENERAL STUDIES") must be present
  - Minimum 3 principal subjects (excluding GS)
  - No duplicates (enforced by unique constraint, but validate here too)
- Return normalized output: OK/FAIL, errors, warnings, principal_ids, all_ids

### Phase 4: Update Registration UI

**File**: `resources/views/registration/candidates.blade.php`

**Changes**:
- Add "Candidate Type" select in modal: SCHOOL (default) / PRIVATE
- If PRIVATE:
  - Hide/disable "School" field (optional logic; depends on your requirement)
  - Hide "Combination" field (or mark as optional)
  - Show helper text: "Private candidates allocate subjects individually."
- If SCHOOL:
  - Keep current behavior; combination optional or required per your policy

**Backend** (`routes/web.php` or controller):
- Save `candidate_type` in POST/PUT handlers
- For PRIVATE: allow `combination_id` to be null

### Phase 5: Create Allocation UI & Allocation Modal

**File**: `resources/views/exam-types/acsee.blade.php` (Candidates tab)

**Changes to Candidates Tab**:
1. Add "Candidate Type" badge to each row
2. Add "Allocate Subjects" action button per row (opens modal)

**New Allocation Modal**:

**Mode A: For SCHOOL candidates**
- Select combination (dropdown)
- Preview subjects
- Optional: toggle is_principal per subject (default: all except GS)
- Validate with AcseeAllocationValidator
- Commit with source='template'

**Mode B: For PRIVATE or Manual allocation**
- Multi-select subjects
- Automatically force General Studies (or show error if not selected)
- Toggle is_principal per subject
- Validate with AcseeAllocationValidator
- Commit with source='manual'

**Behavior**:
- "Add missing only" (default): INSERT new records; skip if (candidate, subject, exam_year) exists
- "Replace allocations" (explicit checkbox): DELETE old, INSERT new (transactional)

### Phase 6: Update Registration Import (CSV)

**Location**: Already exists in `/registration/candidates` modal

**Enhancement**:
- Accept optional `candidate_type` column in CSV (default: SCHOOL)
- Accept optional `combination_id` or `combination_code` (SCHOOL only)
- For PRIVATE candidates, combination is ignored
- Import report shows:
  - Total rows
  - Success count
  - Duplicates skipped
  - Invalid candidates (not found)
  - Invalid combinations
  - Rule violations (will be caught on subject allocation, not here)
  - Downloadable error list

---

## IMPLEMENTATION PRIORITY

1. ✅ **Apply pending migration** (2026_02_15_add_necta_alignment_columns)
2. ✅ **Update models** (Candidate, CandidateSubjectSelection)
3. ✅ **Create validator service**
4. ✅ **Update /registration/candidates UI** (add candidate_type field)
5. ✅ **Update /exam-types/acsee Candidates tab** (add allocation UI)
6. ✅ **Add tests** (registration flow, allocation flow, validator)
7. ✅ **Update CSV import** (optional: support candidate_type)

---

## DATA INTEGRITY GUARDRAILS

- **No silent deletes**: Always prompt user before replacing allocations
- **Transactions**: Wrap multi-step operations (apply template + validate)
- **Audit trail**: `created_by` and `source` fields track who did what and how
- **Backward compat**: Existing SCHOOL candidates work as before
- **Validation**: AcseeAllocationValidator prevents invalid allocations

---

## TESTING CHECKLIST

### Unit Tests
- [ ] AcseeAllocationValidator passes with >=3 principals + GS
- [ ] AcseeAllocationValidator fails without GS
- [ ] AcseeAllocationValidator fails with <3 principals
- [ ] Duplicate prevention (unique constraint)

### Feature Tests
- [ ] Register SCHOOL candidate (with or without combination)
- [ ] Register PRIVATE candidate (no combination required)
- [ ] Allocate subjects via template (SCHOOL)
- [ ] Allocate subjects manually (PRIVATE or SCHOOL)
- [ ] Import candidates with candidate_type
- [ ] Allocation report (success, skipped, errors)

### UI Tests
- [ ] Candidate Type selector in registration modal
- [ ] Combination field visibility/requirement based on type
- [ ] Allocation modal appears for each candidate
- [ ] Subject multi-select includes General Studies auto-force
- [ ] Replace allocations checkbox and behavior

---

## ROLLBACK PROCEDURE

If issues arise, rollback is simple:
```bash
php artisan migrate:rollback --path=database/migrations/2026_02_15_add_necta_alignment_columns.php
```

Since all changes are additive (no column drops, no data migration), the rollback is safe and data-preserving.

---

## REFERENCES

- **NECTA ACSEE Rules**: General Studies mandatory, >=3 principal subjects, no duplicates
- **Current Subject Allocations Table**: `candidate_subject_selections`
- **Current Combination Template**: `combination_subject` pivot
- **Pending Columns**: `candidate_type`, `combination_id`, `is_principal`, `source`, `created_by`
