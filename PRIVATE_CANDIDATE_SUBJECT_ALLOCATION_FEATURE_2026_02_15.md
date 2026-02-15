# Private Candidate Subject Allocation During Import
**Date:** 2026-02-15  
**Status:** ✅ IMPLEMENTED AND TESTED  
**Feature:** Automatic subject allocation for PRIVATE candidates during Candidate Registration CSV import

---

## Overview

When importing PRIVATE candidates via CSV with a `subjects` column, the system now automatically allocates subjects to those candidates in `candidate_subject_selections`. This populates the "Allocated Subjects" column on `/exam-types/acsee` without requiring manual allocation.

**Key Benefits:**
- Automatic allocation for PRIVATE candidates at import time
- Reuses existing ACSEE validation rules (General Studies mandatory, ≥3 principals)
- Safe: marks/registrations/results never deleted
- Idempotent: reimporting same data doesn't create duplicates
- Replaces allocations only for the imported candidate/exam year

---

## How It Works

### CSV Format

Add a `subjects` column to your Candidate Import CSV with pipe-delimited subject codes or IDs:

```csv
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0001-0001,John Private,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026
P0002-0001,Jane Private,F,TESTSCH,PRIVATE,111|104|121|122,ACSEE,2026
```

**Fields:**
- `subjects`: Pipe-delimited subject codes or IDs
  - Example: `111|102|103|121` (codes)
  - Example: `1|2|3|4` (IDs)
  - General Studies (111) is mandatory
  - Minimum 4 subjects required (GS + ≥3 principals)

### Validation Rules

When allocating subjects, the system enforces NECTA ACSEE rules:

1. **General Studies (111) is mandatory** - Import fails validation if missing
2. **Minimum 3 principal subjects** - Code 111 doesn't count; need ≥3 others
3. **No duplicates** - Duplicate subject codes are automatically deduplicated
4. **All subjects must exist** - Subject codes/IDs must match database records

**Example:**
```csv
# VALID: 4 subjects (1 GS + 3 principals)
111|102|103|121

# INVALID: Missing GS
102|103|121|122

# INVALID: Only 2 principals (need 3)
111|102|103

# VALID: 5 subjects (1 GS + 4 principals)
111|102|103|121|122
```

### Process Flow

1. **Candidate Import (Phase 1 - Validate)**
   - Parse CSV including `subjects` column
   - Validate candidate data
   - Validation report shows: created, updated, skipped candidates

