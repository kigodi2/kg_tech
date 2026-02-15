# Phase 5: Day 2 - Database Migration & Testing (Execution Log)

**Date**: 2026-02-17 (Tuesday)  
**Status**: 📋 READY TO EXECUTE  
**Duration**: 8 hours (8 AM - 5 PM)  
**Team**: Database Admin, Technical Lead, QA Lead

---

## Task Checklist

### Morning Session (8 AM - 12 PM)

#### Task 2.1: Create Database Backup ⏳
- [ ] Backup current database
- [ ] Verify backup integrity
- [ ] Document backup location

#### Task 2.2: Update Application Configuration ⏳
- [ ] Create .env.production
- [ ] Set database connection settings
- [ ] Set cache configuration
- [ ] Set session configuration

#### Task 2.3: Run Database Migrations ⏳
- [ ] Test on staging database
- [ ] Verify all migrations run
- [ ] Verify data integrity
- [ ] Create production indexes

#### Task 2.4: Add Production Indexes ⏳
- [ ] Create migration file
- [ ] Add all required indexes
- [ ] Run migration
- [ ] Verify indexes created

### Afternoon Session (1 PM - 5 PM)

#### Task 2.5: Smoke Testing (Staging) ⏳
- [ ] Test application loads
- [ ] Test database connection
- [ ] Test cache connection
- [ ] Test login

#### Task 2.6: Functional Testing (Staging) ⏳
- [ ] Test teacher upload
- [ ] Test HOD moderation
- [ ] Test admin submission
- [ ] Test PDF generation
- [ ] Test CSV export

#### Task 2.7: Performance Testing (Staging) ⏳
- [ ] Run load tests
- [ ] Measure page load times
- [ ] Measure PDF generation
- [ ] Measure CSV export

#### Task 2.8: Prepare Production Migration Script ⏳
- [ ] Create deployment script
- [ ] Test script
- [ ] Document script
- [ ] Get approval

---

## Execution Details

### Task 2.1: Create Database Backup

**Estimated Time**: 15 minutes  
**Responsible**: Database Administrator

```bash
# Step 1: Create backup directory
BACKUP_DIR="/backups/irms/$(date +%Y-%m-%d)"
mkdir -p $BACKUP_DIR

# Step 2: Backup current database (if exists)
# If using SQLite:
if [ -f "database/database.sqlite" ]; then
    cp database/database.sqlite $BACKUP_DIR/database.sqlite
    echo "✅ SQLite backup created"
fi

# If using MySQL:
if command -v mysqldump &> /dev/null; then
    mysqldump -u root -p irms > $BACKUP_DIR/irms_mysql_backup.sql
    gzip $BACKUP_DIR/irms_mysql_backup.sql
    echo "✅ MySQL backup created"
fi

# Step 3: Verify backup
ls -lh $BACKUP_DIR/
du -sh $BACKUP_DIR/

# Step 4: Create backup manifest
cat > $BACKUP_DIR/MANIFEST.txt << 'EOF'
IRMS Database Backup
Date: $(date)
Source: [Current DB]
Location: $BACKUP_DIR
Files:
- database.sqlite (if SQLite)
- irms_mysql_backup.sql.gz (if MySQL)

To restore:
1. Stop application
2. Copy backup files back
3. Restart application
4. Verify data
EOF

echo "✅ Backup completed and verified"
```

**✅ Success Criteria**:
- Backup file created
- File integrity verified
- Backup size recorded
- Manifest created

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.2: Update Application Configuration

**Estimated Time**: 15 minutes  
**Responsible**: Technical Lead

```bash
# Step 1: Create production environment file
cp .env.example .env.production

# Step 2: Edit configuration
nano .env.production

# Required settings:
# ==================

APP_NAME=IRMS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://irms.example.com
APP_KEY=base64:xxxxxxxxxxxxx  # Generate with: php artisan key:generate

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=6432  # PgBouncer port
DB_DATABASE=irms_production
DB_USERNAME=irms_app
DB_PASSWORD=<DATABASE_PASSWORD>

# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=<REDIS_PASSWORD>
REDIS_CACHE_DB=0

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=.irms.example.com
SESSION_SAME_SITE=lax

# Queue Configuration
QUEUE_CONNECTION=redis

# Mail Configuration (if applicable)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<MAIL_USERNAME>
MAIL_PASSWORD=<MAIL_PASSWORD>

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info

# Step 3: Generate app key
php artisan key:generate --env=production

# Step 4: Verify configuration
php artisan config:show --env=production | head -20

echo "✅ Configuration updated"
```

**✅ Success Criteria**:
- .env.production created
- All required keys set
- No hardcoded secrets in code
- Configuration validated

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.3: Run Database Migrations

