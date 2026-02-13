# Pagination Implementation - ACSEE Exam Type Page

## Overview
Added pagination controls to all major data tables on the ACSEE exam type page.

## Pagination Added

### 1. Subjects Tab
**Location**: After the subjects table
**Features**:
- Shows count of filtered subjects vs total subjects
- Simple info display (no page navigation needed for search results)
- Format: "Showing X subject(s) out of Y total"

### 2. Combinations Tab
**Location**: After the combinations table
**Features**:
- Full pagination with page numbers
- Previous/Next buttons
- Current page indicator
- Format: "Page X of Y, showing Z record(s) out of W total"
- Responsive navigation buttons that trigger `loadCombinations()`

### 3. Candidates Tab
**Existing**: Already has pagination implemented
**Features**:
- Page navigation
- Previous/Next buttons
- Page number buttons
- Search integration

## Implementation Details

### Subjects Pagination
```html
<div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
    <div class="text-sm text-gray-600">
        Showing <span x-text="filteredSubjects.length"></span> subject(s) out of <span x-text="subjects.length"></span> total
    </div>
</div>
```

### Combinations Pagination
```html
<div class="flex items-center justify-between px-6 py-4 border-t border-gray-200">
    <div class="text-sm text-gray-600">
        Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>, 
        showing <span x-text="filteredCombinations.length"></span> record(s) out of <span x-text="totalCount"></span> total
    </div>
    <div class="flex items-center gap-1">
        <!-- Previous Button -->
        <button @click="currentPage > 1 && (currentPage--, loadCombinations())" 
                :disabled="currentPage <= 1">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <!-- Page Number Buttons -->
        <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)">
            <button @click="currentPage = page; loadCombinations()"
                    :class="currentPage === page ? 'bg-blue-600 text-white' : 'text-gray-600'">
                <span x-text="page"></span>
            </button>
        </template>
        
        <!-- Next Button -->
        <button @click="currentPage < totalPages && (currentPage++, loadCombinations())"
                :disabled="currentPage >= totalPages">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>
```

## Data Variables Used

### Subjects
- `subjects` - Total subjects array
- `filteredSubjects` - Filtered subjects array (after search)

### Combinations
- `currentPage` - Current page number (1-based)
- `totalPages` - Total number of pages
- `filteredCombinations` - Combination records on current page
- `totalCount` - Total combinations across all pages

### Candidates
- `currentPage` - Current page number
- `totalPages` - Total number of pages
- `filteredCandidates` - Candidates on current page
- `totalCount` - Total candidates across all pages

## API Integration

The pagination works with existing API endpoints:

### GET `/api/exam-types/{code}/combinations`
Query Parameters:
- `page` - Page number (default: 1)
- `page_size` - Records per page (default: 25)
- `search` - Search query (optional)

Response includes:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 100,
    "total_pages": 4
  }
}
```

### GET `/api/candidates`
Query Parameters:
- `page` - Page number
- `page_size` - Records per page
- `search` - Search query
- `exam_type` - Filter by exam type

## Files Modified

- `resources/views/exam-types/show.blade.php`
  - Added pagination after Subjects table (lines 159-165)
  - Added pagination after Combinations table (lines 241-276)

## Design Features

- **Consistent styling** with existing candidates pagination
- **Responsive buttons** with hover states
- **Disabled states** for previous/next buttons at boundaries
- **Active page indicator** (blue background for current page)
- **Info text** showing current position and total records
- **Icon buttons** for previous/next navigation

## Testing Checklist

- [x] Subjects tab shows record count
- [x] Combinations tab pagination displays correctly
- [x] Previous button disabled on first page
- [x] Next button disabled on last page
- [x] Page number buttons work correctly
- [x] Pagination survives page refresh
- [x] Search preserves pagination state
- [x] Candidates tab pagination (already working)

## Future Enhancements

1. Add items-per-page selector dropdown
2. Add "First" and "Last" page buttons
3. Add total record count at the top of tables
4. Implement keyboard navigation (arrow keys)
5. Add pagination to Paper Structures and Timetable when implemented
