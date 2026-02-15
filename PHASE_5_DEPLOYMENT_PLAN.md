# Phase 5: Production Deployment - Complete Plan

**Date**: 2026-02-13  
**Phase**: 5 (Final - Deployment)  
**Timeline**: Week 9  
**Status**: 🔜 Ready to Deploy  

---

## Executive Summary

Phase 5 is the final deployment phase where the Mark Entry Lifecycle system transitions from testing to production. This plan covers all aspects of deployment including infrastructure setup, data migration, testing, training, and go-live.

---

## Phase 5 Schedule (Week 9)

### Day 1: Infrastructure & Setup
- [ ] Database migration (SQLite → PostgreSQL)
- [ ] Connection pooling configuration (PgBouncer)
- [ ] Redis caching setup
- [ ] Monitoring & alerting configuration
- [ ] Load balancer configuration (if applicable)

### Day 2: Pre-Deployment Testing
- [ ] Database migration verification
- [ ] Smoke tests on all critical paths
- [ ] PDF generation testing
- [ ] CSV export testing
- [ ] Performance baseline on production hardware

### Day 3: User Training
- [ ] Teacher training sessions
- [ ] HOD moderation training
- [ ] Admin support training
- [ ] Dry-run with sample data

### Day 4: Staging Environment Testing
- [ ] Full workflow testing
- [ ] Concurrent user simulation
- [ ] Error scenario testing
- [ ] Rollback procedure testing

### Day 5: Production Go-Live
- [ ] Final production checklist
- [ ] Database backup
- [ ] Deploy code changes
- [ ] Run migrations
- [ ] Verify all systems
- [ ] Enable monitoring
- [ ] Launch to users

### Days 6-7: Production Monitoring
- [ ] 24/7 monitoring
- [ ] Error log review
- [ ] Performance verification
- [ ] User support
- [ ] Issue resolution

---

## Pre-Deployment Tasks

### 1. Infrastructure Setup

#### PostgreSQL Database
```bash
# Install PostgreSQL 14+
sudo apt-get install postgresql-14

# Create database
sudo -u postgres createdb irms_production
sudo -u postgres createdb irms_staging

# Create application user
sudo -u postgres createuser irms_app -W
sudo -u postgres psql -c "ALTER USER irms_app CREATEDB;"

# Configure pg_hba.conf for connection pooling
# Add line: local   irms_production   irms_app   md5
```

#### PgBouncer Connection Pooling
```ini
; /etc/pgbouncer/pgbouncer.ini

[databases]
irms_production = host=localhost port=5432 dbname=irms_production user=irms_app password=<password>

[pgbouncer]
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 50
min_pool_size = 10
reserve_pool_size = 5
reserve_pool_timeout = 3
max_db_connections = 100
max_user_connections = unlimited
listen_port = 6432
listen_addr = localhost
logfile = /var/log/pgbouncer/pgbouncer.log
pidfile = /var/run/pgbouncer/pgbouncer.pid
```

#### Redis Cache
```bash
# Install Redis
sudo apt-get install redis-server

# Configure for IRMS
# /etc/redis/redis.conf
port 6379
bind 127.0.0.1
maxmemory 512mb
maxmemory-policy allkeys-lru
```

### 2. Application Configuration

#### .env Production Settings
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated-key>

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=6432  # PgBouncer port
DB_DATABASE=irms_production
DB_USERNAME=irms_app
DB_PASSWORD=<secure-password>

CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=redis

QUEUE_CONNECTION=redis

LOG_CHANNEL=stack
LOG_LEVEL=info
```

### 3. Database Migration

#### Backup Current Data
```bash
# Backup SQLite
cp database/database.sqlite database/database.sqlite.backup-2026-02-13

# Or if using existing database
mysqldump -u root -p irms > irms_backup_2026_02_13.sql
```

#### Migrate to PostgreSQL
```bash
# 1. Export data from current database
php artisan db:seed --class=ProductionSeeder

