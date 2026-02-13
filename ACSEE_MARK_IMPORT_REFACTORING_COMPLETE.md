# ACSEE CSV Marks Import Refactoring - COMPLETE

**Status:** ✅ COMPLETE  
**Date:** 2026-01-31  
**Scope:** Remove combination selection from mark entry workflow  

---

## EXECUTIVE SUMMARY

The ACSEE CSV marks import module has been refactored to **remove all combination selection from the user workflow**. Combination is now:
- **Derived dynamically** from candidate registration data
- **Never stored** in the raw marks staging table
- **Validated internally** during CSV processing
- **Not required** as a UI input

This ensures:
- ✅ Simpler, error-proof UI/UX for data entry officers
- ✅ Correct validation of subject-combination relationships
- ✅ Scalability across schools with different candidate combinations
- ✅ Prevention of invalid subject assignments

---

## CHANGES BREAKDOWN

### 1. BACKEND - REQUEST VALIDATION & CONTROLLER

#### File: `app/Http/Controllers/MarkEntryController.php`

**Changes:**
- ❌ Removed `getCombinations()` method (no longer needed)
- ❌ Removed `combination_id` from `downloadTemplate()` request validation
- ❌ Removed `combination_id` from `uploadMarks()` request validation
- ✅ Added legacy UI protection: rejects `combination_id` if passed in request
- ❌ Removed `combination` relationship from `getBatchDetails()` eager load
- ❌ Removed unused `Combination` model import

**Impact:**
- `POST /mark-entry/acsee/upload` now accepts ONLY:
  - `exam_year` (required)
  - `school_id` (required)
  - `subject_id` (required)
  - `file` (required)
- Any attempt to pass `combination_id` returns HTTP 422 with clear error message

---

### 2. BACKEND - VALIDATION SERVICE (CRITICAL)

#### File: `app/Services/MarkImport/MarkValidationService.php`

**Changes:**
- ✅ Added `getCandidateCombination()` method that:
  1. Retrieves candidate's exam registration for the year
  2. Gets candidate's selected subjects for ACSEE
  3. Finds the combination containing ALL those subjects
  4. Returns the matched combination (or null if not found)
- ✅ Updated `validateRawMark()` to:
  1. Verify candidate is registered for ACSEE in the year
  2. Call `getCandidateCombination()` to derive combination dynamically
  3. Validate subject belongs to the derived combination
  4. Return clear error: "Subject '{code}' is not registered under candidate's ACSEE combination"

**Combination Derivation Logic:**
```
For each CSV row:
  1. Find candidate by index_number
  2. Get candidate's ACSEE registration for exam_year
  3. Get candidate's subject selections (CandidateSubjectSelection)
  4. Find combination where ALL selected subjects exist
  5. Validate imported subject is in that combination
  6. Reject row if subject not in combination
```

**Error Messages:**
- "Candidate is not registered for ACSEE in year {year}"
- "Candidate's ACSEE combination not found"
- "Subject '{code}' is not registered under candidate's ACSEE combination"

---

### 3. BACKEND - IMPORT SERVICE

#### File: `app/Services/MarkImport/MarkImportService.php`

**Changes:**
- ❌ Removed `combinationId` parameter from `createBatch()` method
- ✅ Updated method signature to accept only: `examYear`, `schoolId`, `subjectId`, `importedBy`
- ✅ Removed `combination_id` from batch creation
- ✅ Added comment documenting the change

**Updated Method:**
```php
public function createBatch(
    int $examYear,
    int $schoolId,
    int $subjectId,
    string $importedBy
): MarkImportBatch {
    // combination_id is NOT stored
    // combination is derived during validation
}
```

---

### 4. BACKEND - TEMPLATE SERVICE

#### File: `app/Services/MarkImport/MarkTemplateService.php`

**Changes:**
- ❌ Removed `Combination` parameter from `generateSampleRows()`
- ❌ Removed `Combination` parameter from `generateCsv()`
- ✅ Updated sample index generation to be generic: `'S' . str_pad($i, 6, '0', STR_PAD_LEFT)`
- ✅ Removed `Combination` model import

**Impact:**
- CSV templates no longer encode combination info
- Sample data uses generic index numbers
- Template structure determined by subject only (written papers, practical, project)

---

### 5. DATABASE SCHEMA

#### New Migration: `database/migrations/2026_01_31_make_combination_id_nullable_in_batches.php`

**Change:**
- Made `combination_id` column **nullable** in `mark_import_batches` table
- Allows backward compatibility with existing batches
- Future batches will have `combination_id = NULL`

**SQL:**
```sql
ALTER TABLE mark_import_batches 
MODIFY combination_id BIGINT UNSIGNED NULL;
```

