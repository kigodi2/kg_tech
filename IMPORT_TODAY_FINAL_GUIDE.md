# 🚀 Import TODAY - Final Complete Guide

## TL;DR (The Quick Version)

Your CSV has school code `S0108` but database doesn't have it.

**Fix in 4 steps:**
1. Copy your CSV to `/home/prosmart-technologies/SOL/irms/candidates.csv`
2. Run: `php fix_csv.php candidates.csv candidates_fixed.csv`
3. Go to IRMS: REGISTRATION → Candidates → Tools → Import CSV
4. Select `candidates_fixed.csv` and click Import

**Done in ~5 minutes!**

---

## The Problem (1 minute read)

Your CSV format:
```
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
```

The issue: **Column 5 has `S0108` (school code)**

Database schools available:
- DSM001 ✅
- DSM002 ✅
- MGO001 ✅
- MGO002 ✅
- MGO003 ✅

**Result**: Import fails because S0108 doesn't exist

---

## The Solution (Choose One)

### ⚡ FASTEST: Command Line (1 minute)

```bash
cd /home/prosmart-technologies/SOL/irms

php fix_csv.php candidates.csv candidates_fixed.csv
```

**What it does:**
- Reads your CSV
- Changes all `S0108` → `DSM001`
- Creates `candidates_fixed.csv`
- Ready to import

**Then:**
1. Open IRMS
2. REGISTRATION → Candidates
3. Tools → Import CSV
4. Select `candidates_fixed.csv`
5. Click Import ✅

---

### 🟢 EASY: Google Sheets (3 minutes)

1. Go to **sheets.google.com**
2. File → Open → Upload `candidates.csv`
3. Press **Ctrl+H** (Find & Replace)
   - Find: `S0108`
   - Replace: `DSM001`
4. Click **Replace All**
5. File → Download → CSV

**Then:**
1. Open IRMS
2. REGISTRATION → Candidates
3. Tools → Import CSV
4. Select downloaded file
5. Click Import ✅

---

### 🔵 EASY: Excel (3 minutes)

1. Open `candidates.csv` in Excel
2. Press **Ctrl+H** (Find & Replace)
   - Find: `S0108`
   - Replace: `DSM001`
3. Click **Replace All**
4. Save As → `candidates_fixed.csv` (CSV format)

**Then:**
1. Open IRMS
2. REGISTRATION → Candidates
3. Tools → Import CSV
4. Select `candidates_fixed.csv`
5. Click Import ✅

---

## Step-by-Step: Using Command Line (Recommended)

### Prerequisites
- You have `candidates.csv` file

### Process

**Step 1: Navigate to project**
```bash
cd /home/prosmart-technologies/SOL/irms
```

**Step 2: Check file exists**
```bash
ls -la candidates.csv
```
(You should see your file listed)

**Step 3: Run the fixer**
```bash
php fix_csv.php candidates.csv candidates_fixed.csv
```

**Step 4: Verify success**
```
You'll see output like:
📂 Processing: candidates.csv
💾 Output: candidates_fixed.csv

✏️  Row 2: Replaced 'S0108' → 'DSM001'
✏️  Row 3: Replaced 'S0108' → 'DSM001'
✏️  Row 4: Replaced 'S0108' → 'DSM001'
... (more rows)

✅ Done!
   Replacements made: 546
   Output file: candidates_fixed.csv
   Ready to import!
```

**Step 5: Import via Web UI**
1. Open IRMS in browser
2. Click menu: **REGISTRATION**
3. Click: **Candidates** (left sidebar)
4. Find button with dropdown (top right)
5. Click dropdown arrow
6. Select: **Import CSV**
7. Click to select file
8. Choose: `candidates_fixed.csv`
9. Click: **Import**
10. Wait for success message ✅

---

## CSV Format Verification

Before you import, verify your CSV has exactly this:

| Col | Name | Example | Notes |
|-----|------|---------|-------|
| 1 | Index Number | S0108-0501 | Student ID |
| 2 | Full Name | AGRIPINA YOHANA MAGANGA | Required |
| 3 | Sex | F | F or M |
| 4 | Combination | HGL | Subject code |
| 5 | School Code | ~~S0108~~ → **DSM001** | ← CHANGED |
| 6 | Exam Type | ACSEE | Required |

**Key point**: Column 5 MUST be one of:
- DSM001 ✅
- DSM002 ✅
- MGO001 ✅
- MGO002 ✅
- MGO003 ✅

---

## What Each Column Means

**Column 1 - Index Number**: Student's unique ID (S0108-0501)
- Can be auto-generated if missing
- Helps identify student in reports

