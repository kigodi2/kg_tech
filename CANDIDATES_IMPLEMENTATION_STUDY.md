# Candidates Management - Implementation Alignment with Schools

## Summary
The Candidates Management page (`/registration/candidates`) has been fully aligned with the implementation patterns used in the Schools Management page (`/registration/schools`).

## Architecture Comparison

### 1. Data Management (Alpine.js State)

#### Schools Manager
```javascript
function schoolManager() {
    return {
        schools: [],
        filteredSchools: [],
        regions: [],
        districts: [],
        districtsByRegion: [],
        search: '',
        filterRegion: '',
        editingId: null,
        loading: false,
        modalOpen: false,
        viewModalOpen: false,
        viewingSchool: {},
        formData: { code: '', name: '', ownership: '', region_id: '', district_id: '' },
        selectedItems: new Set(),
        showToolsMenu: false,
        currentPage: 1,
        pageSize: 10,
        totalCount: 0,
        totalPages: 0,
    };
}
```

#### Candidates Manager (Aligned)
```javascript
function candidatesManager() {
    return {
        candidates: [],
        filteredCandidates: [],
        schools: [],
        search: '',
        filterSchool: '',
        editingId: null,
        selectedCandidates: [],
        formData: { first_name: '', last_name: '', email: '', school_id: '', exam_type: '' },
        loading: false,
        modalOpen: false,
        viewModalOpen: false,
        viewingCandidate: {},
        showToolsMenu: false,
    };
}
```

**Alignment Notes:**
- Both use `editingId` to track which item is being edited (null for add mode)
- Both use `modalOpen` and `viewModalOpen` for modal state management
- Both use `selectedItems/selectedCandidates` for bulk operations
- Both use `formData` object for form state
- Both use `showToolsMenu` for CSV tools dropdown

---

## 2. Data Loading & Filtering

### Schools Pattern
```javascript
async loadSchools() {
    this.loading = true;
    try {
        let url = `/api/schools?page=${this.currentPage}&page_size=${this.pageSize}&search=${this.search}`;
        if (this.filterRegion) {
            url += `&region_id=${this.filterRegion}`;
        }
        const response = await fetch(url);
        const data = await response.json();
        this.schools = data.data || [];
        this.filteredSchools = this.schools;
        this.totalCount = data.pagination.total_count;
        this.totalPages = data.pagination.total_pages;
    } catch (error) {
        console.error('Error loading schools:', error);
        this.showMessage('Error loading schools', 'error');
    } finally {
        this.loading = false;
    }
}

filterSchools() {
    this.currentPage = 1;
    this.loadSchools();
}
```

### Candidates Pattern (Aligned)
```javascript
async loadCandidates() {
    this.loading = true;
    try {
        let url = '/api/candidates?page_size=10&page=1';
        if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
        if (this.filterSchool) url += `&school_id=${this.filterSchool}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        // Map school relationship to school_name
        this.candidates = (data.data || []).map(candidate => ({
            ...candidate,
            school_name: candidate.school?.name || '-'
        }));
        this.filteredCandidates = this.candidates;
    } catch (error) {
        console.error('Error loading candidates:', error);
        this.showMessage('Error loading candidates', 'error');
    } finally {
        this.loading = false;
    }
}

filterCandidates() {
    this.loadCandidates();
}
```

---

## 3. Add, View, Edit, Delete Operations

### 3.1 Add Modal (openAddModal)

**Schools Implementation:**
```javascript
openAddModal() {
    this.editingId = null;
    this.viewModalOpen = false;
    this.formData = { code: '', name: '', ownership: '', region_id: '', district_id: '' };
    this.districtsByRegion = [];
    this.modalOpen = true;
    this.$nextTick(() => {
        const codeInput = document.querySelector('input[placeholder="e.g., SCH001"]');
        if (codeInput) codeInput.focus();
    });
}
```

**Candidates Implementation (Aligned):**
```javascript
openAddModal() {
    this.editingId = null;
    this.viewModalOpen = false;
    this.formData = { first_name: '', last_name: '', email: '', school_id: '', exam_type: '' };
    this.modalOpen = true;
    this.$nextTick(() => {
        const firstNameInput = document.querySelector('input[placeholder*="First Name"]') || 
                              document.querySelector('input[x-model="formData.first_name"]');
        if (firstNameInput) firstNameInput.focus();
    });
}
```

**Key Improvements Applied:**
- ✅ Clear viewModalOpen state
- ✅ Reset formData completely
- ✅ Use $nextTick for focus management
- ✅ Auto-focus on first input field

---

### 3.2 View Modal (viewCandidate/viewSchool)

**Pattern (Both Identical):**
```javascript
viewSchool(school) {
    this.viewingSchool = { ...school };
    this.editingId = null;
    this.viewModalOpen = true;
}

