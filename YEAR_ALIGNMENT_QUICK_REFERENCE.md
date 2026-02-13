# Year Alignment Quick Reference

## What Changed

| Component | Change | Impact |
|-----------|--------|--------|
| Database | Added `exam_year_id` FK to registration tables | Strict year isolation |
| SubjectFilterService | All queries use `exam_year_id` FK | No fallback to previous years |
| MarkEntryController | Returns 422 on locked/empty years | Clear validation feedback |
| CandidateController | Requires exam_year in registration | Explicit year selection |
| Frontend | Shows year status, handles 422 errors | Better UX for empty states |
| Artisan | New legacy alignment command | Safe one-time data migration |

## Deployment

```bash
# 1. Pull code
git pull origin main

# 2. Run migration
php artisan migrate

# 3. Clear caches
php artisan cache:clear

# 4. Test
curl "http://localhost/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1"
```

## Testing Mark Entry

**Scenario 1: Normal (subjects show)**
```
Year: 2024
School: Has registered ACSEE candidates
Expected: Subject dropdown populated
```

**Scenario 2: No candidates**
```
Year: 2024
School: No registered candidates
Expected: 422 response, yellow warning message
```

**Scenario 3: Locked year**
```
Year: 2023 (locked)
Any school
Expected: 422 response, red lock icon message
```

## Error Codes

| Code | Meaning | Status | Action |
|------|---------|--------|--------|
| `ELIGIBLE` | Candidate can register | 200 | Proceed |
| `ALLOWED` | Mark entry OK | 200 | Proceed |
| `AVAILABLE` | Subject available | 200 | Proceed |
| `YEAR_LOCKED` | Year is locked | 422 | Show lock icon, disable UI |
| `NO_CANDIDATES` | No registrations | 422 | Show yellow message |
| `INVALID_YEAR` | Year not found | 422 | Show error |
| `ALREADY_REGISTERED` | Already registered | Thrown | Skip (idempotent) |

## Common Queries

```php
// Get subjects for mark entry
app(SubjectFilterService::class)->getSubjectsBySchoolAndYear($schoolId, $examYear);

// Validate before operation
app(ExamYearValidationService::class)->validateMarkEntry($schoolId, $examYear);

// Register candidate
// $this->registerForACSEE($candidate, 'PCM', $examYear); // in CandidateController

// Get registrations for year
CandidateExamRegistration::where('exam_year_id', $examYear->id)->get();
```

## Frontend Changes

### Changed Endpoints
- GET `/api/mark-entry/acsee/subjects-by-school`
  - Now validates year first
  - Returns 422 on locked/empty years
  - Includes `code` field for error handling

### UI States
- `yearIsLocked` - Set from 422 response code
- `subjectFilterMessage` - Updated from response
- Shows lock icon when `yearIsLocked === true`
- Disables subject dropdown when no candidates

### Error Handling
```javascript
if (response.status === 422) {
    if (data.code === 'YEAR_LOCKED') {
        this.yearIsLocked = true;
    }
    // Show message from data.message
}
```

## Key Files

| File | Purpose |
|------|---------|
| `database/migrations/2026_02_01_enforce_exam_year_relationships.php` | Add FK columns |
| `app/Services/ExamYear/ExamYearValidationService.php` | Business logic validation |
| `app/Services/MarkImport/SubjectFilterService.php` | Year-aware subject queries |
| `app/Http/Controllers/MarkEntryController.php` | Add validation to API |
| `app/Http/Controllers/CandidateController.php` | Accept exam_year in registration |
| `app/Console/Commands/AlignLegacyACSEEYear.php` | Legacy data alignment |
| `resources/views/mark-entry/index.blade.php` | Enhanced UI |

## Troubleshooting

### "No subjects showing even though candidates exist"
- Check: Is `exam_year_id` backfilled in migrations?
- Test: `CandidateExamRegistration::first()->exam_year_id`
- Fix: Run `php artisan acsee:align-legacy-year` if needed

### "422 error but year should be valid"
- Check: Is year locked? (`ExamYear::find(id)->is_locked`)
- Check: Do candidates exist? (Query `candidate_exam_registrations` by exam_year_id)
- Check: Query logs for error messages

### "UI not updating on year change"
- Check: Browser console for JS errors
- Check: Response status code (should be 200 or 422)
- Check: `loadFilteredSubjects()` is being called on year change

### "Legacy command not working"
- Run: `php artisan acsee:align-legacy-year` (interactive)
- Watch: Preview of affected records before confirmation
- Check: `exam_year_audit_logs` table for operation record

## Before Going Live

- [ ] Run migration: `php artisan migrate`
- [ ] Clear cache: `php artisan cache:clear`
- [ ] Test mark entry endpoint (200 and 422 scenarios)
- [ ] Test candidate registration with explicit year
- [ ] Test UI with locked year (should show lock icon)
- [ ] Verify audit logs created
- [ ] Check error logs for any warnings

## Key Constraints (Don't Break These)

❌ **Never:**
- Auto-assign years silently
- Fallback to previous years
- Allow modification of locked years
- Mix candidates across years

✅ **Always:**
- Validate year before operation
- Return meaningful error messages
- Require explicit year selection
- Log all year-based operations
- Show clear UI indicators

## API Response Format

**Success (200):**
```json
{
  "success": true,
  "data": [...subjects],
  "has_candidates": true,
  "candidate_count": 5,
  "message": "Subjects shown are based on 5 registered ACSEE candidate(s)..."
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "data": [],
  "has_candidates": false,
  "candidate_count": 0,
  "message": "No ACSEE candidates registered for 2024...",
  "code": "NO_CANDIDATES"
}
```

---

**Need more details?** See `YEAR_ALIGNMENT_IMPLEMENTATION_GUIDE.md`
