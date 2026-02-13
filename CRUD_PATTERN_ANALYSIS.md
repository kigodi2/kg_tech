# CRUD Pattern Analysis: Schools vs Candidates

## Executive Summary
The Schools Management page uses a unified modal-based CRUD pattern that needs to be applied to Candidates.

---

## 1. SCHOOLS CRUD PATTERN

### 1.1 Modal Structure

```html
<!-- Single modal for all modes: View, Add, Edit -->
<div x-show="modalOpen || viewModalOpen">
    <!-- Header with conditional titles -->
    <h2>
        <span x-show="viewModalOpen && !editingId">School Details</span>
        <span x-show="!viewModalOpen && !editingId">Add New School</span>
        <span x-show="!viewModalOpen && editingId">Edit School</span>
    </h2>
    
    <!-- View Mode (read-only) -->
    <div x-show="viewModalOpen">
        <!-- Display fields -->
    </div>
    
    <!-- Add/Edit Mode (form) -->
    <form x-show="!viewModalOpen" @submit.prevent="saveSchool()">
        <!-- Editable fields -->
    </form>
</div>
```

### 1.2 State Management
```javascript
modalOpen: false              // Controls overlay visibility
viewModalOpen: false          // Controls view vs form mode
editingId: null              // null = Add, number = Edit
formData: {                  // Form field values
    code: '',
    name: '',
    ownership: '',
    region_id: '',
    district_id: ''
}
viewingSchool: {}            // School data in view mode
```

### 1.3 CRUD Operations

#### ADD: openAddModal()
```javascript
openAddModal() {
    this.editingId = null;                    // Clear edit mode
    this.viewModalOpen = false;               // Show form
    this.formData = { code: '', name: '', ... };  // Reset fields
    this.modalOpen = true;                    // Open modal
    this.$nextTick(() => {
        document.querySelector('input[placeholder="e.g., SCH001"]').focus();
    });
}
```

**Flow:**
- User clicks "Add School" button
- openAddModal() called → editingId = null, viewModalOpen = false
- Form displays with empty fields
- First field auto-focused

---

#### VIEW: viewSchool(school)
```javascript
viewSchool(school) {
    this.viewingSchool = { ...school };      // Copy data
    this.editingId = null;                    // Ensure not editing
    this.viewModalOpen = true;                // Show view mode
}
```

**Flow:**
- User clicks eye icon
- viewSchool() called → viewModalOpen = true
- Read-only fields display with data
- Shows "Close" and "Edit" buttons

---

#### EDIT: openEditModal(school)
```javascript
openEditModal(school) {
    this.editingId = school.id;              // Set ID for update
    this.formData = {                        // Pre-fill form
        code: school.code,
        name: school.name,
        ...
    };
    this.loadDistrictsByRegion();            // Load related data
    this.modalOpen = true;                   // Open modal
}
```

**Flow:**
- From View → Click "Edit" OR Direct click edit icon
- openEditModal() called → editingId = id, viewModalOpen = false
- Form displays with pre-filled data
- User can modify fields

---

#### SAVE: saveSchool() (POST/PUT)
```javascript
async saveSchool() {
    const url = this.editingId ? `/api/schools/${this.editingId}` : '/api/schools';
    const method = this.editingId ? 'PUT' : 'POST';
    
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify(this.formData),
    });
    
    if (response.ok) {
        this.showMessage(
            this.editingId ? 'School updated successfully' : 'School added successfully',
            'success'
        );
        this.modalOpen = false;
        await this.loadSchools();
    }
}
```

**Flow:**
- User fills form and clicks "Add School" or "Edit School"
- @submit.prevent="saveSchool()" triggered
- Determines POST (add) or PUT (edit) based on editingId
- Sends to `/api/schools` or `/api/schools/{id}`
- Closes modal and reloads data on success

---

