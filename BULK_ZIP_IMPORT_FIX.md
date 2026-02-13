# Bulk ZIP Import 422 Error - Fixed

## Problem
The bulk ZIP import was failing with a **422 Unprocessable Content** error. This occurred because the frontend code was sending a hardcoded `exam_year_id: 1` instead of the actual selected exam year ID.

## Root Cause
In `resources/views/mark-entry/index.blade.php`, three functions had hardcoded exam year ID values:

1. **`startBulkImport()`** (Line 1677) - School-level bulk import
2. **`printScoresheet()`** (Line 1401) - Scoresheet PDF print
3. **`bulkExport()`** (Line 1417) - Bulk export

The backend validates that the `exam_year_id` exists in the database using Laravel validation:
```php
$request->validate([
    'exam_year_id' => 'required|integer|exists:exam_years,id',
]);
```

If exam year with ID 1 doesn't exist (or the wrong ID was sent), the validation would fail with a 422 error.

## Solution
Updated all three functions to properly extract the exam year ID from the selected exam year label, matching the pattern already used elsewhere in the codebase:

```javascript
const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
if (!examYearId) {
    this.showMessage('Invalid exam year selected', 'error');
    return;
}
```

## Files Changed
- `resources/views/mark-entry/index.blade.php`
  - Lines 1662-1679: `startBulkImport()` function
  - Lines 1395-1407: `printScoresheet()` function
  - Lines 1410-1421: `bulkExport()` function

## How to Test
1. Refresh the mark entry page (cache cleared)
2. Select exam year, school, and upload a valid ZIP file
3. Click "Start Import"
4. The import should now proceed without a 422 error

## Related Code References
- Backend validation: `app/Http/Controllers/BulkImportController.php` (lines 102-105)
- Similar pattern used in: `startSchoolBulkImport()` (line 1817), `startDistrictImport()` (line 1936)
