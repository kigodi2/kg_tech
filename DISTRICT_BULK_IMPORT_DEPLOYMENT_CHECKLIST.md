# District Bulk Import Deployment Checklist

## Pre-Deployment (Development)

### Code Review
- [ ] All files created without syntax errors
- [ ] No hardcoded values (except exam years in manifest)
- [ ] PSR-12 code style consistent
- [ ] All exceptions properly typed
- [ ] No commented-out code left

### Unit Tests
- [ ] `DistrictManifestValidator` - all validation rules tested
- [ ] `DistrictBulkImportOrchestrator` - preflight flow tested
- [ ] `BulkImportPolicy` - all authorization rules tested
- [ ] Error handling for each service

### Integration Tests
- [ ] School import still works (backward compatibility)
- [ ] Full district import flow end-to-end
- [ ] Job dispatch and processing
- [ ] Progress tracking updates correctly
- [ ] Authorization enforcement on API calls

### Database
- [ ] Migrations are reversible
- [ ] No conflicts with existing migrations
- [ ] Foreign keys properly defined
- [ ] Indexes created for performance
- [ ] Test migration rollback

---

## Staging Deployment

### Environment Setup
- [ ] Copy code to staging environment
- [ ] Update `ENVIRONMENT` to staging
- [ ] Configure queue connection (database/redis)
- [ ] Set queue workers to 2-4 concurrent
- [ ] Configure audit log channel

### Database Preparation
- [ ] Backup production database
- [ ] Run migrations: `php artisan migrate`
- [ ] Verify `bulk_imports` table changes
- [ ] Verify `bulk_import_schools` table created
- [ ] Check indexes created: `SHOW INDEX FROM bulk_imports`
- [ ] Seed test district with schools

### Service Registration
- [ ] Register `BulkImportPolicy` in `AuthServiceProvider.php`
- [ ] Verify service auto-discovery: `php artisan tinker`
  ```php
  app(DistrictBulkImportOrchestrator::class)  // Should work
  ```
- [ ] Check policy gates: `php artisan tinker`
  ```php
  Gate::can('uploadDistrictCsv', BulkImport::class, 5)
  ```

### API Routes
- [ ] Routes registered: `php artisan route:list`
- [ ] POST `/api/bulk-import/preview` exists
- [ ] POST `/api/bulk-import/district/start` exists
- [ ] GET `/api/bulk-import/{id}/progress` exists
- [ ] GET `/api/bulk-import/{id}` exists

### Queue Configuration
- [ ] Job `ProcessBulkImportSchool` discoverable
- [ ] Queue driver configured (database/redis)
- [ ] Queue worker started: `php artisan queue:work`
- [ ] Jobs table exists (if using database queue)

### Security
- [ ] Update permissions on temporary files directory
- [ ] Ensure ZIP uploads use secure temp path
- [ ] Verify session storage secure
- [ ] Check CSRF tokens on form submissions
- [ ] Verify authorization on all endpoints

### Configuration
- [ ] Set zip extraction path writable: `chmod 755 storage/app/temp`
- [ ] Set log directory writable: `chmod 755 storage/logs`
- [ ] Verify queue timeout: 3600 seconds minimum
- [ ] Verify job retry count: 3 attempts

---

## Staging Testing

### Functional Tests

#### Test 1: School Import Still Works
```bash
# Upload school-level ZIP for existing school
POST /api/bulk-import/preview
POST /api/bulk-import/start (with school_id)
# Expected: Status completed with all candidates imported
```

#### Test 2: District Import Success Path
```bash
# Create test district with 2-3 schools
# Create manifest.json with valid structure
# Create school folders with CSV files
# Zip everything
POST /api/bulk-import/preview
# Expected: Valid preview with all schools listed
POST /api/bulk-import/district/start
# Expected: status=importing, bulk_import_id returned
GET /api/bulk-import/{id}/progress (poll every 10s)
# Expected: progress_percentage increases, schools marked success
```

#### Test 3: Manifest Validation
```bash
# Test each validation rule
- Missing exam_year → error
- Wrong scope.type → error
- Wrong scope.code → error
- School not in district → error
- Missing subject checksum → error
# Expected: Validation errors returned
```

#### Test 4: Failure Isolation
```bash
# Create district ZIP with 3 schools
# Make second school's CSV invalid (corrupt headers)
POST /api/bulk-import/district/start
# Expected: School 1 success, School 2 failed, School 3 success
# Status should be "partial"
```

