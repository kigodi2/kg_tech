# NECTA Phase 2 Deployment Runbook
**Date**: 2026-02-15  
**Feature**: NECTA-Aligned ACSEE Registration & Subject Allocation  
**Status**: Production Ready  

---

## Overview

This runbook provides step-by-step instructions for deploying NECTA Phase 2 code to production. Phase 2 implements SCHOOL and PRIVATE candidate registration with subject allocation validation.

**Key Points:**
- Database schema changes (Phase 1) are already applied
- Phase 2 includes: AcseeAllocationValidator service, API endpoint, UI modals, candidate registration updates
- Changes are code-only and non-destructive
- Rollback is simple: `git revert`

---

## Prerequisites

- Access to production server with SSH
- Git access with push permissions
- PHP 7.4+ with Laravel 8+ installed
- MySQL/MariaDB access
- Backup capability enabled
- Deployment approval obtained

---

## Deployment Methods

Choose one:

### Method A: Automated (Recommended)
```bash
./scripts/deploy-necta-phase2.sh production
```
Handles all steps automatically with safety checks.

### Method B: Manual Steps
Follow the steps in "Detailed Deployment Steps" below.

---

## Pre-Deployment: Verification & Checks

### 1. Verify Git Status
```bash
git status
```
Expected: Clean working tree or all changes committed.

**If uncommitted changes exist:**
```bash
git diff HEAD
```
Review changes. Either commit or stash before proceeding.

### 2. Backup Database
```bash
php artisan backup:run --only=database
# Verify backup created
ls -lh storage/backups/ | head -5
```
Expected: Backup file created with current timestamp.

### 3. Verify Database Migrations
```bash
php artisan migrate:status
```
Expected: `2026_02_15_add_necta_alignment_columns` shows as "Ran".

### 4. Check Database Columns Exist
```bash
php artisan tinker
# Inside tinker:
DB::select("SHOW COLUMNS FROM candidates WHERE Field IN ('candidate_type', 'combination_id')")
DB::select("SHOW COLUMNS FROM candidate_subject_selections WHERE Field IN ('is_principal', 'source', 'created_by')")
exit
```
Expected: All 5 columns exist in respective tables.

### 5. Verify Application Health
```bash
php artisan tinker
# Inside tinker:
exit
```
Expected: No errors or syntax issues.

---

## Deployment: Code Push & Cache

### 6. Pull Latest Code
```bash
git pull origin main
```
Expected: Code updated to latest commit.

**For staging environments:**
```bash
git pull origin staging
```

### 7. Install Dependencies (If Changed)
```bash
# Check if composer.json changed
git diff HEAD~1 composer.json

# If changed, run:
composer install --no-dev
```
Expected: Dependencies installed without errors.

### 8. Clear All Caches
```bash
php artisan optimize:clear
```
Or manually (more control):
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### 9. Build Production Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Expected: Cache files created in bootstrap/cache/ and storage/.

### 10. Run Migrations
```bash
php artisan migrate --force
```
Expected: "Nothing to migrate" (migrations already ran in Phase 1).

---

## Post-Deployment: Testing & Verification

### 11. Run Smoke Test Suite
```bash
php NECTA_SMOKE_TESTS_2026_02_15.php
```
Expected: Green output with "All tests passed" or "X passed, 0 failed".

**If any test fails:**
- Read the error message carefully
- Check the logs: `tail -f storage/logs/laravel.log`
- Decide: Fix in place vs rollback (see Rollback section below)

### 12. Manual Application Test
```bash
curl -I https://<your-domain>/admin
```
Expected: HTTP 200 (or redirect to login).

### 13. Test SCHOOL Candidate Workflow
1. Open browser: `https://<your-domain>/admin`
2. Navigate to: **Candidates** > **Add Candidate**
3. Fill in:
   - Name: "Test School Candidate"
   - Registration #: "TEST001"
   - District: Select any
   - School: Select any
