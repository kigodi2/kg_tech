# Bulk CSV Export - Deployment Checklist

## Pre-Deployment

### Code Review ✓
- [x] BulkCsvExportService.php reviewed
- [x] BulkCsvExportPolicy.php reviewed
- [x] MarkEntryController endpoint reviewed
- [x] routes/web.php updated
- [x] Blade template updated
- [x] Alpine.js method added

### Testing ✓
- [x] Unit tests for service logic
- [x] Policy authorization tests
- [x] Controller endpoint tests
- [x] UI button state tests
- [x] Integration tests (end-to-end)

## Deployment Steps

### 1. Directory Setup
```bash
# Create temporary export directory
mkdir -p storage/app/temp
chmod 755 storage/app/temp

# Verify ownership and permissions
ls -la storage/app/temp
```

### 2. Logging Configuration
Edit `config/logging.php` and add/verify audit channel:
```php
'channels' => [
    // ... existing channels ...
    'audit' => [
        'driver' => 'single',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
    ],
],
```

### 3. User Model Configuration
Verify User model has these role-checking methods:
```php
// app/Models/User.php
public function isAdmin(): bool {
    return $this->role === 'admin';
}

public function isRegionalOfficer(): bool {
    return $this->role === 'regional_officer';
}

public function isSchoolUser(): bool {
    return $this->role === 'school_user';
}
```

### 4. Database Indexes (Recommended)
```sql
-- Create performance indexes
CREATE INDEX IF NOT EXISTS idx_candidate_exam_reg_year_type 
    ON candidate_exam_registrations(exam_year_id, exam_type_id);

CREATE INDEX IF NOT EXISTS idx_candidate_school 
    ON candidates(school_id);

CREATE INDEX IF NOT EXISTS idx_combination_subject 
    ON combination_subject(combination_id, subject_id);
```

### 5. File Deployment
Push to server:
```bash
# New files
app/Services/MarkImport/BulkCsvExportService.php
app/Policies/BulkCsvExportPolicy.php
BULK_CSV_EXPORT_IMPLEMENTATION.md
BULK_CSV_EXPORT_QUICK_START.md
BULK_CSV_EXPORT_DEPLOYMENT.md

# Modified files
app/Http/Controllers/MarkEntryController.php
routes/web.php
resources/views/mark-entry/index.blade.php
```

### 6. Route Cache (if using)
```bash
# Clear and rebuild route cache
php artisan route:cache
```

### 7. Config Cache (if using)
```bash
# Clear config cache to pick up new logging config
php artisan config:cache
```

## Verification Steps

### 1. Routes Registered
```bash
php artisan route:list | grep bulk-csv

# Expected output:
# GET|HEAD  /mark-entry/acsee/bulk-csv-download ... MarkEntryController@downloadBulkCsvExport
```

### 2. Temp Directory Writable
```bash
# Test write access
touch storage/app/temp/test.txt && rm storage/app/temp/test.txt && echo "✓ Writable"
```

### 3. Audit Logging Works
```bash
# Check audit log exists and is writable
ls -la storage/logs/audit.log
tail -5 storage/logs/audit.log
```

### 4. Service Can Be Instantiated
```bash
php artisan tinker << 'EOF'
$service = new \App\Services\MarkImport\BulkCsvExportService();
echo "✓ Service instantiated successfully\n";
exit()
EOF
```

### 5. Policy Can Be Checked
```bash
php artisan tinker << 'EOF'
$user = \App\Models\User::first();
$school = \App\Models\School::first();
$can = auth()->user()->can('downloadBulkCsv', $school->id);
echo "✓ Policy check: " . ($can ? "Allowed" : "Denied") . "\n";
exit()
EOF
```

## Smoke Tests

### Test 1: Authorization
```bash
# As school user, try downloading different school
# Expected: 403 Forbidden

curl -b cookies.txt \
  'http://localhost:8001/mark-entry/acsee/bulk-csv-download?exam_year_id=1&school_id=999'
  
# Response should be:
# {"success": false, "message": "You do not have permission..."}
```

### Test 2: Validation
```bash
# Missing required parameter
curl -b cookies.txt \
  'http://localhost:8001/mark-entry/acsee/bulk-csv-download?exam_year_id=1'
  
# Response should be:
# {"success": false, "message": "...school_id is required"}
```

### Test 3: Happy Path
```bash
# Valid request (as authorized user)
curl -b cookies.txt \
  'http://localhost:8001/mark-entry/acsee/bulk-csv-download?exam_year_id=1&school_id=34' \
  -o export.zip
  
# Should download ZIP file
file export.zip
# Expected: Zip archive data
```

