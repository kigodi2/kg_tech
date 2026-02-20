# IRMS ADMIN PANEL ARCHITECTURE REPORT

**Date:** February 17, 2026  
**Project:** Integrated Result Management System (IRMS)  
**Purpose:** Full architecture audit to prepare for Admin Panel implementation  
**Repository:** `/home/prosmart-technologies/SOL/irms`

---

## 1. Technology Stack

| Component | Version / Detail |
|---|---|
| **Laravel** | 12.48.1 |
| **PHP** | 8.3.6 (requires ^8.2) |
| **Frontend** | Blade + Alpine.js (236 directives across views) |
| **Livewire** | Only present via Filament (not used directly) |
| **CSS Framework** | Tailwind CSS 4.0.0 |
| **Build Tool** | Vite 7.0.7 with `laravel-vite-plugin` |
| **Vue/React** | Not used |
| **Database** | SQLite |
| **Filament** | v3 (already installed, partially configured) |
| **Spatie Permission** | v6.24 declared in `composer.json` but **NOT used** — `HasRoles` trait is not applied to User model |

---

## 2. Authentication & Authorization

### 2.1 Authentication

- **Custom implementation** — `app/Http/Controllers/AuthController.php`
- No Breeze, Jetstream, Sanctum, Fortify, or Passport installed
- Session-based login with `Auth::attempt()`
- Governance audit logging on login/failure/suspension
- Password change enforcement via `EnforcePasswordChange` middleware
- Filament panel access gated via `canAccessPanel()` on the User model (admin only)

### 2.2 Authorization

- **Custom RBAC** via `roles` table + `user_scopes` table
- 5 role codes defined in `app/Models/Role.php`:
  - `admin`
  - `regional_officer`
  - `district_data_entry_officer`
  - `district_supervisor`
  - `school_registrar`

- **14 Policies** implemented:
  - `AdminAccessPolicy`
  - `BackupPolicy`
  - `BulkCsvExportPolicy`
  - `BulkImportPolicy`
  - `CandidateRegistrationPolicy`
  - `DistrictPolicy`
  - `ExamYearPolicy`
  - `HardenedRestorePolicy`
  - `MarkImportBatchPolicy`
  - `MarkImportPolicy`
  - `RegionPolicy`
  - `RestoreAuditLogPolicy`
  - `ResultsPolicy`
  - `SchoolPolicy`

- **5 Gates** defined in `app/Providers/AuthServiceProvider.php`:
  - `uploadMarkForDistrict`
  - `downloadBulkCsv`
  - `mark-entry.upload`
  - `mark-entry.moderate`
  - `mark-entry.lock`

- **4 Custom Middleware** in `app/Http/Middleware/`:
  - `AdminOnly`
  - `EnforcePasswordChange`
  - `LogAuthenticationEvents`
  - `SetExamYearContext`

- **48 inline auth checks** (`Auth::user()`, `auth()->user()`, `is_admin`) scattered across controllers

### 2.3 ⚠️ Critical Role System Conflict

Two competing role systems exist:

1. **Custom Role model** (`app/Models/Role.php`) — User `belongsTo` Role, checked via `$this->role->code`
2. **Spatie Permission** (`spatie/laravel-permission` v6.24) — in `composer.json` but **NOT wired**

Some controllers call `$user->hasRole()` (custom method) while others like `UnlockBatchController` call `$user->hasPermissionTo()` (Spatie-style). The latter **will fail at runtime** because the User model does not use Spatie's `HasRoles` trait.

**Resolution required before expanding the admin panel.**

---

## 3. Core Modules

### 3.1 Registration Module

| Sub-module | Controller(s) | Service(s) | Models | Tables | Type |
|---|---|---|---|---|---|
| **Candidates** | `CandidateController` (544L), `CandidateImportController` (307L), `DistrictCandidateImportController` (254L) | `CandidateImportService` (1,157L) | `Candidate`, `CandidateExamRegistration` | `candidates`, `candidate_exam_registrations` | Hybrid (CRUD + CSV import) |
| **Schools** | `SchoolController`, `SchoolImportController` (227L) | `SchoolImportService` (485L) | `School` | `schools` | Hybrid |
| **Districts** | `DistrictImportController` | `DistrictImportService` (468L) | `District`, `DistrictCouncil` | `districts`, `district_councils` | Hybrid |
| **Regions** | `RegionController` (402L) | — | `Region` | `regions` | CRUD |

