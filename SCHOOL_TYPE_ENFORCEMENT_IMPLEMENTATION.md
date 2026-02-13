# School Type Enforcement Implementation

## Overview
Implemented automatic enforcement of school types based on exam registrations:
- Schools with CSEE/ACSEE registrations must have `school_type` as `SECONDARY` or `BOTH`
- Schools with PSLE registrations must have `school_type` as `PRIMARY` or `BOTH`
- Schools with both PSLE and CSEE/ACSEE registrations must have `school_type` as `BOTH`

## Components Implemented

### 1. Model Methods (School.php)
Added three methods to `app/Models/School.php`:

- **`validateExamTypeSchoolType()`**: Validates school types based on exam registrations. Throws exception if validation fails.
  - CSEE/ACSEE schools must be SECONDARY or BOTH
  - PSLE schools must be PRIMARY or BOTH
  
- **`enforceExamTypeSchoolType()`**: Automatically enforces correct school types:
  - PSLE only → PRIMARY
  - CSEE/ACSEE only → SECONDARY
  - Both PSLE and CSEE/ACSEE → BOTH
  
- **`ensureSecondaryTypeForSecondaryExams()`**: Deprecated wrapper that calls `enforceExamTypeSchoolType()` for backward compatibility

### 2. Admin Panel Updates (SchoolResource.php)
- Updated helper text to `school_type` field: "PSLE schools must be PRIMARY or BOTH. CSEE/ACSEE schools must be SECONDARY or BOTH."
- Helper text informs admins of the requirements

### 3. Filament Page Hooks

#### CreateSchool.php
- Added `afterCreate()` hook that calls `enforceExamTypeSchoolType()` after record creation
- Ensures new schools are validated and corrected if needed

#### EditSchool.php
- Added `afterSave()` hook that calls `enforceExamTypeSchoolType()` after record update
- Ensures updated schools maintain compliance

### 4. Model Observer (SchoolObserver.php)
- `created()`: Enforces exam type school types on creation
- `updated()`: Enforces exam type school types on update
- `saving()`: Optional validation hook (commented out for auto-correction mode)

**Registration in AppServiceProvider.php**: Observer registered via `School::observe(SchoolObserver::class)`

### 5. Artisan Command
Created `app/Console/Commands/EnforceSchoolTypes.php`:
- Scans all schools for exam registrations (PSLE, CSEE, ACSEE)
- Updates non-compliant schools to correct types:
  - PSLE only → PRIMARY
  - CSEE/ACSEE only → SECONDARY
  - Both types → BOTH
- Usage: `php artisan enforce-school-types`
- Options:
  - `--dry-run`: Show what would be updated without making changes

## Enforcement Strategy

**Auto-Correction Mode** (Currently Active):
- Schools are automatically assigned correct types based on exam registrations
- No exceptions thrown; changes applied silently
- Happens at multiple points: Filament saves, Model observer, and via command
- Logic:
  - School with PSLE → PRIMARY
  - School with CSEE/ACSEE → SECONDARY
  - School with both → BOTH
  - School with neither → No change

**Optional Strict Validation** (Can be enabled):
- Uncomment `$school->validateExamTypeSchoolType()` in SchoolObserver's `saving()` hook
- Will throw exception if invalid data is provided
- Requires admin to fix the issue before saving

## Usage

### Manual Enforcement
Run the command to fix existing data:
```bash
php artisan enforce-school-types
```

### Preview Changes (Dry Run)
```bash
php artisan enforce-school-types --dry-run
```

### Automatic Enforcement
- Admin creates/edits school → Filament hooks trigger
- School created/updated via code → Model observer triggers
- Result: Automatic correction to SECONDARY if needed

## Data Flow

1. **Admin saves school in Filament**
   - CreateSchool/EditSchool page hooks trigger
   - `enforceExamTypeSchoolType()` called
   - School type auto-corrected based on exam registrations

2. **School updated programmatically**
   - SchoolObserver `updated()` hook triggers
   - `enforceExamTypeSchoolType()` called
   - School type auto-corrected based on exam registrations

3. **Fixing existing data**
   - Run: `php artisan enforce-school-types`
   - All schools updated to correct types based on exam registrations

## Testing

```php
// Test 1: School with PSLE registration should be PRIMARY
$school = School::create([
    'code' => 'PSL001',
    'name' => 'Primary School',
    'school_type' => 'SECONDARY',
    'district_id' => 1,
    'region_id' => 1,
]);
$school->refresh(); // Will be PRIMARY after observer (has PSLE)

// Test 2: School with CSEE registration should be SECONDARY
$school = School::create([
    'code' => 'CSE001',
    'name' => 'Secondary School',
    'school_type' => 'PRIMARY',
    'district_id' => 1,
    'region_id' => 1,
]);
$school->refresh(); // Will be SECONDARY after observer (has CSEE/ACSEE)

// Test 3: School with both PSLE and ACSEE should be BOTH
$school = School::create([
    'code' => 'CBT001',
    'name' => 'Combined School',
    'school_type' => 'PRIMARY',
    'district_id' => 1,
    'region_id' => 1,
]);
$school->refresh(); // Will be BOTH after observer (has both types)

// Test 4: School without exam registrations stays unchanged
// (only auto-corrected if it has exam registrations)
```

## Files Modified/Created

| File | Action | Purpose |
|------|--------|---------|
| app/Models/School.php | Modified | Added validation and enforcement methods |
| app/Filament/Admin/Resources/SchoolResource.php | Modified | Added helper text |
| app/Filament/Admin/Resources/SchoolResource/Pages/CreateSchool.php | Modified | Added afterCreate hook |
| app/Filament/Admin/Resources/SchoolResource/Pages/EditSchool.php | Modified | Added afterSave hook |
| app/Observers/SchoolObserver.php | Created | Model observer for automatic enforcement |
| app/Console/Commands/EnforceSchoolTypes.php | Created | Command for batch fixing |
| app/Providers/AppServiceProvider.php | Modified | Registered observer |

## Notes

- The enforcement is **non-destructive**: existing valid types are preserved
- Schools are only corrected if their current type doesn't match exam registrations
- The command is idempotent: running it multiple times is safe
- Helper text in admin panel provides context to users about the requirements
- Backward compatibility maintained: `ensureSecondaryTypeForSecondaryExams()` still works but delegates to `enforceExamTypeSchoolType()`