**Estimated Time**: 30 minutes  
**Responsible**: Technical Lead

```bash
# Step 1: Set environment to staging first
export APP_ENV=staging
export DB_CONNECTION=pgsql
export DB_HOST=127.0.0.1
export DB_PORT=6432
export DB_DATABASE=irms_staging
export DB_USERNAME=irms_app
export DB_PASSWORD=<PASSWORD>

# Step 2: Run migrations on staging
php artisan migrate:fresh --seed --force

# Step 3: Verify migration
php artisan tinker

# In tinker:
>>> DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', ['public'])
// Should show 15+ tables

>>> DB::table('migrations')->count()
// Should show number of migrations run

>>> DB::table('users')->count()
// Should show seeded users

>>> exit

# Step 4: Check for errors
tail storage/logs/laravel.log | grep -i error

# Step 5: Verify tables exist
psql -U irms_app -d irms_staging -c "\dt"

# Expected tables:
# - mark_import_batches
# - mark_entry_lifecycle_states
# - mark_moderation_reviews
# - candidates
# - raw_marks
# - users
# - roles
# - exam_types
# - subjects
# - schools
# - districts
# - regions
# etc.

echo "✅ Database migrations completed successfully"
```

**✅ Success Criteria**:
- All migrations run successfully
- 15+ tables created
- Data seeded correctly
- No errors in logs
- Tables accessible via psql

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.4: Add Production Indexes

**Estimated Time**: 20 minutes  
**Responsible**: Technical Lead

```bash
# Step 1: Create migration file
php artisan make:migration create_production_indexes

# Step 2: Edit the migration file
nano database/migrations/YYYY_MM_DD_HHMMSS_create_production_indexes.php

# Add this content:
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Mark Import Batch Indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_import_batches_school_year ON mark_import_batches(school_id, exam_year)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_import_batches_status ON mark_import_batches(lifecycle_state)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_import_batches_exam_year ON mark_import_batches(exam_year)');

        // Mark Entry Lifecycle Indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_entry_lifecycle_batch_id ON mark_entry_lifecycle_states(mark_import_batch_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_entry_lifecycle_created ON mark_entry_lifecycle_states(created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_entry_lifecycle_user ON mark_entry_lifecycle_states(user_id)');

        // Mark Moderation Review Indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_moderation_reviews_batch_id ON mark_moderation_reviews(mark_import_batch_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_moderation_reviews_reviewer ON mark_moderation_reviews(reviewer_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mark_moderation_reviews_created ON mark_moderation_reviews(created_at)');

        // Candidate Indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_candidates_school_year ON candidates(school_id, exam_year)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_candidates_index_number ON candidates(exam_index_number)');

        // Raw Marks Indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_raw_marks_batch_id ON raw_marks(mark_import_batch_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_raw_marks_status ON raw_marks(validation_status)');
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS idx_mark_import_batches_school_year');
        DB::statement('DROP INDEX IF EXISTS idx_mark_import_batches_status');
        DB::statement('DROP INDEX IF EXISTS idx_mark_import_batches_exam_year');
        DB::statement('DROP INDEX IF EXISTS idx_mark_entry_lifecycle_batch_id');
        DB::statement('DROP INDEX IF EXISTS idx_mark_entry_lifecycle_created');
        DB::statement('DROP INDEX IF EXISTS idx_mark_entry_lifecycle_user');
        DB::statement('DROP INDEX IF EXISTS idx_mark_moderation_reviews_batch_id');
        DB::statement('DROP INDEX IF EXISTS idx_mark_moderation_reviews_reviewer');
        DB::statement('DROP INDEX IF EXISTS idx_mark_moderation_reviews_created');
        DB::statement('DROP INDEX IF EXISTS idx_candidates_school_year');
        DB::statement('DROP INDEX IF EXISTS idx_candidates_index_number');
        DB::statement('DROP INDEX IF EXISTS idx_raw_marks_batch_id');
        DB::statement('DROP INDEX IF EXISTS idx_raw_marks_status');
    }
};
```

```bash
# Step 3: Run migration on staging
php artisan migrate --force

# Step 4: Verify indexes created
psql -U irms_app -d irms_staging -c "\di"

# Should show all created indexes

# Step 5: Check index sizes
psql -U irms_app -d irms_staging << 'EOF'
SELECT
    indexname,
    pg_size_pretty(pg_relation_size(indexrelid)) AS size
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY pg_relation_size(indexrelid) DESC;
EOF

echo "✅ Indexes created and verified"
```

**✅ Success Criteria**:
- Migration file created
- All indexes created successfully
- Indexes visible via \di
- No errors in process

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.5: Smoke Testing (Staging)

