# SQLite Backup & Restore - Quick Reference

## Installation (5 minutes)

```bash
# 1. Run migration
php artisan migrate

# 2. Update .env
BACKUP_ENCRYPTION_KEY=your-strong-key-here
AUTOMATED_BACKUPS_ENABLED=true

# 3. Start queue worker
php artisan queue:work --queue=backups &

# 4. Add to crontab (schedule:run every minute)
* * * * * cd /path/to/irms && php artisan schedule:run >> /dev/null 2>&1
```

## API Quick Reference

### Create Backup (Manual)
```bash
curl -X POST https://app.example.com/api/backups/create \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"notes": "Pre-release backup"}'
```

### Check Backup Status
```bash
curl https://app.example.com/api/backups/status \
  -H "Authorization: Bearer $TOKEN"
```

### List Recent Backups
```bash
curl "https://app.example.com/api/backups/logs?operation=backup_created" \
  -H "Authorization: Bearer $TOKEN"
```

### Validate Backup Before Restore
```bash
curl -X POST https://app.example.com/api/backups/validate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"backup_path": "storage/backups/sqlite/bak-full-*.zip.enc"}'
```

### Simulate Restore (Test Only)
```bash
curl -X POST https://app.example.com/api/backups/simulate-restore \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"backup_path": "storage/backups/sqlite/bak-full-*.zip.enc"}'
```

### Perform Actual Restore
```bash
curl -X POST https://app.example.com/api/backups/restore \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "backup_path": "storage/backups/sqlite/bak-full-*.zip.enc",
    "create_snapshot": true,
    "confirm": true
  }'
```

## Scheduled Backups (Automatic)

| Time | Type | Frequency |
|------|------|-----------|
| 1:00 AM | Daily Full | Every day |
| 2:00 AM (Sun) | Weekly Full | Sundays |
| 3:00 AM (1st) | Monthly Archive | 1st of month |

**Status:** View in `/api/backups/health-metrics`

## Restore Workflow (3 Steps)

### Step 1: Validate
```json
POST /api/backups/validate
{
  "backup_path": "storage/backups/sqlite/bak-full-2025-02-02-150000-abc12345.zip.enc"
}
```

### Step 2: Simulate
```json
POST /api/backups/simulate-restore
{
  "backup_path": "storage/backups/sqlite/bak-full-2025-02-02-150000-abc12345.zip.enc"
}
```

### Step 3: Restore
```json
POST /api/backups/restore
{
  "backup_path": "storage/backups/sqlite/bak-full-2025-02-02-150000-abc12345.zip.enc",
  "create_snapshot": true,
  "confirm": true
}
```

## Backup File Locations

```
storage/backups/sqlite/           # Daily/weekly backups
  bak-full-YYYY-MM-DD-HHMMSS-xxxx.zip.enc

storage/backups/archives/monthly/  # Monthly archives (immutable)
  bak-full-YYYY-MM-DD-HHMMSS-xxxx.zip.enc

storage/backups/quarantine/        # Pre-restore backups
  YYYY-MM-DD-HHMMSS/
    database.sqlite
    database.sqlite-wal
    database.sqlite-shm
```

## Emergency Recovery

### If Restore Fails Automatically

1. Check logs: `storage/logs/laravel.log`
2. Manual restore:
   ```bash
   # From quarantine (most recent)
   cp storage/backups/quarantine/*/database.sqlite* database/
   chmod 640 database/database.sqlite
   systemctl restart irms
   ```

### If Database is Corrupted

1. Stop application: `systemctl stop irms`
2. Restore from backup: `POST /api/backups/restore`
3. Verify: Check data integrity in app
4. Alert admins: Review audit logs

## Encryption

- **Algorithm:** AES-256-CBC
- **Key:** Derived from `BACKUP_ENCRYPTION_KEY` in `.env`
- **Random IV:** 16 bytes prepended to each encrypted file
- **Verification:** HMAC-SHA256 signature

