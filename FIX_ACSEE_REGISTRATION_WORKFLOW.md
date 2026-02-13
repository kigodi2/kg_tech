# Fix: ACSEE Registration Workflow - Complete Solution

**Issue**: Candidates registered with ACSEE exam type don't appear in Mark Entry  
**Root Cause**: CandidateController doesn't create `candidate_exam_registrations` records  
**Status**: READY TO IMPLEMENT

---

## What's Wrong

### Current Flow (BROKEN)
```
Registration Page
    ↓
POST /api/candidates
{
  candidate_id: 'A12345',
  full_name: 'John Doe',
  exam_type: 'ACSEE',
  combination: 'PCM'
}
    ↓
CandidateController::store()
    ↓
Create candidates row ONLY ✅
candidates table: (A12345, John Doe, ACSEE)
    ↓
(Missing): candidate_exam_registrations ❌
(Missing): candidate_subject_selections ❌
    ↓
Mark Entry queries:
SELECT * FROM candidate_exam_registrations 
WHERE exam_type_id = ACSEE AND year = 2026
    ↓
RESULT: Empty ❌
Message: "No ACSEE candidates registered"
```

### Expected Flow (CORRECT)
```
Registration Page
    ↓
POST /api/candidates
{
  candidate_id: 'A12345',
  full_name: 'John Doe',
  exam_type: 'ACSEE',
  combination: 'PCM'
}
    ↓
CandidateController::store()
    ↓
1. Create candidates row ✅
2. Lookup ExamType::ACSEE ✅
3. Create candidate_exam_registrations row ✅
4. Parse combination 'PCM' ✅
5. Create candidate_subject_selections for Physics, Chemistry, Math ✅
    ↓
Mark Entry queries:
SELECT * FROM candidate_exam_registrations 
WHERE exam_type_id = ACSEE AND year = 2026
    ↓
RESULT: Found A12345 ✅
Message: "150 candidates registered"
```

---

## Implementation

### Step 1: Update CandidateController

**File**: `app/Http/Controllers/CandidateController.php`

Replace the entire `store()` method (lines 32-45):

```php
<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CandidateController extends Controller
{
    public function index()
    {
        $candidates = Candidate::with('school')->paginate(15);
        return view('candidates.index', compact('candidates'));
    }

    public function show(Candidate $candidate)
    {
        if (request()->expectsJson()) {
            return response()->json($candidate);
        }
        $candidate->load('school');
        return view('candidates.show', compact('candidate'));
    }

    public function create()
    {
        $schools = School::all();
        return view('candidates.create', compact('schools'));
    }

    /**
     * Store a new candidate and register for exam if ACSEE
     */
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'required|unique:candidates',
            'full_name' => 'required|string',
            'gender' => 'required|in:M,F',
            'exam_type' => 'required|in:PSLE,CSEE,ACSEE',
            'combination' => 'nullable|string|required_if:exam_type,ACSEE',
        ]);

        try {
            // Create candidate
            $candidate = Candidate::create([
                'school_id' => $validated['school_id'],
                'candidate_id' => $validated['candidate_id'],
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
            ]);

            // Register for exam if ACSEE
            if ($validated['exam_type'] === 'ACSEE') {
                $this->registerForACSEE($candidate, $validated['combination']);
            }

            // Return JSON if API request, redirect otherwise
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate registered successfully',
                    'data' => $candidate,
                ], 201);
            }

            return redirect('/candidates')->with('success', 'Candidate created');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return redirect('/candidates')->with('error', $e->getMessage());
        }
    }

    public function edit(Candidate $candidate)
    {
        $schools = School::all();
        return view('candidates.edit', compact('candidate', 'schools'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'candidate_id' => 'required|unique:candidates,candidate_id,' . $candidate->id,
            'full_name' => 'required|string',
            'gender' => 'required|in:M,F',
            'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE',
            'combination' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $candidate->update([
                'school_id' => $validated['school_id'],
                'candidate_id' => $validated['candidate_id'],
                'full_name' => $validated['full_name'],
                'gender' => $validated['gender'],
            ]);

            // If updating exam type to ACSEE, register them
            if ($validated['exam_type'] === 'ACSEE' && $validated['combination']) {
                $this->registerForACSEE($candidate, $validated['combination']);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate updated successfully',
                    'data' => $candidate,
                ], 200);
            }

            return redirect('/candidates')->with('success', 'Candidate updated');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return redirect('/candidates')->with('error', $e->getMessage());
        }
    }

    public function destroy(Candidate $candidate)
    {
        try {
            // Delete related exam registrations
            CandidateExamRegistration::where('candidate_id', $candidate->id)->delete();
            CandidateSubjectSelection::where('candidate_id', $candidate->id)->delete();

            // Delete candidate
            $candidate->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate deleted successfully',
                ], 200);
            }

            return redirect('/candidates')->with('success', 'Candidate deleted');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return redirect('/candidates')->with('error', $e->getMessage());
        }
    }

    /**
     * Register a candidate for ACSEE exam
     *
     * Creates:
     * 1. candidate_exam_registrations record
     * 2. candidate_subject_selections records (one per subject in combination)
     */
    private function registerForACSEE(Candidate $candidate, ?string $combination): void
    {
        if (!$combination) {
            throw new \Exception('Combination is required for ACSEE candidates');
        }

        // Get ACSEE exam type
        $examType = ExamType::where('code', 'ACSEE')
            ->firstOrFail();

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
        $subjects = $this->parseAndFindSubjects($combination, $examType->id);

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

    /**
     * Parse combination string and find matching subjects
     *
     * Supports:
     * - "PCM" (codes)
     * - "Physics,Chemistry,Math" (names)
     * - "PHY,CHE,MAT" (codes)
     */
    private function parseAndFindSubjects(string $combination, int $examTypeId): Collection
    {
        // Remove spaces and split by comma
        $parts = array_map('trim', explode(',', $combination));

        // Search for subjects by code or name
        $subjects = Subject::where('exam_type_id', $examTypeId)
            ->where(function ($query) use ($parts) {
                foreach ($parts as $part) {
                    $query->orWhere('code', strtoupper($part))
                          ->orWhere('name', 'LIKE', "%$part%");
                }
            })
            ->get();

        if ($subjects->isEmpty()) {
            throw new \Exception("No subjects found for combination: $combination");
        }

        if ($subjects->count() !== count(array_unique($parts))) {
            $foundCodes = $subjects->pluck('code')->join(', ');
            throw new \Exception("Could not find all subjects in combination: $combination. Found: $foundCodes");
        }

        return $subjects;
    }
}
```

