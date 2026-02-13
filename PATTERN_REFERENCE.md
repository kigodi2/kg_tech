# Candidates Implementation - Pattern Reference Guide

## Quick Reference: Schools vs Candidates Implementation

This document provides a side-by-side comparison of key patterns for easy reference.

---

## 1. Modal State Management

### Pattern: Using editingId to determine Add vs Edit mode

```javascript
// SCHOOLS
data: {
    editingId: null,           // null = Add mode, set = Edit mode
    modalOpen: false,          // Controls form modal visibility
    viewModalOpen: false,      // Controls view-only modal visibility
}

// CANDIDATES (SAME PATTERN)
data: {
    editingId: null,           // null = Add mode, set = Edit mode
    modalOpen: false,          // Controls form modal visibility
    viewModalOpen: false,      // Controls view-only modal visibility
}
```

### HTML Template Logic
```html
<h2>
    <span x-show="!viewModalOpen && !editingId">Add New Item</span>
    <span x-show="!viewModalOpen && editingId">Edit Item</span>
    <span x-show="viewModalOpen && !editingId">View Item</span>
</h2>

<button x-show="!editingId">Add Item</button>
<button x-show="editingId">Update Item</button>
```

---

## 2. Opening Add Modal

### SCHOOLS Pattern
```javascript
openAddModal() {
    this.editingId = null;                    // Clear edit mode
    this.viewModalOpen = false;               // Close view modal
    this.formData = { code: '', name: '', ... };
    this.districtsByRegion = [];              // Clear related data
    this.modalOpen = true;                    // Open form modal
    this.$nextTick(() => {                    // After DOM update
        const codeInput = document.querySelector('input[placeholder="e.g., SCH001"]');
        if (codeInput) codeInput.focus();     // Auto-focus first field
    });
}
```

### CANDIDATES Pattern (IDENTICAL)
```javascript
openAddModal() {
    this.editingId = null;                    // Clear edit mode
    this.viewModalOpen = false;               // Close view modal
    this.formData = { first_name: '', last_name: '', ... };
    this.modalOpen = true;                    // Open form modal
    this.$nextTick(() => {                    // After DOM update
        const firstNameInput = document.querySelector('input[x-model="formData.first_name"]');
        if (firstNameInput) firstNameInput.focus();  // Auto-focus first field
    });
}
```

### Key Pattern Points
✅ Clear editingId (set to null)
✅ Close viewModalOpen
✅ Reset formData
✅ Use $nextTick for focus management
✅ Focus on first input field

---

## 3. Opening View Modal

### SCHOOLS Pattern
```javascript
viewSchool(school) {
    this.viewingSchool = { ...school };      // Copy entire object
    this.editingId = null;                    // Ensure not in edit mode
    this.viewModalOpen = true;                // Open view modal
}
```

### CANDIDATES Pattern (IDENTICAL)
```javascript
viewCandidate(candidate) {
    this.viewingCandidate = { ...candidate }; // Copy entire object
    this.editingId = null;                    // Ensure not in edit mode
    this.viewModalOpen = true;                // Open view modal
}
```

### Key Pattern Points
✅ Copy object with spread operator `{ ...object }`
✅ Clear editingId
✅ Set viewModalOpen to true

---

## 4. Opening Edit Modal

### SCHOOLS Pattern
```javascript
openEditModal(school) {
    this.editingId = school.id;               // Set ID for update detection
    this.formData = {                         // Explicit field mapping
        code: school.code,
        name: school.name,
        ownership: school.ownership || '',
        region_id: school.region_id,
        district_id: school.district_id
    };
    this.loadDistrictsByRegion();             // Load related data
    this.modalOpen = true;                    // Open form modal
}
```

### CANDIDATES Pattern (ALIGNED)
```javascript
openEditModal(candidate) {
    this.editingId = candidate.id;            // Set ID for update detection
    this.viewModalOpen = false;               // Close view modal
    this.formData = {                         // Explicit field mapping
        first_name: candidate.first_name,
        last_name: candidate.last_name,
        email: candidate.email,
        school_id: candidate.school_id,
        exam_type: candidate.exam_type || ''
    };
    this.modalOpen = true;                    // Open form modal
}
```

### Key Pattern Points
✅ Set editingId to item.id
✅ Close viewModalOpen before opening form
✅ Explicitly map only needed fields
✅ Use defaults for optional fields
✅ Load related data if needed
✅ Open modalOpen

---

## 5. Saving (POST/PUT)

