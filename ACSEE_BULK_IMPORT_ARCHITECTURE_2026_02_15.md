# ACSEE Bulk CSV Import - Architecture & Data Flow

**Date:** February 15, 2026

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                           │
│                  (Blade Template - acsee.blade.php)              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │         CANDIDATES TAB                                   │   │
│  │  ┌──────────────┐  ┌──────────────────┐  ┌──────────┐   │   │
│  │  │ Search Input │  │ Type Filter ↴    │  │ Export   │   │   │
│  │  └──────────────┘  └──────────────────┘  │ Bulk     │   │   │
│  │                                           │ Import   │   │   │
│  │                                           │ CSV ↓    │   │   │
│  │  ┌──────────────────────────────────┐    └──────────┘   │   │
│  │  │     CANDIDATES TABLE (READ-ONLY) │                   │   │
│  │  │  - Index Number                  │                   │   │
│  │  │  - Full Name                      │                   │   │
│  │  │  - Combination                    │                   │   │
│  │  │  - Allocated Subjects            │                   │   │
│  │  │  - Actions (Allocate)            │                   │   │
│  │  └──────────────────────────────────┘                   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │   ALLOCATION MODAL (SHARED)                             │   │
│  │                                                           │   │
│  │  ┌─────────────────────────────┐ ┌──────────────────┐   │   │
│  │  │ SINGLE ALLOCATION MODE      │ │ BULK IMPORT MODE │   │   │
│  │  │ (for single candidate)      │ │ (for CSV import) │   │   │
│  │  │ x-show="!bulkImportModal"   │ │ x-show="bulk... │   │   │
│  │  └─────────────────────────────┘ └──────────────────┘   │   │
│  │                                                           │   │
│  │  └──────────────────────────────────────────────────┐   │   │
│  │  │          BULK IMPORT SECTION                     │   │   │
│  │  │                                                   │   │   │
│  │  │  1. Template Download Section                   │   │   │
│  │  │     [Download School] [Download Private]        │   │   │
│  │  │                                                   │   │   │
│  │  │  2. Import Mode Selection                       │   │   │
│  │  │     ◉ School (Combination) ○ Private (Subjects) │   │   │
│  │  │                                                   │   │   │
│  │  │  3. CSV File Upload                             │   │   │
│  │  │     [Select CSV File] → filename (size)         │   │   │
│  │  │                                                   │   │   │
│  │  │  4. Exam Year Selection                         │   │   │
│  │  │     [Dropdown: Select Exam Year]                │   │   │
│  │  │                                                   │   │   │
│  │  │  5. Replace Allocations Checkbox                │   │   │
│  │  │     ☐ Replace existing allocations              │   │   │
│  │  │     ⚠ Warning message                           │   │   │
│  │  │                                                   │   │   │
│  │  │  6. Validation & Commit Section                 │   │   │
│  │  │     [Validate CSV] [Commit Import]              │   │   │
│  │  │     [Download Error Rows]                       │   │   │
│  │  │                                                   │   │   │
│  │  │  7. Reports Display                             │   │   │
│  │  │     - Validation Report                         │   │   │
│  │  │     - Commit Report                             │   │   │
│  │  │     - Error List                                │   │   │
│  │  │                                                   │   │   │
│  │  └──────────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                               ↓
                    Alpine.js Data Binding
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│                    ALPINE.JS COMPONENT                           │
│                  (acseeManager function)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─── STATE VARIABLES ──────────────────────────────────────┐   │
│  │                                                           │   │
│  │  Modal & Display:                                        │   │
│  │  • bulkImportModalOpen                                   │   │
│  │  • bulkPhase ('idle'|'validating'|'reviewing'|...)      │   │
│  │  • bulkProcessing                                        │   │
│  │                                                           │   │
│  │  User Input:                                             │   │
│  │  • bulkImportMode ('SCHOOL'|'PRIVATE')                  │   │
│  │  • bulkExamYearId                                        │   │
│  │  • bulkReplaceAllocations                               │   │
│  │  • bulkUploadedFile, bulkUploadedFileName, Size         │   │
│  │                                                           │   │
│  │  Server Responses:                                       │   │
│  │  • bulkValidationReport {total_rows, valid_count, ...}  │   │
│  │  • bulkCommitReport {success, skipped, failed, ...}     │   │
│  │  • bulkLastErrors [{row_number, index_number, msgs}]   │   │
│  │                                                           │   │
│  │  Messages:                                               │   │
│  │  • bulkErrorMessage                                      │   │
│  │  • bulkSuccessMessage                                    │   │
│  │                                                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─── FUNCTIONS ────────────────────────────────────────────┐   │
│  │                                                           │   │
│  │  openBulkImportModal()          ← User clicks button    │   │
│  │    ├─ Set bulkImportModalOpen = true                    │   │
│  │    └─ Load allocation contexts (exam years, etc)        │   │
│  │                                                           │   │
│  │  applyCandidateTypeFilter()     ← Filter dropdown       │   │
│  │    ├─ Filter candidates by type                         │   │
│  │    └─ Auto-set bulkImportMode                          │   │
│  │                                                           │   │
│  │  downloadTemplate(type)         ← Download buttons      │   │
│  │    ├─ Fetch CSV template from API                       │   │
│  │    └─ Trigger browser download                          │   │
│  │                                                           │   │
│  │  handleBulkFileUpload(event)    ← File input            │   │
│  │    ├─ Validate file format (.csv)                       │   │
│  │    ├─ Store file, name, size                            │   │
│  │    └─ Reset phase and reports                           │   │
│  │                                                           │   │
│  │  validateBulkCSV()              ← Validate button       │   │
│  │    ├─ Set phase = 'validating'                          │   │
│  │    ├─ POST FormData to /allocate-from-csv/validate      │   │
│  │    ├─ Set bulkValidationReport                          │   │
│  │    └─ Phase = 'reviewing' (success) or 'idle' (error)  │   │
│  │                                                           │   │
│  │  commitBulkCSV()                ← Commit button         │   │
│  │    ├─ Confirm with user                                 │   │
│  │    ├─ Set phase = 'committing'                          │   │
│  │    ├─ POST FormData to /allocate-from-csv/commit        │   │
│  │    ├─ Set bulkCommitReport                              │   │
│  │    ├─ Phase = 'complete', reload candidates            │   │
│  │    └─ Or Phase = 'reviewing' on error                   │   │
│  │                                                           │   │
│  │  downloadBulkErrorReport()      ← Error download        │   │
│  │    ├─ POST JSON {errors} to /download-errors            │   │
│  │    └─ Trigger CSV file download                         │   │
│  │                                                           │   │
│  │  resetBulkState()               ← On close/reopen       │   │
│  │    ├─ Clear all bulk variables                          │   │
│  │    ├─ Reset file input                                  │   │
│  │    └─ Return to idle state                              │   │
│  │                                                           │   │
│  │  closeBulkImportModal()         ← Close button          │   │
│  │    ├─ Set bulkImportModalOpen = false                   │   │
│  │    └─ Reset state                                       │   │
│  │                                                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                               ↓
                        API REQUESTS
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│                       BACKEND API                                │
│                   (Laravel Routes)                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─ Template Endpoints (GET) ─────────────────────────────────┐ │
│  │ GET /api/exam-types/acsee/templates/school-allocation.csv │ │
│  │ GET /api/exam-types/acsee/templates/private-allocation.csv│ │
│  │ Response: CSV blob                                         │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌─ Validation Endpoint (Phase 1) ────────────────────────────┐ │
│  │ POST /api/exam-types/acsee/allocate-from-csv/validate     │ │
│  │ Request: FormData(file, exam_year_id, mode, replace)      │ │
│  │ Response: {report: {...}, errors: [...]}                  │ │
│  │   report: {total_rows, valid_count, invalid_count, ...}   │ │
│  │   errors: [{row_num, index_num, error_messages}, ...]     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌─ Commit Endpoint (Phase 2) ────────────────────────────────┐ │
│  │ POST /api/exam-types/acsee/allocate-from-csv/commit       │ │
│  │ Request: FormData(file, exam_year_id, mode, replace)      │ │
│  │ Response: {report: {...}, errors: [...]}                  │ │
│  │   report: {success_count, failed_count, affected_cand...} │ │
│  │   errors: [{row_num, index_num, error_messages}, ...]     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌─ Error Download Endpoint ──────────────────────────────────┐ │
│  │ POST /api/exam-types/acsee/allocate-from-csv/download-errs│ │
│  │ Request: JSON {errors: [...]}                             │ │
│  │ Response: CSV blob with error details                     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  ┌─ Database Operations ──────────────────────────────────────┐ │
│  │ • Validate CSV data against database rules               │ │
│  │ • Create/update candidate allocations                    │ │
│  │ • Replace allocations (if flag set)                      │ │
│  │ • Return detailed operation results                      │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                               ↓
                          DATABASE
                               ↓