4. **Candidate Type**: Select "SCHOOL"
5. **Combination**: Select any (e.g., "Science")
6. **Exam Year**: Select 2026
7. Click **Save**
8. Open candidate, verify subjects auto-allocated from combination
9. Check in database:
   ```bash
   mysql -u root -p irms -e "SELECT registration_number, candidate_type, combination_id FROM candidates WHERE registration_number='TEST001';"
   ```
   Expected: `TEST001 | SCHOOL | <combination_id>`

### 14. Test PRIVATE Candidate Workflow
1. Navigate to: **Candidates** > **Add Candidate**
2. Fill in:
   - Name: "Test Private Candidate"
   - Registration #: "TEST002"
   - District: Select any
3. **Candidate Type**: Select "PRIVATE"
4. Leave **Combination**: Empty
5. Click **Save**
6. Click candidate row
7. Click **Allocate Subjects** button
8. Allocate subjects (must include General Studies 111, 3+ principals)
9. Click **Apply**
10. Verify in database:
    ```bash
    mysql -u root -p irms -e "SELECT registration_number, candidate_type, combination_id FROM candidates WHERE registration_number='TEST002';"
    ```
    Expected: `TEST002 | PRIVATE | NULL`

### 15. Verify API Endpoint
```bash
# Test allocation endpoint exists (via artisan)
php artisan route:list | grep allocate-subjects
```
Expected: Route shows `POST /api/exam-types/acsee/allocate-subjects`

### 16. Check Application Logs
```bash
tail -n 50 storage/logs/laravel.log | grep -i "error\|exception\|failed"
```
Expected: No errors related to NECTA changes.

### 17. Verify Existing Data Integrity
```bash
# Check existing candidates still have subjects
mysql -u root -p irms << 'EOF'
SELECT COUNT(DISTINCT candidate_id) FROM candidate_subject_selections;
SELECT COUNT(DISTINCT candidate_id) FROM candidates WHERE exam_type_id = (SELECT id FROM exam_types WHERE name='ACSEE');
EOF
```
Expected: Subject count >= candidate count (candidates can have multiple subjects).

---

## Post-Deployment: Notification & Monitoring

### 18. Notify Operations Team
Send notification with:
- Deployment completion time
- Status: **SUCCESS**
- What was deployed: NECTA Phase 2 (SCHOOL/PRIVATE candidate support)
- What to monitor: Application logs for next 24 hours
- Who to contact if issues: Tech Lead

### 19. Set Up Monitoring
- Watch logs: `tail -f storage/logs/laravel.log`
- Monitor error rates in your logging service
- Check API endpoint performance

### 20. Update Documentation
- Mark this deployment date in deployment log
- Archive this runbook with sign-offs
- Share operator guide with support team

---

## Rollback Procedure

If critical issues occur during or after deployment:

### Quick Rollback (Code Only)

**Step 1: Revert Code**
```bash
# Get previous commit hash
git log --oneline | head -5

# Revert to previous version
git revert HEAD --no-edit
# OR
git reset --hard HEAD~1
```

**Step 2: Clear Caches**
```bash
php artisan optimize:clear
```

**Step 3: Verify**
```bash
curl -I https://<your-domain>/admin
php artisan tinker
exit
```

**Expected Result:** Application loads normally with old code.

### Full Rollback (Database + Code)

If data integrity issues occurred:

**Step 1: Restore Database**
```bash
# List available backups
ls -lh storage/backups/

# Restore specific backup
php artisan backup:restore --from=<backup-filename>
```

**Step 2: Revert Code**
```bash
git reset --hard HEAD~1
```

**Step 3: Clear Caches**
```bash
php artisan optimize:clear
```

**Step 4: Verify**
```bash
php artisan migrate:status
mysql -u root -p irms -e "SELECT COUNT(*) FROM candidates;"
```

**Expected Result:** Database restored to pre-deployment state, old code running.

---

## Decision Points

### If Smoke Tests Fail

**1. Read the error message carefully**
```bash
php NECTA_SMOKE_TESTS_2026_02_15.php 2>&1 | tee smoke-test.log
```

**2. Check logs for context**
```bash
tail -n 100 storage/logs/laravel.log
```

**3. Decide:**
- **If minor issue (e.g., cache not built):** Fix and re-test
  ```bash
  php artisan view:cache
  php NECTA_SMOKE_TESTS_2026_02_15.php
  ```
