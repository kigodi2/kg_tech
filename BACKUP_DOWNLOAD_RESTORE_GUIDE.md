# Backup Download & Restore Guide

## Overview
The IRMS backup system supports two models:
1. **Legacy Backups** (Filament Resource) - Older backup system with manual creation/restore
2. **BackupLog** (Audit Trail) - New immutable backup audit trail from SQLiteBackupService

---

## Part 1: Download Backup

### Option A: Download via Admin Panel (Backup Model)
**Location:** Admin Panel → Backups

1. Navigate to the Backups list
2. Click the **Download** button (arrow icon) next to the backup
3. Click **Confirm** when prompted
4. The `.zip` file will download to your computer

**File Location:** Stored in `storage/app/` directory

### Option B: Download via REST API
**Endpoint:** `GET /api/backups/{backupId}`

```bash
# Get backup details
curl -X GET http://localhost/api/backups/5 \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Accept: application/json"

# Download file from path returned
```

### Option C: Manual Download from Server
**File Location:** `storage/backups/sqlite/`

```bash
# SSH into server
ssh user@server

# Navigate to backup directory
cd /home/prosmart-technologies/SOL/irms/storage/backups/sqlite/

# List backups
ls -lh

# Download specific backup
# Example: bak-full-2026-02-02-034415-8089d936.zip.enc
```

---

## Part 2: Restore from Backup

### ⚠️ IMPORTANT: Before Restoring
- **BACKUP CURRENT DATA FIRST** - Restoration overwrites your database
- Only admins can perform restore operations
- Application enters **MAINTENANCE MODE** during restore
- All users will see "Under Maintenance" message
- Restore cannot be interrupted mid-process

### Option A: Restore via Admin Panel (Recommended)
**Location:** Admin Panel → Backups → [Select Backup] → Restore

1. Go to Backups list
2. Click on the backup you want to restore
3. Click **Restore** button (red "Execute Restore" button)
4. Read the confirmation dialog carefully
5. Type `RESTORE` exactly in the confirmation field
6. Click **Execute Restore**
7. System enters maintenance mode
8. Restoration completes (may take 1-2 minutes)
9. Application automatically restarts

**What Happens During Restore:**
- Application enters maintenance mode
- Current database moved to quarantine (`storage/backups/quarantine/`)
- Backup file extracted and verified
- `database.sqlite` restored
- `database.sqlite-wal` restored (if exists)
- `database.sqlite-shm` restored (if exists)
- Database integrity checked
- Application returns to normal

### Option B: Restore via REST API

#### Step 1: Simulate Restore (Test Only)
```bash
curl -X POST http://localhost/api/backup/simulate-restore \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"backup_id": "bak-full-2026-02-02-034415-8089d936"}'
```

Response example:
```json
{
  "success": true,
  "validation": {
    "tables_found": 25,
    "row_counts": {
      "users": 150,
      "exam_years": 8,
      "schools": 42,
      "candidates": 1250
    },
    "fk_integrity": true,
    "schema_version": "2025_02_02"
  }
}
```

#### Step 2: Execute Actual Restore
```bash
curl -X POST http://localhost/api/backup/restore \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "backup_id": "bak-full-2026-02-02-034415-8089d936",
    "confirmed": true
  }'
```

Response:
```json
{
  "success": true,
  "message": "Backup restored successfully",
  "restored_at": "2026-02-02T10:30:45Z"
}
```

### Option C: Restore via Command Line

```bash
# SSH into server
ssh user@server
cd /home/prosmart-technologies/SOL/irms

# Trigger restore (Admin-only)
php artisan backup:restore bak-full-2026-02-02-034415-8089d936

# Monitor progress
tail -f storage/logs/laravel.log | grep -i restore
```

---

## Part 3: Backup Encryption & Decryption

### Download & Decrypt Backup Locally

All backups are encrypted with **AES-256**. To decrypt:

