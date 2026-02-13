# BULK IMPORT - QUICK START GUIDE
**Last Updated:** February 8, 2026

---

## IN 5 MINUTES

### 1. Login
Go to your IRMS application and login

### 2. Go to Mark Entry
Click: **Mark Entry** → ACSEE

### 3. Download Mark Templates
- Select **Exam Year**: (choose one)
- Select **School**: (choose your school)
- Click **"Download Bulk CSVs"** button
- ZIP file downloads (example: `SCHOOL_ACSEE_2026_MarkTemplate.zip`)

### 4. Fill in Marks in Excel
- Extract the ZIP file on your computer
- Open each CSV file (one per subject) in Excel
- Fill in marks for each candidate in columns like:
  - Column A: index_number
  - Column B: sex
  - Columns C onwards: paper marks, practical, project, etc.
- **Save each CSV file** (DO NOT change filenames)
- Keep the ZIP structure: all CSVs in one folder

### 5. Upload & Import Back
- Still in Mark Entry, go to **"Upload District Marks ZIP"** section
- Select same **Exam Year** and **School**
- **Drag and drop** the ZIP file (or click to select)
- Click **"Preview"** button - verify all data shows up
- Click **"Start Import"** button
- Watch the progress bar - takes a few seconds to minutes depending on candidates
- **Done!** See success message

---

## COMMON ISSUES & SOLUTIONS

### Issue: "No subjects available for download"
- **Solution:** Make sure candidates are registered for the exam year
- Check: Is your school registered with candidates in this exam year?

### Issue: Preview shows "0 files"
- **Solution:** This is normal - the system loads data correctly anyway
- Click "Start Import" - it will work

### Issue: Import fails with error
- **Solution:** 
  1. Check if you edited the CSV filenames (don't do this)
  2. Check if all columns are there
  3. Try downloading a fresh ZIP and filling it again

### Issue: Excel shows "123.0" instead of "123"
- **Solution:** This is normal - just save the file, the system will convert it

---

## STEP-BY-STEP WITH SCREENSHOTS

### Step 1: Navigate to Mark Entry
```
Click: [MENU] → Mark Entry
You should see: "ACSEE Mark Entry" page
```

### Step 2: Select Context
```
[Year dropdown] ← Select your exam year
[School dropdown] ← Select your school
```

### Step 3: Download Button
```
Look for: "🖨️ School Mark Templates" button
Click it
Your browser downloads: SCHOOL_ACSEE_YEAR_MarkTemplate.zip
```

### Step 4: Extract & Edit
```
1. Find the ZIP file on your computer
2. Right-click → Extract All (or use WinRAR, 7-Zip)
3. Open each CSV file in Excel:
   - Each file = one subject
   - Fill in marks in empty cells
   - Save each file
4. Keep the folder structure (all CSVs in one folder)
```

### Step 5: Upload Section
```
In the same page, scroll down to:
"📦 Upload District Marks ZIP"

1. Select same Year and School
2. Click in the upload area or drag-drop the ZIP
3. Click [Preview] button
4. You should see: "✅ ZIP is valid and ready to import"
5. Click [Start Import] button
```

### Step 6: Monitor Progress
```
You'll see:
- Progress bar (0% → 100%)
- School status
- Candidate count
- Auto-refreshes every 2 seconds

When done:
- Green checkmark ✅ appears
- Success message shows
- You can now close the page
```

---

## WHAT EACH COLUMN MEANS

### Always Present
- **index_number**: Candidate's unique ID (DO NOT CHANGE)
- **sex**: M or F (DO NOT CHANGE)

### Subject-Specific (will vary)
- **paper_p1**: Paper 1 marks (0-100)
- **paper_p2**: Paper 2 marks (0-100)
- **practical**: Practical marks (if applicable)
- **project**: Project marks (if applicable)

**Example Row:**
```
index_number | sex | paper_p1 | paper_p2 | practical
123456       | M   | 85       | 78       | 92
```

---

## TIPS FOR SUCCESS

### Before You Start
- ✓ Make sure you have all mark sheets ready
- ✓ Don't use USB with viruses - scan it
- ✓ Have at least 50 MB free disk space
- ✓ Use Firefox, Chrome, or Edge browser

### During Entry
- ✓ Fill marks carefully
- ✓ Don't skip candidates (leave blank if you don't have mark)
- ✓ Use numbers only (no letters, no symbols)
- ✓ Save after each subject

### Before Upload
- ✓ Close Excel files to avoid conflicts
- ✓ Don't rename CSV files
- ✓ Don't move CSV files outside the folder
- ✓ Recompress everything into one ZIP

### Best Practice
- Create a separate folder for each exam year/school
- Label them clearly: `2026_TOSAMAGANGA`
- Keep the original ZIP as backup
- Test with one subject first

---

## BULK IMPORT WORKFLOW VISUAL

```
┌─────────────────────────────────────┐
│   Login to IRMS                     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Mark Entry → ACSEE                │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Select Year, School               │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Download Bulk CSVs (ZIP)          │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Extract ZIP on Computer           │
│   Fill marks in each CSV            │
│   Save all CSV files                │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Upload ZIP Section                │
│   Drag-drop the ZIP file            │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Click Preview                     │
│   Verify: ✓ All data shows up       │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Click Start Import                │
│   Watch progress bar                │
│   Wait for completion ✓             │
└─────────────────────────────────────┘
```

---

## CONTACT SUPPORT

If you encounter any errors or issues:

1. **Take a screenshot** of the error message
2. **Note the time** it happened
3. **Check your browser console** (Press F12, click "Console" tab)
4. **Copy any red error messages**
5. **Contact your administrator** with these details

---

## YOU'RE READY!

Everything is set up and working. Start with one school and one subject to test.
Once you're comfortable, you can do larger batches.

**Questions?** Check the troubleshooting section above or ask your admin.

Good luck! 🎯
