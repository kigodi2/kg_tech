# Bulk Candidate Import - ACSEE Year Support

**Status:** ⚠️ NEEDS UPDATE  
**Issue:** Bulk candidate CSV import doesn't support exam_year field  
**Impact:** Candidates imported in bulk don't get registered for ACSEE with specific year

---

## Problem Statement

The bulk candidate import feature in `/registration` allows importing multiple candidates from a CSV file, but it doesn't include exam year support. This means:

1. ❌ Users can't specify exam year during bulk import
2. ❌ Candidates don't get CandidateExamRegistration created with exam_year_id
3. ❌ Imported candidates don't appear in Mark Entry (wrong year context)
4. ❌ Inconsistent with individual registration (which now has exam year)

---

## Current Flow (Broken)

```
User uploads CSV with candidates
  → CSV parsed without exam_year
  → Candidates created with exam_type but NO year context
  → No CandidateExamRegistration created
  ❌ Mark Entry can't find imported candidates
```

---

## Fixed Flow (Required)

```
User selects Exam Year: 2026
User uploads CSV with candidates
  → CSV parsed with exam_year context
  → Candidates created with exam_type
  ✅ CandidateExamRegistration created with exam_year_id
  ✅ CandidateSubjectSelection created for combinations
  ✅ Mark Entry finds imported candidates
```

---

## CSV Template Format

### Current (Without Year)
```
candidate_id,full_name,sex,combination,school_code,exam_type
IND001,John Doe,M,PCM,SCHOOL001,ACSEE
IND002,Jane Smith,F,PCB,SCHOOL001,ACSEE
```

### Required (With Year in Form)
```
User selects: Exam Year = 2026
Then uploads CSV with above format
Backend uses selected year context
```

---

## Required Changes

### Change 1: Frontend - Add Exam Year Selection Modal

**File:** `resources/views/registration/candidates.blade.php`

**Location:** Before file input (around line 140-150)

**Add a modal/form to collect exam_year before importing:**

```html
<!-- Import Modal -->
<div x-show="showImportModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">Import Candidates</h2>
        
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
            <select 
                x-model="importExamYear"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Select Exam Year</option>
                <template x-for="year in examYears" :key="year.id">
                    <option :value="year.year_label" x-text="year.year_label"></option>
                </template>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Type *</label>
            <select 
                x-model="importExamType"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Select Exam Type</option>
                <option value="PSLE">PSLE</option>
                <option value="CSEE">CSEE</option>
                <option value="ACSEE">ACSEE</option>
            </select>
        </div>

        <input 
            id="importInput" 
            type="file" 
            accept=".csv" 
            @change="importCSV($event)" 
            class="hidden"
        >

        <div class="flex gap-3">
            <button 
                @click="showImportModal = false"
                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg"
            >
                Cancel
            </button>
            <button 
                @click="document.getElementById('importInput').click(); showImportModal = false"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
            >
                Select File
            </button>
        </div>
    </div>
</div>
```

### Change 2: Frontend - Add State Properties

**File:** `resources/views/registration/candidates.blade.php`

**Location:** In candidatesManager() return object (around line 600)

**Add:**
```javascript
showImportModal: false,
importExamYear: '',
importExamType: '',
```

### Change 3: Frontend - Update Button to Show Modal

**File:** `resources/views/registration/candidates.blade.php`

**Location:** Around line 141

**Change from:**
```html
<button @click="document.getElementById('importInput').click()" ...>
```

**To:**
```html
<button @click="showImportModal = true" ...>
```

### Change 4: Frontend - Update importCSV Method

**File:** `resources/views/registration/candidates.blade.php`

**Location:** Around line 1089

**Update to pass exam_year:**

```javascript
async importCSV(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate exam_year is selected
    if (!this.importExamYear) {
        this.showMessage('Please select an exam year', 'error');
        return;
    }

    try {
        // First, check for conflicts
        const conflictFormData = new FormData();
        conflictFormData.append('file', file);
        conflictFormData.append('exam_year', this.importExamYear);
        conflictFormData.append('exam_type', this.importExamType);

        const conflictResponse = await fetch('/api/candidates/import/check', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: conflictFormData,
        });

        const conflictData = await conflictResponse.json();

        if (conflictData.conflicts && conflictData.conflicts.length > 0) {
            this.importConflicts = conflictData.conflicts;
            this.importFile = file;
            this.showImportConflictModal = true;
            return;
        }

        // No conflicts, proceed with import
        await this.performImport(file, 'skip');
    } catch (error) {
        console.error('Error checking conflicts:', error);
        this.showMessage('Error checking conflicts', 'error');
    }
    
    event.target.value = '';
}
```