#### Test 5: Progress Tracking
```bash
# Start import, immediately GET /api/bulk-import/{id}/progress
# Expected: Schools list with pending status
# Poll every 2 seconds for 1 minute
# Expected: Schools change from pending → processing → {success|failed}
# Overall percentage increases from 0 → 100
```

#### Test 6: Authorization
```bash
# School officer user tries to upload district import
# Expected: 403 Forbidden
# District officer from different district tries to upload
# Expected: 403 Forbidden
# Authorized district officer uploads
# Expected: 200 OK
```

#### Test 7: Cleanup
```bash
# Complete import
# Check storage/app/temp/imports/{bulk_import_id}
# Expected: Directory removed
# Check database for audit log
# Expected: Audit entry present with full context
```

#### Test 8: Job Queue Processing
```bash
# Monitor queue:
php artisan queue:work --verbose
# Expected: ProcessBulkImportSchool job dispatched per school
# Expected: ProcessBulkImportFile job dispatched per subject
# Expected: All jobs completed successfully
```

### Performance Tests

#### Test 9: Load Test - 5k Candidates
```bash
# Create district ZIP with 5 schools × 10 subjects each
# Total: ~5000 candidates
php artisan queue:work
POST /api/bulk-import/district/start
# Monitor: memory usage, CPU, processing time
# Expected: Complete in < 5 minutes
# Expected: Memory < 100MB
```

#### Test 10: Load Test - Queue Processing
```bash
# Start 4 queue workers in parallel
php artisan queue:work &
php artisan queue:work &
php artisan queue:work &
php artisan queue:work &
# Upload large district import
# Expected: Schools process in parallel
# Expected: Total time reduced vs single worker
```

### Database Tests

#### Test 11: Data Integrity
```bash
# After import completes, verify:
SELECT COUNT(*) FROM mark_imports WHERE bulk_import_id = ?;
# Expected: Count matches total_files
SELECT SUM(rows_success) FROM bulk_import_files WHERE bulk_import_id = ?;
# Expected: Matches total candidates imported
```

#### Test 12: Relationship Integrity
```php
// Verify relationships
$import = BulkImport::with('schools', 'district', 'examYear')->find(42);
echo $import->schools()->count();    // Should be 3
echo $import->district->name;        // Should be set
echo $import->examYear->year;        // Should be set
```

### Edge Cases

#### Test 13: Duplicate School Codes
```bash
# ZIP with school code appearing twice
# Expected: Validation error "Duplicate school_code"
```

#### Test 14: Timestamp Testing
```bash
# Verify timestamps recorded correctly
SELECT * FROM bulk_import_schools WHERE bulk_import_id = 42;
# Expected: started_at and completed_at are reasonable (not 1970-01-01)
```

#### Test 15: Concurrent Imports
```bash
# Start import for District A
# While processing, start import for District B
# Expected: Both complete successfully (no crosstalk)
```

---

## Production Deployment

### Pre-Deployment Checklist
- [ ] Code review approved by senior dev
- [ ] All staging tests passed
- [ ] Database backup completed
- [ ] Rollback plan documented
- [ ] Team trained on procedures
- [ ] Support team briefed
- [ ] Monitoring configured

### Deployment Steps

#### 1. Database Migration (Zero Downtime)
```bash
# Create maintenance window (optional but recommended)
# Backup current database
mysqldump -u root irms > backup_$(date +%Y%m%d).sql

# Run migrations
cd /var/www/irms
php artisan migrate --force

# Verify tables exist
php artisan tinker
>>> Schema::hasTable('bulk_import_schools')
```

#### 2. Code Deployment
```bash
# Pull latest code
git pull origin main

# Install composer dependencies (if any new)
composer install --no-dev --no-interaction

# Cache config and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3. Service Registration
```bash
# Register policy in AuthServiceProvider.php
# Publish to production

# Verify in production
php artisan tinker
>>> Gate::can('uploadDistrictCsv', BulkImport::class, 1)
```

#### 4. Queue Configuration
```bash
# Ensure queue workers running via supervisor
supervisorctl status laravel-worker

# If using database queue:
php artisan queue:table
php artisan migrate

