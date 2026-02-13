# Phase 2: Database Migration - Execution Guide

**Status**: Ready for Execution  
**Date**: February 3, 2026  
**Estimated Duration**: 5 minutes

---

## Overview

Phase 2 creates the `restore_audit_logs` table in the database. This table stores immutable records of all restore operations for NECTA compliance.

---

## Prerequisites Check

Before running migration, verify:

```bash
# 1. Check Laravel installation
ls -la artisan
# Expected: File exists and is executable

# 2. Check migration file exists
ls -la database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php
# Expected: File exists

# 3. Check database connection
php artisan config:show | grep DB_
# Expected: Database configuration visible
```

---

## Migration Execution

### Option A: Quick Command (Recommended)

```bash
cd /home/prosmart-technologies/SOL/irms
php artisan migrate
```

**Expected Output**:
```
Migrating: 2024_12_01_000000_create_restore_audit_logs
Migrated:  2024_12_01_000000_create_restore_audit_logs (XXX.XXms)
```

### Option B: Using Script

```bash
cd /home/prosmart-technologies/SOL/irms
bash PHASE_2_MIGRATION_EXECUTION.sh
```

This will:
1. Run the migration
2. Verify table creation
3. Check table structure
4. Verify model connection
5. Display summary

### Option C: Step-by-Step (Most Detailed)

```bash
cd /home/prosmart-technologies/SOL/irms

# Step 1: Run migration
php artisan migrate

# Step 2: Verify table exists
php artisan tinker
>>> Schema::hasTable('restore_audit_logs')
# Expected: true

# Step 3: Count columns
>>> Schema::getColumns('restore_audit_logs')
# Expected: 20 columns

# Step 4: Test model
>>> RestoreAuditLog::count()
# Expected: 0

# Step 5: Exit tinker
>>> exit()
```

---

## Detailed Verification

### Verify Table Exists

```bash
php artisan tinker
>>> use Illuminate\Support\Facades\Schema;
>>> Schema::hasTable('restore_audit_logs')
```

**Expected Result**: `true`

### Verify Column Count

```bash
php artisan tinker
>>> Schema::getColumns('restore_audit_logs')
```

**Expected**: Array with 20 columns

**Columns**:
```
1. id
2. user_id
3. authorized_by_id
4. backup_id
5. backup_filename
6. backup_hash
7. scope_type
8. region_id
9. district_id
10. restore_reason
11. legal_acknowledgment
12. legal_acknowledged
13. ip_address
14. user_agent
15. status
16. error_message
17. initiated_at
18. confirmed_at
19. executed_at
20. completed_at
21. created_at
(Note: updated_at is null - immutable design)
```

### Verify Foreign Keys

```bash
php artisan tinker
>>> DB::select("PRAGMA foreign_key_list('restore_audit_logs')")
```

**Expected**: 4 foreign keys
- user_id → users.id
- authorized_by_id → users.id
- region_id → regions.id
- district_id → districts.id

### Verify Indexes

```bash
php artisan tinker
>>> DB::select("PRAGMA index_list('restore_audit_logs')")
```

**Expected**: Multiple indexes for query optimization

### Verify Model Works

```bash
php artisan tinker
>>> use App\Models\RestoreAuditLog;
>>> RestoreAuditLog::count()
```

**Expected Result**: `0` (empty table, as expected)

---

## What Gets Created

### Table: `restore_audit_logs`

| Column | Type | Nullable | Key | Notes |
|--------|------|----------|-----|-------|
| id | BIGINT | No | PK | Auto-increment |
| user_id | BIGINT | No | FK | Operator |
| authorized_by_id | BIGINT | Yes | FK | Optional 2FA auth |
| backup_id | VARCHAR | No | - | Backup ID |
| backup_filename | VARCHAR | No | - | Archive filename |
| backup_hash | VARCHAR | No | - | SHA-256 hash |
| scope_type | ENUM | No | - | full/region/district |
| region_id | BIGINT | Yes | FK | Regional scope |
| district_id | BIGINT | Yes | FK | District scope |
| restore_reason | LONGTEXT | No | - | Required explanation |
| legal_acknowledgment | LONGTEXT | No | - | Legal text |
| legal_acknowledged | BOOLEAN | No | - | Checkbox value |
| ip_address | VARCHAR | No | - | IPv4/IPv6 |
| user_agent | TEXT | No | - | Browser info |
| status | ENUM | No | - | Operation status |
| error_message | LONGTEXT | Yes | - | Error if failed |
| initiated_at | TIMESTAMP | No | - | Request time |
| confirmed_at | TIMESTAMP | Yes | - | Confirmation time |
| executed_at | TIMESTAMP | Yes | - | Execution start |
| completed_at | TIMESTAMP | Yes | - | Completion time |
| created_at | TIMESTAMP | No | IDX | Record creation |

### Indexes Created

