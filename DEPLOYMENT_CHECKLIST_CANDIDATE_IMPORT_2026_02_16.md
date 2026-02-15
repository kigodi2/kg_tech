# Deployment Checklist - Candidate Import System
**Date**: 2026-02-16  
**Component**: Candidate Import with Auto-Subject Allocation  
**Priority**: High  
**Status**: Ready for Production

---

## Pre-Deployment Verification

- [ ] **Code Review**
  - [ ] CandidateImportService.php reviewed (lines 121-129)
  - [ ] No breaking changes to API contracts
  - [ ] No new dependencies added
  - [ ] No database migrations needed

- [ ] **Environment Check**
  - [ ] Database is reachable and healthy
  - [ ] Laravel application is running
  - [ ] Exam years 2024-2026 exist in database
  - [ ] ACSEE exam type is configured
  - [ ] At least one school exists in database

---

## Deployment Steps

### Step 1: Code Deployment
```bash
# Navigate to project
cd /home/prosmart-technologies/SOL/irms

# Verify current branch
git branch

# Pull latest code (if using git)
# or deploy from your CI/CD pipeline

# Verify file has changes
grep -A8 "Validate exam year if provided" app/Services/Candidates/CandidateImportService.php
```

**Expected Output:**
```
// Validate exam year if provided (from CSV or UI dropdown)
// The exam_year is optional in the CSV - it can come from the UI dropdown instead
$csvExamYear = $record['exam_year'] ?? null;
```

### Step 2: Clear Cache (Optional but Recommended)
```bash
# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Warm up cache
php artisan view:cache
php artisan config:cache
```

### Step 3: Verify Routes
```bash
# List candidate import routes
php artisan route:list | grep "candidates/import"
```

**Expected Output:**
```
POST   api/candidates/import/validate          CandidateImportController@validateImport
POST   api/candidates/import/commit            CandidateImportController@commitImport
POST   api/candidates/import/async             CandidateImportController@asyncBulkImport
GET    api/candidates/import/template          CandidateImportController@downloadTemplate
POST   api/candidates/import/download-errors   CandidateImportController@downloadErrorReport
```

### Step 4: Health Check
```bash
# Test API connectivity
curl -s http://localhost:8000/api/candidates/import/template > /dev/null && echo "✓ API responding"

# Check database connection
php artisan tinker
>>> DB::connection()->getPDO();
>>> exit;
```

---

## Post-Deployment Testing

### Test 1: Validate Import Endpoint
```bash
# Create test CSV
cat > test_candidates.csv << 'EOF'
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S0001,Test School,M,P0652,SCHOOL,PCM,
P0001,Test Private,F,P0652,PRIVATE,,111|102|103
EOF

# Get CSRF token (if needed)
CSRF=$(curl -s http://localhost:8000/login | grep -oP "csrf-token" | head -1)

# Test validation endpoint
curl -X POST http://localhost:8000/api/candidates/import/validate \
  -H "X-CSRF-TOKEN: $CSRF" \
  -F "file=@test_candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip" | jq .

# Expected response
# {
#   "success": true,
#   "message": "All rows valid",
#   "create_count": 2,
#   "error_count": 0,
#   "can_import": true
# }
```

### Test 2: Check Candidate Page
1. Navigate to `http://localhost:8000/dashboard`
2. Go to **Registration** → **Candidates**
3. Click **"Import Candidates"** button
4. Verify modal appears with:
   - [ ] Exam Year dropdown (default: 2026)
   - [ ] Import Mode selection (Skip/Replace)
   - [ ] File upload area
   - [ ] Drag & drop support

### Test 3: Check ACSEE Page
1. Navigate to **Exams** → **ACSEE Management**
2. Verify filters work:
   - [ ] Year filter shows available years
   - [ ] Type filter shows SCHOOL/PRIVATE options
3. If test candidates exist:
   - [ ] S0001 appears with PCM subjects
   - [ ] P0001 appears with allocated subjects (111, 102, 103)

### Test 4: Check Logs
```bash
# Check Laravel logs for errors
tail -f storage/logs/laravel.log

# Look for any ERROR or Exception lines
grep -i "error\|exception" storage/logs/laravel.log | tail -20

# Expected: No errors related to candidate import
```

---

## Rollback Plan (If Needed)

### If Issues Occur:
1. **Stop using the import feature** - don't upload new CSV files
2. **Check logs** - identify the specific error
3. **Revert code** - go back to previous version if needed
4. **Clear cache** - ensure no stale data

```bash
# If need to rollback
git revert HEAD
php artisan cache:clear

# Or manually revert the file
git checkout HEAD~1 app/Services/Candidates/CandidateImportService.php
```

---

## Monitoring Post-Deployment

### Daily Checks
- [ ] Check Laravel logs for import-related errors
- [ ] Verify no import-related exceptions in logs
- [ ] Test import functionality if time permits

### Weekly Checks
- [ ] Review import statistics (if dashboard available)
- [ ] Verify ACSEE allocations are visible
- [ ] Check database for any orphaned records

### Error Alert Triggers
- [ ] Any "Missing required column" errors in logs → **CRITICAL**
- [ ] Database connection errors during import → **CRITICAL**
- [ ] Subject allocation failures → **HIGH**
- [ ] ACSEE registration failures → **HIGH**

---

## Sign-Off

### Deployment Team
- [ ] **Name**: _________________  **Date**: _________  **Time**: _________
- [ ] **Verified**: API responding correctly
- [ ] **Verified**: Routes configured properly
- [ ] **Verified**: No errors in logs

### QA Team
- [ ] **Name**: _________________  **Date**: _________  **Time**: _________
- [ ] **Tested**: Import validation works
- [ ] **Tested**: ACSEE page shows allocations
- [ ] **Tested**: No UI errors in browser console

### Operations
- [ ] **Name**: _________________  **Date**: _________  **Time**: _________
- [ ] **Deployed**: Code is in production
- [ ] **Monitored**: No errors in first hour
- [ ] **Approved**: Ready for user testing

---

## Contact Information

### If Issues Arise
- **Backend Issues**: Contact dev team
- **Database Issues**: Contact DBA
- **UI Issues**: Contact frontend team
- **General Questions**: Refer to QUICK_START_CANDIDATE_IMPORT_2026_02_16.md

---

## Deployment Notes

```
Deployment Date: _______________
Deployed By: ___________________
Environment: Development / Staging / Production
Version: CandidateImport-2026-02-16
Notes: _________________________
_______________________________
_______________________________
```

---

## Success Criteria

All of the following must be true for deployment to be considered successful:

✅ Code deployed without errors  
✅ No database migration errors  
✅ Routes are configured and responding  
✅ Import validation endpoint returns valid response  
✅ Candidate page modal opens without errors  
✅ ACSEE management page displays allocated subjects  
✅ No errors in Laravel logs  
✅ Test import succeeds  
✅ Can see imported candidates in database  

---

**Deployment Status**: 🟢 READY TO DEPLOY

This checklist should be completed before considering the deployment successful.
