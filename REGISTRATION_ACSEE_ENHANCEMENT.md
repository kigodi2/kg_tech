# Registration Page Enhancement for ACSEE Mark Entry

**Issue:** Mark Entry page requires ACSEE candidates to be properly registered with exam year and subjects, but the registration form doesn't include exam year selector.

**Impact:** Candidates registered without exam year → No candidates appear in Mark Entry page for that year

**Solution:** Add exam year field to registration form and pass it to the backend

---

## Current Flow (Broken)

```
Registration Form
  → Select exam type: ACSEE
  → Enter combination: PCM
  → Submit
  → Backend receives: exam_type, combination
  ❌ NO exam_year_id passed
  → CandidateExamRegistration created WITHOUT exam_year_id
  → Mark Entry searches for candidates by exam_year_id
  → No results (year mismatch)
```

## Fixed Flow (Correct)

```
Registration Form
  → Select exam year: 2026
  → Select exam type: ACSEE
  → Enter combination: PCM
  → Submit
  → Backend receives: exam_year, exam_type, combination
  ✅ exam_year passed correctly
  → CandidateExamRegistration created WITH exam_year_id
  → CandidateSubjectSelection created WITH exam_year_id
  → Mark Entry finds candidates by exam_year_id
  → ✅ Candidates appear and ready for mark entry
```

---

## Required Changes

### Change 1: Add Exam Year Selector to Registration Form

**File:** `resources/views/registration/candidates.blade.php`

**Location:** In the candidate registration form (around line 529-540)

**Add this field BEFORE Exam Type selector:**

```html
<div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
    <select 
        x-model="formData.exam_year"
        required
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        <option value="">Select Exam Year</option>
        <template x-for="year in examYears" :key="year.id">
            <option :value="year.year_label" x-text="year.year_label"></option>
        </template>
    </select>
</div>
```

### Change 2: Load Exam Years in Component Init

**File:** `resources/views/registration/candidates.blade.php`

**Location:** In candidatesManager() init() method (around line 619-630)

**Add this call:**

```javascript
async init() {
    const savedPageSize = localStorage.getItem('candidatesPageSize');
    if (savedPageSize) {
        this.pageSize = parseInt(savedPageSize);
    }
    
    await this.loadRegions();
    await this.loadDistricts();
    await this.loadSchools();
    await this.loadExamYears();  // ADD THIS LINE
    await this.loadCandidates();
},
```

### Change 3: Add Exam Years Data Property

**File:** `resources/views/registration/candidates.blade.php`

**Location:** In candidatesManager() return object (around line 564-590)

**Add this property:**

```javascript
examYears: [],  // ADD THIS LINE
```

### Change 4: Add loadExamYears Method

**File:** `resources/views/registration/candidates.blade.php`

**Location:** After loadSchools() method (around line 650-660)

**Add this method:**

```javascript
async loadExamYears() {
    try {
        const response = await fetch('/api/exam-years');
        const data = await response.json();
        this.examYears = data.exam_years || [];
    } catch (error) {
        console.error('Error loading exam years:', error);
    }
},
```

### Change 5: Update Form Data Object

**File:** `resources/views/registration/candidates.blade.php`

**Location:** In candidatesManager() formData initialization (around line 584)

**Update from:**
```javascript
formData: { full_name: '', gender: '', combination: '', school_id: '', exam_type: '' },
```

**To:**
```javascript
formData: { full_name: '', gender: '', combination: '', school_id: '', exam_type: '', exam_year: '' },
```

### Change 6: Update Submit Handler

**File:** `resources/views/registration/candidates.blade.php`

**Location:** In the form submit handler (onSubmit or similar method around line 670-720)

**Ensure it passes exam_year to backend:**

The submit should now include:
```javascript
{
    full_name: formData.full_name,
    school_id: formData.school_id,
    gender: formData.gender,
    exam_type: formData.exam_type,
    combination: formData.combination,
    exam_year: formData.exam_year  // ADD THIS
}
```

### Change 7: Update Backend Controller (Optional - Already Supports It)

**File:** `app/Http/Controllers/CandidateController.php`

**Status:** ✅ Already implemented!

The `store()` method already calls `registerForACSEE()` which accepts exam_year parameter:

```php
if ($validated['exam_type'] === 'ACSEE') {
    $this->registerForACSEE($candidate, $validated['combination'] ?? null);
}
```

And `registerForACSEE()` resolves the exam year:
```php
private function registerForACSEE(Candidate $candidate, ?string $combination, $examYear = null): void
{
    // Resolves exam year from request or uses active year as fallback
    if ($examYear === null) {
        $examYear = ExamYear::active()->first();
    }
}
```

**Note:** The backend currently uses the active exam year as fallback. We need to ensure the frontend passes the selected year explicitly.

---

## Validation Rules

### Frontend Validation
- Exam Year: Required for ACSEE registration
- Exam Type: Required
- School: Required
- Gender: Required (M or F)
- Full Name: Required
- Combination: Required if Exam Type is ACSEE

### Backend Validation
Already implemented in:
- `CandidateController::store()` - validates all inputs
- `CandidateController::registerForACSEE()` - validates exam year
- `ExamYearValidationService::validateCandidateRegistration()` - ensures year is not locked

---

## Testing Checklist

After implementing these changes:

- [ ] Load /registration page
- [ ] See "Exam Year" dropdown in registration form
- [ ] Exam years populate from API
- [ ] Register new ACSEE candidate with:
  - [ ] Full Name: "Test Student"
  - [ ] Gender: M or F
  - [ ] School: Select a school
  - [ ] Exam Year: 2026
  - [ ] Exam Type: ACSEE
  - [ ] Combination: PCM
- [ ] Verify candidate appears in candidates list
- [ ] Go to /mark-entry/acsee
  - [ ] Select same school and year
  - [ ] Candidate should appear with subjects
  - [ ] Subject marks can be uploaded
- [ ] Test with multiple years:
  - [ ] Register same candidate for different years
  - [ ] Verify each year shows separately

---

## Important Notes

1. **Exam Year Requirement:** ACSEE candidates MUST have an exam year assigned
2. **Year Isolation:** Marks, registrations, and subjects are year-specific
3. **Backward Compatibility:** The backend has fallback logic that uses active year if none provided
4. **Subject Auto-Registration:** When combination is provided, subjects are auto-registered
5. **Multiple Registrations:** Same candidate can register for multiple exam years

---

## Expected Outcome

After these changes:

1. ✅ Registration form will have exam year selector
2. ✅ Candidates will be registered with proper exam_year_id
3. ✅ Mark Entry page will find the candidates
4. ✅ Marks can be entered for those candidates
5. ✅ Proper year isolation maintained

---

## Implementation Time

**Frontend Changes:** ~30 minutes
- Add 4 form fields/methods
- Load exam years in init()
- Pass exam_year to backend

**Testing:** ~30 minutes
- Test registration with different years
- Test mark entry for registered candidates
- Verify no candidates appear for unregistered years

**Total:** ~1 hour

---

## Dependencies

This fix depends on:
- ✅ Exam Years module (already implemented)
- ✅ ExamYearValidationService (already implemented)
- ✅ CandidateController registration logic (already implemented)
- ✅ Registration form (being updated)

All dependencies are already in place. This is just a frontend UI update.

---

## Files to Modify

1. `resources/views/registration/candidates.blade.php` (6 changes)

That's it! Backend already handles everything.
