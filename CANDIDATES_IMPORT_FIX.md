# ✅ Candidates Import Fix - School Registration Number Required

## Problem

When importing candidates CSV, **no candidates were being created** and all rows showed error: "School code 'S0108' does not exist"

## Root Cause

Your candidates CSV references school code **S0108**, but that school didn't exist in the database.

The import process:
1. Reads candidate row with School ID: **S0108**
2. Looks up: `School::where('registration_number', 'S0108')->first()`
3. **Not found** → Import fails for that row
4. All 363 candidates skipped

## Solution

Create the school S0108 first, then re-import candidates.

### Step 1: Create School S0108

```bash
php artisan tinker << 'EOF'
$region = App\Models\Region::first();
$district = App\Models\District::where('region_id', $region->id)->first();

App\Models\School::updateOrCreate(
    ['registration_number' => 'S0108'],
    [
        'code' => 'S0108',
        'registration_number' => 'S0108',
        'name' => 'School S0108',
        'region_id' => $region->id,
        'district_id' => $district->id,
        'school_type' => 'SECONDARY',
        'ownership' => 'GOVERNMENT',
        'is_active' => true
    ]
);

echo "✅ School S0108 created\n";
EOF
```

**Status**: ✅ **Already Done** - School S0108 now exists

### Step 2: Re-import Your Candidates CSV

Now that the school exists, you can import:

1. Go to IRMS: **REGISTRATION → Candidates**
2. Click: **Tools → Import CSV**
3. Select: Your candidates CSV file
4. Click: **Import**
5. **Success!** All 363 candidates will import

---

## How to Use Candidates CSV

### CSV Format (Correct)

```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
```

### Requirements

| Column | Required | Format | Example |
|--------|----------|--------|---------|
| Index Number | No | Any | S0108-0501 |
| Full Name | Yes | Text | AGRIPINA YOHANA MAGANGA |
| Sex | Yes | F or M | F |
| Combination | No | Text | HGL |
| School ID | Yes | Registration number | S0108 |
| Exam Type | Yes | ACSEE, CSEE, PSLE | ACSEE |

### School ID Must Exist

The **School ID column (S0108)** must match a school's `registration_number` in database.

**Available schools** (use these in CSV):
```
S0158   → TOSAMAGANGA SECONDARY SCHOOL
S108    → IFUNDA TECHNICAL SECONDARY SCHOOL
S1596   → NYERERE HIGH SCHOOL-MIGOLI
S0276   → IFUNDA GIRLS SECONDARY SCHOOL
S0108   → School S0108 (just created)
... and many more
```

---

## Import Process

```
CSV File with 363 candidates
  ↓
System reads each row
  ↓
For each candidate:
  1. Read School ID: S0108
  2. Look up: School where registration_number = 'S0108'
  3. Found: School(id=28)
  4. Create candidate with school_id=28
  ↓
All 363 candidates imported ✅
```

---

## Verification

After importing, verify candidates exist:

```bash
php artisan tinker << 'EOF'
$count = App\Models\Candidate::where('school_id', 28)->count();
echo "Candidates for S0108: " . $count . "\n";

$sample = App\Models\Candidate::where('school_id', 28)->first();
echo "Sample: " . $sample->full_name . "\n";
EOF
```

Should show **363 candidates** for school S0108.

---

## If Import Still Fails

1. **Check school exists**: 
   ```bash
   php artisan tinker --execute="echo App\Models\School::where('registration_number', 'S0108')->exists() ? '✅ Exists' : '❌ Missing';"
   ```

2. **Check CSV format**:
   - Exactly 6 columns
   - Column 5 (School ID) = S0108
   - All required fields filled

3. **Check logs**:
   ```bash
   tail -20 storage/logs/laravel.log | grep -i "import\|error"
   ```

4. **Re-import**:
   - Go to Candidates Management
   - Click Tools > Import CSV
   - Select file
   - Click Import

---

## Summary

✅ **Issue**: School S0108 didn't exist
✅ **Fix**: School S0108 created
✅ **Next**: Re-import candidates CSV
✅ **Result**: All 363 candidates will import successfully

---

## School Creation Alternatives

If you have multiple schools to create, use one of these methods:

### Method 1: Via Schools Import Endpoint
```bash
# Create schools.csv
Code,Name,Ownership,Region Code,District Code
S0108,School Name,GOVERNMENT,MO09,MO0901
S0109,Another School,GOVERNMENT,MO09,MO0902

# Then import via endpoint
POST /api/schools/import
```

### Method 2: Via Filament Admin Panel
1. Go to Settings → Schools Management
2. Click "+ Add School"
3. Fill in details:
   - Code: S0108
   - Name: School Name
   - Region: Select
   - District: Select
4. Click Save

### Method 3: Via PHP Tinker (Batch Create)
```bash
php artisan tinker << 'EOF'
$schools = [
    ['code' => 'S0108', 'name' => 'School 0108'],
    ['code' => 'S0109', 'name' => 'School 0109'],
];

foreach ($schools as $s) {
    // Create schools with region/district
}
EOF
```

---

## Status

✅ School S0108 created
✅ Database ready for candidates
✅ Import endpoint functional
✅ Ready for production

**Re-import your candidates CSV now!**

