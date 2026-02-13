# Phase 2: Database Migration - Execution Checklist

**Date**: February 3, 2026  
**Estimated Duration**: 5-10 minutes  
**Status**: Ready to Execute

---

## Pre-Migration Verification

Before running the migration, verify these prerequisites:

### Check 1: Laravel Installation
```bash
ls -la artisan
```
- [ ] File exists
- [ ] File is executable

### Check 2: Migration File Exists
```bash
ls -la database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php
```
- [ ] File exists
- [ ] File is readable

### Check 3: Database Connection
```bash
php artisan db:show
```
- [ ] Shows database information
- [ ] Connection successful

### Check 4: Composer Dependencies
```bash
php artisan --version
```
- [ ] Shows Laravel version
- [ ] No errors

---

## Migration Execution Steps

### Step 1: Navigate to Project Root
```bash
cd /home/prosmart-technologies/SOL/irms
```
- [ ] Command executed
- [ ] Current directory is correct (verify with `pwd`)

### Step 2: Run Migration
```bash
php artisan migrate
```
- [ ] Command executed without errors
- [ ] See message: "Migrated: 2024_12_01_000000_create_restore_audit_logs"
- [ ] Execution time shown (e.g., "XXX.XXms")

---

## Post-Migration Verification

### Verification 1: Table Exists
```bash
php artisan tinker
>>> Schema::hasTable('restore_audit_logs')
```
- [ ] Returns: `true`
- [ ] No exceptions thrown

### Verification 2: Column Count
```bash
>>> Schema::getColumns('restore_audit_logs')
```
- [ ] Returns array with 20 columns
- [ ] All expected columns present:
  - [ ] id
  - [ ] user_id
  - [ ] authorized_by_id
  - [ ] backup_id
  - [ ] backup_filename
  - [ ] backup_hash
  - [ ] scope_type
  - [ ] region_id
  - [ ] district_id
  - [ ] restore_reason
  - [ ] legal_acknowledgment
  - [ ] legal_acknowledged
  - [ ] ip_address
  - [ ] user_agent
  - [ ] status
  - [ ] error_message
  - [ ] initiated_at
  - [ ] confirmed_at
  - [ ] executed_at
  - [ ] completed_at
  - [ ] created_at

### Verification 3: Foreign Keys
```bash
>>> DB::select("PRAGMA foreign_key_list('restore_audit_logs')")
```
- [ ] Shows 4 foreign keys
- [ ] user_id → users.id
- [ ] authorized_by_id → users.id
- [ ] region_id → regions.id
- [ ] district_id → districts.id

### Verification 4: Model Connection
```bash
>>> use App\Models\RestoreAuditLog;
>>> RestoreAuditLog::count()
```
- [ ] Returns: `0`
- [ ] No exceptions thrown

### Verification 5: Migration Recorded
```bash
>>> DB::table('migrations')->where('migration', 'like', '%restore_audit%')->get()
```
- [ ] Shows one record
- [ ] Batch number recorded

### Step 6: Exit Tinker
```bash
>>> exit()
```
- [ ] Tinker shell closed

---

## Cache Clearing Phase

### Clear Cache
```bash
php artisan cache:clear
```
- [ ] Success message: "Application cache cleared"
- [ ] No errors

### Rebuild Config
```bash
php artisan config:cache
```
- [ ] Success message: "Configuration cached"
- [ ] No errors

### Rebuild Routes
```bash
php artisan route:cache
```
- [ ] Success message: "Routes cached"
- [ ] No errors

---

## Application Verification

### Verify Admin Panel Loads
Navigate to: `http://localhost/admin`
- [ ] Page loads without errors
- [ ] Navigation menu visible
- [ ] No console errors (F12 → Console)
- [ ] Login/admin interface visible

### Verify Database Migrations Table
Check that migration was recorded:
```bash
php artisan migrate:status
```
- [ ] Shows 2024_12_01_000000_create_restore_audit_logs_table
- [ ] Status shows "Ran"

---

## Log File Verification

### Check Application Logs
```bash
tail -n 50 storage/logs/laravel.log
```
- [ ] No error messages
- [ ] No exception traces
- [ ] No foreign key violations

### Check for Migration Errors
```bash
grep -i "error\|exception\|failed" storage/logs/laravel.log
```
- [ ] No matching lines (or only old entries)

---

## Database Integrity Checks

### Verify No Data Loss
```bash
php artisan tinker
>>> DB::table('users')->count()
>>> DB::table('regions')->count()
>>> DB::table('districts')->count()
>>> exit()
```
- [ ] All tables still contain data
- [ ] Counts are same as before migration

