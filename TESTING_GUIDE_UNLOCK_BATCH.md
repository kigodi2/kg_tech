# Testing Guide: Unlock Batch Modal Fix

## Quick Test (5 minutes)

### Prerequisites
- Admin user account
- Access to `http://127.0.0.1:8000/mark-entry/acsee`
- Browser DevTools (F12)

### Test Steps

1. **Navigate to Mark Entry Page**
   ```
   URL: http://127.0.0.1:8000/mark-entry/acsee
   Wait for page to load completely
   ```

2. **Open Browser DevTools**
   ```
   Press: F12
   Go to: Network tab
   Filter: XHR/Fetch
   Keep DevTools open during test
   ```

3. **Locate a Submitted Batch**
   - Look for batches with status "Submitted" or "Locked"
   - Each batch should have an "Unlock" button

4. **Click "Unlock Batch" Button**
   - Modal should appear: "Unlock Batch" dialog
   - Button shows: "Unlock Batch" (not disabled initially)

5. **Enter Unlock Reason**
   - Click in "Reason for Unlock" textarea
   - Type: "Testing unlock batch functionality for system verification"
   - Verify: Character count shows ~52/1000
   - Verify: Button remains enabled (reason ≥ 10 chars)

6. **Submit Unlock Request**
   - Click "Unlock Batch" button
   - Button text changes to: "Processing..." with spinner
   - In DevTools Network tab, watch for POST request

7. **Verify Network Request**
   - Request URL: `/api/mark-entry/submission/unlock/[id]`
   - Request Method: POST
   - Request Headers should include:
     ```
     X-CSRF-TOKEN: [token-value]
     Content-Type: application/json
     X-Requested-With: XMLHttpRequest
     ```
   - Request Body:
     ```json
     {
       "reason": "Testing unlock batch functionality..."
     }
     ```

8. **Verify Successful Response**
   - Response Status: `200 OK` (NOT 419 or 500)
   - Response Headers:
     ```
     Content-Type: application/json
     ```
   - Response Body:
     ```json
     {
       "success": true,
       "message": "Batch unlocked successfully",
       "data": {
         "batch_id": [number],
         "lifecycle_state": "unlocked",
         "unlocked_at": "2026-02-14T...",
         "unlocked_by": "[admin-name]"
       }
     }
     ```

9. **Verify Modal Behavior**
   - Modal should close automatically (~1 second)
   - Success message appears: "Batch unlocked successfully. Admin action logged."
   - Toast notification visible for ~5 seconds
   - Unlock button should be removed or disabled for that batch

10. **Verify Console Logs**
    - Open Console tab in DevTools
    - Scroll up to find your test
    - Should see logs like:
      ```
      Unlock batch request: {url: "...", batchId: 1, ...}
      Response received: {status: 200, statusText: "OK", ...}
      API response data: {success: true, message: "...", ...}
      ```

## Error Case Testing

### Test Case 1: Insufficient Reason Length

1. Click "Unlock Batch" button again
2. Enter reason: "Short" (5 characters)
3. Verify: Button is DISABLED (grayed out)
4. Click button should have no effect
5. Add more text until ≥10 characters
6. Verify: Button becomes ENABLED

### Test Case 2: Missing CSRF Token

1. Open Browser Console (F12 → Console)
2. Clear the CSRF token:
   ```javascript
   document.querySelector('meta[name="csrf-token"]').remove()
   ```
3. Try to unlock a batch
4. Should show error: "CSRF token not found in page"

### Test Case 3: Network Error

1. Open DevTools Network tab
2. Throttle network: Network tab → Throttling → Slow 3G
3. Click "Unlock Batch"
4. Wait or let it timeout after 30 seconds
5. Should show: "Request timeout (>30s). Server may be busy..."

### Test Case 4: Server Error (Simulate)

1. In another terminal:
   ```bash
   php artisan tinker
   # In tinker:
   > $user = User::find(1)
   > $user->roles()->detach()  // Remove admin role temporarily
   > exit
   ```

2. Try to unlock batch
3. Should show: "Unauthorized: Admin access required" (403 error)

4. Restore admin role:
   ```bash
   php artisan tinker
   > $user = User::find(1)
   > $role = Role::where('name', 'admin')->first()
   > $user->roles()->attach($role)
   > exit
   ```

## Browser Console Validation

### Expected Console Output

When unlock succeeds, you should see (in order):
```
[DEBUG] Unlock batch request: {
  url: "/api/mark-entry/submission/unlock/1",
  batchId: 1,
  reasonLength: 50,
  hasCSRFToken: true
}

[DEBUG] Response received: {
  status: 200,
  statusText: "OK",
  headers: {content-type: "application/json", ...}
}

[DEBUG] API response data: {
  success: true,
  message: "Batch unlocked successfully",
  data: {...}
}
```

### Forbidden Patterns (Indicate Bugs)

❌ 419 status code → CSRF token issue
❌ 401 status code → Authentication issue
❌ 403 status code → Authorization (user not admin)
❌ 500 status code → Server error (check Laravel logs)
❌ Non-JSON response with 200 status → Response type issue

## Server Log Monitoring

In a separate terminal, watch the logs:

```bash
cd /home/prosmart-technologies/SOL/irms
tail -f storage/logs/laravel.log | grep -i unlock
```

