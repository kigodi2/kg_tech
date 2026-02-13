# District Bulk Import - Deployment & Quick Start Guide

**Document Version**: 1.0  
**Status**: Production Ready  
**Last Updated**: February 1, 2026

---

## 1. Quick Start (5 Minutes)

### For Developers
```bash
# 1. Apply migrations
php artisan migrate

# 2. Start queue worker (separate terminal)
php artisan queue:work --timeout=3600

# 3. Access UI
# Navigate to: /mark-entry
# Click: "District Bulk ZIP" tab

# Done! Ready to test.
```

### For DevOps
```bash
# 1. Ensure these files exist:
#    - database/migrations/2026_02_01_*.php
#    - app/Models/BulkImport.php
#    - app/Services/MarkImport/DistrictBulkImportOrchestrator.php
#    - app/Http/Controllers/BulkImportController.php
#    - app/Jobs/ProcessBulkImportSchool.php
#    - app/Jobs/ProcessBulkImportFile.php
#    - resources/views/mark-entry/index.blade.php

# 2. Run migrations
php artisan migrate

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 4. Start queue workers
supervisord restart  # or manually start workers

# 5. Test endpoint
curl -X GET http://localhost/api/bulk-import/1/progress
```

---

## 2. Pre-Deployment Checklist

### Environment Variables
```bash
# .env should include:
APP_URL=https://your-domain.com
QUEUE_CONNECTION=database  # or redis for production
CACHE_DRIVER=redis         # Recommended for sessions
SESSION_DRIVER=database    # Or redis

# Optional: increase upload limit if needed
# In PHP: post_max_size=100M, upload_max_filesize=100M
```

### Database
```bash
# Run migrations
php artisan migrate

# Verify tables
php artisan tinker
>>> Schema::hasTable('bulk_imports')
>>> Schema::hasTable('bulk_import_schools')
>>> true  # Should return true for both
```

### Services & Providers
Verify in `config/app.php` or `AppServiceProvider`:
```php
// Already registered:
// - BulkImportController
// - Policies: BulkImportPolicy
```

### Storage
```bash
# Ensure temp directory exists
mkdir -p storage/app/temp/imports
chmod 755 storage/app/temp/imports

# Cleanup old temp files (in Kernel.php or cron)
# Run daily: rm -rf storage/app/temp/imports/*
```

### Queue Workers
```bash
# Option 1: Synchronous (Testing Only)
# .env: QUEUE_CONNECTION=sync
# No worker needed, jobs run immediately

# Option 2: Database Queue (Small Deployments)
# .env: QUEUE_CONNECTION=database
php artisan queue:work --timeout=3600

# Option 3: Redis Queue (Recommended for Production)
# .env: QUEUE_CONNECTION=redis
php artisan queue:work --timeout=3600

# Option 4: Supervisor (Production - Recommended)
# /etc/supervisor/conf.d/irms-queue.conf:
[program:irms-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/irms/artisan queue:work redis --sleep=3 --tries=3 --timeout=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/irms/storage/logs/queue.log

# Start supervisor
supervisorctl reread
supervisorctl update
supervisorctl start irms-queue:*
```

---

## 3. Migration Guide

### If Coming from School-Level Only Imports

**What Changes**:
- `bulk_imports` table gets new columns:
  - `scope_type` (enum: school | district)
  - `scope_id` (bigint)
  - `district_id` (bigint, nullable)
  - `total_schools`, `processed_schools`
- New table: `bulk_import_schools` (pivot)

**Data Migration** (existing records):
```php
// Existing imports are safe. They get:
// scope_type = 'school'
// scope_id = school_id
// district_id = NULL
// No data loss

// Migration handles this automatically
```

**Backward Compatibility**:
✅ All existing school-level import endpoints work unchanged  
✅ New district endpoints are additive  
✅ UI adds new "District Bulk ZIP" tab alongside existing tabs

---

## 4. Installation Steps

