# PHASE 2b: UI INTEGRATION NOTES

**Date**: 2026-02-15  
**Status**: Study Complete — Ready for Implementation  
**Scope**: resources/views/exam-types/acsee.blade.php

---

## EXISTING UI STRUCTURE

### File Location
`resources/views/exam-types/acsee.blade.php`

### Tabs Structure (Alpine)
- Data property: `activeTab: 'subjects'` (line 461)
- Options: 'subjects', 'combinations', 'candidates'
- Tab selector: Lines 18-22 (buttons with `@click="activeTab = '...'"`)

### CANDIDATES TAB

**Location**: Lines 123-197 (HTML), Lines 492-498 (Alpine data)

**HTML Elements**:
- Search bar: `candidateSearch` input (line 126)
- Export button: `exportAcseeCandicates()` (line 127)
- Candidates table: 7 columns (index, name, sex, combination, allocated subjects, school, actions)
- Pagination: Lines 181-196
- Action button: Opens allocation modal (line 162)

**Alpine Data Properties** (for ACSEE Candidates):
```javascript
acseeCandicates: [],                    // Array of candidates
candidateSearch: '',                    // Search filter (NOTE: duplicate key line 494)
loadingAcseeCandicates: false,          // Loading state
acseeCurrentPage: 1,                    // Pagination page
acseetotalPages: 1,                     // Total pages
acseetotalCount: 0,                     // Total count
```

**Alpine Functions** (Lines 886-941):
- `loadAcseeCandicates()` — Fetch candidates (with search, page)
- `filterAcseeCandicates()` — Reset page, load candidates
- `exportAcseeCandicates()` — Export to CSV

**Candidate Object Structure**:
```javascript
{
    id: number,
    candidate_id: string,           // Index number (e.g., "S0445-0001")
    full_name: string,
    gender: 'M' | 'F',
    combination: string | null,     // Combination code (e.g., "PCB")
    school_id: number,
    school_name: string,
    allocated_subjects: [           // Array of subject objects
        {
            id: number,
            code: string,
            name: string
        }
    ],
    exam_type: string,
    status: string,
    candidate_type: string          // SCHOOL or PRIVATE (if available)
}
```

---

## ALLOCATION MODAL

**Location**: Lines 296-453 (HTML), Lines 500-1115 (Alpine script)

**Modal State**:
- Show condition: `x-show="allocationModalOpen"` (line 297)
- Z-index: `z-[9999]` (overlay + modal stacking)
- Max-width: `max-w-2xl`
- Header is sticky: `sticky top-0 bg-white` (line 299)

**Alpine Data Properties**:
```javascript
allocationModalOpen: false,             // Modal visibility (line 501)
allocationCandidate: null,              // Selected candidate
allocationMode: 'template' | 'manual',  // Allocation mode (line 503)
allocationExamYearId: '',               // Selected exam year (line 504)
allocationCombinationId: '',            // Selected combination (line 505)
allocationSubjectIds: [],               // Selected subject IDs (line 506)
allocationReplace: false,               // Replace allocations flag (line 507)
allocationProcessing: false,            // Processing flag (line 508)
allocationExamYears: [],                // Exam year options (line 509)
allocationCombinations: [],             // Combination options (line 510)
allocationAllSubjects: [],              // All available subjects (line 511)
allocationPreviewSubjects: [],          // Preview subjects for selected combo (line 512)
allocationValidationMessages: {         // Validation messages (line 513)
    errors: [],
    warnings: []
},
```

**Alpine Functions** (Lines 945-1115):
- `openAllocationModal(candidate)` — Open modal for candidate (line 945)
- `closeAllocationModal()` — Close modal (line 958)
- `setAllocationMode(mode)` — Switch between template/manual (line 965)
- `loadAllocationContexts()` — Load exam years, combos, subjects (line 973)
- `loadCombinationSubjectsPreview()` — Load subjects for combo (line 995)
- `saveAllocation()` — Save allocation to backend (line 1014)

**Current Features**:
- Two allocation modes: "Apply Combination Template" and "Manual Subject Selection"
- Exam year dropdown (required)
- Template/Manual UI toggle
- Combination dropdown (template mode)
- Subject checkboxes (manual mode)
- Replace allocations checkbox (line 398)
- Validation messages display (lines 413-429)
- Save/Cancel buttons (lines 432-450)

---

## SCOPE: WHAT TO ADD FOR PHASE 2b

