# Hardened Restore System - Quick Reference Card

**Print this for your operations team** 📋

---

## What This System Does

| Feature | Benefit |
|---------|---------|
| **SQLite Hardening** | Prevents corrupted/partial restores |
| **Legal Compliance** | NECTA-style governance & audit trails |
| **Role-Based Access** | Restrict who can restore what |
| **Automatic Rollback** | Recovers from failures automatically |
| **Immutable Audit Log** | Tamper-proof record for examination authority |

---

## Quick Reference: The 5 Steps

```
┌──────────────────────────────────────┐
│ STEP 1: VALIDATE BACKUP              │
│                                      │
│ Is the backup file valid?            │
│ - File exists ✓                      │
│ - ZIP valid ✓                        │
│ - Has database.sqlite ✓              │
│ - Checksums match ✓                  │
│                                      │
│ System aborts if ANY check fails     │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│ STEP 2: SHOW LEGAL NOTICE            │
│                                      │
│ "This operation will REPLACE the     │
│  ENTIRE examination database..."     │
│                                      │
│ Operator reads and understands       │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│ STEP 3: GET CONFIRMATION             │
│                                      │
│ [ ✓ ] I understand...                │
│ [ Type: RESTORE _____________ ]      │
│ [ Reason: Emergency... ]             │
│                                      │
│ ALL three fields required            │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│ STEP 4: ATOMIC RESTORE EXECUTION     │
│                                      │
│ 🔴 Maintenance mode ON               │
│ 📦 Current DB → quarantine           │
│ ✅ New DB extracted & validated      │
│ 🔄 Atomic file replacement           │
│ ✓ Post-restore verification          │
│ 🟢 Maintenance mode OFF              │
│                                      │
│ NO PARTIAL STATES POSSIBLE           │
└──────────────────────────────────────┘
              ↓
┌──────────────────────────────────────┐
│ STEP 5: RECORD AUDIT TRAIL           │
│                                      │
│ ✓ Operator name & role               │
│ ✓ Backup ID & hash                   │
│ ✓ Restore reason                     │
│ ✓ Complete timeline                  │
│ ✓ IP address & device info           │
│ ✓ Status (completed/failed/rolled)   │
│                                      │
│ IMMUTABLE RECORD (cannot modify)     │
└──────────────────────────────────────┘
```

---

## Permission Matrix (Who Can Restore What?)

```
╔════════════════════════════════════════════════════════════════╗
║ SUPER ADMIN (is_admin = true)                                  ║
╠════════════════════════════════════════════════════════════════╣
║ ✓ Restore ANY backup                                           ║
║ ✓ Restore ANY region                                           ║
║ ✓ Restore ANY district                                         ║
║ ✓ Recover from quarantine                                      ║
║ ✓ View ALL audit logs                                          ║
║ ✓ Export ALL audit reports                                     ║
╚════════════════════════════════════════════════════════════════╝

╔════════════════════════════════════════════════════════════════╗
║ REGIONAL ADMIN (role=regional_officer + region scope)         ║
╠════════════════════════════════════════════════════════════════╣
║ ✓ Restore their REGION only                                    ║
║ ✓ Restore districts within their region                        ║
║ ✗ Cannot restore other regions                                 ║
║ ✗ Cannot recover from quarantine                               ║
║ ✓ View REGIONAL audit logs only                                ║
║ ✓ Export REGIONAL audit reports only                           ║
╚════════════════════════════════════════════════════════════════╝

╔════════════════════════════════════════════════════════════════╗
║ DISTRICT ADMIN (role=district_supervisor + district scope)    ║
╠════════════════════════════════════════════════════════════════╣
║ ✓ Restore their DISTRICT only                                  ║
║ ✗ Cannot restore other districts                               ║
║ ✗ Cannot restore regions                                       ║
║ ✗ Cannot recover from quarantine                               ║
║ ✓ View DISTRICT audit logs only                                ║
║ ✓ Export DISTRICT audit reports only                           ║
╚════════════════════════════════════════════════════════════════╝

╔════════════════════════════════════════════════════════════════╗
║ ALL OTHER ROLES                                                ║
╠════════════════════════════════════════════════════════════════╣
║ ✗ Cannot perform ANY restore                                   ║
║ ✗ Cannot view audit logs                                       ║
║ ✗ Cannot export reports                                        ║
╚════════════════════════════════════════════════════════════════╝
```