---

### 6. DATABASE MODEL

#### File: `app/Models/MarkImportBatch.php`

**Changes:**
- ❌ Removed `combination_id` from `$fillable` array
- ❌ Removed `combination()` relationship method
- ✅ Model no longer attempts to load combination relation

**Data Structure:**
```
mark_import_batches:
  - id
  - batch_code
  - exam_year
  - region_id
  - district_id
  - school_id
  - subject_id          ← Subject only (NOT combination)
  - exam_type_id
  - status
  - ...
  (combination_id now NULL/unused)
```

---

### 7. USER INTERFACE

#### File: `resources/views/mark-entry/index.blade.php`

**Changes:**

**Layout:**
- ❌ Removed entire "Combination" dropdown (32 lines)
- ✅ Expanded Subject field from `col-span-2` to `col-span-4`
- ✅ Grid layout now: Year(1) + Region(1) + District(2) + School(3) + Subject(4) + Reset(1)

**Data/State:**
- ❌ Removed `selectedCombination` state variable
- ❌ Removed `combinations` array
- ❌ Removed `combinationOpen` dropdown state
- ❌ Removed `combinationSearch` search state
- ✅ Simplified state management

**Methods:**
- ❌ Removed `loadCombinations()` method
- ✅ Updated `downloadTemplate()` - no longer checks for combination
- ✅ Updated `uploadFile()` - no longer sends combination_id
- ✅ Updated `resetContext()` - no longer resets combination
- ✅ Updated validation messages

**User Messaging:**
- Old: "Select subject and combination first"
- New: "Select subject first"
- Old: "Please select all required fields"
- New: "Please select all required fields (year, school, subject)"

---

### 8. API ROUTES

#### File: `routes/web.php`

**Changes:**
- ❌ Removed `GET /api/mark-entry/acsee/combinations` route
- ✅ Remaining API endpoints:
  - `GET /api/mark-entry/acsee/regions`
  - `GET /api/mark-entry/acsee/districts`
  - `GET /api/mark-entry/acsee/schools`
  - `GET /api/mark-entry/acsee/subjects`

---

## VALIDATION FLOW (UPDATED)

```
┌─────────────────────────────────────────────┐
│  USER SELECTS:                              │
│  - Exam Year                                │
│  - School                                   │
│  - Subject (NO COMBINATION!)                │
└────────────┬────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────┐
│  DOWNLOAD TEMPLATE (Subject-Based)          │
│  - Columns determined by subject structure  │
│  - No combination encoding                  │
└────────────┬────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────┐
│  UPLOAD CSV FILE                            │
│  Create batch WITHOUT combination_id        │
└────────────┬────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────┐
│  FOR EACH CSV ROW - VALIDATE:               │
│  1. Find candidate by index_number          │
│  2. Check ACSEE registration for year       │
│  3. Get candidate's selected subjects       │
│  4. Derive combination that contains ALL    │
│     candidate's subjects                    │
│  5. Validate uploaded subject is in that    │
│     combination                             │
│  6. Validate marks (structure, ranges)      │
└────────────┬────────────────────────────────┘
             │
             ├─ ✅ Valid Records → stored
             │
             └─ ❌ Invalid → error message
                   "Subject not registered under
                    candidate's ACSEE combination"
```

---

## BACKWARD COMPATIBILITY

✅ **Existing batches are safe:**
- `combination_id` column remains (now nullable)
- Old batches keep their combination_id value
- Validation logic works for both old and new batches

✅ **Legacy UI protection:**
- If old UI tries to send `combination_id`, controller rejects it with HTTP 422
- Clear error message directs users to update

---

## ERROR HANDLING

**Rejection Scenarios:**

| Scenario | Error Message | HTTP Status |
|----------|---------------|------------|
| Candidate not found | "Candidate with index number '{idx}' not found" | 400 |
| Not registered for ACSEE | "Candidate is not registered for ACSEE in year {year}" | 400 |
| Combination not derivable | "Candidate's ACSEE combination not found" | 400 |
| Subject not in combination | "Subject '{code}' is not registered under candidate's ACSEE combination" | 400 |
| Legacy combination_id sent | "Combination selection is not allowed. Combination is derived from candidate registration." | 422 |

---

## TESTING CHECKLIST

### Unit Tests (Recommended)

- [ ] `MarkValidationService::getCandidateCombination()` returns correct combination
- [ ] `MarkValidationService::getCandidateCombination()` returns null for unregistered candidates
- [ ] `MarkValidationService::validateRawMark()` rejects subject not in combination
- [ ] `MarkValidationService::validateRawMark()` accepts subject in combination
- [ ] `MarkImportService::createBatch()` works without combination_id

