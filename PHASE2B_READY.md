# PHASE 2b: FRONTEND UI — READY TO START

**Date**: 2026-02-15  
**Previous**: Phase 2a (Backend Services) — COMPLETE ✅  
**Next**: Phase 2b (Frontend UI)  
**Estimated Effort**: 4-6 hours

---

## WHAT'S AVAILABLE FOR FRONTEND

### Backend Endpoints (Ready to Call)

#### 1. Template Downloads
```
GET /api/exam-types/acsee/templates/school-allocation.csv
GET /api/exam-types/acsee/templates/private-allocation.csv
```
**Returns**: CSV file download (with headers, examples, instructions)

#### 2. CSV Validation (Phase 1)
```
POST /api/exam-types/acsee/allocate-from-csv/validate
```
**Request**:
```json
{
    "file": <File>,
    "exam_year_id": 45,
    "candidate_type_filter": "ALL|SCHOOL|PRIVATE"
}
```
**Response**:
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
        "combination_not_found": 1,
        "...": 1
    }
}
```

#### 3. CSV Commit (Phase 2)
```
POST /api/exam-types/acsee/allocate-from-csv/commit
```
**Request**:
```json
{
    "file": <File>,
    "exam_year_id": 45,
    "candidate_type_filter": "ALL|SCHOOL|PRIVATE",
    "replace_allocations_default": false
}
```
**Response**:
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

#### 4. Error Report Download
```
POST /api/exam-types/acsee/allocate-from-csv/download-errors
```
**Request**:
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
**Returns**: CSV file download with failed rows

---

## WHAT'S NOT YET IMPLEMENTED (Phase 2b Tasks)

### 1. Candidate Type Filter
**Location**: Allocation modal (resources/views/exam-types/acsee.blade.php)

**UI Component**:
```html
<div class="flex items-center gap-4 mb-4">
    <label class="text-sm font-semibold">Filter by Candidate Type:</label>
    <select @change="filterCandidateType()" x-model="candidateTypeFilter" 
            class="px-3 py-2 border rounded-lg">
        <option value="ALL">All Candidates</option>
        <option value="SCHOOL">School Only</option>
        <option value="PRIVATE">Private Only</option>
    </select>
</div>
```

**Alpine Functions Needed**:
- `filterCandidateType()` — Re-filter candidate list
- Update `candidateTypeFilter` data property
- Auto-select import mode based on filter

### 2. Bulk Allocation Section
**Location**: New tab/section in allocation modal

**UI Components**:
```html
<!-- Tab selector (or section toggle) -->
<div class="flex gap-4 border-b border-gray-200 pb-4">
    <button @click="activeTab = 'single'" ...>Single Candidate</button>
    <button @click="activeTab = 'bulk'" ...>Bulk Upload</button>
</div>

<!-- Bulk allocation section (show when activeTab === 'bulk') -->
<div x-show="activeTab === 'bulk'" class="space-y-6">
    <!-- Template download buttons -->
    <div class="flex gap-2">
        <button @click="downloadTemplate('SCHOOL')" ...>
            <i class="fas fa-download"></i> Download School Template
        </button>
        <button @click="downloadTemplate('PRIVATE')" ...>
            <i class="fas fa-download"></i> Download Private Template
        </button>
    </div>

    <!-- File upload -->
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
        <input type="file" @change="handleFileUpload($event)" accept=".csv" ...>
        <p class="text-sm text-gray-600 mt-2">Select CSV file</p>
    </div>

    <!-- Exam year selector -->
    <div>
        <label class="block text-sm font-semibold mb-2">Exam Year *</label>
        <select x-model="bulkExamYearId" ...>
            <option value="">Select Exam Year</option>
            <template x-for="year in allocationExamYears">
                <option :value="year.id" x-text="year.year_label"></option>
            </template>
        </select>
    </div>

    <!-- Replace checkbox -->
    <div class="flex items-center gap-2">
        <input type="checkbox" id="bulkReplace" x-model="bulkReplaceAllocations">
        <label for="bulkReplace">Replace existing allocations</label>
    </div>

    <!-- Import button -->
    <button @click="bulkImportAllocations()" ...>
        <span x-show="!bulkProcessing">Import Allocations</span>
        <span x-show="bulkProcessing"><i class="fas fa-spinner animate-spin"></i> Processing...</span>
    </button>

    <!-- Import report (show after import) -->
    <div x-show="importReport" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h4 class="font-semibold mb-3">Import Report</h4>
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div><div class="text-2xl font-bold" x-text="importReport.total_rows"></div>Total</div>
            <div><div class="text-2xl font-bold text-green-600" x-text="importReport.success_count"></div>Successful</div>
            <div><div class="text-2xl font-bold text-yellow-600" x-text="importReport.skipped_count"></div>Skipped</div>
            <div><div class="text-2xl font-bold text-red-600" x-text="importReport.failed_count"></div>Failed</div>
        </div>

        <!-- Error list -->
        <div x-show="importReport.errors.length > 0">
            <h5 class="font-semibold text-sm mb-2">Errors</h5>
            <div class="max-h-48 overflow-y-auto">
                <template x-for="(error, idx) in importReport.errors.slice(0, 5)">
                    <div class="text-xs bg-white p-2 mb-1 border border-red-200 rounded">
                        <div class="font-semibold text-red-700" x-text="`Row ${error.row_number}: ${error.index_number}`"></div>
                        <div class="text-gray-700" x-html="error.error_messages.join('<br>')"></div>
                    </div>
                </template>
            </div>
            <button @click="downloadErrorReport()" ...>Download Error Rows CSV</button>
        </div>
    </div>
