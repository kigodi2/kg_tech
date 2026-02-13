# Database Migration - Applied ✅

**Migration File**: `database/migrations/2026_01_28_add_combination_to_candidates.php`
**Status**: ✅ APPLIED SUCCESSFULLY
**Execution Time**: 58.93ms

## What Was Done

### Migration Applied
Added `combination` column to `candidates` table

### Column Details
| Property | Value |
|----------|-------|
| Name | combination |
| Type | VARCHAR (String) |
| Nullable | Yes |
| Default | NULL |
| Position | After `exam_type` column |
| Purpose | Store ACSEE subject combinations (PCM, PCB, HEC, etc.) |

## Database Schema Before vs After

### Before Migration
```
candidates table:
├── id (PK)
├── school_id (FK)
├── candidate_id (unique)
├── first_name
├── last_name
├── gender (enum: M, F)
├── date_of_birth
├── is_active
├── created_at
├── updated_at
├── email (nullable, unique)
├── exam_type (nullable)
└── status (default: 'registered')
```

### After Migration
```
candidates table:
├── id (PK)
├── school_id (FK)
├── candidate_id (unique)
├── first_name
├── last_name
├── gender (enum: M, F)
├── date_of_birth
├── is_active
├── created_at
├── updated_at
├── email (nullable, unique)
├── exam_type (nullable)
├── status (default: 'registered')
└── combination (nullable) ✅ NEW
```

## Verification

✅ Migration Status: **APPLIED**

✅ All Columns Present:
- id
- school_id
- candidate_id
- first_name
- last_name
- gender
- date_of_birth
- is_active
- created_at
- updated_at
- email
- exam_type
- status
- combination ← NEW

✅ Sample Data:
```
ID: 51
Candidate: John Doe
Gender: M
Email: john@example.com
Exam Type: KCSE
Combination: NULL (not applicable for KCSE)
```

## Migration Code

### Up (Apply)
```php
Schema::table('candidates', function (Blueprint $table) {
    $table->string('combination')
        ->nullable()
        ->after('exam_type')
        ->comment('Subject combination for ACSEE only (e.g., PCM, PCB, etc.)');
});
```

### Down (Rollback)
```php
Schema::table('candidates', function (Blueprint $table) {
    $table->dropColumn(['combination']);
});
```

## Usage

The `combination` field is used to store subject combinations for ACSEE candidates:

**For ACSEE**: Store combination like "PCM", "PCB", "HEC"
**For PSLE/CSEE**: Leave NULL/blank
**Optional**: Not required to fill

## Model Update

The Candidate model was also updated to include `combination` in the `$fillable` array:

```php
protected $fillable = [
    ...
    'combination',  // ← Added
    ...
];
```

## API Integration

The API now:
- ✅ Accepts `combination` in POST/PUT requests
- ✅ Returns `combination` in GET responses
- ✅ Validates combination (optional field)
- ✅ Shows combination in table/modal

## Backward Compatibility

✅ **Fully Compatible**
- New field is nullable
- Existing records unaffected (NULL value)
- No data loss
- No breaking changes

## Rollback

If rollback needed:
```bash
php artisan migrate:rollback --step=1
```

This will:
- Drop the `combination` column
- Revert to previous schema
- Keep all other data intact

## Status

✅ **DATABASE UPDATED SUCCESSFULLY**

Ready for:
- ✅ New candidate registrations with combination field
- ✅ Editing candidates with combination field
- ✅ Filtering/querying ACSEE candidates
- ✅ Production use

---

**Date Applied**: January 28, 2026
**System Status**: ✅ OPERATIONAL
