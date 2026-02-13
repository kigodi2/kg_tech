# CSV Import Error Diagnosis & Solutions

## Your CSV Format
```
Index Number | Full Name | Sex | Combination | School ID | Exam Type
S0108-0501   | AGRIPINA YOHANA MAGANGA | F | HGL | S0108 | ACSEE
```

## Import Process Flow

```
1. User selects CSV file
    ↓
2. Check conflicts (if any candidates already exist)
    ↓
3. Parse CSV and validate:
    - Column count (must be exactly 6)
    - Required fields (Name, Sex, School Code, Exam Type)
    - School code exists in database
    ↓
4. Import candidates
    ↓
5. Show results (success/errors)
```

## Common Import Errors & Solutions

### ❌ Error 1: "Insufficient columns (expected 6, got X)"

**What this means**: Your CSV doesn't have exactly 6 columns

**Causes**:
- Extra blank columns
- Missing columns
- Column headers don't match expected structure
- Encoding issues (UTF-8 vs others)

**Solution**:
```
Expected order:
1. Index Number (candidate_id)
2. Full Name
3. Sex (F/M)
4. Combination (subject combination code)
5. School ID (or School Code)
6. Exam Type (ACSEE, CSEE, PSLE, etc.)
```

**Fix**: Clean your CSV to have exactly these 6 columns in this order.

---

### ❌ Error 2: "School code 'S0108' does not exist"

**What this means**: The school code in your CSV doesn't match any school in the database

**Your CSV has**: `S0108`
**Database schools**: Currently only have codes like `DSM001`, `DSM002`, `MGO001`, etc.

**Solution - Choose one**:

**Option A: Create the school first**
```php
// In browser console or via Filament admin:
// 1. Go to SETTINGS > Schools Management
// 2. Click "Add School"
// 3. Create school with code: S0108
// 4. Then retry the import
```

**Option B: Update your CSV**
```
Replace all instances of: S0108
With existing school code: DSM001 (or any school in database)

Your CSV would become:
S0108-0501, AGRIPINA YOHANA MAGANGA, F, HGL, DSM001, ACSEE
```

**Option C: Bulk create schools**
```
If you have many different school codes, create them first using:
- School bulk import feature
- Or direct database insertion via tinker
```

---

### ❌ Error 3: "Row X: Missing Full Name"

**What this means**: The Name column (column 2) is empty

**Causes**:
- Blank rows in your CSV
- Data in wrong column position
- Extra spaces/tabs making name appear empty

**Solution**:
- Check row X in your CSV file
- Ensure name is in column 2
- Remove any completely blank rows

---

### ❌ Error 4: "Row X: Missing Sex"

**What this means**: Column 3 (Sex) is empty or invalid

**Valid values**: `F` or `M`

**Solution**:
- Fill in sex as `F` or `M`
- Check capitalization (should be uppercase)

---

### ❌ Error 5: "Row X: Missing Exam Type"

**What this means**: Column 6 (Exam Type) is empty

**Valid values**: 
- `ACSEE` (Advanced Certificate of Secondary Education)
- `CSEE` (Certificate of Secondary Education)
- `PSLE` (Primary School Leaving Examination)

**Solution**:
- Fill in the exam type for this row
- Ensure correct spelling

---

## Your CSV Validation Checklist

Before importing, verify:

```
✓ CSV has exactly 6 columns in this order:
  1. Index Number
  2. Full Name
  3. Sex (F/M)
  4. Combination (can be null/empty for non-ACSEE)
  5. School ID/Code
  6. Exam Type

✓ All required fields are filled:
  - Full Name (never blank)
  - Sex (F or M, uppercase)
  - School ID/Code (must exist in database)
  - Exam Type (ACSEE, CSEE, or PSLE)

✓ School codes in your CSV exist in database:
  - Current schools: DSM001, DSM002, MGO001, MGO002, MGO003
  - Your CSV has: S0108 (DOES NOT EXIST - need to create or update)

✓ No extra columns or blank columns

✓ File encoding is UTF-8 (not Windows-1252, etc.)

✓ No blank rows at the end
```

---

## How to Check Database Schools

To see what schools currently exist:

**Method 1: Via Admin Panel**
1. Go to SETTINGS > Schools Management
2. View the schools list
3. Note the school codes

**Method 2: Via PHP Artisan**
```bash
php artisan tinker
# Then run:
App\Models\School::all()->pluck('code', 'name');
```

**Current schools in database:**
```
DSM001 → Dar es Salaam Primary School
DSM002 → Dar Secondary School
MGO001 → Morogoro Primary School
MGO002 → Morogoro Secondary School
MGO003 → Morogoro Combined School
```

---

## Solutions for Your Import

### Step 1: Identify Exact Errors

Try importing your CSV and take note of ALL error messages shown. They will list:
- Row numbers with errors
- Specific field that's missing or invalid
- School codes that don't exist

### Step 2: Choose Action

**If School Code is Wrong (Most Likely)**:

Option A - Create missing schools:
```
School code 'S0108' doesn't exist

Solution: 
1. Note down all unique school codes from your CSV
2. Create each one in Schools Management
3. Retry import
```

Option B - Update CSV with existing codes:
```
In your CSV editor (Excel/Google Sheets):
Find & Replace: S0108 → DSM001 (or appropriate school code)
Save as CSV
Retry import
```

**If Required Fields are Missing**:
- Fix the data in your CSV
- Add missing values (Sex, Exam Type, Names)
- Retry import

---

## Import Resolution Modes

When conflicts occur (candidate already exists), system shows options:

1. **Skip** (default): Keep existing candidate, don't overwrite
2. **Replace**: Replace existing with new data for that candidate
3. **Replace All**: Replace all conflicting candidates without asking again

---

## Detailed CSV Format Reference

| Column | Position | Name | Required | Example | Valid Values |
|--------|----------|------|----------|---------|--------------|
| 1 | First | Index Number | Optional | S0108-0501 | Any unique ID |
| 2 | Second | Full Name | **YES** | AGRIPINA YOHANA MAGANGA | Any text |
| 3 | Third | Sex | **YES** | F | F or M |
| 4 | Fourth | Combination | Optional | HGL | Subject codes (ACSEE only) |
| 5 | Fifth | School ID/Code | **YES** | S0108 | Must exist in schools table |
| 6 | Sixth | Exam Type | **YES** | ACSEE | ACSEE, CSEE, PSLE |

---

## Next Steps

1. **Check your CSV file** - Open in text editor and verify format matches
2. **Try importing** - Click "Import CSV" and note exact error messages
3. **Based on errors**:
   - If "School code doesn't exist" → Create school first
   - If "Missing" errors → Fill those columns
   - If column errors → Verify 6 columns in correct order
4. **Retry import** with corrected CSV

## Example: Fixing Your CSV

**Original (problematic)**:
```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
```

**Fixed (if S0108 doesn't exist)**:
```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,DSM001,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,DSM001,ACSEE
```

---

## Still Getting Errors?

1. Open browser DevTools (F12)
2. Go to Console tab
3. Try import again
4. Check console for detailed error messages
5. Share those messages for specific debugging
