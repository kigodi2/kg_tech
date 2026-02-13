# Exam Year Display Bug Fix (2026 → 2826)

## Issue
The mark entry page was displaying exam year as "2826" instead of "2026", along with JSON parsing errors.

## Root Cause
Multiple issues combined:

1. **Frontend Type Mismatch**: The year select dropdowns were using Alpine.js's `.number` modifier on string fields:
   ```html
   <select x-model.number="examYear">
       <option :value="year.year_label">{{ year.year_label }}</option>
   </select>
   ```
   - Database stores `year_label` as string: `"2026"`
   - `.number` modifier converts to number: `2026`
   - Select value comparison fails, display shows "2828"

2. **Backend Type Casting**: The `/api/exam-years/with-acsee` endpoint was incorrectly casting year_label to integer:
   ```php
   'year_label' => (int)$year->year_label,  // WRONG
   ```

3. **JSON Encoding Issues**: Emoji characters in API responses (`✓ Active`, `🔒 Locked`) could cause JSON parsing errors in strict parsers.

## Solution

### Frontend Changes
Removed `.number` modifier from all exam year select dropdowns since `year_label` is a string field.

**Files Modified:**
1. **resources/views/mark-entry/index.blade.php**
   - Line 23: `x-model.number="examYear"` → `x-model="examYear"`
   - Line 577: `x-model.number="schoolBulkExamYear"` → `x-model="schoolBulkExamYear"`
   - Line 779: `x-model.number="districtExamYear"` → `x-model="districtExamYear"`

2. **resources/views/mark-entry/bulk-district-import.blade.php**
   - Line 12: Removed `.number` modifier
   - Fixed value from `year.id` to `year.year_label`

### Backend Changes
Fixed type handling in API responses:

1. **routes/web.php** - `/api/exam-years/with-acsee`
   - Removed `(int)` cast from year_label
   - Removed emoji-containing status field
   - Added error handling

2. **routes/web.php** - `/api/exam-years/active`
   - Removed emoji-containing status field
   - Added error handling

## Testing
After fix:
- Exam year dropdown displays "2026" correctly
- Value binding works with active year
- JSON parsing works without errors
- No type mismatch in browser console

## Related
- `/api/exam-years/active` endpoint now returns clean JSON
- `/api/exam-years/with-acsee` endpoint returns proper string year_label
- Database unchanged (year_label is correctly stored as "2026")
