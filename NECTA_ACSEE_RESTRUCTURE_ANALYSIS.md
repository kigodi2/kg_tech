# NECTA-Aligned ACSEE Registration Restructure - Architecture Analysis

**Date:** 2026-02-15  
**Status:** ANALYSIS PHASE  
**Objective:** Support both SCHOOL and PRIVATE candidate registration models

---

## CURRENT ARCHITECTURE OVERVIEW

### 1. TABLE STRUCTURES

#### `candidates` table
```
id (PK)
school_id (FK) - REQUIRED
candidate_id (string, unique)
first_name
last_name
full_name (appended in model)
gender (ENUM: M, F)
date_of_birth
exam_type (ENUM: PSLE, CSEE, ACSEE) - Added column
combination (string) - Added column for ACSEE combination code (e.g., PCM, PCB)
is_active (boolean, default: true)
status (string, inferred from exam_type)
created_at, updated_at
```

**Issues for PRIVATE candidates:**
- `school_id` is REQUIRED (NOT NULL, constrained)
- `combination` is stored as string code, not FK relationship
- No `candidate_type` field to distinguish SCHOOL vs PRIVATE

#### `combinations` table
```
id (PK)
exam_type_id (FK)
code (string) - e.g., PCM, PCB, CBE
category
description
subjects (JSON or text field)
is_active
created_at, updated_at
```

#### `combination_subject` (Pivot)
```
id (PK)
combination_id (FK)
subject_id (FK)
unique constraint: (combination_id, subject_id)
created_at, updated_at
```

#### `candidate_subject_selections` table
```
id (PK)
candidate_id (FK)
exam_type_id (FK)
exam_year_id (FK)
subject_id (FK)
year (integer)
is_active
unique constraint: (candidate_id, exam_type_id, subject_id, year)
created_at, updated_at
```

**Current usage:**
- Stores selected subjects per candidate per exam year
- Used in mark entry and results display
- Does NOT track principal vs non-principal subjects
- No source tracking (manual vs import vs template)

#### `subjects` table
```
id (PK)
code (string)
name
category
written_papers
has_practical (boolean)
has_project (boolean)
exam_type_id (FK)
max_marks
description
is_active
created_at, updated_at
```

#### `exam_registrations` table (CandidateExamRegistration)
```
id (PK)
candidate_id (FK)
exam_type_id (FK)
exam_year_id (FK)
year (integer)
- other result fields
created_at, updated_at
```

---

## MODEL RELATIONSHIPS

### Current Candidate Model (`app/Models/Candidate.php`)
```php
// Existing relationships
->school()              // belongsTo School
->combination()         // belongsTo Combination via 'combination' code (string)
->examRegistrations()   // hasMany CandidateExamRegistration
->subjectSelections()   // hasMany CandidateSubjectSelection
->marks()               // hasMany SubjectMarks
->results()             // hasMany CandidateResult
->finalGrades()         // hasMany FinalGrade

// Key accessor
getExamYearAttribute()  // Gets from first exam registration
```

### Current Combination Model (`app/Models/Combination.php`)
```php
->examType()           // belongsTo ExamType
->subjects()           // belongsToMany Subject (via combination_subject pivot)
->syncSubjects()       // sync subjects
->hasSubject()         // check if subject exists
->getSubjectsWithDetails() // get subject info
```

---

## CURRENT REGISTRATION FLOW

### Route: `/registration/candidates`
- Blade view with Alpine.js component `candidatesManager()`
- Displays list of candidates filtered by region/district/school
- Supports:
  - Individual candidate registration via modal
  - CSV import with exam year selection
  - Bulk delete
  - Search/filtering

### Route: `/exam-types/{code}` → `/exam-types/acsee`
- Blade view with Alpine.js component `acseeManager()`
- Three tabs:
  1. **Subjects** - CRUD for ACSEE subjects
  2. **Combinations** - CRUD for subject combinations (PCM, PCB, etc.)
  3. **Candidates** - Read-only list of ACSEE candidates and their allocated subjects

