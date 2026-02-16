# ACSEE Pagination - Quick Start & Reference

## What Was Changed?

### ❌ Before
- 464 page buttons rendered (one for each page)
- Massive horizontal scroll on desktop
- Completely unusable on mobile
- Performance risk

### ✅ After
- Smart pagination: `1 … 14 15 16 … 464` (max 9 buttons)
- Per-page selector (15/25/50/100)
- Jump-to-page input
- Mobile responsive
- URL state preservation

---

## Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/ExamTypeController.php` | Updated API endpoint response format |
| `resources/views/exam-types/acsee.blade.php` | Replaced pagination UI & state management |

---

## API Endpoint

**URL:** `GET /api/exam-types/acsee/candidates`

**New Query Parameters:**
```
page=2              # Current page
per_page=25         # Rows per page (15, 25, 50, 100)
q=john              # Search query
candidate_type=SCHOOL  # Filter: ALL, SCHOOL, PRIVATE
```

**Response Format:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "per_page": 25,
    "total": 6959,
    "last_page": 279,
    "from": 26,
    "to": 50
  }
}
```

---

## Testing API Manually

```bash
# Test default pagination
curl "http://localhost:8001/api/exam-types/acsee/candidates" | jq .

# Test page 2
curl "http://localhost:8001/api/exam-types/acsee/candidates?page=2" | jq .

# Test with search
curl "http://localhost:8001/api/exam-types/acsee/candidates?q=john" | jq .

# Test with filter
curl "http://localhost:8001/api/exam-types/acsee/candidates?candidate_type=PRIVATE" | jq .

# Run full test suite
./scripts/test-acsee-pagination.sh
```

---

## Frontend State Variables

```javascript
// Pagination metadata
pagination: {
  page: 1,          // Current page
  perPage: 15,      // Rows per page
  total: 6959,      // Total candidates
  lastPage: 464,    // Last page number
  from: 1,          // First row index
  to: 15,           // Last row index
}

// Other
perPageOptions: [15, 25, 50, 100]  // Selector options
jumpToPageInput: ''                 // Jump-to-page field
candidateSearch: ''                 // Search query
candidateTypeFilter: 'ALL'          // Filter: ALL|SCHOOL|PRIVATE
```

---

## Frontend Functions

### Load Candidates (with pagination & filtering)
```javascript
await this.loadAcseeCandicates()
// - Builds query params from state
// - Syncs to URL
// - Fetches paginated data
// - Updates pagination metadata
```

### Search (debounced)
```javascript
this.filterAcseeCandicates()
// - Resets to page 1
// - Calls debounced search (300ms)
// - Prevents API spam
```

### Change Per-Page
```javascript
@change="pagination.page = 1; loadAcseeCandicates()"
// - Reset to page 1
// - Reload candidates
```

### Jump to Page
```javascript
@keyup.enter="pagination.page = jumpToPageInput; loadAcseeCandicates()"
// - Validate page number
// - Load page
// - Clear input
```

---

## UI Components

### Pagination Pager (3 Rows)

**Row 1: Info & Per-Page Selector**
```
Showing 26 to 50 of 6,959 candidates    [Per page: ▼ 25]
```

**Row 2: Page Navigation** (Desktop only)
```
[First] [Prev]  1 … 14 15 [16] 17 … 464  [Next] [Last]
```

**Row 3: Jump-to-Page & Mobile**
```
Go to page: [___] [Go]    Page 2 of 279    ⟳ Loading...
```

---

## Smart Page Number Algorithm

Shows pages with intelligent ellipsis:
- Always show: page 1 and last page
- Show current: page ±2
- Insert ellipsis (`…`) in gaps

**Examples:**
- Small dataset (464 pages): `1 … 14 15 16 … 464`
- Early pages: `1 2 3 4 5 … 464`
- Late pages: `1 … 460 461 462 463 464`
- Current page 250: `1 … 248 249 250 251 252 … 464`

---

## URL Synchronization

### Query String Format
```
?page=2&per_page=25&q=john&candidate_type=SCHOOL
```

### URL State Preserved For:
✅ Page reload  
✅ Browser back/forward  
✅ Bookmarking  
✅ Sharing links  

### Example State Restore
```javascript
const params = new URLSearchParams(window.location.search);
this.pagination.page = parseInt(params.get('page')) || 1;
this.pagination.perPage = parseInt(params.get('per_page')) || 15;
this.candidateSearch = params.get('q') || '';
this.candidateTypeFilter = params.get('candidate_type') || 'ALL';
```

---

## Accessibility

### ARIA Labels
- All buttons have `aria-label`
- Current page has `aria-current="page"`
- Semantic HTML (button, select, input)

### Keyboard Support
- Tab: Navigate between controls
- Enter: Execute action (pagination, jump-to-page)
- Number field: Min/max validation

### Screen Readers
- Button purposes announced
- Current page indicated
- Form labels associated

---

## Responsive Design

### Desktop (md breakpoint)
- Full pagination: `[First] [Prev] 1 … 464 [Next] [Last]`
- All page numbers visible
- Per-page selector on same row

### Mobile (< md)
- Page numbers hidden
- Shows: `Page X of Y`
- Per-page selector accessible
- Jump-to-page available

---

## Performance Tips

1. **Debounced Search**
   - 300ms delay prevents API spam
   - User can type freely without lag

2. **Server-Side Filtering**
   - Candidate type filtered at DB level
   - Only 15-100 rows per request

3. **Eager Loading**
   - School, district, region pre-loaded
   - Subject selections eager-loaded
   - No N+1 query problems

4. **Smart Pagination UI**
   - Only 7-9 page buttons (not 400+)
   - Fixed DOM element count
   - Fast rendering (<100ms)

---

## Common Scenarios

### User Types "john"
```
1. User types 'j' → Search debounces (300ms wait)
2. User continues typing 'ohn'
3. After 300ms with no new input → API call
4. Results filter to candidates with "john"
5. Page resets to 1
6. URL updates: ?q=john
```

### User Clicks Per-Page "50"
```
1. Select changes to 50
2. @change handler triggers
3. pagination.page reset to 1
4. loadAcseeCandicates() called
5. API fetches 50 rows
6. URL updates: ?per_page=50
7. Page displays first 50 candidates
```

### User Enters "150" in Jump-to-Page
```
1. User types "150" in input
2. Presses Enter
3. Validation: 150 <= 464 ✓
4. pagination.page = 150
5. loadAcseeCandicates() called
6. API fetches page 150
7. Pager updates to show page 150 active
8. Input clears for next use
```

### User Clicks Browser Back Button
```
1. Previous URL: ?page=2&per_page=25&q=john
2. Browser back executed
3. init() reads URL params
4. State restored:
   - pagination.page = 2
   - pagination.perPage = 25
   - candidateSearch = 'john'
