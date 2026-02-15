# Candidates Import Modal - Visual Guide

## Modal UI Flow

### Phase 1: Upload

```
╔════════════════════════════════════════════════════════════════╗
║  Import Candidates                                          ✕  ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  Step 1: Prepare File                                          ║
║                                [Download Template] ↓ CSV        ║
║                                                                ║
║  ┌────────────────────────────────────────────────────────┐  ║
║  │    📤 Drop CSV file here or click to select            │  ║
║  │       Accepts .csv and .txt files                       │  ║
║  └────────────────────────────────────────────────────────┘  ║
║                                                                ║
║  📄 Selected file: candidates.csv (15.2 KB)                   ║
║                                                                ║
║  Exam Type:  [ACSEE        ▼]    Exam Year:  [2026     ]      ║
║                                                                ║
╠════════════════════════════════════════════════════════════════╣
║  [Cancel]                                       [Validate ✓]   ║
╚════════════════════════════════════════════════════════════════╝
```

### Phase 2: Report

```
╔════════════════════════════════════════════════════════════════╗
║  Import Candidates                                          ✕  ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  Step 2: Review Results                                        ║
║                                                                ║
║  ┌──────────┐ ┌──────────┐ ┌─────────┐ ┌──────────────────┐  ║
║  │ TOTAL    │ │ VALID    │ │ ERRORS  │ │ CAN IMPORT?      │  ║
║  │  ROWS    │ │          │ │         │ │                  │  ║
║  │   100    │ │    98    │ │    2    │ │      Yes         │  ║
║  └──────────┘ └──────────┘ └─────────┘ └──────────────────┘  ║
║                                                                ║
║  Errors Found           [⬇ Download Errors]                   ║
║  ┌────────────────────────────────────────────────────────┐  ║
║  │ Row │ ID     │ Name      │ Error                       │  ║
║  ├────────────────────────────────────────────────────────┤  ║
║  │  5  │ S0001  │ John D    │ gender must be M or F  [!] │  ║
║  │ 12  │ S0002  │ Jane      │ school_code not found  [!] │  ║
║  └────────────────────────────────────────────────────────┘  ║
║                                                                ║
║  Showing 2 of 2 errors (download file to see details)         ║
║                                                                ║
╠════════════════════════════════════════════════════════════════╣
║  [⬅ Back]  [Cancel]  [Upload 98 Records ✓]                   ║
╚════════════════════════════════════════════════════════════════╝
```

### Phase 3: Processing

```
╔════════════════════════════════════════════════════════════════╗
║  Import Candidates                                          ✕  ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║                                                                ║
║                        ⟳ ⟳ ⟳                                   ║
║                     (loading spinner)                          ║
║                                                                ║
║                  Processing Import...                          ║
║              Importing candidates... (52%)                     ║
║                                                                ║
║                                                                ║
╠════════════════════════════════════════════════════════════════╣
║  [Processing...]                                              ║
╚════════════════════════════════════════════════════════════════╝
```

### Success Notification

```
┌──────────────────────────────────────────────────────────────┐
│ ✓ Import successful: 98 new candidate(s)                     │
│   (appears as green toast at top-right of page)              │
└──────────────────────────────────────────────────────────────┘
```

---

## CSV Format Example

### Minimal (Required Fields Only)
```csv
candidate_id,full_name,gender,school_code
S1378-0001,John Doe,M,S1378
S1378-0002,Jane Smith,F,S1378
S1378-0003,Bob Wilson,M,S1378
```

### Full (With ACSEE Registration)
```csv
candidate_id,full_name,gender,combination,school_code,exam_type,exam_year
S1378-0001,John Doe,M,Physics;Chemistry;Math,S1378,ACSEE,2026
S1378-0002,Jane Smith,F,English;History;Geography,S1378,ACSEE,2026
S1378-0003,Bob Wilson,M,PCM,S1378,ACSEE,2026
```

### Mixed (ACSEE + PSLE)
```csv
candidate_id,full_name,gender,combination,school_code,exam_type,exam_year
A001,Alice Primary,F,,S1378,PSLE,2026
A002,Bob Advanced,M,Physics;Chemistry;Biology,S1378,ACSEE,2026
```

---

## Error Report Download Example

When user clicks "Download Errors", they get CSV:

```csv
row_number,candidate_id,full_name,gender,school_code,combination,exam_type,error_messages
5,S0001,John Doe,X,,CBE,ACSEE,"gender must be M or F"
12,S0002,Jane Smith,F,INVALID,HGE,ACSEE,"school_code not found: INVALID"
23,S0003,,F,S1378,PCM,ACSEE,"full_name is required"
```

---

## Validation Decision Tree

```
                    User Uploads CSV
                          │
                          ▼
              ┌─────────────────────────┐
              │  Validate CSV           │
              │  (Phase 1: No Writing)  │
              └────────┬────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
    ✓ Valid                      ✗ Errors Found
    (All rows pass)              (Some rows fail)
        │                             │
        ▼                             ▼
    Report:                       Report:
    ├─ Total: 100                ├─ Total: 100
    ├─ Valid: 100  ✓             ├─ Valid: 95
    ├─ Errors: 0                 ├─ Errors: 5  ✗
    └─ Can Import: YES           └─ Can Import: YES*
                                 (*Can import valid rows)
        │                             │
        │                    ┌────────┴────────┐
        │                    │                 │
        │                    ▼                 ▼
        │              [Download]        [Fix & Re-upload]
        │              Errors CSV            OR
        │                    │            [Proceed with 95]
        │                    │                 │
        └────────┬───────────┘                 │
                 ▼                             │
        ┌──────────────────┐                  │
        │ [Import 100]     │◄─────────────────┘
        │ [Import 95]      │
        │ [Cancel]         │
        └────────┬─────────┘
                 │
         ┌───────┴───────┐
         ▼               ▼
    Click Import    Click Cancel
         │               │
         ▼               ▼
    Phase 2:        Back to Upload
    Commit DB       or Close Modal
         │
         ▼
    ✓ Success Toast
    Refresh List
```

