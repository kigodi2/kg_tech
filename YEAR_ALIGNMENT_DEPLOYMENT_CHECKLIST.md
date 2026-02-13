# Year Alignment - Deployment Checklist

**Project**: ACSEE Year-Based Data Alignment  
**Version**: 1.0  
**Date**: February 01, 2026  
**Status**: Ready for Deployment  

---

## 📋 Pre-Deployment (Dev Environment)

### Code Review
- [ ] Review migration file: `database/migrations/2026_02_01_enforce_exam_year_relationships.php`
- [ ] Review model changes: `CandidateExamRegistration.php` and `CandidateSubjectSelection.php`
- [ ] Review service: `app/Services/ExamYear/ExamYearValidationService.php`
- [ ] Review service updates: `app/Services/MarkImport/SubjectFilterService.php`
- [ ] Review controller updates: `MarkEntryController.php` and `CandidateController.php`
- [ ] Review view changes: `mark-entry/index.blade.php`
- [ ] Review command: `AlignLegacyACSEEYear.php`
- [ ] Review all comments marked IMPORTANT

### Testing in Development
- [ ] Run migration: `php artisan migrate` ✓ (No errors expected)
- [ ] Test Tinker: `CandidateExamRegistration::first()->examYear` ✓ (Should return exam year)
- [ ] Test Artisan: `php artisan acsee:align-legacy-year --help` ✓ (Should show help text)
- [ ] Manual test: GET `/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1`
- [ ] Verify response includes: `success`, `data`, `message`, `code` fields
- [ ] Test with locked year: Verify 422 response with `YEAR_LOCKED` code
- [ ] Test with no candidates: Verify 422 response with `NO_CANDIDATES` code

### Documentation Review
- [ ] Read: `YEAR_ALIGNMENT_IMPLEMENTATION_GUIDE.md`
- [ ] Read: `YEAR_ALIGNMENT_QUICK_REFERENCE.md`
- [ ] Read: `YEAR_ALIGNMENT_DELIVERY_SUMMARY.md`
- [ ] Understand: Key constraints and assumptions

### Git & Version Control
- [ ] Verify commits are all present: `git log --oneline | head -10`
- [ ] Verify no uncommitted changes: `git status`
- [ ] Tag release: `git tag -a v1.0-year-alignment -m "Year alignment implementation"`
- [ ] Push tags: `git push origin --tags`

---

## 🔐 Pre-Deployment (Staging Environment)

### Environment Setup
- [ ] Pull code: `git pull origin main`
- [ ] Verify environment: `.env` file configured
- [ ] Verify database: Connection working
- [ ] Clear any old caches: `php artisan cache:clear`

### Database Backup
- [ ] Create full backup: 
  ```bash
  mysqldump -u root -p irms > backup_staging_2026_02_01_pre.sql
  ```
- [ ] Verify backup file exists and has size > 1MB
- [ ] Store backup in safe location
- [ ] Note backup timestamp and file path

### Pre-Migration Status
- [ ] Check: Total candidates in database
  ```bash
  php artisan tinker
  > DB::table('candidates')->count()
  > DB::table('candidate_exam_registrations')->count()
  > DB::table('candidate_subject_selections')->count()
  ```
- [ ] Record counts for comparison after migration

### Run Migration
- [ ] Execute: `php artisan migrate`
- [ ] Watch output for: Migrating and Migrated messages
- [ ] Check for errors: Should see NO errors
- [ ] Time recorded: _________ seconds

### Post-Migration Verification
- [ ] Check: New columns exist
  ```bash
  php artisan tinker
  > Schema::hasColumn('candidate_exam_registrations', 'exam_year_id')
  > Schema::hasColumn('candidate_subject_selections', 'exam_year_id')
  ```
- [ ] Check: Data backfilled correctly
  ```bash
  > DB::table('candidate_exam_registrations')->where('exam_year_id', null)->count()
  # Should return 0
  ```
- [ ] Check: FK constraints working
  ```bash
  > CandidateExamRegistration::first()->examYear
  # Should return exam year object
  ```
- [ ] Check: Indexes created
  ```bash
  > DB::select("SHOW INDEX FROM candidate_exam_registrations")
  ```

### Clear Caches
- [ ] Clear app cache: `php artisan cache:clear`
- [ ] Clear config cache: `php artisan config:cache`
- [ ] Clear route cache: `php artisan route:cache`

### API Testing
- [ ] Test endpoint (valid scenario):
  ```bash
  curl "http://staging/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1" \
    -H "Accept: application/json"
  # Expected: 200 OK with subjects (or empty array if no candidates)
  ```

