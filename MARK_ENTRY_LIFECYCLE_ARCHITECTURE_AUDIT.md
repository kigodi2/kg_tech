# MARK ENTRY LIFECYCLE ARCHITECTURE: TECHNICAL AUDIT & RESTRUCTURING PROPOSAL

**Document**: Technical Architecture Audit  
**System**: Integrated Results Management System (IRMS) - ACSEE Mark Entry Module  
**Scope**: Complete examination lifecycle restructuring  
**Prepared**: February 13, 2026  
**Audience**: System Architects, Laravel Engineers, NECTA Examination Standards  

---

## SECTION A: CURRENT SYSTEM ANALYSIS

### A.1 EXISTING IMPLEMENTATION OVERVIEW

The IRMS Mark Entry module currently operates as a single-phase system focused on **data ingestion** (CSV/ZIP upload) with minimal lifecycle management. The system is at `/mark-entry/acsee` and handles mark entry for ACSEE (Advanced Certificate of Secondary Education) examinations.

#### A.1.1 Current Route Structure

```
Routes/web.php (lines 1351-1372):
├── GET  /mark-entry/acsee                          → MarkEntryController@index
├── POST /mark-entry/acsee/upload                   → MarkEntryController@uploadMarks
├── GET  /mark-entry/acsee/download-template        → MarkEntryController@downloadTemplate
├── GET  /mark-entry/acsee/batch/{batchId}          → MarkEntryController@getBatchDetails
├── GET  /mark-entry/acsee/batch/{batchId}/error-report
├── POST /mark-entry/acsee/batch/{batchId}/lock     → MarkEntryController@lockBatch
├── GET  /mark-entry/acsee/scoresheet/print         → MarkEntryController@printScoresheet
├── GET  /mark-entry/acsee/scoresheet/bulk-export   → MarkEntryController@bulkExportScoresheets
├── GET  /mark-entry/acsee/bulk-csv-download        → MarkEntryController@downloadBulkCsvExport
├── GET  /mark-entry/acsee/district-bulk-csv-download
├── GET  /mark-entry/acsee/district-bulk-scoresheet-download
└── Various API endpoints for hierarchical filtering
```

**Assessment**: Routes are functionally correct but lack **logical grouping** and **lifecycle representation**. All routes are flat; no distinction between entry, validation, moderation, or processing phases.

---

### A.2 CONTROLLER ARCHITECTURE

#### A.2.1 MarkEntryController Structure

**Location**: `app/Http/Controllers/MarkEntryController.php` (1,342 lines)

**Current Responsibilities** (Mixed concerns):
- View rendering (`index()`)
- Hierarchical filtering (6 API methods: `getRegions`, `getDistricts`, `getSchools`, `getSubjects`, `getSubjectsBySchoolAndYear`)
- Mark upload & CSV template generation (`uploadMarks()`, `downloadTemplate()`)
- Batch management (`getBatchDetails()`, `lockBatch()`)
- Report generation (scoresheet PDFs, bulk exports)
- File download operations (CSV, ZIP, PDF)

**Constructor Dependencies** (11 injected services):
```php
MarkImportService
MarkValidationService
MarkTemplateService
SubjectFilterService
AcseeMarkTemplateService
CsvIntegrityService
MarkRowLockingService
ExamYearValidationService
ScoresheetService
BulkCsvExportService
```

**Assessment**: Single controller handling **entry, filtering, validation, locking, export, and reporting** — a clear violation of Single Responsibility Principle. This creates maintenance overhead and makes lifecycle management opaque.

---

### A.3 DATABASE SCHEMA

#### A.3.1 Core Mark Storage Tables

**raw_marks** (temporary/staging):
```
Columns: mark_import_batch_id, candidate_id, subject_id, row_number,
         paper_1_marks, paper_2_marks, paper_3_marks, practical_marks, project_marks,
         has_errors, error_messages, raw_data, processed_at, is_locked, locked_at, locked_by
Status: Draft → Validated → Locked → Processed (tracked via mark_import_batch_id)
```

**subject_marks** (final):
```
Columns: candidate_id, exam_type_id, subject_id, year,
         marks_obtained, max_marks, percentage, grade
Constraints: Unique [candidate_id, exam_type_id, subject_id, year]
```

**mark_import_batches** (import tracking):
```
Columns: batch_code, exam_year, school_id, subject_id, combination_id,
         status (draft|validated|locked|processed), 
         total_records, valid_records, error_records,
         imported_by, imported_at, validated_by, validated_at,
         locked_by, locked_at, processed_by, processed_at
```

**Assessment**: 
- ✅ **Strengths**:
  - Staging table (`raw_marks`) captures validation state
  - Batch tracking provides audit trail
  - Separation of raw vs. final marks prevents data loss
  - Year isolation via `exam_year_id` in `mark_import_batches`

- ❌ **Limitations**:
  - `raw_marks` lacks `subject_id` foreign key (added only via migration 2026_02_11)
  - No **moderation log table** — changes made during review are not tracked
  - No **paper-level locking** — only batch-level or row-level
  - `subject_marks` lacks `exam_year_id` FK (only loose `year` integer)
  - No **state machine tracking** — current lifecycle state not queryable
  - Missing **rejection/resubmission workflow**

---

### A.4 CURRENT WORKFLOWS

#### A.4.1 Upload & Import Flow

```
User selects: Year → Region → District → School → Subject
    ↓
Download CSV template (candidates for selected school+subject)
    ↓
Fill marks (Paper 1, Paper 2, Paper 3, Practical, Project)
    ↓
Upload CSV → Server-side validation
    ├─ Candidate index validation
    ├─ Mark range validation (0-100)
    ├─ Paper structure validation
    ├─ Completeness checks
    ↓
If errors: Display error report, allow download of error CSV
    ↓
If valid: Create MarkImportBatch (status=draft)
    ├─ Store RawMark records (is_locked=false)
    ├─ Run SubjectMarks processor
    ↓
Lock batch → status changes to 'locked'
    ├─ Set is_locked=true on all RawMark rows
    ├─ Generate scoresheet PDFs
```

**Assessment**: 
- Flow works end-to-end but lacks **explicit validation phase**
- No **moderation/review gate** before final commit
- **Paper-specific workflows** not supported (e.g., "Grade Paper 1 before Paper 2")
- **Rollback capabilities** not implemented

---

### A.5 ROLE & PERMISSION STRUCTURE

**Defined Roles** (`app/Models/Role.php`):
```
admin
regional_officer
district_data_entry_officer
district_supervisor
school_registrar
```

