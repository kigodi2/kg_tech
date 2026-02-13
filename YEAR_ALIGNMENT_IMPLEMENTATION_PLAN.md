# Year-Based Data Alignment Implementation Plan

## Overview
Fix year-based data alignment in ACSEE mark entry by enforcing explicit exam-year registrations and preventing silent empty states.

## Current State Analysis

### Issues Identified
1. **No explicit exam_year_id in candidate registrations** - Using loose `year` column (integer) instead of foreign key
2. **Silent fallback to previous years** - Subject queries may return stale data
3. **No clear empty-state messaging** - Admin can't distinguish "no registrations" from "no matching data"
4. **Year enforcement missing** - Both candidate registration and subject selection need exam_year_id FK
5. **Mark entry UI lacks year-aware validation** - No check for whether year has registrations

### Current Flow
- Mark entry page accepts: exam_year (number), school_id, subject_id
- Subject filter query chains: Candidate → CandidateExamRegistration (year) → SubjectSelection (year) → Subject
- **Problem**: Uses loose `year` integer instead of exam_year_id FK reference

## Implementation Steps

### 1. Database Migration: Add exam_year_id to Registration Tables
**Status**: Ready to implement
**Files to create**: `database/migrations/2026_02_01_xxxxx_enforce_exam_year_relationships.php`

**Changes**:
- Add `exam_year_id` FK to `candidate_exam_registrations`
- Add `exam_year_id` FK to `candidate_subject_selections`
- Add CHECK constraint: `year == exam_year.year_label`
- Add compound indexes: (exam_year_id, candidate_id), (exam_year_id, subject_id)
- Drop `year` column (optional, but cleaner)
- Backfill: Link existing year integers to exam_year records

### 2. Model Updates: Enforce Year Relationships
**Files**:
- `app/Models/CandidateExamRegistration.php` - Add exam_year relation
- `app/Models/CandidateSubjectSelection.php` - Add exam_year relation
- `app/Models/ExamYear.php` - Ensure relationships are correct

### 3. Service Updates: Year-Aware Subject Filtering
**Files**: `app/Services/MarkImport/SubjectFilterService.php`

**Changes**:
- Update `getSubjectsBySchoolAndYear()` to use exam_year_id FK instead of loose year
- Add validation: `examYear.is_locked == false` (prevent editing locked years)
- Return empty set gracefully if year has no registrations
- Add new method: `validateYearHasRegistrations()` for guardrails

### 4. Controller Updates: Year Validation Guardrails
**Files**: `app/Http/Controllers/MarkEntryController.php`

**Changes**:
- `getSubjectsBySchoolAndYear()` - Add backend validation for year + school combo
- Return meaningful 422 error if: no candidates, year is locked, invalid year
- Cache key update: Include exam_year_id instead of loose year

### 5. Frontend: Year-Aware State Management
**Files**: `resources/views/mark-entry/index.blade.php`

**Changes**:
- Store examYear in Alpine session
- Add visual indicator: Year status (active/locked/draft)
- Show warning banner when no registrations exist
- Disable subject dropdown with reason message
- Add "Register ACSEE Candidates" CTA for admins

### 6. Candidate Registration Flow
**Files**: `app/Http/Controllers/CandidateController.php`

**Changes**:
- Make exam_year_id mandatory for ACSEE registration
- Add validation: exam_year must exist and not be locked
- Update `registerForACSEE()` to require exam_year_id parameter
- Create registration with: candidate_id + exam_type_id + exam_year_id

### 7. Validation Rules & Guards
**Files**: Create `app/Services/ExamYear/ExamYearValidationService.php`

**Methods**:
- `canRegisterForYear(examYear)` - Check if locked, active, etc.
- `validateSubjectForYear(subject, examYear)` - Verify subject is valid for year
- `validateCandidateForYear(candidate, examYear)` - Verify registration exists
- Return validation errors with business context

### 8. Optional: Legacy Data Alignment Command
**Files**: Create `app/Console/Commands/AlignLegacyACSEEYear.php`

**Purpose**: One-time command to assign legacy candidates to selected year
**Behavior**:
- Requires explicit year selection (no auto-assign)
- Prompts for confirmation before execution
- Logs affected records (audit trail)
- Generates report of assigned candidates

### 9. Artisan Routes Update
**Files**: `routes/api.php`

**Changes**:
- Add `/api/mark-entry/acsee/exam-year-status` - Returns active year + status
- Update `/api/mark-entry/acsee/subjects-by-school` - Validate year first

## Implementation Order

1. ✅ Create migration for exam_year_id FKs
2. ✅ Update models with relationships
3. ✅ Create validation service
4. ✅ Update SubjectFilterService queries
5. ✅ Update MarkEntryController guardrails
6. ✅ Update CandidateController registration flow
7. ✅ Update mark entry view (Alpine.js state)
8. ✅ Create legacy alignment command
9. ✅ Add documentation & inline comments

## Key Constraints (MUST ENFORCE)

❌ Do NOT auto-assign years
❌ Do NOT fallback to previous years
❌ Do NOT remove year isolation
✅ Require explicit year selection
✅ Show clear validation errors
✅ Maintain NECTA audit requirements
✅ Keep zero silent failures

## Testing Checklist

- [ ] Candidate registration requires exam_year_id
- [ ] Subject filter respects exam_year_id FK
- [ ] Empty year shows meaningful message
- [ ] Locked year is read-only in UI
- [ ] Migration handles legacy data correctly
- [ ] Cache invalidation on year change
- [ ] API returns 422 for invalid year combos
- [ ] Legacy command execution is safe

## Files Modified

### Database
- `database/migrations/2026_02_01_xxxxx_enforce_exam_year_relationships.php` (NEW)

### Models
- `app/Models/CandidateExamRegistration.php` (MODIFIED)
- `app/Models/CandidateSubjectSelection.php` (MODIFIED)
- `app/Models/ExamYear.php` (VERIFIED)

### Services
- `app/Services/MarkImport/SubjectFilterService.php` (MODIFIED)
- `app/Services/ExamYear/ExamYearValidationService.php` (NEW)

### Controllers
- `app/Http/Controllers/MarkEntryController.php` (MODIFIED)
- `app/Http/Controllers/CandidateController.php` (MODIFIED)

### Console
- `app/Console/Commands/AlignLegacyACSEEYear.php` (NEW)

### Views
- `resources/views/mark-entry/index.blade.php` (MODIFIED)

### Routes
- `routes/api.php` (MODIFIED)

---
**Estimated Implementation Time**: 4-6 hours
**Risk Level**: Medium (data integrity + migration)
**Rollback Plan**: Reverse migration + cache clear
