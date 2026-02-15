# NECTA ACSEE Registration Restructure - Implementation Checklist

**Start Date:** 2026-02-15  
**Status:** READY TO IMPLEMENT  
**Scope:** Non-destructive restructure to support SCHOOL and PRIVATE candidates

---

## PHASE 1: DATABASE MIGRATIONS & MODELS

### Database Changes
- [ ] Run migration: `2026_02_15_add_necta_alignment_columns.php`
  - Adds `candidate_type` to candidates
  - Adds `combination_id` FK to candidates
  - Adds `is_principal`, `source`, `created_by` to candidate_subject_selections
  - Adds performance indexes

**Verification:**
```bash
php artisan migrate
php artisan tinker
>>> DB::table('candidates')->first(); // Check new columns
>>> DB::table('candidate_subject_selections')->first(); // Check new columns
```

### Model Updates

#### Update `app/Models/Candidate.php`
- [ ] Add new fields to `$fillable`
  ```php
  'candidate_type', 'combination_id'
  ```

- [ ] Add new relationship: `combinationRelation()`
  ```php
  public function combinationRelation()
  {
      return $this->belongsTo(Combination::class, 'combination_id');
  }
  ```

- [ ] Add helper methods
  ```php
  public function isSchool(): bool { return $this->candidate_type === 'SCHOOL'; }
  public function isPrivate(): bool { return $this->candidate_type === 'PRIVATE'; }
  ```

- [ ] Update `$casts` to include `candidate_type` enum
  ```php
  'candidate_type' => 'string', // or use enum casting if Laravel 11+
  ```

#### Update `app/Models/CandidateSubjectSelection.php`
- [ ] Add new fields to `$fillable`
  ```php
  'is_principal', 'source', 'created_by'
  ```

- [ ] Add new relationship: `creator()`
  ```php
  public function creator()
  {
      return $this->belongsTo(User::class, 'created_by');
  }
  ```

- [ ] Add scopes
  ```php
  public function scopePrincipal($query)
  {
      return $query->where('is_principal', true);
  }
  
  public function scopeBySource($query, $source)
  {
      return $query->where('source', $source);
  }
  ```

- [ ] Update `$casts`
  ```php
  'is_principal' => 'boolean',
  'source' => 'string', // or enum
  ```

**Verification:**
```bash
php artisan tinker
>>> $c = Candidate::first();
>>> $c->isSchool();  // Should return true/false
>>> $c->combinationRelation; // Should work
>>> $selections = $c->subjectSelections()->principal()->get(); // Should filter
```

---

## PHASE 2: SERVICE LAYER

### Create AcseeRegistrationValidator
- [ ] File created: `app/Services/AcseeRegistrationValidator.php`
- [ ] Implements all validation rules:
  - [x] Minimum 3 principal subjects
  - [x] General Studies mandatory
  - [x] No duplicate subjects
  - [x] Maximum 8 subjects
  - [x] Subject conflict prevention (framework ready)

- [ ] Create ValidationResult class
  - [x] Data class with structure
  - [x] toArray() method
  - [x] toJson() method

**Verification:**
```bash
php artisan tinker
>>> $validator = new \App\Services\AcseeRegistrationValidator();
>>> $candidate = Candidate::where('exam_type', 'ACSEE')->first();
>>> $result = $validator->validate($candidate);
>>> dd($result);
```

### Create CandidateAllocationService (TODO)
- [ ] File: `app/Services/CandidateAllocationService.php`
- [ ] Methods:
  - [ ] `allocateSubjectsFromCombination($candidate, $combination)`
  - [ ] `allocateSubjectsManually($candidate, $subjectIds, $principalIds, $userId)`
  - [ ] `updateAllocation($candidate, $subjectIds, $principalIds, $userId)`
  - [ ] `removeSubjectAllocation($candidateSubjectSelection)`
  - [ ] `validateAllocation($candidate): ValidationResult`

