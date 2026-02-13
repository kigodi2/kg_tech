# Pagination Quick Reference

## UI Layout

```
┌─────────────────────────────────────────────────────────────────┐
│ Show: [10 ▼]        Page 5 of 25 | 4437 total      Go to: [5][Go] │
├─────────────────────────────────────────────────────────────────┤
│  [< Prev]  [3][4][5][6][7]...  [Next >]                         │
└─────────────────────────────────────────────────────────────────┘
```

## Key Features

### 1. Items Per Page Dropdown
```javascript
Select: 10, 25, 50, 100 items per page
Action: Automatically saves selection to localStorage
Reset: Goes back to page 1
```

### 2. Quick Jump Input
```javascript
Input: Type any page number (1-25)
Submit: Click "Go" or press Enter
Validation: Disabled if invalid range
```

### 3. Smart Page Numbers
```javascript
Display: Only 5 page buttons visible
Behavior: Center current page
Example on page 20 of 40: [18][19][20][21][22]...[40]
Example on page 1: [1][2][3][4][5]...
Example on page 40: ...[36][37][38][39][40]
```

## Data Flow

```
User Changes Page Size
    ↓
changePageSize() Called
    ↓
localStorage.setItem('candidatesPageSize', value)
currentPage = 1
    ↓
loadCandidates() Called
    ↓
API: /api/candidates?page=1&page_size=50
    ↓
Update UI with new data
```

## How It Handles 4437 Records

| Items/Page | Total Pages | Display |
|-----------|-------------|---------|
| 10        | 444         | 5 buttons + ellipsis |
| 25        | 178         | 5 buttons + ellipsis |
| 50        | 89          | 5 buttons + ellipsis |
| 100       | 45          | 5 buttons + ellipsis |

**Old Way (before)**: 444 page number buttons → Horizontal scrolling  
**New Way (after)**: 5 smart buttons + ellipsis + quick jump input → Clean UI

## Code Reference

### Frontend Properties
```javascript
pageSize: 10              // Current items per page
currentPage: 1            // Current page (1-indexed)
totalPages: 0             // Total pages available
totalCount: 0             // Total records
goToPageNum: null         // "Go to page" input value
pageWindowStart: 1        // First visible page number
pageWindowEnd: 5          // Last visible page number
```

### Frontend Methods
```javascript
changePageSize()          // Update items per page
goToPage(num)            // Navigate to page
goToPageByNumber()       // Navigate from input field
previousPage()           // Go to previous page
nextPage()               // Go to next page
getPaginatedPageNumbers() // Calculate visible buttons
loadCandidates()         // Fetch data from API
```

### API Endpoint
```
GET /api/candidates
  ?page=1
  &page_size=10
  &search=query

Returns:
{
  "data": [...candidates],
  "pagination": {
    "total_count": 4437,
    "total_pages": 444,
    "current_page": 1,
    "per_page": 10
  }
}
```

## Browser Storage

**Key**: `candidatesPageSize`  
**Value**: Page size (10, 25, 50, 100)  
**Duration**: Persistent across sessions  
**Auto-load**: On page initialization via `init()`

```javascript
// Save on change
localStorage.setItem('candidatesPageSize', this.pageSize);

// Load on init
const savedPageSize = localStorage.getItem('candidatesPageSize');
if (savedPageSize) {
    this.pageSize = parseInt(savedPageSize);
}
```

## Navigation Examples

### Scenario 1: Change to 50 items per page
```
1. User clicks dropdown → selects "50 per page"
2. changePageSize() triggered
3. localStorage updated
4. currentPage reset to 1
5. API fetches 50 records from page 1
6. UI shows 5 page buttons centered
```

### Scenario 2: Jump to page 100
```
1. User types 100 in "Go to:" input
2. User clicks "Go" button
3. goToPageByNumber() validates: 100 ≤ totalPages?
4. API fetches data from page 100
5. UI updates with current pagination window
```

### Scenario 3: Navigate through pages
```
User on page 20 of 444
Sees: [18][19][20][21][22]...
Clicks [22]
→ Page 22 loaded
→ Shows: [20][21][22][23][24]...
```

## Performance Impact

- **API**: Still handles pagination (no change)
- **Frontend**: Renders 5 buttons instead of 444 (94% reduction)
- **DOM**: Lighter, faster re-renders
- **Network**: Same (single page data only)
- **UX**: Significantly improved for large datasets

## Mobile Responsiveness

The pagination gracefully wraps on smaller screens:
```
Mobile (320px):
┌──────────────┐
│ Show: [10 ▼] │
├──────────────┤
│ Page 1 of 25 │
├──────────────┤
│ [Prev] [5].. │
│ [Next] [Go]  │
└──────────────┘
```

## Compatibility

- ✅ Modern browsers (Chrome, Firefox, Safari, Edge)
- ✅ Mobile browsers
- ✅ localStorage support required
- ✅ Alpine.js 3.x
- ✅ Tailwind CSS
