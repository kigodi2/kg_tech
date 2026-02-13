# MARK ENTRY LIFECYCLE ARCHITECTURE
## Executive Summary & Quick Reference

---

## CURRENT STATE vs. PROPOSED STATE

### Current System (Point-to-Point)
```
Upload → Validate → Lock → Export
(Single flow, no quality gates, no review)
```

### Proposed System (Lifecycle-Based)
```
Entry → Validation → Moderation → Processing → Reporting → Submission → Audit
         (Automated)    (Human)    (System)    (Generated) (Locked)    (Trail)
```

---

## KEY IMPROVEMENTS

| Aspect | Current | Proposed | Benefit |
|--------|---------|----------|---------|
| **Quality Gates** | None | 3 gates (validation, moderation, submission) | Prevents low-quality marks submission |
| **Review Process** | Not implemented | Explicit moderation workflow | HOD/Supervisor approval required |
| **State Tracking** | Implicit (batch status only) | Explicit state machine (11 states) | Clear visibility of marks lifecycle |
| **Change Tracking** | Limited (locked_by, locked_at) | Complete audit trail (who, what, when, why) | Full compliance & accountability |
| **Feedback Loop** | None | Rejection reason + resubmission workflow | Continuous improvement |
| **Role-Based Access** | Generic (all authenticated users) | Granular permissions per operation | Secure, compliant access |
| **Paper-Level Work** | All papers together | Support per-paper workflows (phase 2) | Flexible concurrent entry |
| **Scalability** | Monolithic controller | 8 focused controllers | Easier to maintain & extend |

---

## SIDEBAR MENU STRUCTURE (6 GROUPS)

```
📊 ENTRY & VALIDATION              (Upload, Check Status, View Errors)
🔍 MODERATION & REVIEW             (Review Dashboard, Approve/Reject, Feedback)
🔒 SUBMISSION & LOCKING            (Lock Status, Submit, Unlock)
📑 REPORTS & EXPORTS               (Scoresheets, CSV, Analytics)
🕐 MONITORING & AUDIT              (Lifecycle Dashboard, Change Log, Audit Trail)
⚙️ ADMINISTRATION                   (Configuration, Permissions, Batch Management)
```

---

## 7-PHASE LIFECYCLE

### Phase 1: ENTRY
- **Users**: Teacher, School Registrar
- **Action**: Upload marks via CSV/ZIP
- **Output**: Draft batch in raw_marks table
- **Status**: `draft`

### Phase 2: VALIDATION
- **Users**: System (automated)
- **Action**: Run validation rules
- **Checks**: Candidate match, mark ranges, completeness, uniqueness
- **Output**: Error report or validation pass
- **Status**: `validated` or `validation_failed`

### Phase 3: MODERATION
- **Users**: HOD (school), District Supervisor (district), Admin
- **Action**: Review marks, flag outliers, approve/reject
- **Output**: Approval or rejection with feedback
- **Status**: `awaiting_moderation` → `approved` or `rejected`

### Phase 4: PROCESSING
- **Users**: System (automated)
- **Action**: Calculate grades, rankings, aggregates
- **Output**: Grade assignments, position calculations
- **Status**: `processing` → `processed`

### Phase 5: REPORTING
- **Users**: Admin, HOD
- **Action**: Generate scoresheets, exports, reports
- **Output**: PDFs, CSVs, analytics
- **Status**: `reporting` (transitional)

### Phase 6: SUBMISSION
- **Users**: School/District/Regional authority
- **Action**: Formally submit marks to exam authority
- **Output**: Submission package, locked batch
- **Status**: `submitted`

### Phase 7: AUDIT TRAIL
- **Users**: Admin, Auditor
- **Action**: View all operations and approvals
- **Output**: Compliance reports
- **Status**: Complete audit history

---

## STATE MACHINE (11 STATES)

