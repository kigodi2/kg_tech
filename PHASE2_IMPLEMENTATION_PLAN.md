# PHASE 2 IMPLEMENTATION PLAN
## ACSEE Private vs School CSV Import + Template Generator (NECTA-Aligned)

**Document**: Implementation Strategy & Deliverables  
**Status**: Ready for implementation  
**Date**: 2026-02-15

---

## STUDY FINDINGS

The existing system has:
- ✅ Two-phase candidate import (validate → commit)
- ✅ ACSEE allocation modal (single-step per candidate)
- ✅ `candidate_type` column (SCHOOL or PRIVATE)
- ✅ AcseeAllocationValidator service
- ✅ Combination pivot relationships
- ✅ CandidateSubjectSelection truth table (idempotent)
- ❌ NO bulk CSV allocation import
- ❌ NO candidate type filter in modal
- ❌ NO allocation template download
- ❌ NO PRIVATE-specific allocation flow

**Key Integration Points**:
- Reuse existing validator: `AcseeAllocationValidator`
- Reuse endpoint structure: `POST /api/exam-types/acsee/allocate-subjects` already handles single allocations
- Reuse modal: `acseeManager()` Alpine component (extend with new features)
- Match candidates: By `index_number` + `exam_year_id` (not ID-based)

---

## PHASE 2 OBJECTIVES (REFINED)

### 1. CSV Template Downloads (2 Types)
   - **SCHOOL**: Combination-driven (S index prefix)
   - **PRIVATE**: Subject-codes-driven (P index prefix)
   - Downloadable from modal per candidate type
   - Include headers + 1 example row + comment instructions

### 2. Modal UI Enhancements
   - **Candidate Type Filter**: Dropdown (ALL | SCHOOL | PRIVATE)
   - Filters candidate list (optional but recommended)
   - Controls template type displayed
   - Controls import validation rules

### 3. CSV Allocation Import (Bulk)
   - **Endpoint**: `POST /api/exam-types/acsee/allocate-from-csv`
   - **Validation**: Two-phase (validate → commit)
   - **Matching**: By `index_number` (CSV) + `exam_year_id` (UI)
   - **Source Detection**: Auto-detect SCHOOL vs PRIVATE from index prefix or CSV column
   - **Validation Rules**:
     - SCHOOL: combination must exist
     - PRIVATE: subject codes must be valid, GS mandatory, ≥3 principals
     - Candidate type must match mode
   - **Report**: Success/fail counts, detailed errors, downloadable error rows
   - **Modes**:
     - Add (default): Skip if already allocated
     - Replace (checkbox): Delete existing for exam_year, insert new

### 4. Backward Compatibility
   - Existing single-candidate allocation flow unchanged
   - Existing import flows unchanged
   - Only additive changes

---

## DELIVERABLES

### 1. CSV Templates (Downloadable)

#### A. SCHOOL Candidate Allocation Template
**Endpoint**: `GET /api/exam-types/acsee/templates/school-allocation.csv`

**Content**:
```csv
# ACSEE School Candidate Subject Allocation Template
# Instructions: Fill in the exam_year, index_number, combination_code, and replace_allocations columns
# - exam_year: 4-digit year (e.g., 2026)
# - index_number: e.g., S0445-0004
# - combination_code: e.g., PCB, HGL, PCM (must exist in system)
# - replace_allocations: YES or NO (default NO). If YES, existing allocations for this exam year will be deleted
# Combination subjects will be looked up automatically. Do not include a 'subjects' column.
exam_year,index_number,combination_code,replace_allocations
2026,S0445-0001,PCB,NO
2026,S0445-0002,HGL,NO
```

#### B. PRIVATE Candidate Allocation Template
**Endpoint**: `GET /api/exam-types/acsee/templates/private-allocation.csv`

**Content**:
```csv
# ACSEE Private Candidate Subject Allocation Template
# Instructions: Fill in the exam_year, index_number, subject_codes, and replace_allocations columns
# - exam_year: 4-digit year (e.g., 2026)
# - index_number: e.g., P0652-0502
# - subject_codes: Pipe-separated subject codes (e.g., 111|112|123|145)
#   - Must include 111 (General Studies)
#   - Must include at least 3 other subjects (principals)
# - replace_allocations: YES or NO (default NO)
exam_year,index_number,subject_codes,replace_allocations
2026,P0652-0501,111|112|123|145,NO
2026,P0652-0502,111|001|002|003,NO
```

### 2. Backend Endpoints

#### A. Template Downloads (Non-POST)
```
GET /api/exam-types/acsee/templates/school-allocation.csv
GET /api/exam-types/acsee/templates/private-allocation.csv
```

