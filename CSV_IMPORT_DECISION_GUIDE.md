# CSV Import - Decision Guide

## The Main Issue

Your CSV uses school codes like `S0108` but the database doesn't have schools with those codes. Currently the database has: `DSM001`, `DSM002`, `MGO001`, etc.

---

## Decision Matrix: What Should You Do?

### Decision Point 1: School Code Existence

```
Does your system already have schools with these codes?
(S0108, etc.)
│
├─ YES → Go to Option 1
│
└─ NO → Choose between Option 2a, 2b, or 2c
```

---

## 🔵 Option 1: Schools Already Exist in Database

**If your system already has school S0108 (just not showing in recent list)**

### Action:
1. Go to SETTINGS > Schools Management
2. Verify school S0108 exists
3. If it does → Retry your CSV import

### Why it works:
- Import validation checks: `School::where('code', 'S0108')->first()`
- If school exists, import proceeds
- If school doesn't exist, import fails

---

## 🟢 Option 2a: Quick Fix - Use Existing Schools (Fastest)

**Pros:**
- ✅ Fastest solution (5 minutes)
- ✅ No database changes needed
- ✅ Can import immediately

**Cons:**
- ❌ Your original school codes are lost
- ❌ All candidates assigned to existing schools
- ❌ Need to update CSV file

### Action Steps:

1. **Open your CSV in Excel/Google Sheets**

2. **Replace all school codes**:
   - Find: `S0108`
   - Replace with: `DSM001` (or appropriate school)
   - (Or use a mapping table if you have multiple schools)

3. **Save as CSV**

4. **Go to REGISTRATION > Candidates**

5. **Click Tools > Import CSV**

6. **Select updated CSV**

### Example:
```csv
Before:
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,S0108,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,S0108,ACSEE

After:
S0108-0501,AGRIPINA YOHANA MAGANGA,F,HGL,DSM001,ACSEE
S0108-0502,BERTHA OSWALD IMALLE,F,HGL,DSM001,ACSEE
```

---

## 🟡 Option 2b: Create Missing Schools (Balanced)

**Pros:**
- ✅ Preserves original school codes
- ✅ Accurate data representation
- ✅ Scales for future imports

**Cons:**
- ❌ Takes longer (15-30 minutes)
- ❌ Need to create schools first
- ❌ Need to ensure school details are correct

### Action Steps:

1. **Extract unique school codes from your CSV**
   ```
   Find all: S0108, S0109, S0110, etc.
   ```

2. **Create schools in bulk** (if many schools):
   - Go to REGISTRATION > Districts
   - Click Tools > Bulk Import
   - Create CSV with school data
   - Import

3. **Create schools individually** (if few schools):
   - Go to SETTINGS > Schools Management
   - Click "+ Add School"
   - Fill in:
     - Code: S0108
     - Name: [Get from your data or reference]
     - Region: [Select appropriate]
     - Type: PRIMARY/SECONDARY/BOTH
   - Click Create
   - Repeat for each school code

4. **Once all schools exist**, import your candidates CSV

### School Creation CSV Format (if bulk creating):
```csv
Code,Name,Region,District,Type
S0108,School Name,MOROGORO,GAIRO DC,SECONDARY
S0109,School Name,MOROGORO,GAIRO DC,SECONDARY
```

---

## 🔴 Option 2c: Create Schools + CSV Mapping (Comprehensive)

**Pros:**
- ✅ Most organized approach
- ✅ Clear audit trail
- ✅ Best for large scale imports

**Cons:**
- ❌ Takes most time (30-60 minutes)
- ❌ Requires planning
- ❌ Most complex

### Action Steps:

1. **Create mapping document**:
   ```
   School Code | School Name | Region | District | Type
   S0108       | ? | ? | ? | ?
   S0109       | ? | ? | ? | ?
   ```

2. **Gather school information** from source data

3. **Create schools in database** (bulk or individual)

4. **Import candidates CSV** with verified data

---

## Quick Decision Tree

```
                    Need to Import CSV
                           │
                ┌──────────┴──────────┐
                │                     │
          1. Can Wait?          2. Need Right Away?
                │                     │
                │                     ├─→ Option 2a (Quickest)
                │                     │   Use existing schools
                │                     │   (5 min)
                │                     │
          Can Take             Option 2b (Good balance)
          More Time?           Create missing schools
                │              (15-30 min)
                │
                └─→ Option 2c (Best practice)
                    Full mapping
                    (30-60 min)
```

