# Phase 5: Execution Guide - Week 9 Deployment

**Date Started**: 2026-02-13  
**Target Completion**: 2026-02-20 (Week 9)  
**Status**: 🔜 Ready to Execute  

---

## Quick Reference

### Critical Dates
- **Monday (Day 1)**: Infrastructure Setup
- **Tuesday (Day 2)**: Database Migration & Testing
- **Wednesday (Day 3)**: User Training
- **Thursday (Day 4)**: Staging Testing
- **Friday (Day 5)**: Production Go-Live
- **Saturday-Sunday (Days 6-7)**: Production Monitoring

### Key Files
- Deployment Plan: `PHASE_5_DEPLOYMENT_PLAN.md`
- Go-Live Checklist: `PHASE_5_GO_LIVE_CHECKLIST.md` (create)
- Runbook: `PHASE_5_RUNBOOK.md` (create)

---

## Day 1: Infrastructure Setup (Monday)

### Morning (8 AM - 12 PM)

#### Task 1.1: PostgreSQL Installation
```bash
# Login as admin
sudo su -

# Update package manager
apt-get update
apt-get install -y postgresql postgresql-contrib

# Verify installation
systemctl status postgresql

# Start and enable
systemctl start postgresql
systemctl enable postgresql
```

**Responsible**: Database Administrator  
**Duration**: 30 minutes  
**Success Criteria**: PostgreSQL running, version >= 14

#### Task 1.2: Create Production Database
```bash
# Connect to PostgreSQL
sudo -u postgres psql

# Create database
CREATE DATABASE irms_production;
CREATE DATABASE irms_staging;

# Create user
CREATE USER irms_app WITH PASSWORD '<SECURE_PASSWORD>';

# Grant privileges
ALTER USER irms_app CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE irms_production TO irms_app;
GRANT ALL PRIVILEGES ON DATABASE irms_staging TO irms_app;

# List databases
\l

# Exit
\q
```

**Responsible**: Database Administrator  
**Duration**: 15 minutes  
**Success Criteria**: Databases and user created, permissions granted

#### Task 1.3: PostgreSQL Configuration
```bash
# Edit PostgreSQL config
sudo nano /etc/postgresql/14/main/postgresql.conf

# Key settings for production:
max_connections = 200
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB
random_page_cost = 1.1
effective_io_concurrency = 200
```

**Responsible**: Database Administrator  
**Duration**: 20 minutes  
**Success Criteria**: Configuration saved and PostgreSQL restarted

### Afternoon (1 PM - 5 PM)

#### Task 1.4: PgBouncer Installation (Connection Pooling)
```bash
# Install PgBouncer
sudo apt-get install -y pgbouncer

# Backup original config
sudo cp /etc/pgbouncer/pgbouncer.ini /etc/pgbouncer/pgbouncer.ini.backup

# Edit config
sudo nano /etc/pgbouncer/pgbouncer.ini
```

**Configuration** (see PHASE_5_DEPLOYMENT_PLAN.md for full config)

```bash
# Start and enable
sudo systemctl start pgbouncer
sudo systemctl enable pgbouncer

# Verify
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production
```

**Responsible**: Database Administrator  
**Duration**: 30 minutes  
**Success Criteria**: PgBouncer running, connection pooling working

#### Task 1.5: Redis Installation (Caching)
```bash
# Install Redis
sudo apt-get install -y redis-server

# Start and enable
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Verify
redis-cli ping
# Expected output: PONG
```

**Responsible**: System Administrator  
**Duration**: 15 minutes  
**Success Criteria**: Redis running, responds to PING

#### Task 1.6: Redis Configuration
```bash
# Backup original
sudo cp /etc/redis/redis.conf /etc/redis/redis.conf.backup

# Edit config
sudo nano /etc/redis/redis.conf

# Key settings:
port 6379
bind 127.0.0.1
maxmemory 512mb
maxmemory-policy allkeys-lru
requirepass <STRONG_PASSWORD>

# Restart
sudo systemctl restart redis-server

# Test with password
redis-cli -a <STRONG_PASSWORD> ping
```

**Responsible**: System Administrator  
**Duration**: 20 minutes  
**Success Criteria**: Redis configured with password, responding

#### Task 1.7: Monitoring Setup (If applicable)
```bash
# Install monitoring agent (e.g., New Relic, DataDog)
# Follow vendor documentation
# Configure alerts
# Test alerting
```

**Responsible**: Operations Engineer  
**Duration**: 1 hour  
**Success Criteria**: Monitoring agent running, metrics flowing