5. loadAcseeCandicates() called
6. Previous page displayed
```

---

## Testing Checklist

### Quick Test (5 min)
- [ ] Load `/exam-types/acsee`
- [ ] Click Candidates tab
- [ ] See new pagination UI
- [ ] Click Next button
- [ ] Test per-page selector
- [ ] Type in search box
- [ ] Refresh page (state preserved)

### Full Test (30 min)
- [ ] Test all navigation buttons
- [ ] Test jump-to-page
- [ ] Test search (should debounce)
- [ ] Test candidate type filter
- [ ] Test combined filters
- [ ] Test on mobile device
- [ ] Test browser back/forward
- [ ] Test bookmark/share URL
- [ ] Verify no console errors
- [ ] Check API response format

---

## Troubleshooting

### Issue: Too many API calls on search
**Solution:** Check if debounce is active (should wait 300ms)

### Issue: Pagination not updating
**Solution:** Verify API response has `meta` field (not `pagination`)

### Issue: Mobile shows too many buttons
**Solution:** Check CSS `.hidden md:flex` is applied to pagination row

### Issue: URL not updating
**Solution:** Verify `window.history.replaceState()` is called in loadAcseeCandicates()

### Issue: Jump-to-page not working
**Solution:** Verify input value is between 1 and lastPage

---

## Migration from Old API

### If Using Old Response Format
Replace:
```javascript
// Old
this.acseeCandicates = data.candidates;
this.acseetotalPages = data.pagination.total_pages;

// New
this.acseeCandicates = data.data;
this.pagination = {
  page: data.meta.current_page,
  perPage: data.meta.per_page,
  total: data.meta.total,
  lastPage: data.meta.last_page,
  from: data.meta.from,
  to: data.meta.to,
};
```

### Query Parameter Changes
```
page_size → per_page
search → q
(Added) candidate_type
```

---

## Related Documentation

- **Full Implementation Details:** `ACSEE_PAGINATION_IMPLEMENTATION_2026_02_16.md`
- **Deployment Guide:** `ACSEE_PAGINATION_DEPLOYMENT_2026_02_16.txt`
- **API Test Script:** `scripts/test-acsee-pagination.sh`

---

## Support

For issues or questions:
1. Check browser console (F12) for errors
2. Review `storage/logs/laravel.log`
3. Run API test suite: `./scripts/test-acsee-pagination.sh`
4. Verify database connections: `php artisan migrate:status`
