# Moderation & Submission Workflows - Test Cases

**Date:** 2026-02-14  
**Scope:** Approval, Rejection, Lock, Unlock workflows  
**Environment:** All (Dev, Staging, Production)

---

## 🧪 Test Case Structure

Each test case includes:
- **ID:** Unique identifier
- **Title:** What is being tested
- **Prerequisites:** Setup required
- **Steps:** How to execute
- **Expected Result:** What should happen
- **Pass/Fail:** Test outcome
- **Notes:** Additional observations

---

## ✅ APPROVAL WORKFLOW TESTS

### TC-001: Approve with Optional Feedback

**Title:** User can approve batch with optional feedback  
**Prerequisites:** Logged in as moderator, batch in "awaiting_moderation" state  

**Steps:**
1. Navigate to "Pending Review" dashboard section
2. Find a batch needing approval
3. Click "✅ Approve" button
4. Modal appears with batch ID
5. Enter feedback text: "Marks look good, data complete"
6. Click "Approve" button
7. Observe success message

**Expected Result:**
- ✅ Modal displays correctly
- ✅ Feedback field accepts text
- ✅ API call succeeds
- ✅ Success toast shows "Batch approved successfully"
- ✅ Modal closes
- ✅ Batch state changes to "approved" in database
- ✅ Audit trail records the approval with feedback

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-002: Approve Without Feedback

**Title:** User can approve batch without adding feedback  
**Prerequisites:** Logged in as moderator, batch in "awaiting_moderation" state  

**Steps:**
1. Navigate to "Pending Review" section
2. Find a batch to approve
3. Click "✅ Approve" button
4. Modal appears
5. Leave feedback field empty
6. Click "Approve" button

**Expected Result:**
- ✅ Feedback field is optional (no validation required)
- ✅ Approve button works without feedback
- ✅ Success message appears
- ✅ Batch transitions to "approved"
- ✅ Audit trail shows approval with null feedback

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-003: Character Counter on Feedback

**Title:** Character counter updates in real-time  
**Prerequisites:** Approval modal open  

**Steps:**
1. Click "✅ Approve" on a batch
2. Modal opens
3. Click in feedback field
4. Type some text: "This is test feedback"
5. Observe character counter
6. Add more text to reach 1000 characters
7. Try to exceed 1000 characters

**Expected Result:**
- ✅ Character counter shows "0/1000" initially
- ✅ Counter updates as text is entered
- ✅ After "This is test feedback" (21 chars), shows "21/1000"
- ✅ Text input stops accepting characters at 1000 limit
- ✅ Counter shows "1000/1000" at maximum

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-004: Modal Closes on Successful Approval

**Title:** Modal closes and form resets after successful approval  
**Prerequisites:** Approval modal with feedback entered  

**Steps:**
1. Enter feedback and click "Approve"
2. Wait for API response
3. Observe modal behavior
4. Open approve modal again for different batch
5. Check if feedback field is empty

**Expected Result:**
- ✅ Modal closes immediately after success
- ✅ Feedback field is cleared in state
- ✅ Next approval shows empty feedback field
- ✅ selectedBatchId is reset to null

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-005: Unauthorized User Cannot Approve

**Title:** Non-moderator user cannot approve batches  
**Prerequisites:** Logged in as non-moderator user (e.g., student, teacher)  

**Steps:**
1. Try to navigate to approval modal
2. Attempt to call approval API directly
3. Submit approval request without proper permission

**Expected Result:**
- ✅ Approve button not visible or disabled
- ✅ API returns 403 Forbidden error
- ✅ Error message: "Unauthorized" appears
- ✅ Batch state remains unchanged

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-006: Loading State During Approval

**Title:** UI shows loading state while approval is processing  
**Prerequisites:** Approval modal ready to submit  

**Steps:**
1. Enter feedback and click "Approve"
2. Observe button state while request is in flight
3. Wait for API response

**Expected Result:**
- ✅ Button text changes to "Processing..." with spinner
- ✅ Button is disabled during request
- ✅ Cursor may show "not-allowed"
- ✅ After response, button returns to normal state

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## ❌ REJECTION WORKFLOW TESTS

### TC-101: Reject with Valid Reason

**Title:** User can reject batch with valid reason  
**Prerequisites:** Logged in as moderator, batch in "awaiting_moderation" state  