**Current Implementation**:
- Simple role check: `user->hasRole('admin')`
- No granular permission matrix for mark entry operations
- All authenticated users can access mark entry (no role filtering in routes)

**Assessment**: 
- ❌ No **role-based access control (RBAC)** for lifecycle operations
- Missing **operation-level permissions**:
  - Who can upload marks?
  - Who can validate?
  - Who can moderate?
  - Who can lock/reject?
  - Who can unlock (admin only)?

---

### A.6 AUDIT & LOGGING

**Existing Audit Mechanisms**:
1. **AuditLog** table: Generic module/action logging (basic)
2. **RawMark columns**: `locked_by`, `locked_at` (who locked and when)
3. **MarkImportBatch** columns: `imported_by`, validated_by`, `locked_by`, etc.

**Assessment**:
- ✅ Basic audit trail exists
- ❌ **No moderation audit log** — reviews/rejections not tracked
- ❌ **No version history** — changes to marks during validation not captured
- ❌ **No rejection reasons** — no way to record why marks were rejected
- ❌ **No signature/approval log** — no formal sign-off mechanism

---

### A.7 FRONTEND ARCHITECTURE

**Current View** (`resources/views/mark-entry/index.blade.php` — 661+ lines):

**Alpine.js Manager**: `markEntryManager()` — monolithic state management
- 30+ reactive properties
- Handles: filtering, file upload, preview, import progress, exports
- No modular component separation
- File upload logic mixed with filtering logic

**Assessment**:
- ✅ Responsive UI with hierarchical filtering
- ❌ **Single monolithic component** — difficult to extend
- ❌ **No workflow visualization** — users don't see where marks are in lifecycle
- ❌ **No draft/final workflow UI** — no toggle between draft and submission modes
- ❌ **No moderation interface** — no review/approve/reject UI

---

## SECTION B: ARCHITECTURAL GAPS

### B.1 MISSING EXAMINATION LIFECYCLE PHASES

The current system implements **only Entry & Locking**. It lacks:

| Phase | Current State | Required Implementation |
|-------|---------------|------------------------|
| **Entry** | ✅ Fully implemented | CSV/ZIP upload, template download |
| **Validation** | ⚠️ Basic checks only | Systematic error reporting, rule engine |
| **Moderation** | ❌ Missing | Review gates, approval workflow |
| **Processing** | ⚠️ Implicit | Grade calculation, ranking, aggregate generation |
| **Reporting** | ⚠️ PDF exports only | Multiple report formats, drill-down analytics |
| **Submission** | ⚠️ Via locking | Formal submission, state machine tracking |
| **Audit Trail** | ⚠️ Partial | Complete operation logging, change tracking |

### B.2 PAPER-LEVEL WORKFLOW GAPS

ACSEE subjects can have:
- Multiple written papers (Paper 1, 2, 3)
- Practical components
- Project work

**Current limitation**: No paper-by-paper workflow. All papers must be uploaded together.

**Missing capabilities**:
- Grade Paper 1 → Review → Unlock to add Paper 2
- Separate validation per paper
- Paper-specific moderation gates
- Partial submission tracking

### B.3 MISSING STATE MACHINE

No formalized state transitions. Currently:
- Mark upload → Auto-validation → Lock available (choice point)
- No explicit "draft approved" or "validation failed" states
- No "awaiting moderation" state
- No "rejected, awaiting resubmission" state

### B.4 MISSING INTERFACES

No UI/route implementation for:
- ✅ **Moderation Dashboard**: Review uploaded marks, flag issues
- ✅ **Batch Approval/Rejection**: Approve or return batches with feedback
- ✅ **Change Log Viewer**: See all modifications to a batch
- ✅ **Lifecycle Status Dashboard**: Monitor all marks through phases
- ✅ **Paper-Level Progress**: Track which papers are complete/incomplete

### B.5 MISSING PERMISSION LAYERS

Currently all authenticated users have same access. Needed:
- `mark-entry.upload` — Create new batches
- `mark-entry.validate` — Run validation rules
- `mark-entry.review` — See validation reports
- `mark-entry.moderate` — Approve/reject batches
- `mark-entry.lock` — Finalize marks
- `mark-entry.unlock` — Revert to draft (admin only)
- `mark-entry.export` — Generate reports

---

## SECTION C: PROPOSED LIFECYCLE STRUCTURE

### C.1 SEVEN-PHASE EXAMINATION LIFECYCLE

```
┌─────────────────────────────────────────────────────────────────────┐
│                   EXAMINATION MARK LIFECYCLE                         │
└─────────────────────────────────────────────────────────────────────┘

1. ENTRY (User: Teacher/School Registrar)
   └─ Upload marks via CSV/ZIP
   └─ Store in staging table (raw_marks)
   └─ Status: draft

2. VALIDATION (User: System/Automated)
   └─ Run validation rules (ranges, completeness, uniqueness)
   └─ Generate error report
   └─ Status: validated | validation_failed

3. MODERATION (User: HOD/District Supervisor)
   └─ Review uploaded marks
   └─ Flag outliers/anomalies
   └─ Approve or reject with feedback
   └─ Status: awaiting_moderation → approved | rejected

4. PROCESSING (User: System/Admin)
   └─ Calculate grades from marks
   └─ Generate rank/position
   └─ Aggregate results
   └─ Status: processing → processed

5. REPORTING (User: Admin/HOD)
   └─ Generate scoresheet PDFs
   └─ Export CSV/Excel reports
   └─ Publish hierarchy views
   └─ Status: reporting

6. SUBMISSION (User: School/District/Region)
   └─ Formally submit marks to exam authority
   └─ Lock from further modification
   └─ Status: submitted

7. AUDIT TRAIL (User: Admin/Auditor)
   └─ View all changes and approvals
   └─ Generate compliance reports
   └─ Status: auditable

┌──────────────────────────────────────────────────────────────────────┐
│                        STATE MACHINE                                  │
└──────────────────────────────────────────────────────────────────────┘

[Draft] ──upload──→ [Validating] ──validate──→ [Validated]
         (retry)        ↓                           ↓
                  [Validation Failed] ←────────┐  [Awaiting Moderation]
                        ↓                      │    ↓
                    (fix & reupload)           │  [Approved] ←── (rejected)
                        ↓                      │    ↓         [Rejected]
                    [Draft]                    │  [Processing]   ↓
                                               │    ↓        [Modifying]
                                               └─ (modify marks)
                                                    ↓
                                              [Submitted (Locked)]
                                                    ↓
                                              [Archived (Immutable)]
