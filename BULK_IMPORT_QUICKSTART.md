# Bulk Import - Quick Start Guide

## What Was Fixed

The bulk ZIP import feature had **3 main issues** that have all been fixed:

1. **Hardcoded exam year ID** → Now uses selected exam year dynamically
2. **Strict MIME type validation** → Now uses file extension validation
3. **Poor error handling** → Now provides clear, specific error messages

---

## How to Use

### Step 1: Go to Mark Entry Page
```
http://127.0.0.1:8000/mark-entry/acsee
```

### Step 2: Select Context
- **Year:** 2026 (or your current exam year)
- **Region:** All Regions or select specific region
- **District:** All Districts or select specific district  
- **School:** Select your school (e.g., KLERUU TEACHERS COLLEGE)

### Step 3: Upload ZIP File
- Click on "School Bulk ZIP" tab
- Fill in:
  - **Exam Year:** 2026
  - **School:** KLERUU TEACHERS COLLEGE
- Click the **ZIP upload area**
- Select your ZIP file
- Wait for preview

### Step 4: Verify Preview
- You should see:
  - School name
  - Exam year
  - List of subjects with candidate counts
  - **"✓ Valid" status**

If you see errors:
- Check ZIP file integrity
- Check manifest.json is in the ZIP
- See **Troubleshooting** section

### Step 5: Start Import
- Click **"Start Import"** button
- You should see:
  - **"Import started"** message
  - Import progress indicator
  - Job queue status

---

## What's New in This Version

### Better Error Messages

**Before:**
```
Error 422: Unprocessable Content
```

**After:**
```
✓ "File must be a ZIP archive (*.zip)"
✓ "manifest.json is not valid JSON"
✓ "No ZIP file uploaded. Please upload a ZIP file first."
✓ "Uploaded ZIP file was lost. Please upload again."
```

### Better Logging

All errors now logged with full details:
```bash
tail -f storage/logs/laravel.log | grep -i "preview\|import"
```

---

## Common Errors & Fixes

### Error: "422 Unprocessable Content"
**Cause:** ZIP file upload failed
**Fix:**
1. Check file ends in `.zip`
2. Try uploading again
3. Use a smaller test file first

### Error: "manifest.json not found in ZIP"
**Cause:** ZIP doesn't have required manifest
**Fix:**
1. Verify ZIP contains manifest.json at root level
2. Check ZIP isn't corrupted: `unzip -t file.zip`

### Error: "Invalid exam year selected"
**Cause:** Exam years didn't load in browser
**Fix:**
1. Refresh page (Ctrl+F5)
2. Check browser console (F12)
3. Verify exam year exists in system

### Error: "No ZIP file uploaded"
**Cause:** File wasn't selected before clicking "Start Import"
**Fix:**
1. Go back to "School Bulk ZIP" tab
2. Upload the ZIP file
3. Wait for preview to complete
4. Then click "Start Import"

---

## Testing Checklist

- [ ] Exam year dropdown shows "2026"
- [ ] School dropdown shows your school
- [ ] ZIP file uploads without error
- [ ] Preview shows subjects and candidate counts
- [ ] "Start Import" button is clickable
- [ ] Import status shows "Processing" or similar
- [ ] Check logs for "Import started" message

---

## File Locations

### Important Directories
```
storage/app/temp/        ← Uploaded ZIP files stored here (temporary)
storage/logs/laravel.log ← Application logs
public/                  ← Static files (CSS, JS)
```

### Important Files
```
app/Http/Controllers/BulkImportController.php      ← Import controller
resources/views/mark-entry/index.blade.php         ← Import UI
app/Services/MarkImport/ZipPreviewService.php      ← ZIP validation
app/Services/MarkImport/BulkImportOrchestrator.php ← Import orchestration
```

---

## Key Features

✅ **Dynamic Exam Year Selection**
- No longer hardcoded to ID 1
- Uses selected exam year from dropdown

✅ **Reliable File Validation**
- Extension-based checking (more reliable)
- Better error messages

✅ **Comprehensive Error Handling**
- Each error type has specific message
- Full stack traces in logs for debugging

✅ **Session Management**
- ZIP stored in session after upload
- Automatically cleared after import starts
- Clear message if session expires

✅ **Better Debugging**
- Errors logged with full context
- Separate logs for preview, start, orchestration
- Stack traces included for all exceptions

---

## Advanced Usage

### View Import Progress
```bash
# Check recent imports
php artisan tinker
>>> BulkImport::latest()->limit(5)->get()

# Check specific import
>>> BulkImport::find(123)->status
```

### Debug ZIP File
```bash
# List ZIP contents
unzip -l your_file.zip

# Validate ZIP integrity
unzip -t your_file.zip

# View manifest
unzip -p your_file.zip manifest.json | jq .
```

### Monitor Logs
```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Filter for preview errors
grep "preview failed" storage/logs/laravel.log

# Filter for import errors
grep "import failed" storage/logs/laravel.log

# Show last 100 lines
tail -100 storage/logs/laravel.log
```

---

## FAQ

**Q: How long does import take?**
A: Depends on file size and number of candidates. Check progress indicator in UI.

**Q: Can I upload multiple files?**
A: Upload one at a time. Wait for first import to complete, then upload next.

**Q: What if import fails?**
A: Check logs at `storage/logs/laravel.log` and see IMPORT_TROUBLESHOOTING_GUIDE.md

**Q: How do I know if import succeeded?**
A: Check marks are in the system and import status shows "Completed"

**Q: Can I edit marks after import?**
A: Yes, use regular mark entry after import completes

---

## Performance Tips

- **Large files (>100MB):** Split into multiple schools if possible
- **Slow upload:** Check network connection and server resources
- **ZIP creation:** Use compression level 6-7 for best balance

---

## Support

If you encounter issues:

1. Check **IMPORT_TROUBLESHOOTING_GUIDE.md** for your specific error
2. Verify exam year and school exist in system
3. Check logs: `tail -f storage/logs/laravel.log`
4. Contact admin if issue persists

---

## Related Documentation

- **IMPORT_TROUBLESHOOTING_GUIDE.md** - Detailed troubleshooting
- **BULK_IMPORT_CONTROLLER_IMPROVEMENTS.md** - Technical details
- **IMPLEMENTATION_CHECKLIST_BULK_IMPORT.md** - What was changed
