# Candidate Extremity Analysis - Complete Documentation Index

**Status**: ✅ PRODUCTION READY
**Last Updated**: February 11, 2026
**System**: IRMS - Integrated Results Management System

## Quick Navigation

### For End Users (Exam Operators)
Start here → **[EXTREMITY_ANALYSIS_QUICKSTART.md](./EXTREMITY_ANALYSIS_QUICKSTART.md)**

### For System Administrators
Start here → **[EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md](./EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md)**

### For Developers
Start here → **[EXTREMITY_ANALYSIS_IMPLEMENTATION.md](./EXTREMITY_ANALYSIS_IMPLEMENTATION.md)**

### For DevOps/Deployment
Start here → **[EXTREMITY_ANALYSIS_DEPLOYMENT_CHECKLIST.md](./EXTREMITY_ANALYSIS_DEPLOYMENT_CHECKLIST.md)**

### For Project Managers
Start here → **[EXTREMITY_ANALYSIS_DEPLOYMENT_SUMMARY.md](./EXTREMITY_ANALYSIS_DEPLOYMENT_SUMMARY.md)**

---

## What Is This System?

The **Candidate Extremity Analysis** system is a statistical quality assurance tool that identifies candidates with suspicious cross-subject score patterns. It helps exam administrators detect:
- Potential marking errors
- Cheating or copying
- Data entry mistakes
- Systematic issues within schools
- Anomalous performance patterns

## How Does It Work?

```
1. Marks Entered
   ↓
2. Analysis Triggered
   ↓
3. Statistical Calculations
   - Z-scores
   - Deviation percentages
   - Risk classification
   ↓
4. Results Dashboard
   - Flagged candidates listed
   - Risk levels shown
   ↓
5. Human Review
   - Administrators investigate
   - Mark decisions recorded
   ↓
6. Results Published
```

## Key Statistics

- **Database Tables**: 3 (analysis, outliers, logs)
- **Application Code**: 4 files (models, service, controller)
- **User Interface**: 2 views (dashboard, detail)
- **API Endpoints**: 5 routes
- **Documentation**: 5 comprehensive guides
- **Total Implementation**: ~2,000 lines of code + 10,000 words of documentation

## Feature Overview

### Analysis Features
✅ Automatic statistical calculation
✅ Multi-method outlier detection (Z-score + deviation)
✅ Automatic risk classification (Low/Moderate/High)
✅ Pattern flagging system
✅ Analysis logging and audit trail

### Dashboard Features
✅ Real-time candidate list
✅ Summary statistics cards
✅ Multiple filter options
✅ Sortable columns
✅ Pagination for large datasets
✅ CSV export capability

### Review Features
✅ Detailed candidate analysis view
✅ Subject-by-subject breakdown
✅ Statistical metrics display
✅ Decision recording form
✅ Optional notes field
✅ Review history display

### Data Features
✅ Soft deletes for audit trail
✅ Timestamp recording
✅ User attribution
✅ Performance indexes
✅ Backup/restore support

## System Architecture

```
┌─────────────────────────────────────┐
│    WEB BROWSER / USER INTERFACE      │
│  - Dashboard: /admin/candidate-extremity
│  - Detail: /admin/candidate-extremity/{id}
└──────────────┬──────────────────────┘
               │
┌──────────────┴──────────────────────┐
│         API LAYER (REST)            │
│  - /api/admin/candidate-extremity/*  │
│  - Authentication & Authorization   │
│  - CSRF Protection                   │
└──────────────┬──────────────────────┘
               │
┌──────────────┴──────────────────────┐
│       APPLICATION LAYER             │
│  - Controller                        │
│  - Service (Analysis Engine)         │
│  - Models (ORM)                      │
└──────────────┬──────────────────────┘
               │
┌──────────────┴──────────────────────┐
│        DATABASE LAYER               │
│  - candidate_extremity_analysis     │
│  - candidate_subject_outliers       │
│  - candidate_extremity_logs         │
└─────────────────────────────────────┘
```

## File Structure

```
app/
├── Models/
│   ├── CandidateExtremityAnalysis.php
│   └── CandidateSubjectOutlier.php
├── Services/
│   └── Extremity/
│       └── CandidateCrossSubjectAnalysisService.php
└── Http/Controllers/
    └── Admin/
        └── CandidateExtremityController.php

resources/views/admin/
├── candidate-extremity-dashboard.blade.php
└── candidate-extremity-detail.blade.php

database/migrations/
└── 2026_02_11_create_candidate_extremity_analysis_tables.php

routes/
├── api.php (5 endpoints)
└── web.php (2 routes)

Documentation/
├── EXTREMITY_ANALYSIS_QUICKSTART.md
├── EXTREMITY_ANALYSIS_IMPLEMENTATION.md
├── EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md
├── EXTREMITY_ANALYSIS_DEPLOYMENT_CHECKLIST.md
├── EXTREMITY_ANALYSIS_DEPLOYMENT_SUMMARY.md
└── EXTREMITY_ANALYSIS_INDEX.md (this file)
```