### Create AcseeImportProcessor (TODO)
- [ ] File: `app/Services/AcseeImportProcessor.php`
- [ ] Methods:
  - [ ] `processImport($file, $mode, $examYear, $examType)`
  - [ ] `validateImportRow($row, $examType)`
  - [ ] `generateImportReport()`

---

## PHASE 3: CONTROLLER UPDATES

### Update `CandidateController::store()`
- [ ] Add `candidate_type` validation
  ```php
  'candidate_type' => 'required|in:SCHOOL,PRIVATE',
  ```

- [ ] Make `school_id` conditional
  ```php
  'school_id' => $validated['candidate_type'] === 'SCHOOL' ? 'required|exists:schools,id' : 'nullable',
  ```

- [ ] Update candidate creation
  ```php
  $candidate = Candidate::create([
      ...
      'candidate_type' => $validated['candidate_type'],
      'combination_id' => $combinationId ?? null,
      ...
  ]);
  ```

- [ ] Handle SCHOOL vs PRIVATE flows
  ```php
  if ($candidate->isSchool() && $combination) {
      // Auto-attach subjects from combination
      $this->attachCombinationSubjects($candidate, $combination);
  } elseif ($candidate->isPrivate()) {
      // Redirect to subject allocation
      return response()->json([
          'success' => true,
          'redirect' => "/exam-types/acsee?allocate={$candidate->id}",
          'message' => 'Candidate registered. Please allocate subjects.',
      ]);
  }
  ```

**Verification:**
```bash
# Test SCHOOL candidate
POST /api/candidates
{
  "candidate_type": "SCHOOL",
  "school_id": 1,
  "combination": "PCM",
  "candidate_id": "TEST-001",
  "full_name": "John Doe",
  "gender": "M",
  "exam_type": "ACSEE"
}

# Test PRIVATE candidate
POST /api/candidates
{
  "candidate_type": "PRIVATE",
  "candidate_id": "PRIV-001",
  "full_name": "Jane Doe",
  "gender": "F",
  "exam_type": "ACSEE"
}
```

### Create CandidateAllocationController (TODO)
- [ ] File: `app/Http/Controllers/CandidateAllocationController.php`
- [ ] Endpoints:
  - [ ] `GET /api/candidates/{id}/allocation` - Get current allocation
  - [ ] `POST /api/candidates/{id}/allocation` - Save allocation
  - [ ] `PUT /api/candidates/{id}/allocation` - Update allocation
  - [ ] `DELETE /api/candidates/{id}/allocation/{subjectId}` - Remove subject

---

## PHASE 4: FRONTEND - REGISTRATION FORM

### Update `/registration/candidates` View & Component
- [ ] Add `candidate_type` selector to "Add Candidate" modal
  ```html
  <div class="form-group">
    <label>Candidate Type *</label>
    <select x-model="form.candidate_type" class="form-control">
      <option value="SCHOOL">School Candidate</option>
      <option value="PRIVATE">Private Candidate</option>
    </select>
  </div>
  ```

- [ ] Make fields conditional based on type
  ```javascript
  :hidden="form.candidate_type !== 'SCHOOL'" // school_id field
  :hidden="form.candidate_type !== 'SCHOOL'" // combination field
  ```

- [ ] Update form submission logic
  ```javascript
  if (this.form.candidate_type === 'PRIVATE') {
      // Redirect to allocation after save
      window.location.href = `/exam-types/acsee?allocate=${data.candidate_id}`;
  }
  ```

- [ ] Update CSV template
  ```
  candidate_id, full_name, gender, candidate_type, combination, school_code, exam_type, exam_year
  ```

**Verification:**
- [ ] Open `/registration/candidates`
- [ ] Click "Register"
- [ ] Test selecting SCHOOL → See school & combination fields
- [ ] Test selecting PRIVATE → School field hidden
- [ ] Register SCHOOL candidate → Check auto-attached subjects
- [ ] Register PRIVATE candidate → Redirected to allocation

---

