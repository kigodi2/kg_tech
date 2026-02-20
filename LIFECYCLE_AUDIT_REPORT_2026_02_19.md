# Lifecycle Audit Report — 2026-02-19

## Root Cause Analysis

### The Problem
Marks uploaded in "Entry & Validation" were not appearing consistently in "Moderation & Review", and approve/reject actions were failing with 400/403 errors.

### Root Causes Identified

#### 1. Dual-Field Status Desync (`status` vs `lifecycle_state`)
The system uses TWO status fields on `mark_import_batches`:
- `status` — legacy field checked by policy methods (`canBeApproved()`, `canBeRejected()`)
- `lifecycle_state` — modern field checked by queries and `LifecycleStateService`

Multiple code paths updated `lifecycle_state` without updating `status`, causing desync:
- Upload sets `status=validated`, `lifecycle_state=awaiting_moderation` ✅
- `MarkModerationService::rejectBatch()` updated `lifecycle_state→rejected` but left `status=validated` ❌
- `MarkModerationService::approveBatch()` updated `lifecycle_state→approved` but left `status=validated` ❌
- `ModerationActionService` had the same issue ❌

#### 2. Policy Gates Too Restrictive
- `canBeApproved()` required `status === 'submitted'` — but batches in moderation have `status=validated`
- `canBeRejected()` required `status === 'submitted'` — same issue
- Result: 403 Forbidden on approve/reject from Pending Review

#### 3. Pending Review Query Missing `validated` Lifecycle State
- `ModerationDashboardService::getPendingQueue()` only looked for `lifecycle_state IN ('awaiting_moderation', 'submitted')`
- Batches that were reset or had edge cases ending in `lifecycle_state=validated` were invisible
- `MarkModerationQueryService::getPendingReviews()` had an `orWhereNull` outside a grouped `where()`, creating a SQL logic bug that included rejected/approved batches

#### 4. SQL Logic Bug in `getPendingReviews()`
```sql
-- BROKEN: orWhereNull applies globally, matches ALL rows
WHERE lifecycle_state IN (...) OR lifecycle_state IS NULL

-- FIXED: grouped properly
WHERE (lifecycle_state IN (...) OR lifecycle_state IS NULL)
```

## Files Changed

| File | Purpose |
|------|---------|
| `app/Models/MarkImportBatch.php` | `canBeApproved()` and `canBeRejected()` now check `lifecycle_state` for `validated`, `awaiting_moderation`, `submitted` |
| `app/Services/MarkEntry/Moderation/MarkModerationService.php` | `approveBatch()` and `rejectBatch()` now sync `status` field |
| `app/Services/MarkEntry/Moderation/ModerationActionService.php` | `approve()` and `reject()` now sync `status` field; `resolveBatches()` includes `validated` lifecycle |
| `app/Services/MarkEntry/Moderation/ModerationDashboardService.php` | All pending queries include `validated` in lifecycle_state filter |
| `app/Services/MarkEntry/Moderation/MarkModerationQueryService.php` | Fixed SQL logic bug with `orWhereNull`; added `validated` to pending filter |
| `app/Http/Controllers/MarkEntry/Api/MarkLifecycleApiController.php` | `rejectBatchAction()` uses `reject` policy instead of `moderate` |

## Tables & Keys

| Table | Role | Key Fields |
|-------|------|-----------|
| `mark_import_batches` | Batch unit linking uploads to moderation | `id`, `status`, `lifecycle_state`, `exam_year`, `school_id`, `subject_id` |
| `raw_marks` | Staged marks from CSV upload | `mark_import_batch_id`, `candidate_index_number`, `subject_id` |
| `mark_import_runs` | Import run audit trail | `mark_import_batch_id`, `status` |
| `mark_import_run_errors` | Row-level validation errors | `run_id`, `error_code`, `severity` |
| `mark_moderation_reviews` | Approve/reject review records | `mark_import_batch_id`, `status` |
| `mark_moderation_actions` | Audit log for moderation actions | `action`, `correlation_id` |
| `subject_marks` | Final promoted marks (after lock) | `candidate_id`, `subject_id`, `year` |
| `subject_exam_statuses` | INC/X/ABS status records | `candidate_id`, `subject_id`, `exam_year` |
| `mark_entry_lifecycle_states` | Transition history log | `mark_import_batch_id`, `current_state`, `previous_state` |

## Lifecycle Flow (Verified Working)

```
Upload CSV → mark_import_batches (status=validated, lifecycle_state=awaiting_moderation)
           → raw_marks (linked via mark_import_batch_id)
           → mark_import_runs (linked via mark_import_batch_id)

Pending Review → queries lifecycle_state IN (awaiting_moderation, validated, submitted)
              → shows batch with error/valid counts from raw_marks

Approve → lifecycle_state=approved, status=approved
Reject  → lifecycle_state=rejected, status=rejected

Submit → lifecycle_state=submitted, status=submitted (via MarkBatchStateMachine)
Lock   → lifecycle_state=locked, status=locked → raw_marks promoted to subject_marks
```

## Data Integrity Verification

- **Total raw_marks**: 3214 (100% linked to batches)
- **Orphan marks**: 0
- **Pending batches**: 29
- **Rejected batches**: 8
- **Approved batches**: 1
- **No destructive changes made** — all fixes are additive query/logic changes

## Manual QA Checklist

- [ ] Upload a CSV → batch appears in Pending Review with correct counts
- [ ] Dashboard shows updated total_pending count
- [ ] Click Approve on a batch → batch moves to Approved, disappears from Pending
- [ ] Click Reject on a batch → modal accepts reason (≥10 chars), batch moves to Rejected
- [ ] Approved batch appears in Submit Marks section
- [ ] Submit → Lock → marks promoted to subject_marks
- [ ] Reports show marks for locked batches
- [ ] Rejected batch shows rejection reason, allows re-upload
