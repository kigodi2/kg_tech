# Hardened Restore System - Quick Start Guide

## What's New

The restore system is now:
- **HARDENED**: Atomic operations with automatic rollback
- **AUDIT-COMPLIANT**: Immutable logs for NECTA compliance
- **ROLE-AWARE**: Super/Regional/District admin scope control
- **UX-SAFE**: Legal warnings, quarantine backup, maintenance mode

## Installation (5 minutes)

```bash
# 1. Run migration
php artisan migrate

# 2. Clear cache
php artisan cache:clear
php artisan config:cache

# 3. Verify files created
ls app/Services/HardenedRestoreService.php
ls app/Models/RestoreAuditLog.php
ls app/Policies/RestoreAuditLogPolicy.php
```

## Access the Restore System

### For Super Admin
1. Go to **Admin Panel**
2. Click **Backups & Restore** → **Hardened Restore**
3. Select a backup
4. Enter reason
5. Optionally select 2FA authorizer
6. Check legal acknowledgment
7. Click **Execute Restore**

### For Regional Admin
1. Same as Super Admin
2. Only sees backups for your region
3. Cannot select 2FA authorizer

### For District Admin
1. Same as Super Admin
2. Only sees backups for your district
3. Cannot select 2FA authorizer

## View Audit Logs

**Admin Panel** → **System Administration** → **Restore Audit Logs**

See every restore attempt with:
- Operator name and role
- Date & time (with timezone)
- Backup restored
- Status (completed/failed/rolled_back)
- Legal acknowledgment (yes/no)
- Restore reason
- Full timeline (initiated → executed → completed)

## What Happens During Restore

```
1. Legal warning screen → you must acknowledge
2. Pre-validation → checks backup integrity
3. Quarantine → current DB moved to storage/app/quarantine/
4. Maintenance mode → system unavailable briefly
5. Extract & restore → new backup copied
6. Post-validation → SQLite PRAGMA integrity_check
7. Success → audit logged, maintenance mode off
   OR
7. Failure → auto-rollback from quarantine
```

## Key Features

### Automatic Rollback
If restore fails at ANY point:
- System auto-recovers from quarantine
- Original DB fully restored
- Audit logged as "rolled_back"
- No manual recovery needed

### Atomic All-or-Nothing
- All backup files extracted successfully, OR
- Everything rolled back to original
- No partial restores allowed
- Database never left in inconsistent state

### Immutable Audit Trail
Every restore operation recorded with:
- Operator name, role, IP
- Exact timestamp (down to second)
- Backup filename & hash
- Restore reason (your explanation)
- Success/failure status
- Error message (if failed)

Nobody can edit or delete these records.

### Quarantine Backup
Failed restores kept safe in:
```
storage/app/quarantine/20241215143000/
├── database.sqlite
├── database.sqlite-wal
└── database.sqlite-shm
```
Retained 7 days for manual recovery if needed.

### Scope-Based Permissions

| Role | Can Restore | What | Can View Logs |
|------|-------------|------|---------------|
| Super Admin | ✓ Full system | ANY backup | ALL logs |
| Regional Admin | ✓ Region only | Region backups | Region logs |
| District Admin | ✓ District only | District backups | District logs |
| Other | ✗ Denied | N/A | N/A |

## Legal Compliance

The system displays this warning:

```
⚠️  CRITICAL WARNING ⚠️

This operation will REPLACE the ENTIRE examination database.

WHAT WILL BE LOST:
✗ All current examination results
✗ All student registrations  
✗ All marks and grades
✗ All candidate information

WHAT WILL HAPPEN:
• The current database will be moved to quarantine
• A previous backup will be restored
• If restoration fails, the system will automatically recover
• This operation is IRREVERSIBLE
```

You must check the box: **"I understand and accept full responsibility"**

## Example Restore Reason

Good reasons:
- "Restoring from yesterday after data entry error in exam_id 5"
- "Scheduled monthly recovery per examination authority protocols"
- "Recovering from 2024-11-14 backup due to SQL injection incident"

Poor reasons:
- "Testing"
- "Just trying"
- (blank)

System requires 10-1000 characters.

## Troubleshooting

### Problem: "Access Denied: User role not authorized"
**Solution**: Only Super/Regional/District admins can restore. Request admin privileges if needed.

### Problem: "Backup validation failed: database.sqlite not found"
**Solution**: Backup file is corrupted. Create a new backup and retry.

### Problem: "Post-restore validation failed: Foreign key violations"
**Solution**: The backed-up database is corrupt. Do NOT attempt restore. Contact support.

### Problem: Restore seems stuck
**Solution**: System is in maintenance mode. Check `storage/app/MAINTENANCE_MODE`. If file exists but restore is hung, manually delete file and restart system.

## Files & Locations

| File | Purpose |
|------|---------|
| `app/Services/HardenedRestoreService.php` | Main restore orchestration |
| `app/Models/RestoreAuditLog.php` | Audit log model |
| `app/Policies/RestoreAuditLogPolicy.php` | Authorization policy |
| `app/Filament/Resources/RestoreAuditLogResource.php` | Audit log viewer |
| `app/Filament/Pages/HardenedRestoreBackup.php` | Restore page |
| `database/migrations/2024_12_01_*.php` | Database schema |
| `storage/app/quarantine/` | Quarantine backups |
| `storage/app/MAINTENANCE_MODE` | Maintenance flag |

## Database Schema

New table: `restore_audit_logs`
- 13 columns (user, backup, scope, status, timestamps, etc.)
- Immutable (no updates allowed)
- Foreign keys to users, regions, districts
- Indexes for fast audit queries

## Testing Your Installation

```bash
# 1. Check migration ran
php artisan tinker
RestoreAuditLog::count()  # Should return 0

# 2. Check policy works
$user = User::where('role_code', 'super_admin')->first();
auth()->setUser($user);
auth()->user()->can('restore', Backup::class)  # Should return true

# 3. Access Filament page
# Visit /admin/hardened-restore-backup
# Should see legal warning and backup selection
```

## Next Steps

- [ ] Run migration
- [ ] Test restore with test backup
- [ ] Train super admin on new features
- [ ] Create backup schedule if not exists
- [ ] Set up quarantine cleanup job (optional)
- [ ] Document restore procedures for your org
- [ ] Export audit log to JSON for records

## Support

For issues or questions:
1. Check audit logs: **Admin Panel** → **Restore Audit Logs**
2. Review error messages in audit log details
3. Check `storage/logs/laravel.log` for technical errors
4. Contact system administrator

## Reference

Full documentation: `HARDENED_RESTORE_SYSTEM_IMPLEMENTATION.md`

---

**Version**: 1.0  
**Date**: 2024-12-01  
**Status**: Production Ready