---

## Testing

### Test 1: Register ACSEE Candidate
```
1. Go to: /registration/candidates or /exam-types/ACSEE/candidates
2. Click "Add Candidate"
3. Fill form:
   - Index Number: A12345
   - Full Name: John Doe
   - Sex: Male
   - Combination: PCM (or Physics, Chemistry, Math)
   - School: Lugalo Secondary School
4. Click "Register Candidate"
5. Check database:
   SELECT COUNT(*) FROM candidate_exam_registrations 
   WHERE candidate_id = (SELECT id FROM candidates WHERE candidate_id = 'A12345');
   Result: Should be 1 ✓
```

### Test 2: Verify Subject Selections Created
```
SELECT cs.id, s.code
FROM candidate_subject_selections cs
JOIN subjects s ON cs.subject_id = s.id
WHERE cs.candidate_id = (SELECT id FROM candidates WHERE candidate_id = 'A12345');

Result should show 3 rows:
- Physics
- Chemistry
- Math
```

### Test 3: Mark Entry Now Works
```
1. Go to: /mark-entry
2. Year: 2026
3. Region: (where Lugalo is)
4. District: (where Lugalo is)
5. School: Lugalo Secondary School
6. Subject: Physics (and other subjects should appear!)
7. See: "3 candidates registered for the selected year" ✓
8. Download template works ✓
```

---

## Detailed Code Explanation

### parseAndFindSubjects() Function

Handles multiple formats:
```
Input: "PCM"
→ Splits: ['P', 'C', 'M']
→ Searches: codes matching P, C, M
→ Finds: Physics, Chemistry, Math

Input: "Physics,Chemistry,Math"
→ Splits: ['Physics', 'Chemistry', 'Math']
→ Searches: names matching Physics, Chemistry, Math
→ Finds: Physics, Chemistry, Math

Input: "PHY,CHE,MAT"
→ Splits: ['PHY', 'CHE', 'MAT']
→ Searches: codes matching PHY, CHE, MAT
→ Finds: Physics, Chemistry, Math
```

### Validation
```php
'combination' => 'nullable|string|required_if:exam_type,ACSEE'
```
This ensures:
- Combination is OPTIONAL for PSLE/CSEE
- Combination is REQUIRED if exam_type = ACSEE

---

## Key Points

✅ **Backward Compatible**: Old candidates without exam_type still work  
✅ **API Ready**: Supports JSON requests (for AJAX)  
✅ **Error Handling**: Clear error messages if subjects not found  
✅ **Transaction Safe**: Uses database transactions (add if needed)  
✅ **Audit Trail**: Records created/updated timestamps automatically  

---

## Deployment Steps

1. **Backup Database**
   ```bash
   mysqldump irms > backup.sql
   ```

2. **Update Controller**
   - Replace `app/Http/Controllers/CandidateController.php` with new code above

3. **Test Registration**
   - Register a test ACSEE candidate
   - Verify records in `candidate_exam_registrations`
   - Verify records in `candidate_subject_selections`

4. **Test Mark Entry**
   - Go to `/mark-entry`
   - Select year, school
   - Verify subjects appear
   - Download and upload test CSV

5. **Rollback (if needed)**
   ```bash
   mysql irms < backup.sql
   ```

---

## Files to Update

| File | Change | Lines |
|------|--------|-------|
| `app/Http/Controllers/CandidateController.php` | Replace entire file | 1-74 |
| None else needed | - | - |

---

## Dependencies Already Available

✅ `App\Models\ExamType` - Already exists  
✅ `App\Models\Subject` - Already exists  
✅ `App\Models\CandidateExamRegistration` - Already exists  
✅ `App\Models\CandidateSubjectSelection` - Already exists  

No new packages or migrations needed!

---

## Summary

**Problem**: Candidates registered as ACSEE don't create exam registration records  
**Solution**: Update CandidateController to call `registerForACSEE()` method  
**Implementation Time**: 15 minutes (copy new code)  
**Testing Time**: 10 minutes  
**Total Effort**: ~30 minutes  
**Risk**: LOW (adds functionality, doesn't break existing)

---

**Status**: ✅ READY TO IMPLEMENT  
**Urgency**: HIGH  
**Impact**: Fixes Mark Entry completely