```

### C.2 LIFECYCLE STATE DEFINITIONS

**Draft**
- Initial state after upload
- Marks stored in staging, not committed
- Can delete or reupload

**Validating**
- Validation rules executing
- User cannot modify
- Transient state

**Validated**
- All validation rules passed
- Ready for moderation
- Changes allowed (marks can be edited)

**Validation Failed**
- One or more validation rules failed
- Error report generated
- User must fix and reupload

**Awaiting Moderation**
- Validation passed, awaiting HOD/Supervisor review
- Marks frozen for review
- Moderator can flag issues

**Approved**
- Moderator signed off
- Ready for processing
- No mark changes allowed (locked for modification)

**Rejected**
- Moderator identified issues
- Return to draft with feedback
- User must correct and resubmit

**Processing**
- System calculating grades/rankings
- Read-only state

**Processed**
- Grades calculated, aggregates ready
- Available for reporting

**Submitted**
- Formally submitted to exam authority
- Fully locked (no unlock without admin)

**Archived**
- Historical record
- Immutable for compliance

---

### C.3 ROLE-BASED OPERATION MATRIX

| Operation | Teacher | HOD | District Supervisor | Admin | Auditor |
|-----------|---------|-----|-------------------|-------|---------|
| **Upload marks** | ✅ Draft | ✅ Draft | — | ✅ Draft | — |
| **View validation errors** | ✅ Own | ✅ School | ✅ District | ✅ All | ✅ All |
| **Fix & reupload** | ✅ Own | ✅ School | — | ✅ Any | — |
| **Review (Moderation)** | — | ✅ School | ✅ District | ✅ All | — |
| **Approve/Reject** | — | ✅ School | ✅ District | ✅ All | — |
| **View approved marks** | ✅ Own | ✅ School | ✅ District | ✅ All | ✅ All |
| **Request changes** | — | ✅ School | ✅ District | ✅ All | — |
| **Lock (submit)** | — | ✅ School | ✅ District | ✅ All | — |
| **Unlock** | — | — | — | ✅ Only | — |
| **View audit log** | — | — | — | ✅ All | ✅ All |
| **Export reports** | ✅ Own | ✅ School | ✅ District | ✅ All | ✅ All |

---

## SECTION D: SIDEBAR MENU DESIGN

### D.1 HIERARCHICAL SIDEBAR STRUCTURE FOR ACSEE

```
╔════════════════════════════════════════════╗
║         ACSEE MARK MANAGEMENT              ║  (Branded header)
╠════════════════════════════════════════════╣
║                                            ║
║  📊 ENTRY & VALIDATION                     ║  (Group 1: Ingestion)
║  ├─ 📤 Upload Marks                        ║
║  │   ├─ Single Subject CSV                 ║  (Drilldown: Import method)
║  │   ├─ School Bulk ZIP                    ║
║  │   └─ District Bulk ZIP                  ║
║  ├─ 📋 Download Template                   ║
║  │   ├─ By Subject & School                ║
║  │   └─ Bulk (School/District)             ║
║  ├─ ✅ View Upload Status                  ║
║  │   ├─ My Batches (Draft)                 ║  (Filtered by role)
║  │   ├─ School Batches                     ║
║  │   └─ District Batches                   ║
║  └─ ⚠️ Validation Reports                  ║
║     ├─ Error Batches                       ║
║     ├─ Download Error CSV                  ║
║     └─ Batch Error Details                 ║
║                                            ║
║  🔍 MODERATION & REVIEW                    ║  (Group 2: Quality Control)
║  ├─ 👁️ Review Dashboard                    ║
║  │   ├─ Awaiting My Review                 ║  (For HOD/Supervisor)
║  │   ├─ All School Marks (HOD)             ║
║  │   └─ All District Marks (Supervisor)    ║
║  ├─ 🚩 Flag Issues                         ║
║  │   ├─ Outlier Candidates                 ║
║  │   ├─ Missing Data                       ║
║  │   └─ Data Quality Checks                ║
║  ├─ ✔️ Approve Batch                       ║
║  ├─ ❌ Reject Batch                        ║
║  └─ 💬 Feedback Log                        ║
║     └─ View Rejection Reasons              ║
║                                            ║
║  🔒 SUBMISSION & LOCKING                   ║  (Group 3: Finalization)
║  ├─ 📌 Lock Approved Batches               ║
║  │   ├─ Lock Single Batch                  ║
║  │   └─ Bulk Lock (School/District)        ║
║  ├─ 🔓 Unlock Batches (Admin)              ║  (Admin only)
║  │   ├─ By Batch ID                        ║
║  │   └─ View Unlock History                ║
║  ├─ 📤 Submit to Authority                 ║
║  │   ├─ Generate Submission Package        ║
║  │   └─ Submission History                 ║
║  └─ 📜 Lock Status Dashboard               ║
║     ├─ Locked vs Unlocked                  ║
║     └─ Submission Status                   ║
║                                            ║
║  📑 REPORTS & EXPORTS                      ║  (Group 4: Output)
║  ├─ 📋 Scoresheet PDFs                     ║
║  │   ├─ Single Subject                     ║
║  │   ├─ School Bulk Export                 ║
║  │   └─ District Bulk Export               ║
║  ├─ 📊 CSV/Excel Exports                   ║
║  │   ├─ School Marks Export                ║
║  │   ├─ District Marks Export              ║
║  │   └─ Regional Summary Export            ║
║  ├─ 🎯 Analysis Reports                    ║
║  │   ├─ Daily Marks Entry Report           ║
║  │   ├─ Subject Performance                ║
║  │   └─ Candidate Extremity Analysis       ║
║  └─ 📈 Publication Reports                 ║
║     ├─ Public Results Portal               ║
║     └─ Performance Hierarchy               ║
║                                            ║
║  🕐 MONITORING & AUDIT                     ║  (Group 5: Governance)
║  ├─ ⏱️ Lifecycle Dashboard                 ║
║  │   ├─ Processing Status by Phase         ║
║  │   ├─ Completion %                       ║
║  │   └─ Bottleneck Analysis                ║
║  ├─ 📝 Change Log                          ║
║  │   ├─ Batch History                      ║
║  │   ├─ Mark Modifications                 ║
║  │   └─ Approval Trail                     ║
║  ├─ 👤 User Activity Log                   ║
║  │   ├─ Who Did What                       ║
║  │   ├─ When & From Where                  ║
║  │   └─ Filter by User/Date                ║
║  ├─ 🔐 Audit Trail                         ║
║  │   ├─ Compliance View                    ║
║  │   ├─ Signature Log                      ║
║  │   └─ Approval Workflow                  ║
║  └─ 📋 System Logs                         ║
║     ├─ Error Logs                          ║
║     └─ Processing Logs                     ║
║                                            ║
║  ⚙️ SETTINGS & ADMINISTRATION               ║  (Group 6: Configuration)
║  ├─ 🎓 ACSEE Configuration                 ║
║  │   ├─ Subject List & Papers              ║
║  │   ├─ Paper Structure                    ║
║  │   ├─ Validation Rules                   ║
║  │   └─ Grade Scale                        ║
║  ├─ 🔐 Access Control                      ║
║  │   ├─ Role Permissions                   ║
║  │   ├─ User Assignment                    ║
║  │   └─ Scope Hierarchy                    ║
║  ├─ 🗂️ Batch Management                    ║
║  │   ├─ Delete Batches                     ║
║  │   ├─ Archive Batches                    ║
║  │   └─ Batch Retention Policy             ║
║  └─ 📊 Reporting Preferences               ║
║     ├─ Report Templates                    ║
║     └─ Export Formats                      ║
║                                            ║
╚════════════════════════════════════════════╝
```

### D.2 UX REASONING FOR MENU STRUCTURE

#### **Group 1: Entry & Validation** (Upload + Immediate QA)
**Why this structure?**
- Teachers/Registrars need quick access to upload
- Grouped with validation to emphasize validation is **first quality gate**
- Separation of import methods (single/bulk) reduces cognitive load
- Error batches highlighted for quick remediation

**User Journey**: Upload → Check Status → View Errors → Reupload → Continue

---

#### **Group 2: Moderation & Review** (HOD/Supervisor Gate)
**Why separate from Entry?**
- Moderation is **role-based** (HOD for school, Supervisor for district)
- Requires different UI (batch review, flagging interface)
- Approval/Rejection creates explicit **decision points**
- Feedback tracking prevents lost context

**User Journey**: Review Dashboard → Review Batch → Flag Issues → Approve/Reject → Track Feedback

---

#### **Group 3: Submission & Locking** (Finalization)
**Why group with locking?**
- Lock = formal submission commitment
- Must follow moderation approval
- Unlock dangerous operation (admin only)
- Forms submission package for exam authority

**User Journey**: Approve → Lock → Generate Submission → Monitor Locked Status

---

#### **Group 4: Reports & Exports** (Output Products)
**Why separate group?**
- Output generation is **distinct workflow**
- Multiple format needs (PDF, CSV, Excel, Portal)
- Can only run on **finalized** (locked) marks
- Follows natural progression of lifecycle

**User Journey**: After Lock → Generate Scoresheet → Export → Publish

---

#### **Group 5: Monitoring & Audit** (Oversight)
**Why separate?**
- Audit users (different permission model)
- View-only operations (cannot modify)
- Compliance-focused reporting
- Traces decision-making trail

**User Journey**: Admin monitors progress → Reviews audit trail → Generates compliance report

---

#### **Group 6: Settings & Administration** (Configuration)
**Why last?**
- Infrequent access
- Requires admin role
- Foundational (set once, modify rarely)
- Collapsible to reduce visual clutter

---

### D.3 PAPER-LEVEL SIDEBAR VARIANTS

For **subjects with multiple papers** (e.g., Paper 1, 2, 3 + Practical), expand the sidebar:

```
📊 ENTRY & VALIDATION
├─ 📤 Upload Marks
│  ├─ Paper 1
│  ├─ Paper 2
│  ├─ Paper 3
│  ├─ Practical Component
│  └─ Project Work
├─ ✅ View Upload Status
│  ├─ Paper 1 Status
│  ├─ Paper 2 Status
│  ├─ Paper 3 Status
│  ├─ Practical Status
│  └─ Project Status
```

**Enables**:
- Upload papers progressively (Paper 1 → Review → Paper 2)
- Separate validation per paper
- Paper-specific error reporting
- Concurrent workflows (different teachers for different papers)

---

### D.4 SCALABILITY TO CSEE & INTERNAL EXAMS

**CSEE (Certificate of Secondary Education)**:
- Similar structure to ACSEE
- Typically 4 or 5 subjects (vs. ACSEE's 3-5)
- Sidebar grows to accommodate more subjects

```
Prefix ACSEE/CSEE/INTERNAL to group sections:
├─ 🎓 ACSEE Mark Management
├─ 🎓 CSEE Mark Management
└─ 🎓 Internal Exams Management
```

**INTERNAL EXAMS**:
- Per-school configurations
- Multiple terms/semesters
- Class-level (Form 1-6) rather than subject-level
- Tree structure:

```
📊 INTERNAL EXAM MANAGEMENT
├─ 📅 Term 1 (2026)
│  ├─ 📚 Form 1
│  │  ├─ English
│  │  ├─ Mathematics
│  │  └─ ...
│  ├─ 📚 Form 2
│  └─ ...
├─ 📅 Term 2 (2026)
└─ 📅 Term 3 (2026)
```

---

## SECTION E: TECHNICAL IMPLEMENTATION BLUEPRINT

### E.1 ROUTE GROUPING ARCHITECTURE

```php
// routes/mark-entry.php (NEW FILE)

