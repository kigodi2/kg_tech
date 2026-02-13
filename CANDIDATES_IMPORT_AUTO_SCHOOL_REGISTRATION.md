# Auto-School Registration for Candidates Import

## Overview

The original `/registration/candidates` template has been enhanced to **automatically register missing schools** during CSV import, making it even more powerful and flexible.

## What Changed

### 1. Enhanced Import API Endpoint

**Location**: `routes/api.php` - `POST /api/candidates/import`

**New Feature**: Auto-registration of missing schools

When importing candidates:
- If a school code (from CSV column 5) is not found in the database
- And it's not a numeric ID
- The system automatically creates the school with:
  - **Code**: The school code from CSV
  - **Name**: "Imported School - [CODE]" (editable later)
  - **District**: First available district in system (default)
  - **Region**: First available region in system (default)
  - **Ownership**: "GOVERNMENT"
  - **Status**: Active

### 2. Updated UI Messages

**File**: `resources/views/registration/candidates.blade.php`

**Changes**:
- Template download message now mentions auto-registration
- Import modal shows informational banner about auto-registration
- Users are informed schools will be handled automatically

## How It Works

### CSV Import Flow

```
Upload CSV
    ↓
Parse Headers
    ↓
For Each Row:
  ├─ Extract school_code from column 5
  ├─ Check if school exists
  │   ├─ Yes: Use existing school
  │   ├─ No: Auto-create school
  ├─ Create/Update candidate
  ├─ If ACSEE: Register for exam
  └─ Commit transaction
    ↓
Return Statistics
```

### Auto-Registration Logic

```php
// Check if school exists
$schoolId = intval($schoolCodeOrId);
if (!$schoolId || !School::find($schoolId)) {
    // Try to find by code or registration number
    $school = School::where('registration_number', $schoolCodeOrId)
        ->orWhere('code', $schoolCodeOrId)
        ->first();
    
    // If not found and looks like a code (not numeric), create it
    if (!$school && !empty($schoolCodeOrId) && !is_numeric($schoolCodeOrId)) {
        $school = School::create([
            'code' => $schoolCodeOrId,
            'name' => "Imported School - {$schoolCodeOrId}",
            'district_id' => $defaultDistrict->id,
            'region_id' => $defaultRegion->id,
            'ownership' => 'GOVERNMENT',
            'is_active' => true,
        ]);
    }
}
```

## Use Cases

### Scenario 1: Import from Printed Results
You have a printed exam results sheet with school codes (S0861, S0862, etc.)

1. Type candidate data into spreadsheet with school codes
2. Save as CSV
3. Upload to `/registration/candidates`
4. System creates any missing schools automatically
5. All candidates imported with proper school linkage

### Scenario 2: Bulk Registration for New Academic Year
New schools join the exam system:

1. Prepare CSV with candidates from new and existing schools
2. Upload to `/registration/candidates`
3. System creates schools for any not in database
4. Candidates all registered in one operation
5. Can edit school details later

### Scenario 3: Data Migration
Consolidating candidates from multiple sources:

1. Combine data from multiple files/systems
2. Create unified CSV
3. Upload to `/registration/candidates`
4. System handles both existing and new schools
5. No errors from unknown school codes

## CSV Format

### Required Columns (in order)
1. `candidate_id` - Unique identifier (or auto-generated)
2. `full_name` - Candidate full name
3. `gender` - M or F
4. `combination` - Subject combination (ACSEE only, optional)
5. `school_code` - School code (auto-registered if missing)
6. `exam_type` - PSLE, CSEE, or ACSEE (optional)
7. `exam_year` - Year label (optional)

### Example
```csv
candidate_id,full_name,gender,combination,school_code,exam_type,exam_year
S1378-0001,John Doe,M,CBE,S1378,ACSEE,2026
S1378-0002,Jane Smith,F,HGE,S1378,ACSEE,2026
S1379-0001,Peter Brown,M,PCB,S1379,ACSEE,2026
```

- S1378 exists → Linked to existing school
- S1379 doesn't exist → Auto-created with name "Imported School - S1379"

## Benefits

✅ **Faster Import** - No need to pre-create schools  
✅ **Fewer Errors** - Can't reference non-existent schools  
✅ **Flexible** - Works with any school code format  
✅ **Traceable** - Schools logged with "Imported School" prefix  
✅ **Editable** - Can rename schools later in `/registration/schools`  

## Important Notes

