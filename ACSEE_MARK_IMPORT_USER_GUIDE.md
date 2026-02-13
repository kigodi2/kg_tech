# ACSEE Mark Entry - User Guide for Data Entry Officers

**Version:** 1.0  
**Date:** 2026-01-31  

---

## QUICK START

### What You Need
- Exam year (e.g., 2025)
- School name/code
- Subject code (e.g., ENG, BIO, MAT, GEO)
- CSV file with candidate index numbers and marks

### What You DON'T Need
- Combination code (automatically determined by the system)

---

## STEP-BY-STEP WORKFLOW

### STEP 1: Open Mark Entry

Navigate to: **Mark Entry → ACSEE**

You will see a form with:
- **Exam Year** field
- **Region** dropdown
- **District** dropdown  
- **School** dropdown
- **Subject** dropdown
- **Reset** button

### STEP 2: Select Context

Fill in the fields in this order:

| Field | Required? | Example |
|-------|-----------|---------|
| Exam Year | ✅ Yes | 2025 |
| Region | ⭕ Optional | Dar es Salaam |
| District | ⭕ Optional | Kinondoni |
| School | ⭕ Optional | School A |
| Subject | ✅ Yes | Biology (BIO) |

**Note:** Region, District, School help you find the right school. If you know your school, you can select just the School field directly.

### STEP 3: Download Template

1. Click **"Download CSV Template"** button
2. A CSV file will download
3. Open the file in Excel or Google Sheets

**Template Structure (Example for Biology):**

```
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100),Practical (out of 100)
S000001,SAMPLE CANDIDATE 1,75,75,80
S000002,SAMPLE CANDIDATE 2,75,75,80
S000003,SAMPLE CANDIDATE 3,75,75,80
```

### STEP 4: Fill in Marks

1. **Delete the sample rows** (the 3 SAMPLE CANDIDATE rows)
2. **Enter your data** (index numbers from student list)
3. **Fill marks** for each column:
   - Paper marks: 0-100
   - Practical marks (if applicable): 0-100
   - Project marks (if applicable): 0-100

**Example for real data:**

```
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100),Practical (out of 100)
S001234,JOHN MWASE,85,78,92
S001235,MARY KIPROTICH,92,88,95
S001236,PETER NDLELA,76,81,88
```

### STEP 5: Upload CSV

1. Back in the browser, click **"Choose File"**
2. Select your completed CSV file
3. Click **"Upload Marks"**

**What happens next:**
- File is processed
- Candidates are validated
- Marks structure is checked
- Your combination is automatically determined for each student

### STEP 6: Review Results

You will see a summary:

```
Total Records:    45 candidates
Valid Records:    45 ✅
Errors:           0
Status:           Ready to lock
```

#### If all records are valid ✅

Click **"Lock Batch (No Changes Allowed)"**  
This prevents accidental modifications.

#### If there are errors ❌

1. Click **"Download Error Report"**
2. Review the error CSV (shows what went wrong)
3. Fix issues in your original data
4. Upload again

**Common errors:**

| Error | Cause | Fix |
|-------|-------|-----|
| Candidate with index number 'S001234' not found | Wrong index number | Check student list, use correct format |
| Candidate is not registered for ACSEE in year 2025 | Student not registered | Register student first, then import |
| Subject 'BIO' is not registered under candidate's ACSEE combination | Student didn't select this subject | Check student's subject combination |
| Paper 1 marks must be between 0 and 100 | Invalid mark value | Marks must be 0-100 (no decimals or negative) |
| Paper 1 marks are missing or empty | Missing column | CSV must have all required columns |

---

## KEY POINTS TO REMEMBER

✅ **DO:**
- Use the exact template structure provided
- Ensure all required columns are present
- Delete sample rows before uploading
- Check your marks are valid (0-100)
- Use correct student index numbers
- Lock the batch when marks are correct

❌ **DON'T:**
- Modify the header row
- Add extra columns
- Leave required fields empty
- Use decimal marks (e.g., 85.5 - use 85 or 86)
- Forget to delete sample data
- Upload the same file twice

---

## EXAMPLE WORKFLOW

### Example: Biology marks for School A, 2025

**Step 1-2: Select context**
```
Exam Year: 2025
School: School A
Subject: Biology (BIO)
```

**Step 3: Download template → BIO marks file**

**Step 4: Fill data**
```
Template has columns: Index Number | Full Name | Paper 1 | Paper 2 | Practical

Fill in:
S001234, JOHN MWASE, 85, 78, 92
S001235, MARY KIPROTICH, 92, 88, 95
...
```

**Step 5: Upload → System processes**

