# ARCHITECTURE COMPARISON: CURRENT vs. PROPOSED

---

## 1. WORKFLOW COMPARISON

### CURRENT WORKFLOW
```
Teacher uploads CSV
    ↓
Auto-validate (server-side checks)
    ├─ If errors → Download error CSV, fix, reupload
    └─ If valid → Batch created
    ↓
Lock batch (teacher clicks "Lock")
    ↓
Generate scoresheet PDF (if needed)
    ↓
Done

Issues:
- No human review before lock
- No way to flag quality issues
- Can't un-approve marks
- No moderation gate
- Marks lock too early
```

### PROPOSED WORKFLOW
```
Teacher uploads CSV
    ↓
[PHASE 1: ENTRY]
Auto-validate (detailed validation rules)
    ├─ If errors → Generate error report, teacher fixes, reupload
    └─ If valid → Batch status = "validated"
    ↓
[PHASE 2: MODERATION]
HOD/Supervisor reviews marks
    ├─ Flags issues, detects outliers
    ├─ Decision point:
    │  ├─ ✅ APPROVE → Batch status = "approved"
    │  ├─ ❌ REJECT → Send feedback, teacher resubmits
    │  └─ ⏸️ HOLD → Awaiting more info
    ↓
[PHASE 3: SUBMISSION & LOCKING]
HOD/Supervisor formally locks batch
    └─ Batch status = "submitted" (fully locked)
    ↓
[PHASE 4: PROCESSING]
System calculates grades automatically
    └─ Batch status = "processed"
    ↓
[PHASE 5: REPORTING]
Generate scoresheets, exports, reports
    └─ Batch status = "reporting"
    ↓
[PHASE 6: AUDIT]
Complete audit trail available
    └─ Who approved? When? Any changes?

Benefits:
✅ Quality gate before lock
✅ Human review mandatory
✅ Feedback loop for improvement
✅ Clear approval chain
✅ Complete audit trail
✅ Locked only when ready for authority
```

---

## 2. CONTROLLER COMPARISON

### CURRENT: Single Monolithic Controller
```
MarkEntryController (1,342 lines)
├─ index()
├─ uploadMarks()
├─ downloadTemplate()
├─ getRegions()
├─ getDistricts()
├─ getSchools()
├─ getSubjects()
├─ getSubjectsBySchoolAndYear()
├─ getBatchDetails()
├─ lockBatch()
├─ printScoresheet()
├─ bulkExportScoresheets()
├─ downloadBulkCsvExport()
├─ downloadDistrictBulkCsvExport()
├─ downloadDistrictBulkScoresheetExport()
└─ generateSchoolScoresheetZip()

Issues:
- Single Responsibility Principle violated
- Hard to understand what phase each method belongs to
- Difficult to add new phases
- Testing is complex (too many scenarios per controller)
- Reusing methods across unrelated operations
```

### PROPOSED: Focused Controllers by Lifecycle Phase
```
MarkEntryUploadController (Entry Phase - ~150 lines)
├─ index()
├─ downloadTemplate()
├─ upload()
├─ uploadSchoolBulk()
├─ uploadDistrictBulk()
├─ batchDetails()
└─ uploadStatus()

MarkValidationController (Validation - ~100 lines)
├─ report()
├─ downloadErrorCsv()
└─ errorBatches()

MarkEntryModerationController (Moderation - ~200 lines)
├─ dashboard()
├─ reviewBatch()
├─ scoresheetPreview()
├─ flagIssue()
├─ approveBatch()
├─ rejectBatch()
└─ feedbackLog()

MarkEntrySubmissionController (Submission - ~150 lines)
├─ dashboard()
├─ lockStatus()
├─ lockBatch()
├─ submitBatch()
├─ submissionHistory()
└─ unlockBatch()

MarkEntryReportController (Reporting - ~200 lines)
├─ scoresheet()
├─ scoresheetBulk()
├─ csvExport()
├─ dailyEntryReport()
└─ extremityAnalysis()

MarkEntryMonitoringController (Audit - ~150 lines)
├─ lifecycleDashboard()
├─ batchHistory()
├─ changeLog()
├─ userActivity()
└─ auditTrail()

MarkEntryApiController (Shared Filtering - ~100 lines)
├─ regions()
├─ districts()
├─ schools()
├─ subjects()
└─ examYears()

MarkEntryAdminController (Admin - ~150 lines)
├─ configuration()
├─ manageBatches()
├─ accessControl()
└─ updateConfiguration()

Benefits:
✅ Single Responsibility Principle
✅ Clear phase ownership
✅ Easier to test (fewer scenarios per controller)
✅ Easier to add new lifecycle phases
✅ Code reuse via shared services
✅ ~1,200 lines distributed vs. 1,342 monolithic
```

