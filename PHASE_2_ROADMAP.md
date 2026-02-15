# NECTA ACSEE Registration - Phase 2 Roadmap

**Predecessor**: Phase 1 (COMPLETE ✅)  
**Status**: READY TO START  
**Estimated Duration**: 2-3 days  
**Priority**: HIGH

---

## PHASE 2 SCOPE

### Primary Goal
Implement the subject allocation UI and backend API to allow users to allocate ACSEE subjects to candidates with NECTA-compliant validation.

### Deliverables
1. Allocation Modal UI (Alpine.js + HTML)
2. Allocation API Endpoint
3. Transactional subject creation
4. Allocation report generation
5. Integration tests

---

## TASK 1: Allocation Modal UI

### Location
`resources/views/exam-types/acsee.blade.php`

### Requirements
1. **Modal Structure** (After line ~189)
   - Title: "Allocate Subjects to [Candidate Name]"
   - Close button (X)
   - Mode selector buttons: "Apply Template" | "Manual Selection"

2. **Mode A: Apply Template** (for SCHOOL candidates)
   ```
   - Combination dropdown (load from combinations)
   - "Preview Subjects" button
   - Subject list display (codes + names)
   - Validation feedback (errors/warnings)
   - Action: "Apply Template" button
   ```

3. **Mode B: Manual Selection** (for PRIVATE or override)
   ```
   - Subject multi-select (load from subjects table)
   - General Studies auto-include (with force indicator)
   - is_principal toggle per subject
   - Validation feedback (errors/warnings)
   - Action: "Save Subjects" button
   ```

4. **Common Options**
   - Radio: "Add missing only" (default) | "Replace allocations"
   - If Replace selected: Warning checkbox "I understand this will remove existing allocations"

### Alpine.js Functions Needed
```javascript
// In acseeManager() component
openAllocationModal(candidate) {
  // Open modal, set candidate context
  // Load combinations and subjects
  // Initialize form
}

setAllocationMode(mode) {
  // Switch between 'template' and 'manual'
  // Load appropriate subject list
}

previewTemplate(combinationId) {
  // Load subjects from selected combination
  // Show preview list
}

loadSubjectsForAllocation() {
  // Fetch all subjects from API
  // Display in multi-select
}

validateAllocation() {
  // Run validation in frontend (optional)
  // Show errors/warnings
}

saveAllocation() {
  // POST to /api/exam-types/acsee/allocate-subjects
  // Handle response
  // Refresh candidates list
}

closeAllocationModal() {
  // Reset form
  // Close modal
}
```

### CSS Classes
- Use existing Tailwind classes from the view
- Modal background: `bg-black/50`
- Modal box: `bg-white rounded-lg shadow-2xl`
- Buttons: Standard green/blue scheme

---

## TASK 2: Allocation API Endpoint

### Endpoint
`POST /api/exam-types/acsee/allocate-subjects`

### Request Payload
```json
{
  "candidate_id": 123,
  "exam_year_id": 5,
  "subject_ids": [101, 102, 103, 111],
  "is_principal_map": {
    "101": true,
    "102": true,
    "103": true,
    "111": false
  },
  "replace_allocations": false,
  "source": "manual"
}
```

### Response (Success)
```json
{
  "ok": true,
  "message": "Subjects allocated successfully",
  "allocated_subjects": [
    {"id": 101, "code": "PHY", "name": "Physics", "is_principal": true},
    {"id": 102, "code": "CHM", "name": "Chemistry", "is_principal": true},
    {"id": 103, "code": "BIO", "name": "Biology", "is_principal": true},
    {"id": 111, "code": "GEN", "name": "General Studies", "is_principal": false}
  ],
  "created_count": 4,
  "skipped_count": 0
}
```

### Response (Validation Error)
```json
{
  "ok": false,
  "errors": [
    "General Studies (code 111) is mandatory for ACSEE candidates",
    "Minimum 3 principal subjects required (found 2)"
  ],
  "warnings": [],
  "allocated_subjects": []
}
```