## PHASE 5: FRONTEND - SUBJECT ALLOCATION INTERFACE

### Create Subject Allocation Modal/Page
- [ ] Create component: `resources/views/components/acsee-subject-allocator.blade.php`
  OR file: `resources/views/exam-types/allocate-subjects.blade.php`

- [ ] Features:
  - [ ] List all ACSEE subjects with checkboxes
  - [ ] Mark subjects as "Principal" (radio/toggle)
  - [ ] Enforce minimum 3 principals
  - [ ] Highlight General Studies (mandatory)
  - [ ] Show validation errors in real-time
  - [ ] Save button with validation check

- [ ] Alpine component: `allocationManager()`
  ```javascript
  x-data="allocationManager()" @init="init()"
  
  Methods:
  - init()
  - loadSubjects()
  - toggleSubject(id)
  - markPrincipal(id)
  - unmarkPrincipal(id)
  - validateAllocation()
  - saveAllocation()
  - showErrors()
  ```

- [ ] Real-time validation
  ```javascript
  // Watch selected subjects
  $watch('selectedSubjects', () => {
      this.validateAllocation();
  });
  
  // Show validation feedback
  if (!validation.valid) {
      this.errors = validation.errors;
      this.saveDisabled = true;
  }
  ```

**Verification:**
- [ ] Open allocation interface
- [ ] Select subjects
- [ ] Try to save with < 3 principals → Error message
- [ ] Try to save without General Studies → Error message
- [ ] Correct errors → Save enabled
- [ ] Save → Subjects allocated with correct source=manual

---

## PHASE 6: FRONTEND - ACSEE CANDIDATES PAGE ENHANCEMENT

### Update `/exam-types/acsee` - Candidates Tab
- [ ] Add allocation status column
  ```html
  <th>Allocation Status</th>
  <td>
    <span v-if="isValid(candidate)" class="badge bg-green">✓ Valid</span>
    <span v-else class="badge bg-red">✗ Invalid</span>
  </td>
  ```

- [ ] Add "Allocate Subjects" button for PRIVATE candidates
  ```html
  <button @click="openAllocationModal(candidate)"
          x-show="candidate.candidate_type === 'PRIVATE'">
      Allocate Subjects
  </button>
  ```

- [ ] Add modal with subject allocator component
  ```html
  <div x-show="showAllocationModal">
      <x-acsee-subject-allocator 
          :candidate="currentCandidate"
          @save="saveAllocation"
          @close="showAllocationModal = false"
      />
  </div>
  ```

- [ ] Display validation errors/warnings
  ```javascript
  if (candidate.validation_result) {
      $el.classList.toggle('has-errors', !candidate.validation_result.valid);
  }
  ```

**Verification:**
- [ ] Navigate to `/exam-types/acsee`
- [ ] Candidates tab shows allocation status
- [ ] PRIVATE candidates have "Allocate Subjects" button
- [ ] SCHOOL candidates button hidden
- [ ] Click button → Allocation modal opens
- [ ] Allocate subjects → Status updates to ✓ Valid

---

## PHASE 7: CSV IMPORT ENHANCEMENT

### Update Import Logic
- [ ] Enhance `CandidateImportController`
  - [ ] Parse `candidate_type` column
  - [ ] Handle PRIVATE candidates (no school_id requirement)
  - [ ] Parse `allocated_subjects` column (comma-separated subject IDs/codes)
  - [ ] For SCHOOL + combination: auto-attach (source=import)
  - [ ] For PRIVATE + subjects: validate and attach (source=import)

- [ ] Create import processor
  ```php
  class AcseeImportProcessor {
      public function process($row, $examYear, $examType) {
          $candidate = Candidate::create([
              'candidate_type' => $row['candidate_type'] ?? 'SCHOOL',
              ...
          ]);
          
          if ($candidate->isSchool() && $row['combination']) {
              $this->attachCombination($candidate, $row['combination']);
          } elseif ($candidate->isPrivate() && $row['allocated_subjects']) {
              $this->attachSubjects($candidate, $row['allocated_subjects']);
          }
      }
  }
  ```