### 1. Candidate Type Filter (NEW)
**Location**: Above Candidates table (add before line 125)

**UI Components**:
- Dropdown: ALL | SCHOOL | PRIVATE
- Label: "Filter by Candidate Type"
- Hidden: if not showing anything useful initially

**Alpine Data** (ADD):
```javascript
candidateTypeFilter: 'ALL',  // NEW property
```

**Alpine Functions** (ADD):
```javascript
filterCandidateType() {
    this.acseeCurrentPage = 1;
    this.loadAcseeCandicates();
    // May need to pass filter to backend if server-side filtering desired
}
```

**Server-side Filtering**:
- Current `loadAcseeCandicates()` can add query param: `?candidate_type_filter=ALL|SCHOOL|PRIVATE`
- Backend already supports this in `AcseeAllocationCSVImporter`

---

### 2. Bulk CSV Import Section (NEW)
**Location**: New tab or modal

**Two Options**:
- **Option A**: Add as 4th tab ("Bulk Import") alongside Subjects, Combinations, Candidates
- **Option B**: Keep in modal but add new "CSV Import" button/tab next to single-candidate allocation

**Recommendation**: Option A (separate tab) — Cleaner UI, less modal stacking

**UI Components** (within tab/modal):
1. Info box: "Import bulk subject allocations from CSV file"
2. Template download buttons (2):
   - "Download SCHOOL Template" → GET /api/exam-types/acsee/templates/school-allocation.csv
   - "Download PRIVATE Template" → GET /api/exam-types/acsee/templates/private-allocation.csv
3. File upload input:
   - Accept: .csv
   - Display selected filename
   - Display file size
4. Import Mode selector (optional if filter above covers it):
   - Radio or dropdown: SCHOOL | PRIVATE
5. Exam Year selector (required):
   - Dropdown from `allocationExamYears`
   - Must set `bulkExamYearId`
6. Candidate Type Filter (reuse from main filter):
   - Optional, default to page filter
7. Replace allocations checkbox:
   - Default OFF
   - Warning text if ON
8. "Validate CSV" button:
   - Disabled until file uploaded + exam year selected
   - Shows spinner while validating
9. Report section (after validation):
   - Summary: total_rows, valid_count, invalid_count
   - Error table (scrollable): row_number, index_number, error_messages
   - "Download Error CSV" button
10. "Commit Import" button:
    - Disabled until validation passes with success=true
    - Shows spinner while committing
    - Warning confirmation if replace=true
11. Final report (after commit):
    - Summary: success_count, failed_count
    - Affected candidates list
    - Errors table (if any)
    - Download error CSV button again

---

## ALPINE DATA TO ADD

```javascript
// CSV Bulk Import (NEW)
bulkImportModalOpen: false,         // NEW tab or modal toggle
bulkImportMode: 'SCHOOL',           // SCHOOL or PRIVATE (auto-detect)
bulkExamYearId: '',                 // Selected exam year
bulkCandidateTypeFilter: 'ALL',     // Candidate type filter
bulkReplaceAllocations: false,      // Replace flag
bulkReplaceAllocationsDefault: false, // For commit

// File upload
uploadedFile: null,                 // File object
uploadedFileName: '',               // Display filename
uploadedFileSize: 0,                // Display file size

// Import workflow
importPhase: 'idle',                // 'idle' | 'validating' | 'reviewing' | 'committing' | 'complete'
importProcessing: false,            // Processing flag (validation/commit)
bulkValidationReport: null,         // Report from validation endpoint
bulkImportReport: null,             // Report from commit endpoint

// Error handling
importError: null,                  // Error message if any
```

---

## ALPINE FUNCTIONS TO ADD