---

## Recommended Path by Scenario

### Scenario A: "I need to get data in NOW"
→ **Use Option 2a**
- Change school codes in CSV to existing ones
- Import immediately
- You can fix school assignments later

### Scenario B: "I want to do this right"
→ **Use Option 2b**
- Create the missing schools (takes 15-30 min per batch)
- Then import with original codes
- Data is correct

### Scenario C: "This is a large migration"
→ **Use Option 2c**
- Create complete mapping
- Bulk create schools
- Bulk import candidates
- Audit trail is clear

### Scenario D: "I don't care about school codes"
→ **Use Option 2a**
- Just map to existing schools
- Get data in
- Move on

---

## Implementation Checklist

### For Option 2a (Quick Fix):

```
[ ] Extract all unique school codes from CSV
[ ] Choose existing school to map them to
[ ] Use Find & Replace in Excel/Sheets
[ ] Save updated CSV
[ ] Go to REGISTRATION > Candidates
[ ] Click Tools > Import CSV
[ ] Select file
[ ] Wait for success message
[ ] Verify candidates imported
```

### For Option 2b (Balanced):

```
[ ] Extract all unique school codes from CSV
[ ] For each school code:
    [ ] Go to SETTINGS > Schools Management
    [ ] Click "+ Add School"
    [ ] Enter Code: [school code]
    [ ] Enter Name: [school name]
    [ ] Select Region: [appropriate region]
    [ ] Select Type: PRIMARY/SECONDARY/BOTH
    [ ] Click Create
[ ] Go to REGISTRATION > Candidates
[ ] Click Tools > Import CSV
[ ] Select your original CSV
[ ] Wait for success message
[ ] Verify candidates imported
```

### For Option 2c (Comprehensive):

```
[ ] Extract all unique school codes from CSV
[ ] Create mapping spreadsheet
[ ] Fill in school names/regions/types
[ ] Export as CSV if doing bulk import
[ ] Bulk import schools
    OR create individually
[ ] Verify all schools created
[ ] Go to REGISTRATION > Candidates
[ ] Click Tools > Import CSV
[ ] Select your candidate CSV
[ ] Wait for success message
[ ] Verify candidates imported
[ ] Audit: Check each school has correct candidates
```

---

## Current Database State

**Schools that exist:**
- DSM001 → Dar es Salaam Primary School
- DSM002 → Dar Secondary School
- MGO001 → Morogoro Primary School
- MGO002 → Morogoro Secondary School
- MGO003 → Morogoro Combined School

**Your CSV needs:**
- S0108, S0109, ... (currently don't exist)

**Action required:**
- Either create these schools (Option 2b/2c)
- Or map to existing schools (Option 2a)

---

## Which Option Should You Choose?

### Choose 2a if:
- You just want to import the data
- You don't care about preserving school codes
- You're in a hurry
- School assignment can be corrected later

### Choose 2b if:
- You want to preserve school codes
- You have a reasonable number of schools (< 20)
- You want to be done in 30 minutes
- Data accuracy is important

### Choose 2c if:
- You're importing large amounts of data
- You want a clear audit trail
- Multiple people are involved
- This is a one-time or recurring migration

---

## What Will Happen After Fixing

```
1. CSV is prepared (either option)
2. You import candidates
3. System validates:
   - 6 columns present
   - School code exists
   - Required fields filled
4. If all valid → Candidates imported
5. If errors → Error list shown
6. You can retry or fix and reimport
```

---

## Estimated Time by Option

| Option | Setup Time | Execution | Total | Data Accuracy |
|--------|-----------|-----------|-------|---|
| 2a | 5 min | 1 min | **6 min** | ⭐⭐ |
| 2b | 20 min | 5 min | **25 min** | ⭐⭐⭐⭐ |
| 2c | 45 min | 10 min | **55 min** | ⭐⭐⭐⭐⭐ |

---

## Ready to Proceed?

1. **Choose your option** (2a, 2b, or 2c)
2. **Follow the steps** for that option
3. **Try importing** again
4. **Check results** - errors should be resolved

If you still get errors after following these steps, share the exact error message and I'll help debug further.
