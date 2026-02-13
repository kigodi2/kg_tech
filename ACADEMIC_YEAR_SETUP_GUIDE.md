# Academic Year Setup Guide - ACSEE Mark Entry

**Question**: Where do I set the academic year (exam year)?

**Answer**: The academic year is set in the **Mark Entry UI**, not in configuration files.

---

## How to Set Academic Year

### Location: Mark Entry Page
**URL**: `/mark-entry` (or your application's mark entry endpoint)

### UI Element: "Year" Input Field

**Section**: "1. Select Context" (top of the page)

**Step-by-Step**:
1. Navigate to the **ACSEE Mark Entry** page
2. Look for the **"Year"** field in the "1. Select Context" section
3. Enter the exam year (e.g., `2026` for 2026 academic year)
4. The year field accepts: **2000 to (current year + 1)**

### Screenshot Guide

```
┌─────────────────────────────────────────────────────────────────┐
│ ACSEE Mark Entry                                                │
└─────────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────────┐
│ 1. Select Context                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Year*]  [Region]  [District]  [School]  [Subject]            │
│  ┌──────┐                                                       │
│  │ 2026 │  ← TYPE THE YEAR HERE                                │
│  └──────┘                                                       │
│   ^                                                              │
│   Defaults to current year (2026)                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## What Happens When You Set the Year

### Automatic Updates
When you change the **Year** field:

1. **Candidates are filtered** for that academic year
2. **Subjects are loaded** for candidates in that year
3. **Templates are generated** for that specific year
4. **Checksums are computed** based on that year's data

### Example

```
User selects:
- Year: 2026
- Region: Dar es Salaam
- District: Dar es Salaam City
- School: Lugalo Secondary School
- Subject: Physics

System response:
- Downloads template with 2026 ACSEE candidates from Lugalo
- Who study Physics
- Checksum includes year: 2026
- When you upload CSV, year must match: 2026
```

---

## Technical Details

### Code Location: `resources/views/mark-entry/index.blade.php`

**Line 19-30**: Year input field

```html
<!-- Exam Year -->
<div class="col-span-1">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Year *</label>
    <input 
        type="number" 
        x-model.number="examYear"
        min="2000"
        :max="currentYear + 1"
        @change="onContextChange()"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg ..."
    >
</div>
```

**Line 337**: Default value (current year)

```javascript
function markEntryManager() {
    return {
        currentYear: new Date().getFullYear(),  // Today's year
        examYear: new Date().getFullYear(),      // Defaults to today's year
        ...
    }
}
```

### Valid Years
- **Minimum**: 2000
- **Maximum**: Current year + 1
- **Default**: Current year (automatically set)

### Example Years
- 2024
- 2025
- 2026 ✅ (current)
- 2027 ✅ (next year, allowed for planning)

---

## Step-by-Step Workflow

### 1. Open Mark Entry Page
```
Navigate to: http://your-domain/mark-entry
```

### 2. Set Academic Year
```
Enter: 2026  (in the Year field)
```

### 3. Select Other Context
```
Choose:
- Region: Dar es Salaam
- District: Dar es Salaam City
- School: Lugalo Secondary School
- Subject: Physics
```

### 4. Download Template
```
Click: Download Template
- System queries 2026 ACSEE candidates
- From Lugalo Secondary
- Who take Physics
- Generates: LUGALO_SECONDARY_SCHOOL_PHY.csv
- Stores checksum (includes year: 2026)
```

### 5. Fill in Marks
```
Open CSV in Excel/Google Sheets
Add marks for each candidate
Save file
```

### 6. Upload Marks
```
Click: Upload Marks
- Selects same: Year (2026), School, Subject
- Uploads CSV
- System verifies checksum (year must match: 2026)
- Locks rows after processing
```

---

## Common Questions

### Q: Can I change the year between download and upload?
**A**: No. The checksum includes the year. If you download for 2026 but try to upload with 2025, it will be rejected.

```
Error: "Uploaded CSV does not match the generated template or has been modified"
Reason: Year mismatch (2026 vs 2025)
Solution: Download template again with correct year
```

### Q: What if I need to enter marks for multiple years?
**A**: Repeat the process for each year:
```
Year 2024:
  1. Set Year: 2024
  2. Download template
  3. Upload marks

Year 2025:
  1. Set Year: 2025
  2. Download template
  3. Upload marks

Year 2026:
  1. Set Year: 2026
  2. Download template
  3. Upload marks
```

### Q: Why does it default to current year?
**A**: To prevent accidental entry of wrong year. Most mark entries are for the current academic year.

### Q: Can I set future years?
**A**: Yes, up to **current year + 1**. This allows planning for next year's exams.

### Q: What if I try to set a year < 2000?
**A**: The field validates and rejects years before 2000.

---

## System Behavior by Year

### Current Year (e.g., 2026)
```
✅ Download template: YES
✅ Upload marks: YES
✅ Lock/unlock rows: YES
✅ View results: YES
```

### Next Year (e.g., 2027)
```
✅ Download template: YES (for planning)
✅ Upload marks: YES
✅ Lock/unlock rows: YES
⏳ View results: Only if marks entered
```

### Past Years (e.g., 2024, 2025)
```
✅ Download template: YES
✅ Upload marks: YES (if not already processed)
✅ Lock/unlock rows: YES
✅ View results: YES
```

---

## Database Impact

### Where Year is Stored

When you set Year and download template:
```sql
INSERT INTO mark_import_batches (
    exam_year,           ← 2026
    school_id,
    subject_id,
    status,
    ...
);

INSERT INTO mark_import_checksums (
    checksum,            ← includes year: 2026
    candidate_index_numbers,
    ...
);
```

When you upload marks:
```sql
INSERT INTO raw_marks (
    mark_import_batch_id,
    candidate_index_number,
    paper_1_marks,
    ...
);
-- Year is linked through mark_import_batch -> exam_year
```

---

## Important Notes

⚠️ **You must set the year BEFORE downloading template**

❌ Don't do this:
```
1. Leave Year blank
2. Click Download Template
3. Get error
```

✅ Do this:
```
1. Set Year: 2026
2. Click Download Template
3. Success
```

---

## Troubleshooting

### Problem: "Year field is empty"
**Solution**: It defaults to current year. Just start typing the year (e.g., 2026)

### Problem: "Year field accepts numbers only"
**Solution**: Clear field and type just the year number (e.g., 2026, not "Academic Year 2026")

### Problem: "Cannot go below 2000 or above current year + 1"
**Solution**: This is by design. Use valid years between 2000 and 2027 (if today is 2026)

### Problem: "Downloaded template for 2026, but uploaded for 2025"
**Solution**: 
1. Get the original 2026 template
2. Fill in marks
3. Upload for year 2026
(Checksum will reject mismatched years)

### Problem: "Candidates not showing up"
**Possible cause**: Wrong year selected
**Solution**: 
1. Check if ACSEE candidates exist for that year
2. Verify in database: `SELECT * FROM candidates WHERE year = 2026`
3. Ensure candidates have subject selections for that year

---

## Year Selection Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                 MARK ENTRY WORKFLOW                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. SET ACADEMIC YEAR                                       │
│     ↓                                                       │
│     [Year Input: 2026]  ← USER ENTERS HERE                 │
│                                                             │
│  2. SELECT CONTEXT (after year is set)                     │
│     ↓                                                       │
│     [Region] → [District] → [School] → [Subject]           │
│                                                             │
│  3. DOWNLOAD TEMPLATE                                       │
│     ↓                                                       │
│     CSV file with year: 2026 embedded in checksum          │
│     | index_number | sex | paper_p1 | paper_p2 |          │
│     | A12345       | M   |          |          |          │
│                                                             │
│  4. FILL IN MARKS IN EXCEL                                 │
│     ↓                                                       │
│     | index_number | sex | paper_p1 | paper_p2 |          │
│     | A12345       | M   |    75    |    82    |          │
│                                                             │
│  5. UPLOAD MARKS (same year: 2026)                         │
│     ↓                                                       │
│     CSV integrity check (year must match: 2026)            │
│     ↓                                                       │
│     Rows locked automatically                              │
│                                                             │
│  6. DONE                                                    │
│     ↓                                                       │
│     Marks are now locked and cannot be edited              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Summary

**Where to set academic year?**
→ In the **"Year" field** in the **"1. Select Context"** section of the Mark Entry page

**How to set it?**
→ Just type the year number (e.g., `2026`)

**When to set it?**
→ **BEFORE** downloading the template

**What happens?**
→ System uses the year to filter candidates and embed in checksum

**Can I change it later?**
→ Yes, but template checksum will mismatch. You'll need to re-download and re-enter marks

**What's the valid range?**
→ 2000 to (current year + 1), defaults to current year

---

**Document Version**: 1.0  
**Last Updated**: February 1, 2026  
**Status**: ✅ COMPLETE
