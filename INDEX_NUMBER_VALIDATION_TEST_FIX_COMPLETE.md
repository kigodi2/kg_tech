# Index Number Validation Test Suite - Complete Fix

## Summary
All 17 tests in `tests/Feature/IndexNumberValidationTest.php` are now passing.

## Issues Fixed

### 1. Database Migration Setup (`tests/TestCase.php`)
**Problem**: Tests were failing because the test database wasn't being migrated before running tests.

**Solution**: Added `RefreshDatabase` trait to the `TestCase` class, which automatically runs migrations before each test.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
```

### 2. ExamYearFactory Schema Mismatch
**Problem**: Factory was trying to insert `start_date` and `end_date` columns that don't exist in the `exam_years` table.

**Solution**: Updated the factory to match the actual schema:
- Removed `start_date` and `end_date` fields
- Changed to use actual columns: `year_label`, `is_active`, `is_locked`

### 3. CandidateFactory Schema Mismatch
**Problem**: Factory was trying to insert obsolete fields:
- `date_of_birth` (doesn't exist in current schema)
- `candidate_type` (auto-determined from candidate_id prefix)

**Solution**: Removed those fields and updated the factory methods to only set the `candidate_id` field, letting other fields use defaults.

### 4. Index Number Validation - Unknown Prefix Handling
**Problem**: When parsing an index number with an unknown prefix (e.g., `X0445`), the parsing would fail completely and return `INDEX_FORMAT_INVALID` instead of the proper `CENTRE_PREFIX_UNKNOWN` error.

**Solution**: Modified `ParsedIndexNumber::fromString()` to allow unknown prefixes and set `candidate_type` to `'UNKNOWN'`, allowing the validator to detect and report the proper error code.

### 5. CandidateExamRegistration Table Schema
**Problem**: Tests were creating registrations with `exam_year_id` field that doesn't exist in the current schema.

**Solution**: Updated test data creation to use the correct schema:
- Removed `exam_year_id` field
- Added required `registration_number` field (unique)
- Kept `exam_type_id` and `year` fields

### 6. Duplicate Detection Logic
**Problem**: The validator's `findDuplicate()` method was checking for `exam_year_id` in the relationship query, but the table uses `year` (numeric year label) instead.

**Solution**: Updated the duplicate detection to:
- Fetch the exam year and extract its `year_label`
- Query registrations by `exam_type_id` and `year` (matching the table schema)

## Test Results

```
   PASS  Tests\Feature\IndexNumberValidationTest
  ✓ parse valid school index number
  ✓ parse valid private index number
  ✓ parse fails missing delimiter
  ✓ parse fails empty string
  ✓ validate school candidate with known centre
  ✓ validate school candidate with unknown centre
  ✓ validate rejects malformed no hyphen
  ✓ validate rejects invalid serial
  ✓ validate rejects invalid prefix
  ✓ validate rejects empty
  ✓ validate detects duplicate in same exam context
  ✓ validate allows same index different years
  ✓ validate ignores same candidate on update
  ✓ normalize converts to uppercase
  ✓ normalize trims whitespace
  ✓ validation result to array
  ✓ error codes available

  Tests:    17 passed (43 assertions)
```

## Files Modified

1. `/home/prosmart-technologies/SOL/irms/tests/TestCase.php` - Added RefreshDatabase trait
2. `/home/prosmart-technologies/SOL/irms/database/factories/ExamYearFactory.php` - Fixed schema mismatch
3. `/home/prosmart-technologies/SOL/irms/database/factories/CandidateFactory.php` - Removed obsolete fields
4. `/home/prosmart-technologies/SOL/irms/app/Services/IndexNumber/DTO/ParsedIndexNumber.php` - Allow unknown prefixes
5. `/home/prosmart-technologies/SOL/irms/app/Services/IndexNumber/IndexNumberValidator.php` - Fixed duplicate detection logic
6. `/home/prosmart-technologies/SOL/irms/tests/Feature/IndexNumberValidationTest.php` - Fixed test data to match actual schema

## Verification

Run tests with:
```bash
php artisan test tests/Feature/IndexNumberValidationTest.php
```

All 17 tests pass successfully with 43 assertions verified.