**Steps:**
1. Navigate to "Pending Review" section
2. Find a batch with issues
3. Click "❌ Reject" button
4. Modal appears
5. Enter rejection reason: "Candidate marks exceed maximum possible"
6. Click "Reject" button

**Expected Result:**
- ✅ Modal displays correctly
- ✅ Reason field is required (validation shows)
- ✅ Text "Candidate marks exceed maximum possible" (44 chars) is accepted
- ✅ Success message: "Batch rejected successfully"
- ✅ Modal closes
- ✅ Batch state changes to "rejected"
- ✅ `requires_resubmission` set to true
- ✅ Rejection reason stored in database

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-102: Reject Fails with Short Reason

**Title:** Rejection with less than 10 characters is rejected  
**Prerequisites:** Rejection modal open  

**Steps:**
1. Click "❌ Reject" on a batch
2. Modal appears
3. Enter text: "Too short" (9 characters)
4. Observe submit button
5. Try to click "Reject" button

**Expected Result:**
- ✅ Character counter shows "9/1000"
- ✅ Shows warning: "(minimum 10 required)" in red
- ✅ "Reject" button is disabled (greyed out)
- ✅ Button cannot be clicked
- ✅ No API call is made

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-103: Reject with Exactly 10 Characters

**Title:** Rejection with exactly 10 characters is accepted  
**Prerequisites:** Rejection modal open  

**Steps:**
1. Click "❌ Reject" on a batch
2. Enter text: "Exactly ten" (11 chars, counting space)
3. Observe button state
4. Click "Reject"

**Expected Result:**
- ✅ Character counter shows correct count
- ✅ "Reject" button becomes enabled (blue)
- ✅ Button can be clicked
- ✅ API call succeeds
- ✅ Batch rejected successfully

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-104: Character Counter on Rejection Reason

**Title:** Real-time character counter on rejection field  
**Prerequisites:** Rejection modal open  

**Steps:**
1. Click in reason field
2. Type "Problem found"
3. Check counter value
4. Delete characters
5. Add more text to reach near limit
6. Try to exceed 1000 characters

**Expected Result:**
- ✅ Counter shows "14/1000" for "Problem found"
- ✅ Counter decreases as text is deleted
- ✅ Counter reaches "1000/1000" at maximum
- ✅ Input stops accepting text at 1000 limit

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-105: Modal Closes on Successful Rejection

**Title:** Rejection modal closes after successful rejection  
**Prerequisites:** Rejection completed successfully  

**Steps:**
1. Submit rejection with valid reason
2. Observe modal behavior
3. Wait for success message
4. Try to open reject modal again

**Expected Result:**
- ✅ Modal closes automatically
- ✅ Success toast displayed for 5 seconds
- ✅ Reason field cleared in component state
- ✅ Next reject modal opens with empty field

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-106: Unauthorized User Cannot Reject

**Title:** Non-moderator cannot reject batches  
**Prerequisites:** Logged in as non-moderator  

**Steps:**
1. Try to access rejection modal
2. Attempt direct API call to reject endpoint
3. Send rejection request

**Expected Result:**
- ✅ Reject button not visible
- ✅ API returns 403 Forbidden
- ✅ Error message shown
- ✅ Batch state unchanged

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 🔒 LOCK WORKFLOW TESTS

### TC-201: Lock with Confirmation

**Title:** User can lock batch with "LOCK" confirmation  
**Prerequisites:** Logged in as submission manager, batch in "approved" state  

**Steps:**
1. Navigate to "Lock Status" or "Submit Marks" section
2. Find an approved batch
3. Click "🔒 Lock & Submit" button
4. Modal appears with warning
5. Read the warning about permanence
6. Type "LOCK" in confirmation field
7. Click "Lock & Submit" button

**Expected Result:**
- ✅ Modal displays permanence warning
- ✅ Lists consequences (no further modifications, etc.)
- ✅ Confirmation field visible
- ✅ Lock button is disabled initially
- ✅ After typing "LOCK", button becomes enabled
- ✅ Click succeeds
- ✅ Success message: "Batch locked and submitted successfully"
- ✅ Modal closes
- ✅ Batch state changes to "submitted"
- ✅ `MarkBatchApproval` record created
- ✅ Audit trail records the lock

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-202: Lock Fails Without Confirmation Text

