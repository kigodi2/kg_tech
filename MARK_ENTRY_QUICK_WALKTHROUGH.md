# Mark Entry - Quick Walkthrough

**Goal**: How to enter ACSEE marks for a specific year and school

---

## 5-Minute Walkthrough

### Step 1: Open Mark Entry Page
```
URL: /mark-entry
You see: "ACSEE Mark Entry" page
```

### Step 2: Set the Academic Year ⭐ (THIS ANSWERS YOUR QUESTION)
```
Look for: "Year *" field at the top left
Action: Type the year (e.g., 2026)

Field appears as:
┌────────────────────┐
│ Year *             │
├────────────────────┤
│ [2026            ] │  ← Type year here
└────────────────────┘

Min: 2000
Max: (current year + 1)
Default: Automatically set to current year
```

### Step 3: Select Region
```
Field: "Region"
Action: Click dropdown and select region
Example: "Dar es Salaam"
```

### Step 4: Select District
```
Field: "District"
Action: Click dropdown and select district
Note: Automatically filtered by selected region
Example: "Dar es Salaam City"
```

### Step 5: Select School
```
Field: "School"
Action: Click dropdown and select school
Note: Automatically filtered by selected district
Example: "Lugalo Secondary School"
```

### Step 6: Select Subject
```
Field: "Subject"
Action: Click dropdown and select subject
Note: Shows ONLY subjects with registered ACSEE candidates
Example: "Physics"
Candidate count shown: "150 candidates registered"
```

### Step 7: Download Template
```
Action: Click "Download Template" button
Result: CSV file downloads
Name: LUGALO_SECONDARY_SCHOOL_PHY.csv
Contains:
  - index_number (e.g., A12345)
  - sex (e.g., M, F)
  - paper_p1, paper_p2, paper_p3 (empty, ready for marks)
```

### Step 8: Fill in Marks
```
Open CSV in Excel or Google Sheets
Add marks for each paper/practical/project
Example:
  index_number | sex | paper_p1 | paper_p2 | paper_p3
  A12345       | M   | 75       | 82       | 88
  B23456       | F   | 68       | 71       | 79
  ...
Save the file
```

### Step 9: Upload Marks
```
Action: Click "Upload Marks" button
Select: The CSV file you just filled
System checks:
  ✓ Year matches (2026)
  ✓ School matches (Lugalo)
  ✓ Subject matches (Physics)
  ✓ Candidates match (checksum verification)
  ✓ No candidates added/removed
  ✓ Headers not modified
Result: "100 records imported successfully"
```

### Step 10: View Results
```
Page shows:
  - Batch ID
  - Records imported: 100
  - Records valid: 100
  - Records with errors: 0
  - Locked rows: 100
Done! All marks are now locked and cannot be edited.
```

---

## Where Each Setting Goes

### Year Setting
```
┌─────────────────────────────────────────────────┐
│ ACSEE Mark Entry                                │
├─────────────────────────────────────────────────┤
│ 1. Select Context                               │
│                                                 │
│ ┌─────┐ ┌────────┐ ┌──────────┐ ┌────────────┐│
│ │Year*│ │Region  │ │District  │ │School      ││
│ │2026 │ │Dar es  │ │Dar City  │ │Lugalo Sec ││
│ └─────┘ │Salaam  │ │          │ │School     ││
│         └────────┘ └──────────┘ └────────────┘│
│  ↑                                             │
│  SET THIS FIRST (before downloading template) │
│                                                 │
│ ┌────────────────────────────────────────────┐│
│ │Subject: Physics (150 candidates registered)││
│ └────────────────────────────────────────────┘│
│                                                 │
│ [Download Template]  [Upload Marks]            │
└─────────────────────────────────────────────────┘
```

---

## What Gets Embedded in Template

When you click "Download Template":
```
Template includes year: 2026

CSV Content:
─────────────────────────────────────────
index_number,sex,paper_p1,paper_p2,paper_p3
A12345,M,,,
B23456,F,,,
C34567,M,,,
─────────────────────────────────────────

Hidden checksum (SHA-256) includes:
  - Year: 2026 ✓
  - School: Lugalo Secondary School ✓
  - Subject: Physics ✓
  - Candidates: A12345, B23456, C34567 ✓
  - Headers: index_number, sex, paper_p1, paper_p2, paper_p3 ✓

When you upload, system verifies:
  Year must be: 2026 ✓
  All candidates must match ✓
  No new candidates can be added ✓
  No candidates can be removed ✓
  Headers cannot be changed ✓
```