---

## What Happens When...

### ✅ Restore Succeeds

```
✓ Application back online
✓ New database in production
✓ Old database in quarantine/2026-02-02_10-30-00_xxx/
✓ Audit log: status = 'completed'
✓ Operator can download audit log

Next: Verify data, notify examination authority
```

### ⚠️ Backup Validation Fails

```
✗ Restore never starts
✗ Application unchanged
✗ No maintenance mode activated
✓ Clear error message shown
✓ Audit log: status = 'initiated' (never progressed)

Next: Use different backup, fix file, retry
```

### 🔄 Restore Fails (but auto-rollback succeeds)

```
✗ Restore started but encountered error
✓ Automatic rollback from quarantine
✓ Original database restored
✓ Application back online
✓ Old database restored
✓ Audit log: status = 'rolled_back' with error message

Next: Review error, contact administrator
```

### 🔴 CRITICAL: Restore Fails & Rollback Fails

```
✗ Restore failed
✗ Auto-rollback also failed
✗ Application in MAINTENANCE MODE
✓ Quarantine directory preserved
✓ Audit log: status = 'failed' with error message

EMERGENCY RECOVERY REQUIRED:
1. Manually restore from storage/backups/quarantine/
2. Call system administrator immediately
3. Review logs: storage/logs/laravel.log
```

---

## Common Error Messages & Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| "Backup file does not exist" | Wrong path | Check: `ls storage/backups/irms-backup-*.zip` |
| "Backup file is not a valid ZIP" | File corrupted | Use older backup from list |
| "Missing required file: database.sqlite" | Incomplete backup | Use different backup |
| "Checksum validation failed" | File corrupted in transit | Re-download backup |
| "Cannot connect to database" | Restored DB corrupted | System auto-rolled back to original |
| "Cannot move to quarantine" | Permission/disk issue | Check: `ls -la storage/backups/` and free disk |
| "CRITICAL: Cannot rollback" | System in unsafe state | Manual recovery required (see EMERGENCY) |

---

## File Locations Cheat Sheet

```
Current Database
└─ database/database.sqlite                    ← Production database

Backups
└─ storage/backups/
   ├─ irms-backup-full-system-2026-02-02_*.zip    ← Full backups
   ├─ irms-backup-metadata-only-*.zip             ← Metadata only
   └─ ...other backups...

Quarantine (Automatic Recovery)
└─ storage/backups/quarantine/
   ├─ 2026-02-02_10-30-00_a1b2c3d4e/
   │  ├─ database.sqlite                     ← Your old DB
   │  ├─ database.sqlite-wal
   │  └─ database.sqlite-shm
   └─ ...other quarantine backups...

Application Logs
└─ storage/logs/
   └─ laravel.log                            ← Check for errors

Maintenance Mode Flag
└─ storage/framework/down                    ← Present when in maintenance

Sandbox (Temporary)
└─ storage/backups/sandbox/
   └─ ...temporary extraction files...       ← Auto-cleaned up
```

---

## API Endpoints Cheat Sheet

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/restore/legal-text` | GET | Get legal acknowledgment text |
| `/api/restore/validate` | POST | Validate backup before restore |
| `/api/restore/confirm` | POST | Get confirmation page data |
| `/api/restore/execute` | POST | ⚠️ EXECUTE RESTORE (destructive) |
| `/api/restore/audit-logs` | GET | View restore operations |
| `/api/restore/audit-export` | POST | Export audit logs CSV/JSON |

---

## Database Status Checks (Tinker)

```bash
php artisan tinker
```

```php
# Check restore audit table
RestoreAuditLog::count()              # How many restores?
RestoreAuditLog::completed()->count() # Completed restores
RestoreAuditLog::failed()->count()    # Failed restores
RestoreAuditLog::latest(5)->get()     # Last 5 restores

# Check database integrity
DB::selectOne('PRAGMA integrity_check')  # Should return "ok"

# Check foreign keys
DB::selectOne('PRAGMA foreign_keys')     # Should return 1