---

## 3. SERVICE LAYER COMPARISON

### CURRENT: Mixed Responsibilities
```
app/Services/MarkImport/
├─ MarkImportService (Handles upload + validation + processing)
├─ MarkValidationService (Validation rules only)
├─ MarkTemplateService (Template generation)
├─ SubjectFilterService (Subject filtering)
├─ AcseeMarkTemplateService (ACSEE-specific template)
├─ CsvIntegrityService (CSV validation)
├─ MarkRowLockingService (Locking logic)
├─ ScoresheetService (PDF generation)
├─ BulkCsvExportService (CSV export)
└─ ZipSignerService (ZIP signing)

Issues:
- 11 services with overlapping concerns
- No clear separation by lifecycle phase
- Hard to find where specific logic lives
- Moderation logic not separated
- Audit trail logic mixed in controllers
```

### PROPOSED: Organized by Lifecycle Phase
```
app/Services/MarkEntry/
│
├─ Shared/
│  ├─ LifecycleStateService         (State machine)
│  ├─ PermissionService              (Authorization)
│  ├─ ExamYearService                (Year validation)
│  └─ MailNotificationService        (Email alerts)
│
├─ Entry/
│  ├─ MarkUploadService              (CSV/ZIP parsing)
│  └─ TemplateGenerationService      (Template creation)
│
├─ Validation/
│  ├─ ValidationRuleService          (Rules engine)
│  ├─ ErrorReportService             (Error formatting)
│  └─ CsvIntegrityService            (CSV validation)
│
├─ Moderation/
│  ├─ MarkModerationService          (Review workflow)
│  ├─ IssueFlaggerService            (Outlier detection)
│  └─ ApprovalWorkflowService        (Approve/reject)
│
├─ Processing/
│  ├─ GradeCalculationService        (Grade from marks)
│  ├─ RankingService                 (Position calculation)
│  └─ AggregateService               (Summary aggregates)
│
├─ Submission/
│  ├─ LockingService                 (Lock/unlock)
│  └─ SubmissionPackageService       (Submission format)
│
├─ Reporting/
│  ├─ ScoresheetService              (PDF generation)
│  ├─ CsvExportService               (CSV export)
│  └─ ReportingService               (Various reports)
│
└─ Audit/
   ├─ AuditTrailService              (Change logging)
   ├─ ChangeTrackingService          (Diff tracking)
   └─ ComplianceService              (Audit reports)

Benefits:
✅ Clear organization by phase
✅ Easy to locate specific logic
✅ Services are reusable across controllers
✅ Moderation explicitly separated
✅ Audit trail systematized
```

---

## 4. DATABASE SCHEMA COMPARISON