### CandidateController::store()
```
Input validation:
  - school_id (required)
  - candidate_id (required, unique)
  - full_name or first_name + last_name
  - gender (required)
  - exam_type (PSLE, CSEE, ACSEE)
  - combination (optional string for ACSEE)
  - exam_year (optional)

Processing:
  1. Validate school authorization
  2. Create candidate record
  3. If exam_type == ACSEE and combination provided:
     - Create CandidateExamRegistration
     - Fetch combination subjects from combination_subject pivot
     - Auto-create CandidateSubjectSelection for each subject
```

---

## IDENTIFIED GAPS FOR PRIVATE CANDIDATES

### Problem 1: No Candidate Type Distinction
- System treats all candidates as SCHOOL candidates
- PRIVATE candidates would need school_id (violates logic)
- No way to track which registration model applies

### Problem 2: Subjects Are Auto-Attached from Combination
```php
// Current behavior in CandidateController
if ($combination_code) {
    $subjects = Combination::where('code', $combination_code)
        ->first()
        ->subjects()
        ->pluck('id');
    
    // Auto-create selections for ALL combination subjects
    foreach ($subjects as $subjectId) {
        CandidateSubjectSelection::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'subject_id' => $subjectId,
            'year' => $examYear->year,
        ]);
    }
}
```

**Issue for PRIVATE:**
- PRIVATE candidates should select subjects manually
- Combination should be optional (just a reference template)
- No auto-attachment should occur

### Problem 3: No Principal Subject Tracking
- `CandidateSubjectSelection` has no `is_principal` field
- NECTA requires 3 principal subjects minimum for ACSEE
- Current system can't enforce this validation

### Problem 4: No ACSEE Validation Rules
- No service to validate ACSEE registration rules
- No check for General Studies (mandatory)
- No check for subject conflicts (e.g., Physics + Biology together)
- No minimum subject count validation
- Cannot display validation errors to users

### Problem 5: No Source Tracking
- Cannot determine how subjects were allocated
- Manual selection vs import vs template auto-attach not tracked
- Auditing and debugging becomes difficult

---

## DATA INTEGRITY CONCERNS

### Current ACSEE Candidates Path
1. **Registration page (`/registration/candidates`):**
   - Register candidate with `exam_type=ACSEE`, `combination=PCM`
   - Auto-creates exam registration
   - Auto-creates subject selections for all combination subjects

2. **ACSEE page (`/exam-types/acsee`):**
   - Shows candidates tab (read-only)
   - No ability to modify subject allocation
   - No subject allocation interface

3. **Bulk import:**
   - Same flow as individual registration
   - Auto-attachment happens for all combinations

**Current Data Integrity Issues:**
- Cannot easily fix incorrect allocations without custom SQL
- CSV import doesn't validate ACSEE rules
- Combination field stored as string (could be inconsistent with combinations table)
- No audit trail of subject allocation changes
- No way to distinguish manually-selected vs auto-attached subjects

---

## MIGRATION STRATEGY (NON-DESTRUCTIVE)

### Phase 1: Add Support Columns (NEW MIGRATION)
```sql
-- Add candidate_type column to candidates table
ALTER TABLE candidates
ADD COLUMN candidate_type ENUM('SCHOOL', 'PRIVATE') DEFAULT 'SCHOOL' AFTER exam_type;

-- Add support columns to candidate_subject_selections
ALTER TABLE candidate_subject_selections
ADD COLUMN is_principal BOOLEAN DEFAULT FALSE AFTER subject_id;

ALTER TABLE candidate_subject_selections
ADD COLUMN source ENUM('manual', 'import', 'template') DEFAULT 'template' AFTER is_principal;

ALTER TABLE candidate_subject_selections
ADD COLUMN created_by INT NULL AFTER source;

-- Update unique constraint to allow multiple is_principal values
-- (Drop and recreate, or create new constraint with source)
```