```bash
# Install OpenSSL (if not already installed)
# macOS: brew install openssl
# Ubuntu: sudo apt-get install openssl
# Windows: Download from https://slproweb.com/products/Win32OpenSSL.html

# Decrypt the backup
openssl enc -d -aes-256-cbc \
  -in bak-full-2026-02-02-034415-8089d936.zip.enc \
  -out bak-full-2026-02-02-034415-8089d936.zip \
  -K YOUR_BACKUP_ENCRYPTION_KEY_IN_HEX \
  -iv YOUR_IV_IN_HEX

# Extract the zip
unzip bak-full-2026-02-02-034415-8089d936.zip

# Now you can access:
# - database.sqlite
# - database.sqlite-wal
# - database.sqlite-shm
# - manifest.json
# - checksums.sha256
# - backup.sig
```

### Get Encryption Key

```bash
# From .env file
grep BACKUP_ENCRYPTION_KEY /home/prosmart-technologies/SOL/irms/.env

# Or via Laravel Tinker
php artisan tinker
>>> echo config('app.key');
```

---

## Part 4: Verify Backup Integrity

### Via Admin Panel
1. Go to Backups list
2. Click on backup → View details
3. Check:
   - ✓ Verified status
   - ✓ File hash (SHA-256)
   - ✓ Created by (admin name)
   - ✓ File size

### Via API
```bash
curl -X GET http://localhost/api/backup/validate \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -d '{"backup_id": "bak-full-2026-02-02-034415-8089d936"}'
```

Response:
```json
{
  "valid": true,
  "checksum_match": true,
  "file_exists": true,
  "size_bytes": 33296,
  "hash": "38986d88d8ca4323eedf86d6e43ae0f2c8716a6a3f8cbc6fd2e801cf21bd4675"
}
```

---

## Part 5: Rollback After Failed Restore

If restore fails:

1. **Check Status:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i restore
   ```

2. **Restore from Quarantine:**
   ```bash
   cd storage/backups/quarantine/
   ls -la
   # Copy database.sqlite back
   cp database.sqlite ../../database/database.sqlite
   ```

3. **Clear Maintenance Mode:**
   ```bash
   php artisan down --cancel
   ```

---

## Part 6: Backup Scheduling (Automatic)

Backups are automatically created:
- **Daily:** 2:00 AM UTC
- **Weekly:** Every Sunday 3:00 AM UTC  
- **Monthly:** First day of month 4:00 AM UTC

View scheduled backups:
```bash
php artisan schedule:list
```

Check backup logs:
```bash
php artisan tinker
>>> App\Models\BackupLog::backupOperations()->latest()->paginate(10)
```

---

## Part 7: Troubleshooting

### Backup Shows "Unencrypted"
The older Backup model may have unencrypted files. Recommendation:
- Create new backup using "Create Backup" button (uses new SQLiteBackupService)
- New backups are always AES-256 encrypted

### Restore Stuck in Maintenance Mode
```bash
# Cancel maintenance mode
php artisan down --cancel

# Check process status
ps aux | grep "php artisan"

# Kill stuck processes if needed
kill -9 PID_NUMBER
```

### File Not Found Error
```bash
# Verify backup file exists
ls -la storage/backups/sqlite/bak-full-*.zip.enc

# Check directory permissions
chmod 750 storage/backups/
chmod 750 storage/backups/sqlite/
chmod 750 storage/backups/quarantine/
chmod 750 storage/backups/sandbox/
```

### Insufficient Disk Space
```bash
# Check available space
df -h

# Delete old backups if needed
php artisan backup:cleanup --days=30
```

---

## Summary of Commands

| Task | Method | Command |
|------|--------|---------|
| Create Backup | UI | Admin Panel → Backups → Create Backup |
| Create Backup | CLI | `php artisan backup:create` |
| Download Backup | UI | Admin Panel → Backups → Download |
| List Backups | CLI | `php artisan backup:list` |
| Simulate Restore | API | `POST /api/backup/simulate-restore` |
| Execute Restore | UI | Admin Panel → Backups → Restore → Confirm |
| Execute Restore | CLI | `php artisan backup:restore ID` |
| Check Health | API | `GET /api/backup/health-metrics` |
| View Audit Log | CLI | `App\Models\BackupLog::backupOperations()` |

---

## Support
For detailed technical specifications, see:
- `SQLITE_BACKUP_RESTORE_SYSTEM.md` - Architecture & design
- `BACKUP_IMPLEMENTATION_INTEGRATION.md` - Setup & integration
- `BACKUP_IMPLEMENTATION_CHECKLIST.md` - Deployment verification
