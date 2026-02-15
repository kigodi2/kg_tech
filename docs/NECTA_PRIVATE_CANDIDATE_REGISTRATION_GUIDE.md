# NECTA Private Candidate Registration & Subject Assignment Guide

## Purpose
This guide explains how to register PRIVATE candidates in IRMS and assign subjects to them following NECTA ACSEE (Advanced Certificate of Secondary Education Examination) standards. It covers both manual registration in the UI and bulk import via CSV. All information is based on the actual IRMS implementation (Laravel 10 + Alpine.js).

---

## Section 1: Registering a PRIVATE Candidate

### Overview
Private candidates are registered in the same location as school candidates but with a specific index number format and candidate type. The system auto-detects whether a candidate is PRIVATE based on their index number prefix.

### Step-by-Step Registration

#### Step 1: Navigate to Candidate Registration
1. Go to **REGISTRATION** → **Candidates** (top navigation menu)
2. Click the green **"Register"** button in the top right

#### Step 2: Fill in Basic Information

| Field | Required? | Notes |
|-------|-----------|-------|
| **Index Number** | ✅ Yes | Format: `P1234-5678` (P-prefix, 4-digit centre code, hyphen, 4-digit serial). Example: `P0652-0501` |
| **Full Name** | ✅ Yes | Name of the candidate (e.g., "John Doe") |
| **Sex** | ✅ Yes | Select "Male (M)" or "Female (F)" |
| **School** | ✅ Yes | For private candidates, still select a school (they may be affiliated with a private centre/institution) |
| **Exam Type** | ✅ Yes | Select **ACSEE** (required for private subject assignment) |
| **Exam Year** | ✅ Yes (if ACSEE) | Select the active exam year (e.g., 2026) |
| **Combination** | ❌ No | **LEAVE BLANK for private candidates** (see Note below) |
| **Candidate Type** | ℹ️ Auto | System will auto-detect as **PRIVATE** based on P-prefix in index number |

**CRITICAL NOTE on Combination field:**
- The **Combination** field is disabled unless you select "ACSEE" as the exam type
- For PRIVATE candidates, **combination is NOT used** in IRMS
- Private candidates assign subjects individually in the "Manual Subject Selection" mode (see Section 2)
- Leave the field blank — do NOT manually enter a combination code

#### Step 3: Verify Index Number Validation

When you type a valid P-prefix index number (e.g., `P0652-0501`), you will see:

**✅ Success Message:**
```
✓ Valid index number
  Type: PRIVATE
  Centre found
```