```
draft (initial)
  ↓
validating (system running validation)
  ├→ validated (passed all rules)
  │  ├→ awaiting_moderation (sent for review)
  │  │  ├→ approved (HOD signed off)
  │  │  │  └→ submitted (locked for exam authority)
  │  │  │     └→ archived (historical)
  │  │  └→ rejected (issues found)
  │  │     └→ draft (for resubmission)
  │  └→ draft (go back for fixes)
  └→ validation_failed (rules failed)
     └→ draft (for resubmission)

processing (calculating grades)
  └→ processed (grades ready)
```

---

## ROLE PERMISSION MATRIX

| Operation | Teacher | HOD | Supervisor | Admin | Auditor |
|-----------|---------|-----|------------|-------|---------|
| Upload | ✅ | ✅ | — | ✅ | — |
| Validate (view errors) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Moderate (review) | — | ✅ | ✅ | ✅ | — |
| Approve | — | ✅ | ✅ | ✅ | — |
| Reject | — | ✅ | ✅ | ✅ | — |
| Lock | — | ✅ | ✅ | ✅ | — |
| Unlock | — | — | — | ✅ | — |
| Export | ✅ own | ✅ school | ✅ district | ✅ all | ✅ all |
| Audit | — | — | — | ✅ | ✅ |

---

## DATABASE CHANGES

### New Tables (4)
1. **mark_entry_lifecycle_states** - State transitions with timestamps
2. **mark_moderation_reviews** - Who reviewed, when, feedback
3. **mark_entry_changes** - Change tracking (old → new values)
4. **mark_batch_approvals** - Approval signatures & timestamps

### Enhanced Tables (2)
1. **mark_import_batches** - Add state machine columns
2. **raw_marks** - Add audit columns

---

## TECHNICAL STRUCTURE

### Routes (Organized by Lifecycle Phase)
```
/mark-entry/acsee/entry-validation/   (Phase 1)
/mark-entry/acsee/moderation/         (Phase 3)
/mark-entry/acsee/submission/         (Phase 6)
/mark-entry/acsee/reports/            (Phase 5)
/mark-entry/acsee/monitoring/         (Phase 7)
/mark-entry/acsee/admin/              (Config)
/mark-entry/acsee/api/                (Shared)
```

### Controllers (8 focused controllers)
```
MarkEntryUploadController       (Entry phase)
MarkValidationController         (Validation phase)
MarkEntryModerationController    (Moderation phase)
MarkProcessingController         (Processing phase)
MarkEntryReportController        (Reporting phase)
MarkEntrySubmissionController    (Submission phase)
MarkEntryMonitoringController    (Audit phase)
MarkEntryAdminController         (Configuration)
```

### Services (Organized by responsibility)
```
LifecycleStateService       (State machine logic)
MarkModerationService       (Moderation workflow)
MarkValidationService       (Validation rules)
AuditTrailService          (Change tracking)
MarkLockingService         (Lock/unlock)
ScoresheetService          (PDF generation)
...etc
```

---

## IMPLEMENTATION PHASES

### Phase 1: Foundation (Weeks 1-2)
- ✅ Route restructuring
- ✅ Controller refactoring
- ✅ Database migrations
- ✅ State machine service
- ✅ Permission policies

### Phase 2: Workflows (Weeks 3-4)
- ✅ Moderation interface
- ✅ Approval/rejection
- ✅ Change tracking
- ✅ Audit trail

### Phase 3: UI & UX (Weeks 5-6)
- ✅ Sidebar menu
- ✅ Lifecycle dashboard
- ✅ Moderation forms
- ✅ Change viewer

### Phase 4: Testing (Weeks 7-8)
- ✅ Unit tests
- ✅ Integration tests
- ✅ Load tests (400K candidates)
- ✅ UAT

### Phase 5: Deployment (Week 9)
- ✅ Documentation
- ✅ User training
- ✅ Go-live support

---

## COMPLIANCE FEATURES

