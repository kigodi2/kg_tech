# NECTA-Aligned ACSEE Registration - Phase 2 Implementation Guide

**Date**: February 15, 2026  
**Status**: Phase 2 - Implementation Starting  
**Previous Phase**: Phase 1 Complete (Migration Applied ✅)

---

## CURRENT STATE (PHASE 1 COMPLETE)

### Database Schema - Already Applied ✅

Migration `2026_02_15_add_necta_alignment_columns` added:

**Candidates Table**:
- `candidate_type` (ENUM: SCHOOL, PRIVATE; default SCHOOL)
- `combination_id` (FK to combinations, nullable, relational)

**Candidate Subject Selections Table**:
- `is_principal` (boolean, default false)
- `source` (ENUM: manual, import, template; default template)
- `created_by` (FK to users, nullable, audit trail)
- Indexes: idx_principal_subjects, idx_allocation_source

**Unique Constraint**:
- (candidate_id, exam_type_id, subject_id, year)

### Existing Tables

**candidate_subject_selections** (Truth Table):
```
- id (PK)
- candidate_id (FK) ← relates to specific candidate
- exam_type_id (FK) ← relates to exam type
- subject_id (FK) ← the subject allocated
- year (integer) ← academic year
- is_active (boolean)
- is_principal (boolean) ← new
- source (enum) ← new
- created_by (FK) ← new
- timestamps
```

**combinations** (Template):
```
- id (PK)
- exam_type_id (FK)
- code (e.g., 'PCB', 'HGE')
- category, description, subjects (text)
- is_active (boolean)
- timestamps
```

**combination_subject** (Pivot):
```
- id (PK)
- combination_id (FK)
- subject_id (FK)
- timestamps
- Unique: (combination_id, subject_id)
```

**subjects**:
```
- id (PK)
- code (e.g., '111' for General Studies)
- name (e.g., 'GENERAL STUDIES', 'Physics', etc.)
- exam_type_id (FK) ← subjects tied to exam types
- other fields (category, written_papers, etc.)
```

### Models - Already Updated ✅

- **Candidate.php**: Has `combination_id`, `candidate_type` in fillable; has `combinationTemplate()` relation
- **CandidateSubjectSelection.php**: Has `is_principal`, `source`, `created_by` in fillable; has `createdBy()` relation
- **Combination.php**: Already has `subjects()` BelongsToMany relation
- **Subject.php**: Already has `combinations()` BelongsToMany relation

### Validation Service - Ready ✅

**AcseeAllocationValidator.php** exists and provides:
- `validate()` - Full validation
- `validateFromCombination()` - From template
- Rules: GS mandatory, ≥3 principals, no duplicates

### Current UI State

**Registration Modal** (`/registration/candidates`):
- "Candidate Type" field exists (SCHOOL/PRIVATE selector)
- Works correctly
- Fully functional

**ACSEE Candidates Tab** (`/exam-types/acsee`):
- Shows candidates in table
- Has "Allocate Subjects" button (+ icon)
- Button calls `openAllocationModal(candidate)` - **NOT YET IMPLEMENTED**
- Shows allocated subjects list but no allocation UI

---

## PHASE 2 GOALS

### Primary: Implement Subject Allocation Workflow

1. **Allocation Modal UI**
   - Template mode (SCHOOL candidates: apply combination template)
   - Manual mode (PRIVATE candidates or manual override)
   - Validation with error/warning display
   - Confirmation before commit

2. **API Endpoint**
   - POST /api/exam-types/acsee/allocate-subjects
   - Validate input with AcseeAllocationValidator
   - Handle add-missing vs. replace modes
   - Transactional commit

3. **Integration**
   - Wire up modal to API
   - Show success/error messages
   - Refresh candidates list after allocation
   - Handle edge cases (no exam year context, etc.)

---

## NECTA BEHAVIOR TO IMPLEMENT

### SCHOOL Candidates
- Typically have a combination template associated
- Can use "Apply Combination Template" mode
- Combination subjects + GS become allocated
- All treated as principal except GS (by default)

### PRIVATE Candidates
- May have NO combination
- MUST use "Manual Subject Selection" mode
- User selects subjects individually
- Must include GS (mandatory)
- Must include ≥3 principal subjects

### Allocation Commit Modes

**"Add Missing Only" (Default)**:
- INSERT new subject_id entries
- SKIP if (candidate_id, exam_type_id, subject_id, year) already exists
- Keeps existing allocations intact
- Safe default

