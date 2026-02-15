# Phase 3C-3: Functionality Implementation Plan

**Status:** 🚀 STARTING  
**Date:** February 13, 2026  
**Estimated Effort:** 80 hours  
**Timeline:** Week 3

---

## Overview

Phase 3C-3 transforms the sidebar dashboard from a static skeleton into a fully functional Mark Entry Lifecycle system. All 24 sidebar sections will be wired up with real data, interactive controls, and complete workflows.

---

## Architecture

```
FRONTEND (Blade Templates + Alpine.js)
    ↓
API ENDPOINTS (14 endpoints from Phase 3C-2)
    ↓
SERVICE LAYER (4 services from Phase 3C-2)
    ↓
DATABASE (Core tables + audit tables)
```

---

## Implementation Roadmap

### Batch 1: Data Fetching & Display (Week 3, Day 1)
**Focus:** Wire up API endpoints to frontend sections

#### 1.1 Moderation Dashboard
- **Endpoint:** `GET /api/mark-entry/moderation/pending`
- **Display:** Table of batches awaiting moderation
- **Columns:** Batch Code, School, Subject, Status, Created Date
- **Features:**
  - Pagination (20 items per page)
  - Search/filter by batch code
  - Click to view batch details
  - Badge showing pending count
  - Loading spinner

**Implementation Files:**
- Update `#moderation-dashboard` section in `resources/views/mark-entry/index.blade.php`
- Add Alpine.js function: `loadModerationDashboard()`
- Create component: `_moderation_dashboard.blade.php`

#### 1.2 Pending Review List
- **Endpoint:** `GET /api/mark-entry/moderation/pending` (same as above)
- **Display:** Detailed list with review history
- **Actions:** View Details, Approve, Reject
- **Columns:** Batch Code, School, Subject, # Marks, Status, Last Reviewed

**Implementation Files:**
- Update `#pending-review` section
- Add Alpine.js: `loadPendingReviews()`

#### 1.3 Lock Status
- **Endpoint:** `GET /api/mark-entry/submission/ready`
- **Display:** Batches ready for submission
- **Columns:** Batch Code, School, Subject, Approved Date, Ready to Lock
- **Actions:** Lock batch, View approval history
- **Badge:** Shows count of ready batches

**Implementation Files:**
- Update `#lock-status` section
- Add Alpine.js: `loadLockStatus()`

#### 1.4 Submission History
- **Endpoint:** `GET /api/mark-entry/submission/batch/{batch}/history`
- **Display:** Approval/submission timeline
- **Columns:** Date, Action, User, Notes, Status
- **Features:** Timeline view, filter by date range

**Implementation Files:**
- Update `#submission-history` section
- Add Alpine.js: `loadSubmissionHistory(batchId)`

#### 1.5 Analytics Dashboard
- **Endpoints:**
  - `GET /api/mark-entry/analytics/overview`
  - `GET /api/mark-entry/analytics/by-subject`
  - `GET /api/mark-entry/analytics/by-school`
  - `GET /api/mark-entry/analytics/errors`
  
- **Display:**
  - Summary cards (Total Batches, Pending, Approved, Submitted)
  - Error rate gauge
  - Charts: By Subject, By School, By Status
  
**Implementation Files:**
- Update `#analytics` section
- Add Alpine.js: `loadAnalytics()`
- Integrate with Chart.js or Alpine-chart

#### 1.6 Audit Trail
- **Endpoint:** `GET /api/mark-entry/audit/batch/{batch}`
- **Display:** Change history with timestamps
- **Columns:** Date/Time, User, Field Changed, Old Value → New Value, Reason
- **Features:** 
  - Timeline visualization
  - Filter by user/date
  - Modification detection badge

**Implementation Files:**
- Update `#audit-trail` section
- Add Alpine.js: `loadAuditTrail(batchId)`

#### 1.7 Activity Log
- **Endpoint:** `GET /api/mark-entry/audit/user/{userId}`
- **Display:** All user actions across system
- **Columns:** Timestamp, Batch, Action, Details
- **Features:** Filter by user, date range, action type

