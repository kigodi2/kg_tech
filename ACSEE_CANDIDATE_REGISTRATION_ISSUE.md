# ACSEE Candidate Registration Issue - Root Cause & Solution

**Problem**: No ACSEE candidates appear in Mark Entry when you select a school  
**Status**: ⚠️ IMPLEMENTATION INCOMPLETE  
**Root Cause**: Candidates registered with exam_type='ACSEE' are NOT being linked to `candidate_exam_registrations` table

---

## The Issue (Diagnosed)

### What Should Happen
```
1. User registers candidate in Candidates Management
   - Index Number: A12345
   - Exam Type: ACSEE ✓
   - School: Lugalo Secondary School ✓

2. System creates:
   a) candidates table row
   b) candidate_exam_registrations row (MISSING!)
   c) candidate_subject_selections rows (MISSING!)

3. Mark Entry queries candidate_exam_registrations
   - Looks for: exam_type_id = ACSEE, year = 2026, is_active = true
   - Should find candidate A12345
   - But FINDS NOTHING (no exam registration)

Result: ❌ "No ACSEE candidates registered for the selected year"
```

### What's Actually Happening
```
✅ Candidate created in candidates table with exam_type='ACSEE'
❌ candidate_exam_registrations NOT created
❌ candidate_subject_selections NOT created

When Mark Entry checks:
  SELECT * FROM candidate_exam_registrations 
  WHERE exam_type_id = <ACSEE> AND year = 2026

Result: Empty (no records)
Reason: Table never populated during registration
```

---

## Where the Problem Is

### Location 1: Candidates Registration Page
**File**: `resources/views/registration/candidates.blade.php`

**Problem**: When saving candidate with exam_type='ACSEE':
```javascript
async saveCandidate() {
    const payload = {
        candidate_id,
        full_name,
        gender,
        school_id,
        combination,
        exam_type: 'ACSEE'
    };
    
    // Saves to candidates table ONLY
    // Does NOT create candidate_exam_registrations
    // Does NOT create candidate_subject_selections
}
```

### Location 2: ACSEE Management Page
**File**: `resources/views/exam-types/acsee.blade.php`

**Problem**: ACSEE candidate registration saves exam_type string:
```javascript
async saveCandidate() {
    const payload = {
        full_name,
        gender,
        combination,
        school_id,
        exam_type: 'ACSEE'  // ← String, not ID
    };
    
    // Saves to candidates table
    // Missing: candidate_exam_registrations creation
    // Missing: candidate_subject_selections creation
}
```

---

## Database Schema Expected

### Should Create (Currently Missing)

```sql
-- When candidate registered as ACSEE
INSERT INTO candidate_exam_registrations (
    candidate_id,
    exam_type_id,              -- FK to exam_types table (ACSEE)
    year,                       -- 2026
    is_active,                  -- true
    is_verified                 -- false initially
) VALUES (
    <candidate_id>,
    <acsee_exam_type_id>,
    2026,
    true,
    false
);

-- When candidate has combination (e.g., PCM)
INSERT INTO candidate_subject_selections (
    candidate_id,
    exam_type_id,              -- FK to exam_types (ACSEE)
    subject_id,                -- FK to subjects (Physics)
    year,                       -- 2026
    is_active                   -- true
);

-- For each subject in combination (Physics, Chemistry, Math)
-- Example for Physics:
INSERT INTO candidate_subject_selections (...) VALUES (...);
-- Example for Chemistry:
INSERT INTO candidate_subject_selections (...) VALUES (...);
-- Example for Math:
INSERT INTO candidate_subject_selections (...) VALUES (...);
```

---

## The Fix (Implementation Required)

### Fix Location 1: CandidateController::store()

**File**: `app/Http/Controllers/CandidateController.php`

**Current Code** (incomplete):
```php
public function store(Request $request)
{
    $candidate = Candidate::create([
        'candidate_id' => $request->candidate_id,
        'full_name' => $request->full_name,
        'gender' => $request->gender,
        'school_id' => $request->school_id,
        'exam_type' => $request->exam_type,  // Stored as string
    ]);
    
    return response()->json(['success' => true, 'data' => $candidate]);
}
```

**Should Be** (with exam registration):
```php
public function store(Request $request)
{
    $request->validate([
        'candidate_id' => 'required|unique:candidates',
        'full_name' => 'required|string',
        'gender' => 'required|in:M,F',
        'school_id' => 'required|exists:schools,id',
        'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
        'combination' => 'nullable|string|required_if:exam_type,ACSEE',
    ]);

    // Create candidate
    $candidate = Candidate::create([
        'candidate_id' => $request->candidate_id,
        'full_name' => $request->full_name,
        'gender' => $request->gender,
        'school_id' => $request->school_id,
    ]);

    // For ACSEE candidates, register for exam and subjects
    if ($request->exam_type === 'ACSEE') {
        $examType = ExamType::where('code', 'ACSEE')->firstOrFail();
        $currentYear = now()->year;

        // Create exam registration
        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'year' => $currentYear,
            'is_active' => true,
            'is_verified' => false,
        ]);

        // Parse combination and register subjects
        if ($request->combination) {
            $subjects = $this->parseCombination($request->combination, $examType->id);
            
            foreach ($subjects as $subject) {
                CandidateSubjectSelection::create([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'subject_id' => $subject->id,
                    'year' => $currentYear,
                    'is_active' => true,
                ]);
            }
        }
    }

    return response()->json(['success' => true, 'data' => $candidate]);
}

private function parseCombination(string $combination, int $examTypeId): Collection
{
    // Parse "PCM" or "Physics,Chemistry,Math"
    $codes = explode(',', str_replace(' ', '', $combination));
    
    return Subject::where('exam_type_id', $examTypeId)
        ->whereIn('code', $codes)
        ->get();
}
```

