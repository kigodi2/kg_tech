# Mark Entry: Moderation & Submission Workflows Implementation

**Status:** Phase 3C-3 Batch 2-3 Complete
**Date:** 2026-02-14
**Overview:** Interactive moderation (approve/reject) and submission (lock/unlock) workflows implemented with full audit logging.

---

## ✅ Completed Components

### 1. Backend API Endpoints

#### Moderation Actions (New)
- **POST** `/api/mark-entry/moderation/batch/{batch}/approve`
  - Approves a batch, transitions to "approved" state
  - Parameters: `feedback` (optional, max 1000 chars)
  - Response includes: `batch_id`, `lifecycle_state`, `approved_at`
  - Authorization: `can:mark-entry.moderate`

- **POST** `/api/mark-entry/moderation/batch/{batch}/reject`
  - Rejects a batch, marks for resubmission
  - Parameters: `reason` (required, min 10 chars, max 1000)
  - Response includes: `batch_id`, `lifecycle_state`, `rejected_at`, `rejection_reason`
  - Authorization: `can:mark-entry.moderate`

#### Submission Actions (New)
- **POST** `/api/mark-entry/submission/lock/{batch}`
  - Locks batch, prevents further modifications
  - Parameters: none
  - Response includes: `batch_id`, `lifecycle_state`, `locked_at`, `locked_by`
  - Authorization: `can:mark-entry.lock`
  - Validates: Batch must be in "approved" state

- **POST** `/api/mark-entry/submission/unlock/{batch}`
  - Unlocks batch (admin only), allows resubmission
  - Parameters: `reason` (required, min 10 chars, max 1000)
  - Response includes: `batch_id`, `lifecycle_state`, `unlocked_at`, `unlocked_by`
  - Authorization: `can:admin` (must be super-admin)
  - Audit logging: Unlock reason stored in audit trail

---

### 2. Frontend Components (Blade/Alpine.js)

#### Modal Components Created

**`_approve_batch_modal.blade.php`**
- Simple approval workflow
- Optional feedback field
- Shows batch ID for confirmation
- Visual confirmation of action

**`_reject_batch_modal.blade.php`**
- Required rejection reason (min 10 characters)
- Character counter with validation
- Warning about resubmission requirement
- Disabled submit button until reason is valid

**`_lock_batch_modal.blade.php`**
- Critical action confirmation
- Requires typing "LOCK" to confirm (case-insensitive)
- Shows permanent lock warnings
- Lists consequences of locking

**`_unlock_batch_modal.blade.php`**
- Admin-only restricted action
- Requires unlock reason (min 10 characters)
- Admin shield indicator
- Audit logging notice

**`_toast_notification.blade.php`**
- Reusable notification component
- 4 toast types: success, error, info, warning
- Auto-dismisses after 5 seconds
- Manual close button available
- Color-coded by type with icons

#### Alpine.js Methods Added to `markEntryManager()`

**Moderation Methods:**
```javascript
openApproveBatchModal(batchId)      // Opens approval modal
approveBatchConfirm()                // Submits approval
openRejectBatchModal(batchId)        // Opens rejection modal
rejectBatchConfirm()                 // Submits rejection
```

**Submission Methods:**
```javascript
openLockBatchModal(batchId)          // Opens lock confirmation
lockBatchConfirm()                   // Submits lock
openUnlockBatchModal(batchId)        // Opens unlock modal
unlockBatchConfirm()                 // Submits unlock
```

**Notification Methods:**
```javascript
showMessage(message, type, details)  // Shows toast notification
closeToast()                         // Manually closes toast
```

#### State Variables Added

**Moderation State:**
```javascript
showApproveBatchModal: boolean
showRejectBatchModal: boolean
selectedBatchId: string|null
approveFeedback: string
rejectReason: string
isApproving: boolean
isRejecting: boolean
```

**Submission State:**
```javascript
showLockBatchModal: boolean
showUnlockBatchModal: boolean
lockConfirmText: string
unlockReason: string
isLocking: boolean
isUnlocking: boolean
```

**Notification State:**
```javascript
toastMessage: string
toastType: 'success'|'error'|'info'|'warning'
toastDetails: string
toastTimeout: number|null
```

---

### 3. Routes Configuration

Added to `/routes/mark-entry.php`:

