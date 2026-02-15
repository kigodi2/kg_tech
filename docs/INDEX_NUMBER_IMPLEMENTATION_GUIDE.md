# Index Number Validation Engine - Implementation Guide

**Created**: 2026-02-15  
**Status**: Ready for Deployment  

## Overview

Complete NECTA-aligned index number validation system with auto-detection of candidate type, centre resolution, and duplicate protection.

## What Was Implemented

### 1. Configuration (`config/necta.php`)
- Index number format specification (CCCC-SSSS)
- Centre prefix mapping (S=SCHOOL, P=PRIVATE)
- Validation rules (format, normalization, uniqueness)
- Error codes (8 standardized codes)
- Private centre configuration (for future private_centres table)

### 2. Service Layer (`app/Services/IndexNumber/`)

#### IndexNumberValidator.php
Main service with methods:
- `parse(string $indexNumber)` - Basic parsing
- `validate(string $indexNumber, array $context)` - Full validation with context
- `resolveCentre(string $code, string $type)` - Resolve to actual school/private centre
- `findDuplicate()` - Check duplicate in exam context
- Static helpers for error codes

**Key Features**:
- Non-destructive validation (never modifies data)
- Context-aware duplicate detection
- Auto-detects candidate_type from index number prefix
- User-friendly error messages
- Configurable via `config/necta.php`

#### DTOs
- **ParsedIndexNumber.php** - Represents successfully parsed index (centre_code, prefix, serial, candidate_type)
- **ValidationResult.php** - Comprehensive validation result with errors, warnings, resolved IDs

### 3. Integration Points

#### CandidateController.php
- `store()` method: Validates index number before creating candidate
- `update()` method: Validates index number changes, ignores self in duplicate check
- Auto-sets `candidate_type` from index number prefix
- Returns user-friendly JSON errors for API calls

### 4. Database Migration (`database/migrations/2026_02_15_add_unique_index_constraint_to_candidates.php`)

**Safe migration with duplicate detection**:
1. Scans for existing duplicates in (exam_year_id, exam_type_id, candidate_id)
2. If duplicates found: logs them, aborts with clear instructions
3. Does NOT auto-delete duplicates (manual review required)
4. Creates UNIQUE index on `candidate_exam_registrations(candidate_id, exam_year_id, exam_type_id)`

### 5. Artisan Command (`app/Console/Commands/ScanDuplicateIndex.php`)

**Usage**:
```bash
# Scan all duplicates
php artisan necta:scan-duplicate-index

# Filter by exam year
php artisan necta:scan-duplicate-index --exam-year=2026

# Filter by exam type
php artisan necta:scan-duplicate-index --exam-type=ACSEE

# Export as JSON
php artisan necta:scan-duplicate-index --output=json --export=/tmp/duplicates.json

# Export as CSV
php artisan necta:scan-duplicate-index --output=csv --export=/tmp/duplicates.csv
```

### 6. Comprehensive Tests (`tests/Feature/IndexNumberValidationTest.php`)

Coverage:
- ✅ Valid SCHOOL index number parsing
- ✅ Valid PRIVATE index number parsing
- ✅ Format validation (all error types)
- ✅ Centre resolution (known and unknown)
- ✅ Duplicate detection in same exam context
- ✅ Allow same index in different exam years
- ✅ Allow same index in different exam types
- ✅ Ignore current candidate on update
- ✅ Normalization (uppercase, trim spaces)
- ✅ Error codes availability

## How It Works

### Validation Flow

```
1. User submits candidate registration with index_number (e.g., "S0445-0001")
                          ↓
2. CandidateController.store() receives request
                          ↓
3. Validator.validate() called with context:
   - exam_year_id (from request or active year)
   - exam_type_id (from request or ACSEE)
   - candidate_id (null for create, id for update)
                          ↓
4. Parsing phase:
   - Normalize (uppercase, trim)
   - Split by delimiter "-"
   - Extract centre_code (S0445) and serial (0001)
                          ↓
5. Format validation:
   - Regex check on centre code
   - Regex check on serial
                          ↓
6. Centre resolution:
   - For SCHOOL: lookup in schools.registration_number
   - For PRIVATE: lookup in private_centres (or fallback mapping)
   - Return error if not found
                          ↓
7. Duplicate detection (if exam context provided):
   - Find any candidate with same index_number
   - In same exam_year_id + exam_type_id
   - Ignore current candidate if updating
   - Return error if duplicate found
                          ↓
8. Return ValidationResult:
   - ok: bool (all validations passed)
   - parsed: ParsedIndexNumber (with auto-detected candidate_type)
   - resolved_school_id: int | null
   - resolved_private_centre_id: int | null
   - duplicate_candidate_id: int | null
   - errors: array (user-friendly messages)
```