- [ ] Generate detailed import report
  ```
  Total records processed: X
  Successful: X
  Failed: X
  Skipped duplicates: X
  
  Candidates needing manual allocation:
  - PRIV-001 (Jane Doe) - missing subjects
  - PRIV-002 (John Smith) - missing principal count
  ```

**Verification:**
- [ ] Download updated template
- [ ] Create CSV with SCHOOL and PRIVATE candidates
- [ ] Import → Check candidates table
- [ ] SCHOOL candidates have subjects auto-attached
- [ ] PRIVATE candidates appear in allocation needed list
- [ ] Check source field: some='import', some='template'

---

## PHASE 8: TESTING

### Unit Tests - Models
- [ ] `CandidateTest`
  - [ ] `test_candidate_type_school_default()`
  - [ ] `test_candidate_type_private()`
  - [ ] `test_is_school_helper()`
  - [ ] `test_is_private_helper()`
  - [ ] `test_combination_relation()`

- [ ] `CandidateSubjectSelectionTest`
  - [ ] `test_principal_scope()`
  - [ ] `test_source_tracking()`
  - [ ] `test_created_by_tracking()`

### Unit Tests - Validation Service
- [ ] `AcseeValidatorTest`
  - [ ] `test_validate_minimum_principals()` → Fails with < 3
  - [ ] `test_validate_minimum_principals_pass()` → Passes with >= 3
  - [ ] `test_validate_general_studies_required()`
  - [ ] `test_validate_no_duplicates()`
  - [ ] `test_validate_max_subjects()`
  - [ ] `test_validation_result_structure()`

### Feature Tests - Registration
- [ ] `RegisterSchoolCandidateTest`
  - [ ] `test_register_school_candidate_with_combination()`
    - Should auto-attach subjects
    - Should have source='template'
    - Should pass validation

- [ ] `RegisterPrivateCandidateTest`
  - [ ] `test_register_private_candidate_without_school()`
    - Should create candidate
    - Should have no subjects initially
    - Should return allocation redirect

- [ ] `SubjectAllocationTest`
  - [ ] `test_allocate_subjects_manually()`
    - Should create selections with is_principal=true/false
    - Should have source='manual'
    - Should have created_by=user_id
    - Should pass validation

### Feature Tests - Import
- [ ] `ImportSchoolCandidatesTest`
  - [ ] `test_import_school_candidates_with_combinations()`
    - Should auto-attach per combination

- [ ] `ImportPrivateCandidatesTest`
  - [ ] `test_import_private_candidates_with_subjects()`
    - Should validate per subject
    - Should show allocation report

### Integration Tests - Data Integrity
- [ ] `BackwardCompatibilityTest`
  - [ ] `test_existing_school_candidates_unaffected()`
    - Old candidates still work
    - Old marks still accessible
    - Old results generation unaffected

- [ ] `MigrationTest`
  - [ ] `test_migration_adds_columns()`
  - [ ] `test_migration_default_values()`
  - [ ] `test_migration_reversible()`

**Test File Structure:**
```
tests/
  Unit/
    Models/
      CandidateTest.php
      CandidateSubjectSelectionTest.php
    Services/
      AcseeRegistrationValidatorTest.php
  Feature/
    Registration/
      RegisterSchoolCandidateTest.php
      RegisterPrivateCandidateTest.php
      SubjectAllocationTest.php
    Import/
      ImportSchoolCandidatesTest.php
      ImportPrivateCandidatesTest.php
    Integration/
      BackwardCompatibilityTest.php
      MigrationTest.php
```

**Run tests:**
```bash
php artisan test
# or specific test file
php artisan test tests/Feature/Registration/RegisterPrivateCandidateTest.php
```

---

## PHASE 9: DOCUMENTATION & ROLLOUT

### Documentation
- [ ] Create API documentation
  - [ ] New endpoints for allocation
  - [ ] Request/response examples
  - [ ] Error codes