### Integration Tests

- [ ] CSV upload succeeds without combination_id parameter
- [ ] CSV upload rejects combination_id in request
- [ ] Template download works with subject_id only
- [ ] Validation correctly identifies students by combination
- [ ] Marks import works for:
  - Single combination school
  - Multi-combination school (different students have different combos)
  - Subject shared across combinations

### Manual Testing

- [ ] Open `/mark-entry/acsee` - no combination dropdown visible
- [ ] Download template for Biology - uses only subject structure
- [ ] Upload CSV with valid candidates - no combination_id in form data
- [ ] Upload CSV with invalid subject - gets clear rejection message
- [ ] Check batch details - no combination displayed
- [ ] Lock batch works normally

---

## NECTA COMPLIANCE

✅ **Meets NECTA requirements:**
- Subject marks import tied to candidate combination (derived internally)
- One CSV upload = one subject = one school
- Combination validation prevents invalid subject assignments
- Clear error reporting for data entry officers
- Scalable to national deployment

---

## FILES MODIFIED

| File | Changes | Status |
|------|---------|--------|
| `app/Http/Controllers/MarkEntryController.php` | Removed combination methods & params | ✅ Done |
| `app/Services/MarkImport/MarkValidationService.php` | Added dynamic combination derivation | ✅ Done |
| `app/Services/MarkImport/MarkImportService.php` | Removed combination param | ✅ Done |
| `app/Services/MarkImport/MarkTemplateService.php` | Removed combination param | ✅ Done |
| `app/Models/MarkImportBatch.php` | Removed combination relation | ✅ Done |
| `resources/views/mark-entry/index.blade.php` | Removed combination UI | ✅ Done |
| `routes/web.php` | Removed combination route | ✅ Done |
| `database/migrations/2026_01_31_make_combination_id_nullable_in_batches.php` | NEW: Migration | ✅ Done |

---

## DEPLOYMENT STEPS

1. **Backup database** (safety first)
2. **Run migration:**
   ```bash
   php artisan migrate
   ```
3. **Clear caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```
4. **Test on staging** (recommended)
5. **Deploy to production**
6. **Verify:**
   - Access `/mark-entry/acsee` - UI loads correctly
   - Download template - works
   - Upload CSV - accepts file
   - Validation works for multi-combination school

---

## KEY DESIGN DECISIONS

### Why derive combination from registration?

**Candidate Registration Schema:**
```
Candidate → CandidateExamRegistration (ACSEE, year)
         → CandidateSubjectSelection (selected subjects)
         → Can be mapped to Combination (has all subjects)
```

This is the **source of truth** for what each candidate is supposed to do. The CSV import simply validates against it.

### Why not store combination in batch?

**Storage principle:**
- Raw marks table should contain **raw data only**
- Combination is **derived/contextual**
- Derived data shouldn't be persisted (prevents sync issues)
- Combination is used only for:
  - Validation (during import)
  - Later result processing (done via candidate → registration → combination)

### Why simple UI?

**Data entry principle:**
- Each CSV upload = **one subject for one school**
- Subject structure is fixed (papers, practical, project)
- Candidates' combinations are already registered
- UI should not force manual selection of something that's predetermined

---

## FUTURE ENHANCEMENTS

1. **Audit trail:** Log which combination was derived for each candidate
2. **Batch summary:** Show candidates by combination in import summary
3. **Auto-detection:** Allow subjects from different combinations in one CSV (if all candidates allow it)
4. **Conflict detection:** Alert when candidates have ambiguous combinations
5. **Reporting:** Export validation report with combination derivation details

---

## SUPPORT & TROUBLESHOOTING

### "Combination not found" error

**Cause:** Candidate's selected subjects don't match any defined combination

**Fix:**
1. Check candidate's subject selections
2. Check combination definitions
3. Ensure subject is defined in at least one combination
4. Re-run subject selection if needed

### Old UI sending combination_id

**Cause:** Old frontend code still sending combination parameter

**Fix:**
1. Clear browser cache
2. Hard refresh (Ctrl+Shift+R)
3. If persists, deploy updated UI code

### Template doesn't match CSV

**Cause:** Subject structure changed after template download

**Fix:**
1. Download new template
2. Re-fill data from source
3. Upload using new template

---

## SIGN-OFF

✅ **Refactoring Complete**  
✅ **Design Verified**  
✅ **Code Quality Checked**  
✅ **Backward Compatible**  
✅ **Ready for Testing**  

**Next Step:** Run automated & manual test suite, then deploy to staging.

---

**Documentation Date:** 2026-01-31  
**Version:** 1.0  
**Status:** COMPLETE
