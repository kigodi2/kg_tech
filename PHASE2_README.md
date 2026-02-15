# PHASE 2: ACSEE PRIVATE vs SCHOOL CSV IMPORT + TEMPLATE GENERATOR

**Status**: ✅ STUDY COMPLETE — Ready for implementation  
**Date**: 2026-02-15  
**Effort**: 21 hours (3 developer-days)  
**Confidence**: HIGH

---

## QUICK START

### 📖 Read First (in order):
1. **PHASE2_STUDY_SUMMARY.txt** (5 min) — Executive summary
2. **PHASE2_QUICK_REFERENCE.md** (10 min) — Key findings + CSV formats
3. **docs/PHASE2_IMPORT_EXISTING_BEHAVIOR.md** (15 min) — Complete system analysis
4. **PHASE2_IMPLEMENTATION_PLAN.md** (20 min) — Detailed specification

### 🎯 Key Deliverables:
- ✅ CSV templates (SCHOOL + PRIVATE) — downloadable
- ✅ Candidate type filter in modal UI
- ✅ Bulk CSV allocation import (two-phase)
- ✅ Import report with error details
- ✅ All tests passing
- ✅ Zero data loss

---

## DOCUMENTS IN THIS STUDY

| Document | Purpose | Time |
|----------|---------|------|
| **PHASE2_STUDY_SUMMARY.txt** | Executive summary, key findings | 5 min |
| **PHASE2_QUICK_REFERENCE.md** | Key findings, CSV formats, validation rules, gotchas | 10 min |
| **docs/PHASE2_IMPORT_EXISTING_BEHAVIOR.md** | Complete system analysis, database schema, workflows | 30 min |
| **PHASE2_IMPLEMENTATION_PLAN.md** | Detailed specification, endpoints, services, UI changes | 30 min |
| **PHASE2_STUDY_FILES.txt** | Index of all documents + existing code references | 5 min |
| **PHASE2_README.md** | This file — quick navigation | 2 min |

---

## CSV FORMATS (FINAL)

### SCHOOL Candidate Allocation CSV
```csv
exam_year,index_number,combination_code,replace_allocations
2026,S0445-0001,PCB,NO
2026,S0445-0002,HGL,NO
```

### PRIVATE Candidate Allocation CSV
```csv
exam_year,index_number,subject_codes,replace_allocations
2026,P0652-0501,111|112|123|145,NO
2026,P0652-0502,111|001|002|003,NO
```

---

## KEY FINDINGS

### ✅ Existing System Strengths
- Two-phase import (validate → commit) = non-destructive
- AcseeAllocationValidator = reusable for rules
- CandidateSubjectSelection with unique constraint = idempotent
- candidate_type column = already distinguishes SCHOOL vs PRIVATE
- Allocation modal = can be extended with bulk import

### ❌ What's Missing
- CSV allocation import (only single-candidate modal exists)
- Candidate type filter in modal
- Bulk CSV templates
- CSV parsing for PRIVATE subject codes
- Import report display