### Phase 2: Make combination_id FK (New Relationships)
- Currently `combination` is string (code)
- Add optional `combination_id` (INT FK) for NECTA alignment
- Keep `combination` string for backward compatibility

### Backward Compatibility
- All existing SCHOOL candidates get `candidate_type='SCHOOL'` (default)
- All existing subjects remain `is_principal=FALSE`, `source='template'` (default)
- Existing flow continues unmodified
- No data loss or deletion

---

## PROPOSED SOLUTION ARCHITECTURE

### 1. Updated Candidate Model
```php
class Candidate extends Model {
    protected $fillable = [
        'school_id',
        'candidate_id',
        'full_name',
        'gender',
        'exam_type',
        'combination',        // Keep for backward compat
        'combination_id',     // NEW: FK to combinations
        'candidate_type',     // NEW: ENUM SCHOOL/PRIVATE
        'status',
        'is_active',
    ];
    
    // NEW relationships
    public function combinationRelation() {
        return $this->belongsTo(Combination::class, 'combination_id');
    }
    
    // For PRIVATE candidates: allocated subjects (distinct from combination)
    public function allocatedSubjects() {
        return $this->hasMany(CandidateSubjectSelection::class)
            ->where('is_active', true);
    }
    
    // Helper method
    public function isSchool(): bool {
        return $this->candidate_type === 'SCHOOL';
    }
    
    public function isPrivate(): bool {
        return $this->candidate_type === 'PRIVATE';
    }
}
```

### 2. Updated CandidateSubjectSelection Model
```php
class CandidateSubjectSelection extends Model {
    protected $fillable = [
        'candidate_id',
        'exam_type_id',
        'exam_year_id',
        'subject_id',
        'year',
        'is_principal',    // NEW: boolean
        'source',          // NEW: manual/import/template
        'created_by',      // NEW: user who allocated (if manual)
        'is_active',
    ];
    
    // NEW relationship
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### 3. New Service: AcseeRegistrationValidator
```php
namespace App\Services;

class AcseeRegistrationValidator {
    public function validate(Candidate $candidate): ValidationResult {
        // Rule 1: Minimum 3 principal subjects
        // Rule 2: General Studies mandatory
        // Rule 3: No duplicate subjects
        // Rule 4: Maximum subjects limit
        // Rule 5: Subject conflict prevention (configurable)
        
        return ValidationResult {
            valid: bool,
            errors: array,
            warnings: array,
            principals_count: int,
            subjects_count: int,
        };
    }
}
```

### 4. Registration Form Flow (Updated)

#### For SCHOOL Candidates
```
1. User selects Candidate Type: SCHOOL
2. Form shows:
   - School (required)
   - Combination (required, dropdown)
   - Candidate ID
   - Name, Gender, DOB
3. On save:
   - Create candidate with candidate_type='SCHOOL'
   - Create exam registration
   - Fetch combination.subjects (PIVOT)
   - Auto-create CandidateSubjectSelection for each subject
   - Set source='template', is_principal=FALSE (for now)
4. Success message
```

#### For PRIVATE Candidates
```
1. User selects Candidate Type: PRIVATE
2. Form shows:
   - Candidate ID
   - Name, Gender, DOB
   - Combination (optional, as reference only)
   - (NO school_id - optional or default to central registry)
3. On save:
   - Create candidate with candidate_type='PRIVATE'
   - Create exam registration
   - NO auto-attachment of subjects
4. Redirect to subject allocation page
   OR show "Allocate Subjects" button
```

### 5. Subject Allocation Page (NEW for PRIVATE)

```
For PRIVATE candidates:
- Show all ACSEE subjects
- User selects subjects manually
- Mark subjects as principal (min 3)
- Validate:
  * Min 3 principals
  * General Studies included
  * Max subjects limit
- Save with source='manual', created_by=auth()->id()
- Validate on save using AcseeRegistrationValidator
```

### 6. CSV Import Enhancement

```
Current template columns:
candidate_id, full_name, gender, combination, school_code, exam_type, exam_year

