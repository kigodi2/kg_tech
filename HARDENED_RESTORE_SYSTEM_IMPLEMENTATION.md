# Hardened Restore System Implementation

## Overview

This document describes the complete hardened restore system, which upgrades the backup/restore functionality with:

1. **SQLite Integrity Protection** - Pre/post-restore validation, atomic operations, automatic rollback
2. **Legal/Audit Compliance** - NECTA-style warnings, immutable audit logs, formal acknowledgment
3. **Role-Based Access Control** - Scope-aware restore permissions for Super/Regional/District admins

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Hardened Restore Flow                     │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  1. USER INITIATES RESTORE                                  │
│     └─ Selects backup from authorized list                 │
│     └─ Views NECTA-style legal warnings                    │
│     └─ Provides restore reason                             │
│     └─ Acknowledges legal responsibility                   │
│                                                               │
│  2. ACCESS CONTROL VALIDATION                               │
│     └─ HardenedRestoreService::canRestore()               │
│     └─ Verify role + scope (full/region/district)         │
│     └─ Deny if insufficient permissions                    │
│                                                               │
│  3. PRE-RESTORE VALIDATION                                  │
│     └─ validateBackupArchive()                             │
│        ├─ Check ZIP integrity                              │
│        ├─ Verify required files                            │
│        └─ Validate manifest.json                           │
│     └─ validateCurrentDatabase()                           │
│        ├─ PRAGMA integrity_check                           │
│        ├─ PRAGMA foreign_key_check                         │
│        └─ Check WAL file consistency                       │
│                                                               │
│  4. CREATE IMMUTABLE AUDIT LOG                              │
│     └─ RestoreAuditLog::create()                           │
│     └─ Record: user, backup, reason, IP, timestamp         │
│     └─ Status: initiated                                    │
│                                                               │
│  5. ENABLE MAINTENANCE MODE                                 │
│     └─ storage/app/MAINTENANCE_MODE                        │
│     └─ Prevent concurrent operations                       │
│     └─ Signal system under maintenance                     │
│                                                               │
│  6. QUARANTINE CURRENT DATABASE                             │
│     └─ Move to storage/app/quarantine/{timestamp}/         │
│     └─ Preserve database.sqlite + WAL files                │
│     └─ Enable auto-rollback if restore fails              │
│                                                               │
│  7. ATOMIC EXTRACT & RESTORE                                │
│     └─ Extract backup ZIP to temp directory                │
│     └─ Copy database.sqlite to correct location            │
│     └─ Restore WAL files if present                        │
│     └─ All-or-nothing: entire operation succeeds or fails  │
│                                                               │
│  8. POST-RESTORE VALIDATION                                 │
│     └─ validateRestoredDatabase()                          │
│     └─ PRAGMA integrity_check                              │
│     └─ PRAGMA foreign_key_check                            │
│     └─ Verify required tables exist                        │
│     └─ Verify exam_year isolation                          │
│                                                               │
│  9. SUCCESS / FAILURE HANDLING                              │
│     ├─ SUCCESS:                                             │
│     │  ├─ Update audit log: status=completed               │
│     │  ├─ Disable maintenance mode                         │
│     │  ├─ Clean quarantine (7-day retention)               │
│     │  └─ Log success                                       │
│     │                                                        │
│     └─ FAILURE:                                             │
│        ├─ Auto-rollback from quarantine                    │
│        ├─ Update audit log: status=rolled_back             │
│        ├─ Restore error message                            │
│        ├─ Disable maintenance mode                         │
│        └─ Log failure with error details                   │
│                                                               │
│  10. AUDIT TRAIL RECORDED                                   │
│      └─ RestoreAuditLog: immutable, permanent              │
│      └─ Non-repudiable record for examination authority    │
│      └─ Visible in Filament admin panel                    │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## Files Created

### Core Service

**`app/Services/HardenedRestoreService.php`**
- Main restore orchestration service
- Implements hardening, atomicity, and rollback logic
- Access control and validation methods
- ~450 lines, well-documented

### Data Models

**`app/Models/RestoreAuditLog.php`**
- Immutable audit log model
- Records all restore operations
- Relationships to User, Region, District
- Helper methods for reporting and export
- Read-only: `updated_at` is null (immutable)

### Database

**`database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php`**
- Creates `restore_audit_logs` table
- Immutable design (no updates allowed)
- Indexes for audit queries
- Foreign keys with restrict delete

### Authorization

**`app/Policies/BackupPolicy.php`** (Updated)
- Enhanced `restore()` method with role-based checks
- Super Admin: can restore any backup
- Regional Admin: can restore region backups only
- District Admin: can restore district backups only

**`app/Policies/RestoreAuditLogPolicy.php`** (New)
- View access based on role and scope
- Prevents creation, update, deletion (immutable)

### Filament Admin

**`app/Filament/Resources/RestoreAuditLogResource.php`**
- View-only Filament resource for audit logs
- Filters by status, scope, legal acknowledgment
- Displays full details with timeline
- Role-aware query scope