- **If major issue (e.g., migration missing):** Rollback immediately

### If Manual Tests Fail

**1. Check error details**
- Look at validation error messages
- Check network tab in browser developer tools
- Review server logs

**2. Attempt fix:**
- If code issue: hot-fix in place, re-test
- If data issue: investigate, do NOT delete data
- If configuration issue: verify env variables

**3. If unresolved:**
- Escalate to Tech Lead
- Prepare for rollback

### If Application Performance Degrades

**1. Check for slow queries**
```bash
# Enable query logging (temporarily)
php artisan tinker
DB::enableQueryLog();
# ... perform test operation ...
dd(DB::getQueryLog());
exit
```

**2. Check memory usage**
```bash
free -h
ps aux | grep php
```

**3. If degradation persists:**
- Consider rollback
- Schedule performance investigation

---

## Sign-Off & Documentation

### Deployment Completion Form

```
Deployment Date:        ________________
Deployment Time:        ________________
Deployed By:            ________________
Reviewed By:            ________________

Environment:            [ ] Production  [ ] Staging  [ ] Local

Pre-Deployment Checks:
  [ ] Git status verified
  [ ] Database backed up
  [ ] Migrations verified
  [ ] Application health checked

Deployment:
  [ ] Code pulled
  [ ] Dependencies installed (if needed)
  [ ] Caches cleared
  [ ] Production caches built
  [ ] Migrations run

Post-Deployment Tests:
  [ ] Smoke tests passed
  [ ] Application loads
  [ ] SCHOOL candidate workflow tested
  [ ] PRIVATE candidate workflow tested
  [ ] API endpoint verified
  [ ] Logs checked (no errors)
  [ ] Data integrity verified

Rollback Status:
  [ ] Not required
  [ ] Executed (reason: _____________)

Issues Encountered:
  None / [Describe]: _________________________________

Approval Sign-Off:
  Tech Lead:          ________________  Date: ________
  Operations Manager: ________________  Date: ________
  Database Admin:     ________________  Date: ________
```

---

## Troubleshooting

### "Command not found: php artisan"
**Cause:** Wrong directory or PHP not in PATH  
**Fix:**
```bash
which php
cd /path/to/irms
php artisan --version
```

### "Database backup failed"
**Cause:** Backup tool not configured or no disk space  
**Fix:**
```bash
# Manual backup
mysqldump -u root -p irms > irms-backup-$(date +%Y%m%d).sql
# Verify
ls -lh irms-backup-*.sql
```

### "Migrations did not run"
**Cause:** Already ran or permission issue  
**Fix:**
```bash
php artisan migrate:status
php artisan migrate --force
# Check if actually migrated
php artisan migrate:status
```

### "Smoke tests fail with database connection"
**Cause:** .env not loaded or database offline  
**Fix:**
```bash
cat .env | grep DB_
# Test connection manually
mysql -u root -p irms -e "SELECT 1;"
```

### "View cache error"
**Cause:** View files have syntax errors  
**Fix:**
```bash
php artisan view:clear
# Check blade syntax
php artisan view:cache
# If still fails, check blade files
php artisan view:clear
```

---

## References

- NECTA Phase 2 Implementation: See thread linked in overview
- AcseeAllocationValidator: `app/Services/AcseeAllocationValidator.php`
- API Endpoint: `routes/web.php` (POST /api/exam-types/acsee/allocate-subjects)
- UI Changes: `resources/views/exam-types/acsee.blade.php`
- Database Schema: Migration `2026_02_15_add_necta_alignment_columns`
- Operator Guide: See NECTA_OPERATOR_QUICK_GUIDE_2026_02_15.md

---

## Support Contacts

- **Technical Issues**: [IT Support Email]
- **Database Issues**: [DBA Email]
- **Operator Questions**: [Operations Manager Email]
- **Emergency**: [On-call Tech Lead Phone]

---

**Document Version**: 1.0  
**Created**: 2026-02-15  
**Status**: Production Ready  
**Approved By**: [Signature Line]