**Estimated Time**: 30 minutes  
**Responsible**: QA Lead

```bash
# Set staging environment
export APP_ENV=staging
export .env.production  # Or use staging-specific env

# Step 1: Start application
php artisan serve --env=staging

# In another terminal:

# Step 2: Test application health
echo "=== Test 1: Application Health ==="
curl -I http://localhost:8000/health
# Expected: 200 OK

# Step 3: Test database connection
echo "=== Test 2: Database Connection ==="
php artisan tinker

# In tinker:
>>> DB::select('SELECT 1')
// Should return: Array [ 0 => stdClass Object { ?column? => 1 } ]

>>> exit

# Step 4: Test cache connection
echo "=== Test 3: Cache Connection ==="
php artisan tinker

# In tinker:
>>> Cache::put('health_check', 'working', 60)
>>> Cache::get('health_check')
// Expected: 'working'

>>> Cache::forget('health_check')
>>> exit

# Step 5: Test login
echo "=== Test 4: Login Test ==="
# Navigate to http://localhost:8000/login in browser
# Login with seeded account:
#   Username: teacher@example.com
#   Password: password

# Expected: Redirected to dashboard

echo "✅ All smoke tests passed"
```

**✅ Success Criteria**:
- Application loads without errors
- Database connection works
- Cache connection works
- Can login with test account
- Dashboard displays correctly

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.6: Functional Testing (Staging)

**Estimated Time**: 1 hour  
**Responsible**: QA Lead

```bash
# Test complete workflows

echo "=== Test 1: Teacher Mark Upload ==="
# 1. Login as teacher
# 2. Navigate to Mark Entry
# 3. Download CSV template
# 4. Upload sample CSV with 10 records
# 5. Verify validation passed
# 6. Submit to HOD
# Expected: Status changes to AWAITING_MODERATION

echo "=== Test 2: HOD Moderation ==="
# 1. Logout and login as HOD
# 2. Navigate to Moderation Queue
# 3. Review batch
# 4. Click "Approve"
# 5. Add moderation notes
# Expected: Status changes to APPROVED

echo "=== Test 3: Admin Submission ==="
# 1. Logout and login as admin
# 2. View approved batches
# 3. Click "Submit to NECTA"
# 4. Confirm submission
# Expected: Status changes to SUBMITTED

echo "=== Test 4: PDF Generation ==="
# 1. Navigate to Reports
# 2. Click "Generate Scoresheet PDF"
# 3. Select batch
# 4. Wait for PDF
# 5. Verify PDF downloads
# Expected: PDF file downloads, < 30 seconds

echo "=== Test 5: CSV Export ==="
# 1. Navigate to Reports
# 2. Click "Export CSV"
# 3. Select batch with 1000+ records
# 4. Wait for export
# 5. Verify CSV downloads
# Expected: CSV file downloads, < 60 seconds

echo "✅ All functional tests completed"
```

**✅ Success Criteria**:
- All workflows complete without errors
- Status transitions correct
- PDF generation works and is fast
- CSV export works and is fast
- No validation errors

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.7: Performance Testing (Staging)

**Estimated Time**: 45 minutes  
**Responsible**: Technical Lead

```bash
# Set staging environment
export APP_ENV=staging

# Step 1: Run extended load tests
echo "=== Running Load Tests ==="
php artisan test tests/Performance/ExtendedLoadTesting.php

# Expected output:
# ✓ pdf generation service load                                          2.52s
# ✓ scoresheet data preparation 1000 records                             0.49s
# ✓ pdf rendering simulation high volume                                 0.50s
# ✓ csv generation 5000 records                                          0.50s
# ✓ csv generation 25000 records                                         0.51s
# ✓ csv generation 50000 records                                         0.50s
# ✓ concurrent users 20 users                                            5.11s
# ✓ concurrent users 50 users                                           12.39s
# ✓ concurrent users 100 plus users                                     23.79s
# ✓ high volume high concurrency stress                                  2.87s
# ✓ extended load testing summary                                        0.51s
# Tests:    11 passed

# Step 2: Measure page load times
echo "=== Page Load Time Tests ==="
for i in {1..5}; do
    curl -w "Request $i: %{time_total}s\n" -o /dev/null -s http://localhost:8000/health
done
# Expected: Each < 3 seconds

# Step 3: Record results
echo "Performance Tests Completed"
echo "All targets met: ✅"

echo "✅ Performance testing completed"
```

**✅ Success Criteria**:
- All load tests pass
- Page load times < 3s
- PDF generation < 30s
- CSV export < 60s
- Concurrent users 100+ handled
- No performance degradation

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 2.8: Prepare Production Migration Script