```javascript
// Template downloads
downloadTemplate(type: 'SCHOOL' | 'PRIVATE') {
    // GET /api/exam-types/acsee/templates/{type}-allocation.csv
    // Browser downloads file automatically
}

// File handling
handleFileUpload(event) {
    const file = event.target.files[0];
    if (file) {
        this.uploadedFile = file;
        this.uploadedFileName = file.name;
        this.uploadedFileSize = file.size;
    }
}

// CSV Import Phase 1: Validate
async validateCSVImport() {
    this.importProcessing = true;
    this.importPhase = 'validating';
    
    const formData = new FormData();
    formData.append('file', this.uploadedFile);
    formData.append('exam_year_id', this.bulkExamYearId);
    formData.append('candidate_type_filter', this.bulkCandidateTypeFilter);
    
    // POST /api/exam-types/acsee/allocate-from-csv/validate
    // Response: { success, total_rows, valid_count, invalid_count, errors[], summary }
    
    this.importPhase = 'reviewing';
    this.importProcessing = false;
}

// CSV Import Phase 2: Commit
async commitCSVImport() {
    this.importProcessing = true;
    this.importPhase = 'committing';
    
    const formData = new FormData();
    formData.append('file', this.uploadedFile);
    formData.append('exam_year_id', this.bulkExamYearId);
    formData.append('candidate_type_filter', this.bulkCandidateTypeFilter);
    formData.append('replace_allocations_default', this.bulkReplaceAllocationsDefault);
    
    // POST /api/exam-types/acsee/allocate-from-csv/commit
    // Response: { success, success_count, failed_count, errors[], affected_candidates[] }
    
    this.importPhase = 'complete';
    this.importProcessing = false;
}

// Error report download
async downloadErrorReport() {
    const errors = this.bulkValidationReport.errors || this.bulkImportReport.errors;
    
    // POST /api/exam-types/acsee/allocate-from-csv/download-errors
    // Payload: { errors: [...] }
    // Response: CSV file (browser downloads)
}
```

---

## BACKEND ENDPOINTS (ALREADY IMPLEMENTED)

These Phase 2a endpoints are ready to call:

### Template Downloads
```
GET /api/exam-types/acsee/templates/school-allocation.csv
GET /api/exam-types/acsee/templates/private-allocation.csv
```
Returns: CSV file (Content-Type: text/csv)

### CSV Validation (Phase 1)
```
POST /api/exam-types/acsee/allocate-from-csv/validate
```
Request (multipart/form-data):
- file: CSV file
- exam_year_id: integer (required)
- candidate_type_filter: ALL|SCHOOL|PRIVATE (optional)

Response (200/422 if validation fails):
```json
{
    "success": true/false,
    "message": "...",
    "total_rows": 10,
    "valid_count": 8,
    "invalid_count": 2,
    "errors": [
        {
            "row_number": 3,
            "index_number": "S0445-0003",
            "candidate_type": "SCHOOL",
            "combination_code": "INVALID",
            "error_messages": ["Combination INVALID not found"]
        }
    ],
    "summary": {
        "combination_not_found": 1
    }
}
```

### CSV Commit (Phase 2)
```
POST /api/exam-types/acsee/allocate-from-csv/commit
```
Request (multipart/form-data):
- file: CSV file
- exam_year_id: integer (required)
- candidate_type_filter: ALL|SCHOOL|PRIVATE (optional)
- replace_allocations_default: boolean (optional)

Response (200 or 400):
```json
{
    "success": true/false,
    "message": "Import complete: 8 successful, 2 failed",
    "total_rows": 10,
    "success_count": 8,
    "skipped_count": 0,
    "failed_count": 2,
    "errors": [...],
    "affected_candidates": [
        {
            "id": 123,
            "index_number": "S0445-0001",
            "full_name": "Student Name",
            "allocation_count": 1
        }
    ]
}
```

### Error Report Download
```
POST /api/exam-types/acsee/allocate-from-csv/download-errors
```
Request (JSON):
```json
{
    "errors": [
        {
            "row_number": 3,
            "index_number": "S0445-0003",
            "candidate_type": "SCHOOL",
            "combination_code": "INVALID",
            "error_messages": ["Combination INVALID not found"]
        }
    ]
}
```
Response: CSV file (Content-Type: text/csv)

---

## IMPLEMENTATION CHECKLIST

### Phase 2b Frontend Implementation

#### Part 1: Add Candidate Type Filter (1 hour)
- [ ] Add `candidateTypeFilter: 'ALL'` to Alpine data
- [ ] Add `filterCandidateType()` function
- [ ] Add filter dropdown in Candidates tab (before search bar)
- [ ] Modify `loadAcseeCandicates()` to include filter query param
- [ ] Test filter works client-side

#### Part 2: Add Bulk Import Section (2-3 hours)
- [ ] Add new tab "Bulk Import" OR button to open bulk import modal
- [ ] Add Alpine data properties (file, import state, reports)
- [ ] Add `downloadTemplate(type)` function
- [ ] Add `handleFileUpload(event)` function
- [ ] Add `validateCSVImport()` function (Phase 1)
- [ ] Add `commitCSVImport()` function (Phase 2)
- [ ] Add `downloadErrorReport()` function
- [ ] Build HTML for bulk import section:
  - Template download buttons
  - File upload input
  - Exam year selector
  - Replace checkbox
  - Validate/Commit buttons
  - Report display