Route::middleware(['auth'])->prefix('mark-entry')->group(function () {

    // ============ ACSEE MARK ENTRY ============
    Route::prefix('acsee')->group(function () {

        // GROUP 1: ENTRY & VALIDATION
        Route::prefix('entry-validation')->group(function () {
            Route::post('upload', [MarkEntryUploadController::class, 'upload']);
            Route::get('download-template', [MarkEntryUploadController::class, 'downloadTemplate']);
            Route::get('upload-status', [MarkEntryUploadController::class, 'uploadStatus']);
            Route::get('validation-report/{batchId}', [MarkEntryUploadController::class, 'validationReport']);
            Route::get('error-batches', [MarkEntryUploadController::class, 'errorBatches']);
        });

        // GROUP 2: MODERATION & REVIEW
        Route::middleware('can:mark-entry.moderate')->prefix('moderation')->group(function () {
            Route::get('dashboard', [MarkEntryModerationController::class, 'dashboard']);
            Route::get('batch/{batchId}', [MarkEntryModerationController::class, 'reviewBatch']);
            Route::post('batch/{batchId}/approve', [MarkEntryModerationController::class, 'approveBatch']);
            Route::post('batch/{batchId}/reject', [MarkEntryModerationController::class, 'rejectBatch']);
            Route::get('batch/{batchId}/feedback', [MarkEntryModerationController::class, 'feedbackLog']);
            Route::post('batch/{batchId}/flag-issue', [MarkEntryModerationController::class, 'flagIssue']);
        });

        // GROUP 3: SUBMISSION & LOCKING
        Route::middleware('can:mark-entry.lock')->prefix('submission')->group(function () {
            Route::post('lock/{batchId}', [MarkEntrySubmissionController::class, 'lockBatch']);
            Route::get('lock-status', [MarkEntrySubmissionController::class, 'lockStatus']);
            Route::post('submit/{batchId}', [MarkEntrySubmissionController::class, 'submitBatch']);
            Route::get('submission-history', [MarkEntrySubmissionController::class, 'submissionHistory']);
            
            // Admin only
            Route::middleware('can:mark-entry.unlock')
                ->post('unlock/{batchId}', [MarkEntrySubmissionController::class, 'unlockBatch']);
        });

        // GROUP 4: REPORTS & EXPORTS
        Route::prefix('reports')->group(function () {
            Route::get('scoresheet/{batchId}', [MarkEntryReportController::class, 'scoresheet']);
            Route::get('scoresheet-bulk', [MarkEntryReportController::class, 'scoresheetBulk']);
            Route::get('csv-export', [MarkEntryReportController::class, 'csvExport']);
            Route::get('daily-entry-report', [MarkEntryReportController::class, 'dailyEntryReport']);
            Route::get('extremity-analysis', [MarkEntryReportController::class, 'extremityAnalysis']);
        });

        // GROUP 5: MONITORING & AUDIT
        Route::middleware('can:mark-entry.audit')->prefix('monitoring')->group(function () {
            Route::get('lifecycle-dashboard', [MarkEntryMonitoringController::class, 'lifecycleDashboard']);
            Route::get('batch-history/{batchId}', [MarkEntryMonitoringController::class, 'batchHistory']);
            Route::get('change-log', [MarkEntryMonitoringController::class, 'changeLog']);
            Route::get('user-activity', [MarkEntryMonitoringController::class, 'userActivity']);
            Route::get('audit-trail', [MarkEntryMonitoringController::class, 'auditTrail']);
        });

        // GROUP 6: ADMINISTRATION
        Route::middleware('can:mark-entry.admin')->prefix('admin')->group(function () {
            Route::get('configuration', [MarkEntryAdminController::class, 'configuration']);
            Route::put('configuration', [MarkEntryAdminController::class, 'updateConfiguration']);
            Route::post('delete-batch/{batchId}', [MarkEntryAdminController::class, 'deleteBatch']);
            Route::get('access-control', [MarkEntryAdminController::class, 'accessControl']);
        });

        // SHARED API ENDPOINTS (Used by all groups)
        Route::prefix('api')->group(function () {
            Route::get('regions', [MarkEntryApiController::class, 'regions']);
            Route::get('districts', [MarkEntryApiController::class, 'districts']);
            Route::get('schools', [MarkEntryApiController::class, 'schools']);
            Route::get('subjects', [MarkEntryApiController::class, 'subjects']);
            Route::get('exam-years', [MarkEntryApiController::class, 'examYears']);
        });
    });

    // CSEE (Similar structure)
    Route::prefix('csee')->group(function () {
        // Same 6 groups, different controller namespace
    });

    // INTERNAL EXAMS (Different structure)
    Route::prefix('internal')->group(function () {
        // Different lifecycle per exam type
    });
});
```

### E.2 CONTROLLER RESPONSIBILITY STRUCTURE

**Create New Controllers** (Instead of monolithic MarkEntryController):

```
app/Http/Controllers/MarkEntry/
├── Entry/
│   ├── MarkEntryUploadController.php      (Handle upload, template download)
│   └── MarkEntryApiController.php          (Filtering endpoints)
├── Validation/
│   └── MarkValidationController.php        (Validation rules, error reports)
├── Moderation/
│   ├── MarkEntryModerationController.php  (Review dashboard, approve/reject)
│   └── MarkFlagController.php              (Flag issues, outliers)
├── Processing/
│   ├── MarkProcessingController.php       (Grade calculation, processing status)
│   └── MarkGradeController.php             (Grade computation)
├── Submission/
│   ├── MarkEntrySubmissionController.php  (Lock, submit, unlock)
│   └── MarkLockController.php              (Lock/unlock operations)
├── Reporting/
│   ├── MarkEntryReportController.php      (PDF, CSV exports)
│   ├── MarkScoresheetController.php       (Scoresheet generation)
│   └── MarkAnalyticsController.php        (Daily entry report, extremity analysis)
├── Audit/
│   ├── MarkEntryMonitoringController.php  (Lifecycle dashboard, audit trail)
│   ├── MarkChangeLogController.php        (Change tracking)
│   └── MarkAuditController.php            (Compliance, audit reports)
└── Admin/
    └── MarkEntryAdminController.php        (Configuration, access control)
