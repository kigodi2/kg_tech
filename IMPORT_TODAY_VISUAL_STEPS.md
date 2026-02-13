# 🚀 Import TODAY - Visual Step-by-Step Guide

## Complete Flow (5 Minutes Total)

```
START
  ↓
[1] Fix CSV File (2 min)
  │
  ├─ Google Sheets: Ctrl+H Find & Replace
  ├─ Excel: Ctrl+H Find & Replace  
  └─ Terminal: php fix_csv.php
  │
  ↓
[2] Go to Import Page (1 min)
  │
  └─ REGISTRATION > Candidates > Import CSV
  │
  ↓
[3] Select Fixed CSV (1 min)
  │
  └─ Choose candidates_fixed.csv
  │
  ↓
[4] Click Import (30 sec)
  │
  ↓
✅ SUCCESS - Data Imported!
```

---

## STEP 1: Fix the CSV File

### METHOD A: Google Sheets (Easiest)

```
1. Go to sheets.google.com
   ↓
2. Click "File > Open"
   ↓
3. Upload: candidates.csv
   ↓
4. Press: Ctrl+H (or Cmd+H on Mac)
   
   FIND:     S0108
   REPLACE:  DSM001
   
   ↓
5. Click "Replace All"
   ↓
6. File > Download > CSV
   
   ↓ (Save as candidates_fixed.csv)
```

### METHOD B: Excel

```
1. Open: candidates.csv in Excel
   ↓
2. Press: Ctrl+H
   
   FIND:     S0108
   REPLACE:  DSM001
   
   ↓
3. Click "Replace All"
   ↓
4. Save as: candidates_fixed.csv (CSV format)
```

### METHOD C: Command Line (Fastest)

```bash
cd /home/prosmart-technologies/SOL/irms

php fix_csv.php candidates.csv candidates_fixed.csv

# Output shows replacements made
# ✅ candidates_fixed.csv created
```

---

## CSV Before vs After

### ❌ BEFORE (Fails Import)
```
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,S0108,ACSEE
S0108-0504,DOREEN RESNICK DILLIMY,F,HGL,S0108,ACSEE
↑
PROBLEM: School code S0108 doesn't exist in database
```

### ✅ AFTER (Successful Import)
```
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,DSM001,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,DSM001,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,DSM001,ACSEE
S0108-0504,DOREEN RESNICK DILLIMY,F,HGL,DSM001,ACSEE
↑
FIXED: School code now matches database (DSM001)
```

**Only column 5 (School ID) changed!**

---

## STEP 2: Go to Import Page

```
Open IRMS System
  ↓
Click: REGISTRATION (top menu)
  ↓
Click: Candidates (left sidebar)
  ↓
Look for GREEN BUTTON (top right) with dropdown
  ↓
Click dropdown arrow next to "+ Register Candidate"
  ↓
Select: "Import CSV"
  ↓
Done! Ready for Step 3
```

---

## STEP 3: Select Your Fixed CSV

```
Click: "Import CSV" button
  ↓
File picker opens
  ↓
Navigate to: candidates_fixed.csv
  ↓
Click: Open
  ↓
Done! Ready for Step 4
```

---

## STEP 4: Click Import

```
File is selected
  ↓
Click: "Import" button
  ↓
System processes...
  ↓
Wait for message...
```

### ✅ IF SUCCESS (Green Message)
```
"X candidates imported successfully"
  ↓
CONGRATULATIONS! Data is in!
  ↓
Go to candidates list to verify
```

### ❌ IF ERROR (Red Message)
```
Error message appears
  ↓
Check error (usually):
  - Wrong school code (verify you used DSM001, DSM002, etc.)
  - Missing required field (Name, Sex, Exam Type)
  - Wrong number of columns
  ↓
Fix CSV and retry
```

---

## Quick Reference: Valid School Codes

**Use ONE of these for column 5:**

```
✅ DSM001  (Dar es Salaam Primary School)
✅ DSM002  (Dar Secondary School)
✅ MGO001  (Morogoro Primary School)
✅ MGO002  (Morogoro Secondary School)
✅ MGO003  (Morogoro Combined School)
```

**DON'T use:**
```
❌ S0108 (doesn't exist in database)
❌ S0109, S0110, etc.
```

---

## Verification Checklist

Before you import, check:

```
☐ CSV has exactly 6 columns
☐ Column 1: Index Number (S0108-0501)
☐ Column 2: Full Name (not empty)
☐ Column 3: Sex (F or M)
☐ Column 4: Combination (can be empty)
☐ Column 5: School Code = DSM001, DSM002, MGO001, MGO002, or MGO003
☐ Column 6: Exam Type (ACSEE, CSEE, PSLE)
☐ No completely blank rows
☐ File saved as CSV (not Excel)
```

If all checked, import will work!

---

## Timeline

```
Activity              Time    Cumulative
─────────────────────────────────────────
1. Fix CSV           2 min   2 min
2. Navigate to page  1 min   3 min
3. Select file       1 min   4 min
4. Import            1 min   5 min
─────────────────────────────────────────
TOTAL:                       ⏱️  5 MINUTES
```

**Can be done even faster with command line (3 minutes total)**

---

## Common Mistakes to Avoid

❌ **Mistake 1**: Leaving school code as S0108
```
WRONG:   S0108-0501,Name,F,HGL,S0108,ACSEE
RIGHT:   S0108-0501,Name,F,HGL,DSM001,ACSEE
                                  ↑
```

❌ **Mistake 2**: Leaving columns blank
```
WRONG:   S0108-0501,,F,HGL,DSM001,ACSEE
         Missing full name!
RIGHT:   S0108-0501,STUDENT NAME,F,HGL,DSM001,ACSEE
```

❌ **Mistake 3**: Wrong number of columns
```
WRONG:   S0108-0501,Name,F (only 3 columns)
RIGHT:   S0108-0501,Name,F,HGL,DSM001,ACSEE (6 columns)
```

❌ **Mistake 4**: Saving as Excel instead of CSV
```
WRONG:   candidates.xlsx
RIGHT:   candidates.csv
```

---

## You're All Set!

You now have:
- ✅ Fast import guide (FAST_IMPORT_TODAY.md)
- ✅ Helper script (fix_csv.php)
- ✅ Direct import option (QUICK_IMPORT_COMMAND.md)
- ✅ Visual steps (this file)

Pick one method, follow it, and you'll have data imported in 5 minutes!

---

## Still Need Help?

If import fails after following these steps:

1. **Screenshot the error message**
2. **Share first 3 rows of your CSV** (the ones you're trying to import)
3. **Tell me which method you used**

I'll help debug immediately.

**You've got this! 🚀**