```php
// Moderation action routes
Route::post('batch/{batch}/approve', [...'approveBatchAction']);
Route::post('batch/{batch}/reject', [...'rejectBatchAction']);

// Submission action routes
Route::post('lock/{batch}', [...'lockBatchAction']);
Route::post('unlock/{batch}', [...'unlockBatchAction'])->middleware('can:admin');
```

---

## 🔄 Workflow Implementation

### Moderation Workflow (Approve/Reject)

1. **User clicks "Approve" button** on a batch in the dashboard
2. `openApproveBatchModal(batchId)` triggered
3. Modal displays:
   - Batch ID (for reference)
   - Optional feedback field
   - Confirmation notice
4. User enters optional feedback
5. User clicks "Approve" button
6. `approveBatchConfirm()` executes:
   - Validates input
   - Calls API: `POST /api/mark-entry/moderation/batch/{batchId}/approve`
   - Service transitions batch to "approved" state
   - Creates `MarkModerationReview` record
   - Logs action to audit trail
7. Success toast shown
8. Modal closes, form reset
9. Dashboard reloads via `loadPendingReview()`

**Rejection follows same flow but:**
- Requires minimum 10-character reason
- Marks batch as "rejected"
- Sets `requires_resubmission = true`
- Stores rejection reason in batch record

---

### Submission Workflow (Lock/Unlock)

#### Lock Process
1. **User clicks "Lock & Submit" button** on approved batch
2. `openLockBatchModal(batchId)` triggered
3. Modal displays:
   - Critical warning about permanence
   - List of lock consequences
   - Confirmation text input (type "LOCK")
4. User types "LOCK" in confirmation field
5. Submit button becomes enabled
6. User clicks "Lock & Submit"
7. `lockBatchConfirm()` executes:
   - Validates confirmation text
   - Calls API: `POST /api/mark-entry/submission/lock/{batchId}`
   - Service transitions batch to "submitted" state
   - Creates `MarkBatchApproval` record
   - Prevents all future modifications
8. Success toast shown
9. Dashboard reloads

#### Unlock Process (Admin Only)
1. **Admin clicks "Unlock" button** on submitted batch
2. `openUnlockBatchModal(batchId)` triggered
3. Modal displays:
   - Admin shield indicator
   - Required reason field
   - Audit logging notice
4. Admin enters unlock reason (min 10 characters)
5. Submit button becomes enabled
6. Admin clicks "Unlock Batch"
7. `unlockBatchConfirm()` executes:
   - Validates reason
   - Logs to audit trail with reason
   - Calls API: `POST /api/mark-entry/submission/unlock/{batchId}`
   - Service transitions batch back to "approved" state
   - Allows resubmission
8. Success toast shown
9. Dashboard reloads

---

## 📋 Usage Guide

### For Moderators

**Approving a Batch:**
1. Navigate to "Pending Review" dashboard section
2. Find batch requiring review
3. Click "✅ Approve" button
4. Optional: Add feedback in modal
5. Click "Approve" to confirm
6. Batch moves to "Approved" state

**Rejecting a Batch:**
1. Navigate to "Pending Review" dashboard section
2. Find batch with issues
3. Click "❌ Reject" button
4. Enter detailed rejection reason (min 10 chars)
5. Click "Reject" to confirm
6. Submitter notified to resubmit

### For Submission Managers

**Locking a Batch:**
1. Navigate to "Lock Status" or "Submit Marks" section
2. Find approved batch ready for submission
3. Click "🔒 Lock & Submit" button
4. Read warning about permanence
5. Type "LOCK" in confirmation field
6. Click "Lock & Submit"
7. Batch locked, cannot be modified

**Unlocking a Batch (Admin Only):**
1. Navigate to "Lock Status" section
2. Find locked batch needing modification
3. Click "(Admin) Unlock" button
4. Enter unlock reason documenting necessity
5. Click "Unlock Batch"
6. Batch reverted to approved, can be resubmitted

---

## 🔐 Authorization & Permissions

| Action | Permission | Role |
|--------|-----------|------|
| Approve Batch | `can:mark-entry.moderate` | Moderator |
| Reject Batch | `can:mark-entry.moderate` | Moderator |
| Lock Batch | `can:mark-entry.lock` | Submission Manager |
| Unlock Batch | `can:admin` | Super-Admin Only |

---

## 📝 Audit & Logging

### Automatic Logging

**Moderation Actions:**
- Creates `MarkModerationReview` record
- Logs to `audit_trail` table
- Records: user_id, action type, feedback/reason, timestamp