NEW template columns (optional):
candidate_id, full_name, gender, candidate_type, combination, school_code, exam_type, exam_year, allocated_subjects

Processing:
- If candidate_type='SCHOOL' + combination: auto-attach (source=import)
- If candidate_type='PRIVATE' + allocated_subjects: validate and attach (source=import)
- Show import report:
  * Total records
  * Successful allocations
  * Rule violations
  * Candidates needing manual subject allocation
```

### 7. ACSEE Candidates Page Enhancement

```
Current view: Read-only list with combination and subjects

NEW features:
1. Add allocation mode toggle (Apply Template / Manual Selection)
2. For PRIVATE candidates:
   - Show "Allocate Subjects" button
   - Open subject allocation modal
3. For SCHOOL candidates:
   - Show combination (read-only)
   - Show auto-attached subjects
   - Option to override (rare case)
4. Display validation status:
   - Green checkmark if valid
   - Red warning if rule violations
```

---

## IMPLEMENTATION ROADMAP

### Step 1: Database Migrations
- [ ] Create migration: Add candidate_type, is_principal, source to tables
- [ ] Make combination_id nullable in candidates (optional)
- [ ] Update foreign key constraints

### Step 2: Model Updates
- [ ] Update Candidate model with new fields & relationships
- [ ] Update CandidateSubjectSelection model
- [ ] Add validation scopes

### Step 3: Service Layer
- [ ] Create AcseeRegistrationValidator service
- [ ] Create CandidateAllocationService
- [ ] Create AcseeImportProcessor service

### Step 4: Registration Form
- [ ] Add candidate_type selector to modal
- [ ] Conditional form fields based on type
- [ ] Update controller to handle both types
- [ ] Update validation rules

### Step 5: Subject Allocation Interface
- [ ] Create allocation view/component
- [ ] Create subject selector modal
- [ ] Add validation UI feedback
- [ ] Create API endpoint for allocation

### Step 6: ACSEE Management Page
- [ ] Add allocation mode options
- [ ] Integrate subject allocation interface
- [ ] Display validation status
- [ ] Add bulk allocation features

### Step 7: CSV Import Enhancement
- [ ] Update import template
- [ ] Enhance processor logic
- [ ] Add import validation report
- [ ] Add error handling

### Step 8: Testing
- [ ] Feature tests for SCHOOL registration
- [ ] Feature tests for PRIVATE registration
- [ ] Tests for validation engine
- [ ] Tests for import processor
- [ ] Data integrity tests

---

## KEY DESIGN DECISIONS

### Why Keep Combination as String?
- **Backward Compatibility:** Existing records reference `combination` string code
- **Simple queries:** No join needed for display
- **Familiar to users:** They see "PCM", "PCB" everywhere

### Why Add combination_id FK?
- **Clean relationships:** Proper referential integrity
- **Future queries:** Can easily fetch combination details
- **Auditing:** Can track which exact combination was used (vs just code)

### Why is source Enum?
- **Traceability:** Understand how allocation happened
- **Filtering:** Easy to find all manually-allocated vs imported
- **Reporting:** Show allocation method statistics

### Why is_principal Boolean?
- **Simplicity:** Binary choice, no ambiguity
- **Validation:** Easy to count principals (3 minimum)
- **Query efficiency:** Can index on is_principal + candidate_id

---

## DATA MIGRATION EXAMPLES

### Existing SCHOOL Candidate Data
```
Before:
candidates.candidate_id = "S1234-001"
candidates.school_id = 1
candidates.exam_type = "ACSEE"
candidates.combination = "PCM"

candidate_subject_selections:
- candidate_id=1, subject_id=10 (Physics)
- candidate_id=1, subject_id=11 (Chemistry)
- candidate_id=1, subject_id=12 (Mathematics)

After (no change in data, only new columns):
candidates.candidate_type = "SCHOOL" (default)
candidates.combination_id = NULL (optional)

