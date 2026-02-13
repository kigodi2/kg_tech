# Bulk ZIP Import - Troubleshooting Guide

## Quick Reference

### Error: 422 (Unprocessable Content) on Preview
**Possible Causes:**
1. ZIP file is not actually a ZIP (wrong extension or corrupted)
2. ZIP structure is invalid (missing manifest.json)
3. Manifest is missing required fields

**Solutions:**
- Check that file ends in `.zip`
- Verify ZIP integrity: `unzip -t file.zip`
- Check manifest has required fields: `exam_year`, `school_code`

### Error: 422 (Unprocessable Content) on Start Import
**Possible Causes:**
1. Exam year doesn't exist in system
2. School doesn't exist in system
3. ZIP was uploaded but disappeared from temp storage

**Solutions:**
- Verify exam year 2026 exists: Check ADMIN > Settings > Exam Years
- Verify school exists: Check ADMIN > Schools
- Re-upload the ZIP file

### Error: 500 (Internal Server Error)
**Location:** `storage/logs/laravel.log`

**Check logs for:**
```bash
tail -f storage/logs/laravel.log | grep -i "zip\|preview\|import"
```

**Common causes:**
- File storage directory not writable: `storage/app/temp`
- ZIP is corrupted: Try extracting locally
- Memory limit exceeded: Check for very large files

---

## Testing Checklist

### Before Uploading
```bash
# Check if ZIP is valid
unzip -t your_file.zip

# Check if ZIP has manifest
unzip -l your_file.zip | grep manifest.json

# View manifest contents
unzip -p your_file.zip manifest.json | jq .
```

### Expected Manifest Content
```json
{
  "exam_year": "2026",
  "school_code": "KLERUU",
  "school_name": "KLERUU TEACHERS COLLEGE",
  "files": {
    "BIOLOGY_ACSEE_2026_P1.csv": {
      "subject_name": "BIOLOGY",
      "subject_code": "BIOLOGY"
    }
  }
}
```

### Console Debugging
Open browser DevTools (F12) → Console tab:

```javascript
// Check if exam years loaded
console.log(examYears);
// Should show array of exam year objects with id and year_label

// Check if school selected
console.log(selectedSchool);
// Should show numeric ID

// Check exam year ID extraction
const examYearId = examYears.find(y => y.year_label === "2026")?.id;
console.log(examYearId);
// Should show numeric ID
```

---

## Log Locations & Parsing

### Application Logs
```bash
# Real-time logs
tail -f storage/logs/laravel.log

# Filter for import errors
grep -i "bulk.*import\|preview" storage/logs/laravel.log

# Show last 50 lines with import-related errors
grep -i "preview\|startImport" storage/logs/laravel.log | tail -50
```

### Expected Log Entries

**Successful Preview:**
```
[TIMESTAMP] local.INFO: ZIP preview successful
[TIMESTAMP] local.DEBUG: preview service returned valid ZIP
```

**Successful Import:**
```
[TIMESTAMP] local.INFO: School bulk import started
[TIMESTAMP] local.INFO: Import job dispatched for bulk_import_id=123
```

**Error Preview:**
```
[TIMESTAMP] local.ERROR: ZIP preview failed: manifest.json not found in ZIP
[TIMESTAMP] local.ERROR: Stack: App\Services\MarkImport\ZipPreviewService::validate()
```

---

## File System Verification

### Check Temp Storage
```bash
# List temp files
ls -lah storage/app/temp/

# Check permissions
ls -ld storage/app/temp/

# Should be writable (mode 755 or 775)
```

### Clean Up Old Files
```bash
# Find files older than 1 day
find storage/app/temp -type f -mtime +1 -ls

# Delete files older than 7 days
find storage/app/temp -type f -mtime +7 -delete
```

---

## Network Issues

### Check CSRF Token
```javascript
// In browser console
document.querySelector('meta[name="csrf-token"]').content
// Should return a long string (64 characters)
```

### Check Request/Response
Open DevTools → Network tab:

1. Upload ZIP file
2. Find request to `/api/bulk-import/preview`
3. Check:
   - **Headers:** Should have `X-CSRF-TOKEN`
   - **Request Body:** Should be FormData with `zip_file`
   - **Response:** Should be JSON with `success: true`

---

## Common Scenarios

### Scenario: "ZIP file was lost"
**Possible Cause:** Session expired or cleared

**Solution:**
- Re-upload the ZIP file
- Session timeout is usually 2 hours
- Refresh page to get fresh session

### Scenario: "No ZIP file uploaded"
**Possible Cause:** Form data not sent correctly

**Solution:**
- Check browser supports FormData API
- Check Network tab for actual request
- Try uploading again

### Scenario: "Invalid exam year selected"
**Possible Cause:** Exam years didn't load in frontend

**Solution:**
```javascript
// In console, check if init() was called
// This loads exam years
console.log(window.examYears);

// If empty, manually load:
fetch('/api/exam-years')
  .then(r => r.json())
  .then(data => console.log(data))
```

---

## Performance Tips

### For Large ZIP Files (>50MB)
1. Increase timeout in browser DevTools settings
2. Check PHP `upload_max_filesize` and `post_max_size`
3. Check Laravel `log_channel` is not slowing down

### Monitor Import Progress
```javascript
// Watch bulk import job queue
GET /api/bulk-import/{id}/progress

// Returns:
{
  "status": "processing",
  "processed": 45,
  "total": 100,
  "percentage": 45
}
```

---

## Contact Support

If you're still getting errors:

1. **Collect information:**
   - Error message (full screenshot)
   - Browser console logs
   - `storage/logs/laravel.log` (last 100 lines)
   - ZIP file name and size

2. **Reproduce with test file:**
   - Use smaller test ZIP
   - Check all required manifest fields

3. **Check system requirements:**
   - `php -v` (should be 8.1+)
   - `php -m | grep zip` (ZipArchive extension)
   - `df -h storage/` (disk space)

---

## Quick Fixes

```bash
# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan config:cache

# Re-compile autoloader
composer dump-autoload -o

# Check storage directory permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Restart PHP-FPM (if applicable)
sudo systemctl restart php-fpm
```
