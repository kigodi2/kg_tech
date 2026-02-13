# Production Deployment Checklist

**System**: Hardened Restore System  
**Date**: 2026-02-02  
**Environment**: Production  
**Status**: Ready for deployment

---

## ✅ PRE-DEPLOYMENT (Before You Start)

- [ ] All team members notified of deployment
- [ ] Maintenance window scheduled (if needed)
- [ ] Backup of current database created
- [ ] Rollback plan documented
- [ ] Deployment team available for support
- [ ] Stakeholders notified of new restore system

---

## ✅ DEPLOYMENT PHASE 1: Code Deployment

### Files Copied to Production

- [ ] `app/Models/RestoreAuditLog.php`
- [ ] `app/Services/HardenedRestoreService.php`
- [ ] `app/Policies/HardenedRestorePolicy.php`
- [ ] `app/Http/Controllers/HardenedRestoreController.php`
- [ ] `app/Filament/Admin/Pages/HardenedRestore.php`
- [ ] `routes/hardened-restore.php`
- [ ] `resources/views/filament/admin/pages/hardened-restore.blade.php`
- [ ] `database/migrations/2026_02_02_000000_create_restore_audit_logs_table.php`

### Configuration Updates

- [ ] `app/Providers/AppServiceProvider.php` - Service registration added
- [ ] `routes/api.php` - Routes included

### File Permissions

- [ ] All PHP files readable by web server
- [ ] Migration file readable by PHP
- [ ] Views directory accessible to Filament
- [ ] No world-writable files

---

## ✅ DEPLOYMENT PHASE 2: Database Setup

### Run Migration

```bash
php artisan migrate
```

- [ ] Migration executed successfully
- [ ] No errors in output
- [ ] restore_audit_logs table created
- [ ] All 20 columns present
- [ ] Indexes created

### Verify Database

```bash
php artisan tinker
>>> DB::table('restore_audit_logs')->count()  # Should return 0
>>> DB::select("PRAGMA table_info(restore_audit_logs)")  # Should show 20 columns
```

- [ ] Table has correct structure
- [ ] Initial record count is 0
- [ ] Columns match specification

---

## ✅ DEPLOYMENT PHASE 3: Service Registration

### Verify Service Registration

In `app/Providers/AppServiceProvider.php`:

```php
$this->app->singleton(HardenedRestoreService::class, function ($app) {
    return new HardenedRestoreService(
        $app->make(SQLiteBackupService::class)
    );
});
```

- [ ] Code added to AppServiceProvider
- [ ] Service imports added
- [ ] Syntax is correct (no PHP errors)

### Test Service

```bash
php artisan tinker
>>> $service = app(\App\Services\HardenedRestoreService::class)
>>> get_class($service)  # Should return HardenedRestoreService class
```

- [ ] Service instantiates without error
- [ ] Service class correct

---

## ✅ DEPLOYMENT PHASE 4: Route Configuration

### Verify Routes Added

In `routes/api.php`:

```php
require base_path('routes/hardened-restore.php');
```

- [ ] Line added to api.php
- [ ] Path is correct

### Clear and Cache Routes

```bash
php artisan route:clear
php artisan route:cache
```

- [ ] Route cache cleared successfully
- [ ] Routes cached successfully
- [ ] No errors in output

### Verify Routes Registered

```bash
php artisan route:list | grep -i restore
```