# 2. Run migrations on PostgreSQL
php artisan migrate --force

# 3. Verify data integrity
php artisan tinker
>>> DB::table('mark_import_batches')->count()
>>> DB::table('candidates')->count()
```

### 4. Production Checklist

#### Code Deployment
- [ ] Code merged to main/production branch
- [ ] All tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Version tagged (v1.0.0)

#### Database
- [ ] PostgreSQL installed and configured
- [ ] PgBouncer configured
- [ ] Database created and initialized
- [ ] Migration scripts tested
- [ ] Data backup completed
- [ ] Indexes added (see below)
- [ ] Partitioning configured (if needed)

#### Caching
- [ ] Redis installed and running
- [ ] Redis security configured
- [ ] Cache keys configured
- [ ] TTL values set
- [ ] Memory limits configured

#### Monitoring
- [ ] Application metrics enabled
- [ ] Database monitoring enabled
- [ ] Redis monitoring enabled
- [ ] Log aggregation configured
- [ ] Alerting rules set
- [ ] Dashboard created

#### Security
- [ ] HTTPS/SSL configured
- [ ] Secrets stored securely
- [ ] Database credentials encrypted
- [ ] API keys rotated
- [ ] Firewall rules configured
- [ ] Rate limiting enabled

#### Performance
- [ ] Indexes created on key tables
- [ ] Caching layer tested
- [ ] Query optimization verified
- [ ] Load testing on production hardware
- [ ] CDN configured (if applicable)

---

## Database Indexes for Production

```sql
-- Mark Entry Lifecycle Indexes
CREATE INDEX idx_mark_import_batches_school_year 
ON mark_import_batches(school_id, exam_year);

CREATE INDEX idx_mark_import_batches_status 
ON mark_import_batches(lifecycle_state);

CREATE INDEX idx_mark_entry_lifecycle_batch_id 
ON mark_entry_lifecycle_states(mark_import_batch_id);

CREATE INDEX idx_mark_entry_lifecycle_created 
ON mark_entry_lifecycle_states(created_at);

CREATE INDEX idx_mark_moderation_reviews_batch_id 
ON mark_moderation_reviews(mark_import_batch_id);

CREATE INDEX idx_mark_moderation_reviews_reviewer 
ON mark_moderation_reviews(reviewer_id);

-- Candidate Indexes
CREATE INDEX idx_candidates_school_year 
ON candidates(school_id, exam_year);

CREATE INDEX idx_candidates_index_number 
ON candidates(exam_index_number);

-- Raw Marks Indexes
CREATE INDEX idx_raw_marks_batch_id 
ON raw_marks(mark_import_batch_id);

CREATE INDEX idx_raw_marks_status 
ON raw_marks(validation_status);
```

---

## Deployment Steps

### Step 1: Pre-Deployment Verification (Day 1)

```bash
# 1. Verify code is ready
git status
git log --oneline -5

# 2. Run all tests
php artisan test

# 3. Verify database backup
ls -lh database/database.sqlite.backup*

# 4. Check disk space
df -h

# 5. Verify services are running
sudo systemctl status postgresql
sudo systemctl status redis-server
```

### Step 2: Database Migration (Day 2)

```bash
# 1. Create PostgreSQL database
sudo -u postgres psql
CREATE DATABASE irms_production;
CREATE USER irms_app WITH PASSWORD '<password>';
ALTER USER irms_app CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE irms_production TO irms_app;
\q

# 2. Configure Laravel for production database
# Update .env with PostgreSQL settings

# 3. Run migrations
php artisan migrate:fresh --seed --force

# 4. Verify migration
php artisan tinker
>>> DB::table('migrations')->count()
>>> DB::table('users')->count()
>>> DB::table('mark_import_batches')->count()

# 5. Create indexes
php artisan tinker
>>> DB::statement('CREATE INDEX idx_...')
```

### Step 3: Cache & Session Configuration (Day 2)

```bash
# 1. Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# 2. Configure cache
php artisan cache:clear
php artisan config:cache

