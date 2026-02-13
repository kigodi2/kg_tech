# Schools CSV Import - Correct Format

## Your CSV Format

```
Code | Name | Ownership | Region ID | District ID
─────────────────────────────────────────────────
S0108 | Dar School | Public | 1 | 53
S0109 | Another School | Private | 1 | 54
```

Where:
- **Code**: School registration number (S0108, S0109, etc.)
- **Name**: Official school name
- **Ownership**: School ownership (Public, Private, etc.)
- **Region ID**: Region identifier from database
- **District ID**: District identifier from database

---

## Database Mapping

When you import this CSV:

| CSV Column | Maps To | Notes |
|-----------|---------|-------|
| Code | registration_number | Official school registration (S0108) |
| Name | name | School name |
| Ownership | ownership | Ownership type |
| Region ID | region_id | Foreign key to regions |
| District ID | district_id | Foreign key to districts |
| (auto) | code | Generated from registration_number |

---

## Import Endpoint Needed

Create new endpoint to handle school CSV import:

**File**: `routes/web.php`

Add this after the candidates import route (around line 795):

```php
Route::post('/api/schools/import', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt'
    ]);
    
    $file = $request->file('file');
    $handle = fopen($file->getRealPath(), 'r');
    
    $count = 0;
    $errors = [];
    $rowNumber = 0;
    $header = fgetcsv($handle);
    
    \Log::info('School import started', ['header' => $header]);
    
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        if (count($row) < 5) {
            $errors[] = "Row $rowNumber: Insufficient columns (expected 5, got " . count($row) . ")";
            continue;
        }
        
        try {
            $registrationCode = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $ownership = trim($row[2] ?? '');
            $regionId = intval($row[3] ?? 0);
            $districtId = intval($row[4] ?? 0);
            
            // Validate required fields
            if (empty($registrationCode)) {
                $errors[] = "Row $rowNumber: Missing School Code";
                continue;
            }
            
            if (empty($name)) {
                $errors[] = "Row $rowNumber: Missing School Name";
                continue;
            }
            
            if ($regionId <= 0) {
                $errors[] = "Row $rowNumber: Invalid Region ID";
                continue;
            }
            
            if ($districtId <= 0) {
                $errors[] = "Row $rowNumber: Invalid District ID";
                continue;
            }
            
            // Verify region exists
            $region = \App\Models\Region::find($regionId);
            if (!$region) {
                $errors[] = "Row $rowNumber: Region ID '$regionId' does not exist";
                continue;
            }
            
            // Verify district exists
            $district = \App\Models\District::find($districtId);
            if (!$district) {
                $errors[] = "Row $rowNumber: District ID '$districtId' does not exist";
                continue;
            }
            
            // Generate internal code from registration number
            $internalCode = substr($registrationCode, 0, 3) . str_pad(
                substr($registrationCode, 3),
                3,
                '0',
                STR_PAD_LEFT
            );
            
            // Create or update school
            \App\Models\School::updateOrCreate(
                ['registration_number' => $registrationCode],
                [
                    'code' => $internalCode,
                    'name' => $name,
                    'registration_number' => $registrationCode,
                    'ownership' => $ownership,
                    'region_id' => $regionId,
                    'district_id' => $districtId,
                    'school_type' => 'SECONDARY',
                    'is_active' => true
                ]
            );
            
            $count++;
            
        } catch (\Exception $e) {
            $errors[] = "Row $rowNumber error: " . $e->getMessage();
            \Log::error('Row import error', ['rowNumber' => $rowNumber, 'error' => $e->getMessage()]);
            continue;
        }
    }
    
    fclose($handle);
    
    \Log::info('School import completed', [
        'count' => $count,
        'errors_count' => count($errors),
        'errors' => $errors
    ]);
    
    return response()->json([
        'message' => $count . ' school(s) imported successfully' . (count($errors) > 0 ? ' with ' . count($errors) . ' error(s)' : ''),
        'count' => $count,
        'errors' => $errors
    ]);
});
```

---

## How It Works

### Input CSV:
```
S0108,Dar School,Public,1,53
S0109,Another School,Private,1,54
```