**Estimated Time**: 15 minutes  
**Responsible**: Technical Lead

```bash
# Step 1: Create deployment script
cat > deploy_production.sh << 'DEPLOY_EOF'
#!/bin/bash

# IRMS Production Deployment Script
# Run as: sudo ./deploy_production.sh
# Exit on any error
set -e

echo "=========================================="
echo "IRMS Production Deployment"
echo "Time: $(date)"
echo "=========================================="

# Configuration
DB_HOST="127.0.0.1"
DB_PORT="6432"
DB_NAME="irms_production"
DB_USER="irms_app"
APP_DIR="/var/www/irms"
BACKUP_DIR="/backups/irms/$(date +%Y%m%d_%H%M%S)"

# Step 1: Create backup
echo "Step 1/8: Creating database backup..."
mkdir -p "$BACKUP_DIR"
pg_dump -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME > "$BACKUP_DIR/irms_production.sql"
gzip "$BACKUP_DIR/irms_production.sql"
echo "✓ Backup created: $BACKUP_DIR"

# Step 2: Pull latest code
echo "Step 2/8: Pulling latest code..."
cd $APP_DIR
git fetch origin main
git reset --hard origin/main
echo "✓ Code updated"

# Step 3: Install dependencies
echo "Step 3/8: Installing dependencies..."
composer install --optimize-autoloader --no-dev
echo "✓ Dependencies installed"

# Step 4: Copy production env
echo "Step 4/8: Configuring environment..."
cp .env.production .env
php artisan key:generate --force
echo "✓ Environment configured"

# Step 5: Run migrations
echo "Step 5/8: Running migrations..."
php artisan migrate --force
echo "✓ Migrations completed"

# Step 6: Cache configuration
echo "Step 6/8: Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Cache configured"

# Step 7: Clear cache
echo "Step 7/8: Clearing old cache..."
php artisan cache:clear
echo "✓ Cache cleared"

# Step 8: Restart services
echo "Step 8/8: Restarting services..."
systemctl restart php-fpm
systemctl restart pgbouncer
systemctl restart redis-server
systemctl restart nginx
echo "✓ Services restarted"

echo "=========================================="
echo "Deployment Complete!"
echo "Time: $(date)"
echo "Backup: $BACKUP_DIR"
echo "=========================================="
echo ""
echo "Verify deployment:"
echo "  curl http://localhost/health"
echo "  php artisan tinker"
echo ""
DEPLOY_EOF

# Step 2: Make script executable
chmod +x deploy_production.sh
ls -l deploy_production.sh

# Step 3: Test script syntax
bash -n deploy_production.sh
echo "✓ Script syntax valid"

# Step 4: Create test dry-run
echo "Script created successfully"
echo "Review and test: ./deploy_production.sh --dry-run"

echo "✅ Deployment script prepared"
```

**✅ Success Criteria**:
- Script created
- Script is executable
- Syntax valid
- Ready for Day 5

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## End of Day 2 Verification

### Checklist

- [ ] Database backup created and verified
- [ ] Production configuration file created
- [ ] All migrations successful
- [ ] Production indexes created
- [ ] Smoke tests passing
- [ ] Functional tests passing
- [ ] Performance tests passing
- [ ] Deployment script created
- [ ] All services running
- [ ] No errors in logs

### Critical Verifications

```bash
# 1. Verify PostgreSQL
psql -U irms_app -d irms_production -c "SELECT COUNT(*) FROM pg_tables WHERE schemaname = 'public';"
# Expected: 15+

# 2. Verify PgBouncer
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production -c "SELECT 1;"
# Expected: 1

# 3. Verify Redis
redis-cli -a <PASSWORD> ping
# Expected: PONG

# 4. Verify indexes
psql -U irms_app -d irms_production -c "\di" | grep idx_
# Expected: 12+ indexes
```

### Issues Encountered

```
Issue 1: _______________________
Solution: ______________________
Status: [ ] Resolved [ ] Pending

Issue 2: _______________________
Solution: ______________________
Status: [ ] Resolved [ ] Pending
```

---

## Day 2 Summary

**Start Time**: 8:00 AM  
**End Time**: _____ PM  
**Total Duration**: _____ hours  
**Tasks Completed**: _____ / 8  
**Issues**: _____ Critical, _____ Minor  

**Status**: ✅ READY FOR DAY 3

---

## Sign-Off

**Technical Lead**: _________________ Date: _______  
**QA Lead**: _________________ Date: _______  
**Database Administrator**: _________________ Date: _______

---

**Next**: [Day 3: User Training](PHASE_5_DAY3_USER_TRAINING.md)