viewCandidate(candidate) {
    this.viewingCandidate = { ...candidate };
    this.editingId = null;
    this.viewModalOpen = true;
}
```

**Implementation Details:**
- Copy entire object using spread operator `{ ...object }`
- Set editingId to null to ensure we're not in edit mode
- Open the view modal
- Display read-only fields with field styling

---

### 3.3 Edit Modal (openEditModal)

**Schools Implementation:**
```javascript
openEditModal(school) {
    this.editingId = school.id;
    this.formData = { 
        code: school.code, 
        name: school.name, 
        ownership: school.ownership || '',
        region_id: school.region_id,
        district_id: school.district_id
    };
    this.loadDistrictsByRegion();
    this.modalOpen = true;
}
```

**Candidates Implementation (Aligned):**
```javascript
openEditModal(candidate) {
    this.editingId = candidate.id;
    this.viewModalOpen = false;
    this.formData = { 
        first_name: candidate.first_name,
        last_name: candidate.last_name,
        email: candidate.email,
        school_id: candidate.school_id,
        exam_type: candidate.exam_type || ''
    };
    this.modalOpen = true;
}
```

**Key Improvements Applied:**
- ✅ Close viewModalOpen before opening edit form
- ✅ Explicitly map only needed fields (prevents spreading extra fields)
- ✅ Use default values for optional fields
- ✅ Set editingId to the item's ID

---

### 3.4 Save Operation (saveSchool/saveCandidate)

**Pattern (Both Follow Same Flow):**
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

**Decision Logic:**
- If `editingId` is set: Send PUT request to `/api/{resource}/{id}`
- If `editingId` is null: Send POST request to `/api/{resource}`
- On success: Close modal and reload data
- On error: Display error message

---

### 3.5 Delete Single Item (deleteSchool/deleteCandidate)

**Schools Implementation:**
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

**Candidates Implementation (Aligned):**
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

**Key Improvements Applied:**
- ✅ Confirmation dialog before deletion
- ✅ Safe CSRF token retrieval
- ✅ Safe JSON parsing with fallback
- ✅ Status code checking (200 or ok)
- ✅ Detailed error messages with status codes

---

## 4. Bulk Operations

### 4.1 Toggle Selection (toggleSelect/toggleSelectAll)

**Pattern (Both Identical):**
```javascript
toggleSelect(id) {
    const index = this.selectedCandidates.indexOf(id);
    if (index > -1) {
        this.selectedCandidates.splice(index, 1);
    } else {
        this.selectedCandidates.push(id);
    }
}

