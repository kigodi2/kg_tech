# Schools Import Modal - Quick Start Guide

**Feature**: Import multiple schools from a CSV file with validation and detailed error reporting  
**Status**: Ready for use  
**Last Updated**: 2026-02-15  

---

## How to Open Import Modal

1. Navigate to **Registration → Schools** page
2. Click **Tools** dropdown (blue button with wrench icon)
3. Select **Import Schools**
4. Modal opens with upload area

---

## Step-by-Step Import Process

### Step 1: Download Template (Optional)

Click **Download Template** in the modal to get a CSV file with the required format.

**Example Template**:
```csv
Code,Name,Region ID,District ID,Ownership
SCH001,Arusha Primary School,1,5,GOVERNMENT
S0203,IRINGA GIRLS SECONDARY SCHOOL,IR07,IR0701,GOVERNMENT
```

### Step 2: Prepare Your CSV File

Create a CSV file with the following columns:

| Column | Required | Format | Example | Notes |
|--------|----------|--------|---------|-------|
| Code | YES | Text, max 30 chars, unique | SCH001, S0203 | Must not duplicate existing schools |
| Name | YES | Text, max 150 chars | Arusha Primary School | School name |
| Region ID | YES | Numeric ID or region code | 1 or IR07 | Must exist in system |
| District ID | NO | Numeric ID or district code | 5 or IR0701 | Must exist if provided |
| Ownership | NO | GOVERNMENT or NON-GOVERNMENT | GOVERNMENT | Defaults to GOVERNMENT if omitted |

**Valid CSV Examples**:

Using numeric IDs:
```csv
Code,Name,Region ID,District ID,Ownership
SCH001,Arusha Primary School,1,5,GOVERNMENT
SCH002,Dar Secondary School,1,6,NON-GOVERNMENT
```

Using region/district codes:
```csv
Code,Name,Region ID,District ID,Ownership
S0203,IRINGA GIRLS' SECONDARY SCHOOL,IR07,IR0701,GOVERNMENT
S0325,LUGALO SECONDARY SCHOOL,IR07,IR0701,GOVERNMENT
```

Mixed (both work):
```csv
Code,Name,Region ID,District ID,Ownership
SCH001,School A,1,IR0701,GOVERNMENT
S0203,School B,IR07,5,NON-GOVERNMENT
```

### Step 3: Upload CSV File

1. Click the upload area in the modal (or select file)
2. Select your CSV file from your computer
3. File name and size will display below upload area
4. Click **Upload & Validate**
5. Wait for validation to complete

### Step 4: Review Validation Report

The system will show:

**Summary Statistics**:
- **Total Rows**: All rows in file (including headers)
- **Valid**: Rows that can be imported
- **Failed**: Rows with errors
- **Status**: "Ready" (no errors) or "Fix Required" (has errors)

**Error Summary** (if errors exist):
- Count of each error type
- Examples: "Region not found", "Invalid ownership", etc.

**Error Table** (if errors exist):
- Row number (1-based)
- School code
- Error messages (one or more per row)
- Scroll through large error lists

### Step 5a: Import Valid Schools (No Errors)

If validation shows **"Ready"**:
1. Click **Import Now** button
2. Wait for import to complete
3. Success screen shows count of imported schools
4. Click **Close and Refresh**
5. Table updates with new schools

### Step 5b: Fix Errors & Re-upload (Has Errors)

If validation shows **"Fix Required"**:

1. Click **Download Errors** to get CSV with failed rows
2. Open the error CSV file
3. Review error messages
4. Fix issues in your original CSV:
   - Add missing required fields
   - Correct school codes (must be unique, max 30 chars)
   - Verify region/district IDs exist
   - Fix ownership values (must be GOVERNMENT or NON-GOVERNMENT)
5. Click **Back to Upload**
6. Upload corrected CSV file
7. Validate again
8. If all valid now, click **Import Now**

---

## CSV Format Rules

### Headers