**Handler**: New service `AcseeAllocationTemplateService`  
**Returns**: CSV file with headers, example row, and instructions

#### B. CSV Allocation Import
```
POST /api/exam-types/acsee/allocate-from-csv
```

**Request Payload**:
```json
{
    "file": <File>,
    "exam_year_id": 45,
    "candidate_type_filter": "ALL|SCHOOL|PRIVATE",
    "replace_allocations_default": false
}
```

**Processing**:
1. Parse CSV (header + rows)
2. For each row:
   - Extract: exam_year, index_number, combination_code (SCHOOL) or subject_codes (PRIVATE)
   - Find candidate by index_number + exam_year_id
   - Validate candidate exists
   - Validate candidate_type matches expected (auto-detect from index prefix or column)
   - If SCHOOL:
     - Load combination by code
     - Load subjects from combination pivot
     - Validate (GS + 3 principals)
   - If PRIVATE:
     - Parse subject codes (pipe-separated)
     - Resolve codes to subject IDs
     - Validate (GS + 3 principals)
   - Check replace flag per row (or use default)
   - Save allocation via updateOrCreate() if validation passes

3. Return report:
   ```json
   {
       "success": true,
       "message": "Import complete",
       "total_rows": 10,
       "success_count": 8,
       "skipped_count": 0,
       "failed_count": 2,
       "errors": [
           {
               "row_number": 3,
               "index_number": "S0445-0003",
               "candidate_id": "abc123",
               "candidate_type": "SCHOOL",
               "combination_code": "INVALID",
               "error_messages": ["Combination INVALID not found"]
           }
       ],
       "affected_candidates": [
           {
               "id": 123,
               "index_number": "S0445-0001",
               "subject_count": 4,
               "allocated_count": 4
           }
       ]
   }
   ```

---

### 3. Modal UI Changes

#### A. Candidate Type Filter (Placement: Top of modal or within import section)
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

**Alpine Logic**:
- Update `acseeCandicates` list based on filter
- Auto-set import mode:
  - If SCHOOL selected → default to "Apply Combination Template"
  - If PRIVATE selected → default to "Manual Subject Selection"
- Update template download button:
  - If SCHOOL → download school template
  - If PRIVATE → download private template

#### B. CSV Allocation Import Section (New)
Add to allocation modal or create separate tab:

```html
<div x-show="activeTab === 'bulk-allocation'" class="space-y-6">
    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <h3 class="font-semibold text-blue-900 mb-2">Bulk CSV Import</h3>
        <p class="text-sm text-blue-800">
            Import subject allocations for multiple candidates at once.
        </p>
    </div>
    
    <!-- Template Download -->
    <div class="flex gap-2">
        <button @click="downloadTemplate('SCHOOL')" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">
            <i class="fas fa-download"></i> Download School Template
        </button>
        <button @click="downloadTemplate('PRIVATE')" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">
            <i class="fas fa-download"></i> Download Private Template
        </button>
    </div>
    
    <!-- File Upload -->
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
        <input type="file" @change="handleFileUpload($event)" accept=".csv" 
               class="w-full" x-ref="allocationFile">
        <p class="text-sm text-gray-600 mt-2">Select a CSV file to upload</p>
    </div>
    
    <!-- Exam Year Selection -->
    <div>
        <label class="block text-sm font-semibold mb-2">Exam Year</label>
        <select x-model="bulkExamYearId" class="w-full px-3 py-2 border rounded-lg">
            <option value="">Select Exam Year</option>
            <template x-for="year in allocationExamYears" :key="year.id">
                <option :value="year.id" x-text="year.year_label"></option>
            </template>
        </select>
    </div>
    
    <!-- Replace Checkbox -->
    <div class="flex items-center gap-2">
        <input type="checkbox" id="bulkReplace" x-model="bulkReplaceAllocations">
        <label for="bulkReplace" class="text-sm">
            Replace existing allocations for this exam year
        </label>
    </div>
    
    <!-- Import Button -->
    <button @click="bulkImportAllocations()" 
            :disabled="!uploadedFile || !bulkExamYearId || bulkProcessing"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
        <span x-show="!bulkProcessing">Import Allocations</span>
        <span x-show="bulkProcessing">
            <i class="fas fa-spinner animate-spin"></i> Processing...
        </span>
    </button>
    
    <!-- Import Report -->
    <div x-show="importReport" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h4 class="font-semibold mb-3">Import Report</h4>
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600" x-text="importReport.total_rows"></div>
                <div class="text-xs text-gray-600">Total Rows</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600" x-text="importReport.success_count"></div>
                <div class="text-xs text-gray-600">Successful</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600" x-text="importReport.skipped_count"></div>
                <div class="text-xs text-gray-600">Skipped</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-red-600" x-text="importReport.failed_count"></div>
                <div class="text-xs text-gray-600">Failed</div>
            </div>
        </div>
        
        <!-- Error Rows -->
        <div x-show="importReport.errors.length > 0">
            <h5 class="font-semibold text-sm mb-2">Errors (<span x-text="importReport.errors.length"></span>)</h5>
            <div class="max-h-48 overflow-y-auto">
                <template x-for="(error, idx) in importReport.errors.slice(0, 5)" :key="idx">
                    <div class="text-xs bg-white p-2 mb-1 border border-red-200 rounded">
                        <div class="font-semibold text-red-700" x-text="`Row ${error.row_number}: ${error.index_number}`"></div>
                        <div class="text-gray-700" x-html="error.error_messages.join('<br>')"></div>
                    </div>
                </template>
            </div>
            <p x-show="importReport.errors.length > 5" class="text-xs text-gray-600 mt-2">
                And <span x-text="importReport.errors.length - 5"></span> more errors...
            </p>
            <button @click="downloadErrorReport()" 
                    class="mt-3 px-3 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                <i class="fas fa-download"></i> Download Error Rows CSV
            </button>
        </div>
    </div>
</div>
```