### Uniqueness Scope

Index numbers are **unique per exam context**:
```
UNIQUE (exam_year_id, exam_type_id, candidate_id)
```

This means:
- ✅ Same index in different exam years: ALLOWED
- ✅ Same index in different exam types: ALLOWED
- ✅ Same index in same exam context: BLOCKED (duplicate error)
- ✅ Same index, same exam, different candidate: BLOCKED (duplicate error)
- ✅ Same index, same exam, same candidate (update): ALLOWED (self-ignore)

## Error Codes (User-Friendly)

| Code | Message | Scenario |
|------|---------|----------|
| INDEX_EMPTY | Index number cannot be empty | User left field blank |
| INDEX_FORMAT_INVALID | Invalid format. Use: CCCC-SSSS | Missing delimiter, wrong format |
| CENTRE_CODE_INVALID | Centre code must be 4 digits | Code too short/long |
| CENTRE_PREFIX_UNKNOWN | Must be S (School) or P (Private) | Invalid prefix (X, Y, etc.) |
| SERIAL_INVALID | Serial number must be 4 digits | Non-numeric, wrong length |
| CENTRE_NOT_FOUND | Centre not found in system | School/private centre doesn't exist |
| DUPLICATE_INDEX_NUMBER | Already registered for this exam | Same index in same exam context |
| EXAM_CONTEXT_MISSING | Exam year and type required | No exam context provided (warning) |

## Deployment Steps

### 1. Verify Schema
```bash
# Check that schools table has registration_number column
php artisan tinker
> Schema::getColumns('schools')  # Look for registration_number
> exit
```

### 2. Scan for Existing Duplicates (CRITICAL)
```bash
php artisan necta:scan-duplicate-index --output=table

# If duplicates found:
# - Note the details
# - Review each manually
# - Decide: delete, re-register, or merge
# - Execute manual cleanup in database
# - Re-run scan to verify cleanup
```

### 3. Run Migration
```bash
# This will automatically check for duplicates again
php artisan migrate

# If migration fails due to duplicates:
# - Fix the duplicates as shown in error message
# - Re-run migration
```

### 4. Test in Development
```bash
# Run validation tests
php artisan test tests/Feature/IndexNumberValidationTest.php

# Test via API/UI:
# - Create SCHOOL candidate: S0445-0001 (should work if school S0445 exists)
# - Create PRIVATE candidate: P0652-0502 (may fail if private centre doesn't exist - expected)
# - Try duplicate: should fail with DUPLICATE_INDEX_NUMBER error
```

### 5. Integration with CandidateImportService
The validator can be integrated into bulk import:
```php
// In CandidateImportService::validateCSV()
$validator = new IndexNumberValidator();
$result = $validator->validate($indexNumber, [
    'exam_year_id' => $examYear->id,
    'exam_type_id' => $examType->id,
]);
if (!$result->ok) {
    // Add to row errors
}
```

## Configuration Notes

### Private Centres
Currently, the system does NOT have a `private_centres` table. Two options:

**Option A: Create private_centres table** (Recommended)
```php
// Create migration for private_centres table
Schema::create('private_centres', function (Blueprint $table) {
    $table->id();
    $table->string('registration_number')->unique();  // P0652, etc.
    $table->string('name');
    $table->foreignId('region_id')->constrained();
    // ... other fields
});

// Update config/necta.php:
'private_centre' => [
    'table' => 'private_centres',
    'use_fallback_mapping' => false,
]
```

**Option B: Use fallback mapping** (Temporary)
```php
// Update config/necta.php:
'private_centre' => [
    'use_fallback_mapping' => true,
    'fallback_mapping' => [
        'P0652' => 1,  // centre_id
        'P0653' => 2,
        // ...
    ],
]
```

