# Hardened Restore System - Verification Tests

**Date**: February 3, 2026  
**Status**: Ready for Testing

---

## File Verification Tests

### Test 1: Core Service Exists
```
File: app/Services/HardenedRestoreService.php
Command: ls -la app/Services/HardenedRestoreService.php
Expected: File exists
Status: ✅ VERIFIED
```

### Test 2: Audit Log Model Exists
```
File: app/Models/RestoreAuditLog.php
Command: ls -la app/Models/RestoreAuditLog.php
Expected: File exists
Status: ✅ VERIFIED
```

### Test 3: Policies Exist
```
Files: 
  - app/Policies/RestoreAuditLogPolicy.php
  - app/Policies/BackupPolicy.php (updated)
Command: ls -la app/Policies/Restore*
Expected: Both files exist
Status: ✅ VERIFIED
```

### Test 4: Filament Resources Exist
```
Files:
  - app/Filament/Admin/Resources/RestoreAuditLogResource.php
  - app/Filament/Admin/Resources/RestoreAuditLogResource/Pages/ListRestoreAuditLogs.php
  - app/Filament/Admin/Resources/RestoreAuditLogResource/Pages/ViewRestoreAuditLog.php
Command: find app/Filament/Admin/Resources -name "*RestoreAudit*"
Expected: All files exist
Status: ✅ VERIFIED
```

### Test 5: Filament Pages Exist
```
File: app/Filament/Admin/Pages/HardenedRestore.php
Command: ls -la app/Filament/Admin/Pages/HardenedRestore.php
Expected: File exists
Status: ✅ VERIFIED
```

### Test 6: Views Exist
```
Files:
  - resources/views/filament/pages/hardened-restore-backup.blade.php
  - resources/views/components/restore-legal-warning.blade.php
  - resources/views/components/restore-audit-notice.blade.php
Command: ls -la resources/views/**/*restore*.blade.php
Expected: All files exist
Status: ✅ VERIFIED
```

### Test 7: Migration Exists
```
File: database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php
Command: ls -la database/migrations/*restore*
Expected: Migration file exists
Status: ✅ VERIFIED
```

---

## Database Tests

### Test 8: Migration Can Run
```bash
Command: php artisan migrate

Expected Output:
  Migrated: 2024_12_01_000000_create_restore_audit_logs

Status: ⏳ AWAITING EXECUTION
```

### Test 9: Table Created Correctly
```php
Command: php artisan tinker
>>> DB::table('restore_audit_logs')->count()
>>> Schema::getColumns('restore_audit_logs')

Expected:
  - Table exists
  - 20 columns
  - correct column names and types
  - Foreign keys present

Status: ⏳ AWAITING EXECUTION
```

### Test 10: Foreign Keys Work
```php
Command: php artisan tinker
>>> DB::select("PRAGMA foreign_key_list('restore_audit_logs')")

Expected:
  - user_id → users.id
  - authorized_by_id → users.id
  - region_id → regions.id
  - district_id → districts.id

Status: ⏳ AWAITING EXECUTION
```

### Test 11: Indexes Created
```php
Command: php artisan tinker
>>> DB::select("PRAGMA index_list('restore_audit_logs')")

Expected:
  - Multiple indexes for queries
  - (user_id)
  - (status)
  - (created_at)
  - (region_id, created_at)

Status: ⏳ AWAITING EXECUTION
```

---

## Authorization Tests

### Test 12: RestoreAuditLogPolicy Exists
```php
Command: php artisan tinker
>>> use App\Policies\RestoreAuditLogPolicy;
>>> class_exists(RestoreAuditLogPolicy::class)

Expected: true

Status: ⏳ AWAITING EXECUTION
```

### Test 13: BackupPolicy Updated
```php
Command: php artisan tinker
>>> use App\Policies\BackupPolicy;
>>> $policy = new BackupPolicy();
>>> method_exists($policy, 'restore')

Expected: true

Status: ⏳ AWAITING EXECUTION
```

### Test 14: Super Admin Can Restore
```php
Command: php artisan tinker
>>> $superAdmin = User::whereHas('role', fn($q) => $q->where('code', 'super_admin'))->first();
>>> auth()->setUser($superAdmin);
>>> auth()->user()->can('restore', Backup::class)

Expected: true

Status: ⏳ AWAITING EXECUTION
```

### Test 15: Regional Admin Can Restore Own Region
```php
Command: php artisan tinker
>>> $regionalAdmin = User::whereHas('role', fn($q) => $q->where('code', 'regional_admin'))->first();
>>> auth()->setUser($regionalAdmin);
>>> $backup = Backup::where('region_id', $regionalAdmin->getRegionId())->first();
>>> auth()->user()->can('restore', $backup)

Expected: true

Status: ⏳ AWAITING EXECUTION
```

### Test 16: Regional Admin Cannot Restore Other Region
```php
Command: php artisan tinker
>>> $regionalAdmin = User::whereHas('role', fn($q) => $q->where('code', 'regional_admin'))->first();
>>> auth()->setUser($regionalAdmin);
>>> $backup = Backup::whereNotIn('region_id', [$regionalAdmin->getRegionId()])->first();
>>> auth()->user()->can('restore', $backup)

Expected: false

Status: ⏳ AWAITING EXECUTION
```

