# ACSEE Marks Import - Code Examples & Integration Guide

## API Integration Examples

### 1. Download CSV Template

**Request:**
```javascript
// Frontend code
fetch('/api/mark-entry/download-template', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('token')
    },
    body: JSON.stringify({
        exam_year: 2024,
        school_id: 5,
        subject_id: 12
    })
})
.then(response => response.blob())
.then(blob => {
    // Create download link
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'LUGALO_SECONDARY_SCHOOL_PHY.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
})
.catch(error => {
    alert('Error: ' + error.message);
});
```

**Response:**
```
Content-Type: text/csv
Content-Disposition: attachment; filename="LUGALO_SECONDARY_SCHOOL_PHY.csv"

index_number,sex,paper_p1,paper_p2,paper_p3
S000001,M,,
S000002,F,,
S000003,M,,
...
```

### 2. Upload Marks with Integrity Verification

**Request:**
```javascript
const formData = new FormData();
formData.append('exam_year', 2024);
formData.append('school_id', 5);
formData.append('subject_id', 12);
formData.append('file', fileInput.files[0]);

fetch('/api/mark-entry/upload-marks', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('token')
    },
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log(`Imported ${data.validation.valid} records`);
        console.log(`Locked ${data.locking.locked_count} rows`);
    } else {
        alert('Upload failed: ' + data.message);
    }
});
```

**Success Response:**
```json
{
    "success": true,
    "batch_id": 42,
    "batch_code": "BATCH-5-12-2024-202402011530",
    "message": "45 records imported",
    "validation": {
        "valid": 45,
        "invalid": 0,
        "total": 45
    },
    "locking": {
        "locked_count": 45,
        "unlocked_count": 0
    }
}
```

**Error Response (Integrity Check Failed):**
```json
{
    "success": false,
    "message": "Uploaded CSV does not match the generated template or has been modified. Please ensure you are using the correct template and have not added or removed candidates."
}
```

### 3. Get Batch Locking Status

**Request:**
```javascript
fetch('/api/mark-entry/batches/42/locking-status', {
    headers: {
        'Authorization': 'Bearer ' + localStorage.getItem('token')
    }
})
.then(response => response.json())
.then(data => {
    console.log(`Locked: ${data.data.locked_rows}/${data.data.total_rows} (${data.data.lock_percentage}%)`);
});
```

**Response:**
```json
{
    "success": true,
    "data": {
        "batch_id": 42,
        "batch_code": "BATCH-5-12-2024-202402011530",
        "total_rows": 45,
        "locked_rows": 45,
        "unlocked_rows": 0,
        "lock_percentage": 100,
        "all_locked": true,
        "fully_unlocked": false
    }
}
```

### 4. Unlock Rows (Restricted Operation)

**Request - Unlock Entire Batch:**
```javascript
fetch('/api/mark-entry/batches/42/unlock-rows', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('token')
    },
    body: JSON.stringify({
        reason: 'Correcting candidate data - approved by Principal on 2024-02-01'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert(`Unlocked ${data.data.unlocked_count} rows`);
    }
});
```

**Request - Unlock Specific Row:**
```javascript
fetch('/api/mark-entry/rows/245/unlock', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('token')
    },
    body: JSON.stringify({
        reason: 'Data entry error in paper 1'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Row unlocked');
    }
});
```

**Response:**
```json
{
    "success": true,
    "message": "Successfully unlocked 45 rows",
    "data": {
        "unlocked_count": 45,
        "failed_count": 0,
        "errors": []
    }
}
```

---

## Service Usage Examples

### Using AcseeMarkTemplateService

```php
use App\Services\MarkImport\AcseeMarkTemplateService;

class ExampleController extends Controller
{
    private AcseeMarkTemplateService $templateService;

    public function __construct(AcseeMarkTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function generateTemplateExample()
    {
        $examYear = 2024;
        $schoolId = 5;
        $subjectId = 12;

        // Generate CSV
        $csv = $this->templateService->generateTemplate($examYear, $schoolId, $subjectId);

        // Generate filename
        $filename = $this->templateService->generateFilename($schoolId, $subjectId);
        // Result: "LUGALO_SECONDARY_SCHOOL_PHY.csv"

        // Get eligible candidate count
        $count = $this->templateService->getEligibleCandidateCount($examYear, $schoolId, $subjectId);
        // Result: 45

        // Get candidate index numbers (for checksum)
        $indexNumbers = $this->templateService->getEligibleCandidateIndexNumbers($examYear, $schoolId, $subjectId);
        // Result: ['S000001', 'S000002', ..., 'S000045']

        // Get subject paper structure
        $structure = $this->templateService->getSubjectPaperStructure($subjectId);
        // Result: [
        //     'written_papers' => 3,
        //     'has_practical' => false,
        //     'has_project' => false,
        //     'code' => 'PHY',
        //     'name' => 'Physics'
        // ]

        return response()->streamDownload(
            fn() => print($csv),
            $filename
        );
    }
}
```

