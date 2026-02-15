# NECTA ACSEE Restructure - Quick Reference Guide

**Created:** 2026-02-15  
**For:** Developers implementing the restructure

---

## TL;DR

We're adding support for PRIVATE ACSEE candidates alongside SCHOOL candidates.

**What changes:**
- Add `candidate_type` field (SCHOOL or PRIVATE)
- Add `is_principal`, `source`, `created_by` fields for tracking
- Create validation service for NECTA rules
- Update registration form & ACSEE page UI

**What doesn't change:**
- Existing SCHOOL candidates (backward compatible)
- Existing marks/results (no impact)
- Existing API endpoints (still work)

**Timeline:** 10-15 days

---

## DATABASE CHANGES

### New Columns (Run Migration)
```sql
-- candidates table
ADD candidate_type ENUM('SCHOOL', 'PRIVATE') DEFAULT 'SCHOOL'
ADD combination_id INT NULLABLE FK(combinations)

-- candidate_subject_selections table
ADD is_principal BOOLEAN DEFAULT FALSE
ADD source ENUM('manual', 'import', 'template') DEFAULT 'template'
ADD created_by INT NULLABLE FK(users)
```

### What This Means
```
SCHOOL Candidate:
  - candidate_type = 'SCHOOL'
  - combination_id = references combination template
  - Subjects auto-attached: is_principal=FALSE, source='template'

PRIVATE Candidate:
  - candidate_type = 'PRIVATE'
  - combination_id = NULL (optional)
  - Subjects manually selected: is_principal=TRUE/FALSE, source='manual'
```

---

## MODEL CHANGES

### Candidate Model
```php
// New fields
protected $fillable = [
    // ... existing fields
    'candidate_type',      // NEW
    'combination_id',      // NEW
];

// New relationships
public function combinationRelation() {
    return $this->belongsTo(Combination::class, 'combination_id');
}

// New helpers
public function isSchool(): bool { return $this->candidate_type === 'SCHOOL'; }
public function isPrivate(): bool { return $this->candidate_type === 'PRIVATE'; }
```

### CandidateSubjectSelection Model
```php
// New fields
protected $fillable = [
    // ... existing fields
    'is_principal',   // NEW
    'source',         // NEW
    'created_by',     // NEW
];

// New relationship
public function creator() {
    return $this->belongsTo(User::class, 'created_by');
}

// New scopes
public function scopePrincipal($query) {
    return $query->where('is_principal', true);
}

public function scopeBySource($query, $source) {
    return $query->where('source', $source);
}
```

---

## VALIDATION RULES

### ACSEE Validation Service
```php
$validator = new AcseeRegistrationValidator();
$result = $validator->validate($candidate);

// Returns:
$result->valid                    // true/false
$result->errors                   // Array of error messages
$result->warnings                 // Array of warnings
$result->principals_count         // Count of is_principal=true
$result->subjects_count           // Total subjects allocated
```

### Validation Rules (All Required)
```
1. Minimum 3 principal subjects (is_principal = true)
2. General Studies (GS) is mandatory
3. No duplicate subjects per candidate
4. Maximum 8 subjects
5. No subject conflicts (configurable)
```

### Usage
```php
// Before saving
$validator = new AcseeRegistrationValidator();
if (!$validator->canRegister($candidate)) {
    $errors = $validator->getErrors($candidate);
    return response()->json(['errors' => $errors], 422);
}

// Or get all info
$result = $validator->validate($candidate);
if (!$result->valid) {
    Log::error('Invalid allocation', $result->errors);
}
```

---

## REGISTRATION FLOW

### SCHOOL Candidate (Unchanged)
```
User fills registration form:
  - Candidate Type: SCHOOL
  - School: [required dropdown]
  - Combination: [required dropdown]
  - Candidate ID, Name, Gender, DOB
  
System:
  ✓ Create candidate (candidate_type='SCHOOL')
  ✓ Auto-attach combination subjects
  ✓ Set is_principal=FALSE, source='template'
  ✓ Validate ACSEE rules
  ✓ Show success
```

### PRIVATE Candidate (New)
```
User fills registration form:
  - Candidate Type: PRIVATE
  - School: [optional or disabled]
  - Combination: [optional, for reference]
  - Candidate ID, Name, Gender, DOB
  
System:
  ✓ Create candidate (candidate_type='PRIVATE')
  ✓ NO auto-attach subjects
  ✓ Redirect to subject allocator
  
User allocates subjects:
  - Select subjects from list
  - Mark 3+ as "Principal"
  - Ensure General Studies included
  - View real-time validation
  ✓ Save subjects
  ✓ Set is_principal=TRUE/FALSE, source='manual', created_by=user_id
  ✓ Validate ACSEE rules
  ✓ Show success
```

