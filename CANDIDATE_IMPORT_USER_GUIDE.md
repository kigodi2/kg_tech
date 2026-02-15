# Candidate Import: User Quick Guide

## Overview

The candidate import feature now supports two modes for handling existing candidates:

| Mode | Behavior | Use Case |
|------|----------|----------|
| **Skip** | Ignore existing candidates, only import new ones | Safe default; avoids overwriting |
| **Replace** | Update existing candidate info (name, gender, school) | Fix typos or school assignments |

---

## Step-by-Step: Import Candidates

### Step 1: Prepare Your File

1. Go to **Candidates Management** page
2. Click **Tools** dropdown
3. Select **Import CSV**
4. Choose template:
   - **School Template** - For SCHOOL candidates with combinations
   - **Private Template** - For PRIVATE candidates with subject IDs

5. Fill in your data:
   ```csv
   candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
   S0754-0001,JOHN DOE,M,S0754,PCM,ACSEE,2026,SCHOOL
   S0754-0002,JANE SMITH,F,S0754,HGE,ACSEE,2026,SCHOOL
   ```

### Step 2: Select Import Mode

**Choose one:**

**Option A: Skip Existing (Recommended for Safety)**
- ✓ Doesn't modify existing candidates
- ✓ Safe if you're not sure about duplicates
- ✓ Default option

**Option B: Replace Existing**
- Updates: Name, Gender, School
- ✗ Does NOT change: Index number, subjects, marks
- ⚠️ Use only if you're fixing incorrect data

### Step 3: Upload & Validate

1. Click file upload area or drag-drop your CSV
2. (Optional) Select Exam Year or Exam Type
3. Click **Validate**
4. Wait for validation to complete

### Step 4: Review Results

You'll see:

**Summary Cards:**
- **Total Rows** - How many rows in your file
- **New** - Candidates to create
- **Will Update** (Replace mode) OR **Will Skip** (Skip mode)
- **Errors** - Validation failures (fix these!)
- **Can Import** - Ready to proceed?

**Error Table** (if any errors):
- Shows row number, ID, name, and error message
- Fix errors in your CSV and re-upload

**Import Plan Table:**
- Shows what will happen to each row
- Status badges:
  - ✓ NEW = Will create
  - ⊘ SKIP = Will skip (exists already)
  - ↻ UPDATE = Will update
  - ✗ ERROR = Has validation error

### Step 5: Confirm & Import

1. Review the Import Plan carefully
2. If Replace mode: Read the orange warning
3. Click **Import X Records**
4. Wait for processing to complete
5. See success message with counts

---

## Common Scenarios

### Scenario 1: Import New Candidates Only

**Setup:**
- Mode: Skip (default)
- File: Mixed new and existing candidates

**Result:**
- New candidates created
- Existing candidates ignored
- No updates to existing data

**Action:** Just import, no worries about duplicates!

---

### Scenario 2: Fix Candidate Information

**Setup:**
- Mode: Replace
- File: Candidates with corrected names/schools

**Example:**
```csv
candidate_id,full_name,gender,school_code
S0754-0001,JOHN PETER DOE,M,S0755
```
Updates the existing S0754-0001 with new name and school.

**Before:** Name: "JOHN DOE", School: S0754  
**After:** Name: "JOHN PETER DOE", School: S0755

---

### Scenario 3: Audit Check Before Importing

**Setup:**
1. Upload your file
2. Validate (don't import yet)
3. Review the Import Plan table carefully
4. Export or screenshot for audit trail
5. Then proceed with import

---

## Understanding Status Badges

| Badge | Meaning | What Happens |
|-------|---------|--------------|
| ✓ NEW | Candidate doesn't exist | Will be created |
| ⊘ SKIP | Candidate exists, Skip mode | Ignored, nothing changes |
| ↻ UPDATE | Candidate exists, Replace mode | Name, gender, school updated |
| ✗ ERROR | Validation failed | Not imported; fix error |

---

## Troubleshooting

### "Can Import" shows "No"
**Cause:** Validation errors prevent import  
**Fix:** Check the Error Table for details, fix your CSV, re-upload

### Import failed halfway
**Cause:** Unlikely; all imports are transactional  
**Fix:** Check error report, or contact support

### Name didn't update (Replace mode)
**Cause:** School code not found  
**Fix:** Verify school code exists in your system

### Candidate ID must be format CCCC-SSSS
**Cause:** Invalid index number format  
**Fix:** Use format like S0754-0001 (school-index)

### "Already exists" error message
**Cause:** You're in Skip mode and candidate exists  
**Fix:** This is normal! Change to Replace mode if you want to update it

---

## Best Practices

✅ **Always Validate First**
- Review Import Plan before clicking Import
- Spot-check a few rows

✅ **Use Skip Mode by Default**
- Safest option
- Only switch to Replace if fixing specific errors

✅ **Check Error Table**
- Fix all errors before importing
- Errors prevent import from proceeding

✅ **Verify Exam Year**
- Set correct exam year in dropdown
- Affects which year candidates are registered for

✅ **Keep Backup**
- Export candidates list before bulk import
- Useful for before/after comparison

---

## FAQ

**Q: Will Replace mode delete the candidate?**  
A: No. Replace mode only updates name, gender, and school. All exam registrations, subjects, and marks are preserved.

**Q: What if I import the same file twice?**  
A: First time (Skip): creates candidates. Second time (Skip): all marked as SKIP, nothing happens.

**Q: Can I undo an import?**  
A: Not automatically, but error table can help you identify what was imported.

**Q: What's the limit on file size?**  
A: 50MB maximum per file. For larger batches, split into multiple files.

**Q: Do I need to set Exam Year?**  
A: If your CSV includes exam_year column, it uses that. Otherwise, set the dropdown to your active year.

**Q: What about private candidates?**  
A: Use Private Template which includes a "subjects" column (pipe-separated IDs).

---

## Support

**Contact support with:**
- Your CSV file (sanitized)
- Screenshot of Import Plan
- Error table if applicable
- What you expected vs. what happened