### Controller Logic
```php
// In routes/web.php or dedicated controller

Route::post('/api/exam-types/acsee/allocate-subjects', function (Request $request) {
    // 1. Validate input
    $validated = $request->validate([
        'candidate_id' => 'required|exists:candidates,id',
        'exam_year_id' => 'required|exists:exam_years,id',
        'subject_ids' => 'required|array|min:1',
        'subject_ids.*' => 'exists:subjects,id',
        'is_principal_map' => 'required|array',
        'replace_allocations' => 'boolean',
        'source' => 'in:manual,template',
    ]);

    // 2. Load candidate
    $candidate = Candidate::findOrFail($validated['candidate_id']);

    // 3. Run validator
    $validator = new AcseeAllocationValidator();
    $validation = $validator->validate(
        $candidate,
        $candidate->examRegistrations()->first()->exam_type_id,
        $validated['exam_year_id'],
        $validated['subject_ids']
    );

    if (!$validation['ok']) {
        return response()->json([
            'ok' => false,
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
            'allocated_subjects' => [],
        ], 422);
    }

    // 4. Handle allocation (transactional)
    try {
        DB::transaction(function () use ($candidate, $validated, $validation) {
            if ($validated['replace_allocations'] ?? false) {
                // Delete existing allocations
                $candidate->subjectSelections()
                    ->where('exam_year_id', $validated['exam_year_id'])
                    ->delete();
            }

            // Create new allocations
            foreach ($validation['all_subject_ids'] as $subjectId) {
                $isPrincipal = in_array($subjectId, $validation['principal_subject_ids']);
                
                CandidateSubjectSelection::updateOrCreate(
                    [
                        'candidate_id' => $candidate->id,
                        'exam_type_id' => $candidate->examRegistrations()->first()->exam_type_id,
                        'exam_year_id' => $validated['exam_year_id'],
                        'subject_id' => $subjectId,
                    ],
                    [
                        'is_principal' => $isPrincipal,
                        'source' => $validated['source'],
                        'created_by' => auth()->id(),
                        'is_active' => true,
                    ]
                );
            }
        });

        // 5. Return success
        $allocated = $candidate->subjectSelections()
            ->with('subject')
            ->where('exam_year_id', $validated['exam_year_id'])
            ->get();

        return response()->json([
            'ok' => true,
            'message' => 'Subjects allocated successfully',
            'allocated_subjects' => $allocated->map(fn($s) => [
                'id' => $s->subject_id,
                'code' => $s->subject->code,
                'name' => $s->subject->name,
                'is_principal' => $s->is_principal,
            ]),
            'created_count' => count($validation['all_subject_ids']),
            'skipped_count' => 0,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'ok' => false,
            'errors' => ['Database error: ' . $e->getMessage()],
            'warnings' => [],
            'allocated_subjects' => [],
        ], 500);
    }
});
```

### Error Handling
- Candidate not found: 404
- Invalid subject IDs: 422
- Validation fails: 422
- Database error: 500

---

## TASK 3: JavaScript Integration

### In `acseeManager()` Alpine component, add:

```javascript
// Data for allocation modal
allocationModalOpen: false,
allocationCandidate: null,
allocationMode: 'template', // or 'manual'
allocationSubjectIds: [],
allocationReplace: false,
allocationProcessing: false,

// Combination and Subject lists (populate from API)
combinationsForAllocation: [],
subjectsForAllocation: [],

// Methods
async openAllocationModal(candidate) {
    this.allocationCandidate = candidate;
    this.allocationModalOpen = true;
    this.allocationMode = 'template';
    this.allocationSubjectIds = [];
    this.allocationReplace = false;
    
    // Load combinations and subjects
    await this.loadCombinationsForAllocation();
    await this.loadSubjectsForAllocation();
},

closeAllocationModal() {
    this.allocationModalOpen = false;
    this.allocationCandidate = null;
    this.allocationSubjectIds = [];
    this.allocationMode = 'template';
    this.allocationReplace = false;
},

setAllocationMode(mode) {
    this.allocationMode = mode;
    this.allocationSubjectIds = [];
},

async loadCombinationsForAllocation() {
    try {
        const response = await fetch('/api/exam-types/ACSEE/combinations');
        const data = await response.json();
        this.combinationsForAllocation = data.data || [];
    } catch (error) {
        console.error('Error loading combinations:', error);
    }
},

async loadSubjectsForAllocation() {
    try {
        const response = await fetch('/api/exam-types/ACSEE/subjects');
        const data = await response.json();
        this.subjectsForAllocation = data.data || [];
    } catch (error) {
        console.error('Error loading subjects:', error);
    }
},

async saveAllocation() {
    if (!this.allocationCandidate) {
        this.showMessage('No candidate selected', 'error');
        return;
    }

    if (this.allocationSubjectIds.length === 0) {
        this.showMessage('Please select at least one subject', 'error');
        return;
    }

    this.allocationProcessing = true;
    try {
        const response = await fetch('/api/exam-types/acsee/allocate-subjects', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                candidate_id: this.allocationCandidate.id,
                exam_year_id: this.getCurrentExamYear(), // Define this based on your context
                subject_ids: this.allocationSubjectIds,
                replace_allocations: this.allocationReplace,
                source: 'manual',
            }),
        });

        const data = await response.json();
        if (response.ok && data.ok) {
            this.showMessage('Subjects allocated successfully', 'success');
            this.closeAllocationModal();
            await this.loadAcseeCandicates(); // Refresh list
        } else {
            const errorMsg = (data.errors || []).join('; ');
            this.showMessage('Allocation failed: ' + errorMsg, 'error');
        }
    } catch (error) {
        console.error('Error allocating subjects:', error);
        this.showMessage('Error allocating subjects', 'error');
    } finally {
        this.allocationProcessing = false;
    }
},

showMessage(message, type) {
    // Use existing message system (already implemented)
    // type: 'success', 'error', 'warning'
}
```

