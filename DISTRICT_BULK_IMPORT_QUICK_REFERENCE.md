# District Bulk Import - Quick Reference

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/MarkImport/DistrictBulkImportOrchestrator.php` | Main orchestration for district imports |
| `app/Services/MarkImport/DistrictManifestValidator.php` | Validates manifest schema |
| `app/Services/MarkImport/DistrictImportRecoveryService.php` | Retry/recovery logic |
| `app/Services/MarkImport/ZipPreviewService.php` | Preview ZIP before import |
| `app/Jobs/ProcessBulkImportSchool.php` | Per-school job processor |
| `app/Jobs/ProcessBulkImportFile.php` | Per-subject CSV processor |
| `app/Http/Controllers/BulkImportController.php` | API endpoints |
| `app/Policies/BulkImportPolicy.php` | Authorization |
| `app/Models/BulkImport.php` | Model with scope methods |
| `database/migrations/2026_02_01_*.php` | Schema migrations |

## Core Classes

### DistrictBulkImportOrchestrator
```php
public function startImport(string $zipPath, int $districtId, int $examYearId): BulkImport
public function getProgress(int $bulkImportId): array
public function markSchoolComplete(int $bulkImportId, int $schoolId, string $status, array $stats): void
public function cleanup(int $bulkImportId): void
```

### DistrictImportRecoveryService
```php
public function retrySchool(int $bulkImportId, int $schoolId): bool
public function retryAllFailedSchools(int $bulkImportId): int
public function getRecoveryStatus(int $bulkImportId): array
```

### BulkImport Model
```php
public function isDistrictImport(): bool
public function isSchoolImport(): bool
public function getProgressPercentage(): int
public function getSummary(): array
public function schools(): BelongsToMany
public function district(): BelongsTo
```

## API Usage Examples

### 1. Upload and Preview ZIP
```javascript
const form = new FormData();
form.append('zip_file', zipFile);

const preview = await fetch('/api/bulk-import/preview', {
    method: 'POST',
    body: form
}).then(r => r.json());

if (!preview.success) {
    console.error('Validation failed:', preview.errors);
}
```

### 2. Start District Import
```javascript
const response = await fetch('/api/bulk-import/district/start', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        district_id: 1,
        exam_year_id: 2025
    })
}).then(r => r.json());

const bulkImportId = response.bulk_import_id;
```

### 3. Monitor Progress
```javascript
const progress = await fetch(`/api/bulk-import/${bulkImportId}/progress`)
    .then(r => r.json());

console.log(`Progress: ${progress.progress.progress_percentage}%`);
progress.progress.schools.forEach(school => {
    console.log(`${school.school_code}: ${school.status}`);
});
```

### 4. Check Recovery Status
```javascript
const recovery = await fetch(`/api/bulk-import/${bulkImportId}/recovery-status`)
    .then(r => r.json());

if (recovery.recovery_status.can_retry_all) {
    console.log('Failed schools:', recovery.recovery_status.failed_schools.length);
}
```

### 5. Retry Failed School
```javascript
const response = await fetch(`/api/bulk-import/${bulkImportId}/retry-school`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ school_id: 123 })
}).then(r => r.json());
```

### 6. Retry All Failed Schools
```javascript
const response = await fetch(`/api/bulk-import/${bulkImportId}/retry-all`, {
    method: 'POST'
}).then(r => r.json());

