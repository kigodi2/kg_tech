# Test FormData CSRF Fix

## Browser Console Test

Open browser DevTools (F12) and go to **Console** tab, then run this JavaScript to verify the fix is working:

```javascript
// Check if CSRF token exists in page
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
console.log('CSRF Token Found:', token ? '✅ Yes' : '❌ No');

// Check if previewSchoolZip function exists
console.log('previewSchoolZip exists:', typeof markEntryManager().previewSchoolZip === 'function' ? '✅ Yes' : '❌ No');

// Manually test FormData with CSRF token
const formData = new FormData();
formData.append('_token', token);
formData.append('test', 'value');

// Log what's in FormData
console.log('FormData contents:');
for (let [key, value] of formData.entries()) {
    console.log(`  ${key}:`, value);
}

// Test fetch request (WITHOUT actually sending to server)
const testRequest = {
    method: 'POST',
    body: formData
    // No headers! Browser will auto-set Content-Type
};
console.log('Fetch request config:', testRequest);
console.log('✅ Request should NOT have X-CSRF-TOKEN header');
```

## Network Tab Test

1. Open **Network** tab in DevTools
2. Click **Preview** button
3. Look for `/api/bulk-import/preview` request
4. Check **Headers** tab:
   - Should see: `Content-Type: multipart/form-data; boundary=----...`
   - Should NOT see: `X-CSRF-TOKEN` header
5. Check **Request** tab (or **Payload**):
   - Should see: `_token: [csrf_token_value]`
   - This means token is in FormData, not headers ✅

## What Should Happen

### ✅ CORRECT (After Fix)
```
Request Headers:
  Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...
  (NO X-CSRF-TOKEN header)

Request Body (Form Data):
  zip_file: (binary)
  _token: abc123def456...

Response:
  Status: 200 OK
  Body: { "success": true, "preview": {...} }
```

### ❌ INCORRECT (Old Code)
```
Request Headers:
  X-CSRF-TOKEN: abc123def456...
  (Browser tries to auto-set multipart boundary but fails)

Request Body (Form Data):
  zip_file: (binary)
  (NO _token field!)

Response:
  Status: 422 Unprocessable Content
  Body: { "message": "CSRF token mismatch" }
```

## Debugging Checklist

- [ ] CSRF token is in page meta tag
- [ ] FormData includes `_token` field
- [ ] Fetch call does NOT set any headers
- [ ] Request Content-Type shows multipart/form-data
- [ ] Response status is 200 OK (not 422)

## If Still Getting 422

1. Check that you see `_token` in FormData (Network → Payload tab)
2. Check that you don't see `X-CSRF-TOKEN` in Request Headers
3. If you still see `X-CSRF-TOKEN` header → Old code is cached
4. If `_token` is missing from FormData → Code fix wasn't deployed

## Clear Cache Commands

If you suspect cache issue:

```bash
# Server side
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Browser side
Ctrl+Shift+Del (clear all cache)
Then F5 (refresh)
```

---

## Expected Result After Fix

✅ **Preview button works**
- Shows ZIP preview data
- Network shows 200 OK
- Start Import button becomes blue enabled

❌ **If still showing 422**
- FormData not properly including _token
- Code fix hasn't been deployed
- Browser cache hasn't been cleared
- Server cache hasn't been cleared
