# Unlock Batch Technical Architecture
**Date**: 2026-02-14  
**Component**: Mark Entry Lifecycle Management  
**Feature**: Admin Unlock Batch Action

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER BROWSER                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Mark Entry Dashboard (index.blade.php)                  │  │
│  │                                                            │  │
│  │  Alpine.js Component: markEntryManager()                │  │
│  │  - showUnlockBatchModal: boolean                         │  │
│  │  - isUnlocking: boolean                                  │  │
│  │  - unlockReason: string (min 10 chars)                  │  │
│  │  - selectedBatchId: number                               │  │
│  └──────────────────────────────────────────────────────────┘  │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  _unlock_batch_modal.blade.php                           │  │
│  │  - Form validation                                       │  │
│  │  - UI: Reason textarea (min 10 chars)                   │  │
│  │  - Buttons: Cancel, Unlock (disabled until valid)      │  │
│  │  - Loading state: Spinner + disabled buttons            │  │
│  └──────────────────────────────────────────────────────────┘  │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  unlockBatchConfirm() - Async Function                  │  │
│  │  1. Validate input (min 10 chars check)                 │  │
│  │  2. Set isUnlocking = true (show spinner)               │  │
│  │  3. Send POST request to API                            │  │
│  │  4. Handle response/error                                │  │
│  │  5. Finally: Set isUnlocking = false                    │  │
│  │  6. Auto-refresh data after success                     │  │
│  └──────────────────────────────────────────────────────────┘  │
│                           ↓                                      │
│                   FETCH REQUEST (POST)                           │
│        Content-Type: application/json                           │
│        X-CSRF-TOKEN: {token}                                    │
│        Body: { reason: string }                                 │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
           ↓↓↓  HTTP POST /api/mark-entry/submission/unlock/{batchId}  ↓↓↓
┌─────────────────────────────────────────────────────────────────┐
│                      LARAVEL APPLICATION                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  routes/mark-entry.php                                   │  │
│  │                                                            │  │
│  │  Route Group Middleware:                                 │  │
│  │  - 'web'  → Session, CSRF, auth helpers                │  │
│  │  - 'auth' → Authentication required                      │  │
│  │                                                            │  │
│  │  Submission Group Middleware:                            │  │
│  │  - 'can:mark-entry.lock' → Policy authorization         │  │
│  │                                                            │  │
│  │  Route Definition:                                       │  │
│  │  POST /api/mark-entry/submission/unlock/{batchId}      │  │
│  │    → UnlockBatchController@unlock                        │  │
│  └──────────────────────────────────────────────────────────┘  │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  UnlockBatchController::unlock()                         │  │
│  │                                                            │  │
│  │  Constructor Injection:                                  │  │
│  │  - MarkSubmissionService $submissionService             │  │
│  │  - MarkEntryAuditService $auditService                  │  │
│  │                                                            │  │
│  │  Execution Flow:                                         │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 1. Log request with context                │        │  │
│  │  │    - batchId, userId, authenticated flag   │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 2. Authentication Check                     │        │  │
│  │  │    - auth()->check()                        │        │  │
│  │  │    - Return 401 if failed                   │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 3. Authorization Check                      │        │  │
│  │  │    - hasRole('admin') OR                    │        │  │
│  │  │    - hasPermissionTo('mark-entry.admin')   │        │  │
│  │  │    - Return 403 if failed                   │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 4. Find Batch by ID                         │        │  │
│  │  │    - MarkImportBatch::find($batchId)       │        │  │
│  │  │    - Return 404 if not found                │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 5. Validate Request Input                   │        │  │
│  │  │    - reason: required|string|min:10|max:1000│        │  │
│  │  │    - Return 422 if validation fails         │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 6. Log Audit Trail                          │        │  │
│  │  │    - auditService->logAction()              │        │  │
│  │  │    - Action: 'unlock_requested'             │        │  │
│  │  │    - Includes: reason, user, timestamp      │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 7. Call Service to Unlock Batch             │        │  │
│  │  │    - submissionService->unlockBatch()      │        │  │
│  │  │    - Updates batch lifecycle_state          │        │  │
│  │  │    - Creates approval record                │        │  │
│  │  │    - May throw exception on failure         │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ 8. Return Success Response (JSON)           │        │  │
│  │  │    - HTTP 200                               │        │  │
│  │  │    - success: true                          │        │  │
│  │  │    - message, batch_id, lifecycle_state    │        │  │
│  │  │    - unlocked_at, unlocked_by               │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Error Handling (try/catch blocks)                       │  │
│  │                                                            │  │
│  │  ValidationException → 422 with errors                  │  │
│  │  Other Exception → 400 with message                      │  │
│  │                                                            │  │
│  │  All exceptions logged to storage/logs/laravel.log      │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
           ↓↓↓  JSON Response / Error  ↓↓↓
