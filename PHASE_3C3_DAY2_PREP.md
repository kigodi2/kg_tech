# Phase 3C-3: Day 2 Implementation Plan

**Date:** February 14, 2026  
**Focus:** Moderation & Submission Workflows  
**Estimated Duration:** 20 hours  
**Status:** Ready to start

---

## Overview: Day 2 Objectives

Transform read-only dashboard sections into interactive workflows:
- Users can APPROVE or REJECT pending batches
- Users can LOCK approved batches for submission
- All actions trigger state transitions with audit logging
- Proper confirmations and success messages

---

## Batch 2: Moderation Workflows (20 hours)

### 2.1: Approve Batch Modal (5 hours)

**Trigger:** Click "Approve" button on pending batch

**Modal Specifications:**
```
Title: Approve Batch
Content:
  - Batch Code (display)
  - School Name (display)
  - Subject (display)
  - Total Marks (display)
  - Optional: Feedback textarea (max 1000 chars)
  
Buttons:
  - "Approve" (primary, disabled until validation)
  - "Cancel" (secondary)

Validation:
  - Feedback: optional, max 1000 chars
  - Batch must be in "awaiting_moderation" state
```

**API Call:**
```
POST /mark-entry/acsee/moderation/batch/{id}/approve
Body: { feedback: "..." }
Response: { success: true, message: "Batch approved" }
```

**State Changes:**
- Alpine.js: Remove from `moderationBatches`
- Alpine.js: Add to `approvedBatches` (if we add it)
- Database: Transition to "approved" state
- Audit: Log the approval action

**Implementation:**
1. Add Alpine.js method: `approveBatch(batchId)`
2. Create modal component: `_approve_batch_modal.blade.php`
3. Wire up button click handlers
4. Add success/error toast messages
5. Refresh moderation dashboard after success

### 2.2: Reject Batch Modal (5 hours)

**Trigger:** Click "Reject" button on pending batch

**Modal Specifications:**
```
Title: Reject Batch
Content:
  - Batch Code (display)
  - School Name (display)
  - Subject (display)
  - Rejection Reason textarea (required, min 10 chars, max 1000)
  - Show validation message for length
  
Buttons:
  - "Reject" (danger, disabled until reason entered)
  - "Cancel" (secondary)

Validation:
  - Reason: required, min 10, max 1000 chars
  - Provide character count feedback
```

**API Call:**
```
POST /mark-entry/acsee/moderation/batch/{id}/reject
Body: { reason: "..." }
Response: { success: true, message: "Batch rejected" }
```

**State Changes:**
- Alpine.js: Remove from `moderationBatches`
- Database: Transition to "rejected" state
- Database: Set `requires_resubmission = true`
- Database: Store rejection reason
- Audit: Log rejection with reason

**Implementation:**
1. Add Alpine.js method: `rejectBatch(batchId)`
2. Create modal: `_reject_batch_modal.blade.php`
3. Add character counter
4. Add validation feedback
5. Wire button handlers
6. Refresh dashboard after success

### 2.3: Success/Error Messaging (3 hours)

**Toast Notifications:**
```javascript
showMessage(message, type) // 'success' or 'error'
- Position: top-right corner
- Duration: 5 seconds (success), 8 seconds (error)
- Colors: Green for success, Red for error
- Icon: ✓ or ✕
- Auto-dismiss or manual close button
```

**Implementation:**
1. Enhance existing `showMessage()` method
2. Add toast styling
3. Auto-dismiss timers
4. Stack multiple messages if needed

### 2.4: State Management & Data Refresh (4 hours)

**After Approval:**
1. Remove batch from moderation list
2. Optionally add to "Approved" list (if we create one)
3. Refresh dashboard automatically
4. Show success toast
5. Log to audit trail

**After Rejection:**
1. Remove batch from moderation list
2. Store rejection reason
3. Refresh dashboard
4. Show success toast
5. Log to audit trail

**Implementation:**
1. Update Alpine.js state reactively
2. Implement auto-refresh logic
3. Handle API errors gracefully
4. Provide retry mechanism for failed actions

---

## Batch 3: Submission & Locking (20 hours)

### 3.1: Lock Batch Modal (5 hours)

**Trigger:** Click "Lock & Submit" button on approved batch

**Modal Specifications:**
```
Title: Lock & Submit Batch
Content:
  - Batch Code (display)
  - School Name (display)
  - Subject (display)
  - Total Marks (display)
  - Checksum/Hash (if available, display)
  - Warning message: "This action is permanent. 
    Locked batches cannot be modified."
  
Buttons:
  - "Lock & Submit" (primary, danger styling)
  - "Cancel" (secondary)

Validation:
  - Batch must be in "approved" state
  - User must confirm understanding
  - Optional: Require checkbox confirmation
```