**"Replace Allocations" (Explicit Checkbox)**:
- DELETE existing allocations for this exam_year
- INSERT new ones
- Shows warning: "This will remove existing allocations"
- Requires user confirmation

---

## IMPLEMENTATION PLAN

### Phase 2.1: Allocation Modal HTML & Alpine.js

**Location**: `resources/views/exam-types/acsee.blade.php`

**Components**:
1. Modal container (after other modals, ~line 245+)
2. Mode tabs: "Apply Template" | "Manual Selection"
3. Shared elements: Candidate name, Exam Year selector
4. Mode A: Combination dropdown, preview subjects
5. Mode B: Subject multi-select, GS auto-force
6. Common: Replace checkbox, action buttons

**Alpine.js Functions**:
- `openAllocationModal(candidate)` - Open, load contexts
- `setAllocationMode(mode)` - Switch modes
- `loadAllocationContexts()` - Load exam years, combinations, subjects
- `previewCombinationSubjects(combinationId)` - Preview subjects
- `saveAllocation()` - POST to API
- `closeAllocationModal()` - Close and reset

### Phase 2.2: API Endpoint

**Location**: `routes/web.php`

**Endpoint**: `POST /api/exam-types/acsee/allocate-subjects`

**Request**:
```json
{
  "candidate_id": 123,
  "exam_year_id": 5,
  "subject_ids": [1, 2, 3, 111],
  "is_principal_map": {"1": true, "2": true, "3": true, "111": false},
  "replace_allocations": false,
  "source": "manual"
}
```

**Response Success**:
```json
{
  "ok": true,
  "message": "Subjects allocated successfully",
  "allocated_subjects": [...],
  "created_count": 4,
  "skipped_count": 0
}
```

**Response Error**:
```json
{
  "ok": false,
  "errors": ["General Studies is mandatory..."],
  "warnings": [],
  "allocated_subjects": []
}
```

### Phase 2.3: Integration & Testing

1. Wire modal form submission
2. Handle API responses
3. Show validation errors in modal
4. Refresh candidate list
5. Test complete workflows

---

## KEY TECHNICAL DECISIONS

### General Studies Identification
- By code: `111`
- By name (case-insensitive): "GENERAL STUDIES"
- Query: `WHERE code = '111' OR LOWER(name) LIKE '%general studies%'`

### Principal Subject Determination
- All subjects EXCEPT General Studies are principal
- Can be overridden per allocation via UI

### Exam Year Context
- Currently loaded from dropdown in modal
- Must exist before allocation
- Links allocation to specific academic year

### Transaction Safety
- Use DB::transaction() for atomic operations
- Prevents orphaned records
- Rollback on validation failure

---

## TESTING CHECKLIST (PHASE 2)

- [ ] Can open allocation modal for SCHOOL candidate
- [ ] Can open allocation modal for PRIVATE candidate
- [ ] Combination dropdown populates correctly
- [ ] Can select combination and preview subjects
- [ ] Can switch to manual mode
- [ ] Can multi-select subjects
- [ ] General Studies is pre-selected or validated
- [ ] Validation error shows for missing GS
- [ ] Validation error shows for <3 principals
- [ ] "Replace allocations" checkbox works
- [ ] Can save allocation
- [ ] API returns success response
- [ ] Subjects appear in candidate's row
- [ ] Allocation persists after page reload
- [ ] is_principal flag set correctly in database
- [ ] source field set to 'manual' or 'template'
- [ ] created_by set to authenticated user
- [ ] Duplicates skipped (add-missing mode)
- [ ] Duplicates replaced (replace mode)
- [ ] Tests pass

---

## NEXT STEPS

1. ✅ Inspect schema (done above)
2. → Implement allocation modal HTML
3. → Implement Alpine.js functions
4. → Create API endpoint
5. → Integration testing
6. → Document any issues

---

## REFERENCES

- Validator: `app/Services/AcseeAllocationValidator.php`
- Models: `app/Models/Candidate.php`, `CandidateSubjectSelection.php`, `Combination.php`, `Subject.php`
- Phase 1 Docs: `docs/necta_acsee_registration_alignment.md`
- Current View: `resources/views/exam-types/acsee.blade.php` (line 162 has button)

---

## CRITICAL CONSTRAINTS (MUST FOLLOW)

✅ NO data deletion  
✅ NO table drops  
✅ Only additive changes  
✅ Backward compatible  
✅ Transactional integrity  
✅ Clear validation messages  
✅ Audit trail (created_by, source)

All constraints maintained from Phase 1 apply here.
