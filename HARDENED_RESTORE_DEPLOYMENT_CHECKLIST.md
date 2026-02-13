# Hardened Restore System - Deployment Checklist

**Date**: 2024-12-01  
**Version**: 1.0  
**Status**: Ready for Deployment

---

## Pre-Deployment Verification

### Code Review
- [ ] HardenedRestoreService.php reviewed
- [ ] RestoreAuditLog model reviewed
- [ ] Policies reviewed (BackupPolicy + RestoreAuditLogPolicy)
- [ ] Filament resources reviewed
- [ ] Views reviewed
- [ ] Migration reviewed
- [ ] No security vulnerabilities identified
- [ ] Code follows project standards

### Testing
- [ ] Unit tests passed (if applicable)
- [ ] Integration tests passed
- [ ] Access control tested
- [ ] Rollback scenario tested
- [ ] Quarantine system tested
- [ ] Audit log immutability verified

### Documentation
- [ ] HARDENED_RESTORE_SYSTEM_IMPLEMENTATION.md complete
- [ ] HARDENED_RESTORE_QUICKSTART.md complete
- [ ] HARDENED_RESTORE_DELIVERY_SUMMARY.md complete
- [ ] All inline code comments clear
- [ ] No outdated documentation

---

## Deployment Steps

### Phase 1: File Deployment (15 min)

#### Core Service
```bash
[ ] Copy app/Services/HardenedRestoreService.php
    Location: /app/app/Services/HardenedRestoreService.php
    Verify: file exists and is readable
```

#### Models
```bash
[ ] Copy app/Models/RestoreAuditLog.php
    Location: /app/app/Models/RestoreAuditLog.php
    Verify: file exists and is readable
```

#### Policies
```bash
[ ] Copy app/Policies/RestoreAuditLogPolicy.php
    Location: /app/app/Policies/RestoreAuditLogPolicy.php
    Verify: file exists and is readable

[ ] Update app/Policies/BackupPolicy.php
    Method: restore() (see reference doc)
    Verify: changes match specification
```

#### Filament Resources
```bash
[ ] Copy app/Filament/Resources/RestoreAuditLogResource.php
    Location: /app/app/Filament/Resources/RestoreAuditLogResource.php
    Verify: file exists and is readable
```

#### Filament Pages
```bash
[ ] Copy app/Filament/Pages/HardenedRestoreBackup.php
    Location: /app/app/Filament/Pages/HardenedRestoreBackup.php
    Verify: file exists and is readable
```

#### Views
```bash
[ ] Copy resources/views/filament/pages/hardened-restore-backup.blade.php
    Location: /app/resources/views/filament/pages/hardened-restore-backup.blade.php
    Verify: file exists and is readable

[ ] Copy resources/views/components/restore-legal-warning.blade.php
    Location: /app/resources/views/components/restore-legal-warning.blade.php
    Verify: file exists and is readable

[ ] Copy resources/views/components/restore-audit-notice.blade.php
    Location: /app/resources/views/components/restore-audit-notice.blade.php
    Verify: file exists and is readable
```

#### Migration
```bash
[ ] Copy database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php
    Location: /app/database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php
    Verify: file exists and is readable
```

### Phase 2: Database Migration (5 min)

```bash
[ ] Run migration
    Command: php artisan migrate
    Expected: "Migrated: 2024_12_01_000000_create_restore_audit_logs"

[ ] Verify migration success
    Command: php artisan tinker
    >>> Schema::hasTable('restore_audit_logs')
    Expected output: true

[ ] Verify table structure
    Command: php artisan tinker
    >>> Schema::getColumns('restore_audit_logs')
    Expected: 20 columns with correct types
```

### Phase 3: Cache Clearing (2 min)

```bash
[ ] Clear application cache
    Command: php artisan cache:clear
    Expected: "Application cache cleared"

[ ] Rebuild configuration cache
    Command: php artisan config:cache
    Expected: "Configuration cached successfully"

[ ] Clear route cache
    Command: php artisan route:cache
    Expected: "Routes cached successfully"

[ ] Rebuild class map (optional but recommended)
    Command: composer dump-autoload
    Expected: "Generated optimized autoload files"
```