### Using CsvIntegrityService

```php
use App\Services\MarkImport\CsvIntegrityService;
use App\Models\MarkImportBatch;
use Illuminate\Http\UploadedFile;

class IntegrityExampleController extends Controller
{
    private CsvIntegrityService $integrityService;

    public function __construct(CsvIntegrityService $integrityService)
    {
        $this->integrityService = $integrityService;
    }

    public function example()
    {
        $batch = MarkImportBatch::find(42);
        $file = request()->file('csv');

        // Generate and store checksum when creating batch
        $checksum = $this->integrityService->generateAndStoreChecksum(
            examYear: 2024,
            schoolId: 5,
            subjectId: 12,
            batch: $batch
        );

        // Verify uploaded CSV
        $result = $this->integrityService->verifyUploadedCSV(
            batch: $batch,
            file: $file,
            examYear: 2024,
            schoolId: 5,
            subjectId: 12
        );

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 422);
        }

        // Get checksum info
        $info = $this->integrityService->getChecksumInfo($batch);
        // Result: [
        //     'checksum' => '8a3c5f2e...',
        //     'full_checksum' => '8a3c5f2e...(full hash)',
        //     'candidate_count' => 45,
        //     'generated_at' => '2024-02-01 15:30:45'
        // ]

        // Delete checksum when batch is deleted
        $this->integrityService->deleteChecksum($batch);
    }
}
```

### Using MarkRowLockingService

```php
use App\Services\MarkImport\MarkRowLockingService;
use App\Models\MarkImportBatch;
use App\Models\RawMark;

class LockingExampleController extends Controller
{
    private MarkRowLockingService $lockingService;

    public function __construct(MarkRowLockingService $lockingService)
    {
        $this->lockingService = $lockingService;
    }

    public function examples()
    {
        $batch = MarkImportBatch::find(42);
        $userId = auth()->id();

        // Lock all rows in batch
        $result = $this->lockingService->lockBatchRows($batch, $userId);
        if ($result['success']) {
            echo "Locked {$result['locked_count']} rows";
        }

        // Lock specific rows
        $result = $this->lockingService->lockSpecificRows([1, 2, 3], $userId);
        // Result: ['success' => true, 'locked_count' => 3, ...]

        // Unlock batch rows (restricted)
        $result = $this->lockingService->unlockBatchRows(
            batch: $batch,
            userId: $userId,
            reason: 'Correcting candidate data - approved by Principal'
        );
        if ($result['success']) {
            echo "Unlocked {$result['unlocked_count']} rows";
        }

        // Unlock specific row
        $result = $this->lockingService->unlockSpecificRow(
            rowId: 245,
            userId: $userId,
            reason: 'Data entry error'
        );

        // Get locking status
        $status = $this->lockingService->getBatchLockingStatus($batch);
        // Result: [
        //     'batch_id' => 42,
        //     'total_rows' => 45,
        //     'locked_rows' => 45,
        //     'unlocked_rows' => 0,
        //     'lock_percentage' => 100,
        //     'all_locked' => true,
        //     'fully_unlocked' => false
        // ]

        // Check if row is locked
        $isLocked = $this->lockingService->isRowLocked(245);

        // Get counts
        $lockedCount = $this->lockingService->getLockedRowsCount($batch);
        $unlockedCount = $this->lockingService->getUnlockedRowsCount($batch);

        // Prevent updates to locked rows
        $row = RawMark::find(245);
        $this->lockingService->preventLockedRowUpdate($row);  // Throws if locked
        $this->lockingService->preventLockedRowDelete($row);  // Throws if locked
    }
}
```

---

## Model Usage Examples

### RawMark Model

