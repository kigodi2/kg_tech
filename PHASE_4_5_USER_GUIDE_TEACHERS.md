# Phase 4.5: User Guide for Teachers - Mark Entry & Validation

**Document Type**: User Guide  
**Target Audience**: Teachers (Data Entry Officers)  
**Phase**: 4.5 - Documentation & Training  
**Last Updated**: 2026-02-13  

---

## Table of Contents

1. [Overview](#overview)
2. [System Access](#system-access)
3. [Complete Workflow - Step by Step](#complete-workflow---step-by-step)
4. [Troubleshooting](#troubleshooting)
5. [FAQs](#faqs)
6. [Contact Support](#contact-support)

---

## Overview

As a Teacher (Data Entry Officer), you are responsible for:

✅ **Uploading** student marks from your subject/class  
✅ **Validating** the uploaded data for accuracy  
✅ **Submitting** the batch for moderation by your HOD  

This guide walks you through the complete process from login to submission.

### Key Points
- **Time Required**: 15-30 minutes per batch
- **CSV Format**: Simple text file format (Excel compatible)
- **Access**: Web browser via the IRMS system
- **Support**: Contact your HOD or System Administrator if issues arise

---

## System Access

### Login
1. Open your web browser and navigate to the IRMS system URL
2. Enter your **username** (usually your employee ID or email)
3. Enter your **password**
4. Click **Login**

### After Login
- You'll see the **IRMS Dashboard**
- Look for the **"Mark Entry"** menu in the left sidebar
- Click **"Mark Entry"** to begin

### User Roles
The system identifies you as a **Teacher** with the following permissions:
- ✅ Can upload marks
- ✅ Can validate your own batches
- ✅ Can view validation reports
- ❌ Cannot moderate or approve batches (HOD responsibility)
- ❌ Cannot submit to NECTA (Admin responsibility)

---

## Complete Workflow - Step by Step

### PHASE 1: SELECT CONTEXT (Exam Year, Subject, Class)

#### Step 1.1: Access Mark Entry
1. Click **"Mark Entry"** in the left menu
2. You'll see the **Context Selection** form with dropdown menus

#### Step 1.2: Select Exam Year
1. Click the **"Exam Year"** dropdown
2. Select the current exam year (e.g., **2026**)
   - System only shows years you're authorized to enter marks for

#### Step 1.3: Select Region (Optional Hierarchical)
1. Click the **"Region"** dropdown
2. Select your region (e.g., **IRINGA**)
   - This filters schools to your geographic area

#### Step 1.4: Select District
1. Click the **"District"** dropdown
2. Select your district (e.g., **IRINGA MUNICIPAL**)
   - Only shows districts in the selected region

#### Step 1.5: Select School
1. Click the **"School"** dropdown
2. Select your school (e.g., **KLERRUU SECONDARY SCHOOL**)
   - Only shows schools in the selected district

#### Step 1.6: Select Subject
1. Click the **"Subject"** dropdown
2. Select your subject (e.g., **MATHEMATICS**, **ENGLISH**, etc.)
   - Only shows ACSEE subjects for the selected exam year
   - Must be a subject you teach

#### Step 1.7: Select Combination (if applicable)
1. Some subjects have multiple combinations (e.g., CBE, HEC)
2. Click the **"Combination"** dropdown
3. Select the appropriate combination
   - Leave blank if subject has no combinations

#### Step 1.8: Validate Context
- The system automatically validates your selections
- You'll see a **green checkmark** if context is valid
- A **red error message** if something is wrong
  - Example: "School not found" or "Subject not active"

### PHASE 2: DOWNLOAD CSV TEMPLATE

#### Step 2.1: Click "Download CSV Template"
1. Once context is validated, a blue button appears
2. Click **"Download CSV Template"**
3. Your browser downloads a file named: `mark-entry-[SUBJECT]-[COMBINATION].csv`

#### Step 2.2: Examine the Template
The CSV file contains:
```
Index Number,Candidate Name,Paper 1,Paper 2,Paper 3,Practical,Project
S1378-0501,SAMPLE NAME 1,75,82,88,85,90
S1378-0502,SAMPLE NAME 2,68,71,79,82,88
S1378-0503,SAMPLE NAME 3,92,88,95,90,92
```

**Template Sections:**
- **Row 1**: Headers (column names)
- **Rows 2-4**: Sample data (delete these)
- **Additional Instructions**: Comments at the end of the file

---

### PHASE 3: FILL DATA IN EXCEL OR GOOGLE SHEETS

#### Step 3.1: Open the Template
- **Microsoft Excel**: File → Open → Select the CSV file
- **Google Sheets**: Upload the file
- **LibreOffice Calc**: File → Open → Select the CSV file

#### Step 3.2: Delete Sample Rows
1. Select rows 2-4 (the sample data)
2. Delete them
3. Your spreadsheet now has only the header row

#### Step 3.3: Enter Student Data
1. **Column A (Index Number)**: Student's exam index number
   - Format: `S1378-XXXX` (exactly)
   - Example: `S1378-0501`, `S1378-0502`

2. **Column B (Candidate Name)**: Student's full name
   - Example: `JOHN MUTUA`, `MARY JACKSON`

3. **Column C+ (Paper/Marks)**: Enter marks for each paper
   - Marks must be **0-100**
   - Decimal marks allowed (e.g., 75.5)
   - Leave blank if not applicable

#### Step 3.4: Example - Complete Row
```
S1378-0501,JOHN MUTUA,85,88,92
```

#### Step 3.5: Save the File
1. **File** → **Save As**
2. **File Type**: CSV (Comma-separated values)
3. **Encoding**: UTF-8
4. **File Name**: Keep the original name or rename it
5. Click **Save**

### Important Notes:
- ⚠️ Do NOT change column headers
- ⚠️ Do NOT add/remove columns
- ⚠️ Do NOT change the order of columns
- ✅ Use comma (,) as separator (not semicolon or tab)
- ✅ Save as UTF-8 encoding to avoid character errors

---

### PHASE 4: UPLOAD CSV TO SYSTEM

#### Step 4.1: Return to IRMS
1. Go back to the Mark Entry form in your browser
2. The context (School, Subject, etc.) is still selected

#### Step 4.2: Upload the CSV File
**Option A: Click and Select**
1. Click the **"Choose File"** button
2. Select your CSV file from your computer
3. Click **"Upload Marks"**

**Option B: Drag and Drop**
1. Drag your CSV file from your file explorer
2. Drop it into the **file upload area**
3. The upload begins automatically

#### Step 4.3: System Processing
- A progress bar shows the upload status
- The system will:
  - Parse the CSV
  - Create a batch record (status: DRAFT)
  - Validate each row
  - Check for errors (missing index, invalid marks, etc.)
  - Calculate statistics

#### Step 4.4: Wait for Validation
- Processing time: 30 seconds - 2 minutes (depending on file size)
- Do **NOT** close the browser during upload
- Do **NOT** refresh the page

#### Step 4.5: Review Validation Results

**✅ VALIDATION PASSED**
```
Status: VALIDATED ✓
Errors: 0
Valid Records: 450
Ready to submit to HOD
```
→ Proceed to **Phase 5**

**❌ VALIDATION FAILED**
```
Status: VALIDATION FAILED ✗
Errors: 3
Error Details:
  • Row 5: Invalid mark "150" (max 100)
  • Row 12: Missing index number
  • Row 28: Duplicate index S1378-0501
```
→ Proceed to **Fixing Errors** below

---

### FIXING VALIDATION ERRORS

#### If Validation Failed:

1. **Download Error Report**
   - A button appears: **"Download Error Report"**
   - Click it to save a detailed error list
   - Open the report in Excel/Sheets

2. **Identify Each Error**
   - Error report shows row number and issue
   - Example: "Row 5: Mark 150 exceeds maximum of 100"

3. **Open Your Original CSV**
   - Open the file in Excel/Google Sheets
   - Find the row with the error
   - Fix the data:
     - Change invalid marks to 0-100
     - Add missing index numbers
     - Remove duplicate rows
     - Correct spelling if needed

4. **Re-save the CSV**
   - Save as CSV UTF-8 format again
   - Use the same file or new file name

5. **Re-upload**
   - Go back to Mark Entry form
   - Upload the corrected CSV file
   - Repeat validation process

**Common Errors and Fixes:**

| Error | Cause | Fix |
|-------|-------|-----|
| "Invalid mark '150'" | Mark > 100 | Change to valid range (0-100) |
| "Missing index number" | Column A is blank | Add the student's index number |
| "Duplicate index S1378-0501" | Same index twice | Remove duplicate row |
| "Invalid format" | Wrong CSV format | Save again as CSV UTF-8 |
| "Character encoding error" | Non-UTF-8 encoding | Save as UTF-8 in Excel |

---

### PHASE 5: SUBMIT TO HOD

#### Step 5.1: Review Summary
After successful validation, you see:
```
┌─────────────────────────────────┐
│ BATCH SUMMARY                   │
├─────────────────────────────────┤
│ Exam Year: 2026                 │
│ School: KLERRUU SECONDARY       │
│ Subject: MATHEMATICS            │
│ Combination: CBE                │
│                                 │
│ Total Records: 450              │
│ Valid Records: 450              │
│ Errors: 0                       │
│ Status: READY TO SUBMIT         │
└─────────────────────────────────┘
```

#### Step 5.2: Click "Submit to Moderation"
1. Review the batch summary
2. Confirm all data is correct
3. Click **"Submit to Moderation"** button

#### Step 5.3: Confirmation
- The batch status changes to **"AWAITING_MODERATION"**
- Your HOD will now review the batch
- You receive a **confirmation message**
- You can view the batch in **"My Submissions"** section

#### Step 5.4: Next Steps
- **HOD will moderate** your submission
- **HOD may approve** → Proceeds to admin for final submission
- **HOD may reject** → You'll be notified to resubmit
- You'll receive **email notification** when HOD reviews your batch

---

## Troubleshooting

### Q: I don't see the Mark Entry menu
**A:** 
- Make sure you're logged in as a Teacher
- Contact your administrator to check your permissions
- Try logging out and back in

### Q: Context validation keeps failing
**A:**
- Ensure **all** dropdowns are selected (not blank)
- Check that the school is in the correct district
- Check that the subject is ACSEE (not O-Level)
- Try selecting a different year if the current year isn't available

### Q: Upload is very slow
**A:**
- Large files (>5MB) may take longer
- Check your internet connection
- Try uploading during off-peak hours
- If it takes >5 minutes, check with your administrator

### Q: The file downloads but I can't open it
**A:**
- Ensure your computer has Excel or Google Sheets
- Try opening it with Google Sheets instead of Excel
- Check that the file downloaded completely (check file size)
- Try downloading again

### Q: I got an error "CSV not valid"
**A:**
- Make sure the file is saved as CSV format (not XLSX)
- Check that separators are commas (,), not semicolons (;)
- Ensure file is UTF-8 encoded
- Don't include extra blank rows or columns
- Remove sample rows before uploading

### Q: Marks showing as "0" after upload
**A:**
- Ensure marks are entered as numbers (not text)
- In Excel: Format column as "Number"
- Remove any currency symbols or letters
- Try re-saving the CSV and uploading again

### Q: I accidentally submitted but data was wrong
**A:**
- Contact your HOD immediately
- Provide them with the batch ID or date/subject
- They can reject the batch and ask you to resubmit
- You can then fix and re-upload

---

## FAQs

### Can I edit marks after submission?
**No.** Once you submit to your HOD for moderation, the batch is locked. If changes are needed, your HOD will reject it, and you must re-upload.

### How many students can I upload at once?
**Limit:** Up to 500 students per batch. If you have more, create multiple batches by repeating the workflow.

### Do I need internet throughout the process?
**Yes.** You need internet for:
- Logging in
- Downloading the template
- Uploading the CSV
- Viewing results

You can fill the CSV offline after downloading.

### What if I need to enter marks for multiple classes/subjects?
**Repeat the workflow:**
1. Complete the full process for Subject A
2. Submit to your HOD
3. Go back to Mark Entry
4. Select different context (Subject B)
5. Download new template, fill, upload, submit

### Can my HOD see marks before I submit?
**No.** Your submission is private until you click "Submit to Moderation."

### What if the system crashes during upload?
**Don't worry:**
1. Wait 5 minutes
2. Log back in
3. Your upload may have completed (check the batch list)
4. If not, re-upload the file
5. The system prevents duplicate processing

### Can I download my submitted batch?
**Yes.** After submission, you can view and download the validated batch data from "My Submissions" for your records.

### How long does HOD moderation take?
**Typically:** 1-3 days depending on HOD's workload. You'll get email notification when they review.

### What does "rejected" mean?
**It means:** Your HOD found errors or concerns. Check the rejection reason in the system, fix the issues, and re-upload.

---

## Contact Support

### If You Need Help:

1. **First Contact**: Your **School HOD**
   - They can explain requirements specific to your school
   - Can help troubleshoot upload issues

2. **System Help**: **IRMS Technical Support**
   - Email: support@irms.education.tz
   - Phone: [Support Number]
   - Hours: Monday-Friday, 8 AM - 5 PM

3. **Data Issues**: **Your District NECTA Officer**
   - For questions about mark ranges, subject combinations
   - For exam-specific requirements

### Information to Provide When Asking for Help:
- Your school name
- The subject you're entering marks for
- The exam year
- The exact error message (if any)
- Screenshot of the issue (if possible)

---

## Summary Checklist

Before uploading marks, ensure:
- ✅ You have the correct context selected (Year, School, Subject, Combination)
- ✅ CSV file has proper headers (Index, Name, Paper1, Paper2, etc.)
- ✅ Sample rows are deleted
- ✅ All index numbers are in format: S1378-XXXX
- ✅ All marks are 0-100 (decimals OK)
- ✅ File is saved as CSV (not XLSX)
- ✅ File is UTF-8 encoded
- ✅ No extra blank rows or columns

After upload and validation:
- ✅ Validation passed (0 errors)
- ✅ All records count is correct
- ✅ Spot-check a few marks for accuracy
- ✅ Click "Submit to Moderation"
- ✅ Receive confirmation message

---

**End of User Guide for Teachers**

For questions or feedback on this guide, contact your HOD or the System Administrator.