### Phase 4: Filament Integration (5 min)

```bash
[ ] Register RestoreAuditLogResource in FilamentServiceProvider
    File: app/Providers/FilamentServiceProvider.php
    Add: RestoreAuditLogResource::class to resources array

[ ] Register HardenedRestoreBackup page in admin panel
    File: app/Providers/Filament/AdminPanelProvider.php
    Add: ->pages([...HardenedRestoreBackup::class])

[ ] Clear cache again after Filament changes
    Command: php artisan cache:clear
```

### Phase 5: Verification (10 min)

#### File Existence
```bash
[ ] Verify all files deployed
    Command: find /app -name "*Restore*" -o -name "*AuditLog*"
    Expected: 11 files listed
```

#### Database
```bash
[ ] Verify database table
    Command: php artisan tinker
    >>> RestoreAuditLog::count()
    Expected: 0 (no restores yet)
```

#### Authorization
```bash
[ ] Verify policy exists
    Command: php artisan tinker
    >>> app('Illuminate\Auth\Access\Gate')->getPolicies()
    Expected: RestoreAuditLog policy listed
```

#### Web Access
```bash
[ ] Visit Filament admin
    URL: /admin
    Expected: Admin panel loads

[ ] Check navigation
    Look for: "Backups & Restore" menu
    Look for: "System Administration" menu

[ ] Verify pages accessible
    URL: /admin/hardened-restore-backup
    Expected: Legal warning displayed

    URL: /admin/resources/restore-audit-logs
    Expected: Empty table (no restores yet)
```

### Phase 6: Role-Based Testing (15 min)

#### Super Admin Access
```bash
[ ] Login as super admin
    [ ] Can access /admin/hardened-restore-backup
    [ ] Can see all backups in dropdown
    [ ] Can see 2FA authorizer selector
    [ ] Can access /admin/resources/restore-audit-logs
    [ ] Can see all audit logs
```

#### Regional Admin Access
```bash
[ ] Login as regional admin
    [ ] Can access /admin/hardened-restore-backup
    [ ] Can see ONLY region backups in dropdown
    [ ] Cannot see 2FA authorizer selector
    [ ] Can access /admin/resources/restore-audit-logs
    [ ] Can see ONLY region audit logs
```

#### District Admin Access
```bash
[ ] Login as district admin
    [ ] Can access /admin/hardened-restore-backup
    [ ] Can see ONLY district backups in dropdown
    [ ] Cannot see 2FA authorizer selector
    [ ] Can access /admin/resources/restore-audit-logs
    [ ] Can see ONLY district audit logs
```

#### Other Roles Access
```bash
[ ] Login as user (non-admin)
    [ ] Cannot access /admin/hardened-restore-backup
    [ ] Cannot access /admin/resources/restore-audit-logs
    [ ] Get "Access Denied" error (or 403)
```

### Phase 7: Legal Warning Verification (5 min)

```bash
[ ] Display legal warning
    Action: Visit /admin/hardened-restore-backup as super admin
    Verify: See NECTA-style warning
    Verify: "CRITICAL WARNING" header
    Verify: Bullet points for data loss
    Verify: Authorization requirement section

[ ] Require legal acknowledgment
    Action: Try to submit without checking legal box
    Verify: Form validation prevents submission
    Error: "You must acknowledge the legal implications"

[ ] Enable submission with acknowledgment
    Action: Check legal box
    Verify: Submit button becomes enabled
```

### Phase 8: Audit Log Testing (10 min)

```bash
[ ] Create test restore audit log manually
    Command: php artisan tinker
    >>> App\Models\RestoreAuditLog::create([
        'user_id' => 1,
        'backup_id' => 'test-123',
        'backup_filename' => 'test.zip',
        'backup_hash' => 'abc123',
        'scope_type' => 'full',
        'legal_acknowledgment' => 'Test',
        'legal_acknowledged' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test',
        'status' => 'initiated',
        'initiated_at' => now(),
    ])

[ ] Verify audit log created
    URL: /admin/resources/restore-audit-logs
    Verify: Test entry appears in table

[ ] Verify immutability
    Command: php artisan tinker
    >>> $log = RestoreAuditLog::first()
    >>> $log->update(['status' => 'completed'])
    Expected: Fails (policy prevents update)

[ ] View audit log details
    Action: Click on audit log entry
    Verify: See all details (operator, backup, reason, etc.)
    Verify: No edit option available
```

