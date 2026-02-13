# Exam Year Import Fix - Complete Implementation

## Problem
When importing candidates via CSV in the REGISTRATION page, the **Exam Year column showed empty (-)** instead of displaying the year selected during import. This broke the alignment between REGISTRATION and MARK ENTRY because:

1. Candidates were imported without `exam_year` data
2. MARK ENTRY queries by `exam_year_id` in `CandidateExamRegistration` 
3. No exam registrations existed → "No ACSEE candidates registered" error

## Root Causes

### 1. Frontend - Missing exam_year in form initialization (Fixed)
**File:** `resources/views/registration/candidates.blade.php`

- **Line 858** (`openAddModal`): formData didn't include `exam_year: ''`
- **Line 875** (`openEditModal`): formData didn't preserve `exam_year` from existing candidate

### 2. Backend - API endpoint ignored exam_year (Fixed)
**File:** `routes/api.php`

- **Line 210** (`POST /api/candidates/import`): 
  - Didn't validate `exam_year` parameter
  - Didn't validate `exam_type` parameter
  - Didn't call `registerForACSEE()` method
  - Simply created `Candidate` records without `CandidateExamRegistration`

### 3. Table UX - No visibility of exam_year (Fixed)
**File:** `resources/views/registration/candidates.blade.php`

- Table didn't show "Exam Year" column, making it impossible to see what data was imported

## Fixes Applied

### Fix 1: Frontend Form Initialization
```javascript
// Before:
this.formData = { candidate_id: '', full_name: '', gender: '', combination: '', school_id: '', exam_type: '' };

// After:
this.formData = { candidate_id: '', full_name: '', gender: '', combination: '', school_id: '', exam_type: '', exam_year: '' };
```

### Fix 2: API Import Endpoint Enhancement
Updated `/api/candidates/import` route to:

1. **Accept exam_year and exam_type parameters**
   ```php
   'exam_year' => 'nullable|integer|min:2000|max:' . (now()->year + 1),
   'exam_type' => 'nullable|in:PSLE,CSEE,ACSEE'
   ```

2. **Call registerForACSEE() for ACSEE candidates**
   ```php
   if (strtoupper($examType) === 'ACSEE' && $examYearValue && !empty($combination)) {
       $controller = app(\App\Http\Controllers\CandidateController::class);
       $reflection = new \ReflectionMethod($controller, 'registerForACSEE');
       $reflection->setAccessible(true);
       $reflection->invoke($controller, $candidate, $combination, $examYearValue);
   }
   ```

3. **Handle school lookups by code or ID**
   ```php
   $school = School::where('registration_number', $schoolCodeOrId)
       ->orWhere('code', $schoolCodeOrId)
       ->first();
   ```

### Fix 3: Add Exam Year Column to Table
- Added table header: "Exam Year"
- Added data cell: `<td class="px-3 py-2 text-sm text-gray-600 text-center" x-text="candidate.exam_year || '-'"></td>`
- Updated colspan from 10 to 11

## Flow After Fixes

### Single Candidate Registration (via modal)
1. User opens "Register Candidate" modal
2. Selects exam_year (required for ACSEE)
3. Form includes: candidate_id, full_name, gender, combination, school_id, exam_type, **exam_year**
4. POST to `/api/candidates` 
5. CandidateController.store() → registerForACSEE()
6. Creates `CandidateExamRegistration` with exam_year_id

### Bulk Candidate Import (via CSV)
1. User selects CSV file and exam year in import dialog
2. Frontend calls `/api/candidates/import/check` (validates CSV format)
3. If conflicts, shows conflict resolution modal
4. User confirms mode (skip/replace/replace-all)
5. Frontend calls `/api/candidates/import` with:
   - file: CSV
   - mode: skip|replace|replace-all
   - **exam_year**: Year selected in dialog
   - **exam_type**: Type from CSV or dialog
6. Backend:
   - Creates/updates `Candidate` records
   - For ACSEE candidates: calls `registerForACSEE()` 
   - Creates `CandidateExamRegistration` with exam_year_id
7. Table now shows exam_year value

## Verification

After import, candidates should:
- ✅ Show exam year in REGISTRATION table
- ✅ Be queryable by exam_year in MARK ENTRY
- ✅ Have `CandidateExamRegistration` records with correct exam_year_id
- ✅ Pass subject filtering in MARK ENTRY

## Files Modified
1. `resources/views/registration/candidates.blade.php`
   - Added exam_year to openAddModal() formData
   - Added exam_year to openEditModal() formData  
   - Added Exam Year column to table
   - Updated table colspan

2. `routes/api.php`
   - Updated POST /api/candidates/import validation
   - Added exam_year and exam_type parameter extraction
   - Added registerForACSEE() call for ACSEE candidates
   - Added school code/ID resolution logic
   - Added logging

## Testing Steps

1. **Manual Import Test**
   - Go to REGISTRATION → Candidates
   - Import CSV with Exam Year: 2026
   - Verify "Exam Year" column shows "2026"
   - Go to MARK ENTRY, select same school/year
   - Subjects should now appear (no "No ACSEE candidates" error)

2. **Single Entry Test**
   - Register individual ACSEE candidate for 2026
   - Verify "Exam Year" column shows "2026"
   - Verify candidate appears in MARK ENTRY

3. **Database Verification**
   ```sql
   SELECT c.candidate_id, c.exam_type, cer.exam_year_id 
   FROM candidates c
   JOIN candidate_exam_registrations cer ON c.id = cer.candidate_id
   WHERE cer.exam_year_id = (SELECT id FROM exam_years WHERE year_label = '2026')
   LIMIT 10;
   ```