candidate_subject_selections:
- is_principal = FALSE (default)
- source = "template" (default for historical data)
- created_by = NULL
```

### New PRIVATE Candidate
```
candidates:
- candidate_id = "P2345-001"
- school_id = NULL OR default_school_id
- exam_type = "ACSEE"
- candidate_type = "PRIVATE"
- combination = "PCM" (optional reference)

candidate_subject_selections:
- candidate_id=2, subject_id=10 (is_principal=TRUE)
- candidate_id=2, subject_id=11 (is_principal=TRUE)
- candidate_id=2, subject_id=12 (is_principal=TRUE)
- source = "manual"
- created_by = user_id
```

---

## VALIDATION RULES ENGINE (Pseudocode)

```php
class AcseeRegistrationValidator {
    
    public function validate(Candidate $candidate): ValidationResult {
        $errors = [];
        $warnings = [];
        
        $principals = $candidate->allocatedSubjects()
            ->where('is_principal', true)
            ->where('exam_type_id', ACSEE_ID)
            ->count();
        
        $subjects = $candidate->allocatedSubjects()
            ->where('exam_type_id', ACSEE_ID)
            ->get();
        
        // Rule 1: Minimum 3 principals
        if ($principals < 3) {
            $errors[] = "Minimum 3 principal subjects required, found {$principals}";
        }
        
        // Rule 2: General Studies mandatory
        $hasGeneralStudies = $subjects->contains(fn($s) => 
            $s->subject->code === 'GS'
        );
        if (!$hasGeneralStudies) {
            $errors[] = "General Studies (GS) is mandatory";
        }
        
        // Rule 3: No duplicate subjects
        if ($subjects->pluck('subject_id')->unique()->count() !== $subjects->count()) {
            $errors[] = "Duplicate subjects found";
        }
        
        // Rule 4: Maximum subjects limit
        if ($subjects->count() > 8) {
            $errors[] = "Maximum 8 subjects allowed";
        }
        
        // Rule 5: Subject conflicts (configurable)
        // Check BusinessRules table for conflicts
        
        // Warnings (non-blocking)
        if ($principals > 5) {
            $warnings[] = "More than 5 principal subjects may affect performance";
        }
        
        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
            principals_count: $principals,
            subjects_count: $subjects->count(),
        );
    }
}
```

---

## ROLLBACK/RECOVERY

All migrations are **additive only:**
- New columns have DEFAULT values
- Existing queries still work
- Old code paths continue functioning
- Can disable feature flags without data loss
- Easy to rollback if needed

---

## TESTING MATRIX

| Scenario | Type | Expected | Status |
|----------|------|----------|--------|
| Register SCHOOL candidate with combination | Feature | Auto-attach subjects from combination | TODO |
| Register PRIVATE candidate without combination | Feature | No auto-attach, redirect to allocation | TODO |
| Private candidate allocates < 3 principals | Feature | Validation error | TODO |
| Private candidate allocates without General Studies | Feature | Validation error | TODO |
| CSV import SCHOOL candidate | Feature | Auto-attach works | TODO |
| CSV import PRIVATE with subjects | Feature | Manual allocation saved | TODO |
| Existing SCHOOL candidate still works | Integration | No breaking changes | TODO |
| Existing marks data still accessible | Integration | No data loss | TODO |
| Duplicate subject prevention | Unit | Validation catches | TODO |
| Subject conflict prevention | Unit | Configurable rules enforced | TODO |

---

## NEXT STEPS

1. **Review this analysis** with team
2. **Create migrations** for new columns
3. **Update models** with relationships
4. **Implement AcseeRegistrationValidator service**
5. **Update registration form UI**
6. **Create subject allocation interface**
7. **Enhance ACSEE candidates page**
8. **Add comprehensive tests**
9. **Deploy incrementally** (feature flags optional)

---

**Document Status:** READY FOR IMPLEMENTATION  
**Created by:** Amp  
**Date:** 2026-02-15