```php
use App\Models\RawMark;

// Get unlocked rows
$unlockedRows = RawMark::where('mark_import_batch_id', 42)
    ->unlocked()
    ->get();

// Get locked rows
$lockedRows = RawMark::where('mark_import_batch_id', 42)
    ->locked()
    ->get();

// Lock a row
$row = RawMark::find(245);
$row->lock(auth()->id());

// Unlock a row
$row->unlock(auth()->id());

// Prevent operations on locked row
try {
    $row->preventLocked('update');
    // Can proceed with update
} catch (Exception $e) {
    echo "Row is locked: " . $e->getMessage();
}

// Get locking info
$row->is_locked;      // boolean
$row->locked_at;      // Carbon datetime
$row->lockedByUser;   // User who locked it
$row->lockedByUser->name;  // User name
```

### MarkImportBatch Model

```php
use App\Models\MarkImportBatch;

$batch = MarkImportBatch::find(42);

// Get checksum
$checksum = $batch->checksum;
echo $checksum->candidate_count;

// Get locked rows count
$locked = $batch->rawMarks()->locked()->count();

// Get unlocked rows
$unlocked = $batch->rawMarks()->unlocked()->get();

// Check if all locked
$allLocked = $batch->rawMarks()->unlocked()->doesntExist();
```

---

## Database Query Examples

### Query Locked/Unlocked Rows

```php
// Count locked rows
$lockedCount = RawMark::where('mark_import_batch_id', 42)
    ->where('is_locked', true)
    ->count();

// Get locked rows with details
$lockedRows = RawMark::where('mark_import_batch_id', 42)
    ->where('is_locked', true)
    ->with('candidate', 'lockedByUser')
    ->paginate(20);

// Get rows locked after specific date
$recentlyLocked = RawMark::where('is_locked', true)
    ->where('locked_at', '>=', '2024-02-01')
    ->get();

// Get rows locked by specific user
$rowsLockedByUser = RawMark::where('locked_by', auth()->id())
    ->where('is_locked', true)
    ->get();
```

### Query Checksums

```php
// Get checksum for batch
$checksum = MarkImportChecksum::where('mark_import_batch_id', 42)
    ->first();

// Get all checksums for a school/year
$checksums = MarkImportChecksum::whereHas('batch', function ($query) {
    $query->where('school_id', 5)
        ->where('exam_year', 2024);
})->get();

// Get candidate list from checksum
$indexNumbers = $checksum->candidate_index_numbers;
// ['S000001', 'S000002', ..., 'S000045']
```

---

## Logging Examples

### View Lock/Unlock Log

```bash
# View recent lock operations
tail -n 50 storage/logs/laravel.log | grep "locked"

# Expected output:
[2024-02-01 15:30:45] local.INFO: Batch BATCH-5-12-2024-202402011530 rows locked {"batch_id":42,"locked_count":45,"failed_count":0,"locked_by":3}

[2024-02-01 16:15:22] local.WARNING: Batch BATCH-5-12-2024-202402011530 rows unlocked {"batch_id":42,"unlocked_count":45,"failed_count":0,"unlocked_by":3,"reason":"Correcting data"}
```

### Programmatic Log Access

```php
use App\Services\MarkImport\MarkRowLockingService;

$lockingService = app(MarkRowLockingService::class);
$batch = MarkImportBatch::find(42);

// Get audit log for batch
$auditLog = $lockingService->getAuditLog($batch, limit: 50);

foreach ($auditLog as $logLine) {
    echo $logLine . "\n";
}
```

---

## Error Handling Examples

### Handling Integrity Check Failure

```php
use App\Services\MarkImport\CsvIntegrityService;

$integrityService = app(CsvIntegrityService::class);

$result = $integrityService->verifyUploadedCSV(
    $batch,
    $file,
    $examYear,
    $schoolId,
    $subjectId
);

if (!$result['valid']) {
    // Different handling based on error type
    if (str_contains($result['error'], 'does not match')) {
        // CSV modified
        return response()->json([
            'success' => false,
            'message' => 'CSV has been modified. Please use the original template.',
            'code' => 'CHECKSUM_MISMATCH'
        ], 422);
    } elseif (str_contains($result['error'], 'header structure')) {
        // Header changed
        return response()->json([
            'success' => false,
            'message' => 'CSV columns have been changed. Do not modify the header row.',
            'code' => 'HEADER_MISMATCH'
        ], 422);
    } elseif (str_contains($result['error'], 'Number of candidates')) {
        // Candidate count mismatch
        return response()->json([
            'success' => false,
            'message' => 'CSV has different number of candidates. Do not add or remove rows.',
            'code' => 'CANDIDATE_COUNT_MISMATCH'
        ], 422);
    }
}
```