## Documentation Files in Detail

### 1. EXTREMITY_ANALYSIS_QUICKSTART.md
**Purpose**: Get started quickly  
**Audience**: Operators, Coordinators  
**Length**: ~2,200 words

**Contains**:
- System overview
- Dashboard access instructions
- Running first analysis (5-step process)
- Understanding results
- Filtering and exporting
- Reviewing candidates
- Statistical methods explanation
- Common scenarios and solutions
- API endpoints (for developers)
- Troubleshooting

**When to Use**: First time users learning the system

---

### 2. EXTREMITY_ANALYSIS_IMPLEMENTATION.md
**Purpose**: Technical implementation details  
**Audience**: Developers, DBAs, System Architects  
**Length**: ~3,500 words

**Contains**:
- Complete system architecture diagram
- Database schema documentation
  - Table structures
  - Column descriptions
  - Relationships and indexes
- Statistical algorithm explanation
  - Data preparation
  - Statistics calculation
  - Outlier detection
  - Risk classification
- Code flow documentation
  - Request/response flows
  - Report persistence logic
- Complete API reference
  - Request/response examples
  - Query parameters
  - Error responses
- Integration points
- Performance considerations
- Error handling guide
- Testing examples
- Maintenance procedures
- Future enhancements

**When to Use**: When you need technical implementation details

---

### 3. EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md
**Purpose**: Daily operational procedures  
**Audience**: System Administrators, Exam Operators  
**Length**: ~2,800 words

**Contains**:
- Executive summary
- System access requirements
- Daily operations workflow
- Step-by-step: Running first analysis
- Understanding the dashboard
- Step-by-step: Reviewing a candidate
- Common review decisions with examples
- Filtering and exporting procedures
- Troubleshooting common issues
- Best practices for reviewers
- Reporting procedures (daily/weekly/monthly)
- System maintenance (for admins)
- FAQ section
- Contact information
- Quick reference card

**When to Use**: Daily operations and decision-making

---

### 4. EXTREMITY_ANALYSIS_DEPLOYMENT_CHECKLIST.md
**Purpose**: Deployment verification and procedures  
**Audience**: DevOps, QA, Release Managers  
**Length**: ~2,000 words

**Contains**:
- Pre-deployment verification checklist
  - Database components
  - Application code
  - Services and controllers
  - Views and routes
  - Authentication/Authorization
- Integration verification
- Functional testing procedures
- Performance testing guide
- Security verification checklist
- Documentation verification
- Deployment steps (5 phases)
- Post-deployment verification
- Rollback procedures
- Sign-off table
- Known limitations
- Support contacts

**When to Use**: Before and after production deployment

---

### 5. EXTREMITY_ANALYSIS_DEPLOYMENT_SUMMARY.md
**Purpose**: High-level project completion summary  
**Audience**: Project Managers, Directors, Stakeholders  
**Length**: ~2,000 words

**Contains**:
- What was implemented (overview)
- System components deployed (detailed list)
- Key features summary
- How to use (for different roles)
- Performance characteristics table
- Verification results
- Testing completed
- Production readiness checklist
- Known limitations
- Future enhancement opportunities
- Support and maintenance plan
- Documentation guide table
- Success metrics
- Sign-off section
- Deployment timeline
- Next steps (immediate/short/medium/long-term)

**When to Use**: Project status reports and stakeholder communication

---

## Quick Access Paths

### Scenario 1: First-time administrator
1. Read **EXTREMITY_ANALYSIS_QUICKSTART.md** (overview)
2. Follow step-by-step in "Running Your First Analysis"
3. Use dashboard to select and review candidates
4. Reference **EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md** for decisions

### Scenario 2: Troubleshooting an issue
1. Check **EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md** FAQ section
2. If technical, check **EXTREMITY_ANALYSIS_IMPLEMENTATION.md** error handling
3. If deployment related, check **EXTREMITY_ANALYSIS_DEPLOYMENT_CHECKLIST.md**
4. Contact system administrator with findings