**⚠️ If validation fails, you may see:**
- `"Index number cannot be empty"` — Type in the index number
- `"Invalid format. Use: CCCC-SSSS (e.g., S0445-0001)"` — Check the format
- `"Centre not found in system"` — The private centre code (P####) doesn't exist yet (see Troubleshooting)
- `"This index number is already registered for this exam"` — Duplicate detected

#### Step 4: Submit the Registration

1. Click the blue **"Register Candidate"** button
2. The system will:
   - Create the candidate record
   - Auto-set `candidate_type = PRIVATE`
   - Register them for ACSEE exam (if selected)
3. You'll see: `"Candidate registered successfully"`

**The candidate is now ready for subject allocation.**

---

## Section 2: Assigning Subjects to a PRIVATE Candidate (Manual Mode)

### Overview
After registering a PRIVATE candidate for ACSEE, you must assign subjects to them. This is done in the **ACSEE Exam Type** module using the **Manual Subject Selection** mode.

### Why Manual Mode for Private Candidates?
- Private candidates do NOT use combination templates (unlike school candidates)
- Each private candidate can have a custom subject selection
- The system validates that all NECTA rules are met (General Studies mandatory, ≥3 principals)

### Step-by-Step Subject Assignment

#### Step 1: Navigate to ACSEE Module
1. Go to **EXAM TYPE** (top navigation menu)
2. Click on **ACSEE** (or select from the dropdown)
3. This opens the ACSEE Exam Type dashboard

#### Step 2: Find Your Candidate in the Candidates Tab
1. Ensure you're on the **Candidates** tab
2. Search for your private candidate by:
   - Name (e.g., "John Doe")
   - Index number (e.g., "P0652-0501")
   - Or scroll through the list
3. Click the **blue pencil/edit icon** (or the row) to open the allocation modal

#### Step 3: Open the Allocation Modal
The modal appears with two tabs:
- **Apply Combination Template** (for school candidates)
- **Manual Subject Selection** (for private candidates) ← **Select this one**

#### Step 4: Switch to "Manual Subject Selection" Mode
1. Click the **"Manual Subject Selection"** tab
2. You'll see:
   - An **Exam Year** dropdown (required)
   - A list of all ACSEE subjects with checkboxes
   - A note: *"Required: General Studies + at least 3 other subjects"*

#### Step 5: Select Exam Year
1. Click the **Exam Year** dropdown
2. Select the exam year (e.g., **2026**)
3. **This is mandatory** — the allocation cannot be saved without it

#### Step 6: Select Subjects
**Follow these rules:**

1. **Find and CHECK "General Studies" (code 111)**
   - This is **MANDATORY**
   - If not checked, the system will reject the allocation with:
     ```
     "General Studies (code 111) is mandatory for ACSEE candidates"
     ```

2. **Check at least 3 PRINCIPAL subjects** (in addition to General Studies)
   - Principal subjects = any subject except General Studies
   - Examples: Math, Physics, Chemistry, Biology, English, Swahili, etc.
   - If fewer than 3 principals are selected, you'll get:
     ```
     "Minimum 3 principal subjects required (found X)"
     ```
   - **Total minimum subjects = 1 (General Studies) + 3 (principals) = 4**

**Example Valid Selection:**
```
☑ General Studies (code 111) — Mandatory
☑ Mathematics (code 001)     — Principal 1
☑ Physics (code 002)         — Principal 2
☑ Chemistry (code 003)       — Principal 3
☑ English (code 005)         — Principal 4 (optional, but allowed)
```

#### Step 7: Verify Selections
- Subjects with code **111** show a yellow badge: "Mandatory"
- Other selected subjects are principal subjects
- You can select as many principals as needed (no upper limit in NECTA rules, but practical limit is usually 4-5)

#### Step 8: Handle Existing Allocations

**Two modes are available:**

| Option | Behavior | When to Use |
|--------|----------|------------|
| **Add missing only** (default) | Adds new subjects; keeps existing | First-time allocation or adding more subjects |
| **Replace existing allocations** (checkbox) | Deletes all old subjects; saves only new ones | Correcting or changing the entire allocation |

**If you check "Replace existing allocations":**
- You'll see a warning: `"⚠ This will remove all existing subject allocations for this exam year..."`
- A confirmation dialog appears before saving (click **OK** to proceed)

#### Step 9: Save the Allocation
1. Click the blue **"Save Allocation"** button
2. The system will:
   - Validate all NECTA rules
   - Check for duplicates
   - Save to the database
3. **Success response:** `"Subjects allocated successfully"`
4. The modal closes automatically

**The allocation is now saved in the database.**

---

## Section 3: Validation Rules & Error Messages

### NECTA Rules Enforced by IRMS

| Rule | Error Message | Fix |
|------|---------------|-----|
| **General Studies is mandatory** | `"General Studies (code 111) is mandatory for ACSEE candidates"` | Check the General Studies checkbox |
| **Minimum 3 principals required** | `"Minimum 3 principal subjects required (found X)"` | Check at least 3 more subjects (non-GS) |
| **General Studies must exist in system** | `"General Studies subject not configured in system"` | Contact admin — GS subject code 111 missing from database |
| **Duplicate subjects detected** | `"Duplicate subjects detected and will be removed: X, Y, Z"` | Duplicates are automatically removed (just a warning) |
| **Exam year required** | `"Please select an exam year"` | Choose an exam year from the dropdown |
| **Candidate has no exam registration** | `"Candidate does not have an exam registration"` | Re-register the candidate for ACSEE first |

### Index Number Validation Errors (During Registration)

| Error | Cause | Fix |
|-------|-------|-----|
| `"Index number cannot be empty"` | Field was left blank | Type a valid index number (e.g., P0652-0501) |
| `"Invalid format. Use: CCCC-SSSS (e.g., S0445-0001)"` | Wrong format (missing hyphen, wrong length, etc.) | Use format: `P` + 4 digits + `-` + 4 digits |
| `"Centre not found in system"` | Private centre code (P####) not registered | Register the private centre first (see Troubleshooting) |
| `"This index number is already registered for this exam"` | Duplicate index in same exam year | Use a different index number or contact admin |
| `"Unknown centre prefix. Must be S (School) or P (Private)"` | Prefix is neither S nor P | Use S for school or P for private |

---

## Section 4: Verification & Database Queries

### Verify Subject Allocation Was Saved

#### Method A: Using Tinker (Command Line)

```bash
# Open tinker session
php artisan tinker

# Find the candidate
$candidate = App\Models\Candidate::where('candidate_id', 'P0652-0501')->first();

# Check their subject allocations for ACSEE 2026
$selections = $candidate->subjectSelections()
    ->where('exam_type_id', 1)  // ACSEE exam type ID
    ->where('year', 2026)
    ->with('subject')
    ->get();

# Display the results
$selections->each(fn($s) => echo "{$s->subject->code} {$s->subject->name} (Principal: {$s->is_principal})\n");

# Count subjects
echo "Total subjects: " . $selections->count() . "\n";
```

#### Method B: Direct SQL Query

```sql
-- Check allocations for a private candidate
SELECT 
    c.candidate_id,
    c.full_name,
    s.code,
    s.name,
    css.is_principal,
    css.source,
    css.created_by,
    u.name as allocated_by,
    css.created_at
FROM candidate_subject_selections css
JOIN candidates c ON c.id = css.candidate_id
JOIN subjects s ON s.id = css.subject_id
LEFT JOIN users u ON u.id = css.created_by
WHERE c.candidate_id = 'P0652-0501'
  AND css.year = 2026
ORDER BY css.created_at ASC;
```

### Understanding the `candidate_subject_selections` Table

| Column | Meaning |
|--------|---------|
| `candidate_id` | FK to candidates table |
| `exam_type_id` | FK to exam_types (e.g., 1 for ACSEE) |
| `exam_year_id` | FK to exam_years (e.g., 1 for 2026) |
| `subject_id` | FK to subjects (e.g., 111 for General Studies) |
| `year` | Exam year as numeric label (e.g., 2026) |
| `is_principal` | Boolean: true if principal subject, false if General Studies |
| `is_active` | Boolean: true if currently active |
| `source` | String: `'manual'` (user selected) or `'template'` (from combination) |
| `created_by` | FK to users: who allocated this subject |
| `created_at` / `updated_at` | Timestamps |

### Expected Values for Private Candidates

**For a valid private candidate allocation:**
- `source` should be `'manual'` (not `'template'`)
- Exactly one subject with `is_principal = false` and subject code 111 (General Studies)
- At least 3 subjects with `is_principal = true`
- `is_active = true` for all active allocations
- `exam_type_id = 1` (ACSEE)
- `year = 2026` (or current exam year)

---

## Section 5: Special Notes

### Should Private Candidates Use Combination Templates?

**Answer: NO.**

- **Combination templates** are pre-defined subject sets for **SCHOOL candidates**
- Examples: PCM (Physics + Chemistry + Math), PCB, HGE, etc.
- Private candidates have **individual, flexible subject selection**
- If a private candidate is selected in the "Apply Combination Template" tab, they can choose a template, but it's **NOT recommended** because:
  - Templates are designed for schools, not private centres
  - Private candidates should use "Manual Subject Selection" for custom choices

**Best Practice:**
- Always use **"Manual Subject Selection"** for private candidates
- This ensures the operator consciously chooses subjects for each private candidate individually

### What if the Combination Field Appears in Registration?

The "Combination" field in the candidate registration form is:
- **Disabled** if exam type is not ACSEE
- **Enabled** if exam type is ACSEE
- **For school candidates only** (stores a predefined combination code like "PCM")
- **For private candidates: leave it BLANK**

Private candidates don't use combination codes. Their subjects are assigned later in the ACSEE module using Manual Subject Selection.

### "Replace Allocations" vs "Add Missing Only"

| Mode | Behavior | Data Impact |
|------|----------|-------------|
| **Add missing only** | New subjects added; old ones kept | Safe — no data deletion |
| **Replace allocations** | All old subjects deleted; new ones saved | Destructive — all previous allocations removed for this exam year |

**When to use Replace:**
- Correcting a completely wrong allocation
- Re-doing a candidate's entire subject selection
- Only if you're sure about the new selection

**Warning:** Replace requires confirmation dialog — system shows:
```
CONFIRM DELETE & REPLACE

Candidate: [Name]
Exam Year: [Year]

This will PERMANENTLY DELETE all existing subject allocations 
for this exam year and replace them with the selected subjects.

This action CANNOT be undone.

Continue?
```

---

## Section 6: Common Errors & Troubleshooting

### Modal Won't Open / Modal Won't Close

| Symptom | Cause | Fix |
|---------|-------|-----|
| Modal doesn't appear when clicking candidate | Candidate has no exam registration | Go back to Candidates tab; verify candidate is registered for ACSEE; if not, re-register them |
| Modal closes without saving | Validation failed before save | Check error/warning messages at bottom of modal; fix issues (missing GS, <3 principals, etc.) |
| Modal shows loading spinner forever | API timeout or network error | Refresh page; try again; check browser console for errors (F12 → Console tab) |
| Can't switch between "Template" and "Manual" tabs | UI bug (rare) | Refresh the page; close and reopen the modal |

### API Returns 422 Validation Error

**422 = Validation failed**

| Error Message | Cause | Fix |
|---------------|-------|-----|
| `"General Studies (code 111) is mandatory for ACSEE candidates"` | GS checkbox not checked | Check the General Studies checkbox |
| `"Minimum 3 principal subjects required (found 2)"` | Not enough principals selected | Select 3 or more subjects besides General Studies |
| `"Candidate does not have an exam registration"` | Candidate not registered for ACSEE | Register them in the Candidates tab first |
| `"Exam year and type required for validation"` | Missing exam year | Select an exam year from the dropdown |

### Centre Not Found or Index Number Mismatch

| Problem | Cause | Solution |
|---------|-------|----------|
| **Error: "Centre not found in system"** during registration | Private centre code (e.g., P0652) doesn't exist in schools table | **Action:** Contact admin to create the private centre in SETTINGS → Schools. Create a record with `registration_number = P0652` and mark as private centre |
| **Duplicate index number error** | Another candidate already has this index in the same exam year | Change the serial part (last 4 digits) or contact admin to manage duplicates |
| Index number format rejected | Format is not `CCCC-SSSS` (e.g., `P0652-0501`) | Fix the format; use exactly: Prefix (1 char) + 4 digits + hyphen + 4 digits |

### Duplicate Allocations

| Symptom | Cause | Fix |
|---------|-------|-----|
| Same subject appears twice in allocation | User checked the checkbox twice (system prevents this) | Refresh the page; the duplicate will be auto-removed |
| Allocation shows different subjects in different views | Cache issue or page not refreshed after save | Hard refresh browser (Ctrl+F5 or Cmd+Shift+R) |

### Missing Exam Year

| Symptom | Cause | Fix |
|---------|-------|-----|
| "Please select an exam year" error | Exam year dropdown shows no options | Contact admin to create exam years in SETTINGS → Exam Years |
| Dropdown is empty | No active exam years in system | Admin must create an exam year (e.g., 2026) |

---

## Section 7: Bulk Import via CSV (Optional)

### Private Candidate CSV Format

If you're importing private candidates in bulk via CSV:

```csv
candidate_id,full_name,gender,exam_type,exam_year,school_code,subjects
P0652-0501,John Doe,M,ACSEE,2026,P0652,111|001|002|003
P0652-0502,Jane Smith,F,ACSEE,2026,P0652,111|005|006|007
```

| Column | Example | Notes |
|--------|---------|-------|
| `candidate_id` | `P0652-0501` | P-prefix, 4-digit centre, hyphen, 4-digit serial |
| `full_name` | `John Doe` | Full name of candidate |
| `gender` | `M` or `F` | Male or Female |
| `exam_type` | `ACSEE` | Always ACSEE for private candidate subject allocation |
| `exam_year` | `2026` | Year label |
| `school_code` | `P0652` | Private centre code (matches first part of index number) |
| `subjects` | `111\|001\|002\|003` | Subject IDs separated by pipe (`\|`). Must include 111 (GS) + ≥3 principals |

**System will auto-validate during import:**
- Checks General Studies is present
- Checks ≥3 principal subjects
- Rejects invalid combinations
- Shows error report if validation fails

---

## Quick Reference Checklist

### Before Registering a PRIVATE Candidate ✓
- [ ] Index number format is correct: `P` + 4 digits + `-` + 4 digits
- [ ] Private centre exists in system (or ask admin to create it)
- [ ] Exam type is set to ACSEE
- [ ] Exam year is selected
- [ ] Combination field is **BLANK** (not filled)

### Before Assigning Subjects ✓
- [ ] Candidate is registered for ACSEE
- [ ] Switched to "Manual Subject Selection" tab
- [ ] Selected General Studies (code 111)
- [ ] Selected at least 3 principal subjects (total ≥4)
- [ ] Exam year is selected

### After Allocation ✓
- [ ] System shows "Subjects allocated successfully"
- [ ] Modal closed automatically
- [ ] Verify in database: `SELECT * FROM candidate_subject_selections WHERE candidate_id = 'P0652-0501' AND year = 2026`

---

## Contact & Support

If you encounter issues not covered here:
1. **Check browser console** (F12 → Console) for JavaScript errors
2. **Check server logs** (`storage/logs/laravel.log`)
3. **Contact admin** with:
   - Exact error message
   - Candidate index number
   - Exam year
   - Steps you took
   - Screenshot if possible

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-15  
**NECTA Compliance:** ACSEE Subject Allocation Standards
