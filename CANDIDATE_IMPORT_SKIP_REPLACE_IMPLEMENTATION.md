# Candidate Import: Skip Existing + Replace Existing Implementation

**Date**: February 15, 2026  
**Status**: ✅ Complete - Ready for Testing

---

## Executive Summary

The candidate import workflow has been enhanced to support intelligent handling of existing candidates during bulk imports. Users can now choose between:

1. **SKIP** (default, safe): Don't overwrite existing candidates
2. **REPLACE** (update): Update existing candidate details (name, gender, school)

The import UI now provides clear reporting showing exactly what will happen to each row before committing.

---

## What Changed

### Backend Changes

#### 1. **CandidateImportService.php** - Validation Phase Enhancements

**New validation parameters:**
```php
public function validateCSV(
    UploadedFile $file,
    ?string $examYear = null,
    ?string $examType = null,
    string $mode = 'skip'  // NEW: 'skip' or 'replace'
): array
```

**New return fields in validation report:**
- `create_count`: Candidates to be created (new)
- `update_count`: Candidates to be updated (replace mode only)
- `skip_count`: Candidates that exist and will be skipped
- `error_count`: Genuine validation errors (not conflict-related)
- `rows`: Detailed per-row status for preview table
- `can_import`: Boolean - true if there are valid records and no errors

**Key logic change:**
- Previously: "Candidate already exists" was treated as an ERROR
- Now: "Candidate already exists" is NOT an error; it's handled via the mode:
  - If `mode='skip'`: Row status becomes `SKIP` (not error)
  - If `mode='replace'`: Row status becomes `REPLACE` (not error)
  - Only genuine validation failures are marked as `ERROR`

#### 2. **CandidateImportService.php** - Commit Phase: New `updateCandidate` Method

```php
private function updateCandidate(
    Candidate $candidate, 
    array $record, 
    ?string $examYear = null, 
    ?string $examType = null
): void
```

**Safe update strategy** (prevents data loss):
- ✓ Updates: `full_name`, `gender`, `school_id`
- ✗ Does NOT change: `candidate_id`, `exam_type`, `combination`, or exam registrations
- Reason: Changing these could orphan marks/results data

**Logs all updates** for audit trail.

#### 3. **CandidateImportService.php** - Commit Phase: Mode-Aware Processing

In `commitImport()`:
```php
// Check if candidate exists
$candidateExists = isset($existingCandidateIds[$record['candidate_id']]);

if ($candidateExists) {
    if ($mode === 'skip') {
        $skippedCount++;
        continue;
    } elseif ($mode === 'replace') {
        $existingCandidate = Candidate::where('candidate_id', $record['candidate_id'])->first();
        $this->updateCandidate($existingCandidate, $record, $examYear, $examType);
        $updatedCount++;
        continue;
    }
}
```

#### 4. **CandidateImportController.php** - Parameter Updates

**`validateImport()`:**
- Added validation for `on_exists_mode` parameter ('skip'|'replace')
- Passes mode to service

**`commitImport()`:**
- Added validation for `on_exists_mode` parameter
- Changed from `mode` → `on_exists_mode` for clarity

**`asyncBulkImport()`:**
- Updated to accept `on_exists_mode` parameter

### Frontend Changes

#### 1. **candidates.blade.php** - New State Variables

```javascript
onExistsMode: 'skip',           // 'skip' or 'replace'
showReplaceConfirmation: false, // Reserved for future confirmation dialog
```

#### 2. **candidates.blade.php** - openImportModal() Reset

```javascript
this.onExistsMode = 'skip';
this.showReplaceConfirmation = false;
```

#### 3. **candidates.blade.php** - validateImportFile() Enhancement

- Sends `on_exists_mode` in FormData
- Uses new return fields: `create_count`, `update_count`, `skip_count`, `error_count`
- Updates success message to show total records to import

#### 4. **candidates.blade.php** - commitImportFile() Enhancement

- Sends `on_exists_mode` in FormData (instead of hardcoded 'skip')

#### 5. **candidates.blade.php** - Step 1: New "If candidate already exists" UI

Added radio button options:
```html
<label>
  <input type="radio" x-model="onExistsMode" value="skip">
  Skip existing (Safe option: don't overwrite, just skip the row)
</label>
<label>
  <input type="radio" x-model="onExistsMode" value="replace">
  Replace existing (Update candidate name, gender, and school)
</label>
```

