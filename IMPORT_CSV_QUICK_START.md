# Import CSV - Quick Start Guide

## Complete Workflow

### Step 1: Click "Import CSV" in Tools Menu
```
Candidates Management Page
  ↓
Click "Tools" button (top right)
  ↓
Click "Import CSV" option
  ↓
✓ Import Candidates modal opens
```

### Step 2: Select Exam Year (Required)
```
Import Candidates Modal
┌─────────────────────────────┐
│ Import Candidates           │
├─────────────────────────────┤
│                             │
│ Exam Year *                 │
│ [Select Exam Year ▼]        │ ← CLICK TO SELECT
│                             │
│ Exam Type (optional)        │
│ [Auto-detect from CSV ▼]    │
│                             │
│ [Cancel]  [Select File]     │
│           (disabled until   │
│           exam year chosen) │
└─────────────────────────────┘

✓ Select exam year from dropdown
✓ "Select File" button becomes enabled
```

### Step 3: Click "Select File" to Open File Picker
```
✓ File picker opens
✓ Select your CSV file
✓ Click "Open"
```

### Step 4a: If Candidates Already Exist (Conflicts Detected)
```
Import Conflicts Modal opens
┌──────────────────────────────────┐
│ Import Conflicts Detected        │
├──────────────────────────────────┤
│                                  │
│ 5 candidate(s) already exist     │
│ in the system. Choose how you    │
│ want to handle them:             │
│                                  │
│ Conflicting IDs:                 │
│ • S1378-0501                     │
│ • S1378-0502                     │
│ • S1378-0503                     │
│ ... and 2 more                   │
│                                  │
│ ◉ Skip Existing Records          │ ← Selected by default
│   Only import new records        │
│                                  │
│ ○ Replace Existing Records       │
│   Update existing, add new       │
│                                  │
│ ○ Replace All                    │
│   Delete all and reimport fresh  │
│                                  │
│ [Cancel]  [Import]               │
└──────────────────────────────────┘

CHOOSE ONE:
  ◉ Skip (recommended for safety)
     → Only adds new candidates, keeps existing ones
     
  ○ Replace (update existing)
     → Updates fields of existing candidates
     → Adds candidates not in system
     
  ○ Replace All (CAUTION)
     → Deletes ALL existing candidates
     → Imports fresh from CSV
     → WARNING: Cannot be undone
```

### Step 4b: No Conflicts (Fresh Import)
```
✓ Auto-imports with "Skip" mode
✓ Success message appears
✓ Candidates table refreshes
```

### Step 5: Import Complete
```
Success! 25 candidates imported successfully
(0 skipped, 0 replaced)

✓ New candidates added to table
✓ Can now manage them in the interface
✓ Can view, edit, or delete as needed
```

## CSV File Format

Your CSV file should have these columns:
```csv
candidate_id,full_name,sex,combination,school_code,exam_type
S1378-0501,ADVENTINA GIDIONI ELIA,F,CBE,1378,ACSEE
S1378-0502,AGRIPINA MAKOBE LUSATO,F,CBE,1378,ACSEE
S1378-0503,ASIFIWEELI SENYAELI PALLANGYO,F,CBE,1378,ACSEE
```

**Required Columns**:
- `candidate_id` - Should start with school code (e.g., S1378-0001)
- `full_name` - Student's full name
- `sex` - M or F
- `school_code` - School's numeric code (e.g., 1378)
- `exam_type` - PSLE, CSEE, or ACSEE

**Optional Columns**:
- `combination` - Only used for ACSEE (e.g., PCM, PCB, CBE)

## Download CSV Template

1. Click "Tools" button
2. Click "CSV Template"
3. Save the template file
4. Fill in your candidate data
5. Save and use for import

## Common Messages

### ✅ Success
```
"Candidates imported successfully (25 records)"
"3 skipped, 2 replaced"
```

### ⚠️ Conflict Detected
```
"5 candidate(s) already exist in the system"
```
→ Choose how to handle: Skip, Replace, or Replace All

### ❌ Error
```
"Error checking conflicts"
"Invalid CSV format"
```
→ Check CSV has required columns
→ Check exam year was selected
→ Check file is valid CSV format

## Tips & Tricks

✓ **Safe Import**: Use "Skip Existing Records" mode
  - New candidates added
  - Existing candidates not changed
  - No data loss risk

✓ **Update Data**: Use "Replace Existing Records" mode
  - Updates candidate information
  - Adds new candidates
  - Safe and reversible

✗ **Avoid "Replace All"**: Unless you're certain
  - Deletes ALL candidates in system
  - No undo available
  - Use only for fresh data imports

✓ **Prepare CSV First**: 
  - Verify all required columns present
  - Check data format matches
  - Test on small batch first

✓ **Check Results**:
  - Read success message
  - Review imported count
  - Verify in candidates table

---
**Need Help?**
- Download CSV template to see format
- Check error messages for guidance
- Verify school codes match existing schools
- Ensure exam year matches actual exam year