### End of Day 1 Checklist
- [ ] PostgreSQL installed and running
- [ ] Production/staging databases created
- [ ] Database user created with correct permissions
- [ ] PgBouncer installed and running
- [ ] Redis installed and running
- [ ] Monitoring configured
- [ ] All services enabled for auto-start
- [ ] Backup of configurations created

---

## Day 2: Database Migration & Testing (Tuesday)

### Morning (8 AM - 12 PM)

#### Task 2.1: Create Database Backup
```bash
# Backup current database
BACKUP_DIR="/backups/irms/$(date +%Y-%m-%d)"
mkdir -p $BACKUP_DIR

# If SQLite:
cp database/database.sqlite $BACKUP_DIR/database.sqlite

# If MySQL:
mysqldump -u root -p irms > $BACKUP_DIR/irms_mysql_backup.sql

# Verify backup
ls -lh $BACKUP_DIR/
```

**Responsible**: Database Administrator  
**Duration**: 15 minutes  
**Success Criteria**: Backup created and verified

#### Task 2.2: Update Application Configuration
```bash
# Create .env for production
cp .env.example .env.production

# Edit production environment
nano .env.production

# Key settings:
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=6432  # PgBouncer port
DB_DATABASE=irms_production
DB_USERNAME=irms_app
DB_PASSWORD=<PASSWORD>
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=<PASSWORD>
```

**Responsible**: Technical Lead  
**Duration**: 15 minutes  
**Success Criteria**: .env.production configured correctly

#### Task 2.3: Run Database Migrations
```bash
# Use staging database first
export APP_ENV=staging
cp .env.production .env.staging

# Run migrations on staging
php artisan migrate:fresh --database=staging --seed

# Verify migration
php artisan tinker
>>> DB::connection('staging')->table('migrations')->count()
>>> DB::connection('staging')->table('users')->count()
```

**Responsible**: Technical Lead  
**Duration**: 30 minutes  
**Success Criteria**: Staging migrations successful, data verified

#### Task 2.4: Add Production Indexes
```bash
# Create index migration
php artisan make:migration create_production_indexes

# Edit migration with indexes from PHASE_5_DEPLOYMENT_PLAN.md
nano database/migrations/YYYY_MM_DD_HHMMSS_create_production_indexes.php

# Run migration
php artisan migrate --database=production
```

**Responsible**: Technical Lead  
**Duration**: 20 minutes  
**Success Criteria**: All indexes created, verified with EXPLAIN queries

### Afternoon (1 PM - 5 PM)

#### Task 2.5: Smoke Testing (Staging)
```bash
# Start staging environment
php artisan serve --env=staging

# Test 1: Application loads
curl -I http://localhost:8000/health
# Expected: 200 OK

# Test 2: Database connection
php artisan tinker
>>> DB::connection('staging')->select('SELECT 1');
// Expected: Array with result

# Test 3: Cache connection
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
// Expected: 'value'

# Test 4: Login
# Navigate to http://localhost:8000/login
# Login with test account
# Expected: Dashboard loads
```

**Responsible**: QA Lead  
**Duration**: 30 minutes  
**Success Criteria**: All smoke tests passing on staging

#### Task 2.6: Functional Testing (Staging)
```bash
# Test complete workflows
# 1. Teacher upload
#    - Login as teacher
#    - Upload CSV
#    - Verify validation
#    - Submit to HOD

# 2. HOD moderation
#    - Login as HOD
#    - View pending batches
#    - Approve/reject batch
#    - Verify status change

# 3. Admin submission
#    - Login as admin
#    - View approved batches
#    - Submit to NECTA
#    - Verify final status

# 4. PDF generation
#    - Generate scoresheet PDF
#    - Verify PDF quality
#    - Verify generation time < 30s

# 5. CSV export
#    - Export 5K records
#    - Export 25K records
#    - Export 50K records
#    - Verify export time < 60s
```

**Responsible**: QA Lead  
**Duration**: 1 hour  
**Success Criteria**: All workflows functioning on staging

#### Task 2.7: Performance Testing (Staging)
```bash
# Run load tests on staging
php artisan test tests/Performance/ExtendedLoadTesting.php

# Measure:
# - Page load times (target: < 3s)
# - PDF generation (target: < 30s)
# - CSV export (target: < 60s)
# - Concurrent users (target: 50+)
```

**Responsible**: Technical Lead  
**Duration**: 45 minutes  
**Success Criteria**: All performance metrics met on staging