### CURRENT TABLES
```
raw_marks (staging)
├─ mark_import_batch_id
├─ candidate_id
├─ paper_1_marks, paper_2_marks, ...
├─ has_errors
├─ error_messages
├─ processed_at
├─ is_locked, locked_at, locked_by
└─ Limitations:
   - No explicit state column
   - No moderation data
   - No change tracking
   - locked_by shows lock, not approval

subject_marks (final)
├─ candidate_id, exam_type_id, subject_id
├─ marks_obtained, grade
└─ Limitations:
   - Year is loose integer (not FK)
   - No exam_year_id FK
   - No approval tracking
   - No change history

mark_import_batches (tracking)
├─ status (draft|validated|locked|processed)
├─ imported_by, imported_at
├─ validated_by, validated_at
├─ locked_by, locked_at
└─ Limitations:
   - No explicit lifecycle_state
   - No rejection tracking
   - No moderation data
   - No resubmission chain
```

### PROPOSED TABLES (Current + New)

**Current Tables** (Enhanced):
```
raw_marks
├─ (existing columns)
├─ + paper_identifier (e.g., 'paper_1', 'practical')
├─ + entry_status (explicit state)
├─ + validation_notes (why pass/fail)
└─ + moderation columns

subject_marks
├─ (existing columns)
├─ + exam_year_id FK (tight coupling)
├─ + approval_date (when approved)
└─ + version (for tracking changes)

mark_import_batches
├─ (existing columns)
├─ + lifecycle_state (explicit state)
├─ + lifecycle_history (JSON trail)
├─ + rejection_reason (if rejected)
├─ + requires_resubmission (boolean)
├─ + resubmitted_from_batch_id FK (chain)
├─ + latest_review_id FK (moderation)
└─ + batch_hash (integrity)
```

**New Tables**:
```
mark_entry_lifecycle_states (State transitions)
├─ batch_id
├─ current_state, previous_state
├─ transitioned_by, transitioned_at
├─ transition_reason
└─ history JSON

mark_moderation_reviews (Moderation records)
├─ batch_id
├─ reviewer_id, review_type (role-based)
├─ status (pending|approved|rejected)
├─ feedback, flagged_issues
├─ reviewed_at, signature
└─ Enables tracking who reviewed, when, result

mark_entry_changes (Change tracking)
├─ raw_mark_id
├─ changed_by, change_type
├─ field_name, old_value, new_value
├─ reason (why changed)
├─ changed_at, ip_address
└─ Complete audit trail

mark_batch_approvals (Approval signatures)
├─ batch_id
├─ approval_level (validation|moderation|submission)
├─ approved_by, approved_at
├─ signature (digital)
└─ Formal approval record
```

**Benefits of New Schema**:
✅ Explicit state machine
✅ Complete moderation trail
✅ Change tracking with reasons
✅ Approval signatures
✅ Supports resubmission chains
✅ NECTA compliance ready

**Data Integrity**:
```
Current risks:
- Marks could be modified after locked
- No way to prove who approved
- Moderation gate not enforced
- Unclear why marks changed

Proposed safeguards:
- mark_entry_changes logs all modifications
- mark_batch_approvals tracks formal approvals
- Lifecycle state prevents invalid transitions
- Audit trail immutable (UPDATED_AT = null)
```
```

---

## 5. PERMISSION/AUTHORIZATION COMPARISON

### CURRENT: Generic Role Check
```
Route::middleware('auth')->group(function () {
    // All authenticated users can access mark entry
    Route::get('/mark-entry/acsee', ...)
});

// No operation-level permissions
// No role-based filtering
// All teachers can access all marks
```

**Problems**:
- Teacher from School A can upload marks for School B
- No distinction between upload/moderate/lock permissions
- Cannot prevent certain operations by role
- School registrar has same access as teacher
- HOD has no special moderation rights
- Admin cannot audit without being "logged in as teacher"

### PROPOSED: Granular Permission Model
```
// Permission gates
Gate::define('mark-entry.upload', fn($user) =>
    in_array($user->role->code, ['teacher', 'school_registrar', 'admin'])
);

Gate::define('mark-entry.moderate', fn($user) =>
    in_array($user->role->code, ['school_hod', 'district_supervisor', 'admin'])
);

