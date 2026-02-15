# ACSEE Bulk CSV Import - Quick Reference Guide

**Last Updated:** February 15, 2026

---

## Quick Start for Testing

### 1. Open Bulk Import Modal
```javascript
// From browser console or UI button
acseeManager().openBulkImportModal();
```

### 2. Test Workflow
1. Click "Bulk Import CSV" button on Candidates tab
2. Download template (School or Private)
3. Fill CSV with test data
4. Select import mode (SCHOOL or PRIVATE)
5. Upload CSV file
6. Select exam year
7. Click "Validate CSV"
8. Review validation report
9. Click "Commit Import" (if no errors)
10. Verify candidates updated

---

## Function Reference

| Function | Trigger | Key States | Returns |
|----------|---------|-----------|---------|
| `applyCandidateTypeFilter()` | Dropdown change | Sets filter + mode | void |
| `downloadTemplate(type)` | Button click | None | void (downloads file) |
| `handleBulkFileUpload(event)` | File input change | Updates file state | void |
| `validateBulkCSV()` | Button click | idle → validating → reviewing | void (async) |
| `commitBulkCSV()` | Button click | reviewing → committing → complete | void (async) |
| `downloadBulkErrorReport()` | Button click | None | void (downloads CSV) |
| `resetBulkState()` | Modal open/close | Resets all bulk vars | void |
| `openBulkImportModal()` | Button click | Sets modal open | void |
| `closeBulkImportModal()` | Modal close | Sets modal closed | void |

---

## State Flow Diagram

```
                    ┌─────────────────────────────────┐
                    │   BULK IMPORT IDLE STATE        │
                    │  (Ready for user input)         │
                    └──────────┬──────────────────────┘
                               │ User clicks "Validate CSV"
                               ↓
         ┌─────────────────────────────────────┐
         │   VALIDATING                        │
         │   (Sending CSV to server)           │
         └──────────┬──────────────┬───────────┘
                    │              │
        Success     │              │    Error/Invalid
                    ↓              ↓
    ┌────────────────────┐   ┌──────────────┐
    │   REVIEWING        │   │   IDLE       │
    │   (Show report)    │   │   (Show err) │
    └────┬────────────┬──┘   └──────────────┘
         │            │
  Valid  │            │  Errors
         │            │
         ↓            ↓
    ┌────────┐   ┌──────────────┐
    │COMMIT →│   │Can't commit  │
    └────┬───┘   │(Download err)│
         │       └──────────────┘
    Success
         │
         ↓
    ┌──────────────┐
    │  COMPLETE    │
    │  (Reload UI) │
    └──────────────┘
```

---

## CSV Format

### School Mode CSV
```csv
exam_year,index_number,combination_code,replace_allocations
2026,S0001,111112,NO
2026,S0002,111123,NO
```

### Private Mode CSV
```csv
exam_year,index_number,subject_codes,replace_allocations
2026,P0001,111|112|115|119|122,NO
2026,P0002,111|112|114|117|125,NO
```

---

## Key Variables

### File Upload
- `bulkUploadedFile` - File object (null initially)
- `bulkUploadedFileName` - Name for display
- `bulkUploadedFileSize` - Size in bytes

### Mode Selection
- `bulkImportMode` - SCHOOL or PRIVATE
- `bulkExamYearId` - Selected exam year ID
- `bulkReplaceAllocations` - true/false (destructive flag)

### Phase Management
- `bulkPhase` - idle | validating | reviewing | committing | complete
- `bulkValidationReport` - { total_rows, valid_count, invalid_count, ... }
- `bulkCommitReport` - { success_count, skipped_count, failed_count, ... }

### Error Handling
- `bulkLastErrors` - Array of error objects
- `bulkErrorMessage` - User-facing error text
- `bulkSuccessMessage` - User-facing success text

---

## Common Errors & Solutions

### "Please select a CSV file"
**Cause:** File not uploaded  
**Solution:** Click file upload button and select CSV