#### Task 2.8: Prepare Production Migration Script
```bash
# Create final migration script
cat > deploy_production.sh << 'EOF'
#!/bin/bash

# Exit on error
set -e

echo "=== IRMS Production Deployment ==="
echo "Time: $(date)"

# 1. Pull latest code
echo "1. Pulling latest code..."
git pull origin main

# 2. Install dependencies
echo "2. Installing dependencies..."
composer install --optimize-autoloader --no-dev

# 3. Backup database
echo "3. Backing up database..."
pg_dump irms_production > backups/irms_production_$(date +%Y%m%d_%H%M%S).sql

# 4. Run migrations
echo "4. Running migrations..."
php artisan migrate:refresh --force

# 5. Add indexes
echo "5. Adding production indexes..."
php artisan migrate --force --path=database/migrations/

# 6. Cache configuration
echo "6. Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Clear cache
echo "7. Clearing cache..."
php artisan cache:clear

# 8. Restart services
echo "8. Restarting services..."
sudo systemctl restart php-fpm
sudo systemctl restart pgbouncer
sudo systemctl restart redis-server

echo "=== Deployment Complete ==="
echo "Verify: curl http://localhost/health"
EOF

chmod +x deploy_production.sh
```

**Responsible**: Technical Lead  
**Duration**: 15 minutes  
**Success Criteria**: Deployment script created and tested

### End of Day 2 Checklist
- [ ] Database backup created and verified
- [ ] Production configuration created
- [ ] Staging migrations successful
- [ ] Production indexes created
- [ ] Smoke tests passing on staging
- [ ] Functional tests passing on staging
- [ ] Performance tests passing on staging
- [ ] Deployment script created and tested
- [ ] Rollback procedure documented

---

## Day 3: User Training (Wednesday)

### Morning (8 AM - 12 PM)

#### Task 3.1: Teacher Training Session 1
```
Duration: 90 minutes
Attendees: 20-30 teachers (first batch)
Agenda:
1. System overview (10 min)
2. Login and navigation (10 min)
3. Context selection (15 min)
4. Download and fill CSV (15 min)
5. Upload and validate (15 min)
6. Submit to HOD (10 min)
7. Q&A (15 min)

Materials:
- PHASE_4_5_USER_GUIDE_TEACHERS.md
- Sample CSV file
- Practice server access
- Printed quick reference

Success Criteria:
- All teachers understand workflow
- All can perform each step
- No critical questions unanswered
```

**Responsible**: Training Lead  
**Duration**: 90 minutes

### Afternoon (1 PM - 5 PM)

#### Task 3.2: HOD Training Session 1
```
Duration: 90 minutes
Attendees: 10-15 HODs
Agenda:
1. System overview (10 min)
2. Moderation workflow (15 min)
3. Quality standards (15 min)
4. Decision making (20 min)
5. Scenario practice (20 min)
6. Q&A (10 min)

Materials:
- PHASE_4_5_USER_GUIDE_HODS.md
- Sample batches for review
- Decision flowchart
- Scenario case studies

Success Criteria:
- All HODs understand moderation
- All can make approval decisions
- Decision framework clear
```

**Responsible**: Training Lead  
**Duration**: 90 minutes

#### Task 3.3: Admin/Support Training
```
Duration: 60 minutes
Attendees: 5-10 admins and support staff
Agenda:
1. System architecture (15 min)
2. Admin panel walkthrough (15 min)
3. Troubleshooting guide (20 min)
4. Escalation procedures (10 min)

Materials:
- Admin documentation
- Troubleshooting guide
- Escalation procedures
- Support contact list

Success Criteria:
- Admins understand all functions
- Support team ready to help users
- Escalation paths clear
```

**Responsible**: Training Lead  
**Duration**: 60 minutes

### End of Day 3 Checklist
- [ ] All teachers trained (batch 1)
- [ ] All HODs trained
- [ ] Admin/support trained
- [ ] Training feedback collected
- [ ] Outstanding questions resolved
- [ ] Trainer availability confirmed for production

---

## Day 4: Staging Testing (Thursday)

### Full Day Testing

#### Task 4.1: Complete Workflow Testing
```
1. Teacher workflow
   - 5 teachers create batches
   - 5 batches uploaded
   - All pass validation
   - All submitted to HOD

2. HOD workflow
   - 5 HODs review batches
   - 3 approved
   - 1 rejected (test resubmission)
   - 1 requested changes

3. Admin workflow
   - Admin views approved batches
   - Admin submits to NECTA
   - Verify final status

4. Verify audit trails
   - All transitions logged
   - Timestamps correct
   - User context preserved
```