**Title:** Lock fails if "LOCK" not typed  
**Prerequisites:** Lock modal open  

**Steps:**
1. Click "🔒 Lock & Submit" on a batch
2. Modal appears
3. Leave confirmation field empty
4. Try to click "Lock & Submit" button

**Expected Result:**
- ✅ Button is disabled (greyed out)
- ✅ Cannot click button
- ✅ No API call made
- ✅ Must type "LOCK" to enable

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-203: Lock Case-Insensitive Confirmation

**Title:** Confirmation text "LOCK" works in any case  
**Prerequisites:** Lock modal open  

**Steps:**
1. Click "🔒 Lock & Submit"
2. Try typing "lock" (lowercase)
3. Button should enable
4. Try typing "Lock" (mixed case)
5. Button should remain enabled
6. Try typing "LOCK" (uppercase)
7. Button should remain enabled

**Expected Result:**
- ✅ "lock" enables button
- ✅ "Lock" enables button
- ✅ "LOCK" enables button
- ✅ "LoCk" enables button
- ✅ Any case variation accepted
- ✅ Button is case-insensitive

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-204: Permanent Lock Cannot Be Undone by Manager

**Title:** After locking, batch cannot be modified  
**Prerequisites:** Batch successfully locked  

**Steps:**
1. Lock a batch successfully
2. Try to modify batch data
3. Try to change marks
4. Try to change subject
5. Try to unlock (as non-admin)

**Expected Result:**
- ✅ Batch marked as "submitted"
- ✅ All modification attempts fail
- ✅ System prevents any changes
- ✅ Unlock button only visible to admins
- ✅ Manager cannot reverse the lock

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-205: Loading State During Lock

**Title:** UI shows loading state during lock processing  
**Prerequisites:** Lock modal with confirmation entered  

**Steps:**
1. Type "LOCK" in confirmation field
2. Click "Lock & Submit"
3. Observe button state while request in flight
4. Wait for response

**Expected Result:**
- ✅ Button text changes to "Processing..."
- ✅ Spinner icon shown
- ✅ Button disabled during request
- ✅ After response, button returns to normal or modal closes

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-206: Unauthorized User Cannot Lock

**Title:** Non-submission-manager cannot lock  
**Prerequisites:** Logged in as user without `can:mark-entry.lock`  

**Steps:**
1. Try to access lock modal
2. Attempt direct API call to lock endpoint
3. Send lock request

**Expected Result:**
- ✅ Lock button not visible or disabled
- ✅ API returns 403 Forbidden
- ✅ Error message displayed
- ✅ Batch state unchanged

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 🔓 UNLOCK WORKFLOW TESTS (ADMIN ONLY)

### TC-301: Unlock with Valid Reason

**Title:** Admin can unlock batch with documented reason  
**Prerequisites:** Logged in as admin, batch in "submitted" state  

**Steps:**
1. Navigate to "Lock Status" section
2. Find a locked batch
3. Click "(Admin) Unlock" button
4. Modal appears with "Admin action" notice
5. Enter reason: "Candidate provided evidence of correction"
6. Click "Unlock Batch" button

**Expected Result:**
- ✅ Modal shows admin shield indicator
- ✅ Reason field is required
- ✅ Text accepted (39+ characters)
- ✅ Success message: "Batch unlocked successfully"
- ✅ Batch reverts to "approved" state
- ✅ Unlock reason stored in audit trail
- ✅ Modal closes

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-302: Unlock Fails with Short Reason

**Title:** Unlock fails with less than 10 character reason  
**Prerequisites:** Unlock modal open  

**Steps:**
1. Click "(Admin) Unlock"
2. Modal appears
3. Type reason: "Error" (5 characters)
4. Observe button state
5. Try to click "Unlock Batch"

**Expected Result:**
- ✅ Character counter shows "5/1000"
- ✅ Shows warning: "(minimum 10 required)" in red
- ✅ "Unlock Batch" button is disabled
- ✅ Cannot be clicked
- ✅ No API call made

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-303: Non-Admin Cannot Unlock

**Title:** Non-admin users cannot unlock batches  
**Prerequisites:** Logged in as non-admin user  

**Steps:**
1. Try to access unlock modal
2. Look for unlock button
3. Attempt direct API call to unlock endpoint
4. Send unlock request