console.log(`Retrying ${response.schools_retried} schools`);
```

## Manifest Structure (Quick)

```json
{
  "exam": "ACSEE",
  "exam_year": 2025,
  "scope": {
    "type": "district",
    "code": "IRINGA_M"
  },
  "generated_at": "2025-03-15T10:45:00Z",
  "generated_by": {
    "user_id": 14,
    "role": "district_officer"
  },
  "schools": [
    {
      "school_code": "S0203",
      "school_name": "IRINGA GIRLS",
      "candidates": 2140,
      "subjects": [
        {
          "code": "PHY",
          "papers": ["P1", "P2"],
          "candidates": 2140,
          "checksum": "sha256:..."
        }
      ]
    }
  ],
  "zip_checksum": "sha256:...",
  "signature": {
    "algorithm": "HMAC-SHA256",
    "value": "base64...",
    "signed_at": "2025-03-15T10:45:00Z",
    "signed_by": 14
  }
}
```

## Statuses at a Glance

### Import Statuses
- `validating` → ZIP/manifest validation in progress
- `importing` → Schools being processed
- `partial` → Some schools succeeded, others failed
- `completed` → All schools succeeded
- `failed` → All schools failed

### School Statuses (in bulk_import_schools pivot)
- `pending` → Not yet processed
- `processing` → Currently running
- `success` → All subjects completed
- `partial` → Some subjects completed
- `failed` → All subjects failed

## Common Scenarios

### Scenario 1: Import Succeeds
1. User uploads ZIP
2. DistrictBulkImportOrchestrator validates
3. Creates BulkImport (status=validating)
4. Registers schools in pivot table
5. Dispatches ProcessBulkImportSchool jobs
6. Each school processes subjects in parallel
7. All schools complete → status=completed

### Scenario 2: One School Fails
1. School A fails to process (e.g., missing CSV)
2. School B,C,D continue processing
3. BulkImport status becomes 'partial'
4. User can retry School A
5. School A reruns with new job

### Scenario 3: One Subject in School Fails
1. School A processes 3 subjects: PHY, ENG, BIO
2. BIO CSV has syntax error
3. PHY and ENG complete successfully
4. BIO fails
5. School status = 'partial'
6. Parent import status = 'partial'
7. User retries entire school (all 3 subjects reset)

## Error Handling

### In Code
```php
try {
    $bulkImport = $orchestrator->startImport($zipPath, $districtId, $examYearId);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Manifest validation errors
    $errors = $e->errors(); // Keys like "scope.code", "schools.0.subjects"
} catch (\Exception $e) {
    // ZIP read errors, extraction errors, etc.
    Log::error('Import failed: ' . $e->getMessage());
}
```

### In API
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "scope.code": "Manifest scope code (MBEYA_M) does not match district code (IRINGA_M)",
    "schools.0.school_code": "School S9999 not found in district IRINGA_M"
  }
}
```

## Authorization Quick Check

```php
// School Officer
→ Can import for own school only
→ Cannot create district imports

// District Officer
→ Can import for own district
→ Can see district imports
→ Cannot import for other districts

// Regional Officer
→ Can import for districts in own region
→ Can see all imports in region

// Admin
→ Can import for any school/district
→ Can view all imports
```

## Database Queries

### Find all district imports for a district
```php
BulkImport::where('district_id', $districtId)
    ->where('scope_type', 'district')
    ->with('schools', 'examYear')
    ->get();
```

### Find failed schools in an import
```php
$bulkImport->schools()
    ->wherePivotIn('status', ['failed', 'partial'])
    ->get();
```

### Get summary counts
```php
$summary = $bulkImport->getSummary();
// Returns: total_schools, processed_schools, successful_schools, 
//          partial_schools, failed_schools, total_candidates, etc.
```

## Logging

Audit log channel captures:
- Import start/completion
- School processing events
- Signature verification
- Retry attempts
- Recovery actions

Check logs at:
```
storage/logs/audit.log
```

## Performance Tips

1. **Chunk Processing**: CSV rows processed in 500-row chunks
2. **Job Timeout**: Set to 1 hour per school (`ProcessBulkImportSchool::$timeout = 3600`)
3. **Max Retries**: 3 attempts per job
4. **Parallel Processing**: Schools process in parallel (via queue)
5. **Temp Cleanup**: Extraction directory auto-deleted after import completes

## Debugging

### Check import status
```php
$import = BulkImport::with('schools')->find($id);
dd($import->status, $import->schools->pluck('pivot.status'));
```

### Check errors
```php
$school = $import->schools()->first();
echo $school->pivot->error_summary; // Get detailed error
```

### Check extracted files
```
storage/app/temp/imports/{bulk_import_id}/
```

### Check queue jobs
```bash
php artisan queue:work --stop-when-empty
```

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| "School X not found in district" | School code mismatch | Verify manifest school codes match database |
| "CSV file not found" | Directory structure wrong | Check ZIP subdirs match pattern `<SCHOOL_CODE>_<NAME>/` |
| "Manifest exam_year mismatch" | Wrong year in manifest | Regenerate manifest with correct year |
| "School fails silently" | CSV encoding issue | Ensure CSV is UTF-8 without BOM |
| "Partial import stuck" | Queue not processing | Check queue driver, restart worker |