### SCHOOLS Pattern
```javascript
async saveSchool() {
    try {
        const url = this.editingId ? `/api/schools/${this.editingId}` : '/api/schools';
        const method = this.editingId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(this.formData),
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage(
                this.editingId ? 'School updated successfully' : 'School added successfully',
                'success'
            );
            this.modalOpen = false;
            await this.loadSchools();
        } else {
            this.showMessage(data.message || 'Error saving school', 'error');
        }
    } catch (error) {
        console.error('Error saving school:', error);
        this.showMessage('Error saving school', 'error');
    }
}
```

### CANDIDATES Pattern (IDENTICAL)
```javascript
async saveCandidate() {
    try {
        const url = this.editingId ? `/api/candidates/${this.editingId}` : '/api/candidates';
        const method = this.editingId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(this.formData),
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage(
                this.editingId ? 'Candidate updated successfully' : 'Candidate registered successfully',
                'success'
            );
            this.modalOpen = false;
            await this.loadCandidates();
        } else {
            this.showMessage(data.message || 'Error saving candidate', 'error');
        }
    } catch (error) {
        console.error('Error saving candidate:', error);
        this.showMessage('Error saving candidate', 'error');
    }
}
```

### Key Pattern Points
✅ Determine URL and method based on editingId
✅ Include CSRF token in headers
✅ Send as JSON with Content-Type header
✅ Close modal on success
✅ Reload data on success
✅ Display appropriate message (Add vs Update)
✅ Handle errors with try-catch
✅ Show error message from server

---

## 6. Deleting (Single Item)

