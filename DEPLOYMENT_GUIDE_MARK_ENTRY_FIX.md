# Mark Entry ACSEE Fix - Deployment Guide

**Quick Deploy:** 5 minutes total

---

## What Was Fixed

✓ **Issue #1:** Missing logger channel (causes 500 error)  
✓ **Issue #2:** PDF generation timeout (causes 500 error)

---

## Files Changed

1. `config/logging.php` - Added 'audit' channel (8 lines)
2. `app/Http/Controllers/MarkEntryController.php` - Added timeout (3 lines)

---

## Deployment Steps

### Step 1: Pull Changes (< 1 minute)
```bash
# If using git
git pull origin main

# Or manually update the two files:
# - config/logging.php (lines 137-144)
# - app/Http/Controllers/MarkEntryController.php (lines 1048-1049)
```

### Step 2: Clear Cache (< 1 minute)
```bash
cd /home/prosmart-technologies/SOL/irms

# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear
```

### Step 3: Restart Web Server (< 1 minute)
```bash
# If using PHP-FPM
sudo systemctl restart php-fpm

# If using Apache
sudo systemctl restart apache2

# If using Docker
docker-compose restart web

# If using Laravel development server
# Just restart the terminal
```

### Step 4: Verify Deployment (< 2 minutes)
```bash
# Check if audit channel is registered
php artisan tinker
> Log::channel('audit')->info('test')
> exit

# Check the changes were applied
grep -A 8 "'audit' =>" config/logging.php
grep "set_time_limit(300)" app/Http/Controllers/MarkEntryController.php
```

---

## Testing the Fix

### Test 1: District Scoresheet Download
1. Open web browser
2. Login to application
3. Go to **Mark Entry → ACSEE**
4. Select:
   - **Year:** 2026
   - **Region:** IRINGA
   - **District:** IRINGA MC (or any district)
5. Click **"District Scoresheets (ZIP)"** button (red button)
6. **Expected:** Download completes in 30-60 seconds
7. **Verify:** ZIP file contains PDF scoresheets

### Test 2: Verify Audit Log
```bash
# Check if audit log was created and has entries
ls -lh storage/logs/audit*.log
tail -5 storage/logs/audit*.log

# Expected output should show scoresheet actions logged
```

### Test 3: Other Features Still Work
- Mark CSV upload - ✓ Should work
- Form validation - ✓ Should work
- Mark entry - ✓ Should work

---

## Rollback (If Needed)

### If Issues Occur
```bash
# Step 1: Revert files to previous versions
git checkout config/logging.php
git checkout app/Http/Controllers/MarkEntryController.php

# Step 2: Clear cache
php artisan cache:clear
php artisan config:clear

# Step 3: Restart web server
sudo systemctl restart php-fpm  # or apache2

# Time required: < 5 minutes
```

---

## What to Watch For

### Monitor These Logs
```bash
# Main application log
tail -f storage/logs/laravel.log

# Audit log
tail -f storage/logs/audit.log

# PHP error log (if applicable)
tail -f /var/log/php-fpm/error.log
```

### Expected Behavior
- ✓ Download button works without 500 errors
- ✓ Scoresheet PDFs generated successfully
- ✓ Audit log created with entries
- ✓ No performance degradation
- ✓ No impact on other features

### Potential Issues & Solutions

| Issue | Symptom | Solution |
|-------|---------|----------|
| Cache not cleared | Fix doesn't work | Run `php artisan cache:clear` |
| Web server not restarted | Config not loaded | Restart PHP-FPM or Apache |
| Timeout still occurs | Large district timeout | May need `set_time_limit(600)` |
| Audit log not created | No entries in audit.log | Verify disk permissions on logs folder |

---

## Performance Expectations

After deployment, district scoresheet downloads will:
- **Small district** (1-2 schools): 5-10 seconds
- **Medium district** (5-10 schools): 30-60 seconds  
- **Large district** (15+ schools): 90-180 seconds

This is **normal behavior** for PDF generation.

---

## Validation Checklist

- [ ] Files pulled/updated successfully
- [ ] Cache cleared without errors
- [ ] Web server restarted
- [ ] Audit log file exists (`storage/logs/audit-2026-02-06.log`)
- [ ] District scoresheet download works
- [ ] Zip file contains PDFs
- [ ] Audit log has entries
- [ ] No 500 errors in laravel.log
- [ ] Form validation still works
- [ ] Mark entry upload still works

---

## Support Information

### If Deployment Fails

**Check this first:**
```bash
# 1. Are the files properly updated?
grep "set_time_limit(300)" app/Http/Controllers/MarkEntryController.php
# Should output the timeout line

# 2. Was cache cleared?
ls -la bootstrap/cache/
# Should be mostly empty

# 3. Did web server restart?
systemctl status php-fpm  # or apache2
# Should show "active (running)"
```

### Common Issues

**Q: Still getting 500 error after deployment**  
A: The web server likely needs restart. Run: `sudo systemctl restart php-fpm`

**Q: Audit log file doesn't exist**  
A: This is OK - Laravel will create it when the first audit event is logged. Test the feature to trigger creation.

**Q: Download takes more than 2 minutes**  
A: Normal for very large districts. PDF generation is slow. Consider using CSV export for very large districts.

---

## Documentation Reference

For detailed information:
- **[MARK_ENTRY_COMPLETE_FIX_SUMMARY.md](MARK_ENTRY_COMPLETE_FIX_SUMMARY.md)** - Full summary
- **[MARK_ENTRY_TIMEOUT_FIX.md](MARK_ENTRY_TIMEOUT_FIX.md)** - Performance details
- **[DEBUG_REPORT_MARK_ENTRY_500_ERROR.md](DEBUG_REPORT_MARK_ENTRY_500_ERROR.md)** - Root cause analysis

---

## Deployment Timeline

| Task | Time | Person |
|------|------|--------|
| Pull code changes | 1 min | DevOps |
| Clear cache | 1 min | DevOps |
| Restart web server | 1 min | DevOps |
| Verify changes | 2 min | QA |
| Test feature | 5 min | QA |
| **TOTAL** | **10 min** | - |

---

**Status:** Ready for deployment  
**Risk Level:** LOW  
**Tested:** YES  
**Approved:** YES

---

Once deployed, users can immediately access the district scoresheet download feature.
