# Phase 5: Day 5 - Production Go-Live (Execution Log)

**Date**: 2026-02-20 (Friday)  
**Status**: 🚀 CRITICAL DEPLOYMENT DAY  
**Duration**: 8 hours (6 AM - 2 PM go-live window + 4 hours post-monitoring)  
**Team**: Technical Lead, Database Admin, Operations, Support

---

## Pre-Go-Live (6:00 AM - 8:00 AM)

### Task 5.0.1: Final System Verification

**Estimated Time**: 30 minutes  
**Responsible**: Technical Lead + Database Admin

```bash
echo "=========================================="
echo "FINAL PRE-DEPLOYMENT VERIFICATION"
echo "Time: $(date)"
echo "=========================================="

# 1. Verify PostgreSQL is running
echo "1. Checking PostgreSQL..."
sudo systemctl status postgresql | grep Active
# Expected: active (running)

# 2. Verify PgBouncer is running
echo "2. Checking PgBouncer..."
sudo systemctl status pgbouncer | grep Active
# Expected: active (running)

# 3. Verify Redis is running
echo "3. Checking Redis..."
sudo systemctl status redis-server | grep Active
# Expected: active (running)

# 4. Check disk space
echo "4. Checking disk space..."
df -h / | tail -1
# Expected: > 20% free

# 5. Check memory
echo "5. Checking memory..."
free -h | grep Mem
# Expected: > 2GB free

# 6. Check backup
echo "6. Verifying backup..."
ls -lh /backups/irms/*/
# Expected: Recent backup exists

# 7. Verify staging environment
echo "7. Testing staging database..."
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_staging -c "SELECT COUNT(*) FROM mark_import_batches;"
# Expected: Shows a number

echo "✅ All pre-flight checks passed"
```

**✅ Success Criteria**:
- All services running
- Disk space available
- Memory sufficient
- Backup confirmed
- Staging database accessible

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.0.2: Final Backup

**Estimated Time**: 15 minutes  
**Responsible**: Database Admin

```bash
# Create final backup before deployment
FINAL_BACKUP_DIR="/backups/irms/pre-golive-$(date +%Y-%m-%d_%H%M%S)"
mkdir -p $FINAL_BACKUP_DIR

echo "Creating final backup to: $FINAL_BACKUP_DIR"

# Backup any existing production data
if pg_isready -h 127.0.0.1 -p 5432 2>/dev/null; then
    echo "Backing up production database..."
    pg_dump -h 127.0.0.1 -U irms_app -d irms_production > $FINAL_BACKUP_DIR/irms_production_final.sql
    gzip $FINAL_BACKUP_DIR/irms_production_final.sql
fi

# Backup code
echo "Backing up code..."
git bundle create $FINAL_BACKUP_DIR/irms_code_final.bundle --all

# Create manifest
cat > $FINAL_BACKUP_DIR/MANIFEST.txt << 'EOF'
IRMS Pre-Go-Live Backup
Date: $(date)
Location: $FINAL_BACKUP_DIR

Contents:
- irms_production_final.sql.gz (database)
- irms_code_final.bundle (git repository)

To restore:
1. psql < irms_production_final.sql
2. git clone irms_code_final.bundle
EOF

# Verify backup
du -sh $FINAL_BACKUP_DIR/
ls -lh $FINAL_BACKUP_DIR/

echo "✅ Final backup completed"
```

**✅ Success Criteria**:
- Database backup created
- Code backup created
- Manifest created
- Verified complete

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.0.3: Announce Deployment

**Estimated Time**: 15 minutes  
**Responsible**: Project Manager

```bash
echo "Sending deployment announcement..."

# Email template:
cat << 'EMAIL'
Subject: IRMS System Going Live Today (2026-02-20)

Dear Users,

Today we are deploying the IRMS Mark Entry Lifecycle system to production.

DEPLOYMENT WINDOW: 8:00 AM - 9:00 AM (Estimated)
EXPECTED AVAILABILITY: System online by 10:00 AM

DURING THIS TIME:
- System will be unavailable (approximately 1 hour)
- Please save any work before 8:00 AM
- Avoid starting new uploads

AFTER GO-LIVE:
- New features available
- Same login credentials
- Training materials available (see attached guides)

SUPPORT:
- Technical Issues: support@irms.example.com
- Urgent: [Phone number]
- During deployment: [On-call number]

Thank you for your patience!

Development Team
EMAIL

echo "✅ Announcement sent"
```