#### 6. **candidates.blade.php** - Step 2: Enhanced Summary Cards

**Old (4 cards):**
- Total Rows, Valid, Errors, Can Import

**New (6 cards, responsive grid):**
- Total Rows
- **New** (blue badge)
- **Will Update** (purple badge, shown if mode='replace')
- **Will Skip** (amber badge, shown if mode='skip')
- **Errors** (red badge)
- **Can Import** (green badge with ✓/✗)

#### 7. **candidates.blade.php** - New "Import Plan" Preview Table

After the error table, shows detailed row-by-row status:
- Row number, Candidate ID, Name, Status badge
- Status badges:
  - ✓ NEW (blue) - Will create
  - ⊘ SKIP (amber) - Will skip
  - ↻ UPDATE (purple) - Will update
  - ✗ ERROR (red) - Validation error
- Shows first 20 rows with pagination hint

#### 8. **candidates.blade.php** - Replace Mode Warning

When mode='replace' and update_count > 0, shows orange warning:
```
⚠️ Replace Mode Active
X existing candidate(s) will be updated with new names, genders, and schools.
```

#### 9. **candidates.blade.php** - Import Button Update

Button text now shows total records to import:
```
Import X Records  (where X = create_count + update_count)
```

---

## Request/Response Examples

### Phase 1: Validate with Skip Mode

**Request:**
```http
POST /api/candidates/import/validate
Content-Type: multipart/form-data

file: (CSV file)
exam_year: 2026
exam_type: ACSEE
on_exists_mode: skip
```

**Response (example with 100 rows, 30 new, 20 existing):**
```json
{
  "success": true,
  "message": "All rows valid",
  "total_rows": 100,
  "create_count": 30,
  "update_count": 0,
  "skip_count": 20,
  "error_count": 0,
  "rows": [
    {
      "row_number": 1,
      "candidate_id": "S0754-0001",
      "full_name": "JOHN DOE",
      "status": "NEW",
      "messages": []
    },
    {
      "row_number": 2,
      "candidate_id": "S0754-0002",
      "full_name": "JANE SMITH",
      "status": "SKIP",
      "messages": []
    }
  ],
  "can_import": true,
  "summary": {}
}
```

### Phase 1: Validate with Replace Mode

**Request:**
```http
POST /api/candidates/import/validate
Content-Type: multipart/form-data

file: (CSV file)
on_exists_mode: replace
```

**Response (same CSV, replace mode):**
```json
{
  "success": true,
  "total_rows": 100,
  "create_count": 30,
  "update_count": 20,
  "skip_count": 0,
  "error_count": 0,
  "rows": [
    {
      "row_number": 1,
      "candidate_id": "S0754-0001",
      "full_name": "JOHN DOE",
      "status": "NEW",
      "messages": []
    },
    {
      "row_number": 2,
      "candidate_id": "S0754-0002",
      "full_name": "JANE SMITH",
      "status": "REPLACE",
      "messages": []
    }
  ],
  "can_import": true
}
```

### Phase 2: Commit Import

**Request:**
```http
POST /api/candidates/import/commit
Content-Type: multipart/form-data

file: (CSV file)
exam_year: 2026
on_exists_mode: replace
```

**Response:**
```json
{
  "success": true,
  "message": "Imported 30 candidates, updated 20, skipped 0",
  "imported_count": 30,
  "updated_count": 20,
  "skipped_count": 0,
  "errors": []
}
```

---

## Data Safety Rules Enforced

✅ **No Destructive Deletes**
- Candidates are never deleted, only updated
- Existing relationships preserved

✅ **Safe Fields Only**
- Updates: name, gender, school
- Immutable: candidate_id, exam_type, combination, marks, results

✅ **Exam Allocations Protected**
- No changes to exam registrations, subject selections, or marks
- User can manually update exam allocation separately if needed

✅ **Transaction Safety**
- All imports wrapped in database transactions
- Rollback on any error (no partial imports)

✅ **Audit Trail**
- All updates logged to application logs
- Timestamp recorded via Laravel's `updated_at`

---

## Testing Checklist

### Basic Flow
- [ ] Open Candidates page
- [ ] Click Import CSV
- [ ] Upload file with new candidates
- [ ] Validate → shows Step 2 with preview
- [ ] Confirm counts are accurate
- [ ] Click Import
- [ ] Candidates created ✓

