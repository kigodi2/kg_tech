# Quick Start Guide - Filtering Features

## 30-Second Overview

The ACSEE Candidates page now has:
- ✓ Cascading Region → District → School filters
- ✓ Allocated Subjects column showing candidate's subjects
- ✓ Auto-school detection from Index Number
- ✓ Searchable dropdowns with quick find

---

## Using the ACSEE Candidates Page

### 1. Navigate to the Page
```
URL: http://yourapp.com/exam-types/acsee
→ Click "CANDIDATES" tab in left sidebar
```

### 2. Using Filters

**To filter by Region:**
1. Click Region dropdown
2. Type to search (e.g., "Northern")
3. Click region name
4. Districts filter automatically updates

**To filter by District:**
1. Click District dropdown
2. Type to search
3. Click district name
4. Schools filter automatically updates

**To filter by School:**
1. Click School dropdown
2. Type to search
3. Click school name
4. Candidates list updates instantly

**To clear all filters:**
- Click the "Reset" button

### 3. Viewing Allocated Subjects

Look at the "Allocated Subjects" column in the table:
- **Shows:** Subject codes (e.g., "ENG, MATH, SCI")
- **If empty:** Displays "-"

### 4. Searching Candidates

Use the search box at the top:
- Search by **Index Number**
- Search by **Full Name**

---

## Using Auto-School Detection

### Registration Page
```
URL: http://yourapp.com/registration/candidates
```

**How it works:**
1. Click "Register Candidate" button
2. Enter Index Number (e.g., "S0445-0034")
3. School field auto-fills with matching school
4. Complete other fields and save

---

## Filtering in Schools Management

### Navigate
```
URL: http://yourapp.com/registration/schools
```

**Available Filters:**
- **Region:** Select region
- **District:** Shows districts in selected region
- **Search:** Find schools by name or code

---

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| Click dropdown | Open/close filter |
| Type in search | Filter dropdown options |
| Esc | Close dropdown |
| Enter | Select highlighted option |

---

## Common Scenarios

### Scenario 1: Find all candidates in a school
1. Select Region
2. Select District
3. Select School
4. See filtered candidates

### Scenario 2: Register a candidate with auto-school
1. Click "Register Candidate"
2. Enter Index Number: "S0445-0004"
3. School auto-fills
4. Enter other details
5. Click "Register Candidate"

### Scenario 3: See what subjects a candidate takes
1. Find candidate in table
2. Look at "Allocated Subjects" column
3. See their subject codes

### Scenario 4: Search for specific candidate
1. Type in search box
2. Type candidate's name or index
3. Table filters instantly

---

## Troubleshooting

### Problem: Filters not showing
**Solution:** 
- Refresh page: F5 or Ctrl+R
- Check browser console: F12
- Ensure JavaScript is enabled

### Problem: No candidates appearing
**Solution:**
- Check if data exists in database
- Try reset button
- Clear all filters

### Problem: Auto-school not working
**Solution:**
- Ensure Index Number format is correct (e.g., S0445-0004)
- School code must exist in database
- Check console for error message

### Problem: Allocated Subjects showing "-"
**Solution:**
- Combination may not have subjects assigned
- Check combination configuration
- Verify combination_subject pivot table

---

## Tips & Tricks

1. **Quick Filter:** Type in dropdown search to instantly find regions/districts/schools
2. **Reset Easily:** Click "Reset" to start over
3. **Pagination:** Use page numbers at bottom to browse candidates
4. **Bulk Operations:** Select multiple candidates, then delete/update together
5. **View Details:** Click eye icon to see full candidate details

---

## API Endpoints (For Developers)

```
GET /api/exam-types/ACSEE/candidates
  Query params:
  - page=1 (pagination)
  - page_size=15 (results per page)
  - search=name (search term)

GET /api/regions
GET /api/districts?page_size=999
GET /api/schools?page_size=999
```

---

## File Locations (For Developers)

**Main Files:**
- View: `resources/views/exam-types/show.blade.php`
- Controller: `app/Http/Controllers/ExamTypeController.php`
- Routes: `routes/web.php` (line 637)

**Documentation:**
- `ALLOCATED_SUBJECTS_IMPLEMENTATION.md` - Technical details
- `FILTERING_FEATURES_COMPLETE.md` - Full testing guide
- `IMPLEMENTATION_STATUS_SUMMARY.md` - Complete status

---

## Support

**For Issues:**
1. Check documentation files
2. Review code comments
3. Check browser console (F12)
4. Review server logs

**For Enhancements:**
- See: `IMPLEMENTATION_STATUS_SUMMARY.md` → Future Enhancements

---

## Summary Table

| Feature | Location | Status |
|---------|----------|--------|
| ACSEE Filters | /exam-types/acsee | ✓ Working |
| Allocated Subjects | Candidates table | ✓ Working |
| Auto-School Detection | /registration/candidates | ✓ Working |
| School Filters | /registration/schools | ✓ Working |
| Search Functionality | All pages | ✓ Working |

---

**Last Updated:** January 31, 2025  
**Status:** ✓ PRODUCTION READY

For detailed information, see the other documentation files in the project root.
