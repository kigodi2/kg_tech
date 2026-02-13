# District Bulk Import Quick Start

## 5-Minute Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Update AuthServiceProvider
Add to `app/Providers/AuthServiceProvider.php`:
```php
use App\Models\BulkImport;
use App\Policies\BulkImportPolicy;

public function boot()
{
    $this->registerPolicies();
    
    Gate::policy(BulkImport::class, BulkImportPolicy::class);
}
```

### 3. Verify Services Are Registered
In `config/app.php` providers array (usually auto-discovered):
- DistrictBulkImportOrchestrator
- DistrictManifestValidator

## ZIP Structure

```
DISTRICT_IRINGA_M_2025.zip
├── manifest.json
├── S0203_IRINGA_GIRLS/
│   ├── PHY.csv
│   ├── MAT.csv
│   └── ENG.csv
└── S0205_MBEYA_GIRLS/
    ├── PHY.csv
    └── BIO.csv
```

## manifest.json Template

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
    "user_id": 1,
    "role": "district_officer"
  },
  "schools": [
    {
      "school_code": "S0203",
      "school_name": "IRINGA GIRLS",
      "total_candidates": 2140,
      "subjects": [
        {
          "code": "PHY",
          "papers": ["P1", "P2"],
          "candidates": 350,
          "checksum": "sha256:abc123..."
        }
      ]
    }
  ],
  "zip_checksum": "sha256:abc123..."
}
```

## API Usage

### Step 1: Preview ZIP
```bash
curl -X POST http://localhost:8000/api/bulk-import/preview \
  -F "zip_file=@DISTRICT_IRINGA_M_2025.zip"

# Response:
{
  "success": true,
  "preview": {
    "valid": true,
    "exam": "ACSEE",
    "exam_year": 2025,
    "total_schools": 2,
    "total_subjects": 6,
    "total_candidates": 4200
  }
}
```

### Step 2: Start Import
```bash
curl -X POST http://localhost:8000/api/bulk-import/district/start \
  -H "Content-Type: application/json" \
  -d '{
    "district_id": 5,
    "exam_year_id": 12
  }'

# Response:
{
  "success": true,
  "bulk_import_id": 42,
  "message": "District-level bulk import started"
}
```

### Step 3: Monitor Progress
```bash
curl http://localhost:8000/api/bulk-import/42/progress

# Response:
{
  "success": true,
  "progress": {
    "status": "importing",
    "progress_percentage": 45,
    "schools": [
      {
        "school_code": "S0203",
        "status": "success",
        "total_candidates": 2140,
        "successful_candidates": 2140
      }
    ]
  }
}
```

## Key Differences: School vs District

| Feature | School Import | District Import |
|---------|---------------|-----------------|
| **Endpoint** | `/bulk-import/start` | `/bulk-import/district/start` |
| **Parameter** | `school_id` | `district_id` |
| **Scope** | Single school | Multiple schools |
| **Status Tracking** | Per file | Per school + per file |
| **Authorization** | School Officer | District Officer |
| **ZIP Structure** | Single level | School folders |

## Common Errors

### Error: "School X not found in district Y"
**Cause**: School code in manifest doesn't exist or belongs to different district  
**Fix**: Verify school codes in manifest match database

### Error: "Manifest exam_year does not match selected exam year"
**Cause**: ZIP created for different year  
**Fix**: Ensure exam_year in manifest matches selected exam_year_id

### Error: "Invalid role for generated_by"
**Cause**: User role not district_officer, regional_officer, or admin  
**Fix**: Update role in manifest to valid value

### Error: "You do not have permission to import for this district"
**Cause**: User lacks authorization  
**Fix**: Verify user role and district assignment

## Permissions Quick Reference

```
District Officer:
  ✅ Upload for own district
  ❌ Cannot upload for other districts
  ❌ Cannot upload school-level imports

Regional Officer:
  ✅ Upload for districts in region
  ✅ Upload for schools in region

Admin:
  ✅ Upload for any district
  ✅ Upload for any school
```

## Monitoring Job Queue

```bash
# Check pending jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry

# Monitor queue in real-time
php artisan queue:work --verbose
```

## Database Queries

### Get district import by ID
```php
$import = BulkImport::with('district', 'schools', 'examYear')
    ->where('scope_type', 'district')
    ->find(42);

// Access schools and their status
foreach ($import->schools as $school) {
    echo $school->pivot->school_code . ': ' . $school->pivot->status;
}
```

### Get all failed schools in import
```php
$failedSchools = $import->schools()
    ->wherePivot('status', 'failed')
    ->get();
```

### Get import summary
```php
$summary = $import->getSummary();
echo "Total candidates: {$summary['total_candidates']}";
echo "Successful: {$summary['successful_candidates']}";
echo "Failed: {$summary['failed_candidates']}";
```

## Logging

All district imports logged to `storage/logs/audit.log`:

```
[2025-03-15 10:45:00] District Bulk Import Started
bulk_import_id: 42
district_id: 5
district_code: IRINGA_M
exam_year_id: 12
total_schools: 3
total_files: 9
user_id: 1
timestamp: 2025-03-15T10:45:00Z
ip_address: 192.168.1.1
```

## Testing Workflow

1. **Prepare test ZIP** with 2-3 schools
2. **Upload and preview** to verify structure
3. **Start import** and monitor progress
4. **Verify database** for correct data
5. **Test failure** by including bad CSV
6. **Verify partial completion** works

## Performance Tips

1. **Chunk size**: 300-500 rows per database batch (configured in ProcessBulkImportFile)
2. **Queue workers**: Run 2-4 workers for parallel school processing
3. **Timeout**: Each school job has 1-hour timeout
4. **Memory**: ~50MB per concurrent import

## Cleanup

Temporary files auto-cleaned after import completes or fails:
```
storage/app/temp/imports/{bulk_import_id}/
  ├── manifest.json
  ├── S0203_IRINGA_GIRLS/
  │   ├── PHY.csv
  │   └── ...
  └── ...
```

To manually clean:
```php
$orchestrator = app(DistrictBulkImportOrchestrator::class);
$orchestrator->cleanup($bulkImportId);
```

## Next Steps

1. ✅ Run migrations
2. ✅ Register policy
3. ✅ Test with sample ZIP
4. ✅ Monitor first production import
5. ✅ Document district officer procedures
6. ✅ Train support team on error recovery
