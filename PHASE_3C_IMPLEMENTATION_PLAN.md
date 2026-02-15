# Phase 3C Implementation Plan
## Mark Entry Lifecycle - Full Section Implementation
### February 13, 2026

---

## Overview

Phase 3C involves implementing **functional dashboard sections for all 6 menu groups** in the sidebar, replacing placeholders with actual working sections.

**Timeline**: 2-3 weeks  
**Scope**: 20 new sections + active state highlighting + responsive layouts

---

## What's Already Done (Phase 3A-3B)

✅ Sidebar menu with 6 groups (24 items)  
✅ Smooth scroll navigation  
✅ Responsive design  
✅ Entry & Validation sections (5-6 working)  
✅ Proper Alpine.js scope integration  

---

## Phase 3C Deliverables

### 1. Moderation & Review Group (4 Sections)

**📋 Review Dashboard**
- Display list of pending batches
- Show batch metadata (school, subject, uploaded date)
- Display validation status
- Show candidate count
- Quick action buttons

**⏳ Pending Review**
- Filter to show only "awaiting_moderation" batches
- Show HOD comments
- Display flagged records
- Mark outliers/suspicious data

**✅ Approve Marks**
- Approve selected batches
- Add approval comments
- Set approval date/time
- Approve with optional notes
- Bulk approve functionality

**❌ Reject & Feedback**
- Reject batches with reasons
- Provide detailed feedback
- Return to entry/validation phase
- Track rejection history

### 2. Submission & Locking Group (4 Sections)

**🔒 Lock Status**
- Show lock status per batch
- Display lock date/time
- Show locked by (user)
- Display lock reason
- Unlock request option

**📤 Submit Marks**
- Submit approved batches to authority
- Generate submission package
- Add submission metadata
- Confirmation dialog
- Success/error messages

**(Admin) Unlock**
- Admin-only unlock functionality
- Unlock reason required
- Unlock confirmation
- Audit log entry

**📜 History**
- Submission history table
- Shows all submissions
- Lock/unlock timeline
- Status changes
- User who performed action

### 3. Reports & Exports Group (4 Sections)

**📄 Scoresheets (PDF)**
- Generate PDF scoresheets
- Download option
- Preview before download
- Multiple format options
- Batch export

**📊 CSV Export**
- Export marks to CSV
- Multiple export formats
- Download option
- Custom column selection
- Scheduled export

**📈 Analytics**
- Grade distribution charts
- Pass/fail statistics
- Subject performance
- Regional comparison
- Time-based trends

**📋 Summary Report**
- Executive summary
- Key statistics
- Performance metrics
- Recommendations
- Print/export options

### 4. Monitoring & Audit Group (4 Sections)

**📊 Lifecycle Dashboard**
- Show all batches in lifecycle
- Visual state indicators
- Status breakdown pie chart
- Timeline view
- Filter by status

**📝 Change Log**
- Log all mark changes
- Show before/after values
- Changed by (user)
- Change timestamp
- Change reason

**🔍 Audit Trail**
- Complete audit history
- All user actions
- IP addresses
- Timestamps
- Action details

**👥 Activity Log**
- User activity
- Login/logout times
- Action history
- Time spent per section
- Activity charts

### 5. Administration Group (4 Sections)

**⚙️ Configuration**
- System settings
- Mark entry settings
- Grading scale configuration
- Validation rules
- Save/reset options

**🔐 Permissions**
- User role management
- Permission matrix
- Grant/revoke permissions
- Role templates
- User assignment

**📦 Batch Management**
- Batch list with filters
- Batch details view
- Batch operations (delete, archive)
- Batch metadata
- Recovery options

**🖥️ System Logs**
- Application logs
- Error logs
- Access logs
- System events
- Log download

---

## Implementation Approach

### Phase 3C-1: Dashboard Shells (Week 1)
- Create empty sections with IDs
- Add section headers
- Responsive grid layout
- Status indicators
- Placeholder content

### Phase 3C-2: Data Integration (Week 2)
- Connect to existing models
- Fetch batch data
- Display in tables
- Add filters
- Add sorting

