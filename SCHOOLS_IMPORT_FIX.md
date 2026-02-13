# Schools Import Functionality - Implementation

## Issue
Schools import feature on the Registration > Schools page was not working - it showed a placeholder that returned 0 imported schools.

## Solution
Implemented full CSV import functionality for schools with proper parsing, validation, and error handling.

## Implementation Details

### Backend Endpoint
**File**: `routes/web.php` (Lines 248-327)

**Route**: `POST /api/schools/import`

**Functionality**:
1. Validates that a CSV file was provided (mimes: csv, txt)
2. Parses CSV file into rows and columns
3. Maps headers to lowercase with underscores
4. For each row:
   - Skips empty rows
   - Looks up district by code or name
   - Looks up region by ID or code
   - Checks if school already exists (skips duplicates)
   - Creates school record with all available data
   - Catches and logs errors per row

### CSV Format Support

**Required Columns** (as per download template):
```
Code, Name, Ownership, Region ID, District ID
```

**Column Descriptions**:
- `Code` - School code/number (required, must be unique)
- `Name` - School name (required)
- `Ownership` - PUBLIC, PRIVATE, GOVERNMENT (optional, defaults to PUBLIC)
- `Region ID` - Can be numeric region ID OR region CODE (optional)
- `District ID` - Can be numeric district ID OR district CODE (optional)

**Lookup Priority**:
- Region ID/District ID columns accept:
  1. **Numeric IDs** (e.g., 1, 2, 3) - looks up in regions/districts table by id
  2. **Region/District CODE** (e.g., IR07, IR0701) - looks up in table by code
  3. Code takes precedence if ID not found

**Example CSV (Using Codes - like your data)**:
```csv
Code,Name,Ownership,Region ID,District ID
S0203,IRINGA GIRLS' SECONDARY SCHOOL,GOVERNMENT,IR07,IR0701
S0325,LUGALO SECONDARY SCHOOL,GOVERNMENT,IR07,IR0701
S0445,MWEMBETOGWA SECONDARY SCHOOL,NON-GOVERNMENT,IR07,IR0701
```

**Example CSV (Using Numeric IDs)**:
```csv
Code,Name,Ownership,Region ID,District ID
SC001,Dar es Salaam Primary School,PUBLIC,1,5
SC002,Arusha Secondary School,GOVERNMENT,2,8
SC003,Mbeya High School,PRIVATE,3,12
```

**Mixing Both (Also Works)**:
```csv
Code,Name,Ownership,Region ID,District ID
S0203,IRINGA GIRLS' SECONDARY SCHOOL,GOVERNMENT,IR07,5
SC002,Arusha Secondary School,GOVERNMENT,2,IR0701
```

**Import Behavior**:
- Validates that Code and Name are provided
- Looks up Region ID and District ID in database
- Skips schools with duplicate codes silently
- Trims whitespace from all fields
- Creates schools with minimal required fields

### Backend Code Flow

```php
// 1. Validate file
$request->validate(['file' => 'required|file|mimes:csv,txt']);

// 2. Read and parse CSV
$csv = array_map('str_getcsv', file($path));

// 3. Extract headers
$headers = array_map('strtolower', str_replace(' ', '_', $csv[0]));

// 4. For each row:
//    - Get district_id from district table
//    - Get region_id from region table
//    - Check for duplicates
//    - Create school record

// 5. Return results
return response()->json([
    'message' => "Imported X school(s)",
    'count' => $imported,
    'errors' => $errors  // Per-row errors
]);
```

### Frontend Implementation
**File**: `resources/views/registration/schools.blade.php` (Lines 628-661)

**Improvements**:
1. Shows import count in success message
2. Displays any per-row errors
3. Resets file input after processing
4. Better error messages with error details

### School Model Fields (Import Supported)

| Field | Required | Default | Import Notes |
|-------|----------|---------|-------|
| code | Yes | - | Must be unique, imported from CSV |
| name | Yes | - | School name, imported from CSV |
| district_id | No | NULL | Looked up from District ID in CSV |
| region_id | No | NULL | Looked up from Region ID in CSV |
| ownership | No | PUBLIC | Imported from CSV, defaults to PUBLIC |
| is_active | No | true | Always set to true on import |

**Fields NOT imported** (use UI to update):
- school_type
- education_level
- address
- phone
- email
- principal_name

## Error Handling

**Validation Errors**:
- Missing file: "file" is required
- Wrong file type: File must be CSV or TXT
- Empty file: CSV file is empty
- File too small: Must have at least header + 1 data row

**Row-Level Errors**:
- Invalid district code/name: District not found (skipped)
- Invalid region ID/code: Region not found (skipped)
- Duplicate school code: School already exists (skipped)
- Missing required fields: Row skipped with error message

**Error Response**:
```json
{
  "message": "Imported 5 school(s)",
  "count": 5,
  "errors": [
    "Row 3: Duplicate entry for school code SC001",
    "Row 7: Invalid district code XX0001"
  ]
}
```

## Usage Steps

1. Go to Registration > Schools page
2. Click "Tools" dropdown
3. Select "Import CSV"
4. Prepare CSV file with school data
5. Select file
6. System imports and shows:
   - Number of schools imported
   - Any errors that occurred
7. Table automatically refreshes with new schools

## Testing Checklist

- [ ] Upload valid CSV with schools
- [ ] Verify count of imported schools matches
- [ ] Check that schools appear in table
- [ ] Test with duplicate school codes (should skip)
- [ ] Test with missing district (should skip)
- [ ] Test with invalid file type (should error)
- [ ] Test with empty CSV (should error)
- [ ] Test flexible column names
- [ ] Verify error messages display correctly
- [ ] File input resets after import

## Files Modified

1. **Backend**:
   - `routes/web.php` (Lines 248-327) - Full import implementation

2. **Frontend**:
   - `resources/views/registration/schools.blade.php` (Lines 628-661) - Improved error handling and feedback

## Related Functions

- `downloadTemplate()` - Generate CSV template
- `exportCSV()` - Export current schools to CSV
- `loadSchools()` - Refresh schools list after import
- `showMessage()` - Display success/error messages

## Future Enhancements

1. Add bulk address/phone/email updates
2. Implement school merge functionality
3. Add district/region assignment UI
4. Support for more school attributes
5. Import progress bar for large files
6. Batch processing for very large files (>1000 rows)
