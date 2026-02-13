# SQLite Backup & Restore System - Implementation Checklist

## Phase 1: File Creation & Database Setup (30 minutes)

### ✓ Core Services
- [ ] Verify `app/Services/SQLiteBackupService.php` exists (434 lines)
- [ ] Verify `app/Services/SQLiteRestoreService.php` exists (495 lines)
- [ ] Check both files have no syntax errors
  ```bash
  php -l app/Services/SQLiteBackupService.php
  php -l app/Services/SQLiteRestoreService.php
  ```

### ✓ Models & Database
- [ ] Verify `app/Models/BackupLog.php` exists
- [ ] Verify migration file exists at `database/migrations/2025_02_02_000001_create_backup_logs_table.php`
- [ ] Run migration
  ```bash
  php artisan migrate
  ```
- [ ] Verify table created
  ```bash
  sqlite3 database/database.sqlite ".schema backup_logs"
  ```

### ✓ Jobs & Scheduling
- [ ] Verify `app/Jobs/ScheduledDailyBackup.php` exists
- [ ] Verify `app/Jobs/ScheduledWeeklyBackup.php` exists
- [ ] Verify `app/Jobs/ScheduledMonthlyBackup.php` exists
- [ ] Verify `app/Console/Kernel.php` exists with schedule definitions
- [ ] Check syntax
  ```bash
  php -l app/Jobs/ScheduledDailyBackup.php
  php -l app/Console/Kernel.php
  ```

### ✓ API & Authorization
- [ ] Verify `app/Http/Controllers/BackupController.php` exists (285 lines)
- [ ] Verify `app/Policies/BackupPolicy.php` exists (62 lines)
- [ ] Verify `routes/backup.php` exists (32 lines)
- [ ] Check syntax
  ```bash
  php -l app/Http/Controllers/BackupController.php
  php -l app/Policies/BackupPolicy.php
  ```

### ✓ Console Commands
- [ ] Verify `app/Console/Commands/ScheduleBackups.php` exists
- [ ] Test command
  ```bash
  php artisan backup:schedule
  ```

---

## Phase 2: Application Configuration (20 minutes)

### ✓ Environment Variables
- [ ] Edit `.env` and add:
  ```env
  BACKUP_ENCRYPTION_KEY=base64:your-strong-key-minimum-32-chars-here
  AUTOMATED_BACKUPS_ENABLED=true
  BACKUP_QUEUE=backups
  BACKUP_RETENTION_DAYS=90
  ```
- [ ] Generate strong key (if not using existing APP_KEY):
  ```bash
  php artisan key:generate --show
  ```

### ✓ Queue Configuration
- [ ] Check `config/queue.php` exists
- [ ] Add to `connections` array in `config/queue.php`:
  ```php
  'backups' => [
      'driver' => 'database',
      'table' => 'jobs',
      'queue' => 'backups',
      'retry_after' => 300,
  ],
  ```
- [ ] Or use Redis (if available):
  ```php
  'backups' => [
      'driver' => 'redis',
      'connection' => 'default',
      'queue' => 'backups',
  ],
  ```

### ✓ Routes Registration
- [ ] Edit `routes/api.php`
- [ ] Add at the end (before closing brace if present):
  ```php
  // Backup & Restore API
  require_once 'backup.php';
  ```
- [ ] Or manually register routes:
  ```php
  Route::middleware(['auth:sanctum', 'admin'])->prefix('backups')->group(function () {
      Route::get('/status', 'App\Http\Controllers\BackupController@status');
      Route::post('/create', 'App\Http\Controllers\BackupController@create');
      // ... rest of endpoints
  });
  ```

### ✓ Authorization Setup
- [ ] Edit `app/Providers/AuthServiceProvider.php` (or `AppServiceProvider.php`)
- [ ] Add to `boot()` method:
  ```php
  use App\Models\Backup;
  use App\Policies\BackupPolicy;
  
  public function boot(): void
  {
      Gate::policy(Backup::class, BackupPolicy::class);
  }
  ```

### ✓ Create Storage Directories
- [ ] Create backup directories:
  ```bash
  mkdir -p storage/backups/sqlite
  mkdir -p storage/backups/archives/monthly
  mkdir -p storage/backups/quarantine
  mkdir -p storage/backups/sandbox
  chmod 750 storage/backups/sqlite
  chmod 750 storage/backups/archives/monthly
  chmod 750 storage/backups/quarantine
  chmod 750 storage/backups/sandbox
  ```
- [ ] Verify permissions:
  ```bash
  ls -la storage/backups/
  ```