### Step 1: Copy Files
```bash
# All files already in repository:
# - Migrations
# - Models
# - Controllers
# - Services
# - Jobs
# - Views
# - Routes

# Nothing to copy manually (already committed)
```

### Step 2: Run Migrations
```bash
php artisan migrate

# Verify
php artisan migrate:status
# Should show 2026_02_01 migrations as "Ran"
```

### Step 3: Create Test Data
```bash
php artisan tinker

# Create test district
$district = District::create([
    'code' => 'TEST',
    'name' => 'Test District',
    'region_id' => 1
]);

# Create test school
$school = School::create([
    'code' => 'S0001',
    'name' => 'Test School',
    'district_id' => $district->id
]);

# Create exam year
$examYear = ExamYear::create([
    'year' => 2025,
    'year_label' => 'ACSEE 2025',
    'exam_type_id' => 3
]);

exit
```

### Step 4: Configure Queue
```bash
# For development (sync):
# In .env: QUEUE_CONNECTION=sync

# For production (Redis):
# In .env: QUEUE_CONNECTION=redis
# And start: php artisan queue:work redis

# For production (Supervisor):
# See section 2, "Queue Workers" → Option 4
```

### Step 5: Test Import
```bash
# 1. Access /mark-entry in browser
# 2. Click "District Bulk ZIP" tab
# 3. Select exam year & district
# 4. Upload test ZIP file
# 5. Click Preview
# 6. Click Start Import
# 7. Monitor progress
```

---

## 5. Configuration Reference

### API Endpoints
```
POST   /api/bulk-import/preview
       Request: multipart/form-data {zip_file}
       Response: {preview object}

POST   /api/bulk-import/district/start
       Request: {district_id, exam_year_id}
       Response: {bulk_import_id, message}

GET    /api/bulk-import/{id}/progress
       Response: {progress object}

POST   /api/bulk-import/{id}/retry-school
       Request: {school_id}
       Response: {success, message}

POST   /api/bulk-import/{id}/retry-all
       Response: {success, schools_retried, message}
```

### Authorization Rules
```
School Officer:
  ✅ View own school imports
  ❌ Cannot access district imports
  ❌ Cannot upload for other schools

District Officer:
  ✅ View/upload district imports for own district
  ❌ Cannot access other districts
  ❌ Cannot access school-level imports

Regional Officer:
  ✅ View/upload for all schools in region
  ✅ View/upload for all districts in region

Admin:
  ✅ Unrestricted access to all imports
```

### Database Cleanup
```php
// Remove old temp files (run in scheduler)
// Add to: app/Console/Kernel.php

$schedule->call(function () {
    $tempDir = storage_path('app/temp/imports');
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (time() - filemtime($file) > 86400) { // 24 hours
            $this->removeDirectory($file);
        }
    }
})->daily();

// Clear old completed imports (after 90 days)
$schedule->call(function () {
    BulkImport::where('status', 'completed')
        ->where('completed_at', '<', now()->subDays(90))
        ->delete();
})->monthly();
```

---

## 6. Troubleshooting

### Issue: "ZIP file not found"
**Cause**: Session not storing temp path  
**Solution**: Check session config in .env
```bash
SESSION_DRIVER=database  # Must have sessions_table
# Or use: SESSION_DRIVER=file
```

### Issue: "School not found in district"
**Cause**: School code doesn't match or school in different district  
**Solution**: 
```php
# Verify school exists in correct district
School::where('code', 'S0001')
      ->where('district_id', $district->id)
      ->first()
```

### Issue: Progress not updating
**Cause**: Queue workers not running  
**Solution**:
```bash
# Check if workers running
ps aux | grep "queue:work"

# If not, start them
php artisan queue:work --timeout=3600

# Or check supervisor
supervisorctl status irms-queue
```

### Issue: "Timeout" on large import
**Cause**: Job timeout too short  
**Solution**: Increase in ProcessBulkImportSchool.php
```php
public $timeout = 7200;  // 2 hours instead of 1
```

