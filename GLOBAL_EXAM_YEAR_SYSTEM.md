# Global Exam Year System - Implementation Plan

**Status:** DESIGN COMPLETE, READY TO IMPLEMENT  
**Goal:** Set active exam year in admin, auto-use everywhere system-wide  
**Impact:** Eliminates need to manually select year in registration, mark entry, import, etc.

---

## Overview

Create a **Global Exam Year System** where:

1. ✅ Admin sets the **Active Exam Year** in system settings
2. ✅ That year is **automatically used** everywhere (registration, mark entry, import, etc.)
3. ✅ Users can still **override manually** if needed
4. ✅ System defaults to active year, improving UX

---

## Architecture

### Current State
```
ExamYear Model has is_active flag
Users manually select year in:
  - Registration form (NEW field)
  - Bulk import (NEW modal)
  - Mark entry (filter selector)
```

### Target State
```
Global Active Exam Year (from admin settings)
  ↓
Automatically pre-filled/pre-selected in:
  - Registration form
  - Bulk import
  - Mark entry
  - All other modules
```

---

## Implementation Strategy

### Phase 1: Frontend - Auto-load Active Year

**File:** `resources/views/registration/candidates.blade.php`

**In init() method, set default from active year:**
```javascript
async init() {
    const savedPageSize = localStorage.getItem('candidatesPageSize');
    if (savedPageSize) {
        this.pageSize = parseInt(savedPageSize);
    }
    
    await this.loadRegions();
    await this.loadDistricts();
    await this.loadSchools();
    await this.loadExamYears();
    await this.loadCandidates();
    
    // NEW: Set import exam year to active year
    await this.setDefaultExamYear();
}

async setDefaultExamYear() {
    try {
        const response = await fetch('/api/exam-years/active');
        const data = await response.json();
        if (data.active_year) {
            this.formData.exam_year = data.active_year.year_label;
            this.importExamYear = data.active_year.year_label;
        }
    } catch (error) {
        console.error('Error loading active exam year:', error);
    }
}
```

### Phase 2: Frontend - Mark Entry Page

**File:** `resources/views/mark-entry/index.blade.php`

**Add active year loading:**
```javascript
async init() {
    await this.loadRegions();
    await this.loadSubjects();
    await this.loadExamYears();
    await this.setDefaultExamYear();  // NEW
}

async setDefaultExamYear() {
    try {
        const response = await fetch('/api/exam-years/active');
        const data = await response.json();
        if (data.active_year) {
            this.examYear = data.active_year.year_label;
        }
    } catch (error) {
        console.error('Error loading active exam year:', error);
    }
}
```

### Phase 3: Backend API - Add Active Year Endpoint

**File:** `routes/web.php`

**Add new endpoint:**
```php
Route::get('/api/exam-years/active', function () {
    $activeYear = \App\Models\ExamYear::active()->first();
    
    if (!$activeYear) {
        return response()->json([
            'active_year' => null,
            'message' => 'No active exam year set'
        ]);
    }
    
    return response()->json([
        'active_year' => [
            'id' => $activeYear->id,
            'year_label' => $activeYear->year_label,
            'is_locked' => $activeYear->is_locked,
            'status' => $activeYear->getStatusLabel()
        ]
    ]);
});
```

### Phase 4: Admin UI - Exam Year Management

**Location:** Admin panel (already exists via Filament likely)

**What admin can do:**
1. View all exam years
2. Set one as "Active"
3. Lock/unlock years
4. View year statistics

**Implementation:**
- If using Filament, add action button to activate year
- Deactivates all other years automatically
- Show status badge

### Phase 5: Smart Form Handling

**For Registration Form:**
```javascript
// If user has exam year pre-filled from active year,
// show it but allow override
formData: {
    full_name: '',
    gender: '',
    combination: '',
    school_id: '',
    exam_type: '',
    exam_year: 'AUTO-FILLED-FROM-ACTIVE-YEAR'  // NEW
}
```

**For Bulk Import:**
```javascript
// Pre-fill exam year in modal with active year
showImportModal() {
    // Auto-set to active year
    this.importExamYear = this.getActiveExamYear();
    this.showImportModal = true;
}
```

---

## Benefits

### User Experience
✅ No need to select year manually each time  
✅ Defaults to what admin configured  
✅ Faster registration and import workflows  
✅ Fewer mistakes from selecting wrong year  

### Data Integrity
✅ All entries use consistent year context  
✅ Admin controls system-wide year  
✅ Prevents mixing candidates across years  
✅ Clear audit trail of which year was active  

### Administration
✅ Admin can change year once for whole system  
✅ No per-form configuration needed  
✅ Easy to manage year transitions  
✅ Clear visibility of active year  

---

## Implementation Steps

### Step 1: Add API Endpoint (15 min)
- Routes: Add `/api/exam-years/active` endpoint
- Returns active exam year with full details

### Step 2: Update Registration Page (30 min)
- Add `setDefaultExamYear()` method
- Call in `init()`
- Pre-fill form with active year

### Step 3: Update Bulk Import (15 min)
- Add `setDefaultExamYear()` method
- Pre-fill modal with active year