**Step 6: See results**
```
✅ 45 valid records
✅ 0 errors
✅ Ready to lock
```

**Step 7: Lock batch**
```
Status changes to "Locked"
No further changes allowed
```

---

## WHAT HAPPENS BEHIND THE SCENES?

When you upload:

1. **System finds each student** by index number in your database
2. **Checks if registered** for ACSEE in 2025
3. **Determines their combination** (e.g., Science - Physics, Chemistry, Biology)
4. **Validates subject** is in their combination (e.g., Biology ✅ is in Science combination)
5. **Checks marks** are valid (0-100, all required fields present)
6. **Stores marks** if everything is correct

If **any** of these fail, the row is rejected with a clear error message.

---

## MULTI-COMBINATION SCHOOLS

If your school has students in different subject combinations:

✅ **This works fine!**

Example:
- Student 1: Science combo (Physics, Chemistry, Biology) → uploads Biology ✅
- Student 2: Arts combo (History, Geography, Kiswahili) → uploads Biology ❌ (rejected)

**System automatically validates** each student's combination. You don't need to do anything!

---

## TROUBLESHOOTING

### Problem: "Candidate is not registered for ACSEE"

**What it means:** Student hasn't been registered for ACSEE yet

**Solution:**
1. Register the student first (in Candidates module)
2. Wait for approval
3. Then try importing marks

### Problem: "Subject not registered under candidate's combination"

**What it means:** Student selected different subjects (e.g., History, not Biology)

**Solution:**
1. Check the student's subject selection
2. Correct it if wrong
3. Then try importing marks again

### Problem: "Paper 1 marks must be numeric"

**What it means:** Marks contain letters or special characters

**Solution:**
1. Check marks column - should be numbers only
2. Remove any commas, letters, or symbols
3. Re-upload

### Problem: CSV file won't upload

**What it means:** File format or structure is wrong

**Solutions:**
1. Check file is CSV (not XLSX)
2. Check columns match template
3. Check no special characters in index numbers
4. Try downloading template again and re-filling

---

## SUPPORT

If you encounter issues:

1. **Check error message** - it tells you exactly what's wrong
2. **Download error report** - shows which rows failed and why
3. **Fix your data** based on error details
4. **Re-upload** with corrected data
5. **Contact IT** if problem persists

---

## BEST PRACTICES

1. **Download fresh template** each time (subject structure might change)
2. **Use Excel** for filling data (easier to manage columns)
3. **Save backup** of your data before uploading
4. **Upload one subject at a time** (one CSV per subject per school)
5. **Lock batch immediately** after all marks are correct
6. **Request error report** for any rejected records

---

## KEYBOARD SHORTCUTS

| Shortcut | Action |
|----------|--------|
| Enter | Submit form / Toggle dropdown |
| Esc | Close dropdown |
| Ctrl+C | Copy (in Excel) |
| Ctrl+V | Paste (in Excel) |

---

## IMPORTANT NOTES

📌 **Combination is automatically determined**  
You don't select it. The system figures it out from the student's registered subjects.

📌 **One upload = One subject**  
Each CSV file must contain marks for only ONE subject (e.g., only Biology, not Biology + Chemistry mixed).

📌 **School-specific validation**  
All marks must be for the same school and the same academic year.

📌 **No edits after lock**  
Once you lock a batch, you cannot modify those marks. If you need to change marks, you must contact an administrator.

---

## FAQ

**Q: Can I upload marks for multiple subjects in one file?**  
A: No. Each subject needs its own CSV file.

**Q: Can I include students from different schools?**  
A: No. Each upload is for one school only.

**Q: Can I upload marks for different years?**  
A: No. Each upload is for one academic year only.

**Q: What if a student took the exam but doesn't have a subject?**  
A: The system will reject that row with an error. The student must select the subject first.

**Q: Can I upload the same file twice?**  
A: The system will create a new batch. It's allowed but not recommended (causes duplicates).

**Q: Can I edit marks after locking?**  
A: No. Once locked, only administrators can make changes.

**Q: What's the maximum file size?**  
A: 5 MB (supports ~5000 candidates per file).

---

## SUMMARY CHECKLIST

Before uploading, confirm:

- [ ] I selected the correct exam year
- [ ] I selected the correct school
- [ ] I selected one subject
- [ ] My CSV file matches the downloaded template
- [ ] I deleted all sample rows
- [ ] All index numbers are correct
- [ ] All marks are between 0-100
- [ ] No columns are empty for required fields
- [ ] File format is CSV (not XLSX or other)

---

**Last Updated:** 2026-01-31  
**Contact:** IT Support for technical issues