**Submission Actions:**
- Creates `MarkBatchApproval` record
- Logs state transitions in lifecycle_history
- Unlock actions: stores reason in audit trail with `unlock_requested` event

### Audit Trail Queries

```php
// Get all actions for a batch
$batch->auditTrail()->get();

// Get all moderation reviews
$batch->reviews()->get();

// Get submission history
$batch->approvals()->get();
```

---

## 🧪 Testing Guide

### Manual Testing Checklist

**Approval Flow:**
- [ ] Modal opens with correct batch ID
- [ ] Feedback field accepts text up to 1000 chars
- [ ] Character counter updates in real-time
- [ ] Approve button submits to API
- [ ] Success toast appears
- [ ] Dashboard refreshes with updated batch status
- [ ] Batch transitions to "approved" state

**Rejection Flow:**
- [ ] Modal requires minimum 10-character reason
- [ ] Submit button disabled until reason valid
- [ ] Character counter shows validation feedback
- [ ] Reject button submits to API
- [ ] Success toast shows message about resubmission
- [ ] Dashboard refreshes
- [ ] Batch transitions to "rejected" state
- [ ] Batch marked with rejection reason

**Lock Flow:**
- [ ] Modal displays permanence warning
- [ ] Confirmation text input visible
- [ ] Lock button disabled until "LOCK" entered
- [ ] Case-insensitive confirmation text
- [ ] Lock button submits to API
- [ ] Success toast shown
- [ ] Batch transitions to "submitted" state
- [ ] Dashboard refreshes

**Unlock Flow (Admin Only):**
- [ ] Accessible to admin users only
- [ ] Modal requires minimum 10-character reason
- [ ] Admin shield indicator visible
- [ ] Unlock button submits to API
- [ ] Success toast shows admin logging confirmation
- [ ] Batch reverts to "approved" state
- [ ] Reason logged in audit trail

**Error Handling:**
- [ ] API errors display in error toast
- [ ] Invalid input prevents submission
- [ ] Network errors handled gracefully
- [ ] Loading states show during requests

---

## 📂 File Structure

```
resources/views/mark-entry/
├── components/
│   ├── _approve_batch_modal.blade.php      (NEW)
│   ├── _reject_batch_modal.blade.php       (NEW)
│   ├── _lock_batch_modal.blade.php         (NEW)
│   ├── _unlock_batch_modal.blade.php       (NEW)
│   └── _toast_notification.blade.php       (NEW)
└── index.blade.php                          (UPDATED: modals included, Alpine methods added)

app/Http/Controllers/MarkEntry/Api/
└── MarkLifecycleApiController.php           (UPDATED: 4 new action endpoints)

routes/
└── mark-entry.php                           (UPDATED: 4 new routes)

app/Services/MarkEntry/
├── Moderation/
│   └── MarkModerationService.php            (EXISTING: used by new endpoints)
└── Submission/
    └── MarkSubmissionService.php            (EXISTING: used by new endpoints)
```

---

## 🚀 Integration Notes

### Modal Display
All modals conditionally render based on Alpine state:
```html
<div x-show="showApproveBatchModal" ...>
```

### Toast Notifications
Toast system integrates with all workflows:
- Success confirmations
- Error messages
- Warning notices

### API Response Structure
Consistent response format across all action endpoints:
```json
{
  "success": true|false,
  "message": "String describing result",
  "data": {
    "batch_id": "...",
    "lifecycle_state": "...",
    "timestamp": "..."
  }
}
```

---

## ⚙️ Configuration

### Validation Rules

**Approval:**
- feedback: optional, max 1000 characters

**Rejection:**
- reason: required, min 10, max 1000 characters

**Lock:**
- No parameters required
- Confirmation via UI text input

**Unlock:**
- reason: required, min 10, max 1000 characters

### Toast Timing
- Auto-dismiss: 5 seconds
- Manual close: Always available
- Multiple toasts: Only one displayed at a time

---

## 🔄 Next Steps (Phase 3C-3 Batch 4)

Potential enhancements:
1. Batch bulk actions (approve/reject multiple at once)
2. Email notifications on moderation actions
3. Workflow customization (approval chains)
4. Performance optimization for large batch lists
5. Export moderation history report

---

## 📞 Support

For issues or questions about this implementation, refer to:
- Audit trail for action history
- Service class documentation
- API endpoint tests
- Modal component templates

---

**Implementation by:** Amp AI Assistant
**Last Updated:** 2026-02-14
**Version:** 1.0