### ✏️ Implementation Strategy
- Reuse AcseeAllocationValidator (validation rules)
- Follow CandidateImportService pattern (CSV parsing)
- Follow two-phase pattern (validate + commit)
- Use updateOrCreate() for idempotency
- Extend existing allocation modal (don't duplicate)

---

## VALIDATION RULES (Reusable)

```php
// Use existing validator
$validator = new AcseeAllocationValidator();
$result = $validator->validate(
    $candidate,
    $examTypeId,
    $examYearId,
    [$subjectId1, $subjectId2, ...]  // Array of IDs
);

// Returns: {ok, errors, warnings, principal_subject_ids, all_subject_ids}
```

**Rules**:
1. General Studies (code 111) is mandatory
2. Minimum 3 principal subjects (excluding GS)
3. No duplicates (auto-removed with warning)

---

## ENDPOINTS TO CREATE

```
GET /api/exam-types/acsee/templates/school-allocation.csv
GET /api/exam-types/acsee/templates/private-allocation.csv
POST /api/exam-types/acsee/allocate-from-csv
```

Request payload:
```json
{
    "file": <File>,
    "exam_year_id": 45,
    "candidate_type_filter": "ALL|SCHOOL|PRIVATE",
    "replace_allocations_default": false
}
```

Response:
```json
{
    "success": true,
    "total_rows": 10,
    "success_count": 8,
    "failed_count": 2,
    "errors": [
        {
            "row_number": 3,
            "index_number": "S0445-0003",
            "error_messages": ["Combination INVALID not found"]
        }
    ]
}
```

---

## SERVICES TO CREATE

### AcseeAllocationTemplateService
```php
public function generateSchoolTemplate(): string {}
public function generatePrivateTemplate(): string {}
```

### AcseeAllocationCSVImporter
```php
public function validateCSV(file, examYearId, candidateTypeFilter): array {}
public function commitImport(file, examYearId, candidateTypeFilter, replaceDefault): array {}

private function matchCandidate(indexNumber, examYearId): ?Candidate {}
private function allocateSubjectsForSchool(Candidate, combinationCode): array {}
private function allocateSubjectsForPrivate(Candidate, subjectCodes): array {}
```

---

## MODAL UI CHANGES

Add to allocation modal:

1. **Candidate Type Filter**
   ```
   Dropdown: ALL | SCHOOL | PRIVATE
   ```

2. **Bulk Allocation Section**
   - File upload
   - Template downloads (×2)
   - Exam year selector
   - Replace checkbox
   - Import button
   - Report display (summary + errors)
   - Download error CSV button

---

## SAFEGUARDS

- ✅ Phase 1 (validate) is read-only
- ✅ Phase 2 (commit) requires user confirmation
- ✅ Replace mode is opt-in (defaults to add-only)
- ✅ Unique constraint prevents duplicates
- ✅ Idempotent (re-import is safe)
- ✅ Detailed error reporting
- ✅ Downloadable error CSV
- ✅ No schema changes
- ✅ No breaking changes
- ✅ All changes are additive

---

## IMPLEMENTATION STEPS

### Phase 2a: Backend Setup (Day 1)
- [ ] Create AcseeAllocationTemplateService
- [ ] Create AcseeAllocationCSVImporter
- [ ] Create AcseeAllocationController
- [ ] Create routes
- [ ] Write unit tests

### Phase 2b: Frontend UI (Day 2)
- [ ] Update allocation modal
- [ ] Add candidate type filter
- [ ] Add bulk allocation section
- [ ] Add Alpine functions
- [ ] Write frontend tests

### Phase 2c: Integration & Testing (Day 3)
- [ ] Full integration testing
- [ ] Edge case testing
- [ ] User acceptance testing
- [ ] Documentation

---

## EFFORT ESTIMATE

| Component | Hours |
|-----------|-------|
| Backend Services | 4 |
| Endpoints | 2 |
| Modal UI | 4 |
| Tests | 6 |
| Integration | 3 |
| Documentation | 2 |
| **Total** | **21 hours** |

---

## INTEGRATION POINTS (Reuse Existing)

| Component | Location | Use For |
|-----------|----------|---------|
| AcseeAllocationValidator | app/Services/AcseeAllocationValidator.php | Validation rules |
| CandidateImportService | app/Services/Candidates/CandidateImportService.php | CSV parsing pattern |
| updateOrCreate() | Eloquent ORM | Idempotency |
| Two-phase pattern | CandidateImportController | Validate + commit |
| ExamTypeController | app/Http/Controllers/ExamTypeController.php | Candidate listing |

---

## NON-DESTRUCTIVE DESIGN

All safeguards in place:
- Validation before insert
- Idempotent operations (no duplicates)
- Two-phase workflow (user confirmation)
- Replace mode is explicit (not default)
- Error reporting (per-row)
- Reversible (data not deleted by default)

---

## BACKWARD COMPATIBILITY

✅ Fully backward compatible:
- No schema changes
- No breaking changes to existing endpoints
- Existing single-candidate allocation unchanged
- Existing import flows unchanged
- Only additive features

---

## SUCCESS CRITERIA

- [ ] CSV templates downloadable
- [ ] Candidate type filter working
- [ ] Bulk import endpoint working
- [ ] Report display working
- [ ] All tests passing
- [ ] Zero data loss
- [ ] User documentation complete

---

## COMMON GOTCHAS

1. **Index Number Matching**  
   CSV: `index_number` (e.g., "S0445-0001")  
   DB: `candidate_id` column (same thing!)

2. **Subject Codes vs IDs**  
   CSV: codes (e.g., "111", "001")  
   DB: IDs (e.g., 1, 2, 3) — must resolve!

3. **Combination Codes vs Subject Codes**  
   SCHOOL CSV: combination code (e.g., "PCB")  
   PRIVATE CSV: subject codes (e.g., "111|001|002")  
   Don't mix them up!

4. **General Studies (111)**  
   Mandatory for ALL ACSEE candidates  
   Must be in subject list (PRIVATE) or combination (SCHOOL)  
   Marked as non-principal (is_principal = false)

5. **Exam Year ID vs Year Label**  
   UI dropdown: year_label (e.g., "2026")  
   API: exam_year_id (database ID)  
   CSV: exam_year (4-digit, must resolve to ID)

---

## REFERENCE BY TOPIC

**Understanding the System**:
→ docs/PHASE2_IMPORT_EXISTING_BEHAVIOR.md

**CSV Formats**:
→ PHASE2_QUICK_REFERENCE.md (section "CSV FORMATS")

**Validation Rules**:
→ PHASE2_QUICK_REFERENCE.md (section "VALIDATION RULES")

**Implementation Plan**:
→ PHASE2_IMPLEMENTATION_PLAN.md

**Quick Lookup**:
→ PHASE2_QUICK_REFERENCE.md

**File Index**:
→ PHASE2_STUDY_FILES.txt

**Executive Summary**:
→ PHASE2_STUDY_SUMMARY.txt

---

## NEXT STEPS

1. ✅ Read PHASE2_STUDY_SUMMARY.txt (overview)
2. ✅ Read PHASE2_QUICK_REFERENCE.md (key findings)
3. ✅ Read docs/PHASE2_IMPORT_EXISTING_BEHAVIOR.md (context)
4. → Start Phase 2a (Backend Services)

---

## SUPPORT

All study documents are self-contained and cross-referenced:
- Navigation guides included
- Index provided (PHASE2_STUDY_FILES.txt)
- Gotchas documented (PHASE2_QUICK_REFERENCE.md)
- Implementation spec detailed (PHASE2_IMPLEMENTATION_PLAN.md)

**Ready for implementation. High confidence in success.**

---

**Study Date**: 2026-02-15  
**Study Author**: Senior Laravel 10 + Alpine.js Engineer  
**Study Status**: COMPLETE ✅