### Phase 3C-3: Functionality (Week 3)
- Add buttons/actions
- Implement workflows
- Add confirmations
- Error handling
- Success messages

### Phase 3C-4: Polish (Week 4)
- Active state highlighting
- Keyboard shortcuts
- Accessibility
- Performance optimization
- Testing

---

## Technical Requirements

### Database Tables Needed
- `mark_moderation_reviews` - Moderation data
- `mark_submissions` - Submission history
- `audit_logs` - Audit trail
- `system_logs` - System events

### API Endpoints Needed
- GET `/api/batches/pending` - Pending moderation
- POST `/api/batches/{id}/approve` - Approve batch
- POST `/api/batches/{id}/reject` - Reject batch
- GET `/api/submissions` - Submission history
- GET `/api/analytics/marks` - Analytics data

### Frontend Components
- DataTable with pagination
- Modal dialogs
- Status badges
- Charts/graphs (Chart.js)
- Date picker
- Multi-select filter

---

## Section Content Structure

Each section should have:

```html
<section id="section-name" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Section Title</h3>
    
    <!-- Filters -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <!-- Filter controls -->
    </div>
    
    <!-- Content -->
    <div class="space-y-4">
        <!-- Main content (table, chart, form) -->
    </div>
    
    <!-- Actions -->
    <div class="flex gap-2 mt-6">
        <!-- Action buttons -->
    </div>
</section>
```

---

## Active State Highlighting (Phase 3B+)

Add to Alpine.js:
```javascript
currentSection: null,
sections: [
    'upload', 'csv-tab', 'school-bulk', 'district-bulk',
    'moderation-dashboard', 'pending-review', 'approve-marks', 'reject-feedback',
    // ... etc
],
setActiveSection(id) {
    this.currentSection = id;
    document.querySelectorAll('aside a').forEach(link => {
        if (link.href.includes(id)) {
            link.classList.add('text-blue-500', 'font-bold');
        } else {
            link.classList.remove('text-blue-500', 'font-bold');
        }
    });
}
```

Add to sidebar links:
```html
<a href="#section-id" @click.prevent="smoothScroll('#section-id'); setActiveSection('section-id')">
```

---

## Milestone Checklist

### Week 1 - Shells
- [ ] Moderation & Review sections (HTML structure)
- [ ] Submission & Locking sections (HTML structure)
- [ ] Reports & Exports sections (HTML structure)
- [ ] Monitoring & Audit sections (HTML structure)
- [ ] Administration sections (HTML structure)
- [ ] Responsive layout testing
- [ ] Scroll navigation working

### Week 2 - Data
- [ ] Database migrations created
- [ ] API endpoints implemented
- [ ] Data fetching implemented
- [ ] Tables populated with data
- [ ] Filters working
- [ ] Sorting implemented

### Week 3 - Actions
- [ ] Approve/Reject workflows
- [ ] Lock/Unlock functionality
- [ ] Export functionality
- [ ] Audit logging
- [ ] Confirmations added
- [ ] Error handling

### Week 4 - Polish
- [ ] Active state highlighting
- [ ] Performance optimization
- [ ] Accessibility audit
- [ ] Testing completed
- [ ] Documentation updated

---

## Resource Requirements

- **UI Components**: DataTables, Modal dialogs, Charts
- **Packages**: Chart.js, Date picker, Export libraries
- **Database**: New tables for audit/moderation
- **API**: 15-20 new endpoints
- **Testing**: Unit and integration tests

---

## Estimated Effort

| Component | Effort | Timeline |
|-----------|--------|----------|
| UI/HTML | 40 hours | Week 1 |
| Backend/API | 60 hours | Week 2 |
| Functionality | 80 hours | Week 3 |
| Testing/Polish | 40 hours | Week 4 |
| **Total** | **220 hours** | **4 weeks** |

---

## Next Steps

1. **Immediate**: Add HTML section shells (Phase 3C-1)
2. **Week 2**: Implement backend API endpoints
3. **Week 3**: Add interactive functionality
4. **Week 4**: Testing and polish

---

**Status**: Ready to implement  
**Priority**: High  
**Date**: February 13, 2026
