# Hardened Restore System

**Status**: Production Ready  
**Version**: 1.0  
**Date**: 2026-02-02  
**Compliance**: NECTA-style examination data governance

---

## Executive Summary

The Hardened Restore System upgrades IRMS database recovery with production-grade safeguards:

1. **🔐 SQLite Integrity Protection** - Strict pre-restore validation, atomic operations, automatic rollback
2. **⚖️ Legal Compliance** - NECTA-style governance wording, legal acknowledgment requirements, immutable audit trails
3. **👥 Role-Aware Access** - Super Admin → Regional Admin → District Admin hierarchy with scoped restore permissions
4. **🛡️ Fail-Safe Design** - Maintenance mode, quarantine directories, automatic recovery from failure

---

## Table of Contents

1. [Architecture](#architecture)
2. [SQLite Hardening](#sqlite-hardening)
3. [Legal & Audit Compliance](#legal--audit-compliance)
4. [Role-Based Access Control](#role-based-access-control)
5. [API Reference](#api-reference)
6. [Database Schema](#database-schema)
7. [Implementation Checklist](#implementation-checklist)
8. [Operations Guide](#operations-guide)

---

## Architecture

### Components

#### 1. **RestoreAuditLog Model** (`app/Models/RestoreAuditLog.php`)
- Immutable audit trail for ALL restore operations
- Records: operator, backup ID, scope, reason, legal acknowledgment, timestamps, IP address
- NECTA-compliant governance record
- Never deleted or updated (only appended)

#### 2. **HardenedRestoreService** (`app/Services/HardenedRestoreService.php`)
Core restore engine with 3 phases:

**Phase 1: Pre-Restore Validation**
- File existence checks (database.sqlite, database.sqlite-wal, database.sqlite-shm)
- ZIP archive integrity verification
- Manifest JSON validation
- SHA-256 checksum validation
- WAL/SHM file presence check
- Aborts if ANY issue found (fail-safe)

**Phase 2: Legal Acknowledgment Validation**
- Confirmation checkbox: "I understand and accept full responsibility"
- Confirmation text: Must type exact string "RESTORE"
- Restore reason: Minimum 10 characters required
- NECTA-compliant wording

**Phase 3: Atomic Restore Execution**
- Application enters maintenance mode (storage/framework/down)
- Current DB files moved to quarantine: storage/backups/quarantine/{timestamp}_{id}/
- Extract backup from ZIP/encrypted archive
- Validate extracted database integrity
- Copy new files to production location
- Reconnect database
- Post-restore verification (PRAGMA checks, table validation, foreign key check)
- Exit maintenance mode on success
- Auto-rollback from quarantine on failure

#### 3. **HardenedRestorePolicy** (`app/Policies/HardenedRestorePolicy.php`)
Role-aware authorization:

```
Super Admin
├─ restoreFullSystem(user) ✓
├─ restoreRegion(user, region) ✓
├─ restoreDistrict(user, district) ✓
└─ recoverFromQuarantine(user) ✓

Regional Officer (with region scope)
├─ restoreRegion(user, their_region_only) ✓
├─ restoreDistrict(user, districts_in_region) ✓
└─ NOT recoverFromQuarantine

District Supervisor/Data Entry Officer (with district scope)
├─ restoreDistrict(user, their_district_only) ✓
└─ NOT restoreRegion (even their own)
└─ NOT recoverFromQuarantine

Other Roles
└─ NO restore permissions
```

#### 4. **HardenedRestoreController** (`app/Http/Controllers/HardenedRestoreController.php`)
REST API endpoints:
- GET /api/restore/legal-text
- POST /api/restore/validate
- POST /api/restore/confirm
- POST /api/restore/execute ← **DESTRUCTIVE**
- GET /api/restore/audit-logs
- POST /api/restore/audit-export

---

## SQLite Hardening

### Pre-Restore Validation

```php
// Validates ALL of these before restore can proceed:
✓ Backup file exists
✓ Backup is valid ZIP archive
✓ database.sqlite present and > 0 bytes
✓ database.sqlite-wal present (WAL mode check)
✓ database.sqlite-shm present (WAL mode check)
✓ manifest.json present and valid JSON
✓ checksums.sha256 present
✓ backup.sig present
✓ All required fields in manifest
✓ Checksum validation passes
✗ ANY failure = ABORT RESTORE
```

### Atomic Restore Process

```
1. Application enters MAINTENANCE MODE
   └─ Serving /down message to all users

2. Quarantine CURRENT database
   storage/backups/quarantine/2026-02-02_10-30-00_xxx/
   ├─ database.sqlite (current production DB)
   ├─ database.sqlite-wal (if exists)
   └─ database.sqlite-shm (if exists)

3. Extract NEW database
   └─ From backup ZIP (encrypted if needed)
   └─ To sandbox directory (not production yet)

4. VALIDATE EXTRACTED DATABASE
   ├─ Run PRAGMA integrity_check → must be 'ok'
   ├─ Verify critical tables exist
   ├─ Check schema version
   └─ Abort if validation fails

5. ATOMIC FILE REPLACEMENT
   ├─ Disconnect from current database
   ├─ Copy validated database to production
   ├─ Copy WAL/SHM files if present
   ├─ Reconnect database
   └─ NO PARTIAL STATE POSSIBLE

6. POST-RESTORE VERIFICATION
   ├─ SELECT sqlite_version() → must return result
   ├─ PRAGMA integrity_check → must be 'ok'
   ├─ PRAGMA foreign_keys = ON → must work
   ├─ Verify critical tables still exist
   └─ Abort if ANY check fails

7. Success: Exit maintenance mode
   └─ System back online

   OR

7. Failure: AUTOMATIC ROLLBACK
   ├─ Restore from quarantine
   ├─ Reconnect database
   ├─ Record as 'rolled_back' in audit log
   └─ System back online (original state)
```

### No Partial Restores

```javascript
// The restore is ATOMIC - all or nothing

Result A: SUCCESS
  ✓ Application back online
  ✓ New database in production
  ✓ Old database safe in quarantine
  ✓ Audit log: status='completed'

Result B: VALIDATION FAILURE
  ✓ Restore never starts
  ✓ Application unchanged
  ✓ No maintenance mode
  ✓ Audit log: status='initiated'

Result C: RESTORE FAILURE + SUCCESSFUL ROLLBACK
  ✓ Automatic recovery from quarantine
  ✓ Application back online
  ✓ Original database restored
  ✓ Audit log: status='rolled_back'

Result D: RESTORE FAILURE + ROLLBACK FAILURE (CRITICAL)
  ✗ Application in maintenance mode
  ✓ Quarantine directory preserved
  ✓ Manual recovery required
  ✓ Audit log: status='failed' with error message
  → Call system administrator immediately
```

---

## Legal & Audit Compliance

### NECTA-Style Legal Acknowledgment

Before restore, operator sees and confirms:

```
╔═════════════════════════════════════════════════════════════════╗
║     EXAMINATION DATA GOVERNANCE NOTICE                          ║
╚═════════════════════════════════════════════════════════════════╝

This operation will REPLACE the ENTIRE examination database.
All current results, registrations, and marks will be LOST.
This action is irreversible and must be authorized
according to examination data governance regulations.

By proceeding, you confirm:
1. You have authority to perform this operation
2. You have verified this restore is necessary
3. You accept full responsibility for consequences
4. All affected stakeholders have been notified
5. This operation complies with examination regulations

This restore operation will be:
• Logged with complete audit trail
• Recorded with your name, role, and timestamp
• Validated against backup integrity checksums
• Protected with automatic rollback on failure

CONFIRMATION REQUIRED:
You must type "RESTORE" in the confirmation field
and check the acknowledgment box to proceed.

[ ✓ ] I understand and accept full responsibility

┌─────────────────────────────────────────┐
│ Type RESTORE to confirm:                │
│ [________________]                      │
└─────────────────────────────────────────┘

Restore Reason (required, min 10 characters):
┌─────────────────────────────────────────┐
│ [Emergency recovery due to data...]      │
└─────────────────────────────────────────┘
```

### Immutable Audit Trail

Every restore operation records:

```sql
-- RestoreAuditLog table
id                    int           AUTO
user_id               int           Foreign Key (users)
authorized_by_id      int NULL      Multi-auth support
backup_id             varchar       Backup identifier
backup_filename       varchar       Original filename
backup_hash           char(64)      SHA-256 hash
scope_type            enum          'full'|'region'|'district'
region_id             int NULL      For regional restores
district_id           int NULL      For district restores
restore_reason        text          Operator provided reason
legal_acknowledgment  text          Full legal text shown
legal_acknowledged    boolean       Confirmed checkbox
ip_address            varchar       Requester IP
user_agent            varchar       Browser/client info
status                enum          'initiated'|'confirmed'|...|'rolled_back'
error_message         text NULL     If failed
initiated_at          timestamp     Request time
confirmed_at          timestamp     Confirmation time
executed_at           timestamp     Restore start time
completed_at          timestamp     Restore finish time
created_at            timestamp     Record creation (immutable)
-- NO updated_at - IMMUTABLE RECORDS
```

### Audit Log Export

Export for examination authority records:

```bash
GET /api/restore/audit-export?format=csv&from_date=2026-01-01

Returns:
Audit ID | Timestamp            | Operator     | Role           | Scope | Backup ID | Hash       | Reason | Status    | Error | IP      | Legal | Duration
1        | 2026-02-02 10:30:00 | J. Smith     | admin          | full  | xxx-xxx   | sha256...  | xxx    | completed |       | 192... | Yes   | 130
2        | 2026-01-15 14:22:00 | M. Johnson   | regional_admin | region| yyy-yyy   | sha256...  | xxx    | completed |       | 192... | Yes   | 145
3        | 2026-01-10 09:15:00 | D. Williams  | district_admin | dist  | zzz-zzz   | sha256...  | xxx    | failed    | Cript  | 10... | Yes   | NULL
```

---

## Role-Based Access Control

### Permission Matrix

| Action | Super Admin | Regional Admin | District Admin | Other |
|--------|:-----------:|:-------------:|:-------------:|:-----:|
| **Full System Restore** | ✓ Any backup | ✗ | ✗ | ✗ |
| **Regional Restore** | ✓ Any region | ✓ Their region only | ✗ | ✗ |
| **District Restore** | ✓ Any district | ✓ Districts in their region | ✓ Their district only | ✗ |
| **View Audit Logs** | ✓ All logs | ✓ Regional logs | ✓ District logs | ✗ |
| **Download Audit Report** | ✓ All records | ✓ Regional records | ✓ District records | ✗ |
| **Recover from Quarantine** | ✓ Critical operation | ✗ | ✗ | ✗ |

### Scope Check

```php
// Super Admin - check is_admin
User::where('is_admin', true)

// Regional Admin - check role + scope
User::where('role_id', regional_officer_role_id)
    ->where(function($q) {
        $q->whereHas('scope', function($sq) {
            $sq->where('scope_type', 'region');
        });
    })

// District Admin - check role + scope
User::whereIn('role_id', [district_supervisor_role_id, data_entry_role_id])
    ->where(function($q) {
        $q->whereHas('scope', function($sq) {
            $sq->where('scope_type', 'district');
        });
    })
```

---

## API Reference

### Endpoint 1: Get Legal Text

```bash
GET /api/restore/legal-text

Response 200:
{
  "success": true,
  "legal_text": "This operation will REPLACE the ENTIRE examination database...",
  "required_fields": {
    "legal_acknowledged": "boolean (checkbox)",
    "confirmation_text": "string (\"RESTORE\")",
    "restore_reason": "string (minimum 10 characters)"
  }
}
```

### Endpoint 2: Validate Backup

```bash
POST /api/restore/validate
Content-Type: application/json

{
  "backup_path": "storage/backups/irms-backup-full-system-2026-02-02_102000.zip"
}

Response 200 (Valid):
{
  "success": true,
  "valid": true,
  "errors": [],
  "warnings": []
}

Response 200 (Invalid):
{
  "success": false,
  "valid": false,
  "errors": [
    "database.sqlite not found in backup",
    "Checksum validation failed: manifest.json"
  ],
  "warnings": [
    "WARNING: WAL file missing - backup may be incomplete"
  ]
}
```

### Endpoint 3: Get Confirmation Page

```bash
POST /api/restore/confirm
Content-Type: application/json

{
  "backup_id": "irms-backup-full-system-2026-02-02_102000",
  "backup_filename": "irms-backup-full-system-2026-02-02_102000.zip",
  "backup_hash": "a1b2c3d4e5f6..."
}

Response 200:
{
  "success": true,
  "operator": {
    "name": "John Smith",
    "email": "john@example.com",
    "role": "admin",
    "id": 1
  },
  "backup_info": {
    "backup_id": "irms-backup-full-system-2026-02-02_102000",
    "filename": "irms-backup-full-system-2026-02-02_102000.zip",
    "hash": "a1b2c3d4e5f6..."
  },
  "legal_acknowledgment": {
    "title": "EXAMINATION DATA GOVERNANCE NOTICE",
    "text": "This operation will REPLACE...",
    "required_checkbox": "I understand and accept full responsibility for this restore operation.",
    "confirmation_required": "Type \"RESTORE\" in the confirmation field"
  },
  "audit_notice": "This operation will be logged and audited...",
  "required_fields": {
    "legal_acknowledged": "boolean",
    "confirmation_text": "string",
    "restore_reason": "string"
  }
}
```

### Endpoint 4: Execute Restore ⚠️ DESTRUCTIVE

```bash
POST /api/restore/execute
Content-Type: application/json

{
  "backup_path": "storage/backups/irms-backup-full-system-2026-02-02_102000.zip",
  "backup_id": "irms-backup-full-system-2026-02-02_102000",
  "backup_filename": "irms-backup-full-system-2026-02-02_102000.zip",
  "backup_hash": "a1b2c3d4e5f6...",
  "legal_acknowledged": true,
  "confirmation_text": "RESTORE",
  "restore_reason": "Emergency recovery due to data corruption in current database"
}

Response 200 (Success):
{
  "success": true,
  "message": "Database restore completed successfully and verified",
  "restore": {
    "audit_log_id": 42,
    "restored_at": "2026-02-02T10:32:15.000000Z",
    "quarantine_location": "storage/backups/quarantine/2026-02-02_10-30-00_a1b2c3d4e",
    "notice": "Original database backed up in quarantine location. The system is now online."
  }
}

Response 422 (Validation Failed):
{
  "success": false,
  "error": "Backup validation failed",
  "errors": [
    "database.sqlite not found in backup",
    "Checksum validation failed"
  ]
}

Response 500 (Critical Failure):
{
  "success": false,
  "error": "CRITICAL RESTORE FAILURE: Cannot move current database to quarantine. ...",
  "recovery_instructions": [
    "Check storage/backups/quarantine for your current database",
    "Review application logs in storage/logs for detailed error information",
    "Contact the system administrator for assistance"
  ]
}
```

### Endpoint 5: View Audit Logs

```bash
GET /api/restore/audit-logs?page=1&per_page=50

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 42,
      "operator": "John Smith",
      "operator_role": "admin",
      "backup_id": "irms-backup-full-system-2026-02-02_102000",
      "backup_filename": "irms-backup-full-system-2026-02-02_102000.zip",
      "scope": "Full System Restore",
      "restore_reason": "Emergency recovery due to data corruption",
      "status": "completed",
      "status_badge": "success",
      "initiated_at": "2026-02-02T10:30:00.000000Z",
      "executed_at": "2026-02-02T10:30:05.000000Z",
      "completed_at": "2026-02-02T10:32:15.000000Z",
      "legal_acknowledged": true,
      "ip_address": "192.168.1.100",
      "error": null
    },
    {
      "id": 41,
      "operator": "Jane Doe",
      "operator_role": "regional_admin",
      "scope": "Regional Restore: Eastern Region",
      "status": "completed",
      ...
    }
  ],
  "pagination": {
    "total": 42,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1
  }
}
```

### Endpoint 6: Export Audit Report

```bash
POST /api/restore/audit-export
Content-Type: application/json

{
  "format": "csv",
  "from_date": "2026-01-01",
  "to_date": "2026-02-02"
}

Response 200 (CSV):
{
  "success": true,
  "csv_data": "Audit ID,Timestamp,Operator,Operator Role,...\n1,2026-02-02T10:30:00Z,John Smith,admin,...",
  "filename": "restore-audit-2026-02-02-103015.csv"
}

Response 200 (JSON):
{
  "success": true,
  "export_date": "2026-02-02T10:35:00.000000Z",
  "exported_by": "John Smith",
  "record_count": 5,
  "records": [
    {
      "audit_id": 42,
      "timestamp": "2026-02-02T10:30:00Z",
      "operator": "John Smith",
      "operator_role": "admin",
      "scope": "Full System Restore",
      "backup_restored": "irms-backup-full-system-2026-02-02_102000",
      "backup_hash": "a1b2c3d4e5f6...",
      "restore_reason": "Emergency recovery...",
      "status": "completed",
      "error": null,
      "ip_address": "192.168.1.100",
      "legal_acknowledged": "Confirmed",
      "duration_seconds": 130
    }
  ]
}
```

---

## Database Schema

### RestoreAuditLog Table

```sql
CREATE TABLE restore_audit_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  
  -- Operator Information
  user_id BIGINT NOT NULL FOREIGN KEY REFERENCES users(id),
  authorized_by_id BIGINT NULL FOREIGN KEY REFERENCES users(id),
  
  -- Backup Identification
  backup_id VARCHAR(255) NOT NULL,
  backup_filename VARCHAR(255) NOT NULL,
  backup_hash CHAR(64) NOT NULL,
  
  -- Scope
  scope_type ENUM('full', 'region', 'district') DEFAULT 'full',
  region_id BIGINT NULL FOREIGN KEY REFERENCES regions(id),
  district_id BIGINT NULL FOREIGN KEY REFERENCES districts(id),
  
  -- Operator Input
  restore_reason LONGTEXT NOT NULL,
  legal_acknowledgment LONGTEXT NOT NULL,
  legal_acknowledged BOOLEAN DEFAULT FALSE,
  
  -- Technical
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  
  -- Status
  status ENUM('initiated', 'confirmed', 'in_progress', 'completed', 'failed', 'rolled_back') DEFAULT 'initiated',
  error_message LONGTEXT NULL,
  
  -- Timeline
  initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  confirmed_at TIMESTAMP NULL,
  executed_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  KEY user_id (user_id),
  KEY status (status),
  KEY scope_type (scope_type),
  KEY region_id (region_id),
  KEY district_id (district_id),
  KEY created_at (created_at),
  UNIQUE unique_backup_hash_per_scope (backup_hash, scope_type),
  
  COMMENT 'Immutable audit trail for restore operations. NECTA-compliant.'
);
```

---

## Implementation Checklist

### Step 1: Create Migration

```bash
php artisan migrate
# Creates restore_audit_logs table with proper schema
```

### Step 2: Register Service

In `config/app.php` or AppServiceProvider:

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(HardenedRestoreService::class, function ($app) {
        return new HardenedRestoreService(
            $app->make(SQLiteBackupService::class)
        );
    });
}
```

### Step 3: Register Policy

In `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    // ... existing policies
    'Restore' => HardenedRestorePolicy::class,
];

public function boot()
{
    $this->registerPolicies();
    Gate::define('restore-full-system', function ($user) {
        return $user->isAdmin();
    });
    // ... more gates
}
```

### Step 4: Register Routes

In `routes/api.php`:

```php
// Include hardened restore routes
require base_path('routes/hardened-restore.php');
```

### Step 5: Update Existing Restore Endpoints (Optional)

If keeping old RestoreController, add redirect:

```php
// BackupRestoreController.php - DEPRECATED
public function executeRestore(Request $request, $id)
{
    // Redirect to new API
    return redirect('api/restore/execute')->with(
        'warning', 
        'Old restore endpoint is deprecated. Use new API.'
    );
}
```

### Step 6: Frontend Integration

Create restore confirmation UI that:

```javascript
// 1. Fetch legal text
GET /api/restore/legal-text

// 2. Show legal acknowledgment modal
display(legal_text)
require_checkbox("I understand and accept...")
require_input("confirmation_text", "Type RESTORE")
require_textarea("restore_reason", "min 10 chars")

// 3. Validate backup
POST /api/restore/validate
if (errors) display(errors) and stop

// 4. Show confirmation page
POST /api/restore/confirm
display(operator_info, backup_info, legal_text)

// 5. FINAL WARNING before execute
if (confirm("This operation is IRREVERSIBLE!")) {
  POST /api/restore/execute
  show_loading()
  display(audit_log_id, quarantine_location)
}
```

---

## Operations Guide

### For Super Admins

**Full System Restore Procedure:**

```bash
# 1. Verify backup exists and is valid
ls -lah storage/backups/irms-backup-full-system-*.zip

# 2. Contact examination authority
# Notify: "I am about to restore from backup X due to [reason]"

# 3. Get backup hash (for audit trail)
sha256sum storage/backups/irms-backup-full-system-2026-02-02_102000.zip
# Output: a1b2c3d4e5f6... irms-backup-full-system-2026-02-02_102000.zip

# 4. Log into IRMS
# Navigate to Admin → Backup & Restore → Restore Backup

# 5. Select backup and click "Validate"
# Review validation results

# 6. Click "Confirm Restore"
# Read legal text carefully

# 7. Check acknowledgment box
# Type "RESTORE" in confirmation field
# Enter restore reason (min 10 characters)

# 8. Click "EXECUTE RESTORE" (FINAL STEP)
# System enters maintenance mode
# Old DB moved to quarantine
# New DB restored
# System comes back online

# 9. Verify restore success
# Check audit log: status = 'completed'
# Verify data integrity
# Notify examination authority

# 10. Archive quarantine (optional, after verification)
# tar czf backups/quarantine/2026-02-02_10-30-00_archive.tar.gz \
#       storage/backups/quarantine/2026-02-02_10-30-00_a1b2c3d4e/
```

### For Regional Admins

**Regional Restore Procedure:**

- Same as super admin but ONLY for your region's districts
- Other districts' restores will be denied by authorization check
- View your region's audit logs: GET /api/restore/audit-logs

### For District Admins

**District Restore Procedure:**

- Same as super admin but ONLY for your district
- Other districts' restores will be denied
- View your district's audit logs: GET /api/restore/audit-logs

### Emergency Recovery (Critical Failure)

If restore fails and automatic rollback fails:

```bash
# 1. APPLICATION IS IN MAINTENANCE MODE
# Users see: "System is restoring from backup. Please wait..."

# 2. MANUAL RECOVERY REQUIRED
# Find quarantine directory:
ls -la storage/backups/quarantine/
# Output: 2026-02-02_10-30-00_a1b2c3d4e/
#   ├─ database.sqlite (your current DB before failed restore)
#   ├─ database.sqlite-wal
#   └─ database.sqlite-shm

# 3. Option A: Restore from quarantine manually
cp storage/backups/quarantine/2026-02-02_10-30-00_a1b2c3d4e/database.sqlite \
   database/database.sqlite
chmod 640 database/database.sqlite
rm storage/framework/down

# 4. Option B: Contact system administrator
# Provide:
# - Audit log ID from failed restore
# - Quarantine location
# - Error message from logs (storage/logs/laravel.log)

# 5. Verify database integrity after recovery
php artisan tinker
>>> DB::select('PRAGMA integrity_check')
# Should return: [
#   stdClass {
#     integrity_check: "ok",
#   }
# ]
```

### Audit Trail Review

```bash
# As Super Admin: See all restores
GET /api/restore/audit-logs

# As Regional Admin: See regional restores only
GET /api/restore/audit-logs?scope_type=region

# Export for examination authority
POST /api/restore/audit-export
{
  "format": "csv",
  "from_date": "2026-01-01",
  "to_date": "2026-02-02"
}

# Download CSV and send to examination authority
# CSV includes: operator, timestamp, reason, status, etc.
```

---

## Monitoring & Maintenance

### Daily Checks

```bash
# Check restore logs
tail -f storage/logs/laravel.log | grep -i restore

# Monitor quarantine directory (keeps recent restores)
du -sh storage/backups/quarantine/

# List completed restores
php artisan tinker
>>> RestoreAuditLog::completed()->latest()->first(10)
```

### Cleanup Old Quarantine

```bash
# Keep quarantine for 30 days, then archive/delete
find storage/backups/quarantine -type d -mtime +30 \
  -exec tar czf backups/quarantine/archive-{}.tar.gz {} \; \
  -exec rm -rf {} \;
```

### Audit Compliance Reporting

```bash
# Monthly export for examination authority
php artisan tinker
>>> RestoreAuditLog::where('created_at', '>=', now()->subMonth())
...   ->orderBy('created_at')
...   ->get()
...   ->each(fn($log) => dd($log->toAuditExport()))
```

---

## Troubleshooting

### Problem: "Backup file is not a valid ZIP archive"

**Cause**: Backup is corrupted or not a valid ZIP  
**Solution**:
```bash
file storage/backups/irms-backup-xxx.zip
unzip -t storage/backups/irms-backup-xxx.zip
# Check for errors
```

### Problem: "Missing required file in backup: database.sqlite"

**Cause**: Backup incomplete or corrupted  
**Solution**:
```bash
unzip -l storage/backups/irms-backup-xxx.zip | grep database.sqlite
# Should show the file
# If not: backup is corrupted, use older backup
```

### Problem: "Database verification failed after restore"

**Cause**: Restored database is corrupted  
**Solution**:
- System automatically rolled back
- Old database is in quarantine
- Check logs for cause
- Use older backup

### Problem: "Restore failed and rollback failed (CRITICAL)"

**Cause**: Multiple failures - system now in unsafe state  
**Solution**:
1. Stop the application immediately
2. Check storage/backups/quarantine/ for old database
3. Manually restore from quarantine (see Emergency Recovery)
4. Call system administrator

---

## Security Notes

### 1. Backup Encryption

Backups are encrypted with AES-256-CBC if BACKUP_ENCRYPTION_KEY is set:

```env
BACKUP_ENCRYPTION_KEY=very-secure-key-here
```

### 2. Legal Wording

The legal acknowledgment text cannot be bypassed:
- Checkbox must be checked
- Confirmation text must be exactly "RESTORE" (case-sensitive)
- Restore reason must be at least 10 characters

### 3. Immutable Audit Trail

RestoreAuditLog table has no UPDATE_AT column:
- Records can only be inserted
- Records cannot be modified or deleted
- Tamper-evident for examination authority

### 4. Role-Based Restrictions

- Restore permissions strictly enforced by policy
- Regional/district admins cannot access other regions/districts
- Quarantine recovery limited to super admins

### 5. Maintenance Mode

During restore, system enters maintenance mode:
- All users locked out
- Database connections closed
- Prevents data changes during restore

---

## Files Created

```
app/Models/
├─ RestoreAuditLog.php                (Audit trail model)

app/Services/
├─ HardenedRestoreService.php          (Core restore engine)

app/Policies/
├─ HardenedRestorePolicy.php           (Authorization policy)

app/Http/Controllers/
├─ HardenedRestoreController.php       (REST API)

routes/
├─ hardened-restore.php                (API routes)

database/migrations/
├─ 2026_02_02_000000_create_restore_audit_logs_table.php

docs/
├─ HARDENED_RESTORE_SYSTEM.md          (This file)
```

---

## Version History

- **1.0** (2026-02-02): Initial release with SQLite hardening, legal compliance, and role-aware access