### Skip Mode (Default)
- [ ] Create initial candidate: ID "S0001-0001", Name "JOHN"
- [ ] Upload CSV with same ID but different name "JOHN UPDATED"
- [ ] Select Skip mode, Validate
- [ ] Verify: skip_count = 1, update_count = 0, create_count = (others)
- [ ] View "Import Plan" table: shows ⊘ SKIP badge
- [ ] Import, verify database: name still "JOHN", not updated ✓

### Replace Mode
- [ ] Create initial candidate: ID "S0001-0002", Name "JANE", Gender "F", School "S0001"
- [ ] Upload CSV with same ID but: Name "JANE UPDATED", Gender "M", School "S0002"
- [ ] Select Replace mode, Validate
- [ ] Verify: update_count = 1, skip_count = 0, create_count = (others)
- [ ] View summary: "Will Update" card shows 1
- [ ] View warning message: "Replace Mode Active - 1 existing candidate(s) will be updated..."
- [ ] View "Import Plan" table: shows ↻ UPDATE badge
- [ ] Import
- [ ] Verify database: name = "JANE UPDATED", gender = "M", school_id matches "S0002" ✓

### Error Handling
- [ ] Upload CSV with invalid candidate_id format
- [ ] Validate → error_count increments, can_import = false
- [ ] View error table with validation message
- [ ] Button disabled ✓

### Mixed Mode (New + Existing + Errors)
- [ ] Upload CSV with:
  - Row 1: New candidate (valid)
  - Row 2: Existing candidate with valid data
  - Row 3: Existing candidate with invalid school code
- [ ] Select Skip mode, Validate
- [ ] Verify: create_count = 1, skip_count = 1, error_count = 1
- [ ] Error table shows Row 3 with message
- [ ] Import Plan shows Rows 1-2 status, Row 3 marked ERROR ✓

### Boundary Cases
- [ ] Empty file → error
- [ ] CSV with only errors → can_import = false
- [ ] CSV with mixed encoding → should work if valid
- [ ] Large file (100+ rows) → pagination in preview works ✓

---

## Backward Compatibility

✅ **Fully backward compatible**
- Old imports still work (default mode='skip')
- Old code calling validateCSV() without mode param gets mode='skip' by default
- Response includes new fields; old code ignores unknown fields
- No database schema changes required

---

## Files Modified

1. `app/Services/Candidates/CandidateImportService.php`
   - validateCSV() signature updated with mode parameter
   - commitImport() updated to handle update path
   - New updateCandidate() method added
   - Return value restructured: create_count, update_count, skip_count, error_count

2. `app/Http/Controllers/CandidateImportController.php`
   - validateImport() updated to accept on_exists_mode parameter
   - commitImport() updated to accept on_exists_mode parameter
   - asyncBulkImport() updated to accept on_exists_mode parameter

3. `resources/views/registration/candidates.blade.php`
   - New state variables: onExistsMode, showReplaceConfirmation
   - openImportModal() enhanced
   - validateImportFile() enhanced
   - commitImportFile() enhanced
   - Step 1 UI: Added radio options for skip/replace
   - Step 2 UI: Enhanced summary cards, new import plan table
   - Warning message for replace mode
   - Button text now shows (create_count + update_count)

---

## Deployment Notes

**Database Migration Required?** No - schema unchanged

**Configuration Required?** No

**Queue/Jobs?** Existing async job (ProcessCandidateBulkImport) handles new mode param automatically

**Rollback Plan:** Changes are backward compatible. If issues arise, set on_exists_mode='skip' as default in form submission.

---

## Future Enhancements

- [ ] Add "Replace with confirmation" mode with row-by-row approval UI
- [ ] Add more fields to update in Replace mode (DOB, candidate_type, combination)
- [ ] Add "Merge" mode combining data from both records
- [ ] Store import batch audit log with counts and changes
- [ ] Email notification after import with summary report
- [ ] Undo import feature with rollback to pre-import state

---

## Support & Documentation

**For Users:**
- Skip mode is safest - use when you want to import only new candidates
- Replace mode is useful for correcting candidate info (name typos, school changes)
- Always review the Import Plan table before clicking Import
- Error messages are specific - fix errors in your CSV and retry

**For Developers:**
- Mode parameter flows through: Controller → Service validate → Service commit
- Key decision point: existence check in validateCSV() (line ~124)
- Safe update enforced by updateCandidate() method
- All business logic in service; controller is thin