---

## Post-Deployment

### Cleanup
```bash
[ ] Remove documentation deployment notes
[ ] Remove temporary test data
[ ] Verify storage/app/quarantine/ directory exists
    Command: mkdir -p storage/app/quarantine
    Verify: directory created with 755 permissions

[ ] Verify storage/app/backups/ directory exists
    Command: mkdir -p storage/app/backups
    Verify: directory created with 755 permissions
```

### Documentation
```bash
[ ] Place HARDENED_RESTORE_QUICKSTART.md in public doc location
[ ] Place HARDENED_RESTORE_SYSTEM_IMPLEMENTATION.md in doc library
[ ] Add links to documentation in admin panel (if applicable)
[ ] Brief team on new features
```

### Monitoring Setup (Optional)
```bash
[ ] Set up log monitoring
    File: storage/logs/laravel.log
    Monitor: HardenedRestoreService logs

[ ] Set up database monitoring
    Monitor: RestoreAuditLog growth
    Alert: If restored status != completed or failed

[ ] Set up disk monitoring
    Monitor: storage/app/quarantine/ size
    Alert: If exceeds threshold
```

---

## Rollback Plan (If Needed)

### Emergency Rollback
```bash
# 1. Revert migration
[ ] php artisan migrate:rollback --target=2024_12_01_000000_create_restore_audit_logs_table

# 2. Remove Filament registration
[ ] Remove RestoreAuditLogResource from FilamentServiceProvider
[ ] Remove HardenedRestoreBackup from AdminPanelProvider

# 3. Revert BackupPolicy.php changes
[ ] Restore original restore() method

# 4. Remove files
[ ] rm /app/app/Services/HardenedRestoreService.php
[ ] rm /app/app/Models/RestoreAuditLog.php
[ ] rm /app/app/Policies/RestoreAuditLogPolicy.php
[ ] rm /app/app/Filament/Resources/RestoreAuditLogResource.php
[ ] rm /app/app/Filament/Pages/HardenedRestoreBackup.php
[ ] rm /app/resources/views/filament/pages/hardened-restore-backup.blade.php
[ ] rm /app/resources/views/components/restore-legal-warning.blade.php
[ ] rm /app/resources/views/components/restore-audit-notice.blade.php
[ ] rm /app/database/migrations/2024_12_01_*.php

# 5. Clear cache
[ ] php artisan cache:clear
[ ] php artisan config:cache

# 6. Verify rollback
[ ] php artisan tinker
>>> Schema::hasTable('restore_audit_logs')
Expected: false
```

---

## Success Criteria

All of the following must be true:

- [ ] Migration runs without error
- [ ] All 11 files deployed successfully
- [ ] Admin panel loads without errors
- [ ] Hardened restore page accessible
- [ ] Legal warning displays correctly
- [ ] Legal acknowledgment checkbox required
- [ ] Super admin can see all backups
- [ ] Regional admin can see only region backups
- [ ] District admin can see only district backups
- [ ] Other roles denied access
- [ ] Audit logs table created with 20 columns
- [ ] Audit log resource shows in admin
- [ ] Test audit log created successfully
- [ ] Immutability enforced (cannot update/delete)
- [ ] All links in documentation work
- [ ] No errors in storage/logs/laravel.log

---

## Deployment Sign-Off

**Deployed By**: ___________________  
**Date**: ___________________  
**Time**: ___________________  
**Status**: [ ] Success [ ] Partial [ ] Failed

**Notes**:
```
_________________________________________________________________

_________________________________________________________________

_________________________________________________________________
```

**Verified By**: ___________________  
**Date**: ___________________  

---

## Support Contact

For deployment issues or questions:

1. Check `HARDENED_RESTORE_SYSTEM_IMPLEMENTATION.md`
2. Review error messages in `storage/logs/laravel.log`
3. Contact system administrator
4. Escalate to development team if needed

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024-12-01 | Initial deployment |

---

**End of Checklist**