Gate::define('mark-entry.lock', fn($user) =>
    in_array($user->role->code, ['school_hod', 'district_supervisor', 'admin'])
);

Gate::define('mark-entry.unlock', fn($user) =>
    $user->isAdmin()
);

Gate::define('mark-entry.audit', fn($user) =>
    $user->isAdmin()
);

// Policy-based authorization
Route::post('batch/{batch}/approve', [...])
    ->middleware('can:approve,batch');  // Uses MarkImportBatchPolicy

Route::post('batch/{batch}/lock', [...])
    ->middleware('can:lock,batch');     // Uses MarkImportBatchPolicy
```

**MarkImportBatchPolicy.php**:
```php
class MarkImportBatchPolicy {
    
    public function moderate(User $user, MarkImportBatch $batch): bool {
        // Admins can moderate any batch
        if ($user->isAdmin()) return true;
        
        // HOD can moderate their school's batches
        if ($user->role->code === 'school_hod') {
            return $batch->school_id === $user->school_id;
        }
        
        // District supervisor can moderate their district's batches
        if ($user->role->code === 'district_supervisor') {
            return $batch->school->district_id === $user->district_id;
        }
        
        return false;
    }

    public function lock(User $user, MarkImportBatch $batch): bool {
        // User must be able to moderate AND batch must be approved
        return $this->moderate($user, $batch) && 
               $batch->lifecycle_state === 'approved';
    }
}
```

**Benefits of Proposed Model**:
✅ Operation-level permissions
✅ Scope-aware (school vs. district)
✅ Role-based access control
✅ Cannot escalate privileges
✅ Testable permission logic
✅ Audit-friendly (logs which permission was checked)

---

## 6. USER EXPERIENCE COMPARISON

### CURRENT UI
```
Single page: /mark-entry/acsee
├─ Context selection (Year, Region, District, School, Subject)
├─ Tabs: Single CSV | School Bulk | District Bulk
├─ Upload section
├─ Preview section
└─ Import progress

Limitations:
- All features on one page
- No indication of where marks are in workflow
- No moderation interface
- Cannot see approval status
- Error handling unclear
- No audit trail visible
```

### PROPOSED UI (Sidebar-Based Navigation)
```
Left Sidebar (6 Groups):
│
├─ 📊 ENTRY & VALIDATION
│  ├─ 📤 Upload Marks
│  ├─ ⚠️ Error Batches
│  └─ ✅ View Upload Status
│
├─ 🔍 MODERATION & REVIEW
│  ├─ 👁️ Review Dashboard
│  ├─ 🚩 Flag Issues
│  ├─ ✔️ Approve Batch
│  └─ ❌ Reject Batch
│
├─ 🔒 SUBMISSION & LOCKING
│  ├─ 📌 Lock Approved
│  ├─ 🔓 Unlock (Admin)
│  └─ 📤 Submit to Authority
│
├─ 📑 REPORTS & EXPORTS
│  ├─ 📋 Scoresheet PDFs
│  ├─ 📊 CSV/Excel Exports
│  └─ 🎯 Analysis Reports
│
├─ 🕐 MONITORING & AUDIT
│  ├─ ⏱️ Lifecycle Dashboard
│  ├─ 📝 Change Log
│  └─ 🔐 Audit Trail
│
└─ ⚙️ ADMINISTRATION
   ├─ 🎓 ACSEE Configuration
   └─ 🔐 Access Control

Main Content Area:
└─ Context-specific content for selected phase

Benefits:
✅ Clear navigation by lifecycle phase
✅ Users know where they are in workflow
✅ Moderation interface dedicated space
✅ Audit trail easily accessible
✅ Role-based menu items
✅ Scalable to CSEE, Internal Exams
```

---

## 7. SCALABILITY COMPARISON

### CURRENT: Single Exam Type, Single Entry Point
```
Routes: /mark-entry/acsee
Controller: MarkEntryController
Database: One batches table, shared columns

