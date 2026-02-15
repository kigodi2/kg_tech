# PRIVATE Candidate Subject Allocation Implementation  
**Date**: 2026-02-15  
**Status**: ✅ COMPLETE AND TESTED

---

## Overview

Fixed PRIVATE candidate subject allocation during CSV import. PRIVATE candidates can now be imported with flexible subject combinations without requiring General Studies (111) to be mandatory.

---

## Key Changes

### 1. **Validation Rules Updated** (`validateSubjects()`)
**File**: `app/Services/Candidates/CandidateImportService.php:730-774`

**Changes**:
- **General Studies (111) is now OPTIONAL** for PRIVATE candidates
- Minimum requirement: **1 subject** (was: GS + 3 principals = 4 subjects)
- Supports both **subject codes** (e.g., "111", "121") and **numeric IDs**
- Resolver tries codes first, then IDs for flexibility

**Example Valid Imports**:
```csv
candidate_type,school_code,subjects
PRIVATE,P0652,122|132                    # 2 subjects (no GS)
PRIVATE,P0652,111|121|131|141            # 4 subjects (with GS)
PRIVATE,P0652,112|122|132|142|152        # 5 subjects
```

### 2. **CSV Validation Integration** (`commitImport()`)
**File**: `app/Services/Candidates/CandidateImportService.php:275-286`

Added subject validation for PRIVATE candidates during import:
```php
if ($candidateType === 'SCHOOL') {
    $this->validateCombination($record['combination'] ?? null, $rowErrors, 'SCHOOL');
} else {
    // PRIVATE candidates require subjects
    $this->validateSubjects($record['subjects'] ?? null, $rowErrors);
}
```

### 3. **Subject Resolution Logic Fixed**
**File**: `app/Services/Candidates/CandidateImportService.php:1077-1083`

**Critical Bug Fix**: Subject identifier resolution now tries **codes first**, then IDs:
```php
// BEFORE (BROKEN): is_numeric("121") = true → tries Subject::find(121) ❌
$subject = is_numeric($identifier)
    ? Subject::find((int)$identifier)
    : Subject::where('code', strtoupper($identifier))->first();

// AFTER (FIXED): Always tries code first ✓
$subject = Subject::where('code', strtoupper($identifier))->first()
    ?? Subject::find((int)$identifier);
```

### 4. **Allocation Logic Simplified**
**File**: `app/Services/Candidates/CandidateImportService.php:1104-1120`

- Removed NECTA principal subject validation for PRIVATE candidates
- All subjects treated as principals (`is_principal = true`)
- PRIVATE candidates can take ANY combination of subjects

### 5. **Candidate Type Detection Fixed**
**File**: `app/Services/Candidates/CandidateImportService.php:912-920`

Respects CSV `candidate_type` column:
```php
if (!empty($record['candidate_type'])) {
    $candidateType = strtoupper($record['candidate_type']);  // Use CSV value ✓
} else {
    // Auto-detect from index number if not specified
    $validator = new IndexNumberValidator();
    $parsed = $validator->parse($record['candidate_id']);
    $candidateType = $parsed?->candidate_type ?? 'SCHOOL';
}
```

---

## CSV Format

### Required Columns for PRIVATE Candidates
```
candidate_id,full_name,gender,candidate_type,school_code,exam_type,exam_year,subjects
P0652-5001,JOHN DOE,M,PRIVATE,P0652,ACSEE,2026,111|121|131|141
P0652-5002,JANE SMITH,F,PRIVATE,P0652,ACSEE,2026,122|132|142
```

### Subjects Column Format
- **Pipe-delimited** (|) subject codes or IDs
- **Subject codes**: 111 (GS), 112, 113, 121, 122, 131, 132, etc.
- **Minimum**: 1 subject
- **Optional**: General Studies (111) can be omitted