### 3.2 Exam Types & Configuration

| Controller | Service | Models | Tables | Type |
|---|---|---|---|---|
| `ExamTypeController` (486L) | `ExamTypeService` | `ExamType`, `Subject`, `Combination` | `exam_types`, `subjects`, `combinations`, `combination_subject` | CRUD |
| `ExamYearController` (226L) | `ExamYearValidationService` (308L) | `ExamYear` | `exam_years` | CRUD |
| `Api/CombinationController` (385L) | — | `Combination` | `combinations`, `combination_subject` | CRUD |

### 3.3 ACSEE Allocation

| Controller | Service | Models | Tables | Type |
|---|---|---|---|---|
| `AcseeAllocationController` (265L) | `AcseeAllocationCSVImporter` (727L), `AcseeAllocationValidator`, `AcseeAllocationTemplateService` | `CandidateSubjectSelection` | `candidate_subject_selections` | Workflow |

### 3.4 Mark Entry (Most Complex Module)

| Controller | Service | Models | Type |
|---|---|---|---|
| `MarkEntryController` (1,342L) | `MarkImportService` (338L), `BulkImportOrchestrator` (443L), `DistrictBulkImportOrchestrator` (462L), `BulkCsvExportService` (435L) | `RawMark`, `SubjectMarks`, `MarkImportBatch`, `MarkImportChecksum`, `MarkEntryLifecycleState`, `MarkEntryChange`, `MarkBatchApproval`, `MarkModerationReview` | **Complex Workflow** |
| `MarkEntry/Api/MarkLifecycleApiController` (438L) | — | Lifecycle/moderation models | Workflow |
| `MarkEntry/Api/UnlockBatchController` | — | Batch models | Workflow |

**Tables involved:** `raw_marks`, `subject_marks`, `mark_import_batches`, `mark_import_checksums`, `mark_entry_lifecycle_states`, `mark_moderation_reviews`, `mark_entry_changes`, `mark_batch_approvals`

### 3.5 Results & Grading

| Controller | Service | Models | Type |
|---|---|---|---|
| `Results/AcseeResultsController` (281L) | `AcseeResultsService` (388L), `NectaGradingService` (438L) | `CandidateResult`, `FinalGrade`, `GradingProfile`, `GradingRule`, `ResultProcess` | Workflow |
| `Results/PublicAcseeResultsController` (238L) | — | — | Read-only |
| `PublicResultsController` (289L) | — | — | Read-only |
| `Grading/NectaGradingController` (279L) | `NectaGradingService` | Grading models | Workflow |

**Tables involved:** `candidate_results`, `final_grades`, `grading_profiles`, `grading_rules`, `result_processes`

### 3.6 Evaluations & Analytics

| Controller | Service | Models | Type |
|---|---|---|---|
| `Admin/CandidateExtremityController` | `CandidateCrossSubjectAnalysisService` (325L) | `CandidateExtremityAnalysis`, `CandidateSubjectOutlier` | Workflow |
| `DailyMarksEntryReportController` | — | — | Read-only |

**Tables involved:** `candidate_extremity_analysis`, `candidate_extremity_logs`, `candidate_subject_outliers`

### 3.7 Backup & Restore

| Controller | Service | Type |
|---|---|---|
| `BackupController` (366L) | `BackupService` (516L), `SQLiteBackupService` (520L) | Workflow |
| `BackupManagementController` | — | CRUD |
| `BackupRestoreController` | `RestoreService` (382L), `SQLiteRestoreService` (605L) | Workflow |
| `HardenedRestoreController` (503L) | `HardenedRestoreService` (614L) | Workflow |

**Tables involved:** `backups`, `backup_logs`, `restore_audit_logs`

### 3.8 Dashboard & Hierarchy

| Controller | Type |
|---|---|
| `DashboardController` | Read-only (aggregation views) |
| `HierarchyController` (296L) | Read-only (region → district → school drilldown) |