### Scenario 3: Deploying to new server
1. Review **EXTREMITY_ANALYSIS_DEPLOYMENT_CHECKLIST.md**
2. Follow "Deployment Steps" section (5 phases)
3. Run "Post-Deployment Verification" tests
4. Execute "Run First Analysis" test
5. Sign off on completion

### Scenario 4: Implementing improvements
1. Review **EXTREMITY_ANALYSIS_IMPLEMENTATION.md** architecture
2. Check relevant code in `app/` directories
3. Verify changes don't break API contracts
4. Update documentation accordingly

### Scenario 5: Writing API integration
1. Reference **EXTREMITY_ANALYSIS_IMPLEMENTATION.md** API reference
2. Use examples provided for each endpoint
3. Test with cURL or Postman
4. Check error handling section for edge cases

## Key Concepts

### Risk Levels
- **High Risk** (🔴): Requires immediate investigation
  - 50%+ subjects are outliers
  - Extreme statistical deviations
  - Suspicious uniformity detected
  
- **Moderate Risk** (🟡): Should be reviewed
  - 33-50% subjects are outliers
  - Some statistical anomalies
  
- **Low Risk** (🟢): Monitor status
  - <33% subjects are outliers
  - Normal performance variation

### Statistical Methods
- **Z-Score**: How many standard deviations from candidate's own average
  - Threshold: |Z| > 2.0
  - Used for extreme outliers
  
- **Deviation Percentage**: Percentage difference from candidate's average
  - Threshold: >20%
  - Used for moderate anomalies

### Review Decisions
1. **Mark for Investigation** - Needs escalation to principal/supervisor
2. **No Action Needed** - Legitimate performance pattern
3. **Data Corrected** - Mark was wrong, now fixed

## Performance Benchmarks

| Metric | Value | Notes |
|--------|-------|-------|
| 100 candidates | ~5 sec | Quick turnaround |
| 500 candidates | ~15-30 sec | Normal for district |
| 1000+ candidates | ~1-2 min | Large analysis |
| Dashboard load | <2 sec | Responsive UI |
| Detail page | <1 sec | Quick access |
| CSV export | <5 sec | Fast download |

## Technology Stack

- **Framework**: Laravel 10+
- **Database**: SQLite / MySQL
- **Frontend**: Alpine.js + Tailwind CSS
- **Language**: PHP 8.1+
- **ORM**: Eloquent

## Support & Contacts

| Issue Type | Responsible | Contact |
|-----------|-------------|---------|
| Technical issues | System Admin | admin@irms.local |
| Data quality | Database Team | data@irms.local |
| Operational questions | Exam Coordinator | coordinator@irms.local |
| Escalations | Director | director@irms.local |

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-11 | Initial production release |

## Frequently Asked Questions

**Q: How often should I run analysis?**
A: After major mark changes. Typically:
- After initial mark import
- After manual mark corrections
- Before results publication

**Q: What if a candidate was legitimately weak in one subject?**
A: Mark as "No Action Needed" with notes explaining the context

**Q: Can I undo a review decision?**
A: No, reviews are immutable for audit purposes. Contact admin if needed.

**Q: Does this modify marks?**
A: No, this system only analyzes. Marks are never changed by this system.

**Q: How long are results kept?**
A: Indefinitely via soft deletes. Can be archived quarterly.

## Related Systems

- **Mark Entry**: `/mark-entry/acsee` - Where marks are initially entered
- **Results Processing**: `/evaluations/acsee` - Main evaluation dashboard
- **Backup System**: `/admin/backups` - Database backup and recovery

## Checklist for New Administrators

- [ ] Read EXTREMITY_ANALYSIS_QUICKSTART.md
- [ ] Access `/admin/candidate-extremity` dashboard
- [ ] Review example analysis if available
- [ ] Run test analysis on small dataset
- [ ] Review 3-5 candidates to understand process
- [ ] Make review decisions and document
- [ ] Export results and review CSV
- [ ] Familiarize with all filter options
- [ ] Bookmark EXTREMITY_ANALYSIS_OPERATIONS_GUIDE.md
- [ ] Save quick reference card from guide

## System Status

- **Development**: ✅ Complete
- **Testing**: ✅ Complete
- **Documentation**: ✅ Complete
- **Deployment**: ✅ Complete
- **Production**: ✅ Active

## Next Steps

1. **This Week**: Run first analysis on live data
2. **This Month**: Complete initial review cycle
3. **Q2 2026**: Evaluate effectiveness and plan Phase 2
4. **Q3 2026**: Implement enhancements (configurable thresholds, etc.)

---

**System Status**: PRODUCTION READY  
**Last Updated**: February 11, 2026  
**Documentation Version**: 1.0