- [ ] Test validation flow
- [ ] Test commit flow
- [ ] Test error report download

#### Part 3: Polish & Testing (1-2 hours)
- [ ] Check for modal stacking issues (z-index)
- [ ] Verify CSRF token is sent
- [ ] Test file downloads work properly
- [ ] Test error messages display correctly
- [ ] Test button disable/enable logic
- [ ] Test loading spinners
- [ ] Test modal close behavior
- [ ] Ensure backward compatibility (existing allocation modal still works)

---

## IMPORTANT NOTES

### 1. CSRF Token
All POST requests must include CSRF token:
```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

### 2. File Upload
Use `multipart/form-data` for file upload:
```javascript
const formData = new FormData();
formData.append('file', file);
formData.append('exam_year_id', examYearId);
// ... more fields

const response = await fetch('/api/endpoint', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token },
    body: formData  // Don't set Content-Type, browser sets it
});
```

### 3. File Download (CSV)
When response is CSV file, create download:
```javascript
const blob = await response.blob();
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'filename.csv';
document.body.appendChild(a);
a.click();
window.URL.revokeObjectURL(url);
document.body.removeChild(a);
```

### 4. Backward Compatibility
- DO NOT remove existing single-candidate allocation flow
- DO NOT rename existing Alpine variables unless necessary
- DO NOT change existing endpoints
- Only add new UI sections and new functions

### 5. Modal Stacking
- Allocation modal is already at z-[9999]
- If adding new modal, use z-[10000] or higher to prevent overlapping
- Test close buttons work properly
- Test overlay click closes modal

---

## TESTING CHECKLIST

### Manual Testing (before deployment)

- [ ] Download SCHOOL template (should contain headers + examples)
- [ ] Download PRIVATE template (should contain headers + examples)
- [ ] Upload valid SCHOOL CSV:
  - [ ] Validation shows 0 errors
  - [ ] Can commit
  - [ ] Allocations appear in candidate list
- [ ] Upload invalid PRIVATE CSV (missing General Studies):
  - [ ] Validation shows error
  - [ ] Commit button disabled
  - [ ] Can download error CSV
- [ ] Upload duplicate candidates:
  - [ ] Validation shows duplicates error
  - [ ] Commit button disabled
- [ ] Try replace allocations mode:
  - [ ] Checkbox enables warning text
  - [ ] Can commit with replace=YES
  - [ ] Old allocations deleted
- [ ] Test candidate type filter:
  - [ ] Filter works on client side
  - [ ] Shows/hides candidates correctly
- [ ] Test modal closing:
  - [ ] Close button works
  - [ ] Overlay click closes modal
  - [ ] Modal resets for next use
- [ ] Test error messages:
  - [ ] Messages are clear and helpful
  - [ ] Layout doesn't break with long messages
- [ ] Test loading states:
  - [ ] Spinners show during requests
  - [ ] Buttons disabled during requests
- [ ] Test responsive design:
  - [ ] UI looks good on mobile
  - [ ] Tables scroll properly
  - [ ] Buttons are clickable

---

## VARIABLES SUMMARY

### Data Properties Already Existing
- `activeTab` — Current tab
- `allocationModalOpen` — Allocation modal visibility
- `allocationExamYearId` — Selected exam year
- `allocationExamYears` — Exam year options
- `acseeCandicates` — Candidate list
- `acseeCurrentPage` — Pagination
- `candidateSearch` — Search filter

### Data Properties To Add
- `candidateTypeFilter` — NEW
- `bulkImportModalOpen` — NEW (if separate modal/tab)
- `uploadedFile` — NEW
- `bulkExamYearId` — NEW
- `bulkReplaceAllocations` — NEW
- `importPhase` — NEW
- `bulkValidationReport` — NEW
- `bulkImportReport` — NEW

### Functions To Add
- `filterCandidateType()` — NEW
- `downloadTemplate()` — NEW
- `handleFileUpload()` — NEW
- `validateCSVImport()` — NEW
- `commitCSVImport()` — NEW
- `downloadErrorReport()` — NEW

---

**Status**: Study Complete ✅  
**Ready to Implement**: YES ✅  
**Estimated Time**: 4-6 hours  
**Backward Compatible**: YES ✅