---

## Phase 3: Queue & Background Worker Setup (20 minutes)

### ✓ Database Queue Table
- [ ] Create jobs table (if not using Redis):
  ```bash
  php artisan queue:table
  php artisan migrate
  ```
- [ ] Verify table created:
  ```bash
  sqlite3 database/database.sqlite ".schema jobs"
  ```

### ✓ Queue Worker Setup (Development)
- [ ] Test queue worker:
  ```bash
  php artisan queue:work --queue=backups --timeout=3600
  ```
- [ ] Open another terminal and verify it's running:
  ```bash
  ps aux | grep queue:work
  ```
- [ ] Stop worker (Ctrl+C) for now

### ✓ Queue Worker Setup (Production - Supervisor)
- [ ] Create supervisor config: `/etc/supervisor/conf.d/irms-backup-worker.conf`
  ```ini
  [program:irms-backup-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path/to/irms/artisan queue:work --queue=backups --timeout=3600
  autostart=true
  autorestart=true
  user=www-data
  numprocs=1
  stdout_logfile=/var/log/irms/backup-worker.log
  ```
- [ ] Reload supervisor:
  ```bash
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start irms-backup-worker:*
  ```
- [ ] Verify running:
  ```bash
  sudo supervisorctl status irms-backup-worker:*
  ```

### ✓ Scheduler Setup
- [ ] Add to crontab:
  ```bash
  crontab -e
  # Add line:
  * * * * * cd /path/to/irms && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Verify crontab entry:
  ```bash
  crontab -l | grep schedule:run
  ```

---

## Phase 4: Testing & Validation (45 minutes)

### ✓ Test 1: Create Manual Backup
```bash
# Via Artisan Tinker
php artisan tinker
> $admin = App\Models\User::where('is_admin', true)->first();
> $result = app('App\Services\SQLiteBackupService')->createFullBackup($admin, 'Test backup');
> $result;
# Should return array with 'success' => true
```

### ✓ Test 2: Verify Backup File
```bash
# Check file exists and is encrypted
ls -lah storage/backups/sqlite/
# Should show: bak-full-YYYY-MM-DD-HHMMSS-xxxx.zip.enc