### SCHOOLS Pattern
```javascript
async deleteSchool(id) {
    if (!confirm('Are you sure you want to delete this school?')) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const token = csrfToken ? csrfToken.content : '';
        
        const response = await fetch(`/api/schools/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
        });

        let data = {};
        try {
            data = await response.json();
        } catch (e) {
            console.log('Response text:', await response.text());
        }
        
        if (response.ok || response.status === 200) {
            this.showMessage('School deleted successfully', 'success');
            await this.loadSchools();
        } else {
            this.showMessage(data.message || `Error deleting school (Status: ${response.status})`, 'error');
        }
    } catch (error) {
        console.error('Error deleting school:', error);
        this.showMessage('Error deleting school: ' + error.message, 'error');
    }
}
```

### CANDIDATES Pattern (IDENTICAL)
```javascript
async deleteCandidate(id) {
    if (!confirm('Are you sure you want to delete this candidate?')) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const token = csrfToken ? csrfToken.content : '';
        
        const response = await fetch(`/api/candidates/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
        });

        let data = {};
        try {
            data = await response.json();
        } catch (e) {
            console.log('Response text:', await response.text());
        }
        
        if (response.ok || response.status === 200) {
            this.showMessage('Candidate deleted successfully', 'success');
            await this.loadCandidates();
        } else {
            this.showMessage(data.message || `Error deleting candidate (Status: ${response.status})`, 'error');
        }
    } catch (error) {
        console.error('Error deleting candidate:', error);
        this.showMessage('Error deleting candidate: ' + error.message, 'error');
    }
}
```

### Key Pattern Points
✅ Confirmation dialog before delete
✅ Safe CSRF token retrieval (null check)
✅ Include Content-Type header
✅ Safe JSON parsing (try-catch)
✅ Check response.ok or status === 200
✅ Detailed error messages with status codes
✅ Reload data on success

---

## 7. Bulk Delete

### SCHOOLS Pattern
```javascript
async bulkDeleteSchools() {
    if (this.selectedItems.size === 0) return;
    
    const count = this.selectedItems.size;
    if (!confirm(`Are you sure you want to delete ${count} school(s)? This action cannot be undone.`)) return;

    try {
        const ids = Array.from(this.selectedItems);
        const response = await fetch('/api/schools/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ ids }),
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage(`${data.deleted} school(s) deleted successfully`, 'success');
            this.selectedItems.clear();
            await this.loadSchools();
        } else {
            this.showMessage(data.message || 'Error deleting schools', 'error');
        }
    } catch (error) {
        console.error('Error deleting schools:', error);
        this.showMessage('Error deleting schools', 'error');
    }
}
```

### CANDIDATES Pattern (ALIGNED - Using Bulk Endpoint)
```javascript
async bulkDelete() {
    if (this.selectedCandidates.length === 0) return;
    
    const count = this.selectedCandidates.length;
    if (!confirm(`Are you sure you want to delete ${count} candidate(s)? This action cannot be undone.`)) return;

    try {
        const ids = this.selectedCandidates;
        const response = await fetch('/api/candidates/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ ids }),
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage(`${data.deleted} candidate(s) deleted successfully`, 'success');
            this.selectedCandidates = [];
            await this.loadCandidates();
        } else {
            this.showMessage(data.message || 'Error deleting candidates', 'error');
        }
    } catch (error) {
        console.error('Error deleting candidates:', error);
        this.showMessage('Error deleting candidates', 'error');
    }
}
```

### Key Pattern Points
✅ Check if any items selected
✅ Confirmation with specific count
✅ Use bulk-delete endpoint (efficient)
✅ Send ids array in JSON body
✅ Clear selection after success
✅ Display deleted count in message
✅ Reload data

---

## 8. CSV Export

### SCHOOLS Pattern
```javascript
exportCSV() {
    const headers = ['Code', 'Name', 'Ownership', 'District', 'Region'].join(',');
    const rows = this.filteredSchools.map(s => 
        [s.code, s.name, s.ownership || '', s.district_name || '', s.region_name || '']
            .map(v => `"${v}"`)
            .join(',')
    );
    const csv = [headers, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `schools_${Date.now()}.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    this.showMessage('Exported successfully', 'success');
}
```

### CANDIDATES Pattern (IDENTICAL)
```javascript
exportCSV() {
    const headers = ['ID', 'First Name', 'Last Name', 'Email', 'School', 'Exam Type'].join(',');
    const rows = this.filteredCandidates.map(c => 
        [c.candidate_id, c.first_name, c.last_name, c.email, c.school_name || '', c.exam_type || '']
            .map(v => `"${v}"`)
            .join(',')
    );
    const csv = [headers, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `candidates_${Date.now()}.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    this.showMessage('Exported successfully', 'success');
}
```

### Key Pattern Points
✅ Define headers array
✅ Map rows with proper quoting
✅ Join with newlines
✅ Create blob with CSV type
✅ Use Blob API for download
✅ Include timestamp in filename
✅ Clean up URL object
✅ Show success message

---

## 9. Error Handling Pattern

### Safe CSRF Token Retrieval
```javascript
// ❌ UNSAFE - Will crash if token not found
const token = document.querySelector('meta[name="csrf-token"]').content;

// ✅ SAFE - Handles missing token
const csrfToken = document.querySelector('meta[name="csrf-token"]');
const token = csrfToken ? csrfToken.content : '';
```

### Safe JSON Parsing
```javascript
// ❌ UNSAFE - Will crash on invalid JSON
const data = await response.json();

// ✅ SAFE - Fallback to empty object
let data = {};
try {
    data = await response.json();
} catch (e) {
    console.log('Response text:', await response.text());
}
```

### Status Code Checking
```javascript
// ❌ INCOMPLETE - Only checks ok flag
if (response.ok) { ... }

// ✅ COMPREHENSIVE - Checks both ok and status
if (response.ok || response.status === 200) { ... }
```

---

## 10. Notification System

### Pattern
```javascript
showMessage(message, type) {
    const alertDiv = document.createElement('div');
    const bgClass = type === 'success' 
        ? 'bg-green-100 text-green-700 border-green-300' 
        : 'bg-red-100 text-red-700 border-red-300';
    
    alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
    alertDiv.textContent = message;
    alertDiv.style.wordWrap = 'break-word';
    
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}

// Usage
this.showMessage('Operation successful', 'success');
this.showMessage('Error occurred', 'error');
```

### Key Pattern Points
✅ Dynamic background classes based on type
✅ Fixed position (top-24 right-8)
✅ Max-width for readability
✅ Shadow styling
✅ Word wrap for long messages
✅ Auto-dismiss after 4 seconds
✅ Proper z-index (z-50)

---

## Summary: Core Pattern

All CRUD operations in both Schools and Candidates follow this pattern:

```javascript
// 1. STATE MANAGEMENT
data: {
    editingId: null,        // null = Add, number = Edit
    modalOpen: false,       // Edit/Add modal
    viewModalOpen: false,   // View-only modal
    formData: {},           // Form values
    items: [],              // List of items
}

// 2. OPERATIONS
function openAddModal()         // Clear state, open form
function viewItem(item)         // Copy object, open view
function openEditModal(item)    // Populate form, open form
async function saveItem()       // POST/PUT based on editingId
async function deleteItem(id)   // DELETE with confirmation
async function bulkDelete()     // POST to bulk-delete endpoint

// 3. HELPERS
filterItems()                   // Reload from API with filters
showMessage(msg, type)          // Toast notification
loadItems()                     // Fetch from API

// 4. RESPONSE HANDLING
if (response.ok) {
    this.showMessage('Success message', 'success');
    this.modalOpen = false;
    await this.loadItems();
} else {
    this.showMessage(data.message || 'Error message', 'error');
}
```

This pattern ensures consistency, maintainability, and predictable behavior across all CRUD modules.

