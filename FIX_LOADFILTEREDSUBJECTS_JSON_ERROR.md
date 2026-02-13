# Fix: loadFilteredSubjects JSON Error

**Issue:** "not valid JSON" error when loading subjects for a selected school  
**Status:** ✓ FIXED  
**Date:** 2026-02-06

---

## Problem

The `loadFilteredSubjects()` function was attempting to parse JSON from error responses (HTTP 500, 404, etc.) without first checking if the response was successful.

**Code Flow:**
```javascript
const response = await fetch('/api/mark-entry/acsee/subjects-by-school?...');
const data = await response.json(); // ❌ Fails if status != 200-299
```

When the API returned an error (e.g., HTTP 500), the response body contained HTML instead of JSON, causing `response.json()` to throw a parse error.

---

## Solution

Added proper error handling with nested try-catch to safely parse JSON:

```javascript
const response = await fetch('/api/mark-entry/acsee/subjects-by-school?...');

// Try to parse JSON safely
let data = {};
try {
    data = await response.json();
} catch (parseError) {
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    throw parseError;
}

// Now safe to handle response
if (response.ok && data.success) {
    // Handle success
} else if (response.status === 422) {
    // Handle validation error
} else if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ...`);
}
```

## Files Modified

**File:** `resources/views/mark-entry/index.blade.php`

**Function:** `loadFilteredSubjects()` (lines 1175-1233)

**Changes:**
- Added nested try-catch for JSON parsing
- Added HTTP status checks before processing
- Improved error messages with HTTP status codes
- Better error propagation to user

---

## Benefits

1. **No More "not valid JSON" Error** - Safely handles parse failures
2. **Better Error Messages** - Users see actual HTTP errors (500, 404, etc.)
3. **Graceful Error Handling** - One API failure doesn't break the page
4. **Consistency** - Matches error handling in other API calls

---

## Testing

### Before Fix
```
User selects school → API returns 500 error
→ response.json() fails with "not valid JSON" error
→ User sees cryptic error message
```

### After Fix
```
User selects school → API returns 500 error
→ JSON parsing catches error safely
→ HTTP status check throws descriptive error
→ User sees: "Failed to load subjects for this school: HTTP 500: Internal Server Error"
```

---

## Code Details

### Key Changes in loadFilteredSubjects()

1. **Safer JSON Parsing:**
   ```javascript
   let data = {};
   try {
       data = await response.json();
   } catch (parseError) {
       console.error('Invalid JSON response:', parseError);
       if (!response.ok) {
           throw new Error(`HTTP ${response.status}: ${response.statusText}`);
       }
       throw parseError;
   }
   ```

2. **Better Error Messages:**
   ```javascript
   // Before
   this.subjectFilterMessage = 'Error loading subjects.';
   
   // After
   this.subjectFilterMessage = 'Error loading subjects: ' + error.message;
   ```

3. **Explicit HTTP Error Check:**
   ```javascript
   else if (!response.ok) {
       throw new Error(`HTTP ${response.status}: ${response.statusText}`);
   }
   ```

---

## Related Functions

This same pattern is now applied to:
- `loadRegions()`
- `loadDistricts()`
- `loadSchools()`
- `loadSubjects()`
- `loadExamYears()`
- `setDefaultExamYear()`
- `loadFilteredSubjects()` ← This one

All API loading functions now have proper error handling.

---

## Impact

- ✓ Fixes "not valid JSON" error when selecting school
- ✓ Improves error visibility across all API calls
- ✓ Makes debugging easier
- ✓ No breaking changes
- ✓ Better user experience

---

**Status:** ✓ **COMPLETE** - All API error handling now consistent and robust