# 3. Test Redis connection
php artisan tinker
>>> Redis::connection()->ping()
"PONG"
```

### Step 4: Deployment (Day 5)

```bash
# 1. Final backup
mysqldump -u root -p irms > final_backup.sql
cp database/database.sqlite database/database.sqlite.final-backup

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --optimize-autoloader --no-dev

# 4. Migrate database
php artisan migrate --force

# 5. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Clear old cache
php artisan cache:clear

# 7. Restart application
sudo systemctl restart php-fpm
# or for Laravel Octane:
php artisan octane:restart

# 8. Verify all systems
curl -I https://irms.example.com/health
```

### Step 5: Production Monitoring (Days 5-7)

```bash
# 1. Check application logs
tail -f storage/logs/laravel.log

# 2. Monitor database
sudo -u postgres psql irms_production
SELECT * FROM pg_stat_statements ORDER BY total_time DESC LIMIT 10;

# 3. Check Redis
redis-cli
> INFO
> DBSIZE
> KEYS *

# 4. Monitor system
top
free -h
df -h
```

---

## Testing Checklist (Before Go-Live)

### Smoke Tests
- [ ] Application loads without errors
- [ ] Login works for all roles (teacher, HOD, admin)
- [ ] Dashboard displays correctly
- [ ] Database connection successful
- [ ] Cache working properly

### Functional Tests
- [ ] Teacher can upload marks
- [ ] CSV validation working
- [ ] HOD can view batches
- [ ] Moderation workflow complete
- [ ] Rejection and resubmission working
- [ ] PDF generation working
- [ ] CSV export working

### Performance Tests
- [ ] Page load times < 3 seconds
- [ ] PDF generation < 30 seconds
- [ ] CSV export < 60 seconds
- [ ] Database queries < 1 second
- [ ] Concurrent users 50+ handled

### Security Tests
- [ ] HTTPS working
- [ ] Authentication required
- [ ] Authorization enforced
- [ ] CSRF protection enabled
- [ ] SQL injection prevented
- [ ] XSS protection enabled

### Data Integrity Tests
- [ ] All data migrated correctly
- [ ] No duplicate records
- [ ] Foreign keys intact
- [ ] Audit trails preserved
- [ ] File uploads working

---

## Rollback Plan

### If Critical Issues Found (Before Day 5)
1. Stop code deployment
2. Use existing database
3. Continue testing
4. Fix issues in development
5. Re-deploy when ready

### If Issues Found After Go-Live (Day 5+)

**Immediate Rollback (< 30 minutes)**:
```bash
# 1. Stop application traffic
sudo systemctl stop nginx
# or update load balancer to redirect to old version

# 2. Rollback code
git revert HEAD
git reset --hard HEAD~1
composer install

# 3. Restart application
sudo systemctl start nginx

# 4. Notify users
Send notification: "Temporary maintenance - expected resolution in 5 minutes"

# 5. Investigate issue
Review logs, identify root cause

# 6. Fix and re-deploy
Apply fix, test, re-deploy
```

**Data Rollback**:
```bash
# If data corruption occurred
sudo -u postgres psql irms_production

-- Create backup of corrupted data
CREATE TABLE mark_import_batches_corrupted AS 
SELECT * FROM mark_import_batches;