---

## CSV IMPORT

### Updated Template
```csv
candidate_id,full_name,gender,candidate_type,combination,school_code,exam_type,exam_year
S1234-001,John Doe,M,SCHOOL,PCM,S1234,ACSEE,2026
P5678-001,Jane Smith,F,PRIVATE,,AUTO,ACSEE,2026
```

### Processing Logic
```php
// For SCHOOL + combination
if ($row['candidate_type'] === 'SCHOOL' && $row['combination']) {
    // Auto-attach subjects from combination
    // source = 'import'
}

// For PRIVATE with allocated_subjects
if ($row['candidate_type'] === 'PRIVATE' && $row['allocated_subjects']) {
    // Validate and attach subjects
    // source = 'import'
}
```

### Import Report
```
✓ Processed: 100 records
✓ Created: 85 new candidates
⚠ Updated: 10 existing candidates
⚠ Skipped: 5 duplicates

Allocation Status:
  ✓ SCHOOL auto-attached: 85
  ⚠ PRIVATE need manual allocation: 5 (see list below)
  
Errors:
  - P5678-002: Missing General Studies
  - P5678-003: Only 2 principal subjects
```

---

## ACSEE PAGE ENHANCEMENTS

### Candidates Tab
```
New columns:
  - Index Number
  - Full Name
  - Sex
  - Combination (if applicable)
  - Allocated Subjects
  - School
  → Allocation Status ← NEW

New actions:
  → "Allocate Subjects" button (PRIVATE candidates only)
```

### Status Indicators
```
✓ Valid        - Passes all NECTA rules
✗ Invalid      - Has validation errors
⚠ Incomplete   - Needs manual allocation
```

### Allocate Subjects Modal
```
Shows for PRIVATE candidates:
  ✓ List all ACSEE subjects
  ✓ Checkboxes to select
  ✓ Radio/toggle to mark principal
  ✓ Real-time validation feedback
  ✓ Error messages if rules violated
  ✓ Save button (enabled only if valid)
```

---

## CONTROLLER ENDPOINTS (NEW)

### Subject Allocation
```
GET  /api/candidates/{id}/allocation
     Returns current allocation + validation result

POST /api/candidates/{id}/allocation
     Save new allocation
     Body: { subject_ids: [...], principal_ids: [...] }
     
PUT  /api/candidates/{id}/allocation
     Update allocation

DELETE /api/candidates/{id}/allocation/{subjectId}
     Remove subject
```

### Example
```php
// Save PRIVATE candidate subjects
POST /api/candidates/42/allocation
{
  "subject_ids": [10, 11, 12, 13],
  "principal_ids": [10, 11, 12]
}

Response:
{
  "success": true,
  "validation": {
    "valid": true,
    "principals_count": 3,
    "subjects_count": 4,
    "errors": [],
    "warnings": []
  }
}
```

---

## TESTING CHECKLIST

### Unit Tests
```php
// Models
CandidateTest::test_candidate_type_school_default()
CandidateTest::test_candidate_type_private()
CandidateSubjectSelectionTest::test_principal_scope()

// Services
AcseeValidatorTest::test_validate_minimum_principals()
AcseeValidatorTest::test_validate_general_studies_required()
```

### Feature Tests
```php
// Registration
RegisterSchoolCandidateTest::test_register_with_combination()
RegisterPrivateCandidateTest::test_register_and_redirect_to_allocation()

// Allocation
SubjectAllocationTest::test_allocate_with_validation()
SubjectAllocationTest::test_invalid_allocation_rejected()

// Import
ImportTest::test_import_school_candidates()
ImportTest::test_import_private_candidates()
```

### Manual Testing
```
✓ Register SCHOOL candidate → Auto-attach works
✓ Register PRIVATE candidate → Allocation modal opens
✓ Allocate < 3 principals → Error message shows
✓ Allocate without GS → Error message shows
✓ Fix errors → Save enabled
✓ Save subjects → Status updated to ✓ Valid
✓ CSV import → Report shows allocation status
✓ Existing SCHOOL candidate → Still works
```

---

## COMMON TASKS

### Check if Candidate is SCHOOL
```php
$candidate = Candidate::find(1);
if ($candidate->isSchool()) {
    // Handle SCHOOL logic
}
```

### Check if Candidate is PRIVATE
```php
if ($candidate->isPrivate()) {
    // Handle PRIVATE logic
}
```

### Get Principal Subjects
```php
$principals = $candidate->subjectSelections()
    ->principal()
    ->get();
```

### Get Manually Allocated Subjects
```php
$manual = $candidate->subjectSelections()
    ->bySource('manual')
    ->get();
```

