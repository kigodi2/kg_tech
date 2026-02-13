# ⚡ Implement Registration Number Support - TODAY

## Executive Summary

Your CSV format is perfect and doesn't need to change. We'll add support for school registration numbers (S0108, etc.) in the database.

**Time needed**: 15 minutes

---

## Quick Steps

### Step 1: Run Migration (2 minutes)

```bash
cd /home/prosmart-technologies/SOL/irms
php artisan migrate
```

This adds `registration_number` column to schools table.

**What it does:**
- Adds column to store official school registration numbers
- Sets up index for fast lookups
- Makes it unique (no duplicates)

### Step 2: Update School Model (2 minutes)

**File**: `app/Models/School.php`

Add `registration_number` to the fillable array:

```php
protected $fillable = [
    'code',
    'name',
    'registration_number',  // ← ADD THIS LINE
    'ownership',
    'district_id',
    'council_id',
    'region_id',
    'school_type',
    'education_level',
    'address',
    'phone',
    'email',
    'principal_name',
    'is_active',
];
```

### Step 3: Update Import Logic (3 minutes)

**File**: `routes/web.php` (around line 749)

Change this:
```php
$school = \App\Models\School::where('code', $schoolCode)->first();
```

To this:
```php
// Look up by registration_number first, then code as fallback
$school = \App\Models\School::where('registration_number', $schoolCode)
    ->orWhere('code', $schoolCode)
    ->first();
```

### Step 4: Create Schools with Registration Numbers (5 minutes)

Run this command:

```bash
php artisan tinker << 'EOF'
// Create all schools with their registration numbers
// Adjust these based on your actual schools

$schools = [
    [
        'code' => 'DSM001',
        'registration_number' => 'S0108',
        'name' => 'First Dar School',
        'region_id' => 1,
        'district_id' => 53,
        'school_type' => 'SECONDARY'
    ],
    [
        'code' => 'DSM002',
        'registration_number' => 'S0109',
        'name' => 'Second Dar School',
        'region_id' => 1,
        'district_id' => 53,
        'school_type' => 'SECONDARY'
    ],
    // Add more schools as needed
    // [
    //     'code' => 'DSM003',
    //     'registration_number' => 'S0110',
    //     'name' => 'Third Dar School',
    //     'region_id' => 1,
    //     'district_id' => 53,
    //     'school_type' => 'SECONDARY'
    // ],
];

foreach ($schools as $schoolData) {
    App\Models\School::updateOrCreate(
        ['registration_number' => $schoolData['registration_number']],
        $schoolData
    );
}

echo "✅ Schools created with registration numbers!\n";
echo "Total schools: " . App\Models\School::count() . "\n";
EOF
```

### Step 5: Test Import (3 minutes)

1. Go to IRMS: REGISTRATION → Candidates
2. Click: Tools → Import CSV
3. Select your CSV (with S0108 school codes)
4. Click: Import
5. **✅ Should work now!**

---

## Timeline

```
Step 1: Migration       2 min
Step 2: Update Model    2 min
Step 3: Update Import   3 min
Step 4: Create Schools  5 min
Step 5: Test Import     3 min
─────────────────────────────
TOTAL:                  15 min
```

---

## What Changed in Database

### Before:
```
Column Name    | Type     | Purpose
───────────────┼──────────┼─────────────────────────
id             | Integer  | Primary key
code           | String   | Internal system code
name           | String   | School name
...
```

### After:
```
Column Name           | Type     | Purpose
──────────────────────┼──────────┼─────────────────────────
id                   | Integer  | Primary key
code                 | String   | Internal system code
registration_number  | String   | Official registration (S0108)
name                 | String   | School name
...
```

---

## Your CSV - NO CHANGES NEEDED

```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,S0108,ACSEE
```

**This stays exactly the same!** ✅

---

## Import Flow After Implementation

```
CSV File:
  S0108-0501 ... S0108 ... ACSEE
              ↓
        Column 5 = S0108
              ↓
    Import Process Looks Up:
    School::where('registration_number', 'S0108')
              ↓
    Finds School:
    code='DSM001', registration_number='S0108'
              ↓
    Creates Candidate:
    full_name='AGRIPINA...'
    school_id=1 (from school above)
              ↓
        ✅ SUCCESS!
```

---

## Complete File Changes

Only 2 files need changes:

### File 1: app/Models/School.php

Change the `$fillable` array (around line 12):

```php
protected $fillable = [
    'code',
    'name',
    'registration_number',  // ← ADD THIS
    'ownership',
    'district_id',
    'council_id',
    'region_id',
    'school_type',
    'education_level',
    'address',
    'phone',
    'email',
    'principal_name',
    'is_active',
];
```

### File 2: routes/web.php

Change around line 749:

```php
// BEFORE (line 749):
$school = \App\Models\School::where('code', $schoolCode)->first();

// AFTER:
$school = \App\Models\School::where('registration_number', $schoolCode)
    ->orWhere('code', $schoolCode)
    ->first();
```

That's it! Only 2 files to change.

---

## Verification Steps

### Check 1: Migration Ran Successfully

```bash
php artisan migrate --list
```

You should see your migration marked as completed.

### Check 2: Column Exists

```bash
php artisan tinker
```

```php
Schema::hasColumn('schools', 'registration_number')  // Should return: true
```

### Check 3: School with Registration Number

```php
App\Models\School::create([
    'code' => 'TEST',
    'registration_number' => 'S9999',
    'name' => 'Test',
    'region_id' => 1,
    'district_id' => 53,
    'school_type' => 'SECONDARY'
])

// Should return the school
App\Models\School::where('registration_number', 'S9999')->first()
```

---

## Next: Bulk Import All Schools

If you have many schools with registration numbers, create a bulk import:

**Create CSV**: `schools_to_import.csv`

```csv
Code,Name,RegistrationNumber,RegionID,DistrictID,Type
DSM001,Dar School 1,S0108,1,53,SECONDARY
DSM002,Dar School 2,S0109,1,53,SECONDARY
MGO001,Morogoro School,S0110,1,54,SECONDARY
```

Then import via bulk import feature or loop in tinker.

---

## After Implementation

1. **CSV format unchanged** ✅
2. **Official registration numbers stored** ✅
3. **Import works with S0108, S0109, etc.** ✅
4. **System properly identifies schools** ✅

You're done! Now your candidates can be imported without any CSV changes.

---

## Backup Plan (If Migration Fails)

If migration fails, don't worry:

```bash
# Rollback
php artisan migrate:rollback

# Check logs
tail -f storage/logs/laravel.log
```

The migration is safe and reversible. Just let me know if you hit any issues.

---

## Need Help?

- **Migration not running**: Check `php artisan migrate:status`
- **Column not showing**: Check database directly with `php artisan tinker`
- **Import still fails**: Verify schools have `registration_number` set
- **Something unclear**: Refer to `SCHOOL_REGISTRATION_NUMBER_SOLUTION.md`

Ready? Let's go! 🚀

