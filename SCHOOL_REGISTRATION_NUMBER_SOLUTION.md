# Better Solution: School Registration Number Support

## Your Insight

You're right. Your CSV format is correct:
- School ID column (S0108, S0109, etc.) = **Official School Registration Number**
- This is NOT an internal system code
- The CSV format should NOT change

**Problem**: Database needs to store and lookup schools by their registration number.

---

## Solution: Add Registration Number Field

### Option 1: Add Column to Schools Table (Recommended)

Create a migration to add `registration_number` field:

```php
Schema::table('schools', function (Blueprint $table) {
    $table->string('registration_number')->nullable()->unique()->index();
    // This stores the official school registration number (S0108, etc.)
});
```

Then modify import to lookup by registration number:

```php
// OLD (doesn't work):
$school = School::where('code', 'S0108')->first();

// NEW (works):
$school = School::where('registration_number', 'S0108')->first();
```

### Option 2: Rename 'code' to 'registration_number'

If your current `code` field should actually be the registration number:

- Keep CSV format as-is (S0108)
- Rename database column `code` → `registration_number`
- Update all references in code

---

## Implementation Steps

### Step 1: Create Migration

```bash
php artisan make:migration add_registration_number_to_schools_table
```

### Step 2: Edit Migration File

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('registration_number')
                ->nullable()
                ->unique()
                ->index()
                ->comment('Official school registration number (e.g., S0108)');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('registration_number');
        });
    }
};
```

### Step 3: Run Migration

```bash
php artisan migrate
```

### Step 4: Update Import Logic

**File**: `routes/web.php` (lines 686-794)

**Change this section** (around line 749):

```php
// OLD:
$school = \App\Models\School::where('code', $schoolCode)->first();

// NEW:
$school = \App\Models\School::where('registration_number', $schoolCode)
    ->orWhere('code', $schoolCode)  // Fallback to code for backward compatibility
    ->first();
```

### Step 5: Update School Model

**File**: `app/Models/School.php`

```php
protected $fillable = [
    'code',
    'name',
    'registration_number',  // ADD THIS
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

---

## Import Schools with Registration Numbers

Now you can populate schools with their registration numbers:

```bash
php artisan tinker << 'EOF'
// Create schools with registration numbers
$schools = [
    [
        'code' => 'DSM001',
        'registration_number' => 'S0108',
        'name' => 'Dar School 1',
        'region_id' => 1,
        'district_id' => 53,
        'school_type' => 'SECONDARY'
    ],
    [
        'code' => 'DSM002',
        'registration_number' => 'S0109',
        'name' => 'Dar School 2',
        'region_id' => 1,
        'district_id' => 53,
        'school_type' => 'SECONDARY'
    ],
    [
        'code' => 'DSM003',
        'registration_number' => 'S0110',
        'name' => 'Dar School 3',
        'region_id' => 1,
        'district_id' => 53,
        'school_type' => 'SECONDARY'
    ]
];

foreach ($schools as $schoolData) {
    App\Models\School::updateOrCreate(
        ['registration_number' => $schoolData['registration_number']],
        $schoolData
    );
}

echo "Schools created with registration numbers\n";
EOF
```

---

## Updated CSV Import Flow

```
Your CSV:
  S0108-0501, AGRIPINA YOHANA MAGANGA, F, HGL, S0108, ACSEE
                                            ↑
                                    registration_number

Import Process:
  1. Read Column 5: S0108
  2. Lookup: School::where('registration_number', 'S0108')
  3. Find: School with registration_number = S0108
  4. Get school_id
  5. Import candidate with correct school_id
  
Result: ✅ Works perfectly!
```

---

## Database Schema After Update

### Before:
```
schools
├── id (primary key)
├── code (UNIQUE) ← System internal code
├── name
├── region_id
├── district_id
└── ...
```

### After:
```
schools
├── id (primary key)
├── code ← System internal code (DSM001, etc.)
├── registration_number (UNIQUE) ← Official registration (S0108, etc.)
├── name
├── region_id
├── district_id
└── ...
```

---

## Updated SchoolResource (Filament Admin)

**File**: `app/Filament/Admin/Resources/SchoolResource.php`

Add to form:

```php
Forms\Components\TextInput::make('registration_number')
    ->label('School Registration Number')
    ->placeholder('e.g., S0108')
    ->maxLength(20)
    ->unique(ignorable: fn ($record) => $record),

Forms\Components\TextInput::make('code')
    ->label('Internal System Code')
    ->required()
    ->maxLength(20)
    ->unique(ignorable: fn ($record) => $record),
```

Add to table columns:

```php
Tables\Columns\TextColumn::make('registration_number')
    ->label('Registration #')
    ->searchable()
    ->sortable(),

Tables\Columns\TextColumn::make('code')
    ->label('Internal Code')
    ->searchable()
    ->sortable(),
```

---

## Updated API Endpoints

**File**: `routes/web.php` (and `routes/api.php`)

Update all school lookup endpoints:

```php
// Search by registration number OR code
Route::get('/api/schools/search/{identifier}', function ($identifier) {
    $school = \App\Models\School::where('registration_number', $identifier)
        ->orWhere('code', $identifier)
        ->first();
    
    return response()->json($school);
});
```

---

## Complete Solution Files

### 1. Create Migration

```bash
php artisan make:migration add_registration_number_to_schools_table
```

### 2. Run Migration

```bash
php artisan migrate
```

### 3. Update Routes (web.php)

Change line 749:
```php
$school = \App\Models\School::where('registration_number', $schoolCode)
    ->orWhere('code', $schoolCode)
    ->first();
```

### 4. Update Model (School.php)

Add `registration_number` to fillable array.

### 5. Update Filament Resource (SchoolResource.php)

Add registration_number fields to form and table.

---

## Your CSV Format - NO CHANGES NEEDED

```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,S0108,ACSEE
```

**Perfect as-is! System now understands it.**

---

## Testing the Solution

### 1. Create test school with registration number:

```bash
php artisan tinker
```

```php
App\Models\School::create([
    'code' => 'TEST001',
    'registration_number' => 'S0108',
    'name' => 'Test School',
    'region_id' => 1,
    'district_id' => 53,
    'school_type' => 'SECONDARY'
])
```

### 2. Test import lookup:

```php
$school = App\Models\School::where('registration_number', 'S0108')->first();
echo $school->name;  // Should output: Test School
```

### 3. Try CSV import:

Your original CSV will now work perfectly!

---

## Benefits of This Approach

✅ **No CSV changes** - Keep your format exactly as-is
✅ **Official numbers preserved** - Registration numbers stored accurately
✅ **Backward compatible** - Still searches by code if needed
✅ **Proper data model** - Separates internal codes from official numbers
✅ **Scalable** - Works for any number of schools
✅ **Professional** - Follows database best practices

---

## Summary

Instead of changing your CSV, we:
1. Add `registration_number` column to schools table
2. Create schools with their official registration numbers (S0108, etc.)
3. Update import to lookup by registration_number
4. Your CSV format stays unchanged ✅

This is the proper solution!

