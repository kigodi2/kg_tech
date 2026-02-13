# Bulk Import Controller - Improvements & Reimplement

## Changes Made

### 1. **Preview Endpoint - Better Error Handling**

**File:** `app/Http/Controllers/BulkImportController.php` - `preview()` method

#### Issues Fixed:
- **MIME Type Validation Too Strict**: Changed from `mimes:zip` to basic file validation, then check extension manually
- **Poor Error Messages**: Now distinguishes between different failure points
- **File Storage Errors Not Caught**: Added try-catch around file storage
- **Preview Generation Failures**: Added try-catch around preview service call
- **Stack Traces Missing**: Logs now include full stack traces for debugging

#### Key Changes:
```php
// Before: Strict MIME type check could fail
$request->validate([
    'zip_file' => 'required|file|mimes:zip',
]);

// After: Extension-based validation (more reliable)
$extension = strtolower($zipFile->getClientOriginalExtension());
if ($extension !== 'zip') {
    return response()->json([...], 422);
}
```

#### New Error Handling:
- File storage failures now return 500 with descriptive message
- ZIP structure analysis failures return 500 with error details
- Validation errors return 422 with specific validation errors
- All exceptions logged with full stack traces

### 2. **Start Import Endpoint - Improved Robustness**

**File:** `app/Http/Controllers/BulkImportController.php` - `startImport()` method

#### Issues Fixed:
- **Combined Session/File Checks**: Now checks separately with specific error messages
- **Lost File Not Detected**: Added file_exists() check after session retrieval
- **Orchestrator Errors Hidden**: Wrapped orchestrator call to catch and log failures
- **Validation Exception Not Handled**: Added explicit ValidationException handler
- **No Request Parameter Validation**: Now stores validated data in variable

#### Key Changes:
```php
// Better separation of concerns
if (!$zipPath) {
    return response()->json([...], 422); // No file uploaded
}

if (!file_exists($zipPath)) {
    session()->forget('bulk_import_temp_zip');
    return response()->json([...], 422); // File lost
}

// Wrap orchestrator with error handling
try {
    $bulkImport = $this->orchestrator->startImport(...);
} catch (\Exception $e) {
    Log::error('Orchestrator error: ' . $e->getMessage());
    throw $e;
}
```

### 3. **District Import Endpoint - Same Improvements**

**File:** `app/Http/Controllers/BulkImportController.php` - `startDistrictImport()` method

Applied same improvements as `startImport()`:
- Better error separation
- Orchestrator error handling
- Explicit exception handling
- Improved logging

## Frontend Integration

The JavaScript code in `resources/views/mark-entry/index.blade.php` already correctly:
1. Validates file selection
2. Sends FormData properly
3. Handles JSON responses
4. Shows user messages

### Example Flow:
```javascript
// 1. User selects ZIP file
handleZipSelect(event) {
    const file = event.target.files[0];
    const formData = new FormData();
    formData.append('zip_file', file);
    
    // 2. Send to preview endpoint
    fetch('/api/bulk-import/preview', {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': ... }
    });
}

// 3. User clicks "Start Import"
startBulkImport() {
    const examYearId = this.examYears.find(...)?.id;
    fetch('/api/bulk-import/start', {
        method: 'POST',
        body: JSON.stringify({
            school_id: this.selectedSchool,
            exam_year_id: examYearId,
        }),
        headers: { 'X-CSRF-TOKEN': ... }
    });
}
```

## Testing Steps

1. **Clear browser cache** and refresh the page
2. **Upload a ZIP file**
   - If ZIP is invalid, error message shows specific validation error
   - If ZIP is valid, preview displays contents
3. **Click "Start Import"**
   - Validation error shows if school/year not selected
   - Success message shows if import started

## Error Messages Now Returned

### Preview Endpoint (POST /api/bulk-import/preview)
- `422`: File is not ZIP, or ZIP structure invalid with specific errors
- `500`: File storage failed, ZIP corrupted, or preview generation failed
- `200`: Success with preview data

### Start Import Endpoint (POST /api/bulk-import/start)
- `422`: Validation failed (school/exam_year invalid or ZIP not uploaded)
- `403`: No permission to import for this school
- `500`: Orchestrator error or other server error
- `200`: Success with bulk_import_id

## Logging Improvements

All errors now logged with:
- Clear error message
- Full exception stack trace
- Relevant context data

Check logs at: `storage/logs/laravel.log`

## Files Modified

- `app/Http/Controllers/BulkImportController.php`
  - `preview()` method (lines 47-130)
  - `startImport()` method (lines 136-239)
  - `startDistrictImport()` method (lines 261-334)

## Related Issues Fixed

This also fixed the earlier `exam_year_id: 1` hardcoding issue by ensuring proper validation of exam year IDs in the controller.