- [ ] Test endpoint (locked year):
  ```bash
  # First lock a year in database, then test
  curl "http://staging/api/mark-entry/acsee/subjects-by-school?exam_year=2023&school_id=1"
  # Expected: 422 with code: YEAR_LOCKED
  ```

- [ ] Verify response JSON structure:
  ```
  {
    "success": boolean,
    "data": array,
    "has_candidates": boolean,
    "candidate_count": number,
    "message": string,
    "code": string (on 422)
  }
  ```

### UI Testing
- [ ] Open mark entry page in browser
- [ ] Select valid school with ACSEE candidates
- [ ] Verify: Subjects dropdown populates (no errors in console)
- [ ] Verify: Message shows: "Subjects shown are based on..."
- [ ] Select school with NO candidates
- [ ] Verify: Yellow warning message appears
- [ ] Verify: Subjects dropdown stays empty
- [ ] Verify: No JavaScript errors in console

### Database Integrity Tests
- [ ] Check FK constraints:
  ```bash
  php artisan tinker
  > $reg = CandidateExamRegistration::first();
  > $reg->examYear  # Should not throw error
  > $reg->examYear->year_label  # Should return year
  ```

- [ ] Check indexes are being used:
  ```bash
  # Run a query and check EXPLAIN plan
  EXPLAIN SELECT * FROM candidate_exam_registrations WHERE exam_year_id = 1;
  # Should see index used in output
  ```

### Audit Log Testing
- [ ] Verify table exists: `Schema::hasTable('exam_year_audit_logs')`
- [ ] Run alignment command: `php artisan acsee:align-legacy-year`
  - Select a test year
  - Confirm the operation
  - Verify entry created in `exam_year_audit_logs`

### Performance Baseline
- [ ] Measure query performance (before vs after)
  - Test subject filter query: Should be < 100ms
  - Test registration lookup: Should be < 50ms
- [ ] Check slow query log: No new slow queries from year alignment

### Sign-Off for Staging
- [ ] All tests passed: _________________ (Name/Date)
- [ ] Ready for production: YES / NO
- [ ] Issues found: (list any issues discovered)
- [ ] Rollback tested: (optional - test rollback procedure)

---

## 🚀 Production Deployment

### Pre-Deployment Notification
- [ ] Notify team: "Starting IRMS deployment - Year Alignment v1.0"
- [ ] Notify stakeholders: Mark entry will have brief downtime
- [ ] Check: No active mark entry sessions
- [ ] Check: No scheduled jobs running
- [ ] Schedule: Deploy during low-traffic window

### Production Backup
- [ ] Create FULL backup:
  ```bash
  mysqldump -u root -p irms > backup_prod_2026_02_01_pre.sql
  ```
- [ ] Verify backup: File size > 100MB (expecting production data)
- [ ] Store backup: Secure location with access restricted
- [ ] Document: Backup timestamp and file path

### Code Deployment
- [ ] Pull latest code:
  ```bash
  cd /path/to/irms
  git pull origin main
  git log --oneline -1  # Verify latest commit
  ```

- [ ] Verify version tag:
  ```bash
  git describe --tags
  # Should show: v1.0-year-alignment
  ```

### Run Migration (Production)
- [ ] Connection verified: `php artisan tinker` (test DB connection)
- [ ] Run migration with output:
  ```bash
  php artisan migrate --verbose
  ```
- [ ] Monitor: Watch for any errors (should see NONE)
- [ ] Time recorded: _________ seconds

### Post-Migration Production Checks
- [ ] Verify data integrity:
  ```bash
  php artisan tinker
  > DB::table('candidate_exam_registrations')->where('exam_year_id', null)->count() # Should be 0
  > CandidateExamRegistration::count() # Compare to pre-migration count
  ```

- [ ] Verify indexes:
  ```bash
  > DB::select("SHOW INDEX FROM candidate_exam_registrations WHERE Column_name = 'exam_year_id'")
  # Should show 2 indexes
  ```

### Clear Production Caches
- [ ] Clear all caches:
  ```bash
  php artisan cache:clear
  php artisan config:cache
  php artisan route:cache
  ```

- [ ] Verify cache cleared: Check `/storage/framework/cache/` is empty

### Smoke Tests (Production)
- [ ] Test API endpoint:
  ```bash
  curl "https://production/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer YOUR_TOKEN"
  # Should return 200 OK or 422 (never 500)
  ```

- [ ] Login to production: Can you access mark entry page?
- [ ] Load mark entry page: No errors in browser console?
- [ ] Select school: Subjects load without error?
- [ ] Check error logs: Any new errors related to year alignment?
  ```bash
  tail -100 /var/log/laravel.log | grep -i "year\|alignment\|error"
  ```

### Monitor & Alert
- [ ] Enable monitoring: Error tracking service (Sentry, etc.)
- [ ] Set alert thresholds:
  - 422 errors > 10 per minute (investigate)
  - 500 errors > 1 (immediate investigation)