### Test 4: ZIP Integrity
```bash
# Extract and verify manifest
unzip -l export.zip
# Should list CSVs and manifest.json

# Check manifest format
unzip -p export.zip manifest.json | jq .
# Should output valid JSON
```

### Test 5: Audit Log
```bash
# Check audit log for entry
tail -1 storage/logs/audit.log | jq .

# Expected fields:
# - action: "bulk_csv_export"
# - user_id: 12
# - school_id: 34
# - num_subjects: 12
# - num_candidates: 523
# - timestamp: "2026-01-15T10:42:00Z"
```

## Performance Verification

### Test Large School Export
```bash
# Time a 1000+ candidate export
time curl -b cookies.txt \
  'http://localhost:8001/mark-entry/acsee/bulk-csv-download?exam_year_id=1&school_id=LARGE_SCHOOL_ID' \
  -o large_export.zip

# Expected: < 30 seconds
```

### Monitor Memory Usage
```bash
# Watch system memory during export
watch -n 1 'free -h && ps aux | grep php'

# Expected: Memory usage stays under 100MB
```

## Post-Deployment Monitoring

### Daily Checks
```bash
# Monitor audit log for bulk exports
grep bulk_csv_export storage/logs/audit.log | tail -10

# Check for errors
grep "Error\|Exception" storage/logs/audit.log | tail -5

# Monitor temp directory size
du -sh storage/app/temp
# Should stay < 100MB (auto-cleanup after download)
```

### Weekly Review
```bash
# Analyze usage patterns
grep bulk_csv_export storage/logs/audit.log | \
  jq -s 'group_by(.user_id) | map({user: .[0].user_id, count: length})'

# Check for suspicious access patterns
grep bulk_csv_export storage/logs/audit.log | \
  jq '.role, .school_id' | sort | uniq -c | sort -rn
```

## Troubleshooting

### Issue: "Failed to create ZIP file"
**Cause:** Temp directory not writable
**Solution:**
```bash
chmod 755 storage/app/temp
chmod 777 storage/app/temp  # More permissive if needed
```

### Issue: Out of Memory on Large Export
**Cause:** Chunk size too large or unchecked dataset
**Solution:**
```php
// In BulkCsvExportService.php, reduce chunk size:
const CHUNK_SIZE = 250;  // was 500
```

### Issue: Checksums Don't Match
**Cause:** File corruption during transfer
**Solution:**
```bash
# Re-download and verify integrity
unzip -t export.zip  # Test archive integrity
```

### Issue: Audit Log Not Recording
**Cause:** Logging channel not configured
**Solution:**
```bash
# Verify config
php artisan config:show logging.channels.audit

# If missing, add to config/logging.php
'audit' => [
    'driver' => 'single',
    'path' => storage_path('logs/audit.log'),
    'level' => 'info',
],
```

## Rollback Plan

If issues occur:

### Disable Feature (Quick)
```php
// In routes/web.php, comment out:
// Route::get('/mark-entry/acsee/bulk-csv-download', ...);
```

### Revert Files (Full)
```bash
git revert <commit-hash>
php artisan route:cache
php artisan config:cache
```

### Verify Rollback
```bash
php artisan route:list | grep bulk-csv
# Should show no results (feature disabled)
```

## Performance Baselines

Document these after deployment:

| Metric | Baseline | Alert Threshold |
|--------|----------|-----------------|
| 100-candidate export | 200ms | 500ms |
| 500-candidate export | 800ms | 2s |
| 1000-candidate export | 2s | 5s |
| Temp dir size | < 50MB | > 500MB |
| Audit log growth | ~500 bytes/export | |

## Sign-Off Checklist

- [ ] Code reviewed by lead developer
- [ ] All tests passing
- [ ] Temp directory created and writable
- [ ] Logging channel configured
- [ ] User role methods verified
- [ ] Database indexes created
- [ ] Routes registered
- [ ] Smoke tests passed
- [ ] Performance baseline documented
- [ ] Audit logging working
- [ ] Team trained on feature
- [ ] Documentation accessible to ops team

## Contact for Issues

- **Feature Owner:** [Engineering Lead]
- **On-Call DevOps:** [DevOps Contact]
- **Documentation:** BULK_CSV_EXPORT_IMPLEMENTATION.md

## Post-Deployment Success Criteria

✓ Users can download bulk CSV exports
✓ Authorization enforced correctly
✓ Checksums validate successfully
✓ Audit logs record all actions
✓ No memory/performance issues
✓ No user-facing errors
✓ ZIP files extract properly
✓ CSVs import without errors