---

## 4. Database Structure Overview

**Total tables:** 52  
**Total migrations:** 72

### 4.1 Master Data Tables (Reference / Configuration)

| Table | Purpose | Key Relations |
|---|---|---|
| `regions` | Geographic regions | → districts, schools |
| `districts` | Geographic districts | → region, schools |
| `district_councils` | Administrative councils | → region, schools |
| `schools` | Examination centres | → council, district, region, candidates |
| `subjects` | ACSEE subjects (code, name, category) | → exam_type; pivot: combination_subject |
| `combinations` | Subject groupings (e.g., PCB, HGE) | → exam_type; M2M: subjects |
| `exam_types` | Exam categories (ACSEE, CSEE, etc.) | → subjects, combinations, registrations |
| `exam_years` | Year configurations with status | → registrations, marks |
| `class_levels` | Form levels | M2M: exam_types |
| `roles` | System roles (5 predefined codes) | → users |
| `grading_profiles` | Grading templates | → grading_rules |
| `grading_rules` | Grade boundaries (A, B, C, etc.) | → grading_profile |
| `system_settings` | Key-value config pairs | standalone |

### 4.2 Operational / Transactional Tables

| Table | Purpose | Key Foreign Keys |
|---|---|---|
| `candidates` | Student records | → school_id, combination |
| `candidate_exam_registrations` | Exam enrollment records | → candidate_id, exam_type_id, exam_year_id |
| `candidate_subject_selections` | Subject allocation per candidate | → candidate_id, subject_id |
| `raw_marks` | Imported raw mark data | → mark_import_batch_id, candidate_id, subject_id |
| `subject_marks` | Processed marks with paper breakdown | → candidate_id, subject_id, exam_type_id |
| `candidate_results` | Computed final results | → candidate_id |
| `final_grades` | Grade assignments | → candidate_id |
| `users` | System users | → role_id, school_id |
| `user_scopes` | Row-level access scoping | → user_id (polymorphic to region/district/school) |
| `bulk_imports` | General import tracking | → user_id |
| `bulk_import_files` | Imported file records | → bulk_import_id |
| `bulk_import_schools` | School-level import tracking | → bulk_import_id, school_id |

### 4.3 Workflow / Lifecycle Tables

| Table | Purpose | Status/Enum Fields |
|---|---|---|
| `mark_import_batches` | CSV import tracking | status: pending → processing → completed → failed |
| `mark_import_checksums` | Data integrity verification | — |
| `mark_entry_lifecycle_states` | Batch state transitions | state: uploaded → verified → moderated → locked |
| `mark_moderation_reviews` | Moderation workflow records | decision: approved / rejected / needs_revision |
| `mark_entry_changes` | Audit trail for mark edits | change_type: create / update / delete |
| `mark_batch_approvals` | Approval workflow | status: pending → approved → rejected |
| `result_processes` | Results computation runs | status: pending → running → completed → failed |

### 4.4 Audit Tables

| Table | Purpose |
|---|---|
| `governance_audit_logs` | Login events, admin actions, security events |
| `export_audit_logs` | Data export tracking |
| `restore_audit_logs` | Backup restore tracking |
| `backup_logs` | Backup operation logs |
| `exam_year_audit_logs` | Exam year changes |
| `candidate_extremity_analysis` | Statistical outlier analysis results |
| `candidate_extremity_logs` | Extremity computation logs |
| `candidate_subject_outliers` | Per-subject outlier flags |

---

## 5. CRUD vs Workflow Classification

### ✅ PURE CRUD (Ideal for Filament Resources)

| Model | Table | Complexity | Notes |
|---|---|---|---|
| `Subject` | `subjects` | Low | Simple master data, no resource yet in Filament |
| `ExamType` | `exam_types` | Low | Already has Filament resource |
| `ExamYear` | `exam_years` | Low | Already has Filament resource |
| `Combination` | `combinations` | Low-Medium | M2M with subjects pivot |
| `Region` | `regions` | Low | Already has Filament resource |
| `District` | `districts` | Low | Already has Filament resource |
| `DistrictCouncil` | `district_councils` | Low | No Filament resource yet |
| `School` | `schools` | Low | Already has Filament resource |
| `User` | `users` | Low | Already has Filament resource |
| `Role` | `roles` | Low | No Filament resource yet |
| `GradingProfile` | `grading_profiles` | Low | No Filament resource yet |
| `GradingRule` | `grading_rules` | Low | No Filament resource yet |
| `SystemSetting` | `system_settings` | Low | Has custom Filament page |
| `ClassLevel` | `class_levels` | Low | No Filament resource yet |

