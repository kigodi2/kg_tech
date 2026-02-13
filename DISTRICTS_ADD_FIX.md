# District Add Modal - Error Fix

## Issue
When clicking "Add District" button and trying to save a new district, the form was returning errors.

## Root Cause
The form was submitting data with `region_id` as a string (from HTML select element), but the backend API validation expected it to be an integer. This caused validation failure.

Additionally, there was minimal error feedback to the user about what was wrong.

## Solution Applied

### Frontend Validation (`resources/views/registration/districts.blade.php`)

Updated the `saveDistrict()` function to:

1. **Validate required fields before submission**:
   - Check if region is selected
   - Check if district name is entered
   - Check if district code is populated

2. **Convert region_id to integer**:
   ```javascript
   const payload = {
       ...this.formData,
       region_id: parseInt(this.formData.region_id)
   };
   ```

3. **Improved error feedback**:
   - Show specific error messages for validation failures
   - Log error response to console for debugging
   - Display detailed error messages from API

### Changes Made

**File**: `resources/views/registration/districts.blade.php` (Lines 454-504)

**Before**:
```javascript
async saveDistrict() {
    try {
        const url = this.editingId ? `/api/districts/${this.editingId}` : '/api/districts';
        const method = this.editingId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(this.formData),  // ❌ region_id is string
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // ...
        } else {
            this.showMessage(data.message || 'Error saving district', 'error');  // Generic error
        }
    } catch (error) {
        this.showMessage('Error saving district', 'error');
    }
}
```

**After**:
```javascript
async saveDistrict() {
    try {
        // Validate required fields
        if (!this.formData.region_id) {
            this.showMessage('Please select a region', 'error');
            return;
        }
        if (!this.formData.name) {
            this.showMessage('Please enter district name', 'error');
            return;
        }
        if (!this.formData.code) {
            this.showMessage('District code is required', 'error');
            return;
        }

        const url = this.editingId ? `/api/districts/${this.editingId}` : '/api/districts';
        const method = this.editingId ? 'PUT' : 'POST';
        
        // Ensure region_id is a number
        const payload = {
            ...this.formData,
            region_id: parseInt(this.formData.region_id)  // ✓ Convert to int
        };

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        
        if (response.ok) {
            // ...
        } else {
            console.error('Error response:', data);  // ✓ Log for debugging
            this.showMessage(data.message || data.errors || 'Error saving district', 'error');  // ✓ Better errors
        }
    } catch (error) {
        console.error('Error saving district:', error);
        this.showMessage('Error saving district: ' + error.message, 'error');
    }
}
```

## API Endpoint

**Route**: `POST /api/districts`

**Request Body**:
```json
{
  "code": "IR0701",
  "name": "Iringa City",
  "region_id": 1
}
```

**Validation Rules** (in `routes/web.php` line 124-128):
- `code` - required, unique across districts
- `name` - required string
- `region_id` - required, must exist in regions table

**Response** (Success - 201):
```json
{
  "message": "District added",
  "data": {
    "id": 1,
    "code": "IR0701",
    "name": "Iringa City",
    "region_id": 1,
    "created_at": "2026-01-30T...",
    "updated_at": "2026-01-30T..."
  }
}
```

## Testing Steps

1. Click "Add District" button
2. Verify modal opens with empty fields
3. Try submitting without selecting a region - should show "Please select a region" error
4. Select a region and enter district name - code should auto-generate
5. Click "Add District" button - should succeed and show success message
6. Verify district appears in table
7. Try editing the district - should also work
8. Try deleting the district - should work

## Field Validation

| Field | Required | Auto-Generated | Format |
|-------|----------|-----------------|--------|
| Region | Yes | - | Dropdown selection |
| District Name | Yes | - | Text (e.g., "Iringa City") |
| District Code | Yes | Yes | Region code + 2-digit number (e.g., "IR01") |

## Error Messages

User will now see specific error messages:
- "Please select a region" - if no region selected
- "Please enter district name" - if name field empty
- "District code is required" - if code wasn't generated
- Backend validation errors (e.g., "code must be unique")

## Related Code

- **Modal opens**: `openAddModal()` - Line 427
- **Region change**: `generateDistrictCode()` - Line 397
- **Save submit**: `saveDistrict()` - Line 454 (Updated)
- **Backend validation**: `routes/web.php` Line 123