**`app/Filament/Pages/HardenedRestoreBackup.php`**
- Restore page with legal warnings
- Backup selection (role-filtered)
- Restore reason input
- 2FA authorizer selection (Super Admin only)
- Legal acknowledgment required

### Views

**`resources/views/filament/pages/hardened-restore-backup.blade.php`**
- Main restore UI
- Help section explaining 7-step process
- Auto-recovery information

**`resources/views/components/restore-legal-warning.blade.php`**
- NECTA-style legal warning
- Explicitly states data loss consequences
- Formal, neutral wording

**`resources/views/components/restore-audit-notice.blade.php`**
- Audit trail notification
- Lists what will be recorded
- Emphasizes immutability

## Database Schema

```sql
CREATE TABLE restore_audit_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,                    -- Operator
    authorized_by_id BIGINT,                    -- Optional 2FA authorizer
    backup_id VARCHAR(255),
    backup_filename VARCHAR(255),
    backup_hash VARCHAR(64),                    -- SHA-256
    scope_type ENUM('full', 'region', 'district'),
    region_id BIGINT,
    district_id BIGINT,
    restore_reason LONGTEXT,
    legal_acknowledgment LONGTEXT,
    legal_acknowledged BOOLEAN,
    ip_address VARCHAR(45),                     -- IPv4 or IPv6
    user_agent TEXT,
    status ENUM(...),
    error_message LONGTEXT,
    initiated_at TIMESTAMP,
    confirmed_at TIMESTAMP,
    executed_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP,
    -- NO updated_at (immutable)
    
    FOREIGN KEY (user_id) REFERENCES users,
    FOREIGN KEY (authorized_by_id) REFERENCES users,
    FOREIGN KEY (region_id) REFERENCES regions,
    FOREIGN KEY (district_id) REFERENCES districts,
    
    INDEX (user_id),
    INDEX (status),
    INDEX (scope_type),
    INDEX (created_at),
    INDEX (region_id, created_at),
    INDEX (district_id, created_at)
);
```

## Role-Based Access Control

### Super Admin
- ✓ Can restore ANY backup (full system)
- ✓ Can view ALL audit logs
- ✓ Can require 2FA authorizer
- ✓ Full system restore recorded

### Regional Admin
- ✓ Can restore backups for their region ONLY
- ✓ Can view audit logs for their region ONLY
- ✗ Cannot select 2FA authorizer
- ✓ Regional restore recorded with region_id

### District Admin
- ✓ Can restore backups for their district ONLY
- ✓ Can view audit logs for their district ONLY
- ✗ Cannot select 2FA authorizer
- ✓ District restore recorded with district_id

### Other Roles
- ✗ Access Denied (BackupPolicy prevents access)

## SQLite Integrity Checks

### Pre-Restore Validation

1. **Backup Archive Integrity**
   ```php
   validateBackupArchive($archivePath)
   - Check file exists and is readable
   - Verify ZIP file structure
   - Check for required files (database.sqlite, manifest.json)
   - Validate manifest.json syntax
   ```

2. **Current Database State**
   ```php
   validateCurrentDatabase()
   - PRAGMA integrity_check → must return 'ok'
   - PRAGMA foreign_key_check → must be empty
   - Verify WAL file consistency
   - Check file sizes > 0
   ```

### Post-Restore Validation

1. **Restored Database Integrity**
   ```php
   validateRestoredDatabase()
   - PRAGMA integrity_check → must return 'ok'
   - PRAGMA foreign_key_check → must be empty
   - Verify key tables exist (users, backups, exams, exam_years)
   ```

## Maintenance Mode

During restore, the system enters maintenance mode:

```
storage/app/MAINTENANCE_MODE
{
    "enabled_at": "2024-12-15T14:30:00Z",
    "reason": "Database restore in progress",
    "operator": "John Doe"
}
```

Middleware can check this file to:
- Return 503 Service Unavailable
- Prevent concurrent operations
- Signal maintenance window

## Quarantine System

Failed restores trigger automatic recovery from quarantine:

```
storage/app/quarantine/
├── 20241215143000/
│   ├── database.sqlite
│   ├── database.sqlite-wal
│   └── database.sqlite-shm
├── 20241215150000/
│   ├── database.sqlite
│   ├── database.sqlite-wal
│   └── database.sqlite-shm
└── ...
```

**Quarantine Retention**: 7 days
- Allows manual recovery if needed
- Scheduled cleanup job removes old entries
- Original operator can restore from quarantine

## Atomic Restore

The restore operation is ATOMIC:
- All files extracted and copied successfully, OR
- ALL files are rolled back from quarantine

No partial restores:
```php
try {
    atomicExtractAndRestore($archivePath);
    // If here, all succeeded
} catch (Exception $e) {
    rollbackFromQuarantine();  // Automatic recovery
    throw new Exception("Restore failed, rolled back");
}
```

## Audit Trail

Every restore attempt creates an immutable audit log:

```
Status Timeline:
initiated      → Restore requested
    ↓
confirmed      → Pre-validation passed
    ↓
in_progress    → Maintenance mode enabled
    ↓
completed      → Success + post-validation passed
    ↓ (or)
failed         → Error occurred
    ↓
rolled_back    → Auto-rollback executed
```