Limitations:
- Cannot easily add CSEE
- Cannot easily add Internal Exams
- Paper structure hardcoded
- Grade scales hardcoded
- Configuration scattered
- Difficult to support different workflows per exam
```

### PROPOSED: Multi-Exam-Type, Configurable
```
Routes:
├─ /mark-entry/acsee/...
├─ /mark-entry/csee/...
└─ /mark-entry/internal/...

Controllers (by exam type):
├─ AcseeMarkEntryController extends MarkEntryController
├─ CseeMarkEntryController extends MarkEntryController
└─ InternalMarkEntryController extends MarkEntryController

Configuration:
├─ config/mark-entry.php
│  ├─ acsee => [papers => [...], max_marks => 100, grade_scale => 'NECTA_ACSEE']
│  ├─ csee => [papers => [...], max_marks => 150, grade_scale => 'NECTA_CSEE']
│  └─ internal => [papers => [], max_marks => 'configurable', ...]

Services:
├─ Shared services (validation, moderation, locking)
└─ Exam-type-specific services (GradeCalculationService_ACSEE)

Database:
├─ Polymorphic design with exam_type_id FK
├─ Paper structure stored in exam_type.paper_config JSON
├─ Grade scale stored in grading_profile.grade_config JSON

Benefits:
✅ Add new exam type in weeks, not months
✅ Each exam type has own configuration
✅ Shared logic reused
✅ Paper structure flexible
✅ Grade scales configurable
```

---

## 8. COMPLIANCE & AUDIT COMPARISON

### CURRENT: Basic Tracking Only
```
Audit Trail:
├─ Who uploaded? (imported_by, imported_at)
├─ Who locked? (locked_by, locked_at)
├─ What errors? (error_messages in raw_marks)
└─ Limitations:
   - No approval tracking
   - No rejection tracking
   - No modification tracking
   - No reason documentation
   - No change history
   - No moderator sign-off
   - Non-compliant for national exam authority

Compliance Risks:
❌ Cannot prove marks approved by HOD
❌ Cannot show who modified marks
❌ Cannot enforce approval gates
❌ Cannot generate compliance reports
❌ No digital signatures
❌ No formal submission records
```

### PROPOSED: Full Audit Trail & Compliance
```
Audit Trail:
├─ Phase 1: ENTRY
│  └─ Who uploaded? (user, timestamp, file, hash)
│
├─ Phase 2: VALIDATION
│  └─ What errors? (rule, field, severity, timestamp)
│
├─ Phase 3: MODERATION
│  ├─ Who reviewed? (reviewer, timestamp)
│  ├─ What decision? (approved/rejected, timestamp)
│  ├─ What feedback? (text, timestamp)
│  ├─ What issues flagged? (candidate, field, severity)
│  └─ Digital signature (phase 2)
│
├─ Phase 4: CHANGES
│  ├─ What changed? (field, old → new)
│  ├─ When? (timestamp)
│  ├─ Who? (user)
│  ├─ Why? (reason)
│  └─ From where? (IP address)
│
├─ Phase 5: LOCKING
│  ├─ When locked? (timestamp)
│  ├─ By whom? (user)
│  └─ Version locked (batch_hash for integrity)
│
└─ Phase 6: SUBMISSION
   ├─ When submitted? (timestamp)
   ├─ To whom? (authority recipient)
   ├─ Package hash (for verification)
   └─ Delivery confirmation

New Tables:
├─ mark_entry_lifecycle_states (state transitions)
├─ mark_moderation_reviews (approval record)
├─ mark_entry_changes (change tracking)
├─ mark_batch_approvals (formal approval)
└─ audit_log (comprehensive operations log)