### ⚙️ WORKFLOW-BASED (Must remain custom pages)

| Module | Reason |
|---|---|
| **Mark Entry** | Multi-step: upload CSV → validate → import → lifecycle (verify → moderate → lock) |
| **Batch Promotion** | State machine transitions with authorization checks |
| **Moderation & Review** | Multi-party approval chain |
| **Bulk CSV Import** (marks, allocations) | Validate → preview errors → commit/rollback |
| **Results Processing** | Grading computation pipeline with progress tracking |
| **Backup & Restore** | File-level operations, encryption, integrity verification |
| **Extremity Analysis** | Statistical computation with configurable thresholds |
| **Public Results Portal** | Public-facing, unauthenticated access |

### 🔄 HYBRID (CRUD + Complex Logic)

| Model | CRUD Part | Workflow Part |
|---|---|---|
| `Candidate` | View/edit candidate details | CSV import, exam registration, subject allocation |
| `CandidateResult` / `FinalGrade` | View computed data | Results pipeline computation |
| `MarkImportBatch` | View batch status/history | State-driven lifecycle transitions |
| `GovernanceAuditLog` | Read-only browsing | Auto-generated by system events |
| `BulkImport` | View import history | Multi-step import orchestration |

---

## 6. Technical Risks & Refactoring Needs

### 🔴 Critical Issues

#### 6.1 Monolithic Blade Files

| File | Lines | Impact |
|---|---|---|
| `resources/views/mark-entry/index.blade.php` | **3,893** | Extremely difficult to maintain, debug, or extend |
| `resources/views/registration/candidates.blade.php` | **2,643** | Complex Alpine.js state management |
| `resources/views/exam-types/show.blade.php` | **2,335** | Mixed concerns |
| `resources/views/exam-types/acsee.blade.php` | **2,099** | Full ACSEE management in one file |
| `resources/views/registration/schools.blade.php` | **1,124** | |
| `resources/views/registration/districts.blade.php` | **1,036** | |

#### 6.2 Monolithic Controller

- `MarkEntryController.php` — **1,342 lines** with 2 explicit `TODO: Add authorization check` comments at lines 707 and 733

#### 6.3 Competing Role Systems

- Spatie Permission v6.24 in `composer.json` but User model uses custom `hasRole()` via `$this->role->code`
- `UnlockBatchController` and `MarkLifecycleApiController` call `$user->hasPermissionTo()` and `$user->roles->pluck()` — **these are Spatie methods that will fail at runtime**
- File: `app/Http/Controllers/MarkEntry/Api/UnlockBatchController.php:47`
- File: `app/Http/Controllers/MarkEntry/Api/MarkLifecycleApiController.php:378`

#### 6.4 Hard-coded Encryption Fallbacks

- `app/Services/SQLiteBackupService.php:38` — `'fallback-encryption-key-do-not-use-in-production'`
- `app/Services/BackupService.php:418` — `'fallback-key-change-in-production'`
- `app/Services/SQLiteRestoreService.php:39` — same fallback key

### 🟡 Medium Issues

#### 6.5 Inline Authorization (48 instances)

Authorization checks are scattered across controllers using `Auth::user()`, `auth()->user()`, `is_admin` instead of consistent Policy/Gate usage.

#### 6.6 Large Service Classes

| Service | Lines | Concern |
|---|---|---|
| `CandidateImportService` | 1,157 | CSV parsing + validation + DB writes |
| `AcseeAllocationCSVImporter` | 727 | CSV import orchestration |
| `HardenedRestoreService` | 614 | Backup restoration |
| `SQLiteRestoreService` | 605 | SQLite-specific restore |
| `SQLiteBackupService` | 520 | SQLite-specific backup |
| `BackupService` | 516 | General backup operations |
| `SchoolImportService` | 485 | School CSV import |
| `DistrictImportService` | 468 | District CSV import |
| `DistrictBulkImportOrchestrator` | 462 | Multi-school bulk import |
| `BulkImportOrchestrator` | 443 | Bulk ZIP import |
| `NectaGradingService` | 438 | Grading computation |