- [ ] Create user guide
  - [ ] How to register SCHOOL candidates
  - [ ] How to register PRIVATE candidates
  - [ ] How to allocate subjects
  - [ ] How to use CSV import
  - [ ] Screenshots of workflows

- [ ] Create administrator guide
  - [ ] New database columns explained
  - [ ] Validation rules
  - [ ] Troubleshooting

### Code Review
- [ ] Review migration
- [ ] Review model changes
- [ ] Review service implementation
- [ ] Review controller updates
- [ ] Review frontend changes

### Deployment Checklist
- [ ] Backup database
- [ ] Run migrations
  ```bash
  php artisan migrate
  ```

- [ ] Clear caches
  ```bash
  php artisan cache:clear
  php artisan config:clear
  php artisan view:clear
  ```

- [ ] Test in staging environment
  - [ ] Register SCHOOL candidate
  - [ ] Register PRIVATE candidate
  - [ ] Allocate subjects
  - [ ] Import CSV
  - [ ] Check existing data

- [ ] Monitor logs
  ```bash
  tail -f storage/logs/laravel.log
  ```

- [ ] Rollback plan ready (if needed)
  ```bash
  php artisan migrate:rollback
  ```

---

## ROLLOUT STRATEGY

### Phased Rollout (Recommended)

**Phase 1: Private Rollout (Internal Testing)**
- [ ] Deploy to dev/staging
- [ ] Internal team tests all features
- [ ] Fix bugs found
- [ ] Estimate: 3-5 days

**Phase 2: Beta Rollout (Limited Users)**
- [ ] Enable for specific user group (e.g., one school)
- [ ] Monitor for issues
- [ ] Gather feedback
- [ ] Estimate: 1-2 weeks

**Phase 3: Full Rollout (All Users)**
- [ ] Enable feature for all
- [ ] Monitor performance
- [ ] Support any issues
- [ ] Estimate: Immediate post-approval

**Feature Flags (Optional):**
```php
// In model or controller
if (!config('features.necta_acsee_restructure')) {
    // Fall back to old behavior
}
```

### Rollback Plan
```bash
# If critical issues found:
php artisan migrate:rollback

# Restore from backup
# Notify affected users
# Post-mortem analysis
```

---

## SUCCESS CRITERIA

✓ SCHOOL candidates register with auto-attached combination subjects  
✓ PRIVATE candidates register without school_id requirement  
✓ PRIVATE candidates can manually allocate subjects  
✓ Validation enforces minimum 3 principals, mandatory General Studies  
✓ CSV import works for both candidate types  
✓ Import report shows allocation status  
✓ Existing SCHOOL candidates continue working (no data loss)  
✓ Marks and results still generate correctly  
✓ All tests pass  
✓ No performance degradation  

---

## TIMELINE ESTIMATE

| Phase | Duration | Status |
|-------|----------|--------|
| 1. Migrations & Models | 1-2 days | Ready |
| 2. Service Layer | 1 day | In Progress |
| 3. Controller Updates | 1 day | Todo |
| 4. Registration Form | 1-2 days | Todo |
| 5. Allocation Interface | 1-2 days | Todo |
| 6. ACSEE Candidates Page | 1 day | Todo |
| 7. CSV Import | 1 day | Todo |
| 8. Testing | 2-3 days | Todo |
| 9. Deployment | 1 day | Todo |
| **Total** | **10-15 days** | **In Progress** |

---

## CONTACT & ESCALATION

- **Architecture:** Amp (codebase analysis, design)
- **Development Lead:** [To be assigned]
- **QA Lead:** [To be assigned]
- **Deployment:** [To be assigned]

**Blockers or questions:** Refer to NECTA_ACSEE_RESTRUCTURE_ANALYSIS.md

---

**Document Status:** READY FOR IMPLEMENTATION  
**Last Updated:** 2026-02-15  
**Next Review:** After Phase 1 completion