```sql
INDEX user_id
INDEX authorized_by_id
INDEX status
INDEX scope_type
INDEX created_at
INDEX (region_id, created_at)
INDEX (district_id, created_at)
```

### Foreign Keys

```sql
user_id → users.id (ON DELETE RESTRICT)
authorized_by_id → users.id (ON DELETE RESTRICT)
region_id → regions.id (ON DELETE RESTRICT)
district_id → districts.id (ON DELETE RESTRICT)
```

---

## Troubleshooting

### Issue: "Migration not found"

**Solution**:
```bash
# Verify migration file exists
ls -la database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php

# If not found, check you copied the file correctly
# Should be in: database/migrations/2024_12_01_000000_create_restore_audit_logs_table.php
```

### Issue: "Table already exists"

**Solution**:
```bash
# The table already exists from a previous migration
# This is safe - migration will be recorded in migrations table

# Verify by checking migrations table
php artisan tinker
>>> DB::table('migrations')->where('migration', 'like', '%restore_audit%')->get()
```

### Issue: "Foreign key constraint failed"

**Solution**:
```bash
# Check if referenced tables exist
php artisan tinker
>>> Schema::hasTable('users')
>>> Schema::hasTable('regions')
>>> Schema::hasTable('districts')

# All should return true
# If false, run migrations for those tables first:
# php artisan migrate --path=database/migrations
```

### Issue: "Database connection error"

**Solution**:
```bash
# Check .env file
cat .env | grep DB_

# Verify credentials are correct
php artisan db:show

# If error, check database service is running
# For SQLite (default):
ls -la database/database.sqlite
```

### Issue: "Permission denied"

**Solution**:
```bash
# Ensure proper permissions
chmod +x artisan
chmod 775 database/
chmod 666 database/database.sqlite
php artisan migrate
```

---

## Success Criteria

Migration successful if ALL of these pass:

- [x] Migration command runs without errors
- [x] "Migrated: 2024_12_01_..." message appears
- [x] No warnings or exceptions
- [x] Table `restore_audit_logs` created
- [x] 20 columns present
- [x] 4 foreign keys created
- [x] Indexes optimized
- [x] Model can query table (RestoreAuditLog::count() = 0)

---

## Next Phase: Cache Clearing

After successful migration, proceed to Phase 3:

```bash
# Clear all caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

---

## Rollback (If Needed)

If you need to undo the migration:

```bash
php artisan migrate:rollback --step=1
```

**This will**:
- Drop the `restore_audit_logs` table
- Remove migration record

**After rollback**:
- Backup data is NOT affected
- Other tables remain intact
- Can migrate again later

---

## Migration Verification SQL

If you prefer to verify using SQL directly:

```sql
-- SQLite
PRAGMA table_info(restore_audit_logs);

-- MySQL
DESCRIBE restore_audit_logs;

-- PostgreSQL
\d restore_audit_logs;
```

---

## Performance Notes

**Migration Time**: < 1 second  
**Table Size**: Empty (0 bytes)  
**Index Creation**: < 100ms  
**Memory Used**: < 1MB

---

## Security Notes

- ✅ Foreign keys enforce referential integrity
- ✅ Immutable design (no updated_at)
- ✅ RESTRICT delete prevents accidental removal
- ✅ Indexes optimize security queries
- ✅ Enum types restrict status values

---

## Backup Before Migration

**Recommended**:
```bash
# Create backup before migration
sqlite3 database/database.sqlite ".backup database_backup_2026_02_03.sqlite"

# Or for MySQL:
mysqldump -u user -p database > backup_2026_02_03.sql
```

---

## Post-Migration Checklist

- [ ] Migration ran successfully
- [ ] No errors in console
- [ ] Table created with correct schema
- [ ] Model can query table
- [ ] Foreign keys working
- [ ] Indexes created
- [ ] No data loss
- [ ] Database intact

---

## Proceed to Phase 3

Once migration is complete and verified:

```bash
# Phase 3: Clear Caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

Then verify admin panel loads:
```
URL: /admin
Expected: Admin panel loads without errors
```

---

## Command Summary

```bash
# Execute migration
php artisan migrate

# Verify
php artisan tinker
>>> Schema::hasTable('restore_audit_logs')  # true
>>> RestoreAuditLog::count()  # 0
>>> exit()

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Verify admin loads
# Visit: http://localhost/admin
```

---

**Phase 2 Complete When**: ✅ Table created & verified  
**Duration**: ~5 minutes  
**Next Step**: Phase 3 - Cache Clearing  
**Reference**: NEXT_STEPS_DEPLOYMENT.md

---

## Questions?

Refer to:
- **Technical Details**: HARDENED_RESTORE_SYSTEM_IMPLEMENTATION.md
- **Full Checklist**: HARDENED_RESTORE_DEPLOYMENT_CHECKLIST.md
- **Overall Status**: DEPLOYMENT_STATUS_REPORT.md