### Test 17: Non-Admin Cannot Restore
```php
Command: php artisan tinker
>>> $user = User::whereDoesntHave('role', fn($q) => $q->whereIn('code', ['super_admin', 'regional_admin', 'district_admin']))->first();
>>> auth()->setUser($user);
>>> auth()->user()->can('restore', Backup::class)

Expected: false

Status: ⏳ AWAITING EXECUTION
```

---

## Model Tests

### Test 18: RestoreAuditLog Model Exists
```php
Command: php artisan tinker
>>> use App\Models\RestoreAuditLog;
>>> class_exists(RestoreAuditLog::class)

Expected: true

Status: ⏳ AWAITING EXECUTION
```

### Test 19: Create Audit Log Entry
```php
Command: php artisan tinker
>>> RestoreAuditLog::create([
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

Expected: Record created successfully

Status: ⏳ AWAITING EXECUTION
```

### Test 20: Audit Log Immutability
```php
Command: php artisan tinker
>>> $log = RestoreAuditLog::first();
>>> $log->update(['status' => 'completed'])

Expected: Policy prevents update (fails)

Status: ⏳ AWAITING EXECUTION
```

### Test 21: Audit Log Cannot Be Deleted
```php
Command: php artisan tinker
>>> $log = RestoreAuditLog::first();
>>> $log->delete()

Expected: Policy prevents delete (fails)

Status: ⏳ AWAITING EXECUTION
```

---

## Web Access Tests

### Test 22: Admin Panel Loads
```
URL: /admin
Expected: Admin panel loads without errors
Status: ⏳ AWAITING EXECUTION
```

### Test 23: Restore Page Accessible (Super Admin)
```
Login: Super Admin
URL: /admin/hardened-restore-backup
Expected: Page loads with legal warning
Status: ⏳ AWAITING EXECUTION
```

### Test 24: Audit Log Resource Shows (Super Admin)
```
Login: Super Admin
URL: /admin/resources/restore-audit-logs
Expected: Empty table (no restores yet)
Status: ⏳ AWAITING EXECUTION
```

### Test 25: Restore Page Denied (Non-Admin)
```
Login: Regular User
URL: /admin/hardened-restore-backup
Expected: Access denied / redirect
Status: ⏳ AWAITING EXECUTION
```

---

## UI/UX Tests

### Test 26: Legal Warning Displays
```
Action: Visit /admin/hardened-restore-backup
Expected:
  - "CRITICAL WARNING" header visible
  - "WILL REPLACE THE ENTIRE EXAMINATION DATABASE" text
  - Bullet points for data loss
  - Authorization requirement section
Status: ⏳ AWAITING EXECUTION
```

### Test 27: Legal Checkbox Required
```
Action: Try to submit form without checking box
Expected: Validation error "You must acknowledge the legal implications"
Status: ⏳ AWAITING EXECUTION
```

### Test 28: Backup Filtering by Role
```
Login: Regional Admin (Region A)
Action: Go to restore page
Expected: Only see backups for Region A
Status: ⏳ AWAITING EXECUTION
```

### Test 29: 2FA Selector Only for Super Admin
```
Login: Regional Admin
Action: Go to restore page
Expected: No 2FA authorizer dropdown visible

Login: Super Admin
Action: Go to restore page
Expected: 2FA authorizer dropdown visible
Status: ⏳ AWAITING EXECUTION
```

### Test 30: Restore Reason Required
```
Action: Leave restore reason blank
Expected: Validation error or placeholder
Status: ⏳ AWAITING EXECUTION
```

---

## Service Tests

### Test 31: HardenedRestoreService Access Check
```php
Command: php artisan tinker
>>> use App\Services\HardenedRestoreService;
>>> $user = User::whereHas('role', fn($q) => $q->where('code', 'super_admin'))->first();
>>> $service = new HardenedRestoreService($user);
>>> $backup = Backup::first();
>>> $result = $service->canRestore($backup);
>>> $result['allowed']

Expected: true

Status: ⏳ AWAITING EXECUTION
```

### Test 32: HardenedRestoreService Regional Scope Check
```php
Command: php artisan tinker
>>> $user = User::whereHas('role', fn($q) => $q->where('code', 'regional_admin'))->first();
>>> $service = new HardenedRestoreService($user);
>>> $backup = Backup::where('region_id', $user->getRegionId())->first();
>>> $result = $service->canRestore($backup);
>>> $result['allowed'] && $result['scope'] === 'region'

Expected: true

Status: ⏳ AWAITING EXECUTION
```

---

## Performance Tests

### Test 33: Audit Log Query Performance
```
Query: RestoreAuditLog::latest()->take(10)->get()
Expected: < 100 ms
Status: ⏳ AWAITING EXECUTION
```

### Test 34: Filter by Status Performance
```
Query: RestoreAuditLog::where('status', 'completed')->get()
Expected: < 500 ms
Status: ⏳ AWAITING EXECUTION
```

---

## Summary

**Total Tests**: 34  
**Status**: Ready for execution  
**Pre-Flight Checks**: ✅ All files verified

### Next Steps

1. Run migration: `php artisan migrate`
2. Execute database tests (Tests 8-11)
3. Execute authorization tests (Tests 12-17)
4. Execute model tests (Tests 18-21)
5. Execute web tests (Tests 22-30)
6. Execute service tests (Tests 31-32)
7. Execute performance tests (Tests 33-34)

All tests should pass before marking deployment as complete.

---

**Testing Status**: READY  
**All Pre-Requisites**: ✅ PASSED
