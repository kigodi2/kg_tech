# Districts Import Modal - Quick Start Guide

**Feature**: Import multiple districts from CSV with validation and error reporting  
**Status**: Ready to Use  
**Date**: 2026-02-15  

---

## How to Use (End Users)

### Step 1: Open Import Modal
1. Go to **Registration → Districts**
2. Click **Tools** dropdown → **Import Districts**
3. Modal opens with upload area

### Step 2: Download Template (Optional)
- Click **Download Template** to see CSV format
- Shows: Name, Region ID, Description, Status columns
- Shows example rows: Dar es Salaam, Arusha, Iringa

### Step 3: Prepare CSV File

```csv
Name,Region ID,Description,Status
Dar es Salaam,TR02,Coastal region,active
Arusha,AR03,Mountain region,active
Iringa,IR07,Mining region,active
Mbeya,MBY01,,active
```

**Rules**:
- `Name` (required): District name, max 255 chars
- `Region ID` (required): Numeric ID or region code (TR02, AR03, etc.)
- `Description` (optional): Max 500 chars
- `Status` (optional): 'active' or 'inactive'

### Step 4: Upload & Validate
1. Click upload area or select file
2. Click **Upload & Validate**
3. Wait for validation (no database changes yet)
4. See report: Total rows, valid, failed

### Step 5a: Import Valid Districts (No Errors)
- If "Status" = **Ready**: Click **Import Now**
- Confirm and wait for completion
- Success message shows count
- Table refreshes automatically

### Step 5b: Fix & Re-upload (Has Errors)
- If "Status" = **Fix Required**: Click **Download Errors**
- CSV file contains failed rows + error reasons
- Fix issues in your original CSV
- Click **Back to Upload**
- Upload corrected file and validate again

---

## CSV Format Reference

### Minimal (Required Fields Only)
```csv
Name,Region ID
Dar es Salaam,TR02
Arusha,AR03
```

### Full (All Fields)
```csv
Name,Region ID,Description,Status
Dar es Salaam,TR02,Coastal region,active
Arusha,AR03,Mountain region,active
Iringa,IR07,Mining region,inactive
```

### Region ID Formats
**Option 1: Region Code**
```csv
Name,Region ID
Dar es Salaam,TR02
Arusha,AR03
Iringa,IR07
Mbeya,MBY01
```

**Option 2: Numeric ID**
```csv
Name,Region ID
Dar es Salaam,1
Arusha,2
Iringa,3
Mbeya,4
```

**Option 3: Mixed (Both Work)**
```csv
Name,Region ID
Dar es Salaam,TR02
Arusha,2
Iringa,IR07
Mbeya,4
```

---

## Important Notes

### District Code
- **NOT imported** - System auto-generates from region code + sequence
- Example: If region is "TR02", generated codes are TR0201, TR0202, TR0203...

### Uniqueness
- District is unique by **name + region combination**
- Two different regions can have districts with same name
- But same region cannot have two districts with same name

### Duplicates
- **In file**: If same name+region appears twice, only first is imported
- **In database**: If name+region already exists, import skips it
- **Error reporting**: Shows which rows were skipped

### Validation Order
1. Required fields present (Name, Region ID)
2. Field length constraints
3. Region exists in system
4. No duplicates within file
5. No duplicates in database
6. Enum values valid (status)

---

## Error Types & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| Name is required | Empty name column | Add district name |
| Name exceeds 255 characters | Name too long | Shorten name |
| Region ID is required | Empty region column | Add region ID or code |
| Region ID {X} does not exist | Invalid numeric ID | Check correct region ID |
| Region code '{X}' does not exist | Invalid region code | Use correct code (TR02, AR03, etc.) |
| District name already exists in region | Duplicate | Use different name or region |
| Status must be active or inactive | Invalid status | Use only "active" or "inactive" |
| Description exceeds 500 characters | Description too long | Shorten description |

---

## Download Errors File

When import has errors:

1. Click **Download Errors** button
2. CSV file saves to computer: `districts-import-errors-YYYY-MM-DD-HHmmss.csv`
3. Open in Excel or text editor
4. Contains:
   - `row_number` - Which row in your file (1-based)
   - `name` - District name from file
   - `region_id` - Region ID from file
   - `description` - Description from file
   - `status` - Status from file
   - `error_messages` - What went wrong

5. Fix the issues in your original CSV
6. Upload the corrected file

---

## Example Workflows

### Workflow 1: Simple Import (All Valid)
```
1. Create CSV with 10 valid districts
2. Open Import Districts modal
3. Upload CSV
4. Click Upload & Validate
5. Report shows: 10 total, 10 valid, 0 failed
6. Status: Ready
7. Click Import Now
8. Success! 10 districts imported
9. Table refreshes
```

### Workflow 2: With Errors (Fix & Re-upload)
```
1. Create CSV with 10 districts
2. Open Import Districts modal
3. Upload CSV
4. Click Upload & Validate
5. Report shows: 10 total, 8 valid, 2 failed
6. Status: Fix Required
7. Click Download Errors
8. Open errors file - see rows 3 and 7 have issues
9. Fix rows 3 and 7 in original CSV
10. Click Back to Upload
11. Upload corrected CSV
12. Click Upload & Validate
13. Report shows: 10 total, 10 valid, 0 failed
14. Click Import Now
15. Success! 10 districts imported
```

### Workflow 3: Using Region Codes
```
CSV File:
Name,Region ID,Description,Status
Dar es Salaam,TR02,Coastal,active
Arusha,AR03,Mountain,active
Iringa,IR07,Mining,active

All rows valid (region codes TR02, AR03, IR07 exist)
Import succeeds
```

---

## Tips & Best Practices

✅ **DO**:
- Download template first to see correct format
- Test with small file (5-10 districts) before bulk import
- Use region codes if you know them (more reliable)
- Check numeric region IDs in system if using IDs
- Fix all errors before re-uploading
- Keep districts CSV files backed up

❌ **DON'T**:
- Try to import district codes (they're auto-generated)
- Import duplicate name+region combinations
- Use invalid region IDs or codes
- Use status values other than "active" or "inactive"
- Upload Excel files (.xlsx) - must be CSV
- Import districts that already exist in database

---

## Supported File Formats

**Formats**: CSV (Comma-Separated Values)  
**Extensions**: .csv, .txt  
**Encoding**: UTF-8 recommended  
**Max Size**: 10MB (typically supports 1000+ districts)  

---

## Troubleshooting

### "File must be CSV"
- Make sure extension is `.csv` or `.txt`
- Open in Excel: File → Save As → CSV (Comma delimited)
- Not `.xlsx`, `.xls`, or other formats

### "Region does not exist"
- Check region exists in system
- Verify region code spelling and case
- Or use numeric region ID instead

### "District already exists"
- Check if district with same name in same region already exists
- Either use different name or different region
- Or update existing district instead of importing

### Modal doesn't open
- Refresh page (Ctrl+F5)
- Clear browser cache
- Try different browser
- Check F12 console for JavaScript errors

### Import hangs/times out
- File might be too large
- Try uploading in smaller batches
- Check internet connection
- Check browser console for errors

---

## Contact Support

For issues with:
- **File format**: Check template and this guide
- **Regional data**: Contact admin for correct region IDs/codes
- **System errors**: Check browser console (F12)
- **Bug reports**: Include: CSV file sample, error messages, browser/OS info

---

**Status**: Ready to Use  
**Last Updated**: 2026-02-15  
**Version**: 1.0  

