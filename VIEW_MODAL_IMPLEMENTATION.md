# View Modal Implementation for Actions

## Overview
Replaced simple message popups with proper view modals for all "View" actions on the exam-types/acsee page.

## Changes Made

### 1. Subject View Modal
**File:** `resources/views/exam-types/show.blade.php`

**State Variables Added:**
- `showSubjectViewModal: false` - Controls modal visibility
- `viewingSubject: {}` - Stores the subject being viewed

**Method Updated:**
```javascript
viewSubject(subject) {
    this.viewingSubject = subject;
    this.showSubjectViewModal = true;
}
```

**Modal Features:**
- Displays: Code, Name, Category, Written Papers, Description
- Read-only fields
- Close button and Edit button
- Z-index: `z-[9998]`

---

### 2. Combination View Modal
**File:** `resources/views/exam-types/show.blade.php`

**State Variables Added:**
- `showCombinationViewModal: false` - Controls modal visibility
- `viewingCombination: {}` - Stores the combination being viewed

**Method Updated:**
```javascript
viewCombination(combination) {
    this.viewingCombination = combination;
    this.showCombinationViewModal = true;
}
```

**Modal Features:**
- Displays: Code, Subjects
- Read-only fields
- Close button and Edit button
- Z-index: `z-[9998]`

---

### 3. Candidate View Modal
**Already Implemented** - No changes needed

**Existing Features:**
- `candidateViewModalOpen` state variable
- Displays: Index Number, Full Name, Sex, Combination, School
- Close button and Edit button

---

## Z-Index Hierarchy (Updated)
1. **Candidate Modal** - `z-[9995]`
2. **Combination Modal** - `z-[9996]`
3. **Subject Modal** - `z-[9997]`
4. **All View Modals** - `z-[9998]` (highest, displays on top during view)

This ensures view modals always appear on top of edit modals.

---

## User Flow

### Subject View
1. Click View icon (eye) on subject row
2. Subject View Modal appears with read-only details
3. Click "Edit" button to switch to edit modal
4. Click "Close" button or click outside to close modal

### Combination View
1. Click View icon (eye) on combination row
2. Combination View Modal appears with read-only details
3. Click "Edit" button to switch to edit modal
4. Click "Close" button or click outside to close modal

### Candidate View
1. Click View icon (eye) on candidate row
2. Candidate View Modal appears with read-only details
3. Click "Edit" button to switch to edit modal
4. Click "Close" button or click outside to close modal

---

## Benefits
✓ Consistent UI for viewing details across all entities
✓ Easy transition from View to Edit mode
✓ Read-only presentation prevents accidental modifications
✓ Professional modal-based UI replaces simple message popups
✓ Proper z-index management prevents modal conflicts

## Testing Checklist
- [ ] Click View icon on Subject - modal opens with correct data
- [ ] Click Edit from Subject view modal - switches to edit modal
- [ ] Click View icon on Combination - modal opens with correct data
- [ ] Click Edit from Combination view modal - switches to edit modal
- [ ] View icon on Candidate (already implemented) - works as expected
- [ ] Close buttons work on all view modals
- [ ] Clicking backdrop closes view modals
- [ ] View modals appear on top of other modals
