# Troubleshooting 422 Error After Deployment
**Status:** Live Test in Progress  
**Issue:** 422 Unprocessable Content on Preview  
**Cause:** Likely browser cache not cleared  

---

## Quick Fix (Do This Now)

### Step 1: Hard Clear Browser Cache

**Chrome/Edge:**
1. Press `Ctrl+Shift+Del` (Windows) or `Cmd+Shift+Del` (Mac)
2. Select "All time" for time range
3. Check: Cookies, Cached images, Cached files
4. Click "Clear data"
5. Close all Mark Entry tabs
6. Restart the browser or open new incognito window

**Firefox:**
1. Press `Ctrl+Shift+Del` (Windows) or `Cmd+Shift+Del` (Mac)
2. Select "Everything"
3. Click "Clear Now"
4. Close all Mark Entry tabs
5. Restart browser or open new private window

### Step 2: Verify Server Cache Was Cleared

Run these commands:

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

Expected output:
```
✓ Application cache cleared successfully
✓ Compiled views cleared successfully
✓ Configuration cache cleared successfully
```

### Step 3: Test Again

1. Open **new browser tab or incognito window**
2. Navigate to: `127.0.0.1:8000/mark-entry`
3. Go to "School Bulk ZIP" tab
4. Select exam year + ZIP file
5. Click **Preview** button
6. Check browser DevTools (F12):
   - Network tab
   - Look for `/api/bulk-import/preview` request
   - Status should be: **200 OK** (not 422)

---

## If Still Getting 422 Error

### Check 1: Verify Code Changes Are in File

```bash
grep -n "formData.append('_token'" /home/prosmart-technologies/SOL/irms/resources/views/mark-entry/index.blade.php
```

**Expected Output:**
```
1794:             formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
1907:             formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
```

If you see these 2 lines → Code is in place ✅  
If you don't see them → Code wasn't deployed properly ❌

### Check 2: Verify No Headers Are Being Set

```bash
grep -A 8 "async previewSchoolZip()" /home/prosmart-technologies/SOL/irms/resources/views/mark-entry/index.blade.php | grep -i "headers"
```

**Expected Output:** (should be empty/nothing)

If you see `'X-CSRF-TOKEN'` or `headers:` → Old code is still there ❌

### Check 3: Check Server Logs for CSRF Errors

```bash
tail -20 storage/logs/laravel.log | grep -i csrf
```

**Expected:** No output (no CSRF errors)

**If you see errors:** Log them and share for analysis

### Check 4: Verify CSRF Token is Present in Page

In browser DevTools Console, run:

```javascript
document.querySelector('meta[name="csrf-token"]').getAttribute('content')
```

**Expected:** Should show a long alphanumeric token (looks like: `abc123def456...`)

**If you see:** `null` or `undefined` → CSRF token not in page

---

## Network Tab Debugging

If 422 persists, check these in Browser DevTools:

### Request Headers
Look for in the **Request Headers** section:

```
POST /api/bulk-import/preview HTTP/1.1
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...
(other headers...)
```

**Should NOT have:** `X-CSRF-TOKEN` header

### Request Body
Expand the request and look at Form Data:

```
zip_file: (binary file data)
_token: (csrf token value)
```

**Should have:** `_token` field in the body ✅  
**Should NOT have:** `X-CSRF-TOKEN` header ❌

### Response
Check the **Response** section:

```json
{
  "success": false,
  "errors": [...],
  "message": "..."
}
```

Look for CSRF-related error messages like:
- "CSRF token mismatch"
- "Invalid token"
- "Unprocessable Content"

---

## Complete Diagnostic Command

Run this to check everything:

```bash
#!/bin/bash

echo "=== DEPLOYMENT DIAGNOSTIC ==="
echo ""
echo "1. Code Changes:"
grep -n "formData.append('_token'" /home/prosmart-technologies/SOL/irms/resources/views/mark-entry/index.blade.php

echo ""
echo "2. Old Code (should be empty):"
grep -A 8 "async previewSchoolZip()" /home/prosmart-technologies/SOL/irms/resources/views/mark-entry/index.blade.php | grep "headers"

echo ""
echo "3. Cache Status:"
php artisan cache:clear
php artisan view:clear

echo ""
echo "4. Recent Log Errors:"
tail -5 storage/logs/laravel.log

echo ""
echo "=== END DIAGNOSTIC ==="
```

---

## If Nothing Works - Full Restore

**Step 1:** Restore from backup
```bash
cp /home/prosmart-technologies/SOL/irms/backups/deployment_2026_02_07/index.blade.php.backup \
   /home/prosmart-technologies/SOL/irms/resources/views/mark-entry/index.blade.php
```

**Step 2:** Clear caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

**Step 3:** Test old behavior
```bash
# In browser: Navigate to /mark-entry
# If old behavior works → Issue is with deployment
# If old behavior also shows 422 → Deeper issue
```

---

## Expected Results After Fix

### ✅ Success Indicators

1. **Browser Console:** No 422 errors
2. **Network Tab:** `/api/bulk-import/preview` shows **200 OK**
3. **UI:** Shows subject list or schools list (depending on which ZIP)
4. **Logs:** Shows benchmark metrics like:
   ```
   "Bulk Import: Process CSV Upload (50000 records)"
   time: "2.45s", rows_per_second: 20408
   ```

### ❌ Failure Indicators

1. Still see **422 error** in DevTools
2. Network shows `/api/bulk-import/preview` returning 422
3. Console shows CSRF validation error
4. UI doesn't display ZIP preview

---

## Getting Help

If stuck, collect this information:

1. Output of code changes check: `grep -n "formData.append('_token'"`
2. Browser DevTools Network tab screenshot (showing the failed request)
3. Latest 10 lines from logs: `tail -10 storage/logs/laravel.log`
4. Output of complete diagnostic command above

---

## Next Actions

1. **Immediately:** Clear browser cache (Ctrl+Shift+Del)
2. **Then:** Restart browser or use incognito window
3. **Try again:** Click Preview button
4. **Check:** If it's now 200 OK → ✅ Success! We're done
5. **If still 422:** Run the diagnostic commands above and check logs

---

**Status:** Troubleshooting in progress  
**Last Update:** February 7, 2026  
**User Action:** Clear browser cache and try again