2. **Candidate Import (Phase 2 - Commit)**
   - Create/update candidates
   - For each PRIVATE candidate with `subjects`:
     a. Parse subject codes from `subjects` column (split by `|`)
     b. Resolve subject IDs from codes
     c. Validate using AcseeAllocationValidator
     d. If valid: delete old allocations, insert new ones
     e. If invalid: log error, skip allocation (don't fail import)
   - Update `candidate_subject_selections` table

3. **Result Report**
   - Shows: imported count, updated count, skipped count
   - New: `allocations_created_count`, `allocations_updated_count`
   - Includes: `allocation_errors` array for debugging

---

## Import Response Format

```json
{
  "success": true,
  "message": "Imported 5 candidates, allocated subjects for 5",
  "imported_count": 5,
  "skipped_count": 0,
  "updated_count": 0,
  "allocations_created_count": 24,
  "allocations_updated_count": 0,
  "errors": [],
  "allocation_errors": []
}
```

**New Fields:**
- `allocations_created_count` - Number of subject allocations created
- `allocations_updated_count` - Number updated (replace mode only)
- `allocation_errors` - Array of allocation validation errors (per candidate)

---

## Implementation Details

### Code Changes

**File:** `app/Services/Candidates/CandidateImportService.php`

**New Method: `allocateSubjectsForPrivateCandidate()`**
```php
private function allocateSubjectsForPrivateCandidate(
    Candidate $candidate,
    string $subjectsStr,
    ExamType $examType,
    ExamYear $examYear,
    &$errors = []
): int
```

- Accepts pipe-delimited subject codes or IDs
- Uses `AcseeAllocationValidator` for NECTA compliance
- Returns count of allocations created
- Errors logged but don't fail import

**Updated Method: `processBatch()`**
- Now handles both SCHOOL (combination-based) and PRIVATE (subject-based) candidates
- Calls `allocateSubjectsForPrivateCandidate()` for PRIVATE candidates with `subjects`

**Updated Method: `commitImport()`**
- Tracks `$allocationsCreated` counter
- Returns allocation statistics in response

### Database Behavior

**Table:** `candidate_subject_selections`

**On Import:**
- Old allocations for candidate+exam_type+exam_year deleted (safe: marks/results on other tables)
- New allocations inserted with:
  - `source = 'import'` (audit trail)
  - `created_by = Auth::id()` (who imported)
  - `is_principal = true` for subjects 102+ (false for GS/111)
  - `is_active = true`

**Unique Constraint:**
- Database has unique key on (candidate_id, exam_type_id, exam_year_id, subject_id)
- Prevents duplicates across reimports
- Safe for idempotent reimports in SKIP mode

---

## Safety Guarantees

### Data Not Deleted:
✅ Marks (in `subject_marks` table)  
✅ Exam registrations (in `candidate_exam_registrations`)  
✅ Results (in result-related tables)  
✅ Candidate records

### What Gets Replaced:
⚠️ Only: `candidate_subject_selections` for that candidate+exam_type+exam_year  
⚠️ Not affected: Other exam years, other exam types

### Idempotency:
- **SKIP mode (default):** Reimporting same CSV = no changes (candidate already exists)
- **REPLACE mode:** Reimporting updates candidate name/school, reallocates subjects
- Unique constraints prevent duplicate subject selections

---

## Error Handling

### Allocation Errors (Non-Fatal)
If allocation fails for a row, the **candidate is still imported**. Only the allocation is skipped.

**Example:**
```csv
P0001-0001,John,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026  # ✓ Imported, allocated
P0002-0001,Jane,F,TESTSCH,PRIVATE,102|103,ACSEE,2026          # ✓ Imported, NO allocation (<3 principals)
```

Result:
```
imported_count: 2
allocations_created_count: 4
allocation_errors: ["Candidate P0002-0001: Minimum 3 principal subjects required..."]
```

### Validation Errors (Fatal)
Candidate data validation errors (missing ID, invalid gender, etc.) **prevent import** of that row.

---

## Testing

**Test File:** `tests/Feature/CandidateImportSubjectAllocationTest.php`

**Tests Included:**

1. ✅ `test_private_candidate_with_subjects_gets_allocated`
   - Verify PRIVATE candidate gets allocations created

2. ✅ `test_missing_general_studies_validation_fails`
   - Verify GS 111 is mandatory

3. ✅ `test_insufficient_principal_subjects_validation_fails`
   - Verify minimum 3 principals required

4. ✅ `test_idempotency_reimport_does_not_duplicate`
   - Verify reimport doesn't create duplicates

5. ✅ `test_replace_mode_reallocates_subjects`
   - Verify REPLACE mode updates allocations

6. ✅ `test_subject_codes_and_ids_both_supported`
   - Verify codes (111) and IDs (1) both work

7. ✅ `test_school_candidate_without_subjects_works`
   - Verify SCHOOL candidates unaffected

8. ✅ `test_marks_not_deleted_during_allocation`
   - Verify marks remain safe after allocation

**Run Tests:**
```bash
php artisan test tests/Feature/CandidateImportSubjectAllocationTest.php
```

---

## Usage Examples

### Example 1: Import PRIVATE Candidates with Subjects

**CSV File:**
```csv
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0001-0001,John Private,M,SCH001,PRIVATE,111|102|103|121,ACSEE,2026
P0002-0001,Jane Private,F,SCH001,PRIVATE,111|104|121|122,ACSEE,2026
```

**API Call:**
```bash
curl -X POST http://localhost:8000/api/candidates/import/commit \
  -H "X-CSRF-TOKEN: {token}" \
  -F "file=@candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

**Response:**
```json
{
  "success": true,
  "message": "Imported 2 candidates, allocated subjects for 2",
  "imported_count": 2,
  "allocations_created_count": 8,
  "allocation_errors": []
}
```

### Example 2: Replace Mode with Subject Updates

**First Import:**
```csv
candidate_id,full_name,gender,school_code,candidate_type,subjects
P0003-0001,John Original,M,SCH002,PRIVATE,111|102|103|121
```

**Second Import (new subjects):**
```csv
candidate_id,full_name,gender,school_code,candidate_type,subjects
P0003-0001,John Updated,M,SCH002,PRIVATE,111|104|121|122
```

**With `on_exists_mode=replace`:**
- Candidate name updated: "John Original" → "John Updated"
- Subjects replaced: `{102,103,121}` → `{104,121,122}`
- Allocations deleted and recreated

---

## "Allocated Subjects" Column Population

After import, the Candidates table on `/exam-types/acsee` shows allocated subjects.

**Query Used (in blade template):**
```blade
@foreach($candidate->subjectSelections as $selection)
    {{ $selection->subject->code ?? '' }}
@endforeach
```

**Works Because:**
- Import populates `candidate_subject_selections` table
- Blade template queries this relationship
- Column automatically populated on page load/refresh

---

## Backward Compatibility

✅ **No breaking changes**

- CSV import works WITHOUT `subjects` column (SCHOOL candidates)
- Existing SCHOOL candidate imports unaffected
- API response includes new fields (allocation_* counts)
- Old integrations continue to work (new fields are optional/additive)

---

## Future Enhancements

Potential future improvements:

1. Add UI toggle: "Auto-allocate subjects" during import
2. Support comma-delimited subjects (e.g., "111,102,103,121")
3. Batch API endpoint for subject allocation without candidate import
4. Dry-run preview showing which candidates will get allocations
5. Optional email report with allocation summary

---

## Troubleshooting

**Q: Allocations not showing on /exam-types/acsee?**
- A: Refresh browser. Allocations are in DB, may be cached in frontend.

**Q: "General Studies is mandatory" error?**
- A: Add code 111 to your subjects list: `111|102|103|121`

**Q: "Minimum 3 principal subjects required"?**
- A: General Studies doesn't count. You need GS + 3 other subjects minimum.
  - ✓ Valid: `111|102|103|121` (4 total = 1 GS + 3 principal)
  - ✗ Invalid: `111|102|103` (3 total = 1 GS + 2 principal)

**Q: Subject codes don't match?**
- A: Check Subject codes in admin panel. Common codes:
  - `111` = General Studies
  - `102` = Math, `103` = Physics, `104` = Chemistry
  - `121` = English, `122` = Swahili

**Q: Marks deleted after allocation?**
- A: No. Marks are safe. Only `candidate_subject_selections` is modified.

---

## Deliverables Checklist

✅ CandidateImportService updated  
✅ allocateSubjectsForPrivateCandidate() method added  
✅ processBatch() updated for subject allocation  
✅ commitImport() returns allocation statistics  
✅ Tests created (8 test cases)  
✅ Documentation complete  
✅ No breaking changes  
✅ Backward compatible  

---

## Summary

The Candidate Import feature now supports automatic subject allocation for PRIVATE candidates. Simply include a `subjects` column in your CSV with pipe-delimited subject codes, and allocations will be created automatically during import. All existing validation rules (GS mandatory, ≥3 principals) are enforced, and marks/results are never deleted. The feature is safe, tested, and production-ready.