### Examples
| Type | Subjects | Valid? | Notes |
|------|----------|--------|-------|
| PRIVATE | `111\|121\|131\|141` | ✅ | With GS + 3 principals |
| PRIVATE | `122\|132\|142` | ✅ | No GS, 3 subjects |
| PRIVATE | `121` | ✅ | Single subject allowed |
| PRIVATE | `111\|121` | ✅ | GS + 1 principal |
| PRIVATE | _(empty)_ | ❌ | At least 1 subject required |

---

## Verification

### Test Case 1: PRIVATE No GS
```csv
candidate_id,full_name,gender,candidate_type,school_code,exam_type,exam_year,subjects
SUCCESS-01,PRIVATE NO GS,F,PRIVATE,P0652,ACSEE,2026,112|121|131
```

**Result**:
- ✅ Candidate created
- ✅ 3 subjects allocated (112, 121, 131)
- ✅ General Studies NOT allocated

### Test Case 2: PRIVATE With GS
```csv
candidate_id,full_name,gender,candidate_type,school_code,exam_type,exam_year,subjects
SUCCESS-02,PRIVATE WITH GS,M,PRIVATE,P0652,ACSEE,2026,111|122|132|142
```

**Result**:
- ✅ Candidate created
- ✅ 4 subjects allocated (111, 122, 132, 142)
- ✅ General Studies IS allocated

### Test Case 3: Multiple Subject Counts
```csv
candidate_id,full_name,gender,candidate_type,school_code,exam_type,exam_year,subjects
FINAL-VERIFY-01,FEW SUBJECTS,F,PRIVATE,P0652,ACSEE,2026,122|132
FINAL-VERIFY-02,MANY SUBJECTS,M,PRIVATE,P0652,ACSEE,2026,111|121|131|141|151
```

**Result**:
- ✅ Imported 2 candidates, allocated subjects for 7
- ✅ FINAL-VERIFY-01: 2 subjects
- ✅ FINAL-VERIFY-02: 5 subjects

---

## API Response

When importing PRIVATE candidates, the commit response includes:
```json
{
  "success": true,
  "message": "Imported 2 candidates, allocated subjects for 7",
  "imported_count": 2,
  "allocations_created_count": 7,
  "errors": []
}
```

---

## Database Impact

### candidate_subject_selections Table
Allocations are stored with:
- `is_principal = true` (PRIVATE candidates don't follow NECTA rules)
- `source = 'import'`
- `is_active = true`

Example record:
```
candidate_id: 16513 (PRIVATE NO GS)
exam_type_id: 1 (ACSEE)
exam_year_id: 3 (2026)
subject_id: 8 (Code: 112 - HISTORY)
is_principal: 1
source: import
```

---

## Known Limitations

1. **No validation of subject prerequisites** - PRIVATE candidates can take any subjects (intentional)
2. **No automatic General Studies requirement** - Must be explicitly included if desired
3. **Subject codes must exist in database** - Invalid codes will cause import to fail

---

## Future Enhancements

1. Add subject count validation rules per school/district
2. Implement subject dependency validation for PRIVATE candidates
3. Support subject allocation by subject categories (e.g., "all science subjects")
4. Add UI for manual allocation editing

---

## Deployment Notes

- No database migrations required
- No table structure changes
- Backward compatible with SCHOOL candidate imports
- All changes in service layer only

**Test thoroughly before production deployment** ✅

---

## Related Files Modified

1. `app/Services/Candidates/CandidateImportService.php`
   - `validateSubjects()` - Subject validation logic
   - `validateCSV()` - Integration at line 284
   - `commitImport()` - Integration at line 284
   - `processBatch()` - Candidate type detection (line 912)
   - `allocateSubjectsForPrivateCandidate()` - Subject resolution & allocation

---

## Testing Commands

```bash
# Unit test validation
php artisan tinker
$service = new \App\Services\Candidates\CandidateImportService();
// ... [see verification section above]

# Manual CSV import via UI
# Navigate to: /registration/candidates → Bulk Import CSV
# Select mode: SKIP or REPLACE
# Upload CSV with PRIVATE candidates
# Verify "Allocated Subjects" column in /exam-types/acsee
```

---

**Status**: Ready for production ✅
