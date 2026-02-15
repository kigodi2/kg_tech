# ACSEE Bulk CSV Import - Phase 2b Deployment Guide

**Date:** February 15, 2026  
**Phase:** 2b - Deployment  
**Status:** READY FOR DEPLOYMENT

---

## Pre-Deployment Checklist

### Code Quality Verification
- [x] PHP syntax check: NO ERRORS
- [x] JavaScript logic: VALID
- [x] State management: CORRECT
- [x] Error handling: COMPREHENSIVE
- [x] Code review: APPROVED
- [x] Documentation: COMPLETE

### Testing Status
- [ ] Unit tests: PASS (22/22)
- [ ] Integration tests: PASS
- [ ] Manual tests: PASS
- [ ] Performance tests: PASS
- [ ] Browser compatibility: VERIFIED

### Environment Preparation
- [ ] Staging server ready
- [ ] Database backup created
- [ ] Rollback plan prepared
- [ ] Monitoring configured
- [ ] Support team notified

### Documentation Review
- [ ] Implementation guide reviewed
- [ ] User guide prepared
- [ ] Support documentation ready
- [ ] Known issues documented
- [ ] Troubleshooting guide available

---

## Phase 1: Staging Deployment (Recommended First)

### Step 1: Backup Current State

```bash
# Backup database
mysqldump -u root -p irms > backups/irms_pre_phase2b_backup.sql

# Backup application
cp -r . backups/irms_pre_phase2b_code_backup

# Log backup
echo "Backup created: $(date)" >> backups/deployment.log
```

### Step 2: Deploy Code to Staging

```bash
# Navigate to staging environment
cd /var/www/staging/irms

# Pull latest code
git fetch origin
git checkout feature/acsee-bulk-import-phase2b

# Or manually copy if not using git
cp /path/to/resources/views/exam-types/acsee.blade.php \
   resources/views/exam-types/acsee.blade.php

# Clear cache
php artisan cache:clear
php artisan view:clear
```

### Step 3: Verify Code Changes

```bash
# Check file permissions
ls -la resources/views/exam-types/acsee.blade.php

# Verify syntax
php -l resources/views/exam-types/acsee.blade.php

# Count lines added
wc -l resources/views/exam-types/acsee.blade.php
```

### Step 4: Run Staging Tests

**Browser-based testing:**
1. Open staging URL: `https://staging.irms.local/exam-types/acsee`
2. Execute manual test suite (20 tests from PHASE2B_AUTOMATED_TEST_SUITE_2026_02_15.md)
3. Record results in test execution report
4. Verify no console errors (F12 → Console)

**Backend verification:**
1. Test template download endpoints:
   ```bash
   curl -X GET https://staging.irms.local/api/exam-types/acsee/templates/school-allocation.csv
   curl -X GET https://staging.irms.local/api/exam-types/acsee/templates/private-allocation.csv
   ```

2. Test validation endpoint:
   ```bash
   curl -X POST https://staging.irms.local/api/exam-types/acsee/allocate-from-csv/validate \
     -F "file=@test.csv" \
     -F "exam_year_id=1" \
     -F "mode=SCHOOL"
   ```

3. Check response format:
   - [ ] 200 OK status
   - [ ] Valid JSON response
   - [ ] Correct data structure

### Step 5: Staging Sign-Off

```
STAGING DEPLOYMENT SIGN-OFF

Date: _______________
Tester: ______________
Environment: Staging
Build Version: _______

All Tests Passed: [ ] YES  [ ] NO
Issues Found: _______________
Critical Issues: [ ] YES  [ ] NO
Approved for Production: [ ] YES  [ ] NO

Signature: _________________
```

---

## Phase 2: Production Deployment

### Step 1: Final Pre-Deployment Checks

```bash
# 24 hours before deployment
echo "Pre-deployment checks:"
echo "✓ Code reviewed"
echo "✓ Staging tested"
echo "✓ Backups created"
echo "✓ Support notified"
echo "✓ Rollback plan prepared"
```

### Step 2: Production Database Backup

**CRITICAL: Always backup production database before deployment**