---

### 4. Service Layer Changes

#### A. New: AcseeAllocationTemplateService
```php
namespace App\Services;

class AcseeAllocationTemplateService
{
    public function generateSchoolTemplate(): string {
        // Return CSV with headers + example + instructions
    }
    
    public function generatePrivateTemplate(): string {
        // Return CSV with headers + example + instructions
    }
}
```

#### B. New: AcseeAllocationCSVImporter
```php
namespace App\Services;

class AcseeAllocationCSVImporter
{
    public function validateCSV(file, examYearId, candidateTypeFilter): array {
        // Parse CSV, validate each row
        // Return: {success, total_rows, valid_count, invalid_count, errors[], summary}
    }
    
    public function commitImport(file, examYearId, candidateTypeFilter, replaceDefault): array {
        // Re-validate, then save allocations
        // Return: {success, total_rows, success_count, skipped_count, failed_count, errors[], affected_candidates[]}
    }
    
    private function matchCandidate(indexNumber, examYearId): ?Candidate {
        // Find candidate by index_number + exam_year_id
    }
    
    private function allocateSubjectsForSchool(Candidate, combinationCode): array {
        // Load combination, validate, allocate
    }
    
    private function allocateSubjectsForPrivate(Candidate, subjectCodes): array {
        // Parse codes, validate, allocate
    }
}
```

---

### 5. Route Changes

```php
// routes/web.php (or api.php)

// Template downloads
Route::get('/api/exam-types/acsee/templates/school-allocation.csv', [TemplateController::class, 'getSchoolTemplate']);
Route::get('/api/exam-types/acsee/templates/private-allocation.csv', [TemplateController::class, 'getPrivateTemplate']);

// CSV allocation import (two-phase)
Route::post('/api/exam-types/acsee/allocate-from-csv/validate', [AcseeAllocationController::class, 'validateCSVImport']);
Route::post('/api/exam-types/acsee/allocate-from-csv/commit', [AcseeAllocationController::class, 'commitCSVImport']);

// Or single endpoint with phase parameter
Route::post('/api/exam-types/acsee/allocate-from-csv', [AcseeAllocationController::class, 'importAllocations']);
```

---

## IMPLEMENTATION STEPS

### Phase 2a: Backend Setup (Days 1-2)

1. **Create Services**:
   - `AcseeAllocationTemplateService::generateSchoolTemplate()`
   - `AcseeAllocationTemplateService::generatePrivateTemplate()`
   - `AcseeAllocationCSVImporter::validateCSV()`
   - `AcseeAllocationCSVImporter::commitImport()`

2. **Create Controller**:
   - `AcseeAllocationController` with methods:
     - `getSchoolTemplate()` → downloads CSV
     - `getPrivateTemplate()` → downloads CSV
     - `validateCSVImport()` → Phase 1 validation
     - `commitCSVImport()` → Phase 2 commit

3. **Create Routes**:
   - `GET /api/exam-types/acsee/templates/school-allocation.csv`
   - `GET /api/exam-types/acsee/templates/private-allocation.csv`
   - `POST /api/exam-types/acsee/allocate-from-csv`

4. **Write Tests**:
   - Test school allocation with valid combination
   - Test private allocation with valid subject codes
   - Test private allocation fails without GS (111)
   - Test private allocation fails with <3 principals
   - Test candidate type mismatch detection
   - Test duplicate prevention
   - Test replace mode

### Phase 2b: Frontend UI (Days 2-3)