### Step 4: Update Mark Entry (15 min)
- Add `setDefaultExamYear()` method
- Pre-fill year selector

### Step 5: Test & Deploy (30 min)
- Test year pre-filling
- Test manual override
- Test year changes
- Deploy to production

**Total Time: ~1.5 hours**

---

## Code Changes Needed

### 1. New API Endpoint (routes/web.php)
```php
Route::get('/api/exam-years/active', ...);  // 10 lines
```

### 2. Registration Page (candidates.blade.php)
```javascript
async setDefaultExamYear() { ... }  // 12 lines
await this.setDefaultExamYear();    // 1 line
```

### 3. Bulk Import Modal (candidates.blade.php)
```javascript
this.importExamYear = data.active_year.year_label;  // In modal init
```

### 4. Mark Entry Page (index.blade.php)
```javascript
async setDefaultExamYear() { ... }  // 12 lines
await this.setDefaultExamYear();    // 1 line
```

**Total: ~40 lines of code**

---

## User Workflow After Implementation

### Scenario 1: Normal Operation (Active Year 2026)

**Registration:**
```
Admin sets: Active Year = 2026
User goes to /registration
  → Exam Year field ALREADY FILLED with "2026"
  → User fills other fields
  → User can change year if needed
  → Candidate registered for 2026
```

**Bulk Import:**
```
Admin sets: Active Year = 2026
User goes to /registration
  → Clicks "Import CSV"
  → Modal shows Exam Year = "2026" (pre-filled)
  → User can change if needed
  → Clicks Select File
  → Candidates imported for 2026
```

**Mark Entry:**
```
Admin sets: Active Year = 2026
User goes to /mark-entry/acsee
  → Year selector shows "2026" (pre-filled)
  → User can change if needed
  → Candidates for 2026 displayed
```

### Scenario 2: Year Transition

**Old Workflow:**
```
Exam 2025 ends, need to start 2026
  → Admin must tell users: "Now use year 2026"
  → Users must remember to select 2026 every time
  → Some users forget and register for 2025
  → Data integrity issues
```

**New Workflow:**
```
Exam 2025 ends, need to start 2026
  → Admin clicks "Activate" for 2026 in admin panel
  → All users automatically see 2026 pre-filled
  → No manual communication needed
  → No data integrity issues
```

---

## Database/Model Changes

**NONE REQUIRED** ✅

The `ExamYear` model already has:
- `is_active` boolean field
- `activate()` method
- `active()` scope
- `current()` static method

Just need to leverage existing functionality!

---

## Testing Checklist

- [ ] Admin can set active exam year
- [ ] Active year shows in API endpoint
- [ ] Registration form pre-fills with active year
- [ ] User can override active year if needed
- [ ] Bulk import modal pre-fills with active year
- [ ] Mark entry pre-fills with active year
- [ ] Year change affects all modules
- [ ] No data integrity issues
- [ ] Mobile view works correctly

---

## Deployment Plan

### Before Deployment
1. Code review
2. Test in staging
3. Verify year pre-filling works
4. Test year override functionality

### Deployment
1. Deploy code to production
2. Set active exam year in admin
3. Verify all pages show correct year
4. Monitor for issues

### Post-Deployment
1. Document for admins
2. Update user guides
3. Monitor error logs
4. Gather user feedback

---

## Admin Documentation

**For System Administrators:**

1. **Setting the Active Year**
   - Go to Admin → System Settings → Exam Years
   - Click "Activate" button next to desired year
   - System automatically deactivates other years
   - All pages now use the active year

2. **Changing the Year**
   - Simply click "Activate" on a different year
   - All users immediately see the new year
   - No manual notifications needed

3. **Viewing Current Year**
   - Check dashboard widget (shows "Active Year: 2026")
   - Or check System Settings → Exam Years
   - Active year shown with ✓ icon

---

## Success Criteria

✅ Active exam year visible to all users  
✅ All forms pre-filled with active year  
✅ Users can still override if needed  
✅ No manual year selection required for normal workflow  
✅ Admin can change year easily  
✅ All data uses consistent year context  
✅ No breaking changes to existing functionality  

---

## Questions & Answers

**Q: What if user selects different year than active?**  
A: That's allowed! Active year is just the default. Users can override.

**Q: What if no active year is set?**  
A: Show current/today's year, or show error to admin to set active year.

**Q: Will this break existing code?**  
A: No, we're only adding defaults. All existing logic still works.

**Q: Can admins change active year anytime?**  
A: Yes, but typically once per exam cycle.

**Q: What about locked years?**  
A: Can't activate locked year. Active year can be locked (read-only mode).

---

## Summary

Create a global exam year system that:
1. Stores active year in ExamYear.is_active
2. Provides API endpoint to retrieve active year
3. Pre-fills forms with active year on page load
4. Allows manual override if needed
5. Makes year management simple for admins

**Result: Better UX, fewer mistakes, easier administration**

---

## Next Steps

1. Review this plan
2. Approve implementation
3. Execute step-by-step
4. Test thoroughly
5. Deploy to production
6. Document for admins