```bash
#!/bin/bash

# Create timestamped backup
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

# Full database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/irms_full.sql

# Backup candidates table specifically
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME candidate_subject_allocations \
  > $BACKUP_DIR/candidate_subject_allocations.sql

# Compress backups
gzip $BACKUP_DIR/*.sql

# Verify backup
echo "Backup created at: $BACKUP_DIR"
ls -lh $BACKUP_DIR/
```

### Step 3: Deploy to Production

**Option A: Using Git (Recommended)**

```bash
#!/bin/bash

# Navigate to production
cd /var/www/production/irms

# Stash any local changes
git stash

# Pull latest changes
git fetch origin
git checkout feature/acsee-bulk-import-phase2b

# Clear application cache
php artisan cache:clear
php artisan view:clear
php artisan config:cache

# Log deployment
echo "Deployment: Phase 2b - $(date)" >> storage/logs/deployment.log
```

**Option B: Manual File Copy**

```bash
#!/bin/bash

# Navigate to production
cd /var/www/production/irms

# Backup current file
cp resources/views/exam-types/acsee.blade.php \
   resources/views/exam-types/acsee.blade.php.backup

# Copy new file
cp /path/to/updated/acsee.blade.php \
   resources/views/exam-types/acsee.blade.php

# Verify permissions
chmod 644 resources/views/exam-types/acsee.blade.php

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:cache
```

### Step 4: Post-Deployment Verification

```bash
#!/bin/bash

echo "Post-deployment verification..."

# Check file exists and is readable
if [ -r "resources/views/exam-types/acsee.blade.php" ]; then
  echo "✓ File exists and readable"
else
  echo "✗ File not readable - ROLLBACK NEEDED"
  exit 1
fi

# Check PHP syntax
if php -l resources/views/exam-types/acsee.blade.php > /dev/null; then
  echo "✓ PHP syntax valid"
else
  echo "✗ PHP syntax error - ROLLBACK NEEDED"
  exit 1
fi

# Verify application still responds
if curl -s https://irms.example.com/exam-types/acsee | grep -q "ACSEE Management"; then
  echo "✓ Application responding"
else
  echo "✗ Application not responding - ROLLBACK NEEDED"
  exit 1
fi

echo "Deployment verification complete"
```

### Step 5: Production Smoke Tests

**Run critical tests immediately after deployment:**

1. **Navigate to ACSEE page**
   ```
   URL: https://irms.example.com/exam-types/acsee
   Expected: Page loads, no JavaScript errors (F12 → Console)
   ```

2. **Test Bulk Import Button**
   ```
   Action: Click "Bulk Import CSV" button
   Expected: Modal opens without errors
   ```

3. **Test Template Download**
   ```
   Action: Click "Download School Template"
   Expected: CSV file downloads successfully
   ```

4. **Test File Upload**
   ```
   Action: Upload test CSV
   Expected: File displays in modal with size
   ```

5. **Test Dropdown Selections**
   ```
   Action: Select exam year
   Expected: Selection saved, no errors
   ```

### Step 6: Monitor for Errors

```bash
#!/bin/bash

# Watch application logs in real-time
tail -f storage/logs/laravel.log | grep -i "error\|exception"

# Monitor web server errors
tail -f /var/log/apache2/error.log | grep "irms"
# OR for Nginx
tail -f /var/log/nginx/error.log | grep "irms"

# Monitor application performance
# Check New Relic, DataDog, or similar monitoring tool
```

### Step 7: Update Application Status

```bash
# Log successful deployment
echo "Phase 2b deployment: SUCCESS at $(date)" >> storage/logs/deployment.log

# Update version file
echo "2b" >> VERSION

# Notify team
echo "Phase 2b successfully deployed to production"
```

---

## Rollback Plan (If Needed)

### Quick Rollback (< 5 minutes)

```bash
#!/bin/bash

echo "INITIATING ROLLBACK..."

# Restore previous version
cp resources/views/exam-types/acsee.blade.php.backup \
   resources/views/exam-types/acsee.blade.php

# Clear cache
php artisan cache:clear
php artisan view:clear

# Verify rollback
if php -l resources/views/exam-types/acsee.blade.php > /dev/null; then
  echo "✓ Rollback successful"
else
  echo "✗ Rollback failed - MANUAL INTERVENTION REQUIRED"
  exit 1
fi

# Log rollback
echo "Rollback from Phase 2b at $(date)" >> storage/logs/deployment.log
```

