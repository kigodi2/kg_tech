# Mark Entry District Scoresheet - Performance Timeout Fix

**Status:** CRITICAL - PDF generation timing out  
**Error:** Maximum execution time of 30 seconds exceeded  
**Location:** MarkEntryController::generateSchoolScoresheetZip() - Line 1212

---

## Problem

The district bulk scoresheet download feature is attempting to generate PDF scoresheets for all subjects across all schools in a district. This operation exceeds PHP's 30-second execution timeout for large districts.

**Error Chain:**
1. User clicks "District Scoresheets (ZIP)"
2. Controller calls `downloadDistrictBulkScoresheetExport()`
3. For each school: calls `generateSchoolScoresheetZip()`
4. For each school subject: generates PDF via dompdf
5. **Timeout at line 1212 (ZIP creation) or earlier**

**Log Evidence:**
```
[2026-02-06 12:34:14] local.ERROR: Maximum execution time of 30 seconds exceeded 
  at /home/prosmart-technologies/SOL/irms/app/Http/Controllers/MarkEntryController.php:1212
```

---

## Root Cause

### Performance Bottleneck
The `generateSchoolScoresheetZip()` method performs synchronous PDF generation for every subject at every school:

```
For IRINGA MC district (example):
  - 10 schools × 5 subjects/school = 50 PDFs
  - Each PDF generation: 0.5-1.5 seconds
  - Total time: 25-75 seconds
  - PHP timeout: 30 seconds
  - Result: TIMEOUT ❌
```

### Why It's Slow
1. **DomPDF rendering** - Converts HTML to PDF (CPU intensive)
2. **No caching** - Every PDF regenerated on every request
3. **No queuing** - All processed synchronously in request lifecycle
4. **No pagination** - Large scoresheets with 100+ candidates

---

## Solutions

### Solution 1: Pre-generate Scoresheets (Recommended)
Instead of generating on-demand, pre-generate and cache scoresheets as a background job.

**Pros:**
- Fast downloads (serve pre-built files)
- No timeout issues
- Better user experience
- Can be refreshed daily

**Cons:**
- Requires queue setup
- Uses more storage
- Additional complexity

**Implementation:**
```php
// Create a scheduled job
php artisan make:job GenerateDistrictScoresheets

// Schedule it to run nightly
// This prevents users from triggering generation
```

### Solution 2: Increase PHP Timeout (Quick Fix)
Increase the PHP execution timeout for this specific endpoint.

**Pros:**
- Quick fix
- No code changes to business logic
- Works for current implementation

**Cons:**
- Still slow for users
- May still timeout on very large districts
- Band-aid solution

**Implementation:**
Add to `.htaccess` or nginx config:
```
php_value max_execution_time 300
```

Or add to controller method:
```php
set_time_limit(300); // 5 minutes
```

### Solution 3: Stream CSVs Instead of PDFs (Lightweight)
Generate district CSV export instead of PDFs (much faster).

**Pros:**
- Very fast (< 2 seconds even for large districts)
- Smaller file size
- Can be opened in Excel

**Cons:**
- Less "pretty" than PDFs
- Users must generate PDFs themselves if needed

**Implementation:**
Already exists! Route: `downloadDistrictBulkCsvExport()`

### Solution 4: Async Generation (Best Long-term)
Generate as async job, return download link when ready.

**Pros:**
- No timeout
- Best UX
- Scalable

**Cons:**
- Requires queue infrastructure
- More complex
- Longer development time

---

## Recommended Action

### Immediate (< 5 minutes)
**Option A:** Increase timeout to 300 seconds
```php
// In MarkEntryController::downloadDistrictBulkScoresheetExport()
set_time_limit(300); // Add this at the start
```

**Option B:** Warn users to use CSV export instead
Update UI to clarify that CSV is faster for district exports.

### Short-term (< 1 hour)
Add performance optimization to PDF generation:
1. Batch multiple subjects into single PDFs
2. Cache generated PDFs
3. Skip empty subjects

### Long-term (< 1 week)
Implement async scoresheet generation using Laravel queues.

---

## Quick Fix Implementation

### Step 1: Add Timeout Increase
Edit `MarkEntryController.php` at line 1046:

```php
public function downloadDistrictBulkScoresheetExport(Request $request)
{
    set_time_limit(300); // Increase from 30s to 5min
    
    try {
        $request->validate([
            // ... rest of code
```

### Step 2: Add Warning Message (Optional)
Update the view to show performance note:

```blade
<!-- In resources/views/mark-entry/index.blade.php around line 315 -->
<button ...>
    <i ...></i>
    <span>District Scoresheets (ZIP) - May take 1-2 minutes for large districts</span>
</button>
```

### Step 3: Test
1. Clear cache: `php artisan cache:clear`
2. Test with IRINGA MC district (10 schools)
3. Monitor execution time
4. Verify PDF quality

---

## Testing Plan

### Test Cases
1. **Small District** (1-2 schools) - Should complete in < 5 seconds
2. **Medium District** (5-10 schools) - Should complete in 30-60 seconds
3. **Large District** (15+ schools) - Should complete in < 300 seconds

### Monitoring
Watch server logs during test:
```bash
tail -f storage/logs/laravel.log
```

Look for:
- PDF generation timing
- ZIP creation speed
- Memory usage
- Final file size

---

## Alternative: Use CSV Export Instead

The system already has a fast CSV export for districts:
- Route: `/mark-entry/acsee/district-bulk-csv-download`
- Performance: < 2 seconds
- File size: Smaller
- User can open in Excel/Google Sheets

**Recommendation:** For immediate relief, users can switch to CSV export while we optimize PDF generation.

---

## Prevention

1. **Add timeout configuration** to `.env`:
   ```
   MARK_ENTRY_TIMEOUT=300
   ```

2. **Monitor performance** of PDF operations
3. **Set up queue system** for async PDF generation
4. **Consider PDF caching** for frequently accessed scoresheets

---

## Files to Modify

| File | Change | Priority |
|------|--------|----------|
| `app/Http/Controllers/MarkEntryController.php` | Add `set_time_limit(300)` | HIGH |
| `resources/views/mark-entry/index.blade.php` | Add performance note | MEDIUM |
| `.env` | Add timeout config | LOW |

---

**Next Step:** Apply the quick fix (add `set_time_limit(300)`) and test the district scoresheet download.