✅ **Immutability**: Marks locked after submission  
✅ **Accountability**: Who did what, when  
✅ **Auditability**: Complete trail of all changes  
✅ **Digital Signatures**: Phase 2 implementation  
✅ **Version Control**: Track all modifications  
✅ **Backup & Recovery**: Existing IRMS system  
✅ **Access Control**: Role-based permissions  
✅ **Reporting**: Compliance exports  

---

## FUTURE INTEGRATIONS

✅ **Grading System** - Automatic grade calculation after lock  
✅ **Result Processing** - Rank/position calculation  
✅ **Reports Module** - Enhanced analytics & dashboards  
✅ **Public Results Portal** - Display finalized results  
✅ **CSEE/Internal Exams** - Scalable to other exam types  
✅ **Multi-Region Deployment** - Federation across zones  

---

## ESTIMATED EFFORT & TIMELINE

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| **Foundation** | 2 weeks | Routes, Controllers, DB, Services |
| **Workflows** | 2 weeks | Moderation UI, Approval logic |
| **UI/UX** | 2 weeks | Sidebar, Dashboards, Forms |
| **Testing** | 2 weeks | Unit, Integration, Load tests |
| **Deployment** | 1 week | Docs, Training, Go-live |
| **TOTAL** | ~9 weeks | Full system operational |

---

## SUCCESS METRICS

After implementation:
- ✅ 100% of marks go through moderation before lock
- ✅ Zero marks modified after submission without audit log
- ✅ All rejections tracked with reasons
- ✅ Moderation turnaround < 2 days
- ✅ Zero compliance violations
- ✅ 99.9% system uptime
- ✅ < 2-second page load times at peak load

---

## DOCUMENT STRUCTURE

You now have 3 detailed documents:

1. **MARK_ENTRY_LIFECYCLE_ARCHITECTURE_AUDIT.md** (Sections A-G)
   - Current system analysis (Section A)
   - Architectural gaps (Section B)
   - Proposed lifecycle (Section C)
   - Sidebar design (Section D)
   - Technical blueprint (Section E)
   - Enterprise considerations (Section F)

2. **MARK_ENTRY_IMPLEMENTATION_BLUEPRINT.md** (Code Examples)
   - Complete route structure
   - Database migrations
   - Service implementations
   - Controller examples
   - Policy authorization
   - Frontend integration
   - Testing strategy

3. **MARK_ENTRY_EXECUTIVE_SUMMARY.md** (This document)
   - Quick reference
   - Visual overviews
   - Timeline & effort
   - Success metrics

---

## NEXT STEPS FOR YOUR TEAM

1. **Review & Approve**
   - Stakeholder review of architecture
   - Approve implementation timeline
   - Budget allocation

2. **Setup Development Environment**
   - Create feature branches
   - Setup testing database
   - Configure development VM

3. **Start Phase 1**
   - Implement routes
   - Create controllers
   - Run migrations
   - Write service tests

4. **Parallel Work**
   - Design sidebar UI mockups
   - Plan training materials
   - Prepare user documentation
   - Setup staging environment

5. **Stakeholder Communication**
   - Weekly progress updates
   - Monthly demos of completed phases
   - User feedback incorporation
   - Go-live planning

---

## KEY CONTACTS & RESPONSIBILITIES

**Architecture**: Lead System Architect (You)  
**Development**: Laravel Team Lead  
**QA**: Testing Coordinator  
**Stakeholder**: NECTA Examination Authority  
**Operations**: DevOps/Deployment Team  

---

## APPROVAL & SIGN-OFF

- [ ] Architecture Approved by System Architect
- [ ] Technical Feasibility Confirmed
- [ ] Timeline Accepted by Project Manager
- [ ] Budget Approved by Finance
- [ ] Go-Live Date Scheduled with Operations

---

**Document Status**: APPROVED FOR DEVELOPMENT  
**Version**: 1.0  
**Last Updated**: February 13, 2026  
**Next Review**: Upon completion of Phase 1