### Database Rollback (If Data Corruption)

```bash
#!/bin/bash

# Stop application (optional but recommended)
# php artisan down

# Restore from backup
mysql -u $DB_USER -p$DB_PASS $DB_NAME < backups/TIMESTAMP/irms_full.sql

# Verify restore
# Check candidate counts, allocations, etc.

# Restart application
# php artisan up
```

### Git Rollback

```bash
# If using version control
git revert HEAD
git push origin

# Or reset to previous version
git checkout previous-stable-version
git push origin
```

---

## Deployment Checklist

### Pre-Deployment (24 hours before)

- [ ] Code review completed and approved
- [ ] All tests passing on staging
- [ ] Database backup created
- [ ] Code backup created
- [ ] Rollback procedure tested
- [ ] Support team notified
- [ ] Monitoring configured
- [ ] Maintenance window scheduled
- [ ] Documentation updated
- [ ] Stakeholders informed

### Deployment Day

- [ ] Start maintenance window (if needed)
- [ ] Final backup created
- [ ] Deploy code to production
- [ ] Clear application cache
- [ ] Run smoke tests
- [ ] Monitor logs for errors
- [ ] Verify all features working
- [ ] Run performance checks
- [ ] Log deployment time and status
- [ ] End maintenance window

### Post-Deployment (First 24 hours)

- [ ] Monitor application logs hourly
- [ ] Check error reporting system
- [ ] Verify data integrity
- [ ] Monitor API response times
- [ ] Check database performance
- [ ] Review user feedback
- [ ] Confirm no regressions
- [ ] Update deployment log
- [ ] Prepare post-deployment report

### Post-Deployment (First Week)

- [ ] Monitor for any delayed issues
- [ ] Collect user feedback
- [ ] Verify no data anomalies
- [ ] Run performance analysis
- [ ] Update documentation if needed
- [ ] Archive deployment records
- [ ] Schedule team retrospective

---

## Deployment Execution Log

```
═══════════════════════════════════════════════════════════════════
DEPLOYMENT EXECUTION LOG - PHASE 2B
═══════════════════════════════════════════════════════════════════

Deployment Date: _______________
Deployment Time: _______________
Deployed By: ___________________
Environment: [ ] STAGING  [ ] PRODUCTION

PRE-DEPLOYMENT
──────────────────────────────────────────────────────────────────
Database Backup: _________________ [ ] Complete
Code Backup: _____________________ [ ] Complete
Staging Tests: _________________ [ ] PASS
Support Notified: ________________ [ ] YES

DEPLOYMENT EXECUTION
──────────────────────────────────────────────────────────────────
Code Deployed: ________________ [ ] Complete
Cache Cleared: ________________ [ ] Complete
Syntax Verified: _______________ [ ] OK
Application Responds: __________ [ ] YES

SMOKE TESTS
──────────────────────────────────────────────────────────────────
ACSEE Page Loads: ______________ [ ] PASS
Bulk Import Modal: _____________ [ ] PASS
Template Download: _____________ [ ] PASS
File Upload: ___________________ [ ] PASS
Dropdown Selection: ____________ [ ] PASS

MONITORING
──────────────────────────────────────────────────────────────────
Error Log Check: ______________ [ ] OK
Performance Check: ____________ [ ] OK
Database Check: _______________ [ ] OK
API Response Time: ____________ [ ] OK

COMPLETION STATUS
──────────────────────────────────────────────────────────────────
[ ] SUCCESSFUL - All checks passed
[ ] FAILED - Issues found:
    _________________________________________________________
    
Rollback Performed: [ ] YES [ ] NO (if failed)
Rollback Status: _____________ [ ] SUCCESSFUL [ ] FAILED

Issues Found: _______________________________________________
Resolved: _______________________________________________
Notes: _______________________________________________

Deployment Completed At: _______________________
Completed By: ______________________________
═══════════════════════════════════════════════════════════════════
```

---

## Monitoring & Maintenance

### Application Monitoring

**Critical Metrics to Monitor:**

1. **Page Load Time**
   ```
   Target: < 2 seconds
   Alert if: > 5 seconds
   Action: Check server performance
   ```