**Implementation Files:**
- Update `#activity-log` section
- Add Alpine.js: `loadActivityLog(userId)`

---

### Batch 2: Moderation Workflows (Week 3, Day 2)

#### 2.1 Approve Batch
- **Action:** Click "Approve" on pending batch
- **Dialog:**
  - Display batch summary (code, school, subject, # marks)
  - Optional feedback text area
  - Confirm button + Cancel
  - Validation: Feedback max 1000 chars

- **Workflow:**
  1. Load batch details
  2. Show modal with batch info
  3. User enters feedback (optional)
  4. Submit POST `/mark-entry/acsee/moderation/batch/{id}/approve`
  5. Show success message
  6. Refresh pending list
  7. Log action to audit trail

**Implementation Files:**
- Create component: `_approve_batch_modal.blade.php`
- Add Alpine.js: `approveBatch(batchId)`
- Add error handling: Network errors, validation errors

#### 2.2 Reject Batch
- **Action:** Click "Reject" on pending batch
- **Dialog:**
  - Display batch summary
  - Rejection reason text area (required, min 10 chars, max 1000)
  - Show validation errors
  - Confirm + Cancel buttons

- **Workflow:**
  1. Show reject modal
  2. Validate reason field
  3. Submit POST `/mark-entry/acsee/moderation/batch/{id}/reject`
  4. Update batch status to "rejected"
  5. Show success message
  6. Refresh dashboard
  7. Log rejection reason to audit

**Implementation Files:**
- Create component: `_reject_batch_modal.blade.php`
- Add Alpine.js: `rejectBatch(batchId)`
- Add validation logic

---

### Batch 3: Submission & Locking (Week 3, Day 2-3)

#### 3.1 Lock Batch
- **Action:** Click "Lock" on approved batch
- **Dialog:**
  - Show batch details
  - Confirmation: "This batch will be locked for submission"
  - Show checksum/hash (if available)
  - Confirm + Cancel

- **Workflow:**
  1. Show lock confirmation dialog
  2. User confirms lock
  3. Submit POST `/mark-entry/acsee/submission/lock/{id}`
  4. Update batch state to "submitted"
  5. Show success message with timestamp
  6. Move from "ready" to "submitted" list
  7. Log lock action with user and timestamp

**Implementation Files:**
- Create component: `_lock_batch_modal.blade.php`
- Add Alpine.js: `lockBatch(batchId)`

#### 3.2 Admin Unlock (Optional)
- **Action:** Click "Unlock" on submitted batch (admin only)
- **Dialog:**
  - Warning message: "This will unlock a submitted batch"
  - Reason text area (required)
  - Confirm + Cancel

- **Workflow:**
  1. Check admin permission
  2. Show unlock confirmation
  3. User provides reason
  4. Submit to MarkSubmissionService::unlockBatch()
  5. Update batch state back to "approved"
  6. Log unlock with reason
  7. Refresh dashboards

**Implementation Files:**
- Create component: `_unlock_batch_modal.blade.php`
- Add Alpine.js: `unlockBatch(batchId)` (admin only)

---

### Batch 4: Export & Reporting (Week 3, Day 3)

#### 4.1 Scoresheet PDF Export
- **Action:** Click "Download Scoresheet" 
- **Features:**
  - Select batch from dropdown
  - Choose format: Full Report or Summary
  - Download button
  - Loading indicator

- **Workflow:**
  1. Load list of submitted batches
  2. User selects batch
  3. Choose export format
  4. POST to `/mark-entry/acsee/reports/scoresheet/{id}` with format
  5. Trigger browser download
  6. Log export action to audit trail

**Implementation Files:**
- Create component: `_scoresheet_export.blade.php`
- Add Alpine.js: `exportScoresheet(batchId, format)`
- Generate PDF in controller (use DomPDF or similar)

#### 4.2 CSV Export
- **Action:** Click "Export as CSV"
- **Features:**
  - Choose what to export (analytics by subject, by school, by year)
  - Date range filter (optional)
  - Export button
  - File naming convention

- **Workflow:**
  1. Show export options form
  2. User selects data and filters
  3. POST to API endpoint with filters
  4. Return CSV file
  5. Trigger download
  6. Log export to audit

**Implementation Files:**
- Create component: `_csv_export.blade.php`
- Add Alpine.js: `exportCsv(type, filters)`
- Implement in controller

#### 4.3 Summary Report
- **Display:** Executive summary with key metrics
- **Data:**
  - Total batches processed this period
  - By exam year breakdown
  - Error statistics
  - Timeline chart
  - Top schools/subjects

- **Endpoint:** `GET /api/mark-entry/analytics/overview`

**Implementation Files:**
- Update `#summary-report` section
- Create component: `_summary_report.blade.php`
- Add Alpine.js: `loadSummaryReport()`

---

### Batch 5: Audit & Monitoring (Week 3, Day 4)

#### 5.1 Change Log
- **Endpoint:** `GET /api/mark-entry/audit/batch/{batch}/summary`
- **Display:**
  - Who changed what and when
  - Modification count by user
  - Percentage of marks modified
  - Flags for unusual activity

**Implementation Files:**
- Update `#change-log` section
- Create component: `_change_log.blade.php`
- Add Alpine.js: `loadChangeLog(batchId)`

#### 5.2 Lifecycle Dashboard
- **Endpoint:** `GET /api/mark-entry/analytics/overview`
- **Display:**
  - State distribution pie chart
  - Processing timeline
  - Current bottlenecks
  - System health metrics

**Implementation Files:**
- Update `#lifecycle-dashboard` section
- Create component: `_lifecycle_dashboard.blade.php`

#### 5.3 Error Handling & Messages
- **Global error handler:** Catch API errors
- **Error types:**
  - Network errors (connection timeout)
  - Validation errors (from backend)
  - Permission errors (403)
  - Server errors (500)
  - Not found errors (404)

- **User feedback:**
  - Toast notifications
  - Modal alerts for critical errors
  - Inline form validation
  - Retry buttons where appropriate

**Implementation Files:**
- Create component: `_error_toast.blade.php`
- Add Alpine.js: `showError(message, type)`

---

## Alpine.js Components & Functions

### Core Manager Object
```javascript
function markEntryManager() {
    return {
        // State
        loading: false,
        currentBatch: null,
        currentUser: null,
        
        // Moderation
        moderationBatches: [],
        showApproveModal: false,
        showRejectModal: false,
        approvalFeedback: '',
        rejectionReason: '',
        
        // Submission
        readyBatches: [],
        submittedBatches: [],
        showLockModal: false,
        
        // Analytics
        analyticsData: null,
        errorStats: null,
        
        // Methods
        init() { /* Initialize */ },
        loadModerationDashboard() { /* Load pending batches */ },
        approveBatch(batchId) { /* Show approve modal */ },
        rejectBatch(batchId) { /* Show reject modal */ },
        lockBatch(batchId) { /* Show lock modal */ },
        loadAnalytics() { /* Load analytics */ },
        exportScoresheet(batchId) { /* Export PDF */ },
        handleError(error) { /* Show error */ },
    }
}
```

---

## Frontend Components to Create

```
resources/views/mark-entry/components/
├── _moderation_dashboard.blade.php
├── _pending_review_list.blade.php
├── _approve_batch_modal.blade.php
├── _reject_batch_modal.blade.php
├── _lock_batch_modal.blade.php
├── _unlock_batch_modal.blade.php
├── _lock_status.blade.php
├── _submission_history.blade.php
├── _scoresheet_export.blade.php
├── _csv_export.blade.php
├── _summary_report.blade.php
├── _analytics_dashboard.blade.php
├── _audit_trail.blade.php
├── _change_log.blade.php
├── _activity_log.blade.php
├── _lifecycle_dashboard.blade.php
├── _error_toast.blade.php
└── _loading_spinner.blade.php
```

---

## Backend Controllers & Methods to Implement

### MarkEntryReportController
- `scoresheet($batchId, $format)` - Generate PDF scoresheet
- `csvExport($type, $filters)` - Export data as CSV

### Enhance MarkEntryModerationController
- Add success/error response formatting
- Add change logging

### Enhance MarkEntrySubmissionController
- Implement lock workflow
- Implement admin unlock (with authorization)

### MarkEntryAuditController (New)
- `getAuditTrail($batchId)` - Return paginated audit trail
- `getActivityLog($userId)` - Return user activity

---

## Testing Strategy

### Unit Tests
- Service methods (approve, reject, lock, unlock)
- API responses (format, pagination)
- Validation logic

### Integration Tests
- Workflow end-to-end (upload → approve → lock)
- Audit logging (every action logged)
- Permission gates (correct users can perform actions)

### Manual Testing Checklist
- [ ] Moderation dashboard loads data
- [ ] Approve batch updates status
- [ ] Reject batch with reason
- [ ] Lock batch marks submitted
- [ ] Unlock reverts to approved
- [ ] PDF export downloads correctly
- [ ] CSV export contains correct data
- [ ] Audit trail shows all changes
- [ ] Error messages display properly
- [ ] Pagination works on all lists
- [ ] Search/filter functionality works
- [ ] Permission checks enforced

---

## Error Handling Strategy

### Network Errors
- Timeout: "Connection timeout. Please check your internet."
- Failed: "Failed to load data. Please refresh the page."
- Retry button: Retry the failed request

### Validation Errors
- Show inline messages in forms
- Highlight invalid fields
- Clear errors when user fixes input
- Submit button disabled until valid

### Permission Errors (403)
- "You don't have permission to perform this action"
- Suggest contacting admin
- Redirect to dashboard

### Server Errors (500)
- "An unexpected error occurred"
- Show error code
- Log to sentry/monitoring
- Contact admin link

### Not Found (404)
- "The requested batch was not found"
- Suggest refreshing the page
- Return to dashboard

---

## Success Messages

- "✓ Batch approved successfully"
- "✓ Batch rejected. Notification sent to operator."
- "✓ Batch locked and submitted"
- "✓ Batch unlocked (admin)"
- "✓ Scoresheet downloaded"
- "✓ Data exported to CSV"

---

## Timeline

**Day 1 (Feb 13):** Data Fetching & Display
- Sections 1.1-1.7
- ~16 hours

**Day 2 (Feb 14):** Moderation Workflows
- Sections 2.1-2.2 + error handling
- ~20 hours

**Day 3 (Feb 15):** Submission & Locking
- Sections 3.1-3.2 + refinement
- ~20 hours

**Day 4 (Feb 16):** Export & Reporting
- Sections 4.1-4.3
- ~16 hours

**Day 5 (Feb 17):** Testing & Refinement
- Integration testing
- Performance optimization
- Polish UI
- ~8 hours

---

## Success Criteria

✅ All 24 sidebar items have functional implementations  
✅ Data loads from API endpoints  
✅ Workflows (approve, reject, lock, unlock) fully functional  
✅ Exports (PDF, CSV) work correctly  
✅ Audit trail captures all actions  
✅ Error handling is robust  
✅ User feedback (success/error messages) clear  
✅ Loading states visible  
✅ Permission checks enforced  
✅ No console errors  

---

## Known Risks & Mitigation

| Risk | Mitigation |
|------|-----------|
| PDF generation slow | Use queue jobs, background processing |
| Large CSV files | Stream response, don't load all in memory |
| Concurrent updates | Implement optimistic locking |
| Permission checks slow | Cache permissions in session |
| Audit log grows large | Archive old entries, index queries |

---

## Next Phase

**Phase 3C-4: Polish (Week 4)**
- Visual improvements
- Performance tuning
- Active state indicators
- Notification badges
- Keyboard shortcuts
- Dark mode support

---

**Phase 3C-3 Implementation Ready to Start** 🚀

*Guided by Phase 3C-2 completed data layer*
