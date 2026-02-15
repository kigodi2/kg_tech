# Districts Import Modal - Implementation Plan

**Date**: 2026-02-15  
**Status**: Planning Phase  
**Scope**: Add professional import modal with two-phase validation and detailed error reporting  

---

## A) AUDIT FINDINGS

### UI Pattern (Current)
- **File**: `resources/views/registration/districts.blade.php`
- **Framework**: Alpine.js with Tailwind CSS
- **Current Features**:
  - Region filter dropdown
  - Search input
  - Tools dropdown menu (Download Template, Import CSV, Export CSV)
  - Add/Edit/View modals for CRUD
  - Basic import function via hidden file input + simple endpoint
  - Table display with pagination

### Database Schema (Districts)
- **File**: `database/migrations/2026_01_25_000000_create_districts_table.php`
- **Unique Key**: `code` (string, unique)
- **Required Fields**:
  - `code` - Auto-generated from region code + sequential number
  - `name` - District name (required)
  - `region_id` - Foreign key to regions table (required)
- **Optional Fields**:
  - `description` (nullable text)
  - `status` (string, default 'active')
  - `candidates_count` (integer, computed)

### Model (District)
- **File**: `app/Models/District.php`
- **Fillable**: code, name, region_id, description, status, candidates_count
- **Relations**: belongsTo Region, hasMany Schools, hasManyThrough Candidates
- **Scopes**: active()

### Existing Import Pattern
- **Location**: `routes/web.php` Line 234-330
- **Current Behavior**: Single-step import, auto-generates codes, updates duplicates
- **Issues**:
  - No validation report
  - No detailed error messages per field
  - No error download capability
  - No modal interface (just file input + endpoint)

### Existing Import Patterns in IRMS (Reference)
- **Schools Import Modal** (NEW): Two-phase validation, detailed errors, error report download
- **Candidates Import**: Two-phase validation (CandidateImportController + CandidateImportService)
- **Mark Entry Import**: Batch processing with transaction safety

---

## B) IMPLEMENTATION APPROACH

### Two-Phase Workflow (Consistent with Schools/Candidates)
1. **Phase 1 - Validate**: Parse file, validate all rows, return error report (no DB writes)
2. **Phase 2 - Commit**: Re-validate and write valid rows in transaction

### Error Detection Rules (Derived from Schema)