### Change 5: Frontend - Update performImport Method

**File:** `resources/views/registration/candidates.blade.php`

**Location:** Around line 1127

**Update to pass exam_year:**

```javascript
async performImport(file, mode) {
    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('mode', mode);
        formData.append('exam_year', this.importExamYear);
        formData.append('exam_type', this.importExamType);

        const response = await fetch('/api/candidates/import', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage(
                `Candidates imported successfully (${data.count} records)${data.skipped ? `, ${data.skipped} skipped` : ''}${data.replaced ? `, ${data.replaced} replaced` : ''}`,
                'success'
            );
            this.showImportConflictModal = false;
            this.importExamYear = '';
            this.importExamType = '';
            await this.loadCandidates();
        } else {
            this.showMessage(data.message || 'Error importing', 'error');
        }
    } catch (error) {
        console.error('Error importing candidates:', error);
        this.showMessage('Error importing', 'error');
    }
}
```

### Change 6: Backend - Update Check Endpoint

**File:** `routes/web.php`

**Location:** Line 681

**Add validation for exam_year and exam_type:**

```php
Route::post('/api/candidates/import/check', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt',
        'exam_year' => 'nullable|integer|min:2000|max:' . (now()->year + 1),
        'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE'
    ]);
    
    // Rest of validation logic...
    // (file conflict checking, doesn't need exam_year context)
});
```

### Change 7: Backend - Update Import Endpoint

**File:** `routes/web.php`

**Location:** Line 728

**Add exam_year support and ACSEE registration:**

```php
Route::post('/api/candidates/import', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt',
        'exam_year' => 'nullable|integer|min:2000|max:' . (now()->year + 1),
        'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE'
    ]);
    
    // Get exam_year if provided
    $examYearValue = $request->input('exam_year');
    $examTypeOverride = $request->input('exam_type');
    
    // ... existing import logic ...
    
    // After creating candidate (around line 816), add ACSEE registration:
    if (strtoupper($examType) === 'ACSEE' && $examYearValue && !empty($combination)) {
        try {
            // Register for ACSEE using the same logic as individual registration
            $candidate = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
            if ($candidate) {
                app(App\Http\Controllers\CandidateController::class)->registerForACSEE(
                    $candidate, 
                    $combination, 
                    $examYearValue
                );
            }
        } catch (\Exception $e) {
            \Log::warning('ACSEE registration failed during import', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage()
            ]);
        }
    }
});
```

---

## CSV File Format

Users should prepare CSV with columns:
```
candidate_id,full_name,sex,combination,school_code,exam_type
IND001,John Doe,M,PCM,SCHOOL001,ACSEE
IND002,Jane Smith,F,PCB,SCHOOL001,ACSEE
IND003,Bob Wilson,M,,SCHOOL001,PSLE
```

**Note:** 
- `candidate_id` is optional (auto-generated if empty)
- `combination` is required only for ACSEE candidates
- User selects exam year and type in the modal form

---

## Impact

After this fix:

✅ Users can specify exam year when importing candidates  
✅ Imported ACSEE candidates registered with exam_year_id  
✅ CandidateExamRegistration created with proper year context  
✅ CandidateSubjectSelection created for combinations  
✅ Imported candidates appear in Mark Entry  
✅ Consistent with individual registration workflow  

---

## Testing Checklist

- [ ] Load /registration page
- [ ] Click "Import Candidates"
- [ ] Modal appears with Exam Year selector
- [ ] Select Exam Year: 2026
- [ ] Select Exam Type: ACSEE  
- [ ] Upload CSV with ACSEE candidates
- [ ] Verify candidates created with exam_year_id
- [ ] Go to /mark-entry/acsee
- [ ] Select 2026, see imported candidates
- [ ] Verify subjects display correctly

---

## Files to Modify

1. **resources/views/registration/candidates.blade.php** (6 changes)
   - Add showImportModal state
   - Add importExamYear state
   - Add importExamType state
   - Update button to show modal
   - Update importCSV to pass exam_year
   - Update performImport to pass exam_year
   - Add import modal HTML

2. **routes/web.php** (2 endpoints)
   - Update /api/candidates/import/check
   - Update /api/candidates/import

---

## Priority

**HIGH** - Bulk import is a critical feature that now conflicts with enhanced registration system.

---

## Summary

Bulk candidate import needs to be enhanced to support exam_year, consistent with the individual registration enhancement. Without this, bulk-imported candidates won't appear in Mark Entry and won't be properly registered for ACSEE with year context.

This is a **REQUIRED** fix to maintain consistency across all registration workflows.
