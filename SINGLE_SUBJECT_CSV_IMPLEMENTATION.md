# Single Subject CSV Implementation - Complete Guide

## Overview
The mark entry system has been professionally updated to support "Single Subject CSV" uploads with robust error handling and validation.

## Changes Made

### 1. Label Update
- Changed **"Single School CSV"** to **"Single Subject CSV"** in the mark entry interface
- File: `resources/views/mark-entry/index.blade.php` (Line 224)

### 2. Improved Instructions
Updated the instructions section to be clear and step-by-step:
- **Step 1**: Select Year, Region, District, School, and Subject
- **Step 2**: Download CSV template (contains only eligible candidates)
- **Step 3**: Fill in marks (numeric 0-100)
- **Step 4**: Upload the completed CSV

Added CRITICAL warnings about:
- Do NOT modify header row
- Do NOT add/remove candidate rows
- Do NOT change CSV file name or structure

### 3. Enhanced Error Handling
Improved error messages in the controller to guide users:
- **Checksum errors** → Tell user the file was modified or wrong template used
- **Header errors** → Tell user not to modify the header row
- **Candidate errors** → Tell user to only use provided candidates

File: `app/Http/Controllers/MarkEntryController.php` (Lines 429-450)

## How It Works

### CSV Template Generation
1. User selects: Year, School, and Subject
2. System generates CSV containing:
   - Only candidates registered for that school and subject
   - Headers: index_number, sex, paper_p1, paper_p2, etc. (based on subject)
   - SHA-256 checksum for integrity verification

### CSV Upload & Validation
1. **Integrity Check**: Verifies CSV hasn't been modified (checksum validation)
2. **Structure Check**: Verifies headers and columns match template
3. **Candidate Check**: Verifies all candidates are valid for the school/subject
4. **Mark Validation**: Validates each mark is numeric and in range (0-100)
5. **Registration Check**: Verifies each candidate is registered for ACSEE

### Error Prevention
- **Checksum Protection**: Prevents accidental use of wrong template
- **Row Locking**: Prevents duplicate uploads after successful processing
- **Clear Messages**: User-friendly error messages guide correction

## Key Files

### Views
- `resources/views/mark-entry/index.blade.php` - Main mark entry interface

### Controllers
- `app/Http/Controllers/MarkEntryController.php` - Handles template download and upload

### Services
- `app/Services/MarkImport/MarkImportService.php` - Orchestrates import process
- `app/Services/MarkImport/CsvIntegrityService.php` - Validates CSV integrity
- `app/Services/MarkImport/MarkValidationService.php` - Validates individual marks
- `app/Services/MarkImport/AcseeMarkTemplateService.php` - Generates templates

## What Users Need to Know

### Correct Workflow
1. Select context (Year, Region, District, School, Subject)
2. Click "Mark Template (CSV)" button
3. Fill in marks for each candidate
4. Keep header row intact
5. Don't add/remove rows
6. Upload the file

### Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "CSV file does not match template" | Template was modified or wrong template used | Download a fresh template for your school/subject |
| "CSV header structure is incorrect" | Header row was modified | Download a fresh template - don't modify headers |
| "Candidates not registered for this subject" | Using wrong template or adding candidates | Use only candidates from the downloaded template |
| "All marks must be numeric" | Non-numeric values in mark columns | Enter only numbers (0-100) for all marks |

## Testing the Implementation

To verify Single Subject CSV uploads work correctly:

1. Navigate to Mark Entry → ACSEE
2. Select a Year, Region, District, School, and Subject
3. Click "Mark Template (CSV)" to download
4. Fill in some candidate marks
5. Upload the file
6. Verify marks appear in the system

## Technical Details

### CSV Structure Example
```
index_number,sex,paper_p1,paper_p2,practical
S1234-0001,M,75,82,80
S1234-0002,F,88,91,85
S1234-0003,M,65,70,72
```

### Validation Rules
- All marks must be numeric integers (0-100)
- All eligible candidates must be included
- No additional candidates can be added
- Header row must not be modified
- File must match the downloaded template exactly

### Integrity Checks
- SHA-256 checksum validation
- Header structure verification
- Candidate count verification
- Candidate registration verification
- Mark range validation
- Combination validation

## Support

If users encounter errors during Single Subject CSV upload:
1. Check the error message - it should indicate the issue
2. Review the instructions in the upload section
3. Download a fresh template
4. Follow the step-by-step instructions
5. Contact administrator if issues persist

---

**Last Updated**: 2026-02-10
**Status**: Production Ready