# Start workers
php artisan queue:work --daemon
```

#### 5. Monitoring Setup
- [ ] Configure log rotation for audit logs
- [ ] Set up alerts for queue failures
- [ ] Set up alerts for job timeouts
- [ ] Monitor disk space for temp files
- [ ] Monitor database for slow queries

### Smoke Tests (Production)

#### Test 1: Endpoint Accessibility
```bash
curl -X GET https://irms.example.com/api/bulk-import/1/progress
# Expected: 200 or 404 (depending on existing imports)
```

#### Test 2: Database Connection
```php
php artisan tinker
>>> BulkImport::count()  // Should return integer
```

#### Test 3: Queue Processing
```bash
php artisan tinker
>>> Queue::pending()  // Should show any queued jobs
```

#### Test 4: Audit Logging
```bash
tail -f storage/logs/audit.log
# Should see existing log entries
```

### Post-Deployment

#### 1. Documentation
- [ ] Update help docs for district officers
- [ ] Create training material
- [ ] Document error codes and recovery
- [ ] Update IRMS wiki/knowledge base

#### 2. Monitoring
- [ ] Monitor queue processing in first 24 hours
- [ ] Check for errors in logs
- [ ] Verify audit logs created
- [ ] Monitor disk usage

#### 3. Communication
- [ ] Notify district officers of new feature
- [ ] Provide quick-start guide
- [ ] Set up support channel for issues
- [ ] Schedule training sessions

---

## Rollback Plan

### If Critical Issue Found

#### Option 1: Database Rollback Only
```bash
# Revert migration
php artisan migrate:rollback

# Check data integrity
php artisan tinker
>>> BulkImport::count()  // Should be previous count
```

#### Option 2: Full Rollback
```bash
# Revert code to previous commit
git revert <commit-hash>

# Rollback database
php artisan migrate:rollback

# Clear caches
php artisan cache:clear
php artisan config:clear

# Verify with smoke tests
curl https://irms.example.com/api/regions
```

#### Option 3: Restore from Backup
```bash
# Stop application
supervisorctl stop laravel-worker
supervisorctl stop laravel-app

# Restore database from backup
mysql -u root irms < backup_20250315.sql

# Start application
supervisorctl start laravel-worker
supervisorctl start laravel-app
```

---

## Post-Deployment Verification

### Week 1

- [ ] Monitor imports daily
- [ ] Check queue processing health
- [ ] Review audit logs for anomalies
- [ ] Verify no performance degradation
- [ ] Check disk usage trends
- [ ] Gather feedback from district officers

### Week 2-4

- [ ] Ensure 100% uptime
- [ ] Process at least 1 full district import
- [ ] Verify data integrity matches school imports
- [ ] Test failure recovery procedures
- [ ] Document any issues encountered

### Month 1+

- [ ] Conduct optimization review
- [ ] Archive audit logs
- [ ] Update runbooks based on learnings
- [ ] Plan future enhancements
- [ ] Review performance metrics

---

## Troubleshooting During Deployment

### Issue: Jobs Not Processing
```bash
# Check queue workers
supervisorctl status

# Check jobs table
SELECT * FROM jobs LIMIT 5;

# Manually process jobs
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log
```

### Issue: Authorization Error
```bash
# Verify policy registered
php artisan tinker
>>> Gate::can('uploadDistrictCsv', BulkImport::class, 1)

# Check AuthServiceProvider.php
grep "BulkImportPolicy" app/Providers/AuthServiceProvider.php
```

### Issue: Migration Failed
```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Check error in migration file
cat database/migrations/2026_02_01_000000*.php
```

### Issue: Temporary Files Not Cleaning
```bash
# Manually clean
rm -rf storage/app/temp/imports/*

# Verify cleanup code in orchestrator
grep "removeDirectory" app/Services/MarkImport/DistrictBulkImportOrchestrator.php
```

---

## Sign-Off

- [ ] Deployment Lead: _________________ Date: _______
- [ ] Database Admin: _________________ Date: _______
- [ ] QA Lead: _________________ Date: _______
- [ ] Product Owner: _________________ Date: _______

---

## Emergency Contacts

- **Lead Developer**: [name/phone/email]
- **Database Admin**: [name/phone/email]
- **DevOps**: [name/phone/email]
- **Product Owner**: [name/phone/email]

---

## Related Documentation

- [District Bulk Import Implementation](./DISTRICT_BULK_IMPORT_IMPLEMENTATION.md)
- [Quick Start Guide](./DISTRICT_BULK_IMPORT_QUICK_START.md)
- [Technical Reference](./DISTRICT_BULK_IMPORT_TECHNICAL_REFERENCE.md)
- [System Architecture](./SYSTEM_ARCHITECTURE.md)
