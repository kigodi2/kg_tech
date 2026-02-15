# NECTA Index Number Validation - Quick Reference

**Status**: Ready to Deploy  
**Created**: 2026-02-15  

## What Is It?

Production-grade validation engine for NECTA-style index numbers in candidate registration.

## Index Number Format

```
CCCC-SSSS
└───┘ └───┘
  │     └── Serial number (0001-9999)
  └─────── Centre code (S0445, P0652, etc.)

Prefix:
S = SCHOOL candidate
P = PRIVATE candidate
```

**Examples**:
- `S0445-0001` - School candidate from centre S0445, serial 0001
- `P0652-0502` - Private candidate from centre P0652, serial 0502

## How It Works (Simple)

1. **Parse**: Extract centre code, serial, and auto-detect candidate type
2. **Validate**: Check format, find school/centre, check for duplicates
3. **Return**: Errors or success with resolved IDs

## Usage in Code

### Basic Validation
```php
use App\Services\IndexNumber\IndexNumberValidator;

$validator = new IndexNumberValidator();

$result = $validator->validate('S0445-0001', [
    'exam_year_id' => 1,
    'exam_type_id' => 2,
]);

if ($result->ok) {
    // Success!
    $schoolId = $result->resolved_school_id;
    $candidateType = $result->parsed->candidate_type;  // 'SCHOOL'
} else {
    // Error
    foreach ($result->errors() as $error) {
        echo $error['message'];  // User-friendly message
    }
}
```

### On Update (Ignore Self)
```php
$result = $validator->validate('S0445-0002', [
    'exam_year_id' => 1,
    'exam_type_id' => 2,
    'candidate_id' => $existingCandidate->id,  // Ignore this candidate in duplicate check
]);
```

### Just Parse (No Validation)
```php
$parsed = $validator->parse('S0445-0001');

if ($parsed) {
    echo $parsed->centre_code;        // "S0445"
    echo $parsed->prefix;              // "S"
    echo $parsed->serial;              // "0001"
    echo $parsed->candidate_type;      // "SCHOOL"
    echo $parsed->normalized;          // "S0445-0001" (after normalization)
}
```

## Error Codes

| Code | User Message | When |
|------|--------------|------|
| `INDEX_EMPTY` | Index number cannot be empty | User didn't enter anything |
| `INDEX_FORMAT_INVALID` | Invalid format. Use CCCC-SSSS (e.g., S0445-0001) | Wrong format, missing "-" |
| `CENTRE_PREFIX_UNKNOWN` | Must be S (School) or P (Private) | First letter is X, Y, Z, etc. |
| `CENTRE_CODE_INVALID` | Centre code must be 4 digits | S04 instead of S0445 |
| `SERIAL_INVALID` | Serial number must be 4 digits | S0445-1 instead of S0445-0001 |
| `CENTRE_NOT_FOUND` | Centre not found in system | School/private centre doesn't exist |
| `DUPLICATE_INDEX_NUMBER` | Already registered for this exam | Same index in same exam year + type |
| `EXAM_CONTEXT_MISSING` | Exam year and type required | Context not provided |

## Admin Commands

### Scan for duplicates
```bash
php artisan necta:scan-duplicate-index
```

### Scan and export
```bash
php artisan necta:scan-duplicate-index --output=json --export=/tmp/dupes.json
php artisan necta:scan-duplicate-index --output=csv --export=/tmp/dupes.csv
```

### Filter by exam year
```bash
php artisan necta:scan-duplicate-index --exam-year=2026
```

### Filter by exam type
```bash
php artisan necta:scan-duplicate-index --exam-type=ACSEE
```

## Configuration

Edit `config/necta.php`:

```php
// Require centres to exist in system (RECOMMENDED)
'enforce_known_centre' => true,

// Require unique per exam context (RECOMMENDED)
'enforce_unique_per_exam_context' => true,

// Private centre mapping (fallback if table doesn't exist)
'private_centre' => [
    'use_fallback_mapping' => true,
    'fallback_mapping' => [
        'P0652' => 1,  // centre code => centre id
        'P0653' => 2,
    ],
],
```

## In Controllers

Already integrated in `app/Http/Controllers/CandidateController.php`:

```php
public function store(Request $request)
{
    // ... validation ...
    
    // Index number validation happens automatically for ACSEE
    if ($validated['exam_type'] === 'ACSEE') {
        // Validator checks format, centre, duplicates
        // Auto-sets candidate_type from prefix
    }
    
    // If validation fails, returns error response
}
```

## Test It

```bash
# Run all validation tests
php artisan test tests/Feature/IndexNumberValidationTest.php

# Run specific test
php artisan test tests/Feature/IndexNumberValidationTest.php --filter=parse_valid_school
```

## JSON Response Examples

### Success
```json
{
  "ok": true,
  "parsed": {
    "normalized": "S0445-0001",
    "centre_code": "S0445",
    "candidate_type": "SCHOOL"
  },
  "resolved": {
    "school_id": 123
  },
  "errors": []
}
```

### Failure
```json
{
  "ok": false,
  "errors": [
    {
      "code": "DUPLICATE_INDEX_NUMBER",
      "message": "This index number is already registered for this exam",
      "field": "index_number"
    }
  ]
}
```

## Uniqueness Rules

Index numbers are unique **per exam context**:

```
UNIQUE (exam_year_id, exam_type_id, index_number)
```

Examples:
- ✅ S0445-0001 in 2025 ACSEE + S0445-0001 in 2026 ACSEE = ALLOWED (different years)
- ✅ S0445-0001 in 2026 ACSEE + S0445-0001 in 2026 CSEE = ALLOWED (different types)
- ❌ S0445-0001 in 2026 ACSEE + S0445-0001 in 2026 ACSEE = BLOCKED (duplicate)

## Private Centres (TODO)

Currently configured for fallback mapping. When `private_centres` table is created:

```php
'private_centre' => [
    'table' => 'private_centres',
    'use_fallback_mapping' => false,
]
```

## Files

| File | Purpose |
|------|---------|
| `config/necta.php` | Configuration |
| `app/Services/IndexNumber/IndexNumberValidator.php` | Main validator service |
| `app/Services/IndexNumber/DTO/ParsedIndexNumber.php` | Parsed data model |
| `app/Services/IndexNumber/DTO/ValidationResult.php` | Validation result model |
| `app/Console/Commands/ScanDuplicateIndex.php` | Admin command |
| `database/migrations/2026_02_15_add_unique_index_constraint_to_candidates.php` | DB constraint |
| `tests/Feature/IndexNumberValidationTest.php` | Tests |
| `docs/index_number_validation_engine.md` | Technical docs |
| `docs/INDEX_NUMBER_IMPLEMENTATION_GUIDE.md` | Implementation guide |

## Logs

Check application logs for validation issues:
```bash
tail -f storage/logs/laravel.log | grep -i "index_number\|duplicate"
```

## Common Issues

### "Centre not found in system"
- School registration number not in `schools.registration_number`
- Private centre not in fallback mapping or `private_centres` table
- **Fix**: Add school/centre to system first

### "Already registered for this exam"
- Index number exists in same exam year + type
- **Fix**: Use different index number OR resolve duplicate manually

### "Invalid format"
- Missing "-" delimiter
- Wrong prefix (not S or P)
- **Fix**: Use format CCCC-SSSS where C is [SP][0-9]{4} and S is [0-9]{4}

## Support

See `docs/INDEX_NUMBER_IMPLEMENTATION_GUIDE.md` for detailed troubleshooting.

