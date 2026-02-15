# Unlock Batch Functionality Test Guide

## Overview
This document describes the comprehensive fix applied to the "Unlock Batch" modal that was stuck in a "Processing..." state.

## Root Causes Fixed

### 1. **Frontend: JavaScript Handler**
- **Issue**: The original code was using a fire-and-forget approach without proper async/await
- **Fix**: Rewrote `unlockBatchConfirm()` as an async function with proper error handling
- **File**: `resources/views/mark-entry/index.blade.php` (lines 3523-3569)

### 2. **Frontend: Correct API Endpoint**
- **Issue**: Code was calling `/mark-entry/unlock-batch/{batchId}` instead of the API endpoint
- **Fix**: Updated to use `/api/mark-entry/submission/unlock/{batchId}` which is the correct API route
- **File**: `resources/views/mark-entry/index.blade.php` (line 3538)

### 3. **Backend: UnlockBatchController Implementation**
- **Issue**: Controller had only a test response without actual logic
- **Fix**: Fully implemented the unlock logic with:
  - Authentication & authorization checks
  - Batch existence validation
  - Request validation (reason field minimum 10 characters)
  - Audit trail logging
  - Service integration for batch state updates
  - Proper error responses with HTTP status codes
- **File**: `app/Http/Controllers/MarkEntry/Api/UnlockBatchController.php`

### 4. **Route Configuration**
- **Status**: ✅ Already configured correctly with `web` middleware
- **File**: `routes/mark-entry.php` (line 82)

## Testing Steps

### Prerequisites
1. Clear all caches: `php artisan cache:clear && php artisan view:clear`
2. Ensure you're logged in as an admin user with `mark-entry.admin` permission
3. Navigate to the Mark Entry dashboard

### Test Case 1: Basic Unlock Success
1. Go to the "Submission & Locking" section
2. Find a submitted/locked batch
3. Click the "Unlock Batch" button
4. Enter a reason (minimum 10 characters)
5. Click "Submit"

**Expected Results**:
- Modal should close immediately (not freeze)
- Success message should display
- Page should refresh automatically after 500ms
- Batch should transition to "ready for resubmission" state

### Test Case 2: Validation Errors
1. Open the unlock modal
2. Leave the reason field empty
3. Click "Submit" button

**Expected Results**:
- Submit button should remain disabled
- No request sent to server
- Modal stays open

### Test Case 3: Short Reason Error
1. Open the unlock modal
2. Enter less than 10 characters in the reason field
3. Click "Submit" button

**Expected Results**:
- Submit button should be disabled
- No request sent to server
- Error message: "Please enter at least 10 characters for the unlock reason."

### Test Case 4: Network Error Handling
1. Open browser DevTools → Network tab
2. Throttle to "Offline" mode
3. Try to unlock a batch with valid reason
4. Restore online mode

**Expected Results**:
- Error message should display: "Failed to unlock batch: [error description]"
- Modal should close
- Button state should reset
- No UI freeze

### Test Case 5: Authorization Check
1. Logout and login as a non-admin user
2. Try to access the unlock endpoint directly via console:
```javascript
fetch('/api/mark-entry/submission/unlock/1', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ reason: 'Test reason for unlock' })
}).then(r => r.json()).then(d => console.log(d));
```

**Expected Results**:
- Response status: 403 Forbidden
- Message: "Unauthorized: Admin access required"

## Browser Console Testing

### Test Modal State Management
```javascript
// Check if modal closes properly
console.log('Modal visible:', markEntryManager().showUnlockBatchModal);

// Check button state
console.log('Unlocking state:', markEntryManager().isUnlocking);

// Test validation
markEntryManager().selectedBatchId = 123;
markEntryManager().unlockReason = 'Testing the unlock functionality here';
markEntryManager().unlockBatchConfirm();
```

## Logs to Monitor

Check Laravel logs for proper audit trail:
```bash
tail -f storage/logs/laravel.log | grep -i unlock
```

Expected log entries:
1. "Unlock batch request" - Initial request logged
2. "Batch found" - Batch retrieved successfully
3. "Admin check" - Authorization verified
4. "Unlock batch error" or success response

## Checklist

- [ ] JavaScript validation working (button disabled until 10 chars)
- [ ] Modal closes after submission attempt
- [ ] Loading state (spinner) visible while processing
- [ ] Success message displayed on success
- [ ] Error message displayed on failure
- [ ] Page auto-refreshes after unlock
- [ ] No UI freeze at any point
- [ ] Audit trail recorded
- [ ] Authorization properly enforced
- [ ] CSRF token protection active

## Files Changed

1. **resources/views/mark-entry/index.blade.php**
   - Rewrote `unlockBatchConfirm()` with async/await and error handling
   - Added proper loading state management
   - Implemented auto-refresh logic

2. **app/Http/Controllers/MarkEntry/Api/UnlockBatchController.php**
   - Fully implemented unlock logic with authentication, validation, and service integration
   - Added comprehensive logging and error responses

3. **routes/mark-entry.php**
   - ✅ Already correctly configured with `web` and `auth` middleware

## Deployment Notes

1. Run `php artisan cache:clear` after deployment
2. Monitor logs for any "Unlock batch error" messages
3. Test with at least one locked batch before marking as complete
4. Verify admin user can see and interact with unlock button

## Troubleshooting

### Modal Still Freezes
1. Check browser console for JavaScript errors
2. Check Laravel logs for exception stack traces
3. Verify CSRF token is present: `document.querySelector('meta[name="csrf-token"]').content`
4. Clear browser cache

### Button Stays Disabled
1. Verify reason field has >= 10 characters
2. Check browser console for validation errors
3. Ensure selectedBatchId is set

### Network Errors (419, 403)
1. Verify user is authenticated: `auth()->check()` in logs
2. Check user has admin role: Review user roles in database
3. Verify CSRF token in meta tag matches session token
4. Check X-CSRF-TOKEN header is being sent

### Batch Not Found (404)
1. Verify batch ID exists in database
2. Check query: `SELECT * FROM mark_import_batches WHERE id = {batchId}`
3. Ensure batch is in correct state for unlock

## Success Criteria

✅ Modal opens and closes properly
✅ Form validation works (button enabled/disabled correctly)
✅ Request sent with correct API endpoint
✅ Backend processes request successfully
✅ Audit trail records the action
✅ Batch state updates correctly
✅ UI refreshes with updated batch status
✅ No errors in browser console or application logs
✅ Error handling for network/auth failures
✅ CSRF protection active on all requests