### Get Subject Allocator
```php
$allocator = $candidate->subjectSelections()
    ->where('source', 'manual')
    ->first()?->creator;  // Get the user who allocated
```

### Validate Candidate
```php
$validator = new AcseeRegistrationValidator();
$result = $validator->validate($candidate);

if (!$result->valid) {
    foreach ($result->errors as $error) {
        // Display error to user
    }
}
```

### Mark Subject as Principal
```php
$selection = $candidate->subjectSelections()
    ->where('subject_id', 10)
    ->first();

$selection->update(['is_principal' => true]);
```

### Track Allocation Source
```php
// Check how subjects were allocated
$byTemplate = $candidate->subjectSelections()
    ->where('source', 'template')
    ->count();

$byManual = $candidate->subjectSelections()
    ->where('source', 'manual')
    ->count();

$byImport = $candidate->subjectSelections()
    ->where('source', 'import')
    ->count();
```

---

## MIGRATION COMMANDS

### Run Migrations
```bash
php artisan migrate
# Adds: candidate_type, combination_id, is_principal, source, created_by
```

### Check Migration Status
```bash
php artisan migrate:status
# Shows which migrations have run
```

### Rollback if Needed
```bash
php artisan migrate:rollback
# Removes the NECTA columns (if something goes wrong)
```

### Fresh Migration (Dev Only)
```bash
php artisan migrate:fresh
# Resets ALL migrations (dangerous in production!)
```

---

## DEBUGGING

### Check Candidate Type
```php
DB::table('candidates')->select('id', 'full_name', 'candidate_type')->get();
```

### Check Subject Allocations
```php
DB::table('candidate_subject_selections')
    ->select('candidate_id', 'subject_id', 'is_principal', 'source', 'created_by')
    ->where('candidate_id', 42)
    ->get();
```

### Validate All Candidates
```php
$validator = new AcseeRegistrationValidator();
Candidate::where('exam_type', 'ACSEE')->chunk(100, function($candidates) use ($validator) {
    foreach ($candidates as $candidate) {
        $result = $validator->validate($candidate);
        if (!$result->valid) {
            Log::warning("Invalid candidate", [
                'candidate_id' => $candidate->id,
                'errors' => $result->errors,
            ]);
        }
    }
});
```

### Check Migration Applied
```bash
php artisan tinker
>>> DB::table('candidates')->first();  // Check for new columns
>>> DB::table('candidate_subject_selections')->first();
```

---

## FILES REFERENCE

| File | Purpose |
|------|---------|
| `NECTA_ACSEE_RESTRUCTURE_ANALYSIS.md` | Detailed technical analysis |
| `NECTA_IMPLEMENTATION_CHECKLIST.md` | Step-by-step implementation |
| `NECTA_RESTRUCTURE_SUMMARY.md` | Executive summary |
| `NECTA_QUICK_REFERENCE.md` | This file (quick lookup) |
| `database/migrations/2026_02_15_add_necta_alignment_columns.php` | Migration file |
| `app/Services/AcseeRegistrationValidator.php` | Validation service |

---

## KEY CONTACTS

- **Architecture & Analysis:** Amp
- **Implementation Lead:** [Assign]
- **QA Lead:** [Assign]
- **DevOps/Deployment:** [Assign]

---

## IMPORTANT REMINDERS

⚠️ **Before Running Migration:**
```
1. Backup database
2. Test in staging first
3. Have rollback plan ready
```

✓ **After Running Migration:**
```
1. Clear caches
2. Test SCHOOL candidate registration
3. Test PRIVATE candidate registration
4. Check existing marks/results still work
5. Monitor logs for errors
```

⚡ **Remember:**
```
- All changes are backward compatible
- Existing SCHOOL candidates work unchanged
- Rollback is available if needed
- No data loss (only new columns)
- New features are opt-in
```

---

## FREQUENTLY ASKED QUESTIONS

**Q: Do I need to update existing candidates?**  
A: No. They automatically get `candidate_type='SCHOOL'` and continue working.

**Q: Can I change a candidate type after creation?**  
A: Yes, but update `candidate_type` and possibly subjects accordingly.

**Q: What if a PRIVATE candidate wants to use a combination?**  
A: They can, but manually allocate the subjects. Combination is just a reference.

**Q: How do I know if validation passed?**  
A: Check `$result->valid` boolean and `$result->errors` array.

**Q: Can subjects be re-allocated?**  
A: Yes. Delete old selections and create new ones with new source/created_by.

**Q: Where can I see who allocated subjects?**  
A: Check `candidate_subject_selections.created_by` FK to users table.

---

**Last Updated:** 2026-02-15  
**Status:** Ready for implementation  
**Questions:** Refer to full analysis document or contact architecture team
