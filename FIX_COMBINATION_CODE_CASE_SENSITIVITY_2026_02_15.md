# Fix: Combination Code Case-Sensitivity Issue (PMCs)

## Status: ✅ FIXED

Date: 2026-02-15

## Problem

The combination code **`PMCs`** (registered with lowercase 's') was not being recognized during candidate import validation, resulting in error:
```
combination code not found: PMCs
```

This affected 99 candidates in the import shown in the screenshot.

## Root Cause

In `CandidateImportService.php`, the validation logic was using `strtoupper()` to convert input codes to uppercase:

```php
->where('code', strtoupper($combinationValue))
```

This converted `PMCs` → `PMCS`, which did NOT match the registered `PMCs` in the database.

## Solution

Changed the validation to use **case-insensitive** database comparison:

**File**: `app/Services/Candidates/CandidateImportService.php` (Lines 366-373)

**Before:**
```php
$combinationValue = trim($combination);

// First try: Check if it's a registered combination code (e.g., HGL, HGK, HKL)
$combinationExists = DB::table('combinations')
    ->where('exam_type_id', $acsee->id)
    ->where('code', strtoupper($combinationValue))
    ->exists();
```

**After:**
```php
$combinationValue = trim($combination);

// First try: Check if it's a registered combination code (e.g., HGL, HGK, HKL)
// Case-insensitive comparison to handle codes like PMCs
$combinationExists = DB::table('combinations')
    ->where('exam_type_id', $acsee->id)
    ->whereRaw('LOWER(code) = LOWER(?)', [$combinationValue])
    ->exists();
```

## Verification

Tested with multiple case variations:
- `PMCs` ✓ FOUND
- `pmcs` ✓ FOUND  
- `PMCS` ✓ FOUND
- `PCM` ✓ FOUND
- `pcm` ✓ FOUND

All combinations are now found regardless of case.

## Impact

- **Files Modified**: 1 (`app/Services/Candidates/CandidateImportService.php`)
- **Lines Changed**: 3 (added comment + modified whereRaw)
- **Backward Compatible**: Yes - still accepts uppercase codes like PCM, HGE, etc.
- **Affected Operations**: Candidate import validation

## Deployment Notes

1. Clear any browser cache
2. Retry the import with the 99 failed records
3. All PMCs codes should now validate successfully

## Related Combinations

All 16 registered ACSEE combinations:
- CBA, CBG, CBN
- DGK, DKL
- ECA, EGM
- HGE, HGK, HGL
- HKL
- KLF
- PCB, PCM
- PGM
- **PMCs** ← Fixed case-sensitivity issue