┌─────────────────────────────────────────────────────────────────┐
│                    DATA STORAGE                                  │
│                   (MySQL Tables)                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  • candidates table                                              │
│    - id, candidate_id, full_name, gender, school_id, etc       │
│                                                                   │
│  • candidate_subject_allocations table (updated)                │
│    - id, candidate_id, exam_year_id, subject_id, is_principal  │
│                                                                   │
│  • exam_years table                                              │
│    - id, year_label, academic_year, etc                        │
│                                                                   │
│  • subjects table                                                │
│    - id, code, name, description, etc                          │
│                                                                   │
│  • combinations table                                            │
│    - id, code, subject_codes, etc                              │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Sequence

### Complete Import Workflow

```
USER ACTION                          ALPINE.JS STATE              API CALL
─────────────────────────────────────────────────────────────────────────

1. Click "Bulk Import CSV"    →  bulkImportModalOpen = true
                                  Load exam years
                                                           →  (GET /api/exam-years)

2. Download template          →  Not state change
                                                           →  (GET /api/...template.csv)
                                                              Download file in browser

3. Select import mode         →  bulkImportMode = 'SCHOOL'
4. Upload CSV file            →  bulkUploadedFile = File object
                                  bulkUploadedFileName = 'data.csv'
                                  bulkUploadedFileSize = 12345

5. Select exam year           →  bulkExamYearId = '2026'

6. (Optional) Check replace   →  bulkReplaceAllocations = true

7. Click "Validate CSV"       →  bulkPhase = 'validating'
                                  bulkProcessing = true
                                                           →  POST /api/.../validate
                                                              {file, exam_year, mode}

                              ←  Response: {report, errors}
                                  bulkValidationReport = {...}
                                  bulkLastErrors = [...]
                                  bulkPhase = 'reviewing'
                                  bulkProcessing = false

8. Review validation report   →  Display metrics
                                  - Total rows
                                  - Valid rows
                                  - Invalid rows
                                  - Error details

9. (If errors) Download errs  →  
                                                           →  POST /api/.../download-errors
                                                              {errors: []}
                              ←  Response: CSV blob
                                  Download file in browser

10. Click "Commit Import"     →  Confirm dialog
                                  bulkPhase = 'committing'
                                  bulkProcessing = true
                                                           →  POST /api/.../commit
                                                              {file, exam_year, mode}

                              ←  Response: {report, errors}
                                  bulkCommitReport = {...}
                                  bulkLastErrors = [...]
                                  bulkPhase = 'complete'
                                  bulkProcessing = false
                                  
                                  DATABASE UPDATED
                                  WITH NEW ALLOCATIONS

11. View results              →  Display summary metrics
                                  - Imported count
                                  - Success count
                                  - Failed count
                                  - Affected candidates

12. Reload candidates list    →  Call loadAcseeCandicates()
                                  acseeCandicates updated
                                  UI refreshed with new data

13. Close modal               →  bulkImportModalOpen = false
                                  resetBulkState()
                                  All variables reset
```