### Issue: Memory exhausted
**Cause**: Not chunking CSV reads  
**Solution**: Already fixed in ProcessBulkImportFile.php
- CSV read in chunks (500 rows per batch)
- Garbage collection called every 500 rows
- Memory usage capped at ~50MB

### Issue: Audit logs not recording
**Cause**: Audit log channel not configured  
**Solution**: Add to config/logging.php
```php
'channels' => [
    'audit' => [
        'driver' => 'single',
        'path' => storage_path('logs/audit.log'),
    ],
]
```

---

## 7. Performance Tuning

### For Small Deployments (< 10k candidates)
```env
QUEUE_CONNECTION=sync  # Run jobs immediately
CACHE_DRIVER=file
```

### For Medium Deployments (10k-100k candidates)
```env
QUEUE_CONNECTION=database
CACHE_DRIVER=redis
# 2-4 queue workers
```

### For Large Deployments (> 100k candidates)
```env
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
REDIS_URL=redis://cache-server:6379
# 8-16 queue workers
# Consider batching districts
```

### Chunk Size Tuning
In ProcessBulkImportFile.php:
```php
$chunkSize = 500;  // Adjust based on server memory
// Smaller (300) = more CPU/DB calls, less memory
// Larger (1000) = less CPU/DB calls, more memory
```

---

## 8. Monitoring

### Health Check
```bash
# Test API
curl -X GET http://localhost/api/bulk-import/1/progress

# Test database
php artisan tinker
>>> BulkImport::count()

# Test queue
php artisan queue:monitor
```

### Metrics to Track
```
- Imports per day
- Average import duration
- Success rate
- Failure rate
- Retry rate
- Performance percentiles (p50, p95, p99)
```

### Logs to Monitor
```bash
# Application logs
tail -f storage/logs/laravel.log

# Audit logs
tail -f storage/logs/audit.log

# Queue logs
tail -f storage/logs/queue.log
```

---

## 9. Backup & Recovery

### What to Backup
```bash
# Database (most important)
mysqldump irms > irms_backup.sql

# Uploaded ZIPs (optional, can be re-uploaded)
rsync -av storage/app/temp/imports/ backup/

# Configuration
cp .env backup/.env.backup
```

### Recovery Procedure
```bash
# If something goes wrong:

# 1. Stop queue workers
supervisorctl stop irms-queue:*

# 2. Restore database
mysql irms < irms_backup.sql

# 3. Clear temp files
rm -rf storage/app/temp/imports/*

# 4. Restart workers
supervisorctl start irms-queue:*
```

---

## 10. Security Checklist

