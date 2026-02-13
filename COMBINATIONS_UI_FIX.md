# Combinations Page UI and Edit Button Fix

## Issues Fixed

### 1. Combination Column Not Centered
**Problem**: The "Combination" and "SN" columns in the combinations table were aligned to the left instead of being centered.

**Solution**: Added `text-center` class to both header and data cells for the SN and Combination columns.

**Changes**:
- SN header: `text-left` → `text-center`
- Combination header: `text-left` → `text-center`
- SN cell: added `text-center` class
- Combination cell: added `text-center` class
- Actions header: `text-left` → `text-center`
- Actions cell: added `flex justify-center` to center the action buttons

### 2. Edit Button Not Responding
**Problem**: Clicking the Edit button in the combinations table didn't open the edit modal.

**Root Cause**: The `editCombination()` function was trying to extract subject IDs from `combination.subjects` by mapping it as an array of objects (`combination.subjects?.map(s => s.id)`). However, the API returns `subjects` as a comma-separated string (e.g., "BIO001, PHY001, CHE001"), not an array of subject objects.

**Solution**: Updated the `editCombination()` function to handle both formats:
1. If `subjects` is an array of objects → extract IDs directly
2. If `subjects` is a string → parse it, find matching subjects from `allSubjects`, and extract their IDs

**Before**:
```javascript
editCombination(combination) {
    this.editingCombinationId = combination.id;
    this.combinationForm = { 
        code: combination.code,
        category: combination.category || 'ARTS',
        description: combination.description || '',
        subject_ids: combination.subjects?.map(s => s.id) || []  // ❌ Fails with string
    };
    this.subjects = this.allSubjects || [];
    this.showCombinationModal = true;
}
```

**After**:
```javascript
editCombination(combination) {
    this.editingCombinationId = combination.id;
    
    // Extract subject IDs from the combination's subjects
    let subjectIds = [];
    if (combination.subjects && Array.isArray(combination.subjects)) {
        // If subjects is an array of objects
        subjectIds = combination.subjects.map(s => s.id);
    } else if (combination.subjects && typeof combination.subjects === 'string') {
        // If subjects is a string, we need to find matching subjects from allSubjects
        const subjectCodes = combination.subjects.split(',').map(s => s.trim());
        subjectIds = this.allSubjects
            .filter(s => subjectCodes.includes(s.code))
            .map(s => s.id);
    }
    
    this.combinationForm = { 
        code: combination.code,
        category: combination.category || 'ARTS',
        description: combination.description || '',
        subject_ids: subjectIds
    };
    this.subjects = this.allSubjects || [];
    this.showCombinationModal = true;
}
```

## File Modified
- `resources/views/exam-types/show.blade.php`
  - Lines 200-201: Centered SN and Combination headers
  - Line 203: Centered Actions header
  - Lines 209-210: Centered SN and Combination cells
  - Line 212: Added flexbox centering to Actions cell
  - Lines 1463-1488: Updated editCombination() function to handle string subjects

## Testing Checklist
- [x] SN column is centered
- [x] Combination column is centered
- [x] Actions buttons are centered
- [x] Edit button opens the modal
- [x] Modal is pre-populated with existing combination data
- [x] Subjects are correctly selected in the modal
- [x] Can update and save combination

## Result
- All combinations table columns are properly centered
- Edit button now functions correctly
- Users can edit combinations without errors
