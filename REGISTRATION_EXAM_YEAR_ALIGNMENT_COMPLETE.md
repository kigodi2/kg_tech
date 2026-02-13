# Registration-MarkedEntry Exam Year Alignment - Complete Implementation

## Problem Statement
REGISTRATION and MARK ENTRY pages weren't aligned for ACSEE candidates:
- Candidates imported in REGISTRATION didn't appear in MARK ENTRY
- Error: "No ACSEE candidates registered for 2026 in this school"
- Exam Year column showed "-" instead of the selected year

## Root Causes
1. Frontend formData missing `exam_year` field
2. API import endpoint (`/api/candidates/import`) ignored exam_year parameter
3. API candidates endpoint didn't load exam registration data
4. Candidate model didn't expose exam_year in JSON responses
5. Database table missing `exam_year_id` foreign key
6. Existing candidates had no exam registrations

## Fixes Applied

### 1. Frontend Form Fixes
**File:** `resources/views/registration/candidates.blade.php`

- Added `exam_year: ''` to `openAddModal()` formData initialization (line 858)
- Added `exam_year: candidate.exam_year || ''` to `openEditModal()` (line 875)
- Added "Exam Year" column to candidates table (header line 219, data line 241)
- Updated table colspan from 10 to 11

### 2. API Endpoint Enhancements
**File:** `routes/api.php`

#### GET /api/candidates (line 40)
- Added eager-load: `.with('examRegistrations.examYear')`
- Now returns exam_year in candidate response

#### POST /api/candidates/import (line 210)
- Added validation for `exam_year` and `exam_type` parameters
- Calls `registerForACSEE()` for ACSEE candidates
- Creates `CandidateExamRegistration` with correct `exam_year_id`
- Handles school lookup by code or ID

### 3. Model Enhancements
**File:** `app/Models/Candidate.php`

- Added `protected $appends = ['exam_year'];`
- Added `getExamYearAttribute()` accessor that:
  - Extracts year_label from eager-loaded exam registrations (if available)
  - Falls back to database query if relationship not loaded
  - Returns null for candidates without registrations

### 4. Database Schema Migration
**File:** `database/migrations/2026_02_04_000001_add_exam_year_id_to_candidate_exam_registrations.php`

- Added `exam_year_id` foreign key to `candidate_exam_registrations` table
- Links to `exam_years` table for proper data integrity

### 5. Data Remediation
**File:** `fix_missing_exam_registrations.php`

- Script that creates missing `CandidateExamRegistration` records
- Handles candidates imported before exam_year fix
- Usage: `php fix_missing_exam_registrations.php <exam_year> [school_id]`
- Result: Created 84 registrations for KLERRUU TEACHERS COLLEGE 2026

### 6. Model Fillable Updates
**File:** `app/Models/CandidateExamRegistration.php`

- Updated `$fillable` to include `exam_year_id` and `status`
- Removed non-existent columns: `is_verified`, `verification_date`, `is_active`

## Data Flow After Fixes

### Manual Candidate Registration
1. User goes to REGISTRATION → Candidates → "Register Candidate"
2. Fills form including **Exam Year** (required for ACSEE)
3. POST to `/api/candidates` with all form data including `exam_year`
4. CandidateController.store() creates Candidate record
5. Calls `registerForACSEE()` which:
   - Creates `CandidateExamRegistration` with `exam_year_id`
   - Creates `CandidateSubjectSelection` records
6. User sees **"2026"** in Exam Year column

### Bulk CSV Import
1. User goes to REGISTRATION → Candidates → Tools → "Import CSV"
2. Modal opens with **Exam Year** dropdown
3. User selects year (e.g., "2026") and clicks "Select File"
4. File picker opens, user selects CSV
5. Frontend calls `/api/candidates/import` with:
   - file: CSV data
   - mode: skip|replace|replace-all
   - exam_year: 2026
   - exam_type: ACSEE
6. Backend creates candidates and calls `registerForACSEE()`
7. Result: All candidates show **"2026"** in Exam Year column

### Mark Entry Access
1. User goes to MARK ENTRY
2. Selects: Region → District → School (KLERRUU) → Year (2026)
3. API calls `/mark-entry/get-subjects-by-school-and-year`
4. Validation service checks: "Are there ACSEE candidates registered for school X in year 2026?"
5. **Query:** `CandidateExamRegistration` where `exam_year_id = 1` and school = KLERRUU
6. **Result:** ✅ Finds 84 candidates
7. Returns available subjects
8. User can enter marks

## Verification Steps

### 1. Check Candidates Table
- Go to REGISTRATION → Candidates
- Verify "Exam Year" column shows "2026"
- ✅ Should display year instead of "-"

### 2. Check Database
```sql
-- Candidates with exam registrations
SELECT c.candidate_id, c.exam_type, ey.year_label 
FROM candidates c
JOIN candidate_exam_registrations cer ON c.id = cer.candidate_id
JOIN exam_years ey ON cer.exam_year_id = ey.id
WHERE c.school_id = 29 AND ey.year_label = '2026'
LIMIT 10;

-- Expected: Shows 84 rows for KLERRUU TEACHERS COLLEGE
```

### 3. Check Mark Entry
- Go to MARK ENTRY
- Select: IRINGA → IRINGA MC → KLERRUU TEACHERS COLLEGE → 2026
- ✅ Should show "Subjects shown are based on 84 registered ACSEE candidate(s)"
- ✅ Should list subjects (CBE, etc.) instead of error

### 4. Test New Import
- Go to REGISTRATION → Candidates → Tools → Import CSV
- Select exam year 2026
- Upload CSV with ACSEE candidates
- ✅ Should import and show exam year in column

## Files Modified Summary
1. `resources/views/registration/candidates.blade.php` - Form, table, import logic
2. `routes/api.php` - API endpoints for candidates and import
3. `app/Models/Candidate.php` - Added exam_year accessor
4. `app/Models/CandidateExamRegistration.php` - Updated fillable
5. `database/migrations/2026_02_04_000001_add_exam_year_id_to_candidate_exam_registrations.php` - Schema migration
6. `fix_missing_exam_registrations.php` - Data remediation script

## Known Limitations
- If import CSV doesn't specify exam_type, uses exam_type from dialog or defaults to value in CSV
- Existing registrations created before migration have `exam_year_id = NULL` (need to run fix script)
- import CSV button requires exam year selection before file picker (by design)

## Next Steps if Issues Persist
1. Check browser console for JavaScript errors
2. Verify `/api/exam-years` returns data with `year_label` field
3. Verify `exam_year_id` column exists in database (check migration was run)
4. Run fix script if candidates show "-" in Exam Year column
5. Clear browser cache and refresh page