```

**Each Controller: 2-3 actions maximum**
- Single responsibility
- Focused on one lifecycle phase
- Easier to test and maintain

---

### E.3 DATABASE ENHANCEMENTS

#### **New Tables**

**1. mark_entry_lifecycle_states**
```sql
CREATE TABLE mark_entry_lifecycle_states (
    id PRIMARY KEY,
    mark_import_batch_id FOREIGN KEY (mark_import_batches),
    current_state ENUM('draft', 'validating', 'validated', 'validation_failed', 
                       'awaiting_moderation', 'approved', 'rejected', 'processing', 
                       'processed', 'submitted', 'archived'),
    previous_state VARCHAR(50),
    transitioned_by USER FK,
    transitioned_at TIMESTAMP,
    transition_reason TEXT,
    created_at, updated_at
);

INDEX: batch_id, current_state, transitioned_at
```

**2. mark_moderation_reviews**
```sql
CREATE TABLE mark_moderation_reviews (
    id PRIMARY KEY,
    mark_import_batch_id FOREIGN KEY,
    reviewer_id FOREIGN KEY (users),
    review_type ENUM('school_hod', 'district_supervisor', 'admin'),
    status ENUM('pending', 'approved', 'rejected', 'conditional'),
    feedback TEXT,
    flagged_issues JSON, -- Array of {field, severity, description}
    reviewed_at TIMESTAMP,
    created_at, updated_at
);

INDEX: batch_id, reviewer_id, status
```

**3. mark_entry_changes**
```sql
CREATE TABLE mark_entry_changes (
    id PRIMARY KEY,
    raw_mark_id FOREIGN KEY (raw_marks),
    changed_by FOREIGN KEY (users),
    change_type ENUM('upload', 'edit', 'validation_fix', 'admin_correction'),
    field_name VARCHAR(100),
    old_value DECIMAL(6,2),
    new_value DECIMAL(6,2),
    reason TEXT,
    changed_at TIMESTAMP
);