**Responsible**: QA Lead  
**Duration**: 4 hours  
**Success Criteria**: All workflows complete without errors

#### Task 4.2: Edge Case Testing
```
1. Concurrent uploads
   - 10 teachers upload simultaneously
   - All process correctly

2. Large file handling
   - Upload 50K records
   - Upload 100K records
   - Verify no timeouts

3. Error scenarios
   - Invalid CSV format
   - Missing data
   - Duplicate records
   - Verify error messages clear

4. Rollback testing
   - Stop deployment
   - Verify rollback procedure
   - Confirm old version still works
```

**Responsible**: QA Lead  
**Duration**: 2 hours  
**Success Criteria**: All edge cases handled gracefully

#### Task 4.3: Performance Validation
```
1. Load testing
   - 50 concurrent users
   - Verify no degradation
   - Monitor database load

2. PDF generation
   - Generate 100 PDFs
   - Verify < 30 seconds
   - Monitor memory

3. CSV export
   - Export 50K records
   - Verify < 60 seconds
   - Monitor memory
```

**Responsible**: Performance Engineer  
**Duration**: 1 hour  
**Success Criteria**: Performance targets met

#### Task 4.4: Security Validation
```
1. Authentication
   - Unauthenticated access blocked
   - All sessions secure

2. Authorization
   - Teachers see only their data
   - HODs see department data
   - Admins see all data

3. Data protection
   - HTTPS working
   - No SQL injection vulnerabilities
   - No XSS vulnerabilities
```

**Responsible**: Security Lead  
**Duration**: 1 hour  
**Success Criteria**: All security checks passing

### End of Day 4 Checklist
- [ ] Complete workflow testing passed
- [ ] Edge cases handled
- [ ] Performance validated
- [ ] Security validated
- [ ] No critical issues found
- [ ] Go-live approval signed off

---

## Day 5: Production Go-Live (Friday)

### Pre-Go-Live (6 AM - 8 AM)

#### Task 5.1: Final Verification
```bash
# 1. Verify all services running
sudo systemctl status postgresql
sudo systemctl status pgbouncer
sudo systemctl status redis-server
sudo systemctl status nginx

# 2. Check disk space
df -h /
# Expected: > 20% free

# 3. Check memory
free -h
# Expected: > 2GB free

# 4. Check backups
ls -lh /backups/irms/
# Expected: Recent backups exist

# 5. Verify deployment script
cat deploy_production.sh
# Expected: Script ready to run
```

**Responsible**: System Administrator  
**Duration**: 30 minutes  
**Success Criteria**: All systems ready

#### Task 5.2: Final Backup
```bash
# Create final backup before deployment
BACKUP_DIR="/backups/irms/$(date +%Y-%m-%d_T%H:%M:%S)"
mkdir -p $BACKUP_DIR

# Backup database
pg_dump irms_production > $BACKUP_DIR/irms_production.sql
gzip $BACKUP_DIR/irms_production.sql

# Backup code
git bundle create $BACKUP_DIR/irms_code.bundle --all

# Verify backups
ls -lh $BACKUP_DIR/
```

**Responsible**: Database Administrator  
**Duration**: 15 minutes  
**Success Criteria**: Complete backups created

### Go-Live Execution (8 AM - 2 PM)

#### Task 5.3: Deployment
```bash
# Run deployment script
./deploy_production.sh

# Monitor deployment output
# Expected: Each step completes without error
```

**Responsible**: Technical Lead  
**Duration**: 30 minutes  
**Success Criteria**: Deployment script completes successfully

#### Task 5.4: Post-Deployment Verification
```bash
# 1. Verify application is running
curl -I https://irms.example.com/health
# Expected: 200 OK

# 2. Check application logs
tail -f storage/logs/laravel.log
# Expected: No ERROR lines

# 3. Verify database connection
php artisan tinker
>>> DB::select('SELECT 1')
// Expected: Array with result

# 4. Verify cache
>>> Cache::put('health_check', 'ok', 60)
>>> Cache::get('health_check')
// Expected: 'ok'

# 5. Test login
# Navigate to https://irms.example.com/login
# Login with test account
# Expected: Dashboard loads
```

**Responsible**: Technical Lead  
**Duration**: 15 minutes  
**Success Criteria**: All verifications passing

#### Task 5.5: Enable User Access
```
1. Announcement
   - Send email to all users
   - Subject: "IRMS System is LIVE"
   - Include: Access URL, support contacts

2. Monitor first access
   - Watch for errors
   - Monitor database connections
   - Monitor cache hit rates

3. Support team standby
   - Monitor support queue
   - Answer user questions
   - Escalate technical issues
```