**API Call:**
```
POST /mark-entry/acsee/submission/lock/{id}
Response: { success: true, message: "Batch locked and submitted" }
```

**State Changes:**
- Alpine.js: Remove from `readyBatches`
- Alpine.js: Add to `submittedBatches`
- Database: Transition to "submitted" state
- Database: Set lock timestamp
- Database: Record locked_by user
- Audit: Log lock action

**Implementation:**
1. Add Alpine.js method: `lockBatch(batchId)`
2. Create modal: `_lock_batch_modal.blade.php`
3. Add confirmation checkbox (optional)
4. Wire button handlers
5. Refresh Lock Status section after success

### 3.2: Admin Unlock (5 hours, restricted)

**Trigger:** Click "Unlock" button on submitted batch (ADMIN ONLY)

**Access Control:**
- Check user role: Admin only
- Show warning badge: Admin-only action
- Require explicit permission

**Modal Specifications:**
```
Title: Admin Unlock Batch (ADMIN ONLY)
Warning: "This will unlock a submitted batch. 
Only for emergency situations."

Content:
  - Batch Code (display)
  - Current State (display)
  - Unlock Reason textarea (required, min 10 chars)
  - Show who will unlock and when
  
Buttons:
  - "Unlock" (danger, disabled until reason entered)
  - "Cancel" (secondary)

Validation:
  - Reason: required, min 10 chars
  - User must be admin
```

**API Call:**
```
POST /mark-entry/acsee/submission/unlock/{id}
Body: { reason: "..." }
Response: { success: true, message: "Batch unlocked" }
```

**State Changes:**
- Database: Transition back to "approved" state
- Database: Clear lock timestamp
- Database: Record unlock_by admin
- Audit: Log unlock with reason and admin name
- Alpine.js: Move batch back to readyBatches

**Implementation:**
1. Add permission check in frontend
2. Add Alpine.js method: `unlockBatch(batchId)`
3. Create modal: `_unlock_batch_modal.blade.php`
4. Add admin-only styling and warnings
5. Audit logging for admin action

### 3.3: Confirmation Dialogs (5 hours)

**Generic Confirmation System:**
```javascript
showConfirmation({
  title: "Approve Batch?",
  message: "Are you sure you want to approve this batch?",
  confirmText: "Yes, Approve",
  cancelText: "Cancel",
  onConfirm: () => { /* action */ },
  onCancel: () => { /* cancel */ },
  isDangerous: false // Red button for dangerous actions
})
```

**Use in:**
- Approve batch confirmation
- Reject batch confirmation
- Lock batch confirmation (DANGEROUS)
- Unlock batch confirmation (DANGEROUS)

**Implementation:**
1. Create reusable confirmation component
2. Support both dangerous and normal actions
3. Add proper styling and colors
4. Handle keyboard shortcuts (Enter to confirm)

### 3.4: Batch Detail View Modal (5 hours, optional for Day 2)

**Optional Enhancement:**
- Click batch row to see full details
- Show all batch metadata
- Show recent changes
- Show approval/lock history
- Can approve/reject/lock from detail view

**Implementation:**
1. Create _batch_detail_modal.blade.php
2. Add batch selector in moderation sections
3. Display detailed information
4. Link actions to the batch
5. Can enhance Day 3 if time permits

---

## Technical Implementation Details

### Alpine.js Methods to Add

```javascript
// Moderation actions
async approveBatch(batchId) { }
async rejectBatch(batchId, reason) { }

// Submission actions
async lockBatch(batchId) { }
async unlockBatch(batchId, reason) { } // Admin only

// General utilities
showConfirmation(options) { }
showMessage(message, type) { } // Enhanced
refreshModeration() { }
refreshLockStatus() { }
```

### Modal Components to Create

```
resources/views/mark-entry/components/
├── _approve_batch_modal.blade.php
├── _reject_batch_modal.blade.php
├── _lock_batch_modal.blade.php
├── _unlock_batch_modal.blade.php
├── _confirmation_dialog.blade.php
└── _toast_notification.blade.php
```

### API Endpoints Required

All endpoints already exist from Phase 3C-2:
- POST `/mark-entry/acsee/moderation/batch/{id}/approve`
- POST `/mark-entry/acsee/moderation/batch/{id}/reject`
- POST `/mark-entry/acsee/submission/lock/{id}`
- (Admin Unlock - may need to create)

### Audit Logging

Every action must log to audit trail:
- Who performed action
- What action (approve/reject/lock/unlock)
- When it happened
- Any additional details (feedback, reason, etc.)
- Timestamp and IP address

---

## Testing Checklist for Day 2

### Moderation Workflows
- [ ] Click approve button → modal opens
- [ ] Enter feedback → count characters
- [ ] Click approve → batch removed from list
- [ ] Success message shows
- [ ] Batch appears in "approved" if list exists
- [ ] Audit trail logs approval

