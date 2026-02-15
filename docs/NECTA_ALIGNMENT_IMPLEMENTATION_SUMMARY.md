# NECTA Alignment Implementation - Summary & Architecture

## Overview
This document provides a high-level summary of the NECTA ACSEE alignment implementation in IRMS, including where components live, how they interact, and what's working.

---

## Key Components

### 1. Index Number Validation Engine
**Purpose:** Parse and validate NECTA-format index numbers (CCCC-SSSS)

| Component | Location | Purpose |
|-----------|----------|---------|
| **IndexNumberValidator** | `app/Services/IndexNumber/IndexNumberValidator.php` | Main validator service; parses and validates index numbers |
| **ParsedIndexNumber DTO** | `app/Services/IndexNumber/DTO/ParsedIndexNumber.php` | Data structure for parsed index number (centre_code, prefix, serial, candidate_type) |
| **ValidationResult DTO** | `app/Services/IndexNumber/DTO/ValidationResult.php` | Validation result with errors, warnings, resolved IDs |
| **NECTA Config** | `config/necta.php` | Regex patterns, prefix mappings, error messages |

**Validation Rules:**
- ✅ Prefix S or P only (auto-detects SCHOOL vs PRIVATE)
- ✅ 4-digit centre code (e.g., 0445, 0652)
- ✅ 4-digit serial (e.g., 0001, 0502)
- ✅ Centre must exist in schools table (matching registration_number)
- ✅ Duplicate detection per exam context (exam_year + exam_type)

**Auto-Detection:**
- S prefix → candidate_type = SCHOOL
- P prefix → candidate_type = PRIVATE

---

### 2. Candidate Registration
**Purpose:** Register candidates (SCHOOL and PRIVATE) for ACSEE exams

| Component | Location | Role |
|-----------|----------|------|
| **Registration UI** | `resources/views/registration/candidates.blade.php` | Modal form for manual registration |
| **CandidateController** | `app/Http/Controllers/CandidateController.php` | HTTP handler; validates and creates candidates |
| **API Endpoints** | `routes/web.php` (lines 589–750) | RESTful API for CRUD operations |
| **Candidate Model** | `app/Models/Candidate.php` | ORM model with relations to schools, exams, subjects |

**Fields:**
- `candidate_id` (string) — NECTA index number (e.g., P0652-0501)
- `candidate_type` (enum) — SCHOOL or PRIVATE (auto-set or manually selected)
- `school_id` (FK) — School affiliation (required even for private candidates)
- `combination_id` (FK) — Optional; used for school candidates only
- `exam_type` (string) — PSLE, CSEE, or ACSEE

**Registration Flow:**
1. Operator fills form with candidate info
2. System validates index number (if exam_type = ACSEE)
3. Auto-detects candidate_type from prefix
4. Creates candidate record
5. Registers for ACSEE (if selected)

---

### 3. Subject Allocation for ACSEE
**Purpose:** Assign subjects to candidates following NECTA rules

#### A. Validator
| Component | Location | Function |
|-----------|----------|----------|
| **AcseeAllocationValidator** | `app/Services/AcseeAllocationValidator.php` | Core validation logic for NECTA rules |

**NECTA Rules:**
- ✅ General Studies (code 111) is mandatory
- ✅ Minimum 3 principal subjects (excluding GS)
- ✅ No duplicate subject allocations

**Error Messages:**
- `"General Studies (code 111) is mandatory for ACSEE candidates"`
- `"Minimum 3 principal subjects required (found X)"`
- `"General Studies subject not configured in system"`

#### B. Allocation API
| Component | Location | Endpoint |
|-----------|----------|----------|
| **API Handler** | `routes/web.php` (lines 1366–1460) | POST `/api/exam-types/acsee/allocate-subjects` |

**Request Payload:**
```json
{
    "candidate_id": 123,
    "exam_year_id": 1,
    "subject_ids": [111, 1, 2, 3, 4],
    "is_principal_map": { "111": false, "1": true, "2": true, "3": true, "4": true },
    "replace_allocations": false,
    "source": "manual"
}
```

**Response:**
```json
{
    "ok": true,
    "message": "Subjects allocated successfully",
    "allocated_subjects": [
        { "id": 111, "code": "111", "name": "General Studies", "is_principal": false },
        { "id": 1, "code": "001", "name": "Mathematics", "is_principal": true },
        ...
    ]
}
```

#### C. UI Modal
| Component | Location | Feature |
|-----------|----------|---------|
| **ACSEE View** | `resources/views/exam-types/acsee.blade.php` | Exam type dashboard with allocation modal |
| **Allocation Modal** | Lines 306–450 | Two modes: Template (school) or Manual (private) |
| **Alpine.js** | Lines 945–1130 | Modal logic, validation, API calls |

**Modes:**
1. **Template Mode** — Select a combination; auto-populate subjects (for SCHOOL candidates)
2. **Manual Mode** — Manually check subjects (for PRIVATE candidates)

---

### 4. Database Schema
**Migration:** `database/migrations/2026_02_15_add_necta_alignment_columns.php`

#### candidates table
```sql
- candidate_type (enum: SCHOOL, PRIVATE) — Added
- combination_id (FK to combinations) — Added
```

#### candidate_subject_selections table
```sql
- is_principal (boolean) — Added; true if principal subject
- source (enum: manual, import, template) — Added; tracks allocation method
- created_by (FK to users) — Added; who allocated this subject
```