---

## Field Mapping Guide

### In CSV File Header
```
candidate_id, full_name, gender, combination, school_code, exam_type, exam_year
```

### Mapped To In Database
```
Candidate Model:
  - candidate_id  → candidates.candidate_id
  - full_name     → candidates.full_name
  - gender        → candidates.gender
  - combination   → candidates.combination
  - school_code   → candidates.school_id (lookup)
  - exam_type     → candidates.exam_type

Related:
  - exam_type     → CandidateExamRegistration.exam_type_id (lookup)
  - combination   → CandidateSubjectSelection.subject_id (parse + lookup)
  - exam_year     → CandidateExamRegistration.exam_year_id (lookup)
```

---

## Key Features Visualization

### 1. Two-Phase Process
```
Phase 1                           Phase 2
VALIDATE                          COMMIT
(No DB writes)                    (Write to DB)

┌─────────────┐                ┌──────────────┐
│ Parse CSV   │                │ Re-validate  │
│ Check rules │────────────────│ Create rows  │
│ Report back │                │ Commit TX    │
└─────────────┘                └──────────────┘
     (5-10s)                       (10-30s)
```

### 2. Error Detection Layers
```
Layer 1: Parse
  - CSV syntax valid?
  - Expected columns present?

Layer 2: Required Fields
  - candidate_id?
  - full_name?
  - gender?
  - school_code?

Layer 3: Data Type
  - Gender = M or F?
  - Year = 4 digits?

Layer 4: Format
  - candidate_id <= 50 chars?
  - Combination = valid subjects?

Layer 5: Unique Constraints
  - Duplicate in file?
  - Duplicate in database?

Layer 6: Foreign Keys
  - School exists?
  - Subjects exist?
  - Exam year exists?

Result: Detailed error per row
  - Row number
  - Field + value
  - Specific error message
```

### 3. User Experience Flow
```
Entry Point
    │
    ├─ Via Tools Dropdown
    │  └─ [Tools ▼] → "Import CSV"
    │
    └─ Via Drag-drop
       └─ Drag file to upload area

User Journey:
   1. Download Template (optional)
   2. Upload CSV
   3. Validate (shows report)
   4. If errors: Fix & re-upload
   5. If valid: Commit import
   6. See success toast
   7. List refreshes
   8. Modal closes
```

---

## API Response Examples

### Validate Endpoint (Success)
```json
{
  "success": true,
  "message": "All rows valid",
  "total_rows": 100,
  "valid_count": 100,
  "invalid_count": 0,
  "can_import": true,
  "errors": [],
  "summary": {}
}
```

### Validate Endpoint (With Errors)
```json
{
  "success": false,
  "message": "2 row(s) have errors",
  "total_rows": 100,
  "valid_count": 98,
  "invalid_count": 2,
  "can_import": true,
  "errors": [
    {
      "row_number": 5,
      "candidate_id": "S0001",
      "full_name": "John Doe",
      "gender": "X",
      "school_code": "S1378",
      "combination": "CBE",
      "exam_type": "ACSEE",
      "error_messages": ["gender must be M or F"],
      "primary_error": "gender must be M or F"
    },
    {
      "row_number": 12,
      "candidate_id": "S0002",
      "full_name": "Jane Smith",
      "gender": "F",
      "school_code": "INVALID",
      "combination": "HGE",
      "exam_type": "ACSEE",
      "error_messages": ["school_code not found: INVALID"],
      "primary_error": "school_code not found: INVALID"
    }
  ],
  "total_errors": 2,
  "summary": {
    "gender_must_be_m_or_f": 1,
    "school_code_not_found": 1
  }
}
```

### Commit Endpoint (Success)
```json
{
  "success": true,
  "message": "Imported 98 candidates, skipped 2",
  "imported_count": 98,
  "skipped_count": 2,
  "updated_count": 0,
  "errors": []
}
```

---

## Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Open Import Modal | (Tools menu click) |
| Close Modal | Esc or ✕ button |
| Submit Form | Enter (when focused on input) |
| Focus File Input | Tab to "Drop area" + Space |
| Download Template | Alt+D (after modal opens) |

---

## Browser Compatibility

✅ Chrome/Edge (90+)
✅ Firefox (88+)
✅ Safari (14+)
✅ Mobile browsers (responsive)

*Requires JavaScript enabled*
*CSRF token support (included in all requests)*

---

## Accessibility Features

- Semantic HTML (labels, form elements)
- Keyboard navigation (Tab, Enter, Esc)
- Color contrast (WCAG AA standard)
- Aria labels on buttons
- Focus visible on interactive elements
- Error messages linked to fields

---

## Performance Checklist

✅ Validates without blocking (async)
✅ Streams large files (not loading entire file)
✅ Batch inserts (1000 rows at a time)
✅ Transaction support (atomic commits)
✅ Garbage collection during processing
✅ No N+1 queries
✅ Optimized indexes on lookups

Expected:
- Small files (< 1k rows): < 2 seconds total
- Medium files (1-10k): 5-15 seconds
- Large files (10k+): 20-60 seconds

