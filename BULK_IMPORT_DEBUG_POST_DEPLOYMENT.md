# Bulk Import - Post-Deployment Debugging

## Current Issue

You're seeing 422 errors when trying to upload a ZIP file for bulk import preview.

**Error shown in console:**
```
Failed to load resource: the server responded with a status of 422 
(Unprocessable Content) for /api/bulk-import/preview
```

## Root Cause Analysis

### Most Likely: Browser Cache
Your browser is still serving the old JavaScript code that had the hardcoded `exam_year_id: 1` bug.

**Evidence:**
- ✓ Server-side routes verified working
- ✓ Controller methods verified accessible  
- ✓ New code deployed and verified
- ✗ Browser still using old cached JavaScript

### Less Likely: Server Issues
Server verification shows:
- ✓ Routes registered: `POST api/bulk-import/preview`
- ✓ Controller exists: `BulkImportController`
- ✓ Method exists: `preview()`
- ✓ Validation code updated
- ✓ Error handling in place

## Solution

### Step 1: Clear Browser Cache (Mandatory)

**Chrome/Firefox:**
1. Press **Ctrl+Shift+Delete**
2. Select "Cached images and files"
3. Select "All time"
4. Click "Clear data"

**Safari:**
1. Menu → Preferences
2. Privacy tab
3. Click "Manage Website Data"
4. Select the site → "Remove"

**After clearing:**
1. **Close all browser tabs**
2. **Close browser completely**
3. **Reopen browser**
4. Go to http://127.0.0.1:8000/mark-entry/acsee

### Step 2: Verify New Code Loaded

1. **Open browser DevTools** (F12)
2. **Go to Console tab**
3. Look for message: `✓ Exam years with ACSEE loaded: 1 Proxy(Array)`
4. This confirms the page loaded successfully

**Old code would show:**
- `exam_year_id: 1` hardcoded
- No exam year extraction

**New code shows:**
- Dynamic exam year extraction
- Proper validation

### Step 3: Test Upload Again

1. **Select exam year:** 2026
2. **Select school:** Any school
3. **Upload ZIP file**

**Expected result:**
- ✓ Preview displays with subjects and candidates
- ✓ NO 422 error in console
- ✓ Upload takes 2-3 seconds

**If still 422:**
- Check console for actual error message
- See "If Still Failing" section below

## If Still Failing After Cache Clear

### Check 1: Verify File is Valid ZIP

```bash
# On your computer, test the ZIP:
unzip -t your_file.zip

# Should say: "All files OK"
```

If ZIP is invalid, you'll get 422 from server validation.

### Check 2: Check File Size

**Limits:**
- Minimum: 100 bytes (must have content)
- Maximum: Your server's `upload_max_filesize`

Check limits:
```bash
php -i | grep "upload_max_filesize\|post_max_size"
```

### Check 3: Check Server Logs

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# In another terminal, try upload again
# Logs will show the actual error
```

Expected log entry for successful preview:
```
[timestamp] local.INFO: ZIP preview successful
```

Expected log entry for failure:
```
[timestamp] local.ERROR: ZIP preview failed: [specific error]
```

### Check 4: Verify Exam Year Exists

```bash
php artisan tinker
>>> DB::table('exam_years')->get()
# Should show: [{"id": 1, "year_label": "2026", ...}]
```

If no exam years, add one:
```php
>>> DB::table('exam_years')->insert(['year_label' => '2026'])
```

### Check 5: Check File Permissions

```bash
# Verify temp directory is writable
ls -ld storage/app/temp
# Should show: drwxrwxrwx (all rwx)

chmod -R 777 storage/app/temp
chmod -R 777 bootstrap/cache
```

## Detailed Troubleshooting

### Issue: "File must be a ZIP archive (*.zip)"

**Cause:** File doesn't end in `.zip`

**Solution:**
- Check filename ends in `.zip`
- Rename file if needed
- Re-upload

### Issue: "manifest.json not found in ZIP"

**Cause:** ZIP structure invalid

**Solution:**
- Verify manifest.json is in ZIP root
- Check: `unzip -l file.zip | grep manifest.json`
- Recreate ZIP if needed

### Issue: "No ZIP file uploaded"

**Cause:** File selected but not uploaded

**Solution:**
- Make sure file is selected (checkmark visible)
- Wait for "Upload completed" message
- Then click "Start Import"

### Issue: "Failed to store uploaded file"

**Cause:** Storage directory not writable

**Solution:**
```bash
chmod -R 777 storage/app/temp
chmod -R 777 storage/app
```

## Verification Checklist

After clearing cache, verify:

- [ ] Browser console shows no 422 errors
- [ ] ZIP file uploads without error
- [ ] Preview displays subjects and candidates
- [ ] File size is reasonable (> 1KB, < 100MB)
- [ ] Exam year 2026 exists in database
- [ ] storage/app/temp is writable
- [ ] ZIP file is valid (can extract locally)

## Advanced Debugging

### Check Browser Network Tab

1. **Open DevTools** (F12)
2. **Go to Network tab**
3. **Click on the failed request** `/api/bulk-import/preview`
4. Check:
   - **Request:** Should have file in Form Data
   - **Response:** Should show specific error message
   - **Headers:** Should have CSRF token

### Check Browser Console for JS Errors

```javascript
// In browser console, check if exam years loaded:
console.log(window.examYears);
// Should show array of years with id and year_label

// Check selected school:
console.log(window.selectedSchool);
// Should show numeric ID

// Test exam year extraction:
const yr = window.examYears.find(y => y.year_label === "2026");
console.log(yr);
// Should show year object with id: 1
```

### Manual API Test

```bash
# Get CSRF token from page source or cookie
CSRF_TOKEN="xxx"

# Test preview endpoint
curl -X POST http://127.0.0.1:8000/api/bulk-import/preview \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "zip_file=@path/to/file.zip" \
  -v
```

## Success Indicators

You'll know it's working when:

1. **Browser console** shows NO 422 errors
2. **Preview appears** with:
   - School name
   - Exam year
   - List of subjects
   - Candidate counts
3. **"Start Import" button** becomes enabled
4. **No spinner/loading state** hangs
5. **Logs show** no error entries

## Quick Reference Commands

```bash
# Clear cache and restart
php artisan cache:clear
php artisan view:clear
php artisan config:cache

# Check routes
php artisan route:list | grep bulk-import

# Check logs
tail -f storage/logs/laravel.log

# Verify database
php artisan tinker
>>> DB::table('exam_years')->get()

# Fix permissions
chmod -R 777 storage/ bootstrap/cache

# Test file
unzip -t your_file.zip
unzip -l your_file.zip | grep manifest.json
```

## When All Else Fails

1. **Restart the application:**
   - Kill any running PHP processes
   - Restart web server

2. **Check application health:**
   - Go to http://127.0.0.1:8000
   - Should load without errors

3. **Re-deploy changes:**
   ```bash
   git pull origin main
   php artisan cache:clear
   php artisan view:clear
   ```

4. **Contact support** with:
   - Full browser console output
   - Last 50 lines of storage/logs/laravel.log
   - Screenshot of error
   - ZIP file details (size, structure)