INDEX: raw_mark_id, changed_by, changed_at
```

**4. mark_batch_approvals**
```sql
CREATE TABLE mark_batch_approvals (
    id PRIMARY KEY,
    mark_import_batch_id FOREIGN KEY,
    approval_level ENUM('validation', 'moderation', 'submission'),
    approved_by FOREIGN KEY (users),
    approved_at TIMESTAMP,
    signature VARCHAR(500),  -- Digital signature
    created_at
);

INDEX: batch_id, approval_level
```

#### **Migrations to Modify Existing Tables**

**raw_marks**:
```php
// Add missing columns
$table->string('paper_identifier')->nullable()    // 'paper_1', 'practical', etc.
$table->enum('entry_status', [...])->default('draft')  // Explicit status
$table->text('validation_notes')->nullable()      // Why validation passed/failed
$table->timestamp('moderation_started_at')->nullable()
$table->timestamp('moderation_completed_at')->nullable()
```

**mark_import_batches**:
```php
// Add state machine support
$table->enum('lifecycle_state', [...])->default('draft')
$table->json('lifecycle_history')->nullable()     // Audit trail
$table->text('rejection_reason')->nullable()      // Why rejected
$table->boolean('requires_resubmission')->default(false)
$table->foreignId('resubmitted_from_batch_id')->nullable()  // Chain resubmissions
```

---

### E.4 SERVICE LAYER RESTRUCTURING

**Current**: 11 services, monolithic
**Proposed**: Organized by lifecycle phase

```
app/Services/MarkEntry/
├── Entry/
│   ├── MarkUploadService.php               (CSV/ZIP parsing)
│   ├── TemplateGenerationService.php       (Template creation)
│   └── MarkValidationService.php           (Validation rules)
├── Moderation/
│   ├── MarkModerationService.php           (Review workflow)
│   ├── IssueFlaggerService.php             (Flag outliers)
│   └── ApprovalWorkflowService.php         (Approve/Reject)
├── Processing/
│   ├── GradeCalculationService.php         (Grade from marks)
│   ├── RankingService.php                  (Position calculation)
│   └── AggregateService.php                (Summary aggregates)
├── Submission/
│   ├── LockingService.php                  (Lock/unlock operations)
│   └── SubmissionPackageService.php        (Generate submission file)
├── Reporting/
│   ├── ScoresheetService.php               (PDF generation)
│   ├── CsvExportService.php                (CSV export)
│   └── ReportingService.php                (Various reports)
├── Audit/
│   ├── AuditTrailService.php               (Change logging)
│   ├── ChangeTrackingService.php           (Diff tracking)
│   └── ComplianceService.php               (Audit reports)
└── Shared/
    ├── LifecycleStateService.php           (State machine)
    ├── PermissionService.php               (RBAC for operations)
    └── ExamYearService.php                 (Year validation)
```

---

### E.5 PERMISSION STRUCTURE (Laravel Policies)

**Create Permission Model**:

```php
// app/Models/Permission.php
class Permission extends Model {
    const MARK_ENTRY_UPLOAD = 'mark-entry.upload';
    const MARK_ENTRY_VALIDATE = 'mark-entry.validate';
    const MARK_ENTRY_MODERATE = 'mark-entry.moderate';
    const MARK_ENTRY_APPROVE = 'mark-entry.approve';
    const MARK_ENTRY_LOCK = 'mark-entry.lock';
    const MARK_ENTRY_UNLOCK = 'mark-entry.unlock';
    const MARK_ENTRY_EXPORT = 'mark-entry.export';
    const MARK_ENTRY_AUDIT = 'mark-entry.audit';
}
```

**Create Policy** (`app/Policies/MarkImportBatchPolicy.php`):

```php
class MarkImportBatchPolicy {
    public function upload(User $user): bool {
        return $user->can('mark-entry.upload');
    }

    public function moderate(User $user, MarkImportBatch $batch): bool {
        // HOD can moderate school batches; Supervisor can moderate district batches
        if ($batch->school_id && $user->isHodFor($batch->school)) return true;
        if ($batch->district_id && $user->isSupervisorFor($batch->district)) return true;
        return $user->isAdmin();
    }

    public function approve(User $user, MarkImportBatch $batch): bool {
        return $this->moderate($user, $batch) && $batch->lifecycle_state === 'awaiting_moderation';
    }

    public function lock(User $user, MarkImportBatch $batch): bool {
        return $batch->lifecycle_state === 'approved' && 
               ($this->moderate($user, $batch) || $user->isAdmin());
    }

    public function unlock(User $user): bool {
        return $user->isAdmin();
    }
}
```

**Attach to Routes**:

```php
Route::post('upload', [...])
    ->middleware('can:mark-entry.upload');

Route::post('batch/{batch}/approve', [ModerationController::class, 'approve'])
    ->middleware('can:moderate,batch');

Route::post('batch/{batch}/lock', [SubmissionController::class, 'lock'])
    ->middleware('can:lock,batch');
```

---

### E.6 MODERATION WORKFLOW DESIGN

**Moderation Interface Flow**:

```
1. MODERATION DASHBOARD (List all awaiting batches)
   ├─ Filters: School, Subject, Status
   ├─ Display: Batch Code | Subject | School | Uploaded Date | Status
   └─ Action: Click to "Review Batch"

2. BATCH REVIEW PAGE (Detailed view + review tools)
   ├─ Header: Batch Info
   │  ├─ Subject, School, Exam Year, Upload Date
   │  ├─ Uploader Name, Status
   │  └─ Total Candidates, Valid Records
   │
   ├─ Scoresheet Preview
   │  ├─ Table: Index | Name | Paper 1 | Paper 2 | Paper 3 | Practical
   │  ├─ Sorting: By marks, by name, by index
   │  └─ Search: By candidate name/index
   │
   ├─ Outlier Detection Panel
   │  ├─ High marks flagged (outliers)
   │  ├─ Low marks flagged (check accuracy)
   │  ├─ Missing papers
   │  └─ Manual flag button per row
   │
   ├─ Validation Report
   │  ├─ Re-run validation if marks edited
   │  └─ Show any remaining validation errors
   │
   ├─ Feedback Notes
   │  ├─ Rich text editor
   │  ├─ Can specify which candidates need correction
   │  └─ Saved to moderation review table
   │
   └─ Action Buttons
      ├─ ✅ APPROVE (mark lifecycle_state = 'approved')
      ├─ ❌ REJECT (lifecycle_state = 'rejected', send feedback)
      ├─ ⏸️ HOLD (awaiting further data, keep in review)
      └─ 🔧 EDIT (unlock specific rows for corrction)

