# Moderation & Submission Workflows - Quick Start Guide

## 🎯 What Was Implemented

Your Mark Entry system now has **interactive moderation and submission workflows**. This means:

✅ **Moderators can approve/reject batches** directly from the dashboard
✅ **Managers can lock batches** for final submission
✅ **Admins can unlock batches** if needed for corrections
✅ **All actions are automatically logged** for audit compliance

---

## 📋 Three Simple Workflows

### Workflow 1: APPROVE A BATCH ✅

**Steps:**
1. Go to "Pending Review" section in sidebar
2. Find the batch you want to approve
3. Click the **"✅ Approve"** button
4. (Optional) Add feedback for the submitter
5. Click **"Approve"** in the modal

**Result:** Batch moves to "approved" status and is ready for submission

---

### Workflow 2: REJECT A BATCH ❌

**Steps:**
1. Go to "Pending Review" section in sidebar
2. Find the batch with issues
3. Click the **"❌ Reject"** button
4. **Write a reason** for rejection (minimum 10 characters)
   - Explain what needs to be fixed
   - Be specific about the issues
5. Click **"Reject"** in the modal

**Result:** Batch returns to submitter for corrections. They'll know exactly what to fix.

---

### Workflow 3: LOCK A BATCH 🔒

**Steps:**
1. Go to "Lock Status" or "Submit Marks" section
2. Find an approved batch ready for submission
3. Click **"🔒 Lock & Submit"** button
4. **Read the warning carefully** - this is permanent!
5. Type **"LOCK"** in the confirmation box (case doesn't matter)
6. Click **"Lock & Submit"**

**Result:** Batch is permanently locked and submitted. No more changes allowed.

---

### Workflow 4: UNLOCK A BATCH (ADMIN ONLY) 🔓

**For Administrators Only**

**Steps:**
1. Go to "Lock Status" section
2. Find a locked batch that needs modification
3. Click **(Admin) Unlock** button
4. **Explain why** the batch is being unlocked (minimum 10 characters)
   - This will be recorded in the system audit log
5. Click **"Unlock Batch"**

**Result:** Batch reverts to "approved" status. Managers can submit again.

---

## 🎨 Visual Indicators

| Color | Action | Meaning |
|-------|--------|---------|
| 🟢 Green | Approve | Marks batch as ready for submission |
| 🔴 Red | Reject | Sends back for corrections |
| 🟡 Yellow | Lock | Final submission, no changes allowed |
| 🟣 Purple | Unlock (Admin) | Admin override to allow resubmission |

---

## ⚠️ Important Rules

### When Approving ✅
- Feedback is **optional** but recommended
- Keep feedback constructive
- Batch will move to approved status immediately

### When Rejecting ❌
- **Reason is required** (at least 10 characters)
- Be specific about what needs fixing
- Submitter will see your reason and resubmit
- Batch returns to entry state

### When Locking 🔒
- **This is permanent** - only use when ready
- No one can modify the batch after locking
- Batch is officially submitted
- **Requires typing "LOCK"** to prevent accidents

### When Unlocking 🔓 (Admin Only)
- **This is a rare action** - document why
- Reason will be stored in audit trail
- Batch reverts to approved status
- Creates audit record for compliance

---

## 🔔 What Happens Next

**After Approving:** ✅
→ Batch appears in "Submit Marks" section
→ Managers can lock it for final submission

**After Rejecting:** ❌
→ Batch returns to submitter
→ They see your rejection reason
→ They can resubmit after fixing issues

**After Locking:** 🔒
→ Batch officially submitted
→ System records the lock timestamp
→ Batch cannot be modified

**After Unlocking:** 🔓
→ Batch reverts to approved status
→ Can be resubmitted by manager
→ Audit trail records the unlock reason

---

## 💡 Tips & Best Practices

### For Moderators
- ✓ Approve batches with complete, correct data
- ✓ Provide constructive feedback in rejection reasons
- ✓ Review submission comments before approving
- ✗ Don't approve batches with obvious errors

### For Submission Managers
- ✓ Lock batches when you're absolutely sure they're final
- ✓ Check all data one more time before locking
- ✓ Keep locked batches organized
- ✗ Don't lock a batch before approval

### For Administrators
- ✓ Only unlock batches when necessary
- ✓ Document the reason clearly in unlock notes
- ✓ Review audit trail after unlocking
- ✗ Don't make frequent changes - undermines controls

---

## 🔍 Check Status Anytime

The sidebar shows batch status in real-time:
- **Pending Review** = Waiting for moderator approval
- **Approved** = Ready for manager to lock
- **Submitted/Locked** = Final, cannot be changed
- **Rejected** = Sent back to submitter

---

## 🚨 If Something Goes Wrong

**Batch rejected but shouldn't be?**
→ Only admin can unlock (contact administrator)

**Batch locked but needs changes?**
→ Ask admin to unlock with documented reason

**Unsure about approving?**
→ Reject and ask submitter to resubmit
→ Better to be careful than sorry

**Made a mistake?**
→ All actions are logged in audit trail
→ Admins can see full history
→ Contact system administrator for help

---

## 📞 Quick Reference

| Need to... | Go to... | Click... | Section... |
|-----------|----------|---------|-----------|
| Approve batches | Sidebar | "Pending Review" | Moderation |
| Reject batches | Sidebar | "Pending Review" | Moderation |
| Lock batches | Sidebar | "Lock Status" or "Submit Marks" | Submission |
| Unlock batches | Sidebar | "Lock Status" | Submission (Admin) |

---

## ✨ Success Indicators

You'll see a **green success message** when:
- ✅ Batch approved successfully
- ✅ Batch rejected successfully  
- ✅ Batch locked successfully
- ✅ Batch unlocked successfully (admin only)

You'll see a **red error message** if:
- ❌ Validation fails (too short reason, etc.)
- ❌ Server error occurs
- ❌ You don't have permission
- ❌ Batch is in wrong state

---

## 🎓 Learning Path

**Start with:**
1. Approve a few simple batches ✅
2. Try rejecting a batch with clear issues ❌
3. Lock an approved batch 🔒

**After comfortable with basics:**
4. Contact admin to observe unlock process 🔓
5. Learn audit trail review
6. Become power user!

---

**Remember:** Each action is logged for audit purposes. Be thoughtful, document reasons, and follow the process!

Last Updated: 2026-02-14