### Recorded Data

For each restore attempt:
- Operator name, role, IP address
- Backup filename and SHA-256 hash
- Restore reason (free text, 10-1000 chars)
- Legal acknowledgment text + checkbox
- All timestamps (initiated → executed → completed)
- Success/failure status and error message
- 2FA authorizer (if applicable)
- Scope (full/region/district)

### Immutability

```php
// Model definition prevents updates
const UPDATED_AT = null;

// Database schema has no update triggers
// Policy prevents modifications
public function update(User $user, RestoreAuditLog $log): bool
{
    return false;
}
```

## Usage Guide

### For Super Admin

1. Navigate to **Admin Panel → Backups & Restore → Hardened Restore**
2. Select a backup
3. Enter restore reason
4. Optionally select 2FA authorizer
5. Check legal acknowledgment
6. Click **Execute Restore**
7. Confirm in modal
8. System:
   - Validates backup and current DB
   - Quarantines current DB
   - Restores backup atomically
   - Validates restored DB
   - Logs operation immutably
   - Exits maintenance mode

### For Regional Admin

Same as Super Admin, but:
- Only sees backups for their region
- Scope recorded as 'region'
- Can only view their region's audit logs
- Cannot select 2FA authorizer

### For District Admin

Same as Super Admin, but:
- Only sees backups for their district
- Scope recorded as 'district'
- Can only view their district's audit logs
- Cannot select 2FA authorizer

### Viewing Audit Logs

1. **Admin Panel → System Administration → Restore Audit Logs**
2. Filter by:
   - Status (completed, failed, in progress, etc.)
   - Scope (full, region, district)
   - Legal acknowledgment (confirmed/not)
3. Click row to view full details
4. Download as PDF for examination authority records

## Deployment Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Publish Filament resource (if needed)
- [ ] Add routes to Filament config
- [ ] Create middleware for MAINTENANCE_MODE check
- [ ] Create scheduled job for quarantine cleanup (7-day retention)
- [ ] Test restore with each role type
- [ ] Test rollback scenario
- [ ] Verify audit logs are created
- [ ] Document for training

## Testing Procedures

### 1. Pre-Restore Validation

```bash
# Test with corrupted backup
php artisan tinker
$service = app(HardenedRestoreService::class);
$result = $service->validateBackupArchive('corrupted.zip');
// Should return valid: false, errors: [...]
```

### 2. Access Control

```bash
# Create users with different roles
php artisan tinker
$superAdmin = User::where('role_code', 'super_admin')->first();
$regionalAdmin = User::where('role_code', 'regional_admin')->first();
$districtAdmin = User::where('role_code', 'district_admin')->first();

$service = new HardenedRestoreService($superAdmin);
$result = $service->canRestore($backup);
// Super admin: allowed=true, scope=full

$service = new HardenedRestoreService($regionalAdmin);
$result = $service->canRestore($backup);
// If backup region matches: allowed=true, scope=region
// Else: allowed=false
```

### 3. Rollback on Failure

```bash
# Manually trigger restore and let it fail
# Verify rollback from quarantine
# Check audit log status = rolled_back
# Verify original DB was recovered
```

### 4. Audit Log Immutability

```bash
# Try to update audit log
php artisan tinker
$log = RestoreAuditLog::first();
$log->update(['status' => 'completed']);
// Should fail: policy prevents it
```

## Legal Compliance

This system provides NECTA-compliant restore operations:

1. **Explicit Warnings**: Users see formal data loss warnings
2. **Legal Acknowledgment**: Checkbox confirms understanding
3. **Audit Trail**: Immutable, non-repudiable record
4. **Role-Based Control**: Scope-aware permissions
5. **Automatic Recovery**: Quarantine enables safe rollback
6. **Formal Language**: Examination-authority-compliant wording

## Security Notes

1. **No Partial Restores**: Atomic all-or-nothing
2. **Automatic Rollback**: Failures don't leave system in bad state
3. **Immutable Audit**: Nobody can hide restore operations
4. **Role Restrictions**: Only authorized operators can restore
5. **Maintenance Mode**: Prevents concurrent modifications
6. **Quarantine Retention**: 7-day recovery window

## Future Enhancements

- [ ] 2FA requirement for Super Admin full-system restores
- [ ] Email notifications on restore completion
- [ ] Slack/SMS alerts for failed restores
- [ ] Restore simulation (dry-run) mode
- [ ] Audit log export to JSON/CSV
- [ ] Scheduled quarantine cleanup job
- [ ] Database backup encryption
- [ ] Off-site backup replication

## References

- NECTA Regulations: Data governance for examination systems
- SQLite WAL Mode: https://www.sqlite.org/wal.html
- PRAGMA Integrity Check: https://www.sqlite.org/pragma.html#pragma_integrity_check
- Laravel Policies: https://laravel.com/docs/authorization
- Filament Resources: https://filamentphp.com/docs/3.x/admin/resources/getting-started