</div>
```

**Alpine Functions Needed**:
- `downloadTemplate(type: 'SCHOOL'|'PRIVATE')` — GET template CSV
- `handleFileUpload(event)` — Store file reference
- `bulkImportAllocations()` — POST file to validate endpoint
- `downloadErrorReport()` — POST errors array, get CSV
- Handle two-phase flow: validate → show report → commit
- Show validation errors before allowing commit
- Show final report after commit

### 3. Alpine Data Properties Needed
```javascript
// File upload
uploadedFile: null,

// Bulk import
activeTab: 'single', // or 'bulk'
bulkExamYearId: '',
bulkReplaceAllocations: false,
bulkProcessing: false,

// Import state tracking
importPhase: 'idle', // idle, validating, reviewing, committing, complete
importReport: null, // report object from backend

// Candidate type filter
candidateTypeFilter: 'ALL', // ALL, SCHOOL, PRIVATE
```

---

## IMPLEMENTATION PLAN FOR PHASE 2b

### Step 1: Update Modal HTML (2 hours)
1. Add candidate type filter at top
2. Add bulk allocation section/tab
3. Add file upload input
4. Add template download buttons (×2)
5. Add exam year selector
6. Add replace checkbox
7. Add import button
8. Add report display section

### Step 2: Add Alpine Functions (2-3 hours)
1. `filterCandidateType()` — Filter candidate list
2. `downloadTemplate(type)` — GET CSV template
3. `handleFileUpload(event)` — Store file reference
4. `bulkImportAllocations()` — POST file, handle two-phase
5. `downloadErrorReport()` — POST errors, download CSV
6. Report display logic (show/hide based on phase)
7. Error list formatting & display
8. Status message displays

### Step 3: Integration Testing (1-2 hours)
1. Test template download (SCHOOL + PRIVATE)
2. Test file upload
3. Test validation phase (error display)
4. Test commit phase
5. Test report display
6. Test error CSV download
7. Test modal close/reset

---

## TESTING CHECKLIST FOR PHASE 2b

- [ ] Template download works (SCHOOL)
- [ ] Template download works (PRIVATE)
- [ ] File upload input accepts CSV
- [ ] Validation phase displays errors correctly
- [ ] Can review errors before commit
- [ ] Commit button only enabled after validation passed
- [ ] Import report displays (summary + first N errors)
- [ ] Download error CSV button works
- [ ] Modal closes and resets properly
- [ ] Candidate type filter works
- [ ] Exam year selector required
- [ ] Replace checkbox toggles properly
- [ ] Large file handling (>1MB)
- [ ] Error messages are user-friendly
- [ ] Responsive on mobile

---

## INTEGRATION WITH BACKEND

All backend endpoints are ready:
- ✅ Template downloads
- ✅ CSV validation (Phase 1)
- ✅ CSV commit (Phase 2)
- ✅ Error report download

Frontend just needs to:
1. Call `/api/exam-types/acsee/templates/school-allocation.csv` → download template
2. POST to `/api/exam-types/acsee/allocate-from-csv/validate` → get validation report
3. Show validation errors to user
4. POST to `/api/exam-types/acsee/allocate-from-csv/commit` → commit after user approves
5. Show import report
6. POST to `/api/exam-types/acsee/allocate-from-csv/download-errors` → download error CSV

---

## FILES TO MODIFY

1. **resources/views/exam-types/acsee.blade.php**
   - Add candidate type filter (top of modal)
   - Add bulk allocation section (new tab or section)
   - Add Alpine functions in `<script>` section
   - Add data properties to `acseeManager()`

**That's it!** No other files need modification.

---

## REUSABLE CODE FROM EXISTING MODAL

You can copy/adapt these patterns from the existing allocation modal:
- File upload input
- Modal styling and structure
- Exam year dropdown (already exists)
- Report display styling
- Error message display

---

## NEXT PHASE (Phase 2c)

After Phase 2b is complete:
1. Full end-to-end testing
2. Performance testing (large files)
3. User acceptance testing
4. Documentation update
5. Deployment

---

## READY TO START? 

✅ Yes! All backend endpoints are ready.  
✅ Routes are registered.  
✅ Tests are passing.  
✅ You can start Phase 2b immediately.

**Estimated time**: 4-6 hours for Phase 2b  
**Then**: 3 hours for Phase 2c (testing & integration)

---

**Phase 2a Status**: ✅ COMPLETE  
**Phase 2b Status**: 🚀 READY TO START
