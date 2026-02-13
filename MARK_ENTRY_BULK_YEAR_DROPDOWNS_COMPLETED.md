# Mark Entry Year Dropdowns - Bulk Import Sections

**Date**: 2026-02-04
**Objective**: Convert free-form Year number inputs to dropdowns in all bulk import sections for consistency and data integrity

## Changes Made

### 1. School Bulk ZIP Section (resources/views/mark-entry/index.blade.php)

**Lines 541-553**: Converted Exam Year field from number input to dropdown
- Old: `<input type="number" ... placeholder="e.g., 2026">`
- New: `<select x-model.number="schoolBulkExamYear">` with examYears loop

**Lines 1540-1555**: Updated `startSchoolBulkImport()` function
- Added year_label to exam_year_id conversion
- Validates exam year exists before sending to API
- Prevents invalid exam year submissions

### 2. District Bulk ZIP Section (resources/views/mark-entry/index.blade.php)

**Lines 747-752**: Normalized Exam Year dropdown format
- Changed from `year.id` to `year.year_label` for consistency
- Updated display text from `${year.year} (${year.year_label})` to just `year.year_label`
- Added height class for proper alignment

**Lines 1648-1664**: Updated `startDistrictImport()` function
- Added year_label to exam_year_id conversion
- Validates exam year exists before sending to API
- Prevents invalid exam year submissions

## Implementation Details

### Year Label to ID Conversion

Both bulk import functions now convert the displayed year_label to exam_year_id:

```javascript
const examYearId = this.examYears.find(y => y.year_label == this.schoolBulkExamYear)?.id;
if (!examYearId) {
    this.showMessage('Invalid exam year selected', 'error');
    return;
}
```

This ensures the API receives the correct integer ID while the user sees readable year labels.

### API Compatibility

- Backend APIs expect: `exam_year_id` as integer
- Frontend now sends: Converted integer ID from year_label
- No backend changes needed - APIs remain compatible

## Consistency Across All Sections

All three Mark Entry sections now follow the same pattern:

| Section | Field | Type | Source | Value Sent |
|---------|-------|------|--------|-----------|
| Select Context | Year | Dropdown | examYears | year_label (string) |
| School Bulk ZIP | Exam Year | Dropdown | examYears | year_label + conversion to ID |
| District Bulk ZIP | Exam Year | Dropdown | examYears | year_label + conversion to ID |

## Files Modified

- `routes/web.php` - Added `/api/exam-years/with-acsee` endpoint (previous work)
- `app/Models/ExamYear.php` - Added `candidateExamRegistrations()` relationship (previous work)
- `resources/views/mark-entry/index.blade.php` - Updated all three Year fields (this work)

## Testing Steps

1. Go to Mark Entry → ACSEE
2. **Single School CSV Tab**: Year should be dropdown (not changed, was already fine)
3. **School Bulk ZIP Tab**: 
   - Exam Year shows dropdown with "2026" option
   - School dropdown enables only after selecting exam year
   - Upload works with dropdown selection
4. **District Bulk ZIP Tab**:
   - Exam Year shows dropdown with "2026" option
   - District dropdown populates after exam year selection
   - Upload works with dropdown selection

## Status

✅ **COMPLETED** - All bulk import sections now use consistent, validated Year dropdowns
