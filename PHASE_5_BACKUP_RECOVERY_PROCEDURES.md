# Phase 5: Backup & Recovery Procedures

**Status**: 🔄 CRITICAL OPERATIONS  
**Document**: Backup & Disaster Recovery  
**Last Updated**: 2026-02-13  
**Responsible**: Database Administrator & Operations Team

---

## Table of Contents

1. [Backup Strategy](#backup-strategy)
2. [Backup Procedures](#backup-procedures)
3. [Recovery Procedures](#recovery-procedures)
4. [Disaster Scenarios](#disaster-scenarios)
5. [Testing & Verification](#testing--verification)
6. [Backup Monitoring](#backup-monitoring)

---

## Backup Strategy

### Backup Goals
- **RPO (Recovery Point Objective)**: < 1 hour of data loss
- **RTO (Recovery Time Objective)**: < 4 hours to restore
- **Retention**: 30 days of daily backups
- **Frequency**: Hourly incremental, daily full backup

### Backup Types

**Full Backup** (Daily at 2 AM)
- Entire PostgreSQL database
- Duration: ~15 minutes
- Size: ~500 MB - 1 GB
- Retention: 30 days (rotate)

**Incremental Backup** (Every hour)
- Transaction logs only
- Duration: < 1 minute
- Size: 10-50 MB per hour
- Retention: 7 days

**Code Backup** (Daily with releases)
- Git repository bundle
- Duration: < 5 minutes
- Size: 50-100 MB
- Retention: All releases (indefinite)

**Application Data Backup** (Daily)
- File uploads, configurations
- Duration: < 5 minutes
- Size: 100-500 MB
- Retention: 30 days

---

## Backup Procedures

### Procedure 1: Manual Full Database Backup

**When to Use**: 
- Before major deployments
- Before database migrations
- After significant data changes
- On request

**Procedure**:

```bash
#!/bin/bash
# Full Database Backup Script

BACKUP_DIR="/backups/irms/full-$(date +%Y-%m-%d_%H%M%S)"
DB_USER="irms_app"
DB_NAME="irms_production"
DB_HOST="127.0.0.1"
DB_PORT="6432"

echo "=========================================="
echo "Starting Full Database Backup"
echo "Time: $(date)"
echo "=========================================="

# Create backup directory
mkdir -p "$BACKUP_DIR"
echo "Backup directory: $BACKUP_DIR"

# Step 1: Create SQL dump
echo "1. Creating database dump..."
pg_dump \
  -h $DB_HOST \
  -p $DB_PORT \
  -U $DB_USER \
  -d $DB_NAME \
  --verbose \
  --format=custom \
  --compress=9 \
  --file="$BACKUP_DIR/database.dump"

if [ $? -eq 0 ]; then
    echo "   ✅ Database dump created"
else
    echo "   ❌ Dump failed!"
    exit 1
fi

# Step 2: Create text dump (readable)
echo "2. Creating text backup..."
pg_dump \
  -h $DB_HOST \
  -p $DB_PORT \
  -U $DB_USER \
  -d $DB_NAME \
  --format=plain \
  --file="$BACKUP_DIR/database.sql"
gzip "$BACKUP_DIR/database.sql"
echo "   ✅ Text dump created"

# Step 3: Create backup manifest
echo "3. Creating manifest..."
cat > "$BACKUP_DIR/MANIFEST.md" << 'EOF'
# Database Backup Manifest

**Backup Time**: $(date)
**Database**: irms_production
**Host**: $DB_HOST:$DB_PORT
**User**: $DB_USER

## Files
- database.dump (compressed PostgreSQL format)
- database.sql.gz (readable SQL text)
- MANIFEST.md (this file)

## Restoration
1. Gunzip the SQL file: `gunzip database.sql.gz`
2. Connect to database: `psql -U postgres -d irms_production`
3. Execute: `\i database.sql`

Or use restore command:
```
pg_restore -d irms_production -v database.dump
```

## Verification
```sql
SELECT COUNT(*) FROM mark_import_batches;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM candidates;
```
EOF

# Step 4: Verify backup
echo "4. Verifying backup..."
DUMP_SIZE=$(du -sh "$BACKUP_DIR/database.dump" | cut -f1)
SQL_SIZE=$(du -sh "$BACKUP_DIR/database.sql.gz" | cut -f1)
echo "   Database dump size: $DUMP_SIZE"
echo "   SQL dump size: $SQL_SIZE"

# Step 5: List contents
echo "5. Backup contents:"
ls -lh "$BACKUP_DIR/"

# Step 6: Calculate completion time
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
echo "=========================================="
echo "Backup Complete!"
echo "Duration: $DURATION seconds"
echo "Location: $BACKUP_DIR"
echo "=========================================="
```

**Success Criteria**:
- ✅ Both dump files created
- ✅ File sizes reasonable (> 100 MB)
- ✅ Manifest created
- ✅ Completed in < 20 minutes

---

### Procedure 2: Automated Backup Script

**Setup Cron Job** (Run daily at 2 AM):

```bash
# Edit crontab
crontab -e

# Add line:
0 2 * * * /usr/local/bin/irms-backup.sh

# Or for root cron:
sudo crontab -e
0 2 * * * /usr/local/bin/irms-backup.sh
```

**Backup Script** (`/usr/local/bin/irms-backup.sh`):

```bash
#!/bin/bash

# Configuration
BACKUP_BASE="/backups/irms"
RETENTION_DAYS=30
DB_USER="irms_app"
DB_NAME="irms_production"
BACKUP_LOG="/var/log/irms-backup.log"

# Create daily directory
BACKUP_DIR="$BACKUP_BASE/$(date +%Y-%m-%d)"
mkdir -p "$BACKUP_DIR"

# Log function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$BACKUP_LOG"
}

log "========== BACKUP START =========="

# Create full backup
pg_dump -h 127.0.0.1 -p 6432 -U $DB_USER -d $DB_NAME \
  --format=custom --compress=9 \
  -f "$BACKUP_DIR/database.dump"

if [ $? -eq 0 ]; then
    SIZE=$(du -h "$BACKUP_DIR/database.dump" | cut -f1)
    log "✅ Backup created: $SIZE"
else
    log "❌ Backup failed"
    # Send alert
    mail -s "IRMS Backup Failed" admin@example.com
    exit 1
fi

# Clean old backups (keep 30 days)
find "$BACKUP_BASE" -type d -mtime +$RETENTION_DAYS -exec rm -rf {} \;
log "✅ Cleaned old backups"

log "========== BACKUP COMPLETE =========="
```

**Set permissions**:
```bash
sudo chmod 755 /usr/local/bin/irms-backup.sh
sudo chown root:root /usr/local/bin/irms-backup.sh
```

---

### Procedure 3: Hourly Transaction Log Backup

**Setup WAL (Write-Ahead Log) Archiving**:

```bash
# Edit PostgreSQL config
sudo nano /etc/postgresql/14/main/postgresql.conf

# Set:
wal_level = replica
archive_mode = on
archive_command = 'test ! -f /backups/irms/wal_archive/%f && cp %p /backups/irms/wal_archive/%f'
archive_timeout = 3600

# Create WAL archive directory
sudo mkdir -p /backups/irms/wal_archive
sudo chown postgres:postgres /backups/irms/wal_archive
sudo chmod 700 /backups/irms/wal_archive

# Restart PostgreSQL
sudo systemctl restart postgresql

# Verify WAL archiving active
sudo -u postgres psql -c "SELECT pg_switch_wal();"
ls /backups/irms/wal_archive/
# Should show WAL files
```

---

## Recovery Procedures

### Procedure 1: Point-in-Time Recovery (PITR)

**Use Case**: Recover to specific time before data corruption

**Steps**:

```bash
# 1. Stop PostgreSQL
sudo systemctl stop postgresql

# 2. Create base backup directory
sudo mkdir -p /var/lib/postgresql/14/main_pitr

# 3. Restore from full backup
pg_restore -d irms_production \
  /backups/irms/full-2026-02-20_020000/database.dump

# 4. List available WAL files
ls /backups/irms/wal_archive/ | sort

# 5. Create recovery.conf for PITR
cat > /var/lib/postgresql/14/main/recovery.conf << 'EOF'
# Recovery configuration
restore_command = 'cp /backups/irms/wal_archive/%f %p'
recovery_target_time = '2026-02-20 14:30:00'
recovery_target_timeline = 'latest'
EOF

# 6. Start PostgreSQL
sudo systemctl start postgresql

# 7. Monitor recovery
tail -f /var/log/postgresql/postgresql-14-main.log

# When recovery complete, log will show:
# "database system is ready to accept connections"

# 8. Verify recovery
psql -U irms_app -d irms_production -c "SELECT COUNT(*) FROM mark_import_batches;"

# 9. Make recovery permanent (one-time)
sudo -u postgres psql -c "SELECT pg_wal_replay_resume();"
```

**Success Criteria**:
- ✅ PostgreSQL starts successfully
- ✅ Database accessible
- ✅ Data matches expected state at recovery time
- ✅ No WAL files remain

---

### Procedure 2: Full Database Restoration

**Use Case**: Complete database loss or corruption

**Steps**:

```bash
# 1. Stop application
sudo systemctl stop php-fpm
sudo systemctl stop nginx

# 2. Verify no connections to database
psql -h 127.0.0.1 -p 6432 -U postgres -d postgres \
  -c "SELECT datname, count(*) FROM pg_stat_activity 
      WHERE datname = 'irms_production' GROUP BY datname;"

# Should return 0 connections

# 3. Drop corrupted database
psql -h 127.0.0.1 -p 6432 -U postgres -d postgres \
  -c "DROP DATABASE IF EXISTS irms_production;"

# 4. Create new database
psql -h 127.0.0.1 -p 6432 -U postgres -d postgres \
  -c "CREATE DATABASE irms_production OWNER irms_app;"

# 5. Restore from backup
pg_restore -h 127.0.0.1 -p 6432 \
  -d irms_production \
  -U irms_app \
  --verbose \
  /backups/irms/full-2026-02-20_020000/database.dump

# Monitor progress - look for:
# "RESTORE TABLE..."
# "RESTORE INDEX..."
# Duration: ~10-20 minutes

# 6. Verify restoration
psql -U irms_app -d irms_production << 'SQL'
SELECT 'Batches' as table_name, COUNT(*) FROM mark_import_batches
UNION ALL
SELECT 'Users', COUNT(*) FROM users
UNION ALL
SELECT 'Candidates', COUNT(*) FROM candidates;
SQL

# 7. Restart application
sudo systemctl start php-fpm
sudo systemctl start nginx

# 8. Verify application works
curl https://irms.example.com/health
```

**Success Criteria**:
- ✅ Database restored
- ✅ All tables present
- ✅ Data verified
- ✅ Application accessible

---

### Procedure 3: Code Recovery

**Use Case**: Corrupted or problematic code deployment

**Steps**:

```bash
# 1. Stop application
sudo systemctl stop php-fpm

# 2. List available code backups
ls /backups/irms/code/
# Format: irms_code_YYYY-MM-DD_HHMMSS.bundle

# 3. Clone from backup
cd /var/www/irms_recovery
git clone /backups/irms/code/irms_code_2026-02-20_080000.bundle irms

# 4. Compare with current
diff -r /var/www/irms /var/www/irms_recovery/irms | head -20

# 5. If backup is good, replace current
sudo mv /var/www/irms /var/www/irms_failed
sudo mv /var/www/irms_recovery/irms /var/www/irms

# 6. Restart application
sudo systemctl start php-fpm

# 7. Verify
curl https://irms.example.com/health
```

**Success Criteria**:
- ✅ Previous version restored
- ✅ Application works
- ✅ Database data intact

---

## Disaster Scenarios

### Scenario 1: Database Server Down

**Detection**: 
- Application shows "database connection failed"
- PostgreSQL service not running
- PgBouncer cannot connect to PostgreSQL

**Recovery Steps**:

```bash
# 1. Check PostgreSQL status
sudo systemctl status postgresql

# 2. Check logs for errors
tail -50 /var/log/postgresql/postgresql-14-main.log

# 3. If corrupted, restore from backup
sudo systemctl stop postgresql

# (Follow Procedure 2: Full Database Restoration above)

# 4. Verify
sudo systemctl start postgresql
psql -U irms_app -d irms_production -c "SELECT 1;"

# Time to recover: 15-30 minutes
```

---

### Scenario 2: Storage Full (Disk at 100%)

**Detection**:
- Upload fails with "disk full" error
- PostgreSQL cannot write
- Backup fails

**Emergency Steps**:

```bash
# 1. Check disk usage
df -h /

# 2. Find large files
du -sh /* | sort -h | tail -10

# 3. Delete old backups (emergency)
rm -rf /backups/irms/2026-02-01
rm -rf /backups/irms/2026-02-02
# (Only delete old backups, not current ones)

# 4. Clear log files
> /var/log/nginx/error.log
> /var/log/postgresql/postgresql-14-main.log

# 5. Verify space freed
df -h /

# 6. Resume normal operations
# Backups will resume on next scheduled time

# Long-term: Increase disk size or implement automated cleanup
```

---

### Scenario 3: Data Corruption Detected

**Detection**:
- Validation errors in application
- Duplicate or missing records
- Inconsistent data reported by users

**Recovery Steps**:

```bash
# 1. Identify corruption time/extent
# Check audit logs for when issue occurred
grep -i "error\|corruption\|duplicate" storage/logs/laravel.log

# 2. Choose recovery option:
# Option A: Point-in-Time Recovery to before corruption
# (Follow Procedure 1 above)

# Option B: Manual fix (if corruption is small)
# Stop application
sudo systemctl stop php-fpm

# Connect to database
psql -U irms_app -d irms_production

# Fix specific records
DELETE FROM mark_import_batches WHERE id = 123;
UPDATE users SET status='active' WHERE id = 456;

# Verify fix
SELECT COUNT(*) FROM mark_import_batches;

# Restart
sudo systemctl start php-fpm

# 3. Notify users of data impact
# Document what was recovered/lost
```

---

### Scenario 4: Ransomware/Security Breach

**Detection**:
- Files encrypted or modified
- Unusual database activity
- Large data exfiltration detected

**Immediate Actions**:

```bash
# 1. ISOLATE the system
# Disconnect from network if necessary
# But keep backups accessible

# 2. Stop all services
sudo systemctl stop php-fpm
sudo systemctl stop nginx
sudo systemctl stop postgresql

# 3. Take snapshot of current state (for investigation)
cp -r /var/www/irms /forensics/irms_snapshot_$(date +%s)

# 4. Restore from clean backup (before breach)
# (Follow full recovery procedures above)

# 5. Verify integrity
# Check all restored files
# Scan for suspicious code
# Verify database structure

# 6. Implement security improvements
# Update password
# Patch vulnerability
# Restrict access

# 7. Resume operations when confident
```

---

## Testing & Verification

### Procedure 1: Monthly Backup Verification

**Schedule**: First Friday of each month at 3 PM

**Process**:

```bash
#!/bin/bash
# Backup Verification Script

echo "Starting Backup Verification..."
START=$(date +%s)

# 1. Check backup files exist
echo "1. Checking backup files..."
LATEST_BACKUP=$(ls -1td /backups/irms/full-* | head -1)
if [ -z "$LATEST_BACKUP" ]; then
    echo "   ❌ No backup found!"
    exit 1
fi
echo "   ✅ Latest backup: $LATEST_BACKUP"

# 2. Check file integrity
echo "2. Checking file integrity..."
if file "$LATEST_BACKUP/database.dump" | grep -q "PostgreSQL"; then
    echo "   ✅ Dump file valid"
else
    echo "   ❌ Dump file invalid"
    exit 1
fi

# 3. Test restore to staging
echo "3. Testing restore to staging database..."

# Drop staging if exists
psql -h 127.0.0.1 -p 6432 -U postgres -d postgres \
  -c "DROP DATABASE IF EXISTS irms_staging_test;"

# Create staging
psql -h 127.0.0.1 -p 6432 -U postgres -d postgres \
  -c "CREATE DATABASE irms_staging_test OWNER irms_app;"

# Restore backup
pg_restore -h 127.0.0.1 -p 6432 \
  -d irms_staging_test \
  -U irms_app \
  "$LATEST_BACKUP/database.dump"

if [ $? -eq 0 ]; then
    echo "   ✅ Restore successful"
else
    echo "   ❌ Restore failed"
    exit 1
fi

# 4. Verify data
echo "4. Verifying data..."
COUNT=$(psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_staging_test \
  -t -c "SELECT COUNT(*) FROM mark_import_batches;")
echo "   ✅ Batches count: $COUNT"

# 5. Cleanup
echo "5. Cleaning up test database..."
psql -h 127.0.0.1 -p 6432 -U postgres -d postgres \
  -c "DROP DATABASE irms_staging_test;"

END=$(date +%s)
DURATION=$((END - START))
echo "=========================================="
echo "Verification Complete!"
echo "Duration: $DURATION seconds"
echo "Status: ✅ BACKUP VALID"
echo "=========================================="
```

**Success Criteria**:
- ✅ Backup files exist and valid
- ✅ Restore completes successfully
- ✅ Data verified in restored database
- ✅ All counts reasonable

---

### Procedure 2: Quarterly Disaster Recovery Drill

**Schedule**: Every 3 months (on staging environment)

**Process**:

```bash
# 1. Announce drill
# Email team: "Disaster Recovery Drill - [Date]"

# 2. Select backup to restore
# Use production backup from 1 week ago

# 3. Create test environment
# Use separate VM or test server

# 4. Perform full recovery
# (Follow full recovery procedures)

# 5. Test application
# Verify all features work
# Test user workflows
# Verify data integrity

# 6. Document results
# What went well
# What took longer than expected
# Improvements needed

# 7. Brief team on findings
# Discuss improvements
# Update procedures if needed
```

**Success Criteria**:
- ✅ Recovery completed in < target time
- ✅ All features functional
- ✅ Data verified
- ✅ Issues documented and resolved

---

## Backup Monitoring

### Daily Backup Report

**Generated**: 3 AM (after backup completes)

**Checks**:
```bash
# 1. Backup completed
# 2. File size reasonable
# 3. Backup age < 24 hours
# 4. No errors in log
# 5. Free disk space adequate

# Report template:
echo "IRMS Backup Report - $(date +%Y-%m-%d)"
echo "========================================"
echo "Last Backup: $(ls -1 /backups/irms/full-* | tail -1 | xargs basename)"
echo "Backup Size: $(du -sh /backups/irms/full-$(date +%Y-%m-%d) | cut -f1)"
echo "Backup Status: ✅ Complete"
echo "Next Backup: $(date -d '+1 day' +%Y-%m-%d' at 02:00')"
echo "========================================"
```

**Alert if**:
- Backup file size < 100 MB (too small)
- Backup age > 25 hours (late)
- Backup fails (no file)
- Free disk space < 20% (full)

---

## Backup Retention Policy

### Retention Schedule

| Backup Type | Frequency | Retention |
|-------------|-----------|-----------|
| Full Backup | Daily at 2 AM | 30 days (keep last 30) |
| WAL Archive | Continuous | 7 days (auto-cleanup) |
| Code Backup | With releases | 90 days (keep 3 months) |
| Before Deploy | Manual | 30 days |

### Automatic Cleanup

```bash
# Add to daily backup script
# Clean backups older than 30 days
find /backups/irms/full-* -mtime +30 -delete

# Clean WAL files older than 7 days
find /backups/irms/wal_archive/ -mtime +7 -delete

# Clean code backups older than 90 days
find /backups/irms/code/ -mtime +90 -delete
```

---

## Recovery Time Estimates

| Scenario | Estimated Time | Recovery Method |
|----------|----------------|-----------------|
| Small record fix | < 5 min | Direct SQL update |
| Large data corruption | 10-20 min | PITR recovery |
| Complete DB loss | 15-30 min | Full restore from backup |
| Server total loss | 1-2 hours | Full server rebuild + restore |
| Ransomware attack | 2-4 hours | Full recovery + security audit |

---

## Key Contacts

**Database Administrator**
- Phone: [Number]
- Email: dba@example.com
- Available: 24/7 for emergencies

**Backup Administrator**
- Phone: [Number]
- Email: backups@example.com
- Available: Business hours + on-call

**Operations Lead**
- Phone: [Number]
- Email: ops@example.com
- Available: 24/7 for critical issues

---

## Backup Checklist

- [ ] Full backup created daily at 2 AM
- [ ] WAL archiving active
- [ ] Code backups created with releases
- [ ] Retention policy enforced
- [ ] Verification run monthly
- [ ] Disaster drill run quarterly
- [ ] All procedures documented
- [ ] Team trained on recovery
- [ ] Alerts configured
- [ ] Recovery time targets met

---

**Status**: ✅ BACKUP SYSTEM OPERATIONAL

**Next Review**: 2026-03-13

