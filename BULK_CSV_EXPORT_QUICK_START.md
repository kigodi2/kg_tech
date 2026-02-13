# Bulk CSV Export - Quick Start

## What It Does

Downloads all subject CSVs for a school + exam year as a single ZIP file with:
- One CSV per subject
- SHA-256 checksums for integrity
- Manifest.json for audit trail
- Role-based access control

## UI Location

**Mark Entry Page:** `/mark-entry/acsee`

**Button:** "Download All Subjects (ZIP)" (orange, right side)

## How to Use

1. **Select Context:**
   - Year: 2026
   - Region, District, School

2. **Wait for Subjects to Load**
   - Shows: "Subjects shown are based on X registered ACSEE candidate(s)"

3. **Click "Download All Subjects (ZIP)"**
   - Button is enabled if:
     - ✅ School selected
     - ✅ Year selected
     - ✅ Subjects found
     - ✅ Year not locked

4. **Browser Downloads ZIP**
   - Filename: `IRMS_ACSEE_2026_S0203.zip`
   - Contains: CSV per subject + manifest.json

## ZIP Contents Example

```
IRMS_ACSEE_2026_S0203.zip
├── PHY_2026_S0203.csv
├── CHE_2026_S0203.csv
├── MAT_2026_S0203.csv
├── HIS_2026_S0203.csv
└── manifest.json
```

## CSV Format

```
index_number,sex,papers,paper_1,paper_2,paper_3
2024001,M,2,,
2024002,F,2,,
```

Rules:
- ✅ One row per candidate (for that subject)
- ✅ Pre-filled papers count
- ✅ Empty mark cells
- ❌ NO full names

## Manifest.json

Stores:
- **Files:** list of CSVs with checksums
- **Metadata:** school, year, generation time
- **Audit:** who generated, when

Use to:
- Verify file integrity (checksums)
- Track audit trail (user ID, timestamp)
- Count candidates per subject

## Access Control

| Role | Can Download |
|------|-------------|
| School User | Own school only |
| Regional Officer | Schools in their region |
| Admin | Any school |

Non-authorized users get: **HTTP 403 Forbidden**

## Performance

Expected times:
- 100 candidates, 3 subjects: **~200ms**
- 500 candidates, 10 subjects: **~800ms**
- 1000+ candidates, 15 subjects: **~2s**

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Button disabled | Select school & year; wait for subjects to load |
| No subjects found | Verify candidates have combinations assigned |
| Download fails | Check browser console for error; check server logs |
| Checksum mismatch | File may be corrupted; download again |

## For Developers

### Files Modified/Created

**New Files:**
- `app/Services/MarkImport/BulkCsvExportService.php` - Core logic
- `app/Policies/BulkCsvExportPolicy.php` - Authorization
- `BULK_CSV_EXPORT_IMPLEMENTATION.md` - Full documentation

**Modified Files:**
- `app/Http/Controllers/MarkEntryController.php` - Added endpoint
- `routes/web.php` - Added route
- `resources/views/mark-entry/index.blade.php` - Added button & method

### Key Methods

```php
// Service: Generate export
$service = new BulkCsvExportService();
$result = $service->generateBulkExport($schoolId, $examYearId);
// Returns: ['zip_path', 'filename', 'manifest']

// Controller: Download endpoint
GET /mark-entry/acsee/bulk-csv-download
  ?exam_year_id=1
  &school_id=34
```

### Query Optimization

Uses chunking for memory efficiency:
```php
$query->chunk(500, function ($registrations) {
    // Process 500 records at a time
});
```

### Audit Logging

All exports logged to: `storage/logs/audit.log`

```json
{
  "user_id": 12,
  "role": "school_user",
  "school_id": 34,
  "num_subjects": 12,
  "num_candidates": 523,
  "zip_filename": "IRMS_ACSEE_2026_S0325.zip",
  "timestamp": "2026-01-15T10:42:00Z"
}
```

## Testing

```bash
# Run all tests
php artisan test --filter BulkCsvExport

# Specific test
php artisan test --filter BulkCsvExportServiceTest
```

## Edge Cases Handled

- ✅ No subjects with candidates → error message
- ✅ Year is locked → button disabled
- ✅ User unauthorized → 403 error
- ✅ Large school (1000+ candidates) → chunked processing
- ✅ File corruption → SHA-256 verification in manifest
- ✅ Concurrent downloads → separate temp files per request

## Notes

- Reuses `SubjectFilterService` for consistency
- Does NOT break existing subject-wise import
- Maintains exam_year isolation (no cross-year data)
- Memory optimized: streams CSVs, processes in chunks
- Enterprise-grade: checksums, audit logs, role-based access