**Expected Result:**
- ✅ Unlock button not visible
- ✅ "(Admin) Unlock" section may not appear
- ✅ API returns 403 Forbidden
- ✅ Error message displayed
- ✅ Batch remains locked

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-304: Unlock Reason Stored in Audit Trail

**Title:** Unlock reason is properly logged  
**Prerequisites:** Batch successfully unlocked  

**Steps:**
1. Unlock a batch with reason: "Marker requested correction"
2. Check database `audit_trail` table
3. Find the unlock record
4. Verify reason is stored

**Expected Result:**
- ✅ Audit trail has entry with event: "unlock_requested"
- ✅ Reason: "Marker requested correction" in audit_data
- ✅ User ID matches admin who unlocked
- ✅ Timestamp recorded correctly

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-305: Batch Reverts to Approved After Unlock

**Title:** Batch state correctly reverts to "approved"  
**Prerequisites:** Batch successfully unlocked  

**Steps:**
1. Check batch state before unlock: should be "submitted"
2. Unlock batch with valid reason
3. Query batch state after unlock
4. Verify state in lifecycle_history

**Expected Result:**
- ✅ Before unlock: `lifecycle_state` = "submitted"
- ✅ After unlock: `lifecycle_state` = "approved"
- ✅ Lifecycle history updated
- ✅ Can be submitted again by manager

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-306: Loading State During Unlock

**Title:** UI shows loading state while unlock processing  
**Prerequisites:** Unlock modal with reason entered  

**Steps:**
1. Enter valid reason
2. Click "Unlock Batch"
3. Observe button state during request
4. Wait for API response

**Expected Result:**
- ✅ Button text changes to "Processing..."
- ✅ Spinner icon shown
- ✅ Button disabled during request
- ✅ After response, modal closes

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 🔔 TOAST NOTIFICATION TESTS

### TC-401: Success Toast Appears on Approval

**Title:** Green success toast displayed on successful approval  
**Prerequisites:** Batch successfully approved  

**Steps:**
1. Approve a batch
2. Observe toast notification
3. Check message text
4. Wait 5 seconds

**Expected Result:**
- ✅ Green toast appears in top-right corner
- ✅ Green checkmark icon shown
- ✅ Message: "Batch approved successfully"
- ✅ Close button visible
- ✅ Auto-dismisses after 5 seconds

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-402: Error Toast on API Failure

**Title:** Red error toast displayed on API error  
**Prerequisites:** API unavailable or error response  

**Steps:**
1. Simulate API error (network down or 500 error)
2. Try to approve batch
3. Observe error notification

**Expected Result:**
- ✅ Red error toast appears
- ✅ Red exclamation icon shown
- ✅ Error message displayed
- ✅ Specific error text included
- ✅ Close button available
- ✅ Auto-dismisses after 5 seconds

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-403: Toast Auto-Dismiss After 5 Seconds

**Title:** Toast automatically dismisses after 5 seconds  
**Prerequisites:** Toast notification displayed  

**Steps:**
1. Trigger approval (shows success toast)
2. Watch for 5 seconds
3. Observe toast behavior
4. Toast should disappear automatically

**Expected Result:**
- ✅ Toast visible for approximately 5 seconds
- ✅ Automatically fades or disappears
- ✅ No manual action needed
- ✅ Close button still works if clicked earlier

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-404: Manual Toast Close Button

**Title:** User can manually close toast before auto-dismiss  
**Prerequisites:** Toast notification displayed  

**Steps:**
1. Trigger approval (shows success toast)
2. Click close button (×) on toast
3. Observe behavior

**Expected Result:**
- ✅ Toast closes immediately
- ✅ × button is clickable
- ✅ Toast disappears right away
- ✅ User doesn't have to wait 5 seconds

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-405: Toast Types Display Correctly

**Title:** All toast types display with correct colors and icons  
**Prerequisites:** Ability to trigger different actions  

**Steps:**
1. Approve batch (success toast)
2. Check success toast styling
3. Trigger error (error toast)
4. Check error toast styling
5. Trigger other actions

**Expected Result:**
- ✅ Success toast: Green background, checkmark icon
- ✅ Error toast: Red background, exclamation icon
- ✅ Info toast: Blue background, info icon (if used)
- ✅ Warning toast: Yellow background, warning icon (if used)

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 🔐 AUTHORIZATION & PERMISSION TESTS

### TC-501: Permission Matrix Enforcement