- [ ] Add log pattern: Alert on "exam_year_id" errors

### Post-Deployment Notification
- [ ] Notify team: "Deployment complete - Year Alignment v1.0"
- [ ] Share: Key metrics and monitoring links
- [ ] Document: Any issues encountered and how they were resolved

### Sign-Off for Production
- [ ] Deployment completed: YES / NO
- [ ] Time: _____________ to _____________
- [ ] Deployed by: _________________ (Name)
- [ ] Verified by: _________________ (Name)
- [ ] Issues encountered: (list any, even minor ones)
- [ ] Monitoring active: YES / NO
- [ ] Rollback ready: YES / NO

---

## 🔄 Post-Deployment (All Environments)

### Day 1 (First 24 Hours)
- [ ] Monitor error logs hourly
- [ ] Check: Any new 422 or 500 errors?
- [ ] Check: Any performance degradation?
- [ ] Test: Create new ACSEE candidate registration
- [ ] Test: Try to edit locked year (should fail)
- [ ] Review: Audit logs for any anomalies

### Week 1
- [ ] Monitor: No unusual patterns
- [ ] Performance: Acceptable (compare to baseline)
- [ ] User feedback: Any issues reported?
- [ ] Audit logs: Review for operational insights
- [ ] Database: Check for bloat or index fragmentation

### Month 1
- [ ] Long-term stability: No recurring issues
- [ ] Performance trending: Stable or improving?
- [ ] Data integrity: All audit checks passing?
- [ ] Documentation: Update if any edge cases found

---

## 🚨 Rollback Procedure (If Needed)

### Decision to Rollback
- [ ] Issue severity: Critical data loss / corruption?
- [ ] Approval: Team lead approval obtained?
- [ ] Communication: Notify stakeholders of rollback

### Rollback Steps
1. **Stop application**: 
   ```bash
   php artisan down "Maintenance for rollback"
   ```

2. **Revert migration**:
   ```bash
   php artisan migrate:rollback --step=1 --verbose
   ```

3. **Revert code**:
   ```bash
   git revert HEAD
   git push origin main
   ```

4. **Clear caches**:
   ```bash
   php artisan cache:clear
   ```

5. **Restore backup** (if data corruption):
   ```bash
   mysql -u root -p irms < backup_prod_2026_02_01_pre.sql
   ```

6. **Resume application**:
   ```bash
   php artisan up
   ```

### Post-Rollback
- [ ] Verify: Application working
- [ ] Check: All data restored correctly
- [ ] Notify: Stakeholders of rollback completion
- [ ] Review: What went wrong and why

---

## 📊 Deployment Summary

| Phase | Status | Duration | Notes |
|-------|--------|----------|-------|
| Pre-Deployment Review | ☐ | __:__ | |
| Staging Migration | ☐ | __:__ | |
| Staging Testing | ☐ | __:__ | |
| Production Backup | ☐ | __:__ | |
| Production Migration | ☐ | __:__ | |
| Production Verification | ☐ | __:__ | |
| Monitoring Setup | ☐ | __:__ | |

---

## 📝 Deployment Notes

**Deployment Team Member**: _________________________  
**Date**: _________________________  
**Start Time**: _________________________  
**End Time**: _________________________  
**Status**: ☐ Successful ☐ Rollback ☐ Partial

**Issues Encountered**:
```
(Document any issues, even minor ones)
```

**Resolution**:
```
(How issues were resolved)
```

**Lessons Learned**:
```
(Any insights for future deployments)
```

**Sign-Off**:
- Deployment Lead: _________________________ (Signature/Date)
- Verification: _________________________ (Signature/Date)
- Operations: _________________________ (Signature/Date)

---

## 📞 Support Contacts

| Role | Contact | Phone | Email |
|------|---------|-------|-------|
| DevOps Lead | __________ | __________ | __________ |
| Database Admin | __________ | __________ | __________ |
| Application Support | __________ | __________ | __________ |
| Escalation (Manager) | __________ | __________ | __________ |

---

## 📖 Documentation References

- **Technical Guide**: `YEAR_ALIGNMENT_IMPLEMENTATION_GUIDE.md`
- **Quick Reference**: `YEAR_ALIGNMENT_QUICK_REFERENCE.md`
- **Delivery Summary**: `YEAR_ALIGNMENT_DELIVERY_SUMMARY.md`
- **Migration File**: `database/migrations/2026_02_01_enforce_exam_year_relationships.php`

---

**Deployment Package Version**: 1.0  
**Prepared Date**: February 01, 2026  
**Status**: READY FOR DEPLOYMENT  

**All critical items must be checked before proceeding to production.**