### "Please select an exam year"
**Cause:** Exam year not selected  
**Solution:** Select exam year from dropdown

### "Validation failed"
**Cause:** CSV data validation error  
**Solution:** Check error list, download error rows, fix data

### "Please validate the CSV first"
**Cause:** Clicking commit without validation  
**Solution:** Click "Validate CSV" first

### File shows as "undefined"
**Cause:** File input not properly connected  
**Solution:** Ensure `@change="handleBulkFileUpload($event)"` is in HTML

---

## Testing Checklist

### Unit Tests
```javascript
// Test file validation
const file = new File(['test'], 'test.csv', {type: 'text/csv'});
const event = {target: {files: [file]}};
handleBulkFileUpload(event);
// Assert: bulkUploadedFile === file

// Test state reset
openBulkImportModal();
// Assert: bulkPhase === 'idle'
// Assert: bulkUploadedFile === null
```

### Integration Tests
- [ ] Upload valid school CSV → Validate succeeds
- [ ] Upload valid private CSV → Validate succeeds
- [ ] Upload invalid CSV → Validation fails with errors
- [ ] Validate → Commit → Candidates updated
- [ ] Replace allocations → Old allocations deleted
- [ ] Download error report → CSV file generated

### Edge Cases
- [ ] Very large CSV file (>10k rows)
- [ ] CSV with special characters
- [ ] CSV with missing columns
- [ ] CSV with duplicate entries
- [ ] Network failure during upload
- [ ] User cancels confirmation dialog

---

## API Response Examples

### Validation Success
```json
{
  "report": {
    "total_rows": 100,
    "valid_count": 98,
    "invalid_count": 2
  },
  "errors": [
    {
      "row_number": 5,
      "index_number": "S0001",
      "error_messages": ["Combination 'XYZ' not found"]
    }
  ]
}
```

### Commit Success
```json
{
  "report": {
    "imported": 98,
    "success_count": 98,
    "skipped_count": 0,
    "failed_count": 0,
    "affected_candidates": [
      {
        "id": 123,
        "index_number": "S0001",
        "full_name": "John Doe",
        "allocation_count": 5
      }
    ]
  }
}
```

---

## Developer Notes

### Async Operations
All API calls are async and use try-catch:
```javascript
try {
  const response = await fetch(url, {...});
  const data = await response.json();
  // Handle response
} catch (error) {
  // Handle error
}
```

### FormData for File Upload
File uploads use FormData instead of JSON:
```javascript
const formData = new FormData();
formData.append('file', this.bulkUploadedFile);
formData.append('exam_year_id', this.bulkExamYearId);
// ... send formData
```

### Blob Download
Downloaded files use blob URL pattern:
```javascript
const blob = await response.blob();
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'filename.csv';
a.click();
window.URL.revokeObjectURL(url);
```

### CSRF Protection
All POST requests include CSRF token:
```javascript
headers: {
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

---

## File Locations

- **View:** `resources/views/exam-types/acsee.blade.php`
- **Functions:** Lines 1413-1662
- **State Init:** Lines 766-805
- **Modal HTML:** Lines 307-720

---

## Related Documentation

- Implementation Summary: `ACSEE_BULK_CSV_IMPORT_IMPLEMENTATION_SUMMARY_2026_02_15.md`
- Verification Checklist: `ACSEE_BULK_IMPORT_VERIFICATION_CHECKLIST_2026_02_15.md`
- Backend Phase 2a: See related backend implementation docs
- Original Thread: T-019c61c6-757a-763a-ba51-967412e04c30

---

## Support

For issues or questions:
1. Check error messages displayed in UI
2. Check browser console for JavaScript errors
3. Check server logs for API errors
4. Review error download CSV for data issues
5. Consult implementation documentation

---

**Last Updated:** February 15, 2026  
**Status:** Ready for Use  
**Version:** 1.0