**Responsible**: Project Manager  
**Duration**: 15 minutes  
**Success Criteria**: Users can access system

### Post-Go-Live Monitoring (2 PM - 6 PM)

#### Task 5.6: Production Monitoring
```bash
# Monitor continuously
# Check every 15 minutes:

# 1. Error logs
tail storage/logs/laravel.log | grep ERROR

# 2. Database health
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production
SELECT count(*) FROM mark_import_batches;

# 3. Cache health
redis-cli -a <PASSWORD>
> INFO stats
> KEYS *

# 4. System resources
top
free -h
df -h

# 5. Response times
curl -w "Time: %{time_total}\n" https://irms.example.com/health
```

**Responsible**: Operations Engineer  
**Duration**: 4 hours  
**Success Criteria**: No critical errors, stable performance

### End of Day 5 Checklist
- [ ] Code deployed successfully
- [ ] All systems verified
- [ ] Users have access
- [ ] No critical errors
- [ ] Monitoring active
- [ ] Support team ready

---

## Days 6-7: Production Monitoring (Saturday-Sunday)

### 24/7 Monitoring Schedule
```
Time slots: 8-hour rotations
Engineer 1: 6 AM - 2 PM
Engineer 2: 2 PM - 10 PM
Engineer 3: 10 PM - 6 AM

Duties:
- Monitor logs
- Respond to alerts
- Help users
- Resolve issues
- Document incidents
```

### Monitoring Tasks
```
Every 30 minutes:
- Check error logs
- Verify database health
- Check cache status
- Verify page load times

Every 2 hours:
- Check disk space
- Check memory usage
- Review slow queries
- Check backup status

Daily (8 AM):
- Review all incidents
- Update status dashboard
- Communicate with team
- Plan any optimizations
```

### Issue Response Procedure
```
Level 1 (User unable to login):
1. Check if user exists
2. Reset password if needed
3. Verify user role
4. Test login with test account

Level 2 (Batch upload failing):
1. Check error message
2. Review batch data
3. Check database connection
4. Verify file format
5. Escalate if database issue

Level 3 (Database performance):
1. Check slow query log
2. Review active queries
3. Check connection count
4. Review index usage
5. Consider caching or optimization

Level 4 (Critical outage):
1. Check all services
2. Restart services if needed
3. Check recent deployments
4. Consider rollback
5. Notify management
```

### End of Days 6-7 Checklist
- [ ] 24/7 monitoring completed
- [ ] No critical issues remain
- [ ] All user issues resolved
- [ ] Performance stable
- [ ] Backups successful
- [ ] Incident log updated

---

## Phase 5 Sign-Off

### Deployment Approval
- [ ] **Project Manager**: Go-live approved
- [ ] **Technical Lead**: Deployment successful
- [ ] **Operations**: Infrastructure stable
- [ ] **QA**: Testing complete
- [ ] **Security**: Security verified

### Success Declaration
- [ ] Code deployed to production
- [ ] All systems operational
- [ ] Users successfully using system
- [ ] Performance metrics met
- [ ] Support team handling issues
- [ ] Monitoring active and alerting

---

## Troubleshooting Quick Reference

### Issue: Application Not Starting
```bash
# Check logs
tail storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/irms

# Check database connection
php artisan tinker
>>> DB::select('SELECT 1')

# Restart services
sudo systemctl restart php-fpm nginx
```

### Issue: Database Connection Failing
```bash
# Test PgBouncer connection
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production

# Check PostgreSQL directly
psql -h 127.0.0.1 -p 5432 -U irms_app -d irms_production

# Restart services
sudo systemctl restart postgresql pgbouncer
```

### Issue: Cache Not Working
```bash
# Test Redis connection
redis-cli -a <PASSWORD> ping

# Clear cache
php artisan cache:clear

# Check Redis
redis-cli -a <PASSWORD>
> DBSIZE
> KEYS *

# Restart Redis
sudo systemctl restart redis-server
```

### Issue: High Database Load
```bash
# Check slow queries
SELECT query, calls, mean_time FROM pg_stat_statements
ORDER BY mean_time DESC LIMIT 10;

# Check active queries
SELECT pid, query FROM pg_stat_activity WHERE state = 'active';

# Check connections
SELECT count(*) FROM pg_stat_activity;
```

---

**Next Phase**: Post-deployment optimization and long-term support

**Status**: ✅ Ready for Phase 5 Execution