**Column 2 - Full Name**: Student's name
- **REQUIRED** - Cannot be empty
- Must be exact spelling for records

**Column 3 - Sex**: Gender
- **REQUIRED** - Must be `F` or `M`
- Used for statistical reporting

**Column 4 - Combination**: Subject combination (ACSEE only)
- Optional for other exam types
- Example: HGL = History + Geography + Literature

**Column 5 - School Code**: School identifier
- **REQUIRED** - Must exist in database
- Used to link student to school
- Examples: DSM001, MGO001, etc.

**Column 6 - Exam Type**: Type of exam
- **REQUIRED** - Can be ACSEE, CSEE, or PSLE
- Determines which exam student takes

---

## Example: Before & After

### ❌ BEFORE (Fails)
```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,S0108,ACSEE
```

**Error**: School code 'S0108' does not exist

### ✅ AFTER (Works)
```csv
Index Number,Full Name,Sex,Combination,School ID,Exam Type
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,DSM001,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,DSM001,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,DSM001,ACSEE
```

**Result**: ✅ Import succeeds

---

## Timeline

```
Activity                    Time        Total
──────────────────────────────────────────────
Choose method               1 min       1 min
Fix CSV file               2 min       3 min
Navigate to import page    1 min       4 min
Select & import file       1 min       5 min
──────────────────────────────────────────────
TOTAL                                  5 min
```

**Can be as fast as 2 minutes with command line!**

---

## Troubleshooting

### Issue: "php command not found"
```
You need PHP installed.
Check: php --version

If not found, use Google Sheets method instead.
```

### Issue: "candidates.csv: No such file"
```
File must be in this exact location:
/home/prosmart-technologies/SOL/irms/candidates.csv

Move your file there first.
```

### Issue: "Still getting 'School code doesn't exist' error"
```
Likely reasons:
1. S0108 wasn't replaced with DSM001
2. Used wrong school code (not DSM001, DSM002, etc.)
3. Blank cells in Column 5

Solution:
- Reopen CSV
- Verify Column 5 has DSM001 (or valid code)
- No blank cells in Column 5
- Save and retry import
```

### Issue: "Missing Full Name" error
```
This means Column 2 is empty for some row.

Solution:
- Open CSV
- Check that every row has a name in Column 2
- Fill in any blanks
- Save and retry
```

---

## After Import Succeeds

Once you see the green success message:

1. **Verify in IRMS**
   - Go to REGISTRATION → Candidates
   - Should see your imported candidates listed

2. **Next steps**
   - Register candidates for exams
   - Enter exam marks
   - Generate results

**All set! Data is in the system!**

---

## Files You Now Have

1. **FAST_IMPORT_TODAY.md** - Quick 5-minute guide
2. **IMPORT_TODAY_VISUAL_STEPS.md** - Step-by-step with visuals
3. **CSV_IMPORT_ERROR_DIAGNOSIS.md** - Troubleshooting all errors
4. **CSV_IMPORT_DECISION_GUIDE.md** - All 3 options explained
5. **QUICK_IMPORT_COMMAND.md** - Command line options
6. **fix_csv.php** - The helper script
7. **IMPORT_CHEATSHEET.txt** - 30-second reference
8. **This file** - Complete guide

**Use whichever makes most sense for you!**

---

## Final Checklist Before Import

```
✅ CSV file location: /home/prosmart-technologies/SOL/irms/candidates.csv
✅ CSV has exactly 6 columns
✅ Column 1: Index Number
✅ Column 2: Full Name (no empty cells)
✅ Column 3: Sex (F or M)
✅ Column 4: Combination (can be empty)
✅ Column 5: School Code = DSM001 (not S0108!)
✅ Column 6: Exam Type (ACSEE, CSEE, or PSLE)
✅ No completely blank rows
✅ File saved as CSV (not Excel)
```

**All checked?** → You're ready to import! 🚀

---

## Questions?

1. **Quick answers**: Check IMPORT_CHEATSHEET.txt
2. **Visual guide**: See IMPORT_TODAY_VISUAL_STEPS.md
3. **Specific errors**: See CSV_IMPORT_ERROR_DIAGNOSIS.md
4. **All options**: See CSV_IMPORT_DECISION_GUIDE.md
5. **Command help**: See QUICK_IMPORT_COMMAND.md

---

## You're All Set!

Pick a method, follow the steps, and your data will be imported today!

**Recommended**: Use the command line method (fastest - 1 min to fix)

Ready? Let's go! 🚀
