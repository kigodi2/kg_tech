# Backup & Restore System - Deployment Checklist

## Pre-Deployment

- [ ] Review BACKUP_RESTORE_SYSTEM.md
- [ ] Review BACKUP_QUICKSTART.md
- [ ] Test on development environment
- [ ] Review database migration
- [ ] Check storage directory permissions
- [ ] Verify backup key security config

## Deployment Steps

### 1. Database Migration
```bash
php artisan migrate
```
- [ ] Confirms "backups table created"
- [ ] No errors in migration
- [ ] Check database: `SELECT * FROM backups LIMIT 0;`

### 2. Cache Clear
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```
- [ ] All caches cleared
- [ ] No PHP errors
- [ ] Admin panel accessible

### 3. Directory Setup
```bash
mkdir -p storage/app/backups
mkdir -p storage/app/temp/backups
chmod 755 storage/app/backups
chmod 755 storage/app/temp/backups
```
- [ ] Directories created
- [ ] Permissions set
- [ ] Writable by web server user

### 4. Verify File Permissions
```bash
ls -la storage/app/ | grep backup
ls -la storage/app/temp/ | grep backup
```
- [ ] Both directories exist
- [ ] Both are readable/writable
- [ ] No permission denied errors

## Post-Deployment

### 1. Admin Panel Verification
- [ ] Navigate to `/admin`
- [ ] Check navigation dropdown (SETTINGS)
- [ ] See "Backups & Restore" option
- [ ] Click and verify page loads

### 2. Create Test Backup
- [ ] Click "Create Backup"
- [ ] Select "Metadata Only" type
- [ ] Add note: "Test backup"
- [ ] Check all pre-backup checklist items
- [ ] Click "Create Backup"
- [ ] Verify success notification
- [ ] Check backup appears in list

### 3. Verify Backup File
```bash
ls -lh storage/app/backups/
unzip -t storage/app/backups/irms-backup-metadata-only-*.zip
```
- [ ] Backup file created
- [ ] File size reasonable (1-5MB for metadata)
- [ ] ZIP structure valid
- [ ] Required files present

### 4. View Backup Details
- [ ] Click on test backup in list
- [ ] Verify all fields display
- [ ] Check manifest shows correct type
- [ ] Verify status: "Verified"
- [ ] Check admin name matches current user

### 5. Test Download
- [ ] Click "Download" button
- [ ] File downloads successfully
- [ ] File matches server version
- [ ] Checksum matches (compare first 16 chars)

### 6. Check Audit Logs
- [ ] Navigate to `/admin/governance-audit-logs`
- [ ] Filter by recent backups
- [ ] Should see "backup_created" entries
- [ ] Verify admin_id and timestamp correct

### 7. Database Record Check
```bash
php artisan tinker
>>> \App\Models\Backup::latest()->first();
```
- [ ] Backup record exists in database
- [ ] All fields populated
- [ ] Checksum present and valid
- [ ] Verified status true

## Validation Tests

### Test 1: Metadata Backup
- [ ] Type: Metadata Only
- [ ] Size: 1-5MB
- [ ] Contains: users, roles, regions, schools
- [ ] No candidate/mark data

### Test 2: Exam Year Backup
- [ ] Type: Exam Year
- [ ] Select exam year
- [ ] Size: 10-50MB (depending on year)
- [ ] Contains exam-scoped data

### Test 3: Full System Backup
- [ ] Type: Full System
- [ ] Size: 100-500MB
- [ ] Contains all tables
- [ ] May take 30-60 seconds

### Test 4: Backup Integrity
```bash
cd storage/app/backups
unzip -t irms-backup-*.zip
```
- [ ] ZIP extracts cleanly
- [ ] No corruption errors
- [ ] All files present
- [ ] manifest.json valid JSON
- [ ] checksums.json valid JSON
- [ ] database.sql valid SQL

### Test 5: Signature Verification
```php
$backup = \App\Models\Backup::latest()->first();
$backupService = app(\App\Services\BackupService::class);
$valid = $backupService->verifySignature($backup);
// Should return true
```
- [ ] Signature verification passes
- [ ] Checksum verification passes

## Security Validation

### Test 1: Admin-Only Access
- [ ] Login as non-admin
- [ ] Try to access `/admin/backups`
- [ ] Should be denied (403 or redirect)
- [ ] Logout

### Test 2: Create Button Disabled for Non-Admin
- [ ] Login as non-admin
- [ ] Should not see backups in nav
- [ ] Should not access `/admin/backups/create`

### Test 3: Audit Trail Complete
- [ ] Check governance_audit_logs table
- [ ] Find backup_created entries
- [ ] Verify admin_id
- [ ] Verify timestamp
- [ ] Verify data payload

## Optional: Restore Test

⚠️ **ONLY DO THIS ON DEV/TEST SYSTEM**

- [ ] Create test backup
- [ ] Make change to database
- [ ] Navigate to backup
- [ ] Click "Restore"
- [ ] Read warnings carefully
- [ ] Type "RESTORE"
- [ ] Check confirmation boxes
- [ ] Watch for automatic snapshot
- [ ] Verify restore completes
- [ ] Check data reverted
- [ ] Check pre-restore snapshot created
- [ ] Check audit log shows restore_completed

## Production Sign-Off

- [ ] All tests passed
- [ ] No errors in logs
- [ ] Admin training completed
- [ ] Documentation reviewed
- [ ] Backup procedures documented
- [ ] Recovery procedures documented
- [ ] Scheduled backup plan established (if desired)

## Monitoring Recommendations

### Daily
- [ ] Review audit logs for backup activity
- [ ] Verify backups are being created (if automated)

### Weekly
- [ ] Test restore procedure on test data
- [ ] Verify backup file integrity

### Monthly
- [ ] Full system backup created
- [ ] Restore test on test environment
- [ ] Review backup storage usage
- [ ] Archive old backups (off-site)

## Troubleshooting

### Backup Creation Fails
```bash
# Check storage directory
ls -la storage/app/
ls -la storage/app/backups/
ls -la storage/app/temp/

# Check permissions
chmod 755 storage/app/backups
chmod 755 storage/app/temp/backups

# Check disk space
df -h storage/

# Check logs
tail -f storage/logs/laravel.log
```

### Restore Validation Fails
```bash
# Verify backup integrity
unzip -t storage/app/backups/filename.zip

# Check file permissions
ls -l storage/app/backups/filename.zip

# Verify checksum
sha256sum storage/app/backups/filename.zip
# Compare with database checksum
php artisan tinker
>>> \App\Models\Backup::find($id)->checksum;
```

### Admin Cannot Access
```bash
# Verify user is admin
php artisan tinker
>>> auth()->login(\App\Models\User::find(1));
>>> auth()->user()->isAdmin();  // Should be true
>>> auth()->user()->role->code;  // Should be 'admin'
```

## Documentation Location

- Technical: `/BACKUP_RESTORE_SYSTEM.md`
- User Guide: `/BACKUP_QUICKSTART.md`
- Implementation: `/BACKUP_IMPLEMENTATION_SUMMARY.md`
- Deployment: `/BACKUP_DEPLOYMENT_CHECKLIST.md` (this file)

## Support Contact

For issues or questions:
1. Check documentation files
2. Review audit logs
3. Check server logs: `storage/logs/laravel.log`
4. Contact system administrator

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Approved By:** _______________
**Notes:** _______________________________

---

**System:** IRMS v1.0
**Compliance:** NECTA/NACTVET Standards
**Status:** Ready for Production