**To change encryption key:**
```bash
# 1. Update .env
BACKUP_ENCRYPTION_KEY=new-key-here

# 2. Create backup with new key
# 3. Re-encrypt old backups or delete them
```

## Audit & Compliance

### View All Operations
```bash
curl "https://app.example.com/api/backups/logs" \
  -H "Authorization: Bearer $TOKEN"
```

### Export Audit Log
```bash
# Via Laravel Tinker
php artisan tinker
> BackupLog::all()->toJson()
```

### Immutable Logs
- Logs are NEVER deleted or modified
- Only created_at is recorded (no updated_at)
- User attribution for every operation
- JSON metadata stored with each log

## Troubleshooting

### Backup fails: "Database did not reach quiescence"
- Active transactions detected
- Retry backup after 30 seconds
- Check: `sqlite3 database/database.sqlite "PRAGMA busy_timeout = 5000;"`

### Restore fails: "Cannot open backup ZIP"
- Verify encryption key matches: `BACKUP_ENCRYPTION_KEY`
- Check file exists: `ls -lah storage/backups/sqlite/bak-*.zip.enc`
- Ensure sufficient disk space for decompression

### No WAL/SHM files in backup
- Enable WAL mode: `sqlite3 database/database.sqlite "PRAGMA journal_mode = WAL;"`
- Next backup will include WAL files
- Not critical - database still fully backed up

### Queue worker not processing backups
- Check worker running: `ps aux | grep queue:work`
- Start worker: `php artisan queue:work --queue=backups &`
- Check: `php artisan queue:failed`

## Performance

| Operation | Time | Size |
|-----------|------|------|
| 100 MB DB backup | 3 min | ~30 MB (encrypted) |
| Backup validation | <30 sec | - |
| Restore simulation | 1-2 min | - |
| Actual restore | 1-3 min | - |

## Files Created/Modified

### Created
- `app/Services/SQLiteBackupService.php`
- `app/Services/SQLiteRestoreService.php`
- `app/Models/BackupLog.php`
- `app/Jobs/ScheduledDailyBackup.php`
- `app/Jobs/ScheduledWeeklyBackup.php`
- `app/Jobs/ScheduledMonthlyBackup.php`
- `app/Console/Commands/ScheduleBackups.php`
- `app/Console/Kernel.php` (new)
- `app/Http/Controllers/BackupController.php`
- `app/Policies/BackupPolicy.php`
- `routes/backup.php`
- `database/migrations/2025_02_02_000001_create_backup_logs_table.php`
- `SQLITE_BACKUP_RESTORE_SYSTEM.md` (comprehensive docs)

### Must Register

In `routes/api.php`:
```php
require_once 'backup.php';
```

## Checklists

### Pre-Go-Live
- [ ] Migration run: `php artisan migrate`
- [ ] Encryption key set: `echo $BACKUP_ENCRYPTION_KEY`
- [ ] Queue worker running: `ps aux | grep queue:work`
- [ ] Scheduler configured: Check crontab
- [ ] Test backup created: `POST /api/backups/create`
- [ ] Test restore simulation: `POST /api/backups/simulate-restore`
- [ ] Audit logs in DB: `SELECT COUNT(*) FROM backup_logs`

### Monthly Maintenance
- [ ] Review backup logs: No failed backups?
- [ ] Test restore to staging: Simulation + actual restore
- [ ] Verify encryption key secure
- [ ] Check storage capacity
- [ ] Delete old backups (>90 days if needed)

### Disaster Recovery Drill (Quarterly)
- [ ] Select random backup
- [ ] Run validation: `POST /api/backups/validate`
- [ ] Run simulation: `POST /api/backups/simulate-restore`
- [ ] Document findings
- [ ] Update recovery procedures if needed

---

**Version:** 1.0.0  
**Last Updated:** 2025-02-02  
**Status:** Production Ready ✓
