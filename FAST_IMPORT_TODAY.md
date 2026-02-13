# ⚡ FAST CSV IMPORT - TODAY (5 Minutes)

## The Fastest Path: Option 2a

**Time needed**: 5 minutes max
**Steps**: 3 simple steps

---

## Step 1: Fix Your CSV (2 minutes)

### Using Google Sheets (Easiest):

1. **Open your CSV in Google Sheets**
   - Go to sheets.google.com
   - Click "File > Open"
   - Upload your CSV file

2. **Find & Replace**
   - Press `Ctrl+H` (Windows) or `Cmd+H` (Mac)
   - **Find**: `S0108`
   - **Replace with**: `DSM001`
   - Click "Replace All"

3. **Download**
   - Click "File > Download > Comma-separated values (.csv)"
   - Save as `candidates.csv`

### Using Excel (Also Easy):

1. **Open your CSV in Excel**

2. **Find & Replace**
   - Press `Ctrl+H`
   - **Find**: `S0108`
   - **Replace with**: `DSM001`
   - Click "Replace All"

3. **Save**
   - Press `Ctrl+S`
   - Choose "CSV (Comma delimited) (*.csv)"

### Using Text Editor (Manual):

If you have many different school codes, use this mapping:

```
Find this in your CSV        Replace with
─────────────────────────    ──────────────
S0108                   →    DSM001
S0109                   →    DSM001  (or different school)
S0110                   →    DSM001
```

**Available schools to use:**
- `DSM001` (Dar es Salaam Primary School)
- `DSM002` (Dar Secondary School)
- `MGO001` (Morogoro Primary School)
- `MGO002` (Morogoro Secondary School)
- `MGO003` (Morogoro Combined School)

---

## Step 2: Go to Import Page (1 minute)

1. **Open your IRMS system**

2. **Navigate to**: `REGISTRATION` > `Candidates`

3. **Look for the green button** with dropdown arrow (top right)

4. **Click the dropdown** and select `Import CSV`

---

## Step 3: Import the File (1 minute)

1. **Click "Import CSV"** button

2. **Select your fixed CSV file** (the one you just saved)

3. **Wait for message**:
   - ✅ Green message = SUCCESS
   - ❌ Red message = ERROR (see troubleshooting below)

4. **Done!** Your candidates are imported

---

## That's It! 

Total time: 5 minutes
Result: All candidates imported into system

---

## Troubleshooting If It Fails

### ❌ "School code 'DSM001' does not exist"

**Problem**: You used a school code that doesn't exist
**Solution**: Verify you're using one of these codes:
- DSM001 ✅
- DSM002 ✅
- MGO001 ✅
- MGO002 ✅
- MGO003 ✅

Check your CSV has one of these.

---

### ❌ "Missing Full Name" or "Missing Sex"

**Problem**: Your CSV has blank cells in those columns
**Solution**: 
1. Open CSV again
2. Check column 2 (Full Name) and column 3 (Sex)
3. Fill in any blanks
4. Save and retry

---

### ❌ "Insufficient columns"

**Problem**: Your CSV doesn't have exactly 6 columns
**Solution**:
Your CSV should have these 6 columns:
1. Index Number
2. Full Name
3. Sex
4. Combination
5. School ID/Code
6. Exam Type

Check you have all 6 in order.

---

## Quick Reference: CSV Format Check

Before you import, verify:

```
✓ Column 1: Index Number (S0108-0501)
✓ Column 2: Full Name (AGRIPINA YOHANA MAGANGA)
✓ Column 3: Sex (F or M)
✓ Column 4: Combination (HGL)
✓ Column 5: School Code (DSM001) ← UPDATED FROM S0108
✓ Column 6: Exam Type (ACSEE)
```

---

## Example: Before & After

### BEFORE (doesn't work):
```
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,S0108,ACSEE
```

### AFTER (works):
```
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,DSM001,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,DSM001,ACSEE
S0108-0503,CHRISTINA JOSEPH KISANJI,F,HGL,DSM001,ACSEE
```

**Notice**: Only column 5 (School Code) changed from S0108 to DSM001

---

## Fastest Possible Path

1. **Open CSV in Google Sheets**: 1 minute
2. **Find & Replace S0108 with DSM001**: 1 minute
3. **Download as CSV**: 30 seconds
4. **Import in IRMS**: 1 minute
5. **Verify success**: 30 seconds

**TOTAL: ~4 minutes**

---

## Done! What's Next?

After import completes:
- ✅ Candidates are in the system
- ✅ Ready for exam registration
- ✅ Can now assign to exams
- ✅ Can manage marks entry

All set for today!

---

## Still Having Issues?

If you hit problems, share:
1. The EXACT error message you see
2. A screenshot of the error
3. A few rows from your CSV file (first 3 rows)

Then I can debug the specific issue.