1. **Update Modal**:
   - Add candidate type filter dropdown
   - Add "Bulk Allocation" tab or section
   - Add file upload input
   - Add exam year selector
   - Add replace checkbox
   - Add import button

2. **Add Alpine Functions**:
   - `filterCandidateType()` — filter candidate list
   - `downloadTemplate(type)` → GET template CSV
   - `handleFileUpload(event)` → store file reference
   - `bulkImportAllocations()` → POST file to endpoint
   - `downloadErrorReport()` → download failed rows

3. **Add Report Display**:
   - Show success/fail counts
   - List first N errors on screen
   - Link to download full error CSV

### Phase 2c: Integration & Testing (Days 3-4)

1. **Manual Testing**:
   - Upload SCHOOL CSV, verify allocations
   - Upload PRIVATE CSV, verify allocations
   - Test replace mode
   - Test error reporting
   - Test idempotency (re-import same file)

2. **Automated Tests**:
   - Unit tests for validators
   - Feature tests for endpoints
   - Integration tests (CSV → DB)

3. **Documentation**:
   - User guide: how to use bulk import
   - CSV format specification
   - Troubleshooting guide

---

## VALIDATION RULES (CSV ALLOCATION IMPORT)

### Common Rules (Both Types)
- [ ] exam_year: 4-digit year, must be valid in system
- [ ] index_number: Must match existing candidate + exam_year
- [ ] Candidate must exist in database
- [ ] Candidate type must match import mode (SCHOOL vs PRIVATE)
- [ ] No duplicate index_number in same CSV file

### SCHOOL-Specific
- [ ] combination_code: Must exist in combinations table
- [ ] combination_code is case-insensitive
- [ ] Subjects from combination must satisfy ACSEE rules (GS + 3 principals)
- [ ] Candidate must have candidate_type='SCHOOL' (or index prefix 'S')

### PRIVATE-Specific
- [ ] subject_codes: Pipe-separated, valid subject codes
- [ ] Must include 111 (General Studies)
- [ ] Must include at least 3 other codes (principals)
- [ ] No duplicate codes within same row
- [ ] Candidate must have candidate_type='PRIVATE' (or index prefix 'P')

### Replace Mode
- [ ] If `replace_allocations=YES`:
   - Delete existing allocations for this candidate + exam_year
   - Requires explicit row-level or file-level flag
   - Warning displayed to user

---

## ERROR MESSAGES (User-Friendly)

| Scenario | Message |
|----------|---------|
| Candidate not found | "Candidate {index_number} not found for exam year {year}" |
| Candidate type mismatch | "Candidate {index_number} is SCHOOL type but row expects PRIVATE" |
| Combination not found | "Combination {code} not found in system" |
| Missing GS (111) | "Missing General Studies (code 111) — mandatory for ACSEE" |
| <3 principals | "Minimum 3 principal subjects required (found {n})" |
| Invalid subject code | "Subject code {code} not found in system" |
| Duplicate index_number | "Duplicate index_number {index} in CSV file" |
| Empty CSV | "CSV file is empty" |
| Invalid header | "Missing required column: {column}" |

---

## NON-DESTRUCTIVE SAFEGUARDS

1. **Phase 1 is read-only** → CSV validation only, no DB changes
2. **Phase 2 requires explicit commit** → user reviews Phase 1 report first
3. **Replace mode is opt-in**:
   - Default: add missing allocations only
   - Replace: requires explicit `YES` in CSV row
   - Warning dialog before commit
4. **Idempotent allocation** → `updateOrCreate()` prevents duplicates
5. **Error reporting** → detailed per-row errors, downloadable CSV
6. **Rollback on error** → if partial import fails, already-committed rows are preserved (transaction scope)

---

## SUCCESS CRITERIA

- [x] Study complete (this document)
- [ ] CSV templates downloadable (SCHOOL + PRIVATE)
- [ ] Candidate type filter in modal UI
- [ ] CSV allocation import endpoint (two-phase)
- [ ] Import report display (summary + first N errors)
- [ ] All tests passing
- [ ] Zero data loss (all safeguards in place)
- [ ] Backward compatible (existing flows unchanged)

---

## ESTIMATED EFFORT

| Component | Effort | Notes |
|-----------|--------|-------|
| Template Service | 2 hours | Straightforward CSV generation |
| CSV Importer Service | 4 hours | Main validation logic |
| Controller endpoints | 2 hours | Routes + handlers |
| Modal UI updates | 4 hours | Alpine functions + HTML |
| Tests | 6 hours | Unit + feature + integration |
| Integration testing | 3 hours | Manual + edge cases |
| **Total** | **~21 hours** | 3 days for 1 senior dev |

---

**Next Step**: Start Phase 2a (Backend Services)
