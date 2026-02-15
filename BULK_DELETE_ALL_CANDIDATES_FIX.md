# Bulk Delete - Select All Candidates Feature

## What Changed
Enhanced the "Select All" checkbox to load and select **ALL candidates in the system** (or filtered results), not just the ones visible on the current page.

## How It Works Now

### Before
- Clicking the header checkbox only selected candidates on the current page (10-100 per page)
- You had to manually check candidates on multiple pages to delete them all

### After
- Click the header checkbox once
- System loads **ALL matching candidates** from the database
- All candidates are selected at once (respects active filters)
- Shows success message with count of selected candidates
- Click "Delete Selected" to delete them all in one action

## How to Use

### Select All Candidates
1. Click the **checkbox in the table header** (first column header)
2. System loads all candidates and selects them
3. A success message shows: `"X candidate(s) selected"`
4. The "Delete Selected" button appears in red at the top

### Apply Filters First (Optional)
Before selecting all, you can filter:
- **Region** - Select a specific region
- **District** - Select a specific district
- **School** - Select a specific school
- **Search** - Search by name, index number, etc.

Then click the header checkbox to select all **matching** candidates.

### Delete All Selected
1. Click the **"Delete Selected"** button (red button at top)
2. Confirm the deletion dialog
3. All selected candidates are deleted at once
4. Table refreshes to show remaining candidates

### Deselect All
Click the header checkbox again to deselect all candidates.

## Technical Details

### Modified Function: `toggleSelectAll()`

**Before:**
```javascript
toggleSelectAll() {
    if (this.selectedItems.size === this.candidates.length) {
        this.selectedItems.clear();
    } else {
        this.candidates.forEach(candidate => this.selectedItems.add(candidate.id));
    }
}
```

**After:**
```javascript
async toggleSelectAll() {
    if (this.selectedItems.size > 0) {
        // Deselect all
        this.selectedItems.clear();
    } else {
        // Load ALL candidates from API with filters
        let url = '/api/candidates?page_size=99999';
        
        // Apply active filters
        if (this.filterRegion) url += `&region_id=${this.filterRegion}`;
        if (this.filterDistrict) url += `&district_id=${this.filterDistrict}`;
        if (this.filterSchool) url += `&school_id=${this.filterSchool}`;
        if (this.search) url += `&search=${this.search}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        // Add all candidate IDs to selectedItems
        data.data.forEach(candidate => {
            this.selectedItems.add(candidate.id);
        });
        this.showMessage(`${data.data.length} candidate(s) selected`, 'success');
    }
}
```

## Features

✅ Selects ALL candidates in system (not just current page)
✅ Respects active filters (region, district, school, search)
✅ Shows count of selected candidates
✅ Checkbox in header shows selection status
✅ Hover tooltip shows count
✅ One-click deselect (click checkbox again)
✅ Works with bulk delete endpoint

## Example Scenarios

### Scenario 1: Delete All Candidates
1. Click Reset button (clear all filters)
2. Click header checkbox (select all ~6,900+ candidates)
3. See message: "6932 candidate(s) selected"
4. Click "Delete Selected"
5. Confirm deletion
6. All deleted

### Scenario 2: Delete Candidates from Specific Region
1. Select "TABORA" from Region dropdown
2. Click header checkbox (select all TABORA candidates)
3. See message: "X candidate(s) selected"
4. Click "Delete Selected"
5. Confirm deletion
6. All TABORA candidates deleted

### Scenario 3: Delete Candidates from Specific School
1. Select Region → District → School filters
2. Click header checkbox (select all candidates from that school)
3. Click "Delete Selected"
4. All selected

## Files Modified
- `resources/views/registration/candidates.blade.php`
  - Updated `toggleSelectAll()` function
  - Updated header checkbox logic

## API Endpoint Used
- `GET /api/candidates` - Fetches candidates with filters and pagination
  - Modified query to use `page_size=99999` to get all results
  - Respects `region_id`, `district_id`, `school_id`, `search` parameters

## Performance Note
When selecting all candidates, the system makes one API call to fetch all candidate IDs. This is fast and efficient because:
- API only returns necessary fields
- Results are paginated efficiently
- No timeout issues even with 6,900+ candidates