---

## Phase Transitions Diagram

```
                          ┌─────────────┐
                          │   IDLE      │
                          │  (Initial)  │
                          └──────┬──────┘
                                 │
                    User selects file & validates
                                 │
                                 ↓
                    ┌────────────────────────┐
                    │    VALIDATING          │
                    │ (API call in progress) │
                    └────┬──────────────┬────┘
                         │              │
              Success     │              │    Error/Invalid
              (Valid)     │              │
                         ↓              ↓
            ┌──────────────────┐   ┌─────────┐
            │   REVIEWING      │   │  IDLE   │
            │ (Show report)    │   │ (Reset) │
            └────┬────────┬────┘   └─────────┘
                 │        │
            No Errors │  With Errors
                 │        │
                 │        ↓
                 │   ┌──────────────────┐
                 │   │ Can't commit     │
                 │   │ Download errors  │
                 │   └──────────────────┘
                 │
         User confirms
                 │
                 ↓
            ┌──────────────┐
            │  COMMITTING  │
            │ (Updating DB)│
            └────┬─────┬───┘
                 │     │
          Success│     │Error
                 │     │
                 ↓     ↓
            ┌────────┐ ┌──────────┐
            │COMPLETE│ │REVIEWING │
            │(Success)│ │(Show err)│
            └────────┘ └──────────┘
```

---

## State Machine