---

## Common Workflow Scenarios

### Scenario 1: Enter Marks for One School
```
Year: 2026
Region: Dar es Salaam
District: Dar es Salaam City
School: Lugalo Secondary School
Subject: Physics
→ Download template, fill, upload
```

### Scenario 2: Enter Marks for Multiple Schools
```
School 1 - Lugalo Secondary:
  Year: 2026 → Physics → Download, fill, upload
  Year: 2026 → Chemistry → Download, fill, upload

School 2 - Shinyanga:
  Year: 2026 → Biology → Download, fill, upload
```

### Scenario 3: Enter Marks for Multiple Years
```
Year 2024:
  Region: Dar → District: City → School: Lugalo → Subject: Physics
  → Download, fill, upload

Year 2025:
  Region: Dar → District: City → School: Lugalo → Subject: Physics
  → Download, fill, upload (NEW template for year 2025)

Year 2026:
  Region: Dar → District: City → School: Lugalo → Subject: Physics
  → Download, fill, upload (NEW template for year 2026)
```

---

## What Year Means in This Context

| Term | Meaning | Example |
|------|---------|---------|
| **Year** | Academic year of exams | 2026 (for ACSEE exams held in 2026) |
| **Exam Year** | Same as Year | 2026 |
| **Academic Year** | Same as Year | 2026 |
| **NOT Calendar Year** | (different in some systems) | Here, 2026 = school year 2026 |

---

## Validation Rules

### Year Field
```
Minimum: 2000
Maximum: Current year + 1
Required: YES ✓
Default: Current year (e.g., 2026)
Input type: Number only
```

### Region Field
```
Required: NO (optional)
If selected: Filters districts
If not selected: Shows all districts
```

### District Field
```
Required: NO (optional)
Depends on: Region selected (optional)
If selected: Filters schools
If not selected: Shows all schools
```

### School Field
```
Required: YES ✓
Depends on: District selected (optional)
Example: Lugalo Secondary School
```

### Subject Field
```
Required: YES ✓
Depends on: School selected
Shows: ONLY subjects with ACSEE candidates registered
Example: Physics (shows "150 candidates registered")
```

---

## After Upload - What Happens

### Automatic Actions
```
1. CSV parsed
2. Year verified (must match)
3. Candidates verified (must match template)
4. Checksum verified (must match)
5. RawMark records created
6. Validation runs (checks mark ranges, etc.)
7. Rows locked (is_locked = true)
8. Audit log recorded
```

### Result Messages
```
Success:
  "100 records imported successfully"
  "Valid records: 100"
  "Locked rows: 100"

Error:
  "Uploaded CSV does not match the generated template"
  "Year mismatch"
  "Candidates added/removed"
```

---

## Key Points to Remember

✅ **DO**:
- Set year BEFORE downloading template
- Use same year for upload as download
- Keep CSV file in same format (don't rearrange columns)
- Fill in all required candidates

❌ **DON'T**:
- Change year between download and upload
- Modify CSV headers
- Add new candidates to CSV
- Remove candidates from CSV
- Rearrange candidate rows

---

## Year Setting - Final Answer

**Q: Where do I set the academic year?**

A: In the **"Year" field** at the top of the Mark Entry page

```
┌──────────────────────────────────────┐
│ ACSEE Mark Entry                     │
├──────────────────────────────────────┤
│ 1. Select Context                    │
│                                      │
│ Year *: [2026]  ← TYPE HERE          │
│          ↑                            │
│          Defaults to current year    │
│          Valid range: 2000-2027      │
│                                      │
│ Then select Region, District, etc.   │
└──────────────────────────────────────┘
```

**Q: When do I set it?**

A: **BEFORE** downloading the template. It cannot be changed afterwards without re-downloading.

**Q: Why does it matter?**

A: The year is embedded in the CSV checksum. If you download for 2026 but upload for 2025, the system will reject it (checksum mismatch).

---

## Need More Help?

- **Template Download Issues**: Check year, school, and subject are selected
- **Upload Rejection**: Verify CSV not modified, same year as download
- **Missing Candidates**: Confirm ACSEE candidates exist for that year/school
- **Authorization Issues**: Contact system admin, may need unlock permission

---

**Quick Reference**: Set Year → Select Filters → Download Template → Fill Marks → Upload

**Document Version**: 1.0  
**Last Updated**: February 1, 2026