3. POST-ACTION
   ├─ If approved: Send to Teacher → "Marks Approved" notification
   ├─ If rejected: Send feedback + "Please resubmit" notification
   └─ If held: Email to teacher "Additional data needed"
```

---

### E.7 LOCKING & SUBMISSION CONTROL

**Lock Semantics**:

```
MARK_LEVEL:
  - is_locked on raw_marks row
  - Prevents edit/delete
  - Set when: Row has passed validation + moderation approved

BATCH_LEVEL:
  - lifecycle_state = 'submitted'
  - mark_import_batches.locked_at timestamp
  - locked_by user_id
  - Entire batch frozen
  - Set when: All rows locked + formal submission

YEAR_LEVEL:
  - exam_years.is_locked
  - Prevents new batch creation for that year
  - Managed by admin
  - Set when: All marks finalized nationally
```

**Unlock Process**:
```
1. User (must be admin) navigates to Admin → Unlock Batches
2. Search by Batch ID / Date Range / School
3. Select batch to unlock
4. MODAL: "Reason for unlock" (required free text)
5. MODAL: "Affected area" (which rows affected)
6. Confirm: "This will revert to draft state"
7. System:
   ├─ Set lifecycle_state back to 'awaiting_moderation'
   ├─ Clear locked_by, locked_at
   ├─ Clear approved_by, approved_at  
   ├─ Log unlock in AuditLog + mark_batch_approvals
   └─ Notify school: "Your marks have been unlocked for correction"
```

---

### E.8 AUDIT TRAIL ARCHITECTURE

**What to Track**:

1. **Batch Operations**
   - Upload (who, when, file size, hash)
   - Validation (pass/fail, error count)
   - Approval/Rejection (by whom, feedback)
   - Lock/Unlock (by whom, reason)

2. **Mark Modifications**
   - Original value → New value
   - Changed by (user ID)
   - Reason (validation fix, admin correction, moderation feedback)
   - Timestamp

3. **User Actions**
   - Login/Logout
   - Permission denials
   - Unusual activity (bulk unlock, delete batch)

4. **System Events**
   - Validation rule failures (count, types)
   - Processing errors
   - Report generation

**Implementation**:

```php
// Service: AuditTrailService
class AuditTrailService {
    public static function logBatchUploaded(MarkImportBatch $batch, User $user, string $fileName) {
        AuditLog::create([
            'module' => 'mark_entry',
            'action' => 'batch_uploaded',
            'exam_year_id' => $batch->exam_year_id,
            'user_id' => $user->id,
            'metadata' => [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'file_name' => $fileName,
                'file_size' => filesize(...),
                'file_hash' => hash_file('sha256', ...),
                'candidates_count' => $batch->total_records,
            ]
        ]);
    }

    public static function logMarkChanged(RawMark $mark, User $user, string $field, $oldValue, $newValue, string $reason) {
        MarkEntryChange::create([
            'raw_mark_id' => $mark->id,
            'changed_by' => $user->id,
            'change_type' => 'validation_fix',
            'field_name' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'changed_at' => now(),
        ]);
    }

    public static function logBatchApproved(MarkImportBatch $batch, User $user) {
        MarkBatchApproval::create([
            'mark_import_batch_id' => $batch->id,
            'approval_level' => 'moderation',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'signature' => $user->digital_signature ?? null,
        ]);

        MarkEntryLifecycleState::create([
            'mark_import_batch_id' => $batch->id,
            'current_state' => 'approved',
            'previous_state' => 'awaiting_moderation',
            'transitioned_by' => $user->id,
            'transitioned_at' => now(),
            'transition_reason' => 'Approved by moderator',
        ]);
    }
}
```

---

## SECTION F: SCALABILITY & ENTERPRISE CONSIDERATIONS

### F.1 FUTURE INTEGRATION POINTS

#### **A. Grading System Module**
```
Integration Point: MarkProcessingController
├─ After lock: Trigger grade calculation
├─ Use GradingProfile + GradingRuleService
├─ Store final grades in final_grades table
└─ Update subject_marks with grade
```

#### **B. Result Processing Module**
```
Integration Point: MarkProcessingController → ResultProcessingService
├─ After grades: Aggregate by candidate
├─ Calculate position/rank
├─ Generate transcripts
└─ Create result summaries
```

#### **C. Reports Module**
```
Integration Point: MarkEntryReportController
├─ Scoresheet PDFs (already integrated)
├─ CSV exports (already integrated)
├─ Add: Compliance reports
├─ Add: Performance analysis
└─ Add: Publication preparation
```

#### **D. Public Results Portal**
```
Integration Point: PublicResultsController (already exists)
├─ Query finalized subject_marks
├─ Filter by candidate/school/subject
├─ Display hierarchy
├─ No access to raw_marks or in-progress batches
```

---

### F.2 LOAD & SCALABILITY CONSIDERATIONS

**Estimated Scale** (National ACSEE):
- ~400,000 candidates
- ~130 subjects
- ~100 districts
- ~6,000+ schools
- Peak: 2-4 weeks during exam period

**Performance Bottlenecks & Solutions**:

| Bottleneck | Current Risk | Solution |
|-----------|--------------|----------|
| Batch upload (500MB ZIP) | Memory overflow | Stream-based ZIP processing, chunk uploads |
| Scoresheet PDF generation | Timeout (300s limit) | Queue-based PDF generation, caching |
| CSV export (400K records) | Out of memory | Streaming CSV writer, chunked export |
| Validation rules (regex checks) | O(n²) complexity | Optimized validation rules, parallelization |
| Moderation dashboard list | N+1 queries | Eager loading, database denormalization |
| Audit trail queries | Large table scans | Partition by date, indexing |

**Implementation Strategy**:

```php
// Queue-based PDF generation
Route::post('reports/scoresheet/{batch}', function(MarkImportBatch $batch) {
    GenerateScoresheetJob::dispatch($batch)
        ->onQueue('pdf-generation')
        ->withDelay(now()->addSeconds(5));
    return response()->json(['job_id' => $job->id]);
});

// Stream CSV export
Route::get('reports/csv-export', function() {
    return response()->streamDownload(function() {
        $this->csvExportService->stream();
    }, 'marks.csv');
});

// Chunk-based ZIP processing
protected function processZip(UploadedFile $zip) {
    $reader = new ZipStream($zip->getRealPath());
    foreach ($reader->files() as $file) {
        $this->processChunk($file);  // 100 rows at a time
    }
}
```

---

### F.3 MULTI-EXAM-TYPE ARCHITECTURE

**Currently**: Only ACSEE implemented

**To scale to CSEE + Internal Exams**:

```
Inheritance Model:

