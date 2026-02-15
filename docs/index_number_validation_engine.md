# Index Number Validation Engine (NECTA-Style)

**Status**: Implementation Started  
**Date**: 2026-02-15  

## Current Schema Analysis

### Candidates Table
- **Column**: `candidates.candidate_id` (string, 50 chars, unique)
  - Currently stores registration/index numbers
  - Unique at table level (no exam_year/exam_type scope yet)
  
### School Centre Codes
- **Column**: `schools.registration_number` (string, unique, indexed)
  - Format: S#### (e.g., S0108, S0109)
  - Prefix S = SCHOOL candidate type
  - Added via migration: `2026_02_03_add_registration_number_to_schools.php`

### Exam Context
- **Table**: `candidate_exam_registrations`
  - `exam_year_id` (FK to exam_years)
  - `exam_type_id` (FK to exam_types)
  - Provides exam context for candidates
  - Candidates can register for multiple exams across years

### Candidate Type
- **Column**: `candidates.candidate_type` (enum: SCHOOL | PRIVATE, default: SCHOOL)
  - Added via migration: `2026_02_15_add_necta_alignment_columns.php`
  - Determines:
    - SCHOOL: combination-based, uses schools.registration_number
    - PRIVATE: subject-based, needs private_centre mapping (TODO: confirm table exists)

### Private Centres
- **Status**: NOT YET CONFIRMED
  - Need to find if private_centres table exists
  - Or determine fallback mapping approach

## Index Number Format (NECTA-Style)

Expected format: `CCCC-SSSS`
- **CCCC**: Centre code (e.g., S0445, P0652)
  - First char = Prefix (S=School, P=Private)
  - Next 4 digits = Centre number
- **SSSS**: Serial/candidate number (e.g., 0001-9999)
  - Numeric, fixed 4 digits
- **Delimiter**: Hyphen "-"

## Validation Scope

### Uniqueness Rules
1. **SCHOOL candidates**:
   - Unique per exam_year + exam_type
   - Cannot have duplicate index_number within same exam context
   
2. **PRIVATE candidates**:
   - Unique per exam_year + exam_type
   - Resolved centre = private centre (to be defined)

### Key Validations
1. Format: Must match `^[SP][0-9]{4}-[0-9]{4}$`
2. Centre prefix: S or P
3. Centre resolution: Must exist in schools (S) or private_centres (P)
4. Duplicate detection: Per exam_year + exam_type scope
5. Exam context: Must have exam_year_id and exam_type_id

## Next Steps

1. [x] Document schema findings
2. [ ] Confirm private_centres table existence
3. [ ] Create config/necta.php
4. [ ] Implement IndexNumberValidator service
5. [ ] Add DTOs (ParsedIndexNumber, ValidationResult)
6. [ ] Create safe duplicate-detection migration
7. [ ] Create Artisan command for duplicate scanning
8. [ ] Integrate with CandidateController
9. [ ] Create API endpoint for parsing (Optional UI enhancement)
10. [ ] Tests

## Files to Create/Modify

- `config/necta.php` - Configuration
- `app/Services/IndexNumber/IndexNumberValidator.php` - Main service
- `app/Services/IndexNumber/DTO/ParsedIndexNumber.php` - DTO
- `app/Services/IndexNumber/DTO/ValidationResult.php` - DTO
- `app/Console/Commands/ScanDuplicateIndex.php` - Artisan command
- `database/migrations/XXXX_add_unique_index_to_candidates.php` - Safe migration
- `tests/Feature/IndexNumberValidationTest.php` - Tests
- `app/Http/Controllers/CandidateController.php` - Integration (modify)

## Error Codes (User-Friendly)

| Code | Message | Field |
|------|---------|-------|
| INDEX_EMPTY | Index number cannot be empty | index_number |
| INDEX_FORMAT_INVALID | Invalid format. Use: CCCC-SSSS (e.g., S0445-0001) | index_number |
| CENTRE_CODE_INVALID | Centre code must be 4 digits | index_number |
| CENTRE_PREFIX_UNKNOWN | Unknown centre prefix. Must be S (School) or P (Private) | index_number |
| SERIAL_INVALID | Serial number must be 4 digits | index_number |
| CENTRE_NOT_FOUND | Centre not found in system | index_number |
| DUPLICATE_INDEX_NUMBER | This index number is already registered for this exam | index_number |
| EXAM_CONTEXT_MISSING | Exam year and type required for validation | - |