#### DELETE: deleteSchool(id) (DELETE)
```javascript
async deleteSchool(id) {
    if (!confirm('Are you sure...')) return;
    
    const response = await fetch(`/api/schools/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
        },
    });
    
    if (response.ok) {
        this.showMessage('School deleted successfully', 'success');
        await this.loadSchools();
    }
}
```

**Flow:**
- User clicks trash icon
- Confirmation dialog appears
- DELETE request sent to `/api/schools/{id}`
- Modal closes and data reloads

---

### 1.4 Modal State Transitions

```
                    ┌─────────────────┐
                    │    CLOSED       │
                    │  (no modals)    │
                    └────────┬────────┘
                             │
                    click "Add School"
                             │
                    ┌────────▼────────┐
                    │   ADD MODE      │
    ┌──────────────►│editingId = null │◄─────────────────┐
    │               │modalOpen = true │                  │
    │               │viewModalOpen = false               │
    │               └────────┬────────┘                  │
    │                        │                          │
    │              click Save or Cancel                │
    │                        │                          │
    │        ┌───────────────┘                         │
    │        │                                         │
    │        ▼                                         │
    │   ┌─────────────┐                           click "Edit"
    │   │   CLOSED    │                                 │
    │   └─────────────┘                                 │
    │                                                   │
    │        click eye icon                    ┌────────┴────────┐
    │             │                            │                │
    │             ▼                            │                │
    │    ┌─────────────────┐      click "Edit"│                │
    │    │  VIEW MODE      │◄───────────────────┤                │
    └────│viewModalOpen=true│                    │                │
         │editingId = null │                    │                │
         └─────────────────┘                    │                │
                  │                             │                │
                  └─────────────────────────────┘                │
                                                                │
                                     ┌──────────────────────────┴───┐
                                     │                              │
                                     ▼                              │
                                ┌─────────────────┐                │
                                │  EDIT MODE      │                │
                                │editingId = id   │◄───────────────┘
                                │modalOpen = true │
                                │viewModalOpen=false
                                └─────────────────┘
```

---

## 2. DIFFERENCES FROM CANDIDATES (Current)

| Aspect | Schools | Candidates | Issue |
|--------|---------|-----------|-------|
| Modal Header | x-show spans | x-text ternary | ✓ Candidates fixed |
| View Mode | x-show div | x-show div | ✓ Matching |
| Edit Mode | x-show form | x-show form | ✓ Matching |
| State Variables | Proper setup | Proper setup | ✓ Matching |
| Focus Management | $nextTick | $nextTick | ✓ Matching |
| Modal Close | Both flags reset | Both flags reset | ✓ Matching |

---

## 3. CANDIDATES IMPLEMENTATION CHECKLIST

### Modal Structure
- [x] Single modal for View/Add/Edit
- [x] Header with conditional titles
- [x] View mode (read-only)
- [x] Form mode (editable)
- [x] Proper x-show conditions

### State Management
- [x] modalOpen (overlay control)
- [x] viewModalOpen (view vs form)
- [x] editingId (null = add, id = edit)
- [x] formData (form values)
- [x] viewingCandidate (view data)

### CRUD Operations
- [x] openAddModal() - Clear state, open form
- [x] viewCandidate() - Show read-only view
- [x] openEditModal() - Populate and open form
- [x] saveCandidate() - POST/PUT logic
- [x] deleteCandidate() - Confirmation + DELETE

### API Integration
- [x] POST /api/candidates (Create)
- [x] PUT /api/candidates/{id} (Update)
- [x] DELETE /api/candidates/{id} (Delete)
- [x] Response handling (success/error)

### UX Details
- [x] Auto-focus first field
- [x] Confirmation dialogs
- [x] Success/error messages
- [x] Modal closes on success
- [x] Data reloads after operations

---

## 4. KEY IMPLEMENTATION RULES

### Rule 1: Modal States
```javascript
// Add Mode
editingId = null
viewModalOpen = false
modalOpen = true

// View Mode
editingId = null
viewModalOpen = true
modalOpen = true

// Edit Mode
editingId = school.id
viewModalOpen = false
modalOpen = true

// Closed
editingId = null
viewModalOpen = false
modalOpen = false
```

### Rule 2: Form Data Mapping
Always explicitly map fields, never spread:
```javascript
// ❌ WRONG
this.formData = { ...candidate };

// ✅ CORRECT
this.formData = {
    first_name: candidate.first_name,
    last_name: candidate.last_name,
    email: candidate.email,
    school_id: candidate.school_id,
    exam_type: candidate.exam_type || ''
};
```

### Rule 3: API Calls
```javascript
// Determine method and URL
const url = editingId ? `/api/candidates/${editingId}` : '/api/candidates';
const method = editingId ? 'PUT' : 'POST';

// Always include CSRF token
headers: { 'X-CSRF-TOKEN': token }
```

### Rule 4: Response Handling
```javascript
if (response.ok) {
    // Show success
    // Close modal
    // Reload data
} else {
    // Show error from server
}
```

### Rule 5: Modal Close
Always reset BOTH flags:
```javascript
@click="modalOpen = false; viewModalOpen = false;"
```

---

## 5. SUMMARY

The Schools CRUD pattern is clean and battle-tested:
1. **Single unified modal** for all three modes (View/Add/Edit)
2. **Clear state management** with editingId and viewModalOpen
3. **Proper data flow**: Load → View → Edit → Save
4. **Robust error handling** with user feedback
5. **Clean UX** with auto-focus and confirmations

**Candidates should mirror this exactly.**