| Validation | Check Against | Error If... |
|-----------|---|---|
| Name Required | Row data | Name is empty |
| Name Length | Max 255 chars | Name exceeds length |
| Region ID Required | Row data | Region ID is empty |
| Region Exists | regions table | Region ID/code not found |
| Code Uniqueness | districts table | Code already exists in DB |
| Code Uniqueness (File) | within file | Same code appears twice |
| Code Format | auto-generation | Generated code invalid (shouldn't happen) |
| Status (optional) | enum check | If provided, must be 'active' or 'inactive' |
| Description (optional) | max length | If provided, max 500 chars |

### Special Logic
- **District Code**: Auto-generated from region_code + 2-digit sequence (inherit from existing logic)
- **Auto-assignment**: If code not in CSV, system generates it
- **Duplicate Handling**: Check by name+region_id uniqueness (matching existing business logic)

---

## C) FILES TO CREATE/MODIFY

### NEW FILES

1. **`app/Services/Districts/DistrictImportService.php`**
   - validateCSV() - Phase 1 validation
   - commitImport() - Phase 2 commit
   - Validation methods per field
   - Code generation logic

2. **`app/Http/Controllers/DistrictImportController.php`**
   - validateImport() - POST /api/districts/import/validate
   - commit() - POST /api/districts/import/commit
   - downloadTemplate() - GET /api/districts/import/template
   - downloadErrors() - POST /api/districts/import/download-errors

### UPDATED FILES

3. **`routes/api.php`**
   - Add 4 import routes under /api/districts/import prefix

4. **`resources/views/registration/districts.blade.php`**
   - Add import modal HTML (lines ~255-441, similar to schools)
   - Update Tools menu to link to modal (not old file input)
   - Add import state variables
   - Add import methods (validateImport, commitImport, downloadTemplate, downloadErrors)

---

## D) CSV FORMAT

### Required Columns
- `Name` - District name (required)
- `Region ID` - Region code or numeric ID (required)

### Optional Columns
- `Description` - District description
- `Status` - 'active' or 'inactive'

### Example
```csv
Name,Region ID,Description,Status
Dar es Salaam,TR02,,active
Arusha,AR03,Mountain region,active
Iringa,IR07,,active
```

### Notes
- `Code` is NOT imported (auto-generated)
- Region ID can be numeric ID or region code (e.g., "1" or "TR02")
- System follows existing generation logic: region_code + 2-digit sequence

---

## E) VALIDATION RULES DETAIL

### Name
- Required: Yes
- Type: String
- Max Length: 255
- Duplicates: Checked within file + within DB (by name+region)
- Error: "Name is required" / "Name exceeds 255 characters" / "District name '{name}' already exists in region"

### Region ID
- Required: Yes
- Type: Integer (ID) or String (code)
- Lookup: regions.id or regions.code
- Error: "Region ID is required" / "Region {id} not found"

### Code (Auto-generated)
- Generated from: region_code + sequential 2-digit number
- Validation: Must be unique in districts table
- Error: "Code generation failed" (rare, indicates system issue)

### Description (Optional)
- Type: String
- Max Length: 500
- Error: "Description exceeds 500 characters"

### Status (Optional)
- Type: Enum
- Valid Values: 'active', 'inactive'
- Default: 'active'
- Error: "Status must be 'active' or 'inactive'"

---

## F) ERROR REPORTING

### Error Table Display (In Modal)
- Row number (1-based)
- District name
- Region ID
- Error messages (field-level)
- Data preview

### Download Errors CSV
- Contains failed rows with all input data
- Includes error reasons per field
- User can fix and re-upload

### Error Summary
- Count of each error type
- Examples: "name_required: 5", "region_not_found: 3"

---

## G) MODAL STATES

1. **Idle**: Ready for file upload
2. **Uploading**: File being sent to server
3. **Validating**: Server validating rows
4. **Report**: Showing validation results
5. **Committing**: Importing valid rows
6. **Done**: Success screen with counts

---

## H) TECHNOLOGY STACK (Reused)

- **Backend**: PHP 7.4+, Laravel 8+, Eloquent ORM
- **Frontend**: Alpine.js, Tailwind CSS, Font Awesome
- **File Parsing**: PHP's built-in fgetcsv()
- **Database**: MySQL/SQLite, transactions
- **No new packages required**

---

## I) API ENDPOINTS

### Validate (Phase 1)
```
POST /api/districts/import/validate
Content-Type: multipart/form-data

Request: file (CSV)
Response: Validation report with errors
```

### Commit (Phase 2)
```
POST /api/districts/import/commit
Content-Type: multipart/form-data

Request: file (CSV)
Response: Import results with counts
```

### Download Template
```
GET /api/districts/import/template
Response: CSV file with headers and example rows
```

### Download Errors
```
POST /api/districts/import/download-errors
Content-Type: application/json

Request: { "errors": [...] }
Response: CSV file with failed rows
```

---

## J) SECURITY & PERMISSIONS

- Same auth gate as districts registration page
- CSRF token validation on all POST requests
- File type validation (CSV/TXT only)
- File size limit (10MB)
- Input sanitization and validation
- Database transaction safety (all-or-nothing)

---

## K) DEPLOYMENT CHECKLIST

- [ ] Create DistrictImportService
- [ ] Create DistrictImportController
- [ ] Update routes/api.php
- [ ] Update districts.blade.php with modal UI
- [ ] Add import methods to Alpine.js component
- [ ] Clear Laravel caches
- [ ] Test with valid/invalid CSV
- [ ] Test error reporting
- [ ] Test error download
- [ ] Test modal states
- [ ] Verify no N+1 queries
- [ ] Production deployment

---

## L) TIMELINE & STATUS

- Planning: ✅ COMPLETE
- Implementation: ⏳ NEXT
- Testing: ⏳ PENDING
- Deployment: ⏳ PENDING