# Export audit for examination authority
RestoreAuditLog::recent(30)
  ->get()
  ->map(fn($log) => $log->toAuditExport())
  ->toJson()
```

---

## Emergency Recovery (Step-by-Step)

### Situation: Restore Failed & Automatic Rollback Failed

**Status**: Application in MAINTENANCE MODE  
**Duration**: Until manually recovered

**Action**:

```bash
# 1. List quarantine directories
ls -la storage/backups/quarantine/

# Output: 2026-02-02_10-30-00_a1b2c3d4e/
#         2026-01-15_14-22-00_xyz789abc/

# 2. Restore from most recent quarantine
cp storage/backups/quarantine/2026-02-02_10-30-00_a1b2c3d4e/database.sqlite \
   database/database.sqlite

# 3. Fix permissions
chmod 640 database/database.sqlite

# 4. Remove maintenance mode
rm storage/framework/down

# 5. Verify database works
php artisan tinker
>>> DB::selectOne('PRAGMA integrity_check')
# Should return: stdClass { integrity_check: "ok" }

# 6. Call system administrator
# Provide:
# - Error message from logs
# - Quarantine location used
# - Backup ID that failed
```

---

## Monitoring Commands

```bash
# Watch restore operations in real-time
tail -f storage/logs/laravel.log | grep -i restore

# Check quarantine size (grows with each failed restore)
du -sh storage/backups/quarantine/

# List all completed restores
php artisan tinker
>>> RestoreAuditLog::completed()->latest(10)->get()

# Find restores by operator
php artisan tinker
>>> RestoreAuditLog::where('user_id', 1)->get()

# Find restores by reason
php artisan tinker
>>> RestoreAuditLog::where('restore_reason', 'LIKE', '%corruption%')->get()

# Export for examination authority
php artisan tinker
>>> $logs = RestoreAuditLog::recent(30)->get();
>>> $csv = json_encode($logs->map(fn($l) => $l->toAuditExport()));
>>> echo $csv;
```

---

## Training Checklist (Operators)

- [ ] Read HARDENED_RESTORE_QUICKSTART.md
- [ ] Understand 5-step restore process
- [ ] Know which databases they can restore
- [ ] Know how to write restore reason (min 10 chars)
- [ ] Understand "RESTORE" confirmation requirement
- [ ] Know quarantine location (for recovery)
- [ ] Know who to call if something fails (admin)
- [ ] Know how to check audit logs
- [ ] Know they're being audited (full accountability)

---

## Common Questions

**Q: Can I undo a restore?**  
A: No, but your previous database is in quarantine. Contact your administrator.

**Q: How long are backups kept in quarantine?**  
A: Typically 30 days. Check with your system administrator.

**Q: What if I make a typo typing "RESTORE"?**  
A: The system will reject it. You must type exactly "RESTORE".

**Q: Can I restore while users are using the system?**  
A: System enters maintenance mode. All users are locked out.

**Q: Who can see the audit logs?**  
A: Super Admin (all), Regional Admin (their region), District Admin (their district).

**Q: What if two admins try to restore at same time?**  
A: Only the first one's restore will succeed. Others will get an error.

**Q: Can I export audit logs for examination authority?**  
A: Yes! POST /api/restore/audit-export?format=csv

**Q: Is the audit log permanent?**  
A: Yes! Records are immutable and cannot be modified or deleted.

---

## Version & Support

**System**: Hardened Restore System v1.0  
**Released**: 2026-02-02  
**Compliance**: NECTA-style examination governance  

**Documentation**:
- HARDENED_RESTORE_SYSTEM.md (full docs)
- HARDENED_RESTORE_QUICKSTART.md (quick start)
- HARDENED_RESTORE_VERIFICATION.md (testing)
- HARDENED_RESTORE_REFERENCE.md (this card)

---

## Quick Links

📘 Full Documentation: HARDENED_RESTORE_SYSTEM.md  
⚡ Quick Start: HARDENED_RESTORE_QUICKSTART.md  
✅ Verification: HARDENED_RESTORE_VERIFICATION.md  
📋 This Card: HARDENED_RESTORE_REFERENCE.md  

---

**🔐 Hardened. ⚖️ Auditable. 👥 Role-Aware. ✅ Production-Ready.**

Keep this card handy for quick reference!