**Title:** Each action enforces correct permissions  
**Prerequisites:** Multiple user accounts with different roles  

**Steps:**
1. Login as Student - try all actions
2. Login as Moderator - try moderation actions
3. Login as Manager - try lock actions
4. Login as Admin - try all including unlock

**Expected Results:**
| User | Approve | Reject | Lock | Unlock |
|------|---------|--------|------|--------|
| Student | ❌ | ❌ | ❌ | ❌ |
| Moderator | ✅ | ✅ | ❌ | ❌ |
| Manager | ❌ | ❌ | ✅ | ❌ |
| Admin | ✅ | ✅ | ✅ | ✅ |

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-502: API Validates User Permission

**Title:** API endpoint validates user has required permission  
**Prerequisites:** API endpoint accessible  

**Steps:**
1. Get auth token for non-moderator user
2. Send POST to `/api/mark-entry/moderation/batch/{id}/approve`
3. Observe response

**Expected Result:**
- ✅ API returns 403 Forbidden
- ✅ Response includes "Unauthorized" or "Forbidden"
- ✅ No batch state change
- ✅ No audit trail entry

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 📊 AUDIT TRAIL TESTS

### TC-601: All Actions Logged to Audit Trail

**Title:** Every action creates audit trail entry  
**Prerequisites:** Actions completed successfully  

**Steps:**
1. Approve a batch
2. Check `audit_trail` table
3. Reject a batch
4. Check audit trail again
5. Lock a batch
6. Check audit trail again
7. Unlock a batch
8. Check audit trail again

**Expected Result:**
- ✅ Approval logged with user_id, timestamp, feedback
- ✅ Rejection logged with user_id, timestamp, reason
- ✅ Lock logged with user_id, timestamp
- ✅ Unlock logged with user_id, timestamp, reason
- ✅ All records have batch_id, event type, data

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-602: Audit Trail Timestamps Accurate

**Title:** Timestamps in audit trail are correct  
**Prerequisites:** Audit entry created  

**Steps:**
1. Note current time: 14:32:45
2. Perform action (approve batch)
3. Check audit trail timestamp
4. Compare with noted time

**Expected Result:**
- ✅ Timestamp matches action time closely (within 1 second)
- ✅ Uses server time (not client time)
- ✅ Format is consistent (ISO format)
- ✅ Timezone is correct

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 🔄 INTEGRATION TESTS

### TC-701: Complete Moderation to Submission Flow

**Title:** Full workflow from submission to locking  
**Prerequisites:** Batch in entry state  

**Steps:**
1. Upload batch marks
2. Batch enters "validated" state
3. Send to moderation (should auto-transition)
4. Approve batch as moderator
5. Verify batch in "approved" state
6. Lock batch as manager
7. Verify batch in "submitted" state
8. Attempt to unlock as admin
9. Verify batch reverted to "approved"

**Expected Result:**
- ✅ All state transitions occur correctly
- ✅ Each step logs to audit trail
- ✅ UI updates after each action
- ✅ Permissions enforced at each step
- ✅ Final state is correct

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

### TC-702: Rejection and Resubmission Flow

**Title:** Rejected batch can be resubmitted  
**Prerequisites:** Batch in "awaiting_moderation" state  

**Steps:**
1. Reject batch with reason
2. Batch enters "rejected" state
3. Verify submitter sees rejection reason
4. Submitter corrects issues
5. Resubmit batch
6. Batch returns to "awaiting_moderation"
7. Approve batch
8. Batch proceeds to "approved" state

**Expected Result:**
- ✅ Rejection captured correctly
- ✅ Batch marked for resubmission
- ✅ Reason communicated
- ✅ Resubmission possible
- ✅ Full workflow continues

**Pass:** ☐  **Fail:** ☐  **Notes:** ___________

---

## 📋 SUMMARY

**Total Test Cases:** 36  
**Categories:**
- Approval: 6 tests
- Rejection: 6 tests
- Lock: 6 tests
- Unlock: 6 tests
- Toast: 5 tests
- Authorization: 2 tests
- Audit: 2 tests
- Integration: 2 tests

**Execution Date:** ________________  
**Executed By:** ________________  
**Overall Result:** ✅ **PASS** / ❌ **FAIL**

---

**Test Plan Version:** 1.0  
**Last Updated:** 2026-02-14
