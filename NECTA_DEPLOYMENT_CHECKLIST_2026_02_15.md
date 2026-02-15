# NECTA-Aligned ACSEE Deployment Checklist
**Date**: 2026-02-15  
**Feature**: NECTA-aligned ACSEE Registration & Subject Allocation (Phase 2)  
**Status**: Ready for Production

---

## PRE-DEPLOYMENT (Execute in order)

### 1. Backup Database
- [ ] Run `php artisan backup:run --only=database`
- [ ] Verify backup file exists in `/storage/backups/`
- [ ] Document backup filename: `_____________________`

### 2. Verify Database Migrations
- [ ] Check migrations applied: `php artisan migrate:status`
- [ ] Confirm `2026_02_15_add_necta_alignment_columns` is listed as "Ran"
- [ ] Verify columns exist:
  ```bash
  mysql -u root -p irms -e "DESCRIBE candidates;" | grep -E 'candidate_type|combination_id'
  mysql -u root -p irms -e "DESCRIBE candidate_subject_selections;" | grep -E 'is_principal|source|created_by'
  ```

### 3. Code Review
- [ ] Review changes in `/app/Services/AcseeAllocationValidator.php`
- [ ] Review API endpoint in `/routes/web.php` (POST `/api/exam-types/acsee/allocate-subjects`)
- [ ] Review UI modal in `/resources/views/exam-types/acsee.blade.php`
- [ ] Review registration form in `/resources/views/registration/candidates.blade.php`

### 4. Cache & Dependencies
- [ ] Run `php artisan cache:clear`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan view:clear`
- [ ] Verify no compilation errors: `php artisan tinker --help` (should show no errors)

---

## DEPLOYMENT

### 5. Code Push
- [ ] Commit all changes: `git status` shows clean working tree
- [ ] Push to production: `git push origin main`
- [ ] Verify production branch updated

### 6. Production Cache Clear
- [ ] SSH to production server
- [ ] Run `php artisan cache:clear`
- [ ] Run `php artisan config:cache`
- [ ] Restart queue workers (if applicable): `php artisan queue:restart`

---

## SMOKE TESTS (Run in order)

### 7. System Health Check
- [ ] Application loads without errors: `curl https://irms-domain.com/`
- [ ] Admin dashboard accessible
- [ ] No 500 errors in logs: `tail -f storage/logs/laravel.log`

### 8. SCHOOL Candidate Test
- [ ] Navigate to Candidates > Add Candidate
- [ ] Select **SCHOOL** as Candidate Type
- [ ] Choose a combination (e.g., Science)
- [ ] Save candidate
- [ ] Verify `candidate_type = 'SCHOOL'` in database
- [ ] Verify subjects allocated from combination

### 9. PRIVATE Candidate Test
- [ ] Navigate to Candidates > Add Candidate
- [ ] Select **PRIVATE** as Candidate Type
- [ ] Leave Combination field **empty**
- [ ] Save candidate
- [ ] Verify `candidate_type = 'PRIVATE'` and `combination_id = NULL` in database
- [ ] Allocate subjects manually via allocation modal

### 10. Subject Allocation API Test
Run the smoke test script (see next file):
```bash
php NECTA_SMOKE_TESTS_2026_02_15.php
```
- [ ] All tests pass (green output)
- [ ] No validation errors
- [ ] API responds < 500ms

### 11. Data Integrity Test
- [ ] Existing candidates (pre-deployment) still have subjects allocated
- [ ] No duplicate subject allocations for any candidate
- [ ] General Studies (code 111) present in all allocations
- [ ] Principal subject counts are correct

### 12. Performance Check
- [ ] Page load times normal (< 2s)
- [ ] No database query timeouts
- [ ] Memory usage stable

---

## POST-DEPLOYMENT

### 13. Logging & Monitoring
- [ ] Set up alerts for `AcseeAllocationValidator` exceptions
- [ ] Monitor endpoint: `POST /api/exam-types/acsee/allocate-subjects`
- [ ] Check for any "Sanitized exception" warnings in logs

### 14. Operator Notification
- [ ] Email operators about new "Candidate Type" field
- [ ] Share operator guide (see below)
- [ ] Confirm receipt and understanding

### 15. Documentation
- [ ] Update admin manual with PRIVATE candidate workflow
- [ ] Document when to use SCHOOL vs PRIVATE
- [ ] Archive this checklist with completion timestamps

---

## Operator Quick Guide

### When to use SCHOOL:
- Candidate is registered through school
- Use existing combination template for subjects
- System automatically allocates subjects

### When to use PRIVATE:
- Candidate is self-registered or from external source
- No combination template applies
- Manually select individual subjects via modal
- Must select at least 3 principals + General Studies (111)

---

## Rollback Plan (if issues occur)

### Quick Rollback:
1. `git revert <commit-hash>` (latest deployment)
2. `php artisan cache:clear`
3. Verify in logs that old code is running
4. Database remains intact (migrations were Phase 1)

### Full Rollback:
- Restore database: `php artisan backup:restore --from=<backup-filename>`
- Revert code to previous stable commit
- Clear caches

---

## Sign-Off

**Deployed By**: ___________________  
**Date/Time**: ___________________  
**All Checklist Items Completed**: [ ] Yes [ ] No  
**Issues Encountered**: None / [describe]:  
**Rollback Executed**: [ ] Yes [ ] No  

**Approval**:  
- Operations Manager: ___________________
- Tech Lead: ___________________