---

## TASK 4: Integration Tests

### File Location
`tests/Feature/AcseeSubjectAllocationTest.php`

### Test Cases
```php
<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
// ... other imports

class AcseeSubjectAllocationTest extends TestCase
{
    use RefreshDatabase;

    // Setup similar to AcseeRegistrationTest

    public function test_can_allocate_subjects_manually() { ... }
    public function test_can_apply_combination_template() { ... }
    public function test_allocation_validates_with_validator() { ... }
    public function test_replace_allocations_removes_old_subjects() { ... }
    public function test_add_missing_only_skips_duplicates() { ... }
    public function test_allocation_tracks_source_and_user() { ... }
    public function test_allocation_fails_without_general_studies() { ... }
    public function test_allocation_fails_with_insufficient_principals() { ... }
}
```

---

## IMPLEMENTATION ORDER

1. **Day 1 (Morning)**:
   - Build Allocation Modal HTML in `acsee.blade.php`
   - Add Alpine.js functions for UI logic
   - Test modal opening/closing

2. **Day 1 (Afternoon)**:
   - Implement API endpoint `/api/exam-types/acsee/allocate-subjects`
   - Add validator integration
   - Test endpoint with curl/Postman

3. **Day 2 (Morning)**:
   - Wire up modal form submission
   - Handle API responses
   - Show validation errors in UI

4. **Day 2 (Afternoon)**:
   - Write integration tests
   - Test complete workflow end-to-end
   - Verify allocation persistence in database

5. **Day 3 (Optional)**:
   - Enhancement: CSV allocation import
   - Enhancement: Allocation history/audit trail UI
   - Performance optimization if needed

---

## TESTING CHECKLIST

### Manual Testing
- [ ] Open ACSEE Candidates tab
- [ ] Click "Allocate Subjects" on a SCHOOL candidate
- [ ] Modal opens with candidate info
- [ ] Can select combination template
- [ ] Can switch to manual mode
- [ ] Can multi-select subjects
- [ ] General Studies is highlighted/auto-included
- [ ] Validation errors show for invalid selections
- [ ] "Replace allocations" option works
- [ ] Can save allocation
- [ ] Subjects appear in candidate row after save
- [ ] For PRIVATE candidate, test manual-only workflow

### API Testing
```bash
curl -X POST http://localhost:8000/api/exam-types/acsee/allocate-subjects \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: TOKEN" \
  -d '{
    "candidate_id": 1,
    "exam_year_id": 1,
    "subject_ids": [1, 2, 3, 4],
    "replace_allocations": false,
    "source": "manual"
  }'
```

### Automated Tests
```bash
php artisan test tests/Feature/AcseeSubjectAllocationTest.php
```

---

## SUCCESS CRITERIA

✅ Modal opens on button click  
✅ Subject allocation works for both SCHOOL and PRIVATE  
✅ Validator prevents invalid allocations  
✅ Database shows correct is_principal and source values  
✅ Audit trail (created_by) populated  
✅ Existing allocations can be replaced or preserved  
✅ Tests pass 100%  
✅ No data loss or corruption  
✅ All error cases handled gracefully  

---

## ROLLBACK PLAN

If Phase 2 has critical issues:
1. Revert code changes (git checkout)
2. API endpoint will be removed
3. Modal will not appear
4. System reverts to Phase 1 state (functional but without allocation UI)
5. All Phase 1 guarantees remain intact

---

## REFERENCES

- Validator: `app/Services/AcseeAllocationValidator.php`
- Phase 1 Docs: `docs/necta_acsee_registration_alignment.md`
- Models: `app/Models/Candidate.php`, `CandidateSubjectSelection.php`
- Existing Modal Examples: Look at Subject/Combination modals in `acsee.blade.php`

---

## CONTACT

For Phase 2 questions:
- Architecture: See `docs/necta_acsee_registration_alignment.md`
- Validator: See `app/Services/AcseeAllocationValidator.php`
- Models: See updated model files with new columns

**Ready to start Phase 2!** ✅