- [ ] Shows 6 restore API routes
- [ ] All routes are api/* routes
- [ ] Methods correct (GET/POST)

---

## ✅ DEPLOYMENT PHASE 5: Authorization Setup

### Verify Policy File Exists

- [ ] `app/Policies/HardenedRestorePolicy.php` exists
- [ ] File is readable

### Test Authorization

```bash
php artisan tinker
>>> $policy = new \App\Policies\HardenedRestorePolicy()
>>> $admin = \App\Models\User::where('is_admin', true)->first()
>>> $policy->restoreFullSystem($admin)  # Should return true
```

- [ ] Policy instantiates
- [ ] Authorization checks work
- [ ] Admin can restore

---

## ✅ DEPLOYMENT PHASE 6: Filament Integration

### Verify Page File Exists

- [ ] `app/Filament/Admin/Pages/HardenedRestore.php` exists
- [ ] File is readable

### Verify Template File Exists

- [ ] `resources/views/filament/admin/pages/hardened-restore.blade.php` exists
- [ ] File is readable

### Test Page Access

1. Log into admin panel: `http://localhost:8000/admin`
2. Look for "Restore Database" in sidebar
3. Click to access the page

- [ ] Page loads without error
- [ ] Shows Step 1 (Select Backup)
- [ ] Progress indicator visible
- [ ] Input fields present

---

## ✅ DEPLOYMENT PHASE 7: API Testing

### Test 1: Get Legal Text

```bash
curl -X GET http://localhost:8000/api/restore/legal-text \
  -H "Authorization: Bearer YOUR_TOKEN"
```

- [ ] Returns 200 OK
- [ ] Response includes legal_text
- [ ] Legal text is complete

### Test 2: Validate Backup

Create a test backup first:
```bash
php artisan backup:create
```

Then:
```bash
curl -X POST http://localhost:8000/api/restore/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"backup_path":"storage/backups/irms-backup-full-system-YYYY-MM-DD_HHMMSS.zip"}'
```

- [ ] Returns 200 OK
- [ ] Response shows validation result
- [ ] Shows errors or success correctly

### Test 3: Get Audit Logs

```bash
curl -X GET http://localhost:8000/api/restore/audit-logs \
  -H "Authorization: Bearer YOUR_TOKEN"
```

- [ ] Returns 200 OK
- [ ] Shows pagination info
- [ ] Data array present (may be empty)

---

## ✅ DEPLOYMENT PHASE 8: Security Verification

### Authorization Checks

- [ ] Only admins can access restore page
- [ ] Non-admins get access denied
- [ ] API endpoints require authentication
- [ ] Bearer token validation working

### CSRF Protection

- [ ] Filament forms protected by CSRF
- [ ] API requests validate tokens

### Database Permissions

- [ ] restore_audit_logs table has proper constraints
- [ ] Immutable design (no update_at column)
- [ ] Foreign keys enforced

---

## ✅ DEPLOYMENT PHASE 9: Backup Verification

### Quarantine Directory

```bash
mkdir -p storage/backups/quarantine
chmod 750 storage/backups/quarantine
```

- [ ] Quarantine directory exists
- [ ] Proper permissions set (750)
- [ ] Writable by PHP process

### Test Backup Creation

```bash
php artisan backup:create
ls -la storage/backups/
```

- [ ] Backup file created
- [ ] File is readable
- [ ] Filename matches expected pattern

---

## ✅ DEPLOYMENT PHASE 10: Documentation Verification

All documentation files present:

- [ ] HARDENED_RESTORE_DEPLOYMENT_SUMMARY.md
- [ ] HARDENED_RESTORE_SYSTEM.md
- [ ] HARDENED_RESTORE_QUICKSTART.md
- [ ] HARDENED_RESTORE_REFERENCE.md
- [ ] HARDENED_RESTORE_VERIFICATION.md
- [ ] HARDENED_RESTORE_INDEX.md
- [ ] HARDENED_RESTORE_FILAMENT_INTEGRATION.md
- [ ] HARDENED_RESTORE_UI_COMPLETE.md
- [ ] DEPLOYMENT_FINAL_SUMMARY.md
- [ ] OPERATOR_TRAINING_GUIDE.md
- [ ] DEPLOYMENT_VERIFICATION_SCRIPT.php

---

## ✅ DEPLOYMENT PHASE 11: Operator Training

### Training Materials Prepared

- [ ] Printed HARDENED_RESTORE_REFERENCE.md
- [ ] Printed OPERATOR_TRAINING_GUIDE.md
- [ ] Test environment ready for practice
- [ ] Training schedule created

### Training Completed

- [ ] All operators trained
- [ ] Certification checklist signed
- [ ] Questions answered
- [ ] Hands-on practice completed

---

## ✅ DEPLOYMENT PHASE 12: Production Testing

### Test with Production-Like Data

1. Create backup: `php artisan backup:create`
2. Access UI: http://localhost:8000/admin/hardened-restore
3. Enter backup path
4. Click "Validate Backup"
5. Complete all 4 steps
6. Verify success page shows

- [ ] Validation passes
- [ ] Legal text displays correctly
- [ ] 3 required confirmations work
- [ ] Restore completes successfully
- [ ] Audit log recorded

### Verify Audit Trail

```bash
php artisan tinker
>>> \App\Models\RestoreAuditLog::latest()->first()
# Should show your recent restore
```

- [ ] Audit log entry created
- [ ] All fields populated correctly
- [ ] Status correct

---

## ✅ POST-DEPLOYMENT: Monitoring

### First Week

- [ ] Monitor application logs daily
- [ ] Check error logs: storage/logs/laravel.log
- [ ] Monitor database performance
- [ ] Verify no unexpected errors

### First Restore Operation (When Needed)

1. Before restore:
   - [ ] Notify all stakeholders
   - [ ] Create backup of current data
   - [ ] Document the reason

2. During restore:
   - [ ] Monitor application logs
   - [ ] Ensure system stays online
   - [ ] Watch for errors

3. After restore:
   - [ ] Verify data integrity
   - [ ] Test key functionality
   - [ ] Export audit log
   - [ ] Document completion

---

## ✅ ROLLBACK PLAN (If Needed)

If deployment has critical issues:

### Rollback Procedure

1. Stop application (if needed)
2. Remove deployed files:
   ```bash
   rm app/Models/RestoreAuditLog.php
   rm app/Services/HardenedRestoreService.php
   rm app/Policies/HardenedRestorePolicy.php
   rm app/Http/Controllers/HardenedRestoreController.php
   rm app/Filament/Admin/Pages/HardenedRestore.php
   rm routes/hardened-restore.php
   rm resources/views/filament/admin/pages/hardened-restore.blade.php
   ```
3. Revert configuration changes:
   - Undo AppServiceProvider changes
   - Undo routes/api.php changes
4. Rollback migration:
   ```bash
   php artisan migrate:rollback
   ```
5. Clear caches:
   ```bash
   php artisan cache:clear
   php artisan route:clear
   ```
6. Start application

- [ ] Rollback procedure documented
- [ ] Team briefed on rollback process
- [ ] Database backup available for recovery

---

## ✅ SIGN-OFF

### Deployment Manager

- [ ] Name: ________________
- [ ] Date: ________________
- [ ] Signature: ________________

### System Administrator

- [ ] Name: ________________
- [ ] Date: ________________
- [ ] Signature: ________________

### Operations Lead

- [ ] Name: ________________
- [ ] Date: ________________
- [ ] Signature: ________________

---

## 📝 Notes & Issues

```
[Space for documenting any issues during deployment]




```

---

## ✨ Deployment Complete

Once all checks are complete and signed off:

- ✅ System is live in production
- ✅ All features operational
- ✅ All staff trained
- ✅ Audit trail active
- ✅ Ready for use

**Congratulations on successful deployment! 🎉**

---

## 📞 Post-Deployment Support

**Issue discovered?** Contact: ________________  
**Training needed?** Contact: ________________  
**Emergency?** Contact: ________________  

---

**Deployment Date**: ________________  
**Production Go-Live**: ________________  
**First Restore Date** (when applicable): ________________