#### 6.7 Missing Policies

No policies exist for: `Subject`, `Combination`, `Candidate` (direct CRUD), `User`, `ExamType`, `GradingProfile`, `SystemSetting`

#### 6.8 Route File Size

- `routes/web.php` — **1,689 lines** (should be further decomposed)

### 🟢 Low Issues

#### 6.9 TODO Comments (3 total)

- `app/Http/Controllers/MarkEntryController.php:707` — `TODO: Add authorization check`
- `app/Http/Controllers/MarkEntryController.php:733` — `TODO: Add authorization check`
- `app/Services/IndexNumber/IndexNumberValidator.php:246` — `TODO: When private_centres table is created, implement proper resolution`

#### 6.10 SQLite in Production

SQLite has a performance ceiling for concurrent writes. Mark entry, bulk imports, and results processing with many simultaneous users may hit locking issues.

#### 6.11 Potential N+1 Queries

Relationship-heavy views (candidates with marks, results dashboards) may suffer N+1 without eager loading verification.

---

## 7. Recommended Admin Panel Strategy

### 7.1 Approach: Hybrid (Filament for CRUD + Custom Blade for Workflows)

Filament v3 is already installed and partially configured. The recommended approach is to expand the existing Filament setup for CRUD operations while keeping the custom Blade pages for workflow-heavy modules.

### 7.2 Current Filament State (Already Implemented)

#### Resources (15 total)

| Resource | File | Status |
|---|---|---|
| `UserResource` | `app/Filament/Admin/Resources/UserResource.php` | ✅ Create/Edit/List |
| `RegionResource` | `app/Filament/Admin/Resources/RegionResource.php` | ✅ CRUD + View |
| `DistrictResource` | `app/Filament/Admin/Resources/DistrictResource.php` | ✅ CRUD + View |
| `SchoolResource` | `app/Filament/Admin/Resources/SchoolResource.php` | ✅ CRUD + View |
| `ExamTypeResource` | `app/Filament/Admin/Resources/ExamTypeResource.php` | ✅ CRUD + View |
| `ExamYearResource` | `app/Filament/Admin/Resources/ExamYearResource.php` | ✅ CRUD + View |
| `CandidateResource` | `app/Filament/Admin/Resources/CandidateResource.php` | ✅ CRUD + View |
| `CandidateResultResource` | `app/Filament/Admin/Resources/CandidateResultResource.php` | ✅ CRUD + View |
| `SubjectMarksResource` | `app/Filament/Admin/Resources/SubjectMarksResource.php` | ✅ CRUD + View |
| `FinalGradeResource` | `app/Filament/Admin/Resources/FinalGradeResource.php` | ✅ CRUD + View |
| `RawMarkResource` | `app/Filament/Admin/Resources/RawMarkResource.php` | ✅ CRUD |
| `BackupResource` | `app/Filament/Admin/Resources/BackupResource.php` | ✅ List/View/Restore |
| `BulkImportResource` | `app/Filament/Admin/Resources/BulkImportResource.php` | ✅ List + View |
| `GovernanceAuditLogResource` | `app/Filament/Admin/Resources/GovernanceAuditLogResource.php` | ✅ Read-only |
| `RestoreAuditLogResource` | `app/Filament/Admin/Resources/RestoreAuditLogResource.php` | ✅ Read-only |

#### Custom Pages (4 total)

| Page | File |
|---|---|
| `Dashboard` | `app/Filament/Admin/Pages/Dashboard.php` |
| `AuditLogs` | `app/Filament/Admin/Pages/AuditLogs.php` |
| `ManageBackups` | `app/Filament/Admin/Pages/ManageBackups.php` |
| `SystemSettings` | `app/Filament/Admin/Pages/SystemSettings.php` |

#### Widgets (5 total)