### Expected Log Entries

```
[2026-02-14 10:30:45] local.INFO: Unlock batch request received [
  "batchId" => 1,
  "user_id" => 5,
  "authenticated" => true,
  ...
]

[2026-02-14 10:30:46] local.INFO: Admin check [
  "user_id" => 5,
  "is_admin" => true,
  ...
]

[2026-02-14 10:30:46] local.INFO: Batch unlocked successfully [
  "batch_id" => 1,
  "unlocked_by" => 5,
  ...
]
```

## Database Verification

Verify the batch status changed in the database:

```bash
php artisan tinker

# Check batch status
> $batch = \App\Models\MarkImportBatch::find(1)
> $batch->lifecycle_state  // Should be "unlocked"
> $batch->unlocked_at      // Should show current timestamp
```

## Step-by-Step Detailed Test

### Setup (1 minute)
```bash
# 1. Clear caches
php artisan cache:clear
php artisan view:clear
php artisan route:cache --force

# 2. Start server (if not running)
php artisan serve
```

### Core Test (5 minutes)
1. Open browser: http://127.0.0.1:8000/mark-entry/acsee
2. Open DevTools: F12
3. Find a submitted batch in the list
4. Click "Unlock" button
5. Enter unlock reason (>10 characters)
6. Click "Unlock Batch" button
7. Watch Network tab for request
8. Verify response status is 200
9. Verify modal closes and message appears

### Extended Test (10 minutes)
1. Repeat core test with multiple batches
2. Test with invalid inputs (< 10 chars)
3. Test error cases (network throttling, etc.)
4. Check browser console for expected logs
5. Check server logs for unlock entries
6. Verify database change with tinker

## Checklist for QA

### Functional Tests
- [ ] Button appears for admin users only
- [ ] Modal opens on button click
- [ ] Reason textarea accepts input
- [ ] Character counter works correctly
- [ ] Button disabled when reason < 10 chars
- [ ] Button enabled when reason ≥ 10 chars
- [ ] "Cancel" button closes modal
- [ ] Loading state shows spinner
- [ ] Success message displays
- [ ] Modal closes after success
- [ ] Batch status updates in list

### Technical Tests
- [ ] CSRF token present in page (DevTools)
- [ ] CSRF token sent in request headers
- [ ] Request method is POST
- [ ] Response status is 200 OK
- [ ] Response content-type is application/json
- [ ] Response body has required fields
- [ ] Console logs show expected messages
- [ ] Server logs show unlock entries

### Error Handling Tests
- [ ] Invalid reason shows error message
- [ ] Network error shows appropriate message
- [ ] Timeout (>30s) shows timeout message
- [ ] Server error shows API error message
- [ ] Modal doesn't close on error
- [ ] User can retry after error

### Browser Compatibility
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile browser (iOS Safari)

## Common Issues & Troubleshooting

### Issue: 419 Page Expired Error
**Cause**: CSRF middleware not applied
**Fix**: Verify `routes/mark-entry.php` line 65 has `'web'` middleware
**Test**: `php artisan route:list | grep unlock`

### Issue: Modal Stuck in "Processing..."
**Cause**: Request never completes
**Solution**:
1. Check server logs for errors
2. Verify CSRF token is being sent
3. Check Network tab for hanging request
4. Clear browser cache: Ctrl+Shift+Delete

### Issue: "CSRF token not found" Error
**Cause**: Meta tag missing from page
**Fix**: Verify `resources/views/layout.blade.php` line 6
**Test**: `grep -n "csrf-token" resources/views/layout.blade.php`

### Issue: "Unauthorized" Error
**Cause**: User doesn't have admin role
**Fix**: Verify user has admin role in database
**Test**: `php artisan tinker` → `User::find(1)->hasRole('admin')`

## Performance Testing

### Expected Performance
- Request time: < 2 seconds
- Modal close delay: < 1 second
- Page update time: < 500ms

### Load Testing (Optional)
```bash
# Simulate 10 concurrent unlock requests
for i in {1..10}; do
  curl -X POST http://127.0.0.1:8000/api/mark-entry/submission/unlock/1 \
    -H "X-CSRF-TOKEN: $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"reason":"Load test unlock request number '$i'"}' &
done
wait
```

## Sign-Off Template

```
[ ] Core functionality test: PASS / FAIL
[ ] Error handling test: PASS / FAIL
[ ] Browser compatibility test: PASS / FAIL
[ ] Server logs verified: PASS / FAIL
[ ] Database state verified: PASS / FAIL

Test Date: _____________
Tester Name: _____________
Notes: _________________________

Status: ✅ READY / ❌ NEEDS FIXES
```

## Still Having Issues?

1. **Check Logs**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Verify Configuration**
   ```bash
   php artisan config:show app
   php artisan route:list | grep unlock
   ```

3. **Clear Everything**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:cache --force
   composer dump-autoload
   ```

4. **Check Middleware**
   ```bash
   grep -r "web.*middleware\|VerifyCsrfToken" app/Http/Middleware/
   ```

5. **Contact Development**
   - Share browser console screenshot
   - Share server logs: `tail -100 storage/logs/laravel.log`
   - Share Network tab screenshot with response
