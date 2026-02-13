# ✅ Schools CSV - Correct Format with Region/District Codes

## CSV Format (CORRECT)

```
Code | Name | Ownership | Region Code | District Code
──────────────────────────────────────────────────────
S0108 | Dar Primary | GOVERNMENT | MO09 | MO0901
S0109 | Dar Secondary | GOVERNMENT | MO09 | MO0902
S0110 | Iringa School | NON-GOVERNMENT | IR07 | IR0701
```

Where:
- **Code**: School registration number (S0108, S0109, etc.)
- **Name**: School name
- **Ownership**: GOVERNMENT or NON-GOVERNMENT
- **Region Code**: Auto-generated region code (MO09, DO06, IR07, etc.)
- **District Code**: Auto-generated district code (MO0901, MO0902, etc.)

---

## Available Codes

### Region Codes
```
MO09  → MOROGORO
DO06  → DODOMA
IR07  → IRINGA
MT08  → MTWARA
LI10  → LINDI
TA11  → TABORA
TAN12 → TANGA
SI13  → SINGIDA
```

### District Codes (Example for Morogoro - MO09)
```
MO0901 → MOROGORO MC
MO0902 → MOROGORO DC
MO0903 → KILOLO DC
MO0904 → MUFINDI DC
MO0905 → MAFINGA TC
```

---

## How Import Works

### Input CSV:
```csv
Code,Name,Ownership,Region Code,District Code
S0108,Dar School,GOVERNMENT,MO09,MO0901
S0109,Another School,NON-GOVERNMENT,IR07,IR0701
```

### Processing:
```
Row 1: S0108 | Dar School | GOVERNMENT | MO09 | MO0901
  ↓
Validate:
  - Code S0108: ✓
  - Name exists: ✓
  - Region code MO09 exists: ✓
  - District code MO0901 exists: ✓
  - District MO0901 belongs to region MO09: ✓
  ↓
Create School:
  registration_number: S0108
  code: S0108
  name: Dar School
  ownership: GOVERNMENT
  region_id: (ID of MO09 region)
  district_id: (ID of MO0901 district)
  ✓
```

---

## Database Mapping

### Lookup Process
```
CSV Column 4: MO09
  ↓
Database Query: Region::where('code', 'MO09')
  ↓
Returns: Region object with id=1
  ↓
Stores: region_id = 1

CSV Column 5: MO0901
  ↓
Database Query: District::where('code', 'MO0901')
  ↓
Returns: District object with id=53
  ↓
Stores: district_id = 53
```

---

## Validation Rules

When importing schools:

| Field | Rule | Check |
|-------|------|-------|
| Code | Required, Unique | School registration number |
| Name | Required | School name |
| Ownership | Optional | School ownership |
| Region Code | Required, exists | Must match region code in DB |
| District Code | Required, exists, belongs to region | Must match district code AND belong to specified region |

---

## Example Data

Create `schools.csv`:

```csv
Code,Name,Ownership,Region Code,District Code
S0108,Dar Primary School,GOVERNMENT,MO09,MO0901
S0109,Dar Secondary School,GOVERNMENT,MO09,MO0902
S0110,Morogoro Primary,NON-GOVERNMENT,MO09,MO0903
S0111,Iringa School,GOVERNMENT,IR07,IR0701
S0112,Dodoma School,GOVERNMENT,DO06,DO0601
```

---

## Import Methods

### Method 1: Via PHP Artisan (Tinker)

```bash
php artisan tinker << 'EOF'
$file = fopen('schools.csv', 'r');
$header = fgetcsv($file);

$count = 0;
while (($row = fgetcsv($file)) !== false) {
    if (empty(array_filter($row))) continue;
    
    $region = App\Models\Region::where('code', $row[3])->first();
    $district = App\Models\District::where('code', $row[4])->first();
    
    if (!$region || !$district) {
        echo "Skipped row: Region or District not found\n";
        continue;
    }
    
    App\Models\School::updateOrCreate(
        ['registration_number' => $row[0]],
        [
            'code' => $row[0],
            'registration_number' => $row[0],
            'name' => $row[1],
            'ownership' => $row[2],
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_type' => 'SECONDARY',
            'is_active' => true
        ]
    );
    $count++;
}

echo "$count schools imported\n";
EOF
```

### Method 2: Via API Endpoint

If you have a web UI:

```
POST /api/schools/import
Content-Type: multipart/form-data

file: schools.csv
```

---

## After Import: Candidates

Your candidates CSV works the same way:

```csv
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
```

The system:
1. Reads school code: S0108
2. Looks up: School where registration_number = 'S0108'
3. Finds the school imported above
4. Creates candidate linked to that school ✅

---

## Complete Flow

```
1. Import Schools CSV
   School Code Column: S0108
   Region Code Column: MO09 → Looks up region ID
   District Code Column: MO0901 → Looks up district ID
   ✓ School created with correct region_id and district_id

2. Import Candidates CSV
   School Code Column: S0108
   → Looks up: School where registration_number = 'S0108'
   → Finds: School with region_id and district_id from above
   → Creates: Candidate linked to school
   ✓ Candidate registered for exam
```

---

## Key Points

✅ **Use region CODES** (MO09, IR07, etc.) - Not IDs
✅ **Use district CODES** (MO0901, IR0701, etc.) - Not IDs
✅ **Both codes must exist** in database
✅ **District must belong to region** - System validates this
✅ **School code is registration number** - Same as before
✅ **Candidates lookup by registration number** - Already works

---

## Testing

### Step 1: Check Available Codes

```bash
php artisan tinker << 'EOF'
echo "REGIONS:\n";
App\Models\Region::all()->each(fn($r) => echo "  " . $r->code . " → " . $r->name . "\n");

echo "\nDISTRICTS (sample):\n";
App\Models\District::limit(10)->get()->each(fn($d) => echo "  " . $d->code . " → " . $d->name . "\n");
EOF
```

### Step 2: Create CSV

Use codes from the output above.

### Step 3: Import

Use one of the methods above.

### Step 4: Verify

```bash
php artisan tinker << 'EOF'
$school = App\Models\School::where('registration_number', 'S0108')->first();
echo "School: " . $school->name . "\n";
echo "Region ID: " . $school->region_id . "\n";
echo "District ID: " . $school->district_id . "\n";
EOF
```

---

## Summary

| Item | Value |
|------|-------|
| **CSV Columns** | 5: Code, Name, Ownership, Region Code, District Code |
| **Lookup** | By region CODE and district CODE (auto-generated) |
| **Validation** | Region exists, District exists, District belongs to region |
| **Result** | Schools created with correct IDs for relationships |
| **Ready** | Yes! Import endpoint updated |

Everything is ready! Use region and district codes in your CSV.