| Widget | File |
|---|---|
| `StatsOverview` | `app/Filament/Admin/Widgets/StatsOverview.php` |
| `ExamYearOverview` | `app/Filament/Admin/Widgets/ExamYearOverview.php` |
| `BulkImportStats` | `app/Filament/Admin/Widgets/BulkImportStats.php` |
| `RecentAuditLogsWidget` | `app/Filament/Admin/Widgets/RecentAuditLogsWidget.php` |
| `SecurityAlertsWidget` | `app/Filament/Admin/Widgets/SecurityAlertsWidget.php` |

### 7.3 Still Needed as Filament Resources

| Model | Table | Priority | Reason |
|---|---|---|---|
| `Subject` | `subjects` | **High** | Simple CRUD, no resource exists yet |
| `Combination` | `combinations` | **High** | CRUD with subject pivot management |
| `Role` | `roles` | **Medium** | Small table, quick to implement |
| `DistrictCouncil` | `district_councils` | **Medium** | Administrative unit management |
| `GradingProfile` | `grading_profiles` | **Medium** | Configuration management |
| `GradingRule` | `grading_rules` | **Medium** | Grade boundary configuration |
| `ClassLevel` | `class_levels` | **Low** | Rarely changes |

### 7.4 Must Remain Custom Pages (NOT Filament)

| Module | Reason |
|---|---|
| Mark Entry workflow | Multi-step upload → validate → import → lifecycle with real-time progress |
| CSV Bulk Import | Two-phase validation → preview → commit flow with inline error reporting |
| Results Processing | Grading computation pipeline with batch operations |
| ACSEE Allocation Import | Template download → upload → validate → commit with candidate matching |
| Backup/Restore operations | File-level operations, encryption, streaming downloads |
| Extremity Analysis | Statistical computation with configurable thresholds |
| Public Results Portal | Public-facing, unauthenticated, separate layout |
| Dashboard (main) | Custom widgets, hierarchy drilldown, role-scoped views |

### 7.5 Refactoring Required BEFORE Expanding Filament

| Priority | Task | Impact |
|---|---|---|
| **P0** | Resolve Spatie vs Custom role conflict | Runtime errors in mark entry lifecycle |
| **P1** | Remove hard-coded encryption fallback keys | Security vulnerability |
| **P1** | Add authorization checks at MarkEntryController:707,733 | Open authorization gaps |
| **P2** | Add missing Policies for Subject, Combination, User, ExamType | Filament authorization support |
| **P3** | Split `routes/web.php` (1,689L) into domain route files | Maintainability |
| **P3** | Break down `MarkEntryController` (1,342L) | Maintainability |

### 7.6 Layout Conflict Assessment

**No conflicts.** Filament runs on `/admin` path with its own isolated layout (`filament::layouts.app`). The existing Blade app runs on `/` with `resources/views/layout.blade.php`. They coexist completely independently.

### 7.7 Integration Path (Non-Breaking)

1. Filament already runs at `/admin` — completely isolated from the main app
2. Both share the same `User` model and session-based authentication
3. Custom Blade pages continue serving operational workflows at their current routes
4. Filament handles master data CRUD and system oversight
5. No database migration needed — both access the same database
6. The `canAccessPanel()` method on User already restricts Filament to admins only

---

## 8. Implementation Readiness Score

| Criterion | Score | Notes |
|---|---|---|
| Filament installed & configured | **9/10** | Already has 15 resources, 4 pages, 5 widgets |
| Model readiness | **8/10** | Well-defined relationships, fillable fields, scopes |
| Policy coverage | **5/10** | 14 policies exist but critical gaps remain |
| Role system clarity | **3/10** | Dual system conflict (custom + Spatie) — must resolve |
| Blade/Filament isolation | **9/10** | Clean separation at `/admin` vs `/` |
| Database maturity | **7/10** | 52 tables, 72 migrations, but SQLite has scale limits |
| Service layer quality | **7/10** | Good separation of concerns, but some monolithic services |
| Route organization | **5/10** | 7 route files but `web.php` is 1,689 lines |
| **Overall Readiness** | **7/10** | Ready for expansion after resolving role system conflict |

---

## 9. File Size Summary

