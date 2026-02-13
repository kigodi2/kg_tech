# District Candidates Import - Quick Start

## What Was Created

A new registration template that imports candidates from CSV and **auto-registers missing schools**.

## Where to Find It

- **URL**: `/registration/candidates-by-district`
- **Menu**: Registration Dashboard → Import by District
- **Direct Link**: Quick Actions button on registration dashboard

## How to Use (5 Steps)

### 1. Go to the Page
Navigate to `/registration/candidates-by-district`

### 2. Download Template
Click the **Template** button to download a CSV template

### 3. Prepare Your Data
Fill the CSV with:
- **school_code** - e.g., S0861
- **candidate_id** - e.g., S0861-0001
- **full_name** - e.g., JOHN DOE
- **gender** - M or F
- **exam_type** - ACSEE, CSEE, or PSLE
- **exam_year** - e.g., 2026

### 4. Select District
Choose your district from the dropdown

### 5. Upload & Import
- Click file input and select your CSV
- Review preview to see registered schools vs. new schools
- Click **Import Candidates**
- Done! Schools auto-registered, candidates imported

## Key Features

✅ **Auto-Register Schools** - Missing schools are created automatically  
✅ **Flexible CSV Format** - Multiple column name variations supported  
✅ **Live Preview** - See what will be imported before confirming  
✅ **Smart Duplicates** - Skips candidates/schools that already exist  
✅ **ACSEE Ready** - Automatically registers ACSEE candidates  
✅ **Statistics** - Shows counts of registered vs. skipped items  

## CSV Example

```
school_code,candidate_id,full_name,gender,exam_type,exam_year
S0861,S0861-0001,ABBY JACKSON MARUA,M,ACSEE,2026
S0861,S0861-0002,ABDUL RAZAQ HAMZA,M,ACSEE,2026
S0862,S0862-0001,NEW SCHOOL STUDENT,M,ACSEE,2026
```

Schools S0861 and S0862 will be auto-registered if missing.

## Technical Details

### Files Created
- `resources/views/registration/candidates-by-district.blade.php` - Frontend template
- `app/Http/Controllers/DistrictCandidateImportController.php` - Backend logic

### Routes Added
- `GET /registration/candidates-by-district` - Web view
- `POST /api/registration/import-by-district` - Import API
- `GET /api/districts` - Get districts list
- `GET /api/districts/{id}/schools` - Get district schools

### Database Operations
- Creates schools if they don't exist
- Creates candidates
- Registers ACSEE exam if specified
- Uses transactions (atomic operations)

## What Gets Auto-Registered

When importing a school not in the database:
- **Code** - From CSV
- **Name** - "Imported School - [CODE]"
- **District** - Selected district
- **Region** - From district
- **Ownership** - GOVERNMENT
- **Status** - Active

(School name can be edited later from `/registration/schools`)

## Column Mapping

The system automatically recognizes these column variations:

| What You Need | Accepts |
|---|---|
| School Code | school_code, school, center_no, centre_no |
| Candidate ID | candidate_id, index_number, candidate_no |
| Full Name | full_name, candidate_full_name, name |
| Gender | gender, sex |
| Exam Type | exam_type, examination_type, exam |
| Exam Year | exam_year, year, year_label |

So you can use CSV headers that match your data format!

## Response Example

After import, you see:
```json
{
  "schools_registered": 2,
  "schools_skipped": 1,
  "candidates_imported": 45,
  "candidates_skipped": 3,
  "errors": []
}
```

## Requirements

- Select district (mandatory)
- Upload CSV file (mandatory)
- Candidates with valid school_code
- CSV must have required columns

## Validation

- Duplicate candidates are skipped
- Empty rows are ignored
- Invalid gender (not M or F) skipped
- Missing school_code or candidate_id skipped

## Troubleshooting

### "Select a district first"
- Click district dropdown at top
- Select a district

### "Please select an exam year"
- CSV might be missing exam_year column
- System uses active year if not specified

### No schools showing
- Make sure you selected a district
- Reload page after selection

### Candidates not importing
- Check for duplicates (candidate_id already exists)
- Verify required columns are present
- Check candidate_id is unique

## Next Steps

After importing:
1. Verify candidates are registered
2. Edit school names if needed (`/registration/schools`)
3. Set up ACSEE subjects (`/registration/exam-types/ACSEE`)
4. Begin mark entry (`/mark-entry`)

## Support Documents

- Full guide: `DISTRICT_CANDIDATES_IMPORT_GUIDE.md`
- CSV format: See template download in the interface
- ACSEE subjects: Related ACSEE implementation docs

---

**TL;DR**: Select district → Upload CSV → Auto-registers missing schools → Import candidates → Done!