┌─────────────────────────────────────────────────────────────────┐
│                        USER BROWSER                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  unlockBatchConfirm() - Response Handler                 │  │
│  │                                                            │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ Check Response Status                       │        │  │
│  │  │ if !response.ok → Throw error               │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ Parse JSON Response                         │        │  │
│  │  │ const data = await response.json()          │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓                                              │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ Success Path                                │        │  │
│  │  │ - showMessage('success')                    │        │  │
│  │  │ - closeUnlockModal()                        │        │  │
│  │  │ - loadSubmittedBatches() [delayed 500ms]   │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           OR                                             │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ Error Path                                  │        │  │
│  │  │ - showMessage('error', error.message)      │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  │           ↓↓↓                                           │  │
│  │  ┌─────────────────────────────────────────────┐        │  │
│  │  │ Finally Block (ALWAYS EXECUTES)             │        │  │
│  │  │ - isUnlocking = false (hide spinner)       │        │  │
│  │  └─────────────────────────────────────────────┘        │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  UI State Update                                         │  │
│  │  - Modal visible: false                                  │  │
│  │  - Loading spinner: hidden                               │  │
│  │  - Toast message: displayed                              │  │
│  │  - Submission table: refreshed                           │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Request/Response Flow Diagram

### Success Flow
```
User enters reason (≥10 chars)
        ↓
Clicks "Unlock Batch" button
        ↓
unlockBatchConfirm() executes
        ↓
isUnlocking = true (spinner visible)
        ↓
Sends: POST /api/mark-entry/submission/unlock/{batchId}
       Headers: X-CSRF-TOKEN, Content-Type: application/json
       Body: { reason: "..." }
        ↓
Backend validates & processes
        ↓
Returns: { success: true, message: "...", data: {...} }
        ↓
Response handler:
  - showMessage('success')
  - closeUnlockModal()
  - loadSubmittedBatches() [500ms delay]
        ↓
Finally block: isUnlocking = false
        ↓
Modal closes, UI updates, spinner hidden
```

### Error Flow
```
Invalid input OR network error
        ↓
Error caught in catch block
        ↓
showMessage('error', error.message)
        ↓
Finally block: isUnlocking = false
        ↓
Spinner hidden, buttons enabled
Modal remains open for retry
```

## Security Architecture

### Authentication Layer
```
Middleware: 'auth'
  ├─ Checks session exists
  ├─ Verifies user is logged in
  └─ Redirects to login if not authenticated
```

### Authorization Layer
```
Middleware: 'can:mark-entry.lock' (Applied to submission group)
  ├─ Checks user has permission 'mark-entry.lock'
  └─ Or can be overridden by explicit role check
  
Additional Check in Controller:
  ├─ hasRole('admin') OR
  └─ hasPermissionTo('mark-entry.admin')
```

### CSRF Protection
```
Middleware: 'web'
  ├─ Verifies X-CSRF-TOKEN header matches session token
  ├─ Tokens stored in meta tag: <meta name="csrf-token">
  └─ Mismatch returns 419 error
```

### Request Validation
```
ValidateRequest (Laravel Built-in)
  ├─ reason: required
  ├─ reason: string
  ├─ reason: min:10
  ├─ reason: max:1000
  └─ Failure returns 422 with error details
```

## Database Schema Impact

### MarkImportBatch Table
```
Table: mark_import_batches
  ├─ id: Primary Key
  ├─ lifecycle_state: Current state (submitted, locked, etc.)
  ├─ locked_at: Timestamp when locked
  ├─ unlocked_at: Timestamp when unlocked (updated)
  ├─ updated_at: Last modified timestamp
  └─ ... other columns
```