### School Default Location
Auto-registered schools are assigned to:
- **District**: First district in system (alphabetically)
- **Region**: First region in system (alphabetically)

If you need specific district/region assignment:
- Create schools first in `/registration/schools`
- Or import and edit schools afterward

### School Names
Auto-registered schools get default names like "Imported School - S1378"

You can:
- Edit names in `/registration/schools` after import
- Bulk update if needed

### Logging
All auto-registrations are logged in Laravel logs:
```
Auto-registered missing school
school_code: S1379
school_id: 45
```

## Comparison: Both Import Methods

### `/registration/candidates` (Original, Now Enhanced)
- ✅ Auto-registers missing schools
- ✅ Per-candidate import
- ✅ Conflict detection/resolution
- ✅ ACSEE auto-registration
- ✅ Flexible CSV format
- ❌ Requires district selection after (for school assignment)

### `/registration/candidates-by-district` (New)
- ✅ Auto-registers schools to specific district
- ✅ District filtering
- ✅ Live preview
- ✅ School status display
- ✅ ACSEE auto-registration
- ✓ Requires district pre-selection

## Workflow Recommendation

### Approach 1: Use Original Template (Simpler)
1. Prepare CSV with all candidates and school codes
2. Upload to `/registration/candidates`
3. System auto-registers schools (default location)
4. Edit school details later if needed

### Approach 2: Use District Template (More Control)
1. Select target district
2. Prepare CSV with candidates and school codes
3. Upload to `/registration/candidates-by-district`
4. Schools auto-registered to selected district
5. Schools already appear with correct location

## Database Impact

### Schools Table
New schools created with:
- `code` - From CSV
- `name` - "Imported School - [CODE]"
- `district_id` - Default district
- `region_id` - Default region
- `ownership` - "GOVERNMENT"
- `is_active` - 1 (true)

### Candidates Table
No changes to schema - existing fields used

### Exam Registrations
ACSEE candidates linked normally

## Error Handling

### Invalid School Codes
- Numeric-only values: Not auto-created (treated as school ID)
- Empty values: Skipped
- Valid codes: Auto-created

### Validation
- CSV file required
- Exam year required
- School code can be code or numeric ID
- Duplicate candidates skipped

## After Import

### Manage Auto-Created Schools
1. Go to `/registration/schools`
2. Find "Imported School - [CODE]" entries
3. Edit name, district, region, ownership as needed
4. Save changes

### Verify Import
- Check candidates count
- Verify school linkages
- Confirm ACSEE registrations
- Review any error logs

## Related Features

- **Original Template**: `/registration/candidates`
- **District Template**: `/registration/candidates-by-district`
- **School Management**: `/registration/schools`
- **ACSEE Registration**: Auto-linked during import if exam_type=ACSEE

## Technical Details

### Code Location
`routes/api.php`, lines 281-310

### Methods Involved
- `School::create()` - Create missing school
- `School::where()` - Find existing school
- `Candidate::updateOrCreate()` - Create/update candidate
- `CandidateController::registerForACSEE()` - ACSEE registration

### Transaction Handling
- Wrapped in DB transaction
- Rollback on critical error
- Partial imports possible (per-row processing)

### Performance
- Single-pass CSV processing
- Efficient school lookups
- Minimal database queries

## Files Modified

1. **routes/api.php**
   - Enhanced POST /api/candidates/import endpoint
   - Added auto-registration logic
   - Added logging

2. **resources/views/registration/candidates.blade.php**
   - Updated template download message
   - Added info banner to import modal
   - Enhanced user guidance

## Testing Checklist

- [ ] Upload CSV with unknown school codes
- [ ] Verify schools are auto-created
- [ ] Check default district/region assigned
- [ ] Verify candidates linked to auto-created schools
- [ ] Confirm school names have "Imported School -" prefix
- [ ] Test with numeric school IDs (should not auto-create)
- [ ] Verify ACSEE registration still works
- [ ] Check error handling for invalid CSV
- [ ] Review Laravel logs for auto-registration entries
- [ ] Edit auto-created school names/details after import

## Support

For questions or issues:
1. Check import logs at `/storage/logs/`
2. Verify CSV format matches template
3. Ensure exam year is selected
4. Confirm school codes are not purely numeric
5. Review database for auto-created schools

---

**Status**: ✅ Complete  
**Date**: February 11, 2026  
**Feature**: Auto-School Registration for Candidates Import