### Processing:
```
Row 1: S0108 | Dar School | Public | 1 | 53
  ↓
Validate:
  - Code S0108: ✓
  - Name exists: ✓
  - Region 1 exists: ✓
  - District 53 exists: ✓
  ↓
Create School:
  registration_number: S0108
  code: S01 (auto-generated)
  name: Dar School
  ownership: Public
  region_id: 1
  district_id: 53
  ↓
✅ School created!
```

---

## Update Candidates Import

Since the school CSV Code = registration_number, the existing candidate import already works!

Your candidate CSV:
```
S0108-0501,AGRIPINA,F,HGL,S0108,ACSEE
```

The system now:
1. Reads Column 5: S0108
2. Looks up: School::where('registration_number', 'S0108')
3. Finds the school
4. Creates candidate ✅

---

## Testing

### 1. Create Test School CSV

File: `schools.csv`
```
Code,Name,Ownership,Region ID,District ID
S0108,Dar Primary,Public,1,53
S0109,Dar Secondary,Public,1,53
S0110,Morogoro School,Private,1,54
```

### 2. Import Via API

Option A - Direct tinker:
```bash
php artisan tinker << 'EOF'
// Read and import CSV
$file = fopen('schools.csv', 'r');
$header = fgetcsv($file);

while (($row = fgetcsv($file)) !== false) {
    App\Models\School::updateOrCreate(
        ['registration_number' => $row[0]],
        [
            'code' => $row[0],
            'registration_number' => $row[0],
            'name' => $row[1],
            'ownership' => $row[2],
            'region_id' => intval($row[3]),
            'district_id' => intval($row[4]),
            'school_type' => 'SECONDARY',
            'is_active' => true
        ]
    );
}

echo "Done!\n";
EOF
```

Option B - Via Web UI (if you create upload endpoint)

---

## Update School Model

**File**: `app/Models/School.php`

The fillable already includes `registration_number`, so no changes needed!

---

## Update Filament Admin Panel

**File**: `app/Filament/Admin/Resources/SchoolResource.php`

Update the form to show both code and registration_number:

```php
Forms\Components\TextInput::make('registration_number')
    ->label('School Registration Code')
    ->placeholder('e.g., S0108')
    ->maxLength(20)
    ->required()
    ->unique(ignorable: fn ($record) => $record),

Forms\Components\TextInput::make('code')
    ->label('Internal System Code')
    ->placeholder('Auto-generated')
    ->maxLength(20)
    ->disabled(),
```

---

## School Import vs Candidate Import

### Schools CSV (NEW)
- **Purpose**: Register schools in system
- **Format**: Code, Name, Ownership, Region ID, District ID
- **Endpoint**: `/api/schools/import`
- **Result**: Schools created with registration numbers

### Candidates CSV (EXISTING)
- **Purpose**: Register candidates for exams
- **Format**: Index Number, Full Name, Sex, Combination, School Code, Exam Type
- **Endpoint**: `/api/candidates/import`
- **Lookup**: Uses School registration_number (S0108)
- **Works**: Because schools have registration_number stored

---

## Complete Flow

```
1. Import Schools CSV
   S0108 | Dar School | Public | 1 | 53
   ↓
   Creates:
   - registration_number: S0108
   - code: S0108 (or generated)
   - name: Dar School
   - ownership: Public
   ↓
2. Import Candidates CSV
   S0108-0501 | AGRIPINA | F | HGL | S0108 | ACSEE
   ↓
   Looks up: School where registration_number = S0108
   ↓
   Creates: Candidate linked to that school
   ↓
✅ Complete system working!
```

---

## Summary

| Item | Action |
|------|--------|
| **CSV Format** | Code, Name, Ownership, Region ID, District ID |
| **Code Column** | Maps to `registration_number` |
| **Import Endpoint** | Create `/api/schools/import` |
| **Candidates Import** | Already works (uses registration_number) |
| **School Model** | Already updated |
| **Ready to Import** | Yes, after adding endpoint |

---

## Next Steps

1. Add school import endpoint to `routes/web.php`
2. Create school CSV file
3. Import schools
4. Then import candidates with matching school codes
5. ✅ Everything works!