# Verify it's not readable (encrypted)
file storage/backups/sqlite/*.zip.enc
```

### ✓ Test 3: Check Audit Logs
```bash
php artisan tinker
> App\Models\BackupLog::latest()->first();
# Should show backup_created operation with success status
```

### ✓ Test 4: API Endpoint - Status
```bash
# Get authentication token first
TOKEN=$(curl -s -X POST https://app.example.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.token')

# Test status endpoint
curl https://app.example.com/api/backups/status \
  -H "Authorization: Bearer $TOKEN" \
  | jq .

# Should return: {"status": "healthy", "last_backup": {...}}
```

### ✓ Test 5: API Endpoint - Create Backup
```bash
curl -X POST https://app.example.com/api/backups/create \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"notes": "Test backup via API"}' \
  | jq .

# Should return 201 with backup details
```

### ✓ Test 6: API Endpoint - View Logs
```bash
curl https://app.example.com/api/backups/logs \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data | length'

# Should show number of log entries > 0
```

### ✓ Test 7: Validate Backup
```bash
# Find most recent backup
BACKUP=$(ls -t storage/backups/sqlite/*.zip.enc | head -1 | xargs basename)

curl -X POST https://app.example.com/api/backups/validate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"backup_path\": \"storage/backups/sqlite/$BACKUP\"}" \
  | jq .

# Should return: {"success": true, "valid": true, "errors": []}
```

### ✓ Test 8: Simulate Restore
```bash
curl -X POST https://app.example.com/api/backups/simulate-restore \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"backup_path\": \"storage/backups/sqlite/$BACKUP\"}" \
  | jq .

# Should return simulation results with passed: true
```

### ✓ Test 9: Non-Admin Cannot Backup
```bash
# Get token as non-admin user
USER_TOKEN=$(curl -s -X POST https://app.example.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  | jq -r '.token')

# Try to create backup (should fail)
curl -X POST https://app.example.com/api/backups/create \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"notes": "Unauthorized backup"}' \
  | jq .

# Should return: 403 Forbidden
```

### ✓ Test 10: Queue Jobs Dispatch
```bash
# Manually dispatch a job
php artisan tinker
> dispatch(new App\Jobs\ScheduledDailyBackup());
# Should return job ID

# Check job in queue
> DB::table('jobs')->count();
# Should be > 0

# Process queue
php artisan queue:work --queue=backups --once

# Check job was processed
> App\Models\BackupLog::byOperation('backup_created')->latest()->first();
# Should show new backup entry
```

---

## Phase 5: Scheduler & Automation Testing (30 minutes)

### ✓ Verify Schedule Registered
```bash
# Test scheduler without executing
php artisan schedule:list

# Should show:
# - backup-daily          | 1:00 AM daily
# - backup-weekly         | Sunday 2:00 AM  
# - backup-monthly        | 1st of month 3:00 AM
```

### ✓ Test Scheduler Execution
```bash
# Run scheduler once
php artisan schedule:run

# Check logs
tail -f storage/logs/laravel.log | grep -i backup

# Should show scheduled jobs dispatched
```

### ✓ Test Daily Backup Job
```bash
# Manually trigger (to test before scheduled time)
php artisan tinker
> dispatch(new App\Jobs\ScheduledDailyBackup());

# Run queue worker
php artisan queue:work --queue=backups --once

# Verify backup created
> App\Models\BackupLog::backupOperations()->latest()->first();

# Check backup file
ls -lah storage/backups/sqlite/ | tail -1
```

### ✓ Test Weekly Backup Job
```bash
# Manually trigger
> dispatch(new App\Jobs\ScheduledWeeklyBackup());

# Run queue worker
# (In another terminal)

# Verify
> App\Models\BackupLog::where('operation', 'like', '%weekly%')->latest()->first();
```

### ✓ Monitor Scheduled Execution
```bash
# Watch cron logs (if available)
tail -f /var/log/syslog | grep CRON

# Or check Laravel schedule log
tail -f storage/logs/schedule.log

# Should see entries for schedule:run execution
```

---

## Phase 6: Production Hardening (20 minutes)

### ✓ Security Permissions
```bash
# Restrict backup directory access
chmod 750 storage/backups/sqlite
chmod 750 storage/backups/archives
chmod 750 storage/backups/quarantine
chmod 750 storage/backups/sandbox

# Set backup file permissions (non-world-readable)
chmod 640 storage/backups/sqlite/*.zip.enc
chmod 440 storage/backups/archives/monthly/*.zip.enc

# Verify
ls -la storage/backups/sqlite/
```

### ✓ Encryption Key Security
```bash
# Verify key is in .env and not in .env.example
grep BACKUP_ENCRYPTION_KEY .env
echo "Should not show key below:"
grep BACKUP_ENCRYPTION_KEY .env.example || echo "✓ Not in .env.example"

# Ensure .env is not world-readable
chmod 640 .env
```

### ✓ Log Rotation
- [ ] Add to `/etc/logrotate.d/irms`:
  ```
  /var/log/irms/backup-worker.log {
      daily
      rotate 30
      compress
      delaycompress
      missingok
      notifempty
  }
  ```

### ✓ Backup Storage Security
- [ ] Verify backups are on secure storage
- [ ] Consider off-site backup replication (future feature)
- [ ] Document retention policy
- [ ] Set up automated old backup deletion (>90 days)

### ✓ Monitoring & Alerting
- [ ] Set up log monitoring:
  ```bash
  # Example: Alert on failed backups
  grep "backup_failed" storage/logs/laravel.log | wc -l
  ```
- [ ] Consider integration with:
  - Sentry (error tracking)
  - DataDog (monitoring)
  - New Relic (APM)
  - Custom webhook for alerts

### ✓ Database Backups Exclude
- [ ] Ensure `.gitignore` excludes backups:
  ```
  storage/backups/
  storage/logs/
  ```

---

## Phase 7: Documentation & Training (15 minutes)

### ✓ Documentation Created
- [ ] ✅ `SQLITE_BACKUP_RESTORE_SYSTEM.md` (comprehensive 450+ lines)
- [ ] ✅ `BACKUP_QUICK_REFERENCE.md` (quick start 280+ lines)
- [ ] ✅ `BACKUP_IMPLEMENTATION_INTEGRATION.md` (integration 380+ lines)
- [ ] ✅ `SQLITE_BACKUP_SYSTEM_SUMMARY.md` (executive summary)
- [ ] ✅ This checklist file

### ✓ Team Training
- [ ] Share documentation with team
- [ ] Walkthrough backup creation process
- [ ] Walkthrough restore process
- [ ] Discuss emergency recovery procedures
- [ ] Q&A session

### ✓ Runbooks Created
- [ ] Create runbook: "How to Create Manual Backup"
- [ ] Create runbook: "How to Restore from Backup"
- [ ] Create runbook: "How to Troubleshoot Failed Backup"
- [ ] Create runbook: "How to Recover from Backup Failure"

---

## Phase 8: Production Deployment (1-2 hours)

### ✓ Pre-Deployment Checklist
- [ ] All tests passed
- [ ] Queue worker running
- [ ] Scheduler configured
- [ ] Encryption key set
- [ ] Storage directories created
- [ ] Permissions correct
- [ ] Logging configured
- [ ] Team trained

### ✓ Deployment Steps
1. [ ] Commit code to repository
   ```bash
   git add -A
   git commit -m "feat: SQLite backup and restore system"
   git push origin main
   ```

2. [ ] Deploy to production
   ```bash
   cd /path/to/irms
   git pull origin main
   php artisan migrate --force
   ```

3. [ ] Start services
   ```bash
   # If using supervisor
   sudo supervisorctl start irms-backup-worker:*
   
   # Or start manually
   php artisan queue:work --queue=backups &
   ```

4. [ ] Verify services running
   ```bash
   ps aux | grep "queue:work\|schedule:run"
   ps aux | grep supervisor
   ```

5. [ ] Test in production
   ```bash
   curl https://production.example.com/api/backups/status \
     -H "Authorization: Bearer $PROD_TOKEN"
   ```

6. [ ] Monitor logs
   ```bash
   tail -f storage/logs/laravel.log | grep -i backup
   ```

### ✓ Post-Deployment Monitoring
- [ ] Monitor backup creation (first daily run at 1:00 AM)
- [ ] Verify audit logs updated
- [ ] Check storage utilization
- [ ] Confirm queue worker healthy
- [ ] Test restore simulation (day 2)
- [ ] Review for errors/warnings

---

## Phase 9: Ongoing Maintenance

### Daily Checklist
- [ ] Check logs for backup errors: `grep backup-failed storage/logs/laravel.log`
- [ ] Verify last backup completed: `App\Models\BackupLog::backupOperations()->successful()->latest()->first()`
- [ ] Confirm queue worker running: `ps aux | grep queue:work`

### Weekly Checklist
- [ ] Review backup logs
- [ ] Check storage usage: `du -sh storage/backups/`
- [ ] Verify encryption key not compromised
- [ ] Test restore simulation

### Monthly Checklist
- [ ] Run full disaster recovery drill
- [ ] Review and update documentation
- [ ] Audit access logs
- [ ] Delete old backups (>90 days)
- [ ] Update retention policy if needed

### Quarterly Checklist
- [ ] Test actual restore to staging
- [ ] Security assessment
- [ ] Performance optimization review
- [ ] Compliance audit

---

## Troubleshooting Reference

### Issue: Backup fails with "Database did not reach quiescence"
**Solution:**
```bash
# Check for active connections
sqlite3 database/database.sqlite "PRAGMA busy_timeout = 5000;"

# Wait for transactions to complete, then retry
```

### Issue: Cannot decrypt backup file
**Solution:**
```bash
# Verify encryption key matches
echo $BACKUP_ENCRYPTION_KEY

# Ensure file not corrupted
file storage/backups/sqlite/*.zip.enc

# Check file size is reasonable
ls -lah storage/backups/sqlite/
```

### Issue: Queue worker not processing jobs
**Solution:**
```bash
# Check queue has jobs
sqlite3 database/database.sqlite "SELECT COUNT(*) FROM jobs;"

# Start/restart worker
php artisan queue:work --queue=backups

# Check failed jobs
php artisan queue:failed
```

### Issue: Restore simulation fails
**Solution:**
```bash
# Validate backup first
curl -X POST /api/backups/validate

# Check disk space for sandbox
df -h storage/backups/sandbox/

# Check logs for detailed error
tail -f storage/logs/laravel.log | grep -i restore
```

---

## Sign-Off

- [ ] All phases complete
- [ ] All tests passed
- [ ] Documentation reviewed
- [ ] Team trained
- [ ] Monitoring configured
- [ ] Deployment successful
- [ ] Production validation complete

**Deployment Date:** ___________  
**Deployed By:** ___________  
**Approved By:** ___________

---

## Contact & Support

- **Documentation:** See SQLITE_BACKUP_RESTORE_SYSTEM.md
- **Quick Reference:** See BACKUP_QUICK_REFERENCE.md
- **Issues:** Review Troubleshooting Reference above
- **Support:** contact@example.com

---

**Checklist Version:** 1.0  
**Last Updated:** 2025-02-02  
**Status:** Ready for Implementation ✅
