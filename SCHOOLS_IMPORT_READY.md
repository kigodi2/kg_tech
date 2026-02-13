# ✅ Schools Import Ready - Complete Setup

## CSV Format (Correct)

```
Code | Name | Ownership | Region ID | District ID
─────────────────────────────────────────────────
S0108 | Dar Primary | Public | 1 | 53
S0109 | Dar Secondary | Public | 1 | 53
```

Where:
- **Code**: School registration number (S0108, S0109, etc.)
- **Name**: School name
- **Ownership**: School ownership type
- **Region ID**: ID from regions table
- **District ID**: ID from districts table

---

## What's Ready

✅ **Migration**: `registration_number` column added
✅ **School Model**: Updated with registration_number
✅ **Candidates Import**: Updated to use registration_number
✅ **School Import Endpoint**: `/api/schools/import` added to routes
✅ **Validation**: All required fields checked

---

## How to Import Schools

### Method 1: Via PHP Artisan

```bash
php artisan tinker << 'EOF'
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
EOF
```

### Method 2: Via API

If you have a UI upload form:

```
POST /api/schools/import
Content-Type: multipart/form-data

file: schools.csv
```

Response:
```json
{
    "message": "5 school(s) imported successfully",
    "count": 5,
    "errors": []
}
```

---

## Example: Test Data

Create `schools.csv`:

```csv
Code,Name,Ownership,Region ID,District ID
S0108,Dar Primary School,Public,1,53
S0109,Dar Secondary School,Public,1,53
S0110,Morogoro School,Private,1,54
S0111,Iringa School,Public,4,55
```

---

## How It All Works Together

### 1. Import Schools
```
CSV: S0108,Dar Primary,Public,1,53
  ↓
Database:
  registration_number: S0108
  code: S0108
  name: Dar Primary
  ownership: Public
  region_id: 1
  district_id: 53
```

### 2. Import Candidates
```
CSV: S0108-0501,AGRIPINA,F,HGL,S0108,ACSEE
  ↓
System looks up: School where registration_number = 'S0108'
  ↓
Finds school created above
  ↓
Creates candidate linked to that school
```

---

## Database State

### Schools Table
```
id | code  | registration_number | name             | ownership | region_id | district_id
───┼───────┼─────────────────────┼──────────────────┼───────────┼───────────┼────────────
1  | S0108 | S0108               | Dar Primary      | Public    | 1         | 53
2  | S0109 | S0109               | Dar Secondary    | Public    | 1         | 53
3  | S0110 | S0110               | Morogoro School  | Private   | 1         | 54
```

### Candidates Table (After Import)
```
id | candidate_id | full_name      | school_id | exam_type | status
───┼──────────────┼────────────────┼───────────┼───────────┼──────────
1  | S0108-0501   | AGRIPINA Y...  | 1         | ACSEE     | registered
2  | S0108-0502   | BERTHA O...    | 1         | ACSEE     | registered
```

---

## Validation Rules

When importing schools:

| Field | Rule | Error If |
|-------|------|----------|
| Code | Required, Unique | Missing or duplicate |
| Name | Required | Empty |
| Ownership | Optional | None |
| Region ID | Required, exists | Invalid or doesn't exist |
| District ID | Required, exists | Invalid or doesn't exist |

---

## Import Workflow

```
1. Prepare Schools CSV
   ├─ Code (registration number)
   ├─ Name
   ├─ Ownership
   ├─ Region ID (must exist in DB)
   └─ District ID (must exist in DB)

2. Import Schools
   ├─ Validate each row
   ├─ Check region/district exist
   └─ Create schools with registration_number

3. Prepare Candidates CSV
   ├─ Index Number
   ├─ Full Name
   ├─ Sex
   ├─ Combination
   ├─ School Code (must match school registration_number)
   └─ Exam Type

4. Import Candidates
   ├─ Look up school by registration_number
   ├─ Link candidate to school
   └─ Create candidate records

5. Both are now in system!
```

---

## Key Points

✅ **CSV Code column** = School registration number
✅ **Database registration_number** = Stores the official code
✅ **Candidates lookup** = Uses registration_number to find school
✅ **No code changes needed** = Everything already configured

---

## Testing

### Step 1: Create Sample School CSV

```bash
cat > schools.csv << 'EOF'
Code,Name,Ownership,Region ID,District ID
S0108,Test School,Public,1,53
EOF
```

### Step 2: Import

```bash
cd /home/prosmart-technologies/SOL/irms
php artisan tinker << 'EOF'
$file = fopen('schools.csv', 'r');
fgetcsv($file);

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
EOF
```

### Step 3: Verify

```bash
php artisan tinker << 'EOF'
App\Models\School::where('registration_number', 'S0108')->first();
EOF
```

Should return the school.

---

## Files Modified

1. **database/migrations/2026_02_03_add_registration_number_to_schools.php**
   - ✅ Already ran

2. **app/Models/School.php**
   - ✅ Updated fillable

3. **routes/web.php**
   - ✅ Updated candidates import (line 749)
   - ✅ Added schools import endpoint (line 798)

---

## Summary

| Component | Status | Details |
|-----------|--------|---------|
| **Migration** | ✅ Done | Column added |
| **Model** | ✅ Done | Fillable updated |
| **Candidates Import** | ✅ Done | Uses registration_number |
| **Schools Import** | ✅ Done | Endpoint created |
| **Ready to Use** | ✅ Yes | Fully functional |

---

## Next Steps

1. **Prepare schools CSV** with your actual data
2. **Import schools** using endpoint or tinker
3. **Import candidates** using existing endpoint
4. **Done** - Both are linked by registration_number ✅

Everything is ready to go!