### Controllers (Top 10 by size)

| File | Lines |
|---|---|
| `MarkEntryController.php` | 1,342 |
| `CandidateController.php` | 544 |
| `HardenedRestoreController.php` | 503 |
| `ExamTypeController.php` | 486 |
| `MarkEntry/Api/MarkLifecycleApiController.php` | 438 |
| `RegionController.php` | 402 |
| `Api/CombinationController.php` | 385 |
| `BackupController.php` | 366 |
| `BulkImportController.php` | 321 |
| `CandidateImportController.php` | 307 |

### Services (Top 10 by size)

| File | Lines |
|---|---|
| `Candidates/CandidateImportService.php` | 1,157 |
| `AcseeAllocationCSVImporter.php` | 727 |
| `HardenedRestoreService.php` | 614 |
| `SQLiteRestoreService.php` | 605 |
| `SQLiteBackupService.php` | 520 |
| `BackupService.php` | 516 |
| `Schools/SchoolImportService.php` | 485 |
| `Districts/DistrictImportService.php` | 468 |
| `MarkImport/DistrictBulkImportOrchestrator.php` | 462 |
| `MarkImport/BulkImportOrchestrator.php` | 443 |

### Blade Views (Top 10 by size)

| File | Lines |
|---|---|
| `mark-entry/index.blade.php` | 3,893 |
| `registration/candidates.blade.php` | 2,643 |
| `exam-types/show.blade.php` | 2,335 |
| `exam-types/acsee.blade.php` | 2,099 |
| `registration/schools.blade.php` | 1,124 |
| `registration/districts.blade.php` | 1,036 |
| `hierarchy/school-results.blade.php` | 672 |
| `dashboard/home.blade.php` | 656 |
| `regions/dashboard.blade.php` | 654 |
| `registration/regions.blade.php` | 622 |

### Route Files

| File | Lines |
|---|---|
| `routes/web.php` | 1,689 |
| `routes/api.php` | 587 |
| `routes/mark-entry.php` | 102 |
| `routes/results.php` | 101 |
| `routes/api-grading.php` | 45 |
| `routes/backup.php` | 41 |
| **Total** | **2,565** |

---

## 10. Complete Model Relationship Map

```
Region
├── hasMany → District
├── hasMany → School
└── hasMany → Candidate (through School)

District
├── belongsTo → Region
└── hasMany → School

DistrictCouncil
├── belongsTo → Region
├── hasMany → School
└── hasMany → User

School
├── belongsTo → DistrictCouncil
├── belongsTo → District
├── belongsTo → Region
├── hasMany → Candidate
└── hasMany → User

Candidate
├── belongsTo → School
├── belongsTo → Combination
├── hasMany → CandidateExamRegistration
├── hasMany → CandidateSubjectSelection
├── hasMany → SubjectMarks
├── hasMany → CandidateResult
└── hasMany → FinalGrade

ExamType
├── hasMany → Subject
├── hasMany → Combination
├── hasMany → CandidateExamRegistration
└── belongsToMany → ClassLevel

Subject
├── belongsTo → ExamType
├── hasMany → CandidateSubjectSelection
├── hasMany → SubjectMarks
└── belongsToMany → Combination (pivot: combination_subject)

Combination
├── belongsTo → ExamType
└── belongsToMany → Subject (pivot: combination_subject)

ExamYear
├── hasMany → CandidateExamRegistration
└── hasMany → Candidate

User
├── belongsTo → Role
├── belongsTo → School
├── belongsTo → DistrictCouncil
├── hasOne → UserScope
└── hasMany → GovernanceAuditLog

MarkImportBatch
├── belongsTo → Region
├── belongsTo → District
├── belongsTo → School
├── belongsTo → Subject
├── belongsTo → ExamType
├── hasMany → RawMark
└── hasOne → MarkImportChecksum

RawMark
├── belongsTo → MarkImportBatch
├── belongsTo → Candidate
├── belongsTo → Subject
├── belongsTo → User (locked_by)
└── hasMany → MarkEntryChange

MarkEntryLifecycleState
├── belongsTo → MarkImportBatch
└── belongsTo → User (transitioned_by)
```

---

*End of Report*