- [ ] CSRF protection enabled (middleware in routes)
- [ ] Authorization policies enforced (can't access other district)
- [ ] File upload size limited (PHP post_max_size)
- [ ] Temporary files cleaned up (24h retention)
- [ ] Audit logs stored securely (read-only for users)
- [ ] ZIP signature verification enabled
- [ ] Database credentials secured (.env file)
- [ ] Queue connection encrypted (Redis with AUTH)
- [ ] API rate limiting configured
- [ ] Input validation on all endpoints

---

## 11. Support & Maintenance

### Weekly Tasks
```bash
# Cleanup old temp files
rm -rf storage/app/temp/imports/*

# Check disk space
df -h

# Monitor queue performance
php artisan queue:monitor
```

### Monthly Tasks
```bash
# Archive old audit logs
gzip storage/logs/audit.log

# Delete completed imports older than 90 days
php artisan schedule:run

# Review error logs
grep ERROR storage/logs/laravel.log | tail -100
```

### Quarterly Tasks
```bash
# Performance analysis
# - Query slow logs
# - Queue performance
# - Database indexes

# User feedback collection
# - UI usability
# - Error clarity
# - Feature requests
```

---

## 12. Rollback Plan

If issues found after deployment:

### Option 1: Disable District Import (Immediate)
```php
// In routes/api.php, comment out:
// Route::post('/bulk-import/district/start', ...);
// Route::post('/bulk-import/{id}/retry-school', ...);
// Route::post('/bulk-import/{id}/retry-all', ...);

// Keep preview & progress endpoints active for monitoring
```

### Option 2: Rollback Migration (Complete)
```bash
php artisan migrate:rollback --step=2

# This removes:
# - bulk_import_schools table
# - scope columns from bulk_imports
# - Restores to previous version
```

### Option 3: Point-in-Time Recovery (Full)
```bash
# Restore database backup
mysql irms < irms_backup.sql

# Rollback code to previous version
git checkout previous-tag

# Restart application
```

---

## 13. After-Action Items

### First Week
- [ ] Monitor all imports
- [ ] Check for errors in logs
- [ ] Verify audit trail
- [ ] Get user feedback

### First Month
- [ ] Review import statistics
- [ ] Optimize chunk sizes if needed
- [ ] Document any edge cases
- [ ] Plan enhancements

### Ongoing
- [ ] Regular backups
- [ ] Monthly maintenance
- [ ] Performance monitoring
- [ ] User support

---

## 14. Contact & Escalation

### For Issues:
1. **Check Logs**: `storage/logs/laravel.log`
2. **Check Database**: Verify data integrity
3. **Check Queue**: `php artisan queue:monitor`
4. **Contact DevOps**: If infrastructure issue

### Common Questions:
- **"Why is import slow?"** → Check queue workers running
- **"Where did my ZIP go?"** → Temporary files cleaned up after import
- **"Can I re-import?"** → Yes, use retry endpoints or upload new ZIP
- **"Can I export the data?"** → Use mark entry interface or analytics

---

## 15. Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-01 | Initial release - Full implementation |

---

## 16. Success Confirmation

When deployed successfully, you should see:

✅ Mark entry page loads  
✅ "District Bulk ZIP" tab visible  
✅ Exam year dropdown populated  
✅ District dropdown populated  
✅ File upload accepts .zip  
✅ Preview button functional  
✅ API returns valid response  
✅ Queue workers processing jobs  
✅ Progress updating in real-time  
✅ Completion section appears  
✅ Audit logs recording  
✅ Database records created  

---

**Deployment Date**: ____________  
**Deployed By**: ____________  
**Verified By**: ____________  

---

## Appendix: File Manifest

```
Backend:
  ✅ database/migrations/2026_02_01_000000_extend_bulk_imports_for_district_scope.php
  ✅ database/migrations/2026_02_01_000001_create_bulk_import_schools_table.php
  ✅ app/Models/BulkImport.php (updated)
  ✅ app/Models/BulkImportFile.php
  ✅ app/Services/MarkImport/DistrictBulkImportOrchestrator.php
  ✅ app/Services/MarkImport/DistrictManifestValidator.php
  ✅ app/Services/MarkImport/DistrictImportRecoveryService.php
  ✅ app/Services/MarkImport/ZipSignerService.php
  ✅ app/Services/MarkImport/ZipPreviewService.php
  ✅ app/Jobs/ProcessBulkImportSchool.php
  ✅ app/Jobs/ProcessBulkImportFile.php
  ✅ app/Http/Controllers/BulkImportController.php (updated)
  ✅ app/Policies/BulkImportPolicy.php

Frontend:
  ✅ resources/views/mark-entry/index.blade.php (updated)

Routes:
  ✅ routes/api.php (updated with new endpoints)

Documentation:
  ✅ DISTRICT_BULK_IMPORT_IMPLEMENTATION_COMPLETE.md
  ✅ DISTRICT_BULK_IMPORT_UI_COMPLETE.md
  ✅ DISTRICT_BULK_IMPORT_TESTING_GUIDE.md
  ✅ DISTRICT_BULK_IMPORT_DEPLOYMENT_GUIDE.md
```

All files already committed. No additional files needed.

