# Mark Entry Year Dropdown Implementation

**Date**: 2026-02-04
**Objective**: Replace the free-form Year number input with a dropdown filtered to show only exam years with ACSEE candidates

## Changes Made

### 1. Backend - API Endpoint (routes/web.php)

Added new endpoint `GET /api/exam-years/with-acsee`:
- Fetches all exam years that have ACSEE candidates registered
- Returns filtered list ordered by year descending
- Includes year status (Active, Locked, Draft) for visual feedback

```php
Route::get('/api/exam-years/with-acsee', function () {
    // Returns: { years: [ { id, year_label, is_locked, status } ] }
});
```

### 2. Backend - Model Relationship (app/Models/ExamYear.php)

Added `candidateExamRegistrations()` relationship:
```php
public function candidateExamRegistrations(): HasMany
{
    return $this->hasMany(CandidateExamRegistration::class);
}
```

This allows querying exam years that have ACSEE registrations.

### 3. Frontend - Mark Entry View (resources/views/mark-entry/index.blade.php)

**UI Changes:**
- Replaced number input with select dropdown
- Shows label "Year *" with required indicator
- Options populated from API response
- Height matches other filter dropdowns

**JavaScript Changes:**
- Updated `loadExamYears()` to call `/api/exam-years/with-acsee`
- Changed response data mapping from `data.exam_years` to `data.years`
- Maintains existing auto-select of active year via `setDefaultExamYear()`

## User Experience Improvements

✓ **Constraint-based selection**: Users can only select years with actual ACSEE data
✓ **Visual feedback**: Status shown (Active, Locked) to indicate year state
✓ **Consistent UI**: Dropdown style matches Region, District, School, Subject filters
✓ **Auto-select**: Active exam year (2026) selected by default
✓ **Validation**: Cannot accidentally select invalid years

## How It Works

1. **On page load**: Frontend calls `loadExamYears()` → `/api/exam-years/with-acsee`
2. **API returns**: List of years with ACSEE candidates (currently: 2026)
3. **Dropdown populated**: User sees only valid year options
4. **Default selection**: `setDefaultExamYear()` auto-selects active year (2026)
5. **Subject filtering**: When year changes, `onContextChange()` reloads subjects for that year/school

## Files Modified

- `routes/web.php` - Added `/api/exam-years/with-acsee` endpoint
- `app/Models/ExamYear.php` - Added `candidateExamRegistrations()` relationship
- `resources/views/mark-entry/index.blade.php` - Updated Year field UI and JS

## Testing

Navigate to Mark Entry → ACSEE:
- Year dropdown shows only "2026" (the year with ACSEE candidates)
- Year auto-selects to 2026 on load
- Subject dropdown filters correctly based on selected year
- Message shows "Subjects shown are based on 84 registered ACSEE candidate(s)"

## Future Extensibility

If more exam years with ACSEE candidates are added:
- API automatically includes them in the dropdown
- No frontend changes needed
- Users can switch between years seamlessly