### Audit Trail
```
Table: audit_logs (or similar)
  ├─ id: Primary Key
  ├─ batch_id: Foreign Key to mark_import_batches
  ├─ action: 'unlock_requested'
  ├─ user_id: Admin who performed action
  ├─ details: JSON { reason: "..." }
  ├─ created_at: Timestamp
  └─ ... other columns
```

### Approvals Table
```
Table: batch_approvals (or similar)
  ├─ id: Primary Key
  ├─ batch_id: Foreign Key
  ├─ approved_by: User ID
  ├─ approved_at: Timestamp
  └─ ... state tracking columns
```

## Service Layer

### MarkSubmissionService::unlockBatch()
```php
public function unlockBatch(MarkImportBatch $batch, User $user): BatchApproval
{
    // Logic:
    // 1. Update batch lifecycle_state to 'ready_for_resubmission'
    // 2. Update batch unlocked_at timestamp
    // 3. Create/update BatchApproval record
    // 4. Fire events for any listeners
    // 5. Return the updated approval record
    
    // Returns BatchApproval model
}
```

### MarkEntryAuditService::logAction()
```php
public function logAction(
    MarkImportBatch $batch,
    string $action,
    User $user,
    array $details = []
): AuditLog
{
    // Logic:
    // 1. Create AuditLog record
    // 2. Set action type
    // 3. Store user who performed action
    // 4. Serialize $details to JSON
    // 5. Create timestamp
    
    // Returns AuditLog model
}
```

## State Transitions

```
                    Unlock Batch Request
                            ↓
    ┌─────────────────────────────────────┐
    │   SUBMITTED (locked)                │
    │   lifecycle_state: 'submitted'      │
    └─────────────────────────────────────┘
                            ↓
    ┌─────────────────────────────────────┐
    │   READY FOR RESUBMISSION             │
    │   lifecycle_state: 'ready_...'       │
    └─────────────────────────────────────┘
                            ↓
    School can re-upload marks, and batch
    moves back through validation pipeline
```

## Performance Considerations

### Query Optimization
```php
// Uses direct find (indexed on id)
$batch = MarkImportBatch::find($batchId);

// Efficient role/permission checks
$user->hasRole('admin')  // Uses cache layer
$user->hasPermissionTo() // Uses cache layer

// Batch refresh only fetches necessary columns
$batch->fresh()  // Gets latest from DB
```

### Response Time
- Authentication check: ~1ms (session lookup)
- Authorization check: ~5-10ms (role cache)
- Batch lookup: ~2-5ms (indexed query)
- Audit logging: ~5-10ms (insert operation)
- Service unlock: ~10-20ms (update operations)
- **Total**: ~25-50ms typical response time

### Load Considerations
- Single batch lookup (no N+1 queries)
- Minimal memory footprint
- Single audit log write
- No batch operations or loops
- Suitable for concurrent requests

## Error Scenarios & HTTP Status Codes

| Scenario | Status | Message | Action |
|----------|--------|---------|--------|
| Not authenticated | 401 | "Not authenticated..." | Show login prompt |
| Not admin | 403 | "Unauthorized: Admin..." | Show permission denied |
| Batch not found | 404 | "Batch with ID X not found" | Show not found error |
| Validation failed | 422 | "Validation failed" + errors | Show validation errors |
| Server error | 400 | Exception message | Show error with retry option |
| Network error | N/A | Browser error | Show network error message |

## Integration Points

### Services Used
1. **MarkSubmissionService**
   - `unlockBatch($batch, $user)` - Updates batch state
   
2. **MarkEntryAuditService**
   - `logAction($batch, $action, $user, $details)` - Logs action

### Models Updated
1. **MarkImportBatch**
   - lifecycle_state updated
   - unlocked_at timestamp set
   
2. **BatchApproval** (or similar)
   - Record created/updated with unlock info

### Events (if applicable)
- `MarkBatchUnlocked` - Event fired after unlock
- Can trigger notifications, webhooks, etc.

## Future Enhancements

1. **Bulk Unlock** - Unlock multiple batches at once
2. **Scheduled Unlock** - Lock unlock for specific date/time
3. **Notifications** - Notify schools/submitters of unlock
4. **Re-unlock Prevention** - Limit re-unlocks per batch
5. **Lock Reason** - Also require reason when locking

---

**Architecture Version**: 1.0  
**Last Updated**: 2026-02-14  
**Status**: Production Ready