### Custom Index Number Format
If NECTA format changes, update `config/necta.php`:
```php
'centre_code_regex' => '^[SP][0-9]{4}$',  // Current: 1 letter + 4 digits
'serial_regex' => '^[0-9]{4}$',            // Current: 4 digits
'full_pattern' => '^[SP][0-9]{4}-[0-9]{4}$',
```

## Troubleshooting

### Migration fails with duplicate error
```
Cannot add unique constraint: X duplicate index numbers found
```

**Solution**:
1. Run scan: `php artisan necta:scan-duplicate-index`
2. Export: `php artisan necta:scan-duplicate-index --output=json --export=/tmp/dups.json`
3. Manually review and fix in database
4. Re-run migration

### Private centre index numbers fail validation
```
Error: Centre not found in system
```

**Cause**: Private centre table doesn't exist or centre not registered  
**Solution**:
1. Option A: Create private_centres table
2. Option B: Add centre code to fallback mapping in config/necta.php
3. Option C: Disable enforcement: set `enforce_known_centre => false` (NOT recommended for production)

### Index number not auto-detecting as PRIVATE
```
Expected: candidate_type = 'PRIVATE'
Actual: candidate_type = 'SCHOOL'
```

**Cause**: Validator returns error before reaching candidate_type assignment  
**Solution**: Ensure index number format is valid (P0652-0502) and centre exists

## API Responses

### Successful validation (JSON)
```json
{
  "ok": true,
  "errors": [],
  "parsed": {
    "raw": "S0445-0001",
    "normalized": "S0445-0001",
    "centre_code": "S0445",
    "prefix": "S",
    "serial": "0001",
    "candidate_type": "SCHOOL"
  },
  "resolved": {
    "school_id": 123,
    "private_centre_id": null
  }
}
```

### Failed validation (JSON)
```json
{
  "ok": false,
  "errors": [
    {
      "code": "DUPLICATE_INDEX_NUMBER",
      "message": "This index number is already registered for this exam",
      "field": "index_number"
    }
  ],
  "parsed": {
    "raw": "S0445-0001",
    "normalized": "S0445-0001",
    "centre_code": "S0445",
    "prefix": "S",
    "serial": "0001",
    "candidate_type": "SCHOOL"
  },
  "resolved": {
    "school_id": 123,
    "private_centre_id": null
  },
  "duplicate_candidate_id": 456
}
```

## Files Created/Modified

### Created
- ✅ `config/necta.php`
- ✅ `app/Services/IndexNumber/IndexNumberValidator.php`
- ✅ `app/Services/IndexNumber/DTO/ParsedIndexNumber.php`
- ✅ `app/Services/IndexNumber/DTO/ValidationResult.php`
- ✅ `app/Console/Commands/ScanDuplicateIndex.php`
- ✅ `database/migrations/2026_02_15_add_unique_index_constraint_to_candidates.php`
- ✅ `tests/Feature/IndexNumberValidationTest.php`
- ✅ `docs/index_number_validation_engine.md`
- ✅ `docs/INDEX_NUMBER_IMPLEMENTATION_GUIDE.md`

### Modified
- ✅ `app/Http/Controllers/CandidateController.php` (store + update methods)

## Next Steps (Optional)

### 1. Create Private Centres Table
```bash
php artisan make:migration create_private_centres_table
# Implement with registration_number, name, region_id, etc.
```

### 2. Add API Endpoint for Index Number Parsing
```php
// routes/api.php
Route::post('/api/index/parse', function (Request $request) {
    $validator = new IndexNumberValidator();
    $parsed = $validator->parse($request->input('index_number'));
    return response()->json($parsed?->toArray() ?? ['error' => 'Invalid format']);
});
```

### 3. Frontend Enhancement (Alpine.js)
```js
// In candidate registration modal
x-data="{ indexNumber: '', parsed: null }"
@input.debounce="
  fetch('/api/index/parse?index_number=' + indexNumber)
    .then(r => r.json())
    .then(d => parsed = d)
"
```

### 4. Integrate with CandidateImportService
Add index number validation to bulk import CSV validation.

## Support

For issues or questions about the index number validation engine:
1. Check the error codes table (above)
2. Review validation logs: `storage/logs/laravel.log`
3. Run diagnostic scan: `php artisan necta:scan-duplicate-index`
4. Check config: `config/necta.php`