### Fix Location 2: Also in ACSEE Page

**File**: `resources/views/exam-types/acsee.blade.php` lines 585-619

**The JavaScript saveCandidate() should call the same API endpoint**:
```javascript
async saveCandidate() {
    try {
        const url = this.editingCandidateId 
            ? `/api/candidates/${this.editingCandidateId}`
            : `/api/candidates`;
        const method = this.editingCandidateId ? 'PUT' : 'POST';

        const payload = {
            full_name: this.candidateForm.full_name,
            gender: this.candidateForm.gender,
            combination: this.candidateForm.combination,
            school_id: parseInt(this.candidateForm.school_id),
            exam_type: 'ACSEE',
        };

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            // Same as Candidates Management page
            // Controller will handle exam registration
            this.showMessage(
                this.editingCandidateId ? 'Candidate updated' : 'Candidate registered',
                'success'
            );
            this.candidateModalOpen = false;
            this.editingCandidateId = null;
            await this.loadCandidates();
        } else {
            this.showMessage('Error saving candidate', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        this.showMessage('Error saving candidate', 'error');
    }
}
```

---

## What Needs to Be Done

### Step 1: Check CandidateController

**File**: `app/Http/Controllers/CandidateController.php`

Look for the `store()` method:
```php
public function store(Request $request)
{
    // Check if it creates candidate_exam_registrations
    // Check if it creates candidate_subject_selections
}
```

**If missing**, add the logic above.

### Step 2: Create/Update Migration (if needed)

If `candidate_exam_registrations` and `candidate_subject_selections` tables don't exist:

```bash
php artisan make:migration create_candidate_exam_registrations_table
php artisan make:migration create_candidate_subject_selections_table
```

**Table Structure**:
```sql
-- candidate_exam_registrations
CREATE TABLE candidate_exam_registrations (
    id BIGINT PRIMARY KEY,
    candidate_id BIGINT FK → candidates,
    exam_type_id BIGINT FK → exam_types,
    year INT,
    registration_number VARCHAR(100),
    is_verified BOOLEAN DEFAULT false,
    verification_date TIMESTAMP,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(candidate_id, exam_type_id, year)
);

-- candidate_subject_selections
CREATE TABLE candidate_subject_selections (
    id BIGINT PRIMARY KEY,
    candidate_id BIGINT FK → candidates,
    exam_type_id BIGINT FK → exam_types,
    subject_id BIGINT FK → subjects,
    year INT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(candidate_id, exam_type_id, subject_id, year)
);
```

### Step 3: Test

After implementing:

```bash
# 1. Register an ACSEE candidate
# Via: /registration/candidates or /exam-types/ACSEE

# 2. Go to Mark Entry page
# URL: /mark-entry

# 3. Select:
#    - Year: 2026
#    - School: Lugalo Secondary School (where you registered candidate)

# 4. Expected: Subject dropdown should populate (no warning)
# Expected: Candidate count shown next to subject
```

---

## Why This Wasn't Caught

### Incomplete Implementation
1. Candidates table stores `exam_type` field (string)
2. But Mark Entry queries `candidate_exam_registrations` table (not used)
3. No bridge between the two during registration

### What Should Happen on Registration
```
Registration page calls:
  POST /api/candidates
  {
    candidate_id: 'A12345',
    exam_type: 'ACSEE',
    combination: 'PCM',
    ...
  }

Controller should:
  1. Create candidates row
  2. Look up ExamType.ACSEE
  3. Create candidate_exam_registrations row ← MISSING
  4. Parse combination
  5. Create candidate_subject_selections rows ← MISSING

Currently only does step 1.
```

---

## Quick Diagnosis Query

Run this to verify the issue:

```sql
-- Check candidates registered as ACSEE
SELECT COUNT(*) as candidates_with_exam_type
FROM candidates
WHERE exam_type = 'ACSEE';
-- Result: Should show candidates you registered

-- Check exam registrations
SELECT COUNT(*) as exam_registrations
FROM candidate_exam_registrations
WHERE exam_type_id = (SELECT id FROM exam_types WHERE code = 'ACSEE')
  AND year = 2026;
-- Result: Will be 0 (EXPLAINS THE PROBLEM!)

-- The gap
SELECT 
    (SELECT COUNT(*) FROM candidates WHERE exam_type = 'ACSEE') as in_candidates_table,
    (SELECT COUNT(*) FROM candidate_exam_registrations WHERE exam_type_id = 1 AND year = 2026) as in_exam_registrations
```

---

## Summary

**Problem**: Registering a candidate with exam_type='ACSEE' does NOT create `candidate_exam_registrations` and `candidate_subject_selections` records.

**Result**: Mark Entry queries for candidates in `candidate_exam_registrations` → finds nothing → "No ACSEE candidates registered"

**Solution**: Update CandidateController to create proper exam registration records when saving ACSEE candidates.

**Effort**: 2-3 hours implementation + testing

**Urgency**: HIGH - Blocks Mark Entry functionality

---

**Status**: ⚠️ ROOT CAUSE IDENTIFIED  
**Recommendation**: Implement fix in CandidateController::store() method
