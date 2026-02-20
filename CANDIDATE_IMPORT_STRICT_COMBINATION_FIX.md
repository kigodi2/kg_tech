# Candidate Import - Strict Combination Handling Fix

**Date:** 2026-02-17  
**Status:** Complete and Ready for Production

## Problem Statement

Previously, candidate CSV imports allowed silent overwrites and fuzzy-matching of subject combinations. This caused:

- **Silent mapping failures**: CSV combination codes were sometimes guessed or ignored.
- **Data inconsistency**: `Candidate.combination` and `Candidate.combination_id` could diverge.
- **Post-processing mutations**: Background jobs and scripts could overwrite imported combinations after commit.

## Solution Overview

Implemented **strict, deterministic combination handling** across all import entry points with integrity guards to prevent post-save mutations.

### Key Changes

#### 1. **Core Import Service** (`CandidateImportService.php`)

- **Exact Code Matching**: Combination codes are normalized to `UPPER(TRIM())` and matched exactly against the `combinations` table.
- **Both Fields Persisted**: Both `combination` (code string) and `combination_id` (FK) are set atomically.
- **Post-Save Integrity Guard**: After creating/updating a candidate, the service verifies that saved values match the CSV values. If they differ, the transaction is rolled back.
- **No Fallbacks or Guessing**: Subject parsing and fuzzy name matching have been removed.

#### 2. **Bulk Import Job** (`ProcessCandidateBulkImport.php`)

- Uses normalized combination codes and persists both `combination` and `combination_id`.
- Includes post-create integrity guard.

#### 3. **API Endpoint** (`routes/api.php` — `/api/candidates/import`)

- Normalizes combination, resolves `combination_id`, and avoids silent overwrites.
- In **skip mode**: existing `combination` is never touched.
- In **replace mode**: combination is updated only on explicit user action.
- Integrity guards on both create and update paths.

#### 4. **Linking Controller** (`Results/LinkingController.php`)

- When auto-assigning default combinations, both `combination` and `combination_id` are set together.

#### 5. **Candidate Controller** (`CandidateController.php`)

- Added verification check after candidate creation to ensure the record was successfully persisted.

#### 6. **Import Modal UI** (`registration/candidates.blade.php`)

- Extended preview table with new columns:
  - **CSV Combination**: The raw value from the CSV file.
  - **Resolved Combination**: The matched combination code (with success badge if found).
  - **Error Details**: Inline error messages if combination could not be resolved.
- Users can now verify mappings before committing the import.

### Import Workflow

```
CSV Upload → Validate (Phase 1)
  ↓
  • Normalize combination codes (UPPER, TRIM)
  • Look up exact match in combinations table
  • Store csv_combination and resolved_combination in preview
  • Show preview with errors (if any)
  ↓
User Reviews → Commit (Phase 2)
  ↓
  • Create/update candidates with both combination + combination_id
  • Run post-save integrity guard
  • Register for ACSEE with exact combination code
  ↓
Import Complete
```

### Error Detection

If a combination code from the CSV cannot be found in the database:

- Row is marked **ERROR** status.
- Error message is displayed in the preview.
- Row is excluded from import.

If an imported combination differs from saved combination:

- Integrity guard detects the mismatch.
- Transaction is rolled back.
- User sees a detailed error message.

## Background Job Safety

**Safe (read-only):**

- `app/Observers/CandidateExamRegistrationObserver.php` — reads `candidate->combination` but does NOT mutate it.
- Standalone scripts (`sync_combination_allocated_subjects.php`, `register_candidate_subjects.php`) — read-only.

**Hardened (no silent overwrites):**

- `ProcessCandidateBulkImport.php` — persists both fields; integrity guard on create.
- Linking auto-assign — sets both fields together.

## Testing

### Unit Tests

Run the new feature tests to verify all scenarios:

```bash
php artisan test tests/Feature/CandidateImportTest.php
```

Tests included:

- ✅ Import creates candidate with exact combination
- ✅ Import fails on unknown combination
- ✅ Replace mode updates combination to CSV value
- ✅ Skip mode leaves existing combination unchanged

### Manual Import Test

1. Create a CSV with your test candidates (include valid combination codes like PCM, PCB, HGE).
2. Navigate to `/registration/candidates`.
3. Click "Import Candidates".
4. Upload your CSV and validate.
5. Review the preview table:
   - Verify CSV Combination column shows your codes.
   - Verify Resolved Combination column shows matched codes (or error).
6. Commit the import.
7. Query the database to confirm both `combination` and `combination_id` are set correctly.

## Deployment Checklist

- [x] Service logic updated (exact matching, integrity guards).
- [x] API endpoint hardened (normalize, no silent overwrites).
- [x] Controllers updated (Linking, CandidateController).
- [x] Bulk import job aligned (strict combination handling).
- [x] UI preview table extended (show CSV and resolved combination).
- [x] Feature tests written and passing.
- [x] Background jobs audited (safe combinations reference).

## Breaking Changes

**None.** The changes are backward compatible:

- Existing API parameters still work.
- The import workflow respects `on_exists_mode` (skip, replace).
- No database schema changes.

## Documentation References

- [CandidateImportService](app/Services/Candidates/CandidateImportService.php) — Core validation and commit logic.
- [Routes API](routes/api.php#L284) — Import endpoints.
- [CandidateImportTest](tests/Feature/CandidateImportTest.php) — Feature test suite.
- [Blade Modal](resources/views/registration/candidates.blade.php#L2162) — Import preview UI.

## Questions?

For questions or issues, please refer to the test suite or contact the development team.