Must include these column names (case-insensitive, order doesn't matter):
- `Code`
- `Name`
- `Region ID`
- `District ID` (optional)
- `Ownership` (optional)

Examples:
- `code, name, region_id, district_id, ownership` ✓
- `Code,Name,Region ID,District ID,Ownership` ✓
- `CODE, NAME, REGION_ID, DISTRICT_ID, OWNERSHIP` ✓

### Data Types & Constraints

**Code**:
- Must not be empty
- Must be unique (no duplicates in file or database)
- Maximum 30 characters
- Can contain letters, numbers, hyphens, underscores
- Examples: SCH001, S-0203, SCHOOL_1, school123

**Name**:
- Must not be empty
- Maximum 150 characters
- Can contain letters, numbers, spaces, hyphens, etc.
- Examples: Arusha Primary School, St. John's Secondary

**Region ID**:
- Must not be empty
- Either numeric ID (1, 2, 3...) OR region code (IR07, IR08, etc.)
- Must exist in system
- Check with admin for valid region IDs/codes

**District ID**:
- Optional (can be left blank)
- Either numeric ID OR district code if provided
- Must exist in system if provided
- Check with admin for valid district IDs/codes

**Ownership**:
- Optional (defaults to GOVERNMENT if omitted)
- Must be exactly: `GOVERNMENT` or `NON-GOVERNMENT`
- Not case-sensitive (GOVERNMENT, government, Government all work)

### Data Quality

- **Trim spaces**: System automatically trims spaces from all values
- **Empty rows**: Automatically skipped (rows with no data)
- **Max file size**: 10MB (usually supports 1000+ schools)
- **Encoding**: UTF-8 recommended (all characters preserved)

---

## Error Types & Fixes

| Error Message | Cause | Fix |
|---------------|-------|-----|
| Code is required | School code column empty | Add school code |
| Name is required | School name column empty | Add school name |
| Code must be 30 characters or less | Code too long | Shorten code to ≤30 chars |
| Code already exists in database | Code already used for another school | Use unique code or check database |
| Code appears multiple times in file | Same code used twice in CSV | Remove or rename duplicate |
| Region ID does not exist | Region ID/code not found in system | Verify region exists, check ID/code |
| District ID does not exist | District ID/code not found in system | Verify district exists, check ID/code |
| Ownership must be GOVERNMENT or NON-GOVERNMENT | Invalid ownership value | Use only GOVERNMENT or NON-GOVERNMENT |
| Name must be 150 characters or less | Name too long | Shorten name to ≤150 chars |

---

## Download Errors File

After validation, if there are errors:

1. Click **Download Errors** button
2. CSV file saves to your computer: `schools-import-errors-YYYY-MM-DD-HHmmss.csv`
3. Open file in Excel, Google Sheets, or text editor
4. Shows:
   - Row number from original file
   - School code (from your CSV)
   - School name (from your CSV)
   - Region ID (from your CSV)
   - District ID (from your CSV)
   - Ownership (from your CSV)
   - **error_messages**: Description of what went wrong

Example error file:
```csv
row_number,code,name,region_id,district_id,ownership,error_messages
3,SCH002,Test School,99,,INVALID,"Region ID 99 does not exist; Ownership must be GOVERNMENT or NON-GOVERNMENT"
5,SCH005,Another,2,7,INVALID,"Ownership must be GOVERNMENT or NON-GOVERNMENT"
12,,,1,5,GOVERNMENT,"Code is required"
```

---

## Import Success

After successful import:

✓ Schools appear in the table below  
✓ Can be immediately filtered by region/district  
✓ Can be edited via the Edit button  
✓ Can be used for candidate registration  
✓ Candidates can select these schools during registration  

---

## API Reference (For Developers)

### Validate Endpoint
```
POST /api/schools/import/validate
```
Uploads and validates CSV without writing to database.

**Response**: Validation report with error details

### Commit Endpoint
```
POST /api/schools/import/commit
```
Uploads, validates, and writes valid schools to database.

**Response**: Import results with count of imported schools

### Download Template
```
GET /api/schools/import/template
```
Downloads empty CSV template with correct headers.

### Download Errors
```
POST /api/schools/import/download-errors
```
Downloads CSV with failed rows and error messages.

---

## Troubleshooting

### "File must be CSV"
- Make sure file extension is `.csv` or `.txt`
- Not `.xlsx`, `.xls`, or other formats
- Save in CSV format: File → Save As → CSV (Comma-delimited)

### "Validation failed / Processing error"
- Check internet connection
- Try smaller file (upload in batches)
- Check browser console (F12) for error details
- Refresh page and try again

### Modal doesn't open
- Refresh page
- Clear browser cache
- Try different browser
- Check browser console for errors (F12)

### "Region does not exist"
- Contact admin to confirm region IDs
- Use correct region code or numeric ID
- Check for typos in region identifier

### "Code already exists"
- Check if school code already in system
- Change code to unique value
- Or edit existing school instead of importing

### Import takes too long
- Try uploading smaller batches (e.g., 500 schools at a time)
- Check browser's Network tab to see response time
- Large files (1000+) may take 30-60 seconds

---

## Tips & Best Practices

1. **Start Small**: Test with 5-10 schools first
2. **Use Template**: Download template to ensure correct format
3. **Validate First**: Always validate before importing
4. **Fix Errors**: Download errors and fix all issues before retry
5. **Batch Import**: For 1000+ schools, split into multiple batches
6. **Check IDs**: Verify region/district IDs with admin before import
7. **Backup**: System keeps all schools, no data loss during failed import
8. **Unique Codes**: Ensure all school codes are unique before importing

---

## Contact Support

For issues or questions:
- Contact system administrator
- Check import report messages for detailed error info
- Review this guide for common errors
- Check browser console (F12) for technical errors