Compliance Features:
✅ Can prove HOD approval
✅ Can show all mark modifications
✅ Can enforce approval gates (state machine)
✅ Can generate NECTA-compliant reports
✅ Digital signatures supported
✅ Formal submission package
✅ Immutable audit trail (UPDATED_AT = null)
✅ Batch integrity verified (checksum)
```

---

## 9. TESTING COMPARISON

### CURRENT: Difficult to Test
```
MarkEntryController (1,342 lines)
├─ Dozens of test cases per method
├─ Overlapping scenarios
├─ Hard to isolate failures
├─ Service dependencies tangled
└─ No clear test boundaries

Problems:
- Testing upload() means testing validation, filtering, PDF generation
- Mocking is complex (11 service dependencies)
- Cannot test moderation (doesn't exist)
- Cannot test rejection (doesn't exist)
- Cannot test audit trail (incomplete)
- Test suite slow (many scenarios, much setup)
```

### PROPOSED: Testable Architecture
```
Individual Controllers (150-200 lines each)
├─ MarkEntryUploadController tests: 20-30 test cases
├─ MarkEntryModerationController tests: 30-40 test cases
├─ MarkEntrySubmissionController tests: 20-30 test cases
└─ Each controller tests one phase

Services (focused responsibility)
├─ LifecycleStateService tests: 25 cases
├─ MarkModerationService tests: 30 cases
├─ AuditTrailService tests: 20 cases
└─ Each service tests one concern

Test Types:
├─ Unit Tests
│  ├─ Service logic (state machine, moderation, validation)
│  ├─ Policy authorization (can moderate? can lock?)
│  └─ Model relationships
│
├─ Integration Tests
│  ├─ Complete workflows (upload → validate → moderate → lock)
│  ├─ State transitions (verify invalid transitions blocked)
│  ├─ Audit trail (verify all operations logged)
│  └─ Permission enforcement (verify unauthorized access denied)
│
├─ Feature Tests
│  ├─ Moderation dashboard (accessible? shows right data?)
│  ├─ Approval workflow (approve button works? state changes?)
│  └─ Audit viewer (shows all changes? correct timestamps?)
│
└─ Load Tests
   ├─ 400K candidates per year
   ├─ Peak concurrent users
   └─ PDF generation at scale

Benefits:
✅ Clear test boundaries per controller/service
✅ Easier to mock dependencies
✅ Faster test execution
✅ Higher code coverage possible
✅ Can test all phases (currently can't)
✅ Parallel test execution
```

---

## SUMMARY: WHY THIS MATTERS

| Aspect | Impact | Current | Proposed |
|--------|--------|---------|----------|
| **Quality Assurance** | Critical | HOD review not enforced | Mandatory moderation gate |
| **Compliance** | Critical | No approval proof | Full audit trail, signatures |
| **User Experience** | High | Single page, unclear | Sidebar navigation, clear phases |
| **Code Maintainability** | High | Monolithic | Modular by phase |
| **Scalability** | Medium | ACSEE only | ACSEE, CSEE, Internal |
| **Testing** | Medium | Hard to test | Easy to test |
| **Debugging** | Medium | Hard to find issues | Clear responsibility per service |
| **Security** | High | All users same access | Granular permissions |
| **Audit Trail** | Critical | Incomplete | Complete |
| **Performance** | Medium | No caching | Optimizable |

---

**Bottom Line**: The proposed architecture transforms the mark entry system from a **functional but unsafe single-point-of-entry** into an **enterprise-grade, compliant, lifecycle-based system** suitable for national-level examination authority operations.

The additional effort (~9 weeks) is justified by:
1. Compliance requirements (NECTA)
2. Quality assurance (mandatory moderation)
3. Scalability (support multiple exam types)
4. Maintainability (easier to debug, extend, test)
5. User experience (clear navigation, better workflows)

---

**Document Status**: APPROVED FOR IMPLEMENTATION  
**Version**: 1.0  
**Last Updated**: February 13, 2026