**✅ Success Criteria**:
- Users notified
- Deployment window communicated
- Support contacts provided
- Questions answered

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## Go-Live Execution (8:00 AM - 10:00 AM)

### Task 5.1: Enable Maintenance Mode

**Estimated Time**: 5 minutes  
**Responsible**: Technical Lead

```bash
# This prevents new requests during deployment
echo "Enabling maintenance mode..."

# Create maintenance flag
mkdir -p storage/framework
touch storage/framework/down

# Optionally display maintenance page
# Update your web server to show maintenance message
# (Nginx/Apache configuration)

# Verify maintenance mode active
[ -f storage/framework/down ] && echo "✅ Maintenance mode enabled" || echo "❌ Failed"
```

**✅ Success Criteria**:
- Maintenance mode flag created
- Users see maintenance message
- No new requests processed

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.2: Stop Application Services

**Estimated Time**: 5 minutes  
**Responsible**: Operations Engineer

```bash
echo "Stopping application services..."

# Stop web server
sudo systemctl stop nginx
sleep 2

# Stop PHP-FPM
sudo systemctl stop php-fpm
sleep 2

# Verify stopped
echo "Checking services..."
sudo systemctl status nginx | grep "inactive\|stopped"
sudo systemctl status php-fpm | grep "inactive\|stopped"

echo "✅ Services stopped"
```

**✅ Success Criteria**:
- Nginx stopped
- PHP-FPM stopped
- No requests being processed

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.3: Deploy Code Changes

**Estimated Time**: 15 minutes  
**Responsible**: Technical Lead

```bash
cd /var/www/irms

echo "Deploying code changes..."

# 1. Fetch latest code
git fetch origin main
git reset --hard origin/main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev

# 3. Setup production environment
cp .env.production .env

# 4. Generate app key (if not exists)
php artisan key:generate --force

# 5. Verify no errors
php artisan config:show | head -5

echo "✅ Code deployed"
```

**✅ Success Criteria**:
- Latest code pulled
- Dependencies installed
- Configuration in place
- No errors

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.4: Run Database Migrations

**Estimated Time**: 10 minutes  
**Responsible**: Database Admin

```bash
echo "Running database migrations..."

# Run migrations
php artisan migrate --force

# Verify migrations
php artisan tinker

# In tinker:
# >>> DB::table('migrations')->count()
# Should show number of migrations

# Check tables created
# >>> DB::select('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?', ['public'])

echo "✅ Migrations complete"
```

**✅ Success Criteria**:
- All migrations run
- Tables created
- Data intact
- No errors

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.5: Add Production Indexes

**Estimated Time**: 5 minutes  
**Responsible**: Database Admin

```bash
echo "Creating production indexes..."

php artisan migrate --force --path=database/migrations

# Verify indexes
psql -U irms_app -d irms_production -c "\di" | grep idx_

echo "✅ Indexes created"
```

**✅ Success Criteria**:
- 12+ indexes created
- Database optimized
- No errors

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.6: Cache Configuration

**Estimated Time**: 10 minutes  
**Responsible**: Technical Lead

```bash
echo "Configuring caching..."

# Clear old cache
php artisan cache:clear

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Verify cache
php artisan config:show | grep CACHE
# Expected: CACHE_DRIVER=redis

echo "✅ Caching configured"
```

**✅ Success Criteria**:
- Cache cleared
- Configuration cached
- Routes cached
- Views cached
- All successful

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.7: Start Services

**Estimated Time**: 10 minutes  
**Responsible**: Operations Engineer

```bash
echo "Starting services..."

# Start PHP-FPM
sudo systemctl start php-fpm
sleep 3

# Verify PHP-FPM
sudo systemctl status php-fpm | grep active

# Start Nginx
sudo systemctl start nginx
sleep 3

# Verify Nginx
sudo systemctl status nginx | grep active

# Verify all services
echo "Service verification:"
sudo systemctl status postgresql | grep active
sudo systemctl status pgbouncer | grep active
sudo systemctl status redis-server | grep active

echo "✅ Services started"
```

**✅ Success Criteria**:
- PHP-FPM started
- Nginx started
- All services running
- Verified responsive

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.8: Disable Maintenance Mode

**Estimated Time**: 5 minutes  
**Responsible**: Technical Lead

```bash
echo "Disabling maintenance mode..."

# Remove maintenance flag
rm storage/framework/down

# Verify
[ ! -f storage/framework/down ] && echo "✅ Maintenance mode disabled" || echo "❌ Failed"

echo "System now available to users"
```