### Verify Foreign Key Constraints
```bash
php artisan tinker
>>> DB::select("PRAGMA foreign_keys")
>>> exit()
```
- [ ] Returns 1 (enabled)
- [ ] Constraints are active

---

## Phase Completion Checklist

**Migration Execution**
- [ ] Migration ran successfully
- [ ] No error messages
- [ ] Execution time recorded

**Table Verification**
- [ ] Table `restore_audit_logs` created
- [ ] 20 columns present
- [ ] All column names correct
- [ ] Column types correct

**Relationships**
- [ ] 4 foreign keys created
- [ ] Foreign key constraints working
- [ ] ON DELETE RESTRICT enforced

**Indexes**
- [ ] Multiple indexes created
- [ ] Query optimization indexes present

**Model Integration**
- [ ] RestoreAuditLog model can query table
- [ ] Model relations work
- [ ] No connection errors

**Application Health**
- [ ] Admin panel loads
- [ ] No Laravel errors
- [ ] No database errors
- [ ] Migration recorded in database

**Caching**
- [ ] Application cache cleared
- [ ] Config cache rebuilt
- [ ] Route cache rebuilt

---

## Sign-Off

### Migration Status
- [ ] ✅ SUCCESSFUL
- [ ] ❌ FAILED (see troubleshooting)

### Next Phase Readiness
- [ ] Ready for Phase 3 (Verification & Testing)
- [ ] All checks passed
- [ ] No blocking issues

---

## Troubleshooting Checklist

If any check failed, investigate:

### Migration Failed
- [ ] Check database connection: `php artisan db:show`
- [ ] Check migration file exists: `ls database/migrations/2024_12_01*`
- [ ] Check Laravel version: `php artisan --version`
- [ ] Review error message carefully
- [ ] Check `storage/logs/laravel.log`

### Table Not Created
- [ ] Verify migration ran: `php artisan migrate:status`
- [ ] Check migration file syntax: `cat database/migrations/2024_12_01*`
- [ ] Verify foreign key tables exist: `Schema::hasTable('users')`
- [ ] Run migration step by step (see PHASE_2_DATABASE_MIGRATION.md)

### Model Won't Connect
- [ ] Check table exists: `Schema::hasTable('restore_audit_logs')`
- [ ] Verify model class: `class_exists(RestoreAuditLog::class)`
- [ ] Check namespace: `use App\Models\RestoreAuditLog`

### Admin Panel Won't Load
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Check composer autoload: `composer dump-autoload`
- [ ] Verify permissions: `chmod -R 755 app/`
- [ ] Check `storage/logs/laravel.log` for errors

### Foreign Key Errors
- [ ] Verify users table exists: `Schema::hasTable('users')`
- [ ] Verify regions table exists: `Schema::hasTable('regions')`
- [ ] Verify districts table exists: `Schema::hasTable('districts')`
- [ ] Check foreign key constraints enabled

---

## Success Criteria Summary

**Phase 2 is SUCCESSFUL when:**

✅ Migration executes without errors  
✅ Table `restore_audit_logs` created  
✅ 20 columns present with correct types  
✅ 4 foreign keys created and working  
✅ Model can query table  
✅ Admin panel loads  
✅ No application errors in logs  
✅ All checks passed above  

---

## Post-Phase 2

Once ALL checks are complete and marked:

1. **Document Results**: Note any issues or observations
2. **Update Status**: Mark PHASE_2 as COMPLETE in DEPLOYMENT_STATUS_REPORT.md
3. **Proceed to Phase 3**: Test with different roles and verify functionality
4. **Run Test Suite**: Execute all 34 verification tests

---

## Progress Tracking

```
Phase 1: File Deployment         ✅ COMPLETE
Phase 2: Database Migration      ⏳ IN PROGRESS (YOU ARE HERE)
Phase 3: Verification            ⏳ PENDING
Phase 4: Role Testing            ⏳ PENDING
Phases 5-9: Final Testing        ⏳ PENDING
```

**Estimated Remaining Time**: ~60 minutes (Phases 3-9)

---

## Quick Reference Commands

```bash
# Migration
php artisan migrate

# Verify
php artisan tinker
>>> Schema::hasTable('restore_audit_logs')
>>> exit()

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Check status
php artisan migrate:status

# Check logs
tail storage/logs/laravel.log
```

---

**Checklist Version**: 1.0  
**Date**: February 3, 2026  
**Status**: Ready for Execution

---

## Let's Go! 🚀

Execute the commands in order and check off each item as you complete it.

See PHASE_2_QUICK_START.txt for command copy/paste reference.