---

## Workflow Examples

### Example 1: Register & Allocate Private Candidate
```
1. Operator navigates to /registration/candidates
2. Clicks "Register" button
3. Fills form:
   - Index Number: P0652-0501 (auto-detected as PRIVATE)
   - Name: John Doe
   - Sex: Male
   - School: Private Centre ABC (P0652)
   - Exam Type: ACSEE
   - Exam Year: 2026
4. Clicks "Register Candidate"
5. Candidate created with candidate_type = PRIVATE
6. Operator navigates to EXAM TYPE → ACSEE
7. Finds "John Doe" in candidates list
8. Clicks edit icon → Opens allocation modal
9. Switches to "Manual Subject Selection" mode
10. Selects:
    - ☑ General Studies (111)
    - ☑ Mathematics (1)
    - ☑ Physics (2)
    - ☑ Chemistry (3)
11. Clicks "Save Allocation"
12. Subjects saved to candidate_subject_selections table
    - source = 'manual'
    - created_by = [current user ID]
    - is_principal = true for Math, Physics, Chemistry; false for GS
```

### Example 2: School Candidate with Template
```
1. Operator registers SCHOOL candidate with index S0445-0001
2. Candidate created with candidate_type = SCHOOL, combination = "PCM"
3. Operator opens allocation modal in ACSEE
4. Uses "Apply Combination Template" mode
5. Selects "PCM - Physics, Chemistry, Math" template
6. Subjects auto-selected (including GS)
7. Clicks "Save Allocation"
8. Subjects saved with source = 'template'
```

---

## Known Gaps

### Gap 1: CSV Bulk Import for Private Candidates (HIGH PRIORITY)
- **Location:** `app/Services/Candidates/CandidateImportService.php` line 497
- **Issue:** Code tries to set `district_id` field that doesn't exist
- **Impact:** Private candidate CSV import fails
- **Fix:** See `docs/NECTA_PRIVATE_CANDIDATE_GAPS.md`

### Gap 2: Private Centre Model Not Implemented (LOW PRIORITY)
- **Location:** Config expects optional PrivateCentre model
- **Issue:** Currently uses schools table with P-prefix registration numbers
- **Impact:** Works fine; optional future enhancement
- **Status:** Code has fallback; no action needed

---

## Testing Guide

### Unit Tests
- `tests/Unit/Services/AcseeAllocationValidatorTest.php` — Validator logic
- Test: GS mandatory, ≥3 principals, no duplicates

### Manual Testing
1. Register a SCHOOL candidate with S-prefix
2. Register a PRIVATE candidate with P-prefix
3. Verify candidate_type auto-detected correctly
4. Open allocation modal
5. Try Manual mode with invalid selections (missing GS, <3 principals)
6. Verify error messages appear
7. Fix selections and save
8. Query database to verify records in candidate_subject_selections

### Integration Testing
- CSV import with school candidates (works)
- CSV import with private candidates (fails before fix)
- Bulk candidate import via UI (works)
- Subject allocation via API (works)
- Permission checks (authorization working)

---

## Performance Notes

- **Index number validation:** O(1) — direct DB lookups
- **Subject allocation:** O(n) where n = number of subjects — transaction-safe
- **Duplicate detection:** O(1) with indexed queries
- **Bulk import:** O(n) with batch processing

---

## Security & Audit Trail

- **Authorization:** CandidateController checks user scope (school-level permissions)
- **Audit Logging:** GovernanceAuditLog tracks registration events
- **Source Tracking:** candidate_subject_selections.source tracks allocation method
- **User Attribution:** candidate_subject_selections.created_by identifies who allocated subjects

---

## Configuration Reference

**File:** `config/necta.php`

```php
return [
    'index_number' => [
        'delimiter' => '-',
        'centre_prefix_map' => [
            'S' => 'SCHOOL',
            'P' => 'PRIVATE',
        ],
        'centre_code_regex' => '^[SP][0-9]{4}$',
        'serial_regex' => '^[0-9]{4}$',
        'full_pattern' => '^[SP][0-9]{4}-[0-9]{4}$',
    ],
    'validation' => [
        'enforce_known_centre' => true,
        'enforce_unique_per_exam_context' => true,
        'school_centre_column' => 'registration_number',
    ],
    'error_codes' => [
        // ... error messages
    ],
];
```

---

## Related Documentation

- **Operator Guide:** `docs/NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md` ← START HERE
- **Technical Gaps:** `docs/NECTA_PRIVATE_CANDIDATE_GAPS.md`
- **Index Number Validator Tests:** `tests/Feature/IndexNumberValidationTest.php`
- **ACSEE Allocation Tests:** `tests/Unit/Services/AcseeAllocationValidatorTest.php`

---

## Support & Troubleshooting

### Common Issues
- **"Centre not found"** → Register private centre in SETTINGS → Schools with registration_number = P####
- **"General Studies missing"** → Check GS checkbox in allocation modal
- **CSV import fails** → Check Gap #1 fix; ensure school_code exists

### Contact
- **Operator Questions:** See NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, Section 5 (Troubleshooting)
- **Developer Issues:** Check NECTA_PRIVATE_CANDIDATE_GAPS.md for known issues
- **Bug Reports:** Include candidate_id, exam_year, error message, and browser console output

---

**Version:** 1.0  
**Last Updated:** 2026-02-15  
**Status:** Production Ready (with known Gap #1)