**✅ Success Criteria**:
- Maintenance mode disabled
- Users can access system
- No errors displayed

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## Post-Go-Live Verification (10:00 AM - 12:00 PM)

### Task 5.9: Health Check

**Estimated Time**: 10 minutes  
**Responsible**: Technical Lead

```bash
echo "=========================================="
echo "POST-DEPLOYMENT HEALTH CHECK"
echo "Time: $(date)"
echo "=========================================="

# 1. Application health
echo "1. Application health check..."
curl -I https://irms.example.com/health
# Expected: 200 OK

# 2. Database connection
echo "2. Database connection..."
php artisan tinker
# >>> DB::select('SELECT 1')
# Expected: Array with result
# >>> exit

# 3. Cache connection
echo "3. Cache connection..."
php artisan tinker
# >>> Cache::put('health', 'ok', 60)
# >>> Cache::get('health')
# Expected: 'ok'
# >>> exit

# 4. Test login
echo "4. Login test..."
# Navigate to https://irms.example.com/login
# Try login with test account
# Expected: Redirected to dashboard

# 5. Check logs
echo "5. Checking logs..."
tail -20 storage/logs/laravel.log
# Expected: No ERROR lines

# 6. Monitor resources
echo "6. System resources..."
top -n 1 | head -10
free -h | grep Mem
df -h / | tail -1

echo "✅ Health check complete"
```

**✅ Success Criteria**:
- Application responds (200)
- Database connected
- Cache working
- Login successful
- No errors in logs
- System resources normal

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.10: Feature Verification

**Estimated Time**: 30 minutes  
**Responsible**: QA Lead + Support Team

```bash
echo "Verifying critical features..."

# Feature 1: Teacher Login
echo "1. Teacher login..."
# Login as teacher@example.com
# Expected: Dashboard loads

# Feature 2: CSV Download
echo "2. CSV template download..."
# Navigate to Mark Entry
# Click "Download CSV Template"
# Expected: File downloads

# Feature 3: CSV Upload
echo "3. CSV upload..."
# Upload sample CSV
# Expected: Validation runs

# Feature 4: PDF Generation
echo "4. PDF generation..."
# Click "Generate PDF"
# Expected: PDF downloads (< 30s)

# Feature 5: CSV Export
echo "5. CSV export..."
# Click "Export CSV"
# Expected: CSV downloads (< 60s)

echo "✅ Features verified"
```

**✅ Success Criteria**:
- All critical features work
- No errors observed
- Performance acceptable
- User experience good

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.11: Performance Baseline

**Estimated Time**: 10 minutes  
**Responsible**: Technical Lead

```bash
echo "Establishing production performance baseline..."

# Measure page load time
for i in {1..5}; do
    echo "Request $i:"
    curl -w "Time: %{time_total}s\n" -o /dev/null -s https://irms.example.com/health
done

# Expected: All < 3 seconds

# Check database query performance
php artisan tinker
# >>> $start = microtime(true);
# >>> DB::select('SELECT COUNT(*) FROM mark_import_batches');
# >>> echo "Time: " . (microtime(true) - $start);
# Expected: < 0.5 seconds

# Check cache performance
# >>> $start = microtime(true);
# >>> Cache::put('test', 'value', 60);
# >>> Cache::get('test');
# >>> echo "Time: " . (microtime(true) - $start);
# Expected: < 0.1 seconds

echo "✅ Performance baseline established"
```

**✅ Success Criteria**:
- Page load < 3 seconds
- Database queries < 1 second
- Cache responses < 0.1 second
- No performance issues

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.12: Error Log Review

**Estimated Time**: 10 minutes  
**Responsible**: Technical Lead

```bash
echo "Reviewing error logs..."

# Check application log
echo "Application logs:"
tail -50 storage/logs/laravel.log | grep -i error
# Expected: No errors (or only expected warnings)

# Check database log
echo "Database logs:"
sudo tail -20 /var/log/postgresql/postgresql-14-main.log
# Expected: No critical errors

# Check web server log
echo "Web server logs:"
sudo tail -20 /var/log/nginx/error.log
# Expected: No critical errors

echo "✅ Log review complete"
```

**✅ Success Criteria**:
- No critical errors
- Expected warnings only
- Clear logs indicate healthy system
- Any issues documented

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## User Communication & Support (12:00 PM - 2:00 PM)

### Task 5.13: Announce Go-Live Success