-- Restore from backup
psql irms_production < irms_backup_2026_02_13.sql
```

---

## Monitoring & Alerting

### Application Metrics to Monitor
```
- Page load time (target: < 3s)
- API response time (target: < 500ms)
- Error rate (target: < 0.1%)
- User session count
- PDF generation time (target: < 30s)
- CSV export time (target: < 60s)
```

### Database Metrics
```
- Query response time (target: < 1s)
- Connection count (target: < 100)
- Cache hit rate (target: > 80%)
- Slow query log
- Replication lag
```

### Infrastructure Metrics
```
- CPU usage (alert: > 80%)
- Memory usage (alert: > 85%)
- Disk space (alert: < 10% free)
- Network latency
- Database connection queue
```

### Alert Rules
```
- Page load time > 5s: WARNING
- Error rate > 1%: CRITICAL
- Database down: CRITICAL
- Memory > 90%: WARNING
- Disk < 5% free: CRITICAL
- Response time > 2s: WARNING
```

---

## Post-Deployment Activities (Week 10+)

### Day 1-3: Monitoring
- [ ] Monitor logs for errors
- [ ] Check performance metrics
- [ ] Review user feedback
- [ ] Fix any identified issues

### Day 4-7: Optimization
- [ ] Profile slow queries
- [ ] Optimize database queries
- [ ] Adjust cache settings
- [ ] Tune application settings

### Week 2+: Documentation
- [ ] Document deployment process
- [ ] Update runbooks
- [ ] Create escalation procedures
- [ ] Train support team

---

## Go-Live Communication Plan

### Before Go-Live (Day 4)
- [ ] Announce deployment schedule
- [ ] Provide user guides (already done)
- [ ] Set expectations for downtime
- [ ] Provide support contact info

### During Go-Live (Day 5)
- [ ] Update status page every 30 minutes
- [ ] Provide real-time updates
- [ ] Answer user questions
- [ ] Resolve critical issues

### After Go-Live (Day 6+)
- [ ] Send success announcement
- [ ] Thank users for patience
- [ ] Provide feedback survey
- [ ] Share lessons learned

---

## Support Escalation

### Level 1: User Support
- Handles basic user questions
- Uses troubleshooting guides
- Escalates technical issues
- Response time: < 30 minutes

### Level 2: Technical Support
- Handles application errors
- Checks logs and metrics
- Escalates database issues
- Response time: < 15 minutes

### Level 3: Engineering Team
- Handles critical issues
- Performs code fixes
- Manages rollbacks
- Response time: < 5 minutes

### On-Call Rotation
```
Week 1:  [Engineer 1] - Primary, [Engineer 2] - Backup
Week 2:  [Engineer 2] - Primary, [Engineer 3] - Backup
Week 3:  [Engineer 3] - Primary, [Engineer 1] - Backup
Week 4:  [Engineer 1] - Primary, [Engineer 2] - Backup
```

---

## Success Criteria

### Deployment Success
- ✅ Code deployed to production
- ✅ All tests passing
- ✅ Zero critical errors
- ✅ Response time < 3 seconds
- ✅ All features working

### User Success
- ✅ Teachers can upload marks
- ✅ HODs can moderate batches
- ✅ Admins can submit to NECTA
- ✅ Users report satisfaction
- ✅ Support queue minimal

### System Success
- ✅ 99.9% uptime
- ✅ < 0.1% error rate
- ✅ Database healthy
- ✅ Cache hitting > 80%
- ✅ Monitoring alerting working

### Business Success
- ✅ Deadline met
- ✅ Budget on target
- ✅ Quality verified
- ✅ Team satisfied
- ✅ Users ready for next phase

---

## Appendix: Emergency Contacts

### On-Call Engineer
- Name: [To be assigned]
- Phone: [To be provided]
- Email: [To be provided]
- Slack: @oncall

### Database Administrator
- Name: [To be assigned]
- Phone: [To be provided]
- Email: [To be provided]

### System Administrator
- Name: [To be assigned]
- Phone: [To be provided]
- Email: [To be provided]

### Project Manager
- Name: [To be assigned]
- Phone: [To be provided]
- Email: [To be provided]

---

## Sign-Off

- [ ] **Project Manager**: Deployment plan approved
- [ ] **Technical Lead**: Technical readiness verified
- [ ] **QA Lead**: Testing complete
- [ ] **Operations**: Infrastructure ready
- [ ] **Security**: Security verified

---

**Next Step**: Execute Phase 5 Deployment Plan
