# District Candidates Registration Template - Implementation Guide

## Overview

A new **District Candidates Registration** template has been implemented at `/registration/candidates-by-district`. This feature allows you to import candidates from CSV files while automatically registering any missing schools from the same district.

## Features

### 1. **District-Based Import**
- Select a specific district before importing
- Only candidates from that district are imported
- Automatically registers schools not yet in the system

### 2. **CSV Processing**
- Flexible column mapping for CSV files
- Supports multiple column name variations:
  - **School Code**: `school_code`, `school`, `center_no`, `centre_no`
  - **Candidate ID**: `candidate_id`, `index_number`, `candidate_no`
  - **Full Name**: `full_name`, `candidate_full_name`, `name`
  - **Gender**: `gender`, `sex`
  - **Exam Type**: `exam_type`, `examination_type`, `exam`
  - **Exam Year**: `exam_year`, `year`, `year_label`

### 3. **Auto-Registration**
- Schools not found in the database are automatically created
- Schools are registered to the selected district
- Inherits district's region automatically
- Default ownership set to "GOVERNMENT"

### 4. **Duplicate Handling**
- Existing candidates are skipped (not re-imported)
- Existing schools are linked (not re-registered)
- Statistics provided for all outcomes

### 5. **Live Preview**
- CSV file preview before import
- Shows registered vs. new schools
- Displays first 10 records as sample
- Real-time status of each candidate

## Access

1. Navigate to **Registration > Import by District**
2. Or from the dashboard, click **Import by District** button
3. URL: `/registration/candidates-by-district`

## Usage Steps

### Step 1: Download Template
1. Click the **Download Template** button
2. A sample CSV file downloads with required columns
3. Edit the file with your candidate data

### Step 2: Select District
1. Click on the **Select District** dropdown
2. Search for your district by name
3. Select the district
4. The interface shows registered schools in that district

### Step 3: Upload CSV File
1. Ensure district is selected (required)
2. Click file input or drag-and-drop CSV file
3. File is immediately parsed and previewed
4. You'll see:
   - Number of records in the CSV
   - Schools that will be auto-registered
   - Registered schools in the district

### Step 4: Review Preview
- Check the preview table for accuracy
- Identify any new schools (marked with <i class="fas fa-plus"></i>)
- Verify candidate information

### Step 5: Import
1. Click **Import Candidates** button
2. System processes:
   - Creates new schools (if needed)
   - Registers candidates
   - Performs ACSEE registration (if specified)
3. Results displayed immediately:
   - Schools registered
   - Candidates imported
   - Duplicates skipped

## CSV Template Format

### Required Columns

| Column | Example | Notes |
|--------|---------|-------|
| school_code | S0861 | School code/center number |
| candidate_id | S0861-0001 | Unique candidate identifier |
| full_name | ABBY JACKSON MARUA | Full candidate name |
| gender | M | M or F |
| exam_type | ACSEE | ACSEE, CSEE, or PSLE |
| exam_year | 2026 | Exam year (optional if using current active year) |

### Sample CSV

```csv
school_code,candidate_id,full_name,gender,exam_type,exam_year
S0861,S0861-0001,ABBY JACKSON MARUA,M,ACSEE,2026
S0861,S0861-0002,ABDUL RAZAQ HAMZA MWINYIJUMA,M,ACSEE,2026
S0862,S0862-0001,JOHN PAUL SHEM,F,ACSEE,2026
S0863,S0863-0001,NEW SCHOOL STUDENT,M,ACSEE,2026
```

In this example:
- S0861 and S0862 might already exist
- S0863 will be automatically created

## Info Cards

### Three cards show current state:

1. **Registered Schools** - Schools already in the selected district
2. **CSV Preview** - Number of records in uploaded CSV
3. **To Register** - New schools that will be auto-registered

## Auto-Registration Details

When a school is auto-registered:

1. **School Code** - From CSV's `school_code` column
2. **School Name** - "Imported School - [CODE]" (editable later)
3. **District** - Selected district
4. **Region** - Inherited from district
5. **Ownership** - Set to "GOVERNMENT" (editable later)
6. **Status** - Marked as active

## Processing Results

After import, you'll see statistics:

- **Schools Registered** - New schools created
- **Schools Skipped** - Schools already existed
- **Candidates Imported** - Successfully registered candidates
- **Candidates Skipped** - Duplicates not imported

## ACSEE Registration

When candidates are imported with `exam_type = ACSEE`:

1. Automatic exam registration is created
2. Links to specified exam year or active year
3. Ready for subject selection
4. Can be managed from Mark Entry

## API Endpoints

### 1. Get All Districts
```
GET /api/districts
```
Returns districts with school counts.

### 2. Get District Schools
```
GET /api/districts/{districtId}/schools
```
Returns schools registered in a district.

### 3. Import by District
```
POST /api/registration/import-by-district
```

**Request:**
```json
{
  "file": "<CSV file>",
  "district_id": "<district_id>"
}
```

**Response:**
```json
{
  "schools_registered": 2,
  "schools_skipped": 1,
  "candidates_imported": 45,
  "candidates_skipped": 3,
  "errors": []
}
```

## Files Created/Modified

### New Files
- `resources/views/registration/candidates-by-district.blade.php` - Main template
- `app/Http/Controllers/DistrictCandidateImportController.php` - Backend controller

### Modified Files
- `routes/web.php` - Added route for new view
- `routes/api.php` - Added API endpoints
- `resources/views/registration/dashboard.blade.php` - Added quick action button

## Error Handling

### Common Issues

1. **No districts found**
   - Ensure districts are created in `/registration/districts`

2. **School code mismatch**
   - Verify CSV school codes match exact format
   - Case-sensitive comparison

3. **Invalid CSV format**
   - Download template and follow exact format
   - Headers are case-insensitive (auto-normalized)

4. **Transaction rollback**
   - If any error occurs, entire import is rolled back
   - Check error details in response

## Performance

- Handles large CSV files efficiently
- Transaction-based (atomic operations)
- Preview generated in-browser (no server load)
- Batch processing for performance

## Security

- CSRF token validation on all POST requests
- User authentication required
- Authorization checks performed
- Audit logging available

## Future Enhancements

Possible improvements:
- Custom school name mapping
- Combination/subject auto-selection
- Validation rules for candidates
- Batch scheduling for large imports
- Email notifications on completion

## Troubleshooting

### Schools not showing as registered
- Verify district is selected
- Check if schools are in different district
- Reload page to refresh cache

### Candidates not importing
- Check candidate_id is unique
- Verify school_code exists or will be created
- Check gender field (M or F only)

### ACSEE registration failing
- Ensure exam year exists in system
- Check ACSEE exam type is configured
- Verify exam year is not archived

## Support

For issues:
1. Check error message details
2. Review CSV format against template
3. Verify data in system matches CSV
4. Check browser console for JavaScript errors

## Related Documentation

- [Candidates Management](/ACSEE_CRUD_IMPLEMENTATION.md)
- [School Registration](/DISTRICT_BULK_IMPORT_IMPLEMENTATION.md)
- [ACSEE System Setup](/ACSEE_MARK_IMPORT_IMPLEMENTATION.md)