**Estimated Time**: 5 minutes  
**Responsible**: Project Manager

```bash
echo "Announcing go-live success..."

# Email template:
cat << 'EMAIL'
Subject: ✅ IRMS System Now Live!

Dear Users,

We're delighted to announce that the IRMS Mark Entry Lifecycle system 
is now LIVE and available for use!

CURRENT STATUS: ✅ All systems operational
PERFORMANCE: Excellent - All systems running smoothly
SUPPORT TEAM: Standing by for any questions

NEXT STEPS:
1. Login at: https://irms.example.com
2. Use your existing username/password
3. Refer to the user guide if needed (attached)
4. Contact support with any questions

KEY FEATURES NOW AVAILABLE:
- Simple CSV mark upload
- Automatic validation
- HOD moderation workflow
- PDF scoresheet generation
- CSV data export
- Complete audit trails

SUPPORT:
- Email: support@irms.example.com
- Phone: [Number]
- Live Chat: Available in system

Thank you for your patience during the transition!

Development Team
EMAIL

echo "✅ Announcement sent"
```

**✅ Success Criteria**:
- Users notified
- Support contacts provided
- Guides referenced
- Enthusiasm conveyed

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

### Task 5.14: Activate Support Team

**Estimated Time**: 5 minutes  
**Responsible**: Support Manager

```bash
echo "Activating support team..."

# Checklist for support team:
# - Monitor support email
# - Monitor support phone
# - Watch for user questions
# - Refer to troubleshooting guide
# - Escalate technical issues
# - Track issues for follow-up

echo "Support team activated and standing by"
```

**✅ Success Criteria**:
- Support team online
- Monitoring channels active
- Procedures in place
- Issues being tracked

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## Production Monitoring (2:00 PM - 6:00 PM & Ongoing)

### Task 5.15: Continuous Monitoring

**Estimated Time**: 4 hours (ongoing)  
**Responsible**: Operations Engineer + On-Call Engineer

```bash
# Monitor continuously with 30-minute intervals:

echo "=== Monitoring Cycle 1 (2:00 PM) ==="
# Check error logs
# Monitor system resources
# Check database connections
# Verify cache hit rate
# Check user load

echo "=== Monitoring Cycle 2 (2:30 PM) ==="
# Repeat checks
# Check for new errors
# Monitor performance metrics

echo "=== Monitoring Cycle 3 (3:00 PM) ==="
# Continue monitoring
# Track user feedback
# Watch for issues

echo "=== Monitoring Cycle 4 (3:30 PM) ==="
echo "=== Monitoring Cycle 5 (4:00 PM) ==="
echo "=== Monitoring Cycle 6 (4:30 PM) ==="
echo "=== Monitoring Cycle 7 (5:00 PM) ==="
echo "=== Monitoring Cycle 8 (5:30 PM) ==="

echo "✅ Continuous monitoring active"
```

**✅ Success Criteria**:
- System stable
- No critical errors
- Performance consistent
- Users happy
- No escalations

**⏱️ Time Tracking**: Start: _____ | End: _____ | Duration: _____

---

## End of Day 5 Checklist

### Go-Live Completion

- [ ] Code deployed
- [ ] Database migrated
- [ ] Indexes created
- [ ] Cache configured
- [ ] Services running
- [ ] Health checks passed
- [ ] Features verified
- [ ] Logs reviewed
- [ ] Users notified
- [ ] Support activated
- [ ] Monitoring active

### Critical Verifications

- [ ] Application accessible
- [ ] Login working
- [ ] Database connected
- [ ] Cache working
- [ ] No critical errors
- [ ] Performance acceptable
- [ ] All features functional

### Issues Found

```
Issue 1: _______________________
Severity: [ ] Critical [ ] Major [ ] Minor
Status: [ ] Open [ ] Fixed [ ] Monitoring
```

---

## Day 5 Summary

**Deployment Start**: 8:00 AM  
**Go-Live**: _____ AM  
**Duration**: _____ hours  
**Critical Issues**: _____  
**Support Tickets**: _____  
**User Feedback**: Positive / Mixed / Negative

**DEPLOYMENT STATUS**: ✅ SUCCESSFUL

---

## Sign-Off

**Technical Lead**: _________________ Date: _______ Time: _______

**Operations Lead**: _________________ Date: _______ Time: _______

**Project Manager**: _________________ Date: _______ Time: _______

---

**Next**: [Days 6-7: Production Monitoring](PHASE_5_DAYS6-7_MONITORING.md)
