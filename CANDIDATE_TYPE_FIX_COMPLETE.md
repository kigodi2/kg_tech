# Candidate Type Correction - Complete Fix

## Issue
Candidates with P-prefix index numbers were incorrectly stored as "SCHOOL" type instead of "PRIVATE" type in the database.

Example: `P0652-0502`, `P0652-0503`, etc. were showing as SCHOOL instead of PRIVATE.

## Root Cause
1. District candidate import controller wasn't using the IndexNumberValidator to detect candidate type
2. Batch import method wasn't setting candidate type
3. 16 candidates were already created with wrong type before the code fixes

## Solution

### Part 1: Code Fixes (Prevents Future Issues)
Fixed two code locations to auto-detect candidate_type from index number prefix:

1. **DistrictCandidateImportController** - Added IndexNumberValidator to parse index number and extract candidate_type
2. **CandidateImportService** - Fixed batch import method to also auto-detect candidate_type

### Part 2: Data Correction (Fixed Existing Data)

Created Artisan command `candidates:fix-type` that:
- Parses all candidate index numbers using IndexNumberValidator
- Compares expected candidate_type (based on prefix) with actual stored type
- Updates any mismatches in the database

**Execution:**
```bash
# Dry-run to see what would be changed
php artisan candidates:fix-type --dry-run

# Apply fixes (with confirmation)
php artisan candidates:fix-type

# Apply without confirmation
php artisan candidates:fix-type --force
```

## Results

**Candidates Fixed:**
- 16 candidates with P-prefix index numbers corrected from SCHOOL → PRIVATE
  - P1770-0501 through P1770-0504 (4 candidates)
  - P0652-0502 through P0652-0516 (12 candidates)

**Summary:**
- Total candidates analyzed: 6,932
- Already correct: 6,916
- Fixed: 16
- Unparseable: 0

## Verification

Check fixed candidates:
```bash
# In tinker
$candidates = App\Models\Candidate::where('candidate_id', 'like', 'P%')->limit(5)->get();
$candidates->each(fn($c) => echo "{$c->candidate_id} => {$c->candidate_type}\n");
```

All P-prefix candidates now show `candidate_type = 'PRIVATE'`.

## Prevention
Going forward, all new candidates will have correct candidate_type because:
1. DistrictCandidateImportController now auto-detects from index number
2. CandidateImportService batch method now auto-detects from index number
3. Existing CandidateController already uses IndexNumberValidator

## Files Modified
- `app/Http/Controllers/DistrictCandidateImportController.php` - Added auto-detection
- `app/Services/Candidates/CandidateImportService.php` - Added auto-detection
- `app/Console/Commands/FixCandidateType.php` - New command for data correction

## Command Reference

```bash
# Show what needs to be fixed without making changes
php artisan candidates:fix-type --dry-run

# Fix with confirmation prompt
php artisan candidates:fix-type

# Fix without confirmation
php artisan candidates:fix-type --force

# Get help
php artisan candidates:fix-type --help
```