MarkEntryController (Abstract Base)
├── methods shared across all exam types
├── getHierarchyFilters()
├── getValidationRules()
└── getReportTemplates()

AcseeMarkEntryController extends MarkEntryController
├── ACSEE-specific paper structure (3 written + practical)
├── ACSEE-specific validation rules
└── ACSEE reporting formats

CseeMarkEntryController extends MarkEntryController
├── CSEE-specific paper structure (different papers)
├── CSEE-specific grade scales
└── CSEE reporting formats

InternalMarkEntryController extends MarkEntryController
├── Per-school configuration
├── Term/semester based
└── Class-level workflows
```

**Configuration-Driven**:

```php
// config/mark-entry.php
return [
    'acsee' => [
        'papers' => ['paper_1', 'paper_2', 'paper_3', 'practical'],
        'max_marks' => 100,
        'grade_scale' => 'NECTA_ACSEE',
        'allow_partial_entry' => false,
    ],
    'csee' => [
        'papers' => ['paper_1', 'paper_2'],
        'max_marks' => 150,
        'grade_scale' => 'NECTA_CSEE',
        'allow_partial_entry' => false,
    ],
    'internal' => [
        'papers' => [],  // Term-based, no papers
        'max_marks' => 'configurable',
        'grade_scale' => 'school_specific',
        'allow_partial_entry' => true,
    ],
];
```

---

### F.4 MULTI-REGION DEPLOYMENT

**Current**: Centralized system at 127.0.0.1:8000

**For National Scale**:

```
Architecture:
┌─────────────────────────────────────────────┐
│      Central Authority (National)           │
│  - Master exam configuration                │
│  - Permission templates                     │
│  - Reporting dashboards                     │
│  - Audit trail aggregation                  │
└────────────┬────────────────────────────────┘
             │
    ┌────────┼────────┐
    │        │        │
┌───▼─┐  ┌──▼──┐  ┌─▼────┐
│East │  │West │  │South │  Regional Instances
│Zone │  │Zone │  │Zone  │  - Entry processing
└────┘  └─────┘  └──────┘  - Moderation gates
    │        │        │      - Local reporting
    └────────┼────────┘
             │
        ┌────▼────┐
        │Districts│
        │Schools  │
        └─────────┘
```

**Implementation** (Optional Phase 2):
- API federation
- Batch sync across regions
- Distributed validation
- Central audit log aggregation
- Regional dashboards

---

### F.5 DISASTER RECOVERY & DATA INTEGRITY

**Risk Scenarios**:

| Scenario | Mitigation |
|----------|-----------|
| Upload in progress, server crashes | Transaction rollback, retry mechanism |
| Marks locked, need to correct | Unlock + moderation cycle with audit trail |
| Duplicate submissions | Batch code uniqueness, checksum verification |
| Corrupted ZIP file | File integrity check, re-upload |
| Moderator claims marks approved but weren't | Digital signature + audit log proof |

**Implementation**:

```php
// Transaction-based batch processing
DB::transaction(function() {
    $batch = MarkImportBatch::create([...]);
    
    foreach ($csvRows as $row) {
        RawMark::create([
            'mark_import_batch_id' => $batch->id,
            ...
        ]);
    }
    
    // Validate entire batch atomically
    if ($this->validateBatch($batch)) {
        $batch->update(['status' => 'validated']);
    } else {
        throw new ValidationException(...);  // Rollback all
    }
});

// Digital signatures for approvals
public function approveBatch(MarkImportBatch $batch, User $moderator) {
    $signature = hash('sha256', json_encode([
        'batch_id' => $batch->id,
        'moderator_id' => $moderator->id,
        'timestamp' => now()->toIso8601String(),
        'batch_hash' => $this->calculateBatchHash($batch),
    ]));

    MarkBatchApproval::create([
        'mark_import_batch_id' => $batch->id,
        'approved_by' => $moderator->id,
        'signature' => $signature,
    ]);
}
```

---

### F.6 COMPLIANCE & AUDIT READINESS

**NECTA Requirements**:
- ✅ Marks immutable after submission
- ✅ Change tracking with timestamps
- ✅ User accountability (who did what)
- ✅ Digital signatures for approvals (phase 2)
- ✅ Regular backups (already implemented in IRMS)
- ✅ Audit trail accessible to authority

**Compliance Checklist**:

```
□ Audit log captures all mark operations
□ Timestamps precise (database server time, not user-supplied)
□ IP address logging for access control audit
□ User agent logging for device tracking
□ Batch hash verification (prevent tampering)
□ Periodic compliance exports (monthly, annual)
□ Admin-only unlock capability with reason requirement
□ Read-only audit trail access
□ Export to secure format (PDF) with watermark
```

---

## SECTION G: IMPLEMENTATION ROADMAP

### Phase 1: Foundation (Weeks 1-2)
- ✅ Route restructuring
- ✅ Controller refactoring (split into 8 controllers)
- ✅ Database migrations (new tables + columns)
- ✅ State machine implementation (LifecycleStateService)
- ✅ Permission structure (Policies)

### Phase 2: Workflows (Weeks 3-4)
- ✅ Moderation interface (dashboard + review page)
- ✅ Approval/Rejection workflow
- ✅ Change tracking
- ✅ Feedback loop

### Phase 3: UI & UX (Weeks 5-6)
- ✅ Sidebar menu implementation
- ✅ Lifecycle status visualization
- ✅ Moderation forms
- ✅ Audit trail viewer

### Phase 4: Testing & Optimization (Weeks 7-8)
- ✅ Unit tests for services
- ✅ Integration tests for workflows
- ✅ Performance optimization (caching, indexing)
- ✅ Load testing (400K candidates scenario)

### Phase 5: Documentation & Deployment (Week 9)
- ✅ Technical documentation
- ✅ User guides
- ✅ Admin runbooks
- ✅ Go-live preparation

---

## CONCLUSION

The current Mark Entry system is **functionally complete** but **architecturally opaque**. By restructuring around the seven-phase examination lifecycle, you create:

1. **Clarity**: Each phase has explicit purpose
2. **Control**: Quality gates at each transition
3. **Compliance**: Complete audit trail
4. **Scalability**: Modular design supports future integrations
5. **Enterprise-readiness**: Professional workflows for national exam authority

The proposed sidebar menu reflects this lifecycle visually, making it obvious to users where marks are and what actions are available. The restructured routes, controllers, and database enhancements support this without breaking existing functionality.

**Next Step**: Proceed to SECTION H for detailed implementation code samples (services, controllers, migrations).

---

**Document Status**: APPROVED FOR IMPLEMENTATION  
**Version**: 1.0  
**Last Updated**: 2026-02-13