### Handling Locked Row Operations

```php
use App\Services\MarkImport\MarkRowLockingService;

$lockingService = app(MarkRowLockingService::class);
$row = RawMark::find(245);

try {
    $lockingService->preventLockedRowUpdate($row);
    // Proceed with update
} catch (Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'Cannot modify locked row',
        'details' => $e->getMessage(),
        'row_id' => $row->id,
        'locked_at' => $row->locked_at,
        'locked_by_user' => $row->lockedByUser?->name
    ], 422);
}
```

---

## Testing Examples

### Unit Test: Template Generation

```php
use App\Services\MarkImport\AcseeMarkTemplateService;
use Tests\TestCase;

class TemplateGenerationTest extends TestCase
{
    public function test_template_contains_only_index_and_sex()
    {
        $service = app(AcseeMarkTemplateService::class);
        $csv = $service->generateTemplate($examYear = 2024, $schoolId = 5, $subjectId = 12);

        $lines = explode("\n", $csv);
        $headers = str_getcsv($lines[0]);

        $this->assertContains('index_number', $headers);
        $this->assertContains('sex', $headers);
        $this->assertNotContains('full_name', $headers);
        $this->assertNotContains('candidate_id', $headers);
    }

    public function test_filename_follows_convention()
    {
        $service = app(AcseeMarkTemplateService::class);
        $filename = $service->generateFilename($schoolId = 5, $subjectId = 12);

        $this->assertStringEndsWith('.csv', $filename);
        $this->assertStringNotContainsString(' ', $filename);
        $this->assertTrue(ctype_upper(strstr($filename, '_', true)));
    }
}
```

### Unit Test: Integrity Verification

```php
public function test_modified_csv_fails_verification()
{
    $batch = MarkImportBatch::create([...]);
    $integrityService = app(CsvIntegrityService::class);

    // Store checksum
    $integrityService->generateAndStoreChecksum(2024, 5, 12, $batch);

    // Create modified CSV (with extra candidate)
    $modifiedCsv = $this->createModifiedCsvFile([..., 'S999999']);

    // Verify should fail
    $result = $integrityService->verifyUploadedCSV($batch, $modifiedCsv, 2024, 5, 12);

    $this->assertFalse($result['valid']);
    $this->assertStringContainsString('does not match', $result['error']);
}
```

### Unit Test: Row Locking

```php
public function test_rows_cannot_be_updated_when_locked()
{
    $batch = MarkImportBatch::create([...]);
    $row = $batch->rawMarks()->create([...]);
    $lockingService = app(MarkRowLockingService::class);

    $lockingService->lockBatchRows($batch, auth()->id());

    $this->assertTrue($row->fresh()->is_locked);

    $this->expectException(Exception::class);
    $lockingService->preventLockedRowUpdate($row);
}
```

---

## Performance Tuning

### Database Indexes

Ensure these indexes exist for optimal performance:

```sql
-- In migration or manual SQL
ALTER TABLE `raw_marks` ADD INDEX `idx_is_locked` (`is_locked`);
ALTER TABLE `raw_marks` ADD INDEX `idx_mark_batch_locked` (`mark_import_batch_id`, `is_locked`);
ALTER TABLE `mark_import_checksums` ADD INDEX `idx_batch_checksum` (`mark_import_batch_id`, `checksum`);
```

### Query Optimization

```php
// Bad: N+1 query problem
$rows = $batch->rawMarks()->get();
foreach ($rows as $row) {
    echo $row->lockedByUser->name;  // Query per row
}

// Good: Eager loading
$rows = $batch->rawMarks()->with('lockedByUser')->get();
foreach ($rows as $row) {
    echo $row->lockedByUser->name;  // No additional queries
}
```

---

## Summary

This implementation provides three complementary features:
1. **Template Service**: Professional, minimal-exposure CSV templates
2. **Integrity Service**: Cryptographic verification of CSV authenticity
3. **Locking Service**: Row-level protection after processing

All examples above demonstrate production-ready usage patterns.