toggleSelectAll(event) {
    if (event.target.checked) {
        this.selectedCandidates = this.filteredCandidates.map(c => c.id);
    } else {
        this.selectedCandidates = [];
    }
}
```

---

### 4.2 Bulk Delete

**Schools Implementation (Using Bulk Endpoint):**
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

**Candidates Implementation (Aligned - Using Bulk Endpoint):**
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

**Key Improvements Applied:**
- ✅ Use dedicated bulk-delete endpoint (more efficient than individual deletes)
- ✅ Confirmation dialog with specific count
- ✅ Clear selection after successful delete
- ✅ Display number of deleted items in success message
- ✅ Reload data after deletion

---

## 5. CSV Operations

### 5.1 Download Template

**Pattern (Both Identical):**
```javascript
downloadTemplate() {
    const headers = ['First Name', 'Last Name', 'Email', 'School ID', 'Exam Type'].join(',');
    const csv = headers;
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `candidates_template_${Date.now()}.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    this.showMessage('Template downloaded', 'success');
}
```

---

### 5.2 Export CSV

**Pattern (Both Identical):**
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

---

### 5.3 Import CSV

**Schools Implementation:**
```javascript
async importCSV(event) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch('/api/schools/import', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage('Imported successfully', 'success');
            await this.loadSchools();
        } else {
            this.showMessage(data.message || 'Error importing', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        this.showMessage('Error importing', 'error');
    }
}
```

**Candidates Implementation (Aligned):**
```javascript
async importCSV(event) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch('/api/candidates/import', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();
        
        if (response.ok) {
            this.showMessage('Candidates imported successfully', 'success');
            await this.loadCandidates();
        } else {
            this.showMessage(data.message || 'Error importing', 'error');
        }
    } catch (error) {
        console.error('Error importing candidates:', error);
        this.showMessage('Error importing', 'error');
    }
    
    // Reset the file input
    event.target.value = '';
}
```

**Key Improvements Applied:**
- ✅ Reset file input after import (allows re-importing same file)
- ✅ Better error message handling
- ✅ Consistent error logging

---

## 6. Notification System (showMessage)

**Schools Implementation:**
```javascript
showMessage(message, type) {
    const alertDiv = document.createElement('div');
    const bgClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
    
    alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
    alertDiv.textContent = message;
    alertDiv.style.wordWrap = 'break-word';
    
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}
```

**Candidates Implementation (Aligned):**
```javascript
showMessage(message, type) {
    const alertDiv = document.createElement('div');
    const bgClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
    
    alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
    alertDiv.textContent = message;
    alertDiv.style.wordWrap = 'break-word';
    
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}
```

**Key Improvements Applied:**
- ✅ Position at `top-24` (below header)
- ✅ Shadow styling for depth
- ✅ Word wrap for long messages
- ✅ 4-second timeout instead of 3
- ✅ Max-width of `max-w-sm` for readability

---

## 7. Modal UI Consistency

### Modal Structure (Both Identical)
```html
<!-- Modal Container -->
<div 
    x-show="modalOpen || viewModalOpen" 
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4"
    style="display: none;"
    @click.self="modalOpen = false; viewModalOpen = false;"
    x-transition
>
    <!-- Modal Content -->
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full" x-transition>
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-800">...</h2>
            <!-- Close Button -->
            <button 
                @click="modalOpen = false; viewModalOpen = false;" 
                class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
            >
                &times;
            </button>
        </div>

        <!-- View Mode -->
        <div x-show="viewModalOpen" class="p-4 space-y-2">...</div>

        <!-- Edit/Add Mode -->
        <form x-show="!viewModalOpen" @submit.prevent="save()" class="p-6 space-y-4">...</form>
    </div>
</div>
```

### Form Fields Styling (Both Aligned)
```html
<input 
    x-model="formData.field"
    type="text" 
    placeholder="e.g., ..."
    required
    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
>
```

### Button Styling (Both Aligned)
```html
<!-- Cancel Button -->
<button 
    type="button" 
    @click="modalOpen = false" 
    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition-colors font-medium"
>
    Cancel
</button>

<!-- Submit Button -->
<button 
    type="submit" 
    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors font-medium"
>
    <span x-show="!editingId">Add/Register</span>
    <span x-show="editingId">Update</span>
</button>
```

---

## Summary of Alignments

| Feature | Schools | Candidates | Status |
|---------|---------|-----------|--------|
| Data State Management | ✅ | ✅ | Aligned |
| Data Loading with Filters | ✅ | ✅ | Aligned |
| Add Modal with Focus | ✅ | ✅ | Aligned |
| View Modal | ✅ | ✅ | Aligned |
| Edit Modal | ✅ | ✅ | Aligned |
| Save (POST/PUT) | ✅ | ✅ | Aligned |
| Delete Single Item | ✅ | ✅ | Aligned |
| Bulk Delete via API | ✅ | ✅ | Aligned |
| CSV Download Template | ✅ | ✅ | Aligned |
| CSV Export | ✅ | ✅ | Aligned |
| CSV Import | ✅ | ✅ | Aligned |
| Error Handling | ✅ | ✅ | Aligned |
| Notification System | ✅ | ✅ | Aligned |
| Modal UI | ✅ | ✅ | Aligned |
| Button Styling | ✅ | ✅ | Aligned |
| Form Fields Styling | ✅ | ✅ | Aligned |

---

## Testing Checklist

- [ ] Add new candidate
- [ ] View candidate details (read-only)
- [ ] Edit existing candidate
- [ ] Delete single candidate with confirmation
- [ ] Filter candidates by school
- [ ] Search candidates by name/email
- [ ] Select/deselect individual candidates
- [ ] Select all candidates
- [ ] Bulk delete multiple candidates
- [ ] Download CSV template
- [ ] Export filtered candidates to CSV
- [ ] Import candidates from CSV
- [ ] Verify error messages display correctly
- [ ] Verify success messages display correctly
- [ ] Verify focus on first field when adding
- [ ] Verify modal closes after save
- [ ] Verify data reloads after CRUD operations