- [ ] Click reject button → modal opens
- [ ] Reject reason required → disable button if empty
- [ ] Enter reason with min 10 chars → button enables
- [ ] Click reject → batch removed
- [ ] Success message shows
- [ ] Rejection reason stored in database
- [ ] Audit trail logs rejection

### Submission Workflows
- [ ] Click lock button → confirmation modal
- [ ] Warning message visible
- [ ] Click lock → batch marked submitted
- [ ] Success message shows
- [ ] Batch moves to submitted list
- [ ] Lock timestamp recorded
- [ ] Audit trail logs lock

### Admin Unlock (if completed)
- [ ] Admin-only check enforced
- [ ] Unlock button visible only for admins
- [ ] Modal shows danger warning
- [ ] Reason required
- [ ] Click unlock → batch returns to approved
- [ ] Audit logs admin unlock action

### Error Handling
- [ ] API failures show error message
- [ ] Network timeout handled
- [ ] Permission denied (403) handled
- [ ] Validation errors shown in modal
- [ ] Retry mechanism available

### UI/UX
- [ ] Modals open/close smoothly
- [ ] Loading spinners show during request
- [ ] Buttons disabled while processing
- [ ] Toast notifications dismiss properly
- [ ] Responsive on mobile
- [ ] Keyboard navigation works

---

## Day 2 Time Breakdown

| Task | Hours | Status |
|------|-------|--------|
| Approve Modal | 5 | Ready to build |
| Reject Modal | 5 | Ready to build |
| Lock Modal | 5 | Ready to build |
| Unlock (Admin) | 5 | Ready to build |
| Success/Error Messages | 3 | Ready to build |
| State Management | 4 | Ready to build |
| Confirmations | 5 | Ready to build |
| Testing & Polish | 5 | Ready to validate |
| **TOTAL** | **37** | Ready to start |

**Note:** Actual implementation usually completes in 20 hours with focused work.

---

## Day 2 Starting Checklist

Before we start Day 2:
- [ ] Review this plan
- [ ] Pull latest code from main
- [ ] Verify Phase 3C-3 Day 1 is deployed
- [ ] Test Day 1 sections load data
- [ ] Review API endpoints in PHASE_3C2_QUICK_REFERENCE.txt
- [ ] Set up local testing environment
- [ ] Have browser DevTools ready for debugging

---

## Resources Available

### Documentation
- PHASE_3C2_QUICK_REFERENCE.txt (API endpoints)
- PHASE_3C2_DATA_INTEGRATION_COMPLETE.md (API details)
- DEPLOYMENT_PHASE3C3_DAY1.md (current state)

### Code References
- `resources/views/mark-entry/index.blade.php` (existing modals)
- `app/Http/Controllers/MarkEntry/Moderation/MarkEntryModerationController.php`
- `app/Services/MarkEntry/Moderation/MarkModerationService.php`
- `app/Services/MarkEntry/Submission/MarkSubmissionService.php`

---

## Success Criteria for Day 2

By end of Day 2:
- ✅ Users can approve batches
- ✅ Users can reject batches with reasons
- ✅ Admins can unlock batches
- ✅ All actions logged to audit trail
- ✅ Success/error messages displayed
- ✅ State updates reflected immediately
- ✅ No console errors
- ✅ All tests passing

---

## Known Challenges & Solutions

| Challenge | Solution |
|-----------|----------|
| Modal state management | Use Alpine.js reactive properties |
| API request race conditions | Disable buttons during requests |
| Validation timing | Use Alpine.js computed properties |
| Browser history | No page reload needed |
| Audit logging | Implemented on server side |
| Admin permissions | Check user role before showing unlock |

---

## Implementation Order (Recommended)

1. **Start with Approve** (simplest workflow)
   - Create modal
   - Wire button
   - Test API call
   - Add success message

2. **Then Reject** (similar to approve)
   - Create modal
   - Add required field validation
   - Wire button
   - Test

3. **Then Lock** (more complex - state change)
   - Create modal
   - Add confirmation checkbox
   - Wire button
   - Handle state transitions

4. **Finally Unlock** (admin-only)
   - Add permission check
   - Create modal
   - Wire button
   - Test admin flow

---

## Ready for Day 2

Everything is prepared:
- ✅ API endpoints exist
- ✅ Services implemented
- ✅ Day 1 deployed and working
- ✅ Models configured
- ✅ Audit logging ready
- ✅ Documentation complete

**We're ready to start immediately on Day 2.**

---

**Next Session:** Friday, February 14, 2026 - Begin Batch 2 Implementation

*Prepared by: Amp*  
*Date: February 13, 2026*  
*Status: Ready to execute*