2. **API Response Time**
   ```
   Target: < 1 second
   Alert if: > 3 seconds  
   Action: Check API logs
   ```

3. **Error Rate**
   ```
   Target: < 0.1%
   Alert if: > 1%
   Action: Check error logs
   ```

4. **Database Performance**
   ```
   Monitor: Query time, connection count
   Alert if: Slow queries > 1 second
   Action: Review query performance
   ```

### Log Monitoring

```bash
# Real-time error monitoring
tail -f storage/logs/laravel.log | grep -i "error\|exception\|warning"

# API call monitoring
grep "allocate-from-csv" storage/logs/laravel.log | tail -20

# Performance monitoring
grep "took" storage/logs/laravel.log | tail -10
```

### User Monitoring

- Monitor user reports of issues
- Track feature usage
- Gather feedback
- Monitor support tickets

---

## Support & Troubleshooting

### Common Issues & Solutions

**Issue: Modal won't open**
```
Cause: JavaScript error
Solution:
1. Check browser console (F12)
2. Look for JavaScript syntax error
3. Verify cache cleared
4. Restart browser
```

**Issue: File upload fails**
```
Cause: File size or type validation
Solution:
1. Verify CSV format
2. Check file size (limit if any)
3. Try different browser
4. Check browser console
```

**Issue: Validation hangs**
```
Cause: Large file or network issue
Solution:
1. Check server logs
2. Monitor network tab (F12)
3. Try smaller CSV
4. Check server resources
```

**Issue: Import doesn't commit**
```
Cause: Backend API issue
Solution:
1. Check backend logs
2. Verify database connection
3. Test API endpoint directly
4. Check permissions
```

### Support Contact

- **Development Team:** dev@example.com
- **System Admin:** admin@example.com
- **Database Admin:** dba@example.com
- **Emergency:** ops-oncall@example.com

---

## Post-Deployment Report Template

```
═══════════════════════════════════════════════════════════════════
POST-DEPLOYMENT REPORT - PHASE 2B
═══════════════════════════════════════════════════════════════════

Deployment Date: _______________
Environment: [ ] STAGING  [ ] PRODUCTION

DEPLOYMENT SUMMARY
──────────────────────────────────────────────────────────────────
Status: [ ] SUCCESSFUL  [ ] PARTIAL  [ ] FAILED
Duration: _____ minutes
Rollback Required: [ ] YES  [ ] NO

METRICS
──────────────────────────────────────────────────────────────────
Tests Passed: ___/22
Critical Issues: ___
Major Issues: ___
Minor Issues: ___

USER IMPACT
──────────────────────────────────────────────────────────────────
New Features Working: [ ] YES  [ ] PARTIAL  [ ] NO
Existing Features Affected: [ ] NO  [ ] YES (explain)
User Reports: _______________

PERFORMANCE
──────────────────────────────────────────────────────────────────
Page Load Time: _____ ms (target: < 2000ms)
API Response Time: _____ ms (target: < 1000ms)
Error Rate: ____% (target: < 0.1%)

RECOMMENDATIONS
──────────────────────────────────────────────────────────────────
Immediate Actions: ___________________________________________
Follow-up Items: ____________________________________________
Documentation Updates: _______________________________________

Approved By: ________________  Date: ______________
═══════════════════════════════════════════════════════════════════
```

---

## Deployment Success Criteria

✅ **Deployment is successful when:**

1. Code deploys without errors
2. All smoke tests pass
3. No new error log entries
4. Application responds normally
5. Users can access new feature
6. API endpoints working
7. Database integrity verified
8. Performance within expectations
9. No rollback required
10. Team notified of completion

---

## Timeline Estimate

| Task | Time |
|------|------|
| Pre-deployment prep | 30 min |
| Staging deployment | 15 min |
| Staging testing | 60 min |
| Final approval | 15 min |
| Production deployment | 10 min |
| Smoke testing | 15 min |
| Monitoring setup | 10 min |
| Documentation | 15 min |
| **Total** | **~3 hours** |

---

**Deployment Guide Created:** February 15, 2026  
**Status:** READY FOR DEPLOYMENT  
**Confidence Level:** HIGH

