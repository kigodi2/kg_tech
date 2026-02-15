# Candidate Type Auto-Detection Fix

## Issue
Candidates with index numbers starting with "P" (indicating PRIVATE candidates) were being incorrectly classified as SCHOOL candidates.

Example: Index numbers like `P0652-0502` were showing as type "SCHOOL" instead of "PRIVATE".

## Root Cause
The district candidate import functionality was not using the IndexNumberValidator to auto-detect the candidate type from the index number prefix. Instead, it relied on the database default, which defaulted to 'SCHOOL'.

Similarly, a batch import method in CandidateImportService was also not setting candidate_type.

## Solution
Auto-detect candidate type from index number prefix using the IndexNumberValidator service in all candidate creation paths:

### Files Modified

#### 1. `app/Http/Controllers/DistrictCandidateImportController.php`
- Added import: `use App\Services\IndexNumber\IndexNumberValidator;`
- Modified `importByDistrict()` method to parse index number and extract candidate_type
- Now correctly identifies P-prefix candidates as PRIVATE and S-prefix as SCHOOL

**Before:**
```php
$candidate = Candidate::create([
    'school_id' => $school->id,
    'candidate_id' => $candidateId,
    'full_name' => $fullName,
    'gender' => strtoupper($gender[0]),
    'date_of_birth' => null,
]);
```

**After:**
```php
// Auto-detect candidate type from index number prefix
$validator = new IndexNumberValidator();
$parsed = $validator->parse($candidateId);
$candidateType = $parsed?->candidate_type ?? 'SCHOOL'; // Default to SCHOOL if parsing fails

$candidate = Candidate::create([
    'school_id' => $school->id,
    'candidate_id' => $candidateId,
    'full_name' => $fullName,
    'gender' => strtoupper($gender[0]),
    'candidate_type' => $candidateType,
]);
```

#### 2. `app/Services/Candidates/CandidateImportService.php`
- Added import: `use App\Services\IndexNumber\IndexNumberValidator;`
- Modified batch import method around line 812 to auto-detect candidate_type
- Now all batch imports also properly classify candidates

## Validation Rules
The IndexNumberValidator uses the prefix map from `config/necta.php`:
- **S prefix** → `'SCHOOL'` candidate
- **P prefix** → `'PRIVATE'` candidate
- **Unknown prefix** → `'UNKNOWN'` (allows validation to report proper error)

## Impact
- Candidates imported via district bulk import now have correct candidate_type
- Candidates imported via batch import now have correct candidate_type
- Manual candidate registration via CandidateController already had this fix
- CSV import via CandidateImportService already had this fix

## Testing
Query candidates with P-prefix index numbers:
```sql
SELECT id, candidate_id, candidate_type, full_name FROM candidates WHERE candidate_id LIKE 'P%';
```

Should now show `candidate_type = 'PRIVATE'` for P-prefix candidates.

## Code Standards
- Follows NECTA index number validation standards
- Uses existing IndexNumberValidator service for consistency
- Defaults to 'SCHOOL' if parsing fails (safe fallback)
- No breaking changes to existing APIs