```
States:
├── idle        : Ready for user input
├── validating  : API call to validate CSV
├── reviewing   : Validation complete, show results
├── committing  : API call to commit import
└── complete    : Import finished successfully

Transitions:
idle ──validate──→ validating
           ├──success──→ reviewing
           └──error────→ idle

reviewing ──commit──→ committing
            ├──success──→ complete
            └──error────→ reviewing

complete ──close──→ idle (via reset)
```

---

## Error Handling Flow

```
USER ATTEMPTS ACTION
        │
        ↓
    INPUT VALIDATION
    (File, Exam Year)
        │
        ├─ Missing required input
        │  └─ Show error message
        │     Don't call API
        │
        └─ All inputs valid
           │
           ↓
        API CALL
        (validate or commit)
           │
           ├─ Network error
           │  └─ Show error message
           │     Reset to idle
           │
           ├─ Server returns error
           │  └─ Parse error response
           │     Store in bulkLastErrors
           │     Reset to idle/reviewing
           │
           └─ Server returns success
              ├─ Parse response data
              ├─ Update state (report, errors)
              └─ Show success message
```

---

## Component Interaction Diagram

```
┌─────────────────┐
│  FILE INPUT     │
│   (HTML)        │
└────────┬────────┘
         │
      @change event
         │
         ↓
┌────────────────────────────┐
│ handleBulkFileUpload()     │
│ • Validate file type       │
│ • Store file object        │
│ • Capture name & size      │
│ • Reset state              │
└────────┬───────────────────┘
         │
         ↓
    bulkUploadedFile
    bulkUploadedFileName
    bulkUploadedFileSize

─────────────────────────────

┌─────────────────┐
│ DROPDOWN        │
│ (Exam Year)     │
└────────┬────────┘
         │
      v-model binding
         │
         ↓
    bulkExamYearId

─────────────────────────────

┌──────────────────┐
│ VALIDATE BUTTON  │
└────────┬─────────┘
         │
      @click event
         │
         ↓
┌───────────────────────┐
│ validateBulkCSV()     │
│ • Validate inputs     │
│ • Build FormData      │
│ • API call            │
│ • Parse response      │
│ • Update state        │
└───────────┬───────────┘
            │
            ↓
    bulkValidationReport
    bulkLastErrors
    bulkPhase

─────────────────────────────

┌──────────────────┐
│ COMMIT BUTTON    │
└────────┬─────────┘
         │
      @click event
         │
         ↓
┌──────────────────────┐
│ commitBulkCSV()      │
│ • Confirm dialog     │
│ • Build FormData     │
│ • API call           │
│ • Parse response     │
│ • Update state       │
│ • Reload candidates  │
└──────────┬───────────┘
           │
           ↓
    bulkCommitReport
    bulkLastErrors
    bulkPhase = 'complete'
    acseeCandicates (reloaded)
```

---

## Request/Response Formats

### Validation Request
```
POST /api/exam-types/acsee/allocate-from-csv/validate

FormData:
- file: File object (binary)
- exam_year_id: "2026"
- mode: "SCHOOL" or "PRIVATE"
- replace_allocations: "true" or "false"

Headers:
- X-CSRF-TOKEN: <token>
```

### Validation Response (Success)
```json
{
  "report": {
    "total_rows": 100,
    "valid_count": 98,
    "invalid_count": 2,
    "data": {...}
  },
  "errors": [
    {
      "row_number": 5,
      "index_number": "S0001",
      "error_messages": ["Combination not found", ...]
    }
  ]
}
```

### Commit Request
```
POST /api/exam-types/acsee/allocate-from-csv/commit

Same FormData as validation
```

### Commit Response (Success)
```json
{
  "report": {
    "success_count": 98,
    "skipped_count": 0,
    "failed_count": 2,
    "affected_candidates": [
      {
        "id": 123,
        "index_number": "S0001",
        "full_name": "John Doe",
        "allocation_count": 5
      }
    ]
  },
  "errors": [...]
}
```

---

## Performance Considerations

```
Operation              Typical Time    Bottleneck
─────────────────────────────────────────────────
Download Template      <100ms          Network
Upload File            Variable        Network + File size
Validate CSV           100-5000ms      Server processing
Commit Import          50-100ms/row    Database + Network
Download Error Report  100-500ms       Server + Network

Total Time (happy path): 1-20 seconds depending on CSV size
```

---

**Architecture Design:** Complete, integrated, tested  
**Ready for:** Deployment and production use

